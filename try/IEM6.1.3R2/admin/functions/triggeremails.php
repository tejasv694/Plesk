<?php
/**
 * Triggers
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
 * Class for Triggers
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */
class TriggerEmails extends SendStudio_Functions
{
	/**
	 * Cache of custom fields that are being used by list
	 * @var Array An array of custom field lists
	 */
	static private $_cacheCustomFieldsUsedByLists = array();

	/**
	 * Cache of available list for users
	 * @var Array An array of list ID that is available for users
	 */
	static private $_cacheUserAvailableListIDs = array();


	/**
	 * Constructor
	 * @return Triggers Return new instance of this class
	 *
	 * @uses SendStudio_Functions::LoadLanguageFile()
	 */
	public function __construct()
	{
		$this->LoadLanguageFile();
		$this->_DefaultSort = 'TriggerName';
	}

	/**
	 * Process
	 * This handles working out what stage you are up to and so on with workflow.
	 * @return Void Does not return anything
	 *
	 * @uses GetUser()
	 * @uses User_API::HasAccess()
	 * @uses SendStudio_Functions::PrintHeader()
	 * @uses SendStudio_Functions::DenyAccess()
	 * @uses SendStudio_Functions::PrintFooter()
	 * @uses SendStudio_Functions::_getGETRequest()
	 * @uses TriggerEmails::_ajax()
	 * @uses TriggerEmails::_create()
	 * @uses TriggerEmails::_edit()
	 * @uses TriggerEmails::_copy()
	 * @uses TriggerEmails::_enable()
	 * @uses TriggerEmails::_disable()
	 * @uses TriggerEmails::_save()
	 * @uses TriggerEmails::_delete()
	 * @uses TriggerEmails::_bulkAction()
	 * @uses TriggerEmails::_manage()
	 */
	public function Process()
	{
		// ----- Define and sanitize "common" variables that is used by this function
			$user = GetUser();

			$reqAction		= IEM::requestGetGET('Action', '', 'strtolower');
			$response		= '';
			$parameters 	= array();

			$parameters['message']	= GetFlashMessages();
			$parameters['user']		= GetUser();
			$parameters['action']	= $reqAction;
		// ------


		// ----- Check basic permission
			$access = $user->HasAccess('triggeremails') && check('Triggermails');
			if (!$access) {
				$this->PrintHeader();
				$this->DenyAccess();
				$this->PrintFooter();
				return;
			}
		// ------

		if (!SENDSTUDIO_CRON_ENABLED || SENDSTUDIO_CRON_TRIGGEREMAILS_S <= 0 || SENDSTUDIO_CRON_TRIGGEREMAILS_P <= 0) {
			$parameters['message'] .= $this->PrintWarning('TriggerEmails_Manage_CRON_Alert');
		}

		switch ($reqAction) {
			// AJAX request
			case 'ajax':
				$response = $this->_ajax($parameters);
			break;

			// Show "create" form
			case 'create':
				$response = $this->_create($parameters);
			break;

			// Show "edit" form
			case 'edit':
				$response = $this->_edit($parameters);
			break;

			// Copy trigger record
			case 'copy':
				$response = $this->_copy($parameters);
			break;

			// Enable record
			case 'enable':
				$response = $this->_enable($parameters);
			break;

			// Disable record
			case 'disable':
				$response = $this->_disable($parameters);
			break;

			// Save trigger record (from "create"/"edit" form)
			case 'save':
				$response = $this->_save($parameters);
			break;

			// Delete trigger record
			case 'delete':
				$response = $this->_delete($parameters);
			break;

			// Handle bulk action
			case 'bulkaction':
				$response = $this->_bulkAction($parameters);
			break;

			case 'processpaging':
			default:
				$response = $this->_manage($parameters);
			break;
		}


		// ----- Print output
			$ajax = ($reqAction == 'ajax');

			if (!$ajax) {
				$this->PrintHeader();
			} else {
				header('Content-type: application/json');
			}

			echo $response;

			if (!$ajax) {
				$this->PrintFooter();
			}
		// -----
	}



