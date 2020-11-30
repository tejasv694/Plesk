<?php
/**
* Language file variables for the newsletters area. (Now referred to as email campaigns)
*
* @see GetLang
*
* @version     $Id: newsletters.php,v 1.31 2008/01/07 06:54:01 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the newsletters area... Please backup before you start!
*/
define('LNG_CreateNewsletterCancelButton', 'Are you sure you want to cancel creating a new email campaign?');
define('LNG_CreateNewsletterHeading', 'Email Campaign Details');

define('LNG_CreateNewsletterIntro_Step2', 'Use the form below to create your email campaign. You can also check your email for spam keywords and see how it looks in different email clients.');
define('LNG_Newsletter_Details', 'Email Campaign Details');

define('LNG_EditNewsletterIntro', 'Complete the form below to update the email campaign.');
define('LNG_EditNewsletterCancelButton', 'Are you sure you want to cancel updating this email campaign?');
define('LNG_EditNewsletterHeading', 'Email Campaign Details');

define('LNG_EditNewsletterIntro_Step2', 'Use the form below to update your email campaign. You can also check your email for spam keywords and see how it looks in different email clients.');

define('LNG_EnterNewsletterName', 'Please enter a name for your email campaign.');

define('LNG_NewsletterName', 'Email Campaign Name');

define('LNG_NewsletterNameIsNotValid', 'Email Campaign name is not Valid');
define('LNG_UnableToCreateNewsletter', 'Unable to create an email campaign');

define('LNG_HLP_NewsletterName', 'The name of the email campaign. This is for your reference only and will not be included in the email when its sent out.');

define('LNG_UnableToUpdateNewsletter', 'Unable to update email campaign');

define('LNG_NoNewslettersToDelete', 'No email campaigns have been selected. Please try again.');
define('LNG_Newsletter_NotDeleted', 'Unable to delete the selected email campaign');
define('LNG_Newsletters_NotDeleted', 'Unable to delete the %s selected email campaigns');
define('LNG_Newsletter_Deleted', '1 email campaign has been deleted successfully');
define('LNG_Newsletters_Deleted', '%s email campaigns have been deleted successfully');

define('LNG_Newsletter_NotDeleted_SendInProgress', 'Unable to delete email campaign \'%s\' - it is currently being sent.');
define('LNG_Newsletters_NotDeleted_SendInProgress', 'Unable to delete the following email campaigns - \'%s\' - they are currently being sent.');

define('LNG_NoNewslettersToAction', LNG_NoNewslettersToDelete);
define('LNG_InvalidNewsletterAction', 'Invalid email campaign action. Please try again.');

define('LNG_Newsletter_NotApproved', 'Unable to approve the selected email campaign');
define('LNG_Newsletters_NotApproved', 'Unable to approve the %s selected email campaigns');
define('LNG_Newsletter_Approved', '1 email campaign has been activated successfully');
define('LNG_Newsletters_Approved', '%s email campaigns have been activated successfully');

define('LNG_Newsletter_NotDisapproved', 'Unable to deactivate the selected email campaign');
define('LNG_Newsletters_NotDisapproved', 'Unable to deactivate the %s selected email campaigns');
define('LNG_Newsletter_Disapproved', '1 email campaign has been deactivated successfully');
define('LNG_Newsletters_Disapproved', '%s email campaigns have been deactivated successfully');

define('LNG_Newsletter_NotArchived', 'Unable to archive the selected email campaign');
define('LNG_Newsletters_NotArchived', 'Unable to archive the %s selected email campaigns');
define('LNG_Newsletter_Archived', '1 email campaign has been archived successfully');
define('LNG_Newsletters_Archived', '%s email campaigns have been archived successfully');

define('LNG_Newsletter_NotUnarchived', 'Unable to unarchive the selected email campaign');
define('LNG_Newsletters_NotUnarchived', 'Unable to unarchive the %s selected email campaigns');
define('LNG_Newsletter_Unarchived', '1 email campaign has been unarchived successfully');
define('LNG_Newsletters_Unarchived', '%s email campaigns have been unarchived successfully');

