<?php
/**
* The Settings API.
*
* @version     $Id: settings.php,v 1.47 2008/03/05 04:38:45 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Settings_API
*/


/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This will load settings, set them and save them all for you.
*
* @package API
* @subpackage Settings_API
*/
class Settings_API extends API
{

	/**
	* A list of the current settings. This is used by load and save to store things temporarily.
	*
	* @see Areas
	* @see Load
	* @see Save
	*
	* @var Array
	*/
	var $Settings = array();

	/**
	* Used to store the location of the settings file temporarily.
	*
	* @see Settings_API
	*
	* @var String
	*/
	var $ConfigFile = false;

	/**
	* A list of areas that we hold settings for. This is used by 'save' in conjunction with Settings to see what will get saved.
	*
	* @see Save
	*
	* @var Array
	*/
	var $Areas = array (
		'config' => array (
			'DATABASE_TYPE',
			'DATABASE_USER',
			'DATABASE_PASS',
			'DATABASE_HOST',
			'DATABASE_NAME',
			'DATABASE_UTF8PATCH',
			'TABLEPREFIX',
			'LICENSEKEY',
			'APPLICATION_URL',
		),

		'whitelabel' => array (
			'LNG_ApplicationTitle',
			'LNG_Copyright',
			'LNG_Default_Global_HTML_Footer',
			'LNG_Default_Global_Text_Footer',
			'LNG_AccountUpgradeMessage',
			'LNG_FreeTrial_Expiry_Login',
			'APPLICATION_LOGO_IMAGE',
			'APPLICATION_FAVICON',
			'UPDATE_CHECK_ENABLED',
			'SHOW_SMTP_COM_OPTION',
			'SHOW_INTRO_VIDEO',
		),

		'SMTP_SERVER',
		'SMTP_USERNAME',
		'SMTP_PASSWORD',
		'SMTP_PORT',

		'BOUNCE_ADDRESS',
		'BOUNCE_SERVER',
		'BOUNCE_USERNAME',
		'BOUNCE_PASSWORD',
		'BOUNCE_IMAP',
		'BOUNCE_EXTRASETTINGS',
		'BOUNCE_AGREEDELETE',
		'BOUNCE_AGREEDELETEALL',

		'HTMLFOOTER',
		'TEXTFOOTER',

		'FORCE_UNSUBLINK',
		'MAXHOURLYRATE',
		'MAXOVERSIZE',
		'CRON_ENABLED',
		'DEFAULTCHARSET',
		'EMAIL_ADDRESS',
		'IPTRACKING',
		'USEMULTIPLEUNSUBSCRIBE',
		'CONTACTCANMODIFYEMAIL',
		'MAX_IMAGEWIDTH',
		'MAX_IMAGEHEIGHT',

		'ALLOW_EMBEDIMAGES',
		'DEFAULT_EMBEDIMAGES',

		'ALLOW_ATTACHMENTS',
		'ATTACHMENT_SIZE',

		'CRON_SEND',
		'CRON_AUTORESPONDER',
		'CRON_BOUNCE',
		'CRON_TRIGGEREMAILS_S',
		'CRON_TRIGGEREMAILS_P',
		'CRON_MAINTENANCE',

		'EMAILSIZE_WARNING',
		'EMAILSIZE_MAXIMUM',

		'SYSTEM_MESSAGE',
		'SYSTEM_DATABASE_VERSION',
		'SEND_TEST_MODE',
		'RESEND_MAXIMUM',

		'SHOW_SMTPCOM_OPTION',

		'SECURITY_WRONG_LOGIN_WAIT',
		'SECURITY_WRONG_LOGIN_THRESHOLD_COUNT',
		'SECURITY_WRONG_LOGIN_THRESHOLD_DURATION',
		'SECURITY_BAN_DURATION',

		'CREDIT_INCLUDE_AUTORESPONDERS',
		'CREDIT_INCLUDE_TRIGGERS',
		'CREDIT_WARNINGS',

		'DEFAULT_EMAILSIZE'
	);

	/**
	* send_options
	* The options for how often scheduled sending can run. This is used by the settings page to work out which options to show.
	*
	* @var Array
	*/
	var $send_options = array(
		'0'		=> 'disabled',
		'1'		=> '1_minute',
		'2'		=> '2_minutes',
		'5'		=> '5_minutes',
		'10'	=> '10_minutes',
		'15'	=> '15_minutes',
		'20'	=> '20_minutes',
		'30'	=> '30_minutes',
	);

