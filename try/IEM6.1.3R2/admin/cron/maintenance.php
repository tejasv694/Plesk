<?php
/**
 * This file is for all maintenance purpose
 * - Clearing all exports queries that are stalled
 * - Clearing all the import files that are still there and failed..
 * - Clearing all the old session files that are older then 5 years days.
 *
 * @package interspire.iem.cron
 */

// Include CRON common file
require_once dirname(__FILE__) . '/common.php';


$allow_maintenance = CheckCronSchedule('maintenance');

if ($allow_maintenance) {
	$api = new Maintenance();

	$status = $api->clearImportFiles();
	if (!$status) {
		trigger_error(__FILE__ . '::' . __LINE__ . ' -- Unable to clear old import files', E_USER_NOTICE);
	}

	$status = $api->pruneExportQueries();
	if (!$status) {
		trigger_error(__FILE__ . '::' . __LINE__ . ' -- Unable to clear export queries from the queue table', E_USER_NOTICE);
	}

	//@TODO: All
	/*$status = $api->clearOldSession();
	if (!$status) {
		trigger_error(__FILE__ . '::' . __LINE__ . ' -- Unable to cleanup old session files', E_USER_NOTICE);
	}*/
	//---
	
	$status = $api->pruneErrorLog();
	if (!$status) {
		trigger_error(__FILE__ . '::' . __LINE__ . ' -- Unable to clear error log entries', E_USER_NOTICE);
	}
		
	$status = $api->clearEmptySubscriberData();
	if (!$status) {
		trigger_error(__FILE__ . '::' . __LINE__ . ' -- Unable to clear empty subscriber_data records', E_USER_NOTICE);
	}
}
