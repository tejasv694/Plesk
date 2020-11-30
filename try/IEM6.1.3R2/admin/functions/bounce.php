<?php
/**
 * This file has the bounce management pages in it.
 *
 * @version     $Id: bounce.php,v 1.10 2008/03/05 03:00:17 scott Exp $
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
 * Class for management of bouncing email addresses.
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */
class Bounce extends SendStudio_Functions
{
	/**
	 * Suppress Header and Footer for these actions
	 *
	 * @see Process
	 *
	 * @var Array
	 */
	var $SuppressHeaderFooter = array('process');

	/**
	 * EmailsPerRefresh
	 * Number of emails to process per refresh.
	 *
	 * @var Int
	 */
	var $EmailsPerRefresh = 20;

	/**
	 * Constructor
	 * Loads the language file.
	 *
	 * @see LoadLanguageFile
	 * @see PrintHeader
	 * @see PrintFooter
	 *
	 * @return Void Loads up the language file and adds 'send' as a valid popup window type.
	 */
	public function __construct()
	{
		$this->PopupWindows[] = 'processdisplay';
		$this->PopupWindows[] = 'process';
		$this->PopupWindows[] = 'popupbouncetest';
		$this->PopupWindows[] = 'testbouncesettings';
		$this->PopupWindows[] = 'help';
		$this->LoadLanguageFile();
	}

	/**
	 * Process
	 * This works out where you are up to in the bounce process and takes the appropriate action.
	 * Most is passed off to other methods in this class for processing and displaying the right forms.
	 *
	 * @return Void Doesn't return anything.
	 */
	public function Process()
	{
		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;
		$user = GetUser();

		$popup = (in_array($action, $this->PopupWindows)) ? true : false;
		if (!in_array($action, $this->SuppressHeaderFooter)) {
			$this->PrintHeader($popup);
		}

		$access = $user->HasAccess('Lists', 'Bounce');
		if (!$access) {
			$this->DenyAccess();
			return;
		}

		// Check whether we are even capable of performing bounce processing.
		if (!function_exists('imap_open')) {
			$GLOBALS['Warning'] = GetLang('Bounce_No_ImapSupport_Intro');
			$GLOBALS['ErrorMessage'] = $this->ParseTemplate('WarningMsg', true);
			$this->ParseTemplate('Bounce_NoImapSupport');
			return;
		}

		// Used for popupbouncetest and testbouncesettings.
		$in_place = IEM::ifsetor($_GET['InPlace'], false);

		switch ($action) {
			case 'processfinished':
				$this->printFinalReport();
				break;

			case 'processdisplay':
				$this->ProcessBounceDisplay();
				break;

			case 'process':
				$this->ProcessBounceEmails();
				break;

			default:
			case 'bouncestep1':
				IEM::sessionRemove('BounceWizard');
				$this->bounceStep1();
				break;

			case 'bouncestep2':
				$this->bounceStep2();
				break;

			case 'bouncestep2warning':
				$this->bounceStep2Warning();
				break;

			case 'bouncestep3':
				$this->bounceStep3();
				break;

			case 'bouncestep4':
				$this->bounceStep4();
				break;

			case 'bouncestep5':
				$this->bounceStep5();
				break;

			case 'popupbouncetest':
				$this->popupBounceTest($in_place);
				break;

			case 'testbouncesettings':
				$this->testBounceSettings($in_place);
				break;

			case 'help':
				$topic = IEM::ifsetor($_GET['Topic'], false);
				self::showHelp($topic);
				break;
		}

		if (!in_array($action, $this->SuppressHeaderFooter)) {
			$this->PrintFooter($popup);
		}
	}

	/**
	 * bounceStep1
	 * Lets the user choose a set of lists (grouped by bounce server and username) to process bounces for.
	 *
	 * @return void Doesn't return anything. Prints out a list of Contact Lists.
	 */
	private function bounceStep1()
	{
		$tpl = GetTemplateSystem();

		$bounce_server_map = $this->getBounceServerMap();

		if (!count($bounce_server_map)) {
			$tpl->Assign('message', $this->PrintSuccess('Bounce_NoLists'));
			$tpl->ParseTemplate('Bounce_NoLists');
			return;
		}

		$tpl->Assign('bounce_server_map', $bounce_server_map, true);
		$tpl->ParseTemplate('BounceStep1');
	}

