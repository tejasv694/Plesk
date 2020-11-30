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
 * add_user_unique_token
 *
 * Add a new column in the user table to hold a random string a unique token that can be used to "salt" password.
 * Each user will need to have a unique "salt" that is assigned to him/her.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class add_user_unique_token extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the add_user_unique_token upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->ColumnExists('users', 'unique_token')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = 'ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . 'users ADD unique_token VARCHAR(128) AFTER username';
		} else {
			$query = 'ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . 'users ADD unique_token VARCHAR(128)';
		}
		$result = $this->Db->Query($query);
		return $result;
	}
}