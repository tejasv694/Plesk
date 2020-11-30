<?php
define('LNG_Addon_splittest_Menu_ViewSplitTests', 'View Split Tests');
define('LNG_Addon_splittest_Menu_ViewSplitTests_Description', 'Send different versions of your email to see which performs better.');

define('LNG_Addon_splittest_Menu_ViewStats', 'Split Test Statistics');
define('LNG_Addon_splittest_Menu_ViewStats_Description', 'See how your split tests performed, including open and click rates.');

define('LNG_Addon_splittest_Permission_Create', 'Create new split test campaigns');
define('LNG_Addon_splittest_Permission_Edit', 'Edit existing split test campaigns');
define('LNG_Addon_splittest_Permission_Delete', 'Delete existing split test campaigns');
define('LNG_Addon_splittest_Permission_Stats', 'View split test campaign statistics');

define('LNG_Addon_splittest_DisableFailed_SendsInProgress', 'There are split test campaigns waiting to be sent. Please delete the scheduled campaigns before disabling the addon.');

define('LNG_Cron_Option_splittest_Heading', 'Split Test Campaigns');

define('LNG_Addon_splittest_PreviewSelected', 'Preview Selected');
define('LNG_Addon_splittest_PreviewNoneSelected', 'Please choose at least one email campaign to preview first.');

define('LNG_Addon_splittest_CreateButton', 'Create a Split Test...');
define('LNG_Addon_splittest_DeleteButton', 'Delete Selected');
define('LNG_Addon_splittest_Heading', 'View Split Tests');
define('LNG_Addon_splittest_Intro', 'A split test allows you to send different versions of the same email campaign to your list and see which gets the better open and click-thru rates.');
define('LNG_Addon_splittest_Empty', 'There are no split tests available. %s');
define('LNG_Addon_splittest_NoneAvailable', 'There are no split tests available. %s');
define('LNG_Addon_splittest_CanCreateMessage', 'Click the "Create Split Test" button below to create one.');

define('LNG_Addon_splittest_Form_Intro', 'A split test allows you to send different versions of an email campaign and see which performed better.');
define('LNG_Addon_splittest_Save', 'Save');
define('LNG_Addon_splittest_SaveSend', 'Save And Send');
define('LNG_Addon_splittest_SaveExit', 'Save And Exit');
define('LNG_Addon_splittest_Cancel', 'Cancel');
define('LNG_Addon_splittest_Form_Settings', 'Split Test Settings');
define('LNG_Addon_splittest_Form_CampaignName', 'Give Your Split Test a Name');
define('LNG_Addon_splittest_Form_CampaignName_Aside', '(Such as \'20% off promo split test\'. The name is for your reference only)');
define('LNG_Addon_splittest_Form_ChooseCampaigns', 'Choose Which Emails to Send');
define('LNG_Addon_splittest_Form_SplitType', 'The Kind of Test to Run?');
define('LNG_Addon_splittest_Form_CreateHeading', 'Create A Split Test');
define('LNG_Addon_splittest_Form_EditHeading', 'Edit A Split Test');

define('LNG_Addon_splittest_FillInField_SplitName', 'Please enter a name for this split test campaign.');
define('LNG_Addon_splittest_ChooseCampaigns', 'Please choose some campaigns for your split test to use.');
define('LNG_Addon_splittest_ChooseSplitType', 'Please choose the type of split test you are going to use.');

define('LNG_Addon_splittest_Percentage_Percentage_Between', 'Please enter a percentage to send this split test campaign to between %d%% and %d%%');
define('LNG_Addon_splittest_Percentage_HoursAfter_Between', 'Please enter a time after which to send the rest of the split test campaign. It must be between %d hours and %d days.');

define('LNG_Addon_splittest_WeightsMustTotal100', 'The combined weights of open tracking and link tracking must total 100%');

define('LNG_Addon_splittest_AddonCreated', 'The split test was created successfully.');
define('LNG_Addon_splittest_AddonNotCreated', 'The split test was not created successfully. Please try again.');

define('LNG_Addon_splittest_copy_successful', 'The split test you selected has been copied to "%s"');
define('LNG_Addon_splittest_copy_unsuccessful', 'The split test was not copied successfully. Please try again.');

define('LNG_Addon_splittest_form_Cancel_Create', 'Are you sure you want to cancel creating a split test?');
define('LNG_Addon_splittest_form_Cancel_Edit', 'Are you sure you want to cancel editing your split test?');

define('LNG_Addon_splittest_form_InvalidWeight_Alert', 'Please enter a weight of between 0 and 100.');
define('LNG_Addon_splittest_form_InvalidWeight_IncorrectTotal', 'The weights must add up to 100 %.');

define('LNG_Addon_splittest_form_SelectCampaigns_Alert', 'Please choose at least 2 campaigns before continuing.');
define('LNG_Addon_splittest_form_EnterName_Alert', 'Please enter a name for this split test.');
define('LNG_Addon_splittest_form_ChoosePercent_Alert', 'You can send your split test campaign to between %d%% and %d%% of your mailing list. Please enter a percentage in that range.');
define('LNG_Addon_splittest_form_ChooseHoursAfter_Alert', 'Please choose a valid time range to send this campaign. You can send after a minimum of %d hours and a maximum of %d days. This gives the system time to find the best performing email.');

