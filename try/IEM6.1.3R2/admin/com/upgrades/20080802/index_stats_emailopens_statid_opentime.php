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
class index_stats_emailopens_statid_opentime extends Upgrade_API
{
	/**
	 * Holds the number of maximum stats_emailopens records, if installation have more than this number, it will not create the index
	 * @var Integer Maximum number of stats_emailopens records
	 */
	var $_maxRecordCount = 250000;

	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->IndexExists('stats_emailopens', array('statid', 'opentime'))) {
			return true;
		}

		// ----- Check how many records the installation have, if it is more than $this->_maxRecordCount, then don't run this query
			$status = $this->Db->Query('SELECT COUNT(1) AS count FROM ' . SENDSTUDIO_TABLEPREFIX . 'stats_emailopens');
			if (!$status) {
				return false;
			}

			$row = $this->Db->Fetch($status);
			$this->Db->FreeResult($status);

			if ($row['count'] > $this->_maxRecordCount) {
				return true;
			}
		// -----

		$status = $this->Db->Query('CREATE INDEX ' . SENDSTUDIO_TABLEPREFIX . 'stats_emailopens_statid_opentime_idx ON ' . SENDSTUDIO_TABLEPREFIX . 'stats_emailopens(statid, opentime)');
		if ($status === false) {
			return false;
		}

		return true;
	}
}