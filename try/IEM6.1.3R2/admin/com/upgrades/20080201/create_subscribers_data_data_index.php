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
class create_subscribers_data_data_index extends Upgrade_API
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

		if ($this->IndexExists('subscribers_data', 'data')) {
			return true;
		}

		$query = 'create index ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data_data_idx on ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data(data (255))';
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query = 'create index ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data_data_idx on ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data(data)';
		}
		$result = $this->Db->Query($query);
		return $result;
	}
}
