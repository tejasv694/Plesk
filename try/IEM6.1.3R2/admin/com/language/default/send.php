<?php
/**
* Language file variables for the send management area.
*
* @see GetLang
*
* @version     $Id: send.php,v 1.36 2008/03/04 07:43:44 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the send area... Please backup before you start!
*/

define('LNG_NoLiveNewsletters', 'None of your email campaigns are active.%s');

define('LNG_Send_CancelPrompt', 'Are you sure you want to cancel sending an email campaign?');


define('LNG_SendMailingList', LNG_MailingList);
define('LNG_HLP_SendMailingList', 'Click on the names of the contact lists you want to send this email campaign to. You can send to multiple lists simply by clicking on each list\\\'s name.');

define('LNG_Send_Step2_Intro', 'Use the form below to choose the recipients to receive this email campaign.');

define('LNG_NewsletterDetails', 'Email Campaign Details');


define('LNG_SendMultipart', 'Send Your Email as Multipart?');
define('LNG_SendMultipartExplain', 'Yes, send the email as multipart');

define('LNG_TrackOpens', 'Track Open Rates for HTML Emails?');
define('LNG_TrackOpensExplain', 'Yes, track opening of HTML emails');

define('LNG_TrackLinks', 'Track Links Clicked in this Email?');
define('LNG_HLP_TrackLinks', 'Track all link clicks in this email. After the email is sent, you can view link click details from the statistics page.');
define('LNG_TrackLinksExplain', 'Yes, track all links in this email campaign');

define('LNG_SelectNewsletterToSend', 'Please select an email campaign');

define('LNG_SendImmediately', 'Send Your Email Campaign Now?');
define('LNG_HLP_SendImmediately', 'Do you want to send this email campaign immediately? If not, untick this box and you can choose a specific date and time at which to send the email campaign.');
define('LNG_SendImmediatelyExplain', 'Yes, send my email campaign now (untick to schedule)');

define('LNG_SelectNewsletterPreviewPrompt', 'Please select an email campaign first.');
define('LNG_SelectNewsletterPrompt', 'Select an email campaign to send from the list.');

define('LNG_ReadMore', 'Why approximately?');

define('LNG_EnterSendFromName','Please enter a \\\'From name\\\'');
define('LNG_EnterSendFromEmail','Please enter a \\\'From email\\\'');
define('LNG_EnterReplyToEmail','Please enter a \\\'Reply-To email\\\'');
define('LNG_EnterBounceEmail','Please enter a \\\'Bounce email\\\'');

define('LNG_CronSendOptions', 'Email Scheduling Settings');
define('LNG_SendTime', 'Send Time');
define('LNG_SendDate', 'Send Date');
define('LNG_HLP_SendTime', 'Select the time and date when you would like your email campaign to start sending.');
define('LNG_NotifyOwner', 'Notify Owner About Sending?');
define('LNG_HLP_NotifyOwner', 'Notify the list owner(s) when a scheduled send starts and when it finishes?');
define('LNG_NotifyOwnerExplain', 'Yes, notify the owner of the list(s) when sending starts and ends');

define('LNG_StartSending', 'Send My Email Campaign Now');
define('LNG_Send_Step4_Intro', 'To send your email campaign now, simply click the <em>Send My Email Campaign Now</em> button below.');

define('LNG_Send_TotalRecipients', 'It will be sent to a total of <b>%s</b> contact(s)');


define('LNG_Send_Step4_CronIntro', 'To send your email campaign using the scheduled sending system, simply click the <em>Schedule My Email Campaign</em> button below.');

define('LNG_Send_Step4_CannotSendInPast', 'You have tried to schedule the email campaign to send in the past. Please choose a date in the future.');

define('LNG_Send_Step5', 'Your Email Campaign is Sending...');
define('LNG_Send_NumberLeft_One', '1 email is waiting to be sent');
define('LNG_Send_NumberLeft_Many', '%s emails are still in the queue waiting to be sent');

define('LNG_Send_NumberSent_One', '1 email has been sent to your contacts so far');
define('LNG_Send_NumberSent_Many', '%s emails have been sent to your contacts so far');

define('LNG_Send_TimeSoFar', '%s has passed since you started sending');
define('LNG_Send_TimeLeft', '%s remain until the send is done');

