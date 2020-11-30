<?php
/**
* This file handles processing of unsubscribe forms.
* It uses the appropriate api's to check subscribers, custom field values and lists.
*
* @see Forms_API
* @see Lists_API
* @see Subscribers_API
* @see CustomFields_API
* @see Email_API
*
* @version     $Id: unsubform.php,v 1.21 2007/09/04 02:44:58 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
*/

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

/**
* Make sure we have a valid form id.
*/
$form = 0;
if (isset($_GET['form'])) {
	$form = (int)$_GET['form'];
}
if ($form <= 0) {
	echo 'Invalid Form.';
	exit();
}

$sendstudio_functions = new Sendstudio_Functions();
$sendstudio_functions->LoadLanguageFile('frontend');

$formapi = $sendstudio_functions->GetApi('Forms');
$listapi = $sendstudio_functions->GetApi('Lists');
$emailapi = $sendstudio_functions->GetApi('Email');
$emailapi->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

$subscriberapi = $sendstudio_functions->GetApi('Subscribers');
$customfieldsapi = $sendstudio_functions->GetApi('CustomFields');

$loaded = $formapi->Load($form);
if (!$loaded) {
	echo 'Invalid Form.';
	exit();
}

/**
* See if the user has an smtp server set.
*/
$user = GetUser($formapi->ownerid);
if ($user->smtpserver) {
	$emailapi->SetSmtp($user->smtpserver, $user->smtpusername, $user->smtppassword, $user->smtpport);
}

$errors = array();

$formtype = $formapi->GetFormType($formapi->Get('formtype'));

$captcha_form = ($formapi->Get('usecaptcha') == 1) ? true : false;

$captcha_api = $sendstudio_functions->GetApi('Captcha');

if ($captcha_form) {
	$captcha_answer = (isset($_POST['captcha'])) ? $_POST['captcha'] : false;
	$captcha_code = IEM::sessionGet('CaptchaCode');
	if ($captcha_answer != $captcha_api->LoadSecret()) {
		$errors[] = GetLang('Form_CaptchaIncorrect');
		DisplayErrorPage($formapi, $formtype, $errors);
		exit;
	}
}

$lists = array();

/**
* Check we're posting a proper form and have a list to unsubscribe from.
* If the 'lists' variable isn't in the posted form, check the form from sendstudio.
* If it only has one list associated with it, then that's what you are unsubscribing from.
* If it has multiple lists, then show an error message.
*/
if (!isset($_POST['lists'])) {
	$form_lists = $formapi->Get('lists');
	if (sizeof($form_lists) > 1) {
		$errors[] = GetLang('Form_NoLists_' . $formtype);
	} else {
		$lists = $form_lists;
	}
} else {
	$lists = $_POST['lists'];
	if (!is_array($lists)) {
		$lists = array($lists);
	}
}

/**
* Now make sure we're including an email address on our form.
*/
if (!isset($_POST['email']) || $_POST['email'] == '') {
	$errors[] = GetLang('Form_EmailEmpty_' . $formtype);
	$email = '';
} else {
	$email = $_POST['email'];
}

$subscriberinfo = array();

$subscriber_ids = array();

$not_on_list = array();

/**
* Go through and make sure we're actually on the list(s)..
*/
foreach ($lists as $p => $listid) {
	$listid = (int)$listid;
	$check = $subscriberapi->IsSubscriberOnList($email, $listid, 0, true);

	$listload = $listapi->Load($listid);

	if (!$listload) {
		$errors[] = sprintf(GetLang('FormFail_InvalidList'), $listid);
		continue;
	}

	$listname = $listapi->Get('name');
	$listdetails[$listid] = $listapi;

	if (!$check) {
		$not_on_list[] = $listname;
		continue;
	}

	$subscriber_ids[$listid] = $check;

	$subscriberlistinfo = $subscriberapi->LoadSubscriberList($check, $listid);
	$subscriberlistinfo['Lists'] = $listname;
	$subscriberinfo[$listid] = $subscriberlistinfo;
}

// if we're not on any of the available lists, then show error messages appropriately.
if (sizeof($not_on_list) == sizeof($lists)) {
	foreach ($not_on_list as $p => $listname) {
		$errors[] = sprintf(GetLang('FormFail_NotOnList'), $listname);
	}
}

/**
* We have errors? No point doing anything else. Print out the errors and stop.
*/
if (!empty($errors)) {
	DisplayErrorPage($formapi, $formtype, $errors);
	exit;
}

