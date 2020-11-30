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
 * create_triggeremails_log
 *
 * Create triggeremails_log table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_triggeremails_log_summary extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_triggeremails_log upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('triggeremails_log_summary')) {
			return true;
		}

		$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "triggeremails_log_summary (
			triggeremailsid         INT             NOT NULL,
			subscriberid            INT             NOT NULL,
			actionedoncount         INT             DEFAULT 0,
			lastactiontimestamp     INT             DEFAULT NULL,

			PRIMARY KEY (triggeremailsid, subscriberid),
			FOREIGN KEY (triggeremailsid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "triggeremails (triggeremailsid) ON DELETE CASCADE
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
