<?php
/**
 * This file contains "legacy" initialization procedure.
 * The sole purpose of this file is to bridge the gap between legacy procedures with the newer code base.
 * The use of this file is descouraged, as the file WILL be deprecated.
 *
 * @package interspire.iem
 */


$_POST		= stripslashes_deep($_POST);
$_GET		= stripslashes_deep($_GET);
$_COOKIE	= stripslashes_deep($_COOKIE);
$_REQUEST	= stripslashes_deep($_REQUEST);

$admindir = dirname(dirname(__FILE__));

/**
* Set up our base variables. These are used all over the place.
*
* @see GetLang
* @see SendStudio_Functions::GetApi
* @see SendStudio_Functions::GetAttachments
* @see SendStudio_Functions::ParseTemplate
*/

define('LNG_SENDSTUDIO_VERSION', IEM::VERSION);

define('SENDSTUDIO_BASE_DIRECTORY', $admindir);
define('SENDSTUDIO_INCLUDES_DIRECTORY', $admindir . '/includes');
define('SENDSTUDIO_LANGUAGE_DIRECTORY', IEM_PATH . '/language');
define('SENDSTUDIO_TEMPLATE_DIRECTORY', IEM_PATH . '/templates');
define('SENDSTUDIO_FUNCTION_DIRECTORY', $admindir . '/functions');
define('SENDSTUDIO_LIB_DIRECTORY', SENDSTUDIO_FUNCTION_DIRECTORY . '/lib');
define('SENDSTUDIO_API_DIRECTORY', SENDSTUDIO_FUNCTION_DIRECTORY . '/api');
define('SENDSTUDIO_IMPORT_DIRECTORY', SENDSTUDIO_BASE_DIRECTORY . '/import');
define('SENDSTUDIO_MODULE_BASEDIRECTORY', SENDSTUDIO_BASE_DIRECTORY . '/modules');

/**
 * To put SendStudio in "Debug Mode", set this to true
 */
define('SENDSTUDIO_DEBUG_MODE', false);

/**
 * This is also defined in the ../ping.php file
 * See inline comments there about why the init.php isn't just included.
*/
define('TEMP_DIRECTORY', $admindir .'/temp');

/**
* Check for the config file.
* If it's not around, set default variables for the install process.
*/
$config_file = SENDSTUDIO_INCLUDES_DIRECTORY . '/config.php';
if (is_file($config_file)) {
	require_once($config_file);
}

/**
* If sendstudio isn't set up, then check to see if we have a backup available.
* If we do, load that up.
* If it's a proper config (SENDSTUDIO_IS_SETUP etc)
* then check to see if we can copy it to it's proper location.
* Even if we can't copy it, we'll load it up and be able to get in to sendstudio anyway.
*/
if (!defined('SENDSTUDIO_IS_SETUP')) {
	if (is_readable(TEMP_DIRECTORY)) {
		$bkp_file = TEMP_DIRECTORY . '/config.bkp.php';
		if (is_file($bkp_file)) {
			$backup_file = $bkp_file;
			require_once($bkp_file);
		}
	} else {
		// see if the old location had an available backup.
		// if there is, use that and make sure we copy the right backup file to the new config.
		$old_bkp_file = SENDSTUDIO_INCLUDES_DIRECTORY . '/config.bkp.php';
		if (is_file($old_bkp_file)) {
			$backup_file = $old_bkp_file;
			require_once($old_bkp_file);
		}
	}

	if (defined('SENDSTUDIO_IS_SETUP') && SENDSTUDIO_IS_SETUP == 1) {
		$copy = false;
		if (is_file($config_file)) {
			if (is_writable($config_file)) {
				$copy = true;
			}
		} else {
			if (is_writable(SENDSTUDIO_INCLUDES_DIRECTORY)) {
				$copy = true;
			}
		}
		if ($copy) {
			@copy($backup_file, $config_file);
		} else {
			echo "Your <strong>admin/includes/config.php</strong> file is not writeable and cannot be restored to a previously backed up version. Please change the file permissions of this file so that it is writeable (CHMOD 646, 664 etc)";
			exit(1);
		}
	}
}

