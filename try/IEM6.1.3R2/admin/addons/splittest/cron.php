<?php
/**
 * This file handles cron sending of split test campaigns.
 * It includes the functionality to return a list of 'waiting' jobs that are ready to send.
 * It also includes the class which actually processes the split test campaigns and does all of the sending.
 *
 * @package SendStudio
 * @subpackage SplitTest
 */

/**
 * This should never get called outside of the cron system.
 * IEM_CRON_JOB is defined in the main cron/addons.php file,
 * so if it's not available then someone is doing something strange.
 */
if (!defined('IEM_CRON_JOB')) {
	die("You cannot access this file directly.");
}

/**
 * Splittest_Cron_GetJobs
 * This is used to work out which jobs need to be run for split test sending.
 *
 * It adds an array containing the addon id, the path to this file and the jobs that need to be processed.
 *
 * <code>
 *	$job_details = array (
 *		'addonid' => 'splittest',
 *		'file' => '/full/path/to/file',
 *		'jobs' => array (
 *			'1',
 *			'2',
 *		);
 *	);
 * </code>
 *
 * This gets the job id's from the splittests table which are
 * - 'w'aiting to be sent before "now"
 * - 'i'n progress and haven't been updated in at least 30 minutes (means the job crashed or the server crashed)
 * and are approved/finished being set up.
 *
 * @param EventData_IEM_CRON_RUNADDONS $data The current list of cron tasks that need to be processed. This function just adds it's own data to the end.
 *
 * @return Void The data is passed in by reference, so this doesn't return anything.
 *
 * @uses EventData_IEM_CRON_RUNADDONS
 */
function Splittest_Cron_GetJobs(EventData_IEM_CRON_RUNADDONS $data)
{
	$job_details = array (
		'addonid' => 'splittest',
		'file' => __FILE__,
		'jobs' => array ()
	);

	require_once SENDSTUDIO_API_DIRECTORY . '/api.php';
	$api = new API;

	$timenow = $api->GetServerTime();
	$half_hour_ago = $timenow - (30 * 60);

	$db = IEM::getDatabase();
	$query = "SELECT jobid FROM " . $db->TablePrefix . "jobs WHERE jobtype='splittest' AND (";

	/**
	* get "waiting" jobs
	*/
	$query .= " (jobstatus ='w' AND jobtime < " . $timenow . ") OR ";

	/**
	* get "resending" jobs
	*/
	$query .= " (jobstatus='r' AND jobtime < " . $timenow . ") OR ";

	/**
	 * get "timeout" jobs
	 * they are jobs which are sent to "percentage" split test campaigns
	 * and have waited their "hours after" time before continuing a send.
	 *
	 * When a job is marked as "timeout", it changes the jobtime to include the "hours after" time
	 * so here we don't need to do any calculations.
	 */
	$query .= " (jobstatus='t' AND jobtime < " . $timenow . ") OR ";

	/**
	* Get jobs that haven't been updated in half an hour.
	* This is in case a job has broken (eg the db went down or server was rebooted mid-send).
	*/
	$query .= " (jobstatus='i' AND jobtime < " . $timenow . " AND lastupdatetime < " . $half_hour_ago . ")";

	/**
	* and only get approved jobs
	* which are ones that have been completely set up.
	*/
	$query .= ") AND (approved > 0)";

	$result = $db->Query($query);
	while ($row = $db->Fetch($result)) {
		$job_details['jobs'][] = (int)$row['jobid'];
	}

	if (!empty($job_details)) {
		$data->jobs_to_run[] = $job_details;
	}
}

/**
 * Make sure the parent class is included.
 * We use it to switch between the newsletters we're sending, set up custom fields and so on.
 */
require_once dirname(__FILE__) . '/api/splittest_send.php';

/**
 * This class handles sending split test campaigns via cron.
 * It uses the Splittest_Send_API to cache/switch between each newsletter being sent.
 * It also uses the main classes (Jobs_API, Send_API) to handle job/queue/send processing.
 *
 * This needs to handle two types of split test sending:
 *
 * 1) sending a number of emails evenly across a whole number of subscribers
 *
 * 2) sending a number of emails evenly across a small percentage of subscribers,
 * 		then after "X" hours (defined in the split test), send the email with the most "opens" or "link clicks"
 * 		to the rest of the subscribers on that list or segment.
 *
 * @uses Splittest_Send_API
 * @uses Jobs_API
 * @uses Send_API
 * @uses Stats_API
 */
