<?php
/**
* Language file variables for the mailing lists area. This includes creating, editing, deleting, managing.
*
* @see GetLang
*
* @version     $Id: lists.php,v 1.38 2008/02/18 06:41:11 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the mailing lists area... Please backup before you start!
*/
define('LNG_CreateMailingListHeading', 'New List Details');

define('LNG_ListName', 'List Name');
define('LNG_ListOwnerName', 'List Owners Name');
define('LNG_ListOwnerEmail', 'List Owners Email');
define('LNG_ListBounceEmail', 'List Bounce Email');
define('LNG_ListReplyToEmail', 'List Reply-To Email');

define('LNG_UnableToCreateList', 'Unable to create list');


define('LNG_HLP_ListName', 'The name of the list as it will appear both in the control panel and on your subscription forms.');

define('LNG_UnableToUpdateList', 'Unable to update list');

define('LNG_ChooseList', 'Please choose one or more lists first.');
define('LNG_ChooseMultipleLists', 'To perform this action, you need to choose more than one list.');

define('LNG_ListCopyFail', 'The selected list couldn\\\'t be copied.');

define('LNG_ListBounceServer', 'Bounce Email Server Name');
define('LNG_HLP_ListBounceServer', 'This is used for processing bounced emails. If you enter your email server, username and password, you can process bounces using a cron job.');

define('LNG_ListBounceUsername', 'Bounce Email User Name');
define('LNG_HLP_ListBounceUsername', 'This is used for processing bounced emails. If you enter your email server, username and password, you can process bounces using a cron job.');

define('LNG_ListBouncePassword', 'Bounce Email Password');
define('LNG_HLP_ListBouncePassword', 'This is used for processing bounced emails. If you enter your email server, username and password, you can process bounces using a cron job.');

define('LNG_IMAPAccount', 'IMAP Email Account');
define('LNG_IMAPAccountExplain', 'Yes, this is an IMAP Account');
define('LNG_HLP_IMAPAccount', 'Is the bounce email account an imap account? If it is not an imap account, it is a POP3 account.');

define('LNG_UseExtraMailSettingsExplain', 'Yes, use extra mail settings');
define('LNG_HLP_UseExtraMailSettings', 'You may need to set extra options to connect to an email account for bounce processing. If so, enable this option and choose/fill in the required information below. If unsure, leave this unticked.');

define('LNG_MergeSuccessful', '%s lists have been merged together successfully');
define('LNG_MergeUnsuccessful', '%s lists couldn\\\'t be merged together.');

define('LNG_ListCopyDisabled', 'You cannot copy this list because you do not have access.');
define('LNG_ListEditDisabled', 'You cannot edit this list because you do not have access.');
define('LNG_ListDeleteDisabled', 'You cannot delete this list because you do not have access.');
define('LNG_ListCopyDisabled_TooMany', 'You cannot copy this list, you have reached the maximum you can create');

define('LNG_BounceAccountDetails', 'Bounce Account Details');

define('LNG_ArchiveLists', 'Archive');


/**
**************************
* Changed/added in NX1.0.5
**************************
*/
define('LNG_UseExtraMailSettings', 'Use Extra Mail Settings');


/**
**************************
* Changed/added in NX 1.3
**************************
*/
define('LNG_TestBounceSettings', 'Test Bounce Settings');
define('LNG_TestBounceSettings_Server_Alert', 'Please enter the bounce email server name.');
define('LNG_TestBounceSettings_Username_Alert', 'Please enter the bounce email user name.');
define('LNG_TestBounceSettings_Password_Alert', 'Please enter the bounce email password.');

define('LNG_Bounce_Checking', '<span style="font-family: Tahoma, Verdana; font-size: 12px;">Checking your bounce account details.<br/><br/>This may take up to 2 minutes, please wait...</span>');

define('LNG_PredefinedCustomFields', 'Company Details');
define('LNG_CompanyName', 'Company Name');
define('LNG_CompanyAddress', 'Company Address');
define('LNG_CompanyPhone', 'Company Phone Number');
define('LNG_HLP_CompanyName', 'You can add your company name here to be used as a custom field in your emails so that you can adhere to the CAN-SPAM act.<br><br>The CAN-SPAM act states that you should include your company details in your emails.');
define('LNG_HLP_CompanyAddress', 'You can add your company address here to be used as a custom field in your emails so that you can adhere to the CAN-SPAM act.<br><br>The CAN-SPAM act states that you should include your company details in your emails.');
define('LNG_HLP_CompanyPhone', 'You can add your company phone number here to be used as a custom field in your emails so that you can adhere to the CAN-SPAM act.<br><br>The CAN-SPAM act states that you should include your company details in your emails.');