define('LNG_Addon_splittest_Percentage_Intro', 'Find a winning email then send it to my list automatically');
define('LNG_Addon_splittest_Form_SampleSize', 'Choose Your Sample Size');
define('LNG_Addon_splittest_Form_SampleSizePercent', '% of the contacts I choose when sending this split test');
define('LNG_Addon_splittest_Form_SendingDelay', 'Delay Time for Sending');
define('LNG_Addon_splittest_Form_SendingDelayLabel', 'after the emails have been sent to the sample size');
define('LNG_Addon_splittest_Percentage_Middle', '% of my contacts<br/>Then send the best performing one to the rest of my list');
define('LNG_Addon_splittest_Percentage_End', 'later');
define('LNG_Addon_splittest_Percentage_BestPerformingIntro', 'The best performing campaign will be chosen based on:');
define('LNG_Addon_splittest_Weight_OpenRate', 'Open Rate Weight');
define('LNG_Addon_splittest_Weight_LinkClick', 'Link Click Weight');
define('LNG_Addon_splittest_Pause', 'Pause');
define('LNG_Addon_splittest_Resume', 'Resume');
define('LNG_Addon_splittest_WaitingToSend', 'Waiting To Send');
define('LNG_Addon_splittest_TimeoutMode', 'In Progress');
define('LNG_Addon_splittest_Hours', 'hours');
define('LNG_Addon_splittest_Days', 'days');
define('LNG_Addon_splittest_TimeoutMode_TipDetails', 'A randomly chosen email from those you selected has been sent to %d%% of your contacts. The system is now waiting %d %s to send emails to the other %d%% of your contacts.');

define('LNG_Addon_splittest_Manage_SplitName', 'Name');
define('LNG_Addon_splittest_Manage_SplitCampaigns', 'Campaigns');
define('LNG_Addon_splittest_Manage_SplitType', 'Type');
define('LNG_Addon_splittest_Manage_SplitCreated', 'Created');
define('LNG_Addon_splittest_Manage_SplitLastSent', 'Last Sent');
define('LNG_Addon_splittest_Manage_SplitAction', 'Action');
define('LNG_Addon_splittest_Manage_Send', 'Send');
define('LNG_Addon_splittest_Manage_Copy', 'Copy');
define('LNG_Addon_splittest_Manage_SplitType_Distributed', 'Split Test');
define('LNG_Addon_splittest_Manage_SplitType_Distributed_TipDetails', 'One of the %d selected email campaigns will be sent randomly to each of the contacts you select when sending.');

define('LNG_Addon_splittest_Manage_SplitType_Percentage', 'Best Performing');
define('LNG_Addon_splittest_Manage_SplitType_Percentage_TipDetails', 'Each of the %d selected emails will be sent to a random sample totalling %d%% of the contacts.<br/><br/>The winning email will then be sent to the other %d%% of your contacts after %d %s.');

define('LNG_Addon_splittest_Manage_LastSent_Never', 'Not Sent');
define('LNG_Addon_splittest_Manage_Edit', 'Edit');
define('LNG_Addon_splittest_Manage_Edit_Disabled', 'You cannot edit a split test campaign while it is being sent.');
define('LNG_Addon_splittest_Manage_Edit_Disabled_Alert', 'You cannot edit a split test campaign while it is being sent. To edit the split test campaign, pause the send on the \\\'View Scheduled Emails Queue\\\' page and then you can edit the split test campaign.');
define('LNG_Addon_splittest_UnableToEdit_SendInProgress', 'You cannot edit a split test campaign while it is being sent.');

define('LNG_Addon_splittest_DeleteOne_Confirm', 'Are you sure you want to delete this split test?');
define('LNG_Addon_splittest_Manage_Delete', 'Delete');
define('LNG_Addon_splittest_Manage_Delete_Disabled', 'You cannot delete a split test campaign while it is being sent.');
define('LNG_Addon_splittest_Manage_Delete_Disabled_Alert', 'You cannot delete a split test campaign while it is being sent. To delete the split test campaign, pause the send on the \\\'View Scheduled Emails Queue\\\' page and then you can delete the split test campaign.');
define('LNG_Addon_splittest_Delete_SelectFirst', 'Please choose at least one split test to delete.');
define('LNG_Addon_splittest_Delete_ConfirmMessage', 'Are you sure you want to delete the selected split tests? This action cannot be undone.');
define('LNG_Addon_splittest_Multi_SelectFirst', 'Please choose one or more split tests first.');

define('LNG_Addon_splittest_ChooseSplittestsToDelete', 'Please choose some split tests to delete.');
define('LNG_Addon_splittest_SplittestDeleted_One', 'The split test has been deleted successfully.');
define('LNG_Addon_splittest_SplittestDeleted_Many', '%s split tests have been deleted successfully.');

