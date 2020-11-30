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
* add_user_forcedoubleoptin_column
*
* Adds a user column to track the option to force double opt-in.
*
* @see Upgrade_API
*
* @package SendStudio
*/
class add_user_forcedoubleoptin_column extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the add_user_forcedoubleoptin_column upgrade
	*
	* @return Boolean True if the query was executed successfully, otherwise false.
	*/
	function RunUpgrade()
	{
		if ($this->ColumnExists('users', 'forcedoubleoptin')) {
			return true;
		}

		$query = "ALTER TABLE [|PREFIX|]users ADD forcedoubleoptin CHAR(1) DEFAULT '0'";
		$result = $this->Db->Query($query);
		return (bool)$result;
	}
}
