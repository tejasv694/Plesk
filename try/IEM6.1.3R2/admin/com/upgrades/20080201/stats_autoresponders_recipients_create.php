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
class stats_autoresponders_recipients_create extends Upgrade_API
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

		if (!$this->TableExists('stats_autoresponders_recipients')) {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders_recipients (
			  statid INT DEFAULT 0,
			  autoresponderid INT DEFAULT 0,
			  send_status CHAR(1),
			  recipient INT DEFAULT 0,
			  reason VARCHAR(20),
			  sendtime INT
			  )";

			if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
				$query .= " character set utf8 engine=innodb";
			}

			$result = $this->Db->Query($query);
		} else {
			$result = true;
		}

		if ($result) {
			if (!$this->IndexExists('stats_autoresponders_recipients', array('statid', 'autoresponderid', 'recipient'))) {
				$query = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders_recipients_stat_auto_recip ON " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders_recipients(statid, autoresponderid, recipient)";
				$result = $this->Db->Query($query);
			} else {
				$result = true;
			}
		}

		return $result;
	}
}
?>
