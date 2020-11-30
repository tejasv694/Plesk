<?php
/**
* The Autoresponder API.
*
* @version     $Id: autoresponders.php,v 1.32 2008/02/25 06:01:41 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Autoresponders_API
*/

/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This will load an autoresponder, save an autoresponder, set details and get details.
* It will also check access areas and associate the autoresponder with appropriate mailing lists.
*
* @package API
* @subpackage Autoresponders_API
*/
class Autoresponders_API extends API
{

	/**
	* The autoresponder that is loaded. By default is 0 (no autoresponder).
	*
	* @var Int
	*/
	var $autoresponderid = 0;

	/**
	* Name of the autoresponder that we've loaded.
	*
	* @var String
	*/
	var $name = '';

	/**
	* Subject of the autoresponder that we've loaded.
	*
	* @var String
	*/
	var $subject = '';

	/**
	* The text version of the autoresponder
	*
	* @var String
	*/
	var $textbody = '';

	/**
	* The html version of the autoresponder
	*
	* @var String
	*/
	var $htmlbody = '';

	/**
	* The autoresponder format
	*
	* @var String
	*/
	var $format = 'h';

	/**
	* Whether the autoresponder is active or not.
	*
	* @see Active
	*
	* @var Int
	*/
	var $active = 0;

	/**
	* The list this autoresponder is associated with.
	*
	* @var Int
	*/
	var $listid = 0;

	/**
	* How many hours after subscription this autoresponder will be sent.
	*
	* @var Int
	*/
	var $hoursaftersubscription = 0;

	/**
	* This is used to temporarily store the hours after subscription in case it gets changed when you save.
	* This variable is NOT saved.
	*
	* @see UpdateQueue
	*
	* @var Int
	*/
	var $oldhoursaftersubscription = 0;

	/**
	* Search criteria for the autoresponder.
	*
	* @var Array
	*/
	var $searchcriteria = array();

	/**
	* The 'send from name'.
	*
	* @var String
	*/
	var $sendfromname = '';

	/**
	* The 'send from email'.
	*
	* @var String
	*/
	var $sendfromemail = '';

	/**
	* The 'reply-to-email'.
	*
	* @var String
	*/
	var $replytoemail = '';

	/**
	* The 'bounce address'.
	*
	* @var String
	*/
	var $bounceemail = '';

	/**
	* The autoresponder charset
	*
	* @var String
	*/
	var $charset = '';

	/**
	* Whether to send this autoresponder multipart or not.
	*
	* @var Int
	*/
	var $multipart = 0;

	/**
	* Whether to send this autoresponder with embedded images or not.
	*
	* @var Int
	*/
	var $embedimages = 0;

	/**
	* Whether to track links sent in this autoresponder.
	*
	* @var Int
	*/
	var $tracklinks = 0;

	/**
	* Whether to track opening of this autoresponder.
	*
	* @var Int
	*/
	var $trackopens = 0;

	/**
	* The timestamp of when the autoresponder was created (integer)
	*
	* @var Int
	*/
	var $createdate = 0;

	/**
	* The queue this autoresponder relates to.
	*
	* @var Int
	*/
	var $queueid = 0;

	/**
	* Whether to include existing subscribers or not for an autoresponder. This option is NOT saved.
	*
	* @var boolean
	*/
	var $includeexisting = false;

	/**
	* The firstname custom field id. This is used to send to "Name" <email>.
	*
	* @var Int
	*/
	var $to_firstname = 0;

	/**
	* The lastname custom field id. This is used to send to "Name" <email>.
	*
	* @var Int
	*/
	var $to_lastname = 0;

	/**
	* The userid of the owner of this autoresponder.
	*
	* @var Int
	*/
	var $ownerid = 0;

	/**
	* The size of the autoresponder in bytes.
	* This is updated when you create or save an autoresponder,
	* and is used mainly to check whether you are allowed to activate an autoresponder or not.
	* This saves parsing the autoresponder & checking attachment size all the time.
	*
	* @var Int
	*/
	var $autorespondersize = 0;

	/**
	* Default Order to show autoresponders in.
	*
	* @see GetAutoresponders
	*
	* @var String
	*/
	var $DefaultOrder = 'hoursaftersubscription';

