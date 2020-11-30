<?php
/**
 * TriggerEmails CRON script
 *
 * This file will handle sending out trigger emails VIA CRON.
 * It's main task is to set up the required environment so the API can run.
 * Although this file can be run on a separate CRON Job, it is best not to do this,
 * as the application itself already handles multiple frequency for each functionalities.
 *
 * @package interspire.iem.cron
 */

// Include CRON common file
require_once dirname(__FILE__) . '/common.php';

/**
 * Include the job api. This will let us fetch jobs from the queue and start them up.
 */
require_once(SENDSTUDIO_API_DIRECTORY . '/jobs_triggeremails.php');
$JobsAPI = new Jobs_TriggerEmails_API();

// ----- Populate queues table with contacts who will need to receive the trigger in the next 24 hours.
	if (defined('SENDSTUDIO_CRON_TRIGGEREMAILS_S') && SENDSTUDIO_CRON_TRIGGEREMAILS_S != 0 && CheckCronSchedule('triggeremails_s')) {
		if ($JobsAPI->StartJob('triggeremails_populate')) {
			if (!$JobsAPI->ProcessPopulateJob()) {
				print "Error: Cannot populate trigger emails. Please notify your system adiminstrator about this error.\n";
			}

			$JobsAPI->FinishJob('triggeremails_populate');
		}
		
	    if ($JobsAPI->StartJob('triggeremails_send')) {
		    if (!$JobsAPI->ProcessJob()) {
			    print "Error: Cannot send trigger emails. Please notify your system adiminstrator about this error.\n";
		    }

		    $JobsAPI->FinishJob('triggeremails_send');
	    }		

	}
// -----
