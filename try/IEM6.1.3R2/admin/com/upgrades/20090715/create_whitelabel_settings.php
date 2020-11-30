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
 * create_whitelabel_settings
 *
 * Create whitelabel_settings table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_whitelabel_settings extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_whitelabel_settings upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('whitelabel_settings')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "whitelabel_settings (
				name                    VARCHAR(100)    NOT NULL,
				value             	    TEXT,
				PRIMARY KEY (name)
			) character set utf8 engine=innodb";
		} else {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "whitelabel_settings (
				name                    VARCHAR(100)    NOT NULL,
				value                   TEXT,
				PRIMARY KEY (name)
			)";
		}

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}
