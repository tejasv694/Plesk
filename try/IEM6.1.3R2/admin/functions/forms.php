<?php
/**
* This file handles processing of forms.
*
* @version     $Id: forms.php,v 1.60 2008/02/25 06:59:27 chris Exp $
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
* This class handles processing of forms. This includes creating, editing, deleting and general management.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Forms extends SendStudio_Functions
{

	/**
	* ValidSorts
	* An array of sorts you can use for forms.
	*
	* @see ManageForms
	*
	* @var array
	*/
	var $ValidSorts = array('name', 'createdate', 'owner');

	/**
	* PopupWindows
	* An array list of windows that pop up. This is used with the header function to work out which header to print.
	*
	* @see PrintHeader
	*
	* @var array
	*/
	var $PopupWindows = array('view', 'preview');

	/**
	* DontShowHeader
	* An array list of windows that don't show the header at all.
	*
	* @see PrintHeader
	*
	* @var array
	*/
	var $DontShowHeader = array('showform', 'preview');

	/**
	* Constructor
	* Loads the language file only.
	*
	* @return Void Doesn't return anything.
	*/
	function Forms()
	{
		$this->LoadLanguageFile('Forms');
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
	* @see ManageForms
	* @see EditForm
	* @see CreateForm
	*/
	function Process()
	{
		$GLOBALS['Message'] = '';

		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;
		$user = GetUser();

		$secondary_actions = array('preview', 'gethtml', 'view', 'finish', 'step2', 'step3', 'step4', 'step5', 'manage', 'processpaging');
		if (in_array($action, $secondary_actions)) {
			$access = $user->HasAccess('Forms');
		} else {
			$access = $user->HasAccess('Forms', $action);
		}

		$popup = (in_array($action, $this->PopupWindows)) ? true : false;
		if (!in_array($action, $this->DontShowHeader)) {
			$this->PrintHeader($popup);
		}

		/**
		 * Check user permission to see whether or not they have access to the autoresponder
		 */
			$tempAPI = null;
			$tempCheckActions = array('view', 'copy', 'delete', 'edit', 'gethtml');
			$tempID = null;

			if (isset($_GET['id'])) {
				$tempID = $_GET['id'];
			} elseif (isset($_POST['forms'])) {
				$tempID = $_POST['forms'];
			}

			if (!is_null($tempID)) {
				$_GET['id'] = $tempID;
				$_POST['forms'] = $tempID;

				if (!$user->Admin() && in_array($action, $tempCheckActions)) {
					if (!is_array($tempID)) {
						$tempID = array($tempID);
					}

					$tempAPI = $this->GetApi();

					foreach ($tempID as $tempEachID) {
						$tempEachID = intval($tempEachID);
						if ($tempEachID == 0) {
							continue;
						}

						if (!$tempAPI->Load($tempEachID)) {
							continue;
						}

						if ($tempAPI->ownerid != $user->userid) {
							$this->DenyAccess();
							return;
						}
					}
				}
			}

			unset($tempID);
			unset($tempCheckActions);
			unset($tempAPI);
		/**
		 * -----
		 */

		if (!$popup && !$access) {
			$this->DenyAccess();
			return;
		}

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = '';
		}

		switch ($action) {
			case 'preview':
				$formapi = $this->GetApi();

				$design = (isset($_POST['FormDesign'])) ? $_POST['FormDesign'] : false;
				$formtype = (isset($_POST['FormType'])) ? $_POST['FormType'] : false;

				$chooseformat = (isset($_POST['SubscriberChooseFormat'])) ? $_POST['SubscriberChooseFormat'] : false;

				$changeformat = false;
				if ($formtype == 'm') {
					if (isset($_POST['SubscriberChangeFormat'])) {
						$changeformat = true;
					}
				}

				$lists = array();
				if (isset($_POST['IncludeLists'])) {
					$lists = $_POST['IncludeLists'];
				}
				if (!is_array($lists)) {
					$lists = array($lists);
				}
				$formapi->Set('lists', $lists);

				$field_order = array();
				if (isset($_POST['hidden_fieldorder'])) {
					$order = explode(';', $_POST['hidden_fieldorder']);
					foreach ($order as $order_pos => $order_field) {
						if (!$order_field) {
							continue;
						}
						$field_order[] = $order_field;
					}
				}

				$usecaptcha = false;
				if (isset($_POST['UseCaptcha']) && in_array($formtype, array('s', 'u', 'm'))) {
					$usecaptcha = true;
				}

				$formapi->Set('customfields', $field_order);

				$formapi->Set('design', $design);
				$formapi->Set('formtype', $formtype);
				$formapi->Set('chooseformat', $chooseformat);
				$formapi->Set('changeformat', $changeformat);
				$formapi->Set('usecaptcha', $usecaptcha);

				$html = $formapi->GetHTML(true);
				echo $html;
				exit();
			break;

			case 'gethtml':
				$this->GetFormHTML();
			break;

			case 'view':
				$this->PrintHeader(true);
				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : false;

				$formapi = $this->GetApi();
				$loaded = $formapi->Load($id);

				if (!$id || !$loaded) {
					$GLOBALS['Error'] = GetLang('NoSuchForm');
					$html = $this->ParseTemplate('ErrorMsg', true, false);
				} else {
					// Log this to "User Activity Log"
					$logURL = SENDSTUDIO_APPLICATION_URL . '/admin/index.php?Page=' . __CLASS__ . '&Action=Edit&id=' . $_GET['id'];
					IEM::logUserActivity($logURL, 'images/forms_view.gif', $formapi->name);

					$formtype = $formapi->Get('formtype');
					// if it's a 'm'odify-details form or 'f'riend form,
					// get the user modified html instead of the built in html.
					if (in_array($formtype, array('m', 'f'))) {
						$html = $formapi->Get('formhtml');

					} else {
						$html = $formapi->GetHTML(true);
					
					}
				}
				header('Content-type: text/html; charset="' . SENDSTUDIO_CHARSET . '"');
				print '<html><head><meta http-equiv="Content-Type" content="text/html; charset='.SENDSTUDIO_CHARSET.'"></head><body>';
				echo $html;
				print '</body></html>';
				exit();
			break;

			case 'copy':
				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
				$api = $this->GetApi();
				$result = $api->Copy($id);
				if (!$result) {
					$GLOBALS['Error'] = GetLang('FormCopyFail');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				} else {
					$GLOBALS['Message'] = $this->PrintSuccess('FormCopySuccess');
				}
				$this->ManageForms();
			break;

			case 'delete':
				$formlist = (isset($_POST['forms'])) ? $_POST['forms'] : array();

				if (isset($_GET['id'])) {
					$formlist = $_GET['id'];
				}

				if (!is_array($formlist)) {
					$formlist = array($formlist);
				}

				$formlist = array_map('intval', $formlist);

				$this->RemoveForms($formlist);
			break;

			case 'finish':
				$session_form = IEM::sessionGet('Form');

				if (!$session_form) {
					$this->ManageForms();
					break;
				}

				$errorpage = array();
				$errorpage['html'] = $_POST['errorhtml_html'];
				if ($_POST['userrorhtmlurl'] == '1') {
					$errorpage['url'] = $_POST['errorpageurl'];
				} else {
					$errorpage['url'] = 'http://';
				}

				foreach (array('ThanksPage', 'ErrorPage', 'ConfirmPage') as $p => $pagename) {
					if (!isset($session_form['Pages'][$pagename])) {
						$session_form['Pages'][$pagename] = array();
					}
				}

				$formapi = $this->GetApi();

				if (isset($session_form['FormID']) && $session_form['FormID'] > 0) {
					$formapi->Load($session_form['FormID']);

					/**
					* If the email text for the confirm page is empty, most likely we've changed the form from requiring a confirmation to not requiring one.
					* In that case, we'll get what the page was before so we can at least fill it in if the form is changed back.
					*/
					if (empty($session_form['Pages']['ConfirmPage']['emailtext'])) {
						$confirm_page = $formapi->GetPage('ConfirmPage');

						foreach (array('html', 'url', 'sendfromname', 'sendfromemail', 'replytoemail', 'bounceemail', 'emailsubject', 'emailhtml', 'emailtext') as $k => $area) {
							$session_form['Pages']['ConfirmPage'][$area] = $confirm_page[$area];
						}
					}

					/**
					* We then do the same for the thanks page.
					*/
					if (empty($session_form['Pages']['ThanksPage']['emailtext'])) {
						$thanks_page = $formapi->GetPage('ThanksPage');

						foreach (array('sendfromname', 'sendfromemail', 'replytoemail', 'bounceemail', 'emailsubject', 'emailhtml', 'emailtext') as $k => $area) {
							$session_form['Pages']['ThanksPage'][$area] = $thanks_page[$area];
						}
					}
				}

				$formhtml = '';
				if (isset($_POST['formhtml'])) {
					$formhtml = $_POST['formhtml'];
				}

				$session_form['Pages']['ErrorPage'] = $errorpage;

				$formapi->Set('formtype', $session_form['FormType']);
				$formapi->Set('pages', $session_form['Pages']);
				$formapi->Set('lists', $session_form['IncludeLists']);
				$formapi->Set('customfields', $session_form['CustomFields']);
				$formapi->Set('name', $session_form['FormName']);
				$formapi->Set('design', $session_form['FormDesign']);
				$formapi->Set('chooseformat', $session_form['SubscriberChooseFormat']);
				$formapi->Set('changeformat', $session_form['SubscriberChangeFormat']);

				$formapi->Set('requireconfirm', $session_form['RequireConfirmation']);
				$formapi->Set('sendthanks', $session_form['SendThanks']);

				$formapi->Set('fieldorder', $session_form['CustomFieldsOrder']);

				$formapi->Set('contactform', $session_form['ContactForm']);

				$formapi->Set('usecaptcha', $session_form['UseCaptcha']);

				$formapi->Set('formhtml', $formhtml);

				if (isset($session_form['FormID']) && $session_form['FormID'] > 0) {
					$result = $formapi->Save();

					if (!$result) {
						$GLOBALS['Error'] = GetLang('UnableToUpdateForm');
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					} else {
						$GLOBALS['Message'] = $this->PrintSuccess('FormUpdated');
					}
				} else {
					$formapi->ownerid = $user->userid;
					$result = $formapi->Create();

					if (!$result) {
						$GLOBALS['Error'] = GetLang('UnableToCreateForm');
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					} else {
						$GLOBALS['Message'] = $this->PrintSuccess('FormCreated');
					}
				}

				if (in_array($formapi->formtype, array('s', 'u'))) {
					$this->GetFormHTML($formapi);
				} else {
					$this->ManageForms();
				}
			break;

			case 'step5':
				$session_form = IEM::sessionGet('Form');
				$sendfriendsettings = array();
				$sendfriendsettings['emailhtml'] = $_POST['myDevEditControl_html'];
				$sendfriendsettings['emailtext'] = $_POST['TextContent'];

				$thankssettings = array();
				$thankssettings['html'] = $_POST['thankspage_html_html'];
				$thankssettings['url'] = $_POST['thankspageurl'];

				$session_form['Pages']['SendFriendPage'] = $sendfriendsettings;
				$session_form['Pages']['ThanksPage'] = $thankssettings;
				IEM::sessionSet('Form', $session_form);

				$this->ShowFinalStep();
			break;

			case 'step4':
				$session_form = IEM::sessionGet('Form');
				$thankssettings = array();
				if ($session_form['SendThanks']) {
					$thankssettings['sendfromname'] = $_POST['sendfromname'];
					$thankssettings['sendfromemail'] = $_POST['sendfromemail'];
					$thankssettings['replytoemail'] = $_POST['replytoemail'];
					$thankssettings['bounceemail'] = $_POST['bounceemail'];
					$thankssettings['emailsubject'] = $_POST['thankssubject'];
					$thankssettings['emailhtml'] = $_POST['thanksemail_html_html'];
					$thankssettings['emailtext'] = $_POST['TextContent'];
				}

				$thankssettings['html'] = $_POST['thankspage_html_html'];
				if ($_POST['usethankspageurl'] == '1') {
					$thankssettings['url'] = $_POST['thankspageurl'];
				} else {
					$thankssettings['url'] = 'http://';
				}

				$session_form['Pages']['ThanksPage'] = $thankssettings;
				IEM::sessionSet('Form', $session_form);

				$this->ShowFinalStep();
			break;

			case 'step3':
				$session_form = IEM::sessionGet('Form');
				$confirmsettings = array();
				$confirmsettings['html'] = $_POST['confirmhtml_html'];
				if ($_POST['useconfirmpageurl'] == '1') {
					$confirmsettings['url'] = $_POST['confirmpageurl'];
				} else {
					$confirmsettings['url'] = 'http://';
				}
				$confirmsettings['sendfromname'] = $_POST['sendfromname'];
				$confirmsettings['sendfromemail'] = $_POST['sendfromemail'];
				$confirmsettings['replytoemail'] = $_POST['replytoemail'];
				$confirmsettings['bounceemail'] = $_POST['bounceemail'];
				$confirmsettings['emailsubject'] = $_POST['confirmsubject'];
				$confirmsettings['emailhtml'] = $_POST['confirmemail_html_html'];
				$confirmsettings['emailtext'] = $_POST['TextContent'];

				$session_form['Pages']['ConfirmPage'] = $confirmsettings;
				IEM::sessionSet('Form', $session_form);

				if ($session_form['SendThanks']) {
					$this->ShowThanksStep();
				}

				$this->ShowThanksHTML();

			break;

			case 'step2':
				$session_form = array();

				$optional_fields = array();

				if (isset($_POST['FormType'])) {
					$formtype = $_POST['FormType'];

					$session_form['ContactForm'] = false;
					$session_form['UseCaptcha'] = false;

					switch ($formtype) {
						case 'u':
							$checkfields = array('FormName', 'FormDesign', 'FormType', 'IncludeLists');
							$optional_fields = array('RequireConfirmation', 'SendThanks', 'UseCaptcha');
							$session_form['SubscriberChangeFormat'] = false;
							$session_form['SubscriberChooseFormat'] = '';
						break;

						case 'm':
							$session_form['RequireConfirmation'] = false;
							$session_form['SendThanks'] = false;
							$checkfields = array('FormName', 'FormDesign', 'FormType', 'IncludeLists');
							$optional_fields = array('SubscriberChangeFormat', 'UseCaptcha');
							$session_form['SubscriberChooseFormat'] = '';
						break;

						case 'f':
							$session_form['RequireConfirmation'] = false;
							$session_form['SendThanks'] = false;
							$session_form['IncludeLists'] = array();
							$session_form['SubscriberChangeFormat'] = false;
							$session_form['SubscriberChooseFormat'] = '';

							$checkfields = array('FormName', 'FormDesign', 'FormType');
						break;

						default:
							$session_form['SubscriberChangeFormat'] = false;
							$optional_fields = array('ContactForm', 'RequireConfirmation', 'SendThanks', 'UseCaptcha');
							$checkfields = array('FormName', 'FormDesign', 'FormType', 'SubscriberChooseFormat', 'IncludeLists');
					}
				}

				$valid = true; $errors = array();
				foreach ($checkfields as $p => $field) {
					if (!isset($_POST[$field])) {
						$valid = false;
						$errors[] = GetLang('Form'.$field.'IsNotValid');
						break;
					}
					if (!is_array($_POST[$field])) {
						if ($_POST[$field] == '') {
							$valid = false;
							$errors[] = GetLang('Form'.$field.'IsNotValid');
							break;
						} else {
							$value = $_POST[$field];
							$session_form[$field] = $value;
						}
					} else {
						if (empty($_POST[$field])) {
							$valid = false;
							$errors[] = GetLang('Form'.$field.'IsNotValid');
							break;
						} else {
							$session_form[$field] = $_POST[$field];
						}
					}
				}

				foreach ($optional_fields as $p => $field) {
					if (isset($_POST[$field])) {
						$session_form[$field] = $_POST[$field];
					} else {
						$session_form[$field] = false;
					}
				}

				if (isset($_GET['id'])) {
					$session_form['FormID'] = (int)$_GET['id'];
				}

				if (!$valid) {
					if (!isset($session_form['FormID'])) {
						$id = 0;
						$GLOBALS['Error'] = GetLang('UnableToCreateForm') . '<br/>- ' . implode('<br/>- ',$errors);
					} else {
						$id = $session_form['FormID'];
						$GLOBALS['Error'] = GetLang('UnableToUpdateForm') . '<br/>- ' . implode('<br/>- ',$errors);
					}
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->Form_Step1($id);
					break;
				}

				$session_form['CustomFieldsOrder'] = array();
				if (isset($_POST['hidden_fieldorder'])) {
					$order = explode(';', $_POST['hidden_fieldorder']);
					foreach ($order as $order_pos => $order_field) {
						if (!$order_field) {
							continue;
						}
						$session_form['CustomFieldsOrder'][] = $order_field;
					}
				}

				$session_form['CustomFields'] = array();

				$ftypes = array('s','m');
				if (in_array($session_form['FormType'], $ftypes)) {
					foreach ($session_form['CustomFieldsOrder'] as $each) {
						if (is_numeric($each)) {
							array_push($session_form['CustomFields'], $each);
						}
					}
				}

				IEM::sessionSet('Form', $session_form);

				if ($session_form['FormType'] == 'f') {
					$this->ShowFriendStep();
					$this->ShowThanksHTML('Step5');
					break;
				}

				if ($session_form['RequireConfirmation'] == '1') {
					$this->ShowConfirmationStep();
					break;
				}

				if ($session_form['SendThanks'] == '1') {
					$this->ShowThanksStep();
				}

				if (isset($session_form['FormID']) && $session_form['FormID'] > 0) {
					$GLOBALS['CancelButton'] = GetLang('EditFormCancelButton');
					$GLOBALS['Heading'] = GetLang('EditForm');
					$GLOBALS['Intro'] = GetLang('ThanksPageIntro_Edit');
					if ($session_form['FormType'] == 'm' || $session_form['SendThanks'] != 1) {
						$GLOBALS['Intro'] = GetLang('ThanksPageIntro_Edit_NoEmail');
					}
				} else {
					$GLOBALS['CancelButton'] = GetLang('CreateFormCancelButton');
					$GLOBALS['Heading'] = GetLang('CreateForm');
					$GLOBALS['Intro'] = GetLang('ThanksPageIntro');
					if ($session_form['FormType'] == 'm' || $session_form['SendThanks'] != 1) {
						$GLOBALS['Intro'] = GetLang('ThanksPageIntro_NoEmail');
					}
				}

				$this->ShowThanksHTML();
			break;

			case 'edit':
				IEM::sessionRemove('Form');
				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
				$this->Form_Step1($id);
			break;

			case 'create':
				IEM::sessionRemove('Form');
				$this->Form_Step1();
			break;

			default:
				$this->ManageForms();
		}

		if (!in_array($action, $this->DontShowHeader)) {
			$this->PrintFooter($popup);
		}
	}

	/**
	* Form_Step1
	* This lets you choose the type of form you are going to create and give it a name.
	* It also lets you choose whether it will require confirmation, send a thanks email, and you choose which lists to include on the form.
	* As you choose lists, it will also show the custom fields for that list so if you like, you can choose extra options to include.
	* This is done through javascript (showing/hiding div's).
	* It doesn't have a check currently to see if you are choosing the same custom field from multiple lists (eg "name").
	*
	* @return Void Shows the form, doesn't return anything.
	*/
	function Form_Step1($formid=0)
	{
		$formapi = $this->GetApi();
		$user = GetUser();
		$lists = $user->GetLists();

		if (count($lists) == 0) {
			$this->ManageForms();
			return;
		}

		$form = false;
		$GLOBALS['Action'] = 'Step2';

		if ($formid <= 0) {
			$GLOBALS['CancelButton'] = GetLang('CreateFormCancelButton');
			$GLOBALS['Heading'] = GetLang('CreateForm');
			$GLOBALS['Intro'] = GetLang('CreateFormIntro');
			$GLOBALS['FormDetails'] = GetLang('CreateFormHeading');
			$form = IEM::sessionGet('Form');

			$form_fieldorder = array('e', 'cf');

			$GLOBALS['ChangeFormatStyle'] = 'none';

		} else {
			$loaded = $formapi->Load($formid);
			if (!$loaded) {
				$GLOBALS['ErrorMessage'] = GetLang('FormDoesntExist');
				$this->DenyAccess();
				return;
			}

			if ($loaded) {
				$GLOBALS['Action'] .= '&id=' . $formid;

				$form = array('Name' => $formapi->Get('name'), 'FormType' => $formapi->Get('formtype'), 'RequireConfirmation' => $formapi->Get('requireconfirm'), 'SubscriberChooseFormat' => $formapi->Get('chooseformat'), 'SubscriberChangeFormat' => $formapi->Get('changeformat'), 'SendThanks' => $formapi->Get('sendthanks'), 'ContactForm' => $formapi->Get('contactform'), 'UseCaptcha' => $formapi->Get('usecaptcha'), 'IncludeLists' => $formapi->Get('lists'), 'Design' => $formapi->Get('design'), 'CustomFields' => $formapi->Get('customfields'));

				$GLOBALS['CancelButton'] = GetLang('EditFormCancelButton');
				$GLOBALS['Heading'] = GetLang('EditForm');
				$GLOBALS['Intro'] = GetLang('EditFormIntro');
				$GLOBALS['FormDetails'] = GetLang('EditFormHeading');

				$form_fieldorder = $formapi->Get('fieldorder');

				if (sizeof($form['IncludeLists']) <= 1) {
					$k = array_search('cl', $form_fieldorder);
					if ($k !== false) {
						unset($form_fieldorder[$k]);
					}
				}

				if (($form['FormType'] == 'm' && !$form['SubscriberChangeFormat']) || ($form['FormType'] != 'm' && $form['SubscriberChooseFormat'] != 'c')) {
					$k = array_search('cf', $form_fieldorder);
					if ($k !== false) {
						unset($form_fieldorder[$k]);
					}
				}

				$GLOBALS['ChangeFormatStyle'] = 'none';

				$GLOBALS['ChooseListStyle'] = '';

				if ($form['FormType'] == 'u') {
					$GLOBALS['ChooseFormatStyle'] = 'none';
					$GLOBALS['ContactFormStyle'] = 'none';
				}

				if ($form['FormType'] == 'm') {
					$GLOBALS['RequireConfirmStyle'] = 'none';
					$GLOBALS['SendThanksStyle'] = 'none';
					$GLOBALS['ContactFormStyle'] = 'none';
					$GLOBALS['ChooseFormatStyle'] = 'none';
					$GLOBALS['CaptchaFormStyle'] = '';
					$GLOBALS['ChangeFormatStyle'] = '';
				}

				if ($form['FormType'] == 'f') {
					$GLOBALS['ChooseFormatStyle'] = 'none';
					$GLOBALS['RequireConfirmStyle'] = 'none';
					$GLOBALS['SendThanksStyle'] = 'none';
					$GLOBALS['ChooseListStyle'] = 'none';
					$GLOBALS['ChooseListOptionsStyle'] = 'none';
					$GLOBALS['ChooseListOptionsMessageStyle'] = 'none';
					$GLOBALS['ContactFormStyle'] = 'none';
					$GLOBALS['CaptchaFormStyle'] = 'none';
				}

				$GLOBALS['LoadFormType'] = $formapi->Get('formtype');
				$GLOBALS['LoadDesign'] = $formapi->Get('design');
				$GLOBALS['LoadChangeFormat'] = $formapi->Get('changeformat');
			}
		}

		// Log this to "User Activity Log"
		// IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/forms_view.gif', $formapi->name);

		if (!$form) {
			$form = array('Name' => '', 'FormType' => 's', 'RequireConfirmation' => true, 'SubscriberChooseFormat' => 'c', 'SubscriberChangeFormat' => false, 'SendThanks' => true, 'ContactForm' => false, 'UseCaptcha' => true, 'IncludeLists' => array(), 'Design' => 'default', 'CustomFields' => array());
		}

		$GLOBALS['FormName'] = $form['Name'];

		$GLOBALS['FormLoaded'] = (int)$formid;

		$GLOBALS['FormTypeList'] = '';
		$allformtypes = $formapi->GetFormTypes();
		foreach ($allformtypes as $id => $name) {
			if ($formid > 0 && $form['FormType'] != $id) {
				continue;
			}

			$selected = ($id == $form['FormType']) ? ' SELECTED' : '';
			$GLOBALS['FormTypeList'] .= '<option value="' . $id . '"' . $selected . '>' . GetLang('FormType_' . $name) . '</option>';
		}

		if ($user->group->forcedoubleoptin == 1) {
			$GLOBALS['ForceDoubleOptIn'] = 1;
		}
		$GLOBALS['RequireConfirmation'] = '';
		if ($form['RequireConfirmation']) {
			$GLOBALS['RequireConfirmation'] = 'CHECKED';
		}

		$GLOBALS['SendThanks'] = '';
		if ($form['SendThanks'] == '1') {
			$GLOBALS['SendThanks'] = 'CHECKED';
		}

		$GLOBALS['ContactForm'] = '';
		if ($form['ContactForm'] == '1') {
			$GLOBALS['ContactForm'] = 'CHECKED';
		}

		$GLOBALS['UseCaptcha'] = '';
		if ($form['UseCaptcha'] == '1') {
			$GLOBALS['UseCaptcha'] = 'CHECKED';
		}

		$GLOBALS['SubscriberChangeFormat'] = '';
		if ($form['SubscriberChangeFormat']) {
			$GLOBALS['SubscriberChangeFormat'] = 'CHECKED';
		}

		$GLOBALS['Message'] = GetLang('FormCustomFieldSelection');
		$GLOBALS['FormCustomFieldMessage'] = $this->ParseTemplate('InfoMsg', true);
		unset($GLOBALS['Message']);

		$GLOBALS['SubscriberChooseFormat'] = '';
		foreach (array('c' => 'ChooseFormat', 'fh' => 'ForceHTML', 'ft' => 'ForceText') as $opt => $val) {
			$selected = ($opt == $form['SubscriberChooseFormat']) ? ' SELECTED' : '';
			$GLOBALS['SubscriberChooseFormat'] .= '<option value="' . $opt . '"' . $selected . '>' . GetLang($val) . '</option>';
		}

		$designs = $formapi->GetFormDesigns();
		$GLOBALS['DesignList'] = '';
		foreach ($designs as $id => $name) {
			$GLOBALS['Selected'] = ($id == $form['Design']) ? ' SELECTED' : '';
			$GLOBALS['ID'] = $id;
			$GLOBALS['Name'] = $name;
			$GLOBALS['DesignList'] .= $this->ParseTemplate('FormDesign_Select_Option', true, false);
		}

		$GLOBALS['MailingListOptions'] = '';
		$lists = $user->GetLists();
		$list_api = $this->GetApi('Lists');

		$all_customfields = array();

		$mail_boxes = '';

		$LoadedLists = $LoadedFields = '';

		foreach ($lists as $listid => $details) {
			$tempListSelected = false;
			$GLOBALS['Checked'] = '';
			$GLOBALS['customfields_display'] = 'none';
			if (in_array($listid, $form['IncludeLists'])) {
				$tempListSelected = true;
				$GLOBALS['Checked'] = ' CHECKED';
				$GLOBALS['customfields_display'] = '';
				$LoadedLists .= "'" . $listid . "',";
			}

			$GLOBALS['ListID'] = $listid;
			$GLOBALS['ListName'] = htmlspecialchars($details['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);

			$list_customfields = '';

			if ($form['FormType'] != 'u') {
				$customfields = $list_api->GetCustomFields($listid);

				foreach ($customfields as $pos => $cf_details) {
					$checked = '';
					if ($tempListSelected && in_array($cf_details['fieldid'], $form['CustomFields'])) {
						$checked = ' CHECKED';

						$LoadedFields .= "'" . $cf_details['fieldid'] . "',";
					}

					$all_customfields[$cf_details['fieldid']] = $cf_details['name'];

					$GLOBALS['CustomFieldChecked'] = $checked;

					$GLOBALS['FieldID'] = $cf_details['fieldid'];

					$GLOBALS['FieldName'] = htmlspecialchars($cf_details['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);

					$list_customfields .= $this->ParseTemplate('Form_Form_Step1_Lists_CustomFields', true, false);
				}
			}

			$GLOBALS['ListCustomFields'] = $list_customfields;

			if (empty($customfields)) {
				$list_customfields_heading = $this->ParseTemplate('Form_Form_Step1_Lists_Customfields_Empty', true, false);
			} else {
				$list_customfields_heading = $this->ParseTemplate('Form_Form_Step1_Lists_Customfields_Heading', true, false);
			}

			$GLOBALS['ListCustomFieldsHeading'] = $list_customfields_heading;

			$mail_boxes .= $this->ParseTemplate('Form_Form_Step1_Lists', true, false);
		}

		if ($LoadedLists != '') {
			$LoadedLists = substr($LoadedLists, 0, -1);
		}

		if ($LoadedFields != '') {
			$LoadedFields = substr($LoadedFields, 0, -1);
		}

		$GLOBALS['LoadedLists'] = $LoadedLists;
		$GLOBALS['LoadedFields'] = $LoadedFields;

		$GLOBALS['FieldOrderList'] = '';
		$LoadedOrder = '';
		foreach ($form_fieldorder as $p => $fieldid) {
			$LoadedOrder .= "'" . $fieldid . "',";
			$option = '<li style="cursor: move;"><input type="hidden" name="fieldorder[]" class="FieldOrderHiddenValues" value="' . $fieldid . '" />';
			switch ($fieldid) {
				case 'e':
					$option .= GetLang('Email_Required_For_Form');
				break;
				case 'cf':
					$option .= GetLang('SubscriberFormat_For_Form');
				break;
				case 'cl':
					$option .= GetLang('ChooseList_For_Form');
				break;

				default:
					$option .= $all_customfields[$fieldid];
			}
			$GLOBALS['FieldOrderList'] .= $option;
		}

		if ($LoadedOrder != '') {
			$LoadedOrder = substr($LoadedOrder, 0, -1);
		}
		$GLOBALS['LoadedOrder'] = $LoadedOrder;

		$GLOBALS['MailingListBoxes'] = $mail_boxes;
		$this->ParseTemplate('Form_Form_Step1');
	}

	/**
	* ShowConfirmationStep
	* This step is shown if the form is going to send a confirmation request to the subscriber for taking an action (subscribing, unsubscribing).
	*
	* @see GetUser
	* @see GetAPI
	* @see Forms_API::Load
	* @see Lists_API::Load
	* @see GetHTMLEditor
	*
	* @return Void Doesn't return anything. Prints out the editor screens and that's it.
	*/
	function ShowConfirmationStep()
	{
		$user = GetUser();

		$formsession = IEM::sessionGet('Form');

		$formid = 0; $loaded = false;

		if (isset($formsession['FormID'])) {
			$formid = (int)$formsession['FormID'];
		}

		$GLOBALS['Action'] = 'Step3';

		$found_content = true;

		if ($formid > 0) {
			$formapi = $this->GetApi();
			$loaded = $formapi->Load($formid);
			if ($loaded) {
				$GLOBALS['CancelButton'] = GetLang('EditFormCancelButton');
				$GLOBALS['Heading'] = GetLang('EditForm');
				$GLOBALS['Intro'] = GetLang('EditFormIntro');

				$GLOBALS['SendFromName'] = $formapi->pages['ConfirmPage']['sendfromname'];
				$GLOBALS['SendFromEmail'] = $formapi->pages['ConfirmPage']['sendfromemail'];
				$GLOBALS['ReplyToEmail'] = $formapi->pages['ConfirmPage']['replytoemail'];
				$GLOBALS['BounceEmail'] = $formapi->pages['ConfirmPage']['bounceemail'];
				$GLOBALS['ConfirmSubject'] = $formapi->pages['ConfirmPage']['emailsubject'];

				$GLOBALS['TextContent'] = $formapi->pages['ConfirmPage']['emailtext'];
				$htmlvalue = $formapi->pages['ConfirmPage']['emailhtml'];

				$GLOBALS['ConfirmPageHTML'] = $formapi->pages['ConfirmPage']['html'];
				$GLOBALS['ConfirmPageURL'] = $formapi->pages['ConfirmPage']['url'];

				if ($formapi->pages['ConfirmPage']['emailtext'] == '' || $formapi->pages['ConfirmPage']['emailhtml'] == '') {
					$found_content = false;
				}
			}
		}

		if ($formid <= 0 || !$loaded || !$found_content) {
			if ($formid <= 0 || !$loaded) {
				$GLOBALS['CancelButton'] = GetLang('CreateFormCancelButton');
				$GLOBALS['Heading'] = GetLang('CreateForm');
				$GLOBALS['Intro'] = GetLang('ConfirmPageIntro');
			}

			// if there's more than one list, we'll use the users information.
			if (sizeof($formsession['IncludeLists']) > 1) {
				$GLOBALS['SendFromName'] = $user->Get('fullname');
				$GLOBALS['SendFromEmail'] = $GLOBALS['ReplyToEmail'] = $GLOBALS['BounceEmail'] = $user->Get('emailaddress');
			} else {
				// if there's only one list, load up those details.
				$listapi = $this->GetApi('Lists');

				$lists = current($formsession['IncludeLists']);
				$listapi->Load($lists);
				$GLOBALS['SendFromName'] = $listapi->Get('ownername');
				$GLOBALS['SendFromEmail'] = $listapi->Get('owneremail');
				$GLOBALS['ReplyToEmail'] = $listapi->Get('replytoemail');
				$GLOBALS['BounceEmail'] = $listapi->Get('bounceemail');
			}

			switch ($formsession['FormType']) {
				case 'm':
					$GLOBALS['ConfirmSubject'] = GetLang('FormConfirmPage_Modify_Subject');
					$GLOBALS['ConfirmPageHTML'] = GetLang('FormConfirmPageHTML_Modify');
					$htmlvalue = GetLang('FormConfirmPage_Modify_Email_HTML');
					$GLOBALS['TextContent'] = GetLang('FormConfirmPage_Modify_Email_Text');
				break;
				case 's':
					$GLOBALS['ConfirmSubject'] = GetLang('FormConfirmPage_Subscribe_Subject');
					$GLOBALS['ConfirmPageHTML'] = GetLang('FormConfirmPage_Subscribe_HTML');
					$htmlvalue = GetLang('FormConfirmPage_Subscribe_Email_HTML');
					$GLOBALS['TextContent'] = GetLang('FormConfirmPage_Subscribe_Email_Text');
				break;
				case 'u':
					$GLOBALS['ConfirmSubject'] = GetLang('FormConfirmPage_Unubscribe_Subject');
					$GLOBALS['ConfirmPageHTML'] = GetLang('FormConfirmPage_Unsubscribe_HTML');
					$htmlvalue = GetLang('FormConfirmPage_Unsubscribe_Email_HTML');
					$GLOBALS['TextContent'] = GetLang('FormConfirmPage_Unsubscribe_Email_Text');
				break;
			}
			$GLOBALS['ConfirmPageURL'] = 'http://';
		}

		switch ($formsession['FormType']) {
			case 'm':
			break;
			case 's':
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ConfirmPageHTML_Subscribe');
			break;
			case 'u':
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ConfirmPageHTML_Unsubscribe');
			break;
		}

		$GLOBALS['ConfirmUrlStyle'] = 'none';
		$GLOBALS['ConfirmHTMLStyle'] = "''";
		$GLOBALS['ConfirmUrlField'] = '';
		$GLOBALS['ConfirmHTMLField'] = ' CHECKED';

		if ($GLOBALS['ConfirmPageURL'] != 'http://' && $GLOBALS['ConfirmPageURL'] != '') {
			$GLOBALS['ConfirmUrlStyle'] = "''";
			$GLOBALS['ConfirmHTMLStyle'] = 'none';
			$GLOBALS['ConfirmUrlField'] = ' CHECKED';
			$GLOBALS['ConfirmHTMLField'] = '';
		}

		$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($GLOBALS['ConfirmPageHTML'], false, 'confirmhtml', 'exact', 260, 400);
		$GLOBALS['HTMLEditorName'] = 'confirmhtml';
		$GLOBALS['ConfirmHTML'] = $this->ParseTemplate('Form_Editor_HTML', true, false);

		switch ($formsession['FormType']) {
			case 'm':
			break;
			case 's':
				$GLOBALS['TextHelpTip'] = $this->_GenerateHelpTip('HLP_ConfirmTextVersion_Subscribe');
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ConfirmHTMLVersion_Subscribe');
			break;
			case 'u':
				$GLOBALS['TextHelpTip'] = $this->_GenerateHelpTip('HLP_ConfirmTextVersion_Unsubscribe');
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ConfirmHTMLVersion_Unsubscribe');
			break;
		}

		$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($htmlvalue, false, 'confirmemail_html', 'exact', 260, 400);
		$GLOBALS['HTMLEditorName'] = 'confirmemail_html';
		$GLOBALS['EditorHTML'] = $this->ParseTemplate('Form_Editor_HTML', true, false);
		$GLOBALS['EditorText'] = $this->ParseTemplate('Form_Editor_Text', true, false);

		$GLOBALS['ShowBounceInfo'] = 'none';
		if ($user->HasAccess('Lists', 'BounceSettings')) {
			$GLOBALS['ShowBounceInfo'] = '';
		}

		$GLOBALS['FormConfirmPage'] = $this->ParseTemplate('Form_Form_ConfirmPage', true);

		$this->ParseTemplate('Form_Form_Step2');
	}

	/**
	* ShowThanksStep
	* This step is shown if the form is going to send an email to the subscriber for taking an action (subscribing, unsubscribing, modifying their details and so on).
	*
	* @see GetUser
	* @see GetAPI
	* @see Forms_API::Load
	* @see Lists_API::Load
	* @see GetHTMLEditor
	*
	* @return Void Doesn't return anything. Prints out the editor screens and that's it.
	*/
	function ShowThanksStep()
	{
		$user = GetUser();

		$formsession = IEM::sessionGet('Form');

		$GLOBALS['Action'] = 'Step4';

		$formid = 0; $loaded = false;

		if (isset($formsession['FormID'])) {
			$formid = (int)$formsession['FormID'];
		}

		$found_content = true;

		if ($formid > 0) {
			$formapi = $this->GetApi();
			$loaded = $formapi->Load($formid);
			if ($loaded) {
				$GLOBALS['CancelButton'] = GetLang('EditFormCancelButton');
				$GLOBALS['Heading'] = GetLang('EditForm');
				$GLOBALS['Intro'] = GetLang('EditFormIntro');

				$GLOBALS['SendFromName'] = $formapi->pages['ThanksPage']['sendfromname'];
				$GLOBALS['SendFromEmail'] = $formapi->pages['ThanksPage']['sendfromemail'];
				$GLOBALS['ReplyToEmail'] = $formapi->pages['ThanksPage']['replytoemail'];
				$GLOBALS['BounceEmail'] = $formapi->pages['ThanksPage']['bounceemail'];
				$GLOBALS['ThanksSubject'] = $formapi->pages['ThanksPage']['emailsubject'];

				$GLOBALS['TextContent'] = $formapi->pages['ThanksPage']['emailtext'];
				$htmlvalue = $formapi->pages['ThanksPage']['emailhtml'];

				$GLOBALS['ThanksPageHTML'] = $formapi->pages['ThanksPage']['html'];
				$GLOBALS['ThanksPageURL'] = $formapi->pages['ThanksPage']['url'];

				if ($formapi->pages['ThanksPage']['emailtext'] == '' || $formapi->pages['ThanksPage']['emailhtml'] == '') {
					$found_content = false;
				}
			}
		}

		if ($formid <= 0 || !$loaded || !$found_content) {
			if ($formid <= 0 || !$loaded) {
				$GLOBALS['CancelButton'] = GetLang('CreateFormCancelButton');
				$GLOBALS['Heading'] = GetLang('CreateForm');
			}

			if (sizeof($formsession['IncludeLists']) > 1) {
				$GLOBALS['SendFromName'] = $user->Get('fullname');
				$GLOBALS['SendFromEmail'] = $GLOBALS['ReplyToEmail'] = $GLOBALS['BounceEmail'] = $user->Get('emailaddress');
			} else {
				$listapi = $this->GetApi('Lists');

				$lists = current($formsession['IncludeLists']);
				$listapi->Load($lists);
				$GLOBALS['SendFromName'] = $listapi->Get('ownername');
				$GLOBALS['SendFromEmail'] = $listapi->Get('owneremail');
				$GLOBALS['ReplyToEmail'] = $listapi->Get('replytoemail');
				$GLOBALS['BounceEmail'] = $listapi->Get('bounceemail');
			}

			$GLOBALS['ThanksPageURL'] = 'http://';

			switch ($formsession['FormType']) {
				case 'f':
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_SendFriend');
				break;

				case 'm':
					$GLOBALS['ThanksSubject'] = GetLang('FormThanksPage_Modify_Subject');
					$htmlvalue = GetLang('FormThanksPage_Modify_Email_HTML');
					$GLOBALS['TextContent'] = GetLang('FormThanksPage_Modify_Email_Text');
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_Modify');
				break;
				case 's':
					if ($formsession['ContactForm']) {
						$GLOBALS['ThanksSubject'] = GetLang('FormThanksPage_Subscribe_Subject_Contact');
						$htmlvalue = GetLang('FormThanksPage_Subscribe_Email_HTML_Contact');
						$GLOBALS['TextContent'] = GetLang('FormThanksPage_Subscribe_Email_Text_Contact');
					} else {
						$GLOBALS['ThanksSubject'] = GetLang('FormThanksPage_Subscribe_Subject');
						$htmlvalue = GetLang('FormThanksPage_Subscribe_Email_HTML');
						$GLOBALS['TextContent'] = GetLang('FormThanksPage_Subscribe_Email_Text');
					}
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_Subscribe');
				break;
				case 'u':
					$GLOBALS['ThanksSubject'] = GetLang('FormThanksPage_Unubscribe_Subject');
					$htmlvalue = GetLang('FormThanksPage_Unsubscribe_Email_HTML');
					$GLOBALS['TextContent'] = GetLang('FormThanksPage_Unsubscribe_Email_Text');
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_Unsubscribe');
				break;
			}
		}

		switch ($formsession['FormType']) {
			case 'f':
				$GLOBALS['TextHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksTextVersion');
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksHTMLVersion');
			break;

			case 'm':
				$GLOBALS['TextHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksTextVersion');
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksHTMLVersion');
			break;

			case 's':
				$GLOBALS['TextHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksTextVersion_Subscribe');
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksHTMLVersion_Subscribe');
			break;

			case 'u':
				$GLOBALS['TextHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksTextVersion_Unsubscribe');
				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksHTMLVersion_Unsubscribe');
			break;
		}

		$GLOBALS['Intro'] = GetLang('ThanksPageIntro');
		if ($formid > 0) {
			$GLOBALS['Intro'] = GetLang('ThanksPageIntro_Edit');
		}

		$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($htmlvalue, false, 'thanksemail_html', 'exact', 260, 400);
		$GLOBALS['HTMLEditorName'] = 'thanksemail_html';
		$GLOBALS['EditorHTML'] = $this->ParseTemplate('Form_Editor_HTML', true, false);
		$GLOBALS['EditorText'] = $this->ParseTemplate('Form_Editor_Text', true, false);

		$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($GLOBALS['ThanksPageHTML'], false, 'thankspage_html', 'exact', 260, 400);
		$GLOBALS['HTMLEditorName'] = 'thankspage_html';
		$GLOBALS['ThanksPage_HTML'] = $this->ParseTemplate('Form_Editor_HTML', true, false);

		$GLOBALS['ShowBounceInfo'] = 'none';
		if ($user->HasAccess('Lists', 'BounceSettings')) {
			$GLOBALS['ShowBounceInfo'] = '';
		}

		// need this back here again for the 'Get Text From HTML' option.
		$GLOBALS['HTMLEditorName'] = 'thanksemail_html';

		$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksPageHTML');

		$GLOBALS['FormThanksPage'] = $this->ParseTemplate('Form_Form_ThanksPage', true);
	}

	/**
	* ShowThanksStep
	* This step is shown if the form is going to send an email to the subscriber for taking an action (subscribing, unsubscribing, modifying their details and so on).
	*
	* @see GetUser
	* @see GetAPI
	* @see Forms_API::Load
	* @see Lists_API::Load
	* @see GetHTMLEditor
	*
	* @return Void Doesn't return anything. Prints out the editor screens and that's it.
	*/
	function ShowThanksHTML($form_action='Step4')
	{
		$user = GetUser();

		$formsession = IEM::sessionGet('Form');

		$GLOBALS['Action'] = $form_action;

		$formid = 0; $loaded = false;

		if (isset($formsession['FormID'])) {
			$formid = (int)$formsession['FormID'];
		}

		if ($formid > 0) {
			$formapi = $this->GetApi();
			$loaded = $formapi->Load($formid);
			if ($loaded) {
				$GLOBALS['ThanksPageHTML'] = $formapi->pages['ThanksPage']['html'];
				$GLOBALS['ThanksPageURL'] = $formapi->pages['ThanksPage']['url'];
			}
		}

		if ($formid <= 0 || !$loaded) {
			$GLOBALS['ThanksPageURL'] = 'http://';

			switch ($formsession['FormType']) {
				case 'f':
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_SendFriend');
				break;
				case 'm':
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_Modify');
				break;
				case 's':
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_Subscribe');
				break;
				case 'u':
					$GLOBALS['ThanksPageHTML'] = GetLang('FormThanksPageHTML_Unsubscribe');
				break;
			}
		}

		$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ThanksPageHTML');

		$GLOBALS['ThanksPageUrlStyle'] = 'none';
		$GLOBALS['ThanksPageHTMLStyle'] = "''";
		$GLOBALS['ThanksPageURLField'] = '';
		$GLOBALS['ThanksPageHTMLField'] = ' CHECKED';

		if ($GLOBALS['ThanksPageURL'] != 'http://' && $GLOBALS['ThanksPageURL'] != '') {
			$GLOBALS['ThanksPageUrlStyle'] = "''";
			$GLOBALS['ThanksPageHTMLStyle'] = 'none';
			$GLOBALS['ThanksPageURLField'] = ' CHECKED';
			$GLOBALS['ThanksPageHTMLField'] = '';
		}

		$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($GLOBALS['ThanksPageHTML'], false, 'thankspage_html', 'exact', 260, 400);
		$GLOBALS['HTMLEditorName'] = 'thankspage_html';
		$GLOBALS['ThanksPage_HTML'] = $this->ParseTemplate('Form_Editor_HTML', true, false);

		$GLOBALS['FormThanksPageHTML'] = $this->ParseTemplate('Form_Form_ThanksPageHTML', true);

		$this->ParseTemplate('Form_Form_Step2');
	}

	/**
	* ShowFinalStep
	* The final step of the form is the thanks/error messages page. We show this separately because it will always be shown, no matter what other steps or options are chosen.
	*
	* @see GetAPI
	* @see Forms_API::Load
	* @see Forms_API::Set
	* @see Forms_API::GetHTML
	*
	* @return Void Doesn't return anything. Prints out the final step based on the form type and whether there is an existing form to load up.
	*/
	function ShowFinalStep()
	{
		$formsession = IEM::sessionGet('Form');

		$GLOBALS['Action'] = 'Finish';

		$formid = 0; $loaded = false;

		if (isset($formsession['FormID'])) {
			$formid = (int)$formsession['FormID'];
		}

		$formapi = $this->GetApi();

		if ($formid > 0) {
			$loaded = $formapi->Load($formid);
			if ($loaded) {
				$GLOBALS['CancelButton'] = GetLang('EditFormCancelButton');
				$GLOBALS['Heading'] = GetLang('EditForm');
				$GLOBALS['Intro'] = GetLang('EditFormIntro');

				$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ErrorPageHTML');

				if ($formsession['FormType'] == 'm') {
					$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ErrorPageHTML_Modify');

					$form_lists = $formapi->Get('lists');
					$form_customfields = $formapi->Get('customfields');
					$form_design = $formapi->Get('design');
					$form_format = $formapi->Get('chooseformat');
					$form_changeformat = $formapi->Get('changeformat');
					$form_usecaptcha = $formapi->Get('usecaptcha');

					// in case the lists were in the $_POST variable in different order or something,
					// we'll sort them before doing a comparison.
					sort($form_lists);
					sort($formsession['IncludeLists']);

					// if the form itself is the same (includes the same options, lists etc)
					if (
						$form_lists == $formsession['IncludeLists']
						&& $form_customfields == $formsession['CustomFieldsOrder']
						&& $form_design == $formsession['FormDesign']
						&& $form_format == $formsession['SubscriberChooseFormat']
						&& $form_changeformat == $formsession['SubscriberChangeFormat']
						&& trim($form_usecaptcha) == trim($formsession['UseCaptcha'])
					) {
						// then just get the old html so we can edit it.
						$GLOBALS['EditFormHTMLContents'] = htmlspecialchars($formapi->Get('formhtml'), ENT_QUOTES, SENDSTUDIO_CHARSET);
					} else {
						// if any of the options have been changed, regenerate the html.
						$formapi->Set('formtype', $formsession['FormType']);
						$formapi->Set('design', $formsession['FormDesign']);
						$formapi->Set('lists', $formsession['IncludeLists']);
						$formapi->Set('customfields', $formsession['CustomFields']);
						// overide the value of this based on the formsession.
						$formapi->Set('fieldorder', $formsession['CustomFieldsOrder']);
						$formapi->Set('changeformat', $formsession['SubscriberChangeFormat']);
						$formapi->Set('usecaptcha', $formsession['UseCaptcha']);
						$GLOBALS['EditFormHTMLContents'] = htmlspecialchars($formapi->GetHTML(), ENT_QUOTES, SENDSTUDIO_CHARSET);

						$GLOBALS['Warning'] = sprintf(GetLang('FormContentsHaveChanged'), $formid);
						$GLOBALS['Message'] = $this->ParseTemplate('WarningMsg', true, false);
					}
					$GLOBALS['EditFormHTML'] = $this->ParseTemplate('Form_Edit_HTML', true, false);
				}

				if ($formsession['FormType'] == 'f') {
					$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ErrorPageHTML_SendFriend');

					$form_design = $formapi->Get('design');
					$form_format = $formapi->Get('chooseformat');
					if (
						$form_design == $formsession['FormDesign'] &&
						$form_format == $formsession['SubscriberChooseFormat']
					) {
						// then just get the old html so we can edit it.
						$GLOBALS['EditFormHTMLContents'] = htmlspecialchars($formapi->Get('formhtml'), ENT_QUOTES, SENDSTUDIO_CHARSET);
					} else {
						// if any of the options have been changed, regenerate the html.
						$formapi->Set('formtype', $formsession['FormType']);
						$formapi->Set('design', $formsession['FormDesign']);
						$GLOBALS['EditFormHTMLContents'] = htmlspecialchars($formapi->GetHTML(), ENT_QUOTES, SENDSTUDIO_CHARSET);

						$GLOBALS['Warning'] = sprintf(GetLang('FormContentsHaveChanged'), $formid);
						$GLOBALS['Message'] = $this->ParseTemplate('WarningMsg', true, false);
					}
					$GLOBALS['EditFormHTML'] = $this->ParseTemplate('Form_Edit_HTML', true, false);
				}

				$GLOBALS['ErrorPageHTML'] = $formapi->pages['ErrorPage']['html'];
				$GLOBALS['ErrorPageURL'] = $formapi->pages['ErrorPage']['url'];
			}
		}

		if ($formid <= 0 || !$loaded) {
			$GLOBALS['CancelButton'] = GetLang('CreateFormCancelButton');
			$GLOBALS['Heading'] = GetLang('CreateForm');
			switch ($formsession['FormType']) {

				case 'f':
					$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ErrorPageHTML_SendFriend');

					// because this is a send-to-friend form, we have to set all the form api variables before we can get the html back.
					$formapi->Set('formtype', 'f');
					$formapi->Set('design', $formsession['FormDesign']);
					$GLOBALS['EditFormHTMLContents'] = htmlspecialchars($formapi->GetHTML(), ENT_QUOTES, SENDSTUDIO_CHARSET);
					$GLOBALS['EditFormHTML'] = $this->ParseTemplate('Form_Edit_HTML', true, false);
					$GLOBALS['ErrorPageHTML'] = GetLang('FormErrorPageHTML_SendFriend');
				break;

				case 'm':
					// because this is a modify details form, we have to set all the form api variables before we can get the html back.

					$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ErrorPageHTML_Modify');

					$formapi->Set('formtype', 'm');
					$formapi->Set('design', $formsession['FormDesign']);
					$formapi->Set('lists', $formsession['IncludeLists']);
					$formapi->Set('customfields', $formsession['CustomFields']);
					$formapi->Set('fieldorder', $formsession['CustomFieldsOrder']);
					$formapi->Set('usecaptcha', $formsession['UseCaptcha']);
					$GLOBALS['EditFormHTMLContents'] = htmlspecialchars($formapi->GetHTML(), ENT_QUOTES, SENDSTUDIO_CHARSET);
					$GLOBALS['EditFormHTML'] = $this->ParseTemplate('Form_Edit_HTML', true, false);
					$GLOBALS['ErrorPageHTML'] = GetLang('FormErrorPageHTML_Modify');
				break;

				case 's':
					$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ErrorPageHTML_Subscribe');

					$GLOBALS['ErrorPageHTML'] = GetLang('FormErrorPageHTML_Subscribe');
				break;

				case 'u':
					$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_ErrorPageHTML_Unsubscribe');

					$GLOBALS['ErrorPageHTML'] = GetLang('FormErrorPageHTML_Unsubscribe');
				break;
			}

			$GLOBALS['ErrorPageURL'] = 'http://';
		}

		$GLOBALS['ErrorPageUrlStyle'] = 'none';
		$GLOBALS['ErrorPageHTMLStyle'] = "''";
		$GLOBALS['ErrorPageUrlField'] = '';
		$GLOBALS['ErrorPageHTMLField'] = ' CHECKED';

		if ($GLOBALS['ErrorPageURL'] != 'http://' && $GLOBALS['ErrorPageURL'] != '') {
			$GLOBALS['ErrorPageUrlStyle'] = "''";
			$GLOBALS['ErrorPageHTMLStyle'] = 'none';
			$GLOBALS['ErrorPageUrlField'] = ' CHECKED';
			$GLOBALS['ErrorPageHTMLField'] = '';
		}

		$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($GLOBALS['ErrorPageHTML'], false, 'errorhtml', 'exact', 260, 400);
		$GLOBALS['HTMLEditorName'] = 'errorhtml';
		$GLOBALS['ErrorHTML'] = $this->ParseTemplate('Form_Editor_HTML', true, false);

		$GLOBALS['Intro'] = GetLang('FinalPageIntro');

		$this->ParseTemplate('Form_Form_Step3');
	}

	/**
	* ShowFriendStep
	* If this is a send to a friend form, then this step is shown.
	*
	* @see GetAPI
	* @see Forms_API::Load
	* @see GetHTMLEditor
	*
	* @return Void Doesn't return anything. Prints out the form for editing the send to friend message.
	*/
	function ShowFriendStep()
	{
		$user = GetUser();

		$formsession = IEM::sessionGet('Form');

		$formid = 0; $loaded = false;

		if (isset($formsession['FormID'])) {
			$formid = (int)$formsession['FormID'];
		}

		$GLOBALS['Action'] = 'Step5';

		if ($formid > 0) {
			$formapi = $this->GetApi();
			$loaded = $formapi->Load($formid);
			if ($loaded) {
				$GLOBALS['CancelButton'] = GetLang('EditFormCancelButton');
				$GLOBALS['Heading'] = GetLang('EditForm');
				$GLOBALS['Intro'] = GetLang('EditFormIntro');

				$GLOBALS['TextContent'] = $formapi->pages['SendFriendPage']['emailtext'];
				$htmlvalue = $formapi->pages['SendFriendPage']['emailhtml'];
			}
		}
		if ($formid <= 0 || !$loaded) {
			$GLOBALS['CancelButton'] = GetLang('CreateFormCancelButton');
			$GLOBALS['Heading'] = GetLang('CreateForm');
			$GLOBALS['Intro'] = GetLang('SendFriendPageIntro');

			$htmlvalue = GetLang('FormSendFriendPage_Email_HTML');
			$GLOBALS['TextContent'] = GetLang('FormSendFriendPage_Email_Text');
		}

		$GLOBALS['HTMLEditorName'] = 'myDevEditControl';

		$GLOBALS['TextHelpTip'] = $this->_GenerateHelpTip('HLP_SendFriendTextVersion');
		$GLOBALS['HTMLHelpTip'] = $this->_GenerateHelpTip('HLP_SendFriendHTMLVersion');

		$GLOBALS['ShowCustomFields'] = 'none';
		$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($htmlvalue, false, 'myDevEditControl', 'exact', 260, 400);
		$GLOBALS['EditorHTML'] = $this->ParseTemplate('Form_Editor_HTML', true, false);
		$GLOBALS['EditorText'] = $this->ParseTemplate('Form_Editor_Text', true, false);

		$GLOBALS['FormSendFriendPage'] = $this->ParseTemplate('Form_Form_SendFriendPage', true);
	}

	/**
	* ManageForms
	* Prints out a list of forms for this user to use. If they are an admin user, they get to see everything.
	*
	* @see GetPerPage
	* @see GetCurrentPage
	* @see GetSortDetails
	* @see GetApi
	* @see User_API::Admin
	* @see Forms_API::GetForms
	* @see SetupPaging
	* @see PrintDate
	* @see User_API::HasWriteAccess
	*
	* @return Void Prints out the manage forms list and doesn't return anything.
	*/
	function ManageForms()
	{
		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		$user = GetUser();
		$perpage = $this->GetPerPage();

		/**
		 * At least 1 list have to be available in order for user to be able to create a webform
		 */
			$lists = $user->GetLists();
			if (count($lists) == 0) {
				if ($user->HasAccess('Lists', 'Create')) {
					$GLOBALS['Forms_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
					$GLOBALS['Message'] = $this->PrintSuccess('FormsNoLists', GetLang('FormsNoLists_HasAccess'));
				} else {
					$GLOBALS['Message'] = $this->PrintError('FormsNoLists', GetLang('FormsNoLists_NoAccess'));
				}

				$this->ParseTemplate('Forms_Manage_Empty');
				return;
			}
		/**
		 * -----
		 */

		$DisplayPage = $this->GetCurrentPage();
		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$formapi = $this->GetApi();

		$formowner = ($user->Admin()) ? 0 : $user->userid;
		$NumberOfForms = $formapi->GetForms($formowner, $sortinfo, true);
		$myforms = $formapi->GetForms($formowner, $sortinfo, false, $start, $perpage);

		if ($user->HasAccess('Forms', 'Create')) {
			$GLOBALS['Forms_AddButton'] = $this->ParseTemplate('Form_Create_Button', true, false);
		}

		if ($user->HasAccess('Forms', 'Delete')) {
			$GLOBALS['Forms_DeleteButton'] = $this->ParseTemplate('Form_Delete_Button', true, false);
		}

		if ($NumberOfForms == 0) {
			if ($user->HasAccess('Forms', 'Create')) {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoForms', GetLang('NoForms_HasAccess'));
			} else {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoForms', '');
			}
			$this->ParseTemplate('Forms_Manage_Empty');
			return;
		}

		$this->SetupPaging($NumberOfForms, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging';
		$paging = $this->ParseTemplate('Paging', true, false);

		$form_manage = $this->ParseTemplate('Forms_Manage', true, false);

		$formdisplay = '';

		foreach ($myforms as $pos => $formdetails) {
			$formid = $formdetails['formid'];
			$GLOBALS['FormID'] = $formid;
			$GLOBALS['Name'] = htmlspecialchars($formdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Created'] = $this->PrintDate($formdetails['createdate']);

			$GLOBALS['FormType'] = GetLang('FormType_' . $formapi->GetFormType($formdetails['formtype']));
			$GLOBALS['FormOwner'] = $formdetails['owner'];

			$GLOBALS['FormFormAction']   = '<a href="index.php?Page=Forms&Action=View&id=' . $formid . '" target="_blank">' . GetLang('View') . '</a>';

			if ($formdetails['formtype'] == 'm' || $formdetails['formtype'] == 'f') {
				if ($formdetails['formtype'] == 'm') {
					$GLOBALS['WarningDisplay'] = GetLang('GetHTML_ModifyDetails_Disabled_Alert');
					$title = GetLang('GetHTML_ModifyDetails_Disabled');
				} else {
					$GLOBALS['WarningDisplay'] = GetLang('GetHTML_SendFriend_Disabled_Alert');
					$title = GetLang('GetHTML_SendFriend_Disabled');
				}
				$GLOBALS['ItemTitle'] = $title;
				$GLOBALS['ItemName'] = GetLang('GetHTML');
				$GLOBALS['FormFormAction']  .= '&nbsp;&nbsp;' . $this->ParseTemplate('DisabledFormItem', true, false);
			} else {
				$GLOBALS['FormFormAction']  .= '&nbsp;&nbsp;<a href="index.php?Page=Forms&Action=GetHTML&id=' . $formid . '">' . GetLang('GetHTML') . '</a>';
			}

			if ($user->HasAccess('Forms', 'Edit')) {
				$GLOBALS['FormFormAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Forms&Action=Edit&id=' . $formid . '">' . GetLang('Edit') . '</a>';
			} else {
				$GLOBALS['FormFormAction'] .= $this->DisabledItem('Edit');
			}

			if ($user->HasAccess('Forms', 'Create')) {
				$GLOBALS['FormFormAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Forms&Action=Copy&id=' . $formid . '">' . GetLang('Copy') . '</a>';
			} else {
				$GLOBALS['FormFormAction'] .= $this->DisabledItem('Copy');
			}

			if ($user->HasAccess('Forms', 'Delete')) {
				$GLOBALS['FormFormAction'] .= '&nbsp;&nbsp;<a href="javascript: ConfirmDelete(' . $formid . ');">' . GetLang('Delete') . '</a>';
			} else {
				$GLOBALS['FormFormAction'] .= $this->DisabledItem('Delete');
			}

			$formdisplay .= $this->ParseTemplate('Forms_Manage_Row', true, false);
		}
		$form_manage = str_replace('%%TPL_Forms_Manage_Row%%', $formdisplay, $form_manage);
		$form_manage = str_replace('%%TPL_Paging%%', $paging, $form_manage);
		$form_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $form_manage);

		echo $form_manage;
	}

	/**
	* RemoveForms
	* Remove a list of forms based on the id's passed in.
	* Checks whether the user has access to delete forms or not before continuing.
	*
	* @param Array $forms A list of form id's to delete.
	*
	* @see GetUser
	* @see User_API::HasAccess
	* @see DenyAccess
	* @see GetAPI
	* @see Forms_API::Delete
	* @see ManageForms
	*
	* @return Void Doesn't return anything. It will print an appropriate message and then display the list of forms again, if there are any left.
	*/
	function RemoveForms($forms=array())
	{
		$user = GetUser();

		if (!$user->HasAccess('Forms', 'Delete')) {
			$this->DenyAccess();
			return;
		}

		if (!is_array($forms)) {
			$forms = array($forms);
		}

		$form_api = $this->GetApi('Forms');

		$removed = 0; $notremoved = 0;
		foreach ($forms as $pos => $formid) {
			$status = $form_api->Delete($formid);
			if ($status) {
				$removed++;
			} else {
				$notremoved++;
			}
		}

		$msg = '';

		if ($notremoved > 0) {
			if ($notremoved == 1) {
			$GLOBALS['Error'] = GetLang('FormDeleteFail_One');
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('FormDeleteFail_Many'), $this->FormatNumber($notremoved));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($removed > 0) {
			if ($removed == 1) {
				$msg .= $this->PrintSuccess('FormDeleteSuccess_One');
			} else {
				$msg .= $this->PrintSuccess('FormDeleteSuccess_Many', $this->FormatNumber($removed));
			}
		}
		$GLOBALS['Message'] = $msg;
		$this->ManageForms();
	}

	/**
	 * GetFormHTML
	 * Display "Get HTML" page. If Form's API is parsed in, it will use the form API to get HTML from
	 * @param Forms_API $formapi Form's API (OPTIONAL)
	 * @return Void Returns nothing, as it output directly to the browser
	 */
	function GetFormHTML($formapi = null)
	{
		if (is_null($formapi)) {
			$id = (isset($_GET['id'])) ? (int)$_GET['id'] : false;
			$formapi = $this->GetApi();
			$loaded = $formapi->Load($id);
		} else {
			$id = $formapi->formid;
			$loaded = true;
		}

		if (!$id || !$loaded) {
			$GLOBALS['Error'] = GetLang('NoSuchForm');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageForms();
			return;
		}

		$html = $formapi->GetHTML();
		$GLOBALS['HTMLCode'] = htmlspecialchars($html, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$this->ParseTemplate('Form_GetHTML');
	}
}
