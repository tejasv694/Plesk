<?php
/**
 * This file contains AuthenticationSystem class definition.
 *
 * @package interspire.iem.lib
 */

/**
 * AuthenticationSystem class
 *
 * This class contains procedure to authenticate user login.
 *
 * @package interspire.iem.lib
 */
class AuthenticationSystem
{
	/**
	* Authenticate
	* Authenticates the user. Will return false if the user doesn't exist or the passwords don't match.
	*
	* This function will return the following:
	* - FALSE whenever user does not exists, or cannot authenticate
	* - -1 Whenever IP Address is banned
	* - -2 Whenever account has expired
	*
	* @param String $username Username to authenticate.
	* @param String $password Password to use to authenticate the user.
	* @param String $xmltoken The token to authentication the user when they use the XML-API. This is used instead of the password. This also checks if the user has access to use the XML-API as well.
	*
	* @return Mixed Returns false if the user doesn't exist or can't authenticate, otherwise it will return the UserID of the user it found.
	*/
	public function Authenticate($username, $password='', $xmltoken='')
	{
		$username = trim(preg_replace('/\s+/', ' ', $username));
		$password = trim($password);
		$xmltoken = trim($xmltoken);

		$custom = new CustomConfiguration('authentication');

		// Check whether or not IP address is banned
		if ($this->_isIPBanned()) {
			$this->_failedLogin($username, $password, $xmltoken);
			return -1;
		}

		// Remove XML API authentication if it is disabled by "custom" configuration
		if ($custom->get('enable_xmlapi', true) === false) {
			$xmltoken = '';
		}

		// Do not proceed if username or password is empty (at least password or xmltoken must be specified)
		if (empty($username) || (empty($password) && empty($xmltoken))) {
			$this->_failedLogin($username, $password, $xmltoken);
			return false;
		}

		if (empty($password)) {
			$password = false;
		}

		if (empty($xmltoken)) {
			$xmltoken = false;
		}

		// Check whether or not we should be blocking the login request based on the user IP
		$allowAdmin = $this->_allowLoginBasedOnIP($custom->get('admin_allow_ip', array()), $custom->get('admin_allow_ip_enabled', false));
		$allowRegular = $this->_allowLoginBasedOnIP($custom->get('regularuser_allow_ip', array()), $custom->get('regularuser_allow_ip_enabled', false));

		// ----- Process masquerade directive if enabled
			if ($allowAdmin && $custom->get('enable_masquerade', true)) {
				$tempPatternString = '/^(.*?)' . $custom->get('masquerade_token_separator', '::', 'preg_quote') . '(.*)/';

				if (preg_match($tempPatternString, $username, $matches)) {
					$tempAdminUsername = $matches[1];
					$tempSubjectUsername = $matches[2];
					unset($matches);

					$record = $this->_authenticate($tempAdminUsername, $password, $xmltoken);

					// If no record is found, or admintype is not set, or user is not an admin
					if (empty($record) || !array_key_exists('admintype', $record) || ($record['admintype'] != 'a')) {
						$this->_failedLogin($username, $password, $xmltoken);
						return false;
					}

					unset($record);

					// Set up masquerading username, and set the password to nothing so it won't check any password
					$username = $tempSubjectUsername;
					$password = false;
					$allowRegular = true;

					unset($tempAdminUsername);
					unset($tempSubjectUsername);
				}

				unset($tempPatternString);
			}
		// -----

		if (!$allowRegular && !$allowAdmin) {
			$this->_failedLogin($username, $password, $xmltoken);
			return false;
		}

		$record = $this->_authenticate($username, $password, $xmltoken);
		if (empty($record) || ($record['admintype'] == 'a' && !$allowAdmin) || ($record['admintype'] == 'c' && !$allowRegular)) {
			$this->_failedLogin($username, $password, $xmltoken);
			return false;
		}

		// ----- Make sure that "trial user" cannot login once their trial days is up
			if ($record['trialuser']) {
				$userobject = GetUser($record['userid']);
				if (!$userobject) {
					return false;
				}

				$trialinfo = $userobject->GetTrialInformation();
				if ($trialinfo['days_left'] <= 0) {
					return -2;
				}
			}
		// -----

		return $record;
	}

