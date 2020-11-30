<?php
/**
* This file handles link tracking and processing. It will record the link click and then redirect to the proper location.
*
* @version     $Id: link.php,v 1.19 2008/02/26 19:43:23 tye Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
*/

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

// Displaying an open image does not need a session.
if (!defined('IEM_NO_SESSION')) {
	define('IEM_NO_SESSION', true);
}

// Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
require_once dirname(__FILE__) . '/admin/index.php';

if (!SENDSTUDIO_IS_SETUP) {
	exit();
}

/**
* This file lets us get api's, load language files and parse templates.
*/
require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';

$sendstudio_functions = new Sendstudio_Functions();

$statsapi = $sendstudio_functions->GetApi('Stats');
$subscriberapi = $sendstudio_functions->GetApi('Subscribers');
$listapi = $sendstudio_functions->GetApi('Lists');

$foundparts = array();

foreach ($_GET as $p => $part) {
	$foundparts[strtolower($p)] = $part;
}

$linktype = 'u';
if (isset($foundparts['f'])) {
	$linktype = $foundparts['f'];
}

/**
* No link? Exit.
*/
if (!isset($foundparts['l'])) {
	echo 'Invalid Link.<br>';
	exit();
}
$linkid = (int)$foundparts['l'];

/**
* No "member" info? Exit.
*/
if (!isset($foundparts['m'])) {
	echo 'Invalid Link.<br>';
	exit();
}
$subscriberid = (int)$foundparts['m'];

if (isset($foundparts['a'])) {
	$statstype = 'auto';
	$statid = $foundparts['a'];
} else {
	$statstype = 'newsletter';
	$statid = $foundparts['n'];
}

$url = $statsapi->FetchLink($linkid, $statid);
if (!$url) {
	echo 'Invalid Link.<br>';
	exit();
}

// need to decode the url in case there are custom fields with spaces in them
// eg %%first name%% (shouldn't use decode, as the custom field uses the % character which is a scpecial character for URL)
$url = preg_replace('/%20/', ' ', $url);

// make sure it's a full url.
if (strtolower(substr($url, 0, 4)) != 'http') {
	$url = 'http://' . $url;
}

$stats_info = $statsapi->FetchStats($statid, $statstype);
if (isset($stats_info['sendtype']) && $stats_info['sendtype'] == 'triggeremail') {
	$record = $subscriberapi->GetRecordByID($subscriberid);
	if (isset($record['listid'])) {
		$stats_info['Lists'] = array($record['listid']);
	}
}

$opentime = $statsapi->GetServerTime();
$openip = GetRealIp();

$open_details = array(
	'opentime' => $opentime,
	'openip' => $openip,
	'subscriberid' => $subscriberid,
	'statid' => $statid,
	'opentype' => $linktype
);

$statsapi->RecordOpen($open_details, $statstype, true);

$lists = $stats_info['Lists'];

$listinfo = $subscriberapi->IsSubscriberOnList(null, $lists, $subscriberid, false, false, true);
$subscriberinfo = $subscriberapi->LoadSubscriberList($subscriberid, $listinfo['listid'], true);

$listapi->Load($listinfo['listid']);
$subscriberinfo['listname'] = $listapi->name;

$url = trim($statsapi->CleanVersion($url, $subscriberinfo));

/**
* IE doesn't like redirecting to urls with an anchor on the end - so we'll strip it off.
$newurl = parse_url($url);
$url = $newurl['scheme'] . '://' . $newurl['host'];
if (isset($newurl['path'])) {
	$url .= $newurl['path'];
	if (isset($newurl['query'])) {
		$url .= '?' . $newurl['query'];
	}
}
*/

$clicktime = $statsapi->GetServerTime();
$clickip = GetRealIp();

$click_details = array(
	'clicktime' => $clicktime,
	'clickip' => $clickip,
	'subscriberid' => $subscriberid,
	'statid' => $statid,
	'linkid' => $linkid,
	'listid' => $listinfo['listid'],
	'url' => $url
);

$statsapi->RecordLinkClick($click_details, $statstype);

/**
 * Do Tracking module
 */
	if ($sendstudio_functions->GetApi('module_TrackerFactory', false)) {
		$status = module_Tracker::ProcessURLForAllTracker($statid, $statstype, array('url' => $url), $subscriberinfo);
		if ($status != false) {
			$url = $status;
		}
	}
/**
 * -----
 */

header('Location: ' . $url);
