<?php
/**
* This file prodcues the popup display page allow the user to choose which Statistics to preview / print
*
* @package Addons
* @subpackage Splittest
*/

if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

/**
 * import Sendstudio functions (until these become more easily accessible from Addons context)
 */
require_once(dirname(__FILE__) . '/../../index.php');
require_once(dirname(__FILE__) . '/../../functions/sendstudio_functions.php');
require_once(dirname(__FILE__) . '/api/splittest.php');

/**
 * PrintStats
 * Displays the print options when printing a Split Test.
 */
class PrintStats extends Addons_splittest
{
	/**
	 * @var String The addon ID.
	 */
	protected $addon_id = 'splittest';

	/**
	 * Constructor
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * showOptionsPage
	 * Displays the print option page.
	 *
	 * @return Void Does not return anything.
	 */
	function showOptionsPage()
	{
		$statstype = $this->_getGETRequest('statstype', null);
		if ($statstype == null) {
			return false;
			exit();
		}

		$path = $this->_getGETRequest('path', '');

		SendStudio_Functions::LoadLanguageFile('stats');
		$stats_api = $this->GetApi('Splittest_Stats');

		$bg_color = 'white';
		$print_options = '<input type="hidden" name="statstype" value="' . htmlentities($statstype, ENT_QUOTES, SENDSTUDIO_CHARSET) . '" />';

		switch ($statstype) {
			case 'splittest' :
				$splitStatIds = $this->_getGETRequest('statids', null);
				$jobIds = $this->_getGETRequest('jobids', null);
				$splitStatIds = SplitTest_API::FilterIntSet($splitStatIds);
				$jobIds = SplitTest_API::FilterIntSet($jobIds);
				$print_options .= '<input type="hidden" name="split_statids" value="' . implode(',', $splitStatIds) . '" />';
				$print_options .= '<input type="hidden" name="jobids" value="' . implode(',', $jobIds) . '" />';

				$options = array (
						'snapshot' => GetLang('Addon_splittest_Menu_ViewStats'),
						'open' => GetLang('Addon_splittest_open_summary'),
						'click' => GetLang('Addon_splittest_linkclick_summary'),
						'bounce' => GetLang('Addon_splittest_bounce_summary'),
						'unsubscribe' => GetLang('Addon_splittest_unsubscribe_summary')
					);

				foreach ($options as $key => $val) {
					$bg_color = ($bg_color == 'white') ? '#EDECEC' : 'white';
					$print_options .= '<div style="background-color: ' . $bg_color . '; padding: 5px; margin-bottom: 5px;">';
					$print_options .= '<input id="print_' . $key . '" type="checkbox" name="options[]" value="' . $key . '" checked="checked" style="margin:0;"/>
						<label for="print_' . $key . '">' . $val . '</label>' . "\n";
					$print_options .= '</div>' . "\n";
				}

			break;
		}

		$this->template_system->assign('path', $path);
		$this->template_system->Assign('title', GetLang('Addon_splittest_PrintSplitTestStatistics'));
		$this->template_system->Assign('print_options', $print_options);
		$this->template_system->ParseTemplate('print_stats_options');
	}
}

$PrintStats = new PrintStats();
$PrintStats->showOptionsPage();