define('LNG_Addon_splittest_SplittestNotDeleted_One', 'You cannot delete a split test campaign while it is being sent. Please wait for it to finish sending before trying to delete it.');
define('LNG_Addon_splittest_SplittestNotDeleted_Many', '%s split test campaigns have not been deleted. You cannot delete them while they are sending. Please wait for them to finish sending before trying again.');

define('LNG_Addon_splittest_UnableToLoadSplitTest', 'That split test does not exist. Please try again.');

define('LNG_Addon_splittest_SplittestUpdated', 'The selected split test was updated successfully.');
define('LNG_Addon_splittest_SplittestNotUpdated', 'The split test was not updated. Please try again.');

define('LNG_Addon_splittest_CannotSendPercentage_NoCron', 'To send split tests of the "best performing" type, you need to enable "Split Test Campaigns" from the <a href="%s">Cron Settings</a> tab on the settings page.');

define('LNG_Addon_splittest_Distributed_Intro', 'Find a winning email and show me the results');

define('LNG_Addon_splittest_Send_InvalidSplitTest', 'The split test you are trying to send does not exist. Please try again.');
define('LNG_Addon_splittest_Send_NoListsOrSegments', 'You do not have access to any lists or segments. Please create one or talk to your administrator before trying to send a split test campaign.');
define('LNG_Addon_splittest_Send_ChooseListOrSegment', 'Please choose a list or segment to send your split test campaign to.');

define('LNG_Addon_splittest_Send_Step1', 'Send a Split Test');
define('LNG_Addon_splittest_Send_Step1_Intro', 'To send the selected split test, start by choosing who you want to send it to from the options shown below.');
define('LNG_Addon_splittest_Send_Step1_WhoToSendTo_Heading', 'Who Do You Want To Send To?');
define('LNG_Addon_splittest_Send_Step1_WhoToSendTo', 'I Want To:');
define('LNG_Addon_splittest_Send_Step1_WhoToSendTo_Lists', 'Send the selected split test to all contacts in the selected list(s) below');
define('LNG_Addon_splittest_Send_Step1_WhoToSendTo_Segments', 'Send the selected split test to all contacts in the selected segment(s) below');
define('LNG_Addon_splittest_Send_Step1_List_Heading', 'Select a Contact List(s)');
define('LNG_Addon_splittest_Send_Step1_ChooseList', 'Contact List(s):');
define('LNG_Addon_splittest_SingleContact', '1 active contact');
define('LNG_Addon_splittest_MultipleContacts', ' active contacts');

define('LNG_Addon_splittest_Send_Step1_Segment_Heading', 'Select Segment(s)');
define('LNG_Addon_splittest_Send_Step1_ChooseSegment', 'Segment(s):');

define('LNG_Addon_splittest_Send_CancelPrompt', 'Are you sure you want to cancel sending a split test campaign?');
define('LNG_Addon_splittest_Alert_ChooseSegment', 'Please choose at least one segment before continuing.');
define('LNG_Addon_splittest_Alert_ChooseList', 'Please choose at least one contact list before continuing.');

define('LNG_Addon_splittest_Send_Step2_Heading', LNG_Addon_splittest_Send_Step1);
define('LNG_Addon_splittest_Send_Step2_Intro', 'Fill out the form below to send an email campaign. If you are unsure what any of the advanced options mean then you can skip them.');
define('LNG_Addon_splittest_Send_Step2_EnterSendFromName', "Please enter a 'From Name'");
define('LNG_Addon_splittest_Send_Step2_EnterSendFromEmail', "Please enter a 'From' email address");
define('LNG_Addon_splittest_Send_Step2_EnterReplyToEmail', "Please enter a 'Reply-To' email address");
define('LNG_Addon_splittest_Send_Step2_EnterBounceEmail', "Please enter a 'Bounce' email address");
define('LNG_Addon_splittest_Send_Step2_Settings', 'Split Test Campaign Settings');

define('LNG_Addon_splittest_Send_Step2_FromName', 'Send From This Name: ');
define('LNG_HLP_Addon_splittest_Send_Step2_FromName', 'Which person or company should appear in the \'From Name\' field for this split test campaign?');

define('LNG_Addon_splittest_Send_Step2_FromEmail', 'Send From This Email Address: ');
define('LNG_HLP_Addon_splittest_Send_Step2_FromEmail', 'Which email address should appear in the \'From Email\' field for this split test campaign?');

define('LNG_Addon_splittest_Send_Step2_ReplyToEmail', 'Send Reply Emails To: ');
define('LNG_HLP_Addon_splittest_Send_Step2_ReplyToEmail', 'When a contact receives your email and clicks \'reply\', which email address should that reply be sent to?');

define('LNG_Addon_splittest_Send_Step2_BounceEmail', 'Send Bounced Emails To: ');
define('LNG_HLP_Addon_splittest_Send_Step2_BounceEmail', 'When an email bounces, or is rejected by the mail server, which email address should the error be sent to? If you plan to use the bounce handler, then make sure no other emails will be sent to this address.');

define('LNG_Addon_splittest_Send_Step2_AdvancedOptions', 'Advanced Settings (Optional)');

