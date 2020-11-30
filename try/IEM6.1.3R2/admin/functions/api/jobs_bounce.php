<?php
/**
* This is the bounce job system object.
*
* The rules for bounce processing are in the language/jobs_bounce.php file.
*
* @version     $Id: jobs_bounce.php,v 1.16 2007/05/03 07:09:15 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Jobs
*/

/**
* Require the bounce api class.
* That includes the base api class so we don't have to worry about it.
*/
require_once(dirname(__FILE__) . '/bounce.php');

/**
* This class handles job processing. It will also handle notifying job owners of when things happen as necessary.
*
* The rules for bounce processing are in the language/jobs_bounce.php file.
*
* @package API
* @subpackage Jobs
*/
class Jobs_Bounce_API extends Bounce_API
{

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
	 * How many emails to process before doing an expunge.
	 *
	 * @var Int
	 * @see self::ActionJob()
	 */
	var $_expunge_after = 100;

	/**
	* FetchJob
	* Gets the next job in the queue for this job type ('bounce').
	* If there is no next job it returns false.
	*
	* @see jobtype
	* @see jobstatus
	* @see jobdetails
	* @see jobowner
	*
	* @return Mixed Returns false if there is no next job. Otherwise sets up jobstatus, jobdetails and jobowner for easy user and returns the job id.
	*/
	function FetchJob()
	{
		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "jobs WHERE jobtype='bounce' AND jobstatus ='w' AND jobtime < " . $this->GetServerTime() . " ORDER BY jobtime ASC LIMIT 1";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			return false;
		}

		$this->jobtype = 'bounce';
		$this->jobstatus = $row['jobstatus'];
		$this->jobdetails = unserialize($row['jobdetails']);
		$this->jobowner = $row['ownerid'];
		return $row['jobid'];
	}

	/**
	* ProcessJob
	* This starts the job, loads up the job owner (for emailing), actions the job and marks it complete. All of the work is done by actionjob.
	*
	* @param Int $jobid Jobid to process.
	*
	* @see user
	* @see StartJob
	* @see ActionJob
	* @see FinishJob
	*
	* @return Boolean Returns false if the job can't be started. Otherwise will action the job and finish the job and return true.
	*/
	function ProcessJob($jobid=0)
	{
		$this->user = GetUser($this->jobowner);

		if (!$this->StartJob($jobid)) {
			return false;
		}

		$this->ActionJob($jobid);

		$this->FinishJob($jobid);

		return true;
	}

	/**
	* ActionJob
	* Does the work of the job.
	* This logs in to an email account, looks at each email and determines whether an email is a bounce email or not. It will update the database if necessary and finally delete the email from the email account.
	* If the email account can't be accessed, the job owner will be notified (eg bad login details)
	*
	* @param Int $jobid Jobid to process.
	*
	* @see jobdetails
	* @see ParseBody
	*
	* @return Boolean Returns false if the job can't be started. Otherwise will action the job and finish the job and return true.
	*/
	function ActionJob($jobid=0)
	{
		if (!isset($this->jobdetails['bounceserver']) || !isset($this->jobdetails['bounceusername']) || !isset($this->jobdetails['bouncepassword'])) {
			return false;
		}

		$this->Set('bounceserver', $this->jobdetails['bounceserver']);
		$this->Set('bounceuser', $this->jobdetails['bounceusername']);
		$this->Set('bouncepassword', $this->jobdetails['bouncepassword']);
		$this->Set('imapaccount', $this->jobdetails['imapaccount']);
		$this->Set('extramailsettings', $this->jobdetails['extramailsettings']);

		$inbox = $this->Login();

		$job_listid = current($this->jobdetails['Lists']);

		if (!$inbox) {
			$subject = GetLang('BadLogin_Subject_Cron');
			$msg = sprintf(GetLang('BadLogin_Details_Cron'), $this->jobdetails['bounceusername'], $this->jobdetails['bounceserver'], $this->ErrorMessage, SENDSTUDIO_APPLICATION_URL . '/admin/index.php', SENDSTUDIO_APPLICATION_URL . '/admin/index.php?Page=Lists&Action=Edit&id=' . $job_listid);
			$this->NotifyOwner($subject, $msg);
			return false;
		}

		// This value will not change even if we expunge messages and call it again
		// so we need to keep our own count.
		$email_count = $this->GetEmailCount();

		// On some servers, an expunge will not delete the emails until there's been
		// a logout, so we will login/logout every $this->_expunge_after emails.
		$this->Logout();

		// How many emails we've dealt with (between 0 and $email_count)
		$dealt_with = 0;

		// Which email we're up to looking at, after an expunge. This will ensure we
		// don't keep looking at the same emails we've skipped over before.
		$up_to = 1;

		do {

			$inbox = $this->Login();

			if ($up_to > $email_count) {
				break;
			}

			$process_to = $up_to + $this->_expunge_after - 1;
			$process_to = min($email_count, $process_to);

			for ($emailid = $up_to; $emailid <= $process_to; $emailid++) {
				$processed = $this->ProcessEmail($emailid, $job_listid);
				if (in_array($processed, array('hard', 'soft', 'delete')) || $this->jobdetails['agreedeleteall'] == 1) {
					$this->DeleteEmail($emailid);
				} else {
					$up_to++;
				}
				$dealt_with++;
			}

			// Do an expunging logout.
			$this->Logout(true);
			$this->UpdateJobTime($jobid);

		} while ($dealt_with < $email_count);

		return true;
	}

	/**
	 * Updates the job time with the current sever time.
	 *
	 * $param Int $jobid The Job ID to update the time for.
	 *
	 * @return Boolean True if updated successfully, otherwise false.
	 */
	function UpdateJobTime($jobid)
	{
		$time = intval($this->GetServerTime());
		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "jobs SET lastupdatetime = " . $time . " WHERE jobid = " . intval($jobid);
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		return true;
	}

}
