<?php
/**
 * Include the base init file which connects to the database, sets up correct locations etc.
 * This is the only CRON script that cannot be run independently
 *
 * @package interspire.iem.cron
 *
 * @uses EventData_IEM_CRON_RUNADDONS
 */

// This CRON script cannot be independently run. It has be called from within cron.php
if (!defined('IEM_CRON_JOB')) {
 	die("You cannot run the cron addons as a single cron script.");
}

/**
 * Get the jobs to run from the addons.
 * It expects an array of data in return containing:
 *
 * <code>
 * Array
 * (
 * 	'addonid' => 'my_addon_id',
 * 	'file' => '/full/path/to/file',
 * )
 * </code>
 *
 * If the process functions require any id's they need to be supplied in a 'jobids' array like this:
 * <code>
 * Array
 * (
 * 	'addonid' => 'my_addon_id',
 * 	'file' => '/full/path/to/file',
 * 	'jobids' => array (
 * 		1,
 * 		2,
 * 		3
 * 	),
 * )
 * </code>
 *
 * Id's are not required to be supplied as some types of addons won't need them - eg a 'backup' addon.
 *
 * They also don't need to be "job" id's from the "jobs" table, they can be anything (they just come under the "jobids" array).
 * They could be newsletter id's (for example to create pdf's for), or list id's, or subscriber id's.
 * It's up to the addon itself to work out what they are and what to do with them.
 * At this stage, we don't care what sort of id's they are or what they reference.
 *
 * The file must contain a class called 'Jobs_Cron_addonid_API'
 *
 * so we have a consistent class to call.
 *
 * Inside that class, it needs a 'ProcessJobs' method which takes all of the id's from here to process.
 * From there, the ProcessJobs method can do whatever it likes with those id's.
 * If it throws an exception, it will be caught here and just displayed in output
 * which should be captured via cron/scheduled tasks and emailed somewhere.
 */
$jobs_to_run = array();

/**
 * Trigger event
 */
	$tempEventData = new EventData_IEM_CRON_RUNADDONS();
	$tempEventData->jobs_to_run = &$jobs_to_run;
	$tempEventData->trigger();

	unset($tempEventData);
/**
 * -----
 */

foreach ($jobs_to_run as $job_details) {
	if (!isset($job_details['addonid'])) {
		echo "An Addon is not adding itself to the cron list properly\n";
		print_r($jobs_to_run);
		continue;
	}

	$addon_id = $job_details['addonid'];

	if (!isset($job_details['file'])) {
		echo "Addon " . $addon_id . " is not adding itself to the cron list properly\n";
		echo "It has not included the full path to the file to run\n";
		continue;
	}

	if (!is_file($job_details['file'])) {
		echo "Addon " . $addon_id . " is not adding itself to the cron list properly\n";
		echo "The path provided (" . $job_details['file'] . ") is not correct\n";
		continue;
	}

	if (!isset($job_details['jobs'])) {
		echo "Addon " . $addon_id . " is not adding itself to the cron list properly\n";
		echo "It has not given any jobs to process\n";
		continue;
	}

	/**
	 * Make sure it's ok to run the addon job.
	 * This will also check it has registered itself with the cron schedule properly too.
	 */
	$allow_job = CheckCronSchedule($job_details['addonid']);
	if (!$allow_job) {
		continue;
	}

	require_once $job_details['file'];
	$class = 'Jobs_Cron_API_' . ucwords($addon_id);
	$job_system = new $class;

	/**
	 * Work out whether there are any 'jobs' to pass to the addon.
	 * These don't have to be 'jobid's, they can be anything:
	 * - newsletter id's (eg to create pdf's for)
	 * - list id's to do some statistical processing for.
	 * - anything you can think of.
	 *
	 * Id's aren't required in the cron job step because some addons won't require them - eg a 'backup' addon.
	 */
	$jobs = array();
	if (isset($job_details['jobs'])) {
		$jobs = $job_details['jobs'];
	}

	try {
		$job_system->ProcessJobs($jobs);
	} catch (Exception $e) {
		echo "Trying to process jobs for addon " . $addon_id . " threw an exception: " . $e->getMessage() . "\n";
	}

	unset($job_system);
}

