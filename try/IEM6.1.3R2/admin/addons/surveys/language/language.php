<?php

define('LNG_Addon_surveys_loaded',true);

define('LNG_Addon_surveys_ViewSurveys','View Surveys');
define('LNG_Addon_surveys_ViewSurveysDescription','Create and edit surveys');
define('LNG_Addon_surveys_Manage','View Surveys');
define('LNG_Addon_surveys_Create','Create a Survey...');
define('LNG_Addon_surveys_Delete','Delete Selected...');
define('LNG_Addon_surveys_AccessError','Permission denied. You do not have access to this area or to perform the action requested. Please contact your administrator.');



define('LNG_Addon_surveys_ManageIntro','The surveys you\'ve created are shown below. You can link to a survey when creating an email campaign or autoreponder which will allow contacts to fill in the survey with their responses.');
define('LNG_Addon_surveys_Heading_Edit','Edit a Survey');
define('LNG_Addon_surveys_Edit_Intro','Drag fields from the left to right below to build your survey. You can add a link to your survey when creating an email campaign or autoresponder.');

define('LNG_Addon_surveys_Question_Title','Question #%d Title');
define('LNG_Addon_surveys_One_Line_Answer','One Line Answer');
define('LNG_Addon_surveys_Multi_Line_Answer','Multiple Line Answer');
define('LNG_Addon_surveys_Multiple_Choice_Answer','Multiple Choice');
define('LNG_Addon_surveys_Website_Answer','Website Address');
define('LNG_Addon_surveys_Rating_Answer','Rating Scale');
define('LNG_Addon_surveys_Date','Date');
define('LNG_Addon_surveys_Question_Type','Answer Type');
define('LNG_Addon_surveys_Required','Required');
define('LNG_Addon_surveys_Private','Private');

define('LNG_Addon_surveys_Choices','Choices');

//define('LNG_HLP_Addon_surveys_Choices','Type the possible choices into this text box, one per line. Each choice will be shown along with the ratings you enter below, such as: ' . str_replace(array("'","\n"),array("'",""),$choicestip));

define('LNG_Addon_surveys_Can_Pick_Multiple','Can pick more than one');
define('LNG_Addon_surveys_Remove','Remove');
define('LNG_Addon_surveys_AddAnotherQuestion','Add Another Question');

define('LNG_Addon_surveys_Survey_Name','Survey Name');
define('LNG_HLP_Addon_surveys_Survey_Name','Enter a name for this survey. The name will only be visible to you and will not be shown to your contacts.');

define('LNG_Addon_surveys_Survey_Heading_Create','Create a Survey');
define('LNG_Addon_surveys_Survey_Intro_Create','Drag fields from the left to right below to build your survey. You can add a link to your survey when creating an email campaign or autoresponder.');
define('LNG_SaveAndEdit','Save & Continue');
define('LNG_Addon_surveys_Survey_Details','Survey Details');
define('LNG_Addon_surveys_EnterTitle','Please enter a title for this question.');
define('LNG_Addon_surveys_EnterChoices','Please enter a list of choices for this question.');
define('LNG_Addon_surveys_EnterSurveyName','Please enter a name for your survey.');
define('LNG_Addon_surveys_Number_Answer','Number');


define('LNG_Addon_surveys_Settings_Header','Untitled Survey');
define('LNG_Addon_surveys_Settings_ShowMessage','Thanks for filling in our survey. Your opinion is important to us.');
define('LNG_Addon_surveys_Settings_ShowUri','http://');
define('LNG_Addon_surveys_Settings_Email','admin@yourdomain.com');
define('LNG_Addon_surveys_Settings_ErrorMessage','Oops... something went wrong when trying to save your survey responses. Take a look at the errors below and try again.');
define('LNG_Addon_surveys_Settings_Submit','Submit Form');


define('LNG_Addon_surveys_NoSurveys','No surveys have been created. Please click the "Create a Survey..." button to create one.');
define('LNG_Addon_Survey_Sort_name','Name');
define('LNG_Addon_Survey_Sort_created','Created');

define('LNG_Addon_surveys_SurveySaved','Survey was saved successfully.');

define('LNG_Addon_surveys_SurveyDeleted','Selected surveys were deleted successfully.');
define('LNG_Addon_surveys_SurveyDeleted_Multi','1 survey(s) has been deleted successfully.');
define('LNG_Addon_surveys_SurveyDeleted_Multi_Real','%d survey(s) have been deleted successfully.');

define('LNG_Addon_surveys_SurveyDeleted_PleaseSelect', 'Please choose at least one survey to delete.');

define('LNG_Addon_surveys_ConfirmDelete','Are you sure you want to delete this survey?');
define('LNG_Addon_surveys_ConfirmDelete_Multi','Are you sure you want to delete the selected surveys?');
define('LNG_Addon_survey_ConfirmCancel','Are you sure you want to cancel editing this survey?');
// define('LNG_DeleteSelected','Delete Selected');
define('LNG_Addon_surveys_RestrictRange','Limit answers to this range');
define('LNG_Range','Range');

define('LNG_Addon_surveys_FileTypes','File Types');
define('LNG_HLP_Addon_surveys_FileTypes','Type the allowable file types into this text box. Separate each file type with a comma, such as: pdf, xls, doc, jpg');
define('LNG_Addon_surveys_RestrictFileTypes','Allow only limited file types to be uploaded');
define('LNG_Addon_surveys_RestrictFileTypes_Instruction','pdf,doc,xls');

