<?php
/**
 * Simply include the functions/stats_chart.php file. That file handles including the database, setting itself up and finally processing the request.
 * This is done so we don't have to worry about full urls etc to the functions/stats_chart.php file.
 * Seems a little redundant but saves a lot of headaches.
 *
 * @package interspire.iem
 */

// Make sure that the IEM controller does NOT redirect request.
define('IEM_NO_CONTROLLER', true);

// Include the index file
require_once dirname(__FILE__) . '/index.php';

/**
* The other file handles everything. We keep this outside of the functions/ folder to make it easier to reference in a url.
*/
require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/stats_chart.php';
