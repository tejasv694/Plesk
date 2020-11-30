<?php
/**
* This file has the settings page in it.
*
* @version     $Id: settings.php,v 1.86 2008/03/05 04:49:32 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for the settings page. This simply prints out and handles processing. The API does all of the work.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Settings extends SendStudio_Functions
{

	/**
	* PopupWindows
	* An array of popup windows used in this class. Used to work out what sort of header and footer to print.
	*
	* @see Process
	*
	* @var Array
	*/
	var $PopupWindows = array('sendpreviewdisplay', 'sendsmtppreviewdisplay', 'testbouncedisplay', 'showinfo', 'testbouncesettings');

	/**
	* Suppress Header and Footer for these actions
	*
	* @see Process
	*
	* @var Array
	*/
	var $SuppressHeaderFooter = array('sendpreview', 'testbouncesettings');

	/**
	* Constructor
	* Loads the language file.
	*
	* @see LoadLanguageFile
	*
	* @return Void Doesn't return anything.
	*/
	function Settings()
	{
		$this->LoadLanguageFile();
		$this->LoadLanguageFile('CharacterSets');
		$this->LoadLanguageFile('TimeZones');
		$this->LoadLanguageFile('Lists');
	}

	/**
	* Process
	* Does all the work.
	* Saves settings, Checks details, calls the API to save the actual settings and checks whether it worked or not.
	*
	* @see GetApi
	* @see API::Set
	* @see API::Save
	* @see GetLang
	* @see ParseTemplate
	* @see SendStudio_Functions::Process
	* @see SendTestPreview
	* @see Settings_API::CheckCron
	* @see Settings_API::UpdateCron
	*
	* @return Void Does all of the processing, doesn't return anything.
	*/
	function Process()
	{
		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;

		$user = GetUser();
		$access = $user->HasAccess('System', 'System');

		$popup = (in_array($action, $this->PopupWindows)) ? true : false;

		if (!$access) {
			$this->DenyAccess();
			return;
		}

		$LK = false;

		switch ($action) {
			case 'addons':

				// we need a subaction & addon name.
				if (!isset($_GET['SubAction'])) {
					return $this->ShowSettingsPage();
				}

				if (!isset($_GET['Addon'])) {
					return $this->ShowSettingsPage();
				}

				require_once(SENDSTUDIO_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR . 'interspire_addons.php');

				$post = array();
				if (!empty($_POST)) {
					$post = $_POST;
				}

				try {
					$allowed_sub_action = array('install', 'uninstall', 'enable', 'disable', 'upgrade', 'configure', 'savesettings');
					$subaction = $this->_getGETRequest('SubAction', '');

					if (!in_array(strtolower($subaction), $allowed_sub_action)) {
						FlashMessage(GetLang('Addon_Action_NotAllowed'), SS_FLASH_MSG_ERROR, 'index.php?Page=Settings&Tab=6');
						return;
					}

					$result = Interspire_Addons::Process($_GET['Addon'], $subaction, $post);
					if ($result === true) {
						FlashMessage(GetLang('Addon_Success_' . strtolower($_GET['SubAction'])), SS_FLASH_MSG_SUCCESS, 'index.php?Page=Settings&Tab=6');
						return;
					}
					if ($result === false || $result == null) {
						FlashMessage(GetLang('Addon_Failure_' . strtolower($_GET['SubAction'])), SS_FLASH_MSG_ERROR, 'index.php?Page=Settings&Tab=6');
						return;
					}
					echo $result;
				} catch (Exception $e) {
					$error = $e->GetMessage();
					FlashMessage($error, SS_FLASH_MSG_ERROR, 'index.php?Page=Settings&Tab=6');
				}
				return;
			break;

			case 'viewdisabled':
				$this->PrintHeader(true);
				$reporttype = (isset($_GET['Report'])) ? $_GET['Report'] : null;
				switch ($reporttype) {
					case 'autoresponder':
						$GLOBALS['Heading'] = GetLang('Autoresponders_Disabled_Heading');
						$GLOBALS['Intro'] = GetLang('Autoresponders_Disabled_Heading_Intro');

						$disabled_list = IEM::sessionGet('AutorespondersDisabled');

						$disabled_report = '';
						$var = GetLang('DisabledAutoresponder_Item');
						foreach ($disabled_list as $p => $details) {
							$disabled_report .= sprintf($var, $details['autorespondername'], $details['listname']) . "\n";
						}
					break;

					case 'newsletter':
						$GLOBALS['Heading'] = GetLang('Newsletters_Disabled_Heading');
						$GLOBALS['Intro'] = GetLang('Newsletters_Disabled_Heading_Intro');

						$disabled_list = IEM::sessionGet('NewslettersDisabled');

						$disabled_report = '';
						$var = GetLang('DisabledNewsletter_Item');
						foreach ($disabled_list as $p => $details) {
							$disabled_report .= sprintf($var, $details['newslettername']) . "\n";
						}
					break;
				}
				$GLOBALS['DisabledList'] = $disabled_report;
				$this->ParseTemplate('Settings_Disabled_Report');
				$this->PrintFooter(true);
			break;

			case 'systeminfo':
				$this->PrintHeader();
				$db = IEM::getDatabase();
				$GLOBALS['DatabaseVersion'] = $db->FetchOne('SELECT version() AS version');
				if(class_exists("DOMDocument", false)){$GLOBALS['DOMEnabled'] = GetLang('Yes'); } else {$GLOBALS['DOMEnabled'] = GetLang('No');}
				$GLOBALS['ProductVersion'] = GetLang('SENDSTUDIO_VERSION');
				$GLOBALS['ShowProd'] = empty($GLOBALS['ProductEdition']) ? 'none' : '';
				$charset = (isset($SENDSTUDIO_DEFAULTCHARSET)) ? $SENDSTUDIO_DEFAULTCHARSET : SENDSTUDIO_CHARSET;
				$GLOBALS['DefaultCharset'] = $charset;
				$GLOBALS['CharsetDescription'] = GetLang($charset);
				$GLOBALS['ServerTimeZone'] = SENDSTUDIO_SERVERTIMEZONE;
				$GLOBALS['ServerTimeZoneDescription'] = GetLang(SENDSTUDIO_SERVERTIMEZONE);
				$GLOBALS['ServerTime'] = date('r');
				$GLOBALS['PHPVersion'] = phpversion();
				$GLOBALS['ServerSoftware'] = htmlspecialchars($_SERVER["SERVER_SOFTWARE"], ENT_QUOTES, SENDSTUDIO_CHARSET);

				$GLOBALS['SafeModeEnabled'] = (SENDSTUDIO_SAFE_MODE) ? GetLang('Yes') : GetLang('No');

				$GLOBALS['ImapSupportFound'] = (function_exists('imap_open')) ? GetLang('Yes') : GetLang('No');

				$GLOBALS['CurlSupportFound'] = (function_exists('curl_init')) ? GetLang('Yes') : GetLang('No');

				$php_mods = $this->ParsePHPModules();

				$GLOBALS['GDVersion'] = GetLang('GD_NotDetected');
				if (Settings_API::GDEnabled() && $php_mods !== false) {
					$GLOBALS['GDVersion'] = $php_mods['gd']['GD Version'];
				}

				$GLOBALS['ModSecurity'] = GetLang('ModSecurity_Unknown');

				if (!is_numeric(strpos(php_sapi_name(), 'cgi')) && $php_mods !== false) {
					$apache_mods = $this->ParseApacheModules($php_mods);
					if (in_array('mod_security', $apache_mods)) {
						$GLOBALS['ModSecurity'] = GetLang('Yes');
					} else {
						$GLOBALS['ModSecurity'] = GetLang('No');
					}
				}
				$this->ParseTemplate('Settings_SystemInfo');
				$this->PrintFooter();
			break;

			case 'showinfo':
				$this->PrintHeader(true);
				phpinfo();
				$this->PrintFooter(true);
			break;

			case 'sendpreviewdisplay':
				$this->PrintHeader($popup);
				$this->SendTestPreviewDisplay('index.php?Page=Settings&Action=SendPreview', 'self.parent.getPreviewParameters()');
				$this->PrintFooter($popup);
			break;

			case 'sendsmtppreviewdisplay':
				$this->PrintHeader($popup);
				$this->SendTestPreviewDisplay('index.php?Page=Settings&Action=SendPreview', 'self.parent.getSMTPPreviewParameters()');
				$this->PrintFooter($popup);
			break;

			case 'sendpreview':
				$this->SendTestPreview();
			break;

			case 'testbouncedisplay':
				$this->PrintHeader($popup);
				$this->TestBounceSettingsDisplay();
				$this->PrintFooter($popup);
			break;

			case 'testbouncesettings':
				$this->TestBounceSettings();
			break;

			case 'save':
				if (empty($_POST)) {
					$this->ShowSettingsPage();
					break;
				}
				$api = $this->GetApi();
				$result = false;

				$errors = array();

				// Make sure that Contact email is filled in
				if (!isset($_POST['email_address']) || trim($_POST['email_address']) == '') {
					array_push($errors, GetLang('ErrorAlertMessage_BlankContactEmail'));
				}

				// Make sure that license key is filled in
				if (!isset($_POST['licensekey']) || trim($_POST['licensekey']) == '') {
					array_push($errors, GetLang('ErrorAlertMessage_BlankLicenseKey'));
				}

				// Make sure that application name is filled in
				if (!isset($_POST['lng_applicationtitle']) || trim($_POST['lng_applicationtitle']) == '') {
					array_push($errors, GetLang('ErrorAlertMessage_BlankApplicationName'));
				}

				$agencyId = get_agency_license_variables();
				if(!empty($agencyId['agencyid'])) {
					$temp = IEM::requestGetPOST('lng_accountupgrademessage', '', 'trim');
					if (empty($temp)) {
						array_push($errors, GetLang('ErrorAlertMessage_BlankAccountUpgradeMessage'));
					}

					$temp = IEM::requestGetPOST('lng_freetrial_expiry_login', '', 'trim');
					if (empty($temp)) {
						array_push($errors, GetLang('ErrorAlertMessage_BlankExpiredLogin'));
					}
				}

				if ($api && count($errors) == 0) {
					do {
						$settings = array();

						// fix up the database settings first.
						$all_areas = $api->Areas;

						$LK = (isset($_POST['licensekey'])) ? $_POST['licensekey'] : false;

						if (defined('APPLICATION_SHOW_WHITELABEL_MENU') && constant('APPLICATION_SHOW_WHITELABEL_MENU')) {
							foreach ($all_areas['whitelabel'] as $area) {
								$val = IEM::requestGetPOST(strtolower($area), false);

								$temp = strtolower($area);
								switch ($temp) {
									// Special case for handling logo image
									case 'application_logo_image':
										$val = IEM::requestGetPOST('existing_app_logo_image', false);

										if (isset($_FILES['Application_Logo_Image']) && !empty($_FILES['Application_Logo_Image']['name'])) {
											if ($_FILES['Application_Logo_Image']['error'] != 0 || !@is_uploaded_file($_FILES['Application_Logo_Image']['tmp_name'])) {
												array_push($errors, GetLang('ErrorAlertMessage_ErrorApplicationLogoImage'));
												break 3;
											}

											if (!$this->IsImageFile(strtolower($_FILES['Application_Logo_Image']['name']))){
												array_push($errors, GetLang('ErrorAlertMessage_InvalidNameApplicationLogoImage'));
												break 3;
											}

											$uploadedFile = strtolower(basename($_FILES['Application_Logo_Image']['name']));
											$uploadedFile = preg_replace('/.*(\..*)$/', 'applicationlogo${1}', $uploadedFile);

											if(move_uploaded_file($_FILES['Application_Logo_Image']['tmp_name'], (TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile))) {
												@chmod(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile, 0666);
												$val = 'temp/' . $uploadedFile;
											}

											if (!$this->IsValidImageFile(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile, $_FILES['Application_Logo_Image']['type'])){
												@unlink(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile);
												array_push($errors, GetLang('ErrorAlertMessage_InvalidFormatApplicationLogoImage'));
												break 3;
											}
										}
									break;

									// Special case for handling favicon
									case 'application_favicon':
										$val = IEM::requestGetPOST('existing_app_favicon', false);

										if (isset($_FILES['Application_Favicon']) && !empty($_FILES['Application_Favicon']['name'])) {
											if ($_FILES['Application_Favicon']['error'] != 0 || !@is_uploaded_file($_FILES['Application_Favicon']['tmp_name'])) {
												array_push($errors, GetLang('ErrorAlertMessage_ErrorApplicationFavicon'));
												break 3;
											}

											if (!$this->IsIconFile(strtolower($_FILES['Application_Favicon']['name']))){
												array_push($errors, GetLang('ErrorAlertMessage_InvalidNameApplicationFavicon'));
												break 3;
											}

											$uploadedFile = 'favicon.ico';

											if(move_uploaded_file($_FILES['Application_Favicon']['tmp_name'], (TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile))) {
												@chmod(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile, 0666);
												$val = 'temp/' . $uploadedFile;
											}

											if (!$this->IsValidIconFile(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile, $_FILES['Application_Favicon']['type'])){
												@unlink(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $uploadedFile);
												array_push($errors, GetLang('ErrorAlertMessage_InvalidFormatApplicationFavicon'));
												break 3;
											}
										}
									break;
								}
								$settings[$area] = $val;
							}
						}

						foreach ($all_areas['config'] as $area) {

							if (isset($_POST[strtolower($area)])) {
								$val = $_POST[strtolower($area)];
							} else {
								$val = false;
							}

							if ($area == 'DATABASE_USER') {
								if (isset($_POST['database_u'])) {
									$val = $_POST['database_u'];
								}
							}

							if ($area == 'DATABASE_PASS') {
								if (isset($_POST['database_p'])) {
									$val = $_POST['database_p'];
								}
							}

							if ($area == 'APPLICATION_URL') {
								if (substr($val, -1) == '/') {
									$val = substr($val, 0, -1);
								}
							}
							$settings[$area] = $val;
						}

						unset($all_areas['config']);
						unset($all_areas['whitelabel']);

						// look after all of the other settings now.
						foreach ($all_areas as $p => $area) {
							if (isset($_POST[strtolower($area)])) {
								$val = $_POST[strtolower($area)];
							} else {
								$val = false;
							}

							if ($area == 'BOUNCE_AGREEDELETE' && isset($_POST['bounce_process'])) {
								$val = 1;
							}

							if ($area == 'TEXTFOOTER') {
								$val = strip_tags($val);
							}

							if ($area == 'SMTP_USERNAME') {
								if (isset($_POST['smtp_u'])) {
									$val = $_POST['smtp_u'];
								}
							}

							if ($area == 'SMTP_PASSWORD') {
								if (isset($_POST['smtp_p'])) {
									$val = $_POST['smtp_p'];
								}
								$val = base64_encode($val);
							}

							if ($area == 'BOUNCE_PASSWORD') {
								if (isset($_POST['bounce_password'])) {
									$val = $_POST['bounce_password'];
								}
								$val = base64_encode($val);
							}

							$settings[$area] = $val;
						}

						// ----- Settings that cannot be changed
							$settings['DEFAULTCHARSET'] = SENDSTUDIO_DEFAULTCHARSET;
							if (!empty($settings['DEFAULTCHARSET'])) {
								$settings['DEFAULTCHARSET'] = 'UTF-8';
							}
						// -----

						// ----- Security settings
							$settings['SECURITY_WRONG_LOGIN_WAIT'] = intval($settings['SECURITY_WRONG_LOGIN_WAIT']);
							$settings['SECURITY_WRONG_LOGIN_THRESHOLD_COUNT'] = intval($settings['SECURITY_WRONG_LOGIN_THRESHOLD_COUNT']);
							$settings['SECURITY_WRONG_LOGIN_THRESHOLD_DURATION'] = intval($settings['SECURITY_WRONG_LOGIN_THRESHOLD_DURATION']) * 60;
							$settings['SECURITY_BAN_DURATION'] = intval($settings['SECURITY_BAN_DURATION']) * 60;

							if (!isset($_POST['security_wrong_login_wait_enable'])) {
								$settings['SECURITY_WRONG_LOGIN_WAIT'] = 0;
							}

							if (!isset($_POST['security_wrong_login_threshold_enable'])) {
								$settings['SECURITY_WRONG_LOGIN_THRESHOLD_COUNT'] = 0;
							}
						// -----

						$api->Set('Settings', $settings);

						$result = $api->Save();

						// Save warnings
						if ($result) {
							$tempRequestWarningsEnabled = IEM::requestGetPOST('credit_percentage_warnings_enable', array());
							$tempRequestWarningLevels = IEM::requestGetPOST('credit_percentage_warnings_level', array());
							$tempRequestWarnigSubjects = IEM::requestGetPOST('credit_percentage_warnings_subject', array());
							$tempRequestWarningEmails = IEM::requestGetPOST('credit_percentage_warnings_text', array());

							if (!empty($tempRequestWarningsEnabled) && !empty($tempRequestWarningLevels) && !empty($tempRequestWarningEmails)) {
								$tempRecords = array();
								foreach ($tempRequestWarningLevels as $index => $level) {
									$tempRecords[] = array(
										'enabled' => in_array($index, $tempRequestWarningsEnabled),
										'creditlevel' => $level,
										'aspercentage' => '1', // FIXME at this stage, only monthly credits warnings are available
										'emailsubject' => (isset($tempRequestWarnigSubjects[$index]) ? $tempRequestWarnigSubjects[$index] : ''),
										'emailcontents' => (isset($tempRequestWarningEmails[$index]) ? $tempRequestWarningEmails[$index] : '')
									);
								}

								$result = $api->SaveCreditWarnings($tempRecords);
							} else {
								$result = $api->SaveCreditWarnings(array());
							}

							unset($tempRequestWarningsEnabled);
							unset($tempRequestWarningLevels);
							unset($tempRequestWarningEmails);
						}
					} while(false);
				}

				$tabNum = ($_POST['tab_num'] && intval($_POST['tab_num'])) ? intval($_POST['tab_num']) : 1 ;

				if ($result) {
					FlashMessage(GetLang('SettingsSaved'), SS_FLASH_MSG_SUCCESS, 'index.php?Page=Settings&Tab='.$tabNum);
				} else {
					foreach ($errors as $error) {
						FlashMessage($error, SS_FLASH_MSG_ERROR);
					}

					FlashMessage(GetLang('SettingsNotSaved'), SS_FLASH_MSG_ERROR, 'index.php?Page=Settings&Tab='.$tabNum);
				}
			break;

			default:
				$this->ShowSettingsPage();
			break;
		}
	}

	/**
	 * ShowSettingsPage
	 * Prints out the settings page and pre-fills the form fields as much as it can.
	 *
	 * It will also show:
	 * - if there is a license key issue
	 * - if there is a problem with cron/scheduled tasks set up not working properly
	 * - if 'test mode' is enabled
	 * - if you disable attachments, it checks autoresponders to see if any need to be disabled
	 *
	 * @return Void Prints out the settings form, doesn't return anything.
	 */
	function ShowSettingsPage()
	{
		require_once(SENDSTUDIO_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR . 'interspire_addons.php');
		$addonSystem = new Interspire_Addons();
		$addonStatus = $addonSystem->GetAvailableAddons();

		$this->PrintHeader();

		$tpl = GetTemplateSystem();

		$GLOBALS['Message'] = '';

		list($license_error, $msg) = sesion_start();

		$extra = '';

		if ($license_error) {
			$GLOBALS['Error'] = $msg;
			$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
			$extra = '
				<script>
					$(function() {
						$("licensekey").select();
						$("licensekey").focus();
					});
				</script>
			';
			unset($GLOBALS['Error']);
		}

		if (!is_writable(SENDSTUDIO_INCLUDES_DIRECTORY . '/config.php')) {
			FlashMessage(sprintf(GetLang('ConfigFileNotWritable'), SENDSTUDIO_INCLUDES_DIRECTORY . '/config.php'), SS_FLASH_MSG_WARNING);
		}

		$api = $this->GetApi();
		$api->Load();

		$all_areas = $api->Areas;

		foreach ($all_areas['config'] as $k => $option) {
			$opt_name = 'SENDSTUDIO_' . $option;
			${$opt_name} = constant($opt_name);
		}

		unset($all_areas['config']);
		unset($all_areas['whitelabel']);

		foreach ($all_areas as $k => $option) {
			$opt_name = 'SENDSTUDIO_' . $option;
			${$opt_name} = constant($opt_name);
		}

		if (isset($SENDSTUDIO_FORCE_UNSUBLINK) && $SENDSTUDIO_FORCE_UNSUBLINK == 1) {
			$SENDSTUDIO_FORCE_UNSUBLINK = ' CHECKED';
		}

		if (!isset($SENDSTUDIO_FORCE_UNSUBLINK)) {
			$SENDSTUDIO_FORCE_UNSUBLINK = '';
		}

		$cron_checked = false;
		if (isset($SENDSTUDIO_CRON_ENABLED) && $SENDSTUDIO_CRON_ENABLED == 1) {
			$SENDSTUDIO_CRON_ENABLED = ' CHECKED';
			$cron_checked = true;
		}

		if (!isset($SENDSTUDIO_CRON_ENABLED)) {
			$SENDSTUDIO_CRON_ENABLED = '';
		}

		$GLOBALS['Cron_ShowInfo'] = 'none';
		if ($cron_checked) {
			$GLOBALS['Cron_ShowInfo'] = '';
		}

		$ip_tracking = false;
		if (isset($SENDSTUDIO_IPTRACKING) && $SENDSTUDIO_IPTRACKING == 1) {
			$SENDSTUDIO_IPTRACKING = ' CHECKED';
			$ip_tracking = true;
		}

		if (!isset($SENDSTUDIO_IPTRACKING)) {
			$SENDSTUDIO_IPTRACKING = '';
		}

		if (isset($SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE) && $SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE == 1) {
			$SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE = ' CHECKED';
		}

		if (!isset($SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE)) {
			$SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE = '';
		}

		if (isset($SENDSTUDIO_CONTACTCANMODIFYEMAIL) && $SENDSTUDIO_CONTACTCANMODIFYEMAIL == 1) {
			$SENDSTUDIO_CONTACTCANMODIFYEMAIL = ' CHECKED';
		}

		if (!isset($SENDSTUDIO_CONTACTCANMODIFYEMAIL)) {
			$SENDSTUDIO_CONTACTCANMODIFYEMAIL = '';
		}

		$send_test_mode = false;
		if (isset($SENDSTUDIO_SEND_TEST_MODE) && $SENDSTUDIO_SEND_TEST_MODE == 1) {
			$SENDSTUDIO_SEND_TEST_MODE = ' CHECKED';
			$send_test_mode = true;
		}

		if (!isset($SENDSTUDIO_SEND_TEST_MODE) && SENDSTUDIO_SEND_TEST_MODE == 1) {
			$SENDSTUDIO_SEND_TEST_MODE = ' CHECKED';
			$send_test_mode = true;
		}

		if (!isset($SENDSTUDIO_SEND_TEST_MODE)) {
			$SENDSTUDIO_SEND_TEST_MODE = '';
		}

		if ($SENDSTUDIO_SMTP_SERVER) {
			$GLOBALS['UseSMTP'] = ' CHECKED';
			$GLOBALS['DisplaySMTP'] = "'';";
		} else {
			$GLOBALS['UseDefaultMail'] = ' CHECKED';
			$GLOBALS['DisplaySMTP'] = 'none';
		}

		$GLOBALS['ShowCronInfo'] = 'none';
		$GLOBALS['CronRunTime'] = GetLang('CronRunTime_Never');
		$GLOBALS['CronRunTime_Explain'] = GetLang('CronRunTime_Explain');
		if ($SENDSTUDIO_CRON_ENABLED) {
			$GLOBALS['ShowCronInfo'] = '';
			$cron_ok = $api->Get('cronok');
			if ($cron_ok) {
				$cron_1 = $api->Get('cronrun1');
				$cron_2 = $api->Get('cronrun2');
				if (!$cron_1) {
					$GLOBALS['CronRunTime'] = GetLang('CronRunTime_Once');
				} else {
					$diff = $this->TimeDifference($cron_2 - $cron_1);
					$GLOBALS['CronRunTime'] = sprintf(GetLang('CronRunTime_Difference'), $diff);
				}
			}
		}

		$GLOBALS['Settings_CronOptionsList'] = '';

		/**
		 * The schedule stuff is a little different and comes from the database.
		 * Look at all of the options in the schedule
		 * as addons can defined their own schedules if they need to.
		 */
		$cron_schedule = $api->Get('Schedule');
		$cron_options = array_keys($cron_schedule);

		foreach ($cron_options as $p => $cron_option) {
			if ($cron_option == 'triggeremails_p') {
				$GLOBALS['Settings_CronOptionsList'] .= '<tr style="display:none;"><td><input type="hidden" name="cron_triggeremails_p" value="' . SENDSTUDIO_CRON_TRIGGEREMAILS_P . '" /></td></tr>';
				continue;
			}

			$opt_name = 'Cron_Options';
			$GLOBALS[$opt_name] = '';
			$settings_var = ${'SENDSTUDIO_CRON_' . strtoupper($cron_option)};

			$GLOBALS['Cron_Option_SelectName'] = 'cron_' . $cron_option;

			$GLOBALS['Cron_Option_Heading'] = GetLang('Cron_Option_'.$cron_option.'_Heading');


			foreach ($api->Get($cron_option . '_options') as $opt => $desc) {
				$selected = '';
				if ($opt == $settings_var) {
					$selected = ' SELECTED';
				}
				$GLOBALS[$opt_name] .= '<option value="' . $opt . '"' . $selected . '>' . GetLang('Cron_Option_' . $desc) . '</option>';
			}

			$GLOBALS['Cron_LastRun'] = $GLOBALS['Cron_NextRun'] = '';

			$last_run = $cron_schedule[$cron_option]['lastrun'];
			if ($last_run > 0) {
				$GLOBALS['Cron_LastRun'] = IEM::timeGetUserDisplayString(GetLang('Cron_DateFormat'), $last_run);
				if ($settings_var > 0) {
					$next_run = $last_run + ($settings_var * 60);
					$GLOBALS['Cron_NextRun'] = IEM::timeGetUserDisplayString(GetLang('Cron_DateFormat'), $next_run);
				} else {
					$GLOBALS['Cron_NextRun'] = GetLang('Cron_Option_Disabled');
				}
			} else {
				$GLOBALS['Cron_LastRun'] = GetLang('Cron_Option_HasNotRun');
			}

			// Skipping any problematic CRON schedule
			if (!defined('LNG_Cron_Option_'.$cron_option.'_Heading')) {
				continue;
			}

			$GLOBALS['Cron_Option_Heading'] = GetLang('Cron_Option_'.$cron_option.'_Heading');
			$GLOBALS['Settings_CronOptionsList'] .= $this->ParseTemplate('Settings_Cron_Option', true, false);
		}

		$GLOBALS['Imap_Selected'] = $GLOBALS['Pop3_Selected'] = '';
		if (!isset($SENDSTUDIO_BOUNCE_IMAP) && SENDSTUDIO_BOUNCE_IMAP == 1) {
			$GLOBALS['Imap_Selected'] = ' SELECTED ';
		} else {
			$GLOBALS['Pop3_Selected'] = ' SELECTED ';
		}

		if (isset($SENDSTUDIO_BOUNCE_IMAP) && $SENDSTUDIO_BOUNCE_IMAP == 1) {
			$GLOBALS['Imap_Selected'] = ' SELECTED ';
		} else {
			$GLOBALS['Pop3_Selected'] = ' SELECTED ';
		}

		if (!isset($SENDSTUDIO_BOUNCE_AGREEDELETE) && SENDSTUDIO_BOUNCE_AGREEDELETE == 1) {
			$SENDSTUDIO_BOUNCE_AGREEDELETE = ' CHECKED';
		}

		if (isset($SENDSTUDIO_BOUNCE_AGREEDELETE) && $SENDSTUDIO_BOUNCE_AGREEDELETE == 1) {
			$SENDSTUDIO_BOUNCE_AGREEDELETE = ' CHECKED';
			$GLOBALS['ProcessBounceChecked'] = ' CHECKED';
		} else {
			$GLOBALS['DisplayExtraMailSettings'] = 'none';
		}

		if (!isset($SENDSTUDIO_BOUNCE_AGREEDELETEALL) && SENDSTUDIO_BOUNCE_AGREEDELETEALL == 1) {
			$SENDSTUDIO_BOUNCE_AGREEDELETEALL = ' CHECKED';
		}

		if (isset($SENDSTUDIO_BOUNCE_AGREEDELETEALL) && $SENDSTUDIO_BOUNCE_AGREEDELETEALL == 1) {
			$SENDSTUDIO_BOUNCE_AGREEDELETEALL = ' CHECKED';
		}


		if ($SENDSTUDIO_BOUNCE_EXTRASETTINGS) {
			$GLOBALS['Bounce_ExtraSettings'] = ' CHECKED';
		} else {
			$GLOBALS['DisplayExtraMailSettings'] = 'none';
		}

		$allow_attachments = false;
		if (isset($SENDSTUDIO_ALLOW_ATTACHMENTS) && $SENDSTUDIO_ALLOW_ATTACHMENTS == 1) {
			$SENDSTUDIO_ALLOW_ATTACHMENTS = ' CHECKED';
			$allow_attachments = true;
		}
		if (!isset($SENDSTUDIO_ALLOW_ATTACHMENTS) && SENDSTUDIO_ALLOW_ATTACHMENTS == 1) {
			$SENDSTUDIO_ALLOW_ATTACHMENTS = ' CHECKED';
			$allow_attachments = true;
		}
		if (!isset($SENDSTUDIO_ALLOW_ATTACHMENTS)) {
			$SENDSTUDIO_ALLOW_ATTACHMENTS = '';
		}

		$GLOBALS['ShowAttachmentSize'] = 'none';
		if ($allow_attachments) {
			$GLOBALS['ShowAttachmentSize'] = "'';";
		}

		$embedded_images = false;
		if (isset($SENDSTUDIO_ALLOW_EMBEDIMAGES) && $SENDSTUDIO_ALLOW_EMBEDIMAGES == 1) {
			$embedded_images = true;
			$SENDSTUDIO_ALLOW_EMBEDIMAGES = ' CHECKED';
		}

		if (!isset($SENDSTUDIO_ALLOW_EMBEDIMAGES) && SENDSTUDIO_ALLOW_EMBEDIMAGES == 1) {
			$embedded_images = true;
			$SENDSTUDIO_ALLOW_EMBEDIMAGES = ' CHECKED';
		}

		if (!isset($SENDSTUDIO_ALLOW_EMBEDIMAGES)) {
			$SENDSTUDIO_ALLOW_EMBEDIMAGES = '';
		}

		// this option is hidden by the embedded_images check but we should remember the 'state' in case the admin turns off embedded images and then turns it back on.
		if (isset($SENDSTUDIO_DEFAULT_EMBEDIMAGES) && $SENDSTUDIO_DEFAULT_EMBEDIMAGES == 1) {
			$SENDSTUDIO_DEFAULT_EMBEDIMAGES = ' CHECKED';
		}

		if (!isset($SENDSTUDIO_DEFAULT_EMBEDIMAGES) && SENDSTUDIO_DEFAULT_EMBEDIMAGES == 1) {
			$SENDSTUDIO_DEFAULT_EMBEDIMAGES = ' CHECKED';
		}

		if (!isset($SENDSTUDIO_DEFAULT_EMBEDIMAGES)) {
			$SENDSTUDIO_DEFAULT_EMBEDIMAGES = '';
		}

		$GLOBALS['ShowDefaultEmbeddedImages'] = 'none';
		if ($embedded_images) {
			$GLOBALS['ShowDefaultEmbeddedImages'] = "'';";
		}

		/**
		* Now we have worked out the logic of what options are pre-filled,
		* we'll just set up the variables ready for the template system to use everything.
		*/

		$disabled_functions = explode(',', SENDSTUDIO_DISABLED_FUNCTIONS);
		$php_binary = 'php';
		if (substr(strtolower(PHP_OS), 0, 3) == 'win') {
			$php_binary = 'php.exe';
		}
		$php_path = $this->Which($php_binary);

		// If we can't find the full path, just print the binary so people get the right idea.
		if ($php_path == '') {
			$php_path = $php_binary;
		}
		$php_path .= ' -f ';

		$GLOBALS['CronPath'] = $php_path . SENDSTUDIO_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'cron.php';

		$GLOBALS['DatabaseType'] = $SENDSTUDIO_DATABASE_TYPE;
		$GLOBALS['DatabaseUser'] = $SENDSTUDIO_DATABASE_USER;
		$GLOBALS['DatabaseHost'] = $SENDSTUDIO_DATABASE_HOST;
		$GLOBALS['DatabasePass'] = $SENDSTUDIO_DATABASE_PASS;
		$GLOBALS['DatabaseName'] = $SENDSTUDIO_DATABASE_NAME;
		$GLOBALS['DatabaseTablePrefix'] = $SENDSTUDIO_TABLEPREFIX;
		$GLOBALS['ApplicationURL'] = $SENDSTUDIO_APPLICATION_URL;
		$GLOBALS['LicenseKey'] = $SENDSTUDIO_LICENSEKEY;
		$GLOBALS['DatabaseVersion'] = $api->Db->FetchOne('SELECT version() AS version');

		$GLOBALS['System_Message'] = htmlentities($SENDSTUDIO_SYSTEM_MESSAGE, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['TextFooter'] = strip_tags($SENDSTUDIO_TEXTFOOTER);
		$GLOBALS['HTMLFooter'] = $SENDSTUDIO_HTMLFOOTER;

		$GLOBALS['ForceUnsubLink'] = $SENDSTUDIO_FORCE_UNSUBLINK;

		$GLOBALS['CronEnabled'] = $SENDSTUDIO_CRON_ENABLED;

		$GLOBALS['IpTracking'] = $SENDSTUDIO_IPTRACKING;
		$GLOBALS['UseMultipleUnsubscribe'] = $SENDSTUDIO_USEMULTIPLEUNSUBSCRIBE;
		$GLOBALS['ContactCanModifyEmail'] = $SENDSTUDIO_CONTACTCANMODIFYEMAIL;

		$GLOBALS['SendTestMode'] = $SENDSTUDIO_SEND_TEST_MODE;

		$GLOBALS['MaxHourlyRate'] = $SENDSTUDIO_MAXHOURLYRATE;

		$GLOBALS['MaxOverSize'] = $SENDSTUDIO_MAXOVERSIZE;

		$GLOBALS['EmailAddress'] = htmlspecialchars($SENDSTUDIO_EMAIL_ADDRESS, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['MaxImageWidth'] = intval($SENDSTUDIO_MAX_IMAGEWIDTH);
		$GLOBALS['MaxImageHeight'] = intval($SENDSTUDIO_MAX_IMAGEHEIGHT);

		$GLOBALS['Smtp_Server'] = htmlspecialchars($SENDSTUDIO_SMTP_SERVER, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['Smtp_Username'] = htmlspecialchars($SENDSTUDIO_SMTP_USERNAME, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['Smtp_Password'] = base64_decode($SENDSTUDIO_SMTP_PASSWORD);
		$GLOBALS['Smtp_Port'] = $SENDSTUDIO_SMTP_PORT;

		$GLOBALS['Bounce_Address'] = htmlspecialchars($SENDSTUDIO_BOUNCE_ADDRESS, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['Bounce_Server'] = htmlspecialchars($SENDSTUDIO_BOUNCE_SERVER, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['Bounce_Username'] = htmlspecialchars($SENDSTUDIO_BOUNCE_USERNAME, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['Bounce_Password'] = base64_decode($SENDSTUDIO_BOUNCE_PASSWORD);
		$GLOBALS['Bounce_Imap'] = $SENDSTUDIO_BOUNCE_IMAP;
		$GLOBALS['Bounce_ExtraSettings'] = htmlspecialchars($SENDSTUDIO_BOUNCE_EXTRASETTINGS, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$GLOBALS['Bounce_AgreeDelete'] = $SENDSTUDIO_BOUNCE_AGREEDELETE;
		$GLOBALS['Bounce_AgreeDeleteAll'] = $SENDSTUDIO_BOUNCE_AGREEDELETEALL;

		$GLOBALS['AllowAttachments'] = $SENDSTUDIO_ALLOW_ATTACHMENTS;
		$GLOBALS['AllowEmbedImages'] = $SENDSTUDIO_ALLOW_EMBEDIMAGES;

		$GLOBALS['AttachmentSize'] = $SENDSTUDIO_ATTACHMENT_SIZE;

		$GLOBALS['EmailSize_Warning'] = $SENDSTUDIO_EMAILSIZE_WARNING;
		$GLOBALS['EmailSize_Maximum'] = $SENDSTUDIO_EMAILSIZE_MAXIMUM;

		$GLOBALS['Resend_Maximum'] = $SENDSTUDIO_RESEND_MAXIMUM;

		$GLOBALS['DefaultEmbedImages'] = $SENDSTUDIO_DEFAULT_EMBEDIMAGES;

		$GLOBALS['Copyright'] = htmlspecialchars(LNG_Copyright, ENT_QUOTES, SENDSTUDIO_CHARSET);

		$GLOBALS['Existing_App_Logo_Image'] = APPLICATION_LOGO_IMAGE;

		$GLOBALS['Existing_App_Favicon'] = APPLICATION_FAVICON;

	/* #*#*# DISABLED! FLIPMODE! #*#*#
		$GLOBALS['EnableUpdatesCheck'] = (isset($addonStatus['updatecheck']['enabled']) && $addonStatus['updatecheck']['enabled'] == '1') ? 'CHECKED' : '';
	#*#*# / / / / #*#*# */

		$GLOBALS['ShowIntroVideo'] = (SHOW_INTRO_VIDEO == true) ? 'CHECKED' : '';

		$GLOBALS['ShowSmtpComOption'] = (SHOW_SMTP_COM_OPTION == true) ? 'CHECKED' : '';

		$GLOBALS['ShowSmtpComOptionShow'] = (SHOW_SMTP_COM_OPTION == true) ? '' : 'none';



		$GLOBALS['FormAction'] = 'Action=Save';

		if (!$cron_checked) {
			$api->DisableCron();
		}

		if ($cron_checked) {
			$this->DisplayCronWarning(false);
		}

		$test_mode_report = '';
		if ($send_test_mode) {
			$jobs_api = $this->GetApi('Jobs');
			$job_found = $jobs_api->FindJob('send', 'newsletter', 0, true, false, false);
			if ($job_found) {
				$test_mode_report = $this->PrintWarning('Send_TestMode_JobsWaiting');
			}
		}

		$GLOBALS['Send_TestMode_Report'] = $test_mode_report;

		$attachments_report = '';

		if (!$allow_attachments) {
			$autos_to_disable = array();

			$auto_files = list_files(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'autoresponders', null, true);
			if (!empty($auto_files)) {
				$autoresponder_ids = array_keys($auto_files);
				foreach ($autoresponder_ids as $p => $autoresponderid) {
					$files = $auto_files[$autoresponderid];
					if (isset($files['attachments'])) {
						if (!empty($files['attachments'])) {
							$autos_to_disable[] = $autoresponderid;
						}
					}
				}
			}

			if (!empty($autos_to_disable)) {
				$auto_api = $this->GetApi('Autoresponders');
				$disabled_list = $auto_api->DisableAutoresponders($autos_to_disable);
				if (!empty($disabled_list)) {
					$amount = sizeof(array_keys($disabled_list));
					if ($amount == 1) {
						$attachments_report .= GetLang('Autoresponders_Disabled_Attachments_One_Link');
					} else {
						$attachments_report .= sprintf(GetLang('Autoresponders_Disabled_Attachments_Many_Link'), $this->FormatNumber($amount));
					}

					$email_api = $this->GetApi('Email');
					$email_api->Set('CharSet', SENDSTUDIO_CHARSET);

					foreach ($disabled_list as $p => $disabled_details) {
						$subject = GetLang('Autoresponders_Disabled_Email_Subject');

						$message = sprintf(GetLang('Autoresponders_Disabled_Email_Message'), $disabled_details['autorespondername'], $disabled_details['listname']);

						$email_api->ClearAttachments();
						$email_api->ClearRecipients();

						$email_api->Set('Multipart', false);
						$email_api->AddBody('text', $message);
						$email_api->Set('Subject', $subject);

						$email_api->Set('FromAddress', SENDSTUDIO_EMAIL_ADDRESS);

						$email_api->AddRecipient($disabled_details['owneremail'], $disabled_details['ownername'], 't');

						$email_api->Send();
					}
					IEM::sessionSet('AutorespondersDisabled', $disabled_list);
				}
			}
			/* ???? */
			$newsletters_to_disable = array();
            $db = IEM::getDatabase();
			
            $result = $db->Query("SELECT newsletterid FROM ".SENDSTUDIO_TABLEPREFIX."newsletters WHERE active != 0");
            $newsletter_ids = array();
            while($row = $db->Fetch($result)){
                $newsletter_ids[] = $row['newsletterid'];
            }
            foreach ($newsletter_ids as $value) {
                $dir = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'newsletters'. DIRECTORY_SEPARATOR . $value . DIRECTORY_SEPARATOR . 'attachments';
                if(is_dir($dir)){
                    $files = array();
                    $files = scandir($dir);
                    for($i = 0; $i <= count($files); $i++){
                        if($files[$i] == "." || $files[$i] == ".."){
                            unset($files[$i]);
                        }
                    }
					/* ???? */
                    if(count($files) > 0){$newsletters_to_disable[] = $value;}
				
                }
            }            

			if (!empty($newsletters_to_disable)) {
				if ($attachments_report != '') {
					$attachments_report .= '<br/><br/>';
				}

				$news_api = $this->GetApi('Newsletters');
				$disabled_list = $news_api->DisableNewsletters($newsletters_to_disable);
                $amount = sizeof(array_keys($disabled_list));
				if ($amount == 1) {
				/* ???? */
				    $news_api = $this->GetApi('Newsletters');

					$attachments_report .= GetLang('Newsletters_Disabled_Attachments_One_Link');
				} else {
					$attachments_report .= sprintf(GetLang('Newsletters_Disabled_Attachments_Many_Link'), $this->FormatNumber($amount));
				}
				IEM::sessionSet('NewslettersDisabled', $disabled_list);
			}
		}

		$GLOBALS['DisplayAttachmentsMessage'] = "none;";
		if ($attachments_report) {
			$GLOBALS['DisplayAttachmentsMessage'] = '';
			$GLOBALS['Warning'] = $attachments_report;

			$GLOBALS['Attachments_Message'] = $this->ParseTemplate('WarningMsg', true, false);
		}

		$GLOBALS['ExtraScript'] = $extra;

		$GLOBALS['Settings_AddonsDisplay'] = $this->PrintAddonsList();

		// ----- Credit settings
			$tempPercentageWarnings = array();
			$tempFixedWarnings = array(); // TODO fixed credit warnings aren't implemented yet
			$tempWarnings = $api->GetCreditWarningsSettings();

			// If warnings can't be found, create default.
			if (empty($tempWarnings)) {
				$tempDefaultLevel = array('0', '15', '25');
				$tempWarnings = array();

				foreach ($tempDefaultLevel as $each) {
					$tempPercentageWarnings[] = array(
						'enabled' => '0',
						'creditlevel' => $each,
						'aspercentage' => '1',
						'emailsubject' => GetLang('CreditWarnings_Warnings_EmailSubjectDefaultText'),
						'emailcontents' => str_replace('%s', "{$each}%", GetLang('CreditSettings_Warnings_PercentageDefaultText'))
					);
				}

				unset($tempDefaultLevel);

			// Split the warnings into two arrays (fixed and percentage warnings)
			} else {
				foreach ($tempWarnings as $each) {
					if ($each['aspercentage']) {
						$tempPercentageWarnings[] = $each;
					} else {
						$tempFixedWarnings[] = $each;
					}
				}
			}

			unset($tempWarnings);

			$tpl->Assign('credit_settings', array(
				'autoresponders_take_credit' => (bool)SENDSTUDIO_CREDIT_INCLUDE_AUTORESPONDERS,
				'triggers_take_credit' => (bool)SENDSTUDIO_CREDIT_INCLUDE_TRIGGERS,
				'enable_credit_level_warnings' => (bool)SENDSTUDIO_CREDIT_WARNINGS,
				'warnings_percentage_level' => $tempPercentageWarnings,
				'warnings_percentage_level_choices' => array(
					'0', '5', '10', '15', '20', '25',
					'30', '35', '40', '45', '50'
				)
			));
		// -----

		// ----- Login Security settings
			$security_settings = array(
				'login_wait' => SENDSTUDIO_SECURITY_WRONG_LOGIN_WAIT,
				'threshold_login_count' => SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_COUNT,
				'threshold_login_duration' => SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_DURATION / 60,
				'ip_login_ban_duration' => SENDSTUDIO_SECURITY_BAN_DURATION / 60
			);

			$security_settings_options = array(
				'login_wait' => array(1, 2, 3, 4, 5),
				'threshold_login_count' => array(3, 4, 5, 10, 15),
				'threshold_login_duration' => array(1, 5, 10, 15),
				'ip_login_ban_duration' => array(1, 5, 10, 15)
			);

			$tpl->Assign('security_settings', $security_settings);
			$tpl->Assign('security_settings_options', $security_settings_options);
		// -----

		$tpl->Assign('AgencyEdition', get_agency_license_variables());

		$showtab = 1;
		if (isset($_GET['Tab'])) {
			$tab = (int)$_GET['Tab'];
			if ($tab > 0) {
				$showtab = $tab;
			}
		}

		$GLOBALS['Message'] .= GetFlashMessages();

		$tpl->Assign('ShowTab', $showtab);
		$tpl->Assign('DisplayPrivateLabel', (defined('APPLICATION_SHOW_WHITELABEL_MENU')? constant('APPLICATION_SHOW_WHITELABEL_MENU') : true));

		$tpl->ParseTemplate('Settings');

		$this->PrintFooter();
	}

	/**
	* ParsePHPModules
	* Function to grab the list of PHP modules installed.
	*
	* @return Array An associative array of all the modules installed for PHP
	*/
	function ParsePHPModules()
	{
		ob_start();
		phpinfo(INFO_MODULES);
		$s = ob_get_contents();
		ob_end_clean();

		if (strstr($s,'phpinfo() has been disabled for security reasons')) {
			// phpinfo() is disabled
			return false;
		}

		$s = strip_tags($s,'<h2><th><td>');
		$s = preg_replace('/<th[^>]*>([^<]+)<\/th>/',"<info>\\1</info>",$s);
		$s = preg_replace('/<td[^>]*>([^<]+)<\/td>/',"<info>\\1</info>",$s);
		$vTmp = preg_split('/(<h2[^>]*>[^<]+<\/h2>)/',$s,-1,PREG_SPLIT_DELIM_CAPTURE);
		$vModules = array();
		for ($i=1;$i<count($vTmp);$i++) {
			if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/',$vTmp[$i],$vMat)) {
				$vName = trim($vMat[1]);
				$vTmp2 = explode("\n",$vTmp[$i+1]);
				foreach ($vTmp2 AS $vOne) {
					$vPat = '<info>([^<]+)<\/info>';
					$vPat3 = "/".$vPat."\s*".$vPat."\s*".$vPat."/";
					$vPat2 = "/".$vPat."\s*".$vPat."/";
					if (preg_match($vPat3,$vOne,$vMat)) { // 3cols
						$vModules[$vName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
					} elseif (preg_match($vPat2,$vOne,$vMat)) { // 2cols
						$vModules[$vName][trim($vMat[1])] = trim($vMat[2]);
					}
				}
			}
		}
		return $vModules;
	}

	/**
	* ParseApacheModules
	* Function to grab the list of Apache modules installed.
	* This is mainly used to check if mod-security is enabled or not.
	*
	* @param String $input The input string to parse to look for apache modules.
	*
	* @return Array An associative array of all the modules installed for Apache.
	*/
	function ParseApacheModules($input)
	{
		if (isset($input['apache'])) {
			$modules = $input['apache']['Loaded Modules'];
			$mod_list = explode(",",$modules);
			foreach ($mod_list as $key=>$value) {
				$mod_list[$key] = trim($value);
			}
			return $mod_list;
		}

		if (isset($input['apache2handler'])) {
			$modules = $input['apache2handler']['Loaded Modules'];
			$mod_list = explode(" ",$modules);
			foreach ($mod_list as $key=>$value) {
				$mod_list[$key] = trim($value);
			}
			return $mod_list;
		}

		//apache2handler
		return array();
	}

	/**
	 * TestBounceSettingsDisplay
	 * This sets session variables for showing the 'test bounce account' details
	 * It then passes control to the 'TestBounceSettings' method which actually tests the details.
	 *
	 * @see TestBounceSettings
	 *
	 * @return Void Doesn't return anything. Sets session variables then prints the window which actually tests the account details.
	 */
	function TestBounceSettingsDisplay()
	{
		$test_bounce_details = array (
			'server' => $_GET['Bounce_Server'],
			'username' => $_GET['Bounce_Username'],
			'password' => base64_encode($_GET['Bounce_Password']),
			'extra_settings' => $_GET['Bounce_ExtraSettings'],
			'imap' => (isset($_GET['bounce_imap']) && $_GET['bounce_imap'] == 1) ? 1 : 0,
		);
		IEM::sessionSet('TestBounceDetails', $test_bounce_details);

		$GLOBALS['Page'] = 'Settings';
		$this->LoadLanguageFile('Bounce');
		$this->ParseTemplate('Bounce_Test_Window');
	}

	/**
	 * TestBounceSettings
	 * This is the function which actually tests the bounce account details
	 * It then prints an appropriate message (success/failure and why if possible)
	 * It uses the session variables set by TestBounceSettingsDisplay.
	 *
	 * @uses TestBounceSettingsDisplay
	 *
	 * @return Void Prints out a success/failure message if an email account can be logged in to or not.
	 */
	function TestBounceSettings()
	{
		$this->LoadLanguageFile('Bounce');

		if (!function_exists('imap_open')) {
			$GLOBALS['Error'] = GetLang('Bounce_No_ImapSupport_Intro');
			$this->ParseTemplate('ErrorMsg');
			return;
		}

		$test_bounce_details = IEM::sessionGet('TestBounceDetails');

		if ($test_bounce_details === false || empty($test_bounce_details)) {
			$GLOBALS['Error'] = sprintf(GetLang('BadLogin_Details'), GetLang('BounceError_NoDetails'));
			$this->ParseTemplate('ErrorMsg');
			return;
		}

		$bounce_server = $test_bounce_details['server'];
		$bounce_user = $test_bounce_details['username'];
		$bounce_pass = $test_bounce_details['password'];

		$extra_settings = false;
		if ($test_bounce_details['extra_settings'] !== '') {
			$extra_settings = $test_bounce_details['extra_settings'];
		}

		$imap = ($test_bounce_details['imap'] === 1) ? true : false;

		$bounce_api = $this->GetApi('Bounce');

		$bounce_api->Set('bounceuser', $bounce_user);
		$bounce_api->Set('bouncepassword', $bounce_pass);
		$bounce_api->Set('bounceserver', $bounce_server);
		$bounce_api->Set('imapaccount', $imap);
		if ($extra_settings) {
			$bounce_api->Set('extramailsettings', $extra_settings);
		}

		$login_ok = $bounce_api->Login();
		if (!$login_ok) {
			$GLOBALS['Error'] = sprintf(GetLang('BadLogin_Details'), $bounce_api->Get('ErrorMessage'));
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		} else {
			$GLOBALS['Message'] = $this->PrintSuccess('BounceLogin_Successful');
		}
		$bounce_api->Logout(false);

		print $GLOBALS['Message'];
	}

	/**
	* Behaves like the unix which command
	* It checks the path in order for which version of $binary to run
	*
	* @param String $binary The name of a binary
	*
	* @return String The full path to the binary or an empty string if it couldn't be found
	*/
	function Which($binary)
	{
		// If the binary has the / or \ in it then skip it
		if (strpos($binary, DIRECTORY_SEPARATOR) !== false) {
			return '';
		}
		$path = null;

		if (ini_get('safe_mode')) {
			// if safe mode is on the path is in the ini setting safe_mode_exec_dir
			$_SERVER['safe_mode_path'] = ini_get('safe_mode_exec_dir');
			$path = 'safe_mode_path';
		} else if (isset($_SERVER['PATH']) && $_SERVER['PATH'] != '') {
			// On unix the env var is PATH
			$path = 'PATH';
		} else if (isset($_SERVER['Path']) && $_SERVER['Path'] != '') {
			// On windows under IIS the env var is Path
			$path = 'Path';
		}

		// If we don't have a path to search we can't find the binary
		if ($path === null) {
			return '';
		}

		$dirs_to_check = explode(PATH_SEPARATOR, $_SERVER[$path]);

		$open_basedirs = explode(PATH_SEPARATOR, ini_get('open_basedir'));

		foreach ($dirs_to_check as $dir) {
			if ($dir == '') {
				continue;
			}
			if (substr($dir, -1) == DIRECTORY_SEPARATOR) {
				$dir = substr($dir, 0, -1);
			}
			$can_check = true;
			if (!empty($open_basedirs)) {
				$can_check = false;
				foreach ($open_basedirs as $restricted_dir) {
					// if open_basedir isn't set, sometimes we end up with an array with an empty dir in it.
					// so ignore this one and see if there are more.
					if ($restricted_dir == '') {
						$can_check = true;
						continue;
					}
					if (strpos($dir, $restricted_dir) === 0) {
						$can_check = true;
					}
				}
			}

			if ($can_check && is_dir($dir) && is_file($dir.DIRECTORY_SEPARATOR.$binary)) {
				return $dir.DIRECTORY_SEPARATOR.$binary;
			}
		}
		return '';
	}

	/**
	 * PrintAddonsList
	 * Prints a list of all addons that the system can use.
	 * It works out what step an addon is up to (whether it is configured, enabled, installed or not) and prints an appropriate action
	 *
	 * @uses Interspire_Addons
	 * @uses Interspire_Addons::GetAllAddons
	 * @uses Interspire_Addons::GetAvailableAddons
	 * @uses FlashMessage
	 * @uses GetFlashMessages
	 *
	 * @return String Returns a formatted (table design) list of addons and what they are up to (whether they need to be configured, installed, enabled etc).
	 */
	function PrintAddonsList()
	{
		require_once(SENDSTUDIO_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR . 'interspire_addons.php');
		$addon_system = new Interspire_Addons();
		$addons = $addon_system->GetAllAddons();
		if (empty($addons)) {
			FlashMessage(GetLang('Addon_NoAddonsAvailable'), SS_FLASH_MSG_ERROR);
			$GLOBALS['Message'] .= GetFlashMessages();
			return $this->ParseTemplate('Settings_Addons_Empty', true, false);
		} else {
			$GLOBALS['Message'] .= GetFlashMessages();
		}

		$addons_status = $addon_system->GetAvailableAddons();

		$addons_list = '';

		$page = array(
			'message' => $GLOBALS['Message']
		);

		foreach ($addons as $addon_name => $details) {
			$addons[$addon_name]['name'] = htmlspecialchars($details['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$addons[$addon_name]['short_name'] = htmlspecialchars($this->TruncateName($details['name']), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$addons[$addon_name]['description'] = htmlspecialchars($details['description'], ENT_QUOTES, SENDSTUDIO_CHARSET);
			$addons[$addon_name]['short_description'] = htmlspecialchars($this->TruncateName($details['description']), ENT_QUOTES, SENDSTUDIO_CHARSET);

			if (isset($addons_status[$addon_name])) {
				$addons[$addon_name]['install_details'] = $addons_status[$addon_name];
				$addons[$addon_name]['need_upgrade'] = (version_compare($details['addon_version'], $addons_status[$addon_name]['addon_version']) == 1);
			} else {
				$addons[$addon_name]['install_details'] = false;
			}
		}

		$tpl = GetTemplateSystem();
		$tpl->Assign('PAGE', $page);
		$tpl->Assign('records', $addons);
		return $tpl->ParseTemplate('Settings_Addons_Display', true);
	}
}
