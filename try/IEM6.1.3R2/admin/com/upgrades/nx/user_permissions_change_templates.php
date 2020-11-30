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
class user_permissions_change_templates extends Upgrade_API
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
		* Expand 'manage templates' permissions into 'create', 'edit', 'delete' and 'approve'.
		* A user does NOT get 'global' permissions in the upgrade process.
		*/
		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'templates\', \'create\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=10';
		$result = $this->Db->Query($query);

		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'templates\', \'delete\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=10';
		$result = $this->Db->Query($query);

		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'templates\', \'edit\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=10';
		$result = $this->Db->Query($query);

		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_permissions (userid, area, subarea)';
		$query .= ' SELECT AdminID, \'templates\', \'approve\' FROM ' . SENDSTUDIO_TABLEPREFIX . 'allow_functions WHERE SectionID=10';
		$result = $this->Db->Query($query);

		$query = 'insert into ' . SENDSTUDIO_TABLEPREFIX . 'user_access (userid, area, id)';
		$query .= ' SELECT AdminID, \'templates\', templateid FROM ' . SENDSTUDIO_TABLEPREFIX . 'templates';
		$result = $this->Db->Query($query);

		return $result;
	}
}
