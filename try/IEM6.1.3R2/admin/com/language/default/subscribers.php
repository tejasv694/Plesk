<?php

define('LNG_ChooseValueForCustomField', 'Choose a value for custom field \'%s\'');
define('LNG_EnterValueForCustomField', 'Enter a value for custom field \'%s\'');
define('LNG_ChooseOptionForCustomField', 'Choose an option for custom field \'%s\'');
define('LNG_RemoveOptions', 'For the Contacts Above');
define('LNG_EnterEmailAddressesToRemove', 'Please enter some email addresses to remove or choose a file to upload.');
define('LNG_Unsubscribe', 'Unsubscribe');
define('LNG_HLP_RemoveEmails', 'Type or paste the list of email addresses that you want to remove here. You should put each email address on a new line.<br><br/>Use this option if you have a small number of email addresses to remove.');
define('LNG_EmptyRemoveList', 'The file that you uploaded contains no email addresses.');
define('LNG_MassUnsubscribeFailed', 'The following email addresses were unable to be unsubscribed or deleted:<br/>');
define('LNG_MassUnsubscribeSuccessful', '%s email addresses were removed from the list successfully.');
define('LNG_MassUnsubscribeSuccessful_Single', '1 email address was removed from the list successfully.');
define('LNG_SubscriberEmailaddress', 'Email Address');
define('LNG_SubscriberFormat', 'Format');
define('LNG_SubscriberStatus', 'Status');
define('LNG_SubscriberConfirmed', 'Confirmed');
define('LNG_SubscriberAddFail_InvalidData', 'Invalid data was entered for the custom field \'%s\'.');
define('LNG_SubscriberAddFail_EmptyData_ChooseOption', '\'%s\' is a required field. Please choose an option.');
define('LNG_SubscriberAddFail_EmptyData_EnterData', '\'%s\' is a required field. Please fill in the field below.');
define('LNG_SubscriberEditFail_Duplicate', 'Someone is already subscribed to this list with the email address \'%s\'.');
define('LNG_SubscriberEditFail_InvalidData', 'Invalid data was entered for the custom field \'%s\'.');
define('LNG_Save_AddAnother', 'Save & Add Another');
define('LNG_UnsubscribeTime', 'Unsubscribe Time');
define('LNG_UnsubscribeIP', 'Unsubscribe IP');
define('LNG_Import_From_file', 'File');
define('LNG_ImportType', 'Import Type');
define('LNG_ImportDetails', 'Import Details');
define('LNG_ImportFormat', 'Format');
define('LNG_IncludeAutoresponder', 'Autoresponders');
define('LNG_ImportFileDetails', 'File Details');
define('LNG_ContainsHeaders', 'Contains Headers');
define('LNG_YesContainsHeaders', 'Yes, this file contains headers');
define('LNG_HLP_ContainsHeaders', 'Does the first line of your import file contain headers? If so, each header should be separated with a field separator, such as:<br><br>Email, Name, Sex.');
define('LNG_FieldSeparator', 'Field Separator');
define('LNG_EnterFieldSeparator', 'Please enter a field separator');
define('LNG_FieldEnclosed', 'Field Enclosure');
define('LNG_HLP_FieldEnclosed', 'Which character is each field enclosed by? All fields must have this character around them. For example, a record might look like this:<br><br>&quot;test@test.com&quot;, &quot;21&quot;, &quot;Male&quot;');
define('LNG_ImportFile', 'Import File');
define('LNG_ImportFile_FieldSeparatorEmpty', 'Please enter a field separator.');
define('LNG_ImportFile_FileEmpty', 'Please choose a file to import.');
define('LNG_MatchOption', 'Match option \'%s\' to field');
define('LNG_ImportFields', 'Link Import Fields');
define('LNG_MappingOption', 'Map Field');
define('LNG_HLP_MappingOption', 'Which database field does the \\\'%s\\\' field from the file relate to?');
define('LNG_ImportStart', 'Start Importing...');
define('LNG_InvalidSubscribeDate', ' <-- Invalid Subscribe Date');
define('LNG_InvalidCustomFieldData', ' <-- Invalid custom field data for field \'%s\'');
define('LNG_InvalidSubscriberEmailAddress', ' <-- Invalid Email Address');
define('LNG_InvalidSubscriberImportLine_NotEnough', ' <-- Missing a delimiter');
define('LNG_InvalidSubscriberImportLine_TooMany', ' <-- Too many delimiters');
define('LNG_InvalidReportURL', 'You have accessed an invalid report url. Please try again.');
define('LNG_ImportResults_Report_Invalid_Heading', 'Invalid Report URL.');
define('LNG_ImportResults_Report_Invalid_Intro', 'You have accessed an invalid url. Please close this window and try again.');
define('LNG_ImportResults_Report_Duplicates_Heading', 'Duplicate email addresses found');
define('LNG_ImportResults_Report_Failures_Heading', 'Failure to import email addresses');
define('LNG_ImportResults_Report_Failures_Intro', 'The following email addresses were not able to be imported. Please try again.');
define('LNG_ImportResults_Report_Unsubscribed_Heading', 'Unsubscribed Email Addresses');
define('LNG_DuplicateReport', '<b>Duplicate email addresses</b>');
define('LNG_FailureReport', '<b>Unable to subscribe these email addresses</b>');
define('LNG_ImportResults_InProgress', 'Import In Progress');
define('LNG_ImportSubscribers_InProgress_unsubscribes_Many', '%s lines contain unsubscribed email addresses so far');
define('LNG_ImportSubscribers_InProgress_unsubscribes_One', '1 line contains an unsubscribed email address so far');
define('LNG_ExportStart', 'Start Exporting...');
define('LNG_IncludeFields', 'Fields to Include');
define('LNG_ExportOptions', 'Export Options');
define('LNG_IncludeHeader', 'Include Field Headers?');
define('LNG_HLP_IncludeHeader', 'Should this export include field headers? If so, the first line of the file will look something like this:<br><br>Email, Status, Format');
define('LNG_FieldEnclosedBy', 'Field Enclosed By');
define('LNG_HLP_FieldEnclosedBy', 'Which character (if any) should each field be wrapped in? For example, if you enter a quote, then a sample record might look like this:<br><br>&quot;test@test.com&quot;, &quot;21&quot;, &quot;James&quot;');
define('LNG_Export_FieldSeparator', LNG_FieldSeparator);
define('LNG_HLP_Export_FieldSeparator', 'What character should be added to this export file to separate the contents of each new field in a record?');
define('LNG_ExportField', 'Field #%s');
define('LNG_SubscriberNone', 'None');
define('LNG_Subscribers_Export_Step4_Intro', 'Click the "Start Export" button to start exporting.');
define('LNG_ExportResults_Heading', 'Export Results');
define('LNG_SubscriberEmail', 'Email Address');
define('LNG_SubscribeDate_DMY', 'Subscribe Date (dd/mm/yyyy)');
define('LNG_SubscribeDate_MDY', 'Subscribe Date (mm/dd/yyyy)');
define('LNG_SubscribeDate_YMD', 'Subscribe Date (yyyy/mm/dd)');
define('LNG_IncludeField', 'Include this field?');
define('LNG_DeleteExportFile', 'Delete the Export File');
define('LNG_EmptyBannedList', 'The file that you uploaded contains no email addresses.');
define('LNG_BannedSubscriberEmail', 'Email Address');
define('LNG_Delete_Banned_Selected', 'Delete Selected');
define('LNG_ConfirmBannedSubscriberChanges', 'Are you sure you want to make these changes?\nThis action cannot be undone.');