/**
* If there are no errors, let's do the rest of the work.
*/
$ipaddress = GetRealIp();
$subscriberapi->Set('unsubscriberequestip', $ipaddress);

/**
* If the form needs us to confirm our unsubscribe request, set it up appropriately.
*/
if ($formapi->Get('requireconfirm') == 'y' || $formapi->Get('requireconfirm') == '1') {
	$subscriberapi->Set('unsubscribeconfirmed', 0);
} else {
	$subscriberapi->Set('unsubscribeconfirmed', 1);
	$subscriberapi->Set('unsubscribeip', $ipaddress);
}

/**
* Mark the request per list in the database.
* This also handles if we don't need to confirm (ie it will mark them as unsubscribed in the db).
*/
foreach ($lists as $p => $listid) {
	// if we're only subscribed to one list of the options available,
	// this won't be set for all.
	if (!isset($subscriber_ids[$listid])) {
		$subscriberapi->Set('formid', 0);
		// make sure the form is set to 0 - so it's not picked up by the confirmation process.
		//$subscriberapi->SetForm($subscriber_ids[$listid]);
		continue;
	}
	/**
	* Set the formid so the confirmation process can check it and act accordingly.
	*/
	$subscriberapi->Set('formid', $form);
	$subscriberapi->UnsubscribeRequest($subscriber_ids[$listid], $listid);
	$subscriberapi->SetForm($subscriber_ids[$listid]);
}

$subscriber['CustomFields'] = array();

/**
* Put this into a 'subscriber' array so the email api can access it.
*/
foreach ($subscriberinfo as $p => $subinfo) {
	foreach ($subscriberinfo[$p]['CustomFields'] as $k => $details) {
		$fieldvalue = $details['data'];
		if ($fieldvalue == '') {
			$fieldvalue = GetLang('SubscriberNotification_EmptyField');
		}

		$fieldname = $details['fieldname'];
		$fieldid = $details['fieldid'];
		$subscriber['CustomFields'][$fieldid] = array('name' => $details['fieldname'], 'value' => $fieldvalue);
	}
	if (isset($subinfo['Lists'])) {
		$subscriber['Lists'][] = $subinfo['Lists'];
	}
	$confcode = $subscriberinfo[$p]['confirmcode'];
}

$subscriber['subscriberid'] = 0;
$subscriber['emailaddress'] = $email;

// save the confirmation code.
$subscriber['confirmcode'] = $confcode;

$emailformat = 't';

$emailapi->Set('forcechecks', false);

$mailing_list_names = array();

if (isset($subscriber['Lists']) && !empty($subscriber['Lists'])) {
	foreach ($subscriber['Lists'] as $listname) {
		if (strpos($listname, ',') !== false) {
			$mailing_list_names[] = '"' . $listname . '"';
		} else {
			$mailing_list_names[] = $listname;
		}
	}
}

// if we need to confirm the subscriber's request, do it here.
if ($formapi->Get('requireconfirm') == 'y' || $formapi->Get('requireconfirm') == '1') {

	$emailapi->Set('Subject', $formapi->GetPage('ConfirmPage', 'emailsubject'));
	$emailapi->Set('FromName', $formapi->GetPage('ConfirmPage', 'sendfromname'));
	$emailapi->Set('FromAddress', $formapi->GetPage('ConfirmPage', 'sendfromemail'));
	$emailapi->Set('ReplyTo', $formapi->GetPage('ConfirmPage', 'replytoemail'));
	$emailapi->Set('BounceAddress', $formapi->GetPage('ConfirmPage', 'bounceemail'));

	$emailapi->AddBody('text', $formapi->GetPage('ConfirmPage', 'emailtext'));
	$emailapi->AddBody('html', $formapi->GetPage('ConfirmPage', 'emailhtml'));

	$emailapi->AddRecipient($email, false, $emailformat);

	reset($subscriberinfo);
	$subinfo = current($subscriberinfo);
	$subinfo['listname'] = implode(',', $mailing_list_names);
	$emailapi->AddCustomFieldInfo($email, $subinfo);
	$emailapi->Set('Multipart', true);

	$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);
	$mail_results = $emailapi->Send(true);

	$confirmurl = $formapi->GetPage('ConfirmPage', 'url');
	if ($confirmurl) {
		header('Location: ' . $confirmurl);
	} else {
		$html = $formapi->GetPage('ConfirmPage', 'html');
		echo $formapi->CleanVersion($html, $subinfo);
	}
	exit;
}

/**
* Do we need to send the list owner a notification? Let's check!
*/
$send_notification = false;