define('LNG_Addon_surveys_File_Answer','File Upload Box');
define('LNG_Addon_survey_SelectCountry','Select a country');
define('LNG_Addon_surveys_DefaultCountry','Default Selection');
define('LNG_Addon_surveys_Country_Answer','Country');

/**
* Permissions
*/
define('LNG_Addon_Settings_Survey_Header', 'Surveys');
define('LNG_Addon_surveys_Permission_Create','Create a survey');
define('LNG_Addon_surveys_Permission_Edit','Edit a survey');
define('LNG_Addon_surveys_Permission_Delete','Delete a survey');
define('LNG_Addon_surveys_results_Permission_View','View a survey results');
define('LNG_Addon_surveys_responses_Permission_View','View a survey response');
define('LNG_Addon_surveys_responses_Permission_Edit','Edit a survey response');
define('LNG_Addon_surveys_responses_Permission_Delete','Delete a survey response');
define('LNG_Addon_surveys_export_Permission', 'Export a survey result');

/**
* Question form
*/
define('LNG_Addon_surveys_Add_PageBreak','Add a Page Break');
define('LNG_Addon_surveys_Page_Break','Page Break');
define('LNG_Addon_surveys_Label','Label #%d');
define('LNG_Addon_surveys_Score','Score');
define('LNG_Addon_surveys_Scale','Score');
define('LNG_Addon_surveys_Ratings','%d ratings');

/**
* Error messages
*/
define('LNG_Addon_surveys_Enter_Label','Please enter a label for this rating.');
define('LNG_Addon_surveys_Enter_Score','Please enter a score for this rating.');
define('LNG_Addon_surveys_Enter_Rating_Choices','Please enter a list of choices for this rating.');

/**
* Survey Form
*/
define('LNG_Addon_surveys_Welcome_Text','Welcome Text');
define('LNG_HLP_Addon_surveys_Welcome_Text','Optionally enter some welcome text for this survey which will be shown to survey participants before they take your survey.');

define('LNG_Addon_surveys_CreateSurveys','Create a Survey');
define('LNG_Addon_surveys_CreateSurveysDescription','Create a new survey');
define('LNG_Addon_surveys_SurveyTemplates','Survey Templates');
define('LNG_Addon_surveys_SurveyTemplatesDescription','');
define('LNG_Addon_surveys_TemplatesManage','Manage Survey Templates');
define('LNG_Addon_surveys_TemplatesManageIntro','Create templates for surveys');

define('LNG_Addon_surveys_ButtonSaveAndContinue',  "Save &amp; Continue");
define('LNG_Addon_surveys_ButtonSaveAndExit',  "Save &amp; Exit");
define('LNG_Addon_surveys_ButtonCancel',  "Cancel");
define('LNG_Addon_surveys_ButtonDelete',  "Delete");

define('LNG_Addon_surveys_ConfirmCancel', "Are you sure you want to cancel editing this survey?\\r\\n\\r\\nClick OK to confirm. Any unsaved changes you have made will be lost.");

//define('LNG_Addon_surveys_Required','This question must be answered');

/**
* Survey Templates
*/
define('LNG_Addon_surveys_NoTemplates','No survey templates have been created. Please click the "Create a Template" button to create one.');
define('LNG_Addon_surveys_TemplateCreate','Create a Template...');

// Form
define('LNG_Survey_Template_Heading_Create','Create a Survey Template');
define('LNG_Survey_Template_Intro_Create','Create survey template');
define('LNG_Addon_survey_Template_Details','Template Details');
define('LNG_Addon_survey_Template_Name','Template Name');
define('LNG_Addon_survey_Template_Properties','Template Properties');
define('LNG_Addon_survey_Confirm_Delete','Are you sure you want to remove the selected question?');
define('LNG_Addon_survey_Confirm_Delete_Multi', 'Are you sure you want to delete the selected survey(s)?\n\nThis action cannot be undone.');

// define('LNG_Addon_surveys_Question_Type_Help',str_replace(array("'","\n"),array("\\'",''),$helptip));


// New Survey Form


define('LNG_Addon_Surveys_Default_TableHead_Name', 'Survey Name');
define('LNG_Addon_Surveys_Default_TableHead_Created', 'Date Created');
define('LNG_Addon_Surveys_Default_TableHead_Updated', 'Last Edited');
define('LNG_Addon_Surveys_Default_TableHead_Responses', 'Responses');

define('LNG_Addon_Surveys_Default_Table_ViewResponses', 'View Responses');
define('LNG_Addon_Surveys_Default_Table_ViewResults', 'View Results');
define('LNG_Addon_Surveys_Default_Table_BrowseResponses', 'Browse Responses');
define('LNG_Addon_Surveys_Default_Table_ExportResponses', 'Export Responses');

define('LNG_Addon_Surveys_Default_NeverUpdated', 'Never');


define('LNG_Addon_Surveys_Menu_Text', 'Single Line of Text');
define('LNG_Addon_Surveys_Menu_TextArea', 'Paragraph of Text');
define('LNG_Addon_Surveys_Menu_Radio', 'List of Radio Buttons');

define('LNG_Addon_Surveys_Menu_Checkbox', 'List of Checkboxes');
define('LNG_Addon_Surveys_Menu_File', 'File Upload Box');
define('LNG_Addon_Surveys_Menu_SectionBreak', 'Section Break');
define('LNG_Addon_Surveys_Menu_SelectCountries', 'Drop Down List of Countries');
define('LNG_Addon_Surveys_Menu_Select', 'Drop Down List');

