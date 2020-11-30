<?php

/**
* Language file variables for the jobs bouncing area.
*
* @see GetLang
*
* @version     $Id: jobs_bounce.php,v 1.7 2007/11/01 02:48:20 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* This is all handled by the bounce.php language file so we'll just include that.
*/
if (!defined('LNG_BadLogin_Subject_Cron')) {
	require_once(dirname(__FILE__) . '/bounce.php');
}
