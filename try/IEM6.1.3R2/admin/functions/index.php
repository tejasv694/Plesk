<?php
/**
 * This file has the first welcome page functions, including quickstats.
 *
 * @version     $Id: index.php,v 1.28 2008/02/08 05:47:02 chris Exp $
 * @author Chris <chris@interspire.com>
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */

/**
 * Include the base sendstudio functions.
 */
require_once dirname(__FILE__) . '/sendstudio_functions.php';

/**
 * Class for the welcome page. Includes quickstats and so on.
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */
class Index extends SendStudio_Functions
{
	/**
	 * Constructor
	 * Loads the language file and Getting Started steps
	 *
	 * @see LoadLanguageFile
	 *
	 * @return Void Doesn't return anything.
	 */
	function Index()
	{
		$user = GetUser();
		$this->LoadLanguageFile();
		$this->LoadLanguageFile('Stats');
		$dc_tmp_langfile = dirname(dirname(__FILE__)).'/addons/splittest/language/language.php';
		require_once($dc_tmp_langfile);
	}

	/**
	 * Process
	 * Sets up the main page. Checks access levels and so on before printing out each option. Once the areas are set up, it simply calls the parent process function to do everything.
	 *
	 * @see GetUser
	 * @see User_API::HasAccess
	 * @see SendStudio_Functions::Process
	 *
	 * @return Void Prints out the main page, doesn't return anything.
	 */
	function Process()
	{
		$user = GetUser();

		$action = '';
		if (isset($_GET['Action'])) {
			$action = strtolower($_GET['Action']);
		}

		$print_header = true;

		/*
		 * If it's an ajax action, don't print the header.
		 * This also affects the footer at the bottom.
		 */
		$ajax_actions = array('switch', 'subscribergraph', 'cleanupexport', 'hidethis', 'getcampaignlist', 'campaignview', 'getcampaigndropdown', 'getcampaignchart', 'getrecentlists', 'getpredefinedlinklist');
		if (in_array($action, $ajax_actions)) {
			$print_header = false;
		}

		if ($print_header) {
			$this->PrintHeader();
		}

		switch ($action) {
			case 'switch':
				if (isset($_POST['To']) && strtolower($_POST['To']) == 'quicklinks') {
					$user->SetSettings('StartLinks', 'quicklinks');
					break;
				}
				$user->SetSettings('StartLinks', 'gettingstarted');
				break;

			case 'campaignview':
				if (isset($_POST['To'])) {
					switch (strtolower($_POST['To'])) {
						case 'campaignshowschedule':
							$user->SetSettings('CampaignLinks', 'campaignshowschedule');
							break;
						case 'campaignshowsent':
							$user->SetSettings('CampaignLinks', 'campaignshowsent');
							break;
						case 'campaignshowarchive':
							$user->SetSettings('CampaignLinks', 'campaignshowarchive');
							break;
						default:
							$user->SetSettings('CampaignLinks', 'campaignshowall');
					}
				}
				break;

			case 'hidethis':
				if (isset($_POST['To']) && strtolower($_POST['To']) == 'none') {
					$user->SetSettings('ShowThis', 'none');
					break;
				}
				$user->SetSettings('ShowThis', 'block');
				break;

			case 'getcampaigndropdown':
				$this->PrintCampaignsDropdown();
				break;

			case 'getrecentlists':
				$this->PrintRecentLists();
				break;

			case 'getcampaignchart':
				$statsapi = $this->GetApi('Stats');
				if (isset($_POST['StatId'])) {
					$this->PrintCampaignsChart($_POST['StatId']);
					$user->SetSettings('CampaignChart', $_POST['StatId']);
					break;
				}

				break;
			case 'getcampaignlist':
				if (isset($_POST['To'])) {
					if ($_POST['To'] == 'campaignshowschedule') {
						$this->PrintJobs();
					} else {
						$this->PrintCampaign($_POST['To']);
					}
					break;
				}
				break;

			case 'subscribergraph':
				$this->PrintGraph();
				break;

			case 'cleanupexport':
				$this->CleanupExportFile();
				break;

			case 'getpredefinedlinklist':
				$this->GetPredefinedLinkList();
				break;

			default:
				$db = IEM::getDatabase();
				$GLOBALS['Message'] = GetFlashMessages();

				if ($user->GetSettings('StartLinks') == 'quicklinks') {
					$GLOBALS['HomeGettingStartedDisplay'] = 'display:none;';
					$GLOBALS['StartTitle'] = GetLang('IWouldLikeTo');
					$GLOBALS['SwitchLink'] = GetLang('SwitchtoGettingStartedLinks');
				} else {
					$GLOBALS['HomeQuickLinksDisplay'] = 'display:none;';
					$GLOBALS['StartTitle'] = GetLang('GettingStarted_Header');
					$GLOBALS['SwitchLink'] = GetLang('SwitchtoQuickLinks');
				}

				$GLOBALS['HideThisDisplay'] = 'display:block;';
				$GLOBALS['HideThisText'] = GetLang('GettingStarted_HideThis');
				if ($user->GetSettings('ShowThis') == 'none') {
					$GLOBALS['HideThisDisplay'] = 'display:none;';
					$GLOBALS['HideThisText'] = GetLang('GettingStarted_ShowMore');
				}

				$GLOBALS['CampaignSelectedLink'] = $user->GetSettings('CampaignLinks');
				if (!$GLOBALS['CampaignSelectedLink']) {
					$GLOBALS['CampaignSelectedLink'] = 'campaignshowall';
				}

				$GLOBALS['CampaignSelectedChart'] = $user->GetSettings('CampaignChart');
				if (!$GLOBALS['CampaignSelectedChart']) {
					$GLOBALS['CampaignSelectedChart'] = 0;
				}

				$GLOBALS['VersionCheckInfo'] = $this->_CheckVersion();

				$GLOBALS['DisplayBox'] = GetDisplayInfo($this, false, null);

				$this->PrintSystemMessage();

				$GLOBALS['DisplayListButton'] = 'none';
				if ($this->PrintRecentLists(true)) {
					$GLOBALS['DisplayListButton'] = 'block';
				}

				$tpl = GetTemplateSystem();
				$tpl->Assign('showintrovideo', !!constant('SHOW_INTRO_VIDEO'));
				$tpl->ParseTemplate('index');
		}

		if ($print_header) {
			$this->PrintFooter();
		}
	}

