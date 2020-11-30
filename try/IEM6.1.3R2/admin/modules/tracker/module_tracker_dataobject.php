<?php
/**
 * Tracker Module Data Object Class
 *
 * This file contains a class definition for Tracker Module Data Object
 *
 * @version $Id: module_tracker_dataobject.php,v 1.1 2008/01/18 03:39:25 hendri Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_TrackerFactory
 * @uses module_Tracker
 *
 * @abstract
 */

/**
 * Tracker Module Data Object Class
 *
 * The following function needed to be implemented:
 * - CONSTRUCTOR => Need to have the same signature as this class (ie.. see declaration below)
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_TrackerFactory
 * @uses module_Tracker
 *
 * @abstract
 */
class module_Tracker_DataObject
{
	// ----- Proctected variables
		/**
		 * Held records
		 * @var mixed[] Records
		 * @access protected
		 */
		var $_records = array(	'statid' => null,
								'stattype' => null,
								'trackername' => null,
								'newsletterid' => null,
								'datastring' => null);

		/**
		 * Tracker name
		 * @var string
		 * @access protected
		 */
		var $_trackerName = null;

		/**
		 * Cached data
		 * @var mixed[] Unserialized cached data
		 * @access protected
		 */
		var $_cachedData = null;

		/**
		 * Cached DAO
		 * @var module_Tracker
		 * @access protected
		 */
		var $_cachedDAO = null;

		/**
		 * A "smart" flag whether to update or insert the record
		 * @var boolean
		 */
		var $_update = false;
	// -----




	/**
	 * CONSTRUCTOR
	 *
	 * Each constructor must:
	 * - Emulate this constructor signature
	 * - call $this->_init($record);
	 *
	 * @param mixed[] $record Record to be loaded to the class (Optional, Default = array())
	 * @throws E_USER_ERROR This class cannot be instantiated
	 *
	 * @abstract
	 */
	function module_Tracker_DataObject($record = array())
	{
		trigger_error('This class cannot be instantiated', E_USER_ERROR);
	}




	/**
	 * MAGIC: SLEEP
	 */
	function __sleep()
	{
		return array('_records', '_trackerName');
	}

	/**
	 * MAGIC: WAKEUP
	 */
	function __wakeup()
	{
		$this->_init();
	}





	/**
	 * Get ID
	 * @return mixed[] Retuns ID (statistic_id, statistic_type)
	 */
	function getID()
	{
		return array(	'statistic_id' => $this->_record['statid'],
						'statistic_type' => $this->_records['stattype']);
	}

	/**
	 * Set ID
	 * @param int $statisticID Statistic ID
	 * @param string $statisticType Statistic Type
	 * @param boolean $check Specify whether or not to check ID (statistic) exists or not (Optional, DEFAULT = TRUE)
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_NOTICE Invalid ID
	 * @throws E_USER_NOTICE Statistic record does not exists
	 *
	 * @uses module_Tracker::DO_CheckValidStatisticType()
	 * @uses module_Tracker::DO_CheckStatisticAvailable()
	 */
	function setID($statisticID, $statisticType, $check = true)
	{
		$mStatID = intval($statisticID);
		$mStatType = $this->_cachedDAO->DO_CheckValidStatisticType($statisticType)? $statisticType : '';

		if ($mStatID == 0 || $statisticType == '') {
			trigger_error('module_Tracker_DataObject::setID -- Invalid ID', E_USER_NOTICE);
			return false;
		}

		if ($check) {
			if (!$this->_cachedDAO->DO_CheckStatisticAvailable($mStatID, $mStatType)) {
				trigger_error('module_Tracker_DataObject::setID -- Statistic record does not exists', E_USER_NOTICE);
				return false;
			}
		}

		$this->_records['statid'] = $mStatID;
		$this->_records['stattype'] = $mStatType;
		return true;
	}

	/**
	 * Get Newsletter ID
	 * @return int Return newsletter ID
	 */
	function getNewsletterID()
	{
		return intval($this->_records['newsletterid']);
	}

