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
 * Add "groupid" column to the users table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class user_add_groupid_column extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->ColumnExists('users', 'groupid')) {
			return true;
		}

		$query = "ALTER TABLE " . SENDSTUDIO_TABLEPREFIX . "users ADD COLUMN groupid INT DEFAULT NULL";

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}
