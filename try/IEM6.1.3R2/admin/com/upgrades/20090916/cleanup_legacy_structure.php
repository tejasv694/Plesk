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
 * Cleans up the the legacy structure.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class cleanup_legacy_structure extends Upgrade_API
{
	/**
	 * Runs an upgrade step.
	 * 
	 * @return boolean Returns TRUE if successful, FALSE otherwise.
	 */
	function RunUpgrade()
	{
		// drop the user_permissions table
		if (!$this->TableExists('user_permissions'))
	        $this->Db->Query('DROP TABLE [|PREFIX|]user_permissions;');
		
		// drop the user_access table
		if (!$this->TableExists('user_access'))
		    $this->Db->Query('DROP TABLE [|PREFIX|]user_access;');
		
		// the column names to remove from the users table
		$columnsToRemove = array(
		    'forcedoubleoptin',
		    'forcespamcheck',
		    'perhour',
		    'permonth',
		    'maxlists'
	    );
	    
	    // drop the specified columns from the users table
	    foreach ($columnsToRemove as $colName)
		    $this->Db->Query('ALTER TABLE [|PREFIX|]users DROP COLUMN ' . $colName . ';');

		return true;
	}
}