/**
* If sendstudio still isn't set up (ie the config file is missing &/or the backup file is missing or incomplete)
* Then set the default values so we can handle the fresh install or upgrade from v2004.
*/
if (!defined('SENDSTUDIO_IS_SETUP')) {
	define('SENDSTUDIO_IS_SETUP', false);
	define('SENDSTUDIO_APPLICATION_URL', false);
	define('SENDSTUDIO_LICENSEKEY', false);
	define('SENDSTUDIO_DEFAULTCHARSET', 'UTF-8');
}

/**
 * If the app is set up, we can include the addons and also the storage interfaces.
 * Once we have included those, we need to include the event system.
 *
 * We need to include the addon system first so the storage/event systems have the class/objects they need to work with.
 *
 * Then, include the database files and set up the db connection.
 * This is done before including the user object so the database object is 'complete' and not invalid.
 */
if (SENDSTUDIO_IS_SETUP) {
	require_once(SENDSTUDIO_BASE_DIRECTORY . '/addons/interspire_addons.php');
	Interspire_Addons::SetUrl(SENDSTUDIO_APPLICATION_URL . '/admin');

	require_once(SENDSTUDIO_API_DIRECTORY . '/settings.php');
	$settings_api = new Settings_API();
}

// SENDSTUDIO_SERVERTIMEZONE is no longer stored in database, rather it will be defined here pending deprecation
// TODO depracate in favour of IEM framework
if (!defined('SENDSTUDIO_SERVERTIMEZONE')) {
        $tempTimeZone = preg_replace('/^([+-])0?(\d{1,2})(\d\d)/', '$1$2:$3', date('O'));
	if ($tempTimeZone == '+0:00') {
		$tempTimeZone = '';
	}
	define('SENDSTUDIO_SERVERTIMEZONE', "GMT{$tempTimeZone}");
}
if (SENDSTUDIO_IS_SETUP) {
        require_once(IEM_PATH . '/ext/interspire_log/interspire_log.php');
	if(!IEM::isUpgrading()){
            set_error_handler('HandlePHPErrors');
        }
}

/**
* GetLogSystem
* Gets the log system set up with the appropriate options ready for the error handler to use.
*
* @uses IEM::getDatabase()
* @uses Interspire_Log
* @see set_error_handler
*/
function GetLogSystem()
{
	static $logsystem = null;

	if (is_null($logsystem)) {
		if (!class_exists('Interspire_Log', false)) {
			$logsystem = false;
			return false;
		}

		$logsystem = new Interspire_Log(true, false);
		$db = IEM::getDatabase();
		$logsystem->SetLogTypes(array('sql','php'));
		$logsystem->SetDb($db);
		$logsystem->SetSeverities('all');
		$logsystem->SetGeneralLogSize(5000);
	}

	return $logsystem;
}

/**
* These two options got added in NX1.0.5
*/
defined('SENDSTUDIO_MAX_IMAGEWIDTH') or define('SENDSTUDIO_MAX_IMAGEWIDTH', 700);
defined('SENDSTUDIO_MAX_IMAGEHEIGHT') or define('SENDSTUDIO_MAX_IMAGEHEIGHT', 400);


/**
* These options got added in NX1.1.0
*/
defined('SENDSTUDIO_BOUNCE_ADDRESS') or define('SENDSTUDIO_BOUNCE_ADDRESS', '');
defined('SENDSTUDIO_BOUNCE_SERVER') or define('SENDSTUDIO_BOUNCE_SERVER', '');
defined('SENDSTUDIO_BOUNCE_USERNAME') or define('SENDSTUDIO_BOUNCE_USERNAME', '');
defined('SENDSTUDIO_BOUNCE_PASSWORD') or define('SENDSTUDIO_BOUNCE_PASSWORD', '');
defined('SENDSTUDIO_BOUNCE_IMAP') or define('SENDSTUDIO_BOUNCE_IMAP', '');
defined('SENDSTUDIO_BOUNCE_EXTRASETTINGS') or define('SENDSTUDIO_BOUNCE_EXTRASETTINGS', '');


