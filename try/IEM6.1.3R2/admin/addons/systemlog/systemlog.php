<?php
/**
 * This file contains the 'systemlog' addon which shows php errors, notices, warnings generated from debugging code.
 *
 * @package Interspire_Addons
 * @subpackage Addons_systemlog
 */

/**
 * Make sure the base Interspire_Addons class is defined.
 */
if (!class_exists('Interspire_Addons', false)) {
	require_once(dirname(dirname(__FILE__)) . '/interspire_addons.php');
}

/**
 * This class handles listing, deleting and processing system log entries.
 * It also puts itself in the tools menu for system-admins to use.
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 */
class Addons_systemlog extends Interspire_Addons
{
	/**
	 * Set a default for the maximum number of log entries to keep.
	 * This is used when the addon is first installed so we have a default setting.
	 * Of course it can be changed through the admin control panel to be whatever you like.
	 *
	 * @var Int
	 */
	protected $default_settings = array('logsize' => '1000');

	/**
	 * Admin_Action_Default
	 * This is the method that is run when an admin user views the addon.
	 *
	 * If there are no log entries to worry about, then it will show the 'Addon_systemlog_logs_empty' file from the templates/ folder
	 * and then quickly return out of the function.
	 *
	 * If there are log entries to worry about, it will work out the paging for the top of the list
	 * then show buttons to allow you to delete selected entries or delete all log entries at once
	 *
	 * If you want to view a particular log entry, click the '+' next to the relevant log summary and it will expand to show more detail
	 *
	 * @uses db
	 * @uses template_system
	 * @uses InterspireTemplate::GetTemplatePath()
	 * @uses InterspireTemplate::SetTemplatePath()
	 * @uses InterspireTemplate::ParseTemplate()
	 * @uses InterspireTemplate::Assign()
	 * @uses GetFlashMessages
	 * @uses perpage
	 * @uses show_max_pages
	 * @uses admin_url
	 * @uses GetId
	 *
	 * @return Void Instead of returning anything, the list of log entries (if any) are shown directly.
	 */
	public function Admin_Action_Default()
	{
		$flash_messages = GetFlashMessages();

		$this->template_system->Assign('FlashMessages', $flash_messages, false);

		$number_of_logs = $this->db->FetchOne("SELECT count(logid) AS count FROM " . $this->db->TablePrefix . "log_system_system");
		if ($number_of_logs == 0) {
			$curr_template_dir = $this->template_system->GetTemplatePath();

			$this->template_system->SetTemplatePath(SENDSTUDIO_TEMPLATE_DIRECTORY);

			$GLOBALS['Error'] = GetLang('Addon_systemlog_Logs_Empty');
			$error = $this->template_system->ParseTemplate('errormsg', true);

			$this->template_system->SetTemplatePath($curr_template_dir);

			$this->template_system->Assign('Addon_systemlog_Logs_Empty', $error , false);
			$this->template_system->ParseTemplate('logs_empty');
			return;
		}

		$paging = $this->SetupPaging($this->admin_url, $number_of_logs);
		$this->template_system->Assign('Paging', $paging, false);

		$perpage = $this->GetPerPage();

		// paging always starts at '1' - so take one off so we get the right offset.
		$page_number = $this->GetCurrentPage() - 1;

		$offset = $page_number * $perpage;

		$qry = "SELECT logid, logseverity, logtype, logmodule, logsummary, logdate FROM " . $this->db->TablePrefix . "log_system_system ORDER BY logid DESC " . $this->db->AddLimit($offset, $perpage);
		$result = $this->db->Query($qry);

		$this->template_system->Assign('AddonId', $this->GetId());

		while ($row = $this->db->Fetch($result)) {
			$row['rowid'] = 'addon_' . $this->GetId() . '_' . $row['logid'];

			if ($row['logmodule'] === '') {
				$row['logmodule'] = 'Internal';
			}

			if (strlen($row['logsummary']) > 170) {
				$row['logsummary'] = substr($row['logsummary'], 0, 166) . ' ...';
			}
			$row['logsummary'] = htmlspecialchars($row['logsummary']);

			$row['logdate'] = gmdate('M d Y H:i:s', $row['logdate']);

			$image = 'error.gif';
			$severity = 'Error';
			switch ($row['logseverity']) {
				case 1:
					$severity = 'Success';
					$image = 'success.gif';
				break;

				case 2:
					$severity = 'Notice';
					$image = 'notice.gif';
				break;

				case 3:
					$severity = 'Warning';
					$image = 'warning.gif';
				break;
			}
			$row['image'] = $image;
			$row['severity'] = $severity;

			$log_entries[] = $row;
		}

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);
		$this->template_system->Assign('TemplateUrl', $this->template_url, false);

		$this->template_system->Assign('logsList', $log_entries, false);

