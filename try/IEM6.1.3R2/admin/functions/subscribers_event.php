<?php
/**
* This file handles logging of events for subscribers. All functions are meant to be used through AJAX.
*
* @version     $Id: subscribers_view.php,v 1.33 2007/05/15 07:03:55 rodney Exp $
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
* Class for manage events for subscribers.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Subscribers_Event extends Subscribers
{
	/**
	* Stores the Subscibers_API object
	*/
	var $api;

	/**
	* The default column to sort entries by. This only applies to entries retrieved in table form.
	* @var String The column name to sort by
	*/
	var $_DefaultSort = 'lastupdate';

	/**
	* The default order to sort entries by. This only applies to entries retrieved in table form.
	* @var String The order to sort by
	*/
	var $_DefaultDirection = 'desc';

	/**
	* Number of events to show per page by default
	* @var Int Number of events per page
	*/
	var $_PerPageDefault = 20;

	/**
	* Event icons for different event types, the key is the event type in lower case
	*/
	var $eventIcons = array(
		'email' => 'event_email.gif',
		'meeting' => 'event_meeting.gif',
		'phone call' => 'event_phone.gif'
	);

	/**
	* Process
	* Works out what you're trying to do and takes the appropriate action. Passes off processing to other functions.
	*
	* @param String $action The subaction or area you're working in.
	*
	* @see GetApi
	* @see GetUser
	* @see User_API::HasAccess
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function Process($subaction)
	{
		$user = GetUser();

		$this->api = $this->GetApi('Subscribers');

		$listid = 0;
		if (isset($_GET['List'])) {
			$listid = (int)$_GET['List'];
		}

		$subscriberid = 0;
		if (isset($_GET['id'])) {
			$subscriberid = (int)$_GET['id'];
		}

		$segmentid = 0;
		if (isset($_GET['SegmentID'])) {
			$segmentid = intval($_GET['SegmentID']);
		}
        
                if($listid == 0 && $segmentid == 0 && $subscriberid != 0){
                    $temp_sub_info = $this->api->GetRecordByID($subscriberid);
                    $listid = $temp_sub_info['listid'];
                }

		/**
		 * Check if user has access to this subscriber
		 */
			$subscriberinfo = false;
			$adminAccess = false;

			// If this user is an admin/list admin/list admintype == a then give permission
			if ($user->Admin() || $user->ListAdminType() == 'a' || $user->ListAdmin()) {
				$adminAccess = true;
			}

			// Get subscribers from list
			if ($segmentid == 0) {
				if (!$adminAccess && !$this->api->CheckPermission($user->userid, $subscriberid)) {
					$this->DenyAccess();
					return;
				}

				$subscriberinfo = $this->api->LoadSubscriberList($subscriberid, $listid);
                
                

			// Get subscribers from segment
			} else {
				if (!$adminAccess) {
					$segmentapi = $this->GetApi('Segment', true);
					$segmentapi->Load($segmentid);

					if ($segmentapi->ownerid != $user->userid && !$user->HasAccess('Segments', 'View', $segmentid)) {
						$this->DenyAccess();
						return;
					}
				}

				$subscriberinfo = $this->api->LoadSubscriberSegment($subscriberid, $segmentid);
			}

			if (empty($subscriberinfo)) {
				$this->DenyAccess();
				return;
			}          
		/**
		 * -----
		 */

		$eventid = 0;
		if (isset($_GET['eventid'])) {
			$eventid = (int)$_GET['eventid'];
		}

		switch ($subaction) {
			case 'eventsave':
				$this->EventSave($subscriberid, $listid, $_POST['event']);
			break;
			case 'eventlist':
				$this->EventList($subscriberid);
			break;
			case 'eventupdate':
				$this->EventUpdate($subscriberid, $eventid, $_POST['event']);
			break;
			case 'eventdelete':
				$this->EventDelete($subscriberid, $eventid);
			break;
			case 'eventtable':
				$this->EventTable($subscriberid, $listid);
			break;
			default:
		}
		exit;
	}

	/**
	* EventTable
	* Generates the table view for events used on the View Contact and Edit Contact pages.
	*
	* @param Integer $subscriberid The subscriberid the event belongs to
	* @param Integer $listid The listid of the subscriber being viewed
	*
	* @see Subscriber_API::GetEvents
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function EventTable($subscriberid, $listid)
	{
		$user = GetUser();

		$perpage = $this->GetPerPage('subscriber_events');

		if (isset($_GET['SetPerPage'])) {
			$perpage = (int)$_GET['SetPerPage'];
			$this->SetPerPage($perpage, 'subscriber_events');
		}

		$page = 1;
		if (isset($_GET['DisplayPage'])) {
			$page = (int)$_GET['DisplayPage'];
		}
		if ($page < 1) { $page = 1; }

		$sort_details = $this->GetSortDetails('subscriber_events');

		$num_events = $this->api->GetEvents($subscriberid, $page, $perpage, false, $sort_details, true);
		$events = $this->api->GetEvents($subscriberid, $page, $perpage, false, $sort_details);

		$GLOBALS['SubscriberID'] = $subscriberid;
		$GLOBALS['ListID'] = $listid;

		$GLOBALS['Event_AddButton'] = '';
		if ($user->HasAccess('Subscribers', 'EventSave')) {
			$GLOBALS['Event_AddButton'] =  $this->ParseTemplate('Subscriber_Events_AddButton', true);
		}

		$GLOBALS['Event_DeleteButton'] = '';
		if ($user->HasAccess('Subscribers', 'EventDelete')) {
			$GLOBALS['Event_DeleteButton'] = $this->ParseTemplate('Subscriber_Events_DeleteButton', true);
		}

		if ($num_events == 0) {
			$GLOBALS['Message'] .= $this->PrintSuccess('SubscriberEventsEmpty', GetLang('SubscriberEventsEmpty'));

			$this->ParseTemplate('Subscriber_Events_Empty');

			return;
		}

		$GLOBALS['Events'] = '';

		foreach ($events as $event) {
			$event['date'] = $this->PrintDate($event['eventdate'], 'd/n/Y');
			$event['time'] = $this->PrintDate($event['eventdate'], 'g:i A');
			$GLOBALS['eventid'] = $event['eventid'];
			$GLOBALS['Subject'] = htmlspecialchars($event['eventsubject'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Type'] = htmlspecialchars($event['eventtype'], ENT_QUOTES, SENDSTUDIO_CHARSET);

			if (isset($this->eventIcons[strtolower($event['eventtype'])])) {
				$GLOBALS['Icon'] = $this->eventIcons[strtolower($event['eventtype'])];
			} else {
				$GLOBALS['Icon'] = 'event.gif';
			}

			$GLOBALS['Date'] = $this->PrintTime($event['lastupdate']);
			$GLOBALS['Notes'] = htmlspecialchars(substr($event['eventnotes'], 0, 100), ENT_QUOTES, SENDSTUDIO_CHARSET);

			$GLOBALS['User'] = htmlspecialchars($event['username'], ENT_QUOTES, SENDSTUDIO_CHARSET);

			$GLOBALS['EventJSON'] = GetJSON($event);

			$GLOBALS['EventDeleteLink'] = '';
			$GLOBALS['EventEditLink'] = '';

			if ($user->HasAccess('Subscribers', 'Eventdelete')) {
				$GLOBALS['EventDeleteLink'] = $this->ParseTemplate('subscribers_manage_eventdeletelink', true, false);
			}
			if ($user->HasAccess('Subscribers', 'Eventupdate')) {
				$GLOBALS['EventEditLink'] = $this->ParseTemplate('subscribers_manage_eventeditlink', true, false);
			}

			$GLOBALS['Events'] .= $this->ParseTemplate('subscriber_event_row', true);
		}

		$this->SetupPaging($num_events, $page, $perpage);

		$this->ParseTemplate('subscriber_event_table');
	}

	/**
	* EventUpdate
	* Updates an event and prints out javascript to update the event on the page. This will print an alert() if an error occurs or if the API returns an error message.
	*
	* @param Integer $subscriberid The subscriberid the event belongs to
	* @param Integer $eventid The eventid to update
	* @param Array $event The values to update the event with. This must have indexes: type, notes, time, date. Time is in 12-hour format ex: 10:24 PM. Date is in dd/mm/yyyy
	*
	* @see ParseDate
	* @see Subscriber_API::UpdateEvent
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function EventUpdate($subscriberid, $eventid, $event)
	{
		if (!$this->ParseDate($event)) {
			return false;
		}
		$result = $this->api->UpdateEvent($subscriberid,$eventid,$event);

		$event['notes'] = htmlspecialchars($event['notes'], ENT_QUOTES, SENDSTUDIO_CHARSET);

		$user = GetUser();
		if ($user) {
			$user->AddEventActivityType($event['type']);
		}

		if (is_array($result) && $result[0] == false) {
			echo 'alert("' . $result[1] . '");';
			return false;
		}

		if ($user && isset($event['google']['log']) && $event['google']['log']) {
			// Add to google calendar
			$gdata = array(
				'what' => $event['subject'],
				'description' => $event['notes'] . "\n" . $event['google']['link'],
				'datefrom' => $event['date'],
				'timefrom' => $event['time'],
				'dateto' => $event['google']['enddate'],
				'timeto' => $event['google']['endtime'],
				'username' => $user->googlecalendarusername,
				'password' => $user->googlecalendarpassword,
			);
			try {
				$this->GoogleCalendarAdd($gdata);
			} catch (GoogleCalendarException $e) {
				echo "alert('" . sprintf(GetLang('GoogleCalendarUnabletoSave'),$e->getMessage()) . "');";
			}
			IEM::sessionSet('eventGoogleUse','yes');
		} else {
			IEM::sessionSet('eventGoogleUse','no');
		}

		$event_button = addslashes($this->ParseTemplate('subscribers_manage_row_eventbutton',true,false));

		echo 'subscriberEventAdded('.$subscriberid.',"' . $event_button . '");';
		echo 'resetForm();';
	}

	/**
	* EventDelete
	* Deletes an event and prints out javascript to remove the event from the page. This will print an alert() if an error occurs or if the API returns an error message.
	*
	* @param Integer $subscriberid The subscriberid the event belongs to
	* @param Integer $eventid The eventid to delete
	*
	* @see Subscriber_API::DeleteEvent
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function EventDelete($subscriberid,$eventid)
	{
		if ($eventid == 0 && isset($_GET['eventids'])) {
			$eventid = $_GET['eventids'];
		}

		$result = $this->api->DeleteEvent($subscriberid,$eventid);

		if (is_array($result) && $result[0] == false) {
			echo 'alert("' . $result[1] . '");';
			return false;
		}

		echo 'subscriberEventDeleted(' . $subscriberid . ',' . (int)$eventid . ');';
	}

	/**
	* EventList
	* Prints a list of events in HTML
	*
	* @param Integer $subscriberid The subscriberid to list events for
	*
	* @see Subscriber_API::GetEvents
	* @see GetJSON
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function EventList($subscriberid)
	{
		$user = GetUser();
		$sortdetails = array(
			'SortBy' => 'eventdate',
			'Direction' => 'desc'
		);
		$events = $this->api->GetEvents($subscriberid,0,'all',false,$sortdetails);

		$events_list = '';
		$GLOBALS['SubscriberID'] = $subscriberid;

		foreach ($events as $event) {
			$event['date'] = $this->PrintDate($event['eventdate'],'d/n/Y');
			$event['time'] = $this->PrintDate($event['eventdate'],'g:i A');
			$GLOBALS['eventid'] = $event['eventid'];
			$GLOBALS['Subject'] = htmlspecialchars($event['eventsubject'],ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Type'] = htmlspecialchars($event['eventtype'],ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Date'] = $this->PrintTime($event['eventdate']);
			$GLOBALS['Notes'] = nl2br(htmlspecialchars($event['eventnotes']));

			$GLOBALS['EventJSON'] = GetJSON($event);

			$GLOBALS['EventLinkDisplay'] = 'none';
			$GLOBALS['EventDeleteLink'] = '';
			$GLOBALS['EventEditLink'] = '';
			$GLOBALS['EventOr'] = '';

			if ($user->HasAccess('Subscribers','Eventdelete')) {
				$GLOBALS['EventDeleteLink'] = $this->ParseTemplate('subscribers_manage_eventdeletelink',true,false);
				$GLOBALS['EventLinkDisplay'] = 'inline';
			}
			if ($user->HasAccess('Subscribers','Eventupdate')) {
				$GLOBALS['EventEditLink'] = $this->ParseTemplate('subscribers_manage_eventeditlink',true,false);
				$GLOBALS['EventLinkDisplay'] = 'inline';
			}
			if ($GLOBALS['EventDeleteLink'] != '' && $GLOBALS['EventEditLink'] != '') {
				$GLOBALS['EventOr'] = strtolower(GetLang('OR'));
			}

			$this->ParseTemplate('Subscriber_Event');
		}
	}

	/**
	* ParseDate
	* This validates the date in $event['date'] and $event['time'] and turns it into a unix timestamp. The timestamp is stored in $event['eventdate']. If validate fails an alert() is printed.
	*
	* @param Array $event The values to to parse the time form. Time is in 12-hour format ex: 10:24 PM. Date is in dd/mm/yyyy. This value is passed by reference
	*
	* @see AdjustTime
	*
	* @return Boolean Returns true on success and false on failure.
	*/
	function ParseDate(&$event)
	{
		if (!preg_match('~^\d{1,2}/\d{1,2}/\d{4}$~',$event['date'])) {
			echo 'alert("' . GetLang('EventSpecifyDate') . '");';
			return false;
		}
		$date = explode('/',$event['date']);

		if (!preg_match('/^(\d{1,2}):(\d{2}) (AM|PM)$/i',$event['time'],$matches)) {
			echo 'alert("' . GetLang('EventSpecifyTime') . '");';
			return false;
		} else {
			$time = array($matches[1] + (strtolower($matches[3]) == 'pm' ? 12 : 0),$matches[2]);
		}

		$timestamp = AdjustTime(array($time[0],$time[1],0,$date[1],$date[0],(int)substr($date[2],-2,2)),true);
		$event['eventdate'] = $timestamp;
		return true;
	}

	/**
	* EventSave
	* Adds an event and prints out javascript to add the event on the page. This will print an alert() if an error occurs or if the API returns an error message.
	*
	* @param Integer $subscriberid The subscriberid the event belongs to
	* @param Integer $listid The listid the subscriber belongs to
	* @param Array $event The values to add the event with. This must have indexes: type, notes, time, date. Time is in 12-hour format ex: 10:24 PM. Date is in dd/mm/yyyy
	*
	* @see ParseDate
	* @see Subscriber_API::AddEvent
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function EventSave($subscriberid, $listid, $event)
	{
		$GLOBALS['SubscriberID'] = $subscriberid;
		$event_button = $this->ParseTemplate('subscribers_manage_row_eventbutton',true,false);

		if (!$this->ParseDate($event)) {
			return false;
		}

		$user = GetUser();
		if ($user) {
			$user->AddEventActivityType($event['type']);
		}

		$result = $this->api->AddEvent($subscriberid,$listid,$event);
		if (is_array($result) && $result[0] == false) {
			echo 'alert("' . $result[1] . '");';
			return false;
		}

		if ($user && isset($event['google']) && isset($event['google']['log']) && $event['google']['log']) {
			// Add to google calendar
			$gdata = array(
				'what' => $event['subject'],
				'description' => $event['notes'] . "\n" . $event['google']['link'],
				'datefrom' => $event['date'],
				'timefrom' => $event['time'],
				'dateto' => $event['google']['enddate'],
				'timeto' => $event['google']['endtime'],
				'username' => $user->googlecalendarusername,
				'password' => $user->googlecalendarpassword,
			);
			try {
				$this->GoogleCalendarAdd($gdata);
			} catch (GoogleCalendarException $e) {
				echo "alert('" . sprintf(GetLang('GoogleCalendarUnabletoSave'),$e->getMessage()) . "');";
			}
			IEM::sessionSet('eventGoogleUse',true);
		} else {
			IEM::sessionSet('eventGoogleUse',false);
		}

		$event_button = addslashes($event_button);

		echo 'subscriberEventAdded('.$subscriberid.',"' . $event_button . '");';
		echo 'resetForm();';
	}

	/**
	* SetupPaging
	* Sets up the paging variables used by the events table.
	*
	* @param Int $numrecords The number of records in the table
	* @param Int $currentpage The current page being viewed
	* @param Int $perpage The number of records on each page
	*
	* @return Mixed False on failure, nothing on success.
	*/

	function SetupPaging($numrecords=0, $currentpage=1, $perpage=20)
	{
		$display_settings['NumberToShow'] = $this->GetPerPage('subscriber_events');

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
			$GLOBALS['PagingBottom'] = '';
			$GLOBALS['Paging'] = '';
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

		$sortinfo = $this->GetSortDetails('subscriber_events');

		$direction = $sortinfo['Direction'];
		$sort = $sortinfo['SortBy'];
		$sortdetails = '&SortBy=' . $sort . '&Direction=' . $direction;

		$string = '(' . GetLang('Page') . ' ' . $this->FormatNumber($currentpage) . ' ' . GetLang('Of') . ' ' . $this->FormatNumber($num_pages) . ')&nbsp;&nbsp;&nbsp;&nbsp;';

		$display_page_name = 'DisplayPage';
		if (isset($GLOBALS['PPDisplayName'])) {
			$display_page_name .= $GLOBALS['PPDisplayName'];
		}

		if ($currentpage > 1) {
			$string .= "<a href=\"#\" title=\"" . GetLang('GoToFirst') . "\" onclick=\"eventsTable('$sort','$direction',1);return false;\">&laquo;</a>&nbsp;|&nbsp;";
			$string .= "<a href=\"#\" title=\"" . GetLang('PagingBack') . "\" onclick=\"eventsTable('$sort','$direction',$prevpage);return false;\">".GetLang('PagingBack') ."</a>&nbsp;|";
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
				$string .= "<a href=\"#\" onclick=\"eventsTable('$sort','$direction',$pageid);return false;\">$pageid</a>";
			}
			$string .= '&nbsp;|';
		}

		if ($currentpage == $num_pages) {
			$string .= '&nbsp;' . GetLang('PagingNext') . '&nbsp;|';
			$string .= '&nbsp;&raquo;';
		} else {
			$string .= "&nbsp;<a href=\"#\" onclick=\"eventsTable('$sort','$direction',$nextpage);return false;\">" . GetLang('PagingNext') . "</a>&nbsp;|";
			$string .= "&nbsp;<a href=\"#\" title=\"" . GetLang('GoToLast') . "\" onclick=\"eventsTable('$sort','$direction',$num_pages);return false;\">&raquo;</a>";
		}

		$GLOBALS['DisplayPage'] = $string;

		if ($perpage != 'all' && ($perpage >= $this->_PagingMinimum && $numrecords > $perpage)) {
			$paging_bottom = $this->ParseTemplate('Paging_Bottom', true, false);
			$paging = $this->ParseTemplate('subscriber_events_paging',true,false);
		} else {
			$paging_bottom = '';
			$paging = '';
		}

		$GLOBALS['Paging'] = $paging;
		$GLOBALS['PagingBottom'] = $paging_bottom;
	}
}