define('LNG_Addon_Surveys_tabSurveysDesigner', 'Survey Designer');
define('LNG_Addon_Surveys_tabSurveysSettings', 'Survey Settings');

define('LNG_Addon_Surveys_canvasEmptyText', "(Drag a field from the left and drop it here)");
define('LNG_Addon_Surveys_DefaultName', "Untitled Survey");
define('LNG_Addon_Surveys_DefaultDescription', "Type some introductory or instructional text here");

define('LNG_Addon_Surveys_TooltipTitleEmailFeedback',  "Email Survey Responses?");
define('LNG_Addon_Surveys_TooltipDescriptionEmailFeedback',  "Would you like to receive an email with the completed survey responses? If so tick this option and enter your email address in the text box below.");

define('LNG_Addon_Surveys_TooltipTitleShowMessage',  "Display Thank You Page");
define('LNG_Addon_Surveys_TooltipDescriptionShowMessage', "When a contact completed the survey they will be shown the text you type here. Alternatively you can take them to a page on your website by selecting the option below instead.");

define('LNG_Addon_Surveys_TooltipTitleShowUri', "Take Them to a Web Page");
define('LNG_Addon_Surveys_TooltipDescriptionShowUri', "When a contact completes the survey they will be taken to the web page you type here. Alternatively you can show them a thank you message by selecting the option above instead.");

define('LNG_Addon_Surveys_TooltipTitleHeaderLogo', "Display Your Logo");
define('LNG_Addon_Surveys_TooltipHeaderLogoDescription', "You can upload your logo image to be displayed at the top of the survey. Uploaded images will not be resized and both GIF and JPG images are supported.");

define('LNG_Addon_Surveys_TooltipTitleHeaderText', "Display Text");
define('LNG_Addon_Surveys_TooltipHeaderDescription', "The text you type here will be shown at the top of the survey.");

define('LNG_Addon_Surveys_WidgetCheckboxDefaultName', "Untitled List of Checkboxes");
define('LNG_Addon_Surveys_WidgetCheckboxDefaultDescription', "Click here to enter some optional help text");
define('LNG_Addon_Surveys_WidgetCheckboxOptionRequiresAnAnswer', "Requires an answer?");
define('LNG_Addon_Surveys_WidgetCheckboxOptionRandomize', "Randomize?");
define('LNG_Addon_Surveys_WidgetCheckboxOptionVisibleToEveryone', "Visible to everyone");
define('LNG_Addon_Surveys_WidgetCheckboxOptionVisibleToAdministrators', "Visible only to administrators");


define('LNG_Addon_Surveys_WidgetCheckboxTooltipTitleRandom', "Randomize");
define('LNG_Addon_Surveys_WidgetCheckboxTooltipDescriptionRandom', "<p>Choosing this option will randomize the order of the checkboxes when displayed to the user.</p>");
define('LNG_Addon_Surveys_WidgetCheckboxTooltipTitleVisibility', "Visibility");
define('LNG_Addon_Surveys_WidgetCheckboxTooltipDescriptionVisibility', "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>");

define('LNG_Addon_Surveys_WidgetFileDefaultName', "Untitled File Upload Field");
define('LNG_Addon_Surveys_WidgetFileDefaultDescription', "Click here to enter some optional help text");
define('LNG_Addon_Surveys_WidgetFileOptionRequiresAnAnswer', "Requires an answer?");
define('LNG_Addon_Surveys_WidgetFileOptionVisibleToEveryone', "Visible to everyone");
define('LNG_Addon_Surveys_WidgetFileOptionVisibleToAdministrators', "Visible only to administrators");
define('LNG_Addon_Surveys_WidgetFileOptionAllowAllFileTypes', "Allow all file types?");
define('LNG_Addon_Surveys_WidgetFileValueFile', "File will be uploaded here");
define('LNG_Addon_Surveys_WidgetFileValueAllowedFileTypes', "doc, xls, pdf, gif, jpg");
define('LNG_Addon_Surveys_WidgetFileTextBrowse', "Browse...");

define('LNG_Addon_Surveys_WidgetFileTooltipTitleVisibility', "Visibility");
define('LNG_Addon_Surveys_WidgetFileTooltipDescriptionVisibility',"<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>");
define('LNG_Addon_Surveys_WidgetFileTooltipTitleAllowedFileTypes', "Allowed File Types");
define('LNG_Addon_Surveys_WidgetFileTooltipDescriptionAllowedFileTypes', "<p>Only the file types entered here are allowed to be uploaded/sent.</p><p>File types are file extensions separated by a comma (i.e. pdf, txt, doc, docx).</p>");

define('LNG_Addon_Surveys_WidgetRadioDefaultName', "Untitled List of Radio Buttons");
define('LNG_Addon_Surveys_WidgetRadioDefaultDescription', "Click here to enter some optional help text");
define('LNG_Addon_Surveys_WidgetRadioOptionRequiresAnAnswer', "Requires an answer?");
define('LNG_Addon_Surveys_WidgetRadioOptionRandomize', "Randomize?");
define('LNG_Addon_Surveys_WidgetRadioOptionVisibleToEveryone', "Visible to everyone");
define('LNG_Addon_Surveys_WidgetRadioOptionVisibleToAdministrators', "Visible only to administrators");

