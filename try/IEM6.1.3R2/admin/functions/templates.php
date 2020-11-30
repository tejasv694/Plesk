<?php
/**
* This file handles processing of templates.
*
* @version     $Id: templates.php,v 1.44 2008/01/16 00:25:56 chris Exp $
* @author Chris <chris@interspire.com>
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* This class handles processing of templates. This includes creating, editing, deleting and general management.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Templates extends SendStudio_Functions
{

	/**
	* ValidSorts
	* An array of sorts you can use for templates.
	*
	* @see ManageTemplates
	*
	* @var array
	*/
	var $ValidSorts = array('name', 'createdate');

	/**
	* PopupWindows
	* An array list of windows that pop up. This is used with the header function to work out which header to print.
	*
	* @see PrintHeader
	*
	* @var array
	*/
	var $PopupWindows = array('view');

	/**
	* Constructor
	* Loads the language file only.
	*
	* @return Void Doesn't return anything, just loads up the language files.
	*/
	function Templates()
	{
		$this->LoadLanguageFile('Templates');
		$this->LoadLanguageFile('Newsletters');
		$this->PopupWindows[] = 'viewcompatibility';
	}

	/**
	* Process
	* Works out where you are in the process and prints / processes the appropriate step.
	*
	* @see GetUser
	* @see User_API::HasAccess
	* @see PrintHeader
	* @see PopupWindows
	* @see PreviewWindow
	* @see ManageTemplates
	* @see EditTemplate
	* @see DisplayEditTemplate
	* @see ConvertContent
	* @see CreateTemplate
	*
	* @return Void Doesn't return anything. Handles processing and that's it.
	*/
	function Process()
	{
		$GLOBALS['Message'] = '';

		$action = strtolower(IEM::requestGetGET('Action', ''));
		$user = GetUser();

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = '';
		}

		// map the actions to the permissions required to do them
		$effective_permission = array(
			'' => null,
			'activate' => 'approve',
			'activateglobal' => 'global',
			'addtemplate' => 'create',
			'builtin' => 'builtin',
			'change' => 'edit',
			'complete' => 'view',
			'copy' => 'view',
			'create' => 'create',
			'deactivate' => 'approve',
			'deactivateglobal' => 'global',
			'edit' => 'edit',
			'manage' => null,
			'save' => 'edit',
			'step1' => 'view',
			'view' => 'view',
			'viewcompatibility' => null,
			);

		$access = false;

		if (!isset($_GET['id'])) {
			// we are not dealing with a particular template
			$access = $user->HasAccess('Templates', $effective_permission[$action]);
		} else if (!is_numeric($_GET['id'])) {
			// we are dealing with a particular built-in template
			$access = $user->HasAccess('Templates', 'builtin');
		} else {
			// we are dealing with a particular user template
			$id = intval($_GET['id']);
			if ($id == 0 && $action == 'create') {
				// we are saving/creating a new template
				$access = $user->HasAccess('Templates', $action);
			} else {
				$templates = array_keys($user->GetTemplates());
				if (in_array($id, $templates)) {
					// we at least have 'view' access
					if ($effective_permission[$action] == 'view') {
						$access = true;
					} else {
						$access = $this->_haveTemplateAccess($id, $effective_permission[$action]);
					}
				}
			}
		}

		$popup = (in_array($action, $this->PopupWindows)) ? true : false;
			if ($action != 'viewcompatibility') {
			$this->PrintHeader($popup);
		}

		if (!$access) {
			$this->DenyAccess();
		}

		switch ($action) {
			case 'viewcompatibility':
				$template_info = IEM::sessionGet('Templates'.$_GET['id']);

				$html = (isset($_POST['myDevEditControl_html'])) ? $_POST['myDevEditControl_html'] : false;
				$text = (isset($_POST['TextContent'])) ? $_POST['TextContent'] : false;
				$showBroken = isset($_REQUEST['ShowBroken']) && $_REQUEST['ShowBroken'] == 1;
				$details = array();
				$details['htmlcontent'] = $html;
				$details['textcontent'] = $text;
				$details['format'] = $template_info['Format'];

				$this->PreviewWindow($details, $showBroken);
				exit;
			break;

			case 'view':
				$details = array();
				$id = (isset($_GET['id'])) ? $_GET['id'] : 0;
				$type = strtolower(get_class($this));
				$template = $this->GetApi();
				if (is_numeric($id)) {
					if (!$template->Load($id)) {
						$details['textcontent'] = GetLang('UnableToLoadTemplate');
						$details['htmlcontent'] = '';
						$details['format'] = 't';
					} else {
						$details['htmlcontent'] = $template->GetBody('HTML');
						$details['textcontent'] = $template->GetBody('Text');
						$details['format'] = $template->format;
					}
				} else {
					$templatename = str_replace('servertemplate_', '', $id);

					$results = $template->ReadServerTemplate($templatename);
					if (!$results) {
						$details['textcontent'] = GetLang('UnableToLoadTemplateFromServer');
						$details['htmlcontent'] = '';
						$details['format'] = 't';
					} else {
						$details['htmlcontent'] = $results;
						$details['textcontent'] = '';
						$details['format'] = 'h';
					}
				}
				$this->PreviewWindow($details);
			break;

			case 'activate':
			case 'deactivate':
				$access = $user->HasAccess('Templates', 'approve');
				if (!$access) {
					$this->DenyAccess();
					break;
				}

				$id = (int)$_GET['id'];
				$templateapi = $this->GetApi();
				$templateapi->Load($id);

				$message = '';

				switch ($action) {
					case 'activate':
						$templateapi->Set('active', $user->Get('userid'));
						$GLOBALS['Success'] = GetLang('Template_ActivatedSuccessfully');
					break;
					case 'deactivate':
						$templateapi->Set('active', 0);
						if ($templateapi->IsGlobal()) {
							$GLOBALS['Error'] = GetLang('TemplateCannotBeInactiveAndGlobal');
							$message .= $this->ParseTemplate('ErrorMsg', true, false);
						}
						$GLOBALS['Success'] = GetLang('Template_DeactivatedSuccessfully');
				}
				$templateapi->Save();

				$message .= $this->ParseTemplate('SuccessMsg', true, false);
				$GLOBALS['Message'] = $message;

				$this->ManageTemplates();
			break;

			case 'activateglobal':
			case 'deactivateglobal':
				$access = $user->HasAccess('Templates', 'Global');
				if (!$access) {
					$this->DenyAccess();
					break;
				}

				$id = (int)$_GET['id'];
				$templateapi = $this->GetApi();
				$templateapi->Load($id);

				$message = '';

				switch ($action) {
					case 'activateglobal':
						$templateapi->Set('isglobal', $user->Get('userid'));
						$GLOBALS['Success'] = GetLang('Template_Global_ActivatedSuccessfully');
						if (!$templateapi->Active()) {
							$GLOBALS['Error'] = GetLang('TemplateCannotBeInactiveAndGlobal');
							$message .= $this->ParseTemplate('ErrorMsg', true, false);
						}
					break;
					case 'deactivateglobal':
						$templateapi->Set('isglobal', 0);
						$GLOBALS['Success'] = GetLang('Template_Global_DeactivatedSuccessfully');
					break;
				}
				$templateapi->Save();

				$message .= $this->ParseTemplate('SuccessMsg', true, false);
				$GLOBALS['Message'] = $message;

				$this->ManageTemplates();
			break;

			case 'delete':
				$templateid = (int)$_GET['id'];
				$access = $user->HasAccess('Templates', 'Delete');
				if ($access) {
					$this->DeleteTemplates(array($templateid));
				} else {
					$this->DenyAccess();
				}
			break;

			case 'change':
				$subaction = strtolower($_POST['ChangeType']);
				$templatelist = $_POST['templates'];

				switch ($subaction) {
					case 'delete':
						$access = $user->HasAccess('Templates', 'Delete');
						if ($access) {
							$this->DeleteTemplates($templatelist);
						} else {
							$this->DenyAccess();
						}
					break;

					case 'activate':
					case 'deactivate':
						$access = $user->HasAccess('Templates', 'Approve');
						if ($access) {
							$this->ActionTemplates($templatelist, $subaction);
						} else {
							$this->DenyAccess();
						}
					break;

					case 'global':
					case 'disableglobal':
						$access = $user->HasAccess('Templates', 'Global');
						if ($access) {
							$this->ActionTemplates($templatelist, $subaction);
						} else {
							$this->DenyAccess();
						}
					break;
				}
			break;

			case 'copy':
				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
				$api = $this->GetApi();
				list($result, $newid, $files_copied) = $api->Copy($id);
				if (!$result) {
					$GLOBALS['Error'] = GetLang('TemplateCopyFail');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				} else {
					$changed = false;
					// check the permissions.
					// if we can't make it global, disable this aspect of it.
					if (!$user->HasAccess('Templates', 'Global')) {
						$changed = true;
						$api->Set('isglobal', 0);
					}

					// if we can't approve templates, then make sure we disable it.
					if (!$user->HasAccess('Templates', 'Approve')) {
						$changed = true;
						$api->Set('active', 0);
					}

					if ($changed) {
						$api->Save();
					}
					$GLOBALS['Message'] = $this->PrintSuccess('TemplateCopySuccess');
					if (!$files_copied) {
						$GLOBALS['Error'] = GetLang('TemplateFilesCopyFail');
						$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
					}
				}

				$user->LoadPermissions($user->userid);
				$user->GrantTemplateAccess($newid);
				$user->SavePermissions();

				$this->ManageTemplates();
			break;

			case 'edit':
				$template = $this->GetApi();

				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
				$template->Load($id);

				$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : '';
				switch ($subaction) {
					case 'step2':
						$edittemplate = array('id' => $id);

						$checkfields = array('Name', 'Format');
						$valid = true; $errors = array();
						foreach ($checkfields as $p => $field) {
							if ($_POST[$field] == '') {
								$valid = false;
								$errors[] = GetLang('Template'.$field.'IsNotValid');
								break;
							} else {
								$value = $_POST[$field];
								$edittemplate[$field] = $value;
							}
						}

						if (!$valid) {
							$GLOBALS['Error'] = GetLang('UnableToUpdateTemplate') . '<br/>- ' . implode('<br/>- ',$errors);
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
							$this->EditTemplate($id);
							break;
						}

						IEM::sessionSet('Templates'.$id, $edittemplate);
						$this->DisplayEditTemplate($id);
					break;

					case 'save':
					case 'complete':
						$session_template = IEM::sessionGet('Templates'.$id);

						if (isset($_POST['TextContent'])) {
							$template->SetBody('Text', $_POST['TextContent']);
							$textcontent = $_POST['TextContent'];
						}

						if (isset($_POST['myDevEditControl_html'])) {
							$htmlcontent = $_POST['myDevEditControl_html'];

							/**
							 * This is an effort not to overwrite the eixsting HTML contents
							 * if there isn't any contents in it (DevEdit will have '<html><body></body></html>' as a minimum
							 * that will be passed to here)
							 */
							if (trim($htmlcontent) == '') {
								$GLOBALS['Error'] = GetLang('UnableToUpdateTemplate');
								$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
								$this->DisplayEditTemplate($id);
								break;
							}

							$template->SetBody('HTML', $_POST['myDevEditControl_html']);
						}

						foreach (array('Name', 'Format') as $p => $area) {
							$template->Set(strtolower($area), $session_template[$area]);
						}

						$template->Set('active', 0);
						if (($user->HasAccess('Templates', 'Approve', $id)) ||
                                                     $template->ownerid == $user->userid) {
							if (isset($_POST['active'])) {
								$template->Set('active', $user->Get('userid'));
							}
						}

						$template->Set('isglobal', 0);

						if ($user->HasAccess('Templates', 'Global') && isset($_POST['isglobal'])) {
							$template->Set('isglobal', 1);
						}

						$dest = strtolower(get_class($this));
						$movefiles_result = $this->MoveFiles($dest, $id);
						if ($movefiles_result) {
							if (isset($textcontent)) {
								$textcontent = $this->ConvertContent($textcontent, $dest, $id);
								$template->SetBody('Text', $textcontent);
							}
							if (isset($htmlcontent)) {
								$htmlcontent = $this->ConvertContent($htmlcontent, $dest, $id);
								$template->SetBody('HTML', $htmlcontent);
							}
						}

						$result = $template->Save();

						if (!$result) {
							$GLOBALS['Error'] = GetLang('UnableToUpdateTemplate');
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
							$this->ManageTemplates();
							break;
						} else {
							$GLOBALS['Message'] = $this->PrintSuccess('TemplateUpdated');

							if (!$template->Active() && isset($_POST['isglobal'])) {
								$GLOBALS['Error'] = GetLang('TemplateCannotBeInactiveAndGlobal');
								$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
							}
						}
						($subaction == 'save') ? $this->DisplayEditTemplate($id) : $this->ManageTemplates();
					break;

					default:
					case 'step1':
						$this->EditTemplate($id);
					break;
				}
			break;
			case 'create':
				$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : '';
				switch ($subaction) {
					case 'step2':
						$server_template = false;
						if (isset($_POST['TemplateID'])) {
							$server_template = $_POST['TemplateID'];
						}

						$newtemplate = array();
						$checkfields = array('Name', 'Format');
						$valid = true; $errors = array();
						foreach ($checkfields as $p => $field) {
							if ($_POST[$field] == '') {
								$valid = false;
								$errors[] = GetLang('Template'.$field.'IsNotValid');
								break;
							} else {
								$value = $_POST[$field];
								$newtemplate[$field] = $value;
							}
						}
						if (!$valid) {
							$GLOBALS['Error'] = GetLang('UnableToCreateTemplate') . '<br/>- ' . implode('<br/>- ',$errors);
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
							$this->CreateTemplate();
							break;
						}
						IEM::sessionSet('Templates0', $newtemplate);
						$this->DisplayEditTemplate(0, $server_template);
					break;

					case 'save':
					case 'complete':
						$user = IEM::getCurrentUser();
						$session_template = IEM::sessionGet('Templates0');

						$newtemplate = $this->GetApi();

						if (isset($_POST['TextContent'])) {
							$textcontent = $_POST['TextContent'];
							$newtemplate->SetBody('Text', $textcontent);
						}
						if (isset($_POST['myDevEditControl_html'])) {
							$htmlcontent = $_POST['myDevEditControl_html'];
							$newtemplate->SetBody('HTML', $htmlcontent);
						}

						foreach (array('Name', 'Format') as $p => $area) {
							$newtemplate->Set(strtolower($area), $session_template[$area]);
						}

						$newtemplate->Set('active', 0);
						if ($user->HasAccess('Templates', 'Approve')) {
							if (isset($_POST['active'])) {
								$newtemplate->Set('active', $user->Get('userid'));
							}
						}

						$newtemplate->Set('isglobal', 0);

						if ($user->HasAccess('Templates', 'Global') && isset($_POST['isglobal'])) {
							$newtemplate->Set('isglobal', 1);
						}

						$newtemplate->ownerid = $user->userid;
						$result = $newtemplate->Create();
						IEM::sessionSet('Templates'.$result, IEM::sessionGet('Templates0'));

						if (!$result) {
							$GLOBALS['Error'] = GetLang('UnableToCreateTemplate');
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
							$this->ManageTemplates();
							break;

						}

						$GLOBALS['Message'] = $this->PrintSuccess('TemplateCreated');

						if (!$newtemplate->Active() && isset($_POST['isglobal'])) {
							$GLOBALS['Error'] = GetLang('TemplateCannotBeInactiveAndGlobal');
							$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
						}

						$dest = strtolower(get_class($this));
						$movefiles_result = $this->MoveFiles($dest, $result);
						if ($movefiles_result) {
							if (isset($textcontent)) {
								$textcontent = $this->ConvertContent($textcontent, $dest, $result);
								$newtemplate->SetBody('Text', $textcontent);
							}
							if (isset($htmlcontent)) {
								$htmlcontent = $this->ConvertContent($htmlcontent, $dest, $result);
								$newtemplate->SetBody('HTML', $htmlcontent);
							}
						}
						$newtemplate->Save();

						$user->LoadPermissions($user->userid);
						$user->GrantTemplateAccess($result);
						$user->SavePermissions();

						if ($subaction == 'save') {
							$this->DisplayEditTemplate($result);
						} else {
							$this->ManageTemplates();
						}
					break;

					default:
					$this->CreateTemplate();
				}
			break;

			case 'addtemplate':
				$template = $this->GetApi();
				$user = IEM::getCurrentUser();

				$valid = true; $errors = array();
				if (!$valid) {
					$GLOBALS['Error'] = GetLang('UnableToCreateTemplate') . '<br/>- ' . implode('<br/>- ',$errors);
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->CreateTemplate();
					break;
				}

				$template->ownerid = $user->userid;

				$create = $template->Create();
				if (!$create) {
					$GLOBALS['Error'] = GetLang('UnableToCreateTemplate');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->CreateTemplate();
				} else {
					$GLOBALS['Message'] = $this->PrintSuccess('TemplateCreated');
					$this->EditTemplate($create);
				}
			break;

			case 'builtin':
				$this->ManageBuiltInTemplates();
			break;

			default:
				$this->ManageTemplates();
			break;
		}
		$this->PrintFooter($popup);
	}

	/**
	* ManageTemplates
	* Prints out a list of templates for this user to use. If they are an admin user, they get to see everything.
	*
	* @see GetPerPage
	* @see GetCurrentPage
	* @see GetSortDetails
	* @see GetApi
	* @see User_API::Admin
	* @see Templates_API::GetTemplates
	* @see Templates_API::GetFormat
	* @see SetupPaging
	* @see PrintDate
	* @see User_API::HasAccess
	*
	* @return Void Prints out the manage templates list and doesn't return anything.
	*/
	function ManageTemplates()
	{
		$user = IEM::getCurrentUser();
		$perpage = $this->GetPerPage();

		$DisplayPage = $this->GetCurrentPage();

		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$templateapi = $this->GetApi();

		$all_templates = $user->GetTemplates();
		$templates = array_keys($all_templates);

		$NumberOfTemplates = count($templates);

		$GLOBALS['Help_TemplatesManage'] = '';

		$check_templates = $templates;
		// if we're a template admin, no point checking - we have access to everything.
		if ($user->TemplateAdmin()) {
			$check_templates = null;
		}

		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		$mytemplates = $templateapi->GetTemplates($check_templates, $sortinfo, false, $start, $perpage);

		if ($user->HasAccess('Templates', 'Create')) {
			$GLOBALS['Help_TemplatesManage'] = GetLang('Help_TemplatesManage_Create');
			$GLOBALS['Templates_AddButton'] = $this->ParseTemplate('Template_Create_Button', true, false);
		}

		if ($user->HasAccess('Templates', 'Delete')) {
			$GLOBALS['Option_DeleteTemplate'] = '<option value="Delete">' . GetLang('Delete') . '</option>';
		}

		if ($user->HasAccess('Templates', 'Approve')) {
			$GLOBALS['Option_ActivateTemplate'] = '<option value="activate">' . GetLang('ActivateTemplates') . '</option>';
			$GLOBALS['Option_ActivateTemplate'] .= '<option value="deactivate">' . GetLang('InactivateTemplates') . '</option>';
		}

		if ($user->HasAccess('Templates', 'Global')) {
			$GLOBALS['Option_GlobalTemplate'] = '<option value="Global">' . GetLang('GlobalTemplates') . '</option>';
			$GLOBALS['Option_GlobalTemplate'] .= '<option value="DisableGlobal">' . GetLang('DisableGlobalTemplates') . '</option>';
		}

		if ($NumberOfTemplates == 0) {
			if ($user->HasAccess('Templates', 'Create')) {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoTemplates', GetLang('NoTemplates_HasAccess'));
			} else {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoTemplates', '');
			}
			$this->ParseTemplate('Templates_Manage_Empty');
			return;
		}

		$this->SetupPaging($NumberOfTemplates, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging';
		$paging = $this->ParseTemplate('Paging', true, false);

		$template_manage = $this->ParseTemplate('Templates_Manage', true, false);

		$templatedisplay = '';

		foreach ($mytemplates as $pos => $templatedetails) {
			$templateid = $templatedetails['templateid'];
			$GLOBALS['Name'] = htmlspecialchars($templatedetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			if ($user->TemplateAdmin()) {
				$GLOBALS['Name'] .= sprintf(GetLang('TemplateID'), $templateid);
			}
			$GLOBALS['Created'] = $this->PrintDate($templatedetails['createdate']);
			$GLOBALS['Format'] = GetLang('Format_' . $templateapi->GetFormat($templatedetails['format']));

			if ($templatedetails['active'] > 0) {
				$statusaction = 'deactivate';
				$activeicon = 'tick';
				$activetitle = GetLang('Template_Title_Disable');
			} else {
				$statusaction = 'activate';
				$activeicon = 'cross';
				$activetitle = GetLang('Template_Title_Enable');
			}
			$GLOBALS['id'] = $templateid;

			if (($templatedetails['ownerid'] == $user->userid) || ($user->HasAccess('Templates', 'Approve', $templateid))) {
				$GLOBALS['ActiveAction'] = '<a href="index.php?Page=Templates&Action=' . $statusaction . '&id=' . $templateid . '" title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0"></a>';
			} else {
				$GLOBALS['ActiveAction'] = '<span><img src="images/' . $activeicon . '.gif" border="0"></span>';
			}

			if ($templatedetails['isglobal'] > 0) {
				$statusaction = 'deactivateglobal';
				$activeicon = 'tick';
				$activetitle = GetLang('Template_Title_Global_Disable');
			} else {
				$statusaction = 'activateglobal';
				$activeicon = 'cross';
				$activetitle = GetLang('Template_Title_Global_Enable');
			}

			if (($templatedetails['ownerid'] == $user->userid) || ($user->HasAccess('Templates', 'Global', $templateid))) {
				$GLOBALS['IsGlobalAction'] = '<a href="index.php?Page=Templates&Action=' . $statusaction . '&id=' . $templateid . '" title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0"></a>';
			} else {
				$GLOBALS['IsGlobalAction'] = '<span><img src="images/' . $activeicon . '.gif" border="0"></span>';
			}

			$GLOBALS['TemplateAction']  = '<a href="index.php?Page=Templates&Action=View&id=' . $templateid . '" target="_blank">' . GetLang('View') . '</a>';

			if ($this->_haveTemplateAccess($templatedetails, 'Edit')) {
				$GLOBALS['TemplateAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Templates&Action=Edit&id=' . $templateid . '">' . GetLang('Edit') . '</a>';
			} else {
				$GLOBALS['TemplateAction'] .= $this->DisabledItem('Edit');
			}

			if ($user->HasAccess('Templates', 'Create')) {
				$GLOBALS['TemplateAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Templates&Action=Copy&id=' . $templateid . '">' . GetLang('Copy') . '</a>';
			} else {
				$GLOBALS['TemplateAction'] .= $this->DisabledItem('Copy');
			}

			if ($this->_haveTemplateAccess($templatedetails, 'Delete')) {
				$GLOBALS['TemplateAction'] .= '&nbsp;&nbsp;<a href="javascript: ConfirmDelete(' . $templateid . ');">' . GetLang('Delete') . '</a>';
			} else {
				$GLOBALS['TemplateAction'] .= $this->DisabledItem('Delete');
			}

			$templatedisplay .= $this->ParseTemplate('Templates_Manage_Row', true, false);
		}
		$template_manage = str_replace('%%TPL_Templates_Manage_Row%%', $templatedisplay, $template_manage);
		$template_manage = str_replace('%%TPL_Paging%%', $paging, $template_manage);
		$template_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $template_manage);

		echo $template_manage;
	}

	/**
	* EditTemplate
	* Prints out stage 1 of editing a template (selecting a format etc).
	*
	* @param Int $templateid Templateid to edit.
	*
	* @see GetApi
	* @see Templates_API::Load
	* @see Templates_API::GetAllFormats
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function EditTemplate($templateid=0)
	{
		$template = $this->GetApi();

		if ($templateid <= 0 || !$template->Load($templateid)) {
			$GLOBALS['Error'] = GetLang('UnableToLoadTemplate');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageTemplates();
			return;
		}

		$GLOBALS['Action'] = 'Edit&SubAction=Step2&id=' . $templateid;
		$GLOBALS['CancelButton'] = GetLang('EditTemplateCancelButton');
		$GLOBALS['Heading'] = GetLang('EditTemplate');
		$GLOBALS['Intro'] = GetLang('EditTemplateIntro');
		$GLOBALS['TemplateDetails'] = GetLang('EditTemplateHeading');

		$GLOBALS['FormatList'] = '';
		$allformats = $template->GetAllFormats();
		foreach ($allformats as $id => $name) {
			$selected = '';
			if ($id == $template->format) {
				$selected = ' SELECTED';
			}

			$GLOBALS['FormatList'] .= '<option value="' . $id . '"' . $selected . '>' . GetLang('Format_' . $name) . '</option>';
		}
		$GLOBALS['Name'] = htmlspecialchars($template->name, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['DisplayTemplateList'] = 'none';

		$this->ParseTemplate('Template_Form_Step1');
	}

	/**
	* CreateTemplate
	* Prints out stage 1 of creating a template (selecting a format etc).
	*
	* @see GetApi
	* @see Templates_API::GetAllFormats
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function CreateTemplate()
	{
		$templateapi = $this->GetApi();

		$GLOBALS['Action'] = 'Create&SubAction=Step2';
		$GLOBALS['CancelButton'] = GetLang('CreateTemplateCancelButton');
		$GLOBALS['Heading'] = GetLang('CreateTemplate');
		$GLOBALS['Intro'] = GetLang('CreateTemplateIntro');
		$GLOBALS['TemplateDetails'] = GetLang('CreateTemplateHeading');

		$GLOBALS['FormatList'] = '';
		$allformats = $templateapi->GetAllFormats();
		foreach ($allformats as $id => $name) {
			$GLOBALS['FormatList'] .= '<option value="' . $id . '">' . GetLang('Format_' . $name) . '</option>';
		}

		$templateselects = $this->GetTemplateList(true);
		$GLOBALS['TemplateList'] = $templateselects;

		$this->ParseTemplate('Template_Form_Step1');
	}

	/**
	* DisplayEditTemplate
	* Prints out stage 2 of editing a template based on whether this is a text, html or multipart template. This information is stored in the session, so we need to retrieve those settings.
	* This function is used both when creating and editing a template.
	*
	* @param Int $templateid If there is a template id, we are updating an existing template. If there is no template id, we are creating a new template. This changes form actions depending on what we're doing.
	*
	* @see GetApi
	* @see GetUser
	* @see Templates_API::Load
	* @see Templates_API::GetBody
	* @see FetchEditor
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function DisplayEditTemplate($templateid=0, $server_template=false)
	{
		$template = $this->GetApi();
		$templatecontents = array('text' => '', 'html' => '');

		$user = IEM::getCurrentUser();

		if ($templateid > 0) {
			$GLOBALS['SaveAction'] = 'Edit&SubAction=Save&id=' . $templateid;
			$GLOBALS['Heading'] = GetLang('EditTemplate');
			$GLOBALS['Intro'] = GetLang('EditTemplateIntro_Step2');
			$GLOBALS['Action'] = 'Edit&SubAction=Complete&id=' . $templateid;
			$GLOBALS['CancelButton'] = GetLang('EditTemplateCancelButton');

			$template->Load($templateid);

			$show_misc_options = false;
			if ($user->HasAccess('Templates', 'Approve')) {
				$show_misc_options = true;
				$GLOBALS['IsActive'] = ($template->Active()) ? ' CHECKED' : '';
			} else {
				$GLOBALS['ShowActive'] = 'none';
			}

			if ($user->HasAccess('Templates', 'Global')) {
				$show_misc_options = true;
				$GLOBALS['IsGlobal'] = ($template->IsGlobal() && $template->Active()) ? ' CHECKED' : '';
			} else {
				$GLOBALS['ShowGlobal'] = 'none';
			}

			if (!$show_misc_options) {
				$GLOBALS['ShowMiscOptions'] = 'none';
			}

			$templatecontents['text'] = $template->GetBody('text');
			$templatecontents['html'] = $template->GetBody('html');
		} else {
			$GLOBALS['SaveAction'] = 'Create&SubAction=Save&id=' . $templateid;
			$GLOBALS['Heading'] = GetLang('CreateTemplate');
			$GLOBALS['Intro'] = GetLang('CreateTemplateIntro_Step2');
			$GLOBALS['Action'] = 'Create&SubAction=Complete';
			$GLOBALS['CancelButton'] = GetLang('CreateTemplateCancelButton');

			if (!$user->HasAccess('Templates', 'Global')) {
				$GLOBALS['ShowGlobal'] = 'none';
			}

			$show_misc_options = false;
			if ($user->HasAccess('Templates', 'Approve')) {
				$GLOBALS['IsActive'] = ' CHECKED';
				$show_misc_options = true;
			} else {
				$GLOBALS['ShowActive'] = 'none';
			}

			if (!$show_misc_options) {
				$GLOBALS['ShowMiscOptions'] = 'none';
			}
		}

		if ($server_template) {
			$templatecontents['html'] = $template->ReadServerTemplate($server_template);
		}

		// we don't really need to get/set the stuff here.. we could use references.
		// if we do though, it segfaults! so we get and then set the contents.
		$session_template = IEM::sessionGet('Templates'.$templateid);
		$session_template['id'] = (int)$templateid;
		$session_template['contents'] = $templatecontents;
		IEM::sessionSet('Templates'.$templateid, $session_template);
		$editor = $this->FetchEditor('Templates'.$templateid);
		$GLOBALS['Editor'] = $editor;
		$this->ParseTemplate('Template_Form_Step2');
	}

	/**
	* DeleteTemplates
	* This will attempt to delete the templates based on the id's passed in.
	* It checks whether you are trying to delete a global template, if you are and you don't have access, an error message is shown.
	* If you are the owner of the template, you can do whatever you like with it.
	* It will also remove permissions for this user so in future it won't show up (just in case).
	*
	* @param Array $templateids An array of templateid's to delete
	*
	* @see GetApi
	* @see API::CheckIntVars
	* @see ManageTemplates
	* @see GetUser
	* @see Templates_API::Load
	* @see Templates_API::IsGlobal
	* @see User_API::HasAccess
	* @see User_API::RevokeTemplateAccess
	* @see User_API::SavePermissions
	* @see Templates_API::Delete
	* @see FormatNumber
	*
	* @return Void Doesn't return anything. Deletes the templateid's passed in if it can, then prints out a message about what actions did or didn't occur.
	*/
	function DeleteTemplates($templateids=array())
	{
		if (!is_array($templateids)) {
			$templateids = array($templateids);
		}

		$api = $this->GetApi();
		$templateids = $api->CheckIntVars($templateids);

		if (empty($templateids)) {
			$GLOBALS['Error'] = GetLang('NoTemplatesSelected');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageTemplates();
			return;
		}

		$user = GetUser();

		$delete_ok = $delete_fail = 0;
		$delete_fail_messages = array();

		$images_found_messages = array();

		$user->LoadPermissions($user->userid);

		foreach ($templateids as $p => $templateid) {
			$api->Load($templateid);
			if ($api->IsGlobal() && !$user->HasAccess('Templates', 'Global')) {
				$delete_fail++;
				$delete_fail_messages[$templateid] = sprintf(GetLang('CannotDeleteGlobalTemplate_NoAccess'), $api->Get('name'));
				continue;
			}

			$status = $api->Delete($templateid);
			if ($status) {
				$delete_ok++;
				$user->RevokeTemplateAccess($templateid);

				$preview_file = SENDSTUDIO_RESOURCES_DIRECTORY . '/user_template_previews/' . $templateid . '_preview.gif';
				if (is_file($preview_file)) {
					$images_found_messages[] = sprintf(GetLang('DeleteTemplatePreview_Image'), $templateid);
				}

			} else {
				$delete_fail++;
			}
		}

		$user->SavePermissions();

		$msg = '';

		if ($delete_fail > 0) {
			if (empty($delete_fail_messages)) {
				if ($delete_fail == 1) {
					$GLOBALS['Error'] = GetLang('Template_NotDeleted');
				} else {
					$GLOBALS['Error'] = sprintf(GetLang('Templates_NotDeleted'), $this->FormatNumber($delete_fail));
				}
				$msg .= $this->ParseTemplate('ErrorMsg', true, false);

			} else {
				foreach ($delete_fail_messages as $templateid => $message) {
					$GLOBALS['Error'] = $message;
					$msg .= $this->ParseTemplate('ErrorMsg', true, false);
				}
			}
		}

		if ($delete_ok > 0) {
			if ($delete_ok == 1) {
				$msg .= $this->PrintSuccess('Template_Deleted');
			} else {
				$msg .= $this->PrintSuccess('Templates_Deleted', $this->FormatNumber($delete_ok));
			}
		}

		if (!empty($images_found_messages)) {
			if ($user->TemplateAdmin()) {
				$GLOBALS['Warning'] = implode('<br/>', $images_found_messages);
				$msg .= $this->ParseTemplate('WarningMsg', true, false);
			}
		}

		$GLOBALS['Message'] = $msg;

		$this->ManageTemplates();

	}

	/**
	* ActionTemplates
	* This will perform the action passed in to all the templates in the array.
	* The action can be approve, disapprove, global, disableglobal only. Anything else throws an error message and the user is taken back to the manage templates page.
	*
	* @param Array $templateids An array of templateid's to perform an action on
	* @param String $action The action to perform. Can be one of approve, disapprove, global, disableglobal.
	*
	* @see GetApi
	* @see API::CheckIntVars
	* @see ManageTemplates
	* @see GetUser
	* @see Templates_API::Load
	* @see Templates_API::Save
	* @see FormatNumber
	*
	* @return Void Doesn't return anything. Processes the templates based on the action, prints a message out about what happened and takes the user back to the manage templates screen.
	*/
	function ActionTemplates($templateids=array(), $action='')
	{
		if (!is_array($templateids)) {
			$templateids = array($templateids);
		}

		$templateapi = $this->GetApi();

		$templateids = $templateapi->CheckIntVars($templateids);

		if (empty($templateids)) {
			$GLOBALS['Error'] = GetLang('NoTemplatesSelected');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageTemplates();
			return;
		}

		$action = strtolower($action);

		if (!in_array($action, array('activate', 'deactivate', 'approve', 'disapprove', 'global', 'disableglobal'))) {
			$GLOBALS['Error'] = GetLang('InvalidTemplateAction');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageTemplates();
			return;
		}

		$user = GetUser();

		$update_ok = $update_fail = 0;
		foreach ($templateids as $p => $templateid) {
			$templateapi->Load($templateid);

			switch ($action) {
				case 'approve':
				case 'activate':
					$langvar = 'Approved';
					$templateapi->Set('active', $user->Get('userid'));
				break;

				case 'disapprove':
				case 'deactivate':
					$langvar = 'Disapproved';
					$templateapi->Set('active', 0);
				break;

				case 'global':
					$langvar = 'Global';
					$templateapi->Set('isglobal', $user->Get('userid'));
				break;

				case 'disableglobal':
					$langvar = 'DisableGlobal';
					$templateapi->Set('isglobal', 0);
				break;
			}
			$status = $templateapi->Save();
			if ($status) {
				$update_ok++;
			} else {
				$update_fail++;
			}
		}

		$msg = '';

		if ($update_fail > 0) {
			if ($update_fail == 1) {
				$GLOBALS['Error'] = GetLang('Template_Not' . $langvar);
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Templates_Not' . $langvar), $this->FormatNumber($update_fail));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($update_ok > 0) {
			if ($update_ok == 1) {
				$msg .= $this->PrintSuccess('Template_' . $langvar);
			} else {
				$msg .= $this->PrintSuccess('Templates_' . $langvar, $this->FormatNumber($update_ok));
			}
		}
		$GLOBALS['Message'] = $msg;

		$this->ManageTemplates();
	}

	/**
	* ManageTemplates
	* Prints out a list of templates for this user to use. If they are an admin user, they get to see everything.
	*
	* @see GetPerPage
	* @see GetCurrentPage
	* @see GetSortDetails
	* @see GetApi
	* @see User_API::Admin
	* @see Templates_API::GetTemplates
	* @see Templates_API::GetFormat
	* @see SetupPaging
	* @see PrintDate
	* @see User_API::HasAccess
	*
	* @return Void Prints out the manage templates list and doesn't return anything.
	*/
	function ManageBuiltInTemplates()
	{

		$template_names = array();

		$template_packs = array();

		$server_template_list = list_files(SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY . '/', null, true, true);
		if (!$server_template_list) {
			$GLOBALS['Message'] .= $this->PrintSuccess('NoTemplatesBuiltIn', '');
			$this->ParseTemplate('Templates_BuiltIn_Manage_Empty');
			return;
		}

		// we only support two folders depth currently so we'll hardcode the look.
		if ($server_template_list) {
			foreach ($server_template_list as $template_name => $sub_templates) {
				if ($template_name == 'CVS' || $template_name == '.svn') {
					continue;
				}
				$sub_folders = array_keys($sub_templates);
				if (in_array('CVS', $sub_folders)) {
					$pos = array_search('CVS', $sub_folders);
					if ($pos !== false) {
						unset($sub_folders[$pos]);
					}
				}
				if (in_array('.svn', $sub_folders)) {
					$pos = array_search('.svn', $sub_folders);
					if ($pos !== false) {
						unset($sub_folders[$pos]);
					}
				}

				if (!empty($sub_folders)) {
					$template_packs[] = array('Name' => $template_name, 'Designs' => $sub_folders);
					continue;
				}
				$template_names[] = $template_name;
			}
		}

		natsort($template_names);
		sort($template_packs);

		$template_manage = $this->ParseTemplate('Templates_BuiltIn_Manage', true, false);

		$template_display = '';

		if (!empty($template_names)) {
			foreach ($template_names as $p => $name) {
				$GLOBALS['Template_ID'] = $name;
				if (is_file(SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY . '/' . $name . '/preview.gif')) {
					$GLOBALS['PreviewImage'] = SENDSTUDIO_RESOURCES_URL . '/email_templates/' . $name . '/preview.gif';
				} else {
					$GLOBALS['PreviewImage'] = SENDSTUDIO_IMAGE_URL . '/nopreview_builtin.gif';
				}
				$GLOBALS['Name'] = sprintf(GetLang('BuiltInTemplate_Preview_Template'), $name);

				$template_display .= $this->ParseTemplate('Templates_BuiltIn_Manage_Cell', true, false);
			}
		}

		if (!empty($template_packs)) {
			foreach ($template_packs as $tp => $details) {
				natsort($details['Designs']);
				foreach ($details['Designs'] as $p => $templatename) {
					$GLOBALS['Template_ID'] = $details['Name'] . '/' . $templatename;
					if (is_file(SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY . '/' . $details['Name'] . '/' . $templatename . '/preview.gif')) {
						$GLOBALS['PreviewImage'] = SENDSTUDIO_RESOURCES_URL . '/email_templates/' . $details['Name'] . '/' . $templatename . '/preview.gif';
					} else {
						$GLOBALS['PreviewImage'] = SENDSTUDIO_IMAGE_URL . '/nopreview_builtin.gif';
					}
					$GLOBALS['Name'] = sprintf(GetLang('BuiltInTemplate_Preview_TemplatePack'), $templatename, $details['Name']);

					$template_display .= $this->ParseTemplate('Templates_BuiltIn_Manage_Cell', true, false);
				}
			}
		}

		$template_manage = str_replace('%%TPL_Templates_BuiltIn_Manage_Row%%', $template_display, $template_manage);
		echo $template_manage;
	}

	/**
	 * Have Access
	 * Check whether or not current user have access to the template
	 *
	 * @param array|integer $templateRecord Template record or template ID to check
	 * @param string $action Action to check
	 *
	 * @return boolean Returns TRUE if user have access, FALSE otherwise
	 */
	private function _haveTemplateAccess($templateRecord, $action)
	{
		$currentUser = IEM::getCurrentUser();

		if (!is_array($templateRecord)) {
			$templateid = intval($templateRecord);
			if ($templateid == 0) {
				return false;
			}

			$templateapi = $this->GetApi('Templates');
			if (!$templateapi->Load($templateid)) {
				return false;
			}

			// For now these two arrays will suffice.
			$templateRecord = array(
				'templateid'	=> $templateid,
				'ownerid'		=> $templateapi->ownerid
			);
		}

		// Owner always have access
		if (array_key_exists('ownerid', $templateRecord) && $templateRecord['ownerid'] == $currentUser->userid) {
			return true;
		}

		if (array_key_exists('templateid', $templateRecord)) {
			return $currentUser->HasAccess('Templates', $action, $templateRecord['templateid']);
		}

		// Invalid record
		return false;
	}
}