class Jobs_Cron_API_Splittest extends Splittest_Send_API
{

	/**
	 * send_limit
	 * The maximum number of recipients/subscribers to load in to memory at once.
	 * This allows the job to process a bunch of recipients at once
	 * (one db query to load this number of subscribers)
	 * but not take up all available memory.
	 *
	 * @usedby _ActionJob
	 */
	const send_limit = 500;

	/**
	 * _splittest_api
	 * A placeholder variable for the splittest api
	 *
	 * @var Splittest_API
	 *
	 * @see _ProcessJob
	 */
	private $_splittest_api = null;

	/**
	 * _subscribers_api
	 * A placeholder variable for the subscriber api
	 * This is mainly used when setting up a send ready to go:
	 * - set up the queue
	 * - remove duplicates
	 * - remove banned emails
	 * etc
	 *
	 * @var Subscribers_API
	 *
	 * @see ProcessJobs
	 * @see _ProcessJob
	 */
	private $_subscribers_api = null;

	/**
	 * _stats_api
	 * A placeholder variable for the stats api
	 * Used to record user stats, marking a newsletter as "finished" sending etc.
	 *
	 * @var Stats_API
	 *
	 * @see ProcessJobs
	 * @see ProcessJob
	 * @see _FinishJob
	 */
	private $_stats_api = null;

	/**
	 * Maximum number of people to send to
	 * if the send type is 'percentage'.
	 * This is used to limit how many we send to
	 * before the campaign is paused for a period of time.
	 *
	 * If this is null, then there is no maximum and we're processing
	 * - an "evenly distributed" split test
	 * - sending the "winning" campaign to the rest of a list/segment
	 *
	 * and so don't need to look at the limit.
	 *
	 * @var Int It's an integer value if we're sending to the first percentage of a list/segment.
	 *
	 * @usedby _ProcessJob
	 * @usedby _ActionJob
	 */
	private $_percentage_send_maximum = null;

	/**
	 * __construct
	 * This constructor does nothing itself.
	 * It calls the parent constructor to get it to set whatever it needs up.
	 *
	 * @uses Splittest_Send_API::__construct
	 *
	 * @return Void Doesn't return anything.
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * ProcessJobs
	 * This takes an array of job id's to process and goes through them one by one
	 * to send a split test campaign.
	 * It also sets up some local variables for caching/easy use.
	 *
	 * This method just basically loops over each job in the array and gives it to _ProcessJob
	 * In between each job it processes, it clears the "cached" elements in the Splittest_Send_API
	 *
	 * If any of the job id's are invalid (non-numeric), they are just discarded.
	 *
	 * @param Array $jobs An array of job id's to process.
	 *
	 * @see Splittest_Cron_GetJobs
	 * @see _subscribers_api
	 * @see _stats_api
	 * @uses ForgetSend
	 * @uses _ProcessJob
	 * @uses CheckIntVars
	 *
	 * @return Void Doesn't return anything.
	 */
	function ProcessJobs($jobs=array())
	{
		$jobs = $this->CheckIntVars($jobs);
		if (empty($jobs)) {
			return;
		}

		require_once dirname(__FILE__) . '/api/splittest.php';
		require_once SENDSTUDIO_API_DIRECTORY . '/subscribers.php';
		require_once SENDSTUDIO_API_DIRECTORY . '/stats.php';

		$this->_splittest_api = new Splittest_API;
		$this->_subscribers_api = new Subscribers_API;
		$this->_stats_api = new Stats_API;

		foreach ($jobs as $jobid) {
			$this->ForgetSend();
			$this->_ProcessJob($jobid);
		}
	}

