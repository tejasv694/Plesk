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
* fix_3784_index_user_permissions
*
* This adds a unique constraint to the user_permissions table, as it has no primary key.
* See bug #3784.
*
* @see Upgrade_API
*
* @package SendStudio
*/
class fix_3784_index_user_permissions extends Upgrade_API
{
	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->IndexExists('user_permissions', array('userid', 'area', 'subarea'), true)) {
			return true;
		}

		// Identify and any duplicate records in this table before creating the unique constraint.

		$records_to_fix = array();

		$result = $this->Db->Query('SELECT COUNT(*) AS num, userid, area, subarea FROM [|PREFIX|]user_permissions GROUP BY userid, area, subarea HAVING num > 1');
		while ($row = $this->Db->Fetch($result)) {
			$records_to_fix[] = array(
				'user_id' => $row['userid'],
				'area' => $row['area'],
				'subarea' => $row['subarea'],
			);
		}

		// Remove the duplicate records.

		foreach ($records_to_fix as $r) {
			$sql = "DELETE FROM [|PREFIX|]user_permissions WHERE userid = {$r['user_id']} AND area = '{$r['area']}' AND subarea = '{$r['subarea']}'";
			$result = $this->Db->Query($sql);
			if (!$result) {
				return false;
			}
			$sql = "INSERT INTO [|PREFIX|]user_permissions (userid, area, subarea) VALUES ({$r['user_id']}, '{$r['area']}', '{$r['subarea']}')";
			$result = $this->Db->Query($sql);
			if (!$result) {
				return false;
			}
		}

		// Create the unique constraint.
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$sql = "CREATE UNIQUE INDEX [|PREFIX|]user_permissions_userid_area_subarea_idx on [|PREFIX|]user_permissions(userid, area, subarea)";
		} else {
			// We limit the key length to 94 for the varchar columns so we can fit in a key space of 767 (InnoDB) x 4 (UTF-8 in MySQL 6).
			// Also note that it needs the space in 'area (94)', otherwise it thinks it's the AREA() function.
			$sql = "CREATE UNIQUE INDEX [|PREFIX|]user_permissions_userid_area_subarea_idx on [|PREFIX|]user_permissions(userid, area (94), subarea (94))";
		}

		$result = $this->Db->Query($sql);
		if (!$result) {
			return false;
		}

		return true;
	}
}
