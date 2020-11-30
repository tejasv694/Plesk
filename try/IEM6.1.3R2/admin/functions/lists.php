<?php
/**
 * This file handles list processing. This only covers maintaining (creating, editing, deleting etc).
 *
 * @version     $Id: lists.php,v 1.59 2008/03/05 04:49:00 chris Exp $
 * @author Chris <chris@interspire.com>
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */

/**
 * Include the base sendstudio functions.
 */
require_once(dirname(__FILE__) . '/sendstudio_functions.php');
require_once(dirname(__FILE__) . '/folders.php');

/**
 * This class handles list processing. This only covers maintaining (creating, editing, deleting etc). The main work is done by the API.
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */
class Lists extends SendStudio_Functions
{

	/**
	 * PopupWindows
	 * An array of popup windows used in this class. Used to work out what sort of header and footer to print.
	 *
	 * @see Process
	 *
	 * @var Array
	 */
	public $PopupWindows = array('testbouncedisplay', 'testbouncesettings');

	/**
	 * Suppress Header and Footer for these actions
	 *
	 * @see Process
	 *
	 * @var Array
	 */
	public $SuppressHeaderFooter = array('testbouncesettings');

	/**
	 * Set the default direction to be ascending (alphabetical order) rather than descending which is normally the default.
	 *
	 * @see GetSortDetails
	 * @var String
	 */
	public $_DefaultDirection = 'up';

	/**
	 * Constructor
	 * Loads the language file.
	 *
	 * @see LoadLanguageFile
	 *
	 * @return Void Doesn't return anything, just loads up the language file.
	 */
	public function __construct()
	{
		$this->LoadLanguageFile();
	}

	/**
	 * Process
	 * Does all of the work.
	 * This handles processing of the functions. This includes adding, deleting, editing etc.
	 *
	 * @see GetUser
	 * @see User_API::HasAccess
	 * @see GetApi
	 * @see List_API::DeleteAllSubscribers
	 * @see List_API::ChangeSubscriberFormat
	 * @see ManageLists
	 * @see CreateList
	 * @see EditList
	 *
	 * @return Void Handles processing, prints out what it needs to. Doesn't return anything.
	 */
	public function Process()
	{

		// ----- Define and sanitize "common" variables that are used by this function
			$user = GetUser();

			$req_action		= strtolower($this->_getGETRequest('Action', ''));
			$response		= '';
			$parameters 	= array();

			$parameters['user']		= GetUser();
			$parameters['action']	= $req_action;
		// ------

		// ----- Check permissions
			$secondary_actions = array('addlist', 'change', 'processpaging', 'testbouncedisplay', 'testbouncesettings', 'update');
			if (in_array($req_action, $secondary_actions) || empty($req_action)) {
				$access = $user->HasAccess('lists');
			} else {
				$access = $user->HasAccess('lists', $req_action);
			}

			// Check if the user has permission to perform an action on the supplied item.
			// If an item is supplied to the 'update' action then we should treat it like an 'edit' check.
			$effective_action = $req_action;
			if ($req_action == 'update') {
				$effective_action = 'edit';
			}
			if ($access && isset($_GET['id']) && !in_array($effective_action, $secondary_actions)) {
				$access = $user->HasAccess('lists', $effective_action, $_GET['id']);

				if (!$access) {
					$list = array_keys($user->GetLists());
					$access = in_array($_GET['id'], $list);
				}
			}

			if (!$access) {
				$this->PrintHeader();
				$this->DenyAccess();
				$this->PrintFooter();
				return;
			}
		// ------

		// ------ Handle Folders
			$folders = new Folders();
			if (isset($_GET['Mode'])) {
				$folders->SetFolderMode(strtolower($_GET['Mode']));
			}
			unset($folders);
		// ------

		// ------ Set up paging
			if ($req_action == 'processpaging') {
				$this->SetPerPage($_GET['PerPageDisplay']);
				$req_action = '';
			}
		// ------

		$GLOBALS['Message'] = GetFlashMessages();
		$response = '';

		switch ($req_action) {
			case 'copy':
				$response = $this->CopyList($parameters);
			break;

			case 'edit':
				$response = $this->EditList($parameters);
			break;

			case 'update':
				$response = $this->UpdateList($parameters);
			break;

			case 'create':
				// Display the form to create a list
				$response = $this->CreateList($parameters);
			break;

			case 'addlist':
				// Add the list to the system.
				$response = $this->AddList($parameters);
			break;

			case 'change':
				$response = $this->ChangeList($parameters);
			break;

			case 'delete':
				$response = $this->DeleteList($parameters);
			break;

			case 'testbouncesettings':
				$response = $this->TestBounceSettings($parameters);
			break;

			case 'testbouncedisplay':
				$response = $this->TestBounceSettingsDisplay($parameters);
			break;

			default:
				$response = $this->ManageLists($parameters);
			break;
		}

		// Output HTML

		$popup = (in_array($req_action, $this->PopupWindows)) ? true : false;

		if (!in_array($req_action, $this->SuppressHeaderFooter)) {
			$this->PrintHeader($popup);
		}

		echo $response;

		if (!in_array($req_action, $this->SuppressHeaderFooter)) {
			$this->PrintFooter($popup);
		}

	}