	/**
	 * PrintInterspireRSS
	 * Print the latest info from an rss feed.
	 * This also caches the feed for 24 hours so it's not going to constantly cause a remote hit
	 * and should also mean subsequent views are a little faster.
	 * If a url can't be fetched, the box on the front appears empty.
	 *
	 * @param String $rss_url The RSS URL to read in and process.
	 * @param Int $number_to_display The number of entries to display from the rss feed.
	 *
	 * @return Void Doesn't return anything, just prints the top 5 entries from the feed.
	 */
	function PrintRSS($rss_url=false, $number_to_display=5)
	{
		if (!$rss_url) {
			return;
		}

		// make sure number_to_display is >= 1.
		if ((int)$number_to_display < 1) {
			$number_to_display = 5;
		}

		$check_version = false;
		$cache_file = TEMP_DIRECTORY . '/' . str_replace(array('/', '\\', '.', ';', '"', "'"), '', base64_encode($rss_url)).'.xml';
		if (!is_file($cache_file)) {
			$check_version = true;
		}

		if (is_file($cache_file)) {
			$last_hit = filemtime($cache_file);
			if ($last_hit === false) {
				$check_version = true;
			}
			if ($last_hit < (time() - 86400)) {
				$check_version = true;
			}
			// if the file is empty..
			if (filesize($cache_file) == 0) {
				$check_version = true;
			}
		}

		if ($check_version) {
			list($content, $error) = $this->GetPageContents($rss_url);
			if ($content !== false) {
				if (is_writable(TEMP_DIRECTORY)) {
					$fp = fopen($cache_file, 'w');
					fputs($fp, $content);
					fclose($fp);
				}
			}
		} else {
			$content = file_get_contents($cache_file);
		}

		if ($content !== false) {
			$items = $this->FetchXMLNode('item',$content,true);
			$i = 0;
			foreach ($items as $item) {
				$url = $this->FetchXMLNode('link',$item);
				$title = $this->FetchXMLNode('title',$item);

				preg_match('%<\!\[cdata\[(.*?)\]\]>%is', $title, $matches);
				if (isset($matches[1])) {
					echo '<li><a href="' . $url . '" target="_blank" title="' . htmlspecialchars($matches[1], ENT_QUOTES, SENDSTUDIO_CHARSET) . '">' . $this->TruncateInMiddle($matches[1]) . '</a></li>';
				} else {
					preg_match('/http:\/\/www\.anonym\.com\/?http:\/\/www\.viewkb\.com\/questions\/(\d*)\//is', $url, $matches);
					if (count($matches) == 2) {
						echo '<li><a href="#" onClick="LaunchHelp(\''.IEM::enableInfoTipsGet().'\',\'' . $matches[1] . '\');" title="' . htmlspecialchars($title, ENT_QUOTES, SENDSTUDIO_CHARSET) . '">' . $this->TruncateInMiddle($title) . '</a></li>';
					} else {
						echo '<li><a href="' . $url . '" target="_blank" title="' . htmlspecialchars($title, ENT_QUOTES, SENDSTUDIO_CHARSET) . '">' . $this->TruncateInMiddle($title) . '</a></li>';
					}
				}
				$i++;
				if ($i >= $number_to_display) {
					break;
				}
			}
		}
	}

