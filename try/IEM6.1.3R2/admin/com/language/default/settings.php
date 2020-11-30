<?php

define('LNG_SettingsSaved', 'The modified settings have been saved successfully.');
define('LNG_SettingsNotSaved', 'The modified settings have not been saved.');
define('LNG_DatabaseIntro', 'Database Details');
define('LNG_DatabaseType', 'Database Type');
define('LNG_DatabaseUser', 'Database User');
define('LNG_DatabasePassword', 'Database Password');
define('LNG_DatabaseHost', 'Database Hostname');
define('LNG_DatabaseName', 'Database Name');
define('LNG_DatabaseTablePrefix', 'Database Table Prefix');
define('LNG_Miscellaneous', 'Miscellaneous Settings');
define('LNG_ApplicationURL', 'Application URL');
define('LNG_LicenseKeyIntro', 'Licence Key Details');
define('LNG_LicenseKey', 'Licence Key');
define('LNG_LicenseKeyUpdated', 'Your licence key has been updated. You will be logged out completely and you will need to log in again for the change to take effect.');
define('LNG_HLP_DatabaseUser','User Name used to login to the database.');
define('LNG_HLP_DatabasePassword','Password used to login to the database.');
define('LNG_HLP_DatabasePasswordConfirm','Re-type your database password to confirm that it is correct.');
define('LNG_HLP_DatabaseHost', 'Hostname or IP address of the database server. eg. localhost.');
define('LNG_HLP_DatabaseName', 'The name of the database being used.');
define('LNG_HLP_DatabaseTablePrefix', 'Optional text to be prepended to tables. This is recommended if you are using a database that contains other tables.');
define('LNG_HLP_ApplicationURL', 'Full URL where this application is setup eg. http://www.domain.com/newsletter/');
define('LNG_HLP_LicenseKey', 'The licence key generated when you purchased this product. If unsure please contact your administrator or the seller of this product.');
define('LNG_GlobalHTMLFooter', 'Global HTML Footer');
define('LNG_HLP_GlobalHTMLFooter', 'Anything you type here will be added to the end of every HTML email (after the users HTML footer). To add a line break, type <br/>\\\'&lt; br&gt;\\\'');
define('LNG_GlobalTextFooter', 'Global Text Footer');
define('LNG_HLP_GlobalTextFooter', 'Anything you type here will be added to the end of every text email (after the users text footer).');
define('LNG_ForceUnsubLink', 'Force Unsubscribe Link');
define('LNG_HLP_ForceUnsubLink', 'This option will force all email campaigns and autoresponders to contain a mandatory unsubscribe link before they are sent.<br/><br/>If an unsubscribe link is not detected in an email, it will automatically be added. If an unsubscribe link is detected, this option will be ignored.');
define('LNG_ForceUnsubLinkExplain', 'Yes, force an unsubscribe link');
define('LNG_MaxOverSize', 'Monthly Leeway Allowance');
define('LNG_CronEnabled', 'Do You Want to Enable CRON Support?');
define('LNG_CronEnabledExplain', 'Yes, I want to enable CRON support (<a onclick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'819\'); return false;" href="#">How can I enable CRON?</a>)');
define('LNG_HLP_CronEnabled', 'This option will let you schedule emails in advance, send autoresponders and process bounced emails quickly. You must have CRON installed and setup correctly on your server for this to work.');
define('LNG_IpTracking', 'IP Address Tracking');
define('LNG_IpTrackingExplain', 'Yes, ip address tracking is enabled');
define('LNG_ApplicationEmail', 'Contact Email Address');
define('LNG_HLP_ApplicationEmail', 'The return email address for \\\'Forgot Password\\\' requests.');
define('LNG_CronPath', 'CRON Command to Run');
define('LNG_HLP_CronPath', 'This is the path to the scheduled email batch script which is required for CRON jobs to work. The script makes sure scheduled sending, autoresponders and automatic bounce processing tasks are executed.<br /><br />You will need this path when setting up your server for CRON support (click the <em>How can I enable CRON?</em> link above for help).');



/**
 * 1.0.5
 */
