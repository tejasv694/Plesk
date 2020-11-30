<?php
/**
 * This file handles processing the stats charts when viewing statistics.
 *
 * TODO: This file SHOULD NOT BE CALLED DIRECTLY from the request.
 * All request should be directed to index.php
 *
 * @version     $Id: remote_stats.php,v 1.32 2008-03-03 23:58:39 tye Exp $
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

/**
* Include the main init file and the base sendstudio functions file.
*/
require_once(dirname(__FILE__) . '/../index.php');
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for AJAX Statistics functions
*
* @package SendStudio
*/
class RemoteStats extends SendStudio_Functions
{
	/**
	* Type
	* The type of table that the stats are referring to.
	*
	* @var String
	*/
	var $type = null;

	/**
	* Sort
	* The sort direction (up/down).
	*
	* @var String
	*/
	var $sort = null;

	/**
	* Column
	* The column to sort the stats by.
	*
	* @var String
	*/
	var $column = null;

	/**
	* Columns
	* A list of valid types and column names.
	* The first column of each type is the default column to sort by.
	*
	* @var Array
	*/
	var $columns = array (
			'newsletter_opens' => array('opened' => 'opentime', 'email' => 'l.emailaddress', 'type' => 'opentype'),
			'newsletter_links' => array('clicked' => 'clicktime', 'email' => 'l.emailaddress', 'url' => 'url'),
			'newsletter_bounces' => array('time' => 'bouncetime', 'email' => 'l.emailaddress', 'type' => 'bouncetype', 'rule' => 'bouncerule'),
			'newsletter_unsubscribes' => array('time' => 'unsubscribetime', 'email' => 'l.emailaddress'),
			'forwards' => array('time' => 'forwardtime', 'email' => 'forwardedby', 'recipient' => 'forwardedto'),
			'triggeremails_opens' => array('opened' => 'opentime', 'email' => 'l.emailaddress', 'type' => 'opentype'),
			'triggeremails_links' => array('clicked' => 'clicktime', 'email' => 'l.emailaddress', 'url' => 'url'),
			'triggeremails_bounces' => array('time' => 'bouncetime', 'email' => 'l.emailaddress', 'type' => 'bouncetype', 'rule' => 'bouncerule'),
			'triggeremails_unsubscribes' => array('time' => 'unsubscribetime', 'email' => 'l.emailaddress'),
			'triggeremails_forwards' => array('time' => 'forwardtime', 'email' => 'forwardedby', 'recipient' => 'forwardedto')
	);

	/**
	* RemoteStats
	* Loads up the language file.
	*
	* @return Void Doesn't return anything.
	*/
	function RemoteStats()
	{
		if (!IEM::getCurrentUser()) {
			if (defined('SENDSTUDIO_APPLICATION_URL') && SENDSTUDIO_APPLICATION_URL !== false) {
				header('Location: ' . SENDSTUDIO_APPLICATION_URL . '/admin/index.php');
			} else {
				header('Location: ../index.php');
			}
			exit;
		}

		$this->LoadLanguageFile('Stats');
	}

