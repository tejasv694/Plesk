<?php
/**
 * IEM Installer
 *
 * A class that handles the logic of installing the application.
 *
 * @package interspire.iem.lib.iem
 */
class IEM_Installer{

	/**
	 * The database resource, once it has been established.
	 * @var Object
	 */
	private $_db;

	/**
	 * The application settings that are required to install the software.
	 * @var Array
	 */
	private $_settings;

	/**
	 * Country list cache
	 * @var Mixed Country list cache
	 */
	static private $_country_list_cache = null;

	/**
	 * Error codes
	 */
	const SUCCESS				= 0;
	const FIELD_NOT_PRESENT		= 1;
	const FIELD_INVALID			= 2;
	const DB_UNSUPPORTED		= 3;
	const DB_BAD_VERSION		= 4;
	const DB_CONN_FAILED		= 5;
	const DB_QUERY_ERROR		= 6;
	const DB_INSUFFICIENT_PRIV	= 7;
	const DB_ALREADY_INSTALLED	= 8;
	const DB_OLD_INSTALL		= 9;
	const DB_MISSING			= 10;
	const SETTINGS_MISSING		= 11;
	const FILES_UNWRITABLE		= 12;
	const SERVER_BAD_CONFIG		= 13;

	/**
	 * CONSTRUCTOR
	 * Initialises the required settings.
	 */
	public function __construct(){
		$this->_settings = array(
				'DATABASE_TYPE'		=> null,
				'LICENSEKEY'		=> null,
				'APPLICATION_URL'	=> null,
				'EMAIL_ADDRESS'		=> null,
				'DATABASE_USER'		=> null,
				'DATABASE_PASS'		=> null,
				'DATABASE_HOST'		=> null,
				'DATABASE_NAME'		=> null,
				'TABLEPREFIX'		=> null,
			);
		if (is_callable(array('IEM', 'getDatabase'))) {
			$this->_api = IEM::getDatabase();
		}
	}

	/**
	 * LoadFields
	 * Loads settings into the object.
	 *
	 * @param Array $settings An associative array of the settings required to install the application.
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string.
	 */
	public function LoadRequiredSettings($settings){
		foreach ($this->_settings as $key=>$value) {
			if (isset($settings[$key])) {
				$this->_settings[$key] = $settings[$key];
			}
		}
		return array(self::SUCCESS, null);
	}

	/**
	 * SetupDatabase
	 * Creates a database connection and loads the schema, if it's safe to do so.
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string or an array of error strings.
	 */
	public function SetupDatabase(){
		// Check the DB type.
		$type = $this->_settings['DATABASE_TYPE'];
		if (!$this->validDbType($type)) {
			return array(self::DB_UNSUPPORTED, null);
		}

		// Check we can connect to it.
		require_once(IEM_PATH . '/ext/database/' . $type . '.php');
		$type_api = $type . 'Db';
		$db = new $type_api();

/* #*#*# DISABLED! FLIPMODE! #*#*# ?????????? ORIGINAL ???????????????
			if (!defined(SENDSTUDIO_DEFAULTCHARSET) or SENDSTUDIO_DEFAULTCHARSET == 'UTF-8'){
				$db->charset = 'utf8';
			}
			if (!$db->Connect($this->_settings['DATABASE_HOST'], $this->_settings['DATABASE_USER'], $this->_settings['DATABASE_PASS'], $this->_settings['DATABASE_NAME'])) {
		#*#*# / #*#*# */

/* ????????????????? SWAP ORIGINAL (STILL ACTIVE) WITH THIS ??????????????
		$conn_ok = $db->Connect($this->_settings['DATABASE_HOST'], $this->_settings['DATABASE_USER'], $this->_settings['DATABASE_PASS'], $this->_settings['DATABASE_NAME']);
		if (!$conn_ok) {
????????????????????????????????????????? */

		if (!defined(SENDSTUDIO_DEFAULTCHARSET) or SENDSTUDIO_DEFAULTCHARSET == 'UTF-8'){
 			$db->charset = 'utf8';
 		}
						
		if (!$db->Connect($this->_settings['DATABASE_HOST'], $this->_settings['DATABASE_USER'], $this->_settings['DATABASE_PASS'], $this->_settings['DATABASE_NAME'])) {



			return array(self::DB_CONN_FAILED, $db->GetErrorMsg());
		}
		
		$this->_db =& $db;

		// Set DB configuration settings needed by other parts of the installer.
		$this->_db->TablePrefix = $this->_settings['TABLEPREFIX'];
		define('SENDSTUDIO_DATABASE_TYPE', $this->_settings['DATABASE_TYPE']);

		// Check for sufficient version.
		$version = $this->_db->Version();
		list($error, $msgs) = self::DbVersionCheck($type, $version);
		if ($error) {
			return array($error, $msgs);
		}

		// Check whether the DB user has sufficient privileges.
		if (!self::DbSufficientPrivileges($this->_db)) {
			return array(self::DB_INSUFFICIENT_PRIV, null);
		}

		// Check whether the DB has already been set up.
		if ($this->DbAlreadyInstalled()) {
			return array(self::DB_ALREADY_INSTALLED, null);
		}

		// Check whether there is an old (SS 2004) install already here.
		if ($this->DbOldVersionInstalled()) {
			return array(self::DB_OLD_INSTALL, null);
		}

		$errors = $this->LoadSchema();
		if (empty($errors)) {
			return array(self::SUCCESS, null);
		}
		return array(self::DB_QUERY_ERROR, $errors);
	}

