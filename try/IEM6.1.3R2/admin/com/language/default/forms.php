<?php
/**
* Language file variables for the forms area.
*
* @see GetLang
*
* @version     $Id: forms.php,v 1.41 2008/02/28 00:11:44 hendri Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the forms area... Please backup before you start!
*/

define('LNG_FormConfirmPage_Unsubscribe_Subject', 'Unsubscribe Request');
define('LNG_FormThanksPage_Unsubscribe_Subject', 'Your subscription is now cancelled.');

define('LNG_Form_Edit_Disabled', 'You cannot edit this form because you do not have access.');
define('LNG_Form_Delete_Disabled', 'You cannot delete this form because you do not have access.');
define('LNG_Form_Copy_Disabled', 'You cannot copy this form because you do not have access.');

define('LNG_Preview_Form', 'Preview Form');

define('LNG_CreateForm', 'Create a Website Form');
define('LNG_CreateFormButton', 'Create a Website Form...');

define('LNG_CreateFormCancelButton', 'Are you sure you want to cancel creating a new form?');
define('LNG_CreateFormHeading', 'Form Name &amp; Type');

define('LNG_EditForm', 'Edit a Website Form');
define('LNG_EditFormIntro', 'Complete the form below to update the selected website form. When you\'re done you can get the new HTML to add to your website.');
define('LNG_EditFormCancelButton', 'Are you sure you want to cancel updating your form?');
define('LNG_EditFormHeading', 'Edit Form Details');

define('LNG_FormDetails', 'Form Details');

define('LNG_NoSuchFormDesign', 'That form design does not exist. Please try again.');
define('LNG_NoSuchForm', 'That form no longer exists. Please try again.');

define('LNG_FormsManage', 'View Website Forms');

define('LNG_EnterFormName', 'Please enter a name for this form.');

define('LNG_EnterSendFromName', 'Enter a name to display in the \'From\' field');
define('LNG_EnterSendFromEmail', 'Enter an email address to send the emails from.');
define('LNG_EnterConfirmSubject', 'Enter a subject for the confirmation email.');
define('LNG_EnterThanksSubject', 'Enter a subject for the thanks email.');

define('LNG_FormName', 'Form Name');

define('LNG_FormNameIsNotValid', 'Form Name is not Valid');
define('LNG_FormChooseList', 'Choose lists to include on this form');
define('LNG_UnableToCreateForm', 'Unable to create form');
define('LNG_FormCreated', 'The new form has been created successfully');

define('LNG_DeleteFormPrompt', 'Are you sure you want to delete this form?');

define('LNG_FormDeleteSuccess_One', 'The selected form has been deleted successfully. Make sure you remove it from your web site as it will no longer work.');
define('LNG_FormDeleteSuccess_Many', '%s forms deleted successfully. Please make sure you remove them from your websites.');
define('LNG_FormDeleteFail_One', 'Form not deleted successfully. Please try again.');
define('LNG_FormDeleteFail_Many', '%s form not deleted successfully. Please try again.');

define('LNG_ConfirmRemoveForms', 'Are you sure you want to delete the selected forms?');
define('LNG_ChooseFormsToDelete', 'Please choose one or more forms first.');
define('LNG_Delete_Form_Selected', 'Delete Selected');

define('LNG_HLP_FormName', 'The name of the form. This is only used in the management area, not on your website.');

define('LNG_UnableToUpdateForm', 'Unable to update form');
define('LNG_FormUpdated', 'Form updated successfully');

define('LNG_NoForms', 'No forms have been created.%s');
define('LNG_NoForms_HasAccess', ' Click the <em>Create a Website Form...</em> button below to create one.');

define('LNG_FormCopySuccess', 'Form was copied successfully.');
define('LNG_FormCopyFail', 'Form was not copied successfully.');

define('LNG_SubscriberChooseFormat', 'Format Options');
define('LNG_ForceHTML', 'HTML');
define('LNG_ForceText', 'Text');

define('LNG_SubscriberChangeFormat', 'Change Format');


