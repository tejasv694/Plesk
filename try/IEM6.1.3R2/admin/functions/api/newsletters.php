<?php
/**
* The Newsletter API.
*
* @version     $Id: newsletters.php,v 1.39 2008/01/18 03:41:18 hendri Exp $
* @author Chris <chris@interspire.com>
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
*
* @package API
* @subpackage Newsletters_API
*/

/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This will load a newsletter, save a newsletter, set details and get details.
* It will also check access areas.
*
* @package API
* @subpackage Newsletters_API
*/
class Newsletters_API extends API
{

	/**
	* The newsletter that is loaded. By default is 0 (no newsletter).
	*
	* @var Int
	*/
	var $newsletterid = 0;

	/**
	* Name of the newsletter that we've loaded.
	*
	* @var String
	*/
	var $name = '';

	/**
	* Subject of the newsletter that we've loaded.
	*
	* @var String
	*/
	var $subject = '';

	/**
	* The text version of the newsletter
	*
	* @var String
	*/
	var $textbody = '';

	/**
	* The html version of the newsletter
	*
	* @var String
	*/
	var $htmlbody = '';

	/**
	* The newsletters' format
	*
	* @var String
	*/
	var $format = 'h';

	/**
	* Whether the newsletter is active or not.
	*
	* @see Active
	*
	* @var Int
	*/
	var $active = 0;

	/**
	* Whether to show this newsletter in the archive or not.
	*
	* @see Archive
	*
	* @var Int
	*/
	var $archive = 0;

	/**
	* The userid of the owner of this newsletter.
	*
	* @var Int
	*/
	var $ownerid = 0;

	/**
	* The timestamp of when the newsletter was created (integer)
	*
	* @var Int
	*/
	var $createdate = 0;

	/**
	* Default Order to show newsletters in.
	*
	* @see GetNewsletters
	*
	* @var String
	*/
	var $DefaultOrder = 'createdate';

	/**
	* Default direction to show newsletters in.
	*
	* @see GetNewsletters
	*
	* @var String
	*/
	var $DefaultDirection = 'down';

	/**
	* An array of valid sorts that we can use here. This makes sure someone doesn't change the query to try and create an sql error.
	*
	* @see GetNewsletters
	*
	* @var Array
	*/
	var $ValidSorts = array('name' => 'Name', 'date' => 'CreateDate', 'subject' => 'Subject', 'owner' => 'Owner');

	/**
	* Constructor
	* Sets up the database object, loads the newsletter if the ID passed in is not 0.
	*
	* @param Int $newsletterid The newsletterid of the newsletter to load. If it is 0 then you get a base class only. Passing in a newsletterid > 0 will load that newsletter.
	*
	* @see GetDb
	* @see Load
	*
	* @return Boolean If no newsletterid is passed in, this will return true. Otherwise, it will call Load and return that status.
	*/
	function Newsletters_API($newsletterid=0)
	{
		$this->GetDb();
		if ($newsletterid > 0) {
			return $this->Load($newsletterid);
		}
		return true;
	}

	/**
	* Load
	* Loads up the newsletter and sets the appropriate class variables.
	*
	* @param Int $newsletterid The newsletterid to load up. If the newsletterid is not present then it will not load up.
	*
	* @return Boolean Will return false if the newsletterid is not present, or the newsletter can't be found, otherwise it set the class vars and return true.
	*/
	function Load($newsletterid=0)
	{
		$newsletterid = (int)$newsletterid;
		if ($newsletterid <= 0) {
			return false;
		}

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "newsletters WHERE newsletterid='" . $newsletterid . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$newsletter = $this->Db->Fetch($result);
		if (empty($newsletter)) {
			return false;
		}

		$this->newsletterid = $newsletter['newsletterid'];
		$this->name = $newsletter['name'];
		$this->createdate = $newsletter['createdate'];
		$this->format = $newsletter['format'];
		$this->textbody = $newsletter['textbody'];
		$this->htmlbody = $newsletter['htmlbody'];
		$this->active = $newsletter['active'];
		$this->archive = $newsletter['archive'];
		$this->subject = $newsletter['subject'];
		$this->ownerid = $newsletter['ownerid'];
		return true;
	}

