<?php
/**
* This file handles updating subscriber details.
* This is accessed by posting from the 'modifydetails.php' file.
*
* It uses the appropriate api's to check subscribers, custom field values and lists.
*
* @see Forms_API
* @see Lists_API
* @see Subscribers_API
* @see CustomFields_API
* @see Email_API
*
* @version     $Id: updatedetails.php,v 1.11 2008/02/18 02:46:44 chris Exp $
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

$sendstudio_functions = new Sendstudio_Functions();
$sendstudio_functions->LoadLanguageFile('frontend');

$subscriber_id = IEM::sessionGet('Subscriber');
$form = IEM::sessionGet('Form');

$subscriber_info = IEM::sessionGet('SubscriberInfo');

if (empty($_POST)) {
	echo "Can't post an empty form<br/>";
	exit();
}

if (!$subscriber_id || !$form) {
	echo GetLang('InvalidModifyURL');
	exit();
}

$subscriberapi = $sendstudio_functions->GetApi('Subscribers');
$customfieldsapi = $sendstudio_functions->GetApi('CustomFields');

$listapi = $sendstudio_functions->GetApi('Lists');

$formapi = $sendstudio_functions->GetApi('Forms');

$emailapi = $sendstudio_functions->GetApi('Email');

$emailapi->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

$loaded = $formapi->Load($form);
if (!$loaded) {
	echo GetLang('InvalidModifyURL');
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

$update_subscriber_ids = array();

$formtype = $formapi->GetFormType($formapi->Get('formtype'));

$captcha_form = ($formapi->Get('usecaptcha') == 1) ? true : false;

$captcha_api = $sendstudio_functions->GetApi('Captcha');

if ($captcha_form) {
	$captcha_answer = (isset($_POST['captcha'])) ? $_POST['captcha'] : false;
	$captcha_code = IEM::sessionGet('CaptchaCode');
	if ($captcha_answer != $captcha_api->LoadSecret()) {
		$errors[] = GetLang('Form_CaptchaIncorrect');
		DisplayErrorPage($formapi, $formtype, $errors);
		exit();
	}
}

$format = false;
if (isset($_POST['format'])) {
	$posted_format = $_POST['format'];
	if (in_array($posted_format, array('t', 'h'))) {
		$format = $posted_format;
	}
}

/**
* Make sure the case is the same for the new & old.
* Otherwise if you are signed up to multiple lists using different cases, they won't match.
* eg 'EMAIL@ADDRESS.COM' and 'email@address.com'
*/
$new_email = strtolower($_POST['email']);
$old_email = strtolower($subscriber_info['EmailAddress']);

// As a default contact will not be able to modify their email address
defined('SENDSTUDIO_CONTACTCANMODIFYEMAIL') or define('SENDSTUDIO_CONTACTCANMODIFYEMAIL', '0');

if (SENDSTUDIO_CONTACTCANMODIFYEMAIL && ($old_email != $new_email)) {
	$email = $new_email;
	$changed_email_address = true;
} else {
	$email = $old_email;
	$changed_email_address = false;
}

$posted_form_customfields = (isset($_POST['CustomFields'])) ? $_POST['CustomFields'] : array();

/**
* See which custom fields are on the form, so we know which ones to update and check.
*/
$loaded_customfields = $formapi->Get('customfields');

$form_customfields = array();
if (!empty($loaded_customfields)) {
	foreach ($loaded_customfields as $p => $fieldid) {
		// we don't care about 'e' or 'cf' or 'cl' custom fields.
		if (!is_numeric($fieldid)) {
			continue;
		}
		if (!in_array($fieldid, $form_customfields)) {
			$form_customfields[] = $fieldid;
		}
	}
}

/**
* Check to see if we're adding ourselves to a list.
*/
$posted_form_lists = (isset($_POST['lists'])) ? $_POST['lists'] : array();
if (!is_array($posted_form_lists)) {
	$posted_form_lists = array($posted_form_lists);
}

/**
* These are used so we can quickly see which actions we need to take.
* New mailing lists are ones (we were not on before, but ticked on the form) go into the subscribed_to_lists array.
* Lists that were previously checked but now aren't go into the 'unsubscribed_from_lists' array.
* And custom fields which have changed go into the updated_custom_fields array.
*/
$subscribed_to_lists = array();
$unsubscribed_from_lists = array();
$updated_custom_fields = array();

