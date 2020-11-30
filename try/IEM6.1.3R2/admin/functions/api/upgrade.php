<?php
/**
* The Upgrade API.
*
* @version     $Id: upgrade.php,v 1.33 2008/02/25 06:24:05 chris Exp $
* @author Chris <chris@interspire.com>
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
*
* @package API
* @subpackage User_API
*/

/**
* Include the base api class if we need to.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This will load a user, save a user, set details and get details.
* It will also check access areas.
*
* @package API
* @subpackage User_API
*/
class Upgrade_API extends API
{

	/**
	 * FriendlyDescription
	 * @var String Friendly description of the current update
	 */
	var $FriendlyDescription = false;

	/**
	 * Error
	 * @var String Contains error string if available
	 */
	var $error = null;

	/**
	 * Offset Query
	 * @var Mixed Offset Query
	 */
	var $offset_query = '';

	/**
	* Versions of the database (left) correspond to which sendstudio versions?
	* @var Array Version array
	*/
	var $versions = array (
		'20070701' => 'NX1.3.1',
		'20071205' => 'NX1.3.3',
		'20080201' => 'NX1.4.0',
		'20080312' => 'NX1.4.1',
		'20080325' => '5.0',
		'20080610' => '5.0.1',
		'20080710' => '5.0.2',
		'20080801' => '5.0.10',
		'20080802' => '5.0.14',
		'20081215' => '5.5',
		'20090107' => '5.5.3',
		'20090112' => '5.5.4',
		'20090126' => '5.5.5',
		'20090310' => '5.5.8',
		'20090323' => '5.5.9',
		'20090331' => '5.5.10',
		'20090415' => '5.5.11',
		'20090420' => '5.6.0',
		'20090609' => '5.6.6',
		'20090715' => '5.7.0',
		'20090916' => '6.0.0',
		'20100731' => '6.1.0',
		'20101204' => '6.1.1',
		'20111011' => '6.1.2',
		'20120608' => '6.1.3'
	);

