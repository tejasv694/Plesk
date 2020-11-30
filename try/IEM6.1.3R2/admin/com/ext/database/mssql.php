<?php
/**
* This file handles mssql database connections, queries, procedures etc.
* Most functions are overridden from the base object.
*
* @version     $Id: mssql.php,v 1.31 2008-02-14 00:25:41 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package Db
* @subpackage MsSQLDb
*/

/**
* Include the base database class.
*/
require_once(dirname(__FILE__).'/db.php');

/**
* This is the class for the MsSQL database system.
*
* @package Db
* @subpackage MsSQLDb
*/
class MsSQLDb extends Db
{

	// David Added Keep track of the limit/offset
	var $numtofetch = -1;
	var $offset = -1;
	var $topKeyword = 'TOP';


	/**
	* Is magic quotes runtime on ?
	*
	* @var Boolean
	*/
	var $magic_quotes_runtime_on = false;

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
	function MsSQLDb($hostname='', $username='', $password='', $databasename='')
	{
		if( version_compare(PHP_VERSION, '4.3.0', '>=') ) {
			ini_set('mssql.datetimeconvert',0);
		}

		$this->magic_quotes_runtime_on = get_magic_quotes_runtime();
		$this->numtofetch = -1;
		$this->offset = -1;

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

		if (!function_exists('mssql_connect')) {
			$this->SetError('PHP Installation does not support mssql, please add the mssql extension to your php installation');
			return false;
		}

		$connection_result = @mssql_connect($hostname, $username, $password);

		if (!$connection_result) {
			$this->SetError('Unable to connect to MS SQL Server: \''.$hostname.'\'');
			return false;
		}
		$this->connection = &$connection_result;

		$db_result = @mssql_select_db($databasename, $this->connection);
		if (!$db_result) {
			$this->SetError('Unable to select database \''.$databasename.'\': ');
			return false;
		}
		$this->_hostname = $hostname;
		$this->_username = $username;
		$this->_password = $password;
		$this->_databasename = $databasename;

		return $this->connection;
	}

