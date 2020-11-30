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
* lists_add_agreedeleteall
*
* Creates the agreedeleteall column on the email_lists table.
*
* @see Upgrade_API
*
* @package SendStudio
*/
class lists_add_agreedeleteall extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the list_subscriber_event upgrade
	*
	* @return Resource Returns query resource
	*/
	function RunUpgrade()
	{
		if ($this->ColumnExists('lists', 'agreedeleteall')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = 'ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . 'lists ADD COLUMN agreedeleteall CHAR(1) DEFAULT \'0\' AFTER agreedelete';
		} else {
			$query = 'ALTER TABLE ' . SENDSTUDIO_TABLEPREFIX . 'lists ADD COLUMN agreedeleteall CHAR(1) DEFAULT \'0\'';
		}
		$result = $this->Db->Query($query);
		return $result;
	}
}
