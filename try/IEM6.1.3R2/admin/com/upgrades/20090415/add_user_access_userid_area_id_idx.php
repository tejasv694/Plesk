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
 * add_user_access_userid_area_id_idx
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class add_user_access_userid_area_id_idx extends Upgrade_API
{
	/**
	 * RunUpgrade
	 *
	 * @return Boolean True if the query was executed successfully, otherwise false.
	 */
	function RunUpgrade()
	{
		if ($this->IndexExists('user_access', array('userid', 'area', 'id'), true)) {
			return true;
		}

		$status = $this->Db->Query("CREATE UNIQUE INDEX [|PREFIX|]user_access_userid_area_id_idx ON [|PREFIX|]user_access (userid, area, id)");
		if (!$status) {
			return false;
		}

		return true;
	}
}
