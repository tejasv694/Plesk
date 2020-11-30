<?php
/**
 * Contains a procedure to completly delete a user account from the system.
 *
 * While normal deletion will leave a lot of data behind (ie. lists, subscribers, stats, etc),
 * this script will delete ALL trace of the user account.
 *
 * All you need to do is edit the $userids variable to contains an array of user accounts
 * that you want to delete.
 *
 * NOTE: This file is used by API_USERS::deleteUserByID(). It should not be used (or called) directly.
 * This script is only a temporary solution, and will need to be refactored to the application.
 *
 * @package interspire.iem.lib
 */

/**
 * This is a temporary helper class that will completely delete user
 * along with its data from an installation.
 *
 * This is a temporary solution which we will need to refactor into the application.
 * Please do not use this class directly from your code. It might not be present in other versions.
 *
 * To delete a user, use API_USERS::deleteRecordByID() method
 *
 * @package interspire.iem.lib
 *
 * @todo refactor this into the application
 */
class HelperUserDelete
{
	public function deleteUsers($userids)
	{
		$status = array();

		foreach ($userids as $userid) {
			$m_userid = intval($userid);

			if ($m_userid == 0) {
				continue;
			}

			$status[$m_userid]= self::_deleteUser($m_userid);
		}

		if (!del_user_dir($userids)) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' - User files/data was not found?', E_USER_NOTICE);
			return false;
		}

		return $status;
	}

	static private function _deleteUser($userid)
	{
		$user   = API_USERS::getRecordById($userid);
		$status = array(
			'status' => false,
			'data' => array(
				'segments' => false,
				'templates' => false,
				'usergroups_access' => false,
				'usergroups_permissions' => false,
				'user_activitylog' => false,
				'autoresponders' => false,
				'customfields' => false,
				'folders' => false,
				'triggers' => false,
				'jobs_and_queues' => false,
				'forms' => false,
				'splittests' => false,
				'newsletters' => false,
				'lists' => false,
				'stats' => false,
				'users' => false
			)
		);

		// Delete "easy" data (ie. data that can be deleted without processing anything else)
		if (($temp = self::_deleteFromSimpleTable('segments', 'ownerid', $userid)) === false) return $status; else $status['data']['segments'] = $temp;
		if (($temp = self::_deleteFromSimpleTable('templates', 'ownerid', $userid)) === false) return $status; else $status['data']['templates'] = $temp;
		if (($temp = self::_deleteFromSimpleTable('user_activitylog', 'userid', $userid)) === false) return $status; else $status['data']['user_activitylog'] = $temp;

		// delete complex data
		if (($temp = self::_deleteComplexAutoresponder($userid)) === false) return $status; else $status['data']['autoresponders'] = $temp;
		if (($temp = self::_deleteComplexCustomFields($userid)) === false) return $status; else $status['data']['customfields'] = $temp;
		if (($temp = self::_deleteComplexFolders($userid)) === false) return $status; else $status['data']['folders'] = $temp;
		if (($temp = self::_deleteComplexTriggers($userid)) === false) return $status; else $status['data']['triggers'] = $temp;
		if (($temp = self::_deleteComplexJobsQueues($userid)) === false) return $status; else $status['data']['jobs_and_queues'] = $temp;
		if (($temp = self::_deleteComplexForms($userid)) === false) return $status; else $status['data']['forms'] = $temp;
		if (($temp = self::_deleteComplexSplittests($userid)) === false) return $status; else $status['data']['splittests'] = $temp;
		if (($temp = self::_deleteComplexNewsletters($userid)) === false) return $status; else $status['data']['newsletters'] = $temp;
		if (($temp = self::_deleteComplexLists($userid)) === false) return $status; else $status['data']['lists'] = $temp;
		if (($temp = self::_deleteComplexStats($userid)) === false) return $status; else $status['data']['stats'] = $temp;
		if (($temp = self::_deleteComplexUsers($userid)) === false) return $status; else $status['data']['users'] = $temp;

		$status['status'] = true;
		return $status;
	}

	static private function _deleteFromSimpleTable($table, $column, $userid)
	{
		$db = IEM::getDatabase();
		$tempRS = $db->Query("DELETE FROM [|PREFIX|]{$table} WHERE {$column} = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from {$table}: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		return $db->NumAffected($tempRS);
	}

	static private function _deleteComplexAutoresponder($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT autoresponderid, name, queueid FROM [|PREFIX|]autoresponders WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from autoresponders: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['autoresponderid'];
			$tempQueueid = $list['queueid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]queues WHERE queueid={$tempQueueid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from queues: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]jobs_lists WHERE jobid IN (SELECT jobid FROM [|PREFIX|]jobs WHERE queueid={$tempQueueid})");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from jobs_lists: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]jobs WHERE queueid={$tempQueueid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from jobs: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_autoresponders_recipients WHERE autoresponderid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_autoresponders_recipients: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_autoresponders WHERE autoresponderid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_autoresponders: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			// Even if you cannot delete the resource, continue along with the process....
			// However we need to note this incident in the error log!
			if (!self::_deleteResource(TEMP_DIRECTORY . "/autoresponders/{$tempID}")) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete autoresponder resources with autoresponderid = {$tempID}", E_USER_NOTICE);
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]autoresponders WHERE autoresponderid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from autoresponders: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($list);
	}

	static private function _deleteComplexCustomFields($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT fieldid, name FROM [|PREFIX|]customfields WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from customfields: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['fieldid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]form_customfields WHERE fieldid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from form_customfields: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]subscribers_data WHERE fieldid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from subscribers_data: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]customfield_lists WHERE fieldid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from customfield_lists: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]customfields WHERE fieldid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from customfields: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($lists);
	}

	static private function _deleteComplexFolders($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT folderid, name FROM [|PREFIX|]folders WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from folders: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['folderid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]folder_item WHERE folderid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from folder_item: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]folder_user WHERE folderid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from folder_user: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]folders WHERE folderid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from folders: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($lists);
	}

	static private function _deleteComplexTriggers($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT triggeremailsid, name, queueid, statid FROM [|PREFIX|]triggeremails WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from triggeremails: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['triggeremailsid'];
			$tempQueueid = $list['queueid'];
			$tempStatsid = $list['statid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]queues WHERE queueid={$tempQueueid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from queues: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]jobs_lists WHERE jobid IN (SELECT jobid FROM [|PREFIX|]jobs WHERE queueid={$tempQueueid})");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from jobs_lists: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]jobs WHERE queueid={$tempQueueid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from jobs: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]module_tracker WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from module_tracker: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_emailforwards WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_emailforwards: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_emailopens WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_emailopens: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_linkclicks WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_linkclicks: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_links WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_links: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_newsletter_lists WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_newsletter_lists: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_newsletters WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_newsletters: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_users WHERE statid={$tempStatsid}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_users: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]triggeremails_log_summary WHERE triggeremailsid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from triggeremails_log_summary: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]triggeremails_log WHERE triggeremailsid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from triggeremails_log: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]triggeremails_data WHERE triggeremailsid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from triggeremails_data: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]triggeremails_actions_data WHERE triggeremailsid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from triggeremails_actions_data: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]triggeremails_actions WHERE triggeremailsid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from triggeremails_actions: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]triggeremails WHERE triggeremailsid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from triggeremails: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($lists);
	}

	static private function _deleteComplexJobsQueues($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();
		$total = 0;

		$tempRS = $db->Query("SELECT jobid, queueid FROM [|PREFIX|]jobs WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from jobs: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (!empty($lists)) {
			foreach ($lists as $list) {
				$tempID = $list['jobid'];

				$tempRS = $db->Query("DELETE FROM [|PREFIX|]queues WHERE queueid={$list['queueid']}");
				if (!$tempRS) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from queues: " . $db->Error(), E_USER_NOTICE);
					return false;
				}

				$tempRS = $db->Query("DELETE FROM [|PREFIX|]queues_unsent WHERE queueid={$list['queueid']}");
				if (!$tempRS) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from queues_unsent: " . $db->Error(), E_USER_NOTICE);
					return false;
				}

				$tempRS = $db->Query("DELETE FROM [|PREFIX|]jobs_lists WHERE jobid IN (SELECT jobid FROM (SELECT jobid FROM [|PREFIX|]jobs WHERE jobid={$tempID}) AS x)");
				if (!$tempRS) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from jobs_lists: " . $db->Error(), E_USER_NOTICE);
					return false;
				}

				$tempRS = $db->Query("DELETE FROM [|PREFIX|]jobs WHERE jobid={$tempID}");
				if (!$tempRS) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from jobs: " . $db->Error(), E_USER_NOTICE);
					return false;
				}
			}

			$total += count($lists);
		}



		$tempRS = $db->Query("DELETE FROM [|PREFIX|]queues_unsent WHERE queueid IN (SELECT queueid FROM (SELECT queueid FROM [|PREFIX|]queues WHERE ownerid={$userid}) AS x)");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from queues_unsent: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		$total += $db->NumAffected($tempRS);

		$tempRS = $db->Query("DELETE FROM [|PREFIX|]queues WHERE ownerid={$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from queues: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		$total += $db->NumAffected($tempRS);

		return $total;
	}

	static private function _deleteComplexForms($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT formid FROM [|PREFIX|]forms WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from forms: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['formid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]form_customfields WHERE formid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from form_customfields: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]form_lists WHERE formid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from form_lists: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]form_pages WHERE formid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from form_pages: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]forms WHERE formid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from forms: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($lists);
	}

	static private function _deleteComplexSplittests($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT splitid FROM [|PREFIX|]splittests WHERE userid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from splittests: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['splitid'];

			$tempRS = $db->Query("
				DELETE FROM [|PREFIX|]splittest_statistics_newsletters
				WHERE split_statid IN
				(
					SELECT split_statid
					FROM [|PREFIX|]splittest_statistics
					WHERE splitid={$tempID}
				)");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from splittest_statistics_newsletters: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]splittest_statistics WHERE splitid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from splittest_statistics: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]splittest_campaigns WHERE splitid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from splittest_campaigns: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]splittests WHERE splitid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from splittests: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($lists);
	}

	static private function _deleteComplexNewsletters($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT newsletterid FROM [|PREFIX|]newsletters WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from newsletters: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['newsletterid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]module_tracker WHERE newsletterid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from module_tracker: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			if (!self::_deleteResource(TEMP_DIRECTORY . "/newsletters/{$tempID}")) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete resources for newsletterid={$tempID}", E_USER_NOTICE);
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]newsletters WHERE newsletterid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from newsletters: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($lists);
	}

	static private function _deleteComplexLists($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT listid FROM [|PREFIX|]lists WHERE ownerid = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from lists: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);

		if (empty($lists)) {
			return 0;
		}

		foreach ($lists as $list) {
			$tempID = $list['listid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]list_subscriber_bounces WHERE listid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from list_subscriber_bounces: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]list_subscriber_events WHERE listid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from list_subscriber_events: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]list_subscribers_unsubscribe WHERE listid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from list_subscribers_unsubscribe: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]banned_emails WHERE list='{$tempID}'");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from banned_emails: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempOffset = 0;
			do {
				$tempList = array();
				$tempRS = $db->Query("SELECT subscriberid FROM [|PREFIX|]list_subscribers WHERE listid={$tempID} LIMIT 500 OFFSET {$tempOffset}");
				if (!$tempRS) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from list_subscribers: " . $db->Error(), E_USER_NOTICE);
					return false;
				}

				while ($tempRow = $db->Fetch($tempRS)) {
					$tempList[] = $tempRow['subscriberid'];
				}

				$db->FreeResult($tempRS);

				if (empty($tempList)) {
					break;
				}

				$tempRS = $db->Query("DELETE FROM [|PREFIX|]subscribers_data WHERE subscriberid IN (" . implode(',', $tempList) . ")");
				if (!$tempRS) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from subscribers_data: " . $db->Error(), E_USER_NOTICE);
					return false;
				}

				$tempOffset += 500;
			} while(true);

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]list_subscribers WHERE listid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from list_subscribers: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]lists WHERE listid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from lists: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return count($lists);
	}

	static private function _deleteComplexStats($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("SELECT statid FROM [|PREFIX|]stats_newsletters WHERE sentby = {$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to query data from stats_users: " . $db->Error(), E_USER_NOTICE);
			return false;
		}		

		while ($tempRow = $db->Fetch($tempRS)) {
			$lists[] = $tempRow;
		}

		$db->FreeResult($tempRS);
		
		$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_newsletters WHERE sentby='{$userid}'");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_newsletters: " . $db->Error(), E_USER_NOTICE);
			return false;
		}
		
		$tempRS = $db->Query("DELETE FROM [|PREFIX|]user_stats_emailsperhour WHERE userid='{$userid}'");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from user_stats_emailsperhour: " . $db->Error(), E_USER_NOTICE);
			return false;
		}
		$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_users WHERE userid='{$userid}'");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_users: " . $db->Error(), E_USER_NOTICE);
			return false;
		}		
		foreach ($lists as $list) {
			$tempID = $list['statid'];

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_newsletter_lists WHERE statid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_newsletter_lists: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_links WHERE statid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_links: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_linkclicks WHERE statid={$tempID}");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_linkclicks: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_emailopens WHERE statid='{$tempID}'");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_emailopens: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]stats_emailforwards WHERE statid='{$tempID}'");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from stats_emailforwards: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$tempRS = $db->Query("DELETE FROM [|PREFIX|]module_tracker WHERE statid='{$tempID}'");
			if (!$tempRS) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from module_tracker: " . $db->Error(), E_USER_NOTICE);
				return false;
			}

		}

		return count($lists);
	}

	static private function _deleteComplexUsers($userid)
	{
		$db = IEM::getDatabase();
		$lists = array();

		$tempRS = $db->Query("DELETE FROM [|PREFIX|]user_stats_emailsperhour WHERE userid={$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from user_stats_emailsperhour: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		$tempRS = $db->Query("DELETE FROM [|PREFIX|]user_credit WHERE userid={$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from user_credit: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		if (!self::_deleteResource(TEMP_DIRECTORY . "/user/{$userid}")) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete resources for user {$userid}", E_USER_NOTICE);
		}

		$tempRS = $db->Query("DELETE FROM [|PREFIX|]users WHERE userid={$userid}");
		if (!$tempRS) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " - Unable to delete data from users: " . $db->Error(), E_USER_NOTICE);
			return false;
		}

		return 1;
	}

	static private function _deleteResource($fullpath)
	{
		if (!is_readable($fullpath)) {
			return false;
		}

		if (!is_dir($fullpath)) {
			return unlink($fullpath);
		} else {
			$contents = scandir($fullpath);
			foreach ($contents as $each) {
				if (in_array($each, array('.', '..'))) continue;
				if (!self::_deleteResource("{$fullpath}/{$each}")) return false;
			}

			return rmdir($fullpath);
		}
	}
}