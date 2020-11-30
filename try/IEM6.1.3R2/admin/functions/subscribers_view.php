<?php
/**
* This file handles view of subscribers only. This lets you view only. Management is handled elsewhere.
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
* Class for viewing a subscriber.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Subscribers_View extends Subscribers
{

	/**
	* Process
	* Works out what you're trying to do and takes the appropriate action. Passes off processing to other functions.
	*
	* @param String $action The subaction or area you're working in.
	*
	* @see GetApi
	* @see Subscribers_API::IsDuplicate
	* @see Subscribers_API::UpdateEmailAddress
	* @see Subscribers_API::SaveSubscriberCustomField
	* @see Lists_API::GetCustomFields
	* @see CustomFields_API::Load
	* @see CustomFields_API::ValidData
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function Process()
	{
		$list = (int)$_GET['List'];
		$subscriberid = (int)$_GET['id'];
		$segmentid = isset($_GET['SegmentID'])? intval($_GET['SegmentID']) : 0;
		$this->ViewSubscriber($list, $subscriberid, $segmentid);
	}

	/**
	* ViewSubscriber
	* Prints the 'view subscriber' page and all appropriate options including custom fields.
	*
	* @param Int $listid The list the subscriber is on. This is checked to make sure the user has 'manage' access to the list before anything else.
	* @param Int $subscriberid The subscriberid to view.
	* @param Int $segmentid The ID of the segment that the subscriber is going to be fetched from
	* @param String $msgtype The heading to show when viewing a subscriber. This can be either error or success. Used with $msg to display something.
	* @param String $msg The message to display in the heading. If this is not present, no message is displayed.
	*
	* @see GetApi
	* @see Subscribers_API::GetCustomFieldSettings
	* @see Lists_API::GetCustomFields
	* @see Lists_API::Load
	* @see Lists_API::GetListFormat
	*
	* @return Void Doesn't return anything. Prints out the view form and that's it.
	*/
	function ViewSubscriber($listid = 0, $subscriberid = 0, $segmentid = 0, $msgtype = 'Error', $msg = false)
	{
		$user = GetUser();
		$access = $user->HasAccess('Subscribers', 'Manage');
		if (!$access) {
			$this->DenyAccess();
			return;
		}

		$this->SetupGoogleCalendar();

		$search_info = IEM::sessionGet('Search_Subscribers');

		$GLOBALS['list'] = $listid;

		if ($msg && $msgtype) {
			switch (strtolower($msgtype)) {
				case 'success':
					$GLOBALS['Success'] = $msg;
					$GLOBALS['Message'] = $this->ParseTemplate('SuccessMsg', true, false);
				break;
				default:
					$GLOBALS['Error'] = $msg;
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			}
		}

		$SubscriberApi = $this->GetApi('Subscribers');
		$subscriberinfo = false;

		/**
		 * Get Subscriber record from the database
		 */
			$adminAccess = false;

			// If this user is an admin/list admin/list admintype == a then give permission
			if ($user->Admin() || $user->ListAdminType() == 'a' || $user->ListAdmin()) {
				$adminAccess = true;
			}

			// Get subscribers from list
			if ($segmentid == 0) {
				if (!$adminAccess && !$SubscriberApi->CheckPermission($user->userid, $subscriberid)) {
					$this->DenyAccess();
					return;
				}

				$subscriberinfo = $SubscriberApi->LoadSubscriberList($subscriberid, $listid);


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

				$subscriberinfo = $SubscriberApi->LoadSubscriberSegment($subscriberid, $segmentid);
			}
		/**
		 * -----
		 */

		// hmm, the subscriber doesn't exist or can't be loaded? show an error.
		if (empty($subscriberinfo)) {
			$GLOBALS['ErrorMessage'] = GetLang('SubscriberDoesntExist_View');
			$this->DenyAccess();
			return;
		}

		// Log this to "User Activity Log"
		$logURL = SENDSTUDIO_APPLICATION_URL . '/admin/index.php?Page=Subscribers&Action=Edit&List=' . $_GET['List'] . '&id=' . $_GET['id'];
		IEM::logUserActivity($logURL, 'images/contacts_view.gif', $subscriberinfo['emailaddress']);

		$list_api = $this->GetApi('Lists');
		$list_api->Load($listid);

		$GLOBALS['emailaddress'] = $subscriberinfo['emailaddress'];
		$GLOBALS['subscriberid'] = $subscriberid;

		if ($subscriberinfo['requestdate'] == 0) {
			$GLOBALS['requestdate'] = GetLang('Unknown');
		} else {
			$GLOBALS['requestdate'] = $this->PrintTime($subscriberinfo['requestdate']);
		}

		$GLOBALS['requestip'] = ($subscriberinfo['requestip'] == '') ? GetLang('Unknown') : $subscriberinfo['requestip'];

		if ($subscriberinfo['confirmdate'] == 0) {
			$GLOBALS['confirmdate'] = GetLang('Unknown');
		} else {
			$GLOBALS['confirmdate'] = $this->PrintTime($subscriberinfo['confirmdate']);
		}

		$GLOBALS['confirmip'] = ($subscriberinfo['confirmip'] == '') ? GetLang('Unknown') : $subscriberinfo['confirmip'];

		if ($subscriberinfo['confirmed'] == 1) {
			$confirmed = 'Confirmed';
		} elseif ($subscriberinfo['confirmed'] == 0) {
			$confirmed = 'Unconfirmed';
		}

		$GLOBALS['ConfirmedList'] = GetLang($confirmed);

		$GLOBALS['ShowUnsubscribeInfo'] = 'none';

		$subscriber_status = 'a';
		if ($subscriberinfo['unsubscribed'] > 0) {
			$subscriber_status = 'u';
			$GLOBALS['unsubscribetime'] = $this->PrintTime($subscriberinfo['unsubscribed']);
			$GLOBALS['unsubscribeip'] = ($subscriberinfo['unsubscribeip'] == '') ? GetLang('Unknown') : $subscriberinfo['unsubscribeip'];
			$GLOBALS['ShowUnsubscribeInfo'] = '';
		}

		if ($subscriberinfo['bounced'] > 0) {
			$subscriber_status = 'b';
		}

		switch ($subscriber_status) {
			case 'a':
				$status = 'Active';
			break;

			case 'u':
				$status = 'Unsubscribed';
			break;

			case 'b':
				$status = 'Bounced';
			break;

			default:
		}

		// this is used both by the 'edit' and 'delete' buttons.
		$GLOBALS['subscriberid'] = $subscriberid;
		if ($segmentid != 0) {
			$GLOBALS['SegmentID'] = $segmentid;
			$GLOBALS['ExtraParameter'] = '&SegmentID=' . $segmentid;
		} else {
			$GLOBALS['SegmentID'] = 0;
			$GLOBALS['ExtraParameter'] = '';
		}

		$GLOBALS['EditButton'] = '';
		if ($user->HasAccess('Subscribers', 'Edit')) {
			$GLOBALS['EditButton'] = $this->ParseTemplate('Subscribers_View_Button_Edit', true, false);
		}

		$GLOBALS['DeleteButton'] = '';
		if ($user->HasAccess('Subscribers', 'Delete')) {
			$GLOBALS['DeleteButton'] = $this->ParseTemplate('Subscribers_View_Button_Delete', true, false);
		}

		$GLOBALS['StatusList'] = GetLang($status);

		$listformat = $list_api->GetListFormat();

		if ($subscriberinfo['format'] == 'h') {
			$format = GetLang('Format_HTML');
		} elseif ($subscriberinfo['format'] == 't') {
			$format = GetLang('Format_Text');
		}

		$GLOBALS['FormatList'] = $format;

		$customfields = $list_api->GetCustomFields($listid);

		$customfield_display = array();
		$customfieldinfo = '';

		if (!empty($customfields)) {
			$customfields_api = $this->GetApi('CustomFields');

			$customfieldinfo .= $this->ParseTemplate('Subscribers_Edit_Step2_CustomFields', true, false);
			foreach ($customfields as $pos => $customfield_info) {
				$GLOBALS['FieldID'] = $customfield_info['fieldid'];
				if ($customfield_info['required']) {
					$GLOBALS['Required'] = $this->ParseTemplate('Required', true, false);
				} else {
					$GLOBALS['Required'] = $this->ParseTemplate('Not_Required', true, false);
				}

				$subscriber_settings = $SubscriberApi->GetCustomFieldSettings($customfield_info['fieldid']);

				$customfields_api->fieldid = $customfield_info['fieldid'];
				$customfields_api->fieldtype = $customfield_info['fieldtype'];

				$subfield = $customfields_api->LoadSubField();

				$GLOBALS['FieldName'] = htmlspecialchars($customfield_info['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
				$GLOBALS['FieldValue'] = (empty($subscriber_settings)) ? "" : htmlspecialchars($subfield->GetRealValue($subscriber_settings), ENT_QUOTES, SENDSTUDIO_CHARSET);

				switch ($customfield_info['fieldtype']) {
					case 'textarea':
						$template_name = 'Subscribers_View_CustomField_TextArea';
					break;
					case 'date':
						$fieldsettings = unserialize($customfield_info['fieldsettings']);
						$GLOBALS['DateJSON'] = '';
						$GLOBALS['GoogleCalendarButton'] = '';
						if (strlen($GLOBALS['FieldValue'])) {
							$date = explode('/',$GLOBALS['FieldValue']);
							$datejson = array_combine(array_slice($fieldsettings['Key'],0,3),$date);

							$GLOBALS['DateJSON'] = GetJSON($datejson);

							if (strlen($user->googlecalendarusername) && strlen($user->googlecalendarpassword)) {
								$GLOBALS['GoogleCalendarButton'] =  $this->ParseTemplate('google_calendar_button',true);
							} else {
								$GLOBALS['GoogleCalendarButton'] =  $this->ParseTemplate('google_calendar_button_disabled',true);
							}
						}

						$template_name = 'Subscribers_View_CustomField_Date';
					break;
					default:
						$template_name = 'Subscribers_View_CustomField';
				}

				$customfield_display[] = $this->ParseTemplate($template_name, true, false);
				unset($subfield);
			}

			$column1 = $column2 = array();
			if (count($customfield_display) > 9) {
				$customfieldinfo_template = 'Subscribers_customfieldinfo_twocolumns';
				$split = ceil(count($customfield_display) / 2);

				for ($i = 0; $i < $split; $i++) {
					$column1[] = $customfield_display[$i];
					if (isset($customfield_display[$i + $split])) {
						$column2[] = $customfield_display[$i + $split];
					} else {
						$column2[] = '<td>&nbsp;</td><td>&nbsp;</td>';
					}
				}

				$GLOBALS['CustomFieldInfo_1'] = '<tr>' . implode('</tr><tr>',$column1) . '</tr>';
				$GLOBALS['CustomFieldInfo_2'] = '<tr>' . implode('</tr><tr>',$column2) . '</tr>';
			} else {
				$customfieldinfo_template = 'Subscribers_customfieldinfo_onecolumn';
				$GLOBALS['CustomFieldInfo_1'] = '';
				foreach ($customfield_display as $field) {
					$GLOBALS['CustomFieldInfo_1'] .= '<tr>' . $field . '</tr>';
				}
			}
			$GLOBALS['CustomFieldInfo'] = $customfieldinfo . $this->ParseTemplate($customfieldinfo_template,true);
		}

		$GLOBALS['listid'] = $listid;

		$actions = $user->GetEventActivityType();
		$GLOBALS['Actions'] = '';
		foreach ($actions as $action) {
			$GLOBALS['Actions'] .= '<option value="' . htmlspecialchars($action,ENT_QUOTES, SENDSTUDIO_CHARSET) . '">'. htmlspecialchars($action,ENT_QUOTES, SENDSTUDIO_CHARSET) . "</option>";
		}

		$GLOBALS['EventTypesJSON'] = GetJSON($actions);
		$GLOBALS['EventAddForm'] = $this->ParseTemplate('Subscriber_Event_Add',true,false);

		$GLOBALS['DatePickerJavascript'] = $this->ParseTemplate('ui.datepicker.custom_iem',true,false);

		if (IEM::sessionGet('gcal_allday')) {
			$GLOBALS['GoogleCalendarAllDay'] = 'true';
		} else {
			$GLOBALS['GoogleCalendarAllDay'] = 'false';
		}

		$GLOBALS['SubscriberEvents_Intro'] = GetLang('SubscriberEvents_Intro');
		if ($user->HasAccess('Subscribers','EventSave')) {
			$GLOBALS['SubscriberEvents_Intro'] .= GetLang('SubscriberEvents_Intro_AddEvent');
		}

		$this->ParseTemplate('Subscribers_View_Step2');
	}
}
