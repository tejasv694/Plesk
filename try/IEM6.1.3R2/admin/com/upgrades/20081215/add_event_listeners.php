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
 * add_event_listeners
 *
 * Adds core event listeners to the stash. Add-ons are excluded from this.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class add_event_listeners extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the add_event_listeners upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		try {
			IEM_Installer::RegisterEventListeners();
		} catch (Exception $e) {
			return false;
		}
		return true;
	}
}
?>
