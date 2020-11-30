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
	exit();
}

/**
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class update_db_version extends Upgrade_API
{
	/**
	 * Error message
	 * @var String Error message
	 */
	var $errormessage = 'Previous database updates have failed. Will not update the version number.';

	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		$new_version = '20090420';

		$errors = IEM::sessionGet('DatabaseUpgradesFailed');

		if (empty($errors)) {
			$query = 'UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'settings SET database_version=' . $new_version;
			$result = $this->Db->Query($query);
			return $result;
		}

		return false;
	}
}
