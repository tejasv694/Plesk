<?php
/**
* This file handles oracle database connections, queries, procedures etc.
* Most functions are overridden from the base object.
*
* @version     $Id: oci8.php,v 1.0 2008-11-04 00:25:41 david Exp $
* @author David Chandra <david.chandra@interspire.com>
*
* @package Db
* @subpackage Oci8Db
*/

/**
* Include the base database class.
*/
require_once(dirname(__FILE__).'/db.php');

/**
* This is the class for the Oracle database system.
*
* @package Db
* @subpackage Oci8Db
*/
class Oci8Db extends Db
{

	/**
	* Is magic quotes runtime on ?
	*
	* @var Boolean
	*/
	var $magic_quotes_runtime_on = false;

	var $numtofetch = -1;
	var $offset = -1;

	/**
	* Constructor
	* Sets up the database connection.
	* Can pass in the hostname, username, password and database name if you want to.
	* If you don't it will set up the base class, then you'll have to call Connect yourself.
	*
	* @param String $hostname Name of the server to connect to.
	* @param String $username Username to connect to the server with.
	* @param String $password Password to connect with.
	* @param String $databasename Database name to connect to.
	*
	* @see Connect
	* @see GetError
	*
	* @return Mixed Returns false if no connection can be made - the error can be fetched by the Error() method. Returns the connection result if it can be made. Will return Null if you don't pass in the connection details.
	*/
	function Oci8Db($hostname='', $username='', $password='', $databasename='')
	{
		$this->magic_quotes_runtime_on = get_magic_quotes_runtime();

		if ($hostname && $username && $databasename) {
			$connection = $this->Connect($hostname, $username, $password, $databasename);
			return $connection;
		}
		return null;
	}

