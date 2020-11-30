<?php
/**
* Language file variables for the autoresponders area.
*
* @see GetLang
*
* @version     $Id: autoresponders.php,v 1.39 2008/02/15 06:07:47 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the autoresponders area... Please backup before you start!
*/

define('LNG_AutorespondersManage', 'View Autoresponders');

define('LNG_AutoresponderCreate', '&nbsp;Click the "Create Autoresponder" button below to create one.');

define('LNG_Autoresponder_Step1', 'View Autoresponders');
define('LNG_Autoresponder_Step1_CancelPrompt', 'Are you sure you want to cancel managing your autoresponders?');

define('LNG_Autoresponders_Step2', LNG_Autoresponder_Step1);

define('LNG_CreateAutoresponder', 'Create Autoresponder');

define('LNG_CreateAutoresponderCancelButton', 'Are you sure you want to cancel creating a new autoresponder?');
define('LNG_CreateAutoresponderHeading', 'New Autoresponder Details');

define('LNG_CreateAutoresponderIntro_Step4', 'Enter the content of your autoresponder in the form below. Click the "Save & Exit" button when you are finished.');

define('LNG_EditAutoresponderIntro_Step4', 'Please update your content below. Click the "Save & Exit" button when you are finished.');

define('LNG_AutoresponderDetails', 'Autoresponder Details');

define('LNG_EditAutoresponder', 'Edit an Autoresponder');
define('LNG_EditAutoresponderIntro', 'Complete the form below to update the selected autoresponder. You can also <a href="#" onClick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'797\'); return false;">learn more about autoresponders</a>.');
define('LNG_EditAutoresponderCancelButton', 'Are you sure you want to cancel updating this autoresponder?');
define('LNG_EditAutoresponderHeading', 'Autoresponder Details');

define('LNG_EnterAutoresponderName', 'Please enter a name for this autoresponder.');
define('LNG_PleaseEnterAutoresonderSubject', 'Please enter a subject for this autoresponder.');

define('LNG_AutoresponderName', 'Name this Autoresponder');

define('LNG_AutoresponderNameIsNotValid', 'Autoresponder Name is not Valid');
define('LNG_UnableToCreateAutoresponder', 'Unable to create autoresponder');

define('LNG_DeleteAutoresponderPrompt', 'Are you sure you want to delete this autoresponder?');

define('LNG_UnableToUpdateAutoresponder', 'Unable to update autoresponder');

define('LNG_AutoresponderDeleteFail', 'Unable to delete the autoresponder');
define('LNG_AutoresponderDeleteSuccess', 'Autoresponder deleted successfully');

define('LNG_AutoresponderFormat', 'Autoresponder Format');
define('LNG_HLP_AutoresponderFormat', 'What format should this autoresponder be?');
define('LNG_AutoresponderContent', 'Enter your autoresponder content below');

define('LNG_AutoresponderCopySuccess', 'The selected autoresponder has been copied and can be edited below.');
define('LNG_AutoresponderCopyFail', 'Autoresponder was not copied successfully.');

define('LNG_AutoresponderSubject', 'Email Subject');

define('LNG_HLP_AutoresponderSubject', 'The subject of the autoresponder email. You can include custom fields in the subject simply by copying the placeholder and placing it in the text box.');

define('LNG_Autoresponder_Edit_Disabled', 'You cannot edit this autoresponder, you do not have access.');
define('LNG_Autoresponder_Copy_Disabled', 'You cannot copy this autoresponder, you do not have access.');
define('LNG_Autoresponder_Delete_Disabled', 'You cannot delete this autoresponder, you do not have access.');

define('LNG_UnableToLoadAutoresponder', 'Unable to load autoresponder. Please try again.');

define('LNG_MatchEmail', 'Match Email');

define('LNG_MatchFormat', 'Match Format');

define('LNG_MatchConfirmedStatus', 'Match Confirmed Status');


define('LNG_SendMultipart', 'Send Your Email as Multipart?');
define('LNG_SendMultipartExplain', 'Yes, send the email as multipart');

