<?php
/**
* This file has the upgrade functionality in it.
*
* @version     $Id: upgrade.php,v 1.30 2008/03/04 04:31:33 hendri Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

$GLOBALS['ROOTDIR'] = $ROOTDIR;
$GLOBALS['TABLEPREFIX'] = $TABLEPREFIX;
$GLOBALS['ROOTURL'] = $ROOTURL;
$GLOBALS['DBHOST'] = $DBHOST;
$GLOBALS['DBUSER'] = $DBUSER;
$GLOBALS['DBPASS'] = $DBPASS;
$GLOBALS['DBNAME'] = $DBNAME;
$GLOBALS['LicenseKey'] = $LicenseKey;
$GLOBALS['ServerSending'] = $ServerSending;

/**
* Class for the upgrade process. This will run through all the queries needed to upgrade sendstudio 2004 to sendstudio nx, and change the config file.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Upgrade extends SendStudio_Functions{
	/**
	 * Default characterset
	 * @var String
	 */
	var $default_charset = 'ISO-8859-1';

	/**
	* Process
	* Works out which step we are up to in the install process and passes it off for the other methods to handle.
	*
	* @return Void Works out which step you are up to and that's it.
	*/
	function Process(){
		if (isset($_GET['Action'])) {
			$action = strtolower($_GET['Action']);
			switch ($action) {
				case 'createbackup':
					$this->CreateBackup();
					return;
				break;

				case 'popupiframe':
					header('Content-type: text/html; charset="' . SENDSTUDIO_CHARSET . '"');

					$variables = array();

					switch ($_GET['SubAction']) {
						case 'Backup':
							$tempList = $this->FetchTables();

							$variables['ProgressTitle'] = 'Backup In Progress ...';
							$variables['ProgressMessage'] = sprintf('Please wait while we attempt to backup %s tables...', count($tempList));
							$variables['ProgressReport'] = '';
							$variables['ProgressStatus'] = '';
							$variables['ProgressURLAction'] = 'index.php?Page=Upgrade&Action=CreateBackup';
						break;
						case 'CopyFiles':
							$tempList = list_directories($GLOBALS['ROOTDIR'] . 'temp/images', null, true);

							$variables['ProgressTitle'] = 'Copying files to new location ...';
							$variables['ProgressMessage'] = sprintf('Please wait while we copy %s files to a new location...', count($tempList));
							$variables['ProgressReport'] = '';
							$variables['ProgressStatus'] = '';
							$variables['ProgressURLAction'] = 'index.php?Page=Upgrade&Action=CopyFiles';
						break;
						case 'UpgradeDatabase':
							$variables['ProgressTitle'] = 'Upgrading database ...';
							$variables['ProgressMessage'] = 'Please wait while we upgrade tables in your database...';
							$variables['ProgressReport'] = '';
							$variables['ProgressStatus'] = '';
							$variables['ProgressURLAction'] = 'index.php?Page=Upgrade&Action=UpgradeDatabase';
						break;
					}

					print 	'<html><head><link rel="stylesheet" href="includes/styles/stylesheet.css" type="text/css"></head>'.
							'<body class="popupBody"><div class="popupContainer">';

					$template = file_get_contents(SENDSTUDIO_TEMPLATE_DIRECTORY . '/progressreport_popup.tpl');
					foreach ($variables as $key=>$value) {
						$template = str_replace('%%GLOBAL_'.$key.'%%', $value, $template);
					}
					print $template;

					print	'</div></body></html>';
					return;
				break;

				case 'upgradedatabase':
					$this->UpgradeDatabase();
					return;
				break;

				case 'copyfiles':
					$this->CopyFiles();
					return;
				break;
			}
		}

		$this->PrintHeader();
		?>
			<script src="includes/js/jquery.js"></script>
			<script src="includes/js/jquery/thickbox.js"></script>
			<link rel="stylesheet" type="text/css" href="includes/styles/thickbox.css" />
			<link rel="stylesheet" href="includes/styles/stylesheet.css" type="text/css">
		<?php

		$step = 0;
		if (isset($_GET['Step'])) {
			$step = (int)$_GET['Step'];
		}

		if ($step <= 0) {
			$step = 0;
		}

		$handle_step = 'ShowStep_' . $step;

		$this->$handle_step();
		$this->PrintFooter();
	}

	/**
	* ShowStep_0
	* This shows the first "thanks for purchasing" page.
	* Doesn't do anything else.
	*
	* @return Void Doesn't return anything.
	*/
	function ShowStep_0(){
		?>
		<form method="post" action="index.php?Page=Upgrade&Step=1">
		<table cellSpacing="0" cellPadding="0" width="95%" align="center">
			<tr>
				<td class="Heading1">Welcome to the Sendstudio Upgrade Wizard</TD>
			</tr>
			<tr>
				<td class="Gap">&nbsp;</TD>
			</tr>
			<tr>
				<td>
					<table class="Panel" id="Table14" width="100%">
						<tr>
							<td class="Content" colSpan="2">
								<table id="Table2" style="border-right: #adaaad 1px solid; border-top: #adaaad 1px solid; border-left: #adaaad 1px solid; border-bottom: #adaaad 1px solid; background-color: #f7f7f7"
									cellSpacing="0" cellPadding="10" width="100%" border="0">
									<tr>
										<td>
											<table width="100%" class="Message" cellSpacing="0" cellPadding="0" border="0">
												<tr>
													<td width="20"><img height="18" hspace="5" src="images/success.gif" width="18" align="middle" vspace="5"></td>
													<td class="Text">Thank you for upgrading Sendstudio!<BR>
													</td>
												</tr>
											</table>
											<div class="Text">
												Welcome to the Sendstudio upgrade wizard. Over the next 4 steps your current copy of SendStudio (including your database) will be upgraded.<br>Click the "Proceed" button below to get started and create a backup of your database.
											</div>
										</td>
									</tr>
									<tr>

										<td>
											<input type="submit" name="WelcomeProceedButton" value="Proceed" class="FormButton" />
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		<?php

		$vars = array(
			'DatabaseTables_BackupErrors',
			'BackupFile',
			'DatabaseTables_Todo',
			'DatabaseTables_Done',

			'DatabaseUpgradesCompleted',
			'DatabaseUpgradesFailed',

			'DirectoriesToCopy',
			'DirectoriesCopied',
			'DirectoriesNotCopied'
		);
		foreach ($vars as $k => $var) {
			IEM::sessionRemove($var);
		}
	}

	/**
	* CheckPermissions
	* Checks permissions on the appropriate folders.
	* If permissions aren't correct, a message is displayed and you can't continue until they are fixed.
	* Also checks to make sure either 'mysql' or 'postgresql' functions are available
	* That is, some sort of database functionality is there.
	*
	* @return Void If everything is ok, this will return out of the function. If something is wrong, it prints an error message and the script dies.
	*/
	function CheckPermissions(){
		$isOK = true;
		$permError = '';
		$serverError = '';
		$permArray = array(
			'/includes/config.php',
			'/temp'
		);

		$linux_message = 'Please CHMOD it to 757.';
		$windows_message = 'Please set anonymous write permissions in IIS. If you don\'t have access to do this, you will need to contact your hosting provider.';

		$error_message = $linux_message;
		if (strtolower(substr(PHP_OS, 0, 3)) == 'win') {
			$error_message = $windows_message;
		}

		foreach ($permArray as $a) {
			if (!$this->CheckWritable(SENDSTUDIO_BASE_DIRECTORY . $a)) {
				$permError .= sprintf("<li>The file or folder <b>%s</b> isn't writable. " . $error_message . "</li>", SENDSTUDIO_BASE_DIRECTORY . str_replace('/', DIRECTORY_SEPARATOR, $a));
				$isOK = false;
			}
		}

		if (SENDSTUDIO_SAFE_MODE) {
			if (!$this->CheckWritable(TEMP_DIRECTORY . '/send')) {
				$permError .= sprintf("<li>The file or folder <b>%s</b> isn't writable. " . $error_message . "</li>", TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'send');
				$isOK = false;
			}
			if (!$this->CheckWritable(TEMP_DIRECTORY . '/autoresponder')) {
				$permError .= sprintf("<li>The file or folder <b>%s</b> isn't writable. " . $error_message . "</li>", TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'autoresponder');
				$isOK = false;
			}
		}

		if (!function_exists('mysql_connect') && !function_exists('pg_connect')) {
			$serverError .= '<li>Your server does not support mysql or postgresql databases. PHP on your web server needs to be compiled with MySQL or PostgreSQL support.<br><br>
			For more information:<br>
			<a href="http://www.php.net/mysql" target="_blank">http://www.php.net/mysql</a><br>
			<a href="http://www.php.net/pgsql" target="_blank">http://www.php.net/pgsql</a><br><br>
			Please contact your web hosting provider or administrator for more details.
			</li>';
			$isOK = false;
		}

		// since the upgrade from v2004 to nx can only be mysql, we don't need to check the database type.
		$query = "SELECT VERSION() AS version";
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		$version = $row['version'];
		$compare = version_compare($version, '4.0');
		if ($compare < 0) {
			$serverError .= '<li>SendStudio NX requires MySQL v4.0 or above to work properly. Your server is running ' . $version . '. To complete the upgrade, your host will need to upgrade MySQL. Please contact your host to arrange this.</li>';
			$isOK = false;
		}

		if ($isOK) {
			return;
		}

		?>
		<form method="post" action="index.php?Page=Upgrade&Step=1">
		<table cellSpacing="0" cellPadding="0" width="95%" align="center">
			<tr>
				<td class="Heading1">Oops... Something Went Wrong</TD>
			</tr>
			<tr>
				<td class="Text"><br/></TD>
			</tr>
			<tr>
				<td>
					<table border=0 cellspacing="0" cellpadding="0" width=100% class="Text">
						<tr>
							<td colspan='2'>
								<table border='0' cellspacing='0' cellpadding='0'>
									<tr>
										<td class='Message' width='20' valign='top'>
											<img src='images/error.gif' width='18' height='18' hspace='10'>
										</td>
										<td class='Message' width='100%'>
											<?php
												if ($permError) {
													echo 'The following files or folders cannot be written to:<br/>';
													echo '<ul>';
													echo $permError;
													echo '</ul>';
													if ($serverError) {
														echo '<br/>';
													}
												}

												if ($serverError) {
													echo 'The following problems have been found with your server:<br/>';
													echo '<ul>';
													echo $serverError;
													echo '</ul>';
												}
											?>
											<br/>
											<input type="submit" value="Try Again" class="FormButton">
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		<?php
		$this->PrintFooter();
		exit;
	}

	/**
	* CheckFiles
	* Checks the number of files in the admin/com/upgrades/{upgrade_version}/ folder.
	*
	* @return Void If everything is ok, this will return out of the function. If something is wrong, it prints an error message and the script dies.
	*/
	function CheckFiles(){
		$upgrade_api = new Upgrade_API();
		$upgrades_to_run = $upgrade_api->Get('upgrades_to_run');
		$nx_upgrades = $upgrades_to_run['nx'];
		$nx_upgrades_found = sizeof($nx_upgrades);
		$upgrades_found = list_files(IEM_PATH . '/upgrades/nx');

		if (sizeof($upgrades_found) == $nx_upgrades_found) {
			return;
		}

		$upgrades_found = str_replace('.php', '', $upgrades_found);
		$missing_files = array_diff($nx_upgrades, $upgrades_found);

		?>
		<form method="post" action="index.php?Page=Upgrade&Step=1">
		<table cellSpacing="0" cellPadding="0" width="95%" align="center">
			<tr>
				<td class="Heading1">Weops... Something Went Wrong</td>
			</tr>
			<tr>
				<td class="Text"><br/></TD>
			</tr>
			<tr>
				<td>
					<table border=0 cellspacing="0" cellpadding="0" width=100% class="Text">
						<tr>
							<td colspan='2'>
								<table border='0' cellspacing='0' cellpadding='0'>
									<tr>
										<td class='Message' width='20' valign='top'>
											<img src='images/error.gif' width='18' height='18' hspace='10'>
										</td>
										<td class='Message' width='100%'>
											The following file(s) are missing from the admin/com/upgrades/nx/ folder:<br/>
											<ul>
												<?php
													foreach ($missing_files as $p => $filename) {
														echo '<li>' . $filename . '.php</li>';
													}
												?>
											</ul>
											Once you have uploaded the file(s) mentioned, try again.
											<br/>
											<br/>
											<input type="submit" value="Try Again" class="FormButton">
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		<?php
		$this->PrintFooter();
		exit;
	}

	/**
	* ShowStep_1
	* Step 1 checks the license key is valid and permissions on the appropriate files/folders.
	*
	* @param String $license_error If there is a license key error it is passed in here so we can display an error message. If this is empty, we are at step 1 for the first time so an error message isn't shown.
	*
	* @see CheckPermissions
	* @see ShowStep_2
	*
	* @return Void Doesn't return anything. Checks permissions, shows the license key box and (if applicable) shows an error message if the license key is invalid.
	*/
	function ShowStep_1(){
		$already_upgraded = $this->AlreadyUpgraded();
		if ($already_upgraded) {
			return;
		}
		$permissions = $this->CheckPermissions();
		$check_files = $this->CheckFiles();

		?>
		<script>
			$(function() {
				$('input#startBackup').click(function(event) {
					tb_show('', 'index.php?Page=Upgrade&Action=PopupIFrame&SubAction=Backup&keepThis=true&TB_iframe=tue&height=265&width=450&modal=true', '');
					event.preventDefault();
					event.stopPropagation();
				});
			});
		</script>
		<form>
			<table cellspacing="0" cellpadding="0" width="95%" align="center">
				<tr>
					<td class="Heading1">Step 1: Creating a database backup</td>
				</tr>
				<tr>
					<td class="Text"><br /><br /></td>
				</tr>
				<tr>
					<td>
						<table class="Panel" border="0" cellpadding="2" cellspacing="0" width="100%">
							<tr class="Heading3">
								<td colspan="2">
									&nbsp;&nbsp;Database Backup
								</td>
							</tr>
							<tr>
								<td>
									&nbsp;
								</td>
								<td style="padding:10px">
									Before upgrading, your existing SendStudio database will be backed up. If at any time during the upgrade something goes wrong,<br>we can use this backup to restore your database. Click the "Create Backup" button below to continue.
									<br><br>
									<input id="startBackup" type="button" value="Create Backup &raquo;" class="Field150">
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</form>
		<?php
	}

	/**
	* ShowStep_2
	* Checks the license key from step 1. If it is invalid, it goes back to step 1 and that's it.
	* If the license key is valid, database information is displayed.
	* If there are database errors (step 3 checks this) then this will also display the database errors so they can be addressed.
	*
	* @param Boolean $dberror Whether there is a database error or not.
	* @param Array $query_errors The database errors step 3 encountered.
	*
	* @see ShowStep_1
	* @see ShowStep_3
	*/
	function ShowStep_2(){
		$backup_errors = IEM::sessionGet('DatabaseTables_BackupErrors');

		if (!empty($backup_errors)) {
			?>
				<table cellspacing="0" cellpadding="0" width="95%" align="center">
					<tr>
						<td class="Heading1">Step 1: Backup Errors</TD>
					</tr>
					<tr>
						<td class="Text">
							<!-- #*#*# DISABLED! FLIPMODE! #*#*#
								<br/>There were problems creating a backup of your database. Please contact <a href="mailto:help@interspire.com">help@interspire.com</a> before proceeding.<br/><br/>
							#*#*# / / / / #*#*# -->
							<br/>There were problems creating a backup of your database.<br/><br/>
						
						</td>
					</tr>
				</table>
			<?php
			return;
		}
		$backup_file = IEM::sessionGet('BackupFile');
		if (preg_match('~/$~', $GLOBALS['ROOTURL']) == 0) {
			$GLOBALS['ROOTURL'] .= '/';
		}
		$backup_url = str_replace(TEMP_DIRECTORY, substr($GLOBALS['ROOTURL'], 0, -1) . SENDSTUDIO_TEMP_URL, $backup_file);

		?>
		<form>
		<table cellspacing="0" cellpadding="0" width="95%" align="center">
			<tr>
				<td class="Heading1">Step 2: Copying Images and Attachments</TD>
			</tr>
			<tr>
				<td class="Text"><br/><br/></TD>
			</tr>
			<tr>
				<td>
					<table class="Panel" border="0" cellpadding="2" cellspacing="0" width="100%">
						<tr class="Heading3">
							<td colspan="2">
								&nbsp;&nbsp;Copying Images and Attachments
							</td>
						</tr>
						<tr>
							<td>
								&nbsp;
							</td>
							<td style="padding:10px">
								You database has been backed up. Right click <a href="<?php echo $backup_url; ?>" target="_blank">this link</a> and choose "Save As" to save the backup to your hard drive.<br>The upgrade wizard will now copy your images and attachments to their new locations. Click "Copy Files" to continue.
								<br><br>
								<input id="startCopy" type="button" value="Copy Files &raquo;" class="Field150">
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		<script>
			$(function() {
				$('input#startCopy').click(function(event) {
					tb_show('', 'index.php?Page=Upgrade&Action=PopupIFrame&SubAction=CopyFiles&keepThis=true&TB_iframe=tue&height=230&width=450&modal=true', '');
					event.preventDefault();
					event.stopPropagation();
				});
			});
		</script>
		<?php
	}

	/**
	 * ShowStep_3
	 * @return Void Returns nothing
	 */
	function ShowStep_3(){
		$copy_errors = IEM::sessionGet('DirectoriesNotCopied');

		if (!empty($copy_errors)) {
			?>
				<table cellspacing="0" cellpadding="0" width="95%" align="center">
					<tr>
						<td class="Heading1">Step 2: Copy Errors</TD>
					</tr>
					<tr>
						<td class="Text">
							<!-- #*#*# DISABLED! FLIPMODE! #*#*#
							<br/>There were problems copying images and attachments to their new locations. Please contact <a href="mailto:help@interspire.com">help@interspire.com</a> before proceeding.<br/><br/>
							#*#*# / / / / #*#*# -->
							
							<br/>There were problems copying images and attachments to their new locations.<br/><br/>
						
						</td>
					</tr>
				</table>
			<?php
			return;
		}
		?>
		<form>
		<table cellspacing="0" cellpadding="0" width="95%" align="center">
			<tr>
				<td class="Heading1">Step 3: Database Upgrade</TD>
			</tr>
			<tr>
				<td class="Text"><br/><br/></TD>
			</tr>
			<tr>
				<td>
					<table class="Panel" border="0" cellpadding="2" cellspacing="0" width="100%">
						<tr class="Heading3">
							<td colspan="2">
								&nbsp;&nbsp;Upgrade database
							</td>
						</tr>
						<tr>
							<td>
								&nbsp;
							</td>
							<td style="padding:10px">
								Your database has been backed up successfully.
								<?php
									if (SENDSTUDIO_SAFE_MODE) {
									<!-- #*#*# DISABLED! FLIPMODE! #*#*#
										echo '<br/><br/><b>There were problems copying images and attachments to their new locations. This is because safe-mode is enabled on your server. This step has been bypassed, please contact <a href="mailto:help@interspire.com">help@interspire.com</a> once your upgrade has finished.</b><br/>';
									#*#*# / / / / #*#*# -->
										echo '<br/><br/><b>There were problems copying images and attachments to their new locations. This is because safe-mode is enabled on your server. This step has been bypassed.</b><br/>';
									
									} else {
										echo 'Your images and attachments have also been copied over successfully.';
									}
								?><br>Click the "Upgrade Database" button below to upgrade your SendStudio database.
								<br><br>
								<input id="startUpgrade" type="button" value="Upgrade Database &raquo;" class="Field150" />
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
		</form>
		<script>
			$(function() {
				$('input#startUpgrade').click(function(event) {
					tb_show('', 'index.php?Page=Upgrade&Action=PopupIFrame&SubAction=UpgradeDatabase&keepThis=true&TB_iframe=tue&height=265&width=450&modal=true', '');
					event.preventDefault();
					event.stopPropagation();
				});
			});
		</script>
		<?php
	}

	/**
	 * ShowStep_4
	 * @return Void Returns nothing
	 */
	function ShowStep_4(){
		$upgrade_errors = IEM::sessionGet('DatabaseUpgradesFailed');

		if (!empty($upgrade_errors)) {
			?>
				<table cellspacing="0" cellpadding="0" width="95%" align="center">
					<tr>
						<td class="Heading1">Step 3: Upgrade Errors</TD>
					</tr>
					<tr>
						<td class="Text">
						<!-- #*#*# DISABLED! FLIPMODE! #*#*#
							<br/>There were problems upgrading your database. Please contact <a href="mailto:help@interspire.com">help@interspire.com</a> and tell them the following errors occurred:<br/><br/>
						#*#*# / / / / #*#*# -->
							<br/>There were problems upgrading your database.<br/><br/>
							
							<textarea cols="100" rows="5" onfocus="this.select();"><?php
								foreach ($upgrade_errors as $p => $upgrade_problem) {
									echo $upgrade_problem . "\n";
								}
							?></textarea>
							<br/>
						</td>
					</tr>
				</table>
			<?php
			return;
		}

		$backup_file = IEM::sessionGet('BackupFile');
		if ($backup_file) {
			$backup_files = list_files(TEMP_DIRECTORY);
			foreach ($backup_files as $p => $backupfile) {
				if (strpos($backupfile, 'system_backup.'.date('m-d-Y').'.txt') !== false) {
					unlink(TEMP_DIRECTORY . '/' . $backupfile);
				}
			}
		}

		require_once(SENDSTUDIO_API_DIRECTORY . '/settings.php');

		$settings_api = new Settings_API(false);

		$settings = array();
		// hardcode this in, for this upgrade it's always going to be mysql.
		$settings['DATABASE_TYPE'] = 'mysql';
		$settings['DATABASE_USER'] = $GLOBALS['DBUSER'];
		$settings['DATABASE_PASS'] = $GLOBALS['DBPASS'];
		$settings['DATABASE_HOST'] = $GLOBALS['DBHOST'];
		$settings['DATABASE_NAME'] = $GLOBALS['DBNAME'];
		$settings['TABLEPREFIX'] = $GLOBALS['TABLEPREFIX'];

		$settings['LICENSEKEY'] = $GLOBALS['LicenseKey'];

		$settings['APPLICATION_URL'] = substr($GLOBALS['ROOTURL'], 0, -1);

		$settings['CRON_ENABLED'] = $GLOBALS['ServerSending'];

		$timezone = date('O');
		if ($timezone == '+0000') {
			$timezone = 'GMT';
		} else {
			$minutes = substr($timezone, -2);
			$timezone = 'GMT' . substr_replace($timezone, ':' . $minutes, -2);
		}

		$settings['SERVERTIMEZONE'] = str_replace(array('GMT-0', 'GMT+0'), array('GMT-', 'GMT+'), $timezone);

		$settings['DEFAULTCHARSET'] = $this->default_charset;

		$empty_settings = array('SMTP_SERVER', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'HTMLFOOTER', 'TEXTFOOTER', 'EMAIL_ADDRESS', 'BOUNCE_ADDRESS', 'BOUNCE_SERVER', 'BOUNCE_USERNAME', 'BOUNCE_PASSWORD', 'BOUNCE_EXTRASETTINGS');
		foreach ($empty_settings as $k => $set) {
			$settings[$set] = '';
		}

		$zero_settings = array('SMTP_PORT', 'FORCE_UNSUBLINK', 'MAXHOURLYRATE', 'MAXOVERSIZE', 'IPTRACKING', 'BOUNCE_IMAP');
		foreach ($zero_settings as $k => $set) {
			$settings[$set] = '0';
		}

		$settings['MAX_IMAGEWIDTH'] = 700;
		$settings['MAX_IMAGEHEIGHT'] = 400;

		$settings_api->Set('Settings', $settings);

		define('SENDSTUDIO_DATABASE_TYPE', 'mysql');
		define('SENDSTUDIO_DATABASE_HOST', $GLOBALS['DBHOST']);
		define('SENDSTUDIO_DATABASE_USER', $GLOBALS['DBUSER']);
		define('SENDSTUDIO_DATABASE_PASS', $GLOBALS['DBPASS']);
		define('SENDSTUDIO_DATABASE_NAME', $GLOBALS['DBNAME']);
		define('SENDSTUDIO_TABLEPREFIX', $GLOBALS['TABLEPREFIX']);

		if (!defined('SENDSTUDIO_DEFAULTCHARSET')) {
			define('SENDSTUDIO_DEFAULTCHARSET', $this->default_charset);
		}

		if (!class_exists('MySQLDb', false)) {
			require_once(IEM_PATH . '/ext/database/mysql.php');
		}
		$db_type = 'MySQLDb';
		$db = new $db_type();

		$connection = $db->Connect(SENDSTUDIO_DATABASE_HOST, SENDSTUDIO_DATABASE_USER, SENDSTUDIO_DATABASE_PASS, SENDSTUDIO_DATABASE_NAME);

		$settings_api->Db = &$db;

		$settings_api->Save();

		?>
			<table cellspacing="0" cellpadding="0" width="95%" align="center">
				<tr>
					<td class="Heading1">Step 4: Upgrade Complete</TD>
				</tr>
				<tr>
					<td class="Text"><br/><br/></TD>
				</tr>
				<tr>
					<td>
						<table class="Panel" border="0" cellpadding="2" cellspacing="0" width="100%">
							<tr class="Heading3">
								<td colspan="2">
									&nbsp;&nbsp;Important Notes. Please Read.
								</td>
							</tr>
							<tr>
								<td>
									&nbsp;
								</td>
								<td style="padding:10px">
									<br/>The upgrade wizard has been completed successfully. You can log in <a href="<?php echo $_SERVER['PHP_SELF']; ?>">here</a> - your login details have not changed.<br>It's very important that you read the notes below, so please do that now:<br/><br/>
									<ul>
										<li>The default character set is set to 'ISO-8859-1'. If you need to change this, you will need to edit your admin/includes/config.php file to change it to 'UTF-8'.</li>
										<li>Sendstudio now supports timezones. Please check the settings page and confirm the server timezone. Please also check the timezone for each user and adjust it accordingly, they have all been set to GMT.</li>
										<li>Information (such as the date a person unsubscribed) was not stored, so the upgrade had to "guess" when this happened and set all of that information to today's date.</li>
										<li>Existing autoresponder statistics are not accurate. Information about who was sent which type of autoresponder was previously not recorded. That is, whether a subscriber was sent the html version or the text version.</li>
										<li>Users &amp; settings have a lot of new options.</li>
										<li>Custom fields have been associated with all of a users mailing list. Please check these associations.</li>
										<li>All forms have been set to 'Classic White (Default)', please adjust as necessary.</li>
										<li>You may need to clear your browsers cache to see the new images and buttons.</li>
									</ul>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		<?php
	}

	/**
	 * CopyFiles
	 * @return Void Returns nothing
	 */
	function CopyFiles(){
		if (SENDSTUDIO_SAFE_MODE) {
			?>
				<script>
					self.parent.parent.location = 'index.php?Page=Upgrade&Step=3';
				</script>
			<?php
			return;
		}
		$dirs_to_copy = IEM::sessionGet('DirectoriesToCopy');
		if (!$dirs_to_copy) {
			$dirs_to_copy = list_directories($GLOBALS['ROOTDIR'] . 'temp/images', null, true);

			IEM::sessionSet('DirectoriesToCopy', $dirs_to_copy);

			$dirs_copied = array();
			IEM::sessionSet('DirectoriesCopied', $dirs_copied);

			$dirs_not_copied = array();
			IEM::sessionSet('DirectoriesNotCopied', $dirs_not_copied);
		}

		$dirs_to_copy = IEM::sessionGet('DirectoriesToCopy');
		$dirs_copied = IEM::sessionGet('DirectoriesCopied');

		// Check if there is anything to copy
		if (count($dirs_to_copy) == 0) {
			?>
				<script>
					self.parent.parent.location = 'index.php?Page=Upgrade&Step=3';
				</script>
			<?php
		}

		if ($dirs_to_copy == $dirs_copied) {

			// copy attachments last. there won't be too many of these so we'll do it all in one step.
			$all_attachments = array();
			$query = "SELECT AttachmentID, AttachmentFilename, AttachmentName FROM " . $GLOBALS['TABLEPREFIX'] . "attachments";
			$result = mysql_query($query);
			while ($row = mysql_fetch_assoc($result)) {
				$all_attachments[$row['AttachmentID']] = array('filename' => $row['AttachmentFilename'], 'realname' => $row['AttachmentName']);
			}

			if (!empty($all_attachments)) {
				$query = "select ComposedID, AttachmentIDs from " . $GLOBALS['TABLEPREFIX'] . "composed_emails where attachmentids != ''";
				$result = mysql_query($query);
				while ($row = mysql_fetch_assoc($result)) {
					$new_folder = TEMP_DIRECTORY . '/newsletters/' . $row['ComposedID'];
					CreateDirectory($new_folder);
					$attachments = explode(':', stripslashes($row['AttachmentIDs']));
					foreach ($attachments as $k => $attachid) {
						$fname = basename($all_attachments[$attachid]['filename']);
						$file = $GLOBALS['ROOTDIR'] . 'temp/attachments/' . $fname;

						$realname = $all_attachments[$attachid]['realname'];
						copy($file, $new_folder . '/' . $realname);

						if (!SENDSTUDIO_SAFE_MODE) {
							@chmod($new_folder . '/' . $realname, 0644);
						}
					}
				}

				$query = "select AutoresponderID, AttachmentIDs from " . $GLOBALS['TABLEPREFIX'] . "autoresponders where attachmentids != ''";
				$result = mysql_query($query);
				while ($row = mysql_fetch_assoc($result)) {
					$new_folder = TEMP_DIRECTORY . '/autoresponders/' . $row['ComposedID'];
					CreateDirectory($new_folder);
					$attachments = explode(':', stripslashes($row['AttachmentIDs']));
					foreach ($attachments as $k => $attachid) {
						$fname = basename($all_attachments[$attachid]['filename']);
						$file = $GLOBALS['ROOTDIR'] . 'temp/attachments/' . $fname;

						$realname = $all_attachments[$attachid]['realname'];
						copy($file, $new_folder . '/' . $realname);

						if (!SENDSTUDIO_SAFE_MODE) {
							@chmod($new_folder . '/' . $realname, 0644);
						}
					}
				}
			}
			?>
				<script>
					self.parent.parent.location = 'index.php?Page=Upgrade&Step=3';
				</script>
			<?php
			return;
		}

		$listProcessed = count($dirs_copied);
		$listTotal = count($dirs_to_copy);
		$percentProcessed = 0;

		foreach ($dirs_to_copy as $p => $dir) {
			if (in_array($dir, $dirs_copied)) {
				continue;
			}

			$percentProcessed = ceil(($listProcessed / $listTotal)*100);
			echo "<script>\n";
			echo sprintf("self.parent.UpdateStatusReport('%s');", "Files copied: {$listProcessed}/{$listTotal}");
			echo sprintf("self.parent.UpdateStatus('%s', %d);", "Copying directory \\'{$dir}\\' to new location ...", $percentProcessed);
			echo "</script>\n";
			flush();

			echo 'Copying directory ' . str_replace($GLOBALS['ROOTDIR'], '', $dir) . ' to new location...<br/>';

			$new_dir = str_replace($GLOBALS['ROOTDIR'] . 'temp/images', TEMP_DIRECTORY . '/user', $dir);
			$copied = CopyDirectory($dir, $new_dir);
			if (!$copied) {
				$dirs_not_copied[] = $dir;
				IEM::sessionSet('DirectoriesNotCopied', $dirs_not_copied);
			}
			$dirs_copied[] = $dir;
			IEM::sessionSet('DirectoriesCopied', $dirs_copied);

			$listProcessed++;
		}
		?>
			<script>
				setTimeout('window.location="index.php?Page=Upgrade&Action=CopyFiles"', 1);
			</script>
		<?php
	}

	/**
	 * UpgradeDatabase
	 * @return Void Returns nothing
	 */
	function UpgradeDatabase(){
		$disabled_functions = explode(',', str_replace(' ', '', SENDSTUDIO_DISABLED_FUNCTIONS));

		if (!SENDSTUDIO_SAFE_MODE && !in_array('set_time_limit', $disabled_functions)) {
			set_time_limit(0);
		}

		define('SENDSTUDIO_DATABASE_TYPE', 'mysql');
		define('SENDSTUDIO_DATABASE_HOST', $GLOBALS['DBHOST']);
		define('SENDSTUDIO_DATABASE_USER', $GLOBALS['DBUSER']);
		define('SENDSTUDIO_DATABASE_PASS', $GLOBALS['DBPASS']);
		define('SENDSTUDIO_DATABASE_NAME', $GLOBALS['DBNAME']);
		define('SENDSTUDIO_TABLEPREFIX', $GLOBALS['TABLEPREFIX']);

		if (!defined('SENDSTUDIO_DEFAULTCHARSET')) {
			define('SENDSTUDIO_DEFAULTCHARSET', $this->default_charset);
		}

		if (!class_exists(SENDSTUDIO_DATABASE_TYPE . 'Db', false)) {
			require_once(IEM_PATH . '/ext/database/' . SENDSTUDIO_DATABASE_TYPE . '.php');
		}

		$db = new MySQLDb();
		$connection = $db->Connect(SENDSTUDIO_DATABASE_HOST, SENDSTUDIO_DATABASE_USER, SENDSTUDIO_DATABASE_PASS, SENDSTUDIO_DATABASE_NAME);
		$GLOBALS['SendStudio']['Database'] = &$db;
		require_once(SENDSTUDIO_API_DIRECTORY . '/upgrade.php');
		$upgrade_api = new Upgrade_API();
		$upgrades_done = IEM::sessionGet('DatabaseUpgradesCompleted');

		if (!$upgrades_done) {
			$upgrades_done = array();
			IEM::sessionSet('DatabaseUpgradesCompleted', $upgrades_done);
			$upgrades_failed = array();
			IEM::sessionSet('DatabaseUpgradesFailed', $upgrades_failed);

			$upgrades_to_run = $upgrade_api->GetUpgradesToRun('2004', SENDSTUDIO_DATABASE_VERSION);

			IEM::sessionSet('UpgradesToRun', $upgrades_to_run['upgrades']);
			IEM::sessionSet('UpgradeCount', $upgrades_to_run['number_to_run']);
		}

		$upgrades_failed = IEM::sessionGet('DatabaseUpgradesFailed');
		$server_timeoffset = date('O');
		$offset_direction = $server_timeoffset{0};
		$offset_hours = $server_timeoffset{1} . $server_timeoffset{2};
		$offset_minutes = $server_timeoffset{3} . $server_timeoffset{4};
		$offset_query = "-(" . $offset_direction . (60 * 60) * (($offset_hours . $offset_minutes) / 100) . ")";
		$upgrade_api->Set('offset_query', $offset_query);

		$running_upgrade = $upgrade_api->GetNextUpgrade();
		if (!is_null($running_upgrade)) {
			// ----- Upgrade message
				$upgradeMessage = '';

				$statusList = IEM::sessionGet('DatabaseUpgradeStatusList');
				if (isset($statusList[$running_upgrade])) {
					$statusQuery = $statusList[$running_upgrade];
					$upgradeMessage = " ({$statusQuery['Processed']}/{$statusQuery['Total']})";
				}
			// -----

			$totalUpgradeCount = IEM::sessionGet('UpgradeCount');

			// Total queries processed
			$totalProcessed = count($upgrades_done);

			// Print message before update
			$percentProcessed = ceil(($totalProcessed / $totalUpgradeCount)*100);
			echo "<script>\n";
			echo sprintf("self.parent.UpdateStatusReport('%s');", "Queries processed: {$totalProcessed}/{$totalUpgradeCount}");
			echo sprintf("self.parent.UpdateStatus('%s', %d);", addcslashes("Executing query '{$running_upgrade}' {$upgradeMessage}", "'\n\r"), $percentProcessed);
			echo "</script>\n";
			flush();

			$upgrade_result = $upgrade_api->RunUpgrade($running_upgrade);

			$percentProcessed = ceil(($totalProcessed / $totalUpgradeCount)*100);
			echo "<script>\n";
			echo sprintf("self.parent.UpdateStatusReport('%s');", "Queries processed: {$totalProcessed}/{$totalUpgradeCount}");
			echo sprintf("self.parent.UpdateStatus('%s', %d);", addcslashes("Query '{$running_upgrade}' {$upgradeMessage} finished running", "'\n\r"), $percentProcessed);
			echo "</script>\n";
			flush();

			if ($upgrade_result === true || $upgrade_result === false) {
				$upgrades_done[] = $running_upgrade;
				IEM::sessionSet('DatabaseUpgradesCompleted', $upgrades_done);

				$upgrades_todo = IEM::sessionGet('UpgradesToRun', array());
				$version = array_keys($upgrades_todo);

				do {
					if (empty($version)) {
						$upgrades_todo = array();
						break;
					}

					if (empty($upgrades_todo[$version[0]])) {
						unset($upgrades_todo[$version[0]]);
						array_shift($version);
						continue;
					}

					array_shift($upgrades_todo[$version[0]]);
					break;
				} while(true);

				IEM::sessionSet('UpgradesToRun', $upgrades_todo);
			}

			if (!$upgrade_result) {
				$upgrades_failed[] = $upgrade_api->Get('error');
				IEM::sessionSet('DatabaseUpgradesFailed', $upgrades_failed);
			}
			?>
				<script>
					setTimeout('window.location="index.php?Page=Upgrade&Action=UpgradeDatabase"', 1);
				</script>
			<?php
			return;
		}
		?>
			<script>
				self.parent.parent.location = 'index.php?Page=Upgrade&Step=4';
			</script>
		<?php
		return;
	}

	/**
	 * CreateBackup
	 * @return Void Returns nothing
	 */
	function CreateBackup(){
		?>
			<script>
				self.parent.parent.location = 'index.php?Page=Upgrade&Step=2';
			</script>
		<?php
		return;

		$disabled_functions = explode(',', str_replace(' ', '', SENDSTUDIO_DISABLED_FUNCTIONS));

		if (!SENDSTUDIO_SAFE_MODE && !in_array('set_time_limit', $disabled_functions)) {
			set_time_limit(0);
		}

		$backupfile = IEM::sessionGet('BackupFile');
		if (!$backupfile) {
			$orig_backupfile = TEMP_DIRECTORY . '/system_backup.' . date('m-d-Y').'.txt';
			$backupfile = $orig_backupfile;
			$c = 1;
			while (true) {
				if (!is_file($backupfile)) {
					break;
				}
				$backupfile = $orig_backupfile . '.' . $c;
				$c++;
			}

			IEM::sessionSet('BackupFile', $backupfile);

			$tables_todo = $this->FetchTables();
			IEM::sessionSet('DatabaseTables_Todo', $tables_todo);

			$tables_done = array();
			IEM::sessionSet('DatabaseTables_Done', $tables_done);

			$backup_errors = array();
			IEM::sessionSet('DatabaseTables_BackupErrors', $backup_errors);
		}

		$tables_todo = IEM::sessionGet('DatabaseTables_Todo');
		$tables_done = IEM::sessionGet('DatabaseTables_Done');
		$backup_errors = IEM::sessionGet('DatabaseTables_BackupErrors');

		if ($tables_done == $tables_todo) {
			?>
				<script>
					self.parent.parent.location = 'index.php?Page=Upgrade&Step=2';
				</script>
			<?php
			return;
		}
		$tableProcessed = count($tables_done);
		$tableTotal = count($tables_todo);
		$percentProcessed = 0;

		foreach ($tables_todo as $p => $table) {
			if (in_array($table, $tables_done)) {
				continue;
			}

			$percentProcessed = ceil(($tableProcessed / $tableTotal)*100);
			echo "<script>\n";
			echo sprintf("self.parent.UpdateStatusReport('%s');", "Tables backed-up: {$tableProcessed}/{$tableTotal}");
			echo sprintf("self.parent.UpdateStatus('%s', %d);", "Backing up table \\'{$table}\\' ...", $percentProcessed);
			echo "</script>\n";
			flush();

			echo "Backing up table '" . $table . "'..<br/>\n";

			$this->PrintFooter(true);

			$result = $this->BackupTable($table, $backupfile);
			if (!$result) {
				$backup_errors[] = $table;
			}
			$tables_done[] = $table;
			$tableProcessed++;
			break;
		}
		IEM::sessionSet('DatabaseTables_Done', $tables_done);
		IEM::sessionSet('DatabaseTables_BackupErrors', $backup_errors);
		?>
			<script>
				setTimeout('window.location="index.php?Page=Upgrade&Action=CreateBackup"', 1);
			</script>
		<?php
	}

	/**
	* BackupTable
	* Since 2004 -> NX can only be a mysql upgrade, we'll just use native functions.
	*/
	function BackupTable($tablename='', $filename=''){
		if ($tablename == '' || $filename == '') {
			return false;
		}
		if (!$fp = fopen($filename, 'a+')) {
			return false;
		}

		$drop_table = "DROP TABLE IF EXISTS " . $tablename . ";\n";
		fputs($fp, $drop_table);

		$qry = "SHOW CREATE TABLE " . $tablename;
		$result = mysql_query($qry);
		$create_table = mysql_result($result, 0, 1) . ";\n";

		fputs($fp, $create_table);

		$qry = "SELECT * FROM " . $tablename;
		$result = mysql_query($qry);
		while ($row = mysql_fetch_assoc($result)) {
			$insert_query_fields = $insert_query_values = array();
			foreach ($row as $name => $val) {
				$insert_query_fields[] = $name;
				$insert_query_values[] = str_replace("'", "\'", stripslashes($val));
			}
			$insert_query = "INSERT INTO " . $tablename . "(" . implode(',', $insert_query_fields) . ") VALUES ('" . implode("','", $insert_query_values) . "');\n";
			fputs($fp, $insert_query);
		}

		$empty_lines = "\n";
		fputs($fp, $empty_lines);

		fclose($fp);
		return true;
	}

	/**
	* FetchTables
	* Since 2004 -> NX can only be a mysql upgrade, we'll just use native functions.
	*/
	function FetchTables(){
		$qry = "SHOW TABLES LIKE '" . addslashes($GLOBALS['TABLEPREFIX']) . "%'";
		$result = mysql_query($qry);
		$return = array();
		while ($row = mysql_fetch_assoc($result)) {
			$return[] = array_pop($row);
		}
		return $return;
	}

	/**
	 * CheckWritable
	 * @param String $file File to be checked
	 * @return Boolean Returns TRUE if file is writable, FALSE otherwise
	 */
	function CheckWritable($file=''){
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
	 * AlreadyUpgraded
	 * @return Boolean Returns TRUE if application has already been installed, FALSE othwerwise
	 */
	function AlreadyUpgraded(){
		define('SENDSTUDIO_DATABASE_TYPE', 'mysql');
		define('SENDSTUDIO_DATABASE_HOST', $GLOBALS['DBHOST']);
		define('SENDSTUDIO_DATABASE_USER', $GLOBALS['DBUSER']);
		define('SENDSTUDIO_DATABASE_PASS', $GLOBALS['DBPASS']);
		define('SENDSTUDIO_DATABASE_NAME', $GLOBALS['DBNAME']);
		define('SENDSTUDIO_TABLEPREFIX', $GLOBALS['TABLEPREFIX']);

		require_once(SENDSTUDIO_API_DIRECTORY . '/upgrade.php');

		$upgrade_api = new Upgrade_API();

		$query = "SELECT COUNT(*) AS subcount FROM " . $GLOBALS['TABLEPREFIX'] . "users";
		$result = $upgrade_api->Db->Query($query, true);
		// the table already exists?! That's bad.
		if ($result) {
			$count = $upgrade_api->Db->FetchOne($result, 'subcount');
			if ($count > 0) {
				?>
					<table cellspacing="0" cellpadding="0" width="95%" align="center">
						<tr>
							<td class="Heading1">Step 1: Problem Upgrading</TD>
						</tr>
						<tr>
							<td class="Text">
								<br/>
								<table class="Panel">
									<tr>
										<td class='Message' width='20' valign='top'>
											<img src='images/error.gif' width='18' height='18' hspace='10' vspace='5'>
										</td>
										<td class='Message' width='100%'>
											SendStudio NX seems to be installed in this database already. If you are upgrading from a previous version of SendStudio NX, please restore the admin/includes/config.php file from your backup.
										</td>
									</tr>
								</table>
								<br/>
							</td>
						</tr>
					</table>
				<?php
				return true;
			}
		}
		return false;
	}
}
