<?php
/**
 * This file contains the following:
 * - Check whether or not the server is running a new-enough version of PHP,
 * - Include the framework initialization procedure.
 *
 * As you can always safely assume that this file will always be located in the
 * same place, its primary role is also to become the main entry point for all
 * requests.
 *
 * This means ALL other files needs to include this file instead referencing to
 * the init file directly, because init.php's location can be moved somewhere
 * else.
 *
 * If you don't want the framework controller to take over re-directing
 * requests, you will need to set the "IEM_NO_CONTROLLER" constant and define
 * its value to TRUE.
 *
 * A full lists of constants that can be define to affect IEM framework is described in admin/com/init.php
 *
 * @see admin/com/init.php
 *
 * @package interspire.iem
 */

/**
 * Perform rudimentary PHP check.
 */
$min_php = '5.1.3';
if (version_compare(PHP_VERSION, $min_php, '<')) {
	$sapi = php_sapi_name();
	$error_message = '';

	switch ($sapi) {
		// Display CLI version of the error message (this most likely be triggered by CRON)
		case 'cli':
			$error_message = "This application requires at least PHP version {$min_php} but your server is running PHP version " . PHP_VERSION . ".\n\nYour server might be running two version of PHP. You will need to modify your CRON details to use PHP 5 (usually modifying /usr/bin/php to /usr/bin/php5). If you are not sure how to do this, please ask your hosting provider.";
		break;


		// Display Web-version of the error message
		default:
			// print templated error message
			$path = dirname(__FILE__) . '/com/templates';
			$header = file_get_contents($path . '/upgrade_header.tpl');
			$body = file_get_contents($path . '/upgrade_body.tpl');
			$footer = file_get_contents($path . '/upgrade_footer.tpl');

			$action = 'Installation';
			require_once('includes/config.php');
			if (defined('SENDSTUDIO_IS_SETUP') && SENDSTUDIO_IS_SETUP) {
				$action = 'Upgrade';
			}

			// See also admin/functions/upgradenx.php for a similar message
			$title = "This {$action} Cannot Proceed";
			$msg = '<p>This application requires at least PHP version <em>' . $min_php . '</em> but your server is running PHP version <em>' . PHP_VERSION . '</em>. To use this application, your web host must upgrade PHP to version <em>' . $min_php . '</em> or higher. Please note that this is not a problem with this application and it is something only your web host can change.</p>';

			// manually replace the tokens
			$header = str_replace('%%LNG_ControlPanel%%', $title, $header);
			$header = str_replace('%%GLOBAL_CHARSET%%', 'UTF-8', $header);
			$body = str_replace('{$title}', $title, $body);
			$body = str_replace('{$msg}', $msg, $body);
			$footer = str_replace('%%LNG_Copyright%%', '', $footer);

			$error_message = $header . $body . $footer;
		break;
	}

	echo $error_message;
	exit;
}


// It's now up to the controller to re-direct requests.
if(isset($_GET['Page'])){
    $_GET['Page']= preg_replace('/[^\w]/', '_', $_GET['Page']);
}
require_once dirname(__FILE__) . '/com/init.php';
shutdown_and_cleanup();
