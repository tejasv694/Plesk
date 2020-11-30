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
 * create_triggeremails_actions
 *
 * Create triggeremails_actions table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_triggeremails_actions extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_triggeremails_actions upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('triggeremails_actions')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions (
				triggeremailsactionid	INT				AUTO_INCREMENT NOT NULL,
				triggeremailsid         INT             NOT NULL,
				action                  VARCHAR(25)     NOT NULL,

				PRIMARY KEY (triggeremailsactionid),
				FOREIGN KEY (triggeremailsid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "triggeremails (triggeremailsid) ON DELETE CASCADE,

				UNIQUE (triggeremailsid, action)
			) character set utf8 engine=innodb";
		} else {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions (
				triggeremailsactionid	SERIAL			NOT NULL,
				triggeremailsid         INT             NOT NULL,
				action                  VARCHAR(25)     NOT NULL,

				PRIMARY KEY (triggeremailsactionid),
				FOREIGN KEY (triggeremailsid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "triggeremails (triggeremailsid) ON DELETE CASCADE,

				UNIQUE (triggeremailsid, action)
			)";
		}

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}
