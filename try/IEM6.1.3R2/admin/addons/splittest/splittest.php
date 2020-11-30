<?php
/**
 * This file contains the basic functionality for the 'splittest' addon, including
 * - installing the addon
 * - uninstalling the addon
 * - creating a splittest
 * - editing a splittest
 * - deleting a splittest
 *
 * The main class is broken up as much as possible into sections.
 * There are two addition files:
 * - splittest_send.php
 * - splittest_stats.php
 *
 * These files are only included when you are viewing the relevant area(s)
 * to help keep memory usage & processing time to a reasonable limit.
 *
 * It also makes it much easier to work out where things are.
 *
 * @package Interspire_Addons
 * @subpackage Addons_splittest
 */

/**
 * Make sure the base Interspire_Addons class is defined.
 */
if (!class_exists('Interspire_Addons', false)) {
	require_once(dirname(dirname(__FILE__)) . '/interspire_addons.php');
}

require_once (dirname(__FILE__) . '/language/language.php');

/**
 * This class handles most things for split testing
 * including extra user permissions, menu items (under 'email campaigns' and also in 'stats')
 * and of course processing everything.
 *
 * If you go into a particular area (eg 'sending' a split test campaign), then extra files are included.
 * This helps keep memory usage and processing time to a reasonable limit.
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 * @uses Addons_splittest_Send
 */
class Addons_splittest extends Interspire_Addons
{

	/**
	 * percentage_minimum
	 * For a percentage split test, this is the minimum percentage you can send to
	 * to work out the "best" performing newsletter/campaign.
	 */
	const percentage_minimum = 0;

	/**
	 * percentage_default
	 * For a percentage split test, this is the default percentage to set when the 'create form' is displayed.
	 */
	const percentage_default = 10;

	/**
	 * percentage_maximum
	 * For a percentage split test, this is the maximum percentage you can send to
	 * to work out the "best" performing newsletter/campaign.
	 */
	const percentage_maximum = 100;

	/**
	 * percentage_hoursafter_default
	 * For a percentage split test, this is the default 'hours after' setting
	 * to use when the 'create form' is displayed.
	 */
	const percentage_hoursafter_default = 8;

	/**
	 * percentage_hoursafter_minimum
	 * For a percentage split test, This is the minimum "hours after" setting you can use
	 * before sending the "best" performing newsletter/campaigns to the rest of the mailing list(s).
	 */
	const percentage_hoursafter_minimum = 2;

	/**
	 * percentage_hoursafter_maximum
	 * For a percentage split test, This is the maximum "hours after" setting you can use
	 * before sending the "best" performing newsletter/campaigns to the rest of the mailing list(s).
	 *
	 * This gets turned into "days"
	 * 720 hours = 30 days.
	 */
	const percentage_hoursafter_maximum = 720;

	/**
	 * weight_openrate_default
	 * This is the default "open rate" weighting to use.
	 */
	const weight_openrate_default = 100;

	/**
	 * weight_linkclick_default
	 * This is the default "link click" weighting to use.
	 */
	const weight_linkclick_default = 0;

	/*
	 * "t" means "t"imeout which is used for percentage split test sends.
	 * It is the time after a job has started and sent to the first "X" percent of a list/segment
	 * but is waiting for the "hours after" time to expire to send the rest.
	 */
	static $jobstatuscodes = array('t');

	/**
	 * Install
	 * This addon has to create some database tables to work.
	 * It includes the schema files (based on the database type) and creates the bits it needs.
	 * Once that's done, it calls the parent Install method to do its work.
	 *
	 * @uses enabled
	 * @uses configured
	 * @uses Interspire_Addons::Install
	 * @uses Interspire_Addons_Exception
	 *
	 * @throws Throws an Interspire_Addons_Exception if something in the install process fails.
	 * @return True Returns true if everything works ok.
	 */
	public function Install()
	{
		$tables = $sequences = array();

		$this->db->StartTransaction();

		require dirname(__FILE__) . '/schema.' . SENDSTUDIO_DATABASE_TYPE . '.php';
		foreach ($queries as $query) {
			$qry = str_replace('%%TABLEPREFIX%%', $this->db->TablePrefix, $query);
			$result = $this->db->Query($qry);
			if (!$result) {
				$this->db->RollbackTransaction();
				throw new Interspire_Addons_Exception("There was a problem running query " . $qry . ": " . $this->db->GetErrorMsg(), Interspire_Addons_Exception::DatabaseError);
			}
		}

		$this->enabled = true;
		$this->configured = true;
        try {
			$status = parent::Install();
		} catch (Interspire_Addons_Exception $e) {
			$this->db->RollbackTransaction();
			throw new Exception("Unable to install addon {$this->GetId()} " . $e->getMessage());
		}

		$this->db->CommitTransaction();

		return true;
	}

	/**
	 * UnInstall
	 * Drop tables the addon created.
	 * It includes the schema files (based on the database type) and drops the bits it created.
	 * Once that's done, it calls the parent UnInstall method to do its work.
	 *
	 * @uses Interspire_Addons::UnInstall
	 * @uses Interspire_Addons_Exception
	 *
	 * @return Returns true if the addon was uninstalled successfully.
	 * @throws Throws an Interspire_Addons_Exception::DatabaseError if one of the tables it created couldn't be removed. If the parent::UnInstall method throws an exception, this will
	 * just re-throw that error.
	 */
	public function UnInstall()
	{
		$tables = $sequences = array();

		$this->db->StartTransaction();

		try {
			$this->Disable();
		} catch (Interspire_Addons_Exception $e) {
			$this->db->RollbackTransaction();
			throw new Interspire_Addons_Exception($e->getMessage(), $e->getCode());
		}

		require dirname(__FILE__) . '/schema.' . SENDSTUDIO_DATABASE_TYPE . '.php';
		foreach ($tables as $tablename) {
			$query = 'DROP TABLE [|PREFIX|]' . $tablename . ' CASCADE';
			$result = $this->db->Query($query);
			if (!$result) {
				$this->db->RollbackTransaction();
				throw new Interspire_Addons_Exception("There was a problem running query " . $query . ": " . $this->db->GetErrorMsg(), Interspire_Addons_Exception::DatabaseError);
			}
		}

		foreach ($sequences as $sequencename) {
			$query = 'DROP SEQUENCE [|PREFIX|]' . $sequencename;
			$result = $this->db->Query($query);
			if (!$result) {
				$this->db->RollbackTransaction();
				throw new Interspire_Addons_Exception("There was a problem running query " . $query . ": " . $this->db->GetErrorMsg(), Interspire_Addons_Exception::DatabaseError);
			}
		}

        try {
			$status = parent::UnInstall();
		} catch (Interspire_Addons_Exception $e) {
			$this->db->RollbackTransaction();
			throw new Interspire_Addons_Exception($e->getMessage(), $e->getCode());
		}

		$this->db->CommitTransaction();

		return true;
	}

	/**
	 * Enable
	 * This enables the split test addon to work, including displaying in the menu(s), adding it's own permissions etc.
	 * It adds an entry to the settings_cron_schedule table
	 * so if necessary, the addon can be run via cron instead of the web interface.
	 *
 	 * @uses Interspire_Addons::Enable
	 * @uses Interspire_Addons_Exception
	 *
	 * @return Returns true if the addon was enabled successfully.
	 * @throws If the parent::Enable method throws an exception, this will just re-throw that error.
	 */
	public function Enable()
	{
		$this->db->Query("INSERT INTO [|PREFIX|]settings_cron_schedule(jobtype, lastrun) VALUES ('" . $this->db->Quote($this->addon_id) . "', 0)");
		try {
			$status = parent::Enable();
		} catch (Interspire_Addons_Exception $e) {
			throw new Interspire_Addons_Exception($e->getMessage(), $e->getCode());
		}
		return true;
	}

