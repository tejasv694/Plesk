<?php
/**
 * This file is part of the upgrade process.
 *
 * @package SendStudio
 */

// Do a sanity check to make sure the upgrade api has been included.
if (!class_exists('Upgrade_API', false)) {
	exit;
}

/**
 * add_column_users_credit_warnings
 *
 * This update will add 2 new columns in the users table which noted
 * the last time a warning message was sent to them, and which one.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class add_column_users_credit_warnings extends Upgrade_API
{
	/**
	* RunUpgrade
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		if (!$this->ColumnExists('users', 'credit_warning_time')) {
			$query = "ALTER TABLE " . SENDSTUDIO_TABLEPREFIX . "users ADD credit_warning_time INT DEFAULT NULL";
			$result = $this->Db->Query($query);
			if (!$result) {
				return false;
			}
		}

		if (!$this->ColumnExists('users', 'credit_warning_percentage')) {
			$query = "ALTER TABLE " . SENDSTUDIO_TABLEPREFIX . "users ADD credit_warning_percentage INT DEFAULT NULL";
			$result = $this->Db->Query($query);
			if (!$result) {
				return false;
			}
		}

			if (!$this->ColumnExists('users', 'credit_warning_fixed')) {
			$query = "ALTER TABLE " . SENDSTUDIO_TABLEPREFIX . "users ADD credit_warning_fixed INT DEFAULT NULL";
			$result = $this->Db->Query($query);
			if (!$result) {
				return false;
			}
		}

		return true;
	}
}
