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
class autoresponders_fix_content extends Upgrade_API
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
		$ok = true;
		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$search_criteria = unserialize(stripslashes($row['searchcriteria']));

			$new_criteria = array();
			if (isset($search_criteria['Email'])) {
				$new_criteria['emailaddress'] = $search_criteria['Email'];
			}

			$new_format = '-1';

			if (isset($search_criteria['SearchFormat'])) {
				if ($search_criteria['SearchFormat'] == 1) {
					$new_format = 't';
				} elseif ($search_criteria['SearchFormat'] == 2) {
					$new_format = 'h';
				}
			}

			$new_criteria['format'] = $new_format;

			$confirmed = 1;
			if (isset($search_criteria['Confirmed'])) {
				$confirmed = $search_criteria['Confirmed'];
			}
			$new_criteria['confirmed'] = $confirmed;

			if (isset($search_criteria['HaveClickedLink'])) {
				$new_criteria['link'] = $search_criteria['HaveClickedLink'];
				if ($new_criteria['link'] == 'A') {
					unset($new_criteria['link']);
				}
			}

			if (isset($search_criteria['HaveOpenedNewsletter'])) {
				$new_criteria['newsletter'] = $search_criteria['HaveOpenedNewsletter'];
				if ($new_criteria['newsletter'] == 'A') {
					unset($new_criteria['newsletter']);
				}
			}

			$new_criteria['customfields'] = array();
			if (!empty($search_criteria['Fields'])) {
				foreach ($search_criteria['Fields'] as $d) {
					foreach ($d as $fieldid => $filter) {
						if (!is_array($filter)) {
							$new_criteria['customfields'][$fieldid] = $filter;
						} else {
							// it's a date filter? convert it.
							if (isset($filter['enable']) && $filter['enable'] == 'on') {
								unset($filter['enable']);
								$filter['filter'] = 1;
								$new_criteria['customfields'][$fieldid] = $filter;
							}
						}
					}
				}
			}

			$htmlbody = html_entity_decode(stripslashes($row['htmlbody']));
			$htmlbody = str_replace(SENSTUDIO_BASE_APPLICATION_URL.'/temp/images', SENDSTUDIO_TEMP_URL.'/user', $htmlbody);

			$update_query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "autoresponders SET name='" . $this->Db->Quote(stripslashes($row['name'])) . "', subject='" . $this->Db->Quote(stripslashes($row['subject'])) . "', htmlbody='" . $this->Db->Quote($htmlbody) . "', textbody='" . $this->Db->Quote(stripslashes($row['textbody'])) . "', searchcriteria='" . $this->Db->Quote(serialize($new_criteria)) . "' WHERE autoresponderid='" . $row['autoresponderid'] . "'";
			$update_result = $this->Db->Query($update_query);
			if ($ok) {
				$ok = $update_result;
			}
		}

		return $ok;
	}
}