	/**
	 * Disable
	 * This disables the split test addon from the control panel.
	 * Before it does it, it checks for any non-complete split test sending jobs
	 * If any are found, the addon cannot be disabled.
	 *
	 * If that's ok, it deletes itself from the settings_cron_schedule table and any other settings it created (config_settings table).
	 *
  	 * @uses Interspire_Addons::Disable
	 * @uses Interspire_Addons_Exception
	 *
	 * @return Returns true if the addon was disabled successfully and there are no pending/in progress split test sends.
	 * @throws If the parent::Disable method throws an exception, this will just re-throw that error.
	 */
	public function Disable()
	{
		$job_check = "SELECT COUNT(jobid) AS jobcount FROM [|PREFIX|]jobs WHERE jobtype='splittest' AND jobstatus NOT IN ('c')";
		$count = $this->db->FetchOne($job_check);
		if ($count > 0) {
			throw new Interspire_Addons_Exception(GetLang('Addon_splittest_DisableFailed_SendsInProgress'));
		}

		$this->db->StartTransaction();
		$result = $this->db->Query("DELETE FROM [|PREFIX|]settings_cron_schedule WHERE jobtype='" . $this->db->Quote($this->addon_id) . "'");
		if (!$result) {
			$this->db->RollbackTransaction();
		}
		$result = $this->db->Query("DELETE FROM [|PREFIX|]config_settings WHERE area='" . $this->db->Quote(strtoupper('CRON_' . $this->addon_id)) . "'");
		if (!$result) {
			$this->db->RollbackTransaction();
		}
		$this->db->CommitTransaction();
		try {
			$status = parent::Disable();
		} catch (Interspire_Addons_Exception $e) {
			throw new Interspire_Addons_Exception($e->getMessage(), $e->getCode());
		}
		return true;
	}

