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
* add_autoresponder_pause
*
* Add a column 'pause' to the autoresponde table to indicate
* that the autoresponder should be paused (ie. stopped from sending out)
*
* @see Upgrade_API
*
* @package SendStudio
*/
class add_autoresponder_pause extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the add_autoresponder_pause upgrade
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		if ($this->ColumnExists('autoresponders', 'pause')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]autoresponders ADD COLUMN pause INT DEFAULT 0";
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query .= " AFTER active";
		}

		$status = $this->Db->Query($query);
		if (!$status) {
			return false;
		}

		return true;
	}
}
