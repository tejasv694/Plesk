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
 * add_global_customfields
 *
 * Add a new column to the customfields table to indicate whether a custom field is global or not.
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class add_global_customfields extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the add_global_customfields upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->ColumnExists('customfields', 'isglobal')) {
			return true;
		}

		$query = 'ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . 'customfields ADD isglobal CHAR(1) DEFAULT \'0\'';
		$result = $this->Db->Query($query);
		return $result;
	}
}
