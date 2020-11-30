<?php
/**
*
* @package Interspire
* @subpackage Log
*/

define("INTERSPIRE_LOG_SEVERITY_SUCCESS", 1);
define("INTERSPIRE_LOG_SEVERITY_NOTICE",  2);
define("INTERSPIRE_LOG_SEVERITY_WARNING", 3);
define("INTERSPIRE_LOG_SEVERITY_ERROR",   4);

/**
* The database table schemas look like this. You will need to create them in your products install/upgrade wizard.
* Using this class requires the db class which can handle calling 'Insert', 'Update' and 'Delete'.
*
* The 'log_system_administrator' table is for admin actions, eg 'user 12345 edited product xyz'.
* The 'log_system_system' table is for logging of errors, notices and warnings (eg 'undefined variable xyz in file . on line .').
*
* For MySQL:
*
$query = "CREATE TABLE %%TABLEPREFIX%%log_system_administrator (
  logid int not null primary key auto_increment,
  loguserid int NOT NULL,
  logip varchar(30) NOT NULL default '',
  logdate int NOT NULL default '0',
  logtodo varchar(100) NOT NULL default '',
  logdata text NOT NULL
) character set utf8";

$query = "CREATE TABLE %%TABLEPREFIX%%log_system_system (
  logid int not null primary key auto_increment,
  logtype varchar(20),
  logmodule varchar(100) NOT NULL default '',
  logseverity char(1) NOT NULL default '4',
  logsummary varchar(250) NOT NULL,
  logmsg text NOT NULL,
  logdate int NOT NULL default '0'
) character set utf8";


* For PostgreSQL:
*
$query = "CREATE TABLE %%TABLEPREFIX%%log_system_administrator (
  logid serial NOT NULL ,
  loguserid int NOT NULL,
  logip varchar(30) NOT NULL default '',
  logdate int NOT NULL default '0',
  logtodo varchar(100) NOT NULL default '',
  logdata text NOT NULL,
  PRIMARY KEY  (logid)
)";

$query = "CREATE TABLE %%TABLEPREFIX%%log_system_system (
  logid serial NOT NULL ,
  logtype varchar(20),
  logmodule varchar(100) NOT NULL default '',
  logseverity char(1) NOT NULL default '4',
  logsummary varchar(250) NOT NULL,
  logmsg text NOT NULL,
  logdate int NOT NULL default '0',
  PRIMARY KEY  (logid)
)";

*
*/
class Interspire_Log
{

	/**
	* _validSeverities
	* The types of severities the log system can handle.
	* If an invalid log severity is passed in, an event is not going to be logged.
	*
	* @see _WriteSystemLog
	*/
	var $_validSeverities = array (
		"success" => INTERSPIRE_LOG_SEVERITY_SUCCESS,
		"warnings" => INTERSPIRE_LOG_SEVERITY_WARNING,
		"errors" => INTERSPIRE_LOG_SEVERITY_ERROR,
		"notices" => INTERSPIRE_LOG_SEVERITY_NOTICE
	);

	/**
	* _validLogTypes
	* The types of logs we are going to log.
	* By default it includes php errors and sql errors.
	* You can add more logtypes by using the SetLogTypes method.
	* This can then include product specific log types such as 'shipping' or 'blogs'.
	*
	* @see SetLogTypes
	*/
	var $_validLogTypes = array (
		'php',
		'sql',
	);

	/**
	* _dbClasses
	* This is an array of filenames in the db class system.
	* This is used when trying to work out where an error came from.
	* These files are ignored in the backtrace in case we use one of the helper functions
	* like 'FetchOne'.
	*
	* @see LogSQLError
	*/
	var $_dbClasses = array (
		'db.php',
		'mysql.php',
		'pgsql.php'
	);

	/**
	* _logtypes
	* The types of events we're going to log.
	* This is different to _validLogTypes and tells the log system which particular types of logs to store.
	*
	* @see _validLogTypes
	* @see SetLogTypes
	* @see _WriteSystemLog
	*/
	var $_logtypes = array();

