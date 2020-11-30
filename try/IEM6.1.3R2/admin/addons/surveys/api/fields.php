<?php
/**
 * Addons_surveys_widgets_api
 * API functions for surveys
 *
 * By Yudi
 */

class Addons_survey_fields_api extends API
{

 	const BASE_TABLE_NAME = 'surveys_fields';

	/**
	 * Holds values set using __set
	 * @var Array
	 */
	private $data = array();


	protected $_columns = array(
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
	 * __set
	 * Handles setting of members object
	 *
	 * @param $var the name of the variable
	 * @param $val value to be set
	 *
	 */
	public function __set($var,$val)
	{
		$this->data[$var] = $val;
	}

	/**
	 * __get
	 *
	 * Get the object data and will return the actual value
	 * or false if nothing is fine.
	 *
	 * @param $var the name of the variable
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
	 * populateFormData
	 *
	 * This function will populate all the data from the post param into
	 * the fields object.
	 *
	 * @param $widgets the actual widgets data
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





}