	/**
	 * _CheckVersion
	 * Shows the "you are running the latest version" .. or not ..
	 * box on the front page.
	 *
	 * This allows an IEM user to see if there's an update (either bugfix or new feature version) available.
	 * A non-admin user does not see this box.
	 * Nor does it show up if the 'SENDSTUDIO_WHITE_LABEL' variable is set to true
	 *
	 * @see init.php
	 *
	 * @return String Returns either an empty string (for a non-admin user) or "you are (or are not) running the latest version" box.
	 */
	function _CheckVersion()
	{
		$user = GetUser();
		if (!$user->Admin() || SENDSTUDIO_WHITE_LABEL) {
			return '';
		}
		$template = "<script>
						var latest_version = '';
						var feature_version = '';
						var latest_critical = 0;
						var feature_critical = 0;
					</script>";
		$template .= '<script src="http://www.version-check.net/version.js?p=9"></script>';
		$template .= '<script>
						$.ajax({
							type: "post",
							url: "remote.php",
							data: "what=save_version&latest=" + latest_version + "&feature=" + feature_version + "&latest_critical=" + latest_critical + "&feature_critical=" + feature_critical
						});
					</script>';
		return $template;
	}

	/**
	 * CleanupExportFile
	 * Removes the export file recorded in the user's session.
	 *
	 * @return Void Does not return anything. Sets Flash Messages.
	 */
	function CleanupExportFile()
	{
		$exportinfo = IEM::sessionGet('ExportInfo');

		if (!empty($exportinfo)) {
			$api = $this->GetApi('Jobs');

			if (isset($exportinfo['ExportQueue'])) {
				$queueid = $exportinfo['ExportQueue'];
				if ($queueid && is_array($queueid)) {
					foreach ($queueid as $id) {
						$api->ClearQueue($id['queueid'], 'export');
					}
				}
			}

			$exportfile = $exportinfo['ExportFile'];

			if (is_file(TEMP_DIRECTORY . '/' . $exportfile)) {
				if (@unlink(TEMP_DIRECTORY . '/' . $exportfile)) {
					IEM::sessionRemove('ExportInfo');
					FlashMessage(GetLang('ExportFileDeleted'), SS_FLASH_MSG_SUCCESS, 'index.php');
					return;
				}
			}
		}
		FlashMessage(GetLang('ExportFileNotDeleted'), SS_FLASH_MSG_ERROR, 'index.php');
	}

