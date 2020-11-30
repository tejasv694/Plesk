<?php

/**
 * This file has the newsletters page in it. This allows you to manage your newsletters, send and so on.
 *
 * @version     $Id: newsletters.php,v 1.77 2008/02/21 00:48:59 chris Exp $
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
 * Class for management of newsletters.
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */
class Newsletters extends SendStudio_Functions {

    /**
     * A list of actions that will have header and footer suppressed
     * @var Array
     */
    var $SuppressHeaderFooter = array('checkspam', 'sendpreview', 'preview', 'viewcompatibility');
    /**
     * ValidSorts
     * An array of sorts you can use with newsletter management.
     *
     * @var Array
     */
    var $ValidSorts = array('name', 'createdate', 'subject', 'owner');

    /**
     * Constructor
     * Loads the language file and adds "sendpreview" to the safe popup window list. This is used by the header/footer to work out which ones to print out.
     *
     * @see LoadLanguageFile
     * @see PrintHeader
     * @see PrintFooter
     *
     * @return Void Doesn't return anything.
     */
    function Newsletters() {
        $this->PopupWindows[] = 'sendpreviewdisplay';
        $this->PopupWindows[] = 'checkspamdisplay';
        $this->PopupWindows[] = 'viewcompatibility';
        $this->LoadLanguageFile();
    }

