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
class user_add_notification_email extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the user_add_trialuser upgrade
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		$columns = array(
			"adminnotify_email" => "VARCHAR(100) DEFAULT NULL",
			"adminnotify_send_flag" => "CHAR(1) DEFAULT '0'",
			"adminnotify_send_threshold" => "INT DEFAULT NULL",
			"adminnotify_send_emailtext" => "TEXT DEFAULT NULL",
			"adminnotify_import_flag" => "CHAR(1) DEFAULT '0'",
			"adminnotify_import_threshold" => "INT DEFAULT NULL",
			"adminnotify_import_emailtext" => "TEXT DEFAULT NULL"
		);

		foreach ($columns as $columnName => $columnDefinition) {
			if ($this->ColumnExists('users', $columnName)) {
				continue;
			}

			$query = "ALTER TABLE [|PREFIX|]users ADD COLUMN {$columnName} {$columnDefinition}";
			$status = $this->Db->Query($query);
			if (!$status) {
				return false;
			}
		}

		return true;
	}
}