	/**
	 * Prints out the System Message box above quick-stats.
	 *
	 * @return String Returns the panel to put in. This is only done if there is a system message to print out.
	 */
	function PrintSystemMessage()
	{
		if (SENDSTUDIO_SYSTEM_MESSAGE && trim(SENDSTUDIO_SYSTEM_MESSAGE) != '') {
			$GLOBALS['System_Message'] = SENDSTUDIO_SYSTEM_MESSAGE;
			$GLOBALS['SystemMessage'] = $this->ParseTemplate('Index_System_Message', true, false);
		} else {
			$GLOBALS['SystemMessage'] = '';
		}
	}

	/**
	 * FetchXMLNode
	 * Get XML node from xml data
	 *
	 * @param String $node The node name
	 * @param String $xml The XML data
	 * @param Boolean $all True to grab all nodes, false to grab only the first
	 *
	 * @see GetContent
	 *
	 * @return Array Returns an array of nodes
	 */
	function FetchXmlNode($node='', $xml='', $all=false)
	{
		if ($node == '') {
			return false;
		}

		if ($all) {
			preg_match_all('%<(' . $node . '[^>]*)>(.*?)</' . $node . '>%is', $xml, $matches);
		} else {
			preg_match('%<(' . $node . '[^>]*)>(.*?)</' . $node . '>%is', $xml, $matches);
		}

		if (!isset($matches[2])) {
			return false;
		}

		return $matches[2];
	}

	/**
	 * PrintGraph
	 * Prints out the graph on the front page
	 * Which shows contact activity for the last 7 days including:
	 * - signups (confirmed/unconfirmed)
	 * - bounces
	 * - unsubscribes
	 * - forwards
	 *
	 * @return Void Doesn't return anything, this just prints out the contact activity graph.
	 */
	function PrintGraph()
	{
		$user = GetUser();
		$lists = $user->GetLists();
		$listids = array_keys($lists);
		$stats_api = $this->GetApi('Stats');

		$idx_calendar = array('DateType' => 'last7days');
		IEM::sessionSet('IndexCalendar', $idx_calendar);

		$rightnow = AdjustTime(0, true);

		$today = AdjustTime(array(0, 0, 0, date('m'), date('d'), date('Y')), true, null, true);

		$time = AdjustTime(array(0, 0, 0, date('m'), date('d') - 6, date('Y')), true, null, true);

		$query = ' AND (%%TABLE%% >= ' . $time . ')';

		$restrictions = array();
		$restrictions['subscribes'] = str_replace('%%TABLE%%', 'subscribedate', $query);
		$restrictions['unsubscribes'] = str_replace('%%TABLE%%', 'unsubscribetime', $query);
		$restrictions['bounces'] = str_replace('%%TABLE%%', 'bouncetime', $query);
		$restrictions['forwards'] = str_replace('%%TABLE%%', 'forwardtime', $query);

		$data = $stats_api->GetSubscriberGraphData('last7days', $restrictions, $listids);

		require_once(dirname(__FILE__) . '/amcharts/amcharts.php');

		IEM::sessionSet('IndexSubscriberGraphData', $data);

		$data_url = 'stats_chart.php?graph=' . urlencode(strtolower('subscribersummary')) . '&Area='.urlencode(strtolower('list')) . '&statid=0&i=1&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		$chart = InsertChart('column', $data_url);
		echo $chart;
	}

	function PrintCampaignsChart($statId) {
		$user = GetUser();
		$statsapi = $this->GetApi('Stats');
		$statId = ($user->HasAccess('Statistics') && $user->HasAccess('statistics', 'newsletter'))?$statId:0;

		if (!$statId || $statsapi->IsHidden($statId)) {
			$this->PrintGraph();
		} else {
			$this->PrintNewsletterStatsChart($statId);
		}
	}

