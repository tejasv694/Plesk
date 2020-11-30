<?php

define('LNG_UserDetails', 'User Details');
define('LNG_UserAdd', 'Create a User Account...');
define('LNG_UserName', 'Username');
define('LNG_FullName', 'Full Name');
define('LNG_UserType', 'User Type');
define('LNG_EditUser', 'Edit a User Account');
define('LNG_Help_EditUser', 'Use the form below to make changes to a user account. The user must logout and log back in before changes to permissions take effect.');
define('LNG_Admin', 'Administrator');
define('LNG_YesIsActive','Yes, this user is active');
define('LNG_SupplyUserPassword', 'Please supply a password');
define('LNG_SupplyUserUsername', 'Please supply a username');
define('LNG_UserUpdated', 'The details of the selected user account have been updated.');
define('LNG_UserNotUpdated', 'The details of the selected user account couldn\'t be updated. Please try again.');
define('LNG_UserCreated', 'User has been created successfully.');
define('LNG_UserAlreadyExists', 'A user with that username already exists. Please enter a different username.');
define('LNG_EnterEmailaddress', 'Please enter a valid email address.');
define('LNG_DeleteUserPrompt', 'Are you sure you want to delete this user?');
define('LNG_User_CantDeleteOwn', 'You cannnot delete your own user account.');
define('LNG_UserDeleted', 'The selected user accounts have been deleted successfully.');
define('LNG_Help_DisplayMyAccount','Your details are shown below. Please contact your administrator to update any information');
define('LNG_HLP_Active', 'An inactive user will still exist in the system but will not be able to login. This can be used to temporarily suspend access to a particular user.');
define('LNG_HLP_AdministratorType', 'Choose which areas this particular user has access to.<br/>You can choose from the default types of administrators or choose custom permissions.');
define('LNG_RegularUser', 'Regular User');
define('LNG_CreateUser', 'Create User');
define('LNG_EditOwnSettings', 'Edit Own Settings');
define('LNG_HLP_EditOwnSettings', 'Should this user be able to edit his/her own account settings? They will be able to edit everything except permissions.');
define('LNG_YesEditOwnSettings', 'Yes, let this user edit their own settings');
define('LNG_ShowInfoTips', 'Enable Built-In Helps');
define('LNG_HLP_ShowInfoTips', 'If yes, this client will be able to access the help links to knowledge base and see email marketing tips across the top of the screen when they login. Each tip links to an article with more information.');
define('LNG_YesShowInfoTips', 'Yes, enable help and knowledge base links');
define('LNG_TimeZone', 'User Timezone');
define('LNG_HLP_TimeZone', 'The timezone where your client is located. When your client is viewing reports and stats, all times and dates will be converted to their time zone.');
define('LNG_UserRestrictions', 'User Restrictions');
define('LNG_TextFooter', 'Text Footer');
define('LNG_HLP_TextFooter', 'Any text you type here will be added to the end of every text-based email that this client sends.');
define('LNG_HTMLFooter', 'HTML Footer');
define('LNG_HLP_HTMLFooter', 'Any text you type here will be added to the end of every HTML-based email that this client sends.');
define('LNG_HLP_EmailAddress', 'If your client forgets his/her password, it will be sent to this email address.');
define('LNG_AccessPermissions', 'Access Permissions');
define('LNG_AdministratorType_SystemAdministrator', 'System Administrator');
define('LNG_AdministratorType_ListAdministrator', 'List Administrator');
define('LNG_AdministratorType_NewsletterAdministrator', 'Email Campaign Administrator');
define('LNG_AdministratorType_TemplateAdministrator', 'Template Administrator');
define('LNG_AdministratorType_UserAdministrator', 'User Administrator');
define('LNG_AdministratorType_RegularUser', 'Regular User');
define('LNG_AdministratorType_', '(unknown)');
define('LNG_AdministratorType_Custom', 'Custom');
define('LNG_TemplateAdministratorType_AllTemplates', 'All templates');
define('LNG_OtherPermissions', 'Other Permissions');
define('LNG_Delete_User_Selected', 'Delete Selected');
define('LNG_ChooseUsersToDelete', 'Please choose at least one user account to delete first.');
define('LNG_ConfirmRemoveUsers', 'Are you sure you want to delete the selected users? This action cannot be undone.');
define('LNG_UserDeleteFail', 'User \'%s\' could not be deleted: %s');
define('LNG_UserDeleteSuccess_One', 'The selected user account has been deleted.');
define('LNG_UserDeleteSuccess_Many', 'The %s selected user accounts have been deleted.');
define('LNG_User_Delete_Disabled', 'You cannot delete this user.');
define('LNG_User_Delete_Own_Disabled', 'You cannot delete your own user account.');



