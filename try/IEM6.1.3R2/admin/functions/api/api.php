<?php
/**
* This has the Base SendStudio API class in it.
* Includes the init file (to set up database and so on) if it needs to.
*
* @version     $Id: api.php,v 1.70 2008/02/20 07:40:14 chris Exp $
* @author Chris <chris@interspire.com>
*
* @todo Remove GetCustomFieldData
*
* @package API
*/

/**
* This has the Base API class in it.
* Sets up the database object for use.
* Has base functions available to all subclasses, like Get, Set
* A lot of the queue functions are in here also.
*
* @package API
*/
class API
{

	/**
	* Database object is stored here. Is null by default, the constructor sets it up.
	*
	* @see API
	*
	* @var Object
	*/
	var $Db = null;

	/**
	* The ConvertDate object is stored here.
	*
	* @see GetServerTime
	*
	* @var Object
	*/
	var $ConvertDate = null;

	/**
	* List of all formats.
	* The option is the language pack variable to display.
	*
	* @see GetAllFormats
	*
	* @var Array
	*/
	var $AllFormats = array('b' => 'TextAndHTML', 'h' => 'HTML', 't' => 'Text');

	/**
	* TextBody - this is used with templates, autoresponders, newsletters. Set it to nothing in the base class, the other API's set it.
	*
	* @see GetBody
	* @see SetBody
	*
	* @var String
	*/
	var $textbody = '';

	/**
	* HTMLBody - this is used with templates, autoresponders, newsletters. Set it to nothing in the base class, the other API's set it.
	*
	* @see GetBody
	* @see SetBody
	*
	* @var String
	*/
	var $htmlbody = '';

	/**
	 * Stores a cache of process result to see whether or not database is capable of sub-query
	 * @var boolean|null Boolean when it holds value, NULL if it is not yet initialized
	 */
	var $_cacheSubqueryCapable = null;

	/**
	* API
	* Sets up the database object for this and the child objects to use.
	*
	* @see GetDb
	*
	* @return Void Doesn't return anything.
	*/
	function API()
	{
		$this->GetDb();
	}

	/**
	* GetDb
	* Sets up the database object for this and the child objects to use.
	* If the Db var is already set up and the connection is a valid resource, this will return true straight away.
	* If the Db var is null or the connection is not valid, it will fetch it and store it for easy reference.
	* If it's unable to setup the database (or it's null or false) it will trigger an error.
	*
	* @see Db
	* @see IEM::getDatabase()
	*
	* @return Boolean True if it works or false if it fails. Failing also triggers a fatal error.
	*/
	function GetDb()
	{
		if (is_object($this->Db) && is_resource($this->Db->connection)) {
			return true;
		}

		if (is_null($this->Db) || !$this->Db->connection) {
			$Db = IEM::getDatabase();
			$this->Db = &$Db;
		}

		if (!is_object($this->Db) || !is_resource($this->Db->connection)) {
			throw new Exception("Unable to connect to the database. Please make sure the database information specified in admin/includes/config.php are correct.");
		}
		return true;
	}

	/**
	* Set
	* This sets the class var to the value passed in.
	* If a variable name isn't provided, this will return false.
	* If the variable doesn't exist in the object, this will return false
	* It checks to make sure a class variable exists before it assigns the value.
	*
	* <b>Example</b>
	* <code>
	* $obj->Set('non-existent-var', 'xyz');
	* </code>
	* will return false.
	*
	* <code>
	* $obj->Set('existent-var', 'xyz');
	* </code>
	* will return true.
	*
	* @param String $varname Name of the class var to set.
	* @param Mixed $value The value to set the class var (this can be an array, string, int, float, object).
	*
	* @return Boolean True if it works, false if the var isn't present or not provided.
	*/
	function Set($varname='', $value='')
	{

		if ($varname == '') {
			return false;
		}

		// make sure we're setting a valid variable.
		$my_vars = array_keys(get_object_vars($this));
		if (!in_array($varname, $my_vars)) {
			return false;
		}

		$this->$varname = $value;
		return true;
	}

	/**
	* Get
	* Returns the class variable based on the variable passed in.
	* If a variable name isn't provided, or if the object variable doesn't exist, this will return false.
	*
	* @param String $varname Name of the class variable to return.
	*
	* @return False|Mixed Returns false if the class variable doesn't exist, otherwise it will return the value in the variable.
	*/
	function Get($varname='')
	{
		if ($varname == '') {
			return false;
		}

		if (!isset($this->$varname)) {
			return false;
		}

		return $this->$varname;
	}

	/**
	* GetAllFormats
	* Returns a list of all formats for use with newsletters, templates, autoresponders and lists.
	*
	* @see AllFormats
	*
	* @return Array List of all formats that we can use.
	*/
	function GetAllFormats()
	{
		return $this->AllFormats;
	}

	/**
	* GetFormat
	* Returns a format name based on the format letter you pass in.
	*
	* <b>Example</b>
	* <code>
	* $format = 'h';
	* </code>
	* will return 'HTML'
	*
	* @param String $format Format to find and return the name of.
	*
	* @see AllFormats
	*
	* @return False|String False if the format doesn't exist, otherwise returns a string of the format name.
	*/
	function GetFormat($format='h')
	{
		if (empty($format)) {
			$format = 'h';
		} else {
			$format = strval($format);
		}

		$format = strtolower($format{0}); // only get the first character in case the whole name is passed in.
		if (!in_array($format, array_keys($this->AllFormats))) {
			return false;
		}

		return $this->AllFormats[$format];
	}


	/**
	* SetBody
	* SetBody sets class variables for easy access. Newsletters, templates and autoresponders all use this.
	* If you pass in something other than text and html for the bodytype, this returns false.
	*
	* @param String $bodytype The type you're setting. This is either text or html.
	* @param String $content The content to set the bodytype to.
	*
	* @return Boolean Returns whether it worked or not. Passing an invalid bodytype will return false. Passing in a correct bodytype will return true.
	*/
	function SetBody($bodytype='text', $content='')
	{
		switch (strtolower($bodytype)) {
			case 'text':
				$this->textbody = $content;
				return true;
			break;
			case 'html':
				$this->htmlbody = $content;
				return true;
			break;
			default:
				return false;
		}
	}

	/**
	* GetBody
	* GetBody returns the class variable based on which bodytype you're after.
	* If you pass in something other than text and html, this returns false.
	*
	* @param String $bodytype The type you're getting. This is either text or html.
	*
	* @return False|String If the right sort of bodytype is passed in, it will return the content. If an invalid type is passed in, this will return false.
	*/
	function GetBody($bodytype='text')
	{
		switch (strtolower($bodytype)) {
			case 'text':
				return $this->textbody;
			break;
			case 'html':
				return $this->htmlbody;
			break;
			default:
				return false;
		}
	}