define('LNG_Addon_Surveys_WidgetRadioTooltipTitleRandom', "Random Radio Button Order");
define('LNG_Addon_Surveys_WidgetRadioTooltipDescriptionRandom', "<p>Choosing this option will randomize the order of the radio buttons when displayed to the user.</p>");
define('LNG_Addon_Surveys_WidgetRadioTooltipTitleVisibility', "Visibility");
define('LNG_Addon_Surveys_WidgetRadioTooltipDescriptionVisibility', "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>");

define('LNG_Addon_Surveys_WidgetSectionBreakDefaultName', "Untitled Section Break");
define('LNG_Addon_Surveys_WidgetSectionBreakDefaultDescription', "Click here to enter some optional help text");

define('LNG_Addon_Surveys_WidgetSelectDefaultName', "Untitled Dropdown");
define('LNG_Addon_Surveys_WidgetSelectDefaultDescription', "Click here to enter some optional help text");
define('LNG_Addon_Surveys_WidgetSelectOptionRequiresAnAnswer', "Requires an answer?");
define('LNG_Addon_Surveys_WidgetSelectOptionRandomize', "Randomize?");
define('LNG_Addon_Surveys_WidgetSelectOptionVisibleToEveryone', "Visible to everyone");
define('LNG_Addon_Surveys_WidgetSelectOptionVisibleToAdministrators', "Visible only to administrators");

define('LNG_Addon_Surveys_WidgetSelectTooltipTitleRandom', "Randomize");
define('LNG_Addon_Surveys_WidgetSelectTooltipDescriptionRandom', "<p>Choosing this option will randomize the order of the options in the dropdown list when displayed to the user.</p>");
define('LNG_Addon_Surveys_WidgetSelectTooltipTitleVisibility', "Visibility");
define('LNG_Addon_Surveys_WidgetSelectTooltipDescriptionVisibility', "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>");

define('LNG_Addon_Surveys_WidgetTextDefaultName', "Untitled Line of Text");
define('LNG_Addon_Surveys_WidgetTextDefaultDescription', "Click here to enter some optional help text");
define('LNG_Addon_Surveys_WidgetTextValueText', "Response will be typed here");
define('LNG_Addon_Surveys_WidgetTextOptionRequiresAnAnswer', "Requires an answer?");
define('LNG_Addon_Surveys_WidgetTextOptionVisibleToEveryone', "Visible to everyone");
define('LNG_Addon_Surveys_WidgetTextOptionVisibleToAdministrators', "Visible only to administrators");

define('LNG_Addon_Surveys_WidgetTextTooltipTitleVisibility', "Visibility");
define('LNG_Addon_Surveys_WidgetTextTooltipDescriptionVisibility', "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>");

define('LNG_Addon_Surveys_WidgetTextareaDefaultName', "Untitled Paragraph of Text");
define('LNG_Addon_Surveys_WidgetTextareaDefaultDescription', "Click here to enter some optional help text");
define('LNG_Addon_Surveys_WidgetTextareaValueText', "Response will be typed here");
define('LNG_Addon_Surveys_WidgetTextareaOptionRequiresAnAnswer', "Requires an answer?");
define('LNG_Addon_Surveys_WidgetTextareaOptionVisibleToEveryone', "Visible to everyone");
define('LNG_Addon_Surveys_WidgetTextareaOptionVisibleToAdministrators', "Visible only to administrators");

define('LNG_Addon_Surveys_WidgetTextareaDuplicate', "Duplicate");
define('LNG_Addon_Surveys_WidgetTextareaToggle', "Toggle");

define('LNG_Addon_Surveys_WidgetTextareaTooltipTitleVisibility', "Visibility");
define('LNG_Addon_Surveys_WidgetTextareaTooltipDescriptionVisibility', "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>");

define('LNG_Addon_Surveys_editResponseTitle', "Editing Response %s of %s for &quot;%s&quot;");
define('LNG_Addon_Surveys_editResponseDescription', "Use the buttons below to navigate between responses for the selected survey form. To switch back to viewing the response, click &quot;Cancel&quot;.");
define('LNG_Addon_Surveys_editResponseNoResponses', "The form '%s' has no responses yet. <a href', 'index.php?section', module&action', custom&module', form&moduleAction', view.responses'>Go Back</a>.");
define('LNG_Addon_Surveys_editResponseConfirmCancel', "Are you sure you want to exit? Click OK to confirm.");
define('LNG_Addon_Surveys_editResponseConfirmDelete', "Are you sure you want to delete this response? Click OK to confirm.");
define('LNG_Addon_Surveys_editResponseButtonSaveAndView', "Save &amp; View");
define('LNG_Addon_Surveys_editResponseButtonSaveAndViewNext', "Save &amp; View Next &raquo;");
define('LNG_Addon_Surveys_editResponseButtonSaveAndKeepEditing', "Save &amp; Keep Editing");
define('LNG_Addon_Surveys_editResponseButtonCancel', "Exit");
define('LNG_Addon_Surveys_editResponseButtonDelete', "Delete");
define('LNG_Addon_Surveys_editResponseButtonPrevious', "&laquo; Previous");
define('LNG_Addon_Surveys_editResponseButtonNext', "Next &raquo;");
define('LNG_Addon_Surveys_editResponseOtherText', "Other:");
define('LNG_Addon_Surveys_editResponseResponseNumber', "Response #%d");