define('LNG_TrackOpens', 'Track Open Rates for HTML Emails?');
define('LNG_TrackOpensExplain', 'Yes, track opening of HTML emails');

define('LNG_TrackLinks', 'Track Links Clicked in this Email?');
define('LNG_HLP_TrackLinks', 'Do you want to track all link clicks in this email campaign? If so, you will be able to view reports on link clicks from the statistics tab at the top of the page.');
define('LNG_TrackLinksExplain', 'Yes, track all links in this email campaign');

define('LNG_EmailFormat', 'Email Format');

define('LNG_AutoresponderFile', 'Autoresponder File');
define('LNG_HLP_AutoresponderFile', 'Upload a html file from your computer to use as your autoresponder');
define('LNG_UploadAutoresponder', 'Upload');
define('LNG_AutoresponderFileEmptyAlert', 'Please choose a file from your computer before trying to upload it.');
define('LNG_AutoresponderFileEmpty', 'Please choose a file from your computer before trying to upload it.');

define('LNG_AutoresponderURL', 'Autoresponder URL');
define('LNG_HLP_AutoresponderURL', 'Import a autoresponder from a url');
define('LNG_ImportAutoresponder', 'Import');
define('LNG_AutoresponderURLEmptyAlert', 'Please enter a url to import the autoresponder from');
define('LNG_AutoresponderURLEmpty', 'Please enter a url to import the autoresponder from');

define('LNG_AutoresponderActivatedSuccessfully', 'The selected autoresponder is now active.');
define('LNG_AutoresponderDeactivatedSuccessfully', 'The selected autoresponder is no longer active.');

define('LNG_Autoresponder_Title_Enable', 'Enable this autoresponder');
define('LNG_Autoresponder_Title_Disable', 'Disable this autoresponder');

define('LNG_ChooseAutoresponders', 'Please choose at least one autoresponders first.');
define('LNG_ActivateAutoresponders', 'Activate the selected autoresponder(s)');
define('LNG_DeactivateAutoresponders', 'Deactivate the selected autoresponder(s)');

define('LNG_Autoresponder_Approved', 'The selected autoresponder has been activated.');
define('LNG_Autoresponders_Approved', 'The %s selected autoresponders have been activated.');

define('LNG_Autoresponder_NotApproved', 'The selected autoresponder couldn\'t be activated. Please try again.');
define('LNG_Autoresponders_NotApproved', 'The %s selected autoresponders couldn\'t be activated. Please try again.');

define('LNG_Autoresponder_Disapproved', 'The selected autoresponder has been deactivated.');
define('LNG_Autoresponders_Disapproved', 'The %s selected autoresponders have been deactivated.');

define('LNG_Autoresponder_NotDisapproved', '1 autoresponder was not disapproved. Please try again.');
define('LNG_Autoresponders_NotDisapproved', '%s autoresponders were not disapproved. Please try again.');

define('LNG_Autoresponder_Deleted', 'The selected autoresponder has been deleted.');
define('LNG_Autoresponders_Deleted', 'The %s selected autoresponders have been deleted.');

define('LNG_Autoresponder_NotDeleted', '1 autoresponder was not deleted. Please try again.');
define('LNG_Autoresponders_NotDeleted', '%s autoresponders were not deleted. Please try again.');

define('LNG_Autoresponder_Details', 'Autoresponder Details');

define('LNG_Autoresponder_OpenedNewsletter', LNG_OpenedNewsletter);
define('LNG_Autoresponder_YesFilterByOpenedNewsletter', LNG_YesFilterByOpenedNewsletter);

define('LNG_Autoresponder_ClickedOnLink', LNG_ClickedOnLink);
define('LNG_Autoresponder_YesFilterByLink', LNG_YesFilterByLink);

