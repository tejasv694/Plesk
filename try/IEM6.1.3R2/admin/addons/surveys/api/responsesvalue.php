<?php

class Addons_survey_responsesvalue_api extends API
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
				'surveys_response_id',
				'surveys_widgets_id',
				'value',
				'is_othervalue',
				'file_value'
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
	 * SaveResponse
	 * Sets the datetime to the current mysql timestamp and then calls parent::Save().
	 *
	 * @return bool
	 */
	public function Save()
	{
		$this->Set('value', Interspire_HtmlCleaner::CleanHTMLStatic($this->value));
		$id = $this->insertResponseValue();
		if ($id !== false) {
			return true;
		}
	}

	public function insertResponseValue()
	{
		$columns = $this->_columns;
		array_shift($columns);

		$tablefields =  implode(',', $columns);
		$query = "INSERT INTO {$this->Db->TablePrefix}" . self::RESPONSEVALUE_TABLE_NAME ." ({$tablefields})
				VALUES ('" . $this->surveys_response_id . "','"
						   . $this->surveys_widgets_id . "','"
						   . $this->Db->Quote($this->value) . "','"
						   . intval($this->is_othervalue) . "','"
						   . $this->file_value .
						"')";

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query .= ' RETURNING id;';
		}

		$results = $this->Db->Query($query);

		if ($results === false) {
			return false;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$responsevalueid = $this->Db->FetchOne($results);
		} else {
			$responsevalueid = $this->Db->LastId();
		}

		return $responsevalueid;
	}




	/**
	 * Removes all uploads associated with the current response, deletes all response value
	 * entries and removes the response.
	 *
	 * @return bool
	 */
	public function Delete($survey_id=0)
	{

		// deleting the actual survey images folder if there are any..
		if (!empty($survey_id)) {
			$dir = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $survey_id . DIRECTORY_SEPARATOR . $this->id;
			if (is_dir($dir)) {
				$dir = new IEM_FileSystem_Directory($dir)	;
				$dir->delete();
			}
		}

		// remove all response values associated to this response
		$this->db->Query("
				DELETE FROM " . $this->Db->TablePrefix .  self::RESPONSEVALUE_TABLE_NAME . "
				WHERE surveys_response_id = " . $this->id );

		// delete the response
		$this->db->Query("
				DELETE FROM " . $this->Db->TablePrefix .  self::RESPONSE_TABLE_NAME . "
				WHERE id = " . $this->id );

	}

}
