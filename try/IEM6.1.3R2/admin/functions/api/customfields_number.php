<?php
/**
* The Custom Fields Numbers API. This only handles the numbers type of custom field.
*
* @version     $Id: customfields_number.php,v 1.15 2007/09/18 01:20:53 chris Exp $
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
* This class handles validation and checking of type number custom fields.
*
* @package API
* @subpackage CustomFields_API
*/
class CustomFields_Number_API extends CustomFields_API
{

	/**
	* This overrides the parent classes setting so we know what sort it is.
	*
	* @see CustomFields_API::fieldtype
	*
	* @var String
	*/
	var $fieldtype = 'number';

	/**
	* Options for this custom field type.
	*
	* @var Array
	*/
	var $Options = array(
		'MinLength' => '0',
		'MaxLength' => '0',
		'FieldLength' => '50',
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
	function CustomFields_Number_API($fieldid=0, $connect_to_db=true)
	{
		return $this->CustomFields_API($fieldid, $connect_to_db);
	}

	/**
	* CheckData
	* Check to make sure there are only numbers passed in. This includes removing spaces, commas etc.
	*
	* @param String $data The data to check. Spaces and commas are stripped out and we check whether there are any non-numeric characters left (eg abcdef).
	*
	* @see ValidData
	* @see IsLoaded
	*
	* @return Boolean If it's a valid option, this returns true. Otherwise false (including if the field hasn't been loaded correctly).
	*/
	function CheckData($data='')
	{
		if (!$this->IsLoaded()) {
			return false;
		}

		if (empty($data)) {
			return true;
		}
		
		return is_numeric($data);
	}

	/**
	* DisplayFieldOptions
	* This displays options for the custom field if it is loaded. This will display a simple text box with the default value filled in (if it is available).
	*
	* @param Int $defaultvalue A default value for the item. If nothing is passed in, nothing is filled in. If anything is passed in, it is converted to a number before prefilling the text field.
	* @param Boolean $useoptions Whether to use the fieldlength and maxlength options passed in. This is used when displaying forms but not in the admin area so the fields have a consistent look in the admin area.
	* @param Int $formid The form we are generating the content for. This is used to modify the form id's and javascript slightly to allow multiple forms to be on the same page.
	*
	* @see IsLoaded
	*
	* @return String Returns a blank string if the custom field hasn't been loaded. If it has, it will create a text entry for the item.
	*/
	function DisplayFieldOptions($defaultvalue=0, $useoptions=false, $formid=0)
	{
		if (!$this->IsLoaded()) {
			return '';
		}

		if ($defaultvalue === false) {
			$defaultvalue = '';
		} else {
			$defaultvalue = (int)$defaultvalue;
		}

		$length = '';
		if ($useoptions && $this->Settings['FieldLength'] > 0) {
			$length = " size='" . (int)$this->Settings['FieldLength'] . "'";
			if ($this->Settings['MaxLength'] > 0) {
				$length .= " maxlength='" . (int)$this->Settings['MaxLength'] . "'";
			}
		}

		$return_string = '<input type="text" name="CustomFields[' . $this->fieldid . ']" id="CustomFields_' . $this->fieldid;
		if ($formid > 0) {
			$return_string .= '_' . $formid;
		}
		$return_string .= '" value="' . $defaultvalue . '"' . $length . '>';
		return $return_string;
	}


	/**
	* CreateJavascript
	* Creates a javascript check for this field for a form to use.
	* It uses the id of the field to check for a value.
	* If the field isn't loaded, then this returns nothing.
	* This checks to make sure the value entered is a number.
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

		$formid = (int)$formid;

		$alert = sprintf(GetLang('Form_Javascript_Field'), $this->GetFieldName());

		$number_alert = sprintf(GetLang('Form_Javascript_Field_NumberCheck'), $this->GetFieldName());

		$javascript = '
			var fname = "CustomFields_' . $this->fieldid;
		if ($formid > 0) {
			$javascript .= '_' . $formid;
		}
		$javascript .= '";
			var fld = document.getElementById(fname);
		';

		if ($this->IsRequired()) {
			$javascript .= '
				if (fld.value == "") {
					alert("' . $alert . '");
					fld.focus();
					return false;
				}
			';
		}

		$javascript .= '
			CheckNum = parseInt(fld.value);
			if(fld.value != "" && isNaN(CheckNum)) {
				alert("' . $number_alert . '");
				fld.select();
				fld.focus();
				return false;
			}
		';

		return $javascript;
	}

}

?>
