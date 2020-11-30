<?php
/**
* This file has the sending page in it. This only handles sending and scheduling of newsletters.
*
* @version     $Id: schedule.php,v 1.42 2008/01/09 23:19:06 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for management of sending newsletters.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Schedule extends SendStudio_Functions
{

	/**
	* Constructor
	* Loads the schedule, newsletters and send language files.
	*
	* @see LoadLanguageFile
	*
	* @return Void Doesn't return anything.
	*/
	function Schedule()
	{
		$this->LoadLanguageFile();
		$this->LoadLanguageFile('Newsletters');
		$this->LoadLanguageFile('Send');
	}

	/**
	* Process
	* This handles working out what stage you are up to and so on with workflow.
	* Handles editing of schedules, pausing, resuming and deleting of schedules.
	* Deleting a scheduled event (especially) needs to update statistics if there are any emails left over in the queue.
	*
	* @see GetUser
	* @see User_API::HasAccess
	* @see SENDSTUDIO_CRON_ENABLED
	* @see GetApi
	* @see Jobs_API::PauseJob
	* @see Jobs_API::ResumeJob
	* @see Jobs_API::LoadJob
	* @see ManageSchedules
	* @see CheckJob
	* @see AdjustTime
	*/
	function Process()
	{
		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;
		if (!SENDSTUDIO_CRON_SEND) {
			$popup = (in_array($action, $this->PopupWindows)) ? true : false;
			$this->PrintHeader($popup);
			$GLOBALS['Error'] = GetLang('CronNotEnabled');
			$this->ParseTemplate('ErrorMsg');
			$this->PrintFooter();
			return;
		}
		
		$user = GetUser();
		$access = $user->HasAccess('Newsletters', 'Send');

		$popup = (in_array($action, $this->PopupWindows)) ? true : false;
		$this->PrintHeader($popup);

		if (!$access) {
			$this->DenyAccess();
			return;
		}

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = '';
		}

		$jobapi = $this->GetApi('Jobs');

		$approve_job = IEM::sessionGet('ApproveJob');
		if ($approve_job) {
			if (isset($_GET['A'])) {
				$jobapi->ApproveJob($approve_job, $user->Get('userid'));
				$GLOBALS['Message'] = $this->PrintSuccess('JobScheduledOK');
				IEM::sessionRemove('ApproveJob');
			}
		}

		$jobid = 0;
		if (isset($_GET['job'])) {
			$jobid = (int)$_GET['job'];
		}

		switch ($action) {
			/**
			 * These methods are all called the same thing:
			 * 'ActionJob'
			 * The 'action' has an upper-case first letter
			 * then 'Job' has the same.
			 * They also all just take the jobid as the argument.
			 */
			case 'approve':
			case 'edit':
			case 'pause':
			case 'resend':
			case 'resume':
			case 'update':
				if (!$this->CanAccessJobs($jobid)) {
					$this->DenyAccess();
					return false;
				}

				$method = ucwords($action) . 'Job';
				call_user_func(array($this, $method), $jobid);
			break;

			case 'delete':
				$jobids = array();

				if (isset($_POST['jobs'])) {
					$jobids = $_POST['jobs'];
				} else {
					$jobids[] = $jobid;
				}

				if (!$this->CanAccessJobs($jobids)) {
					$this->DenyAccess();
					return false;
				}

				$this->DeleteJobs($jobids);

			break;

			default:
				$this->ManageSchedules();
		}
		$this->PrintFooter($popup);
	}

	/**
	* ManageSchedules
	* Prints a list of scheduled events (only newsletters at this stage but could be expanded).
	* This allows you to edit, delete, pause and resume events.
	*
	* @see GetUser
	* @see GetApi
	* @see GetPerPage
	* @see GetSortDetails
	* @see User_API::GetLists
	* @see User_API::HasAccess
	* @see Jobs_API::GetJobList
	* @see SetupPaging
	* @see Jobs_API::GetJobStatus
	* @see PrintDate
	*/
	function ManageSchedules()
	{
		$user = GetUser();
		$jobsApi = $this->GetApi('Jobs');

		$settingsApi = $this->GetApi('Settings');
		$cron_check = $settingsApi->CheckCron();

		$cron_run1 = $settingsApi->Get('cronrun1');
		$cron_run2 = $settingsApi->Get('cronrun2');

		if ($cron_run1 > 0 && $cron_run2 > 0) {
			$schedule = $settingsApi->Get('Schedule');
			$send_schedule = $schedule['send'];
			$cron_diff = SENDSTUDIO_CRON_SEND * 60;

			$next_run = $send_schedule['lastrun'] + ($cron_diff);

			$now = AdjustTime();
			$time_to_wait = $next_run - $now;
			$update_cron_time = 'true';
		} else {
			$time_to_wait = GetLang('Unknown');
			$cron_diff = 0;
			$next_run = 0;
			$update_cron_time = 'false';
		}

		$this->DisplayCronWarning(false);

		if ($update_cron_time && $cron_diff > 0) {
			while ($time_to_wait < 0) {
				$time_to_wait += $cron_diff;
			}
		}

		$GLOBALS['UpdateCronTime'] = $update_cron_time;
		$GLOBALS['CronTimeLeft'] = $time_to_wait;
		$GLOBALS['CronTimeDifference'] = $cron_diff;

		$perpage = $this->GetPerPage();

		$DisplayPage = $this->GetCurrentPage();
		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$lists = $user->GetLists();

		$listids = array_keys($lists);

		if ($user->HasAccess('Newsletters', 'Send')) {
			$GLOBALS['Newsletters_SendButton'] = $this->ParseTemplate('Newsletter_Send_Button', true, false);
		}

		$include_unapproved = false;
		if ($user->Admin()) {
			$include_unapproved = true;
			$numJobs = $jobsApi->GetJobList('send', 'newsletter', $listids, true, 0, 0, $include_unapproved, false);
		} else {
			$numJobs = $jobsApi->GetJobList('send', 'newsletter', $listids, true, 0, 0, $include_unapproved, false, $user->userid);
		}

		if ($numJobs < 1) {
			if ($user->HasAccess('Newsletters', 'Send')) {
				$msg = $this->PrintSuccess('Schedule_Empty', GetLang('NoSchedules_HasAccess'));
			} else {
				$msg = $this->PrintSuccess('Schedule_Empty', '');
			}
			if (!isset($GLOBALS['Message'])) {
				$GLOBALS['Message'] = '';
			}
			$GLOBALS['Message'] .= $msg;
			echo $this->ParseTemplate('Schedule_Manage_Row_Empty', true, false);
			return;
		}

		if ($user->Admin()) {
			$jobs = $jobsApi->GetJobList('send', 'newsletter', $listids, false, $start, $perpage, $include_unapproved, false);
		} else {
			$jobs = $jobsApi->GetJobList('send', 'newsletter', $listids, false, $start, $perpage, $include_unapproved, false, $user->userid);
		}
		$this->SetupPaging($numJobs, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=ProcessPaging';
		$paging = $this->ParseTemplate('Paging', true, false);

		$manage_row = '';

		$rid = 0;

		foreach ($jobs as $p => $details) {
			$rid++;
			$GLOBALS['JobID'] = (int)$details['jobid'];
			$GLOBALS['Status'] = $jobsApi->GetJobStatus($details['jobstatus']);
			$GLOBALS['JobTime'] = $this->PrintTime($details['jobtime'], true);
			$GLOBALS['ListName'] = htmlspecialchars($details['listname'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['NewsletterName'] = htmlspecialchars($details['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);

			$GLOBALS['NewsletterSubject'] = '';
			if ($details['subject'] !== null) {
				$GLOBALS['NewsletterSubject'] = ' - ' . htmlspecialchars($details['subject'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}

			$GLOBALS['NewsletterType'] = GetLang('Schedule_NewsletterType_Newsletter');
			if ($details['jobdescription'] !== null) {
				$GLOBALS['NewsletterType'] = htmlspecialchars($details['jobdescription'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			}

			$action = sprintf(GetLang('Schedule_ViewNewsletter'), 'index.php?Page=Newsletters&Action=View&id=' . (int)$details['newsletterid']);
			if ($details['jobtype'] != 'send') {
				$action = '';
			}

			$GLOBALS['RowID'] = $rid;

			$GLOBALS['RefreshLink'] = $GLOBALS['AlreadySent'] = '';

			/**
			 * If a job is not 'p'aused or 'c'omplete, or 'i'n progress,
			 * show the "countdown" for when it will send.
			 *
			 * This allows addons to have their own "status" and still show a countdown for when it will send.
			 */
			if (!in_array($details['jobstatus'], array ('p', 'c', 'i'))) {
				if ($details['jobstatus'] == 'w' && ($user->Admin() && $details['approved'] <= 0)) {
					$GLOBALS['TipName'] = $this->GetRandomId();
					$GLOBALS['ScheduleTip'] = GetLang('WaitingForApproval_Description');

					$need_approval_tip = $this->ParseTemplate('Schedule_Needs_Approval_Tip', true, false);
					$GLOBALS['Status'] = $need_approval_tip;

					$action .= $this->ParseTemplate('Schedule_Manage_Row_ApproveLink', true, false);

					$action .= $this->ParseTemplate('Schedule_Manage_Row_DeleteLink', true, false);

					$GLOBALS['Action'] = $action;

					$manage_row .= $this->ParseTemplate('Schedule_Manage_Row', true, false);
					continue;
				}

				if ($next_run > 0) {
                                        $send_in_time_difference = $details['jobtime'] - $jobsApi->GetGmtTime();

					// if we're below 0, that means we should send next time cron runs.
					if ($send_in_time_difference < 0) {
						$send_in_time_difference = $time_to_wait;
					}

					if ($next_run > 0 && $details['jobtime'] <= $next_run) {
						$send_in_time_difference = $next_run - $jobsApi->GetGmtTime();
					}

					while ($send_in_time_difference < 0) {
						$send_in_time_difference += $cron_diff;
					}

					if ($send_in_time_difference > 0) {
						$have_refreshed = (isset($_GET['R'])) ? 1 : 0;

						$GLOBALS['RefreshLink'] = "<script>UpdateMyTimer('" . $GLOBALS['JobID'] . "_" . $rid . "', " . (int)$send_in_time_difference . ", " . $have_refreshed . ");</script>";
					} else {
						if ($details['jobstatus'] == 'w') {
							$GLOBALS['Status'] = GetLang('WaitingToSend');
						}
					}
				} else {
					if ($details['jobstatus'] == 'w') {
						$GLOBALS['Status'] = GetLang('WaitingToSend');
					}
				}
			}

			if ($details['jobstatus'] == 'i') {
				if ($user->Admin() && $details['approved'] <= 0) {
					$GLOBALS['TipName'] = $this->GetRandomId();
					$GLOBALS['ScheduleTip'] = GetLang('WaitingForApproval_Description');

					$need_approval_tip = $this->ParseTemplate('Schedule_Needs_Approval_Tip', true, false);
					$GLOBALS['Status'] = $need_approval_tip;

					$action .= $this->ParseTemplate('Schedule_Manage_Row_ApproveLink', true, false);

					$action .= $this->ParseTemplate('Schedule_Manage_Row_DeleteLink', true, false);

					$GLOBALS['Action'] = $action;

					$manage_row .= $this->ParseTemplate('Schedule_Manage_Row', true, false);
					continue;
				}

				$queueinfo = $jobsApi->GetJobQueueSize($details['jobid']);

				if (!empty($queueinfo)) {
					$GLOBALS['AlreadySent'] = sprintf(GetLang('AlreadySentTo'), $this->FormatNumber($queueinfo['totalsent']), $this->FormatNumber($queueinfo['sendsize']));
				}
				$GLOBALS['RefreshDisplayPage'] = $DisplayPage;
				$action .= $this->ParseTemplate('Schedule_Manage_Row_RefreshLink', true, false);
			}

			// if the job is paused, we can resume it.
			if ($details['jobstatus'] == 'p') {
				$action .= $this->ParseTemplate('Schedule_Manage_Row_ResumeLink', true, false);
			}

			// if the job is in progress we can pause it.
			// or if it's in progress.
			// that will allow us to delay it without losing all of the info....
			if ($details['jobstatus'] == 'i' || $details['jobstatus'] == 'w' || $details['jobstatus'] == 'r') {
				$action .= $this->ParseTemplate('Schedule_Manage_Row_PauseLink', true, false);
			}

			// if it's not in progress, we can edit or delete the scheduled event.
			// but only if it has not started yet (the queueid will be > 0 for started events).
			if ($details['jobstatus'] != 'i' || $details['jobstatus'] != 'r') {
				if ($details['queueid'] == 0 && $details['jobstatus'] != 'c') {
					$action .= $this->ParseTemplate('Schedule_Manage_Row_EditLink', true, false);
				}
				$action .= $this->ParseTemplate('Schedule_Manage_Row_DeleteLink', true, false);
			}

			$GLOBALS['Action'] = $action;

			$manage_row .= $this->ParseTemplate('Schedule_Manage_Row', true, false);
		}

		$template = $this->ParseTemplate('Schedule_Manage', true, false);
		$template = str_replace('%%TPL_Schedule_Manage_Row%%', $manage_row, $template);

		$template = str_replace('%%TPL_Paging%%', $paging, $template);
		$template = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $template);

		echo $template;
	}

	/**
	* EditJob
	* Allows you to edit a job's send time and view other details (which list(s), newsletter).
	*
	* @param Int $jobid JobId to edit.
	*
	* @see CheckJob
	* @see GetApi
	* @see Jobs_API::Load
	* @see GetUser
	* @see Newsletters_API::Load
	* @see Lists_API::Load
	* @see CreateDateTimeBox
	*
	* @return Void Prints out the form for editing, doesn't return anything.
	*
	* @uses EventData_IEM_SCHEDULE_EDITJOB
	*/
	function EditJob($jobid=0)
	{
		$check = $this->CheckJob($jobid);
		if (!$check) {
			return false;
		}

		$api = $this->GetApi('Jobs');
		$job = $api->LoadJob($jobid);

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SCHEDULE_EDITJOB();
			$tempEventData->jobrecord = &$job;

			if (!$tempEventData->trigger()) {
				return;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$user = GetUser();
		$user_lists = $user->GetLists();

		$newslettername = '';
		$newsletterApi = $this->GetApi('Newsletters');
		$newsletterApi->Load($job['jobdetails']['Newsletter']);
		$newslettername = $newsletterApi->Get('name');

		$GLOBALS['JobID'] = $jobid;

		$listdetails = array();
		foreach ($job['jobdetails']['Lists'] as $l => $listid) {
			$listdetails[] = htmlspecialchars($user_lists[$listid]['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		}
		$listnames = implode(', ', $listdetails);

		$GLOBALS['NewsletterID'] = $newsletterApi->Get('newsletterid');
		$GLOBALS['Send_NewsletterName'] = sprintf(GetLang('Send_NewsletterName'), htmlspecialchars($newslettername, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['Send_SubscriberList'] = sprintf(GetLang('Send_SubscriberList'), $listnames);

		$GLOBALS['SendTimeBox'] = $this->CreateDateTimeBox($job['jobtime']);

		$this->ParseTemplate('Schedule_Edit');
	}

	/**
	* CheckJob
	* Makes sure the job you're trying to access is valid. It also makes sure it's not in progress.
	*
	* @param Int $jobid The job to check.
	*
	* @see ManageSchedules
	* @see GetApi
	* @see Jobs_API::LoadJob
	*
	* @return Boolean Returns false if there is a problem with the job (it's not valid or we can't load it). It also returns false if the job is in progress - it will print a message out and then print the schedule list.
	*/
	function CheckJob($jobid=0)
	{
		$jobid = (int)$jobid;
		if ($jobid <= 0) {
			$GLOBALS['Error'] = GetLang('JobDoesntExist');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageSchedules();
			return false;
		}
		$api = $this->GetApi('Jobs');
		$job = $api->LoadJob($jobid);

		if (empty($job)) {
			$GLOBALS['Error'] = GetLang('JobDoesntExist');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageSchedules();
			return false;
		}

		if ($job['jobstatus'] == 'i') {
			$GLOBALS['Error'] = GetLang('UnableToEditJob_InProgress');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->ManageSchedules();
			return false;
		}
		return true;
	}

	/**
	 * PauseJob
	 * Handles the "pause job" process.
	 * An addon can override this functionality to do it's own work.
	 * If it does, it must call
	 *
	 * InterspireEventData::preventDefault
	 *
	 * to stop the default action from happening.
	 *
	 * The job is loaded (based on the id) before it is passed to the InterspireEvent::trigger method.
	 *
	 * Once either the addon does it's processing work, or the default action happens, the user is taken back to the "ManageSchedules" page.
	 *
	 * @param Int $jobid The job we are going to pause.
	 *
	 * @uses Jobs_API::PauseJob
	 * @uses InterspireEvent::trigger
	 * @see InterspireEventData::preventDefault
	 * @see ManageSchedules
	 *
	 * @uses EventData_IEM_SCHEDULE_PAUSEJOB
	 */
	function PauseJob($jobid=0)
	{
		$jobapi = $this->GetApi('Jobs');
		$jobinfo = $jobapi->LoadJob($jobid);

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SCHEDULE_PAUSEJOB();
			$tempEventData->jobrecord = &$jobinfo;

			if (!$tempEventData->trigger()) {
				$this->ManageSchedules();
				return;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$result = $jobapi->PauseJob($jobid);

		if ($result) {
			$GLOBALS['Message'] = $this->PrintSuccess('JobPausedSuccess');
		} else {
			$GLOBALS['Error'] = GetLang('JobPausedFail');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}
		$this->ManageSchedules();
	}

	/**
	 * ResumeJob
	 * Handles the "resume job" process.
	 * An addon can override this functionality to do it's own work.
	 * If it does, it must call
	 *
	 * InterspireEventData::preventDefault
	 *
	 * to stop the default action from happening.
	 *
	 * The job is loaded (based on the id) before it is passed to the InterspireEvent::trigger method.
	 *
	 * Once either the addon does it's processing work, or the default action happens, the user is taken back to the "ManageSchedules" page.
	 *
	 * @param Int $jobid The job we are going to resume.
	 *
	 * @uses Jobs_API::ResumeJob()
	 * @see InterspireEventData::preventDefault
	 * @see ManageSchedules
	 *
	 * @uses EventData_IEM_SCHEDULE_RESUMEJOB
	 */
	function ResumeJob($jobid=0)
	{
		$jobapi = $this->GetApi('Jobs');
                $jobinfo = $jobapi->LoadJob($jobid);
                $queueid = $jobapi->GetJobQueue($jobid);
                if($queueid !== false){
                    $statapi = $this->GetApi('Stats');
                    $subapi = $this->GetApi('Subscribers');
                    $user = GetUser();
                    $original_queuesize = $jobinfo['jobdetails']['SendSize'];
                    $real_queuesize = $subapi->QueueSize($queueid, 'send');
                    $check_stats = $statapi->ReCheckUserStats($user, $original_queuesize, $real_queuesize, AdjustTime());
                    list($ok_to_send, $not_ok_to_send_reason) = $check_stats;
                    if (!$ok_to_send) {
                            trigger_error(__CLASS__ . '::' . __METHOD__ . " -- ".GetLang($not_ok_to_send_reason),E_USER_WARNING);
                            $jobapi->PauseJob($jobid);
                            $jobapi->UnapproveJob($jobid);
                            $this->ManageSchedules();
                            return;
                    }
                }

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SCHEDULE_RESUMEJOB();
			$tempEventData->jobrecord = &$jobinfo;

			if (!$tempEventData->trigger()) {
				$this->ManageSchedules();
				return;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$result = $jobapi->ResumeJob($jobid);
		if ($result) {
			$GLOBALS['Message'] = $this->PrintSuccess('JobResumedSuccess');
		} else {
			$GLOBALS['Error'] = GetLang('JobResumedFail');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}
		$this->ManageSchedules();
	}

	/**
	 * ApproveJob
	 * Approve job allows an administrator to 'approve' or 'authorise' a send
	 * even if it takes a user over their per-month of email quota.
	 * It checks the user doing the approving is an admin user.
	 * If they are not, they cannot approve a send.
	 *
	 * An addon can override this functionality if need be.
	 * If it does, it must call
	 *
	 * InterspireEventData::preventDefault
	 *
	 * to stop the default action from happening.
	 *
	 * @param Int $jobid The job that needs approving.
	 *
	 * @uses Jobs_API::ApproveJob()
	 * @uses ManageSchedules
	 * @uses InterspireEvent::trigger
	 * @see InterspireEventData::preventDefault
	 *
	 * @return Void Doesn't return anything. Once the job is approved or checked, the user is shown the "Manage Schedules" page.
	 *
	 * @uses EventData_IEM_SCHEDULE_APPROVEJOB
	 */
	function ApproveJob($jobid=0)
	{
		$jobapi = $this->GetApi('Jobs');
		$jobinfo = $jobapi->LoadJob($jobid);

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SCHEDULE_APPROVEJOB();
			$tempEventData->jobrecord = &$jobinfo;

			if (!$tempEventData->trigger()) {
				$this->ManageSchedules();
				return;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$user = GetUser();

		if (!$user->Admin()) {
			$GLOBALS['Error'] = GetLang('JobApprovedFail_NotAdmin');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		} else {
			$result = $jobapi->ApproveJob($jobid, $user->Get('userid'), $user->Get('userid'));
			if ($result) {
				$GLOBALS['Message'] = $this->PrintSuccess('JobApprovedSuccess');
			} else {
				$GLOBALS['Error'] = GetLang('JobApprovedFail');
				$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			}
		}
		$this->ManageSchedules();
	}

	/**
	 * DeleteJobs
	 * Takes an array of job id's that are going to be deleted, and tries to delete them one by one.
	 *
	 * An addon/extension handles it's own work if necessary
	 * and if it does, it removes the 'jobid' from the jobids array that was passed in
	 * so the default action won't be performed (again) on the job.
	 *
	 * @param Array $jobids The job id's that are going to be deleted.
	 *
	 * @uses Jobs_API::ApproveJob
	 * @uses ManageSchedules
	 * @uses InterspireEvent::trigger
	 * @see InterspireEventData::preventDefault
	 *
	 * @return Void Doesn't return anything. Once the job is approved or checked, the user is shown the "Manage Schedules" page.
	 *
	 * @uses EventData_IEM_SCHEDULE_DELETEJOBS
	 */
	function DeleteJobs($jobids=array())
	{
		$jobapi = $this->GetApi('Jobs');

		$jobids = $jobapi->CheckIntVars($jobids);

		// in case the schedule is for multiple lists, we only want the unique job ids.
		$jobids = array_unique($jobids);

		$msg = '';
		$success = 0;
		$fail = 0;

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SCHEDULE_DELETEJOBS();
			$tempEventData->jobids = &$jobids;
			$tempEventData->Message = &$msg;
			$tempEventData->success = &$success;
			$tempEventData->failure = &$fail;

			$tempEventData->trigger();

			unset($tempEventData);
		/**
		 * -----
		 */

		$user = GetUser();

		$stats_api = $this->GetApi('Stats');

		foreach ($jobids as $p => $jobid) {
			$in_progress = $jobapi->JobStarted($jobid);
			if ($in_progress) {
				$fail++;
				continue;
			}

			$jobinfo = $jobapi->LoadJob($jobid);

			if (empty($jobinfo)) {
				continue;
			}

			$statid = $jobapi->LoadStats($jobid);

			/**
			 * If a send is started (ie it has stats),
			 * but is not completed,
			 * We need to mark it as complete.
			 *
			 * This also credits a user back if they have any limits in place.
			 *
			 * This needs to happen before we delete the 'job' from the database
			 * as deleting the job cleans up the queues/unsent items.
			 */
			if ($statid > 0 && $jobinfo['jobstatus'] != 'c') {
				$stats_api->MarkNewsletterFinished($statid, $jobinfo['jobdetails']['SendSize'], false);

			// Credits needs to be returned too whenever the job is canceled AFTER it has been scheduled, but before it was sent
			} elseif ($jobinfo['jobstatus'] != 'c') {
				$stats_api->RefundFixedCredit($jobid);
			}

			$stats_api->DeleteUserStats($user->userid,$jobid);
			
			$result = $jobapi->Delete($jobid);

			if ($result) {
				$success++;
			} else {
				$fail++;
			}
		}

		if ($success > 0) {
			if ($success == 1) {
				$msg .= $this->PrintSuccess('JobDeleteSuccess');
			} else {
				$msg .= $this->PrintSuccess('JobsDeleteSuccess', $success);
			}
		}

		$GLOBALS['Message'] = $msg;
		$this->ManageSchedules();

	}

	/**
	 * ResendJob
	 * Sets up a job ready for "re-sending" if any emails failed to be sent the first time.
 	 * An addon can override this functionality if need be.
	 * If it does, it must call
	 *
	 * InterspireEventData::preventDefault
	 *
	 * to stop the default action from happening.
	 *
	 * @param Int $jobid The job that needs resending.
	 *
	 * @uses Jobs_API::ResendJob()
	 * @uses ManageSchedules
	 * @see InterspireEventData::preventDefault()
	 *
	 * @return Void Doesn't return anything. Once the job is set up for re-sending, the user is shown the "Manage Schedules" page.
	 *
	 * @uses EventData_IEM_SCHEDULE_RESENDJOB
	 */
	function ResendJob($jobid=0)
	{
		$jobapi = $this->GetApi('Jobs');
		$jobinfo = $jobapi->LoadJob($jobid);

		/**
		 * Trigger Event
		 */
			$tempEventData = new EventData_IEM_SCHEDULE_RESENDJOB();
			$tempEventData->jobrecord = &$jobinfo;

			if (!$tempEventData->trigger()) {
				$this->ManageSchedules();
				return;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$resend_setup = $jobapi->ResendJob($jobid);
		if ($resend_setup) {
			$GLOBALS['Message'] = $this->PrintSuccess('JobResendSuccess');
		} else {
			$GLOBALS['Error'] = GetLang('JobResendFail');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}
		$this->ManageSchedules();
	}

	/**
	 * UpdateJob
	 * Updates when a job/scheduled event is set to send.
	 *
	 * A scheduled event by default cannot be changed if:
	 * - it has already sent some emails (it's too hard to re-calculate/re-check/re-allocate per-month statistics)
	 * - you are trying to send "in the past"
	 *
 	 * An addon can override this functionality if need be.
	 * If it does, it must call
	 *
	 * InterspireEventData::preventDefault
	 *
	 * to stop the default action from happening.
	 *
	 * @param Int $jobid The job that needs updating.
	 *
	 * @uses CheckJob
	 * @uses Jobs_API::UpdateTime
	 * @uses ManageSchedules
	 * @uses InterspireEvent::trigger
	 * @see InterspireEventData::preventDefault
	 *
	 * @return Void Doesn't return anything. Checks that it's ok to re-schedule a send. If it is ok, it changes the scheduled time. If it's not, the user gets a message.
	 *
	 * @uses EventData_IEM_SCHEDULE_UPDATEJOB
	 */
	function UpdateJob($jobid=0)
	{
		$check = $this->CheckJob($jobid);
		if (!$check) {
			return false;
		}

		$jobapi = $this->GetApi('Jobs');
		$jobinfo = $jobapi->LoadJob($jobid);

		/**
		 * Trigger Event
		 */
			$tempEventData = new EventData_IEM_SCHEDULE_UPDATEJOB();
			$tempEventData->jobrecord = &$jobinfo;

			if (!$tempEventData->trigger()) {
				$this->ManageSchedules();
				return;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$user = GetUser();

		$sendtime = $_POST['sendtime'];

		$hr = $_POST['sendtime_hours'];
		$minute = $_POST['sendtime_minutes'];

		$ampm = null;
		if (isset($_POST['sendtime_ampm'])) {
			$ampm = strtolower($_POST['sendtime_ampm']);
		}

		if ($ampm == 'pm' && $hr < 12) {
			$hr += 12;
		}

		if ($ampm == 'am' && $hr == 12) {
			$hr = 0;
		}

		if ($hr > 23) {
			$hr = $hr % 24;
		}

		$scheduletime = AdjustTime(array($hr, $minute, 0, (int)$_POST['datetime']['month'], (int)$_POST['datetime']['day'], (int)$_POST['datetime']['year']), true);

		// see if they haev changed the send time. If they have, then we need to check stats again to make sure it's ok to do that.
		if ($jobinfo['jobtime'] != $scheduletime) {

			$now = $jobapi->GetServerTime();

			// you are trying to schedule before now (well, 5 minutes ago), then don't allow it.
			if ($scheduletime < ($now - (5 * 60))) {
				$GLOBALS['Error'] = GetLang('Send_Step4_CannotSendInPast');
				$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				$this->EditJob($jobid);
				return;
			}

			if ($jobinfo['queueid'] > 0) {
				$GLOBALS['Error'] = GetLang('CannotChangeAScheduleOnceItHasStarted');
				$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				$this->EditJob($jobid);
				return;
			}

			$schedulemonth = AdjustTime(array(0, 0, 1, date('m', $scheduletime), 1, date('Y', $scheduletime)), true);

			$original_schedulemonth = AdjustTime(array(0, 0, 1, date('m', $jobinfo['jobtime']), 1, date('Y', $jobinfo['jobtime'])), true);

			if ($schedulemonth != $original_schedulemonth) {

				$statsapi = $this->GetApi('Stats');

				/**
				* We need to check user stats for when this is scheduled to send.
				*/
				$check_stats = $statsapi->CheckUserStats($user, $jobinfo['jobdetails']['SendSize'],$scheduletime);

				list($ok_to_send, $not_ok_to_send_reason) = $check_stats;

				if (!$ok_to_send) {
					$GLOBALS['Error'] = GetLang($not_ok_to_send_reason);
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->EditJob($jobid);
					return;
				}

				$new_size = $jobinfo['jobdetails']['SendSize'];

				$statsapi->DeleteUserStats($user->userid, $jobid);

				$statsapi->RecordUserStats($user->userid, $jobid, $new_size, $scheduletime);
			}
		}

		$result = $jobapi->UpdateTime($jobid, $scheduletime);
		if ($result) {
			$GLOBALS['Message'] = $this->PrintSuccess('JobScheduled', $this->PrintTime($scheduletime));
		} else {
			$GLOBALS['Error'] = sprintf(GetLang('JobNotScheduled'), $this->PrintTime($scheduletime));
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}
		$this->ManageSchedules();
	}

	/**
	 * CanAccessJobs
	 *
	 * Check whether or not current user is able to access the job.
	 * The checking that is currently being done is NOT optimized for query performance,
	 * as it WILL query the database multiple times for each job ID (and it does it without caching anything).
	 *
	 * @param Mixed $jobids Job IDs to be checked
	 * @return Boolean Returns TRUE if user has permission, FALSE otherwise
	 */
	function CanAccessJobs($jobids)
	{
		$user = GetUser();

		if ($user->Admin()) {
			return true;
		}

		if (!is_array($jobids)) {
			$jobids = array($jobids);
		}

		$jobids = array_map('intval', $jobids);
		$jobids = array_unique($jobids);

		if (empty($jobids)) {
			return false;
		}

		$jobapi = $this->GetApi('Jobs');
		foreach ($jobids as $jobid) {
			$jobrecords = $jobapi->LoadJob($jobid);
			if ($jobrecords['ownerid'] != $user->userid) {
				return false;
			}
		}

		return true;
	}
}
