<?php
/**
* This file handles printing modify detail forms.
* It uses the appropriate api's to check subscribers, custom field values and lists.
*
* @see Forms_API
* @see Lists_API
* @see Subscribers_API
* @see CustomFields_API
* @see Email_API
*
* @version     $Id: modifydetails.php,v 1.13 2007/06/19 04:43:22 chris Exp $
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
require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';

$sendstudio_functions = new Sendstudio_Functions();
$sendstudio_functions->LoadLanguageFile('frontend');

$subscriberapi = $sendstudio_functions->GetApi('Subscribers');
$customfieldsapi = $sendstudio_functions->GetApi('CustomFields');
$listapi = $sendstudio_functions->GetApi('Lists');
$formapi = $sendstudio_functions->GetApi('Forms');

/**
* This is used in the 'updatedetails.php' file so we don't have to re-do all of these checks.
*/
$subscriber_on_lists = array();
$subscriber_custom_fields = array();

$errors = array();

$listid = 0;
if (isset($_GET['List'])) {
	$listid = (int)$_GET['List'];
} else {
	if (isset($_GET['L'])) {
		$listid = (int)$_GET['L'];
	}
}

$subscriber_id = $primary_subscriber_id = 0;
$confirmcode = false;

if (isset($_GET['M'])) {
	if (!isset($_GET['C'])) {
		// found a member id but no confirm code? Eek!
		echo GetLang('InvalidModifyURL');
		exit();
	}
	$subscriber_id = $primary_subscriber_id = (int)$_GET['M'];
	$confirmcode = $_GET['C'];
}

$form = 0;
if (isset($_GET['F'])) {
	$form = (int)$_GET['F'];
} else {
	echo GetLang('InvalidModifyURL');
	exit();
}

$loaded = $formapi->Load($form);
if (!$loaded) {
	echo GetLang('InvalidModifyURL');
	echo '<br/>' . __LINE__;
	exit();
}

$subscriber_email = $subscriberapi->GetEmailForSubscriber($subscriber_id);

if ($subscriber_email === false) {
	// the subscriber no longer exists
	echo GetLang('InvalidModifyURL');
	exit();
}

// so we know which placeholders to replace so we can pre-fill the form.
$placeholders = $placeholder_values = array();

$form_customfields = $formapi->Get('customfields');

// so we don't check the same fields over and over again.
$customfields_done = array();

$listdetails = array();

$email = '';

