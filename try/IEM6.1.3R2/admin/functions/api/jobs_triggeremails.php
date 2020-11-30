<?php
/**
 * This file contains TriggerEmails job system class.
 *
 * The class will contains everything that needed to be done
 * to send a trigger email.
 *
 * @author Hendri <hendri@interspire.com>
 * @package API
 * @subpackage Jobs
 */

/**
 * Require the base job class.
 */
require_once(dirname(__FILE__) . '/triggeremails.php');

/**
 * Require the ss Email class
 */
require_once(dirname(__FILE__) . '/ss_email.php');

/**
 * Require the subscriber API
 */
require_once(dirname(__FILE__) . '/subscribers.php');

/**
 * Require List API
 */
require_once(dirname(__FILE__) . '/lists.php');


/**
 * TriggerEmails job system class
 *
 * This class will handle everything that needed to be done to send
 * trigger emails.
 *
 * @package API
 * @subpackage Jobs
 */
class Jobs_TriggerEmails_API extends TriggerEmails_API
{
	/**
	 * The numver of records to be processed per-loop
	 */
	const RECORDS_PER_PROCESS = 500;


	/**
	 * Log file to be used when debug mode is ON
	 * @var String Log file name (If left empty it will populate itself automatically)
	 */
	private $_logFile = null;

	/**
	 * Put trigger email in debug mode, it will also use "SENDSTUDIO_DEBUG_MODE" constant if it needs to be in debug mode
	 * @var Boolean Debug mode flag
	 */
	private $_debugMode = false;

	/**
	 * Statistics API
	 * @var Stats_API Statistics API
	 */
	private $_statsAPIObject = null;

	/**
	 * Cache of trigger records
	 * @var Array An array of trigger email records
	 */
	private $_triggerRecords = array();

	/**
	 * Cache of newsletter records
	 * @var Array An array of newsletter records
	 */
	private $_newsletterRecords = array();

	/**
	 * Cache of list records
	 * @var Array An array of list records
	 */
	private $_subscriberlistRecords = array();

	/**
	 * Cache of newsletter custom fields
	 * @var Array An array of customfields for each newsletters
	 */
	private $_newsletterCustomFields = array();

	/**
	 * Cache of user records
	 * @var Array An array of user API objects
	 */
	private $_userRecords = array();


	/**
	 * Valid job types
	 * @var Array An array of string for valid job types
	 */
	static private $_validJobTypes = array('triggeremails_send', 'triggeremails_populate');

	/**
	 * Valid job status
	 * @var Array An array of string for valid job status
	 */
	static private $_validJobStatus = array('w', 'i');


	/**
	 * CONSTRUCTOR
	 *
	 * @uses SENDSTUDIO_DEBUG_MODE
	 * @uses SendStudio_Functions::GetDb()
	 * @uses Jobs_TriggerEmails_API::_log()
	 */
	public function __construct()
	{
		// Set up debug environment
		if (defined('SENDSTUDIO_DEBUG_MODE') && SENDSTUDIO_DEBUG_MODE) {
			$this->_debugMode = true;
		}

		$this->_log('Trigger Emails Jobs object constructed');
		$this->GetDb();
	}

	/**
	 * StartJob
	 * Updates the job status in the database to mark it as 'in progress' (i).
	 * Checks to make sure the job hasn't already been started by another process.
	 *
	 * @param String $jobType Trigger emails job type (triggeremails_send or triggeremails_populate)
	 * @return Mixed Returns TRUE if job is sucessfully started; FALSE if the job has already been started; on error it will return NULL.
	 *
	 * @uses Jobs_TriggerEmails_API::_log()
	 * @uses Db::StartTransaction()
	 * @uses Db::Query()
	 * @uses Db::RollbackTransaction()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 * @uses Db::GetError()
	 * @uses Db::CommitTransaction()
	 * @uses Jobs_TriggerEmails_API::UpdateJob()
	 * @uses Jobs_TriggerEmails_API::$_validJobTypes
	 * @uses Jobs_TriggerEmails_API::CreateJob()
	 */
	public function StartJob($jobType)
	{
		$this->_log("Starting trigger emails job for {$jobType}");

		if (!in_array($jobType, self::$_validJobTypes)) {
			$this->_log('Cannot start job, invalid type specified');
			trigger_error('Invalid job type specified', E_USER_NOTICE);
			return null;
		}

		$now = time();
		$job = null;

		$this->Db->StartTransaction();

		/**
		 * Get all job that matches the job type.
		 * There should only be one record in the job table that matches it
		 */
			$status = $this->Db->Query("SELECT * FROM [|PREFIX|]jobs WHERE jobtype='" . $jobType . "'");
			if (!$status) {
				list($msg, $errno) = $this->Db->GetError();
				$this->Db->RollbackTransaction();
				$this->_log('Cannot fetch job: ' . $msg);
				trigger_error($msg, $errno);
				return null;
			}

			$jobs = array();
			$jobids = array();
			while ($row = $this->Db->Fetch($status)) {
				array_push($jobs, $row);
				array_push($jobids, $row['jobid']);
			}

			$this->Db->FreeResult($status);

			if (count($jobs) != 0) {
				$job = array_shift($jobs);
				array_shift($jobids); // Also remove the first element of the jobids array
			}

			/**
			 * Make sure there is only one job, delete the rest
			 */
				if (count($jobs) > 0) {
					$this->_log('More than one job records found. Cleaning up unused jobs: ' . implode(',', $jobids));
					$status = $this->Db->Query("DELETE FROM [|PREFIX|]jobs WHERE jobtype='" . $jobType . "' AND jobid IN (" . implode(',', $jobids) . ")");
					if (!$status) {
						list($msg, $errno) = $this->Db->GetError();
						$this->Db->RollbackTransaction();
						$this->_log('Cannot clean up job: ' . $msg);
						trigger_error($msg, $errno);
						return null;
					}
				}
			/**
			 * -----
			 */
		/**
		 * -----
		 */

		/**
		 * If job has not been created, then create one, and return TRUE if successful
		 */
			if (empty($job)) {
				if ($this->CreateJob($jobType, 'i', $now) == false) {
					$this->Db->RollbackTransaction();
					trigger_error('Cannot create job', E_USER_NOTICE);
					return null;
				}

				// We just created a job, so everything is good, return TRUE
				$this->Db->CommitTransaction();
				$this->_log('A new job gets created for ' . $jobType . ', and started');
				return true;
			}
		/**
		 * -----
		 */

		/**
		 * See if job has been started or have not been updated in the last 30 minutes
		 */
			$half_hour_ago = $now - (30 * 60);
			if (($job['jobstatus'] == 'w' && $job['lastupdatetime'] == 0) || $job['lastupdatetime'] < $half_hour_ago) {
				if (!$this->UpdateJob($jobType, 'i', $now)) {
					trigger_error('Cannot update job record', E_USER_NOTICE);
					return false;
				}

				// Everything is Good, return TRUE
				$this->Db->CommitTransaction();
				$this->_log('Job started');
				return true;
			}
		/**
		 * -----
		 */

		// Job has already been started, so return FALSE
		$this->Db->CommitTransaction();
		$this->_log('Job has already been started');
		return false;
	}

	/**
	 * FinishJob
	 *
	 * This function will take off the "in progress" flag off the job record
	 * for the specified job type
	 *
	 * @param String $jobType Job Type
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function FinishJob($jobType)
	{
		$this->_log('Finish job');

		// Update job to have "waiting" status
		if (!$this->UpdateJob($jobType, 'w', 0)) {
			$this->_log('Cannot update job');
			return false;
		}

		$this->_log('Job finished');
		return true;
	}

	/**
	 * CreateJob
	 *
	 * Create a job entry in the job table.
	 * This function primarily is used by StartJob() function,
	 * as it will detect whether or job has been created for the trigger.
	 *
	 * @param String $jobType Job Type
	 * @param String $jobstatus Job status
	 * @param Integer $lastupdate Last update
	 *
	 * @return Mixed Returns the new job ID if successful, FALSE othwerwise
	 *
	 * @uses Jobs_TriggerEmails_API::_log()
	 * @uses Jobs_TriggerEmails_API::$_validJobTypes
	 * @uses Jobs_TriggerEmails_API::$_validJobStatus
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::LastId()
	 * @uses SENDSTUDIO_TABLEPREFIX
	 */
	public function CreateJob($jobType, $jobstatus = 'w', $lastupdate = 0)
	{
		$this->_log('Creating new job');

		if (!in_array($jobType, self::$_validJobTypes)) {
			$this->_log('Cannot start job, invalid type specified');
			trigger_error('Invalid job type specified', E_USER_NOTICE);
			return false;
		}

		if (!in_array($jobstatus, self::$_validJobStatus)) {
			$this->_log('Cannot start job, invalid job status specified');
			trigger_error('Invalid job status specified', E_USER_NOTICE);
			return false;
		}

		$status = $this->Db->Query('INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . "jobs (jobtype, jobstatus, lastupdatetime) VALUES ('" . $jobType . "', '" . $jobstatus . "', " . intval($lastupdate) . ')');
		if ($status == false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->_log('Cannot create job: ' . $msg);
			trigger_error($msg, $errno);
			return false;
		}

		$jobid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'jobs_sequence');