define('LNG_MaxImageWidth', 'Maximum Image Width');
define('LNG_HLP_MaxImageWidth', 'Set the maximum width of images that users are allowed to upload. This should be set to stop users from uploading extremely large images in email campaigns.');
define('LNG_MaxImageHeight', 'Maximum Image Height');
define('LNG_HLP_MaxImageHeight', 'Set the maximum height of images that users are allowed to upload. This should be set to stop users from uploading extremely large images in email campaigns.');

/**
 * 1.0.7
 */
define('LNG_BounceAccountIntro', 'Default Bounce Details');
define('LNG_SetDefaultBounceAccountDetails', 'Default Bounce Details');
define('LNG_SetDefaultBounceAccountDetailsExplain', 'Yes, set default bounce details');
define('LNG_DefaultBounceAddress', 'Default Bounce Address');
define('LNG_HLP_DefaultBounceAddress', 'This is the address where emails will be returned to if they bounce, which means they haven\\\'t reached the intended recipient.');
define('LNG_DefaultBounceServer', 'Default Bounce Server');

define('LNG_DefaultBounceUsername', 'Default Bounce Username');

define('LNG_DefaultBouncePassword', 'Default Bounce Password');

define('LNG_DefaultBounceImap', 'IMAP Email Account');
define('LNG_DefaultBounceImapExplain', 'Yes, this is an imap account');

define('LNG_UseDefaultBounceExtraSettings', 'Use Extra Mail Settings');
define('LNG_UseDefaultBounceExtraSettingsExplain', 'Yes, use extra mail settings');

define('LNG_DefaultBounceExtraSettings', 'Extra Mail Settings');

define('LNG_Charset', 'Character Set');

define('LNG_EmailSettings', 'Email Settings');
define('LNG_CurrentServerTime', 'Current Server Time');

define('LNG_ServerTimeZone', 'Server Timezone');


/**
 **************************
 * 1.1.3
 **************************
 */
define('LNG_DatabaseUpgraded', 'Your database has been upgraded to version %s.');
define('LNG_DatabaseUpgradesFailed', 'Your database was not upgraded successfully. The following errors occurred:');

/**
 **************************
 *  1.3
 **************************
 */
define('LNG_AllowAttachments', 'Allow Attachments');
define('LNG_AllowAttachmentsExplain', 'Yes, allow attachments');
define('LNG_HLP_AllowAttachments', 'If this option is enabled, your users will be able to send attachments with their email campaigns or autoresponders.');

define('LNG_AllowEmbeddedImages', 'Allow Embedded Images');
define('LNG_AllowEmbeddedImagesExplain', 'Yes, allow embedded images');
define('LNG_HLP_AllowEmbeddedImages', 'If this option is enabled, your users will be able to embed images in their email campaigns or autoresponders.');

define('LNG_DefaultEmbeddedImages', 'Default Embedded Images');
define('LNG_DefaultEmbeddedImagesExplain', 'Yes, embedded images are enabled by default');
define('LNG_HLP_DefaultEmbeddedImages', 'If this option is checked, then embedded images are enabled by default when you send an email campaign or create a new autoresponder. If this option is not checked, then embedded images are disabled by default.');

define('LNG_Autoresponders_Disabled_Attachments_One_Link', '<a href="#" onclick="javascript: ShowReport(\'autoresponder\'); return false;">1 autoresponder has been disabled because it has one or more attachments.</a> An email has been sent to the list owner advising them of this action.');
define('LNG_Autoresponders_Disabled_Attachments_Many_Link', '<a href="#" onclick="javascript: ShowReport(\'autoresponder\'); return false;">%s autoresponders have been disabled because they have one or more attachments.</a> An email has been sent to the list owner advising them of this action.');

define('LNG_Autoresponders_Disabled_Heading', 'Autoresponders Disabled');
define('LNG_Autoresponders_Disabled_Heading_Intro', 'The following autoresponder(s) has been disabled.');

define('LNG_Autoresponders_Disabled_Email_Subject', 'Autoresponder has been disabled');

define('LNG_MaxAttachmentSize', 'Maximum Attachment Size');
define('LNG_HLP_MaxAttachmentSize', 'This is the maximum size <b>per attachment</b> you can upload. This size is in kilobytes (KB) - 1 megabyte (MB) is equivalent to 1,024 KB');
define('LNG_MaxAttachmentSizeKB', 'KB');