	/**
	 * _ajax
	 * Handle ALL AJAX requests for Trigger Emails functionalities.
	 *
	 * The function act as a "controller" that re-direct all ajax requests to the appropriate functions.
	 * It will return a text that can be converted to JSON javascript notation.
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 */
	private function _ajax($parameters = array())
	{
		$requestType = IEM::requestGetPOST('ajaxType', '', 'trim');

		if (empty($requestType)) {
			return "{status:false, data:'Ajax Type cannot be empty'}";
		}

		if (!is_callable(array($this, '_ajax_' . $requestType))) {
			return "{status:false, data:'Invalid Ajax Type'}";
		}

		$callableName = "_ajax_{$requestType}";
		list($status, $data) = $this->$callableName($parameters);

		return GetJSON(array('status' => $status, 'data' => $data));
	}

	/**
	 * _ajax_listlinksfornewsletters
	 * Return a list of links in a newsletter
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return Array Returns an array containing a response status and the data payload
	 */
	private function _ajax_listlinksfornewsletters($parameters = array())
	{
		$newsletterid = IEM::requestGetPOST('newsletterid', 0, 'intval');

		if ($newsletterid == 0) {
			return array(false, 'Newsletter ID cannot be empty');
		}

		$links = $this->_getLinksFormNewsletter($newsletterid);
		if (!is_array($links)) {
			return array(false, 'Cannot fetch links');
		}

		return array(true, array(strval($newsletterid) => $links));
	}

