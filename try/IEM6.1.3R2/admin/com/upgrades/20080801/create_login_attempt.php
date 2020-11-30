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
 * create_login_attempt
 *
 * Create login_attempt table which purpose is to store failed login attempt
 * within certain period of time.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_login_attempt extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_login_attempt upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('login_attempt')) {
			return true;
		}

		$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "login_attempt (
			timestamp INTEGER NOT NULL,
			ipaddress VARCHAR(15) NOT NULL
		)";

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query .= ' character set utf8 engine=innodb';
		}

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}