	/**
	 * bounceStep2
	 * Displays the bounce details for that list, allowing the user to fill in or modify the details.
	 *
	 * @return void Doesn't return anything. Prints out bounce details form.
	 */
	private function bounceStep2()
	{
		$user = GetUser();
		$list_api = $this->GetApi('Lists');
		$bd = self::hold('TestBounceDetails');

		$list = IEM::ifsetor(intval($_POST['list']), null);
		if (!$list) {
			$list = self::hold('list');
		}

		// User should have edit permissions for the list since they can change its bounce settings.
		// TODO: it should be for all lists that share this bounce server and username.
		$access = $user->HasAccess('lists', 'edit', $list);

		// Get bounce details for list.
		if (!$list_api->Load($list) || !$access) {
			$GLOBAL['ErrorMessage'] = GetLang('ListDoesntExist');
			$this->DenyAccess();
			return;
		}

		// Remember the List ID obtained in step 1.
		self::hold('list', $list);

		// The bounce details template still uses the old system.
		$GLOBALS['Bounce_Server'] = IEM::ifsetor($bd['server'], $list_api->bounceserver);
		$GLOBALS['Bounce_Username'] = IEM::ifsetor($bd['username'], $list_api->bounceusername);

		$GLOBALS['Bounce_Password'] = IEM::ifsetor($bd['password'], $list_api->bouncepassword);

		$imap = IEM::ifsetor($bd['imap'], $list_api->imapaccount);
		$GLOBALS['Imap_Selected'] = ($imap) ? ' selected="selected"' : '';

		$GLOBALS['DisplayExtraMailSettings'] = 'none';
		$extra_settings = IEM::ifsetor($bd['extra_settings'], $list_api->extramailsettings);
		if ($extra_settings) {
			$GLOBALS['DisplayExtraMailSettings'] = '';
			$GLOBALS['Bounce_ExtraOption'] = ' checked="checked"';
			$GLOBALS['Bounce_ExtraSettings'] = $extra_settings;
		}
		$GLOBALS['Bounce_AgreeDeleteAll'] = $list_api->agreedeleteall ? ' checked="checked"' : '';

		$tpl = GetTemplateSystem();

		// Show the manual settings if we're coming back from a future step.
		$tpl->Assign('show_manual', !is_null(($bd['server'])));
		$tpl->Assign('list_url', IEM::urlFor('Lists', array('Action' => 'edit', 'id' => $list)));
		$tpl->Assign('list_name', $list_api->name, true);
		$tpl->Assign('cron_url', IEM::urlFor('Settings', array('Tab' => 4)));
		$tpl->Assign('system_access', $user->HasAccess('System', 'System'));
		$tpl->ParseTemplate('BounceStep2');
	}

	/**
	 * bounceStep2Warning
	 * Displays errors from trying to login to the bounce server.
	 *
	 * @return void Doesn't return anything. Prints out error messages.
	 */
	private function bounceStep2Warning()
	{
		$errors = self::hold('ConnectionErrors');
		$bd = self::hold('TestBounceDetails');

		// Generate this for debugging purposes so more error handling can be added later.
		$error_report = '';
		foreach ($errors as $error) {
			$error_report .= $error . "\n\n";
		}
		$real_error = self::getRealError($errors);

		$tpl = GetTemplateSystem();
		if (IEM::ifsetor($real_error['unknown'], false)) {
			$tpl->Assign('problem_type', 'unknown');
		}
		$GLOBALS['Error'] = sprintf(GetLang('Bounce_Connection_Failed'), $bd['server']);
		$tpl->Assign('message', $this->ParseTemplate('ErrorMsg', true, false));
		$tpl->Assign('problem_name', $real_error['name']);
		$tpl->Assign('problem_advice', $real_error['advice']);
		$tpl->Assign('error_report', $error_report);

		$tpl->ParseTemplate('BounceStep2Warning');
	}

	/**
	 * bounceStep3
	 * Save the working bounce details and prompt the user to start looking for bounces.
	 *
	 * @return void Doesn't return anything.
	 */
	private function bounceStep3()
	{
		$tpl = GetTemplateSystem();
		$list_api = $this->GetApi('Lists');
		$bd = self::hold('TestBounceDetails');
		$email_count = self::hold('EmailCount');
		$list = self::hold('list');

		// Get bounce details for list.
		if (!$list_api->Load($list)) {
			$GLOBAL['ErrorMessage'] = GetLang('ListDoesntExist');
			$this->DenyAccess();
			return;
		}

		// Save settings if asked to.
		if ($bd['save_settings']) {
			$this->saveBounceDetails($list, $bd);
		}

		// We're not testing any more so change the storage key.
		self::hold('BounceDetails', $bd);

		// Set up state for bounce operations and report.
		$operations = array(
			'EmailsToDelete' => array(),
		);
		self::hold('BounceOperations', $operations);

		$report = array(
			'HardBounces' => 0,
			'SoftBounces' => 0,
			'EmailsIgnored' => 0,
			'EmailsDeleted' => 0,
		);
		self::hold('BounceReport', $report);

		$tpl->Assign('heading', sprintf(GetLang('Bounce_Find_Email_Step'), $list_api->bounceemail));

		$message = $this->PrintSuccess('Bounce_Connection_Success', $list_api->bounceserver);
		if (!$email_count) {
			$message = $this->PrintSuccess('BounceLogin_Successful');
		}
		$tpl->Assign('message', $message);
		$tpl->Assign('email_count', $email_count);
		$tpl->ParseTemplate('BounceStep3');
	}

