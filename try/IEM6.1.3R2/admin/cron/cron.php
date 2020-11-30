<?php
/**
* This file handles all cron jobs together.
*
* It does:
* - Scheduled sending
* - Autoresponder
* - Bounce processing
* - Addons
* - Trigger emails
*
* @package interspire.iem.cron
*/


// Easy reference to this directory.
$mydir = dirname(__FILE__);

// Include CRON common file
require_once $mydir . '/common.php';

/**
 * Run the jobs in the following order:
 * - scheduled sending
 * - autoresponders
 * - bounce processing
 * - Trigger Emails
 * - any addons that have cron jobs
 * - maintaince is for all maintenance work
 */

require_once $mydir . '/send.php';
require_once $mydir . '/autoresponders.php';
require_once $mydir . '/bounce.php';
require_once $mydir . '/addons.php';
require_once $mydir . '/triggeremails.php';
require_once $mydir . '/maintenance.php';

/**
* After everything has run, see if we need to keep the "logs" in check.
* This will keep the size of them down if any errors etc have been logged by the cron jobs running.
*
* @see GetLogSystem
* @see Interspire_Log::PruneSystemLog
* @see Interspire_Log::PruneAdminLog
*/
$logsystem = GetLogSystem();
if ($logsystem) {
	$logsystem->PruneSystemLog();
	$logsystem->PruneAdminLog();
}
