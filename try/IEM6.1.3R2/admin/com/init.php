<?php
/**
 * This file contains the main procedure and classes for IEM to run.
 *
 * Every files or call need to include this file one way or another.
 * There are constants that can be define which will change the startup behaviour of the application.
 *
 * A full list of constants which can influence the application when defined are:
 * - IEM_NO_CONTROLLER => Will prevent controller from being invoked by initialization process
 * - IEM_NO_SESSION => This will prevent session files from being created. You will still be able to use IEM::session*, but it will not be presisted accross requests
 * - IEM_CLI_MODE => The script is being run from the command line. REQUEST_URI will not be initialised.
 * - IEM_CRON_JOB => The script was invoked through a cron job. Some sending behaviour is different depending on cron vs manual sending. IEM_CLI_MODE will be assumed.
 *
 *
 * @package interspire.iem
 *
 * @authors Chris Smith
 * @authors Scott Tedmanson
 * @authors Tye Shavik
 * @authors Hendri Kurniawan
 * @authors Michael Knight
 * @authors David Chandra
 * @authors Yudi Tukiaty
 * @authors Micheline Nakhle
 */

/**
 * IEM autoloading function defined using spl_autoload_register.
 *
 * @param String $classname Class name to load
 * 
 * @return Void Returns nothing
 */
function __iem_autoload_function($className)
{
	/*
	 * Checking for existings is far faster than just calling require_once. If
	 * the class already exists, then nothing needs to be done. Whereas
	 * require_once will have to run file checks to see if the file has already
	 * been included. This is a moot point since autoloading is only available
	 * for classes anyways.
	 */
	if (class_exists($className, false)) {
		return;
	}

	// parse the path from the class name
	$path = dirname(__FILE__) . '/lib/' . str_replace('_', '/', $className);

	/*
	 * The .class.php naming convention is now deprecated.
	 */
	if (is_readable($path . '.class.php')) {
		require $path . '.class.php';
	} else {
		require $path . '.php';
	}
}

/**
 * Shutdown function
 * Function that will be called when the script finishes execution.
 *
 * @return Void Returns nothing
 */
function __iem_shutdown_function()
{
	$tempEvent = new EventData_IEM_SYSTEM_SHUTDOWN_BEFORE();
	$tempEvent->trigger();
	unset($tempEvent);

	if (!IEM::configSave()) {
		trigger_error('Cannot save configuration variable to file', E_USER_WARNING);
	}

	$tempEvent = new EventData_IEM_SYSTEM_SHUTDOWN_AFTER();
	$tempEvent->trigger();
	unset($tempEvent);
}

/**
 * Exception handler
 * Function that handle rouge exception
 *
 * @param Exception $e Exception that needs to be handled
 * @return Void Returns nothing
 *
 * @todo handle more exception
 */
function __iem_exception_handler(Exception $e)
{
	// Make sure IEM_PATH is defined
	if (!defined('IEM_PATH')) {
		define('IEM_PATH', dirname(__FILE__));
	}

	$title	= 'The application cannot proceed!';
	$header	= file_get_contents(IEM_PATH . '/templates/upgrade_header.tpl');
	$body	= file_get_contents(IEM_PATH . '/templates/upgrade_body.tpl');
	$footer	= file_get_contents(IEM_PATH . '/templates/upgrade_footer.tpl');
	$msg	= '<p>An internal error occured. Please contact your administrator and describe the steps you took before you encounter this error message.</p><p>Error msg: ' . htmlentities($e->getMessage(), ENT_QUOTES) . '</p>';

	if (class_exists('InterspireStashException', false) && $e instanceof InterspireStashException && in_array($e->getCode(), array(InterspireStashException::CANNOT_READ_DATA, InterspireStashException::CANNOT_WRITE_DATA))) {
		$msg  = '<p>';
		$msg .= 'Please make sure the following directory (including its contents) are writable:';
		$msg .= '<ul><li>' . preg_replace('/\s/', '&nbsp;', htmlentities(realpath(IEM_PATH . '/storage'), ENT_QUOTES)) . '</li></ul>';
		$msg .= '</p>';
		$msg .= '<p><input type=\'button\' value=\'Try Again\' style=\'margin-bottom:20px; font-size:11px\' onclick="document.location.href=\'./index.php\'" /></p>';
	}

	// manually replace the tokens
	$header = str_replace('%%LNG_ControlPanel%%', $title, $header);
	$header = str_replace('%%GLOBAL_CHARSET%%', 'UTF-8', $header);
	$body   = str_replace('{$title}', $title, $body);
	$body   = str_replace('{$msg}', $msg, $body);
	$footer = str_replace('%%LNG_Copyright%%', '', $footer);

	echo $header . $body . $footer;
	
	exit(1);
}