	/**
	* _log_severities
	* Which log severities to store.
	* You may only want to store errors but not success messages.
	* This is set using the SetSeverities function.
	*
	* @see SetSeverities
	*/
	var $_log_severities = array();

	/**
	* GeneralLogging
	* Whether to enable general logging or not.
	* General logs store all types of logs according to _logtypes and _validLogTypes and _log_severities
	*
	* @see _WriteSystemLog
	* @see Interspire_Log
	*/
	var $GeneralLogging = true;

	/**
	* _system_log_entries
	* The number of system log entries added so we know whether PruneSystemLog needs to do anything.
	* If no entries have been made, there will be nothing to delete.
	* This is incremented when _WriteSystemLog is called.
	*
	* @see _WriteSystemLog
	* @see PruneSystemLog
	*/
	var $_system_log_entries = 0;

	/**
	* AdminLogging
	* Whether to enable admin logging or not.
	* This controls whether admin activities are logged or not, separate to system errors (php & sql errors).
	*
	* @see _WriteSystemLog
	* @see Interspire_Log
	*/
	var $AdminLogging = true;

	/**
	* _admin_log_entries
	* The number of admin log entries added so we know whether PruneAdminLog needs to do anything.
	* If no entries have been made, there will be nothing to delete.
	* This is incremented when LogAdminAction is called.
	*
	* @see LogAdminAction
	* @see PruneAdminLog
	*/
	var $_admin_log_entries = 0;

	/**
	* MaxGeneralLogSize
	* Used to keep the size of the general system log under control.
	*
	* @see SetGeneralLogSize
	* @see PruneSystemLog
	*/
	var $MaxGeneralLogSize = 0;

	/**
	* MaxAdminLogSize
	* Used to keep the size of the admin activity log under control.
	*
	* @see SetAdminLogSize
	* @see PruneAdminLog
	*/
	var $MaxAdminLogSize = 0;

	/**
	* _log_action_strings
	* The parts of the query string you want to log. This should be a particular thing, eg 'todo=' or 'action=' or multiple.
	* This is case sensitive.
	*
	* @see SetLogActionStrings
	* @see LogAdminAction
	*/
	var $_log_action_strings = array();

	/**
	* _db
	* A placeholder for the database class object.
	*
	* @see SetDb
	*/
	var $_db = null;

	/**
	* Interspire_Log
	* Sets up the log system ready for access.
	* By default it enables both general logging and admin activity logging.
	*
	* @param Boolean $general_logging Whether to enable general system logs or not. On by default.
	* @param Boolean $admin_logging Whether to enable admin activity logging or not. On by default.
	*
	* @see GeneralLogging
	* @see AdminLogging
	*
	* @return Void Doesn't return anything.
	*/
	function Interspire_Log($general_logging=true, $admin_logging=true)
	{
		$this->GeneralLogging = (int)$general_logging;
		$this->AdminLogging = (int)$admin_logging;
	}

	/**
	* SetLogActionStrings
	* Set an array of things to log from the query string.
	* <b>Example</b>
	* SetLogActionStrings(array('todo', 'action'));
	* This will capture whatever is in the todo= part of the query string and what is in the action= part.
	*
	* @param Array $strings_to_capture The parts of the query string you want to capture. This can be a single item or multiple.
	*
	* @return Void Doesn't return anything, just records which parts you want to log.
	*/
	function SetLogActionStrings($strings_to_capture=array())
	{
		if (!is_array($strings_to_capture)) {
			$strings_to_capture = array($strings_to_capture);
		}
		$this->_log_action_strings = array_merge($this->_log_action_strings, $strings_to_capture);
	}

	/**
	* SetGeneralLogSize
	* Sets the maximum size of the general system log.
	*
	* @param Int $size The maximum size the system log should be.
	*
	* @see MaxGeneralLogSize
	* @see PruneSystemLog
	*
	* @return Void Doesn't return anything, just sets the size.
	*/
	function SetGeneralLogSize($size = 0)
	{
		$this->MaxGeneralLogSize = (int)$size;
	}