		$this->template_system->ParseTemplate('logs_display');
	}

	/**
	 * Install
	 * This is called when the addon is installed in the main application.
	 * In this case, it simply sets the default settings and then calls the parent install method to add itself to the database.
	 *
	 * @uses default_settings
	 * @uses Interspire_Addons::Install
	 * @uses Interspire_Addons_Exception
	 *
	 * @throws Throws an Interspire_Addons_Exception if something goes wrong with the install process.
	 * @return True Returns true if all goes ok with the install.
	 */
	public function Install()
	{
		$this->enabled = true;
		$this->configured = true;
		$this->settings = $this->default_settings;

		try {
			$status = parent::Install();
		} catch (Interspire_Addons_Exception $e) {
			throw new Exception("Unable to install addon {$this->GetId()}" . $e->getMessage());
		}
		return true;
	}

	/**
	 * GetEventListeners
	 * This returns an array of events that the addon listens to.
	 *
	 * This addon uses
	 * - 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS' event to put itself into the 'tools' menu at the top
	 *
	 * @see Interspire_Addons::GetEventListeners
	 *
	 * @return Array Returns an array of events, what methods to call and which file to call the method from.
	 */
	public function GetEventListeners()
	{
		$trigger_file = '{%IEM_ADDONS_PATH%}/systemlog/systemlog.php';
		$listeners = array();

		$listeners[] =
			array (
				'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS',
				'trigger_details' => array (
					'Addons_systemlog',
					'GetTextMenuItems',
				),
				'trigger_file' => $trigger_file
			);

		$listeners[] =
			array (
				'eventname' => 'IEM_SYSTEM_STARTUP_AFTER',
				'trigger_details' => array (
					'Addons_systemlog',
					'SetPruneLog',
				),
				'trigger_file' => $trigger_file
			);

		return $listeners;
	}

	/**
	 * GetTextMenuItems
	 * If the addon is installed & enabled, this method is called
	 * A link to use this addon will then be put into the 'tools' menu at the top of the page.
	 *
	 * @param EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS $data The existing text menu items. This addon puts itself into the tools menu.
	 *
	 * @uses InterspireEventData
	 * @uses Interspire_Addons::Load
	 * @uses enabled
	 *
	 * @see SendStudio_Functions::GenerateTextMenuLinks
	 *
	 * @return Void The menu is passed in by reference, so it's manipulated directly.
	 *
	 * @uses EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS
	 */
	public static function GetTextMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS $data)
	{
		$user = GetUser();
		if (!$user->Admin()) {
			return;
		}

		try {
			$me = new self;
			$me->Load();
		} catch (Exception $e) {
			return;
		}

		if (!$me->enabled) {
			return;
		}

		if (!isset($data->data['tools'])) {
			$data->data['tools'] = array();
		}

		$data->data['tools'][] = array (
			'text' => GetLang('Addon_systemlog_Menu_Text'),
			'link' => $me->admin_url,
			'description' => GetLang('Addon_systemlog_Menu_Description'),
		);
		
		unset($me);
	}

	/**
	 * Configure
	 * This method is called when the addon needs to be configured.
	 * It uses the templates/settings.tpl file to show its current settings and display the settings form.
	 *
	 * @uses settings
	 * @uses template_system
	 * @uses InterspireTemplate::Assign
	 * @uses InterspireTemplate::ParseTemplate
	 *
	 * @return String Returns the settings form with the current settings pre-filled.
	 */
	public function Configure()
	{
		foreach ($this->settings as $k => $v) {
			$this->template_system->Assign($k, $v);
		}

		$this->template_system->Assign('SettingsUrl', $this->settings_url, false);
		$this->template_system->Assign('ApplicationUrl', $this->application_url, false);

		return $this->template_system->ParseTemplate('settings', true);
	}


	/**
	 * SaveSettings
	 * This is called when the settings form is submitted.
	 * It checks if any values were posted.
	 * It then checks against the settings it should find (from default_settings) to make sure you're not trying to sneak any extra settings in there
	 *
	 * If no form was posted or if you post invalid options, this will return false (which then displays an error message).
	 *
	 * @see Configure
	 * @uses default_settings
	 * @uses db
	 *
	 * @return Boolean Returns false if an invalid settings form is posted or if
	 */
	public function SaveSettings()
	{
		$settings = array_intersect_key($_POST, $this->default_settings);

		if (empty($settings)) {
			return false;
		}

		return self::SetSettings($settings);
	}


	/**
	 * SetSettings
	 * Saves the settings to the database.
	 *
	 * @uses db
	 *
	 * @param Array $settings The settings to save for this addon.
	 *
	 * @return Boolean True if saved successfully, otherwise false.
	 */
	public static function SetSettings($settings)
	{
		$db = IEM::getDatabase();
		if (!$db) {
			return false;
		}

		$id = str_replace('Addons_', '', __CLASS__);

		if (!isset($settings['logsize'])) {
			return false;
		}

		$settings['logsize'] = abs(intval($settings['logsize']));
		if ($settings['logsize'] == 0) {
			return false;
		}

		$result = $db->Query("UPDATE [|PREFIX|]addons SET configured=1, settings='" . $db->Quote(serialize($settings)) . "' WHERE addon_id='{$id}'");
		return (bool)$result;
	}

	/**
	 * GetSettings
	 * Retrieves the saved settings from the database.
	 *
	 * @see Configure
	 * @uses db
	 *
	 * @return Array The saved settings.
	 */
	public static function GetSettings()
	{
		$db = IEM::getDatabase();
		if (!$db) {
			return array();
		}

		$id = str_replace('Addons_', '', __CLASS__);
		$settings = $db->FetchOne("SELECT settings FROM [|PREFIX|]addons WHERE addon_id='{$id}'");
		if (!$settings) {
			return array();
		}
		return unserialize($settings);
	}

	/**
	 * Admin_Action_Delete
	 * This is called when the 'delete' option is clicked when viewing a list of log items.
	 * If we call this method directly it won't work as it checks we are doing a form post.
	 *
	 * If that check is ok, then it goes through the id's posted to make sure they are all int values
	 * These values are then given to the database to delete.
	 *
	 * If there are no logs to delete, then it creates a 'flashmessage' to display an error and also redirects the user back to the default action.
	 *
	 * If there are logs to delete then that action is performed then the user is directed back to the default action.
	 *
	 * @see Admin_Action_Default
	 * @uses FlashMessage
	 *
	 * @return Void Doesn't return anything.
	 */
	public function Admin_Action_Delete()
	{
		if (empty($_POST) || !isset($_POST['logids']) || empty($_POST['logids'])) {
			FlashMessage(GetLang('Addon_systemlog_no_logs_chosen'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		// make sure we're only going to pass through id's
		$logs_to_delete = array();
		foreach ($_POST['logids'] as $logid) {
			if (is_numeric($logid)) {
				$logs_to_delete[] = $logid;
			}
		}

		if (empty($logs_to_delete)) {
			FlashMessage(GetLang('Addon_systemlog_no_logs_chosen'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}


		$this->db->Query("DELETE FROM " . $this->db->TablePrefix . "log_system_system WHERE logid IN (" . implode(',', $logs_to_delete) . ")");

		FlashMessage(GetLang('Addon_systemlog_logsdeleted'), SS_FLASH_MSG_SUCCESS, $this->admin_url);
	}

	/**
	 * Admin_Action_DeleteAll
	 * This is called when the 'delete all' option is clicked when viewing a list of log items.
	 * If we call this method directly it won't work as it checks we are doing a form post.
	 *
	 * If there are no logs to delete, then it creates a 'flashmessage' to display an error and also redirects the user back to the default action.
	 *
	 * If there are logs to delete then that action is performed then the user is directed back to the default action.
	 *
	 * @see Admin_Action_Default
	 * @uses FlashMessage
	 *
	 * @return Void Doesn't return anything.
	 */
	public function Admin_Action_DeleteAll()
	{
                if(!(isset($_GET['Action']) && $_GET['Action'] == 'DeleteAll')){
			FlashMessage("This method cannot be accessed directly.", SS_FLASH_MSG_ERROR, $this->admin_url);
			return;                   
                }

		$this->db->Query("truncate " . $this->db->TablePrefix . "log_system_system");
		FlashMessage(GetLang('Addon_systemlog_all_logsdeleted'), SS_FLASH_MSG_SUCCESS, $this->admin_url);
	}

	/**
	 * Admin_Action_ViewLog
	 * This is called when a user clicks the '+' next to a log entry to view more detail.
	 * It is called via ajax so there are no page refreshes to show more detail.
	 * It loads the long description from the database and returns it.
	 *
	 * @return String If an invalid id is passed through (or a log entry can't be loaded), this will return a blank string. Otherwise it returns the long description.
	 */
	public function Admin_Action_ViewLog()
	{
		$logid = 0;
		if (isset($_GET['id'])) {
			$logid = (int)$_GET['id'];
		}

		$result = $this->db->Query("SELECT logmsg FROM " . $this->db->TablePrefix . "log_system_system WHERE logid=" . intval($logid));
		if (!$result) {
			return '';
		}
		$msg = $this->db->FetchOne($result);
		return $msg;
	}

	/**
	 * SetPruneLog
	 * Sets the Interspire Logger to only keep the configured amount of log entries.
	 * This function is triggered by the IEM_SYSTEM_STARTUP_AFTER event.
	 *
	 * @uses IEM_SYSTEM_STARTUP_AFTER
	 *
	 * @return Void Does not return anything.
	 */
	public static function SetPruneLog()
	{
		$settings = self::GetSettings();
		$logsystem = GetLogSystem();
		if ($logsystem) {
			$logsystem->SetGeneralLogSize($settings['logsize']);
		}
	}

}
