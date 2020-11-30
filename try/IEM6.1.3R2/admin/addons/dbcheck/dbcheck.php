<?php
/**
 * This file contains the 'dbcheck' addon which checks the integrity of the database.
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
 * This class handles the database check.
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 */
class Addons_dbcheck extends Interspire_Addons
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
			throw new Exception("Unable to install addon {$this->GetId()} " . $e->getMessage());
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
	public function GetEventListeners()
	{
		return
			array (
				array (
					'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS',
					'trigger_details' => array (
						'Addons_dbcheck',
						'GetTextMenuItems',
					),
					'trigger_file' => '{%IEM_ADDONS_PATH%}/dbcheck/dbcheck.php'
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
	static public function GetTextMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS $data)
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
			'text' => GetLang('Addon_dbcheck_Menu_Text'),
			'link' => $me->admin_url,
			'description' => GetLang('Addon_dbcheck_Menu_Description'),
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
		require_once(dirname(__FILE__) . '/api/dbcheck.php');
		$cd = new DatabaseCheck();

		$tables_to_check = $cd->getTableList();

		IEM::sessionSet($this->addon_id . '_StepNumber', 1);
		IEM::sessionSet($this->addon_id . '_TotalSteps', sizeof($tables_to_check));
		IEM::sessionSet($this->addon_id . '_TablesToCheck', $tables_to_check);
		IEM::sessionSet($this->addon_id . '_TablesStatus', array());

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);
		$this->template_system->ParseTemplate('default');
	}

	/**
	 * Admin_Action_ShowPopup
	 * This shows the popup window which includes the progress bar.
	 *
	 * @see Admin_Action_Default
	 *
	 * @return Void Just prints out the popup window / progress bar so you know what's going on.
	 */
	public function Admin_Action_ShowPopup()
	{
		$fix = $this->_getGETRequest('Fix', false);

		if ($fix) {
			$this->template_system->Assign('Fix', true, false);
		}

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);
		$this->template_system->Assign('TemplateUrl', $this->template_url, false);
		$this->template_system->Assign('ApplicationUrl', $this->application_url, false);
		$this->template_system->Assign('RandomValue', time());
		$this->template_system->ParseTemplate('progress_report');
	}

	/**
	 * Admin_Action_CheckDatabase
	 * This actually checks the database, storing results in the session.
	 *
	 * Redirects you to the 'Admin_Action_Finished' once done.
	 *
	 * @see Admin_Action_Default
	 * @see Admin_Action_ShowPopup
	 * @see Admin_Action_Finished
	 */
	public function Admin_Action_CheckDatabase()
	{
		require_once(dirname(__FILE__) . '/api/dbcheck.php');

		$cd = new DatabaseCheck();

		$fix = (bool)$this->_getGETRequest('Fix', false);

		$tables_to_check = IEM::sessionGet($this->addon_id . '_TablesToCheck');
		$tables_status = IEM::sessionGet($this->addon_id . '_TablesStatus');

		$total_steps = IEM::sessionGet($this->addon_id . '_TotalSteps');
		$step_number = IEM::sessionGet($this->addon_id . '_StepNumber');

		$current_table = array_shift($tables_to_check);

		IEM::sessionSet($this->addon_id . '_TablesToCheck', $tables_to_check);

		// array_shift returns null when it's empty - so we can check for that here to see if we're all done.
		if (is_null($current_table)) {
			echo "<script>\n";
			echo "self.parent.ProcessFinished();\n";
			echo "</script>\n";
			exit;
		}

		$msg = sprintf(GetLang('Addon_dbcheck_CheckingTable'), $current_table, $step_number, $total_steps);
		$percent = ceil(($step_number / $total_steps) * 100);

		echo "<script>\n";
		echo "self.parent.UpdateStatus('" . $msg . "', '" . $percent . "');\n";
		echo "</script>\n";
		flush();

		// Perform the actual table check.
		$tables_status[$current_table] = $cd->checkTable($current_table, $fix);
		IEM::sessionSet($this->addon_id . '_TablesStatus', $tables_status);
		IEM::sessionSet($this->addon_id . '_StepNumber', ($step_number + 1));

		// Throw back to this same page to continue the checking process.
		$fix_param = '';
		if ($fix) {
			$fix_param = '&Fix=true';
		}
		echo "<script>\n";
		echo "setTimeout(function() { window.location = '" . $this->admin_url . "&AJAX=1&Action=CheckDatabase" . $fix_param . "&r=" . time() . "'; }, 10);\n";
		echo "</script>\n";
		exit;
	}

	/**
	 * Admin_Action_Finished
	 * This is the final status report.
	 *
	 * @see Admin_Action_Default
	 * @see Admin_Action_ShowPopup
	 *
	 * @return Void Prints out the report, doesn't return anything.
	 */
	public function Admin_Action_Finished()
	{
		$repaired = (bool)$this->_getGETRequest('Repair', false);
		$tables_status = IEM::sessionGet($this->addon_id . '_TablesStatus');

		list($num_problems, $problem_tables, $problems) = $this->SummariseProblems($tables_status);

		if ($repaired) {
			FlashMessage(GetLang('Addon_dbcheck_Repaired'), SS_FLASH_MSG_SUCCESS);
		} elseif ($num_problems) {
			$error_msg = sprintf(GetLang('Addon_dbcheck_Problems'), $num_problems);
			FlashMessage($error_msg, SS_FLASH_MSG_ERROR);
		} else {
			FlashMessage(GetLang('Addon_dbcheck_NoProblems'), SS_FLASH_MSG_SUCCESS);
		}

		$report = self::GenerateReport($tables_status);
		IEM::sessionSet($this->addon_id . '_TablesReport', $report);
		IEM::sessionSet($this->addon_id . '_TablesToCheck', $problem_tables);
		IEM::sessionSet($this->addon_id . '_StepNumber', 1);
		IEM::sessionSet($this->addon_id . '_TotalSteps', count($problem_tables));

		$flash_messages = GetFlashMessages();

		$this->template_system->Assign('repaired', $repaired);
		$this->template_system->Assign('problems', $problems);
		$this->template_system->Assign('num_problems', $num_problems);
		$this->template_system->Assign('admin_url', $this->admin_url);
		$this->template_system->Assign('report', $report);
		$this->template_system->Assign('flash_messages', $flash_messages);
		$this->template_system->ParseTemplate('final_report');
	}

	/**
	 * Admin_Action_ShowReport
	 * This shows the detailed report window of the table errors.
	 *
	 * @return Void Does not return anything.
	 */
	public function Admin_Action_ShowReport()
	{
		$report = IEM::sessionGet($this->addon_id . '_TablesReport');
		$report = print_r($report, true);

		$this->template_system->Assign('report', $report);
		$this->template_system->Assign('ApplicationUrl', $this->application_url, false);
		$this->template_system->ParseTemplate('error_report');
	}

	/**
	 * GenerateReport
	 * Generates the detailed report used in the pop-up.
	 *
	 * @param Array $tables_status
	 *
	 * @return Array A list of problematic tables with a summary of the problems: [table_name => [corrupt => true], ...]
	 */
	private static function GenerateReport($tables_status)
	{
		$report = array();
		foreach ($tables_status as $name=>$status) {
			$report[$name] = array();
			if (!$status['present']) {
				$report[$name]['present'] = false;
				continue;
			}
			if ($status['corrupt']) {
				$report[$name]['corrupt'] = true;
			}
			if (!empty($status['missing_columns'])) {
				$report[$name]['missing_columns'] = $status['missing_columns'];
			}
			if (!empty($status['missing_indexes'])) {
				$report[$name]['missing_indexes'] = $status['missing_indexes'];
			}
			if (empty($report[$name])) {
				unset($report[$name]);
			}
		}
		return $report;
	}

	/**
	 * SummariseProblems
	 * Provides a report on the number and type of problems of the tables.
	 *
	 * @see Admin_Action_Finished
	 *
	 * @param array $tables The raw table status.
	 *
	 * @return array An array with three elements: [number of problems, list of tables that have problems, summary of problems].
	 */
	private static function SummariseProblems($tables)
	{
		$problem_tables = array();
		$problems = array(
			'present' => 0,
			'corrupt' => 0,
			'missing_columns' => 0,
			'missing_indexes' => 0,
		);
		foreach ($tables as $name=>$status) {
			if (!$status['present']) {
				$problems['present']++;
				$problem_tables[] = $name;
			}
			if ($status['corrupt']) {
				$problems['corrupt']++;
				$problem_tables[] = $name;
			}
			if (!empty($status['missing_columns'])) {
				$problems['missing_columns'] += count($status['missing_columns']);
				$problem_tables[] = $name;
			}
			if (!empty($status['missing_indexes'])) {
				$problems['missing_indexes'] += count($status['missing_indexes']);
				$problem_tables[] = $name;
			}
		}
		$langvar_map = array(
			'present' => 'NotPresent',
			'corrupt' => 'Corrupt',
			'missing_columns' => 'MissingColumns',
			'missing_indexes' => 'MissingIndexes',
		);
		$problem_summary = array();
		foreach ($langvar_map as $type=>$var) {
			$langstr = sprintf(GetLang("Addon_dbcheck_Problem_{$var}"), $problems[$type]);
			$problem_summary[$type] = array('num' => $problems[$type], 'text' => $langstr);
		}
		$num_problems = array_sum($problems);
		return array($num_problems, $problem_tables, $problem_summary);
	}

}