	/**
	* SetAdminLogSize
	* Sets the maximum size of the admin activity log.
	*
	* @param Int $size The maximum size the admin activity log should be.
	*
	* @see MaxAdminLogSize
	* @see PruneAdminLog
	*
	* @return Void Doesn't return anything, just sets the size.
	*/
	function SetAdminLogSize($size = 0)
	{
		$this->MaxAdminLogSize = (int)$size;
	}

	/**
	* SetSeverities
	* Sets which severities to log.
	* This can either be an array of severities or if you pass in 'all' it will log everything.
	*
	* @param Mixed $severities If you pass in an array, only those particular severities are stored. If you pass in the string 'all', all severities are stored.
	*
	* @see _validSeverities
	* @see _log_severities
	*
	* @return Void Doesn't return anything, just sets the severities to log.
	*/
	function SetSeverities($severities=array())
	{
		if ($severities == 'all') {
			$severities = $this->_validSeverities;
		}
		$this->_log_severities = $severities;
	}

	/**
	* SetValidLogTypes
	* Set the valid types of events to log in the system log.
	* By default only php and sql errors are logged, but you can make it product specific by passing in an array of other events to log.
	* This is different to SetLogTypes which tells you exactly which types of events to store.
	* This function tells the system which types are valid.
	* For example, a product may have a valid log type of 'blog' or 'shipping', but not necessarily log that event type based on different settings.
	*
	* @param Mixed $log_types The extra types of logs to store. This can either be an array or an extra single type of event.
	*
	* @example SetValidLogTypes(array('blog', 'shipping', 'sendstudio'));
	* @example SetValidLogTypes('blog');
	*
	* @see _validLogTypes
	*
	* @return Void Doesn't return anything, just remembers the extra log types you want to store.
	*/
	function SetValidLogTypes($log_types=array())
	{
		/**
		* Make sure it's an array for array_merge to work.
		*/
		if (!is_array($log_types)) {
			$log_types = array($log_types);
		}

		$this->_validLogTypes = array_merge($this->_validLogTypes, $log_types);
	}

	/**
	* SetLogTypes
	* Sets the types of events to log.
	* This is different to SetValidLogTypes and tells the log system exactly which types of logs to store.
	* For example, a product may have a valid log type of 'blog' or 'shipping', but not necessarily log that event type based on different settings.
	* This cannot be called multiple times, so if you need to store multiple types of logs make sure you pass in an array rather than a singular item.
	*
	* @param Mixed $log_types The exact types of logs you want to store. This can be an array of types or a single type.
	*
	* @see _logtypes
	*
	* @return Void Doesn't return anything, just remembers the particular log types you want to store.
	*/
	function SetLogTypes($log_types=array())
	{
		if (!is_array($log_types)) {
			$log_types = array($log_types);
		}
		$this->_logtypes = $log_types;
	}

	/**
	* SetDb
	* Remembers the database so it can log the events.
	* If sql errors are valid log types as well, the database class also sets the Callback setting to point to this log system.
	*
	* @param Object $db_class A reference to the database class to remember in the log system.
	*
	* @see _db
	*
	* @return Boolean Returns false if no database object is passed in, otherwise remembers the object and returns true.
	*/
	function SetDb($db_class=null)
	{
		if ($db_class === null) {
			return false;
		}
		if (!is_object($db_class)) {
			return false;
		}

		$this->_db = &$db_class;
		if (in_array('sql', $this->_logtypes)) {
			$this->_db->ErrorCallback = array($this, 'LogSQLError');
		}
		return true;
	}

