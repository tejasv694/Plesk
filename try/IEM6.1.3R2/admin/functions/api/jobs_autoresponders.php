<?php
/**
* This is the autoresponder job system object. This handles everything for autoresponder sending.
*
* @version     $Id: jobs_autoresponders.php,v 1.58 2008/02/25 06:05:11 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Jobs
*/

/**
* Require the base job class.
*/
require_once(dirname(__FILE__) . '/jobs.php');

/**
* This class handles scheduled sending job processing.
*
* @package API
* @subpackage Jobs
*/
class Jobs_Autoresponders_API extends Jobs_API
{

	/**
	* Whether debug mode is on or not.
	*
	* @var Boolean
	*/
	var $Debug = false;

	/**
	* Where to log messages if debug is enabled.
	*
	* @var String
	*/
	var $LogFile = null;

	/**
	* The job status for the job we're running.
	*
	* @see FetchJob
	* @see ProcessJob
	* @see ActionJob
	*
	* @var Array
	*/
	var $jobstatus = null;

	/**
	* The job details for the job we're running. This is used to save database queries.
	*
	* @see FetchJob
	* @see ProcessJob
	* @see ActionJob
	*
	* @var Array
	*/
	var $jobdetails = array();

	/**
	* A reference to the autoresponder api. Saves us having to re-create it all the time.
	*
	* @see Jobs_Send_API
	* @see ProcessJob
	* @see ActionJob
	*
	* @var Object
	*/
	var $autoresponder_api = null;

	/**
	* Used to remember the autoresponder we're sending. Saves constantly loading it from the database.
	*
	* @see ActionJob
	*
	* @var Array
	*/
	var $autoresponder = array();

	/**
	* A count of the emails we have sent (used for reporting).
	*
	* @var Int
	*/
	var $emailssent = 0;

	/**
	* Which list we're currently processing.
	*
	* @var Int
	*/
	var $listid = 0;

	/**
	* Current time (used to work out how long someone has been subscribed for).
	*
	* @var Int
	*/
	var $currenttime = 0;

	/**
	* The pause between sending each newsletter if applicable.
	*
	* @var Int
	*/
	var $userpause = null;

	/**
	* The current statistic we are looking at.
	*
	* @var Int
	*/
	var $statid = 0;

	/**
	 * Holds queueid that has been processed
	 * @var Array An array of queue id
	 */
	var $_queues_done = array();