define('LNG_Send_Finished', 'The selected email campaign has been sent. It took %s to complete.');
define('LNG_SendReport_Intro', 'The selected email campaign has been sent. It took %s to complete.');

define('LNG_PauseSending', 'Pause Sending (You can always resume later)');
define('LNG_Send_Paused_Heading', 'Sending Paused');
define('LNG_Send_Paused_Success', 'Sending of your email campaign has been paused. You can resume sending by clicking the <em>Pause</em> link on the <em>Email Campaigns</em> page.');
define('LNG_Send_Paused_Failure', 'Something went wrong when trying to pause the sending of your email campaign.');
define('LNG_Send_Paused', 'You can resume sending your email campaign from the "View Email Campaigns" page.<br/>');

define('LNG_JobScheduled', 'Your job has been scheduled to run at %s');
define('LNG_JobNotScheduled', 'Your job has not been scheduled to run at %s');

define('LNG_SendFinished', 'Your email campaign has finished sending.');

define('LNG_ApproveScheduledSend', 'Schedule My Email Campaign');
define('LNG_CancelScheduledSend', 'Do not send my email campaign');

/**
* different helptips for sending a newsletter for "date subscribed", "opened newsletter" and "clicked link".
*/
define('LNG_Send_FilterByDate', LNG_FilterByDate);

define('LNG_Send_OpenedNewsletter', LNG_OpenedNewsletter);

define('LNG_Send_ClickedOnLink', LNG_ClickedOnLink);


/**
**************************
* Changed/Added in NX 1.3
**************************
*/
define('LNG_Send_NewsletterSubject', 'The subject line of your email campaign is <b>%s</b>');

define('LNG_Send_Step1', 'Send an Email Campaign');
define('LNG_Send_Step2', 'Send an Email Campaign');
define('LNG_Send_Step3', 'Send an Email Campaign');
define('LNG_SendNewsletter', 'Send an Email Campaign');
define('LNG_Send_Step4', 'Send an Email Campaign');

define('LNG_NoLiveNewsletters_HasAccess', ' Please go to the <a href="index.php?Page=Newsletters">View Email Campaigns</a> page and make the email campaign active.');

define('LNG_FilterOptions_Send', 'Who Do You Want to Send to?');
define('LNG_ShowFilteringOptions_Send', 'I Want to:');

/**
*************************
* Changed/Added in NX 1.4
*************************
*/
define('LNG_Send_Finished_Heading', 'Email Campaign Sending Report');

define('LNG_Send_Resend', 'Resend Your Email Campaign');
define('LNG_Send_Resend_Intro', 'To resend this email campaign to the recipients who didn\'t receive it the first time, click the "Start Sending" button below.');
define('LNG_Send_Resend_TotalRecipients', 'Total recipient(s) left to send to: %s');
define('LNG_Send_Resend_Count_One', 'You have tried to resend this email campaign 1 time. You can only resend this email campaign %s more times.');
define('LNG_Send_Resend_Count_One_OneLeft', 'You have tried to resend this email campaign 1 time. You can only resend this email campaign 1 more time.');
define('LNG_Send_Resend_Count_Many', 'You have tried to resend this email campaign %s times. You can only resend this email campaign %s more times.');
define('LNG_Send_Resend_Count_Many_OneLeft', 'You have tried to resend this email campaign %s times. You can only resend this email campaign 1 more time.');
define('LNG_Send_Resend_Count_Maximum', 'You have already tried to resend this email campaign %s times and you cannot send it any more. Please contact your system administrator.');

define('LNG_SendReport_Failure_Link', '<a href="#" style="color: blue;" onclick="javascript: ShowReport(\'%s\'); return false;">[ Click here for more information ]</a>');
define('LNG_SendReport_Failure_Reason_20', 'there was a problem with the mail server. Check with your system administrator to see if there if there is a problem with the mail server, or if there is a limit on the number of emails you are allowed to send per hour.');

define('LNG_SendProblem_Report_Invalid_Heading', 'Invalid Report');
define('LNG_SendProblem_Report_Invalid_Intro', 'The report you have chosen is invalid. Please try again');
define('LNG_SendProblem_InvalidReportURL', LNG_SendProblem_Report_Invalid_Intro);

define('LNG_SendProblem_Report_MailServer_Problem_Heading', 'Problem with mail server');