	/**
	 * bounceStep4
	 * Provides a summary of the bounce emails that were found and prompts the user to process them.
	 *
	 * @return void Doesn't return anything.
	 */
	private function bounceStep4()
	{
		$list_api = $this->GetApi('Lists');
		$list = self::hold('list');
		$report = self::hold('BounceReport');
		$bd = self::hold('BounceDetails');

		// Get bounce details for list.
		if (!$list_api->Load($list)) {
			$GLOBAL['ErrorMessage'] = GetLang('ListDoesntExist');
			$this->DenyAccess();
			return;
		}

		$tpl = GetTemplateSystem();
		$tpl->Assign('heading', sprintf(GetLang('Bounce_Remove_Contact_Step'), $list_api->bounceemail));

		$bounce_count = $report['HardBounces'] + $report['SoftBounces'];
		$delete_count = $report['EmailsDeleted'];
		if ($bd['agreedeleteall']) {
			$delete_count += $report['EmailsIgnored'];
		}

		if ($bounce_count > 0) {
			$tpl->Assign('message', $this->PrintSuccess('Bounce_Found_Summary', $bounce_count, $report['HardBounces'], $report['SoftBounces']));
		} else {
			$tpl->Assign('message', $this->PrintWarning('Bounce_Found_None_Summary', $delete_count));
		}

		$tpl->Assign('bounce_count', $bounce_count);
		$tpl->Assign('delete_count', $delete_count);

		// Clear reports, counts and operations for the next (real) run.
		foreach ($report as $k=>$v) {
			$report[$k] = 0;
		}
		self::hold('BounceReport', $report);
		$operations = array(
			'EmailsToDelete' => array(),
		);
		self::hold('BounceOperations', $operations);

		$tpl->ParseTemplate('BounceStep4');
	}

	/**
	 * bounceStep3
	 * Provies a summary of the processed emails and deletes the emails marked for deletion.
	 *
	 * @return void Doesn't return anything.
	 */
	private function bounceStep5()
	{
		$bounce_api = $this->GetApi('Bounce');
		$list_api = $this->GetApi('Lists');
		$list = self::hold('list');
		$bd = self::hold('BounceDetails');
		$report = self::hold('BounceReport');
		$operations = self::hold('BounceOperations');

		// Get bounce details for list.
		if (!$list_api->Load($list)) {
			$GLOBAL['ErrorMessage'] = GetLang('ListDoesntExist');
			$this->DenyAccess();
			return;
		}

		$tpl = GetTemplateSystem();
		$tpl->Assign('heading', sprintf(GetLang('Bounce_Finished_Step'), $list_api->bounceemail));
		$tpl->Assign('message', $this->PrintSuccess('Bounce_Process_Success', ($report['HardBounces'] + $report['SoftBounces'])));
		$tpl->ParseTemplate('BounceStep5');
		flush();

		// Delete the emails marked for deletion.
		$bounce_api->Set('bounceserver', $bd['server']);
		$bounce_api->Set('bounceuser', $bd['username']);
		$bounce_api->Set('bouncepassword', base64_encode($bd['password']));
		$bounce_api->Set('imapaccount', $bd['imap']);
		$bounce_api->Set('extramailsettings', $bd['extra_settings']);

		$inbox = $bounce_api->Login();
		foreach ($operations['EmailsToDelete'] as $email_index) {
			$bounce_api->DeleteEmail($email_index);
		}
		$bounce_api->Logout(true);
	}

	/**
	 * processBounceDisplay
	 * Bootstraps the manual bounce processing by loading up the initial thickbox contents.
	 *
	 * @see processBounceEmails
	 *
	 * @return void Does not return anything.
	 */
	private function processBounceDisplay()
	{
		$bd = self::hold('BounceDetails');
		$email_count = self::hold('EmailCount');
		$params = '&DryRun=true';

		$bounce_opts = array(
			'inactive_hbounce' => IEM::ifsetor($_GET['inactive_hbounce'], false),
			'inactive_sbounce' => IEM::ifsetor($_GET['inactive_sbounce'], false),
			'delete_hbounce' => IEM::ifsetor($_GET['delete_hbounce'], false),
		);

		if ($bounce_opts['inactive_hbounce'] !== false) {
			// If this is set it means this is not a dry run.
			$GLOBALS['ProgressTitle'] = GetLang('BounceResults_InProgress');
			$GLOBALS['ProgressMessage'] = sprintf(GetLang('BounceResults_InProgress_Message'), $email_count);
			$params = '';
			foreach ($bounce_opts as $k=>$v) {
				$params .= "&{$k}={$v}";
			}
		} else {
			$GLOBALS['ProgressTitle'] = GetLang('Bounce_Finding_Bounces');
			$GLOBALS['ProgressMessage'] = GetLang('Bounce_Attempt_To_Find');
		}

		$GLOBALS['ProgressReport'] = $this->getStatusReport();
		$GLOBALS['ProgressStatus'] = sprintf(GetLang('BounceResults_InProgress_Progress'), 0, $this->FormatNumber($email_count));
		$GLOBALS['ProgressURLAction'] = 'index.php?Page=Bounce&Action=Process' . $params;

		$this->ParseTemplate('ProgressReport_Popup');
		flush();
	}

