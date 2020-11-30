<?php

/**
* Language file variables for the jobs bouncing area.
*
* @see GetLang
*
* @version     $Id: bounce.php,v 1.24 2008/03/05 03:02:59 scott Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the jobs bouncing area... Please backup before you start!
*/

// we need some variables from the lists language file.
require_once(dirname(__FILE__) . '/lists.php');

define('LNG_BadLogin_Details', 'Unable to log in using the details provided, the server returned this error message: %s<br/>Please check the details and try again.');

define('LNG_Bounce_No_ImapSupport_Heading', 'Process Bounced Emails');

define('LNG_Bounce_Step1', 'Process Bounced Emails');
define('LNG_Bounce_CancelPrompt', 'Are you sure you want to cancel processing bounced emails?');

define('LNG_Bounce_Step2', 'Process Bounced Emails');

define('LNG_Bounce_Step3', 'Process Bounced Emails');
define('LNG_StartProcessing', 'Start Processing');

define('LNG_BounceUsername', LNG_ListBounceUsername);
define('LNG_HLP_BounceUsername', 'Enter the username for the bounce email account.<br/>This can either be \\\'username\\\' or \\\'username@domain.com\\\' depending on the host.');

define('LNG_BouncePassword', LNG_ListBouncePassword);
define('LNG_HLP_BouncePassword', 'Enter the password for the bounce email account.');

define('LNG_BounceResults_InProgress_Message', 'Please wait while we attempt to process the %s email(s) found in the account...');

define('LNG_BounceResults_InProgress_HardBounces_Many', '%s hard bounces have been found so far');
define('LNG_BounceResults_InProgress_HardBounces_One', '1  hard bounce have been found so far');

define('LNG_BounceResults_InProgress_SoftBounces_Many', '%s soft bounces have been found so far');
define('LNG_BounceResults_InProgress_SoftBounces_One', '1  soft bounce have been found so far');

define('LNG_BounceResults_InProgress_EmailsIgnored_Many', '%s emails have been ignored so far.');
define('LNG_BounceResults_InProgress_EmailsIgnored_One', '1  emails have been ignored so far.');

define('LNG_BounceResults_HardBounces_Many', '%s emails were processed as "hard bounces"');
define('LNG_BounceResults_HardBounces_One', '1 email was processed as a "hard bounce"');

define('LNG_BounceResults_SoftBounces_Many', '%s emails were processed as "soft bounces"');
define('LNG_BounceResults_SoftBounces_One', '1 email was processed as a "soft bounce"');

define('LNG_BounceResults_Finished', 'Process Bounced Emails');
define('LNG_BounceResults_Message_Multiple', '%s emails were found in the email account.');
define('LNG_BounceResults_Message_One', '1 email was found in the email account.');

define('LNG_ViewBounceStatistics', 'View Bounce Statistics');

/**
**************************
* Changed/Added in NX1.0.5
**************************
*/

define('LNG_BounceServer', LNG_ListBounceServer);
define('LNG_HLP_BounceServer', 'Enter the email server name to connect to so bounced emails can be processed. This can be either in the format of just hostname or can include an alternate port with hostname:port');

define('LNG_AddOwnBounceRules', '<br/>You or your administrator can modify the bounce rules used by editing the admin/resources/user_bounce_rules.php file.');

define('LNG_BounceDetailsSaved', 'Bounce details saved successfully.');


/**
**************************
* Changed/Added in NX1.0.7
**************************
*/
define('LNG_BounceResults_InProgress_Progress', 'Processing %s of %s emails');

/**
**************************
* Changed/Added in NX 1.3
**************************
*/
define('LNG_BounceLogin_Successful', 'Your bounce email account has been successfully logged into.');
define('LNG_Bounce_TestHeading', 'Test Bounce Email Settings');

define('LNG_Bounce_StartTesting', 'Checking your "bounce" account... <br />This process may take up to 2 minutes...');

