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
class form_customfields_fix_fieldorder extends Upgrade_API
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
		// add 'e'mail to the form_customfields table.
		$query = "insert into " . SENDSTUDIO_TABLEPREFIX . "form_customfields(formid, fieldid, fieldorder) select formid, 'e', 0 FROM " . SENDSTUDIO_TABLEPREFIX . "forms";
		$result = $this->Db->Query($query);

		// add 'cf' (choose format) to the form_customfields table.
		// we can't do this as a union query because mysql truncates the field names to be the smaller length, so this would become 'c' instead of 'cf'.
		$query = "insert into " . SENDSTUDIO_TABLEPREFIX . "form_customfields(formid, fieldid, fieldorder) select formid, 'cf', 0 FROM " . SENDSTUDIO_TABLEPREFIX . "forms";
		$result = $this->Db->Query($query);

		// add 'cl' (choose lists) to the customfields table where appropriate.
		// this query finds any forms that need it (they are associated with multiple lists)
		$query = "SELECT formid FROM " . SENDSTUDIO_TABLEPREFIX . "form_lists GROUP BY formid HAVING COUNT(formid) > 1";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$insert_query = "insert into " . SENDSTUDIO_TABLEPREFIX . "form_customfields(formid, fieldid, fieldorder) values ('" . $row['formid'] . "', 'cl', 0)";
			$insert_result = $this->Db->Query($insert_query);
		}
		return true;
	}
}
