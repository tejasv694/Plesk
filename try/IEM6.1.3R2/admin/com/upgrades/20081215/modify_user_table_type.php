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
 * modify_user_table_type
 *
 * Adds core event listeners to the stash. Add-ons are excluded from this.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class modify_user_table_type extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the add_event_listeners upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		// Only run this for MySQL server
		if (SENDSTUDIO_DATABASE_TYPE != 'mysql') {
			return true;
		}

		$query = "SHOW TABLE STATUS LIKE '[|PREFIX|]users'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		if (!isset($row['Name'])) {
			return true;
		}

		$table_name = $row['Name'];
		$table_type = $row['Engine'];

		if (empty($table_name)) {
			return false;
		}

		if ($table_type == 'InnoDB') {
			return true;
		}

		// Some past installations had a FULLTEXT index on the username column, which InnoDB doesn't support.
		$query = "DROP INDEX username ON [|PREFIX|]users";
		$this->Db->Query($query);
		// Fail silently if they don't have this index.

		$query = "ALTER TABLE {$table_name} TYPE=InnoDB";

		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		return true;
	}
}