define('LNG_Addon_splittest_Send_Step2_FirstName', 'My "First Name" Custom Field is: ');
define('LNG_HLP_Addon_splittest_Send_Step2_FirstName', 'If you have a custom field for the \'last name\' of the contact, choose it here so the split test campaigns can be addressed to the person individually. <br/>If you have a combined custom field for the persons name (that is, just one field called \'name\') then choose that field here.');

define('LNG_Addon_splittest_Send_Step2_LastName', 'My "Last Name" Custom Field is: ');
define('LNG_HLP_Addon_splittest_Send_Step2_LastName', 'If you have a custom field for the \'last name\' of the contact, choose it here so the split test campaigns can be addressed to the person individually. <br/>If you have a combined custom field for the persons name (that is, just one field called \'name\') then leave this option empty.');

define('LNG_Addon_splittest_Send_Step2_Multipart', 'Send Your Email as Multipart?');
define('LNG_Addon_splittest_Send_Step2_Multipart_Explain', 'Yes, send the email as multipart');
define('LNG_HLP_Addon_splittest_Send_Step2_Multipart', 'Sending a multipart email will let the contacts email program decide which format (HTML or Text) to display the email in.<br/><br/>It is best to use this if you don\'t give your contacts a choice to which format they receive (e.g. they all subscribe as HTML), when they receive the email their email program will automatically show the correct format.<br/><br/>If unsure, leave this option ticked.');

define('LNG_Addon_splittest_Send_Step2_MustTrackOpens', 'Tracking open rates is required for split test campaigns. You cannot disable this option.');
define('LNG_Addon_splittest_Send_Step2_MustTrackLinks', 'Link tracking is required for split test campaigns. You cannot disable this option.');

define('LNG_Addon_splittest_Send_Step2_TrackOpens', 'Track Open Rates for these Emails?');
define('LNG_Addon_splittest_Send_Step2_TrackOpens_Explain', 'Yes, track opening of emails');
define('LNG_HLP_Addon_splittest_Send_Step2_TrackOpens', 'Track opening of emails when a contact receives an email. This is needed for split test campaign sending and cannot be disabled.');

define('LNG_Addon_splittest_Send_Step2_TrackLinks', 'Track Links Clicked in these Email?');
define('LNG_Addon_splittest_Send_Step2_TrackLinks_Explain', 'Yes, track all links in the email campaigns');
define('LNG_HLP_Addon_splittest_Send_Step2_TrackLinks', 'Track all links licked in the email campaigns. This is needed for split test campaign sending and cannot be disabled.');

define('LNG_Addon_splittest_Send_Step2_EmbedImages', 'Embed Images as Attachments?');
define('LNG_Addon_splittest_Send_Step2_EmbedImages_Explain', 'Yes, embed images in the content');
define('LNG_HLP_Addon_splittest_Send_Step2_EmbedImages', 'This will embed the images from the content inside the email the contacts receive. This may make the email significantly larger but will allow contacts to view the content offline.');

define('LNG_Addon_splittest_Send_Step2_CronOptions', 'Email Scheduling Settings');
define('LNG_Addon_splittest_Send_Step2_SendImmediately', 'Send Your Split Test Now?');
define('LNG_Addon_splittest_Send_Step2_SendImmediatelyExplain', 'Yes, send my split test campaign now (untick to schedule it for a later time or date)');

define('LNG_Addon_splittest_Send_Step2_NotifyOwner', 'Notify Owner About Sending?');
define('LNG_Addon_splittest_Send_Step2_NotifyOwnerExplain', 'Yes, notify the owner of the list(s) when the sending starts and ends');
define('LNG_Addon_splittest_Send_Step2_SendMyCampaignWhen', 'Send My Campaign On:');

define('LNG_Addon_splittest_Send_Step2_SendingInPast', 'You have tried to schedule the email campaign to send in the past. Please choose a date in the future.');
define('LNG_Addon_splittest_Send_Step2_FirstName_Choose', 'Please select your "First Name" custom field');
define('LNG_Addon_splittest_Send_Step2_LastName_Choose', 'Please select your "Last Name" custom field');
define('LNG_Addon_splittest_Send_NoContacts_One_list', 'There are no contacts on the list you chose. Please try again.');
define('LNG_Addon_splittest_Send_NoContacts_Many_list', 'There are no contacts on the lists you chose. Please try again.');
define('LNG_Addon_splittest_Send_NoContacts_One_segment', 'There are no contacts on the segment you chose. Please try again.');
define('LNG_Addon_splittest_Send_NoContacts_Many_segment', 'There are no contacts on the segments you chose. Please try again.');
define('LNG_Addon_splittest_Send_Step2_Size_One', 'The split test campaign will be sent to approximately 1 contact.');
define('LNG_Addon_splittest_Send_Step2_Size_Many', 'The split test campaign will be sent to approximately %s contacts.');

define('LNG_Addon_splittest_Send_Step3_EnterSendFromName', "Please enter a 'From Name'");
define('LNG_Addon_splittest_Send_Step3_EnterSendFromEmail', "Please enter a 'From' email address");
define('LNG_Addon_splittest_Send_Step3_EnterReplyToEmail', "Please enter a 'Reply To' email address");
define('LNG_Addon_splittest_Send_Step3_FieldsMissing', 'One or more fields were missing. Please fill in the necessary details and try again.<br/>%s');

