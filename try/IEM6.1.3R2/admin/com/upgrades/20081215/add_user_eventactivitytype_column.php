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
 * add_user_eventactivitytype_column
 *
 * Add a new column to the users table to store "event activity type"
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class add_user_eventactivitytype_column extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the add_user_eventactivitytype_column upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->ColumnExists('users', 'eventactivitytype')) {
			return true;
		}

		$type = 'TEXT';
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$type = 'LONGTEXT';
		}

		$query = 'ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . "users ADD eventactivitytype {$type} DEFAULT NULL";
		$result = $this->Db->Query($query);
		return $result;
	}
}