	/**
	* Connect
	* This function will connect to the database based on the details passed in.
	*
	* @param String $hostname Name of the server to connect to.
	* @param String $username Username to connect to the server with.
	* @param String $password Password to connect with.
	* @param String $databasename Database name to connect to.
	*
	* @see SetError
	*
	* @return False|Resource Returns the resource if the connection is successful. If anything is missing or incorrect, this will return false.
	*/
	function Connect($hostname=null, $username=null, $password=null, $databasename=null)
	{

		if ($hostname === null && $username === null && $password === null && $databasename === null) {
			$hostname = $this->_hostname;
			$username = $this->_username;
			$password = $this->_password;
			$databasename = $this->_databasename;
		}

		if ($hostname == '') {
			$this->SetError('No server name to connect to');
			return false;
		}

		if ($username == '') {
			$this->SetError('No username name to connect to server '.$hostname.' with');
			return false;
		}

		if ($databasename == '') {
			$this->SetError('No database name to connect to');
			return false;
		}

		if (!function_exists('oci_connect')) {
			$this->SetError('PHP Installation does not support oracle database, please add the oci8 extension to your php installation');
			return false;
		}
		$db = sprintf('(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=1521)) (CONNECT_DATA=(SERVICE_NAME = %s)))',
		$hostname, $databasename);

		$connection_result = @oci_connect($username, $password, $db);

		if (!$connection_result) {
			$this->SetError('Unable to connect to ORACLE Server: \''.$hostname.'\'');
			return false;
		}
		$this->connection = &$connection_result;

		$this->_hostname = $hostname;
		$this->_username = $username;
		$this->_password = $password;
		$this->_databasename = $databasename;

		// set the date format as ISO date format
		$this->Query("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

		// set the session to in-case sensitive search string
		$this->Query("ALTER SESSION SET NLS_SORT=BINARY_CI");
		$this->Query("ALTER SESSION SET NLS_COMP=LINGUISTIC");

		return $this->connection;
	}

	function Query ($query='')
	{
		$query = str_replace('`', '', $query);

		// add limit implementation
		if ($this->numtofetch >= 0) {
			$totalToFetch = $this->numtofetch;
			if ($this->offset != -1) {
				$totalToFetch += $this->offset;
			}
			$query = 'SELECT * FROM (SELECT X.*, ROWNUM as R FROM ('.$query.') X WHERE ROWNUM <= '.$totalToFetch.') WHERE R >= ' . $this->offset . ' ';
		} else if ($this->offset > 0) {
			$query = 'SELECT * FROM ( '.$query.' ) WHERE ROWNUM <= ' . $this->offset . ' ';
		}

		$resource = oci_parse($this->connection, $query);
		if (!$resource) {
			$e = oci_error($this->connection);
			$this->SetError($e['message']);
			return false;
		}

		$result = oci_execute($resource);
		if (!$result) {
			$e = oci_error($resource);
			$this->SetError($e['message']);
			return false;
		}

		$this->offset = $this->numtofetch = -1;
		return $resource;
	}

	/**
	 * Build and execute a database insert query from an array of keys/values.
	 *
	 * @param string The table to insert into.
	 * @param array Associative array of key/value pairs to insert.
	 * @param bool TRUE to interpret NULL as being database NULL, FALSE to mean an empty string
	 * @return mixed Insert ID on successful insertion, false on failure.
	 */
	function InsertQuery($table, $values, $useNullValues=false)
	{
		$keys = array_keys($values);
		$fields = implode($this->EscapeChar.",".$this->EscapeChar, $keys);
		$lobVals = array();
		$lobDesc = array();
		foreach ($keys as $key) {

			if (strlen($values[$key]) > 4000) {
				$lobVals[':'.$key] = $values[$key];
				$lobDesc[':'.$key] = oci_new_descriptor($this->connection, OCI_D_LOB);
				$values[$key] = ':'.$key;
			} else {
				if ($useNullValues) {
					if (is_null($values[$key])) {
						$values[$key] = " ";
					} else {
						$values[$key] = "'" . $this->Quote($values[$key]) . "'";
					}
				} else {
					$values[$key] = "'" . $this->Quote($values[$key]) . "'";
				}
			}
		}

		$values = implode(",", $values);
		$query = sprintf('INSERT INTO %1$s[|PREFIX|]%2$s%1$s (%1$s%3$s%1$s) VALUES (%4$s)', $this->EscapeChar, $table, $fields, $values);
		if ($this->TablePrefix !== null) {
			$query = str_replace("[|PREFIX|]", $this->TablePrefix, $query);
		} else {
			$query = str_replace("[|PREFIX|]", '', $query);
		}

		$resource = oci_parse($this->connection, $query);
		if (!$resource) {
			$e = oci_error($this->connection);
			$this->SetError($e['message']);
			return false;
		}

		foreach ($lobDesc as $k => $v) {
			oci_bind_by_name($resource, $k, $lobDesc[$k], -1, OCI_B_CLOB);
			$lobDesc[$k]->WriteTemporary($lobVals[$k]);
		}

		$result = oci_execute($resource);

		foreach ($lobDesc as $k => $v) {
			$lobDesc[$k]->close();
			$lobDesc[$k]->free();
		}


		if (!$result) {
			$e = oci_error($resource);
			$this->SetError($e['message']);
			return false;
		}

		if(oci_commit($this->connection)) {
			return $this->LastId($table . '_seq');
		}
		return false;
	}

	/**
	 * Build and execute a database update query from an array of keys/values.
	 *
	 * @param string The table to insert into.
	 * @param array Associative array containing key/value pairs to update.
	 * @param string The where clause to apply to the update
 	 * @param bool TRUE to interpret NULL as being database NULL, FALSE to mean an empty string
	 *
	 * @return boolean True on success, false on error.
	 */
	function UpdateQuery($table, $values, $where="", $useNullValues=false)
	{
		$fields = array();
		$lobVals = array();
		$lobDesc = array();
		foreach ($values as $k => $v) {

			if (strlen($v) > 4000) {
				$lobVals[':'.$k] = $v;
				$lobDesc[':'.$k] = oci_new_descriptor($this->connection, OCI_D_LOB);
				$v = ':'.$k;
			} else {
				if ($useNullValues) {
					if (is_null($v)) {
						$v = " ";
					} else {
						$v = "'" . $this->Quote($v) . "'";
					}
				} else {
					$v = "'" . $this->Quote($v) . "'";
				}
			}
			$fields[] = sprintf("%s=%s", $k, $v);
		}

		$fields = implode(",", $fields);



		if ($where != "") {
			$fields .= sprintf(" WHERE %s", $where);
		}

		$query = sprintf('UPDATE [|PREFIX|]%s SET %s', $table, $fields);

		if ($this->TablePrefix !== null) {
			$query = str_replace("[|PREFIX|]", $this->TablePrefix, $query);
		} else {
			$query = str_replace("[|PREFIX|]", '', $query);
		}

		$resource = oci_parse($this->connection, $query);
		if (!$resource) {
			$e = oci_error($this->connection);
			$this->SetError($e['message']);
			return false;
		}

		foreach ($lobDesc as $k => $v) {
			oci_bind_by_name($resource, $k, $lobDesc[$k], -1, OCI_B_CLOB);
			$lobDesc[$k]->WriteTemporary($lobVals[$k]);
		}

		$result = oci_execute($resource);

		foreach ($lobDesc as $k => $v) {
			$lobDesc[$k]->close();
			$lobDesc[$k]->free();
		}


		if (!$result) {
			$e = oci_error($resource);
			$this->SetError($e['message']);
			return false;
		}

		return oci_commit($this->connection);
	}

	/**
	* Fetch
	* This function will fetch a result from the result set passed in.
	*
	* @see Query
	* @see SetError
	*
	* @return Mixed Returns false if the result is empty. Otherwise returns the next result.
	*/
	function Fetch($resource=null)
	{
		if ($resource === null) {
			$this->SetError('Resource is a null object');
			return false;
		}
		if (!is_resource($resource)) {
			$this->SetError('Resource '.$resource.' is not really a resource');
			return false;
		}
		$tmpRow = array();
		$row = ($this->magic_quotes_runtime_on) ? $this->StripslashesArray(oci_fetch_assoc($resource)) : oci_fetch_assoc($resource);
		if ($row && is_array($row) && sizeof($row)) {
			foreach ($row as $k => $v) {

				if (is_object($v) && strtolower(get_class($v)) == 'oci-lob') {
					$lob = oci_result($resource,$k);
					$lobVal = $lob->load();
					$tmpRow[strtolower($k)] = $lobVal;
				} else {
					$tmpRow[strtolower($k)] = $v;
				}
			}
		}
		return $tmpRow;
	}

	/**
	* CheckSequence
	*
	* Checks to make sure a sequence doesn't have multiple entries.
	*
	* @return True Oracle doesn't have an issue with being able to have multiple id's in a sequence.
	*/
	function CheckSequence()
	{
		return true;
	}

	/**
	* Concat
	* Concatentates multiple strings together. This method is oracle specific. It doesn't matter how many arguments you pass in, it will handle them all.
	* If you pass in one argument, it will return it straight away.
	* Otherwise, it will use the oracle specific CONCAT function to put everything together and return the new string.
	*
	* @return String Returns the new string with all of the arguments concatenated together.
	*/
	function Concat()
	{
		$num_args = func_num_args();
		if ($num_args < 1) {
			return func_get_arg(0);
		}
		$all_args = func_get_args();
		$returnstring = implode(' || ', $all_args);
		return $returnstring;
	}

	/**
	* CountResult
	* Returns the number of rows returned for the resource passed in
	*
	* @param String $resource The result from calling Query
	*
	* @see Query
	* @see SetError
	*
	* @return Int Number of rows from the result
	*/
	function CountResult($resource=null)
	{
		if (is_null($resource)) {
			$this->SetError('Query string is a blank string.');
			return false;
		}
		if (is_resource($resource)) {
			$this->SetError('Resource '.$resource.' is not really a resource');
			return false;
		}

		$query = "SELECT COUNT(*) AS num_rows FROM($resource)";
		$result = $this->Query($query);
		$count = $this->FetchOne($result);
		return $count;
	}

	/**
	* Disconnect
	* This function will disconnect from the database handler passed in.
	*
	* @param String $resource Resource to disconnect from
	*
	* @see SetError
	*
	* @return Boolean If the resource passed in is not valid, this will return false. Otherwise it returns the status from pg_close.
	*/
	function Disconnect($resource=null)
	{
		if ($resource === null) {
			$this->SetError('Resource is a null object');
			return false;
		}
		if (!is_resource($resource)) {
			$this->SetError('Resource '.$resource.' is not really a resource');
			return false;
		}
		$close_success = oci_close($resource);
		if ($close_success) {
			$this->connection = null;
		}
		return $close_success;
	}

	/**
	* FreeResult
	* Frees the result from memory.
	*
	* @param String $resource The result resource you want to free up.
	*
	* @return Boolean Whether freeing the result worked or not.
	*/
	function FreeResult($resource=null)
	{
		if ($resource === null) {
			$this->SetError('Resource is a null object');
			return false;
		}
		if (!is_resource($resource)) {
			$this->SetError('Resource '.$resource.' is not really a resource');
			return false;
		}
		$result = oci_free_statement($resource);
		return $result;
	}

	/**
	* LastId
	*
	* Returns the last insert id
	*
	* @return Int Returns mysql_insert_id from the database.
	*/
	function LastId($seq='')
	{
		if (!$seq) {
			return false;
		}
		$query = "SELECT ".$seq.".currval FROM dual";
		$nextid = $this->FetchOne($query);
		return $nextid;
	}

	/**
	* NextId
	* Fetches the next id from the sequence passed in
	*
	* @param String $sequencename Sequence Name to fetch the next id for.
	* @param String $idcolumn The name of the column for the id field. This is not used in pgsql.
	*
	* @see Query
	*
	* @return Mixed Returns false if there is no sequence name or if it can't fetch the next id. Otherwise returns the next id
	*/
	function NextId($sequencename=false, $idcolumn=false)
	{
		if (!$sequencename) {
			return false;
		}

		$query = "SELECT ".$sequencename.".nextval FROM dual";
		$nextid = $this->FetchOne($query);
		return $nextid;
	}

	/**
	* NumAffected
	*
	* Returns The number of rows affected by the query. If no tuple is affected, it will return 0.
	*
	* @param $result The result of a pg_ operation
	*
	* @return int
	*/
	function NumAffected($result=null)
	{
		return oci_num_rows($result);
	}

	/**
	* Quote
	* Quotes the string ready for database queries.
	*
	* @param Mixed $var Variable you want to quote ready for database entry.
	*
	* @return Mixed $var with quotes applied to it appropriately
	*/
	function Quote($var='')
	{
		if (is_string($var) || is_numeric($var) || is_null($var)) {
			return trim(str_replace("'","''",$var));
		} else if (is_array($var)) {
			return array_map(array($this, 'Quote'), $var);
		} else {
			trigger_error("Invalid type passed to DB quote", E_USER_ERROR);
			return false;
		}
	}

	/**
	* ResetSequence
	*
	* Resets a sequence to a new id.
	*
	* @param String $seq The sequence name to reset.
	* @param Int $newid The new id to set the sequence to.
	*
	* @return Boolean Returns true if the sequence is reset, otherwise false.
	*/
	function ResetSequence($seq='', $newid=0)
	{
		if (!$seq) {
			return false;
		}

		$newid = (int)$newid;
		if ($newid <= 0) {
			return false;
		}

		$query = "DROP SEQUENCE " . $seq . " ";
		$result = $this->Query($query);
		if (!$result) {
			return false;
		}
		$query = "CREATE SEQUENCE " . $seq . " START WITH $newid INCREMENT BY 1 NOCYCLE ";
		$result = $this->Query($query);
		if (!$result) {
			return false;
		}
		return $this->CheckSequence;
	}

	/**
	* OptimizeTable
	*
	* Runs "optimize" over the tablename passed in. This is useful to keep the database reasonably speedy.
	*
	* @param String $tablename The tablename to optimize.
	*
	* @see Query
	*
	* @return Always return true in Oracle.
	*/
	function OptimizeTable($tablename='')
	{
		return true;
	}

	/**
	 * CleanFullTextString
	 * This is not used in oracle as oracle already have user friendly fulltext searching
	 *
	 * @param String $searchstring The string you wish to clean.
	 * @return String The formatted string
	 */
	function CleanFullTextString($searchstring)
	{
		return $searchstring;
	}

	/**
	* AddLimit
	* This function creates the SQL to add a limit clause to an sql statement.
	*
	* @param Int $offset Where to start fetching the results
	* @param Int $numtofetch Number of results to fetch
	*
	* @return String The string to add to the end of the sql statement
	*/
	function AddLimit($offset=-1, $numtofetch=-1)
	{
		$this->offset = $offset;
		$this->numtofetch = $numtofetch;
		return ' ';
	}

	/**
	* FullText
	* Fulltext works out how to handle full text searches. Returns an sql statement to append to enable full text searching.
	*
	* @param Mixed $fields Fields to search against. This can be an array or a single field.
	* @param String $searchstring String to search for against the fields
	* @param Bool $booleanmode In MySQL, is this search in boolean mode ?
	*
	* @return Mixed Returns false if either fields or searchstring aren't present, otherwise returns a string to append to an sql statement.
	*/
	function FullText($fields=null, $searchstring=null, $booleanmode=false)
	{
		if ($fields === null || $searchstring === null) {
			return false;
		}
		$query = '';
		$subqueries = array();
		$counter = 1;
		foreach ($fields as $field) {
			if ($booleanmode) {
				$subqueries[]= ' CONTAINS('.$field.', \''.$this->Quote($searchstring).'\', '.$counter++.') > 0 ';
			} else {
				$subqueries[]= ' CONTAINS('.$field.', \''.$this->Quote($searchstring).'\', '.$counter++.') > 0 ';
			}
		}
		$query = implode(' OR ', $subqueries);

		return $query;
	}

	/**
	* Update FullText index
	*
	* This update the database FullText index, only valid for Oracle Database. Other Database will always return true.
	*
	* @param Array	$tables An array of the tables that need to be updated
	* @return Boolean True if update process success. Otherwise, return False
	*/
	function UpdateFullTextIndex($tables, $fulltexts = '')
	{
		if (is_array($fulltexts)) {
			foreach ($fulltexts as $fulltext) {
				if( !$this->Query("BEGIN CTX_DDL.SYNC_INDEX('$fulltext'); END;"))
				{
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	* FetchOne
	* Fetches one item from a result and returns it.
	*
	* @param String $result Result to fetch the item from.
	* @param String $item The item to look for and return.
	*
	* @see Fetch
	*
	* @return Mixed Returns false if there is no result or item, or if the item doesn't exist in the result. Otherwise returns the item's value.
	*/
	function FetchOne($result=null, $item=null)
	{
		if ($result === null) {
			return false;
		}
		if (!is_resource($result)) {
			$result = $this->Query($result);
		}
		$row = $this->Fetch($result);
		if (!$row) {
			return false;
		}
		if ($item === null) {
			foreach ($row as $k => $v) {
				if (strtolower($k) != 'r') {
					$item = $k;
					break;
				}
			}

		}
		if (!isset($row[$item])) {
			return false;
		}
		if($this->magic_quotes_runtime_on) {
			$row[$item] = stripslashes($row[$item]);
		}
		return $row[$item];
	}

	/**
	* SubString
	* Get the substring from a specified string. The base class returns nothing - it needs to be overridden for each database type.
	*
	* @param String $str The target string.
	* @param Int $from The position that the substring started (1 = the first character in the string)
	* @param Int $len The length of the string that we would like to retrieve, start from the $from param
	*
	* @return String If all the param is valid, this will return the substring SQL string. Otherwise, it will return an empty string.
	*/
	function SubString($str = '', $from = 1, $len = 1)
	{
		if ($str == '') {
			return '';
		}
		return " SUBSTR(".$this->Quote($str).", $from, $len) ";
	}
}