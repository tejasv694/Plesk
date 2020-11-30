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
class add_subscriber_listid_index extends Upgrade_API
{
	/**
	 * Holds the number of maximum subscriber records, if installation have more than this number, it will not create the index
	 * @var Integer Maximum subscribers record
	 */
	var $_maxSubscriberCount = 100000;

	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->IndexExists('list_subscribers', 'listid')) {
			return true;
		}

		/**
		 * Check how many subscribers the installation have, if it is more than $this->_maxSubscriberCount, then don't run this
		 */
			$status = $this->Db->Query('SELECT COUNT(1) AS count FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers');
			if (!$status) {
				return false;
			}

			$row = $this->Db->Fetch($status);
			$this->Db->FreeResult($status);

			if ($row['count'] > $this->_maxSubscriberCount) {
				return true;
			}
		/**
		 * -----
		 */

		$status = $this->Db->Query('CREATE INDEX ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers_listid_idx ON ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers(listid)');
		if ($status === false) {
			return false;
		}

		return true;
	}
}