define('LNG_Addon_splittest_Send_Step3_Heading', LNG_Addon_splittest_Send_Step1);
define('LNG_Addon_splittest_Send_Step3_Intro', 'To send your split test campaign now, simply click the <i>Send My Split Test Campaign Now</i> button below.');
define('LNG_Addon_splittest_Send_Step3_SendButton', 'Send My Split Test Campaign Now');
define('LNG_Addon_splittest_Send_Step3_ScheduleButton', 'Schedule My Split Test Campaign');
define('LNG_Addon_splittest_Send_Step3_CancelButton', 'Cancel');
define('LNG_Addon_splittest_Send_Step3_CancelButtonAlert', 'Are you sure you want to cancel sending your split test campaign? If you click ok, it will not be sent.');
define('LNG_Addon_splittest_Send_Step3_SendingCampaignsIntro', 'You are sending the following email campaigns:');
define('LNG_Addon_splittest_Send_Step3_ListsIntro', 'You are sending to the following list(s):');
define('LNG_Addon_splittest_Send_Step3_SegmentsIntro', 'You are sending to the following segment(s):');

define('LNG_Addon_splittest_Send_Step3_JobScheduleTime', 'Your job has been scheduled to run at %s');
define('LNG_Addon_splittest_Send_JobScheduled', 'Your split test campaign has been scheduled.');

define('LNG_Addon_splittest_Send_Step3_Size_One', 'The split test campaign will be sent to 1 contact.');
define('LNG_Addon_splittest_Send_Step3_Size_Many', 'The split test campaign will be sent to %s contacts.');

define('LNG_Addon_splittest_Send_Step3_Send_ConfirmMessage', '*** Important, Please Read ***\n\nA popup window will now appear through which your split test campaign will be sent. Please do not close the window. When your split test campaign has been sent the window will close automatically. If you close the window then your split test campaign won\\\'t be sent.');

define('LNG_Addon_splittest_Send_Step4_Heading', 'Send a Split Test Campaign');
define('LNG_Addon_splittest_Send_Step4_KeepBrowserWindowOpen', 'Please keep this browser window open. If you close it, your email campaign will stop sending which means some of your contacts will not receive your email.');
define('LNG_Addon_splittest_Send_Step4_PauseSending', 'Pause Sending');
define('LNG_Addon_splittest_Send_Step4_TimeSoFar', '%s has passed since you started sending');
define('LNG_Addon_splittest_Send_Step4_TimeLeft', '%s remain until the send is done');
define('LNG_Addon_splittest_Send_Step4_NumberSent_One', '1 email has been sent to your contacts so far');
define('LNG_Addon_splittest_Send_Step4_NumberSent_Many', '%s emails have been sent to your contacts so far');
define('LNG_Addon_splittest_Send_Step4_NumberLeft_One', '1 email is waiting to be sent');
define('LNG_Addon_splittest_Send_Step4_NumberLeft_Many', '%s emails are still in the queue waiting to be sent');
define('LNG_Addon_splittest_Send_Step4_SendFinished', 'Your split test campaign has finished sending.');

define('LNG_Addon_splittest_Send_Step5_Finished_Heading', 'Split Test Campaign Sending Report');
define('LNG_Addon_splittest_Send_Step5_Intro', 'The selected split test campaign has been sent. It took %s to complete.');
define('LNG_Addon_splittest_Send_Step5_SendReport_Success_One', 'The selected split test campaign was sent to 1 contact successfully');
define('LNG_Addon_splittest_Send_Step5_SendReport_Success_Many', 'The selected split test campaign was sent to %s contacts successfully');

define('LNG_Addon_splittest_Send_Paused_Heading', 'Sending Paused');
define('LNG_Addon_splittest_Send_Paused_Success', 'Your split test campaign has been paused. You can resume sending by clicking the <em>Resume</em> link on the <em>Split Test Campaign</em> page.');
define('LNG_Addon_splittest_Send_Paused_Failure', 'Something went wrong when trying to pause the sending of your split test email campaign.');

define('LNG_Addon_splittest_Send_Resumed_Success', 'Your split test campaign has been resumed and is waiting to send.');
define('LNG_Addon_splittest_Send_Resumed_Failure', 'Something went wrong when trying to resume the sending of your split test email campaign. Please try again.');
/**
 * These are required if you are doing a 'scheduled split test'.
 */
define('LNG_Addon_splittest_Job_Subject_Started', 'Split test campaign %s has started sending');
define('LNG_Addon_splittest_Job_Message_Started', 'Split test campaign %s has started sending at %s');

define('LNG_Addon_splittest_Job_Subject_Paused', 'Split test campaign %s has been paused');
define('LNG_Addon_splittest_Job_Message_Paused_One', 'Split test campaign %s was paused at %s. 1 email has been sent so far.');
define('LNG_Addon_splittest_Job_Message_Paused_Many', 'Split test campaign %s was paused at %s. %s emails have been sent so far.');

