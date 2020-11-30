<?php
/**
 * Module Tracker base class defintion
 *
 * This file contains a base class of Module Tracker
 *
 * @version $Id: module_tracker.php,v 1.8 2008/01/29 21:44:07 hendri Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_Tracker_DataObject
 * @uses Stats_API
 * @uses Newsletters_API
 * @uses SendStudio_Functions
 *
 * @abstract
 */

/**
 * Include data object definition
 */
require_once(dirname(__FILE__) . '/module_tracker_dataobject.php');

/**
 * Tracker Module
 *
 * The purpose of this class is to establish "common" signature among
 * the descandant. As such, the return "signature" must also be defined here.
 * The signature for return CANNOT be just FALSE when the childeren returns something else.
 *
 *
 * Each of the children must implement the following functions:
 * - GetDisplayOption
 * - ProcessOptions
 * - GetRequestOptionNames
 * - GetRecordByID
 * - _processURL
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_Tracker_DataObject
 * @uses Stats_API
 * @uses Newsletters_API
 * @uses SendStudio_Functions
 *
 * @abstract
 *
 * @todo Currently the tracker does not yet implemented in the "autoresponder" option and cleanup
 */
class module_Tracker
{
	// ----- Protected variables
		/**
		 * Tracker name
		 * @var string Name of the tracker
		 * @access protected
		 */
		var $_trackerName = null;
	// -----




	// ----- Private variables
		/**
		 * Cached newsletter API
		 * @var Newsletters_API
		 * @access private
		 */
		var $_cachedNewsletterAPI = null;

		/**
		 * Cached statistic API
		 * @var Stats_API
		 * @access private
		 */
		var $_cachedStatisticAPI = null;

		/**
		 * Cached SendStudio Function
		 * @var SendStudio_Functions
		 * @access private
		 */
		var $_cachedSendStudioFunction = null;

		/**
		 * Cached database object
		 * @var Db
		 * @access private
		 */
		var $_db = null;
	// -----