	/**
	* LogSQLError
	* Logs an sql error to the database
	* This is used as a callback function for the database class(es).
	* The message is maxed out at 70 characters
	* If possible, the query is traced back to where it was originally called from.
	*
	* @param String $message The return message from the database containing what was wrong with the query
	* @param String $query The actual query that was trying to be run.
	*
	* @see _dbClasses
	* @see _WriteSystemLog
	* @see INTERSPIRE_LOG_SEVERITY_ERROR
	*
	* @return Void Doesn't return anything, just logs the query and message.
	*/
	function LogSQLError($message, $query="")
	{
		$details = '';
		if (strlen($message) > 70) {
			$details = "<h5>".$message."</h5>";
			$message = substr($message, 0, 70)."...";
		}

		if ($query) {
			$details = "<h5>".'Query'.":</h5>";
			$details .= "<p>" . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . "</p>";
		}
		if (function_exists("debug_backtrace")) {
			$backtrace = debug_backtrace();
			array_shift($backtrace);
			$dbClasses = implode("|", array_map("preg_quote", $this->_dbClasses));
			$dbClasses = str_replace("/", "\\".DIRECTORY_SEPARATOR, $dbClasses);
			while (preg_match("#".$dbClasses."#i", $backtrace[0]['file'])) {
				if (count($backtrace) == 1) {
					break;
				}
				array_shift($backtrace);
			}
			if (isset($backtrace[0]['file'])) {
				$details .= '<h5>'.'Location:'.':</h5>' . $backtrace[0]['file'] . ' (Line ' . $backtrace[0]['line'] . ')';
			}
		}
		$this->_WriteSystemLog(INTERSPIRE_LOG_SEVERITY_ERROR, "sql", $message, $details);
	}

	/**
	* LogSystemSuccess
	* Logs a success message to the system.
	*
	* @param String $type The type of event message you are trying to store.
	* @param String $summary The summary of the message to save. Eg 'user 1 created product'.
	* @param String $message The full message to save. Eg 'user 1 created a new product called "X" on date "Y"'. If no message is provided, the summary is used instead.
	*
	* @see _validLogTypes
	* @see _logtypes
	* @See _WriteSystemLog
	* @see INTERSPIRE_LOG_SEVERITY_SUCCESS
	*
	* @return Void Doesn't return anything.
	*/
	function LogSystemSuccess($type, $summary, $message="")
	{
		if (!$message) {
			$message = $summary;
		}
		$this->_WriteSystemLog(INTERSPIRE_LOG_SEVERITY_SUCCESS, $type, $summary, $message);
	}

	/**
	* LogSystemError
	* Logs an error message to the system.
	*
	* @param String $type The type of event message you are trying to store.
	* @param String $summary The summary of the message to save.
	* @param String $message The full message to save. If no message is provided, the summary is used instead.
	*
	* @see _validLogTypes
	* @see _logtypes
	* @See _WriteSystemLog
	* @see INTERSPIRE_LOG_SEVERITY_ERROR
	*
	* @return Void Doesn't return anything.
	*/
	function LogSystemError($type, $summary, $message="")
	{
		if (!$message) {
			$message = $summary;
		}
		$this->_WriteSystemLog(INTERSPIRE_LOG_SEVERITY_ERROR, $type, $summary, $message);
	}

	/**
	* LogSystemWarning
	* Logs a warning message to the system. Mainly used when php warnings are generated.
	*
	* @param String $type The type of event message you are trying to store.
	* @param String $summary The summary of the message to save.
	* @param String $message The full message to save. If no message is provided, the summary is used instead.
	*
	* @see _validLogTypes
	* @see _logtypes
	* @See _WriteSystemLog
	* @see INTERSPIRE_LOG_SEVERITY_WARNING
	*
	* @return Void Doesn't return anything.
	*/
	function LogSystemWarning($type, $summary, $message="")
	{
		if (!$message) {
			$message = $summary;
		}
		$this->_WriteSystemLog(INTERSPIRE_LOG_SEVERITY_WARNING, $type, $summary, $message);
	}

