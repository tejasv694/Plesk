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
class users_update_smtpdetails extends Upgrade_API
{
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
		/**
		* Expand the old smtpserver which contained:
		* servername; username; password
		* into the separate fields.
		*/
		$query = 'SELECT userid, smtpserver FROM ' . SENDSTUDIO_TABLEPREFIX . 'users';
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			if (strpos($row['smtpserver'], ';') !== false) {
				list($servername, $username, $password) = explode(';', str_replace(' ', '', $row['smtpserver']));

				$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "users SET smtpserver='" . $this->Db->Quote($servername) . "', smtpusername='" . $this->Db->Quote($username) . "', smtppassword='" . $this->Db->Quote(base64_encode($password)) . "' WHERE userid='" . $row['userid'] . "'";

				$update_result = $this->Db->Query($query);
			}
		}
		return true;
	}
}
