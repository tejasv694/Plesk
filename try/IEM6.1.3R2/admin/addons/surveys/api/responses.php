<?php

class Addons_survey_responses_api extends API
{
 	/**
	 * Holds values set using __set
	 * @var Array
	 */
	private $data = array();

	const RESPONSE_TABLE_NAME = 'surveys_response';

	const RESPONSEVALUE_TABLE_NAME = 'surveys_response_value';

	public function __construct() {
		parent::__construct();
	}

	protected $_columns = array(
			'id',
			'surveys_id',
			'datetime'
		);

	protected $_valuecolumns = array(
				'id',
				'surveys_response_id',
				'surveys_widgets_id',
				'value'
	);

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
	 * SetId
	 * Set the actual ID for the response.
	 *
	 * @return
	 **/
	public function SetId($id){
		 $this->id = intval($id);
	}

	/**
	 * GetId
	 * Set the actual ID for the response.
	 *
	 * @return
	 **/
	public function GetId(){
		return $this->id;
	}

	/**
	 * getValues
	 * Retrieves values for a given field for this response.
	 *
	 * @param int $widgetId - The id of the widget to retrieve the values for.
	 * @return mixed - Returns false if a value is not found otherwise the value is returned.
	 */
	public function getValues($widgetId)
	{
		$query = "SELECT *
				FROM {$this->Db->TablePrefix}" . self::RESPONSEVALUE_TABLE_NAME . "
				WHERE
					surveys_response_id = " . $this->id . " AND surveys_widgets_id = ". $widgetId;

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}

		// get a list of responses
		return $return;
	}

	/**
	 * getRealFile Attachment
	 * Retreive the actual file name stored in the db and return the string
	 *
	 */

	public function getRealFileValue($filename)
	{
		$query = "SELECT file_value
				FROM {$this->Db->TablePrefix}" . self::RESPONSEVALUE_TABLE_NAME . "
				WHERE
					surveys_response_id = " . $this->id . " AND value = '" . mysql_real_escape_string($filename) . "'";

		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		$filevalue = $row['file_value'];

		// get a list of responses
		return $filevalue;
	}


	/**
	 * deleteValues
	 * Removes all of the values associated with the current response.
	 *
	 * @return void
	 */
	public function deleteValues()
	{
		return $this->Db->Query("
				DELETE
				FROM {$this->Db->TablePrefix}" . self::RESPONSEVALUE_TABLE_NAME. "
				WHERE surveys_response_id = ". $this->id .
			";");
	}

	/**
	 * Save
	 * Sets the datetime to the current mysql timestamp and then calls parent::Save().
	 *
	 * @return bool
	 */
	public function Save()
	{
		$this->datetime = date('Y-m-d h:i:s');

		// first check if id exist..
		if ($this->id) {
			$this->updateResponse();
			return true;
		} else {
			$id = $this->insertResponse();
			if ($id !== false) {
			 	return true;
			}
		}
	}

	// now updating here..
	public function updateResponse()
	{
		$prefix = $this->Db->TablePrefix;
		$response_data = $this->data;

		if (empty($this->id)) {
			return false;
		}

		if (isset($response_data['id'])) {
			unset($response_data['id']);
		}

		$where = 'id = ' . $this->id;

		// Do the actual update query..
		$this->Db->UpdateQuery(RESPONSE_TABLE_NAME, $field , $where);
	}

	/**
	 * insertResponse
	 *
	 * Inserting the actual response to the database.
	 *
	 * @return void when success false otherwise
	 */
	public function insertResponse()
	{
		$columns = $this->_columns;
		array_shift($columns);

		$tablefields =  implode(',', $columns);
		$query = "INSERT INTO {$this->Db->TablePrefix}surveys_response ({$tablefields})
				VALUES ('" . $this->surveys_id . "','"
						   . $this->datetime .
						"')";

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

		$this->id = $fieldid;
		return $fieldid;
	}

	/**
	 * getResponseNumber
	 *
	 * Returns the response number of the current response.
	 * @param void
	 * @return int
	 */
	public function getResponseNumber()
	{
		if ($this->surveys_id && $this->id) {
			return (int) $this->Db->FetchOne("
					SELECT COUNT(id) AS count
					FROM " . $this->Db->TablePrefix . self::RESPONSE_TABLE_NAME . "
					WHERE
					`surveys_id` = " . $this->surveys_id . " and
					`id` <= " . $this->id);
			}
	}

	/**
	 * Removes all uploads associated with the current response, deletes all response value
	 * entries and removes the response.
	 *
	 * @return bool
	 */
	public function Delete($survey_id=0)
	{
		// delete any files being uploaded..
		if (!empty($survey_id)) {
			//survey ID
			$dir = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'surveys' . DIRECTORY_SEPARATOR . $survey_id . DIRECTORY_SEPARATOR . $this->id;
			if (is_dir($dir)) {
				$dir = new IEM_FileSystem_Directory($dir);
				$dir->delete();
			}
		}

		// remove all response values associated to this response
		$this->Db->Query("
				DELETE FROM " . $this->Db->TablePrefix .  self::RESPONSEVALUE_TABLE_NAME . "
				WHERE surveys_response_id = " . $this->id );

		// delete the response

		$this->Db->Query("
				DELETE FROM " . $this->Db->TablePrefix .  self::RESPONSE_TABLE_NAME . "
				WHERE id = " . $this->id );

		return true;
	}

	/**
	 * Load
	 *
	 * @param $responseid the response ID
	 *
	 * Given a responseid it will fetch the data from the database and
	 * Initiate all the detail to the Response API model.. will parse it
	 * to Load Data to leave the job..
	 *
	 *
	 */
	public function Load($responseid)
	{
		$responseid = (int)$responseid;
		$prefix = $this->Db->TablePrefix;
		$query = "SELECT * FROM {$prefix}" . self::RESPONSE_TABLE_NAME . " WHERE id = $responseid";

		$result = $this->Db->Query($query);
		$responses = $this->Db->Fetch($result);

		return $this->LoadData($responses);
	}


	/**
	 * LoadData
	 *
	 * @param $responses
	 * Load Data, populate the object from the array supplied in the param
	 *
	 * @return void don't return anything as the object will be prepopulated from the content data array
	 *
	 */
	public function LoadData($responses)
	{
		if (is_array($responses)) {
			foreach ($responses as $key => $val) {
					$this->$key = $val;
			}
		}
	}


	/**
	 * getData
	 *
	 * @param void
	 * Return the data array
	 */

	public function getData()
	{
		return $this->data;
	}



}
