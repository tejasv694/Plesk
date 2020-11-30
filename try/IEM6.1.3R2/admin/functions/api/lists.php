<?php
/**
* The List API.
*
* @version     $Id: lists.php,v 1.63 2008/01/29 04:44:54 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Lists_API
*/

/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This will load a list, save a list, set details and get details.
* It will also check access areas.
*
* @package API
* @subpackage Lists_API
*/
class Lists_API extends API
{

	/**
	* The List that is loaded. By default is 0 (no list).
	*
	* @var Int
	*/
	var $listid = 0;

	/**
	* Name of the list that we've loaded.
	*
	* @var String
	*/
	var $name = '';

	/**
	* Email address of the list owner.
	*
	* @var String
	*/
	var $owneremail = '';

	/**
	* Name of the list owner.
	*
	* @var String
	*/
	var $ownername = '';

	/**
	* The lists default bounce email address.
	*
	* @var String
	*/
	var $bounceemail = '';

	/**
	* The lists default reply-to email address.
	*
	* @var String
	*/
	var $replytoemail = '';

	/**
	* The lists format
	*
	* @var String
	*/
	var $format = 'b';

	/**
	* This is the company name.
	*
	* @var String
	*/
	var $companyname = '';

	/**
	* This is the company address.
	*
	* @var String
	*/
	var $companyaddress = '';

	/**
	* This is the company phone number.
	*
	* @var String
	*/
	var $companyphone = '';

	/**
	* This is to confirm that the user wishs to process bounces on this mailing list.
	*
	* @var Boolean
	*/
	var $processbounce = '0';

	/**
	* This is to confirm that the user understands that processing bounces will delete all bounce emails in the account.
	*
	* @var Boolean
	*/
	var $agreedelete = '0';

	/**
	* Whether to delete *all* emails in the bounce account (not just bounce emails).
	*
	* @var Boolean
	*/
	var $agreedeleteall = '0';

	/**
	* This is the bounce email server (used for processing bounced emails).
	*
	* @var String
	*/
	var $bounceserver = '';

	/**
	* This is the bounce email username (used for processing bounced emails).
	*
	* @var String
	*/
	var $bounceusername = '';

	/**
	* This is the bounce email password (used for processing bounced emails).
	*
	* @var String
	*/
	var $bouncepassword = '';

	/**
	* Whether the list notifies the owner about subscribes/unsubscribes
	*
	* @var Boolean
	*/
	var $notifyowner = false;

	/**
	* List of fields to display when viewing the subscribers for a list
	*
	* @var Boolean
	*/
	var $visiblefields = '';

	/**
	* Whether the bounce email account is an imap account or not. If this is false, it is a POP3 account.
	*
	* @var Boolean
	*/
	var $imapaccount = false;

	/**
	* Extra email account settings. For example '/notls'.
	*
	* @var String
	*/
	var $extramailsettings = '';

	/**
	* The userid of the owner of this list.
	*
	* @var Int
	*/
	var $ownerid = 0;

	/**
	* The timestamp of when the list was created (integer)
	*
	* @var Int
	*/
	var $createdate = 0;

	/**
	* Default Order to show templates in.
	* @see GetLists
	*
	* @var String
	*/
	var $DefaultOrder = 'name';

	/**
	* Default direction to show lists in.
	*
	* @see GetLists
	*
	* @var String
	*/
	var $DefaultDirection = 'up';

	/**
	* An array of valid sorts that we can use here. This makes sure someone doesn't change the query to try and create an sql error.
	*
	* @see GetLists
	*
	* @var Array
	*/
	var $ValidSorts = array('name' => 'Name', 'date' => 'CreateDate', 'subscribers' => 'subscribecount', 'unsubscribes' => 'unsubscribecount', 'fullname' => 'fullname');

	/**
	* The active subscriber count for this particular mailing list. This is used to save doing joins when retrieveing a list of mailing lists and their subscriber counts.
	*
	* @var Int
	*/
	var $subscribecount = 0;

	/**
	* The unsubscribe count for this particular mailing list.
	*
	* @var Int
	*/
	var $unsubscribecount = 0;

	/**
	* The custom fields associated with this list.
	* This allows us to automatically create a custom field -> list association when we create a new list.
	*/
	var $customfields = array();

	/**
	* Constructor
	* Sets up the database object, loads the list if the ID passed in is not 0.
	*
	* @param Int $listid The listid of the list to load. If it is 0 then you get a base class only. Passing in a listid > 0 will load that list.
	* @param Boolean $connect_to_db Whether to connect to the database or not. If this is set to false, you need to set the database up yourself.
	*
	* @see Load
	* @see GetDb
	*
	* @return Boolean If no listid is passed in, this will return true. If a listid is passed in, this will return the status from Load
	*/
	function Lists_API($listid=0, $connect_to_db=true)
	{
		if ($connect_to_db) {
			$this->GetDb();
		}

		if ($listid >= 0) {
			return $this->Load($listid);
		}
		return true;
	}