	/**
	 * SaveDefaultSettings
	 * Saves the default settings into the database.
	 * Note that the database and required system settings must be set up before this is called.
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string.
	 */
	public function SaveDefaultSettings(){
		if (!$this->CheckRequiredFields()) {
			return array(self::SETTINGS_MISSING, 'All required settings must be loaded first.');
		}
		if (!$this->_db) {
			return array(self::DB_MISSING, 'Database connection must be established first.');
		}

		require_once(SENDSTUDIO_API_DIRECTORY . '/settings.php');

		$settings_api = new Settings_API(false);

		$settings = $this->_settings;

		$settings['DATABASE_UTF8PATCH']            = '1';
		$settings['SERVERTIMEZONE']                = self::GetTimezone();
		$settings['DEFAULTCHARSET']                = 'UTF-8';
		$settings['SMTP_PORT']                     = '25';
		$settings['IPTRACKING']                    = '1';
		$settings['MAXHOURLYRATE']				   = '0';
		$settings['ALLOW_ATTACHMENTS']			   = '1';
		$settings['USEMULTIPLEUNSUBSCRIBE']		   = '0';
		$settings['CONTACTCANMODIFYEMAIL']		   = '0';
		$settings['FORCE_UNSUBLINK']			   = '0';
		$settings['MAXOVERSIZE']				   = '0';		
		$settings['MAX_IMAGEWIDTH']                = '700';
		$settings['MAX_IMAGEHEIGHT']               = '400';
		$settings['BOUNCE_IMAP']                   = '0';
		$settings['ALLOW_EMBEDIMAGES']             = '1';
		$settings['ATTACHMENT_SIZE']               = '2048';
		$settings['CRON_ENABLED']				   = '0';
		$settings['CRON_SEND']                     = '5';
		$settings['CRON_AUTORESPONDER']            = '10';
		$settings['CRON_BOUNCE']                   = '60';
		$settings['EMAILSIZE_WARNING']             = '500';
		$settings['EMAILSIZE_MAXIMUM']             = '2048';
		$settings['RESEND_MAXIMUM']                = '3';
		$settings['CREDIT_INCLUDE_AUTORESPONDERS'] = '1';
		$settings['CREDIT_INCLUDE_TRIGGERS']       = '1';
		$settings['CREDIT_WARNINGS']               = '0';

		$settings_api->Set('Settings', $settings);

		// set the table prefix constant for the API to work
		define('SENDSTUDIO_TABLEPREFIX', $this->_db->TablePrefix);

		$settings_api->Db = &$this->_db;

		$settings_api->Save();

		$username      = $_POST['admin_username'];
		$usernameToken = API_USERS::generateUniqueToken($username);
		$password      = API_USERS::generatePasswordHash($_POST['admin_password'], $usernameToken);

		// Set the admin user's settings
		$query  = 'UPDATE [|PREFIX|]users SET ';
		$query .= " usertimezone='" . $this->_db->Quote($settings['SERVERTIMEZONE'])           . "', ";
		$query .= " emailaddress='" . $this->_db->Quote($settings['EMAIL_ADDRESS'])            . "', ";
		$query .= " textfooter='"   . $this->_db->Quote(GetLang('Default_Global_Text_Footer')) . "', ";
		$query .= " htmlfooter='"   . $this->_db->Quote(GetLang('Default_Global_HTML_Footer')) . "', ";
		$query .= " unique_token='" . $this->_db->Quote($usernameToken)                        . "', ";
		$query .= " username='"     . $this->_db->Quote($username)                             . "', ";
		$query .= " password='"     . $this->_db->Quote($password)                             . "'  ";
		$query .= ' WHERE userid=1';

		$result = $this->_db->Query($query);

		if (!$result) {
			return array(self::DB_QUERY_ERROR, $this->_db->GetErrorMsg());
		}

		return array(self::SUCCESS, null);
	}