		$this->_log('Job created with id: ' . $jobid);
		return $jobid;
	}

	/**
	 * UpdateJob
	 *
	 * Update job record entry
	 *
	 * @param String $jobType Job Type
	 * @param String $jobstatus Job status
	 * @param Integer $lastupdate Last update
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses Jobs_TriggerEmails_API::_log()
	 * @uses Jobs_TriggerEmails_API::$_validJobTypes
	 * @uses Jobs_TriggerEmails_API::$_validJobStatus
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses SENDSTUDIO_TABLEPREFIX
	 */
	public function UpdateJob($jobType, $jobstatus = 'w', $lastupdate = 0)
	{
		$this->_log("Start updating job for {$jobType}");

		if (!in_array($jobType, self::$_validJobTypes)) {
			$this->_log('Cannot update job, invalid type specified');
			trigger_error('Invalid job type specified', E_USER_NOTICE);
			return false;
		}

		if (!in_array($jobstatus, self::$_validJobStatus)) {
			$this->_log('Cannot update job, invalid job status specified');
			trigger_error('Invalid job status specified', E_USER_NOTICE);
			return false;
		}

		$status = $this->Db->Query('UPDATE ' . SENDSTUDIO_TABLEPREFIX . "jobs SET jobstatus = '" . $jobstatus . "', lastupdatetime=" . intval($lastupdate) . " WHERE jobtype='" . $jobType . "'");
		if ($status == false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->_log('Cannot create job: ' . $msg);
			trigger_error($msg, $errno);
			return false;
		}

		$this->_log('Job updated');
		return true;
	}

	/**
	 * ProcessJob
	 *
	 * This function will process all records in the queues that are related to the trigger emails.
	 * The queues only hold record of trigger emails that is ready to be sent out in the next 24 hours.
	 * Another process will take care populating the queue table.
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @see Jobs_TriggerEmails_API::ProcessPopulateJob()
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Jobs_TriggerEmails_API::RECORDS_PER_PROCESS
	 * @uses Jobs_TriggerEmails_API::_log()
	 * @uses Jobs_TriggerEmails_API::_cacheTriggerRecordClean()
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 * @uses Jobs_TriggerEmails_API::_cacheTriggerRecordGet()
	 * @uses Jobs_TriggerEmails_API::_cacheNewsletterGet()
	 * @uses Jobs_TriggerEmails_API::_send()
	 * @uses Jobs_TriggerEmails_API::UpdateJob()
	 * @uses Jobs_TriggerEmails_API::_markQueueRecordAsProcessed()
	 */
	public function ProcessJob()
	{
		$last_owner_id = null;
		$last_stat_id = null;
		$last_job_id = null;

		$current_available_credit = null;
		$credit_used = 0;

		$this->_log('Start processing Send Job');

		// Clean up old caches
		$this->_cacheTriggerRecordClean();

		// ----- Setup Stats API if it's not already available
			if (is_null($this->_statsAPIObject)) {
				require_once dirname(__FILE__) . '/stats.php';
				$this->_statsAPIObject = new Stats_API();
			}
		// -----

		$queues = array();
		do {
			// ----- Get all triggeremail queues that is due to be sent out
				$now = date('Y-m-d H:i:s', time());
				$limit = self::RECORDS_PER_PROCESS;

				$query = "
					SELECT *
					FROM [|PREFIX|]queues
					WHERE
						queuetype='triggeremail'
						AND processed='0'
						AND processtime <= '{$now}'
					ORDER BY queueid, processtime ASC
					LIMIT {$limit}
				";

				$resQueue = $this->Db->Query($query);
				if (!$resQueue) {
					list($msg, $errno) = $this->Db->GetError();
					$this->_log('Cannot get queues: ' . $msg);
					trigger_error($msg, $errno);
					return false;
				}

				$queues = array();
				while (($row = $this->Db->Fetch($resQueue))) {
					array_push($queues, $row);
				}

				$this->Db->FreeResult($resQueue);
			// -----


			// If nothing to be done in the queues, then get out from the loop
			if (empty($queues)) {
				break;
			}


			// ----- Loop against the queue to process the triggers
				foreach ($queues as $queue) {
					$doactions = true;
					$error = false;
					$queueid = $queue['queueid'];
					$recipientid = $queue['recipient'];

					// Get trigger record, if there is an error fetching the trigger it will NOT send the trigger
					$trigger = $this->_cacheTriggerRecordGet($queueid);
					if ($trigger === false) {
						$error = true;
						$doactions = false;
						$this->_log('Cannot get trigger record for queueid: ' . $queueid);
					}

					// Update credit related procedure
					if (is_null($last_owner_id)) {
						$last_owner_id = $trigger['ownerid'];
						$last_stat_id = $trigger['statid'];
						$last_job_id = $trigger['jobid'];

						// Current available credit returns TRUE if credit is unlimited
						$current_available_credit = true;
						if (SENDSTUDIO_CREDIT_INCLUDE_TRIGGERS) {
							$current_available_credit = API_USERS::creditAvailableTotal($trigger['ownerid']);
						}
					}

					// Check if trigger is active
					if (!$trigger['active']) {
						$error = true;
						$this->_log('Trigger is marked as INACTIVE');
					}

					// Have trigger exceeded it's running interval for this subscriber?
					// Or trigger has recently been actioned on.
					if (!$error && !$this->_checkInterval($trigger, $recipientid)) {
						$doactions = false;
						$this->_log('Do not proceed with trigger actions, as action interval has been exceeded for this subscriber.');
					}

					// ----- Check whether the recipient exists or not
						$status = $this->Db->Query("SELECT subscriberid FROM [|PREFIX|]list_subscribers WHERE subscriberid = {$recipientid}");
						if (!$status) {
							$this->_log('Cannot check database for particular subscriber');
							return false;
						}

						$row = $this->Db->Fetch($status);
						$this->Db->FreeResult($status);

						if (empty($row)) {
							$doactions = false;
							$this->_log('Do not proceed with trigger actions... no subscribers found');
						}
					// -----

					if ($doactions && !$error) {
						if (!empty($trigger) && isset($trigger['triggeractions']) && is_array($trigger['triggeractions'])) {
							if (array_key_exists('send', $trigger['triggeractions'])) {
								// Send only when credit is available
								if (($current_available_credit === true) || ($current_available_credit > 0 && $current_available_credit > $credit_used)) {
									$status = $this->_ProcessJob_Send($queue, $trigger, $recipientid);
									if ($status['halt']) {
										return false;
									}
									if ($status['error']) {
										$error = true;
									}
								} else {
									$rs = $this->Db->Query("SELECT emailaddress FROM [|PREFIX|]list_subscribers WHERE subscriberid = {$recipientid}");
									$emailaddress = $this->Db->FetchOne($rs, 'emailaddress');

									// Log failed sending
									$this->RecordLogActions($trigger['triggeremailsid'], $recipientid, 'send_failed', $emailaddress);
									trigger_error('Insuficient credits! Cannot send newsletter with queueid:' . $queue['queueid'] . ', and recipient:' . $recipientid);
									$error = true;
								}


								// ----- Record sending so that it counted towards credit usage
									// Increment credit use
									if (!$error) {
										++$credit_used;
									}

									// Record usage whenever the statid changed
									// NOTE: Each trigger is assigned a different statid and jobid
									if ($last_stat_id != $trigger['statid']) {
										if ($current_available_credit !== true) {
											$status = API_USERS::creditUse($last_owner_id, API_USERS::CREDIT_USAGETYPE_SENDTRIGGER, $credit_used, $last_job_id, $last_stat_id);
											if (!$status) {
												$this->_log("Cannot record usage -- userid = {$last_owner_id}, credit_used = {$credit_used}, job_id = {$last_job_id}, stat_id = {$last_stat_id}");
												return false;
											}
										}

										$last_owner_id = $trigger['ownerid'];
										$last_stat_id = $trigger['statid'];
										$last_job_id = $trigger['jobid'];

										$current_available_credit = true;
										if (SENDSTUDIO_CREDIT_INCLUDE_TRIGGERS) {
											$current_available_credit = API_USERS::creditAvailableTotal($trigger['ownerid']);
										}

										$credit_used = 0;
									}
								// -----
							}

							if (array_key_exists('addlist', $trigger['triggeractions'])) {
								$status = $this->_ProcessJob_AddList($queue, $trigger, $recipientid);
								if ($status['halt']) {
									return false;
								}
								if ($status['error']) {
									$error = true;
								}
							}

							if (array_key_exists('removelist', $trigger['triggeractions'])) {
								$status = $this->_ProcessJob_RemoveList($queue, $trigger, $recipientid);
								if ($status['halt']) {
									return false;
								}
								if ($status['error']) {
									$error = true;
								}
							}
						}
					}

					// Update log statistics if "removelist" is not enabled, otherwise it will be a wasteful exercise
					if ($doactions) {
						// Record summary
						if (!$this->RecordLogSummary($trigger['triggeremailsid'], $recipientid)) {
							$this->_log('Cannot write log summary to the database... exitting');
							return false;
						}
					}

					// update lastupdatedtime so we can track what's going on.
					// This is used so we can see if the server has crashed or the cron job has been stopped in mid-send.
					if (!$this->UpdateJob('triggeremails_send', 'i', time())) {
						$this->_log('Cannot update job... Exitting');
						return false;
					}

					// Mark queue record as processed, if cannot mark queue as processed, terminate
					if (!$this->_markQueueRecordAsProcessed($queueid, $recipientid)) {
						$this->_log('Cannot mark queue record qith queueueid:' . $queueid . ', and recipient:' . $recipientid . ' as processed');
						return false;
					}
				}
			// -----


			// ----- Clean up queue
				$status = $this->Db->Query("DELETE FROM [|PREFIX|]queues WHERE queuetype='triggeremail' AND processed='1'");
				if (!$status) {
					list($msg, $errno) = $this->Db->GetError();
					$this->_log('Cannot cleanup queue: ' . $msg);
					trigger_error($msg, $errno);
					return false;
				}
			// -----
		} while (!empty($queues));

		// ----- If there are leftover credits that haven't been recorded, then record it here
			if ($credit_used > 0) {
				$status = API_USERS::creditUse($last_owner_id, API_USERS::CREDIT_USAGETYPE_SENDTRIGGER, $credit_used, $last_job_id, $last_stat_id);
				if (!$status) {
					$this->_log("Cannot record usage -- userid = {$last_owner_id}, credit_used = {$credit_used}, job_id = {$last_job_id}, stat_id = {$last_stat_id}");
					return false;
				}
			}
		// -----

		return true;
	}

	/**
	 * _ProcessJob_Send
	 * Send campaign for trigger email
	 *
	 * This function will return an associative array with the following value:
	 * - error => Boolean => Indicates whether or not the function is successful
	 * - halt => Boolean => Indicates whether or not to halt the operation
	 *
	 * @param Array $queue Queue record
	 * @param Array $trigger Trigger record
	 * @param Integer $recipientid Recipient ID
	 * @return Array Returns an array of the status (see comment above)
	 */
	private function _ProcessJob_Send($queue, $trigger, $recipientid)
	{
		$return = array(
			'error' => true,
			'halt' => false
		);

		// ----- Check if trigger has been previously send to the same email recently
			require_once(dirname(__FILE__) . '/subscribers.php');
			$subscribersapi = new Subscribers_API();
			$email = $subscribersapi->GetEmailForSubscriber($recipientid);
			if (!$email) {
				$this->_log('Unable to fetch email address from database');
				return $return;
			}

			$cutoff = (intval(time() / 86400) - 1) * 86400;

			$status = $this->Db->Query("
				SELECT		triggeremailsid
				FROM		[|PREFIX|]triggeremails_log
				WHERE		triggeremailsid = {$trigger['triggeremailsid']}
							AND action = 'send'
							AND timestamp > {$cutoff}
							AND note = '" . $this->Db->Quote($email) . "'
			");
			if (!$status) {
				$this->_log('Unable to check log');
				return $return;
			}

			$row = $this->Db->Fetch($status);
			$this->Db->FreeResult($status);

			// This particular email has been sent to this user before, so do not send it again
			if (!empty($row)) {
				$return['error'] = false;
				return $return;
			}
		// -----

		// Get newsletter record
		$newsletter = $this->_cacheNewsletterGet($trigger['triggeractions']['send']['newsletterid']);
		if ($newsletter === false) {
			$this->_log('Cannot get newsletter record with ID: ' . $trigger['triggeractions']['send']['newsletterid']);
			return $return;
		}

		// Process trigger queue only if newsletter is available and active
		if (empty($newsletter) || !$newsletter['active']) {
			$this->_log('Newsletter is marked as inactive');
			return $return;
		}

		// Send newsletter
		$result = $this->_send($trigger, $queue, $newsletter);

		// If it cannot be sent, return error
		if (!$result['result']) {
			$this->_log('Cannot send newsletter with queueid:' . $queue['queueid'] . ', and recipient:' . $recipientid);
			trigger_error('Trigger job error: Cannot send newsletter with queueid:' . $queue['queueid'] . ', and recipient:' . $recipientid);
			// Log failed sending
			$this->RecordLogActions($trigger['triggeremailsid'], $recipientid, 'send_failed', $result['email']);
            $return['halt'] = true;
			return $return;
		}

		// Record log
		if (!$this->RecordLogActions($trigger['triggeremailsid'], $recipientid, 'send', $result['email'])) {
			$this->_log('Cannot write log to the database... exitting');
			$return['halt'] = true;
			return $return;
		}

		// ----- Record statistic
			$tempFormat = $newsletter['format'];
			if ($tempFormat == 'b') {
				$tempFormat = 'm';
			}

			if ($this->_statsAPIObject->UpdateRecipient($trigger['statid'], $tempFormat) === false) {
				// Commit the transaction, as we do still want the record send to be recorded.
				$this->Db->CommitTransaction();
				$this->_log('Cannot update statistics... Exitting');
				$return['halt'] = true;
				return $return;
			}
		// -----

		$this->_log('Newsletter is sent (queueid:' . $queue['queueid'] . ', and recipient:' . $recipientid . ')');

		// ----- Trigger Event
			$tempEventData = new EventData_IEM_JOBSTRIGGEREMAILSAPI_PROCESSJOBSEND();
			$tempEventData->emailsent = true;
			$tempEventData->newsletter = $newsletter;
			$tempEventData->subscriberid = $recipientid;
			$tempEventData->listid = $result['listid'];
			$tempEventData->triggerrecord = $trigger;
			$tempEventData->trigger();

			unset($tempEventData);
		// -----

		$return['error'] = false;
		$return['halt'] = false;
		return $return;
	}

	/**
	 * _ProcessJob_RemoveList
	 * Add subscriber to another list(s)
	 *
	 * This function will return an associative array with the following value:
	 * - error => Boolean => Indicates whether or not the function is successful
	 * - halt => Boolean => Indicates whether or not to halt the operation
	 *
	 * @param Array $queue Queue record
	 * @param Array $trigger Trigger record
	 * @param Integer $subscriberid Subscriber ID to be copied to another list
	 * @return Array Returns an array of the status (see comment above)
	 */
	private function _ProcessJob_AddList($queueid, $trigger, $subscriberid)
	{
		$return = array(
			'error' => true,
			'halt' => false
		);

		$subscriberapi = new Subscribers_API();
		$listapi = new Lists_API();
		$subscriber_record = $subscriberapi->LoadSubscriberList($subscriberid);
        if(empty($subscriber_record)){
            trigger_error("Cannot check database for particular subscriber ({$subscriberid})");
            $this->_log("Cannot check database for particular subscriber ({$subscriberid})");
            $return['halt'] = true;
            $return['error'] = true;
            return $return;
        }

		$subscriber_customfields = (isset($subscriber_record['CustomFields']) && is_array($subscriber_record['CustomFields'])) ? $subscriber_record['CustomFields'] : array();

		$lists = $trigger['triggeractions']['addlist']['listid'];
		if (!is_array($lists)) {
			$lists = array($lists);
		}

		$this->Db->StartTransaction();
		foreach ($lists as $list) {
			if ($list == $subscriber_record['listid']) {
				continue;
			}

			$duplicate = $subscriberapi->IsSubscriberOnList($subscriber_record['emailaddress'], $list);

			if ($duplicate) {
				$unsubscribed_check = $subscriberapi->IsUnSubscriber(false, $list, $duplicate);
				if ($unsubscribed_check) {
					$this->_log('Cannot add contact to this list: Is already in the list as unsubscriber');
				} else {
					$this->_log('Cannot add contact to this list: Is already in the list');
				}
				continue;
			}

			list($banned, $msg) = $subscriberapi->IsBannedSubscriber($subscriber_record['emailaddress'], $list, false);
			if ($banned) {
				$this->_log('Cannot add contact to this list: Email is banned to be added to the list');
				continue;
			}


			// ----- Save subscriber and custom fields
				$this->Db->StartTransaction();

				$subscriberapi->confirmcode = false;
				$subscriberapi->confirmed = $subscriber_record['confirmed'];
				$subscriberapi->confirmdate = 0;
				$subscriberid = $subscriberapi->AddToList($subscriber_record['emailaddress'], $list);
				if (!$subscriberid) {
					$this->Db->RollbackTransaction();
					$this->_log('Cannot add contact to this list: API returned FALSE value');
					continue;
				}

				$ListCustomFields = $listapi->GetCustomFields($list);
				$allfieldok = true;

				if (!empty($ListCustomFields)) {
					$transferred = array();
					if (!empty($subscriber_customfields)) {
						// Match custom field
						foreach ($subscriber_customfields as $field) {
							// Found an exact match
							if (array_key_exists($field['fieldid'], $lists)) {
								$subscriberapi->SaveSubscriberCustomField($subscriberid, $field['fieldid'], $field['data']);
								$transferred[] = $field['fieldid'];
								continue;
							}

							// Check if there are any "name" and "type" match
							foreach ($ListCustomFields as $fieldid => $listfield) {
								if ((strtolower($listfield['name']) == strtolower($field['fieldname'])) && ($listfield['fieldtype'] == $field['fieldtype'])) {
									$subscriberapi->SaveSubscriberCustomField($subscriberid, $fieldid, $field['data']);
									$transferred[] = $field['fieldid'];
									continue;
								}
							}
						}
					}

					// Check if list required fields are all added in
					$allfieldok = true;
					foreach ($ListCustomFields as $fieldid => $field) {
						if ($field['required'] && !in_array($fieldid, $transferred)) {
							$allfieldok = false;
							break;
						}
					}
				}

				if ($allfieldok) {
					$this->Db->CommitTransaction();
				} else {
					$this->_log('Cannot add contact to this list: Not all of the required custom fields are available to copied across');
					$this->Db->RollbackTransaction();
					continue;
				}
			// -----
		}
		$this->Db->CommitTransaction();

		// Record log
		if (!$this->RecordLogActions($trigger['triggeremailsid'], $subscriberid, 'addlist')) {
			$this->_log('Cannot write log to the database...');
		}

		$return['error'] = false;
		return $return;
	}

	/**
	 * _ProcessJob_RemoveList
	 * Remove subscribers from list
	 *
	 * This function will return an associative array with the following value:
	 * - error => Boolean => Indicates whether or not the function is successful
	 * - halt => Boolean => Indicates whether or not to halt the operation
	 *
	 * @param Array $queue Queue record
	 * @param Array $trigger Trigger record
	 * @param Integer $subscriberid Subscriber ID to be removed
	 * @return Array Returns an array of the status (see comment above)
	 */
	private function _ProcessJob_RemoveList($queueid, $trigger, $subscriberid)
	{
		$return = array(
			'error' => true,
			'halt' => false
		);

		$subscriberapi = new Subscribers_API();
		list($status, $msg) = $subscriberapi->DeleteSubscriber('', 0, $subscriberid);
		if (!$status) {
			$this->_log('Unable to delete subscriber from list.. Reason given: ' . $msg);
			return $return;
		}

		// Record log
		if (!$this->RecordLogActions($trigger['triggeremailsid'], $subscriberid, 'removelist')) {
			$this->_log('Cannot write log to the database...');
		}

		$return['error'] = false;
		$return['halt'] = false;
		return $return;
	}

	/**
	 * ProcessPopulateJob
	 *
	 * This process should only be run once a day, as it will populate the queue table with
	 * records that needed to be sent out in the next 24 hours.
	 *
	 * This process does NOT send any emails, it only queue subscribers that match the trigger.
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function ProcessPopulateJob()
	{
		if (!$this->_populateQueueByCustomFields()) {
			return false;
		}

		if (!$this->_populateQueueByStaticDate()) {
			return false;
		}

		return true;
	}









	/**
	 * _cacheTriggerRecordClean
	 * Clean trigger record cache
	 * @return Void Returns nothing
	 */
	private function _cacheTriggerRecordClean()
	{
		$this->_triggerRecords = array();
	}

	/**
	 * _cacheTriggerRecordGet
	 *
	 * Return a trigger record from the database, and cache them to the memory
	 * so that subsequent calls requesting the same record will not require
	 * another database query.
	 *
	 * @param Integer $queueid Queue ID of the trigger email record
	 * @return Mixed Returns a record
	 */
	private function _cacheTriggerRecordGet($queueid)
	{
		static $jobid = null;

		$key = '_' . $queueid;

		if (is_null($jobid)) {
			$status = $this->Db->FetchOne("SELECT jobid FROM [|PREFIX|]jobs WHERE jobtype='triggeremails_send'", 'jobid');
			if (!$status) {
				$jobid = 0;
			} else {
				$jobid = intval($status);
			}
		}

		if (!array_key_exists($key, $this->_triggerRecords)) {
			$query = "
				SELECT *
				FROM [|PREFIX|]triggeremails
				WHERE queueid >= {$queueid}
				LIMIT 3
			";

			$status = $this->Db->Query($query);
			if (!$status) {
				list($msg, $errno) = $this->Db->GetError();
				trigger_error($msg, $errno);
				return false;
			}

			while ($row = $this->Db->Fetch($status)) {
				$row['jobid'] = $jobid;
				$this->_triggerRecords['_' . $row['queueid']] = $row;
			}

			$this->Db->FreeResult($status);

			if (!array_key_exists($key, $this->_triggerRecords)) {
				$this->_triggerRecords[$key] = null;
			}
		}

		if (!is_null($this->_triggerRecords[$key]) && (!array_key_exists('data', $this->_triggerRecords[$key]) || !array_key_exists('triggeractions', $this->_triggerRecords[$key]))) {
			$temp = $this->GetData($this->_triggerRecords[$key]['triggeremailsid']);
			if (!$temp) {
				$this->_triggerRecords[$key]['data'] = array();
			} else {
				$this->_triggerRecords[$key]['data'] = $temp[$this->_triggerRecords[$key]['triggeremailsid']];
			}

			$temp = $this->GetActions($this->_triggerRecords[$key]['triggeremailsid']);
			if (!$temp) {
				$this->_triggerRecords[$key]['triggeractions'] = array();
			} else {
				$this->_triggerRecords[$key]['triggeractions'] = $temp[$this->_triggerRecords[$key]['triggeremailsid']];
			}
		}

		return $this->_triggerRecords[$key];
	}

	/**
	 * _cacheNewsletterGet
	 *
	 * Return newsletter record and cache them in the memory so that subsequent calls
	 * requesting the same newsletter do not require a database query
	 *
	 * @param Integer $newsletterid Newsletter ID to be fetched
	 * @return Array Returns newsletter record
	 */
	private function _cacheNewsletterGet($newsletterid)
	{
		$newsletterid = intval($newsletterid);
		$key = '_' . $newsletterid;

		if (!array_key_exists($key, $this->_newsletterRecords)) {
			$status = $this->Db->Query('SELECT * FROM [|PREFIX|]newsletters WHERE newsletterid = ' . $newsletterid);
			if (!$status) {
				list($msg, $errno) = $this->Db->GetError();
				trigger_error($msg, $errno);
				return false;
			}

			$row = $this->Db->Fetch($status);
			if ($row) {
				$this->_newsletterRecords[$key] = $row;
			}

			$this->Db->FreeResult($status);

			if (!array_key_exists($key, $this->_newsletterRecords)) {
				$this->_newsletterRecords[$key] = null;
			}
		}

		return $this->_newsletterRecords[$key];
	}

	/**
	 * _cacheListGet
	 *
	 * Return subscriberlist record and cache them in the memory so that subsequent calls
	 * requesting the same subscriberlist do not require a database query
	 *
	 * @param Integer $listid List ID to be fetched
	 * @return Array Returns subscriberlist record
	 */
	private function _cacheListGet($listid)
	{
		$listid = intval($listid);
		$key = '_' . $listid;

		if (!array_key_exists($key, $this->_subscriberlistRecords)) {
			$status = $this->Db->Query('SELECT * FROM [|PREFIX|]lists WHERE listid = ' . $listid);
			if (!$status) {
				list($msg, $errno) = $this->Db->GetError();
				trigger_error($msg, $errno);
				return false;
			}

			$row = $this->Db->Fetch($status);
			if ($row) {
				$this->_subscriberlistRecords[$key] = $row;
			}

			$this->Db->FreeResult($status);

			if (!array_key_exists($key, $this->_subscriberlistRecords)) {
				$this->_subscriberlistRecords[$key] = null;
			}
		}

		return $this->_subscriberlistRecords[$key];
	}

	/**
	 * _cacheUser
	 *
	 * Get user from the database and cache them into the memory.
	 * For subsequent calls to get the same user, the code will not re-query the database again
	 *
	 * @param Integer $userid User ID
	 * @return User_API Returns user API
	 */
	private function _cacheUser($userid)
	{
		$userid = intval($userid);
		$key = '_' . $userid;

		if (!array_key_exists($key, $this->_userRecords)) {
			$user = GetUser($userid);
			if (!is_a($user, 'User_API')) {
				trigger_error('Cannot get user with ID: ' . $userid, E_USER_NOTICE);
				return false;
			}

			$this->_userRecords[$key] = $user;
		}

		return $this->_userRecords[$key];
	}

	/**
	 * _markQueueRecordAsProcessed
	 *
	 * @param Integer $queueid Queue ID to be marked as processed
	 * @param Integer $recipientid Recipient ID to be marked as processed
	 * @return Boolean Returns TRUE if successful, FALSE othwerwise
	 *
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses SENDSTUDIO_TABLEPREFIX
	 */
	private function _markQueueRecordAsProcessed($queueid, $recipientid)
	{
		$status = $this->Db->Query('UPDATE ' . SENDSTUDIO_TABLEPREFIX . "queues SET processed = '1' WHERE queueid=" . $queueid . ' AND recipient = ' . $recipientid);
		if (!$status) {
			list($msg, $errno) = $this->Db->GetError();
			trigger_error($msg, $errno);
			return false;
		}

		return true;
	}

	/**
	 * _send
	 *
	 * Send email campaign that is triggered by the "trigger email".
	 *
	 * @param Array $triggerrecord Trigger record
	 * @param Array $queuerecord Queue record
	 * @param Array $newsletter Newsletter record
	 * @return Array Returns a status result that contains the sending result, and the email address that get sent
	 */
	private function _send($triggerrecord, $queuerecord, $newsletter)
	{
		static $prevUserID = 0;
		static $prevQueueID = 0;
		static $userPause = 0;
		static $subscriberAPI = null;

		$triggerid = $triggerrecord['triggeremailsid'];
		$newsletterid = $newsletter['newsletterid'];
		$recipientid = $queuerecord['recipient'];
		$user = null;
		$email = null;
		$customfields = array();

		$return = array(
			'email'		=> '',
			'message'	=> '',
			'listid'	=> 0,
			'result'	=> false
		);

		if (is_null($subscriberAPI)) {
			$subscriberAPI = new Subscribers_API();
		}

		/**
		 * Get owner of trigger email
		 */
			$user = $this->_cacheUser($triggerrecord['ownerid']);
			if (empty($user)) {
				trigger_error('Cannot get trigger owner record', E_USER_NOTICE);
				return $return;
			}
		/**
		 * -----
		 */

		/**
		 * Get email object, and setup
		 * This will setup emails and setup everything that it needs to have according to the newsletter record.
		 */
			$email = new SS_Email_API();

			$email->Set('CharSet', SENDSTUDIO_CHARSET);
			if (!SENDSTUDIO_SAFE_MODE) {
				$email->Set('imagedir', TEMP_DIRECTORY . '/triggeremails.' . $triggerrecord['queueid']);
			} else {
				$email->Set('imagedir', TEMP_DIRECTORY . '/send');
			}

			if (SENDSTUDIO_FORCE_UNSUBLINK) {
				$email->ForceLinkChecks(true);
			}

			$email->Set('Subject', $newsletter['subject']);

			/**
			 * Setup attachments
			 */
				$email->ClearAttachments();
				$tempAttachments = SendStudio_Functions::GetAttachments('newsletters', $newsletterid, true);
				if ($tempAttachments) {
					$path = $tempAttachments['path'];
					$files = $tempAttachments['filelist'];
					foreach ($files as $p => $file) {
						$email->AddAttachment($path . DIRECTORY_SEPARATOR . $file);
					}
				}
			/**
			 * -----
			 */

			/**
			 * Set up the contents of the newsletter and the formatting (ie. multipart/html only/text only)
			 */
				$format = $newsletter['format'];

				$email->Set('Multipart', false);

				if ($format == 'b' || $format == 't') {
					if ($newsletter['textbody']) {
						$email->AddBody('text', $newsletter['textbody']);
						$email->AppendBody('text', $user->Get('textfooter'));
						$email->AppendBody('text', stripslashes(SENDSTUDIO_TEXTFOOTER));
					}
				}

				if ($format == 'b' || $format == 'h') {
					if ($newsletter['htmlbody']) {
						$email->AddBody('html', $newsletter['htmlbody']);
						$email->AppendBody('html', $user->Get('htmlfooter'));
						$email->AppendBody('html', stripslashes(SENDSTUDIO_HTMLFOOTER));
					}
				}

				if ($format == 'b') {
					if ($newsletter['textbody'] && $newsletter['htmlbody']) {
						$email->Set('Multipart', true);
					} else {
						$email->Set('Multipart', false);
					}
				}
			/**
			 * -----
			 */

			// Setup custom fields
			$customfields = $email->GetCustomFields();

			if (!is_a($email, 'SS_Email_API')) {
				trigger_error('Cannot instantiate email object to be used to send emails', E_USER_NOTICE);
				return $return;
			}

			$email->Debug = $this->_debugMode;
		/**
		 * -----
		 */


		/**
		 * Set up email object for any headers and SMTP server details.
		 * These setup will take account of each trigger records (ie. owner and user as well as stats and other sending related headers)
		 */
			/**
			 * Calculate pause time to make sure it complies with the throttling
			 */
				$pause = $pausetime = 0;
				if ($user->perhour > 0) {
					$pause = $user->perhour;
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
				$userPause = $pausetime;
				$this->_log('UserPause is set to ' . $pausetime);
			/**
			 * -----
			 */

			$email->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

			if ($user->smtpserver) {
				$email->SetSmtp($user->smtpserver, $user->smtpusername, $user->smtppassword, $user->smtpport);
			}

			// If queue has changed since previous send, update email class for queue related information
			$email->TrackOpens((array_key_exists('trackopens', $triggerrecord['triggeractions']['send']) && $triggerrecord['triggeractions']['send']['trackopens'] == '1'));
			$email->TrackLinks((array_key_exists('tracklinks', $triggerrecord['triggeractions']['send']) && $triggerrecord['triggeractions']['send']['tracklinks'] == '1'));

			$email->Set('statid',			$triggerrecord['statid']);

			$email->Set('FromName',			$triggerrecord['triggeractions']['send']['sendfromname']);
			$email->Set('FromAddress',		$triggerrecord['triggeractions']['send']['sendfromemail']);
			$email->Set('ReplyTo',			$triggerrecord['triggeractions']['send']['replyemail']);
			$email->Set('BounceAddress',	$triggerrecord['triggeractions']['send']['bounceemail']);

			$email->Set('EmbedImages',		((array_key_exists('embedimages', $triggerrecord['triggeractions']['send'])) && $triggerrecord['triggeractions']['send']['embedimages'] == '1'));
			$email->Set('Multipart',		((array_key_exists('multipart', $triggerrecord['triggeractions']['send'])) && $triggerrecord['triggeractions']['send']['multipart'] == '1'));
		/**
		 * -----
		 */


		/**
		 * Set up recipient
		 */
			$subscriberinfo = $subscriberAPI->LoadSubscriberList($recipientid, 0, true);
            
			if (empty($subscriberinfo)) {
				trigger_error('Cannot fetch recipient details', E_USER_NOTICE);
				return $return;
			}

			$listinfo = $this->_cacheListGet($subscriberinfo['listid']);
			if (empty($listinfo)) {
                trigger_error('Unable to load recipient list details', E_USER_NOTICE);
				return $return;
			}

			// List ID for the particualar subscriber
			$email->Set('listids', $subscriberinfo['listid']);

			$subscriberinfo['ipaddress'] = $subscriberinfo['confirmip'];
			if (!$subscriberinfo['ipaddress']) {
				$subscriberinfo['ipaddress'] = $subscriberinfo['requestip'];
			}
			if (!$subscriberinfo['ipaddress']) {
				$subscriberinfo['ipaddress'] = '';
			}

			$subscriberinfo['subscriberid'] = $recipientid;
			$subscriberinfo['newsletter'] = $triggerrecord['triggeractions']['send']['newsletterid'];
			$subscriberinfo['listid'] = $subscriberinfo['listid'];
			$subscriberinfo['statid'] = $triggerrecord['statid'];
			$subscriberinfo['listname'] = $listinfo['name'];
			$subscriberinfo['companyname'] = $listinfo['companyname'];
			$subscriberinfo['companyaddress'] = $listinfo['companyaddress'];
			$subscriberinfo['companyphone'] = $listinfo['companyphone'];

			if (!isset($subscriberinfo['CustomFields']) && empty($subscriberinfo['CustomFields'])) {
				$subscriberinfo['CustomFields'] = array();

				/**
				* If the subscriber has no custom fields coming from the database, then set up blank placeholders.
				* If they have no custom fields in the database, they have no records in the 'all_customfields' array - so we need to fill it up with blank entries.
				*/
				foreach ($customfields as $fieldid => $fieldname) {
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
			} else {
                require_once(SENDSTUDIO_API_DIRECTORY.'/customfields_date.php');
 
                foreach($subscriberinfo['CustomFields'] as $ar_key => $ar_value){
                    if($ar_value['fieldtype'] == 'date'){
                        $cfdateapi = new CustomFields_Date_API($ar_value['fieldid']);
                        $real_order = $cfdateapi->GetRealValue($ar_value['data']);
                        $subscriberinfo['CustomFields'][$ar_key]['data'] = $real_order;
                    }
                }           
			}

			// TODO refactor
				$name = '';
				$firstname_field = $triggerrecord['triggeractions']['send']['firstnamefield'];
				if ($firstname_field) {
					foreach ($subscriberinfo['CustomFields'] as $p => $details) {
						if ($details['fieldid'] == $firstname_field && $details['data'] != '') {
							$name = $details['data'];
							break;
						}
					}
				}

				$lastname_field = $triggerrecord['triggeractions']['send']['lastnamefield'];
				if ($lastname_field) {
					foreach ($subscriberinfo['CustomFields'] as $p => $details) {
						if ($details['fieldid'] == $lastname_field && $details['data'] != '') {
							$name .= ' ' . $details['data'];
							break;
						}
					}
				}

				if (trim($name) == '') {
					$name = false;
				}
			// --

	            // SetupDynamicContentFields
	            $dctEventListId = (is_array($subscriberinfo['listid']))?$subscriberinfo['listid']:array($subscriberinfo['listid']);
	            $dctEvent =new EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT();
	            $dctEvent->lists = $dctEventListId;
	            $dctEvent->info = array($subscriberinfo);
	            $dctEvent->trigger();
	            // --

			$email->ClearRecipients();
			$email->AddRecipient($subscriberinfo['emailaddress'], $name, $subscriberinfo['format'], $subscriberinfo['subscriberid']);
			$email->AddDynamicContentInfo($dctEvent->contentTobeReplaced);
			$email->AddCustomFieldInfo($subscriberinfo['emailaddress'], $subscriberinfo);

			$status = $email->Send(true, true);
			if (!$status['success']) {
				list($return['email'], $return['message']) = $status['fail'][0];
                trigger_error("Failed sending trigger email to {$return['email']}: {$return['message']}");
				return $return;
			}

			$return['listid'] = $subscriberinfo['listid'];
			$return['email'] = $subscriberinfo['emailaddress'];
		/**
		 * -----
		 */

		// Set previous user
		$prevUserID = $user->userid;

		// Set previous queue
		$prevQueueID = $triggerrecord['queueid'];

		$return['result'] = true;

		return $return;
	}

	/**
	 * _getCustomFieldValueForSubscriber
	 *
	 * This function is copied from Jobs_Autoresponder::SetupCustomFields.
	 * The function will fetch custom field values for specified subscribers
	 *
	 * @param Int $listid The list the subscribers are on. Passed to the GetAllSubscriberCustomFields method to restrict the search/loading.
	 * @param Array $custom_fields_to_replace The custom field id's to load/replace
	 * @param Array $recipients The recipients to load the custom fields for.
	 *
	 * @see Jobs_Send::SetupCustomFields
	 * @see Jobs_Autoresponders::SetupCustomFields
	 * @uses Subscriber_API::GetAllSubscriberCustomFields
	 *
	 * @return Array Returns a multidimensional array containing all subscribers and all of their custom field values.
	 */
	private function _getCustomFieldValueForSubscriber($listid, $custom_fields_to_replace, $recipients)
	{
		static $subscribersAPI = null;

		if (is_null($subscribersAPI)) {
			require_once (dirname(__FILE__) . '/subscribers.php');
			$subscribersAPI = new Subscribers_API();
		}

		$all_customfields = $subscribersAPI->GetAllSubscriberCustomFields($listid, $custom_fields_to_replace, $recipients);

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
	 * _populateQueueByCustomFields
	 * Populate queue table with "Custom Fields" based trigger emails
	 *
	 * This method will populate queue table with records that trigger needs to action against.
	 * Population of this queue is based on "Custom Fields" rule.
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	private function _populateQueueByCustomFields()
	{
		$now = time();
		$startOfDayString = date('Y-m-d 00:00:00', $now);
		$endOfDayString = date('Y-m-d 23:59:59', $now);
		$cutoff = (intval($now / 86400) - 1) * 86400;
		$year = date('Y', $now);

		switch (SENDSTUDIO_DATABASE_TYPE) {
			case 'mysql':
				$query = "
					INSERT INTO [|PREFIX|]queues (queueid, queuetype, ownerid, recipient, processed, sent, processtime)

					SELECT  t.queueid,
					        'triggeremail',
					        t.ownerid,
					        s.subscriberid,
					        0,
					        0,
					        date_add(str_to_date(CONCAT(LEFT(d.data, 6), '{$year}'), '%d/%m/%Y'), INTERVAL t.triggerhours HOUR)

					FROM    -- Select All of the required trigger emails information
					        (   SELECT  t.triggeremailsid       AS triggeremailsid,
					                    t.queueid               AS queueid,
					                    t.ownerid               AS ownerid,
					                    t.triggerhours          AS triggerhours,
					                    t.triggerinterval       AS triggerinterval,
					                    tdc.datavalueinteger    AS customfieldid,
					                    tdl.datavalueinteger    AS listid
					            FROM    [|PREFIX|]triggeremails AS t
					                        JOIN [|PREFIX|]triggeremails_data AS tdc
					                            ON (    tdc.triggeremailsid = t.triggeremailsid
					                                    AND tdc.datakey = 'customfieldid')
					                        JOIN [|PREFIX|]triggeremails_data AS tdl
					                            ON (    t.triggeremailsid = tdl.triggeremailsid
					                                    AND tdl.datakey = 'listid')
					            WHERE   t.active = '1'
					                    AND t.triggertype = 'f') AS t

					            -- Join with subscribers table, making sure that subscriber is not bounced and not unsubscribed
					            JOIN [|PREFIX|]list_subscribers AS s
					                ON (    s.listid = t.listid
					                        AND (s.bounced IS NULL OR s.bounced = 0)
					                        AND (s.unsubscribed IS NULL OR s.unsubscribed = 0))

					             -- Join with subscriber data to check custom field date against current date
					            JOIN [|PREFIX|]subscribers_data AS d
					                ON (    d.subscriberid = s.subscriberid
					                        AND d.fieldid = t.customfieldid
					                        AND d.data <> ''
					                        AND d.data IS NOT NULL
					                        AND d.data <> '0'
					                        AND RIGHT(d.data, 4) <= {$year}
					                        AND (  (    t.triggerinterval <> 0
					                                    AND date_add(str_to_date(CONCAT(LEFT(d.data, 6), '{$year}'), '%d/%m/%Y'), INTERVAL t.triggerhours HOUR) >= '{$startOfDayString}'
					                                    AND date_add(str_to_date(CONCAT(LEFT(d.data, 6), '{$year}'), '%d/%m/%Y'), INTERVAL t.triggerhours HOUR) <= '{$endOfDayString}')
					                                OR  (   t.triggerinterval = 0
					                                        AND date_add(str_to_date(d.data, '%d/%m/%Y'), INTERVAL t.triggerhours HOUR) >= '{$startOfDayString}'
					                                        AND date_add(str_to_date(d.data, '%d/%m/%Y'), INTERVAL t.triggerhours HOUR) <= '{$endOfDayString}')))

					            -- Left join with triggeremails recipients used to check whether or not subscribers have received a particular trigger or not
					            -- INCLUSION of a record for a subscriber will INDICATE that the SUBSCRIBER IS NOT ELIGIBLE to receive the trigger, because they either:
					            -- (1) Have received more than the specified interval
					            -- (2) Trigger has recently gone off (within 24 hours) for the particular subscriber
					            LEFT JOIN [|PREFIX|]triggeremails_log_summary AS lo
					                ON (    lo.triggeremailsid = t.triggeremailsid
					                        AND lo.subscriberid = s.subscriberid
					                        AND (   -- Exclude anything that doesn't have perpetual interval
					                                t.triggerinterval <> -1

					                                -- Or if it does have an interval, include record if the interval has already been exceeded
					                                OR (t.triggerinterval >= 0 AND lo.actionedoncount >= t.triggerinterval)

					                                -- Or include anything that has been sent out today
					                                OR (lo.lastactiontimestamp >= {$cutoff})))

					             -- Left join with queue table to make sure that the trigger is not currently being queued.
					            LEFT JOIN [|PREFIX|]queues AS q
					                ON (    q.queueid = t.queueid
					                        AND q.recipient = s.subscriberid)

					WHERE	q.queueid IS NULL
					        AND lo.triggeremailsid IS NULL
				";
			break;

			case 'pgsql':
				$query = "
					INSERT INTO [|PREFIX|]queues (queueid, queuetype, ownerid, recipient, processed, sent, processtime)

					SELECT  t.queueid,
					        'triggeremail',
					        t.ownerid,
					        s.subscriberid,
					        0,
					        0,
					        CAST(('{$year}-' || substr(d.data, 4, 2) || '-' || substr(d.data, 1, 2)) AS DATE) + CAST(t.triggerhours || ' hour' AS INTERVAL)

					FROM    -- Select All of the required trigger emails information
					        (   SELECT  t.triggeremailsid       AS triggeremailsid,
					                    t.queueid               AS queueid,
					                    t.ownerid               AS ownerid,
					                    t.triggerhours          AS triggerhours,
					                    t.triggerinterval       AS triggerinterval,
					                    tdc.datavalueinteger    AS customfieldid,
					                    tdl.datavalueinteger    AS listid
					            FROM    [|PREFIX|]triggeremails AS t
					                        JOIN [|PREFIX|]triggeremails_data AS tdc
					                            ON (    tdc.triggeremailsid = t.triggeremailsid
					                                    AND tdc.datakey = 'customfieldid')
					                        JOIN [|PREFIX|]triggeremails_data AS tdl
					                            ON (    t.triggeremailsid = tdl.triggeremailsid
					                                    AND tdl.datakey = 'listid')
					            WHERE   t.active = '1'
					                    AND t.triggertype = 'f') AS t

					            -- Join with subscribers table, making sure that subscriber is not bounced and not unsubscribed
					            JOIN [|PREFIX|]list_subscribers AS s
					                ON (    s.listid = t.listid
					                        AND (s.bounced IS NULL OR s.bounced = 0)
					                        AND (s.unsubscribed IS NULL OR s.unsubscribed = 0))

					             -- Join with subscriber data to check custom field date against current date
					            JOIN [|PREFIX|]subscribers_data AS d
					                ON (    d.subscriberid = s.subscriberid
					                        AND d.fieldid = t.customfieldid
					                        AND d.data <> ''
					                        AND d.data IS NOT NULL
					                        AND d.data <> '0'
					                        AND TO_DATE(d.data, 'DD/MM/YYYY') <= '{$year}-01-01 00:00:00'
					                        AND (  (    t.triggerinterval <> 0
					                                    AND (TO_DATE(CAST(('{$year}-' || date_part('month', TO_DATE(d.data, 'DD/MM/YYYY')) || '-' || date_part('day', TO_DATE(d.data, 'DD/MM/YYYY'))) AS VARCHAR), 'YYYY-MM-DD') + CAST(t.triggerhours || ' hour' AS INTERVAL)) >= '{$startOfDayString}'
					                                    AND (TO_DATE(CAST(('{$year}-' || date_part('month', TO_DATE(d.data, 'DD/MM/YYYY')) || '-' || date_part('day', TO_DATE(d.data, 'DD/MM/YYYY'))) AS VARCHAR), 'YYYY-MM-DD') + CAST(t.triggerhours || ' hour' AS INTERVAL)) <= '{$endOfDayString}'
					                                OR  (   t.triggerinterval = 0
					                                        AND (TO_DATE(d.data, 'DD/MM/YYYY') + CAST(t.triggerhours || ' hour' AS INTERVAL)) >= '{$startOfDayString}'
					                                        AND (TO_DATE(d.data, 'DD/MM/YYYY') + CAST(t.triggerhours || ' hour' AS INTERVAL)) <= '{$endOfDayString}'))))

					            -- Left join with triggeremails recipients used to check whether or not subscribers have received a particular trigger or not
					            -- INCLUSION of a record for a subscriber will INDICATE that the SUBSCRIBER IS NOT ELIGIBLE to receive the trigger, because they either:
					            -- (1) Have received more than the specified interval
					            -- (2) Trigger has recently gone off (within 24 hours) for the particular subscriber
					            LEFT JOIN [|PREFIX|]triggeremails_log_summary AS lo
					                ON (    lo.triggeremailsid = t.triggeremailsid
					                        AND lo.subscriberid = s.subscriberid
					                        AND (   -- Exclude anything that doesn't have perpetual interval
					                                t.triggerinterval <> -1

					                                -- Or if it does have an interval, include record if the interval has already been exceeded
					                                OR (t.triggerinterval >= 0 AND lo.actionedoncount >= t.triggerinterval)

					                                -- Or include anything that has been sent out today
					                                OR (lo.lastactiontimestamp >= {$cutoff})))

					             -- Left join with queue table to make sure that the trigger is not currently being queued.
					            LEFT JOIN [|PREFIX|]queues AS q
					                ON (    q.queueid = t.queueid
					                        AND q.recipient = s.subscriberid)

					WHERE	q.queueid IS NULL
					        AND lo.triggeremailsid IS NULL
				";
			break;

			default:
				die ('Unknown database type');
			break;
		}

		if (!$this->Db->Query(trim($query))) {
			list($msg, $errno) = $this->Db->GetError();
			$this->_log('Cannot populate queue table with custom field based triggeremails: ' . $msg . "\n\nThe query was: {$query}");
			trigger_error($msg, $errno);
			return false;
		}

		return true;
	}

	/**
	 * _populateQueueByStaticDate
	 * Populate queue table with "Static Date" based trigger emails
	 *
	 * This method will populate queue table with records that trigger needs to action against.
	 * Population of this queue is based on "Static Date" rule.
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	private function _populateQueueByStaticDate()
	{
		$now = time();
		$startOfDayString = date('Y-m-d 00:00:00', $now);
		$endOfDayString = date('Y-m-d 23:59:59', $now);
		$cutoff = (intval($now / 86400) - 1) * 86400;
		$year = date('Y', $now);

		switch (SENDSTUDIO_DATABASE_TYPE) {
			case 'mysql':
				$query = "
					INSERT INTO [|PREFIX|]queues (queueid, queuetype, ownerid, recipient, processed, sent, processtime)

					SELECT  t.queueid,
					        'triggeremail',
					        t.ownerid,
					        s.subscriberid,
					        0,
					        0,
					        date_add(str_to_date(CONCAT('{$year}', RIGHT(t.staticdate, 6)), '%Y-%m-%d'), INTERVAL t.triggerhours HOUR)

					FROM    -- Select All of the required trigger emails information
					        (   SELECT  t.triggeremailsid       AS triggeremailsid,
					                    t.queueid               AS queueid,
					                    t.ownerid               AS ownerid,
					                    t.triggerhours          AS triggerhours,
					                    t.triggerinterval       AS triggerinterval,
					                    td.datavaluestring      AS staticdate,
					                    tdl.datavalueinteger    AS listid
					            FROM    [|PREFIX|]triggeremails AS t
					                        JOIN [|PREFIX|]triggeremails_data AS td
					                            ON (    td.triggeremailsid = t.triggeremailsid
					                                    AND td.datakey = 'staticdate'
					                                    AND  LEFT(td.datavaluestring, 4) <= {$year}
					                                    AND (   (   t.triggerinterval <> 0
					                                                AND date_add(str_to_date(CONCAT('{$year}', RIGHT(td.datavaluestring, 6)), '%Y-%m-%d'), INTERVAL t.triggerhours HOUR) >= '{$startOfDayString}'
					                                                AND date_add(str_to_date(CONCAT('{$year}', RIGHT(td.datavaluestring, 6)), '%Y-%m-%d'), INTERVAL t.triggerhours HOUR) <= '{$endOfDayString}')
					                                            OR (    t.triggerinterval = 0
					                                                    AND date_add(str_to_date(td.datavaluestring, '%Y-%m-%d'), INTERVAL t.triggerhours HOUR) >= '{$startOfDayString}'
					                                                    AND date_add(str_to_date(td.datavaluestring, '%Y-%m-%d'), INTERVAL t.triggerhours HOUR) <= '{$endOfDayString}')))
					                        JOIN [|PREFIX|]triggeremails_data AS tdl
					                            ON (    tdl.triggeremailsid = t.triggeremailsid
					                                    AND tdl.datakey = 'staticdate_listids')
					            WHERE   t.active = '1'
					                    AND t.triggertype = 's') AS t

					            -- Join with subscribers table, making sure that subscriber is not bounced and not unsubscribed
					            JOIN [|PREFIX|]list_subscribers AS s
					                ON (    s.listid = t.listid
					                        AND (s.bounced IS NULL OR s.bounced = 0)
					                        AND (s.unsubscribed IS NULL OR s.unsubscribed = 0))

					            -- Left join with triggeremails recipients used to check whether or not subscribers have received a particular trigger or not
					            -- INCLUSION of a record for a subscriber will INDICATE that the SUBSCRIBER IS NOT ELIGIBLE to receive the trigger, because they either:
					            -- (1) Have received more than the specified interval
					            -- (2) Trigger has recently gone off (within 24 hours) for the particular subscriber
					            LEFT JOIN [|PREFIX|]triggeremails_log_summary AS lo
					                ON (    lo.triggeremailsid = t.triggeremailsid
					                        AND lo.subscriberid = s.subscriberid
					                        AND (   -- Exclude anything that doesn't have perpetual interval
					                                t.triggerinterval <> -1

					                                -- Or if it does have an interval, include record if the interval has already been exceeded
					                                OR (t.triggerinterval >= 0 AND lo.actionedoncount >= t.triggerinterval)

					                                -- Or include anything that has been sent out today
					                                OR (lo.lastactiontimestamp >= {$cutoff})))

					             -- Left join with queue table to make sure that the trigger is not currently being queued.
					            LEFT JOIN [|PREFIX|]queues AS q
					                ON (    q.queueid = t.queueid
					                        AND q.recipient = s.subscriberid)

					WHERE	q.queueid IS NULL
					        AND lo.triggeremailsid IS NULL
				";
			break;

			case 'pgsql':
				$query = "
					INSERT INTO [|PREFIX|]queues (queueid, queuetype, ownerid, recipient, processed, sent, processtime)

					SELECT  t.queueid,
					        'triggeremail',
					        t.ownerid,
					        s.subscriberid,
					        0,
					        0,
					        CAST(('{$year}' || substr(t.staticdate, 4)) AS DATE) + CAST(t.triggerhours || ' hour' AS INTERVAL)

					FROM    -- Select All of the required trigger emails information
					        (   SELECT  t.triggeremailsid       AS triggeremailsid,
					                    t.queueid               AS queueid,
					                    t.ownerid               AS ownerid,
					                    t.triggerhours          AS triggerhours,
					                    t.triggerinterval       AS triggerinterval,
					                    td.datavaluestring      AS staticdate,
					                    tdl.datavalueinteger    AS listid
					            FROM    [|PREFIX|]triggeremails AS t
					                        JOIN [|PREFIX|]triggeremails_data AS td
					                            ON (    td.triggeremailsid = t.triggeremailsid
					                                    AND td.datakey = 'staticdate'
					                                    AND  CAST(substr(td.datavaluestring, 1, 4) AS INTEGER) <= {$year}
					                                    AND (   (   t.triggerinterval <> 0
					                                                AND CAST(('{$year}' || substr(td.datavaluestring, 4)) AS DATE) + CAST(t.triggerhours || ' hour' AS INTERVAL) >= '{$startOfDayString}'
					                                                AND CAST(('{$year}' || substr(td.datavaluestring, 4)) AS DATE) + CAST(t.triggerhours || ' hour' AS INTERVAL) <= '{$endOfDayString}')
					                                            OR (    t.triggerinterval = 0
					                                                    AND CAST(td.datavaluestring AS DATE) + CAST(t.triggerhours || ' hour' AS INTERVAL) >= '{$startOfDayString}'
					                                                    AND CAST(td.datavaluestring AS DATE) + CAST(t.triggerhours || ' hour' AS INTERVAL) <= '{$endOfDayString}')))
					                        JOIN [|PREFIX|]triggeremails_data AS tdl
					                            ON (    tdl.triggeremailsid = t.triggeremailsid
					                                    AND tdl.datakey = 'staticdate_listids')
					            WHERE   t.active = '1'
					                    AND t.triggertype = 's') AS t

					            -- Join with subscribers table, making sure that subscriber is not bounced and not unsubscribed
					            JOIN [|PREFIX|]list_subscribers AS s
					                ON (    s.listid = t.listid
					                        AND (s.bounced IS NULL OR s.bounced = 0)
					                        AND (s.unsubscribed IS NULL OR s.unsubscribed = 0))

					            -- Left join with triggeremails recipients used to check whether or not subscribers have received a particular trigger or not
					            -- INCLUSION of a record for a subscriber will INDICATE that the SUBSCRIBER IS NOT ELIGIBLE to receive the trigger, because they either:
					            -- (1) Have received more than the specified interval
					            -- (2) Trigger has recently gone off (within 24 hours) for the particular subscriber
					            LEFT JOIN [|PREFIX|]triggeremails_log_summary AS lo
					                ON (    lo.triggeremailsid = t.triggeremailsid
					                        AND lo.subscriberid = s.subscriberid
					                        AND (   -- Exclude anything that doesn't have perpetual interval
					                                t.triggerinterval <> -1

					                                -- Or if it does have an interval, include record if the interval has already been exceeded
					                                OR (t.triggerinterval >= 0 AND lo.actionedoncount >= t.triggerinterval)

					                                -- Or include anything that has been sent out today
					                                OR (lo.lastactiontimestamp >= {$cutoff})))

					             -- Left join with queue table to make sure that the trigger is not currently being queued.
					            LEFT JOIN [|PREFIX|]queues AS q
					                ON (    q.queueid = t.queueid
					                        AND q.recipient = s.subscriberid)

					WHERE	q.queueid IS NULL
					        AND lo.triggeremailsid IS NULL
				";
			break;

			default:
				die ('Unknown database type');
			break;
		}

		if (!$this->Db->Query(trim($query))) {
			list($msg, $errno) = $this->Db->GetError();
			$this->_log('Cannot populate queue table with static date based triggeremails: ' . $msg . "\n\nThe query was: {$query}");
			trigger_error($msg, $errno);
			return false;
		}

		return true;
	}

	/**
	 * _checkInterval
	 * Check whether or not trigger email can still go to a particular subscriber.
	 *
	 * Since trigger emails have an "interval" (Which is the limit of which a trigger can be actioned for a particular user),
	 * we do not want to exceed this limit.
	 *
	 * @param Array $triggerrecord An associative array of the trigger record
	 * @param Integer $subscriberid Subscriber ID
	 *
	 * @return Boolean Returns TRUE if trigger can be actioned on this particular subscriber, FALSE othwerwise
	 */
	private function _checkInterval($triggerrecord, $subscriberid)
	{
		$triggerid = intval($triggerrecord['triggeremailsid']);
		$subscriberid = intval($subscriberid);

		$query = "SELECT * FROM [|PREFIX|]triggeremails_log_summary WHERE triggeremailsid = {$triggerid} AND subscriberid = {$subscriberid}";
		$result = $this->Db->Query($query);
		if (!$result) {
			list($msg, $errno) = $this->Db->GetError();
			trigger_error($msg, $errno);
			return false;
		}

		$row = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		if (!$row) {
			return true;
		}

		if (in_array($triggerrecord['triggertype'], array('l', 'n'))) {
			$triggerrecord['triggerinterval'] = 0;
		}

		// ----- Check if interval limit has been reaced
			switch ($triggerrecord['triggerinterval']) {
				// Perpetual interval, so this is always FALSE (ie. Never reaches it's limit)
				case -1:
					// Does nothing
				break;

				// Do this once
				case 0:
					if ($row['actionedoncount'] >= 1) {
						return false;
					}
				break;

				// Do this pre-defined time
				default:
					if ($row['actionedoncount'] >= $triggerrecord['triggerinterval']) {
						return false;
					}
				break;
			}
		// -----


		// ----- Make sure that the trigger weren't beeing triggered too close together
			$cutoff = (intval(time() / 86400) - 1) * 86400;

			if ($row['lastactiontimestamp'] > $cutoff) {
				return false;
			}
		// -----

		return true;
	}

	/**
	 * _log
	 * Print debug message if debug mode is ON
	 * @param String $msg Debug message
	 * @return Void Returns nothing
	 */
	private function _log($msg)
	{
		if (!$this->_debugMode) {
			return;
		}

		if (empty($this->_logFile)) {
			$this->_logFile = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'triggeremails_debug.' . getmypid() . '.log';
		}

		$prev = array();
		if (is_callable('debug_backtrace')) {
			$trace = debug_backtrace();
			if (count($trace) > 1) {
				$prev = $trace[0];
			}
		}

		$str = "=======================================================\n";
		$str .= 'Time: ' . date('r');
		if (isset($prev['file'])) {
			$str .= "\tFile: " . $prev['file'];
		}
		if (isset($prev['line'])) {
			$str .= " (Line: {$prev['line']})";
		}
		$str .= "\n{$msg}\n";
		$str .= "\n";

		error_log($str, 3, $this->_logFile);
	}
}