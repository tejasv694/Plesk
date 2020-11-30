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
* install_addon_dbcheck
*
* Installs the database integrity checker addon.
*
* @see Upgrade_API
*
* @package SendStudio
*/
class install_addon_dbcheck extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the install_addon_dbcheck upgrade
	*
	* @return Boolean Will always return true as we fail silently otherwise.
	*/
	function RunUpgrade()
	{
		$api = new IEM_Installer();
		$api->InstallAddon('dbcheck');

		return true;
	}
}
