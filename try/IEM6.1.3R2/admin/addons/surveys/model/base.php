<?php
/**
 * Model Base definition for CRUD Surveys Model Base
 *
 * @author Yudi
 *
 */
abstract class Addons_surveys_model_base
{
	protected $_tableprefix;

	protected $_db;

	public function __construct()
	{

		$this->_db = IEM::getDatabase();

		$this->_tableprefix = constant('SENDSTUDIO_TABLEPREFIX');

	}


	/**
	 * Get ID
	 * 	Returns the specific / current ID of what is currently in the table;
	 */


}






?>