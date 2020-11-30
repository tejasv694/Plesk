<?php
/**
* The Custom Fields Text API. This only handles the text type of custom field.
*
* @version     $Id: customfields_textarea.php,v 1.8 2007/05/30 01:34:53 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage CustomFields_API
*/

/**
* Include the custom fields api if we need to.
* This includes the base API class itself, so we don't need to worry about it.
*/
require_once(dirname(__FILE__) . '/customfields.php');

/**
* This class handles validation and checking of type text custom fields.
*
* @package API
* @subpackage CustomFields_API
*/
class CustomFields_Textarea_API extends CustomFields_API
{

	/**
	* This overrides the parent classes setting so we know what sort it is.
	*
	* @see CustomFields_API::fieldtype
	*
	* @var String
	*/
	var $fieldtype = 'textarea';

	/**
	* Options for this custom field type.
	*
	* @var Array
	*/
	var $Options = array(
		'Rows' => '0',
		'Columns' => '0',
                'ApplyDefault' => ''
	);

	/**
	* Constructor
	* Calls the parent object's constructor.
	*
	* @param Int $fieldid The field to load up. This is passed to the parent constructor for processing.
	* @param Boolean $connect_to_db Whether to connect to the database or not. If this is set to false, you need to set the database up yourself.
	*
	* @see CustomFields_API::CustomFields_API
	* @see Db
	*
	* @return Boolean Returns the parent's constructor.
	*/
	function CustomFields_Textarea_API($fieldid=0, $connect_to_db=true)
	{
		return $this->CustomFields_API($fieldid, $connect_to_db);
	}

	/**
	* CheckData
	* Checkdata makes sure the data passed in is valid for this field type.
	* If the custom field is loaded, This always returns true - text fields accept any data.
	*
	* @param String $data The data to validate. Since this is a text field, anything is valid.
	*
	* @see ValidData
	* @see IsLoaded
	*
	* @return Boolean Returns false if the field hasn't been loaded properly, otherwise true.
	*/
	function CheckData($data='')
	{
		if (!$this->IsLoaded()) {
			return false;
		}

		return true;
	}


	/**
	* DisplayFieldOptions
	* This displays options for the custom field if it is loaded. This will print a simple text box for any sort of input.
	*
	* @param String $defaultvalue A default value for the item.
	* @param Boolean $useoptions Whether to use the fieldlength and maxlength options passed in. This is used when displaying forms but not in the admin area so the fields have a consistent look in the admin area.
	* @param Int $formid The form we are generating the content for. This is used to modify the form id's and javascript slightly to allow multiple forms to be on the same page.
	*
	* @see IsLoaded
	* @see Options
	*
	* @return String Returns a blank string if the custom field hasn't been loaded. If it has, it will create a text entry for the item.
	*/
	function DisplayFieldOptions($defaultvalue='', $useoptions=false, $formid=0)
	{
		if (!$this->IsLoaded()) {
			return '';
		}

		$formid = (int)$formid;

		$length = '';
		if ($useoptions) {
			if ($this->Settings['Rows'] > 0) {
				$length .= " rows='" . (int)$this->Settings['Rows'] . "'";
			}
			if ($this->Settings['Columns'] > 0) {
				$length .= " cols='" . (int)$this->Settings['Columns'] . "'";
			}
		}

		$return_string = '<textarea name="CustomFields[' . $this->fieldid . ']" id="CustomFields_' . $this->fieldid;
		if ($formid > 0) {
			$return_string .= '_' . $formid;
		}

		$return_string .= '"';

		if (!empty($length)) {
			$return_string .= $length;
		}

		$return_string .= '>'.htmlspecialchars($defaultvalue, ENT_QUOTES, SENDSTUDIO_CHARSET) . '</textarea>';

		return $return_string;
	}

	/**
	* CreateJavascript
	* Creates a javascript check for this field for a form to use.
	* It uses the id of the field to check for a value.
	* If the field isn't loaded, then this returns nothing.
	* This is a very basic check.
	*
	* @param Int $formid The form that we are creating javascript for. We need to know this so we can reference the right javascript function.
	*
	* @see IsLoaded
	* @see Forms_API::GetHTML
	*
	* @return String Returns a string with javascript in it for the form to use.
	*/
	function CreateJavascript($formid=0) {
		if (!$this->IsLoaded()) {
			return '';
		}

		if (!$this->IsRequired()) {
			return '';
		}

		$formid = (int)$formid;

		$alert = sprintf(GetLang('Form_Javascript_Field'), $this->GetFieldName());

		$javascript = '
			var fname = "CustomFields_' . $this->fieldid;
			if ($formid > 0) {
				$javascript .= '_' . $formid;
			}
			$javascript .= '";
			var fld = document.getElementById(fname);
			if (fld.value == "") {
				alert("' . $alert . '");
				fld.focus();
				return false;
			}
		';
		return $javascript;
	}

}

?>