/**
* These options got added in NX1.1.4
*/
defined('SENDSTUDIO_ALLOW_EMBEDIMAGES') or define('SENDSTUDIO_ALLOW_EMBEDIMAGES', 1);
defined('SENDSTUDIO_DEFAULT_EMBEDIMAGES') or define('SENDSTUDIO_DEFAULT_EMBEDIMAGES', 1);
defined('SENDSTUDIO_ALLOW_ATTACHMENTS') or define('SENDSTUDIO_ALLOW_ATTACHMENTS', 1);
defined('SENDSTUDIO_ATTACHMENT_SIZE') or define('SENDSTUDIO_ATTACHMENT_SIZE', 2048);


/**
* These got added in NX1.2
*/
defined('SENDSTUDIO_SYSTEM_MESSAGE') or define('SENDSTUDIO_SYSTEM_MESSAGE', '');
defined('SENDSTUDIO_CRON_SEND') or define('SENDSTUDIO_CRON_SEND', 5);
defined('SENDSTUDIO_CRON_AUTORESPONDER') or define('SENDSTUDIO_CRON_AUTORESPONDER', 5);
defined('SENDSTUDIO_CRON_BOUNCE') or define('SENDSTUDIO_CRON_BOUNCE', 60);
defined('SENDSTUDIO_EMAILSIZE_WARNING') or define('SENDSTUDIO_EMAILSIZE_WARNING', 0);
defined('SENDSTUDIO_EMAILSIZE_MAXIMUM') or define('SENDSTUDIO_EMAILSIZE_MAXIMUM', 0);


/**
* These got added in NX1.4.
*/
defined('SENDSTUDIO_SEND_TEST_MODE') or define('SENDSTUDIO_SEND_TEST_MODE', 0);
defined('SENDSTUDIO_RESEND_MAXIMUM') or define('SENDSTUDIO_RESEND_MAXIMUM', 3);
defined('SENDSTUDIO_BOUNCE_AGREEDELETE') or define('SENDSTUDIO_BOUNCE_AGREEDELETE', 1);


/**
* These got added in IEM 6.
*/
defined('SENDSTUDIO_BOUNCE_AGREEDELETEALL') or define('SENDSTUDIO_BOUNCE_AGREEDELETEALL', 0);
defined('SENDSTUDIO_MAXHOURLYRATE') or define('SENDSTUDIO_MAXHOURLYRATE', 0);
defined('SENDSTUDIO_MAXOVERSIZE') or define('SENDSTUDIO_MAXOVERSIZE', 0);

/**
* SENDSTUDIO_WHITE_LABEL
* If this is set to true, then no version checks are made to see if you are running the latest version.
*/
define('SENDSTUDIO_WHITE_LABEL', false);

/**
* This tells sendstudio whether a database update is required or not.
*
* DO NOT CHANGE THIS VALUE
* UNLESS YOU KNOW WHAT YOU ARE DOING.
*/
define('SENDSTUDIO_DATABASE_VERSION', IEM::DATABASE_VERSION);

require_once (SENDSTUDIO_LIB_DIRECTORY . '/general/utf8.php');

require_once (SENDSTUDIO_FUNCTION_DIRECTORY . '/process.php');

$safe_mode = (bool)ini_get('safe_mode');
define('SENDSTUDIO_SAFE_MODE', (int)$safe_mode);
define('SENDSTUDIO_FOPEN', ini_get('allow_url_fopen'));
define('SENDSTUDIO_CURL', function_exists('curl_init'));


define('SENDSTUDIO_COOKIE_PREFIX', 'ss_');

define('SENDSTUDIO_RESOURCES_DIRECTORY', $admindir . '/resources');
define('SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY', SENDSTUDIO_RESOURCES_DIRECTORY . '/email_templates');

define('SENDSTUDIO_FORM_DESIGNS_DIRECTORY', SENDSTUDIO_RESOURCES_DIRECTORY . '/form_designs');

define('SENDSTUDIO_RESOURCES_URL', SENDSTUDIO_APPLICATION_URL . '/admin/resources');

define('SENDSTUDIO_IMAGE_DIRECTORY', $admindir . '/images');
define('SENDSTUDIO_IMAGE_URL', SENDSTUDIO_APPLICATION_URL . '/admin/images');
define('SENDSTUDIO_STYLE_URL', SENDSTUDIO_APPLICATION_URL . '/admin/styles');

define('SENDSTUDIO_TEMP_URL', SENDSTUDIO_APPLICATION_URL . '/admin/temp');