/**
**************************
* Changed/Added in NX 1.4
**************************
*/
define('LNG_BounceResults_InProgress_EmailsDeleted_Many', '%s emails have been cleaned up so far.');
define('LNG_BounceResults_InProgress_EmailsDeleted_One', '1  emails have been cleaned up so far.');

define('LNG_BounceResults_EmailsIgnored_Many', '%s emails were ignored in the email account. These could be non-bounced messages (for example, spam) or they didn\'t match any of the bounce processing rules.' . LNG_AddOwnBounceRules);
define('LNG_BounceResults_EmailsIgnored_One', '1 email was ignored in the email account. This could be a non-bounced message (for example, spam) or it didn\'t match any of the bounce processing rules.' . LNG_AddOwnBounceRules);

define('LNG_BounceResults_EmailsDeleted_Many', '%s emails were cleaned up in the email account. These are most likely autoresponders (for example, out of office messages).');
define('LNG_BounceResults_EmailsDeleted_One', '1 email was cleaned up in the email account. This is most likely an autoresponder (for example, out of office messages).');

define('LNG_AgreeToDelete','You must agree to allow your emails to be deleted from your in box before you can proceed.');

define('LNG_BounceResults_Intro', 'The bounced emails in your account have been processed successfully.');

/**
**************************
* Changed/Added in NX 1.4.1
**************************
*/
// these first two are used by cron bounce processing.
define('LNG_BadLogin_Subject_Cron', 'Invalid Login Details for Bounce Processing');


/**
**************************
* Changed/Added in 5.0.0
**************************
*/
define('LNG_BadLogin_Details_Cron', "Automatic bounce processing was trying to log in to the email account (username '%s' at the email server '%s') but was unable to.\n\nIt received the following error message: '%s'.\n\nLog in to the Control Panel here: %s\n\nEdit the contact list here: %s\n\nThen check the username, password and mail server details and options are correct. You may need to use the 'Extra Mail Settings' options to fix the error.\n\nOnce you have done that, click the 'Test Bounce Settings' button to check the details are working.\n\nOnce you have confirmed the details are working properly, click 'Save' to make sure your changes are kept.\n");

define('LNG_BounceAccountEmpty', 'That email account didn\'t contain any emails to process.');
define('LNG_Bounce_No_ImapSupport_Intro', 'Your server does not have the required modules installed to process bounces. Please contact your host or system administrator and ask them to install the "PHP-IMAP" module.<br/>For more information, see <a href="http://www.php.net/imap" target="_blank">this page</a> on the PHP website.');
define('LNG_Bounce_Step3_Intro', 'Click the button below to check for and process bounced emails in the email account you entered.');
define('LNG_HLP_SaveBounceServerDetails','Would you like to save the bounce server details for this campaign so you do not have to enter them again? If so, tick this box.');
define('LNG_Bounce_Step2_Intro', 'By processing "bounced" emails, you can remove contacts from your list whose email addresses are invalid, as well as those who can\'t receive emails because their inbox is full, etc.');

define('LNG_SelectAContactList', 'Select a Contact List');

define('LNG_SelectContactList_Explain', 'I Want to Process Bounced Emails for');

/**
*************************
* Changed/added in 5.5.0
*************************
*/
// This warning is deprecated for 5.6.
define('LNG_ExplainBounceDeleteAll', 'Warning: This option is dangerous to use if multiple lists use the same bounce account. <a href="#" onClick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'845\'); return false;">Why?</a>');

/**
*************************
* Changed/added in 5.6.0
*************************
*/

define('LNG_SaveBounceServerDetails','Save Bounce Server Details?');
define('LNG_SaveBounceServerDetailsExplain','Yes, save these details so I don\'t have to re-enter them');

define('LNG_Bounce_NoLists', 'No Contact Lists have been created. Please create a contact list before processing bounces.');
define('LNG_Bounce_CreateList', 'Create a Contact List...');