define('LNG_Newsletters_Disabled_Attachments_One_Link', '<a href="#" onclick="javascript: ShowReport(\'newsletter\'); return false;">1 email campaign has been disabled because it has one or more attachments.</a>');
define('LNG_Newsletters_Disabled_Attachments_Many_Link', '<a href="#" onclick="javascript: ShowReport(\'newsletter\'); return false;">%s email campaigns have been disabled because they have one or more attachments.</a>');

define('LNG_Newsletters_Disabled_Heading', 'Email Campaigns Disabled');
define('LNG_Newsletters_Disabled_Heading_Intro', 'The following email campaign(s) has been disabled.');
define('LNG_DisabledNewsletter_Item', 'Email campaign name: \'%s\'');

define('LNG_DatabaseVersion', 'Database Version');

define('LNG_ServerSoftware', 'Server Software');
define('LNG_SafeModeEnabled', 'Safe Mode Enabled');
define('LNG_ImapSupportFound', 'Imap Support Found');
define('LNG_GDVersion', 'GD Version');
define('LNG_CurlSupportFound', 'Curl Support Found');
define('LNG_DOMEnabled', 'DOM Extension Enabled');
define('LNG_GD_NotDetected', 'GD Not Detected');
define('LNG_ModSecurity', 'Mod Security Enabled');
define('LNG_ModSecurity_Unknown', 'Unknown or PHP is running in CGI mode');
define('LNG_ProductVersion', 'Product Version');
define('LNG_PHPVersion', 'PHP Version');
define('LNG_CronRunTime', 'CRON Run Time');
define('LNG_CronRunTime_Never', 'CRON hasn\'t run yet');
define('LNG_CronRunTime_Once', 'CRON has only run once');
define('LNG_CronRunTime_Difference', 'CRON runs every %s');

define('LNG_CronSettings', 'Scheduled Send Settings');
define('LNG_Cron_Option_HasNotRun', 'Has Not Run Yet');

define('LNG_Cron_NextRun', 'Next Run');
define('LNG_Cron_LastRun', 'Last Run');
define('LNG_Cron_RunsEvery', 'Runs Every');

define('LNG_Cron_Option_disabled', 'Disable');
define('LNG_Cron_Option_1_minute', '1 Minute');
define('LNG_Cron_Option_2_minutes', '2 Minutes');
define('LNG_Cron_Option_5_minutes', '5 Minutes');
define('LNG_Cron_Option_10_minutes', '10 Minutes');
define('LNG_Cron_Option_15_minutes', '15 Minutes');
define('LNG_Cron_Option_20_minutes', '20 Minutes');
define('LNG_Cron_Option_30_minutes', '30 Minutes');
define('LNG_Cron_Option_1_hour', '1 Hour');
define('LNG_Cron_Option_2_hours', '2 Hours');
define('LNG_Cron_Option_4_hours', '4 Hours');
define('LNG_Cron_Option_6_hours', '6 Hours');
define('LNG_Cron_Option_12_hours', '12 Hours');
define('LNG_Cron_Option_1_day', 'Daily');

define('LNG_Cron_DateFormat', 'g:i a, d M Y');

define('LNG_Cron_Option_Disabled', 'Option has been disabled.');

define('LNG_Cron_Option_send_Heading', 'Scheduled Sending');

define('LNG_Cron_Option_autoresponder_Heading', 'Autoresponders');

define('LNG_Cron_Option_bounce_Heading', 'Bounce Processing');

define('LNG_EmailSize_Warning_KB', 'KB');
define('LNG_EmailSize_Maximum_KB', 'KB');

define('LNG_EmailSize_Warning', 'Email Size Warning');
define('LNG_HLP_EmailSize_Warning', 'This will show a warning message if an email campaign or autoresponder goes over a particular size. Set to 0 to disable.<br/>This size is in kilobytes (KB) - 1 megabyte (MB) is equivalent to 1,024 KB');

define('LNG_EmailSize_Maximum', 'Email Size Maximum');
define('LNG_HLP_EmailSize_Maximum', 'This won\\\'t allow an email campaign or autoresponder to send if it is over a particular size. Set to 0 to disable.<br/>This size is in kilobytes (KB) - 1 megabyte (MB) is equivalent to 1,024 KB');

