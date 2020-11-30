<?php
/**
* Language file variables for custom fields. This includes creating, editing, deleting, updating, managing etc.
*
* @see GetLang
*
* @version     $Id: customfields.php,v 1.25 2008/02/18 06:33:38 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the customfields area... Please backup before you start!
*/
define('LNG_CustomFieldsName', 'Custom Field');
define('LNG_CustomFieldsType', 'Type');

define('LNG_CustomFieldDeleteFail_One', '1 custom field has not been deleted successfully. Please try again.');
define('LNG_CustomFieldDeleteFail_Many', '%s custom fields have not been deleted successfully. Please try again.');

define('LNG_CustomField_Edit_Disabled', 'You cannot edit this custom field because you do not have access.');

define('LNG_EditCustomField', 'Edit Custom Field');
define('LNG_EditCustomFieldIntro', 'Complete the form below to update the selected custom field.');
define('LNG_EditCustomField_CancelPrompt', 'Are you sure you want to cancel editing of this custom field?');

define('LNG_CustomFieldCreated', 'Custom field created successfully');
define('LNG_UnableToCreateCustomField', 'Unable to create custom field');

define('LNG_CustomFieldUpdated', 'The selected custom field has been updated.');

define('LNG_UnableToUpdateCustomField', 'Unable to update custom field');

define('LNG_CreateCustomField_CancelPrompt', 'Are you sure you want to cancel creating this custom field?');

define('LNG_CreateCustomFieldCancelButton', 'Cancel');
define('LNG_CreateCustomFieldHeading', 'Create a new custom field below');

define('LNG_CustomFieldName', 'Custom Field Name');
define('LNG_HLP_CustomFieldName', 'Enter a name for this custom field. The name will appear on your newsletter subscription form. For example, \\\'First Name\\\', \\\'Gender\\\'.');

define('LNG_CustomFieldType', 'Custom Field Type');

define('LNG_EnterCustomFieldName', 'Please enter a name for your custom field.');
define('LNG_CustomFieldDetails', 'Custom Field Details');

define('LNG_CustomFieldType_text', 'Text Field');
define('LNG_CustomFieldType_date', 'Date Field');
define('LNG_CustomFieldType_multicheckbox', 'Multiple Checkbox Field');
define('LNG_CustomFieldType_dropdown', 'Pick List');

define('LNG_EditCustomField_Step3', 'Edit Custom Field');

define('LNG_FieldLength', 'Field Length');
define('LNG_HLP_FieldLength', 'The length of the text box as it will appear on your forms. Leave this field blank if you are unsure.');

define('LNG_DefaultValue', 'Default Value');
define('LNG_ApplyDefault', 'Apply Default Value to Existing?');
define('LNG_ApplyDefaultToExistingExplain', 'Yes, apply the default value to existing contacts in the contact list(s).');

define('LNG_MaxLength', 'Maximum Length');

define('LNG_MinLength', 'Minimum Length');

define('LNG_Instructions', 'Instructional Text');
define('LNG_HLP_Instructions', 'Enter in the instructions the user will see when prompted to select an option. This is usually instructions such as \\\'Please select an option\\\'.');

define('LNG_Dropdown_Value', 'Option Display Text');

define('LNG_Checkbox_Value', 'Checkbox Display Text');

define('LNG_Radiobutton_Value', 'Radio Display Text');

define('LNG_AddMore', 'Click here to add another value');

define('LNG_DateDisplayOrder', 'Date Display Order');
define('LNG_HLP_DateDisplayOrder', 'In what order do you want the date values to display? Enter day, month or year in each of the three display fields.');
define('LNG_DateDisplayOrderFirst', 'Display Order (First)');
define('LNG_DateDisplayOrderSecond', 'Display Order (Second)');
define('LNG_DateDisplayOrderThird', 'Display Order (Third)');
define('LNG_DateDisplayStartYear', 'Start Year');
define('LNG_HLP_DateDisplayStartYear', 'When displaying the year dropdown list, which year should be first in the list?');
define('LNG_DateDisplayEndYear', 'End Year');
define('LNG_HLP_DateDisplayEndYear', 'When displaying the year dropdown list, which year should be last in the list?<br/>If 0 is entered, this will be the current year (' . date('Y') . ') and change automatically.');

define('LNG_DeleteCustomFieldButton', 'Delete Selected');
define('LNG_ChooseFieldsToDelete', 'Please choose a custom field first.');
define('LNG_DeleteCustomFieldPrompt', 'Are you sure you want to delete this custom field?');
define('LNG_CannotDeleteCustomField_NoAccess', 'You do not have permission to delete field \'%s\'.');
define('LNG_CustomField_Delete_Disabled', 'You do not have permission to delete this field.');

define('LNG_DropdownInstructions', '-- Please choose an option --');

define('LNG_TextAreaRows', 'Number of Rows');
define('LNG_HLP_TextAreaRows', 'The number of rows to show in the multiline text box. This is how long the multiline text box will be.');

define('LNG_TextAreaColumns', 'Number of Columns');
define('LNG_HLP_TextAreaColumns', 'The number of columns to show in the multiline text box. This is how wide the multiline text box will be.');

