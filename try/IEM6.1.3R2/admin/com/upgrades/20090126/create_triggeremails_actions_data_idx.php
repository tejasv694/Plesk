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
class create_triggeremails_actions_data_idx extends Upgrade_API
{
	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		// Can't check this index, so fail silently when index already exist
		$status = $this->Db->Query("
			CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions_data_datavaluestring_idx
			ON " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions_data(triggeremailsactionid, datakey, datavaluestring)
		");

		// Can't check this index, so fail silently when index already exist
		$status = $this->Db->Query("
			CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions_data_datavalueinteger_idx
			ON " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions_data(triggeremailsactionid, datakey, datavalueinteger)
		");

		// Can't check this index, so fail silently when index already exist
		$status = $this->Db->Query('CREATE INDEX ' . SENDSTUDIO_TABLEPREFIX . 'triggeremails_actions_data_triggeremailsid_idx ON ' . SENDSTUDIO_TABLEPREFIX . 'triggeremails_actions_data(triggeremailsid)');

		return true;
	}
}