	/**
	 * processBounceEmails
	 * Loops over a certain number of emails (determined by $this->EmailsPerRefresh) and processes them.
	 * It then saves where it's up to and refreshes the thickbox to do it again until finished.
	 *
	 * @see processBounceDisplay
	 *
	 * @return void Does not return anything.
	 */
	private function processBounceEmails()
	{
		$bounce_api = $this->GetApi('Bounce');
		$bd = self::hold('BounceDetails');
		$list = self::hold('list');
		$email_count = self::hold('EmailCount');
		$bounce_report = self::hold('BounceReport');
		$bounce_operations = self::hold('BounceOperations');

		// Determine which URL to go to on completion.
		$dry_run = IEM::ifsetor($_GET['DryRun'], false);
		$continue_to = 'BounceStep5';
		if ($dry_run) {
			$continue_to = 'BounceStep4';
		}
		$finish_url = 'index.php?Page=Bounce&Action=' . $continue_to;

		// Get the position that we're up to for the next round of processing.
		$start_position = intval(IEM::ifsetor($_GET['Email'], 1));
		if ($start_position > $email_count) {
			self::setJSRedirect($finish_url, 'self.parent.parent', 0);
			exit();
		}

		$bounce_api->Set('bounceserver', $bd['server']);
		$bounce_api->Set('bounceuser', $bd['username']);
		$bounce_api->Set('bouncepassword', base64_encode($bd['password']));
		$bounce_api->Set('imapaccount', $bd['imap']);
		$bounce_api->Set('extramailsettings', $bd['extra_settings']);

		// Connect to the mailbox.
		$inbox = $bounce_api->Login();
		if (!$inbox) {
			$GLOBALS['Error'] = sprintf(GetLang('BadLogin_Details'), $bounce_api->Get('ErrorMessage'));
			$msg = $this->ParseTemplate('ErrorMsg', true, false);
			$msg = addcslashes(str_replace("\n", '\n', $msg), "\"'");
			echo "<script src=\"includes/js/jquery.js\"></script>\n";
			echo "<script>
				parent.$('#ProgressReportContainer > div').hide();
				parent.$('#ProgressReportMessage').html('{$msg}');
				parent.$('#ProgressReportTitle').show();
				parent.$('#ProgressReportMessage').show();
			</script>\n";
			exit();
		}

		// Process another chunk of emails.
		for ($index=0; $index<$this->EmailsPerRefresh; $index++) {
			$email_index = $start_position + $index;

			// Redirect if finished.
			if ($email_index > $email_count) {
				// Session data changed in this loop needs to be saved before redirecting.
				self::hold('EmailCount', $email_count);
				self::hold('BounceOperations', $bounce_operations);
				$bounce_api->Logout();
				self::setJSRedirect($finish_url, 'self.parent.parent', 0);
				exit();
			}

			// Update the progress bar status.
			$report = $this->getStatusReport();
			self::updateProgressReport($report);
			// Caclulate the percentage completed.
			$percent_processed = floor($email_index / $email_count * 100);
			$message = sprintf(GetLang('BounceResults_InProgress_Progress'), $email_index, $this->FormatNumber($email_count));
			self::updateProgressBar($percent_processed, $message);

			// This is used to keep state for processing options.
			$params = '';
			// Set processing parameters.
			$options = array(
				'flagHardBounce' => 'inactive_hbounce',
				'flagSoftBounce' => 'inactive_sbounce',
				'deleteHardBounce' => 'delete_hbounce',
			);
			foreach ($options as $option=>$key) {
				$value = IEM::ifsetor($_GET[$key], null);
				if (!is_null($value)) {
					$bounce_api->Set($option, (bool)$value);
					$params .= "&{$key}={$value}";
				}
			}

			// Process the actual email.
			$processed = $bounce_api->ProcessEmail($email_index, $list, $dry_run);

			if ($processed == 'hard') {
				$bounce_operations['EmailsToDelete'][] = $email_index;
				$bounce_report['HardBounces']++;
			}

			if ($processed == 'soft') {
				$bounce_operations['EmailsToDelete'][] = $email_index;
				$bounce_report['SoftBounces']++;
			}

			// see api/bounce.php for what 'delete' means.
			if ($processed == 'delete' || ($processed == 'ignore' && $bd['agreedeleteall'])) {
				$bounce_operations['EmailsToDelete'][] = $email_index;
				$bounce_report['EmailsDeleted']++;
			} elseif ($processed == 'ignore') {
				$bounce_report['EmailsIgnored']++;
			}
			// We need to save this now for getStatusReport().
			self::hold('BounceReport', $bounce_report);
		}
		$bounce_api->Logout();
		self::hold('EmailCount', $email_count);
		self::hold('BounceOperations', $bounce_operations);

		// Need to increment email_index because emails actually start counting at 1 not 0.
		// Otherwise every $this->EmailsPerRefresh emails would be processed twice.
		// For example: email '20' would be processed twice - which stuffs up reporting.
		$rand = rand(1, 50); // Avoid cache problems.
		$next_index = $email_index + 1;
		$params .= $dry_run ? '&DryRun=true': '';
		self::setJSRedirect("index.php?Page=Bounce&Action=Process{$params}&Email={$next_index}&bx={$rand}");
		exit();
	}