	/**
	* Default direction to show autoresponders in.
	*
	* @see GetAutoresponders
	*
	* @var String
	*/
	var $DefaultDirection = 'down';

	/**
	* An array of valid sorts that we can use here. This makes sure someone doesn't change the query to try and create an sql error.
	*
	* @see GetAutoresponders
	*
	* @var Array
	*/
	var $ValidSorts = array('name' => 'Name', 'date' => 'CreateDate', 'hours' => 'HoursAfterSubscription');

	/**
	* Constructor
	* Sets up the database object, loads the autoresponder if the ID passed in is not 0.
	*
	* @param Int $autoresponderid The autoresponderid of the autoresponder to load. If it is 0 then you get a base class only. Passing in a autoresponderid > 0 will load that autoresponder.
	*
	* @see GetDb
	* @see Load
	*
	* @return True Always returns true
	*/
	function Autoresponders_API($autoresponderid=0)
	{
		$this->GetDb();
		if ($autoresponderid > 0) {
			return $this->Load($autoresponderid);
		}
		return true;
	}

	/**
	* Load
	* Loads up the autoresponder and sets the appropriate class variables.
	*
	* @param Int $autoresponderid The autoresponderid to load up. If the autoresponderid is not present then it will not load up.
	*
	* @return Boolean Will return false if the autoresponderid is not present, or the autoresponder can't be found, otherwise it set the class vars and return true.
	*/
	function Load($autoresponderid=0)
	{
		$autoresponderid = (int)$autoresponderid;
		if ($autoresponderid <= 0) {
			return false;
		}

		$query = 'SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'autoresponders WHERE autoresponderid=\'' . $autoresponderid . '\'';
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$autoresponder = $this->Db->Fetch($result);
		if (empty($autoresponder)) {
			return false;
		}

		$this->autoresponderid = $autoresponder['autoresponderid'];
		$this->name = $autoresponder['name'];
		$this->listid = (int)$autoresponder['listid'];
		$this->createdate = $autoresponder['createdate'];
		$this->format = $autoresponder['format'];
		$this->textbody = $autoresponder['textbody'];
		$this->htmlbody = $autoresponder['htmlbody'];
		$this->hoursaftersubscription = (int)$autoresponder['hoursaftersubscription'];

		$this->to_firstname = (int)$autoresponder['to_firstname'];
		$this->to_lastname = (int)$autoresponder['to_lastname'];

		$this->oldhoursaftersubscription = (int)$autoresponder['hoursaftersubscription'];
		$this->subject = $autoresponder['subject'];
		$this->searchcriteria = unserialize($autoresponder['searchcriteria']);

		$this->sendfromname = $autoresponder['sendfromname'];
		$this->sendfromemail = $autoresponder['sendfromemail'];
		$this->bounceemail = $autoresponder['bounceemail'];
		$this->replytoemail = $autoresponder['replytoemail'];
		$this->charset = $autoresponder['charset'];
		$this->multipart = ($autoresponder['multipart']) ? true : false;
		$this->embedimages = ($autoresponder['embedimages']) ? true : false;
		$this->trackopens = ($autoresponder['trackopens']) ? true : false;
		$this->tracklinks = ($autoresponder['tracklinks']) ? true : false;
		$this->queueid = (int)$autoresponder['queueid'];

		$this->ownerid = (int)$autoresponder['ownerid'];

		$this->active = (int)$autoresponder['active'];

		$this->autorespondersize = (int)$autoresponder['autorespondersize'];

		return true;
	}