	/**
	 * CopyList
	 * Copies a list to a new one and redirects to the edit page for the new list.
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return Void Redirects to Edit the new list on success or Manage Lists error.
	 */
	private function CopyList($param)
	{
		if ($param['user']->CanCreateList() !== true) {
			FlashMessage(GetLang('TooManyLists'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
		}

		$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
		$api = $this->GetApi();
		list($result, $newid) = $api->Copy($id);

		if (!$result) {
			FlashMessage(GetLang('ListCopyFail'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
		} else {
			$param['user']->LoadPermissions($param['user']->userid);
			$param['user']->GrantListAccess($newid);
			$param['user']->SavePermissions();
			IEM::sessionRemove('UserLists');
			FlashMessage(GetLang('ListCopySuccess'), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Lists', array('Action' => 'Edit', 'id' => $newid)));
		}
	}

	/**
	 * ManageLists
	 * Prints out the lists for management. This includes deleting subscribers, changing subscriber formats etc.
	 *
	 * @see GetPerPage
	 * @see GetCurrentPage
	 * @see GetSortDetails
	 * @see GetApi
	 * @see User_API::ListAdmin
	 * @see List_API::GetLists
	 * @see User_API::CanCreateList
	 * @see SetupPaging
	 * @see PrintDate
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return String HTML for all the Contact Lists the user has permission to see, paginated.
	 */
	private function ManageLists($param)
	{
		$user =& $param['user'];
		$folders = new Folders();

		if ($folders->InFolderMode()) {
			$perpage = 'all';
			$GLOBALS['Mode'] = 'Folder';
		} else {
			$perpage = $this->GetPerPage();
			$GLOBALS['Mode'] = 'List';
		}

		$DisplayPage = $this->GetCurrentPage();
		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$all_lists = $user->GetLists();
		$check_lists = array_keys($all_lists);

		$listapi = $this->GetApi('Lists');

		$NumberOfLists = count($check_lists);

		// If we're a list admin, no point checking the lists - we have access to everything.
		if ($user->ListAdmin()) {
			$check_lists = null;
		}

		$mylists = $listapi->GetLists($check_lists, $sortinfo, false, $start, $perpage);

		$GLOBALS['Lists_AddButton'] = '';

		if ($user->CanCreateList() === true) {
			$GLOBALS['Lists_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
		}

		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		if ($NumberOfLists == 0) {
			$GLOBALS['Intro'] = GetLang('ListsManage');
			if ($user->CanCreateList() === true) {
				FlashMessage(GetLang('ListCreate'), SS_FLASH_MSG_SUCCESS);
			} else {
				FlashMessage(GetLang('ListAssign'), SS_FLASH_MSG_SUCCESS);
			}
			$GLOBALS['Message'] = GetFlashMessages();
			return $this->ParseTemplate('Lists_Manage_Empty', true);
		}

		$this->SetupPaging($NumberOfLists, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging';
		$paging = $this->ParseTemplate('Paging', true, false);

		if ($user->HasAccess('Lists', 'Delete')) {
			$GLOBALS['Option_DeleteList'] = '<option value="Delete">' . GetLang('Delete_Lists') . '</option>';
		}

		if ($user->HasAccess('Subscribers', 'Delete')) {
			$GLOBALS['Option_DeleteSubscribers'] = '<option value="DeleteAllSubscribers">' . GetLang('DeleteAllSubscribers') . '</option>';
		}

		$template = $this->ParseTemplate('Lists_Manage', true, false);

		$lists = array();

		foreach ($mylists as $pos => $listinfo) {
			$GLOBALS['Name'] = htmlspecialchars($listinfo['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Created'] = $this->PrintDate($listinfo['createdate']);

			$GLOBALS['SubscriberCount'] = $this->FormatNumber($listinfo['subscribecount']);

			$GLOBALS['ListAction'] = '';

			$GLOBALS['ListID'] = $listinfo['listid'];
			$GLOBALS['ListAction'] .= $this->ParseTemplate('Lists_Manage_ViewSubscribersLink', true);

			if ($user->HasAccess('Subscribers', 'Add')) {
				$GLOBALS['AddSubscriberListID'] = $listinfo['listid'];
				$GLOBALS['ListAction'] .= $this->ParseTemplate('Lists_Manage_AddSubscriberLink', true, false);
			}

			if ($listinfo['ownerid'] == $user->userid || $user->HasAccess('Lists', 'Edit', $listinfo['listid'])) {
				$GLOBALS['EditListID'] = $listinfo['listid'];
				$GLOBALS['ListAction'] .= $this->ParseTemplate('Lists_Manage_EditLink', true, false);
			} else {
				$GLOBALS['ListAction'] .= $this->DisabledItem('Edit');
			}

			// This checks whether the user is an admin or list admin, so we don't need to.
			$create_list = $user->CanCreateList();
			if ($create_list === true) {
				$GLOBALS['CopyListID'] = $listinfo['listid'];
				$GLOBALS['ListAction'] .= $this->ParseTemplate('Lists_Manage_Copy', true, false);
			} else {
				if ($create_list === false) {
					$itemtitle = 'ListCopyDisabled';
				} else {
					$itemtitle = 'ListCopyDisabled_TooMany';
				}
				$GLOBALS['ListAction'] .= $this->DisabledItem('Copy', $itemtitle);
			}

			if ($listinfo['ownerid'] == $user->userid || $user->HasAccess('Lists', 'Delete', $listinfo['listid'])) {
				$GLOBALS['DeleteListID'] = $listinfo['listid'];
				$GLOBALS['ListAction'] .= $this->ParseTemplate('Lists_Manage_DeleteLink', true, false);
			} else {
				$GLOBALS['ListAction'] .= $this->DisabledItem('Delete');
			}

			$GLOBALS['List'] = $listinfo['listid'];
			$fullname = GetLang('N/A');
			if ($listinfo['fullname'] != '') {
				$fullname = $listinfo['fullname'];
			} elseif ($listinfo['username'] != '') {
				$fullname = $listinfo['username'];
			}
			$GLOBALS['Fullname'] = htmlspecialchars($fullname, ENT_QUOTES, SENDSTUDIO_CHARSET);

			$listinfo['html'] = $this->ParseTemplate('Lists_Manage_Row', true, false);
			$lists[$listinfo['listid']] = $listinfo;
		}

		if ($folders->InFolderMode()) {

			// Organise the rows into their respective folders.
			$folder_type = 'list';
			$folders_api = $this->GetApi('Folders');
			$folder_list = $folders_api->GetFolderList($folder_type, $user->Get('userid'), $sortinfo);

			// Folder ID 0 is special -- it's the 'Uncategorised' or 'Orphan' folder.
			$folder_list[0]['name'] = GetLang('Folders_OrphanName');

			// Accumulate the HTML for each folder and list.
			$f_html = '';
			$orphan_html = '';
			foreach ($folder_list as $fid=>$folder) {
				$l_html = '';
				if (is_array($folder['items'])) {
					// Loop through $lists, as these will be sorted for us already.
					foreach ($lists as $lid=>$list) {
						if (in_array($lid, $folder['items'])) {
							$l_html .= $list['html'];
						}
					}
				}
				$GLOBALS['Items'] = $l_html;
				$GLOBALS['FolderID'] = $fid;
				$GLOBALS['FolderName'] = htmlspecialchars($folder['name']);
				$GLOBALS['FolderName_Encoded'] = urlencode($folder['name']);
				$GLOBALS['FolderType'] = $folder_type;
				$GLOBALS['Expanded'] = $folder['expanded'];
				if ($fid == 0) {
					if (!$folders->IsOrphanExpanded($folder_type)) {
						$GLOBALS['Expanded'] = 0;
					}
					$orphan_html .= $this->ParseTemplate('Folder', true, false);
					continue;
				}
				$f_html .= $this->ParseTemplate('Folder', true, false);
			}
			// The orphan folder should be put at the end
			$f_html .= $orphan_html;
			$template = str_replace('%%TPL_Lists_Manage_Row%%', $f_html, $template);
			$template = str_replace('%%TPL_Paging%%', '', $template);
			$template = str_replace('%%TPL_Paging_Bottom%%', '', $template);

		} else {

			// Not in folder mode, just print rows.
			$l_html = '';
			foreach ($lists as $list) {
				$l_html .= $list['html'];
			}
			$template = str_replace('%%TPL_Lists_Manage_Row%%', $l_html, $template);
			$template = str_replace('%%TPL_Paging%%', $paging, $template);
			$template = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $template);
		}
		return $template;
	}

	/**
	 * EditList
	 * Loads the list and displays it for editing.
	 *
	 * @see GetApi
	 * @see List_API::Load
	 * @see List_API::GetAllFormats
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return String The form for the list to be edited.
	 */
	private function EditList($param)
	{
		$listid = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
		if ($listid <= 0) {
			$GLOBALS['ErrorMessage'] = GetLang('ListDoesntExist');
			$this->DenyAccess();
			return;
		}

		$list = $this->GetApi();
		if (!$list->Load($listid)) {
			$GLOBALS['ErrorMessage'] = GetLang('ListDoesntExist');
			$this->DenyAccess();
			return;
		}

		$user = GetUser();
		if (!$user->HasAccess('Lists', 'Edit')) {
			$this->DenyAccess();
			return;
		}

		$GLOBALS['Action'] = 'Update&id=' . $listid;
		$GLOBALS['CancelButton'] = GetLang('EditListCancelButton');
		$GLOBALS['Heading'] = GetLang('EditMailingList');
		$GLOBALS['Intro'] = GetLang('EditMailingListIntro');
		$GLOBALS['ListDetails'] = GetLang('EditMailingListHeading');

		$GLOBALS['Name'] = htmlspecialchars($list->name, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['OwnerName'] = htmlspecialchars($list->ownername, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['OwnerEmail'] = htmlspecialchars($list->owneremail, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['ReplyToEmail'] = htmlspecialchars($list->replytoemail, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['CompanyName'] = htmlspecialchars($list->companyname, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['CompanyAddress'] = htmlspecialchars($list->companyaddress, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['CompanyPhone'] = htmlspecialchars($list->companyphone, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['NotifyOwner'] = ($list->notifyowner) ? ' CHECKED' : '';

		if ($user->HasAccess('Lists', 'BounceSettings')) {
			$GLOBALS['ShowBounceInfo'] = '';

			$GLOBALS['BounceEmail'] = htmlspecialchars($list->bounceemail, ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Bounce_Server'] = htmlspecialchars($list->bounceserver, ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Bounce_Username'] = htmlspecialchars($list->bounceusername, ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Bounce_Password'] = htmlspecialchars($list->bouncepassword, ENT_QUOTES, SENDSTUDIO_CHARSET);

			$GLOBALS['DisplayExtraMailSettings'] = 'none';
			if ($list->extramailsettings) {
				$GLOBALS['DisplayExtraMailSettings'] = '';
				$GLOBALS['Bounce_ExtraOption'] = ' ';
				$GLOBALS['Bounce_ExtraSettings'] = htmlspecialchars($list->extramailsettings, ENT_QUOTES, SENDSTUDIO_CHARSET);
			}

			$GLOBALS['Imap_Selected'] = $GLOBALS['Pop3_Selected'] = '';
			if ($list->imapaccount) {
				$GLOBALS['Imap_Selected'] = ' SELECTED ';
			} else {
				$GLOBALS['Pop3_Selected'] = ' SELECTED ';
			}

			$GLOBALS['ProcessBounceChecked'] = ($list->processbounce == 1)? ' CHECKED' : '';
			$GLOBALS['Bounce_AgreeDeleteAll'] = ($list->agreedeleteall == 1)? ' CHECKED' : '';
		} else {
			$GLOBALS['ShowBounceInfo'] = 'none';
			$GLOBALS['DisplayExtraMailSettings'] = 'none';

			$GLOBALS['BounceEmail'] = 'dummy@email.com';
		}


		$customfields_api = $this->GetApi('CustomFields');
		$user_customfields = $customfields_api->GetCustomFields($list->Get('ownerid'), array(), false, 0, 0);
		$list_customfields = $list->GetCustomFields($listid);

		$temp = array_diff(array_keys($list_customfields), array_keys($user_customfields));
		foreach ($temp as $each) {
			if (!array_key_exists($each, $user_customfields)) {
				$user_customfields[$each] = $list_customfields[$each];
			}
		}

		$availablefields = '';
		foreach ($user_customfields as $row => $fielddetails) {
			$availablefields .= '<option value="' . $fielddetails['fieldid'] . '"';
			$selected = false;
			if (in_array($fielddetails['fieldid'], $list->customfields)) {
				$selected = true;
			}
			if ($selected) {
				$availablefields .= ' SELECTED';
			}
			$availablefields .= '>' . htmlspecialchars($fielddetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		$GLOBALS['AvailableFields'] = $availablefields;

		$visiblefields = '';
		$buildinfields = $this->BuiltinFields;
		$allfields = 0;

		$fields = explode(',',$list->visiblefields);

		foreach ($buildinfields as $key => $name) {
			++$allfields;
			$visiblefields .= '<option value="' . $key . '"';

			if (in_array($key,$fields)) {
				$visiblefields .= ' selected="selected"';
			}

			$visiblefields .= '>' . htmlspecialchars(GetLang($name),ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		foreach ($list_customfields as $key => $details) {
			++$allfields;
			$visiblefields .= '<option value="' . $details['fieldid'] . '"';

			if (in_array($details['fieldid'],$fields)) {
				$visiblefields .= ' selected="selected"';
			}

			$visiblefields .= '>' . htmlspecialchars($details['name'],ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		$GLOBALS['VisibleFields'] = $visiblefields;

		$this->SetVisibleFieldsHeight($allfields);

		return $this->ParseTemplate('Lists_Form', true);
	}

	/**
	 * UpdateList
	 * Updates the list in the database.
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return Void Redirects to Manage Lists.
	 */
	private function UpdateList($param)
	{
		$list = $this->GetApi();

		$subscriber_api = $this->GetApi('Subscribers');

		$listid = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
		$list->Load($listid);

		$email_address_fields = array('OwnerEmail', 'ReplyToEmail');

		$checkfields = array('Name', 'OwnerName', 'OwnerEmail', 'ReplyToEmail');
		$valid = true; $errors = array();
		foreach ($checkfields as $p => $field) {
			if ($_POST[$field] == '') {
				$valid = false;
				$errors[] = GetLang('List' . $field . 'IsNotValid');
				continue;
			}

			$value = $_POST[$field];
			if (in_array($field, $email_address_fields)) {
				if (!$subscriber_api->ValidEmail($value)) {
					$valid = false;
					$errors[] = GetLang('List' . $field . 'NotValidEmail');
					continue;
				}
			}
			$list->Set(strtolower($field), $value);
		}

		$list->notifyowner = (isset($_POST['NotifyOwner'])) ? 1 : 0;


		/**
		 * If user cannot modify bounce details, we will need to use the default bounce details instead of the one passed in
		 */
			if ($param['user']->HasAccess('Lists', 'BounceSettings')) {
				/**
				 * Check bounce email
				 */
					if (isset($_POST['BounceEmail'])) {
						$tempBounceEmail = $_POST['BounceEmail'];

						if (!$subscriber_api->ValidEmail($tempBounceEmail)) {
							$valid = false;
							$errors[] = GetLang('ListBounceEmailNotValidEmail');
						} else {
							$list->bounceemail = $tempBounceEmail;
						}
					} else {
						$valid = false;
						$errors[] = GetLang('ListBounceEmailIsNotValid');
					}
				/**
				 * -----
				 */

				$list->bounceserver = $_POST['bounce_server'];
				$list->bounceusername = $_POST['bounce_username'];
				$list->bouncepassword = $_POST['bounce_password'];
				$list->imapaccount = (isset($_POST['bounce_imap']) && $_POST['bounce_imap'] == 1) ? 1 : 0;

				/**
				 * Get extramailsettings
				 */
					$list->extramailsettings = '';
					if (!isset($_POST['bounce_extraoption'])) {
						$list->extramailsettings = $_POST['bounce_extrasettings'];
					}

				/**
				 * -----
				 */

				$list->processbounce = (isset($_POST['bounce_process'])) ? 1 : 0;
				$list->agreedelete = 1;
				$list->agreedeleteall = (isset($_POST['bounce_agreedeleteall'])) ? 1 : 0;
			}
		/**
		 * -----
		 */


		/**
		 * If entry is not valid, abort the update
		 */
			if (!$valid) {
				$error_msg = GetLang('UnableToUpdateList') . '<br/>- ' . implode('<br/>- ', $errors);
				FlashMessage($error_msg, SS_FLASH_MSG_ERROR, IEM::urlFor('Lists', array('Action' => 'Edit', 'id' => $listid)));
			}
		/**
		 * -----
		 */


		/**
		 * Set visible vields
		 */
			$visiblefields = array();
			if (isset($_POST['VisibleFields'])) {
				foreach ($_POST['VisibleFields'] as $field) {
					$visiblefields[] = str_replace(',','',$field);
				}
				if (count($visiblefields) == 0) {
					array_unshift($visiblefields,'emailaddress');
				}
			} else {
				$_POST['VisibleFields'] = array('emailaddress');
			}

			$list->visiblefields = implode(',', $visiblefields);
		/**
		 * -----
		 */

		$list->companyname = $_POST['CompanyName'];
		$list->companyaddress = $_POST['CompanyAddress'];
		$list->companyphone = $_POST['CompanyPhone'];

		$customfield_assocs = array();
		if (isset($_POST['AvailableFields']) && is_array($_POST['AvailableFields'])) {
			$customfield_assocs = $_POST['AvailableFields'];
		}

		$list->customfields = $customfield_assocs;

		$saveresult = $list->Save();
		if (!$saveresult) {
			FlashMessage(GetLang('UnableToUpdateList'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists', array('Action' => 'Edit', 'id' => $listid)));
		}
		FlashMessage(GetLang('ListUpdated'), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Lists'));
	}

	/**
	 * CreateList
	 * Displays the 'create list' form.
	 *
	 * @see GetUser
	 * @see User_API::CanCreateList
	 * @see GetApi
	 * @see List_API::Load
	 * @see List_API::GetAllFormats
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return String The HTML for the list creation form.
	 */
	private function CreateList($param)
	{
		$this->LoadLanguageFile('bounce');
		$user =& $param['user'];
		$db = IEM::getDatabase();

		if ($user->CanCreateList() !== true) {
			FlashMessage(GetLang('TooManyLists'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
		}

		$GLOBALS['OwnerName'] = htmlspecialchars($user->fullname, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['OwnerEmail'] = htmlspecialchars($user->emailaddress, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['BounceEmail'] = htmlspecialchars($user->emailaddress, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['ReplyToEmail'] = htmlspecialchars($user->emailaddress, ENT_QUOTES, SENDSTUDIO_CHARSET);


		/**
		 * Bounce email/server settings
		 */
			$GLOBALS['DisplayExtraMailSettings'] = 'none';
			$GLOBALS['ShowBounceInfo'] = 'none';

			if ($user->HasAccess('Lists', 'BounceSettings')) {
				$GLOBALS['ShowBounceInfo'] = '';

				if (SENDSTUDIO_BOUNCE_ADDRESS) {
					$GLOBALS['BounceEmail'] = htmlspecialchars(SENDSTUDIO_BOUNCE_ADDRESS, ENT_QUOTES, SENDSTUDIO_CHARSET);
				}

				$GLOBALS['Bounce_Server'] = htmlspecialchars(SENDSTUDIO_BOUNCE_SERVER, ENT_QUOTES, SENDSTUDIO_CHARSET);
				$GLOBALS['Bounce_Username'] = htmlspecialchars(SENDSTUDIO_BOUNCE_USERNAME, ENT_QUOTES, SENDSTUDIO_CHARSET);
				$GLOBALS['Bounce_Password'] = htmlspecialchars(@base64_decode(SENDSTUDIO_BOUNCE_PASSWORD), ENT_QUOTES, SENDSTUDIO_CHARSET);

				if (SENDSTUDIO_BOUNCE_EXTRASETTINGS) {
					$GLOBALS['Bounce_ExtraOption'] = ' ';
					$GLOBALS['DisplayExtraMailSettings'] = '';
					$GLOBALS['Bounce_ExtraSettings'] = htmlspecialchars(SENDSTUDIO_BOUNCE_EXTRASETTINGS, ENT_QUOTES, SENDSTUDIO_CHARSET);
				}

				$GLOBALS['Imap_Selected'] = $GLOBALS['Pop3_Selected'] = '';
				if (SENDSTUDIO_BOUNCE_IMAP == 1) {
					$GLOBALS['Imap_Selected'] = ' SELECTED ';
				} else {
					$GLOBALS['Pop3_Selected'] = ' SELECTED ';
				}


				if (SENDSTUDIO_BOUNCE_AGREEDELETE == 1) {
					$GLOBALS['ProcessBounceChecked'] = ' CHECKED';
					if (SENDSTUDIO_BOUNCE_AGREEDELETEALL == 1) {
						$GLOBALS['Bounce_AgreeDeleteAll'] = ' CHECKED';
					}
				}
			} else {
				$GLOBALS['BounceEmail'] = 'dummy@email.com';
			}
		/**
		 * -----
		 */

		// if the form has been filled in but we're displaying an error, try to prefill the form.
		if (!empty($_POST)) {
			foreach ($_POST as $key => $val) {
				if (is_array($val)) {
					continue;
				}

				$GLOBALS[$key] = htmlspecialchars($val, ENT_QUOTES, SENDSTUDIO_CHARSET);
			}
		}

		$GLOBALS['Action'] = 'AddList';
		$GLOBALS['CancelButton'] = GetLang('CreateListCancelButton');
		$GLOBALS['Heading'] = GetLang('CreateMailingList');
		$GLOBALS['Intro'] = GetLang('CreateMailingListIntro');
		$GLOBALS['ListDetails'] = GetLang('CreateMailingListHeading');

		$listapi = $this->GetApi();

		$GLOBALS['NotifyOwner'] = 'CHECKED';

		// if these variables aren't in the post array, then they have been unticked. Try to remember the options.
		if (!empty($_POST)) {
			if (!isset($_POST['NotifyOwner'])) {
				$GLOBALS['NotifyOwner'] = '';
			}
			if (!isset($_POST['bounce_imap']) || (isset($_POST['bounce_imap']) && $_POST['bounce_imap'] == 0)) {
				$GLOBALS['Imap_Selected'] = ' ';
				$GLOBALS['Pop3_Selected'] = ' SELECTED ';
			} else if (isset($_POST['bounce_imap']) && $_POST['bounce_imap'] == 1) {
				$GLOBALS['Imap_Selected'] = ' ';
				$GLOBALS['Pop3_Selected'] = ' SELECTED ';
			}
		}

		$GLOBALS['AvailableFields'] = '';
		if ($user->HasAccess('CustomFields')) {
			$customfields_api = $this->GetApi('CustomFields');
			$userfields = $customfields_api->GetCustomFields($user->userid, array(), false, 0, 0);
			$GLOBALS['AvailableFields'] = '';
			foreach ($userfields as $name => $value) {
				$GLOBALS['AvailableFields'] .= '<option value="' . $value['fieldid'] . '"';
				if ($value['isglobal'] && $param['action'] == 'create' && empty($_POST)) {
					// Automatically check the global custom fields on list creation.
					$GLOBALS['AvailableFields'] .= ' selected="selected"';
				}
				$GLOBALS['AvailableFields'] .= '>' . htmlspecialchars($value['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
			}
		}

		if (empty($GLOBALS['AvailableFields'])) {
			$GLOBALS['ShowCustomFields'] = 'style="display: none;"';
		}

		$selectedVisibleFields = null;
		if (isset($_POST['VisibleFields']) && is_array($_POST['VisibleFields'])) {
			$selectedVisibleFields = $_POST['VisibleFields'];
		}
		$GLOBALS['VisibleFields'] = '';
		foreach ($this->BuiltinFields as $name => $value) {
			$GLOBALS['VisibleFields'] .= '<option value="' . $name . '"';
			//if (is_null($selectedVisibleFields) || (!is_null($selectedVisibleFields) && in_array($name, $selectedVisibleFields))) {
				$GLOBALS['VisibleFields'] .= ' selected="selected"';
			//}
			$GLOBALS['VisibleFields'] .= '>' . htmlspecialchars(GetLang($value),ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		$this->SetVisibleFieldsHeight(count($this->BuiltinFields));

		return $this->ParseTemplate('Lists_Form', true);
	}

	/**
	 * AddList
	 * Adds a Contact List to the system and returns to the Manage Lists screen, or redisplays the Create a List screen with an error.
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return String Redirects to the Manage Lists page on success, or returns the Edit List form HTML on error.
	 */
	private function AddList($param)
	{
		$user =& $param['user'];
		$list = $this->GetApi();

		$subscriber_api = $this->GetApi('Subscribers');

		if ($user->CanCreateList() !== true) {
			FlashMessage(GetLang('TooManyLists'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
		}

		$email_address_fields = array('OwnerEmail', 'ReplyToEmail');

		$checkfields = array('Name', 'OwnerName', 'OwnerEmail', 'ReplyToEmail');
		$valid = true; $errors = array();
		foreach ($checkfields as $p => $field) {
			if ($_POST[$field] == '') {
				$valid = false;
				$errors[] = GetLang('List' . $field . 'IsNotValid');
				continue;
			}

			$value = $_POST[$field];
			if (in_array($field, $email_address_fields)) {
				if (!$subscriber_api->ValidEmail($value)) {
					$valid = false;
					$errors[] = GetLang('List' . $field . 'NotValidEmail');
					continue;
				}
			}
			$list->Set(strtolower($field), $value);
		}

		$list->notifyowner = (isset($_POST['NotifyOwner'])) ? 1 : 0;


		/**
		 * If user cannot modify bounce details, we will need to use the default bounce details instead of the one passed in
		 */
			$list->bounceemail = $user->emailaddress;
			$list->processbounce = 0;

			if ($user->HasAccess('Lists', 'BounceSettings')) {
				/**
				 * Check bounce email
				 */
					if (isset($_POST['BounceEmail'])) {
						$tempBounceEmail = $_POST['BounceEmail'];

						if (!$subscriber_api->ValidEmail($tempBounceEmail)) {
							$valid = false;
							$errors[] = GetLang('ListBounceEmailNotValidEmail');
						} else {
							$list->bounceemail = $tempBounceEmail;
						}
					} else {
						$valid = false;
						$errors[] = GetLang('ListBounceEmailIsNotValid');
					}
				/**
				 * -----
				 */

				$list->bounceserver = $_POST['bounce_server'];
				$list->bounceusername = $_POST['bounce_username'];
				$list->bouncepassword = $_POST['bounce_password'];

				$list->imapaccount = (isset($_POST['bounce_imap']) && $_POST['bounce_imap'] == 1) ? 1 : 0;

				/**
				 * Get extramailsettings
				 */
					$list->extramailsettings = '';
					if (!isset($_POST['bounce_extraoption'])) {
						$list->extramailsettings = $_POST['bounce_extrasettings'];
					}
				/**
				 * -----
				 */

				$list->processbounce = (isset($_POST['bounce_process'])) ? 1 : 0;
				$list->agreedeleteall = (isset($_POST['bounce_agreedeleteall'])) ? 1 : 0;
				$list->agreedelete = 1;
			} elseif (SENDSTUDIO_BOUNCE_AGREEDELETE) {
				$list->bounceemail = SENDSTUDIO_BOUNCE_ADDRESS;
				$list->bounceserver = SENDSTUDIO_BOUNCE_SERVER;
				$list->bounceusername = SENDSTUDIO_BOUNCE_USERNAME;
				$list->bouncepassword = @base64_decode(SENDSTUDIO_BOUNCE_PASSWORD);

				$list->imapaccount = SENDSTUDIO_BOUNCE_IMAP;
				$list->extramailsettings = SENDSTUDIO_BOUNCE_EXTRASETTINGS;

				$list->processbounce = 1;
				$list->agreedelete = 1;
				$list->agreedeleteall = SENDSTUDIO_BOUNCE_AGREEDELETEALL;
			}
		/**
		 * -----
		 */


		/**
		 * If entry is not valid, abort the update
		 */
			if (!$valid) {
				FlashMessage(GetLang('UnableToUpdateList'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists', array('Action' => 'Edit', 'id' => $list->listid)));
			}
		/**
		 * -----
		 */


		/**
		 * Set visible vields
		 */
			$visiblefields = array();
			if (isset($_POST['VisibleFields'])) {
				foreach ($_POST['VisibleFields'] as $field) {
					$visiblefields[] = str_replace(',','',$field);
				}
				if (count($visiblefields) == 0) {
					array_unshift($visiblefields,'emailaddress');
				}
			} else {
				$_POST['VisibleFields'] = array('emailaddress');
			}

			$list->visiblefields = implode(',', $visiblefields);
		/**
		 * -----
		 */

		$list->companyname = $_POST['CompanyName'];
		$list->companyaddress = $_POST['CompanyAddress'];
		$list->companyphone = $_POST['CompanyPhone'];

		$list->ownerid = $user->userid;

		$customfield_assocs = array();
		if (isset($_POST['AvailableFields']) && is_array($_POST['AvailableFields'])) {
			$customfield_assocs = $_POST['AvailableFields'];
		}

		$list->customfields = $customfield_assocs;

		$create = $list->Create();

		if (!$create) {
			// Don't use a Flash Message here so that they can try again.
			$GLOBALS['Error'] = GetLang('UnableToCreateList');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			return $this->CreateList($param);
		}

		$user->LoadPermissions($user->userid);
		$user->GrantListAccess($create);
		$user->SavePermissions();
		IEM::sessionRemove('UserLists');
		FlashMessage(GetLang('ListCreated'), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Lists'));
	}

	/**
	 * ChangeList
	 * Performs the following actions:
	 * - Deletes lists,
	 * - Deletes all subscribers within lists,
	 * - Changes the format of all subscribers within lists,
	 * - Changes the confirmed status of all subscribers within lists, or
	 * - Merges lists.
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return Void Redirects to the Manage Lists page or Edit List page depending on action or error.
	 */
	private function ChangeList($param)
	{
		$user =& $param['user'];

		// The User should be able to view the lists they want to merge, but there is no 'View' permission for lists.
		// For now we will just require that they have 'edit' permissions.
		foreach ($_POST['Lists'] as $lid) {
			if (!$user->HasAccess('lists', 'edit', $lid)) {
				$this->DenyAccess();
			}
		}
		$subaction = strtolower($_POST['ChangeType']);
		$listApi = $this->GetApi();

		$success_format = 0; $failure_format = 0;
		$success_status = 0; $failure_status = 0;
		$success_confirmed = 0; $failure_confirmed = 0;

		if ($subaction == 'mergelists') {
			if ($user->CanCreateList() !== true) {
				FlashMessage(GetLang('TooManyLists'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
			}

			if (sizeof($_POST['Lists']) < 2) {
				FlashMessage(GetLang('UnableToMergeLists'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
			}

			$message = '';

			$userdetails = array();
			$userdetails['userid'] = $user->userid;
			$userdetails['name'] = $user->fullname;
			$userdetails['emailaddress'] = $user->emailaddress;

			list($newid, $msg, $results) = $listApi->MergeLists($_POST['Lists'], $userdetails);

			$success_merged = $results['Success'];
			$failure_merged = $results['Failure'];
			$duplicates_success_removed = $results['DuplicatesSuccess'];
			$duplicates_failure_removed = $results['DuplicatesFailure'];

			if ($success_merged > 0) {
				$message .= sprintf(GetLang('MergeSuccessful'), $this->FormatNumber($success_merged));
				FlashMessage($message, SS_FLASH_MSG_SUCCESS);
			}

			if ($failure_merged > 0) {
				$message = sprintf(GetLang('MergeUnsuccessful'), $this->FormatNumber($success_merged));
				FlashMessage($message, SS_FLASH_MSG_ERROR);
			}

			if ($duplicates_success_removed > 0) {
				$message = sprintf(GetLang('MergeDuplicatesRemoved_Success'), $this->FormatNumber($duplicates_success_removed));
				FlashMessage($message, SS_FLASH_MSG_SUCCESS);
			}

			if ($duplicates_failure_removed > 0) {
				$message = sprintf(GetLang('MergeDuplicatesRemoved_Fail'), $this->FormatNumber($duplicates_failure_removed));
				FlashMessage($message, SS_FLASH_MSG_ERROR);
			}

			if (!$newid) {
				IEM::redirectTo('Lists');
			}

			$user->LoadPermissions($user->userid);
			$user->GrantListAccess($newid);
			$user->SavePermissions();
			IEM::redirectTo('Lists', array('Action' => 'Edit', 'id' => $newid));
		}

		$lists_deleted_success = $lists_deleted_failure = 0;
		$subscribers_deleted_success = $subscribers_deleted_failure = 0;

		foreach ($_POST['Lists'] as $pos => $list) {
			$listApi->Load((int)$list);
			switch ($subaction) {

				case 'delete':
                                    // ----- get jobs running for this user
                                    $db = IEM::getDatabase();
                                    $jobs_to_check = array();
                                    $query = "SELECT jobid FROM [|PREFIX|]jobs_lists WHERE listid = {$list}";
                                    $result = $db->Query($query);
                                    if(!$result){
                                            trigger_error(mysql_error()."<br />".$query);
                                            FlashMessage("Unable to load list jobs. <br /> ". mysql_error(), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
                                            exit();
                                    }
                                    while($row = $db->Fetch($result)){
                                            $jobs_to_check[] = $row['jobid'];
                                    }
                                    $db->FreeResult($result);
                                    if(!empty($jobs_to_check)){
                                            $query = "SELECT jobstatus FROM [|PREFIX|]jobs WHERE jobid IN (" . implode(',', $jobs_to_check) . ")";	
                                            $result = $db->Query($query);
                                            if(!$result){
                                                    trigger_error(mysql_error()."<br />".$query);
                                                    FlashMessage("Unable to load jobs. <br /> ". mysql_error() . "<br />Query: " . $query, SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
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
                                    $status = $listApi->Delete($list, $user->Get('userid'));
                                    if ($status) {
                                            $lists_deleted_success++;
                                            $user->RevokeListAccess($list);
                                            $user->SavePermissions();
                                    } else {
                                            $lists_deleted_failure++;
                                    }
				break;

				case 'deleteallsubscribers':
                                    // ----- get jobs running for this user
                                    $db = IEM::getDatabase();
                                    $jobs_to_check = array();
                                    $query = "SELECT jobid FROM [|PREFIX|]jobs_lists WHERE listid = {$list}";
                                    $result = $db->Query($query);
                                    if(!$result){
                                            trigger_error(mysql_error()."<br />".$query);
                                            FlashMessage("Unable to load list jobs. <br /> ". mysql_error(), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
                                            exit();
                                    }
                                    while($row = $db->Fetch($result)){
                                            $jobs_to_check[] = $row['jobid'];
                                    }
                                    $db->FreeResult($result);
                                    if(!empty($jobs_to_check)){
                                            $query = "SELECT jobstatus FROM [|PREFIX|]jobs WHERE jobid IN (" . implode(',', $jobs_to_check) . ")";	
                                            $result = $db->Query($query);
                                            if(!$result){
                                                    trigger_error(mysql_error()."<br />".$query);
                                                    FlashMessage("Unable to load jobs. <br /> ". mysql_error() . "<br />Query: " . $query, SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
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
                                    
                                    $status = $listApi->DeleteAllSubscribers($list);
                                    if ($status) {
                                            $subscribers_deleted_success++;
                                    } else {
                                            $subscribers_deleted_failure++;
                                    }
				break;

				case 'changeformat_text':
					$newformat = 'Text';
					list($status, $msg) = $listApi->ChangeSubscriberFormat($newformat, $list);
					if ($status) {
						$success_format++;
					} else {
						$failure_format++;
					}
				break;
				case 'changeformat_html':
					$newformat = 'HTML';
					list($status, $msg) = $listApi->ChangeSubscriberFormat($newformat, $list);
					if ($status) {
						$success_format++;
					} else {
						$failure_format++;
					}
				break;

				case 'changestatus_confirm':
					$newstatus = 'Confirmed';
					list($status, $msg) = $listApi->ChangeSubscriberConfirm('confirm', $list);
					if ($status) {
						$success_confirmed++;
					} else {
						$failure_confirmed++;
					}
				break;
				case 'changestatus_unconfirm':
					$newstatus = 'Unconfirmed';
					list($status, $msg) = $listApi->ChangeSubscriberConfirm('unconfirm', $list);
					if ($status) {
						$success_confirmed++;
					} else {
						$failure_confirmed++;
					}
				break;
			}
		}

		$message = '';

		if ($lists_deleted_success > 0) {
			$message = sprintf(GetLang('ListsDeleteSuccess'), $this->FormatNumber($lists_deleted_success));
			if ($lists_deleted_success == 1) {
				$message = GetLang('ListDeleteSuccess');
			}
			FlashMessage($message, SS_FLASH_MSG_SUCCESS);
		}

		if ($lists_deleted_failure > 0) {
			$message = GetLang('ListsDeleteFail');
			if ($lists_deleted_failure == 1) {
				$message = GetLang('ListDeleteFail');
			}
			FlashMessage($message, SS_FLASH_MSG_ERROR);
		}

		if ($subscribers_deleted_success > 0) {
			$message = sprintf(GetLang('ListsDeleteAllSubscribersSuccess'), $this->FormatNumber($subscribers_deleted_success));
			if ($subscribers_deleted_success == 1) {
				$message = GetLang('ListDeleteAllSubscribersSuccess');
			}
			FlashMessage($message, SS_FLASH_MSG_SUCCESS);
		}

		if ($subscribers_deleted_failure > 0) {
			$message = GetLang('ListsDeleteAllSubscribersFail');
			if ($subscribers_deleted_failure == 1) {
				$message = GetLang('ListDeleteAllSubscribersFail');
			}
			FlashMessage($message, SS_FLASH_MSG_ERROR);
		}

		if ($success_format > 0) {
			$message = sprintf(GetLang('AllListSubscribersChangedFormat'), GetLang('Format_' . $newformat));
			FlashMessage($message, SS_FLASH_MSG_SUCCESS);
		}
		if ($failure_format > 0) {
			$message = sprintf(GetLang('AllListSubscribersNotChangedFormat'), GetLang('Format_' . $newformat));
			FlashMessage($message, SS_FLASH_MSG_ERROR);
		}

		if ($success_status > 0) {
			$message = sprintf(GetLang('AllListSubscribersChangedStatus'), GetLang('Status_' . $newstatus));
			FlashMessage($message, SS_FLASH_MSG_SUCCESS);
		}
		if ($failure_status > 0) {
			$message = sprintf(GetLang('AllListSubscribersNotChangedStatus'), GetLang('Status_' . $newstatus));
			FlashMessage($message, SS_FLASH_MSG_ERROR);
		}

		if ($success_confirmed > 0) {
			$message = sprintf(GetLang('AllListSubscribersChangedConfirm'), GetLang('Status_' . $newstatus));
			FlashMessage($message, SS_FLASH_MSG_SUCCESS);
		}
		if ($failure_confirmed > 0) {
			$message = sprintf(GetLang('AllListSubscribersNotChangedConfirm'), GetLang('Status_' . $newstatus));
			FlashMessage($message, SS_FLASH_MSG_ERROR);
		}
		IEM::redirectTo('Lists');
	}

	/**
	 * DeleteList
	 * Deletes a single list.
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return Void Redirects to the Manage Lists page.
	 */
	private function DeleteList($param)
	{
		$listApi = $this->GetApi('Lists');
		$list = (int)$_GET['id'];
		// ----- get jobs running for this user
		$db = IEM::getDatabase();
		$jobs_to_check = array();
		$query = "SELECT jobid FROM [|PREFIX|]jobs_lists WHERE listid = {$list}";
		$result = $db->Query($query);
		if(!$result){
			trigger_error(mysql_error()."<br />".$query);
			FlashMessage("Unable to load list jobs. <br /> ". mysql_error(), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
			exit();
		}
		while($row = $db->Fetch($result)){
			$jobs_to_check[] = $row['jobid'];
		}
		$db->FreeResult($result);
		if(!empty($jobs_to_check)){
			$query = "SELECT jobstatus FROM [|PREFIX|]jobs WHERE jobid IN (" . implode(',', $jobs_to_check) . ")";	
			$result = $db->Query($query);
			if(!$result){
				trigger_error(mysql_error()."<br />".$query);
				FlashMessage("Unable to load jobs. <br /> ". mysql_error() . "<br />Query: " . $query, SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
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
		$status = $listApi->Delete($list, $param['user']->Get('userid'));

		if ($status) {
			$param['user']->LoadPermissions($param['user']->userid);
			$param['user']->RevokeListAccess($list);
			$param['user']->SavePermissions();
			FlashMessage(GetLang('ListDeleteSuccess'), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Lists'));
		}
		FlashMessage(GetLang('ListDeleteFail'), SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
	}

	/**
	 * TestBounceSettingsDisplay
	 * Loads the template for the bounce test thickbox.
	 *
	 * @param Array $param Any parameters that needed to be passed to this function
	 *
	 * @return Void Doesn't return anything.
	 */
	private function TestBounceSettingsDisplay($param)
	{
		$test_bounce_details = array (
			'server' => $_GET['bounce_server'],
			'username' => $_GET['bounce_username'],
			'password' => $_GET['bounce_password'],
			'extra_settings' => $_GET['bounce_extrasettings'],
			'imap' => (isset($_GET['bounce_imap']) && $_GET['bounce_imap'] == 1) ? 1 : 0,
		);

		// Decrypt the password.
		$test_bounce_details['password'] = IEM::decrypt($test_bounce_details['password'], IEM::sessionGet('RandomToken'));

		IEM::sessionSet('TestBounceDetails', $test_bounce_details);

		$GLOBALS['Page'] = 'Lists';
		$this->LoadLanguageFile('Bounce');
		return $this->ParseTemplate('Bounce_Test_Window', true);
	}

	/**
	 * TestBounceSettings
	 * Tries to log into the bounce server. It will print a success message or the error.
	 *
	 * @param Array $param Any parameters that needed to be passed into this function
	 *
	 * @return Void Doesn't return anything.
	 */
	private function TestBounceSettings($param)
	{
		$this->LoadLanguageFile('Bounce');

		$test_bounce_details = IEM::sessionGet('TestBounceDetails');

		if ($test_bounce_details === false || empty($test_bounce_details)) {
			$GLOBALS['Error'] = sprintf(GetLang('BadLogin_Details'), GetLang('BounceError_NoDetails'));
			$this->ParseTemplate('ErrorMsg');
			return;
		}

		$bounce_server = $test_bounce_details['server'];
		$bounce_user = $test_bounce_details['username'];
		$bounce_pass = $test_bounce_details['password'];

		$extra_settings = false;
		if ($test_bounce_details['extra_settings'] !== '') {
			$extra_settings = $test_bounce_details['extra_settings'];
		}

		$imap = ($test_bounce_details['imap'] === 1) ? true : false;

		$bounce_api = $this->GetApi('Bounce');

		$bounce_api->Set('bounceuser', $bounce_user);
		$bounce_api->Set('bouncepassword', base64_encode($bounce_pass));
		$bounce_api->Set('bounceserver', $bounce_server);
		$bounce_api->Set('imapaccount', $imap);
		if ($extra_settings) {
			$bounce_api->Set('extramailsettings', $extra_settings);
		}

		$login_ok = $bounce_api->Login();

		if (!$login_ok) {
			$GLOBALS['Error'] = sprintf(GetLang('BadLogin_Details'), $bounce_api->Get('ErrorMessage'));
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		} else {
			$GLOBALS['Message'] = $this->PrintSuccess('BounceLogin_Successful');
		}
		$bounce_api->Logout(false);

		return $GLOBALS['Message'];
	}

	/**
	 * SetVisibleFieldsHeight
	 * Sets the height of the available and visisble fields ISelectReplace boxes.
	 *
	 * @param Int $count The number of fields in the box.
	 *
	 * @return Void Doesn't return anything.
	 */
	private function SetVisibleFieldsHeight($count)
	{
		if ($count <= 10) {
			if ($count < 3) {
				$count = 3;
			}
			$GLOBALS['VisibleFields_Style'] = 'height:' . ($count * 25) . 'px';
		}
	}
}