	/**
	 * _ProcessJob
	 * This method does the "setup work" for a split test campaign.
	 *
	 * If a job is passed in that hasn't been started before, it will set everything up:
	 * - create a "queue" of recipients
	 * - clean the queue (remove banned/duplicate recipients etc)
	 * - set up stats for each newsletter in the split test campaign
	 * - save stats for the user sending the campaign to take off credits etc
	 *
	 * If a job is passed in that has been set up before, it just loads the data up.
	 *
	 * Once it has done either of those, it gives the details to the Splittest_Send_API class
	 * and then calls _ActionJob.
	 * Based on what that returns, it will either mark the job as complete or not.
	 *
	 * If the job is a percentage split test send (send to first X % of a list/segment), then
	 * it sets the appropriate flags etc to make sure we only send to that first X%.
	 * If the job hasn't been set up before, it works out the limit and sets the details in the jobdetails
	 * which are then saved in the database in case the job dies or is paused etc.
	 *
	 * If the job is a percentage split test send and it's time to send to the rest of the list/segment,
	 * then it works out the "best" one to send and sets the appropriate variables for that to happen.
	 *
	 * @param Int $jobid The specific job id we're going to process.
	 *
	 * @uses _jobid
	 * @uses StartJob
	 * @uses PauseJob
	 * @uses LoadJob
	 * @uses GetUser
	 * @uses GetJobQueue
	 * @uses CreateQueue
	 * @uses JobQueue
	 * @uses GetSubscribersFromSegment
	 * @uses GetSubscribers
	 * @uses RemoveDuplicatesInQueue
	 * @uses RemoveBannedEmails
	 * @uses RemoveUnsubscribedEmails
	 * @uses QueueSize
	 * @uses _CalculateBestNewsletter
	 * @uses _FinishJob
	 * @uses _ActionJob
	 * @uses _CalculateBestNewsletter
	 *
	 * @return Boolean Returns whether the job was processed or not. If a job could not be processed, it returns false. Otherwise it returns true.
	 */
	private function _ProcessJob($jobid=0)
	{
		if ($jobid <= 0) {
			return false;
		}

		$this->_jobid = $jobid;

		/**
		 * Load the job, then start it.
		 * We need to do this so when we call "StartJob" we can give it the splitid to "start" as well.
		 */
		$jobinfo = $this->LoadJob($jobid);

		$jobdetails = $jobinfo['jobdetails'];

		/**
		 * Need to load the split campaign
		 * before starting the job
		 * so if we're in "t"imeout mode,
		 * we can look at the stats
		 * We also need the weighting's from the split campaign
		 * to work it out.
		 */
		$this->splitcampaign_details = $this->_splittest_api->Load($jobdetails['splitid']);

		/**
		 * If it's a "t"imeout, work out the best stat.
		 */
		if ($jobinfo['jobstatus'] == 't') {
			$jobdetails['newsletters'] = $this->_CalculateBestNewsletter($jobdetails['Stats']);

			/**
			 * Also need to kill off the "percentage_send_maximum" element
			 * as this would cause the job to go into "timeout" mode again.
			 */
			unset($jobdetails['percentage_send_maximum']);
		}

		if (!$this->StartJob($jobid, $jobdetails['splitid'])) {
			$this->PauseJob($jobid);
			return false;
		}

		// ----- "Login" to the system as the job's owner.
			$user = GetUser($jobinfo['ownerid']);
			IEM::userLogin($jobinfo['ownerid'], false);
		// -----

		$queueid = false;

		// if there's no queue, start one up.
		if (!$queueid = $this->GetJobQueue($jobid)) {
			/**
			 * Randomize the newsletters
			 * It's probably already been done but can't hurt to do it again.
			 */
			shuffle($jobdetails['newsletters']);

			$sendqueue = $this->CreateQueue('splittest');
			$queueok = $this->JobQueue($jobid, $sendqueue);
			$send_criteria = $jobdetails['SendCriteria'];

			$queueinfo = array('queueid' => $sendqueue, 'queuetype' => 'splittest', 'ownerid' => $jobinfo['ownerid']);

			if (isset($jobdetails['Segments']) && is_array($jobdetails['Segments'])) {
				$this->_subscribers_api->GetSubscribersFromSegment($jobdetails['Segments'], false, $queueinfo, 'nosort');
			} else {
				$this->_subscribers_api->GetSubscribers($send_criteria, array(), false, $queueinfo, $sendqueue);
			}

			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$this->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
			}

			$this->_subscribers_api->RemoveDuplicatesInQueue($sendqueue, 'splittest', $jobdetails['Lists']);

			$this->_subscribers_api->RemoveBannedEmails($jobdetails['Lists'], $sendqueue, 'splittest');

			$this->_subscribers_api->RemoveUnsubscribedEmails($jobdetails['Lists'], $sendqueue, 'splittest');

			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$this->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
			}

