<?php

if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

require_once(dirname(__FILE__) . '/../../index.php');
require_once(dirname(__FILE__) . '/../../functions/amcharts/amcharts.php');
require_once(dirname(__FILE__) . '/../../functions/sendstudio_functions.php');
require_once(dirname(__FILE__) . '/api/splittest_stats.php');
require_once(dirname(__FILE__) . '/splittest_stats.php');

/**
 * This class handles the collection of stats used to create the bar charts
 * and populate the print templates
 *
 */
class PrintStats extends Addons_splittest_Stats
{

	/**
	 * __construct
	 *
	 *
	 * @uses Interspire_Addons::__construct
	 * @return Void Doesn't return anything.
	 */
	public function __construct()
	{
		parent::__construct();
	}


	/**
	 * printPage
	 *
	 * @return Void Doesn't return anything.
	 */
	public function printPage()
	{
		$user = GetUser();
		$split_api = $this->GetApi('Splittest'); // for permission checks

		$subaction = $this->_getGetRequest('subaction', 'print');
		$perpage = $this->_getGetRequest('PerPageDisplay', null);
		$jobids = $this->_getGETRequest('jobids', null);
		$listids =  $this->_getGETRequest('split_statids', null);

		$jobids = explode(",", $jobids);
		$listids = explode(",", $listids);

		SendStudio_Functions::LoadLanguageFile('Stats');

		if (!SplitTest_API::OwnsJobs($user->Get('userid'), $jobids) && !$user->Admin()) {
			FlashMessage(GetLang('NoAccess'), SS_FLASH_MSG_ERROR, $this->base_url);
			return;
		}

		// Get some setup parameters for the API
		$sortdetails = array ('sort' => 'splitname', 'direction' => 'asc');
		$page_number = 0;
		$perpage = 20;
		$displayAll = false;	// just show a single splitest campaign send. If you want every campaign send for a split test set to true

		$dateFromat = self::getDateFormat();
		$statitics = array();
		$jobid = 0;

		for ($i=0; $i<count($jobids); $i++) {
			$stats = array();
			$stats_api = new Splittest_Stats_API();
			$jobid = $jobids[$i];
			$splitid = $listids[$i];

			// get the array of stats data
			$stats = $stats_api->GetStats(array($splitid), $sortdetails, false, $page_number, $perpage, $displayAll, $jobid);

			foreach ($stats as $stats_id => $stats_details) {
				$stats[$stats_id]['splitname'] = htmlspecialchars($stats_details['splitname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
				$stats[$stats_id]['campaign_names'] = htmlspecialchars($stats_details['campaign_names'], ENT_QUOTES, SENDSTUDIO_CHARSET);
				$stats[$stats_id]['list_names'] = htmlspecialchars($stats_details['list_names'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}

			// A Splittest can be sent multiple times hence we might have multiple campaign record sets here
			while (list($id, $data) = each($stats)) {
				$charts = $this->generateCharts($data['splitname'], $data['campaigns'], $subaction);
				foreach ($charts as $type=>$data) {
					$stats[$id][$type] = $data;
				}
			}
			$statistics[] = $stats;
		}
		$template = GetTemplateSystem(dirname(__FILE__) . '/templates');
		$template->Assign('DateFormat', $dateFromat);
		$template->Assign('statsData', $statistics);
		$template->Assign('subaction', $subaction);
		$options = $this->_getGETRequest('options', null);
		for ($i=0; $i<count($options); $i++) {
			$template->Assign($options[$i], $options[$i]);
		}
		$template->ParseTemplate('Stats_Summary_Splittest');
	}

}

$PrintStats = new PrintStats();
$PrintStats->printPage();
