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
 * Go through each of the user accounts and add them to our
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class populate_groups_with_users extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		$query = "SELECT * FROM [|PREFIX|]users WHERE groupid IS NULL";
		$rs    = $this->Db->Query($query);
		
		if (!$rs) {
			return false;
		}

		$userids = array();
		
		while ($row = $this->Db->Fetch($rs)) {
			$userids[] = $row['userid'];
		}

		$this->Db->FreeResult($rs);
		
		$now = time();

		foreach ($userids as $userid) {
			$query = "
				INSERT INTO [|PREFIX|]usergroups (
					groupname, createdate,
					limit_list, limit_hourlyemailsrate, limit_emailspermonth,
					limit_totalemailslimit,
					forcedoubleoptin, forcespamcheck,
					systemadmin,
					listadmin,
					segmentadmin,
					templateadmin
				)	SELECT	username, {$now},
							maxlists, perhour, permonth,
							CASE unlimitedmaxemails WHEN '1' THEN 0 ELSE (CASE maxemails WHEN 0 THEN 1 ELSE maxemails END) END,
							forcedoubleoptin, forcespamcheck,
							CASE admintype WHEN 'a' THEN '1' ELSE '0' END,
							CASE listadmintype WHEN 'a' THEN '1' ELSE '0' END,
							CASE templateadmintype WHEN 'a' THEN '1' ELSE '0' END,
							CASE segmentadmintype WHEN 'a' THEN '1' ELSE '0' END
					FROM	[|PREFIX|]users
					WHERE	userid = {$userid}
			";

			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$query .= ' RETURNING groupid';
			}

			$rs = $this->Db->Query($query);
			
			if (!$rs) {
				return false;
			}

			$new_groupid = 0;
			
			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$new_groupid = $this->Db->FetchOne($rs, 'groupid');
				
				$this->Db->FreeResult($rs);
			} else {
				$new_groupid = $this->Db->LastId('usergroups');
			}

			if (!$new_groupid) {
				return false;
			}

			$query = "
				INSERT INTO [|PREFIX|]usergroups_permissions (
					groupid, area, subarea
				)	SELECT	{$new_groupid}, area, subarea
					FROM	[|PREFIX|]user_permissions
					WHERE	userid = {$userid}
			";
			$result = $this->Db->Query($query);
			
			if (!$result) {
				return false;
			}

			$query = "
				INSERT INTO [|PREFIX|]usergroups_access (
					groupid, resourcetype, resourceid
				)	SELECT	{$new_groupid}, area, id
					FROM	[|PREFIX|]user_access
					WHERE	userid = {$userid}
			";
			$result = $this->Db->Query($query);
			
			if (!$result) {
				return false;
			}
			
			if (!$result) {
				return false;
			}

			$this->Db->StartTransaction();
			
			
			
			/**
			 * Delete user permissions record.
			 */

			$query  = "DELETE FROM [|PREFIX|]user_permissions WHERE userid = {$userid}";
			$result = $this->Db->Query($query);
			
			if (!$result) {
				$this->Db->RollbackTransaction();
				
				return false;
			}
			
			
			
			/**
			 * Delete user access record.
			 */

			$query  = "DELETE FROM [|PREFIX|]user_access WHERE userid = {$userid}";
			$result = $this->Db->Query($query);
			
			if (!$result) {
				$this->Db->RollbackTransaction();
				
				return false;
			}

			
			
			/**
			 * Update set new group ids.
			 */
			
			$query  = "UPDATE [|PREFIX|]users SET groupid = {$new_groupid} WHERE userid = {$userid}";
			$result = $this->Db->Query($query);
			
			if (!$result) {
				$this->Db->RollbackTransaction();
				
				return false;
			}

			$this->Db->CommitTransaction();
		}

		return true;
	}
}
