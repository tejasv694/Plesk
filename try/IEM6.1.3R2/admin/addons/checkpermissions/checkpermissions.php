<?php
/**
 * This file contains the 'checkpermissions' addon which goes through particular files/folders to make sure
 * the app will have enough permissions to write to those locations.
 *
 * @package Interspire_Addons
 * @subpackage Addons_checkpermissions
 */

/**
 * Make sure the base Interspire_Addons class is defined.
 */
if (!class_exists('Interspire_Addons', false)) {
	require_once(dirname(dirname(__FILE__)) . '/interspire_addons.php');
}

/**
 * This class handles checking files/folders are writable for the main application
 * Once it has done that, it will show a status report.
 * It also puts itself in the tools menu for system-admins to use.
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 */
class Addons_checkpermissions extends Interspire_Addons
{

	/**
	 * Install
	 * This addon has no settings to it can automatically be configured and enabled when it's installed
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
		$this->enabled = true;
		$this->configured = true;
        try {
			$status = parent::Install();
		} catch (Interspire_Addons_Exception $e) {
			throw new Exception("Unable to install addon $this->GetId();" . $e->getMessage());
		}
		return true;
	}

	/**
	 * GetEventListeners
	 * This addon puts itself in the 'tools' text menu at the top of the page.
	 * It uses the 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS' event to do this.
	 *
	 * @return Array Returns an array containing the 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS' event to listen to.
	 */
	function GetEventListeners()
	{
		return
			array (
				array (
					'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS',
					'trigger_details' => array (
						'Addons_checkpermissions',
						'GetTextMenuItems',
					),
					'trigger_file' => '{%IEM_ADDONS_PATH%}/checkpermissions/checkpermissions.php'
				),
			);
	}