/**
 * 1.0.7
 */
define('LNG_HLP_User_SMTP', 'A user can edit their own smtp settings if they can manage their account.');



/**
 * 1.3.0
 */
define('LNG_Help_Users', 'Each user account has their own contact lists, contacts and email campaigns which are separate from the other users. Click <em>Create a User Account...</em> to create a new account.');
define('LNG_Help_CreateUser', 'Each user account has their own separate contact lists, contacts and email campaigns. You can also set different permissions for each user.');
define('LNG_UseWysiwygEditor', 'Use the WYSIWYG Editor');
define('LNG_YesUseWysiwygEditor', 'Yes, use the WYSIWYG editor');
define('LNG_HLP_UseWysiwygEditor', 'This should only be disabled if the user is an advanced user. If it is disabled, the user will be given a text area to use instead of the wysiwyg editor.');
define('LNG_UseWysiwygXHTML', 'Create XHTML output');
define('LNG_YesUseXHTML', 'Yes, create XHTML output in the WYSIWYG editor');
define('LNG_HLP_UseWysiwygXHTML', 'Disable XHTML formatting for WYSIWYG editor. Currently this settings will only affect formatting for Microsoft Internet Explorer. If you don\\\'t know what it is used for, it is best to leave it ticked.');
define('LNG_UserSettings_Heading', 'User Settings');
define('LNG_UserRestrictions_Heading', 'User Restrictions');
define('LNG_UserPermissions_Heading', 'User Permissions');



/**
 * 1.4.0
 */
define('LNG_UseXMLAPI', 'Enable the XML API');
define('LNG_YesUseXMLAPI', 'Yes, allow this user to use the XML API');
define('LNG_XMLToken', 'XML Token');
define('LNG_HLP_XMLToken', 'This token is what the user needs to include in their XML requests. If this token is not present in the XML request or does not match what is set here, the request will fail.');
define('LNG_XMLToken_Regenerate', 'Regenerate XML API Token');
define('LNG_XMLPath', 'XML Path');
define('LNG_HLP_XMLPath', 'This is the path to the file where all XML API requests should be sent.');
define('LNG_UserPerHourOverMaxHourlyRate', 'The system has been limited to sending %s emails per hour. You have set this user to send %s emails per hour, however they will be restricted to sending the maximum set for the system.');
define('LNG_UserPerHour_Unlimited', 'unlimited');
define('LNG_LastActiveUser', 'You cannot delete this user or make them inactive. You need at least one active user who is a system administrator.');
define('LNG_LastUser', 'You cannot delete this user. You need at least one user who is a system administrator.');
define('LNG_LastAdminUser', 'You cannot delete this user or change their permissions. You need at least one user who is a system administrator.');
define('LNG_UserNotCreated_License', 'You have reached your license key limit and cannot create any more users. Please update your license key before trying to create any more users.');



/**
 * 1.4.3
 */
define('LNG_XMLUsername', 'XML Username');
define('LNG_HLP_XMLUsername', 'The username used in the XML API requests.');



/**
 * 5.0.0
 */
define('LNG_HLP_UseXMLAPI', 'This allows the user to create lists, templates, newsletters, search for contacts and so on using the XML API. This should only be enabled for advanced users.');
define('LNG_ListAdministratorType_AllLists', 'All contact lists');
define('LNG_Help_MyAccount','These are your personal user details. You can update these so that your contact lists are filled with your details when you create them.');
define('LNG_UserDoesntExist', 'The user you are trying to edit does not exist. Please try again.');



/**
 * 5.0.4
 */
