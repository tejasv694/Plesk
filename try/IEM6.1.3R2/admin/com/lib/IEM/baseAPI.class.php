<?php
/**
 *
 * @todo all
 * @todo doc
 */
abstract class IEM_baseAPI
{
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
	abstract static public function getRecords($countonly = false, $condition = false, $limit = 0, $offset = 0, $sortby = false, $sortdesc = false);

	/**
	 * Get record by ID
	 * This method will fetch a record from the database.
	 *
	 * NOTE: An $id can be a composite of columns (ie. having more than one column for the primary key).
	 * If this is the case, you will need to pass an associative array for the $id parameter.
	 *
	 * NOTE: Each implementation will return different "baseRecord". For example API_USERS will return
	 * record_User object.
	 *
	 * NOTE: Each implementation may add extra parameters to the signature, but they need to be optional,
	 * and will still need to follow the guideline of this base method signature.
	 *
	 * @param mixed $id Record ID to fetch
	 * @return IEM_baseRecord|FALSE Returns base record if successful, FALSE otherwise
	 */
	abstract static public function getRecordByID($id);

	/**
	 * Delete record by ID
	 * This method will delete record from database
	 *
	 * NOTE: An $id can be a composite of columns (ie. having more than one column for the primary key).
	 * If this is the case, you will need to pass an associative array for the $id parameter.
	 *
	 * NOTE: Each implementation may add extra parameters to the signature, but they need to be optional,
	 * and will still need to follow the guideline of this base method signature.
	 *
	 * @param mixed $id ID of the record to be deleted
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	abstract static public function deleteRecordByID($id);

	/**
	 * Save record
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
	 */
	abstract static public function saveRecord(&$record);
}

/**
 *
 * @author hckurniawan
 * TODO doc
 */
class exception_IEM_baseAPI extends exceptionIEM
{
	const UNABLE_TO_QUERY_DATABASE = 1;
}
