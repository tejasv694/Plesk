<?php
/**
 * Segment API.
 *
 * @package API
 * @subpackage Segment_API
 */

/**
 * Include the base API class if we haven't already.
 */
require_once(dirname(__FILE__) . '/api.php');

/**
 * Segment API provide an abstraction layer
 * that will sit between the application and the database
 * object.
 *
 * The layer will contains logic that handles
 * data input/output from the database relating
 * to "Segments"
 *
 *
 * @package API
 * @subpackage Segment_API
 */
class Segment_API extends API
{
	/**
	 * Segment's Class Variables (Publicly accessible)
	 */
		/**
		 * segmentid
		 * @var Int Segment ID
		 */
		var $segmentid = null;

		/**
		 * segmentname
		 * @var String Segment name
		 */
		var $segmentname = null;

		/**
		 * createdate
		 * @var Int Segment's create date
		 */
		var $createdate = null;

		/**
		 * ownerid
		 * @var Int Segment's owner ID
		 */
		var $ownerid = null;

		/**
		 * searchinfo
		 * @var Array Segment's search information
		 */
		var $searchinfo = null;
	/**
	 * -----
	 */




	/**
	 * A list of fields that are used by this API
	 * @var Array a list of field names used
	 * @access private
	 */
	var $_fieldList = array(
		'segmentname',
		'createdate',
		'ownerid',
		'searchinfo'
	);

	/**
	 * Primary key for the "segments" table
	 * @var String primary key column
	 * @access private
	 */
	var $_fieldPrimaryKey = 'segmentid';

	/**
	 * A list of fields that are sortable in the database
	 * @var Array a list of sortable field
	 * @access private
	 */
	var $_fieldSortable = array(
		'segmentname',
		'createdate'
	);

	/**
	 * Default sort field name
	 * @var String Default sort field name
	 * @access private
	 */
	var $_fieldDefaultSort = 'segmentname';



	/**
	 * COSNTRUCTOR
	 * @return Object Return this class
	 */
	function Segment_API()
	{
		$this->GetDb();
	}

