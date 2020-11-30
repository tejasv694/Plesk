<?php
/**
* This file has the login functions in it. Shows the login page and then authenticates upon submission.
*
* @version     $Id: login.php,v 1.21 2007/09/18 01:22:22 chris Exp $
* @author Chris <chris@interspire.com>
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for the login page. Will show the login screen, authenticate and set the session details as it needs to.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Login extends SendStudio_Functions
{

	/**
	* Constructor
	* Loads the language file.
	*
	* @see LoadLanguageFile
	*
	* @return Void Doesn't return anything.
	*/
	function Login()
	{
		$this->LoadLanguageFile();
	}

	/**
	* Process
	* All the action happens here.
	* If you are not logged in, it will print the login form.
	* Submitting that form will then try to authenticate you.
	* If you are successfully authenticated, you get redirected back to the main index page (quickstats etc).
	* Otherwise, will show an error message and the login form again.
	*
	* @see ShowLoginForm
	* @uses AuthenticationSystem::Authenticate()
	*
	* @return Void Doesn't return anything. Checks the action and passes it off to the appropriate area.
	*/
	function Process()
	{
		$action = IEM::requestGetGET('Action', '', 'strtolower');
		switch ($action) {
			case 'forgotpass':
				$this->ShowForgotForm();
			break;

			case 'changepassword':
				if (!IEM::sessionGet('ForgotUser')) {
					$this->ShowForgotForm('login_error', GetLang('BadLogin_Link'));
					break;
				}

				$userapi = GetUser(-1);
				$loaded = $userapi->Load(IEM::sessionGet('ForgotUser'));

				if (!$loaded) {
					$this->ShowForgotForm('login_error', GetLang('BadLogin_Link'));
					break;
				}

				$password = IEM::requestGetPOST('ss_password', false);
				$confirm = IEM::requestGetPOST('ss_password_confirm', false);

				if ($password == false || ($password != $confirm)) {
					$this->ShowForgotForm_Step2($userapi->Get('username'), 'login_error', GetLang('PasswordsDontMatch'));
					break;
				}

				$userapi->password = $password;
				$userapi->Save();

				$code = md5(uniqid(rand(), true));

				$userapi->ResetForgotCode($code);

				$this->ShowLoginForm('login_success', GetLang('PasswordUpdated'));
			break;

			case 'sendpass':
				$user = GetUser(-1);
				$username = IEM::requestGetPOST('ss_username', '');

				/**
				 * Fix vulnerabilities with MySQL
				 * Documented here: http://www.suspekt.org/2008/08/18/mysql-and-sql-column-truncation-vulnerabilities/
				 *
				 * Basically MySQL is truncating values in a column
				 */
					$username = preg_replace('/\s+/', ' ', $username);
					$username = trim($username);
				/**
				 * -----
				 */

				$founduser = $user->Find($username);
				if (!$founduser) {
					$this->ShowForgotForm('login_error', GetLang('BadLogin_Forgot'));
					break;
				}

				$user->Load($founduser, false);

				$code = md5(uniqid(rand(), true));

				$user->ResetForgotCode($code);

				$link = SENDSTUDIO_APPLICATION_URL . '/admin/index.php?Page=Login&Action=ConfirmCode&user=' . $founduser . '&code=' . $code;

				$message = sprintf(GetLang('ChangePasswordEmail'), $link);

				$email_api = $this->GetApi('Email');
				$email_api->Set('CharSet', SENDSTUDIO_CHARSET);
				$email_api->Set('Multipart', false);
				$email_api->AddBody('text', $message);
				$email_api->Set('Subject', GetLang('ChangePasswordSubject'));

				$email_api->Set('FromAddress', SENDSTUDIO_EMAIL_ADDRESS);
				$email_api->Set('ReplyTo', SENDSTUDIO_EMAIL_ADDRESS);
				$email_api->Set('BounceAddress', SENDSTUDIO_EMAIL_ADDRESS);

				$email_api->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

				$user_fullname = $user->Get('fullname');

				$email_api->AddRecipient($user->emailaddress, $user_fullname, 't');

				$email_api->Send();

				$this->ShowForgotForm_Step2($username,'login_success', sprintf(GetLang('ChangePassword_Emailed'), $user->emailaddress));
			break;

			case 'confirmcode':
				$user = IEM::requestGetGET('user', false, 'intval');
				$code = IEM::requestGetGET('code', false, 'trim');

				if (empty($user) || empty($code)) {
					$this->ShowForgotForm('login_error', GetLang('BadLogin_Link'));
					break;
				}

				$userapi = GetUser(-1);
				$loaded = $userapi->Load($user, false);

				if (!$loaded || $userapi->Get('forgotpasscode') != $code) {
					$this->ShowForgotForm('login_error', GetLang('BadLogin_Link'));
					break;
				}

				IEM::sessionSet('ForgotUser', $user);

				$this->ShowForgotForm_Step2($userapi->Get('username'));
			break;

			case 'login':
				$auth_system = new AuthenticationSystem();
				$username = IEM::requestGetPOST('ss_username', '');
				$password = IEM::requestGetPOST('ss_password', '');
				$result = $auth_system->Authenticate($username, $password);
				if ($result === -1) {
					$this->ShowLoginForm('login_error', GetLang('PleaseWaitAWhile'));
					break;
				} elseif ($result === -2) {
					$this->ShowLoginForm('login_error', GetLang('FreeTrial_Expiry_Login'));
					break;
				} elseif (!$result) {
					$this->ShowLoginForm('login_error', GetLang('BadLogin'));
					break;
				} elseif ($result && defined('IEM_SYSTEM_ACTIVE') && !IEM_SYSTEM_ACTIVE) {
					$msg = (isset($result['admintype']) && $result['admintype'] == 'a') ? 'ApplicationInactive_Admin' : 'ApplicationInactive_Regular';
					$this->ShowLoginForm('login_error', GetLang($msg));
					break;
				}

				$rememberdetails = IEM::requestGetPOST('rememberme', false);
				$rememberdetails = (bool)$rememberdetails;
                                
                                $user = false;
                                $rand_check = false;

				IEM::userLogin($result['userid']);

				$oneyear = 365 * 24 * 3600; // one year's time.

				$redirect = $this->_validateTakeMeToRedirect(IEM::requestGetPOST('ss_takemeto', 'index.php'));

				if ($rememberdetails) {
					if (!$user) { $user = IEM::userGetCurrent(); }
					if (!$rand_check) { $rand_check = uniqid(true); }
					$usercookie_info = array('user' => $user->userid, 'time' => time(), 'rand' => $rand_check, 'takemeto' => $redirect);
					IEM::requestSetCookie('IEM_CookieLogin', $usercookie_info, $oneyear);
					$usercookie_info = array('takemeto' => $redirect);
					IEM::requestSetCookie('IEM_LoginPreference', $usercookie_info, $oneyear);
				}



				header('Location: ' . SENDSTUDIO_APPLICATION_URL . '/admin/' . $redirect);
				exit();
			break;

			default:
				$msg = false; $template = false;
				if ($action == 'logout') {
					$this->LoadLanguageFile('Logout');
				}
				$this->ShowLoginForm($template, $msg);
			break;
		}
	}

	/**
	* ShowLoginForm
	* This shows the login form.
	* If there is a template to use in the data/templates folder it will use that as the login form.
	* Otherwise it uses the default one below. If you pass in a message it will show that message above the login form.
	*
	* @param String $template Uses the template passed in for the message (eg success / error).
	* @param String $msg Prints the message passed in above the login form (eg unsuccessful attempt).
	*
	* @see FetchTemplate
	* @see PrintHeader
	* @see PrintFooter
	*
	* @return Void Doesn't return anything, just prints the login form.
	*/
	function ShowLoginForm($template=false, $msg=false)
	{
		if (!IEM::getCurrentUser()) {
			$this->GlobalAreas['InfoTips'] = '';
		}

		$this->PrintHeader(true);

		$GLOBALS['Message'] = GetLang('Help_Login');

		if ($template && $msg) {
			switch ($template) {
				case 'login_error':
					$GLOBALS['Error'] = $msg;
				break;
				case 'login_success':
					$this->GlobalAreas['Success'] = $msg;
				break;
			}
			$GLOBALS['Message'] = $this->ParseTemplate($template,true);
		}

		$username = IEM::requestGetPOST('ss_username', false);
		if ($username) {
			$GLOBALS['ss_username'] = htmlspecialchars($username, ENT_QUOTES, SENDSTUDIO_CHARSET);
 		}

		$GLOBALS['ss_takemeto'] = 'index.php';
		$loginPreference = IEM::requestGetCookie('IEM_LoginPreference', array());
		if (is_array($loginPreference) && isset($loginPreference['takemeto'])) {
			$GLOBALS['ss_takemeto'] = $loginPreference['takemeto'];
		}

		$this->GlobalAreas['SubmitAction'] = 'Login';

		$this->ParseTemplate('login');

		$this->PrintFooter(true);
	}

	/**
	* ShowForgotForm
	* This shows the forgot password form and handles the multiple stages of actions. If the template and message are passed in, there will be a success/error message shown. If one is not present, nothing is shown.
	*
	* @param String $template If there is a template (will either be success or error template) use that as a message.
	* @param String $msg This also tells us what's going on (password has been reset and so on).
	*
	* @see PrintHeader
	* @see ParseTemplate
	* @see PrintFooter
	*
	* @return Void Doesn't return anything, only prints out the form.
	*/
	function ShowForgotForm($template=false, $msg=false)
	{
		$this->PrintHeader(true);

		$GLOBALS['Message'] = GetLang('Help_ForgotPassword');

		if ($template && $msg) {
			switch (strtolower($template)) {
				case 'login_error':
					$GLOBALS['Error'] = $msg;
				break;
				case 'login_success':
					$this->GlobalAreas['Success'] = $msg;
				break;
			}
			$GLOBALS['Message'] = $this->ParseTemplate($template, true, false);
		}

		$GLOBALS['SubmitAction'] = 'SendPass';

		$this->ParseTemplate('ForgotPassword');

		$this->PrintFooter(true);
	}

	/**
	* ShowForgotForm_Step2
	* This shows the form for changing the password. It will show the password/password confirm boxes for the user to fill in.
	*
	* @param String $username The username to show in the form. This is not editable, it is just shown for reference.
	* @param String $template If there is a template (will either be success or error template) use that as a message.
	* @param String $msg This also tells us what's going on (password has been reset and so on).
	*
	* @see PrintHeader
	* @see ParseTemplate
	* @see PrintFooter
	*
	* @return Void Doesn't return anything, only prints out the form.
	*/
	function ShowForgotForm_Step2($username='', $template=false, $msg=false)
	{
		$this->PrintHeader(true);

		$GLOBALS['UserName'] = htmlspecialchars($username, ENT_QUOTES, SENDSTUDIO_CHARSET);

		if ($template && $msg) {
			switch (strtolower($template)) {
				case 'login_error':
					$GLOBALS['Error'] = $msg;
					$template_page = 'ForgotPassword_Step2';
				break;
				case 'login_success':
					$GLOBALS['Message'] = $msg;
					$template = false;
					$template_page = 'ForgotPassword_Sendpass';
				break;
			}
			if ($template) {
				$GLOBALS['Message'] = $this->ParseTemplate($template, true, false);
			}
		} else {
			$template_page = 'ForgotPassword_Step2';
			$GLOBALS['Message'] = GetLang('Help_ForgotPassword');
		}

		$GLOBALS['SubmitAction'] = 'ChangePassword';

		$this->ParseTemplate($template_page);

		$this->PrintFooter(true);
	}

	/**
	 * _validateTakeMeToRedirect
	 * Validate wheter or not "Take Me To" redirect string is a valid re-direct
	 *
	 * @param String $redirectString Re-direct string
	 * @return String Return a valid re-direct string
	 *
	 * @access private
	 */
	function _validateTakeMeToRedirect($redirectString)
	{
		$defaultRedirect = 'index.php';
		$urlParts = parse_url($redirectString);

		// Don't bother checking if it is the default redirect
		if ($redirectString == $defaultRedirect) {
			return $redirectString;
		}

		// Must begin with index.php
		if (!preg_match('/^index.php/', $redirectString)) {
			return $defaultRedirect;
		}

		// Path must be index.php
		if (!isset($urlParts['path']) || strtolower($urlParts['path']) != 'index.php') {
			return $defaultRedirect;
		}

		// Query must exists
		if (!isset($urlParts['query'])) {
			return $defaultRedirect;
		}

		// Make into REQUEST string
		parse_str($urlParts['query'], $redirectRequest);

		// REQUEST redirect must have "Page" variable in it
		if (!isset($redirectRequest['Page'])) {
			return $defaultRedirect;
		}

		// Check if function exists
		if (!is_file(SENDSTUDIO_FUNCTION_DIRECTORY . '/' . strtolower($redirectRequest['Page']) . '.php')) {
			return $defaultRedirect;
		}

		return $redirectString;
	}
}