	/**
	 * CheckPermissions
	 * Checks whether permissions are set correctly for the installation to continue.
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string or an array of error strings.
	 */
	public function CheckPermissions(){
		$errors = array();

		$folders_to_check = array('admin/temp', 'admin/com/storage');
		$files_to_check = array('admin/includes/config.php');

		$directory_linux_message = 'Please CHMOD it to 775, 757 or 777.';
		$file_linux_message = 'Please CHMOD it to 664, 646 or 666.';

		$directory_windows_message = $file_windows_message = 'Please set anonymous write permissions in IIS. If you don\'t have access to do this, you will need to contact your hosting provider.';

		$file_error_message = $file_linux_message;
		$directory_error_message = $directory_linux_message;
		if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
			$directory_error_message = $directory_windows_message;
			$file_error_message = $file_windows_message;
		}

		$basedir = dirname(SENDSTUDIO_BASE_DIRECTORY) . DIRECTORY_SEPARATOR;
		foreach ($folders_to_check as $folder_name) {
			$fullpath = $basedir . $folder_name;
			if (!self::CheckWritable($fullpath)) {
				$errors[] = 'The folder <strong>' . $folder_name . '</strong> is not writable. ' . $directory_error_message;
			}
		}

		if (SENDSTUDIO_SAFE_MODE && self::CheckWritable(TEMP_DIRECTORY)) {
			$fullpath = str_replace(SENDSTUDIO_BASE_DIRECTORY, 'admin', TEMP_DIRECTORY);
			if (!self::CheckWritable(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'send')) {
				$errors[] = 'The folder <strong>' . $fullpath . DIRECTORY_SEPARATOR . 'send</strong> is not writable. ' . $directory_error_message;
			}
			if (!self::CheckWritable(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'autoresponder')) {
				$errors[] = 'The folder <strong>' . $fullpath . DIRECTORY_SEPARATOR . 'autoresponder</strong> is not writable. ' . $directory_error_message;
			}
		}
		
		/* #*#*# DISABLED! FLIPMODE! #*#*# ?????????????
			if(!file_exists('includes/config.php')){@fopen('includes/config.php','x');}
		#*#*# / #*#*# */
		if(!file_exists('includes/config.php')){@fopen('includes/config.php','x');}