define('LNG_NotifyOwner', 'Notify the List Owner');
define('LNG_NotifyOwnerExplain', 'Yes, send subscribe and unsubscribe notification emails to the list owner');



/**
**************************
* Changed/added in NX 1.4
**************************
*/
define('LNG_ExtraMailSettingsNoValidate_field', 'Do not validate certificate');
define('LNG_ExtraMailSettingsNoValidate', 'Extra Mail Settings: Do not validate certificate');
define('LNG_HLP_ExtraMailSettingsNoValidate', 'Please check this option if you do not want to validate SSL ceritifcate. You need to select this option if you need to connect to a mail server that uses self-signed certificate. If unsure, leave this unticked.');

define('LNG_ExtraMailSettingsNoTLS_field', 'Do not use TLS');
define('LNG_ExtraMailSettingsNoTLS', 'Extra Mail Settings: No TLS');
define('LNG_HLP_ExtraMailSettingsNoTLS', 'Please check this option if you do not want to use TLS to connect to the bounce server. If unsure, leave this unticked.');

define('LNG_ExtraMailSettingsNoSSL_field', 'Do not use SSL');
define('LNG_ExtraMailSettingsNoSSL', 'Extra Mail Settings: No SSL');
define('LNG_HLP_ExtraMailSettingsNoSSL', 'Please check this option if you do not want to use SSL to connect to the bounce server. If unsure, leave this unticked.');

define('LNG_ExtraMailSettingsOthers_field', 'Others');
define('LNG_ExtraMailSettingsOthers', 'Other Extra Mail Settings');
define('LNG_HLP_ExtraMailSettingsOthers', 'Please add any other extra options that may be required to properly connect to a bounce email account.');

define('LNG_YesProcessBounces', 'Yes I want to process bounced emails for this list');
define('LNG_ProcessBounceGuideLink', 'What are bounced emails and how are they processed?');
define('LNG_ProcessBouncesLabel', 'Process Bounced Emails');
define('LNG_ProcessBounceDelete', 'I understand that bounced emails will be removed from the inbox I am using to manage them');
define('LNG_AgreeDeleteLabel', 'Agree to Delete Emails');

define('LNG_ListOwnerEmailNotValidEmail', 'The list owners email address is not valid. Please enter a valid email address.');
define('LNG_ListBounceEmailNotValidEmail', 'The bounce email address is not valid. Please enter a valid email address.');
define('LNG_ListReplyToEmailNotValidEmail', 'The reply-to email address is not valid. Please enter a valid email address.');


/**
**************************
* Changed/added in NX 1.4.1
**************************
*
* These were changed again for NX 5.0
*
*/

/**
**************************
* Changed/added in 5.0.0
**************************
*/

define('LNG_DeleteAllSubscribers', 'Delete contacts in selected list(s)');
define('LNG_DeleteAllSubscribersPrompt', 'Are you sure you want to delete all contacts from this contact list?');
define('LNG_ListDeleteAllSubscribersFail', 'Unable to delete all contacts from this contact list');
define('LNG_ListDeleteAllSubscribersSuccess', 'All contacts from this contact list deleted successfully');

define('LNG_ListsDeleteAllSubscribersFail', 'Unable to delete all contacts from these contact lists');
define('LNG_ListsDeleteAllSubscribersSuccess', 'All contacts from these contact list deleted successfully');

define('LNG_AllListSubscribersChangedFormat', 'All contacts have been updated to receive email campaigns in \'%s\' format.');
define('LNG_AllListSubscribersNotChangedFormat', 'All contacts could not been changed to receive email campaigns in \'%s\' format. Please try again.');

define('LNG_AllListSubscribersChangedStatus', 'All contacts have had their status changed to status \'%s\'.');

define('LNG_AllListSubscribersChangedConfirm', 'All contacts have had their status changed to \'%s\'.');
define('LNG_AllListSubscribersNotChangedConfirm', 'All contacts have not had their status changed to \'%s\'.');

define('LNG_MergeDuplicatesRemoved_Success', 'Successfully removed %s duplicate contact(s) from the new merged list.');
define('LNG_MergeDuplicatesRemoved_Fail', 'Failed to remove %s duplicate contact(s) from the new merged list.');

define('LNG_Lists_DeleteAllSubscribers_Disabled', 'You cannot delete contacts from this because you do not have access.');

define('LNG_AllListSubscribersNotChangedStatus', 'All contacts have not had their status changed to \'%s\'.');

