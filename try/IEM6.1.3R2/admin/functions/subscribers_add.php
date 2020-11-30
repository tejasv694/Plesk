<?php
/**
* This file has the add subscriber functions in it. It only handles adding subscribers, nothing else. It checks for duplicates etc as it goes.
*
* @version     $Id: subscribers_add.php,v 1.39 2007/10/10 07:41:43 chris Exp $
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
* Class for adding subscribers. This simply processes the information. The API does all of the work.
*
* @see Subscribers_API
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Subscribers_Add extends Subscribers
{

	/**
	* Process
	* Works out what you're trying to do and takes appropriate action. Validates data where needed.
	*
	* @param String $action Action to perform. This is usually 'step1', 'step2', 'step3' etc. This gets passed in by the Subscribers::Process function.
	*
	* @see Subscribers::Process
	* @see GetApi
	* @see Subscribers_API::IsSubscriberOnList
	* @see Subscribers_API::AddToList
	* @see Subscribers_API::SaveSubscriberCustomField
	* @see Lists_API::GetCustomFields
	* @see CustomFields_API::IsRequired
	* @see CustomFields_API::ValidData
	* @see CustomFields_API::GetFieldName
	* @see AddSubscriber_Step2
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function Process($action=null)
	{

		switch (strtolower($action)) {
			case 'step2':
				$listid = (isset($_POST['list'])) ? (int)$_POST['list'] : $_GET['list'];
				$this->AddSubscriber_Step2($listid);
			break;

			case 'saveadd':
			case 'save':
				$user = GetUser();

				$listid = (isset($_GET['list'])) ? (int)$_GET['list'] : 0;

				if (!$user->HasAccess('Subscribers', 'Add')) {
					$this->DenyAccess();
					return;
				}

				$user_lists = $user->GetLists();

				/**
				 * Check if user have access to the list
				 */
					if (!array($user_lists) || empty($user_lists)) {
						$this->DenyAccess();
						return;
					}

					$temp = array_keys($user_lists);
					if (!in_array($listid, $temp)) {
						$this->DenyAccess();
						return;
					}
				/**
				 * -----
				 */

				$subscriber = $this->GetApi('Subscribers');

				$email = IEM::requestGetPOST('emailaddress', '', 'trim');
				if (empty($email) || !$subscriber->ValidEmail($email)) {
					$GLOBALS['Error'] = sprintf(GetLang('SubscriberAddFail_InvalidEmailAddress'), $email);
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->AddSubscriber_Step2($listid);
					break;
				}

				$duplicate = $subscriber->IsSubscriberOnList($_POST['emailaddress'], $listid);

				if ($duplicate) {
					$unsubscribed_check = $subscriber->IsUnSubscriber(false, $listid, $duplicate);
					if ($unsubscribed_check) {
						$GLOBALS['Error'] = sprintf(GetLang('SubscriberAddFail_Unsubscribed'), $_POST['emailaddress']);
					} else {
						$GLOBALS['Error'] = sprintf(GetLang('SubscriberAddFail_Duplicate'), $_POST['emailaddress']);
					}
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->AddSubscriber_Step2($listid);
					break;
				}

				list($banned, $msg) = $subscriber->IsBannedSubscriber($_POST['emailaddress'], $listid, false);
				if ($banned) {
					$GLOBALS['Error'] = sprintf(GetLang('SubscriberAddFail_Banned'), $_POST['emailaddress']);
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->AddSubscriber_Step2($listid);
					break;
				}

				$ListApi = $this->GetApi('Lists');
				$ListApi->Load($listid);
				$ListCustomFields = $ListApi->GetCustomFields($listid);

				$customfield_errors = array();

				foreach (array('emailaddress', 'format', 'confirmed') as $p => $area) {
					$subscriber->Set($area, $_POST[$area]);
				}
				$CustomFieldsValid = true;
				foreach ($ListCustomFields as $pos => $data) {
					$CustomFieldApi = $this->GetApi('CustomFields');
					$fieldid = $data['fieldid'];
					$CustomFieldApi->Load($fieldid);
					$postdata = (isset($_POST['CustomFields'][$fieldid])) ? $_POST['CustomFields'][$fieldid] : '';

					if (!isset($_POST['CustomFields'][$fieldid]) && !$CustomFieldApi->IsRequired()) {
						unset($CustomFieldApi);
						continue;
					}

					if ($CustomFieldApi->IsRequired()) {
						if (!$postdata) {
							$ftype = $CustomFieldApi->Get('fieldtype');
							switch ($ftype) {
								case 'text':
								case 'number':
									$errormsg = 'SubscriberAddFail_EmptyData_EnterData';
								break;

								case 'dropdown':
								case 'radiobutton':
								case 'checkbox':
								case 'date':
									$errormsg = 'SubscriberAddFail_EmptyData_ChooseOption';
								break;
							}

							$customfield_errors[] = sprintf(GetLang($errormsg), $CustomFieldApi->GetFieldName());

							unset($CustomFieldApi);

							continue;
						}
					}

					if (!$CustomFieldApi->ValidData($postdata)) {
						$customfield_errors[] = sprintf(GetLang('SubscriberAddFail_InvalidData'), $CustomFieldApi->GetFieldName());

						unset($CustomFieldApi);
						continue;
					}
					unset($CustomFieldApi);
				}

				if (!empty($customfield_errors)) {
					$GLOBALS['Error'] = implode('<br/>', $customfield_errors);
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->AddSubscriber_Step2($listid);
					break;
				}

				$subscriberid = $subscriber->AddToList($_POST['emailaddress'], $listid);
				$GLOBALS['Message'] = $this->PrintSuccess('SubscriberAddSuccessful');
				$GLOBALS['list'] = $listid;

				// go through each custom field and save the info.
				foreach ($ListCustomFields as $pos => $data) {
					$fieldid = $data['fieldid'];
					$postdata = (isset($_POST['CustomFields'][$fieldid])) ? $_POST['CustomFields'][$fieldid] : '';
					$subscriber->SaveSubscriberCustomField($subscriberid, $fieldid, $postdata);
				}

				if ($action == 'saveadd' || sizeof($user_lists) == 1) {
					$this->AddSubscriber_Step2($listid, true);
				} else {
					IEM::sessionSet('AddSubscriberMessage', $ListApi->Get('name'));
					?>
					<script>
						window.location = 'index.php?Page=Subscribers&Action=Add';
					</script>
					<?php
					exit();
				}
			break;

			default:
				$user = GetUser();
				$lists = $user->GetLists();

				// If only one list available, go directly to step 2
				if (count($lists) == 1) {
					$listid = array_pop(array_keys($lists));
					$this->AddSubscriber_Step2($listid);
				} else {
					$this->ChooseList('add', 'step2');
				}
			break;
		}
	}

	/**
	* AddSubscriber_Step2
	* Prints out the 'add subscriber' form. Prints out custom fields to add, sets default options and so on.
	*
	* @param Int $listid Listid to add the subscriber to.
	* @param Boolean $clear_post Whether to clear post information or not before pre-filling the form. This is used when the user chooses "Save & Add".
	*
	* @see GetApi
	* @see Lists_API::Load
	* @see Lists_API::GetCustomFields
	* @see Lists_API::GetListFormat
	*
	* @return Void Prints out the form and doesn't return anything.
	*/
	function AddSubscriber_Step2($listid=0, $clear_post=false)
	{
		$user = GetUser();
		$access = $user->HasAccess('Subscribers', 'Manage');
		if (!$access) {
			$this->DenyAccess();
			return;
		}

		$lists = $user->GetLists();
		$num_lists = sizeof(array_keys($lists));

		if ($num_lists > 1) {
			$GLOBALS['SaveExitButton'] = $this->ParseTemplate('Subscribers_Add_Save_Exit_Button', true, false);
		}

		if (isset($_POST['emailaddress']) && !$clear_post) {
			$GLOBALS['emailaddress'] = $_POST['emailaddress'];
			$formatoption_chosen = $_POST['format'];
			$confirmoption_chosen = $_POST['confirmed'];
		} else {
			$GLOBALS['emailaddress'] = '';
			$formatoption_chosen = 'h';
			$confirmoption_chosen = '1';
		}

		$GLOBALS['list'] = $listid;

		$confirmed = '';
		foreach (array('1' => 'Confirmed', '0' => 'Unconfirmed') as $confirmoption => $option) {
			$selected = ($confirmoption == $confirmoption_chosen) ? ' SELECTED' : '';
			$confirmed .= '<option value="' . $confirmoption . '"' . $selected . '>' . GetLang($option) . '</option>';
		}

		$GLOBALS['ConfirmedList'] = $confirmed;

		$list_api = $this->GetApi('Lists');
		$list_api->Load($listid);

		$GLOBALS['Heading'] = sprintf(GetLang('Subscribers_Add_Step2'), htmlspecialchars($list_api->Get('name'), ENT_QUOTES, SENDSTUDIO_CHARSET));
		$customfields = $list_api->GetCustomFields($listid);

		$listformat = $list_api->GetListFormat();

		switch ($listformat) {
			case 't':
				$format = '<option value="t" SELECTED>' . GetLang('Format_Text') . '</option>';
			break;
			case 'h':
				$format = '<option value="h" SELECTED>' . GetLang('Format_HTML') . '</option>';
			break;
			case 'b':
				$format = '<option value="h"' . (($formatoption_chosen == 'h') ? ' SELECTED' : '' ) . '>' . GetLang('Format_HTML') . '</option>';
				$format .= '<option value="t"' . (($formatoption_chosen == 't') ? ' SELECTED' : '' ) . '>' . GetLang('Format_Text') . '</option>';
			break;
		}

		$GLOBALS['FormatList'] = $format;

		$extra_javascript = '';
		$customfield_display = array();
		$customfieldinfo = '';

		if (!empty($customfields)) {
			$customfieldinfo = $this->ParseTemplate('Subscribers_Add_Step2_CustomFields', true, false);
			foreach ($customfields as $pos => $customfield_info) {
				$GLOBALS['FieldID'] = $customfield_info['fieldid'];
				if ($customfield_info['required']) {
					$GLOBALS['Required'] = $this->ParseTemplate('Required', true, false);
				} else {
					$GLOBALS['Required'] = $this->ParseTemplate('Not_Required', true, false);
				}

				$defaultvalue = $customfield_info['defaultvalue'];

				if (!$clear_post) {
					if (isset($_POST['CustomFields'][$customfield_info['fieldid']])) {
						$defaultvalue = $_POST['CustomFields'][$customfield_info['fieldid']];
					}
				}

				switch ($customfield_info['fieldtype']) {
					case 'date':
						$optionlist = '';
						$this->Display_CustomField($customfield_info,$defaultvalue);
					break;

					case 'radiobutton':
						$fieldsettings = (is_array($customfield_info['fieldsettings'])) ? $customfield_info['fieldsettings'] : unserialize($customfield_info['fieldsettings']);

						$optionlist = '';
						$c = 1;
						foreach ($fieldsettings['Key'] as $pos => $key) {
							$selected = '';
							if (!$clear_post && isset($_POST['CustomFields'][$customfield_info['fieldid']])) {
								$chosen_values = array($_POST['CustomFields'][$customfield_info['fieldid']]);
								if (in_array($key, $chosen_values)) {
									$selected = ' CHECKED';
								}
							} else {
								if ($key == $customfield_info['defaultvalue']) {
									$selected = ' CHECKED';
								}
							}

							$label_id = htmlspecialchars('CustomFields_' . $customfield_info['fieldid'].'_'.$c, ENT_QUOTES, SENDSTUDIO_CHARSET);

							$optionlist .= '<label for="'.$label_id.'"><input type="radio" id="'.$label_id.'" name="CustomFields[' . $customfield_info['fieldid'] . ']" value="' . htmlspecialchars($key, ENT_QUOTES, SENDSTUDIO_CHARSET) . '"' . $selected . '>' . htmlspecialchars($fieldsettings['Value'][$pos], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</label>';
							if ($c % 4 == 0) {
								$optionlist .= '<br/>';
							}
							$c++;
						}

						if ($customfield_info['required']) {
							$extra_javascript .= '
								field_'.$customfield_info['fieldid'].'_check = CheckRadio("CustomFields_'.$customfield_info['fieldid'].'");

								if (!field_'.$customfield_info['fieldid'].'_check) {
									alert("' . sprintf(GetLang('ChooseValueForCustomField'), $customfield_info['name']) . '");
									return false;
								}
							';
						}

					break;

					case 'dropdown':
						$fieldsettings = (is_array($customfield_info['fieldsettings'])) ? $customfield_info['fieldsettings'] : unserialize($customfield_info['fieldsettings']);
						$optionlist = '';

						$optionlist .= '<option value="">' . $customfield_info['defaultvalue'] . '</option>';

						foreach ($fieldsettings['Key'] as $pos => $key) {
							$selected = '';
							if ($key == $defaultvalue) {
								$selected = ' SELECTED';
							}

							$optionlist .= '<option value="' . htmlspecialchars($key, ENT_QUOTES, SENDSTUDIO_CHARSET) . '"' . $selected . '>' . htmlspecialchars($fieldsettings['Value'][$pos], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
						}

						if ($customfield_info['required']) {
							$extra_javascript .= '
								fld = document.getElementById("CustomFields['.$customfield_info['fieldid'].']");
								selIndex = fld.selectedIndex;
								if (selIndex < 1) {
									alert("'.sprintf(GetLang('ChooseOptionForCustomField'), $customfield_info['name']) . '");
									fld.focus();
									return false;
								}
							';
						}

					break;

					case 'checkbox':
						$fieldsettings = (is_array($customfield_info['fieldsettings'])) ? $customfield_info['fieldsettings'] : unserialize($customfield_info['fieldsettings']);

						$chosen_values = array();
						if (isset($_POST['CustomFields'][$customfield_info['fieldid']]) && !$clear_post) {
							$chosen_values = $_POST['CustomFields'][$customfield_info['fieldid']];
						}

						$optionlist = '';
						$c = 1;
						foreach ($fieldsettings['Key'] as $pos => $key) {
							$checked = '';

							if (in_array($key, $chosen_values)) {
								$checked = ' CHECKED';
							}

							$label = htmlspecialchars('CustomFields[' . $customfield_info['fieldid'] . '][' . $key . ']', ENT_QUOTES, SENDSTUDIO_CHARSET);

							$optionlist .= '<label for="' . $label . '"><input type="checkbox" name="CustomFields[' . $customfield_info['fieldid'] . '][' . $pos . ']" id="' . $label . '" value="' . htmlspecialchars($key, ENT_QUOTES, SENDSTUDIO_CHARSET) . '"' . $checked . '>' . htmlspecialchars($fieldsettings['Value'][$pos], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</label>';

							if ($c % 4 == 0) {
								$optionlist .= '<br/>';
							}

							$c++;
						}

						if ($customfield_info['required']) {
							$extra_javascript .= '
								CheckboxCheck = CheckMultiple("CustomFields[' . $customfield_info['fieldid'] . ']", f);
								if (!CheckboxCheck) {
									alert("' . sprintf(GetLang('ChooseValueForCustomField'), htmlspecialchars($customfield_info['name'], ENT_QUOTES, SENDSTUDIO_CHARSET)) . '");
									return false;
								}
							';
						}

					break;

					default:
						$optionlist = '';

						if ($customfield_info['required']) {
							$extra_javascript .= '
								if (document.getElementById("CustomFields['.$customfield_info['fieldid'].']").value == "") {
									alert("' . sprintf(GetLang('EnterValueForCustomField'), htmlspecialchars($customfield_info['name'], ENT_QUOTES, SENDSTUDIO_CHARSET)) . '");
									document.getElementById("CustomFields['.$customfield_info['fieldid'].']").focus();
									return false;
								}
							';
						}

				}
				$GLOBALS['OptionList'] = $optionlist;
				if (!is_array($defaultvalue)) {
					$GLOBALS['DefaultValue'] = htmlspecialchars($defaultvalue, ENT_QUOTES, SENDSTUDIO_CHARSET);
				}
				$GLOBALS['FieldName'] = htmlspecialchars($customfield_info['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
				$GLOBALS['CustomFieldID'] = $customfield_info['fieldid'];
				$customfield_display[] = $this->ParseTemplate('CustomField_Edit_' . $customfield_info['fieldtype'], true, false);
			}

			$column1 = $column2 = array();
			if (count($customfield_display) > 9) {
				$customfieldinfo_template = 'Subscribers_customfieldinfo_twocolumns';
				$split = ceil(count($customfield_display) / 2);

				for ($i = 0; $i < $split; $i++) {
					$column1[]= $customfield_display[$i];
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
			$GLOBALS['ExtraJavascript'] = $extra_javascript;
		}

		$GLOBALS['CustomDatepickerUI'] = $this->ParseTemplate('UI.DatePicker.Custom_IEM', true);
		$this->ParseTemplate('Subscribers_Add_Step2');
	}
}