	/**
	* Process
	* Executes functions for the page that was requested
	*
	* @return Void Returns nothing
	*/
	function Process()
	{
		$user = GetUser();

		$action = $this->_getGETRequest('Action', '');
		$statstype = $this->_getGETRequest('statstype', null);
		$subaction = $this->_getGETRequest('subaction', '');


		if (isset($_GET['PerPageDisplay'])) {
			$perpage = $this->SetPerPage($_GET['PerPageDisplay']);
		} else {
			$perpage = $this->GetPerPage();
		}

		$statsapi = $this->GetApi('Stats');

		switch ($action) {
			case 'get_linkstats':
				$linksjson = array();

				$token_id = IEM::requestGetGET('token', false);
				$request_link = IEM::requestGetGET('link', false);

				if (!$token_id || !$request_link) {
					die();
				}

				$token_data = IEM::sessionGet($token_id);
				$statid = $token_data['statid'];
				$calendar_restrictions = $token_data['calendar_restrictions'];
				$chosen_link = (is_numeric($request_link) ? $request_link : 'a');

				// Total Clicks
				$linkclicks = $statsapi->GetClicks($statid, 0, 0, $chosen_link, $calendar_restrictions, true);
				$linkclicks = intval($linkclicks);

				$linksjson['linkclicks'] = $linkclicks;

				// Average Clicks
				$averageclicks = 0;

				$open_count = $statsapi->GetOpens($statid, 0, 0, true, $calendar_restrictions, true);
				$open_count = intval($open_count);

				if ($open_count != 0) {
					$averageclicks = $linkclicks / $open_count;
				}

				$linksjson['averageclicks'] = $this->FormatNumber($averageclicks, 3);

				// Click-through rate
				if (isset($token_data['summary']['emails_sent'])) {
					$sent_to = $token_data['summary']['emails_sent'];
				} else {
					$summary = $statsapi->GetNewsletterSummary($statid, true, 0);
					if (!isset($summary['htmlrecipients'])) {
						$sent_to = 0;
					} else {
						$sent_to = $summary['htmlrecipients'] + $summary['textrecipients'] + $summary['multipartrecipients'];
					}
				}

				$clicks = $statsapi->GetUniqueClickRecipients($statid,$calendar_restrictions,$chosen_link);

				if ($sent_to == 0) {
					$linksjson['clickthrough'] = '0%';
				} else {
					$linksjson['clickthrough'] = $this->FormatNumber($clicks / $sent_to * 100,2) . '%';
				}

				// Unique Clicks
				$uniqueclicks = $clicks;
				$linksjson['uniqueclicks'] = $uniqueclicks;

				echo "var linksjson = " . GetJSON($linksjson) . ";";
			break;

			case 'print':
				if ($statstype != 'a' && $statstype != 'n' && $statstype != 'l' && $statstype != 't') {
					exit;
				}

				switch ($statstype) {
					case 'a':
						$this->area = 'autoresponder';
					break;
					case 'n':
						$this->area = 'newsletter';
					break;
					case 'l':
						$this->area = 'list';
					break;
					case 't':
						$this->area = 'triggeremails';
					break;
				}

				switch ($subaction) {
					case 'step2':
						require_once(dirname(__FILE__) . "/amcharts/amcharts.php");

						$options_details = array();
						if (isset($_GET['options_details']) && is_array($_GET['options_details'])) {
							$options_details = $_GET['options_details'];
						}

						if (isset($_GET['autoresponderid'])) {
							$autoresponderid = (int)$_GET['autoresponderid'];
						}

						if (!isset($_GET['Preview'])) {
							$GLOBALS['Body_Onload'] = 'window.focus();window.print();';
						}  else {
							$GLOBALS['Body_Onload'] = '';
						}

						header("Content-type: text/html; charset=" . SENDSTUDIO_DEFAULTCHARSET);

						$this->ParseTemplate('Stats_Print_Header');

						$calendar_restrictions = '';
						$statids = $statsapi->CheckIntVars($_GET['stats']);

						foreach ($statids as $index=>$statid) {

							if ($statstype == 'a') {
								// For autoresponders, $_GET['stats'] contains the autoresponderid
								$autoresponderid = $statid;
								$summary = $statsapi->GetAutoresponderSummary($autoresponderid, true, 0);
								$statid = $summary['statid'];
							}

							if ($statstype == 'n') {
								$summary = $statsapi->GetNewsletterSummary($statid, true, 0);
							}

							if ($statstype == 'l') {
								$summary = $statsapi->GetListSummary($statid);
								$listid = $statid;
								$statid = $summary['statids'];
								IEM::sessionSet('ListStatistics', $statid);
							}

							if ($statstype == 't') {
								$triggeremailsid = $this->_getGETRequest('triggermailsid', 0);

								if (isset($triggeremailsid[$index])) {
									$summary = $statsapi->GetTriggerEmailsStatsRecord($triggeremailsid[$index]);
								} else {
									$summary = array();
								}
							}

							$access = true;

							if (in_array($statstype, array('a', 'n'))) {
								$access = $this->CanAccessStats($statid, $statstype);
							} elseif ($statstype == 't') {
								// Admin access?
								$access = $user->Admin();

								// If this is NOT an admin, check whether or not he owns the trigger
								if (!$access && $this->IsOwner($summary['owneruserid'])) {
									$access = true;
								}
							} else {
								$access = $this->CanAccessList($listid);
							}

							if (!$access) {
								$this->DenyAccess();
								return;
							}


							foreach ($_GET['options'] as $option) {

								switch ($option) {
									case 'snapshot':
										switch ($statstype) {
											case 'l':
												$data = $statsapi->GetSubscriberGraphData($statsapi->stats_type, array('unconfirms' => array(),'confirms' => array(),'subscribes' => array(),'unsubscribes' => array(),'bounces' => array(),'forwards' => array()), $listid);
												IEM::sessionSet('SubscriberGraphData', $data);
												$areas = array('unconfirms', 'confirms', 'unsubscribes', 'bounces', 'forwards');
												$totals = array('unconfirms' => 0, 'confirms' => 0, 'unsubscribes' => 0, 'forwards' => 0, 'bounces' => 0);
												$now = getdate();
												$today = $now['0'];
												$date = $today;
												$time_display = '';

												for ($i = 1; $i <= 12; $i++) {
													$found_stats = false;
													foreach ($areas as $k => $area) {
														$GLOBALS[$area] = 0;
														foreach ($data[$area] as $p => $details) {
															if ($details['mth'] != $i) {
																continue;
															}

															$GLOBALS['Name'] = GetLang($this->Months[$i]) . ' ' . $details['yr'];

															$GLOBALS[$area] = $this->FormatNumber($details['count']);
															$totals[$area] += $details['count'];
															$found_stats = true;
														}
													}

													if (!$found_stats) {
														continue;
													}

													$time_display .= $this->ParseTemplate('Stats_List_Step3_Row', true, false);
												}

												foreach ($areas as $k => $area) {
													$GLOBALS['Total_' . $area] = $this->FormatNumber($totals[$area]);
													//$GLOBALS['Total_domain_' . $area] = $this->FormatNumber($domain_totals[$area]);
												}

												$data_url = SENDSTUDIO_APPLICATION_URL . '/admin/functions/stats_chart.php?Area=list&list='.$listid .'&graph=subscribersummary&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

												$this->InsertChartImage('SummaryChart', $data_url, array('graph_title' => GetLang("List_Summary_Graph_subscribersummary")));

												$this->ParseTemplate('Stats_Summary_List');
											break; // case l

											case 'n':
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

												$GLOBALS['NewsletterSubject'] = $summary['newslettersubject'];

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
													$GLOBALS['ClickThroughRate'] = $this->FormatNumber((int)$clicks / (int)$sent_to * 100,2) . '%';
												}

												$total_bounces = $summary['bouncecount_unknown'] + $summary['bouncecount_hard'] + $summary['bouncecount_soft'];

												$GLOBALS['TotalBounces'] = $this->FormatNumber($total_bounces);
												$unopened = $sent_size - $summary['emailopens_unique'] - $total_bounces;
												$data_url = SENDSTUDIO_APPLICATION_URL . '/admin/stats_chart.php?Opens='.$summary['emailopens_unique'].'&Unopened='.$unopened.'&Bounced='.$total_bounces.'&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

												// Newsletter Summary Chart

												$this->InsertChartImage('SummaryChart',$data_url,array('graph_title' => GetLang("NewsletterSummaryChart")));

												// finally put it all together.
												$this->ParseTemplate('Stats_Summary_Newsletter');
											break; // case 'n'

											case 'a':
												$this->LoadLanguageFile('Autoresponders');

												$GLOBALS['AutoresponderID'] = $autoresponderid;

												$GLOBALS['SummaryIntro'] = sprintf(GetLang('AutoresponderStatistics_Snapshot_Summary'), htmlspecialchars($summary['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET));

												$GLOBALS['AutoresponderSubject'] = htmlspecialchars($summary['autorespondersubject'], ENT_QUOTES, SENDSTUDIO_CHARSET);

												$GLOBALS['UserEmail'] = $summary['emailaddress'];
												$created_by = $summary['username'];
												if ($summary['fullname']) {
													$created_by = $summary['fullname'];
												}
												$GLOBALS['CreatedBy'] = $created_by;

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

												$unopened = $total_sent - $summary['emailopens_unique'] - $total_bounces;
												if ($unopened < 0) {
													$unopened = 0;
												}

												$data_url = SENDSTUDIO_APPLICATION_URL . '/admin/stats_chart.php?Opens='.$summary['emailopens_unique'].'&Unopened='.$unopened.'&Bounced='.$total_bounces.'&Area=autoresponder&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

												$this->InsertChartImage('SummaryChart',$data_url);


												$this->ParseTemplate('Stats_Summary_Autoresponder');
											break; // case 'a'

											case 't':
												$this->LoadLanguageFile('TriggerEmails');

												$summary['processed_totalbounced'] = intval($summary['bouncecount_soft']) + intval($summary['bouncecount_hard']) + intval($summary['bouncecount_unknown']);

												$info = array();
												$info['total_open'] = sprintf(GetLang('EmailOpens_Total'), $this->FormatNumber($summary['emailopens']));
												$info['unique_open'] = sprintf(GetLang('EmailOpens_Unique'), $this->FormatNumber($summary['emailopens_unique']));
												$info['total_bounce'] = $this->FormatNumber($summary['processed_totalbounced']);

												$template = GetTemplateSystem();
												$template->assign('record', $summary);
												$template->assign('info', $info);
												$template->ParseTemplate('Stats_Summary_TriggerEmails');
											break; // case 't'
										} // switch ($statstype)
									break; //snapshot

									case 'perdomain':
										$domain_data = $statsapi->GetSubscriberDomainGraphData(array('unconfirms' => array(),'confirms' => array(),'subscribes' => array(),'unsubscribes' => array(),'bounces' => array(),'forwards' => array()), $listid);
										$domain_totals = array('unconfirms' => 0, 'confirms' => 0, 'unsubscribes' => 0, 'forwards' => 0, 'bounces' => 0);
										$areas = array('unconfirms', 'confirms', 'unsubscribes', 'bounces', 'forwards');

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


										$this->DisplayChart('DomainChart', 'subscriberdomains', '0','pie',array(
											'hide_labels_percent' => 2,
											'group_percent' => 2,
											'x_position' => '',
											'radius' => 85,
											'graph_title' => GetLang("ListStatistics_Snapshot_PerDomain")
										));

										$this->ParseTemplate('Stats_Summary_Perdomain');

										if (in_array($option,$options_details)) {
											foreach ($areas as $k => $area) {
												$GLOBALS['Total_domain_' . $area] = $this->FormatNumber($domain_totals[$area]);
											}

											$GLOBALS['DisplayDomainList'] = $domain_display;

											$this->ParseTemplate('Stats_List_Step3_Domains_Table');
										}
									break; // case perdomain

									case 'open':
										if ($statstype == 'l') {
											$total_emails = $summary['emails_sent'];
										} else {
											$total_emails = $summary['htmlrecipients'] + $summary['textrecipients'] + $summary['multipartrecipients'];
										}

										$GLOBALS['TotalEmails'] = $this->FormatNumber($total_emails);
										$GLOBALS['TotalOpens'] = $this->FormatNumber($summary['emailopens']);
										$GLOBALS['TotalUniqueOpens'] = $this->FormatNumber($summary['emailopens_unique']);

										$most_opens = $statsapi->GetMostOpens($statid, $calendar_restrictions);

										$now = getdate();

										if (isset($most_opens['mth'])) {
											$GLOBALS['MostOpens'] = $this->Months[$most_opens['mth']] . ' ' . $most_opens['yr'];
										}

										if (isset($most_opens['hr'])) {
											$GLOBALS['MostOpens'] = $this->PrintDate(mktime($most_opens['hr'], 1, 1, 1, 1, $now['year']), GetLang('Daily_Time_Display'));
										}

										if (isset($most_opens['dow'])) {
											$pos = array_search($most_opens['dow'], array_keys($this->days_of_week));
											// we need to add 1 hour here otherwise we get the wrong day from strtotime.
											$GLOBALS['MostOpens'] = $this->PrintDate(strtotime("last " . $this->days_of_week[$pos] . " +1 hour"), GetLang('Date_Display_Display'));
										}

										if (isset($most_opens['dom'])) {
											$month = $now['mon'];
											// if the day-of-month is after "today", it's going to be for "last month" so adjust the month accordingly.
											if ($most_opens['dom'] > $now['mday']) {
												$month = $now['mon'] - 1;
											}
											$GLOBALS['MostOpens'] = $this->PrintDate(mktime(0, 0, 1, $month, $most_opens['dom'], $now['year']), GetLang('Date_Display_Display'));
										}

										$avg_opens = 0;
										if ($total_emails > 0) {
											$avg_opens = $summary['emailopens'] / $total_emails;
										}
										$GLOBALS['AverageOpens'] = $this->FormatNumber($avg_opens, 1);

										if ($total_emails != 0) {
											$GLOBALS['OpenRate'] = $this->FormatNumber($summary['emailopens_unique'] / $total_emails * 100,2) . "%" ;
										} else {
											$GLOBALS['OpenRate'] = '0%';
										}

										if ($summary['emailopens'] > 0) {
											$this->DisplayChart('OpenChart', $this->area, $statid,'column',array('graph_title' => GetLang("OpensChart")));
										}

										$this->ParseTemplate('Stats_Summary_Newsletter_Opens');

										if (in_array($option,$options_details)) {
											$token = "stats" . md5(uniqid('_'));

											IEM::sessionSet($token,array(
												'statid' => $statid, 'unique_only' => false, 'calendar_restrictions' => $calendar_restrictions,
												'summary' => $summary
											));

											echo '<div id="'.$option . '_details"></div>';
											echo '<script>
												$.ajax({
													type: "get",
													url: "remote_stats.php",
													data: "type=newsletter_opens&pagination=false&token='.$token.'&sort=down",
													success: function (html) {
														$("#'.$option.'_details").html(html);
													}
												});
												</script>';
										}
									break; // opens

									case 'click':
										if (isset($summary['starttime'])) {
											$sent_when = $this->PrintTime($summary['starttime'], true);
										}

										$GLOBALS['StatID'] = (int)$statid;

										$GLOBALS['LinkAction'] = 'Newsletter';

										if (!isset($chosen_link) || !is_numeric($chosen_link)) {
											$chosen_link = 'a';
										}

										$summary['linkclicks'] = $statsapi->GetClicks($statid, 0, 0, $chosen_link, $calendar_restrictions, true);

										// build up the summary table.
										$GLOBALS['TotalClicks'] = $this->FormatNumber($summary['linkclicks']);

										$unique_clicks_count = $statsapi->GetUniqueClicks($statid, $chosen_link, $calendar_restrictions);
										$GLOBALS['TotalUniqueClicks'] = $this->FormatNumber($unique_clicks_count);

										$most_popular_link = $statsapi->GetMostPopularLink($statid, $chosen_link, $calendar_restrictions);

										$GLOBALS['MostPopularLink'] = htmlspecialchars($most_popular_link, ENT_QUOTES, SENDSTUDIO_CHARSET);
										$GLOBALS['MostPopularLink_Short'] = $most_popular_link;

										$averageclicks = 0;
										if (isset($summary['emailopens']) && (int)$summary['emailopens'] > 0) {
											$open_count = (int)$summary['emailopens'];
											$averageclicks = $summary['linkclicks'] / $open_count;
										}
										$GLOBALS['AverageClicks'] = $this->FormatNumber($averageclicks, 1);

										if ($summary['linkclicks'] > 0) {
											$this->DisplayChart('LinksChart', $this->area, $statid,'column',array('graph_title' => GetLang("LinksClickedChart")));
										}

										$this->ParseTemplate('Stats_Summary_Newsletter_Links');

										$token = "stats" . md5(uniqid('_'));

										IEM::sessionSet($token,array(
											'statid' => $statid, 'chosen_link' => 'a', 'calendar_restrictions' => $calendar_restrictions,
											'summary' => $summary
										));

										if (in_array($option,$options_details)) {
											echo '<div id="'.$option . '_details"></div>';
											echo '<script>
												$.ajax({
													type: "get",
													url: "remote_stats.php",
													data: "type=newsletter_links&pagination=false&token='.$token.'&sort=down",
													success: function (html) {
														$("#'.$option.'_details").html(html);
													}
												});
											</script>';
										}

										echo "
											<script>
												$.get('remote_stats.php?Action=get_linkstats&link=a&token={$token}','',function (data) {
													eval(data);
													$('#clickthrough').html(linksjson.clickthrough);
												});
											</script>";

									break; // click

									case 'bounce':
										$chosen_bounce_type = 'a';
										$total_bounces = $statsapi->GetBounces($statid, 0, 10, $chosen_bounce_type, $calendar_restrictions, true);

										$bounce_types_count = $statsapi->GetBounceCounts($statid, $calendar_restrictions);
										$GLOBALS['TotalBounceCount'] = $this->FormatNumber($bounce_types_count['total']);
										$GLOBALS['TotalSoftBounceCount'] = $this->FormatNumber($bounce_types_count['soft']);
										$GLOBALS['TotalHardBounceCount'] = $this->FormatNumber($bounce_types_count['hard']);
										if ($bounce_types_count['total'] > 0) {
											$this->DisplayChart('BounceChart', $this->area, $statid,'column');
										}

										$this->ParseTemplate('stats_summary_newsletter_bounces');

										if (in_array($option,$options_details)) {
											$token = "stats" . md5(uniqid('_'));
											IEM::sessionSet($token,array(
												'statid' => $statid, 'chosen_bounce_type' => false, 'calendar_restrictions' => $calendar_restrictions,
												'summary' => $summary
											));

											echo '<div id="'.$option . '_details"></div>';
											echo '<script>
												$.ajax({
													type: "get",
													url: "remote_stats.php",
													data: "type=newsletter_bounces&pagination=false&token='.$token.'&sort=down",
													success: function (html) {
														$("#'.$option.'_details").html(html);
													}
												});
												</script>';
										}

									break; // bounce

									case 'unsubscribe':
										if ($summary['unsubscribecount'] > 0) {
											$unsubscribes = $statsapi->GetUnsubscribes($statid, 0, 10, $calendar_restrictions);
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

										if ($summary['unsubscribecount'] > 0) {
											$this->DisplayChart('UnsubscribeChart', $this->area, $statid, 'column',array('graph_title' => GetLang("UnsubscribesChart")));
										}

										$this->ParseTemplate('stats_summary_newsletter_unsubscribes');

										if (in_array($option,$options_details)) {
											$token = "stats" . md5(uniqid('_'));

											if ($statstype == 't') {
												IEM::sessionSet($token,array(
													'statid' => $statid, 'calendar_restrictions' => array('unsubscribes' => $calendar_restrictions),
													'summary' => $summary
												));
											} else {
												IEM::sessionSet($token,array(
													'statid' => $statid, 'calendar_restrictions' => $calendar_restrictions,
													'summary' => $summary
												));
											}

											$url_data_statstype = 'newsletter_unsubscribes';
											if ($statstype == 't') {
												$url_data_statstype = 'triggeremails_unsubscribes';
											}

											echo '<div id="'.$option . '_details"></div>';
											echo '<script>
												$.ajax({
													type: "get",
													url: "remote_stats.php",
													data: "type=' . $url_data_statstype . '&pagination=false&token='.$token.'&sort=down",
													success: function (html) {
														$("#'.$option.'_details").html(html);
													}
												});
												</script>';
										}

									break; //unsubscribe

									case 'forward':
										if ($summary['emailforwards'] > 0) {
											$forwards = $statsapi->GetForwards($statid, 0, 0, $calendar_restrictions);
										}

										if ($calendar_restrictions != '') {
											$summary['emailforwards'] = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions, true);
										}

										$GLOBALS['TotalForwards'] = $this->FormatNumber($summary['emailforwards']);

										$new_signups = $statsapi->GetForwards($statid, 0, 0, $calendar_restrictions, true, true);

										$GLOBALS['TotalForwardSignups'] = $this->FormatNumber($new_signups);

										if ($summary['emailforwards'] > 0) {
											$this->DisplayChart('ForwardsChart', $this->area, $statid,'column',array('graph_title' => GetLang("ForwardsChart")));
										}

										$this->ParseTemplate('Stats_Summary_Newsletter_Forwards');

										if (in_array($option,$options_details)) {
											$token = "stats" . md5(uniqid('_'));

											if ($statstype == 't') {
												IEM::sessionSet($token,array(
													'statid' => $statid, 'calendar_restrictions' => array('forwards' => $calendar_restrictions),
													'summary' => $summary
												));
											} else {
												IEM::sessionSet($token,array(
													'statid' => $statid, 'calendar_restrictions' => $calendar_restrictions,
													'summary' => $summary
												));
											}

											$url_data_statstype = 'forwards';
											if ($statstype == 't') {
												$url_data_statstype = 'triggeremails_forwards';
											}

											echo '<div id="'.$option . '_details"></div>';
											echo '<script>
												$.ajax({
													type: "get",
													url: "remote_stats.php",
													data: "type=' . $url_data_statstype . '&pagination=false&token='.$token.'&sort=down",
													success: function (html) {
														$("#'.$option.'_details").html(html);
													}
												});
											</script>';
										}
									break; //forward

									default:
								} // switch $option
							} // foreach $options
						} // foreach $stats
						$this->ParseTemplate('Stats_Print_Footer');

					break; // step2

					default: // step1

						$print_options = '';
						$bg_color = 'white';

						if ($_GET['statstype']) {
							$print_options .= '<input type="hidden" name="statstype" value="' . htmlentities($statstype, ENT_QUOTES, SENDSTUDIO_CHARSET) . '" />';
						}

						switch ($statstype) {

							default:
								$itemids = $statsapi->CheckIntVars($_GET['stats']);

								if (isset($_GET['stats'])) {
									foreach ($_GET['stats'] as $val) {
										$print_options .= '<input type="hidden" name="stats[]" value="' . $val . '" />';
									}
								}

								if (isset($_GET['autoresponderid'])) {
									foreach ($_GET['autoresponderid'] as $val) {
										$print_options .= '<input type="hidden" name="autoresponderid[]" value="' . $val . '" />';
									}
								}

								if (isset($_GET['triggerid'])) {
									$triggeremailid = $_GET['triggerid'];
									if (!is_array($triggeremailid)) {
										$triggeremailid = array($triggeremailid);
									}

									foreach ($triggeremailid as $id) {
										$print_options .= '<input type="hidden" name="triggermailsid[]" value="' . intval($id) . '" />';
									}
								}

								if ($statstype == 'l') {
									$a = array (
										'snapshot' => GetLang('ListStatistics_Snapshot'),
										'perdomain' => GetLang('ListStatistics_Snapshot_PerDomain'),
										'open' => GetLang('Opens_Summary'),
										'click' => GetLang('LinkClicks_Summary'),
										'bounce' => GetLang('Bounce_Summary'),
										'unsubscribe' => GetLang('Unsubscribe_Summary'),
										'forward' => GetLang('Forwards_Summary')
									);
								} else {
									$a = array (
										'snapshot' => GetLang('NewsletterStatistics_Snapshot'),
										'open' => GetLang('Opens_Summary'),
										'click' => GetLang('LinkClicks_Summary'),
										'bounce' => GetLang('Bounce_Summary'),
										'unsubscribe' => GetLang('Unsubscribe_Summary'),
										'forward' => GetLang('Forwards_Summary')
									);
								}

								foreach ($a as $key => $val) {
									$bg_color = ($bg_color == 'white') ? '#EDECEC' : 'white';
									$print_options .= '<div style="background: ' . $bg_color . '; padding: 5px; margin-bottom: 5px;">';
									$print_options .= '<input id="print_'.$key.'" type="checkbox" name="options[]" value="'.$key.'" checked="checked" style="margin:0;"/>
										<label for="print_' . $key . '">'.$val.'</label><br />' . "\n";

									if ($key != 'snapshot') {
										$count = 0;
										$function = 'Get' . ucfirst($key) . 's';

										$real_statids = array();
										if ($statstype == 'l') {
											// For lists, $itemids is actually the listids, so we have to get the statids for the lists
											foreach ($itemids as $listid) {
												if (!$this->CanAccessList($listid)) {
													$this->DenyAccess();
													return;
												}
												$summary = $statsapi->GetListSummary($listid);
												$real_statids = array_merge($real_statids, $summary['statids']);
											}
										}

										if ($statstype == 'a') {
											// For autoresponders, $itemids is actually the autoresponderids, so we have to get the statids for the autoresponders
											foreach ($itemids as $arid) {
												$summary = $statsapi->GetAutoresponderSummary($arid, true, 0);
												if (!$this->CanAccessStats($summary['statid'], 'a')) {
													$this->DenyAccess();
													return;
												}
												$real_statids[] = $summary['statid'];
											}
										}

										switch ($key) {
											case 'perdomain':
												$count = $statsapi->GetSubscriberDomainCount($itemids[0]);
											break;

											case 'bounce':
											case 'open':
												$count = $statsapi->$function( ($real_statids === array() ? $itemids : $real_statids), 0, 0, false, '', true);
											break;

											case 'click':
												$count = $statsapi->$function(($real_statids === array() ? $itemids : $real_statids), 0, 0, 'a', '', true);
											break;

											default:
												$count = $statsapi->$function(($real_statids === array() ? $itemids : $real_statids), 0, 0, '', true);
										}

										$print_options .= '<span style="width: 20px;"><img src="images/blank.gif" width="20" height="1" /></span>';
										$print_options .= '<input id="print_details_'.$key.'" type="checkbox" name="options_details[]" value="'.$key.'" style="margin:0;"';
										if ($count == 0) {
											$print_options .= ' disabled="disabled"';
										}
										$print_options .= ' />
											<label for="print_details_' . $key . '">' .
											sprintf(GetLang('Stats_Print_IncludeDetailsOf'),$count,GetLang("Stats_Print_$key")) .
											'</label><br />' . "\n";
									}

									$print_options .= '</div>';
								}
							break;
						}

						$GLOBALS['PrintOptions'] = $print_options;

						$this->ParseTemplate('stats_print_step1');
						break;
					} // switch subaction
			break; //print

			default:
				$token_data = IEM::sessionGet($_GET['token']);
				$statid = $token_data['statid'];
				$calendar_restrictions = $token_data['calendar_restrictions'];

				$GLOBALS['TableToken'] = $_GET['token']; $GLOBALS['Token'] = $_GET['token'];

				if (isset($_GET['pagination']) && ($_GET['pagination'] == 'false')) {
					$perpage = 'all';
				}

				$DisplayPage = (isset($_GET['DisplayPage'])) ? (int)$_GET['DisplayPage'] : 1;
				$GLOBALS['CurrentPage'] = (int)$DisplayPage;
				$start = 0;
				if ($perpage != 'all') {
					$start = ($DisplayPage - 1) * $perpage;
				}

				$summary = &$token_data['summary'];

				if (isset($_GET['sort'])) {
					switch ($_GET['sort']) {
						case 'up':
						case 'down':
							$GLOBALS['SortDirection'] = $_GET['sort'];
						break;

						default:
							$GLOBALS['SortDirection'] = 'up';
					}
					$this->sort = ($GLOBALS['SortDirection'] == 'up' ? 'ASC' : 'DESC');
				} else {
					$this->sort = 'ASC';
					$GLOBALS['SortDirection'] = 'up';
				}

				if (in_array($_GET['type'], array_keys($this->columns))) {
					$GLOBALS['TableType'] = $_GET['type'];
					$this->type = $_GET['type'];

					if (isset($_GET['column']) && in_array($_GET['column'], array_keys($this->columns[$this->type]))) {
						$GLOBALS['SortColumn'] = $_GET['column'];
						$this->column = $this->columns[$this->type][$_GET['column']];
					} else {
						$q = array_keys($this->columns[$this->type]);
						$GLOBALS['SortColumn'] = $q[0];
						$this->column = $this->columns[$this->type][$q[0]];
					}
				} else {
					echo "Invalid parameters";
					exit;
				}


				// Tables:
				header("Content-type: text/html; charset=" . SENDSTUDIO_DEFAULTCHARSET);
				switch ($this->type) {
					case 'newsletter_opens':
						$opens = array();

						$opencount = $statsapi->GetOpens($token_data['statid'], 0, 0, $token_data['unique_only'], $token_data['calendar_restrictions'], true);

						// make sure unique opens are > 0 - if they aren't, something isn't tracking right anyway so no point trying anything else.
						if ($summary['emailopens_unique'] > 0) {
							if (isset($token_data['listid'])) {
								$opens = $statsapi->GetOpens($statid, $start, $perpage, $token_data['unique_only'], $token_data['calendar_restrictions'],false,$this->column,$this->sort,$token_data['listid']);
							} else {
								$opens = $statsapi->GetOpens($statid, $start, $perpage, $token_data['unique_only'], $token_data['calendar_restrictions'],false,$this->column,$this->sort);
							}
						}

						$GLOBALS['CurrentPage'] = (int)$DisplayPage;
						$this->_SetupPaging($opencount, $DisplayPage, $perpage, '', 'newsletter_opens', $_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$open_list = '';
						foreach ($opens as $k => $opendetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($opendetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['DateOpened'] = $this->PrintTime($opendetails['opentime'], true);
							$GLOBALS['OpenedEmailAsType'] = GetLang('OpenedEmailAs_Unknown');

							switch (strtolower($opendetails['opentype'])) {
								case 'h':
									$GLOBALS['OpenedEmailAsType'] = GetLang('OpenedEmailAs_HTML');
								break;

								case 't':
									$GLOBALS['OpenedEmailAsType'] = GetLang('OpenedEmailAs_Text');
								break;
							}

							$open_list .= $this->ParseTemplate('Stats_Step3_Opens_Row', true, false);
						}

						$GLOBALS['Stats_Step3_Opens_List'] = $open_list;
						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Opens_Table', true, false);
					break; //newsletter_opens

					case 'newsletter_links':
						$chosen_link = $token_data['chosen_link'];
						if (isset($_GET['link']) && is_numeric($_GET['link'])) {
							$chosen_link = (int)$_GET['link'];
						} else {
							$chosen_link = 'a';
						}

						$links = array();
						if ($summary['linkclicks'] > 0) {
							$links = $statsapi->GetClicks($statid, $start, $perpage, $chosen_link, $calendar_restrictions,false,$this->column,$this->sort);
						}

						$all_links = $statsapi->GetUniqueLinks($statid);

						if (empty($all_links)) {
							$GLOBALS['DisplayStatsLinkList'] = 'none';
						} else {
							$GLOBALS['DisplayStatsLinkList'] = 'block';
							$all_links_list = '';

							foreach ($all_links as $p => $linkinfo) {
								$selected = '';
								if ($linkinfo['linkid'] == $chosen_link) {
									$selected = ' SELECTED';
								}

								$all_links_list .= '<option value="' . $linkinfo['linkid'] . '"' . $selected . '>' . str_replace(array("'", '"'), "", $linkinfo['url']) . '</option>';
							}

							$GLOBALS['StatsLinkList'] = $all_links_list;
							$GLOBALS['CurrentPage'] = (int)$GLOBALS['CurrentPage'];
							$GLOBALS['StatsLinkDropDown'] = $this->ParseTemplate('Stats_Step3_Links_List', true, false);
						}

						$GLOBALS['CurrentPage'] = (int)$DisplayPage;
						$total_links = $statsapi->GetClicks($statid, $start, $perpage, $chosen_link, $calendar_restrictions,true);
						$this->_SetupPaging($total_links, $DisplayPage, $perpage,'','newsletter_links',$_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$click_list = '';
						foreach ($links as $k => $clickdetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($clickdetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['DateClicked'] = $this->PrintTime($clickdetails['clicktime'], true);

							$GLOBALS['FullURL'] = $url = str_replace(array('"', "'"), "", $clickdetails['url']);

							$GLOBALS['LinkClicked'] = $this->TruncateInMiddle($url);

							$click_list .= $this->ParseTemplate('Stats_Step3_Links_Row', true, false);
						}

						$GLOBALS['Stats_Step3_Links_List'] = $click_list;
						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = ''; $GLOBALS['StatsLinkDropDown'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Links_Table');
					break; // newsletter_links

					case 'newsletter_bounces':
						$chosen_bounce_type = $token_data['chosen_bounce_type'];

						if (isset($_GET['bouncetype']) && in_array($_GET['bouncetype'],array('any','soft','hard'))) {
							$chosen_bounce_type = $_GET['bouncetype'];
						} else {
							$chosen_bounce_type = 'any';
						}

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

						$bounces = array();

						$total_bounces = $statsapi->GetBounces($statid, $start, $perpage, $chosen_bounce_type, $calendar_restrictions, true);

						if ($total_bounces > 0) {
							$bounces = $statsapi->GetBounces($statid, $start, $perpage, $chosen_bounce_type, $calendar_restrictions,false,$this->column,$this->sort);
						}

						$this->_SetupPaging($total_bounces, $DisplayPage, $perpage,'','newsletter_bounces',$_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$bounce_list = '';
						foreach ($bounces as $k => $bouncedetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($bouncedetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['BounceDate'] = $this->PrintTime($bouncedetails['bouncetime'], true);
							$GLOBALS['BounceType'] = GetLang('Bounce_Type_' . $bouncedetails['bouncetype']);
							$GLOBALS['BounceRule'] = GetLang('Bounce_Rule_' . $bouncedetails['bouncerule']);
							$bounce_list .= $this->ParseTemplate('Stats_Step3_Bounces_Row', true, false);
						}
						$GLOBALS['Stats_Step3_Bounces_List'] = $bounce_list;

						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = ''; $GLOBALS['StatsBounceList'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Bounces_Table');

					break; // newsletter_bounces

					case 'newsletter_unsubscribes':
						$unsubscribes = array();

						$listid = 0;
						$token_request = IEM::requestGetGET('token', '');
						$token = IEM::sessionGet($token_request);
						if ($token !== false && isset($token['listid'])) {
							$listid = $token['listid'];
						}

						if ($summary['unsubscribecount'] > 0) {
							$unsubscribes = $statsapi->GetUnsubscribes($statid, $start, $perpage, $calendar_restrictions,false,$this->column,$this->sort, $listid);
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

						$total_unsubscribes = $statsapi->GetUnsubscribes($statid, $start, $perpage, $calendar_restrictions,true, '', '', $listid);

						$this->_SetupPaging($total_unsubscribes, $DisplayPage, $perpage,'','newsletter_unsubscribes',$_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$unsub_list = '';
						foreach ($unsubscribes as $k => $unsubdetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($unsubdetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['UnsubscribeTime'] = $this->PrintTime($unsubdetails['unsubscribetime'], true);
							$unsub_list .= $this->ParseTemplate('Stats_Step3_Unsubscribes_Row', true, false);
						}

						$GLOBALS['Stats_Step3_Unsubscribes_List'] = $unsub_list;

						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Unsubscribes_Table');
					break; // newsletter_unsubscribes

					case 'forwards':
						$forwards = array();

						if ($summary['emailforwards'] > 0) {
							$forwards = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions,false,false,$this->column,$this->sort);
						}
						$total_forwards = $statsapi->GetForwards($statid, $start, $perpage, $calendar_restrictions,true);
						$this->_SetupPaging($total_forwards, $DisplayPage, $perpage,'','forwards',$_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

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

						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Forwards_Table');
					break; // forwards

					case 'triggeremails_opens':
						$opens = array();

						$opencount = $statsapi->GetOpens($statid, 0, 0, $token_data['unique_open_only'], $token_data['calendar_restrictions']['opens'], true);

						if ($opencount > 0) {
							if (isset($token_data['listid'])) {
								$opens = $statsapi->GetOpens($statid, $start, $perpage, $token_data['unique_open_only'], $token_data['calendar_restrictions']['opens'], false, $this->column, $this->sort, $token_data['listid']);
							} else {
								$opens = $statsapi->GetOpens($statid, $start, $perpage, $token_data['unique_open_only'], $token_data['calendar_restrictions']['opens'], false, $this->column, $this->sort);
							}
						}

						$GLOBALS['CurrentPage'] = (int)$DisplayPage;
						$this->_SetupPaging($opencount, $DisplayPage, $perpage, '', 'triggeremails_opens', $_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$open_list = '';
						foreach ($opens as $k => $opendetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($opendetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['DateOpened'] = $this->PrintTime($opendetails['opentime'], true);
							$GLOBALS['OpenedEmailAsType'] = GetLang('OpenedEmailAs_Unknown');

							switch (strtolower($opendetails['opentype'])) {
								case 'h':
									$GLOBALS['OpenedEmailAsType'] = GetLang('OpenedEmailAs_HTML');
								break;

								case 't':
									$GLOBALS['OpenedEmailAsType'] = GetLang('OpenedEmailAs_Text');
								break;
							}

							$open_list .= $this->ParseTemplate('Stats_Step3_Opens_Row', true, false);
						}

						$GLOBALS['Stats_Step3_Opens_List'] = $open_list;
						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Opens_Table', true, false);
					break; //triggeremails_opens

					case 'triggeremails_links':
						$chosen_link = isset($token_data['chosen_link'])? $token_data['chosen_link'] : 'a';
						if (isset($_GET['link']) && is_numeric($_GET['link'])) {
							$chosen_link = (int)$_GET['link'];
						} else {
							$chosen_link = 'a';
						}

						$links = array();
						if ($summary['linkclicks'] > 0) {
							$links = $statsapi->GetClicks($statid, $start, $perpage, $chosen_link, $token_data['calendar_restrictions']['clicks'], false, $this->column, $this->sort);
						}

						$all_links = $statsapi->GetUniqueLinks($statid);

						if (empty($all_links)) {
							$GLOBALS['DisplayStatsLinkList'] = 'none';
						} else {
							$GLOBALS['DisplayStatsLinkList'] = 'block';
							$all_links_list = '';

							foreach ($all_links as $p => $linkinfo) {
								$selected = '';
								if ($linkinfo['linkid'] == $chosen_link) {
									$selected = ' SELECTED';
								}

								$all_links_list .= '<option value="' . $linkinfo['linkid'] . '"' . $selected . '>' . str_replace(array("'", '"'), "", $linkinfo['url']) . '</option>';
							}

							$GLOBALS['StatsLinkList'] = $all_links_list;
							$GLOBALS['CurrentPage'] = (int)$GLOBALS['CurrentPage'];
							$GLOBALS['StatsLinkDropDown'] = $this->ParseTemplate('Stats_Step3_Links_List', true, false);
						}

						$GLOBALS['CurrentPage'] = (int)$DisplayPage;
						$total_links = $statsapi->GetClicks($statid, $start, $perpage, $chosen_link, $token_data['calendar_restrictions']['clicks'], true);
						$this->_SetupPaging($total_links, $DisplayPage, $perpage, '', 'triggeremails_links', $_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$click_list = '';
						foreach ($links as $k => $clickdetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($clickdetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['DateClicked'] = $this->PrintTime($clickdetails['clicktime'], true);

							$GLOBALS['FullURL'] = $url = str_replace(array('"', "'"), "", $clickdetails['url']);

							$GLOBALS['LinkClicked'] = $this->TruncateInMiddle($url);

							$click_list .= $this->ParseTemplate('Stats_Step3_Links_Row', true, false);
						}

						$GLOBALS['Stats_Step3_Links_List'] = $click_list;
						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = '';
							$GLOBALS['StatsLinkDropDown'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Links_Table');
					break; // triggeremails_links

					case 'triggeremails_bounces':
						$chosen_bounce_type = isset($token_data['chosen_bounce_type'])? $token_data['chosen_bounce_type'] : 'any';

						if (isset($_GET['bouncetype']) && in_array($_GET['bouncetype'], array('any', 'soft', 'hard'))) {
							$chosen_bounce_type = $_GET['bouncetype'];
						} else {
							$chosen_bounce_type = 'any';
						}

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

						$bounces = array();

						$total_bounces = $statsapi->GetBounces($statid, $start, $perpage, $chosen_bounce_type, $token_data['calendar_restrictions']['bounces'], true);

						if ($total_bounces > 0) {
							$bounces = $statsapi->GetBounces($statid, $start, $perpage, $chosen_bounce_type, $token_data['calendar_restrictions']['bounces'], false, $this->column,$this->sort);
						}

						$this->_SetupPaging($total_bounces, $DisplayPage, $perpage, '', 'triggeremails_bounces', $_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$bounce_list = '';
						foreach ($bounces as $k => $bouncedetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($bouncedetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['BounceDate'] = $this->PrintTime($bouncedetails['bouncetime'], true);
							$GLOBALS['BounceType'] = GetLang('Bounce_Type_' . $bouncedetails['bouncetype']);
							$GLOBALS['BounceRule'] = GetLang('Bounce_Rule_' . $bouncedetails['bouncerule']);
							$bounce_list .= $this->ParseTemplate('Stats_Step3_Bounces_Row', true, false);
						}
						$GLOBALS['Stats_Step3_Bounces_List'] = $bounce_list;

						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = ''; $GLOBALS['StatsBounceList'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Bounces_Table');

					break; // triggeremails_bounces

					case 'triggeremails_unsubscribes':
						$unsubscribes = array();;

						$statid = $token_data['statid'];

						if ($summary['unsubscribecount'] > 0) {
							$unsubscribes = $statsapi->GetUnsubscribes($statid, $start, $perpage, $token_data['calendar_restrictions']['unsubscribes'], false, $this->column, $this->sort);
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

						$total_unsubscribes = $statsapi->GetUnsubscribes($statid, $start, $perpage, $token_data['calendar_restrictions']['unsubscribes'], true, '', '');

						$this->_SetupPaging($total_unsubscribes, $DisplayPage, $perpage, '', 'triggeremails_unsubscribes', $_GET['token']);

						$paging = $this->ParseTemplate('Stats_Remote_Paging', true, false);

						$GLOBALS['Paging'] = $paging;

						$unsub_list = '';
						foreach ($unsubscribes as $k => $unsubdetails) {
							$GLOBALS['EmailAddress'] = htmlspecialchars($unsubdetails['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);
							$GLOBALS['UnsubscribeTime'] = $this->PrintTime($unsubdetails['unsubscribetime'], true);
							$unsub_list .= $this->ParseTemplate('Stats_Step3_Unsubscribes_Row', true, false);
						}

						$GLOBALS['Stats_Step3_Unsubscribes_List'] = $unsub_list;

						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Unsubscribes_Table');
					break; // triggeremails_unsubscribes

					case 'triggeremails_forwards':
						$forwards = array();

						$statid = $token_data['statid'];

						if ($summary['emailforwards'] > 0) {
							$forwards = $statsapi->GetForwards($statid, $start, $perpage, $token_data['calendar_restrictions']['forwards'], false, false, $this->column, $this->sort);
						}
						$total_forwards = $statsapi->GetForwards($statid, $start, $perpage, $token_data['calendar_restrictions']['forwards'], true);
						$this->_SetupPaging($total_forwards, $DisplayPage, $perpage, '', 'triggeremails_forwards', $_GET['token']);

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

						if (isset($_GET['pagination']) && $_GET['pagination'] == 'false') {
							$GLOBALS['PagingBottom'] = $GLOBALS['Paging'] = '';
						}

						echo $this->ParseTemplate('Stats_Step3_Forwards_Table');
					break; // triggeremails_forwards

					default:
					break;
				} // switch type
			break; //export
		} // switch selectaction
	}

	/**
	* _SetupPaging
	* Sets up AJAX paging for statistics tables
	*
	* @param Int $numrecords The total number of records
	* @param Int $currentpage The current page being displayed
	* @param Int $perpage The number of entires per page
	* @param String $url The url to load when the page changes
	* @param String $type The statistics type being displayed
	* @param String $token The session token used to store parameters for the currently displays statistics
	*
	* @return Void Returns nothing, sets the variables for paging
	*/
	function _SetupPaging($numrecords=0, $currentpage=1, $perpage=20, $url, $type, $token)
	{
		$display_settings['NumberToShow'] = $this->GetPerPage();

		$PerPageDisplayOptions = '';
		$all_tok = '(' . GetLang('Paging_All') . ')';
		foreach (array('5', '10', '20', '30', '50', '100', '200', '500', '1000') as $p => $numtoshow) {
			$PerPageDisplayOptions .= '<option value="' . $numtoshow . '"';
			if ($numtoshow == $display_settings['NumberToShow']) {
				$PerPageDisplayOptions .= ' SELECTED';
			}
			$fmt_numtoshow = $this->FormatNumber($numtoshow);
			if ($numtoshow == 'all') {
				$fmt_numtoshow = $all_tok;
			}
			$PerPageDisplayOptions .= '>' . $fmt_numtoshow . '</option>';
		}
		$GLOBALS['PerPageDisplayOptions'] = $PerPageDisplayOptions;

		if (!$numrecords || $numrecords < 0) {
			$GLOBALS['PagingBottom'] = '<br />';
			$GLOBALS['Paging'] = '<br />';
			return false;
		}

		if ($currentpage < 1) {
			$currentpage = 1;
		}

		if ($perpage < 1 && $perpage != 'all') {
			$perpage = 10;
		}

		$num_pages = 1;
		if ($perpage != 'all') {
			$num_pages = ceil($numrecords / $perpage);
		}
		if ($currentpage > $num_pages) {
			$currentpage = $num_pages;
		}

		$prevpage = ($currentpage > 1) ? ($currentpage - 1) : 1;
		$nextpage = (($currentpage+1) > $num_pages) ? $num_pages : ($currentpage+1);

		$sortinfo = $this->GetSortDetails();

		$direction = $sortinfo['Direction'];
		$sort = $sortinfo['SortBy'];
		$sortdetails = '&SortBy=' . $sort . '&Direction=' . $direction;

		$string = '(' . GetLang('Page') . ' ' . $this->FormatNumber($currentpage) . ' ' . GetLang('Of') . ' ' . $this->FormatNumber($num_pages) . ')&nbsp;&nbsp;&nbsp;&nbsp;';

		$display_page_name = 'DisplayPage';
		if (isset($GLOBALS['PPDisplayName'])) {
			$display_page_name .= $GLOBALS['PPDisplayName'];
		}

		if ($currentpage > 1) {
			$string .= "<a href=\"javascript:REMOTE_admin_table($('#adminTable{$type}'),'$url','','$type','$token',1,'{$GLOBALS['SortColumn']}','{$GLOBALS['SortDirection']}')\" title=\"" . GetLang('GoToFirst') . "\">&laquo;</a>&nbsp;|&nbsp;";
			$string .= "<a href=\"javascript:REMOTE_admin_table($('#adminTable{$type}'),'$url','','$type','$token',$prevpage,'{$GLOBALS['SortColumn']}','{$GLOBALS['SortDirection']}')\" title=\"" . GetLang('PagingBack') . "\">".GetLang('PagingBack') ."</a>&nbsp;|";
		} else {
			$string .= '&laquo;&nbsp;|&nbsp;';
			$string .= GetLang('PagingBack') . '&nbsp;|';
		}

		if ($num_pages > $this->_PagesToShow) {
			$start_page = $currentpage - (floor($this->_PagesToShow/2));
			if ($start_page < 1) {
				$start_page = 1;
			}

			$end_page = $currentpage + (floor($this->_PagesToShow/2));
			if ($end_page > $num_pages) {
				$end_page = $num_pages;
			}

			if ($end_page < $this->_PagesToShow) {
				$end_page = $this->_PagesToShow;
			}

			$pagestoshow = ($end_page - $start_page);
			if (($pagestoshow < $this->_PagesToShow) && ($num_pages > $this->_PagesToShow)) {
				$start_page = ($end_page - $this->_PagesToShow+1);
			}

		} else {
			$start_page = 1;
			$end_page = $num_pages;
		}

		for ($pageid = $start_page; $pageid <= $end_page; $pageid++) {
			if ($pageid > $num_pages) {
				break;
			}

			$string .= '&nbsp;';
			if ($pageid == $currentpage) {
				$string .= '<b>' . $pageid . '</b>';
			} else {
				$string .= "<a href=\"javascript:REMOTE_admin_table($('#adminTable{$type}'),'$url','','$type','$token',$pageid,'{$GLOBALS['SortColumn']}','{$GLOBALS['SortDirection']}')\">$pageid</a>";
			}
			$string .= '&nbsp;|';
		}

		if ($currentpage == $num_pages) {
			$string .= '&nbsp;' . GetLang('PagingNext') . '&nbsp;|';
			$string .= '&nbsp;&raquo;';
		} else {
			$string .= "&nbsp;<a href=\"javascript:REMOTE_admin_table($('#adminTable{$type}'),'$url','','$type','$token',$nextpage,'{$GLOBALS['SortColumn']}','{$GLOBALS['SortDirection']}')\">" . GetLang('PagingNext') . "</a>&nbsp;|";
			$string .= "&nbsp;<a href=\"javascript:REMOTE_admin_table($('#adminTable{$type}'),'$url','','$type','$token',$num_pages,'{$GLOBALS['SortColumn']}','{$GLOBALS['SortDirection']}')\" title=\"" . GetLang('GoToLast') . "\">&raquo;</a>";
		}

		$GLOBALS['DisplayPage'] = $string;

		if ($perpage != 'all' && ($perpage > $this->_PagingMinimum && $numrecords > $perpage)) {
			$paging_bottom = $this->ParseTemplate('Paging_Bottom', true, false);
		} else {
			$paging_bottom = '<br />';
		}

		$GLOBALS['PagingBottom'] = $paging_bottom;
	}

	/**
	* DisplayChart
	* Sets the URL for chart data and sets the variables to display the chart
	*
	* @param String $chartname The variable name for the chart
	* @param String $chart_area The statistics area the chart is for
	* @param Int $statid The statid the chart is for
	* @param String $type The type of chart to display
	* @param Array $settings Settings for the chart
	*
	* @see InsertChartImage
	*
	* @return Void Returns nothing
	*/
	function DisplayChart($chartname='', $chart_area='', $statid=0, $type = 'pie', $settings = null)
	{
		$data_url = 'stats_chart.php?graph=' . urlencode(strtolower($chartname)) . '&Area='.urlencode(strtolower($chart_area)) . '&statid=' . (int)$statid . '&' . IEM::SESSION_NAME . '=' . IEM::sessionID();

		$this->InsertChartImage($chartname,$data_url);
	}

	/**
	* InsertChartImage
	* Sets the variables to display a statistics chart
	*
	* @param String $chartname The variable name for the chart
	* @param String $data_url The URL the chart should get data from
	* @param Array $settings An array of settings for the chart
	*
	* @return Void Returns nothing, sets the variables for displaying the chart
	*/
	function InsertChartImage($chartname,$data_url,$settings = null)
	{
		$params = array();
		if (is_array($settings)) {
			foreach ($settings as $key => $val) {
				$params[] = urlencode($key) . "=" . urlencode($val);
			}
		}
		$params = implode('&amp;',$params);

		if (Settings_API::GDEnabled()) {
			$GLOBALS[$chartname] = '<img src="' . $data_url . ( $params ? '&amp;' . $params : '') . '&amp;GetAsImg=1" style="display: block;">';
		} else {
			$GLOBALS[$chartname] = '<p>(' . GetLang('GD_Not_Enabled') . ')</p>';
		}
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
		$user = GetUser();
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
		$user = GetUser();
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
	 * IsOwner
	 * Checks whether the current user matches the owner ID passed in.
	 *
	 * @param Int $expected The owner ID to check.
	 *
	 * @return Boolean True if the current user is the owner or an admin, otherwise false.
	 */
	function IsOwner($expected)
	{
		$user = GetUser();
		$actual = $user->Get('userid');
		if ($user->Admin()) {
			return true;
		}
		return (intval($expected) === intval($actual));
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
		$user = GetUser();
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
}

$RemoteStats = new RemoteStats();
$RemoteStats->Process();