define('LNG_FormType', 'Form Type');
define('LNG_FormType_Subscribe', 'Subscription');
define('LNG_FormType_Unsubscribe', 'Unsubscribe');
define('LNG_FormType_ModifyDetails', 'Modify Details');
define('LNG_FormType_SendToFriend', 'Send to Friend');

define('LNG_ContactForm', 'Email New Contacts Details to You');
define('LNG_HLP_ContactForm', 'This subscription form will also act as a contact form. You will receive an email with the contents of the form once it has been filled in, the user will be subscribed to your list and sent to your thank you page.<br/><br />If they are already subscribed to your list then they will be shown the thank you page instead of an error message.');

define('LNG_UseCaptcha', 'Use CAPTCHA Form Security');

define('LNG_RequireConfirmation', 'Use Double Opt-In Confirmation');
define('LNG_RequireConfirmationExplain', 'Yes, use double opt-in email confirmation');

define('LNG_ListsToInclude', 'Choose Contact Lists');
define('LNG_HLP_IncludeLists', 'Which lists should the visitor be able to subscribe to/unsubscribe from on this form?');

define('LNG_FormDesign', 'Choose a Form Design');
define('LNG_HLP_FormDesign', 'This will give you an idea of how your form will look on your site. You can modify the HTML code if you would like to change this later.');

define('LNG_HLP_OrderCustomFields', 'You can change the order your custom fields appear in your form.<br/>To move something up or down, highlight the field name and click the Up or Down arrow.');
define('LNG_Email_Required_For_Form', 'Email Address (Required)');

define('LNG_ChooseList_For_Form', 'List Choices');
define('LNG_ChooseCustomFieldsToInclude', 'Please choose custom fields to include in your form.');
define('LNG_ChooseCustomFieldToOrder', 'Please choose the custom field you want to re-order');

define('LNG_FormSubmit', 'Submit');
define('LNG_FormClear', 'Reset');

define('LNG_FormOptions', 'Form Options');

define('LNG_FormSendFromName', 'Send From Name');
define('LNG_FormSendFromEmail', 'Send From Email');
define('LNG_FormReplyToEmail', 'Reply-To Email');
define('LNG_FormBounceEmail', 'Bounce Email');

define('LNG_ConfirmSubject', 'Email Subject');

define('LNG_ConfirmPageHTML', 'For the Confirmation Page');
define('LNG_ConfirmPageURL', '<b><i>OR</i></b> &nbsp;Confirm Page URL');

define('LNG_ConfirmTextVersion', 'Confirmation Email (Text)');
define('LNG_ConfirmHTMLVersion', 'Confirmation Email (HTML)');

define('LNG_ThanksPageHTML', 'For the Thank You Page');
define('LNG_ThanksPageURL', '<b><i>OR</i></b>&nbsp; Thank You Page URL');

define('LNG_ThanksSubject', 'Email Subject');
define('LNG_ThanksTextVersion', 'Thank You Email (Text)');
define('LNG_ThanksHTMLVersion', 'Thank You Email (HTML)');

define('LNG_FormFormNameIsNotValid', 'Form name is not valid.');
define('LNG_FormFormDesignIsNotValid', 'Form design is not valid.');
define('LNG_FormFormTypeIsNotValid', 'Form type is not valid.');
define('LNG_FormRequireConfirmationIsNotValid', 'Please choose whether this form requires confirmation or not.');

define('LNG_FormDisplayPageOptions', 'Form Display Options');
define('LNG_FormConfirmEmailOptions', 'Confirmation Email Options');
define('LNG_FormThanksEmailOptions', 'Thanks Email Options');

define('LNG_FormThanksPageOptions', 'Thank You Page Options');
define('LNG_FormErrorPageOptions', 'Error Page Options');
define('LNG_ErrorPageHTML', 'For the Error Page');
define('LNG_ErrorPageURL', '<b><i>OR</i></b>&nbsp; Error Page URL');

define('LNG_Form_ChooseFormat', 'Preferred Format');
define('LNG_Form_EmailAddress', 'Your Email Address');
define('LNG_GetHTML', 'Get&nbsp;HTML');

