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
class subscriber_data_fix_data extends Upgrade_API
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
		// change them to the new 'format'
		$query = "SELECT subscriberid, d.fieldid, fieldtype, data FROM " . SENDSTUDIO_TABLEPREFIX . "subscribers_data d, " . SENDSTUDIO_TABLEPREFIX . "customfields c WHERE c.fieldid=d.fieldid AND c.fieldtype IN ('checkbox', 'multicheckbox', 'date')";

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			switch ($row['fieldtype']) {
				case 'checkbox':
					// if it was a multicheckbox, the fieldtype has been renamed.
					// the old data was value1:value2:value3
					// the new data is just that all serialized up.
					// we can use the ':' as a check for the old type and convert it across.
					if (strpos($row['data'], ':') !== false) {
						$options = explode(':', $row['data']);
						$new_value = serialize($options);
						break;
					}
					if (strtolower($row['data']) == 'checked') {
						$row['data'] = '1';
					} else {
						if ($row['data'] == '') {
							$row['data'] = '0';
						}
					}
					$options = array($row['data']);
					$new_value = serialize($options);
				break;

				case 'date':
					$new_value = str_replace(':', '/', $row['data']);
				break;
			}

			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "subscribers_data SET data='" . $this->Db->Quote($new_value) . "' WHERE fieldid='" . $row['fieldid'] . "' AND subscriberid='" . $row['subscriberid'] . "'";
			$update_result = $this->Db->Query($query);
		}
		return true;
	}
}
