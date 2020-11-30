<?php
/**
* Segment language variables.
* This file is used by "Segment" pages.
*
* @see GetLang
*
* @version     $Id: language.php,v 1.153 2008/02/22 04:45:13 chris Exp $
* @author Hendri <hendri@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Please backup before you start.
*/
define('LNG_SegmentName', 'Segment Name');
define('LNG_HLP_SegmentName', 'Enter a name for this segment, such as \\\'Leads in New York\\\' or \\\'Customers who subscribed in March\\\'. This name is used for your reference only and is never visible to contacts.');

define('LNG_SegmentManage', 'View Segments');
define('LNG_Help_SegmentManage', 'A segment is a filtered view for one or more of your contact lists. You can view contacts by segments and even send campaigns to a specific segment.');
define('LNG_SegmentManageCreateNew', 'Create a Segment...');
define('LNG_SegmentManageCreateNew_Title', 'Create a new segment');

define('LNG_SegmentManageNoSegment', 'No segments are available. %s');
define('LNG_SegmentManageSegmentCreate', '&nbsp;Click the "' . LNG_SegmentManageCreateNew . '" button below to create one.');
define('LNG_SegmentManageSegmentAssign', '&nbsp;Please contact your system administrator to assign you a segment.');

define('LNG_SegmentDelete', 'Delete the selected segment(s)');
define('LNG_SegmentAlertChooseSegment', 'Please choose one or more segments first.');

define('LNG_SegmentManageConfirmDeleteOne', 'Are you sure you want to delete the selected segment?');
define('LNG_SegmentManageConfirmDeleteMany', 'Are you sure you want to delete the selected segments?');

define('LNG_SegmentManageDeleteSuccessOne', 'The selected segment has been deleted.');
define('LNG_SegmentManageDeleteSuccessMany', 'The selected segments have been deleted.');

define('LNG_SegmentManageDeleteErrorNoPrivilege', 'You don\'t have permission to delete the selected segment.');
define('LNG_SegmentManageDeleteErrorOne', 'An error occured while trying to delete the selected segment. Please try again.');
define('LNG_SegmentManageDeleteErrorMany', 'An error occured while trying to delete the selected segments. Please try again.');

define('LNG_SegmentManageCopySuccess', 'The selected segment has been copied successfuly');

define('LNG_SegmentManageCopyError', 'A database error occured while trying to copy the selected segment. Please try again.');

define('LNG_SegmentManageCopyErrorNoPrivilege', 'The selected segment can\'t be copied because you don\'t have access to all of the lists contained in this segment.');
define('LNG_SegmentManageSaveErrorNoPrivilege', 'Your changes can\'t be saved because that segment now contains one or more lists which you don\'t have access to.');

define('LNG_SegmentManageEditWarningPrivilage', 'Since you last accessed this segment a filter has been added for a list that you don\'t have access to, therefore you can\'t make any changes.');

define('LNG_SegmentFormRuleDescription', 'Filter Contacts Where');

define('LNG_SegmentFormTitleEdit', 'Edit a Segment');
define('LNG_SegmentFormTitleCreate', 'Create a Segment');
define('LNG_SegmentFormTitleCreateIntro', 'A segment is a filtered view across one or more of your contact lists. For example you could create a segment to view contacts in New York across all of your lists.');
define('LNG_SegmentFormTitleEditIntro', LNG_SegmentFormTitleCreateIntro);

define('LNG_SegmentFormAlertSpecifySegmentName', 'Please enter a name for this segment.');
define('LNG_SegmentFormAlertAtLeastOneRule', 'Please create at least one filter for this segment.');
define('LNG_SegmentFormAlertAtLeastOneList', 'Please choose at least one list to segment contacts from.');

define('LNG_SegmentFormAlertInitializingValues', 'Error initializing values');
define('LNG_SegmentFormAlertCancel', 'Are you sure you want to cancel?');
define('LNG_SegmentFormAlertErrorRequestingData', 'Unable to request data from server');

