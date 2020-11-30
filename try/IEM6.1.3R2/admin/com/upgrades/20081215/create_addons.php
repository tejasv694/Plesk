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
* create_addons
*
* Creates addons table
*
* @see Upgrade_API
*
* @package SendStudio
*/
class create_addons extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the create_addons upgrade
	*
	* @return Resource Returns query resource
	*/
	function RunUpgrade()
	{
		if ($this->TableExists('addons')) {
			return true;
		}

		$query = "create table " . SENDSTUDIO_TABLEPREFIX . "addons (
			addon_id varchar(200) not null primary key,
			installed int default 0,
			configured int default 0,
			enabled int default 0,
			addon_version VARCHAR(10) default '0',
			settings text
		)";

		// if the database driver is mysql force innodb
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query .= ' CHARACTER SET UTF8 ENGINE=INNODB';
		}

		$result = $this->Db->Query($query);

		if ($result) {
			// Install the default set of addons.
			$api = new IEM_Installer();
			$api->RegisterAddons();
		}

		return $result;
	}
}
?>