			$jobdetails['SendSize'] = $this->_subscribers_api->QueueSize($sendqueue, 'splittest');

			$jobdetails['Stats'] = array();

			$jobdetails['SendQueue'] = $sendqueue;

			/**
			 * Delete the old user stats allocations.
			 * They were all allocated under one stat/job before so the user recorded their send info
			 * so they couldn't set up split test sends and go over their send quota.
			 *
			 * Now, we need to re-allocate them per newsletter being sent.
			 */
			$this->_stats_api->DeleteUserStats($jobinfo['ownerid'], $this->_jobid);

			$statids = array();

			foreach ($jobdetails['newsletters'] as $newsletterid) {
				$newsletterstats = $jobdetails;
				$newsletterstats['Job'] = $jobid;
				$newsletterstats['Queue'] = $sendqueue;
				$newsletterstats['SentBy'] = $queueinfo['ownerid'];
				$newsletterstats['SendType'] = 'splittest';
				$newsletterstats['Newsletter'] = $newsletterid;
				$newsletterstats['Lists'] = $jobdetails['sendingto']['Lists'];
				$newsletterstats['SendCriteria'] = $jobdetails['SendCriteria'];

				$statid = $this->_stats_api->SaveNewsletterStats($newsletterstats);
				$statids[] = $statid;

				$jobdetails['Stats'][$newsletterid] = $statid;

				$this->_stats_api->RecordUserStats($jobinfo['ownerid'], $this->_jobid, $jobdetails['SendSize'], $jobdetails['SendStartTime'], $statid);
			}

			$this->SaveSplitStats($jobdetails['splitid'], $this->_jobid, $statids);

			/**
			 * If it's a percentage send,
			 * work out the number of emails to send for the first percentage
			 * It gets stored in the jobdetails array so it can be saved in the database.
			 */
			if ($this->splitcampaign_details['splittype'] == 'percentage') {
				$max_to_email = ceil(($this->splitcampaign_details['splitdetails']['percentage'] / 100) * $jobdetails['SendSize']);
				$jobdetails['percentage_send_maximum'] = $max_to_email;
			}

			/**
			 * Save the job stat details.
			 * Otherwise we could potentially end up with a 'start'ed queue but no stats.
			 */
			$this->Set('jobdetails', $jobdetails);

			$this->UpdateJobDetails();

			/**
			 * This is to process the 'queueid' later in the code.
			 */
			$queueid = $sendqueue;