	/**
	* autoresponder_options
	* The options for how often autoresponders can run. This is used by the settings page to work out which options to show.
	*
	* @var Array
	*/
	var $autoresponder_options = array(
		'0'		=> 'disabled',
		'1'		=> '1_minute',
		'2'		=> '2_minutes',
		'5'		=> '5_minutes',
		'10'	=> '10_minutes',
		'15'	=> '15_minutes',
		'20'	=> '20_minutes',
		'30'	=> '30_minutes',
		'60'	=> '1_hour',
		'120'	=> '2_hours',
		'720'	=> '12_hours',
		'1440'	=> '1_day'
	);

	/**
	* bounce_options
	* The options for how often bounce processing can run. This is used by the settings page to work out which options to show.
	*
	* @var Array
	*/
	var $bounce_options = array(
		'0'			=> 'disabled',
		'60'		=> '1_hour',
		'120'		=> '2_hours',
		'240'		=> '4_hours',
		'360'		=> '6_hours',
		'720'		=> '12_hours',
		'1440'		=> '1_day',
	);

	/**
	 * triggeremails_s_options
	 * The options for how often triggeremails send process can run. This is used by the settings page to work out which options to show.
	 *
	 * @var Array
	 */
	var $triggeremails_s_options = array(
		'0'		=> 'disabled',
		'1'		=> '1_minute',
		'2'		=> '2_minutes',
		'5'		=> '5_minutes',
		'10'	=> '10_minutes',
		'15'	=> '15_minutes',
		'20'	=> '20_minutes',
		'30'	=> '30_minutes',
	);

	/**
	 * triggeremails_p_options
	 * The options for how often triggeremails send process can run. This is used by the settings page to work out which options to show.
	 *
	 * @var Array
	 */
	var $triggeremails_p_options = array(
		'0'		=> 'disabled',
		'1440'	=> '1_day'
	);


	/**
	 * $maintenance_options
	 * The option to set how often maintainace  process can run. This is used by the settings page to work out which options to show.
	 *
	 * @var Array
	 */
	var $maintenance_options = array(
		'0'			=> 'disabled',
		'180'		=> '3_hours',
		'360'		=> '6_hours',
		'540'		=> '9_hours',
		'1440'		=> '1_day',
	);

	/**
	* If cron is enabled, this setting is checked to make sure it's working ok. This allows the settings page to show a warning about it being set up properly or not.
	*
	* @see Load
	*
	* @var Boolean
	*/
	var $cronok = false;

	/**
	* The first time cron runs it will store information in cronrun1.
	*
	* @see Load
	*
	* @var Boolean
	*/
	var $cronrun1 = 0;

	/**
	* The second time cron runs it will store information in cronrun2.
	*
	* @see Load
	*
	* @var Boolean
	*/
	var $cronrun2 = 0;

	/**
	* The database version number.
	*
	* @see Load
	*/
	var $database_version = -1;

	/**
	* Job schedule & when jobs were last run.
	*
	* @see CheckCron
	*/
	var $Schedule = array (
		'autoresponder' => array (
			'lastrun' => -1,
		),
		'bounce' => array (
			'lastrun' => -1,
		),
		'send' => array (
			'lastrun' => -1,
		),
		'triggeremails_s' => array (
			'lastrun' => -1
		),
		'triggeremails_p' => array (
			'lastrun' => -1
		),
		'maintenance' => array (
			'lastrun' => -1
		)
	);

	/**
	* The database version number.
	*
	* @see LoadWhiteLabelSettings
	*/
	var $WhiteLabelCache = null;

	static $_creditWarningMessages = null;

	/**
	* Constructor
	*
	* Sets the path to the config file. Loads up the database so it can check whether cron is set up properly or not.
	*
	* @param Boolean $load_settings Whether to load up the settings or not. Defaults to loading the settings.
	*
	* @see GetDb
	*
	* @return Void Doesn't return anything, just sets up the variables.
	*/
	function Settings_API($load_settings=true)
	{
		$this->ConfigFile = SENDSTUDIO_INCLUDES_DIRECTORY . '/config.php';
		$this->WhiteLabelCache = IEM_InterspireStash::getInstance();

		if ($load_settings) {
			$db = $this->GetDb();
			$this->LoadSettings();
		}
	}

