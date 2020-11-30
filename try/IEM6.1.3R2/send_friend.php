<?php
/**
* This file handles processing of a send-to-friend form. It will send the necessary email to the new person, record the forward and finally display a "thanks" message at the end.
*
* @see Forms_API
* @see Lists_API
* @see Subscribers_API
* @see CustomFields_API
* @see Email_API
*
* @version     $Id: send_friend.php,v 1.15 2008/02/13 07:51:29 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
*/

/**
* Make sure the request is valid.
*/
if (empty($_POST)) {
	echo "Can't post an empty form<br/>";
	exit();
}

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

/**
* Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
*/
require_once dirname(__FILE__) . '/admin/index.php';

/**
* This file lets us get api's, load language files and parse templates.
*/
require_once(SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php');

$sendstudio_functions = new Sendstudio_Functions();
$sendstudio_functions->LoadLanguageFile('frontend');

$formapi = $sendstudio_functions->GetApi('Forms');
$emailapi = $sendstudio_functions->GetApi('Email');

$subscriberapi = $sendstudio_functions->GetApi('Subscribers');

$statsapi = $sendstudio_functions->GetApi('Stats');

$subscriber_id = IEM::sessionGet('Subscriber', false);
$form = IEM::sessionGet('Form', false);

if (!$subscriber_id || !$form) {
	echo GetLang('InvalidSendFriendURL');
	exit();
}

$formapi->Load($form);

$newsletter = IEM::sessionGet('Newsletter');
$autoresponder = IEM::sessionGet('Autoresponder');
$list = IEM::sessionGet('List');

$errors = array();

/**
* Make sure we are posting some info.
*/
if (!isset($_POST['myemail']) || $_POST['myemail'] == '') {
	$errors[] = GetLang('EnterYourEmailAddress');
}

if (!isset($_POST['friendsemail']) || $_POST['friendsemail'] == '') {
	$errors[] = GetLang('EnterYourFriendsEmailAddress');
}

/**
* Make sure the person we're forwarding to is not banned.
* This will check for bans in the suppression list for this contact list as well as the global one.
*/
list($is_banned) = $subscriberapi->IsBannedSubscriber(trim($_POST['friendsemail']), $list, false);
if ($is_banned) {
	$errors[] = GetLang('CannotSendEmail');
}

$introduction = (isset($_POST['introduction'])) ? $_POST['introduction'] : '';

/**
* There are some errors? Print them out!
*/
if (!empty($errors)) {
	$errorpage = $formapi->GetPage('ErrorPage');
	$errorlist = '<br/>-' . implode('<br/>-', $errors);
	if ($errorpage !== false) {
		$pagetitle = GetLang('FormFail_PageTitle_SendFriend');
		$errorurl = $formapi->GetPage('ErrorPage', 'url');
		if ($errorurl) {
			$concat = '?';
			if (strpos($errorurl, '?') !== false) {
				$concat = '&';
			}
			header('Location: ' . $errorurl . $concat . 'Errors=' . urlencode($errorlist));
		} else {
			$errorpage = $formapi->GetPage('ErrorPage', 'html');
			echo str_replace(array('%%GLOBAL_ErrorTitle%%', '%%GLOBAL_Errors%%'), array($pagetitle, $errorlist), $errorpage);
		}
	} else {
		$GLOBALS['DisplayMessage'] = sprintf(GetLang('DefaultErrorMessage'), $errorlist);
		$sendstudio_functions->ParseTemplate('Default_Form_Message');
	}
	exit();
}

/**
* Let's load up the newsletter/autoresponder and see what we can do with it.
*/
if ($newsletter) {
	$attachmentstype = 'newsletters';
	$stats_idtype = 'newsletter';
	$api = $sendstudio_functions->GetApi('Newsletters');
	$id = $newsletter;
}

if ($autoresponder) {
	$attachmentstype = 'autoresponders';
	$stats_idtype = 'autoresponder';
	$api = $sendstudio_functions->GetApi('Autoresponders');
	$id = $autoresponder;
}

$loaded_ok = $api->Load($id);
if (!$loaded_ok) {
	echo GetLang('NewsletterDoesntExistAnymore');
}

$fromname = "";
if (isset($_POST['myname'])) {
	$fromname = $_POST['myname'];
}
$fromemail = $_POST['myemail'];


$subject = $api->Get('subject');
$htmlbody = $api->Get('htmlbody');
$textbody = $api->Get('textbody');

$emailtext = $formapi->GetPage('SendFriendPage', 'emailtext');
$emailtext = str_replace(array('%%REFERRER_EMAIL%%', '%REFERRER_EMAIL%'), $fromemail, $emailtext);
if ($textbody) {
	$textbody = $introduction . "\n" . $emailtext . "\n" . $textbody;
}

$emailhtml = $formapi->GetPage('SendFriendPage', 'emailhtml');
$emailhtml = str_replace(array('%%REFERRER_EMAIL%%', '%REFERRER_EMAIL%'), $fromemail, $emailhtml);
if ($htmlbody) {
	$introduction = nl2br($introduction) . "<br/>" . $emailhtml . "<br/>";
	$htmlbody = preg_replace('%<body(.*?)>%i', "<body\${1}>". $introduction, $htmlbody);
}


$emailapi->Set('FromName', $fromname);
$emailapi->Set('FromAddress', $fromemail);
$emailapi->Set('ReplyTo', $fromemail);
$emailapi->Set('BounceAddress', $fromemail);

$emailapi->Set('Subject', $subject);

$mailformat = 't';

if ($textbody) {
	$emailapi->AddBody('text', $textbody);
}

if ($htmlbody) {
	$emailapi->AddBody('html', $htmlbody);
	$mailformat = 'h';
}

if ($textbody && $htmlbody) {
	$emailapi->Set('Multipart', true);
}

$attachmentslist = $sendstudio_functions->GetAttachments($attachmentstype, $id, true);
if ($attachmentslist) {
	$path = $attachmentslist['path'];
	$files = $attachmentslist['filelist'];
	foreach ($files as $p => $file) {
		$emailapi->AddAttachment($path . '/' . $file);
	}
}

$friendsemail = $_POST['friendsemail'];

$friendsname = false;
if (isset($_POST['friendsname']) && $_POST['friendsname'] != '') {
	$friendsname = $_POST['friendsname'];
}

$emailapi->AddRecipient($friendsemail, $friendsname, $mailformat);

// load up and use the old subscribers details so we can see it exactly how they received it.
$subscriberinfo = $subscriberapi->LoadSubscriberList($subscriber_id, $list, true);

/**
* need to include the newsletter or autoresponder info so proper links can be generated
* for %%webversion%% etc when the 'friend' receives the email in their inbox.
*/
if ($newsletter) {
	$subscriberinfo['newsletter'] = $newsletter;
}

if ($autoresponder) {
	$subscriberinfo['autoresponder'] = $autoresponder;
}


$listAPI = $sendstudio_functions->GetApi('Lists');
$company = $listAPI->getCompanyDetails($list);
$subscriberinfo = array_merge($subscriberinfo, $company);

$emailapi->AddCustomFieldInfo($friendsemail, $subscriberinfo);
$emailapi->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

/**
* See if the user has an smtp server set.
*/
$user = GetUser($formapi->ownerid);
if ($user->smtpserver) {
	$emailapi->SetSmtp($user->smtpserver, $user->smtpusername, $user->smtppassword, $user->smtpport);
}

$emailapi->TrackLinks(false);
$emailapi->ForceLinkChecks(false);
$emailapi->TrackOpens(false);

$emailapi->DisableUnsubscribe(true);

$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);
$mail_result = $emailapi->Send(true);

/**
* Record the forward for statistical purposes.
*/
$forwardip = GetRealIp();
$forwardtime = $statsapi->GetServerTime();

$statid = IEM::sessionGet('Statid');

$forward_details = array(
	'forwardtime' => $forwardtime,
	'forwardip' => $forwardip,
	'subscriberid' => $subscriber_id,
	'statid' => $statid,
	'listid' => $list,
	'emailaddress' => $friendsemail
);

$statsapi->RecordForward($forward_details, $stats_idtype);

/**
* After all that, we'll print out the thanks message!
*/
$thankspage = $formapi->GetPage('ThanksPage');
$thanksurl = $formapi->GetPage('ThanksPage', 'url');
if ($thanksurl) {
	header('Location: ' . $thanksurl);
} else {
	echo $thankspage['html'];
}