/**
 * 1.0.5
 */
define('LNG_ImportResults_Report_Bads_Heading', 'Bad data was found');
define('LNG_ImportResults_Report_Bads_Intro', 'The following lines with the email addresses listed below in the import file were found to contain bad data.');
define('LNG_ImportSubscribers_InProgress_bads_Many', '%s lines contain bad data so far');
define('LNG_ImportSubscribers_InProgress_bads_One', '1 line contains bad data so far');



/**
 * 1.3.0
 */
define('LNG_SubscriberIP_Unknown', 'Unknown IP Address');
define('LNG_SubscriberBounceTime', 'Time of Bounce');
define('LNG_SubscriberBounceType', 'Type of Bounce');
define('LNG_BounceTimeFormat', 'Y/m/d');
define('LNG_BounceTypeFormat', '%s (%s bounce)');
define('LNG_RemoveEmails', 'Contacts to Remove');
define('LNG_RemoveFile', 'Upload a file from my computer');
define('LNG_HLP_RemoveFile', 'If you have a file that contains the email addresses you wish to delete, you can select it here. Your file should contain one email address per line. For example:<br /><br />email1@domain.com<br>email2@domain2.com');
define('LNG_ImportConfirmedStatus', 'Mark as Confirmed');
define('LNG_OverwriteExistingSubscriber', 'Overwrite Existing Details');
define('LNG_Subscribers_Export_Step3_Intro', 'Choose how you want to export your contacts by filling out the form below. You can choose which fields to include under the <em>Fields to Include</em> section.');
define('LNG_SubscriberBanListEmpty', '%s has no suppressed email addresses.');
define('LNG_SubscriberBan_Updated', 'The suppressed email has been updated successfully');
define('LNG_SubscriberBan_NotUpdated', 'Suppressed email address has not been updated.');
define('LNG_SubscriberBan_UnableToUpdate', 'Unable to update the suppression information. Please try again.');
define('LNG_ConfirmRemoveBannedSubscribers', 'Are you sure you want to remove these suppressed emails?');
define('LNG_ChooseBannedSubscribers', 'Please choose some suppressed emails to remove.');
define('LNG_BannedAddButton', 'Suppress an Email Address or Domain...');
define('LNG_Subscriber_Ban_NotDeleted_One', '1 suppressed email address was not deleted from the list \'%s\'.');
define('LNG_Subscriber_Ban_Deleted_One', '1 suppressed email was deleted successfully from list \'%s\'.');
define('LNG_Subscriber_Ban_NotDeleted_Many', '%s suppressed email addresses bans were not deleted from list \'%s\'.');
define('LNG_Subscribers_Banned_Edit', 'Edit Suppressed Email');
define('LNG_Subscribers_Banned_Edit_Intro', 'Modify the details of the suppressed email address in the form below and click on the \'Save\' button.');
define('LNG_Subscribers_Banned_Edit_CancelPrompt', 'Are you sure you want to cancel editing this suppressed email address?');
define('LNG_Banned_Edit_Empty', 'Please enter an email address to suppress.');
define('LNG_Banned_Edit_ChooseList', 'Please choose a list to suppress this email address from.');
define('LNG_Ban_Count_Many', ' (%s suppressions)');
define('LNG_Ban_Count_One', ' (1 suppression)');
define('LNG_BannedDate', 'Date Suppressed');
define('LNG_DeleteBannedSubscriberPrompt', 'Are you sure you want to remove this suppression?');
define('LNG_MassBanSuccessful', '%s email addresses were successfully added to your suppression email list.');
define('LNG_MassBanSuccessful_Single', '1 email address has been suppressed successfully');
define('LNG_MassBanFailed', '<br>An error occurred while trying to suppress the following email addresses:<br/>');
define('LNG_Subscriber_AlreadyBanned', 'Email address is already suppressed');
define('LNG_Subscribers_Banned', 'View Suppression Email List');
define('LNG_Subscribers_BannedManage_CancelPrompt', 'Are you sure you want to cancel managing your suppression email lists?');
define('LNG_Banned_Subscribers_FoundOne', 'Found 1 suppressed email address.');
define('LNG_Banned_Subscribers_FoundMany', 'Found %s suppressed email addresses.');
define('LNG_SubscribersManageBanned', 'View Suppressed Emails for %s');
define('LNG_BannedFile', 'Email Suppression File');
define('LNG_HLP_BannedFile', 'Choose a file to upload that contains a list of email addresses or domains to suppress. The file should contain one entry per line. To suppress an entire domain, simply use:<br /><br />@hotmail.com<br />@gmail.com<br />@company.com');
define('LNG_Subscribers_GlobalBan', '-- Global Suppression (All Lists) --');
define('LNG_Subscribers_Banned_CancelPrompt', 'Are you sure you want to cancel?');
define('LNG_Banned_Add_EmptyList', 'Please enter an email address or domain name to suppress.');
define('LNG_Banned_Add_EmptyFile', 'Please select a file that contains email addresses you want to suppress.');
define('LNG_Banned_Add_ChooseList', 'Please choose a list to suppress these email addresses from.');
define('LNG_BannedEmailDetails', 'Suppression Email/Domain Details');
define('LNG_Subscribers_Banned_Add', 'Add Emails/Domains to Suppression List');
define('LNG_BannedEmails', 'Emails or Domains to Suppress');
define('LNG_HLP_BannedEmails', 'Enter the list of emails addresses to suppress here. Separate each email address with a new line. If you would like to suppress a whole domain, simply enter @DOMAINNAME. For example, to suppress everyone at Hotmail, enter @hotmail.com.');
define('LNG_Banned_AddEmailsUsingForm','I want to type the email addresses/domains to suppress into a text box');
define('LNG_BanSingleEmail', 'Email to Suppress');
define('LNG_HLP_BanSingleEmail', 'Enter the email address to suppress here. If you would like to suppress a whole domain, simply enter @DOMAINNAME. For example, to suppress everyone at Hotmail, enter @hotmail.com.');
define('LNG_HLP_BannedEmailsChooseList', 'Choose a list to suppress these email addresses from.');
define('LNG_ImportSubscribers_InProgress_bans_Many', '%s lines contain suppressed email addresses or domains so far');
define('LNG_ImportSubscribers_InProgress_bans_One', '1 line contains a suppressed email address or domain so far');
define('LNG_ImportResults_Report_Banned_Heading', 'Suppressed email addresses');
define('LNG_FilterOptions_Subscribers', 'Search Options');
define('LNG_ImportFile_ServerFileEmpty', 'Please choose a file to import.');
define('LNG_ImportFile_ServerFileDoesNotExist', 'Selected file does not exist in the "import" directory');
define('LNG_ImportFile_ServerFileEmptyList', 'No files was found on your server. To use this option, please upload the file to be imported to the "import" (admin/import) directory on the server.');
define('LNG_ImportFile_SourceUpload', 'Upload a file from my computer ('.ini_get('upload_max_filesize').' maximum)');
define('LNG_ImportFile_SourceServer', 'Import a file from my web site');
define('LNG_ImportFileFromServer', LNG_ImportFile);
define('LNG_ExportFileType', 'File Format');
define('LNG_ExportFileTypeCSV', 'CSV File');
define('LNG_ExportFileTypeXML', 'XML File');
define('LNG_ImportFile_HeaderInFile', 'The import file contains these fields:');
define('LNG_HLP_FieldSeparator', 'Enter in the character that is used in your CSV import file that separates each field or column. Make sure this character does not appear elsewhere in the CSV file. Usually in a CSV (Comma Separated Values) file this character is a \\\',\\\'. Make sure your columns themselves don\\\'t contain commas by searching and replacing all commas with just a blank value.<br/><br>If you wish to use the tab character enter the word &quot;TAB&quot; here.<br><br>If you are unsure, leave this option as is.');



