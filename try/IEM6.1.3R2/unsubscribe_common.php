<?php
/**
 * Common procedure for "unsubscribe"
 *
 *
 * @todo refactor
 */

// The file cannot be called directly
if (!defined('IEM_UNSUBSCRIBE_HACK')) {
	header('Location: index.php');
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

/**
 * Ignore requests from ClamAV
 */
if (isset($_SERVER['HTTP_USER_AGENT']) && strstr($_SERVER['HTTP_USER_AGENT'],'ClamAV')) {
	exit();
}


$sendstudio_functions = new Sendstudio_Functions();
$sendstudio_functions->LoadLanguageFile('frontend');

$statstype = false;
$statid = 0;
$validLists = array();
$foundparts = array();
$subscriber_id = 0;

$subscriberapi = $sendstudio_functions->GetApi('Subscribers');

$listapi = $sendstudio_functions->GetApi('Lists');

$statsapi = $sendstudio_functions->GetApi('Stats');

$errors = array();


// ----- The following GET request must exists in order for this unsubscribe request to be valid.
	$areas_to_check = array('M', 'C');
	foreach ($areas_to_check as $key) {
		$tempParts = IEM::requestGetGET($key, false);
		if ($tempParts === false) {
			$GLOBALS['DisplayMessage'] = GetLang('InvalidUnsubscribeURL');
			$sendstudio_functions->ParseTemplate('Default_Form_Message');
			exit();
		}

		$foundparts[strtolower($key)] = $tempParts;
	}
// -----


// ----- The following GET request are optional (depending on the request type itself)
	$parts_to_check = array('N', 'A', 'L');
	foreach ($parts_to_check as $each) {
		$tempParts = IEM::requestGetGET($each, false);
		if ($tempParts === false) {
			continue;
		}

		$foundparts[strtolower($each)] = intval($tempParts);
	}
// -----



$subscriber_id = intval($foundparts['m']);
$confirmcode = $foundparts['c'];