define('LNG_Addon_splittest_Job_Subject_Complete', 'Split test campaign %s has finished sending');
define('LNG_Addon_splittest_Job_Message_Complete_One', 'Split test campaign %s has finished sending at %s. 1 email was sent.');
define('LNG_Addon_splittest_Job_Message_Complete_Many', 'Split test campaign %s has finished sending at %s. %s emails were sent.');

define('LNG_Addon_splittest_Job_Subject_Timeout', 'Split test campaign %s has finished sending to the first %s %%');
define('LNG_Addon_splittest_Job_Message_Timeout_One', 'Split test campaign %s has finished sending to the first %s %% of your contact list or segment. 1 email has been sent so far.');
define('LNG_Addon_splittest_Job_Message_Timeout_Many', 'Split test campaign %s has finished sending to the first %s %% of your contact list or segment. %s emails have been sent so far.');

define('LNG_Addon_splittest_CampaignsNotDeleted_UsedBySplitTest', 'Some campaigns could not be deleted as they are being used by split test campaigns.');

/**
 * This is shown on the "schedule" page list.
 */
define('LNG_Addon_splittest_Schedule_Description', 'Split Test Campaign');

define('LNG_Addon_splittest_Schedule_JobDeleteFail', 'The split test campaign schedule cannot be deleted while it is sending. To delete it, first pause the schedule.');
define('LNG_Addon_splittest_Schedule_JobsDeleteFail', '%s split test campaign schedules could not be deleted while they are sending. To delete them, first pause the schedule.');

define('LNG_Addon_splittest_Schedule_Edit_Heading', 'Edit Split Test Campaign Schedule');
define('LNG_Addon_splittest_Schedule_Edit_Intro', 'Use the form below to change when your split test campaign will be sent.');
define('LNG_Addon_splittest_Schedule_Edit_CancelPrompt', 'Are you sure you want to cancel editing your scheduled split test campaign?');
define('LNG_Addon_splittest_Schedule_Splittest_Details', 'Split Test Campaign Details');
define('LNG_Addon_splittest_Schedule_MailingLists', 'Contact List(s):');
define('LNG_Addon_splittest_Schedule_Segments', 'Contact List Segment(s):');
define('LNG_Addon_splittest_Schedule_ListIntro', 'Your split test campaign will be sent to ');
define('LNG_Addon_splittest_Schedule_SendingNewsletters', 'Sending Email Campaigns:');
define('LNG_Addon_splittest_Schedule_SendWhen', 'Send Time:');
define('LNG_HLP_Addon_splittest_Schedule_SendWhen', 'Select the time and date when you would like your split test campaign to start sending.');

define('LNG_Addon_splittest_Schedule_Updated', 'Your split test campaign schedule has been updated.');

define('LNG_Addon_splittest_Schedule_JobStatus_Timeout', 'Waiting for timeout');
define('LNG_Addon_splittest_Schedule_JobStatus_Timeout_TipDetails', 'The first part of your split test campaign has been sent. The split test is now waiting for the appropriate delay before sending the best performing campaign to the rest of your contact list.');

/**
 * Stats stuff here.
 */
define('LNG_Addon_splittest_Stats_Heading', 'Split Test Statistics');
define('LNG_Addon_splittest_Stats_Intro', 'Split test campaign statistics allow you to view detailed open, unsubscribe and bounce rates, see how many subscribers clicked on a link etc.');
define('LNG_Addon_splittest_Stats_NoneSent', 'To view statistics you first need to create and send a split test. Click the button below to do that now.');
define('LNG_Addon_splittest_Stats_SendButton', 'Send a Split Test Campaign');
define('LNG_Addon_splittest_Stats_SendMessage', 'Click the <i>Send a Split Test Campaign</i> button below to send one.');

define('LNG_Addon_splittest_Stats_ChooseAction', 'Choose Action');
define('LNG_Addon_splittest_Stats_DeleteSelected', 'Delete Selected');
define('LNG_Addon_splittest_Stats_ExportSelected', 'Export Selected');
define('LNG_Addon_splittest_Stats_PrintSelected', 'Print Selected');

define('LNG_Addon_splittest_Stats_View', 'View');
define('LNG_Addon_splittest_Stats_Delete', 'Delete');
define('LNG_Addon_splittest_Stats_Export', 'Export');
define('LNG_Addon_splittest_Stats_Print', 'Print');

define('LNG_Addon_splittest_StatsDeleted_Success', 'The split test statistics have been deleted successfully.');
define('LNG_Addon_splittest_StatsDeleted_Fail', 'Split test statistics were unable to be deleted.');

define('LNG_Addon_splittest_Stats_NotFinished', 'You cannot perform this action until the split test has finished sending.');