define('LNG_Addon_Surveys_EmailFeedbackTitle', "Please enter a valid email address");
define('LNG_Addon_Surveys_EmailFeedbackLabel', "Email Survey Responses");
define('LNG_Addon_Surveys_EmailFeedbackConfirm', "Yes, send responses to this email address:");

define('LNG_Addon_Surveys_Heading_DisplayFeedbackOption', "Display & Feedback Options");
define('LNG_Addon_Surveys_Heading_AdvancedOption', "Advanced Options");


define('LNG_Addon_Surveys_DisplayHeaderTextLogo', 'Header Text or Logo');
define('LNG_Addon_Surveys_DisplayHeaderText', 'Display text at the top of the survey');
define('LNG_Addon_Surveys_DisplayHeaderLogo', 'Display my logo at the top of the survey');


define('LNG_Addon_Surveys_WhenSurveyCompleted', "When the Survey is Completed:");
define('LNG_Addon_Surveys_ShowMessageLabel', "Display this text as the thank you page:");
define('LNG_Addon_Surveys_ShowMessageDefaultMessage', "Thank you for completing our survey form. If required, we will be in touch shortly.");
define('LNG_Addon_Surveys_ShowPageLabel', "Take them to a particular web page:");
define('LNG_Addon_Surveys_ShowPageAlt', "Redirect to a URI");

define('LNG_Addon_Surveys_ErrorMessageLabel', "Error Message");
define('LNG_Addon_Surveys_ErrorMessageDefaultMessage', "We're sorry, but the data you entered contains errors. They are shown below.");
define('LNG_Addon_Surveys_TooltipTitleErrorMessage', "Error Message");
define('LNG_Addon_Surveys_TooltipDescriptionErrorMessage', "If something goes wrong when trying to save survey responses your contacts will be shown this error message.");

define('LNG_Addon_Surveys_SubmitButtonTextDefaultValue', "Submit Form");
define('LNG_Addon_Surveys_SubmitButtonTextLabel', "Submit Button Text");
define('LNG_Addon_Surveys_TooltipTitleSubmitButtonText', "Submit Button Text");
define('LNG_Addon_Surveys_TooltipDescriptionSubmitButtonText', "This text will be shown on the button at the bottom of the page. Subscribers can click the button when they're done filling in your survey.");

define('LNG_Addon_Surveys_ConfirmDeleteMultiple', "Are you sure you want to delete these forms?");
define('LNG_Addon_Surveys_WidgetRemove', "Remove");
define('LNG_Addon_Surveys_SelectFormsToDelete', "Please choose at least one survey form to delete first.");

define('LNG_Addon_Surveys_WidgetToggleTitle', "Click here to toggle the visibility of this field");
define('LNG_Addon_Surveys_WidgetDuplicateTitle', "Click here to copy this field after itself");
define('LNG_Addon_Surveys_WidgetRemoveTitle', "Click here to remove this field");
define('LNG_Addon_Surveys_WidgetTextOr', "or");
define('LNG_Addon_Surveys_WidgetTextOther',  "Other:");
define('LNG_Addon_Surveys_WidgetTextAddOther',  "add &quot;Other&quot;");
define('LNG_Addon_Surveys_WidgetTextToggle',  "Minimize/Maximize");
define('LNG_Addon_Surveys_WidgetTextDuplicate',  "Duplicate");
define('LNG_Addon_Surveys_WidgetTextRemove',  "Remove");
define('LNG_Addon_Surveys_WidgetValueOther',  "Response will be typed here");
define('LNG_Addon_Surveys_WidgetValueField',  "Option #");

/**
 * TinyMce
 */

define('LNG_Addon_Surveys_tinymceIModalTitle',  "Link to a Survey");
define('LNG_Addon_Surveys_tinymceIModalCancel',  "Cancel");
define('LNG_Addon_Surveys_tinymceIModalInsert',  "Insert Survey Link");
define('LNG_Addon_Surveys_tinymceCreateNewForm',  "There are no Surveys available. <a href  href='index.php?Page=Addons&Addon=surveys&Action=create'>Create a Survey</a>.");
define('LNG_Addon_Surveys_tinymceTextContainer', "Click here to take our survey");
define('LNG_Addon_Surveys_tinymceNoSurveysAvailable', "No Surveys have been created yet. When you create a survey you will be able to link to it from this window.");

/**
* Error Messages
*/

define('LNG_Addon_Surveys_ErrorMessage_formEmail_required',  "Please enter an email address.");
define('LNG_Addon_Surveys_ErrorMessage_formEmail_email',  "Please enter a valid email address where you would like survey responses sent");
define('LNG_Addon_Surveys_ErrorMessage_formEmail_headertext',  "Please enter a header text.");
define('LNG_Addon_Surveys_ErrorMessage_formEmail_headerlogosize',  "Your file size is too large please upload an image under 2MB");
define('LNG_Addon_Surveys_ErrorMessage_formEmail_headerlogotype',  "Please upload a valid image file");
define('LNG_Addon_Surveys_ErrorMessage_formEmail_headerlogorequired',  "Please select the file for your header logo");



