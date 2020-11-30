<?php
/**
 * Addons_surveys_widgets_api
 * API functions for surveys
 *
 * By Yudi
 */

class Addons_survey_widgets_api extends API
{

 	const BASE_TABLE_NAME = 'surveys_widget';

 	/**
	 * Holds values set using __set
	 * @var Array
	 */
	private $data = array();

	protected $_columns = array(
			'id',
			'surveys_id',
			'name',
			'description',
			'type',
			'is_required',
			'is_random',
			'is_visible',
			'allowed_file_types',
			'display_order'
		);

	protected $_fieldscolumns = array(
				'id',
				'surveys_widget_id',
				'value',
				'is_selected',
				'is_other',
				'other_label_text',
				'display_order'
	);

	/*
	 * __construct
	 * Sets the question_types, loads the specified surveys and questions if given.
	 *
	 * @param Array $survey The survey to load
	 * @param Array $questions The questions to load
	 *
	 * @see _loadData
	 *
	 * @return Void Returns nothing
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * populateFormData
	 * @param $widgets the widgets data type
	 *
	 * Populating all the data variable with the form.
	 */
	public function populateFormData($widgets)
	{
		$this->widgets_data = $widgets;
		if (is_array($widgets)) {
			foreach ($widgets as $key => $val) {
				if (in_array($key, $this->_columns)){
					$this->$key = $val;
				}
			}
		}
	}

	/**
	 *  ----------- DEPRECEATED --------------------
	 *  DO NOT USE
	 * Load all the widget details..
	 * Enter description here...
	 * @return unknown_type
	 */

	public function Load($widgetId)
	{
		$surveyid = (int)$widgetId;
		$prefix = $this->Db->TablePrefix;
		$query = "SELECT * FROM {$prefix}surveys_widgets WHERE id = $widgetId";

		$result = $this->Db->Query($query);
		$widget = $this->Db->Fetch($result);

		return $this->populateFormData($widget);
	}


	/**
	 * Retrieves all fields associated to the current widget instance.
	 *
	 * @return Void
	 */
	public function getFields($includeOther = false)
	{
		$fields = array();
		$prefix = $this->Db->TablePrefix;

		if ($includeOther) {
			$includeOtherSql = '';
		} else {
			$includeOtherSql = 'AND is_other != 1';
		}

		$orderSql = 'ORDER BY display_order ASC';

		$sql = "SELECT *
				FROM  {$prefix}surveys_fields
				WHERE
					 surveys_widget_id = $this->id {$includeOtherSql} {$orderSql}";



		$results = $this->Db->Query($sql);

		if ($results === false || empty($results)) {
			return false;
		}

		while ($row = $this->Db->Fetch($results)) {
			$fields[] = $row;
		}

		return $fields;
	}

	/**
	 * Returns whether the current widget has an "other" field.
	 *
	 * @return bool
	 */
	public function getOtherField($includeOther = false)
	{
		$otherfields = array();
		$prefix = $this->Db->TablePrefix;

		$sql = "SELECT *
				FROM  {$prefix}surveys_fields
				WHERE
					 surveys_widget_id =  $this->id AND
					 is_other = 1";

		$results = $this->Db->Query($sql);

		if ($results === false || empty($results)) {
			return false;
		}

		while ($row = $this->Db->Fetch($results)) {
			$otherfields[] = $row;
		}

		if (empty($otherfields)) {
			return false;
		}

		return $otherfields[0];
	}


	/**
	protected $_fieldscolumns = array(
				'surveys_widget_id',
				'value',
				'is_selected',
				'is_other',
				'other_label_text',
				'display_order'
	);
	 */

	public function saveFields($widgetId, $field)
	{
		$fieldId = intval(@$field['id']);

		if ($fieldId > 0 ) {
			$this->updateField($field);
		} else {
			$fieldId = $this->insertField($widgetId, $field);
		}

		return $fieldId;
	}

	/**
	 * Insert all the fields related..
	 */


