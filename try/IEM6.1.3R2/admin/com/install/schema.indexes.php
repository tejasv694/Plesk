<?php
/**
* Indexes for both databases (mysql & postgresql).
*
* @version     $Id: schema.indexes.php,v 1.17 2008/02/06 06:54:54 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Indexes for both databases (mysql & postgresql).
* DO NOT CHANGE BELOW THIS LINE.
*/

if (!isset($queries)) {
	exit();
}

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%subscribers_email_list_idx ON %%TABLEPREFIX%%list_subscribers(emailaddress, listid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_subscribers_sub_list_idx ON %%TABLEPREFIX%%list_subscribers(subscriberid, listid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%subscribe_date_idx ON %%TABLEPREFIX%%list_subscribers(subscribedate)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_subscribers_listid_idx ON %%TABLEPREFIX%%list_subscribers(listid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_unsubscribe_sub_list_idx ON %%TABLEPREFIX%%list_subscribers_unsubscribe (subscriberid, listid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_subscribers_unsubscribe_statid_idx on %%TABLEPREFIX%%list_subscribers_unsubscribe(statid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_subscriber_bounces_statid_idx on %%TABLEPREFIX%%list_subscriber_bounces(statid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_subscriber_bounces_listid_idx on %%TABLEPREFIX%%list_subscriber_bounces(listid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_subscriber_bounces_subscriberid_idx on %%TABLEPREFIX%%list_subscriber_bounces(subscriberid)";

if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
	$queries[] = "CREATE INDEX %%TABLEPREFIX%%subscribers_data_data_idx on %%TABLEPREFIX%%subscribers_data(data (255))";
} else {
	$queries[] = "CREATE INDEX %%TABLEPREFIX%%subscribers_data_data_idx on %%TABLEPREFIX%%subscribers_data(data)";
}

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%subscribers_data_subscriber_field_idx on %%TABLEPREFIX%%subscribers_data(subscriberid, fieldid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%subscriber_data_field_subscriber_idx on %%TABLEPREFIX%%subscribers_data(fieldid, subscriberid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%autoresponders_owner_idx on %%TABLEPREFIX%%autoresponders(ownerid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%autoresponders_list_idx on %%TABLEPREFIX%%autoresponders(listid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%autoresponders_queue_idx on %%TABLEPREFIX%%autoresponders(queueid)";

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%banned_emails_list_email_idx on %%TABLEPREFIX%%banned_emails(list, emailaddress)";

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%customfield_lists_field_list_idx on %%TABLEPREFIX%%customfield_lists(fieldid, listid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%customfields_owner_idx on %%TABLEPREFIX%%customfields(ownerid)";

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%form_customfields_formid_listid_idx on %%TABLEPREFIX%%form_customfields(formid, fieldid)";

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%form_lists_formid_listid_idx on %%TABLEPREFIX%%form_lists(formid, listid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%form_pages_formid_idx on %%TABLEPREFIX%%form_pages(formid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%jobs_fkid_idx on %%TABLEPREFIX%%jobs(fkid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%jobs_jobtime_idx on %%TABLEPREFIX%%jobs(jobtime)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%jobs_queue_idx on %%TABLEPREFIX%%jobs(queueid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%jobs_owner_idx on %%TABLEPREFIX%%jobs(ownerid)";

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%jobs_lists_jobid_listid_idx on %%TABLEPREFIX%%jobs_lists(jobid, listid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%jobs_lists_listid_idx on %%TABLEPREFIX%%jobs_lists(listid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%lists_owner_idx on %%TABLEPREFIX%%lists(ownerid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%newsletters_owner_idx on %%TABLEPREFIX%%newsletters(ownerid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_autoresponders_statid_idx on %%TABLEPREFIX%%stats_autoresponders(statid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_autoresponders_autoresponderid_idx on %%TABLEPREFIX%%stats_autoresponders(autoresponderid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_emailforwards_subscriberid_idx on %%TABLEPREFIX%%stats_emailforwards(subscriberid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_emailforwards_statid_idx on %%TABLEPREFIX%%stats_emailforwards(statid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_emailforwards_listid_idx on %%TABLEPREFIX%%stats_emailforwards(listid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_emailopens_subscriberid_idx on %%TABLEPREFIX%%stats_emailopens(subscriberid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_emailopens_statid_idx on %%TABLEPREFIX%%stats_emailopens(statid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%open_statid_subscriberid on %%TABLEPREFIX%%stats_emailopens(subscriberid, statid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_emailopens_statid_opentime_idx on %%TABLEPREFIX%%stats_emailopens(statid, opentime)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_stats_emailsperhour_statid_idx on %%TABLEPREFIX%%user_stats_emailsperhour(statid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_stats_emailsperhour_userid_idx on %%TABLEPREFIX%%user_stats_emailsperhour(userid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_linkclicks_subscriberid_idx on %%TABLEPREFIX%%stats_linkclicks(subscriberid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_linkclicks_statid_idx on %%TABLEPREFIX%%stats_linkclicks(statid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_linkclicks_linkid_idx on %%TABLEPREFIX%%stats_linkclicks(linkid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_linkclicks_subscriberid on %%TABLEPREFIX%%stats_linkclicks(subscriberid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_linkclicks_statid_clicktime_idx ON %%TABLEPREFIX%%stats_linkclicks(statid, clicktime)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_links_statid_idx on %%TABLEPREFIX%%stats_links(statid)";

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%stats_newsletter_lists_list_stat_idx on %%TABLEPREFIX%%stats_newsletter_lists(listid, statid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_newsletters_queue_idx on %%TABLEPREFIX%%stats_newsletters(queueid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_newsletters_sentby_idx on %%TABLEPREFIX%%stats_newsletters(sentby)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_users_all_idx on %%TABLEPREFIX%%stats_users(userid, queuetime, queuesize)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_users_statid_idx on %%TABLEPREFIX%%stats_users(statid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%templates_owner_idx on %%TABLEPREFIX%%templates(ownerid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%users_logincheck_idx on %%TABLEPREFIX%%users(username, password)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%customfield_id_name on %%TABLEPREFIX%%customfields(fieldid, name)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%queues_id_type_recip_idx on %%TABLEPREFIX%%queues(queueid,queuetype,recipient)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%queues_id_type_processed_idx on %%TABLEPREFIX%%queues(queueid,queuetype,processed)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%queuetype_recipient_idx on %%TABLEPREFIX%%queues(queuetype, recipient)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%stats_autoresponders_recipients_stat_auto_recip ON %%TABLEPREFIX%%stats_autoresponders_recipients(statid, autoresponderid, recipient)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%list_subscriber_events_subscriberid_idx ON %%TABLEPREFIX%%list_subscriber_events(subscriberid)";

$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%folders_name_type_ownerid_idx ON %%TABLEPREFIX%%folders (name, type, ownerid);";
$queries[] = "CREATE UNIQUE INDEX %%TABLEPREFIX%%folder_item_folderid_itemid_idx ON %%TABLEPREFIX%%folder_item (folderid, itemid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%folder_user_userid_folderid_idx ON %%TABLEPREFIX%%folder_user (userid, folderid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_activitylog_userid_viewed_idx ON %%TABLEPREFIX%%user_activitylog(userid, viewed)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%triggeremails_data_datavaluestring_idx ON %%TABLEPREFIX%%triggeremails_data(triggeremailsid, datakey, datavaluestring)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%triggeremails_data_datavalueinteger_idx ON %%TABLEPREFIX%%triggeremails_data(triggeremailsid, datakey, datavalueinteger)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%triggeremails_actions_data_datavaluestring_idx ON %%TABLEPREFIX%%triggeremails_actions_data(triggeremailsactionid, datakey, datavaluestring)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%triggeremails_actions_data_datavalueinteger_idx ON %%TABLEPREFIX%%triggeremails_actions_data(triggeremailsactionid, datakey, datavalueinteger)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%triggeremails_actions_data_triggeremailsid_idx ON %%TABLEPREFIX%%triggeremails_actions_data(triggeremailsid)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%triggeremails_log_idx ON %%TABLEPREFIX%%triggeremails_log(triggeremailsid, subscriberid, action, timestamp, note)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_credit_transactiontype_idx ON %%TABLEPREFIX%%user_credit (transactiontype)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_credit_userid_transactiontype_idx ON %%TABLEPREFIX%%user_credit (userid, transactiontype)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_credit_transactiontime_idx ON %%TABLEPREFIX%%user_credit (transactiontime)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_credit_userid_transactiontime_idx ON %%TABLEPREFIX%%user_credit (userid, transactiontime)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_credit_transactiontype_transactiontime_idx ON %%TABLEPREFIX%%user_credit (transactiontype, transactiontime)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%user_credit_userid_transactiontype_transactiontime_idx ON %%TABLEPREFIX%%user_credit (userid, transactiontype, transactiontime)";

$queries[] = "CREATE INDEX %%TABLEPREFIX%%queues_unsent_queueid_idx ON %%TABLEPREFIX%%queues_unsent (queueid)";
$queries[] = "CREATE INDEX %%TABLEPREFIX%%queues_unsent_recipient_idx ON %%TABLEPREFIX%%queues_unsent (recipient)";