	/**
	* Load
	* Loads up the list and sets the appropriate class variables.
	*
	* @param Int $listid The listid to load up. If the listid is not present then it will not load up.
	*
	* @return Boolean Will return false if the listid is not present, or the list can't be found, otherwise it set the class vars and return true.
	*/
	function Load($listid=0)
	{
		$listid = (int)$listid;
		if ($listid <= 0) {
			return false;
		}

		$query = 'SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'lists WHERE listid=\'' . $listid . '\'';
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$list = $this->Db->Fetch($result);
		if (empty($list)) {
			return false;
		}

		$this->listid = $list['listid'];
		$this->name = $list['name'];
		$this->ownername = $list['ownername'];
		$this->owneremail = $list['owneremail'];
		$this->bounceemail = $list['bounceemail'];
		$this->replytoemail = $list['replytoemail'];
		$this->notifyowner = ($list['notifyowner'] == 1) ? true : false;
		$this->imapaccount = ($list['imapaccount'] == 1) ? true : false;
		$this->createdate = $list['createdate'];
		$this->format = $list['format'];
		$this->processbounce = $list['processbounce'];
		$this->agreedelete = $list['agreedelete'];
		$this->agreedeleteall = $list['agreedeleteall'];
		$this->bounceserver = $list['bounceserver'];
		$this->bounceusername = $list['bounceusername'];
		$this->bouncepassword = base64_decode($list['bouncepassword']);
		$this->visiblefields = $list['visiblefields'];
		$this->ownerid = $list['ownerid'];
		$this->subscribecount = (int)$list['subscribecount'];
		$this->unsubscribecount = (int)$list['unsubscribecount'];
		$this->extramailsettings = $list['extramailsettings'];
		$this->companyname = $list['companyname'];
		$this->companyphone = $list['companyphone'];
		$this->companyaddress = $list['companyaddress'];

		$field_assocs = array();
		$query = "SELECT fieldid FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE listid=" . intval($listid);
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$field_assocs[] = $row['fieldid'];
		}
		$this->customfields = $field_assocs;
		return true;
	}

	/**
	* Create
	* This function creates a list based on the current class vars.
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Create()
	{
		$createdate = $this->GetServerTime();
		if ((int)$this->createdate > 0) {
			$createdate = (int)$this->createdate;
		}


		$tempQuoted = array(
			'name'				=> $this->Db->Quote($this->name),
			'owneremail'		=> $this->Db->Quote($this->owneremail),
			'ownername'			=> $this->Db->Quote($this->ownername),
			'bounceemail'		=> $this->Db->Quote($this->bounceemail),
			'replytoemail'		=> $this->Db->Quote($this->replytoemail),
			'format'			=> $this->Db->Quote($this->format),
			'createdate'		=> intval($createdate),
			'notifyowner'		=> intval($this->notifyowner),
			'imapaccount'		=> intval($this->imapaccount),
			'bounceserver'		=> $this->Db->Quote($this->bounceserver),
			'bounceusername'	=> $this->Db->Quote($this->bounceusername),
			'bouncepassword'	=> $this->Db->Quote(base64_encode($this->bouncepassword)),
			'extramailsettings'	=> $this->Db->Quote($this->extramailsettings),
			'companyname'		=> $this->Db->Quote($this->companyname),
			'companyaddress'	=> $this->Db->Quote($this->companyaddress),
			'companyphone'		=> $this->Db->Quote($this->companyphone),
			'ownerid'			=> intval($this->ownerid),
			'processbounce'		=> intval($this->processbounce),
			'agreedelete'		=> intval($this->agreedelete),
			'agreedeleteall'	=> intval($this->agreedeleteall),
			'visiblefields'		=> $this->Db->Quote($this->visiblefields)
		);

		$query = "
			INSERT INTO [|PREFIX|]lists (
				name, owneremail, ownername,
				bounceemail, replytoemail, format, createdate, notifyowner,
				imapaccount, bounceserver, bounceusername, bouncepassword, extramailsettings,
				companyname, companyaddress, companyphone,
				ownerid, subscribecount, unsubscribecount,
				processbounce, agreedelete, agreedeleteall, visiblefields
			) VALUES (
				'{$tempQuoted['name']}', '{$tempQuoted['owneremail']}', '{$tempQuoted['ownername']}',
				'{$tempQuoted['bounceemail']}', '{$tempQuoted['replytoemail']}', '{$tempQuoted['format']}', {$tempQuoted['createdate']}, '{$tempQuoted['notifyowner']}',
				'{$tempQuoted['imapaccount']}', '{$tempQuoted['bounceserver']}', '{$tempQuoted['bounceusername']}', '{$tempQuoted['bouncepassword']}', '{$tempQuoted['extramailsettings']}',
				'{$tempQuoted['companyname']}', '{$tempQuoted['companyaddress']}', '{$tempQuoted['companyphone']}',
				{$tempQuoted['ownerid']}, 0, 0,
				'{$tempQuoted['processbounce']}', '{$tempQuoted['agreedelete']}', '{$tempQuoted['agreedeleteall']}', '{$tempQuoted['visiblefields']}'
			)
		";

		$result = $this->Db->Query($query);

		if ($result) {
			$listid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'lists_sequence');
			$this->listid = $listid;

			foreach ($this->customfields as $fieldid) {
				$this->Db->Query("INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "customfield_lists (listid, fieldid) VALUES (" . $listid . ", " . intval($fieldid) . ")");
			}

			/**
			 * Clear out list cache
			 */
			if (array_key_exists('Lists_API::GetListByUserID[listCache]', $GLOBALS)) {
				unset($GLOBALS['Lists_API::GetListByUserID[listCache]']);
			}

			return $listid;
		}

		return false;
	}

	/**
	* Find
	* This function finds a list based on the name passed in. If it's an integer, it will find the list based on that id. If it's a string, it will search for it by name. If it finds more than one, it will return -1.
	*
	* @param Mixed $name The list to find. This could be a string (list name) or an integer.
	*
	* @return Mixed Will return the listid if it's found, false if it can't be found (or it's an invalid type of name), or -1 if there are multiple results.
	*/
	function Find($name=false)
	{
		if (!$name) {
			return false;
		}

		if (is_numeric($name)) {
			$query = "SELECT listid FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE listid='" . $this->Db->Quote($name) . "'";
		} else {
			$query = "SELECT listid FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE name='" . $this->Db->Quote($name) . "'";
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$num_results = $this->Db->CountResult($result);
		if ($num_results > 1) {
			return -1;
		}
		if ($num_results == 0) {
			return false;
		}
		$listid = $this->Db->FetchOne($result);
		return $listid;
	}

	/**
	* Delete
	* Delete a list from the database. First we delete the list (and check the result for that), then we delete the subscribers for the list, the 'custom field data' for the list, the user permissions for the list, and finally reset all class vars.
	*
	* @param Int $listid Listid of the list to delete. If not passed in, it will delete 'this' list.
	* @param Int $userid The userid that is deleting the list. This is used so the stats api can "hide" stats.
	*
	* @see Stats_API::HideStats
	* @see DeleteAllSubscribers
	*
	* @return Boolean True if it deleted the list, false otherwise.
	*
	*/
	function Delete($listid=0, $userid=0)
	{
		$listid = (int)$listid;
		if ($listid == 0) {
			$listid = $this->listid;
		}

		$this->Db->StartTransaction();

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE listid=" . $listid;
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($error, $level);
			return false;
		}

		$this->DeleteAllSubscribers($listid, true);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE listid=" . $listid;
		$result = $this->Db->Query($query);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "form_lists WHERE listid=" . $listid;
		$result = $this->Db->Query($query);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "usergroups_access WHERE resourcetype='lists' AND resourceid=" . $listid;
		$result = $this->Db->Query($query);

		if (!class_exists('stats_api', false)) {
			require_once(dirname(__FILE__) . '/stats.php');
		}
		$stats_api = new Stats_API();

		// clean up stats
		$stats = array('0');
		$query = "SELECT statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists WHERE listid=" . $listid;
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$stats[] = $row['statid'];
		}

		$stats_api->HideStats($stats, 'newsletter', $userid);

		$stats = array('0');

		$query = "SELECT statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders sa, " . SENDSTUDIO_TABLEPREFIX . "autoresponders a WHERE a.autoresponderid=sa.autoresponderid AND a.listid=" . $listid;
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$stats[] = $row['statid'];
		}

		$stats_api->HideStats($stats, 'autoresponder', $userid);

		// autoresponder queues are cleaned up in DeleteAllSubscribers.
		// we just need to clean up the autoresponders.
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders WHERE listid=" . $listid;
		$result = $this->Db->Query($query);

		// clean up banned emails.
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "banned_emails WHERE list='" . $listid . "'";
		$result = $this->Db->Query($query);

		if ($listid == $this->listid) {
			$this->listid = 0;
			$this->name = '';
			$this->ownername = '';
			$this->owneremail = '';
			$this->bounceemail = '';
			$this->replytoemail = '';
			$this->bounceserver = '';
			$this->bounceusername = '';
			$this->bouncepassword = '';
			$this->extramailsettings = '';
			$this->subscribecount = 0;
			$this->unsubscribecount = 0;
		}

		/**
		 * Clear out list cache
		 */
		if (array_key_exists('Lists_API::GetListByUserID[listCache]', $GLOBALS)) {
			unset($GLOBALS['Lists_API::GetListByUserID[listCache]']);
		}

		$this->Db->CommitTransaction();

		return true;
	}

	/**
	* DeleteAllSubscribers
	* Deletes all subscribers from the database.
	* We do not delete custom field info because custom fields can be associated with multiple lists.
	* If that needs doing, deleting the custom field itself will clean up that extra data.
	* This will also clean up any autoresponder queues that are present for this list so nobody can be emailed accidentally.
	*
	* @param Int $listid Listid of the list to delete. If not passed in, it will delete subscribers from 'this' list.
	* @param Boolean $called_inside_lists Whether this function is called from inside the lists api or not. If it is, there is no need to update the lists table because it has been deleted. This is only set to TRUE by the Delete method in the Lists_API class.
	*
	* @see ClearQueue
	* @see Lists_API::Delete
	*
	* @return Boolean Returns false if an invalid listid is passed in (less than 0), otherwise does it's work and returns true.
	*/
	function DeleteAllSubscribers($listid=0, $called_inside_lists=false)
	{
		$listid = (int)$listid;
		if ($listid <= 0) {
			$listid = $this->listid;
		}

		// if the listid is still less than 0, return an error.
		if ($listid <= 0) {
			return false;
		}

		$this->Db->StartTransaction();

		// fix stats only if we're not called from inside the lists api.
		// if we are, the only place that calls it is the Delete method
		// which deletes the list from the database - so no need to update stats.
		if (!$called_inside_lists) {
			$query = "UPDATE [|PREFIX|]lists SET unsubscribecount=0, subscribecount=0 WHERE listid={$listid}";
			$result = $this->Db->Query($query);
			if (!$result) {
				$this->Db->RollbackTransaction();
				trigger_error('Lists_API::DeleteAllSubscribers -- Unable to query database -- ' . $this->Db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		// ----- Delete data
			$query_subscriber_in_list = "SELECT subscriberid FROM [|PREFIX|]list_subscribers WHERE listid={$listid}";
			$status = $this->Db->Query($query_subscriber_in_list);
			if (!$status) {
				$this->Db->RollbackTransaction();
				trigger_error('Lists_API::DeleteAllSubscribers -- Unable to query database -- ' . $this->Db->Error(), E_USER_NOTICE);
				return false;
			}
			while($row = $this->Db->Fetch($status)){
				$query = "DELETE FROM [|PREFIX|]subscribers_data WHERE subscriberid = ".$row['subscriberid'];
				$res = $this->Db->Query($query);
				if(!$res){trigger_error(mysql_error());}
			}
			$event_count_query = "SELECT COUNT(eventid) FROM [|PREFIX|]list_subscriber_events WHERE listid = {$listid}";
			$res = $this->Db->Query($event_count_query);
			if(!$res){trigger_error(mysql_error());}
			$event_count = (int) $this->Db->FetchOne($res);
			if($event_count < 1000){
				$query = "DELETE FROM [|PREFIX|]list_subscriber_events WHERE listid = {$listid}";
				$res = $this->Db->Query($query);
				if(!$res){trigger_error(mysql_error());}				
			} else {
				for ($i = 1; $i <= $event_count; $i++) {
					"DELETE FROM [|PREFIX|]list_subscriber_events WHERE listid = {$listid} LIMIT 1";
					$res = $this->Db->Query($query);
					if(!$res){trigger_error(mysql_error());}
				}
				$query = "DELETE FROM [|PREFIX|]list_subscriber_events WHERE listid = {$listid}";
				$res = $this->Db->Query($query);
				if(!$res){trigger_error(mysql_error());}				
			}
			$queries = array();
			//$queries[] = "DELETE FROM [|PREFIX|]subscribers_data WHERE subscriberid IN ({$query_subscriber_in_list})";
			$queries[] = "DELETE FROM [|PREFIX|]queues WHERE recipient IN ({$query_subscriber_in_list})";
			$queries[] = "DELETE FROM [|PREFIX|]queues_unsent WHERE recipient IN ({$query_subscriber_in_list})";
			//$queries[] = "DELETE FROM [|PREFIX|]list_subscriber_events WHERE listid = {$listid}";
			$queries[] = "DELETE FROM [|PREFIX|]list_subscriber_bounces WHERE listid = {$listid}";
			$queries[] = "DELETE FROM [|PREFIX|]list_subscribers_unsubscribe WHERE listid = {$listid}";
			$queries[] = "DELETE FROM [|PREFIX|]queues WHERE queueid IN (SELECT queueid FROM [|PREFIX|]autoresponders WHERE listid = {$listid})";
			$queries[] = "DELETE FROM [|PREFIX|]list_subscribers WHERE listid = {$listid}";

			foreach ($queries as $query) {
				$status = $this->Db->Query($query);
				if (!$status) {
					$this->Db->RollbackTransaction();
					trigger_error('Lists_API::DeleteAllSubscribers -- Unable to query database -- ' . $this->Db->Error(), E_USER_NOTICE);
					return false;
				}
			}
		// -----

		/**
		 * Clear out list cache
		 */
		if (array_key_exists('Lists_API::GetListByUserID[listCache]', $GLOBALS)) {
			unset($GLOBALS['Lists_API::GetListByUserID[listCache]']);
		}

		$this->Db->CommitTransaction();

		return true;
	}

	/**
	* ChangeSubscriberFormat
	* Changes all subscribers for a list to a particular format.
	*
	* @param String $format Format to change subscribers to. This can be 'h', 'html', 't', 'text'.
	* @param Int $listid Listid of the list to change. If not passed in, it will change 'this' list.
	*
	* @return Array Returns an array consisting of the success/failure and a reason why. If it's an invalid format passed in it will return failure. If it's a valid format, it will return success.
	*/
	function ChangeSubscriberFormat($format='html', $listid=0)
	{
		$listid = (int)$listid;

		if ($listid <= 0) {
			$listid = $this->listid;
		}

		$format = strtolower($format);
		if ($format == 'html') {
			$format = 'h';
		}

		if ($format == 'text') {
			$format = 't';
		}

		if ($format != 'h' && $format != 't') {
			return array(false, 'Invalid Format supplied');
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET format='" . $format . "' WHERE listid=" . $listid;
		$this->Db->Query($query);
		return array(true, false);
	}

	/**
	* ChangeSubscriberStatus
	* Changes all subscribers for a list to a particular status.
	*
	* @param String $status Status to change subscribers to. This can be 'a', 'active', 'i', 'inactive'.
	* @param Int $listid Listid of the list to change. If not passed in, it will change 'this' list.
	*
	* @return Array Returns an array consisting of the success/failure and a reason why. If it's an invalid status passed in it will return failure. If it's a valid status, it will return success.
	*/
	function ChangeSubscriberStatus($status='active', $listid=0)
	{
		$listid = (int)$listid;

		if ($listid <= 0) {
			$listid = $this->listid;
		}

		$status = strtolower($status);
		if ($status == 'active') {
			$status = 'a';
		}

		if ($status == 'inactive') {
			$status = 'i';
		}

		if ($status != 'a' && $status != 'i') {
			return array(false, 'Invalid Status supplied');
		}

		if ($status == 'a') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET bounced=0, unsubscribed=0 WHERE listid=" . $listid;
		}

		if ($status == 'b') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET bounced=" . $this->GetServerTime() . ", unsubscribed=0 WHERE listid=" . $listid;
		}

		if ($status == 'u') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET bounced=0, unsubscribed=" . $this->GetServerTime() . " WHERE listid=" . $listid;
		}
		$this->Db->Query($query);
		return array(true, false);
	}

	/**
	* ChangeSubscriberConfirm
	* Changes all subscribers for a list to a particular confirmation status.
	*
	* @param String $status Status to change subscribers to. This can be 'c', 'confirm', 'confirmed', 'u', 'unconfirm', 'unconfirmed'.
	* @param Int $listid Listid of the list to change. If not passed in, it will change 'this' list.
	*
	* @return Array Returns an array consisting of the success/failure and a reason why. If it's an invalid status passed in it will return failure. If it's a valid status, it will return success.
	*/
	function ChangeSubscriberConfirm($status='confirm', $listid=0)
	{
		$listid = (int)$listid;
		if ($listid <= 0) {
			$listid = $this->listid;
		}

		$status = strtolower($status);
		if ($status == 'confirm' || $status == 'confirmed') {
			$status = 'c';
		}

		if ($status == 'unconfirm' || $status == 'unconfirmed') {
			$status = 'u';
		}

		if ($status != 'c' && $status != 'u') {
			return array(false, 'Invalid Status supplied');
		}

		if ($status == 'c') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET confirmed='1' WHERE listid=" . $listid;
		}

		if ($status == 'u') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET confirmed='0' WHERE listid=" . $listid;
		}
		$this->Db->Query($query);
		return array(true, false);
	}

	/**
	* Copy
	* Copy list details only along with custom field associations.
	*
	* @param Int $int Listid to copy.
	*
	* @see Load
	* @see Create
	* @see Save
	* @see CopyCustomFields
	*
	* @return Array Returns an array of status (whether the copy worked or not) and a message to go with it. If the copy worked, then the message is 'false'.
	*/
	function Copy($oldid=0)
	{
		$oldid = (int)$oldid;
		if ($oldid <= 0) {
			return array(false, 'No ID');
		}

		if (!$this->Load($oldid)) {
			return array(false, 'Unable to load old list.');
		}

		$this->name = GetLang('CopyPrefix') . $this->name;

		/**
			the Create method looks at the createdate class variable to see if it can use it, or if it should use 'now'.
			So we need to re-set it to 0.
		*/
		$this->createdate = 0;

		$newid = $this->Create();
		if (!$newid) {
			return array(false, 'Unable to create new list');
		}

		/**
		 * Clear out list cache
		 */
		if (array_key_exists('Lists_API::GetListByUserID[listCache]', $GLOBALS)) {
			unset($GLOBALS['Lists_API::GetListByUserID[listCache]']);
		}

		return array(true, $newid);
	}

	/**
	* GetSubscriberCount
	* Gets a subscriber count for the list id passed in. This will check the type and return the number.
	*
	* @param Int $listid Listid to get count for. If not supplied, defaults to this list.
	* @param String $counttype The type of count to get. Defaults to active user count.
	*
	* @return Int The number of subscribers on the list.
	*/
	function GetSubscriberCount($listid=0, $counttype='')
	{
		$listid = (int)$listid;
		if ($listid <= 0) {
			$listid = $this->listid;
		}

		if ($listid <= 0) {
			return 0;
		}

		switch (strtolower($counttype)) {
			default:
				$query = "SELECT COUNT(subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid='" . $listid . "' AND bounced = 0 AND unsubscribed = 0";
			break;
		}
		$result = $this->Db->Query($query);
		$count = $this->Db->FetchOne($result, 'count');
		return $count;
	}

	/**
	* Save
	* This function saves the current class vars to the list.
	* If a list isn't loaded, it will return failure.
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Save()
	{
		if ($this->listid <= 0) {
			return false;
		}

		$listid = $this->listid;

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET name='" . $this->Db->Quote($this->name) . "', ownername='" . $this->Db->Quote($this->ownername) . "', owneremail='" . $this->Db->Quote($this->owneremail) . "', bounceemail='" . $this->Db->Quote($this->bounceemail) . "', replytoemail='" . $this->Db->Quote($this->replytoemail) . "', notifyowner='" . $this->Db->Quote((int)$this->notifyowner) . "', imapaccount='" . $this->Db->Quote((int)$this->imapaccount) . "', format='" . $this->Db->Quote($this->format) . "', bounceserver='" . $this->Db->Quote($this->bounceserver) . "', bounceusername='" . $this->Db->Quote($this->bounceusername) . "', bouncepassword='" .
		$this->Db->Quote(base64_encode($this->bouncepassword)) . "',visiblefields='" .
		$this->Db->Quote($this->visiblefields) . "', extramailsettings='" . $this->Db->Quote($this->extramailsettings) . "', companyname='" . $this->Db->Quote($this->companyname) . "', companyaddress='" . $this->Db->Quote($this->companyaddress) . "', companyphone='" . $this->Db->Quote($this->companyphone) . "', processbounce='" . intval($this->processbounce) . "', agreedelete='" . intval($this->agreedelete) . "', agreedeleteall='" . intval($this->agreedeleteall) . "' WHERE listid=" . intval($this->listid);
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$this->Db->Query("DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE listid=" . intval($listid));

		foreach ($this->customfields as $fieldid) {
			$this->Db->Query("INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "customfield_lists (listid, fieldid) VALUES (" . intval($listid) . ", " . intval($fieldid) . ")");
		}

		/**
		 * Clear out list cache
		 */
		if (array_key_exists('Lists_API::GetListByUserID[listCache]', $GLOBALS)) {
			unset($GLOBALS['Lists_API::GetListByUserID[listCache]']);
		}

		return true;
	}

	/**
	* GetListFormat
	* Returns the list formats that subscribers can join as.
	*
	* @see format
	*
	* @return Char Which format the list accepts for subscribers.
	*/
	function GetListFormat()
	{
		return $this->format;
	}

	/**
	* GetCustomFields
	* Fetches custom fields for the list(s) specified. Returns an array with the fieldid, name, type, default, required and settings.
	*
	* @param Array $listids An array of listids to get custom fields for. If not passed in, it will use 'this' list. If it's not an array, it will be converted to one.
	*
	* @return Array Custom field information for the list provided.
	*/
	function GetCustomFields($listids=array(), $type="")
	{
		if (!is_array($listids)) {
			$listid = (int)$listids;
			if ($listid <= 0) {
				$listid = $this->listid;
			}
			$listids = array($listid);
		} else {
			if (empty($listids)) {
				$listids = array($this->listid);
			}
		}

		$listids = $this->CheckIntVars($listids);
		if (empty($listids)) {
			$listids = array('0');
		}

		$qry = "SELECT f.fieldid, f.name, f.fieldtype, f.defaultvalue, f.required, f.fieldsettings, f.ownerid, f.createdate FROM " . SENDSTUDIO_TABLEPREFIX . "customfields f, " . SENDSTUDIO_TABLEPREFIX . "customfield_lists l WHERE l.fieldid=f.fieldid AND l.listid IN (" . implode(',', $listids) . ")";

		if (!empty($type)) {
			$qry = $qry . " AND f.fieldtype = '" . $this->Db->Quote($type) . "'";
		}

		// if a custom field is mapped to multiple lists, we only want the custom field to be returned once.
		if (sizeof($listids) > 1) {
			$qry .= " GROUP BY f.fieldid, f.name, f.fieldtype, f.defaultvalue, f.required, f.fieldsettings, f.ownerid, f.createdate";
		}

		$qry .= " ORDER BY f.name ASC, f.fieldid";

		$fieldlist = array();

		$result = $this->Db->Query($qry);
		while ($row = $this->Db->Fetch($result)) {
			$fieldlist[$row['fieldid']] = $row;
		}
		return $fieldlist;
	}

	/**
	* CopyCustomFields
	* Copies custom fields from one list to the other. This is a 'shortcut' approach to getting each custom field, getting its associations and updating them.
	*
	* @param Array $fromlistids Which lists to copy the custom fields from.
	* @param Int $tolistid Which list to copy the custom fields to. Defaults to 'this' list.
	*
	* @return Boolean Whether the copy worked or not.
	*/
	function CopyCustomFields($fromlistids=array(), $tolistid=0)
	{
		if ($fromlistids <= 0 || empty($fromlistids)) {
			return false;
		}

		if (!is_array($fromlistids)) {
			$fromlistids = array($fromlistids);
		}

		if ($tolistid <= 0) {
			$tolistid = $this->listid;
		}

		$fromlistids = array_map('intval', $fromlistids);
		$tolistid = (int)$tolistid;

		$qry = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "customfield_lists (listid, fieldid) SELECT DISTINCT " . $tolistid . ", fieldid FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists l WHERE l.listid IN (" . implode(',', $fromlistids) . ")";

		$result = $this->Db->Query($qry);
		return (bool)$result;
	}

	/**
	* GetLists
	* Get a list of lists based on the criteria passed in.
	*
	* @param Mixed $lists This is used to restrict which lists this will fetch information for. If this is not passed in (it's null), then all lists are checked. If this is not null, it will be an array of listid's to page through. This is so a user is restricted as to which lists they are shown.
	* @param Array $sortinfo An array of sorting information - what to sort by and what direction.
	* @param Boolean $countonly Whether to only get a count of lists, rather than the information.
	* @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	* @param Int|String $perpage How many results to return (max).
	*
	* @see CheckIntVars
	* @see ValidSorts
	* @see DefaultOrder
	* @see DefaultDirection
	*
	* @return Mixed Returns false if it couldn't retrieve list information. Otherwise returns the count (if specified), or an array of lists.
	*/
	function GetLists($lists=null, $sortinfo=array(), $countonly=false, $start=0, $perpage=10)
	{
		$start = (int)$start;

		if (is_array($lists)) {
			$lists = $this->CheckIntVars($lists);
			$lists[] = '0';
		}

		if ($countonly) {
			$query = "SELECT COUNT(listid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "lists";
			if (is_array($lists)) {
				$query .= " WHERE listid IN (" . implode(',', $lists) . ")";
			}
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT l.listid, l.name, l.createdate, l.subscribecount, l.unsubscribecount, l.ownerid, u.username, u.fullname FROM " . SENDSTUDIO_TABLEPREFIX . "lists AS l";

		$query .= " LEFT JOIN " . SENDSTUDIO_TABLEPREFIX . "users AS u ON (l.ownerid = u.userid)";

		if (is_array($lists)) {
			$query .= " WHERE l.listid IN (" . implode(',', $lists) . ")";
		}

		$order = (isset($sortinfo['SortBy']) && !is_null($sortinfo['SortBy'])) ? strtolower($sortinfo['SortBy']) : $this->DefaultOrder;

		$order = (in_array($order, array_keys($this->ValidSorts))) ? $this->ValidSorts[$order] : $this->DefaultOrder;

		$direction = (isset($sortinfo['Direction']) && !is_null($sortinfo['Direction'])) ? $sortinfo['Direction'] : $this->DefaultDirection;

		$direction = (strtolower($direction) == 'up' || strtolower($direction) == 'asc') ? 'ASC' : 'DESC';

		if (strtolower($order) == 'name') {
			$order = 'LOWER(name)';
		}

		$query .= " ORDER BY " . $order . " " . $direction;

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$return_lists = array();
		while ($row = $this->Db->Fetch($result)) {
			$return_lists[] = $row;
		}
		return $return_lists;
	}

	/**
	* MergeLists
	* Merges a bunch of mailing lists together into a new list. It will copy subscribers, custom field data etc into the new list and then find/remove any duplicate subscribers that have been imported.
	*
	* @param Array $lists_to_merge An array of list id's to merge together. Must be more than one id in the list otherwise we can't merge anything together.
	* @param Array $userinfo An array of user information to use for the new settings. This is done because the API is separate to the 'frontend', it contains the userid, fullname and email address. These are used for setting the "list owner", "list owner email" and so on.
	*
	* @see CheckIntVars
	* @see CopyCustomFields
	*
	* @return Array Returns an array of information. This contains how many lists were successfully merged, how many were not merged, how many duplicates were removed and how many duplicates were not removed.
	*/
	function MergeLists($lists_to_merge = array(), $userinfo=array())
	{
		$results = array('Success' => 0, 'Failure' => 0, 'DuplicatesSuccess' => 0, 'DuplicatesFailure' => 0);

		$lists_to_merge = $this->CheckIntVars($lists_to_merge);

		if (empty($lists_to_merge) || empty($userinfo)) {
			return array(false, 'Empty array of lists to merge', $results);
		}

		if (!isset($userinfo['userid'])) {
			return array(false, 'Empty user information', $results);
		}

		if (sizeof($lists_to_merge) == 1) {
			return array(false, 'Empty array of lists to merge', $results);
		}

		$format = 'b';
		$newname = GetLang('MergePrefix');
		foreach ($lists_to_merge as $p => $listid) {
			if (!$this->Load($listid)) {
				$results['Failure']++;
				continue;
			}

			$results['Success']++;

			$newname .= '\'' . $this->name . '\', ';
		}
		$newname = substr($newname, 0, -2);

		$this->name = $newname;
		$this->ownerid = $userinfo['userid'];
		$this->owneremail = $userinfo['emailaddress'];
		$this->ownername  = $userinfo['name'];
		$this->replytoemail = $userinfo['emailaddress'];
		$this->format = $format;
		$this->notifyowner = false;
		$this->bounceemail = SENDSTUDIO_BOUNCE_ADDRESS;
		$this->imapaccount = SENDSTUDIO_BOUNCE_IMAP;
		$this->bounceserver = SENDSTUDIO_BOUNCE_SERVER;
		$this->bounceusername = SENDSTUDIO_BOUNCE_USERNAME;
		$this->bouncepassword = base64_decode(SENDSTUDIO_BOUNCE_PASSWORD);
		$this->extramailsettings = SENDSTUDIO_BOUNCE_EXTRASETTINGS;
		$this->processbounce = (SENDSTUDIO_BOUNCE_SERVER == '' ? 0 : 1);
		$this->agreedelete = 1;
		$this->customfields = array();

		$this->Db->StartTransaction();

		$newid = $this->Create();
		$newid = (int)$newid;
		if (!$newid || $newid <= 0) {
			$this->Db->RollbackTransaction();
			return array(false, true, $results);
		}

		$customfield_status = $this->CopyCustomFields($lists_to_merge, $newid);

		// clean up any duplicate custom field associations.
		$query = "SELECT fieldid, COUNT(fieldid) AS foundcount FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE listid=" . $newid . " GROUP BY fieldid HAVING COUNT(fieldid) > 1";
		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			// delete all but one instance of the field association (hence 'foundcount - 1').
			$query = "SELECT cflid FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE listid=" . $newid . " AND fieldid=" . $row['fieldid'] . " LIMIT " . ($row['foundcount'] - 1);
			$check_result = $this->Db->Query($query);
			while ($check_row = $this->Db->Fetch($check_result)) {
				$deletelist[] = $check_row['cflid'];
			}
		}

		if (!empty($deletelist)) {
			$deletequery = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "customfield_lists WHERE cflid IN (" . implode(',', $deletelist) . ")";
			$this->Db->Query($deletequery);
		}

		$timenow = $this->GetServerTime();

		/**
		* When we merge lists together, the old way was to:
		* Add everyone from both lists then remove duplicates
		* Since we have a unique index on list_subscribers(listid, emailaddress), that won't work
		* Now, we get uniques when we select the emails to add
		*
		* PostgreSQL has a specific "DISTINCT ON" option we can use
		* MySQL lets us GROUP BY just one field so we can use that to do the same thing
		*/
		$pgsql = $mysql = '';
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$pgsql = "DISTINCT ON (emailaddress) ";
		}
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$mysql = " GROUP BY emailaddress";
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscribers(listid, emailaddress, domainname, format, confirmed, confirmcode, subscribedate, bounced, unsubscribed) SELECT " . $pgsql . $newid . ", emailaddress, domainname, format, confirmed, confirmcode, " . $timenow . ", 0, 0 FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid IN (" . implode(',', $lists_to_merge) . ")" . $mysql;
		$result = $this->Db->Query($query);

		// now we copy the custom field data.
		// since the subscribers_data table doesn't have a listid, we match the email addresses up from the old lists and new list(s).
		$pgsql = $mysql = '';
		$group = "s2.subscriberid, sd.fieldid";
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$pgsql = "DISTINCT ON ({$group})";
		} else {
			$mysql = "GROUP BY {$group}";
		}
		$customfield_copy_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "subscribers_data(subscriberid, fieldid, data) SELECT {$pgsql} s2.subscriberid, sd.fieldid, sd.data FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers s1, " . SENDSTUDIO_TABLEPREFIX . "subscribers_data sd, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers s2 WHERE s1.subscriberid=sd.subscriberid AND s1.emailaddress=s2.emailaddress AND s1.listid IN (" . implode(',', $lists_to_merge) . ") AND s2.listid=" . $newid . " {$mysql}";
		$customfield_copy_result = $this->Db->Query($customfield_copy_query);

		// now we have to check for unsubscribes on any of the merged lists and unsubscribe them from the new list.
		$query = "SELECT emailaddress, MAX(unsubscribed) AS unsubscribe_date FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid IN (" . implode(',', $lists_to_merge) . ") AND unsubscribed > 0 GROUP BY emailaddress";
		$result = $this->Db->Query($query);


		while ($row = $this->Db->Fetch($result)) {
			$unsubscribe_date = (int)$row['unsubscribe_date'];
			$new_subscriberid_query = "SELECT subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE emailaddress='" . $this->Db->Quote($row['emailaddress']) . "' AND listid=" . $newid;


			$new_subscriber_result = $this->Db->Query($new_subscriberid_query);
			$new_subscriber = (int)$this->Db->FetchOne($new_subscriber_result, 'subscriberid');

			 if (!$new_subscriber || $new_subscriber <= 0) {
				continue;
			}

			$update_query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET unsubscribed=" . $unsubscribe_date . " WHERE subscriberid=" . $new_subscriber . " AND listid=" . $newid;
				$update_result = $this->Db->Query($update_query);

			$insert_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe
			(
				subscriberid,
				unsubscribetime,
				unsubscribeip,
				unsubscriberequesttime,
				unsubscriberequestip,
				listid,
				statid,
				unsubscribearea
			)
			SELECT
				" . $new_subscriber . ",
				" . $unsubscribe_date . ",
				unsubscribeip,
				0,
				null,
				" . $newid . ",
				0,
				null
				FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe u,
				" . SENDSTUDIO_TABLEPREFIX . "list_subscribers s
				WHERE s.subscriberid=u.subscriberid AND
				s.emailaddress='" . $this->Db->Quote($row['emailaddress']) . "' AND u.listid IN (" . implode(',', $lists_to_merge) . ") AND u.unsubscribetime=" . $unsubscribe_date;

			$insert_result = $this->Db->Query($insert_query);
		}


		// now we have to check for bounces on any of emails from the merged lists and mark them as such in the new list.
		$query = "SELECT emailaddress, bounced FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid IN (" . implode(',', $lists_to_merge) . ") AND bounced <> 0 GROUP BY emailaddress, bounced";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$new_subscriberid_query = "SELECT subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE emailaddress='" . $this->Db->Quote($row['emailaddress']) . "' AND listid=" . $newid;
			$new_subscriber_result = $this->Db->Query($new_subscriberid_query);
			$new_subscriber = (int)$this->Db->FetchOne($new_subscriber_result, 'subscriberid');
			if (!$new_subscriber || $new_subscriber <= 0) {
				continue;
			}
			$update_query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET bounced = " . $row['bounced'] . " WHERE subscriberid=" . $new_subscriber . " AND listid=" . $newid;
			$update_result = $this->Db->Query($update_query);
			$insert_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces
			(
				subscriberid,
				statid,
				listid,
				bouncetime,
				bouncetype,
				bouncerule,
				bouncemessage
			)
			VALUES
			(
				" . $new_subscriber . ",
				0,
				" . $newid . ",
				" . $row['bounced'] .",
				'unknown',
				'unknown',
				'Status preserved after merging'
			)";
			$insert_result = $this->Db->Query($insert_query);
		}

		// fix up the counts of subscribed/unsubscribed and bounced contacts.
		$query = "SELECT COUNT(subscriberid) AS subscribecount FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid=" . (int)$newid . " AND unsubscribed=0 AND bounced=0";
		$sub_count = $this->Db->FetchOne($query, 'subscribecount');

		$query = "SELECT COUNT(subscriberid) AS unsubscribecount FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid=" . (int)$newid . " AND unsubscribed > 0";
		$unsub_count = $this->Db->FetchOne($query, 'unsubscribecount');

		$query = "SELECT COUNT(subscriberid) AS bouncecount FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid=" . (int)$newid . " AND bounced > 0";
		$bounce_count = $this->Db->FetchOne($query, 'bouncecount');

		// The total subscriber count should be minus the bounce and unsubscribe ( it minus during each time, refer to subscriber api)

		//$sub_count = $sub_count - ($unsub_count + $bounce_count);



		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET subscribecount=" . (int)$sub_count . ", unsubscribecount=" . (int)$unsub_count . ", bouncecount=" . (int)$bounce_count . " WHERE listid=" . (int)$newid;

		$this->Db->Query($query);

		$this->Db->CommitTransaction();


		/**
		 * Clear out list cache
		 */
		if (array_key_exists('Lists_API::GetListByUserID[listCache]', $GLOBALS)) {
			unset($GLOBALS['Lists_API::GetListByUserID[listCache]']);
		}

		return array($newid, false, $results);
	}

	/**
	* GetArchives
	* Gets archives from the database for a particular list. This is used when generating the RSS feed so it will fetch the last $num_to_retrieve sends to that particular list.
	*
	* @param Int $listid List to get archives for
	* @param Int $num_to_retrieve Number of sends to retrieve from the database
	*
	* @return Array Returns an array of entries that were last sent to that particular list.
	*/
	function GetArchives($listid=0, $num_to_retrieve=0)
	{
		$listid = (int)$listid;
		$num_to_retrieve = (int)$num_to_retrieve;

		$query = "SELECT n.newsletterid, n.name, n.subject, MIN(sn.starttime) AS starttime, nl.listid, u.username, u.fullname, n.textbody, n.htmlbody FROM " . SENDSTUDIO_TABLEPREFIX . "newsletters n, " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters sn, " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists nl, " . SENDSTUDIO_TABLEPREFIX . "users u WHERE u.userid=sn.sentby AND sn.statid=nl.statid AND sn.newsletterid=n.newsletterid";

		// we don't just check for 0/1 here because we store the userid who performed the action (eg made it active).
		$query .= " AND n.active > 0 AND n.archive > 0";

		if ($listid > 0) {
			$query .= " AND nl.listid=" . (int)$listid;
		}

		// Group the entries so we can get the start time of the first entry
		// rather then getting an entry for each time the send was started
		$query .= " GROUP BY n.newsletterid, nl.listid, n.name, n.subject, u.username, u.fullname, n.textbody, n.htmlbody";

		// order by most recently sent first.
		$query .= " ORDER BY starttime DESC";

		if ($num_to_retrieve > 0) {
			$query .= " LIMIT " . $num_to_retrieve;
		}

		$result = $this->Db->Query($query);

		$archive_list = array();
		while ($row = $this->Db->Fetch($result)) {
			$archive_list[] = $row;
		}

		return $archive_list;
	}

	/**
	 * LoadVisibleFieldSettings
	 * Load the visible field settings for list id's passed in.
	 *
	 * @param Array $listids The list ids to load the 'visiblefields' entry for. If it's not an array, it will be turned into one. Any non-numeric id's will be removed automatically.
	 *
	 * @return Array Returns an array of listid => visiblefields settings
	*/
	function LoadVisibleFieldSettings($listids=array())
	{
		if (!is_array($listids)) {
			$listids = array($listids);
		}
		$listids = $this->CheckIntVars($listids);
		if (empty($listids)) {
			return array();
		}

		$return = array();

		$query = "SELECT listid, visiblefields FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE listid IN (" . implode(',', $listids) . ")";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$return[$row['listid']] = $row['visiblefields'];
		}
		return $return;
	}

	/**
	 * GetListByUserID
	 * Get available lists for a particular user.
	 * The function will caches it's result in the $GLOBAL variable, which will be refreshed for each request.
	 * The cache should also be cleared when a list has been saved/created/deleted
	 *
	 * The cache is stored in $GLOBALS['Lists_API::GetListByUserID[listCache]']
	 *
	 * The following functions in this class will delete the cache in $GLOBALS
	 * - Create()
	 * - Copy()
	 * - Delete()
	 * - Save()
	 * - MergeList()
	 * - DeleteAllSubscribers()
	 *
	 * @see Lists_API::Create()
	 * @see Lists_API::Copy()
	 * @see Lists_API::Delete()
	 * @see Lists_API::Save()
	 * @see Lists_API::DeleteAllSubscribers()
	 * @see Lists_API::MergeLists()
	 *
	 * @param Integer $userid User ID, If user ID is not supplied, it will return all lists (OPTIONAL)
	 * @param Boolean $getUnconfirmedCount Get unconfirmed count along with the query (OPTIONAL)
	 * @param Boolean $getAutoresponderCount Get autoresponder count (OPTIONAL)
	 *
	 * @return Mixed Returns an array - list of listid's this user has created (or if the user is an admin/listadmin, returns everything), FALSE otherwise.
	 */
	function GetListByUserID($userid = 0, $getUnconfirmedCount = false, $getAutoresponderCount = true)
	{
		$userid = intval($userid);
		$user   = API_USERS::getRecordById($userid);
		$key    = '_' . $userid . '_' . ($getUnconfirmedCount? '1' : '0');

		if (!array_key_exists('Lists_API::GetListByUserID[listCache]', $GLOBALS)) {
			$GLOBALS['Lists_API::GetListByUserID[listCache]'] = array();
		}

		if (!array_key_exists($key, $GLOBALS['Lists_API::GetListByUserID[listCache]'])) {
			$tempSelects = array();
			$tempTables  = array();
			$tempWhere   = array();

			// Add in "list" table
			$tempSelects[]      = 'list.*';
			$tempTables['list'] = "[|PREFIX|]lists AS list";
			
			if ($userid != 0) {
				$tempTables['list'] .= "
					LEFT JOIN [|PREFIX|]usergroups_access AS access
						ON (
							list.listid=access.resourceid
							AND access.resourcetype = 'lists'
							AND access.groupid      = {$user->groupid}
						)
				";

				$tempWhere[] = "(list.ownerid = {$userid} OR access.groupid = {$user->groupid})";
			}

			// Add "autoresponder" table
			if ($getAutoresponderCount) {
				$tempSelects[]       = 'autoresponder.autorespondercount';
				$tempTables['list'] .= "
					LEFT JOIN (
						SELECT a.listid, COUNT(a.listid) AS autorespondercount
						FROM [|PREFIX|]autoresponders AS a
						GROUP BY a.listid
					) AS autoresponder
					ON list.listid = autoresponder.listid
				";
			}

			// If we need to get unconfirmed subscriber count, we also need to 
			// join with list_subscribers table
			if ($getUnconfirmedCount) {
				$tempSelects[]       = 'subscribers.unconfirmedsubscribercount';
				$tempTables['list'] .= "
					LEFT JOIN (
						SELECT listid, COUNT(1) AS unconfirmedsubscribercount
						FROM [|PREFIX|]list_subscribers
						WHERE
							confirmed <> '1'
							AND bounced = 0
							AND unsubscribeconfirmed <> '1'
						GROUP BY listid
					) AS subscribers
					ON list.listid = subscribers.listid
				";
			}


			$tempQuery  = 'SELECT ' . implode(', ', $tempSelects);
			$tempQuery .= ' FROM ' . implode(', ', $tempTables);

			if (!empty($tempWhere)) {
				$tempQuery .= ' WHERE ' . implode(' AND ', $tempWhere);
			}

			$tempQuery  .= ' ORDER BY LOWER(list.name) ASC';
			$tempResult  = $this->Db->Query($tempQuery);
			
			if (!$tempResult) {
				list($error, $level) = $this->Db->GetError();
				
				trigger_error($error, $level);
				
				return false;
			}

			$tempLists = array();
			
			while ($tempRow = $this->Db->Fetch($tempResult)) {
				$tempLists[$tempRow['listid']] = $tempRow;
			}
			
			$this->Db->FreeResult($tempResult);

			// Put list into cache (this will cache the list for the duration of this request)
			$GLOBALS['Lists_API::GetListByUserID[listCache]'][$key] = $tempLists;
		}

		return $GLOBALS['Lists_API::GetListByUserID[listCache]'][$key];
	}

	/**
	 * Return company information from contact list
	 * @param int $id is specific contact list id to get the detail.
	 * @return array Company Details are returned
	 */
	function getCompanyDetails($id) {
		$id = intval($id);
		$company = array(
			'listname' => '',
			'companyname' => '',
			'companyaddress' => '',
			'companyphone'
		);

		if (!empty($id)) {
			$this->Load($id);

		 	$company = array (
					'listname' => $this->name,
					'companyname' => $this->companyname,
					'companyaddress' => $this->companyaddress,
					'companyphone' => $this->companyphone
			);
		}

		return $company;
	}
}
