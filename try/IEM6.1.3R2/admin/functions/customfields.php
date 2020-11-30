<?php
/**
* This file handles adding, editing, deleting of custom fields.
*
* @version     $Id: customfields.php,v 1.41 2008/02/18 06:33:37 chris Exp $
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
* Class for the processing custom fields. Uses the API's to handle functionality, this simply handles processing and calls the API's to do the work.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class CustomFields extends SendStudio_Functions
{

	/**
	* ValidSorts
	* An array of sorts you can use with autoresponder management.
	*
	* @var Array
	*/
	var $ValidSorts = array('name', 'createdate', 'fieldtype');

	/**
	* ValidSorts
	* An array of secondary sorts to use.
	*
	* @var Array
	*/
	var $_SecondarySorts = array('date' => array('field' => 'name', 'order' => 'asc'), 'type' => array('field' => 'name', 'order' => 'asc'));

	/**
	* _DefaultSort
	* Default sort for autoresponders is hours after subscription
	*
	* @see GetSortDetails
	*
	* @var String
	*/
	var $_DefaultSort = 'name';

	/**
	* _DefaultDirection
	* Default sort direction for autoresponders is ascending
	*
	* @see GetSortDetails
	*
	* @var String
	*/
	var $_DefaultDirection = 'Up';

	/**
	* CustomFieldTypes
	* A list of custom field types sendstudio supports.
	*
	* @see EditCustomField
	* @see CreateCustomField_Step1
	*
	* @var Array
	*/
	var $CustomFieldTypes = array(
		'Text',
		'Textarea',
		'Number',
		'Dropdown',
		'Checkbox',
		'Radiobutton',
		'Date',
	);

	/**
	* MoreOptionsToShow
	* How many more options to show if the type supports it.
	*
	* @see EditCustomField
	*
	* @var Int
	*/
	var $MoreOptionsToShow = 5;

	/**
	* MoreOptions Which custom fields have 'more options' to show.
	*
	* @see EditCustomField
	*
	* @var Array
	*/
	var $MoreOptions = array('dropdown', 'checkbox', 'radiobutton');

	/**
	* Constructor
	* Loads the language file.
	*
	* @see LoadLanguageFile
	*
	* @return Void Doesn't return anything.
	*/
	function CustomFields()
	{
		$this->LoadLanguageFile();
	}

	/**
	* Process
	* Does all of the work.
	* This handles processing of the functions. This includes adding, deleting, editing, associating with lists.
	*
	* @see EditCustomField
	* @see CreateCustomField_Step1
	* @see CreateCustomField_Step2
	* @see ManageCustomField_Lists
	* @see ManageCustomFields
	*
	* @return Void Doesn't return anything, just prints out the results.
	*/
	function Process()
	{
		$GLOBALS['Message'] = '';

		$this->PrintHeader();
		$user = GetUser();

		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = 'manage';
		}

		$effective_action = $action;
		if ($action == 'associate') {
			$effective_action = null;
		}

		$access = $user->HasAccess('customfields', $effective_action);

		if ($access) {
			// The user is allowed to perform the action only on their own fields.
			$field_owner = $user->userid;
			$api = $this->GetApi();
			$check = array();
			if (isset($_POST['customfields'])) {
				$check = $_POST['customfields'];
			}
			if (isset($_GET['id'])) {
				$check[] = $_GET['id'];
			}
			if (isset($_POST['fieldid'])) {
				$check[] = $_POST['fieldid'];
			}
			foreach ($check as $id) {
				if (!$api->Load(intval($id))) {
					$this->DenyAccess();
				}
				if ($api->ownerid != $field_owner && !$user->Admin()) {
					$this->DenyAccess();
				}
			}
		}

		if (!$access) {
			$this->DenyAccess();
		}

		switch ($action) {
			case 'associate':
				$associations = (isset($_POST['listid'])) ? $_POST['listid'] : array();
				$fieldid = $_POST['fieldid'];

				$api = $this->GetApi();
				$api->Load($fieldid);

				$fieldapi = $this->GetApi('CustomFields_' . $api->fieldtype);
				if (!$fieldapi) {
					return false;
				}

				unset($api);

				$fieldapi->Load($fieldid);

				$saveresult = $fieldapi->SetAssociations($associations, $user);
				if (!$saveresult) {
					$GLOBALS['Error'] = GetLang('UnableToUpdateCustomField');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				} else {
					$GLOBALS['Message'] = $this->PrintSuccess('CustomFieldUpdated');
				}
				$this->ManageCustomFields();
			break;

			case 'edit':
				$fieldid = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

				$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : '';

				switch ($subaction) {
					case 'update':
						$api = $this->GetApi();
						$api->Load($fieldid);

						$fieldapi = $this->GetApi('CustomFields_' . $api->fieldtype);
						if (!$fieldapi) {
							return false;
						}

						$fieldapi->Load($fieldid);
						$alloptions = $fieldapi->GetOptions();

						$newoptions = array();
						foreach ($alloptions as $fieldname => $option) {
							if (isset($_POST[$fieldname]) && is_array($_POST[$fieldname])) {
								$value = $_POST[$fieldname];
							} else {
								if (isset($customfield_settings[$fieldname])) {
									$value = $customfield_settings[$fieldname];
								} else {
									if (isset($_POST[$fieldname])) {
										$value = $_POST[$fieldname];
									} else {
										$value = false;
									}
								}
							}
							$newoptions[$fieldname] = $value;
						}

						if (isset($newoptions['Key']) && is_array($newoptions['Key'])) {
							foreach ($newoptions['Key'] as $key => $val) {
								if (!strlen($val) && isset($newoptions['Value'][$key]) && strlen($newoptions['Value'][$key])) {
									$newoptions['Key'][$key] = $newoptions['Value'][$key];
								}
							}
						}

						$fieldapi->Set($newoptions);

						$saveresult = $fieldapi->Save();

						$this->ManageCustomField_Lists($fieldid);

					break;
					default:
						$this->EditCustomField($fieldid);
				}
			break;

			case 'delete':
				$deletelist = (isset($_POST['customfields'])) ? $_POST['customfields'] : array();
				if (isset($_GET['id'])) {
					$deletelist = array((int)$_GET['id']);
				}
				$this->RemoveCustomFields($deletelist);
			break;

			case 'create':
				// see what step we're up to.
				$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : '';
				switch ($subaction) {
					case 'step2':
						$newfield = array();
						$newfield['FieldName'] = $_POST['FieldName'];
						$newfield['FieldType'] = $_POST['FieldType'];
						$newfield['FieldRequired'] = '';
						if(isset($_POST['FieldRequired'])){ $newfield['FieldRequired'] = 'on'; $GLOBALS['ApplyDefault'] = ' CHECKED';} else { $GLOBALS['ApplyDefault'] = ''; }
						IEM::sessionSet('CustomFields', $newfield);
						$this->CreateCustomField_Step2($newfield);
					break;

					case 'step3':
						$customfield_settings = IEM::sessionGet('CustomFields');

						$fieldapi = $this->GetApi('CustomFields_' . $customfield_settings['FieldType']);
						if (!$fieldapi) {
							return false;
						}

						$alloptions = $fieldapi->GetOptions();

						$newoptions = array();
                        if(isset($_POST['ApplyDefault'])){$newoptions['ApplyDefault'] = 'on';}
						foreach ($alloptions as $fieldname => $option) {
						    if(isset($newoptions[$fieldname])){continue;}  
							$value = (isset($customfield_settings[$fieldname])) ? $customfield_settings[$fieldname] : $_POST[$fieldname];

							$newoptions[$fieldname] = $value;
						}

						$fieldapi->Set($newoptions);

						$fieldapi->ownerid = $user->userid;

						$create = $fieldapi->Create();

						if (!$create) {
							$GLOBALS['Error'] = GetLang('UnableToCreateCustomField');
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
							break;
						}
						$this->ManageCustomField_Lists($create, true);
					break;

					default:
						$this->CreateCustomField_Step1();
				}
			break;
			default:
				$this->ManageCustomFields();
		}
		$this->PrintFooter();
	}

	/**
	* EditCustomField
	* Allows editing of a custom field. This also handles whether to show more options (if it's a checkbox/dropdown) and so on.
	*
	* @param Int $fieldid Fieldid to load and edit.
	*
	* @see GetApi
	* @see CustomFields_API::Load
	* @see CustomFields_API::Settings
	* @see MoreOptions
	* @see MoreOptionsToShow
	*
	* @return Void Doesn't return anything, just prints out the results.
	*/
	function EditCustomField($fieldid=0)
	{
		if ($fieldid <= 0) {
			return false;
		}

		$api = $this->GetApi();
		if (!$api->Load($fieldid)) {
			$GLOBALS['ErrorMessage'] = GetLang('CustomFieldDoesntExist');
			$this->DenyAccess();
			return;
		}

		$fieldapi = $this->GetApi('CustomFields_' . $api->fieldtype);
		$fieldapi->Load($fieldid);

		// Log this to "User Activity Log"
		IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/customfields.gif', $api->Settings['FieldName']);

		$GLOBALS['CustomFieldDetails'] = GetLang('EditCustomField');

		$GLOBALS['Action'] = 'Edit&SubAction=Update&id=' . $fieldid;
		$GLOBALS['CancelButton'] = GetLang('EditCustomField_CancelPrompt');
		$GLOBALS['Heading'] = GetLang('EditCustomField');
		$GLOBALS['Intro'] = GetLang('EditCustomFieldIntro');

		$type = $fieldapi->fieldtype;
		$GLOBALS['FieldType'] = GetLang('CustomFieldType_' . strtolower($type));

		$GLOBALS['FieldName'] = htmlspecialchars($fieldapi->Settings['FieldName'], ENT_QUOTES, SENDSTUDIO_CHARSET);

		$fieldoptions = $fieldapi->GetOptions();

		foreach ($fieldoptions as $name => $val) {
			if (!is_array($fieldapi->Settings[$name])) {
				$GLOBALS[$name] = htmlspecialchars($fieldapi->Settings[$name], ENT_QUOTES, SENDSTUDIO_CHARSET);
				continue;
			}

			foreach ($fieldapi->Settings[$name] as $p => $pname) {
				$GLOBALS['Display'.$p] = htmlspecialchars($pname, ENT_QUOTES, SENDSTUDIO_CHARSET);
			}
		}

		$required = '';
		if ($fieldapi->Settings['FieldRequired']) {
			$required = ' CHECKED';
		}

		$GLOBALS['FieldRequired'] = $required;

        if (isset($fieldapi->Settings['ApplyDefault'])) {
			$required = ' CHECKED';
		}

		$GLOBALS['ApplyDefault'] = $required;

		$GLOBALS['CancelButton'] = GetLang('EditCustomField_CancelPrompt');

		$currentlist = '';
		$extralist = '';
		$extralistdisplay = 'none';
		$addmorelinkdisplay = '';

		if (in_array($fieldapi->fieldtype, $this->MoreOptions)) {
			$extralist_template = 'CustomField_Form_Step2_' . $fieldapi->fieldtype . '_list_edit';
			$keysize = sizeof($fieldapi->Settings['Key']);

			for ($i = 1; $i <= $keysize; $i++) {
				$GLOBALS['KeyNumber'] = $i;
				$GLOBALS['Key'] = htmlspecialchars($fieldapi->Settings['Key'][$i-1], ENT_QUOTES, SENDSTUDIO_CHARSET);
				$GLOBALS['Value'] = htmlspecialchars($fieldapi->Settings['Value'][$i-1], ENT_QUOTES, SENDSTUDIO_CHARSET);
				$currentlist .= $this->ParseTemplate($extralist_template, true, false);
			}

			$end = $i;

			$GLOBALS['Key'] = '';
			$GLOBALS['Value'] = '';

			$GLOBALS['KeyNumber'] = $i;
			$extralist .= $this->ParseTemplate($extralist_template, true, false);

			$GLOBALS['CurrentSize'] = $i;
		}

		$GLOBALS['CurrentList'] = $currentlist;
		$GLOBALS['ExtraList'] = $extralist;
		$GLOBALS['ExtraListDisplay'] = $extralistdisplay;
		$GLOBALS['AddMoreLinkDisplay'] = $addmorelinkdisplay;
		$GLOBALS['HideMoreLinkDisplay'] = (strtolower($addmorelinkdisplay) == 'none') ? '' : 'none';

		// Load up the edit template specific to custom fields with multiple options if we're editing
		if (in_array($fieldapi->fieldtype, $this->MoreOptions) && isset($_GET['Action']) && $_GET['Action'] == 'Edit') {
			$type .= '_edit';
		}

		$GLOBALS['SubForm'] = $this->ParseTemplate('CustomField_Form_Step2_' . $type, true, false);

		$this->ParseTemplate('CustomField_Form_Edit');
	}

	/**
	* CreateCustomField_Step1
	* Prints step 1 of creating a custom field. Simply prints out the customfield types for choosing.
	*
	* @see CustomFieldTypes
	*
	* @return Void Doesn't return anything, just prints out the results.
	*/
	function CreateCustomField_Step1()
	{
		$user = GetUser();
		$lists = $user->GetLists();

		if (count($lists) == 0) {
			$this->ManageCustomFields();
			return;
		}

		$GLOBALS['Action'] = 'Create&SubAction=Step2';
		$GLOBALS['CancelButton'] = GetLang('CreateCustomFieldCancelButton');
		$GLOBALS['Heading'] = GetLang('CreateCustomField');
		$GLOBALS['Intro'] = GetLang('CreateCustomFieldIntro');
		$GLOBALS['ListDetails'] = GetLang('CreateCustomFieldHeading');

		$typelist = '';
		$count = 0;

		foreach ($this->CustomFieldTypes as $p => $type) {
			$typelist .= '<input onclick="cf_selected=true;" type="radio" name="FieldType" id="' . $type . '" value="' . $type . '"> <label for="' . $type . '">' . GetLang('CustomFieldType_' . strtolower($type)) . '</label> - <span style="color:#AAA">' . GetLang('CustomFieldDesc_' . strtolower($type)) . '</span>';

			// Add a placeholder after the first custom field type for the help (Added by Mitch)
			if ($count++ == 0) {
				$typelist .= '&nbsp;<span id="cfCustomHelp"></span>';
			}

			$typelist .= '<br />';
		}

		$GLOBALS['FieldTypeList'] = $typelist;
		$this->ParseTemplate('CustomField_Form_Step1');
	}

	/**
	* CreateCustomField_Step2
	* Prints step 2 of creating a custom field. Prints options based on the custom field type.
	*
	* @param Array $details Details to use to go to step 2. This comes from the session and includes the name, whether it's required and type.
	*
	* @see MoreOptions
	* @see MoreOptionsToShow
	*
	* @return Void Doesn't return anything, just prints out the results.
	*/
	function CreateCustomField_Step2($details=array())
	{
		if (empty($details)) {
			return false;
		}

		$fieldapi = $this->GetApi('CustomFields_' . $details['FieldType']);
		if (!$fieldapi) {
			return false;
		}

		$GLOBALS['CustomFieldDetails'] = $GLOBALS['Heading'] = GetLang('CreateCustomField_Step2');

		$GLOBALS['Intro'] = GetLang('CreateCustomField_Step2_Intro');

		$GLOBALS['Action'] = 'Create&SubAction=Step3';

		$details['FieldType'] = strtolower($details['FieldType']);

		if (in_array($details['FieldType'], $this->MoreOptions)) {
			$GLOBALS['DefaultValue'] = GetLang('DropdownInstructions');
		}

		$extralist = '';

		$GLOBALS['CancelButton'] = GetLang('CreateCustomField_CancelPrompt');

		$addmorelinkdisplay = 'none';
		$GLOBALS['AddMoreLinkDisplay'] = $addmorelinkdisplay;
		$GLOBALS['HideMoreLinkDisplay'] = (strtolower($addmorelinkdisplay) == 'none') ? '' : 'none';

		if (isset($fieldapi->Options['Key']) && is_array($fieldapi->Options['Key'])) {
			foreach ($fieldapi->Options['Key'] as $pos => $name) {
				$GLOBALS['Display'.$pos] = $name;
			}
		}

		if (in_array($details['FieldType'], $this->MoreOptions)) {
			$extralist_template = 'CustomField_Form_Step2_' . $details['FieldType'] . '_list';
			for ($i = 1; $i <= $this->MoreOptionsToShow; $i++) {
				$GLOBALS['KeyNumber'] = $i;
				$extralist .= $this->ParseTemplate($extralist_template, true, false);
			}
		}

		$GLOBALS['CurrentSize'] = $this->MoreOptionsToShow;

		$GLOBALS['ExtraList'] = $extralist;

		$GLOBALS['SubForm'] = $this->ParseTemplate('CustomField_Form_Step2_' . $details['FieldType'], true, false);

		$this->ParseTemplate('CustomField_Form_Step2');
	}

	/**
	* ManageCustomField_Lists
	* Prints out the custom field to list associations.
	*
	* @param Int $fieldid Fieldid to print associations for.
	* @param Boolean $newfield Whether we're creating a new field or not. This changes language variables accordingly.
	*
	* @see GetApi
	* @see CustomFields_API::Load
	* @see CustomFields_API::Settings
	* @see CustomFields_API::Associations
	* @see User_API::GetLists
	*
	* @return Void Doesn't return anything, just prints out the results.
	*/
	function ManageCustomField_Lists($fieldid=0, $newfield=false)
	{
		if ($fieldid <= 0) {
			return false;
		}

		$api = $this->GetApi();
		if (!$api->Load($fieldid)) {
			return false;
		}

		if ($newfield) {
			$GLOBALS['Heading'] = GetLang('CreateCustomField_Step3');
			$GLOBALS['Intro'] = GetLang('CreateCustomField_Step3_Intro');
			$GLOBALS['CancelButton'] = GetLang('CreateCustomField_CancelPrompt');
		} else {
			$GLOBALS['Heading'] = GetLang('EditCustomField_Step3');
			$GLOBALS['Intro'] = GetLang('EditCustomField_Step3_Intro');
			$GLOBALS['CancelButton'] = GetLang('EditCustomField_CancelPrompt');
		}

		$fieldapi = $this->GetApi('CustomFields_' . $api->fieldtype);
		$fieldapi->Load($fieldid);

		$user = IEM::getCurrentUser();
		$lists = $user->GetLists();

		$GLOBALS['fieldid'] = $fieldid;
		$GLOBALS['CustomFieldListAssociation'] = sprintf(GetLang('CustomFieldListAssociation'), $fieldapi->Settings['FieldName']);

		$list_assoc = '';

		$GLOBALS['ListAssociations'] = '';

		foreach ($lists as $listid => $listdetails) {
			$GLOBALS['ListAssociations'] .= '<option value="'. $listid . '"';

			if (in_array($listid, $fieldapi->Associations)) {
				$GLOBALS['ListAssociations'] .= ' selected="selected"';
			}
			$GLOBALS['ListAssociations'] .= '>' . htmlspecialchars($listdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		$this->ParseTemplate('CustomField_Form_Step3');
	}

	/**
	* ManageCustomFields
	* Prints out the list of custom fields that have been created. This also handles paging and so on.
	*
	* @see GetPerPage
	* @see GetCurrentPage
	* @see GetSortDetails
	* @see GetApi
	* @see User_API::Admin
	* @see CustomFields_API::GetCustomFields
	* @see CustomFields_API::Settings
	* @see SetupPaging
	*
	* @return Void Doesn't return anything, just prints out the results.
	*/
	function ManageCustomFields()
	{
		$user = GetUser();
		$perpage = $this->GetPerPage();

		$DisplayPage = $this->GetCurrentPage();
		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$api = $this->GetApi();

		$fieldowner = ($user->Admin()) ? 0 : $user->userid;
		$NumberOfFields = $api->GetCustomFields($fieldowner, $sortinfo, true);
		$myfields = $api->GetCustomFields($fieldowner, $sortinfo, false, $start, $perpage);

		if ($user->HasAccess('CustomFields', 'Create')) {
			$GLOBALS['CustomFields_AddButton'] = $this->ParseTemplate('CustomFields_Create_Button', true, false);
		}

		if ($user->HasAccess('CustomFields', 'Delete')) {
			$GLOBALS['CustomFields_DeleteButton'] = $this->ParseTemplate('CustomFields_Delete_Button', true, false);
		}

		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		$lists = $user->GetLists();
		$listids = array_keys($lists);
		if (sizeof($listids) < 1) {
			$GLOBALS['Intro_Help'] = GetLang('Help_CustomFieldsManage');
			$GLOBALS['Intro'] = GetLang('CustomFieldsManage');
			$GLOBALS['Lists_AddButton'] = '';

			if ($user->CanCreateList() === true) {
				$GLOBALS['Message'] = $this->PrintSuccess('CustomFields_NoLists', GetLang('ListCreate'));
				$GLOBALS['Lists_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
			} else {
				$GLOBALS['Message'] = $this->PrintSuccess('CustomFields_NoLists', GetLang('ListAssign'));
			}
			$this->ParseTemplate('Subscribers_No_Lists');
			return;
		}

		if ($NumberOfFields == 0) {
			$GLOBALS['Message'] .= $this->PrintSuccess('NoCustomFields');
			$this->ParseTemplate('CustomFields_Manage_Empty');
			return;
		}

		$this->SetupPaging($NumberOfFields, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging';
		$paging = $this->ParseTemplate('Paging', true, false);

		$template = $this->ParseTemplate('CustomFields_Manage', true, false);

		$customfieldlist = '';

		foreach ($myfields as $pos => $fieldinfo) {
			$api->Load($fieldinfo['fieldid']);
			$GLOBALS['id'] = $fieldinfo['fieldid'];
			$GLOBALS['Name'] = htmlspecialchars($fieldinfo['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Created'] = $this->PrintDate($api->createdate);
			$GLOBALS['CustomFieldType'] = GetLang('CustomFieldType_' . $api->fieldtype);
			$GLOBALS['CustomFieldRequired'] = ($api->Settings['FieldRequired']) ? GetLang('Yes') : GetLang('No');
			$GLOBALS['CustomFieldAction'] = '';

			if ($user->Admin() || ($user->HasAccess('customfields', 'edit') && $user->Get('userid') == $api->Get('ownerid'))) {
				$GLOBALS['CustomFieldAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=CustomFields&Action=Edit&id=' . $fieldinfo['fieldid'] . '">' . GetLang('Edit') . '</a>';
			} else {
				$GLOBALS['CustomFieldAction'] .= $this->DisabledItem('Edit');
			}

			if ($user->Admin() || ($user->HasAccess('customfields', 'delete') && $user->Get('userid') == $api->Get('ownerid'))) {
				$GLOBALS['CustomFieldAction'] .= '&nbsp;&nbsp;<a href="javascript: ConfirmDelete(' . $fieldinfo['fieldid'] . ');">' . GetLang('Delete') . '</a>';
			} else {
				$GLOBALS['CustomFieldAction'] .= $this->DisabledItem('Delete');
			}

			$customfieldlist .= $this->ParseTemplate('CustomFields_Manage_Row', true, false);
		}
		$template = str_replace('%%TPL_CustomFields_Manage_Row%%', $customfieldlist, $template);
		$template = str_replace('%%TPL_Paging%%', $paging, $template);
		$template = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $template);
		echo $template;
	}

	/**
	* RemoveCustomFields
	* Takes an array of customfield id's to remove from the database. It checks whether you are the owner of the custom field or if you are an admin user. If you are neither, you can't delete the field.
	*
	* @param Array $fields An array of fields the user wants to remove.
	*
	* @see GetUser
	* @see User_API::HasAccess
	* @see DenyAccess
	* @see GetAPI
	* @see CustomFields_API::Load
	* @see CustomFields_API::Delete
	* @see ManageCustomFields
	*
	* @return Void Doesn't return anything. Prints out the appropriate message based on what happened.
	*/
	function RemoveCustomFields($fields=array())
	{
		$user = GetUser();
		if (!$user->HasAccess('CustomFields', 'Delete')) {
			$this->DenyAccess();
			return;
		}

		if (!is_array($fields)) {
			$fields = array($fields);
		}

		$fields_api = $this->GetApi();

		$removed = 0; $notremoved = 0;
		$not_removed_errors = array();
		foreach ($fields as $pos => $fieldid) {
			$loaded = $fields_api->Load($fieldid);
			if (!$loaded) {
				continue;
			}
			if (!$user->Admin() && $user->Get('userid') != $fields_api->Get('ownerid')) {
				$not_removed_errors[$fieldid] = sprintf(GetLang('CannotDeleteCustomField_NoAccess'), $fields_api->Settings['FieldName']);
				$notremoved++;
				continue;
			}
			$status = $fields_api->Delete($fieldid);
			if ($status) {
				$removed++;
			} else {
				$notremoved++;
			}
		}

		$msg = '';

		if ($notremoved > 0) {
			if (empty($not_removed_errors)) {
				if ($notremoved == 1) {
					$GLOBALS['Error'] = GetLang('CustomFieldDeleteFail_One');
				} else {
					$GLOBALS['Error'] = sprintf(GetLang('CustomFieldDeleteFail_Many'), $this->FormatNumber($notremoved));
				}
				$msg .= $this->ParseTemplate('ErrorMsg', true, false);
			} else {
				foreach ($not_removed_errors as $fieldid => $message) {
					$GLOBALS['Error'] = $message;
					$msg .= $this->ParseTemplate('ErrorMsg', true, false);
				}
			}
		}

		if ($removed > 0) {
			if ($removed == 1) {
				$msg .= $this->PrintSuccess('CustomFieldDeleteSuccess_One');
			} else {
				$msg .= $this->PrintSuccess('CustomFieldDeleteSuccess_Many', $this->FormatNumber($removed));
			}
		}
		$GLOBALS['Message'] = $msg;

	  $this->ManageCustomFields();
	}

}