define('LNG_SendProblem_Report_SMTPMailServer_Problem_Heading', 'Problem with mail server');

define('LNG_Send_Step4_ChooseNewsletter', 'Please choose an email campaign to send.');

/**
*************************
* Changed/Added in NX 1.4.1
*************************
*/

define('LNG_Send_NoCronEnabled_Explain_NotAdmin', 'This email campaign will be sent immediately. If you\'d like the ability to schedule your email campaigns please contact your administrator.');

/**
*************************
* Changed/Added in NX 1.5
*************************
*/
define('LNG_HLP_SendNewsletter', 'Which email campaign would you like to send to your contacts?');

define('LNG_HLP_SendMultipart', 'Sending a multipart email will let the contacts email program decide which format (HTML or Text) to display the email in.<br/><br/>It is best to use this if you don\\\'t give your contacts a choice to which format they receive (e.g. they all subscribe as HTML), when they receive the email their email program will automatically show the correct format.<br/><br/>If unsure, leave this option ticked.');

define('LNG_HLP_TrackOpens', 'Track opening of emails when a contact receives an email. This only applies to HTML email campaigns.');

define('LNG_SendSize_One', 'This email campaign will be sent to approximately 1 contact.');

define('LNG_Send_SubscriberList', 'Your email campaign will be sent to <b>%s</b>');

define('LNG_SendReport_Success_One', 'The selected email campaign was sent to 1 contact successfully');
define('LNG_SendReport_Success_Many', 'The selected email campaign was sent to %s contacts successfully');

define('LNG_HLP_Send_FilterByDate', 'This option will allow you to only send to contacts who have subscribed before, after or between particular dates. To send to all contacts, leave this option unticked.');

define('LNG_HLP_Send_OpenedNewsletter', 'This option will allow you to send only to contacts who have opened a particular email campaign or autoresponder sent to this contact list. To send to all contacts, leave this option unticked.');

define('LNG_HLP_Send_ClickedOnLink', 'This option will allow you to send only to contacts who have clicked on a particular link in an email campaign or autoresponder that was sent to this contact list. To search for all contacts, leave this option unticked.');

define('LNG_SendShowFilteringOptionsExplain', 'Send an email to contacts who match my search criteria in the selected list(s) below');
define('LNG_SendDoNotShowFilteringOptionsExplain', 'Send an email to all contacts in the selected list(s) below');

define('LNG_SendShowFilteringOptionsOneListExplain', 'Send an email to contacts in my list who match my search criteria');
define('LNG_SendDoNotShowFilteringOptionsOneListExplain', 'Send an email to all contacts in my list');

define('LNG_SendReport_Failure_One', 'The email campaign was not sent to 1 contact. Once the problem(s) have been fixed, you can resend your email campaign to this contact.');
define('LNG_SendReport_Failure_Many', 'The email campaign was not sent to %s contacts. Once the problem(s) have been fixed, you can resend your email campaign to these contacts.');

define('LNG_SendReport_Failure_Reason_Many', '%s contacts did not receive an email because %s');
define('LNG_SendReport_Failure_Reason_One', '1 contact did not receive an email because %s');
define('LNG_SendReport_Failure_Reason_1', 'there was a problem with their contact information. For example, they unsubscribed between when you started sending the email campaign and when we were trying to send to them.');

define('LNG_SendReport_Failure_Reason_10', 'they would have received a blank email. If you are sending a html only email campaign to a text only contact, there is nothing to send to this particular contact. Instead of sending a blank email, no email was sent. To fix this, go to "View Contacts", choose your contact list and search for contacts who are set to receive "Text" formatted emails. You will need to change them to receive HTML formatted emails.');

define('LNG_SendProblem_Report_Subscriber_Problem_Heading', 'Problem with contacts');
define('LNG_SendProblem_Report_Subscriber_Problem_Intro', 'The following contacts have been unsubscribed from your list before your email campaign was sent to them');

define('LNG_SendProblem_Report_Email_Problem_Heading', 'Problem with contacts');
define('LNG_SendProblem_Report_Email_Problem_Intro', 'The following contacts were not sent the email campaign because they are most likely set to receive Text-Only emails, they cannot receive HTML-only emails. You can edit your email campaign to include both a HTML version and a Text version, or you can edit the contacts to allow them to receive HTML emails.');

