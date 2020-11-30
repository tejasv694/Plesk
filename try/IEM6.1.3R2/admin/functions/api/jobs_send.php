<?php

/**
 * This is the send job system object. This handles everything for scheduled sending.
 *
 * @version     $Id: jobs_send.php,v 1.48 2008/02/20 07:42:23 chris Exp $
 * @author Chris <chris@interspire.com>
 * @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
 *
 * @package API
 * @subpackage Jobs
 */
/**
 * Require the base job class.
 */
require_once(dirname(__FILE__) . '/send.php');

/**
 * This class handles scheduled sending job processing.
 *
 * @package API
 * @subpackage Jobs
 */
class Jobs_Send_API extends Send_API {

    /**
     * Constructor
     * Calls the parent object to set up the database.
     * Sets up references to the email api, list api and subscriber api.
     *
     * @see Email_API
     * @see Lists_API
     * @see Subscriber_API
     * @see Newsletter_API
     * @see Jobs_API::Jobs_API
     */
    function Jobs_Send_API() {
        SendStudio_Functions::LoadLanguageFile('jobs_send');
        $this->Send_API();
    }

    /**
     * FetchJob
     * Fetches the next 'send' jobtype from the queue that is 'w'aiting.
     * It also checks for stalled sends, which are ones that haven't had a lastupdatetime update in the last 30 minutes.
     *
     * @return Int|False Returns false if there is no next job. Otherwise returns the jobid to run.
     */
    function FetchJob() {
        $timenow = $this->GetServerTime();
	$half_hour_ago = $timenow - (30 * 60);

        $query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "jobs WHERE jobtype='send' AND (";

        /**
         * get "waiting" jobs
         */
        $query .= " (jobstatus ='w' AND jobtime < " . $timenow . ") OR ";

        /**
         * get "resending" jobs
         */
        $query .= " (jobstatus='r' AND jobtime < " . $timenow . ") OR ";

        /**
         * Get jobs that haven't been updated in half an hour.
         * This is in case a job has broken (eg the db went down or server was rebooted mid-send).
         */
        $query .= " (jobstatus='i' AND jobtime < " . $timenow . " AND lastupdatetime < " . $half_hour_ago . ")";

        /**
         * order the results so we get the one scheduled first to send.
         */
        $query .= ") AND (approved > 0)";

        $query .= " ORDER BY jobtime ASC LIMIT 1";
        $result = $this->Db->Query($query);
        if (!$result) {
            return false;
        }

        $row = $this->Db->Fetch($result);
        if (empty($row)) {
            return false;
        }

        $query = "SELECT COUNT(*) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "jobs_lists WHERE jobid='" . $row['jobid'] . "'";
        $result = $this->Db->Query($query);
        $count_check = $this->Db->FetchOne($result, 'count');
        if ($count_check <= 0) {
            echo '*** Found an orphaned job: ' . $row['jobid'] . ' *** - No entries in the jobs_lists table. Please contact your administrator.' . "\n";
            $this->PauseJob($row['jobid']);
            return false;
        }

        $this->jobstatus = $row['jobstatus'];
        $this->jobdetails = unserialize($row['jobdetails']);
        $this->jobowner = $row['ownerid'];

        // if the job is in progress, we'll pause it so the send part can pick it up properly.
        if ($row['jobstatus'] == 'i') {
            $this->PauseJob($row['jobid']);
        }

        // if the job is in 'resend' mode, we need to do some work before it will be picked up properly.
        // the job needs to be 'started' before it can be re-set up, so start it up, do the setup, then pause.
        // see GetJobQueueSize
        if ($row['jobstatus'] == 'r') {
            $this->Db->StartTransaction();
            $start_ok = $this->StartJob($row['jobid']);
            if (!$start_ok) {
                $this->Db->RollbackTransaction();
                return false;
            }

            $resend_ok = $this->ResendJob_Setup($row['jobid']);
            if (!$resend_ok) {
                $this->Db->RollbackTransaction();
                return false;
            }

            $pause_ok = $this->PauseJob($row['jobid']);
            if (!$pause_ok) {
                $this->Db->RollbackTransaction();
                return false;
            }

            $this->Db->CommitTransaction();
        }

        return $row['jobid'];
    }