define('LNG_CreateAutoresponderHelp', 'Create Autoresponders');
define('LNG_HLP_CreateAutoresponderHelp', 'Check this box if this user is allowed to create autoresponders (automatic emails which are sent to subscribers after a particular time interval) <b>Please note </b>: The user needs to be assigned list permissions before autoresponder functions are available to that user');
define('LNG_ApproveAutoresponderHelp', 'Approve Autoresponders');
define('LNG_HLP_ApproveAutoresponderHelp', 'Check this box if this user is allowed to activate/deactivate autoresponders. This is used to control whether or not an autoresponder can be used.');
define('LNG_EditAutoresponderHelp', 'Edit Autoresponders');
define('LNG_HLP_EditAutoresponderHelp', 'Check this box if this user is allowed to change the conditions which trigger the sending of an autoresponder or make changes to the layout and message of an autoresponders.');
define('LNG_DeleteAutoresponderHelp', 'Delete Autoresponders');
define('LNG_HLP_DeleteAutoresponderHelp', 'Check this box if this user is allowed to delete autoresponders from ' . LNG_ApplicationTitle . '.');
define('LNG_HLP_CreateForms', 'Check this box if this user is allowed to create a custom web form to add to your website. For instance a subscription form to add to a contact List.');
define('LNG_HLP_EditForms', 'Check this box if this user is allowed to edit the text and layout of existing forms.');
define('LNG_HLP_DeleteForms', 'Check this box if this user is allowed to delete existing forms.');
define('LNG_HLP_CreateMailingLists', 'Check this box if this user is allowed to create new contact lists used to group contacts together.');
define('LNG_HLP_EditMailingLists', 'Check this box if this user is allowed to edit existing contact List details.');
define('LNG_HLP_MailingListsBounce', 'Check this box if this user is allowed to process bounced emails. By doing so emails which have been bounced are marked accordingly and are removed from campaigns.');
define('LNG_HLP_MailingListsBounceSettings', 'Check this box if this user is allowed to edit existing bounce handling details. For instance mail server addresses and the accounts bounced emails are sent to.');
define('LNG_HLP_DeleteMailingLists', 'Check this box if this user is allowed to delete existing contact lists.');
define('LNG_HLP_SegmentViewPermission', 'Check this box if this user is allowed to view existing list segments. A segment is a filtered view for one or more of your contact lists. You can view contacts by segments and even send campaigns to a specific segment.');
define('LNG_HLP_SegmentSendPermission', 'Check this box if this user is allowed to use a list segment to send emails.');
define('LNG_HLP_SegmentCreatePermission', 'Check this box if this user is allowed to create new segment Lists.');
define('LNG_HLP_SegmentEditPermission', 'Check this box if this user is allowed to edit existing segments and/or change the rules for segment creation.');
define('LNG_HLP_SegmentDeletePermission', 'Check this box if this user is allowed to edit existing segments.');
define('LNG_HLP_CreateCustomFields', 'Check this box if this user is allowed to create custom fields. A custom field is used to store additional information about contacts such as their phone number, or even advanced information such as their location or favorite colour.');
define('LNG_HLP_EditCustomFields', 'Check this box if this user is allowed to edit existing custom fields.');
define('LNG_HLP_DeleteCustomFields', 'Check this box if this user is allowed to delete existing custom fields.');
define('LNG_HLP_CreateNewsletters', 'Check this box if this user is allowed to create a new email campaign. The user needs further permissions to authorise or send an email campaign.');
define('LNG_HLP_ApproveNewsletters', 'Check this box if this user is allowed to approve an existing email campaign. This means the user is allowed to <b>activate</b> an email campaign for a scheduled send.');
define('LNG_HLP_EditNewsletters', 'Check this box if this user is allowed to edit the layout and message of an existing email campaign.');
define('LNG_HLP_SendNewsletters', 'Check this box if this user is allowed to send an existing email campaign. A user must also been an <b>email campaign administrator</b> to be able to send an email campaign. The email campaign must be approved (activated) before a send can proceed.');
define('LNG_HLP_DeleteNewsletters', 'Check this box if this user is allowed to delete an email campaign.');
define('LNG_HLP_ManageSubscribers', 'Check this box if this user is allowed to view the details of contacts in ' . LNG_ApplicationTitle . '.');
define('LNG_HLP_ImportSubscribers', 'Check this box if this user is allowed to import data (from local files) into ' . LNG_ApplicationTitle . ' to automatically add contacts.');
define('LNG_HLP_AddSubscribers', 'Check this box if this user is allowed to create a new contact and add it to a contact list.');
define('LNG_HLP_ExportSubscribers', 'Check this box if this user is allowed to export the information from contact lists to a file for downloading (.CSV or .XML files are generated).');
define('LNG_HLP_EditSubscribers', 'Check this box if this user is allowed to alter the details of contacts.');
define('LNG_HLP_ManageBannedSubscribers', 'Check this box if this user is allowed to view email suppression lists. Contacts in a suppression list are marked so as they never receive an email from an email campaign or an autoresponder.');
define('LNG_HLP_DeleteSubscribers', 'Check this box if this user is allowed to delete contacts from ' . LNG_ApplicationTitle . '.');
define('LNG_HLP_EventDelete', 'Check this box if this user is allowed to delete an event associated with a contact.');
define('LNG_HLP_EventAdd', 'Check this box if this user is allowed to add an event associated with a contact. An event is additional information for a contact such as storing the time and details of a phone call with a contact or noting the time and particulars of an invoice sent to a contact.');
define('LNG_HLP_EventEdit', 'Check this box if this user is allowed to edit the details of an event associated with a contact.');
define('LNG_HLP_CreateTemplates', 'Check this box if this user is allowed to create custom templates which can be used to format the presentation of your email campaigns and autoresponders.');
define('LNG_HLP_ApproveTemplates', 'Check this box if this user is allowed to activate/deactivate custom templates so as they can be used to create email campaigns or autoresponders.');
define('LNG_HLP_EditTemplates', 'Check this box if this user is allowed to change the design and wording of existing custom templates.');
define('LNG_HLP_GlobalTemplates', 'Check this box if this user is allowed to make a custom template global. Making a template global means other users can access and use that template');
define('LNG_HLP_DeleteTemplates', 'Check this box if this user is allowed to delete existing custom templates.');
define('LNG_HLP_BuiltInTemplates', 'Check this box if this user is allowed to select a built in template provided by ' . LNG_ApplicationTitle . ' and use this as the basis for a a new custom template.');
define('LNG_HLP_NewsletterStatistics', 'Check this box if this user is allowed to view email campaign statistics. email campaign statistics include open, bounce, unsubscribe and click-through rate summaries.');
define('LNG_HLP_UserStatistics', 'Check this box if this user is allowed to view statistics about all users. user statistics show an overview of users configured in your ' . LNG_ApplicationTitle . ' system. If this user is not a user administrator, they will only see their own statistics.');
define('LNG_HLP_AutoresponderStatistics', 'Check this box if this user is allowed to view statistics for autoresponders setup in ' . LNG_ApplicationTitle . '.');
define('LNG_HLP_ListStatistics', 'Check this box if this user is allowed to view summary information about contact lists.');
define('LNG_HLP_ListAdministrator', 'A List Administrator is able to perform certain functions for <i><u>all</u></i> contact lists, including editing and deleting.');
define('LNG_HLP_SystemAdministrator', 'A System Administrator can access the settings page as well as all Lists and all users.');
define('LNG_HLP_TemplateAdministrator', 'A Template Administrator can perform certain functions for <i><u>all</u></i> templates, including editing and deleting.');
define('LNG_HLP_UserAdministrator', 'A User Administrator can add, delete and edit user accounts in ' . LNG_ApplicationTitle . '.');
define('LNG_HLP_SettingsShowSMTPCOM', 'Tick this option if you would like users to be able to choose SMTP.com as an option when sending emails. SMTP.com provides mail servers for those who want to send a large amount of emails. Click the \\\'Email Settings\\\' tab above for more information on SMTP.com.');