	/**
	 * getStatusReport
	 * Grabs the bounce report information from the session.
	 *
	 * @return string The report in HTML.
	 */
	private function getStatusReport()
	{
		$bounce_report = self::hold('BounceReport');
		$report = '';
		foreach (array('HardBounces', 'SoftBounces', 'EmailsIgnored', 'EmailsDeleted') as $pos=>$key) {
			$amount = $bounce_report[$key];
			if ($amount == 1) {
				$report .= GetLang('BounceResults_InProgress_' . $key . '_One');
			} else {
				$report .= sprintf(GetLang('BounceResults_InProgress_' . $key . '_Many'), $this->FormatNumber($amount));
			}
			$report .= '<br/>';
		}
		return $report;
	}

	/**
	 * hold
	 * Remembers bounce-related information in the session.
	 *
	 * @param string $key The key of the value to remember.
	 * @param mixed $value If null, will cause the function to return the value of the key, otherwise this is the value to be stored.
	 *
	 * @return mixed False if $value is null and $key has not been set, otherwise returns the value referenced by $key.
	 */
	private static function hold($key, $value=null)
	{
		$hold = IEM::sessionGet('BounceWizard');
		if (!is_array($hold)) {
			$hold = array();
		}
		if (!is_null($value)) {
			$hold[$key] = $value;
			IEM::sessionSet('BounceWizard', $hold);
		} elseif (!isset($hold[$key])) {
			// If a non-existent key is trying to be retrieved, return false.
			return false;
		}
		return $hold[$key];
	}

	/**
	 * popupBounceTest
	 * Bootstraps the thickbox status window for checking bounce login details.
	 *
	 * @param boolean $in_place Whether the popup is mean to give results on this page (true) ore redirect (false).
	 *
	 * @return void Does not return anything.
	 */
	private function popupBounceTest($in_place = false)
	{
		$bounce_details = array (
			'server' => $_GET['bounce_server'],
			'username' => $_GET['bounce_username'],
			'password' => $_GET['bounce_password'],
			'extra_settings' => $_GET['bounce_extrasettings'],
			'imap' => IEM::ifsetor($_GET['bounce_imap'], false),
			'agreedeleteall' => IEM::ifsetor($_GET['bounce_agreedeleteall'], false),
			'save_settings' => IEM::ifsetor($_GET['savebounceserverdetails'], false),
		);

		// Decrypt the password, as it was encrypted with a JavaScript XOR routine to send here.
		$bounce_details['password'] = IEM::decrypt($bounce_details['password'], IEM::sessionGet('RandomToken'));

		self::hold('TestBounceDetails', $bounce_details);

		$GLOBALS['ProgressTitle'] = GetLang('Bounce_Connecting');
		$GLOBALS['ProgressMessage'] = GetLang('Bounce_Connecting_Msg');
		$GLOBALS['ProgressReport'] = ''.
		$GLOBALS['ProgressStatus'] = '';
		$GLOBALS['ProgressURLAction'] = 'index.php?Page=Bounce&Action=TestBounceSettings';
		if ($in_place) {
			$GLOBALS['ProgressURLAction'] .= '&InPlace=true';
		}

		$this->ParseTemplate('ProgressReport_Popup');
	}


	/**
	 * testCombination
	 * Attempts to login to a boucne mailbox.
	 *
	 * @param array $bd The bounce details to connect with, except for the extra settings.
	 * @param string $extra_settings The extra settings to connect with, e.g. /ssl/tls.
	 *
	 * @return array A Boolean value indiciating success or failure, and a second element containing either the number of emails in the inbox (on success) or the error message (on failure).
	 */
	private static function testCombination($bd, $extra_settings)
	{
		$bounce_api = self::GetApi('Bounce');
		$bounce_api->Set('bounceserver', $bd['server']);
		$bounce_api->Set('bounceuser', $bd['username']);
		$bounce_api->Set('bouncepassword', base64_encode($bd['password']));
		$bounce_api->Set('imapaccount', $bd['imap']);
		$bounce_api->Set('extramailsettings', $extra_settings);
		$login_ok = $bounce_api->Login();
		if (!$login_ok) {
			return array(false, $bounce_api->ErrorMessage);
		}
		$count = $bounce_api->GetEmailCount();
		$bounce_api->Logout(false);
		return array(true, $count);
	}