	/**
	* Load
	* Loads up the settings from the config file.
	*
	* @see CheckCron
	* @see Areas
	*
	* @return Boolean Will return false if the config file isn't present, otherwise it set the class vars and return true.
	*/
	function Load()
	{
		if (!is_readable($this->ConfigFile)) {
			return false;
		}

		$this->LoadSettings();

		return true;
	}

	/**
	* LoadSettings
	* Loads up the settings from the database and defines all the variables that it needs to.
	* Also loads up cron to make sure that's working ok.
	*
	* It also has a hook into the settings for any addons to load their own global options.
	* <b>Example</b>
	* Split test sending has a settings event so it can have a cron job run.
	*
	* @return Void Doesn't return anything.
	*
	* @uses EventData_IEM_SETTINGSAPI_LOADSETTINGS
	*/
	function LoadSettings()
	{
		$stash = IEM_InterspireStash::getInstance();

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SETTINGSAPI_LOADSETTINGS();
			$tempEventData->data = $this;
			$tempEventData->trigger();

			unset($tempEventData);
		/**
		 * -----
		 */

		$areas = $this->Areas;
		unset($areas['config']);
		unset($areas['whitelabel']);

		// ----- Obtain the settings value either from the database OR from the stash (ie. cache)
			do {
				// Check if the settings is available in our cache
				if ($stash->exists('IEM_SYSTEM_SETTINGS')) {
					$settings = $stash->read('IEM_SYSTEM_SETTINGS');

					if (!empty($settings) && is_array($settings)) {
						foreach ($settings as $area => $aravalue) {
							/**
							 * TODO refactor
							 * As it stands we are defining constants dynamically. This is a bad programming practice.
							 * Once you have time, you might want to consider refactoring this code.
							 *
							 * As you may have notice, this is a duplicated code
							 * (see the codes that fetches this value from database below).
							 */
							if (!defined('SENDSTUDIO_' . $area)) {
								define('SENDSTUDIO_' . $area, $aravalue);
							}
						}

						break;
					}
				}


				// ------------------------------------------------------------------------
				// The settings cannot be found in stash cache,
				// so we will need to load it from database and put them in our cache
				// ------------------------------------------------------------------------
					$result = $this->Db->Query("SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "config_settings");

					$settings = array();
					while ($row = $this->Db->Fetch($result)) {
						$area = $row['area'];
						// eh? How did a config setting get in the db without it being in the settings api??
						if (!in_array($area, $areas)) {
							continue;
						}

						// this is for the 'upgrade' process - which moves them from the config file to being in the database.
						if (!defined('SENDSTUDIO_' . $area)) {
							/**
							 * @todo Remove hacks like these and refactor code that causes us to use these hacks!!!
							 */
							if ($area == 'CRON_TRIGGEREMAILS_P' && $row['areavalue'] == '') {
								$row['areavalue'] = 1440;
							}

							/**
							 * TODO refactor
							 * As it stands we are defining constants dynamically. This is a bad programming practice.
							 * Once you have time, you might want to consider refactoring this code.
							 */
							define('SENDSTUDIO_' . $area, $row['areavalue']);
						}

						$k = array_search($area, $areas);
						unset($areas[$k]);

						$settings[$area] = $row['areavalue'];
					}

					$this->Db->FreeResult($result);



					// Cache the settings
					$stash->write('IEM_SYSTEM_SETTINGS', $settings, true);
				// ------------------------------------------------------------------------
			} while(false);
		// -----


		// ----- Default settings
			// "Multiple unsubscribe" feature
			if (!defined('SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE')) {
				define('SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE', 0);
			}

			// As a default you do not want contacts to be able to modify their own email
			if (!defined('SENDSTUDIO_CONTACTCANMODIFYEMAIL')) {
				define('SENDSTUDIO_CONTACTCANMODIFYEMAIL', '0');
			}

			// Number of seconds to sleep when login failed
			if (!defined('SENDSTUDIO_SECURITY_WRONG_LOGIN_WAIT')) {
				define('SENDSTUDIO_SECURITY_WRONG_LOGIN_WAIT', 5);
			}

			// Number of attempts threshold
			if (!defined('SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_COUNT')) {
				define('SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_COUNT', 5);
			}

			// Number of seconds that wrong login threshold is checking for
			// (ie. 5 failed log in attempts in 300 seconds)
			if (!defined('SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_DURATION')) {
				define('SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_DURATION', 300);
			}

			// Ban duration
			if (!defined('SENDSTUDIO_SECURITY_BAN_DURATION')) {
				define('SENDSTUDIO_SECURITY_BAN_DURATION', 300);
			}

			// Autoresponders takes credit
			if (!defined('SENDSTUDIO_CREDIT_INCLUDE_AUTORESPONDERS')) {
				define('SENDSTUDIO_CREDIT_INCLUDE_AUTORESPONDERS', 1);
			}

			// Trigger takes credit
			if (!defined('SENDSTUDIO_CREDIT_INCLUDE_TRIGGERS')) {
				define('SENDSTUDIO_CREDIT_INCLUDE_TRIGGERS', 1);
			}

			// Whether or not to enable credit warnings
			if (!defined('SENDSTUDIO_CREDIT_WARNINGS')) {
				define('SENDSTUDIO_CREDIT_WARNINGS', 1);
			}

			// Triggeremails_P is defaulted to run every 24 hours
			if (!defined('SENDSTUDIO_CRON_TRIGGEREMAILS_P')) {
				define('SENDSTUDIO_CRON_TRIGGEREMAILS_P', 1440);
			}

			// Maintenance will default to run once a day
			if (!defined('SENDSTUDIO_CRON_MAINTENANCE')) {
				define('SENDSTUDIO_CRON_MAINTENANCE', 1440);
			}
		// -----


		// ------------------------------------------------------------------------------------------------------------------
		// There is an issue with MySQL database connection whereby most server had it's connection set to latin1
		// The problem lies when we defaulted our database to UTF8 and the application to use UTF8, non standard
		// English characters will be transformed to latin1, but stored as UTF8 in the database
		// See Issue 4807 in RedMine.
		//
		// A fix proved to be a bit difficult, assuming that there will be alot of people that were affected by this issue.
		// Once we set the characterset connection to UTF8, non-English characterset will be BROKEN.
		//
		// To make sure that only NEW installation uses this fix, the settings DATABASE_UTF8PATCH is introduced.
		// It will be set to 1 for newer install, but set to 0 for existing install.
		//
		// Once we work out the details for converting existing data out, we can safely remove this.
		// ------------------------------------------------------------------------------------------------------------------
			if (!defined('SENDSTUDIO_DATABASE_UTF8PATCH')) {
				define('SENDSTUDIO_DATABASE_UTF8PATCH', '0');
			}
		// ------------------------------------------------------------------------------------------------------------------


		/**
		 * Addons might define their own things.
		 * To make everything work we need to go through the left over $areas items to define them.
		 * If we don't do this, then we'd get errors when we try to view the settings page
		 * as the option/variable would not be defined yet.
		 *
		 * Set them to null by default.
		 */
		foreach ($areas as $area) {
			$name = 'SENDSTUDIO_' . $area;
			if (!defined($name)) {
				define($name, null);
			}
		}

		/**
		 * Load the whitelabel settings
		 */
		$this->LoadWhiteLabelSettings();
		/**
		 * -----
		 */

		$this->CheckCron();
	}

	/**
	* CheckCron
	* Checks whether cron has run ok and updated the settings in the database.
	* It goes through the current schedule items to set the applicable options.
	* Addons can modify the Schedule array to include their own things if they need to.
	*
	* @see cronok
	* @see Schedule
	* @see LoadSettings
	*
	* @return Boolean Returns true if the database has been updated, otherwise false.
	*/
	function CheckCron()
	{
		$cronok = false;
		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "settings";
		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if ($row['cronok'] == 1) {
			$cronok = true;
		}

		$this->cronok = $cronok;
		$this->cronrun1 = (int)$row['cronrun1'];
		$this->cronrun2 = (int)$row['cronrun2'];

		if (isset($row['database_version'])) {
			$this->database_version = $row['database_version'];
		} else {
			$query = "ALTER TABLE " . SENDSTUDIO_TABLEPREFIX . "settings ADD COLUMN database_version INT";
			$result = $this->Db->Query($query);
			if ($result) {
				$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "settings SET database_version='0'";
				$result = $this->Db->Query($query);
			}
		}

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "settings_cron_schedule";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {

			/**
			 * check if the item is in the schedule array.
			 * If it's not, then it may be an addon has defined a cron schedule but not cleaned itself up when it has been uninstalled/disabled.
			 */
			$schedule_name = $row['jobtype'];
			if (!isset($this->Schedule[$schedule_name])) {
				continue;
			}
			$this->Schedule[$schedule_name] = array('lastrun' => $row['lastrun']);
		}

		return $cronok;
	}

	/**
	 * Check whether or not cron is still running
	 *
	 * This function will check whether or not cron is still running.
	 * It does that by comparing the last known time cron has successfully run
	 * against current time...
	 *
	 * @param Int $leeway Leeway interval where cron can be skipped
	 *
	 * @return Boolean Returns TRUE if cron has been triggered as expected, FALSE otherwise
	 */
	function CheckCronStillRunning($leeway=3)
	{
		if (!$this->CheckCron()) {
			return true;
		}
		$expectedIntervalPool = array(SENDSTUDIO_CRON_SEND, SENDSTUDIO_CRON_AUTORESPONDER, SENDSTUDIO_CRON_BOUNCE, SENDSTUDIO_CRON_TRIGGEREMAILS_S, SENDSTUDIO_CRON_TRIGGEREMAILS_P);
		$expectedInterval = -1;
		$actualInterval = floor((time() - $this->cronrun2) / 60);

		foreach ($expectedIntervalPool as $item) {
			$item = intval($item);
			if ($item == 0) {
				continue;
			}

			if ($expectedInterval == -1 || $expectedInterval > $item) {
				$expectedInterval = $item;
			}
		}

		return ($actualInterval < ($expectedInterval * $leeway));
	}

	/**
	* SetRunTime
	* Sets the runtime for a particular type of job.
	* It updates the 'lastrun' variable for that particular type.
	*
	* @param String $jobtype The type of job to set the lastrun time for.
	*
	* @return Void Doesn't return anything.
	*/
	function SetRunTime($jobtype='send')
	{
		$allowed_jobtypes = array_keys($this->Schedule);

		if (!in_array($jobtype, $allowed_jobtypes)) {
			return false;
		}

		$jobtime = time();
		$jobtype = $this->Db->Quote($jobtype);

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "settings_cron_schedule SET lastrun={$jobtime} WHERE jobtype='{$jobtype}'";
		$result = $this->Db->Query($query);

		if (!$result) {
			trigger_error(mysql_error());
			trigger_error('Cannot set CRON schedule', E_USER_NOTICE);
			return;
		}

		$number_affected = $this->Db->NumAffected($result);
		if ($number_affected == 0) {
			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "settings_cron_schedule (lastrun, jobtype) VALUES ({$jobtime}, '{$jobtype}')";
			$result = $this->Db->Query($query);
		}
	}

	/**
	* CronEnabled
	* Checks whether cron has been enabled or not.
	*
	* @param Boolean $autoresponder_check Whether this is checking autoresponders.
	*
	* @see cronok
	*
	* @return Boolean Returns true if cron is enabled, otherwise false.
	*/
	function CronEnabled($autoresponder_check=false)
	{
		/**
		 * If cron isn't enabled at all, return straight away.
		 */
		if (!SENDSTUDIO_CRON_ENABLED) {
			return false;
		}

		/**
		 * If we're just checking autoresponders, then check that particular variable.
		 */
		if ($autoresponder_check) {
			if (SENDSTUDIO_CRON_AUTORESPONDER > 0) {
				return true;
			}
			return false;
		}

		/**
		 * If we're not just checking autoresponders, return true
		 * as we're just checking whether cron is working or not.
		 */
		return true;
	}

	/**
	* DisableCron
	* This turns cron off on the settings table, clears out the last run times and also clears out settings for the cron schedule items.
	*
	* @see cronok
	*
	* @return Boolean Returns whether cron was disabled or not. It should only return false if the database somehow goes missing in the middle of the process.
	*/
	function DisableCron()
	{
		$this->cronok = false;

		$this->Db->StartTransaction();

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "settings SET cronok='0', cronrun1=0, cronrun2=0";
		$res = $this->Db->Query($query);
		if (!$res) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "settings_cron_schedule";
		$this->Db->Query($query);
		foreach (array_keys($this->Schedule) as $jobtype) {
			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "settings_cron_schedule(jobtype, lastrun) VALUES ('" . $this->Db->Quote($jobtype) . "', 0)";
			$res = $this->Db->Query($query);
			if (!$res) {
				$this->Db->RollbackTransaction();
				return false;
			}
		}

		$this->Db->CommitTransaction();

		return true;
	}

	/**
	* Save
	* This function saves the current class vars to the settings file.
	* It checks to make sure the file is writable, then places the appropriate values in there and saves it. It uses a temporary name then copies that over the top of the old one, then removes the temporary file.
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Save()
	{

		require_once(SENDSTUDIO_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR . 'interspire_addons.php');

		if (!is_writable($this->ConfigFile)) {
			return false;
		}

		$tmpfname = tempnam(TEMP_DIRECTORY, 'SS_');
		if (!$handle = fopen($tmpfname, 'w')) {
			return false;
		}

		$copy = true;
		if (is_file(TEMP_DIRECTORY . '/config.prev.php')) {
			if (!@unlink(TEMP_DIRECTORY . '/config.prev.php')) {
				$copy = false;
			}
		}

		if ($copy) {
			@copy($this->ConfigFile, TEMP_DIRECTORY . '/config.prev.php');
		}

		// the old config backups were in the includes folder so try to clean them up as part of this process.
		$config_prev = SENDSTUDIO_INCLUDES_DIRECTORY . '/config.prev.php';
		if (is_file($config_prev)) {
			@unlink($config_prev);
		}

		$contents = "<?php\n\n";

		gmt($this);

		$areas = $this->Areas;


		foreach ($areas['config'] as $area) {
			// See self::LoadSettings() on UTF8PATCH settings
			if ($area == 'DATABASE_UTF8PATCH') {
				if (!defined('SENDSTUDIO_DATABASE_UTF8PATCH')) {
					define('SENDSTUDIO_DATABASE_UTF8PATCH', 1);
				}
				$contents .= "define('SENDSTUDIO_DATABASE_UTF8PATCH', '" . SENDSTUDIO_DATABASE_UTF8PATCH . "');\n";
				continue;
			}
			$string = 'define(\'SENDSTUDIO_' . $area . '\', \'' . addslashes($this->Settings[$area]) . '\');' . "\n";
			$contents .= $string;
		}

		$contents .= "define('SENDSTUDIO_IS_SETUP', 1);\n";
		
		if (!defined('SENDSTUDIO_DEFAULTCHARSET')) {
			define('SENDSTUDIO_DEFAULTCHARSET', 'UTF-8');
		}
		$contents .= "define('SENDSTUDIO_DEFAULTCHARSET', '" . SENDSTUDIO_DEFAULTCHARSET . "');\n";

		$contents .= "\n\n";

		fputs($handle, $contents, strlen($contents));
		fclose($handle);
		chmod($tmpfname, 0644);

		if (!copy($tmpfname, $this->ConfigFile)) {
			return false;
		}
		unlink($tmpfname);

		$copy = true;
		if (is_file(TEMP_DIRECTORY . '/config.bkp.php')) {
			if (!@unlink(TEMP_DIRECTORY . '/config.bkp.php')) {
				$copy = false;
			}
		}

		if ($copy) {
			@copy($this->ConfigFile, TEMP_DIRECTORY . '/config.bkp.php');
		}

		// the old config backups were in the includes folder so try to clean them up as part of this process.
		$config_bkp = SENDSTUDIO_INCLUDES_DIRECTORY . '/config.bkp.php';
		if (is_file($config_bkp)) {
			@unlink($config_bkp);
		}

		unset($areas['config']);

		if (defined('APPLICATION_SHOW_WHITELABEL_MENU') && constant('APPLICATION_SHOW_WHITELABEL_MENU')) {
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "whitelabel_settings";
			$result = $this->Db->Query($query);
			foreach ($areas['whitelabel'] as $area) {
				// If settings are not set, do not continue
				if (!isset($this->Settings[$area])) {
					continue;
				}

				$value = $this->Settings[$area];

				if (strtolower($area) == 'update_check_enabled') {
					$subAction = 'uninstall';
					if ($value == '1') {
						$subAction = 'install';
					}
					$result = Interspire_Addons::Process('updatecheck', $subAction, array());
					continue;
				} elseif (strtolower($area) == 'lng_accountupgrademessage') {
					$agencyId = get_agency_license_variables();
					if(empty($agencyId['agencyid'])) {
						continue;
					}
				}

				if (is_bool($value)) {
					$value = (int)$value;
				}

				$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "whitelabel_settings(name, value) VALUES ('" . $this->Db->Quote($area) . "', '" . $this->Db->Quote($value) . "')";
				$result = $this->Db->Query($query);
			}
			if ($this->WhiteLabelCache->exists('IEM_SETTINGS_WHITELABEL')) {
				$this->WhiteLabelCache->remove('IEM_SETTINGS_WHITELABEL');
			}
		}

		if (isset($areas['whitelabel'])) {
			unset($areas['whitelabel']);
		}

		$stash = IEM_InterspireStash::getInstance();
		if ($stash->exists('IEM_SYSTEM_SETTINGS')) {
			$stash->remove('IEM_SYSTEM_SETTINGS');
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "config_settings";
		$result = $this->Db->Query($query);


		foreach ($areas as $area) {
			$value = isset($this->Settings[$area]) ? $this->Settings[$area] : '';



			if ($area == 'SYSTEM_DATABASE_VERSION') {
				$value = $this->Db->FetchOne("SELECT version() AS version");
			}
			if (is_bool($value)) {
				$value = (int)$value;
			}

			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "config_settings(area, areavalue) VALUES ('" . $this->Db->Quote($area) . "', '" . $this->Db->Quote($value) . "')";
			$result = $this->Db->Query($query);
		}


		return true;
	}

	/**
	* GetDatabaseVersion
	* Gets the database version from the settings if needed.
	*
	* @return Int Returns the database version (from the config/settings table if needed).
	*/
	function GetDatabaseVersion()
	{
		if ($this->database_version == -1) {
			$this->CheckCron();
		}
		return $this->database_version;
	}

	/**
	* NeedDatabaseUpgrade
	* This checks whether a database upgrade is needed.
	*
	* It compares the database version from the config/settings to the SENDSTUDIO_DATABASE_VERSION
	* If a database upgrade is needed, then it returns true.
	* If they are the same number, then it returns false.
	*
	* @return Boolean Returns true if an upgrade is needed otherwise false.
	*/
	function NeedDatabaseUpgrade()
	{
		if ($this->database_version == -1) {
			$this->CheckCron();
		}

		if ($this->database_version < SENDSTUDIO_DATABASE_VERSION) {
			return true;
		}

		return false;
	}

	/**
	 * GDEnabled
	 * Function to detect if the GD extension for PHP is enabled.
	 *
	 * @return Boolean Returns true if GD is enabled, false if it's not
	 */
	function GDEnabled()
	{
		static $gd_enabled = null;

		if (is_null($gd_enabled)) {
			$gd_enabled =	function_exists('imagecreate')
							&& (	function_exists('imagegif')
									|| function_exists('imagepng')
									|| function_exists('imagejpeg'));
		}

		return $gd_enabled;
	}

	/**
	 * Get credit warning settings
	 *
	 * NOTE:
	 * - The warnings will be sorted based on creditlevel (ascending) and type
	 *
	 * @return array|FALSE Returns credit warnings record, FALSE if an error occured
	 */
	function GetCreditWarningsSettings()
	{
		if (is_null(self::$_creditWarningMessages)) {
			$db = IEM::getDatabase();

			$result = $db->Query("SELECT * FROM [|PREFIX|]settings_credit_warnings ORDER BY creditlevel ASC, aspercentage DESC");
			if (!$result) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Was not able to query database: ' . $db->Error(), E_USER_NOTICE);
				return false;
			}

			$rows = array();
			while ($row = $db->Fetch($result)) {
				$rows[] = $row;
			}

			$db->FreeResult($result);

			self::$_creditWarningMessages = $rows;
		}

		return self::$_creditWarningMessages;
	}

