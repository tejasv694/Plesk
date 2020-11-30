<?php
/**
* This file has the base subscriber functions in it. Each subprocess is handled separately.
*
* @version     $Id: stats.php,v 1.98 2008/03/03 03:17:04 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Base class for subscribers processing. This simply hands the processing to subareas (eg adding, banning, exporting and so on).
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Stats extends SendStudio_Functions
{

	/**
	* Default sort.
	*
	* @var _DefaultSort
	*/
	var $_DefaultSort = 'finishtime';

	/**
	* Default direction for sorting.
	*
	* @var _DefaultDirection
	*/
	var $_DefaultDirection = 'down';

	/**
	* @var CalendarRestrictions Store the calendar restrictions in the class - easy reference.
	*
	* @see CalculateCalendarRestrictions
	*/
	var $CalendarRestrictions = array (
		'opens' => '',
		'clicks' => '',
		'forwards' => '',
		'bounces' => '',
		'unsubscribes' => '',
		'subscribers' => array('subscribes' => '', 'unsubscribes' => '', 'bounces' => ''),
		'recipients' => ''
	);

	/**
	* Constructor
	* Loads the language file.
	*
	* @return Void Doesn't return anything.
	*/
	function Stats()
	{
		$this->LoadLanguageFile();
	}

	/**
	* Process
	* Standard process function. Works out what you're trying to do and passes action off to other functions.
	*
	* @return Void Doesn't return anything. Hands control off to other functions.
	*/
	function Process()
	{
		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;
		$user = IEM::userGetCurrent();
		$access = $user->HasAccess('Statistics');

		$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : null;

		$popup = ($action == 'print') ? true : false;

		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->PrintHeader($popup);

		// Print the loading indicator for the charts
		$GLOBALS['TableType'] = 'chart';
		$this->ParseTemplate('Loading_Indicator', false);

		if (!$access) {
			$this->DenyAccess();
		}

		foreach (array('lc', 'uc', 'oc', 'bc', 'fc', 'rc', '') as $k => $area) {
			if ($action == 'processpaging' . $area) {
				$page = null;
				if ($area) {
					$page = 'stats_processpaging' . $area;
				}
				if (isset($_GET['PerPageDisplay' . $area])) {
					$this->SetPerPage($_GET['PerPageDisplay' . $area], $page);
				}
				$action = $subaction;
				if (isset($_GET['NextAction'])) {
					$subaction = strtolower($_GET['NextAction']);
				}
				break;
			}
		}

		if ($action == 'processcalendar') {
			if (isset($_POST['Calendar'])) {
				$calendar_settings = $_POST['Calendar'];
				$user->SetSettings('Calendar', $calendar_settings);
				$this->CalculateCalendarRestrictions($calendar_settings);
				$user->SetSettings('CalendarDates', $this->CalendarRestrictions);
				$user->SaveSettings();
			}
			$action = $subaction;
			if (isset($_GET['NextAction'])) {
				$subaction = strtolower($_GET['NextAction']);
			}
		}

		$this->CalculateCalendarRestrictions();

		switch ($action) {
			case 'list':
				if (!$user->HasAccess('statistics', 'list')) {
					$this->DenyAccess();
				}
				switch ($subaction) {
					case 'step2':
					case 'viewsummary':
						$listid = 0;
						if (isset($_GET['list'])) {
							$listid = (int)$_GET['list'];
						}

						$this->PrintListStats_Step2($listid);
					break;

					default:
						// if they have changed paging, we'll have a 'default' action but the userid will still be in the url.
						if (isset($_GET['list'])) {
							$this->PrintListStats_Step2($_GET['list']);
							break;
						}

						IEM::sessionRemove('ListStatistics');
						$this->PrintListStats_Step1();
				}
			break;

			case 'triggeremails':
				$this->TriggerEmailsStats($subaction);
			break;

			case 'user':
				if (!$user->HasAccess('statistics', 'user')) {
					$this->DenyAccess();
				}
				IEM::sessionRemove('ListStatistics');
				switch ($subaction) {
					case 'step2':
						$userid = 0;
						if (isset($_GET['user'])) {
							$userid = (int)$_GET['user'];
						}
						$this->PrintUserStats_Step2($userid);
					break;

					default:
						// if they have changed paging, we'll have a 'default' action but the userid will still be in the url.
						if (isset($_GET['user'])) {
							$this->PrintUserStats_Step2($_GET['user']);
							break;
						}
						$this->PrintUserStats_Step1();
				}
			break;

			case 'autoresponders':
				if (!$user->HasAccess('statistics', 'autoresponder')) {
					$this->DenyAccess();
				}
				IEM::sessionRemove('ListStatistics');
				$this->LoadLanguageFile('Autoresponders');
				switch ($subaction) {
					case 'doselect':
						switch (strtolower($_REQUEST['SelectAction'])) {
							case 'delete':
								$stats_api = $this->GetApi('Stats');
								$stats_to_delete = array();
								if (isset($_POST['stats']) && !empty($_POST['stats'])) {
									foreach ($_POST['stats'] as $statid) {
										$autoresponderid = $statid;
										$summary = $stats_api->GetAutoresponderSummary($autoresponderid, true, 0);
										$stats_to_delete[] = $summary['statid'];
									}
								}

								if (isset($_GET['id'])) {
									$stats_to_delete[] = (int)$_GET['id'];
								}

								if (empty($stats_to_delete)) {
									$this->PrintAutoresponderStats_Step1();
									break;
								}
								$success = 0;
								$failure = 0;
								$cant_delete = 0;
								foreach ($stats_to_delete as $p => $statid) {
									// bail if they're trying to delete things they're not allowed to
									if (!$this->CanAccessStats($statid, 'a')) {
										$this->DenyAccess();
									}

									if (!$statid) {
										$cant_delete++;
										continue;
									}

									$delete = $stats_api->HideStats($statid, 'autoresponder', $user->Get('userid'));
									if ($delete) {
										$success++;
									} else {
										$failure++;
									}
								}

								$msg = '';

								if ($failure > 0) {
									if ($failure == 1) {
										$GLOBALS['Error'] = GetLang('StatisticsDeleteFail_One');
									} else {
										$GLOBALS['Error'] = sprintf(GetLang('StatisticsDeleteFail_One'), $this->FormatNumber($failure));
									}
									$msg .= $this->ParseTemplate('ErrorMsg', true, false);
								}

								if ($success > 0) {
									if ($success == 1) {
										$msg .= $this->PrintSuccess('StatisticsDeleteSuccess_One');
									} else {
										$msg .= $this->PrintSuccess('StatisticsDeleteSuccess_Many', $this->FormatNumber($success));
									}
								}

								if ($cant_delete > 0) {
									if ($cant_delete == 1) {
										$msg .= $this->PrintSuccess('StatisticsDeleteNoStatistics_One');
									} else {
										$msg .= $this->PrintSuccess('StatisticsDeleteNoStatistics_Many', $this->FormatNumber($cant_delete));
									}
								}

								$GLOBALS['Message'] = $msg;
								$this->PrintAutoresponderStats_Step1($msg);
							break; // delete
						}
					break; // doselect

				case 'step2':
				case 'viewsummary':
					$autoid = 0;
					if (isset($_GET['auto'])) {
						$autoid = (int)$_GET['auto'];
					}
					if (!$this->CanAccessAutoresponder($autoid)) {
						$this->DenyAccess();
					}
					$this->PrintAutoresponderStats_Step2($autoid);
				break;

				default:
					$this->PrintAutoresponderStats_Step1();
				} // switch ($subaction)
			break;

			default:
				if (!$user->HasAccess('statistics', 'newsletter')) {
					$this->DenyAccess();
				}

				IEM::sessionRemove('ListStatistics');

				switch (strtolower($subaction)) {
					case 'doselect':
						$selectAction = IEM::ifsetor($_REQUEST['SelectAction'], 'strtolower');
						switch (strtolower($selectAction)) {
							case 'export':
								$newsletterapi = $this->GetApi('Newsletters');
								$statsapi = $this->GetApi('Stats');

								$name = '';
								if (count($_REQUEST['stats']) == 1) {
									// When exporting for just one campaign, use the campaign name in the file name
									$f = $statsapi->FetchStats($_REQUEST['stats'][0],'newsletter');
									$newsletterapi->Load($f['newsletterid']);
									if (!$this->IsOwner($newsletterapi->ownerid)) {
										$this->DenyAccess();
									}
									$name = preg_replace('/[^a-z0-9]/i','_',$newsletterapi->name) . "_";
								}
								$name .= "stats_" . $this->PrintDate(time(),'dmy');

								while (is_file(TEMP_DIRECTORY . "/{$name}.csv")) {
									$name .= "_" . rand(10,99);
								}
								$name .= ".csv";

								$local = TEMP_DIRECTORY . "/$name";
								$http = SENDSTUDIO_TEMP_URL . "/$name";

								if (is_writable(TEMP_DIRECTORY)) {
									$fh = fopen($local,'wb');

									$header = array(
										GetLang('Stats_Export_Header_Subject'),
										GetLang('Stats_Export_Header_Date'),
										GetLang('Stats_Export_Header_Time'),
										GetLang('Stats_Export_Header_Duration'),
										GetLang('Stats_Export_Header_Recipients'),
										GetLang('Stats_Export_Header_Send_Rate'),
										GetLang('Stats_Export_Header_Unique_Opened'),
										GetLang('Stats_Export_Header_Total_Opened'),
										GetLang('Stats_Export_Header_Percent_Opened'),
										GetLang('Stats_Export_Header_Recipients_who_Clicked_Links'),
										GetLang('Stats_Export_Header_Percent_Recipients_who_Clicked'),
										GetLang('Stats_Export_Header_Total_Links_Clicked'),
										GetLang('Stats_Export_Header_Hard_Bounced'),
										GetLang('Stats_Export_Header_Soft_Bounced'),
										GetLang('Stats_Export_Header_Total_Bounced'),
										GetLang('Stats_Export_Header_Percent_Bounced'),
										GetLang('Stats_Export_Header_Unsubscribed'),
										GetLang('Stats_Export_Header_Percent_Unsubscribed'),
										GetLang('Stats_Export_Header_Forwarded'),
										GetLang('Stats_Export_Header_Recipients_who_Forwarded'),
										GetLang('Stats_Export_Header_Percent_Recipients_who_Forwarded')
									);

									$header = '"' . implode('","',$header) . '"';
									fwrite($fh,"$header\r\n");

									foreach ($_REQUEST['stats'] as $statid) {
										$f = $statsapi->FetchStats($statid,'newsletter');

										$row = array();
										$newsletterapi->Load($f['newsletterid']);
										if (!$this->IsOwner($newsletterapi->ownerid)) {
											$this->DenyAccess();
										}

										$duration = $f['finishtime'] - $f['starttime'];
										$recipients = $f['sendsize'];
										$bounces = $f['bouncecount_hard'] + $f['bouncecount_soft'];
										$unique_clicks = (int)$statsapi->GetUniqueClickRecipients($statid);
										$unique_forwards = (int)$statsapi->GetForwardsRecipients($statid);

										if ($duration == 0) {
											$send_rate = $recipients;
										} else {
											$send_rate = round($recipients / ($duration / 60),2);
										}

										if ($recipients == 0) {
											$open_percent = $click_percent = $bounce_percent =
											$unsub_percent = $forward_percent = 0;
										} else {
											$open_percent = round($f['emailopens_unique'] / $recipients * 100,2);
											$click_percent = round($unique_clicks / $recipients * 100,2);
											$bounce_percent = round($bounces / $recipients * 100,2);
											$unsub_percent = round($f['unsubscribecount'] / $recipients * 100,2);
											$forward_percent = round($unique_forwards / $recipients * 100,2);
										}

										$row = array(
											str_replace('"','_',$newsletterapi->subject),
											$this->PrintDate($f['starttime'],'d/m/y'),
											$this->PrintDate($f['starttime'],'H:i'),
											round($duration / 60,2),
											$recipients,
											$send_rate,
											$f['emailopens_unique'],
											$f['emailopens'],
											$open_percent,
											$unique_clicks,
											$click_percent,
											$f['linkclicks'],
											$f['bouncecount_hard'],
											$f['bouncecount_soft'],
											$bounces,
											$bounce_percent,
											$f['unsubscribecount'],
											$unsub_percent,
											$f['emailforwards'],
											$unique_forwards,
											$forward_percent
										);

										$entry = '"' . implode('","',$row) . '"';
										fwrite($fh,"$entry\r\n");
									}

									fclose($fh);
									$GLOBALS['Message'] = $this->PrintSuccess('Export_Newsletter_Statistics',$http);
								} else {
									$GLOBALS['Message'] = $this->PrintWarning('Export_Not_Writable',TEMP_DIRECTORY);
								}
							break; // export

							case 'delete':
								$stats_to_delete = array();
								if (isset($_POST['stats']) && !empty($_POST['stats'])) {
									$stats_to_delete = $_POST['stats'];
								}

								if (isset($_GET['id'])) {
									$stats_to_delete[] = (int)$_GET['id'];
								}

								if (empty($stats_to_delete)) {
									$this->PrintNewsletterStats_Step1();
								}

								$stats_api = $this->GetApi('Stats');
								$success = 0;
								$failure = 0;
								$cant_delete = 0;

								foreach ($stats_to_delete as $p => $statid) {
									if (!$this->CanAccessStats($statid, 'n')) {
										// bail if they're trying to delete things they're not allowed to
										$this->DenyAccess();
									}
									$finished = $stats_api->IsFinished($statid, 'newsletter');
									if (!$finished) {
										$cant_delete++;
										continue;
									}
									$delete = $stats_api->HideStats($statid, 'newsletter', $user->Get('userid'));
									if ($delete) {
										$success++;
									} else {
										$failure++;
									}
								}

								$msg = '';

								if ($failure > 0) {
									if ($failure == 1) {
										$GLOBALS['Error'] = GetLang('StatisticsDeleteFail_One');
									} else {
										$GLOBALS['Error'] = sprintf(GetLang('StatisticsDeleteFail_One'), $this->FormatNumber($failure));
									}
									$msg .= $this->ParseTemplate('ErrorMsg', true, false);
								}

								if ($success > 0) {
									if ($success == 1) {
										$msg .= $this->PrintSuccess('StatisticsDeleteSuccess_One');
									} else {
										$msg .= $this->PrintSuccess('StatisticsDeleteSuccess_Many', $this->FormatNumber($success));
									}
								}

								if ($cant_delete > 0) {
									if ($cant_delete == 1) {
										$msg .= $this->PrintSuccess('StatisticsDeleteNotFinished_One');
									} else {
										$msg .= $this->PrintSuccess('StatisticsDeleteNotFinished_Many', $this->FormatNumber($cant_delete));
									}
								}

								$GLOBALS['Message'] = $msg;
							break; // delete
						}
						$this->PrintNewsletterStats_Step1();
					break; // doselect

					case 'viewsummary':
						$statid = IEM::requestGetGET('id', 0, 'intval');

						if (!$this->CanAccessStats($statid, 'n')) {
							$this->DenyAccess();
						}

						$this->PrintNewsletterStats_Step2($statid);
					break;

					default:
						$this->PrintNewsletterStats_Step1();
					break;
				}
		}
		$this->PrintFooter($popup);
	}


	/**
	* PrintNewsletterStats_Step1
	* This will show a list of newsletters that have been sent out according to which lists the user has access to.
	*
	* @see User_API::GetLists
	* @see Stats_API::GetNewsletterStats
	*
	* @return Void Doesn't return anything. Prints out a list of the newsletters sent to the lists that the user has access to.
	*/
	function PrintNewsletterStats_Step1()
	{
		$user = IEM::userGetCurrent();
		$statsapi = $this->GetApi();

		$this->LoadLanguageFile('Newsletters');

		$perpage = $this->GetPerPage();

		$DisplayPage = $this->GetCurrentPage();
		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$lists = $user->GetLists();
		$listids = array_keys($lists);

		$NumberOfStats = $statsapi->GetNewsletterStats($listids, $sortinfo, true, 0, 0);

		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		if ($NumberOfStats == 0) {
			$GLOBALS['Message'] .= $this->PrintSuccess('NoNewslettersHaveBeenSent');

			if ($user->HasAccess('Newsletters', 'Send')) {
				$GLOBALS['Newsletters_SendButton'] = $this->ParseTemplate('Newsletter_Send_Button', true, false);
			}
			$this->ParseTemplate('Stats_Newsletters_Empty');
			return;
		}

		$mystats = $statsapi->GetNewsletterStats($listids, $sortinfo, false, $start, $perpage);

		$this->LoadLanguageFile('Lists');

		$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=Newsletters&NextAction=Step1';

		$GLOBALS['PAGE'] = 'Stats&Action=Newsletters&SubAction=Step1';

		$this->SetupPaging($NumberOfStats, $DisplayPage, $perpage);

		$paging = $this->ParseTemplate('Paging', true, false);

		$stats_manage = $this->ParseTemplate('Stats_Newsletter_Manage', true, false);

		$statsdisplay = '';

		foreach ($mystats as $pos => $statsdetails) {
			$GLOBALS['StatID'] = $statsdetails['statid'];
			$GLOBALS['Newsletter'] = htmlspecialchars($statsdetails['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$full_list_name = htmlspecialchars($statsdetails['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['MailingList_Full'] = $full_list_name;
			$GLOBALS['MailingList'] = $this->TruncateName($full_list_name, 60);
			$GLOBALS['StartDate'] = $this->PrintTime($statsdetails['starttime'], true);

			$GLOBALS['StatsAction'] = '<a href="index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statsdetails['statid'] . '">' . GetLang('ViewSummary') . '</a>&nbsp;&nbsp;';
			$GLOBALS['StatsAction'] .= '<a href="index.php?Page=Stats&Action=Newsletters&SubAction=DoSelect&SelectAction=export&stats%5B%5D=' . $statsdetails['statid'] . '">' . GetLang('Export_Stats_Selected') . '</a>&nbsp;&nbsp;';
			$GLOBALS['StatsAction'] .= '<a href="remote_stats.php?height=420&width=420&overflow=none&statstype=n&Action=print&stats%5B%5D=' . $statsdetails['statid'] . '" class="thickbox" title="' . GetLang('PrintEmailCampaignStatistics') . '">' . GetLang('Print_Stats_Selected') . '</a>&nbsp;&nbsp;';

			$finishtime = $statsdetails['finishtime'];
			if ($finishtime > 0) {
				$GLOBALS['StatsAction'] .= '<a href="javascript:ConfirmDelete(' . $statsdetails['statid'] . ');">' . GetLang('Delete') . '</a>';
				$GLOBALS['FinishDate'] = $this->PrintTime($finishtime, true);
			} else {
				$GLOBALS['StatsAction'] .= '<span class="disabled" title="' . GetLang('StatsDeleteDisabled') . '">' . GetLang('Delete') . '</a>';
				$GLOBALS['FinishDate'] = GetLang('NotFinishedSending');
			}

			$bounce_count = $statsdetails['bouncecount_soft'] + $statsdetails['bouncecount_hard'] + $statsdetails['bouncecount_unknown'];

			$GLOBALS['TotalRecipients'] = $this->FormatNumber($statsdetails['sendsize']);
			$GLOBALS['BounceCount'] = $this->FormatNumber($bounce_count);
			$GLOBALS['UnsubscribeCount'] = $this->FormatNumber($statsdetails['unsubscribecount']);

			$statsdisplay .= $this->ParseTemplate('Stats_Newsletter_Manage_Row', true, false);
		}
		$stats_manage = str_replace('%%TPL_Stats_Newsletter_Manage_Row%%', $statsdisplay, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging%%', $paging, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $stats_manage);

		echo $stats_manage;
	}

	/**
	* PrintNewsletterStats_Step2
	* This displays summary information for a newsletter based on the statid passed in. This sets up the other tabs (opens, bounces, links and so on) as well but this particular function mainly sets up the summary page.
	*
	* @param Int $statid The statid to get information for.
	*
	* @see Stats_API::GetNewsletterSummary
	* @see DisplayNewsletterOpens
	* @see DisplayNewsletterLinks
	* @see DisplayNewsletterBounces
	* @see DisplayNewsletterUnsubscribes
	* @see DisplayNewsletterForwards
	*
	* @return Void Doesn't return anything - just prints out the summary information.
	*/
	function PrintNewsletterStats_Step2($statid=0)
	{
		include_once(dirname(__FILE__) . '/amcharts/amcharts.php');

		$perpage = $this->GetPerPage();

		$statsapi = $this->GetApi('Stats');
		$summary = $statsapi->GetNewsletterSummary($statid, true, $perpage);

		// Log this to "User Activity Log"
		IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/chart_bar.gif', $summary['newslettername']);

		$GLOBALS['NewsletterID'] = $summary['newsletterid'];

		$sent_when = $GLOBALS['StartSending'] = $this->PrintTime($summary['starttime'], true);

		if ($summary['finishtime'] > 0) {
			$GLOBALS['FinishSending'] = $this->PrintTime($summary['finishtime'], true);
			$GLOBALS['SendingTime'] = $this->TimeDifference($summary['finishtime'] - $summary['starttime']);
		} else {
			$GLOBALS['FinishSending'] = GetLang('NotFinishedSending');
			$GLOBALS['SendingTime'] = GetLang('NotFinishedSending');
		}

		$sent_to = $summary['htmlrecipients'] + $summary['textrecipients'] + $summary['multipartrecipients'];

		$sent_size = $summary['sendsize'];

		$GLOBALS['SentToDetails'] = sprintf(GetLang('NewsletterStatistics_Snapshot_SendSize'), $this->FormatNumber($sent_to), $this->FormatNumber($sent_size));

		$test_mode_info = '';
		if ($summary['sendtestmode'] == 1) {
			$test_mode_info = GetLang('NewsletterStatistics_Send_TestMode_Enabled');
		}

		$GLOBALS['SummaryIntro'] = sprintf(GetLang('NewsletterStatistics_Snapshot_Summary'), htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET), $sent_when, $test_mode_info);

		$GLOBALS['NewsletterName'] = htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['NewsletterSubject'] = htmlspecialchars($summary['newslettersubject'], ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['UserEmail'] = htmlspecialchars($summary['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		$sent_by = $summary['username'];
		if ($summary['fullname']) {
			$sent_by = $summary['fullname'];
		}
		$GLOBALS['SentBy'] = htmlspecialchars($sent_by, ENT_QUOTES, SENDSTUDIO_CHARSET);

		if (sizeof($summary['lists']) > 1) {
			$GLOBALS['SentToLists'] = GetLang('SentToLists');
			$GLOBALS['MailingLists'] = '';
			$break_up = 4;
			$c = 1;
			foreach ($summary['lists'] as $listid => $listname) {
				if ($c % $break_up == 0) {
					$GLOBALS['MailingLists'] .= '<br/>';
					$c = 0;
				}
				$GLOBALS['MailingLists'] .= htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET) . ',';
				$c++;
			}
			if (($c - 1) % $break_up != 0) {
				$GLOBALS['MailingLists'] = substr($GLOBALS['MailingLists'], 0, -1);
			}
		} else {
			$GLOBALS['SentToLists'] = GetLang('SentToList');
			$listname = current($summary['lists']);
			$GLOBALS['MailingLists'] = htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET);
		}

		$GLOBALS['UniqueOpens'] = sprintf(GetLang('EmailOpens_Unique'), $this->FormatNumber($summary['emailopens_unique']));
		$GLOBALS['TotalOpens'] = sprintf(GetLang('EmailOpens_Total'), $this->FormatNumber($summary['emailopens']));

		if ($sent_to != 0) {
			$GLOBALS['OpenRate'] = $this->FormatNumber($summary['emailopens_unique'] / $sent_to * 100,2) . "%" ;
		} else {
			$GLOBALS['OpenRate'] = '0%';
		}

		$clicks = $statsapi->GetUniqueClickRecipients($statid,'','a');
		if ($sent_to == 0) {
			$GLOBALS['ClickThroughRate'] = "0%";
		} else {
			$GLOBALS['ClickThroughRate'] = $this->FormatNumber($clicks / $sent_to * 100,2) . '%';
		}

		$total_bounces = $summary['bouncecount_unknown'] + $summary['bouncecount_hard'] + $summary['bouncecount_soft'];

		$GLOBALS['TotalBounces'] = $this->FormatNumber($total_bounces);

		// now for the opens page.
		// by default this is for all opens, not unique opens.
		$only_unique = false;
		if (isset($_GET['Unique'])) {
			$only_unique = true;
		}

		if (!isset($_GET['Unique'])) {
			$GLOBALS['OpensURL'] = 'javascript: void(0);" onclick="ShowTab(2);';

			$GLOBALS['UniqueOpensURL'] = 'index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statid . '&tab=2&Unique';
		} else {
			$GLOBALS['UniqueOpensURL'] = 'javascript: void(0);" onclick="ShowTab(2);';

			$GLOBALS['OpensURL'] = 'index.php?Page=Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statid . '&tab=2';
		}

		$chosen_link = 'a';
		if (isset($_GET['link'])) {
			if (is_numeric($_GET['link'])) {
				$chosen_link = $_GET['link'];
			}
		}

		$chosen_bounce_type = '';
		if (isset($_GET['bouncetype'])) {
			$chosen_bounce_type = $_GET['bouncetype'];
		}

		// we need to process the opens page first because it sets the number of opens used in a calculation for the links page.
		$GLOBALS['OpensPage'] = $this->DisplayNewsletterOpens($statid, $summary, $only_unique);

		$GLOBALS['LinksPage'] = $this->DisplayNewsletterLinks($statid, $summary, $chosen_link);

		$GLOBALS['BouncesPage'] = $this->DisplayNewsletterBounces($statid, $summary, $chosen_bounce_type);

		$GLOBALS['UnsubscribesPage'] = $this->DisplayNewsletterUnsubscribes($statid, $summary);

		$GLOBALS['ForwardsPage'] = $this->DisplayNewsletterForwards($statid, $summary);

		$unopened = $sent_size - $summary['emailopens_unique'] - $total_bounces;
		if ($unopened < 0) {
			$unopened = 0;
		}

		// explicitly pass the sessionid across to the chart
		// since it's not the browser but the server making this request, it may get a different session id if we don't, which then means it can't load the data properly.
		// especially applies to windows servers.

		$data_url = 'stats_chart.php?Opens='. $summary['emailopens_unique'] . '&Unopened=' . $unopened . '&Bounced=' . $total_bounces . '&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		// Newsletter Summary Chart

		$GLOBALS['SummaryChart'] = InsertChart('pie', $data_url, array('graph_title' => GetLang("NewsletterSummaryChart")));

		// finally put it all together.
		$page = $this->ParseTemplate('Stats_Newsletters_Step3', true, false);

		if (isset($_GET['tab'])) {
			$page .= '
			<script>
				ShowTab(' . $_GET['tab'] . ');
			</script>
			';
		}

		echo $page;
	}

	/**
	* DisplayNewsletterOpens
	* This displays the page of newsletter open information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Array $summary The basic information - start time and total number of opens.
	* @param Boolean $unique_only Whether to only show unique opens or not. By default this will show all open information, not just unique opens.
	*
	* @see Stats_API::GetOpens
	* @see Stats_API::GetMostOpens
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayNewsletterOpens($statid, $summary=array(), $unique_only=false)
	{
		$sent_when = $this->PrintTime($summary['starttime'], true);

		$GLOBALS['DisplayOpensIntro'] = sprintf(GetLang('NewsletterStatistics_Snapshot_OpenHeading'), htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET), $sent_when);

		if ($unique_only) {
			$GLOBALS['DisplayOpensIntro'] = sprintf(GetLang('NewsletterStatistics_Snapshot_OpenHeading_Unique'), htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET), $sent_when);
		}

		$statsapi = $this->GetApi('Stats');

		$GLOBALS['PPDisplayName'] = 'oc';

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Newsletters&NextAction=ViewSummary&id=' . $statid . '&tab=2';

		if ($unique_only) {
			$base_action .= '&Unique';
		}

		$calendar_restrictions = $this->CalendarRestrictions['opens'];

		$GLOBALS['TabID'] = '2';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage'])) ? (int)$_GET['DisplayPage'] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		// make sure unique opens are > 0 - if they aren't, something isn't tracking right anyway so no point trying anything else.
		if ($summary['emailopens_unique'] > 0) {
			$opens = $statsapi->GetOpens($statid, $start, $perpage, $unique_only, $calendar_restrictions);
		}

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/
		$opencount = $statsapi->GetOpens($statid, 0, 0, $unique_only, $calendar_restrictions, true);

		// if we still don't have any opens, not sure how! but we display an error.
		if (empty($opens)) {
			if ($summary['trackopens']) {
				if ($summary['emailopens_unique'] > 0) {
					$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenOpened_CalendarProblem');
				} else {
					$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenOpened');
				}
			} else {
				$GLOBALS['Error'] = GetLang('NewsletterWasNotOpenTracked');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Opens_Empty', true, false);
		}

		$total_emails = $summary['htmlrecipients'] + $summary['textrecipients'] + $summary['multipartrecipients'];
		$GLOBALS['TotalEmails'] = $this->FormatNumber($total_emails);
		$GLOBALS['TotalOpens'] = $this->FormatNumber($summary['emailopens']);
		$GLOBALS['TotalUniqueOpens'] = $this->FormatNumber($summary['emailopens_unique']);

		$most_opens = $statsapi->GetMostOpens($statid, $calendar_restrictions);

		$now = getdate();

		if (isset($most_opens['mth'])) {
			$GLOBALS['MostOpens'] = $this->Months[$most_opens['mth']] . ' ' . $most_opens['yr'];
		}

		if (isset($most_opens['hr'])) {
			$GLOBALS['MostOpens'] = date(GetLang('Daily_Time_Display'),mktime($most_opens['hr'], 1, 1, 1, 1, $now['year']));
		}

		if (isset($most_opens['dow'])) {
			$pos = array_search($most_opens['dow'], array_keys($this->days_of_week));
			$GLOBALS['MostOpens'] = date(GetLang('Date_Display_Display'), strtotime("last " . $this->days_of_week[$pos]));
		}

		if (isset($most_opens['dom'])) {
			$month = $now['mon'];
			// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
			if ($most_opens['dom'] > $now['mday']) {
				$month = $now['mon'] - 1;
			}
			$GLOBALS['MostOpens'] = date(GetLang('Date_Display_Display'),mktime(0, 0, 1, $month, $most_opens['dom'], $now['year']));
		}

		$avg_opens = 0;
		if ($total_emails > 0) {
			$avg_opens = $summary['emailopens'] / $total_emails;
		}
		$GLOBALS['AverageOpens'] = $this->FormatNumber($avg_opens, 1);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statid . '&tab=2';

		if ($unique_only) {
			$GLOBALS['PAGE'] .= '&Unique';
		}

		$this->DisplayChart('OpenChart', 'newsletter', $statid,'column',array('graph_title' => GetLang("OpensChart")));


		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'unique_only' => $unique_only,
		  'calendar_restrictions' => $calendar_restrictions,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_opens';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator',true);

		$GLOBALS['NewsletterOpenCount'] = $opencount;

		return $this->ParseTemplate('Stats_Step3_Opens', true, false);
	}

	/**
	* DisplayNewsletterLinks
	* This displays the page of newsletter link information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Array $summary The basic information - start time and total number of link clicks.
	* @param String $chosen_link If this is present, we are showing information for a specific link. If it's not present, combine all links into one.
	*
	* @see Stats_API::GetClicks
	* @see Stats_API::GetUniqueLinks
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayNewsletterLinks($statid, $summary=array(), $chosen_link='a')
	{
		$sent_when = $this->PrintTime($summary['starttime'], true);

		$GLOBALS['StatID'] = (int)$statid;

		$GLOBALS['LinkAction'] = 'Newsletter';

		if (!is_numeric($chosen_link)) {
			$chosen_link = 'a';
		}

		$GLOBALS['DisplayLinksIntro'] = sprintf(GetLang('NewsletterStatistics_Snapshot_LinkHeading'), htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET), $sent_when);

		$statsapi = $this->GetApi('Stats');

		$GLOBALS['PPDisplayName'] = 'lc';
		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Newsletters&NextAction=ViewSummary&id=' . $statid . '&tab=3&link=' . $chosen_link;

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statid . '&tab=3&link=' . $chosen_link;

		$calendar_restrictions = $this->CalendarRestrictions['clicks'];

		$GLOBALS['TabID'] = '3';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/
		$summary['linkclicks'] = $statsapi->GetClicks($statid, 0, 0, $chosen_link, $calendar_restrictions, true);

		$links = array();
		if ($summary['linkclicks'] > 0) {
			$links = $statsapi->GetClicks($statid, 0, 10, $chosen_link, $calendar_restrictions, false);
		}

		if (empty($links)) {
			if ($summary['tracklinks']) {
				if (empty($all_links)) {
					$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenClicked_NoLinksFound');
				} else {
					if ($summary['linkclicks'] > 0) {
						if (is_numeric($chosen_link)) {
							if ($calendar_restrictions != '') {
								$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenClicked_CalendarLinkProblem');
							} else {
								$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenClicked_LinkProblem');
							}
						} else {
							$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenClicked_CalendarProblem');
						}
					} else {
						$GLOBALS['DisplayStatsLinkList'] = 'none';
						$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenClicked');
					}
				}
			} else {
				$GLOBALS['Error'] = GetLang('NewsletterWasNotTracked_Links');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Links_Empty', true, false);
		}

		// build up the summary table.
		$GLOBALS['TotalClicks'] = $this->FormatNumber($summary['linkclicks']);

		$unique_clicks_count = $statsapi->GetUniqueClicks($statid, $chosen_link, $calendar_restrictions);
		$GLOBALS['TotalUniqueClicks'] = $this->FormatNumber($unique_clicks_count);

		$unique_clicks = $statsapi->GetUniqueClickRecipients($statid,$calendar_restrictions,$chosen_link);
		$GLOBALS['UniqueClicks'] = $this->FormatNumber($unique_clicks);

		$most_popular_link = $statsapi->GetMostPopularLink($statid, $chosen_link, $calendar_restrictions);

		$GLOBALS['MostPopularLink'] = htmlspecialchars($most_popular_link, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['MostPopularLink_Short'] = $this->TruncateName($most_popular_link, 20);

		$averageclicks = 0;
		if (isset($GLOBALS['NewsletterOpenCount']) && (int)$GLOBALS['NewsletterOpenCount'] > 0) {
			$open_count = (int)$GLOBALS['NewsletterOpenCount'];
			$averageclicks = $summary['linkclicks'] / $open_count;
		}
		$GLOBALS['AverageClicks'] = $this->FormatNumber($averageclicks, 1);

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'chosen_link' => $chosen_link,
		  'calendar_restrictions' => $calendar_restrictions,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_links';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator',true);

		$this->DisplayChart('LinksChart', 'newsletter', $statid, 'column', array('graph_title' => GetLang("LinksClickedChart")));

		return $this->ParseTemplate('Stats_Step3_Links', true, false);
	}

	/**
	* DisplayNewsletterBounces
	* This displays the page of newsletter bounce information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Array $summary The basic information - start time and total number of bounces.
	* @param String $chosen_bounce_type If this is present, we are showing information for a specific bounce type (hard, soft or unknown). If it's not present, combine all types.
	*
	* @see Stats_API::GetBounces
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayNewsletterBounces($statid, $summary=array(), $chosen_bounce_type='')
	{
		$sent_when = $this->PrintTime($summary['starttime'], true);

		$GLOBALS['DisplayBouncesIntro'] = sprintf(GetLang('NewsletterStatistics_Snapshot_BounceHeading'), htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET), $sent_when);

		$GLOBALS['BounceAction'] = 'Newsletters';

		$GLOBALS['PPDisplayName'] = 'bc'; // bounce count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Newsletters&NextAction=ViewSummary&id=' . $statid . '&tab=4';

		$calendar_restrictions = $this->CalendarRestrictions['bounces'];

		$GLOBALS['TabID'] = '4';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi('Stats');

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete bounced subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/

		$bounces = array();

                $total_bounces = $statsapi->GetBounces($statid, 0, 10, $chosen_bounce_type, $calendar_restrictions, true);

		if ($total_bounces == 0) {
			if ($calendar_restrictions != '') {
				if ($total_bounces > 0) {
					if (!$chosen_bounce_type || $chosen_bounce_type == 'any') {
						$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenBounced_CalendarProblem');
					} else {
						$GLOBALS['Error'] = sprintf(GetLang('NewsletterHasNotBeenBounced_CalendarProblem_BounceType'), GetLang('Bounce_Type_' . $chosen_bounce_type));
					}
				} else {
					$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenBounced');
				}
			} else {
				if ($total_bounces > 0 && (!$chosen_bounce_type || $chosen_bounce_type == 'any')) {
					$GLOBALS['Error'] = sprintf(GetLang('NewsletterHasNotBeenBounced_BounceType'), GetLang('Bounce_Type_' . $chosen_bounce_type));
				} else {
					$GLOBALS['Error'] = GetLang('NewsletterHasNotBeenBounced');
				}
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Bounces_Empty', true, false);
		}

		$bounce_types_count = $statsapi->GetBounceCounts($statid, $calendar_restrictions);
		$GLOBALS['TotalBounceCount'] = $this->FormatNumber($bounce_types_count['total']);
		$GLOBALS['TotalSoftBounceCount'] = $this->FormatNumber($bounce_types_count['soft']);
		$GLOBALS['TotalHardBounceCount'] = $this->FormatNumber($bounce_types_count['hard']);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statid . '&tab=4&bouncetype=' . $chosen_bounce_type;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'chosen_bounce_type' => $chosen_bounce_type,
		  'calendar_restrictions' => $calendar_restrictions,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_bounces';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator',true);

		$this->DisplayChart('BounceChart', 'newsletter', $statid, 'column', array('graph_title' => GetLang('BounceChart')));

		return $this->ParseTemplate('Stats_Step3_Bounces', true, false);
	}

	/**
	* DisplayNewsletterUnsubscribes
	* This displays the page of newsletter unsubscribe information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Array $summary The basic information - start time and total number of unsubscribes.
	*
	* @see Stats_API::GetUnsubscribes
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayNewsletterUnsubscribes($statid, $summary=array())
	{
		$sent_when = $this->PrintTime($summary['starttime'], true);

		$GLOBALS['DisplayUnsubscribesIntro'] = sprintf(GetLang('NewsletterStatistics_Snapshot_UnsubscribesHeading'), htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET), $sent_when);

		$GLOBALS['PPDisplayName'] = 'uc'; // unsubscribe count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Newsletters&NextAction=ViewSummary&id=' . $statid . '&tab=5';

		$calendar_restrictions = $this->CalendarRestrictions['unsubscribes'];

		$GLOBALS['TabID'] = '5';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi('Stats');

		if ($calendar_restrictions != '') {
			$summary['unsubscribecount'] = $statsapi->GetUnsubscribes($statid, 0, 0, $calendar_restrictions, true);
		}

		$unsubscribes = array();

		if ($summary['unsubscribecount'] > 0) {
			$unsubscribes = $statsapi->GetUnsubscribes($statid, 0, 10, $calendar_restrictions);
		}

		if (empty($unsubscribes)) {
			if ($summary['unsubscribecount'] > 0) {
				$GLOBALS['Error'] = GetLang('NewsletterHasNoUnsubscribes_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('NewsletterHasNoUnsubscribes');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Unsubscribes_Empty', true, false);
		}

		$GLOBALS['TotalUnsubscribes'] = $this->FormatNumber($summary['unsubscribecount']);

		$most_unsubscribes = $statsapi->GetMostUnsubscribes($statid, $calendar_restrictions);

		$now = getdate();

		if (isset($most_unsubscribes['mth'])) {
			$GLOBALS['MostUnsubscribes'] = $this->Months[$most_unsubscribes['mth']] . ' ' . $most_unsubscribes['yr'];
		}

		if (isset($most_unsubscribes['hr'])) {
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(mktime($most_unsubscribes['hr'], 1, 1, 1, 1, $now['year']), GetLang('Daily_Time_Display'));
		}

		if (isset($most_unsubscribes['dow'])) {
			$pos = array_search($most_unsubscribes['dow'], array_keys($this->days_of_week));
			// we need to add 1 hour here otherwise we get the wrong day from strtotime.
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(strtotime("last " . $this->days_of_week[$pos] . " +1 hour"), GetLang('Date_Display_Display'));
		}

		if (isset($most_unsubscribes['dom'])) {
			$month = $now['mon'];
			// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
			if ($most_unsubscribes['dom'] > $now['mday']) {
				$month = $now['mon'] - 1;
			}
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(mktime(0, 0, 1, $month, $most_unsubscribes['dom'], $now['year']), GetLang('Date_Display_Display'));
		}

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statid . '&tab=5';

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'calendar_restrictions' => $calendar_restrictions,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_unsubscribes';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator',true);

		$this->DisplayChart('UnsubscribeChart', 'newsletter', $statid, 'column', array('graph_title' => GetLang("UnsubscribesChart")));

		return $this->ParseTemplate('Stats_Step3_Unsubscribes', true, false);
	}

	/**
	* DisplayNewsletterForwards
	* This displays the page of newsletter forwarding information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Array $summary The basic information - start time and total number of forwards.
	*
	* @see Stats_API::GetForwards
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayNewsletterForwards($statid, $summary=array())
	{
		$sent_when = $this->PrintTime($summary['starttime'], true);

		$GLOBALS['DisplayForwardsIntro'] = sprintf(GetLang('NewsletterStatistics_Snapshot_ForwardsHeading'), htmlspecialchars($summary['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET), $sent_when);

		$GLOBALS['PPDisplayName'] = 'fc'; // forward count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Newsletters&NextAction=ViewSummary&id=' . $statid . '&tab=6';

		$calendar_restrictions = $this->CalendarRestrictions['forwards'];

		$GLOBALS['TabID'] = '6';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi('Stats');

		$forwards = array();

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage'])) ? (int)$_GET['DisplayPage'] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		if ($summary['emailforwards'] > 0) {
			$forwards = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions);
		}

		if (empty($forwards)) {
			if ($summary['emailforwards'] > 0) {
				$GLOBALS['Error'] = GetLang('NewsletterHasNoForwards_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('NewsletterHasNoForwards');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Forwards_Empty', true, false);
		}

		if ($calendar_restrictions != '') {
			$summary['emailforwards'] = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions, true);
		}

		$GLOBALS['TotalForwards'] = $this->FormatNumber($summary['emailforwards']);

		$new_signups = $statsapi->GetForwards($statid, 0, 0, $calendar_restrictions, true, true);

		$GLOBALS['TotalForwardSignups'] = $this->FormatNumber($new_signups);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Newsletters&SubAction=ViewSummary&id=' . $statid . '&tab=6';

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'calendar_restrictions' => $calendar_restrictions,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'forwards';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator',true);

		$this->DisplayChart('ForwardsChart', 'newsletter', $statid, 'column', array('graph_title' => GetLang("ForwardsChart")));

		return $this->ParseTemplate('Stats_Step3_Forwards', true, false);
	}

	/**
	* PrintUserStats_Step1
	* This prints out a list of users that the user can see.
	* If they are not a useradmin, it will take them straight to viewing their own statistics.
	* If they are a useradmin, they will see a list of users to choose from.
	*
	* @see User_API::HasAccess
	* @see User_API::UserAdmin
	*
	* @return Void Doesn't return anything. Prints a dropdown list of users if they are a useradmin. If they are not a useradmin, then this will take them straight to viewing their own statistics.
	*/
	function PrintUserStats_Step1()
	{
		$user = IEM::userGetCurrent();
		if (!$user->UserAdmin()) {
			$location = 'index.php?Page=Stats&Action=User&SubAction=Step2&user=' . $user->Get('userid');
			?>
			<script>
				window.location = '<?php echo $location; ?>';
			</script>
			<?php
			exit();
		}
		$GLOBALS['Action'] = 'User';

		$this->LoadLanguageFile('Users');

		$GLOBALS['NoSelection'] = GetLang('Stats_Users_NoSelection');
		$GLOBALS['CancelPrompt'] = GetLang('Stats_Users_Cancel');

		$GLOBALS['Heading'] = GetLang('Stats_Users_Step1_Heading');
		$GLOBALS['Intro'] = GetLang('Stats_Users_Step1_Intro');

		$GLOBALS['SelectList_Heading'] = GetLang('Stats_Users_SelectList_Heading');
		$GLOBALS['SelectList_Intro'] = GetLang('Stats_Users_SelectList_Intro');

		$perpage = $this->GetPerPage();

		if (!isset($_GET['Direction'])) {
			$this->_DefaultDirection = 'up';
		}

		$DisplayPage = $this->GetCurrentPage();

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$userapi = $this->GetApi('User');
		$NumberOfUsers = $userapi->GetUsers(0, $sortinfo, true);
		$myusers = $userapi->GetUsers(0, $sortinfo, false, $start, $perpage);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=User&NextAction=Step1';

		$GLOBALS['PAGE'] = 'Stats&Action=User&SubAction=Step1';

		$this->SetupPaging($NumberOfUsers, $DisplayPage, $perpage);

		$paging = $this->ParseTemplate('Paging', true, false);

		$stats_manage = $this->ParseTemplate('Stats_Users_Manage', true, false);

		$statsdisplay = '';

		foreach ($myusers as $pos => $userdetails) {
			$GLOBALS['UserID'] = $userdetails['userid'];
			$GLOBALS['UserName'] = htmlspecialchars($userdetails['username'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['FullName'] = htmlspecialchars($userdetails['fullname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			if (!$userdetails['fullname']) {
				$GLOBALS['FullName'] = GetLang('N/A');
			}
			$GLOBALS['Status'] = ($userdetails['status'] == 1) ? GetLang('Active') : GetLang('Inactive');

			$usertype = $user->GetAdminType($userdetails['admintype']);
			if ($usertype == 'c') {
				$usertype = 'RegularUser';
			}

			$GLOBALS['UserType'] = GetLang('AdministratorType_' . $usertype);

			$statsdisplay .= $this->ParseTemplate('Stats_Users_Manage_Row', true, false);
		}
		$stats_manage = str_replace('%%TPL_Stats_Users_Manage_Row%%', $statsdisplay, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging%%', $paging, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $stats_manage);

		echo $stats_manage;
	}

	/**
	* PrintUserStats_Step2
	* Prints user statistics for the selected user.
	* If the user is not a useradmin, then this also checks to make sure they are only checking their own statistics. If the userid's don't match, then it prints an error message and they can't go any further.
	*
	* @param Int $userid The userid to print statistics for.
	*
	* @return Void Prints out the list of statistics for this particular user. It doesn't return anything.
	*/
	function PrintUserStats_Step2($userid=0)
	{
		$this->LoadLanguageFile('Users');

		$userid = (int)$userid;

		$GLOBALS['Heading'] = GetLang('Stats_Users_Step3_Heading');

		$user = IEM::userGetCurrent();
		if (!$user->UserAdmin()) {
			if ($userid != $user->Get('userid')) {
				$GLOBALS['ErrorMessage'] = GetLang('Stats_Unknown_User');
				$this->DenyAccess();
				return;
			}
		}

		if ($user->Get('userid') != $userid) {
			$stats_user = $this->GetApi('User');
			$stats_user->Load($userid, false);
		} else {
			$stats_user = $user;
		}

		// Log this to "User Activity Log"
		IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/chart_bar.gif', $stats_user->username);

		$name = $stats_user->Get('username');
		$fullname = $stats_user->Get('fullname');
		if ($fullname) {
			$name = $fullname;
		}

		$statsapi = $this->GetApi();

		$GLOBALS['SummaryIntro'] = sprintf(GetLang('Stats_Users_Step3_Intro'), htmlentities($name, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['UserCreateDate'] = $this->PrintTime($stats_user->Get('createdate'), true);

		$lastlogindate = $stats_user->Get('lastloggedin');
		if ($lastlogindate == 0) {
			$GLOBALS['LastLoggedInDate'] = GetLang('UserHasNotLoggedIn');
		} else {
			$GLOBALS['LastLoggedInDate'] = $this->PrintTime($lastlogindate, true);
		}

		$last_newsletter_sent = $statsapi->GetLastNewsletterSent($userid);

		if ($last_newsletter_sent == 0) {
			$GLOBALS['LastNewsletterSentDate'] = GetLang('UserHasNotSentAnyNewsletters');
		} else {
			$GLOBALS['LastNewsletterSentDate'] = $this->PrintTime($last_newsletter_sent, true);
		}

		$list_count = $statsapi->GetUserMailingLists($userid);
		$GLOBALS['ListsCreated'] = $this->FormatNumber($list_count);

		$autoresponder_count = $statsapi->GetUserAutoresponders($userid);
		$GLOBALS['AutorespondersCreated'] = $this->FormatNumber($autoresponder_count);

		$calendar_dates = $user->GetSettings('CalendarDates');

		$restrictions = '';
		if (isset($calendar_dates['usersummary'])) {
			$restrictions = $calendar_dates['usersummary'];
		}

		$statsapi->CalculateStatsType();

		$user_stats = $statsapi->GetUserNewsletterStats($userid);

		$data = $statsapi->GetUserSendSummary($userid, $statsapi->stats_type, $restrictions);

		$GLOBALS['NewslettersSent'] = $this->FormatNumber($user_stats['newsletters_sent']);
		$GLOBALS['EmailsSent'] = $this->FormatNumber($user_stats['total_emails_sent']);
		$GLOBALS['TotalBounces'] = $this->FormatNumber($user_stats['total_bounces']);
		$GLOBALS['UniqueOpens'] = $this->FormatNumber($user_stats['unique_opens']);
		$GLOBALS['TotalOpens'] = $this->FormatNumber($user_stats['total_opens']);

		include(dirname(__FILE__) . '/amcharts/amcharts.php');

		$unopened = $user_stats['total_emails_sent'] - $user_stats['unique_opens'] - $user_stats['total_bounces'];
		if ($unopened < 0) {
			$unopened = 0;
		}

		$data_url = 'stats_chart.php?Heading=User&Opens=' . $user_stats['unique_opens'] . '&Unopened=' . $unopened . '&Bounced=' . $user_stats['total_bounces'] . '&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		$GLOBALS['SummaryChart'] = InsertChart('pie',$data_url,array('graph_title' => GetLang("User_Summary_Graph")));

		$GLOBALS['EmailsSentIntro'] = sprintf(GetLang('Stats_Users_Step3_EmailsSent_Intro'), htmlentities($name, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage'])) ? (int)$_GET['DisplayPage'] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$base_action = '&SubAction=User&NextAction=Step2&user=' . $userid . '&tab=2';

		$calendar_restrictions = $this->CalendarRestrictions['usersummary'];

		$GLOBALS['TabID'] = '2';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=User&SubAction=Step2&User=' . $userid . '&tab=2';

		$this->SetupPaging(0, $DisplayPage, $perpage);

		$paging = $this->ParseTemplate('Paging', true, false);

		$GLOBALS['Paging'] = $paging;

		IEM::sessionSet('userchart_data', $data);

		$this->DisplayChart('UserChart', 'user', $userid, 'column', array('graph_title' => GetLang("User_Email_Campaigns_Sent_Graph")));

		$now = getdate();
		$today = $now['0'];

		$this_year = date('Y');

		$time_display = '';

		$total_sent = 0;

		/**
		 * "convert" data to optimize performance
		 */
			$convertedData = array();
			foreach ($data as $row) {
				if (array_key_exists('hr', $row)) {
					$convertedData[$row['hr']] = $row;
				} elseif (array_key_exists('dow', $row)) {
					$convertedData[$row['dow']] = $row;
				} elseif (array_key_exists('dom', $row)) {
					$convertedData[$row['dom']] = $row;
				} else {
					if (!array_key_exists($row['yr'], $convertedData)) {
						$convertedData[$row['yr']] = array();
					}

					$convertedData[$row['yr']][$row['mth']] = $row;
				}
			}
		/**
		 * -----
		 */

		switch ($statsapi->calendar_type) {
			case 'today':
			case 'yesterday':
				for ($i = 0; $i < 24; $i++) {

					$GLOBALS['Name'] = $this->PrintDate(mktime($i, 1, 1, 1, 1, $this_year), GetLang('Daily_Time_Display'));
					$GLOBALS['Count'] = 0;

					if (array_key_exists($i, $convertedData)) {
						$GLOBALS['Count'] = $this->FormatNumber($convertedData[$i]['count']);
						$total_sent += $convertedData[$i]['count'];
					}

					$time_display .= $this->ParseTemplate('Stats_Users_SendSummary_Step3_Row', true, false);
				}
			break;

			case 'last24hours':
				$hours_now = $now['hours'];

				$i = 24;
				while ($i > 0) {
					$yr = mktime($hours_now, 1, 1, 1, 1, $this_year);
					$GLOBALS['Name'] = $this->PrintDate($yr, GetLang('Daily_Time_Display'));

					$hour_check = date('G', $yr);

					$GLOBALS['Count'] = 0;

					if (array_key_exists($hour_check, $convertedData)) {
						$GLOBALS['Count'] = $this->FormatNumber($convertedData[$hour_check]['count']);
						$total_sent += $convertedData[$hour_check]['count'];
					}

					$time_display .= $this->ParseTemplate('Stats_Users_SendSummary_Step3_Row', true, false);

					$hours_now--;

					$i--;
				}
			break;

			case 'last7days':
				$date = $today;
				$i = 7;

				while ($i > 0) {
					$GLOBALS['Name'] = $this->PrintDate($date, GetLang('Date_Display_Display'));
					$GLOBALS['Count'] = 0;

					$dow = date('w', $date);

					if (array_key_exists($dow, $convertedData)) {
						$GLOBALS['Count'] = $this->FormatNumber($convertedData[$dow]['count']);
						$total_sent += $convertedData[$dow]['count'];
					}

					$time_display .= $this->ParseTemplate('Stats_Users_SendSummary_Step3_Row', true, false);

					$date = $date - 86400; // take off one day each time.

					$i--;
				}
			break;

			case 'last30days':
				$date = $today;

				$i = 30;
				while ($i > 0) {
					$GLOBALS['Name'] = $this->PrintDate($date);
					$GLOBALS['Count'] = 0;

					$day = date('j', $date);

					if (array_key_exists($day, $convertedData)) {
						$GLOBALS['Count'] = $this->FormatNumber($convertedData[$day]['count']);
						$total_sent += $convertedData[$day]['count'];
					}

					$time_display .= $this->ParseTemplate('Stats_Users_SendSummary_Step3_Row', true, false);

					$date = $date - 86400; // take off one day each time.

					$i--;
				}
			break;

			case 'thismonth':
			case 'lastmonth':
				if ($statsapi->calendar_type == 'thismonth') {
					$month = $now['mon'];
				} else {
					$month = $now['mon'] - 1;
				}

				$timestamp = mktime(1, 1, 1, $month, 1, $now['year']);

				$days_of_month = date('t', $timestamp);

				for ($i = 1; $i <= $days_of_month; $i++) {
					$GLOBALS['Name'] = $this->PrintDate($timestamp);
					$GLOBALS['Count'] = 0;

					if (array_key_exists($i, $convertedData)) {
						$GLOBALS['Count'] = $this->FormatNumber($convertedData[$i]['count']);
						$total_sent += $convertedData[$i]['count'];
					}

					$time_display .= $this->ParseTemplate('Stats_Users_SendSummary_Step3_Row', true, false);

					$timestamp += 86400;
				}
			break;

			default:
				$request = $user->GetSettings('Calendar');
				if (!isset($request['DateType'])) {
					$request['DateType'] = '';
				}

				if ($request['DateType'] == 'Custom') {
					$tempYear = intval($request['To']['Yr']);
					$tempMonth = intval($request['To']['Mth']);

					$durationYear = intval($request['To']['Yr']) - intval($request['From']['Yr']);
					$rows = (intval($request['To']['Mth']) + ($durationYear * 12)) - intval($request['From']['Mth']) + 1;
				} else {
					$tempYear = intval(date('Y', $today));
					$tempMonth = intval(date('n', $today));

					$rows = 12;

					if (!empty($convertedData)) {
						$temp = array_keys($convertedData);
						sort($temp, SORT_NUMERIC);
						$temp_min_year = intval(array_shift($temp));
						$temp = array_keys($convertedData[$temp_min_year]);
						sort($temp, SORT_NUMERIC);
						$temp_min_month = intval(array_shift($temp));

						$duration_year = $tempYear - $temp_min_year;
						$rows = ($tempMonth + ($duration_year * 12)) - $temp_min_month + 1;

						if ($rows > 100) {
							$rows = 100;
						}
					}
				}


				while ($rows > 0) {
					$GLOBALS['Name'] = GetLang($this->Months[$tempMonth]) . ' ' . $tempYear;
					$GLOBALS['Count'] = 0;

					if (array_key_exists($tempYear, $convertedData)) {
						if (array_key_exists($tempMonth, $convertedData[$tempYear])) {
							$GLOBALS['Count'] = $this->FormatNumber($convertedData[$tempYear][$tempMonth]['count']);
							$total_sent += $convertedData[$tempYear][$tempMonth]['count'];
						}
					}

					$time_display .= $this->ParseTemplate('Stats_Users_SendSummary_Step3_Row', true, false);

					if ($tempMonth == 1) {
						$tempMonth = 12;
						--$tempYear;
					} else {
						--$tempMonth;
					}

					--$rows;
				}
			break;
		}

		if ($total_sent <= 0) {
			if ($statsapi->calendar_type == 'alltime') {
				$GLOBALS['Error'] = GetLang('UserHasNotSentAnyNewsletters');
			} else {
				$GLOBALS['Error'] = GetLang('UserHasNotSentAnyNewsletters_SelectedDateRange');
			}
			$GLOBALS['UserHasNotSentAnyNewsletters'] = $this->ParseTemplate('ErrorMsg', true, false);
			$GLOBALS['UsersSummaryPage'] = $this->ParseTemplate('Stats_Users_Sendsummary_Step3_Empty', true, false);
		} else {

			$GLOBALS['TotalEmailsSent'] = $this->FormatNumber($total_sent);

			$GLOBALS['Stats_Step3_EmailsSent_List'] = $time_display;
			$GLOBALS['UsersSummaryPage'] = $this->ParseTemplate('Stats_Users_Sendsummary_Step3', true, false);
		}

		// finally put it all together.
		$page = $this->ParseTemplate('Stats_Users_Step3', true, false);

		if (isset($_GET['tab'])) {
			$page .= '
			<script>
				ShowTab(' . $_GET['tab'] . ');
			</script>
			';
		}
		echo $page;

	}

	/**
	* CalculateCalendarRestrictions
	* Returns a partial query which can be appended to an existing query to restrict searching to the dates you have searched before (which are retrieved from the session).
	*
	* @param Array $calendarinfo Pass in calendar info if you want to use that instead of the session information.
	* @param Boolean $enddateonly  Whether to only return the end-date. This is used for campaigns so we can calculate the number of days since the start of a campaign properly. Returns it as an integer (epoch time).
	*
	* @see User::GetSettings
	* @see Campaigns::Process
	* @see ViewAll_Campaigns::Process
	*
	* @return String The partial query to be appended or the end date (as an int) depending on the second parameter.
	*/
	function CalculateCalendarRestrictions($calendarinfo=array(), $enddateonly=false)
	{
		$user = IEM::userGetCurrent();

		if (!$calendarinfo) {
			$calendar_settings = $user->GetSettings('Calendar');
		} else {
			$calendar_settings = $calendarinfo;
		}

		if (!isset($calendar_settings['DateType'])) {
			$calendar_settings['DateType'] = 'AllTime';
		}

		$calendar_settings['DateType'] = strtolower($calendar_settings['DateType']);

		$rightnow = AdjustTime(0, true);
		$stats_api = $this->GetApi('Stats');
		$now_ts = $stats_api->GetServerTime();

		$user_date = explode(' ',AdjustTime($rightnow, false, 'j H i s', true));
		$user_time = $user_date[1]*3600 + $user_date[2]*60 + $user_date[3];
		$today = $now_ts - $user_time;
		$yesterday = $today - 86400;
		$this_month = $now_ts - $user_date[0]*86400 - $user_time;
		$last_month = $this_month - AdjustTime($this_month - 86400,false,'j',true)*86400;

		switch ($calendar_settings['DateType']) {
			case 'today':
				$query = ' AND (%%TABLE%% >= ' . $today . ')';
				$enddate = $today + 86400;
			break;

			case 'yesterday':
				$query = ' AND (%%TABLE%% >= ' . $yesterday . ' AND %%TABLE%% < ' . $today . ')';
				$enddate = $today;
			break;

			case 'last24hours':
				$enddate = $now_ts - 86400;

				// since "rightnow" is already adjusted, we don't need to adjust it again.

				$query = ' AND (%%TABLE%% >= ' . $enddate . ' AND %%TABLE%% < ' . $rightnow . ')';

			break;

			case 'last7days':
				$time = $today - 6*86400;

				$query = ' AND (%%TABLE%% >= ' . $time . ')';

				$enddate = $rightnow;
			break;

			case 'last30days':
				$time = $today - 29*86400;
				$query = ' AND (%%TABLE%% >= ' . $time . ')';
				$enddate = $rightnow;
			break;

			case 'thismonth':
				$query = ' AND (%%TABLE%% >= ' . $this_month . ')';
				$enddate = $rightnow;
			break;

			case 'lastmonth':
				$query = ' AND (%%TABLE%% >= ' . $last_month . ' AND %%TABLE%% < ' . $this_month . ')';
				$enddate = $this_month;
			break;

			case 'custom':
				$fromdate = AdjustTime(array(0, 0, 0, $calendar_settings['From']['Mth'], $calendar_settings['From']['Day'], $calendar_settings['From']['Yr']), true);

				// for the "to" part, we want the start of the next day.
				// so if you put From 1/1/04 and To 1/1/04 - it actually finds records from midnight 1/1/04 until 23.59.59 1/1/04 (easier to get the next day and make it before then)..
				$todate = AdjustTime(array(0, 0, 0, $calendar_settings['To']['Mth'], ($calendar_settings['To']['Day']+1), $calendar_settings['To']['Yr']), true);

				$query = ' AND (%%TABLE%% >= ' . $fromdate . ' AND %%TABLE%% < ' . $todate . ')';
				$enddate = $todate;
			break;

			case 'alltime':
			default:
				$query = '';
			break;
		}

		$queries = array(
			'opens' => str_replace('%%TABLE%%', 'opentime', $query),
			'clicks' => str_replace('%%TABLE%%', 'clicktime', $query),
			'forwards' => str_replace('%%TABLE%%', 'forwardtime', $query),
			'bounces' => str_replace('%%TABLE%%', 'bouncetime', $query),
			'unsubscribes' => str_replace('%%TABLE%%', 'unsubscribetime', $query),
			'recipients' => str_replace('%%TABLE%%', 'sendtime', $query),
		);
		$queries['subscribers']['subscribes'] = str_replace('%%TABLE%%', 'subscribedate', $query);
		$queries['subscribers']['unsubscribes'] = str_replace('%%TABLE%%', 'unsubscribetime', $query);
		$queries['subscribers']['bounces'] = str_replace('%%TABLE%%', 'bouncetime', $query);
		$queries['subscribers']['forwards'] = str_replace('%%TABLE%%', 'forwardtime', $query);

		$queries['usersummary'] = str_replace('%%TABLE%%', 'sendtime', $query);

		if ($enddateonly) {
			return $enddate;
		}
		$this->CalendarRestrictions = $queries;
	}

	/**
	* SetupCalendar
	* This sets up the calendar according to what's already been shown. This way, the calendar is persistent across all pages.
	* It sets up the global variables ready for it to be parsed and printed.
	*
	* @param String $formaction The formaction for the calendar to use.
	* @param Array $calendarinfo An array of calendar information to use when setting up the calendar. If this is not present, calendar information is used from the session.
	*
	* @see ParseTemplate
	* @see User::GetSettings
	* @see GetLang
	*
	* @return Void Doesn't return anything, sets up global variables and the global calendar.
	*/
	function SetupCalendar($formaction=null, $calendarinfo=array())
	{
		unset($GLOBALS['PAGE']);

		$thisuser = IEM::userGetCurrent();
		if (!empty($calendarinfo)) {
			$calendar_settings = $calendarinfo;
			$thisuser->SetSettings($calendarinfo);
			$thisuser->SaveSettings();
		} else {
			$calendar_settings = $thisuser->GetSettings('Calendar');
		}

		$thisyear = date('Y');

		if (empty($calendar_settings)) {
			$from = strtotime('1 month ago');
			$to = time();
			$calendar_settings = array('DateType' => 'AllTime');

			$calendar_settings['From'] = array(
				'Day'	=> date('j', $from),
				'Mth'	=> date('n', $from),
				'Yr'	=> date('Y', $from)
			);

			$calendar_settings['To'] = array(
				'Day'	=> date('j', $to),
				'Mth'	=> date('n', $to),
				'Yr'	=> date('Y', $to)
			);
		}

		$date_options = array('Today', 'Yesterday', 'Last24Hours', 'Last7Days', 'Last30Days', 'ThisMonth', 'LastMonth', 'AllTime', 'Custom');

		$date_format = GetLang('DateFormat');
		$time_format = GetLang('TimeFormat');

		$viewing_results_for = GetLang('ViewingResultsFor');
		$datetoshow = $viewing_results_for . ' ';

		$timenow = AdjustTime(0, true);

		switch ($calendar_settings['DateType']) {
			case 'Today':
				$datetoshow .= $this->PrintDate($timenow, $date_format);
			break;

			case 'Yesterday':
				$datetoshow .= $this->PrintDate($timenow - 24*3600, $date_format);
			break;

			case 'Last24Hours':
				$tf_hours_ago = $timenow - 86400;
				$datetoshow .= '<br/>&nbsp;&nbsp;' . $this->PrintDate($tf_hours_ago, $time_format) . ' - ' . $this->PrintDate($timenow, $time_format);
			break;

			case 'Last7Days':
				$seven_daysago = AdjustTime(array(0, 0, 0, date('m'), date('d') - 6, date('Y')), true, null, true);
				$datetoshow .= $this->PrintDate($seven_daysago, $date_format);
				$datetoshow .= ' - ' . $this->PrintDate($timenow, $date_format);
			break;

			case 'Last30Days':
				$thirty_daysago = AdjustTime(array(0, 0, 0, date('m'), date('d') - 29, date('Y')), true, null, true);
				$datetoshow .= $this->PrintDate($thirty_daysago, $date_format);
				$datetoshow .= ' - ' . $this->PrintDate($timenow, $date_format);
			break;

			case 'ThisMonth':
				$startofmonth = AdjustTime(array(0, 0, 0, date('m'), 1, date('Y')), true, null, true);
				$datetoshow .= $this->PrintDate($startofmonth, $date_format);
				$datetoshow .= ' - ' . $this->PrintDate($timenow, $date_format);
			break;

			case 'LastMonth':
				$lastmonth = AdjustTime(array(0, 0, 0, date('m')-1, 1, date('Y')), true, null, true);
				$thismonth = AdjustTime(array(0, 0, 0, date('m'), 1, date('Y')), true, null, true);
				$datetoshow .= $this->PrintDate($lastmonth, $date_format);
				$datetoshow .= ' - ' . $this->PrintDate($thismonth, $date_format);
			break;

			case 'AllTime':
				$datetoshow = '';
			break;

			case 'Custom':
				$start = AdjustTime(array(0, 0, 0, $calendar_settings['From']['Mth'], $calendar_settings['From']['Day'], $calendar_settings['From']['Yr']), true);
				$end = AdjustTime(array(0, 0, 0, $calendar_settings['To']['Mth'], $calendar_settings['To']['Day'], $calendar_settings['To']['Yr']), true);
				$datetoshow .= $this->PrintDate($start, $date_format) . ' - ' . $this->PrintDate($end, $date_format);
			break;
		}

		$calendar_options = '';
		$CustomDateDisplay = 'none';
		$ShowDateDisplay = '';

		foreach ($date_options as $option) {
			$calendar_options .= '<option value="' . $option . '"';
			if ($calendar_settings['DateType'] == $option) {
				$calendar_options .= ' SELECTED';
			}
			$calendar_options .= '>' . GetLang($option) . '</option>';
		}

		if ($calendar_settings['DateType'] == 'Custom') {
			$CustomDateDisplay = '';
			$ShowDateDisplay = 'none';
		}

		if ($calendar_settings['DateType'] == 'AllTime') {
			$ShowDateDisplay = 'none';
		}

		// first we do the "From" stuff.
		$CustomDayFrom = '';
		for ($i = 1; $i <= 31; $i++) {
			$CustomDayFrom .= '<option value="' . $i . '"';
			if ($i == $calendar_settings['From']['Day']) {
				$CustomDayFrom .= ' SELECTED';
			}
			$CustomDayFrom .= '>' . $i . '</option>';
		}
		$CustomDayFrom .= '';

		$CustomMthFrom = '';
		for ($i = 1; $i <= 12; $i++) {
			$CustomMthFrom .= '<option value="' . $i . '"';
			if ($i == $calendar_settings['From']['Mth']) {
				$CustomMthFrom .= ' SELECTED';
			}
			$CustomMthFrom .= '>' . GetLang($this->Months[$i]) . '</option>';
		}
		$CustomMthFrom .= '</select>';

		$CustomYrFrom = '';
		for ($i = ($thisyear - 2); $i <= ($thisyear + 5); $i++) {
			$CustomYrFrom .= '<option value="' . $i . '"';
			if ($i == $calendar_settings['From']['Yr']) {
				$CustomYrFrom .= ' SELECTED';
			}
			$CustomYrFrom .= '>' . $i . '</option>';
		}
		$CustomYrFrom .= '';

		// now we do the "To" stuff.
		$CustomDayTo = '';
		for ($i = 1; $i <= 31; $i++) {
			$CustomDayTo .= '<option value="' . $i . '"';
			if ($i == $calendar_settings['To']['Day']) {
				$CustomDayTo .= ' SELECTED';
			}
			$CustomDayTo .= '>' . $i . '</option>';
		}
		$CustomDayTo .= '';

		$CustomMthTo = '';
		for ($i = 1; $i <= 12; $i++) {
			$CustomMthTo .= '<option value="' . $i . '"';
			if ($i == $calendar_settings['To']['Mth']) {
				$CustomMthTo .= ' SELECTED';
			}
			$CustomMthTo .= '>' . GetLang($this->Months[$i]) . '</option>';
		}
		$CustomMthTo .= '';

		$CustomYrTo = '';
		for ($i = ($thisyear - 2); $i <= ($thisyear + 5); $i++) {
			$CustomYrTo .= '<option value="' . $i . '"';
			if ($i == $calendar_settings['To']['Yr']) {
				$CustomYrTo .= ' SELECTED';
			}
			$CustomYrTo .= '>' . $i . '</option>';
		}
		$CustomYrTo .= '';

		$GLOBALS['CustomDayFrom'] = $CustomDayFrom;
		$GLOBALS['CustomMthFrom'] = $CustomMthFrom;
		$GLOBALS['CustomYrFrom'] = $CustomYrFrom;

		$GLOBALS['CustomDayTo'] = $CustomDayTo;
		$GLOBALS['CustomMthTo'] = $CustomMthTo;
		$GLOBALS['CustomYrTo'] = $CustomYrTo;

		$GLOBALS['ShowDateDisplay'] = $ShowDateDisplay;
		$GLOBALS['CustomDateDisplay'] = $CustomDateDisplay;
		$GLOBALS['CalendarOptions'] = $calendar_options;

		$GLOBALS['DateRange'] = $datetoshow;

		if (is_null($formaction)) {
			$GLOBALS['FormAction'] = 'Action=ProcessDate';
		} else {
			$GLOBALS['FormAction'] = $formaction;
		}
		$GLOBALS['Calendar'] = $this->ParseTemplate('calendar', true, false);
	}

	/**
	* GetCalendarInfo
	* Gets calendar information from the array passed in, makes it 'human-readable'.
	*
	* @param Array $calendar An array of calendar settings to process.
	* @param Boolean $dateonly Whether to get the date only (ignore whether it's yesterday, today etc).
	*
	* @return String The calendar date.
	*/
	function GetCalendarInfo($calendar=array(), $dateonly=false)
	{
		$thisuser = IEM::userGetCurrent();
		if (!empty($calendar)) {
			$calendar_settings = $calendar;
		} else {
			$calendar_settings = $thisuser->GetSettings('Calendar');
		}

		$date_format = GetLang('DateFormat');

		$timenow = time();
		$timenow = AdjustTime($timenow);

		switch ($calendar_settings['DateType']) {
			case 'Yesterday':
				$yesterday = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
				$datetoshow = $this->PrintDate(AdjustTime($yesterday));
			break;

			case 'Last24Hours':
			case 'Today':
				$datetoshow = $this->PrintDate($timenow);
			break;

			case 'Last7Days':
				$seven_daysago = mktime(0, 0, 0, date('m'), date('d') - 7, date('Y'));
				$datetoshow = $this->PrintDate(AdjustTime($seven_daysago));
				$datetoshow .= ' - ' . $this->PrintDate($timenow);
			break;

			case 'Last30Days':
				$thirty_daysago = mktime(0, 0, 0, date('m'), date('d') - 30, date('Y'));
				$datetoshow = $this->PrintDate($thirty_daysago);
				$datetoshow .= ' - ' . $this->PrintDate($timenow);
			break;

			case 'ThisMonth':
				$startofmonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
				$datetoshow = $this->PrintDate(AdjustTime($startofmonth));
				$datetoshow .= ' - ' . $this->PrintDate($timenow);
			break;

			case 'Custom':
				$start = mktime(0, 0, 0, date($calendar_settings['From']['Mth']), date($calendar_settings['From']['Day']), date($calendar_settings['From']['Yr']));
				$end = mktime(0, 0, 0, date($calendar_settings['To']['Mth']), date($calendar_settings['To']['Day']), date($calendar_settings['To']['Yr']));
				$datetoshow = $this->PrintDate(AdjustTime($start)) . ' - ' . $this->PrintDate(AdjustTime($end));
			break;

			case 'LastMonth':
				$lastmonth = mktime(0, 0, 0, date('m')-1, 1, date('Y'));
				$thismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
				$datetoshow = $this->PrintDate(AdjustTime($lastmonth));
				$datetoshow .= ' - ' . $this->PrintDate(AdjustTime($thismonth));
			break;

			case 'AllTime':
				$datetoshow = GetLang('AllTime');
			break;

		}

		if ($dateonly) {
			return $datetoshow;
		}

		$datetype = $calendar_settings['DateType'];

		if ($datetype == 'Custom' || $datetype == 'AllTime') {
			$readableformat = $datetoshow;
		} else {
			$readableformat = GetLang($datetype) . ' (' . $datetoshow . ')';
		}
		return GetLang('DateRange') . ': ' . $readableformat;
	}

	/**
	* DisplayChart
	* This sets up the chart in the tab ready for displaying.
	* It simply sets up the chart_url based on the criteria passed in and sets the global placeholder for the other functions to parse and display.
	*
	* @param String $chartname Name of the global variable chart placeholder.
	* @param String $chart_area The area we're viewing (eg unsubscribes, forwards).
	* @param Int $statid This is passed to the stats_chart.php file for loading / processing.
	* @param Int $listid The list to pass to stats_chart.php. This is only supplied by list unsubscribes.
	*
	* @see stats_chart.php
	*
	* @return Void Doesn't return anything - sets up the global placeholder ready for replacement.
	*/
	function DisplayChart($chartname='', $chart_area='', $statid=0, $type = 'pie', $settings = null, $listid=0)
	{
		// explicitly pass the sessionid across to the chart
		// since it's not the browser but the server making this request, it may get a different session id if we don't, which then means it can't load the data properly.
		// especially applies to windows servers.

		$data_url = 'stats_chart.php?graph=' . urlencode(strtolower($chartname)) . '&Area='.urlencode(strtolower($chart_area)) . '&statid=' . (int)$statid . '&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		$listid = (int)$listid;
		if ($listid > 0) {
			$data_url .= '&List=' . $listid;
		}

		$GLOBALS[$chartname] = InsertChart($type,$data_url,$settings);
	}

	/**
	* PrintAutoresponderStats_Step1
	* This prints out a list of autoresponders whether they have statistics or not.
	* If there are no autoresponders, it will show an error message and give them the option to create one.
	*
	* @param String $msg A display message from an action performed. This is used if you delete all statistics.
	*
	* @return Void Doesn't return anything. Prints a list of autoresponders to choose from if there are some available, or if there are none, it will check user permissions to see if they can create an autoresponder and print an appropriate message.
	*/
	function PrintAutoresponderStats_Step1($msg='')
	{
		$user = IEM::userGetCurrent();
		$lists = $user->GetLists();
		$statsapi = $this->GetApi('Stats');

		$listids = array_keys($lists);

		$perpage = $this->GetPerPage();

		$DisplayPage = $this->GetCurrentPage();

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$NumberOfStats = $statsapi->GetAutoresponderStats($listids, $sortinfo, true, 0, 0);
		$mystats = $statsapi->GetAutoresponderStats($listids, $sortinfo, false, $start, $perpage);

		$GLOBALS['Message'] = $msg;

		if ($NumberOfStats == 0) {
			$autoresponder_api = $this->GetApi('Autoresponders');
			$auto_count = $autoresponder_api->GetAutoresponders($listids, array(), true);
			if ($auto_count == 0) {
				if ($user->HasAccess('Autoresponders', 'Create')) {
					$GLOBALS['Autoresponders_AddButton'] = $this->ParseTemplate('Autoresponder_Create_Button', true, false);
					$GLOBALS['Message'] = $this->PrintSuccess('NoAutoresponders', GetLang('AutoresponderCreate'));
				} else {
					$GLOBALS['Message'] .= $this->PrintSuccess('NoAutoresponders', '');
				}
			} else {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoAutorespondersHaveBeenSent', '');
			}

			$this->ParseTemplate('Stats_Autoresponders_Empty');
			return;
		}

		$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=Autoresponders&NextAction=Step1';

		$GLOBALS['PAGE'] = 'Stats&Action=Autoresponders&SubAction=Step1';

		$this->SetupPaging($NumberOfStats, $DisplayPage, $perpage);

		$paging = $this->ParseTemplate('Paging', true, false);

		$stats_manage = $this->ParseTemplate('Stats_Autoresponders_Manage', true, false);

		$statsdisplay = '';

		foreach ($mystats as $pos => $statsdetails) {
			$GLOBALS['StatID'] = $statsdetails['statid'];
			$GLOBALS['AutoresponderID'] = $statsdetails['autoresponderid'];
			$GLOBALS['Autoresponder'] = htmlspecialchars($statsdetails['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['MailingList'] = htmlspecialchars($statsdetails['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET);

			$GLOBALS['StatsAction'] = '<a href="index.php?Page=Stats&Action=Autoresponders&SubAction=ViewSummary&auto=' . $statsdetails['autoresponderid'] . '">' . GetLang('ViewSummary') . '</a>&nbsp;&nbsp;';

                        $GLOBALS['StatsAction'] .= '<a href="remote_stats.php?height=420&width=420&overflow=none&statstype=a&Action=print&stats%5B%5D=' . $statsdetails['autoresponderid'] . '" class="thickbox" title="' . GetLang('PrintAutoresponderStatistics') . '">' . GetLang('Print_Stats_Selected') . '</a>&nbsp;&nbsp;';

			if ($statsdetails['statid'] > 0) {
				$GLOBALS['StatsAction'] .= '<a href="javascript:ConfirmDelete(' . $statsdetails['statid'] . ');">' . GetLang('Delete') . '</a>';
			} else {
				$GLOBALS['StatsAction'] .= '<span class="disabled" title="' . GetLang('NoStatisticsToDelete') . '">' . GetLang('Delete') . '</a>';
			}

			$bounce_count = $statsdetails['bouncecount_soft'] + $statsdetails['bouncecount_hard'] + $statsdetails['bouncecount_unknown'];

			if ($statsdetails['hoursaftersubscription'] < 1) {
				$GLOBALS['SentWhen'] = GetLang('Immediately');
			} else {
				if ($statsdetails['hoursaftersubscription'] == 1) {
					$GLOBALS['SentWhen'] = GetLang('HoursAfter_One');
				} else {
					$GLOBALS['SentWhen'] = sprintf(GetLang('HoursAfter_Many'), $statsdetails['hoursaftersubscription']);
				}
			}

			$GLOBALS['TotalRecipients'] = $this->FormatNumber($statsdetails['sendsize']);
			$GLOBALS['BounceCount'] = $this->FormatNumber($bounce_count);
			$GLOBALS['UnsubscribeCount'] = $this->FormatNumber($statsdetails['unsubscribecount']);

			$statsdisplay .= $this->ParseTemplate('Stats_Autoresponders_Manage_Row', true, false);
		}
		$stats_manage = str_replace('%%TPL_Stats_Autoresponders_Manage_Row%%', $statsdisplay, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging%%', $paging, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $stats_manage);

		echo $stats_manage;

	}

	/**
	* PrintAutoresponderStats_Step2
	* This displays summary information for an autoresponder based on the autoresponder passed in. This sets up the other tabs (opens, bounces, links and so on) as well but this particular function mainly sets up the summary page.
	*
	* @param Int $autoresponderid The autoresponderid to get information for.
	*
	* @see Stats_API::GetAutoresponderSummary
	* @see DisplayAutoresponderOpens
	* @see DisplayAutoresponderLinks
	* @see DisplayAutoresponderBounces
	* @see DisplayAutoresponderUnsubscribes
	* @see DisplayAutoresponderForwards
	* @see DisplayAutoresponderRecipients
	*
	* @return Void Doesn't return anything - just prints out the summary information.
	*/
	function PrintAutoresponderStats_Step2($autoresponderid=0)
	{

		include(dirname(__FILE__) . '/amcharts/amcharts.php');

		$perpage = $this->GetPerPage();

		// this is all for the summary page.
		$autoresponderid = (int)$autoresponderid;

		$GLOBALS['AutoresponderID'] = $autoresponderid;

		$statsapi = $this->GetApi('Stats');

		$summary = $statsapi->GetAutoresponderSummary($autoresponderid, true, $perpage);

		// Log this to "User Activity Log"
		IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/chart_bar.gif', $summary['autorespondername']);

		$GLOBALS['SummaryIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_Summary'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['AutoresponderName'] = htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['AutoresponderSubject'] = htmlspecialchars($summary['autorespondersubject'], ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['UserEmail'] = $summary['emailaddress'];
		$created_by = $summary['username'];
		if ($summary['fullname']) {
			$created_by = $summary['fullname'];
		}
		$GLOBALS['CreatedBy'] = htmlspecialchars($created_by, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['MailingList'] = htmlspecialchars($summary['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET);

		if ($summary['hoursaftersubscription'] < 1) {
			$GLOBALS['SentWhen'] = GetLang('Immediately');
		} else {
			if ($summary['hoursaftersubscription'] == 1) {
				$GLOBALS['SentWhen'] = GetLang('HoursAfter_One');
			} else {
				$GLOBALS['SentWhen'] = sprintf(GetLang('HoursAfter_Many'), $summary['hoursaftersubscription']);
			}
		}

		$total_sent = $summary['htmlrecipients'] + $summary['textrecipients'] + $summary['multipartrecipients'];
		$GLOBALS['SentToDetails'] = $this->FormatNumber($total_sent);

		$GLOBALS['UniqueOpens'] = sprintf(GetLang('EmailOpens_Unique'), $this->FormatNumber($summary['emailopens_unique']));
		$GLOBALS['TotalOpens'] = sprintf(GetLang('EmailOpens_Total'), $this->FormatNumber($summary['emailopens']));

		$total_bounces = $summary['bouncecount_unknown'] + $summary['bouncecount_hard'] + $summary['bouncecount_soft'];

		$GLOBALS['TotalBounces'] = $this->FormatNumber($total_bounces);

		// now for the opens page.
		// by default this is for all opens, not unique opens.
		$only_unique = false;
		if (isset($_GET['Unique'])) {
			$only_unique = true;
		}

		if (!isset($_GET['Unique'])) {
			$GLOBALS['OpensURL'] = 'javascript: void(0);" onclick="ShowTab(2);';

			$GLOBALS['UniqueOpensURL'] = 'index.php?Page=Stats&Action=Autoresponders&SubAction=ViewSummary&auto=' . $autoresponderid . '&tab=2&Unique';
		} else {
			$GLOBALS['UniqueOpensURL'] = 'javascript: void(0);" onclick="ShowTab(2);';

			$GLOBALS['OpensURL'] = 'index.php?Page=Stats&Action=Autoresponders&SubAction=ViewSummary&auto=' . $autoresponderid . '&tab=2';
		}

		$chosen_link = 'a';
		if (isset($_GET['link'])) {
			if (is_numeric($_GET['link'])) {
				$chosen_link = $_GET['link'];
			}
		}

		$chosen_bounce_type = '';
		if (isset($_GET['bouncetype'])) {
			$chosen_bounce_type = $_GET['bouncetype'];
		}

		$statid = $summary['statid'];

		$GLOBALS['OpensPage'] = $this->DisplayAutoresponderOpens($statid, $autoresponderid, $summary, $only_unique);

		$GLOBALS['LinksPage'] = $this->DisplayAutoresponderLinks($statid, $autoresponderid, $summary, $chosen_link);

		$GLOBALS['BouncesPage'] = $this->DisplayAutoresponderBounces($statid, $autoresponderid, $summary, $chosen_bounce_type);

		$GLOBALS['UnsubscribesPage'] = $this->DisplayAutoresponderUnsubscribes($statid, $autoresponderid, $summary);

		$GLOBALS['ForwardsPage'] = $this->DisplayAutoresponderForwards($statid, $autoresponderid, $summary);

		$GLOBALS['RecipientsPage'] = $this->DisplayAutoresponderRecipients($statid, $autoresponderid, $summary);

		// explicitly pass the sessionid across to the chart
		// since it's not the browser but the server making this request, it may get a different session id if we don't, which then means it can't load the data properly.
		// especially applies to windows servers.

		$unopened = $total_sent - $summary['emailopens_unique'] - $total_bounces;
		if ($unopened < 0) {
			$unopened = 0;
		}

		$data_url = 'stats_chart.php?Opens='.$summary['emailopens_unique'].'&Unopened='.$unopened.'&Bounced='.$total_bounces.'&Area=autoresponder&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		$GLOBALS['SummaryChart'] = InsertChart('pie',$data_url);

		// finally put it all together.
		$page = $this->ParseTemplate('Stats_Autoresponders_Step3', true, false);

		if (isset($_GET['tab'])) {
			$page .= '
			<script>
				ShowTab(' . $_GET['tab'] . ');
			</script>
			';
		}
		echo $page;
	}

	/**
	* DisplayAutoresponderOpens
	* This displays the page of autoresponder open information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Int $autoresponderid The autoresponderid to get information for.
	* @param Array $summary The basic information - start time and total number of opens.
	* @param Boolean $only_unique Whether to only show unique information or not. If this is false, all opens are shown.
	*
	* @see Stats_API::GetOpens
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayAutoresponderOpens($statid, $autoresponderid, $summary=array(), $only_unique=false)
	{
		$GLOBALS['DisplayOpensIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_OpenHeading'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		if ($only_unique) {
			$GLOBALS['DisplayOpensIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_OpenHeading_Unique'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));
		}

		$statsapi = $this->GetApi('Stats');

		$GLOBALS['PPDisplayName'] = 'oc';

		$opens = array();

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Autoresponders&NextAction=ViewSummary&auto=' . $autoresponderid . '&tab=2';

		if ($only_unique) {
			$base_action .= '&Unique';
		}

		$calendar_restrictions = $this->CalendarRestrictions['opens'];

		$GLOBALS['TabID'] = '2';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage'])) ? (int)$_GET['DisplayPage'] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		// make sure unique opens are > 0 - if they aren't, something isn't tracking right anyway so no point trying anything else.
		if ($summary['emailopens_unique'] > 0) {
			$opens = $statsapi->GetOpens($statid, $start, $perpage, $only_unique, $calendar_restrictions);
		}

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/
		$opencount = $statsapi->GetOpens($statid, 0, 0, $only_unique, $calendar_restrictions, true);

		// if we still don't have any opens, not sure how! but we display an error.
		if (empty($opens)) {
			if ($summary['trackopens']) {
				if ($summary['emailopens_unique'] > 0) {
					$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenOpened_CalendarProblem');
				} else {
					$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenOpened');
				}
			} else {
				$GLOBALS['Error'] = GetLang('AutoresponderWasNotOpenTracked');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Opens_Empty', true, false);
		}

		$emails_sent = $summary['htmlrecipients'] + $summary['textrecipients'] + $summary['multipartrecipients'];

		$GLOBALS['TotalEmails'] = $this->FormatNumber($emails_sent);
		$GLOBALS['TotalOpens'] = $this->FormatNumber($summary['emailopens']);
		$GLOBALS['TotalUniqueOpens'] = $this->FormatNumber($summary['emailopens_unique']);

		$most_opens = $statsapi->GetMostOpens($summary['statid'], $calendar_restrictions);

		$now = getdate();

		if (isset($most_opens['mth'])) {
			$GLOBALS['MostOpens'] = $this->Months[$most_opens['mth']] . ' ' . $most_opens['yr'];
		}

		if (isset($most_opens['hr'])) {
			$GLOBALS['MostOpens'] = date(GetLang('Daily_Time_Display'),mktime($most_opens['hr'], 1, 1, 1, 1, $now['year']));
		}

		if (isset($most_opens['dow'])) {
			$pos = array_search($most_opens['dow'], array_keys($this->days_of_week));
			$GLOBALS['MostOpens'] = date(GetLang('Date_Display_Display'), strtotime("last " . $this->days_of_week[$pos]));
		}

		if (isset($most_opens['dom'])) {
			$month = $now['mon'];
			// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
			if ($most_opens['dom'] > $now['mday']) {
				$month = $now['mon'] - 1;
			}
			$GLOBALS['MostOpens'] = date(GetLang('Date_Display_Display'),mktime(0, 0, 1, $month, $most_opens['dom'], $now['year']));
		}

		$avg_opens = 0;
		if ($emails_sent > 0) {
			$avg_opens = $summary['emailopens'] / $emails_sent;
		}

		if ($emails_sent != 0) {
                  $GLOBALS['OpenRate'] = $this->FormatNumber($summary['emailopens_unique'] / $emails_sent * 100,2) . "%" ;
                } else {
                  $GLOBALS['OpenRate'] = '0%';
                }

		$GLOBALS['AverageOpens'] = $this->FormatNumber($avg_opens, 1);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Autoresponders&SubAction=ViewSummary&auto=' . $autoresponderid . '&tab=2';

		if ($only_unique) {
			$GLOBALS['PAGE'] .= '&Unique';
		}

		$GLOBALS['AutoresponderOpenCount'] = $opencount;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'calendar_restrictions' => $calendar_restrictions,
		  'unique_only' => $only_unique,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_opens';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('OpenChart', 'autoresponder', $statid, 'column', array('graph_title' => GetLang("OpensChart")));

		return $this->ParseTemplate('Stats_Step3_Opens', true, false);
	}

	/**
	* DisplayAutoresponderLinks
	* This displays the page of autoresponder link information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Int $autoresponderid The autoresponderid to get information for.
	* @param Array $summary The basic information - start time and total number of link clicks.
	* @param String $chosen_link If this is present, we are showing information for a specific link. If it's not present, combine all links into one.
	*
	* @see Stats_API::GetClicks
	* @see Stats_API::GetUniqueLinks
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayAutoresponderLinks($statid, $autoresponderid, $summary=array(), $chosen_link='a')
	{
		$GLOBALS['AutoresponderID'] = (int)$autoresponderid;

		if (!is_numeric($chosen_link)) {
			$chosen_link = 'a';
		}

		$GLOBALS['StatID'] = (int)$statid;

		$GLOBALS['LinkAction'] = 'Autoresponders';
		$GLOBALS['LinkType'] = 'auto='.$autoresponderid;

		$GLOBALS['DisplayLinksIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_LinkHeading'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$statsapi = $this->GetApi('Stats');

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$GLOBALS['PPDisplayName'] = 'lc';

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Autoresponders&NextAction=ViewSummary&auto=' . $autoresponderid . '&tab=3&link=' . $chosen_link;

		$calendar_restrictions = $this->CalendarRestrictions['clicks'];

		$GLOBALS['TabID'] = '3';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$links = array();
		if ($summary['linkclicks'] > 0) {
			$links = $statsapi->GetClicks($statid, $start, $perpage, $chosen_link, $calendar_restrictions);
		}

		$all_links = $statsapi->GetUniqueLinks($statid);

		if (empty($links)) {
			if ($summary['tracklinks']) {
				if (empty($all_links)) {
					$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenClicked_NoLinksFound');
				} else {
					if ($summary['linkclicks'] > 0) {
						if (is_numeric($chosen_link)) {
							if ($calendar_restrictions != '') {
								$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenClicked_CalendarLinkProblem');
							} else {
								$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenClicked_LinkProblem');
							}
						} else {
							$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenClicked_CalendarProblem');
						}
					} else {
						$GLOBALS['DisplayStatsLinkList'] = 'none';
						$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenClicked');
					}
				}
			} else {
				$GLOBALS['Error'] = GetLang('AutoresponderWasNotTracked_Links');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Links_Empty', true, false);
		}

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/
		$summary['linkclicks'] = $statsapi->GetClicks($statid, 0, 0, $chosen_link, $calendar_restrictions, true);

		// build up the summary table.
		$GLOBALS['TotalClicks'] = $this->FormatNumber($summary['linkclicks']);

		$unique_clicks_count = $statsapi->GetUniqueClicks($statid, $chosen_link, $calendar_restrictions);
		$GLOBALS['TotalUniqueClicks'] = $this->FormatNumber($unique_clicks_count);

		$unique_clicks = $statsapi->GetUniqueClickRecipients($statid,$calendar_restrictions,$chosen_link);
		$GLOBALS['UniqueClicks'] = $this->FormatNumber($unique_clicks);

		$most_popular_link = $statsapi->GetMostPopularLink($statid, $chosen_link, $calendar_restrictions);

		$GLOBALS['MostPopularLink'] = htmlspecialchars($most_popular_link, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['MostPopularLink_Short'] = $this->TruncateName($most_popular_link, 20);

		$averageclicks = 0;
		if (isset($GLOBALS['AutoresponderOpenCount']) && (int)$GLOBALS['AutoresponderOpenCount'] > 0) {
			$open_count = (int)$GLOBALS['AutoresponderOpenCount'];
			$averageclicks = $summary['linkclicks'] / $open_count;
		}
		$GLOBALS['AverageClicks'] = $this->FormatNumber($averageclicks, 1);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Autoresponders&SubAction=ViewSummary&auto=' . $autoresponderid . '&tab=3&link=' . $chosen_link;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'calendar_restrictions' => $calendar_restrictions,
		  'chosen_link' => $chosen_link,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_links';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('LinksChart', 'autoresponder', $statid, 'column', array('graph_title' => GetLang("LinksClickedChart")));

		return $this->ParseTemplate('Stats_Step3_Links', true, false);
	}

	/**
	* DisplayNewsletterBounces
	* This displays the page of autoresponder bounce information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Int $autoresponderid The autoresponderid to get information for.
	* @param Array $summary The basic information - start time and total number of bounces.
	* @param String $chosen_bounce_type If this is present, we are showing information for a specific bounce type (hard, soft or unknown). If it's not present, combine all types.
	*
	* @see Stats_API::GetBounces
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayAutoresponderBounces($statid, $autoresponderid, $summary=array(), $chosen_bounce_type='')
	{
		$GLOBALS['DisplayBouncesIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_BounceHeading'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['BounceType'] = 'auto='.$autoresponderid;
		$GLOBALS['BounceAction'] = 'Autoresponders&auto='.$autoresponderid;

		$bouncetypelist = '';
		$all_bounce_types = array('any', 'hard', 'soft');
		if (!in_array($chosen_bounce_type, $all_bounce_types)) {
			$chosen_bounce_type = 'any';
		}

		$GLOBALS['PPDisplayName'] = 'bc'; // bounce count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Autoresponders&NextAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=4';

		$calendar_restrictions = $this->CalendarRestrictions['bounces'];

		$GLOBALS['TabID'] = '4';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi('Stats');

		$total_bounces = $summary['bouncecount_soft'] + $summary['bouncecount_hard'] + $summary['bouncecount_unknown'];

		$bounces = array();

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage'])) ? (int)$_GET['DisplayPage'] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		if ($total_bounces > 0) {
			$bounces = $statsapi->GetBounces($statid, $start, $perpage, $chosen_bounce_type, $calendar_restrictions);
		}

		if (empty($bounces)) {
			if ($calendar_restrictions != '') {
				if ($total_bounces > 0) {
					if (!$chosen_bounce_type || $chosen_bounce_type == 'any') {
						$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenBounced_CalendarProblem');
					} else {
						$GLOBALS['Error'] = sprintf(GetLang('AutoresponderHasNotBeenBounced_CalendarProblem_BounceType'), GetLang('Bounce_Type_' . $chosen_bounce_type));
					}
				} else {
					$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenBounced');
				}
			} else {
				if ($total_bounces > 0 && (!$chosen_bounce_type || $chosen_bounce_type == 'any')) {
					$GLOBALS['Error'] = sprintf(GetLang('AutoresponderHasNotBeenBounced_BounceType'), GetLang('Bounce_Type_' . $chosen_bounce_type));
				} else {
					$GLOBALS['Error'] = GetLang('AutoresponderHasNotBeenBounced');
				}
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Bounces_Empty', true, false);
		}

		$bounce_types_count = $statsapi->GetBounceCounts($statid, $calendar_restrictions);
		$GLOBALS['TotalBounceCount'] = $this->FormatNumber($bounce_types_count['total']);
		$GLOBALS['TotalSoftBounceCount'] = $this->FormatNumber($bounce_types_count['soft']);
		$GLOBALS['TotalHardBounceCount'] = $this->FormatNumber($bounce_types_count['hard']);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Autoresponders&SubAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=4&bouncetype=' . $chosen_bounce_type;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'calendar_restrictions' => $calendar_restrictions,
		  'chosen_bounce_type' => $chosen_bounce_type,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_bounces';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('BounceChart', 'newsletter', $statid, 'column');

		return $this->ParseTemplate('Stats_Step3_Bounces', true, false);
	}

	/**
	* DisplayAutoresponderUnsubscribes
	* This displays the page of autoresponder unsubscribe information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Int $autoresponderid The autoresponderid to get information for.
	* @param Array $summary The basic information - start time and total number of unsubscribes.
	*
	* @see Stats_API::GetUnsubscribes
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayAutoresponderUnsubscribes($statid, $autoresponderid, $summary=array())
	{
		$GLOBALS['DisplayUnsubscribesIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_UnsubscribesHeading'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['PPDisplayName'] = 'uc'; // unsubscribe count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Autoresponders&NextAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=5';

		$calendar_restrictions = $this->CalendarRestrictions['unsubscribes'];

		$GLOBALS['TabID'] = '5';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi('Stats');

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$unsubscribes = array();

		if ($summary['unsubscribecount'] > 0) {
			$unsubscribes = $statsapi->GetUnsubscribes($statid, $start, $perpage, $calendar_restrictions);
		}

		if (empty($unsubscribes)) {
			if ($summary['unsubscribecount'] > 0) {
				$GLOBALS['Error'] = GetLang('AutoresponderHasNoUnsubscribes_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('AutoresponderHasNoUnsubscribes');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Unsubscribes_Empty', true, false);
		}

		if ($calendar_restrictions != '') {
			$summary['unsubscribecount'] = $statsapi->GetUnsubscribes($statid, $start, $perpage, $calendar_restrictions, true);
		}

		$GLOBALS['TotalUnsubscribes'] = $this->FormatNumber($summary['unsubscribecount']);

		$most_unsubscribes = $statsapi->GetMostUnsubscribes($statid, $calendar_restrictions);

		$now = getdate();

		if (isset($most_unsubscribes['mth'])) {
			$GLOBALS['MostUnsubscribes'] = $this->Months[$most_unsubscribes['mth']] . ' ' . $most_unsubscribes['yr'];
		}

		if (isset($most_unsubscribes['hr'])) {
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(mktime($most_unsubscribes['hr'], 1, 1, 1, 1, $now['year']), GetLang('Daily_Time_Display'));
		}

		if (isset($most_unsubscribes['dow'])) {
			$pos = array_search($most_unsubscribes['dow'], array_keys($this->days_of_week));
			// we need to add 1 hour here otherwise we get the wrong day from strtotime.
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(strtotime("last " . $this->days_of_week[$pos] . " +1 hour"), GetLang('Date_Display_Display'));
		}

		if (isset($most_unsubscribes['dom'])) {
			$month = $now['mon'];
			// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
			if ($most_unsubscribes['dom'] > $now['mday']) {
				$month = $now['mon'] - 1;
			}
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(mktime(0, 0, 1, $month, $most_unsubscribes['dom'], $now['year']), GetLang('Date_Display_Display'));
		}

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Autoresponders&SubAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=5';

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'calendar_restrictions' => $calendar_restrictions,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_unsubscribes';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('UnsubscribeChart', 'autoresponder', $statid, 'column', array('graph_title' => GetLang("UnsubscribesChart")));

		return $this->ParseTemplate('Stats_Step3_Unsubscribes', true, false);
	}

	/**
	* DisplayAutoresponderForwards
	* This displays the page of autoresponder forwarding information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Int $autoresponderid The autoresponderid to get information for.
	* @param Array $summary The basic information - start time and total number of forwards.
	*
	* @see Stats_API::GetForwards
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayAutoresponderForwards($statid, $autoresponderid, $summary=array())
	{
		$GLOBALS['DisplayForwardsIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_ForwardsHeading'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['PPDisplayName'] = 'fc'; // forward count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Autoresponders&NextAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=6';

		$calendar_restrictions = $this->CalendarRestrictions['forwards'];

		$GLOBALS['TabID'] = '6';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$statsapi = $this->GetApi('Stats');

		$forwards = array();

		if ($summary['emailforwards'] > 0) {
			$forwards = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions);
		}

		if (empty($forwards)) {
			if ($summary['emailforwards'] > 0) {
				$GLOBALS['Error'] = GetLang('AutoresponderHasNoForwards_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('AutoresponderHasNoForwards');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Forwards_Empty', true, false);
		}

		if ($calendar_restrictions != '') {
			$summary['emailforwards'] = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions, true);
		}

		$GLOBALS['TotalForwards'] = $this->FormatNumber($summary['emailforwards']);

		$new_signups = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions, true, true);

		$GLOBALS['TotalForwardSignups'] = $this->FormatNumber($new_signups);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Autoresponders&SubAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=6';

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $statid,
		  'calendar_restrictions' => $calendar_restrictions,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'forwards';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('ForwardsChart', 'autoresponder', $statid, 'column', array('graph_title' => GetLang("ForwardsChart")));

		return $this->ParseTemplate('Stats_Step3_Forwards', true, false);
	}

	/**
	* DisplayAutoresponderRecipients
	* This displays the page of autoresponder recipient information based on the details passed in.
	* It will work out the calendar information, graph, paging and so on.
	*
	* @param Int $statid The statid to get information for.
	* @param Int $autoresponderid The autoresponderid to get information for.
	* @param Array $summary The basic information - start time and total number of forwards.
	*
	* @see Stats_API::GetRecipients
	* @see DisplayChart
	*
	* @return Void Doesn't return anything - just prints out the tab of information.
	*/
	function DisplayAutoresponderRecipients($statid=0, $autoresponderid=0, $summary=array())
	{
		$GLOBALS['DisplayRecipientsIntro'] = sprintf(GetLang('AutoresponderStatistics_SubscriberInformation_Intro'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['PPDisplayName'] = 'rc'; // recipient count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=Autoresponders&NextAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=7';

		$calendar_restrictions = $this->CalendarRestrictions['recipients'];

		$GLOBALS['TabID'] = '7';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$statsapi = $this->GetApi('Stats');

		$recipients = array();

		$summary_recipient_count = $statsapi->GetRecipients($statid, 'autoresponder', $start, $perpage, $calendar_restrictions, true);

		$recipients = $statsapi->GetRecipients($statid, 'autoresponder', $start, $perpage, $calendar_restrictions);

		if (empty($recipients)) {
			if ($summary_recipient_count > 0) {
				$GLOBALS['Error'] = GetLang('AutoresponderSentStats_NotSent_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('AutoresponderSentStats_NotSent');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_RecipientList_Empty', true, false);
		}

		$GLOBALS['TotalRecipients'] = $this->FormatNumber($summary_recipient_count);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=Autoresponders&SubAction=ViewSummary&id=' . $statid . '&auto=' . $autoresponderid . '&tab=7';
		$this->SetupPaging($summary_recipient_count, $DisplayPage, $perpage);

		$paging = $this->ParseTemplate('Paging', true, false);

		$GLOBALS['Paging'] = $paging;

		$recipient_list = '';

		foreach ($recipients as $k => $recipient_details) {
			$GLOBALS['EmailAddress'] = htmlspecialchars($recipient_details['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['SendTime'] = $this->PrintTime($recipient_details['sendtime'], true);
			$GLOBALS['ReasonMessage'] = '';

			if ($recipient_details['send_status'] == 1) {
				$GLOBALS['EmailSent'] = GetLang('Yes');
			} else {
				$GLOBALS['EmailSent'] = GetLang('No');
				$GLOBALS['ReasonMessage'] = GetLang('AutoresponderSentStatus_' . ucwords($recipient_details['reason']));
			}
			$recipient_list .= $this->ParseTemplate('Stats_Step3_RecipientList_Row', true, false);
		}
		$GLOBALS['Stats_Step3_RecipientList'] = $recipient_list;

		$GLOBALS['DisplayRecipientList_Table'] = $this->ParseTemplate('Stats_Step3_RecipientList_Table', true, false);

		return $this->ParseTemplate('Stats_Step3_RecipientList', true, false);
	}

	/**
	* PrintListStats_Step1
	* This prints out a list of mailing lists that the user can see.
	* If they can only see one list, it will take them straight to it.
	*
	* @see User_API::HasAccess
	* @see User_API::UserAdmin
	*
	* @return Void Doesn't return anything. Prints a dropdown list of users if they are a useradmin. If they are not a useradmin, then this will take them straight to viewing their own statistics.
	*/
	function PrintListStats_Step1()
	{
		$user = IEM::userGetCurrent();
		$lists = $user->GetLists();

		$GLOBALS['Action'] = 'List';

		$listids = array_keys($lists);

		if (sizeof($listids) < 1) {
			if ($user->CanCreateList()) {
				$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListCreate'));
				$GLOBALS['Lists_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
			} else {
				$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListAssign'));
			}

			$this->ParseTemplate('Stats_List_Empty');
			return;
		}

		if (sizeof($listids) == 1) {
			$location = 'index.php?Page=Stats&Action=List&SubAction=Step2&list=' . current($listids);
			?>
			<script>
				window.location = '<?php echo $location; ?>';
			</script>
			<?php
			exit();
		}

		$perpage = $this->GetPerPage();

		$DisplayPage = $this->GetCurrentPage();

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$this->_DefaultSort = 'name';
		$this->_DefaultDirection = 'asc';

		$sortinfo = $this->GetSortDetails();

		$listapi = $this->GetApi('Lists');

		$NumberOfLists = count($listids);

		// if we're a list admin, no point checking the lists - we have access to everything.
		if ($user->ListAdmin()) {
			$check_lists = null;
		}

		$mylists = $listapi->GetLists($listids, $sortinfo, false, $start, $perpage);

		$GLOBALS['PAGE'] = 'Stats&Action=List&SubAction=Step1';

		$this->SetupPaging($NumberOfLists, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=List&NextAction=Step1';
		$paging = $this->ParseTemplate('Paging', true, false);

		$stats_manage = $this->ParseTemplate('Stats_Lists_Manage', true, false);

		$statsdisplay = '';

		foreach ($mylists as $pos => $listdetails) {
			$GLOBALS['ListID'] = $listdetails['listid'];
			$GLOBALS['MailingList'] = htmlspecialchars($listdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['CreateDate'] = $this->PrintDate($listdetails['createdate']);
			$GLOBALS['SubscribeCount'] = $this->FormatNumber($listdetails['subscribecount']);
			$GLOBALS['UnsubscribeCount'] = $this->FormatNumber($listdetails['unsubscribecount']);

			$GLOBALS['StatsAction'] = '<a href="index.php?Page=Stats&Action=List&SubAction=ViewSummary&list=' . $listdetails['listid'] . '">' . GetLang('ViewSummary') . '</a>&nbsp;&nbsp;';

			$GLOBALS['StatsAction'] .= '<a href="remote_stats.php?height=475&width=420&overflow=none&statstype=l&Action=print&stats%5B%5D=' . $listdetails['listid'] . '" class="thickbox" title="' . GetLang('PrintListStatistics') . '">' . GetLang('Print_Stats_Selected') . '</a>&nbsp;&nbsp;';

			if ($user->isAdmin()) {
				$GLOBALS['StatsAction'] .= '<a href="index.php?Page=Users&Action=Edit&UserID=' . $listdetails['ownerid'] . '" target="_blank">' . GetLang('EditListOwner') . '</a>&nbsp;&nbsp;';
			}

			$statsdisplay .= $this->ParseTemplate('Stats_Lists_Manage_Row', true, false);
		}
		$stats_manage = str_replace('%%TPL_Stats_Lists_Manage_Row%%', $statsdisplay, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging%%', $paging, $stats_manage);
		$stats_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $stats_manage);

		echo $stats_manage;
	}

	/**
	* PrintListStats_Step2
	* Print mailing list statistics for the list selected. This includes all sorts of stuff.
	* It checks to make sure you're not trying to view someone elses statistics before anything else.
	*
	* @param Int $listid The listid to print statistics for.
	*
	* @see Stats_Chart::Process
	*
	* @return Void Prints out the list of statistics for this particular mailing list. It doesn't return anything.
	*/
	function PrintListStats_Step2($listid=0)
	{
		$listid = (int)$listid;

		$GLOBALS['Heading'] = GetLang('Stats_List_Step2_Heading');

		$user = IEM::userGetCurrent();
		$lists = $user->GetLists();
		if (!in_array($listid, array_keys($lists))) {
			$GLOBALS['SummaryIntro'] = sprintf(GetLang('Stats_List_Step2_Intro'), GetLang('Unknown_List'));
			$this->DenyAccess();
			return;
		}

		include(dirname(__FILE__) . '/amcharts/amcharts.php');

		$listapi = $this->GetApi('Lists');
		$listapi->Load($listid);

		// Log this to "User Activity Log"
		IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/chart_bar.gif', $listapi->name);

		$GLOBALS['SummaryIntro'] = sprintf(GetLang('Stats_List_Step2_Intro'), htmlspecialchars($listapi->Get('name'), ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['TabID'] = '7';

		$base_action = 'SubAction=List&NextAction=ViewSummary&list=' . $listid;

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi();

		$calendar_dates = $user->GetSettings('CalendarDates');

		$statsapi->CalculateStatsType();

		$restrictions = array();
		if (isset($calendar_dates['subscribers'])) {
			$restrictions = $calendar_dates['subscribers'];
		}


		$summary = $statsapi->GetListSummary($listid);

		IEM::sessionSet('ListStatistics', $summary['statids']);

		$data = $statsapi->GetSubscriberGraphData($statsapi->stats_type, $restrictions, $listid);

		$domain_data = $statsapi->GetSubscriberDomainGraphData($restrictions, $listid);

		IEM::sessionSet('SubscriberGraphData', $data);

		$data_url = 'stats_chart.php?Area=list&list=' . $listid . '&graph=subscribersummary&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		$GLOBALS['SummaryChart'] = InsertChart('column', $data_url, array('graph_title' => GetLang("List_Summary_Graph_subscribersummary")));

		$areas = array('unconfirms', 'confirms', 'unsubscribes', 'bounces', 'forwards');
		$now = getdate();
		$today = $now['0'];

		$totals = array('unconfirms' => 0, 'confirms' => 0, 'unsubscribes' => 0, 'forwards' => 0, 'bounces' => 0);
		$domain_totals = array('unconfirms' => 0, 'confirms' => 0, 'unsubscribes' => 0, 'forwards' => 0, 'bounces' => 0);

		$time_display = '';

		$this_year = date('Y');

		switch ($statsapi->calendar_type) {
			case 'today':
			case 'yesterday':
				for ($i = 0; $i < 24; $i++) {

					$server_time = AdjustTime(array($i, 1, 1, 1, 1, $this_year), true);
					$GLOBALS['Name'] = $this->PrintDate($server_time, GetLang('Daily_Time_Display'));

					foreach ($areas as $k => $area) {
						$GLOBALS[$area] = 0;
						foreach ($data[$area] as $p => $details) {
							if ($details['hr'] == $i) {
								$GLOBALS[$area] = $this->FormatNumber($details['count']);
								$totals[$area] += $details['count'];
								break;
							}
						}
						if (empty($data)) {
							break;
						}
					}
					$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
				}
			break;

			case 'last24hours':
				$hours_now = $now['hours'];

				$i = 24;
				while ($i > 0) {
					$yr = AdjustTime(array($hours_now, 1, 1, 1, 1, $this_year), true, null, true);
					$GLOBALS['Name'] = $this->PrintDate($yr, GetLang('Daily_Time_Display'));

					$hour_check = date('G', $yr);

					foreach ($areas as $k => $area) {
						$GLOBALS[$area] = 0;
						foreach ($data[$area] as $p => $details) {
							if ($details['hr'] == $hour_check) {
								$GLOBALS[$area] = $this->FormatNumber($details['count']);
								$totals[$area] += $details['count'];
								break;
							}
						}
						if (empty($data)) {
								$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
							break 2;
						}
					}
					$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);

					$hours_now--;

					$i--;
				}
			break;

			case 'last7days':

				$date = AdjustTime($today, true, null, true);

				$i = 7;
				while ($i > 0) {
					$GLOBALS['Name'] = $this->PrintDate($date, GetLang('DOW_Word_Full_Display'));

					foreach ($areas as $k => $area) {
						$GLOBALS[$area] = 0;
						foreach ($data[$area] as $p => $details) {
							if ($details['dow'] == date('w', $date)) {
								$GLOBALS[$area] = $this->FormatNumber($details['count']);
								$totals[$area] += $details['count'];
								break;
							}
						}
						if (empty($data)) {
							$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
							break 2;
						}
					}
					$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);

					$date = $date - 86400; // take off one day each time.

					$i--;
				}
			break;

			case 'last30days':

				$date = $today;

				$i = 30;
				while ($i > 0) {
					$GLOBALS['Name'] = $this->PrintDate($date);

					foreach ($areas as $k => $area) {
						$GLOBALS[$area] = 0;
						foreach ($data[$area] as $p => $details) {
							if ($details['dom'] == date('j', $date)) {
								$GLOBALS[$area] = $this->FormatNumber($details['count']);
								$totals[$area] += $details['count'];
								break;
							}
						}
						if (empty($data)) {
								$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
							break 2;
						}
					}
					$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);

					$date = $date - 86400; // take off one day each time.

					$i--;
				}
			break;

			case 'thismonth':
			case 'lastmonth':
				if ($statsapi->calendar_type == 'thismonth') {
					$month = $now['mon'];
				} else {
					$month = $now['mon'] - 1;
				}

				$timestamp = AdjustTime(array(1, 1, 1, $month, 1, $now['year']), true);

				$days_of_month = date('t', $timestamp);

				for ($i = 1; $i <= $days_of_month; $i++) {
					$GLOBALS['Name'] = $this->PrintDate($timestamp);

					foreach ($areas as $k => $area) {
						$GLOBALS[$area] = 0;
						foreach ($data[$area] as $p => $details) {
							if ($details['dom'] == $i) {
								$GLOBALS[$area] = $this->FormatNumber($details['count']);
								$totals[$area] += $details['count'];
								break;
							}
						}
					}

					$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);

					$timestamp += 86400;
				}
			break;

			default:
				$calendar_settings = $user->GetSettings('Calendar');
				$found = false;
				foreach ($areas as $k => $area) {
					foreach ($data[$area] as $p => $details) {
						if ($details['count'] > 0) {
							$found = true;
							break 2;
						}
					}
				}

				if ($found) {
					if ($statsapi->stats_type == 'last7days') {
						$date = AdjustTime(array(1, 1, 1, $calendar_settings['From']['Mth'], 1, $calendar_settings['From']['Yr']), true);

						$i = 7;
						while ($i > 0) {
							$GLOBALS['Name'] = $this->PrintDate($date, GetLang('DOW_Word_Full_Display'));

							foreach ($areas as $k => $area) {
								$GLOBALS[$area] = 0;
								foreach ($data[$area] as $p => $details) {
									if ($details['dow'] == date('w', $date)) {
										$GLOBALS[$area] = $this->FormatNumber($details['count']);
										$totals[$area] += $details['count'];
										break;
									}
								}
								if (empty($data)) {
									$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
									break 2;
								}
							}
							$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);

							$date = $date - 86400; // take off one day each time.

							$i--;
						}
					}

					if ($statsapi->stats_type == 'monthly') {
						$timestamp = AdjustTime(array(1, 1, 1, $calendar_settings['From']['Mth'], 1, $calendar_settings['From']['Yr']), true);

						$days_of_month = date('t', $timestamp);

						for ($i = 1; $i <= $days_of_month; $i++) {
							$GLOBALS['Name'] = $this->PrintDate($timestamp);

							foreach ($areas as $k => $area) {
								$GLOBALS[$area] = 0;
								foreach ($data[$area] as $p => $details) {
									if ($details['dom'] == $i) {
										$GLOBALS[$area] = $this->FormatNumber($details['count']);
										$totals[$area] += $details['count'];
										break;
									}
								}
							}

							$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);

							$timestamp += 86400;
						}
					}

					if ($statsapi->stats_type != 'last7days' && $statsapi->stats_type != 'monthly') {
                                                $now = getdate();
						$month = $now['mon'];
						for ($i = 1; $i <= 12; $i++) {
							$found_stats = false;
							foreach ($areas as $k => $area) {
								$GLOBALS[$area] = 0;
								foreach ($data[$area] as $p => $details) {
									if ($details['mth'] == $month) {
                                                                                $GLOBALS['Name'] = GetLang($this->Months[$month]) . $details['yr'];
                                                                                $GLOBALS[$area] = $this->FormatNumber($details['count']);
                                                                                $totals[$area] += $details['count'];
                                                                                $found_stats = true;
                                                                        }

								}
							}

                                                        $month = $month==1 ? 12 : $month-1;

							if ($found_stats) {
                                                            $time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
							}
						}
					}
				} else {
					$GLOBALS['Name'] = '&nbsp;';
					$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
				}
			break;
		}

		$GLOBALS['DisplayList'] = $time_display;

		$domain_lines = array();

		foreach ($areas as $k => $area) {
			foreach ($domain_data[$area] as $p => $details) {
				if (isset($details['domainname'])) {
					$domain = $details['domainname'];
					if (!isset($domain_lines[$domain])) {
						$domain_lines[$domain] = array('unconfirms' => 0, 'confirms' => 0, 'unsubscribes' => 0, 'forwards' => 0, 'bounces' => 0);
					}
					$domain_lines[$domain][$area] = $details['count'];
				}
			}
		}

		$graph_details = array();

		$domain_display = '';
		if (!empty($domain_lines)) {
			foreach ($domain_lines as $domain_name => $domain_info) {
				$GLOBALS['Name'] = htmlspecialchars($domain_name, ENT_QUOTES, SENDSTUDIO_CHARSET);
				foreach ($domain_info as $area => $count) {
					$GLOBALS[$area] = $this->FormatNumber($count);
					$domain_totals[$area] += $count;
					if ($area == 'confirms') {
						if (!isset($graph_details[$domain_name])) {
							$graph_details[$domain_name] = 0;
						}
						$graph_details[$domain_name] += $count;
						continue;
					}
				}
				$domain_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
			}
		} else {
			$GLOBALS['Name'] = '';
			foreach ($areas as $k => $area) {
				$GLOBALS[$area] = 0;
			}
			$domain_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
		}

		IEM::sessionSet('SubscriberDomains', $graph_details);

		foreach ($areas as $k => $area) {
			$GLOBALS['Total_' . $area] = $this->FormatNumber($totals[$area]);
			$GLOBALS['Total_domain_' . $area] = $this->FormatNumber($domain_totals[$area]);
		}

		$page = $this->ParseTemplate('Stats_List_Step3', true, false);

		$base_action = 'SubAction=List&NextAction=ViewSummary&list=' . $listid . '&tab=7';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$GLOBALS['DisplayDomainList'] = $domain_display;

		if (!empty($domain_lines)) {
			$this->DisplayChart('DomainChart', 'SubscriberDomains', '0','pie',array(
			 'hide_labels_percent' => 2,
			 'group_percent' => 2,
			 'x_position' => '',
			 'radius' => 85,
			 'graph_title' => GetLang("ListStatistics_Snapshot_PerDomain")
			));
		}

		$subscriber_count = $listapi->Get('subscribecount');

		if ($subscriber_count <= 0) {
			$GLOBALS['Error'] = GetLang('Stats_NoSubscribersOnList');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$domain_page = $this->ParseTemplate('Stats_List_Step3_Domains_Empty', true, false);
		} elseif (empty($domain_lines)) {
			$GLOBALS['Error'] = GetLang('Stats_NoSubscribersOnList_DateRange');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$domain_page = $this->ParseTemplate('Stats_List_Step3_Domains_Empty', true, false);
		} else {
			$domain_page = $this->ParseTemplate('Stats_List_Step3_Domains', true, false);
		}

		$page = str_replace('%%TPL_DomainPage%%', $domain_page, $page);

		// by default this is for all opens, not unique opens.
		$unique_only = false;
		if (isset($_GET['Unique'])) {
			$unique_only = true;
		}

		$chosen_bounce_type = '';
		if (isset($_GET['bouncetype'])) {
			$chosen_bounce_type = $_GET['bouncetype'];
		}

		$chosen_link = 'a';
		if (isset($_GET['link'])) {
			if (is_numeric($_GET['link'])) {
				$chosen_link = $_GET['link'];
			}
		}

		$summary['listname'] = $listapi->Get('name');

		// we need to process the opens page first because it sets the number of opens used in a calculation for the links page.
		$open_page = $this->DisplayListOpens($listid, $summary, $unique_only);

		$links_page = $this->DisplayListLinks($listid, $summary, $chosen_link);

		$bounces_page = $this->DisplayListBounces($listid, $summary, $chosen_bounce_type);

		$unsubscribes_page = $this->DisplayListUnsubscribes($listid, $summary);
		$forwards_page = $this->DisplayListForwards($listid, $summary);

		$page = str_replace(array('%%TPL_OpensPage%%', '%%TPL_LinksPage%%', '%%TPL_BouncesPage%%', '%%TPL_UnsubscribesPage%%', '%%TPL_ForwardsPage%%'), array($open_page, $links_page, $bounces_page, $unsubscribes_page, $forwards_page), $page);

		if (isset($_GET['tab'])) {
			$page .= '
			<script>
				ShowTab(' . $_GET['tab'] . ');
			</script>
			';
		}
		echo $page;
	}

	/**
	* DisplayListOpens
	* Displays the list of opens and open summary chart for a mailing list.
	*
	* @param Int $listid The listid to print statistics for.
	* @param Array $summary The list summary information
	* @param Boolean $unique_only True to display only unique opens, false to display all opens
	*
	* @see Stats_API::GetOpens
	*
	* @return Void Prints out the list of statistics for this particular mailing list. It doesn't return anything.
	*/
	function DisplayListOpens($listid=0, $summary, $unique_only=false)
	{
		$GLOBALS['DisplayOpensIntro'] = sprintf(GetLang('ListStatistics_Snapshot_OpenHeading'), htmlspecialchars($summary['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		if ($unique_only) {
			$GLOBALS['DisplayOpensIntro'] = sprintf(GetLang('ListStatistics_Snapshot_OpenHeading_Unique'), $summary['ListStatistics_Snapshot_OpenHeading']);
		}

		$statsapi = $this->GetApi('Stats');

		$GLOBALS['PPDisplayName'] = 'oc';

		$opens = array();

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=List&NextAction=ViewSummary&list=' . $listid . '&tab=2';

		if ($unique_only) {
			$base_action .= '&Unique';
		}

		$calendar_restrictions = $this->CalendarRestrictions['opens'];

		$GLOBALS['TabID'] = '2';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/
		$open_count = $statsapi->GetOpens($summary['statids'], 0, 0, $unique_only, $calendar_restrictions, true);

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage'])) ? (int)$_GET['DisplayPage'] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$opens = $statsapi->GetOpens($summary['statids'], $start, $perpage, $unique_only, $calendar_restrictions);

		// if we still don't have any opens, we display an error.
		if (empty($opens)) {
			if ($summary['emailopens_unique'] > 0) {
				$GLOBALS['Error'] = GetLang('ListOpenStatsHasNotBeenOpened_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('ListOpenStatsHasNotBeenOpened');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Opens_Empty', true, false);
		}

		$GLOBALS['TotalEmails'] = $this->FormatNumber($summary['emails_sent']);
		$GLOBALS['TotalOpens'] = $this->FormatNumber($summary['emailopens']);
		$GLOBALS['TotalUniqueOpens'] = $this->FormatNumber($summary['emailopens_unique']);

		$most_opens = $statsapi->GetMostOpens($summary['statids'], $calendar_restrictions,$listid);

		$now = getdate();

		if (isset($most_opens['mth'])) {
			$GLOBALS['MostOpens'] = $this->Months[$most_opens['mth']] . ' ' . $most_opens['yr'];
		}

		if (isset($most_opens['hr'])) {
			$GLOBALS['MostOpens'] = date(GetLang('Daily_Time_Display'),mktime($most_opens['hr'], 1, 1, 1, 1, $now['year']));
		}

		if (isset($most_opens['dow'])) {
			$pos = array_search($most_opens['dow'], array_keys($this->days_of_week));
			$GLOBALS['MostOpens'] = date(GetLang('Date_Display_Display'), strtotime("last " . $this->days_of_week[$pos]));
		}

		if (isset($most_opens['dom'])) {
			$month = $now['mon'];
			// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
			if ($most_opens['dom'] > $now['mday']) {
				$month = $now['mon'] - 1;
			}
			$GLOBALS['MostOpens'] = date(GetLang('Date_Display_Display'),mktime(0, 0, 1, $month, $most_opens['dom'], $now['year']));
		}

		$avg_opens = 0;
		if ($summary['emails_sent'] > 0) {
			$avg_opens = $summary['emailopens'] / $summary['emails_sent'];
		}

		if ($summary['emails_sent'] != 0) {
                  $GLOBALS['OpenRate'] = $this->FormatNumber($summary['emailopens_unique'] / $summary['emails_sent'] * 100,2) . "%" ;
                } else {
                  $GLOBALS['OpenRate'] = '0%';
                }

		$GLOBALS['AverageOpens'] = $this->FormatNumber($avg_opens, 1);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=List&SubAction=ViewSummary&list=' . $listid . '&tab=2';

		if ($unique_only) {
			$GLOBALS['PAGE'] .= '&Unique';
		}

		$GLOBALS['ListStatsOpenCount'] = $open_count;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $summary['statids'],
		  'calendar_restrictions' => $calendar_restrictions,
		  'unique_only' => $unique_only,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_opens';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator',true);

		$this->DisplayChart('OpenChart', 'list', $listid,'column', array('graph_title' => GetLang("OpensChart")));

		return $this->ParseTemplate('Stats_Step3_Opens', true, false);
	}

	/**
	* DisplayListLinks
	* Displays the list of link clicks and link clicks summary chart for a mailing list.
	*
	* @param Int $listid The listid to print statistics for.
	* @param Array $summary The list summary information
	* @param Boolean $chosen_link The linkid to display stats for or 'a' for all links
	*
	* @see Stats_API::GetClicks
	*
	* @return Void Prints out the list of statistics for this particular mailing list. It doesn't return anything.
	*/
	function DisplayListLinks($listid, $summary=array(), $chosen_link='a')
	{
		$GLOBALS['ListID'] = (int)$listid;

		$GLOBALS['LinkAction'] = 'List';
		$GLOBALS['LinkType'] = 'list='.$listid;

		if (!is_numeric($chosen_link)) {
			$chosen_link = 'a';
		}

		$GLOBALS['DisplayLinksIntro'] = sprintf(GetLang('ListStatistics_Snapshot_LinkHeading'), htmlspecialchars($summary['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$statsapi = $this->GetApi('Stats');

		$perpage = $this->GetPerPage();

		$GLOBALS['PPDisplayName'] = 'lc';

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=List&NextAction=ViewSummary&list=' . $listid . '&tab=3&link=' . $chosen_link;

		$calendar_restrictions = $this->CalendarRestrictions['clicks'];

		$GLOBALS['TabID'] = '3';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$links = array();
		if ($summary['linkclicks'] > 0) {
			$links = $statsapi->GetClicks($summary['statids'], $start, $perpage, $chosen_link, $calendar_restrictions);
		}

		$all_links = $statsapi->GetUniqueLinks($summary['statids'],$listid);

		if (empty($all_links)) {
			$GLOBALS['DisplayStatsLinkList'] = 'none';
		} else {
			$GLOBALS['DisplayStatsLinkList'] = 'block';
		}

		$all_links_list = '';
		foreach ($all_links as $p => $linkinfo) {
			$selected = '';
			if ($linkinfo['linkid'] == $chosen_link) {
				$selected = ' SELECTED';
			}
			$all_links_list .= '<option value="' . $linkinfo['linkid'] . '"' . $selected . '>' . str_replace(array("'", '"'), "", $linkinfo['url']) . '</option>';
		}

		$GLOBALS['StatsLinkList'] = $all_links_list;

		if (!isset($GLOBALS['CurrentPage'])) {
			$GLOBALS['CurrentPage'] = 1;
		}
		$GLOBALS['CurrentPage'] = (int)$GLOBALS['CurrentPage'];

		$GLOBALS['StatsLinkDropDown'] = $this->ParseTemplate('Stats_Step3_Links_List', true, false);

		if (empty($links)) {
			if (empty($all_links)) {
				$GLOBALS['Error'] = GetLang('ListLinkStatsHasNotBeenClicked_NoLinksFound');
			} else {
				if ($summary['linkclicks'] > 0) {
					if (is_numeric($chosen_link)) {
						if ($calendar_restrictions != '') {
							$GLOBALS['Error'] = GetLang('ListLinkStatsHasNotBeenClicked_CalendarLinkProblem');
						} else {
							$GLOBALS['Error'] = GetLang('ListLinkStatsHasNotBeenClicked_LinkProblem');
						}
					} else {
						$GLOBALS['Error'] = GetLang('ListLinkStatsHasNotBeenClicked_CalendarProblem');
					}
				} else {
					$GLOBALS['DisplayStatsLinkList'] = 'none';
					$GLOBALS['Error'] = GetLang('ListLinkStatsHasNotBeenClicked');
				}
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Links_Empty', true, false);
		}

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/
		$summary['linkclicks'] = $statsapi->GetClicks($summary['statids'], 0, 0, $chosen_link, $calendar_restrictions, true);

		// build up the summary table.
		$GLOBALS['TotalClicks'] = $this->FormatNumber($summary['linkclicks']);

		$unique_clicks_count = $statsapi->GetUniqueClicks($summary['statids'], $chosen_link, $calendar_restrictions);
		$GLOBALS['TotalUniqueClicks'] = $this->FormatNumber($unique_clicks_count);

		$unique_clicks = $statsapi->GetUniqueClickRecipients($summary['statids'], $calendar_restrictions, $chosen_link);
		$GLOBALS['UniqueClicks'] = $this->FormatNumber($unique_clicks);

		$most_popular_link = $statsapi->GetMostPopularLink($summary['statids'], $chosen_link, $calendar_restrictions);

		$GLOBALS['MostPopularLink'] = htmlspecialchars($most_popular_link, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['MostPopularLink_Short'] = $this->TruncateName($most_popular_link, 20);

		$averageclicks = 0;
		if (isset($GLOBALS['ListStatsOpenCount']) && (int)$GLOBALS['ListStatsOpenCount'] > 0) {
			$open_count = (int)$GLOBALS['ListStatsOpenCount'];
			$averageclicks = $summary['linkclicks'] / $open_count;
		}
		$GLOBALS['AverageClicks'] = $this->FormatNumber($averageclicks, 1);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=List&SubAction=ViewSummary&list=' . $listid . '&tab=3&link=' . $chosen_link;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $summary['statids'],
		  'calendar_restrictions' => $calendar_restrictions,
		  'chosen_link' => $chosen_link,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_links';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('LinksChart', 'list', $listid, 'column', array('graph_title' => GetLang("LinksClickedChart")));

		return $this->ParseTemplate('Stats_Step3_Links', true, false);
	}

	/**
	* DisplayListBounces
	* Displays the list of bounces and bounce summary chart for a mailing list.
	*
	* @param Int $listid The listid to print statistics for.
	* @param Array $summary The list summary information
	* @param Boolean $chosen_bounce_type The type of bounces to display stats for. This can be 'soft', 'hard' or 'any'
	*
	* @see Stats_API::GetBounces
	*
	* @return Void Prints out the list of statistics for this particular mailing list. It doesn't return anything.
	*/
	function DisplayListBounces($listid, $summary, $chosen_bounce_type)
	{
		$GLOBALS['DisplayBouncesIntro'] = sprintf(GetLang('ListStatistics_Snapshot_BounceHeading'), htmlspecialchars($summary['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['BounceAction'] = 'List&list=' . $listid;

		$bouncetypelist = '';
		$all_bounce_types = array('any', 'hard', 'soft');
		if (!in_array($chosen_bounce_type, $all_bounce_types)) {
			$chosen_bounce_type = 'any';
		}
		foreach ($all_bounce_types as $p => $bounce_type) {
			$selected = '';
			if ($bounce_type == $chosen_bounce_type) {
				$selected = ' SELECTED';
			}
			$bouncetypelist .= '<option value="' . $bounce_type . '"' . $selected . '>' . GetLang('Bounce_Type_' . $bounce_type) . '</option>';
		}
		$GLOBALS['StatsBounceList'] = $bouncetypelist;

		$GLOBALS['PPDisplayName'] = 'bc'; // bounce count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=List&NextAction=ViewSummary&list=' . $listid . '&tab=4';

		$calendar_restrictions = $this->CalendarRestrictions['bounces'];

		$GLOBALS['TabID'] = '4';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi('Stats');

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$total_bounces = $summary['bouncecount_soft'] + $summary['bouncecount_hard'] + $summary['bouncecount_unknown'];

		$bounces = array();

		if ($total_bounces > 0) {
			$bounces = $statsapi->GetBounces($summary['statids'], $start, $perpage, $chosen_bounce_type, $calendar_restrictions);
		}

		if (empty($bounces)) {
			if ($calendar_restrictions != '') {
				if ($total_bounces > 0) {
					if (!$chosen_bounce_type || $chosen_bounce_type == 'any') {
						$GLOBALS['Error'] = GetLang('ListStatsHasNotBeenBounced_CalendarProblem');
					} else {
						$GLOBALS['Error'] = sprintf(GetLang('ListStatsHasNotBeenBounced_CalendarProblem_BounceType'), GetLang('Bounce_Type_' . $chosen_bounce_type));
					}
				} else {
					$GLOBALS['Error'] = GetLang('ListStatsHasNotBeenBounced');
				}
			} else {
				if ($total_bounces > 0 && (!$chosen_bounce_type || $chosen_bounce_type == 'any')) {
					$GLOBALS['Error'] = sprintf(GetLang('ListStatsHasNotBeenBounced_BounceType'), GetLang('Bounce_Type_' . $chosen_bounce_type));
				} else {
					$GLOBALS['Error'] = GetLang('ListStatsHasNotBeenBounced');
				}
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Bounces_Empty', true, false);
		}

		/*
		* we can't rely on the counter in the summary table -
		* because you could delete bounced subscribers.
		* and we don't want that to affect the summary table because it distorts statistics.
		*
		* So we do an actual count here for paging.
		*/
		$total_bounces = $statsapi->GetBounces($summary['statids'], $start, $perpage, $chosen_bounce_type, $calendar_restrictions, true);

		$bounce_types_count = $statsapi->GetBounceCounts($summary['statids'], $calendar_restrictions);
		$GLOBALS['TotalBounceCount'] = $this->FormatNumber($bounce_types_count['total']);
		$GLOBALS['TotalSoftBounceCount'] = $this->FormatNumber($bounce_types_count['soft']);
		$GLOBALS['TotalHardBounceCount'] = $this->FormatNumber($bounce_types_count['hard']);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=List&SubAction=ViewSummary&list=' . $listid . '&tab=4&bouncetype=' . $chosen_bounce_type;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token, array(
		  'statid' => $summary['statids'],
		  'calendar_restrictions' => $calendar_restrictions,
		  'chosen_bounce_type' => $chosen_bounce_type,
		  'summary' => $summary
		));

		$GLOBALS['TableType'] = 'newsletter_bounces';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('BounceChart', 'newsletter', $summary['statids'], 'column', array(), $listid);

		return $this->ParseTemplate('Stats_Step3_Bounces', true, false);
	}

	/**
	* DisplayListUnsubscribes
	* Displays the list of unsubscribes and unsubscribes summary chart for a mailing list.
	*
	* @param Int $listid The listid to print statistics for.
	* @param Array $summary The list summary information
	*
	* @see Stats_API::GetUnsubscribes
	*
	* @return Void Prints out the list of statistics for this particular mailing list. It doesn't return anything.
	*/
	function DisplayListUnsubscribes($listid, $summary)
	{
		$GLOBALS['DisplayUnsubscribesIntro'] = sprintf(GetLang('ListStatistics_Snapshot_UnsubscribesHeading'), htmlspecialchars($summary['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['PPDisplayName'] = 'uc'; // unsubscribe count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=List&NextAction=ViewSummary&list=' . $listid . '&tab=5';

		$calendar_restrictions = $this->CalendarRestrictions['unsubscribes'];

		$GLOBALS['TabID'] = '5';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$statsapi = $this->GetApi('Stats');

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$unsubscribes = array();

		if ($summary['unsubscribecount'] > 0) {
			$unsubscribes = $statsapi->GetUnsubscribes($summary['statids'], $start, $perpage, $calendar_restrictions, false, 'unsubscribetime', 'desc', $listid);
		}

		if (empty($unsubscribes)) {
			if ($summary['unsubscribecount'] > 0) {
				$GLOBALS['Error'] = GetLang('ListHasNoUnsubscribes_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('ListHasNoUnsubscribes');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Unsubscribes_Empty', true, false);
		}

		if ($calendar_restrictions != '') {
			$summary['unsubscribecount'] = $statsapi->GetUnsubscribes($summary['statids'], $start, $perpage, $calendar_restrictions, true, '', '', $listid);
		}

		$GLOBALS['TotalUnsubscribes'] = $this->FormatNumber($summary['unsubscribecount']);

		$most_unsubscribes = $statsapi->GetMostUnsubscribes($summary['statids'], $calendar_restrictions, $listid);

		$now = getdate();

		if (isset($most_unsubscribes['mth'])) {
			$GLOBALS['MostUnsubscribes'] = $this->Months[$most_unsubscribes['mth']] . ' ' . $most_unsubscribes['yr'];
		}

		if (isset($most_unsubscribes['hr'])) {
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(mktime($most_unsubscribes['hr'], 1, 1, 1, 1, $now['year']), GetLang('Daily_Time_Display'));
		}

		if (isset($most_unsubscribes['dow'])) {
			$pos = array_search($most_unsubscribes['dow'], array_keys($this->days_of_week));
			// we need to add 1 hour here otherwise we get the wrong day from strtotime.
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(strtotime("last " . $this->days_of_week[$pos] . " +1 hour"), GetLang('Date_Display_Display'));
		}

		if (isset($most_unsubscribes['dom'])) {
			$month = $now['mon'];
			// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
			if ($most_unsubscribes['dom'] > $now['mday']) {
				$month = $now['mon'] - 1;
			}
			$GLOBALS['MostUnsubscribes'] = $this->PrintDate(mktime(0, 0, 1, $month, $most_unsubscribes['dom'], $now['year']), GetLang('Date_Display_Display'));
		}

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=List&SubAction=ViewSummary&list=' . $listid . '&tab=5';

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token,
			array(
				'statid' => $summary['statids'],
				'calendar_restrictions' => $calendar_restrictions,
				'summary' => $summary,
				'listid' => $listid
			)
		);

		$GLOBALS['TableType'] = 'newsletter_unsubscribes';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('UnsubscribeChart', 'list', $summary['statids'], 'column', array('graph_title' => GetLang("UnsubscribesChart")), $listid);

		return $this->ParseTemplate('Stats_Step3_Unsubscribes', true, false);
	}

	/**
	* DisplayListForwards
	* Displays the list of forwards and forwards summary chart for a mailing list.
	*
	* @param Int $listid The listid to print statistics for.
	* @param Array $summary The list summary information
	*
	* @see Stats_API::GetForwards
	*
	* @return Void Prints out the list of statistics for this particular mailing list. It doesn't return anything.
	*/
	function DisplayListForwards($listid, $summary)
	{
		$GLOBALS['DisplayForwardsIntro'] = sprintf(GetLang('ListStatistics_Snapshot_ForwardsHeading'), htmlspecialchars($summary['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['PPDisplayName'] = 'fc'; // forward count

		$base_action = $GLOBALS['PPDisplayName'].'&SubAction=List&NextAction=ViewSummary&list=' . $listid . '&tab=6';

		$calendar_restrictions = $this->CalendarRestrictions['forwards'];

		$GLOBALS['TabID'] = '6';

		$this->SetupCalendar('Action=ProcessCalendar&' . $base_action);

		$perpage = $this->GetPerPage();

		$DisplayPage = (isset($_GET['DisplayPage' . $GLOBALS['PPDisplayName']])) ? (int)$_GET['DisplayPage' . $GLOBALS['PPDisplayName']] : 1;

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$statsapi = $this->GetApi('Stats');

		$forwards = array();

		if ($summary['emailforwards'] > 0) {
			$forwards = $statsapi->GetForwards($summary['statids'], $start, $perpage, $calendar_restrictions);
		}

		if (empty($forwards)) {
			if ($summary['emailforwards'] > 0) {
				$GLOBALS['Error'] = GetLang('ListHasNoForwards_CalendarProblem');
			} else {
				$GLOBALS['Error'] = GetLang('ListHasNoForwards');
			}
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->ParseTemplate('Stats_Step3_Forwards_Empty', true, false);
		}

		if ($calendar_restrictions != '') {
			$summary['emailforwards'] = $statsapi->GetForwards($summary['statids'], $start, $perpage, $calendar_restrictions, true);
		}

		$GLOBALS['TotalForwards'] = $this->FormatNumber($summary['emailforwards']);

		$new_signups = $statsapi->GetForwards($summary['statids'], $start, $perpage, $calendar_restrictions, true, true);

		$GLOBALS['TotalForwardSignups'] = $this->FormatNumber($new_signups);

		$GLOBALS['FormAction'] = 'Action=ProcessPaging' . $base_action;

		$GLOBALS['PAGE'] = 'Stats&Action=List&SubAction=ViewSummary&list=' . $listid . '&tab=6';

		$this->SetupPaging($summary['emailforwards'], $DisplayPage, $perpage);

		$paging = $this->ParseTemplate('Paging', true, false);

		$GLOBALS['Paging'] = $paging;

		$forward_list = '';
		foreach ($forwards as $k => $forwarddetails) {
			$GLOBALS['ForwardedTo'] = htmlspecialchars($forwarddetails['forwardedto'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['ForwardedBy'] = htmlspecialchars($forwarddetails['forwardedby'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['ForwardTime'] = $this->PrintTime($forwarddetails['forwardtime'], true);
			if ($forwarddetails['subscribed'] > 0) {
				$hassubscribed = GetLang('Yes');
			} else {
				$hassubscribed = GetLang('No');
			}
			$GLOBALS['HasSubscribed'] = $hassubscribed;
			$forward_list .= $this->ParseTemplate('Stats_Step3_Forwards_Row', true, false);
		}
		$GLOBALS['Stats_Step3_Forwards_List'] = $forward_list;

		$token = "stats" . md5(uniqid('_'));

		IEM::sessionSet($token,
			array(
				'statid' => $summary['statids'],
				'calendar_restrictions' => $calendar_restrictions,
				'summary' => $summary,
				'listid' => $listid
			)
		);

		$GLOBALS['TableType'] = 'forwards';
		$GLOBALS['TableToken'] = $token;
		$GLOBALS['Loading_Indicator'] = $this->ParseTemplate('Loading_Indicator', true);

		$this->DisplayChart('ForwardsChart', 'list', $summary['statids'], 'column', array('graph_title' => GetLang("ForwardsChart")));

		return $this->ParseTemplate('Stats_Step3_Forwards', true, false);
	}

	/**
	 * IsOwner
	 * Checks whether the current user matches the owner ID passed in.
	 *
	 * @param Int $expected The owner ID to check.
	 *
	 * @return Boolean True if the current user is the owner or an admin, otherwise false.
	 */
	function IsOwner($expected)
	{
		$user = IEM::userGetCurrent();
		$actual = $user->Get('userid');
		if ($user->Admin()) {
			return true;
		}
		return (intval($expected) === intval($actual));
	}

	/**
	 * CanAccessList
	 * Checks whether the current user can access a particular contact list.
	 *
	 * @param Int $list_id The ID of the contact list.
	 *
	 * @return Boolean True if the user can access the list, otherwise false.
	 */
	function CanAccessList($list_id)
	{
		$user = IEM::userGetCurrent();
		$allowed_lists = $user->GetLists();
		if (is_array($allowed_lists)) {
			$allowed_lists = array_keys($allowed_lists);
			if (in_array($list_id, $allowed_lists)) {
				return true;
			}
		}
		return $user->Admin();
	}

	/**
	 * CanAccessStats
	 * Checks whether the current user can access a particular stats record.
	 *
	 * @param Int $stat_id The ID of the statistics record.
	 * @param String $type Either 'n' for newsletters or 'a' for autoresponders.
	 *
	 * @return Boolean True if the user can access the stats, otherwise false.
	 */
	function CanAccessStats($stat_id, $type)
	{
		$user = IEM::userGetCurrent();
		$stats_api = $this->GetApi('Stats');
		$stat_record = $stats_api->FetchStats($stat_id, $type);
		if (!isset($stat_record['statid'])) {
			return false;
		}
		if ($type == 'n') {
			$api = $this->GetApi('Newsletters');
			$api->Load($stat_record['newsletterid']);
			$list_ids = $stat_record['Lists'];
			$access = $user->HasAccess('statistics', 'newsletter');
		} elseif ($type == 'a') {
			$api = $this->GetApi('Autoresponders');
			$api->Load($stat_record['autoresponderid']);
			$access = $user->HasAccess('statistics', 'autoresponder');
		} else {
			return false;
		}
		if ($access && $this->IsOwner($api->ownerid)) {
			return true;
		}
		// They also have access if they have access to the list.
		// We should probably change this, as it means the stats of a campaign
		// sent to multiple lists can be deleted by someone who has access to
		// at least one of those lists.
		$list_ids = $stat_record['Lists'];
		foreach ($list_ids as $list_id) {
			if ($this->CanAccessList($list_id)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * CanAccessAutoresponder
	 * Returns true if the current user is allowed access to the autoresponder.
	 *
	 * @param Int $id The ID of the autoresponder.
	 *
	 * @return Boolean True if the current user is allowed to access the autoresponder, otherwise false.
	 */
	function CanAccessAutoresponder($id)
	{
		$user = IEM::userGetCurrent();
		if (!$user->HasAccess('statistics', 'autoresponder')) {
			return false;
		}
		$api = $this->GetApi('Autoresponders');
		if (!$api->Load($id)) {
			return false;
		}
		$list_id = $api->listid;
		return $this->CanAccessList($list_id);
	}

	// --------------------------------------------------------------------------------
	// Methods related to Trigger Emails Statistics
	// --------------------------------------------------------------------------------
		/**
		 * TriggerEmailsStats
		 * Handle displaying statistics for trigger emails.
		 *
		 * @param String $subaction Request sub-action
		 * @return Void Prints output directly to stdout without returning anything.
		 */
		function TriggerEmailsStats($subaction)
		{
			// ----- Check user access
				$tempUser = IEM::userGetCurrent();
				if (!$tempUser->HasAccess('Statistics', 'TriggerEmails') || !check('TriggerEmails')) {
					$this->PrintNewsletterStats_Step1();
					return;
				}
				unset($tempUser);
			// -----


			$contents = '';

			switch ($subaction) {
				case 'view':
					$contents = $this->TriggerEmailsStats_View();
				break;

				case 'delete':
					$this->TriggerEmailsStats_Delete();
				break;

				case 'bulkaction':
					$this->TriggerEmailsStats_BulkAction();
				break;

				default:
					$contents = $this->TriggerEmailsStats_List();
				break;
			}

			print $contents;
		}

		/**
		 * TriggerEmailsStats_Delete
		 * Handle trigger email statistics deletion
		 *
		 * @return Void Prints output directly to stdout without returning anything.
		 *
		 * @uses SendStudio_Functions::LoadLanguageFile()
		 * @uses SendStudio_Functions::GetApi()
		 * @uses SendStudio_Functions::DenyAccess()
		 * @uses Stats_API::GetTriggerEmailsStats()
		 * @uses Stats_API::HideStats
		 * @uses User::Admin()
		 * @uses User::GetRecordByAssociatedLinkStatisticID()
		 * @uses FlashMessage
		 */
		function TriggerEmailsStats_Delete()
		{
			// ----- Sanitize and declare variables that is going to be used in this function
				$user 		= IEM::userGetCurrent();
				$action		= isset($_GET['bulkAction']) ? strtolower($_GET['bulkAction']) : '';
				$api		= $this->GetApi();
				$id			= intval(isset($_POST['id']) ? $_POST['id'] : 0);
			// -----

			$this->LoadLanguageFile('TriggerEmails');

			if ($id == 0) {
				FlashMessage(GetLang('TriggerEmails_Stats_BulkAction_Delete_Failed'), SS_FLASH_MSG_ERROR, 'index.php?Page=Stats&Action=TriggerEmails');
				exit();
			}

			// If this user is not an admin, then check the ownership of the trigger
			if (!$user->Admin()) {
				$triggerAPI	= $this->GetApi('TriggerEmails');

				$record = $triggerAPI->GetRecordByAssociatedStatisticID($id);

				if (!$record || empty($record)) {
					$this->DenyAccess();
					exit();
				}

				if ($record['ownerid'] != $user->userid) {
					$this->DenyAccess();
					exit();
				}

				unset($record);
				unset($triggerAPI);
			}

			$status = $api->HideStats($id, 'n', $user->userid);
			if ($status) {
				FlashMessage(GetLang('TriggerEmails_Stats_BulkAction_Delete_Success'), SS_FLASH_MSG_SUCCESS, 'index.php?Page=Stats&Action=TriggerEmails');
			} else {
				FlashMessage(GetLang('TriggerEmails_Stats_BulkAction_Delete_Failed'), SS_FLASH_MSG_ERROR, 'index.php?Page=Stats&Action=TriggerEmails');
			}

			exit();
		}

		/**
		 * TriggerEmailsStats_BulkAction
		 * Handle trigger email "bulk action"
		 *
		 * @return Void Prints output directly to stdout without returning anything.
		 *
		 * @uses SendStudio_Functions::LoadLanguageFile()
		 * @uses SendStudio_Functions::GetApi()
		 * @uses SendStudio_Functions::DenyAccess()
		 * @uses Stats_API::GetTriggerEmailsStats()
		 * @uses Stats_API::HideStats
		 * @uses User::Admin()
		 * @uses User::GetRecordByAssociatedLinkStatisticID()
		 * @uses FlashMessage
		 */
		function TriggerEmailsStats_BulkAction()
		{
			// ----- Sanitize and declare variables that is going to be used in this function
				$user 		= IEM::userGetCurrent();
				$action		= isset($_GET['bulkAction']) ? strtolower($_GET['bulkAction']) : '';
				$api		= $this->GetApi();
				$id			= isset($_POST['id']) ? $_POST['id'] : array();

				$msg		= '';
				$msgType	= SS_FLASH_MSG_ERROR;
			// -----

			$this->LoadLanguageFile('TriggerEmails');

			if (!is_array($id)) {
				$id = array($id);
			}

			$id = array_map('intval', $id);

			// If this user is not an admin, then check the ownership of the trigger
			if (!$user->Admin()) {
				$triggerAPI	= $this->GetApi('TriggerEmails');

				$records = $triggerAPI->GetRecordByAssociatedLinkStatisticID($id);
				if (!$records || count($records) == 0) {
					$this->DenyAccess();
					exit();
				}

				foreach ($records as $record) {
					if ($record['ownerid'] != $user->userid) {
						$this->DenyAccess();
						exit();
					}
				}

				unset($records);
				unset($record);
				unset($triggerAPI);
			}

			switch ($action) {
				case 'delete':
					$status = $api->HideStats($id, 'n', $user->userid);
					if ($status) {
						$msg = GetLang('TriggerEmails_Stats_BulkAction_Delete_Success');
						$msgType = SS_FLASH_MSG_SUCCESS;
					} else {
						$msg = GetLang('TriggerEmails_Stats_BulkAction_Delete_Failed');
						$msgType = SS_FLASH_MSG_ERROR;
					}
				break;

				default:
					$msg = GetLang('TriggerEmails_Stats_BulkAction_UnknownAction');
					$msgType = SS_FLASH_MSG_WARNING;
				break;
			}

			FlashMessage($msg, $msgType, 'index.php?Page=Stats&Action=TriggerEmails');
			exit();
		}

		/**
		 * TriggerEmailsStats_List
		 * Handle listing of the trigger emails statistics
		 *
		 * @return Void Prints output directly to stdout without returning anything.
		 *
		 * @uses Stats_API::GetTriggerEmailsStats()
		 */
		function TriggerEmailsStats_List()
		{
			// ----- Sanitize and declare variables that is going to be used in this function
				$user = IEM::userGetCurrent();

				$pageRecordPP		= 0;
				$pageCurrentIndex	= $this->GetCurrentPage();
				$pageSortInfo		= $this->GetSortDetails();

				$requestSubAction	= isset($_GET['SubAction'])? strtolower($_GET['SubAction']) : '';

				$records			= array();
				$recordTotal		= 0;
				$listCount			= 0;
				$newsletterCount	= 0;

				$api				= $this->GetApi();

				$page = array(
					'messages'	=> GetFlashMessages()
				);

				$permissions = array(
					'create'			=> $user->HasAccess('triggeremails', 'create'),
					'edit'				=> $user->HasAccess('triggeremails', 'edit'),
					'delete'			=> $user->HasAccess('triggeremails', 'delete'),
					'activate'			=> $user->HasAccess('triggeremails', 'activate'),
					'createList'		=> true,
					'createNewsletter'	=> true
				);
			// -----

			/**
			 * Get list and newsletter count
			 */
				$tempListRecords = $user->GetLists();
				$listCount = count($tempListRecords);
				unset($tempListRecords);

				$tempNewsletterRecords = $user->GetNewsletters();
				$newsletterCount = count($tempNewsletterRecords);
				unset($tempNewsletterRecords);
			/**
			 * -----
			 */


			// Load additional language variable for displaying trigger email statistics
			$this->LoadLanguageFile('TriggerEmails');


			if (!SENDSTUDIO_CRON_ENABLED || SENDSTUDIO_CRON_TRIGGEREMAILS_S <= 0 || SENDSTUDIO_CRON_TRIGGEREMAILS_P <= 0) {
				$page['messages'] .= $this->PrintWarning('TriggerEmails_Manage_CRON_Alert');
			}


			// ----- Get "Record Per Page"
				if ($requestSubAction == 'processpaging') {
					$pageRecordPP = intval($this->_getGETRequest('PerPageDisplay', 10));
					if ($pageRecordPP == 0) {
						$pageRecordPP = 10;
					}
					$this->SetPerPage($pageRecordPP);
				}

				if ($pageRecordPP == 0) {
					$pageRecordPP = $this->GetPerPage();
				}
			// -----


			// ----- Get statistic record and total number of records from database
				$tempTriggerIDs = $user->GetTriggerEmailsList();
				$tempUserID = $user->userid;

				// Admin user do not need to enter their user ID
				if ($user->Admin()) {
					$tempUserID = null;
				}

				if (!empty($tempTriggerIDs)) {
					$tempTriggerIDs = array_keys($tempTriggerIDs);

					$tempStart = 0;
					if ($pageRecordPP != 'all') {
						$tempStart = ($pageCurrentIndex - 1) * $pageRecordPP;
					}

					$records = $api->GetTriggerEmailsStats($tempUserID, $pageSortInfo, false, $tempStart, $pageRecordPP);
					$recordTotal = $api->GetTriggerEmailsStats($tempUserID, array(), true);

					unset($tempStart);
				}

				unset($tempTriggerIDs);
			// -----


			// Calculate pagination, this is still using the older method of pagination
			$GLOBALS['PAGE'] = 'Stats&Action=TriggerEmails&SubAction=Manage';
			$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=TriggerEmails&NextAction=Manage';
			$this->SetupPaging($recordTotal, $pageCurrentIndex, $pageRecordPP);


			// If No statistic records are available, display relevent message to user
			if (count($records) == 0) {
				$page['messages'] .= $this->PrintSuccess('TriggerEmails_Stats_NoTriggerEmailsDefined', GetLang('TriggerEmails_Stats_NoTriggerEmailsDefined'));
			}


			// ----- Print HTML
				$tpl = GetTemplateSystem();
				$tpl->Assign('PAGE', $page);
				$tpl->Assign('records', $records);
				$tpl->Assign('permissions', $permissions);
				$tpl->Assign('newsletterCount', $newsletterCount);
				$tpl->Assign('listCount', $listCount);

				return $tpl->ParseTemplate('Stats_Triggeremails_Manage', true);
			// ----
		}

		/**
		 * TriggerEmailsStats_View
		 * Handle listing of the trigger emails statistics
		 *
		 * @return Void Prints output directly to stdout without returning anything.
		 *
		 * @uses Stats_API::GetTriggerEmailsStats()
		 */
		function TriggerEmailsStats_View()
		{
			// ----- Sanitize and declare variables that is going to be used in this function
				$user = IEM::userGetCurrent();

				$id					= intval($this->_getGETRequest('id', ''));

				$record				= array();
				$triggerrecord		= array();

				$api				= $this->GetApi();
				$triggerapi			= $this->GetApi('TriggerEmails');

				$page = array(
					'messages'		=> GetFlashMessages(),
					'whichtab'		=> intval($this->_getGETRequest('tab', 1)),
					'unique_open'	=> ($this->_getGETRequest('Unique', false) ? true : false),
					'session_token'	=> md5(uniqid(rand()))
				);

				$tabs				= array(
					'snapshot'		=> array(),
					'open'			=> array(),
					'links'			=> array(),
					'bounces'		=> array(),
					'unsubscribe'	=> array(),
					'forward'		=> array(),
					'recipients'	=> array()
				);

			// ----

			if ($id == 0) {
				return $this->TriggerEmailsStats_List();
			}

			// Make sure that user can access this particular trigger email statistics
			if (!$this->_TriggerEmailsStats_Access($id)) {
				return $this->TriggerEmailsStats_List();
			}

			// ----- Load trigger emails statistics record
				$record = $api->GetTriggerEmailsStatsRecord($id);
				$triggerrecord = $triggerapi->GetRecordByID($id, true, true);

				if (!isset($triggerrecord['triggeractions']) || !is_array($triggerrecord['triggeractions'])) {
					$triggerrecord['triggeractions'] = array();
				}

				if (!isset($triggerrecord['triggeractions']['send']) || !is_array($triggerrecord['triggeractions']['send'])) {
					$triggerrecord['triggeractions']['send'] = array();
				}

				$temp = array('trackopens', 'tracklinks');
				foreach ($temp as $each) {
					if (!isset($triggerrecord['triggeractions']['send'][$each])) {
						$triggerrecord['triggeractions']['send'][$each] = 0;
					}
				}

				if (empty($record)) {
					return $this->TriggerEmailsStats_List();
				}
			// -----


			// Log this to "User Activity Log"
			IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/chart_bar.gif', $record['triggeremailsname']);

			// ----- Calculate some common variables for the record
				$record['processed_totalsent'] = intval($record['htmlrecipients']) + intval($record['textrecipients']) + intval($record['multipartrecipients']);
				$record['processed_unopened'] = abs($record['processed_totalsent'] - $record['emailopens_unique']);
				$record['processed_totalbounced'] = intval($record['bouncecount_soft']) + intval($record['bouncecount_hard']) + intval($record['bouncecount_unknown']);

				if ($record['processed_totalsent'] != 0) {
					if ($triggerrecord['triggeractions']['send']['trackopens'] != 0) {
						$record['processed_timeframe_emailopens_total'] = intval($api->GetOpens($record['statid'], 1, 'all', $page['unique_open'], $this->CalendarRestrictions['opens'], true));
						$record['processed_timeframe_emailopens_unique'] = intval($api->GetOpens($record['statid'], 1, 'all', $page['unique_open'], $this->CalendarRestrictions['opens'], true));
					}

					if ($triggerrecord['triggeractions']['send']['tracklinks'] != 0) {
						$record['processed_timeframe_linkclicks_total'] = intval($api->GetClicks($record['statid'], 1, 'all', 'a', $this->CalendarRestrictions['clicks'], true));
						$record['processed_timeframe_linkclicks_unique'] = intval($api->GetUniqueClicks($record['statid'], 'a', $this->CalendarRestrictions['clicks']));
						$record['processed_timeframe_linkclicks_individuals'] = intval($api->GetUniqueClickRecipients($record['statid'], $this->CalendarRestrictions['clicks'], 'a'));
					}

					$record['processed_timeframe_bounces'] = intval($api->GetBounces($record['statid'], 1, 'all', 'any', $this->CalendarRestrictions['bounces'], true));

					$record['processed_timeframe_unsubscribes'] = intval($api->GetUnsubscribes($record['statid'], 1, 'all', $this->CalendarRestrictions['unsubscribes'], true));

					if (array_key_exists('forwards', $this->CalendarRestrictions) && !empty($this->CalendarRestrictions['forwards'])) {
						$record['processed_timeframe_forwards'] = intval($api->GetForwards($record['statid'], 1, 'all', $this->CalendarRestrictions['forwards'], true));
					} else {
						$record['processed_timeframe_forwards']	= intval($record['emailforwards']);
					}

					$record['processed_timeframe_totalsent'] = 0;

					// Set up session information that correspond to the current stats (this information in the session will not be cleaned up, need to refactor)
					// The session infromation is used to print out a table that list email addressess for "open" and "link" tabs
					IEM::sessionSet($page['session_token'], array(
						'statid'				=> $record['statid'],
						'calendar_restrictions'	=> $this->CalendarRestrictions,
						'unique_open_only'		=> $page['unique_open'],
						'summary'				=> $record
					));
				}
			// -----





			// Load additional language variable for displaying trigger email statistics
			$this->LoadLanguageFile('TriggerEmails');

			// Include the charting tool
			include_once (SENDSTUDIO_FUNCTION_DIRECTORY . '/amcharts/amcharts.php');


			// ----- Tab 1: Snapshot
				$tabs['snapshot']['intro'] = sprintf(GetLang('TriggerEmails_Stats_Snapshots_Intro'), $record['triggeremailsname']);
				$tabs['snapshot']['newsletter_uniqueopen'] = sprintf(GetLang('EmailOpens_Unique'), $this->FormatNumber($record['emailopens_unique']));
				$tabs['snapshot']['newsletter_totalopen'] = sprintf(GetLang('EmailOpens_Total'), $this->FormatNumber($record['emailopens']));
				$tabs['snapshot']['newsletter_bounce'] = $this->FormatNumber($record['processed_totalbounced']);
				$tabs['snapshot']['url_open_url'] = 'index.php?Page=Stats&Action=TriggerEmails&SubAction=view&tab=2&id=' . $id;
				$tabs['snapshot']['url_openunique_url'] = $tabs['snapshot']['url_open_url'] . '&Unique=1';

				$tabs['snapshot']['summary_chart'] = InsertChart(
					'pie',
					'stats_chart.php?Opens=' . $record['emailopens_unique'] . '&Unopened=' . $record['processed_unopened'] . '&Bounced=' . $record['processed_totalbounced'] . '&Area=TriggerEmails&'. IEM::SESSION_NAME . '=' . IEM::sessionID(),
					array('graph_title' => sprintf(GetLang('TriggerEmails_Stats_Snapshots_ChartTitle'), $record['triggeremailsname'])));
			// -----

			// ----- Tab 2: Open rates
				$tabs['open']['intro'] = sprintf(GetLang('TriggerEmails_Stats_Open_Intro'), $record['triggeremailsname']);

				// setup calendar
				$GLOBALS['TabID'] = '1';
				$this->SetupCalendar('Action=ProcessCalendar&SubAction=TriggerEmails&NextAction=View&tab=2&id=' . $id);
				$tabs['open']['calendar'] = $GLOBALS['Calendar'];
				unset($GLOBALS['TabID']);
				unset($GLOBALS['Calendar']);


				// Set up error message if no "opens" count is not available
				if (!array_key_exists('processed_timeframe_emailopens_total', $record) || !$record['processed_timeframe_emailopens_total']) {
					$tempMessage = 'TriggerEmails_Stats_Open_Error_HasNotBeenOpened';
					$tempRestriction = $this->CalendarRestrictions;

					if ($triggerrecord['triggeractions']['send']['trackopens'] == 0) {
						$tempMessage = 'TriggerEmails_Stats_Open_Error_NotOpenTracked';
					} elseif (array_key_exists('opens', $tempRestriction) && !empty($tempRestriction['opens'])) {
						$tempMessage = 'TriggerEmails_Stats_Open_Error_HasNotBeenOpened_CalendarProblem';
					}

					$GLOBALS['Error'] = GetLang($tempMessage);
					$tabs['open']['message'] = $this->ParseTemplate('ErrorMsg', true, false);
					unset($GLOBALS['Error']);


				// Set up open information otherwise
				} else {
					$tabs['open']['email_opens_total'] = $this->FormatNumber($record['processed_timeframe_emailopens_total']);
					$tabs['open']['email_opens_unique'] = $this->FormatNumber($record['processed_timeframe_emailopens_unique']);

					// ----- Most opens
						$tempMostOpens = $api->GetMostOpens($record['statid'], $this->CalendarRestrictions['opens']);
						$tempNow = getdate();

						if (isset($tempMostOpens['mth'])) {
							$tabs['open']['most_open_date'] = $this->Months[$tempMostOpens['mth']] . ' ' . $tempMostOpens['yr'];

						} elseif (isset($tempMostOpens['hr'])) {
							$tabs['open']['most_open_date'] = date(GetLang('Daily_Time_Display'),mktime($tempMostOpens['hr'], 1, 1, 1, 1, $tempNow['year']));

						} elseif (isset($tempMostOpens['dow'])) {
							$pos = array_search($tempMostOpens['dow'], array_keys($this->days_of_week));
							$tabs['open']['most_open_date'] = date(GetLang('Date_Display_Display'), strtotime("last " . $this->days_of_week[$pos]));

						} elseif (isset($tempMostOpens['dom'])) {
							$month = $tempNow['mon'];
							// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
							if ($tempMostOpens['dom'] > $tempNow['mday']) {
								$month = $tempNow['mon'] - 1;
							}

							$tabs['open']['most_open_date'] = date(GetLang('Date_Display_Display'),mktime(0, 0, 1, $month, $tempMostOpens['dom'], $tempNow['year']));
						}

						unset($tempNow);
						unset($tempMostOpens);
					// -----

					// ----- Average opens
						$tabs['open']['average_opens'] = 0;
						if ($record['processed_totalsent'] > 0) {
							$tempAverage = $record['processed_timeframe_emailopens_total'] / $record['processed_totalsent'];
							$tabs['open']['average_opens'] = $this->FormatNumber($tempAverage, 3);
							unset($tempAverage);
						}
					// -----

					// ----- Open rate
						$tabs['open']['open_rate'] = '0%';
						if ($record['processed_totalsent'] > 0) {
							$tempOpenRate = $record['processed_timeframe_emailopens_unique'] / $record['processed_totalsent'] * 100;
							$tabs['open']['open_rate'] = $this->FormatNumber($tempOpenRate, 2) . '%' ;
							unset($tempOpenRate);
						}
					// -----

					// Setup chart
					$this->DisplayChart('OpenChart', 'triggeremails', $record['statid'], 'column', array('graph_title' => GetLang('OpensChart')));
					$tabs['open']['open_chart'] = $GLOBALS['OpenChart'];
					unset($GLOBALS['OpenChart']);
				}
			// -----

			// ----- Tab 3: Links (TODO: when user chooses a specific link. Currently this is being ignored)
				$tabs['links']['intro'] = sprintf(GetLang('TriggerEmails_Stats_Links_Intro'), $record['triggeremailsname']);

				// setup calendar
				$GLOBALS['TabID'] = '2';
				$this->SetupCalendar('Action=ProcessCalendar&SubAction=TriggerEmails&NextAction=View&tab=3&id=' . $id);
				$tabs['links']['calendar'] = $GLOBALS['Calendar'];
				unset($GLOBALS['TabID']);
				unset($GLOBALS['Calendar']);

				// Set up error message if no "links" count is not available
				if (!array_key_exists('processed_timeframe_linkclicks_total', $record) || !$record['processed_timeframe_linkclicks_total']) {
					$tempMessage = 'TriggerEmails_Stats_Links_Error_NoLinksFound';
					$tempRestriction = $this->CalendarRestrictions;

					if ($triggerrecord['triggeractions']['send']['tracklinks'] == 0) {
						$tempMessage = 'TriggerEmails_Stats_Links_Error_NotLinkTracked';
					} elseif (array_key_exists('clicks', $tempRestriction) && !empty($tempRestriction['clicks'])) {
						$tempMessage = 'TriggerEmails_Stats_Links_Error_NoLinksFound_CalendarProblem';
					}

					$GLOBALS['Error'] = GetLang($tempMessage);
					$tabs['links']['message'] = $this->ParseTemplate('ErrorMsg', true, false);
					unset($GLOBALS['Error']);


				// Set up open information otherwise
				} else {
					$tabs['links']['linkclicks_total'] = $this->FormatNumber($record['processed_timeframe_linkclicks_total']);
					$tabs['links']['linkclicks_unique'] = $this->FormatNumber($record['processed_timeframe_linkclicks_unique']);
					$tabs['links']['linkclicks_individuals'] = $this->FormatNumber($record['processed_timeframe_linkclicks_individuals']);

					// ----- Most popular
						$most_popular_link = $api->GetMostPopularLink($record['statid'], 'a', $this->CalendarRestrictions['clicks']);
						$most_popular_link = htmlspecialchars($most_popular_link, ENT_QUOTES, SENDSTUDIO_CHARSET);

						$tabs['links']['most_popular_link'] = $most_popular_link;
						$tabs['links']['most_popular_link_short'] = $this->TruncateName($most_popular_link, 20);

						unset($most_popular_link);
					// -----

					// ----- Average clicks per-email-opens
						$tabs['links']['average_clicks'] = '0';
						if ($record['emailopens'] > 0) {
							$tabs['links']['average_clicks'] = $this->FormatNumber(($record['linkclicks'] / $record['emailopens']), 3);
						}
					// -----

					// ----- Clickthrough rate
						$tabs['links']['click_through'] = '0%';
						if ($record['processed_totalsent'] > 0) {
							$tempClickThroughRate = $record['processed_timeframe_linkclicks_unique'] / $record['processed_totalsent'] * 100;
							$tabs['links']['click_through'] = $this->FormatNumber($tempClickThroughRate, 2) . '%';
							unset($tempClickThroughRate);
						}
					// -----

					// Setup chart
					$this->DisplayChart('LinksChart', 'triggeremails', $record['statid'], 'column', array('graph_title' => GetLang('LinksClickedChart')));
					$tabs['links']['link_chart'] = $GLOBALS['LinksChart'];
					unset($GLOBALS['LinksChart']);
				}
			// -----

			// ----- Tab 4: Bounces (TODO: Cannot filter the bounce under soft/hard)
				$tabs['bounces']['intro'] = sprintf(GetLang('TriggerEmails_Stats_Bounces_Intro'), $record['triggeremailsname']);

				// setup calendar
				$GLOBALS['TabID'] = '3';
				$this->SetupCalendar('Action=ProcessCalendar&SubAction=TriggerEmails&NextAction=View&tab=4&id=' . $id);
				$tabs['bounces']['calendar'] = $GLOBALS['Calendar'];
				unset($GLOBALS['TabID']);
				unset($GLOBALS['Calendar']);

				// Set up error message if no "bounces" count is not available
				if (!array_key_exists('processed_timeframe_bounces', $record) || !$record['processed_timeframe_bounces']) {
					$tempMessage = 'TriggerEmails_Stats_Bounces_Error_NoBouncesFound';
					$tempRestriction = $this->CalendarRestrictions;

					if (array_key_exists('clicks', $tempRestriction) && !empty($tempRestriction['clicks'])) {
						$tempMessage = 'TriggerEmails_Stats_Links_Error_NoLinksFound_CalendarProblem';
					}

					$GLOBALS['Error'] = GetLang($tempMessage);
					$tabs['bounces']['message'] = $this->ParseTemplate('ErrorMsg', true, false);
					unset($GLOBALS['Error']);


				// Set up open information otherwise
				} else {
					$tabs['bounces']['bounces_total'] = $this->FormatNumber($record['processed_totalbounced']);
					$tabs['bounces']['bounces_soft'] = $this->FormatNumber(intval($record['bouncecount_soft']));
					$tabs['bounces']['bounces_hard'] = $this->FormatNumber(intval($record['bouncecount_hard']));
					$tabs['bounces']['bounces_unknown'] = $this->FormatNumber(intval($record['bouncecount_unknown']));

					// Setup chart
					$this->DisplayChart('BounceChart', 'triggeremails', $record['statid'], 'column', array('graph_title' => GetLang('BounceChart')));
					$tabs['bounces']['bounce_chart'] = $GLOBALS['BounceChart'];
					unset($GLOBALS['BounceChart']);
				}
			// -----

			// ----- Tab 5: Unsubscribe
				$tabs['unsubscribes']['intro'] = sprintf(GetLang('TriggerEmails_Stats_Unsubscribes_Intro'), $record['triggeremailsname']);

				// setup calendar
				$GLOBALS['TabID'] = '4';
				$this->SetupCalendar('Action=ProcessCalendar&SubAction=TriggerEmails&NextAction=View&tab=5&id=' . $id);
				$tabs['unsubscribes']['calendar'] = $GLOBALS['Calendar'];
				unset($GLOBALS['TabID']);
				unset($GLOBALS['Calendar']);

				// Set up error message if no "unsubscribes" count is not available
				if (!array_key_exists('processed_timeframe_unsubscribes', $record) || !$record['processed_timeframe_unsubscribes']) {
					$tempMessage = 'TriggerEmails_Stats_Unsubscribes_Error_NoUnsubscribesFound';
					$tempRestriction = $this->CalendarRestrictions;

					if (array_key_exists('bounces', $tempRestriction) && !empty($tempRestriction['bounces'])) {
						$tempMessage = 'TriggerEmails_Stats_Unsubscribes_Error_NoUnsubscribesFound_CalendarProblem';
					}

					$GLOBALS['Error'] = GetLang($tempMessage);
					$tabs['unsubscribes']['message'] = $this->ParseTemplate('ErrorMsg', true, false);
					unset($GLOBALS['Error']);


				// Set up open information otherwise
				} else {
					$tabs['unsubscribes']['unsubscribes_total'] = $this->FormatNumber($record['processed_timeframe_unsubscribes']);

					// ----- Most unsubscribe
						$tempMostUnsubscribes = $api->GetMostUnsubscribes($record['statid'], $this->CalendarRestrictions['unsubscribes']);
						$tempNow = getdate();

						if (isset($tempMostUnsubscribes['mth'])) {
							$tabs['unsubscribes']['unsubscribes_most'] = $this->Months[$tempMostUnsubscribes['mth']] . ' ' . $tempMostUnsubscribes['yr'];

						} elseif (isset($tempMostUnsubscribes['hr'])) {
							$tabs['unsubscribes']['unsubscribes_most'] = $this->PrintDate(mktime($tempMostUnsubscribes['hr'], 1, 1, 1, 1, $tempNow['year']), GetLang('Daily_Time_Display'));

						} elseif (isset($tempMostUnsubscribes['dow'])) {
							$pos = array_search($tempMostUnsubscribes['dow'], array_keys($this->days_of_week));
							// we need to add 1 hour here otherwise we get the wrong day from strtotime.
							$tabs['unsubscribes']['unsubscribes_most'] = $this->PrintDate(strtotime("last " . $this->days_of_week[$pos] . " +1 hour"), GetLang('Date_Display_Display'));

						} elseif (isset($tempMostUnsubscribes['dom'])) {
							$month = $tempNow['mon'];
							// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
							if ($tempMostUnsubscribes['dom'] > $tempNow['mday']) {
								$month = $tempNow['mon'] - 1;
							}

							$tabs['unsubscribes']['unsubscribes_most'] = $this->PrintDate(mktime(0, 0, 1, $month, $tempMostUnsubscribes['dom'], $tempNow['year']), GetLang('Date_Display_Display'));
						}

						unset($tempNow);
						unset($tempMostUnsubscribes);
					// -----

					// Setup chart
					$this->DisplayChart('UnsubscribeChart', 'triggeremails', $record['statid'], 'column', array('graph_title' => GetLang('UnsubscribesChart')));
					$tabs['unsubscribes']['unsubscribe_chart'] = $GLOBALS['UnsubscribeChart'];
					unset($GLOBALS['UnsubscribeChart']);
				}
			// -----

			// ----- Tab 6: Forwards
				$tabs['forwards']['intro'] = sprintf(GetLang('TriggerEmails_Stats_Forwards_Intro'), $record['triggeremailsname']);

				// setup calendar
				$GLOBALS['TabID'] = '5';
				$this->SetupCalendar('Action=ProcessCalendar&SubAction=TriggerEmails&NextAction=View&tab=6&id=' . $id);
				$tabs['forwards']['calendar'] = $GLOBALS['Calendar'];
				unset($GLOBALS['TabID']);
				unset($GLOBALS['Calendar']);

				// Set up error message if no "forwards" count is not available
				if (!array_key_exists('processed_timeframe_forwards', $record) || !$record['processed_timeframe_forwards']) {
					$tempMessage = 'TriggerEmails_Stats_Forwards_Error_NoForwardFound';
					$tempRestriction = $this->CalendarRestrictions;

					if (array_key_exists('forwards', $tempRestriction) && !empty($tempRestriction['forwards'])) {
						$tempMessage = 'TriggerEmails_Stats_Forwards_Error_NoForwardFound_CalendarProblem';
					}

					$GLOBALS['Error'] = GetLang($tempMessage);
					$tabs['forwards']['message'] = $this->ParseTemplate('ErrorMsg', true, false);
					unset($GLOBALS['Error']);


				// Set up open information otherwise
				} else {
					$tabs['forwards']['forward_total'] = $this->FormatNumber($record['processed_timeframe_forwards']);

					// ----- Total new Signups
						$temp = intval($api->GetForwards($record['statid'], 1, 'all', $this->CalendarRestrictions['forwards'], true, true));
						$tabs['forwards']['forward_signups'] = $this->FormatNumber($temp);
						unset($temp);
					// -----

					// Setup chart
					$this->DisplayChart('ForwardsChart', 'triggeremails', $record['statid'], 'column', array('graph_title' => GetLang('ForwardsChart')));
					$tabs['forwards']['forwards_chart'] = $GLOBALS['ForwardsChart'];
					unset($GLOBALS['ForwardsChart']);
				}
			// -----

			// ----- Tab 7: Contact info
				$tabs['recipients'] = $this->_TriggerEmailsStats_View_Tab7($record);
			// -----

			// ----- Tab 8: Failed sending info
				$tabs['failed'] = $this->_TriggerEmailsStats_View_Tab8($record);
			// -----



			// ----- Print HTML
				$tpl = GetTemplateSystem();
				$tpl->Assign('PAGE', $page);
				$tpl->Assign('record', $record);
				$tpl->Assign('tabs', $tabs);

				return $tpl->ParseTemplate('Stats_Triggeremails_Summary', true);
			// -----
		}



		/**
		 * _TriggerEmailsStats_View_Tab7
		 * "Tab 7" of the trigger email statistics
		 *
		 * @param Array $record An associative array of the trigger email statistic record
		 * @return Array Returns tab information that is used by TriggerEmailsStats_View method
		 *
		 * @see Stats::TriggerEmailsStats_View()
		 */
		function _TriggerEmailsStats_View_Tab7($record)
		{
			// ----- Sanitize and declare variables that is going to be used in this function
				$user 					= IEM::userGetCurrent();

				$pageRecordPP			= 0;
				$pageCurrentIndex		= IEM::requestGetGET('DisplayPagetriggerrecipients', 1, 'intval');
				$calendarRestrictions	= array_key_exists('recipients', $this->CalendarRestrictions) ? $this->CalendarRestrictions['recipients'] : '';

				$requestAction			= isset($_GET['Action'])? strtolower($_GET['Action']) : '';

				$api					= $this->GetApi('TriggerEmails');

				$tabinfo				= array();
			// ----

			$tabinfo['intro'] = sprintf(GetLang('TriggerEmails_Stats_Recipients_Intro'), $record['triggeremailsname']);

			// setup calendar
			$GLOBALS['TabID'] = '6';
			$this->SetupCalendar('Action=ProcessCalendar&SubAction=TriggerEmails&NextAction=View&tab=7&id=' . $record['triggeremailsid']);
			$tabinfo['calendar'] = $GLOBALS['Calendar'];
			unset($GLOBALS['TabID']);
			unset($GLOBALS['Calendar']);

			$tabinfo['record_count'] = intval($api->GetRecipientList($record['triggeremailsid'], $pageCurrentIndex, $pageRecordPP, $calendarRestrictions, true));

			// Set up error message if no recipients has been found, do not proceed with the rest of the function
			if ($tabinfo['record_count'] == 0) {
				$tempMessage = 'TriggerEmails_Stats_Recipients_Error_NoRecipientFound';

				if (!empty($calendarRestrictions)) {
					$tempMessage = 'TriggerEmails_Stats_Recipients_Error_NoRecipientFound_CalendarProblem';
				}

				$GLOBALS['Error'] = GetLang($tempMessage);
				$tabinfo['message'] = $this->ParseTemplate('ErrorMsg', true, false);
				unset($GLOBALS['Error']);

				return $tabinfo;
			}

			// ----- Get "Record Per Page"
				if ($requestAction == 'processpaging') {
					$pageRecordPP = IEM::requestGetGET('PerPageDisplaytriggerrecipients', 10, 'intval');
					if ($pageRecordPP == 0) {
						$pageRecordPP = 10;
					}
					$this->SetPerPage($pageRecordPP);
				}

				if ($pageRecordPP == 0) {
					$pageRecordPP = $this->GetPerPage();
				}
			// -----

			// ----- Get records from DB
				$tempStart = 0;
				if ($pageRecordPP != 'all') {
					$tempStart = ($pageCurrentIndex - 1) * $pageRecordPP;
				}

				$tabinfo['records'] = $api->GetRecipientList($record['triggeremailsid'], $tempStart, $pageRecordPP, $calendarRestrictions, false, GetLang('TimeFormat'));
			// -----

			// ----- Calculate pagination, this is still using the older method of pagination
				$GLOBALS['PAGE'] = 'Stats&Action=TriggerEmails&SubAction=View&id=' . $record['triggeremailsid'] . '&tab=7';
				$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=TriggerEmails&NextAction=View&id=' . $record['triggeremailsid'] . '&tab=7';
				$GLOBALS['PPDisplayName'] = 'triggerrecipients';

				$this->SetupPaging($tabinfo['record_count'], $pageCurrentIndex, $pageRecordPP);
				$tabinfo['pagination_top'] = $this->ParseTemplate('Paging', true);
				$tabinfo['pagination_bottom'] = $this->ParseTemplate('Paging_Bottom', true);

				unset($GLOBALS['PAGE']);
				unset($GLOBALS['FormAction']);
				unset($GLOBALS['PPDisplayName']);
			// -----

			return $tabinfo;
		}

		/**
		 * _TriggerEmailsStats_View_Tab8
		 * "Tab 8" of the trigger email statistics
		 *
		 * @param Array $record An associative array of the trigger email statistic record
		 * @return Array Returns tab information that is used by TriggerEmailsStats_View method
		 *
		 * @see Stats::TriggerEmailsStats_View()
		 */
		function _TriggerEmailsStats_View_Tab8($record)
		{
			// ----- Sanitize and declare variables that is going to be used in this function
				$user 					= IEM::userGetCurrent();

				$pageRecordPP			= 0;
				$pageCurrentIndex		= IEM::requestGetGET('DisplayPagetriggerfailed', 1, 'intval');
				$calendarRestrictions	= array_key_exists('recipients', $this->CalendarRestrictions) ? $this->CalendarRestrictions['recipients'] : '';

				$requestAction			= isset($_GET['Action'])? strtolower($_GET['Action']) : '';

				$api					= $this->GetApi('TriggerEmails');

				$tabinfo				= array();
			// ----

			$tabinfo['intro'] = sprintf(GetLang('TriggerEmails_Stats_Failed_Intro'), $record['triggeremailsname']);

			// setup calendar
			$GLOBALS['TabID'] = '7';
			$this->SetupCalendar('Action=ProcessCalendar&SubAction=TriggerEmails&NextAction=View&tab=8&id=' . $record['triggeremailsid']);
			$tabinfo['calendar'] = $GLOBALS['Calendar'];
			unset($GLOBALS['TabID']);
			unset($GLOBALS['Calendar']);

			$tabinfo['record_count'] = intval($api->GetFailedList($record['triggeremailsid'], $pageCurrentIndex, $pageRecordPP, $calendarRestrictions, true));

			// Set up error message if no recipients has been found, do not proceed with the rest of the function
			if ($tabinfo['record_count'] == 0) {
				$tempMessage = 'TriggerEmails_Stats_Failed_Error_NoRecipientFound';

				if (!empty($calendarRestrictions)) {
					$tempMessage = 'TriggerEmails_Stats_Failed_Error_NoRecipientFound_CalendarProblem';
				}

				$GLOBALS['Error'] = GetLang($tempMessage);
				$tabinfo['message'] = $this->ParseTemplate('ErrorMsg', true, false);
				unset($GLOBALS['Error']);

				return $tabinfo;
			}

			// ----- Get "Record Per Page"
				if ($requestAction == 'processpaging') {
					$pageRecordPP = IEM::requestGetGET('PerPageDisplaytriggerfailed', 10, 'intval');
					if ($pageRecordPP == 0) {
						$pageRecordPP = 10;
					}
					$this->SetPerPage($pageRecordPP);
				}

				if ($pageRecordPP == 0) {
					$pageRecordPP = $this->GetPerPage();
				}
			// -----

			// ----- Get records from DB
				$tempStart = 0;
				if ($pageRecordPP != 'all') {
					$tempStart = ($pageCurrentIndex - 1) * $pageRecordPP;
				}

				$tabinfo['records'] = $api->GetFailedList($record['triggeremailsid'], $tempStart, $pageRecordPP, $calendarRestrictions, false, GetLang('TimeFormat'));
			// -----

			// ----- Calculate pagination, this is still using the older method of pagination
				$GLOBALS['PAGE'] = 'Stats&Action=TriggerEmails&SubAction=View&id=' . $record['triggeremailsid'] . '&tab=8';
				$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=TriggerEmails&NextAction=View&id=' . $record['triggeremailsid'] . '&tab=8';
				$GLOBALS['PPDisplayName'] = 'triggerfailed';

				$this->SetupPaging($tabinfo['record_count'], $pageCurrentIndex, $pageRecordPP);
				$tabinfo['pagination_top'] = $this->ParseTemplate('Paging', true);
				$tabinfo['pagination_bottom'] = $this->ParseTemplate('Paging_Bottom', true);

				unset($GLOBALS['PAGE']);
				unset($GLOBALS['FormAction']);
				unset($GLOBALS['PPDisplayName']);
			// -----

			return $tabinfo;
		}

		/**
		 * _TriggerEmailsStats_Permission
		 * Check whether or not current user have the permission to access
		 *
		 * @param Integer $id ID of the trigger emails
		 * @return Boolean Returns TRUE if user is able to access record, FALSE otherwise
		 */
		function _TriggerEmailsStats_Access($id)
		{
			$id = intval($id);
			$user = IEM::userGetCurrent();

			if ($id == 0) {
				return false;
			}

			// Admin can access all
			if ($user->Admin()) {
				return true;
			}

			// Only admin user and the owner of the trigger can access them.
			$api = $this->GetApi('Triggeremails');
			$record = $api->GetRecordByID($id);
			return ($user->userid === $record['ownerid']);
		}
	// --------------------------------------------------------------------------------
}