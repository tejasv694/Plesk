<?php
/**
 * This file contains "User Activity Log" API
 *
 * @package API
 * @subpackage UserActivityLog_API
 */

require_once dirname(__FILE__) . '/api.php';

/**
 * This class provide logic that will interface the application with the database.
 *
 * @package API
 * @subpackage UserActivityLog_API
 */
class UserActivityLog_API extends API
{
	/**
	 * The number of activity record to be displayed
	 */
	const NUMBER_OF_ACTIVITY_KEPT = 6;




	/**
	 * CONSTRUCTOR
	 * @return UserActivityLog_API Returns a new instance of this class
	 */
	public function __construct()
	{
		$this->GetDb();
	}




	/**
	 * LogActivity
	 * Log an activity for current user
	 *
	 * @param String $url URL to be logged
	 * @param String $icon URL location of an icon relative to admin/ directory
	 * @param String $text Text to be displayed in the log
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function LogActivity($url, $icon = null, $text = null)
	{
		// -----
			$user = IEM::getCurrentUser();

			$url = trim($url);
			$icon = trim($icon);
			$text = trim($text);
		// -----

		if ($user === false) {
			return false;
		}

		if (!$user->enableactivitylog) {
			return true;
		}

		// ------ Do not record the same consecutive action twice
			$last_url = $this->_mostRecentEntry($user->userid);
			if ($last_url === false) {
				return false;
			}

			if (!empty($last_url)) {
				if ($last_url['url'] == $_SERVER['QUERY_STRING'] && $last_url['icon'] == $icon && $last_url['text'] == $text) {
					return true;
				}
			}
		// -----


		// ----- Record data
			$temp = array(
				'userid'	=> intval($user->userid),
				'url'		=> $this->Db->Quote($url),
				'icon'		=> $this->Db->Quote($icon),
				'text'		=> $this->Db->Quote($text),
				'viewed'	=> time()
			);

			$this->Db->StartTransaction();

			// Delete existing entry
			$result = $this->Db->Query("DELETE FROM [|PREFIX|]user_activitylog WHERE userid = {$temp['userid']} AND url = '{$temp['url']}' AND icon = '{$temp['icon']}' AND text = '{$temp['text']}'");
			if (!$result) {
				list($msg, $errno) = $this->Db->GetError();

				// If table does not exists, we will assume that the application is upgrading itself??
				// But we do still need to log this in the database error log
				$status = $this->_checkTableExists();
				if ($status !== 0) {
					$this->Db->RollbackTransaction();
					trigger_error($msg, $errno);
					return false;
				}

				return true;
			}

			// Insert the new log
			$result = $this->Db->Query("INSERT INTO [|PREFIX|]user_activitylog(userid, url, icon, text, viewed) VALUES ({$temp['userid']}, '{$temp['url']}', '{$temp['icon']}', '{$temp['text']}', {$temp['viewed']})");
			if (!$result) {
				list($msg, $errno) = $this->Db->GetError();
				$this->Db->RollbackTransaction();
				trigger_error($msg, $errno);
				return false;
			}

			$this->Db->CommitTransaction();
		// -----

		$this->_trimLastViewed($user->userid);

		return true;
	}

	/**
	 * GetActivity
	 * Get log activity for current user
	 *
	 * @return Array|FALSE Returns an array of log records (or an empty array if none is available) if successful, FALSE otherwise
	 */
	public function GetActivity()
	{
		$user = IEM::getCurrentUser();
		if ($user === false) {
			return false;
		}

		$userid = intval($user->userid);

		$result = $this->Db->Query("SELECT * FROM [|PREFIX|]user_activitylog WHERE userid = {$userid} ORDER BY viewed DESC LIMIT " . self::NUMBER_OF_ACTIVITY_KEPT);
		if (!$result) {
			list($msg, $errno) = $this->Db->GetError();

			// If table does not exists, we will assume that the application is upgrading itself??
			// But we do still need to log this in the database error log
			$status = $this->_checkTableExists();
			if ($status === 0) {
				trigger_error('user_activitylog table does not exists', E_USER_NOTICE);
				return array();
			}

			trigger_error($msg, $errno);
			return false;
		}

		$rows = array();
		while ($row = $this->Db->Fetch($result)) {
			array_push($rows, $row);
		}

		$this->Db->FreeResult($result);

		return $rows;
	}



	/**
	 * _mostRecentEntry
	 * Get most recent entry for a user from the database
	 *
	 * @param Integer $userid User ID
	 * @return Array|FALSE Returns a record of recently added entry for specified user if successful, FALSE otherwise
	 */
	protected function _mostRecentEntry($userid)
	{
		$userid = intval($userid);

		$result = $this->Db->Query("SELECT * FROM [|PREFIX|]user_activitylog WHERE userid= {$userid} ORDER BY viewed DESC LIMIT 1");
		if (!$result) {
			list($msg, $errno) = $this->Db->GetError();

			// If table does not exists, we will assume that the application is upgrading itself??
			// But we do still need to log this in the database error log
			$status = $this->_checkTableExists();
			if ($status === 0) {
				trigger_error('user_activitylog table does not exists', E_USER_NOTICE);
				return array();
			}

			trigger_error($msg, $errno);
			return false;
		}

		$row = $this->Db->Fetch($result);
		if (!$row) {
			$row = array();
		}
		$this->Db->FreeResult($result);

		return $row;
	}

	/**
	 * _trimLastViewed
	 * Trim the number of records kept for specified user
	 *
	 * @param Integer $userid Trim record for this user
	 * @return Void Does not return anything
	 */
	protected function _trimLastViewed($userid)
	{
		$userid = intval($userid);

		$query = "SELECT COUNT(1) FROM [|PREFIX|]user_activitylog WHERE userid={$userid}";
		$numEntries = $this->Db->FetchOne($query);
		if ($numEntries > self::NUMBER_OF_ACTIVITY_KEPT) {
			$toDelete = $numEntries - self::NUMBER_OF_ACTIVITY_KEPT;
			if ($toDelete <= 0) {
				return;
			}
			// Delete x oldest entries from the log
			$qry = ' ORDER BY viewed ASC';
			$this->Db->DeleteQuery('user_activitylog', $qry, $toDelete);
		}
	}

	/**
	 * _checkTableExists
	 * Check whether or not the user_activitylog table exists
	 *
	 * @return Integer|FALSE Returns 1 if table exists, 0 if it doesn't, FALSE if it encountered any error
	 */
	protected function _checkTableExists()
	{
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "SHOW TABLES LIKE '[|PREFIX|]user_activitylog'";
		} else {
			$query = "SELECT table_name FROM information_schema.tables WHERE table_name='[|PREFIX|]user_activitylog'";
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			list($msg, $errno) = $this->Db->GetError();
			trigger_error($msg, $errno);
			return false;
		}

		$row = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		if (empty($row)) {
			return 0;
		}

		return 1;
	}
}