	/**
	 * Set newsletter ID
	 * @param int $newsletterID Newsletter ID
	 * @param boolean $check Specify whether or not to check ID (newsletter) exists or not (Optional, DEFAULT = TRUE)
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_NOTICE Invalid newsletter ID
	 * @throws E_USER_NOTICE Newsletter record does not exits
	 *
	 * @uses module_Tracker::DO_CheckNewsletterAvailable()
	 */
	function setNewsletterID($newsletterID, $check = true)
	{
		$mNewsletterID = intval($newsletterID);
		if ($mNewsletterID == 0) {
			trigger_error('module_Tracker_DataObject::setNewsletterID -- Invalid newsletter ID', E_USER_NOTICE);
			return false;
		}

		if ($check) {
			if (!$this->_cachedDAO->DO_CheckNewsletterAvailable($newsletterID)) {
				trigger_error('module_Tracker_DataObject::setNesletterID -- Newsletter record does not exists');
				return false;
			}
		}

		$this->_records['newsletterid'] = $mNewsletterID;
		return true;
	}

	/**
	 * Get data
	 * @return mixed[] Returns data stored
	 */
	function getData()
	{
		if(is_null($this->_cachedData)) {
			if (!empty($this->_records['datastring'])) {
				$this->_cachedData = unserialize($this->_records['datastring']);
			} else {
				$this->_cachedData = array();
			}
		}

		return $this->_cachedData;
	}

	/**
	 * Set Data
	 * @param string|mixed[] $data Data to be set
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_NOTICE Cannot process data
	 */
	function setData($data)
	{
		if(is_array($data)) {
			$this->_records['datastring'] = serialize($data);
			$this->_cachedData = $data;
		} else {
			$status = unserialize($data);

			if($status === false) {
				trigger_error('module_Tracker_DataObject::setData -- Cannot process data');
				return false;
			}

			$this->_records['datastring'] = $data;
			$this->_cachedData = null;
		}

		return true;
	}

	/**
	 * Process url
	 * @param mixed[] $linkRecord Link records
	 * @param mixed[] $subscriberRecord Subscriber records
	 *
	 * @uses module_Tracker::DO_ProcessURL()
	 */
	function processURL($linkRecord, $subscriberRecord)
	{
		$data = $this->_records;
		$data['data'] = $this->getData();
		return $this->_cachedDAO->DO_ProcessURL($data, $linkRecord, $subscriberRecord);
	}

	/**
	 * Save data to database
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_NOTICE Invalid ID
	 *
	 * @uses module_Tracker::DO_Save()
	 */
	function save()
	{
		if(empty($this->_records['statid']) || empty($this->_records['stattype'])) {
			trigger_error('module_Tracker_DataObject::save() -- Invalid ID', E_USER_NOTICE);
			return false;
		}

		return $this->_cachedDAO->DO_Save($this->_records, $this->_update);
	}

	/**
	 * Delete data from database
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_NOTICE Invalid ID
	 *
	 * @uses module_Tracker::DO_Delete()
	 */
	function delete()
	{
		if(empty($this->_records['statid']) || empty($this->_records['stattype'])) {
			trigger_error('module_Tracker_DataObject::save() -- Invalid ID', E_USER_NOTICE);
			return false;
		}

		return $this->_cachedDAO->DO_Delete($this->_records['statid'], $this->_records['stattype']);
	}





	// ----- Proctected function
		/**
		 * A protected method to initialize this class
		 * @param mixed[] $record Record to be loaded to the class (Optional, Default = array())
		 * @throws E_USER_ERROR Invalid class name
		 * @access protected
		 *
		 * @uses module_TrackerFactory_API
		 * @uses module_TrackerFactory::manufacture()
		 */
		function _init($record = array())
		{
			// Get tracker implementation name
			if (is_null($this->_trackerName)) {
				@preg_match('/module_(.*)_Tracker_DataObject/i', get_class($this), $matches);
				if (is_array($matches) && count($matches) == 2) {
					$this->_trackerName = ucwords($matches[1]);
				} else {
					trigger_error('module_Tracker_DataObject::_init -- Invalid class name', E_USER_ERROR);
				}
			}

			// Cache the DAO
			$factory =& module_TrackerFactory_API::getInstance();
			$this->_cachedDAO =& $factory->manufacture($this->_trackerName);

			// Populate record if record is parsed to the parameter
			if (is_array($record) &&  count($record) > 0) {
				foreach($this->_records as $key => $value) {
					if (array_key_exists($key, $record)) {
						$this->_records[$key] = $record[$key];
					}
				}
			}

			// Make sure that "trackername" is always be overwritten
			$this->_records['trackername'] = $this->_trackerName;

			// Update the "update" flag
			$this->_update = !(is_null($this->_records['statid']) || is_null($this->_records['stattype']));
		}
	// -----
}
?>