/*
 * Check if date_default_timezone_set is available.
 * 
 * @todo remove this function once our PHP requirement is > PHP 5.2
 */
if (!function_exists('date_default_timezone_set')) {
	/**
	 * date_default_timezone_set
	 * This function is only available to PHP 5.2 or above
	 *
	 * @param string $timezone Timezone to set date.timezone settings to
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	function date_default_timezone_set($timezone)
	{
		static $available_timezone = null;

		// Pupulate timezone cache
		if (is_null($available_timezone)) {
			if (function_exists('timezone_identifiers_list')) {
				$available_timezone = timezone_identifiers_list();
			} else {
				$available_timezone = array();
			}
		}

		// If available_timezone list is not empty, verify it
		if (!empty($available_timezone) && !in_array($timezone, $available_timezone)) {
			return false;
		}

		ini_set('date.timezone', $timezone);
		return true;
	}
}

/*
 * Check if date_default_timezone_get is available.
 * 
 * @todo remove this function once our PHP requirement is > PHP 5.2
 */
if (!function_exists('date_default_timezone_get')) {
	/**
	 * This function is only available to PHP 5.2 or above
	 *
	 * NOTE: This function will NOT query the TZ environment variable nor the 
	 * system settings. If nothing is found, it will simply return UTC.
	 *
	 * @return string Returns current timezone of which date.timezone is set to.
	 */
	function date_default_timezone_get()
	{
		$temp = ini_get('date.timezone');
		
		if (empty($temp)) {
			$temp = 'UTC';
		}

		return $temp;
	}
}



/*
 * Pre-check
 * 
 * @todo Should this be checked each time? Re-do how it defines disabled 
 *       functions.
 * @todo For the above todo, if a good conventoin
 * @todo Check whether or not ini_get is available? If possible catch errors for 
 *       it.
 */

define('SENDSTUDIO_DISABLED_FUNCTIONS', ini_get('disable_functions'));

$disabled_functions = explode(',', SENDSTUDIO_DISABLED_FUNCTIONS);

if (in_array('ini_set', $disabled_functions)) {
	$turn_off_message = "The 'ini_set' function has been disabled by your systems administrator or website host.\nThe application requires this function to be active, please contact your systems administrator or website host to have it enabled again.\n";
	$turn_off_message .= "It will have to be removed from the 'disabled_functions' line in the php.ini file.\n";

	die(nl2br($turn_off_message));
}



// Set up PHP environment
error_reporting(E_ALL);

// PHP > 5.3 will be deprecating this function
@set_magic_quotes_runtime(false);

ini_set('short_tags', false);
$memlimit = ini_get('memory_limit');
$memlimit = strtolower($memlimit);
$memlimit = trim($memlimit,"m");
if((int)$memlimit < 64 ){
    ini_set('memory_limit', '64M');
}
ini_set('track_errors', true);
ini_set('magic_quotes_sybase', false);

// Since we auto-detect the time anyway, we should set up the default time zone to avoid warnings.
@date_default_timezone_set(@date_default_timezone_get());

if (!defined('IEM_CLI_MODE')) {
	define('IEM_CLI_MODE', (defined('IEM_CRON_JOB') || php_sapi_name() == 'cli'));
}

// Initialize any environment that is CLI related
if (IEM_CLI_MODE) {


// Initialize any environment for other than CLI
} else {
	// With IIS, REQUEST_URI is not available, so we have to construct it.
	if (!isset($_SERVER['REQUEST_URI'])) {
		// The following variable will be set on IIS servers that have ISAPI_Rewrite:
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
		}
		// This should be an acceptable fallback:
		if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . '?' . (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
		}
	}
	// It's a similar story with DOCUMENT_ROOT for IIS.
	if (!isset($_SERVER['DOCUMENT_ROOT'])) {
		// We need to be careful about which vars are available.
		if (isset($_SERVER['PATH_INFO'])) {
			$local_path = $_SERVER['PATH_INFO'];
		} else {
			$local_path = $_SERVER['SCRIPT_NAME'];
		}
		if (isset($_SERVER['PATH_TRANSLATED'])) {
			$absolute_path = $_SERVER['PATH_TRANSLATED'];
		} else {
			$absolute_path = $_SERVER['SCRIPT_FILENAME'];
		}
		$local_path = str_replace('/', DIRECTORY_SEPARATOR, $local_path);
		$_SERVER['DOCUMENT_ROOT'] = substr($absolute_path, 0, strpos($absolute_path, $local_path));
	}
}



// Define a constant path that is available in the application
// Load any customization made in the whitelabel directory
$tempFileName = dirname(__FILE__) . '/custom/init-path.php';

if (is_readable($tempFileName)) {
	include $tempFileName;
}

unset ($tempFileName);