define('LNG_SegmentFormErrorCannotLoadRecord', 'An error occured while trying to display the selected segment. Please try again.');
define('LNG_SegmentFormSaveFailed', 'An error occured while trying to save the selected segment. Please try again.');
define('LNG_SegmentFormSaveSuccess', 'The selected segment has been updated successfully.');

define('LNG_SegmentFormHeaderDetails', 'Segment Details');
define('LNG_SegmentFormHeaderRules', 'Segment Rules');

define('LNG_SegmentFormFieldMailingList', 'Segment Contacts From');
define('LNG_HLP_SegmentFormFieldMailingList', 'Which contact list(s) do you want to segment? You can select multiple lists if required, simply by ticking the ones you want to include.');
define('LNG_SegmentFormFieldMatchType', 'Match Type');
define('LNG_HLP_SegmentFormFieldMatchType', 'How do you want the segment rules you create below to be applied? Choose \\\'Match all rules\\\' to only see contacts who match every rule you create. Choose \\\'Match any rule\\\' to see contacts who match one or more rules.');
define('LNG_SegmentFormMatchAllRule', 'Match all rules (AND condition)');
define('LNG_SegmentFormMatchAnyRule', 'Match any rule (OR condition)');

define('LNG_SegmentFormOperator_date_equalto', 'is on');
define('LNG_SegmentFormOperator_date_notequalto', 'is not on');
define('LNG_SegmentFormOperator_date_greaterthan', 'is after');
define('LNG_SegmentFormOperator_date_lessthan', 'is before');
define('LNG_SegmentFormOperator_date_between', 'is between');

define('LNG_SegmentFormOperator_number_equalto', 'is');
define('LNG_SegmentFormOperator_number_notequalto', 'is not');
define('LNG_SegmentFormOperator_number_greaterthan', 'is greater than');
define('LNG_SegmentFormOperator_number_lessthan', 'is less than');
define('LNG_SegmentFormOperator_number_between', 'is between');

define('LNG_SegmentFormOperator_multiple_equalto', 'include');
define('LNG_SegmentFormOperator_multiple_notequalto', 'do not include');

define('LNG_SegmentFormOperator_link_equalto', 'has clicked');
define('LNG_SegmentFormOperator_link_notequalto', 'has not clicked');

define('LNG_SegmentFormOperator_campaign_equalto', 'has opened');
define('LNG_SegmentFormOperator_campaign_notequalto', 'has not opened');

define('LNG_SegmentFormCheckbox_SelectInstruction', 'Click to select');
define('LNG_SegmentFormCheckbox_SelectTooltip', 'Click here to change/add value');
define('LNG_SegmentFormCheckbox_SelectWindowTitle', 'Select value(s)');

define('LNG_SegmentDoesntExist', 'The segment you are trying to edit does not exist. Please try again.');


/**
*****************************
* Changed/Added in IEM 5.0.1
*****************************
*/
define('LNG_SegmentFormOperator_text_equalto', 'is');
define('LNG_SegmentFormOperator_text_notequalto', 'is not');
define('LNG_SegmentFormOperator_text_like', 'contains');
define('LNG_SegmentFormOperator_text_notlike', 'does not contain');

define('LNG_SegmentFormOperator_dropdown_equalto', 'is');
define('LNG_SegmentFormOperator_dropdown_notequalto', 'is not');

/**
*****************************
* Changed/Added in IEM 5.0.2
*****************************
*/
define('LNG_SegmentFormOperator_common_customfields_isempty', 'is empty');
define('LNG_SegmentFormOperator_common_customfields_isnotempty', 'is not empty');

/**
*****************************
* Changed/Added in IEM 5.7.1
*****************************
*/
define('LNG_SegmentCustomField_Basic', 'Basic Fields');
define('LNG_SegmentCustomField_CustomField', 'Custom Fields');
