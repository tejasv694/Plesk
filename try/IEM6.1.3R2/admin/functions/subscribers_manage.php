<?php
/**
* This file manages subscribers. It prints out the forms, lets you perform mass-actions and lets you delete subscribers. It also handles paging and so on.
*
* @version     $Id: subscribers_manage.php,v 1.52 2008/01/11 07:07:20 chris Exp $
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
* Class for managing subscribers. This only handles subscriber management and mass-actions (eg changing formats, bulk deletion etc). It handles paging, processing, sorting and that's it.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Subscribers_Manage extends Subscribers
{

	/**
	* ValidSorts
	* An array of valid sort criteria.
	*
	* @var array
	*/
	var $ValidSorts = array('emailaddress', 'format', 'subscribedate', 'confirmed', 'listname','status');

	/**
	* _DefaultDirection
	* Set the default sort direction to ascending.
	*
	* @var String
	*/
	var $_DefaultDirection = 'asc';

	/**
	* Process
	* Works out what you're trying to do and takes appropriate action.
	* Checks to make sure you have access to manage subscribers before anything else.
	*
	* @param String $action Action to perform. This is usually 'step1', 'step2', 'step3' etc. This gets passed in by the Subscribers::Process function.
	*
	* @see Subscribers::Process
	* @see GetUser
	* @see User_API::HasAccess
	* @see ChooseList
	* @see DeleteSubscribers
	* @see ChangeFormat
	* @see ManageSubscribers_Step2
	* @see ManageSubscribers_Step3
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function Process($action=null)
	{
		$user = GetUser();
		$subscribersapi = $this->GetApi('subscribers');

		$this->PrintHeader(false, false, false);

		if (!is_null($action)) {
			$action = strtolower($action);
		}

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = 'step3';
		}

		switch ($action) {
			case 'change':
				$subaction = strtolower($_POST['ChangeType']);
				$subscriberlist = $_POST['subscribers'];

				if (!$subscribersapi->CheckPermission($user->userid, $subscriberlist)) {
					$this->DenyAccess();
					return;
				}

				switch ($subaction) {
					case 'delete':
						$access = $user->HasAccess('Subscribers', 'Delete');
						if ($access) {
							$this->DeleteSubscribers($subscriberlist);
						} else {
							$this->DenyAccess();
						}
					break;

					case 'changeformat_text':
						$this->ChangeFormat('Text', $subscriberlist);
					break;
					case 'changeformat_html':
						$this->ChangeFormat('HTML', $subscriberlist);
					break;
					case 'changestatus_confirm':
						$this->ChangeConfirm('Confirm', $subscriberlist);
					break;
					case 'changestatus_unconfirm':
						$this->ChangeConfirm('Unconfirm', $subscriberlist);
					break;
				}
				$this->ManageSubscribers_Step3(true);

			break;

			case 'delete':
				$access = $user->HasAccess('Subscribers', 'Delete');
				if ($access) {
					$subscriberids = array();
					if (isset($_GET['id'])) {
						$subscriberids[] = $_GET['id'];
					}

					$adminAccess = false;

					// If this user is an admin/list admin/list admintype == a then give permission
					if ($user->Admin() || $user->ListAdminType() == 'a' || $user->ListAdmin()) {
						$adminAccess = true;
					}

					if (!$subscribersapi->CheckPermission($user->userid, $subscriberids)) {
						$this->DenyAccess();
						return;
					}

					$this->DeleteSubscribers($subscriberids);
					$this->ManageSubscribers_Step3(true);
				} else {
					$this->DenyAccess();
				}
			break;

			case 'step3':
				if (isset($_POST['ShowFilteringOptions'])) {
					$show_filtering_options = $_POST['ShowFilteringOptions'];
					$user->SetSettings('ShowFilteringOptions', $show_filtering_options);
				}

				$this->ManageSubscribers_Step3();
			break;

			case 'step2':
				IEM::sessionset('visiblefields','');

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

				$this->ManageSubscribers_Step2($listid);
			break;

			case 'advancedsearch':
				IEM::sessionset('visiblefields','');
				$this->ChooseList('Manage', 'Step2');
			break;

			case 'simplesearch':
			default:
				IEM::sessionset('visiblefields','');
				$this->ManageSubscribers_Step3();
			break;
		}
	}

	/**
	* DeleteSubscribers
	* Deletes subscribers from the list. Goes through the subscribers array (passed in) and deletes them from the list as appropriate.
	*
	* @param Array $subscribers A list of subscriber id's to remove from the list.
	*
	* @see GetApi
	* @see Subscribers_API::DeleteSubscriber
	*
	* @return Void Doesn't return anything. Creates a report and prints that out.
	*/
	function DeleteSubscribers($subscribers=array())
	{
		if (!is_array($subscribers)) {
			$subscribers = array($subscribers);
		}

		if (empty($subscribers)) {
			return array(false, GetLang('NoSubscribersToDelete'));
		}
		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		// ----- get jobs running for this user
		$listid = 0;
		if (isset($_POST['lists'])) {
			$listid = $_POST['lists'];
		} elseif (isset($_GET['Lists'])) {
			$listid = $_GET['Lists'];
		} elseif (isset($_POST['list'])) {
			$listid = $_POST['list'];
		} elseif (isset($_GET['List'])) {
			$listid = $_GET['List'];
		}
		if(is_array($listid) && $listid[0] == 'any'){
			$listid = array();
		} else {
			$listid = array(0 => (int) $listid);
		}
		$db = IEM::getDatabase();
		// don't have a specific list? use the subscribers' listid
		if(empty($listid)){
			$query = "SELECT listid FROM [|PREFIX|]list_subscribers WHERE subscriberid IN (".implode(",",$subscribers).")";
			$result = $db->Query($query);
			if(!$result){
				trigger_error(mysql_error()."<br />".$query);
				FlashMessage(mysql_error(), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
				exit();
			}
			while($row = $db->Fetch($result)){
				$listid[] = $row['listid'];
			}
		}
		
		$jobs_to_check = array();
		
		if(!empty($listid)){
			$query = "SELECT jobid FROM [|PREFIX|]jobs_lists WHERE listid IN (".implode(",",$listid).")";
			$result = $db->Query($query);
			if(!$result){
				trigger_error(mysql_error()."<br />".$query);
				FlashMessage(mysql_error(), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
				exit();
			}
			while($row = $db->Fetch($result)){
				$jobs_to_check[] = $row['jobid'];
			}
			$db->FreeResult($result);
		}
		
		if(!empty($jobs_to_check)){
			$query = "SELECT jobstatus FROM [|PREFIX|]jobs WHERE jobid IN (" . implode(',', $jobs_to_check) . ")";	
			$result = $db->Query($query);
			if(!$result){
				trigger_error(mysql_error()."<br />".$query);
				FlashMessage(mysql_error(), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
				exit();
			}
			while($row = $db->Fetch($result)){
				if($row['jobstatus'] != 'c'){
					FlashMessage('Unable to delete contacts from list(s). Please cancel any campaigns sending to the list(s) in order to delete them.', SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
					exit();
				}
			}
			$db->FreeResult($result);
		}
		// -----


		$subscriber_search = IEM::sessionGet('Search_Subscribers');
		$list = $subscriber_search['List'];

		$subscribersdeleted = 0;
		$subscribersnotdeleted = 0;
		$SubscriberApi = $this->GetApi('Subscribers');
		foreach ($subscribers as $p => $subscriberid) {
			list($status, $msg) = $SubscriberApi->DeleteSubscriber(false, 0, $subscriberid);
			if ($status) {
				$subscribersdeleted++;
				continue;
			}
			$subscribersnotdeleted++;
		}

		$msg = '';

		if ($subscribersnotdeleted > 0) {
			if ($subscribersnotdeleted == 1) {
				$GLOBALS['Error'] = GetLang('Subscriber_NotDeleted');
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Subscribers_NotDeleted'), $this->FormatNumber($subscribersnotdeleted));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($subscribersdeleted > 0) {
			if ($subscribersdeleted == 1) {
				$msg .= $this->PrintSuccess('Subscriber_Deleted');
			} else {
				$msg .= $this->PrintSuccess('Subscribers_Deleted', $this->FormatNumber($subscribersdeleted));
			}
		}
		$GLOBALS['Message'] .= $msg;
	}

	/**
	* ChangeFormat
	* Changes subscriber formats to the one chosen for the particular list.
	*
	* @param String $format The format to change the subscribers to.
	* @param Array $subscribers A list of subscriber id's to change for the list.
	* @param Int $listid Listid to change subscribers for.
	*
	* @see GetApi
	* @see Subscribers_API::ChangeSubscriberFormat
	*
	* @return Void Doesn't return anything. Creates a report and prints that out.
	*/
	function ChangeFormat($format='html', $subscribers=array(), $listid=0)
	{
		$format = strtolower($format);
		if (!is_array($subscribers)) {
			$subscribers = array($subscribers);
		}

		if (empty($subscribers)) {
			return array(false, GetLang('NoSubscribersToChangeFormat'));
		}

		$subscriberschanged = 0;
		$subscribersnotchanged = 0;
		$SubscriberApi = $this->GetApi('Subscribers');
		foreach ($subscribers as $p => $subscriberid) {
			list($status, $msg) = $SubscriberApi->ChangeSubscriberFormat($format, $subscriberid);
			if ($status) {
				$subscriberschanged++;
				continue;
			}
			$subscribersnotchanged++;
		}

		$msg = '';

		$format_lang = ($format == 'text') ? 'Format_Text' : 'Format_HTML';

		if ($subscribersnotchanged > 0) {
			if ($subscribersnotchanged == 1) {
				$GLOBALS['Error'] = sprintf(GetLang('Subscriber_NotChangedFormat'), strtolower(GetLang($format_lang)));
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Subscribers_NotChangedFormat'), $this->FormatNumber($subscribersnotchanged), strtolower(GetLang($format_lang)));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($subscriberschanged > 0) {
			if ($subscriberschanged == 1) {
				$msg .= $this->PrintSuccess('Subscriber_ChangedFormat', strtolower(GetLang($format_lang)));
			} else {
				$msg .= $this->PrintSuccess('Subscribers_ChangedFormat', $this->FormatNumber($subscriberschanged), strtolower(GetLang($format_lang)));
			}
		}
		$GLOBALS['Message'] = $msg;
	}

	/**
	* ChangeStatus
	* Changes subscriber status to the one chosen for the particular list.
	*
	* @param String $newstatus The status to change the subscribers to.
	* @param Array $subscribers A list of subscriber id's to change for the list.
	*
	* @see GetApi
	* @see Subscribers_API::ChangeSubscriberStatus
	*
	* @return Void Doesn't return anything. Creates a report and prints that out.
	*/
	function ChangeStatus($newstatus='active', $subscribers=array())
	{
		$newstatus = strtolower($newstatus);
		if (!is_array($subscribers)) {
			$subscribers = array($subscribers);
		}

		if (empty($subscribers)) {
			return array(false, GetLang('NoSubscribersToChangeStatus'));
		}

		$subscriberschanged = 0;
		$subscribersnotchanged = 0;
		$SubscriberApi = $this->GetApi('Subscribers');
		foreach ($subscribers as $p => $subscriberid) {
			list($status, $msg) = $SubscriberApi->ChangeSubscriberStatus($newstatus, 0, $subscriberid);
			if ($status) {
				$subscriberschanged++;
				continue;
			}
			$subscribersnotchanged++;
		}

		$msg = '';

		$status_lang = ($newstatus == 'active') ? 'Status_Active' : 'Status_Inactive';

		if ($subscribersnotchanged > 0) {
			if ($subscribersnotchanged == 1) {
				$GLOBALS['Error'] = sprintf(GetLang('Subscriber_NotChangedStatus'), strtolower(GetLang($status_lang)));
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Subscribers_NotChangedStatus'), $this->FormatNumber($subscribersnotchanged), strtolower(GetLang($status_lang)));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($subscriberschanged > 0) {
			if ($subscriberschanged == 1) {
				$msg .= $this->PrintSuccess('Subscriber_ChangedStatus', strtolower(GetLang($status_lang)));
			} else {
				$msg .= $this->PrintSuccess('Subscribers_ChangedStatus', $this->FormatNumber($subscriberschanged), strtolower(GetLang($status_lang)));
			}
		}
		$GLOBALS['Message'] = $msg;
	}

	/**
	* ChangeConfirm
	* Changes subscriber confirmation status to the one chosen for the particular list.
	*
	* @param String $confirmstatus The status to change the subscribers to.
	* @param Array $subscribers A list of subscriber id's to change for the list.
	*
	* @see GetApi
	* @see Subscribers_API::ChangeSubscriberConfirm
	*
	* @return Void Doesn't return anything. Creates a report and prints that out.
	*/
	function ChangeConfirm($confirmstatus='confirm', $subscribers=array())
	{
		$confirmstatus = strtolower($confirmstatus);
		if (!is_array($subscribers)) {
			$subscribers = array($subscribers);
		}

		if (empty($subscribers)) {
			return array(false, GetLang('NoSubscribersToChangeConfirm'));
		}

		$subscriberschanged = 0;
		$subscribersnotchanged = 0;
		$SubscriberApi = $this->GetApi('Subscribers');
		foreach ($subscribers as $p => $subscriberid) {
			list($status, $msg) = $SubscriberApi->ChangeSubscriberConfirm($confirmstatus, 0, $subscriberid);
			if ($status) {
				$subscriberschanged++;
				continue;
			}
			$subscribersnotchanged++;
		}

		$msg = '';

		$status_lang = ($confirmstatus == 'confirm') ? 'Status_Confirmed' : 'Status_Unconfirmed';

		if ($subscribersnotchanged > 0) {
			if ($subscribersnotchanged == 1) {
				$GLOBALS['Error'] = sprintf(GetLang('Subscriber_NotChangedConfirm'), strtolower(GetLang($status_lang)));
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Subscribers_NotChangedConfirm'), $this->FormatNumber($subscribersnotchanged), strtolower(GetLang($status_lang)));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($subscriberschanged > 0) {
			if ($subscriberschanged == 1) {
				$msg .= $this->PrintSuccess('Subscriber_ChangedConfirm', strtolower(GetLang($status_lang)));
			} else {
				$msg .= $this->PrintSuccess('Subscribers_ChangedConfirm', $this->FormatNumber($subscriberschanged), strtolower(GetLang($status_lang)));
			}
		}
		$GLOBALS['Message'] = $msg;
	}

	/**
	* ManageSubscribers_Step3
	* Prints out the subscribers for the list chosen and criteria selected in steps 1 & 2. This handles sorting, paging and searching. If you are coming in for the first time, it remembers your search criteria in the session. If you change number per page, sorting criteria, it fetches the search criteria from the session again before continuing.
	*
	* @see ManageSubscribers_Step2
	* @see GetApi
	* @see GetPerPage
	* @see GetCurrentPage
	* @see GetSortDetails
	* @see Subscribers_API::FetchSubscribers
	* @see SetupPaging
	* @see Lists_API::Load
	*
	* @return Void Doesn't return anything. Prints out the results and that's it.
	*/
	function ManageSubscribers_Step3($change=false)
	{
		$subscriber_api = $this->GetApi('Subscribers');
		$user = IEM::getCurrentUser();
		$search_info = IEM::sessionGet('Search_Subscribers');

		$this->SetupGoogleCalendar();

		$user_lists = $user->GetLists();

		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		// if we are posting a form, we are most likely resetting the search criteria.
		// we are also resetting the search criteria once we detect "Lists" variable in the GET Request
		$resetsearch = ((isset($_POST) && !empty($_POST)) || isset($_GET['Lists']) || isset($_GET['Segment'])) ? true : false;

		// except if we're changing paging!
		if (isset($_GET['SubAction'])) {
			$subaction =  strtolower($_GET['SubAction']);
			if ($subaction == 'processpaging' || $subaction == 'change') {
				$resetsearch = false;
			}
		}

		if (!$search_info || $resetsearch) {
			$this->SetCurrentPage(1); // forget current page
			$search_details = array();
			if (isset($_POST['emailaddress']) && $_POST['emailaddress'] != '') {
				$search_details['Email'] = trim($_POST['emailaddress']);
			}

			if (isset($_POST['format']) && $_POST['format'] != '-1') {
				$search_details['Format'] = $_POST['format'];
			}

			if (isset($_POST['confirmed']) && $_POST['confirmed'] != '-1') {
				$search_details['Confirmed'] = $_POST['confirmed'];
			}

			if (isset($_POST['status']) && $_POST['status'] != '-1') {
				$search_details['Status'] = $_POST['status'];
			}

			if (isset($_POST['datesearch']) && isset($_POST['datesearch']['filter'])) {
				$search_details['DateSearch'] = $_POST['datesearch'];

				$search_details['DateSearch']['StartDate'] = AdjustTime(array(0, 0, 1, $_POST['datesearch']['mm_start'], $_POST['datesearch']['dd_start'], $_POST['datesearch']['yy_start']));

				$search_details['DateSearch']['EndDate'] = AdjustTime(array(0, 0, 1, $_POST['datesearch']['mm_end'], $_POST['datesearch']['dd_end'], $_POST['datesearch']['yy_end']));
			}

			$customfields = array();
			if (isset($_POST['CustomFields']) && !empty($_POST['CustomFields'])) {
				$customfields = $_POST['CustomFields'];
			}

			$search_details['CustomFields'] = $customfields;

			if (isset($_GET['Lists']) || isset($_GET['List'])) {
				$search_details['List'] = isset($_GET['Lists'])? $_GET['Lists'] : $_GET['List'];
			} else {
				$search_details['List'] = 'any';
			}

			// Get segment, and make sure user have access permission to it
			if ($user->HasAccess('Segments')) {
				$search_details['Segment'] = null;
				if (isset($_GET['Segment'])) {
					$tempSegmentList = array_keys($user->GetSegmentList());
					$tempSegment = $_GET['Segment'];

					// Make sure that selected segment is allowed for user
					if (!is_array($tempSegment)) {
						if (!in_array($tempSegment, $tempSegmentList)) {
							$tempSegment = null;
						}
					} else {
						$tempSegment = array_intersect($tempSegment, $tempSegmentList);
					}

					if (!is_null($tempSegment)) {
						$search_details['Segment'] = $tempSegment;
					}
				}
			}

			if (is_array($search_details['List'])) {
				// Make sure that "any" is not selected when you are selecting multiple list
				if (count($search_details['List']) > 1) {
					if (($index = array_search('any', $search_details['List'])) !== false) {
						unset($search_details['List'][$index]);
					}
				}

				// If the array only contain 1 id, make take it out of the array
				if (count($search_details['List']) == 1) {
					$search_details['List'] = array_pop($search_details['List']);
				}
			}

			// Get allowable list
			if (!$user->ListAdmin()) {
				$search_details['AvailableLists'] = array_keys($user_lists);
			}

			if (is_array($search_details['List'])) {
				// Make sure IDs are numerics
				if (is_array($search_details['List'])) {
					$listIDs = array();
					foreach ($search_details['List'] as $id) {
						array_push($listIDs, intval($id));
					}
					$search_details['List'] = $listIDs;
				}
			}

			// Make sure that user can only select newsletter from his/her allowable list
			if (isset($search_details['AvailableLists']) && (is_numeric($search_details['List']) || is_array($search_details['List']))) {
				if (is_array($search_details['List'])) {
					$search_details['List'] = array_intersect($search_details['List'], $search_details['AvailableLists']);
				} else {
					$temp = in_array($search_details['List'], $search_details['AvailableLists']);
					if (!$temp) {
						$search_details['List'] = null;
					}
				}

				if (empty($search_details['List'])) {
					$search_details['List'] = $search_details['AvailableLists'];
				}

				// Make sure to unset available list, otherwise Subscribers API will think
				// we are looking to query all list
				unset($search_details['AvailableLists']);
			}

			if (isset($_POST['clickedlink']) && isset($_POST['linkid'])) {
				$search_details['LinkType'] = 'clicked';
				if (isset($_POST['linktype']) && $_POST['linktype'] == 'not_clicked') {
					$search_details['LinkType'] = 'not_clicked';
				}

				$search_details['Link'] = $_POST['linkid'];
			}

			if (isset($_POST['openednewsletter']) && isset($_POST['newsletterid'])) {
				$search_details['OpenType'] = 'opened';
				if (isset($_POST['opentype']) && $_POST['opentype'] == 'not_opened') {
					$search_details['OpenType'] = 'not_opened';
				}

				$search_details['Newsletter'] = $_POST['newsletterid'];
			}

			if (isset($_POST['Search_Options'])) {
				$search_details['Search_Options'] = $_POST['Search_Options'];
			}

			// Flag to differentiate where the search details are coming from
			$GLOBALS['Search'] = '';
			if (!empty($_POST) || !empty($search_details['Email'])) {
				$search_details['Source'] = 'search';
				$GLOBALS['Search'] = isset($search_details['Email'])? $search_details['Email'] : '';
			} else {
				if (!empty($search_details['Segment'])) {
					$search_details['Source'] = 'segment';
				} else {
					$search_details['Source'] = 'list';
				}
			}

			IEM::sessionSet('Search_Subscribers', $search_details);
		}

		$search_info = IEM::sessionGet('Search_Subscribers');

		// Process segmenting information
		if (!empty($search_info['Segment'])) {
			$segmentAPI = $this->GetApi('Segment');

			if (is_array($search_info['Segment'])) {
				$search_info['List'] = $segmentAPI->GetMailingListUsed($search_info['Segment']);
			} else {
				$segmentAPI->Load($search_info['Segment']);
				$search_info['List'] = $segmentAPI->GetMailingListUsed();
			}

			$subscriber_header_template = 'Subscribers_Manage_AnyList';
			$subscriber_row_template = 'Subscribers_Manage_AnyList_Row';

			$GLOBALS['Segment'] = is_array($search_info['Segment'])? implode('&Segment[]=', $search_info['Segment']) : $search_info['Segment'];
		}

		$GLOBALS['List'] = is_array($search_info['List'])? implode('&Lists[]=', $search_info['List']) : $search_info['List'];

		// Load visible fields for each list
		if (isset($_POST['VisibleFields'])) {
			IEM::sessionSet('visiblefields', $_POST['VisibleFields']);
			$visiblefields_set = $_POST['VisibleFields'];
		} elseif (IEM::sessionGet('visiblefields')) {
			$visiblefields_set = IEM::sessionGet('visiblefields');
		} else {
			list(,$visiblefields_set) = $this->GetVisibleFields($search_info['List']);
		}

		$perpage = $this->GetPerPage();
		$pageid = $this->GetCurrentPage();

		$sortinfo = $this->GetSortDetails();

		// Check if we are sorting by a custom field
		if (is_numeric($sortinfo['SortBy'])) {
			if (in_array($sortinfo['SortBy'], $visiblefields_set)) {
				$sortinfo['CustomFields'] = array($sortinfo['SortBy']);
				$sortinfo['SortBy'] = 'sd.data';
			} else {
				$sortinfo['SortBy'] = 'emailaddress';
			}
		}

		if (!empty($search_info['Segment'])) {
			$tempEmail = null;
			if (!empty($search_details['Email'])) {
				$tempEmail = $search_details['Email'];
			}

			$subscriber_list = $subscriber_api->FetchSubscribersFromSegment($pageid, $perpage, $search_info['Segment'], $sortinfo, $tempEmail);
		} else {
			$subscriber_list = $subscriber_api->FetchSubscribers($pageid, $perpage, $search_info, $sortinfo);
		}

		$subscriber_edited = (isset($_GET['Edit'])) ? true : false;

		$totalsubscribers = $subscriber_list['count'];
		unset($subscriber_list['count']);

		if ($subscriber_edited) {
			$GLOBALS['Message'] .= $this->PrintSuccess('SubscriberEditSuccess');
		}

		$GLOBALS['TotalSubscriberCount'] = $this->FormatNumber($totalsubscribers);

		$tempMessageStringSubfix = '';
		switch ($search_info['Source']) {
			case 'list':
				if ($search_info['List'] == 'any') {
					$tempMessageStringSubfix = 'AllList_';
				} elseif (is_array($search_info['List'])) {
					$tempMessageStringSubfix = 'ManyList_';
				} else {
					$tempMessageStringSubfix = 'OneList_';
				}
			break;

			case 'segment':
				$tempMessageStringSubfix = 'Segment_';
			break;
		}


		$DisplayPage = $pageid;

		$GLOBALS['PAGE'] = 'Subscribers&Action=Manage&SubAction=Step3';

		// set up paging before we add the Lists[]= part, as we never want paging links to reset a search
		$this->SetupPaging($totalsubscribers, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'SubAction=ProcessPaging';
		$paging = $this->ParseTemplate('Paging', true, false);

		if (!empty($search_info['Segment'])) {
			$GLOBALS['PAGE'] .= '&Segment[]=' . $GLOBALS['Segment'];
		} else {
			$GLOBALS['PAGE'] .= '&Lists[]=' . $GLOBALS['List'];
		}

		$subscriberdetails = '';

		// If no visible fields are selected, make emailaddress visible
		if (count($visiblefields_set) == 0) {
			array_unshift($visiblefields_set,'emailaddress');
		}

		// Make "View" PopUp menu
		$GLOBALS['SubscriberViewPickerMenu'] = $this->MakeViewPopupMenu($search_info, $user);

		$loaded_customfields = array();

		$customfields = array();

		$visiblefields = array();
		$visiblefields_lists = array();

		$subscriber_header_template = 'Subscribers_Manage_AnyList';
		$subscriber_row_template = 'Subscribers_Manage_AnyList_Row';

		if (!$user->HasAccess('Subscribers', 'Add')) {
			$GLOBALS['AddButtonDisplay'] = 'none';
		}

		if (!empty($search_info['Segment'])) {
			$segmentAPI = $this->GetApi('Segment');
			$tempSegmentID = $search_info['Segment'];
			$usedLists = array();

			if (!is_array($tempSegmentID)) {
				$tempSegmentID = array($tempSegmentID);
			}

			foreach ($tempSegmentID as $id) {
				$segmentAPI->Load($id);
				$tempList = $segmentAPI->GetMailingListUsed();

				$usedLists = array_merge($usedLists, $tempList);
			}

			$search_info['List'] = $usedLists;

			/**
			 * Segments contain lists (as they can go across multiple lists)
			 */
			$listids = $search_info['List'];

			if ($search_info['Source'] == 'search' || is_array($search_info['Segment'])) {
				$title = GetLang('SubscribersManageSearchResult');
			} else {
				$title = sprintf(GetLang('SubscribersManageSegment'), htmlspecialchars($segmentAPI->segmentname, ENT_QUOTES, SENDSTUDIO_CHARSET));
			}

			$GLOBALS['AddButtonURL'] = 'index.php?Page=Subscribers&Action=Add';
		} else {
			/**
			 * Only viewing one list here.
			 */
			if (is_numeric($search_info['List'])) {
				$listids = array($search_info['List']);
				$subscriber_header_template = 'Subscribers_Manage';
				$subscriber_row_template = 'Subscribers_Manage_Row';

				$GLOBALS['ColumnCount'] = 3;

				if ($search_info['Source'] == 'search') {
					$title = GetLang('SubscribersManageSearchResult');
				} else {
					$listname = $user_lists[$search_info['List']]['name'];
					$title = sprintf(GetLang('SubscribersManageSingleList'), htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));
				}

				$GLOBALS['AddButtonURL'] = 'index.php?Page=Subscribers&Action=Add&SubAction=Step2&list=' . $search_info['List'];
			} else {
				/**
				 * If we're viewing more than one list, use those id's.
				 */
				if (is_array($search_info['List'])) {
					$listids = $search_info['List'];
					$title = GetLang('SubscribersManageMultipleList');
				} else {
					/**
					 * The default is all of the users lists.
					*/
					$listids = array_keys($user_lists);
					$title = GetLang('SubscribersManageAnyList');
				}

				/**
				 * Override the title if we're coming from a search result.
				 */
				if ($search_info['Source'] == 'search') {
					$title = GetLang('SubscribersManageSearchResult');
				}

				$GLOBALS['AddButtonURL'] = 'index.php?Page=Subscribers&Action=Add';
				$GLOBALS['ColumnCount'] = 4;
			}
		}

		$GLOBALS['SubscribersManage'] = $title;

		// Log this to "User Activity Log" except when is deleting.
		if (!(isset($_GET['SubAction']) && strtolower($_GET['SubAction']) != "delete")) {
			IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/lists_view.gif', $title);
		}

		if ($totalsubscribers < 1) {
			IEM::sessionRemove('Search_Subscribers');
			if ($subscriber_edited) {
				$GLOBALS['Message'] .= $this->PrintSuccess('SubscriberEditSuccess');
			} else {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoSubscribersMatch', true);
			}
			$this->ParseTemplate('Subscribers_Manage_Empty');
			return;
		}

		if ($totalsubscribers == 1) {
			$GLOBALS['Message'] .= $this->PrintSuccess('Subscribers_' . $tempMessageStringSubfix . 'FoundOne');
		} else {
			$GLOBALS['Message'] .= $this->PrintSuccess('Subscribers_' . $tempMessageStringSubfix . 'FoundMany', $GLOBALS['TotalSubscriberCount']);
		}

		$CustomFieldsApi = $this->GetApi('CustomFields');
		$customfields_for_all_lists = $CustomFieldsApi->GetCustomFieldsForLists($listids, $visiblefields_set);
		$listNames = array();

		foreach ($listids as $listid) {
			array_push($listNames, $user_lists[$listid]['name']);

			foreach ($this->BuiltinFields as $key => $name) {
				if (in_array($key,$visiblefields_set) && !in_array($key,$visiblefields)) {
					if (!isset($visiblefields_lists[$key])) {
						$visiblefields_lists[$key] = array();
					}
					$visiblefields_lists[$key][] = (int)$listid;

					$visiblefields[] = $key;
				}
			}

			foreach ($customfields_for_all_lists as $key => $details) {
				if (in_array($details['fieldid'],$visiblefields_set)) {
					if (!isset($visiblefields_lists[$details['fieldid']])) {
						$visiblefields_lists[$details['fieldid']] = array();
					}
					$visiblefields_lists[$details['fieldid']][] = (int)$listid;

					if (!in_array($details['fieldid'],$visiblefields)) {
						$visiblefields[] = $details['fieldid'];
					}
				}
			}
		}

		$customfield_data = array();

		$GLOBALS['Columns'] = '';
		foreach ($visiblefields as $name) {
			if (!in_array($name,$visiblefields_set)) {
				continue;
			}

			if (is_numeric($name)) {
				$customfieldinfo = array();
				foreach ($customfields_for_all_lists as $pos => $details) {
					if ($details['fieldid'] === $name) {
						$customfieldinfo = $details;
						break;
					}
				}

				/**
				 * Check we got some data here.
				 * We may have just changed the lists we are viewing and the custom field isn't associated with this new list
				*/
				if (!empty($customfieldinfo)) {
					$GLOBALS['FieldName'] = htmlspecialchars($customfieldinfo['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);

					$subfield = $CustomFieldsApi->LoadSubField($customfieldinfo);

					$loaded_customfields[$name] = $subfield;

					$customfield_data[] = array (
						'fieldid' => $name,
						'fieldtype' => $subfield->fieldtype,
						'defaultvalue' => $subfield->GetDefaultValue(),
						'name' => $subfield->GetFieldName(),
					);
				}
			} elseif (in_array($name,array_keys($this->BuiltinFields))) {
				$GLOBALS['FieldName'] = GetLang($this->BuiltinFields[$name]);
			}

			if ($name == 'emailaddress') {
				$GLOBALS['Width'] = 'width="17%"';
			} else {
				$GLOBALS['Width'] = '';
			}

			$GLOBALS['SortName'] = htmlspecialchars($name, ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Columns'] .= $this->ParseTemplate('Subscribers_Manage_Column_Sortable',true,false);
		}

		if (isset($GLOBALS['Segment'])) {
			$GLOBALS['URLQueryString'] = '&Segment[]=' . $GLOBALS['Segment'];
		} else {
			$GLOBALS['URLQueryString'] = '&Lists[]=' . $GLOBALS['List'];
		}

		$actions = $user->GetEventActivityType();
		$GLOBALS['Actions'] = '';
		foreach ($actions as $action) {
			$GLOBALS['Actions'] .= '<option value="' . htmlspecialchars($action,ENT_QUOTES, SENDSTUDIO_CHARSET) . '">'. htmlspecialchars($action,ENT_QUOTES, SENDSTUDIO_CHARSET) . "</option>";
		}

		$GLOBALS['EventTypesJSON'] = GetJSON($actions);
		$GLOBALS['EventAddForm'] = $this->ParseTemplate('Subscriber_Event_Add',true,false);

		$GLOBALS['EventJavascript'] = $this->ParseTemplate('Subscribers_Events_Javascript',true,false);
		$GLOBALS['DatePickerJavascript'] = $this->ParseTemplate('ui.datepicker.custom_iem',true,false);


		$template = $this->ParseTemplate($subscriber_header_template, true, false);

		$GLOBALS['List'] = $search_info['List'];

		$subscriber_customfields = array();
		$customfield_ids = $visiblefields;
		$customfield_ids = $subscriber_api->CheckIntVars($customfield_ids);

		if (!empty($customfield_ids)) {
			$subids = array();
			foreach ($subscriber_list['subscriberlist'] as $info) {
				$subids[] = $info['subscriberid'];
			}
			$subscriber_customfields = $subscriber_api->GetAllSubscriberCustomFields($listids, array(), $subids, $customfield_ids);
			unset($subids);
		}

		if (!isset($GLOBALS['ColumnCount'])) {
			$GLOBALS['ColumnCount'] = 0;
		}
		$GLOBALS['ColumnCount'] += count($visiblefields);

		foreach ($subscriber_list['subscriberlist'] as $pos => $subscriberinfo) {
			$GLOBALS['Columns'] = '';
			$GLOBALS['FieldValue'] = '';
			$subscriberfields = array();
			foreach ($visiblefields as $fieldname) {
				switch ($fieldname) {
					case 'emailaddress':
						$GLOBALS['FieldValue'] = htmlspecialchars($subscriberinfo[$fieldname], ENT_QUOTES, SENDSTUDIO_CHARSET);
					break;
					case 'subscribedate':
						$GLOBALS['FieldValue'] = $this->PrintDate($subscriberinfo['subscribedate']);
					break;
					case 'format':
						$GLOBALS['FieldValue'] = ($subscriberinfo['format'] == 't') ? 	GetLang('Format_Text') : GetLang('Format_HTML');
					break;
					case 'confirmed':
						$GLOBALS['FieldValue'] = ($subscriberinfo['confirmed'] == '1') ? GetLang('Confirmed') : GetLang('Unconfirmed');
					break;
					case 'status':
						$status = GetLang('Active');

						if ($subscriberinfo['unsubscribed'] > 0) {
							$status = GetLang('Unsubscribed');
						}

						if ($subscriberinfo['bounced'] > 0) {
							$status = GetLang('Bounced');
						}

						$GLOBALS['FieldValue'] = $status;
					break;
					default:
						$GLOBALS['FieldValue'] = '&nbsp;';
						if (is_numeric($fieldname)) {
							$subfield = $loaded_customfields[$fieldname];
							$subid = $subscriberinfo['subscriberid'];

							/**
							* If there is no custom field for this subscriber, go to the next field.
							* This could happen if you view all lists but a field is only associated with one particular list
							*/
							if (!isset($subscriber_customfields[$subid])) {
								continue;
							}

							foreach ($subscriber_customfields[$subid] as $cf_p => $cf_details) {
								if ($cf_details['fieldid'] != $fieldname) {
									continue;
								}
								$GLOBALS['FieldValue'] = htmlspecialchars($subfield->GetRealValue($cf_details['data'],','), ENT_QUOTES, SENDSTUDIO_CHARSET);
							}
						}
				}
				$GLOBALS['Columns'] .= $this->ParseTemplate('Subscribers_Manage_Row_Column',true,false);
			}
			// if we are searching "any" list then we need to adjust the link.
			if (isset($subscriberinfo['listid'])) {
				$GLOBALS['List'] = $subscriberinfo['listid'];
			}
			if (isset($subscriberinfo['listname'])) {
				$GLOBALS['MailingListName'] = htmlspecialchars($subscriberinfo['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}

			$GLOBALS['subscriberid'] = $subscriberinfo['subscriberid'];
			$GLOBALS['SubscriberID'] = $subscriberinfo['subscriberid'];
			$GLOBALS['EditSubscriberID'] = $subscriberinfo['subscriberid'];

			if (array_key_exists('Segment', $search_info) && $search_info['Segment'] != 0) {
				$GLOBALS['SegmentID'] = $search_info['Segment'];
				$GLOBALS['ExtraParameter'] = '&SegmentID=' . $search_info['Segment'];
			} else {
				$GLOBALS['SegmentID'] = 0;
				$GLOBALS['ExtraParameter'] = '';
			}

			$GLOBALS['SubscriberAction'] = $this->ParseTemplate('Subscribers_Manage_ViewLink', true, false);

			if ($user->HasAccess('Subscribers', 'Eventsave')) {
				$GLOBALS['SubscriberAction'] .= $this->ParseTemplate('Subscribers_Manage_EventAddLink', true, false);
			}

			if ($user->HasAccess('Subscribers', 'Edit')) {
				$GLOBALS['SubscriberAction'] .= $this->ParseTemplate('Subscribers_Manage_EditLink', true, false);
			}

			if ($user->HasAccess('Subscribers', 'Delete')) {
				$GLOBALS['DeleteSubscriberID'] = $subscriberinfo['subscriberid'];
				$GLOBALS['SubscriberAction'] .= $this->ParseTemplate('Subscribers_Manage_DeleteLink', true, false);
			}
			$events = $subscriber_api->CountEvents($subscriberinfo['subscriberid']);

			$GLOBALS['EventButton'] = '';
			if ($events) {
				$GLOBALS['EventButton'] = $this->ParseTemplate('Subscribers_Manage_Row_Eventbutton',true,false);
			}


			$subscriberdetails .= $this->ParseTemplate($subscriber_row_template, true, false);
		}

		$template = str_replace('%%TPL_' . $subscriber_row_template . '%%', $subscriberdetails, $template);
		$template = str_replace('%%TPL_Paging%%', $paging, $template);
		$template = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $template);

		echo $template;
	}

	/**
	* ManageSubscribers_Step2
	* Prints out the search forms to restrict the subscribers you want to see. This includes custom fields, format and so on.
	*
	* @param Int $listid Which list we are managing subscribers for.
	* @param Mixed $msg If there is a message (eg "no subscribers found"), it is passed in for display.
	*
	* @see GetApi
	* @see Lists_API::Load
	* @see Lists_API::GetListFormat
	* @see Lists_API::GetCustomFields
	* @see Search_Display_CustomField
	*
	* @return Void Doesn't return anything. Prints the search form and that's it.
	*/
	function ManageSubscribers_Step2($listid=0, $msg=false)
	{
		$user = GetUser();

		$user_lists = $user->GetLists();

		$access = $user->HasAccess('Subscribers', 'Manage');

		if (!$access) {
			$this->DenyAccess();
			return;
		}

		if ($msg) {
			$GLOBALS['Error'] = $msg;
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}

		$listApi = $this->GetApi('Lists');

		if (is_array($listid)) {
			// Make sure that "any" is not selected when you are selecting multiple list
			if (count($listid) > 1) {
				if (($index = array_search('any', $listid)) !== false) {
					unset($listid[$index]);
				}
			}

			// If the array only contain 1 id, make take it out of the array
			if (count($listid) == 1) {
				$listid = array_pop($listid);
			}

			// Make sure the IDs are numerics
			if (is_array($listid)) {
				$temp = array();
				foreach ($listid as $id) {
					array_push($temp, intval($id));
				}
				$listid = $temp;
			}
		}

		// Make sure that user can only select newsletter from his/her allowable list
		if (!$user->ListAdmin() && (is_numeric($listid) || is_array($listid))) {
			$allowableListIDs = array_keys($user_lists);
			if (is_array($listid)) {
				$listid = array_intersect($listid, $allowableListIDs);
			} else {
				$temp = in_array($listid, $allowableListIDs);
				if (!$temp) {
					$listid = null;
				}
			}

			if (empty($listid)) {
				if (!headers_sent()) {
					header('Location: index.php?Page=Subscribers&Action=Manage');
					exit;
				}
				?>
				<script>
					document.location.href = 'index.php?Page=Subscribers&Action=Manage';
				</script>
				<?php
				exit();
			}
		}

		$user = GetUser();
		$user_lists = $user->GetLists();

		if (is_numeric($listid)) {
			$listids = array($listid); // used to print visiblefields
			$listApi->Load($listid);
			$listname = $listApi->name;
			$GLOBALS['Heading'] = sprintf(GetLang('SubscribersManageSingleList'), htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));
			$GLOBALS['List'] = $listid;
			$GLOBALS['DoNotShowFilteringOptionLabel'] = GetLang('SubscribersDoNotShowFilteringOptionsExplainOne');
			$GLOBALS['ShowFilteringOptionLabel'] = GetLang('SubscribersShowFilteringOptionsExplainOne');
		} else {
			if (!is_array($listid)) {
				$listids = array_keys($user_lists);
			} else {
				$listids = $listid;
			}
		}

		list($listids,$visiblefields,$visiblefields_list) = $this->GetVisibleFields($listid);

		if (is_array($listid)) {
			// Load list name for each of the selected mailing list
			$listnames = array();
			foreach ($user_lists as $id => $list_details) {
				array_push($listnames, $list_details['name']);
			}

			$GLOBALS['Heading'] = sprintf(GetLang('SubscribersManageMultipleList'), htmlspecialchars("'".implode("', '", $listnames)."'", ENT_QUOTES, SENDSTUDIO_CHARSET));
			$GLOBALS['List'] = implode('&Lists[]=',$listid);
			$GLOBALS['DoNotShowFilteringOptionLabel'] = GetLang('SubscribersDoNotShowFilteringOptionsExplain');
			$GLOBALS['ShowFilteringOptionLabel'] = GetLang('SubscribersShowFilteringOptionsExplain');
		} else {
			$GLOBALS['Heading'] = GetLang('SubscribersManageAnyList');
			$GLOBALS['List'] = $listid;
			$GLOBALS['DoNotShowFilteringOptionLabel'] = GetLang('SubscribersDoNotShowFilteringOptionsExplain');
			$GLOBALS['ShowFilteringOptionLabel'] = GetLang('SubscribersShowFilteringOptionsExplain');
		}

		$GLOBALS['VisibleFields'] = '';
		$CustomFieldsApi = $this->GetApi('CustomFields');

		foreach ($this->BuiltinFields as $key => $name) {
			$GLOBALS['VisibleFields'] .= '<option value="' . $key . '"';

			if (in_array($key,$visiblefields)) {
				$GLOBALS['VisibleFields'] .= ' selected="selected"';
			}

			$GLOBALS['VisibleFields'] .= '>' . htmlspecialchars(GetLang($name),ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		$fieldslisted = array();
		foreach ($listids as $listidTemp) {
			$customfields = $listApi->GetCustomFields($listidTemp);
			foreach ($customfields as $key => $details) {
				if (in_array($details['fieldid'],$fieldslisted)) {
					continue;
				}

				$GLOBALS['VisibleFields'] .= '<option value="' . $details['fieldid'] . '"';

				if (in_array($details['fieldid'],$visiblefields)) {
					$GLOBALS['VisibleFields'] .= ' selected="selected"';
				}

				$GLOBALS['VisibleFields'] .= '>' . htmlspecialchars($details['name'],ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';

				$fieldslisted[] = $details['fieldid'];
			}
		}

		$GLOBALS['VisibleFieldsInfo'] = $this->ParseTemplate('subscriber_manage_step2_visiblefields',true);

		$GLOBALS['FormAction'] = 'Manage';

		$format_either = '<option value="-1">' . GetLang('Either_Format') . '</option>';
		$format_html = '<option value="h">' . GetLang('Format_HTML') . '</option>';
		$format_text = '<option value="t">' . GetLang('Format_Text') . '</option>';

		if (is_numeric($listid)) {
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
		} else {
			$format = $format_either . $format_html . $format_text;
		}

		IEM::sessionRemove('LinksForList');
		if (is_numeric($listid)) {
			IEM::sessionSet('LinksForList', $listid);
		}

		$GLOBALS['ClickedLinkOptions'] = $this->ShowLinksClickedOptions();

		$GLOBALS['OpenedNewsletterOptions'] = $this->ShowOpenedNewsletterOptions();

		$GLOBALS['FormatList'] = $format;

		$this->PrintSubscribeDate();

		/**
		 * Print custom fields options if applicable
		 */
			if (is_numeric($listid)) {
				$customfields = $listApi->GetCustomFields($listid);

				if (!empty($customfields)) {
					$customfield_display = $this->ParseTemplate('Subscriber_Manage_Step2_CustomFields', true, false);
					foreach ($customfields as $pos => $customfield_info) {
						$manage_display = $this->Search_Display_CustomField($customfield_info);
						$customfield_display .= $manage_display;
					}
					$GLOBALS['CustomFieldInfo'] = $customfield_display;
				}
			}
		/**
		 * -----
		 */

		$this->ParseTemplate('Subscriber_Manage_Step2');

		if (sizeof(array_keys($user_lists)) == 1) {
			return;
		}

		if (isset($_GET['Reset'])) {
			return;
		}

		if (!$msg && (isset($_POST['ShowFilteringOptions']) && $_POST['ShowFilteringOptions'] == 2)) {
			?>
			<script>
				document.forms[0].submit();
			</script>
			<?php
			exit();
		}
	}

	/**
	* GetVisibleFields
	* Loads the visible fields for the given lists
	*
	* @param Mixed $listid Either an integer of a single list or an array of multiple lists, or 'all' for all lists
	*
	* @return Array Returns an array containing an array of lists, an array of visible fields for all lists passed in, and an array of fields and the lists they belong to
	*/
	function GetVisibleFields($listid)
	{
		$listApi = $this->GetApi('Lists');
		if (is_numeric($listid)) {
			$listids = array($listid); // used to print visiblefields
			$listApi->Load($listid);

			$visiblefields = explode(',',$listApi->visiblefields);
			$visiblefields_lists = array();

			foreach ($visiblefields as $name) {
				$visiblefields_lists[$name] = array((int)$listid);
			}
			return array($listids,$visiblefields,$visiblefields_lists);
		}

		/**
		 * If we're going to look at more than one list, then load all of the visible fields stuff from the db in one go.
		*/
		if (!is_array($listid)) {
			$user = GetUser();
			$lists = $user->GetLists();
			$listids = array_keys($lists);
		} else {
			$listids = $listid;
		}

		$visiblefields = array();
		$visiblefields_lists = array();

		$vis_fields = $listApi->LoadVisibleFieldSettings($listids);
		foreach ($vis_fields as $listid => $fields) {
			$visiblelistfields = explode(',',$fields);

			foreach ($visiblelistfields as $name) {
				if (!isset($visiblefields_lists[$name])) {
					$visiblefields_lists[$name] = array();
				}
				$visiblefields_lists[$name][] = $listid;

				if (!in_array($name,$visiblefields)) {
					$visiblefields[] = $name;
				}
			}
		}
		return array($listids,$visiblefields,$visiblefields_lists);
	}

	/**
	 * MakeViewPopupMenu
	 * Return "view" popup menus
	 *
	 * @param Array $search_info Search info
	 * @param User_API $user (REF) Current user record
	 * @return String Returns "View" popup menu HTML string
	 *
	 * @uses GetLang()
	 * @uses SendStudio_Functions::ParseTemplate()
	 */
	function MakeViewPopupMenu($search_info, &$user)
	{
		$tempCommonRows = array();
		$tempListRows = array();
		$tempSegmentRows = array();

		$tempSelectedListID = 0;
		$tempSelectedSegmentID = '-';
		$tempSelectedAllList = false;

		if (array_key_exists('List', $search_info)) {
			$tempSelectedListID = intval($search_info['List']);
		}

		if (array_key_exists('Segment', $search_info) && is_array($search_info['Segment'])) {
			$tempSelectedSegmentID = $search_info['Segment'];
		}

		$tempSelectedAllList = ($tempSelectedListID == 0 && $tempSelectedSegmentID == 0);

		/**
		 * List views
		 */
			if ($user->HasAccess('Lists') && !empty($search_info['List'])) {
				$tempListList = $user->GetLists();
				foreach ($tempListList as $tempListID => $tempListRecord) {
					$GLOBALS['RowAction'] = 'index.php?Page=Subscribers&Action=Manage&Lists[]=' . $tempListID;
					$GLOBALS['RowTitle'] = htmlspecialchars($tempListRecord['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
					$GLOBALS['RowCaption'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img border="0" src="images/nodejoinsmall.gif" />&nbsp;&nbsp;' . htmlspecialchars($this->TruncateName($tempListRecord['name'], 55), ENT_QUOTES, SENDSTUDIO_CHARSET);

					if ($tempSelectedListID == $tempListID) {
						$GLOBALS['RowCaption'] = '<b>' . $GLOBALS['RowCaption'] . '</b>';
					}

					array_push($tempListRows, $this->ParseTemplate('Subscribers_Manage_ViewPicker_Row', true));
				}
				unset($tempListList);
			} else {
				$GLOBALS['DisplayStyleList'] = 'none';
			}
		/**
		 * -----
		 */

		/**
		 * Segment views
		 */
			if ($user->HasAccess('Segments') && !empty($search_info['Segment'])) {
				$tempSegmentList = $user->GetSegmentList();
				if (count($tempSegmentList) == 0) {
					$GLOBALS['SegmentDisplay'] = 'none';
				} else {
					$GLOBALS['SegmentDisplay'] = '';

					foreach ($tempSegmentList as $tempSegmentID => $tempSegmentRecord) {
						$GLOBALS['RowAction'] = 'index.php?Page=Subscribers&Action=Manage&Segment=' . $tempSegmentID;
						$GLOBALS['RowTitle'] = htmlspecialchars($tempSegmentRecord['segmentname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
						$GLOBALS['RowCaption'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img border="0" src="images/nodejoinsmall.gif" />&nbsp;&nbsp;' . htmlspecialchars($this->TruncateName($tempSegmentRecord['segmentname'], 55), ENT_QUOTES, SENDSTUDIO_CHARSET);

						if ($tempSelectedSegmentID == $tempSegmentID) {
							$GLOBALS['RowCaption'] = '<b>' . $GLOBALS['RowCaption'] . '</b>';
						}

						array_push($tempSegmentRows, $this->ParseTemplate('Subscribers_Manage_ViewPicker_Row', true));
					}
					unset($tempSegmentList);
				}
			} else {
				$GLOBALS['DisplayStyleSegment'] = 'none';
			}
		/**
		 * -----
		 */

		unset($GLOBALS['RowCaption']);
		unset($GLOBALS['RowTitle']);
		unset($GLOBALS['RowAction']);

		$GLOBALS['CommonViews'] = implode('', $tempCommonRows);
		$GLOBALS['ListViews'] = implode('', $tempListRows);
		$GLOBALS['SegmentViews'] = implode('', $tempSegmentRows);

		$output = $this->ParseTemplate('Subscribers_Manage_ViewPicker', true);

		unset($GLOBALS['SegmentViews']);
		unset($GLOBALS['ListViews']);
		unset($GLOBALS['CommonViews']);

		return $output;
	}
}