define('LNG_TestSendTo', 'Send a test email to');

define('LNG_Help_Settings', 'These are the settings that control the top level of the application. From here you can set global application settings, view your system set up and set up global email controls.');

define('LNG_CronTutorialLink', 'Learn how to setup CRON on your server');

define('LNG_Send_TestMode', 'Send in Test Mode');
define('LNG_Send_TestModeExplain', 'Yes, put the application into test mode');

define('LNG_Send_TestMode_JobsWaiting', 'There are email campaigns waiting to be sent. If you leave the application in test mode, these email campaigns will not actually be sent to the recipients. Please pause the email campaigns waiting to be sent or take the application out of test mode.');

define('LNG_Resend_Maximum', 'Maximum Resend Attempts');
define('LNG_HLP_Resend_Maximum', 'The maximum number of times an email campaign can be re-sent. This number should not be very high because if you re-send an email campaign too many times, it may cause you to get blacklisted or have other issues with email deliverability.');

define('LNG_DatabaseUpgradeFailed', '<font color="red"><b>Upgrade failed! Please contact your adminstrator and give him/her the error messages displayed.</b></font>');

/**
 **************************
 *  1.4
 **************************
 */
define('LNG_ConfigFileNotWritable', 'The configuration file (%s) is not writable. Please FTP in to your server and CHMOD it to 664, 646 or 666. If you are unsure about this, please contact your system administrator.');

/**
 **************************
 *  1.4.1
 **************************
 */
define('LNG_CronRunTime_Explain', 'When you add the CRON command (above) to your CRONTAB, it needs to run at least as frequently as the <em>Run Every</em> times you choose below. <a onclick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'842\'); return false;" href="#">Learn why</a>.');

/**
 **************************
 *  5
 **************************
 */
define('LNG_HLP_MaxOverSize', 'If a user tries to send more emails than they are allowed, the monthly leeway allowance will let them send a few more.<br/><br/>For example, if a user has a per-month limit of 100, but schedules an email campaign 3 days in advance and it has to send to 110 contacts, it would normally be blocked. If they had a leeway allowance of 10 or more, it would sent.<br/><br/>Both the user and administrator will be notified when this takes effect.<br/><br/>Set to 0 for no leeway.');

define('LNG_HLP_IpTracking', 'This option will let you enable or disable storing ip addresses when contacts subscribe or unsubscribe from contact lists.');

define('LNG_HLP_Send_TestMode', 'When the system is in test mode, no actual emails will be sent when you schedule or send an email campaign so you can test the speed of the application without worrying about sending emails to your contacts. Only email campaign sending is affected by this setting, website forms and autoresponders are not affected by this setting.');

define('LNG_HLP_SetDefaultBounceAccountDetails', 'Complete the details below to specify default bounce handling information when a new contact list is created.<br/><br/>If you don\\\'t specify bounce information, all bounced emails will be returned to the \\\'From\\\' address setup for a particular contact list.');

define('LNG_HLP_DefaultBounceServer', 'Enter the email server name to connect to so bounced emails can be processed. This can be either in the format of just hostname or can include an alternate port with hostname:port.<br/><br/>This setting is only used when a new contact list is created, if the \\\'Hide Bounce Information\\\' permission is not checked then the user will be able to change this information for their contact list.');

define('LNG_HLP_DefaultBounceUsername', 'This is used for processing bounced emails. If you enter your email server, username and password, you can process bounces using a CRON job.<br/><br/>This setting is only used when a new contact list is created, if the \\\'Hide Bounce Information\\\' permission is not checked then the user will be able to change this information for their contact list.');

define('LNG_HLP_DefaultBouncePassword', 'This is used for processing bounced emails. If you enter your email server, username and password, you can process bounces using a CRON job.<br/><br/>This setting is only used when a new contact list is created, if the \\\'Hide Bounce Information\\\' permission is not checked then the user will be able to change this information for their contact list.');