/**
 * 5.0.6
 */
define('LNG_ViewKB_ExplainDefaultFooter', '<a href="#" onclick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'847\'); return false;">How do I change the default footer?</a>');



/**
 * 5.5.0
 */
define('LNG_EventTypeList','Event Types');
define('LNG_HLP_EventTypeList','A list of event types which can be used when logging an event for a contact.<br /><br />Enter the event types in this text box, one per line.');
define('LNG_Permissions_Addons', 'Addon Permissions');
define('LNG_GoogleSettings_Heading','Google Calendar Settings');
define('LNG_GoogleCalendarIntro','Google Calendar Details');
define('LNG_GoogleCalendarUsername','Google Calendar Username');
define('LNG_HLP_GoogleCalendarUsername','Enter the username that you use to access your Google Calendar here. All date fields when viewing/editing contacts will appear with an icon that allows you to add a follow up reminder to your Google Calendar.');
define('LNG_HLP_GoogleCalendarPassword','Enter the password that you use to access your Google Calendar here. All date fields when viewing/editing contacts will appear with an icon that allows you to add a follow up reminder to your Google Calendar.');
define('LNG_GoogleCalendarIntroText','Enter your Google Calendar details below and all date fields when viewing/editing contacts will appear with an icon that allows you to add a follow up reminder to your calendar.');
define('LNG_GoogleCalendarPassword','Google Calendar Password');
define('LNG_TestLogin','Test Login');
define('LNG_EnterGoogleCalendarPassword','Please enter a Google Calendar password.');
define('LNG_EnterGoogleCalendarUsername','Please enter a Google Calendar username.');
define('LNG_GoogleCalendarTestLogin','Google Calendar Login Test');
define('LNG_GooglecalendarTestSuccess','The login details you provided for Google Calendar are correct.');
define('LNG_GooglecalendarTestFailure','The login details you provided for Google Calendar are incorrect. Please double check them and try again.');
define('LNG_GooglecalendarTestError', 'Unable to test your login details. Please try again later.');
define('LNG_HLP_Permissions_Triggeremails_Create', 'Check this box if this user is allowed to create triggers');
define('LNG_HLP_Permissions_Triggeremails_Edit', 'Check this box if this user is allowed to edit triggers');
define('LNG_HLP_Permissions_Triggeremails_Delete', 'Check this box if this user is allowed to delete triggers');
define('LNG_HLP_Permissions_Triggeremails_Activate', 'Check this box if this user is allowed to approve triggers');
define('LNG_HLP_Permissions_Triggeremails_Statistics', 'Check this box if this user is allowed to view triggers statistics');
define('LNG_EnterPassword', 'Please enter a password (must be at least 3 characters long).');
define('LNG_EnterUsername', 'Please enter a username (must be at least 3 characters long).');
define('LNG_EnableActivityLog', 'Recent Activity Log');
define('LNG_YesEnableActivityLog', 'Yes, track my recent activity');
define('LNG_HLP_EnableActivityLog', 'When enabled, the application will display your recent activity accross the top of your page.');



