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
class user_permissions_change_subscriber extends Upgrade_API
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
		/**
		** NOTE **
		* For all permissions, they are separate queries because a union will truncate the field length
		* to the smallest field.
		* So 'add' union 'edit' becomes 'add' and 'edi'.
		*/

		/**
		* Expand 'manage subscriber' permissions into 'add', 'edit'.
		* Delete is handled separately.
		*/
		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'subscribers\', \'add\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=1';
		$result = $this->Db->Query($query);

		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'subscribers\', \'edit\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=1';
		$result = $this->Db->Query($query);

		/**
		* Convert 'removed subscriber' permissions
		*/
		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'subscribers\', \'delete\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=19';
		$result = $this->Db->Query($query);

		/**
		* Convert 'import subscriber' permissions
		*/
		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'subscribers\', \'import\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=4';
		$result = $this->Db->Query($query);

		/**
		* Convert 'export subscriber' permissions
		*/
		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'subscribers\', \'export\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=5';
		$result = $this->Db->Query($query);

		/**
		* Convert 'banned subscriber' permissions
		*/
		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'subscribers\', \'banned\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=6';
		$result = $this->Db->Query($query);
		return $result;
	}
}
