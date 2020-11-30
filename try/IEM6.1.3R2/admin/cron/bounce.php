<?php
/**
 * This file handles bounce processing by itself. It's main task is to set up the required information (classes), load up the database and so on, then pass control over to the jobs_bounce.php file which runs everything from there.
 * This can be run as a separate cron job to the jobs.php file to allow more specific / frequent scheduling of just bounce processing.
 *
 * @package interspire.iem.cron
 */

// Include CRON common file
require_once dirname(__FILE__) . '/common.php';

	/* #*#*# DISABLED! FLIPMODE! #*#*# ...
$allow_bounce_processing = CheckCronSchedule('bounce');
if (!$allow_bounce_processing) {
	return;
}
	... #*#*# / #*#*# */

// Check whether we are even capable of performing bounce processing.
if (!function_exists('imap_open')) {
	echo "Your server (Command Line version of PHP) does not have the required modules installed to process bounces. Please contact your host or system administrator and ask them to install the \"PHP-IMAP\" module.\nFor more information, see http://www.php.net/imap.\n";
	trigger_error(__FILE__ . '::' . __LINE__ . ' -- IMAP module not found for PHP CLI');
	return;
}

Cron_BounceProcess();

/**
 * Cron_BounceProcess
 * This checks whether it's time to run bounce processing or not based on the options on the settings page.
 * If it's not time to run yet, then it will return out of the function and nothing will be done.
 * If it is time to process bounces, then it will
 * - clean up any left over bounce jobs (in case the server killed the old job or it timed out or ..)
 * - look for unique sets of username/password/server login details (so we only process an email account once per run)
 * - check for imap functions (to be able to log in etc)
 * - then process the jobs it created
 *
 * The bounce api handles processing a single email account for multiple lists, so we can just look for unique combinations here.
 *
 * We check for imap support after checking if there are any accounts to process deliberately.
 * If we checked before hand, straight after someone sets up cron, they'd start getting emailed about "no imap support" even if they haven't set it up yet.
 * So we check for jobs to process first, and only if there are some, check for imap support.
 *
 * @uses CheckCronSchedule
 * @uses Jobs_Bounce_API::Jobs_Bounce_API
 * @uses Jobs_API::FetchJob
 * @uses Jobs_API::ProcessJob
 *
 * @return Void Doesn't return anything from the function, it just does the processing it needs to and returns.
 */
function Cron_BounceProcess(){
	/* #*#*# ADDED! FLIPMODE! #*#*# */
	$allow_bounce_processing = CheckCronSchedule('bounce');
	if (!$allow_bounce_processing) {
		return;
	}
	/* #*#*# / / / / #*#*# */

	/**
	* The rules for bounce processing are in the language/jobs_bounce.php file.
	* It is included in Jobs_Bounce_API
	*
	* @see Jobs_Bounce_API::Jobs_Bounce_API
	*/

	/**
	* Include the job api. This will let us fetch jobs from the queue and start them up.
	*/
	require_once(SENDSTUDIO_API_DIRECTORY . '/jobs_bounce.php');

	/**
	* Set up the Bounce Jobs API.
	*/
	$JobsAPI = new Jobs_Bounce_API();

	/**
	* Check if there are any jobs in progress that aren't stale.
	*/
	if ($JobsAPI->JobsRunning('bounce')) {
		return;
	}

	/**
	* Clean up any old bounce processing jobs before we start
	* Just in case.
	*/
	$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "jobs WHERE jobtype='bounce'";
	$JobsAPI->Db->Query($query);

	/**
	* We create the jobs for just before now so we can find / process them properly.
	*/
	$timenow = $JobsAPI->GetServerTime() - 5;

	/**
	* We're going to create the bounce jobs ourselves.
	* We can run this once a day and it'll go through every list to check for bounce details.
	* We only search for lists that have the server name, username and password specified.
	*
	* We only look for email accounts that haven't been processed for 10 minutes - there's no need to process them more often than that.
	*
	* @see JobsAPI::Create
	*/

	$jobs_created = array();

	$found_job = false;

	/**
	* Look for mailing lists that have
	* - bounce servers, usernames and passwords saved
	* - both of the 'processbounce' and 'agreedelete' options set
	*
	* In theory we shouldn't have any lists that have the server,
	* username and password but not the other two checks
	* but it can't hurt to check them here as well.
	*/
	$query = "SELECT bounceserver, bounceusername, bouncepassword FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE bounceserver != '' AND bounceusername != '' AND bouncepassword != '' AND agreedelete='1' AND processbounce='1' GROUP BY bounceserver, bounceusername, bouncepassword";
	$unique_result = $JobsAPI->Db->Query($query);
	while ($unique_row = $JobsAPI->Db->Fetch($unique_result)) {

		$query = "SELECT listid, name, bounceserver, bounceusername, bouncepassword, imapaccount, extramailsettings, ownerid, agreedeleteall FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE bounceserver='" . $JobsAPI->Db->Quote($unique_row['bounceserver']) . "' AND bounceusername='" . $JobsAPI->Db->Quote($unique_row['bounceusername']) . "' AND bouncepassword='" . $JobsAPI->Db->Quote($unique_row['bouncepassword']) . "' LIMIT 1";

		$result = $JobsAPI->Db->Query($query);
		while ($listrow = $JobsAPI->Db->Fetch($result)) {
			$found_job = true;

			$details = array(
				'Lists' => array($listrow['listid']),
				'listname' => $listrow['name'],
				'bounceserver' => $listrow['bounceserver'],
				'bounceusername' => $listrow['bounceusername'],
				'bouncepassword' => $listrow['bouncepassword'],
				'extramailsettings' => $listrow['extramailsettings'],
				'imapaccount' => $listrow['imapaccount'],
				'agreedeleteall' => $listrow['agreedeleteall'],
			);
			$jobcreated = $JobsAPI->Create('bounce', $timenow, $listrow['ownerid'], $details, 'bounce', $listrow['listid'], $listrow['listid']);
			$jobs_created[] = $jobcreated;
		}
	}

	if (!$found_job) {
		return;
	}

	/**
	 * Check for imap support before we go any further.
	 * Seems strange to do here and not first but this way if someone doesn't have imap support,
	 * they'll only get an email once they set up bounce processing (ie save the details)
	 * otherwise they'll get an email every time cron runs to say "no imap support".
	 */
	if (!function_exists('imap_open')) {
		foreach ($jobs_created as $p => $jobid) {
			$JobsAPI->Delete($jobid);
		}
		echo GetLang('ImapSupportMissing');
		return;
	}

	/**
	* Now we go through each job we have just created and process it.
	*
	* @see Jobs_Bounce_API::FetchJob
	* @see Jobs_Bounce_API::ProcessJob
	* @see Jobs_Bounce_API::Delete
	*/
	while ($job = $JobsAPI->FetchJob('bounce')) {
		$result = $JobsAPI->ProcessJob($job);
		// we delete these because we're only temporarily creating them (there's no management area in sendstudio for bounce jobs).
		$JobsAPI->Delete($job);
	}
}

?>
