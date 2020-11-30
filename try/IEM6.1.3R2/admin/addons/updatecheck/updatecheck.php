<?php
/**
 * This file contains the 'updatecheck' addon which checks online for a new version of the application.
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
 * This class handles the update check.
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 */
class Addons_updatecheck extends Interspire_Addons
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
						'Addons_updatecheck',
						'GetTextMenuItems',
					),
					'trigger_file' => '{%IEM_ADDONS_PATH%}/updatecheck/updatecheck.php'
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
			'text' => GetLang('Addon_updatecheck_Menu_Text'),
			// FIXME: This is a bit of hackery that is perhaps too clever.
			'link' => "#\" onclick=\"tb_show('" . LNG_Addon_updatecheck_Check . "', 'index.php?Page=Addons&Addon=updatecheck&Ajax=true&keepThis=true&TB_iframe=true&height=80&width=300', '');",
			'description' => GetLang('Addon_updatecheck_Menu_Description'),
		);
		unset($me);
	}

	/**
	 * Admin_Action_Default
	 * This is the first page shown when you view the addon.
	 *
	 * @return Void Does not return anything.
	 */
	public function Admin_Action_Default()
	{
		$this->template_system->ParseTemplate('default');
	}

	/**
	 * Admin_Action_Check
	 *
	 *
	 * @return Void Does not return anything.
	 */
	public function Admin_Action_Check()
	{
		$this->template_system->ParseTemplate('check');
	}

	/**
	 * Admin_Action_Report
	 *
	 * @return Void Does not return anything.
	 */
	public function Admin_Action_Report()
	{
		$version = $this->GetLatestVersion();
		if (version_compare($version, IEM::VERSION, '>')) {
			printf(GetLang('Addon_updatecheck_YesNew'), IEM::VERSION, $version);
			return;
		}
		printf(GetLang('Addon_updatecheck_NoNew'), IEM::VERSION);
	}

	/**
	 * GetLatestVersion
	 * Obtains the latest version of the application from the .version file.
	 *
	 * @return Mixed The version string cached in the file, or false if there was a problem.
	 */
	private function GetLatestVersion()
	{
		$file = IEM_STORAGE_PATH . '/.version';
		if (!is_file($file) || !is_readable($file)) {
			return false;
		}
		$data = file_get_contents($file);
		if (!preg_match('/latest=([\d\.]+)/', $data, $matches)) {
			return false;
		}
		return $matches[1];
	}

}