	function PrintCampaign ($listOptions) {
		$user = GetUser();
		$noCampaignLang = 'GettingStarted_NoCampaign';

		// Check the user access
		$access = $user->HasAccess('newsletters', 'manage');
		if (!$access) {
			$tpl = GetTemplateSystem();
			$page['message']=GetLang('NoAccess');
			$tpl->Assign('page', $page);
			echo $tpl->ParseTemplate('index_campaign_noitem', true);
			return;
		}

		$start = 0;
		$perpage = 5;
		$newsletterapi = $this->GetApi('Newsletters');
		$jobapi = $this->GetApi('Jobs');

		$newsletterowner = ($user->Admin() || $user->AdminType() == 'n') ? 0 : $user->userid;
		$NumberOfNewsletters = $newsletterapi->GetNewsletters($newsletterowner, null, true);

		$mynewsletters = $newsletterapi->GetNewsletters($newsletterowner, null, false, $start, $perpage, true);
		$NumberOfNewsletters = 0;

		foreach ($mynewsletters as $pos => $newsletterdetails) {
			$display = false;
			$send_inprogress = false;

			$newsletterdetailsPage = array();
			$newsletterdetailsPage['newsletterid'] = $newsletterdetails['newsletterid'];
			$newsletterdetailsPage['namelong'] = htmlspecialchars($newsletterdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$newsletterdetailsPage['name'] = htmlspecialchars($this->TruncateName($newsletterdetails['name'], 30), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$newsletterdetailsPage['archive'] = htmlspecialchars($newsletterdetails['archive'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$newsletterdetailsPage['subject'] = htmlspecialchars($this->TruncateName($newsletterdetails['subject'], 30), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$newsletterdetailsPage['createdate'] = $this->PrintDate($newsletterdetails['createdate']);
			$newsletterdetailsPage['action'] = 'Edit';
			$newsletterdetailsPage['name_link_param'] = '';
			// Check if the user has access to edit the articles
			$job = false;
			if ($newsletterdetails['jobid'] > 0) {
				$job = $jobapi->LoadJob($newsletterdetails['jobid']);
			}
			if ($user->HasAccess('Newsletters', 'Send')) {
				if ($newsletterdetails['active']) {
					if (!empty($job) && $job && $job['jobstatus'] == 'i') {
						$send_inprogress = true;
					}
				}
			}

			if ($user->HasAccess('Newsletters', 'Edit')) {
				if ($send_inprogress) {
					$newsletterdetailsPage['action'] = 'None';
				}
			} else {
				$newsletterdetailsPage['action'] = 'None';
			}

			switch ($listOptions) {
				case 'campaignshowall':
					$display = true;
				break;
				case 'campaignshowsent':
					if ($newsletterdetails['starttime'] != 0) {
						$display = true;
					}
				break;
				case 'campaignshowarchive':
					if ($newsletterdetails['archive'] > 0) {
						$display = true;
					}
				break;
			}

			if ($display) {
				$NumberOfNewsletters++;
				$tpl = GetTemplateSystem();
				$tpl->Assign('newsletterdetailsPage', $newsletterdetailsPage);
				echo $tpl->ParseTemplate('index_campaign_item', true);
			}
		}
		// nothing found
		$noCampaignLang .= ucfirst($listOptions);

		if ($NumberOfNewsletters < 1) {
			$tpl = GetTemplateSystem();
			$page['message']=GetLang($noCampaignLang);
			$tpl->Assign('page', $page);
			echo $tpl->ParseTemplate('index_campaign_noitem', true);
			return;
		} else {
			$tpl = GetTemplateSystem();
			echo $tpl->ParseTemplate('index_campaign_viewall_butt', true);
			return;
		}

	}

	function PrintJobs () {
		$user = GetUser();

		// Check the user access
		$access = $user->HasAccess('newsletters', 'Send');
		if (!$access) {
			$tpl = GetTemplateSystem();
			$page['message']=GetLang('NoAccess');
			$tpl->Assign('page', $page);
			echo $tpl->ParseTemplate('index_campaign_noitem', true);
			return;
		}

		if (!SENDSTUDIO_CRON_ENABLED) {
			$tpl = GetTemplateSystem();
			$page['message'] = GetLang('CronNotEnabled');
			$tpl->Assign('page', $page);
			echo $tpl->ParseTemplate('index_campaign_noitem', true);
			return;
		}

		$start = 0;
		$perpage = 5;
		$jobsApi = $this->GetApi('Jobs');
		$lists = $user->GetLists();
		$listids = array_keys($lists);
		$tpl = GetTemplateSystem();

		$include_unapproved = false;
		if ($user->Admin()) {
			$include_unapproved = true;
		}

		$numJobs = $jobsApi->GetJobList('send', 'newsletter', $listids, true, 0, 0, $include_unapproved, false);

		// Nothing found
		if ($numJobs < 1) {
			$page['message']=GetLang('GettingStarted_NoScheduledCampaign');
			$tpl->Assign('page', $page);
			echo $tpl->ParseTemplate('index_campaign_noitem', true);
			return;
		}

		$jobs = $jobsApi->GetJobList('send', 'newsletter', $listids, false, $start, $perpage, $include_unapproved, false);

		foreach ($jobs as $p => $newsletterdetails) {
			$newsletterdetailsPage = array();
			$newsletterdetailsPage['newsletterid'] = $newsletterdetails['newsletterid'];

			$newsletterdetailsPage['namelong'] = $GLOBALS['NewsletterName'] = htmlspecialchars($newsletterdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$newsletterdetailsPage['name'] = $GLOBALS['NewsletterName'] = htmlspecialchars($this->TruncateName($newsletterdetails['name'],30), ENT_QUOTES, SENDSTUDIO_CHARSET);

			$newsletterdetailsPage['subject'] = '';
			if ($newsletterdetails['subject'] !== null) {
				$newsletterdetailsPage['subject'] = htmlspecialchars($this->TruncateName($newsletterdetails['subject'],30), ENT_QUOTES, SENDSTUDIO_CHARSET);
			}
			$newsletterdetailsPage['createdate'] = $this->PrintTime($newsletterdetails['jobtime'], true);
			$newsletterdetailsPage['action'] = 'View';
			$newsletterdetailsPage['name_link_param'] = ' target="_BLANK" ';
			$tpl->Assign('newsletterdetailsPage', $newsletterdetailsPage);
			echo $tpl->ParseTemplate('index_campaign_item', true);
		}
		$tpl = GetTemplateSystem();
		echo $tpl->ParseTemplate('index_campaign_viewall_butt', true);
	}

	function PrintCampaignsDropdown() {
		$user = GetUser();
		$statsapi = $this->GetApi('Stats');
		$mystatsSelected['selected'] = 0;
		$mystats = array();
		$perpage = 15;
		$start = 0;

		if ($user->HasAccess('Statistics') && $user->HasAccess('statistics', 'newsletter')) {
			$lists = $user->GetLists();
			$listids = array_keys($lists);

			$mystats = $statsapi->GetNewsletterStats($listids, null, false, $start, $perpage);

			foreach ($mystats as $k=>$v) {
				if (isset($_POST['SelectedCampaignChart']) && $mystats[$k]['statid'] == $_POST['SelectedCampaignChart']) {
					$mystatsSelected['selected'] = $_POST['SelectedCampaignChart'];
				}
				$mystats[$k]['newslettername'] = htmlspecialchars($this->TruncateName($mystats[$k]['newslettername'], 30), ENT_QUOTES, SENDSTUDIO_CHARSET);
				$mystats[$k]['starttime'] = $this->PrintDate($mystats[$k]['starttime']);
				$mystats[$k]['totalrecipients'] = $mystats[$k]['htmlrecipients'] + $mystats[$k]['textrecipients'] + $mystats[$k]['multipartrecipients'];

			}
			$mystatsSelected['selected'] = $_POST['SelectedCampaignChart'];
		}

		$tpl = GetTemplateSystem();
		$tpl->Assign('mystats', $mystats);
		$tpl->Assign('mystatsSelected', $mystatsSelected);
		echo $tpl->ParseTemplate('index_campaign_options', true);
		return $mystatsSelected['selected'];
	}

	function PrintNewsletterStatsChart($statid=0)
	{
		$statsapi = $this->GetApi('Stats');

		include(dirname(__FILE__) . '/amcharts/amcharts.php');

		$perpage = 1;
		$summary = $statsapi->GetNewsletterSummary($statid, true, $perpage);

		$sent_size = $summary['sendsize'];

		$total_bounces = $summary['bouncecount_unknown'] + $summary['bouncecount_hard'] + $summary['bouncecount_soft'];

		// now for the opens page.
		// by default this is for all opens, not unique opens.
		$only_unique = false;
		if (isset($_GET['Unique'])) {
			$only_unique = true;
		}

		$unopened = $sent_size - $summary['emailopens_unique'] - $total_bounces;
		if ($unopened < 0) {
			$unopened = 0;
		}

		$data_url = 'stats_chart.php?Opens='. $summary['emailopens_unique'] . '&Unopened=' . $unopened . '&Bounced=' . $total_bounces . '&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		// Newsletter Summary Chart
		$chart = InsertChart('pie', $data_url, array('graph_title' => GetLang("NewsletterSummaryChart"), 'y_position' => '150', 'x_position' => '300', 'legend_x_position' => '0', 'legend_y_position' => '230', 'title_align' => 'left'));

		echo $chart;
	}

	function PrintRecentLists($countOnly = false) {

		$user = GetUser();
		$perpage = 5;
		$start = 0;
		$DisplayPage = 1;
		$sortinfo['SortBy'] = 'date';
		$sortinfo['Direction'] = 'DESC';

		$all_lists = $user->GetLists();
		$check_lists = array_keys($all_lists);

		$listapi = $this->GetApi('Lists');

		$NumberOfLists = count($check_lists);

		if ($countOnly) {
			return $NumberOfLists;
		}

		// If we're a list admin, no point checking the lists - we have access to everything.
		if ($user->ListAdmin()) {
			$check_lists = null;
		}

		$mylists = $listapi->GetLists($check_lists, $sortinfo, false, $start, $perpage);

		if ($NumberOfLists == 0) {
			$tpl = GetTemplateSystem();
			$page['message']=GetLang('GettingStarted_NoContactList');
			$tpl->Assign('page', $page);
			echo $tpl->ParseTemplate('index_campaign_noitem', true);
			exit;
		}

		$this->SetupPaging($NumberOfLists, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging';
		$paging = $this->ParseTemplate('Paging', true, false);

		foreach ($mylists as $pos => $listinfo) {
			$list['Name'] = htmlspecialchars($listinfo['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$list['Created'] = $this->PrintDate($listinfo['createdate']);

			$list['SubscriberCount'] = $this->FormatNumber($listinfo['subscribecount']);

			$list['ListID'] = $listinfo['listid'];
			$list['NameShort'] = $this->TruncateName($list['Name'], 30);
			if ($user->HasAccess('Lists', 'Edit', $listinfo['listid'])) {
				$editList = 'index.php?Page=Lists&Action=Edit&id='.$list['ListID'];
				$manageContacts = $list['SubscriberCount'].' '.GetLang('GettingStarted_Contacts');
				if ($list['SubscriberCount'] > 0) {
					$manageContacts = '<a href="index.php?Page=Subscribers&Action=Manage&Lists[]='.$list['ListID'].'">'.$list['SubscriberCount'].' '.GetLang('GettingStarted_Contacts').'</a>';
				}
				echo '<li><a class="ListLink" href="' . $editList . '" title="' . GetLang('Edit'). ' - ' . $list['Name'] . '">' . $list['NameShort'] . '</a> - ('.$manageContacts.')</li>';
			} else {
				echo '<li>' . $list['NameShort'] . ' - ('.$list['SubscriberCount'].' '.GetLang('GettingStarted_Contacts').')</li>';
			}
		}
	}
}