// Defines default constants if they were not already defined
if (!defined('IEM_PATH')) {
	define('IEM_PATH', dirname(__FILE__));
}

if (!defined('IEM_STORAGE_PATH')) {
	define('IEM_STORAGE_PATH', IEM_PATH . '/storage');
}

if (!defined('IEM_PUBLIC_PATH')) {
	define('IEM_PUBLIC_PATH', dirname(IEM_PATH));
}

if (!defined('IEM_ADDONS_PATH')) {
	define('IEM_ADDONS_PATH', IEM_PUBLIC_PATH . '/addons');
}



// Set spl_autoload, error and exception handler
spl_autoload_register('__iem_autoload_function');
// TODO: set error handler, and deprecate the existing error handler
// set_error_handler()
set_exception_handler('__iem_exception_handler');



// Include file that will provide "Event" support
require_once IEM_PATH . '/event.php';



// --------------------------------------------------------------------------------
// This is the legacy "init" file that needed to be refactored/deprecated gradually
// as it contains procedure that are obsolete or shouldn't be used anymore.
// TODO: deprecate
// --------------------------------------------------------------------------------
require_once IEM_PATH . '/init-legacy.php';



// --------------------------------------------------------------------------------
// Initialize framework and trigger any events that might be listening
// to the "SYSTEM_STARTUP" (before and after) event
// --------------------------------------------------------------------------------
$tempEvent = new EventData_IEM_SYSTEM_STARTUP_BEFORE();
$tempEvent->trigger();
unset($tempEvent);

// Initialize the framework
if(IEM::configGet() !== false){
	IEM::init();
}


// Register a shutdown function
register_shutdown_function('__iem_shutdown_function');

$tempEvent = new EventData_IEM_SYSTEM_STARTUP_AFTER();
$tempEvent->trigger();
unset($tempEvent);



/*
 * Check to see if the applicatin is installed. If not, redirect it to the
 * installation screen.
 */
if (!IEM::isInstalled() && !IEM::isInstalling()) {
    header('Location: index.php?Page=install');
    
    exit;
}



/*
 * Check to see if the application requires an upgrade. If there are upgrades
 * present, redirect to the upgrade screen.
 */
if (IEM::hasUpgrade() && !IEM::isUpgrading()) {
    header('Location: index.php?Page=upgradenx');
    
    exit;
}



// --------------------------------------------------------------------------------
// Do not invoke the controller when:
// - IEM_NO_CONTROLLER flag is defined
// - and the flag is set to TRUE
//
// This is useful for "addons" or pages that does not follow the standard
// convention of the framework model (such as the legacy cods) and for
// testing purposes.
//
// The controller main functionality is to:
// - re-direct request to the appropriate "function".
// - Currently also checking "Login" information
// --------------------------------------------------------------------------------

ss9O24kwehbehb();

