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
* add_settings_credit_warnings_table
*
* @see Upgrade_API
*
* @package SendStudio
*/
class add_settings_credit_warnings_table extends Upgrade_API
{
	/**
	* RunUpgrade
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		if ($this->TableExists('settings_credit_warnings')) {
			return true;
		}

		$queries = array();
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$queries[] = "
				CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "settings_credit_warnings (
				    creditwarningid         INT             NOT NULL AUTO_INCREMENT,
				    enabled					CHAR(1)			NOT NULL DEFAULT '0',
				    creditlevel				INT				NOT NULL DEFAULT 0,
				    aspercentage			CHAR(1)			NOT NULL DEFAULT '1',
				    emailsubject			VARCHAR(255)	NOT NULL,
				    emailcontents			MEDIUMTEXT		NOT NULL,
				    PRIMARY KEY (creditwarningid)
				) CHARACTER SET UTF8 ENGINE=INNODB
			";
		} else {
			$queries[] = "
				CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "settings_credit_warnings (
				    creditwarningid         SERIAL          NOT NULL,
				    enabled					CHAR(1)			NOT NULL DEFAULT '0',
				    creditlevel				INT				NOT NULL DEFAULT 0,
				    aspercentage			CHAR(1)			NOT NULL DEFAULT '1',
				    emailsubject			VARCHAR(255)	NOT NULL,
				    emailcontents			TEXT			NOT NULL,
				    PRIMARY KEY (creditwarningid)
				)
			";
		}

		foreach ($queries as $query) {
			$result = $this->Db->Query($query);
			if (!$result) {
				return false;
			}
		}

		return true;
	}
}