	// ----- Static function
		/**
		 * ProcessURLForAllTracker
		 *
		 * This processes a URL for all existing tracker record
		 * If a url is not passed in, or an invalid statistics type is passed in, this returns false.
		 * If an appropriate record can't be loaded from the database, this will also return false.
		 * Once the record has been found in the database, the appropriate tracker is loaded up and it will process the url from there.
		 * Once the tracker has processed the url, it is returned.
		 * You could potentially have multiple trackers tracking the same request all at the same time, so it needs to call each sub-class that it needs to
		 * so they can handle their own part of the work.
		 *
		 * @param Int $statisticID The statistics info to process the url for.
		 * @param String $statisticType The statistics type to process the url for.
		 * @param Array $linkRecord The link we're processing. This is passed off to the sub-modules for processing and then is returned from there.
		 * @param Array $subscriberRecord Subscriber record.
		 *
		 * @static
		 *
		 * @uses Db::Query()
		 * @uses Db::Fetch()
		 * @uses Db::FreeResult()
		 * @uses module_TrackerFactory_API
		 * @uses module_TrackerFactory_API::getInstance()
		 * @uses module_TrackerFactory_API::manufacture()
		 * @uses module_Tracker::getDatabaseObject()
		 * @uses module_Tracker_DataObject::processURL()
		 *
		 * @return String Returns processed URL
		 */
		function ProcessURLForAllTracker($statisticID, $statisticType, $linkRecord, $subscriberRecord)
		{
			if ($linkRecord['url'] == '') {
				return false;
			}

			if (!in_array($statisticType, module_Tracker::_getValidStatisticTypes())) {
				return false;
			}

			$db = IEM::getDatabase();
			$mStatID = intval($statisticID);
			$mStatType = $db->Quote($statisticType);

			$rs = $db->Query('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . "module_tracker WHERE statid = {$mStatID} AND stattype = '{$mStatType}'");
			if ($rs === false) {
				return false;
			}

			$factory =& module_TrackerFactory_API::getInstance();

			// we could have multiple trackers for the same item, so go through them all.
			$trackers = array();
			while (($record = $db->Fetch($rs)) != false) {
				if (!array_key_exists($record['trackername'], $trackers)) {
					$trackers[$record['trackername']] =& $factory->manufacture($record['trackername']);
				}

				$dataObject = $trackers[$record['trackername']]->getDataObject($record);
				$status = $dataObject->processURL($linkRecord, $subscriberRecord);

				if ($status != false) {
					$linkRecord['url'] = $status;
				}
			}

			$db->FreeResult($rs);

			return $linkRecord['url'];
		}

		/**
		 * GetDisplayOptionsForAllTracker
		 * For each of the trackers available, it will add their display options to the array that gets returned.
		 * This will allow the caller to get a list of tracker modules and handle displaying all of the options they provide.
		 *
		 * @static
		 *
		 * @uses module_TrackerFactory_API::getInstance()
		 * @uses module_TrackerFactory_API::implementationList()
		 * @uses module_TrackerFactory_API::manufacture()
		 * @uses module_Tracker::GetDisplayOption()
		 *
		 * @return Array Returns an array of HTML that contains the options for all the available trackers (key => HTML, where the key is the tracker name)
		 */
		function GetDisplayOptionsForAllTracker()
		{
			$factory =& module_TrackerFactory_API::getInstance();
			$list = $factory->implementationList();

			$options = array();

			if (is_array($list)) {
				foreach ($list as $each) {
					$tracker =& $factory->manufacture($each);
					$options[$each] = $tracker->GetDisplayOption();
				}
			}

			return $options;
		}

		/**
		 * ParseOptionsForAllTracker
		 * This checks for basic information which includes a statid, a stattype and a newsletterid.
		 * If any of the required information isn't provided, this will trigger an error and return an array of 'status' => false and an empty 'results' array.
		 *
		 * Once the required info is checked, it passes everything off to the sub-tracker to handle individually.
		 * If any of the sub-tracker checks fail, this will return false and a 'results' array containing more information.
		 *
		 * @param Array $request Request variable (can be obtained from $_POST or $_GET or $_REQUEST)
		 * @throws E_USER_NOTICE Invalid request parameters were passed
		 *
		 * @uses module_TrackerFactory_API::getInstance()
		 * @uses module_TrackerFactory_API::implementationList()
		 * @uses module_TrackerFactory_API::manufacture()
		 * @uses module_Tracker::ProcessOptions()
		 *
		 * @static
		 *
		 * @return Array Returns status array (array(status => boolean, results => array()))
		 */
		function ParseOptionsForAllTracker($request)
		{
			$parametersNeeded = array('statid', 'stattype', 'newsletterid');
			foreach ($parametersNeeded as $each) {
				if (!array_key_exists($each, $request)) {
					trigger_error('module_Tracker::ParseOptionsForAllTracker -- Invalid request parameters were passed', E_USER_NOTICE);
					return array('status' => false, 'results' => array());
				}
			}

			$factory =& module_TrackerFactory_API::getInstance();
			$list = $factory->implementationList();

			$allResult = true;
			$results = array();

			if (is_array($list)) {
				foreach ($list as $each) {
					$tracker =& $factory->manufacture($each);
					$status = $tracker->ProcessOptions($request);

					if ($status == false) {
						$allResult = false;
					}

					$results[$each] = $status;
				}
			}

			return array('status' => $allResult, 'results' => $results);
		}

		/**
		 * GetRequestOptionNames
		 * Get all of the option names for all tracker
		 *
		 * This method will return an array of request variables that should be retained
		 * in order for the trackers to get thier information.
		 *
		 * @uses module_TrackerFactory_API::getInstance()
		 * @uses module_TrackerFactory_API::implementationList()
		 * @uses module_TrackerFactory_API::manufacture()
		 * @uses module_Tracker::GetRequestOptionNames()
		 *
		 * @static
		 *
		 * @return Array Returns a list of variables that needed to be retained
		 */
		function GetRequestOptionNamesForAllTracker()
		{
			$factory =& module_TrackerFactory_API::getInstance();
			$list = $factory->implementationList();

			$variableNames = array();

			if (is_array($list)) {
				foreach ($list as $each) {
					$tracker =& $factory->manufacture($each);
					$temp = $tracker->GetRequestOptionNames();

					if (is_array($temp)) {
						$variableNames = array_merge($variableNames, $temp);
					}
				}
			}

			return $variableNames;
		}

		/**
		 * Delete records for all trackers by ID
		 *
		 * Delete all records for all trackers that matches the specified ID
		 *
		 * @param Int $statisticID Statistic ID
		 * @param String $statisticType Statistic type
		 *
		 * @throws E_USER_NOTICE Invalid ID
		 *
		 * @static
		 *
		 * @uses Db::Query()
		 * @uses Db::Fetch()
		 * @uses Db::FreeResult()
		 * @uses module_TrackerFactory_API
		 * @uses module_TrackerFactory_API::getInstance()
		 * @uses module_TrackerFactory_API::manufacture()
		 * @uses module_Tracker::getDatabaseObject()
		 * @uses module_Tracker_DataObject::delete()
		 *
		 * @todo use transaction so u know whether or not to start transaction or not
		 *
		 * @return Boolean Returns TRUE if successful, FALSE otherwise
		 */
		function DeleteRecordsForAllTrackerByID($statisticID, $statisticType)
		{
			/**
			 * Sanitize/Declare variables used by function
			 */
				$db = null;
				$factory = null;
				$trackers = array();

				$mStatisticID = intval($statisticID);
				$mStatisticType = in_array($statisticType, module_Tracker::_getValidStatisticTypes())? $statisticType : '';
			/**
			 * -----
			 */

			/**
			 * Pre-checking
			 */
				if ($mStatisticID == 0 || $mStatisticType == '') {
					trigger_error('module_Tracker::DeleteRecordsForAllTrackerByID -- Invalid ID', E_USER_NOTICE);
					return false;
				}
			/**
			 * -----
			 */

			/**
			 * Passes pre-check, do pre-processing
			 */
				$db = IEM::getDatabase();
				$factory =& module_TrackerFactory_API::getInstance();
			/**
			 * -----
			 */

			$rs = $db->Query('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . "module_tracker WHERE statid = {$mStatisticID} AND stattype = '{$mStatisticType}'");
			if ($rs === false) {
				return true;
			}

			$status = true;
			while (($record = $db->Fetch($rs)) != false) {
				if (!array_key_exists($record['trackername'], $trackers)) {
					$trackers[$record['trackername']] =& $factory->manufacture($record['trackername']);
				}

				$do = $trackers[$record['trackername']]->getDataObject($record);
				$status = $do->delete();

				if ($status == false) {
					break;
				}
			}

			$db->FreeResult($rs);
			return $status;
		}

		/**
		 * DeleteRecordForAllTrackerByNewsletterID
		 * Delete all records for all trackers that matches specific newsletter ID
		 * If an invalid id is passed in (<= 0), this returns false.
		 * If the tracker modules don't have any data for this newsletterid, this also returns false.
		 *
		 * @param Int $newsletterID Newsletter ID
		 *
		 * @throws E_USER_NOTICE Invalid newsletter ID
		 *
		 * @static
		 *
		 * @uses Db::Query()
		 * @uses Db::Fetch()
		 * @uses Db::FreeResult()
		 * @uses module_TrackerFactory_API
		 * @uses module_TrackerFactory_API::getInstance()
		 * @uses module_TrackerFactory_API::manufacture()
		 * @uses module_Tracker::getDatabaseObject()
		 * @uses module_Tracker_DataObject::delete()
		 *
		 * @todo use transaction so u know whether or not to start transaction or not
		 *
		 * @return Boolean Returns TRUE if successful, FALSE otherwise
		 */
		function DeleteRecordForAllTrackerByNewsletterID($newsletterID)
		{
			/**
			 * Sanitize/Declare variables used by function
			 */
				$db = null;
				$factory = null;
				$trackers = array();

				$mNewsletterID = intval($newsletterID);
			/**
			 * -----
			 */

			/**
			 * Pre-checking
			 */
				if ($mNewsletterID == 0) {
					trigger_error('module_Tracker::DeleteRecordForAllTrackerByNewsletterID -- Invalid newsletter ID', E_USER_NOTICE);
					return false;
				}
			/**
			 * -----
			 */

			/**
			 * Passes pre-check, do pre-processing
			 */
				$db = IEM::getDatabase();
				$factory =& module_TrackerFactory_API::getInstance();
			/**
			 * -----
			 */

			$rs = $db->Query('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . "module_tracker WHERE newsletterid = {$mNewsletterID}");
			if ($rs === false) {
				return true;
			}

			$status = true;
			while (($record = $db->Fetch($rs)) != false) {
				if (!array_key_exists($record['trackername'], $trackers)) {
					$trackers[$record['trackername']] =& $factory->manufacture($record['trackername']);
				}

				$do = $trackers[$record['trackername']]->getDataObject($record);
				$status = $do->delete();

				if ($status == false) {
					break;
				}
			}

			$db->FreeResult($rs);
			return $status;
		}
	// -----




	/**
	 * CONSTRUCTOR
	 * Each sub-module's constructor must call $this->_init()
	 *
	 * @throws E_USER_ERROR This class cannot be instantiated
	 *
	 * @abstract
	 */
	function module_Tracker()
	{
		trigger_error('This class cannot be instantiated', E_USER_ERROR);
	}




	/**
	 * __sleep
	 * Return a list of properties PHP should keep in case of serialization happens.
	 *
	 * @return Array Returns an array of properties to keep, which in this case is just the _trackerName
	 */
	function __sleep()
	{
		return array('_trackerName');
	}

	/**
	 * __wakeup
	 * This function is called right after unsserialization.
	 * It basically will re-initialize the object.
	 * @return Void Returns nothing
	 */
	function __wakeup()
	{
		$this->_cachedNewsletterAPI = null;
		$this->_cachedStatisticAPI = null;
		$this->_cachedSendStudioFunction = null;
		$this->_db = null;

		$this->_init();
	}




	/**
	 * getDataObject
	 * Get new data object
	 * This tries to create a new class based on the tracker name that has been loaded.
	 * Once that class is loaded, the record is passed to that class for processing.
	 * If no class or tracker has been loaded, this returns false.
	 *
	 * @param Array $record Records to populate data object with (Optional, Default = array())
	 * @return module_Tracker_DataObject Returns tracker data object
	 */
	function GetDataObject($record = array())
	{
		if ($this->_trackerName == null) {
			return false;
		}
		$className = "module_{$this->_trackerName}_Tracker_DataObject";
		return new $className($record);
	}

	/**
	 * GetDisplayOption
	 * Get HTML options to be displayed
	 * Each sub-module needs to create their own version of this method.
	 *
	 * Base class will throw an error and WILL NOT CONTINUE when it is not being implemented by the descendants
	 *
	 * @throws E_USER_ERROR Cannot instantiate this class
	 * @abstract
	 *
	 * @return String Returns options as an HTML
	 */
	function GetDisplayOption()
	{
		trigger_error('Cannot instantiate this class', E_USER_ERROR);
	}

	/**
	 * ProcessOptions
	 * Process options from request
	 * Each sub-module needs to create their own version of this method.
	 *
	 * @param mixed[] $request Request variables (ie. $_POST, $_GET, $_REQUEST)
	 *
	 * @throws E_USER_ERROR Cannot instantiate this class
	 * @abstract
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function ProcessOptions($request)
	{
		trigger_error('Cannot instantiate this class', E_USER_ERROR);
	}

	/**
	 * GetRequestOptionNames
	 * Get request variable names used by tracker
	 * Each sub-module needs to create their own version of this method.
	 *
	 * @throws E_USER_ERROR Cannot instantiate this class
	 * @abstract
	 *
	 * @return string[] Returns variable names in request used by tracker
	 */
	function GetRequestOptionNames()
	{
		trigger_error('Cannot instantiate this class', E_USER_ERROR);
	}

	/**
	 * GetRecordByID
	 * Get record by ID
	 * Each sub-module needs to create their own version of this method.
	 *
	 * @param Integer $statisticID Statistic ID
	 * @param String $statisticType Statistic type
	 *
	 * @throws E_USER_ERROR Cannot instantiate this class
	 * @abstract
	 *
	 * @return module_Tracker_DataObject|false Returns data object if successful, FALSE otherwise
	 */
	function GetRecordByID($statisticID, $statisticType)
	{
		trigger_error('Cannot instantiate this class', E_USER_ERROR);
	}




	// ----- Data Object methods (As these functions should not be called from outside of "Data Object" it should be STRICT in it's signature, no OPTIONAL allowed)
	// ----- DO_ is a prefix for "Data Object" -- NOT do something
		/**
		 * DO_ProcessURL
		 * This method should only be called by the data object
		 * This calls _ProcessURL to do all of the work.
		 *
		 * @param Array $trackerRecord The record to pass to _ProcessURL
		 * @param Array $linkRecord The link info to pass to _ProcessURL
		 * @param Array $subscriberRecord The subscriber info to pass to _ProcessURL
		 *
		 * @return Mixed Return processed URL if successful, FALSE otherwise
		 */
		function DO_ProcessURL($trackerRecord, $linkRecord, $subscriberRecord)
		{
			return $this->_ProcessURL($trackerRecord, $linkRecord, $subscriberRecord);
		}

		/**
		 * DO_CheckStatisticAvailable
		 * Data Object Method: Check whether or not statistic type is valid
		 * Method should only be called by DataObject class
		 *
		 * @param String $statisticType Statistic type to be checked
		 *
		 * @return Boolean Returns TRUE if valid, FALSE otherwise
		 */
		function DO_CheckValidStatisticType($statisticType)
		{
			return in_array($statisticType, module_Tracker::_getValidStatisticTypes());
		}

		/**
		 * DO_CheckValidStatisticType
		 * Data Object Method: Check whether or not statistic record exists
		 * Method should only be called by DataObject class
		 *
		 * @param Int $statisticID Statistic ID
		 * @param String $statisticType Statistic type
		 *
		 * @uses Stats_API
		 * @uses Stats_API::FetchStats()
		 *
		 * @return Boolean Returns TRUE if exists, FALSE otherwise
		 */
		function DO_CheckStatisticAvailable($statisticID, $statisticType)
		{
			if (is_null($this->_cachedStatisticAPI)) {
				require_once(SENDSTUDIO_API_DIRECTORY . '/stats.php');

				$this->_cachedStatisticAPI = new Stats_API();
			}

			$status = $this->_cachedStatisticAPI->FetchStats($statisticID, $statisticType);

			// It should only return boolean AND NOTHING ELSE
			if ($status == false) {
				return false;
			}

			return true;
		}

		/**
		 * DO_CheckNewsletterAvailable
		 * Data Object Method: Check whether or not newsletter record exists
		 * Method should only be called by DataObject class
		 * If the newsletter can be loaded, this returns true. Otherwise it returns false.
		 *
		 * @param Int $newsletterID The newsletter id to check for.
		 *
		 * @uses Newsletters_API
		 * @uses Newsletters_API::Load()
		 *
		 * @return Boolean Returns TRUE if exists, FALSE otherwise
		 */
		function DO_CheckNewsletterAvailable($newsletterID)
		{
			if (is_null($this->_cachedNewsletterAPI)) {
				require_once(SENDSTUDIO_API_DIRECTORY . '/newsletters.php');

				$this->_cachedNewsletterAPI = new Newsletters_API();
			}

			$status = $this->_cachedNewsletterAPI->Load($newsletterID);

			// It should only return boolean AND NOTHING ELSE
			if ($status == false) {
				return false;
			}

			return true;
		}

		/**
		 * DO_Save
		 * Method should only be called by DataObject class
		 *
		 * @param Array $record Record to be saved
		 * @param Boolean $update Specify whether to update or to insert
		 *
		 * @return Boolean Returns TRUE if successful, FALSE otherwise
		 */
		function DO_Save($record, $update)
		{
			if ($update) {
				if (!$this->_updateRecord($record)) {
					return $this->_insertRecord($record);
				}

				return true;
			} else {
				if (!$this->_insertRecord($record)) {
					return $this->_updateRecord($record);
				}

				return true;
			}
		}

		/**
		 * DO_Delete
		 * Method should only be called by DataObject class
		 * Returns the status from _DeleteRecord.
		 *
		 * @param Int $statisticID Statistic ID
		 * @param String $statisticType Statistic Type
		 *
		 * @return Boolean Returns TRUE if sucessful, FALSE otherwise
		 */
		function DO_Delete($statisticID, $statisticType)
		{
			return $this->_DeleteRecord($statisticID, $statisticType);
		}
	// -----



	// ----- Protected functions
		/**
		 * _ProcessURL
		 *
		 * @param Mixed $trackerRecord Record fetched from database
		 * @param Mixed $linkRecord Link record
		 * @param Mixed $subscriberRecord Subscriber record
		 * @return Mixed Return processed URL if successful, FALSE otherwise
		 * @throws E_USER_ERROR This is an abstract class
		 *
		 * @access protected
		 * @abstract
		 */
		function _ProcessURL($trackerRecord, $linkRecord, $subscriberRecord)
		{
			trigger_error('This is an abstract class', E_USER_ERROR);
		}

		/**
		 * _insertRecord
		 * Inserts a new record into the database based on the array of info passed in.
		 * If either statid or stattype aren't present, this will return false.
		 *
		 * @param Array $record Records to be inserted to database
		 *
		 * @access protected
		 *
		 * @uses Db::Query()
		 * @uses Db::Quote()
		 * @uses Db::NumAffected()
		 *
		 * @return Boolean Returns TRUE if successful, FALSE otherwise
		 */
		function _insertRecord($record)
		{
			$columns = array();
			$values = array();

			foreach ($record as $column => $value) {
				array_push($columns, $column);

				if (is_null($value)) {
					array_push($values, 'NULL');
				} elseif (is_numeric($value)) {
					array_push($values, $value);
				} else {
					array_push($values, "'" . $this->_db->Quote($value) . "'");
				}
			}

			$status = $this->_db->Query('INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'module_tracker('. implode(',', $columns) . ')'.
										' VALUES (' . implode(',', $values) . ')');

			if ($status !== false) {
				if ($this->_db->NumAffected($status) > 0) {
					return true;
				}
			}

			return false;
		}

		/**
		 * _updateRecord
		 * Updates an existing tracker record in the database based on the array of info passed in.
		 * The array contains all info for the record.
		 * If either statid or stattype are not present in the array passed in, this will return false.
		 *
		 * @param Array $record The records to be updated. This includes the statid and stattype and any other info that the record requires.
		 *
		 * @access protected
		 *
		 * @uses Db::Query()
		 * @uses Db::Quote()
		 * @uses Db::NumAffected()
		 *
		 * @return Boolean Returns TRUE if successful, FALSE otherwise
		 */
		function _updateRecord($record)
		{
			$skip = array('statid', 'stattype');
			$fields = array();

			foreach ($record as $column => $value) {
				if (in_array($column, $skip)) {
					continue;
				}

				$temp = "{$value}=";
				if (is_null($value)) {
					$temp .= 'NULL';
				} elseif (is_numeric($value)) {
					$temp .= $value;
				} else {
					$temp .= "'" . $this->_db->Quote($value) . "'";
				}

				array_push($fields, $temp);
			}

			$status = $this->_db->Query('UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'module_tracker'.
										' SET ' . implode(',', $fields).
										' WHERE statid=' . intval($record['statid']) .
										" AND stattype = '" . $this->Db->Quote($record['stattype']) . "'", true);

			if ($status !== false) {
				if ($this->_db->NumAffected($status) > 0) {
					return true;
				}
			}

			return false;
		}

		/**
		 * _DeleteRecord
		 * Deletes a record from the module tracker table based on the statid, statstype and tracker name.
		 * The trackername depends on which module has already been loaded.
		 * If no module has been loaded then this will return false.
		 * If no records are deleted, this will also return false.
		 *
		 * @param Int $statisticID The statistics id we're going to delete.
		 * @param String $statisticType The type of statistics we're going to delete.
		 *
		 * @access protected
		 *
		 * @uses Db::Query()
		 * @uses Db::Quote()
		 * @uses Db::NumAffected()
		 *
		 * @return Boolean Returns TRUE if sucessful, FALSE otherwise
		 */
		function _DeleteRecord($statisticID, $statisticType)
		{
			$status = $this->_db->Query('DELETE FROM ' . SENDSTUDIO_TABLEPREFIX . 'module_tracker'.
										' WHERE statid=' . intval($statisticID).
										" AND stattype='".$this->_db->Quote($statisticType)."'" .
										" AND trackername='".$this->_db->Quote($this->_trackerName)."'");

			if ($status !== false) {
				if ($this->_db->NumAffected($status) > 0) {
					return true;
				}
			}

			return false;
		}

		/**
		 * _parseTemplate
		 * This is a wrapper for the sendstudio_functions::ParseTemplate function.
		 * It only loads up the sendstudio class once and stores it in the object for easy caching.
		 * If an array of values to plug in to the template are not supplied, then it triggers an error and returns false.
		 * If the template doesn't exist, this will also return false but the sendstudio_functions::ParseTemplate function handles this directly.
		 *
		 * @param String $templateFullPath The full path where the template reside
		 * @param Array $values Values to merge template with (Optional, default = array())
		 *
		 * @access protected
		 *
		 * @uses SendStudio_Functions
		 * @uses SendStudio_Functions::ParseTemplate()
		 *
		 * @throws E_USER_NOTICE Invalid values
		 * @return String|False Return a processed template if successful, FALSE otherwise
		 */
		function _parseTemplate($templateFullPath, $values = array())
		{
			if (!is_array($values)) {
				trigger_error('module_Tracker::_parseTemplate -- Invalid values', E_USER_NOTICE);
				return false;
			}

			if (is_null($this->_cachedSendStudioFunction)) {
				require_once(SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php');

				$this->_cachedSendStudioFunction = new SendStudio_Functions();
			}

			foreach ($values as $key => $value) {
				$GLOBALS[$key] = $value;
			}

			return $this->_cachedSendStudioFunction->ParseTemplate(true, true, true, $templateFullPath);
		}

		/**
		 * _loadLanguage
		 * Load language file
		 * This checks to make sure that the language file is in the right place before it tries to load it up.
		 *
		 * @param String $languageFile The language file to load. This is based off the tracker that has been loaded.
		 *
		 * @return Boolean Returns TRUE if successful, FALSE otherwise
		 */
		function _loadLanguage($languageFile)
		{
			$fileName = dirname(__FILE__) . '/' . strtolower($this->_trackerName) . '/language/' . $languageFile . '.php';
			if (!is_file($fileName)) {
				return false;
			}

			include_once($fileName);
			return true;
		}

		/**
		 * _init
		 * A protected method to initialize this class
		 * It checks to make sure that the tracker being loaded is actually a proper tracker.
		 * Also sets up the database class for easy access and loads up the 'common' language file.
		 *
		 * @throws E_USER_ERROR Invalid class name
		 * @access protected
		 *
		 * @uses IEM::getDatabase()
		 *
		 * @return Void Doesn't return anything.
		 */
		function _init()
		{
			// Get tracker implementation name
			if (is_null($this->_trackerName)) {
				preg_match('/module_(.*)_Tracker/i', get_class($this), $matches);
				if (is_array($matches) && count($matches) == 2) {
					$this->_trackerName = ucwords($matches[1]);
				} else {
					trigger_error('module_Tracker::_init -- Invalid class name', E_USER_ERROR);
				}
			}

			if (is_null($this->_db)) {
				$this->_db = IEM::getDatabase();
			}

			// Load common language
			$this->_loadLanguage('common');
		}
	// -----




	// ----- Private functions
		/**
		 * _getValidStatisticTypes
		 * Get valid statistic types
		 *
		 * @static
		 * @access private
		 *
		 * @return string[] Retuns an array of valid statistic types
		 */
		function _getValidStatisticTypes()
		{
			return array('auto', 'newsletter');
		}
	// -----
}
