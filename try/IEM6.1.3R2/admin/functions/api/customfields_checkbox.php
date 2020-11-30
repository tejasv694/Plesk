<?php
/**
* The Custom Fields Checkbox API. This only handles the checkbox type of custom field.
*
* @version     $Id: customfields_checkbox.php,v 1.20 2007/05/15 07:03:56 rodney Exp $
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
* This class handles validation and checking of checkbox custom fields.
*
* @package API
* @subpackage CustomFields_API
*/
class CustomFields_Checkbox_API extends CustomFields_API
{

	/**
	* This overrides the parent classes setting so we know what sort it is.
	*
	* @see CustomFields_API::fieldtype
	*
	* @var String
	*/
	var $fieldtype = 'checkbox';

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
	* Unsets the default value for checkboxes and then calls the parent object's constructor.
	* Checkboxes don't need instructions/default values.
	*
	* @param Int $fieldid The field to load up. This is passed to the parent constructor for processing.
	* @param Boolean $connect_to_db Whether to connect to the database or not. If this is set to false, you need to set the database up yourself.
	*
	* @see CustomFields_API::CustomFields_API
	* @see Db
	*
	* @return Boolean Returns the parent's constructor.
	*/
	function CustomFields_Checkbox_API($fieldid=0, $connect_to_db=true)
	{
		unset($this->SharedOptions['DefaultValue']);
		return $this->CustomFields_API($fieldid, $connect_to_db);
	}

	/**
	* CheckData
	* Checkdata makes sure the data passed in is valid for this field type.
	* If the field hasn't been loaded this will return false.
	*
	* @param Array $data A list of options to check to see whether they are valid or not.
	*
	* @see Settings
	* @see ValidData
	* @see IsLoaded
	*
	* @return Boolean If it's a valid option, this returns true. Otherwise false (including if the field hasn't been loaded correctly).
	*/
	function CheckData($data=array(), $return_keys=false)
	{
		if (!$this->IsLoaded()) {
			return false;
		}

		if (!is_array($data)) {
			$data = array($data);
		}

		if ($return_keys) {
			$return_values = array();
			foreach ($data as $d => $option) {
				// see if the data contains the 'value' first.
				// eg 'male'.
				$key = array_search($option, $this->Settings['Value']);
				if (!is_null($key) && $key !== false) {
					$return_values[] = $this->Settings['Key'][$key];
					continue;
				}
				// if the 'value' isn't found, check if we're trying to import the 'key' instead.
				// eg 'm' instead of 'male'.
				$key = array_search($option, $this->Settings['Key']);
				if (!is_null($key) && $key !== false) {
					$return_values[] = $this->Settings['Key'][$key];
					continue;
				}
			}
			return $return_values;
		}

		$validKeyOptions = $this->Settings['Key'];

		$returnvalue = true;
		foreach ($data as $d => $option) {
			if ($option == '') {
				continue;
			}
			if (!in_array($option, $validKeyOptions)) {
				$returnvalue = false;
			}
		}
		return $returnvalue;
	}

	/**
	* DisplayFieldOptions
	* This displays options for the custom field if it is loaded. Each type handles this differently.
	* It will return the options and the items (eg checkboxes).
	*
	* @param Array $chosen A list of chosen checkboxes (keys not values).
	* @param Boolean $useoptions This isn't used in the function, needed as a parameter placeholder. Set to false.
	* @param Int $formid The form we are generating the content for. This is used to modify the form id's and javascript slightly to allow multiple forms to be on the same page.
	*
	* @see IsLoaded
	* @see Settings
	*
	* @return String Returns a blank string if the custom field hasn't been loaded. If it has, it will create a list of checkboxes with pre-selected items (if supplied).
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

			$return_string .= '<label for="' . $label_id . '"><input type="checkbox" id="' . $label_id . '" name="CustomFields[' . $this->fieldid . '][]" value="' . htmlspecialchars($key, ENT_QUOTES, SENDSTUDIO_CHARSET) . '"' . $selected . '>' . htmlspecialchars($this->Settings['Value'][$pos], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</label>';

			$return_string .= '<br/>';
		}
		return $return_string;
	}

	/**
	* GetRealValue
	* This gets the 'real' value from the custom field. This is used when sending the list owner a notification of a subscription.
	*
	* @param Array $value The values that have been posted in a form are passed in here. This should be an array, if it's a string, it's assumed to be a serialized string of values to check.
        * @param String $imploder The character or characters to implode the return array by so this returns a string of keys back instead of an array of options. Defaults to a comma
	*
	* @see Settings
	*
	* @return String Returns the real value based on the custom field type.
	*/
	function GetRealValue($value='', $imploder=',')
	{
		if (!$value) {
			return false;
		}

		if (!is_array($value)) {
			$value = unserialize($value);
		}

		$return_value = array();
		foreach ($this->Settings['Key'] as $pos => $key) {
			if (in_array($key, $value)) {
				$return_value[] = $this->Settings['Value'][$pos];
			}
		}
		return implode($imploder, $return_value);
	}

	/**
	* CreateJavascript
	* Creates a javascript check for this field for a form to use.
	* It uses the id of the field to check for a value.
	* If the field isn't loaded, then this returns nothing.
	* This makes sure one of the checkboxes has been selected.
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

		$alert = sprintf(GetLang('Form_Javascript_Field_Choose_Multiple'), $this->GetFieldName());

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
