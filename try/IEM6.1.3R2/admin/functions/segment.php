<?php
/**
 * List Segmentation Management
 * @version     $Id: schedule.php,v 1.42 2008/01/09 23:19:06 chris Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */

/**
 * Include the base sendstudio functions.
 */
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
 * Class for List Segmentation Management
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */
class Segment extends SendStudio_Functions
{
	/**
	 * Cache of available list IDs for a particular user
	 * @var Array an associative array of availability list
	 * @see Segment::_filterAllowedList()
	 */
	var $_cacheUserAvailableListIDs = array();

	/**
	 * Cache of custom fields that is used by list
	 * @var Array an associative array of custom fields that is used by lists
	 * @see Segment::_getCustomFieldUseByList()
	 */
	var $_cacheCustomFieldsUsedByLists = array();

	/**
	 * Constructor
	 * Loads language file and set up any class variables to overwite parent's default value
	 *
	 * @uses SendStudio_Functions::LoadLanguageFile()
	 *
	 * @return Object Returns this class
	 */
	function Segment()
	{
		$this->LoadLanguageFile();
		$this->_DefaultSort = 'SegmentName';
	}

	/**
	 * Process
	 * This handles working out what stage you are up to and so on with workflow.
	 * @return Void Does not return anything
	 *
	 * @uses Segment::_getGETRequest()
	 * @uses Segment::_DeleteSegment()
	 * @uses Segment::_EditSegment()
	 * @uses Segment::_CreateSegment()
	 * @uses Segment::_SaveSegment()
	 * @uses Segment::_ManageSegment()
	 */
	function Process()
	{

		$user = GetUser();
		$access = $user->HasAccess('Segments');
		if (!$access) {
			$this->PrintHeader();
			$this->DenyAccess();
			$this->PrintFooter();
			return;
		}

		/**
		 * Define and sanitize "common" variables that is used by this function
		 */
			$reqAction		= strtolower($this->_getGETRequest('Action', ''));
			$response		= '';
			$parameters 	= array();


			$parameters['message']	= '&nbsp;';
			$parameters['user']		= GetUser();
			$parameters['action']	= $reqAction;
		/**
		 * -----
		 */

		switch ($reqAction) {
			case 'delete':
				$response = $this->_DeleteSegment($parameters);
			break;

			case 'edit':
				$response = $this->_EditSegment($parameters);
			break;

			case 'copy':
				$response = $this->_CopySegment($parameters);
			break;

			case 'create':
				$response = $this->_CreateSegment($parameters);
			break;

			case 'save':
				$response = $this->_SaveSegment($parameters);
			break;

			case 'ajax':
				$response = $this->_DoAjaxRequest($parameters);
			break;

			case 'processpaging':
			default:
				$response = $this->_ManageSegment($parameters);
			break;
		}


		/**
		 * Print output
		 */
			$show = ($reqAction != 'ajax');

			if ($show) {
				$this->PrintHeader();
			}

			echo $response;

			if ($show) {
				$this->PrintFooter();
			}
		/**
		 * -----
		 */
	}




