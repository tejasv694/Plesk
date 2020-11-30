<?php
/**
 * This file contains a class that will handle "Groups" for user accounts.
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */

/**
 * Include the base sendstudio functions.
 */
require_once(dirname(__FILE__) . '/sendstudio_functions.php');


/**
* Class for the users management page.
* This checks whether you are allowed to manage users or whether you are only allowed to manage your own account. This also handles editing, deleting, checks to make sure you're not removing the 'last' user and so on.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class UsersGroups extends SendStudio_Functions
{
	/**
	 * CONSTRUCTOR
	 */
	public function __construct()
	{
		// Overwrite the default variable that are defined
		// in parent's class.
		$this->_DefaultSort = 'groupname';
		$this->_DefaultDirection = 'asc';

		$this->LoadLanguageFile('UsersGroups');
	}

	/**
	 * Controller for this class.
	 *
	 * This function will re-direct request to the appropriate method
	 * according to the "Action" variable that are specified in request string.
	 *
	 * @return Void Does not return anything.
	 */
	public function Process()
	{
		$reqAction = IEM::requestGetGET('Action', '', 'trim');

		// Check whether or not the user have access to this page
		$user = IEM::userGetCurrent();
		if (!$user || !$user->UserAdmin()) {
			$this->PrintHeader();
			$this->DenyAccess();
		}

		// Make sure that the page that has been requested is available
		if (!is_callable(array($this, "page_{$reqAction}"))) {
			$reqAction = 'manageGroups';
		}

		$method = "page_{$reqAction}";
		$this->{$method}();
	}




	/**
	 * This method will display a "manage user" page
	 *
	 * @return void
	 * @todo phpdocs
	 */
	public function page_manageGroups()
	{
		// ----- Sanitize and declare variables that is going to be used in this function
			$pageRecordPP		= 0;
			$pageCurrentIndex	= $this->GetCurrentPage();
			$pageSortInfo		= $this->GetSortDetails();

			$reqProcessPaging	= IEM::requestGetGET('ProcessPaging', 0, 'intval');

			$records			= array();
			$recordTotal		= 0;

			$currentUser		= IEM::getCurrentUser();

			$page = array(
				'messages'		=> GetFlashMessages(),
				'currentuserid'	=> $currentUser->userid
			);
		// -----

		// Do we need to process paging?
		if ($reqProcessPaging) {
			$temp = IEM::requestGetGET('PerPageDisplay', 0, 'intval');
			if ($temp) {
				$this->SetPerPage($temp);
			}
		}

		// Get "Record Per Page"
		if ($pageRecordPP == 0) {
			$pageRecordPP = $this->GetPerPage();
		}

		$start = 0;
		if ($pageRecordPP != 'all') {
			$start = ($pageCurrentIndex - 1) * $pageRecordPP;
		}

		$recordTotal = API_USERGROUPS::getRecords(true);
		if (!$recordTotal) {
			$recordTotal = 0;
		}

		$records = API_USERGROUPS::getRecords(false, false, $pageRecordPP, $start, $pageSortInfo['SortBy'], ($pageSortInfo['Direction'] == 'desc'));
		if (!$records) {
			$records = array();
		} else {
			for ($i = 0, $j = count($records); $i < $j; ++$i) {
				$records[$i]['processed_CreateDate'] = $this->PrintDate($records[$i]['createdate']);
			}
		}


		// ----- Calculate pagination, this is using the older method of pagination
			$GLOBALS['PAGE'] = 'UsersGroups';
			$GLOBALS['FormAction'] = 'Action=manageGroups&ProcessPaging=1';

			$this->SetupPaging($recordTotal, $pageCurrentIndex, $pageRecordPP);
		// -----

		// ----- Print out HTML
			$this->PrintHeader();

			$tpl = GetTemplateSystem();
			$tpl->Assign('PAGE', $page);
			$tpl->Assign('records', $records);

			$tpl->ParseTemplate('UsersGroups_ManageGroups');

			$this->PrintFooter();
		// -----

		return;
	}

	/**
	 *
	 * @return unknown_type
	 * @todo phpdocs
	 */
	public function page_deleteGroups()
	{
		$groupIds = IEM_Request::getParam('groups', array());

		// No IDs specified
		if (empty($groupIds)) {
			$this->page_manageGroups();

			return;
		}

		if (!is_array($groupIds)) {
			$groupIds = array($groupIds);
		}

		$failed  = array();
		$success = array();

		foreach ($groupIds as $id) {
			$status = API_USERGROUPS::deleteRecordByID($id);

			if ($status) {
				$success[] = $id;
			} else {
				$failed[] = $id;
			}
		}

		if (count($failed) != 0) {
			FlashMessage(GetLang('UsersGroups_ManageGroups_Message_DeleteFail'), SS_FLASH_MSG_ERROR, IEM::urlFor('UsersGroups', array('Action' => 'manageGroups')));
		} else {
			FlashMessage(sprintf(GetLang('UsersGroups_ManageGroups_Message_DeleteSuccess'), count($success)), SS_FLASH_MSG_SUCCESS, IEM::urlFor('UsersGroups', array('Action' => 'manageGroups')));
		}
	}

	/**
	 *
	 * @return unknown_type
	 * @todo phpdocs
	 */
	public function page_editGroup()
	{
		$id = IEM::requestGetGET('GroupID', 0, 'intval');

		$record = array();
		if (!empty($id)) {
			$record = API_USERGROUPS::getRecordByID($id);
		}

		$this->printEditor($record);
		return;
	}

	// TODO docs
	public function page_createGroup()
	{
		$this->printEditor();
		return;
	}

	// TODO docs
	public function page_saveRecord()
	{
		$record = IEM::requestGetPOST('record', array());
		$created = ((IEM::ifsetor($record['groupid'], 0, 'intval') == 0) ? true : false);

		/*
		 * Transform the permission so that it will be recognized by the API
		 */

		$permissions = IEM::ifsetor($record['permissions'], array());


		$new_permissions = array();
		if (!is_array($permissions)) {
			$permissions = array();
		}
		if (!empty($permissions)) {
			foreach ($permissions as $each) {
				$temp = explode('.', $each);

				// This can only handle 2 level permissions,
				// ie. autoresponders.create, autoresponders.delete, autoresponders.edit
				// will become $permissions['autoresponders'] = array('create', 'delete', 'edit');
				if (count($temp) != 2) {
					continue;
				}

				if (!isset($new_permissions[$temp[0]])) {
					$new_permissions[$temp[0]] = array();
				}

				$new_permissions[$temp[0]][] = $temp[1];
			}
		}

		$record['permissions'] = $new_permissions;

		if (empty($record)) {
			return $this->page_createGroup($record);
		}

		// Check if "Request Token" matches
		// This tries to prevent CSRF
		$token = IEM::sessionGet('UsersGroups_Editor_RequestToken', false);
		if (!$token || $token != IEM::requestGetPOST('requestToken', false)) {
			return $this->page_createGroup($record);
		}

		$status = API_USERGROUPS::saveRecord($record);
		if (!$status) {
			FlashMessage(GetLang('UsersGroups_From_Error_CannotSave'), SS_FLASH_MSG_ERROR);
			return $this->printEditor($record);
		}

		$messageVariable = 'UsersGroups_From_Success_Saved';
		if ($created) {
			$messageVariable = 'UsersGroups_From_Success_Created';
		}

		FlashMessage(GetLang($messageVariable), SS_FLASH_MSG_SUCCESS, IEM::urlFor('UsersGroups'));
	}

	// TODO docs
	protected function printEditor($record = array())
	{
		$user               = IEM::userGetCurrent();
		$group              = new record_UserGroups($record);
		$permissionList     = $user->getProcessedPermissionList();
		$availableLists     = $user->GetLists();
		$availableSegments  = $user->GetSegmentList();
		$availableTemplates = $user->GetTemplates();
		$requestToken       = md5(mt_rand());

		$page = array(
			'messages' => GetFlashMessages()
		);

		IEM::sessionSet('UsersGroups_Editor_RequestToken', $requestToken);

		if (!isset($record['permissions']) || !is_array($record['permissions'])) {
			$record['permissions'] = array();
		}

		if (!isset($record['access']) || !is_array($record['access'])) {
			$record['access'] = array();
		}

		$record['permissions_stupid_template'] = array();
		
		if (isset($record['permissions'])) {
			foreach ($record['permissions'] as $key => $value) {
				foreach ($value as $each) {
					$record['permissions_stupid_template'][] = "{$key}.{$each}";
				}
			}
		}
		
		$this->PrintHeader();
		
		$tpl = GetTemplateSystem();
		$tpl->Assign('PAGE', $page);
		$tpl->Assign('record', $record);
		$tpl->Assign('permissionList', $permissionList);
		$tpl->Assign('isSystemAdmin', $group->isAdmin());
		$tpl->Assign('isLastAdminWithUsers', $group->isLastAdminWithUsers());
		$tpl->Assign('availableLists', $availableLists, true);
		$tpl->Assign('availableSegments', $availableSegments, true);
		$tpl->Assign('availableTemplates', $availableTemplates, true);
		$tpl->Assign('requestToken', $requestToken);

		$tpl->ParseTemplate('UsersGroups_Form');

		$this->PrintFooter();

		return;
	}
}