define('LNG_HLP_DefaultBounceImap', 'Is the bounce email account an imap account? If it is not an imap account, it is a POP3 account.<br/><br/>This setting is only used when a new contact list is created, if the \\\'Hide Bounce Information\\\' permission is not checked then the user will be able to change this information for their contact list.');


define('LNG_HLP_UseDefaultBounceExtraSettings', 'You may need to set extra options to connect to an email account for bounce processing. If so, enable this option and fill in the required information below. If unsure, leave this unticked.<br/><br/>This setting is only used when a new contact list is created, if the \\\'Hide Bounce Information\\\' permission is not checked then the user will be able to change this information for their contact list.');

define('LNG_HLP_DefaultBounceExtraSettings', 'Please add any extra options that may be required to properly connect to a bounce email account. For example \\\'/notls\\\' or \\\'/nossl\\\'.<br/><br/>This setting is only used when a new contact list is created, if the \\\'Hide Bounce Information\\\' permission is not checked then the user will be able to change this information for their contact list.');

define('LNG_DisabledAutoresponder_Item', 'Autoresponder name: \'%s\' on contact list \'%s\'');

define('LNG_Autoresponders_Disabled_Email_Message', 'Your systems adminstrator has disabled attachments in autoresponders. Due to this, the autoresponder \'%s\' on contact list \'%s\' has been disabled because it has one or more attachments.' . "\n\n" . 'To re-enable the autoresponder, edit the autoresponder from your control panel and remove all attachments.');

define('LNG_MaxHourlyRate', 'Max Hourly Rate');

define('LNG_Settings_SystemInformation', 'System Information');
define('LNG_Help_Settings_SystemInformation', 'An overview of the configuration of your server can be seen below.');

define('LNG_ProductEdition', 'Product Edition');

define('LNG_ShowFullSystemInfo', 'View Full System Info');

define('LNG_ServerInfo_Heading', 'Server Information');

define('LNG_ServerInfo', 'Server Information');
define('LNG_CronJobTimeSettings', 'CRON Job Time Settings &amp; Log');

define('LNG_ErrorAlertMessage_BlankContactEmail', 'Please enter system administrator contact email address.');
define('LNG_ErrorAlertMessage_BlankLicenseKey', 'Please enter a licence key.');

define('LNG_SystemMessage', 'System Message');
define('LNG_HLP_SystemMessage', 'Add a system message to inform your clients about important events such as system maintenance. They will see this message below the \\\'Contact Activity\\\' graph on the home page.<br/>Delete the entire message to stop it from showing up.');

/**
 **************************
 * Changed/Added in 5.0.10
 **************************
 */

define('LNG_HLP_MaxHourlyRate', 'The maximum number of emails that will be sent per hour. The per hour rate for users can be less than this, but not more.<br/><br/>Set to 0 for unlimited.');


/**
 **************************
 * Changed/Added in 5.5.0
 **************************
 */
define('LNG_Addon_NoAddonsAvailable', 'There are no addons currently available.');
define('LNG_Addon_Name', 'Name');
define('LNG_Addon_Description', 'Description');
define('LNG_Addon_RunningVersion', 'Version');
define('LNG_Addon_Installed', 'Installed?');
define('LNG_Addon_Enabled', 'Enabled?');
define('LNG_Addon_Configure', 'Configure Addon');
define('LNG_Addon_Alert_NeedToConfigure', 'You need to configure this addon before it can be enabled.');
define('LNG_Addon_Alert_NeedToInstall', 'You need to install this addon before it can be enabled.');
define('LNG_Addon_NoneAvailable', 'No addons are currently available');
define('LNG_Addon_Edit_Settings', 'Edit Settings');
define('LNG_Addon_Alert_NeedToInstallBeforeConfigure', 'You need to install this addon before it can be configured.');
define('LNG_Addon_Uninstall_Confirm', 'Are you sure you want to uninstall this addon? All of its settings and data will be removed. This cannot be undone.');
define('LNG_Addon_Alert_NoConfiguration', 'This addon has no options to configure.');

define('LNG_Addon_Action_NotAllowed', 'This action is not allowed!');

define('LNG_Addon_Success_install', 'The selected addon has been installed successfully.');
define('LNG_Addon_Success_uninstall', 'The selected addon has been uninstalled and is no longer available for use.');
define('LNG_Addon_Success_disable', 'The selcted addon has been disabled.');
define('LNG_Addon_Success_enable', 'The selected addon has been enabled successfully.');
define('LNG_Addon_Success_savesettings', 'The selected addon has been configured successfully.');