	/**
	 * Private functions
	 */
		/**
		 * _ManageSegment
		 * Create the User Interface for Segmement Management
		 *
		 * @param Array $parameter Any parameters that need to be parsed to this function
		 * @return String Returns response string that can be outputted to the browser
		 *
		 * @uses GetLang()
		 * @uses Users_API::SegmentAdmin()
		 * @uses Users_API::SetSettings()
		 * @uses Users_API::CanCreateSegment()
		 * @uses Users_API::CanCreateList()
		 * @uses Segment::_getGETRequest()
		 * @uses Segment::_checkPermissionCanDelete()
		 * @uses Segment::_checkPermissionCanEdit()
		 * @uses Segment_API::GetSegmentByUserID()
		 * @uses SendStudio_Functions::GetApi()
		 * @uses SendStudio_Functions::PrintSuccess()
		 * @uses SendStudio_Functions::ParseTemplate()
		 * @uses SendStudio_Functions::SetPerPage()
		 * @uses SendStudio_Functions::GetPerPage()
		 * @uses SendStudio_Functions::GetCurrentPage()
		 * @uses SendStudio_Functions::GetSortDetails()
		 * @uses SendStudio_Functions::DisabledItem()
		 * @uses SendStudio_Functions::PrintDate()
		 *
		 * @access private
		 */
		function _ManageSegment($parameter = array())
		{
			/**
			 * Sanitize and declare variables that is going to be used in this function
			 */
				$pageRecordPP		= 0;
				$pageCurrentIndex	= $this->GetCurrentPage();
				$pageSortInfo		= $this->GetSortDetails();

				$records			= array();
				$recordTotal		= 0;

				$segmentList		= '';
				$output				= '';

				$api				= $this->GetApi('Segment');

				$userID				= $parameter['user']->userid;
			/**
			 * -----
			 */


			/**
			 * Sort out pagination
			 */
				if ($parameter['action'] == 'processpaging') {
					$pageRecordPP = intval($this->_getGETRequest('PerPageDisplay', 10));
					if ($pageRecordPP == 0) {
						$pageRecordPP = 10;
					}

					$this->SetPerPage($pageRecordPP);
				}

				if ($pageRecordPP == 0) {
					$pageRecordPP = $this->GetPerPage();
				}
			/**
			 * -----
			 */

			if ($parameter['user']->SegmentAdmin() || $parameter['user']->segmentadmintype == 'a') {
				$userID = null;
			}

			$start = 0;
			if ($pageRecordPP != 'all') {
				$start = ($pageCurrentIndex - 1) * $pageRecordPP;
			}
			$records = $api->GetSegmentByUserID($userID, $pageSortInfo, false, $start, $pageRecordPP);
			$recordTotal = $api->GetSegmentByUserID($userID, $pageSortInfo, true);

			$GLOBALS['CreateButtonDisplayProperty'] = ($parameter['user']->CanCreateSegment()? '' : 'none');

			if ($recordTotal > 0) {
				foreach ($records as $record) {
					$GLOBALS['SegmentID'] = htmlspecialchars($record['segmentid'], ENT_QUOTES, SENDSTUDIO_CHARSET);
					$GLOBALS['SegmentName'] = htmlspecialchars($record['segmentname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
					$GLOBALS['Created'] = $this->PrintDate($record['createdate']);
					$GLOBALS['SegmentAction'] = '';

					$api->Load($record['segmentid']);
					$canEdit = $this->_checkPermissionCanEdit($api, $parameter['user']);

					// Can View Contacts
					if ($parameter['user']->HasAccess('Subscribers')) {
						$GLOBALS['SegmentAction'] .= $this->ParseTemplate('Segment_Manage_ViewContacts', true, false);
					} else {
						$GLOBALS['SegmentAction'] .= $this->DisabledItem('ViewContacts');
					}

					// Can edit
					if ($canEdit) {
						$GLOBALS['SegmentAction'] .= $this->ParseTemplate('Segment_Manage_EditLink', true, false);
					} else {
						$GLOBALS['SegmentAction'] .= $this->DisabledItem('Edit');
					}

					// Can create list and can edit the segment to be copied (ie. Copy)
					if ($parameter['user']->CanCreateSegment() && $canEdit) {
						$GLOBALS['SegmentAction'] .= $this->ParseTemplate('Segment_Manage_CopyLink', true, false);
					} else {
						$GLOBALS['SegmentAction'] .= $this->DisabledItem('Copy');
					}

					// Can delete segment
					if ($this->_checkPermissionCanDelete($api, $parameter['user'])) {
						$GLOBALS['SegmentAction'] .= $this->ParseTemplate('Segment_Manage_DeleteLink', true, false);
					} else {
						$GLOBALS['SegmentAction'] .= $this->DisabledItem('Delete');
					}

					$segmentList .= $this->ParseTemplate('Segment_Manage_Row', true);
				}

				/**
				 * Clean GLOBAL
				 */
					$tempCleanGlobal = array(
						'SegmentAction',
						'Created',
						'SegmentName',
						'SegmentID'
					);

					foreach ($tempCleanGlobal as $tempEach) {
						if (isset($GLOBALS[$tempEach])) {
							unset($GLOBALS[$tempEach]);
						}
					}
				/**
				 * -----
				 */

				/**
				 * Parse template for a list that contain rows
				 */
					$GLOBALS['Message'] = (isset($parameter['message']) && !empty($parameter['message']))? $parameter['message'] : '&nbsp;';

					$GLOBALS['PAGE'] = 'Segment';
					$GLOBALS['FormAction'] = 'Action=ProcessPaging';
					$GLOBALS['SegmentList'] = $segmentList;

					$this->SetupPaging($recordTotal, $pageCurrentIndex, $pageRecordPP);
					$output = $this->ParseTemplate('Segment_Manage', true);

					unset($GLOBALS['SegmentList']);
					unset($GLOBALS['FormAction']);
					unset($GLOBALS['PAGE']);
				/**
				 * -----
				 */
			} else {
				/**
				 * Parse template for EMPTY segment
				 */
					if ($parameter['user']->CanCreateSegment() === true) {
						$parameter['message'] .= $this->PrintSuccess('SegmentManageNoSegment', GetLang('SegmentManageSegmentCreate'));
						$GLOBALS['DisplayCreateButton'] = 'true';
					} else {
						$parameter['message'] .= $this->PrintSuccess('SegmentManageNoSegment', GetLang('SegmentManageSegmentAssign'));
						$GLOBALS['DisplayCreateButton'] = 'false';
					}

					$GLOBALS['Message'] = $parameter['message'];
					$output = $this->ParseTemplate('Segment_Manage_Empty', true);
					unset($GLOBALS['DisplayCreateButton']);
					unset($GLOBALS['Message']);
				/**
				 * -----
				 */
			}

			unset($GLOBALS['CreateButtonDisplayProperty']);

			return $output;
		}

		/**
		 * _EditSegment
		 * Create user interface for editng a segment
		 *
		 * @param Array $parameter Any parameters that need to be parsed to this function
		 * @return String Returns response string that can be outputted to the browser
		 *
		 * @uses GetLang()
		 * @uses Segment::_getGETRequest()
		 * @uses Segment::_ManageSegment()
		 * @uses Segment::_getEditor()
		 * @uses Segment::_checkPermissionCanEdit()
		 * @uses Segment_API::Load()
		 * @uses Segment_API::RemoveUnavailableCustomFields()
		 * @uses SendStudio_Functions::GetApi()
		 *
		 * @access private
		 */
		function _EditSegment($parameter = array())
		{
			$segmentID = intval($this->_getGETRequest('id', null));

			if (empty($segmentID)) {
				return $this->_ManageSegment($parameter);
			}

			$segment = $this->GetApi('Segment');
			$loaded = $segment->Load($segmentID);
			if (!$loaded) {
				$this->PrintHeader();
				$GLOBALS['ErrorMessage'] = GetLang('SegmentDoesntExist');
				$this->DenyAccess();
				$this->PrintFooter();
				return;
			}

			if (!$this->_checkPermissionCanEdit($segment, $parameter['user'])) {
				return $this->_ManageSegment($parameter);
			}

			$message = '';

			$availableList = $parameter['user']->GetLists();
			$originalUsedList = $segment->searchinfo['Lists'];
			$segment->searchinfo['Lists'] = array_intersect(array_keys($availableList), $originalUsedList);

			// If there are any lists that were taken out due to permission,
			// need to remove them from view too
			if (count($segment->searchinfo['Lists']) != count($originalUsedList)) {
				$GLOBALS['Warning'] = GetLang('SegmentManageEditWarningPrivilage');
				$message = $this->ParseTemplate('WarningMsg', true);

				$tempCustomField = $this->_getCustomFieldUsedByList($segment->searchinfo['Lists']);
				$tempCustomField = is_array($tempCustomField['customfields'])? array_keys($tempCustomField['customfields']) : array();

				$segment->RemoveUnavailableCustomFields($tempCustomField);
			}

			$variables = array(
				'Heading' 			=> GetLang('SegmentFormTitleEdit'),
				'Intro'				=> GetLang('SegmentFormTitleEditIntro'),
				'Message'			=> $message
			);

			return $this->_getEditor($parameter['user'], $segment, $variables);
		}

		/**
		 * _CopySegment
		 * Copy a segment
		 *
		 * @param Array $parameter Any parameters that need to be parsed to this function
		 * @return String Returns response string that can be outputted to the browser
		 *
		 * @uses Segment::_getGETRequest()
		 * @uses Segment::_ManageSegment()
		 * @uses Users_API::CanCreateSegment()
		 * @uses Users_API::_checkPermissionCanEdit()
		 * @uses SendStudio_Functions::GetApi()
		 * @uses Segment_API::Copy()
		 *
		 * @access private
		 */
		function _CopySegment($parameter = array())
		{
			$segmentID = intval($this->_getGETRequest('id', null));

			if (empty($segmentID)) {
				return $this->_ManageSegment($parameter);
			}

			$segment = $this->GetApi('Segment');
			$segment->Load($segmentID);

			if (!$parameter['user']->CanCreateSegment() || !$this->_checkPermissionCanEdit($segment, $parameter['user'])) {
				return $this->_ManageSegment($parameter);
			}

			$status = $segment->Copy($segmentID);

			if (!$status[0]) {
				$parameter['message'] = GetLang('SegmentManageCopyError');
			} else {
				$parameter['message'] = $this->PrintSuccess('SegmentManageCopySuccess');
			}

			return $this->_ManageSegment($parameter);
		}

		/**
		 * _CreateSegment
		 * Create user interface for creating a segment
		 *
		 * @param Array $parameter Any parameters that need to be parsed to this function
		 * @return String Returns response string that can be outputted to the browser
		 *
		 * @uses Segment::_getEditor()
		 * @uses Segment::_ManageSegment()
		 * @uses Users_API::GetLists()
		 *
		 * @access private
		 */
		function _CreateSegment($parameter = array())
		{
			if (!$parameter['user']->CanCreateSegment()) {
				return $this->_ManageSegment($parameter);
			}

			if (count($parameter['user']->GetLists()) == 0) {
				if ($parameter['user']->CanCreateList()) {
					$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListCreate'));
					$GLOBALS['List_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
				} else {
					$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListAssign'));
					$GLOBALS['List_AddButton'] = '';
				}

				$output = $this->ParseTemplate('Segment_Manage_EmptyList', true);

				unset($GLOBALS['List_AddButton']);
				unset($GLOBALS['Message']);

				return $output;
			}

			return $this->_getEditor($parameter['user'], null, array(
				'Heading'				=> GetLang('SegmentFormTitleCreate'),
				'Intro'					=> GetLang('SegmentFormTitleCreateIntro')
			));
		}

		/**
		 * _DeleteSegment
		 * Delete segment, and then hand over the user interface creation to _ManageSegment function
		 *
		 * @param Array $parameter Any parameters that need to be parsed to this function
		 * @return String Returns response string that can be outputted to the browser
		 *
		 * @uses GetLang()
		 * @uses Segment::_getPOSTRequest()
		 * @uses Segment::_ManageSegment()
		 * @uses Segment::_checkPermissionCanDelete()
		 * @uses Segment_API::Delete()
		 * @uses SendStudio_Functions::GetApi()
		 * @uses SendStudio_Functions::PrintSuccess()
		 * @uses SendStudio_Functions::ParseTemplate()
		 *
		 * @access private
		 */
		function _DeleteSegment($parameter = array())
		{
			$segments = $this->_getPOSTRequest('Segments', null);
			$multipleSegments = (is_array($segments) && count($segments) > 1);
			$noPrivilege = array();
			$api = $this->GetApi('Segment');

			if (!is_null($segments)) {
				/**
				 * Make sure all segment ID is integer and also check user privilege
				 */
					if (!is_array($segments)) {
						$segments = array(intval($segments));
					}

					$userList = $parameter['user']->GetLists();

					foreach ($segments as $tempKey => $tempValue) {
						$segments[$tempKey] = intval($tempValue);
						$api->Load($segments[$tempKey]);

						if (!$this->_checkPermissionCanDelete($api, $parameter['user'])) {
							array_push($noPrivilege, $tempValue);
						}
					}
				/**
				 * -----
				 */

				if (count($noPrivilege) > 0) {
					$GLOBALS['Error'] = GetLang('SegmentManageDeleteErrorNoPrivilege');
					$parameter['message'] = $this->ParseTemplate('ErrorMsg', true, false);
					unset($GLOBALS['Error']);
				} else {
					if ($api->Delete($segments)) {
						$parameter['message'] = $this->PrintSuccess($multipleSegments? 'SegmentManageDeleteSuccessMany' : 'SegmentManageDeleteSuccessOne');
					} else {
						$GLOBALS['Error'] = $multipleSegments? GetLang('SegmentManageDeleteErrorMany') : GetLang('SegmentManageDeleteErrorOne');
						$parameter['message'] = $this->ParseTemplate('ErrorMsg', true, false);
						unset($GLOBALS['Error']);
					}
				}
			}

			return $this->_ManageSegment($parameter);
		}

		/**
		 * _SaveSegment
		 * Save segment, and then hand over the user interfasce creation to _ManageSegment function
		 * Save segment is a generic function where you can "Create" new segment or "Save" edited segment
		 *
		 * @param Array $parameter Any parameters that need to be parsed to this function
		 * @return String Returns response string that can be outputted to the browser
		 *
		 * @uses SendStudio_Functions::GetApi()
		 * @uses Segment::_getPOSTRequest()
		 * @uses Segment::_ManageSegment()
		 * @uses Segment_API
		 * @uses Segment_API::Load()
		 *
		 * @access private
		 */
		function _SaveSegment($parameter = array())
		{
			if (count($parameter['user']->GetLists()) == 0) {
				return $this->_ManageSegment($parameter);
			}

			$segment = $this->GetApi('Segment');
			$segmentID = intval($this->_getPOSTRequest('SegmentID', null));
			$segmentName = trim($this->_getPOSTRequest('SegmentName', null));
			$segmentLists = $this->_getPOSTRequest('Lists', null);
			$segmentRules = $this->_getPOSTRequest('Rules', null);


			// Make sure "Segment Name", "Segment Lists" and "Segment Rules" exists in the POST request
			// Otherwise, use might have gotten bere by typing a url
			if (is_null($segmentName) || is_null($segmentLists) || is_null($segmentRules)) {
				return $this->_ManageSegment($parameter);
			}

			/**
			 * Check permission (either "Create" or "Edit" permission),
			 * This section will also load values/set the default values to the "Segment_API" class
			 */
				if ($segmentID == 0) {
					if (!$parameter['user']->CanCreateSegment()) {
						return $this->_ManageSegment($parameter);
					}

					$segment->ownerid = $parameter['user']->userid;
					$segment->createdate = AdjustTime();
				} else {
					if (!$segment->Load($segmentID)) {
						$GLOBALS['Error'] = GetLang('SegmentFormErrorCannotLoadRecord');
						$parameter['message'] = $this->ParseTemplate('ErrorMsg', true, false);
						unset($GLOBALS['Error']);

						return $this->_ManageSegment($parameter);
					}

					if (!$this->_checkPermissionCanEdit($segment, $parameter['user'])) {
						$GLOBALS['Error'] = GetLang('SegmentManageSaveErrorNoPrivilege');
						$parameter['message'] = $this->ParseTemplate('ErrorMsg', true, false);
						unset($GLOBALS['Error']);

						return $this->_ManageSegment($parameter);
					}
				}
			/**
			 * -----
			 */

			$segment->segmentname = $segmentName;
			$segment->searchinfo = array(
				'Lists' => $segmentLists,
				'Rules' => $segmentRules,
			);

			$status = (is_null($segment->segmentid)? $segment->Create() : $segment->Save());
			if (!$status) {
				$GLOBALS['Error'] = GetLang('SegmentFormSaveFailed');
				$tempMessage = $this->ParseTemplate('ErrorMsg', true, false);
				unset($GLOBALS['Error']);

				return $this->_getEditor($parameter['user'], $segment, array(
					'Message'		=> $tempMessage,
					'Heading' 		=> (is_null($segment->segmentid)? GetLang('SegmentFormTitleCreate') : GetLang('SegmentFormTitleEdit')),
					'Intro'			=> (is_null($segment->segmentid)? GetLang('SegmentFormTitleCreateIntro') : GetLang('SegmentFormTitleEditIntro'))
				));
			} else {
				$parameter['message'] = $this->PrintSuccess('SegmentFormSaveSuccess');
				return $this->_ManageSegment($parameter);
			}
		}

		/**
		 * _DoAjaxRequest
		 * Return an ajax request based on the request type
		 *
		 * @param Array $parameter Any parameters that need to be parsed to this function
		 * @return String Returns response string that can be outputted to the browser
		 *
		 * @uses GetJSON()
		 * @uses SendStudio_Functions::GetApi()
		 * @uses Segment::_getPOSTRequest()
		 * @uses Segment::_filterAllowedList()
		 * @uses Segment::_getCustomFieldUsedByList()
		 * @uses Segment::_getAvailableLinks()
		 * @uses Segment::_getAvailableCampaigns()
		 * @uses Segment_API::GetSubscribersCount()
		 *
		 * @access private
		 */
		function _DoAjaxRequest($parameter = array())
		{
			$requestType = $this->_getPOSTRequest('ajaxType', null);
			$output = '';

			header("Content-type: text/html; charset=" . SENDSTUDIO_DEFAULTCHARSET);

			// Check for "edit" permssion for the following AJAX Request
			$checkEditForType = array(
				'CustomFieldUsedByList',
				'GetAvailableLinks',
				'GetAvailableCampaigns'
			);

			if (in_array($requestType, $checkEditForType)) {
				if (!$parameter['user']->HasAccess('Segments', 'Edit') && ! $parameter['user']->CanCreateSegment()) {
					return '{}';
				}
			} else {
				if (!$parameter['user']->HasAccess('Segments')) {
					return '{}';
				}
			}

			if (!is_null($requestType)) {
				switch ($requestType) {
					case 'CustomFieldUsedByList':
						$listIDs = $this->_getPOSTRequest('listid', null);

						if (is_array($listIDs)) {
							$listIDs = $this->_filterAllowedList($parameter['user'], $listIDs);
							$output = GetJSON($this->_getCustomFieldUsedByList($listIDs));
						}
					break;

					case 'GetAvailableLinks':
						$listIDs = $this->_getPOSTRequest('listid', null);

						if (is_array($listIDs)) {
							$listIDs = $this->_filterAllowedList($parameter['user'], $listIDs);
							$output = GetJSON($this->_getAvailableLinks($parameter['user'], $listIDs));
						}
					break;

					case 'GetAvailableCampaigns':
						$output = GetJSON($this->_getAvailableCampaigns($parameter['user']));
					break;

					// Get the number of subscribers a segment is describing
					case 'GetSubscriberCount':
						$segmentID = intval($this->_getPOSTRequest('segmentID', null));

						if ($segmentID != 0) {
							$segmentAPI = $this->GetApi('Segment');
							$status = $segmentAPI->GetSubscribersCount($segmentID);
							if ($status === false) {
								$output = GetJSON(array('status' => false, 'output' => 0));
							} else {
								$output = GetJSON(array('status' => true, 'output' => $status));
							}
						}
					break;
				}
			}

			return $output;
		}




		/**
		 * _getEditor
		 * Returns an HTML string of the editor
		 * @param User_API $userAPI Current user API
		 * @param Segment_API $segmentAPI Segment to be displayed
		 * @param Array $variables An associative array of the variables to be put in the editor
		 * @return String Returns an HTML string
		 *
		 * @uses GetJSON()
		 * @uses SendStudio_Functions::ParseTemplate()
		 * @uses Segment::_getCustomFieldUsedByList()
		 * @uses Segment::_getRuleNamesUsed()
		 * @uses Segment::_getAvailableLinks()
		 * @uses Segment::_getAvailableCampaigns()
		 * @uses User_API::GetLists()
		 */
		function _getEditor($userAPI, $segmentAPI = null, $variables = array())
		{
			$existingValues = array();
			$listIDs = array();

			/**
			 * Set initial values if segment API is passed along
			 */
				if (!is_null($segmentAPI)) {
					$tempSearchInfo = $segmentAPI->searchinfo;

					$listIDs = $tempSearchInfo['Lists'];
					$variables['SegmentID'] = $segmentAPI->segmentid;
					$variables['SegmentName'] = htmlspecialchars($segmentAPI->segmentname, ENT_QUOTES, SENDSTUDIO_CHARSET);

					/**
					 * Get rule and convert them to appropriate format accepted by the template
					 */
						$tempRules = array(
							'ruleCache' => $this->_getCustomFieldUsedByList($listIDs),
							'rules' => $segmentAPI->searchinfo['Rules']
						);

						// Get default values for each of the custom fields (if required)
						$tempRuleArray = $this->_getRuleNamesUsed($tempRules['rules']);
						if (in_array('link', $tempRuleArray)) {
							$tempRules['ruleCache']['values']['link'] = $this->_getAvailableLinks($userAPI, $listIDs);
						}

						if (in_array('campaign', $tempRuleArray)) {
							$tempRules['ruleCache']['values']['campaign'] = $this->_getAvailableCampaigns($userAPI);
						}

						$variables['InitialValues'] = addslashes(GetJSON($tempRules));
					/**
					 * -----
					 */

					unset($variables['SegmentAPI']);
				} else {
					$variables['InitialValues'] = '{}';
				}
			/**
			 * -----
			 */

			/**
			 * Get mailing list from database and process list for display
			 */
				$tempList = $userAPI->GetLists();
				$tempSelectList = '';

				foreach ($tempList as $tempEach) {
					$tempSubscriberCount = intval($tempEach['subscribecount']);

					$GLOBALS['ListID'] = intval($tempEach['listid']);
					$GLOBALS['ListName'] = htmlspecialchars($tempEach['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
					$GLOBALS['OtherProperties'] = in_array($GLOBALS['ListID'], $listIDs)? ' selected="selected"' : '';

					if ($tempSubscriberCount == 1) {
						$GLOBALS['ListSubscriberCount'] = GetLang('Subscriber_Count_One');
					} else {
						$GLOBALS['ListSubscriberCount'] = sprintf(GetLang('Subscriber_Count_Many'), $this->FormatNumber($tempSubscriberCount));
					}

					$tempSelectList .= $this->ParseTemplate('Segment_Form_ListRow', true);

					unset($GLOBALS['OtherProperties']);
					unset($GLOBALS['ListSubscriberCount']);
					unset($GLOBALS['ListName']);
					unset($GLOBALS['ListID']);
				}

				$variables['SelectListHTML'] = $tempSelectList;

				// If list is less than 10, use the following formula: list size * 25px for the height
				$tempCount = count($tempList);
				if ($tempCount <= 10) {
					if ($tempCount < 3) {
						$tempCount = 3;
					}
					$variables['SelectListStyle'] = 'height: ' . ($tempCount * 25) . 'px;';
				}
			/**
			 * -----
			 */

			/**
			 * Match type (is not used by the internal API anymore, but is still used
			 * by the UI, so emulate this... It will be replaced by "grouping" in later version??
			 */
				$variables['MatchType_AND'] = ' checked="checked"';
				$variables['MatchType_OR'] = '';
				If (!is_null($segmentAPI) && $segmentAPI->searchinfo['Rules'][0]['connector'] == 'or') {
						$variables['MatchType_AND'] = '';
						$variables['MatchType_OR'] = ' checked="checked"';
				}
			/**
			 *
			 */

			//Get Common UI.DatePicker.Custom_IEM JavaScript
			$variables['CustomDatepickerUI'] = $this->ParseTemplate('UI.DatePicker.Custom_IEM', true);

			/**
			 * Setup GLOBAL variable
			 */
				foreach ($variables as $key => $value) {
					if (array_key_exists($key, $GLOBALS)) {
						$existingValues[$key] = $GLOBALS[$key];
					}

					$GLOBALS[$key] = $value;
				}
			/**
			 * -----
			 */

			$output = $this->ParseTemplate('Segment_Form', true);

			/**
			 * Restore GLOBAL variable to it's original state
			 */
				foreach (array_keys($variables) as $key) {
					if (array_key_exists($key, $existingValues)) {
						$GLOBALS[$key] = $existingValues[$key];
					} else {
						unset($GLOBALS[$key]);
					}
				}
			/**
			 * -----
			 */

			return $output;
		}

		/**
		 * _getCustomFieldUsedByList
		 * Get custom fields that are used by the list
		 * @param Array $listIDs An array of list ID
		 * @return Array Returns an associated array of the custom fields representation
		 *
		 * @uses SendStudio_Functions::GetApi()
		 * @uses Lists_API::GetCustomFields()
		 * @uses Segment::$_cacheCustomFieldsUsedByLists
		 *
		 * @access private
		 */
		function _getCustomFieldUsedByList($listIDs)
		{
			$cacheid = implode(':', $listIDs);

			if (!array_key_exists($cacheid, $this->_cacheCustomFieldsUsedByLists)) {
				$listapi = $this->GetApi('Lists');

				$tempOutput = array(
					'list' => array(),
					'customfields' => array(),
					'values' => array()
				);

				foreach ($listIDs as $tempID) {
					$tempStatus = $listapi->GetCustomFields($tempID);

					if (!array_key_exists($tempID, $tempOutput['list'])) {
						$tempOutput['list'][$tempID] = array();
					}

					foreach ($tempStatus as $tempEach) {
						array_push($tempOutput['list'][$tempID], $tempEach['fieldid']);

						/**
						 * Get list of custom fields
						 */
							if (!array_key_exists($tempEach['fieldid'], $tempOutput['customfields'])) {
								$tempFieldType = 'text';

								switch ($tempEach['fieldtype']) {
									case 'date':
										$tempFieldType = 'date';
									break;

									case 'number':
										$tempFieldType = 'number';
									break;

									case 'checkbox':
										$tempFieldType = 'multiple';
									break;

									case 'radiobutton':
									case 'dropdown':
										$tempFieldType = 'dropdown';
									break;
								}

								$tempOutput['customfields'][$tempEach['fieldid']] = array(
									'name' => htmlspecialchars($tempEach['name'], ENT_QUOTES, SENDSTUDIO_CHARSET),
									'fieldtype' => $tempEach['fieldtype'],
									'defaultvalue' => $tempEach['defaultvalue'],
									'operatortype' => $tempFieldType
								);
							}
						/**
						 * -----
						 */

						/**
						 * Get list of values the custom field uses
						 */
							if (!array_key_exists($tempEach['fieldid'], $tempOutput['values'])) {
								$tempFieldValues = array();
								$temp = unserialize($tempEach['fieldsettings']);
								if (is_array($temp) && array_key_exists('Key', $temp) && array_key_exists('Value', $temp)) {
									foreach ($temp['Key'] as $index => $value) {
										array_push($tempFieldValues, array(
											'value' => $value,
											'text' => htmlspecialchars($temp['Value'][$index], ENT_QUOTES, SENDSTUDIO_CHARSET)
										));
									}
								}

								if (count($tempFieldValues) != 0) {
									$tempOutput['values'][$tempEach['fieldid']] = $tempFieldValues;
								}
							}
						/**
						 * -----
						 */
					}

				}

				if (count($tempOutput['list']) == 0) {
					$tempOutput['list'] = null;
				}

				if (count($tempOutput['customfields']) == 0) {
					$tempOutput['customfields'] = null;
				}

				if (count($tempOutput['values']) == 0) {
					$tempOutput['values'] = null;
				}

				$this->_cacheCustomFieldsUsedByLists[$cacheid] = $tempOutput;
			}

			return $this->_cacheCustomFieldsUsedByLists[$cacheid];
		}

		/**
		 * _getAvailableLinks
		 * Get available links associated with specified mailing list
		 *
		 * @param User_API $userapi User API
		 * @param Array $listIDs Fetch available links from these lists
		 * @return Array Returns an associated array of available links representation
		 *
		 * @uses GetLang()
		 * @uses Users_API::GetAvailableLinks()
		 *
		 * @access private
		 */
		function _getAvailableLinks($userapi, $listIDs)
		{
			$links = $userapi->GetAvailableLinks($listIDs);
			$return = array(
				array('value' => '-1', 'text' => GetLang('FilterAnyLink'))
			);

			foreach ($links as $linkid => $linkurl) {
				array_push($return, array('value' => $linkid, 'text' => $this->TruncateInMiddle($linkurl, 80), 'title' => $linkurl));
			}

			return $return;
		}

		/**
		 * _getAvailableCampaigns
		 * Get available campaigns
		 *
		 * @param User_API $userapi User API
		 * @return Array Returns an associated array of available campaigns representation
		 *
		 * @uses GetLang()
		 *
		 * @access private
		 */
		function _getAvailableCampaigns($userapi)
		{
			$campaigns = $userapi->GetAvailableNewsletters(false);
			$return = array(
				array('value' => '-1', 'text' => GetLang('FilterAnyNewsletter'))
			);

			foreach ($campaigns as $campaignid => $campaignname) {
				array_push($return, array('value' => $campaignid, 'text' => $campaignname));
			}

			return $return;
		}

		/**
		 * _filterAllowedList
		 * Returns an array of list ID that is available to be accessed by a user
		 * @param User_API $userapi Current user API
		 * @param Array $listIDs List ID to be filtered
		 * @return Array Return filtered list IDs
		 *
		 * @uses Segment::$_cacheUserAvailableListIDs
		 *
		 * @access private
		 */
		function _filterAllowedList($userapi, $listIDs)
		{
			// User must be loaded to the API
			if (empty($userapi->userid)) {
				return array();
			}

			if (!array_key_exists($userapi->userid, $this->_cacheUserAvailableListIDs)) {
				$tempAvailableList = $userapi->GetLists();
				$tempAllowed = array();

				foreach ($tempAvailableList as $tempEach) {
					if (in_array($tempEach['listid'], $listIDs)) {
						array_push($tempAllowed, $tempEach['listid']);
					}
				}

				$this->_cacheUserAvailableListIDs[$userapi->userid] = $tempAllowed;
			}

			return $this->_cacheUserAvailableListIDs[$userapi->userid];
		}

		/**
		 * _checkPermissionCanDelete
		 * Check whether or not a user can delete a segment
		 *
		 * Checking user privilege in this instance will also means checking
		 * whether or not a user have access to all mailing list used in a segment.
		 * Once lists used in a segment become "restricted" to a user, user should not be able to delete
		 * the segment at all.
		 *
		 * Here's the logic:
		 * (1) If Admin go to (7), otherwise go to (2)
		 * (2) If segment is owned by user, go to (3), otherwise go (4)
		 * (3) If user have "delete" permission, go to (7), otherwise (6)
		 * (4) If user is allowed to have "delete" access to the segment, then check (5), otherwise go (7)
		 * (5) If user DO NOT have access to all the lists in the segment, go (6), otherwise go (7)
		 * (6) CANNOT DELETE
		 * (7) CAN DELETE
		 *
		 * @param Segment_API $segmentapi Current segment API
		 * @param User_API $userapi Current user API
		 *
		 * @return Boolean Returns TRUE if user have delete privilege on segment, FALSE otherwise
		 *
		 * @uses User_API::HasAccess()
		 * @uses User_API::GetLists()
		 *
		 * @access private
		 */
		function _checkPermissionCanDelete($segmentapi, $userapi)
		{
			if ($userapi->Admin()) {
				return true;
			}

			$haveAccess = false;
			$userList = array_keys($userapi->GetLists());

			if ($segmentapi->ownerid == $userapi->userid) {
				if ($userapi->HasAccess('Segments', 'Delete')) {
					$haveAccess = true;
				}
			} else {
				if ($userapi->HasAccess('Segments', 'Delete', $segmentapi->segmentid)) {
					if (count(array_intersect($userList, $segmentapi->searchinfo['Lists'])) == count($segmentapi->searchinfo['Lists'])) {
						$haveAccess = true;
					}
				}
			}

			return $haveAccess;
		}

		/**
		 * _checkPermissionCanEdit
		 * Check whether or not a user can edit a segment
		 *
		 * Checking user privilege in this instance will also means checking
		 * whether or not a user have access to all mailing list used in a segment.
		 * Once lists used in a segment become "restricted" to a user, user should not be able to edit
		 * the segment at all.
		 *
		 * Here's the logic:
		 * (1) If Admin go to (7), otherwise go to (2)
		 * (2) If segment is owned by user, go to (3), otherwise go (4)
		 * (3) If user have "edit" permission, go to (7), otherwise (6)
		 * (4) If user is allowed to have "edit" access to the segment, then check (5), otherwise go (7)
		 * (5) If user DO NOT have access to all the lists in the segment, go (6), otherwise go (7)
		 * (6) CANNOT EDIT
		 * (7) CAN EDIT
		 *
		 * @param Segment_API $segmentapi Current segment API
		 * @param User_API $userapi Current user API
		 *
		 * @return Boolean Returns TRUE if user have edit privilege on segment, FALSE otherwise
		 *
		 * @uses User_API::HasAccess()
		 * @uses User_API::GetLists()
		 *
		 * @access private
		 */
		function _checkPermissionCanEdit($segmentapi, $userapi)
		{
			if ($userapi->Admin()) {
				return true;
			}

			$haveAccess = false;
			$userList = array_keys($userapi->GetLists());

			if ($segmentapi->ownerid == $userapi->userid) {
				if ($userapi->HasAccess('Segments', 'Edit')) {
					$haveAccess = true;
				}
			} else {
				if ($userapi->HasAccess('Segments', 'Edit', $segmentapi->segmentid)) {
					if (count(array_intersect($userList, $segmentapi->searchinfo['Lists'])) == count($segmentapi->searchinfo['Lists'])) {
						$haveAccess = true;
					}
				}
			}

			return $haveAccess;
		}

		/**
		 * _getRuleNamesUsed
		 * Get a list of rule names that is used in the segment
		 * @param Array $rules Segment rules
		 * @return Array Retuns an array of string of "Rule Name" used
		 *
		 * @uses Segment::_getRuleNamesUsed()
		 *
		 * @access private
		 */
		function _getRuleNamesUsed($rules)
		{
			$ruleNames = array();

			for ($i = 0, $j = count($rules); $i < $j; ++$i) {
				$each = $rules[$i];
				if (!array_key_exists('type', $each) || !array_key_exists('rules', $each)) {
					continue;
				}

				switch ($each['type']) {
					// If rule type is a "group", recurse
					case 'group':
						$ruleNames = array_merge($ruleNames, $this->_getRuleNamesUsed($each['rules']));
					break;

					// If rule type is "rule", process it
					case 'rule':
						array_push($ruleNames, $each['rules']['ruleName']);
					break;
				}
			}

			return $ruleNames;
		}
	/**
	 * -----
	 */
}
