<?php
/**
* This file is part of the upgrade process.
*
* @author John Tuck <John.Tuck@interspire.com>
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
class add_list_permissions extends Upgrade_API
{
	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{   
        //These tables should have been dropped in the upgrade script from 20090916.  Their presence doesn't hurt anything, but it's clutter.  Try and delete them.  If they're not there, or you can't delete them, no big deal.
        $query = "DROP TABLE [|PREFIX|]user_access";
		$rs    = $this->Db->Query($query);
		
        $query = "DROP TABLE [|PREFIX|]user_permissions";
		$rs    = $this->Db->Query($query);
        
        return true;
	}
}