define('LNG_ChooseATime', 'Choose a time');
define('LNG_1Day', '1 Day');
define('LNG_2Days', '2 Days');
define('LNG_3Days', '3 Days');
define('LNG_4Days', '4 Days');
define('LNG_5Days', '5 Days');
define('LNG_6Days', '6 Days');
define('LNG_1Week', '1 Week');
define('LNG_2Weeks', '2 Weeks');
define('LNG_3Weeks', '3 Weeks');
define('LNG_1Month', '1 Month');
define('LNG_2Months', '2 Months');
define('LNG_3Months', '3 Months');
define('LNG_4Months', '4 Months');
define('LNG_5Months', '5 Months');
define('LNG_6Months', '6 Months');
define('LNG_7Months', '7 Months');
define('LNG_8Months', '8 Months');
define('LNG_9Months', '9 Months');
define('LNG_10Months', '10 Months');
define('LNG_11Months', '11 Months');
define('LNG_1Year', '1 Year');
define('LNG_2Years', '2 Years');
define('LNG_3Years', '3 Years');

/**
**************************
* Changed/added in NX1.0.5
**************************
*/
define('LNG_HoursDelayed', 'Send this Autoresponder');

define('LNG_TemplateDetails','Autoresponder Content');

/**
**************************
* Changed/added in NX1.1.1
**************************
*/
define('LNG_AutoresponderFilesCopyFail', 'The images and/or attachments for this autoresponder were not copied successfully.');

/**
**************************
* Changed/added in NX 1.3
**************************
*/
define('LNG_AutoresponderActivateFailed_HasAttachments', 'The autoresponder could not be activated because the administrator has disabled attachments. To enable this autoresponder, please edit it and remove the attachments.');
define('LNG_AutoresponderActivateFailed_HasAttachments_Multiple', '%s autoresponders could not be activated because the administrator has disabled attachments. To enable these autoresponders, please edit them and remove the attachments.');

define('LNG_UnableToLoadImage_Autoresponder_List_Embed', 'Unable to load the following images to embed in the autoresponder. This is most likely because the image does not exist.<br/>%s');
define('LNG_UnableToLoadImage_Autoresponder_List', 'Unable to load the following images in the autoresponder. This is most likely because the image does not exist.<br/>%s');

define('LNG_AutoresponderUpdated', 'Autoresponder updated successfully. %s');

define('LNG_AutoresponderCreated', 'Your autoresponder email has been created successfully. %s');

define('LNG_FormatDetails', 'Advanced Options');
// removed
// define('LNG_HTMLFormatDetails','HTML Format Details');

define('LNG_CreateAutoresponderButton', 'Create an Autoresponder...');


define('LNG_Autoresponder_Size_Over_EmailSize_Maximum_Embed', 'This autoresponder is larger than %s. You will not be able to activate this autoresponder until you reduce the size of the images or attachments, or you can send it without embedded images.');
define('LNG_Autoresponder_Size_Over_EmailSize_Maximum_No_Embed', 'This autoresponder is larger than %s. You will not be able to activate this autoresponder until you reduce the size of the attachments.');

define('LNG_AutoresponderActivateFailed_Over_EmailSize_Maximum_Embed', 'The autoresponder could not be activated because it is larger than %s. You will need to reduce the size of the images or attachments, or you can edit it and disable embedded images.');
define('LNG_AutoresponderActivateFailed_Over_EmailSize_Maximum_Embed_Multiple', '%s autoresponders could not be activated because each one is larger than %s. You will need to reduce the size of the images or attachments, or you can edit each one and disable embedded images.');

define('LNG_AutoresponderActivateFailed_Over_EmailSize_Maximum', 'The autoresponder could not be activated because it is larger than %s. You will need to reduce the size of the attachments.');
define('LNG_AutoresponderActivateFailed_Over_EmailSize_Maximum_Multiple', '%s autoresponders could not be activated because each one is larger than %s. You will need to reduce the size of the attachments.');

define('LNG_Autoresponder_Title_Disable_Too_Big', 'You cannot activate this autoresponder because it is too big.');

// this needs to have no single quotes in it.
define('LNG_Autoresponder_Title_Disable_Too_Big_Alert', LNG_Autoresponder_Title_Disable_Too_Big);

define('LNG_FilterOptions_Autoresponders', 'Search Options');
define('LNG_ShowFilteringOptions_Autoresponders', 'This Autoresponder Should Go to');

define('LNG_AutoresponderFilterDetails', 'Filter by Basic Details');

define('LNG_EditAutoresponderIntro_Step3', LNG_EditAutoresponderIntro);