define('LNG_Addon_Surveys_ErrorMessage_formShowMessage_required',  "Please enter a message to show when the survey is completed");
define('LNG_Addon_Surveys_ErrorMessage_formShowUri_required',  "If you would like the user redirected to a URL, you must provide a url.");
define('LNG_Addon_Surveys_ErrorMessage_formShowUri_uri',  "Please enter a valid web page to shown when the survey is completed.");
define('LNG_Addon_Surveys_ErrorMessage_formErrorMessage_required',  "Please enter an error message to display if something goes wrong");
define('LNG_Addon_Surveys_ErrorMessage_formSubmitButtonText_required',  "Please enter text to show on the submit button at the bottom of the survey");
define('LNG_Addon_Surveys_ErrorMessage_mustHaveWidgets_numberRange',  "Please add at least one field to this form first.");

define('LNG_Addon_Surveys_ErrorForm',  "An error has occured.");
define('LNG_Addon_Surveys_ErrorRequired',  "This field is required.");
define('LNG_Addon_Surveys_ErrorRequiredOther',  "Please type an answer in the text box next to the checked field.");
define('LNG_Addon_Surveys_ErrorInvalidFileType',  "The file you uploaded needs to end in %s or %s.");

define('LNG_Addon_Surveys_saveSurveysMessageSuccess',  "The survey you created has been saved successfully. You can now link to it when creating an email campaign or autoresponder.");
define('LNG_Addon_Surveys_saveFormMessageError',  "Your survey couldn't be saved because of the following problems:");



/**
 * Exporting Response
 */


define('LNG_Addon_Surveys_exportResponsesTitle',  "Export Responses");
define('LNG_Addon_Surveys_exportResponsesDescription',  "To download responses to a CSV file for futher analysis, start by choosing a survey form below.");
define('LNG_Addon_Surveys_exportResponsesSelectForm',  "Select a Survey");
define('LNG_Addon_Surveys_exportResponsesFeedbackForm',  "Survey Form:");
define('LNG_Addon_Surveys_exportResponsesNoForms',  "There are no surveys to export the responses for.");
define('LNG_Addon_Surveys_exportResponsesDownload',  "Download Responses");
define('LNG_Addon_Surveys_exportResponsesCancel',  "Cancel");
define('LNG_Addon_Surveys_exportResponsesConfirmCancel',  "Are you sure you wish to cancel? Click OK to confirm.");

/**
* viewing responses
*/

define('LNG_Addon_Surveys_responseSingular',  "response");
define('LNG_Addon_Surveys_responsePlural',  "responses");

define('LNG_Addon_Surveys_viewResponsesTitle',  "Browse Survey Responses");
define('LNG_Addon_Surveys_viewResponsesDescription',  "To browse results for a particular survey start by choosing it from the list below.");
define('LNG_Addon_Surveys_viewResponsesNoResponsesMessage',  "There aren't any responses yet.");
define('LNG_Addon_Surveys_viewResponsesButtonNext',  "Browse Responses");
define('LNG_Addon_Surveys_viewResponsesButtonCancel',  "Cancel");
define('LNG_Addon_Surveys_viewResponsesLinkView',  "View");
define('LNG_Addon_Surveys_viewResponsesLinkDelete',  "Delete");
define('LNG_Addon_Surveys_viewResponsesConfirmCancel',  "Are you sure you wish to cancel? Click OK to confirm.");
define('LNG_Addon_Surveys_viewResponsesSelectResponsesToDelete',  "Please select which responses you would like to delete.");
define('LNG_Addon_Surveys_viewResponsesSelectForm',  "Select a Survey");
define('LNG_Addon_Surveys_viewResponsesFeedbackForm',  "Survey:");
define('LNG_Addon_Surveys_viewResponsesNoForms',  "You haven't created any surveys yet. You can create a new survey by clicking the button below.");

//;
//; viewing a response
//;
define('LNG_Addon_Surveys_viewResponseTitle',  "Viewing Response %s of %s for &quot;%s&quot;");
define('LNG_Addon_Surveys_viewResponseDescription',  "Use the buttons below to navigate between responses for the selected survey. To edit a response, click &quot;Edit Response&quot;.");
define('LNG_Addon_Surveys_viewResponseNoResponses',  "The form '%s' has no responses yet. <a href', 'index.php?section', module&action', custom&module', form&moduleAction', view.responses'>Go Back</a>.");
define('LNG_Addon_Surveys_viewResponseConfirmCancel',  "Are you sure you want to exit? Click OK to confirm.");
define('LNG_Addon_Surveys_viewResponseConfirmDelete',  "Are you sure you want to delete this response? Click OK to confirm.");
define('LNG_Addon_Surveys_viewResponseButtonEdit',  "Edit Response");

define('LNG_Addon_Surveys_viewResponseInvalidResponseId',  "Invalid Response ID");
define('LNG_Addon_Surveys_viewResponseInvalidSurveyId',  "Invalid Survey ID");
define('LNG_Addon_Surveys_viewResponseButtonCancel',  "Exit");
define('LNG_Addon_Surveys_viewResponseButtonDelete',  "Delete Response");
define('LNG_Addon_Surveys_viewResponseButtonPrevious',  "&laquo; Previous");
define('LNG_Addon_Surveys_viewResponseButtonNext',  "Next &raquo;");
define('LNG_Addon_Surveys_viewResponseResponseNumber',  "Response #%d");
define('LNG_Addon_Surveys_viewResponseNotProvided',  "(No Response was provided)");


