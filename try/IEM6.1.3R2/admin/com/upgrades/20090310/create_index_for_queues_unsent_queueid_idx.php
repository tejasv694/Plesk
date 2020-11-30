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
class create_index_for_queues_unsent_queueid_idx extends Upgrade_API
{
	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if (!$this->IndexExists('queues_unsent', 'queueid')) {
			$status = $this->Db->Query("SELECT COUNT(1) FROM " . SENDSTUDIO_TABLEPREFIX . "queues_unsent");
			if ($this->Db->CountResult($status) > 250000) {
				return true;
			}

			// If we can't create index, just failed silently
			$status = $this->Db->Query("CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "queues_unsent_queueid_idx ON " . SENDSTUDIO_TABLEPREFIX . "queues_unsent (queueid)");
		}

		return true;
	}
}