	/**
	* CreateQueue
	* Creates a queue based on the queuetype and the recipients you pass in.
	* This is used by the send process, export process and autoresponder process to create a queue before anything else happens.
	* It also checks that the queues-sequence is ok and only has one value.
	* In some cases, the queue was able to get multiple values in it which caused a problem when CreateQueue was called.
	* Now it checks that the sequence only has one value in it.
	* If it has more than one value in the queues_sequence table, then it attempts to reset it by checking the stats_newsletters, autoresponders and queues_sequence tables for the max value.
	* Once the new max value is calculated, then it's reset in the main queues_sequence table.
	* If that process fails, this will return false.
	*
	* Before this function is called, the user has to be logged in or set up programatically so we can get the 'owner' of the queue.
	*
	* @param String $queuetype The type of queue to create.
	* @param Array $recipients A list of recipients to put in the queue as an array. If it's not an array (ie you just pass in one id), it gets converted to one. If this is not empty, once the queue has been created all recipients in this array are added to the queue as 'unprocessed'.
	*
	* @see GetUser
	* @see User_API::UserID
	* @see Autoresponders_API::Create
	* @see Jobs_Send_API::ProcessJob
	* @see Subscribers_API::GetSubscribers
	* @see Send::Process
	* @see Subscribers_Export::ExportSubscribers_Step3
	* @see Db::CheckSequence
	*
	* @return False|Int Returns false if it can't create a queue, or if in the process of adding subscribers to the database something goes wrong. If everything succeeds, this returns the new queueid.
	*/
	function CreateQueue($queuetype='send', $recipients=array())
	{
		$queuetype = strtolower($queuetype);
		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}

		$thisuser = GetUser();
		$ownerid = $thisuser->userid;

		$queue_sequence_ok = $this->Db->CheckSequence(SENDSTUDIO_TABLEPREFIX . 'queues_sequence');
		if (!$queue_sequence_ok) {
			$query =  "(SELECT queueid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters ORDER BY queueid DESC LIMIT 1)";
			$query .= " UNION ";
			$query .= "(SELECT queueid FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders ORDER BY queueid DESC LIMIT 1)";
			$query .= " UNION ";
			$query .= "(SELECT queueid FROM " . SENDSTUDIO_TABLEPREFIX . "triggeremails ORDER BY queueid DESC LIMIT 1)";
			$query .= " UNION ";
			$query .= "(SELECT id AS queueid FROM " . SENDSTUDIO_TABLEPREFIX . "queues_sequence ORDER BY id DESC LIMIT 1)";
			$query .= " ORDER BY queueid DESC LIMIT 1";
			$id = $this->Db->FetchOne($query, 'queueid');
			$new_id = $id + 1;
			$reset_ok = $this->Db->ResetSequence(SENDSTUDIO_TABLEPREFIX . 'queues_sequence', $new_id);
			if (!$reset_ok) {
				return false;
			}
		}

		$queueid = $this->Db->NextId(SENDSTUDIO_TABLEPREFIX . 'queues_sequence');
		if (!$queueid) {
			return false;
		}