/**
* Clear out the email and recipients just in case.
*/
$emailapi->ClearRecipients();
$emailapi->ForgetEmail();
$emailapi->Set('forcechecks', false);

foreach ($lists as $p => $listid) {
	$notifyowner = $listdetails[$listid]->Get('notifyowner');
	if (!$notifyowner) {
		continue;
	}
	$send_notification = true;

	$listowneremail = $listdetails[$listid]->Get('owneremail');
	$listownername = $listdetails[$listid]->Get('ownername');
	$emailapi->AddRecipient($listowneremail, $listownername, 't', 0);
}

/**
* If we need to send an email notification, lets set up the email here and send it off.
*/
if ($send_notification) {
	$emailapi->Set('Subject', GetLang('UnsubscribeNotification_Subject'));
	$emailapi->Set('FromName', false);
	$emailapi->Set('FromAddress', $email);
	$emailapi->Set('ReplyTo', $email);
	$emailapi->Set('BounceAddress', false);

	$body = '';
	$body .= sprintf(GetLang('UnsubscribeNotification_Field'), GetLang('EmailAddress'), $email);

	if (isset($subscriber['Lists']) && !empty($subscriber['Lists'])) {
		$body .= sprintf(GetLang('SubscriberNotification_Lists'), implode(',', $mailing_list_names));
	}

	foreach ($subscriber['CustomFields'] as $fieldid => $details) {
		$fieldvalue = $details['value'];
		$fieldname = $details['name'];
		$body .= sprintf(GetLang('UnsubscribeNotification_Field'), $fieldname, $fieldvalue);
	}
	$emailbody = sprintf(GetLang('UnsubscribeNotification_Body'), $body);

	$emailapi->AddBody('text', $emailbody);
	$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);
	$emailapi->Send(false);
}

/**
* If we need to send a thanks (sorry?) email to the subscriber, do it here.
*/
if ($formapi->Get('sendthanks') == 1) {
	$emailapi->ClearRecipients();
	$emailapi->ForgetEmail();
	$emailapi->Set('forcechecks', false);

	$emailapi->Set('Subject', $formapi->GetPage('ThanksPage', 'emailsubject'));
	$emailapi->Set('FromName', $formapi->GetPage('ThanksPage', 'sendfromname'));
	$emailapi->Set('FromAddress', $formapi->GetPage('ThanksPage', 'sendfromemail'));
	$emailapi->Set('ReplyTo', $formapi->GetPage('ThanksPage', 'replytoemail'));
	$emailapi->Set('BounceAddress', $formapi->GetPage('ThanksPage', 'bounceemail'));

	$emailapi->AddBody('text', $formapi->GetPage('ThanksPage', 'emailtext'));
	$emailapi->AddBody('html', $formapi->GetPage('ThanksPage', 'emailhtml'));

	reset($subscriberinfo);
	$subinfo = current($subscriberinfo);
	$emailapi->AddCustomFieldInfo($email, $subinfo);

	$emailapi->AddRecipient($email, false, $emailformat);
	$emailapi->Set('Multipart', true);
	$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);
	$mail_results = $emailapi->Send(true);
}

/**
* Finally, show the "Thanks/Sorry" page to the subscriber.
*/
$thanksurl = $formapi->GetPage('ThanksPage', 'url');
if ($thanksurl) {
	header('Location: ' . $thanksurl);
} else {
	reset($subscriberinfo);
	$subinfo = current($subscriberinfo);
	$subinfo['listname'] = implode(',', $mailing_list_names);
	echo $formapi->CleanVersion($formapi->GetPage('ThanksPage', 'html'), $subinfo);
}

/**
 * DisplayErrorPage
 * Displays an error page based on the form details.
 * Either it redirects (using a header redirect) to the error url
 * Or it fills in the errors for the page and sets the title based on the content
 *
 * @param Object $formapi The form api which holds all of the details. This is used to work out whether to redirect to a url or show a page
 * @param String $formtype The type of form we're displaying errors for. This is so we can set the page title properly.
 * @param Array $errors The errors from trying to update the subscriber details, including whether a custom field was missing, or an email address is invalid etc.
 *
 * @return Void Doesn't return anything. Either redirects to the error url (grabbed via the formapi passed in), or it shows the error html (again grabbed from the formapi passed in).
*/
function DisplayErrorPage($formapi, $formtype, $errors)
{
	$pagetitle = GetLang('FormFail_PageTitle_' . $formtype);
	$errorlist = '<br/>-' . implode('<br/>-', $errors);
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
}

?>
