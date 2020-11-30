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
 * Add a NOT NULL constraint to the groupid column in the user database
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class user_update_groupid_column_constraint extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		$query = "ALTER TABLE [|PREFIX|]users ADD CONSTRAINT FOREIGN KEY (groupid) REFERENCES [|PREFIX|]usergroups (groupid) ON DELETE RESTRICT;";
		$rs = $this->Db->Query($query);
		if (!$rs) {
			// Ignore any errors just incase
			// the database returned an error because
			// the constraint is already there
		}

		return true;
	}
}