	/**
	 * _manage
	 * Manage trigger page
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::GetCurrentPage()
	 * @uses SendStudio_Functions::GetSortDetails()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses SendStudio_Functions::_getGETRequest()
	 * @uses SendStudio_Functions::SetPerPage()
	 * @uses SendStudio_Functions::GetPerPage()
	 * @uses SendStudio_Functions::SetupPaging()
	 * @uses SendStudio_Functions::PrintSuccess()
	 * @uses GetFlashMessages()
	 * @uses GetLang()
	 * @uses GetTemplateSystem()
	 * @uses TriggerEmails_API::GetRecordsByUserID()
	 * @uses User_API::HasAccess()
	 */
	private function _manage($parameters = array())
	{
		/**
		 * Sanitize and declare variables that is going to be used in this function
		 */
			$pageRecordPP		= 0;
			$pageCurrentIndex	= $this->GetCurrentPage();
			$pageSortInfo		= $this->GetSortDetails();

			$records			= array();
			$recordTotal		= 0;
			$listCount			= 0;
			$newsletterCount	= 0;

			$api				= $this->GetApi();

			$userID				= $parameters['user']->userid;

			$page = array(
				'messages'	=> $parameters['message']
			);

			$permissions = array(
				'create'			=> $parameters['user']->HasAccess('triggeremails', 'create'),
				'edit'				=> $parameters['user']->HasAccess('triggeremails', 'edit'),
				'delete'			=> $parameters['user']->HasAccess('triggeremails', 'delete'),
				'activate'			=> $parameters['user']->HasAccess('triggeremails', 'activate'),
				'createList'		=> true,
				'createNewsletter'	=> true
			);
		/**
		 * -----
		 */


		/**
		 * Get list and newsletter count
		 */
			$tempListRecords = $parameters['user']->GetLists();
			$listCount = count($tempListRecords);
			unset($tempListRecords);

			$tempNewsletterRecords = $parameters['user']->GetNewsletters();
			$newsletterCount = count($tempNewsletterRecords);
			unset($tempNewsletterRecords);
		/**
		 * -----
		 */


		/**
		 * Get "Record Per Page"
		 */
			if ($parameters['action'] == 'processpaging') {
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

		$start = 0;
		if ($pageRecordPP != 'all') {
			$start = ($pageCurrentIndex - 1) * $pageRecordPP;
		}

		if ($parameters['user']->isAdmin()) {
			$records     = $api->GetRecords($pageSortInfo, false, $start, $pageRecordPP);
			$recordTotal = $api->GetRecords(array(), true);
		} else {
			$records     = $api->GetRecordsByUserID($parameters['user']->userid, $pageSortInfo, false, $start, $pageRecordPP);
			$recordTotal = $api->GetRecordsByUserID($parameters['user']->userid, array(), true);
		}

		/**
		 * Calculate pagination, this is using the older method of pagination
		 */
			$GLOBALS['PAGE'] = 'TriggerEmails';
			$GLOBALS['FormAction'] = 'Action=ProcessPaging';

			$this->SetupPaging($recordTotal, $pageCurrentIndex, $pageRecordPP);
		/**
		 * -----
		 */

		/**
		 * Display relevant messages for users
		 */
			// Check if newsletters and contact lists are available (At least 1 newsletter and 1 list need to exits)
			if ($newsletterCount == 0 || $listCount == 0) {
				if ($listCount == 0) {
					$tempMessage = 'TriggerEmails_Manage_NoLists_AskAdmin';

					if ($permissions['createList']) {
						$tempMessage = 'TriggerEmails_Manage_NoLists_CanCreate';
					}

					$page['messages'] .= $this->PrintSuccess('TriggerEmails_Manage_NoLists', GetLang($tempMessage));

					unset($tempMessage);
				}

				if ($newsletterCount == 0) {
					$tempMessage = 'TriggerEmails_Manage_NoCampaigns_AskAdmin';

					if ($permissions['createNewsletter']) {
						$tempMessage = 'TriggerEmails_Manage_NoCampaigns_CanCreate';
					}

					$page['messages'] .= $this->PrintSuccess('TriggerEmails_Manage_NoCampaigns', GetLang($tempMessage));

					unset($tempMessage);
				}



			// If newsletters and contact lists exists, check if there are any trigger records in the system
			} else {
				if ($recordTotal == 0) {
					if ($permissions['create']) {
						$page['messages'] .= $this->PrintSuccess('TriggerEmails_Manage_NoRecords', GetLang('TriggerEmails_Manage_NoRecords_CreateRecord'));
					} else {
						$page['messages'] .= $this->PrintSuccess('TriggerEmails_Manage_NoRecords', GetLang('TriggerEmails_Manage_NoRecords_AskAdmin'));
					}
				}
			}
		/**
		 * -----
		 */

		/**
		 * Return HTML
		 */
			$tpl = GetTemplateSystem();
			$tpl->Assign('PAGE', $page);
			$tpl->Assign('records', $records);
			$tpl->Assign('permissions', $permissions);
			$tpl->Assign('newsletterCount', $newsletterCount);
			$tpl->Assign('listCount', $listCount);

			return $tpl->ParseTemplate('TriggerEmails_Manage', true);
		/**
		 * -----
		 */
	}


	/**
	 * _edit
	 * Display the editor page foe editing existing record
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::_getGETRequest()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses TriggerEmails_API::GetRecordByID()
	 * @uses TriggerEmails::_manage()
	 * @uses TriggerEmails::_getEditor()
	 * @uses FlashMessage()
	 *
	 * @test permission
	 */
	private function _edit($parameters = array())
	{
		if (!$parameters['user']->HasAccess('triggeremails', 'edit')) {
			$this->DenyAccess();
			exit();
		}

		$id = intval($this->_getGETRequest('id', 0));

		if ($id == 0) {
			return $this->_manage($parameters);
		}

		$api = $this->GetApi();

		if (!$parameters['user']->Admin() && !$api->IsOwner($id, $parameters['user']->userid)) {
			$this->DenyAccess();
			exit();
		}

		$record = $api->GetRecordByID($id);
		if ($record === false || empty($record)) {
			FlashMessage(GetLang('TriggerEmails_Cannot_Load_Record'), SS_FLASH_MSG_ERROR, 'index.php?Page=TriggerEmails');
		}

		// ----- Fetch trigger data
			$tempData = $api->GetData($id);
			if ($tempData === false) {
				FlashMessage(GetLang('TriggerEmails_Cannot_Load_Record'), SS_FLASH_MSG_ERROR, 'index.php?Page=TriggerEmails');
			}

			if (array_key_exists($id, $tempData)) {
				$record['data'] = $tempData[$id];
			}

			unset($tempData);
		// -----

		// ----- Fetch trigger actions
			$tempActions = $api->GetActions($id);
			if ($tempActions === false) {
				FlashMessage(GetLang('TriggerEmails_Cannot_Load_Record'), SS_FLASH_MSG_ERROR, 'index.php?Page=TriggerEmails');
			}

			if (array_key_exists($id, $tempActions)) {
				$record['triggeractions'] = $tempActions[$id];
			}

			unset($tempActions);
		// -----

		// Log this to "User Activity Log"
		IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/triggeremails_view.gif', $record['name']);

		return $this->_getEditor($parameters, $record);
	}

	/**
	 * _create
	 * Display the editor page for adding/creating new record
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 *
	 * @uses TriggerEmails::_getEditor()
	 */
	private function _create($parameters = array())
	{
		if (!$parameters['user']->HasAccess('triggeremails', 'create')) {
			$this->DenyAccess();
			exit();
		}

		/**
		 * At least 1 contact list and 1 newsletter must be available in the system
		 */
			$tempListRecord = $parameters['user']->GetLists();
			$tempNewsletterRecord = $parameters['user']->GetNewsletters();

			if (count($tempListRecord) == 0 || count($tempNewsletterRecord) == 0) {
				return $this->_manage($parameters);
			}

			unset($tempNewsletterRecord);
			unset($tempListRecord);
		/**
		 * -----
		 */

		return $this->_getEditor($parameters);
	}

	/**
	 * _copy
	 * Copy a record
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::_getPOSTRequest()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses TriggerEmails_API::Copy()
	 * @uses TriggerEmails::_manage()
	 * @uses FlashMessage()
	 */
	private function _copy($parameters = array())
	{
		if (!$parameters['user']->HasAccess('triggeremails', 'create')) {
			$this->DenyAccess();
			exit();
		}

		$recordID = intval($this->_getPOSTRequest('id', 0));

		if ($recordID == 0) {
			FlashMessage(GetLang('TriggerEmails_Cannot_Invalid_ID'), SS_FLASH_MSG_ERROR);
			return $this->_manage($parameters);
		}

		$api = $this->GetApi();

		if (!$parameters['user']->Admin() && !$api->IsOwner($recordID, $parameters['user']->userid)) {
			$this->DenyAccess();
			exit();
		}

		$newid = $api->Copy($recordID);
		if (!$newid) {
			FlashMessage(GetLang('TriggerEmails_Manage_Copy_Failed'), SS_FLASH_MSG_ERROR);
			return $this->_manage($parameters);
		}

		$api->Load($newid);
		FlashMessage(sprintf(GetLang('TriggerEmails_Manage_Copy_Success'), $api->name), SS_FLASH_MSG_SUCCESS, 'index.php?Page=TriggerEmails');
		exit();
	}

	/**
	 * _enable
	 * Mark a trigger record as active
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::_getPOSTRequest()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses TriggerEmails_API::RecordActivate()
	 * @uses TriggerEmails::_manage()
	 * @uses FlashMessage()
	 */
	private function _enable($parameters = array())
	{
		if (!$parameters['user']->HasAccess('triggeremails', 'activate')) {
			$this->DenyAccess();
			exit();
		}

		$recordID = intval($this->_getPOSTRequest('id', 0));

		if ($recordID == 0) {
			FlashMessage(GetLang('TriggerEmails_Cannot_Invalid_ID'), SS_FLASH_MSG_ERROR);
			return $this->_manage($parameters);
		}

		$api = $this->GetApi();

		if (!$parameters['user']->Admin() && !$api->IsOwner($recordID, $parameters['user']->userid)) {
			$this->DenyAccess();
			exit();
		}

		if (!$api->RecordActivate($recordID)) {
			FlashMessage(GetLang('TriggerEmails_Manage_Activate_Failed'), SS_FLASH_MSG_ERROR);
			return $this->_manage($parameters);
		}

		FlashMessage(GetLang('TriggerEmails_Manage_Activate_Success'), SS_FLASH_MSG_SUCCESS, 'index.php?Page=TriggerEmails');
		exit();
	}

	/**
	 * _disable
	 * Mark a trigger record as inactive
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::_getPOSTRequest()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses TriggerEmails_API::RecordDeactivate()
	 * @uses TriggerEmails::_manage()
	 * @uses FlashMessage()
	 */
	private function _disable($parameters = array())
	{
		if (!$parameters['user']->HasAccess('triggeremails', 'activate')) {
			$this->DenyAccess();
			exit();
		}

		$recordID = intval($this->_getPOSTRequest('id', 0));

		if ($recordID == 0) {
			FlashMessage(GetLang('TriggerEmails_Cannot_Invalid_ID'), SS_FLASH_MSG_ERROR);
			return $this->_manage($parameters);
		}

		$api = $this->GetApi();

		if (!$parameters['user']->Admin() && !$api->IsOwner($recordID, $parameters['user']->userid)) {
			$this->DenyAccess();
			exit();
		}

		if (!$api->RecordDeactivate($recordID)) {
			FlashMessage(GetLang('TriggerEmails_Manage_Deactivate_Failed'), SS_FLASH_MSG_ERROR);
			return $this->_manage($parameters);
		}

		FlashMessage(GetLang('TriggerEmails_Manage_Deactivate_Success'), SS_FLASH_MSG_SUCCESS, 'index.php?Page=TriggerEmails');
		exit();
	}

	/**
	 * _save
	 * Save record (edit/create)
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns response string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::_getPOSTRequest()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses TriggerEmails_API::Save()
	 * @uses TriggerEmails::_getEditor()
	 * @uses FlashMessage()
	 */
	private function _save($parameters = array())
	{
		if (IEM::requestGetPOST('ProcessThis', 0, 'intval') != 1) {
			return $this->_manage($parameters);
		}

		$api = $this->GetApi();
		$record = IEM::requestGetPOST('record', array());

		if (empty($record['triggeremailsid'])) {
			if (!$parameters['user']->HasAccess('triggeremails', 'create')) {
				$this->DenyAccess();
				exit();
			}
		} else {
			if (!$parameters['user']->HasAccess('triggeremails', 'edit')) {
				$this->DenyAccess();
				exit();
			}

			if (!$parameters['user']->Admin() && !$api->IsOwner($record['triggeremailsid'], $parameters['user']->userid)) {
				$this->DenyAccess();
				exit();
			}
		}

		// If triggeremailsid is specified, load the record from database,
		// if error is encountered, flash error message, and return to the editor page
		if (!empty($record['triggeremailsid'])) {
			$status = $api->Load(intval($record['triggeremailsid']));
			if (!$status) {
				FlashMessage(GetLang('TriggerEmails_Cannot_Load_Record'), SS_FLASH_MSG_ERROR);
				return $this->_getEditor($parameters, $record);
			}
		}

		// Check permission for parameters entered in
		if (!$this->_checkUserResourcePermission($record, $parameters['user'])) {
			$GLOBALS['Error'] = GetLang('TriggerEmails_Form_Save_Failed_Permission');
			$parameters['message'] .= $this->ParseTemplate('errormsg', true);
			unset($GLOBALS['Error']);

			return $this->_getEditor($parameters, $record);
		}

		// Overwrite bounce email if user can't specify their own
		if (!$parameters['user']->HasAccess('Lists', 'BounceSettings') && isset($record['triggeractions']) && isset($record['triggeractions']['send']) && isset($record['triggeractions']['send']['enabled']) && $record['triggeractions']['send']['enabled']) {
			$record['triggeractions']['send']['bounceemail'] = SENDSTUDIO_BOUNCE_ADDRESS;
		}

		// Populate the API
		foreach ($record as $property => $value) {
			if ($property != 'triggeremailsid') {
				$api->{$property} = $value;
			}
		}

		// Set up owner ID
		$api->ownerid = $parameters['user']->userid;

		// Save
		$triggerid = $api->Save();
		if ($triggerid === false) {
			$GLOBALS['Error'] = GetLang('TriggerEmails_Form_Save_Failed');
			$parameters['message'] .= $this->ParseTemplate('errormsg', true);
			unset($GLOBALS['Error']);

			return $this->_getEditor($parameters, $record);
		} else {
			FlashMessage(GetLang('TriggerEmails_Form_Save_Success'), SS_FLASH_MSG_SUCCESS, 'index.php?Page=TriggerEmails');
			return $this->_manage($parameters);
		}
	}

	/**
	 * _delete
	 * Delete a record.
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns an HTML string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::_getPOSTRequest()
	 * @uses TriggerEmails::_manage()
	 * @uses SS_FLASH_MSG_SUCCESS
	 * @uses SS_FLASH_MSG_ERROR
	 * @uses FlashMessage()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses TriggerEmails_API::Delete()
	 */
	private function _delete($parameters)
	{
		if (!$parameters['user']->HasAccess('triggeremails', 'delete')) {
			$this->DenyAccess();
			exit();
		}

		$id = intval($this->_getPOSTRequest('id', 0));

		// Check if user got here accidentally
		if ($id == 0) {
			return $this->_manage($parameters);
		}

		$api = $this->GetApi();

		if (!$parameters['user']->Admin() && !$api->IsOwner($id, $parameters['user']->userid)) {
			$this->DenyAccess();
			exit();
		}

		if (!$api->Delete($id)) {
			FlashMessage(GetLang('TriggerEmails_Manage_Delete_Failed'), SS_FLASH_MSG_ERROR, 'index.php?Page=TriggerEmails');
		} else {
			FlashMessage(GetLang('TriggerEmails_Manage_Delete_Success'), SS_FLASH_MSG_SUCCESS, 'index.php?Page=TriggerEmails');
		}

		exit();
	}

	/**
	 * _bulkAction
	 * Perform an action on multiple records
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function (OPTIONAL)
	 * @return String Returns an HTML string that can be outputted to the browser
	 *
	 * @uses SendStudio_Functions::_getPOSTRequest()
	 * @uses SendStudio_Functions::GetApi()
	 * @uses TriggerEmails::_manage()
	 * @uses SS_FLASH_MSG_SUCCESS
	 * @uses SS_FLASH_MSG_ERROR
	 * @uses FlashMessage()
	 * @uses TriggerEmails_API::DeleteMultiple()
	 * @uses TriggerEmails_API::RecordActivateMultiple()
	 * @uses TriggerEmails_API::RecordDeactivateMultiple()
	 */
	private function _bulkAction($parameters)
	{
		$which = $this->_getPOSTRequest('Which', '');
		$ids = $this->_getPOSTRequest('IDs', array());

		if (empty($ids)) {
			return $this->_manage($parameters);
		}

		$msg = '';
		$status = SS_FLASH_MSG_SUCCESS;
		$redirect = 'index.php?Page=TriggerEmails';

		$api = $this->GetApi();

		if (!$parameters['user']->Admin() && !$api->IsOwner($ids, $parameters['user']->userid)) {
			$this->DenyAccess();
			exit();
		}

		switch ($which) {
			case 'delete':
				if (!$parameters['user']->HasAccess('triggeremails', 'delete')) {
					$this->DenyAccess();
					exit();
				}

				if ($api->DeleteMultiple($ids)) {
					$msg = GetLang('TriggerEmails_Manage_Bulk_Delete_Success');
					$status = SS_FLASH_MSG_SUCCESS;
				} else {
					$msg = GetLang('TriggerEmails_Manage_Bulk_Delete_Failed');
					$status = SS_FLASH_MSG_ERROR;
				}
			break;

			case 'activate':
				if (!$parameters['user']->HasAccess('triggeremails', 'activate')) {
					$this->DenyAccess();
					exit();
				}

				if ($api->RecordActivateMultiple($ids)) {
					$msg = GetLang('TriggerEmails_Manage_Bulk_Activate_Success');
					$status = SS_FLASH_MSG_SUCCESS;
				} else {
					$msg = GetLang('TriggerEmails_Manage_Bulk_Activate_Failed');
					$status = SS_FLASH_MSG_ERROR;
				}
			break;

			case 'deactivate':
				if (!$parameters['user']->HasAccess('triggeremails', 'delete')) {
					$this->DenyAccess();
					exit();
				}

				if ($api->RecordDeactivateMultiple($ids)) {
					$msg = GetLang('TriggerEmails_Manage_Bulk_Deactivate_Success');
					$status = SS_FLASH_MSG_SUCCESS;
				} else {
					$msg = GetLang('TriggerEmails_Manage_Bulk_Deactivate_Failed');
					$status = SS_FLASH_MSG_ERROR;
				}
			break;

			default:
				return $this->_manage($parameters);
			break;
		}

		if (!empty($msg)) {
			FlashMessage($msg, $status, $redirect);
			exit();
		}

		return $this->_manage($parameters);
	}

	/**
	 * _getEditor
	 * This will return an HTML of the editor page
	 *
	 * @param Array $parameters Any parameters that need to be parsed to this function
	 * @param Array $record Record that should be displayed as the default value for the form (for editing purpose)
	 * @return String Returns HTML string of the page
	 *
	 * @uses SendStudio_Functions::GetApi()
	 * @uses User_API::AdminType()
	 * @uses User_API::GetLists()
	 * @uses User_API::GetLiveNewsletters()
	 * @uses User_API::GetAvailableLinks()
	 * @uses GetLang()
	 * @uses GetFlashMessages()
	 * @uses TriggerEmails::_getDateCustomFieldUsedByList()
	 * @uses GetTemplateSystem()
	 * @uses InterspireTemplate::Assign()
	 * @uses InterspireTemplate::ParseTemplate()
	 */
	private function _getEditor($parameters, $record = array())
	{
		$newslettrAPI = $this->GetApi('Newsletters');
		$customfieldAPI = $this->GetApi('CustomFields');

		$newsletterowner = ($parameters['user']->Admin() || $parameters['user']->AdminType() == 'n') ? 0 : $parameters['user']->userid;
		$availableLists = $parameters['user']->GetLists();
		$availableNewsletters = $newslettrAPI->GetLiveNewsletters($newsletterowner);
		$availableLinks = array();
		$availableCustomFields = array();
		$availableNameCustomFields = array();
		$allowEmbedImages = SENDSTUDIO_ALLOW_EMBEDIMAGES;
		$allowSetBounceDetails =  $parameters['user']->HasAccess('Lists', 'BounceSettings');
		$options = array();
		$page = array(
			'heading'	=> GetLang('TriggerEmails_Create'),
			'messages'	=> $parameters['message']
		);

		if (!empty($record)) {
			$page['heading'] = GetLang('TriggerEmails_Edit');

			if ($record['triggertype'] == 'l' && isset($record['data']['linkid_newsletterid'])) {
				$temp = $this->_getLinksFormNewsletter($record['data']['linkid_newsletterid']);
				if (is_array($availableLinks)) {
					$availableLinks[$record['data']['linkid_newsletterid']] =  $temp;
				}
			}
		} else {
			// Default values
			$record['active'] = 1;
			$record['triggeractions']['send']['enabled'] = 0;
			$record['triggeractions']['send']['sendfromname'] = $parameters['user']->fullname;
			$record['triggeractions']['send']['sendfromemail'] = $parameters['user']->emailaddress;
			$record['triggeractions']['send']['replyemail'] = $parameters['user']->emailaddress;
			$record['triggeractions']['send']['bounceemail'] = SENDSTUDIO_BOUNCE_ADDRESS;
			$record['triggeractions']['send']['multipart'] = 1;
			$record['triggeractions']['send']['trackopens'] = 1;
			$record['triggeractions']['send']['tracklinks'] = 1;
			$record['triggeractions']['send']['embedimages'] = SENDSTUDIO_DEFAULT_EMBEDIMAGES;
		}

		// ----- Get available "date" and "text" custom fields, and set up the currently available custom fields in the options (if it is in edit mode)
			$tempCustomFields = $customfieldAPI->GetCustomFieldsForLists(array_keys($availableLists), null, array('date', 'text'));
			foreach ($tempCustomFields as $each) {
				if (!array_key_exists($each['listid'], $availableCustomFields)) {
					$availableCustomFields[$each['listid']] = array(
						'date' => array(),
						'text' => array()
					);
				}

				$availableCustomFields[$each['listid']][$each['fieldtype']][$each['fieldid']] = $each;
			}

			if (!empty($availableCustomFields) && isset($record['data']['listid'])  && array_key_exists($record['data']['listid'], $availableCustomFields)) {
				$availableNameCustomFields = $availableCustomFields[$record['data']['listid']]['text'];
			}
			unset($tempCustomFields);
		// -----

		/**
		 * Return HTML
		 */
			$tpl = GetTemplateSystem();
			$tpl->Assign('PAGE', $page);
			$tpl->Assign('record', $record);
			$tpl->Assign('options', $options);
			$tpl->Assign('availableLists', $availableLists);
			$tpl->Assign('availableNewsletters', $availableNewsletters);
			$tpl->Assign('availableLinks', $availableLinks);
			$tpl->Assign('availableCustomFields', $availableCustomFields);
			$tpl->Assign('availableNameCustomFields', $availableNameCustomFields);
			$tpl->Assign('allowEmbedImages', $allowEmbedImages);
			$tpl->Assign('allowSetBounceDetails', $allowSetBounceDetails);

			return $tpl->ParseTemplate('TriggerEmails_Form', true);
		/**
		 * -----
		 */
	}





	/**
	 * _getDateCustomFieldUsedByList
	 * Get all date custom fields that are used by the list
	 * @param Array $listIDs An array of list ID
	 * @return Array Returns an associated array of the custom fields (fieldid as the key, field name as the value)
	 *
	 * @uses SendStudio_Functions::GetApi()
	 * @uses Lists_API::GetCustomFields()
	 * @uses TriggerEmails::$_cacheCustomFieldsUsedByLists
	 */
	private function _getDateCustomFieldUsedByList($listID)
	{
		if (!array_key_exists($listID, self::$_cacheCustomFieldsUsedByLists)) {
			$api = $this->GetApi('CustomFields');

			$tempOutput = array();

			$tempStatus = $api->GetCustomFieldsForLists($listID, array(), array('date'));

			foreach ($tempStatus as $tempEach) {
				$tempOutput[$tempEach['fieldid']] = htmlspecialchars($tempEach['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}

			if (count($tempOutput) == 0) {
				$tempOutput = null;
			}

			self::$_cacheCustomFieldsUsedByLists[$listID] = $tempOutput;
		}

		return self::$_cacheCustomFieldsUsedByLists[$listID];
	}

	/**
	 * _getLinksFormNewsletter
	 * Get and process links from given newsletter
	 *
	 * @param Integer $newsletterid Newsletter ID
	 * @return Array|FALSE Returns an associative array of available links if successful, FALSE otherwise
	 */
	private function _getLinksFormNewsletter($newsletterid)
	{
		$newsletterid = intval($newsletterid);
		$newsletterapi = $this->GetApi('Newsletters');

		return $newsletterapi->GetLinks($newsletterid);
	}

	/**
	 * _checkUserResourcePermission
	 * Check if user have access to all of the resources that trigger email record has defined
	 *
	 * We need to do this here, because currently API shuldn't be checking any user permission.
	 * Once user permission are being used in API, we can deprecate this function
	 *
	 * @param Array $record Associated array of the record
	 * @param User_API $user User API
	 *
	 * @return Boolean Returns TRUE if user have all permission, FALSE otherwise
	 *
	 * @todo deprecate this when API take account user permission
	 */
	private function _checkUserResourcePermission($record, $user)
	{
		// If admin, don't worry about evaluating permission
		if ($user->Admin()) {
			return true;
		}

		$error = false;
		$userLists = $user->GetLists();
		$userNewsletters = $user->GetNewsletters();

		// Check if user have access to particular list
		if ($record['triggertype'] == 'f' && isset($record['data']['listid']) && !array_key_exists($record['data']['listid'], $userLists)) {
			trigger_error('Does not have access to contact list', E_USER_NOTICE);
			$error = true;
		}

		// Check if user have access to particular newsletter specified for link
		if ($record['triggertype'] == 'l' && isset($record['data']['linkid_newsletterid']) && !array_key_exists($record['data']['linkid_newsletterid'], $userNewsletters)) {
			trigger_error('Does not have access to specified newsletter', E_USER_NOTICE);
			$error = true;
		}

		// Check newsletter ID defined for "Newsletter Opened" event
		if ($record['triggertype'] == 'n' && isset($record['data']['newsletterid']) && !array_key_exists($record['data']['newsletterid'], $userNewsletters)) {
			trigger_error('Does not have access to specified newsletter', E_USER_NOTICE);
			$error = true;
		}

		// Check if list IDs defined for static date exists
		if ($record['triggertype'] == 's' && isset($record['data']['staticdate_listids'])) {
			foreach ($record['data']['staticdate_listids'] as $each) {
				if (!array_key_exists($each, $userLists)) {
					trigger_error('Does not have access to specified list', E_USER_NOTICE);
					$error = true;
					break;
				}
			}
		}

		// ----- The following are required for "send" action
			if (isset($record['triggeractions']['send']) && isset($record['triggeractions']['send']['enabled']) && $record['triggeractions']['send']['enabled']) {
				if (isset($record['triggeractions']['send']['newsletterid']) && !array_key_exists($record['triggeractions']['send']['newsletterid'], $userNewsletters)) {
					trigger_error('Newsletter does not exits', E_USER_NOTICE);
					return false;
				}
			}
		// -----

		// ----- The following are required for "addlist" action
			if (isset($record['triggeractions']['addlist']) && isset($record['triggeractions']['addlist']['enabled']) && $record['triggeractions']['addlist']['enabled']) {
				if (isset($record['triggeractions']['addlist']['listid'])) {
					foreach ($record['triggeractions']['addlist']['listid'] as $each) {
						if (!array_key_exists($each, $userLists)) {
							trigger_error('Does not have access to specified newsletter', E_USER_NOTICE);
							$error = true;
							break;
						}
					}
				}
			}
		// -----


		return !$error;
	}
}
