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
class form_generate_modifydetails extends Upgrade_API
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
		require_once(SENDSTUDIO_API_DIRECTORY . '/forms.php');
		$api = new Forms_API(0, false);

		$api->Set('Db', $this->Db);

		$query = "SELECT formid FROM " . SENDSTUDIO_TABLEPREFIX . "forms WHERE formtype='m'";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$api->Load($row['formid']);
			$html = $api->GetHTML();
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "forms SET formhtml='" . $this->Db->Quote($html) . "' WHERE formid='" . $row['formid'] . "'";
			$update_result = $this->Db->Query($query);
		}
		return true;
	}
}