/**
* Check the lists we were already subscribed to.
*/
foreach ($subscriber_info['SubscribedToLists'] as $p => $listid) {

	/**
	* If the list is in the posted form, then we're staying on the list.
	* We need to check if we're changing the email address.
	* If we are, then see whether we are on the list already or not.
	*/
	if (in_array($listid, $posted_form_lists)) {
		if ($changed_email_address) {
			$subscriber_check = $subscriberapi->IsSubscriberOnList($new_email, $listid);
			if ($subscriber_check !== false) {
				$listapi->Load($listid);
				$listname = $listapi->Get('name');
				$errors[] = sprintf(GetLang('NewEmailAlreadyOnList'), $new_email, $listname);
			}
		} else {
			/**
			* If the format option has been posted, we need to update the user preferences based on that format.
			*
			* So we need to:
			* - find/load the subscriber info
			* - update the list preferences.
			*/
			if ($format) {
				$subscriber_check = $subscriberapi->IsSubscriberOnList($email, $listid, 0, true);
				$subscriberapi->Set('format', $format);
				$subscriberapi->Set('confirmed', 1);
				$subscriberapi->UpdateList($subscriber_check, $listid);
			}
		}
		continue;
	}

	/**
	* If the email address has changed, we need to check the old email address to see whether to unsubscribe them or not.
	* If the email has not changed, then check the current email address.
	*/
	if ($changed_email_address) {
		$subscriber_check = $subscriberapi->IsSubscriberOnList($old_email, $listid, 0, true);
	} else {
		$subscriber_check = $subscriberapi->IsSubscriberOnList($email, $listid, 0, true);
	}

	/**
	* We're not on the list? Keep going.
	*/
	if ($subscriber_check === false) {
		continue;
	}

	/**
	* Since the list we were on isn't in the form results posted, we're unsubscribing.
	* We keep the 'unsubscribed_lists' array so we can send the list owner an email to let them know.
	* We also do this in case we find an error with another list or a custom field - we don't want a person to be half-unsubscribed.
	*/
	$unsubscribed_from_lists[] = $listid;
}

/**
* Check the lists we are not subscribed to.
*/
foreach ($subscriber_info['NotSubscribedToLists'] as $p => $listid) {

	/**
	* If this list isn't in the form then we're not subscribing to it - no need to do anything.
	*/
	if (!in_array($listid, $posted_form_lists)) {
		continue;
	}

	$banned = false;

	/**
	* Make sure they aren't banned from subscribing to the list.
	* Have to check the new address if we have changed it, if we haven't, check the old one.
	*/
	if ($changed_email_address) {
		list($banned, $reason) = $subscriberapi->IsBannedSubscriber($new_email, $listid);
	} else {
		list($banned, $reason) = $subscriberapi->IsBannedSubscriber($email, $listid);
	}

	if ($banned) {
		$errors[] = $reason;
		continue;
	}

	/**
	* If we have changed our email address, make sure we're not already on that new list.
	*/
	if ($changed_email_address) {
		$subscriber_check = $subscriberapi->IsSubscriberOnList($new_email, $listid);
		if ($subscriber_check !== false) {
			$listapi->Load($listid);
			$listname = $listapi->Get('name');
			$errors[] = sprintf(GetLang('NewEmailAlreadyOnList'), $new_email, $listname);
			continue;
		}
	} else {

		$subscriber_check = $subscriberapi->IsSubscriberOnList($email, $listid, 0, true);

		/**
		* if they are already on the list, don't do anything else.
		*/
		if ($subscriber_check !== false) {
			continue;
		}

		/**
		* They might have previously unsubscribed, re-activate them.
		*/
		if ($sid = $subscriberapi->IsUnSubscriber(false, $listid, $subscriber_id)) {
			$subscribed_to_lists[] = $listid;
			continue;
		}
	}

	/**
	* We keep the 'subscribed_to_lists' array so we can send the list owner an email to let them know.
	* We also do this in case we find an error with another list or a custom field - we don't want a person to be half-subscribed.
	*/
	$subscribed_to_lists[] = $listid;
}

$subscriber_custom_fields = array();

/**
* Now we've been added/removed from mailing lists, we'll check the custom fields.
*/
foreach ($form_customfields as $p => $fieldid) {

	$value = (isset($posted_form_customfields[$fieldid])) ? $posted_form_customfields[$fieldid] : null;

	$customfieldsapi->Load($fieldid);

	$sub_field = $customfieldsapi->LoadSubField();

	$required = $customfieldsapi->IsRequired();

	$subscriber_custom_fields[$fieldid] = array('fieldname' => $sub_field->GetFieldName(), 'fieldvalue' => $sub_field->GetRealValue($value));

	/**
	* See if we've updated the custom field while we're at it.
	*/
	if (is_array($value)) {
		$check_value = serialize($value);
	} else {
		$check_value = $value;
	}

	$updated_custom_fields[] = array('fieldid' => $fieldid, 'value' => $value);

	/**
	* If the field isn't required, if there's no value - we don't care.
	*/
	if (is_null($value) && !$required) {
		continue;
	}

	/**
	* We found a value, so we need to make sure it's valid.
	*/
	if ($customfieldsapi->ValidData($value)) {
		continue;
	}

	/**
	* If it's not valid data, then we need to remember the error.
	*/
	$errors[] = sprintf(GetLang('FormFail_InvalidData_ModifyDetails'), $customfieldsapi->GetFieldName());
}

/**
* Did we find any errors?
*/
if (!empty($errors)) {
	$pagetitle = GetLang('FormFail_PageTitle_ModifyDetails');
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
	exit();
}


/**
* Set up our email api ready to send list notifications where necessary.
*/
$emailapi->ForgetEmail();
$emailapi->Set('forcechecks', false);

