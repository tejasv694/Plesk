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
* check_cron_schedule_time
*
* This upgrade will check if CRON last run entry is set in the future.
* If they are, fix it. This is a rare issue, but there are a few cases where it prevents
* CRON from running until the "last run" timestamp has lapse.
*
* @see Upgrade_API
*
* @package SendStudio
*/
class check_cron_schedule_time extends Upgrade_API
{
	/**
	* RunUpgrade
	*
	* @return Boolean True if the query was executed successfully, otherwise false.
	*/
	function RunUpgrade()
	{
		$now = time();
		$update_job = array();

		// ----- Get job type to update
			$query = "SELECT * FROM [|PREFIX|]settings_cron_schedule";
			$rs = $this->Db->Query($query);
			if (!$rs) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to query database: ' . $this->Db->Error(), E_USER_NOTICE);
				return false;
			}


			while ($row = $this->Db->Fetch($rs)) {
				$last_run = intval($row['lastrun']);

				// Job time is in the future. Need to update it.
				if ($row['lastrun'] > $now) {
					$update_job[$row['jobtype']] = $this->RecalculateLastRun($row['jobtype'], $row['lastrun'], $now);
				}
			}

			$this->Db->FreeResult($rs);
		// -----

		// Nothing to update
		if (empty($update_job)) {
			return true;
		}

		foreach ($update_job as $jobtype => $update_lastrun_to) {
			$query = "UPDATE [|PREFIX|]settings_cron_schedule SET lastrun = {$update_lastrun_to} WHERE jobtype = '" . $this->Db->Quote($jobtype) . "'";
			if (!$this->Db->Query($query)) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to update cron schedule: ' . $this->Db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return true;
	}

	/**
	 * RecalculateLastRun
	 * Recalculate CRON schedule "lastrun" timestamp
	 *
	 * @param string $jobtype Job type
	 * @param integer $lastrun Last run time stamp
	 * @param integer $currentTime Current time time stamp
	 *
	 * @return integer Recalculated last run timestamp
	 */
	function RecalculateLastRun($jobtype, $lastrun, $currentTime)
	{
		$interval = 0;
		$constant_name = "SENDSTUDIO_CRON_" . strtoupper($jobtype);

		if (defined($constant_name)) {
			$interval = abs(intval(constant($constant_name)));
		}

		if ($interval == 0 || $currentTime == 0) {
			return $currentTime;
		}

		// Only allow this to run 1500 times max
		// The maximum time discrepencies that may happen is +13 hours
		// Assuming that CRON run in 1 minute interval, the maximum differences is 780 minutes.
		for ($i = 0; $i < 1500; ++$i) {
			$lastrun -= $interval;

			if ($lastrun < $currentTime) {
				return $lastrun;
			}
		}

		// Give up! Cannot find last run equivalent, so returning currentTime instead.
		return $currentTime;
	}
}