define('LNG_Bounce_Step1_Intro', 'Processing bounced emails will clear your list of bad email addressess and reduce the chance of your mail server being blacklisted.');

define('LNG_SelectBounceEmail', 'Process Bounces for');
define('LNG_WhyListsGrouped', 'Why are multiple lists grouped together?');
define('LNG_BounceProcessHelp', 'Bounce Processing Help');
define('LNG_HLP_SelectBounceEmail', 'Select a list or group of lists to process bounce emails for.<br /><br />Lists with common bounce details are grouped together and can be processed at the same time.');
define('LNG_Bounce_PleaseChooseList', 'Please choose a list first.');

define('LNG_BounceIWouldLikeTo', 'I Would Like to');
define('LNG_Bounce_Auto_Process', 'Process bounces automatically (recommended - <a href="#" id="auto_explain">why?</a>)');
define('LNG_Bounce_Auto_Process_Steps', 'To have bounced emails processed automatically, follow these steps');
define('LNG_Bounce_Auto_Process_Step1', 'Go to the <a href="%s" target="_blank">edit page</a> for the "%s" list');
define('LNG_Bounce_Auto_Process_Step2', 'Click the "Bounce Account Details" checkbox and fill in the details');
define('LNG_Bounce_Auto_Process_Step3', 'Setup the cron script on your web server');
define('LNG_Bounce_Auto_Process_Step4', 'Enable bounce processing on the "<a href="%s" target="_blank">Cron Settings</a>" page');
define('LNG_Bounce_Auto_Process_Step5', 'Congratulations, you have just setup automatic bounce processing!');
define('LNG_Bounce_Auto_Button', 'OK, Thanks for the Help');

define('LNG_Bounce_Why_Group_Lists', 'Lists with the same bounce server and username are grouped together. This is because bounce emails from these lists go into the same mailbox, so can be processed at the same time.');
define('LNG_Bounce_Why_Use_Auto', 'Automatic bounce processing is more reliable because it is not susceptible to web browser timeouts. It only needs to be set up once, so it will run even if forgotten about.');
define('LNG_Bounce_Why_Not_Manual', 'Manual bounce processing is susceptible to web browser timeouts, so it may not complete. Also, it needs to be run regularly and forgetting to do so could lead to the sending sever being blacklisted.');
define('LNG_Bounce_Auto_Process_Why', 'Why is automatic bounce processing recommended?');
define('LNG_Bounce_Manual_Process_Why', 'Why is manual bounce processing not recommended?');
define('LNG_Bounce_Manual_Process', 'Process bounces manually (not recommended - <a href="#" id="manual_explain">why?</a>)');

define('LNG_Bounce_Help_HowTo', 'Read the bounce processing "How to" guide for step-by-step help');
define('LNG_Bounce_Help_Work', 'How does bounce processing work?');
define('LNG_Bounce_Help_Customise', 'How to customize bounce processing rules yourself');
define('LNG_Bounce_Help_Gmail', 'Can I use a Gmail account to handle bounced emails');
define('LNG_Bounce_Help_More', 'See more bounce processing help');

define('LNG_Bounce_Connecting', 'Connecting to Mail Server...');
define('LNG_Bounce_Connecting_Msg', 'Please wait while we attempt to connect to your mail server.');
define('LNG_Bounce_Connecting_To', 'Connecting to %s...');

define('LNG_Bounce_Connection_Failed', 'We were unable to login to your mail server (%s). The error message and possible solutions are shown below.');
define('LNG_Bounce_Connection_Success', 'A connection to your mail server (%s) was established successfully! Click the button below to find bounces now.');
define('LNG_Bounce_Find_Email_Step', 'Process Bounces for %s');
define('LNG_Bounce_Remove_Contact_Step', 'Process Bounces for %s');
define('LNG_Bounce_Review_Settings', '<< Review Bounce Settings');
define('LNG_Bounce_Find_Bounces', 'Find Bounces >>');
define('LNG_Bounce_Finding_Bounces', 'Finding Bounces...');
define('LNG_Bounce_Attempt_To_Find', 'Please wait as we attempt to find bounced emails. Do not close this window.');