define('LNG_Addon_splittest_Stats_SplitName', 'Split Test Name');
define('LNG_Addon_splittest_Stats_SplitType', 'Split Test Type');
define('LNG_Addon_splittest_Stats_ListNames', 'Sent To');
define('LNG_Addon_splittest_Stats_CampaignNames', 'Emails Sent');
define('LNG_Addon_splittest_Stats_DateStarted', 'Started Sending');
define('LNG_Addon_splittest_Stats_DateFinished', 'Finished Sending');
define('LNG_Addon_splittest_Stats_TotalRecipients', 'Recipients');
define('LNG_Addon_splittest_Stats_Recipient_s', 'Recipient(s)');
define('LNG_Addon_splittest_Stats_TotalOpens', 'Opens');
define('LNG_Addon_splittest_Stats_UniqueOpens', 'Unique Opens');
define('LNG_Addon_splittest_Stats_TotalClicks', 'Clicks');
define('LNG_Addon_splittest_Stats_UniqueClicks', 'Unique Clicks');
define('LNG_Addon_splittest_Stats_TotalUnsubscribes', 'Unsubscribes');
define('LNG_Addon_splittest_Stats_TotalBounces', 'Bounces');
define('LNG_Addon_splittest_Stats_EmailCampaigns', 'Email Campaigns');

define('LNG_Addon_splittest_Stats_WinningCampaign', 'Winning Email');

define('LNG_Addon_splittest_Stats_FinishTime_NotFinished', 'Not finished sending');

define('LNG_Addon_splittest_SplittestTitle', 'Split Test');
define('LNG_Addon_splittest_Winner', 'Winner');
define('LNG_Addon_splittest_Campaign_Statistics', LNG_Addon_splittest_SplittestTitle . ' Campaign Statistics');
define('LNG_Addon_splittest_SendSize', LNG_Addon_splittest_SplittestTitle . ' Send Size');
define('LNG_Addons_splittest_Text', 'Text');
define('LNG_Addons_splittest_Email', 'Email');
define('LNG_Addons_splittest_HTML', 'HTML');

define('LNG_Addon_splittest_WonMessage', 'The \'%s\' campaign won this split test');
define('LNG_Addon_splittest_ClickRate', 'scoring a unique click through rate of %d%%.');
define('LNG_Addon_splittest_OpenRate', 'scoring a unique open rate of %d%%.');
define('LNG_Addon_splittest_WonNone', 'No winner for this split test is able to be determined yet.');

define('LNG_Addon_splittest_ViewMore', 'Click the \'View\' link in the action column to view more statistics.');
define('LNG_Addon_splittest_WinningCampaign', '%s was the winning campaign');
define('LNG_Addon_splittest_WonScoreMessage', LNG_Addon_splittest_SplittestTitle . ' score of %s');
define('LNG_Addon_splittest_SentToMessage', 'Sent to %s');
define('LNG_Addon_splittest_Click', 'Click');
define('LNG_Addon_splittest_Open', 'Open');
define('LNG_Addon_splittest_None', 'None');
define('LNG_Addon_splittest_UniqueOpens', 'Unique Opens');
define('LNG_Addon_splittest_emailopens_unique', 'Unique Opens (Total)');
define('LNG_Addon_splittest_emailopens_unique_precent', 'Unique Opens (Percentage)');
define('LNG_Addon_splittest_linkclicks', 'Link Clicks (Total)');
define('LNG_Addon_splittest_linkclicks_percent', 'Link Clicks (Percentage)');
define('LNG_Addon_splittest_linkclicks_unique', 'Unique Link Clicks (Total)');
define('LNG_Addon_splittest_linkclicks_unique_percent', 'Unique Link Clicks (Percentage)');
define('LNG_Addon_splittest_bouncecount_total', 'Bounce Count (Total)');
define('LNG_Addon_splittest_bouncecount_total_percent', 'Bounce Count (Percentage)');
define('LNG_Addon_splittest_unsubscribecount', 'Unsubscribes (Total)');
define('LNG_Addon_splittest_unsubscribecount_percent', 'Unsubscribes (Percentage)');
define('LNG_SplittestStatsConfirmDelete', 'Are you sure you want to delete these statistics?\nOnce they are deleted they cannot be retrieved.');

define('LNG_Addon_splittest_Stats_Summary', 'Split Test Statistics Summary for %s');
define('LNG_Addon_splittest_Stats_Total_UniqueOpens', 'Total Unique Opens for %s');
define('LNG_Addon_splittest_Stats_Total_LinkClicks', 'Total Unique Link Clicks for %s');
define('LNG_Addon_splittest_Stats_Total_Bounces', 'Total Bounces for %s');
define('LNG_Addon_splittest_Stats_Total_Unsubscribes', 'Total Unsubscribes for %s');
define('LNG_Addon_splittest_Stats_ViewNewsletterStats', 'View statistics for this newsletter');

define('LNG_Addon_splittest_Summary', 'Split Test Statistics Summary');
define('LNG_Addon_splittest_open_summary', 'Open Summary');
define('LNG_Addon_splittest_linkclick_summary', 'Link Click Summary');
define('LNG_Addon_splittest_bounce_summary', 'Bounce Summary');
define('LNG_Addon_splittest_unsubscribe_summary', 'Unsubscribe Summary');
define('LNG_Addon_splittest_PrintSplitTestStatistics', 'Print Split Test Statistics');

define('LNG_Addon_splittest_SendBy', 'Send the Split Test by');
define('LNG_Addon_splittest_ChooseWinnerBy', 'Choose a Winner Based on');

