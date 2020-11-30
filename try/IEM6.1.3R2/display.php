<?php
/**
* This file handles displaying archived newsletters. This displays individual archives rather than a list.
*
* @version     $Id: display.php,v 1.10 2007/12/11 00:02:08 hendri Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
*/

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

// Displaying an archive does not need a session to be started.
if (!defined('IEM_NO_SESSION')) {
	define('IEM_NO_SESSION', true);
}

// Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
require_once dirname(__FILE__) . '/admin/index.php';

if (SENDSTUDIO_IS_SETUP != 1) {
	exit;
}

/**
* This file lets us get api's, load language files and parse templates.
*/
require_once(SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php');

if (!check('rss', true)) {
	exit;
}


$sendstudio_functions = new Sendstudio_Functions();

$listapi = $sendstudio_functions->GetApi('Lists');
$newsletterapi = $sendstudio_functions->GetApi('Newsletters');
$autoapi = $sendstudio_functions->GetApi('Autoresponders');
$subscriberapi = $sendstudio_functions->GetApi('Subscribers');
$emailapi = $sendstudio_functions->GetApi('SS_Email');

$listid = 0;
if (isset($_GET['List'])) {
	$listid = (int)$_GET['List'];
} else {
	if (isset($_GET['L'])) {
		$listid = (int)$_GET['L'];
	}
}

$statid = 0;
if (isset($_GET['S'])) {
	$statid = (int) $_GET['S'];
}


$newsletterid = (isset($_GET['N'])) ? (int)$_GET['N'] : 0;
$autoresponderid = (isset($_GET['A'])) ? (int)$_GET['A'] : 0;

$subscriberid = 0;
$confirmcode = false;
$subscriberinfo = array();

if (isset($_GET['M'])) {
	if (!isset($_GET['C'])) {
		// found a member id but no confirm code? Eek!
		echo 'Invalid archive link.';
		exit();
	}
	$subscriberid = (int)$_GET['M'];
	$confirmcode = $_GET['C'];
}

/**
* Since we're displaying a specific newsletter we can check for the list before anything else.
* If it's not valid, we can abort.
*/
if (!$listid || (!$newsletterid && !$autoresponderid)) {
	echo 'Invalid archive link.';
	exit();
}
$list_loaded = $listapi->Load($listid);

if (!$list_loaded) {
	echo 'Invalid archive link.';
	exit();
}
/* #### ??????????????? #### */
$dctEvent =new EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT();
$dctEvent->lists = array($listid);

if ($newsletterid) {
	$id = $newsletterid;
	$api = $newsletterapi;
} else {
	$id = $autoresponderid;
	$api = $autoapi;
}

$loaded = $api->Load($id);

/**
* Make sure the newsletter is ok to be displayed.
* If it's not in "archive" mode or "active" mode, don't show anything.
*/
if (!$api->Archive() || !$api->Active()) {
	echo 'Invalid archive link.';
	exit();
}

$format = $api->Get('format');
if ($format == 't') {
	$description = nl2br($api->GetBody('text'));
} else {
	$description = $api->GetBody('html');
}

if ($subscriberid > 0 && $confirmcode && $listid) {
	$loaded = $api->Load($id);

	if (!$loaded) {
		echo 'Invalid archive link.';
		exit();
	}

	$sub_listinfo = $subscriberapi->LoadSubscriberList($subscriberid, $listid, true, true, true);
    if(empty($sub_listinfo)){
		echo 'Invalid archive link.';
		exit();        
    }
	if (isset($sub_listinfo['confirmcode']) && $sub_listinfo['confirmcode'] == $confirmcode) {
		$subscriberinfo = $sub_listinfo;
		$subscriberinfo['statid'] = $statid;

		$list_fields = array (
			'name' => 'listname',
			'listid' => 'listid',
			'companyname' => 'companyname',
			'companyphone' => 'companyphone',
			'companyaddress' => 'companyaddress'
		);
		foreach ($list_fields as $list_field_name => $clean_name) {
			$subscriberinfo[$clean_name] = $listapi->Get($list_field_name);
		}

		if ($newsletterid) {
			$subscriberinfo['newsletter'] = $newsletterid;
		}
        

		if ($format != 't' && $statid > 0) {
			// track the open
			$open_image = '<img src="' . SENDSTUDIO_APPLICATION_URL . '/open.php?M=' . $subscriberid . '&L=' . $listid . '&N=' . $statid . '&F=H">';
			$description = $emailapi->InsertAtEnd($description, $open_image);
		}
	}
            
    $arr = preg_match('/%%\[[a-zA-Z0-9_ ]+\]%%/i', $description);
    if(!empty($arr)){
        /**
         * Working out and replacing dynamic content place holder
         */
        $dctEvent =new EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT();
        $dctEvent->lists = array($listid);
        $dctEvent->info = array($subscriberinfo);
        $dctEvent->trigger();
        $dctEvent->text = str_replace($dctEvent->contentTobeReplaced[$subscriberinfo['subscriberid']]['tagsTobeReplaced'], $dctEvent->contentTobeReplaced[$subscriberinfo['subscriberid']]['tagsContentTobeReplaced'], $description);
        $pattern = '/%%\[[a-zA-Z0-9_ ]+\]%%/i';
        $dctEvent->text = preg_replace($pattern, '', $dctEvent->text);
    }
    
    /**
     * Replacing survey tag
     */
    $surveyEvent = new EventData_IEM_SURVEYS_REPLACETAG();
    if(isset($dctEvent->text)){
        $surveyEvent->description = $dctEvent->text;
    }else{
        $surveyEvent->description = $description;
    }
    $surveyEvent->trigger();
    $description = $surveyEvent->description;
}


header('Content-type: text/html; charset=utf8');
/* #### LOGGING.
echo $api->CleanVersion($dctEvent->text, $subscriberinfo);
#### */
echo $api->CleanVersion($description, $subscriberinfo);