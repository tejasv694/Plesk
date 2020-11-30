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
 * create_user_activitylog_userid_viewed_idx
 *
 * Create an index on user_activitylog table on userid and viewed column
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_user_activitylog_userid_viewed_idx extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the create_user_activitylog_userid_viewed_idx upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->IndexExists('user_activitylog', array('userid', 'viewed'))) {
			return true;
		}

		$query = 'CREATE INDEX ' . SENDSTUDIO_TABLEPREFIX . 'user_activitylog_userid_viewed_idx ON ' . SENDSTUDIO_TABLEPREFIX . 'user_activitylog(userid, viewed)';

		$result = $this->Db->Query($query);
		if ($result == false) {
			return false;
		}

		return true;
	}
}