if (!defined('IEM_NO_CONTROLLER') || constant('IEM_NO_CONTROLLER') !== true) {
	$non_allowable_pages = array('init', 'upgrade', 'sendstudio_functions');
	$page                = IEM::requestGetGET('Page', '', 'strtolower');
	$newPage             = false;

    // See whether this is set up or not. If it's not set up, start the installer.
    if (!IEM::isInstalled()) {
    	// We are moving "config.php" to a different directory
    	// Other than for "remote_installer", we will need to check the
    	// existance of older config file
    	if ($page != 'remote_installer') {
    		$page          = 'installer';
    		$tempOldConfig = SENDSTUDIO_BASE_DIRECTORY . '/../includes/config.inc.php';
    		
    		if (is_readable($tempOldConfig)) {
    			require_once $tempOldConfig;
    			
    			if (isset($IsSetup) && $IsSetup == 1) {
    				$page = 'upgrade';
    			}
    		}
    
    		unset($tempOldConfig);
    	}
    
    	// Start up the installer
    	require_once SENDSTUDIO_FUNCTION_DIRECTORY . "/{$page}.php";
    	
    	$system = new $page();
    	
    	$system->Process();
    	
    	exit();
    }

    

    // Redirect the application the the URL specified in the configuration file
    $url_parts = parse_url(SENDSTUDIO_APPLICATION_URL);
    $host      = $url_parts['host'];
    
    if (isset($url_parts['port'])) {
    	$host .= ':'.$url_parts['port'];
    }
    
    if ($host != $_SERVER['HTTP_HOST']) {
    	header('Location: ' . SENDSTUDIO_APPLICATION_URL . '/admin/index.php');
    	exit();
    }


    
    // Make sure that the pages does not contains undesirable characters
    // by replacing all of non-word character with an underscore (_)
    $page = preg_replace('/[^\w]/', '_', $page);
    
    // If someone tries to be tricky, redirect them back to the main page.
    if (!is_file(SENDSTUDIO_FUNCTION_DIRECTORY . '/' . $page . '.php') || in_array($page, $non_allowable_pages)) {
    	$newPage = IEM::requestGetGET('page', false);
    	$newPage = preg_replace('/[^\w]/', '_', $newPage);
    
    	if (!is_file(IEM_PATH . "/pages/{$newPage}.class.php")) {
    		$newPage = false;
    		$page    = 'index';
    	}
    }



    // --------------------------------------------------------------------------------
    // Check whether or not the request is coming from a user that's already logged in.
    //
    // If the user have not logged in yet, we need to check for "IEM_CookieLogin"
    // and "IEM_LoginPreference" cookie. This cookie is used in "remember me" feature.
    //
    // TODO refactor this to IEM::login() function
    // --------------------------------------------------------------------------------
    if (!IEM::getCurrentUser()) {
    	$tempValid  = false;
    	$tempCookie = false;
    	$tempUser   = false;
    
    	// This is not a loop, rather a way to "return early" to avoid nested if
    	// * Comment from a later developer: If you have to do this, there is
    	// * probably a better way to code it. Programming doesn't necessarily
    	// * mean "hacking".
    	while (true) {
    	    // if we are installing or upgrading then we need to bypass this
    	    if (
    	        !IEM::isInstalled() && IEM::isInstalling() ||
				IEM::hasUpgrade()   && IEM::isUpgrading()  ||
				IEM::isCompletingUpgrade()
    	    ) {
    	        $tempValid = true;
    	        
    	        break;
    	    }
    	    
    		// Get cookie
    		$tempCookie = IEM::requestGetCookie('IEM_CookieLogin', array());
    		
    		if (empty($tempCookie)) {
    			break;
    		}
    
    		// Check if cookie contains user information
    		if (!is_array($tempCookie) || !isset($tempCookie['user'])) {
    			break;
    		}
    
    		// Get user
    		$tempUser = new User_API();
    		
    		$tempUser->Load(intval($tempCookie['user']));
    
    		// Check if the user is a valid user
    		if (
    		    !isset($tempUser->settings['LoginCheck']) 
    		    || !$tempUser->userid 
    		    || !$tempUser->Status()
    	    ) {
    			break;
    		}
    
    		// Check whether or not the random number matches
    		if (!$tempUser->settings['LoginCheck'] == $tempCookie['rand']) {
    			break;
    		}
    
    		// The cookie is valid! Update session accordingly
    		IEM::userLogin($tempUser->userid);
    		
    		$tempValid = true;
    
    		// Check if we have login preferences
    		$tempLoginPref = IEM::requestGetCookie('IEM_LoginPreference', array());
    		
    		if (is_array($tempLoginPref) && isset($tempLoginPref['takemeto'])) {
    			header('Location: ' . SENDSTUDIO_APPLICATION_URL . '/admin/' . $tempLoginPref['takemeto']);
    		}
    
    		break;
    	}
        
    	if (!$tempValid) {
    		$page = 'login';
    	}
        
    	unset($tempValid);
    	unset($tempCookie);
    	unset($tempUser);
    } else {
    	$tempUser = GetUser();
    	
    	if (!$tempUser->Find($tempUser->username)) {
    		$page = 'login';
    	}
    
    	unset($tempUser);
    }
    
    
    
	// Include the 'page' we're working with and process it.
	// This is getting the page class from functions directory.
	// Starting from version 5.6, the page structure has been gradually moved.
	if ($newPage === false) {
		require_once SENDSTUDIO_FUNCTION_DIRECTORY . "/{$page}.php";

		$system = new $page();
		
		$system->Process();
		
		unset($system);
	// This is the new page structure
	} else {
		require_once IEM_PATH . "/pages/{$newPage}.class.php";
		
		$tempClassName  = "page_{$newPage}";
		$tempAction     = 'page_' . preg_replace('/[^\w]/', '_',IEM::requestGetGET('action', 'index'));
		$tempPageObject = new $tempClassName();

		// Check if "action" exists
		if (!is_callable(array($tempPageObject, $tempAction))) {
			// page_index will alwas exists (albeit only returning a FALSE)
			$tempAction = 'page_index';
		}

		// Call the function specified by "action" parameter
		$tempOutput = $tempPageObject->{$tempAction}();

		// TODO other return value have no effect at the moment.
		// Currently it only prints out a string
		if (is_string($tempOutput)) {
			echo $tempOutput;
		}

		// Call the page class destructor if it wants to cleanup anything
		unset($tempPageObject);
	}


	// After everything has run, see if we need to keep the "logs" in check.
	$logsystem = GetLogSystem();

	if ($logsystem) {
		$logsystem->PruneSystemLog();
		$logsystem->PruneAdminLog();

		unset($logsystem);
	}


	// The controller should end the request.
	exit();
}