	/**
	 * _authenticate
	 * Return user record based on the username/password that is supplied.
	 * If user does not exists, it will return an integer 0 (Zero).
	 *
	 * @param String $username Username to login user with
	 * @param String $password Password to login user with
	 * @param String $xmltoken XML Token to login user with
	 * @return Mixed Returns an associative array of the user record if username/password match, 0 if record does not match, FALSE if error occured
	 *
	 * @uses Db::Quote()
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	private function _authenticate($username, $password, $xmltoken)
	{
		$db = IEM::getDatabase();
		$username = $db->Quote($username);

		if ($password === '' && $xmltoken === '') {
			return 0;
		}

		$query = "SELECT * FROM [|PREFIX|]users WHERE username = '{$username}' AND status = '1'";

		$result = $db->Query($query);
		if ($result == false) {
			list($error, $level) = $db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$details = $db->Fetch($result);
		$db->FreeResult($result);

		if (empty($details)) {
			return 0;
		}

		if (!empty($password)) {
			$tempPassword = $password;

			if (array_key_exists('unique_token', $details)) {
				$tempPassword = API_USERS::generatePasswordHash($password, $details['unique_token']);
			} else {
				$tempPassword = md5($password);
			}

			if ($details['password'] != $tempPassword) {
				return 0;
			}
		} elseif (!empty($xmltoken) && ($details['xmltoken'] != $xmltoken)) {
			return 0;
		}

		return $details;
	}

	/**
	 * _failedLogin
	 * Provide a unified way to handle failed login.
	 *
	 * @param String $username Username that it tries to login with
	 * @param String $password Password that it tries to login with
	 * @param String $xmltoken XML Token that it tries to login with
	 * @return Void Returns nothing
	 */
	private function _failedLogin($username, $password, $xmltoken)
	{
		// If "Ban IP" feature is disabled, do not proceed
		if (SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_COUNT == 0) {
			return;
		}

		$db = IEM::getDatabase();

		$tablePrefix = SENDSTUDIO_TABLEPREFIX;
		$ip = GetRealIp(true);
		$breached = false;
		$now = time();
		$threshold_duration = time() - SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_DURATION;


		if (!empty($ip) && $this->_failedLoginSecurityAvailable()) {
			// ----- Clean up unused record
				$status = $db->Query("DELETE FROM {$tablePrefix}login_attempt WHERE timestamp < {$threshold_duration}");
				if ($status === false) {
					trigger_error('Cannot clean up unused record from failed login attempt table', E_USER_WARNING);
				}
			// -----

			// ----- Record failed login
				$query = "
					INSERT INTO {$tablePrefix}login_attempt (timestamp, ipaddress)
					VALUES ({$now}, '{$ip}')
				";

				$status = $db->Query($query);
				if ($status === false) {
					trigger_error('Cannot record failed login attempt to database', E_USER_WARNING);
				}
			// -----

			// ----- Check if the IP has exceeded login threshold
				$query = "
					SELECT COUNT(1) AS count
					FROM {$tablePrefix}login_attempt
					WHERE	ipaddress = '{$ip}'
							AND timestamp > {$threshold_duration}
				";

				$status = $db->Query($query);
				if ($status === false) {
					trigger_error('Cannot count login threshold breach for current IP', E_USER_WARNING);
				} else {
					$count = $db->FetchOne($status, 'count');
					if ($count >= SENDSTUDIO_SECURITY_WRONG_LOGIN_THRESHOLD_COUNT) {
						$breached = true;
					}

					$db->FreeResult($status);
				}
			// -----

			// Ban IP if it breaches threshold
			if ($breached) {
				$bantime = time() + SENDSTUDIO_SECURITY_BAN_DURATION;
				$query = "INSERT INTO {$tablePrefix}login_banned_ip (ipaddress, bantime) VALUES ('{$ip}', {$bantime})";

				// ----- Determine whether or not to do an update or an insert
					$status = $db->Query("SELECT ipaddress FROM {$tablePrefix}login_banned_ip WHERE ipaddress='{$ip}'");
					if ($status === false) {
						trigger_error('Cannot query banned IP table', E_USER_WARNING);
					} else {
						$row = $db->Fetch($status);
						$db->FreeResult($status);

						if ($row !== false) {
							$query = "UPDATE {$tablePrefix}login_banned_ip SET bantime = {$bantime} WHERE ipaddress = '{$ip}'";
						}
					}
				// -----

				// ----- Process ban information
					$status = $db->Query($query);
					if ($status === false) {
						trigger_error('Cannot update/insert ban information', E_USER_WARNING);
					}
				// -----
			}
		}

		/**
		 * Delay the login process so that it takes longer for program/people to brute force
		 * their way to the application.
		 */
		if (SENDSTUDIO_SECURITY_WRONG_LOGIN_WAIT != 0) {
			sleep(SENDSTUDIO_SECURITY_WRONG_LOGIN_WAIT);
		}
	}