	function Query ($query='')
	{
		if ($this->numtofetch >= 0) {
			$totalToFetch = $this->numtofetch;
			if ($this->offset != -1) {
				$totalToFetch += $this->offset;
			}
			$query = preg_replace('/(^\s*select\s+(distinctrow|distinct)?)/i','\\1 ' . $this->topKeyword . ' ' . $totalToFetch . ' ', $query);
		}
		$query = str_replace('`', '', $query);

		if ($this->TablePrefix !== null) {
			$query = str_replace("[|PREFIX|]", $this->TablePrefix, $query);
		} else {
			$query = str_replace("[|PREFIX|]", '', $query);
		}

		$result = mssql_query($query);
		if (!$result) {
			return false;
		}

		if ($this->offset >= 0 && $this->CountResult($result) > 0) {
			mssql_data_seek  ( $result, $this->offset);
		}
		$this->offset = $this->numtofetch = -1;
		return $result;
	}

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
		return ($this->magic_quotes_runtime_on) ? $this->StripslashesArray(mssql_fetch_assoc($resource)) : mssql_fetch_assoc($resource);
	}

	function AddLimit($offset=-1, $numtofetch=-1)
	{
		$this->offset = $offset;
		$this->numtofetch = $numtofetch;
		return ' ';
	}

	/**
	* CheckSequence
	*
	* Checks to make sure a sequence doesn't have multiple entries.
	*
	* @return True MsSQL doesn't have an issue with being able to have multiple id's in a sequence.
	*/
	function CheckSequence()
	{
		return true;
	}

	/**
	* FullText
	* Fulltext works out how to handle full text searches. Returns an sql statement to append to enable full text searching.
	*
	* @param Mixed $fields Fields to search against. This can be an array or a single field.
	* @param String $searchstring String to search for against the fields
	* @param Bool $booleanmode In MsSQL, is this search in boolean mode ?
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
		foreach ($fields as $field) {
			if ($booleanmode) {
				$subqueries[]= ' CONTAINS('.$field.', \''.$this->Quote($this->CleanFullTextString($searchstring)).'\') ';
			} else {
				$subqueries[]= ' FREETEXT('.$field.', \''.$this->Quote($searchstring).'\') ';
			}
		}
		$query = implode(' OR ', $subqueries);

		return $query;
	}

	/**
	 * CleanFullTextString
	 * Cleans and properly formats an incoming search query in to a string MsSQL will love to perform fulltext queries on.
	 * For example, the and/or words are replaced with correct boolean mode formats, phrases are supported.
	 *
	 * @param String $searchstring The string you wish to clean.
	 * @return String The formatted string
	 */
	function CleanFullTextString($searchstring)
	{
		$string = str_replace('"', '', $searchstring);
		$string = '"'.preg_replace('/([ ]+)(and not|and|or)([ ]+)/i','" \\2 "', $string).'"';
		return $string;
	}

	/**
	* Concat
	* Concatentates multiple strings together. This method is mssql specific. It doesn't matter how many arguments you pass in, it will handle them all.
	* If you pass in one argument, it will return it straight away.
	* Otherwise, it will use the mssql specific CONCAT function to put everything together and return the new string.
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
		$returnstring = implode(' + ', $all_args);
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
		if ($resource === null) {
			$this->SetError('Resource is a null object');
			return false;
		}
		if (!is_resource($resource)) {
			$resource = $this->Query($resource);
		}
		$count = mssql_num_rows($resource);
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
	* @return Boolean If the resource passed in is not valid, this will return false. Otherwise it returns the status from mssql_close.
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
		$close_success = mssql_close($resource);
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
		$result = mssql_free_result($resource);
		return $result;
	}

	/**
	* LastId
	*
	* Returns the last insert id
	*
	* @return Int Returns mssql_insert_id from the database.
	*/
	function LastId($seq='')
	{
		$query="SELECT @@IDENTITY as lastid";
		$result = $this->query($query);
		$row = $this->Fetch($result);
		return $row['lastid'];
	}


	/**
	* NumAffected
	*
	* Returns the number of affected rows on success.
	*
	* @param  mixed $null Placeholder for postgres compatability
	*
	* @return int
	*/
	function NumAffected($null)
	{
		return mssql_affected_rows($this->connection);
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
	* @return Mixed If no tablename is passed in, this returns false straight away. Otherwise it calls Query and returns the result from that.
	*/
	function OptimizeTable($tablename='')
	{
		if (!$tablename) {
			return false;
		}
		$query = "OPTIMIZE TABLE " . $tablename;
		return $this->Query($query);
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

		$query = "DBCC CHECKIDENT ( '".$seq."',RESEED,.$newid.)";
		$result = $this->Query($query);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	* Update FullText index
	*
	* This update the database FullText index, only valid for SQL Server. Other Database will always return true.
	*
	* @param Array	$tables An array of the tables that need to be updated
	* @return Boolean True if update process success. Otherwise, return False
	*/
	function UpdateFullTextIndex($tables, $fulltexts = '')
	{
		if (is_array($tables)) {
			foreach ($tables as $table) {
				if( !$this->Query("IF OBJECTPROPERTY ( object_id('$table'),
	                    'TableHasActiveFulltextIndex') = 1
				BEGIN
    				EXEC sp_fulltext_table '$table', 'start_full'
				END"))
				{
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	* IsFulltextInstalled
	* Check if Full Text is Installed
	*
	* This will check if the full text is installed on the SQL Server. This only applies to MS SQL Server.
	*
	* @return Boolean True if Full Text Service is Installed. Otherwise, return False
	*/
	function IsFulltextInstalled()
	{
		$query = "SELECT fulltextserviceproperty('IsFulltextInstalled')";
		$result = $this->Query($query);
		$isInstalled = $this->FetchOne($result);
		if ($isInstalled) {
			return true;
		}
		$this->SetError('Full Text Service is not installed on your SQL Server.');
		return false;
	}

	/**
	* DeleteQuery
	* Formats a delete query based on the table name passed in and the query which could include a where clause and/or an order by etc.
	* If a query is not passed in, then this returns false as a safe-guard against accidentally deleting all of your table records.
	*
	* @param String $table The table you want to delete from.
	* @param String $query The query to restrict which entries to delete. If this is not supplied, the function returns false.
	* @param Int $limit This number will not be used for SQL Server, this might be done in sub query using $query params
	*
	* @see Query
	*
	* @return Mixed Returns false if no query is passed in, or if an invalid limit is supplied. Otherwise returns the result from Query
	*/
	function DeleteQuery($table='', $query=null, $limit=0)
	{
		if ($query === null) {
			return false;
		}

		$limit = intval($limit);

		if ($limit < 0) {
			return false;
		}

		$query = 'DELETE FROM [|PREFIX|]' . $table . ' ' . $query;

		return $this->Query($query);
	}

	/**
	* Version
	* Retrieves the version number of the DB.
	*
	* @return String The version number of the DB, e.g. "9.00.1399.06".
	*/
	function Version()
	{
		$result = $this->Query("SELECT SERVERPROPERTY('productversion')");
		return $this->FetchOne($result);
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

		foreach ($keys as $key) {

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

		$values = implode(",", $values);
		$query = sprintf('INSERT INTO %1$s[|PREFIX|]%2$s%1$s (%1$s%3$s%1$s) VALUES (%4$s)', $this->EscapeChar, $table, $fields, $values);

		if ($this->Query($query)) {
			return $this->LastId();
		}
		else {
			return false;
		}
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
		foreach ($values as $k => $v) {

			if ($useNullValues) {
				if (is_null($v)) {
					$v = " ";
				} else {
					$v = "'" . $this->Quote($v) . "'";
				}
			} else {
				$v = "'" . $this->Quote($v) . "'";
			}

			$fields[] = sprintf("%s=%s", $k, $v);
		}
		$fields = implode(", ", $fields);
		if ($where != "") {
			$fields .= sprintf(" WHERE %s", $where);
		}

		$query = sprintf('UPDATE [|PREFIX|]%s SET %s', $table, $fields);
		if ($this->Query($query)) {
			return true;
		}
		else {
			return false;
		}
	}
	
}