/**
 * 1.4.1
 */
define('LNG_SubscriberIsAlreadyUnsubscribed', 'The email address \'%s\' is already unsubscribed.');



/**
 * 5.0.0
 */
define('LNG_Subscribers_Add_CancelPrompt', 'Are you sure you want to cancel adding a new contact?');
define('LNG_Subscribers_EnterEmailAddress', 'Please enter an email address for this contact.');
define('LNG_NewSubscriberDetails', 'New Contact Details');
define('LNG_SubscriberAddFail', 'Contact was not added successfully');
define('LNG_SubscriberAddFail_Duplicate', 'A contact with the email address \'%s\' already exists.');
define('LNG_SubscriberAddFail_Unsubscribed', 'A contact with the email address \'%s\' has unsubscribed from this contact list. To reactivate, edit the contact and change their status to "Active".');
define('LNG_SubscriberAddFail_InvalidEmailAddress', 'A contact with email address \'%s\' cannot be added to this list. It is an invalid email address.');
define('LNG_SubscriberAddSuccessful', 'The new contact was added to your list.');
define('LNG_SubscriberAddSuccessfulList', 'Contact added to contact list \'%s\' successfully.');
define('LNG_Subscribers_Remove_Heading', 'Remove Contacts');
define('LNG_Subscribers_Remove', 'Remove Contacts');
define('LNG_Subscribers_Remove_CancelPrompt', 'Are you sure you want to cancel removing contacts?');
define('LNG_Subscribers_Remove_Step2', 'Remove Contacts');
define('LNG_Subscribers_RemoveMore', 'Remove More Contacts');
define('LNG_DeleteSubscriberPrompt', 'Are you sure you want to delete this contact?');
define('LNG_NoSubscribersToDelete', 'No contacts to delete. Please try again.');
define('LNG_Subscriber_Deleted', '1 contact was deleted successfully');
define('LNG_Subscribers_Deleted', '%s contacts were deleted successfully');
define('LNG_Subscriber_NotDeleted', '1 contact was not deleted.');
define('LNG_Subscribers_NotDeleted', '%s contacts were not deleted.');
define('LNG_NoSubscribersToChangeFormat', 'There are no contacts to change email formats for.');
define('LNG_Subscriber_NotChangedFormat', '1 contact was not changed to receive emails in %s format.');
define('LNG_Subscribers_NotChangedFormat', '%s contacts were not changed to receive emails in %s format.');
define('LNG_Subscriber_ChangedFormat', '1 contact was changed to receive emails in %s format.');
define('LNG_Subscribers_ChangedFormat', '%s contacts were changed to receive emails in %s format.');
define('LNG_NoSubscribersToChangeStatus', 'There are no contacts to change status.');
define('LNG_Subscriber_NotChangedStatus', '1 contact was not changed to status %s');
define('LNG_Subscribers_NotChangedStatus', '%s contacts were not changed to status %s');
define('LNG_Subscriber_ChangedStatus', '1 contact was changed to status %s');
define('LNG_Subscribers_ChangedStatus', '%s contacts were changed to status %s');
define('LNG_NoSubscribersToChangeConfirm', 'There are no contacts to change confirmation status.');
define('LNG_Subscriber_NotChangedConfirm', '1 contact was not changed to confirmation status \'%s\'.');
define('LNG_Subscribers_NotChangedConfirm', '%s contacts were not changed to confirmation status \'%s\'.');
define('LNG_Subscriber_ChangedConfirm', '1 contact was changed to confirmation status \'%s\'.');
define('LNG_Subscribers_ChangedConfirm', '%s contacts were changed to confirmation status \'%s\'.');
define('LNG_Subscribers_Edit', 'Edit Contact');
define('LNG_Subscribers_Edit_CancelPrompt', 'Are you sure you want to cancel editing this contact?');
define('LNG_EditSubscriberDetails', 'Edit Contact Details');
define('LNG_SubscriberEditSuccess', 'The details of the contact have been updated. You can continue making changes below.');
define('LNG_SubscriberEditFail', 'Unable to update contact information. Please try again.');
define('LNG_ChooseSubscribers', 'Please choose at least one contact first.');
define('LNG_HLP_UnsubscribeTime', 'When the contact unsubscribed from the contact list.');
define('LNG_HLP_UnsubscribeIP', 'The ip address of the contact when they unsubscribed from the contact list.');
define('LNG_HLP_ConfirmedStatus', 'The confirmed option is usually used for the double-optin process where users confirm there subscription by clicking a link in a confirmation email. If you select unconfirmed you can send the contacts an email at a later date which contains a confirmation link to make sure they want to be included your contact list.');
define('LNG_HLP_Format', 'Which email format should these contacts be \\\'flagged\\\' to receive by default? HTML or Text? HTML contacts can receive both HTML and Text emails, but Text contacts can only receive Text emails.<br><br>If you are unsure, select HTML.');
define('LNG_HLP_SubscriberStatus', 'Active contacts are those who have not bounced and have not unsubscribed from the contact list.<br/>The \\\'bounced\\\' status is for those who have been disabled on the contact list because they have had too many messages bounce from their email address, or have been detected as a hard bounce.<br/>The \\\'unsubscribed\\\' status is for those who have specifically unsubscribed from the contact list.');
define('LNG_Subscribers_Import', 'Import Contacts from a File');
define('LNG_Subscribers_Import_Intro', 'To import contacts from a CSV file on your computer, start by select which lists to import contacts to from those shown below.');
define('LNG_Subscribers_Import_Step2', 'Import Contacts from a File');
define('LNG_HLP_ImportType', 'How will you be importing your list of contacts?');
define('LNG_Subscribers_Import_CancelPrompt', 'Are you sure you want to cancel importing contacts?');
define('LNG_ImportStatus', 'Status');
define('LNG_HLP_ImportStatus', 'When these contacts are imported, what should their status be?');
define('LNG_Subscribers_Import_Step3', 'Import Contacts');
define('LNG_Subscribers_Import_Step3_Intro', 'The fields from your CSV file are shown below on the left. Choose which contact details they correspond to by selecting them from the list on the right.');
define('LNG_HLP_ImportFormat', 'Which email format should these contacts be \\\'flagged\\\' to receive by default? HTML or Text? HTML contacts can receive both HTML and Text emails, but Text contacts can only receive Text emails. If your import file contains a field to specify formatting, it will override this setting.<br><br>If you are unsure, select HTML.');
define('LNG_YesIncludeAutoresponder', 'Yes, add contacts to autoresponders');
define('LNG_HLP_ImportFile', 'Choose a file to upload that contains the contact details that you want to import. This should be a plain text file.');
define('LNG_EmailAddressNotLinked', 'The contact email address field is not linked. We cannot proceed without this being linked.');
define('LNG_Subscribers_Import_Step4', 'Import Contacts');
define('LNG_Subscribers_Import_Step4_Intro', 'Click the button below to start importing your contacts. Please do not close your browser or navigate away from this page while your contacts are being imported.<br /><br />');
define('LNG_ImportSubscribers_success_Many', '%s contacts were imported successfully');
define('LNG_ImportSubscribers_success_One', '1 contact was imported successfully');
define('LNG_ImportSubscribers_updates_Many', '%s contacts were updated successfully');
define('LNG_ImportSubscribers_updates_One', '1 contact was updated successfully');
define('LNG_ImportSubscribers_duplicates_Many', '%s contacts contained duplicate email addresses');
define('LNG_ImportSubscribers_failures_Many', '%s contacts were not imported successfully');
define('LNG_ImportSubscribers_unsubscribes_Many', '%s contacts are unsubscribed');
define('LNG_ImportResults_Heading', 'Import Contacts');
define('LNG_ImportResults_Intro', 'The contact import has been completed successfully');
define('LNG_ImportResults_InProgress_Message', 'Please wait while we attempt to import the %s contact record(s)...');
define('LNG_ImportSubscribers_InProgress_success_Many', '%s contacts have been imported so far');
define('LNG_ImportSubscribers_InProgress_success_One', '1 contact has been imported so far');
define('LNG_ImportSubscribers_InProgress_updates_Many', '%s contacts have been updated so far');
define('LNG_ImportSubscribers_InProgress_updates_One', '1 contact has been updated so far');
define('LNG_ImportSubscribers_InProgress_duplicates_Many', '%s duplicate contacts have been found so far');
define('LNG_ImportSubscribers_InProgress_duplicates_One', '1 duplicate contact has been found so far');
define('LNG_ImportSubscribers_InProgress_failures_Many', '%s contacts have not been imported so far');
define('LNG_ImportSubscribers_InProgress_failures_One', '1 contact has not been imported so far');
define('LNG_Subscribers_Export_Step2', 'Export Contacts to a File');
define('LNG_Subscribers_Export_Step2_Intro', 'Use the wizard below to export a copy of your contact list to a CSV file which you can download to your computer.');
define('LNG_Subscribers_Export_FoundOne', '1 contact matched your search and can be exported. Choose your export options below and click <em>Next</em> to start the export.');
define('LNG_Subscribers_Export_FoundMany', '%s contacts matched your search and can be exported. Choose your export options below and click <em>Next</em> to start the export.');
define('LNG_ExportSummary_FoundOne', 'Click the button below to start exporting your contact. Please do not close your browser or navigate away from this page while your contact is being exported.<br /><br />');
define('LNG_ExportSummary_FoundMany', 'Click the button below to start exporting (%s contacts will be exported). Please do not close your browser or navigate away from this page while your contacts are being exported.<br /><br />');
define('LNG_ExportResults_InProgress_Message', 'Please wait while we attempt to export your %s contact(s).');
define('LNG_ExportResults_InProgress_Status', '%s of %s contacts have been exported so far...');
define('LNG_ExportResults_Intro', 'The selected contacts have been exported successfully. <a href=%s target=_blank>Click here to download the export file</a>. You should delete this file once you have finished downloading.');
define('LNG_ExportResults_Link', 'Click here to download your exported contacts.');
define('LNG_ExportResults_InProgress', 'Exporting Contacts');
define('LNG_ImportSubscribers_bads_One', '1 contact contains bad data');
define('LNG_ImportSubscribers_bads_Many', '%s contacts contained bad data');
define('LNG_SubscriberIPAddress', 'Contacts IP Address');
define('LNG_ImportSubscribers_duplicates_Many_Link', '%s contacts contained duplicate email addresses. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_duplicates_One_Link', '1 contact contained a duplicate email address. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_failures_Many_Link', '%s contacts were not imported successfully. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_failures_One_Link', '1 contact was not imported successfully. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_unsubscribes_Many_Link', '%s contacts are unsubscribed from this list. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_unsubscribes_One_Link', '1 contact is unsubscribed from this list. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_bads_Many_Link', '%s contacts contained bad data. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_bads_One_Link', '1 contact contained bad data. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_Subscribers_Manage', 'View Contacts');
define('LNG_Subscribers_Manage_Intro', 'A contact is a person that has been added or subscribed to your contact list. You can view all of your contacts or you can use the filtering options to find specific contacts.');
define('LNG_Subscribers_Manage_CancelPrompt', 'Are you sure you want to cancel viewing contacts?');
define('LNG_Subscribers_Add', 'Add a Contact');
define('LNG_Subscribers_Add_Step1', LNG_Subscribers_Add);
define('LNG_Subscribers_Remove_Step2_Intro', 'Use the form below to remove contacts from your list. You can set their status to unsubscribed, or you can delete them from your list permanently.');
define('LNG_YesOverwriteExistingSubscriber', 'Yes, overwrite existing contact details');
define('LNG_Subscribers_Export', 'Export Contacts to a File');
define('LNG_Subscribers_Export_Intro', 'A copy of your contact list can be exported to a CSV file that you can download to your computer. Please choose which contact list you wish to export from.');
define('LNG_Subscribers_Export_CancelPrompt', 'Are you sure you want to cancel exporting your contacts?');
define('LNG_Subscribers_Export_Step3', 'Export Contacts to a File');
define('LNG_Subscribers_Export_Step4', 'Export Contacts to a File');
define('LNG_Subscribers_Edit_Intro', 'Update the details of the selected contact using the form below.');
define('LNG_Subscribers_Add_Step1_Intro', 'To add a single contact to your list by typing in their details, start by choosing which list you want to add them to. Alternatively you can <a href="index.php?Page=Subscribers&Action=Import">import contacts from a file</a>.');
define('LNG_Subscribers_Add_Step2_Intro', 'Type the details of the new contact into the form below. When you click Save they will be added to your list.');
define('LNG_ImportSubscribers_bans_One_Link', '1 contact has been suppressed from joining this contact list. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_bans_Many_Link', '%s contacts have been suppressed from joining this contact list. <a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_ImportSubscribers_bans_Many', '%s contacts are suppressed from joining this contact list');
define('LNG_Subscribers_FoundOne', 'Your search returned 1 contact. Details are shown below.');
define('LNG_Subscribers_FoundMany', 'Your search returned %s contacts. They are shown below.');
define('LNG_Subscribers_OneList_FoundOne', 'You have 1 contact in your mailing list. Details are shown below.');
define('LNG_Subscribers_OneList_FoundMany', 'You have %s contacts in your mailing list. They are shown below.');
define('LNG_Subscribers_ManyList_FoundOne', 'You have 1 contact across your lists. Details are shown below.');
define('LNG_Subscribers_ManyList_FoundMany', 'You have %s contacts across your lists. They are shown below.');
define('LNG_Subscribers_AllList_FoundOne', 'You have 1 contact across all of your lists. Details are shown below.');
define('LNG_Subscribers_AllList_FoundMany', 'You have %s contacts across all of your lists. They are shown below.');
define('LNG_Subscribers_Segment_FoundOne', 'You have 1 contact in your segment. Details are shown below.');
define('LNG_Subscribers_Segment_FoundMany', 'You have %s contacts in your segment. They are shown below.');
define('LNG_SubscribersManageAnyList', 'All Contacts');
define('LNG_SubscribersManageMultipleList', 'Contacts from multiple list');
define('LNG_SubscribersManageSearchResult', 'Search Results');
define('LNG_SubscribersManageSingleList', '%s');
define('LNG_SubscribersManageSegment', '%s');
define('LNG_SubscribersShowFilteringOptionsExplain', 'View specific contacts from within the selected lists below');
define('LNG_SubscribersDoNotShowFilteringOptionsExplain', 'View all contacts in the selected contact lists below');
define('LNG_SubscribersShowSegmentsExplain', 'View all contacts within the selected segments below');
define('LNG_SubscribersShowFilteringOptionsOneListExplain', 'Specific contacts from within your contact list');
define('LNG_SubscribersDoNotShowFilteringOptionsOneListExplain', 'All contacts in your contact list');
define('LNG_SubscribersExportShowFilteringOptionsExplain', 'Export specific contacts from within the selected contact list');
define('LNG_SubscribersExportDoNotShowFilteringOptionsExplain', 'Export all contacts in the selected contact list');
define('LNG_SubscribersExportShowFilteringOptionsOneListExplain', 'Export specific contacts from within your contact list');
define('LNG_SubscribersExportDoNotShowFilteringOptionsOneListExplain', 'Export all contacts in my contact list');
define('LNG_Subscribers_Add_Step2', 'Add a Contact to %s');
define('LNG_HLP_ImportFileFromServer', htmlentities('Upload a file into your "admin/import" folder to see it listed here. Generally you would upload a file to your server first instead of uploading it directly using the "Upload a File..." method above if it is for a large import containing thousands of Contacts.'));
define('LNG_HLP_ExportFileTypeCSV', 'Choose this option to export your contact details in a comma-separated value (CSV) file. This file type can be opened by most spreadsheet programs, including Microsoft Excel.');
define('LNG_HLP_ExportFileTypeXML', 'Choose this option to export your contact details into an XML file format. Some third party applications allow you to work with data via an XML file, or you can programatically extract your contacts from this XML file using a programming language.');
define('LNG_Subscribers_Export_MultipleList', 'Export Contacts For: %s contact lists');
define('LNG_Subscribers_Export_AnyList', 'Export Contacts For All Contact Lists');
define('LNG_SubscribersShowFilteringOptionsExplainOne', 'View specific contacts from your contact list');
define('LNG_SubscribersDoNotShowFilteringOptionsExplainOne', 'View all contacts in your contact list');
define('LNG_SubscribersExportShowFilteringOptionsExplainOne', 'Search for contacts to export from my contact list');
define('LNG_SubscribersExportDoNotShowFilteringOptionsExplainOne', 'Export all contacts in my contact list');
define('LNG_HLP_RemoveOptions', 'What do you want to happen to the list of email addresses?<br><br/>Choose \\\'Delete from List\\\' to remove them from the contact list completely.<br/><br/>Choose \\\'Unsubscribe\\\' to move them to the unsubscribe list.');
define('LNG_ImportResults_Report_Duplicates_Intro', 'The following email addresses were already on your contact list or in the file you uploaded multiple times and were not imported again.');
define('LNG_ImportResults_Report_Unsubscribed_Intro', 'The following email addresses were not able to be imported because they have unsubscribed from this contact list.');
define('LNG_HLP_IncludeField', 'Do you want to include this field in the export of your contact list? If not, set this option to \\\'None\\\'');
define('LNG_NoBannedSubscribersOnList', 'The contact list \'%s\' does not contain any suppressed email addresses.');
define('LNG_Subscribers_Banned_Intro', 'Email addresses included in a suppression list will never be emailed even if they are still subscribed to a contact list.');
define('LNG_Manage_Banned_Intro', 'Email addresses included in a suppression list will never be emailed even if they are still subscribed to a contact list.');
define('LNG_HLP_BannedEmailsChooseList_Edit', 'Choose the contact list to suppress this email address or domain name from.');
define('LNG_Subscribers_Banned_Add_Intro', 'Email addresses included in a suppression list will never be emailed even if they are still subscribed to a contact list.');
define('LNG_BannedEmailsChooseList', 'I Want to Suppress Contacts from');
// we duplicate it here so we can use a different helptip.
define('LNG_BannedEmailsChooseList_Edit', LNG_BannedEmailsChooseList);
define('LNG_ImportResults_Report_Banned_Intro', 'The following email addresses were not able to be imported because they are suppressed from joining this contact list.');
define('LNG_SubscriberAddFail_Banned', 'The email address \'%s\' is suppressed from joining this contact list.');
define('LNG_Subscribers_Remove_Intro', 'To <em>permanently</em> delete a contact from your list, start by choosing which list you want to remove them from. <u>Removing a contact from your list cannot be undone</u>.');
define('LNG_ImportFile_HeaderInMailingList', '... which should be saved as these fields in the contact list:');
define('LNG_Help_SubscribersManage', 'A contact is a person that has been added or has subscribed to your contact list. Your existing contacts are shown below.');
define('LNG_Subscribers_Import_Step2_Intro', 'Choose the CSV file from your computer by clicking the <em>Browse...</em> button below. You can also specify advanced options if required.');
define('LNG_ImportTutorialLink', 'Learn more about importing here.');
define('LNG_HLP_ImportConfirmedStatus', 'Should imported contacts be marked as confirmed? The confirmed option is usually used for the double-optin process where users confirm there subscription by clicking a link in a confirmation email. If you select unconfirmed you can send the contacts an email at a later date which contains a confirmation link to make sure they want to be included your contact list.<br><br>If you have permission to email these contacts, select the Confirmed option, otherwise select the Unconfirmed option and send them an email later to confirm their subscription.');
define('LNG_HLP_OverwriteExistingSubscriber', 'If a contact already exists in the current contact list with the same email address, and you select this option, their current details will be overwritten with the details in your import file. For example, if a contact has a \\\'Phone number\\\' field and it has changed in your CSV, selecting this option will update the phone number with the new details in the CSV file.<br><br>If you are unsure, leave this option unchecked.');
define('LNG_HLP_IncludeAutoresponder', 'If autoresponders have been created for this contact list, then selecting this option would start the autoresponders being sent to these contacts, otherwise, these contacts will never receive any of your Autoresponders.<br><br>If you are unsure, leave this option unchecked.');
define('LNG_EnterValidDate', 'Please enter a valid date for custom field \'%s\'');
define('LNG_Subscribers_AdvancedSearch', 'Search for Contacts');
define('LNG_Subscribers_SimpleSearch_Title', 'Search for emails in this mailing list.');
define('LNG_Subscribers_View', 'View Contact');
define('LNG_Subscribers_View_Intro', 'Details for the contact you selected are shown below. Click <em>Edit Contact</em> to make changes or <em>Delete Contact</em> to delete the contact from your list.');
define('LNG_Subscribers_View_Button_Edit', 'Edit Contact');
define('LNG_Subscribers_View_Button_Delete', 'Delete Contact');
define('LNG_SubscriberDoesntExist_View', 'The contact you are trying to view does not exist. Please try again.');
define('LNG_SubscriberDoesntExist_Edit', 'The contact you are trying to edit does not exist. Please try again.');
define('LNG_BannedSubscriberDoesntExist', 'The suppressed email you are trying to edit does not exist. Please try again.');
define('LNG_SubscriberSegmentDetails', 'Select Segment(s)');
define('LNG_SubscriberFilterBySegments', LNG_Segments);
define('LNG_HLP_SubscriberFilterBySegments', 'Which segments would you like to view contacts for? Choose one or more simply by ticking the checkboxes.');
define('LNG_SubscriberViewPicker_All', 'View All');
define('LNG_SubscriberViewPicker_Search', 'Search');
define('LNG_DeleteSelectedContacts', 'Delete selected contact(s)');
define('LNG_RemoveUnsubscribe', 'Mark them as unsubscribed in my list');
define('LNG_RemoveDelete', 'Remove them from my list permanently');
define('LNG_RemoveViaTextbox', 'I want to type the email addresses of contacts into a text box');
define('LNG_RemoveViaFile', 'I want to upload a file that contains the email addresses of contacts');
define('LNG_RemoveConfirmDelete', 'Are you sure you want to permanently remove the contacts you\\\'ve selected from your list? This action cannot be undone.');
define('LNG_ShowSupressionsFor', 'Show Suppressed Emails for');
define('LNG_Banned_Choose_Action', 'Please choose how you want to add suppressed emails/domains.');
define('LNG_Subscriber_Ban_Deleted_Many', '%s suppressed email addresses were removed from the \'%s\' list.');
define('LNG_Subscribers_Add_Button', 'Add a Contact to My List...');
define('LNG_SubscribersEditorCustomfieldHeader', 'Custom Field Details');
define('LNG_SubscriberQuickSearch_Description', 'Search by email address...');
define('LNG_SubscriberQuickSearch_ClearSearch', 'Clear Search Results');



