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
class newsletters_fix_content extends Upgrade_API
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
		$ok = true;
		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "newsletters";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$htmlbody = html_entity_decode(stripslashes($row['htmlbody']));
			$htmlbody = str_replace(SENSTUDIO_BASE_APPLICATION_URL.'/temp/images', SENDSTUDIO_TEMP_URL.'/user', $htmlbody);

			$update_query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "newsletters SET name='" . $this->Db->Quote(stripslashes($row['name'])) . "', subject='" . $this->Db->Quote(stripslashes($row['subject'])) . "', htmlbody='" . $this->Db->Quote($htmlbody) . "', textbody='" . $this->Db->Quote(stripslashes($row['textbody'])) . "' WHERE newsletterid='" . $row['newsletterid'] . "'";
			$update_result = $this->Db->Query($update_query);
			if ($ok) {
				$ok = $update_result;
			}
		}

		return $ok;
	}
}
