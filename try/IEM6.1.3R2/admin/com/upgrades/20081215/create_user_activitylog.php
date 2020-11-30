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
 * create_user_activitylog
 *
 * Create user_activitylog table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_user_activitylog extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_user_activitylog upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('user_activitylog')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "user_activitylog (
				lastviewid 				INT				AUTO_INCREMENT NOT NULL,
				userid					INT				NOT NULL,
				icon					VARCHAR(255)	DEFAULT NULL,
				text					VARCHAR(255)	DEFAULT NULL,
				url						VARCHAR(255)	NOT NULL,
				viewed					INT				NOT NULL,

				PRIMARY KEY (lastviewid),
				FOREIGN KEY (userid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users (userid) ON DELETE CASCADE
			) character set utf8 engine=innodb";
		} else {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "user_activitylog (
				lastviewid 				SERIAL			NOT NULL,
				userid					INT				NOT NULL,
				icon					VARCHAR(255)	DEFAULT NULL,
				text					VARCHAR(255)	DEFAULT NULL,
				url						VARCHAR(255)	NOT NULL,
				viewed					INT				NOT NULL,

				PRIMARY KEY (lastviewid),
				FOREIGN KEY (userid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users (userid) ON DELETE CASCADE
			)";
		}

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}