	/**
	 * Save credit warning into database
	 *
	 * NOTE: warning record structure is as follow
	 * - enabled => character 1 or 0
	 * - creditlevel => integer
	 * - aspercentage => character 1 or 0
	 * - emailcontents => string
	 *
	 * @param array $warnings An array of warnings record that needed to be saved to the database (See note for record structure)
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * FIXME better way of saving warnings. This might probably invlove refactoring Settings_API class
	 */
	function SaveCreditWarnings($warnings)
	{
		$db = IEM::getDatabase();

		$db->StartTransaction();

		$status = $db->Query("DELETE FROM [|PREFIX|]settings_credit_warnings");
		if (!$status) {
			$db->RollbackTransaction();
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to clear old warning messages: ' . $db->Error(), E_USER_NOTICE);
			return false;
		}

		$levelSpecified = array();
		$sqlValues = array();
		foreach ($warnings as $warning) {
			$tempEnabled = ((array_key_exists('enabled', $warning) && $warning['enabled'] == 1) ? '1' : '0'); // Default to 0
			$tempCreditLevel = intval(array_key_exists('creditlevel', $warning) ? $warning['creditlevel'] : '0'); // Default to 0
			$tempAsPercentage = ((!array_key_exists('aspercentage', $warning) || $warning['aspercentage'] != 1) ? '0' : '1'); // Default to 1
			$tempEmailSubject = (array_key_exists('emailsubject', $warning) ? $db->Quote(trim($warning['emailsubject'])) : ''); // Default to empty
			$tempEmailContents = (array_key_exists('emailcontents', $warning) ? $db->Quote(trim($warning['emailcontents'])) : ''); // Default to empty

			if (empty($tempEmailSubject) || empty($tempEmailContents)) {
				$db->RollbackTransaction();
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- emailcontents and emailsubject cannot be empty', E_USER_NOTICE);
				return false;
			}

			if (in_array($tempCreditLevel, $levelSpecified)) {
				$db->RollbackTransaction();
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Credit level cannot be choosen more than once', E_USER_NOTICE);
				return false;
			}

			$sqlValues[] = "'{$tempEnabled}', {$tempCreditLevel}, '{$tempAsPercentage}', '{$tempEmailSubject}', '{$tempEmailContents}'";
		}

		if (!empty($sqlValues)) {
			$status = $db->Query("
				INSERT INTO [|PREFIX|]settings_credit_warnings (enabled, creditlevel, aspercentage, emailsubject, emailcontents)
				VALUES (" . implode('),(', $sqlValues) . ")
			");
			if (!$status) {
				$db->RollbackTransaction();
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Cannot save record to database: ' . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		$db->CommitTransaction();

		// Need to refresh cache...
		self::$_creditWarningMessages = $warnings;

		return true;
	}

	/**
	* LoadWhiteLabelSettings
	* Loads up the settings from the whitelabel_settings table
	* and defines all the variables that it needs to, only if
	* the user has already set the custom whitelabel settings.
	* If the settings are not defined here, will be defined in
	* whitelabel.php
	*
	* @return Void Doesn't return anything.
	*
	* @uses IEM_InterspireStash
	*/
	function LoadWhiteLabelSettings() {
		$tempWhiteLabelCache = array();

		// Load the customizable white label settings.
		if (!$this->WhiteLabelCache->exists('IEM_SETTINGS_WHITELABEL')) {
			// Read from Database
			$query = "SELECT * FROM [|PREFIX|]whitelabel_settings";
			$result = @$this->Db->Query($query);
			if (!$result) {
				return;
			}

			// Restore from Database
			while ($row = $this->Db->Fetch($result)) {
				if ($row['name']) {
					$tempWhiteLabelCache[] = $row;
				}
			}

			$this->Db->FreeResult($result);

			// Cache it
			if (sizeof($tempWhiteLabelCache)) {
				$this->WhiteLabelCache->write('IEM_SETTINGS_WHITELABEL', $tempWhiteLabelCache);
			}


		// Restore from cached
		} else {
			$tempWhiteLabelCache = $this->WhiteLabelCache->read('IEM_SETTINGS_WHITELABEL');
		}


		// Defining the white label settings
		foreach ($tempWhiteLabelCache as $eachWhiteLabelCache) {
			defined($eachWhiteLabelCache['name']) or define($eachWhiteLabelCache['name'], $eachWhiteLabelCache['value']);
		}
	}
}