$GLOBALS['SendStudioURL'] = SENDSTUDIO_APPLICATION_URL;
$GLOBALS['SendStudioImageURL'] = SENDSTUDIO_IMAGE_URL;
$GLOBALS['SendStudioStyleURL'] = SENDSTUDIO_STYLE_URL;

define('SENSTUDIO_BASE_APPLICATION_URL', dirname(SENDSTUDIO_APPLICATION_URL));

define('SENDSTUDIO_ERROR_FATAL', 1);
define('SENDSTUDIO_ERROR_ERROR', 2);
define('SENDSTUDIO_ERROR_PARSE', 4);
define('SENDSTUDIO_ERROR_NOTICE', 8);
define('SENDSTUDIO_ERROR_CORE_ERROR', 16);
define('SENDSTUDIO_ERROR_CORE_WARNING', 32);

/**
* Set the HTML_CHARSET
*
* The php function htmlspecialchars only allows certain character sets.
* If the default charset from the config file is in the allowed list, then use that.
* If it's not, set it to utf-8.
*/
$allowed_html_charsets = array(
	'ISO-8859-1',
	'UTF-8',
);

if (in_array(SENDSTUDIO_DEFAULTCHARSET, $allowed_html_charsets)) {
	define('SENDSTUDIO_CHARSET', SENDSTUDIO_DEFAULTCHARSET);
} else {
	define('SENDSTUDIO_CHARSET', 'UTF-8');
}

/**
* We're always going to be using the user file to check permissions. let's load 'er up.
*/
require_once(SENDSTUDIO_API_DIRECTORY . '/user.php');

/**
* Finally, include our general file
*/
require_once(SENDSTUDIO_LIB_DIRECTORY . '/general/general.php');

/**
* Include our timezone converter file.
*/
require_once(SENDSTUDIO_LIB_DIRECTORY . '/general/convertdate.php');

/**
* GetLang
* Returns the defined language variable based on the name passed in.
*
* If a default value is NOT specified (or specified to NULL), the function WILL STOP execution
* whenever a language definition is NOT found. If it is specified, the function will
* return the specified default value instead.
*
* @param String $langvar Name of the language variable to retrieve.
* @param String $default Default value to be returned if language definition does not exists
*
* @return String Returns the defined string, if it doesn't exist (and default is not specified) the script execution will be halted.
*/
function GetLang($langvar=false, $default = null)
{
	static $array_to_replace_from = false;
	static $array_to_replace_to = false;

	if (!$langvar) {
		return '';
	}

	if (!defined('LNG_' . $langvar)) {
		// Language definition is not found, return a default value if it is defined
		if (!is_null($default)) {
			return strval($default);
		}

		// Make note of where the error occured
		$message = '';
		if (function_exists('debug_backtrace')) {
			$btrace = debug_backtrace();
			$called_from = $btrace[0];
			$message = ' (Called from ' . basename($called_from['file']) . ', line ' . $called_from['line'] . ')';
		}
		trigger_error("Langvar '{$langvar}' doesn't exist: " . $message, E_USER_NOTICE);

		return $langvar;
	}

	$var = constant('LNG_' . $langvar);

	if (!$array_to_replace_from || !$array_to_replace_to) {
		$agency_edition_info = get_agency_license_variables();

		$array_to_replace_from = array(
			'%%WHITELABEL_INFOTIPS%%',
			'%%IEM_SYSTEM_LICENSE_TRIALUSER_TRIALDAYS%%',
			'%%IEM_SYSTEM_LICENSE_TRIALUSER_EMAILLIMIT%%',
		);
		defined('LNG_NumberFormat_Thousands') or define('LNG_NumberFormat_Thousands',',');
		defined('LNG_NumberFormat_Dec') or define('LNG_NumberFormat_Dec', '.');
		$array_to_replace_to = array(
			IEM::enableInfoTipsGet(),
			$agency_edition_info['trial_days'],
			number_format((float)$agency_edition_info['trial_email_limit'], 0, LNG_NumberFormat_Dec, LNG_NumberFormat_Thousands)
		);
	}

	return str_replace($array_to_replace_from, $array_to_replace_to, $var);
}

