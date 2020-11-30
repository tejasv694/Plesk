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
	exit;
}

/**
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class create_list_subscriber_events_subscriberid_index extends Upgrade_API
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
		// If index has already exists, return
		if ($this->IndexExists('list_subscriber_events', array('subscriberid'))) {
			return true;
		}

		$status = $this->Db->Query("
			CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_events_subscriberid_idx
			ON " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_events(subscriberid)
		");

		if ($status === false) {
			return false;
		}

		return true;
	}
}