/**
 * 5.5.0
 */
define('LNG_EventAdd', 'Log&nbsp;Event');
define('LNG_EventAddTitle', 'Log an Event');
define('LNG_EventEditTitle', 'Edit an Event');
define('LNG_ChooseAnAction', 'Choose an Action');
define('LNG_Date', 'Date');
define('LNG_Notes', 'Notes');
define('LNG_EventType', 'Event&nbsp;Type');
define('LNG_SelectAnEventType', 'Please select an event type.');
define('LNG_Add_Event', 'Add Event');
define('LNG_ConfirmEventDelete', 'Are you sure you want to delete this event?');
define('LNG_EventSpecifyDate', 'Could not parse date, specify the date in dd/mm/yyyy format.');
define('LNG_EventSpecifyTime', 'Could not parse time, specify the time in hh:nn am/pm format.');
define('LNG_ToggleEvents', 'Click here to toggle event logs for this contact');
define('LNG_EnterEventSubject', 'Please enter a subject.');
define('LNG_SubscriberEvents', 'Event Log');
define('LNG_SubscriberEvents_Intro', 'The event log for this contact is shown below.');
define('LNG_SubscriberEvents_Intro_AddEvent', ' Click <em>Log Event</em> to log a new event.');
define('LNG_ConfirmMultipleEventDelete', 'Are you sure you want to delete the selected events?');
define('LNG_SubscriberEventsEmpty', 'There are no events logged for this contact. Click the button below to add one.');
define('LNG_CreatedBy', 'Created By');
define('LNG_LastUpdated', 'Last Updated');
define('LNG_LogEvent', 'Log Event...');
define('LNG_SelectAnEvent', 'Please select an event first.');
define('LNG_AddtoGoogleCalendar', 'Click here to add a follow up reminder to your Google calendar');
define('LNG_FollowUp', 'Follow Up');
define('LNG_GoogleCalendarDescription', LNG_FollowUp);
define('LNG_GoogleCalendarCaption', 'Add to Google Calendar');
define('LNG_Where', 'Where');
define('LNG_What', 'What');
define('LNG_When', 'When');
define('LNG_AllDay', 'All Day');
define('LNG_to', 'to');
define('LNG_Description', 'Description');
define('LNG_GoogleCalendarEnterDescription', 'Please enter a description for this calendar event.');
define('LNG_GoogleCalendarException', 'Unable to save event. Please ensure the date and time are formatted correctly.');
define('LNG_GoogleCalendarAuth', 'Your username and password were rejected. Please double check these settings.');
define('LNG_GoogleCalendarIntro', 'Fill out the form below to add an event to your calendar.');
define('LNG_GoogleCalendarNotEnabled', 'To add an event to Google Calendar you must enter your login details in your Google Calendar Settings.');
define('LNG_AddToGoogleCalendar', 'Add to my Google Calendar');
define('LNG_GoogleCalendarUnabletoSave', 'Unable to save Google Calendar event. An error occurred: %s');



/**
 * 5.7.1
 */
define('LNG_RequireFieldNotLinked', 'The following required custom fields are not linked or not available: <strong>%s</strong>. We cannot proceed until all the required custom fields are linked.');



/*
 * 6.0.2
 */
define('LNG_ShowFilteringOptionsLabel', 'Filter selected contacts?');
define('LNG_ShowFilteringOptionsExplanation', 'Yes, I would like to filter the contacts from the selected list(s).');