	/**
	 * GetEventListeners
	 * The addon uses quite a few events to place itself in the app and allow it to work.
	 *
	 * IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS
	 * Put itself in the menu
	 *
	 * IEM_USERAPI_GETPERMISSIONTYPES
	 * Add new permissions to control who is allowed to use the addon
	 *
	 * IEM_SETTINGSAPI_LOADSETTINGS
	 * Adds new options to the settings for cron
	 *
	 * IEM_CRON_RUNADDONS
	 * Adds itself to the list of addons that can have cron jobs
	 *
	 * IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES
	 * Cleans up any incomplete "sends" that have been started.
	 * For example, get to step 2 or 3 in the "send" process, then either
	 * your browser dies, or you navigate away from the process.
	 *
	 * IEM_NEWSLETTERSAPI_DELETE
	 * When a newsletter/email campaign is about to be deleted,
	 * check it's not used by split test campaigns.
	 * If it is, the newsletter/email campaign can't be deleted.
	 *
	 * IEM_NEWSLETTERS_MANAGENEWSLETTERS
	 * Adds any messages created when newsletters/email campaigns
	 * are attempted to be deleted.
	 *
	 * IEM_JOBSAPI_GETJOBLIST
	 * Adds itself to the "job list" which is used to show the scheduled sending list
	 *
	 * IEM_JOBSAPI_GETJOBSTATUS
	 * Adds it's own unique job status types
	 * (which can then show different status messages)
	 *
	 * IEM_SCHEDULE_PAUSEJOB
	 * Handle it's own "pause job" process.
	 * In this case, it needs to update it's own database tables with the new status codes.
	 *
	 * IEM_SCHEDULE_EDITJOB
	 * Print it's own "edit schedule" form to fill out
	 *
	 * IEM_SCHEDULE_RESUMEJOB
	 * Handle it's own "resume job" process.
	 * In this case, it needs to update it's own database tables with the new status codes.
	 *
	 * IEM_SCHEDULE_DELETEJOBS
	 * Handle it's own "delete jobs" process
	 * In this case, the way user credits are re-allocated is changed.
	 *
	 * @return Array Returns an array containing the listeners, the files to include, the function/methods to run etc.
	 */
	function GetEventListeners()
	{
		$my_file = '{%IEM_ADDONS_PATH%}/splittest/splittest.php';
		$listeners = array();

		$listeners[] =
			array (
				'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS',
				'trigger_details' => array (
					'Addons_splittest',
					'SetMenuItems',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_USERAPI_GETPERMISSIONTYPES',
				'trigger_details' => array (
					'Interspire_Addons',
					'GetAddonPermissions',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_SETTINGSAPI_LOADSETTINGS',
				'trigger_details' => array (
					'Addons_splittest',
					'SetSettings',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_CRON_RUNADDONS',
				'trigger_details' => 'Splittest_Cron_GetJobs',
				'trigger_file' => dirname($my_file) . '/cron.php'
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES',
				'trigger_details' => array (
					'Addons_splittest',
					'CleanupPartialSends',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_NEWSLETTERSAPI_DELETE',
				'trigger_details' => array (
					'Addons_splittest',
					'DeleteNewsletters',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_NEWSLETTERS_MANAGENEWSLETTERS',
				'trigger_details' => array (
					'Addons_splittest',
					'ManageNewsletters',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_JOBSAPI_GETJOBLIST',
				'trigger_details' => array (
					'Addons_splittest',
					'GenerateJobListQuery',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_JOBSAPI_GETJOBSTATUS',
				'trigger_details' => array (
					'Addons_splittest',
					'GetJobStatus',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_SCHEDULE_PAUSEJOB',
				'trigger_details' => array (
					'Addons_splittest',
					'PauseSchedule',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_SCHEDULE_EDITJOB',
				'trigger_details' => array (
					'Addons_splittest',
					'EditSchedule',
				),
				'trigger_file' => $my_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_SCHEDULE_RESUMEJOB',
				'trigger_details' => array (
					'Addons_splittest',
					'ResumeSchedule',
				),
				'trigger_file' => $my_file
			);


		$listeners[] =
			array (
				'eventname' => 'IEM_SCHEDULE_DELETEJOBS',
				'trigger_details' => array (
					'Addons_splittest',
					'DeleteSchedules',
				),
				'trigger_file' => $my_file
			);

		return $listeners;

	}

	/**
	 * SetSettings
	 * Adds new options to the "cron settings" page and settings database table.
	 * Sets the "last run time" for the job to -1 which means "hasn't run".
	 *
	 * Adds a new settings entry called "CRON_SPLITTEST" to the settings table.
	 * Also adds the following times to the "run job every" dropdown box:
	 * - 1 minute
	 * - 2, 5, 10, 15, 20, 30 minutes
	 *
	 * @param EventData_IEM_SETTINGSAPI_LOADSETTINGS $data The current settings data which is passed in by reference (is an object).
	 *
	 * @uses EventData_IEM_SETTINGSAPI_LOADSETTINGS
	 */
	static function SetSettings(EventData_IEM_SETTINGSAPI_LOADSETTINGS $data)
	{
		$data->data->Schedule['splittest'] = array (
			'lastrun' => -1,
		);

		$data->data->splittest_options = array (
			'0' => 'disabled',
			'1' => '1_minute',
			'2' => '2_minutes',
			'5' => '5_minutes',
			'10' => '10_minutes',
			'15' => '15_minutes',
			'20' => '20_minutes',
			'30' => '30_minutes'
		);

		$data->data->Areas[] = 'CRON_SPLITTEST';

	}

	/**
	 * SetMenuItems
	 * Adds itself to the navigation menu(s).
	 *
	 * If the user has access to "send email campaigns" in the email campaigns menu,
	 * it tries to put "View Split Tests" under that.
	 * If they don't have access to that, then "View Split Tests" goes at the bottom of the email campaigns menu.
	 *
	 * If the user has access to "email campaign stats" in the stats menu,
	 * it tries to put "Split Test Stats" under that.
	 * If they don't, then it goes at the bottom of the stats menu.
	 *
	 * @param EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS $data The current menu.
	 *
	 * @return Void The current menu is passed in by reference, no need to return anything.
	 *
	 * @uses EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS
	 */
	static function SetMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS $data)
	{
		$self = new self;

		$news_split_test_menu = array (
			'text' => GetLang('Addon_splittest_Menu_ViewSplitTests'),
			'link' => $self->admin_url,
			'image' => '../addons/splittest/images/m_splittests.gif',
			'show' => array (
				'CheckAccess' => 'HasAccess',
				'Permissions' => array('splittest'),
			),
			'description' => GetLang('Addon_splittest_Menu_ViewSplitTests_Description'),
		);

		$stats_split_test_menu = array (
			'text' => GetLang('Addon_splittest_Menu_ViewStats'),
			'link' => $self->admin_url . '&Action=Stats',
			'image' => '../addons/splittest/images/m_splittests.gif',
			'show' => array (
				'CheckAccess' => 'HasAccess',
				'Permissions' => array ('splittest', 'stats')
			),
			'description' => GetLang('Addon_splittest_Menu_ViewStats_Description'),
		);


		$menuItems = $data->data;

		$slice_pos = false;

		foreach ($menuItems['newsletter_button'] as $pos => $newsletter_menu_item) {
			if ($newsletter_menu_item['link'] == 'index.php?Page=ImageManager') {
				$slice_pos = $pos;
				break;
			}
		}

		/**
		 * If the user has access to 'send' campaigns, we want split testing under that.
		 */
		if ($slice_pos !== false) {
			$newsmenu_slice = array_slice($menuItems['newsletter_button'], $slice_pos, 1);
			$newsmenu_slice[] = $news_split_test_menu;
			array_splice($menuItems['newsletter_button'], $slice_pos, 1, $newsmenu_slice);
		} else {
			/**
			 * They don't have access to send campaigns? Just put it at the end of the campaign menu.
			 */
			$menuItems['newsletter_button'][] = $news_split_test_menu;
		}


		$slice_pos = false;
		foreach ($menuItems['statistics_button'] as $pos => $stats_menu_item) {
			if ($stats_menu_item['link'] == 'index.php?Page=Stats') {
				$slice_pos = $pos;
				break;
			}
		}

		/**
		 * If the user has access to 'campaign stats', we want split testing under that.
		 */
		if ($slice_pos !== false) {
			$statsmenu_slice = array_slice($menuItems['statistics_button'], $slice_pos, 1);
			$statsmenu_slice[] = $stats_split_test_menu;
			array_splice($menuItems['statistics_button'], $slice_pos, 1, $statsmenu_slice);
		} else {
			/**
			 * They don't have access to email campaign stats? Just put it at the end of the stats menu.
			 */
			$menuItems['statistics_button'][] = $stats_split_test_menu;
		}

		$data->data = $menuItems;
	}

	/**
	 * RegisterAddonPermissions
	 * Registers permissions for this addon to create.
	 * This allows an admin user to finely control which parts of split tests a user can access.
	 *
	 * Creates the following permissions:
	 * - create
	 * - edit
	 * - delete
	 * - send
	 * - stats
	 *
	 * @uses RegisterAddonPermission
	 */
	static function RegisterAddonPermissions()
	{
		$description = self::LoadDescription('splittest');
		$perms = array (
			'splittest' => array (
				'addon_description' => GetLang('Addon_Settings_Header'),
				'create' => array('name' => GetLang('Addon_splittest_Permission_Create')),
				'edit' => array('name' => GetLang('Addon_splittest_Permission_Edit')),
				'delete' => array('name' => GetLang('Addon_splittest_Permission_Delete')),
				'send' => array('name' => GetLang('Addon_splittest_Permission_Send')),
				'stats' => array('name' => GetLang('Addon_splittest_Permission_Stats')),
			),
		);
		self::RegisterAddonPermission($perms);
	}

	/**
	 * GetApi
	 * An easy way to include the splittest api file which does all of the database queries.
	 * This is marked as protected so the sub-classes (for sending & stats) can use it.
	 *
	 * @param String $api Which api to get. It defaults to the 'splittest' api but can be passed 'splittest_send' to get that api instead.
	 *
	 * @return Object|False Returns false if the api name is invalid. Otherwise returns the appropriate api object ready for use.
	 */
	protected function GetApi($api='SplitTest')
	{
		$path = $this->addon_base_directory . $this->addon_id . '/api/' . strtolower($api) . '.php';
		if (!is_file($path)) {
			return false;
		}

		require_once $path;
		$class = $api . '_API';
		$api = new $class;
		return $api;
	}

	/**
	 * Admin_Action_Default
	 * This prints the 'manage page' which shows a list of split tests that have been created.
	 * If the user has access to create new ones, it also shows a 'create split test' button.
	 *
	 * @uses GetApi
	 * @uses Splittest_API::GetSplitTests
	 *
	 * @return Void Just prints out the page, doesn't return anything.
	 */
	public function Admin_Action_Default()
	{
		$user = GetUser();

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);

		$create_button = '';
		$create_button_extra_msg = '';
		if ($user->HasAccess('splittest', 'create')) {
			$create_button = $this->template_system->ParseTemplate('create_button', true);
			$create_button_extra_msg = GetLang('Addon_splittest_CanCreateMessage');
		}
		$this->template_system->Assign('SplitTest_Create_Button', $create_button, false);

		if ($user->HasAccess('splittest', 'delete')) {
			$this->template_system->Assign('ShowDeleteButton', true);
		}

		$flash_messages = GetFlashMessages();

		$this->template_system->Assign('FlashMessages', $flash_messages, false);

		$api = $this->GetApi();
		$userid = $user->Get('userid');
		if ($user->Admin()) {
			$userid = 0;
		}

		$number_of_tests = $api->GetSplitTests($userid, array(), true);

		if ($number_of_tests == 0) {
			$curr_template_dir = $this->template_system->GetTemplatePath();

			$this->template_system->SetTemplatePath(SENDSTUDIO_TEMPLATE_DIRECTORY);
			$GLOBALS['Success'] = sprintf(GetLang('Addon_splittest_NoneAvailable'), $create_button_extra_msg);

			$msg = $this->template_system->ParseTemplate('successmsg', true);
			$this->template_system->SetTemplatePath($curr_template_dir);

			$this->template_system->Assign('Addon_splittest_Empty', $msg, false);

			$this->template_system->ParseTemplate('manage_empty');
			return;
		}

		$this->template_system->Assign('ApplicationUrl', $this->application_url, false);

		if ($user->HasAccess('newsletters', 'send')) {
			$this->template_system->Assign('ScheduleSendPermission', true);
		}

		if ($user->HasAccess('splittest', 'send')) {
			$this->template_system->Assign('SendPermission', true);
		}

		if ($user->HasAccess('splittest', 'edit')) {
			$this->template_system->Assign('EditPermission', true);
		}

		if ($user->HasAccess('splittest', 'create')) {
			$this->template_system->Assign('CopyPermission', true);
		}

		if ($user->HasAccess('splittest', 'delete')) {
			$this->template_system->Assign('DeletePermission', true);
		}

		$paging = $this->SetupPaging($this->admin_url, $number_of_tests);
		$this->template_system->Assign('Paging', $paging, false);

		$perpage = $this->GetPerPage();

		$this->template_system->Assign('DateFormat', GetLang('DateFormat'));

		// paging always starts at '1' - so take one off so we get the right offset.
		$page_number = $this->GetCurrentPage() - 1;

		$sortdetails = $this->GetSortDetails();

		$tests = $api->GetSplitTests($userid, $sortdetails, false, $page_number, $perpage);

		foreach ($tests as $p => $test_details) {
			switch ($test_details['splittype']) {
				case 'distributed':
					$tip = GetLang('Addon_splittest_Manage_SplitType_Distributed');
					$tests[$p]['tipdetails'] = sprintf(GetLang('Addon_splittest_Manage_SplitType_Distributed_TipDetails'), self::PrintNumber($test_details['campaigncount']));
				break;
				case 'percentage':
					$tip = GetLang('Addon_splittest_Manage_SplitType_Percentage');
					$percent = $test_details['splitdetails']['percentage'];
					$remainder = floor(100 - $percent);

					if ($test_details['splitdetails']['hoursafter'] % 24 == 0) {
						$amount = $test_details['splitdetails']['hoursafter'] / 24;
						$scale = GetLang('Addon_splittest_Days');
					} else {
						$amount = $test_details['splitdetails']['hoursafter'];
						$scale = GetLang('Addon_splittest_Hours');
					}

					$tests[$p]['tipdetails'] = sprintf(GetLang('Addon_splittest_Manage_SplitType_Percentage_TipDetails'), self::PrintNumber($test_details['campaigncount']), self::PrintNumber($percent), self::PrintNumber($remainder), self::PrintNumber($amount), $scale);

					$tests[$p]['TimeoutTipHeading'] = GetLang('Addon_splittest_TimeoutMode');
					$tests[$p]['TimeoutTipDetails'] = sprintf(GetLang('Addon_splittest_TimeoutMode_TipDetails'), self::PrintNumber($percent), self::PrintNumber($amount), $scale, self::PrintNumber($remainder));
				break;
			}
			$tests[$p]['tipheading'] = htmlspecialchars($tip, ENT_QUOTES, SENDSTUDIO_CHARSET);
			$tests[$p]['splitname'] = htmlspecialchars($test_details['splitname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$tests[$p]['campaign_names'] = htmlspecialchars($test_details['campaign_names'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		}

		$this->template_system->Assign('splitTests', $tests);
		$this->template_system->ParseTemplate('manage_display');
	}

	/**
	 * Admin_Action_Create
	 * This handles creating a split test
	 * If we are not posting a form (that is, we're just showing the form), then it shows the form and returns.
	 *
	 * If we are posting a form, then it will check & save the details posted into the database.
	 * It also handles the "Copy" process as that is basically "creating" a new split test but based on an existing one.
	 *
	 * @uses GetApi
	 * @uses GetUser
	 * @uses _ShowForm
	 */
	public function Admin_Action_Create()
	{
		$user = GetUser();

		$copy = $this->_getGETRequest('Copy', null);
		if ($copy != null) {
			$copy_from = (int)$copy;
			$api = $this->GetApi();
			if (SplitTest_API::OwnsSplitTests($user->Get('userid'), $copy) || $user->Admin()) {
				$new_name = $api->Copy($copy_from, $user->Get('userid'));
				if ($new_name) {
					FlashMessage(sprintf(GetLang('Addon_splittest_copy_successful'), htmlspecialchars($new_name, ENT_QUOTES, SENDSTUDIO_CHARSET)), SS_FLASH_MSG_SUCCESS, $this->admin_url);
				} else {
					FlashMessage(GetLang('Addon_splittest_copy_unsuccessful'), SS_FLASH_MSG_ERROR, $this->admin_url);
				}
				return;
			}
			FlashMessage(GetLang('Addon_splittest_copy_unsuccessful'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$passthru_campaigns = $this->_getPOSTRequest('passthru_campaigns', false);
		if (empty($_POST) || !empty($passthru_campaigns)) {
			return $this->_ShowForm(null, $passthru_campaigns);
		}

		list($errors, $splitdetails) = $this->_CheckFormPost();

		if ($errors) {
			return $this->_ShowForm();
		}

		$create_details = array (
			'splitname' => trim($this->_getPOSTRequest('splitname', null)),
			'splittest_campaigns' => $this->_getPOSTRequest('splittest_campaigns', null),
			'splittype' => $this->_getPOSTRequest('splittype', null),
			'splitdetails' => $splitdetails,
			'userid' => $user->Get('userid'),
		);

		$split_api = $this->GetApi();
		$create_result = $split_api->Create($create_details);

		if (!$create_result) {
			FlashMessage(GetLang('Addon_splittest_AddonNotCreated'), SS_FLASH_MSG_ERROR);
			return $this->_ShowForm();
		}

		$redirect_to = $this->admin_url;
		if (isset($_POST['Submit_Send'])) {
			// we are doing a 'Save and Send'
			$redirect_to .= '&Action=Send&id=' . $create_result;
		}
		FlashMessage(GetLang('Addon_splittest_AddonCreated'), SS_FLASH_MSG_SUCCESS, $redirect_to);
		return;
	}

	/**
	 * Admin_Action_Edit
	 * This handles editing a split test
	 * If we are not posting a form (that is, we're just showing the form), then it shows the form and returns.
	 *
	 * If we are posting a form, then it will check & updates the details posted into the database.
	 *
	 * @uses GetApi
	 * @uses GetUser
	 * @uses _ShowForm
	 */
	public function Admin_Action_Edit()
	{
		$user = GetUser();
		$split_api = $this->GetApi();
		$id = $this->_getGETRequest('id', null);

		if (!SplitTest_API::OwnsSplitTests($user->Get('userid'), $id) && !$user->Admin()) {
			FlashMessage(GetLang('NoAccess'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		if (empty($_POST)) {
			if ($id != null) {
				return $this->_ShowForm($id);
			}
			FlashMessage(GetLang('Addon_splittest_UnableToLoadSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		list($errors, $splitdetails) = $this->_CheckFormPost();

		if ($errors) {
			return $this->_ShowForm($id);
		}

		$new_details = array (
			'splitname' => trim($this->_getPOSTRequest('splitname', null)),
			'splittest_campaigns' => $this->_getPOSTRequest('splittest_campaigns', null),
			'splittype' => $this->_getPOSTRequest('splittype', null),
			'splitdetails' => $splitdetails,
		);

		$update_result = $split_api->Save($id, $new_details);

		if (!$update_result) {
			FlashMessage(GetLang('Addon_splittest_SplittestNotUpdated'), SS_FLASH_MSG_ERROR);
			return $this->_ShowForm($id);
		}

		$redirect_to = $this->admin_url;
		if (isset($_POST['Submit_Send'])) {
			// we are doing a 'Save and Send'
			$redirect_to .= '&Action=Send&id=' . $id;
		}
		FlashMessage(GetLang('Addon_splittest_SplittestUpdated'), SS_FLASH_MSG_SUCCESS, $redirect_to);
		return;
	}

	/**
	 * Admin_Action_Delete
	 * This function handles what happens when you delete a split test.
	 * It checks you are doing a form post.
	 * Then it grabs the api and passes the id(s) across to the api to delete.
	 *
	 * It checks what the api returns and creates a flash message based on the result.
	 * Eg you can't delete a split test campaign while it's sending.
	 *
	 * After that, it returns you to the 'Manage' page.
	 *
	 * @uses SplitTest_API::Delete
	 * @see Admin_Action_Default
	 * @uses GetApi
	 */
	public function Admin_Action_Delete()
	{
		$user = GetUser();
		$api = $this->GetApi();

		$split_ids = $this->_getPOSTRequest('splitids', null);
		if (is_null($split_ids)) {
			$split_ids = $this->_getPOSTRequest('splitid', null);
		}

		if (is_null($split_ids)) {
			FlashMessage(GetLang('Addon_splittest_ChooseSplittestsToDelete'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$split_ids = SplitTest_API::FilterIntSet($split_ids);

		if (!SplitTest_API::OwnsSplitTests($user->Get('userid'), $split_ids) && !$user->Admin()) {
			FlashMessage(GetLang('NoAccess'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$deleted = 0;
		$not_deleted = 0;

		foreach ($split_ids as $split_id) {
			$delete_success = $api->Delete($split_id);
			if ($delete_success) {
				$deleted++;
				continue;
			}
			$not_deleted++;
		}

		/**
		 * If there are only "delete ok" messages, then just work out the number to show
		 * and then create a flash message.
		 */
		$url = $this->admin_url;
		if ($not_deleted > 0) {
			$url = null;
		}

		if ($deleted == 1) {
			FlashMessage(GetLang('Addon_splittest_SplittestDeleted_One'), SS_FLASH_MSG_SUCCESS, $url);
			if ($not_deleted == 0) {
				return;
			}
		}

		if ($deleted > 1) {
			FlashMessage(sprintf(GetLang('Addon_splittest_SplittestDeleted_Many'), self::PrintNumber($deleted)), SS_FLASH_MSG_SUCCESS, $url);
			if ($not_deleted == 0) {
				return;
			}
		}

		if ($not_deleted == 1) {
			$msg = GetLang('Addon_splittest_SplittestNotDeleted_One');
		} else {
			$msg = sprintf(GetLang('Addon_splittest_SplittestNotDeleted_Many'), self::PrintNumber($not_deleted));
		}
		FlashMessage($msg, SS_FLASH_MSG_ERROR, $this->admin_url);
	}

	/**
	 * _ShowForm
	 * This is a private function that both creating a split test and editing a split test uses.
	 * If an id is passed through (ie when editing), it will fill in the relevant details in the form for processing.
	 * Based on whether an id is passed through and whether a test is successfully loaded, it will also change the form action.
	 *
	 * It uses the newsletters api to get the newsletters/email campaigns the user has access to so they are shown in the dropdown list.
	 * It only shows "live" (active) newsletters the user has access to.
	 * If the user creating/editing a split test is an admin user, then all live newsletters are shown.
	 * If they are not an admin user, then only the live newsletters the user created are shown.
	 *
	 * @param Int $splitid The split test id to load for editing if applicable. If none is supplied, then we assume you're creating a new split test.
	 * @param Array $chosen_campaigns A list of campaign ids to select by default when displaying the form. This will be overridden if $splitid is provided.
	 *
	 * @uses GetUser
	 * @uses Newsletters_API::GetNewsletters
	 * @uses GetApi
	 *
	 * @return Void Just prints out the form. It does not return anything.
	 */
	private function _ShowForm($splitid=null, $chosen_campaigns=array())
	{
		$user = GetUser();

		$admin_url = $this->admin_url;
		$action = $this->_getGETRequest('Action', null);
		$show_send = true;

		if (!empty($chosen_campaigns)) {
			$chosen_campaigns = array_map('intval', $chosen_campaigns);
			foreach ($chosen_campaigns as $k=>$v) {
				unset($chosen_campaigns[$k]);
				$chosen_campaigns[$v] = $v;
			}
		}

		$formtype = 'create';
		$splittype = 'distributed';
		$percentage_percentage = self::percentage_default;
		$splitHoursAfter = self::percentage_hoursafter_default;

		$weight_openrate = self::weight_openrate_default;
		$weight_linkclick = self::weight_linkclick_default;

		$this->template_system->Assign('Percentage_Minimum', self::percentage_minimum);
		$this->template_system->Assign('Percentage_Maximum', self::percentage_maximum);

		$this->template_system->Assign('Percentage_HoursAfter_Minimum', self::percentage_hoursafter_minimum);
		$this->template_system->Assign('Percentage_HoursAfter_Maximum', self::percentage_hoursafter_maximum);
		$this->template_system->Assign('Percentage_HoursAfter_Maximum_Days', floor(self::percentage_hoursafter_maximum/24));

		$this->template_system->Assign('splitHoursAfter_TimeRange', 'hours');

		if ($splitid !== null) {
			$splitid = (int)$splitid;
			if ($splitid <= 0) {
				FlashMessage(GetLang('Addon_splittest_UnableToLoadSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
				return;
			}

			$split_api = $this->GetApi();
			$split_details = $split_api->Load($splitid);

			if (empty($split_details)) {
				FlashMessage(GetLang('Addon_splittest_UnableToLoadSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
				return;
			}

			if (!isset($split_details['splitid'])) {
				FlashMessage(GetLang('Addon_splittest_UnableToLoadSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
				return;
			}

			$jobstatus = $split_details['jobstatus'];
			if (in_array($jobstatus, $split_api->GetSendingJobStatusCodes())) {
				FlashMessage(GetLang('Addon_splittest_UnableToEdit_SendInProgress'), SS_FLASH_MSG_ERROR, $this->admin_url);
				return;
			}

			$formtype = 'edit';
			$this->template_system->Assign('splitid', $splitid);
			$this->template_system->Assign('splitname', htmlspecialchars($split_details['splitname'], ENT_QUOTES, SENDSTUDIO_CHARSET));
			$chosen_campaigns = $split_details['splittest_campaigns'];
			$splittype = $split_details['splittype'];
			$show_send = (empty($split_details['jobstatus']) || $split_details['jobstatus'] == 'c');

			if ($splittype == 'percentage') {
				$splitHoursAfter = (float)$split_details['splitdetails']['hoursafter'];

				if ($splitHoursAfter % 24 == 0) {
					$splitHoursAfter = $splitHoursAfter / 24;
					$this->template_system->Assign('splitHoursAfter_TimeRange', 'days');
				}
				$percentage_percentage = $split_details['splitdetails']['percentage'];
			}
			$weight_openrate = $split_details['splitdetails']['weights']['openrate'];
			$weight_linkclick = $split_details['splitdetails']['weights']['linkclick'];
		}

		$this->template_system->Assign('FormType', $formtype);

		$this->template_system->Assign('TemplateUrl', $this->template_url, false);
		$this->template_system->Assign('BaseAdminUrl', $this->admin_url, false);
		$this->template_system->Assign('AdminUrl', $admin_url, false);
		$this->template_system->Assign('ShowSend', $show_send);

		$flash_messages = GetFlashMessages();

		$this->template_system->Assign('FlashMessages', $flash_messages, false);

		require_once(SENDSTUDIO_API_DIRECTORY . '/newsletters.php');
		$news_api = new Newsletters_API;

		$owner = $user->Get('userid');
		if ($user->Admin()) {
			$owner = 0;
		}

		$campaigns = $news_api->GetLiveNewsletters($owner);

		if (!empty($chosen_campaigns)) {
			foreach ($campaigns as $row => $details) {
				$id = $details['newsletterid'];
				$campaigns[$row]['selected'] = false;
				if (isset($chosen_campaigns[$id])) {
					$campaigns[$row]['selected'] = true;
				}
			}
		}

		$this->template_system->Assign('splitHoursAfter', $splitHoursAfter);

		$this->template_system->Assign('splitType', $splittype);
		$this->template_system->Assign('action', $action);
		$this->template_system->Assign('percentage_percentage', $percentage_percentage);

		$this->template_system->Assign('weight_openrate', $weight_openrate);
		$this->template_system->Assign('weight_linkclick', $weight_linkclick);

		$this->template_system->Assign('campaigncounter', sizeof($chosen_campaigns));

		$this->template_system->Assign('campaigns', $campaigns);

		$this->template_system->ParseTemplate('splittest_form');
	}

	/**
	 * Admin_Action_Stats
	 * This handles the displaying, paging, processing of stats.
	 * Most of the work is actually done in the splittest_stats.php file
	 * Here, just work out which step we're processing, then include the other file
	 * and call the appropriate method.
	 *
	 * @uses splittest_stats.php
	 * @uses Addons_splittest_Stats
	 */
	public function Admin_Action_Stats()
	{
		require_once(dirname(__FILE__) . '/splittest_stats.php');

		$stats = new Addons_splittest_Stats;

		if (method_exists($stats, 'Route')) {
			return $stats->Route();
		}

		/**
		 * If the method doesn't exist, take the user back to the default action.
		 */
		FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
	}

	/**
	 * Admin_Action_Send
	 * This handles the sending process.
	 * It will handle the multistep process for sending a split test campaign.
	 * The send method only works out which step you are up to in the send process and then passes it off
	 * to other methods to handle the actual work and returns whatever the other methods do (which in most cases is nothing).
	 *
	 * Of course it checks the step exists before trying to run it,
	 * otherwise trying to go to step 146 will cause an error.
	 *
	 * Everything passes through this method because of the way permissions work for addons.
	 *
	 * The send processing functionality is actually handled outside of this file in another.
	 * The Admin_Action_Send method includes the second file and calls the methods in that file.
	 * This helps separate functionality and also helps keep memory to a minimum
	 * and also helps with performance.
	 *
	 * @uses Addons_splittest_Send
	 *
	 * @return Mixed If a method from the Addons_splittest_Send class returns something, this method will return that value. Otherwise, it returns nothing.
	 */
	public function Admin_Action_Send()
	{
		$step = 1;
		$pageStep = $this->_getGETRequest('Step', null);
		if ($pageStep != null) {
			$step = (int)$pageStep;
			if ($step <= 0) {
				$step = 1;
			}
		}

		$method = 'Show_Send_Step_'.$step;

		require_once dirname(__FILE__) . '/splittest_send.php';

		$send = new Addons_splittest_Send;

		if (method_exists($send, $method)) {
			return $send->$method();
		}

		/**
		 * If the method doesn't exist, take the user back to the default action.
		 */
		FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
	}

	/**
	 * PrintNumber
	 * Helper function to print a number out using the right language variable for the thousands separator.
	 * Used by sending & stats.
	 *
	 * @param Int $number The number to format.
	 *
	 * @return String Returns the number formatted per language variables.
	 */
	static function PrintNumber($number=0)
	{
		return number_format((float)$number, 0, GetLang('NumberFormat_Dec'), GetLang('NumberFormat_Thousands'));
	}

	/**
	* TimeDifference
	* Returns the time difference in an easy format / unit system (eg how many seconds, minutes, hours etc).
	*
	* @param Int $timedifference Time difference as an integer to transform.
	*
	* @return String Time difference plus units.
	*/
	function TimeDifference($timedifference)
	{
		if ($timedifference < 60) {
			if ($timedifference == 1) {
				$timechange = GetLang('TimeTaken_Seconds_One');
			} else {
				$timechange = sprintf(GetLang('TimeTaken_Seconds_Many'), self::PrintNumber($timedifference, 0));
			}
		}

		if ($timedifference >= 60 && $timedifference < 3600) {
			$num_mins = floor($timedifference / 60);

			$secs = floor($timedifference % 60);

			if ($num_mins == 1) {
				$timechange = GetLang('TimeTaken_Minutes_One');
			} else {
				$timechange = sprintf(GetLang('TimeTaken_Minutes_Many'), self::PrintNumber($num_mins, 0));
			}

			if ($secs > 0) {
				$timechange .= ', ' . sprintf(GetLang('TimeTaken_Seconds_Many'), self::PrintNumber($secs, 0));
			}
		}

		if ($timedifference >= 3600) {
			$hours = floor($timedifference/3600);
			$mins = floor($timedifference % 3600) / 60;

			if ($hours == 1) {
				if ($mins == 0) {
					$timechange = GetLang('TimeTaken_Hours_One');
				} else {
					$timechange = sprintf(GetLang('TimeTaken_Hours_One_Minutes'), self::PrintNumber($mins, 0));
				}
			}

			if ($hours > 1) {
				if ($mins == 0) {
					$timechange = sprintf(GetLang('TimeTaken_Hours_Many'), self::PrintNumber($hours, 0));
				} else {
					$timechange = sprintf(GetLang('TimeTaken_Hours_Many_Minutes'), self::PrintNumber($hours, 0), self::PrintNumber($mins, 0));
				}
			}
		}

		// can expand this futher to years/months etc - the schedule_manage file has it all done in javascript.

		return $timechange;
	}

	/**
	 * _CheckFormPost
	 * This is used by both when posting a new split test and also when posting an existing split test.
	 *
	 * It checks all fields are filled in.
	 * If they aren't, it generates the appropriate flash messages and returns.
	 *
	 * If there are no errors, the array that gets returned contains all of the split details necessary
	 * to create or update a percentage type split test.
	 *
	 * It only contains the percentage details:
	 * <code>
	 * $splitdetails = array (
	 * 	'percentage' => 0 -> 100,
	 * 	'hoursafter' => 2 -> 720,
	 * 	'weights' => array (
	 * 		'openrate' => 50,
	 * 		'linkclick' => 50,
	 * 	),
	 * );
	 * </code>
	 *
	 * It also checks the weights add up to 100%
	 *
	 * @uses percentage_minimum
	 * @uses percentage_maximum
	 * @uses percentage_hoursafter_minimum
	 * @uses percentage_hoursafter_maximum
	 *
	 * @return Array Returns an array containing whether there were any errors and also a complete splitdetails array which can then be passed off to the api for creating/saving.
	 */
	private function _CheckFormPost()
	{
		$errors = false;

		$fields = array (
			'splitname' => 'FillInField_SplitName',
			'splittest_campaigns' => 'ChooseCampaigns',
			'splittype' => 'ChooseSplitType',
		);

		$splitdetails = array();

		foreach ($fields as $fieldname => $lang_var) {
			$field = $this->_getPOSTRequest($fieldname, null);
			if ($field == null) {
				FlashMessage(GetLang('Addon_splittest_' . $lang_var), SS_FLASH_MSG_ERROR);
				$errors = true;
				continue;
			}
		}

		if ($_POST['splittype'] == 'percentage') {
			$splitdetails['percentage'] = 0;
			if (isset($_POST['percentage_percentage'])) {
				$splitdetails['percentage'] = (int)$_POST['percentage_percentage'];
			}

			if ($splitdetails['percentage'] < self::percentage_minimum || $splitdetails['percentage'] > self::percentage_maximum) {
				FlashMessage(sprintf(GetLang('Addon_splittest_Percentage_Percentage_Between'), self::percentage_minimum, self::percentage_maximum), SS_FLASH_MSG_ERROR);
				$errors = true;
			}

			$splitdetails['hoursafter'] = 0;
			if (isset($_POST['percentage_hoursafter'])) {
				$splitdetails['hoursafter'] = (float)$_POST['percentage_hoursafter'];
			}

			if ($splitdetails['hoursafter'] < self::percentage_hoursafter_minimum || $splitdetails['hoursafter'] > self::percentage_hoursafter_maximum) {
				FlashMessage(sprintf(GetLang('Addon_splittest_Percentage_HoursAfter_Between'), self::percentage_hoursafter_minimum, ceil(self::percentage_hoursafter_maximum/24)), SS_FLASH_MSG_ERROR);
				$errors = true;
			}
		}

		$splitdetails['weights']['openrate'] = 0;
		if (isset($_POST['weights']['openrate'])) {
			$splitdetails['weights']['openrate'] = (int)$_POST['weights']['openrate'];
		}

		$splitdetails['weights']['linkclick'] = 0;
		if (isset($_POST['weights']['linkclick'])) {
			$splitdetails['weights']['linkclick'] = (int)$_POST['weights']['linkclick'];
		}

		if ($splitdetails['weights']['openrate'] + $splitdetails['weights']['linkclick'] != 100) {
			FlashMessage(GetLang('Addon_splittest_WeightsMustTotal100'), SS_FLASH_MSG_ERROR);
			$errors = true;
		}

		return array($errors, $splitdetails);
	}

	/**
	 * CleanupPartialSends
	 * Cleans up any sends that haven't been completed if a browser crashes or a user navigates away from the "send" process.
	 *
	 * This is needed so if a user gets to the last step and decides to not send a split test
	 * or if they navigate away to another page,
	 * it credits the user back with their now "used" email credits.
	 *
	 * @param EventData_IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES $data The data passed in contains an array of the current pagename which is used to work out whether to do anything or not.
	 *
	 * @return Void Doesn't return anything.
	 * @uses EventData_IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES
	 */
	public static function CleanupPartialSends(EventData_IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES $data)
	{
		/**
		 * We want to clean up the "job" if:
		 * - we're not looking at an addons page
		 * - if we are looking at an addon, make sure it's not the 'splittest' addon.
		 * - if we are looking at the 'splittest' addon, make sure we're not in the middle of the 'send' process somewhere.
		 */
		if ($data->page == 'addons') {
			if (isset($_GET['Addon']) && strtolower($_GET['Addon']) == 'splittest') {
				if (isset($_GET['Action']) && strtolower($_GET['Action']) === 'send') {
					return;
				}
			}
		}

		$send_details = IEM::sessionGet('SplitTestSend_Cleanup');
		if (!$send_details || empty($send_details)) {
			return;
		}

		if (!isset($send_details['Job'])) {
			return;
		}

		$user = IEM::userGetCurrent();

		require_once dirname(__FILE__) . '/api/splittest_send.php';
		
		$send_api = new Splittest_Send_API;
		
		$send_api->DeleteJob($send_details['Job'], $send_details['splitid']);

		if (isset($send_details['Stats'])) {
			if (!class_exists('Stats_API', false)) {
				require_once SENDSTUDIO_API_DIRECTORY . '/stats.php';
			}
			
			$stats_api = new Stats_API;

			/**
			 * Delete any left over stats.
			 * 
			 * These might have been created if the user is sending via the popup window
			 * but they clicked 'cancel' on the last step.
			 */
			$stats = array_values($send_details['Stats']);
			
			$stats_api->Delete($stats, 'n');
		}

		IEM::sessionRemove('SplitTestSend_Cleanup');
	}

	/**
	 * DeleteNewsletters
	 * This is used to check it's ok to delete an email campaign/newsletter from the 'manage email campaigns' page.
	 * It's called from inside the newsletter api so if someone tries to delete via the api, this still gets triggered.
	 *
	 * The data passed in contains two elements:
	 * - newsletterid (the id to check isn't being used)
	 * - status - a true/false flag about whether it's ok to delete the newsletter or not.
	 *
	 * @param EventData_IEM_NEWSLETTERSAPI_DELETE $data This contains an array of the newsletterid to check and also a "status" (true/false)
	 *
	 * @uses Splittest_API::GetCampaignsUsed
	 * @uses EventData_IEM_NEWSLETTERSAPI_DELETE
	 */
	public static function DeleteNewsletters(EventData_IEM_NEWSLETTERSAPI_DELETE $data)
	{
		if (!isset($data->newsletterid) || !isset($data->status)) {
			$data->status = false;
			return;
		}

		require_once dirname(__FILE__) . '/api/splittest.php';
		$api = new Splittest_API;

		$campaigns_used = $api->GetCampaignsUsed($data->newsletterid);

		if (empty($campaigns_used)) {
			$data->status = true;
			return;
		}

		/**
		 * In case we're processing multiple newsletters,
		 * kill off the previous messages (if any)
		 * to make sure we only set one message.
		 */
		GetFlashMessages();

		FlashMessage(GetLang('Addon_splittest_CampaignsNotDeleted_UsedBySplitTest'), SS_FLASH_MSG_ERROR);
		$data->status = false;
	}

	/**
	 * ManageNewsletters
	 * This event hook does two things:
	 *
	 * Adds any flash messages to the current message being displayed (passed in).
	 * This is mainly used if newsletters are deleted but they can't be because
	 * they are used by split test campaigns.
	 *
	 * Adds a 'Create Split Test' button to the page where a use can select two
	 * or more campaigns and create a split test with these campaigns already
	 * selected.
	 *
	 * @param EventData_IEM_NEWSLETTERS_MANAGENEWSLETTERS $data The current message that's going to be displayed.
	 */
	public static function ManageNewsletters(EventData_IEM_NEWSLETTERS_MANAGENEWSLETTERS $data)
	{
		// Append any flash messages
		$data->displaymessage .= GetFlashMessages();

		// Append 'Create Split Test' button
		$user =& GetUser();
		if (!$user->HasAccess('splittest', 'create')) {
			return;
		}

		$addon = new self();
		$addon->GetTemplateSystem();

		$create_button = $addon->template_system->ParseTemplate('create_button', true);

		$addon->template_system->Assign('alert_msg', GetLang('Addon_splittest_ChooseCampaigns'));
		$addon->template_system->Assign('url', $addon->admin_url . '&Action=Create');
		$button_js = $addon->template_system->ParseTemplate('newsletter_button', true);

		if (!isset($GLOBALS['Newsletters_ExtraButtons'])) {
			$GLOBALS['Newsletters_ExtraButtons'] = '';
		}
		$GLOBALS['Newsletters_ExtraButtons'] .= $create_button;
		$GLOBALS['Newsletters_ExtraButtons'] .= $button_js;
	}

	/**
	 * GenerateJobListQuery
	 * Generates an sql query which gets included when the "scheduled sending" page job list is generated.
	 * It's a query by itself but becomes part of a subquery in the calling code.
	 *
	 * The data passed in contains a few elements:
	 * - listids (lists to compare against - ie the lists the user has access to)
	 * - countonly - whether the generated query should be for a "countonly" query (for paging)
	 * - subqueries - the existing subqueries array
	 *
	 * If it's a countonly query, then just return the jobid's for the jobtype of 'splittest'.
	 * Since it's going to be part of a subquery, we just need the job id's and the calling code
	 * works out the actual count from the generated subquery/subqueries.
	 *
	 * Basically it works out a query like this for a countonly query:
	 * <code>
	 * 	select jobid from jobs j inner join jobs_lists jl on (j.jobid=jl.jobid) inner join splittests s on (s.splitid=j.fkid)
	 * 	where jl.listid in ($listids) and j.jobtype='splittest'
	 * </code>
	 *
	 * The full query has to return the following columns:
	 * - jobid
	 * - jobtype ('splittest')
	 * - job description ('split test campaign')
	 * - the lists the job is sending to
	 * - "null" for the subject (since there are potentially multiple subjects)
	 * - "null" as the newsletter id (since there are multiple newsletter id's for a split test campaign)
	 *
	 * @param EventData_IEM_JOBSAPI_GETJOBLIST $data The existing data which includes whether the query should be a countonly query, which lists to look at, and the existing subqueries.
	 *
	 * @uses EventData_IEM_JOBSAPI_GETJOBLIST
	 */
	public static function GenerateJobListQuery(EventData_IEM_JOBSAPI_GETJOBLIST $data)
	{
		$listids = $data->listids;
		if (empty($listids)) {
			$listids = array('0');
		}

		$from_clause = "
			FROM
				[|PREFIX|]jobs j INNER JOIN [|PREFIX|]jobs_lists jl ON (j.jobid=jl.jobid)
				INNER JOIN [|PREFIX|]splittests s ON (s.splitid=j.fkid)
			WHERE
				jl.listid in (" . implode(',', $listids) . ") AND
				j.jobtype='splittest' AND
				j.fktype='splittest'
			";

		if ($data->countonly) {
			$query = "
				SELECT
					j.jobid
					" . $from_clause;
			$data->subqueries[] = $query;
			return;
		}

		$listname_query = "(SELECT CONCAT('\'', GROUP_CONCAT(name SEPARATOR '\',\''), '\'') FROM [|PREFIX|]lists l INNER JOIN [|PREFIX|]jobs_lists jl ON (l.listid=jl.listid) WHERE jl.jobid=j.jobid AND jl.listid IN (" . implode(',', $listids) . "))";
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$listname_query = "'\'' || array_to_string(array(SELECT l.name FROM [|PREFIX|]lists l INNER JOIN [|PREFIX|]jobs_lists jl ON (l.listid=jl.listid) WHERE jl.jobid=j.jobid AND jl.listid IN (" . implode(',', $listids) . ")), '\',\'') || '\''";
		}

		$query = "
			SELECT
				j.jobid,
				'splittest' AS jobtype,
				'" . GetLang('Addon_splittest_Schedule_Description') . "' AS jobdescription,
				splitname AS name,
				" . $listname_query . " AS listname,
				null AS subject,
				null AS newsletterid " . $from_clause;

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query .= " GROUP BY j.jobid";
		}

		$data->subqueries[] = $query;
	}

	/**
	 * PauseSchedule
	 * If a scheduled job is a "splittest" type, then this will pause the schedule
	 * and set an appropriate message in the "GLOBALS[Message]" field.
	 *
	 * If it's not a splittest job, then this will just return.
	 *
	 * @param EventData_IEM_SCHEDULE_PAUSEJOB $data The data array contains the jobtype and the jobid to process.
	 *
	 * @uses InterspireEventData::preventDefault()
	 * @uses Splittest_Send_API::PauseJob()
	 */
	public static function PauseSchedule(EventData_IEM_SCHEDULE_PAUSEJOB $data)
	{
		$jobinfo = &$data->jobrecord;
		if ($jobinfo['jobtype'] != 'splittest') {
			return;
		}

		$data->preventDefault();

		require_once dirname(__FILE__) . '/api/splittest_send.php';
		$send_api = new Splittest_Send_API;

		$paused = $send_api->PauseJob($jobinfo['jobid']);

		if ($paused) {
			FlashMessage(GetLang('Addon_splittest_Send_Paused_Success'), SS_FLASH_MSG_SUCCESS);
		} else {
			FlashMessage(GetLang('Addon_splittest_Send_Paused_Failure'), SS_FLASH_MSG_ERROR);
		}

		$GLOBALS['Message'] = GetFlashMessages();
	}

	/**
	 * ResumeSchedule
	 * If a scheduled job is a "splittest" type, then this will resume the schedule
	 * and set an appropriate message in the "GLOBALS[Message]" field.
	 *
	 * If it's not a splittest job, then this will just return.
	 *
	 * @param EventData_IEM_SCHEDULE_RESUMEJOB $data The data array contains the jobtype and the jobid to process.
	 *
	 * @uses InterspireEventData::preventDefault()
	 * @uses Splittest_Send_API::ResumeJob()
	 */
	public static function ResumeSchedule(EventData_IEM_SCHEDULE_RESUMEJOB $data)
	{
		$jobinfo = &$data->jobrecord;
		if ($jobinfo['jobtype'] != 'splittest') {
			return;
		}

		$data->preventDefault();

		require_once dirname(__FILE__) . '/api/splittest_send.php';
		$send_api = new Splittest_Send_API;

		$resumed = $send_api->ResumeJob($jobinfo['jobid']);
		if ($resumed) {
			FlashMessage(GetLang('Addon_splittest_Send_Resumed_Success'), SS_FLASH_MSG_SUCCESS);
		} else {
			FlashMessage(GetLang('Addon_splittest_Send_Resumed_Failure'), SS_FLASH_MSG_ERROR);
		}

		$GLOBALS['Message'] = GetFlashMessages();
	}

	/**
	 * DeleteSchedules
	 * If scheduled items are going to be deleted, this processes the jobs it needs to.
	 *
	 * The data passed in contains lots of data:
	 * jobids - the job id's that need to be processed
	 * message - the current success/failure message
	 * success - the current success counter (how many jobs have successfully been deleted previous to getting here)
	 * failure - the current failure counter (how many jobs have not successfully been deleted previous to getting here)
	 *
	 * Any non-"splittest" job types are skipped
	 * Any "in progress" splittest jobs are skipped and the failure counter is incremented
	 * Any jobs that can be deleted are - as well as figuring out whether a job needs to give back any email credits.
	 *
	 * Any appropriate messages are added to the "message" item in the passed in array.
	 *
	 * @param EventData_IEM_SCHEDULE_DELETEJOBS $data The data array containing the jobs to process, the current message, success and failure counts.
	 *
	 * @uses Jobs_API::LoadJob()
	 * @uses Stats_API::DeleteUserStats()
	 * @uses Stats_API::MarkNewsletterFinished()
	 * @uses Splittest_Send_API::DeleteJob()
	 * @uses User_API::ReduceEmails()
	 * @uses EventData_IEM_SCHEDULE_DELETEJOBS
	 */
	public static function DeleteSchedules(EventData_IEM_SCHEDULE_DELETEJOBS $data)
	{
		$jobids = &$data->jobids;
		$message = &$data->Message;
		$success = &$data->success;
		$failure = &$data->failure;

		$user = GetUser();

		require_once SENDSTUDIO_API_DIRECTORY . '/jobs.php';
		require_once SENDSTUDIO_API_DIRECTORY . '/stats.php';
		require_once dirname(__FILE__) . '/api/splittest_send.php';

		$jobapi = new Jobs_API;
		$stats_api = new Stats_API;
		$send_api = new Splittest_Send_API;

		foreach ($jobids as $p => $jobid) {
			$jobinfo = $jobapi->LoadJob($jobid);

			if (empty($jobinfo)) {
				continue;
			}

			if ($jobinfo['jobtype'] !== 'splittest') {
				continue;
			}

			if ($jobinfo['jobstatus'] == 'i') {
				$failure++;
				unset($jobids[$p]);
				continue;
			}

			$statids = array();
			if (isset($jobinfo['jobdetails']['Stats'])) {
				$statids = array_values($jobinfo['jobdetails']['Stats']);
			}

			/**
			 * If there are no stats, then the send hasn't started yet.
			 * So just credit the user back with the number of emails they were trying to send.
			 * Use 'ReduceEmails' to re-add the credits by using a double negative :)
			 */
			if (empty($statids) && $jobinfo['jobstatus'] == 'w') {
				$stats_api->DeleteUserStats($jobinfo['ownerid'], $jobid);
				$user->ReduceEmails(-(int)$jobinfo['jobdetails']['SendSize']);
			}

			/**
			 * If a send is started (ie it has stats),
			 * but is not completed,
			 * We need to mark it as complete.
			 *
			 * This also credits a user back if they have any limits in place.
			 *
			 * This needs to happen before we delete the 'job' from the database
			 * as deleting the job cleans up the queues/unsent items.
			 */
			if (!empty($statids) && $jobinfo['jobstatus'] != 'c') {
				$stats_api->MarkNewsletterFinished($statids, $jobinfo['jobdetails']['SendSize']);

			// Credits needs to be returned too whenever the job is canceled AFTER it has been scheduled, but before it was sent
			} elseif ($jobinfo['jobstatus'] != 'c') {
				$stats_api->RefundFixedCredit($jobid);
			}

			$result = $send_api->DeleteJob($jobid);

			if ($result) {
				$success++;
			} else {
				$failure++;
			}
			unset($jobids[$p]);
		}

		/**
		 * Only failure messages get added to the message stack.
		 * Successful deletes are handled in the calling code
		 * in case:
		 * - a non-addon deletes an item
		 * - other addons delete their own items
		 */
		if ($failure > 0) {
			if ($failure == 1) {
				FlashMessage(GetLang('Addon_splittest_Schedule_JobDeleteFail'), SS_FLASH_MSG_ERROR);
			} else {
				FlashMessage(sprintf(GetLang('Addon_splittest_Schedule_JobsDeleteFail'), self::PrintNumber($failure)), SS_FLASH_MSG_SUCCESS);
			}
		}

		$message .= GetFlashMessages();
	}

	/**
	 * EditSchedule
	 * This prints out the "edit schedule" page
	 * which in reality isn't much different to a normal edit schedule page except:
	 * - list multiple campaigns (each name being clickable to show a preview of that campaign)
	 *
	 * The data passed in contains the "jobdetails" array from the job being edited.
	 * That array contains the splitid - which can then be used to work out the campaigns etc being used.
	 *
	 * If it's not a 'splittest' job type, this function/method just returns and doesn't do anything.
	 *
	 * @param EventData_IEM_SCHEDULE_EDITJOB $data The array of jobdetails for the scheduled event being edited.
	 *
	 * @uses InterspireEventData::preventDefault
	 * @uses User_API::GetLists
	 * @uses User_API::GetSegmentList
	 * @uses SendStudio_Functions::CreateDateTimeBox
	 */
	public static function EditSchedule(EventData_IEM_SCHEDULE_EDITJOB $data)
	{
		$jobinfo = &$data->jobrecord;

		if (empty($jobinfo) || !isset($jobinfo['jobtype'])) {
			FlashMessage(GetLang('Addon_splittest_Schedule_JobInvalid'), SS_FLASH_MSG_ERROR, self::application_url . 'index.php?Page=Schedule');
			return;
		}

		if ($jobinfo['jobtype'] != 'splittest') {
			return;
		}

		$data->preventDefault();

		$self = new self;
		$user = GetUser();

		$splitid = $jobinfo['jobdetails']['splitid'];

		/**
		 * Check for messages.
		 * If there are no flash messages, maybe it's being set in the "GLOBALS[Message]" string instead
		 * by the admin/functions/schedule.php file.
		 * Check that too :)
		 */
		$flash_messages = GetFlashMessages();
		if (isset($GLOBALS['Message'])) {
			$flash_messages .= $GLOBALS['Message'];
		}
		$self->template_system->Assign('FlashMessages', $flash_messages, false);

		$self->template_system->Assign('Jobid', $jobinfo['jobid']);

		require_once SENDSTUDIO_API_DIRECTORY . '/newsletters.php';
		require_once SENDSTUDIO_API_DIRECTORY . '/jobs.php';
		require_once SENDSTUDIO_API_DIRECTORY . '/stats.php';

		$job_api = new Jobs_API;
		$stats_api = new Stats_API;
		$news_api = new Newsletters_API;

		$splitapi = $self->GetApi();
		$splitdetails = $splitapi->Load($splitid);

		$sendtype = $jobinfo['jobdetails']['sendingto']['sendtype'];

		$sending_to = $jobinfo['jobdetails']['sendingto']['sendids'];

		$sendinglist = array();

		if ($sendtype == 'list') {
			$user_lists = $user->GetLists();
			foreach ($sending_to as $listid) {
				$sendinglist[$listid] = htmlspecialchars($user_lists[$listid]['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}
		}

		if ($sendtype == 'segment') {
			$user_segments = $user->GetSegmentList();
			foreach ($sending_to as $segmentid) {
				$sendinglist[$segmentid] = htmlspecialchars($user_segments[$segmentid]['segmentname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}
		}

		/**
		 * Get the sendstudio functions file to create the date/time box.
		 */
		require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';

		/**
		 * also need to load the 'send' language file so it can put in the names/descriptions.
		 */
		require_once SENDSTUDIO_LANGUAGE_DIRECTORY . '/default/send.php';
		$ssf = new SendStudio_Functions;
		$timebox = $ssf->CreateDateTimeBox($jobinfo['jobtime'], false, 'datetime', true);

		$self->template_system->Assign('ApplicationUrl', $self->application_url, false);

		$self->template_system->Assign('ScheduleTimeBox', $timebox, false);

		$self->template_system->Assign('SendType', $sendtype);

		$self->template_system->Assign('campaigns', $splitdetails['splittest_campaigns']);

		$self->template_system->Assign('sendinglist', $sendinglist);

		$self->template_system->ParseTemplate('schedule_edit');
	}

	/**
	 * GetJobStatus
	 * Returns a "job status" message explaining what the job status means.
	 * It looks for specific status codes only this addon uses.
	 *
	 * @param EventData_IEM_JOBSAPI_GETJOBSTATUS $data The current data which contains the 'jobstatus' code and a message placeholder.
	 *
	 * @uses jobstatuscodes
	 *
	 * @return Void The data is passed in by reference, so this just modifies the statusmessage in the data->data array.
	 *
	 * @uses EventData_IEM_JOBSAPI_GETJOBSTATUS
	 */
	static function GetJobStatus(EventData_IEM_JOBSAPI_GETJOBSTATUS $data)
	{
		/**
		 * If it's a status code used by this addon,
		 * set the message, stop the event propogation in the calling code and return.
		 *
		 * If it's not a status code used by this addon, do nothing.
		 */
		if (in_array($data->jobstatus, self::$jobstatuscodes)) {
			$data->preventDefault();
			$rand_tipid = rand(1, 100000);
			$heading = GetLang('Addon_splittest_Schedule_JobStatus_Timeout');
			$message = GetLang('Addon_splittest_Schedule_JobStatus_Timeout_TipDetails');
			$tip_message = '<span class="HelpText" onMouseOut="HideHelp(\'splitDisplayTimeout' . $rand_tipid . '\');" onMouseOver="ShowQuickHelp(\'splitDisplayTimeout' . $rand_tipid . '\', \'' . $heading . '\', \'' . $message . '\');">' . $heading . '</span><div id="splitDisplayTimeout' . $rand_tipid . '" style="display: none;"></div>';
			$data->statusmessage = $tip_message;
		}
	}

	/**
	 * getDateFormat
	 * Obtains the date format used for split testing.
	 *
	 * @return String The date format ready to be fed to the date() function.
	 */
	protected static function getDateFormat()
	{
		return GetLang('DateFormat') . ' ' . GetLang('Stats_TimeFormat');
	}
}