define('LNG_SendProblem_Report_MailServer_Problem_Intro', 'The following contacts were not sent an email because the mail server did not accept the email. This could be because the mail server was down or because your system administrator has set a limit on the number of emails you can send per hour. Please contact your system administrator or hosting company and see if they have a limit on the number of emails you can send per hour.');

define('LNG_SendProblem_Report_SMTPMailServer_Problem_Intro', 'The following contacts were not sent an email because the mail server did not accept the email. Please check your SMTP server details and try again. If you are unsure of this, please contact your system administrator.');

define('LNG_Send_Step5_KeepBrowserWindowOpen', '<div style="padding:10px; background-color:#FAD163">Please keep this browser window open. If you close it, your email campaign will stop sending which means some of your contacts will not receive your email.</div>');

define('LNG_Send_Step1_Intro', 'Before you can send an email campaign, please select which contact list(s) you want to send to.');

define('LNG_Send_Step3_Intro', 'Fill out the form below to send an email campaign. If you are unsure what any of the advanced options mean then you can skip them.');

define('LNG_ReadMoreWhyApprox', 'If you are scheduling this email campaign to be sent at a later date, then the number of people it is sent to may change as people subscribe or unsubscribe from your contact list.');

define('LNG_SendToTestListWarning', 'This email campaign has not been sent before. You should send it to a test list before you send it to your contacts.');

define('LNG_Send_Step1_ChooseListToSendTo', 'Please choose one or more contact list to send your email campaign to.');

define('LNG_Send_Not_Completed', 'If your email can\'t be sent it will be flagged on the <em>View Email Campaigns</em> page with an option to resend it');

define('LNG_SendShowSegmentOptionsExplain', 'Send an email to all contacts in the selected segment(s) below');
define('LNG_SegmentDetails', 'Select Segment(s)');
define('LNG_SendToSegment', LNG_Segments);
define('LNG_HLP_SendToSegment', 'To send to multiple segments at once, simply tick the segments you want to send to. Please note that only segments containing at least one active contact (those which haven\\\'t bounced or been unsubscribed) will be shown here.');
define('LNG_Send_Step1_ChooseSegmentToSendTo', 'Please choose one or more segment to send your email campaign to.');

define('LNG_Send_Subscribers_Search_Step2', 'Use the form below to choose which contacts receive your email. You don\'t have to fill in all fields, only the ones you want to filter on. ' . LNG_GuideToFiltering);

define('LNG_SendMyEmailCampaignOn', 'Send My Email Campaign on');
define('LNG_Schedule_At', 'at');
define('LNG_AdvancedSendingOptions', 'Advanced Settings (Optional)');
define('LNG_WhichCampaignToSend', 'Email Campaign Settings');
define('LNG_WhichEmailToSend', 'Send This Email Campaign:');
define('LNG_SelectFirstNameOption', 'Please select your "first name" custom field');
define('LNG_SelectLastNameOption', 'Please select your "last name" custom field');
define('LNG_ConfirmCancelSend', 'Are you sure you want to cancel sending your email campaign? If you cancel it will not be sent.');
define('LNG_ConfirmCancelSchedule', 'Are you sure you want to cancel scheduling your email campaign? If you cancel it will not be sent.');
define('LNG_PopupSendWarning', '*** Important, Please Read ***\n\nA popup window will now appear through which your email campaign will be sent. Please do not close the window. When your email campaign has been sent the window will close automatically. If you close the window then your email campaign won\\\'t be sent.');

/**
**************************
* Changed/Added in 5.5.0
**************************
*/
define('LNG_Send_NewsletterName', 'Your email campaign is called <b>%s</b>');

/**
**************************
* Changed/Added in 5.5.5
**************************
*/
define('LNG_Send_TotalRecipients_Cron', 'It will be sent to approximately <b>%s</b> contact(s)');

/**
**************************
* Changed/Added in 5.5.8
**************************
*/
define('LNG_SendReport_Failure_Reason_30', 'there was a problem sending to this email. Either it is invalid (if so, double-check the email address), or the SMTP server information you have provided is not correct (if so, check with your system administrator).');

/**
**************************
* Changed/Added in 5.6.6
**************************
*/

define('LNG_SendNewsletterArchive_DeactivatedWarning', 'Archive is deactivated for this email campaign, as a result RSS and web version of the email will be disabled.');