/**
* AdjustTime
* Adjusts the time based on the users timezone and the server timezone.
*
* @see GetUser
* @see User_API::UserTimeZone
* @see SENDSTUDIO_SERVERTIMEZONE
* @see ConvertDate
*
* @return Int The adjusted timestamp.
*/
function AdjustTime($time=0, $convert_to_gmt=true, $date_format='', $from_servertime=false)
{
	$user = GetUser();

	if (!is_object($user)) {
		return false;
	}

	if (!isset($GLOBALS['DateConverter'])) {
		$GLOBALS['DateConverter'] = new ConvertDate(SENDSTUDIO_SERVERTIMEZONE, $user->Get('usertimezone'));
	}

	if ($convert_to_gmt) {
		if ((int)$time < 0) {$time = 0;}
		if ($time == 0) {return gmmktime();}
		$hr = $time[0]; $min = $time[1]; $sec = $time[2]; $mon = $time[3]; $day = $time[4]; $yr = $time[5];
		if ($from_servertime) {
			return $GLOBALS['DateConverter']->ConvertToGMTFromServer($hr, $min, $sec, $mon, $day, $yr);
		}
		return $GLOBALS['DateConverter']->ConvertToGMT($hr, $min, $sec, $mon, $day, $yr);
	}
	return $GLOBALS['DateConverter']->ConvertFromGMT($time, $date_format);
}

/**
 * Fix broken unix timestamp that is obtained from API::GetServerTime()
 *
 * Because API::GetServerTime() is actually returning server's time, we will need
 * to convert this to actual GMT time.
 *
 * @param integer $brokenGMTTIme Time obtained from running API::GetServerTime()
 * @return integer Returns real GMT timestamp.
 */
function FixBrokenGMTTime($brokenGMTTIme)
{
	static $offset = null;

	if (is_null($offset)) {
		if (preg_match('/(\-|\+)(\d+)\:(\d+)/', date('P'), $matches)) {
			list(, $tempSign, $tempHour, $tempMinute) = $matches;

			$offset = ($tempHour * 3600) + ($tempMinute * 60);

			if ($tempSign == '-') {
				$offset *= -1;
			}
		} else {
			$offset = 0;
		}
	}

	return $brokenGMTTIme + $offset;
}

/**
* GetUser
* If a userid is passed in, it will create a new user object and return the reference to it.
* If no userid is passed in, it will get the current user from the session.
*
* @param Int $userid If a userid is passed in, it will create a new user object and return it. If there is no userid it will get the current user from the session.
*
* @see User
*
* @return User_API The user object.
*
* @todo deprecate this in favour of IEM::getCurrentUser function
*/
function GetUser($userid=0)
{
	if ($userid == 0) {
		$UserDetails = IEM::getCurrentUser();
		return $UserDetails;
	}

	if ($userid == -1) {
		$user = new User_API();
	} else {
		$user = new User_API($userid);
	}
	return $user;
}

/**
* GetSession
* Checks whether the session is setup. Will start if it needs to.
*
* @return Object Returns the SendStudio session object.
*/
function GetSession()
{
	static $s = null;

	if (is_null($s)) {
		$s = new Session();
	}

	return $s;
}

/**
* Recursively use stripslashes on an array or a value
* If magic_quotes_gpc is on, strip all the slashes it added.
* By doing this we can be sure that all the gpc vars never have slashes and so
* we will always need to treat them the same way
*
* @param Mixed $value The array or value to perform stripslashes on
*
* @return Mixed The array or value which was stripslashed
*/
function stripslashes_deep($value='')
{
	if (!get_magic_quotes_gpc()) {
		return $value;
	}
	if (is_array($value)) {
		foreach ($value as $k=>$v) {
			$sk = stripslashes($k); // we may need to strip the key as well
			$sv = stripslashes_deep($v);
			if ($sk != $k) {
				unset($value[$k]);
			}
			$value[$sk] = $sv;
		}
	} else {
		$value = stripslashes($value);
	}
	return $value;
}

/**
 * CheckCronSchedule
 *
 * Checks whether a jobtype is allowed to run or not based on the settings saved by the admin user.
 * The settings api is loaded and checked to make sure
 * - the jobtype is valid (ie you are not making up a new 'jobtype')
 * - that it is time to run the job
 *
 * If it is time to run the job, then update the time that the job was last run so it can be checked again in the future.
 *
 * @param String $jobtype The type of job that we are checking
 *
 * @see Settings_API
 * @see Settings_API::Schedule
 * @see Settings_API::SetRunTime
 *
 * @return boolean Returns FALSE if the jobtype is invalid or it is not yet time to run, TRUE otherwise
 */