define('LNG_CreateMailingListIntro', LNG_Help_ListsManage);

define('LNG_HLP_NotifyOwner', 'If this option is selected, the contact list owner will be sent a notification email whenever someone contacts or unsubscribes from this contact list.');

define('LNG_CreateListCancelButton', 'Are you sure you want to cancel creating a new contact list?');

define('LNG_EditMailingList', 'Edit Contact List');
define('LNG_EditMailingListIntro', 'Complete the form below to update the selected contact list.');
define('LNG_EditListCancelButton', 'Are you sure you want to cancel updating this contact list?');
define('LNG_EditMailingListHeading', 'Contact List Details');

define('LNG_EnterOwnerName', 'Please enter the name of the person who owns this contact list.');
define('LNG_EnterOwnerEmail', 'Please enter the email address of the person who owns this contact list.');
define('LNG_EnterReplyToEmail', 'Please enter the default \'Reply-To\' address for this contact list.');
define('LNG_EnterBounceEmail', 'Please enter the default \'Bounce\' address for this contact list.');

define('LNG_ListCreated', 'Your contact list has been saved successfully');


define('LNG_ListDeleteFail', 'An error occurred while trying to delete the selected contact list.');
define('LNG_ListsDeleteFail', 'An error occurred while trying to delete the selected contact lists.');

define('LNG_ListDeleteSuccess', 'The selected contact list has been deleted successfully');
define('LNG_ListsDeleteSuccess', 'The selected contact lists have been deleted successfully');

define('LNG_RSS_Tip', 'Click here to view archives of email campaigns sent to this contact list.');

define('LNG_CreateMailingList', 'Create a Contact List');

define('LNG_ListsManage', 'View Contact Lists');

define('LNG_HLP_ListOwnerName', 'The name of the person who owns this list. This is the default name used when you send an email campaign to this contact list.');
define('LNG_HLP_ListOwnerEmail', 'Emails are sent to this address when someone subscribes or unsubscribes from your contact list.<br/>This is also the default \\\'From\\\' email address used when sending an email campaign to this contact list.');
define('LNG_HLP_ListBounceEmail', 'This is the default bounce address used when you send an email campaign to this contact list. This is used if an invalid email address has been added to your contact list.');
define('LNG_HLP_ListReplyToEmail', 'This is the default reply address used when you send an email campaign to this contact list.');

define('LNG_ListOwner','List&nbsp;Owner');

define('LNG_ListDoesntExist', 'The contact list you are trying to edit does not exist. Please try again.');

define('LNG_Delete_Lists', 'Delete selected list(s)');

define('LNG_ListNameIsNotValid', 'Please enter a list name.');
define('LNG_EnterListName', LNG_ListNameIsNotValid);

define('LNG_ListOwnerNameIsNotValid', 'Please enter a list owner name.');
define('LNG_ListOwnerEmailIsNotValid', 'Please enter a valid list owner email address.');
define('LNG_ListBounceEmailIsNotValid', 'Please enter a valid bounce email address.');
define('LNG_ListReplyToEmailIsNotValid', 'Please enter a valid reply-to email address.');

define('LNG_ListUpdated', 'The selected contact list has been updated successfully.');
define('LNG_MergeLists', 'Merge the selected lists together');
define('LNG_ListCopySuccess', 'The selected list has been copied successfully.');

define('LNG_Add_Customfields_To_List', 'Custom Fields');
define('LNG_HLP_AddTheseFields', 'Select which custom fields you want associated with this list.');

/**
*************************
* Changed/added in 5.5.0
*************************
*/

define('LNG_ProcessBounceDeleteAll', 'Remove all emails from the inbox, not just bounce emails');
define('LNG_ProcessBounceDeleteAll_ManualPrompt', 'Ticking this option means that all emails in the bounce account inbox will be deleted. Are you sure?');
define('LNG_ProcessBounceDeleteAll_AutoPrompt', 'Ticking this option means that all emails in the bounce account inbox will be deleted every time automatic bounce processing runs. Are you sure?');
define('LNG_HLP_ProcessBounceDeleteAll', 'If ticked, all emails in the bounce account\\\'s inbox will be deleted every time bounce processing runs. You should tick this option if this email account is only used to process bounces.');
define('LNG_AddTheseFields', 'Add These Fields to the List');

/**
*************************
* Changed/added in 5.6.6
*************************
*/

define('LNG_DeleteListPrompt', 'Deleting this contact list will also delete all associated statistics. Are you sure you want to delete this contact list?');