	/**
	 * updateField
	 *
	 * @param $field array, which is the array from the form.
	 * the form will get the ID tag form the fields, which means
	 * the Data already exist.. and wil perform the update operation
	 *
	 * @return Return the fieldID that is updated
	 *
	 */
	public function updateField($field)
	{
		$prefix = $this->Db->TablePrefix;
		//$widgets_data = $this->data;

		if (empty($field['id'])) {
			return false;
		}

		$fieldId = $field['id'];
		$where = 'id = ' . $fieldId;

		if (isset($field['id'])) {
			unset($field['id']);
		}
		// Do the actual update query..
		$this->Db->UpdateQuery('surveys_fields', $field , $where);

		return $fieldId;
	}

	/**
	 *
	 */

	public function insertField($widgetId, $field)
	{
		$prefix = $this->Db->TablePrefix;
		// for saving need to remove the ID from the fieldscolumns
		$fieldscolumns = $this->_fieldscolumns;
		array_shift($fieldscolumns);

		$tablefields =  implode(',', $fieldscolumns);
		$query = "INSERT INTO {$prefix}surveys_fields ({$tablefields})
				VALUES (" . $widgetId  . ",'"
						 . $this->Db->Quote(@$field['value']) . "','"
						 . $this->Db->Quote(@$field['is_selected']) . "',"
						 . intval(@$field['is_other']) . ",'"
						 . $this->Db->Quote(@$field['other_label_text']) . "','"
						 . $this->Db->Quote(@$field['display_order']) . "')";


		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query .= ' RETURNING id;';
		}

		$results = $this->Db->Query($query);

		if ($results === false) {
			return false;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$fieldid = $this->Db->FetchOne($results);
		} else {
			$fieldid = $this->Db->LastId();
		}

		return $fieldid;
	}



	public function deleteFieldsNotIn(Array $fieldIds)
	{
		$prefix = $this->Db->TablePrefix;
		$query = "DELETE FROM {$prefix}surveys_fields
				WHERE surveys_widget_id  = {$this->GetId()} AND
				id NOT IN (" . implode(',', $fieldIds) . ");";


		$this->Db->query($query);
	}

	/**
	 * __set
	 * Handles setting of members
	 */
	public function __set($var,$val)
	{
		$this->data[$var] = $val;
	}

	/**
	 * __get
	 * Returns members
	 */
	public function __get($var)
	{
		if (array_key_exists($var,$this->data)) {
			return $this->data[$var];
		}
		return false;
	}

	/**
	 * SaveWidgets
	 * Functions to save all the widget type into the DB with their corresponding Survey ID
	 * To keep track all the widgets in the survey..
	 *
	 * Enter description here...
	 * @return unknown_type
	 */
	public function saveWidget($surveyId)
	{
		//if exist then update, if not then insert
		$this->surveys_id = $surveyId;

		if ($this->ValidId()) {
			$this->updateWidget($this->GetId());
			$widgetId = $this->GetId();
		} else {
			$widgetId = $this->insertWidget();
		}

		return $widgetId;
	}



	/**
	 * GetId
	 * This returns the integer current primary key value.
	 *
	 * @return integer The id number or primary key value of the data currently stored
	**/
	public function GetId()
	{
		return (int)$this->id;
	}

	/**
	 * SetId
	 *
	 *
	 * @return
	 **/
	public function SetId($id)
	{
		 $this->id = intval($id);
	}

	/**
	 * getResponseValeus
	 * This method will returns all responses value associated with specific response..
	 *
	 * @params responseId
	 * @return
	 **/
	public function getResponseValues($responseId)
	{
		$query = "SELECT *
				FROM {$this->Db->TablePrefix}surveys_response_value
				WHERE
					surveys_response_id = " . $responseId . " AND surveys_widgets_id = ". $this->id;

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}

