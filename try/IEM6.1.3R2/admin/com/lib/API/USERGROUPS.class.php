<?php

/**
 * This file contains API_USERS static class definition.
 *
 * @package interspire.iem.lib.api
 */

/**
 * USER API static class
 *
 * This class provides an encapsulation for accessing a collection of users and users related
 * information from the database.
 *
 * @package interspire.iem.lib.api
 *
 * @todo Move all related functionalities from Users_API to this class
 */
class API_USERGROUPS extends IEM_baseAPI
{
	// --------------------------------------------------------------------------------
	// Methods needed to be extended from the parent class
	// --------------------------------------------------------------------------------
	
	/**
	 * Get record list (or number of records)
	 * This method will fetch a list of records or the number of available records from the database
	 *
	 * NOTE: The parameter condition is an associative array, where the key correspond to the column name
	 * in the database.
	 *
	 * NOTE: If $limit parameter is NOT set (ie. set to 0), $offset will be ignored.
	 *
	 * NOTE: Both $limit and $offset is an absolute value (ie. It will be positive integer number)
	 *
	 * NOTE: $sortdesc parameter will be ignored when $sortby parameter is not set (ie. FALSE or empty)
	 *
	 * @param boolean $countonly When this is set to TRUE, it will only return the number of records available
	 * @param array $condition Conditions to be applied to the search (OPTIONAL, default = FALSE)
	 * @param integer $limit Maximum number of records returned, when 0 is specified no maximum limit is set (OPTIONAL, default = 0)
	 * @param integer $offset Starting record offset (OPTIONAL, default = 0)
	 * @param string $sortby Column name that will be used to sort result (OPTIONAL, default = FALSE)
	 * @param boolean $sortdesc When this parameter is set to TRUE, it will sort result descendingly (OPTIONAL, default - FALSE)
	 *
	 * @return array|integer|false Returns list of available records, the number of available records, or FALSE if it encounter any errors.
	 */
	static public function getRecords($countonly = false, $condition = false, $limit = 0, $offset = 0, $sortby = false, $sortdesc = false)
	{
		$limit = abs(intval($limit));
		$offset = abs(intval($offset));

		$db = IEM::getDatabase();

		$valid_conditions = array(
			'groupid' => 'groupid'
		);

		$query = '';
		$query_condition = '';

		if (!empty($condition) && is_array($condition)) {
			$query_condition = 'WHERE ';

			$columns = array_keys($condition);
			for ($i = 0, $j = count($columns); $i < $j; ++$i) {
				$condition_column = $columns[$i];

				if (!isset($valid_conditions[$condition_column])) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " -- The condition, {$condition_column}, is not listed as a valid condition.", E_USER_WARNING);
					return false;
				}

				$condition_column = $valid_conditions[$condition_column];
				$condition_value = $condition[$columns[$i]];

				if (!is_numeric($condition_value)) {
					$condition_value = "'" . $db->Quote($condition_value) . "'";
				}

				$query_condition .= "{$condition_column} = {$condition_value}";
				if (($i + 1) < $j) {
					$query_condition .= ' AND ';
				}
			}
		}

		if ($countonly) {
			$query = "
				SELECT	COUNT(1) AS counter
				FROM	[|PREFIX|]usergroups AS ug
				{$query_condition}
			";

		} else {
			$sub_query = "SELECT * FROM [|PREFIX|]usergroups {$query_condition}";

			if (!empty($sortby)) {
				$sub_query .= " ORDER BY " . $db->Quote($sortby) . ($sortdesc? ' ASC' : ' DESC');
			}

			if ($limit != 0) {
				$sub_query .= " LIMIT {$limit}";
				if ($offset != 0) {
					$sub_query .= " OFFSET {$offset}";
				}
			}

			$query = "
				SELECT	ug.*,
						tempuser.usercount,
						gp.area AS permissions__area,
						gp.subarea AS permissions__subarea,
						ga.resourcetype AS access__resourcetype,
						ga.resourceid AS access__resourceid

				FROM	({$sub_query}) AS ug
							LEFT JOIN (SELECT groupid, COUNT(1) AS usercount FROM [|PREFIX|]users GROUP BY groupid) AS tempuser
								ON ug.groupid = tempuser.groupid
							LEFT JOIN [|PREFIX|]usergroups_permissions AS gp
								ON ug.groupid = gp.groupid
							LEFT JOIN [|PREFIX|]usergroups_access AS ga
								ON ug.groupid = ga.groupid
			";
		}

		$rs = $db->Query($query);
		if (!$rs) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to query database: ' . $db->Error(), E_USER_WARNING);
			return false;
		}

		if ($countonly) {
			$temp = $db->FetchOne($rs, 'counter');
			$db->FreeResult($rs);
			return $temp;
		}

		$temp_unset_properties = array('permissions__area', 'permissions__subarea', 'access__resourcetype', 'access__resourceid');
		$records = array();
		$groupids = array();
		while ($row = $db->Fetch($rs)) {
			if (is_null($row['usercount'])) {
				$row['usercount'] = 0;
			}

			$tempIndex = array_search($row['groupid'], $groupids);

			if ($tempIndex === false) {
				$records[] = $row;
				$groupids[] = $row['groupid'];

				$tempIndex = count($records) - 1;
			}

			$tempRecord = &$records[$tempIndex];

			foreach ($temp_unset_properties as $each) {
				if (array_key_exists($each, $tempRecord)) {
					unset($tempRecord[$each]);
				}
			}

			if (!array_key_exists('permissions', $tempRecord) || !is_array($tempRecord['permissions'])) {
				$tempRecord['permissions'] = array();
			}

			if (!empty($row['permissions__area'])) {
				if (!array_key_exists($row['permissions__area'], $tempRecord['permissions'])) {
					$tempRecord['permissions'][$row['permissions__area']] = array();
				}

				if (!in_array($row['permissions__subarea'], $tempRecord['permissions'][$row['permissions__area']])) {
					$tempRecord['permissions'][$row['permissions__area']][] = $row['permissions__subarea'];
				}
			}

			if (!array_key_exists('access', $tempRecord) || !is_array($tempRecord['access'])) {
				$tempRecord['access'] = array();
			}

			if (!empty($row['access__resourcetype'])) {
				if (!array_key_exists($row['access__resourcetype'], $tempRecord['access'])) {
					$tempRecord['access'][$row['access__resourcetype']] = array();
				}

				if (!in_array($row['access__resourceid'], $tempRecord['access'][$row['access__resourcetype']])) {
					$tempRecord['access'][$row['access__resourcetype']][] = $row['access__resourceid'];
				}
			}

			unset($tempRecord);
		}

		$db->FreeResult($rs);
		return $records;
	}

	/**
	 * Get record by ID
	 * This method will fetch a record from the database.
	 *
	 * @param integer $id Record ID to fetch
	 * @return record_Users|FALSE Returns base record if successful, FALSE otherwise
	 */
	static public function getRecordByID($id)
	{
		$records = self::getRecords(false, array('groupid' => $id));
		if (empty($records)) {
			return false;
		}

		return $records[0];
	}


	/**
	 * Delete record by ID
	 * This method will delete record from database
	 *
	 * @param integer $id ID of the record to be deleted
	 *
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	static public function deleteRecordByID($id)
	{
		$db = IEM::getDatabase();

		$db->StartTransaction();

		$queries = array(
			"DELETE FROM [|PREFIX|]usergroups_access WHERE groupid = {$id} AND groupid NOT IN (SELECT groupid FROM [|PREFIX|]users)",
			"DELETE FROM [|PREFIX|]usergroups_permissions WHERE groupid = {$id} AND groupid NOT IN (SELECT groupid FROM [|PREFIX|]users)",
			"DELETE FROM [|PREFIX|]usergroups WHERE groupid = {$id}"
		);

		foreach ($queries as $query) {
			$status = $db->Query($query);
			if (!$status) {
				$db->RollbackTransaction();
				return false;
			}
		}

		$db->CommitTransaction();
		return true;
	}

	/**
	 * Save record
	 * 
	 * This method will create/edit record in the database
	 *
	 * NOTE: You can pass in an associative array or "record" object.
	 *
	 * NOTE: The action that is taken by the API (either create a new record or edit an existing one)
	 * will depends on the record that is passed in (ie. They have their primary key included or not)
	 *
	 * NOTE: The method will be able to transform the record passed in, by either adding new default value
	 * (or in the case of creating new record, a new id)
	 *
	 * @param array|baseRecord $record Record to be saved
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @todo ALL
	 */
	static public function saveRecord(&$record)
	{
		$db = IEM::getDatabase();

		if (!isset($record['groupid'])) {
			$record['groupid'] = 0;
		} else {
			$record['groupid'] = intval($record['groupid']);
		}

		if (isset($record['permissions']['system']) && is_array($record['permissions']['system']) && in_array('system', $record['permissions']['system'])) {
			$record['systemadmin'] = '1';
		}

		$main_record = $record;
		
		// permissions don't exist on the main record
		if (isset($main_record['permissions'])) {
			unset($main_record['permissions']);
		}
		
		// access doesn't exist on the main record
		if (isset($main_record['access'])) {
			unset($main_record['access']);
		}

		$obj         = new record_UserGroups($main_record);
		$main_record = $obj->getAssociativeArray();

		$db->StartTransaction();

		
		
		/*
		 * Save main usergroup record
		 */
		
		if (empty($main_record['groupid'])) {
			unset($main_record['groupid']);

			$main_record['createdate'] = time();

			$fields = array();
			$values = array();

			foreach ($main_record as $key => $value) {
				$fields[] = $key;

				if (is_null($value) || $value === '') {
					$values[] = 'NULL';
				} elseif (is_numeric($value)) {
					$values[] = $value;
				} else {
					$values[] = "'" . $db->Quote($value) . "'";
				}
			}

			$query = "INSERT INTO [|PREFIX|]usergroups(" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";

			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$query .= " RETURNING groupid";
			}

			$status = $db->Query($query);
			
			if (!$status) {
				$db->RollbackTransaction();
				
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to insert record: ' . $db->Error(), E_USER_WARNING);
				
				return false;
			}

			$new_id = 0;
			
			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$new_id = $db->FetchOne($status, 'groupid');
				$db->FreeResult($status);
			} else {
				$new_id = $db->LastId(SENDSTUDIO_TABLEPREFIX . 'usergroups_sequence');
			}

			$main_record['groupid'] = $record['groupid'] = $new_id;
			$record['createdate']   = $main_record['createdate'];
		} else {
			$id = $main_record['groupid'];

			// hacks to prevent db errors
			unset($main_record['groupid'], $main_record['createdate']);

			// more hacks so that the database doesn't complain about null values when
			// it is expecting an integer
			foreach ($main_record as $colName => &$colVal) {
				if (!$colVal && strpos($colName, 'limit_') === 0) {
					$colVal = 0;
				}
			}

			$status = $db->UpdateQuery('usergroups', $main_record, "groupid = {$id}", true);

			if (!$status) {
				$db->RollbackTransaction();
				
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot update record: ' . $db->Error(), E_USER_WARNING);
				
				return false;
			}
		}

		
		
		/*
		 * Save permissions
		 */
		
		// remove existing permissions since they are being overwritten
		$status = $db->Query("DELETE FROM [|PREFIX|]usergroups_permissions WHERE groupid = {$record['groupid']}");
		
		// if they weren't able to be deleted, rollback and trigger an error
		if (!$status) {
			$db->RollbackTransaction();
			
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot delete group permission records: ' . $db->Error(), E_USER_WARNING);
			
			return false;
		}

		// if there are permissions to be saved
		if (!empty($record['permissions'])) {
			$values = array();

			// format them
			foreach ($record['permissions'] as $area => $subarea) {
				foreach ($subarea as $each) {
					$values[] = $record['groupid'] . ", '" . $db->Quote($area) . "', '" . $db->Quote($each) . "'";
				}
			}

			// remove duplicates
			$values = array_unique($values);
			
			// insert them
			$status = $db->Query("INSERT INTO [|PREFIX|]usergroups_permissions (groupid, area, subarea) VALUES (" . implode('), (', $values) . ")");
			
			// rollback and trigger an error if they weren't able to be inserted
			if (!$status) {
				$db->RollbackTransaction();
				
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot save permission records: ' . $db->Error(), E_USER_WARNING);
				
				return false;
			}
		}

		
		
		/*
		 * Save access
		 */
		
		// delete all access records first since they are being overwritten
		$status = $db->Query("DELETE FROM [|PREFIX|]usergroups_access WHERE groupid = {$record['groupid']}");
		
		// if they weren't able to be deleted, rollback and trigger an error
		if (!$status) {
			$db->RollbackTransaction();
			
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot delete group access records: ' . $db->Error(), E_USER_WARNING);
			
			return false;
		}

		// if there are access permissions
		if (!empty($record['access'])) {
			$values = array();

			// format them
			foreach ($record['access'] as $resourcetype => $resoureid) {
				foreach ($resoureid as $each) {
					$values[] = $record['groupid'] . ", '" . $db->Quote($resourcetype) . "', " . intval($each);
				}
			}

			// make sure there are no duplicates
			$values = array_unique($values);
			
			// insert the access permissions
			$status = $db->Query("INSERT INTO [|PREFIX|]usergroups_access (groupid, resourcetype, resourceid) VALUES (" . implode('), (', $values) . ")");
			
			// if they weren't able to be inserted, rollback and trigger an error
			if (!$status) {
				$db->RollbackTransaction();
				
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot save access records: ' . $db->Error(), E_USER_WARNING);
				
				return false;
			}
		}

		$db->CommitTransaction();

		return true;
	}
}
