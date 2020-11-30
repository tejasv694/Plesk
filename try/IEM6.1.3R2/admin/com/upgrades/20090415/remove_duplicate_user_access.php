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
 * remove_duplicate_user_access
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class remove_duplicate_user_access extends Upgrade_API
{
	/**
	 * RunUpgrade
	 *
	 * @return Boolean True if the query was executed successfully, otherwise false.
	 */
	function RunUpgrade()
	{
		$records = array();

		// ----- Retrive current user access
			$rs = $this->Db->Query("SELECT userid, area, id FROM [|PREFIX|]user_access GROUP BY userid, area, id");
			if (!$rs) {
				trigger_error(__FILE__ . '::' . __METHOD__ . " -- Unable to query database with the following query {$query}", E_USER_NOTICE);
				return false;
			}

			while ($row = $this->Db->Fetch($rs)) {
				$records[] = $row;
			}

			$this->Db->FreeResult($rs);
		// -----

		// ----- Repopulate user access after delete
			$this->Db->StartTransaction();
			$status = $this->Db->Query("DELETE FROM [|PREFIX|]user_access");
			if (!$status) {
				$this->Db->RollbackTransaction();
				trigger_error(__FILE__ . '::' . __METHOD__ . " -- Unable to query database with the following query {$query}", E_USER_NOTICE);
				return false;
			}

			$count = 0;
			$total_count = count($records);
			$insert_query = array();
			foreach ($records as $each) {
				$userid = intval($each['userid']);
				$area = $this->Db->Quote($each['area']);
				$id = intval($each['id']);

				$insert_query[] = "({$userid}, '{$area}', {$id})";
				--$total_count;

				// Insert every 100 records or when no more records needed to be added into the $insert_query array
				if (($total_count <= 0 || $count > 100) && !empty($insert_query)) {
					$query = "INSERT INTO [|PREFIX|]user_access (userid, area, id) VALUES " . implode(',', $insert_query);
					$result = $this->Db->Query($query);
					if (!$result) {
						$this->Db->RollbackTransaction();
						trigger_error(__FILE__ . '::' . __METHOD__ . " -- Unable to query database with the following query {$query}", E_USER_NOTICE);
						return false;
					}

					$count = 0;
					$insert_query = array();
				}
			}

			$this->Db->CommitTransaction();
		// -----

		return true;
	}
}
