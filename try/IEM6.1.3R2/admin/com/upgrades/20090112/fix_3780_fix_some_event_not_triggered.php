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
 * fix_3780_fix_some_event_not_triggered
 *
 * Fix absolute path usage in the event listeners.
 * To do this, we will need to unregister every listeners and re-register them again.
 * (see register_listeners upgrade)
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class fix_3780_fix_some_event_not_triggered extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the fix_3744_fix_absolute_path upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		try {
			$eventList = InterspireEvent::eventList();
			foreach ($eventList as $event) {
				$listeners = InterspireEvent::eventListenerList($event);

				foreach ($listeners as $listener) {
					InterspireEvent::listenerUnregister($event, $listener['function'], $listener['file']);
				}
			}
		} catch (Exception $e) {
			return true;
		}

		return true;
	}
}