	/**
	* LogSystemNotice
	* Logs a notice message to the system. Mainly used when php notices are generated.
	*
	* @param String $type The type of event message you are trying to store.
	* @param String $summary The summary of the message to save.
	* @param String $message The full message to save. If no message is provided, the summary is used instead.
	*
	* @see _validLogTypes
	* @see _logtypes
	* @See _WriteSystemLog
	* @see INTERSPIRE_LOG_SEVERITY_NOTICE
	*
	* @return Void Doesn't return anything.
	*/
	function LogSystemNotice($type, $summary, $message="")
	{
		if (!$message) {
			$message = $summary;
		}
		$this->_WriteSystemLog(INTERSPIRE_LOG_SEVERITY_NOTICE, $type, $summary, $message);
	}

	/**
	* _WriteSystemLog
	* Writes the log to the system if logging is enabled.
	* It checks whether the particular type of log you are trying to store is valid and one you have activated.
	* It checks whether the severity of the message you are trying to store is valid and one you have activated.
	*
	* @param String $severity The severity of the message you are trying to store
	* @param String $type The type of event you are trying to store. This is checked against _validLogTypes and _logtypes to make sure it's ok to log the message.
	* @param String $summary The summary of the message to store.
	* @param String $message The full message if applicable. If no message is supplied, the summary is used instead.
	*
	* @see @GeneralLogging
	* @see _validSeverities
	* @see _validLogTypes
	* @see _logtypes
	*
	* @return Void Doesn't return anything.
	*/
	function _WriteSystemLog($severity, $type, $summary, $message="")
	{
		if (!$message) {
			$message = $summary;
		}

		// Is system logging disabled?
		if (!$this->GeneralLogging) {
			return;
		}

		if (!in_array($severity, $this->_validSeverities)) {
			return;
		}

		$module = '';
		if (is_array($type)) {
			$module = $type[1];
			$type = $type[0];
		}

		if (!in_array($type, $this->_validLogTypes)) {
			return;
		}

		// Are we allowed to log messages of this type?
		if (!in_array($type, $this->_logtypes)) {
			return;
		}

		$this->_system_log_entries++;

		$logEntry = array(
			"logtype" => $type,
			"logmodule" => $module,
			"logseverity" => $severity,
			"logsummary" => $summary,
			"logmsg" => $message,
			"logdate" => time()
		);
		$this->_db->InsertQuery("log_system_system", $logEntry);
	}

	/**
	* LogAdminAction
	* Logs a particular admin action with as much detail as possible.
	* It logs the page and query string being used for the action and the ip, date and other details.
	* It uses an unlimited number of arguments and serializes all of that data into one entry.
	* The first argument has to be the userid of the user doing the particular action.
	*
	* It can also log parts of the request variable based on what you have previously set.
	* For example, 'action=Step1' or 'todo=editproduct' etc.
	* If more than one item is meant to be logged (and more than one is found), then they are separated with ' ; '.
	* For example:
	* todo=editproduct ; action=step2
	*
	* <b>Example</b>
	* LogAdminAction($admin_userid, $message_id, _POST['message'], $_POST['title']);
	*
	* @see _log_action_strings
	* @see SetLogActionStrings
	* @see AdminLogging
	*
	* @return Void Doesn't return anything.
	*/
	function LogAdminAction()
	{

		// Is admin logging disabled?
		if (!$this->AdminLogging) {
			return;
		}

		$args = func_get_args();

		$userid = array_shift($args);

		if (is_array($args)) {
			$args = serialize($args);
		}

		$todoList = array();
		$todo = '';
		foreach ($this->_log_action_strings as $k=>$item) {
			if (!isset($_REQUEST[$item])) {
				continue;
			}
			$todoList[] = $item . '=' . $_REQUEST[$item];
		}

		$todo = implode(' ; ', $todoList);

		$logEntry = array(
			"loguserid" => $userid,
			"logip" => $_SERVER['REMOTE_ADDR'],
			"logdate" => time(),
			"logtodo" => $todo,
			"logdata" => $args
		);

		$this->_admin_log_entries++;

		if($this->_db->InsertQuery("log_system_administrator", $logEntry)){
			return true;
		}else{
			return false;
		}
	}

