<?php
/**
* The Custom fields API.
*
* @version     $Id: customfields.php,v 1.34 2007/11/12 22:12:54 tye Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage CustomFields_API
*/


/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');


/**
* This will load a custom field, save a custom field, set details and get details.
* Some methods are overwritten in subclasses.
* This will find and load a subclass if it needs to.
*
* @package API
* @subpackage CustomFields_API
*/
class CustomFields_API extends API
{

	/**
	* The CustomField that is loaded. By default is 0 (no field).
	*
	* @var Int
	*/
	var $fieldid = 0;

	/**
	* The type of field this is.
	*
	* @var String
	*/
	var $fieldtype = '';

	/**
	* The userid of the owner of this custom field.
	*
	* @var Int
	*/
	var $ownerid = 0;

	/**
	* The timestamp of when the custom field was created (integer)
	*
	* @var Int
	*/
	var $createdate = 0;

	/**
	 * Whether this field is global (1) or per-user (0).
	 *
	 * @var Boolean
	 */
	var $isglobal = 0;

	/**
	* Default Order to show the fields in.
	*
	* @see GetLists
	*
	* @var String
	*/
	var $DefaultOrder = 'name';

	/**
	* Default direction to show the fields in.
	*
	* @see GetLists
	*
	* @var String
	*/
	var $DefaultDirection = 'up';

	/**
	* Options all custom fields share.
	*
	* @see Options
	* @see GetFieldOptions
	*
	* @var Array
	*/
	var $SharedOptions = array(
		'FieldName' => '',
		'DefaultValue' => '',
		'FieldRequired' => false
	);

	/**
	* @var Array
	* A list of options just for this custom field.
	*
	* @see SharedOptions
	* @see GetFieldOptions
	*
	*/
	var $Options = array();

	/**
	* Settings for the custom field - such as default value, field length etc.
	*
	* @see Create
	* @see Save
	* @see Load
	* @see Set
	*
	* @var Array
	*/
	var $Settings = array();

	/**
	* A list of assocations this custom field has with mailing lists.
	*
	* @see SetAssocations
	* @see Save
	* @see Load
	*
	* @var Array
	*/
	var $Associations = array();

	/**
	* An array of valid sorts that we can use here. This makes sure someone doesn't change the query to try and create an sql error.
	*
	* @see GetCustomFields
	*
	* @var Array
	*/
	var $ValidSorts = array('name' => 'Name', 'date' => 'CreateDate', 'type' => 'FieldType');

	/**
	* Constructor
	* Sets up the database object, loads the customfield if the ID passed in is not 0.
	*
	* @param Int $fieldid The fieldid of the custom field to load. If it is 0 then you get a base class only. Passing in a fieldid > 0 will load that field.
	* @param Boolean $connect_to_db Whether to connect to the database or not. If this is set to false, you need to set the database up yourself.
	*
	* @see GetDb
	* @see Load
	* @see Db
	*
	* @return Boolean If there is a field to load, returns the result of the load. If setting up the base class, always returns true.
	*/
	function CustomFields_API($fieldid=0, $connect_to_db=true)
	{
		if ($connect_to_db) {
			$this->GetDb();
		}

		if ($fieldid > 0) {
			return $this->Load($fieldid);
		}
		return true;
	}

	/**
	* Load
	* Loads up the customfield and sets the appropriate class variables. This handles loading of a subclass with different options and settings.
	*
	* @param Int $fieldid The fieldid to load up. If the field is not present then it will not load up.
	* @param Boolean $load_list_associations Whether to load up list associations or not. The default is to load up the associations. Only subscriber importing should need to skip loading associations.
	* @param Boolean $return_options Whether to return the information loaded from the database or not. The default is not to return the options, so this sets up the class variables instead. Only subscriber importing should need to return the options.
	*
	* @see fieldid
	* @see fieldtype
	* @see ownerid
	* @see createdate
	* @see Options
	* @see Settings
	* @see Associations
	* @see SetAllOptions
	* @see Subscribers_Import::ImportSubscriberLine
	*
	* @return Boolean Will return false if the fieldid is not present, or the field can't be found, otherwise it set the class vars, associations and options and return true.
	*/
	function Load($fieldid=0, $load_list_associations=true, $return_options=false)
	{
		$fieldid = (int)$fieldid;
		if ($fieldid <= 0) {
			return false;
		}

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "customfields WHERE fieldid='" . $this->Db->Quote($fieldid) . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$field = $this->Db->Fetch($result);
		if (empty($field)) {
			return false;
		}

		if ($return_options) {
			return $field;
		}

		$this->SetAllOptions($field);

		$assocs = array();
		if ($load_list_associations) {
			$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE fieldid='" . $this->Db->Quote($this->fieldid) . "'";
			$result = $this->Db->Query($query);
			while ($row = $this->Db->Fetch($result)) {
				$assocs[] = $row['listid'];
			}
		}
		$this->Associations = $assocs;

		return true;
	}

