<?php
/**
* This file handles remote installations. Data is passed to the script in XML format.
*
* @version     $Id: remote_installer.php,v 1.37 2008/02/20 22:06:02 chris Exp $
* @author Tye <tye@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once (dirname(__FILE__) . '/sendstudio_functions.php');

/**
 * Include the whitelabel file
 */
require_once IEM_PATH . '/language/default/whitelabel.php';

/**
* Class for Remote Installer
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Remote_Installer extends SendStudio_Functions
{

	/**
	* xml
	* The simplexml object holding the XML request
	*
	* @var Object
	*/
	var $xml;

	/**
	* Constructor
	* Doesn't do anything.
	*
	* @return Void Doesn't return anything.
	*/
	function Remote_Installer()
	{
		/**
		* Do our checking for the right version, whether simple-xml is enabled and whether it's a valid xml request.
		*/
		$min_version = '5.1.0';
		$version_check = version_compare(PHP_VERSION, $min_version);

		if ($version_check < 0) {
			$this->Error('The remote installer could not process the request',array(array('code' => 'initError', 'message' => 'The XML-API requires PHP ' . $min_version . ' or higher to work. You have ' . PHP_VERSION)));
		}

		if (!extension_loaded('SimpleXML')) {
			$this->Error('The remote installer could not process the request',array(array('code' => 'initError', 'message' => 'The XML-API requires the SimpleXML extension to be loaded.')));
		}

		if (!isset($_POST['xml'])) {
			$raw_input = file_get_contents('php://input');
			if (empty($raw_input)) {
				$this->Error('The remote installer could not process the request',array(array('code' => 'initError', 'message' => 'No data has been given to the XML-API.')));
			}
			$_POST['xml'] = $raw_input;
		}

		/**
		* we can't use a try/catch and a 'new SimpleXMLObject' here because php4 throws a parse error when it hits the 'try' line.
		* We need a try/catch to check to make sure the xml is valid,
		* so instead of doing it that way, use another function to do it.
		*/

		$this->xml = @simplexml_load_string($_POST['xml']);
		if (!is_object($this->xml)) {
			$this->Error('The remote installer could not process the request',array(array('code' => 'xmlError', 'message' => 'The XML you provided is not valid. Please check your xml document and try again.')));
		}
	}

	/**
	* Process
	* Works out which step we are up to in the install process and passes it off for the other methods to handle.
	*
	* @return Void Works out which step you are up to and that's it.
	*/
	function Process()
	{
		if (SENDSTUDIO_IS_SETUP) {
			$this->Error('Application is already installed in this server');
			exit(1);
		}

		if (is_object($this->xml->install)) {
			$this->Install();
		}
	}

	/**
	* Install
	* Performs an installation based on the request in $xml
	*
	* @return Void Returns nothing, exits on error
	*/
	function Install()
	{
		$install = &$this->xml->install;

		// Required variables:
		$required = array(
			'licenseKey','installPath',
			'user' => array(
				'email',
				'username',
				'password'
			),
			'database' => array(
				/* #*#*# MODIFIED! FLIPMODE! #*#*# */
					'dbUser','dbPass','dbDatabase','dbServer',
				/* #*#*# DISABLED! FLIPMODE! #*#*#
					'dbUser','dbPass','dbDatabase','dbServer','dbType'
				#*#*# / / / / #*#*# */
			)
		);
		$errors = array();
		foreach ($required as $node_name => $node) {
			if (is_array($node)) {
				foreach ($node as $variable) {
					if (!isset($install->$node_name->$variable)) {
						$errors[] = array('code' => 'missing' . ucfirst($node_name) . ucfirst($variable), 'message' => 'The ' . $node_name . ' ' . $variable . ' value was not supplied.');
					}
				}
			} else {
				if (!isset($install->$node)) {
					$errors[] = array('code' => 'missing' . ucfirst($node), 'message' => 'The ' . $node . ' value was not supplied.');
				}
			}
		}
		if (count($errors)) {
			$this->Error('Please fill out all mandatory fields to complete the installation.',$errors);
		}

		// Check if config file is writable

		$config_file = SENDSTUDIO_INCLUDES_DIRECTORY . "/config.php";
		if (!is_writable($config_file)) {
			$this->Error('Before you can install Flipmode\'s Email Marketing Deluxe make sure the following files are writable.',array(array('code' => 'filePermissions', 'message' => $config_file . ' is not writable.')));
		}

		if (!is_writable(TEMP_DIRECTORY)) {
			$this->Error('Before you can install Flipmode\'s Email Marketing Deluxe make sure the following files are writable.',array(array('code' => 'filePermissions', 'message' => TEMP_DIRECTORY . ' is not writable.')));
		}

		$license_key = (string)$install->licenseKey;
		list($error, $msg) = sesion_start($license_key);
		if ($error) {
			$this->Error('A valid license key was not supplied.',array(array('code' => 'badLicenseKey','message' => $msg)));
		}

		/**
		* Connect to the database
		*/

		/**
		* Due to a problem with Plesk only mysql installations can be done
		* ??????????????????????????
		if ($install->database->dbType == 'postgresql') {
			require(dirname(__FILE__) . "/lib/database/pgsql.php");
			$db_type = 'PGSQLDb';
			$db_type_name = 'pgsql';
		} elseif ($install->database->dbType == 'mysql') {
		*/
		require_once IEM_PATH . '/ext/database/mysql.php';
		$db_type = 'MySQLDb';
		$db_type_name = 'mysql';

		defined('SENDSTUDIO_DATABASE_TYPE') or define('SENDSTUDIO_DATABASE_TYPE', $db_type_name);

	/* #*#*# DISABLED! FLIPMODE! #*#*#
		} else {
			$this->Error('The installer was not able to connect to the database.',array(array('code' => 'dbConnectError', 'message' => 'Unknown database type ' . $install->database->dbType)));
		}
	#*#*# / / / / #*#*# */

		$db = new $db_type($install->database->dbServer, $install->database->dbUser, $install->database->dbPass, $install->database->dbDatabase);
		$db->TablePrefix = $install->database->tablePrefix;
		$db->ErrorCallback = array(&$this,'DatabaseError');

		IEM::getDatabase($db);

		if (!$db->connection) {
			$this->Error('The installer was not able to connect to the database.', array(array('code' => 'dbConnectError', 'message' => "Unable to connect to the database: " . $db->GetError())));
		}

		/**
		* Load the database schema file and create the database tables
		*/

		require_once(IEM_PATH . "/install/schema." . $db_type_name . ".php");

		$tableprefix = '';
		if (isset($install->database->tablePrefix)) {
			$tableprefix = (string)$install->database->tablePrefix;
		}

		foreach ($queries as $query) {
			$query = str_replace('%%TABLEPREFIX%%', $tableprefix, $query);
			$db->Query($query);
		}

		/**
		* Find the server timezone and write the configuration file
		*/

		$this->LoadLanguageFile('Timezones');

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

		if (!defined('SENDSTUDIO_SERVERTIMEZONE')) {
			define('SENDSTUDIO_SERVERTIMEZONE', $timez);
		}
		define('SENDSTUDIO_TABLEPREFIX', $tableprefix);

		ob_start();

		$settings_api = $this->GetApi('Settings');

		$settings_details = array();

		$settings_details['DATABASE_UTF8PATCH'] = '1';
		$settings_details['DATABASE_TYPE'] = $db_type_name;
		$settings_details['DATABASE_USER'] = (string)$install->database->dbUser;
		$settings_details['DATABASE_PASS'] = (string)$install->database->dbPass;
		$settings_details['DATABASE_HOST'] = (string)$install->database->dbServer;
		$settings_details['DATABASE_NAME'] = (string)$install->database->dbDatabase;
		$settings_details['TABLEPREFIX'] = $tableprefix;
		$settings_details['LICENSEKEY'] = (string)$install->licenseKey;
		$settings_details['APPLICATION_URL'] = (string)$install->installPath;
		$settings_details['SERVERTIMEZONE'] = $timez;
		$settings_details['DEFAULTCHARSET'] = 'UTF-8';
		$settings_details['EMAIL_ADDRESS'] = (string)$install->user->email;

		// now for the default settings.
		$settings_details['SMTP_PORT'] = '25';

		$settings_details['IPTRACKING'] = '1';

		$settings_details['MAX_IMAGEWIDTH'] = 700;
		$settings_details['MAX_IMAGEHEIGHT'] = 400;

		$settings_details['BOUNCE_IMAP'] = '0';

		$settings_details['ALLOW_EMBEDIMAGES'] = '1';

		$settings_details['ATTACHMENT_SIZE'] = '2048';

		$settings_details['CRON_SEND'] = '5';
		$settings_details['CRON_AUTORESPONDER'] = '10';
		$settings_details['CRON_BOUNCE'] = '60';

		$settings_details['EMAILSIZE_WARNING'] = '500';
		$settings_details['EMAILSIZE_MAXIMUM'] = '2048';

		$settings_details['RESEND_MAXIMUM'] = '3';

		$settings_api->Set('Settings', $settings_details);

		$settings_api->Db = &$db;
		$settings_api->Save();

		// ----- Update the default user account
			$username     = $install->user->username;
			$unique_token = API_USERS::generateUniqueToken($username);
			$new_password = API_USERS::generatePasswordHash($install->user->password, $unique_token);

			$tempServerTimeZone = $db->Quote($settings_details['SERVERTIMEZONE']);
			$tempEmailAddress = $db->Quote(strval($install->user->email));
			$tempUniqueToken = $db->Quote($unique_token);
			$tempUsername = $db->Quote($username);
			$tempPassword = $db->Quote($new_password);
			$tempHTMLFooter = $db->Quote(GetLang('Default_Global_HTML_Footer', ''));
			$tempTEXTFooter = $db->Quote(GetLang('Default_Global_Text_Footer', ''));

			$query = "
				UPDATE {$tableprefix}users
				SET unique_token = '{$tempUniqueToken}',
					usertimezone = '{$tempServerTimeZone}',
					emailaddress ='{$tempEmailAddress}',
					textfooter ='{$tempTEXTFooter}',
					htmlfooter ='{$tempHTMLFooter}',
					username = '{$tempUsername}',
					password ='{$tempPassword}'
				WHERE userid = 1
			";

			$db->Query($query);

			unset($tempTEXTFooter);
			unset($tempHTMLFooter);
			unset($tempPassword);
			unset($tempUniqueToken);
			unset($tempEmailAddress);
			unset($tempServerTimeZone);

			unset($new_password);
			unset($unique_token);
		// -----

		ob_end_clean();

		/**
		* Installation is finished
		*/

		$this->PrintHeader();
		?>
			<status>OK</status>
			<installPath><?php echo $install->installPath; ?></installPath>
			<user>
				<username>admin</username>
				<password><?php echo $install->user->password; ?></password>
			</user>
		<?php
		$this->PrintFooter();
		return;
	}


	/**
	* PrintHeader
	* Prints the XML response header
	*
	* @return Void Returns nothing
	*/
	function PrintHeader()
	{
		if (PHP_SAPI != 'cli') {
			header("Content-Type: text/xml");
		}

		echo '<';
		?>?xml version="1.0" encoding="<?php echo SENDSTUDIO_CHARSET; ?>" ?>
		<response>
		<?php
	}

	/**
	* PrintFooter
	* Prints the XML response footer
	*
	* @return Void Returns nothing
	*/
	function PrintFooter()
	{
		echo "</response>\n";
	}

	/**
	* Error
	* Prints an error response and exits
	*
	* @param String $message The error message
	* @param Array $errors An array of errors, each element in the format of array('code' => code,'message' => message)
	*
	* @return Void Returns nothing
	*/
	function Error($message,$errors = false)
	{
		$this->PrintHeader();
		?>
			<status>ERROR</status>
			<message><?php echo htmlspecialchars($message); ?></message>
		<?php
		if (is_array($errors)) {
			echo '<errors>';
			foreach ($errors as $error) {
				?>
				<error code="<?php echo htmlspecialchars($error['code']); ?>">
					<?php echo htmlspecialchars($error['message']); ?>
				</error>
				<?php
			}
			echo '</errors>';
		}
		$this->PrintFooter();
		exit;
	}

	/**
	* DatabaseError
	* Handles database errors
	*
	* @param String $error The database error message
	* @param Array $query The query that failed
	*
	* @return Void Returns nothing
	*/
	function DatabaseError($error,$query)
	{
		$this->Error('A database query failed: ' . $query,array(array('code' => 'dbError', 'message' => $error)));
	}

}