	/**
	 * Update file list for each version
	 * @var Array file list
	 */
	var $upgrades_to_run = array (
		'nx' => array (
		#	'banned_emails_drop_status',
			'banned_emails_add_banid',
			'banned_emails_change_email',
			'banned_emails_change_dateadded',
			'banned_emails_change_listid',

			'customfield_lists_create',
			'customfield_lists_insert',

			'customfields_rename',
			'customfields_change_fieldid',
			'customfields_change_fieldname',
			'customfields_change_fieldtype',
			'customfields_change_defaultvalue',
			'customfields_change_required',
			'customfields_change_fieldsettings',
			'customfields_change_owner',
			'customfields_add_createdate',
			'customfields_fix_fieldsettings',

			'jobs_create',

			'jobs_list_create',

			'queues_create',
			'queues_processed_index',
			'queues_recipient_index',
			'queues_queuetype_recipient_index',
			'queues_sequence_create',
			'queues_sequence_insert',

			'lists_change_listid',
			'lists_change_createdate',
			'lists_change_format',
			'lists_change_ownername',
			'lists_change_owneremail',
			'lists_change_notifyowner',
			'lists_change_name',
			'lists_drop_status',
			'lists_drop_cansubscribe',
			'lists_drop_canunsubscribe',
			'lists_add_bounceemail',
			'lists_add_replytoemail',
			'lists_add_bounceserver',
			'lists_add_bounceusername',
			'lists_add_bouncepassword',
			'lists_add_extramailsettings',
			'lists_add_imapaccount',
			'lists_add_subscribecount',
			'lists_add_unsubscribecount',
			'lists_add_bouncecount',
			'lists_add_ownerid',
			'list_add_company_address',
			'list_add_company_name',
			'list_add_company_phone',
			'lists_set_new_defaults',
			'lists_set_format_multipart',
			'lists_set_format_html',
			'lists_set_format_text',

			'users_rename',
			'users_change_userid',
			'users_change_name',
			'users_change_username',
			'users_change_password',
			'users_change_emailaddress',
			'users_change_status',
			'users_change_perhour',
			'users_change_permonth',
			'users_change_maxlists',
			'users_change_lastloggedin',
			'users_drop_loginstring',
			'users_drop_quickstart',
			'users_drop_summaries',
			'users_drop_attachments',
			'users_add_settings',
			'users_add_editownsettings',
			'users_add_unlimitedmaxemails',
			'users_add_textfooter',
			'users_add_htmlfooter',
			'users_change_smtpport',
			'users_change_smtpserver',
			'users_add_smtpusername',
			'users_add_smtppassword',
			'users_add_admintype',
			'users_add_listadmintype',
			'users_add_templateadmintype',
			'users_add_usertimezone',
			'users_add_infotips',
			'users_add_createdate',
			'users_add_maxemails',
			'users_add_forgotpasscode',
			'users_add_usewysiwyg',
			'users_update_defaults',
			'users_update_smtpdetails',
			'users_update_adminpermissions',
			'users_update_usewysiwyg',
			'users_drop_manager',
			'users_drop_root',

			'user_access_create',
			'user_access_convert',
			'user_access_convertowner',

			'user_permissions_create',
			'user_permissions_change_subscriber',
			'user_permissions_change_list',
			'user_permissions_change_forms',
			'user_permissions_change_customfields',
			'user_permissions_change_templates',
			'user_permissions_change_newsletters',
			'user_permissions_change_send',
			'user_permissions_change_autoresponders',
			'user_permissions_change_statistics',

			'templates_change_templateid',
			'templates_change_name',
			'templates_change_format',
			'templates_change_textbody',
			'templates_change_htmlbody',
			'templates_change_createdate',
			'templates_change_ownerid',
			'templates_add_active',
			'templates_add_isglobal',
			'templates_set_new_defaults',
			'templates_set_format_multipart',
			'templates_set_format_html',
			'templates_set_format_text',

			'settings_create',
			'settings_set_new_defaults',
			'settings_cron_schedule_create',
			'settings_cron_schedule_populate',

			'form_lists_change_formid',
			'form_lists_change_listid',

			'form_customfields_rename',
			'form_customfields_change_formid',
			'form_customfields_change_fieldid',
			'form_customfields_change_fieldorder',
			'form_customfields_drop_adminid',
			'form_customfields_fix_fieldorder',

			'form_pages_create',
			'form_pages_create_responses',

			'forms_change_formid',
			'forms_change_name',
			'forms_change_requireconfirm',
			'forms_change_sendthanks',
			'forms_change_ownerid',
			'forms_change_createdate',
			'forms_add_contactform',
			'forms_add_usecaptcha',
			'forms_add_changeformat',
			'forms_add_chooseformat',
			'forms_add_design',
			'forms_add_formhtml',
			'forms_set_new_defaults',
			'forms_change_formtype',
			'forms_drop_status',
			'forms_drop_formcode',
			'forms_drop_selectlists',
			'forms_drop_contenttypeid',
			'forms_drop_templateid',
			'forms_drop_sendemail',
			'forms_drop_sendname',

			'list_subscriber_bounces_create',

			'list_subscribers_unsubscribe_create',

			'list_subscribers_rename',
			'list_subscribers_change_memberid',
			'list_subscribers_change_listid',
			'list_subscribers_change_emailaddress',
			'list_subscribers_change_format',
			'list_subscribers_update_format_html',
			'list_subscribers_update_format_text',
			'list_subscribers_change_confirmed',
			'list_subscribers_change_confirmcode',
			'list_subscribers_change_subscribedate',
			'list_subscribers_change_formid',
			'list_subscribers_drop_importid',
			'list_subscribers_add_confirmip',
			'list_subscribers_add_requestip',
			'list_subscribers_add_requestdate',
			'list_subscribers_add_confirmdate',
			'list_subscribers_add_bounced',
			'list_subscribers_add_unsubscribed',
			'list_subscribers_add_unsubscribeconfirmed',
			'list_subscribers_set_unsubscribed',
			'list_subscribers_set_unsubscribeconfirmed',
			'list_subscribers_add_to_unsubscribe',
			'list_subscribers_drop_status',
			'list_subscribers_add_domainname',
			'list_subscribers_update_domain',
			'list_subscribers_update_subscribecount',
			'list_subscribers_update_unsubscribecount',

			'autoresponders_change_autoresponderid',
			'autoresponders_change_listid',
			'autoresponders_change_hoursaftersubscription',
			'autoresponders_change_format',
			'autoresponders_change_subject',
			'autoresponders_change_sendfromemail',
			'autoresponders_change_sendfromname',
			'autoresponders_change_bounceemail',
			'autoresponders_change_replytoemail',
			'autoresponders_change_htmlbody',
			'autoresponders_change_textbody',
			'autoresponders_change_createdate',
			'autoresponders_change_searchcriteria',
			'autoresponders_change_ownerid',
			'autoresponders_change_trackopens',
			'autoresponders_change_tracklinks',
			'autoresponders_change_multipart',
			'autoresponders_change_name',
			'autoresponders_drop_attachmentids',
			'autoresponders_add_queueid',
			'autoresponders_add_active',
			'autoresponders_add_charset',
			'autoresponders_add_to_firstname',
			'autoresponders_add_to_lastname',
			'autoresponders_add_embedimages',
			'autoresponders_add_autorespondersize',
			'autoresponders_set_new_defaults',
			'autoresponders_set_createdate',
			'autoresponders_fix_queues',
			'list_subscribers_drop_lastresponderid',
			'autoresponders_set_format_multipart',
			'autoresponders_set_format_html',
			'autoresponders_set_format_text',

			'newsletters_rename',
			'newsletters_change_newsletterid',
			'newsletters_change_name',
			'newsletters_change_createdate',
			'newsletters_change_subject',
			'newsletters_change_textbody',
			'newsletters_change_htmlbody',
			'newsletters_change_ownerid',
			'newsletters_change_format',
			'newsletters_drop_attachmentids',
			'newsletters_add_active',
			'newsletters_add_archive',
			'newsletters_set_format_multipart',
			'newsletters_set_format_html',
			'newsletters_set_format_text',
			'newsletters_set_new_defaults',

			'subscriber_data_rename',
			'subscriber_data_drop_listid',
			'subscriber_data_change_subscriberid',
			'subscriber_data_change_fieldid',
			'subscriber_data_change_data',
			'subscriber_data_fix_data',
			'subscriber_data_field_subscriber_idx',

			'stats_sequence_create',
			'stats_sequence_insert',
			'stats_newsletter_lists_create',
			'stats_newsletters_create',
			'stats_users_create',
			'stats_emailopens_create',
			'user_stats_emailsperhour_create',
			'stats_linkclicks_create',
			'stats_links_create',
			'stats_emailforwards_create',
			'stats_autoresponders_create',

			'links_new_create',
			'links_new_populate',

			'stats_newsletters_convert',
			'stats_autoresponders_convert',

			'stats_emailopens_update_fromlink',
			'stats_emailopens_update_opentype',

			'stats_newsletters_update_htmlopens',
			'stats_autoresponders_update_htmlopens',

			'links_old_rename',
			'links_new_rename',

			'drop_unused_table_sends',
			'drop_unused_table_sends_perhour',
			'drop_unused_table_sends_permonth',
			'drop_unused_table_server_sends',
			'drop_unused_table_allow_functions',
			'drop_unused_table_email_opens',
			'drop_unused_table_form_responses',
			'drop_unused_table_link_clicks',
			'drop_unused_table_send_recipients',
			'drop_unused_table_autoresponder_recipients',
			'drop_unused_table_imports',
			'drop_unused_table_import_mappings',
			'drop_unused_table_export_users',
			'drop_unused_table_exports',
			'drop_unused_table_attachments',
			'drop_unused_table_allow_lists',

			'newsletters_fix_content',
			'autoresponders_fix_content',
			'templates_fix_content',
			'form_generate_modifydetails',
			'settings_config_create',

			'timezone_fix_banned_emails',
			'timezone_fix_customfields',
			'timezone_fix_lists',
			'timezone_fix_users',
			'timezone_fix_templates',
			'timezone_fix_forms',
			'timezone_fix_autoresponders',
			'timezone_fix_newsletters',
			'timezone_fix_newsletter_stats',
			'timezone_fix_user_stats',
			'timezone_fix_open_stats',
			'timezone_fix_user_stats_perhour',
			'timezone_fix_link_stats',

			'optimize_table_banned_emails',
			'optimize_table_customfield_lists',
			'optimize_table_customfields',
			'optimize_table_jobs',
			'optimize_table_jobs_lists',
			'optimize_table_queues',
			'optimize_table_queues_sequence',
			'optimize_table_lists',
			'optimize_table_users',
			'optimize_table_user_access',
			'optimize_table_user_permissions',
			'optimize_table_templates',
			'optimize_table_settings',
			'optimize_table_form_lists',
			'optimize_table_form_customfields',
			'optimize_table_form_pages',
			'optimize_table_forms',
			'optimize_table_list_subscriber_bounces',
			'optimize_table_list_subscribers_unsubscribe',
			'optimize_table_list_subscribers',
			'optimize_table_autoresponders',
			'optimize_table_newsletters',
			'optimize_table_subscribers_data',
			'optimize_table_stats_sequence',
			'optimize_table_stats_newsletter_lists',
			'optimize_table_stats_newsletters',
			'optimize_table_stats_users',
			'optimize_table_stats_emailopens',
			'optimize_table_user_stats_emailsperhour',
			'optimize_table_stats_linkclicks',
			'optimize_table_stats_links',
			'optimize_table_stats_emailforwards',
			'optimize_table_stats_autoresponders',
			'optimize_table_links',
		),

		'20070701' => array(
			'list_subscribers_add_domain',
			'list_subscribers_update_domain',
			'list_add_company_name',
			'list_add_company_address',
			'list_add_company_phone',
			'settings_cron_schedule_create',
			'settings_cron_schedule_populate',
			'autoresponders_change_htmlbody',
			'autoresponders_change_textbody',
			'form_change_formhtml',
			'form_pages_change_emailhtml',
			'form_pages_change_emailtext',
			'form_pages_change_html',
			'newsletters_change_htmlbody',
			'newsletters_change_textbody',
			'templates_change_htmlbody',
			'templates_change_textbody',
			'queues_queuetype_recipient_idx',
			'subscriber_data_field_subscriber_idx',
			'stats_emailopens_add_fromlink',
			'stats_emailopens_update_fromlink',
			'stats_emailopens_add_opentype',
			'stats_emailopens_update_opentype',
			'stats_newsletters_add_textopens',
			'stats_newsletters_add_textopens_unique',
			'stats_newsletters_add_htmlopens',
			'stats_newsletters_add_htmlopens_unique',
			'stats_newsletters_update_htmlopens',
			'stats_autoresponders_add_textopens',
			'stats_autoresponders_add_textopens_unique',
			'stats_autoresponders_add_htmlopens',
			'stats_autoresponders_add_htmlopens_unique',
			'stats_autoresponders_update_htmlopens',
			'users_change_textfooter',
			'users_change_htmlfooter',
			'users_add_usewysiwyg',
			'users_update_usewysiwyg',
			'autoresponders_add_autorespondersize',
			'settings_config_create',
			'move_settings_to_database',
			'update_db_version',
		),

		'20071205' => array(
			'open_statid_subscriberid_index',
			'stats_linkclicks_subscriberid_index',
			'update_db_version',
		),

		'20080201' => array(
			'users_add_xmlapi',
			'users_add_xmltoken',
			'stats_newsletters_add_jobid',
			'stats_newsletters_update_jobid',
			'create_queues_unsent',
			'create_subscribers_data_data_index',
			'lists_add_processbounce',
			'lists_add_agreedelete',
			'lists_update_settings',
			'add_module_table',
			'create_log_system_system',
			'create_log_system_administrator',
			'stats_newsletters_testmode_add',
			'stats_newsletters_testmode_update',
			'stats_autoresponders_recipients_create',
			'jobs_add_resendcount',
			'update_db_version',
		),

		'20080312' => array(
			'autoresponders_change_active',
			'newsletters_change_active',
			'templates_change_active',
			'templates_change_isglobal',
			'update_db_version',
		),

		'20080325' => array(
			'lists_add_visiblefields',
			'lists_update_visiblefields',
			'users_add_gettingstarted',
			'users_add_segmentadmintype',
			'user_permission_update_subscriber_view',
			'create_segments',
			'queues_unsent_fk_check',
			'update_db_version',
		),

		'20080610' => array(
			'modify_segment_operatortype',
			'update_db_version'
		),

		'20080710' => array(
			'add_subscriber_listid_index',
			'remove_list_subscribers_domain_idx',
			'update_db_version'
		),

		'20080801' => array(
			'add_user_unique_token',
			'populate_user_unique_token',
			'create_login_attempt',
			'create_login_banned_ip',
			'update_db_version'
		),

		'20080802' => array(
			'index_stats_linkclicks_statid_clicktime',
			'index_stats_emailopens_statid_opentime',
			'index_links_url',
			'update_db_version'
		),

		'20081215' => array(
			'modify_user_table_type',
			'add_user_eventactivitytype_column',
			'create_list_subscriber_events',
			'create_addons',
			'create_folders',
			'lists_add_agreedeleteall',
			'users_add_googlecalendarusername',
			'users_add_googlecalendarpassword',
			'users_add_language_preference',
			'stats_newsletters_add_sendtype',
			'create_triggeremails',
			'create_triggeremails_data',
			'create_triggeremails_actions',
			'create_triggeremails_actions_data',
			'create_triggeremails_log',
			'create_triggeremails_log_summary',
			'add_user_enableactivitylog_column',
			'create_user_activitylog',
			'create_user_activitylog_userid_viewed_idx',
			'add_event_listeners',
			'add_global_customfields',
			'update_db_version',
		),

		'20090107' => array(
			'fix_3744_fix_absolute_path',
			'update_db_version'
		),

		'20090112' => array(
			'fix_3780_fix_some_event_not_triggered',
			'reregister_listeners',
			'fix_3784_index_user_permissions',
			'update_db_version'
		),

		'20090126' => array(
			'create_list_subscriber_events_subscriberid_index',
			'create_triggeremails_data_idx',
			'create_triggeremails_actions_data_idx',
			'create_triggeremails_log_idx',
			'update_db_version'
		),

		'20090310' => array(
			'create_index_for_queues_unsent_queueid_idx',
			'create_index_for_queues_unsent_recipient_idx',
			'enable_or_reactivate_systemlog_addon',
			'update_db_version'
		),

		'20090323' => array(
			'create_login_attempt_2',
			'create_login_banned_ip_2',
			'update_db_version'
		),

		'20090331' => array(
			'delete_cron_triggeremails_p_settings',
			'update_db_version'
		),

		'20090415' => array(
			'remove_duplicate_user_access',
			'add_user_access_userid_area_id_idx',
			'update_db_version'
		),

		'20090420' => array(
			'add_user_forcedoubleoptin_column',
			'add_user_forcespamcheck_column',
			'install_addon_dbcheck',
			'add_column_users_credit_warnings',
			'add_settings_credit_warnings_table',
			'add_user_credit_table',
			'add_user_credit_summary_table',
			'check_cron_schedule_time',
			'update_db_version'
		),

		'20090609' => array(
			'add_autoresponder_pause',
			'fix_user_stats_emails_perhour_oldtimestamp',
			'update_db_version'
		),

		'20090715' => array(
			'user_add_trialuser',
			'user_add_notification_email',
			'create_whitelabel_settings',
			'update_db_version'
		),

		'20090916' => array(
			'create_usergroups',
			'user_add_groupid_column',
            'user_update_groupid_column_constraint',
			'populate_groups_with_users',
            'cleanup_legacy_structure',
			'install_addon_dynamiccontenttags',
			'install_addon_surveys',
			'update_db_version'
		),

        '20100731' => array(
			'update_links_url',
            'update_db_version'
		),

		'20101204' => array(
			'add_confirmed_index',
			'add_list_permissions',
			'update_db_version'
		),
		'20111011' => array(
			'update_db_version'
		),
		'20120608' => array(
			'update_db_version'
		)
	);