	/**
	* SetAllOptions
	* This sets up the class variables based on the field information passed in.
	* Load & LoadSubField call this to set up the necessary info (settings, fieldtype etc).
	*
	* @see Load
	* @see LoadSubField
	*
	* @return Void Doesn't return anything.
	*/
	function SetAllOptions($field=array())
	{
		if (!isset($field['fieldid'])) {
			return false;
		}

		$this->fieldid = $field['fieldid'];
		$this->fieldtype = $field['fieldtype'];
		$this->ownerid = $field['ownerid'];
		$this->createdate = $field['createdate'];
		$this->isglobal = $field['isglobal'];

		$fieldsettings = unserialize($field['fieldsettings']);

		$settings = array();
		$settings['FieldName'] = $field['name'];
		$settings['FieldRequired'] = ($field['required'] == 1) ? true : false;
		$settings['DefaultValue'] = $field['defaultvalue'];

		$options = $this->Options;
		foreach ($options as $name => $val) {
			if (isset($fieldsettings[$name])) {
				$settings[$name] = $fieldsettings[$name];
			}
		}
		$this->Settings = $settings;
	}

	/**
	* LoadSubField
	* Loads up a sub custom field based on the field type loaded and the fieldid loaded.
	* If this custom field hasn't loaded or the fieldtype is blank, then nothing can be loaded.
	*
	* @param Array $field_options An array of options that have already been loaded. This will include all info about the custom field (apart from list associations). This is used by the import script so it can load the info once and set everything up properly to call CheckData. We only do a basic check to see if the fieldid & fieldtype are set.
	*
	* @see SetAllOptions
	* @see Load
	*
	* @return Mixed Returns the sub class if a field is loaded, otherwise false.
	*/
	function LoadSubField($field_options=array())
	{
		if (empty($field_options) || !isset($field_options['fieldid'])) {
			if ($this->fieldid <= 0 || $this->fieldtype == '') {
				return false;
			}
			$fieldtype = $this->fieldtype;
		} else {
			if ((int)$field_options['fieldid'] <= 0 || !$field_options['fieldtype']) {
				return false;
			}
			$fieldtype= $field_options['fieldtype'];
		}

		require_once(dirname(__FILE__) . '/customfields_' . strtolower($fieldtype) . '.php');
		$classname = 'CustomFields_' . ucwords($fieldtype) . '_API';
		$subApi = new $classname(0, false);
		$subApi->Db = &$this->Db;
		if (empty($field_options)) {
			$subApi->Load($this->fieldid);
		} else {
			$subApi->SetAllOptions($field_options);
		}
		return $subApi;
	}

	/**
	* Create
	* This function creates a custom field based on the current class vars. This includes all options and settings. They are serialized up before going into the database.
	*
	* @see Settings
	* @see Options
	*
	* @return Mixed Returns the new fieldid if everything was created ok, false if it fails.
	*/
	function Create()
	{
		$required = (strtolower($this->Settings['FieldRequired']) == 'on') ? 1 : 0;

		$settings = array();
		$options = $this->Options;
		foreach ($options as $name => $val) {
			$settings[$name] = $this->Settings[$name];
		}

		$default_value = '';
		if (isset($this->Settings['DefaultValue'])) {
			$default_value = $this->Settings['DefaultValue'];
		}

		$settings = serialize($settings);

		$createdate = $this->GetServerTime();
		if ((int)$this->createdate > 0) {
			$createdate = (int)$this->createdate;
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "customfields (fieldtype, name, defaultvalue, required, fieldsettings, createdate, ownerid, isglobal) VALUES ('" . $this->fieldtype . "', '" . $this->Db->Quote($this->Settings['FieldName']) . "', '" . $this->Db->Quote($default_value) . "', '" . $required . "', '" . $this->Db->Quote($settings) . "', '" . $this->Db->Quote($createdate) . "', '" . $this->Db->Quote($this->ownerid) . "', '" . $this->Db->Quote($this->isglobal) . "')";
		$result = $this->Db->Query($query);

		if ($result) {
			$fieldid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'customfields_sequence');

			$this->fieldid = $fieldid;
			return $fieldid;
		}
		return false;
	}

