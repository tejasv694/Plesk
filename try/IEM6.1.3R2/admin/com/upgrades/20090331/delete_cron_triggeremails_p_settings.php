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
 * delete_cron_triggeremails_p_settings
 *
 * Since 5.5.8, cron_triggeremails_p wasn't being saved. It relies on cron_triggeremails_s's values.
 * As it turns out, when you do a fresh install, "Triggeremails Process" CRON weren't being processed.
 *
 * This is because the value SENDSTUDIO_CRON_TRIGGEREMAILS_P contains an empty string, which evaluated to FALSE
 * whenever CRON check for its' schedule.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class delete_cron_triggeremails_p_settings extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_login_attempt_2 upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "config_settings WHERE area='CRON_TRIGGEREMAILS_P'";

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}
