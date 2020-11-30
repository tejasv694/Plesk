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
 * fix_3744_fix_absolute_path
 *
 * Fix absolute path usage in the event listeners
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class fix_3744_fix_absolute_path extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the fix_3744_fix_absolute_path upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		$stash = IEM_InterspireStash::getInstance();

		// Cannot run this upgrade as the event data does not exists
		if (!$stash->exists(InterspireEvent::DEFAULT_STORAGE_KEY)) {
			return true;
		}

		$data = array();

		// Try to read the data, if failed, do not continue
		try {
			$data = $stash->read(InterspireEvent::DEFAULT_STORAGE_KEY);
		} catch (Exception $e) {
			return true;
		}

		$newData = array();
		foreach ($data as $priority => $listeners_in_priority) {
			foreach ($listeners_in_priority as $index_in_priority => $listeners) {
				foreach ($listeners as $index => $listener) {
					if (isset($listener['file']) && !empty($listener['file'])) {
						$listener['file'] = preg_replace('/^' . preg_quote(IEM_PUBLIC_PATH, '/') . '/', '{%IEM_PUBLIC_PATH%}', $listener['file']);
					}

					$newData[$priority][$index_in_priority][$index] = $listener;
				}
			}
		}

		// Try to write the data, if failed, do not continue
		try {
			$data = $stash->write(InterspireEvent::DEFAULT_STORAGE_KEY, $newData, true);
		} catch (Exception $e) {
			return true;
		}

		return true;
	}
}
