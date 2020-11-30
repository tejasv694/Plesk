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
 * add_user_enableactivitylog_column
 *
 * Add a new column to the users table to indicate whether or not the user wants to enable "activity log" feature
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class add_user_enableactivitylog_column extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the add_user_enableactivitylog_column upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->ColumnExists('users', 'enableactivitylog')) {
			return true;
		}

		$query = 'ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . 'users ADD enableactivitylog CHAR(1) DEFAULT \'1\'';
		$result = $this->Db->Query($query);
		return $result;
	}
}