function CheckCronSchedule($jobtype = 'send')
{
	if (!class_exists('settings_api', false)) {
		require_once(SENDSTUDIO_API_DIRECTORY . '/settings.php');
	}
	$settings_api = new Settings_API();
	$settings_api->Load();

	/**
	* Check we're looking for a valid type of job.
	* If we're not, return false and don't allow it to be run.
	*/
	$scheduled_events = $settings_api->Get('Schedule');

	$allowed_jobtypes = array_keys($scheduled_events);

	if (!in_array($jobtype, $allowed_jobtypes)) {
		unset($settings_api);
		return false;
	}

	/**
	* By default, we don't allow sending to occur.
	*/
	$allow_job = false;

	$last_send_time = $scheduled_events[$jobtype]['lastrun'];

	$next_send_time = 0;

	$option_name = 'SENDSTUDIO_CRON_' . strtoupper($jobtype);

	/**
	 * Check the variable exists and is defined.
	 * An addon could be in the 'schedule' array but not set up yet.
	 * If that's the case, it can't be allowed to run.
	 */
	if (!defined($option_name)) {
		unset($settings_api);
		return false;
	}

	$ss_jobtype = constant($option_name);

	/**
	 * If the job is disabled, then don't run it.
	 * It is possible for  triggeremails_p to hold 0 value, so exempt this particular job and continue with the process.
	 */
	if ($ss_jobtype == 0 && $jobtype != 'triggeremails_p') {
		unset($settings_api);
		return false;
	}


	/**
	* if last_send_time is less than 0, we have not sent before.
	* If it's greater than 0, then we have sent before and we need to check the frequency.
	*/
	if ($last_send_time > 0) {
		$next_send_time = $last_send_time + ($ss_jobtype * 60) - 5;
	}

	$server_time = time();

	if ($server_time >= $next_send_time) {
		$allow_job = true;


	// Force triggeremails_p to run early in the morning around 12 AM.
	// Once forced, it will move the schedule to around 12 AM.
	// It does not really matter if the processing is run more than once in 24 hours period.
	// The only concern at the moment is that the process may take quite some time to complete.
	} elseif ($jobtype == 'triggeremails_p') {
		$early_morning = mktime(0, 0, 0, date('n'), date('j'), date('Y'));

		// if CRON have NOT been executed for 'triggeremails_p' today, then allow the job.
		if ($last_send_time < $early_morning) {
			$allow_job = true;
		}
	}

	if ($allow_job) {
		/**
		* Set the last run time to now so next time it runs it's remembered properly.
		*/
		$settings_api->SetRunTime($jobtype);
	}

	unset($settings_api);

	return $allow_job;

}

/**
 * GetTemplateSystem
 * Get templating system
 * @param String $templateDirectory Template base directory (OPTIONAL)
 * @return Object Returns templating system
 *
 * @uses IEM_InterspireTemplate
 */
function GetTemplateSystem($templateDirectory = null)
{
	$directory = SENDSTUDIO_TEMPLATE_DIRECTORY . '/';
	$cache_directory = IEM_STORAGE_PATH . '/template-cache';

	if (!is_null($templateDirectory)) {
		if (!is_dir($templateDirectory)) {
			throw new Exception('Template directory does not exists');
		}

		$directory = "{$templateDirectory}/;{$directory}";
	}

	if (!is_dir($cache_directory)) {
		if (!@mkdir($cache_directory)) {
			throw new Exception('Cannot create template cache directory');
		}

		@chmod($cache_directory, 0777);
	}

	$tpl = new IEM_InterspireTemplate();
	$tpl->SetLangFunction('GetLang');
	$tpl->SetCachePath($cache_directory);
	$tpl->SetTemplatePath($directory, 'tpl');
	$tpl->SetCharacterSet(SENDSTUDIO_CHARSET);

	return $tpl;
}

/**
* Include the flash message file.
* This needs to be included after the template system as it requires the templatesystem to return the messages.
*/
require_once(SENDSTUDIO_LIB_DIRECTORY . '/general/flashmessages.php');