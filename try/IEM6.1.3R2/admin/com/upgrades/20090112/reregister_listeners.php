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
 * reregister_listeners
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class reregister_listeners extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the reregister_listeners
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		try {
			// Restore default listeners
			IEM_Installer::RegisterEventListeners();

			// Restore Addons listeners
			require_once IEM_ADDONS_PATH . '/interspire_addons.php';
			$addons = new Interspire_Addons();
			$addons->FixEnabledEventListeners();
		} catch (Exception $e) {
			return true;
		}

		return true;
	}
}