	/**
	* Constructor
	* Calls the parent object to set up the database.
	* Sets up references to the email api, list api and subscriber api.
	*
	* @see Email_API
	* @see Lists_API
	* @see Subscriber_API
	* @see newsletter_api
	*
	* @see Jobs_API::Jobs_API
	*/
	function Jobs_Autoresponders_API()
	{
		$this->LogFile = TEMP_DIRECTORY . '/autoresponder_debug.'.getmypid().'.log';

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "---------------------\n", 3, $this->LogFile);
		}

		$this->currenttime = $this->GetServerTime();

		Jobs_API::Jobs_API(false);
		SendStudio_Functions::LoadLanguageFile('jobs_autoresponders');

		if ($this->Debug) {
			$this->Db->QueryLog = TEMP_DIRECTORY . '/autoresponder_queries.'.getmypid().'.log';
			$this->Db->LogQuery('-----------');

			$this->Db->TimeLog = TEMP_DIRECTORY . '/autoresponder_queries_timed.'.getmypid().'.log';
			$this->Db->TimeQuery('-----------', 0, 0);
		}

		if (is_null($this->Email_API)) {
			if (!class_exists('ss_email_api', false)) {
				require_once(dirname(__FILE__) . '/ss_email.php');
			}
			$email = new SS_Email_API();
			$this->Email_API = &$email;
		}

		if ($this->Debug) {
			$this->Email_API->Debug = true;
		}

		if (is_null($this->Subscriber_API)) {
			if (!class_exists('subscribers_api', false)) {
				require_once(dirname(__FILE__) . '/subscribers.php');
			}
			$subscribers = new Subscribers_API();
			$this->Subscriber_API = &$subscribers;
		}

		if (is_null($this->Lists_API)) {
			if (!class_exists('lists_api', false)) {
				require_once(dirname(__FILE__) . '/lists.php');
			}
			$lists = new Lists_API();
			$this->Lists_API = &$lists;
		}

		if (is_null($this->autoresponder_api)) {
			require_once(dirname(__FILE__) . '/autoresponders.php');
			$newsl = new Autoresponders_API();
			$this->autoresponder_api = &$newsl;
		}

		if (is_null($this->Stats_API)) {
			if (!class_exists('stats_api', false)) {
				require_once(dirname(__FILE__) . '/stats.php');
			}
			$statsapi = new Stats_API();
			$this->Stats_API = &$statsapi;
		}

		$this->_queues_done = array();
	}

	/**
	* FetchJob
	* Fetches the next autoresponder job from the queue that hasn't been looked at yet.
	*
	* @see ProcessJob
	*
	* @return False|Int If there is no next queue, this returns false. If there is a next queue, it returns the queueid for handing to the ProcessJob function
	*/
	function FetchJob()
	{

		if ($this->Debug) {
			$this->Db->LogQuery('###################');
			$this->Db->TimeQuery('###################', 0, 0);
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "###################\n", 3, $this->LogFile);
		}

		$timenow = $this->GetServerTime();
		$half_hour_ago = $timenow - (30 * 60);

		// get the queues we CAN'T process
		// This include autoresponder that are inactive or paused
		$queues_done_query = "
			SELECT queueid FROM (
				SELECT	queueid
				FROM	[|PREFIX|]jobs
				WHERE	jobtype='autoresponder'
						AND lastupdatetime > {$half_hour_ago}

				UNION ALL

				SELECT	queueid
				FROM	[|PREFIX|]autoresponders
				WHERE	active = 0
						OR pause <> 0
			) AS x
		";

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "queues done query: " . $queues_done_query . "\n", 3, $this->LogFile);
		}

		// Get queueid that has been processed so far
		if (empty($this->_queues_done)) {
			$this->_queues_done[] = '0';
		}
		$queue_done = implode(',', $this->_queues_done);

		/**
		* Get the next autoresponder queueid that hasn't been processed before.
		* We only need one 'queue' to process.
		* The next time this function will be called, the queue we just processed will be in the 'queues_done' array.
		*/
		$autoresponder_queueids_query = "
			SELECT queueid FROM " . SENDSTUDIO_TABLEPREFIX . "queues
			WHERE
				queueid NOT IN ({$queues_done_query})
				AND queueid NOT IN ({$queue_done})
				AND queuetype = 'autoresponder'
			LIMIT 1
		";


		$autoresponder_queueid = $this->Db->FetchOne($autoresponder_queueids_query);

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "got queueid " . $autoresponder_queueid . "\n", 3, $this->LogFile);
		}

		/**
		* If we can't find an autoresponder queueid, return.
		* We've processed all of the autoresponders we can for this run.
		*/
		if (!$autoresponder_queueid) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "unable to find queueid to process, returning.\n", 3, $this->LogFile);
			}
			return false;
		}

		// Add queueid to be processed to the "queue_done" variable
		array_push($this->_queues_done, $autoresponder_queueid);

		/**
		* From the queueid, work out which autoresponder it relates to so we can process it.
		*
		* We use coalesce to convert statid to 0 if there are no autoresponder statistics
		* and do the same for the 'hiddenby'.
		* ie the autoresponder has just been set up but there are no statistics available for it yet
		* or if the autoresponder stats have been 'hidden' (deleted), so we can create them again.
		*/
		$query = "SELECT a.queueid AS queueid, a.ownerid, a.autoresponderid, COALESCE(s.statid, 0) AS statid, COALESCE(s.hiddenby, 0) AS hiddenby FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders a LEFT OUTER JOIN " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders s ON s.autoresponderid=a.autoresponderid WHERE a.queueid='" . $autoresponder_queueid . "' ORDER BY s.hiddenby ASC LIMIT 1";

		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if (empty($row) || !$result) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "unable to find an autoresponder for queueid " . $autoresponder_queueid . ".\n", 3, $this->LogFile);
			}

			/**
			* If we got an autoresponder queueid, double check there are no autoresponders for that queueid.
			* Not sure how it happens but clean up the queues table if it does,
			* so when the autoresponder job runs next time it'll get another autoresponder to process
			* instead of getting stuck on a queueid that has no autoresponder attached to it.
			*/
			$query = "SELECT autoresponderid FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders WHERE queueid='" . $autoresponder_queueid . "' LIMIT 1";
			$autoresponder_id = $this->Db->FetchOne($query);
			if (!$autoresponder_id) {
				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "double checked there is no autoresponder for queueid " . $autoresponder_queueid . " so deleting the left over recipients.\n", 3, $this->LogFile);
				}
				$this->ClearQueue($autoresponder_queueid, 'autoresponder');
			} else {
				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "found an autoresponder (" . $autoresponder_id . ") for " . $autoresponder_queueid . "!\n", 3, $this->LogFile);
				}
			}
			return false;
		}

		$this->autoresponder_api->Load($row['autoresponderid']);

		if ($this->autoresponder_api->Get('ownerid') <= 0) {
			return false;
		}

		$listid = $this->autoresponder_api->Get('listid');

		if (!$listid) {
			return false;
		}

		$job_details = array(
			'Lists' => array($listid),
			'autorespondername' => $this->autoresponder_api->Get('name')
		);

		$jobcreated = $this->Create(
			'autoresponder',
			$timenow,
			$this->autoresponder_api->Get('ownerid'),
			$job_details,
			'autoresponder',
			$this->autoresponder_api->Get('autoresponderid'),
			$job_details['Lists'],
			$this->autoresponder_api->Get('ownerid')
		);

		$this->StartJob($jobcreated);

		$this->JobQueue($jobcreated, $row['queueid']);

		if (!$row['statid'] || $row['hiddenby'] > 0) {
			$autoresponderdetails = array(
				'autoresponderid' => $row['autoresponderid']
			);
			$statid = $this->Stats_API->SaveAutoresponderStats($autoresponderdetails);
		} else {
			$statid = $row['statid'];
		}
		$this->statid = $statid;

		$this->jobowner = $row['ownerid'];
		return $row['queueid'];
	}

	/**
	* ProcessJob
	* Processes an autoresponder queue
	* Checks a queue for duplicates, makes sure the queue is present and has recipients in it and then calls ActionJob to handle the rest
	*
	* @param Int $queueid Autoresponder queue to process. This is passed to ActionJob
	*
	* @see GetUser
	* @see RemoveDuplicatesInQueue
	* @see QueueSize
	* @see ActionJob
	* @see UnprocessQueue
	*
	* @return True Always returns true
	*/
	function ProcessJob($queueid=0)
	{
		$queueid = (int)$queueid;

		$this->user = GetUser($this->jobowner);
		IEM::userLogin($this->jobowner, false);

		$queuesize = $this->QueueSize($queueid, 'autoresponder');

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "queuesize: " . $queuesize . " for queueid " . $queueid . "\n", 3, $this->LogFile);
		}

		$jobid_query = "SELECT jobid FROM " . SENDSTUDIO_TABLEPREFIX . "jobs WHERE queueid='" . $queueid . "'";
		$jobid_result = $this->Db->Query($jobid_query);
		$jobid = $this->Db->FetchOne($jobid_result, 'jobid');

		if (!$jobid) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "no jobid (result " . gettype($jobid_result) . "; " . $jobid_result . ")" . "\n", 3, $this->LogFile);
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Returning" . "\n", 3, $this->LogFile);
			}
			IEM::userLogout();
			return true;
		}

		$timenow = $this->GetServerTime();
		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "jobs SET lastupdatetime=" . $timenow . " WHERE jobid='" . $jobid . "'";
		$update_job_result = $this->Db->Query($query);

		if ($queuesize <= 0) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Deleting job " . $jobid . " and then returning" . "\n", 3, $this->LogFile);
			}
			$this->Db->Query("DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "jobs WHERE jobid='" . $jobid . "'");
			IEM::userLogout();
			return true;
		}

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Actioning jobid " . $jobid . "\n", 3, $this->LogFile);
		}

		$finished = $this->ActionJob($queueid, $jobid);

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Finished: " . $finished . "\n", 3, $this->LogFile);
		}

		// we need to turn 'processed' emails back to normal so we can check them next time.
		$this->UnprocessQueue($queueid);

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Deleting jobid " . $jobid . "\n", 3, $this->LogFile);
		}

		$this->Db->Query('DELETE FROM ' . SENDSTUDIO_TABLEPREFIX . 'jobs_lists WHERE jobid=' . intval($jobid));
		$this->Db->Query("DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "jobs WHERE jobid=" . intval($jobid));

		IEM::userLogout();
		return true;
	}

	/**
	* ActionJob
	* This actually processes the autoresponder job and sends it out.
	* It makes sure the autoresponder queue is present (if not, returns false)
	* It makes sure the autoresponder exists and is active (if not, returns false)
	* It makes sure the autoresponder has some content (if not, returns false)
	* Once that is done, it removes any newly banned subscribers
	* Then removes any newly unsubscribe recipients
	* It makes sure the recipient is valid, is on the list and matches the criteria set by the autoresponder
	* Then it gets to work, constructing the email to get sent to the final recipient
	* Once all recipients for this queue have been looked at, it will "UnProcess" the queue to make everyone active again so next time the job runs, it can start all over again.
	* The only recipients that are treated this way are the ones who are before the autoresponder's "hours after subscription" timeframe.
	*
	* @param Int $queueid The queue to process.
	*
	* @see IsQueue
	* @see Autoresponders_API::Load
	* @see Autoresponders_API::Active
	* @see SendStudio_Functions::GetAttachments
	* @see Lists_API::Load
	* @see RemoveBannedEmails
	* @see RemoveUnsubscribedEmails
	* @see Email_API
	* @see FetchFromQueue
	* @see Subscribers_API::LoadSubscriberList
	* @see RemoveFromQueue
	* @see MarkAsProcessed
	* @see MatchCriteria
	*
	* @return Boolean Returns false if the queue can't be processed for any reason, otherwise it gets processed and returns true.
	*/
	function ActionJob($queueid=0, $jobid=0)
	{
		$queueid = (int)$queueid;
		if (!$this->IsQueue($queueid, 'autoresponder')) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "queueid (" . $queueid . ") is not valid" . "\n", 3, $this->LogFile);
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Returning" . "\n", 3, $this->LogFile);
			}
			return false;
		}

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "found queueid (" . $queueid . ")" . "\n", 3, $this->LogFile);
		}

		$query = "SELECT autoresponderid FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders WHERE queueid='" . $queueid . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "unable to find autoresponder for queue" . "\n", 3, $this->LogFile);
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Returning" . "\n", 3, $this->LogFile);
			}
			return false;
		}

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "got autoresponder result" . "\n", 3, $this->LogFile);
		}

		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "unable to find autoresponder" . "\n", 3, $this->LogFile);
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Returning" . "\n", 3, $this->LogFile);
			}
			return false;
		}

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "found autoresponder (" . $row['autoresponderid'] . ")" . "\n", 3, $this->LogFile);
		}

		$this->autoresponder_api->Load($row['autoresponderid']);
		if ($this->autoresponder_api->autoresponderid <= 0) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "unable to find autoresponder" . "\n", 3, $this->LogFile);
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Returning" . "\n", 3, $this->LogFile);
			}
			return false;
		}

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "loaded autoresponder (" . $row['autoresponderid'] . ")" . "\n", 3, $this->LogFile);
		}

		// if the autoresponder isn't active, don't do anything.
		if (!$this->autoresponder_api->Active()) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "autoresponder not active" . "\n", 3, $this->LogFile);
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Returning" . "\n", 3, $this->LogFile);
			}
			return false;
		}

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "autoresponder is active" . "\n", 3, $this->LogFile);
		}

		// if the autoresponder is empty, don't do anything.
		if (empty($this->autoresponder_api->textbody) && empty($this->autoresponder_api->htmlbody)) {
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "autoresponder bodies are empty" . "\n", 3, $this->LogFile);
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "Returning" . "\n", 3, $this->LogFile);
			}
			return false;
		}

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "autoresponder has text &/or html body" . "\n", 3, $this->LogFile);
		}

		$this->autoresponder['Attachments'] = SendStudio_Functions::GetAttachments('autoresponders', $this->autoresponder_api->Get('autoresponderid'), true);

		$this->listid = $this->autoresponder_api->Get('listid');

		$this->Lists_API->Load($this->listid);
		$listname = $this->Lists_API->Get('name');

		$search_criteria = $this->autoresponder_api->Get('searchcriteria');

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "search_criteria: " . array_contents($search_criteria) . "\n", 3, $this->LogFile);
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$this->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
		}

		// double check there are no duplicates in the autoresponder queue.
		$this->RemoveDuplicatesInQueue($queueid, 'autoresponder', $this->listid);

		// remove any that have been newly banned.
		$this->RemoveBannedEmails($this->listid, $queueid, 'autoresponder');

		// remove any that have unsubscribed.
		$this->RemoveUnsubscribedEmails($this->listid, $queueid, 'autoresponder');

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$this->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
		}

		$this->Email_API->ForgetEmail();

		$this->Email_API->Set('statid', $this->statid);
		$this->Email_API->Set('listids', array($this->listid));

		$this->Email_API->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

		if ($this->user->smtpserver) {
			$this->Email_API->SetSmtp($this->user->smtpserver, $this->user->smtpusername, $this->user->smtppassword, $this->user->smtpport);
		}

		if (is_null($this->userpause)) {
			$pause = $pausetime = 0;
			if ($this->user->perhour > 0) {
				$pause = $this->user->perhour;
			}

			// in case the system rate is less than the user rate, lower it.
			if (SENDSTUDIO_MAXHOURLYRATE > 0) {
				if ($pause == 0) {
					$pause = SENDSTUDIO_MAXHOURLYRATE;
				} else {
					$pause = min($pause, SENDSTUDIO_MAXHOURLYRATE);
				}
			}

			if ($pause > 0) {
				$pausetime = 3600 / $pause;
			}
			$this->userpause = $pausetime;
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "userpause is set to " . $this->userpause . "\n", 3, $this->LogFile);
			}
		}

		if ($this->autoresponder_api->Get('tracklinks')) {
			$this->Email_API->TrackLinks(true);
		}

		if ($this->autoresponder_api->Get('trackopens')) {
			$this->Email_API->TrackOpens(true);
		}

		if (SENDSTUDIO_FORCE_UNSUBLINK) {
			$this->Email_API->ForceLinkChecks(true);
		}

		$this->Email_API->Set('CharSet', $this->autoresponder_api->Get('charset'));

		if (!SENDSTUDIO_SAFE_MODE) {
			$this->Email_API->Set('imagedir', TEMP_DIRECTORY . '/autoresponder.' . $queueid);
		} else {
			$this->Email_API->Set('imagedir', TEMP_DIRECTORY . '/autoresponder');
		}

		// clear out the attachments just to be safe.
		$this->Email_API->ClearAttachments();

		if ($this->autoresponder['Attachments']) {
			$path = $this->autoresponder['Attachments']['path'];
			$files = $this->autoresponder['Attachments']['filelist'];
			foreach ($files as $p => $file) {
				$this->Email_API->AddAttachment($path . '/' . $file);
			}
		}

		$this->Email_API->Set('Subject', $this->autoresponder_api->Get('subject'));

		$this->Email_API->Set('FromName', $this->autoresponder_api->Get('sendfromname'));
		$this->Email_API->Set('FromAddress', $this->autoresponder_api->Get('sendfromemail'));
		$this->Email_API->Set('ReplyTo', $this->autoresponder_api->Get('replytoemail'));
		$this->Email_API->Set('BounceAddress', $this->autoresponder_api->Get('bounceemail'));

		$this->Email_API->Set('EmbedImages', $this->autoresponder_api->Get('embedimages'));

		$auto_format = $this->autoresponder_api->Get('format');

		$this->Email_API->Set('Multipart', false);

		if ($auto_format == 'b' || $auto_format == 't') {
			if ($this->autoresponder_api->GetBody('text')) {
				$this->Email_API->AddBody('text', $this->autoresponder_api->GetBody('text'));
				$this->Email_API->AppendBody('text', $this->user->Get('textfooter'));
				$this->Email_API->AppendBody('text', stripslashes(SENDSTUDIO_TEXTFOOTER));
			}
		}

		if ($auto_format == 'b' || $auto_format == 'h') {
			if ($this->autoresponder_api->GetBody('html')) {
				$this->Email_API->AddBody('html', $this->autoresponder_api->GetBody('html'));
				$this->Email_API->AppendBody('html', $this->user->Get('htmlfooter'));
				$this->Email_API->AppendBody('html', stripslashes(SENDSTUDIO_HTMLFOOTER));
			}
		}

		if ($auto_format == 'b' && $this->autoresponder_api->Get('multipart')) {
			if ($this->autoresponder_api->GetBody('text') && $this->autoresponder_api->GetBody('html')) {
				$sent_format = 'm';
				$this->Email_API->Set('Multipart', true);
			} else {
				$this->Email_API->Set('Multipart', false);
			}
		}

		$custom_fields_to_replace = $this->Email_API->GetCustomFields();

		$personalize_customfields = array();

		$firstname_field = $this->autoresponder_api->Get('to_firstname');
		if ($firstname_field) {
			$personalize_customfields[] = $firstname_field;
		}

		$lastname_field = $this->autoresponder_api->Get('to_lastname');
		if ($lastname_field) {
			$personalize_customfields[] = $lastname_field;
		}

		$personalize_customfields = array_unique($personalize_customfields);

		// Current available credit returns TRUE if credit is unlimited
		$credit_available = true;

		if (SENDSTUDIO_CREDIT_INCLUDE_AUTORESPONDERS) {
			$credit_available = API_USERS::creditAvailableTotal($this->autoresponder_api->Get('ownerid'));
		}

		$credit_used = 0;
		$emails_sent = 0;
		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "checking queue for deleted recipients" . "\n", 3, $this->LogFile);
		}		
		$recipient_to_check = array();
		$query = "SELECT recipient FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid={$queueid}";
		$result = $this->Db->Query($query);
		if (!$result) {
			trigger_error(mysql_error());
			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . mysql_error() . "\n", 3, $this->LogFile);
			}
		}
		while ($row = $this->Db->Fetch($result)) {
			$recipient_to_check[] = $row['recipient'];
		}

		foreach($recipient_to_check as $val)
		{
			$query = "SELECT COUNT(subscriberid) FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE subscriberid={$val}";
			$exist = $this->Db->FetchOne($query);
			if((int)$exist == 0){
				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "subscriber id {$val} no longer exists" . "\n", 3, $this->LogFile);
				}
				$this->SaveAutoresponderSentStatus(false, $this->autoresponder_api->Get('autoresponderid'), $val, 'doesntexist');
				$this->MarkAsSent($queueid, $val);

			}
			
		}
		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "fetching autoresponder recipients" . "\n", 3, $this->LogFile);
		}
		while ($recipients = $this->FetchFromQueue($queueid, 'autoresponder', 1, 500, $this->autoresponder_api->Get('hoursaftersubscription'))) {
			if (empty($recipients)) {
				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "no more recipients" . "\n", 3, $this->LogFile);
				}

				break;
			}

			if ($this->Debug) {
				error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "found " . sizeof($recipients) . " recipients" . "\n", 3, $this->LogFile);
			}

			
            		$tempCustomField = $this->SetupDynamicContentFields($recipients, $this->listid, true);
            		$custom_fields_to_replace = array_unique(array_merge($tempCustomField, $custom_fields_to_replace));
			$all_customfields = $this->SetupCustomFields($this->listid, $custom_fields_to_replace, $recipients, $personalize_customfields);

			foreach ($recipients as $p => $recipientid) {
				$subscriberinfo = $this->Subscriber_API->LoadSubscriberList($recipientid, $this->listid, true, false, false);

				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . "; subscriberinfo: " . array_contents($subscriberinfo) . "\n", 3, $this->LogFile);
				}

				// if they don't match the search criteria, remember it for later and don't sent it.
				if (empty($subscriberinfo) || !isset($subscriberinfo['subscribedate'])) {
					if ($this->Debug) {
						error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . "; subscriber info is empty or date is not set" . "\n", 3, $this->LogFile);
					}

					$this->SaveAutoresponderSentStatus(false, $this->autoresponder_api->Get('autoresponderid'), $recipientid, 'unsubscribed');


					$this->MarkAsSent($queueid, $recipientid);
					continue;
				}

				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . " has email address " . $subscriberinfo['emailaddress'] . "\n", 3, $this->LogFile);
				}

				// work out how long they have been subscribed for.
				$hours_subscribed = floor(($this->currenttime - $subscriberinfo['subscribedate']) / 3600);

				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . " subscribed for " . $hours_subscribed . "\n", 3, $this->LogFile);
				}

				// not long enough? Go to the next one.
				if ($hours_subscribed < $this->autoresponder_api->Get('hoursaftersubscription')) {
					if ($this->Debug) {
						error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . "; not time to send the autoresponder yet (subscribed for " . $hours_subscribed . "; hours set to: " . $this->autoresponder_api->Get('hoursaftersubscription') . ")" . "\n", 3, $this->LogFile);
					}
					$this->MarkAsProcessed($queueid, 'autoresponder', $recipientid);
					continue;
				}

				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . " has been subscribed for long enough" . "\n", 3, $this->LogFile);
				}

				// if they don't match the search criteria, remember it for later and don't send it.
				if (!$this->MatchCriteria($search_criteria, $recipientid)) {
					$this->SaveAutoresponderSentStatus(false, $this->autoresponder_api->Get('autoresponderid'), $recipientid, 'search_criteria');
					if ($this->Debug) {
						error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . "; dont meet search criteria (" . array_contents($search_criteria) . ")" . "\n", 3, $this->LogFile);
					}
					$this->MarkAsSent($queueid, $recipientid);
					continue;
				}

				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "does meet search criteria" . "\n", 3, $this->LogFile);
				}

				// If user don't have enough credit, discard queue record
				if ($credit_available !== true && ($credit_available <= 0 || $credit_available <= $credit_used)) {
					if ($this->Debug) {
						error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\tUser (userid: {$this->autoresponder->ownerid}) does not have enough credit... Will remove subscriber (id: {$recipientid}) from queue table.\n", 3, $this->LogFile);
					}

					$this->SaveAutoresponderSentStatus(false, $this->autoresponder_api->Get('autoresponderid'), $recipientid, 'icredit');

					$this->MarkAsSent($queueid, $recipientid);
					continue;
				}

				$this->Email_API->ClearRecipients();

				$subscriberinfo['ipaddress'] = $subscriberinfo['confirmip'];
				if (!$subscriberinfo['ipaddress']) {
					$subscriberinfo['ipaddress'] = $subscriberinfo['requestip'];
				}
				if (!$subscriberinfo['ipaddress']) {
					$subscriberinfo['ipaddress'] = '';
				}

				$subscriberinfo['CustomFields'] = array();

				if (!empty($all_customfields) && isset($all_customfields[$recipientid])) {
					$subscriberinfo['CustomFields'] = $all_customfields[$recipientid];
				} else {
					/**
					* If the subscriber has no custom fields coming from the database, then set up blank placeholders.
					* If they have no custom fields in the database, they have no records in the 'all_customfields' array - so we need to fill it up with blank entries.
					*/
					foreach ($custom_fields_to_replace as $fieldid => $fieldname) {
						$subscriberinfo['CustomFields'][] = array(
							'fieldid' => $fieldid,
							'fieldname' => $fieldname,
							'fieldtype' => 'text',
							'defaultvalue' => '',
							'fieldsettings' => '',
							'subscriberid' => $recipientid,
							'data' => ''
						);
					}
				}

				$name = false;

				$firstname_field = $this->autoresponder_api->Get('to_firstname');
				if ($firstname_field) {
					foreach ($subscriberinfo['CustomFields'] as $p => $details) {
						if ($details['fieldid'] == $firstname_field && $details['data'] != '') {
							$name = $details['data'];
							break;
						}
					}
				}

				$lastname_field = $this->autoresponder_api->Get('to_lastname');
				if ($lastname_field) {
					foreach ($subscriberinfo['CustomFields'] as $p => $details) {
						if ($details['fieldid'] == $lastname_field && $details['data'] != '') {
							$name .= ' ' . $details['data'];
							break;
						}
					}
				}

				$this->Email_API->AddRecipient($subscriberinfo['emailaddress'], $name, $subscriberinfo['format'], $subscriberinfo['subscriberid']);

				$subscriberinfo['listid'] = $this->listid;
				$subscriberinfo['listname'] = $listname;
				$subscriberinfo['autoresponder'] = $this->autoresponder_api->Get('autoresponderid');
				$subscriberinfo['statid'] = $this->statid;

				$subscriberinfo['companyname'] = $this->Lists_API->Get('companyname');
				$subscriberinfo['companyaddress'] = $this->Lists_API->Get('companyaddress');
				$subscriberinfo['companyphone'] = $this->Lists_API->Get('companyphone');

				$this->Email_API->AddCustomFieldInfo($subscriberinfo['emailaddress'], $subscriberinfo);
				$this->Email_API->AddDynamicContentInfo($this->dynamic_content_replacement);

				$mail_result = $this->Email_API->Send(true, true);

				if ($this->Debug) {
					error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "recipientid: " . $recipientid . "; mail result: " . array_contents($mail_result) . "\n", 3, $this->LogFile);
				}

				if (!$this->Email_API->Get('Multipart')) {
					$sent_format = $subscriberinfo['format'];
				}

				$last_sent_error = false;
				if ($mail_result['success'] > 0) {
					$this->Stats_API->UpdateRecipient($this->statid, $sent_format, 'a');
					$this->SaveAutoresponderSentStatus(true, $this->autoresponder_api->Get('autoresponderid'), $recipientid);
				} else {
					$last_sent_error = true;
					$error_reason = 'mail_error';
					reset($mail_result['fail']);
					$error = current($mail_result['fail']);
					if ($error[1] == 'BlankEmail') {
						$error_reason = 'blankemail_' . $sent_format;
					}
					$this->SaveAutoresponderSentStatus(false, $this->autoresponder_api->Get('autoresponderid'), $recipientid, $error_reason);
				}

				/**
				* Trigger Event
				*/
					$tempEventData = new EventData_IEM_JOBSAUTORESPONDERAPI_ACTIONJOB();
					$tempEventData->emailsent = ($mail_result['success'] > 0);
					$tempEventData->subscriberinfo = &$subscriberinfo;
					$tempEventData->autoresponder = &$this->autoresponder_api;
					$tempEventData->trigger();
					unset($tempEventData);
				/**
				* ------
				*/

				$emails_sent++;

				$this->MarkAsSent($queueid, $recipientid);

				/**
				 * Work out whether we need to update the job 'lastupdatetime'
				 * - If we pause between each email, we should update it
				 * - If we are not pausing between each email, we should update it every 10 emails
				*/
				$update_job_time = false;
				if ($this->userpause > 0) {
					$update_job_time = true;
				} else {
					if (($emails_sent % 10) == 0) {
						$update_job_time = true;
					}
				}

				if ($update_job_time) {
					$timenow = $this->GetServerTime();
					$query = "UPDATE [|PREFIX|]jobs SET lastupdatetime = {$timenow} WHERE jobid = {$jobid}";
					$update_job_result = $this->Db->Query($query);

					$emails_sent = 0;
				}

				// ----- Record credit usage every 100 emails sent when there aren't any sending error
					if (!$last_sent_error && $credit_available !== true) {
						++$credit_used;
						if ($credit_used >= 100) {
							$ownerid = $this->autoresponder_api->Get('ownerid');
							$status = API_USERS::creditUse($ownerid, API_USERS::CREDIT_USAGETYPE_SENDAUTORESPONDER, $credit_used, $jobid, $this->statid);
							if (!$status) {
								trigger_error(__CLASS__ . '::' . __METHOD__ . " -- Cannot record usage -- userid = {$ownerid}, credit_used = {$credit_used}, job_id = {$jobid}, stat_id = {$this->statid}", E_USER_NOTICE);
								return false;
							}

							$credit_used = 0;
						}
					}
				// -----

				// do we need to pause between each email?
				if ($this->userpause > 0) {
					if ($this->userpause > 0 && $this->userpause < 1) {
						$p = ceil($this->userpause * 1000000);
						usleep($p);
					} else {
						$p = ceil($this->userpause);
						sleep($p);
					}
				} // end if we need to pause.
			} // end foreach recipient
		} // end while loop (to go through each subscriber in the queue).

		// ----- If there are leftover credits that haven't been recorded, then record it here
			if ($credit_available !== true && $credit_used > 0) {
				$ownerid = $this->autoresponder_api->Get('ownerid');
				$status = API_USERS::creditUse($ownerid, API_USERS::CREDIT_USAGETYPE_SENDAUTORESPONDER, $credit_used, $jobid, $this->statid);
				if (!$status) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . " -- Cannot record usage -- userid = {$ownerid}, credit_used = {$credit_used}, job_id = {$jobid}, stat_id = {$this->statid}", E_USER_NOTICE);
					return false;
				}
			}
		// -----

		$this->Email_API->CleanupImages();

		// logout of the smtp server. the email class handles whether it's actually using an smtp server or not.
		$this->Email_API->SMTP_Logout();

		// reset the 'pause' counter.
		$this->userpause = null;
		return true;
	}

	/**
	* MatchCriteria
	* This will make sure the recipient passed in matches the search criteria information which could possibly include custom fields.
	* It creates an array to pass off to the Subscribers_API::GetSubscribers method
	*
	* @param Array $search_criteria Search criteria of the autoresonder. This is reconstructed for passing to the GetSubscribers method in Subscribers_API
	* @param Int $recipient The specific recipient we're checking
	*
	* @see Subscribers_API::GetSubscribers
	*
	* @return Boolean Returns true if the recipient matches the criteria passed in, else false.
	*/
	function MatchCriteria($search_criteria=array(), $recipient=0)
	{
		$searchinfo = array('List' => $this->listid);

		if (isset($search_criteria['customfields'])) {
			$searchinfo['CustomFields'] = $search_criteria['customfields'];
		}

		if (isset($search_criteria['format'])) {
			// only need to include the format IF you are restricting to subscribers.
			// using "either format" uses '-1'
			// sending to 'h'tml subscribers only or 't'ext subscribers only should restrict the matching.
			if ($search_criteria['format'] != '-1') {
				$searchinfo['Format'] = $search_criteria['format'];
			}
		}

		if (isset($search_criteria['emailaddress'])) {
			$searchinfo['Email'] = $search_criteria['emailaddress'];
		}

		if (isset($search_criteria['status'])) {
			$searchinfo['Status'] = $search_criteria['status'];
		}

		if (isset($search_criteria['confirmed'])) {
			$searchinfo['Confirmed'] = $search_criteria['confirmed'];
		}

		if (isset($search_criteria['link'])) {
			$searchinfo['Link'] = $search_criteria['link'];

			if (isset($search_criteria['linktype'])) {
				$searchinfo['LinkType'] = $search_criteria['linktype'];
			}
		}

		if (isset($search_criteria['newsletter'])) {
			$searchinfo['Newsletter'] = $search_criteria['newsletter'];

			if (isset($search_criteria['opentype'])) {
				$searchinfo['OpenType'] = $search_criteria['opentype'];
			}
		}

		if (isset($search_criteria['search_options'])) {
			$searchinfo['Search_Options'] = $search_criteria['search_options'];
		}

		$searchinfo['Subscriber'] = $recipient;

		$check = $this->Subscriber_API->GetSubscribers($searchinfo, array(), true);

		if ($this->Debug) {
			error_log(time() . "\t" . __FILE__ . "\t" . __LINE__ . "\t" . "checking search critiera (" . array_contents($searchinfo) . ") for recipient (" . $recipient . ") returned " . $check . "\n", 3, $this->LogFile);
		}

		if ($check == 1) {
			return true;
		}

		return false;
	}

	/**
	* UnprocessQueue
	* This marks all recipients left in the queue as unprocessed. This allows the autoresponder to run again next time and reprocess everything.
	*
	* @param Int $queueid The queue to 'unprocess'.
	*
	* @see UnprocessQueue
	*
	* @return Boolean Returns false if there is no queueid passed in or if the unprocess query failed. If it works, this will return true.
	*/
	function UnprocessQueue($queueid=0)
	{
		$queueid = (int)$queueid;
		if ($queueid <= 0) {
			return false;
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE sent='1' AND queueid='" . $queueid . "' AND queuetype='autoresponder' AND processed='1'";
		$result = $this->Db->Query($query);

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "queues SET processed='0' WHERE queueid='" . $queueid . "' AND queuetype='autoresponder' AND sent='0'";
		$result = $this->Db->Query($query);
		return $result;
	}


	/**
	* MarkAsSent
	* Marks recipients as processed & sent in the queue. An update is usually 'cheaper' in database terms to do than a delete so that's what this does.
	*
	* @param Int $queueid The queueid you're processing recipients for.
	* @param Mixed $recipients A list of recipients to process in the queue. This can be an array or a singular recipient id.
	*
	* @return Boolean Returns true if the query worked, returns false if there was a problem with the query.
	*/
	function MarkAsSent($queueid=0, $recipients=array())
	{
		if (!is_array($recipients)) {
			$recipients = array($recipients);
		}

		$recipients = $this->CheckIntVars($recipients);

		// stops the query from failing.
		if (empty($recipients)) {
			return false;
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "queues SET processed='1', sent='1' WHERE queueid='" . $this->Db->Quote($queueid) . "' AND queuetype='autoresponder' AND recipient IN (" . implode(',', $recipients) . ")";
		$result = $this->Db->Query($query);
		if ($result) {
			return true;
		}
		return false;
	}

	/**
	 * SetupDynamicContentFields
         * Loads the Dynamic Content to be replaced and its content
	 *
	 * @param Array $recipients The recipient data to load Dynamic content field data for.
	 * @param Int/Array $lists The list where the data will be retrieved.
	 *
	 * @return Mix Returns an array of custom fields, only if the param $returnCustomFields are true
	 */
        function SetupDynamicContentFields($recipients=array(), $lists = 0, $returnCustomFields = false) {
            $lists = (is_array($lists)) ? $lists : array($lists);
            $customFields = $this->Lists_API->GetCustomFields($lists);
            $allCustomFields = array();
            $allSubscribersInfo = array();
            foreach ($customFields as $customFieldEntry) {
                $allCustomFields[] = $customFieldEntry['name'];
            }
            $listCustomFields = $this->Subscriber_API->GetAllSubscriberCustomFields($lists,$allCustomFields, $recipients);
            $counter = 0;
            foreach ($listCustomFields as $subscriberId => $customFieldEntry) {
                $allSubscribersInfo[$counter] = $this->Subscriber_API->LoadSubscriberBasicInformation($subscriberId, $lists);
                $allSubscribersInfo[$counter]['CustomFields'] = $customFieldEntry;
                $counter++;
            }
            $dctEvent =new EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT();
            $dctEvent->lists = $lists;
            $dctEvent->info = $allSubscribersInfo;
            $dctEvent->trigger();
            $this->dynamic_content_replacement = $dctEvent->contentTobeReplaced;
            if ($returnCustomFields) {
            	return $allCustomFields;
            }
        }

	/**
	 * SetupCustomFields
	 * This loads a bunch of custom fields in to memory for an array of subscriber id's and then changes their format in bulk.
	 * For example, change a "dropdown" key to a dropdown value.
	 * Same for checkboxes and radio buttons.
	 * For dates, re-organise the date field into the right order - dd/mm/yy or mm/dd/yy
	 *
	 * @param Int $listid The list the subscribers are on. Passed to the GetAllSubscriberCustomFields method to restrict the search/loading.
	 * @param Array $custom_fields_to_replace The custom field id's to load/replace
	 * @param Array $recipients The recipients to load the custom fields for.
	 * @param Array $personalize_customfields The "to" custom field id's (if any).
	 *
	 * @see Jobs_Send::SetupCustomFields
	 * @uses Subscriber_API::GetAllSubscriberCustomFields
	 *
	 * @return Array Returns a multidimensional array containing all subscribers and all of their custom field values.
	 */
	function SetupCustomFields($listid=0, $custom_fields_to_replace=array(), $recipients=array(), $personalize_customfields=array())
	{
		$all_customfields = $this->Subscriber_API->GetAllSubscriberCustomFields($listid, $custom_fields_to_replace, $recipients, $personalize_customfields);

		// rather than using the customfield api to do all of this (which would require loading each item separately, then running GetRealValue) we'll do the "dodgy" and do it all here instead.

		foreach ($all_customfields as $subscriberid => $customfield_list) {
			$fields_found = array();
			foreach ($customfield_list as $p => $details) {

				$fields_found[] = $details['fieldname'];
				if ($details['data'] === null || $details['data'] == '') {
					continue;
				}

				$fieldid = $details['fieldid'];

				switch ($details['fieldtype']) {
					case 'date':
						list($dd, $mm, $yy) = explode('/', $details['data']);
						$real_order = array();
						$settings = unserialize($details['fieldsettings']);
						$field_order = array_slice($settings['Key'], 0, 3);
						foreach ($field_order as $posp => $order) {
							switch ($order) {
								case 'day':
									$real_order[] = $dd;
								break;
								case 'month':
									$real_order[] = $mm;
								break;
								case 'year':
									$real_order[] = $yy;
								break;
							}
						}
						$all_customfields[$subscriberid][$p]['data'] = implode('/', $real_order);
					break;

					case 'dropdown':
						$settings = unserialize($details['fieldsettings']);
						$data = $details['data'];
						$pos = array_search($data, $settings['Key']);
						if (is_numeric($pos)) {
							$all_customfields[$subscriberid][$p]['data'] = $settings['Value'][$pos];
						}
					break;

					case 'radiobutton':
						$settings = unserialize($details['fieldsettings']);
						$data = $details['data'];
						$pos = array_search($data, $settings['Key']);

						if (is_numeric($pos)) {
							$all_customfields[$subscriberid][$p]['data'] = $settings['Value'][$pos];
						}
					break;

					case 'checkbox':
						$settings = unserialize($details['fieldsettings']);
						$data = $details['data'];

						$value = unserialize($data);

						$return_value = array();

						foreach ($settings['Key'] as $pos => $key) {
							if (in_array($key, $value)) {
								$return_value[] = $settings['Value'][$pos];
							}
						}

						$data = implode(',', $return_value);

						$all_customfields[$subscriberid][$p]['data'] = $data;
					break;
				}
			}

			if (!isset($all_customfields[$subscriberid])) {
				$all_customfields[$subscriberid] = array();
			}

			/**
			* If the subscriber only has some custom fields from the database, make sure we fill in the rest with blank entries.
			* Otherwise in some cases they could end up with custom fields not being replaced.
			* Eg if they have one custom field entry, then they skip the check around line 385.
			*
			* Makes for double handling of the custom fields but this should ensure you don't get half-managed custom fields.
			*/
			foreach ($custom_fields_to_replace as $fieldid => $fieldname) {
				if (!in_array($fieldname, $fields_found)) {
					$all_customfields[$subscriberid][] = array (
						'fieldid' => $fieldid,
						'fieldname' => $fieldname,
						'fieldtype' => 'text',
						'defaultvalue' => '',
						'fieldsettings' => '',
						'subscriberid' => $subscriberid,
						'data' => ''
					);
				}
			}
		}
		return $all_customfields;
	}

	/**
	* SaveAutoresponderSentStatus
	* When a subscriber is emailed a particular autoresponder, record whether they were sent an autoresponder and if not, why not.
	* This will be shown on the autoresponder statistics page for people to look at why an email address wasn't sent something.
	*
	* @param Boolean $sent_status Whether the email was sent ok or not according to the email class
	* @param Int $autoresponderid The autoresponderid the subscriber was just sent
	* @param Int $recipientid The recipient who was just processed
	* @param String $reason The reason they were not emailed. This is either 'search_criteria' if they didn't meet the custom field filtering, or 'mail_failed' if the mail class didn't send the email.
	*
	* @return Void Doesn't return anything.
	*/
	function SaveAutoresponderSentStatus($sent_status=true, $autoresponderid=0, $recipientid=0, $reason='')
	{
		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders_recipients(send_status, statid, autoresponderid, recipient, reason, sendtime) VALUES ('" . (int)$sent_status . "', '" .  (int)$this->statid . "', '" . (int)$autoresponderid . "', '" . (int)$recipientid . "', '" . $this->Db->Quote($reason) . "', '" . $this->GetServerTime() . "')";
		$result = $this->Db->Query($query);
	}

	/**
	 * PruneAutoJobs
	 * Prune autoresponder jobs records in the 'jobs' and 'jobs_lists' table for unused records.
	 *
	 * Pruning these entries will keep the jobs table clean,
	 * and make the database more responsive, as it does NOT need
	 * to consider any autoresponder records that are already obselete.
	 *
	 * @return Void Does not return anything
	 */
	function PruneAutoJobs()
	{
		$half_hour_ago = $this->GetServerTime() - (30 * 60);

		$query = 'DELETE FROM ' . SENDSTUDIO_TABLEPREFIX . "jobs WHERE jobtype='autoresponder' AND lastupdatetime < {$half_hour_ago}";
		$status = $this->Db->Query($query);
		if ($status === false) {
			trigger_error('Cannot prune job tables', E_USER_NOTICE);
		}

		// Also prune the jobs_lists table
		if ($status) {
			// MySQL have a "Dependent Subquery" bug which will degrade performance when used.
			// This is a workaround that will force MySQL to evaluate "Dependant Subquery" first before moving on the the main query (as it should)
			$query = 'DELETE FROM ' . SENDSTUDIO_TABLEPREFIX . 'jobs_lists WHERE jobid NOT IN ( SELECT jobid FROM (SELECT jobid FROM ' . SENDSTUDIO_TABLEPREFIX . 'jobs) AS x )';

			$status = $this->Db->Query($query);
			if ($status === false) {
				trigger_error('Cannot delete records from jobs list table.', E_USER_NOTICE);
			}
		}
	}
}
