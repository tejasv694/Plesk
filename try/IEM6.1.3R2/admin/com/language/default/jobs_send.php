<?php

/**
* Language file variables for the newsletters area.
*
* @see GetLang
*
* @version     $Id: jobs_send.php,v 1.5 2007/11/01 02:48:20 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the jobs sending area... Please backup before you start!
*/

define('LNG_Job_Subject_Started', 'Email campaign %s has started sending');
define('LNG_Job_Message_Started', 'Email campaign %s has started sending at %s');

define('LNG_Job_Subject_Paused', 'Email campaign %s has been paused');
define('LNG_Job_Message_Paused_One', 'Email campaign %s was paused at %s. 1 email has been sent so far.');
define('LNG_Job_Message_Paused_Many', 'Email campaign %s was paused at %s. %s emails have been sent so far.');

define('LNG_Job_Subject_Complete', 'Email campaign %s has finished sending');
define('LNG_Job_Message_Complete_One', 'Email campaign %s has finished sending at %s. 1 email was sent.');
define('LNG_Job_Message_Complete_Many', 'Email campaign %s has finished sending at %s. %s emails were sent.');

?>