	/**
	* Create
	* This function creates an autoresponder based on the current class vars.
	* A new queue is created for the new autoresponder. If includeexisting is set, then the whole list is imported into the new autoresponder queue.
	*
	* @see includeexisting
	* @see CreateQueue
	* @see ImportToQueue
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Create()
	{

		$queueid = $this->CreateQueue('autoresponder');

		/**
		 * Make sure that spaces in links get url encoded, otherwise some email client will NOT be able to link to it
		 */
			if (!empty($this->htmlbody) && preg_match_all('/<a([^>]+)href\s*=\s*(\'|")(.*?)\2/is', $this->htmlbody, $matches)) {
				foreach ($matches[0] as $index => $match) {
					$link = str_replace(' ', '%20', $matches[3][$index]);
					$this->htmlbody = str_replace($match, ('<a' . $matches[1][$index] . 'href=' . $matches[2][$index] . $link . $matches[2][$index]), $this->htmlbody);
				}
			}
		/**
		 * -----
		 */

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "autoresponders(name, format, subject, textbody, htmlbody, hoursaftersubscription, listid, searchcriteria, sendfromname, sendfromemail, replytoemail, bounceemail, charset, tracklinks, trackopens, multipart, embedimages, to_firstname, to_lastname, queueid, createdate, active, autorespondersize, ownerid) VALUES('" . $this->Db->Quote($this->name) . "', '" . $this->Db->Quote($this->format) . "', '" . $this->Db->Quote($this->subject) . "', '" . $this->Db->Quote($this->textbody) . "', '" . $this->Db->Quote($this->htmlbody) . "', '" . (int)$this->hoursaftersubscription . "', '" . (int)$this->listid . "', '" . $this->Db->Quote(serialize($this->searchcriteria)) . "', '" . $this->Db->Quote($this->sendfromname) . "', '" . $this->Db->Quote($this->sendfromemail) . "', '" . $this->Db->Quote($this->replytoemail) . "', '" . $this->Db->Quote($this->bounceemail) . "', '" . $this->Db->Quote($this->charset) . "', '" . (int)$this->tracklinks . "', '" . (int)$this->trackopens . "', '" . (int)$this->multipart . "', '" . (int)$this->embedimages . "', '" . (int)$this->to_firstname . "', '" . (int)$this->to_lastname . "', '" . (int)$queueid . "', '" . $this->GetServerTime() . "', 1, '" . (int)$this->autorespondersize . "', '" . $this->Db->Quote($this->ownerid) . "')";

		$result = $this->Db->Query($query);

		if ($result) {

			$autoresponderid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'autoresponders_sequence');

			$this->autoresponderid = $autoresponderid;

			if ($this->includeexisting) {
				$this->ClearQueue($queueid, 'autoresponder');
				$this->ImportToQueue($queueid, 'autoresponder', $this->listid);
			}