$form_lists = $formapi->Get('lists');
foreach ($form_lists as $p => $listid) {
	$listload = $listapi->Load($listid);
	if (!$listload) {
		continue;
	}

	$listdetails[$listid] = $listapi;

	// go through each list and see whether the person is subscribed or not.
	$subscriber_list_info = $subscriberapi->LoadSubscriberList($subscriber_id, $listid);

	if (empty($subscriber_list_info)) {
		$sub_id = $subscriberapi->IsSubscriberOnList($subscriber_email, $listid);
		if (!$sub_id) {
			continue;
		}
		$subscriber_list_info = $subscriberapi->LoadSubscriberList($sub_id, $listid);
	}

	if ($subscriber_list_info['subscriberid'] == $primary_subscriber_id) {
		if ($subscriber_list_info['confirmcode'] != $confirmcode) {
			$listname = $listapi->Get('name');
			$errors[] = sprintf(GetLang('ConfirmCodeDoesntMatch_Unsubscribe'), $listname);
			continue;
		}
	}

	$email = $subscriber_list_info['emailaddress'];

	$placeholders[] = '%%Email%%';
	$placeholder_values[] = $email;

	if ($subscriber_list_info['unsubscribed'] > 0) {
		$list_placeholder_val = '';
		$placeholders[] = '%%Lists_' . $listid . '%%';
		$placeholder_values[] = $listid;
	} else {
		$subscriber_on_lists[] = $listid;
		$placeholders[] = '%%Lists_' . $listid . '%%';
		$placeholder_values[] = ' CHECKED';
	}

	$format_html = $format_text = '';

	if (isset($subscriber_list_info['format'])) {
		if ($subscriber_list_info['format'] == 'h') {
			$format_html = ' SELECTED';
		} else {
			$format_text = ' SELECTED';
		}
		$placeholders[] = '%%Format_html%%';
		$placeholder_values[] = $format_html;

		$placeholders[] = '%%Format_text%%';
		$placeholder_values[] = $format_text;
	}

	if ((!isset($subscriber_list_info['CustomFields']) || empty($subscriber_list_info['CustomFields']))) {
		continue;
	}

	foreach ($subscriber_list_info['CustomFields'] as $p => $customfieldid) {
		if (in_array($p, $customfields_done)) {
			continue;
		}

		$customfield_info = $subscriber_list_info['CustomFields'];

		foreach ($customfield_info as $p => $customfield) {
			$fid = $customfield['fieldid'];
			if (!in_array($fid, array_keys($subscriber_custom_fields))) {
				$subscriber_custom_fields[$fid] = $customfield;
			}

			switch ($customfield['fieldtype']) {
				case 'checkbox':
					$data = unserialize($customfield['data']);
					if (is_array($data) && !empty($data)) {
						foreach ($data as $k => $option) {
							$placeholder_line = '%%CustomField_' . $customfield['fieldid'] . '_' . $option . '%%';
							$placeholders[] = $placeholder_line;
							$placeholder_values[] = ' CHECKED';
						}
					}
				break;

				case 'radiobutton':
					$placeholders[] = '%%CustomField_' . $customfield['fieldid'] . '_' . $customfield['data'] . '%%';
					$placeholder_values[] = ' CHECKED';
				break;

				case 'dropdown':
					$placeholders[] = '%%CustomField_' . $customfield['fieldid'] . '_' . $customfield['data'] . '%%';
					$placeholder_values[] = ' SELECTED';
				break;

				case 'date':
					$exploded_date = explode('/', $customfield['data']);
					foreach (array('dd', 'mm', 'yy') as $p => $datepart) {
						// If date is not available, then do not continue with the selection
						$item = IEM::ifsetor($exploded_date[$p], '');
						if (empty($item)) {
							continue;
						}

						$placeholders[] = '%%CustomField_'.$customfield['fieldid'].'_'.$item.'_'.$datepart.'%%';
						$placeholder_values[] = ' SELECTED';
					}
				break;

				default:
					$placeholders[] = '%%CustomField_' . $customfield['fieldid'] . '%%';
					$placeholder_values[] = htmlspecialchars($customfield['data'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}

			$customfields_done[] = $customfield['fieldid'];
		}
	}
}

if (empty($subscriber_on_lists)) {
	// found a member id but no confirm code? Eek!
	echo GetLang('InvalidModifyURL');
	exit();
}

if (!empty($errors)) {
	echo $errors[0];
	exit();
}

$subscriber_lists = $subscriberapi->GetAllListsForEmailAddress($subscriber_email, $form_lists);

foreach ($subscriber_lists as $details) {
	if (!in_array($details['listid'], $subscriber_on_lists)) {
		$subscriber_on_lists[] = $details['listid'];
		$placeholders[] = '%%Lists_' . $details['listid'] . '%%';
		$placeholder_values[] = ' CHECKED';
	}
}

$subscriber_not_on_lists = array_diff($form_lists, $subscriber_on_lists);

$subscriber_info['EmailAddress'] = $email;
$subscriber_info['CustomFields'] = $subscriber_custom_fields;
$subscriber_info['SubscribedToLists'] = $subscriber_on_lists;
$subscriber_info['NotSubscribedToLists'] = $subscriber_not_on_lists;

IEM::sessionSet('SubscriberInfo', $subscriber_info);
IEM::sessionSet('Subscriber', $subscriber_id);
IEM::sessionSet('Form', $form);

$formhtml = $formapi->Get('formhtml');

$placeholders[] = '%%FORMACTION%%';
$placeholder_values[] = SENDSTUDIO_APPLICATION_URL . '/updatedetails.php';

if ($formapi->usecaptcha) {
	$captcha_api = $sendstudio_functions->GetApi('Captcha');

	$captcha_api = new Captcha_API();
	// so we don't include the session stuff in the captcha image, we set this flag for now.
	// this stops the session from being blanked out when you submit and causing an error.
	$captcha_api->Set('modify_details', true);
	$captcha_api->CreateSecret();

	$placeholders[] = '%%captchaimage%%';
	$placeholder_values[] = $captcha_api->ShowCaptcha();
}

// pre-fill the form.
$formhtml = str_replace($placeholders, $placeholder_values, $formhtml);

// get rid of anything we don't need.
$formhtml = preg_replace('/%%CustomField_(.*?)%%/', '', $formhtml);

$formhtml = preg_replace('/%%Lists_(.*?)%%/', '', $formhtml);

// print 'er out!
echo $formhtml;