	/**
	* Constructor
	* Sets up the database object, loads the user if the ID passed in is not 0.
	*
	* @param Int $userid The userid of the user to load. If it is 0 then you get a base class only. Passing in a userid > 0 will load that user.
	*
	* @see GetDb
	* @see Load
	*
	* @return True|Load If no userid is present, this always returns true. Otherwise it returns the status from Load
	*/
	function Upgrade_API()
	{
		if (is_object($this->Db) && is_resource($this->Db->connection)) {
			return true;
		}

		$this->Db = IEM::getDatabase();
		$this->Db->TablePrefix = SENDSTUDIO_TABLEPREFIX;
	}

	/**
	 * RunUpgrade
	 * @param String $upgrade Upgrade to run
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade($upgrade=false)
	{
		$class = new $upgrade;

		$upgrade_result = $class->RunUpgrade();

		$status =  IEM::sessionGet('DatabaseUpgradeStatusList');
		if (isset($status[$upgrade])) {
			if ($upgrade_result !== false) {
				return $upgrade_result;
			}
		} else {
			if ($upgrade_result) {
				return true;
			}
		}

		if (!isset($class->errormessage)) {
			$class_err = $class->Db->GetError();

			$this->error = 'Upgrade for \'' . $upgrade . '\' failed. Reason: \'' . $class_err[0] . '\'';
		} else {
			$this->error = $class->errormessage;
		}
		return false;
	}

	/**
	 * GetNextUpgrade
	 * @return String Returns TRUE if successful, FALSE otherwise
	 */
	function GetNextUpgrade()
	{
		$upgrades_done = IEM::sessionGet('DatabaseUpgradesCompleted');

		$upgrades_todo = IEM::sessionGet('UpgradesToRun');

		if (empty($upgrades_todo)) {
			return null;
		}

		$versions = array_keys($upgrades_todo);

		if (empty($versions)) {
			IEM::sessionSet('UpgradesToRun', array());
			return null;
		}

		$version = $versions[0];

		$upgrade = array_shift($upgrades_todo[$version]);

		// if we've grabbed the last upgrade for that version, array_shift returns null.
		if ($upgrade === null) {
			unset($upgrades_todo[$version]);

			// if we've grabbed the last upgrade for that old version, see if there's another version we need to look at.
			$versions = array_keys($upgrades_todo);

			// if there are no more versions, then we're finished.
			if (empty($versions)) {
				IEM::sessionSet('UpgradesToRun', array());
				return null;
			}

			$version = $versions[0];

			$upgrade = array_shift($upgrades_todo[$version]);
		}

		$file = IEM_PATH . '/upgrades/' . $version . '/' . $upgrade . '.php';

		if (!is_readable($file)) {
			$this->error = 'Invalid Version - File Doesn\'t Exist';
			return false;
		}

		require_once($file);

		if (isset($upgrade_description)) {
			$this->FriendlyDescription = $upgrade_description;
		}

		return $upgrade;
	}