define('LNG_Bounce_Help_PossibleSolutions', 'Possible solutions are shown below. Click on a link for more information:');
define('LNG_Bounce_Help_PossibleSolutions_Unknown', "We weren't able to find specific information regarding this error but possible solutions are shown below:");

define('LNG_Bounce_Error_Unknown', '(An Unknown Error Has Occurred)');

define('LNG_Bounce_Error_CertFailure', 'SSL Certificate Has Expired Or Is Invalid');
define('LNG_Bounce_Help_CertFailure1', 'Use the "Do not validate certificate" option');

define('LNG_Bounce_Error_InvalidServer', 'Invalid Server');
define('LNG_Bounce_Help_InvalidServer1', 'Double-check the bounce server name');

define('LNG_Bounce_Error_TimeOut', 'Connection Timed Out');
define('LNG_Bounce_Help_TimeOut1', 'Verify your server is not firewalled');
define('LNG_Bounce_Help_TimeOut2', 'Try using a different protocol or port combination');

define('LNG_Bounce_Error_LoginFailed', 'Login Failed');
define('LNG_Bounce_Help_LoginFailed1', 'Check your username and password');

define('LNG_Bounce_Error_ConnRefused', 'Connection Refused');

define('LNG_Bounce_Error_SSLFailed', 'SSL Negotiation Failed');
define('LNG_Bounce_Help_SSLFailed1', 'Try using the "Do not use SSL" and/or the "Do not use TLS" options');

define('LNG_Bounce_Error_InvalidSpec', 'Invalid Remote Specification');
define('LNG_Bounce_Help_InvalidSpec1', 'The PHP software on your server may need SSL support enabled');

define('LNG_Bounce_Found_None_Summary', 'No hard or soft bounce emails were found. There are %d other emails to delete.');
define('LNG_Bounce_Found_Summary', '<b>%s</b> bounced emails were found. There are <b>%s hard bounces</b> and <b>%s soft bounces</b>. You can flag them as inactive or remove them from your list(s) below.');
define('LNG_Bounce_Flag_Hard_Bounces_Inactive', 'Flag Hard Bounces as Inactive (Recommended)');
define('LNG_Bounce_Delete_Hard_Bounces', 'Permanently Delete Hard Bounces From My List(s)');
define('LNG_Bounce_Flag_Soft_Bounces_Inactive', 'Flag Soft Bounces as Inactive (Not Recommended)');
define('LNG_Bounce_Flag_Hard_Bounces_Inactive_Intro', 'A hard bounce indicates an email address for a contact which is invalid or does not exist. <br />Choose this option to mark hard bounces as inactive so you do not send to those contacts again.');
define('LNG_Bounce_Flag_Soft_Bounces_Inactive_Intro', 'A soft bounce indicates a temporary problem, such as a full inbox or deliverability issues.<br />Choose this option to mark soft bounces as inactive so you do not send to those contacts again.');
define('LNG_Bounce_Delete_Hard_Bounces_Intro', 'A hard bounce indicates an email address for a contact which is invalid or does not exist. <br />Choose this option to permanently delete all contacts whose email addresses are invalid.');
define('LNG_Bounce_Process_Now', 'Process Bounces Now');
define('LNG_Bounce_Delete_Now', 'Delete Emails Now');
define('LNG_BounceResults_InProgress', 'Processing Bounces...');
define('LNG_Bounce_PleaseChooseOption', 'Please choose at least one option for processing bounces.');

define('LNG_Bounce_Process_Success', '<b>%d bounces</b> have been processed successfully. Click the button below to process bounces for another list.');
define('LNG_Bounce_Process_Once_More', 'Process Bounces for Another List');
define('LNG_Bounce_Process_Finished', 'I\'m Finished');
define('LNG_Bounce_Finished_Step', 'Process Bounces for %s');