//;
//; viewing a results
//;
define('LNG_Addon_Surveys_ResultsDefaultTitle', 'Survey Results');
define('LNG_Addon_Surveys_ResultsDefaultDescription', 'To view results for a survey start by choosing it from the list below.');
define('LNG_Addon_Surveys_ResultsViewResults', 'View Results');
define('LNG_Addon_Surveys_NoResults', 'No surveys have been created yet. Click the "Create a Survey..." button below to create one now.');
define('LNG_Addon_Surveys_ResultsViewResponsesCancel', 'Cancel');
define('LNG_Addon_Surveys_ResultsDefaultForm', 'Select a Survey');
define('LNG_Addon_Surveys_ResultsSurveySelect', 'Survey:');
define('LNG_Addon_Surveys_Results_exportResponses', 'Export Responses');
define('LNG_Addon_Surveys_Results_browseResponse', 'Browse Responses One-by-One');
define('LNG_Addon_Surveys_Results_others', 'Others');
define('LNG_Addon_Surveys_ResultsNoForms',  "There are no surveys to export the responses for.");
define('LNG_Addon_Surveys_resultsResponseTitle', "Response for \"%s\" Survey");
define('LNG_Addon_Surveys_resultsResponseDescription', "The results for the selected survey are shown below. You can also export or browse responses one-by-one using the buttons below.");


//define('LNG_Addon_Surveys_exportResponsesConfirmCancel',  "Are you sure you wish to cancel? Click OK to confirm.");


//;
//; saving responses
//;
define('LNG_Addon_Surveys_saveResponseMessageSuccess',  "Your changes to this response have been saved.");
define('LNG_Addon_Surveys_saveResponseMessageError',  "It looks like one or more fields aren't filled in or contain bad data. Please correct the errors in red below and try again.");

//;
//; deleting responses
//;
define('LNG_Addon_Surveys_deleteResponseMessageSuccess',  "Response #%d has been deleted for the survey '%s'.");
define('LNG_Addon_Surveys_deleteResponseMessageError',  "The selected response was unable to be deleted.");

define('LNG_Addon_Surveys_emailSubject', "New response for the '%s' survey on your web site");
define('LNG_Addon_Surveys_emailBodyStart', "Someone on your web site has filled out the '%s' form. Their responses are as follows:");
define('LNG_Addon_Surveys_emailViewLink', "You can view this response by clicking here: %s");
define('LNG_Addon_Surveys_emailEditLink', "You can make changes to this response by clicking here: %s");

define('LNG_Addon_Surveys_filesMustEndIn', "Please upload a file ending in ");
define('LNG_or', "or");




// LAST EDITED
//; canvas
//canvasEmptyText',  "(Drag a field from the left and drop it here)"




