<?php
/**
 * This is the language file for the 'checkpermissions' addon.
 * The addon just goes through particular files/folders to check they are writable.
 *
 * @package Interspire_Addons
 * @subpackage Addons_checkpermissions
 */

define('LNG_Addon_checkpermissions_Menu_Text', 'Check Permissions');

define('LNG_Addon_checkpermissions_Heading', 'Check the permissions in your application');
define('LNG_Addon_checkpermissions_Intro', 'Check the permissions of the files and folders in your application. This will let you know if there are any problems so they can be resolved.');
define('LNG_Addon_checkpermissions_GoButton', 'Start Checking');

define('LNG_Addon_checkpermissions_ProgressTitle', 'Permission Checking in progress...');
define('LNG_Addon_checkpermissions_ProgressIntro', 'Please wait while we check all of the permissions in your system...');

define('LNG_Addon_checkpermissions_CheckingPermission', 'Checking permission %s (step %s of %s)');

define('LNG_Addon_checkpermissions_FollowingFileFolders_OK', 'OK');
define('LNG_Addon_checkpermissions_FollowingFileFolders_NotOK', 'NOT OK');

/**
**************************
* Changed/Added in 5.5.0
**************************
*/

define('LNG_Addon_checkpermissions_Menu_Description', 'This will go through files and folders and look for permissions that need adjusting.');
define('LNG_Addon_checkpermissions_FollowingFileFolders', 'The following files or folders are');
define('LNG_Addon_checkpermissions_CheckAgain', 'Check Again');
define('LNG_Addon_checkpermissions_WhatToDo', 'The files and folders that are not OK are not writable by the web server. Try changing their permissions to CHMOD 777 or similar. You may need to contact your host if you are unable to do this.');