define('LNG_Addon_Failure_install', 'An error occurred while trying to install the selected addon. Please try again.');
define('LNG_Addon_Failure_uninstall', 'An error occurred while trying to remove the selected addon. Please try again.');
define('LNG_Addon_Failure_disable', 'An error occurred while trying to disable the selected addon. Please try again.');
define('LNG_Addon_Failure_enable', 'An error occurred while trying to enable the selected addon. Please try again.');
define('LNG_Addon_Failure_savesettings', 'An error occurred while trying to configure the selected addon. Please try again.');

define('LNG_Addon_Tooltip_ClickToEnable', 'Click here to enable this addon');
define('LNG_Addon_Tooltip_ClickToDisable', 'Click here to disable this addon');
define('LNG_Addon_Tooltip_ClickToInstall', 'Click here to install this addon');
define('LNG_Addon_Tooltip_ClickToUninstall', 'Click here to uninstall this addon');

define('LNG_Addon_Action_Text_Install', 'Install');
define('LNG_Addon_Action_Text_Uninstall', 'Unistall');
define('LNG_Addon_Action_Text_Enable', 'Enable');
define('LNG_Addon_Action_Text_Disable', 'Disable');
define('LNG_Addon_Action_Text_Upgrade', 'Upgrade');
define('LNG_Addon_Action_Text_Configure', 'Configure');

define('LNG_Cron_Option_triggeremails_s_Heading', 'Triggers Sending');

define('LNG_SecuritySettings_LoginSecurity_EnableLoginWait_Title', 'Login Failure Wait Security Settings');
define('LNG_SecuritySettings_LoginSecurity_EnableLoginWait', 'Enable Login Failure Wait');
define('LNG_SecuritySettings_LoginSecurity_YesEnableLoginWait', 'Yes, enable login failure wait security feature');
define('LNG_HLP_SecuritySettings_LoginSecurity_EnableLoginWait', 'When this option is enabled, failed login attempts will be delayed for a specified number of seconds.');
define('LNG_SecuritySettings_LoginSecurity_EnableLoginWait_DelayFor', 'Delay login failure for');

define('LNG_SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_Title', 'Login Failure IP Ban Security Settings');
define('LNG_SecuritySettings_LoginSecurity_EnableLoginFailureThreshold', 'Enable Login Failure IP Ban');
define('LNG_SecuritySettings_LoginSecurity_YesEnableLoginFailureThreshold', 'Yes, enable login failure IP Ban security feature');
define('LNG_HLP_SecuritySettings_LoginSecurity_EnableLoginFailureThreshold', 'When this option is enabled, user IP will be loged when the application detected a failed login attempt.<br /><br />An IP address that has exceeded the allowable threshold will be banned for specified time period.');
define('LNG_SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_Threshold', 'Threshold');
define('LNG_SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_FailedAttemptsIn', 'failed attempts in');
define('LNG_SecuritySettings_LoginSecurity_EnableLoginFailureThreshold_BanIPFor', 'Ban IP for');

/**
 **************************
 * Changed/Added in 5.6.0
 **************************
 */
define('LNG_CreditSettings_UserCredit_Section', 'User Credit Settings');
define('LNG_CreditSettings_UserCredit_AutorespondersTakeCredit', 'Autoresponders Deduct Credits?');
define('LNG_CreditSettings_UserCredit_AutorespondersTakeCredit_Description', 'Yes, deduct email credits for autoresponders');
define('LNG_HLP_CreditSettings_UserCredit_AutorespondersTakeCredit', "When ticked, any autoresponder emailed sent out will deduct credits from the user's credit limit. If you're unsure you should tick this box.");
define('LNG_CreditSettings_UserCredit_TriggersTakeCredit', 'Triggers Deduct Credits?');
define('LNG_CreditSettings_UserCredit_TriggersTakeCredit_Description', 'Yes, deduct email credits for trigger emails');
define('LNG_HLP_CreditSettings_UserCredit_TriggersTakeCredit', "When ticked, any emails sent as the result of a trigger will deduct credits from the user's credit limit. If you're unsure you should tick this box.");