/**
**************************
* Changed/added in NX 1.3
**************************
*/
define('LNG_CustomFieldsManage', 'View Custom Fields');
define('LNG_NoCustomFields', 'No custom fields have been created. Click the <em>Create a Custom Field...</em> button below to create one.');
define('LNG_CreateCustomFieldButton', 'Create a Custom Field...');

define('LNG_CreateCustomField', 'Create a Custom Field');

define('LNG_CustomFieldTypeHelp', 'Every custom field has a particular type of data. For example, a \\\'First Name\\\' custom field will simply be a textbox. On the other hand, a \\\'Gender\\\' custom field will be radio buttons, as users will be able to select from either \\\'Male\\\' or \\\'Female\\\'. Here you will need to select the \\\'Custom Field Type\\\' that is relevant to your \\\'Custom Field\\\'.<br><br>Examples: <br><br><table border=0 cellpadding=\\\'2\\\' cellspacing=\\\'0\\\' style=\\\'width:220px\\\'><tr><td>Type</td><td>Example</td><tr><td style=\\\'width: 50%\\\'>Text:</td><td style=\\\'width:50%\\\'><input type=text style=\\\'font-family: tahoma; font-size: 10px; width:100px;\\\' value=\\\'My Name\\\'></td></tr><tr><td>Multiline Text:</td><td><textarea style=\\\'font-family: tahoma; font-size: 10px; width:100px;\\\' rows=\\\'2\\\'>My Address</textarea></td></tr><tr><td>Numbers Only:</td><td><input type text style=\\\'font-family: tahoma; font-size: 10px; width:100px;\\\' value=\\\'1800555777\\\'></td></tr><tr><td>Dropdown List:</td><td><select style=\\\'font-family: tahoma; font-size: 10px; width:100px;\\\'><option>Select Country</option></select></td></tr><tr><td>Checkboxes:</td><td style=\\\'font-family: tahoma; font-size: 10px;\\\'><input type=\\\'checkbox\\\'>Red <input type=\\\'checkbox\\\'>Blue</td></tr><tr><td>Radio Buttons:</td><td style=\\\'font-family: tahoma; font-size: 10px;\\\'><input type=\\\'radio\\\'>Male <input type=\\\'radio\\\'>Female</td></tr><tr><td>Date Field:</td><td style=\\\'font-family: tahoma; font-size:10px;\\\' nowrap><select style=\\\'font-family: tahoma; font-size:10px; width:35px\\\'><option>31</option></select><select style=\\\'font-family: tahoma; font-size:10px; width:45px\\\'><option>July</option></select><select style=\\\'font-family: tahoma; font-size:10px; width:45px\\\'><option>2010</option></select></td></tr></table>');

define('LNG_CustomFieldRequired', 'Is This Field Required?');
define('LNG_CustomFieldRequiredExplain', 'Yes, contacts must fill in this field to be added to my list');

define('LNG_CustomFieldType_textarea', 'Multiline Text Field');

define('LNG_CustomFieldType_number', 'Numbers Only');
define('LNG_CustomFieldType_checkbox', 'Checkboxes');
define('LNG_CustomFieldType_radiobutton', 'Radio Buttons');

define('LNG_CreateCustomField_Step2', 'Create a Custom Field');
define('LNG_CreateCustomField_Step2_Intro', 'Fill out the form below and then click <em>Next &gt;&gt;</em> to continue.');

define('LNG_CreateCustomField_Step3', 'Create a Custom Field');

define('LNG_HLP_MaxLength', 'Enter a maximum length for text typed into this custom field. For example, entering 2 will limit input to only 2 characters. This is useful if you are collecting information such as a postcode where you want to limit the input to a maximum of 4 or 5 characters. This field is optional, so if you are unsure, leave it blank.');

define('LNG_HLP_MinLength', 'Enter a minimum length for text typed into this custom field. For example, entering 2 means the user has to type in a minimum of 2 characters. This is useful if you want to make sure someone has entered in valid information. For example, if you want to make sure that a valid phone number is entered you could enter in a minimum length of 8. This field is optional, so if you are unsure, leave it blank.');

define('LNG_Checkbox', 'Checkbox');
define('LNG_RadioButton', 'Radio Button');


define('LNG_Value', 'Value');
define('LNG_Display_Text', 'Display Text');

define('LNG_Value_Required', 'You need to enter a Value for this option here.');
define('LNG_Display_Required', 'You need to enter a Display Text for this option here.');

/**
**************************
* Changed/added in NX 5.0
**************************
*/
define('LNG_CustomFieldDeleteSuccess_One', '1 custom field has been deleted. You should update your website forms to make sure they don\'t use the custom field you deleted.');

define('LNG_CustomFieldDeleteSuccess_Many', '%s custom fields have been deleted. You should update your website forms to make sure they don\'t use the custom field you deleted.');