define('LNG_NewsletterFormat', 'Email Campaign Format');
define('LNG_NewsletterContent', 'Enter your email campaign content below');

define('LNG_NewsletterCopySuccess', 'Email campaign was copied successfully.');
define('LNG_NewsletterCopyFail', 'Email campaign was not copied successfully.');

// newslettersubject is in language.php
define('LNG_PleaseEnterNewsletterSubject', 'Please enter the email campaign subject.');

define('LNG_Newsletter_Send_Disabled_Inactive', 'You cannot send this email campaign because it is inactive.');
define('LNG_Newsletter_Send_Disabled', 'You cannot send this email campaign, you do not have access.');
define('LNG_Newsletter_Edit_Disabled', 'You cannot edit this email campaign, you do not have access.');
define('LNG_Newsletter_Copy_Disabled', 'You cannot copy this email campaign, you do not have access.');
define('LNG_Newsletter_Delete_Disabled', 'You cannot delete this email campaign, you do not have access.');
define('LNG_Newsletter_Delete_Disabled_SendInProgress', 'You cannot delete a email campaign while it is being sent.');

define('LNG_Archive', 'Archive');

define('LNG_ArchiveNewsletters', 'Archive');
define('LNG_UnarchiveNewsletters', 'Unarchive');
define('LNG_ApproveNewsletters', 'Activate');
define('LNG_DisapproveNewsletters', 'Deactivate');

define('LNG_Newsletter_Title_Enable', 'Enable this email campaign');
define('LNG_Newsletter_Title_Disable', 'Disable this email campaign');

define('LNG_Newsletter_Title_Archive_Enable', 'Enable archiving this email campaign');
define('LNG_Newsletter_Title_Archive_Disable', 'Disable archiving this email campaign');

define('LNG_NewsletterArchive', 'Archive Email Campaign');
define('LNG_NewsletterArchiveExplain', 'Yes, archive this email campaign');

define('LNG_NewsletterIsActive', 'Activate Email Campaign');
define('LNG_NewsletterIsActiveExplain', 'Yes, this email campaign is active');

define('LNG_NewsletterCannotBeInactiveAndArchive', 'This email will not be included in your archive as it has been deactivated. Once it has been reactivated it will be included in your archive.');

define('LNG_UnableToLoadNewsletter', 'Unable to load email campaign. Please try again.');

define('LNG_NewsletterFile', 'Email Campaign File');
define('LNG_HLP_NewsletterFile', 'Upload a html file from your computer to use as your email campaign');
define('LNG_UploadNewsletter', 'Upload');
define('LNG_NewsletterFileEmptyAlert', 'Please choose a file from your computer before trying to upload it.');
define('LNG_NewsletterFileEmpty', 'Please choose a file from your computer before trying to upload it.');

define('LNG_NewsletterURL', 'Email Campaign URL');
define('LNG_HLP_NewsletterURL', 'Import an email campaign from a url');
define('LNG_NewsletterURLEmptyAlert', 'Please enter a URL to import the email campaign from');
define('LNG_NewsletterURLEmpty', 'Please enter a URL to import the email campaign from');

define('LNG_NewsletterActivatedSuccessfully', 'Email campaign has been activated successfully');
define('LNG_NewsletterDeactivatedSuccessfully', 'Email campaign has been deactivated successfully');

define('LNG_NewsletterArchive_ActivatedSuccessfully', 'Email campaign has been archive activated successfully');
define('LNG_NewsletterArchive_DeactivatedSuccessfully', 'Email campaign has been archive deactivated successfully');

define('LNG_ChooseNewsletters', 'Please choose one or more email campaigns first.');

define('LNG_LastSent', 'Last Sent');
define('LNG_NotSent', 'Not Sent');

define('LNG_Newsletter_Edit_Disabled_SendInProgress', 'You cannot edit an email campaign while it is being sent');
define('LNG_Newsletter_ChangeActive_Disabled_SendInProgress', 'You cannot change this status while this email campaign is being sent');