	/**
	 * handle
	 * Perform the appropriate action after the Bounce Test popup has completed.
	 * For manual bounce processing, this will redirect to the next step.
	 * For other cases it will either report success and close or display the error.
	 *
	 * @param string $type One of 'next_combo', 'error_report' or 'success'.
	 * @param boolean $in_place Whether the action should take place in the popup (true) or redirect.
	 *
	 * @return void Does not return anything. It prints JavaScript (ugh!).
	 */
	private static function handle($type, $in_place)
	{
		$bd = self::hold('TestBounceDetails');
		$root = 'index.php?Page=Bounce&Action=';
		$urls = array(
			'next_combo' => array('TestBounceSettings', 'window', 150),
			'error_report' => array('BounceStep2Warning', 'self.parent.parent', 0),
			'success' => array('BounceStep3', 'self.parent.parent', 0)
		);
		if (!$in_place || $type == 'next_combo') {
			$url = $root . $urls[$type][0];
			$url .= $in_place ? '&InPlace=true' : '';
			self::setJSRedirect($url, $urls[$type][1], $urls[$type][2]);
			exit();
		}
		// Replace the progress bar with a message.
		// TODO: work out some nicer way to do this.
		echo "<script src=\"includes/js/jquery.js\"></script>\n";
		if ($type == 'error_report') {
			// Alert the appropriate error.
			$error = self::getRealError(self::hold('ConnectionErrors'));
			$tpl = GetTemplateSystem();
			$GLOBALS['Error'] = "<strong>" . $error['name'] . "</strong>";
			$GLOBALS['Error'] .= "<ul style=\"padding-left:2em;\">";
			foreach ($error['advice'] as $title => $article) {
				$GLOBALS['Error'] .= "<li><a href=\"javascript:LaunchHelp('".IEM::enableInfoTipsGet()."',{$article});\">{$title}</a></li>\n";
			}
			$GLOBALS['Error'] .= "</ul>";
			$msg = $tpl->ParseTemplate('ErrorMsg', true);
		} elseif ($type == 'success') {
			// Set the combo in the UI and disappear.
			$msg = self::PrintSuccess('BounceLogin_Successful');
		}
		$msg = str_replace("\n", '\n', addslashes($msg));
		echo "<script>
			parent.$('#ProgressReportContainer > div').hide();
			parent.$('#ProgressReportMessage').html('{$msg}');
			parent.$('#ProgressReportTitle').show();
			parent.$('#ProgressReportMessage').show();\n";
		if ($type == 'success') {
			echo "self.parent.parent.$('#bounce_extrasettings').val('{$bd['extra_settings']}');\n";
			echo "self.parent.parent.Application.Page.BounceInfo.evaluateCheckboxes();\n";
			echo "setTimeout(function() { self.parent.parent.tb_remove(); }, 1500);\n";
		} else {
			echo "parent.$('#ProgressReportWindow_Close').show();\n";
		}
		echo "</script>\n";
		exit();
	}

	/**
	 * testBounceSettings
	 * Produces the contents of the thickbox used when checking bounce login details.
	 * If specified, it will try all possible combinations of extra settings to get a connection.
	 *
	 * @param boolean $in_place If set to true, will update the extra settings in place and not redirect.
	 *
	 * @return void Does not return anything.
	 */
	private function testBounceSettings($in_place = false)
	{
		$tpl = GetTemplateSystem();
		$bd = self::hold('TestBounceDetails');

		$upto_combo = IEM::ifsetor($bd['upto_combo'], 0);
		$combinations = array($bd['extra_settings']);
		// If extra settings aren't specified, we need to auto-detect.
		if (!$bd['extra_settings']) {
			$combinations = $this->generateConnectionCombinations();
		}

		if ($upto_combo > count($combinations)) {
			// Handle the case where checking has finished but no solution has been found.
			self::handle('error_report', $in_place);
		}

		if ($upto_combo == 0) {
			// Reset error log.
			self::hold('ConnectionErrors', array());
			// Check the sever can actually be connected to (manually, so we can customise the timeout).
			$message = sprintf(GetLang('Bounce_Connecting_To'), $bd['server']);
			self::updateProgressBar(0, $message);
			list($success, $error) = self::testConnection($bd);
			if (!$success) {
				$error_log[] = $error;
				self::hold('ConnectionErrors', $error_log);
				self::handle('error_report', $in_place);
			}
		}

		// Update progress window status.
		$percent_processed = floor($upto_combo / count($combinations) * 100);
		self::updateProgressBar($percent_processed);

		// Attempt a login with one of the settings combinations.
		list($success, $count_or_error) = self::testCombination($bd, $combinations[$upto_combo]);

		if ($success) {
			// Store the email count for the next step.
			self::hold('EmailCount', $count_or_error);

			// Save the successfull extra settings.
			$bd['extra_settings'] = $combinations[$upto_combo];
			self::hold('TestBounceDetails', $bd);

			// Redirect to the next step.
			self::updateProgressBar(100);
			self::handle('success', $in_place);
		}

		// Combination failed - record error and try the next combination.
		$error_message = $combinations[$upto_combo] . ': ' . $count_or_error;
		$error_log[] = $error_message;
		self::hold('ConnectionErrors', $error_log);

		$error = self::getRealError($error_message);

		if ($error['fatal'] || count($combinations) == 1) {
			// No point continuing to try after a fatal error.
			self::updateProgressBar(100);
			self::handle('error_report', $in_place);
		}

		$bd['upto_combo']++;
		self::hold('TestBounceDetails', $bd);
		self::handle('next_combo', $in_place);
	}