	/**
	* PruneAdminLog
	* Prunes the admin log according to the logdate and based on the MaxAdminLogSize.
	* If admin logging is not enabled or MaxAdminLogSize is 0, this just returns.
	* Otherwise it works out how many records to delete and calls the database to delete that number of records.
	*
	* @see AdminLogging
	* @see MaxAdminLogSize
	*
	* @return Void Doesn't return anything, just prunes the number of log entries it needs to from the database.
	*/
	function PruneSystemLog()
	{
		// Is system logging disabled?
		if (!$this->GeneralLogging || $this->MaxGeneralLogSize == 0) {
			return;
		}

		/**
		* If no log entries were made, nothing to delete.
		*/
		if ($this->_system_log_entries == 0) {
			return;
		}

		$query = "SELECT COUNT(logid) FROM [|PREFIX|]log_system_system";
		$numEntries = $this->_db->FetchOne($query);
		if ($numEntries > $this->MaxGeneralLogSize) {
			$toDelete = $numEntries - $this->MaxGeneralLogSize;
			if ($toDelete <= 0) {
				return;
			}
			// Delete x oldest entries from the log
			$qry = ' ORDER BY logdate ASC';
			$this->_db->DeleteQuery('log_system_system', $qry, $toDelete);
		}
	}

	/**
	* PruneAdminLog
	* Prunes the admin log according to the logdate and based on the MaxAdminLogSize.
	* If admin logging is not enabled or MaxAdminLogSize is 0, this just returns.
	* Otherwise it works out how many records to delete and calls the database to delete that number of records.
	*
	* @see AdminLogging
	* @see MaxAdminLogSize
	*
	* @return Void Doesn't return anything, just prunes the number of log entries it needs to from the database.
	*/
	function PruneAdminLog()
	{
		// Is admin logging disabled?
		if (!$this->AdminLogging || $this->MaxAdminLogSize == 0) {
			return;
		}

		/**
		* If no log entries were made, nothing to delete.
		*/
		if ($this->_admin_log_entries == 0) {
			return;
		}


		$query = "SELECT COUNT(logid) FROM [|PREFIX|]log_system_administrator";
		$numEntries = $this->_db->FetchOne($query);
		if ($numEntries > $this->MaxAdminLogSize) {
			$toDelete = $numEntries - $this->MaxAdminLogSize;
			if ($toDelete <= 0) {
				return;
			}
			// Delete x oldest entries from the log
			$qry = ' ORDER BY logdate ASC';
			$this->_db->DeleteQuery('log_system_administrator', $qry, $toDelete);
		}
	}