	/**
	 * GetUpgradesToRun
	 * @param String $current_version Current version
	 * @param String $latest Latest version
	 * @return Array Returns an array of upgrade to be performed
	 */
	function GetUpgradesToRun($current_version=0, $latest=0)
	{
		if ($current_version == $latest) {
			return array('upgrades' => array(), 'number_to_run' => 0);
		}

		$dirs = list_directories(IEM_PATH . '/upgrades');

		$upgrades_to_run = array();
		$number_to_run = 0;

		/**
		* If the current version is '2004'
		* we need to include the 'nx' upgrades before anything else.
		*/
		if ($current_version == '2004') {
			$upgrades = $this->upgrades_to_run['nx'];

			$upgrades_to_run['nx'] = $upgrades;
			$number_to_run += sizeof($upgrades);
		}

		foreach ($dirs as $p => $dir) {
			$dirname = str_replace(IEM_PATH . '/upgrades/', '', $dir);

			/**
			* If the directory isn't a numeric folder name, check the current version.
			* If the current version is '2004', then we need to include the 'nx' folder.
			* If the current version is NOT nx, then skip non-numeric folders.
			*/
			if (!is_numeric($dirname)) {
				continue;
			}

			/**
			* If we are mid-way through database changes, ie:
			* - our version is '2'
			* and there are upgrades for 1,2,3
			* we want to skip any that are before our current version.
			*/
			if ($dirname <= $current_version) {
				continue;
			}

			$upgrades = $this->upgrades_to_run[$dirname];

			$upgrades_to_run[$dirname] = $upgrades;
			$number_to_run += sizeof($upgrades);
		}

		return array('upgrades' => $upgrades_to_run, 'number_to_run' => $number_to_run);
	}