	/**
	* Create
	* This function creates a newsletter based on the current class vars.
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Create()
	{

		$createdate = $this->GetServerTime();

		if ((int)$this->createdate > 0) {
			$createdate = (int)$this->createdate;
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

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "newsletters(name, format, active, archive, subject, textbody, htmlbody, createdate, ownerid) VALUES('" . $this->Db->Quote(str_replace(",", " ", $this->name)) . "', '" . $this->Db->Quote($this->format) . "', '" . (int)$this->active . "', '" . (int)$this->archive . "', '" . $this->Db->Quote($this->subject) . "', '" . $this->Db->Quote($this->textbody) . "', '" . $this->Db->Quote($this->htmlbody) . "', '" . $createdate . "', '" . $this->Db->Quote($this->ownerid) . "')";

		$result = $this->Db->Query($query);
		if ($result) {
			$newsletterid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'newsletters_sequence');
			$this->newsletterid = $newsletterid;
			return $newsletterid;
		}
		return false;
	}

	/**
	* Delete
	* Delete a newsletter from the database
	*
	* @param Int $newsletterid Newsletterid of the newsletter to delete. If not passed in, it will delete 'this' newsletter. We delete the newsletter, then reset all class vars.
	* @param Int $userid The user doing the deleting of the newsletter. This is passed through to the stats api to "hide" statistics rather than deleting them.
	*
	* @see Stats_API::HideStats
	*
	* @return Boolean True if it deleted the newsletter, false otherwise.
	*
	* @uses module_TrackerFactory
	* @uses module_Tracker
	* @uses module_Tracker::DeleteRecordsForAllTrackerByID()
	*/
	function Delete($newsletterid=0, $userid=0)
	{
		if ($newsletterid == 0) {
			$newsletterid = $this->newsletterid;
		}

		$newsletterid = intval($newsletterid);

		/**
		 * Status being 'true' means
		 * it's ok to delete the newsletter.
		 * If it's not true, then the delete method returns false.
		 */
		$trigger_details = array (
			'status' => true,
			'newsletterid' => $newsletterid
		);

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_NEWSLETTERSAPI_DELETE();
			$tempEventData->status = &$trigger_details['status'];
			$tempEventData->newsletterid = &$trigger_details['newsletterid'];
			$tempEventData->trigger();

			unset($tempEventData);
		/**
		 * -----
		 */

		if ($trigger_details['status'] !== true) {
			return false;
		}

		$this->Db->StartTransaction();

		/**
		 * Cleanup trackers
		 *
		 * When newsletter are deleted, delete associated tracker record too
		 *
		 * This was added here, because it's not calling the stats API to clear it's statistic
		 */
			$tempContinue = false;
			if (!class_exists('module_TrackerFactory', false)) {
				$tempFile = dirname(__FILE__) . '/module_trackerfactory.php';
				if (is_file($tempFile)) {
					require_once($tempFile);
					$tempContinue = true;
				}
			} else {
				$tempContinue = true;
			}

			if ($tempContinue) {
				module_Tracker::DeleteRecordForAllTrackerByNewsletterID($newsletterid);
			}
		/**
		 * -----
		 */

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "newsletters WHERE newsletterid=" . $newsletterid;
		$result = $this->Db->Query($query);
		if (!$result) {
			$this->Db->RollbackTransaction();
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$newsletter_dir = TEMP_DIRECTORY . '/newsletters/' . $newsletterid;
		remove_directory($newsletter_dir);

		$this->newsletterid = 0;
		$this->name = '';
		$this->format = 'h';
		$this->active = 0;
		$this->archive = 0;
		$this->SetBody('text', '');
		$this->SetBody('html', '');
		$this->ownerid = 0;

		$stats = array();
		$query = "SELECT statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters WHERE newsletterid=" . $newsletterid;
		$result = $this->Db->Query($query);
		if (!$result) {
			$this->Db->RollbackTransaction();
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		while ($row = $this->Db->Fetch($result)) {
			$stats[] = $row['statid'];
		}

		// clean up stats
		if (!class_exists('stats_api', false)) {
			require_once(dirname(__FILE__) . '/stats.php');
		}

		$stats_api = new Stats_API();

		$stats_api->HideStats($stats, 'newsletter', $userid);

		$this->Db->CommitTransaction();

		return true;
	}

	/**
	* Copy
	* Copy a newsletter along with attachments, images etc.
	*
	* @param Int $oldid Newsletterid of the newsletter to copy.
	*
	* @see Load
	* @see Create
	* @see CopyDirectory
	* @see Save
	*
	* @return Array Returns an array of statuses. The first one is whether the newsletter could be found/loaded/copied, the second is whether the images/attachments could be copied. Both are true for success, false for failure.
	*/
	function Copy($oldid=0)
	{
		$oldid = intval($oldid);

		if ($oldid <= 0) {
			return array(false, false);
		}

		if (!$this->Load($oldid)) {
			return array(false, false);
		}

		$this->name = GetLang('CopyPrefix') . $this->name;

		$this->createdate = $this->GetServerTime();

		$newid = $this->Create();
		if (!$newid) {
			return array(false, false);
		}

		$this->Load($newid);

		$olddir = TEMP_DIRECTORY . '/newsletters/' . $oldid;
		$newdir = TEMP_DIRECTORY . '/newsletters/' . $newid;

		$status = CopyDirectory($olddir, $newdir);

		$this->textbody = str_replace('newsletters/' . $oldid, 'newsletters/' . $newid, $this->textbody);
		$this->htmlbody = str_replace('newsletters/' . $oldid, 'newsletters/' . $newid, $this->htmlbody);

		$this->Save();

		return array(true, $status);
	}

	/**
	* Active
	* Returns whether the newsletter is active or not. We remember who made it active (their userid) so we can't just check on/off status. 0 means it's inactive, anything else means user 'x' made it active.
	*
	* @return Boolean Returns true if the newsletter is active, otherwise returns false.
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
		return $this->archive;
	}

	/**
	* Save
	* This function saves the current class vars to the newsletter. If there is no newsletter currently loaded, this will return false.
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Save()
	{
		if ($this->newsletterid <= 0) {
			return false;
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

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "newsletters SET name='" . $this->Db->Quote(str_replace(",", " ", $this->name)) . "', subject='" . $this->Db->Quote($this->subject) . "', textbody='" . $this->Db->Quote($this->textbody) . "', htmlbody='" . $this->Db->Quote($this->htmlbody) . "', format='" . $this->Db->Quote($this->format) . "', active='" . (int)$this->active . "', archive='" . (int)$this->archive . "' WHERE newsletterid='" . $this->Db->Quote($this->newsletterid) . "'";

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		return true;
	}

	/**
	* GetLiveNewsletters
	* This function only retrieves live newsletters from the database. It will find active newsletters. If you pass in an owner, it will also find their newsletters that are active.
	* If you pass an array of newsletterid's, then it only retrieves live newsletters who are in the id's supplied.
	*
	* @param Int $ownerid The ownerid to fetch newsletters for. If not supplied, then it gets all active newsletters (OPTIONAL)
	* @param Array $newsletterids If supplied, restrict only fetching the id's passed in. This can be useful to bulk load basic information about a bunch of newsletters at once (OPTIONAL)
	*
	* @see active
	*
	* @return Array Returns an array of newsletters that are live.
	*/
	function GetLiveNewsletters($ownerid=0, $newsletterids=array())
	{
		$query = "SELECT newsletterid, name, subject FROM " . SENDSTUDIO_TABLEPREFIX . "newsletters WHERE active > 0";
		if ($ownerid) {
			$query .= " AND ownerid='" . $this->Db->Quote($ownerid) . "'";
		}

		/**
		 * If any newsletter id's are passed through:
		 * - make sure they are integers only
		 * - if they are, then restrict the search even further.
		 */
		if (!empty($newsletterids)) {
			$newsletterids = $this->CheckIntVars($newsletterids);
			if (!empty($newsletterids)) {
				$query .= " AND newsletterid IN (" . implode(',', $newsletterids) . ")";
			}
		}

		$query .= " ORDER BY name ASC";
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$newsletters = array();
		while ($row = $this->Db->Fetch($result)) {
			$newsletters[] = $row;
		}
		return $newsletters;
	}

	/**
	* GetLastSent
	* Get the date, number of recipients it has already been sent to and the number of recipients it will go to of when the last newsletter was sent.
	*
	* @param Int $newsletterid The newsletterid we are getting the last sent data for.
	*
	* @return Array Return the starttime, total recipients (already sent to) and sendsize (total to send to) and return it as an array. If it hasn't been sent before, this will be an empty array.
	*/
	function GetLastSent($newsletterid=0)
	{
		$return = array('jobid' => 0, 'starttime' => 0, 'total_recipients' => 0, 'sendsize' => 0);

		$newsletterid = (int)$newsletterid;
		$query = "
			SELECT		jobid,
						starttime,
						finishtime,
						htmlrecipients + textrecipients + multipartrecipients AS total_recipients,
						sendsize
			FROM		[|PREFIX|]stats_newsletters
			WHERE		newsletterid={$newsletterid}
						AND sendtype='newsletter'
			ORDER BY	starttime DESC LIMIT 1
		";
		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			return $return;
		}
		$return['jobid'] = $row['jobid'];
		$return['starttime'] = $row['starttime'];
		$return['finishtime'] = $row['finishtime'];
		$return['total_recipients'] = $row['total_recipients'];
		$return['sendsize'] = $row['sendsize'];
		return $return;
	}

	/**
	* GetNewsletters
	* Get a list of newsletters based on the criteria passed in.
	*
	* @param Int $ownerid Ownerid of the newsletters to check for.
	* @param Array $sortinfo An array of sorting information - what to sort by and what direction.
	* @param Boolean $countonly Whether to only get a count of lists, rather than the information.
	* @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	* @param Int|String $perpage How many results to return (max).
	*
	* @see ValidSorts
	* @see DefaultOrder
	* @see DefaultDirection
	*
	* @return Mixed Returns false if it couldn't retrieve newsletter information. Otherwise returns the count (if specified), or an array of newsletters.
	*/
	function GetNewsletters($ownerid=0, $sortinfo=array(), $countonly=false, $start=0, $perpage=10, $getLastSentDetails = false)
	{
		$ownerid = (int)$ownerid;
		$start = (int)$start;

		if ($countonly) {
			$query = "SELECT COUNT(newsletterid) AS count FROM [|PREFIX|]newsletters";
			if ($ownerid) {
				$query .= " WHERE ownerid=" . intval($ownerid);
			}

			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "
			SELECT		n.*,
						u.username AS username,
						u.fullname AS fullname,
						CASE WHEN u.fullname = '' THEN u.username ELSE u.fullname END AS owner
			FROM		[|PREFIX|]newsletters n
							LEFT JOIN [|PREFIX|]users u
								ON n.ownerid = u.userid
		";

		if ($ownerid) {
			$query .= " WHERE ownerid=" . intval($ownerid);
		}

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
		$newsletters = array();
		$newsletterid = array();
		while ($row = $this->Db->Fetch($result)) {
			$row['name'] = $row['name'];
			$row['subject'] = $row['subject'];
			$row['queueid'] = 0;
			$row['jobid'] = 0;
			$row['starttime'] = 0;
			$row['finishtime'] = 0;
			$row['total_recipients'] = 0;
			$row['sendsize'] = 0;
			$newsletters[$row['newsletterid']] = $row;
			$newsletterid[] = $row['newsletterid'];
		}
		$this->Db->FreeResult($result);

		if (!empty($newsletterid) && $getLastSentDetails) {
			$newsletterid_string = implode(',', $newsletterid);
			$query = "
				SELECT	s.newsletterid AS newsletterid,
						s.queueid AS queueid,
						s.jobid AS jobid,
						s.starttime AS starttime,
						s.finishtime AS finishtime,
						s.htmlrecipients + s.textrecipients + s.multipartrecipients AS total_recipients,
						s.sendsize AS sendsize

				FROM	[|PREFIX|]stats_newsletters AS s

				WHERE	s.newsletterid IN ({$newsletterid_string})
						AND s.sendtype = 'newsletter'
			";
			$result = $this->Db->Query($query);

			while ($row = $this->Db->Fetch($result)) {
				$newsletters[$row['newsletterid']]['queueid'] = $row['queueid'];
				$newsletters[$row['newsletterid']]['jobid'] = $row['jobid'];
				$newsletters[$row['newsletterid']]['starttime'] = $row['starttime'];
				$newsletters[$row['newsletterid']]['finishtime'] = $row['finishtime'];
				$newsletters[$row['newsletterid']]['total_recipients'] = $row['total_recipients'];
				$newsletters[$row['newsletterid']]['sendsize'] = $row['sendsize'];
			}

			$this->Db->FreeResult($result);
		}

		return $newsletters;
	}

	/**
	* DisableNewsletters
	* This disables an array of newsletterid's.
	* This is used by the settings page in case any newsletters have attachments on them.
	*
	* @param Array $newsletterids Newsletter id's to disable.
	*
	* @see SENDSTUDIO_ALLOW_ATTACHMENTS
	*
	* @return Mixed Returns false if there are any invalid newsletter id's passed in. Otherwise, returns an array of newsletterid's that have been disabled that were active.
	*/
	function DisableNewsletters($newsletterids=array())
	{
		if (!is_array($newsletterids)) {
			$newsletterids = array($newsletterids);
		}
		$newsletterids = $this->CheckIntVars($newsletterids);
		if (empty($newsletterids)) {
			return false;
		}

		$disable_newsletters = array();

		$return_list = array();
		$query = "SELECT newsletterid, name AS newslettername FROM " . SENDSTUDIO_TABLEPREFIX . "newsletters WHERE newsletterid IN (" . implode(',', $newsletterids) . ") AND active > 0";

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$return_list[] = $row;
			$disable_newsletters[] = $row['newsletterid'];
		}

		if (!empty($disable_newsletters)) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "newsletters SET active=0 WHERE newsletterid IN (" . implode(',', $disable_newsletters) . ")";
			$result = $this->Db->Query($query);
		}

		return $return_list;
	}

	/**
	 * GetRecordByID
	 * Get newsletter record by ID
	 *
	 * @param Integer $newsletterid Newsletter ID to be fetched
	 * @return Array|NULL|FALSE Returns newsletter record if successful (as an associative array), NULL if record doesn't exists, FALSE otherwise
	 */
	function GetRecordByID($newsletterid)
	{
		$newsletterid = intval($newsletterid);

		$query = "
			SELECT	*
			FROM	[|PREFIX|]newsletters
			WHERE	newsletterid = {$newsletterid}
		";

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$newsletter = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		if (!$newsletter) {
			null;
		}

		return $newsletter;
	}

	/**
	 * GetLinks
	 * Get available links in a newsletter
	 *
	 * @param Integer $newsletterid ID of the newsletter to be scraped for links
	 * @return Array|FALSE Returns an array of links (associated array) if successful, FALSE otherwise
	 */
	function GetLinks($newsletterid)
	{
		require_once(dirname(__FILE__) . '/ss_email.php');
		$ssemailapi = new SS_Email_API();

		$record = $this->GetRecordByID($newsletterid);
		if (!$record) {
			return false;
		}

		return $ssemailapi->GetLinks($record['textbody'], $record['htmlbody']);
	}
}
