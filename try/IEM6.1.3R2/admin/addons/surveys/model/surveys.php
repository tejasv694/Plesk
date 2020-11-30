<?php
/**
 * Survey Model definition for all operation concering
 * the survey table..
 *
 * @author Yudi
 *
 */

class Addons_surveys_model_surveys extends Addons_surveys_model_base
{
	private $_tablename = 'surveys';

	/*
	 * Contains all data columns for the table
	 */

	protected $_data = array(
				'name' => '',
				'description' => '',
				'created' => '',
				'email' => '',
				'email_feedback' => '',
				'after_submit' => '',
				'show_message' => '',
				'show_uri' => '',
				'error_message' => '',
				'submit_button_text' => ''
			);




	public $id = false;

	public function __construct($form) {
		parent::__construct();

		foreach ($form as $keys->$value) {


		}



		$this->setTableName();
	}

	private function setTableName() {
		$this->_tablename = $this->_tableprefix . $this->_tablename;
	}

	public function getTableKeys() {
		return (array_keys($this->_data));
	}

	public function Set($key, $value) {
		if (!array_key_exists($key, $_data)) {

		}
		$this->_data[$key] = $value;
	}

	public function Get($key) {
		if (array_key_exists($key, $_data)) {
			return $this->_data[$key];
		}
	}

	public function GetAllData() {
		return $this->_data;
	}



	/**
	 * Get ID
	 * 	Returns the specific / current ID of what is currently in the table;
	 */
	public function GetId() {
		return $id;
	}



}






?>