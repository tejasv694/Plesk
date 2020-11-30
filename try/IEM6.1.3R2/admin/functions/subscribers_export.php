<?php
/**
* This file handles exporting of subscribers only.
*
* @version     $Id: subscribers_export.php,v 1.50 2008/02/27 03:34:11 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
if (!defined('SENDSTUDIO_BASE_DIRECTORY')) {
	require_once(dirname(__FILE__) . '/sendstudio_functions.php');
}

/**
* Class for exporting subscribers.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Subscribers_Export extends Subscribers
{
	public
		/**
		 * PerRefresh
		 * Number of subscribers to export per refresh.
		 *
		 * @var Int
		 */
		$PerRefresh = 100;
	
	private
		/**
		 * _customfields_loaded
		 * An array of custom field objects that have been loaded. This is filled up on the fly once per refresh - each custom field is added to the array once.
		 *
		 * @see ExportSubscriber
		 */
		$_customfields_loaded = array(),
		
		/**
		 * Whether or not to show filtering optinos for the selected contact
		 * lists.
		 * 
		 * @var $_showFilteringOptions
		 */
		$_showFilteringOptions = false;

	/**
	* Process
	* Works out what you're trying to do and takes appropriate action.
	* Uses a 'queue' system to export subscribers, and then removes them as it exports the subscriber.
	*
	* @param String $action Action to perform. This is usually 'step1', 'step2', 'step3' etc. This gets passed in by the Subscribers::Process function.
	*
	* @see Subscribers::Process
	* @see GetUser
	* @see ChooseList
	* @see ExportSubscribers_Step2
	* @see ExportSubscribers_Step3
	* @see ExportSubscribers_Step4
	* @see GetApi
	* @see API::ClearQueue
	* @see API::RemoveFromQueue
	* @see API::FetchFromQueue
	* @see PrintStatusReport
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function Process($action=null)
	{
		$user = GetUser();

		$this->PrintHeader(false, false, false);

		if (!is_null($action)) {
			$action = strtolower($action);
		}



		switch ($action) {
			case 'step2':
				if (isset($_POST['ShowFilteringOptions'])) {
					$this->_showFilteringOptions = true;
				}

				$listid = 0;
				
				if (isset($_POST['lists'])) {
					$listid = $_POST['lists'];
				} elseif (isset($_GET['lists'])) {
					$listid = $_GET['lists'];
				} elseif (isset($_POST['list'])) {
					$listid = $_POST['list'];
				} elseif (isset($_GET['list'])) {
					$listid = $_GET['list'];
				}

				if (is_array($listid)) {
					// Make sure that "any" is not selected when you are selecting multiple list
					if(count($listid) > 1) {
						if(($index = array_search('any', $listid)) !== false) {
							unset($listid[$index]);
						}
					}

					// If the array only contain 1 id, make take it out of the array
					if(count($listid) == 1) {
						$listid = array_pop($listid);
					}

					// Make sure the IDs are numerics
					if (is_array($listid)) {
						$temp = array();
						foreach($listid as $id) {
							array_push($temp, intval($id));
						}
						$listid = $temp;
					}
				}

				$exportinfo = array();
				$exportinfo['List'] = $listid;

				IEM::sessionSet('ExportInfo', $exportinfo);
				$this->ExportSubscribers_Step2($listid);
			break;

			case 'step3':
				$this->ExportSubscribers_Step3();
			break;

			case 'step4':
				$this->ExportSubscribers_Step4();
			break;

			case 'exportiframe':
				$this->PrintHeader(false, false, false);
				$exportinfo = IEM::sessionGet('ExportInfo');

				$GLOBALS['ProgressTitle'] = GetLang('ExportResults_InProgress');
				$GLOBALS['ProgressMessage'] = sprintf(GetLang('ExportResults_InProgress_Message'), $this->FormatNumber($exportinfo['QueueSize']));
				$GLOBALS['ProgressStatus'] = sprintf(GetLang('ExportResults_InProgress_Status'), $this->FormatNumber($exportinfo['ExportsCompleted']), $this->FormatNumber($exportinfo['QueueSize']));
				$GLOBALS['ProgressURLAction'] = 'index.php?Page=Subscribers&Action=Export&SubAction=Export';

				$this->ParseTemplate('ProgressReport_Popup');
				$this->PrintFooter(true);
			break;

			case 'export':
				$exportinfo = IEM::sessionGet('ExportInfo');
				if($exportinfo['ExportsCompleted'] == $exportinfo['QueueSize']){
					?>
					<script>
						self.parent.parent.location = 'index.php?Page=Subscribers&Action=Export&SubAction=PrintReport';
					</script>
					<?php
					exit();					
				}
				
				$subscriber_Api = $this->GetApi('Subscribers');
				$ExportsCompleted = $exportinfo['ExportsCompleted'];
				list($queueinfo) = $exportinfo['ExportQueue'];


				$queueid = $queueinfo['queueid'];
				$listid = $queueinfo['listid'];

				$subscriber_list = $subscriber_Api->FetchFromQueue($queueid, 'export', 1, $this->PerRefresh);
				
				if (empty($subscriber_list) && (count($exportinfo['ExportQueue']) > 1)) {
					array_shift($exportinfo['ExportQueue']);
					IEM::sessionSet('ExportInfo', $exportinfo);
					?>
						<script>
							setTimeout('window.location="index.php?Page=Subscribers&Action=Export&SubAction=Export&x=<?php echo rand(1,50); ?>;"', 2);
						</script>
					<?php
					exit();
				}
								
				if(empty($subscriber_list)){
					trigger_error("Failed fetching recipients for export queue");
					trigger_error(print_r($exportinfo,true));
					?>
					<script>
						self.parent.parent.location = 'index.php?Page=Subscribers&Action=Export&SubAction=PrintReport&error=1';
					</script>
					<?php					
					exit();
				}
		
				
				$percentProcessed = 0;
				$listCount = count($subscriber_list);
				
				if($exportinfo['Settings']['FileType'] == "xml"){
					foreach ($subscriber_list as $pos => $subscriberid) {
						$ExportsCompleted++;
						
						// Update the status
						$temp = sprintf(GetLang('ExportResults_InProgress_Status'), $this->FormatNumber($exportinfo['ExportsCompleted']), $this->FormatNumber($exportinfo['QueueSize']));
						$percentProcessed = ceil(($ExportsCompleted / $exportinfo['QueueSize'])*100);
						echo "<script>\n";
						echo sprintf("self.parent.UpdateStatus('%s', %d);", $temp, $percentProcessed);
						echo "</script>\n";
						
						// the start/end are needed for creating the xml
						// to make sure it creates valid xml.
						$start = false;
						if ($ExportsCompleted == 1) {
							$start = true;
						}

						$end = false;
						if ($ExportsCompleted == $exportinfo['QueueSize']) {
							$end = true;
						}
						
						$this->ExportSubscriber($subscriberid, $listid, $start, $end);
						
						flush();
					}
				}elseif($exportinfo['Settings']['FileType'] == "csv"){
					foreach ($subscriber_list as $pos => $subscriberid) {
						$ExportsCompleted++;
						
						// Update the status
						$temp = sprintf(GetLang('ExportResults_InProgress_Status'), $this->FormatNumber($exportinfo['ExportsCompleted']), $this->FormatNumber($exportinfo['QueueSize']));
						$percentProcessed = ceil(($ExportsCompleted / $exportinfo['QueueSize'])*100);
						echo "<script>\n";
						echo sprintf("self.parent.UpdateStatus('%s', %d);", $temp, $percentProcessed);
						echo "</script>\n";
						
						$this->ExportSubscriber($subscriberid, $listid);

						flush();
					}
				}								
				$subscriber_Api->RemoveFromQueue($queueid, 'export', $subscriber_list);

				$exportinfo['ExportsCompleted'] = $ExportsCompleted;

				IEM::sessionSet('ExportInfo', $exportinfo);
				//added so that we don't have to reload the window again when it's already complete
				if($exportinfo['ExportsCompleted'] == $exportinfo['QueueSize']){
					?>
					<script>
						self.parent.parent.location = 'index.php?Page=Subscribers&Action=Export&SubAction=PrintReport';
					</script>
					<?php
					exit();					
				}
				//---
				$this->PrintFooter(true);
				?>
					<script>
						setTimeout('window.location="index.php?Page=Subscribers&Action=Export&SubAction=Export&x=<?php echo rand(1,50); ?>;"', 2);
					</script>
				<?php
				exit();
			break;

			case 'printreport':
				if(isset($_GET['error'])){
					$error = $_GET['error'];
					if($error == 1){$GLOBALS['Message'] = "<h2>Failed fetching recipients for export queue</h2><br />Please contact your system administrator regarding this problem.<br />";}
					if($error == 2){$GLOBALS['Message'] = "<h2>The export queue was not fully cleared.</h2><br />Please contact your system administrator regarding this problem.<br />";}					
					$exportinfo = IEM::sessionGet('ExportInfo');
					$api = $this->GetApi('Subscribers');
					if (isset($exportinfo['ExportQueue'])) {
						$queueid = $exportinfo['ExportQueue'];
						if(is_int($queueid)){
							$api->ClearQueue($queueid, 'export');
						}					
						if (is_array($queueid)) {
							foreach($queueid as $id) {
								$api->ClearQueue($id['queueid'], 'export');
							}
						}
					}	
					if (isset($exportinfo['ExportJobId'])) {
						$jobapi = $this->GetApi('jobs');
						$jobapi->Delete($exportinfo['ExportJobId']);
						unset($exportinfo['ExportJobId']);
					}	
					$this->ParseTemplate('Subscribers_Export_Results_Report');
					exit();
				}
				$exportinfo = IEM::sessionGet('ExportInfo');

				$api = $this->GetApi('Subscribers');
				if (isset($exportinfo['ExportQueue'])) {
					$queueid = $exportinfo['ExportQueue'];
					if ($queueid && is_array($queueid)) {
						foreach($queueid as $id) {
							$api->ClearQueue($id['queueid'], 'export');
						}
					}
				}

				// clear the Job table too..
				if (isset($exportinfo['ExportJobId'])) {
					$jobapi = $this->GetApi('jobs');
					$jobapi->Delete($exportinfo['ExportJobId']);
					unset($exportinfo['ExportJobId']);
				}

				$exportlink = SENDSTUDIO_TEMP_URL . '/' . $exportinfo['ExportFile'];
				$GLOBALS['Message'] = $this->PrintSuccess('ExportResults_Intro', $exportlink);

				$this->ParseTemplate('Subscribers_Export_Results_Report');
			break;

			default:
				$this->ChooseList('Export', 'Step2');
		}
	}

	/**
	* ExportSubscribers_Step2
	* Prints out the 'search' form to restrict which subscribers you are going to export.
	*
	* @param Int $listid Which list you are going to export subscribers from.
	* @param String $msg If there is a message (eg no subscribers for your search criteria), then it is passed in so it can be displayed above the search form.
	*
	* @see GetApi
	* @see Lists_API::Load
	* @see Lists_API::GetListFormat
	* @see Lists_API::GetCustomFields
	* @see Search_Display_CustomField
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function ExportSubscribers_Step2($listid=0, $msg=false)
	{
		$user = GetUser();
		$access = $user->HasAccess('Subscribers', 'Export');
		if (!$access) {
			$this->DenyAccess();
			return;
		}

		$user_lists = $user->GetLists();

		if ($msg) {
			$GLOBALS['Error'] = $msg;
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}

		// Make sure that user can only select newsletter from his/her allowable list
		if(!$user->ListAdmin() && (is_numeric($listid) || is_array($listid))) {
			$allowableListIDs = array_keys($user_lists);
			if(is_array($listid)) {
				$listid = array_intersect($listid, $allowableListIDs);
			} else {
				$temp = in_array($listid, $allowableListIDs);
				if(!$temp) $listid = null;
			}

			if(empty($listid)) {
				if(!headers_sent()) {
					header('Location: index.php?Page=Subscribers&Action=Export');
				}
				?>
				<script>
					document.location.href = 'index.php?Page=Subscribers&Action=Export';
				</script>
				<?php
				exit();
			}
		}
		$listApi = $this->GetApi('Lists');

		if (is_numeric($listid)) {
			$listApi->Load($listid);
			$listname = $listApi->name;
			$GLOBALS['List'] = $listid;
			$GLOBALS['Heading'] = GetLang('Subscribers_Export');
			$GLOBALS['DoNotShowFilteringOptionLabel'] = GetLang('SubscribersExportDoNotShowFilteringOptionsExplainOne');
			$GLOBALS['ShowFilteringOptionLabel'] = GetLang('SubscribersExportShowFilteringOptionsExplainOne');
		} elseif (is_array($listid)) {
			// Load list name for each of the selected mailing list
			$listnames = array();
			foreach ($listid as $id) {
				if ($listApi->Load($id)) {
					array_push($listnames, $listApi->name);
				}
			}
			$GLOBALS['List'] = implode('&Lists[]=',$listid);
			$GLOBALS['Heading'] = sprintf(GetLang('Subscribers_Export_MultipleList'), htmlspecialchars("'".implode("', '", $listnames)."'", ENT_QUOTES, SENDSTUDIO_CHARSET));
			$GLOBALS['DoNotShowFilteringOptionLabel'] = GetLang('SubscribersExportDoNotShowFilteringOptionsExplain');
			$GLOBALS['ShowFilteringOptionLabel'] = GetLang('SubscribersExportShowFilteringOptionsExplain');
		} else {
			$GLOBALS['List'] = $listid;
			$GLOBALS['Heading'] = GetLang('Subscribers_Export_AnyList');
			$GLOBALS['DoNotShowFilteringOptionLabel'] = GetLang('SubscribersExportDoNotShowFilteringOptionsExplain');
			$GLOBALS['ShowFilteringOptionLabel'] = GetLang('SubscribersExportShowFilteringOptionsExplain');
		}

		if (sizeof(array_keys($user_lists)) != 1) {
			$GLOBALS['FilterOptions'] = 'style="display:none;"';
		}

		if ($this->_showFilteringOptions) {
			$GLOBALS['ShowFilteringOptions'] = ' CHECKED';
			$GLOBALS['FilterNext_Display'] = 'display:none;';
		} else {
			$GLOBALS['DoNotShowFilteringOptions'] = ' CHECKED';
			$GLOBALS['FilteringOptions_Display'] = 'style="display:none;"';
			$GLOBALS['FilterNext_Display'] = 'display:\'\';';
		}

		$GLOBALS['FormAction'] = 'Export';

		$confirmed  = '<option value="-1">' . GetLang('Either_Confirmed') . '</option>';
		$confirmed .= '<option value="1">'  . GetLang('Confirmed') . '</option>';
		$confirmed .= '<option value="0">'  . GetLang('Unconfirmed') . '</option>';

		$format_either  = '<option value="-1">' . GetLang('Either_Format') . '</option>';
		$format_html    = '<option value="h">' . GetLang('Format_HTML') . '</option>';
		$format_text    = '<option value="t">' . GetLang('Format_Text') . '</option>';

		$listformat = $listApi->GetListFormat();
		switch ($listformat) {
			case 'h':
				$format = $format_html;
			break;
			case 't':
				$format = $format_text;
			break;
			default:
				$format = $format_either . $format_html . $format_text;
		}

		$GLOBALS['ConfirmedList'] = $confirmed;
		$GLOBALS['FormatList'] = $format;

		$GLOBALS['ClickedLinkOptions'] = $this->ShowLinksClickedOptions();

		$GLOBALS['OpenedNewsletterOptions'] = $this->ShowOpenedNewsletterOptions();

		$this->PrintSubscribeDate();

		/*
		 * Print custom fields options if applicable
		 */
		if(is_numeric($listid)) {
			$customfields = $listApi->GetCustomFields($listid);

			if (!empty($customfields)) {
				$customfield_display = $this->ParseTemplate('Subscriber_Search_Step2_CustomFields', true, false);
				foreach ($customfields as $pos => $customfield_info) {
					$manage_display = $this->Search_Display_CustomField($customfield_info);
					$customfield_display .= $manage_display;
				}
				$GLOBALS['CustomFieldInfo'] = $customfield_display;
			}
		}
		
		$this->ParseTemplate('Subscriber_Search_Step2');

		if (sizeof(array_keys($user_lists)) == 1) {
			return;
		}
		
		if (!$msg && !$this->_showFilteringOptions) {
			/*
			 * Please let me know who did this so I can lecture them.
			 */
			?>
			<script type="text/javascript">
				document.forms[0].submit();
			</script>
			<?php
			exit();
		}
	}

	/**
	* ExportSubscribers_Step3
	* Checks that there are subscribers to export. Creates a 'queue' of subscribers to export and lets you choose which fields you want to export.
	*
	* @see GetApi
	* @see Lists_API::Load
	* @see Lists_API::GetCustomFields
	* @see GetSortDetails
	* @see Subscribers_API::GetSubscribers
	* @see ExportSubscribers_Step2
	* @see API::CreateQueue
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function ExportSubscribers_Step3()
	{
		$subscriber_api = $this->GetApi('Subscribers');
		$user = IEM::getCurrentUser();
		$exportinfo = IEM::sessionGet('ExportInfo');

		$listApi = $this->GetApi('Lists');
		$listid = $exportinfo['List'];
		$CustomFieldsList = array();

		if (is_numeric($listid)) {
			$listApi->Load($listid);
			$listname = $listApi->name;
			$GLOBALS['List'] = $listid;
			$GLOBALS['Heading'] = GetLang('Subscribers_Export');
			$CustomFieldsList = $listApi->GetCustomFields($listid);
		} elseif (is_array($listid)) {
			// Load list name for each of the selected mailing list
			$listnames = array();
			$eachCustomFieldList = array();
			foreach ($listid as $id) {
				if ($listApi->Load($id)) {
					array_push($listnames, $listApi->name);
					$eachCustomFieldList = $listApi->getCustomFields($id);
					$CustomFieldsList = array_merge($CustomFieldsList, $eachCustomFieldList);
				}
			}
			$GLOBALS['List'] = implode('&Lists[]=',$listid);
			$GLOBALS['Heading'] = sprintf(GetLang('Subscribers_Export_MultipleList'), htmlspecialchars("'".implode("', '", $listnames)."'", ENT_QUOTES, SENDSTUDIO_CHARSET));
		} else {
			$GLOBALS['List'] = $listid;
			$GLOBALS['Heading'] = GetLang('Subscribers_Export_AnyList');
		}

		if (!$exportinfo || !empty($_POST)) {
			$export_details = array();
			if (isset($_POST['emailaddress']) && $_POST['emailaddress'] != '') {
				$export_details['Email'] = $_POST['emailaddress'];
			}

			if (isset($_POST['format']) && $_POST['format'] != '-1') {
				$export_details['Format'] = $_POST['format'];
			}

			if (isset($_POST['confirmed']) && $_POST['confirmed'] != '-1') {
				$export_details['Confirmed'] = $_POST['confirmed'];
			}

			if (isset($_POST['status']) && $_POST['status'] != '-1') {
				$export_details['Status'] = $_POST['status'];
			}

			if (isset($_POST['datesearch']) && isset($_POST['datesearch']['filter'])) {
				$export_details['DateSearch'] = $_POST['datesearch'];

				$export_details['DateSearch']['StartDate'] = AdjustTime(array(0, 0, 1, $_POST['datesearch']['mm_start'], $_POST['datesearch']['dd_start'], $_POST['datesearch']['yy_start']));

				$export_details['DateSearch']['EndDate'] = AdjustTime(array(0, 0, 1, $_POST['datesearch']['mm_end'], $_POST['datesearch']['dd_end'], $_POST['datesearch']['yy_end']));
			}

			$customfields = array();
			if (isset($_POST['CustomFields']) && !empty($_POST['CustomFields'])) {
				$customfields = $_POST['CustomFields'];
			}

			if (isset($_POST['clickedlink']) && isset($_POST['linkid'])) {
				$export_details['LinkType'] = 'clicked';
				if (isset($_POST['linktype']) && $_POST['linktype'] == 'not_clicked') {
					$export_details['LinkType'] = 'not_clicked';
				}

				$export_details['Link'] = $_POST['linkid'];
			}

			if (isset($_POST['openednewsletter']) && isset($_POST['newsletterid'])) {
				$export_details['OpenType'] = 'opened';
				if (isset($_POST['opentype']) && $_POST['opentype'] == 'not_opened') {
					$export_details['OpenType'] = 'not_opened';
				}

				$export_details['Newsletter'] = $_POST['newsletterid'];
			}

			if (isset($_POST['Search_Options'])) {
				$export_details['Search_Options'] = $_POST['Search_Options'];
			}

			$export_details['CustomFields'] = $customfields;

			if(empty($listid)){$listid = isset($_GET['Lists'])? $_GET['Lists'] : $_GET['List'];}
			if(is_array($listid)) {
				// Make sure that "any" is not selected when you are selecting multiple list
				if(count($listid) > 1) {
					if(($index = array_search('any', $listid)) !== false) {
						unset($listid[$index]);
					}
				}

				// If the array only contain 1 id, make take it out of the array
				if(count($listid) == 1) {
					$listid = array_pop($listid);
				}

				// Make sure the IDs are numerics
				if (is_array($listid)) {
					$temp = array();
					foreach($listid as $id) {
						array_push($temp, intval($id));
					}
					$listid = $temp;
				}
			}

			/**
			 * Make sure that user can only select newsletter from his/her allowable list
			 */
				if(!$user->ListAdmin() && (is_numeric($listid) || is_array($listid))) {
					$user_lists = $user->GetLists();
					$allowableListIDs = array_keys($user_lists);
					if(is_array($listid)) {
						$listid = array_intersect($listid, $allowableListIDs);
					} else {
						$temp = in_array($listid, $allowableListIDs);
						if(!$temp) $listid = null;
					}

					if(empty($listid)) {
						if(!headers_sent()) {
							header('Location: index.php?Page=Subscribers&Action=Export');
						}
						?>
						<script>
							document.location.href = 'index.php?Page=Subscribers&Action=Export';
						</script>
						<?php
						exit();
					}
				}
			/**
			 * -----
			 */

			$export_details['List'] = $listid;

			$exportinfo['ExportDetails'] = $export_details;

			$exportinfo['ExportsCompleted'] = 0;

			IEM::sessionSet('ExportInfo', $exportinfo);
		}

		$exportinfo = IEM::sessionGet('ExportInfo');
		$export_details = $exportinfo['ExportDetails'];

		if (isset($exportinfo['ExportQueue'])) {
			$queueid = $exportinfo['ExportQueue'];
			if ($queueid && is_array($queueid)) {
				foreach($queueid as $id) {
					$subscriber_api->ClearQueue($id['queueid'], 'export');
				}
			}
		}

		/**
		 * Get export queueIDs
		 */
			$exportqueue = array();
			if(is_numeric($export_details['List'])) {
				array_push($exportqueue, array(	'queueid' 	=> $subscriber_api->CreateQueue('Export'),
												'listid' 	=> $export_details['List']));
			} elseif(is_array($export_details['List'])) {
				foreach($export_details['List'] as $listid) {
					array_push($exportqueue, array(	'queueid' 	=> $subscriber_api->CreateQueue('Export'),
													'listid' 	=> $listid));
				}
			} else {
				$user_lists = $user->GetLists();
				foreach($user_lists as $listid=>$each) {
					array_push($exportqueue, array(	'queueid' 	=> $subscriber_api->CreateQueue('Export'),
													'listid' 	=> $listid));
				}
			}
			$exportinfo['ExportQueue'] = $exportqueue;
		/**
		 * -----
		 */

		/**
		 * Put subscribers into queue
		 */
			$totalsubscribers = 0;
			$tempExportDetails = $export_details;
			foreach($exportinfo['ExportQueue'] as $queue) {
				$queueinfo = array('queueid' => $queue['queueid'], 'queuetype' => 'export', 'ownerid' => $user->userid);
				$tempExportDetails['List'] = $queue['listid'];
				$tempInfo = $subscriber_api->GetSubscribers($tempExportDetails, array(), false, $queueinfo, 'true');
				$totalsubscribers += $tempInfo['count'];
			}

			$jobapi = $this->GetApi('jobs');
			$jobcreated = $jobapi->Create('export', time(), $user->userid, $exportinfo, 'export', 0, 0, $user->userid);
			$exportinfo['ExportJobId'] = $jobcreated;
		/**
		 * -----
		 */

		if ($totalsubscribers < 1) {
			$this->ExportSubscribers_Step2($exportinfo['List'], GetLang('NoSubscribersMatch'));
			return;
		}

		$exportinfo['QueueSize'] = $totalsubscribers;
		IEM::sessionSet('ExportInfo', $exportinfo);

		$GLOBALS['TotalSubscriberCount'] = $this->FormatNumber($totalsubscribers);
		if ($totalsubscribers == 1) {
			$GLOBALS['Message'] = $this->PrintSuccess('Subscribers_Export_FoundOne');
		} else {
			$GLOBALS['Message'] = $this->PrintSuccess('Subscribers_Export_FoundMany', $GLOBALS['TotalSubscriberCount']);
		}

		$all_options = array('e' => GetLang('EmailAddress'), 'f' => GetLang('Format'), 'c' => GetLang('Confirmed'), 'mdy' => GetLang('SubscribeDate_MDY'));

		if (SENDSTUDIO_IPTRACKING) {
			$all_options['i'] = GetLang('SubscriberIPAddress');
		}

		if (isset($export_details['Status']) && $export_details['Status'] == 'b') {
			$all_options['btime'] = GetLang('SubscriberBounceTime');
			$all_options['btype'] = GetLang('SubscriberBounceType');
		}

		foreach ($CustomFieldsList as $pos => $details) {
			$all_options[$details['fieldid']] = $details['name'];
		}

		$all_options['n'] = GetLang('None');

		$fieldoptions = '';

		$fieldcount = sizeof($all_options) - 1;
		for ($i = 1; $i <= $fieldcount; $i++) {
			$GLOBALS['FieldName'] = sprintf(GetLang('ExportField'), $i);
			$GLOBALS['OptionName'] = 'fieldoption[' . $i . ']';
			$optionlist = '';
			$fcount = 1;
			foreach ($all_options as $id => $name) {
				$optionlist .= '<option value="' . $id . '"';
				if ($fcount == $i) {
					$optionlist .= ' SELECTED';
				}

				$optionlist .= '>' . htmlspecialchars($name, ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
				if ($id == 'mdy') {
					$optionlist .= '<option value="dmy">' . GetLang('SubscribeDate_DMY') . '</option>';
					$optionlist .= '<option value="ymd">' . GetLang('SubscribeDate_YMD') . '</option>';
				}
				$fcount++;
			}
			$GLOBALS['OptionList'] = $optionlist;
			$fieldoptions .= $this->ParseTemplate('Subscribers_Export_Step3_Options', true, false);
		}
		$GLOBALS['FieldOptions'] = $fieldoptions;

		$this->ParseTemplate('Subscribers_Export_Step3');
	}

	/**
	* ExportSubscribers_Step4
	* Prints out the export header (if required) and creates the export file. This is the last step before exports happen.
	*
	* @see GetApi
	* @see CustomFields_API::Load
	* @see CustomFields_API::GetFieldName
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function ExportSubscribers_Step4()
	{
		$exportinfo = IEM::sessionGet('ExportInfo');

		$exportsettings = array();
		$exportsettings['Headers'] = $_POST['includeheader'];
		$exportsettings['FieldSeparator'] = $_POST['fieldseparator'];
		$exportsettings['FieldEnclosedBy'] = $_POST['fieldenclosedby'];
		$exportsettings['FieldOptions'] = $_POST['fieldoption'];
		$exportsettings['FileType'] = trim($_POST['filetype']);

		if (!in_array($exportsettings['FileType'], array('csv', 'xml'))) {
			$exportsettings['FileType'] = 'csv';
		}

		$exportinfo['ExportFile'] = 'export-'. md5(uniqid(rand(), true) . SENDSTUDIO_LICENSEKEY) . '.' . $exportsettings['FileType'];
		touch(TEMP_DIRECTORY . '/' . $exportinfo['ExportFile']);
		chmod(TEMP_DIRECTORY . '/' . $exportinfo['ExportFile'], 0644);

		$exportinfo['Settings'] = $exportsettings;
		IEM::sessionSet('ExportInfo', $exportinfo);

		$queuesize = $exportinfo['QueueSize'];

		if ($queuesize == 1) {
			$GLOBALS['SubscribersReport'] = GetLang('ExportSummary_FoundOne');
		} else {
			$GLOBALS['SubscribersReport'] = sprintf(GetLang('ExportSummary_FoundMany'), $this->FormatNumber($queuesize));
		}

		$exportfile = $exportinfo['ExportFile'];

		if (is_file(TEMP_DIRECTORY . '/'. $exportinfo['ExportFile'])) {
			unlink(TEMP_DIRECTORY . '/'. $exportinfo['ExportFile']);
		}

		$customfields_Api = $this->GetApi('CustomFields');

		if ($exportsettings['Headers']) {
			$parts = array();
			foreach ($exportsettings['FieldOptions'] as $pos => $type) {
				switch (strtolower($type)) {
					case 'n':
						continue;
					break;
					case 'e':
						$parts[] = GetLang('EmailAddress');
					break;
					case 'f':
						$parts[] = GetLang('Format');
					break;
					case 'c':
						$parts[] = GetLang('Confirmed');
					break;
					case 'dmy':
						$parts[] = GetLang('SubscribeDate_DMY');
					break;
					case 'mdy':
						$parts[] = GetLang('SubscribeDate_MDY');
					break;
					case 'ymd':
						$parts[] = GetLang('SubscribeDate_YMD');
					break;
					case 'i':
						$parts[] = GetLang('SubscriberIPAddress');
					break;
					case 'btime':
						$parts[] = GetLang('SubscriberBounceTime');
					break;
					case 'btype':
						$parts[] = GetLang('SubscriberBounceType');
					break;

					default:
						if (is_numeric($type)) {
							$customfields_Api->Load($type);
							$parts[] = $customfields_Api->GetFieldName();
						}
				}
			}

			switch($exportsettings['FileType']) {
				case 'xml':
					$line = '<?xml version="1.0" encoding="UTF-8"?>'."\n".
							'<export>'."\n".
							"\t".'<version>'.IEM::VERSION.'</version>'."\n".
							"\t".'<type>subscribers</type>'."\n".
							"\t".'<fields>'."\n";

					foreach($parts as $index => $part) {
						$line .= "\t\t".'<field id="'.$index.'">'.htmlspecialchars($part, ENT_QUOTES, SENDSTUDIO_CHARSET).'</field>'."\n";
					}

					$line .= "\t".'</fields>'."\n";
				break;

				case 'csv':
				default:
					if ($exportsettings['FieldEnclosedBy'] != '') {
						$line = '';
						foreach ($parts as $p => $part) {
							// To escape a field enclosure inside a field we double it up
							$part = str_replace($exportsettings['FieldEnclosedBy'], $exportsettings['FieldEnclosedBy'].$exportsettings['FieldEnclosedBy'], $part);
							$line .= $exportsettings['FieldEnclosedBy'] . $part . $exportsettings['FieldEnclosedBy'] . $exportsettings['FieldSeparator'];
						}
						$line = substr($line, 0, -1);
					} else {
						$line = implode($exportsettings['FieldSeparator'], $parts);
					}

					$line .= "\n";
				break;
			}

			$fp = fopen(TEMP_DIRECTORY . '/' . $exportinfo['ExportFile'], 'a');
			fputs($fp, $line, strlen($line));
			fclose($fp);
		}

		$this->ParseTemplate('Subscribers_Export_Step4');
	}

	/**
	* PrintStatusReport
	* Prints out the status report of how many subscribers have been exported, how many to go.
	*
	* @return Void Prints out the report, doesn't return anything.
	*/
	function PrintStatusReport()
	{
		$exportinfo = IEM::sessionGet('ExportInfo');

		$GLOBALS['ExportResults_Message'] = sprintf(GetLang('ExportResults_InProgress_Message'), $this->FormatNumber($exportinfo['QueueSize']));

		$GLOBALS['Report'] = sprintf(GetLang('ExportResults_InProgress_Status'), $this->FormatNumber($exportinfo['ExportsCompleted']), $this->FormatNumber($exportinfo['QueueSize']));

		$this->ParseTemplate('Subscribers_Export_ReportProgress');
	}

	/**
	* ExportSubscriber
	* Actually does the exporting of the subscriber.  Gets what it needs to export from the session, prints out the subscriber info to the export file.
	*
	* @param Int $subscriberid The subscriber to export.
	* @param Int $listid The list id subscriber is listed on
	* @param boolean $first Indicates whether or not this is the first record, used only by XML export (OPTIONAL)
	* @param boolean $last Indicates whether or not this is the last record, used only by XML export (OPTIONAL)
	*
	* @see GetApi
	* @see Subscribers_API::LoadSubscriberList
	* @see Subscribers_API::GetCustomFieldSettings
	*
	* @return Void Exports the subscriber information to the export file.
	*/
	function ExportSubscriber($subscriberid=0, $listid=0, $first = false, $last = false)
	{

		$exportinfo = IEM::sessionGet('ExportInfo');

		$list = $listid;
		$exportfile = $exportinfo['ExportFile'];
		$exportsettings = $exportinfo['Settings'];

		$subscriberApi = $this->GetApi('Subscribers');
		$subscriberinfo = $subscriberApi->LoadSubscriberList($subscriberid, $list);

		$CustomFieldApi = $this->GetApi('CustomFields');

		$bounce_info = false;

		if (in_array('btype', $exportsettings['FieldOptions'])) {
			$bounce_info = $subscriberApi->LoadSubscriberBounceInfo($subscriberid, $list);
		}

		if (in_array('btime', $exportsettings['FieldOptions'])) {
			if (!$bounce_info) {
				$bounce_info = $subscriberApi->LoadSubscriberBounceInfo($subscriberid, $list);
			}
		}

		if ($bounce_info) {
			$this->LoadLanguageFile('Stats');
		}

		$parts = array();
		foreach ($exportsettings['FieldOptions'] as $pos => $type) {
			switch (strtolower($type)) {
				case 'n':
					continue;
				break;

				case 'e':
					$parts[] = $subscriberinfo['emailaddress'];
				break;

				case 'f':
					$parts[] = ($subscriberinfo['format'] == 'h') ? GetLang('Format_HTML') : GetLang('Format_Text');
				break;

				case 'c':
					$parts[] = ($subscriberinfo['confirmed']) ? GetLang('Confirmed') : GetLang('Unconfirmed');
				break;

				case 'dmy':
					$parts[] = AdjustTime($subscriberinfo['subscribedate'], false, 'd/m/Y');
				break;

				case 'mdy':
					$parts[] = AdjustTime($subscriberinfo['subscribedate'], false, 'm/d/Y');
				break;

				case 'ymd':
					$parts[] = AdjustTime($subscriberinfo['subscribedate'], false, 'Y/m/d');
				break;

				case 'i':
					// if they have a confirm ip, we'll use that.
					$ip = $subscriberinfo['confirmip'];

					// if they don't have a confirm ip, check for the request ip.
					if (!$ip) {
						$ip = $subscriberinfo['requestip'];
					}

					// if they still don't have an ip then chuck in a message.
					if (!$ip) {
						$ip = GetLang('SubscriberIP_Unknown');
					}
					$parts[] = $ip;
				break;

				case 'btime':
					$parts[] = AdjustTime($bounce_info['bouncetime'], false, GetLang('BounceTimeFormat'));
				break;

				case 'btype':
					$parts[] = sprintf(GetLang('BounceTypeFormat'), GetLang('Bounce_Rule_' . $bounce_info['bouncerule']), $bounce_info['bouncetype']);
				break;

				default:
					if (is_numeric($type)) {
						$customfield_data = $subscriberApi->GetCustomFieldSettings($type, true);

						if ($customfield_data !== false) {
							/**
							* See if we have loaded this custom field yet or not.
							* If we haven't then load it up.
							*
							* If we have, then use that instead for the checkdata calls.
							*
							* Doing it this way saves a lot of db queries/overhead especially with lots of custom fields.
							*/
							$fieldid = $customfield_data['fieldid'];

							if (!in_array($fieldid, array_keys($this->_customfields_loaded))) {
								$field_options = $CustomFieldApi->Load($fieldid, false, true);
								$subfield = $CustomFieldApi->LoadSubField($field_options);
								$this->_customfields_loaded[$fieldid] = $subfield;
							}

							$subf = $this->_customfields_loaded[$fieldid];
                            if(is_object($subf)){$customfield_data['data'] = $subf->GetRealValue($customfield_data['data']);}

						}

						if (!isset($customfield_data['data'])) {
							$parts[] = '';
							continue;
						}

						$customfield_data = $customfield_data['data'];

						if (!is_array($customfield_data)) {
							if (substr_count($customfield_data, $exportsettings['FieldSeparator']) > 0) {
								if ($exportsettings['FieldEnclosedBy'] == '') {
									$customfield_data = '"' . $customfield_data . '"';
								}
							}

							$parts[] = $customfield_data;
							continue;
						}

						if ($exportsettings['FieldEnclosedBy'] != '') {
							$customfield_sanitized = implode(',', $customfield_data);
						} else {
							if (sizeof($customfield_data) > 1) {
								$customfield_sanitized = '"' . implode(',', $customfield_data) . '"';
							} else {
								$customfield_sanitized = implode(',', $customfield_data);

								if (substr_count($customfield_sanitized, $exportsettings['FieldSeparator']) > 0) {
									$customfield_sanitized = '"' . $customfield_sanitized . '"';
								}
							}
						}
						$parts[] = $customfield_sanitized;
					}
				break;
			}
		}


		switch($exportsettings['FileType']) {
			case 'xml':
				$line = '';

				if($first) {
					$line = "\t".'<records>'."\n";
				}

				$line .= "\t\t".'<record>'."\n";
				foreach($parts as $index => $part) {
					$line .= "\t\t\t".'<field id="'.$index.'">'.htmlspecialchars($part, ENT_QUOTES, SENDSTUDIO_CHARSET).'</field>'."\n";
				}
				$line .= "\t\t".'</record>'."\n";

				if($last) {
					$line .= "\t".'</records>' . "\n" . '</export>' . "\n";
				}
			break;

			case 'csv':
			default:
				if ($exportsettings['FieldEnclosedBy'] != '') {
					$line = '';
					foreach ($parts as $p => $part) {
						// To escape a field enclosure inside a field we double it up
						$part = str_replace($exportsettings['FieldEnclosedBy'], $exportsettings['FieldEnclosedBy'].$exportsettings['FieldEnclosedBy'], $part);
						$line .= $exportsettings['FieldEnclosedBy'] . $part . $exportsettings['FieldEnclosedBy'] . $exportsettings['FieldSeparator'];
					}
					$line = substr($line, 0, -1);
				} else {
					$line = implode($exportsettings['FieldSeparator'], $parts);
				}

				$line .= "\n";
			break;
		}

		$fp = fopen(TEMP_DIRECTORY . '/' . $exportinfo['ExportFile'], 'a');
		fputs($fp, $line, strlen($line));
		fclose($fp);
	}

}