/**
**************************
* Changed/added in NX 5.0
**************************
*/

define('LNG_HLP_AutoresponderName', 'Enter a name for this autoresponder. This name will be used to identify the autoresponder in the control panel and will not be shown to your contacts.');

define('LNG_AutoresponderIncludeExisting', 'Send to Existing Contacts?');

define('LNG_AutoresponderIncludeExistingExplain', 'Yes, send this autoresponder to existing contacts');

define('LNG_HLP_MatchEmail', 'Autoresponders will only be sent to contacts that match this email address. You can specify all or part of an email address. For example, to send to all hotmail email addresses, you can use \\\'@hotmail.com\\\'. To send to all email addresses, simply leave this blank.');

define('LNG_HLP_MatchFormat', 'Autoresponders will only be sent to contacts that have selected this subscription format. If you select \\\'HTML\\\' then this autoresponder will only be sent out to users that have selected \\\'HTML\\\' as their preferred format when subscribing to your contact list.');

define('LNG_HLP_MatchConfirmedStatus', 'Autoresponders will only be sent to contacts that have confirmed their email subscription. When using double-optin subscription, your contacts will be sent an email to confirm their subscription. If they have confirmed their subscription, then their status will be \\\'confirmed\\\'. It\\\'s usually best to only email confirmed contacts.');

define('LNG_HLP_SendMultipart', 'Sending a multipart email will let the contacts email program decide which format (HTML or Text) to display the email in.<br/><br/>It is best to use this if you don\\\'t give your contacts a choice to which format they receive (e.g. they all subscribe as HTML), when they receive the email their email software (eg. Outlook) will automatically show the correct format.<br/><br/>If unsure, leave this option ticked.');

define('LNG_HLP_TrackOpens', 'Do you want to track opening of emails when a contact receives an email campaign? If so, you will be able to view reports from the statistics tab at the top of the page. This applies to HTML newsletters only.');

define('LNG_HLP_EmailFormat', 'How will this autoresponder be composed and sent? Select HTML if you want to include colored text, images, tables and other HTML elements. Choose text to create and send your autoresponder in plain-text. Alternatively, you can choose \\\'Both HTML and Text\\\' to create 2 versions of your autoresponder. Contacts who can view HTML will see the HTML version. Those that can\\\'t will see the plain-text version only.');

define('LNG_AutoresponderHasBeenDisabled', 'To prevent an incomplete autoresponder from being sent to your contacts, it has been marked as inactive. To activate your autoresponder, click the \'X\' in the active column.');

define('LNG_AutoresponderHasBeenDisabled_Save', 'To prevent an incomplete autoresponder from being sent to contacts, it has been marked as inactive.<br>You will need to activate this autoresponder when you go to the "Manage Autoresponders" page.');

define('LNG_HLP_Autoresponder_OpenedNewsletter', 'This option will allow you to filter contacts who have opened a particular email campaign sent to this contact list. If selected, only contacts who have opened the chosen email will be sent this autoresponder. To send to all contacts, leave this option unticked.');

define('LNG_HLP_Autoresponder_ClickedOnLink', 'This option will allow you to filter contacts who have clicked a particular link in an email campaign sent to this contact list. If selected, only contacts who have clicked the chosen link will be sent this autoresponder. To send to all contacts, leave this option unticked.');

define('LNG_HLP_HoursDelayed', 'How long after joining your list should a contact receive this autoresponder? Choose \\\'As soon as the contact joins my list\\\' to send the autoresponder to contacts as soon as they join your list.<br /><br />Choose \\\'After the contact has been on my list for\\\' to send the autoresponder to new contacts after the specified time period has elapsed.');

define('LNG_Autoresponder_Size_Approximate', 'This autoresponder will be approximately %s per contact.');



define('LNG_Autoresponder_Size_Over_EmailSize_Warning_Embed', 'This autoresponder is larger than %s which means it may take a while for your contacts to download. If you do not embed images, this will reduce the size of the email your contacts receive.');
define('LNG_Autoresponder_Size_Over_EmailSize_Warning_No_Embed', 'This autoresponder is larger than %s which means it may take a while for your contacts to download.');