	/**
	 * testConnection
	 * Connects to the bounce server using fsockopen with a specified timeout.
	 *
	 * @param array Bounce server details [(string)server, (bool)imap, ...].
	 * @param int $timeout Defaults to 10 seconds.
	 *
	 * @return array Returns an array of the form [(bool)status, (string)error_message].
	 */
	private static function testConnection($bounce_details, $timeout = 10)
	{
		$server = $bounce_details['server'];
		$port = false;
		if (strpos($server, ':') !== false) {
			list($server, $port) = explode(':', $server);
		}
		if (!$port) {
			$port = $bounce_details['imap'] ? 143 : 110;
		}
		$fp = @fsockopen($server, $port, $errno, $errstr, $timeout);
		if (!$fp) {
			return array(false, $errstr);
		}
		return array(true, '');
	}

	/**
	 * generateConnectionCombinations
	 * Generates possible connection strings to pass to imap_open.
	 *
	 * @return array An array of connection strings, e.g. ['/ssl/tls', '/ssl/notls', ...].
	 */
	private function generateConnectionCombinations()
	{
		$ssl = array('', '/ssl', '/nossl');
		$tls = array('', '/tls', '/notls');
		$cert = array('', '/novalidate-cert');

		$combinations = array();
		foreach ($ssl as $use_ssl) {
			foreach ($tls as $use_tls) {
				foreach ($cert as $cert_check) {
					$combinations[] = "{$use_ssl}{$use_tls}{$cert_check}";
				}
			}
		}

		return array_unique($combinations);
	}

	/**
	 * getBounceServerMap
	 * Obtains a list of Contact Lists, grouped by bounce details (server and username).
	 *
	 * @return array The list of contact lists, grouped by server and username.
	 */
	private function getBounceServerMap()
	{
		$user = GetUser();
		$lists = $user->GetLists();
		$bounce_server_map = array();

		// Contact lists are grouped by their bounce server and bounce username.
		foreach ($lists as $list) {
			$key = $list['bounceserver'] . $list['bounceusername'];
			if (!$list['bounceserver'] || !$list['bounceusername']) {
				 // Prefix keys of lists we do not group with a ~ to place them at the end.
				$key = '~' . $list['name'];
			}
			if (!isset($bounce_server_map[$key])) {
				$bounce_server_map[$key] = array();
			}
			$bounce_server_map[$key][] = array(
				'id' => $list['listid'],
				'name' => $list['name'],
				'server' => $list['bounceserver'],
				'username' => $list['bounceusername'],
			);
		}
		ksort($bounce_server_map);
		return $bounce_server_map;
	}

	/**
	 * saveBounceDetails
	 * Saves the bounce details for all lists that share the same bounce server and username.
	 *
	 * @param int $lid The list ID to use to locate the other lists.
	 * @param array $details The bounce details to set for these lists.
	 *
	 * @return boolean True for success, otherwise false.
	 */
	private function saveBounceDetails($lid, $details)
	{
		$list_api = $this->GetApi('Lists');

		$map = $this->getBounceServerMap();
		$group = null;

		// Check the map for the other lists like the one passed in.
		foreach ($map as $k=>$lists) {
			foreach ($lists as $list) {
				if ($list['id'] == $lid) {
					$group = $map[$k];
				}
			}
		}
		if (!is_array($group)) {
			return false;
		}
		// Save the details for each list.
		foreach ($group as $list) {
			if (!$list_api->Load($list['id'])) {
				trigger_error("Unable to load list with ID {$list['id']}.", E_USER_NOTICE);
				continue;
			}
			$list_api->Set('bounceserver', $details['server']);
			$list_api->Set('bounceusername', $details['username']);
			$list_api->Set('bouncepassword', $details['password']);
			$list_api->Set('imapaccount', $details['imap']);
			$list_api->Set('extramailsettings', $details['extra_settings']);
			$list_api->Set('processbounce', true);
			$list_api->Set('agreedelete', true);
			$list_api->Set('agreedeleteall', $details['agreedeleteall']);
			$list_api->Save();
		}
		return true;
	}

	/**
	 * updateProgressBar
	 * Updates the percentage of the progress bar for the progress thickbox.
	 *
	 * @param int $percentage The percentage the progress bar should reflect.
	 * @param string $message A message underneath the progress bar.
	 *
	 * @return void Does not return anything. Flushes output.
	 */
	private static function updateProgressBar($percentage, $message = '')
	{
		$message = str_replace("'", "\'", $message);
		echo "<script defer=\"defer\">\n";
		echo sprintf("self.parent.UpdateStatus('%s', %d);\n", $message, $percentage);
		echo "</script>\n";
		flush();
	}

	/**
	 * updateProgressReport
	 * Updates the report in the progress thickbox.
	 *
	 * @param string $report The report to put in the progress thickbox.
	 *
	 * @return void Does not return anything. Flushes output.
	 */
	private static function updateProgressReport($report)
	{
		echo "<script>\n";
		echo sprintf("self.parent.UpdateStatusReport('%s');\n", $report);
		echo "</script>\n";
		flush();
	}

	/**
	 * setJSRedirect
	 * Makes a setTimeout JS call to the given URL.
	 *
	 * @param string $url The URL to redirect to.
	 * @param string $target The target window to perform the redirect in.
	 * @param int $delay The number of milliseconds to wait before redirecting.
	 *
	 * @return void Does not return anything. Flushes output.
	 */
	private static function setJSRedirect($url, $target = 'window', $delay = 150)
	{
		$delay = intval($delay);
		$url = str_replace("'", "\'", $url);
		echo "<script>\n";
		echo "setTimeout(function() { {$target}.location = '{$url}'; }, {$delay});\n";
		echo "</script>\n";
		flush();
	}

