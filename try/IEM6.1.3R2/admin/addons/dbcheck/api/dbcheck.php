<?php
/**
 * IEM Database Diagnostics Class
 *
 * This class will look at the existing schema files' and see what tables, table
 * columns and indexes are corrupt, missing or different.
 *
 * This has only been tested (and probably only works) with MySQL >= 4.
 *
 * Unique indexes that cannot be created due to duplicates can be created with:
 * ALTER IGNORE TABLE table_name ADD UNIQUE INDEX index_name (field1, field2, etc);
 *
 * For checking PgSQL indexes, use: SELECT * FROM pg_indexes WHERE tablename = 'table_name';
 */
class DatabaseCheck
{
	protected $db, $table_schema;

	/**
	 * Constructor
	 * Sets up the database connection.
	 *
	 * @return Void Does not return anything.
	 */
	public function __construct()
	{
		$this->db = IEM::getDatabase();
	}

	// API METHODS

	/**
	 * getTableList
	 *
	 * @return Array A list of tables based off the table schema that should be present in the system.
	 */
	public function getTableList()
	{
		$tables = $this->getExpectedSchema();
		$tables = array_keys($tables);
		sort($tables);
		return $tables;
	}

	/**
	 * checkTable
	 *
	 * @param String $table_name The name of the table to check.
	 * @param Boolean $fix Whether to fix the problems with the table or not.
	 *
	 * @return Array Details about the table check, including whether it's present, corrupt and details about missing indexes.
	 */
	public function checkTable($table_name, $fix = false)
	{
		$status = array(
			'present' => false,
			'corrupt' => true,
			'missing_indexes' => array(),
			'missing_columns' => array(),
		);
		$status['present'] = $this->tableExists($table_name);
		if (!$status['present']) {
			// We don't want to try and fix this. A missing table requires someone to look at it manually.
			return $status;
		}
		$status['corrupt'] = $this->tableCorrupt($table_name, $fix);
		if (!$status['corrupt']) {
			$status['missing_columns'] = $this->getMissingColumns($table_name);
		}
		// We may still check indexes if the table is corrupt.
		$status['missing_indexes'] = $this->getMissingIndexes($table_name, $fix);
		return $status;
	}

	/**
	 * runQueries
	 * Runs a series of queries.
	 *
	 * @param Array $queries The list of queries to run.
	 * @param Boolean $die_on_error Whether or not to die after encountering an error.
	 *
	 * @return Void Does not return anything.
	 */
	private function runQueries($queries, $die_on_error = false)
	{
		foreach ($queries as $query) {
			$result = $this->db->Query($query);
			if (!$result && $die_on_error) {
				echo $this->db->GetErrorMsg();
				die();
			}
		}
	}

	// INDEX METHODS

	/**
	 * getMissingIndexes
	 *
	 * @param String $table_name The name of the table to check.
	 * @param Boolean $fix Whether to attempt repair or not.
	 *
	 * @return Array A list of missing indexes for this table.
	 */
	private function getMissingIndexes($table_name, $fix = false)
	{
		$missing_indexes = $queries = array();
		// The $queries variable must be defined before we include this, or we'll die.
		require(IEM_PATH . '/install/schema.indexes.php');

		$index_queries = $this->processIndexQueries($queries);
		$missing_index_queries = array();

		foreach ($index_queries as $name=>$details) {
			if ($details['table'] != $table_name) {
				continue;
			}
			if (!$this->indexExists($name, $details['table'])) {
				$missing_indexes[] = $name;
				$missing_index_queries[] = $details['query'];
			}
		}

		if ($fix) {
			$this->runQueries($missing_index_queries);
			return $this->getMissingIndexes($table_name, false);
		} else {
			return $missing_indexes;
		}
	}

	/**
	 * processIndexQueries
	 *
	 * @param Array $queries A list of index creation queries.
	 *
	 * @return Array The index creation queries broken down into their parts: $index_name => [$table_name, $creation_query]
	 */
	private function processIndexQueries($queries)
	{
		$new_query_list = array();
		foreach ($queries as $query) {
			$query = str_replace('%%TABLEPREFIX%%', '[|PREFIX|]', $query);
			preg_match('/INDEX \[\|PREFIX\|\](\w+) ON \[\|PREFIX\|\](\w+)/i', $query, $matches);
			//var_dump($matches);
			$index_name = $matches[1];
			$table_name = $matches[2];
			$new_query_list[$index_name] = array('table' => $table_name, 'query' => $query);
		}
		return $new_query_list;
	}

	/**
	 * indexExists
	 *
	 * @param String $name The name of the index.
	 * @param String $table The table it applies to.
	 *
	 * @return Boolean True if the index exists, false if not.
	 */
	private function indexExists($name, $table)
	{
		switch (SENDSTUDIO_DATABASE_TYPE) {
			case 'mysql':
				$detect_sql = "SHOW INDEXES FROM [|PREFIX|]{$table}";
				break;
			case 'pgsql':
				$detect_sql = "SELECT indexname AS \"Key_name\" FROM pg_indexes WHERE indexname = '[|PREFIX|]{$name}'";
				break;
			default:
				die('Unsupported database type.');
		}
		$result = $this->db->Query($detect_sql);
		$indexes = array();
		// We need to loop through this since MySQL < 5 doesn't support "WHERE Key_name = 'blah'".
		while ($row = $this->db->Fetch($result)) {
			if ($row['Key_name'] == $this->db->TablePrefix . $name) {
				return true;
			}
		}
		return false;
	}

	// TABLE METHODS