	/**
	 * GetTextMenuItems
	 * This checks the addon is installed & enabled before displaying in the 'tools' menu at the top of the page.
	 *
	 * @param EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS $data The existing text menu items. This addon puts itself into the tools menu.
	 *
	 * @uses Load
	 * @uses enabled
	 *
	 * @see SendStudio_Functions::GenerateTextMenuLinks
	 *
	 * @return Void The menu is passed in by reference, so it's manipulated directly.
	 *
	 * @uses EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS
	 */
	static function GetTextMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS $data)
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
			'text' => GetLang('Addon_checkpermissions_Menu_Text'),
			'link' => $me->admin_url,
			'description' => GetLang('Addon_checkpermissions_Menu_Description'),
		);
		unset($me);
	}

	/**
	 * Admin_Action_Default
	 * This is the first page shown when you view the addon.
	 * It uses the session to set which files & folders to check permissions of.
	 * Once it has done that, it shows the templates/default.tpl file.
	 *
	 * It currently only checks the:
	 * - admin/temp/ folder
	 * - admin/temp/autoresponder/ folder
	 * - admin/temp/autoresponders/ folder
	 * - admin/temp/newsletters/ folder
	 * - admin/temp/send/ folder
	 * - admin/temp/templates/ folder
	 * - admin/temp/templates_cache/ folder
	 * - admin/temp/user/ folder
	 * - admin/includes/config.php file
	 * - admin/com/storage/template-cache/ folder
	 * - admin/com/storage/session-files/ folder
	 * - admin/com/storage/iem_stash_storage.php file
	 *
	 * @see Admin_Action_CheckPermissions
	 * @see Admin_Action_ShowPopup
	 * @see Admin_Action_Finished
	 *
	 * @return Void Prints out the default page with a 'go' button to actually check permissions.
	 */
	public function Admin_Action_Default()
	{
		IEM::sessionSet($this->addon_id . '_StepNumber', 1);

		$permissions_to_check = array (
			TEMP_DIRECTORY,
			TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'autoresponder',
			TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'autoresponders',
			TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'newsletters',
			TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'send',
			TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'templates',
			TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'user',
			SENDSTUDIO_INCLUDES_DIRECTORY . DIRECTORY_SEPARATOR . 'config.php',
			IEM_STORAGE_PATH . DIRECTORY_SEPARATOR . 'template-cache',
			IEM_STORAGE_PATH . DIRECTORY_SEPARATOR . 'session-files',
			IEM_STORAGE_PATH . DIRECTORY_SEPARATOR . 'iem_stash_storage.php'
		);

		IEM::sessionSet($this->addon_id . '_TotalSteps', sizeof($permissions_to_check));

		IEM::sessionSet($this->addon_id . '_PermissionsChecked', $permissions_to_check);
		IEM::sessionSet($this->addon_id . '_PermissionsOk', array());
		IEM::sessionSet($this->addon_id . '_PermissionsFailed', array());

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);
		$this->template_system->ParseTemplate('default');
	}

	/**
	 * Admin_Action_ShowPopup
	 * This shows the popup window which includes the progress bar.
	 *
	 * @see Admin_Action_Default
	 * @see Admin_Action_CheckPermissions
	 *
	 * @return Void Just prints out the popup window / progress bar so you know what's going on.
	 */
	public function Admin_Action_ShowPopup()
	{
		$this->template_system->Assign('AdminUrl', $this->admin_url, false);
		$this->template_system->Assign('TemplateUrl', $this->template_url, false);
		$this->template_system->Assign('ApplicationUrl', $this->application_url, false);
		$this->template_system->Assign('RandomValue', time());
		$this->template_system->ParseTemplate('progress_report');
	}

	/**
	 * Admin_Action_CheckPermissions
	 * This actually checks the permissions on the files/folders set in the session in the first step.
	 *
	 * If there are no more permissions to check,
	 * it redirects you to the 'Admin_Action_Finished' page which will give you a final status report.
	 *
	 * @see Admin_Action_Default
	 * @see Admin_Action_ShowPopup
	 * @eee Admin_Action_Finished
	 */
	function Admin_Action_CheckPermissions()
	{
		$permissions_checked = IEM::sessionGet($this->addon_id . '_PermissionsChecked');

		$permissions_ok = IEM::sessionGet($this->addon_id . '_PermissionsOk');

		$permissions_failed = IEM::sessionGet($this->addon_id . '_PermissionsFailed');

		$total_steps = IEM::sessionGet($this->addon_id . '_TotalSteps');
		$step_number = IEM::sessionGet($this->addon_id . '_StepNumber');

		$checking_permission = array_pop($permissions_checked);

		IEM::sessionSet($this->addon_id . '_PermissionsChecked', $permissions_checked);

		// array_pop returns null when it's empty - so we can check for that here to see if we're all done.
		if (is_null($checking_permission)) {
			echo "<script>\n";
			echo "self.parent.ProcessFinished();";
			echo "</script>";
			exit;
		}

		$msg = sprintf(GetLang('Addon_checkpermissions_CheckingPermission'), str_replace(SENDSTUDIO_BASE_DIRECTORY, '', $checking_permission), $step_number, $total_steps);

		$percent = ceil(($step_number / $total_steps) * 100);

		echo "<script>";
		echo "self.parent.UpdateStatus('".$msg."', '".$percent."');\n";
		echo "</script>";
		flush();

		// If the file/folder doesn't exist, do not worry about its permissions as it probably hasn't been created by the system yet.
		if (is_writeable($checking_permission) || !file_exists($checking_permission)) {
			$permissions_ok[] = $checking_permission;
			IEM::sessionSet($this->addon_id . '_PermissionsOk', $permissions_ok);
		} else {
			$permissions_failed[] = $checking_permission;
			IEM::sessionSet($this->addon_id . '_PermissionsFailed', $permissions_failed);
		}

		IEM::sessionSet($this->addon_id . '_StepNumber', ($step_number + 1));

		// Throw back to this same page to continue the upgrade process
		echo "<script>\n";
		echo "setTimeout(function() { window.location = '" . $this->admin_url . "&AJAX=1&Action=CheckPermissions&r=" . time() . "'; }, 10);\n";
		echo "</script>";
		exit;
	}

	/**
	 * Admin_Action_Finished
	 * This is the final status report.
	 * It shows a list of files/folders that are ok
	 * Then it shows a list of files/folders that are not ok and can't be written to.
	 *
	 * @see Admin_Action_Default
	 * @see Admin_Action_ShowPopup
	 * @see Admin_Action_CheckPermissions
	 *
	 * @return Void Prints out the report, doesn't return anything.
	 */
	function Admin_Action_Finished()
	{
		$ok_permissions = IEM::sessionGet($this->addon_id . '_PermissionsOk');
		asort($ok_permissions);
		$found_ok = false;
		if (sizeof($ok_permissions) > 1) {
			$found_ok = true;
		}

		$failed_permissions = IEM::sessionGet($this->addon_id . '_PermissionsFailed');
		asort($failed_permissions);
		$found_failed = false;
		if (sizeof($failed_permissions) > 1) {
			$found_failed = true;
		}

		$this->template_system->Assign('ShowOk', $found_ok);
		$this->template_system->Assign('ShowFailed', $found_failed);
		$this->template_system->Assign('PermissionsOk', $ok_permissions);
		$this->template_system->Assign('PermissionsFailed', $failed_permissions);
		$this->template_system->Assign('AdminUrl', $this->admin_url);
		$this->template_system->ParseTemplate('final_report');
	}

}