	/**
	 * Check whether or not an IP address is banned
	 *
	 * This method will also clean up the ban table for records that are no longer used.
	 *
	 * @return Boolean Returns TRUE if an IP address is recorded as banned, FALSE otherwise
	 */
	private function _isIPBanned()
	{
		// If the "login_banned_ip" table is not yet available, then bypass procedure and just return a FALSE
		// (ie. Returning FALSE means the IP is NOT banned)
		if (!$this->_failedLoginSecurityAvailable()) {
			return false;
		}

		$db = IEM::getDatabase();

		$tablePrefix = SENDSTUDIO_TABLEPREFIX;
		$ip = GetRealIp(true);
		$now = time();


		// This shuld always NEVER happened, but as a precaution, we need to ban empty IPs
		if (empty($ip)) {
			return true;
		}

		// ----- Clean up unused record
			$status = $db->Query("DELETE FROM {$tablePrefix}login_banned_ip WHERE bantime < {$now}");
			if ($status === false) {
				trigger_error('Cannot clean up unused record from ban table', E_USER_WARNING);
			}
		// -----

		// ----- Check if the IP has exceeded login threshold
			$query = "
				SELECT ipaddress
				FROM {$tablePrefix}login_banned_ip
				WHERE	ipaddress = '{$ip}'
						AND bantime >= {$now}
			";

			$status = $db->Query($query);
			if ($status === false) {
				trigger_error('Cannot query ban table', E_USER_WARNING);
				return true;
			}

			$row = $db->Fetch($status);
			$db->FreeResult($status);

			if (empty($row)) {
				return false;
			}
		// -----


		return true;
	}

	/**
	 * Check whether or not "Failed Login Security" is available
	 * @return Boolean Returns TRUE if it is available, FALSE otherwise
	 */
	private function _failedLoginSecurityAvailable()
	{
		$db = IEM::getDatabase();

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "SHOW TABLES LIKE '" . SENDSTUDIO_TABLEPREFIX . "login_banned_ip'";
		} else {
			$query = "SELECT table_name FROM information_schema.tables WHERE table_name='" . SENDSTUDIO_TABLEPREFIX . "login_banned_ip'";
		}

		$result = $db->Query($query);
		if ($result === false) {
			trigger_error('Cannot query database for table availibility', E_USER_WARNING);
			return false;
		}

		$row = $db->Fetch($result);
		$db->FreeResult($result);

		return (!empty($row));
	}

	/**
	 * _allowLoginBasedOnIP
	 * Check whether or not user can login based on the user IP address
	 *
	 * @param Array $allowable_ip An array of allowable IP addresses
	 * @param Boolean $only_from_allowable Whether or not it should use the allowable IP list
	 *
	 * @return Boolean Returns TRUE if the user can login as an admin, FALSE otherwise
	 */
	private function _allowLoginBasedOnIP($allowable_ip, $only_from_allowable)
	{
		if (!$only_from_allowable) {
			return true;
		}

		if (!is_array($allowable_ip)) {
			return false;
		}

		return (in_array($_SERVER['REMOTE_ADDR'], $allowable_ip));
	}
}