		if (!empty($recipients)) {
			$this->Db->StartTransaction();
			foreach ($recipients as $pos => $subscriberid) {
				$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "queues (queueid, queuetype, ownerid, recipient, processed) VALUES (" . $queueid . ", '" . $this->Db->Quote($queuetype) . "', " . (int)$ownerid . ", " . (int)$subscriberid['subscriberid'] . ", 0)";
				$result = $this->Db->Query($query);
				if (!$result) {
					return false;
				}
			}
			$this->Db->CommitTransaction();
		}
		return $queueid;
	}

	/**
	* AddToQueue
	* Adds to an existing queue based on the recipient list you pass in.
	* The recipients in the queue are added as 'unprocessed'.
	*
	* Before this function is called, the user has to be logged in or set up programatically so we can get the 'owner' of the queue.
	*
	* The parameter $processtime is added to support triggeremails functionalities.
	* The column was already part of the original table definition, but is currently not being
	* used by anything.
	*
	* @param Int $queueid The queueid you're accessing.
	* @param String $queuetype The queuetype you're adding to.
	* @param Array $recipients A list of recipients to put in the queue as an array.
	* @param Integer $processtime Process time value. This value can be omitted.
	*
	* @see GetUser
	* @see User_API::UserID
	* @see RemoveDuplicatesInQueue
	* @see CreateQueue
	*
	* @return Boolean Returns false if it can't add to the queue, otherwise true.
	*/
	function AddToQueue($queueid=0, $queuetype='send', $recipients=array(), $processtime = null)
	{
		$queueid = (int)$queueid;

		$queuetype = strtolower($queuetype);

		$thisuser = GetUser();
		$ownerid = $thisuser->userid;

		$sqlProcessTime = 'NULL';
		if (!is_null($processtime)) {
			$tempProcessTime = date('Y-m-d H:i:s', $processtime);
			if ($tempProcessTime != false) {
				$sqlProcessTime = "'" . $tempProcessTime . "'";
			}
		}

		if (!empty($recipients)) {
			$this->Db->StartTransaction();
			foreach ($recipients as $pos => $subscriberid) {
				$tempSubscriberID = intval($subscriberid['subscriberid']);

				$query = "
					INSERT INTO [|PREFIX|]queues (queueid, queuetype, ownerid, recipient, processed, processtime)
					VALUES ({$queueid}, '" . $this->Db->Quote($queuetype) . "', {$ownerid}, {$tempSubscriberID}, 0, {$sqlProcessTime})
				";

				$result = $this->Db->Query($query);
				if (!$result) {
					list($msg, $errno) = $this->Db->GetError();
					$this->Db->RollbackTransaction();
					trigger_error($msg, $errno);
					return false;
				}
			}
			$this->Db->CommitTransaction();
		}

		return true;
	}

	/**
	* ImportToQueue
	* Import recipients to a queue based on the criteria passed in.
	* This imports all subscriber id's on a particular list based on the listid passed in straight through sql.
	* Once they have all been inserted, duplicates are checked and removed.
	* It is used by the autoresponder api when the appropriate flag is enabled.
	*
	* Before this function is called, the user has to be logged in or set up programatically so we can get the 'owner' of the queue.
	*
	* @param Int $queueid The queue you're adding recipients to.
	* @param String $queuetype The queuetype you're adding them to.
	* @param Int $lists The list you're importing from.
	*
	* @see GetUser
	* @see User_API::UserID
	* @see RemoveDuplicatesInQueue
	* @see Autoresponders_API::Create
	* @see Autoresponders_API::Save
	*
	* @return False|RemoveDuplicatesInQueue Returns false if there's no list to import them from. Otherwise imports them, and returns the status from RemoveDuplicatesInQueue.
	*/
	function ImportToQueue($queueid=0, $queuetype='', $listid=0)
	{
		$listid = (int)$listid;
		if ($listid <= 0) {
			return false;
		}

		$thisuser = GetUser();
		$ownerid = $thisuser->userid;

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "queues(queueid, queuetype, ownerid, recipient, processed) SELECT " . (int)$queueid . ", '" . $this->Db->Quote($queuetype) . "', '" . (int)$ownerid . "', l.subscriberid, 0 FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE l.listid='" . (int)$listid . "'";
		$this->Db->Query($query);

		return $this->RemoveDuplicatesInQueue($queueid, $queuetype, $listid);
	}


	/**
	* RemoveDuplicatesInQueue
	* Removes duplicate recipients from a queue based on the id and queuetype you pass in.
	*
	* If the listid's are passed in (either singularly or as an array), then the queries can take a slightly more efficient path.
	* Instead of having to do a join to the queues table and the subscribers table to work out who was added and which ones are duplicates,
	* The listid's can be used to only query the main list_subscribers table (ie eliminate the join & extra work).
	*
	* The old method still works (if no listid's are passed through).
	*
	* @param Int $queueid The queueid you're removing recipients from.
	* @param String $queuetype The queuetype you're removing from.
	* @param Array $listids The list id's we have imported from. If this is not present, the function still works but the query or queries will take longer as they will need to join to more tables to do the same work.
	*
	* @return Boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RemoveDuplicatesInQueue($queueid=0, $queuetype='send', $listids=array())
	{
		if (!is_array($listids)) {
			$listids = array($listids);
		}

		$listids = $this->CheckIntVars($listids);

		$queueid = intval($queueid);
		$queuetype = $this->Db->Quote(strtolower($queuetype));
		$safeguard_counter = 0;
		$safeguard_max_counter = 0;

		$queryString = '';
		$queryString_substring = '';
		$queryString_listid = '';

		if (!empty($listids)) {
			$queryString_listid .= " AND l.listid IN (" . implode(',', $listids) . ")";
		}

		// Define a query which will delete duplicate emailaddress out of the queue table
		switch (SENDSTUDIO_DATABASE_TYPE) {
			// Since PostgreSQL cannot select columns that are grouped with another column,
			// we will need to use sub-queries. This replacement query increase query
			// speed significantly (from ~250 minutes -- guestimating -- to ~7 seconds for a ~400k subscriber list)
			case 'pgsql':
				$queryString_substring = "
					SELECT	DISTINCT ON (emailaddress) subscriberid
					FROM	[|PREFIX|]list_subscribers AS l,
							[|PREFIX|]queues AS q
					WHERE	q.recipient = l.subscriberid
							AND q.queueid = {$queueid}
							AND queuetype = '{$queuetype}'
							{$queryString_listid}
							AND l.emailaddress IN	(	SELECT		l.emailaddress AS emailaddress
														FROM		[|PREFIX|]list_subscribers AS l,
																	[|PREFIX|]queues AS q
														WHERE		q.recipient = l.subscriberid
																	AND q.queueid = {$queueid}
																	AND queuetype = '{$queuetype}'
																	{$queryString_listid}
														GROUP BY	l.emailaddress HAVING COUNT(l.emailaddress) > 1
													)
				";

				$queryString = "
					DELETE FROM	[|PREFIX|]queues
							WHERE		queueid = {$queueid}
										AND queuetype = '{$queuetype}'
							            AND recipient IN({$queryString_substring})
				";
			break;

			// Since we can now use sub-qeury, I will just refactor the SQL
			// to use sub-query. The use of double subquery is because MySQL
			// works faster with temporary table defined
			case 'mysql':
				$queryString_substring = "
					SELECT		l.subscriberid AS subscriberid
					FROM		[|PREFIX|]list_subscribers AS l,
								[|PREFIX|]queues AS q
					WHERE 		q.recipient = l.subscriberid
								AND q.queueid = {$queueid}
								AND queuetype = '{$queuetype}'
								{$queryString_listid}
					GROUP BY	l.emailaddress HAVING COUNT(l.emailaddress) > 1
				";

				$queryString = $queryString_substring;
			break;

			// No other database types are supported at the moment
			default:
				die('Unknown database type');
			break;
		}


		// We create an endless loop that looks for subscriberid's based on email addresses that are duplicated.
		// We can't get a full list of all id's that are duplicated, so instead we narrow each email address down one by one.
		//
		// Example:
		// email@domain.com is on list 1, 2 & 3.
		// email2@domain.com is on list 2 & 3.
		// email3@domain.com is only on list 1.
		// and we send to lists 1, 2, & 3.
		//
		// The first part of the query will find email2@domain.com and email@domain.com (since they are both duplicated on the send).
		// Then it will call RemoveFromQueue to remove one instance of each subscriber.
		// The next loop will only find email@domain.com (since email2@domain.com only has one instance in the queue now)
		// Then it will call RemoveFromQueue to remove that second instance of the duplicate.
		// Finally, another loop will happen this time fetching nothing (1 email address per recipient) - and that will return out of the function.
		//
		// This relies on the database class returning the correct result from NumAffected() function
		while (true) {
			$result = $this->Db->Query($queryString);
			if (!$result) {
				trigger_error("Cannot execute: {$queryString} ......\n Reason: " . $this->Db->Error(), E_USER_NOTICE);
				// Since MOST of the code does NOT check the returned result, it's best if we die here... When ALL of the precedure check the resuturn status
				// of this function, you can remove the die call.
				die ('Cannot query database: Database may have been restarted. If you keep getting this error message, please contact your system administrator.');
				return false;
			}

			if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
				$recipients_to_remove = array();
				while ($row = $this->Db->Fetch($result)) {
					$recipients_to_remove[] = $row['subscriberid'];
				}

				if (empty($recipients_to_remove)) {
					return true;
				}

				$this->RemoveFromQueue($queueid, $queuetype, $recipients_to_remove);
			} else {
				$count = intval($this->Db->NumAffected($result));
				if ($count == 0) {
					return true;
				}
			}

			++$safeguard_counter;
			++$safeguard_max_counter;

			// What the query runs 100 times??? Need to check if this is an error
			if ($safeguard_counter >= 100) {
				$query = "
					SELECT	COUNT(1) AS rowcounter
					FROM	({$queryString_substring}) AS x
				";

				$result = $this->Db->FetchOne($query, 'rowcounter');
				if ($result === false) {
					trigger_error("Cannot execute: {$queryString} ......\n Reason: " . $this->Db->Error(), E_USER_NOTICE);
					// Since MOST of the code does NOT check the returned result, it's best if we die here... When ALL of the precedure check the resuturn status
					// of this function, you can remove the die call.
					die ('Cannot query database: Database may have been restarted. If you keep getting this error message, please contact your system administrator.');
					return false;
				}

				if ($result === 0) {
					return true;
				}

				// Give it another 20 times leeway since there are still dumplicates in the queue table
				$safeguard_counter = 80;
			}

			// Hmmmm, there must be something wrong.... The query haven't stopped looping
			// for 100,000,000 times.... even that is excessive.... We will need to return false
			// and log this anomaly
			if ($safeguard_max_counter >= 100000000) {
				$error_message = "The query: {$queryString} has been executed for more than 100 million times. Please contact your system administrator to see if there is anything wrong with the system.";
				trigger_error($error_message, E_USER_NOTICE);

				// Since MOST of the code does NOT check the returned result, it's best if we die here... When ALL of the precedure check the resuturn status
				// of this function, you can remove the die call.
				die ($error_message);
				return false;
			}
		}
	}

	/**
	* RemoveBannedEmails
	* Checks a queue for banned email addresses and domain names. It checks the lists you pass in (listids) and the global list.
	* For performance reasons, we break it down into a bunch of union queries to check each part.
	* - Check each list for specific email address bans
	* - Check the global ban list for specific email address bans
	* - Check each list for domain name bans
	* - Check the global ban list for domain name bans
	* Then put all of that together and delete all of the banned subscribers it finds.
	* The method used to delete the banned emails will be different depending on the database type you are using.
	* See inline comments for further details.
	*
	* @param Array $list_ids A list of listid's to check for banned subscribers. This doesn't include the global list.
	* @param Int $queueid The queueid we're working with.
	* @param String $queuetype The Queuetype we're working with. Most likely to be the 'send' queue.
	*
	* @see SENDSTUDIO_DATABASE_TYPE
	*
	* @return Boolean Returns true if it worked, returns false if there was a problem with the query.
	*/
	function RemoveBannedEmails($list_ids = array(), $queueid=0, $queuetype='send')
	{
		if (!is_array($list_ids)) {
			$list_ids = array($list_ids);
		}

		$list_ids = $this->CheckIntVars($list_ids);

		if (empty($list_ids)) {
			$list_ids = array('0');
		}

		$select = "SELECT l.subscriberid AS subscriberid ";
		$from = " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "banned_emails b";

		/**
		* We could combine each part of these unions into one big query but it's a very slow approach.
		* By doing a UNION on each section, the db is able to optimize (ie use an index) just for that section
		* instead of trying to optimize the whole thing in one go.
		*
		* A UNION ALL (compared to a UNION) doesn't try to remove duplicate id's from the returned items.
		* We handle that in the code anyway in the delete statement (it doesn't matter if someone is removed from the queue twice).
		* So it will be quicker because the db doesn't have to check the combined result for duplicate id's.
		*
		* We use the in clause for both tables instead of joining them together because
		* postgresql 8.3 doesn't support joining a varchar column (b.list) to an int column (l.listid)
		*/
		$subscriber_query = $select . $from . " WHERE l.listid IN ('" . implode('\',\'', $list_ids) . "') AND b.list IN ('" . implode('\',\'', $list_ids) . "') AND b.emailaddress=l.emailaddress";
		$subscriber_query .= " UNION ALL ";
		$subscriber_query .= $select . $from . " WHERE l.listid IN ('" . implode('\',\'', $list_ids) . "') AND b.list IN ('g') AND b.emailaddress=l.emailaddress";
		$subscriber_query .= " UNION ALL ";
		$subscriber_query .= $select . $from . " WHERE l.listid IN ('" . implode('\',\'', $list_ids) . "') AND b.list IN ('" . implode('\',\'', $list_ids) . "') AND b.emailaddress=l.domainname";
		$subscriber_query .= " UNION ALL ";
		$subscriber_query .= $select . $from . " WHERE l.listid IN ('" . implode('\',\'', $list_ids) . "') AND b.list IN ('g') AND b.emailaddress=l.domainname";

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$result = $this->Db->Query($subscriber_query);
			$subscribers = array();

			while ($row = $this->Db->Fetch($result)) {
				$subscribers[] = $row['subscriberid'];
			}

			/**
			* No duplicates? Excellent - just return from the function.
			*/
			if (empty($subscribers)) {
				return true;
			}

			/**
			* If we find duplicate subscriber id's to remove, we need to chunk the results up.
			* Otherwise we could end up with a very long query and mysql will throw an error about the query being too long.
			* So break it up in to 500 id's at a time and go into an endless loop until they are all removed.
			*
			* The query settings can be controlled by max_allowed_packet INI for MySQL (server default is 1M)
			*/
			$banned_email_count_before = sizeof($subscribers);

			$remove_size = 500;
			$start_pos = 0;

			while ($start_pos < $banned_email_count_before) {
				$remove_subscribers = array_slice($subscribers, $start_pos, $remove_size);

				$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . (int)$queueid . "' AND queuetype='" . $this->Db->Quote($queuetype) . "' AND recipient IN (" . implode(',', $remove_subscribers) . ")";
				$result = $this->Db->Query($query);
				if ($result === false) {
					return false;
				}

				$start_pos += $remove_size;
			}
		}

		/**
		* If it's a postgresql database, we can just run a normal delete query with a subquery inside it.
		* So we don't need to do any special loops or chunking to handle a lot of banned email addresses or domain names.
		*/
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE recipient IN (";
			$query .= $subscriber_query;
			$query .= ") AND queueid='" . (int)$queueid . "' AND queuetype='" . $this->Db->Quote($queuetype) . "'";
			$result = $this->Db->Query($query);
			if ($result === false) {
				return false;
			}
		}
		return true;
	}

	/**
	* RemoveUnsubscribedEmails
	* Checks a queue for unsubscribed email addresses. It checks the lists you pass in (listids), the queue and the queue type. The database queries are slightly different depending on which database type you are using.
	*
	* @param Array $lists A list of listid's to check for unsubscribed subscribers.
	* @param Int $queueid The queueid to check.
	* @param String $queuetype The Queuetype to check.
	*
	* @see SENDSTUDIO_DATABASE_TYPE
	*
	* @return Boolean Returns true if it worked, returns false if there was a problem with the query.
	*/
	function RemoveUnsubscribedEmails($lists = array(), $queueid=0, $queuetype='send')
	{
		if (!is_array($lists)) {
			$lists = array($lists);
		}

		$lists = $this->CheckIntVars($lists);

		if (empty($lists)) {
			$lists = array('0');
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {

			$query = "SELECT l.subscriberid AS subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe l WHERE l.listid IN (" . implode(',', $lists) . ")";

			$subscribers = array();
			$result = $this->Db->Query($query);
			while ($row = $this->Db->Fetch($result)) {
				$subscribers[] = $row['subscriberid'];
			}

			if (empty($subscribers)) {
				return true;
			}

			/**
			* If we find unsubscribed subscriber id's to remove, we need to chunk the results up.
			* Otherwise we could end up with a very long query and mysql will throw an error about the query being too long.
			* So break it up in to 500 id's at a time and go into an endless loop until they are all removed.
			*
			* The query settings can be controlled by max_allowed_packet INI for MySQL (server default is 1M)
			*/
			$remove_size = 500;
			$start_pos = 0;

			$number_recipients = count($subscribers);

			while ($start_pos < $number_recipients) {
				$remove_subscribers = array_slice($subscribers, $start_pos, $remove_size);

				$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . (int)$queueid . "' AND queuetype='" . $this->Db->Quote($queuetype) . "' AND recipient IN (" . implode(',', $remove_subscribers) . ")";

				$result = $this->Db->Query($query);

				if ($result === false) {
					return false;
				}

				$start_pos += $remove_size;
			}
		}

		/**
		* If it's a postgresql database, we can just run a normal delete query with a subquery inside it.
		*/
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE recipient IN (SELECT l.subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe l WHERE l.listid IN (" . implode(',', $lists) . ")) AND queueid='" . (int)$queueid . "' AND queuetype='" . $this->Db->Quote($queuetype) . "'";

			$result = $this->Db->Query($query);
			if ($result === false) {
				return false;
			}
		}

		return true;
	}


	/**
	* RemoveFromQueue
	* Removes recipients from a queue based on the id, queuetype and recipients list you pass in.
	*
	* @param Int $queueid The queueid you're removing recipients from.
	* @param String $queuetype The queuetype you're deleting from.
	* @param Mixed $recipients A list of recipients to remove from the queue. This can be an array or a singular recipient id.
	*
	* @return Boolean Returns true if the query worked, returns false if there was a problem with the query.
	*/
	function RemoveFromQueue($queueid=0, $queuetype='export', $recipients=array())
	{
		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}

		$recipients = $this->CheckIntVars($recipients);

		if (empty($recipients)) {
			return true;
		}

		/**
		* The removing process is chunked, because MySQL might reject a long query string
		* The query settings can be controlled by max_allowed_packet INI for MySQL (server default is 1M)
		*/
		$remove_size = 500;
		$start_pos = 0;

		$number_recipients = count($recipients);

		while ($start_pos < $number_recipients) {
			$remove_subscribers = array_slice($recipients, $start_pos, $remove_size);

			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . (int)$queueid . "' AND queuetype='" . $this->Db->Quote($queuetype) . "' AND recipient IN (" . implode(',', $remove_subscribers) . ")";

			$result = $this->Db->Query($query);

			if ($result === false) {
				return false;
			}

			$start_pos += $remove_size;

		}

		return true;
	}

	/**
	* MarkAsProcessed
	* Marks recipients as processed in the queue.
	* An update is usually 'cheaper' in database terms to do than a delete so that's what this does.
	* It sets them as processed and also records the processtime so we could also use that as a filter if necessary.
	* If there are no recipients to mark as processed, the function returns straight away.
	*
	* @param Int $queueid The queueid you're processing recipients for.
	* @param String $queuetype The queuetype you're processing.
	* @param Int $recipients This can be a singular recipient id.
	*
	* @return Boolean Returns true if the query worked, returns false if there was a problem with the query.
	*/
	function MarkAsProcessed($queueid=0, $queuetype='export', $recipient=0)
	{
        $queuetype = strtolower($queuetype);
        $queueid = (int)$queueid;
        $recipient = (int)$recipient;
		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "queues SET processed=1, processtime=NOW() WHERE queueid={$queueid} AND queuetype='{$queuetype}' AND recipient={$recipient}";
		$result = $this->Db->Query($query);
		if ($result){
			return true;
		}else{
			trigger_error(mysql_error());
		}
        return false;
	}

	/**
	* FetchFromQueue
	* Fetches recipients from a queue based on the queueid and queuetype and only fetches a certain number of id's so we can process them in chunks.
	*
	* If we are fetching for the autoresponder queue, we make sure we add an extra check to make sure we haven't sent to the subscriber already.
	* This is used as an 'update' type flag instead of deleting them straight away (database speed issue).
	* It also checks the subscribe date of the subscriber so we only fetch the subscribers we need to action.
	* By doing this we limit the number of recipients we need to process,
	* instead of looking at all of them we only look at the recipients who have been subscribed long enough for a particular autoresponder.
	*
	* @param Int $queueid The queueid you're fetching recipients from.
	* @param String $queuetype The queuetype you're fetching from.
	* @param Int $startpos Starting position of records you want to fetch.
	* @param Int $limit The number of records you want to fetch. Combined with startpos you can 'page' through records in a queue.
	* @param Int $hours_after_subscription The autoresponder hours after subscription time. This is used to cut down the recipients that are returned in the first place. This parameter is only used if the queuetype is 'autoresponder'.
	*
	* @see Jobs_Autoresponders_API::ActionJob
	*
	* @return False|Array Returns false if the query fails. If the query works then it returns an array of recipients - whether that array is empty or not is left for the calling function to check.
	*/
	function FetchFromQueue($queueid=0, $queuetype='', $startpos=1, $limit=100, $hours_after_subscription=-1)
	{
		$queueid = (int)$queueid;
		$queuetype = strtolower($queuetype);

		$query = "SELECT recipient FROM " . SENDSTUDIO_TABLEPREFIX . "queues q ";

		$where_clause = " WHERE q.queueid='" . $queueid . "' AND q.queuetype='" . $this->Db->Quote($queuetype) . "' AND q.processed='0'";

		$hours_after_subscription = (int)$hours_after_subscription;

		if (strtolower(substr($queuetype, 0, 4)) == 'auto') {
			$where_clause .= " AND q.sent='0'";
			if ($hours_after_subscription > -1) {
				$query .= " INNER JOIN " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls ON (q.recipient=ls.subscriberid)";

				$start_time = $this->GetServerTime() - ($hours_after_subscription * 3600);

				$where_clause .= " AND ls.subscribedate < " . $start_time;
			}
		}

		$query .= $where_clause;

		if ($startpos && $limit) {
			$query .= $this->Db->AddLimit((($startpos - 1) * $limit), $limit);
		}

		$result = $this->Db->Query($query);
		if (!$result) {
                        trigger_error('Problem fetching recipients from queue' . "\n" . mysql_error());
			return false;
		}

		$recipients = array();
		while ($row = $this->Db->Fetch($result)) {
			array_push($recipients, $row['recipient']);
		}
		return $recipients;
	}

	/**
	* IsQueue
	* Checks whether a queue has already been created for this id and queuetype.
	* This is used with autoresponders and scheduled sending especially.
	* Scheduled sending will create the queue when it is about to run, so it needs to check if it is there already first.
	*
	* @param Int $queueid The queueid you're checking for.
	* @param String $queuetype The queuetype you're checking for.
	*
	* @see Jobs_Send_API::ActionJob
	* @see Jobs_Autoresponders_API::ActionJob
	*
	* @return Boolean Returns false if it doesn't exist, returns true if it does.
	*/
	function IsQueue($queueid=0, $queuetype='')
	{
		$query = "SELECT queueid FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . $this->Db->Quote($queueid) . "' AND queuetype='" . $this->Db->Quote($queuetype) . "' LIMIT 1";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			return false;
		}

		if ($row['queueid'] <= 0) {
			return false;
		}

		return true;
	}

	/**
	* ClearQueue
	* Deletes all recipients on a particular queue based on the queueid and queuetype.
	*
	* @param Int $queueid The queueid you're deleting.
	* @param String $queuetype The queuetype you're deleting.
	*
	* @return Boolean Returns true if the query worked, returns false if there was a problem with the query.
	*/
	function ClearQueue($queueid=0, $queuetype='')
	{
		$queuetype = strtolower($queuetype);
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . $this->Db->Quote($queueid) . "' AND queuetype='" . $this->Db->Quote($queuetype) . "'";
		$result = $this->Db->Query($query);
		if ($result) {
			return true;
		}

		return false;
	}

	/**
	* QueueSize
	* Lets us know how many unprocessed recipients are left in the queue.
	*
	* @param Int $queueid The queueid you're fetching recipients from.
	* @param String $queuetype The queuetype you're fetching from.
	*
	* @return False|Int Returns false if the query didn't work. Otherwise returns the number of recipients left in the queue.
	*/
	function QueueSize($queueid=0, $queuetype='')
	{
		$queueid = (int)$queueid;
		$queuetype = strtolower($queuetype);

		$query = "SELECT COUNT(recipient) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . $queueid . "' AND queuetype='" . $this->Db->Quote($queuetype) . "' AND processed='0'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		return $row['count'];
	}


	/**
	* GetCustomFieldData
	* Returns custom field information based on the data passed in. If it's a checkbox or dropdown custom field type, it will get the 'real' values from the custom field - rather than using the 'key' values.
	*
	* <b>Example</b>
	* <code>
	* $data = array('fieldtype' => 'text', 'data' => 'this is my text', 'fieldid' => 0);
	* </code>
	* <code>
	* $data = array('fieldtype' => 'dropdown', 'data' => array('m'), 'fieldid' => 0);
	* </code>
	*
	* @param Array $data An array which contains the custom field data you want to process. This includes the fieldtype, the data and the fieldid.
	*
	* @see Customfields_API::Load
	* @see Customfields_API::Settings
	*
	* @see GetCustomFieldSettings
	*
	* @return Mixed Returns false if the data array is empty or it doesn't have a fieldtype. Returns an array of real values if it's a checkbox or dropdown. Otherwise returns the raw value (for textboxes for example) or the integer value if it's a number field.
	*
	* @deprecated See GetCustomFieldSettings instead. Marked for removal in NX 3.0
	*/
	function GetCustomFieldData($data=array())
	{
		if (!is_array($data)) {
			return false;
		}

		if (!isset($data['fieldtype'])) {
			return false;
		}

		switch (strtolower($data['fieldtype'])) {
			case 'checkbox':
			case 'dropdown':
				if ($data['fieldtype'] == 'checkbox') {
					$returninfo = (!is_array($data['data'])) ? unserialize($data['data']) : $data['data'];
				} else {
					$returninfo = $data['data'];
				}

				require_once(dirname(__FILE__) . '/customfields_' . $data['fieldtype'] . '.php');
				$apiname = 'CustomFields_' . ucwords($data['fieldtype']) . '_API';
				$customfields_api = new $apiname();
				$customfields_api->Load($data['fieldid']);

				$returndetails = array();
				$settings = $customfields_api->Settings;
				foreach ($settings['Key'] as $pos => $val) {
					if (is_array($returninfo)) {
						if (in_array($val, array_keys($returninfo))) {
							$returndetails[] = $settings['Value'][$pos];
						}
					} else {
						if ($val == $returninfo) {
							$returndetails[] = $settings['Value'][$pos];
							break;
						}
					}
				}
				return $returndetails;
			break;
			default:
				return $data['data'];
		}
		return false;
	}

	/**
	* DeleteUserStats
	* Deletes statistics from a user account based on the details passed in. This is used when a scheduled job has been started but then cancelled (ie deleted). This will update the user stats to reflect the right number of emails that were sent from the beginning until when it was deleted.
	*
	* @param Int $userid The userid to update statistics for
	* @param Int $jobid The job that was deleted
	* @param Int $remove_amount The amount to remove from the user statistics. If this is less than 0 for any reason, it is reset back to 0.
	* @param Boolean $delete_all Whether to delete the whole user statistics for this job or not. This is used if the stats are recorded (when the job is set up) but never run.
	*
	* @see Jobs_API::Delete
	*
	* @return True Always returns true
	*/
	function DeleteUserStats($userid=0, $jobid=0, $remove_amount=0, $delete_all=false)
	{
		$remove_amount = (int)$remove_amount;

		$userid = (int)$userid;
		$jobid = (int)$jobid;

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "stats_users SET ";

		if ($remove_amount < 0) {
			$remove_amount = 0;
		}

		if (!$delete_all) {
			if ($remove_amount >= 0) {
				$query .= "queuesize=queuesize - " . $remove_amount;
			}
		}

		if ($delete_all) {
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_users";
		}

		$query .= " WHERE jobid='" . $jobid . "' AND userid='" . $userid . "'";

		$result = $this->Db->Query($query);

		return true;
	}

	/**
	* FetchLink
	* Fetchs a url from the links table based on the linkid. This is used after tracking has taken place so we can redirect to the right place.
	*
	* @param Int $linkid Link to load
	* @param Int $statid The statistics id to load up the link for. This is passed in by link tracking so we can make sure it's a valid link and statistical recording.
	*
	* @see GetDb
	* @see SaveLink
	*
	* @return String Returns the URL from the database based on the information passed in.
	*/
	function FetchLink($linkid=0, $statid=false)
	{
		$this->GetDb();

		if (!$statid) {
			$query = "SELECT url FROM " . SENDSTUDIO_TABLEPREFIX . "links WHERE linkid='" . $this->Db->Quote($linkid) . "'";
		} else {
			$query = "SELECT url FROM " . SENDSTUDIO_TABLEPREFIX . "links l, " . SENDSTUDIO_TABLEPREFIX . "stats_links sl WHERE l.linkid=sl.linkid AND l.linkid='" . $this->Db->Quote($linkid) . "' AND sl.statid='" . $this->Db->Quote($statid) . "'";
		}
		$result = $this->Db->Query($query);
		$url = $this->Db->FetchOne($result, 'url');
		$url = str_replace(array('"', "'"), '', $url);
		return $url;
	}

	/**
	* CheckIntVars
	* This goes through the array passed in and strips out any non-numeric characters.
	* This can then be used safely for implodes for searching particular listid's or subscriberid's without worrying about sql injection.
	* Quoted numbers such as '2' or "11" will get returned without the quotes as per is_numeric functionality.
	* This loops over each element in the array and removes anything that is not a number.
	* Once that is done, the resulting array is returned (minus the bad elements).
	*
	* <b>Example</b>
	* <code>
	* $vals = array(1,'12', 'f', "string");
	* $vals = CheckIntVars($vals);
	* </code>
	* This will become:
	* <code>
	* $vals = array(1, 12);
	* </code>
	*
	* @param Array $array_to_check Array of values to check and make sure they are integers.
	*
	* @see RemoveBannedEmails
	* @see RemoveUnsubscribedEmails
	* @see RemoveFromQueue
	* @see MarkAsProcessed
	*
	* @return Array Array of values which are numbers only. All non-numeric characters or strings are removed.
	*/
	function CheckIntVars($array_to_check=array())
	{
		if (!is_array($array_to_check)) {
			return array();
		}
		foreach ($array_to_check as $p => $var) {
			if (!is_numeric($var)) {
				unset($array_to_check[$p]);
			}
		}
		return $array_to_check;
	}

	/**
	* GetServerTime
        * 
        * Returns the current timestamp for the server 
	*/
	function GetServerTime(){
            return gmdate('U');
	}
        
	/**
	* GetGmtTime
	* Uses the ConvertDate object to turn "now" server time into gmt time.
	*
	* @return Int Returns the new 'timestamp' in gmt time.
	*/
	function GetGmtTime(){
            return gmmktime();
	}

	/**
	* CleanVersion
	* Cleans up placeholders from the content passed in. If no subscriber information is passed in, it will replace placeholders with '#' so for example an unsubscribe link can't be clicked on from an rss feed if you are not a valid subscriber.
	*
	* @param String $content The content to "clean up".
	* @param Array $subscriberinfo The subscriber information to use to clean up the content. If this is not present, placeholders will be replaced with a '#'.
	*
	* @return String The new content either with proper placeholders or invalid placeholders.
	*/
	function CleanVersion($content='', $subscriberinfo=array()){
        $content = str_ireplace('%%todaysdate%%', date(GetLang('TodaysDate')), $content);        
		if (empty($subscriberinfo) || !isset($subscriberinfo['subscriberid'])) {
            $content = str_ireplace('%basic:archivelink%', '#', $content);
            $content = str_ireplace('%%mailinglistarchive%%', "#", $content);
            $content = str_ireplace('%%webversion%%', '#', $content);
			$content = preg_replace('/%basic:unsublink%/i', '#', $content);
			$content = preg_replace('/%%unsubscribelink%%/i', '#', $content);
			$content = preg_replace('/%%confirmlink%%/i', '#', $content);
			$content = preg_replace('/%basic:confirmlink%/i', '#', $content);
			$content = preg_replace('/%%sendfriend_(.*)%%/i', '#', $content);
			$content = preg_replace('/%%modifydetails_(.*)%%/i', '#', $content);
			return $content;
		}        
        $content = str_ireplace('%basic:unsublink%', '%%unsubscribelink%%', $content);
        $content = str_ireplace('http://%%unsubscribelink%%/', '%%unsubscribelink%%', $content);
        $content = str_ireplace('%basic:archivelink%', '%%mailinglistarchive%%', $content);
		$customfields = $subscriberinfo['CustomFields'];
        require_once(SENDSTUDIO_API_DIRECTORY.'/customfields_date.php');
		foreach ($customfields as $p => $details) {            
            if(empty($details)){continue;}
                if(isset($details['fieldtype'])){
                    switch ($details['fieldtype']) {
                        case 'checkbox':
                            // unserialize checkbox, ready for displaying in correct format.
                            $data = $details['data'];
                            $value = @unserialize($data);
                            if (is_array($value) && sizeof($value)) {
                                $data = implode(',', $value);
                                $details['data'] = $data;
                            }
                        break;
                        
                        case 'date':
                            $cfdateapi = new CustomFields_Date_API($details['fieldid']);
                            $real_order = $cfdateapi->GetRealValue($details['data']);
                            if($real_order !== false){$details['data'] = $real_order;}
                        break;
                }
            }
            $fieldname = '%%' . str_replace(' ', '\\s+', preg_quote(strtolower($details['fieldname']), '/')) . '%%';
            if (!is_null($details['data'])) {$content = preg_replace('/'. $fieldname . '/i', $details['data'], $content);}
		}
		        
		$content = str_ireplace('%lists%', '%%listname%%', $content);
		$content = str_ireplace(array('%basic:email%', '%email%'), '%%emailaddress%%', $content);

	    $basefields = array('emailaddress', 'confirmed', 'format', 'subscribedate', 'listname', 'ipaddress', 'companyname', 'companyaddress', 'companyphone');
		foreach ($basefields as $p => $field) {
			$field = strtolower($field);

			if (!isset($subscriberinfo[$field])) {
				continue;
			}
			$fielddata = $subscriberinfo[$field];
			if ($field == 'subscribedate') {
				$fielddata = date(GetLang('DateFormat'), $fielddata);
			}
			$fieldname = '%%' . $field . '%%';
			$content = str_replace($fieldname, $fielddata, $content);
			unset($fielddata);
		}

		$web_version_link = SENDSTUDIO_APPLICATION_URL . '/display.php?M=' . $subscriberinfo['subscriberid'];
		$web_version_link .= '&C=' . $subscriberinfo['confirmcode'];

		if (isset($subscriberinfo['listid'])) {
			$web_version_link .= '&L=' . $subscriberinfo['listid'];
		}

		if (isset($subscriberinfo['newsletter'])) {
			$web_version_link .= '&N=' . $subscriberinfo['newsletter'];
		}

		if (isset($subscriberinfo['autoresponder'])) {
			$web_version_link .= '&A=' . $subscriberinfo['autoresponder'];
		}

		$content = str_ireplace('%%webversion%%', $web_version_link, $content);

		$mailinglist_archives_link = SENDSTUDIO_APPLICATION_URL . '/rss.php?M=' . $subscriberinfo['subscriberid'];
		$mailinglist_archives_link .= '&C=' . $subscriberinfo['confirmcode'];

		if (isset($subscriberinfo['listid'])) {
			$mailinglist_archives_link .= '&L=' . $subscriberinfo['listid'];
		}

		$content = str_ireplace('%%mailinglistarchive%%', $mailinglist_archives_link, $content);


		$confirmlink = SENDSTUDIO_APPLICATION_URL . '/confirm.php?E=' . $subscriberinfo['emailaddress'];
		if (isset($subscriberinfo['listid'])) {
			$confirmlink .= '&L=' . $subscriberinfo['listid'];
		}

		$confirmlink .= '&C=' . $subscriberinfo['confirmcode'];

		$content = str_replace(array('%%confirmlink%%', '%%CONFIRMLINK%%'), $confirmlink, $content);

		$content = str_replace(array('%basic:confirmlink%', '%BASIC:CONFIRMLINK%'), $confirmlink, $content);

		$unsubscribelink = SENDSTUDIO_APPLICATION_URL . '/unsubscribe.php?';  

        $linkdata = "&M={$subscriberinfo['subscriberid']}";  

		// so we can track where someone unsubscribed from, we'll add that into the url.
		if (isset($subscriberinfo['newsletter'])) {
			$linkdata .= "&N={$subscriberinfo['statid']}";
		}

		if (isset($subscriberinfo['autoresponder'])) {
			$linkdata .= "&A={$subscriberinfo['statid']}";
		}

		if (isset($subscriberinfo['listid'])) {
            $linkdata .= "&L={$subscriberinfo['listid']}";		    		      		   			
		}        

		$linkdata .= "&C={$subscriberinfo['confirmcode']}";
        
        $unsubscribelink .= $linkdata;
        
        $content = str_ireplace('%%unsubscribelink%%', $unsubscribelink, $content);

		$replaceurl = SENDSTUDIO_APPLICATION_URL . '/modifydetails.php?' . $linkdata . '&F=$1';
		$content = preg_replace('~(?:http://)?%%modifydetails_(\d*?)%%/?~i', $replaceurl, $content);

		$replaceurl = SENDSTUDIO_APPLICATION_URL . '/sendfriend.php?' . $linkdata . '&F=$1';
		if (isset($subscriberinfo['newsletter'])) {
			$replaceurl .= '&i=' . $subscriberinfo['newsletter'];
		} elseif (isset($subscriberinfo['autoresponder'])) {
			$replaceurl .= '&i=' . $subscriberinfo['autoresponder'];
		}
		$content = preg_replace('~(?:http://)?%%sendfriend_([0-9]+)%%/?~i', $replaceurl, $content);

		return $content;
	}

	/**
	* GetCustomFieldType
	* Gets the custom field type for the fieldid passed in. This allows us to quickly use different searching / filtering for different field types.
	*
	* @see Subscriber_API::FetchSubscribers
	* @see Subscriber_API::GetSubscribers
	*
	* @return Mixed Returns false if the fieldtype can't be fetched, otherwise returns the fieldtype from the database.
	*/
	function GetCustomFieldType($fieldid=0)
	{
		$query = "SELECT fieldtype FROM " . SENDSTUDIO_TABLEPREFIX . "customfields WHERE fieldid='" . (int)$fieldid . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		$fieldtype = $this->Db->FetchOne($result, 'fieldtype');
		return strtolower($fieldtype);
	}

	/**
	* Save_Unsent_Recipient
	* Saves an unsent recipient to another database table so we have the option to re-send an email campaign to any subscribers who didn't get it the first time.
	*
	* ReasonCode should be one of the following:
	* 1 - subscriber problem (eg they have unsubscribed since the job was first started)
	* 10 - general email problem (eg the email was blank so instead of sending, the email was ignored)
	* 20 - general mail server error (sending through php mail() does not give us a reason why it couldn't be sent)
	* 30 - we are sending through an smtp server and an error occurred
	*
	* @param Int $recipient The recipient who didn't receive it the first time
	* @param Int $queueid The queueid they were on before when they didn't receive it the first time.
	* @param Int $reasoncode A reason code which we can use to work out why a message wasn't sent properly. This should be a 1, 5 or 10.
	* @param String $reason This message can also be used to work out what the problem was. This could contain an smtp server error for example.
	*
	* @see admin/language/language.php file for language descriptions of the error codes.
	*
	* @see Send_API::SendToRecipient
	*/
	function Save_Unsent_Recipient($recipient=0, $queueid=0, $reasoncode=0, $reason='')
	{
		$recipient = intval($recipient);
		$queueid = intval($queueid);
		$reasoncode = intval($reasoncode);
		$reason = $this->Db->Quote($reason);

		$tablePrefix = SENDSTUDIO_TABLEPREFIX;

		$query = "
			INSERT INTO {$tablePrefix}queues_unsent(recipient, queueid, reasoncode, reason)
			VALUES ({$recipient}, {$queueid}, {$reasoncode}, '{$reason}')
		";

		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		return true;
	}

	/**
	* Get_Unsent_Reasons
	* Returns an array of reasoncodes and number of recipients who couldn't be sent to for that particular reason.
	*
	* @param Int $queueid The queue we are looking for reasons in
	*
	* @see Save_Unsent_Recipient
	*
	* @return Array Returns a multi-dimensional array containing the reasoncode and the number who didn't get it for that reason.
	*/
	function Get_Unsent_Reasons($queueid=0)
	{
		$return = array();
		$query = "SELECT reasoncode, COUNT(recipient) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "queues_unsent WHERE queueid='" . $this->Db->Quote($queueid) . "' GROUP BY reasoncode";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* UnsentQueueSize
	* Gets the size of an unsent queue based in the queueid passed in.
	* This is used by newsletters when you resend something to work out how many are left to send to.
	*
	* @param Int $queueid The queueid to get the number of unsent recipients for.
	*
	* @return Int Returns the number of subscribers left in the queue.
	*/

	function UnsentQueueSize($queueid=0)
	{
		$query = "SELECT COUNT(recipient) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "queues_unsent WHERE queueid='" . $this->Db->Quote($queueid) . "'";
		return $this->Db->FetchOne($query, 'count');
	}

	/**
	 * Check whether the database can handle sub query
	 * @return Boolean Returns TRUE if YES, FALSE otherwise
	 */
	function _subqueryCapable()
	{
		if (is_null($this->_cacheSubqueryCapable)) {
			$this->_cacheSubqueryCapable = 	(SENDSTUDIO_DATABASE_TYPE == 'pgsql')
											|| (SENDSTUDIO_DATABASE_TYPE == 'mysql'
												&& version_compare(SENDSTUDIO_SYSTEM_DATABASE_VERSION, '4.1', '>='));
		}

		return $this->_cacheSubqueryCapable;
	}
}

?>
