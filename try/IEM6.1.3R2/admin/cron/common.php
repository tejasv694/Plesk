<?php
/**
 * This file contains the common procedure for each cron.
 * It should be "required once" for each cron
 *
 * @package interspire.iem.cron
 */

// This tells the session whether it needs to start or not.
define('IEM_CRON_JOB', true);

// Make sure that the IEM controller does NOT redirect request.
define('IEM_NO_CONTROLLER', true);

// CRON needs to run under CLI mode
define('IEM_CLI_MODE', true);

// Include the base init file.
require_once (dirname(dirname(__FILE__)) . '/index.php');

// Include settings API class
require_once IEM_PUBLIC_PATH . '/functions/api/settings.php';

// If database need upgrading, do not proceed.
$settings_api = new Settings_API();
if ($settings_api->NeedDatabaseUpgrade()) {
	exit;
}
unset($settings_api);

// Try to set unlimted time limit
if (!SENDSTUDIO_SAFE_MODE && strpos(SENDSTUDIO_DISABLED_FUNCTIONS, 'set_time_limit') === false) {
	set_time_limit(0);
}

// Sendstudio isn't set up? Quit.
if (!defined('SENDSTUDIO_IS_SETUP') || !SENDSTUDIO_IS_SETUP) {
	exit;
}

// Sendstudio isn't supposed to use cron? Quit.
if (SENDSTUDIO_CRON_ENABLED != 1) {
	exit;
}


require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';