define('LNG_CreditSettings_Warnings_Section', 'Low Credit Email Settings');
define('LNG_CreditSettings_Warnings_Enabled', 'Low Credit Reminder Emails:');
define('LNG_CreditSettings_Warnings_Enabled_Description', "Yes, send reminder email when a user's credit balance is close to being used up");
define('LNG_HLP_CreditSettings_Warnings_Enabled', "Tick this option if you'd like the system to automatically remind users to purchase more credit from you when their credit balance is nearing zero. Please note that this system only works if you use a monthly credits system.<br/><br/>The following custom fields are available to be used in the warning mail:<br/>- %%user_fullname%%<br/>- %%user_firstname%%<br/>- %%user_lastname%%<br/>-%%credit_total%%<br/>- %%credit_remains%%<br/>- %%credit_remains_precentage%%<br/>- %%credit_used%%<br/>- %%credit_used_percentage%%");
define('LNG_CreditSettings_Warnings_LevelPrompt_PRE', 'Send a reminder email when the user has only');
define('LNG_CreditSettings_Warnings_LevelPrompt_POST', 'of their credits remaining');
define('LNG_CreditWarnings_Warnings_EmailSubjectDefaultText', 'Urgent: You are running low on email credits');
define('LNG_CreditSettings_Warnings_PercentageDefaultText', "Hi %%user_firstname%%,

This is an automated reminder to let you know that you only have %%credit_remains_precentage%%% of your email credits left. When you use up all of your credits, you will no longer be able to send email campaigns and your autoresponder emails will no longer be sent.

You have used %%credit_used%% of %%credit_total%% credits for this month.

To avoid a disruption to your email marketing service, you need to purchase more credits. To purchase more credits, please [type your instructions here]

Thank you,
[Your company details here]");

define('LNG_CreditSettings_Warnings_Alert_EnterEmailSubject', 'Please enter subject of the warning email.');
define('LNG_CreditSettings_Warnings_Alert_EnterEmailContents', 'Please enter the contents of warning email.');

/**
 **************************
 * Changed/Added in 5.7.0
 **************************
 */

define('LNG_Cron_Option_maintenance_Heading', 'Maintenance Process');
define('LNG_Cron_Option_3_hours', '3 Hours');
define('LNG_Cron_Option_9_hours', '9 Hours');

define('LNG_PrivateLabelSettings_ApplicationName', 'Application Name');
define('LNG_HLP_PrivateLabelSettings_ApplicationName', 'What text would you like to use as the application name as it is displayed in the control panel?');
define('LNG_PrivateLabelSettings_ApplicationFooter', 'Application Footer');
define('LNG_HLP_PrivateLabelSettings_ApplicationFooter', 'The application footer, or "powered by" text appears at the bottom of every page in the control panel.');
define('LNG_PrivateLabelSettings_DefaultHtmlEmailFooter', 'Default HTML Email Footer');
define('LNG_HLP_PrivateLabelSettings_DefaultHtmlEmailFooter', 'This is the HTML which will be set as the value of "HTML Footer" field by default when you are creating a new user account. It will be appended to the bottom of all emails which are sent as HTML.');
define('LNG_PrivateLabelSettings_DefaultTextEmailFooter', 'Default Text Email Footer');
define('LNG_HLP_PrivateLabelSettings_DefaultTextEmailFooter', 'This is the text which will be set as the value of "Text Footer" field by default when you are creating a new user account. It will be appended to the bottom of all emails which are sent as text.');
define('LNG_PrivateLabelSettings_UpgradeMessage', 'Upgrade Message');
define('LNG_HLP_PrivateLabelSettings_UpgradeMessage', 'This message will appear at the top of the control panel for users whose account type is "Trial Account". Available placeholders are as follows:<br /><br /> - <b>%%trial_days_current%%</b> - How many days the user is into their free trial account.<br /> - <b>%%trial_days_left%%</b> - How many days the user has left in their free trial.');
define('LNG_PrivateLabelSettings_ApplicationLogoImage', 'Application Logo Image');
define('LNG_HLP_PrivateLabelSettings_ApplicationLogoImage', 'This is the logo image which will appear both on the login screen and at the top of every page in the control panel.');
define('LNG_PrivateLabelSettings_ApplicationFavicon', 'Favicon Image');
define('LNG_HLP_PrivateLabelSettings_ApplicationFavicon', 'Optionally upload an icon file (ending in .ico) here which will be shown in the browser\'s address bar.');

define('LNG_PrivateLabelSettings_UpdatesCheck', 'Updates Check?');
define('LNG_PrivateLabelSettings_YesUpdatesCheck', 'Yes, enable automatic update checking');
define('LNG_HLP_PrivateLabelSettings_UpdatesCheck', 'Tick this box to have the application automatically check for new versions and show a notification message in the control panel when one is available for download. The notification message does not include any references or links to any third parties.');

define('LNG_PrivateLabelSettings_SmtpSending', 'SMTP Sending?');
define('LNG_PrivateLabelSettings_YesSmtpSending', 'Yes, show SMTP.com sending options');
define('LNG_HLP_PrivateLabelSettings_SmtpSending', 'Tick this box to show the SMTP.com sending options when creating/editing this user account. SMTP.com is a third party which provides outsourced mail servers and integrates with the software.');

define('LNG_PrivateLabelSettings_GettingStartedVideo', 'Getting Started Video & Guide?');
define('LNG_PrivateLabelSettings_YesGettingStartedVideo', 'Yes, link to getting started video and guide');
define('LNG_HLP_PrivateLabelSettings_GettingStartedVideo', 'Tick this box to show links to the branded getting started video and documentation on the home page of the control panel for this user.');

define('LNG_PrivateLabelSettings_ExpiredTrial_LoginMessage', 'Upgrade Expiry Message');
define('LNG_HLP_PrivateLabelSettings_ExpiredTrial_LoginMessage', 'This message will be shown to users who try to login to the application after their free trial has expired and is no longer accessible.');

define('LNG_ErrorAlertMessage_BlankApplicationName', 'Please enter an application name.');
define('LNG_ErrorAlertMessage_BlankAccountUpgradeMessage', 'Please enter an upgrade message.');
define('LNG_ErrorAlertMessage_BlankExpiredLogin', 'Please enter an upgrade expiry message.');
define('LNG_ErrorAlertMessage_ErrorApplicationLogoImage', 'Error when uploading logo file.');
define('LNG_ErrorAlertMessage_InvalidFormatApplicationLogoImage', 'The logo file you have uploaded is not valid. Please upload a GIF or JPG file and try again.');
define('LNG_ErrorAlertMessage_InvalidNameApplicationLogoImage', 'The logo file name you have uploaded is not valid. Please upload a GIF or JPG file and try again.');
define('LNG_ErrorAlertMessage_ErrorApplicationFavicon', 'Error when uploading icon file.');
define('LNG_ErrorAlertMessage_InvalidFormatApplicationFavicon', 'The icon file you have uploaded is not valid. Please upload a valid icon file ending with ico.');
define('LNG_ErrorAlertMessage_InvalidNameApplicationFavicon', 'The icon file name you have uploaded is not valid. Please upload a valid icon file ending with ico.');

/**
 **************************
 * Changed/Added in 5.7.3
 **************************
 */
define('LNG_MultipleUnsubscribe', 'Use "Multiple Unsubscribe"');
define('LNG_MultipleUnsubscribe_Explain', 'Yes, present a list of "contact list" that subscribers can choose during unsubscribing process.');
define('LNG_HLP_MultipleUnsubscribe', 'When you enable this option, your subscribers will be presented with a list of "contact list" that they can unsubscribe when they click the "unsubscribe" link (Please note that mailing list names will be shown to the customer).');

/**
 **************************
 * Changed/Added in 5.7.4
 **************************
 */
define('LNG_ContactCanModifyEmail', 'Let contacts modify their own email');
define('LNG_ContactCanModifyEmail_Explain', 'Yes, allow contacts to modify their own emails when they use "Modify details" form.');
define('LNG_HLP_ContactCanModifyEmail', 'Allowing contacts to modify their own emails will means that they will be able to modify their own email to anything they wanted without having to opt-in again.');
