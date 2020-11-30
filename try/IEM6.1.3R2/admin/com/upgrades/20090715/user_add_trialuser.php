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
* user_add_trialuser
*
* Add a column 'trialuser' to the users table to indicate
* that the user is a trial user
*
* @see Upgrade_API
*
* @package SendStudio
*/
class user_add_trialuser extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the user_add_trialuser upgrade
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		if ($this->ColumnExists('users', 'trialuser')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]users ADD COLUMN trialuser CHAR(1) DEFAULT '0'";
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query .= " AFTER userid";
		}

		$status = $this->Db->Query($query);
		if (!$status) {
			return false;
		}

		return true;
	}
}
