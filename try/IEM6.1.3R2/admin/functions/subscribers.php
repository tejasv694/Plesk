<?php
/**
* This file has the base subscriber functions in it. Each subprocess is handled separately.
*
* @version     $Id: subscribers.php,v 1.37 2007/12/28 03:56:01 hendri Exp $
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
class Subscribers extends SendStudio_Functions
{

	/**
	* PopupWindows
	* A list of popup windows for subscribers. This is overridden from the main sendstudio_functions file.
	*
	* @var Array
	*/
	var $PopupWindows = array('import', 'importiframe', 'export', 'exportiframe', 'view_report', 'viewtutorial');

	/**
	 * SuppressHeader
	 * A list of action that need to have the header string suppressed
	 *
	 * @var Array
	 */
	var $SuppressHeader = array('import', 'export','eventadd','eventlist','eventsave','eventupdate','eventdelete','eventtable');

	/**
	* Constructor
	* Loads the language file.
	*
	* @return Void Doesn't return anything
	*/
	function Subscribers()
	{
		$this->LoadLanguageFile('Subscribers');
	}

	/**
	* Process
	* This does base processing only. Prints the headers, handles paging, then passes off the functionality to the appropriate subarea.
	*
	* @see Subscribers_Add
	* @see Subscribers_Banned
	* @see Subscribers_Edit
	* @see Subscribers_Export
	* @see Subscribers_Import
	* @see Subscribers_Manage
	* @see Subscribers_Remove
	*/
	function Process()
	{
		$GLOBALS['Message'] = '';

		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;
		$user = GetUser();

		$permission_action = $action;
		if ($action == 'remove') {
			$permission_action = 'delete';
		}
		if ($action == 'view') {
			$permission_action = 'manage';
		}

		$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : null;

		if ($action == 'event') {
			$permission_action = $subaction;
			if ($permission_action == 'eventlist' || $permission_action == 'eventtable') {
				$permission_action = 'manage';
			}
		}

		$access = $user->HasAccess('Subscribers', $permission_action);

		$popup = (in_array($subaction, $this->PopupWindows)) ? true : false;
		$this->PrintHeader($popup, true, (!in_array($subaction, $this->SuppressHeader)));

		if (!$access) {
			$this->DenyAccess();
			return;
		}

		switch ($action) {
			case 'add':
				require_once(dirname(__FILE__) . '/subscribers_add.php');
				$AddSubscribers = new Subscribers_Add();
				$AddSubscribers->Process($subaction);
			break;

			case 'remove':
				require_once(dirname(__FILE__) . '/subscribers_remove.php');
				$RemoveSubscribers = new Subscribers_Remove();
				$RemoveSubscribers->Process($subaction);
			break;

			case 'edit':
				require_once(dirname(__FILE__) . '/subscribers_edit.php');
				$EditSubscriber = new Subscribers_Edit();
				$EditSubscriber->Process($subaction);
			break;

			case 'view':
				require_once(dirname(__FILE__) . '/subscribers_view.php');
				$EditSubscriber = new Subscribers_View();
				$EditSubscriber->Process();
			break;

			case 'import':
				require_once(dirname(__FILE__) . '/subscribers_import.php');
				$ImportSubscribers = new Subscribers_Import();
				$ImportSubscribers->Process($subaction);
			break;

			case 'export':
				require_once(dirname(__FILE__) . '/subscribers_export.php');
				$ExportSubscribers = new Subscribers_Export();
				$ExportSubscribers->Process($subaction);
			break;

			case 'banned':
				require_once(dirname(__FILE__) . '/subscribers_banned.php');
				$ExportSubscribers = new Subscribers_Banned();
				$ExportSubscribers->Process($subaction);
			break;

			case 'search':
				require_once(dirname(__FILE__) . '/subscribers_search.php');
				$SearchSubscribers = new Subscribers_Search();
				$SearchSubscribers->Process($subaction);
			break;

			case 'event':
				require_once(dirname(__FILE__) . '/subscribers_event.php');
				$EventSubscribers = new Subscribers_Event();
				$EventSubscribers->Process($subaction);
			break;

			case 'delete':
			case 'manage':
			default:
				require_once(dirname(__FILE__) . '/subscribers_manage.php');
				$ManageSubscribers = new Subscribers_Manage();
				$ManageSubscribers->Process($subaction);
			break;
		}
		$this->PrintFooter($popup);
	}

	/**
	* ChooseList
	* This prints out the select box which makes you choose a list (to start any subscriber process).
	* If there is only one list, it will automatically redirect you to that particular list (depending on which area you're looking for).
	* Otherwise, it prints out the appropriate template for the area you're working with.
	*
	* @param String $action The area you're working with. This can be manage, export, import, banned and so on.
	* @param String $subaction Which step you're up to in the process.
	*
	* @see User_API::GetLists
	* @see User_API::CanCreateList
	*
	* @return Void Prints out the appropriate template, doesn't return anything.
	*/
	function ChooseList($action='Manage', $subaction=null)
	{
		$action = strtolower($action);
		$user = GetUser();
		$lists = $user->GetLists();

		$listids = array_keys($lists);

		if (sizeof($listids) < 1) {
			switch ($action) {
				case 'banned':
					if ($subaction == 'add') {
						$extra_message = GetLang('Banned_Add_NoList_Message');
					} else {
						$extra_message = GetLang('Banned_Manage_NoList_Message');
					}
					$GLOBALS['Intro_Help'] = GetLang('Help_SubscribersManage');
				break;

				case 'import':
					$extra_message = GetLang('Import_Add_NoList_Message');
					$GLOBALS['Intro_Help'] = GetLang('Help_SubscribersManage');
				break;

				case 'export':
					$extra_message = GetLang('Export_Add_NoList_Message');
					$GLOBALS['Intro_Help'] = GetLang('Help_SubscribersManage');
				break;

				case 'remove':
					$extra_message = GetLang('Remove_NoList_Message');
					$GLOBALS['Intro_Help'] = GetLang('Help_SubscribersManage');
				break;

				case 'add':
					$extra_message = GetLang('Add_NoList_Message');
					$GLOBALS['Intro_Help'] = GetLang('Help_SubscribersManage');
				break;

				default:
					$extra_message = GetLang('View_NoList_Message');
					$GLOBALS['Intro_Help'] = GetLang('Help_SubscribersManage');
			}

			$GLOBALS['Intro'] = GetLang('Subscribers_' . ucwords($action));
			$GLOBALS['Lists_AddButton'] = '';

			if ($user->CanCreateList() === true) {
				$GLOBALS['Message'] = $this->PrintSuccess('Subscriber_NoLists', $extra_message, GetLang('ListCreate'));
				$GLOBALS['Lists_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
			} else {
				$GLOBALS['Message'] = $this->PrintSuccess('Subscriber_NoLists', $extra_message, GetLang('ListAssign'));
			}
			$this->ParseTemplate('Subscribers_No_Lists');
			return;
		}

		if ($listname = IEM::sessionGet('AddSubscriberMessage')) {
			$GLOBALS['Message'] = $this->PrintSuccess('SubscriberAddSuccessfulList', htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));
			IEM::sessionRemove('AddSubscriberMessage');
		}

		if ($emptybannedmsg = IEM::sessionGet('EmptyBannedSubscriberMessage')) {
			$GLOBALS['Message'] = $this->PrintSuccess('SubscriberBanListEmpty', htmlspecialchars($emptybannedmsg, ENT_QUOTES, SENDSTUDIO_CHARSET));
			IEM::sessionRemove('EmptyBannedSubscriberMessage');
		}

		if ($bannedmsg = IEM::sessionGet('DeleteBannedSubscriberMessage')) {
			$GLOBALS['Message'] = $bannedmsg;
			IEM::sessionRemove('DeleteBannedSubscriberMessage');
		}

		$sortedlist = array();

		if ($action == 'banned') {
			$banned_list = $user->GetBannedLists($listids);

			$banned_listids = array_keys($banned_list);

			if ($user->HasAccess('Lists', 'Global')) {
				$sortedlist['global'] = array('name' => GetLang('Subscribers_GlobalBan'));
			}

			$sortedlist += $lists;

			foreach ($sortedlist as $name => $details) {
				$check_name = $name;
				if ($name == 'global') {
					$check_name = 'g';
				}
				$sortedlist[$name]['bancount'] = 0;
				if (in_array($check_name, $banned_listids)) {
					$sortedlist[$name]['bancount'] = $banned_list[$check_name];
				}
			}
		}

		if ($action != 'banned') {
			if ($action == 'manage' || $action == 'export') {
				$sortedlist = array('any' => array('name' => GetLang('AnyList')));
			}
			$sortedlist += $lists;
		}

		$selectlist = '';
		foreach ($sortedlist as $listid => $listdetails) {
			$subscriber_count = '';
			if (isset($listdetails['bancount'])) {
				if ($listdetails['bancount'] == 1) {
					$subscriber_count = GetLang('Ban_Count_One');
				} else {
					$subscriber_count = sprintf(GetLang('Ban_Count_Many'), $this->FormatNumber($listdetails['bancount']));
				}
			} else {
				if (isset($listdetails['subscribecount'])) {
					if ($listdetails['subscribecount'] == 1) {
						$subscriber_count = GetLang('Subscriber_Count_One');
					} else {
						$subscriber_count = sprintf(GetLang('Subscriber_Count_Many'), $this->FormatNumber($listdetails['subscribecount']));
					}
				}
			}

			if ($listid == 'any') {
				$sel = 'selected';
			} else {
				$sel = '';
			}

			$selectlist .= '<option ' . $sel . ' value="' . $listid . '">' . htmlspecialchars($listdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . $subscriber_count . '</option>';
		}
		$GLOBALS['SelectList'] = $selectlist;

		$tempCount = count($sortedlist);
		if ($tempCount <= 10) {
			if ($tempCount < 3) {
				$tempCount = 3;
			}
			$GLOBALS['SelectListStyle'] = 'height: ' . ($tempCount * 25) . 'px;';
		}

		$GLOBALS['DisplaySegmentOption'] = 'none';
		if (in_array($action, array('manage', 'send')) && $user->HasAccess('Segments', 'View')) {
			$selectSegment = '';
			$segments = $user->GetSegmentList();
			$segmentAPI = $this->GetApi('Segment');
			foreach ($segments as $segmentid => $segmentdetails) {
				$tempCount = $segmentAPI->GetSubscribersCount($segmentdetails['segmentid']);

				if ($tempCount == 1) {
					$tempCount = GetLang('Subscriber_Count_One');
				} else {
					$tempCount = sprintf(GetLang('Subscriber_Count_Many'), $this->FormatNumber($tempCount));
				}

				$selectSegment .= 	'<option value="' . $segmentid . '">'
									. htmlspecialchars($segmentdetails['segmentname'], ENT_QUOTES, SENDSTUDIO_CHARSET)
									. $tempCount
									. '</option>';
			}
			$GLOBALS['SelectSegment'] = $selectSegment;

			$GLOBALS['DisplaySegmentOption'] = '';
		}

		switch ($action) {
			case 'search':
				$this->ParseTemplate('Subscriber_Search_Step1');
			break;
			case 'manage':
				$this->ParseTemplate('Subscriber_Manage_Step1');
			break;
			case 'add':
				$this->ParseTemplate('Subscribers_Add_Step1');
			break;
			case 'remove':
				$this->ParseTemplate('Subscribers_Remove_Step1');
			break;
			case 'import':
				$this->ParseTemplate('Subscribers_Import_Step1');
			break;
			case 'export':
				$this->ParseTemplate('Subscribers_Export_Step1');
			break;
			case 'banned':
				$this->ParseTemplate('Subscribers_Banned_Step1');
			break;
		}
	}

	/**
	* SetupGoogleCalendar
	* Sets the variables used for Google Calendar integration
	*
	* @see Subscribers_View::ViewSubscriber
	* @see Subscribers_Edit::EditSubscriber
	* @see Subscribers_Manage::ManageSubscribers_Step3
	*
	* @return Void Returns nothing
	*/
	function SetupGoogleCalendar()
	{
		$user = GetUser();

		$eventGoogleUse = IEM::sessionGet('eventGoogleUse');

		if (strlen($user->googlecalendarusername) && strlen($user->googlecalendarpassword)) {
			$GLOBALS['GoogleCalendarLink'] = SENDSTUDIO_APPLICATION_URL . "/admin/index.php?" . $_SERVER['QUERY_STRING'];
			$GLOBALS['GoogleCalendarIntroText'] = GetLang('GoogleCalendarIntro');
			$GLOBALS['eventGoogleDisplay'] = '';

			if ($eventGoogleUse !== 'no') {
				$GLOBALS['eventGoogleUse'] = 'true';
			} else {
				$GLOBALS['eventGoogleUse'] = 'false';
			}
		} else {
			$GLOBALS['eventGoogleUse'] = 'false';
			$GLOBALS['eventGoogleDisplay'] = 'display: none;';
		}
	}
}
