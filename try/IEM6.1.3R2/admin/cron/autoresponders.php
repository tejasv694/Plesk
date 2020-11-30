<?php
/**
 * This file handles autoresponder sending by itself. It's main task is to set up the required information (classes), load up the database and so on, then pass control over to the jobs_autoresponders.php file which runs everything from there.
 * This can be run as a separate cron job to the jobs.php file to allow more specific / frequent scheduling of just autoresponders.
 *
 * @package interspire.iem.cron
 */

// Include CRON common file
require_once dirname(__FILE__) . '/common.php';

$allow_autoresponders = CheckCronSchedule('autoresponder');

if ($allow_autoresponders) {
	/**
	* Include the job api. This will let us fetch jobs from the queue and start them up.
	*/
	require_once SENDSTUDIO_API_DIRECTORY . '/jobs_autoresponders.php';

	/**
	* Set up the Autoresponder Jobs API.
	*/
	$JobsAPI = new Jobs_Autoresponders_API();

	// Prune entries in the job table that are no longer necessary
	$JobsAPI->PruneAutoJobs();

	/**
	* Check if there are any jobs in progress that aren't stale.
	* If there is a global sending rate in place we don't want to overlap jobs.
	*/
	if ($JobsAPI->ShouldLockSending() && $JobsAPI->JobsRunning('autoresponder')) {
		return;
	}

	while ($job = $JobsAPI->FetchJob('autoresponder')) {
		$result = $JobsAPI->ProcessJob($job);
		if (!$result) {
			echo "*** WARNING *** autoresponder job " . $job . " couldn't be processed.\n";
		}
	}
}