$subject = GetLang('SubscriberNotification_Subject');
$fieldnametype = 'SubscriberNotification_Field';
$bodyname = 'SubscriberNotification_Body';

$emailapi->Set('Subject', $subject);
$emailapi->Set('FromName', false);
$emailapi->Set('FromAddress', $email);
$emailapi->Set('ReplyTo', $email);
$emailapi->Set('BounceAddress', SENDSTUDIO_EMAIL_ADDRESS);

$body = '';
$body .= sprintf(GetLang($fieldnametype), GetLang('EmailAddress'), $email);

foreach ($subscriber_custom_fields as $fieldid => $details) {
	$fieldname = $details['fieldname'];
	$fieldvalue = $details['fieldvalue'];
	$body .= sprintf(GetLang($fieldnametype), $fieldname, $fieldvalue);
}

$emailbody = sprintf(GetLang($bodyname), $body);

/**
* Since there are no errors, we can actually add the person to the list(s).
*/
foreach ($subscribed_to_lists as $p => $listid) {

	if ($subscriberapi->IsUnSubscriber($email, $listid)) {
		$subscriberapi->ActivateSubscriber($email, $listid);
		continue;
	}

	/**
	* Add them to the list!
	*/
	$subscriberapi->Set('confirmdate', 0);
	$subscriberapi->Set('requestdate', 0);
	$subscriberapi->Set('format', 'h');
	$sid = $subscriberapi->AddToList($email, $listid, true, true);
	$subscriberapi->ListConfirm($listid, $sid);
	$update_subscriber_ids[] = $sid;

	$listapi->Load($listid);

	/**
	* Do we need to notify the list owner?
	*/
	$notifyowner = $listapi->Get('notifyowner');
	if (!$notifyowner) {
		continue;
	}

	$emailapi->ClearRecipients();

	$listowneremail = $listapi->Get('owneremail');
	$listownername = $listapi->Get('ownername');

	$emailapi->AddRecipient($listowneremail, $listownername, 't', 0);

	$emailapi->AddBody('text', $emailbody);
	$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);
	$emailapi->Send(false);
}


/**
* Change the subject and body ready for the unsubscribe notifications.
*/
$subject = GetLang('UnsubscribeNotification_Subject');
$fieldnametype = 'UnsubscribeNotification_Field';
$bodyname = 'UnsubscribeNotification_Body';

$emailapi->Set('Subject', $subject);

$body = '';
$body .= sprintf(GetLang($fieldnametype), GetLang('EmailAddress'), $email);

foreach ($subscriber_custom_fields as $fieldid => $details) {
	$fieldname = $details['fieldname'];
	$fieldvalue = $details['fieldvalue'];
	$body .= sprintf(GetLang($fieldnametype), $fieldname, $fieldvalue);
}
$emailbody = sprintf(GetLang($bodyname), $body);


/**
* ..and remove them from the other list(s).
*/
foreach ($unsubscribed_from_lists as $p => $listid) {

	if ($changed_email_address) {
		$subscriberapi->UnsubscribeSubscriber($old_email, $listid, 0, false, '', 0);
	} else {
		$subscriberapi->UnsubscribeSubscriber($email, $listid, 0, false, '', 0);
	}

	$listapi->Load($listid);

	/**
	* Do we need to notify the list owner?
	*/
	$notifyowner = $listapi->Get('notifyowner');
	if (!$notifyowner) {
		continue;
	}

	$emailapi->ClearRecipients();

	$listowneremail = $listapi->Get('owneremail');
	$listownername = $listapi->Get('ownername');

	$emailapi->AddRecipient($listowneremail, $listownername, 't', 0);

	$emailapi->AddBody('text', $emailbody);
	$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);
	$emailapi->Send(false);
}

$all_lists_for_email = $subscriberapi->GetAllListsForEmailAddress($old_email, $formapi->Get('lists'));

foreach ($all_lists_for_email as $details) {
	if (!in_array($details['subscriberid'], $update_subscriber_ids)) {

		// make sure we're not updating a list we just unsubscribed from!
		if (!in_array($details['listid'], $unsubscribed_from_lists)) {
			$update_subscriber_ids[] = $details['subscriberid'];
		}
	}
}

/**
* We can finally update their custom field(s).
*/
foreach ($updated_custom_fields as $p => $data) {
	$fieldid = $data['fieldid'];
	$value = $data['value'];
	$subscriberapi->SaveSubscriberCustomField($update_subscriber_ids, $fieldid, $value);
}

/**
* Lastly, check if we need to update the email address.
*/
if ($changed_email_address) {
	$subscriberapi->UpdateEmailAddress($update_subscriber_ids, $new_email);
}

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
		header('Location: ' . $errorurl . '?Errors=' . urlencode($errorlist));
	} else {
		$errorpage = $formapi->GetPage('ErrorPage', 'html');
		echo str_replace(array('%%GLOBAL_ErrorTitle%%', '%%GLOBAL_Errors%%'), array($pagetitle, $errorlist), $errorpage);
	}
}

exit;