	/**
	* Delete
	* Delete a custom field from the database
	*
	* @param Int $fieldid Id of the field to delete. If not passed in, it will delete 'this' list. First we delete the field (and check the result for that), then we delete the subscribers info for the field, and finally reset all class vars.
	*
	* @return Boolean True if it deleted the field, false otherwise.
	*
	*/
	function Delete($fieldid=0)
	{
		$fieldid = (int)$fieldid;
		if ($fieldid <= 0) {
			$fieldid = $this->fieldid;
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "customfields WHERE fieldid='" . $fieldid . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "subscribers_data WHERE fieldid='" . $fieldid . "'";
		$result = $this->Db->Query($query);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "form_customfields WHERE fieldid='" . $fieldid . "'";
		$result = $this->Db->Query($query);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE fieldid='" . $fieldid . "'";
		$result = $this->Db->Query($query);

		$this->fieldid = 0;
		$this->fieldtype = '';
		$this->ownerid = 0;
		$this->createdate = 0;
		$this->Settings = array();
		return true;
	}

	/**
	* Save
	* This function saves the current class vars to the custom field. This includes all options and settings. They are serialized up before saving them in the database.
	* If a field hasn't been loaded properly then this returns false.
	*
	* @see Settings
	* @see Options
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Save()
	{
		if ((int)$this->fieldid <= 0) {
			return false;
		}

		$required = (strtolower($this->Settings['FieldRequired']) == 'on') ? 1 : 0;

		$settings = array();
		$options = $this->Options;
		foreach ($options as $name => $val) {
			$settings[$name] = $this->Settings[$name];
		}

		$settings = serialize($settings);

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "customfields SET name='" . $this->Db->Quote($this->Settings['FieldName']) . "', defaultvalue='" . $this->Db->Quote($this->Settings['DefaultValue']) . "', required='" . $this->Db->Quote($required) . "', fieldsettings='" . $this->Db->Quote($settings) . "', isglobal='" . $this->Db->Quote($this->isglobal) . "' WHERE fieldid='" . (int)$this->fieldid . "'";

		$result = $this->Db->Query($query);
		if ($result) {
			return true;
		}

		return false;
	}

	/**
	* IsGlobal
	* Returns whether the custom field is global or not. A global custom field is one that any user can use.
	*
	* @return Boolean True if the field is global, otherwise false.
	*/
	function IsGlobal()
	{
		return $this->isglobal;
	}

	/**
	* GetCustomFields
	* Gets custom field info based on the info passed in. This includes fieldid, name, fieldtype, defaultvalue, required status, other settings, when it was created, who created the field and whether it's global or not.
	*
	* @param Int $ownerid If specified, will only fetch custom fields owned by a particular user.
	* @param Array $sortinfo An array (sortby, direction) containing the sort details you want to use. If not specified, uses $this->DefaultOrder and $this->DefaultDirection.
	* @param Boolean $countonly Whether to only return a count of custom fields. Also uses ownerid if specified.
	* @param Int $start Where to start in the list. Used with perpage, this controls paging.
	* @param Int|String $perpage How many records to return. Used with start, this controls paging.
	*
	* @see DefaultOrder
	* @see DefaultDirection
	*
	* @return Mixed Returns either an array of fields to use, or a count if specified.
	*/
	function GetCustomFields($ownerid=0, $sortinfo=array(), $countonly=false, $start=0, $perpage=10)
	{
		$ownerid = (int)$ownerid;
		$start = (int)$start;

		if ($countonly) {
			$query = "SELECT COUNT(fieldid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "customfields";
			if ($ownerid) {
				$query .= " WHERE ownerid='" . $ownerid . "' OR isglobal='1'";
			}

			$result = $this->Db->Query($query);
			if (!$result) {
				return false;
			}

			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "customfields";
		if ($ownerid) {
			$query .= " WHERE ownerid='" . $ownerid . "' OR isglobal='1'";
		}

		$order = (isset($sortinfo['SortBy']) && !is_null($sortinfo['SortBy'])) ? strtolower($sortinfo['SortBy']) : $this->DefaultOrder;

		$order = (in_array($order, array_keys($this->ValidSorts))) ? $this->ValidSorts[$order] : $this->DefaultOrder;

		$direction = (isset($sortinfo['Direction']) && !is_null($sortinfo['Direction'])) ? $sortinfo['Direction'] : $this->DefaultDirection;

		$direction = (strtolower($direction) == 'up' || strtolower($direction) == 'asc') ? 'ASC' : 'DESC';

		$query .= " ORDER BY " . strtolower($order) . " " . $direction;

		if (isset($sortinfo['Secondary']) && $sortinfo['Secondary'] && $sortinfo['SecondaryDirection']) {
			if (in_array($sortinfo['Secondary'], array_keys($this->ValidSorts))) {
				$query .= ", " . $this->ValidSorts[$sortinfo['Secondary']] . " " . $sortinfo['SecondaryDirection'];
			}
		}

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$fields = array();
		while ($row = $this->Db->Fetch($result)) {
			$fields[$row['fieldid']] = $row;
		}
		return $fields;
	}

	/**
	* GetOptions
	* Gets all options (shared and specific custom field options) and returns an array.
	*
	* @see SharedOptions
	* @see Options
	*
	* @return Array List of options.
	*/
	function GetOptions()
	{
		$AllOptions = array_merge($this->SharedOptions, $this->Options);
		return $AllOptions;
	}

	/**
	* Set
	* Sets the current settings to the values passed in based on which options this particular custom field has.
	*
	* @param Array $newvalues List of new values to set the current settings to.
	*
	* @see GetOptions
	* @see Settings
	*
	* @return Void Doesn't return anything.
	*/
	function Set($newvalues=array())
	{
		$myoptions = $this->GetOptions();

		foreach ($myoptions as $name => $val) {
			$newval = $newvalues[$name];
			if (is_array($newval)) {
				$checkvals = array();
				foreach ($newval as $k => $v) {
					if ($v != '') {
						$checkvals[] = $v;
					}
				}
				$newval = $checkvals;
			}
			$this->Settings[$name] = $newval;
		}
	}

	/**
	* SetAssociations
	* Sets list assocations to the list passed in.
	* If the field hasn't been loaded properly then this will return false.
	* If you don't specify the user to check permissions for, this will return false.
	*
	* @param Array $associations List of assocations to match up to.
	* @param Object $user_object The user object is passed in to see which lists the user has access to. Since the user object will already be loaded, then we don't have to do anything special with it.
	*
	* @see User_API
	* @see User_API::GetLists
	*
	* @return Boolean Returns true if it worked ok, false otherwise.
	*/
	function SetAssociations($associations = array(), &$user_object)
	{
		
		if ($this->fieldid <= 0) {
			return false;
		}

		$userlists = $user_object->GetLists();

		$addassocs = array();
		//these lists only need the default value updated -- assocs are already set
		$def_only = array();
        //---
		$removeassocs = array('0');

		$assocs = $associations;
		foreach ($userlists as $listid => $listname) {
			// if it's in the list of new assocations, AND in the list of current assocations, keep going - nothing to do.
			if (in_array($listid, $assocs) && in_array($listid, $this->Associations)) {
				if ($this->Settings['ApplyDefault']=='on' && !empty($this->Settings['DefaultValue'])) {
					$def_only[] = $listid;
				}
				continue;
			}

			// if it's in the list of new associations, but not in the list of the current assocations, add it.
			if (in_array($listid, $assocs) && !in_array($listid, $this->Associations)) {
				$addassocs[] = $listid;
			}

			// if it's in the list of the current assocations, but not in the passed in list, remove it.
			if (in_array($listid, $this->Associations) && !in_array($listid, $assocs)) {
				$removeassocs[] = $listid;
			}
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE fieldid='" . $this->Db->Quote($this->fieldid) . "' AND listid IN (" . implode(',', $removeassocs) . ")";
		$result = $this->Db->Query($query);
		if (!$result) {
			trigger_error(mysql_error());
		}
		if(!empty($def_only)){
            foreach($def_only as $p => $listid){
    			$query = "SELECT subscriberid FROM [|PREFIX|]list_subscribers WHERE listid = {$listid}";
    			$result = $this->Db->Query($query);
    			if (!$result) {trigger_error($this->Db->Error());continue;}
                $subid =  $this->Db->FetchOne($result);
				$query = "INSERT INTO [|PREFIX|]subscribers_data (subscriberid, fieldid, data)VALUES({$subid},{$this->fieldid},'".$this->Db->Quote($this->Settings['DefaultValue'])."')";
				$this->Db->Query($query);     
            }
		}
        if(!empty($addassocs)){
    		foreach ($addassocs as $p => $listid) {
    			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "customfield_lists (fieldid, listid) VALUES ('" . $this->Db->Quote($this->fieldid) . "', '" . $this->Db->Quote($listid) . "')";
    			$result = $this->Db->Query($query);
    			if (!$result) {
    				trigger_error($this->Db->Error());
    				return false;
    			}
    			if ($this->Settings['ApplyDefault']=='on' && !empty($this->Settings['DefaultValue'])) {			 
        			$query = "SELECT subscriberid FROM [|PREFIX|]list_subscribers WHERE listid = {$listid}";
        			$result = $this->Db->Query($query);
        			if (!$result) {trigger_error($this->Db->Error());continue;}
                    $subid =  $this->Db->FetchOne($result);
    				$query = "INSERT INTO [|PREFIX|]subscribers_data (subscriberid, fieldid, data)VALUES({$subid},{$this->fieldid},'".$this->Db->Quote($this->Settings['DefaultValue'])."')";
    				$this->Db->Query($query);	
    			}
    		}
        }
		return true;
	}

	/**
	* IsLoaded
	* Returns whether a field has been loaded in this class or not. This is used by the subclasses before checking data to make sure it fits that particular fields requirements. For example, it's a valid (dropdown) option, or it's valid date.
	*
	* @see CheckData
	* @see DisplayFieldOptions
	*
	* @see CustomFields_Checkbox_API::CheckData
	* @see CustomFields_Date_API::CheckData
	* @see CustomFields_Dropdown_API::CheckData
	* @see CustomFields_Number_API::CheckData
	* @see CustomFields_Radiobutton_API::CheckData
	* @see CustomFields_Text_API::CheckData
	*
	* @see CustomFields_Checkbox_API::DisplayFieldOptions
	* @see CustomFields_Date_API::DisplayFieldOptions
	* @see CustomFields_Dropdown_API::DisplayFieldOptions
	* @see CustomFields_Number_API::DisplayFieldOptions
	* @see CustomFields_Radiobutton_API::DisplayFieldOptions
	* @see CustomFields_Text_API::DisplayFieldOptions
	*
	* @see fieldid
	*
	* @return Boolean Returns true if this custom field is already loaded, otherwise false.
	*/
	function IsLoaded()
	{
		if ($this->fieldid > 0) {
			return true;
		}

		return false;
	}

	/**
	* IsRequired
	* Returns whether this particular custom field is required or not.
	*
	* @see Settings
	*
	* @return Boolean Whether the field is required or not.
	*/
	function IsRequired()
	{
		return $this->Settings['FieldRequired'];
	}

	/**
	* GetDefaultValue
	* Returns the default value of this custom field.
	*
	* @see Settings
	*
	* @return String The default value of the field.
	*/
	function GetDefaultValue()
	{
		return $this->Settings['DefaultValue'];
	}

	/**
	* GetFieldName
	* Returns the fieldname of this custom field.
	*
	* @see Settings
	*
	* @return String The name of the field.
	*/
	function GetFieldName()
	{
		return $this->Settings['FieldName'];
	}

	/**
	* CheckData
	* Checkdata makes sure the data passed in is valid for this field type.
	* The base class always returns false. This needs to be overridden in each subclass.
	*
	* @see ValidData
	*
	* @return False The base class always returns false. The subclasses need to override this method and check the data properly.
	*/
	function CheckData()
	{
		return false;
	}

	/**
	* ValidData
	* This checks whether the data passed in is valid according to the custom field.
	* It checks to see whether the field is empty first off, then checks the appropriate subclass to use the CheckData function to more thoroughly check data.
	* If the field is marked as required and the data passed in is empty, then this will immediately return false without checking the subclass.
	*
	* @param Mixed $data The data to check. This could be a string, an integer, a float or anything else. The subclass takes this and processes it.
	*
	* @see IsRequired
	* @see CheckData
	* @see fieldtype
	* @see Load
	*
	* @see CustomFields_Checkbox_API::CheckData
	* @see CustomFields_Date_API::CheckData
	* @see CustomFields_Dropdown_API::CheckData
	* @see CustomFields_Number_API::CheckData
	* @see CustomFields_Radiobutton_API::CheckData
	* @see CustomFields_Text_API::CheckData
	*
	* @return Boolean Returns true if the data is valid based on subclass checks. If it's not valid it returns false.
	*/
	function ValidData($data='', $subfield_object=null)
	{
		if ($this->IsRequired() && $data == '') {
			return false;
		}

		$cleanup = false;
		if (is_null($subfield_object) || !is_object($subfield_object)) {
			$subfield_object = $this->LoadSubField();
			$cleanup = true;
		}

		$check = $subfield_object->CheckData($data);
		if ($cleanup) {
			unset($subfield_object);
		}
		return $check;
	}

	/**
	* DisplayFieldOptions
	* This displays options for the custom field if it is loaded. Each type handles this differently.
	* The base class returns a blank string.
	*
	* @return String Returns a blank string. Subclasses override this method and return proper options.
	*/
	function DisplayFieldOptions()
	{
		return '';
	}

        /**
	* GetRealValue
	* This gets the 'real' value from the custom field. This is used when sending the list owner a notification of a subscription.
	* The base class returns the value passed in as it is. Subclasses override this method to provide other checks.
	*
	* @return String Returns the real value based on the custom field type.
	*/
	function GetRealValue($value='')
	{
		return $value;
	}

	/**
	* CreateJavascript
	* Creates a javascript check for this field for a form to use.
	* Base class always returns an empty string. Each custom field type will need to create it's own check.
	*
	* @see IsLoaded
	* @see Forms_API::GetHTML
	*
	* @return String Returns an empty string.
	*/
	function CreateJavascript()
	{
		return '';
	}

	/**
	 * GetCustomFieldsForLists
	 * Gets all custom fields and their settings for the list id's passed in and field id's (if available).
	 * This is mainly used by 'Manage Subscribers' so instead of loading fields one by one, we load them all at once to save round trips to the database
	 *
	 * @param Array $listids The listid's to get custom fields for. This field is mandatory as it links custom fields and their lists. If you pass in a single list id, then it's turned into an array. Non-numeric list id's are removed automatically.
	 * @param Array $fieldids The fields to get the data and settings for. This field is not mandatory but can be used to narrow down the query. This can either be an array or single field id.
	 * @param Array $fieldTypes Field types to look for (Empty array means all type)
	 *
	 * @return Returns an array of custom field settings (name, fieldsettings, defaultvalue etc).
	 */
	function GetCustomFieldsForLists($listids = array(), $fieldids = array(), $fieldTypes = array())
	{
		if (!is_array($listids)) {
			$listids = array($listids);
		}

		$listids = $this->CheckIntVars($listids);
		if (empty($listids)) {
			return array();
		}

		if (!empty($fieldids)) {
			$fieldids = $this->CheckIntVars($fieldids);
		}

		if (!is_array($fieldTypes)) {
			$fieldTypes = array($fieldTypes);
		}

		$query = "SELECT cf.*, cl.listid FROM " . SENDSTUDIO_TABLEPREFIX . "customfields cf INNER JOIN " . SENDSTUDIO_TABLEPREFIX . "customfield_lists cl ON cf.fieldid = cl.fieldid WHERE cl.listid IN (" . implode(',', $listids) . ")";
		if (!empty($fieldids)) {
			$query .= " AND cf.fieldid IN (" . implode(',', $fieldids) . ")";
		}
		if (!empty($fieldTypes)) {
			$query .= " AND cf.fieldtype IN ('" . implode("','", $fieldTypes) . "')";
		}
		$query .= ' ORDER BY cl.listid';

		$result = $this->Db->Query($query);
		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		$this->Db->FreeResult($result);
		return $return;
	}
}

?>
