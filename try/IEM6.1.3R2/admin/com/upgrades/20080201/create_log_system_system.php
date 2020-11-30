<?php
/**
* This file is part of the upgrade process.
*
* @package SendStudio
*/

/**
* Do a sanity check to make sure the upgrade api has been included.
*/
if (!class_exists('Upgrade_API', false)) {
	exit();
}

/**
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class create_log_system_system extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the query for the upgrade process
	* and returns the result from the query.
	* The calling function looks for a true or false result
	*
	* @return Mixed Returns true if the condition is already met (eg the column already exists).
	*  Returns false if the database query can't be run.
	*  Returns the resource from the query (which is then checked to be true).
	*/
	function RunUpgrade()
	{

		if ($this->TableExists('log_system_system')) {
			return true;
		}

		$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "log_system_system (
		  logid int not null primary key auto_increment,
		  logtype varchar(20),
		  logmodule varchar(100) NOT NULL default '',
		  logseverity char(1) NOT NULL default '4',
		  logsummary varchar(250) NOT NULL,
		  logmsg text NOT NULL,
		  logdate int NOT NULL default '0'
		) character set utf8 engine=innodb";

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "log_system_system (
			  logid serial NOT NULL,
			  logtype varchar(20),
			  logmodule varchar(100) NOT NULL default '',
			  logseverity char(1) NOT NULL default '4',
			  logsummary varchar(250) NOT NULL,
			  logmsg text NOT NULL,
			  logdate int NOT NULL default '0',
			  PRIMARY KEY  (logid)
			)";
		}
		$result = $this->Db->Query($query);
		return $result;
	}
}
