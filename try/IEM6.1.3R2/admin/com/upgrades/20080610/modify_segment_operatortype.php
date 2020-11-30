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
class modify_segment_operatortype extends Upgrade_API
{
	/**
	 * Affected custom field ID cache
	 * @var Array An array of string
	 */
	var $_cacheCustomFields = null;

	/**
	 * RunUpgrde
	 * Run current upgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		$rows = $this->Db->Query('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'segments');

		$success = true;
		$this->Db->StartTransaction();

		while (($record = $this->Db->Fetch($rows))) {
			$info = unserialize($record['searchinfo']);

			/**
			 * Only update segment version lesser than 20080610
			 */
				if (!isset($info['_cache']['cacheVersion'])) {
					continue;
				}

				if (intval($info['_cache']['cacheVersion']) < 20080610) {
					$status = $this->_modifyProblematicOperator($info['Rules']);

					if (!$status) {
						$success = false;
						break;
					}

					unset ($info['_cache']);

					$status = $this->Db->Query(	'UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'segments'
												. " SET searchinfo = '" . $this->Db->Quote(serialize($info)) . "'"
												. ' WHERE segmentid = ' . $record['segmentid']);

					if (!$status) {
						$success = false;
						break;
					}
				}
			/**
			 * -----
			 */
		}

		if ($success) {
			$this->Db->CommitTransaction();
		} else {
			$this->Db->RollbackTransaction();
		}

		$this->Db->FreeResult($rows);

		return $success;
	}

	/**
	 * _modifyProblematicOperator
	 * @param Array $rules (REF) Rules
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function _modifyProblematicOperator(&$rules)
	{
		foreach ($rules as $ruleIndex => $rule) {
			if ($rule['type'] == 'rule') {
				// Only process rules that uses "Custom Fields" and "Email"
				if (!is_numeric($rule['rules']['ruleName']) && $rule['rules']['ruleName'] != 'email') {
					continue;
				}

				// Only rule that have "equal" or "notequal" needed to be changed
				if (!in_array($rule['rules']['ruleOperator'], array('equalto', 'notequalto'))) {
					continue;
				}

				// Check if custom field type... Only "Text", "Textarea" needed to be changed
				if (is_numeric($rule['rules']['ruleName'])) {
					// Fetch affected custom fields ID
					if (is_null($this->_cacheCustomFields)) {
						$result = $this->Db->Query('SELECT fieldid FROM ' . SENDSTUDIO_TABLEPREFIX . "customfields WHERE fieldtype IN ('text', 'textarea')");
						if ($result == false) {
							return false;
						}

						$tempFieldID = array();

						while (($row = $this->Db->Fetch($result))) {
							array_push($tempFieldID, $row['fieldid']);
						}

						$this->Db->FreeResult($result);

						$this->_cacheCustomFields = $tempFieldID;
						unset($tempFieldID);
					}

					if (!in_array($rule['rules']['ruleName'], $this->_cacheCustomFields)) {
						continue;
					}
				}

				if ($rule['rules']['ruleOperator'] == 'equalto') {
					$new_rule = 'like';
				} else {
					$new_rule = 'notlike';
				}
				$rules[$ruleIndex]['rules']['ruleOperator'] = $new_rule;
			} else {
				$status = $this->_modifyProblematicOperator($rules[$ruleIndex]['rules']);
				if (!$status) {
					return false;
				}
			}
		}

		return true;
	}
}