/**
 * Changed/Added in 5.6.0
 */
define('LNG_LoginAsUser', 'Login');
define('LNG_UserCreatedOn', 'Created&nbsp;On');
define('LNG_LastLoggedIn', 'Last&nbsp;Login');
define('LNG_UserStatusColumn', 'Status');
define('LNG_UserDeletePopDown', 'Delete Account(s)... <img alt="&nabla;" src="images/arrow_blue.gif" />');
define('LNG_UserDeleteNoData_Summary', 'Delete selected user account(s), but keep all the data owned by the account.');
define('LNG_UserDeleteWithData_Summary', 'Delete selected user account(s) along WITH all the data owned by the account.');
define('LNG_ConfirmRemoveUsersWithData', 'Are you sure you want to delete the selected users AND their data? This action cannot be undone.');


/**
 * 5.7.0
 */
define('LNG_UserDeleteNoData', 'Keep User Data');
define('LNG_UserDeleteWithData', 'Delete User Data');
define('LNG_UserType_NormalUser', 'Regular User Account');
define('LNG_UserType_TrialUser', 'Free Trial Account');
define('LNG_AdministratorType_TrialUser', LNG_UserType_TrialUser);
define('LNG_HLP_UserType', "What kind of user account would you like to create? Choose '" . LNG_UserType_NormalUser . "' to create a regular user account which doesn't expire and over which you have full control. Choose '" . LNG_UserType_TrialUser . "' to create a trial account which expires after %%IEM_SYSTEM_LICENSE_TRIALUSER_TRIALDAYS%% days and has a limit of %%IEM_SYSTEM_LICENSE_TRIALUSER_EMAILLIMIT%% total emails during the free trial period.");
define('LNG_AdminNotifications_Heading', 'Admin Notifications');
define('LNG_AdminNotifications_SubHeading', 'Activity-Based Notification Emails');
define('LNG_AdminNotifications_EmailInstruction', '(Separate multiple email addresses with commas)');
define('LNG_AdminNotifications_Notify_Send', 'Notify of Large Send?');
define('LNG_AdminNotifications_Send_Desc', 'Yes, notify me when this user sends to more than (click to choose):');
define('LNG_AdminNotifications_Send_LimitDesc', 'contacts in a single send');
define('LNG_AdminNotifications_Email_Text', 'Email Message Text:');
define('LNG_AdminNotifications_Notify_Import', 'Notify of Large Import?');
define('LNG_AdminNotifications_Import_Desc', 'Yes, notify me when this user imports to more than (click to choose):');
define('LNG_AdminNotifications_Import_LimitDesc', 'contacts in a single import');
define('LNG_AdminNotifications_Send_Email', '***This is an automatic email, please do not reply***

%%user_fullname%% has just sent an email campaign titled "%%campaign_title%%" with subject line "%%campaign_subject%%" to %%number_of_contacts%% contacts.

To stop receiving these messages, login to the email system, edit %%user_fullname%%\'s user account and go to the "Admin Notifications" tab.');
define('LNG_AdminNotifications_Import_Email', '***This is an automatic email, please do not reply***

%%user_fullname%% has just imported %%number_of_contacts%% contacts into the list named "%%list_name%%" list.

To stop receiving these messages, login to the email system, edit %%user_fullname%%\'s user account and go to the "Admin Notifications" tab.');
define('LNG_EnterNotifyAdminEmail', "Please enter a valid email address.");
define('LNG_EnterNotifyAdminThresholdNotNumber', "Please enter the number of contacts which will trigger this email.");
define('LNG_EnterNotifyAdminThreshold', "Please enter the number of contacts which will trigger this email." );
define('LNG_EnterNotifyAdminEmailText', "Please enter some text for the email message.");
define('LNG_AdminNotifications_Send_Enabled', "Send Size Trigger");
define('LNG_HLP_AdminNotifications_Send_Enabled', "When the user sends an email campaign to this many (or more) contacts in a single send, an email will be sent to the email address above, containing the text in the \"Email Message Text\" box bellow.");
define('LNG_AdminNotifications_Import_Enabled', "Import Size Trigger");
define('LNG_HLP_AdminNotifications_Import_Enabled', "When the user imports this many (or more) contacts in a single import, an email will be sent to the email address above, containing the text in the \"Email Message Text\" box bellow.");
define('LNG_AdminNotifications_Send_Text', "Email Message Text");
define('LNG_HLP_AdminNotifications_Send_Text', "The text here will be sent as the body of the notification email. You can use placeholders to include details about the large send as follows:<ul><li><b>%%user_fullname%%</b> - The full name of the user who sent the email campaign</li><li><b>%%campaign_title%%</b> - The name of the email campaign which was sent</li><li><b>%%campaign_subject%%</b> - The subject line of the email campaign which was sent</li><li><b>%%number_of_contacts%%</b> - The total number of contacts the email campaign was sent to </li></ul>");
define('LNG_AdminNotifications_Import_Text', "Email Message Text");
define('LNG_HLP_AdminNotifications_Import_Text', "The text here will be sent as the body of the notification email. You can use placeholders to include details about the large import as follows:<ul><li><b>%%user_fullname%%</b> - The full name of the user who imported the contacts</li><li><b>%%list_name%%</b> - The name of the list into which the contacts were imported</li><li><b>%%number_of_contacts%%</b> - The total number of contacts which were imported</li></ul>");
define('LNG_NotifyEmailAddress', 'Email Address');
define('LNG_HLP_NotifyEmailAddress', 'To which email address (or addresses) do you want the admin notification emails to be sent? To use more than one email address, just seperate them with commas, such as:<br /><br />user1@example.com, user2@example.com');
define('LNG_AdminNotifications_Notify_SendEnable', 'Notify of Large Send?');
define('LNG_HLP_AdminNotifications_Notify_SendEnable', 'Would you like to be notified when this user sends an email campaign to a large list of contacts? If so tick this box and you will be able to customize the notification settings bellow.');
define('LNG_AdminNotifications_Notify_ImportEnable', 'Notify of Large Import?');
define('LNG_HLP_AdminNotifications_Notify_ImportEnable', 'Would you like to be notified when this user imports a large list of contacts? If so tick this box and you will be able to customize the notification settings bellow.');
define('LNG_UserNotCreated', 'An error occured while trying to create this user. If this problem persists, please contact your administrator.');
define('LNG_AdministratorType', 'Account Type');



/**
 * 5.7.1
 */
define('LNG_QuickUserSearchIntro', 'Search user by: username, email or name...');



/**
 * 5.7.2
 */
define('LNG_SearchRecordNotFound', 'No user accounts match your search conditions.');



/**
 * 6.0.0
 */
define('LNG_UsersGroups', 'User Group');
define('LNG_HLP_UsersGroups', 'Which group should this user belong to? Each group contains its own permissions and email sending options. You can manage groups from the User Accounts menu at the top right of the page.');
define('LNG_UsersGroups_Intro', '-- Please choose a user group --');
define('LNG_UsersGroups_Choose_Group', 'Please choose a group.');
define('LNG_UserDetailsAdvanced', 'Advanced User Details');
define('LNG_UserSettingsAdvanced_Heading', 'Advanced User Settings');
define('LNG_HeaderFooter_Heading', 'Footer Settings');
define('LNG_AdminCannotChangeGroup', 'Cannot change user group. There must be at least one system administrator.');