		foreach ($files_to_check as $file_name) {
			$fullpath = $basedir . $file_name;
			if (!self::CheckWritable($fullpath)) {
				$errors[] = 'The file <strong>' . $file_name . '</strong> is not writable. ' . $file_error_message;
			}
		}
		if (!empty($errors)) {
			return array(self::FILES_UNWRITABLE, $errors);
		}
		return array(self::SUCCESS, null);
	}

	/**
	 * CheckServerSettings
	 * Checks whether some basic server settings are OK (e.g. Safe Mode).
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string or an array of error strings.
	 */
	public function CheckServerSettings(){
		$errors = array();

		if (!function_exists('session_id')) {
			$errors[] = "PHP sessions are not available on this server.";
			return array(self::SERVER_BAD_CONFIG, $errors);
		}

		if (self::iniBool('safe_mode')) {
			$errors[] = "PHP's 'Safe Mode' is currently on and needs to be deactivated.";
			return array(self::SERVER_BAD_CONFIG, $errors);
		}

		return array(self::SUCCESS, null);
	}

	/**
	 * CreateCustomFields
	 * Creates a set of 'default' or 'starter' custom fields.
	 * Note that this function should only be run after the database connection has been established.
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string.
	 */
	public function CreateCustomFields(){
		$country_data = self::GetCountryList();
		$country_options = array();
		foreach ($country_data as $row) {
			$country_options[$row['alpha3_code']] = $row['country_name'];
		}
		$fields = array(
			array('name' => 'Title',
				  'type' => 'dropdown',
				  'data' => array(
					'Ms' => 'Ms',
					'Mrs' => 'Mrs',
					'Mr' => 'Mr',
					'Dr' => 'Dr',
					'Prof' => 'Prof',
				),
		),
			array('name' => 'First Name',
				  'type' => 'text'),

			array('name' => 'Last Name',
				  'type' => 'text'),

			array('name' => 'Phone',
				  'type' => 'text'),

			array('name' => 'Mobile',
				  'type' => 'text'),

			array('name' => 'Fax',
				  'type' => 'text'),

			array('name' => 'Birth Date',
				  'type' => 'date',
				  'data' => array(3 => date('Y') - 100), // set starting year
				),
			array('name' => 'City',
				  'type' => 'text'),

			array('name' => 'State',
				  'type' => 'text'),

			array('name' => 'Postal/Zip Code',
				  'type' => 'text'),

			array('name' => 'Country',
				  'type' => 'dropdown',
				  'data' => $country_options),
			);
		foreach ($fields as $field) {
			$data = null;
			if (isset($field['data'])) {
				$data = $field['data'];
			}
			$this->GenerateCustomField($field['name'], $field['type'], $data);
		}
		return array(self::SUCCESS, null);
	}

	/**
	 * RegisterAddons
	 * Installs a set of add-ons to be enabled after application installation.
	 *
	 * @return Void Does not return anything.
	 */
	public function RegisterAddons(){
		require_once(IEM_PATH . '/../addons/interspire_addons.php');
		$all_addons = array_keys(Interspire_Addons::GetAllAddons());
		// the add-ons we want to be enabled after installation
		/* #*#*# DISABLED! FLIPMODE! #*#*#
			$addons_to_install = array('checkpermissions', 'dbcheck', 'emaileventlog', 'splittest', 'systemlog', 'updatecheck', 'dynamiccontenttags', 'surveys');
		#*#*# / #*#*# */
		/* #*#*# MODIFIED! FLIPMODE! #*#*# */
		$addons_to_install = array('checkpermissions', 'dbcheck', 'emaileventlog', 'splittest', 'systemlog', 'dynamiccontenttags', 'surveys');
		
		$addons_to_install = array_intersect($all_addons, $addons_to_install);
		foreach ($addons_to_install as $addon) {
			$this->InstallAddOn($addon);
		}
	}

	/**
	 * validDbType
	 *
	 * @param String $type The type of database being validated.
	 *
	 * @return Boolean True if $type is a supported DB type, otherwise false.
	 */
	private function validDbType($type){
		return ($type == 'mysql' || $type == 'pgsql');
	}

	/**
	 * DbAlreadyInstalled
	 * Checks to see if the schema has already been installed in this database.
	 *
	 * @return Boolean True if the schema has been installed into the current database, otherwise false.
	 */
	private function DbAlreadyInstalled(){
		$query = "SELECT COUNT(*) AS subcount FROM [|PREFIX|]users";
		$result = $this->_db->Query($query);
		$count = (int)$this->_db->FetchOne($result, 'subcount');
		return ($count > 0);
	}

	/**
	 * DbOldVersionInstalled
	 * Checks to see if the schema of an old SendStudio version has been installed.
	 *
	 * @return Boolean True if the old schema has been installed into the current database, otherwise false.
	 */
	private function DbOldVersionInstalled(){
		$query = "SELECT COUNT(*) AS subcount FROM [|PREFIX|]admins";
		$result = $this->_db->Query($query);
		$count = (int)$this->_db->FetchOne($result, 'subcount');
		return ($count > 0);
	}

	/**
	 * LoadSchema
	 * Loads the DB schema for the configured database type.
	 *
	 * @return Array A list of error messages from each query run.
	 */
	private function LoadSchema(){
		require_once(IEM_PATH . '/install/schema.' . $this->_settings['DATABASE_TYPE'] . '.php');
		$errors = array();
		$this->_db->StartTransaction();
		foreach ($queries as $p => $query) {
			$query = str_replace('%%TABLEPREFIX%%', $this->_db->TablePrefix, $query);
			$result = $this->_db->Query($query);
			if (!$result) {
				$errors[] = $query . ' (' . $this->_db->GetErrorMsg() . ')';
			}
		}
		if (empty($errors)) {
			$this->_db->CommitTransaction();
		} else {
			// this will only work in PostgreSQL, as MySQL does not support rolling back DDL commands in transactions
			$this->_db->RollbackTransaction();
		}
		return $errors;
	}

	/**
	 * CheckRequiredFields
	 * Verifies the required fields needed to install the application are present.
	 *
	 * @return Boolean True if all required fields are present, otherwise false.
	 */
	private function CheckRequiredFields(){
		foreach ($this->_settings as $required_setting) {
			if (is_null($required_setting)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * GenerateCustomField
	 * Creates a custom field owned by the initial administrator.
	 *
	 * @param String $name The name of the custom field, e.g. "Address".
	 * @param String $type The type of custom field it should be. Defaults to "text".
	 * @param Array $data Pre-defined data for pick-lists
	 *
	 * @return Boolean True if the field was generated successfully, otherwise false.
	 */
	private function GenerateCustomField($name, $type='text', $data=null){
		$api = 'CustomFields_' . $type;
		// this reproduces GetApi functionality... can we call it statically?
		$api_file = SENDSTUDIO_API_DIRECTORY . strtolower('/' . $api . '.php');
		require_once($api_file);
		$api .= '_API';
		$field_api = new $api(0, false);

		if (!$field_api) {
			return false;
		}

		// Assign the database, as we may not have it if we're being called by the installer.
		$field_api->Db =& $this->_db;

		$properties = $field_api->GetOptions();
		$properties['FieldName'] = $name;

		if (!is_null($data)) {
			if ($type == 'dropdown') {
				$keys = array_keys($data);
				$values = array_values($data);
				$properties['Key'] = $keys;
				$properties['Value'] = $values;
			} else if ($type == 'date') {
				foreach ($data as $k=>$v) {
					$properties['Key'][$k] = $v;
				}
			}
		}

		$field_api->Set($properties);
		$field_api->ownerid = 1; // The admin user will be ID 1 after installation.
		$field_api->isglobal = '1'; // The fields should be global so all users can use them.
		$create = $field_api->Create();
		return ($create !== false);
	}

	/**
	 * InstallAddon
	 * Installs and enables a given addon. If there is a problem installing it, fail silently.
	 *
	 * @param String $addon_id The ID of the addon, e.g. 'updatecheck'.
	 *
	 * @return Void Does not return anything.
	 */
	public function InstallAddon($addon_id){
		$addon_file = IEM_PATH . '/../addons/' . $addon_id . '/' . $addon_id . '.php';
		if (!is_readable($addon_file)) {
			return;
		}
		require_once($addon_file);
		$addon_class = 'Addons_' . $addon_id;
		try {
			$addon = new $addon_class($this->_db);
			$addon->Install();
		} catch (Exception $e) {
			// TODO: should we do more than fail silently?
		}
	}

	/**
	 * RegisterEventListeners
	 * Loads all the event listeners used in the system as listed in com/install/events.php.
	 *
	 * @throws InterspireEventException
	 *
	 * @return Void Does not return anything.
	 */
	public static function RegisterEventListeners(){
		require(IEM_PATH . '/install/listeners.php');
		foreach ($listeners as $listener) {
			if (!isset($listener[2])) {
				$listener[2] = null;
			}
			list($event, $function, $file) = $listener;
			if (strpos($function, '::') !== false) {
				$function = explode('::', $function);
			}
			if (!InterspireEvent::listenerExists($event, $function, $file)) {
				InterspireEvent::listenerRegister($event, $function, $file);
			}
		}

		// Add IEM_MARKER which will mark the integrity of the listener
		InterspireEvent::eventCreate('IEM_MARKER');
	}

	/**
	 * GetCountryList
	 * Obtains a list of countries and their associated codes, caching it.
	 *
	 * @return Array A list of country records with the keys country_name, numeric_code, alpha2_code and alpha3_code.
	 */
	public static function GetCountryList(){
		if (is_null(self::$_country_list_cache)) {
			$file = IEM_PATH . '/resources/country_list.res';

			if (!is_readable($file)) {
				return array();
			}

			$data = file_get_contents($file);
			$lines = explode("\n", trim($data));
			unset ($data);

			reset($lines);
			$list = array();
			while (list(, $line) = each($lines)) {
				$line = trim($line);

				if (empty($line)) {
					continue;
				}

				if (preg_match('/(\d{3})\s*?,\s*?(\w{2})\s*?,\s*?(\w{3})\s*?,(.*)/', $line, $matches)) {
					if (count($matches) == 5) {
						array_push($list, array(	'country_name'	=> $matches[4],
													'numeric_code'	=> $matches[1],
													'alpha2_code'	=> $matches[2],
													'alpha3_code'	=> $matches[3]));
					}
				}
			}
			unset($lines);
			self::$_country_list_cache = $list;
			unset($list);
		}
		return self::$_country_list_cache;
	}

	/**
	 * GetLicenseKey
	 * Obtains the license key from Interspire.
	 *
	 * @param Array $fields An associative array of all the fields required to submit a license key request.
	 *
	 * @return Mixed The license key, or false if there was a problem obtaining it.
	 */

/* #*#*# DISABLED! FLIPMODE! #*#*#                  ?????????????
	public static function GetLicenseKey($fields){
		// check required values
		$required_fields = array('contactname', 'contactemail', 'applicationurl', 'contactphone', 'country');
		foreach ($required_fields as $field) {
			if (!isset($fields[$field]) || !$fields[$field]) {
				return false;
			}
		}
		// create request
		$xml = "
			<?xml version='1.0' standalone='yes'?>
			<licenserequest>
				<product>iem</product>
				<customer>
					<name><![CDATA[" . htmlspecialchars($fields['contactname'], ENT_QUOTES, 'UTF-8') . "]]></name>
					<email>" . htmlspecialchars($fields['contactemail'], ENT_QUOTES, 'UTF-8') . "</email>
					<url>" . htmlspecialchars($fields['applicationurl'], ENT_QUOTES, 'UTF-8') . "</url>
					<phone>" . htmlspecialchars($fields['contactphone'], ENT_QUOTES, 'UTF-8') . "</phone>
					<country>" . htmlspecialchars($fields['country'], ENT_QUOTES, 'UTF-8') . "</country>
				</customer>
			</licenserequest>
		";
		// submit request
		$response = self::PostData('/www.interspire.com', '/licensing/generate_trial.php', $xml);
		// read response
		if (function_exists('simplexml_load_string')) {
			$check = @simplexml_load_string($response);
			if (is_object($check)) {
				$lk = (string)$check->licensekey;
			}
		} else {
			preg_match('%<(licensekey[^>]*)>(.*?)</licensekey>%is', $response, $matches);
			if (isset($matches[2])) {
				$lk = $matches[2];
			}
		}
		return $lk;
	}
#*#*# / #*#*# */
	

	/**
	 * DbVersionCheck
	 * Checks if the supplied database version is sufficient for the application.
	 *
	 * @param String $type The database type (e.g. 'mysql', 'pgsql').
	 * @param String $version The version string (e.g. '4.1.1').
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string or an array of error strings.
	 */
	public static function DbVersionCheck($type, $version){
		$product = array(
				'mysql' => 'MySQL',
				'pgsql' => 'PostgreSQL',
			);
		$version_ok = IEM_MinimumVersion::Sufficient($type, $version);
		if (!$version_ok) {
			return array(self::DB_BAD_VERSION, array(
				'product' => $product[$type],
				'version' => $version,
				'req_version' => IEM_MinimumVersion::ForApp($type)));
		}
		return array(self::SUCCESS, null);
	}

	/**
	 * DbSufficientPrivileges
	 * Checks to see if the current database user has sufficient privileges to install/upgrade IEM.
	 *
	 * @return Boolean True if the user has sufficient privieleges, otherwise false.
	 */
	public static function DbSufficientPrivileges($db){
		$table_name = '[|PREFIX|]test';
		$queries = array(
			'create' => "CREATE TABLE {$table_name} (id INT PRIMARY KEY, name VARCHAR(50))",
			'index' => "CREATE UNIQUE INDEX {$table_name}_name_idx ON {$table_name} (name)",
			'insert' => "INSERT INTO {$table_name} (id, name) VALUES (1, 'test1')",
			'select' => "SELECT * FROM {$table_name}",
			'update' => "UPDATE {$table_name} SET name='test2' WHERE id=1",
			'delete' => "DELETE FROM {$table_name} WHERE id=1",
			'alter' => "ALTER TABLE {$table_name} ADD COLUMN description TEXT",
			'drop' => "DROP TABLE IF EXISTS {$table_name}",
			);
		$db->Query($queries['drop']); // just in case the table is left over
		$db->StartTransaction();
		foreach ($queries as $operation=>$query) {
			$result = $db->Query($query);
			if (!$result) {
				// Rolling back the transaction will not remove the table and index on MySQL, so we need to drop it.
				$db->Query($queries['drop']);
				$db->RollbackTransaction();
				return false;
			}
		}
		$db->RollbackTransaction();
		return true;
	}

	/**
	 * CheckWritable
	 * Check if file is writable by writing "<?php\n" to it. This will destroy the existing contents of the file!
	 *
	 * @param String $file File to be checked.
	 *
	 * @return Boolean Returns TRUE if file is writable, FALSE otherwise.
	 */
	public static function CheckWritable($file=''){
		if (!$file) {
			return false;
		}

		$unlink = false;

		if (!is_file($file)) {
			$unlink = true;
			if (is_dir($file)) {
				$file = $file . '/' . date('U') . '.php';
			} else {
				return false;
			}
		}

		if (!$fp = @fopen($file, 'w+')) {
			return false;
		}

		$contents = '<?php' . "\n";

		if (!@fputs($fp, $contents, strlen($contents))) {
			return false;
		}

		if (!@fclose($fp)) {
			return false;
		}

		if ($unlink) {
			if (!@unlink($file)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * ValidateLicense
	 * Checks whether the supplied licence is valid or not, and checks if the license can be used with am optionally supplied database type.
	 *
	 * @param String $key The licence key to validate.
	 * @param String $db_type A database type, e.g. 'mysql', 'pgsql'.
	 *
	 * @return Array The first element is an error code indicating success (0) or failure (> 0). The second element is an error string.
	 */
	public static function ValidateLicense($key, $db_type=''){
	/* #*#*# DISABLED! FLIPMODE! #*#*#
		$error = false;
		$msg = '';
		if (ss02k31nnb($key) === false){$error = true; $msg = 'Your license key is invalid - possibly an old license key'; }		
	#*#*# / #*#*# */

		list($error, $msg) = sesion_start($key);
		if ($error){
			return array(self::FIELD_INVALID, $msg);
		}
		if ($db_type && !installCheck($key, $db_type)){
		/* #*#*# DISABLED! FLIPMODE! #*#*#
			return array(self::DB_UNSUPPORTED, 'Your license key only allows you to use a MySQL database.');
		#*#*# / #*#*# */
		}
		return array(self::SUCCESS, null);
	}

	/**
	 * PostData
	 * Posts data to a URL and returns the response.
	 *
	 * @uses PostDataSocket
	 *
	 * @param String $host The host portion of the URL.
	 * @param String $path The path portion of the URL.
	 * @param String $xml The license key request in XML format.
	 *
	 * @return String The server's response data (no headers).
	 */
	private static function PostData($host, $path, $data){
		if (SENDSTUDIO_CURL) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://' . $host . $path);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			curl_close($ch);
		} else {
			$response = IEM_Installer::PostDataSocket($host, $path, $data);
		}
		return $response;
	}

	/**
	 * PostDataSocket
	 * Posts data to a URL and returns the response.
	 * This method should only be used if cURL is not enabled. It does things the long way.
	 * Using fsockopen, it creates a 'form post' with the right details and waits for the return of the message.
	 *
	 * @see PostData
	 *
	 * @param String $host The host portion of the URL.
	 * @param String $path The path portion of the URL.
	 * @param String $data Data to put in the form post.
	 *
	 * @return String Returns the response from the form post.
	 */
	private static function PostDataSocket($host, $url, $data=''){
		$fp = fsockopen($host, 80, $errno, $errstr, 2);
		if (!$fp) {
			return '';
		}

		$newline = "\r\n";
		$post_data = "POST " . $url . " HTTP/1.0" . $newline;
		$post_data .= "Host: " . $host . $newline;
		$post_data .= "Content-Type: text/xml; charset=ISO-8859-1" . $newline;
		$post_data .= "Content-Length: " . strlen($data) . $newline;
		$post_data .= "Connection: close" . $newline . $newline;
		$post_data .= $data;

		fputs($fp, $post_data, strlen($post_data));

		$in_headers = true;
		$response = '';
		while (!feof($fp)) {
			$line = trim(fgets($fp, 1024));

			// the first time we meet a blank line, that means we're not in the header response any more.
			if ($line == '') {
				$in_headers = false;
				continue;
			}

			if ($in_headers) {
				continue;
			}

			$response .= $line;
		}
		fclose($fp);
		return $response;
	}

	/**
	 * GetTimezone
	 * Returns the properly formatted timezone from date().
	 *
	 * @return String The properly formatted timezone.
	 */
	private static function GetTimezone(){
		require_once(IEM_PATH . '/language/default/timezones.php');
		$timezone = date('O');
		$timezone = preg_replace('/([+-])0/', '$1', $timezone);
		if ($timezone == '+000') {
			$timezone = 'GMT';
		}
		$timez = 'GMT';
		foreach ($GLOBALS['SendStudioTimeZones'] as $k => $tz) {
			// if we're using date('O') it doesn't include "GMT" or the ":"
			// see if we can match it up.
			$tz_trim = str_replace(array('GMT', ':'), '', $tz);
			if ($tz_trim == $timezone) {
				$timez = $tz;
				break;
			}
		}
		return $timez;
	}


	/**
	 * iniBool
	 * Returns an actual Boolean value of a Boolean php.ini setting.
	 * So if ini_get('safe_mode') returns 'Off', this function will return false.
	 *
	 * @param String $option The php.ini boolean option to check.
	 *
	 * @return Boolean The Boolean ini value.
	 */
	private static function iniBool($option){
		$val = strtolower(ini_get($option));
		if ($val == 'off') {
			return false;
		}
		return (bool)$val;
	}
}