	/**
	 * getRealError
	 * Takes a list of errors from multiple bounce server login attempts and returns a generic version of the most appropriate error message.
	 *
	 * @param array|string $error_list List of error messages from bounce server login attempts.
	 *
	 * @return array A generic version of the most appropriate error in the list in the form [pattern, name, advice, fatal].
	 */
	private static function getRealError($error_list)
	{
		if (!is_array($error_list)) {
			$error_list = array($error_list);
		}
		// The earlier the error is defined, the higher priority it has.
		// An error that is fatal is one that no amount of auto-detection can get around (e.g. bad login details).
		$possible_errors = array(
			'bad_host' => array(
				'pattern' => array('/No such host/i', '/Name or service not known/i'),
				'name' => GetLang('Bounce_Error_InvalidServer'),
				'advice' => array('Bounce_Help_InvalidServer1' => 862, 'Bounce_Help_TimeOut1' => 863),
				'fatal' => true,
			),
			'timeout' => array(
				'pattern' => '/ time/i',
				'name' => GetLang('Bounce_Error_TimeOut'),
				'advice' => array('Bounce_Help_TimeOut1' => 863, 'Bounce_Help_TimeOut2' => 864),
				'fatal' => true,
			),
			'connection_refused' => array(
				'pattern' => '/Connection refused/i',
				'name' => GetLang('Bounce_Error_ConnRefused'),
				'advice' => array('Bounce_Help_TimeOut2' => 864, 'Bounce_Help_TimeOut1' => 863),
				'fatal' => true,
			),
			'bad_login' => array(
				'pattern' => array('/Login failed/i', '/Authentication failed/i', '/Logon failure/i'),
				'name' => GetLang('Bounce_Error_LoginFailed'),
				'advice' => array('Bounce_Help_LoginFailed1' => 865),
				'fatal' => true,
			),
			// The following errors should be overcome if using auto-detection.
			'cert_failure' => array(
				'pattern' => '/Certificate failure for/i',
				'name' => GetLang('Bounce_Error_CertFailure'),
				'advice' => array('Bounce_Help_CertFailure1' => 860),
				'fatal' => false,
			),
			'ssl_neg_failed' => array(
				'pattern' => array('/SSL negotiation failed/i', '/Unable to negotiate TLS with this server/i'),
				'name' => GetLang('Bounce_Error_SSLFailed'),
				'advice' => array('Bounce_Help_SSLFailed1' => 866),
				'fatal' => false,
			),
			'invalid_spec' => array(
				'pattern' => '/invalid remote specification/i',
				'name' => GetLang('Bounce_Error_InvalidSpec'),
				'advice' => array('Bounce_Help_TimeOut2' => 864, 'Bounce_Help_InvalidSpec1' => 867),
				'fatal' => false,
			),
			'unknown' => array(
				'pattern' => '/./',
				'name' => GetLang('Bounce_Error_Unknown'),
				'advice' => array('Bounce_Help_TimeOut1' => 863, 'Bounce_Help_TimeOut2' => 864),
				'fatal' => false,
				'unknown' => true,
			),
		);
		$actual_error = $possible_errors['unknown'];
		foreach ($possible_errors as $k=>$p) {
			foreach ($error_list as $error) {
				if (!is_array($p['pattern'])) {
					$p['pattern'] = array($p['pattern']);
				}
				foreach ($p['pattern'] as $pattern) {
					if (preg_match($pattern, $error)) {
						$actual_error = $p;
						break 3;
					}
				}
			}
		}
		// Fill in the language variables for the advice.
		$advice = array();
		foreach ($actual_error['advice'] as $tok=>$article) {
			$lang = GetLang($tok);
			$advice[$lang] = $article;
		}
		$actual_error['advice'] = $advice;
		return $actual_error;
	}

	/**
	 * showHelp
	 * Displays a help message, to be placed within a thickbox window.
	 *
	 * @param string $topic The topics to display.
	 *
	 * @return boolean Returns false on error, otherwise true.
	 */
	private static function showHelp($topic)
	{
		$questions = array(
			'list_group' => 'WhyListsGrouped',
			'auto_explain' => 'Bounce_Auto_Process_Why',
			'manual_explain' => 'Bounce_Manual_Process_Why',
		);
		$answers = array(
			'list_group' => 'Bounce_Why_Group_Lists',
			'auto_explain' => 'Bounce_Why_Use_Auto',
			'manual_explain' => 'Bounce_Why_Not_Manual',
		);
		if (!array_key_exists($topic, $questions)) {
			return false;
		}
		$tpl = GetTemplateSystem();
		$tpl->Assign('heading', GetLang($questions[$topic], true));
		$tpl->Assign('message', GetLang($answers[$topic], true));
		$tpl->ParseTemplate('Help_PopUp');
		return true;
	}

}
