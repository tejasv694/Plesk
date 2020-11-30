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
class customfield_lists_insert extends Upgrade_API
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
		$query = 'INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'customfield_lists (fieldid, listid) SELECT f.fieldid, l.listid FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_fields f, ' . SENDSTUDIO_TABLEPREFIX . 'allow_lists l WHERE f.AdminID=l.AdminID';
		$result = $this->Db->Query($query);
		return $result;
	}
}
