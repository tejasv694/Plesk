<?php
/**
*
***********************************************************************************
* See the bounce_rules.php file for instructions about how to add your own rules. *
***********************************************************************************
*
*
* User defined bounce rules.
* These are separated from the base rules
* so the base rules can be continually updated
* and won't interfere with your own rules.
* See bounce_rules.php for instructions about how to update this file.
*
* @see bounce_rules.php
*
*/

/**
* Make sure this is a valid request.
* The bounce_rules.php file sets up this array so it should always be set.
* If it's not set, someone is trying to be dodgy so just exit.
*/
if (!isset($GLOBALS['BOUNCE_RULES']) || empty($GLOBALS['BOUNCE_RULES'])) {
	exit;
}

// ----------- Start your own rules below this line ----------- //

// ----------- Make sure your rules are above this line ----------- //
?>
