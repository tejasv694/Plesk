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
 * create_triggeremails_actions_data
 *
 * Create triggeremails_actions_data table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_triggeremails_actions_data extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_triggeremails_actions_data upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('triggeremails_actions_data')) {
			return true;
		}

		$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions_data (
			triggeremailsactionid	INT				NOT NULL,
			datakey                 VARCHAR(25)     NOT NULL,
			datavaluestring         VARCHAR(255)    DEFAULT NULL,
			datavalueinteger        INTEGER         DEFAULT NULL,
			triggeremailsid			INT				NOT NULL,

			FOREIGN KEY (triggeremailsactionid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "triggeremails_actions (triggeremailsactionid) ON DELETE CASCADE,
			FOREIGN KEY (triggeremailsid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "triggeremails (triggeremailsid) ON DELETE CASCADE
		)";

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query .= " character set utf8 engine=innodb";
		}

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}
