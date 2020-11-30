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
 * create_triggeremails
 *
 * Create triggeremails table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_triggeremails extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_triggeremails upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('triggeremails')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "triggeremails (
				triggeremailsid         INT             AUTO_INCREMENT NOT NULL,
				active                  CHAR(1)         NOT NULL DEFAULT '0',
				createdate              INT             NOT NULL,
				ownerid                 INT             NOT NULL,
				name                    VARCHAR(100)    NOT NULL,
				triggertype             CHAR(1)         NOT NULL,

				triggerhours            INT             DEFAULT 0,
				triggerinterval         INT             DEFAULT 0,

				queueid                 INT             NOT NULL,
				statid                  INT             NOT NULL,

				PRIMARY KEY (triggeremailsid),
				UNIQUE (queueid),
				UNIQUE (statid)
			) character set utf8 engine=innodb";
		} else {
			$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "triggeremails (
				triggeremailsid         SERIAL			NOT NULL,
				active                  CHAR(1)         NOT NULL DEFAULT '0',
				createdate              INT             NOT NULL,
				ownerid                 INT             NOT NULL,
				name                    VARCHAR(100)    NOT NULL,
				triggertype             CHAR(1)         NOT NULL,

				triggerhours            INT             DEFAULT 0,
				triggerinterval         INT             DEFAULT 0,

				queueid                 INT             NOT NULL,
				statid                  INT             NOT NULL,

				PRIMARY KEY (triggeremailsid),
				UNIQUE (queueid),
				UNIQUE (statid)
			)";
		}

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}