define('LNG_AutorespondersShowFilteringOptionsOneListExplain', 'Only contacts who match my search criteria (below)');
define('LNG_AutorespondersDoNotShowFilteringOptionsOneListExplain', 'All contacts in my list with a status of "confirmed"');


define('LNG_AutoresponderAlreadySentTo', "*** IMPORTANT **** \n\nThis autoresponder has already sent to %s contacts. If you leave this option enabled, all of those contacts will receive this autoresponder again. If you do not want them to receive this autoresponder again, simply untick this option.\n\nNew contacts who join your contact list through a form on your website or who are added through the 'Add Contacts' option will automatically get this autoresponder.");

define('LNG_NoAutoresponders', 'No autoresponders have been created for this contact list. Click the <em>Create an Autoresponder...</em> button below to create one.');

define('LNG_AutoresponderAssign', '&nbsp;Please talk to the system administrator to assign you a contact list.');


define('LNG_HLP_AutoresponderIncludeExisting', 'Choose this option to send the autoresponder to both new and existing contacts for the selected contact list.<br /><br/>This option will not be remembered next time you edit this autoresponder. It will only add the existing contacts to the list of recipients for the next time autoresponders are sent.');


define('LNG_Autoresponder_Step1_Intro', 'Autoresponders are emails that you can set up to be sent automatically to contacts at different intervals after they subscribe. <a href="#" onClick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'797\'); return false;">Learn more about autoresponders</a>.');

define('LNG_Help_AutorespondersManage', LNG_Autoresponder_Step1_Intro);

define('LNG_CreateAutoresponder_Step1_Intro', 'Creating an autoresponder is a multi-step process. Start by entering a name and choosing who should receive it and then click the <em>Next &gt;&gt;</em> button.  <a href="#" onClick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'797\'); return false;">Get help on autoresponders here</a>.');

define('LNG_CreateAutoresponderIntro', LNG_CreateAutoresponder_Step1_Intro);

define('LNG_CreateAutoresponderIntro_Step3', LNG_Autoresponder_Step1_Intro);

define('LNG_AutoresponderCronNotEnabled', 'Before your contacts can receive autoresponders, you need to setup scheduled sending. Please see <a href="#" onclick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'841\'); return false;">this article</a> to learn how, or contact your administrator.');

define('LNG_Autoresponder_Name_Reference', 'This name is for your reference only and isn\'t shown to anyone');
define('LNG_Autoresponder_Filter_Help', 'Fill in the fields below to filter contacts. You don\'t have to fill in all fields, only the ones you want to filter on. <a onclick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'832\'); return false;" href="#">Learn more about filtering contacts.</a>');

define('LNG_SchedulingDetails', 'Sending Options');
define('LNG_Autoresponder_Valid_Time', 'Please enter a valid number for the time delay.');

define('LNG_Autoresponder_Send_ASAP', 'As soon as the contact joins my list');
define('LNG_Autoresponder_Send_Custom', 'After the contact has been on my list for');

define('LNG_Autoresponder_Period_Hours', 'hour(s)');
define('LNG_Autoresponder_Period_Days', 'day(s)');
define('LNG_Autoresponder_Period_Weeks', 'week(s)');
define('LNG_Autoresponder_Period_Months', 'month(s)');
define('LNG_Autoresponder_Period_Years', 'year(s)');

define('LNG_Autoresponder_I_Want_To', 'I Want to');
define('LNG_Autoresponder_From_Scratch', 'Create the content of my autoresponder from scratch');
define('LNG_Autoresponder_From_Tpl', 'Use a template as the basis of my autoresponder');

define('LNG_DeleteAutoresponders', 'Delete the selected autoresponder(s)');

/**
**************************
* Changed/added in 5.6.6
**************************
*/

defined('LNG_NoAttachment_Admin') or define('LNG_NoAttachment_Admin', 'Attachments have been disabled. They can be enabled in the Email Settings');
defined('LNG_NoAttachment_User') or define('LNG_NoAttachment_User', 'Attachments have been disabled by the administrator');

?>