	/**
	 * getExpectedSchema
	 * Loads the complete table schema into $this->table_schema if not already there and returns it.
	 *
	 * @return Array Associative array of the form table_name => fields_to_parse.
	 */
	private function getExpectedSchema()
	{
		if (is_array($this->table_schema)) {
			return $this->table_schema;
		}
		$queries = array();
		require(IEM_PATH . '/install/schema.' . SENDSTUDIO_DATABASE_TYPE . '.php');
		$this->table_schema = $this->processTableQueries($queries);
		return $this->table_schema;
	}

	/**
	 * tableExists
	 *
	 * @param String $table_name The name of the table to check.
	 *
	 * @return Boolean True if the table exists, otherwise false.
	 */
	private function tableExists($table_name)
	{
		switch (SENDSTUDIO_DATABASE_TYPE) {
			case 'mysql':
				$query = "SHOW TABLES LIKE '[|PREFIX|]{$table_name}'";
				break;
			case 'pgsql':
				$query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '[|PREFIX|]{$table_name}'";
				break;
			default:
				die('Unsupported database type.');
		}
		$check_table_name = $this->db->FetchOne($query);
		return ($check_table_name == $this->db->TablePrefix . $table_name);
	}

	/**
	 * tableCorrupt
	 *
	 * @param String $table_name The name of the table to check.
	 * @param Boolean $fix Whether to attempt repair or not.
	 *
	 * @return Boolean True if the table is corrupt, otherwise false.
	 */
	private function tableCorrupt($table_name, $fix = false)
	{
		switch (SENDSTUDIO_DATABASE_TYPE) {
			case 'mysql':
				$op = ($fix ? 'REPAIR' : 'CHECK');
				$query = "{$op} TABLE [|PREFIX|]{$table_name}";
				break;
			case 'pgsql':
				// Skip check here as it's unnecessary.
				return false;
				break;
			default:
				die('Unsupported database type.');
		}
		$row = $this->db->FetchRow($query);
		return ($row['Msg_text'] != 'OK'
			&& !strstr($row['Msg_text'], 'closed') // We don't care about the error "1 client is using or hasn't closed the table properly".
			&& !strstr($row['Msg_text'], 'support')); // InnoDB doesn't support this.
	}

	/**
	 * getMissingColumns
	 * Compares the expected schema with the actual table schema to check for missing columns.
	 *
	 * @param String $table_name The name of the table to check.
	 *
	 * @return Array A list of missing columns in $table_name.
	 */
	private function getMissingColumns($table_name)
	{
		$schema = $this->getExpectedSchema();
		$expected_cols = $this->parseFields($schema[$table_name]);
		$actual_cols = $this->getActualSchema($table_name);
		$missing_cols = array();
		foreach ($expected_cols as $name => $details) {
			if (!isset($actual_cols[$name])) {
				$missing_cols[] = $name;
			}
		}
		return $missing_cols;
	}

	/**
	 * processTableQueries
	 *
	 * @param Array $queries The table creation queries from the schema file.
	 *
	 * @return Array An associative array of tables in the form table_name => fields_to_parse.
	 */
	private function processTableQueries($queries)
	{
		$tables = array();
		foreach ($queries as $query) {
			$query = str_replace('%%TABLEPREFIX%%', '', $query);
			$m = preg_match('/CREATE TABLE ([^\s]+) \(\n(.+)\s+\)/ism', $query, $matches);
			if (!$m) {
				// It could be an INSERT statement.
				continue;
			}
			$tables[$matches[1]] = $matches[2];
		}
		return $tables;
	}

	/**
	 * Parses the field list used in a CREATE TABLE query.
	 *
	 * @return Array Field Name => details
	 */
	private function parseFields($fields)
	{
		$new_fields = array();
		$fields = explode(",\n", $fields);
		$fields = array_map('trim', $fields);
		//var_dump($fields);
		foreach ($fields as $field) {
			if (preg_match('/^(PRIMARY KEY|FOREIGN KEY|UNIQUE)/i', $field)) {
				continue;
			}
			preg_match('/([^\s]+)\s+([^\s]+)(\s*NOT NULL)?(\s*default [^\s]+)*/i', $field, $matches);
			$name = $matches[1];
			// get field type
			$type = strtolower($matches[2]);
			if ($type == 'int') {
				$type = 'int(11)';
			}
			// check if null is allowed
			$null = 'YES';
			if (isset($matches[3]) && !empty($matches[3])) {
				$null = 'NO';
			}
			// check default
			$default = null;
			if (isset($matches[4]) && !is_null($matches[4])) {
				$default = preg_replace('/\s*default\s*/i', '', $matches[4]);
			}
			$default = preg_replace('/[\'"]/', '', $default);
			if (strtolower($default) == 'null') {
				$default = null;
			}
			//var_dump($matches);
			//die();
			$new_fields[$name] = array(
				'type' => strtolower($type),
				'null?' => $null,
				'default' => $default,
				);
		}
		//var_dump($new_fields);
		return $new_fields;
	}

	/**
	 * getActualSchema
	 * Obtain the actual schema for table_name.
	 *
	 * @param String $table_name The name of the table to retrieve the schema of.
	 *
	 * @return Array The table schema in the form of column_name => [type, null?, default].
	 */
	private function getActualSchema($table_name)
	{
		$query = "DESCRIBE [|PREFIX|]{$table_name}";
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			// MySQL also supports information schema queries in versions >= 5.
			$query = "SELECT column_name AS \"Field\", data_type AS \"Type\", is_nullable AS \"Null\", column_default AS \"Default\" FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '[|PREFIX|]{$table_name}'";
		}
		$result = $this->db->Query($query);
		if (!$result) {
			return false;
		}
		// Acquire details of the columns in the table.
		$columns = array();
		while ($row = $this->db->Fetch($result)) {
			$columns[$row['Field']] = array(
				'type' => $row['Type'],
				'null?' => $row['Null'],
				'default' => $row['Default'],
				);
		}
		return $columns;
	}

}