	/**
	* trace
	* Print a friendly looking backtrace up to the last execution point.
	*
	* @param Boolean $die Do we want to stop all execution (die) after outputting the trace? By default we don't.
 	* @param Boolean $return Do we want to return the output instead of echoing it ? By default we do want it to be returned. If we do return the error, we don't die regardless of the first setting.
	*
	* @return Mixed Returns the trace if applicable, otherwise it is echoed out to the screen instead.
	*/
	function trace($die=false, $return=true)
	{
		if (!function_exists('debug_backtrace')) {
			$backtrace = "Backtrace is not available (function is disabled)<br/>";
			if (!$return) {
				echo $backtrace;
				if ($die === true) {
					die();
				}
			}
			return $backtrace;
		}

		$trace = debug_backtrace();
		$backtrace = "<table style=\"width: 100%; margin: 10px 0; border: 1px solid #aaa; border-collapse: collapse; border-bottom: 0;\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
		$backtrace .= "<thead><tr>\n";
		$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">File</th>\n";
		$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">Line</th>\n";
		$backtrace .= "<th style=\"border-bottom: 1px solid #aaa; background: #ccc; padding: 4px; text-align: left; font-size: 11px;\">Function</th>\n";
		$backtrace .= "</tr></thead>\n<tbody>\n";

		// Strip off last item (the call to this function)
		array_shift($trace);

		foreach ($trace as $call) {
			if (!isset($call['file'])) {
				$call['file'] = "[PHP]";
			}

			if (!isset($call['line'])) {
				$call['line'] = "&nbsp;";
			}

			if (isset($call['class'])) {
				$call['function'] = $call['class'].$call['type'].$call['function'];
			}

			$backtrace .= "<tr>\n";
			$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['file']}</td>\n";
			$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['line']}</td>\n";
			$backtrace .= "<td style=\"font-size: 11px; padding: 4px; border-bottom: 1px solid #ccc;\">{$call['function']}</td>\n";
			$backtrace .= "</tr>\n";
		}
		$backtrace .= "</tbody></table>\n";
		if (!$return) {
			echo $backtrace;
			if ($die === true) {
				die();
			}
		} else {
			return $backtrace;
		}
	}
}

	/**
	* HandlePHPErrors
	* This calls the log system to handle the logging of the messages.
	* If error_reporting is disabled this does nothing.
	* This is the function to call for set_error_handler to use to do all of the logging.
	* The GetLogSystem function is one you have to write yourself.
	*
	* <b>Example</b>
	*
	*	require('path/to/log/interspire_log.php');
	*	function GetLogSystem()
	*	{
	*		static $logsystem = null;
	*		if (is_null($logsystem)) {
	*			$logsystem = new Interspire_Log(true, false);
	*
	*			// log which parts of the query string?
	*			// can be an array or a singular item.
	*			$logsystem->SetLogActionStrings(array('todo', 'action', 'save'));
	*
	*			// add whatever extra log types are needed.
	*			// these are on top of the built in types of 'php' and 'sql'.
	*			$logsystem->SetValidLogTypes(array('notifications', 'blogs', 'shipping'));
	*
	*			// set the types of errors/reports we're actually going to save.
	*			$logsystem->SetLogTypes(array('sql','php'));
	*
	*			$db = &GetDatabase();
	*			// need to connect the database to the log system so it can do it's work.
	*			// it's handled by reference inside the SetDb method.
	*			$logsystem->SetDb($db);
	*
	*			$logsystem->SetSeverities('all');
	*		}
	*		return $logsystem;
	*	}
	*
	* 	// then set the error handler.
	*	set_error_handler('HandlePHPErrors');
	*
	*
	* @param Int $errno The error number we are logging. This is one of the defined constants like 'E_USER_ERROR'.
	* @param String $errstr The error message to log.
	* @param String $errfile The filename the error occurred in.
	* @param Int $errline The line number the error occurred on.
	*
	* @return Mixed Returns straight away if error_reporting is disabled. Otherwise it calls the log system to handle the error and then returns true so the error isn't output to the browser.
	*/
	function HandlePHPErrors($errno, $errstr, $errfile, $errline)
	{
		// Error reporting turned off (either globally or by @ before erroring statement)
		if (error_reporting() == 0) {
			return;
		}

		if (!defined('E_STRICT')) {
			define('E_STRICT', 2048);
		}

		if ($errno === E_STRICT) {
			return;
		}

		$logsystem = GetLogSystem();

		$msg = "$errstr in $errfile at $errline<br/>\n";
		$msg .= $logsystem->trace(false,true);

		// This switch uses case fallthrough's intentionally
		switch ($errno) {
			case E_USER_ERROR:
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
				$logsystem->LogSystemError('php', substr($errstr, 0, 250), $msg);
				exit(1);
			break;

			case E_USER_WARNING:
			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
				$logsystem->LogSystemWarning('php', substr($errstr, 0, 250), $msg);
			break;

			case E_USER_NOTICE:
			case E_NOTICE:
				$logsystem->LogSystemNotice('php', substr($errstr, 0, 250), $msg);
			break;

			case E_STRICT:
				//$logsystem->LogSystemNotice('php', substr($errstr, 0, 250), $msg);
			break;

			default:
				$logsystem->LogSystemNotice('php', substr($errstr, 0, 250), $msg);
			break;
		}

		/* Don't execute PHP internal error handler */
		return true;
	}

?>