    /**
     * Process
     * This handles working out what stage you are up to and so on with workflow.
     * It handles creating, editing, deleting, copying etc.
     * It also uses the session to remember what you've done (eg chosen a text newsletter) so it only has to do one update at a time rather than doing everything separately.
     *
     * @see GetUser
     * @see User_API::HasAccess
     * @see PrintHeader
     * @see GetApi
     * @see Newsletter_API::Load
     * @see Newsletter_API::GetBody
     * @see Newsletter_API::Copy
     * @see Newsletter_API::Create
     * @see Newsletter_API::Save
     * @see Newsletter_API::Delete
     * @see ManageNewsletters
     * @see PreviewWindow
     * @see MoveFiles
     * @see CreateNewsletter
     * @see DisplayEditNewsletter
     * @see EditNewsletter
     *
     * Doesn't return anything, handles processing (with the api) and prints out the results.
     */
    function Process() {
        $GLOBALS['Message'] = '';

        $action = (isset($_GET['Action'])) ? strtolower(urldecode($_GET['Action'])) : null;
        $id = (isset($_GET['id'])) ? strtolower(urldecode($_GET['id'])) : null;
        $user = IEM::userGetCurrent();
        $final_action = $action;
        $secondary_actions = array('activate', 'deactivate', 'activatearchive', 'deactivatearchive');
        if (in_array($action, $secondary_actions)) {
            $final_action = 'approve';
        }

        // with 'change' actions, each separate action is checked further on, so we'll just check they can manage anything in this area.
        if (in_array($action, array('change', 'checkspam', 'viewcompatibility', 'processpaging', 'sendpreview', 'preview'))) {
            $final_action = 'manage';
        }

        if(is_null($id)){
            $access = $user->HasAccess('newsletters', $final_action);
        } else {
            $access = $user->HasAccess('newsletters', $final_action, $id);
        }


        $popup = (in_array($action, $this->PopupWindows)) ? true : false;
        if (!in_array($action, $this->SuppressHeaderFooter)) {
            $this->PrintHeader($popup);
        }

        if (!$access && !$popup) {
            $this->DenyAccess();
            return;
        }

        if ($action == 'processpaging') {
            $this->SetPerPage($_GET['PerPageDisplay']);
            $this->ManageNewsletters();
            if (!in_array($action, $this->SuppressHeaderFooter)) {
                $this->PrintFooter($popup);
            }
            exit;
        }

        switch ($action) {
            case 'viewcompatibility':
                $newsletter_info = IEM::sessionGet('Newsletters');

                $html = (isset($_POST['myDevEditControl_html'])) ? $_POST['myDevEditControl_html'] : false;
                $text = (isset($_POST['TextContent'])) ? $_POST['TextContent'] : false;
                $showBroken = isset($_REQUEST['ShowBroken']) && $_REQUEST['ShowBroken'] == 1;
                $details = array();
                $details['htmlcontent'] = $html;
                $details['textcontent'] = $text;
                $details['format'] = $newsletter_info['Format'];

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
            case 'activatearchive':
            case 'deactivatearchive':
                $newsletterapi = $this->GetApi();
                $newsletterapi->Load($id);

                $message = '';

                if ($user->HasAccess('newsletters', 'approve')) {
                    switch ($action) {
                        case 'activatearchive':
                            $newsletterapi->Set('archive', 1);
                            if (!$newsletterapi->Active()) {
                                $GLOBALS['Error'] = GetLang('NewsletterCannotBeInactiveAndArchive');
                                $message .= $this->ParseTemplate('ErrorMsg', true, false);
                            }
                            $message .= $this->PrintSuccess('NewsletterArchive_ActivatedSuccessfully');
                            break;
                        case 'deactivatearchive':
                            $newsletterapi->Set('archive', 0);
                            $message .= $this->PrintWarning('NewsletterArchive_DeactivatedWarning');
                            $message .= $this->PrintSuccess('NewsletterArchive_DeactivatedSuccessfully');
                            break;
                        case 'activate':
                            $allow_attachments = $this->CheckForAttachments($id, 'newsletters');
                            if ($allow_attachments) {
                                $newsletterapi->Set('active', $user->Get('userid'));
                                $message .= $this->PrintSuccess('NewsletterActivatedSuccessfully');
                            } else {
                                $GLOBALS['Error'] = GetLang('NewsletterActivateFailed_HasAttachments');
                                $message .= $this->ParseTemplate('ErrorMsg', true, false);
                            }
                            break;
                        default:
                            $newsletterapi->Set('active', 0);
                            if ($newsletterapi->Archive()) {
                                $GLOBALS['Error'] = GetLang('NewsletterCannotBeInactiveAndArchive');
                                $message .= $this->ParseTemplate('ErrorMsg', true, false);
                            }
                            $message .= $this->PrintSuccess('NewsletterDeactivatedSuccessfully');
                    }
                    $newsletterapi->Save();

                    $GLOBALS['Message'] = $message;
                }
                $this->ManageNewsletters();
                break;

            case 'sendpreviewdisplay':
                $this->SendPreviewDisplay();
                break;

            case 'sendpreview':
                $this->SendPreview();
                break;

            case 'delete':
                $this->DeleteNewsletters($id);
                break;

            case 'view':
                $type = 'newsletters';
                $newsletter = $this->GetApi();
                if (!$newsletter->Load($id)) {
                    break;
                }

                // Log this to "User Activity Log"
                $logURL = SENDSTUDIO_APPLICATION_URL . '/admin/index.php?Page=Newsletters&Action=Edit&id=' . $_GET['id'];
                IEM::logUserActivity($logURL, 'images/newsletters_view.gif', $newsletter->name);

                $details = array();
                $details['htmlcontent'] = $newsletter->GetBody('HTML');
                $details['textcontent'] = $newsletter->GetBody('Text');
                $details['format'] = $newsletter->format;

                $this->PreviewWindow($details);
                exit;
                break;

            case 'preview':
                $type = 'newsletters';
                $newsletter = $this->GetApi();
                if (!$newsletter->Load($id)) {
                    break;
                }

                $details = array();
                $details['htmlcontent'] = $newsletter->GetBody('HTML');
                $details['textcontent'] = $newsletter->GetBody('Text');
                $details['format'] = $newsletter->format;

                $this->PreviewWindow($details, false, $id);
                exit;
                break;

            case 'copy':
                $api = $this->GetApi();
                list($newsletter_result, $files_copied) = $api->Copy($id);
                if (!$newsletter_result) {
                    $GLOBALS['Error'] = GetLang('NewsletterCopyFail');
                    $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
                } else {
                    $changed = false;
                    // check the permissions.
                    // if we can't make archive a newsletter, disable this aspect of it.
                    if (!$user->HasAccess('Newsletters', 'Approve')) {
                        $changed = true;
                        $api->Set('archive', 0);
                    }

                    // if we can't approve newsletters, then make sure we disable it.
                    if (!$user->HasAccess('Newsletters', 'Approve')) {
                        $changed = true;
                        $api->Set('active', 0);
                    }

                    if ($changed) {
                        $api->Save();
                    }
                    $GLOBALS['Message'] = $this->PrintSuccess('NewsletterCopySuccess');
                    if (!$files_copied) {
                        $GLOBALS['Error'] = GetLang('NewsletterFilesCopyFail');
                        $GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
                    }
                }
                $this->ManageNewsletters();
                break;

            case 'edit':
                $newsletter = $this->GetApi();
                $newsletter->Load($id);
                $subaction = (isset($_GET['SubAction'])) ? strtolower(urldecode($_GET['SubAction'])) : '';
                switch ($subaction) {
                    case 'step2':
                        $editnewsletter = array('id' => $id);

                        $checkfields = array('Name', 'Format');
                        $valid = true;
                        $errors = array();
                        foreach ($checkfields as $p => $field) {
                            if (!isset($_POST[$field])) {
                                $valid = false;
                                $errors[] = GetLang('Newsletter' . $field . 'IsNotValid');
                                break;
                            }
                            if ($_POST[$field] == '') {
                                $valid = false;
                                $errors[] = GetLang('Newsletter' . $field . 'IsNotValid');
                                break;
                            } else {
                                $value = $_POST[$field];
                                $editnewsletter[$field] = $value;
                            }
                        }
                        if (!$valid) {
                            $GLOBALS['Error'] = GetLang('UnableToUpdateNewsletter') . '<br/>- ' . implode('<br/>- ', $errors);
                            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
                            $this->EditNewsletter($id);
                            break;
                        }

                        IEM::sessionSet('Newsletters', $editnewsletter);
                        $this->DisplayEditNewsletter($id);
                        break;

                    case 'save':
                    case 'complete':
                        $session_newsletter = IEM::sessionGet('Newsletters');

                        $text_unsubscribelink_found = true;
                        $html_unsubscribelink_found = true;
                        $textcontent = "";
                        $htmlcontent = "";

                        if (isset($_POST['TextContent'])) {
                            $textcontent = $_POST['TextContent'];
                            $newsletter->SetBody('Text', $textcontent);
                            $text_unsubscribelink_found = $this->CheckForUnsubscribeLink($textcontent, 'text');
                            $session_newsletter['contents']['text'] = $textcontent;
                        }

                        if (isset($_POST['myDevEditControl_html'])) {
                            $htmlcontent = $_POST['myDevEditControl_html'];

                            /**
                             * This is an effort not to overwrite the eixsting HTML contents
                             * if there isn't any contents in it (DevEdit will have '<html><body></body></html>' as a minimum
                             * that will be passed to here)
                             */
                            if (trim($htmlcontent) == '') {
                                $GLOBALS['Error'] = GetLang('UnableToUpdateNewsletter');
                                $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
                                $this->DisplayEditNewsletter($id);
                                break;
                            }

                            $newsletter->SetBody('HTML', $htmlcontent);
                            $html_unsubscribelink_found = $this->CheckForUnsubscribeLink($htmlcontent, 'html');
                            $session_newsletter['contents']['html'] = $htmlcontent;
                        }

                        if (isset($_POST['subject'])) {$newsletter->subject = $_POST['subject'];}
                        
                        $newsletter->name = $session_newsletter['Name'];
                        
                        $newsletter->format = $session_newsletter['Format'];

                        $newsletter->active = 0;
                        
                        if ($user->HasAccess('newsletters', 'approve')) {
                            if (isset($_POST['active'])) {
                                $newsletter->active = $user->userid;
                            }
                        }
                        
                        $newsletter->archive = 0;

                        if (isset($_POST['archive'])) {
                            $newsletter->archive = 1;
                        }
                    
                        $dest = 'newsletters';

                        if (!empty($textcontent)) {
                            $textcontent = $this->ConvertContent($textcontent, $dest, $id);
                            $newsletter->SetBody('Text', $textcontent);
                        }
                        if (!empty($htmlcontent)) {
                            $htmlcontent = $this->ConvertContent($htmlcontent, $dest, $id);
                            $newsletter->SetBody('HTML', $htmlcontent);
                        }


                        $result = $newsletter->Save();

                        if (!$result) {
                            $GLOBALS['Error'] = GetLang('UnableToUpdateNewsletter');
                            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
                            $this->ManageNewsletters();
                            break;
                        }

                        $newsletter_info = $session_newsletter;
                        $newsletter_info['embedimages'] = true;
                        $newsletter_info['multipart'] = true;

                        list($newsletter_size, $newsletter_img_warnings) = $this->GetSize($newsletter_info);

                        if (SENDSTUDIO_ALLOW_EMBEDIMAGES) {
                            $size_message = GetLang('Newsletter_Size_Approximate');
                        } else {
                            $size_message = GetLang('Newsletter_Size_Approximate_Noimages');
                        }
                        $GLOBALS['Message'] = $this->PrintSuccess('NewsletterUpdated', sprintf($size_message, $this->EasySize($newsletter_size)));

                        if (SENDSTUDIO_EMAILSIZE_WARNING > 0) {
                            $warning_size = SENDSTUDIO_EMAILSIZE_WARNING * 1024;
                            if ($newsletter_size > $warning_size) {
                                $GLOBALS['Message'] .= $this->PrintWarning('Newsletter_Size_Over_EmailSize_Warning', $this->EasySize($warning_size));
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
                                $GLOBALS['Error'] = $attachments_status_msg;
                                $GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
                            }
                        }

                        if (!$newsletter->Active() && isset($_POST['archive'])) {
                            $GLOBALS['Error'] = GetLang('NewsletterCannotBeInactiveAndArchive');
                            $GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
                        }

                        if ($newsletter_img_warnings) {
                            $GLOBALS['Message'] .= $this->PrintWarning('UnableToLoadImage_Newsletter_List', $newsletter_img_warnings);
                        }

                        if (!$html_unsubscribelink_found) {
                            $GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInHTMLContent');
                        }

                        if (!$text_unsubscribelink_found) {
                            $GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInTextContent');
                        }

                        $GLOBALS['Message'] = str_replace('<br><br>', '<br>', $GLOBALS['Message']);
                        
                        if ($subaction == 'save') {
                            $this->DisplayEditNewsletter($id);
                        } else {
                            IEM::sessionRemove("Newsletters");
                            IEM::sessionSet('Newsletters_editing['.$user->Get('userid').']',$GLOBALS['Message']);
                            IEM::redirectTo("Newsletters",array("Action" => "Manage"));
                        }
                        
                        break;

                    default:
                    case 'step1':
                        $this->EditNewsletter($id);
                        break;
                }
                break;

            case 'create':
                $subaction = (isset($_GET['SubAction'])) ? strtolower(urldecode($_GET['SubAction'])) : '';
                switch ($subaction) {
                    default:
                        $this->CreateNewsletter(); 
                    break;
                    case 'step2':
                        IEM::sessionSet('Newsletters_creation['.$user->Get('userid').']', true);
                        $newnewsletter = array();
                        $checkfields = array('Name', 'Format');
                        $valid = true;
                        $errors = array();
                        foreach ($checkfields as $p => $field) {
                            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                                $valid = false;
                                $errors[] = GetLang('Newsletter' . $field . 'IsNotValid');
                                break;
                            } else {
                                $value = $_POST[$field];
                                $newnewsletter[$field] = $value;
                            }
                        }
                        if (!$valid) {
                            $GLOBALS['Error'] = GetLang('UnableToCreateNewsletter') . '<br/>- ' . implode('<br/>- ', $errors);
                            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
                            IEM::sessionRemove('Newsletters_creation['.$user->Get('userid').']');
                            $this->CreateNewsletter();
                            break;
                        }
                        if (isset($_POST['TemplateID'])) {
                            $newnewsletter['TemplateID'] = $_POST['TemplateID'];
                        }
                        IEM::sessionSet('Newsletters', $newnewsletter);
                        $this->DisplayEditNewsletter();
                        break;

                    case 'save':
                    case 'complete':
                        $session_newsletter = IEM::sessionGet('Newsletters');

                        $newnewsletter = $this->GetApi();

                        $text_unsubscribelink_found = true;
                        $html_unsubscribelink_found = true;

                        if (isset($_POST['TextContent'])) {
                            $textcontent = $_POST['TextContent'];
                            $newnewsletter->SetBody('Text', $textcontent);
                            $text_unsubscribelink_found = $this->CheckForUnsubscribeLink($textcontent, 'text');
                            $session_newsletter['contents']['text'] = $textcontent;
                        }

                        if (isset($_POST['myDevEditControl_html'])) {
                            $htmlcontent = $_POST['myDevEditControl_html'];
                            $newnewsletter->SetBody('HTML', $htmlcontent);
                            $html_unsubscribelink_found = $this->CheckForUnsubscribeLink($htmlcontent, 'html');
                            $session_newsletter['contents']['html'] = $htmlcontent;
                        }

                        if (isset($_POST['subject'])) {
                            $newnewsletter->subject = $_POST['subject'];
                        }
                        
                        $newnewsletter->name = $session_newsletter['Name'];
                        $newnewsletter->format = $session_newsletter['Format'];
                        $newnewsletter->active = 0;
                        
                        if ($user->HasAccess('newsletters', 'approve')) {
                            if (isset($_POST['active'])) {
                                $newnewsletter->active = $user->userid;
                            }
                        }
                        
                        $newnewsletter->archive = 0;
                        if (isset($_POST['archive'])) {
                            $newnewsletter->archive = 1;
                        }

                        $newnewsletter->ownerid = $user->userid;
                        $result = $newnewsletter->Create();

                        if (!$result) {
                            $GLOBALS['Error'] = GetLang('UnableToCreateNewsletter');
                            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
                            IEM::sessionRemove('Newsletter_creation['.$user->Get('userid').']');                                                        
                            $this->ManageNewsletters();
                            break;
                        }

                        $newsletter_info = $session_newsletter;
                        $newsletter_info['embedimages'] = true;
                        $newsletter_info['multipart'] = true;

                        list($newsletter_size, $newsletter_img_warnings) = $this->GetSize($newsletter_info);

                        if (SENDSTUDIO_ALLOW_EMBEDIMAGES) {
                            $size_message = GetLang('Newsletter_Size_Approximate');
                        } else {
                            $size_message = GetLang('Newsletter_Size_Approximate_Noimages');
                        }
                        $GLOBALS['Message'] = $this->PrintSuccess('NewsletterCreated', sprintf($size_message, $this->EasySize($newsletter_size)));

                        if (SENDSTUDIO_EMAILSIZE_WARNING > 0) {
                            $warning_size = SENDSTUDIO_EMAILSIZE_WARNING * 1024;
                            if ($newsletter_size > $warning_size) {
                                $GLOBALS['Message'] .= $this->PrintWarning('Newsletter_Size_Over_EmailSize_Warning', $this->EasySize($warning_size));
                            }
                        }

                        $dest = 'newsletters';

                        $movefiles_result = $this->MoveFiles($dest, $result);

                        if ($movefiles_result) {
                            if (isset($textcontent)) {
                                $textcontent = $this->ConvertContent($textcontent, $dest, $result);
                                $newnewsletter->SetBody('Text', $textcontent);
                            }
                            if (isset($htmlcontent)) {
                                $htmlcontent = $this->ConvertContent($htmlcontent, $dest, $result);
                                $newnewsletter->SetBody('HTML', $htmlcontent);
                            }
                        } else {
                            $GLOBALS['Error'] = "Unable to move images to final location.";
                            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
                            $this->DisplayEditNewsletter($result);
                            break;
                        }
                        
                        $newnewsletter->Save();
                        
                        if (SENDSTUDIO_ALLOW_ATTACHMENTS) {
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
                        if (!$newnewsletter->Active() && isset($_POST['archive'])) {
                            $GLOBALS['Error'] = GetLang('NewsletterCannotBeInactiveAndArchive');
                            $GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
                        }

                        if ($newsletter_img_warnings) {
                            $GLOBALS['Message'] .= $this->PrintWarning('UnableToLoadImage_Newsletter_List', $newsletter_img_warnings);
                        }

                        if (!$html_unsubscribelink_found) {
                            $GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInHTMLContent');
                        }

                        if (!$text_unsubscribelink_found) {
                            $GLOBALS['Message'] .= $this->PrintWarning('NoUnsubscribeLinkInTextContent');
                        }

                        $GLOBALS['Message'] = str_replace('<br><br>', '<br>', $GLOBALS['Message']);
                        
                        if(is_dir(TEMP_DIRECTORY . "/newsletters/".$user->userid."_tmp")){remove_directory(TEMP_DIRECTORY . "/newsletters/".$user->userid."_tmp");}
                        
                        if ($subaction == 'save') {
                            $this->DisplayEditNewsletter($result);
                        } else {
                            IEM::sessionRemove("Newsletters");
                            IEM::sessionRemove('Newsletters_creation['.$user->Get('userid').']');
                            IEM::sessionSet('Newsletters_creation['.$user->Get('userid').']',$GLOBALS['Message']);
                            IEM::redirectTo("Newsletters",array("Action" => "Manage"));
                        }
                        break;
                }
                break;

            case 'change':
                $subaction = strtolower($_POST['ChangeType']);
                $newsletterlist = $_POST['newsletters'];

                switch ($subaction) {
                    case 'delete':
                        $access = $user->HasAccess('Newsletters', 'Delete');
                        if ($access) {
                            $this->DeleteNewsletters($newsletterlist);
                        } else {
                            $this->DenyAccess();
                        }
                        break;

                    case 'approve':
                    case 'disapprove':
                        $access = $user->HasAccess('Newsletters', 'Approve');
                        if ($access) {
                            $this->ActionNewsletters($newsletterlist, $subaction);
                        } else {
                            $this->DenyAccess();
                        }
                        break;

                    case 'archive':
                    case 'unarchive':
                        $access = $user->HasAccess('Newsletters', 'Edit');
                        if ($access) {
                            $this->ActionNewsletters($newsletterlist, $subaction);
                        } else {
                            $this->DenyAccess();
                        }
                        break;
                }
                break;

            default:
                $this->ManageNewsletters();
                break;
        }

        if (!in_array($action, $this->SuppressHeaderFooter)) {
            $this->PrintFooter($popup);
        }
    }

