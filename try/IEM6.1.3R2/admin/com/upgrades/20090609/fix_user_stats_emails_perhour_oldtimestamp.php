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
* fix_user_stats_emails_perhour_oldtimestamp
*
* Fix an issue where user statistics were incorrectly recorded
* in the user_stats_emails_perhour table
*
* @see Upgrade_API
*
* @package SendStudio
*/
class fix_user_stats_emails_perhour_oldtimestamp extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the fix_user_stats_emails_perhour_oldtimestamp upgrade
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		$records = array();

		// ----- Fetch statid to be updated
			// Get all statid from user_stats_emailsperhour where the sending was recorded before 1985-11-05 11:53:20
			// A bug in the system causes this bug to record the send time difference instead of recording the actual time
			$rs = $this->Db->Query("SELECT statid FROM [|PREFIX|]user_stats_emailsperhour WHERE sendtime < 500000000");
			if (!$rs) {
				trigger_error(__CLASS . '::' . __METHOD . ' -- Unable to fetch data from user_stats_emailsperhour table: ' . $this->Db->Error(), E_USER_NOTICE);
				return false;
			}

			$records = array();
			while ($row = $this->Db->Fetch($rs)) {
				$records[] = $row['statid'];
			}

			$this->Db->FreeResult($rs);

			if (empty($records)) {
				return true;
			}
		// -----

		// ----- Fetch time to update user_stats_emailsperhour with
			$rs = $this->Db->Query("SELECT statid, starttime FROM [|PREFIX|]stats_newsletters WHERE statid IN (" . implode(',', $records) . ")");
			if (!$rs) {
				trigger_error(__CLASS . '::' . __METHOD . ' -- Unable to fetch data from stats_newsletters table: ' . $this->Db->Error(), E_USER_NOTICE);
				return false;
			}

			$records = array();
			while ($row = $this->Db->Fetch($rs)) {
				$records[$row['statid']] = $row['starttime'];
			}

			if (empty($records)) {
				return true;
			}
		// -----

		// ----- Update time
			foreach ($records as $statid => $starttime) {
				$status = $this->Db->Query("UPDATE [|PREFIX|]user_stats_emailsperhour SET sendtime = sendtime + {$starttime} WHERE statid = {$statid}");
				if (!$status) {
					trigger_error(__CLASS . '::' . __METHOD . ' -- Cannot update record: ' . $this->Db->Error(), E_USER_NOTICE);
					return false;
				}
			}

			return true;
		// -----
	}
}