	/**
	 * Load
	 * Load segment details to this class
	 *
	 * @param Int $segmentID ID of the segment to be loaded
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function Load($segmentID)
	{
		$record = $this->GetSegmentByID($segmentID);

		if (!$record || empty($record)) {
			return false;
		}

		foreach ($record as $key => $value) {
			$this->{$key} = $value;
		}

		return true;
	}

	/**
	 * GetSegmentByUserID
	 * This method will return a list of segments that are accessible by the specified user.
	 * If the parameter $userID is omitted, all segments will be returned
	 *
	 * The returned array will contains associated array,
	 * whereby the array index is the segment id
	 *
	 * @param Int $userID User ID (OPTIONAL, default NULL)
	 * @param Array $sortinfo An array of sorting information - what to sort by and what direction (OPTIONAL)
	 * @param Boolean $countonly Whether to only get a count of segments, rather than the information.
	 * @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	 * @param Mixed $perpage How many results to return (Integer or String) (max).
	 *
	 * @return Mixed Returns false if it couldn't retrieve segment information. Otherwise returns the count (if specified), or an array of segments.
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses API::_subQueryCapable()
	 * @uses Segment_API::_fieldDefaultSort
	 * @uses Segment_API::_FieldSortable
	 * @uses Db::AddLimit()
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	function GetSegmentByUserID($userID = null, $sortinfo = array(), $countonly = false, $start=0, $perpage=10)
	{
		$query = 'SELECT ' 
		       . ($countonly? 'COUNT(1) AS count' : '*') 
		       . ' FROM ' 
		       . SENDSTUDIO_TABLEPREFIX 
		       . 'segments';

		// Constraint by user's permission if user ID is specified
		if (!is_null($userID)) {
			$userID    = intval($userID);
			$user      = API_USERS::getRecordById($userID);
			$query    .= ' WHERE ownerid = ' . $userID;
			$subQuery  = 'SELECT resourceid FROM ' 
			           . SENDSTUDIO_TABLEPREFIX 
			           . "usergroups_access WHERE resourcetype='segments' AND "
			           . "groupid=" 
			           . $user->groupid;
			
			if ($this->_subqueryCapable()) {
				$query .= ' OR segmentid IN (' . $subQuery . ')';
			} else {
				$tempResult = $this->Db->Query($subQuery);
				
				if (!$tempResult) {
					list($error, $level) = $this->Db->GetError();
					
					trigger_error($error, $level);
					
					return false;
				}

				$tempRow = array();
				
				while (($row = $this->Db->Fetch($tempResult))) {
					array_push($tempRow, $row['resourceid']);
				}

				$this->Db->FreeResult($tempResult);

				if (count($tempRow) > 0) {
					$query .= ' OR segmentid IN (' . implode(',', $tempRow) . ')';
				}
			}
		}

		if (!$countonly) {
			// Add sorting to the query
			$sortField     = $this->_fieldDefaultSort;
			$sortDirection = 'asc';

			if (isset($sortinfo['SortBy']) && in_array($sortinfo['SortBy'], $this->_fieldSortable)) {
				$sortField = strtolower($sortinfo['SortBy']);
			}

			if ($sortField == 'segmentname') {
				$sortField = 'LOWER(segmentname)';
			}

			if (isset($sortinfo['Direction'])) {
				$sortDirection = strtolower(trim($sortinfo['Direction']));
			}

			$sortDirection  = ($sortDirection == 'up' || $sortDirection == 'asc')? ' ASC' : ' DESC';
			$query         .= ' ORDER BY ' . $sortField . $sortDirection;

			// Add limit to the query
			if ($perpage != 'all' && ($start || $perpage)) {
				$query .= $this->Db->AddLimit($start, $perpage);
			}
			
			// Query the database
			$lists  = array();
			$result = $this->Db->Query($query);
			
			if (!$result) {
				list($error, $level) = $this->Db->GetError();
				
				trigger_error($error, $level);
				
				return false;
			}
			
			while (($row = $this->Db->Fetch($result))) {
				$row['searchinfo']        = unserialize($row['searchinfo']);
				$lists[$row['segmentid']] = $row;
			}
			
			$this->Db->FreeResult($result);

			return $lists;
		} else {
			$result = $this->Db->Query($query);
			
			if (!$result) {
				list($error, $level) = $this->Db->GetError();
				
				trigger_error($error, $level);
				
				return false;
			}

			$row   = $this->Db->Fetch($result);
			$count = $row['count'];

			$this->Db->FreeResult($result);

			return $count;
		}
	}

	/**
	 * GetSegmentByID
	 * Fetches record from the database, and return an associative array of the record
	 *
	 * @param Int $segmentID ID of the segment to be fetched
	 * @return Mixed Returns an associative array of the record if exists, FALSE otherwise
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::GetError()
	 * @uses Db::Query()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	function GetSegmentByID($segmentID)
	{
		$result = $this->Db->Query('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'segments WHERE segmentid = ' . intval($segmentID));
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$row = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		if (empty($row)) {
			return array();
		}

		$row['searchinfo'] = unserialize($row['searchinfo']);

		return $row;
	}

	/**
	 * GetSegments
	 * This method will return a list of segments that are accessible by the specified user.
	 *
	 * @param Array $sortinfo An array of sorting information - what to sort by and what direction (OPTIONAL)
	 * @param Boolean $countonly Whether to only get a count of segments, rather than the information.
	 * @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	 * @param Mixed $perpage How many results to return (Int or String) (max).
	 *
	 * @return Mixed Returns false if it couldn't retrieve segment information. Otherwise returns the count (if specified), or an array of segments.
	 *
	 * @uses Segment_API::GetSegmentByUserID()
	 */
	function GetSegments($sortinfo = array(), $countonly=false, $start=0, $perpage=10)
	{
		return $this->GetSegmentByUserID(null, $sortinfo, $countonly, $start, $perpage);
	}

