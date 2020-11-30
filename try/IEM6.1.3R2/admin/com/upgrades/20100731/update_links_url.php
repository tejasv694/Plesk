<?php
/**
* This file is part of the upgrade process.
*
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
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
class update_links_url extends Upgrade_API
{
	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->IndexExists('links', 'url')) {
			$status = $this->Db->Query('ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . 'links DROP INDEX ' . SENDSTUDIO_TABLEPREFIX . 'links_url_idx');
                        if ($status === false) {
                            return false;
                        }
		}

                $status = $this->Db->Query('ALTER TABLE  ' . SENDSTUDIO_TABLEPREFIX . 'links CHANGE  url url TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
		if ($status === false) {
			return false;
		}

		return true;
	}
}