/****
 * ; core
ModuleNamePlural',  "Feedback Forms"
ModuleNameSingular',  "Feedback Form"
ModuleDescription',  "Collect, view and export feedback from visitors, leads and customers."

; control panel tab
ControlPanelTab',  "Feedback Forms"
ControlPanelTabTip',  "Collect, view and export feedback from visitors, leads and customers."

; tooltips
module_formInfotip',  "Feedback Forms"

; breadcrumbs
breadcrumb',  "Feedback Forms"
breadcrumb_indexAction',  "View Feedback Forms"
breadcrumb_createFormAction',  "Create a Feedback Form"
breadcrumb_editFormAction',  "Edit a Feedback Form"
breadcrumb_viewResponseAction',  "View Response"
breadcrumb_editResponseAction',  "Edit Response"
breadcrumb_viewResponsesAction',  "View Responses"
breadcrumb_exportResponsesAction',  "Export Responses"

; menu titles
menuTitleCreateForm',  "Create a Feedback Form"
menuTitleViewForms',  "View Feedback Forms"
menuTitleViewResponses',  "View Responses"
menuTitleExportResponses',  "Export Responses"

; menu details
menuDetailCreateForm',  "Build a custom feedback form to accept feedback on your web site."
menuDetailViewForms',  "Manage existing feedback forms which you've already created."
menuDetailViewResponses',  "See feedback that was submitted through your feedback forms."
menuDetailExportResponses',  "Download responses to a CSV file for further analysis in Microsoft Excel."


;
; viewing forms
;
viewFormsTitle',  "View Feedback Forms"
viewFormsDescription',  "Feedback forms allow you to collect and view feedback from visitors on your website. Click 'Create a Feedback Form...' to create one now."
viewFormsNoFormsMessage',  "You haven't created any forms yet. <a href', 'http://beast/trey/iwp/branches-5-1/admin/index.php?section', module&action', custom&module', form&moduleAction', edit.form'>Create a feedback form</a>."
viewFormsButtonCreateForm',  "Create a Feedback Form..."
viewFormsButtonDeleteForms',  "Delete Selected"
viewFormsThName',  "Form Name"
viewFormsThCreated',  "Date Created"
viewFormsThUpdated',  "Date Updated"
viewFormsThResponseCount',  "Number of Responses"
viewFormsThActions',  "Actions"
viewFormsLinkViewResponses',  "View Responses"
viewFormsLinkResponseSnapshot',  "Response Snapshot"
viewFormsLinkExportResponses',  "Export Responses"
viewFormsLinkEdit',  "Edit"
viewFormsLinkDelete',  "Delete"



;
; deleting forms
;
deleteFormMessageSuccess',  "The selected form has been deleted successfully."
deleteFormMessageError',  "The selected form was unable to be deleted."

;
; creating/editing forms
;

; main heading
editFormTitle',  "Edit a Feedback Form"
editFormDescription',  "Drag fields below to build your feedback form. Responses are saved for viewing/filtering from your control panel and can be emailed to you."




//  CUT HERE


; checkbox
formWidgetCheckboxDefaultName',  "Untitled List of Checkboxes"
formWidgetCheckboxDefaultDescription',  "Click here to enter some optional help text"
formWidgetCheckboxOptionRequiresAnAnswer',  "Requires an answer?"
formWidgetCheckboxOptionRandomize',  "Randomize?"
formWidgetCheckboxOptionVisibleToEveryone',  "Visible to everyone"
formWidgetCheckboxOptionVisibleToAdministrators',  "Visible only to administrators"

formWidgetCheckboxTooltipTitleRandom',  "Randomize"
formWidgetCheckboxTooltipDescriptionRandom',  "<p>Choosing this option will randomize the order of the checkboxes when displayed to the user.</p>"
formWidgetCheckboxTooltipTitleVisibility',  "Visibility"
formWidgetCheckboxTooltipDescriptionVisibility',  "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>"

; file
formWidgetFileDefaultName',  "Untitled File Upload Field"
formWidgetFileDefaultDescription',  "Click here to enter some optional help text"
formWidgetFileOptionRequiresAnAnswer',  "Requires an answer?"
formWidgetFileOptionVisibleToEveryone',  "Visible to everyone"
formWidgetFileOptionVisibleToAdministrators',  "Visible only to administrators"
formWidgetFileOptionAllowAllFileTypes',  "Allow all file types?"
formWidgetFileValueFile',  "File will be uploaded here"
formWidgetFileValueAllowedFileTypes',  "doc, xls, pdf, gif, jpg"
formWidgetFileTextBrowse',  "Browse..."

formWidgetFileTooltipTitleVisibility',  "Visibility"
formWidgetFileTooltipDescriptionVisibility',  "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>"
formWidgetFileTooltipTitleAllowedFileTypes',  "Allowed File Types"
formWidgetFileTooltipDescriptionAllowedFileTypes',  "<p>Only the file types entered here are allowed to be uploaded/sent.</p><p>File types are file extensions separated by a comma (i.e. pdf, txt, doc, docx).</p>"

; radio
formWidgetRadioDefaultName',  "Untitled List of Radio Buttons"
formWidgetRadioDefaultDescription',  "Click here to enter some optional help text"
formWidgetRadioOptionRequiresAnAnswer',  "Requires an answer?"
formWidgetRadioOptionRandomize',  "Randomize?"
formWidgetRadioOptionVisibleToEveryone',  "Visible to everyone"
formWidgetRadioOptionVisibleToAdministrators',  "Visible only to administrators"

formWidgetRadioTooltipTitleRandom',  "Random Radio Button Order"
formWidgetRadioTooltipDescriptionRandom',  "<p>Choosing this option will randomize the order of the radio buttons when displayed to the user.</p>"
formWidgetRadioTooltipTitleVisibility',  "Visibility"
formWidgetRadioTooltipDescriptionVisibility',  "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>"

; section break
formWidgetSectionBreakDefaultName',  "Untitled Section Break"
formWidgetSectionBreakDefaultDescription',  "Click here to enter some optional help text"

; select list
formWidgetSelectDefaultName',  "Untitled Dropdown"
formWidgetSelectDefaultDescription',  "Click here to enter some optional help text"
formWidgetSelectOptionRequiresAnAnswer',  "Requires an answer?"
formWidgetSelectOptionRandomize',  "Randomize?"
formWidgetSelectOptionVisibleToEveryone',  "Visible to everyone"
formWidgetSelectOptionVisibleToAdministrators',  "Visible only to administrators"

formWidgetSelectTooltipTitleRandom',  "Randomize"
formWidgetSelectTooltipDescriptionRandom',  "<p>Choosing this option will randomize the order of the options in the dropdown list when displayed to the user.</p>"
formWidgetSelectTooltipTitleVisibility',  "Visibility"
formWidgetSelectTooltipDescriptionVisibility',  "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>"

; text
formWidgetTextDefaultName',  "Untitled Line of Text"
formWidgetTextDefaultDescription',  "Click here to enter some optional help text"
formWidgetTextValueText',  "Response will be typed here"
formWidgetTextOptionRequiresAnAnswer',  "Requires an answer?"
formWidgetTextOptionVisibleToEveryone',  "Visible to everyone"
formWidgetTextOptionVisibleToAdministrators',  "Visible only to administrators"

formWidgetTextTooltipTitleVisibility',  "Visibility"
formWidgetTextTooltipDescriptionVisibility',  "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>"

; textarea
formWidgetTextareaDefaultName',  "Untitled Paragraph of Text"
formWidgetTextareaDefaultDescription',  "Click here to enter some optional help text"
formWidgetTextareaValueTextarea',  "Response will be typed here"
formWidgetTextareaOptionRequiresAnAnswer',  "Requires an answer?"
formWidgetTextareaOptionVisibleToEveryone',  "Visible to everyone"
formWidgetTextareaOptionVisibleToAdministrators',  "Visible only to administrators"

formWidgetTextareaTooltipTitleVisibility',  "Visibility"
formWidgetTextareaTooltipDescriptionVisibility',  "<p>Choose <em>Visible to Everyone</em> to display this field as part of the form on your website.</p><p>Choose <em>Visible to Admins Only</em> to allow only administrators to enter information in this field.</p>"



 *
 *
 */


?>