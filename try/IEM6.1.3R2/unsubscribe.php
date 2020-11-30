<?php
/**
 * This file will handle "unsubcribe" action that is instigated by subscribers.
 *
 *
 * @todo refactor
 */

// Include common procedure.
defined('IEM_UNSUBSCRIBE_HACK') or define('IEM_UNSUBSCRIBE_HACK', true);
require_once dirname(__FILE__) . '/unsubscribe_common.php';

defined('SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE') or define('SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE', '0');

if (!SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE) {
	require_once dirname(__FILE__) . '/unsubscribe_confirmed.php';
	exit();
}

$primary_listid = 0;
if (isset($foundparts['l'])) {
	$primary_listid = $foundparts['l'];
}

if (isset($foundparts['a'])) {
	$statstype = 'auto';
	$statid = $foundparts['a'];
} elseif (isset($foundparts['n'])) {
	$statstype = 'newsletter';
	$statid = $foundparts['n'];
}
$validLists = array();
if ($statstype) {
	$validLists = $subscriberapi->GetSubscribersListByStatOwner($statid, $subscriber_id, $statstype);
} elseif($primary_listid > 0) {
	// default
	$validLists[] = array('listid' => $primary_listid, 'subscriberid' => $subscriber_id);
}

if($primary_listid <= 0 && $subscriber_id > 0){
    //get all lists for subscriber
    $subscriber_email = $subscriberapi->GetEmailForSubscriber($subscriber_id);
    $db = IEM::getDatabase();
    $subscriber_email = $db->Quote($subscriber_email);
    $result = $db->query("SELECT listid FROM [|PREFIX|]list_subscribers WHERE emailaddress = '{$subscriber_email}'");
    while($row = $db->Fetch($result)){
        $all_lists[] = $row['listid'];    
    }
    if(!empty($all_lists)){
        foreach($all_lists as $lid){
	       $validLists[] = array('listid' => $lid, 'subscriberid' => $subscriber_id);
        }
    }
}


$displayList = array();
foreach ($validLists as $eachList) {
    if($eachList['listid'] <= 0){continue;}
	$listapi->Load($eachList['listid']);
	$subscriberlistinfo = $subscriberapi->LoadSubscriberList($eachList['subscriberid'], $eachList['listid']);
	$displayList[] = array('listid' => $eachList['listid'], 'name' => $listapi->Get('name'), 'cc' => $subscriberlistinfo['confirmcode'], 'subscriberid' => $eachList['subscriberid']);
}
if (!sizeof($displayList)) {
	$GLOBALS['DisplayMessage'] = GetLang('DefaultUnsubscribeMessage');
	$sendstudio_functions->ParseTemplate('Default_Form_Message');
	exit();
}

$GLOBALS['Message'] = '<div style="padding:10px;">'.GetLang('Unsubscribe_Form_Note').'</div>';

$tpl = GetTemplateSystem();
$tpl->Assign('page', $_GET);
$tpl->Assign('list', $displayList);
$tpl->Assign('primary_listid', $primary_listid);
echo $tpl->ParseTemplate('unsubscribe_form', true);