define('LNG_Help_CustomFieldsManage', 'Custom fields allows you to collect and store more information that just a contact\'s email address, such as their name, country, etc.');
define('LNG_CreateCustomFieldIntro', 'To create a custom field, start by giving it a name and choosing the type of field you want to create below. Click <em>Next &gt;&gt;</em> to continue. <a href="Javascript:LaunchHelp(\'%%WHITELABEL_INFOTIPS%%\',\'810\')">Learn about custom fields here.</a>');

define('LNG_HLP_CustomFieldRequired', 'To make this field mandatory, select this option. When contacts are added or when they subscribe to your contact list, they will be forced to fill in this custom field. For example, if you want to make sure that all contacts enter in their \\\'First Name\\\' then you would select this option for your \\\'First Name\\\' custom field. If you are unsure, leave this option unselected');

define('LNG_HLP_DefaultValue', 'The default value is the value that will show up on your website subscription forms and as a substitute if the contact does not fill in the data. For example, if you are collecting a contacts first name, you could put the word Friend in here. This way, if they don\\\'t enter in their name, they will be addressed as \\\'Friend\\\'. This field is optional, so if you are unsure, leave it blank.');

define('LNG_AssociateCustomField', 'Select Contact Lists');

define('LNG_CustomFieldListAssociation', 'Associate the custom field "%s" with your contact lists');

define('LNG_ChooseCustomFieldLists', 'Please select one or more contact lists to associate this custom field with.');

define('LNG_CreateCustomField_Step3_Intro', 'Choose which contact list(s) you\'d like to associate this custom field with and then click the Save button below.');
define('LNG_EditCustomField_Step3_Intro', LNG_CreateCustomField_Step3_Intro);

define('LNG_CustomFields_NoLists', 'You need access to a contact list before you can create custom fields. %s');

define('LNG_WhatAreCustomFields', 'Learn about custom fields here.');

define('LNG_CustomFieldDoesntExist', 'The custom field you are trying to edit does not exist. Please try again.');
define('LNG_CustomFieldRequired1', 'Mandatory');
define('LNG_SelectCustomFieldType', 'Please choose a custom field type.');

define('LNG_CustomFieldDesc_text', 'Allows users to enter any combination of letters and numbers');
define('LNG_CustomFieldDesc_textarea', 'Allows users to enter text on separate lines');
define('LNG_CustomFieldDesc_number', 'Allows users to enter any number, range can be restricted');
define('LNG_CustomFieldDesc_dropdown', 'Allows users to select a value from a list you define');
define('LNG_CustomFieldDesc_checkbox', 'Allows users to select multiple true/false values using checkboxes');
define('LNG_CustomFieldDesc_radiobutton', 'Allows users to select only one value from a list of options');
define('LNG_CustomFieldDesc_date', 'Allows users to pick a date, optionally within a certain range');

define('LNG_CustomField_Values', 'List of Values');
define('LNG_CustomField_Dropdown_Hint', 'Enter the list of values for the picklist above. Each value should be separated by a new line.');
define('LNG_CustomField_Checkbox_Hint', 'Enter the list of values for the checkboxes above. Each value should be separated by a new line.');
define('LNG_CustomField_RadioButton_Hint', 'Enter the list of values for the radio buttons above. Each value should be separated by a new line.');
define('LNG_CustomFields_NoMultiValues', 'Please enter at least one value.');
define('LNG_CustomField_NoDefaultValue', 'Please enter some instructional text.');
define('LNG_CustomField_NoFieldName', 'Please enter a name for this custom field.');
define('LNG_CustomField_Checkbox_Help', 'Each value you enter (one per line) will have its own checkbox. For example, if you type in <em>Yes&lt;ENTER>No&lt;ENTER>Maybe</em> then 3 separate checkboxes created.');
define('LNG_CustomFields_Sort_Alpha', 'Sort Values Alphabetically');

define('LNG_DropDown', 'Pick List');

/**
**************************
* Changed/added in 5.5.7
**************************
*/
define('LNG_Checkbox_Key', 'Checkbox Key Value');
define('LNG_HLP_Checkbox_Value', 'Enter the display text for this checkbox. This is what the contact sees when they choose this option.');
define('LNG_HLP_Checkbox_Key', "This value is derived automatically from the checkbox display text.<br/><br/>This is the value that will be stored in the database.<br/><br/>When you edit your checkbox display value, THIS KEY VALUE will NOT be CHANGED.");

define('LNG_Dropdown_Key', 'Option Key Value');
define('LNG_HLP_Dropdown_Value', 'Enter the display text for this option. This is what the contact sees when they choose this option.');
define('LNG_HLP_Dropdown_Key', "This value is derived automatically from the picklist display text.<br/><br/>This is the value that will be stored in the database.<br/><br/>When you edit your picklist display value, THIS KEY VALUE will NOT be CHANGED.");

define('LNG_Radiobutton_Key', 'Radio Key Value');
define('LNG_HLP_Radiobutton_Value', 'Enter the display text for this radio button. This is what the contact sees when they choose this option.');
define('LNG_HLP_Radiobutton_Key', "This value is derived automatically from the radiobutton display text.<br/><br/>This is the value that will be stored in the database.<br/><br/>When you edit your radiobutton display value, THIS KEY VALUE will NOT be CHANGED.");
