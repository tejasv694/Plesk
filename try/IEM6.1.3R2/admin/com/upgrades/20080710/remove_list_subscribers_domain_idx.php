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
	exit();
}

/**
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class remove_list_subscribers_domain_idx extends Upgrade_API
{
	/**
	 * Holds the number of maximum subscriber records, if installation have more than this number, it will not create the index
	 * @var Integer Maximum subscribers record
	 */
	var $_maxSubscriberCount = 100000;

	/**
	* RunUpgrade
	* Runs the query for the upgrade process
	* and returns the result from the query.
	* The calling function looks for a true or false result
	*
	* @return Mixed Returns true if the condition is already met (eg the column already exists).
	*  Returns false if the database query can't be run.
	*  Returns the resource from the query (which is then checked to be true).
	*/
	function RunUpgrade()
	{
		if (!$this->IndexExists('list_subscribers', array('listid', 'domainname'))) {
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

		$query = 'DROP INDEX ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers_list_domain ON ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers';
		$result = $this->Db->Query($query);

		// Return TRUE anyway, as there might be other indexes that uses the same column
		return true;
	}
}