		// get a list of responses
		return $return;
	}

	/**
	 * getResponseValuesByType
	 *
	 * @params start the offset start
	 * @params isOther check is this for an isOther field or not
	 * @return array of reponse value
	 *
	 */
	public function getResponseValuesByType($start, $isOther = false)
	{
		$isOtherSelect = "";
		$isOtherFrom = "";
		$isOtherWhere = "";
		$limit = 10;

		// If it is isOther...
		// We need to involve the widget From..
		if (!empty($isOther)) {
			$isOtherFrom = ", {$this->Db->TablePrefix}surveys_fields as sf ";
			$isOtherWhere = " sf.surveys_widget_id  = sw.id AND sf.is_other = 1 AND srv.is_othervalue = 1 AND ";
		}

		$query = "SELECT srv.value, sr.id,  ( SELECT COUNT(id) AS count
												FROM {$this->Db->TablePrefix}surveys_response
													WHERE
													surveys_id = {$this->surveys_id} AND
													id <= sr.id
													)  as response_number

				FROM {$this->Db->TablePrefix}surveys_response as sr,
					 {$this->Db->TablePrefix}surveys_response_value as srv,
					 {$this->Db->TablePrefix}surveys_widgets as sw
					 {$isOtherFrom}
				WHERE
					sw.id = srv.surveys_widgets_id AND
					srv.surveys_response_id = sr.id AND
					{$isOtherWhere}
					srv.surveys_widgets_id = '{$this->id}' AND
					sr.surveys_id = '{$this->surveys_id}' AND
					sw.type = '{$this->type}'
				ORDER BY
					srv.id LIMIT {$start}, {$limit}";

		$start++;
		$index = $start;



		$result = $this->Db->Query($query);
		$results = array();


		while ($row = $this->Db->Fetch($result)) {
			$results[$index] = $row;
			$index++;
		}

		return $results;
	}


	public function getResponsesCount($isOther=false)
	{

		$otherWhere = "";
		if ($isOther === true) {
			$otherWhere = " srv.is_othervalue = 1 AND ";
		}

		$prefix = $this->Db->TablePrefix;
		$query = "
				SELECT count(srv.id) as totalother
				FROM  {$prefix}surveys_response as sr, {$prefix}surveys_response_value as srv
				WHERE
					sr.id = srv.surveys_response_id AND
					{$otherWhere}
					srv.surveys_widgets_id = {$this->id} AND
					sr.surveys_id = {$this->surveys_id}
				;";

		$result = $this->Db->Query($query);
		$total = $this->Db->Fetch($result);
		$count = $total["totalother"];

		return $count;
	}



	/**
	 * ValidId
	 *
	 * This checks to see if the current Id is value. If its not a positive integer above zero, return false.
	 *
	 * @return boolean Whether or not the id number is a positive integer above zero
	**/
	public function ValidId()
	{
		$id = (int)$this->GetId();
		if ($id > 0) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * InsertWidget
	 *
	 * @param void
	 *
	 * This will inserts a widget into the table..
	 *
	 * @return The actual survey ID or false
	 */

	public function insertWidget()
	{
		$prefix = $this->Db->TablePrefix;

		// for saving need to remove the ID from the fieldscolumns
		$columns = $this->_columns;
		array_shift($columns);

		$tablefields = implode(',', $columns);

		/**
		 * Inserting query
		 */
		// $this->Db->InsertQuery(BASE_TABLE_NAME, $values)

		$query = "INSERT INTO {$prefix}surveys_widgets ({$tablefields})
				  VALUES (" . $this->surveys_id  . ",'"
							 . $this->Db->Quote($this->name) . "','"
							 . $this->Db->Quote($this->description) . "','"
							 . $this->Db->Quote($this->type) . "','"
							 . $this->Db->Quote($this->is_required) . "','"
							 . $this->Db->Quote($this->is_random) . "','"
							 . $this->Db->Quote($this->is_visible) . "','"
							 . $this->Db->Quote($this->allowed_file_types) . "','"
							 . $this->Db->Quote($this->display_order) . "')";

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query .= ' RETURNING id;';
		}

		$results = $this->Db->Query($query);

		if ($results === false) {
			return false;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$widgetid = $this->Db->FetchOne($results);
		} else {
			$widgetid = $this->Db->LastId();
		}

		return $widgetid;
	}

	/***
	 *
	 *
	 */
	public function updateWidget($id)
	{
		$prefix = $this->Db->TablePrefix;

		$widgets_data = $this->data;

		if (isset($widgets_data['widgets_data'])) {
			unset($widgets_data['widgets_data']);
		}

		$where = 'id = ' . $widgets_data['id'];

		if (isset($widgets_data['id'])) {
			unset($widgets_data['id']);
		}

		// Do the actual update query..
		$this->Db->UpdateQuery('surveys_widgets', $widgets_data, $where);

	}

}