/**
**************************
* Changed/added in NX1.1.1
**************************
*/
define('LNG_NewsletterFilesCopyFail', 'The images and/or attachments for this email campaign were not copied successfully.');

/**
**************************
* Changed/added in NX 1.3
**************************
*/
define('LNG_CreateNewsletter', 'Create an Email Campaign');

define('LNG_EditNewsletter', 'Edit an Email Campaign');

define('LNG_NewslettersManage', 'View Email Campaigns');
define('LNG_Help_NewslettersManage_HasAccess', ' To create an email campaign, click on the "Create an Email Campaign" button below.');

define('LNG_HLP_NewsletterSubject', 'The subject line of the email. For most email clients, they will see the subject line before they see the content of the email.<br /><br />You can include custom fields in the subject line by clicking on the \\\'Insert Custom Fields\\\' link below the editor and copy/pasting them into the subject text box.');

define('LNG_ArchiveHelp', 'An email campaign can be added to your automatically generated email campaign archive. You can turn this on or off by clicking on the option in the Archive column');

define('LNG_NewsletterActivateFailed_HasAttachments', 'The email campaign could not be activated because the administrator has disabled attachments. To enable this email campaign, please edit it and remove the attachments.');
define('LNG_NewsletterActivateFailed_HasAttachments_Multiple', '%s email campaigns could not be activated because the administrator has disabled attachments. To enable these email campaigns, please edit them and remove the attachments.');

define('LNG_NewsletterUpdated', 'Your email campaign has been updated. %s');

define('LNG_NewsletterCreated', 'Your email campaign has been saved. %s');


/**
**************************
* Changed/added in NX 1.4
**************************
*/
define('LNG_Newsletter_Send_Disabled_Resend_Maximum', sprintf('You have already tried to resend this email campaign %s times and you cannot send it any more. Please contact your system administrator.',
SENDSTUDIO_RESEND_MAXIMUM));



/**
**************************
* Changed/added in 5.0.0
**************************
*/
define('LNG_CreateNewsletterIntro', 'Type a name for your campaign and optionally choose a format and template below to get started. Click <em>Next</em> when you are ready to continue.');

define('LNG_Help_NewslettersManage', 'Email campaigns are messages that are sent to your contacts. Use email campaigns to send newsletters, promotions or notification emails.');

define('LNG_Newsletter_Size_Approximate', 'This email campaign will be approximately %s per contact if images are embedded as part of the email.');

define('LNG_HLP_NewsletterFormat', 'HTML and Text will allow your contacts to view your email in any type of client they wish. This type of email will produce limited results for open rates and link tracking.<br><br>Your contacts must be using an HTML capable email client to be able to view HTML emails. HTML emails will produce the best results for open rates and link tracking.<br><br>Text only emails are viewable in all email clients and are less likely to be reported as spam due to the lack of HTML code. This type of email will not be able to obtain results for open rates.');

define('LNG_Newsletter_Size_Approximate_Noimages', 'This email campaign will be approximately %s per contact if images are not embedded as part of the email.');

define('LNG_HLP_NewsletterArchive', 'Should this email campaign be archived? If so, it will appear in the archives for the contact list it is being sent to. You can then publish the archives on your website so your website visitors can read them ');

define('LNG_HLP_NewsletterIsActive', 'Should this email campaign be marked as active? Inactive email campaigns cannot be sent to a contact list and must be approved first.');

define('LNG_SendPreviewFrom', 'Send Preview from this Email');
define('LNG_SendPreviewTo', 'Send Preview to this Email');

/**
**************************
* Changed/added in 5.6.6
**************************
*/

define('LNG_DeleteNewsletterPrompt', 'Deleting this email campaign will also delete all associated statistics. Are you sure you want to delete this email campaign?');

define('LNG_NoAttachment_Admin', 'Attachments have been disabled. They can be enabled in the Email Settings');
define('LNG_NoAttachment_User', 'Attachments have been disabled by the administrator');

define('LNG_NewsletterArchive_DeactivatedWarning', 'When archive is deactivated RSS and web version of the email are disabled');