define('LNG_Addon_splittest_Form_AddCampaigns', 'Select Campaigns');
define('LNG_HLP_Addon_splittest_Form_AddCampaigns', 'Choose at least two email campaigns to send. To test different variables (such as subject line, headline or image), simply create one or more variations of your email campaign and then select them from this list.');

define('LNG_Addon_splittest_Splittype_Distributed', LNG_Addon_splittest_Manage_SplitType_Distributed);
define('LNG_HLP_Addon_splittest_Splittype_Distributed', 'Example: You select 4 emails from the list above. You then send this split test to 1,000 contacts (you can choose sending options after saving).<br /><br />In this example each of the 4 emails above is sent to 250 randomly selected contacts from your list.');


define('LNG_Addon_splittest_Percentage_SampleSize', 'The percentage of contacts in your list who should be sent');

define('LNG_Addon_splittest_Splittype_Percentage', LNG_Addon_splittest_Manage_SplitType_Percentage);
define('LNG_HLP_Addon_splittest_Splittype_Percentage', 'Example: You select 4 emails from the list above, specify a sample size of 10% (<em>sample size</em>) and a delay time of 8 hours (<em>delay time</em>). You then send this split test to 1,000 contacts (you can choose sending options after saving).<br /><br />10% (<em>sample size</em>) of your list (or 100 contacts) are split into 4 groups. Each group receives one of the 4 emails selected above. After 8 hours (<em>delay time</em>) a winning email is determined (based on open or click rates) and sent to the other 900 contacts.');

define('LNG_Addon_splittest_Splittype_Percentage_SampleSize', 'Sample Size');
define('LNG_HLP_Addon_splittest_Splittype_Percentage_SampleSize', 'Example: You select 4 emails from the list above, specify a sample size of 10% (<em>sample size</em>) and a delay time of 8 hours (<em>delay time</em>). You then send this split test to 1,000 contacts (you can choose sending options after saving).<br /><br />10% (<em>sample size</em>) of your list (or 100 contacts) are split into 4 groups. Each group receives one of the 4 emails selected above. After 8 hours (<em>delay time</em>) a winning email is determined (based on open or click rates) and sent to the other 900 contacts.');

define('LNG_Addon_splittest_Splittype_Percentage_DelayTime', 'Delay Time');
define('LNG_HLP_Addon_splittest_Splittype_Percentage_DelayTime', 'Example: You select 4 emails from the list above, specify a sample size of 10% (<em>sample size</em>) and a delay time of 8 hours (<em>delay time</em>). You then send this split test to 1,000 contacts (you can choose sending options after saving).<br /><br />10% (<em>sample size</em>) of your list (or 100 contacts) are split into 4 groups. Each group receives one of the 4 emails selected above. After 8 hours (<em>delay time</em>) a winning email is determined (based on open or click rates) and sent to the other 900 contacts.');

define('LNG_Addon_splittest_PerformanceWeighting', 'Performance Weighting');
define('LNG_HLP_Addon_splittest_PerformanceWeighting', 'If you want to place a greater emphasis on how many contacts click a link in the email campaign, increase the link click weight.<br/><br/>If you want to place a greater emphasis on how many contacts open the email campaign, increase the open rate weight.');

define('LNG_Addon_splittest_TotalSendSize', 'Send Size');
define('LNG_Addon_splittest_Stats', 'Statistics');
define('LNG_Addon_splittest_TotalBounces', 'Total Bounces');
define('LNG_Addon_splittest_TotalUnsubscribes', 'Total Unsubscribes');

define('LNG_Addon_splittest_Stats_Snapshot_Heading', 'Statistics Snapshot');

define('LNG_Addon_splittest_Stats_StillSending', 'Still Sending');
define('LNG_Addon_splittest_Stats_StillSending_Tip', 'Your split test is still being sent. When it has finished sending the winning campaign will be listed here.');

define('LNG_Addon_splittest_Stats_DownloadExportedFile', 'The statistics for the selected campaign have been exported. <a href="temp/%s">Click here</a> to download the file.');

define('LNG_Addon_splittest_Winner_Open', 'Number of people who open the email');
define('LNG_Addon_splittest_Winner_Click', 'Number of people who click a link in the email');

define('LNG_Addon_splittest_Distributed_List_1', 'Your emails will be sent in equal groups to your entire list');
define('LNG_Addon_splittest_Distributed_List_2', 'You can then view the best performing email from the split test statistics page');

define('LNG_Addon_splittest_Percentage_List_1_1', 'Emails will be sent in equal groups to');
define('LNG_Addon_splittest_Percentage_List_1_2', 'of your list');
define('LNG_Addon_splittest_Percentage_List_2_1', 'The best peforming email will then be sent to the rest of your list');
define('LNG_Addon_splittest_Percentage_List_2_2', 'later');
define('LNG_Addon_splittest_Percentage_List_3', 'Results of the split test can be viewed from the split test statistics page');

define('LNG_Addon_splittest_Send_CannotSend_StillSending', 'Please wait until previous splittest has been completed before you can shedule another splittest sending.');

define('LNG_Addon_Settings_Header', 'Split Tests');
define('LNG_Addon_splittest_Permission_Send', 'Send Split Test campaigns');