    /**
     * ProcessJob
     * Does most of the work setting up the job (creates the queue, removes duplicates and so on) to run. Once the job has been set up ready to run, it 'Actions' the job, then marks it as 'finished'.
     *
     * @param Int $jobid The job to run.
     *
     * @see Email_API
     * @see Subscriber_API
     * @see Lists_API
     * @see newsletter_api
     * @see GetUser
     * @see StartJob
     * @see GetJobQueue
     * @see CreateQueue
     * @see JobQueue
     * @see Subscribers_API::GetSubscribers
     * @see RemoveDuplicatesInQueue
     * @see RemoveBannedEmails
     * @see ActionJob
     * @see FinishJob
     *
     * @return Boolean Returns false if the job can't be started. Otherwise runs the job and returns true.
     */
    function ProcessJob($jobid=0) {
        if (!$this->StartJob($jobid)) {
            $this->PauseJob($jobid);
            $this->jobstatus = 'p';
            trigger_error("Unable to start send job {$jobid}");
            return false;
        }

        $user = GetUser($this->jobowner);
        IEM::userLogin($this->jobowner, false);

        $queueid = false;

        // if there's no queue, start one up.
        if (!$queueid = $this->GetJobQueue($jobid)) {
            $sendqueue = $this->CreateQueue('send');
            $queueok = $this->JobQueue($jobid, $sendqueue);
            $send_criteria = $this->jobdetails['SendCriteria'];

            $original_queuesize = $this->jobdetails['SendSize'];

            $queueinfo = array('queueid' => $sendqueue, 'queuetype' => 'send', 'ownerid' => $this->jobowner);

            if (isset($this->jobdetails['Segments']) && is_array($this->jobdetails['Segments'])) {
                $this->Subscriber_API->GetSubscribersFromSegment($this->jobdetails['Segments'], false, $queueinfo, 'nosort');
            } else {
                $this->Subscriber_API->GetSubscribers($send_criteria, array(), false, $queueinfo, $sendqueue);
            }

            $this->Subscriber_API->RemoveDuplicatesInQueue($sendqueue, 'send', $this->jobdetails['Lists']);

            $this->Subscriber_API->RemoveBannedEmails($this->jobdetails['Lists'], $sendqueue, 'send');

            $this->Subscriber_API->RemoveUnsubscribedEmails($this->jobdetails['Lists'], $sendqueue, 'send');

            $queueid = $sendqueue;

            $newsletterstats = $this->jobdetails;
            $newsletterstats['Job'] = $jobid;
            $newsletterstats['Queue'] = $sendqueue;
            $newsletterstats['SentBy'] = $queueinfo['ownerid'];

            $real_queuesize = $this->Subscriber_API->QueueSize($queueid, 'send');

            $newsletterstats['SendSize'] = $real_queuesize;

            $statid = $this->Stats_API->SaveNewsletterStats($newsletterstats);

            /**
             * Process tracker request where because cron was not enabled, we need to parse the option straight away
             * @todo Result for the call to module_Tracker::ParseOptionsForAllTracker() is not being processed and being ignored
             */
            $tempAPIFile = dirname(__FILE__) . '/module_trackerfactory.php';
            if (is_file($tempAPIFile)) {
                require_once($tempAPIFile);
                $temp = array_merge($this->jobdetails, array('statid' => $statid,
                    'stattype' => 'newsletter',
                    'newsletterid' => $this->jobdetails['Newsletter']));

                $status = module_Tracker::ParseOptionsForAllTracker($temp);
            }
            /**
             * -----
             */
            /**
             * So we can link user stats to send stats, we need to update it.
             */
            $this->Stats_API->UpdateUserStats($queueinfo['ownerid'], $jobid, $statid);

            /**
             * The 'queuesize' in the stats_users table is updated by MarkNewsletterFinished in send.php
             * so we don't need to worry about it while setting up the send.
             * That takes into account whether some recipients were skipped because a html-only email was sent etc.
             */
            /**
             * We re-check the user stats in case a bunch of subscribers have joined, or the user has done something like:
             * - create a list
             * - added a few subscribers
             * - scheduled a send
             * - added more subscribers
             */
            $check_stats = $this->Stats_API->ReCheckUserStats($user, $original_queuesize, $real_queuesize, AdjustTime());

            list($ok_to_send, $not_ok_to_send_reason) = $check_stats;
            if (!$ok_to_send) {
                trigger_error(__CLASS__ . '::' . __METHOD__ . " -- " . GetLang($not_ok_to_send_reason), E_USER_WARNING);
                $this->PauseJob($jobid);
                $this->UnapproveJob($jobid);
                IEM::userLogout();
                return false;
            }

            API_USERS::creditEvaluateWarnings($user->GetNewAPI());
        }

        $this->statid = $this->LoadStats($jobid);
        if (empty($this->statid)) {
            trigger_error(__CLASS__ . '::' . __METHOD__ . " -- Cannot find statid. Previous preliminary job process did not get finalized (either CRON died, or it hasn't finished processing the job). Ignoring this job: jobid {$jobid}.", E_USER_NOTICE);
            IEM::userLogout();
            return false;
        }

        $queuesize = $this->Subscriber_API->QueueSize($queueid, 'send');

        // used by send.php::CleanUp
        $this->queuesize = $this->jobdetails['SendSize'];

        /**
         * There's nothing left? Just mark it as done.
         */
        if ($queuesize == 0) {
            $this->jobstatus = 'c';
            $this->FinishJob($jobid);
            IEM::userLogout();
            return true;
        }

        $finished = $this->ActionJob($jobid, $queueid);

        if ($finished) {
            $this->jobstatus = 'c';
            $this->FinishJob($jobid);
        }

        IEM::userLogout();
        return true;
    }

