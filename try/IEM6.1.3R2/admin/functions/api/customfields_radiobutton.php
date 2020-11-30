<?php
/**
* The Custom Fields Radiobutton API. This only handles the radio button type of custom field.
*
* @version     $Id: customfields_radiobutton.php,v 1.11 2007/03/21 12:51:33 chris Exp $
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
* This class handles validation and checking of radio button custom fields.
*
* @package API
* @subpackage CustomFields_API
*/
class CustomFields_Radiobutton_API extends CustomFields_API
{

	/**
	* This overrides the parent classes setting so we know what sort it is.
	*
	* @see CustomFields_API::fieldtype
	*
	* @var String
	*/
	var $fieldtype = 'radiobutton';

	/**
	* Options for this custom field type.
	*
	* @var Array
	*/
	var $Options = array(
		'Key' => '',
		'Value' => ''
	);

	/**
	* Constructor
	* Unsets the default value for radio buttons and then calls the parent object's constructor.
	* Radio buttons don't need instructions/default values.
	*
	* @param Int $fieldid The field to load up. This is passed to the parent constructor for processing.
	* @param Boolean $connect_to_db Whether to connect to the database or not. If this is set to false, you need to set the database up yourself.
	*
	* @see CustomFields_API::CustomFields_API
	* @see Db
	*
	* @return Boolean Returns the parent's constructor.
	*/
	function CustomFields_Radiobutton_API($fieldid=0, $connect_to_db=true)
	{
		unset($this->SharedOptions['DefaultValue']);
		return $this->CustomFields_API($fieldid, $connect_to_db);
	}

	/**
	* CheckData
	* Checkdata makes sure the data passed in is valid for this field type.
	*
	* @param Array $data The data to validate. If it's not an array it gets converted to one.
	*
	* @see ValidData
	* @see Settings
	* @see IsLoaded
	*
	* @return Boolean If it's a valid option, this returns true. Otherwise false (including if the field hasn't been loaded correctly).
	*/
	function CheckData($data=array())
	{
		if (!$this->IsLoaded()) {
			return false;
		}

		if (!is_array($data)) {
			$data = array($data);
		}

		$validOptions = $this->Settings['Key'];
		foreach ($data as $option => $d) {
			if (in_array($d, $validOptions)) {
				return true;
			}
		}
		return false;
	}

	/**
	* DisplayFieldOptions
	* This displays options for the custom field if it is loaded. This will display a list of radio buttons to choose from.
	*
	* @param Array $chosen A list of chosen items (keys not values).
	* @param Boolean $useoptions This isn't used in the function, needed as a parameter placeholder. Set to false.
	* @param Int $formid The form we are generating the content for. This is used to modify the form id's and javascript slightly to allow multiple forms to be on the same page.
	*
	* @see IsLoaded
	* @see Settings
	*
	* @return String Returns a blank string if the custom field hasn't been loaded. If it has, it will create a list of radio buttons with pre-selected items (if supplied).
	*/
	function DisplayFieldOptions($chosen=array(), $useoptions=false, $formid=0)
	{
		if (!$chosen) {
			$chosen = array();
		}

		if (!$this->IsLoaded()) {
			return '';
		}

		$formid = (int)$formid;

		$return_string = '';

		foreach ($this->Settings['Key'] as $pos => $key) {
			$selected = '';
			if (in_array($key, $chosen)) {
				$selected = ' CHECKED';
			}

			$label_id = 'CustomFields[' . $this->fieldid;
			if ($formid > 0) {
				$label_id .= '_' . $formid;
			}
			$label_id .= ']_'.$key;

			$return_string .= '<label for="'.$label_id.'"><input type="radio" name="CustomFields[' . $this->fieldid . ']" id="'.$label_id.'" value="' . $key . '"' . $selected . '>' . $this->Settings['Value'][$pos] . '</label>';

			$return_string .= '<br/>';
		}
		return $return_string;
	}

	/**
	* GetRealValue
	* This gets the 'real' value from the custom field. This is used when sending the list owner a notification of a subscription.
	* This will return the 'value' passed in for a particular key.
	*
	* <b>Example</b>
	* <code>
	* $this->Settings['Key']['m'] = 'Male';
	* $this->Settings['Key']['f'] = 'Female';
	* $value = 'm';
	* </code>
	* This will return 'Male'.
	*
	* @param String $value The value to check against the Settings list. This checks against the settings key.
	*
	* @see Settings
	*
	* @return String Returns the real value based on the key passed in.
	*/
	function GetRealValue($value='')
	{
		$return_value = '';
		foreach ($this->Settings['Key'] as $pos => $key) {
			if ($key == $value) {
				$return_value = $this->Settings['Value'][$pos];
			}
		}
		return $return_value;
	}

	/**
	* CreateJavascript
	* Creates a javascript check for this field for a form to use.
	* It uses the id of the field to check for a value.
	* If the field isn't loaded, then this returns nothing.
	* This makes sure one of the radio buttons has been selected.
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

		$alert = sprintf(GetLang('Form_Javascript_Field_Choose'), $this->GetFieldName());

		$javascript = '
			var fldcheck = CheckMultiple' . $formid . '(f, "CustomFields[' . $this->fieldid;
			if ($formid > 0) {
				$javascript .= '_' . $formid;
			}
			$javascript .= ']");
			if (!fldcheck) {
				alert("' . $alert . '");
				return false;
			}
		';
		return $javascript;
	}

}

?>
