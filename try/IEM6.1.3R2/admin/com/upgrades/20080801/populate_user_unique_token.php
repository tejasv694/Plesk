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
 * populate_user_unique_token
 *
 * Populate user's unique token
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class populate_user_unique_token extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * Runs the populate_user_unique_token upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		$result = $this->Db->Query('SELECT username, password FROM ' . SENDSTUDIO_TABLEPREFIX . 'users');
		if ($result === false) {
			return false;
		}

		while ($row = $this->Db->Fetch($result)) {
			$new_token = API_USERS::generateUniqueToken($row['username']);
			$new_password = md5(md5($new_token) . $row['password']);

			$query = 'UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'users ';
			$query .= " SET unique_token='" . $this->Db->Quote($new_token) . "'";
			$query .= ", password='" . $this->Db->Quote($new_password) . "'";
			$query .= " WHERE username='" . $this->Db->Quote($row['username']) . "'";

			$status = $this->Db->Query($query);
			if ($status === false) {
				return false;
			}
		}

		return true;
	}
}