    /**
     * ActionJob
     * Actions the job passed in. It goes through the queue (also passed in) and sends an email to each of the recipients in the queue. Since the queue has already been checked for duplicates and banned emails, it doesn't need to do any of this.
     *
     * @param Int $jobid The Job to action.
     * @param Int $queueid The queue for the job.
     *
     * @see IsQueue
     * @see Newsletters_API::Load
     * @see Newsletters_API::Get
     * @see SendStudio_Functions::GetAttachments
     * @see Email_API::SetSmtp
     * @see Email_API::AddAttachment
     * @see Email_API::Set
     * @see Email_API::AddBody
     * @see Email_API::AppendBody
     * @see FetchFromQueue
     * @see Email_API::ClearRecipients
     * @see Subscribers_API::LoadSubscriberFromList
     * @see Lists_API::Load
     * @see RemoveFromQueue
     * @see Subscribers_API::GetFormat
     * @see Email_API::AddRecipient
     * @see Email_API::AddCustomFieldInfo
     * @see Email_API::Send
     *
     * @return Boolean Returns true if the job has been actioned successfully. If anything doesn't work (eg newsletter can't be loaded) it returns false.
     */
    function ActionJob($jobid=0, $queueid=0) {
        if (!$this->SetupJob($jobid, $queueid)) {
            trigger_error("Unable to setup job {$jobid}");
            return false;
        }

        $this->NotifyOwner();
        $this->Db->StartTransaction();

        $emails_sent = 0;
        $paused = false;

        while ($recipients = $this->FetchFromQueue($queueid, 'send', 1, 500)) {
            if (empty($recipients)) {
                break;
            }

            $this->SetupDynamicContentFields($recipients);
            $this->SetupCustomFields($recipients);

            $sent_to_recipients = array();

            foreach ($recipients as $p => $recipientid) {
                $send_results = $this->SendToRecipient($recipientid, $queueid);

                $sent_to_recipients[] = $recipientid;

                $emails_sent++;

                /*
                 * Whether to check if the job has been paused or not.
                 * 
                 * We want to do that at the last possible moment..
                 */
                $check_paused = false;

                /*
                 * Update lastupdatedtime so we can track what's going on.
                 * 
                 * This is used so we can see if the server has crashed or the 
                 * cron job has been stopped in mid-send.
                 *
                 * @see FetchJob
                 */
                if ($this->userpause > 0 || ($this->userpause == 0 && (($emails_sent % 5) == 0))) {
                    $query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "jobs SET lastupdatetime='" . $this->GetServerTime() . "' WHERE jobid='" . (int) $jobid . "'";

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

                /*
                 * See if the job has been paused or not through the control 
                 * panel.
                 * 
                 * If it has, break out of the recipient loop then clean up the 
                 * recipients we have sent to successfully then break out of the
                 * send 'job'.
                 */
                if ($check_paused) {
                    $paused = $this->JobPaused($jobid);

                    if ($paused) {
                        break;
                    }
                }
            }

            if (!empty($sent_to_recipients)) {
                $query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "queues WHERE queueid='" . $queueid . "' AND queuetype='send' AND recipient IN (" . implode(',', $sent_to_recipients) . ") AND processed='1'";

                $this->Db->Query($query);
            }

            if ($paused) {
                break;
            }
        }

        $this->Db->CommitTransaction();

        if ($paused) {
            $finished = false;
            $this->jobstatus = 'p';
        } else {
            $this->jobstatus = 'c';
            $finished = true;
            $this->CleanUp($queueid);
        }

        $this->Email_API->SMTP_Logout();
        $this->NotifyOwner();
        $this->ResetSend();

        return $finished;
    }

}
