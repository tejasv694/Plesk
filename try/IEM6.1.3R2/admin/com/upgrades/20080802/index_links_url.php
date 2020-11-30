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
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class index_links_url extends Upgrade_API
{
	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->IndexExists('links', 'url')) {
			return true;
		}

		$status = $this->Db->Query('CREATE INDEX ' . SENDSTUDIO_TABLEPREFIX . 'links_url_idx ON ' . SENDSTUDIO_TABLEPREFIX . 'links(url)');
		if ($status === false) {
			return false;
		}

		return true;
	}
}