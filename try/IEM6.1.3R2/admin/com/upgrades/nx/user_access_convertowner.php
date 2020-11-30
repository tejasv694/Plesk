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
class user_access_convertowner extends Upgrade_API
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
		// if only one user has access to the list, they are the "owner".
		$query = 'SELECT AdminID, ListID FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_lists GROUP BY ListID HAVING COUNT(ListID)=1';
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET ownerid='" . $row['AdminID'] . "' WHERE ListID='" . $row['ListID'] . "'";
			$update_result = $this->Db->Query($query);

			$delete_query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "allow_lists WHERE AdminID='" . $row['AdminID'] . "' AND ListID='" . $row['ListID'] . "'";
			$delete_result = $this->Db->Query($delete_query);
		}

		// more than one user has access to the list? Eek - make the "first" user the owner (most likely the admin user).
		// not a great solution but it's consistent.
		$query = 'SELECT AdminID, ListID FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_lists ORDER BY ListID, AdminID';
		$result = $this->Db->Query($query);
		$prev_listid = 0;
		$prev_adminid = 0;
		while ($row = $this->Db->Fetch($result)) {
			$listid = $row['ListID'];
			// already done something for this list? Keep going!
			if ($prev_listid == $listid) {
				continue;
			}

			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET ownerid='" . $row['AdminID'] . "' WHERE listid='" . $row['ListID'] . "'";

			$update_result = $this->Db->Query($query);
		}
		return true;
	}
}
