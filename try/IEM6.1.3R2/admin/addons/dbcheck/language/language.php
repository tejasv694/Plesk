<?php
/**
 * This is the language file for the 'dbcheck' addon.
 *
 * @package Interspire_Addons
 * @subpackage Addons_dbcheck
 */

define('LNG_Addon_dbcheck_Menu_Text', 'Check Database');
define('LNG_Addon_dbcheck_Menu_Description', 'Checks the integrity of the database.');

define('LNG_Addon_dbcheck_Heading', 'Check Database Integrity');
define('LNG_Addon_dbcheck_Intro', 'Click the button below to check your database for errors.');
define('LNG_Addon_dbcheck_Heading_Checked', 'Database Check Finished');
define('LNG_Addon_dbcheck_Heading_Repaired', 'Database Repair Finished');

define('LNG_Addon_dbcheck_Button_Start', 'Check Database Now...');
define('LNG_Addon_dbcheck_Button_Continue', 'Continue');
define('LNG_Addon_dbcheck_Button_FixProblems', 'Fix Problems');

define('LNG_Addon_dbcheck_Begin', 'Click "' . LNG_Addon_dbcheck_Button_Start . '" to begin.');

define('LNG_Addon_dbcheck_ProgressTitleCheck', 'Database Check in Progress...');
define('LNG_Addon_dbcheck_ProgressIntroCheck', 'Please wait while your database is checked for errors...');
define('LNG_Addon_dbcheck_ProgressTitleFix', 'Database Repair in Progress...');
define('LNG_Addon_dbcheck_ProgressIntroFix', 'Please wait while your database is being repaired...');

define('LNG_Addon_dbcheck_CheckingTable', 'Checking table %s (step %s of %s)');

define('LNG_Addon_dbcheck_Problems', 'There were %d problem(s) found with your database and they are listed below.');
define('LNG_Addon_dbcheck_NoProblems', 'There were no problems found with your database.');
define('LNG_Addon_dbcheck_Repaired', 'Your database was repaired successfully and should now be free from errors.');

define('LNG_Addon_dbcheck_Problem_NotPresent', '%d table(s) are not present');
define('LNG_Addon_dbcheck_Problem_Corrupt', '%d table(s) are marked as corrupt');
define('LNG_Addon_dbcheck_Problem_MissingColumns', '%d table(s) are missing columns');
define('LNG_Addon_dbcheck_Problem_MissingIndexes', '%d table(s) are missing indexes');

define('LNG_Addon_dbcheck_Advice', "Please note that only missing indexes and some types of table corruption can be fixed. If you have corrupt or missing tables that can not be fixed, please contact your web host. If you have missing table columns, please contact support.");

define('LNG_Addon_dbcheck_DisplayReport', 'View Check Code');
define('LNG_Addon_dbcheck_DisplayReport_Intro', 'The check code for your database is shown below. If you cannot repair your database using this wizard, please send the code below to the support team for help.');
define('LNG_Addon_dbcheck_DisplayError', '(display errors)');