define('LNG_FormGetHTML_Heading', 'Add the Form to Your Website');
define('LNG_FormGetHTML_Introduction', 'The HTML to display your website form is shown below. To copy it, click in the textbox and press Ctrl+C on your keyboard.');
define('LNG_FormGetHTML_Options', 'Add the Form to Your Website');
define('LNG_FormHTML', 'Website Form HTML Code');
define('LNG_HLP_FormHTML', 'This is the code you place on your website to let your visitors subscribe to your email campaigns. Simply select all of the code, right click in the text box, choose copy. Then edit your web page, and paste the code where you want to display the signup form.');

/**
* Confirmation options.
*/
define('LNG_FormConfirmPage_Subscribe_HTML',
'
<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>Your email subscription is almost complete...</b><br><br>
		An email has been sent to the email address you entered. In this email is a confirmation link. Please click on this link to confirm your subscription.<br><br>
		Once you\'ve done this your subscription will be complete.<br><br>
		<a href="javascript:history.back()">&laquo; Go Back</a>
	</div>
</div>
</body>
</html>');


define('LNG_FormConfirmPage_Subscribe_Subject', 'Confirm your subscription');

define('LNG_FormConfirmPage_Subscribe_Email_Text', "Thank you for subscribing to our newsletter.\n\nTo finalize your subscription, please click on the confirmation link below. Once you've done this, your subscription will be complete.\n\n%%CONFIRMLINK%%\n\n");

define('LNG_FormConfirmPage_Subscribe_Email_HTML', "
<html>
<body style='font:12px tahoma'>
<b>Please confirm your subscription</b>
<br><br>
Thank you for subscribing to our newsletter.<br><br>To finalize your subscription, please click on the confirmation link below. Once you've done this, your subscription will be complete.<br><br>
<a href='%%CONFIRMLINK%%' target='_blank'>Please click here to confirm your subscription</a><br><br>or copy and paste the following URL into your browser:<br>
%%CONFIRMLINK%%");

//define('LNG_FormConfirmPage_Unsubscribe_HTML', 'Please confirm you want to be removed from the list before we action it.<br/>');
//define('LNG_FormConfirmPage_Unsubscribe_Subject', 'Please confirm you want to unsubscribe');

/**
* Some form options are disabled.
*/
define('LNG_GetHTML_ModifyDetails_Disabled', 'You cannot Get HTML for the modify details form.');
define('LNG_GetHTML_ModifyDetails_Disabled_Alert', 'You cannot place a modify details form on your website. To use this form, edit an email campaign or autoresponder and click the Insert Custom Field link at the bottom of the editor to include a link to this form.');
define('LNG_GetHTML_SendFriend_Disabled', 'You cannot place a send to friend form on your website. To use this form, edit an email campaign or autoresponder and click the Insert Custom Field link at the bottom of the editor to include a link to this form.');
define('LNG_GetHTML_SendFriend_Disabled_Alert', 'You cannot place a send-to-friend form on your website.\nTo use this form, edit an email campaign or autoresponder and include a link to the form.');

/**
* For modify details and send-to-friend forms, we have extra html editing options.
*/
define('LNG_FormEditHTMLOptions', 'Edit Form HTML');
define('LNG_EditFormHTML', 'Edit Form HTML');
define('LNG_HLP_EditFormHTML', 'Customize the way your form looks by modifying the default HTML code.<br/><br/>You must leave the form tag, the field names and the placeholders as they are.');

define('LNG_FormHasBeenChanged', 'Warning - the form has been changed. New HTML code will be generated for this form.\nDo you wish to continue?');

define('LNG_FormThanksPage_Subscribe_Subject', 'Your subscription is now complete.');

/**
* These are used if the signup form is a contact form as well.
*/

//define('LNG_FormThanksPage_Unsubscribe_Subject', 'You have been unsubscribed.');



/**
* Error page
*/

define('LNG_FormThanksPageHTML_Modify', '
<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>Your modifications have been completed successfully.</b><br><br>
		The changes made to your details stored with us have been completed successfully.
		<br><br>
	</div>
</div>
</body>
</html>
');


define('LNG_FormErrorPageHTML_Modify', '
<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>An error has occurred.</b><br><br>
		An error(s) has occurred trying to change your details:
		%%GLOBAL_Errors%%
		<br><br>
		<a href="javascript:history.back()">&laquo; Go Back</a>
	</div>
</div>
</body>
</html>
');


/**
* Send-to-Friend stuff.
*/

define('LNG_FormThanksPageHTML_SendFriend', '<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>Your email was forwarded successfully.</b><br><br>
		Thank you for forwarding this email. It has been sent to your friend.
	</div>
</div>
</body>
</html>');
define('LNG_FormErrorPageHTML_SendFriend', '<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>An error has occurred.</b><br><br>
		An error(s) has occurred trying to send this email to your friend:
		%%GLOBAL_Errors%%
		<br><br>
		<a href="javascript:history.back()">&laquo; Go Back</a>
	</div>
</div>
</body>
</html>');
define('LNG_SendFriendTextVersion', 'Email Header (Text)');
define('LNG_SendFriendHTMLVersion', 'Email Header (HTML)');


/**
* Javascript/customfield stuff.
*/
define('LNG_Form_Javascript_Field', 'Please enter a value for field %s');
define('LNG_Form_Javascript_Field_Choose', 'Please choose an option for field %s');
define('LNG_Form_Javascript_Field_Choose_Multiple', 'Please choose one or more options for field %s');
define('LNG_Form_Javascript_Field_NumberCheck', 'Please enter a numeric value for field %s');
define('LNG_Form_Javascript_EnterEmailAddress', 'Please enter your email address.');
define('LNG_Form_Javascript_ChooseFormat', 'Please choose a format to receive your email campaigns in');
define('LNG_Form_Javascript_EnterCaptchaAnswer', 'Please enter the security code shown');
define('LNG_Form_EnterCaptcha', 'Enter the security code shown');

/**
* Buttons etc for form designs.
*/
define('LNG_Form_Subscribe_Button', 'Subscribe');

define('LNG_Form_Unsubscribe_Button', 'Unsubscribe');

define('LNG_Form_ModifyDetails_Button', 'Update your details');

define('LNG_Form_SendFriend_Button', 'Send to your friend');
define('LNG_Form_SendFriend_YourName', 'Your Name : ');
define('LNG_Form_SendFriend_YourEmailAddress', 'Your Email Address : ');
define('LNG_Form_SendFriend_FriendsName', 'Your Friends Name : ');
define('LNG_Form_SendFriend_FriendsEmailAddress', 'Your Friends Email Address : ');
define('LNG_Form_SendFriend_Introduction', 'Hey, I found this really interesting newsletter that I thought you might like to read for yourself.');

/**
**************************
* Changed/added in NX1.0.7
**************************
*/
define('LNG_FormContentsHaveChanged', 'Warning - the form has been changed. New HTML code has been generated for this form. <a href="index.php?Page=Forms&Action=View&id=%d" target="_blank">View the old html code.</a>');

/**
**************************
* Changed/added in NX 1.3
**************************
*/

define('LNG_HLP_ErrorPageHTML', 'Enter the content that should appear on the error page.');

define('LNG_FormSendFriendPage_Email_HTML', '<div style="padding: 5px; border: 1px solid #EFECBA; background-color: #FBFAE7; text-align: center; font-family: tahoma; font-size: 11px;">This email was forwarded to you by %%REFERRER_EMAIL%%.</div>');
define('LNG_FormSendFriendPage_Email_Text', "This email was forwarded to you by %%REFERRER_EMAIL%%.");

define('LNG_ErrorPageHTML_Modify', LNG_ErrorPageHTML);
define('LNG_HLP_ErrorPageHTML_Modify', 'This is the HTML page that is shown when an error occurs. You can modify
this using HTML or leave as is for the default \\\'Error\\\' page.');

define('LNG_ErrorPageHTML_SendFriend', LNG_ErrorPageHTML);

define('LNG_ErrorPageHTML_Subscribe', LNG_ErrorPageHTML);

define('LNG_FormThanksPageHTML_Unsubscribe',
'
<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 30px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
	margin-top: 10px;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		Sorry to see you go!
	</div>
</div>
</body>
</html>
');


/**
***************************
* Changed/Added in NX 1.3.2
***************************
*/
define('LNG_ErrorPageHTML_Unsubscribe', 'Error Page HTML');


/**
****************************
* Changed/added in IEM 5.0.0
****************************
*/

define('LNG_EnterReplyToEmail', 'Enter an email address for the contact to reply to.');
define('LNG_EnterBouceEmail', 'Enter an email address  in case the form bounces back from the contact.');

define('LNG_HLP_SubscriberChooseFormat', 'Would you like to give your contacts the option to choose which format they will receive your email campaigns in?');
define('LNG_ChooseFormat', 'Allow Contact to Choose');

define('LNG_HLP_SubscriberChangeFormat', 'Would you like your contacts to be able to change which type of emails they receive? They will be able to either switch from html to text, or text to html.');
define('LNG_SubscriberChangeFormatExplain', 'Yes, allow the contact to change their email format.');

define('LNG_HLP_RequireConfirmation', 'Do you want the contact to receive a confirmation email with a link they must click to verify their action before they are added to or remove from your list?<br /><br />Double opt-in is the industry standard, so if you\\\'re unsure you should tick this box.');

define('LNG_HLP_SendThanks', 'If you tick this box then a thank you email will be sent to the contact once they fill out the form. You can customize the thank you email on the next page.');
define('LNG_SendThanksExplain', 'Yes, send the contact a thank you email');

define('LNG_SubscriberFormat_For_Form', 'Contact Format');

define('LNG_HLP_ConfirmSubject', 'The subject of the confirmation email sent to the new contact.');

define('LNG_HLP_ThanksPageHTML', 'This is the HTML page that is shown when a contact has completed the subscription process. You can modify this using HTML or leave as is for the default \\\'Thank you\\\' page.');

define('LNG_HLP_ThanksPageURL', 'If you have already uploaded your thank you page, enter the URL to that file here and contacts will be taken to that page instead.');

define('LNG_HLP_ThanksSubject', 'The subject of the thanks email sent to the new contact.');

define('LNG_FormSendThanksIsNotValid', 'Please choose whether this form requires a thanks email to be sent to the contact.');
define('LNG_FormSubscriberChooseFormatIsNotValid', 'Please choose whether this form allows the contact to choose their format.');

define('LNG_HLP_ErrorPageURL', 'If you have already uploaded your error page, enter the URL to that file here and contacts will be taken to that page instead.');

define('LNG_SendFriendPageIntro', 'A Send to a Friend form is used to allow contacts to forward your email onto their friends. This form can only be included inside an email and is auto-generated when a user clicks it.');

define('LNG_HLP_SendFriendTextVersion', 'This text is placed at the beginning of the email your contact is forwarding.<br/><br/>You should include a link to your subscription form on your web site so the recipient can sign up if they want to.');

define('LNG_HLP_SendFriendHTMLVersion', 'This HTML is placed at the beginning of the email your contact is forwarding.<br/><br/>You should include a link to your email campaign subscription form on your web site so the recipient can sign up if they want to.');

define('LNG_HLP_ConfirmPageHTML_Subscribe', 'This is the HTML page that is shown to a contact letting them know they need to confirm their email subscription. You can modify this using HTML or leave as is for the default \\\'Confirmation\\\' page.');
define('LNG_HLP_ConfirmTextVersion_Subscribe', 'This is the Text page that is shown to a contact letting them know they need to confirm their email subscription. You can modify this using Text or leave as is for the default \\\'Confirmation\\\' page.');
define('LNG_HLP_ConfirmHTMLVersion_Subscribe', 'This is the HTML version of the email that is sent to your contact telling them to confirm their subscription. You can modify this using HTML or leave as is for the default \\\'Confirmation\\\' email.');
define('LNG_HLP_ThanksTextVersion_Subscribe', 'This is the Text version of the email that is sent to your contact thanking them for subscribing. You can modify this using Text or leave as is for the default \\\'Thank you\\\' email.');
Define('LNG_HLP_ThanksHTMLVersion_Subscribe', 'This is the HTML version of the email that is sent to your contact thanking them for subscribing. You can modify this using HTML or leave as is for the default \\\'Thank you\\\' email.');

define('LNG_HLP_ErrorPageHTML_SendFriend', 'This is what your contacts will see if there was an error when they tried to forward this newsletter to their friends.');

define('LNG_HLP_ErrorPageHTML_Subscribe', 'This is what your contacts will see if there was an error during the sign-up process. For example, they are already subscribed to your contact list.');

define('LNG_Help_FormsManage', 'Website forms can be placed on your website to collect new contacts, let existing contacts modify their details, or even unsubscribe from your list. <a href="javascript:LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'809\');">Learn more here.</a>');

define('LNG_CreateFormIntro', 'Fill out the form below to create a subscribe, unsubscribe, modify details or send to friend form which you can place on your website.');

define('LNG_ConfirmPageIntro', 'The form below shows the options you\'ve selected for this form. Complete the form and click <em>Next&gt;&gt;</em> to create the form.');
define('LNG_ThanksPageIntro', 'This step allows you to create the thank you emails and HTML page to let your contact know that you are aware of their actions.');

define('LNG_FinalPageIntro', 'If something goes wrong during signup the contact will be shown the options you choose in the form below.');

define('LNG_ThanksPageIntro_NoEmail', 'This step allows you to create the thank you HTML page to let your contact know that you are aware of their actions.');

define('LNG_ThanksPageIntro_Edit', 'Setup the thank you page and optionally the thank you email options below. These will be shown to contacts after they join your list.');

define('LNG_ThanksPageIntro_Edit_NoEmail', 'This step allows you to edit the thank you HTML page to let your contact know that you are aware of their actions.');

define('LNG_HLP_ErrorPageHTML_Unsubscribe', 'This is the HTML page that is shown when a contact encountered any errors during the subscription process. You can modify this using HTML or leave as is for the default \\\'Error\\\' page.');

define('LNG_ChooseFormLists', 'Please choose some contact lists to include on this form.');

define('LNG_IncludeLists', 'Contact Lists/Custom Fields:');

define('LNG_ChooseCustomFields', 'Custom Fields For \'%s\' Contact List');

define('LNG_FormIncludeListsIsNotValid', 'Please choose some contact lists for this form to use.');

define('LNG_FormConfirmPage_Unsubscribe_Email_Text', "Please confirm you want to unsubscribe by clicking on the link below:\n\n%BASIC:CONFIRMUNSUBLINK%\n\nWe need to do this before removing you from the contact list.");

define('LNG_FormDisplaySendFriendOptions', 'Forwarded email headers');
/**
* Thanks email options.
*/
define('LNG_FormThanksPage_Subscribe_HTML', '
<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>Your subscription is now complete.</b><br><br>
		Thank you for subscribing to our contact list. Your subscription is now complete.<br><br>
	</div>
</div>
</body>
</html>');

define('LNG_FormThanksPage_Subscribe_Email_Text', "Thank you for subscribing to our contact list.\n\nYour subscription is now complete. If you have any questions you can contact us by replying to this email.");

define('LNG_FormThanksPage_Subscribe_Email_HTML', "
<html>
<body style='font: 12px tahoma'>
<b>Your subscription is complete</b><br><br>
Thank you for subscribing to our contact list. Your subscription is now complete. If you have any questions you can contact us by replying to this email.
</body>
</html>
");

define('LNG_FormThanksPage_Subscribe_Subject_Contact', 'Thank you for signing up to our contact list');

define('LNG_FormThanksPage_Unsubscribe_Email_Text', "Hi,\nYou have been unsubscribed from our contact list.\nSorry to see you go!");

/**
* Thanks page options.
*/
define('LNG_FormThanksPageHTML_Subscribe', '
<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>Your subscription is now complete.</b><br><br>
		Thank you for subscribing to our contact list. Your subscription is now complete.<br><br>
	</div>
</div>
</body>
</html>
');


define('LNG_FormErrorPageHTML_Subscribe',
'<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>An error has occurred.</b><br><br>
		An error(s) has occurred while trying to subscribe you to our contact list:<br>
		%%GLOBAL_Errors%%
		<br><br>
		<a href="javascript:history.back()">&laquo; Go Back</a>
	</div>
</div>
</body>
</html>');


define('LNG_FormErrorPageHTML_Unsubscribe',
'<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>An error has occurred.</b><br><br>
		An error(s) has occurred trying to unsubscribe you from our contact list:
		%%GLOBAL_Errors%%
		<br><br>
		<a href="javascript:history.back()">&laquo; Go Back</a>
	</div>
</div>
</body>
</html>
');

define('LNG_Form_Javascript_ChooseLists', 'Please choose some contact lists to subscribe to');

define('LNG_FormConfirmPage_Unsubscribe_HTML',
'
<html>
<head>
<style>
body {
	margin: 0px;
}

#content {
	border: 1px solid #EFECBA;
	width: 300px;
	height: 150px;
	background-color: #FBFAE7;
	padding:20px;
	top: 50%;
	left: 50%;
	position: absolute;
}

#container  {
	width: 100%;
	height: 100%;
	font: 11px tahoma;
	position: absolute;
	top: -75px;
	left: -150px;
}

</style>
</head>
<body>
<div id="container">
	<div id="content">
		<b>Your request to be removed from our contact list is almost complete...</b><br><br>
		Please confirm your request to unsubscribe from this contact list.<br><br>You will receive an email shortly with a link to confirm your request. Please click this link and you will be removed from our contact list.
	</div>
</div>
</body>
</html>');

define('LNG_FormConfirmPage_Unsubscribe_Email_HTML',
"
<html>
<body style='font:12px tahoma'>
Please confirm you want to unsubscribe by clicking on the link below.<br><br>
<a href='%%CONFIRMLINK%%' target='_blank'>Please click here to confirm you want to leave this contact list</a><br><br>or copy and paste the following URL into your browser:<br>
%%CONFIRMLINK%%<br><br>
We need to do this before removing you from the contact list.
</body></html>"
);

define('LNG_FormThanksPage_Unsubscribe_Email_HTML', "
<html>
<body style='font:12px tahoma'>
Hi,<br/>You have been unsubscribed from our contact list.<br/>Sorry to see you go!</body></html>");

define('LNG_FormThanksPage_Subscribe_Email_Text_Contact', "Thank you for signing up to our contact list and/or contacting us.\n\nIf you have any problems you can contact us by replying to this email.");

define('LNG_FormThanksPage_Subscribe_Email_HTML_Contact',
"
<html>
<body style='font:12px tahoma'>
Thank you for signing up to our contact list and/or contacting us.<br><br>If you have any problems you can contact us by replying to this email.
</body>
</html>
");


define('LNG_HLP_FormType', 'Choose the type of form you will be creating. <br><br>A <i>subscription</i> form lets visitors subscribe to your contact list.<br><br>An <i>unsubscribe</i> form allow visitors to unsubscribe from your contact list. This is optional, and an unsubscribe link can be added to your email campaigns automatically instead.<br><br>A <i>modify details</i> form allows contacts to modify their subscription information.<br><br>Finally, a <i>send to a friend</i> form lets users share your email campaign with their friends.');

define('LNG_HLP_ConfirmPageHTML_Unsubscribe', 'This is the page your contacts will see once they fill in the website form and ask to be removed from your contact list(s).');

define('LNG_WhatAreTheForms', '<a href="javascript:LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'809\');">What are the different types of Website Forms and how do I use them.</a>');

// these few are needed for the 'heading' of the helptip(s).
// they need to be UNDER the 'LNG_ConfirmPageHTML' and so on language variables.
define('LNG_ConfirmPageHTML_Subscribe', LNG_ConfirmPageHTML);
define('LNG_ConfirmTextVersion_Subscribe', LNG_ConfirmTextVersion);
define('LNG_ConfirmHTMLVersion_Subscribe', LNG_ConfirmHTMLVersion);
define('LNG_ThanksTextVersion_Subscribe', LNG_ThanksTextVersion);
define('LNG_ThanksHTMLVersion_Subscribe', LNG_ThanksHTMLVersion);

// these few are needed for the 'heading' of the helptip(s).
// they need to be UNDER the 'LNG_ConfirmPageHTML' and so on language variables.
define('LNG_ConfirmPageHTML_Unsubscribe', LNG_ConfirmPageHTML);
define('LNG_ConfirmTextVersion_Unsubscribe', LNG_ConfirmTextVersion);
define('LNG_ConfirmHTMLVersion_Unsubscribe', LNG_ConfirmHTMLVersion);
define('LNG_ThanksTextVersion_Unsubscribe', LNG_ThanksTextVersion);
define('LNG_ThanksHTMLVersion_Unsubscribe', LNG_ThanksHTMLVersion);

// make the helptips the same as the subscribe ones for now.
// they need to be UNDER the 'LNG_ConfirmPageHTML' and so on language variables.
define('LNG_HLP_ConfirmTextVersion_Unsubscribe', LNG_HLP_ConfirmTextVersion_Subscribe);
define('LNG_HLP_ConfirmHTMLVersion_Unsubscribe', LNG_HLP_ConfirmHTMLVersion_Subscribe);
define('LNG_HLP_ThanksTextVersion_Unsubscribe', LNG_HLP_ThanksTextVersion_Subscribe);
Define('LNG_HLP_ThanksHTMLVersion_Unsubscribe', LNG_HLP_ThanksHTMLVersion_Subscribe);

define('LNG_HLP_UseCaptcha', 'Captcha (an acronym for \\\'Completely Automated Public Turing Test to Tell Computers and Humans Apart\\\') is a type of challenge-response test used in computing to determine whether or not the user is human. This helps prevent automated submission of your forms. If this is on, the form will ask for a \\\'security code\\\' to be entered in before the user can complete the website form.<br><br>If you are placing your form on a different domain to the one used for the application your contacts will have issues using captcha on browsers such as Safari as they do not allow third party cookies to be set by default.');

define('LNG_FormDoesntExist', 'The website form you are trying to edit does not exist. Please try again.');

define('LNG_FormCustomFieldSelection', 'Choose the list(s) you want to add/remove contact from and which custom fields to include on your form. <a href="#" onClick="LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'840\'); return false;">Learn more here</a>.');

define('LNG_FormCustomFieldSortExplain', 'Drag and drop to rearrange the order in which the fields will appear on the form');

define('LNG_ContactFormExplain', 'Yes, send me an email with a copy of the contact\'s details');
define('LNG_UseCaptchaExplain', 'Yes, use CAPTCHA form security (recommended)');
define('LNG_SendThanks', 'Send a &quot;Thank You&quot; Email?');
define('LNG_OrderCustomFields', 'Change Field Order (Drag &amp; Drop):');
define('LNG_PreviewThisDesign', 'Preview this Design');
define('LNG_FormName_Hint', 'The form name is shown in the control panel only');
define('LNG_NameThisForm', 'Name this Form');
define('LNG_ChooseAFormType', 'Choose a Form Type');
define('LNG_FormAdvancedOptions', 'Advanced Options');
define('LNG_ShowContentBelow', 'Let me customize what the page looks like');
define('LNG_TakeSubscriberToAURL', 'Take the subscriber to an existing web site address');
define('LNG_InsertACustomField', 'Insert a Custom Field...');


/**
****************************
* Changed/added in IEM 5.0.2
****************************
*/
define('LNG_FormsNoLists', 'No Contact Lists have been created.%s');
define('LNG_FormsNoLists_HasAccess', ' Click the <em>' . LNG_CreateListButton . '</em> button below to create one.');
define('LNG_FormsNoLists_NoAccess', ' Please ask your administrator to create one for you.');

/**
 * Added in 6.0.0
 */
define('LNG_UseCaptchaNoGd', 'The GD Library was not detected. In order to use CAPTCHA\'s in your forms, this must be installed.');