			// This will make sure that the credit warning emails are also being send out from splittest
			API_USERS::creditEvaluateWarnings($user->GetNewAPI());
		}

		$this->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");

		$queuesize = $this->_subscribers_api->QueueSize($queueid, 'splittest');

		$this->_queueid = $queueid;

		/**
		 * If there is a "percentage_send_maximum" variable in the jobdetails array,
		 * we are sending to the first part of a 'percentage' split test.
		 *
		 * We have to send to the rest of the percentage maximum before we pause the job,
		 * work out the "best" performing campaign and send to that.
		 */
		if (isset($jobdetails['percentage_send_maximum'])) {
			$this->_percentage_send_maximum = (int)$jobdetails['percentage_send_maximum'];
		}

		/**
		 * If the _percentage_send_maximum is 0, then "timeout" the job.
		 * We must have hit "pause" right at the end of the initial send process or something.
		 */
		if ($this->_percentage_send_maximum !== null && $this->_percentage_send_maximum <= 0) {
			$this->TimeoutJob($jobid, $this->splitcampaign_details['splitid'], $this->splitcampaign_details['splitdetails']['hoursafter']);
			IEM::userLogout();
			return true;
		}

		$this->Set('statids', $jobdetails['Stats']);
		$this->Set('jobdetails', $jobdetails);
		$this->Set('jobowner', $jobinfo['ownerid']);

		/**
		* There's nothing left? Just mark it as done.
		*/
		if ($queuesize == 0) {
			$this->_FinishJob();
			IEM::userLogout();
			return true;
		}

		$finished = $this->_ActionJob($jobid, $queueid);

		if ($finished) {
			$this->_FinishJob();
		}

		IEM::userLogout();

		return true;
	}

	/**
	 * _ActionJob
	 * This loads the recipients/subscribers from the database
	 * and loops through them to send them an email.
	 *
	 * Between each email being sent, it switches to another newsletter (if necessary)
	 * so each recipient gets something different.
	 *
	 * If we're sending a percentage split test, at the end of sending the first X emails,
	 * it also puts the job into "timeout" mode which delays sending the rest of the split test campaign.
	 *
	 * @param Int $jobid The job we are processing
	 * @param Int $queueid The job queue we are processing (this is where the recipient id's are all stored).
	 *
	 * @uses SetupJob
	 * @uses NotifyOwner
	 * @uses SetupAllNewsletters
	 * @uses send_limit
	 * @uses _percentage_send_maximum
	 * @uses FetchFromQueue
	 * @uses SetupCustomFields
	 * @uses SetupNewsletter
	 * @uses SendToRecipient
	 * @uses Paused
	 * @uses JobPaused
	 * @uses TimeoutJob
	 * @see _FinishJob
	 *
	 * @return Boolean Returns whether the job has completely finished sending or not. This is used by _FinishJob to mark everything as done if necessary.
	 */
	private function _ActionJob($jobid, $queueid)
	{
		if (!$this->SetupJob($jobid, $queueid)) {
			return false;
		}

		$this->NotifyOwner();

		$this->SetupAllNewsletters();

		$emails_sent = 0;

		$this->Db->StartTransaction();

		$paused = false;

		/**
		 * If the _percentage_send_maximum variable is not null,
		 * we're sending a "percentage" split test.
		 *
		 * We need to work out how many to send to.
		 *
		 * Normally we just get 500 subscribers.
		 * If that limit is set, we need the minimum to send to.
		 *
		 * Each time an email is sent, that number will be decreased
		 * just in case the job is paused or the server kills it off,
		 * it won't get reset to the "maximum" for the job
		 * (which could end up emailing everyone).
		 */
		$send_limit = self::send_limit;
		if ($this->_percentage_send_maximum !== null) {
			$send_limit = min(self::send_limit, $this->_percentage_send_maximum);
		}

		while ($recipients = $this->FetchFromQueue($queueid, 'splittest', 1, $send_limit)) {
			if (empty($recipients)) {
				break;
			}

			$this->SetupDynamicContentFields($recipients);
			$this->SetupCustomFields($recipients);

			$sent_to_recipients = array();

			foreach ($recipients as $p => $recipientid) {
				/**
				 * If we're sending a "percentage" split test,
				 * decrement the local class variable
				 * and also the job details variable
				 *
				 * The job details variable is automatically saved in "SendToRecipient"
				 * so if the job is paused or dies etc
				 * it will get saved and we'll have a new number of recipients to limit the send to.
				 */
				if ($this->_percentage_send_maximum !== null) {
					$this->_percentage_send_maximum--;
					$this->jobdetails['percentage_send_maximum']--;
				}

				$this->SetupNewsletter();

				$send_results = $this->SendToRecipient($recipientid, $queueid);

				$sent_to_recipients[] = $recipientid;

				$emails_sent++;

				/**
				 * Whether to check if the job has been paused or not.
				 * We want to do that at the last possible moment..
				 */
				$check_paused = false;

				/**
				* update lastupdatedtime so we can track what's going on.
				* This is used so we can see if the server has crashed or the cron job has been stopped in mid-send.
				*
				* @see FetchJob
				*/
				if ($this->userpause > 0 || ($this->userpause == 0 && (($emails_sent % 5) == 0))) {
					$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "jobs SET lastupdatetime='" . $this->GetServerTime() . "' WHERE jobid='" . (int)$jobid . "'";
					$this->Db->Query($query);
					$this->Db->CommitTransaction();
					$this->Db->StartTransaction();
					$emails_sent = 0;
					$check_paused = true;
				}

				// we should only need to pause if we successfully sent.
				if ($send_results['success'] > 0) {
					$this->Pause();
				}

				/**
				 * See if the job has been paused or not through the control panel.
				 * If it has, break out of the recipient loop
				 * Then clean up the recipients we have sent to successfully
				 * then break out of the send 'job'.
				 */
				if ($check_paused) {
					$paused = $this->JobPaused($jobid);
					if ($paused) {
						break;
					}
				}
			}

			if (!empty($sent_to_recipients)) {
				$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . $queueid . "' AND queuetype='splittest' AND recipient IN (" . implode(',', $sent_to_recipients) . ") AND processed='1'";
				$this->Db->Query($query);
			}

			if ($paused) {
				break;
			}

			/**
			 * If we've reached the "max" to send for the first part of the "percentage" split test campaign,
			 * break out of the loop.
			 */
			if ($this->_percentage_send_maximum !== null) {

				/**
				 * If there's nothing left to send, break out of the loop.
				 */
				if ($this->_percentage_send_maximum <= 0) {
					break;
				}

				/**
				 * re-calculate how many recipients to retrieve.
				 * For example, if there were 700 recipients previously, and we just sent to 500,
				 * we should only send to another 200.
				 */
				$send_limit = min(self::send_limit, $this->_percentage_send_maximum);
			}

		}

		$this->Db->CommitTransaction();

		/**
		 * By default, mark the job as "complete" and finished.
		 */
		$jobstatus = 'c';
		$finished = true;

		if ($paused) {
			$jobstatus = 'p';
			$finished = false;
		}

		/**
		 * If we are doing a percentage send and we're in the first stages,
		 * the jobstatus becomes "t" for "timeout".
		 * The job is *not* finished either (which would kill stats off).
		 */
		if ($this->_percentage_send_maximum !== null) {

			/**
			 * Only "timeout" a job if we are finished the initial send.
			 * We can "pause" the initial send and resume it
			 * so need to make sure that we don't prematurely "timeout" a job.
			 */
			if ($this->_percentage_send_maximum <= 0) {
				$jobstatus = 't';
				$finished = false;
				$this->TimeoutJob($jobid, $this->splitcampaign_details['splitid'], $this->splitcampaign_details['splitdetails']['hoursafter']);
			}
		}

		$this->Email_API->SMTP_Logout();

		$this->NotifyOwner($jobstatus);

		return $finished;
	}

	/**
	 * _FinishJob
	 * This does a few cleanup jobs.
	 * - Marks the job as complete in stats
	 * - Clears out any unsent recipients from the "queue".
	 * - Calls the parent FinishJob method to do it's work.
	 *
	 * @uses _stats_api
	 * @uses Stats_API::MarkNewsletterFinished
	 * @uses ClearQueue
	 * @uses FinishJob
	 *
	 * @return Void Doesn't return anything.
	 */
	function _FinishJob()
	{
		/**
		 * Pass all of the stats through to the stats api.
		 *
		 * Since the stats contains an array of:
		 * newsletterid => statid
		 *
		 * we just need to pass through the statid's.
		 */
		$this->_stats_api->MarkNewsletterFinished(array_values($this->jobdetails['Stats']), $this->jobdetails['SendSize']);

		$this->ClearQueue($this->jobdetails['SendQueue'], 'splittest');

		$this->FinishJob($this->_jobid, $this->jobdetails['splitid']);
	}

	/**
	 * NotifyOwner
	 * Sends an email to the list owner(s) to tell them what's going on with a send.
	 * eg:
	 * - send has started
	 * - send has finished
	 * - send has been paused
	 * - send is in 'timeout' mode (for percentage split tests)
	 *
	 * @param String $jobstatus The new jobstatus. This is used to work out the subject/message for the notification email.
	 *
	 * @uses SendStudio_Functions::PrintTime
	 * @uses SendStudio_Functions::FormatNumber
	 * @uses emailssent
	 * @uses Send_API::NotifyOwner
	 *
	 * @return Mixed Returns the status from the parent NotifyOwner call.
	 */
	function NotifyOwner($jobstatus='s')
	{
		$notify_subject = $this->splitcampaign_details['splitname'];

		require_once dirname(__FILE__) . '/language/language.php';

		$time = $this->sendstudio_functions->PrintTime();

		switch ($jobstatus) {
			case 'c':
				$subject = sprintf(GetLang('Addon_splittest_Job_Subject_Complete'), $notify_subject);
				if ($this->emailssent == 1) {
					$message = sprintf(GetLang('Addon_splittest_Job_Message_Complete_One'), $notify_subject, $time);
				} else {
					$message = sprintf(GetLang('Addon_splittest_Job_Message_Complete_Many'), $notify_subject, $time, $this->sendstudio_functions->FormatNumber($this->emailssent));
				}
			break;
			case 'p':
				$subject = sprintf(GetLang('Addon_splittest_Job_Subject_Paused'), $notify_subject);
				if ($this->emailssent == 1) {
					$message = sprintf(GetLang('Addon_splittest_Job_Message_Paused_One'), $notify_subject, $time);
				} else {
					$message = sprintf(GetLang('Addon_splittest_Job_Message_Paused_Many'), $notify_subject, $time, $this->sendstudio_functions->FormatNumber($this->emailssent));
				}
			break;

			case 't':
				$percent = $this->sendstudio_functions->FormatNumber($this->splitcampaign_details['splitdetails']['percentage']);

				$subject = sprintf(GetLang('Addon_splittest_Job_Subject_Timeout'), $notify_subject, $percent);
				if ($this->emailssent == 1) {
					$message = sprintf(GetLang('Addon_splittest_Job_Message_Timeout_One'), $notify_subject, $percent);
				} else {
					$message = sprintf(GetLang('Addon_splittest_Job_Message_Timeout_Many'), $notify_subject, $percent, $this->sendstudio_functions->FormatNumber($this->emailssent));
				}
			break;

			default:
				$subject = sprintf(GetLang('Addon_splittest_Job_Subject_Started'), $notify_subject);
				$message = sprintf(GetLang('Addon_splittest_Job_Message_Started'), $notify_subject, $time);
		}

		$this->notify_email = array (
			'subject' => $subject,
			'message' => $message
		);

		$this->jobstatus = $jobstatus;

		return parent::NotifyOwner();
	}

	/**
	 * ForgetSend
	 * Forgets (or resets) the class variables between each split test campaign being sent.
	 *
	 * This one just sets the _percentage_send_maximum variable back to the default (null)
	 * then calls the parent ForgetSend method to do the rest.
	 *
	 * @uses _percentage_send_maximum
	 * @uses Splittest_Send_API::ForgetSend
	 *
	 * @return Mixed Returns the status from the parent ForgetSend method.
	 */
	protected function ForgetSend()
	{
		$this->_percentage_send_maximum = null;
		return parent::ForgetSend();
	}

	/**
	 * _CalculateBestNewsletter
	 *
	 * This works out the "best" newsletter based on the statid's passed in
	 * and the current split test we're running.
	 *
	 * The process is quite simple.
	 *
	 * Using the percentages from the split test campaign,
	 * work out the number of subscribers in that category for the original "X %"
	 *
	 * Multiply the number of subscribers in that weight category against the weight percentage
	 *
	 * That gives us a score in that particular category.
	 *
	 * Add up all of the scores across all categories to try and work out the best.
	 *
	 * If there is only one stat with the best score, that newsletter id is returned.
	 *
	 * If there are multiple stats with the best score, the more heavily weighted area is considered.
	 * That is - if one area is weighted 60% and one is 40%, then only the 60% category is considered.
	 * Then work out the max score just for that category.
	 * If there is only one newsletter with that max score, that's the best one to send.
	 *
	 * If multiple categories have the same weighting or
	 * if there are multiple newsletters with the same score in the best category,
	 * all of those newsletter id's are returned and the rest of the split test campaign
	 * turns into an 'evenly distributed' campaign with a rotation between newsletters being sent.
	 *
	 * An email also goes to the list owner(s) to inform them of this action.
	 *
	 * @param Array $newsletter_statids The newsletter -> statids array from the jobdetails. This was originally created/setup when the job sent it's first "X %".
	 *
	 * @return Array Returns either an array with a single newsletter id, or returns an array of newsletter id's to continue sending.
	 */
	private function _CalculateBestNewsletter($newsletter_statids=array())
	{
		$statids = array_values($newsletter_statids);

		$total_scores = array();
		$weight_scores = array();

		foreach ($statids as $statid) {
			$total_scores[$statid] = 0;
		}

		$weights = $this->splitcampaign_details['splitdetails']['weights'];
		foreach ($weights as $weight_name => $weight_percentage) {
			foreach ($statids as $statid) {
				$weight_scores[$weight_name][$statid] = $total_scores[$statid];
			}

			if ($weight_percentage == 0) {
				continue;
			}

			switch ($weight_name) {
				case 'openrate':
					$query = "SELECT statid, emailopens_unique AS count FROM " . $this->Db->TablePrefix . "stats_newsletters WHERE statid IN (" . implode(',', $statids) . ")";
				break;

				case 'linkclick':
					$query = "SELECT lc.statid AS statid, COUNT(DISTINCT lc.linkid) AS count FROM " . $this->Db->TablePrefix . "stats_linkclicks lc INNER JOIN " . $this->Db->TablePrefix . "links ml ON (lc.linkid=ml.linkid) WHERE lc.statid IN (" . implode(',', $statids) . ") GROUP BY lc.statid";
				break;
			}

			$result = $this->Db->Query($query);
			while ($row = $this->Db->Fetch($result)) {
				$statid = $row['statid'];
				$percentage = $row['count'] * $weight_percentage;
				$weight_scores[$weight_name][$statid] += $percentage;
				$total_scores[$statid] += $percentage;
			}
		}

		/**
		 * Work out the max score.
		 */
		$best_score = max($total_scores);

		/**
		 * Work out which stat(s) have the best score.
		 * We could use array_values here however, it's possible for 2 (or more) stats to have the same score.
		 * array_values can only return one entry (the first it finds).
		 */
		$best_statids = array_keys($total_scores, $best_score);

		/**
		 * If we only found one stat with the best score, we have our newsletter.
		 */
		if (sizeof($best_statids) == 1) {
			$best_stat = array_pop($best_statids);
			$best_newsletter = array_search($best_stat, $newsletter_statids);
			return array($best_newsletter);
		}

		/**
		 * If we haven't got one winner,
		 * the next step is to look at which is more heavily weighted
		 * and try to choose the best score in that category.
		 */
		$best_weight = max($weights);

		/**
		 * Use array_keys here again as two weight types could have the same amount (eg both are 50%).
		 * If we get one "weight type" with the best weight,
		 * we look at the newsletter with the best score in that category.
		 */
		$best_weights = array_keys($weights, $best_weight);

		if (sizeof($best_weights) == 1) {
			$best_weight = array_pop($best_weights);

			/**
			 * Now we have limited the "weight" category,
			 * work out the best score just for that category.
			 */
			$scores = $weight_scores[$best_weight];
			$best_score = max($scores);

			/**
			 * Again, look for the stat with the best score.
			 */
			$best_statids = array_keys($scores, $best_score);

			/**
			 * If there is only one stat with the best score, we have our newsletter.
			 */
			if (sizeof($best_statids) == 1) {
				$best_stat = array_pop($best_statids);
				$best_newsletter = array_search($best_stat, $newsletter_statids);
				return array($best_newsletter);
			}

			/**
			 * If there are multiple newsletters with the same score in the more heavily weighted category,
			 * we'll just set the "best_statids" array (as above)
			 * and fall through
			 * The rest of the method handles if multiple newsletters have the same score.
			 * All we've done here is change the statid's to look at to ones that are in the more heavily weighted category.
			 */
		}

		/**
		 * If there are multiple newsletters with the same score,
		 * we want to return all of the best ones
		 * so then they can be looped through like an 'evenly distributed' split test would be.
		 */
		$best_stats = array_intersect($newsletter_statids, $best_statids);

		/**
		 * best_stats contains an array of newsletterid => statid associations
		 * we only need to return the newsletterid's so just get the keys.
		 */
		return array_keys($best_stats);
	}
}