	/**
	* TableExists
	* Check whether a table exists or not.
	* This is useful for partial upgrades so we can quickly see whether we need to run a particular query or not.
	*
	* @param String $tablename The table to check for
	*
	* @return Boolean Returns false if the table does not exist otherwise returns true.
	*/
	function TableExists($tablename=false)
	{
		if (!$tablename) {
			return false;
		}

		$db = IEM::getDatabase();

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "SHOW TABLES LIKE '" . SENDSTUDIO_TABLEPREFIX . $tablename . "'";
		} else {
			$query = "SELECT table_name FROM information_schema.tables WHERE table_name='" . SENDSTUDIO_TABLEPREFIX . $tablename . "'";
		}
		$result = $db->Query($query);
		$row = $db->Fetch($result);

		if (empty($row)) {
			return false;
		}
		return true;
	}

	/**
	* ColumnExists
	* Check whether a column exists or not.
	* This is useful for partial upgrades so we can quickly see whether we need to run a particular query or not.
	*
	* @param String $tablename The table to check for the column in
	* @param String $column The name of the column to check for
	*
	* @return Boolean Returns false if the column does not exist otherwise returns true.
	*/
	function ColumnExists($tablename=false, $column=false)
	{
		if (!$tablename || !$column) {
			return false;
		}

		$db = IEM::getDatabase();

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "SHOW COLUMNS FROM " . SENDSTUDIO_TABLEPREFIX . $tablename . " LIKE '".$column."'";

			$result = $db->Query($query);
			$row = $db->Fetch($result);

			if ($row['Field'] == $column) {
				return true;
			} else {
				return false;
			}
		}

		$query = "SELECT column_name FROM information_schema.columns WHERE table_name='" . SENDSTUDIO_TABLEPREFIX . $tablename . "' AND column_name='" . $column . "'";
		$result = $db->Query($query);
		$row = $db->Fetch($result);

		if (empty($row)) {
			return false;
		}

		if ($row['column_name'] == $column) {
			return true;
		}
		return false;
	}

	/**
	* GetIndexInfo
	*
	* Get information about the indexes on $table and return an array with
	* this information
	*
	* @param String $table The name of the table to get information about the indexes for
	*
	* @return Array The array containing the index information in the format
		$indexes = array (
			'indexname' => array (
				'unique' => true/false,
				'type' => btree/fulltext,
				'columns' = array (
					'field1',
					'field2',
					'field3',
				),
			),
			'index2name' => array (
				...
			),
		)
	*/
	function GetIndexInfo($table)
	{
		$indexes = array();

		$query = 'SHOW INDEX FROM '. SENDSTUDIO_TABLEPREFIX . $table;
		$result = $this->Db->Query($query);
		if ($result === false) {
			return false;
		}

		while ($row = $this->Db->Fetch($result)) {
			if (!isset($indexes[$row['Key_name']])) {
				if (!isset($row['Index_type'])) {
					$row['Index_type'] = 'UNDEFINED';
				}

				$indexes[$row['Key_name']] = array (
					'unique' => ($row['Non_unique'] == 0),
					'type' => $row['Index_type'],
				);

				if (!isset($indexes[$row['Key_name']]['columns'])
				|| !is_array($indexes[$row['Key_name']]['columns'])) {
					$indexes[$row['Key_name']]['columns'] = array();
				}
			}

			$indexes[$row['Key_name']]['columns'][] = $row['Column_name'];
		}
		return $indexes;
	}

	/**
	* ConstraintExists
	* Check whether a particular constraint exists for a table.
	* This could be a foreign key name for example.
	*
	* @param String $table The table to look for the constraint in. This is only needed for mysql tables.
	* @param String $constraint_name The name of the constraint to look for.
	*
	* @return Boolean Returns false if the constraint doesn't exist (or incorrect parameters are passed in). Returns true if it does exist.
	*/
	function ConstraintExists($table, $constraint_name)
	{
		if (empty($table)) {
			return false;
		}

		if (empty($constraint_name)) {
			return false;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$db = IEM::getDatabase();
			$query = "SELECT conname from pg_constraint where conname='" . SENDSTUDIO_TABLEPREFIX . $constraint_name . "'";
			$result = $db->Query($query);
			if (!$result) {
				return false;
			}
			$row = $db->Fetch($result);
			if (empty($row)) {
				return false;
			}
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$indexes = $this->GetIndexInfo($table);
			if (empty($indexes)) {
				return false;
			}

			$key_names = array_keys($indexes);
			if (in_array(SENDSTUDIO_TABLEPREFIX . $constraint_name, $key_names)) {
				return true;
			}
			return false;
		}
	}

	/**
	* AddForeignKey
	* Adds a foreign key constraint (this query is passed straight in).
	* If you are running a mysql database, then each of the tables in the fk constraint (the source & target)
	* both need to be innodb tables.
	* So we need to pass in the constraint and the tables to check.
	*
	* If any of the tables are not innodb tables, then this doesn't actually run the constraint - there's no point.
	* If all of the tables are innodb tables, the constraint is run and the result is returned.
	*
	* @param String $constraint The foreign key constraint to run (if the database type is 'pgsql' or if all of the tables are innodb tables).
	* @param Array $tables The tables to check if they are innodb tables and thus support foreign keys in the first place.
	*
	* @return Boolean Returns true if adding the constraint worked. Also returns true if any of the database tables are not innodb tables.
	*/
	function AddForeignKey($constraint, $tables=array())
	{
		if (empty($tables)) {
			return false;
		}
		if (empty($constraint)) {
			return false;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$db = IEM::getDatabase();
			$result = $db->Query($constraint);
			if ($result) {
				return true;
			}
			return false;
		}

		$db = IEM::getDatabase();
		foreach ($tables as $table) {
			$query = "SHOW TABLE STATUS LIKE '" . SENDSTUDIO_TABLEPREFIX . $table . "'";
			$result = $db->Query($query);
			$row = $db->Fetch($result);

			// if the table isn't an innodb table, it doesn't support foreign keys
			if (strtolower($row['Engine']) != 'innodb') {
				return true;
			}
		}

		$result = $db->Query($constraint);
		if ($result) {
			return true;
		}
		return false;
	}


	/**
	* IndexExists
	*
	* Check if an index exists on some table columns
	*
	* @param String $table The name of the table
	* @param Array $columns The array of column names the index is on. Order counts.
	* @param Boolean $unique Is the index a unique index ?
	* @param String $type The type of index to check for (BTREE or FULLTEXT)
	*
	* @return Boolean Does the index exist as expected or not ?
	*/
	function IndexExists($table, $columns, $unique=false, $type='BTREE')
	{
		$keymatches = array();
		$indexname = '';

		if (empty($table)) {
			return false;
		}

		if (empty($columns)) {
			return false;
		}

		if (!is_array($columns)) {
			$columns = array($columns);
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$db = IEM::getDatabase();

			$indexlist = implode(', ', $columns);
			$query = "SELECT indexdef FROM pg_indexes where tablename='" . SENDSTUDIO_TABLEPREFIX . $table . "' AND indexdef LIKE '%" . $indexlist . "%'";
			$result = $db->Query($query);
			if (!$result) {
				return false;
			}
			$row = $db->Fetch($result);
			if (empty($row)) {
				return false;
			}
			return true;
		}

		if (!in_array($type, array('BTREE', 'FULLTEXT', 'UNDEFINED'))) {
			return false;
		}

		$indexes = $this->GetIndexInfo($table);

		if (empty($indexes)) {
			return false;
		}

		foreach ($indexes as $name => $index) {
			// Since MySQL can use the first part of the array, lets check
			// to see if the required index is already part of another index
			$slice = array_slice($index['columns'], 0, count($columns));

			// Check if the index is one we can use
			if (($index['type'] == $type || $index['type'] == 'UNDEFINED')
			&& $index['unique'] == $unique
			&& $slice == $columns) {
				return true;
			}
		}
		return false;
	}
}
