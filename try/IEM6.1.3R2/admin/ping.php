<?php
/**
 * This is a file that SendStudio's header is calling in an interval which will keep PHP Session alive.
 * It will simply include Session library which start session, and print out a simple "PING" response
 *
 * @package interspire.iem
 */

// Make sure that the IEM controller does NOT redirect request.
define('IEM_NO_CONTROLLER', true);

// Include the index file
require_once dirname(__FILE__) . '/index.php';
?>
PING