	/**
	 * Delete
	 * Delete a segment or multiple segments from the database.
	 *
	 * @param Mixed $segmentID Segment ID or an array of Segment ID to be deleted (Int|Array)
	 * @return Boolean True if it deleted the list, false otherwise.
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::GetError()
	 * @uses Db::Query()
	 * @uses Db::NumAffected()
	 */
	function Delete($segmentID)
	{
		if (!is_array($segmentID)) {
			$segmentID = array($segmentID);
		}

		$segmentID = array_map('intval', $segmentID);

		$query = 'DELETE FROM ' . SENDSTUDIO_TABLEPREFIX . 'segments WHERE segmentid IN (' . implode(', ', $segmentID) . ')';
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		if ($this->Db->NumAffected($result) != count($segmentID)) {
			return false;
		}

		return true;
	}

	/**
	 * Create
	 * Add segment record to the database
	 *
	 * This function accept an associated array that can be saved to the database.
	 * If it is not specified, it will use class variable instead.
	 *
	 * @param Array $record An associative array of the record to be saved (OPTIONAL)
	 * @return Boolean Returns TRUE if successfule, FALSE otherwise
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Quote()
	 *
	 * @todo test save if record is parsed as parameter
	 */
	function Create($record = null)
	{
		$escaped = array();

		if (!$this->_ValidateSegmentInformation()) {
			return false;
		}

		if (!$this->_PopulateQueryCache()) {
			return false;
		}

		foreach ($this->_fieldList as $tempKey) {
			if (!is_null($record)) {
				if (array_key_exists($tempKey, $record)) {
					$this->{$tempKey} = $record[$tempKey];
				}
			}

			$tempValue = $this->{$tempKey};

			if ($tempKey == 'searchinfo') {
				if (isset($tempValue['_cache'])) {
					unset($tempValue['_cache']);
				}

				$tempValue = serialize($tempValue);
			}

			if (is_null($tempValue)) {
				array_push($escaped, 'NULL');
			} elseif (is_int($tempValue)) {
				array_push($escaped, $tempValue);
			} else {
				array_push($escaped, "'" . $this->Db->Quote(strval($tempValue)) . "'");
			}
		}

		$result = $this->Db->Query(	'INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'segments(' . implode(',', $this->_fieldList) . ') '.
									'VALUES (' . implode(',', $escaped) . ')');
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		} else {
			$this->segmentid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'segments_sequence');
			if (!$this->segmentid) {
				trigger_error('Cannot get last inserted ID from segments sequence', E_USER_ERROR);
				return false;
			}
		}

		return true;
	}

	/**
	 * Save
	 * Update record in the database with the newer information
	 *
	 * This function accept an associated array that can be saved to the database.
	 * If it is not specified, it will use class variable instead.
	 *
	 * @param Array $record An associative array of the record to be saved (OPTIONAL)
	 * @return Boolean Returns TRUE if successfule, FALSE otherwise
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Quote()
	 * @uses Segment_API::_ValidateSegmentInformation()
	 * @uses Segment_API::_PopulateQueryCache()
	 *
	 * @todo test save if record is parsed as parameter
	 */
	function Save($record = null)
	{
		$escaped = array();
		$segmentid = $this->segmentid;

		if (!is_null($record)) {
			if (!array_key_exists('segmentid', $record)) {
				trigger_error('Segment ID cannot be empty when updating a record', E_USER_ERROR);
				return false;
			}

			$segmentid = $record['segmentid'];
		}

		if (!$this->_ValidateSegmentInformation()) {
			return false;
		}

		foreach ($this->_fieldList as $tempKey) {
			if (!is_null($record)) {
				if (array_key_exists($tempKey, $record)) {
					$this->{$tempKey} = $record[$tempKey];
				}
			}

			$tempValue = $this->{$tempKey};

			if ($tempKey == 'searchinfo') {
				if (isset($tempValue['_cache'])) {
					unset($tempValue['_cache']);
				}

				$tempValue = serialize($tempValue);
			}

			if (is_null($tempValue)) {
				array_push($escaped, $tempKey . ' = NULL');
			} elseif (is_int($tempValue)) {
				array_push($escaped, $tempKey . ' = ' . $tempValue);
			} else {
				array_push($escaped, $tempKey . " = '" . $this->Db->Quote(strval($tempValue)) . "'");
			}
		}

		$result = $this->Db->Query(	'UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'segments SET ' . implode(',', $escaped) . ' WHERE segmentid = ' . intval($segmentid));
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		return true;
	}

	/**
	* Copy
	* Copy segment details
	*
	* @param Int $segmentID Segment ID to copy.
	*
	* @return Array Returns an array of status (whether the copy worked or not) and a message to go with it. If the copy worked, then the message is the new ID.
	*/
	function Copy($segmentID)
	{
		$segmentID = intval($segmentID);
		if ($segmentID <= 0) {
			return array(false, 'No ID');
		}

		if (!$this->Load($segmentID)) {
			return array(false, 'Unable to load segment to be copied.');
		}

		$currentuser = GetUser();

		$this->segmentname = GetLang('CopyPrefix') . $this->segmentname;
		$this->ownerid = $currentuser->userid;
		$this->createdate = AdjustTime();

		$status = $this->Create();
		if (!$status) {
			return array(false, 'Unable to create new segment');
		}

		return array(true, $this->segmentid);
	}

	/**
	 * GetSubscribersCount
	 * Get subscriber record count described by segment
	 *
	 * @param Integer $segmentIDs ID of segment to be counted (OPTIONAL if you have load a segment to the class)
	 * @param Boolean $activeOnly Flag whether or not to filter out any "inactive" subscribers (OPTIONAL)
	 * @param Boolean $includeUnconfirmed Flag whether or not to filter out any "unconfirmed" subscribers (OPTIONAL)
	 * @return Mixed Returns the number of subscribers a segment is describing if sucessful, FALSE otherwise
	 */
	function GetSubscribersCount($segmentIDs = 0, $activeOnly = false, $includeUnconfirmed = true)
	{
		if ($segmentIDs != 0) {
			if (!$this->Load(intval($segmentIDs))) {
				trigger_error('Segment cannot be loaded', E_USER_ERROR);
				return false;
			}
		}

		$query = $this->GetSubscribersCountQueryString($activeOnly, $includeUnconfirmed);
		if (!$query) {
			return false;
		}

		$result = $this->Db->Query($query);
		if ($result === false) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$record = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		return intval($record['count']);
	}

	/**
	 * GetSubscribersQueryString
	 * Get subscriber query string. Segment must already been loaded first.
	 * @param Boolean $activeOnly Flag whether or not to filter out any "inactive" subscribers (OPTIONAL)
	 * @return Mixed Returns query string if successful, FALSE otherwise
	 */
	function GetSubscribersQueryString($activeOnly = false)
	{
		if (!$this->_PopulateQueryCache()) {
			trigger_error('Cannot generate query cache', E_USER_WARNING);
			return false;
		}

		if (!isset($this->searchinfo['_cache']['selectQuery'])) {
			trigger_error('Internal error: Select Query not found', E_USER_WARNING);
			return false;
		}

		$query = $this->searchinfo['_cache']['selectQuery'];
		if ($activeOnly) {
			$query .= ' AND (subscribers.unsubscribed=0 AND subscribers.bounced=0)';
		}

		return $query;
	}

	/**
	 * GetSubscribersCountQueryString
	 * Get count subscriber query string. Segment must already been loaded first.
	 * @param Boolean $activeOnly Flag whether or not to filter out any "inactive" subscribers (OPTIONAL)
	 * @param Boolean $includeUnconfirmed Flag whether or not to filter out any "unconfirmed" subscribers (OPTIONAL)
	 * @return Mixed Returns query string if successful, FALSE otherwise
	 */
	function GetSubscribersCountQueryString($activeOnly = false, $includeUnconfirmed = true)
	{
		if (!$this->_PopulateQueryCache()) {
			trigger_error('Cannot generate query cache', E_USER_WARNING);
			return false;
		}

		if (!isset($this->searchinfo['_cache']['countQuery'])) {
			trigger_error('Internal error: Count Query not found', E_USER_WARNING);
			return false;
		}

		$query = $this->searchinfo['_cache']['countQuery'];
		if ($activeOnly) {
			$query .= ' AND (subscribers.unsubscribed=0 AND subscribers.bounced=0)';
		}

		if (!$includeUnconfirmed) {
			$query .= " AND (subscribers.confirmed <> '0')";
		}

		return $query;
	}

	/**
	 * GetMailingListUsed
	 * Get mailing list ID used by segment(s). If parameter is NOT specified, it will get currently loaded Segment's information
	 *
	 * @param Mixed $segments Specify which segment we are going to get (Integer|Array|NULL) (OPTIONAL)
	 *
	 * @return Mixed Returns an array of list id used by segment, FALSE otherwise
	 *
	 * @uses Segment_API::_GetSearchInfo()
	 */
	function GetMailingListUsed($segments = null)
	{
		/**
		 * Get current segment info, and then return
		 */
			if (is_null($segments)) {
				if (!isset($this->searchinfo['Lists'])) {
					return false;
				}
				return $this->searchinfo['Lists'];
			}
		/**
		 * -----
		 */

		/**
		 * Get list from specified segment ID(s)
		 */
			$searchinfo = $this->_GetSearchInfo($segments);
			if (isset($searchinfo['Lists'])) {
				return $searchinfo['Lists'];
			} else {
				return false;
			}
		/**
		 * -----
		 */
	}

	/**
	 * GetRules
	 * Get segment rules used by segment(s). If parameter is NOT specified, it will get currently loaded Segment's information
	 *
	 * @param Mixed $segments Specify which segment we are going to get (Integer|Array|NULL) (OPTIONAL)
	 *
	 * @return Mixed Returns an array of list id used by segment, FALSE otherwise
	 *
	 * @uses Segment_API::_GetSearchInfo()
	 */
	function GetRules($segments = null)
	{
		/**
		 * Get current segment info, and then return
		 */
			if (is_null($segments)) {
				if (!isset($this->searchinfo['Rules'])) {
					return false;
				}
				return $this->searchinfo['Rules'];
			}
		/**
		 * -----
		 */

		/**
		 * Get rules from specified segment ID(s)
		 */
			$searchinfo = $this->_GetSearchInfo($segments);
			if (isset($searchinfo['Rules'])) {
				return $searchinfo['Rules'];
			} else {
				return false;
			}
		/**
		 * -----
		 */
	}

	/**
	 * GetSearchInfo
	 * Get segment search info. If parameter is NOT specified, it will get currently loaded Segment's information
	 *
	 * @param Mixed $segments Specify which segment we are going to get (Integer|Array|NULL) (OPTIONAL)
	 *
	 * @return Mixed Returns an array of list id used by segment, FALSE otherwise
	 *
	 * @uses Segment_API::_GetSearchInfo()
	 */
	function GetSearchInfo($segments = null)
	{
		// Get current segment info, and then return
		if (is_null($segments)) {
			return $this->searchinfo;
		}

		// Get search info from specified segment ID(s)
		return $this->_GetSearchInfo($segments);
	}

	/**
	 * RemoveUnavailableCustomFields
	 * This will remove unavailable custom fields from the loaded segment's rule
	 * This method is the public interface for performing this action.
	 *
	 * @param Array $availableCustomFields An array of available customfields
	 * @return Void Returns nothing, as it will save the rules directly in the class variables
	 *
	 * @uses Segment_API::_RemoveUnavailableCustomFields()
	 */
	function RemoveUnavailableCustomFields($availableCustomFields)
	{
		if (!is_array($availableCustomFields)) {
			return;
		}

		if (!is_array($this->searchinfo)) {
			return;
		}

		if (!array_key_exists('Rules', $this->searchinfo) || !is_array($this->searchinfo['Rules'])) {
			return;
		}

		$this->searchinfo['Rules'] = $this->_RemoveUnavailableCustomFields($availableCustomFields, $this->searchinfo['Rules']);
	}

	/**
	 * AppendRule
	 * Add another rule to the Segment.
	 *
	 * @param String $connector Rule connector ('and' or 'or')
	 * @param Array $rules An array of rules (which contains 'ruleName', 'ruleOperator' and 'ruleValues')
	 *
	 * @return Boolean Returns TRUE if succesful, FALSE oterwise
	 *
	 * @uses Segment_API::_PopulateQueryCache()
	 */
	function AppendRule($connector, $rules)
	{
		if (!is_array($this->searchinfo)) {
			$this->searchinfo = array();
		}

		if (!array_key_exists('Rules', $this->searchinfo)) {
			$this->searchinfo['Rules'] = array();
		}

		array_unshift($this->searchinfo['Rules'], array('type' => 'rule', 'connector' => $connector, 'rules' => $rules));
		$status =  $this->_PopulateQueryCache();

		// Remove appended rule if re-population of query cache failed
		if (!$status) {
			array_shift($this->searchinfo['Rules']);
			$this->_PopulateQueryCache();
		}

		return $status;
	}

	/**
	 * ReplaceRules
	 * Replace this segment's rule with the specified rule.
	 * Please NOTE that the rule must be formatted accordingly
	 *
	 * Rule is an array with the following key:
	 * - type => 'group' | 'rule'
	 * - connector => 'and' | 'or'
	 * - rules => array of Rules or array of "Rule Values"
	 *
	 * Rule Values is an array with the follwing key:
	 * - ruleName
	 * - ruleOperator
	 * - ruleValues
	 *
	 * @param Array $newrules An array of new rules
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses Segment_API::_PopulateQueryCache()
	 */
	function ReplaceRules($newrules)
	{
		if (!is_array($newrules)) {
			return false;
		}

		if (!is_array($this->searchinfo)) {
			$this->searchinfo = array();
		}

		if (!array_key_exists('Rules', $this->searchinfo)) {
			$this->searchinfo['Rules'] = array();
		}

		$oldrules = $this->searchinfo['Rules'];
		$this->searchinfo['Rules'] = $newrules;

		if (array_key_exists('Lists', $this->searchinfo)) {
			$status = $this->_PopulateQueryCache();
		} else {
			$status = true;
		}

		// Remove modification if re-generating query cache failed
		if (!$status) {
			$this->searchinfo['Rules'] = $oldrules;
			$this->_PopulateQueryCache();
		}

		return $status;
	}

	/**
	 * ReplaceLists
	 * Replace this segment's list with the specified lists.
	 *
	 * @param Array $newLists An array of new list IDs to be used
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses Segment_API::_PopulateQueryCache()
	 */
	function ReplaceLists($newLists)
	{
		if (!is_array($newLists)) {
			return false;
		}

		if (!is_array($this->searchinfo)) {
			$this->searchinfo = array();
		}

		if (!array_key_exists('Lists', $this->searchinfo)) {
			$this->searchinfo['Lists'] = array();
		}

		$oldlists = $this->searchinfo['Lists'];
		$this->searchinfo['Lists'] = $newLists;

		if (array_key_exists('Rules', $this->searchinfo)) {
			$status =  $this->_PopulateQueryCache();
		} else {
			$status = true;
		}

		// Remove modification if re-generating query cache failed
		if (!$status) {
			$this->searchinfo['Lists'] = $oldlists;
			$this->_PopulateQueryCache();
		}

		return $status;
	}

	/**
	 * _RemoveUnavailableCustomFields
	 * This is the actual implementation of removing unavailable customfields
	 *
	 * @param Array $availableCustomFields Available custom fields
	 * @param Array $rules Custom fields
	 * @return Array Returns processed customfields
	 *
	 * @uses Segment_API::_RemoveUnavailableCustomFields()
	 *
	 * @access private
	 */
	function _RemoveUnavailableCustomFields($availableCustomFields, $rules)
	{
		$processedRules = array();

		for ($i = 0, $j = count($rules); $i < $j; ++$i) {
			$each = $rules[$i];
			if (!array_key_exists('type', $each) || !array_key_exists('rules', $each)) {
				continue;
			}

			switch ($each['type']) {
				// If rule type is a "group", recurse
				case 'group':
					$each['rules'] = $this->_RemoveUnavailableCustomFields($availableCustomFields, $each['rules']);
				break;

				// If rule type is "rule", process it
				case 'rule':
					if (is_numeric($each['rules']['ruleName'])) {
						if (!in_array($each['rules']['ruleName'], $availableCustomFields)) {
							$each['rules'] = false;
						}
					}
				break;

				// Unknown type, remove
				default:
					$each['rules'] = false;
				break;
			}

			// Include if rule is valid
			if ($each['rules'] !== false) {
				array_push($processedRules, $each);
			}
		}

		return $processedRules;
	}

	/**
	 * _ValidateSegmentInformation
	 * Validate segment information. This method should be called before saving (creating/editing) segment information to the database.
	 * @return Boolean Returns TRUE if everything is validated, FALSE otherwise
	 * @access private
	 */
	function _ValidateSegmentInformation()
	{
		if (!array_key_exists('Lists', $this->searchinfo) || !is_array($this->searchinfo['Lists']) || count($this->searchinfo['Lists']) == 0) {
			trigger_error('"Lists" cannot be empty, and must be set in the searchinfo', E_USER_ERROR);
			return false;
		}

		if (!array_key_exists('Rules', $this->searchinfo) || !is_array($this->searchinfo['Rules']) || count($this->searchinfo['Rules']) == 0) {
			trigger_error('"Rules" cannot be empty, and must be set in the searchinfo', E_USER_ERROR);
			return false;
		}

		return true;
	}

	/**
	 * _PopulateQueryCache
	 * Process and populate query cache. It gets called whenever a record is saved (ie. created or edited).
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses Segment_API::$searchinfo
	 * @uses Subscriber_API
	 * @uses Subscriber_API::GenerateQueryFromSegmentRules()
	 *
	 * @access private
	 */
	function _PopulateQueryCache()
	{
		if (!class_exists('Subscribers_API', false)) {
			require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'subscribers.php');
		}

		$subscriberAPI = new Subscribers_API();
		$status = $subscriberAPI->GenerateQueryFromSegmentRules($this->searchinfo['Lists'], $this->searchinfo['Rules']);
		if ($status === false) {
			trigger_error('"Rules" cannot be processed', E_USER_WARNING);
			return false;
		}

		$this->searchinfo['_cache'] = array(
			'selectQuery' => $status['selectQuery'],
			'countQuery' => $status['countQuery']
		);

		return true;
	}

	/**
	 * _GetSearchInfo
	 * Get search info for segment.
	 *
	 * @param Mixed $segments IDs of the segments (Integer|Array)
	 * @return Mixed Returns an array of list id used by segment, FALSE otherwise
	 *
	 * @access private
	 */
	function _GetSearchInfo($segments)
	{
		if (!is_array($segments)) {
			$segments = array($segments);
		}

		$lists = array();
		$rules = array();
		$api = new Segment_API();
		foreach ($segments as $id) {
			$status = $api->Load(intval($id));
			if ($status === false) {
				return false;
			}

			$temp = $api->GetMailingListUsed();
			if (is_array($temp) && !is_null($temp)) {
				$lists = array_merge($lists, $temp);
			}

			$temp = $api->GetRules();
			if (is_array($temp) && !is_null($temp)) {
				$temp = array('type' => 'group', 'connector' => 'or', 'rules' => $temp);
				$rules = array_merge($rules, array($temp));
			}
		}
		unset($api);

		return array('Lists' => $lists, 'Rules' => $rules);
	}
}