    /**
     * ManageNewsletters
     * Prints out the newsletters for management. Depending on your access levels you can edit, delete, send, schedule and so on.
     *
     * @see GetPerPage
     * @see GetSortDetails
     * @see GetApi
     * @see User_API::Admin
     * @see Newsletter_API::GetNewsletters
     * @see SetupPaging
     * @see PrintDate
     * @see User_API::HasWriteAccess
     * @see Jobs_API::FindJob
     *
     * @return Void Doesn't return anything, just prints out the results and that's it.
     *
     * @uses EventData_IEM_NEWSLETTERS_MANAGENEWSLETTERS
     */
    function ManageNewsletters() {        
        $user = GetUser();
        
        $remove_temp_dir = IEM::sessionGet('Newsletters_creation['.$user->Get('userid').']');
        IEM::sessionSet('Newsletters_creation['.$user->Get('userid').']',null);
        IEM::sessionRemove('Newsletter_creation['.$user->Get('userid').']');
        if(!empty($remove_temp_dir)){
            $d_path = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . "newsletters" . DIRECTORY_SEPARATOR . $user->Get('userid')."_tmp";
            if(is_dir($d_path)){remove_directory($d_path);}
            $GLOBALS['Message'] = $remove_temp_dir;    
        }

        $edit_msg = IEM::sessionGet('Newsletters_editing['.$user->Get('userid').']');
        IEM::sessionSet('Newsletters_editing['.$user->Get('userid').']',null);
        IEM::sessionRemove('Newsletters_editing['.$user->Get('userid').']');
        if(!empty($edit_msg)){
            $GLOBALS['Message'] = $edit_msg;    
        }        

        $delete_msg = IEM::sessionGet('Newsletters_deletion['.$user->Get('userid').']');
        IEM::sessionSet('Newsletters_deletion['.$user->Get('userid').']',null);
        IEM::sessionRemove('Newsletters_deletion['.$user->Get('userid').']');
        if(!empty($delete_msg)){
            $GLOBALS['Message'] = $delete_msg;    
        }         
        $perpage = $this->GetPerPage();

        $DisplayPage = $this->GetCurrentPage();
        $start = 0;
        if ($perpage != 'all') {
            $start = ($DisplayPage - 1) * $perpage;
        }

        $sortinfo = $this->GetSortDetails();

        $newsletterapi = $this->GetApi();

        $newsletterowner = ($user->Admin() || $user->AdminType() == 'n') ? 0 : $user->userid;
        $NumberOfNewsletters = $newsletterapi->GetNewsletters($newsletterowner, $sortinfo, true);
        $mynewsletters = $newsletterapi->GetNewsletters($newsletterowner, $sortinfo, false, $start, $perpage, true);

        if ($user->HasAccess('Newsletters', 'Create')) {
            $GLOBALS['Newsletters_AddButton'] = $this->ParseTemplate('Newsletter_Create_Button', true, false);
            $GLOBALS['Newsletters_Heading'] = GetLang('Help_NewslettersManage_HasAccess');
        }

        if (!isset($GLOBALS['Message'])) {
            $GLOBALS['Message'] = '';
        }

        /**
         * Trigger event
         */
        $tempEventData = new EventData_IEM_NEWSLETTERS_MANAGENEWSLETTERS();
        $tempEventData->displaymessage = &$GLOBALS['Message'];
        $tempEventData->trigger();

        unset($tempEventData);
        /**
         * -----
         */
        if ($NumberOfNewsletters == 0) {
            if ($user->HasAccess('Newsletters', 'Create')) {
                $GLOBALS['Message'] .= $this->PrintSuccess('NoNewsletters', GetLang('NoNewsletters_HasAccess'));
            } else {
                $GLOBALS['Message'] .= $this->PrintSuccess('NoNewsletters', '');
            }
            $this->ParseTemplate('Newsletters_Manage_Empty');
            return;
        }

        $this->SetupPaging($NumberOfNewsletters, $DisplayPage, $perpage);
        $GLOBALS['FormAction'] = 'Action=ProcessPaging';
        $paging = $this->ParseTemplate('Paging', true, false);

        if ($user->HasAccess('Newsletters', 'Delete')) {
            $GLOBALS['Option_DeleteNewsletter'] = '<option value="Delete">' . GetLang('Delete') . '</option>';
        }

        if ($user->HasAccess('Newsletters', 'Approve')) {
            $GLOBALS['Option_ActivateNewsletter'] = '<option value="Approve">' . GetLang('ApproveNewsletters') . '</option>';
            $GLOBALS['Option_ActivateNewsletter'] .= '<option value="Disapprove">' . GetLang('DisapproveNewsletters') . '</option>';
            $GLOBALS['Option_ArchiveNewsletter'] = '<option value="Archive">' . GetLang('ArchiveNewsletters') . '</option>';
            $GLOBALS['Option_ArchiveNewsletter'] .= '<option value="Unarchive">' . GetLang('UnarchiveNewsletters') . '</option>';
        }

        $newsletter_manage = $this->ParseTemplate('Newsletters_Manage', true, false);

        $newsletterdisplay = '';

        $jobapi = $this->GetApi('Jobs');

        foreach ($mynewsletters as $pos => $newsletterdetails) {
            $newsletterid = $newsletterdetails['newsletterid'];
            $GLOBALS['Name'] = htmlspecialchars($newsletterdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
            $GLOBALS['Short_Name'] = htmlspecialchars($this->TruncateName($newsletterdetails['name'], 34), ENT_QUOTES, SENDSTUDIO_CHARSET);

            $GLOBALS['Created'] = $this->PrintDate($newsletterdetails['createdate']);
            $GLOBALS['Format'] = GetLang('Format_' . $newsletterapi->GetFormat($newsletterdetails['format']));
            $GLOBALS['Owner'] = $newsletterdetails['owner'];

            $GLOBALS['Subject'] = htmlspecialchars($newsletterdetails['subject'], ENT_QUOTES, SENDSTUDIO_CHARSET);
            $GLOBALS['Short_Subject'] = htmlspecialchars($this->TruncateName($newsletterdetails['subject'], 37), ENT_QUOTES, SENDSTUDIO_CHARSET);

            $GLOBALS['id'] = $newsletterid;

            $GLOBALS['NewsletterIcon'] = '<img src="images/m_newsletters.gif">';

            $GLOBALS['NewsletterAction'] = '<a href="index.php?Page=Newsletters&Action=View&id=' . $newsletterid . '" target="_blank">' . GetLang('View') . '</a>';

            $send_inprogress = false;
            $send_fully_completed = true;

            $job = false;
            if ($newsletterdetails['jobid'] > 0) {
                $job = $jobapi->LoadJob($newsletterdetails['jobid']);
            }

            $GLOBALS['LastSentTip'] = $GLOBALS['LastSentTip_Extra'] = $GLOBALS['Job'] = '';

            if ($newsletterdetails['starttime'] > 0) {
                $GLOBALS['LastSent'] = $this->PrintDate($newsletterdetails['starttime']);

                $GLOBALS['TipName'] = $this->GetRandomId();

                if ($newsletterdetails['finishtime'] > 0) {
                    $GLOBALS['LastSentTip'] = sprintf(GetLang('AlreadySentTo'), $this->FormatNumber($newsletterdetails['total_recipients']), $this->FormatNumber($newsletterdetails['sendsize']));
                    if ($newsletterdetails['total_recipients'] < $newsletterdetails['sendsize'] && $job) {
                        $send_fully_completed = false;
                        $GLOBALS['ResendTipName'] = $this->GetRandomId();
                        $GLOBALS['Job'] = $job['jobid'];
                        if ($job['resendcount'] < SENDSTUDIO_RESEND_MAXIMUM) {
                            $GLOBALS['NewsletterIcon'] = $this->ParseTemplate('Newsletters_Send_Resend_Tip', true, false);
                            $GLOBALS['LastSentTip_Extra'] = GetLang('AlreadySentTo_Partial');
                        }
                    }
                } else {
                    $GLOBALS['LastSentTip'] = sprintf(GetLang('AlreadySentTo_SoFar'), $this->FormatNumber($newsletterdetails['total_recipients']), $this->FormatNumber($newsletterdetails['sendsize']));
                }

                $already_sent_tip = $this->ParseTemplate('Newsletters_Send_Tip', true, false);

                $GLOBALS['LastSent'] = $already_sent_tip;
            } else {
                $GLOBALS['LastSent'] = GetLang('NotSent');
            }

            if ($user->HasAccess('Newsletters', 'Send')) {
                if ($newsletterdetails['active']) {
                    if (!$job || empty($job)) {
                        $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Send&id=' . $newsletterid . '">' . GetLang('Send') . '</a>';
                    } else {
                        $jobstate = $jobapi->GetJobStatus($job['jobstatus']);
                        switch ($job['jobstatus']) {
                            case 'i':
                                $send_inprogress = true;
                                if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SEND > 0) {
                                    $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Schedule">' . $jobstate . '</a>';
                                } else {
                                    $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Send&Action=PauseSend&Job=' . $job['jobid'] . '">' . $jobstate . '</a>';
                                }
                                break;
                            case 'p':
                                if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SEND > 0) {
                                    $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Schedule">' . $jobstate . '</a>';
                                } else {
                                    $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Send&Action=ResumeSend&Job=' . $job['jobid'] . '">' . $jobstate . '</a>';
                                }
                                break;
                            case 'w':
                                // this is only applicable for scheduled newsletters (waiting to send).
                                $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Schedule">' . GetLang('Waiting') . '</a>';
                                break;
                            default:
                                if ($send_fully_completed) {
                                    $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Send&id=' . $newsletterid . '">' . GetLang('Send') . '</a>';
                                } else {
                                    if ($job['resendcount'] < SENDSTUDIO_RESEND_MAXIMUM) {
                                        $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Send&Action=Resend&Job=' . $job['jobid'] . '">' . GetLang('Resend') . '</a>';
                                    } else {
                                        $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Resend', 'Newsletter_Send_Disabled_Resend_Maximum');
                                    }
                                }
                        }
                    }
                } else {
                    $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Send', 'Newsletter_Send_Disabled_Inactive');
                }
            } else {
                $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Send');
            }

            if ($user->HasAccess('Newsletters', 'Edit')) {
                if (!$send_inprogress) {
                    $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Newsletters&Action=Edit&id=' . $newsletterid . '">' . GetLang('Edit') . '</a>';
                } else {
                    $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Edit', 'Newsletter_Edit_Disabled_SendInProgress');
                }
            } else {
                $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Edit');
            }

            if ($user->HasAccess('Newsletters', 'Create')) {
                $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="index.php?Page=Newsletters&Action=Copy&id=' . $newsletterid . '">' . GetLang('Copy') . '</a>';
            } else {
                $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Copy');
            }

            if ($user->HasAccess('Newsletters', 'Delete')) {
                if (!$send_inprogress) {
                    $GLOBALS['NewsletterAction'] .= '&nbsp;&nbsp;<a href="javascript: ConfirmDelete(' . $newsletterid . ');">' . GetLang('Delete') . '</a>';
                } else {
                    $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Delete', 'Newsletter_Delete_Disabled_SendInProgress');
                }
            } else {
                $GLOBALS['NewsletterAction'] .= $this->DisabledItem('Delete');
            }

            if ($newsletterdetails['active'] > 0) {
                $statusaction = 'deactivate';
                $activeicon = 'tick';
                if ($user->HasAccess('Newsletters', 'Approve')) {
                    $activetitle = GetLang('Newsletter_Title_Disable');
                } else {
                    $activetitle = GetLang('NoAccess');
                }
            } else {
                $statusaction = 'activate';
                $activeicon = 'cross';
                if ($user->HasAccess('Newsletters', 'Approve')) {
                    $activetitle = GetLang('Newsletter_Title_Enable');
                } else {
                    $activetitle = GetLang('NoAccess');
                }
            }

            if ($user->HasAccess('Newsletters', 'Approve')) {
                if (!$send_inprogress) {
                    $GLOBALS['ActiveAction'] = '<a href="index.php?Page=Newsletters&Action=' . $statusaction . '&id=' . $newsletterid . '" title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0"></a>';
                } else {
                    $activetitle = GetLang('Newsletter_ChangeActive_Disabled_SendInProgress');
                    $GLOBALS['ActiveAction'] = '<span title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0"></span>';
                }
            } else {
                $GLOBALS['ActiveAction'] = '<span title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0"></span>';
            }

            if ($newsletterdetails['archive'] > 0) {
                $statusaction = 'deactivatearchive';
                $activeicon = 'tick';
                $activetitle = GetLang('Newsletter_Title_Archive_Disable');
            } else {
                $statusaction = 'activatearchive';
                $activeicon = 'cross';
                $activetitle = GetLang('Newsletter_Title_Archive_Enable');
            }

            if ($user->HasAccess('Newsletters', 'Approve')) {
                $GLOBALS['ArchiveAction'] = '<a href="index.php?Page=Newsletters&Action=' . $statusaction . '&id=' . $newsletterid . '" title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0"></a>';
            } else {
                $GLOBALS['ArchiveAction'] = '<span title="' . $activetitle . '"><img src="images/' . $activeicon . '.gif" border="0"></span>';
            }

            $newsletterdisplay .= $this->ParseTemplate('Newsletters_Manage_Row', true, false);
        }
        $newsletter_manage = str_replace('%%TPL_Newsletters_Manage_Row%%', $newsletterdisplay, $newsletter_manage);
        $newsletter_manage = str_replace('%%TPL_Paging%%', $paging, $newsletter_manage);
        $newsletter_manage = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $newsletter_manage);

        echo $newsletter_manage;
    }

    /**
     * ActionNewsletters
     * Actions newsletters based on the action passed in. This can be activate, inactivate, enable archiving or disable archiving.
     *
     * @param Array $newsletterids An array of newsletter id's to action. If it's a single item, it's turned into an array for easy processing.
     * @param String $action The action to perform. Can be approve, disapprove, acthive, unarchive. Anything else is dropped.
     *
     * @see ManageNewsletters
     * @see GetUser
     * @see GetApi
     * @see Newsletters_API::Set
     * @see Newsletters_API::Load
     * @see Newsletters_API::Save
     * @see FormatNumber
     *
     * @return Void Prints out a message based on what happened and then prints out the list of newsletters again.
     */
    function ActionNewsletters($newsletterids=array(), $action='') {
        if (!is_array($newsletterids)) {
            $newsletterids = array($newsletterids);
        }

        if (empty($newsletterids)) {
            $GLOBALS['Error'] = GetLang('NoNewslettersToAction');
            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
            $this->ManageNewsletters();
            return;
        }

        $action = strtolower($action);

        if (!in_array($action, array('approve', 'disapprove', 'archive', 'unarchive'))) {
            $GLOBALS['Error'] = GetLang('InvalidNewsletterAction');
            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
            $this->ManageNewsletters();
            return;
        }

        $user = GetUser();

        $newsletterapi = $this->GetApi();

        $update_ok = $update_fail = $update_not_done = 0;
        foreach ($newsletterids as $p => $newsletterid) {
            $newsletterapi->Load($newsletterid);

            $save_newsletter = true;

            switch ($action) {
                case 'approve':
                    $allow_attachments = $this->CheckForAttachments($newsletterid, 'newsletters');
                    if ($allow_attachments) {
                        $langvar = 'Approved';
                        $newsletterapi->Set('active', $user->Get('userid'));
                    } else {
                        $update_not_done++;
                        $save_newsletter = false;
                    }

                    break;
                case 'disapprove':
                    $langvar = 'Disapproved';
                    $newsletterapi->Set('active', 0);
                    break;
                case 'archive':
                    $langvar = 'Archived';
                    $newsletterapi->Set('archive', 1);
                    break;
                case 'unarchive':
                    $langvar = 'Unarchived';
                    $newsletterapi->Set('archive', 0);
                    break;
            }
            if ($save_newsletter) {
                $status = $newsletterapi->Save();
                if ($status) {
                    $update_ok++;
                } else {
                    $update_fail++;
                }
            }
        }

        $msg = '';

        if ($update_not_done > 0) {
            if ($update_not_done == 1) {
                $GLOBALS['Error'] = GetLang('NewsletterActivateFailed_HasAttachments');
            } else {
                $GLOBALS['Error'] = sprintf(GetLang('NewsletterActivateFailed_HasAttachments_Multiple'), $this->FormatNumber($update_not_done));
            }
            $msg .= $this->ParseTemplate('ErrorMsg', true, false);
        }

        if ($update_fail > 0) {
            if ($update_fail == 1) {
                $GLOBALS['Error'] = GetLang('Newsletter_Not' . $langvar);
            } else {
                $GLOBALS['Error'] = sprintf(GetLang('Newsletters_Not' . $langvar), $this->FormatNumber($update_fail));
            }
            $msg .= $this->ParseTemplate('ErrorMsg', true, false);
        }

        if ($update_ok > 0) {
            if ($update_ok == 1) {
                $msg .= $this->PrintSuccess('Newsletter_' . $langvar);
            } else {
                $msg .= $this->PrintSuccess('Newsletters_' . $langvar, $this->FormatNumber($update_ok));
            }
        }

        $GLOBALS['Message'] = $msg;

        $this->ManageNewsletters();
    }

    /**
     * DeleteNewsletters
     * Deletes a list of newsletter id's passed in.
     *
     * @param Array $newsletterids An array of newsletters you want to delete. If it's a single id, it's turned into an array for easy processing.
     *
     * @see GetApi
     * @see Newsletters_API::Delete
     * @see ManageNewsletters
     *
     * @return Void Doesn't return anything. Prints out a message based on what happened and prints out the list of newsletters again.
     */
    function DeleteNewsletters($newsletterids=array()) {
        if (!is_array($newsletterids)) {
            $newsletterids = array($newsletterids);
        }

        $api = $this->GetApi();
        $jobapi = $this->GetApi('Jobs');

        $newsletterids = $api->CheckIntVars($newsletterids);

        if (empty($newsletterids)) {
            $GLOBALS['Error'] = GetLang('NoNewslettersToDelete');
            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
            $this->ManageNewletters();
            return;
        }

        $user = GetUser();

        $sends_in_progress = array();
        $delete_ok = $delete_fail = 0;
        foreach ($newsletterids as $p => $newsletterid) {

            $job = $jobapi->FindJob('send', 'newsletter', $newsletterid);
            if ($job) {
                if ($job['jobstatus'] == 'i') {
                    $api->Load($newsletterid);
                    $newsletter_name = $api->Get('name');
                    $sends_in_progress[] = $newsletter_name;
                    continue;
                }
                $jobapi->Delete($job['jobid']);

                while ($job = $jobapi->FindJob('send', 'newsletter', $newsletterid)) {
                    if ($job['jobstatus'] == 'i') {
                        $api->Load($newsletterid);
                        $newsletter_name = $api->Get('name');
                        $sends_in_progress[] = $newsletter_name;
                        break;
                    }
                    $jobapi->Delete($job['jobid']);
                }
            }

            $status = $api->Delete($newsletterid, $user->Get('userid'));
            if ($status) {
                $delete_ok++;
            } else {
                $delete_fail++;
            }
        }

        $msg = '';

        if (!empty($sends_in_progress)) {
            if (sizeof($sends_in_progress) == 1) {
                $GLOBALS['Error'] = sprintf(GetLang('Newsletter_NotDeleted_SendInProgress'), current($sends_in_progress));
            } else {
                $GLOBALS['Error'] = sprintf(GetLang('Newsletters_NotDeleted_SendInProgress'), implode('\',\'', $sends_in_progress));
            }
            $msg .= $this->ParseTemplate('ErrorMsg', true, false);
        }

        if ($delete_ok > 0) {
            if ($delete_ok == 1) {
                $msg .= $this->PrintSuccess('Newsletter_Deleted');
            } else {
                $msg .= $this->PrintSuccess('Newsletters_Deleted', $this->FormatNumber($delete_ok));
            }
        }
        $GLOBALS['Message'] = $msg;

        IEM::sessionSet('Newsletters_deletion['.$user->Get('userid').']',$GLOBALS['Message']);
        IEM::redirectTo("Newsletters",array("Action" => "Manage"));
    }

    /**
     * EditNewsletter
     * Loads up stage 1 of editing a newsletter (choosing format, templates etc).
     *
     * @param Int $newsletterid Newsletter to load up.
     *
     * @see GetApi
     * @see GetTemplateList
     * @see Newsletter_API::Load
     * @see Newsletter_API::GetAllFormats
     *
     * @return Void Prints out the form, doesn't return anything.
     */
    function EditNewsletter($newsletterid=0) {
        $newsletter = $this->GetApi();

        if ($newsletterid <= 0 || !$newsletter->Load($newsletterid)) {
            $GLOBALS['Error'] = GetLang('UnableToLoadNewsletter');
            $GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
            $this->ManageNewsletters();
            return;
        }

        // Log this to "User Activity Log"
        IEM::logUserActivity($_SERVER['REQUEST_URI'], 'images/newsletters_view.gif', $newsletter->name);

        $GLOBALS['Action'] = 'Edit&SubAction=Step2&id=' . $newsletterid;
        $GLOBALS['CancelButton'] = GetLang('EditNewsletterCancelButton');
        $GLOBALS['Heading'] = GetLang('EditNewsletter');
        $GLOBALS['Intro'] = GetLang('EditNewsletterIntro');
        $GLOBALS['NewsletterDetails'] = GetLang('EditNewsletterHeading');

        $GLOBALS['FormatList'] = '';
        $allformats = $newsletter->GetAllFormats();
        foreach ($allformats as $id => $name) {
            $selected = '';
            if ($id == $newsletter->format) {
                $selected = ' SELECTED';
            }

            if ($name == 'TextAndHTML') {
                $recommended = ' ' . GetLang('Recommended');
            } else {
                $recommended = '';
            }

            $GLOBALS['FormatList'] .= '<option value="' . $id . '"' . $selected . '>' . GetLang('Format_' . $name) . $recommended . '</option>';
        }

        $GLOBALS['Name'] = htmlspecialchars($newsletter->name, ENT_QUOTES, SENDSTUDIO_CHARSET);

        $GLOBALS['DisplayTemplateList'] = 'none';

        $this->ParseTemplate('Newsletter_Form_Step1');
    }

    /**
     * CreateNewsletter
     * Loads up stage 1 of creating a newsletter (choosing format, templates etc).
     *
     * @see GetApi
     * @see GetTemplateList
     * @see Newsletter_API::GetAllFormats
     *
     * @return Void Prints out the form, doesn't return anything.
     */
    function CreateNewsletter() {
        $newsletterapi = $this->GetApi();

        $GLOBALS['Action'] = 'Create&SubAction=Step2';
        $GLOBALS['CancelButton'] = GetLang('CreateNewsletterCancelButton');
        $GLOBALS['Heading'] = GetLang('CreateNewsletter');
        $GLOBALS['Intro'] = GetLang('CreateNewsletterIntro');
        $GLOBALS['NewsletterDetails'] = GetLang('CreateNewsletterHeading');

        $GLOBALS['FormatList'] = '';
        $allformats = $newsletterapi->GetAllFormats();
        foreach ($allformats as $id => $name) {
            if ($name == 'TextAndHTML') {
                $recommended = ' ' . GetLang('Recommended');
            } else {
                $recommended = '';
            }
            $GLOBALS['FormatList'] .= '<option value="' . $id . '">' . GetLang('Format_' . $name) . $recommended . '</option>';
        }

        $templateselects = $this->GetTemplateList();
        $GLOBALS['TemplateList'] = $templateselects;

        $this->ParseTemplate('Newsletter_Form_Step1');
    }

    /**
     * DisplayEditNewsletter
     * Prints out the editor for stage 2 of editing a newsletter (the wysiwyg area or textarea depending on the format chosen in stage 1). If you have selected a template in the previous step, the content from that template will be displayed here.
     *
     * @param Int $newsletterid Newsletter to load up. If there is one, it will pre-load that content. If there is no newsletterid, it displays a blank area to create your content.
     *
     * @see CreateNewsletter
     * @see EditNewsletter
     * @see GetApi
     * @see Newsletter_API::Load
     * @see Newsletter_API::GetBody
     * @see GetAttachments
     * @see FetchEditor
     *
     * @return Void Prints out the form, doesn't return anything.
     */
    function DisplayEditNewsletter($newsletterid=0) {
        $newsletter = $this->GetApi();
        $newslettercontents = array('text' => '', 'html' => '');

        $user = GetUser();

        $GLOBALS['FromPreviewEmail'] = $user->Get('emailaddress');

        $GLOBALS['DisplayAttachmentsHeading'] = 'none';

        $tpl = GetTemplateSystem();


        if ($newsletterid > 0) {
            $GLOBALS['SaveAction'] = 'Edit&SubAction=Save&id=' . $newsletterid;
            $GLOBALS['Heading'] = GetLang('EditNewsletter');
            $GLOBALS['Intro'] = GetLang('EditNewsletterIntro_Step2');
            $GLOBALS['Action'] = 'Edit&SubAction=Complete&id=' . $newsletterid;
            $GLOBALS['CancelButton'] = GetLang('EditNewsletterCancelButton');

            $newsletter->Load($newsletterid);
            $GLOBALS['IsActive'] = ($newsletter->Active()) ? ' CHECKED' : '';
            $GLOBALS['Archive'] = ($newsletter->Archive()) ? ' CHECKED' : '';
            $newslettercontents['text'] = $newsletter->GetBody('text');
            $newslettercontents['html'] = $newsletter->GetBody('html');

            $GLOBALS['Subject'] = htmlspecialchars($newsletter->subject, ENT_QUOTES, SENDSTUDIO_CHARSET);
        } else {
            $GLOBALS['SaveAction'] = 'Create&SubAction=Save&id=' . $newsletterid;
            $GLOBALS['Heading'] = GetLang('CreateNewsletter');
            $GLOBALS['Intro'] = GetLang('CreateNewsletterIntro_Step2');
            $GLOBALS['Action'] = 'Create&SubAction=Complete';
            $GLOBALS['CancelButton'] = GetLang('CreateNewsletterCancelButton');

            $GLOBALS['IsActive'] = ' CHECKED';
            $GLOBALS['Archive'] = ' CHECKED';
        }

        if (!SENDSTUDIO_ALLOW_ATTACHMENTS) {
            $tpl->Assign('ShowAttach', false);
            $GLOBALS['DisplayAttachments'] = 'none';
            $user = IEM::getCurrentUser();
            if ($user) {
                if ($user->isAdmin()) {
                    $GLOBALS['AttachmentsMsg'] = GetLang('NoAttachment_Admin');
                } else {
                    $GLOBALS['AttachmentsMsg'] = GetLang('NoAttachment_User');
                }
            }
        } else {
            $tpl->Assign('ShowAttach', true);
            $attachmentsarea = strtolower(get_class($this));
            $attachments_list = $this->GetAttachments($attachmentsarea, $newsletterid);
            $GLOBALS['AttachmentsList'] = $attachments_list;
        }

        $GLOBALS['PreviewID'] = $newsletterid;
        // we don't really need to get/set the stuff here.. we could use references.
        // if we do though, it segfaults! so we get and then set the contents.
        $session_newsletter = IEM::sessionGet('Newsletters');
        $session_newsletter['id'] = (int) $newsletterid;

        if (isset($session_newsletter['TemplateID'])) {
            $templateApi = $this->GetApi('Templates');
            if (is_numeric($session_newsletter['TemplateID'])) {
                $templateApi->Load($session_newsletter['TemplateID']);
                $newslettercontents['text'] = $templateApi->textbody;
                $newslettercontents['html'] = $templateApi->htmlbody;
            } else {
                $newslettercontents['html'] = $templateApi->ReadServerTemplate($session_newsletter['TemplateID']);
            }
            unset($session_newsletter['TemplateID']);
        }

        $session_newsletter['contents'] = $newslettercontents;
        IEM::sessionSet('Newsletters', $session_newsletter);
        $editor = $this->FetchEditor();
        $GLOBALS['Editor'] = $editor;

        $user = GetUser();
        if ($user->group->forcespamcheck) {
            $GLOBALS['ForceSpamCheck'] = 1;
        }

        $tpl->ParseTemplate('Newsletter_Form_Step2');
    }

}
