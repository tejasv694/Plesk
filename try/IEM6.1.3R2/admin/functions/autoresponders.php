<?php
/**
* This file has the autoresponders page in it. This allows you to manage, create and edit your autoresponders.
*
* @version     $Id: autoresponders.php,v 1.81 2008/02/15 06:07:46 chris Exp $
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
* Class for management of autoresponders.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Autoresponders extends SendStudio_Functions
{
	/**
	 * A list of actions that will have header and footer suppressed
	 * @var Array
	 */
	var $SuppressHeaderFooter = array('checkspam', 'sendpreview', 'viewcompatibility');

	/**
	* ValidSorts
	* An array of sorts you can use with autoresponder management.
	*
	* @var Array
	*/
	var $ValidSorts = array('name', 'createdate', 'hoursaftersubscription', 'owner');

	/**
	* _DefaultSort
	* Default sort for autoresponders is hours after subscription
	*
	* @see GetSortDetails
	*
	* @var String
	*/
	var $_DefaultSort = 'hoursaftersubscription';

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
	* Constructor
	* Loads the language file.
	* Adds 'sendpreview' to the list of valid popup windows.
	*
	* @return Void Doesn't return anything.
	*/
	function Autoresponders()
	{
		$this->PopupWindows[] = 'sendpreviewdisplay';
		$this->PopupWindows[] = 'checkspamdisplay';
		$this->PopupWindows[] = 'viewcompatibility';
		$this->LoadLanguageFile();
	}

	/**
	* Process
	* Takes the appropriate action based on the action and user permissions
	*
	* @see GetUser
	* @see User_API::HasAccess
	* @see PrintHeader
	* @see PrintFooter
	*
	* @return Void Doesn't return anything. Takes the appropriate action.
	*/
	function Process()
	{
		$GLOBALS['Message'] = '';

		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;
		$user = GetUser();

		$secondary_actions = array('step2', 'sendpreview', 'view', 'processpaging', 'activate', 'deactivate', 'pause', 'resume', 'change', 'checkspam', 'viewcompatibility');
		if (in_array($action, $secondary_actions)) {
			$access = $user->HasAccess('Autoresponders');
		} else {
			$access = $user->HasAccess('Autoresponders', $action);
		}

		$popup = (in_array($action, $this->PopupWindows)) ? true : false;
		if (!in_array($action, $this->SuppressHeaderFooter)) {
			$this->PrintHeader($popup);
		}

		if (!$access) {
			if (!$popup) {
				$this->DenyAccess();
				return;
			}
		}

		/**
		 * Check user permission to see whether or not they have access to the autoresponder
		 */
			$tempAPI = null;
			$tempCheckActions = array('activate', 'deactivate', 'copy', 'change', 'pause', 'resume', 'delete', 'step2', 'sendpreview', 'view', 'edit');
			$tempID = null;

			if (isset($_GET['id'])) {
				$tempID = $_GET['id'];
			} elseif(isset($_POST['autoresponders'])) {
				$tempID = $_POST['autoresponders'];
			}

			if (!is_null($tempID)) {
				$_GET['id'] = $tempID;
				$_POST['autoresponders'] = $tempID;

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

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = 'step2';
		}


		switch ($action) {
			case 'pause':
			case 'resume': 
				$autoresponderAPI = $this->GetApi();
				$autoresponderID = IEM::requestGetGET('id', 0, 'intval');
				$listID = IEM::requestGetGET('list', 0, 'intval');

				if ($action == 'pause') {
					$autoresponderAPI->PauseAutoresponder($autoresponderID);
				} else {
					$autoresponderAPI->ResumeAutoresponder($autoresponderID);
				}

				$this->ManageAutoresponders($listID);
			break;

			case 'viewcompatibility':
				$auto_info = IEM::sessionGet('Autoresponders');

				$html = (isset($_POST['myDevEditControl_html'])) ? $_POST['myDevEditControl_html'] : false;
				$text = (isset($_POST['TextContent'])) ? $_POST['TextContent'] : false;
				$showBroken = isset($_REQUEST['ShowBroken']) && $_REQUEST['ShowBroken'] == 1;
				$details = array();
				$details['htmlcontent'] = $html;
				$details['textcontent'] = $text;
				$details['format'] = $auto_info['Format'];

				$this->PreviewWindow($details, $showBroken);
				exit;
			break;

			case 'checkspamdisplay':
				$force = IEM::ifsetor($_GET['Force'], false);
				$this->CheckContentForSpamDisplay($force);
			break;

			case 'checkspam':
				$text = (isset($_POST['TextContent'])) ? $_POST['TextContent'] : false;
				$html = (isset($_POST['myDevEditControl_html'])) ? $_POST['myDevEditControl_html'] : false;
				$this->CheckContentForSpam($text, $html);
			break;

			case 'activate':
			case 'deactivate':
				$access = $user->HasAccess('Autoresponders', 'Approve');
				if (!$access) {
					$this->DenyAccess();
					break;
				}

				$id = (int)$_GET['id'];
				$autoapi = $this->GetApi();
				$autoapi->Load($id);
				if ($action == 'activate') {
					$prob_found = false;
					$max_size = (SENDSTUDIO_EMAILSIZE_MAXIMUM*1024);
					if ($max_size > 0) {
						if ($autoapi->Get('autorespondersize') > $max_size) {
							$prob_found = true;
							if ($autoapi->Get('embedimages')) {
								$error_langvar = 'Autoresponder_Size_Over_EmailSize_Maximum_Embed';
							} else {
								$error_langvar = 'Autoresponder_Size_Over_EmailSize_Maximum_No_Embed';
							}
							$GLOBALS['Error'] = sprintf(GetLang($error_langvar), $this->EasySize($max_size, 0));
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						}
					}
					if (!$prob_found) {
						$allow_attachments = $this->CheckForAttachments($id, 'autoresponders');
						if ($allow_attachments) {
							$autoapi->Set('active', $user->Get('userid'));
							$GLOBALS['Message'] = $this->PrintSuccess('AutoresponderActivatedSuccessfully');
						} else {
							$GLOBALS['Error'] = GetLang('AutoresponderActivateFailed_HasAttachments');
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						}
					}
				} else {
					$autoapi->Set('active', 0);
					$GLOBALS['Message'] = $this->PrintSuccess('AutoresponderDeactivatedSuccessfully');
				}
				$autoapi->Save();

				if (isset($_GET['list'])) {
					$listid = (int)$_GET['list'];
				}

				$this->ManageAutoresponders($listid);
			break;

			case 'copy':
				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
				$api = $this->GetApi();
				list($result, $files_copied) = $api->Copy($id);
				if (!$result) {
					$GLOBALS['Error'] = GetLang('AutoresponderCopyFail');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				} else {
					$api->Set('active', 0);
					$api->Save();
					$GLOBALS['Message'] = $this->PrintSuccess('AutoresponderCopySuccess');
					$GLOBALS['Message'] .= $this->PrintWarning('AutoresponderHasBeenDisabled');
					if (!$files_copied) {
						$GLOBALS['Error'] = GetLang('AutoresponderFilesCopyFail');
						$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
					}
				}
				if (isset($_GET['list'])) {
					$listid = (int)$_GET['list'];
				}

				$this->ManageAutoresponders($listid);
			break;

			case 'change':
				$subaction = strtolower($_POST['ChangeType']);
				$autolist = $_POST['autoresponders'];

				switch ($subaction) {
					case 'delete':
						$access = $user->HasAccess('Autoresponders', 'Delete');
						if ($access) {
							$this->DeleteAutoresponders($autolist);
						} else {
							$this->DenyAccess();
						}
					break;

					case 'approve':
					case 'disapprove':
						$access = $user->HasAccess('Autoresponders', 'Approve');
						if ($access) {
							$this->ActionAutoresponders($autolist, $subaction);
						} else {
							$this->DenyAccess();
						}
					break;
				}
			break;

			case 'delete':
				$id = (int)$_GET['id'];
				$autolist = array($id);
				$access = $user->HasAccess('Autoresponders', 'Delete');
				if ($access) {
					$this->DeleteAutoresponders($autolist);
				} else {
					$this->DenyAccess();
				}
			break;

			case 'step2':
				$listid = 0;
				if (isset($_GET['list'])) {
					$listid = (int)$_GET['list'];
				}

				$this->ManageAutoresponders($listid);
			break;

			case 'sendpreviewdisplay':
				$this->SendPreviewDisplay();
			break;

			case 'sendpreview':
				$this->SendPreview();
			break;

			case 'view':
				$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;
				$type = strtolower(get_class($this));
				$autoresponderapi = $this->GetApi();
				if (!$autoresponderapi->Load($id)) {
					break;
				}

				// Log this to "User Activity Log"
				$logURL = SENDSTUDIO_APPLICATION_URL . '/admin/index.php?Page=' . __CLASS__ . '&Action=Edit&id=' . $_GET['id'];
				IEM::logUserActivity($logURL, 'images/autoresponders_view.gif', $autoresponderapi->name);

				$details = array();
				$details['htmlcontent'] = $autoresponderapi->GetBody('HTML');
				$details['textcontent'] = $autoresponderapi->GetBody('Text');
				$details['format'] = $autoresponderapi->format;
				$this->PreviewWindow($details);
			break;

			case 'edit':
				$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : false;

				switch ($subaction) {
					case 'save':
					case 'complete':
						$user = IEM::getCurrentUser();
						$session_autoresponder = IEM::sessionGet('Autoresponders');

						$listid = $session_autoresponder['list'];

						if (!$session_autoresponder || !isset($session_autoresponder['autoresponderid'])) {
							$this->ManageAutoresponders($listid);
							break;
						}

						$text_unsubscribelink_found = true;
						$html_unsubscribelink_found = true;

						$id = $session_autoresponder['autoresponderid'];

						$autoapi = $this->GetApi();
						$autoapi->Load($id);

						$autoapi->Set('listid', $listid);

						if (isset($_POST['TextContent'])) {
							$textcontent = $_POST['TextContent'];
							$autoapi->SetBody('Text', $textcontent);
							$text_unsubscribelink_found = $this->CheckForUnsubscribeLink($textcontent, 'text');
							$session_autoresponder['contents']['text'] = $textcontent;
						}

						if (isset($_POST['myDevEditControl_html'])) {
							$htmlcontent = $_POST['myDevEditControl_html'];

							/**
							 * This is an effort not to overwrite the eixsting HTML contents
							 * if there isn't any contents in it (DevEdit will have '<html><body></body></html>' as a minimum
							 * that will be passed to here)
							 */
							if (trim($htmlcontent) == '') {
								$GLOBALS['Error'] = GetLang('UnableToUpdateAutoresponder');
								$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
								$this->EditAutoresponderStep4($id);
								break;
							}

							$autoapi->SetBody('HTML', $htmlcontent);
							$html_unsubscribelink_found = $this->CheckForUnsubscribeLink($htmlcontent, 'html');
							$session_autoresponder['contents']['html'] = $htmlcontent;
						}

						if (isset($_POST['subject'])) {
							$autoapi->Set('subject', $_POST['subject']);
						}

						foreach (array('name', 'format', 'searchcriteria', 'sendfromname', 'sendfromemail', 'replytoemail', 'bounceemail', 'tracklinks', 'trackopens', 'multipart', 'embedimages', 'hoursaftersubscription', 'charset', 'includeexisting', 'to_firstname', 'to_lastname') as $p => $area) {
							$autoapi->Set($area, $session_autoresponder[$area]);
						}

						$autoapi->Set('active', 0);

						$dest = strtolower(get_class($this));

						$movefiles_result = $this->MoveFiles($dest, $id);

						if ($movefiles_result) {
							if (isset($textcontent)) {
								$textcontent = $this->ConvertContent($textcontent, $dest, $id);
								$autoapi->SetBody('Text', $textcontent);
							}
							if (isset($htmlcontent)) {
								$htmlcontent = $this->ConvertContent($htmlcontent, $dest, $id);
								$autoapi->SetBody('HTML', $htmlcontent);
							}
						}

						// Delete any attachments we're meant to first
						if (SENDSTUDIO_ALLOW_ATTACHMENTS) {
							list($del_attachments_status, $del_attachments_status_msg) = $this->CleanupAttachments($dest, $id);

							if ($del_attachments_status) {
								if ($del_attachments_status_msg) {
									$GLOBALS['Success'] = $del_attachments_status_msg;
									$GLOBALS['Message'] .= $this->ParseTemplate('SuccessMsg', true, false);
								}
							} else {
								$GLOBALS['Error'] = $del_attachments_status_msg;
								$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
							}

							// Only save the new attachments after deleting the old ones
							list($attachments_status, $attachments_status_msg) = $this->SaveAttachments($dest, $id);

							if ($attachments_status) {
								if ($attachments_status_msg != '') {
									$GLOBALS['Success'] = $attachments_status_msg;
									$GLOBALS['Message'] .= $this->ParseTemplate('SuccessMsg', true, false);
								}
							} else {
								$GLOBALS['AttachmentError'] = $attachments_status_msg;
								$GLOBALS['Error'] = $attachments_status_msg;
								$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
							}
						}

						list($autoresponder_size, $autoresponder_img_warnings) = $this->GetSize($session_autoresponder);
						$GLOBALS['Message'] .= $this->PrintSuccess('AutoresponderUpdated', sprintf(GetLang('Autoresponder_Size_Approximate'), $this->EasySize($autoresponder_size)));
						$max_size = (SENDSTUDIO_EMAILSIZE_MAXIMUM*1024);

						if (SENDSTUDIO_EMAILSIZE_WARNING > 0) {
							$warning_size = SENDSTUDIO_EMAILSIZE_WARNING * 1024;
							if ($autoresponder_size > $warning_size && ($max_size > 0 && $autoresponder_size < $max_size)) {
								if ($session_autoresponder['embedimages']) {
									$warning_langvar = 'Autoresponder_Size_Over_EmailSize_Warning_Embed';
								} else {
									$warning_langvar = 'Autoresponder_Size_Over_EmailSize_Warning_No_Embed';
								}
								$GLOBALS['Message'] .= $this->PrintWarning($warning_langvar, $this->EasySize($warning_size));
							}
						}

						if ($max_size > 0 && $autoresponder_size >= $max_size) {
							if ($session_autoresponder['embedimages']) {
								$error_langvar = 'Autoresponder_Size_Over_EmailSize_Maximum_Embed';
							} else {
								$error_langvar = 'Autoresponder_Size_Over_EmailSize_Maximum_No_Embed';
							}
							$GLOBALS['Error'] = sprintf(GetLang($error_langvar), $this->EasySize($max_size, 0));

							$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
						}

						$autoapi->Set('autorespondersize', $autoresponder_size);

						$result = $autoapi->Save();

						if (!$result) {
							$GLOBALS['Error'] = GetLang('UnableToUpdateAutoresponder');
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
							$this->ManageAutoresponders($listid);
							break;
						}

						if ($autoresponder_img_warnings) {
							if ($session_autoresponder['embedimages']) {
								$warning_var = 'UnableToLoadImage_Autoresponder_List_Embed';
							} else {
								$warning_var = 'UnableToLoadImage_Autoresponder_List';
							}
							$GLOBALS['Message'] .= $this->PrintWarning($warning_var, $autoresponder_img_warnings);
						}

						if (!$html_unsubscribelink_found) {
							$GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInHTMLContent');
						}

						if (!$text_unsubscribelink_found) {
							$GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInTextContent');
						}
						
                        if(is_dir(TEMP_DIRECTORY . "/autoresponders/".$user->userid."_tmp")){remove_directory(TEMP_DIRECTORY . "/autoresponders/".$user->userid."_tmp");}


						if ($subaction == 'save') {
							$GLOBALS['Message'] .= $this->PrintWarning('AutoresponderHasBeenDisabled_Save');

							$GLOBALS['Message'] = str_replace('<br><br>', '<br>', $GLOBALS['Message']);

							$this->EditAutoresponderStep4($id);
							break;
						}

						$GLOBALS['Message'] .= $this->PrintWarning('AutoresponderHasBeenDisabled');

						$GLOBALS['Message'] = str_replace('<br><br>', '<br>', $GLOBALS['Message']);

						$this->ManageAutoresponders($listid);

					break;

					case 'step4':
						$sessionauto = IEM::sessionGet('Autoresponders');

						$sessionauto['sendfromname'] = $_POST['sendfromname'];
						$sessionauto['sendfromemail'] = $_POST['sendfromemail'];
						$sessionauto['replytoemail'] = $_POST['replytoemail'];
						$sessionauto['bounceemail'] = $_POST['bounceemail'];

						$sessionauto['charset'] = $_POST['charset'];

						$sessionauto['format'] = $_POST['format'];
						$sessionauto['hoursaftersubscription'] = (int)$_POST['hoursaftersubscription'];
						$sessionauto['trackopens'] = (isset($_POST['trackopens'])) ? true : false;
						$sessionauto['tracklinks'] = (isset($_POST['tracklinks'])) ? true : false;
						$sessionauto['multipart'] = (isset($_POST['multipart'])) ? true : false;
						$sessionauto['embedimages'] = (isset($_POST['embedimages'])) ? true : false;
						$sessionauto['includeexisting'] = (isset($_POST['includeexisting'])) ? true : false;

						$sessionauto['to_lastname'] = 0;
						if (isset($_POST['to_lastname'])) {
							$sessionauto['to_lastname'] = (int)$_POST['to_lastname'];
						}
						$sessionauto['to_firstname'] = 0;
						if (isset($_POST['to_firstname'])) {
							$sessionauto['to_firstname'] = (int)$_POST['to_firstname'];
						}

						IEM::sessionSet('Autoresponders', $sessionauto);

						$this->EditAutoresponderStep4($sessionauto['autoresponderid']);
					break;

					case 'step3':
						$sessionauto = IEM::sessionGet('Autoresponders');
						$sessionauto['name'] = $_POST['name'];
						$sessionauto['searchcriteria'] = array(
							'emailaddress' => '',
							'format' => '-1',
							'confirmed' => '1',
							'search_options' => array(),
							'customfields' => array()
						);

						if ($_POST['ShowFilteringOptions'] == 1) {
							$sessionauto['searchcriteria']['emailaddress'] = $_POST['emailaddress'];
							$sessionauto['searchcriteria']['format'] = $_POST['format'];
							$sessionauto['searchcriteria']['confirmed'] = $_POST['confirmed'];

							$search_options = (isset($_POST['Search_Options'])) ? $_POST['Search_Options'] : array();
							$sessionauto['searchcriteria']['search_options'] = $search_options;

							$customfields = (isset($_POST['CustomFields'])) ? $_POST['CustomFields'] : array();
							$sessionauto['searchcriteria']['customfields'] = $customfields;

							foreach ($sessionauto['searchcriteria']['customfields'] as $fieldid => $fieldvalue) {
								if (!$fieldvalue) {
									unset($sessionauto['searchcriteria']['customfields'][$fieldid]);
									continue;
								}
							}

							if (isset($_POST['clickedlink']) && isset($_POST['linkid'])) {
								$sessionauto['searchcriteria']['linktype'] = 'clicked';
								if (isset($_POST['linktype']) && $_POST['linktype'] == 'not_clicked') {
									$sessionauto['searchcriteria']['linktype'] = 'not_clicked';
								}

								$sessionauto['searchcriteria']['link'] = $_POST['linkid'];
							}

							if (isset($_POST['openednewsletter']) && isset($_POST['newsletterid'])) {
								$sessionauto['searchcriteria']['opentype'] = 'opened';
								if (isset($_POST['opentype']) && $_POST['opentype'] == 'not_opened') {
									$sessionauto['searchcriteria']['opentype'] = 'not_opened';
								}

								$sessionauto['searchcriteria']['newsletter'] = $_POST['newsletterid'];
							}
						}

						IEM::sessionSet('Autoresponders', $sessionauto);

						$this->EditAutoresponderStep3($sessionauto['autoresponderid']);
					break;

					default:
						$id = (int)$_GET['id'];

						IEM::sessionRemove('Autoresponders');
						$autosession = array('list' => (int)$_GET['list'], 'autoresponderid' => $id);
						IEM::sessionSet('Autoresponders', $autosession);

						$this->EditAutoresponderStep1($id);
				}
			break;

			case 'create':
				$subaction = (isset($_GET['SubAction'])) ? strtolower($_GET['SubAction']) : false;

				switch ($subaction) {

					case 'save':
					case 'complete':
						$autoresponder = $this->GetApi();

						$user = IEM::getCurrentUser();
						$session_autoresponder = IEM::sessionGet('Autoresponders');

						if (!$session_autoresponder || !isset($session_autoresponder['name'])) {
							$this->ManageAutoresponders($listid);
							break;
						}

						$text_unsubscribelink_found = true;
						$html_unsubscribelink_found = true;

						$listid = $session_autoresponder['list'];

						$autoresponder->Set('listid', $listid);

						if (isset($_POST['TextContent'])) {
							$textcontent = $_POST['TextContent'];
							$autoresponder->SetBody('Text', $textcontent);
							$text_unsubscribelink_found = $this->CheckForUnsubscribeLink($textcontent, 'text');
							$session_autoresponder['contents']['text'] = $textcontent;
						}

						if (isset($_POST['myDevEditControl_html'])) {
							$htmlcontent = $_POST['myDevEditControl_html'];
							$autoresponder->SetBody('HTML', $htmlcontent);
							$html_unsubscribelink_found = $this->CheckForUnsubscribeLink($htmlcontent, 'html');
							$session_autoresponder['contents']['html'] = $htmlcontent;
						}

						if (isset($_POST['subject'])) {
							$autoresponder->Set('subject', $_POST['subject']);
						}

						foreach (array('name', 'format', 'searchcriteria', 'sendfromname', 'sendfromemail', 'replytoemail', 'bounceemail', 'tracklinks', 'trackopens', 'multipart', 'embedimages', 'hoursaftersubscription', 'charset', 'includeexisting', 'to_firstname', 'to_lastname') as $p => $area) {
							$autoresponder->Set($area, $session_autoresponder[$area]);
						}

						$autoresponder->Set('active', 0);

						$autoresponder->ownerid = $user->userid;

						$result = $autoresponder->Create();

						if (!$result) {
							$GLOBALS['Error'] = GetLang('UnableToCreateAutoresponder');
							$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
							$this->ManageAutoresponders($listid);
							break;
						}

						/**
						* explicitly set the 'includeexisting' flag to false so we don't import the existing subscribers twice.
						* Create() & Save() both call ImportQueue if this flag is set, so ensure we don't do it twice.
						*/
						$autoresponder->Set('includeexisting', false);

						$session_autoresponder['autoresponderid'] = $result;
						IEM::sessionSet('Autoresponders', $session_autoresponder);

						if (SENDSTUDIO_ALLOW_ATTACHMENTS) {
							$dest = strtolower(get_class($this));
							$movefiles_result = $this->MoveFiles($dest, $result);
							if ($movefiles_result) {
								if (isset($textcontent)) {
									$textcontent = $this->ConvertContent($textcontent, $dest, $result);
									$autoresponder->SetBody('Text', $textcontent);
								}
								if (isset($htmlcontent)) {
									$htmlcontent = $this->ConvertContent($htmlcontent, $dest, $result);
									$autoresponder->SetBody('HTML', $htmlcontent);
								}
							}

							list($attachments_status, $attachments_status_msg) = $this->SaveAttachments($dest, $result);

							if ($attachments_status) {
								if ($attachments_status_msg != '') {
									$GLOBALS['Success'] = $attachments_status_msg;
									$GLOBALS['Message'] .= $this->ParseTemplate('SuccessMsg', true, false);
								}
							} else {
								$GLOBALS['Error'] = $attachments_status_msg;
								$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
							}
						}

						list($autoresponder_size, $autoresponder_img_warnings) = $this->GetSize($session_autoresponder);
						$GLOBALS['Message'] .= $this->PrintSuccess('AutoresponderUpdated', sprintf(GetLang('Autoresponder_Size_Approximate'), $this->EasySize($autoresponder_size)));
						$max_size = (SENDSTUDIO_EMAILSIZE_MAXIMUM*1024);

						if (SENDSTUDIO_EMAILSIZE_WARNING > 0) {
							$warning_size = SENDSTUDIO_EMAILSIZE_WARNING * 1024;
							if ($autoresponder_size > $warning_size && ($max_size > 0 && $autoresponder_size < $max_size)) {
								if ($session_autoresponder['embedimages']) {
									$warning_langvar = 'Autoresponder_Size_Over_EmailSize_Warning_Embed';
								} else {
									$warning_langvar = 'Autoresponder_Size_Over_EmailSize_Warning_No_Embed';
								}
								$GLOBALS['Message'] .= $this->PrintWarning($warning_langvar, $this->EasySize($warning_size));
							}
						}

						if ($max_size > 0 && $autoresponder_size >= $max_size) {
							if ($session_autoresponder['embedimages']) {
								$error_langvar = 'Autoresponder_Size_Over_EmailSize_Maximum_Embed';
							} else {
								$error_langvar = 'Autoresponder_Size_Over_EmailSize_Maximum_No_Embed';
							}
							$GLOBALS['Error'] = sprintf(GetLang($error_langvar), $this->EasySize($max_size, 0));

							$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
						}

						$autoresponder->Set('autorespondersize', $autoresponder_size);

						$autoresponder->Save();

						if ($autoresponder_img_warnings) {
							if ($session_autoresponder['embedimages']) {
								$warning_var = 'UnableToLoadImage_Autoresponder_List_Embed';
							} else {
								$warning_var = 'UnableToLoadImage_Autoresponder_List';
							}
							$GLOBALS['Message'] .= $this->PrintWarning($warning_var, $autoresponder_img_warnings);
						}

						if (!$html_unsubscribelink_found) {
							$GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInHTMLContent');
						}

						if (!$text_unsubscribelink_found) {
							$GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInTextContent');
						}

						if ($subaction == 'save') {
							$GLOBALS['Message'] .= $this->PrintWarning('AutoresponderHasBeenDisabled_Save');
							$GLOBALS['Message'] = str_replace('<br><br>', '<br>', $GLOBALS['Message']);
							$this->EditAutoresponderStep4($result);
							break;
						}

						$GLOBALS['Message'] .= $this->PrintWarning('AutoresponderHasBeenDisabled');

						$GLOBALS['Message'] = str_replace('<br><br>', '<br>', $GLOBALS['Message']);

						$this->ManageAutoresponders($listid);
					break;

					case 'step4':
						$sessionauto = IEM::sessionGet('Autoresponders');

						$sessionauto['sendfromname'] = $_POST['sendfromname'];
						$sessionauto['sendfromemail'] = $_POST['sendfromemail'];
						$sessionauto['replytoemail'] = $_POST['replytoemail'];
						$sessionauto['bounceemail'] = $_POST['bounceemail'];

						$sessionauto['charset'] = $_POST['charset'];

						$sessionauto['format'] = $_POST['format'];
						$sessionauto['hoursaftersubscription'] = (int)$_POST['hoursaftersubscription'];
						$sessionauto['trackopens'] = (isset($_POST['trackopens'])) ? true : false;
						$sessionauto['tracklinks'] = (isset($_POST['tracklinks'])) ? true : false;
						$sessionauto['multipart'] = (isset($_POST['multipart'])) ? true : false;
						$sessionauto['embedimages'] = (isset($_POST['embedimages'])) ? true : false;

						$sessionauto['includeexisting'] = (isset($_POST['includeexisting'])) ? true : false;

						$sessionauto['to_lastname'] = 0;
						if (isset($_POST['to_lastname'])) {
							$sessionauto['to_lastname'] = (int)$_POST['to_lastname'];
						}

						$sessionauto['to_firstname'] = 0;
						if (isset($_POST['to_firstname'])) {
							$sessionauto['to_firstname'] = (int)$_POST['to_firstname'];
						}

						if (isset($_POST['TemplateID'])) {
							$sessionauto['TemplateID'] = $_POST['TemplateID'];
						}

						IEM::sessionSet('Autoresponders', $sessionauto);

						$this->EditAutoresponderStep4();

					break;

					case 'step3':
						$sessionauto = IEM::sessionGet('Autoresponders');
						$sessionauto['name'] = $_POST['name'];
						$sessionauto['searchcriteria'] = array(
							'emailaddress' => '',
							'format' => '-1',
							'confirmed' => '1',
							'search_options' => array(),
							'customfields' => array()
						);

						if ($_POST['ShowFilteringOptions'] == 1) {
							$sessionauto['searchcriteria']['emailaddress'] = $_POST['emailaddress'];
							$sessionauto['searchcriteria']['format'] = $_POST['format'];
							$sessionauto['searchcriteria']['confirmed'] = $_POST['confirmed'];

							$search_options = (isset($_POST['Search_Options'])) ? $_POST['Search_Options'] : array();
							$sessionauto['searchcriteria']['search_options'] = $search_options;

							$customfields = (isset($_POST['CustomFields'])) ? $_POST['CustomFields'] : array();
							$sessionauto['searchcriteria']['customfields'] = $customfields;

							foreach ($sessionauto['searchcriteria']['customfields'] as $fieldid => $fieldvalue) {
								if (!$fieldvalue) {
									unset($sessionauto['searchcriteria']['customfields'][$fieldid]);
									continue;
								}
							}

							if (isset($_POST['clickedlink']) && isset($_POST['linkid'])) {
								$sessionauto['searchcriteria']['linktype'] = 'clicked';
								if (isset($_POST['linktype']) && $_POST['linktype'] == 'not_clicked') {
									$sessionauto['searchcriteria']['linktype'] = 'not_clicked';
								}

								$sessionauto['searchcriteria']['link'] = $_POST['linkid'];
							}

							if (isset($_POST['openednewsletter']) && isset($_POST['newsletterid'])) {
								$sessionauto['searchcriteria']['opentype'] = 'opened';
								if (isset($_POST['opentype']) && $_POST['opentype'] == 'not_opened') {
									$sessionauto['searchcriteria']['opentype'] = 'not_opened';
								}

								$sessionauto['searchcriteria']['newsletter'] = $_POST['newsletterid'];
							}
						}

						IEM::sessionSet('Autoresponders', $sessionauto);

						$this->EditAutoresponderStep3();
					break;

					case 'step2':
						$listid = 0;
						if (isset($_POST['list'])) {
							$listid = (int)$_POST['list'];
						}

						if (isset($_GET['list'])) {
							$listid = (int)$_GET['list'];
						}

						$auto = array('list' => $listid);

						IEM::sessionSet('Autoresponders', $auto);

						$this->EditAutoresponderStep1();
					break;

					default:
						IEM::sessionRemove('Autoresponders');
						$this->ChooseCreateList();
				}
			break;

			default:
				$this->SetCurrentPage(1);
				$this->ChooseList('Autoresponders', 'step2');
			break;
		}

		if (!in_array($action, $this->SuppressHeaderFooter)) {
			$this->PrintFooter($popup);
		}
	}

	/**
	* ManageAutoresponders
	* Prints a list of autoresponders for the list we're passing in. Sets up the action dropdown list so we can bulk change or bulk delete autoresponders. Checks permissions to see what the user can do.
	*
	* @param Int $listid ListID to get autoresponders for
	*
	* @see ChooseList
	* @see GetPerPage
	* @see GetAPI
	* @see Autoresponder_API::GetAutoresponders
	* @see User_API::HasAccess
	* @see SetupPaging
	*
	* @return Void Doesn't return anything. Prints out the list of autoresponders.
	*/
	function ManageAutoresponders($listid=0)
	{
		$listid = (int)$listid;

		if (!isset($GLOBALS['Message'])) {
			$GLOBALS['Message'] = '';
		}

		if ($listid <= 0 || !$this->CanAccessList($listid)) {
			$this->ChooseList('Autoresponders', 'step2');
			return;
		}

		IEM::sessionRemove('Autoresponders');

		$autodetails = array('list' => $listid);
		IEM::sessionSet('Autoresponders', $autodetails);

		$user = IEM::getCurrentUser();
		$perpage = $this->GetPerPage();

		$DisplayPage = $this->GetCurrentPage();
		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$sortinfo = $this->GetSortDetails();

		$autoresponderapi = $this->GetApi();

		$NumberOfAutoresponders = $autoresponderapi->GetAutoresponders($listid, $sortinfo, true);
		$myautoresponders = $autoresponderapi->GetAutoresponders($listid, $sortinfo, false, $start, $perpage);

		$GLOBALS['SubAction'] = 'SubAction=Step2&list=' . $listid;
		$GLOBALS['Autoresponders_AddButton'] = $this->ParseTemplate('Autoresponder_Create_Button', true, false);

		$GLOBALS['List'] = $listid;

		$this->DisplayCronWarning();

		if ($NumberOfAutoresponders == 0) {
			$GLOBALS['Intro'] = GetLang('AutorespondersManage');
			if ($user->HasAccess('Autoresponders', 'Create')) {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoAutoresponders', GetLang('AutoresponderCreate'));
			} else {
				$GLOBALS['Message'] .= $this->PrintSuccess('NoAutoresponders', GetLang('AutoresponderAssign'));
			}

			$this->ParseTemplate('Autoresponders_Manage_Empty');

			return;
		}

		if ($user->HasAccess('Autoresponders', 'Delete')) {
			$GLOBALS['Option_DeleteAutoresponder'] = '<option value="Delete">' . GetLang('DeleteAutoresponders') . '</option>';
		}

		if ($user->HasAccess('Autoresponders', 'Approve')) {
			$GLOBALS['Option_ActivateAutoresponder'] = '<option value="Approve">' . GetLang('ActivateAutoresponders') . '</option>';
			$GLOBALS['Option_ActivateAutoresponder'] .= '<option value="Disapprove">' . GetLang('DeactivateAutoresponders') . '</option>';
		}

		$GLOBALS['PAGE'] = 'Autoresponders&Action=Step2&list=' . $listid;

		$this->SetupPaging($NumberOfAutoresponders, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging&SubAction=Step2&list=' . $listid;
		$paging = $this->ParseTemplate('Paging', true, false);

		// reset the page for correct links for ordering.
		$GLOBALS['PAGE'] = 'Autoresponders&Action=Step2&list=' . $listid;

		$GLOBALS['list'] = $listid;

		$autoresponder_manage = $this->ParseTemplate('Autoresponders_Manage', true, false);

		$autoresponderdisplay = '';

		$max_email_size = (SENDSTUDIO_EMAILSIZE_MAXIMUM*1024);

		foreach ($myautoresponders as $pos => $autoresponderdetails) {
			$autoresponderid = $autoresponderdetails['autoresponderid'];
			$GLOBALS['Name'] = htmlspecialchars($autoresponderdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['Created'] = $this->PrintDate($autoresponderdetails['createdate']);
			$GLOBALS['Format'] = GetLang('Format_' . $autoresponderapi->GetFormat($autoresponderdetails['format']));
			$GLOBALS['Owner'] = $autoresponderdetails['owner'];
			if ($autoresponderdetails['hoursaftersubscription'] < 1) {
				$GLOBALS['SentWhen'] = GetLang('Immediately');
			} else {
				if ($autoresponderdetails['hoursaftersubscription'] == 1) {
					$GLOBALS['SentWhen'] = GetLang('HoursAfter_One');
				} else {
					$GLOBALS['SentWhen'] = sprintf(GetLang('HoursAfter_Many'), $autoresponderdetails['hoursaftersubscription']);
				}
			}

			if ($autoresponderdetails['pause'] == 0) {
				$GLOBALS['AutoresponderAction']  = '<a href="index.php?Page=Autoresponders&Action=Pause&id=' . $autoresponderid . '&list=' . $listid . '">' . GetLang('Pause') . '</a>';
			} else {
				$GLOBALS['AutoresponderAction']  = '<a href="index.php?Page=Autoresponders&Action=Resume&id=' . $autoresponderid . '&list=' . $listid . '">' . GetLang('Resume') . '</a>';
			}

			$GLOBALS['AutoresponderAction']  .= '&nbsp;&nbsp;<a href="index.php?Page=Autoresponders&Action=View&id=' . $autoresponderid . '" target="_blank">' . GetLang('View') . '</a>';

			if ($user->HasAccess('Autoresponders', 'Edit')) {
				$GLOBALS['AutoresponderAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Autoresponders&Action=Edit&id=' . $autoresponderid . '&list=' . $listid . '">' . GetLang('Edit') . '</a>';
			} else {
				$GLOBALS['AutoresponderAction'] .= $this->DisabledItem('Edit');
			}

			if ($user->HasAccess('Autoresponders', 'Create')) {
				$GLOBALS['AutoresponderAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Autoresponders&Action=Copy&id=' . $autoresponderid . '&list=' . $listid . '">' . GetLang('Copy') . '</a>';
			} else {
				$GLOBALS['AutoresponderAction'] .= $this->DisabledItem('Copy');
			}

			if ($user->HasAccess('Autoresponders', 'Delete')) {
				$GLOBALS['AutoresponderAction'] .= '&nbsp;&nbsp;<a href="javascript:ConfirmDelete(' . $autoresponderid . ');">' . GetLang('Delete') . '</a>';
			} else {
				$GLOBALS['AutoresponderAction'] .= $this->DisabledItem('Delete');
			}

			if ($autoresponderdetails['active'] > 0) {
				$statusaction = 'deactivate';
				$activeicon = 'tick';
				$activetitle = GetLang('Autoresponder_Title_Disable');
			} else {
				$statusaction = 'activate';
				$activeicon = 'cross';
				$activetitle = GetLang('Autoresponder_Title_Enable');
			}
			$GLOBALS['id'] = $autoresponderid;

			$size_prob_found = false;
			if ($max_email_size > 0) {
				if ($autoresponderdetails['autorespondersize'] > $max_email_size) {
					$size_prob_found = true;
					$GLOBALS['ActiveAction'] = '<span title="' . GetLang('Autoresponder_Title_Disable_Too_Big') . '"><img src="images/cross.gif" border="0" alt="' . GetLang('Autoresponder_Title_Disable_Too_Big') . '" onclick="javascript: alert(\'' . GetLang('Autoresponder_Title_Disable_Too_Big_Alert') . '\');"></span>';
				}
			}

			if (!$size_prob_found) {
				if ($user->HasAccess('Autoresponders', 'Approve')) {
					$GLOBALS['ActiveAction'] = '<a href="index.php?Page=Autoresponders&Action=' . $statusaction . '&id=' . $autoresponderid . '&list=' . $listid . '" title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0" alt="' . $activetitle . '"></a>';
				} else {
					$GLOBALS['ActiveAction'] = '<span title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0" alt="' . $activetitle . '"></span>';
				}
			}

			$autoresponderdisplay .= $this->ParseTemplate('Autoresponders_Manage_Row', true, false);
		}
		$autoresponder_manage = str_replace('%%TPL_Autoresponders_Manage_Row%%', $autoresponderdisplay, $autoresponder_manage);
		$autoresponder_manage = str_replace('%%TPL_Paging%%', $paging, $autoresponder_manage);
		$autoresponder_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $autoresponder_manage);

		echo $autoresponder_manage;
	}

	/**
	* EditAutoresponderStep1
	* The first step in creating/editing an autoresponder is the name and any custom field type filters that should be set up based on which list the autoresponder is for.
	*
	* @param Int $autoresponderid Autoresponder to load up. If there is one, it will pre-load that content.
	*
	* @see GetAPI
	* @see DisplayCronWarning
	* @see Autoresponder_API::Load
	* @see List_API::Load
	* @see List_API::GetListFormat
	* @see List_API::GetCustomFields
	* @see Search_Display_CustomField
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function EditAutoresponderStep1($autoresponderid=0)
	{
		$autoapi = $this->GetApi();

		$this->DisplayCronWarning();

		$link_chosen = $newsletter_chosen = false;
		$link_type = 'clicked';
		$newsletter_type = 'opened';

		$custom_search_found = false;

		if ($autoresponderid > 0) {
			$autoapi->Load($autoresponderid);
			$GLOBALS['Action'] = 'Edit&SubAction=Step3';
			$GLOBALS['CancelButton'] = GetLang('EditAutoresponderCancelButton');
			$GLOBALS['Heading'] = GetLang('EditAutoresponder');
			$GLOBALS['Intro'] = GetLang('EditAutoresponderIntro');

			$GLOBALS['Name'] = htmlspecialchars($autoapi->Get('name'), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$criteria = $autoapi->Get('searchcriteria');

			$GLOBALS['emailaddress'] = htmlspecialchars($criteria['emailaddress'], ENT_QUOTES, SENDSTUDIO_CHARSET);

			if ($criteria['emailaddress'] != '') {
				$custom_search_found = true;
			}

			$formatchosen = $criteria['format'];
			if ($formatchosen != '-1') {
				$custom_search_found = true;
			}

			$confirmed = $criteria['confirmed'];
			if ($confirmed != '1') {
				$custom_search_found = true;
			}

			if (isset($criteria['link'])) {
				$link_chosen = $criteria['link'];
				$custom_search_found = true;
			}

			if (isset($criteria['linktype'])) {
				$link_type = $criteria['linktype'];
				$custom_search_found = true;
			}

			if (isset($criteria['newsletter'])) {
				$newsletter_chosen = $criteria['newsletter'];
				$custom_search_found = true;
			}

			if (isset($criteria['opentype'])) {
				$newsletter_type = $criteria['opentype'];
				$custom_search_found = true;
			}

		} else {
			$GLOBALS['Action'] = 'Create&SubAction=Step3';
			$GLOBALS['CancelButton'] = GetLang('CreateAutoresponderCancelButton');
			$GLOBALS['Heading'] = GetLang('CreateAutoresponder');
			$GLOBALS['Intro'] = GetLang('CreateAutoresponderIntro');

			$formatchosen = 'b';
			$confirmed = '1';

			$GLOBALS['DoNotShowFilteringOptions'] = ' CHECKED';
			$GLOBALS['FilteringOptions_Display'] = 'style="display: none;"';
			$GLOBALS['FilteringNext_Display'] = 'style="display:\'\';"';
		}

		// Log this to "User Activity Log"
		// IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/autoresponders_view.gif', $autoapi->name);

		$sessionauto = IEM::sessionGet('Autoresponders');
		$listid = $sessionauto['list'];

		$GLOBALS['List'] = $listid;

		IEM::sessionSet('LinksForList', $listid);

		$GLOBALS['clickedlinkdisplay'] = 'none';

		if ($link_chosen !== false) {
			if ($link_type == 'clicked') {
				$GLOBALS['LinkType_Clicked'] = ' SELECTED';
			} else {
				$GLOBALS['LinkType_NotClicked'] = ' SELECTED';
			}

			$GLOBALS['clickedlink'] = ' CHECKED';
			$GLOBALS['clickedlinkdisplay'] = "'';";
			$GLOBALS['LinkChange'] = 'onClick="enable_ClickedLink(clickedlink, \'clicklink\', \'linkid\', \'' . GetLang('LoadingMessage') . '\', \'' . $link_chosen . '\')"';
			if ($link_chosen == '-1') {
				$GLOBALS['ClickedLinkOptions'] = '<option value="-1" SELECTED>' . GetLang('FilterAnyLink') . '</option>';
			} else {
				$link_url = $autoapi->FetchLink($link_chosen);
				$GLOBALS['ClickedLinkOptions'] = '<option value="' . $link_chosen . '" SELECTED>' . $link_url . '</option>';
			}
		}

		IEM::sessionSet('NewsForList', $listid);

		$GLOBALS['openednewsletterdisplay'] = 'none';

		if ($newsletter_chosen !== false) {
			if ($newsletter_type == 'opened') {
				$GLOBALS['NewsletterType_Opened'] = ' SELECTED';
			} else {
				$GLOBALS['NewsletterType_NotOpened'] = ' SELECTED';
			}

			$GLOBALS['openednewsletter'] = ' CHECKED';
			$GLOBALS['openednewsletterdisplay'] = "'';";
			$GLOBALS['NewsletterChange'] = 'onClick="enable_OpenedNewsletter(openednewsletter, \'openednewsletter\', \'newsletterid\', \'' . GetLang('LoadingMessage') . '\', \'' . $newsletter_chosen . '\')"';
			if ($newsletter_chosen == '-1') {
				$GLOBALS['OpenedNewsletterOptions'] = '<option value="-1" SELECTED>' . GetLang('FilterAnyNewsletter') . '</option>';
			} else {
				$newsletter_api = $this->GetApi('Newsletters');
				$newsletter_api->Load($newsletter_chosen);
				$GLOBALS['OpenedNewsletterOptions'] = '<option value="' . $newsletter_chosen . '" SELECTED>' . $newsletter_api->Get('name') . '</option>';
			}
		}

		$listApi = $this->GetApi('Lists');
		$listApi->Load($listid);

		$format_either = '<option value="-1">' . GetLang('Either_Format') . '</option>';
		$format_html = '<option value="h">' . GetLang('Format_HTML') . '</option>';
		$format_text = '<option value="t">' . GetLang('Format_Text') . '</option>';

		$listformat = $listApi->GetListFormat();

		switch ($listformat) {
			case 'h':
				$format = $format_html;
			break;
			case 't':
				$format = $format_text;
			break;
			default:
				switch ($formatchosen) {
					case 't':
						$format_text = str_replace('"t">', '"t" SELECTED>', $format_text);
					break;
					case 'h':
						$format_html = str_replace('"h">', '"h" SELECTED>', $format_html);
					break;
					case '-1':
						$format_either = str_replace('"-1">', '"-1" SELECTED>', $format_either);
					break;
				}
				$format = $format_either . $format_html . $format_text;
		}
		$GLOBALS['FormatList'] = $format;

		$confirmlist = '';
		$selected = '';
		if ($confirmed == '-1') {
			$selected = ' SELECTED';
		}

		$confirmlist .= '<option value="-1"' . $selected . '>' . GetLang('Either_Confirmed') . '</option>';

		$selected = '';
		if ($confirmed == '1') {
			$selected = ' SELECTED';
		}

		$confirmlist .= '<option value="1"' . $selected . '>' . GetLang('Confirmed') . '</option>';

		$selected = '';
		if ($confirmed == '0') {
			$selected = ' SELECTED';
		}

		$confirmlist .= '<option value="0"' . $selected . '>' . GetLang('Unconfirmed') . '</option>';

		$GLOBALS['ConfirmList'] = $confirmlist;

		$customfields = $listApi->GetCustomFields($listid);

		if (!empty($customfields)) {
			$customfield_display = $this->ParseTemplate('Subscriber_Search_Step2_CustomFields', true, false);
			foreach ($customfields as $pos => $customfield_info) {
				$fieldid = $customfield_info['fieldid'];
				$fieldvalue = '';
				if (isset($criteria['customfields'][$fieldid])) {
					$fieldvalue = $criteria['customfields'][$fieldid];
					$custom_search_found = true;
				}

				if (isset($criteria['search_options']['CustomFields'][$fieldid])) {
					$GLOBALS['CheckboxFilterType_AND'] = '';
					$GLOBALS['CheckboxFilterType_OR'] = '';
					$GLOBALS['CheckboxFilterType_'.$criteria['search_options']['CustomFields'][$fieldid]] = ' SELECTED';
				}

				$customfield_info['FieldValue'] = $fieldvalue;
				$manage_display = $this->Search_Display_CustomField($customfield_info);
				$customfield_display .= $manage_display;
			}
			$GLOBALS['CustomFieldInfo'] = $customfield_display;
		}

		if ($autoresponderid > 0) {
			$GLOBALS['DoNotShowFilteringOptions'] = ' CHECKED';
			$GLOBALS['FilteringOptions_Display'] = 'style="display: none;"';
			$GLOBALS['FilteringNext_Display'] = 'style="display:\'\';"';

			if ($custom_search_found) {
				$GLOBALS['DoNotShowFilteringOptions'] = '';
				$GLOBALS['ShowFilteringOptions'] = ' CHECKED';
				$GLOBALS['FilteringOptions_Display'] = 'style="display: \'\';"';
				$GLOBALS['FilteringNext_Display'] = 'style="display: none;"';
			}
		}

		$this->ParseTemplate('Autoresponder_Form_Step2');
	}

	/**
	* EditAutoresponderStep3
	* This step sets whether the autoresponder is multipart, whether to embed images, which character set to use and so on.
	*
	* @param Int $autoresponderid Autoresponder to load up. If there is one, it will pre-load that content.
	*
	* @return Void Prints out the form, doesn't return anything.
	*/
	function EditAutoresponderStep3($autoresponderid=0)
	{
		$autoresponderapi = $this->GetApi();

		$user = GetUser();

		$sessionauto = IEM::sessionGet('Autoresponders');
		$listid = $sessionauto['list'];

		$GLOBALS['List'] = $listid;

		$this->DisplayCronWarning();

		$GLOBALS['ShowEmbed'] = 0;

		if ($autoresponderid > 0) {
			$GLOBALS['Heading'] = GetLang('EditAutoresponder');
			$GLOBALS['Intro'] = GetLang('EditAutoresponderIntro_Step3');
			$GLOBALS['Action'] = 'Edit&SubAction=Step4&id=' . $autoresponderid;
			$GLOBALS['CancelButton'] = GetLang('EditAutoresponderCancelButton');

			$autoresponderapi->Load($autoresponderid);

			$GLOBALS['SendFromName'] = htmlspecialchars($autoresponderapi->Get('sendfromname'), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['SendFromEmail'] = $autoresponderapi->Get('sendfromemail');
			$GLOBALS['BounceEmail'] = $autoresponderapi->Get('bounceemail');
			$GLOBALS['ReplyToEmail'] = $autoresponderapi->Get('replytoemail');

			$charset = $autoresponderapi->Get('charset');

			$formatoption_chosen = $autoresponderapi->Get('format');
			$GLOBALS['HoursAfterSubscription'] = $autoresponderapi->Get('hoursaftersubscription');

			if ($autoresponderapi->Get('multipart')) {
				$GLOBALS['multipart'] = 'CHECKED';
			}

			$GLOBALS['DisplayEmbedImages'] = 'none';
			if (SENDSTUDIO_ALLOW_EMBEDIMAGES) {
				$GLOBALS['ShowEmbed'] = 1;
				$GLOBALS['DisplayEmbedImages'] = '';
				if ($autoresponderapi->Get('embedimages')) {
					$GLOBALS['embedimages'] = 'CHECKED';
				}
			}

			if ($autoresponderapi->Get('tracklinks')) {
				$GLOBALS['tracklinks'] = 'CHECKED';
			}

			if ($autoresponderapi->Get('trackopens')) {
				$GLOBALS['trackopens'] = 'CHECKED';
			}

			$templateselects = $this->GetTemplateList(false, 15);
			$GLOBALS['TemplateList'] = $templateselects;

			$customfield_to_firstname = $autoresponderapi->Get('to_firstname');
			$customfield_to_lastname = $autoresponderapi->Get('to_lastname');

			$GLOBALS['AutoresponderID'] = $autoresponderid;

		} else {
			$GLOBALS['Heading'] = GetLang('CreateAutoresponder');
			$GLOBALS['Intro'] = GetLang('CreateAutoresponderIntro_Step3');
			$GLOBALS['Action'] = 'Create&SubAction=Step4';
			$GLOBALS['CancelButton'] = GetLang('CreateAutoresponderCancelButton');

			$GLOBALS['HoursAfterSubscription'] = 0;

			$charset = false;

			$formatoption_chosen = 'b';

			$GLOBALS['multipart'] = 'CHECKED';

			$GLOBALS['DisplayEmbedImages'] = 'none';
			if (SENDSTUDIO_ALLOW_EMBEDIMAGES) {
				$GLOBALS['ShowEmbed'] = 1;
				$GLOBALS['DisplayEmbedImages'] = '';
				if (SENDSTUDIO_DEFAULT_EMBEDIMAGES) {
					$GLOBALS['embedimages'] = 'CHECKED';
				}
			}

			$GLOBALS['tracklinks'] = 'CHECKED';
			$GLOBALS['trackopens'] = 'CHECKED';

			$templateselects = $this->GetTemplateList(false, 15);
			$GLOBALS['TemplateList'] = $templateselects;

			$customfield_to_firstname = $customfield_to_lastname = 0;

		}

		$GLOBALS['Charset'] = SENDSTUDIO_CHARSET;

		$list_api = $this->GetApi('Lists');
		$list_api->Load($listid);

		$customfields = $list_api->GetCustomFields($listid);
		if (empty($customfields)) {
			$GLOBALS['DisplayNameOptions'] = 'none';
		} else {
			$GLOBALS['FirstNameOptions'] = '<option value="0">'.GetLang('SelectNameOption').'</option>';
			$GLOBALS['LastNameOptions'] = '<option value="0">'.GetLang('SelectNameOption').'</option>';
			foreach ($customfields as $p => $details) {
				$selected = '';
				if ($details['fieldid'] == $customfield_to_firstname) {
					$selected = " SELECTED";
				}
				$GLOBALS['FirstNameOptions'] .= "<option value='" . $details['fieldid'] . "'" . $selected . ">" . htmlspecialchars($details['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . "</option>";

				$selected = '';
				if ($details['fieldid'] == $customfield_to_lastname) {
					$selected = " SELECTED";
				}
				$GLOBALS['LastNameOptions'] .= "<option value='" . $details['fieldid'] . "'" . $selected . ">" . htmlspecialchars($details['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . "</option>";

			}
		}

		if ($autoresponderid <= 0) {
			$GLOBALS['SendFromName'] = $list_api->ownername;
			$GLOBALS['SendFromEmail'] = $list_api->owneremail;
			$GLOBALS['BounceEmail'] = $list_api->bounceemail;
			$GLOBALS['ReplyToEmail'] = $list_api->replytoemail;
		}

		$GLOBALS['ShowBounceInfo'] = 'none';

		if ($user->HasAccess('Lists', 'BounceSettings')) {
			$GLOBALS['ShowBounceInfo'] = '';
		}

		$listformat = $list_api->GetListFormat();

		switch ($listformat) {
			case 't':
				$format = '<option value="t" SELECTED>' . GetLang('Format_Text') . '</option>';
			break;
			case 'h':
				$format = '<option value="h" SELECTED>' . GetLang('Format_HTML') . '</option>';
			break;
			case 'b':
				$format = '<option value="b"' . (($formatoption_chosen == 'b') ? ' SELECTED' : '' ) . '>' . GetLang('Format_TextAndHTML') . ' ' . GetLang('Recommended') . '</option>';
				$format .= '<option value="h"' . (($formatoption_chosen == 'h') ? ' SELECTED' : '' ) . '>' . GetLang('Format_HTML') . '</option>';
				$format .= '<option value="t"' . (($formatoption_chosen == 't') ? ' SELECTED' : '' ) . '>' . GetLang('Format_Text') . '</option>';
			break;
		}

		$GLOBALS['FormatList'] = $format;

		// Hide the template options if we're editing because they do nothing anyway
		if (isset($_GET['Action'])) {
			if ($_GET['Action'] == 'Edit') {
				$GLOBALS['HideCompleteTemplateList'] = 'none';
			}
		}

		$this->ParseTemplate('Autoresponder_Form_Step3');
	}

	/**
	* EditAutoresponderStep4
	* Loads up step 4 of editing an autoresponder which is editing the actual content.
	* If you pass in an autoresponderid, it will load it up and set the appropriate language variables.
	*
	* @param Int $autoresponderid AutoresponderID to edit.
	*
	* @return Void Prints out step 4, doesn't return anything.
	*/
	function EditAutoresponderStep4($autoresponderid=0)
	{

		$autoapi = $this->GetApi();
		$autorespondercontents = array('text' => '', 'html' => '');

		$this->DisplayCronWarning();

		$user = GetUser();
		$GLOBALS['FromPreviewEmail'] = $user->Get('emailaddress');

		//$GLOBALS['DisplayAttachmentsHeading'] = 'none';
		$tpl = GetTemplateSystem();
		if ($autoresponderid > 0) {
			$GLOBALS['SaveAction'] = 'Edit&SubAction=Save&id=' . $autoresponderid;
			$GLOBALS['Heading'] = GetLang('EditAutoresponder');
			$GLOBALS['Intro'] = GetLang('EditAutoresponderIntro_Step4');
			$GLOBALS['Action'] = 'Edit&SubAction=Complete&id=' . $autoresponderid;
			$GLOBALS['CancelButton'] = GetLang('EditAutoresponderCancelButton');

			$autoapi->Load($autoresponderid);
			$autorespondercontents['text'] = $autoapi->GetBody('text');
			$autorespondercontents['html'] = $autoapi->GetBody('html');

			$GLOBALS['Subject'] = htmlspecialchars($autoapi->subject, ENT_QUOTES, SENDSTUDIO_CHARSET);

		} else {

			$GLOBALS['SaveAction'] = 'Create&SubAction=Save&id=' . $autoresponderid;
			$GLOBALS['Heading'] = GetLang('CreateAutoresponder');
			$GLOBALS['Intro'] = GetLang('CreateAutoresponderIntro_Step4');
			$GLOBALS['Action'] = 'Create&SubAction=Complete';
			$GLOBALS['CancelButton'] = GetLang('CreateAutoresponderCancelButton');
		}

		if (SENDSTUDIO_ALLOW_ATTACHMENTS) {
				$attachmentsarea = strtolower(get_class($this));
				$attachments_list = $this->GetAttachments($attachmentsarea, $autoresponderid);
				$GLOBALS['AttachmentsList'] = $attachments_list;
				$tpl->Assign('ShowAttach', true);
		} else {
			$GLOBALS['DisplayAttachments'] = 'none';
			$user = IEM::getCurrentUser();
			if($user) {
				if ($user->isAdmin()) {
					$GLOBALS['AttachmentsMsg'] = GetLang('NoAttachment_Admin');
				} else {
					$GLOBALS['AttachmentsMsg'] = GetLang('NoAttachment_User');
				}
			}
			$tpl->Assign('ShowAttach', false);
		}

		$GLOBALS['PreviewID'] = $autoresponderid;

		// we don't really need to get/set the stuff here.. we could use references.
		// if we do though, it segfaults! so we get and then set the contents.
		$session_autoresponder = IEM::sessionGet('Autoresponders');

		$GLOBALS['List'] = $session_autoresponder['list'];

		if (isset($session_autoresponder['TemplateID'])) {
			$templateApi = $this->GetApi('Templates');
			if (is_numeric($session_autoresponder['TemplateID'])) {
				$templateApi->Load($session_autoresponder['TemplateID']);
				$autorespondercontents['text'] = $templateApi->textbody;
				$autorespondercontents['html'] = $templateApi->htmlbody;
			} else {
				$autorespondercontents['html'] = $templateApi->ReadServerTemplate($session_autoresponder['TemplateID']);
			}
			unset($session_autoresponder['TemplateID']);
		}

		$session_autoresponder['id'] = (int)$autoresponderid;

		$session_autoresponder['contents'] = $autorespondercontents;

		// we use the lowercase variable when we save, but the editor expects the uppercased version.
		$session_autoresponder['Format'] = $session_autoresponder['format'];

		IEM::sessionSet('Autoresponders', $session_autoresponder);
		$editor = $this->FetchEditor();
		$GLOBALS['Editor'] = $editor;

		unset($session_autoresponder['Format']);
		$GLOBALS['MaxFileSize'] = SENDSTUDIO_ATTACHMENT_SIZE*1024;

		$user = GetUser();
		if ($user->Get('forcespamcheck')) {
			$GLOBALS['ForceSpamCheck'] = 1;
		}

		$tpl->ParseTemplate('Autoresponder_Form_Step4');
	}

	/**
	* ChooseCreateList
	* Prints a list of options to choose from to create an autoresponder.
	* If you only have one list or only have access to one list, you are taken directly to it.
	*
	* @see GetUser
	* @see User_API::GetLists
	* @see GetAPI
	*
	* @return Void Returns nothing, either prints the list or takes you to your only list.
	*/
	function ChooseCreateList()
	{
		$user = GetUser();
		$lists = $user->GetLists();

		$listids = array_keys($lists);

		if (sizeof($listids) < 1) {
			$GLOBALS['Intro'] = GetLang('CreateAutoresponder');
			$GLOBALS['Lists_AddButton'] = '';

			if ($user->CanCreateList() === true) {
				$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListCreate'));
				$GLOBALS['Lists_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
			} else {
				$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListAssign'));
			}
			$this->ParseTemplate('Lists_Manage_Empty');
			return;
		}

		if (sizeof($listids) == 1) {
			$listid = current($listids);
			$location = 'index.php?Page=Autoresponders&Action=Create&SubAction=Step2&list=' . $listid;
			?>
			<script>
				window.location = '<?php echo $location; ?>';
			</script>
			<?php
			exit();
		}

		$this->DisplayCronWarning();

		$selectlist = '';
		foreach ($lists as $listid => $listdetails) {

			$autoresponder_count = '';

			switch ($listdetails['autorespondercount']) {
				case 0:
					$autoresponder_count = GetLang('Autoresponder_Count_None');
				break;
				case 1:
					$autoresponder_count = GetLang('Autoresponder_Count_One');
				break;
				default:
					$autoresponder_count = sprintf(GetLang('Autoresponder_Count_Many'), $this->FormatNumber($listdetails['autorespondercount']));
				break;
			}

			if ($listdetails['subscribecount'] == 1) {
				$subscriber_count = GetLang('Subscriber_Count_One');
			} else {
				$subscriber_count = sprintf(GetLang('Subscriber_Count_Many'), $this->FormatNumber($listdetails['subscribecount']));
			}
			$selectlist .= '<option value="' . $listid . '">' . $listdetails['name'] . $subscriber_count . $autoresponder_count . '</option>';
		}
		$GLOBALS['SelectList'] = $selectlist;

		$this->ParseTemplate('Autoresponders_Create_Step1');
	}

	/**
	* ActionAutoresponders
	* This actions the autoresponders based on the id's passed in and the action you are passing in.
	*
	* @param Array $autoresponderids An array of autoresponderid's to "action".
	* @param String $action The action to perform. This function only accepts approve/disapprove as appropriate actions. Any other type of action will throw an error message.
	*
	* @see GetAPI
	* @see Autoresponders_API::Set
	* @see Autoresponders_API::Load
	* @see Autoresponders_API::Save
	*
	* @return Void Doesn't return anything. Prints out a message about what happened and displays the list of autoresponders again.
	*/
	function ActionAutoresponders($autoresponderids=array(), $action='')
	{
		$listid = (isset($_GET['list'])) ? (int)$_GET['list'] : 0;

		$autoresponderapi = $this->GetApi();

		$autoresponderids = $autoresponderapi->CheckIntVars($autoresponderids);

		if (!is_array($autoresponderids)) {
			$autoresponderids = array($autoresponderids);
		}

		if (empty($autoresponderids)) {
			$GLOBALS['Error'] = GetLang('NoAutorespondersToAction');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);

			$this->ManageAutoresponders($listid);
			return;
		}

		$action = strtolower($action);

		if (!in_array($action, array('approve', 'disapprove'))) {
			$GLOBALS['Error'] = GetLang('InvalidAutoresponderAction');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageAutoresponders($listid);
			return;
		}

		$user = GetUser();

		$max_size = (SENDSTUDIO_EMAILSIZE_MAXIMUM*1024);

		$update_ok = $update_fail = $update_not_done = 0;
		$over_max_size_embed = $over_max_size_no_embed = 0;
		foreach ($autoresponderids as $p => $autoid) {
			$autoresponderapi->Load($autoid);

			$save_autoresponder = true;

			switch ($action) {
				case 'approve':
					if ($max_size > 0) {
						if ($autoresponderapi->Get('autorespondersize') > $max_size) {
							if ($autoresponderapi->Get('embedimages')) {
								$over_max_size_embed++;
							} else {
								$over_max_size_no_embed++;
							}
							$save_autoresponder = false;
							continue;
						}
					}

					$allow_attachments = $this->CheckForAttachments($autoid, 'autoresponders');
					if ($allow_attachments) {
						$langvar = 'Approved';
						$autoresponderapi->Set('active', $user->Get('userid'));
					} else {
						$update_not_done++;
						$save_autoresponder = false;
					}

				break;
				case 'disapprove':
					$langvar = 'Disapproved';
					$autoresponderapi->Set('active', 0);
				break;
			}
			if ($save_autoresponder) {
				$status = $autoresponderapi->Save();
				if ($status) {
					$update_ok++;
				} else {
					$update_fail++;
				}
			}
		}

		$msg = '';

		if ($over_max_size_embed > 0) {
			if ($over_max_size_embed == 1) {
				$GLOBALS['Error'] = sprintf(GetLang('AutoresponderActivateFailed_Over_EmailSize_Maximum_Embed'), $this->EasySize($max_size, 0));
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('AutoresponderActivateFailed_Over_EmailSize_Maximum_Embed_Multiple'), $this->FormatNumber($over_max_size_embed), $this->EasySize($max_size, 0));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($over_max_size_no_embed > 0) {
			if ($over_max_size_no_embed == 1) {
				$GLOBALS['Error'] = sprintf(GetLang('AutoresponderActivateFailed_Over_EmailSize_Maximum'), $this->EasySize($max_size, 0));
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('AutoresponderActivateFailed_Over_EmailSize_Maximum_Multiple'), $this->FormatNumber($over_max_size_no_embed), $this->EasySize($max_size, 0));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($update_not_done > 0) {
			if ($update_not_done == 1) {
				$GLOBALS['Error'] = GetLang('AutoresponderActivateFailed_HasAttachments');
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('AutoresponderActivateFailed_HasAttachments_Multiple'), $this->FormatNumber($update_not_done));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($update_fail > 0) {
			if ($update_fail == 1) {
				$GLOBALS['Error'] = GetLang('Autoresponder_Not' . $langvar);
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Autoresponders_Not' . $langvar), $this->FormatNumber($update_fail));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($update_ok > 0) {
			if ($update_ok == 1) {
				$msg .= $this->PrintSuccess('Autoresponder_' . $langvar);
			} else {
				$msg .= $this->PrintSuccess('Autoresponders_' . $langvar, $this->FormatNumber($update_ok));
			}
		}

		$GLOBALS['Message'] = $msg;

		$this->ManageAutoresponders($listid);
	}

	/**
	* DeleteAutoresponders
	* This goes through the autoresponders and deletes the ones that have been passed in.
	* The API is used to delete and clean up the autoresponders as they need to so we don't need to worry about it.
	*
	* @param Array $autoresponderids An array of autoresponder id's to delete.
	*
	* @see GetAPI
	* @see Autoresponders_API::Delete
	*
	* @return Void Doesn't return anything, prints out the appropriate messages.
	*/
	function DeleteAutoresponders($autoresponderids=array())
	{
		$listid = (isset($_GET['list'])) ? (int)$_GET['list'] : 0;

		$api = $this->GetApi();
		$user = GetUser();

		if (!is_array($autoresponderids)) {
			$autoresponderids = array($autoresponderids);
		}

		$autoresponderids = $api->CheckIntVars($autoresponderids);

		if (empty($autoresponderids)) {
			$GLOBALS['Error'] = GetLang('NoAutorespondersToDelete');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageAutoresponders($listid);
			return;
		}

		$delete_ok = $delete_fail = 0;
		foreach ($autoresponderids as $p => $autoid) {
			$status = $api->Delete($autoid, $user->Get('userid'));
			if ($status) {
				$delete_ok++;
			} else {
				$delete_fail++;
			}
		}

		$msg = '';

		if ($delete_fail > 0) {
			if ($delete_fail == 1) {
				$GLOBALS['Error'] = GetLang('Autoresponder_NotDeleted');
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Autoresponders_NotDeleted'), $this->FormatNumber($delete_fail));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($delete_ok > 0) {
			if ($delete_ok == 1) {
				$msg .= $this->PrintSuccess('Autoresponder_Deleted');
			} else {
				$msg .= $this->PrintSuccess('Autoresponders_Deleted', $this->FormatNumber($delete_ok));
			}
		}
		$GLOBALS['Message'] = $msg;

		$this->ManageAutoresponders($listid);
	}

	/**
	 * CanAccessList
	 * Checks whether the current user can access a particular contact list.
	 *
	 * @param Int $list_id The ID of the contact list.
	 *
	 * @return Boolean True if the user can access the list, otherwise false.
	 */
	function CanAccessList($list_id)
	{
		$user = GetUser();

		if ($user->Admin()) {
			return true;
		}

		$allowed_lists = $user->GetLists();
		if (is_array($allowed_lists)) {
			$allowed_lists = array_keys($allowed_lists);
			if (in_array($list_id, $allowed_lists)) {
				return true;
			}
		}

		return false;
	}
}
