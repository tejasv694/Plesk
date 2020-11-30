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
class enable_or_reactivate_systemlog_addon extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Run current upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		// Upgrading from a very old installation. Ignore this upgrade since it is not a crucial upgrade.
		if (!IEM::getDatabase()) {
			return true;
		}

		try {
			// We want to install & enable this addon, but we may need to configure it first.
			require_once IEM_ADDONS_PATH . '/systemlog/systemlog.php';
			$systemlog = new Addons_systemlog();
			$systemlog->Install();
			$systemlog->Disable(); // It may have already been installed/enabled
			$settings = Addons_systemlog::GetSettings();
			if (empty($settings)) {
				$settings = array('logsize' => 1000);
			}
			Addons_systemlog::SetSettings($settings); // This will mark it as 'configured' too.
			$systemlog->Enable();
		} catch (Exception $e) {
			return true;
		}

		return true;
	}
}