			return $autoresponderid;
		}
		return false;
	}

	/**
	* Delete
	* Delete an autoresponder from the database. Also deletes the queue associated with it.
	*
	* @param Int $autoresponderid Autoresponderid of the autoresponder to delete. If not passed in, it will delete 'this' autoresponder. We delete the autoresponder, then reset all class vars.
	* @param Int $userid The user doing the deleting of the autoresponder. This is so the stats api can "hide" autoresponder stats.
	*
	* @see Stats_API::HideStats
	* @see ClearQueue
	*
	* @return Boolean True if it deleted the autoresponder, false otherwise.
	*
	*/
	function Delete($autoresponderid=0, $userid=0)
	{
		if ($autoresponderid == 0) {
			$autoresponderid = $this->autoresponderid;
		}

		$this->Load($autoresponderid);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders WHERE autoresponderid='" . $autoresponderid. "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$this->ClearQueue($this->queueid, 'autoresponder');

		$autoresponder_dir = TEMP_DIRECTORY . '/autoresponders/' . $autoresponderid;
		remove_directory($autoresponder_dir);

		$this->autoresponderid = 0;
		$this->name = '';
		$this->format = 'h';
		$this->hoursaftersubscription = 0;
		$this->queueid = 0;
		$this->archive = 0;
		$this->SetBody('text', '');
		$this->SetBody('html', '');
		$this->ownerid = 0;
		$this->autorespondersize = 0;

		$stats = array();
		$query = "SELECT statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders WHERE autoresponderid='" . $autoresponderid . "'";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$stats[] = $row['statid'];
		}

		// clean up stats
		if (!class_exists('stats_api', false)) {
			require_once(dirname(__FILE__) . '/stats.php');
		}

		$stats_api = new Stats_API();

		$stats_api->HideStats($stats, 'autoresponder', $userid);

		return true;
	}

	/**
	* Copy
	* Copy an autoresponder along with attachments, images etc.
	*
	* @param Int $oldid Autoresponderid of the autoresponder to copy.
	*
	* @see Load
	* @see Create
	* @see CopyDirectory
	* @see Save
	*
	* @return Boolean True if it copied the autoresponder, false otherwise.
	*/
	function Copy($oldid=0)
	{
		if ($oldid <= 0) {
			return array(false, false);
		}

		if (!$this->Load($oldid)) {
			return array(false, false);
		}

		$this->name = GetLang('CopyPrefix') . $this->name;

		$newid = $this->Create();
		if (!$newid) {
			return array(false, false);
		}

		$this->Load($newid);

		$this->createdate = $this->GetServerTime();

		$olddir = TEMP_DIRECTORY . '/autoresponders/' . $oldid;
		$newdir = TEMP_DIRECTORY . '/autoresponders/' . $newid;

		$status = CopyDirectory($olddir, $newdir);

		$this->textbody = str_replace('autoresponders/' . $oldid, 'autoresponders/' . $newid, $this->textbody);
		$this->htmlbody = str_replace('autoresponders/' . $oldid, 'autoresponders/' . $newid, $this->htmlbody);

		$this->Save();

		return array(true, $status);
	}

	/**
	* Active
	* Returns whether the autoresponder is active or not. This allows you to temporarily disable particular autoresponders.
	*
	* @return Boolean Returns true if the autoresponder is active, otherwise returns false.
	*/
	function Active()
	{
		if ($this->active < 1) {
			return false;
		}

		return true;
	}

	/**
	* Archive
	* Returns whether the newsletter is archiveable or not. An inactive newsletter cannot be archived.
	*
	* @return Boolean Returns true if the newsletter is ok to archive, otherwise returns false.
	*/
	function Archive()
	{
		return $this->Active();
	}

	/**
	* Save
	* This function saves the current class vars to the autoresponder.
	* If there is no autoresponder currently loaded, this will return false.
	*
	* @see UpdateQueue
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Save()
	{
		if ($this->autoresponderid <= 0) {
			return false;
		}

		if ($this->includeexisting) {
			$this->ClearQueue($this->queueid, 'autoresponder');
			$this->ImportToQueue($this->queueid, 'autoresponder', $this->listid);
		}

		/**
		 * Make sure that spaces in links get url encoded, otherwise some email client will NOT be able to link to it
		 */
			if (!empty($this->htmlbody) && preg_match_all('/<a([^>]+)href\s*=\s*(\'|")(.*?)\2/is', $this->htmlbody, $matches)) {
				foreach ($matches[0] as $index => $match) {
					$link = str_replace(' ', '%20', $matches[3][$index]);
					$this->htmlbody = str_replace($match, ('<a' . $matches[1][$index] . 'href=' . $matches[2][$index] . $link . $matches[2][$index]), $this->htmlbody);
				}
			}
		/**
		 * -----
		 */

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "autoresponders SET name='" . $this->Db->Quote($this->name) . "', format='" . $this->Db->Quote($this->format) . "', subject='" . $this->Db->Quote($this->subject) . "', textbody='" . $this->Db->Quote($this->textbody) . "', htmlbody='" . $this->Db->Quote($this->htmlbody) . "', hoursaftersubscription='" . (int)$this->hoursaftersubscription . "', listid='" . (int)$this->listid . "', searchcriteria='" . $this->Db->Quote(serialize($this->searchcriteria)) . "', sendfromname='" . $this->Db->Quote($this->sendfromname) . "', sendfromemail='" . $this->Db->Quote($this->sendfromemail) . "', replytoemail='" . $this->Db->Quote($this->replytoemail) . "', bounceemail='" . $this->Db->Quote($this->bounceemail) . "', charset='" . $this->Db->Quote($this->charset) . "', tracklinks='" . (int)$this->tracklinks . "', trackopens='" . (int)$this->trackopens . "', multipart='" . (int)$this->multipart . "', embedimages='" . (int)$this->embedimages . "', active='" . (int)$this->active . "', to_firstname='" . (int)$this->to_firstname . "', to_lastname='" . (int)$this->to_lastname . "', autorespondersize='" . (int)$this->autorespondersize . "' WHERE autoresponderid='" . (int)$this->autoresponderid . "'";

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$this->UpdateQueue();
		return true;
	}

	/**
	* UpdateQueue
	* Updates the autoresponder queue when the hours after subscription has been changed.
	* If they are the same time, then it does nothing.
	* If the new time is after the old time, it does nothing.
	* If the new time is BEFORE the old time, then we need to check subscribe times to make sure we don't send it incorrectly.
	*
	* @see oldhoursaftersubscription
	* @see hoursaftersubscription
	*
	* @return Boolean Returns false if the autoresponder isn't loaded. Returns true if it doesn't have to do anything, or true if it succeeds in changing the queue.
	*/
	function UpdateQueue()
	{
		if ($this->autoresponderid <= 0) {
			return false;
		}

		if ($this->queueid <= 0) {
			return false;
		}

		if ($this->hoursaftersubscription >= $this->oldhoursaftersubscription) {
			return true;
		}

		$time = AdjustTime();
		$newtime = $time - ($this->hoursaftersubscription * 3600);

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE recipient IN (SELECT l.subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE l.listid='" . (int)$this->listid . "' AND subscribedate < " . $newtime . ") AND queueid='" . (int)$this->queueid . "'";
			return $this->Db->Query($query);
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "SELECT subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE listid='" . (int)$this->listid . "' AND subscribedate < " . $newtime;
			$subscribers = array('0');
			$result = $this->Db->Query($query);
			while ($row = $this->Db->Fetch($result)) {
				$subscribers[] = $row['subscriberid'];
			}

			return $this->RemoveFromQueue($this->queueid, 'autoresponder', $subscribers);
		}
	}

	/**
	* GetAutoresponders
	* Get a list of autoresponders based on the criteria passed in.
	*
	* @param Array $lists Lists to get autoresponders for. If you pass in a single id, it will be converted to an array for easy retrieval.
	* @param Array $sortinfo An array of sorting information - what to sort by and what direction.
	* @param Boolean $countonly Whether to only get a count of the autoresponders, rather than the information.
	* @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	* @param Int|String $perpage How many results to return (max).
	*
	* @see ValidSorts
	* @see DefaultOrder
	* @see DefaultDirection
	*
	* @return Mixed Returns false if it couldn't retrieve autoresponder information. Otherwise returns the count (if specified), or an array of autoresponders.
	*/
	function GetAutoresponders($lists=array(), $sortinfo=array(), $countonly=false, $start=0, $perpage=10)
	{
		if (!is_array($lists)) {
			$lists = array($lists);
		}

		$lists = $this->CheckIntVars($lists);
		if (empty($lists)) {
			$lists[] = '0';
		}

		$start = (int)$start;

		if ($countonly) {
			$query = "SELECT COUNT(autoresponderid) AS count FROM [|PREFIX|]autoresponders WHERE listid IN (" . implode(',', $lists) . ")";
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT a.*, u.username AS username, u.fullname AS fullname, ";
		$query .= "CASE WHEN u.fullname = '' THEN u.username ELSE u.fullname END AS owner ";
		$query .= "FROM [|PREFIX|]autoresponders a LEFT JOIN [|PREFIX|]users u ON a.ownerid = u.userid ";
		$query .= "WHERE listid IN (" . implode(',', $lists) . ")";

		$order = (isset($sortinfo['SortBy']) && !is_null($sortinfo['SortBy'])) ? strtolower($sortinfo['SortBy']) : $this->DefaultOrder;

		$order = (in_array($order, array_keys($this->ValidSorts))) ? $this->ValidSorts[$order] : $this->DefaultOrder;

		$direction = (isset($sortinfo['Direction']) && !is_null($sortinfo['Direction'])) ? $sortinfo['Direction'] : $this->DefaultDirection;

		$direction = (strtolower($direction) == 'up' || strtolower($direction) == 'asc') ? 'ASC' : 'DESC';
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
		$autoresponders = array();
		while ($row = $this->Db->Fetch($result)) {
			$row['name'] = $row['name'];
			$row['subject'] = $row['subject'];
			$autoresponders[] = $row;
		}
		return $autoresponders;
	}

	/**
	 * GetRecordByStatID
	 * Get autoresponder by statistic ID
	 *
	 * @param Integer $statid Statistic ID
	 * @return Array|FALSE Returns autoresponder record if successful, FALSE otherwise
	 */
	function GetRecordByStatID($statid)
	{
		$statid = intval($statid);

		$query = "
			SELECT	a.*
			FROM	[|PREFIX|]autoresponders AS a
						JOIN [|PREFIX|]stats_autoresponders AS sa
							ON sa.autoresponderid = a.autoresponderid
			WHERE	a.statid = {$statid}
		";

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$row = $this->Db->Fetch($result);
		$this->Db->FreeResult($row);

		if (empty($row)) {
			return array();
		}

		return $row;
	}

	/**
	* DisableAutoresponders
	* This disables an array of autoresponderid's.
	* This is used by the settings page in case any autoresponders have attachments on them.
	*
	* @param Array $autoresponderids Autoresponderid's to disable.
	*
	* @see SENDSTUDIO_ALLOW_ATTACHMENTS
	*
	* @return Mixed Returns false if there are any invalid autoresponderid's passed in. Otherwise, returns an array of autoresponderid's that have been disabled that were active.
	*/
	function DisableAutoresponders($autoresponderids=array())
	{
		if (!is_array($autoresponderids)) {
			$autoresponderids = array($autoresponderids);
		}
		$autoresponderids = $this->CheckIntVars($autoresponderids);
		if (empty($autoresponderids)) {
			return false;
		}

		$disable_autoresponders = array();

		$return_list = array();
		$query = "SELECT autoresponderid, a.name AS autorespondername, subject, l.listid AS listid, l.name AS listname, l.ownername, l.owneremail FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders a, " . SENDSTUDIO_TABLEPREFIX . "lists l WHERE a.listid=l.listid AND autoresponderid IN (" . implode(',', $autoresponderids) . ") AND active > 0";

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$return_list[] = $row;
			$disable_autoresponders[] = $row['autoresponderid'];
		}

		if (!empty($disable_autoresponders)) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "autoresponders SET active=0 WHERE autoresponderid IN (" . implode(',', $disable_autoresponders) . ")";
			$result = $this->Db->Query($query);
		}

		return $return_list;
	}

	/**
	 * Pauses autoresponder
	 * @param integer $autoresponderid Autoresponder to be paused
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	function PauseAutoresponder($autoresponderid)
	{
		$user = IEM::getCurrentUser();
		$autoresponderid = intval($autoresponderid);
		$userid = $user->userid;

		if ($userid == 0) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- You need to login first before you can pause autoresponder', E_USER_NOTICE);
			return false;
		}

		$status = $this->Db->Query("UPDATE [|PREFIX|]autoresponders SET pause = {$userid} WHERE autoresponderid = {$autoresponderid}");
		if (!$status) {
			return false;
		}

		return true;
	}

	/**
	 * Resume autoresponder
	 * @param integer $autoresponderid Autoresponder to be paused
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	function ResumeAutoresponder($autoresponderid)
	{
		$autoresponderid = intval($autoresponderid);

		$status = $this->Db->Query("UPDATE [|PREFIX|]autoresponders SET pause = 0 WHERE autoresponderid = {$autoresponderid}");
		if (!$status) {
			return false;
		}

		return true;
	}

	/**
	* GetRecipientCount
	* Gets the number of recipients this autoresponder has already been sent to.
	* This is used when editing an autoresponder and someone ticks the "send to existing" checkbox
	* It will show an alert message to tell you how many recipients have already received the autoresponder
	*
	* @param Int $autoresponderid The autoresponderid you are counting statistics for
	* @param Int $userid Current user ID
	*
	* @return Int Returns the number of recipients that have already been emailed
	*/
	function GetRecipientCount($autoresponderid, $userid = 0)
	{
		$autoresponderid = intval($autoresponderid);
		$userid = intval($userid);
		$tablePrefix = SENDSTUDIO_TABLEPREFIX;
		$limitByOwner = '';

		if ($autoresponderid == 0) {
			return false;
		}

		if ($userid != 0) {
			$limitByOwner = "
				JOIN {$tablePrefix}autoresponders AS a
					ON sa.autoresponderid = a.autoresponderid
			";
		}

		$query = "
			SELECT SUM(sa.htmlrecipients + sa.textrecipients + sa.multipartrecipients) AS count
			FROM {$tablePrefix}stats_autoresponders AS sa
			{$limitByOwner}
			WHERE sa.autoresponderid = {$autoresponderid}
		";

		$result = $this->Db->FetchOne($query);
		return $result;
	}
}
