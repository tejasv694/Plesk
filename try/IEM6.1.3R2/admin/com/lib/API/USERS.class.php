<?php
/**
 * This file contains API_USERS static class definition.
 * @author Chris <chris@interspire.com>
 *
 * @package interspire.iem.lib.api
 */

/**
 * USER API static class
 *
 * This class provides an encapsulation for accessing a collection of users and users related
 * information from the database.
 *
 * @package interspire.iem.lib.api
 *
 * @todo Move all related functionalities from Users_API to this class
 */
class API_USERS extends IEM_baseAPI
{
	// --------------------------------------------------------------------------------
	// Methods needed to be extended from the parent class
	// --------------------------------------------------------------------------------
		/**
		 * Get record list (or number of records)
		 * This method will fetch a list of records or the number of available records from the database
		 *
		 * NOTE: The parameter condition is an associative array, where the key correspond to the column name
		 * in the database.
		 *
		 * NOTE: If $limit parameter is NOT set (ie. set to 0), $offset will be ignored.
		 *
		 * NOTE: Both $limit and $offset is an absolute value (ie. It will be positive integer number)
		 *
		 * NOTE: $sortdesc parameter will be ignored when $sortby parameter is not set (ie. FALSE or empty)
		 *
		 * @param boolean $countonly When this is set to TRUE, it will only return the number of records available
		 * @param array $condition Conditions to be applied to the search (OPTIONAL, default = FALSE)
		 * @param integer $limit Maximum number of records returned, when 0 is specified no maximum limit is set (OPTIONAL, default = 0)
		 * @param integer $offset Starting record offset (OPTIONAL, default = 0)
		 * @param string $sortby Column name that will be used to sort result (OPTIONAL, default = FALSE)
		 * @param boolean $sortdesc When this parameter is set to TRUE, it will sort result descendingly (OPTIONAL, default - FALSE)
		 *
		 * @return array|integer|false Returns list of available records, the number of available records, or FALSE if it encounter any errors.
		 *
		 * FIXME implement
		 */
		static public function getRecords($countonly = false, $condition = false, $limit = 0, $offset = 0, $sortby = false, $sortdesc = false) { }

		/**
		 * Get record by ID
		 * This method will fetch a record from the database.
		 *
		 * @param integer $id Record ID to fetch
		 * @return record_Users|FALSE Returns base record if successful, FALSE otherwise
		 */
		static public function getRecordByID($id)
		{
			$userid = intval($id);
			$db = IEM::getDatabase();

			$rs = $db->Query("SELECT * FROM [|PREFIX|]users WHERE userid = {$userid}");
			if (!$rs) {
				throw new exception_IEM_baseAPI('Cannot query database -- ' . $db->Error(), exception_IEM_baseAPI::UNABLE_TO_QUERY_DATABASE);
			}

			$row = $db->Fetch($rs);
			$db->FreeResult($rs);

			if (empty($row)) {
				return false;
			}

			return new record_Users($row);
		}


		/**
		 * Delete record by ID
		 * This method will delete record from database
		 *
		 * @param integer $id ID of the record to be deleted
		 * @param boolean $deleteAllOwnedData Whether or not to delete all data associated with this user
		 *
		 * @return boolean Returns TRUE if successful, FALSE otherwise
		 */
		static public function deleteRecordByID($id, $deleteAllOwnedData = true)
		{
			if ($deleteAllOwnedData) {
				$obj    = new HelperUserDelete();
				$status = $obj->deleteUsers(array($id));

				if ($status[$id]['status'] === false) {
					return false;
				} else {
					return true;
				}
			}

			$userid = intval($id);
			$db     = IEM::getDatabase();

			$db->StartTransaction();

			$query  = "DELETE FROM [|PREFIX|]users WHERE userid = {$userid}";
			$result = $db->Query($query);
			
			if (!$result) {
				$db->RollbackTransaction();
				
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' - Unable to delete user record' . $this->Db->Error(), E_USER_NOTICE);
				
				return false;
			}

			if (!del_user_dir($userid)) {
				$db->RollbackTransaction();
				
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' - User files/data was not found?', E_USER_NOTICE);
				
				return false;
			}

			$db->CommitTransaction();
			
			return true;
		}

		/**
		 * Save record
		 * This method will create/edit record in the database
		 *
		 * NOTE: You can pass in an associative array or "record" object.
		 *
		 * NOTE: The action that is taken by the API (either create a new record or edit an existing one)
		 * will depends on the record that is passed in (ie. They have their primary key included or not)
		 *
		 * NOTE: The method will be able to transform the record passed in, by either adding new default value
		 * (or in the case of creating new record, a new id)
		 *
		 * @param array|baseRecord $record Record to be saved
		 * @return boolean Returns TRUE if successful, FALSE otherwise
		 *
		 * @todo ALL
		 * FIXME this function is NOT yet working
		 */
		static public function saveRecord(&$record)
		{

		}
	// --------------------------------------------------------------------------------

	/**
	 * Returns a record_Users object from the passed parameter.
	 * 
	 * @param int|record_Users $user
	 * 
	 * @return record_Users
	 */
    static public function getUser($user)
    {
        if ($user instanceof record_Users) {
			return $user;
        }
        
		return self::getRecordByID($user);
    }

	/**
	 * Generate password hash
	 *
	 * @param String $password Plaintext password
	 * @param String $token User token
	 *
	 * @return String Returns a password hash
	 */
	static public function generatePasswordHash($password, $token)
	{
		return md5(md5($token) . md5($password));
	}

	/**
	 * Generate and return a new unique token
	 *
	 * A unique token generated from this method will have a maximum length of 128.
	 * It will also use the username as an additional salt to the token.
	 *
	 * @param String $username The username which the token will be generated against
	 * @return String Returns a new unique token
	 */
	static public function generateUniqueToken($username)
	{
		$token = time() . rand(10, 5000) . sha1(rand(10, 5000)) . md5(__FILE__);
		$token = str_shuffle($token);
		$token = sha1($token) . md5(microtime()) . md5($username);

		return $token;
	}

	/**
	 * Get schedule list for user
	 * This function used to get the scheduled list for specific user ID, if the user is admin it will show
	 * all the job. If count is provided will return the number of job.
	 *
	 * @param int $userid ID of the user that need to have it's schedule fetched
	 * @param boolean $countonly Flag whether or not to perform full operation or count operation
	 * @param int $start number to start page offset
	 * @param int $perpage  number to display per page
	 * @param boolean $include_unapproved to include approved job or not
	 * @param boolean $chronological order by asc or desc based on job status
	 *
	 * @return array|FALSE return array if there are result, false otherwise
	 *
	 * @FIXME this function is not yet working properly... Please do not use.
	 */
	static public function getUserScheduleList($userid, $countonly, $start, $perpage, $include_unapproved, $chronological = true)
	{
		$userid = intval($userid);
		$start = intval($start);
		$perpage = intval($perpage);

		$db = IEM::getDatabase();
		$user = self::getRecordByID($userid);

		$return_value;
		$query;
		$query_condition = '';

		// If Admin, allow user to view all schedule
		if (!$user->isAdmin()) {
			$query_condition = "j.ownerid = {$userid}";
		}

		if (!$include_unapproved) {
			if (!empty($query_condition)) {
				$query_condition .= ' AND ';
			}

			$query_condition .= 'j.approved <> 0';
		}

		if (!empty($query_condition)) {
			$query_condition = " WHERE " . $query_condition;
		}

		if ($countonly) {
			$query = "SELECT COUNT(1) AS record_number FROM [|PREFIX|]jobs AS j {$query_condition}";
		} else {
				$query = "
					SELECT	j.*,
							l.name as listname, n.name, n.subject, n.newsletterid

					FROM 	[|PREFIX|]jobs AS j,
							[|PREFIX|]newsletters AS n,
							[|PREFIX|]jobs_lists AS jl,
							[|PREFIX|]lists AS l";

				if (!empty($query_condition)) {
					$query .= "{$query_condition} AND ";
				} else {
					$query .= " WHERE ";
				}

				$query .= " j.fkid = n.newsletterid
							AND j.jobid = jl.jobid
							AND jl.listid = l.listid
							AND j.jobtype = 'send'";

				if ($chronological) {
					$query .= "ORDER BY jobstatus ASC, jobtime ASC";
				} else {
					$query .= "ORDER BY jobstatus ASC, jobtime DESC";
				}

				$query .= " LIMIT {$start}, {$perpage} ";
		}

		$rs = $db->Query($query);
		if (!$rs) {
			trigger_error(__CLASS__ . "::" . __METHOD__ . ' -- Unable to query database -- ' . $db->Error(), E_USER_WARNING);
			return false;
		}

		if ($countonly) {
			$return_value = $db->FetchOne($rs, 'record_number');
		} else {
			$return_value = array();
			while ($row = $db->Fetch($rs)) {
				$return_value[] = $row;
			}
		}

		$db->FreeResult($rs);

		return $return_value;
	}


	// --------------------------------------------------------------------------------
	// Methods related to credits
	// --------------------------------------------------------------------------------
		const CREDIT_USAGETYPE_SENDCAMPAIGN			= 'send_campaign';
		const CREDIT_USAGETYPE_SENDTRIGGER			= 'send_trigger';
		const CREDIT_USAGETYPE_SENDAUTORESPONDER	= 'send_autoresponder';


		/**
		 * Record credit usage
		 * This function will record credit usage for a particular user.
		 *
		 * @param record_Users|integer $user User record object or user ID
		 * @param string $usagetype Usage type (see class constansts CREDIT_USAGETYPE_* for valid types)
		 * @param integer $creditused The number of credits that are being used up
		 * @param integer $jobid Associate job ID (OPTIONAL, default = 0)
		 * @param integer $statid Associate statistic ID (OPTIONAL, default = 0)
		 * @param integer $time Time of which the credit is being used (OPTIONAL, default = now)
		 *
		 * @return boolean Returns TRUE if successful, FALSE otherwise
		 */
		static public function creditUse($user, $usagetype, $creditused, $jobid = 0, $statid = 0, $time = 0, $evaluateWarnings = true)
		{
			$userid = 0;
			$usagetype = strtolower($usagetype);
			$creditused = intval($creditused);
			$jobid = intval($jobid);
			$statid = intval($statid);
			$time = intval($time);
			$db = IEM::getDatabase();

			static $validTypes = null;
			
			if (is_null($validTypes)) {
				$validTypes = array(
					self::CREDIT_USAGETYPE_SENDAUTORESPONDER,
					self::CREDIT_USAGETYPE_SENDCAMPAIGN,
					self::CREDIT_USAGETYPE_SENDTRIGGER
				);
			}

			if (!($user instanceof record_Users)) {
				$userid = intval($user);
				$user = API_USERS::getRecordByID($userid);
			}


			if (!$user) {
				trigger_error("API_USERS::creditUse -- Invalid user specified.", E_USER_NOTICE);
				return false;
			}

			if (!in_array($usagetype, $validTypes)) {
				trigger_error("API_USERS::creditUse -- Invalid credit type '{$usagetype}'.", E_USER_NOTICE);
				return false;
			}

			if ($creditused < 1) {
				trigger_error("API_USERS::creditUse -- Credit cannot be less than 1.", E_USER_NOTICE);
				return false;
			}

			if ($jobid < 0) {
				trigger_error("API_USERS::creditUse -- Invalid jobid specified.", E_USER_NOTICE);
				return false;
			}

			if ($statid < 0) {
				trigger_error("API_USERS::creditUse -- Invalid statid specified.", E_USER_NOTICE);
				return false;
			}

			if ($time < 0) {
				trigger_error("API_USERS::creditUse -- Time cannot be negative.", E_USER_NOTICE);
				return false;
			}

			// If user has unlimited emails credit, we don't need to record this
			$usersApi = new User_API($user->userid);

			if ($usersApi->hasUnlimitedCredit()) {
				return true;
			}

            // Check for cases (based on usage type) where credit does not need to be deducted
			switch ($usagetype) {
				case self::CREDIT_USAGETYPE_SENDTRIGGER:
					if (!SENDSTUDIO_CREDIT_INCLUDE_TRIGGERS) {
						return true;
					}
				break;

				case self::CREDIT_USAGETYPE_SENDAUTORESPONDER:
					if (!SENDSTUDIO_CREDIT_INCLUDE_AUTORESPONDERS) {
						return true;
					}
				break;
			}

			$time = ($time == 0 ? time() : $time);

			$db->StartTransaction();

			$tempStatus = $db->Query("
				INSERT INTO [|PREFIX|]user_credit (userid, transactiontype, transactiontime, credit, jobid, statid)
				VALUES ({$userid}, '{$usagetype}', {$time}, -{$creditused}, {$jobid}, {$statid})
			");

			if (!$tempStatus) {
				$db->RollbackTransaction();
				trigger_error("API_USERS::creditUse -- Unable to insert credit usage into database: " . $db->Error(), E_USER_NOTICE);
				return false;
			}
			
			/**@TODO REMOVE ALL REFERENCES TO OLD CREDIT SYSTEM
			/*
			// Record this in the credit summary table
			$tempTimeperiod = mktime(0, 0, 0, date('n'), 1, date('Y'));
			$tempQuery;

			// Since MySQL have a direct query which will insert/update in one go, we can utilzie this.
			if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
				$tempQuery = "
					INSERT INTO [|PREFIX|]user_credit_summary (userid, startperiod, credit_used)
					VALUES ({$userid}, {$tempTimeperiod}, {$creditused})
					ON DUPLICATE KEY UPDATE credit_used = credit_used + {$creditused}
				";


			// Do we need to do an INSERT or an UPDATE query ??
			} else {
				$tempRS = $db->Query("SELECT usagesummaryid FROM [|PREFIX|]user_credit_summary WHERE userid = {$userid} AND startperiod = {$tempTimeperiod}");
				if (!$tempRS) {
					$db->RollbackTransaction();
					trigger_error("API_USERS::creditUse -- Cannot query user_credit_summary table: " . $db->Error(), E_USER_NOTICE);
					return false;
				}

				if ($db->CountResult($tempRS) == 0) {
					$tempQuery = "
						INSERT INTO [|PREFIX|]user_credit_summary (userid, startperiod, credit_used)
						VALUES ({$userid}, {$tempTimeperiod}, {$creditused})
					";
				} else {
					$tempSummaryID = $db->FetchOne($tempRS, 'usagesummaryid');

					$tempQuery = "
						UPDATE [|PREFIX|]user_credit_summary
						SET credit_used = credit_used + {$creditused}
						WHERE usagesummaryid = {$tempSummaryID}
					";
				}

				$db->FreeResult($tempRS);
			}

			$tempStatus = $db->Query($tempQuery);
			
			if (!$tempStatus) {
				$db->RollbackTransaction();
				
				trigger_error("API_USERS::creditUse -- Unable to update/insert user_credit_summary table: " . $db->Error(), E_USER_NOTICE);
				
				return false;
			}*/

			$db->CommitTransaction();

			if ($evaluateWarnings) {
				return self::creditEvaluateWarnings($userid);
			} else {
				return true;
			}
		}

		/**
		 * Not implemented.
		 * 
		 * @param $user
		 * @param $credit
		 * @param $expiry
		 * @return unknown_type
		 */
		static public function creditAdd($user, $credit, $expiry)
		{
            
		}
		
		static public function creditAvailableThisHour($user)
		{
			$user      = self::getUser($user);
			$userGroup = API_USERGROUPS::getRecordById($user->groupid);
			$limit     = (int) $userGroup['limit_hourlyemailsrate'];
            
			// No limit, returns TRUE
			if ($limit == 0) {
				return true;
			}
			
			$used = $user->getUsedHourlyCredit();
			
			if (!$used) {
			    return $limit;
			}
            
			return $limit - $used;
		}

		/**
		 * Get available monthy credit for this month
		 *
		 * @param record_Users|integer $user User record object or user ID
		 * @param boolean $percentage Whether or not you want to return the available credit as a percentage
		 * @return integer|boolean Returns TRUE if user has unlimited credit, an integer if user has a limit, FALSE if it encountered any error
		 */
		static public function creditAvailableThisMonth($user, $percentage = false, $queuetime = 0)
		{
			$user      = self::getUser($user);
			$userGroup = API_USERGROUPS::getRecordById($user->groupid);
			$limit     = (int) $userGroup['limit_emailspermonth'];
            
			// No limit, returns TRUE
			if ($limit == 0) {
				return true;
			}
			
			// get the amount of used credit
			$used = $user->getUsedMonthlyCredit($queuetime);
			
			// If no credits have been used this month, return permonth
			if (!$used) {
				return $limit;
			}
			
			// calculate how much credit is left
			$tempCreditLeft = $limit - ABS($used);

			if (!$percentage) {
				return $tempCreditLeft;
			}

			return ($tempCreditLeft / $limit * 100);
		}

		/**
		 * Get available fixed credit.
		 *
		 * @param record_Users|integer $user User record object or user ID
		 * 
		 * @return integer|boolean Returns TRUE if user has unlimited credit, an integer if user has a limit, FALSE if it encountered any error
		 */
		static public function creditAvailableFixed($user)
		{
			$user      = self::getUser($user);
			$userGroup = API_USERGROUPS::getRecordById($user->groupid);
			$limit     = (int) $userGroup['limit_totalemailslimit'];
			
			if ($limit == 0) {
			    return true;
			}
			
			$used = $user->getUsedCredit();
			
			if (!$used) {
			    return $limit;
			}
			
			return $limit - $used;
		}

		/**
		 * Get total available credit
		 *
		 * @param record_Users|integer $user User record object or user ID
		 * @return integer|boolean Returns TRUE if user has unlimited credit, an integer if user has a limit, FALSE if it encountered any error
		 *
		 * @todo all
		 */
		static public function creditAvailableTotal($user)
		{
			$db = IEM::getDatabase();
			$userobject = null;

			if ($user instanceof record_Users) {
				$userobject = $user;
			} else {
				$userobject = self::getRecordByID($user);
			}

			if (empty($userobject)) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- User is not specified', E_USER_NOTICE);
				
				return false;
			}

			$fixed   = self::creditAvailableFixed($userobject);
			$monthly = self::creditAvailableThisMonth($userobject);
			$hourly  = self::creditAvailableThisHour($userobject);

			// If either functions return FALSE, propagate it.
			if ($fixed === false || $monthly === false || $hourly === false) {
				return false;
			}

			if ($fixed === true) {
				return $monthly;
			} elseif ($monthly === true) {
				return $fixed;
			}

			return $fixed + $monthly + $hourly;
		}

		/**
		 * Evaluate credit warning conditions
		 *
		 * This method will evaluate credit warnings for a particular user.
		 * It will dispatch warning emails accrodingly.
		 *
		 * @param record_Users|integer $user User record object or user ID
		 * @return boolean Returns TRUE if successful, FALSE otherwise
		 *
		 * @todo fixed credits does not have warnings yet
		 */
		static public function creditEvaluateWarnings($user)
		{
			
			$userobject = null;
			$warnings = null;
			$this_month = mktime(0, 0, 0, date('n'), 1, date('Y'));
			$credit_left = null;


			// ----- PRE
				if ($user instanceof record_Users) {
					$userobject = $user;
				} else {
					$userobject = self::getRecordByID($user);
				}

				if (empty($userobject)) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- User is not specified', E_USER_NOTICE);
					return false;
				}
			// -----

			// Credit warnings are not enabled
			if (!SENDSTUDIO_CREDIT_WARNINGS) {
				return true;
			}


			require_once(IEM_PUBLIC_PATH . '/functions/api/settings.php');
			$tempSettingsAPI = new Settings_API();
			$warnings = $tempSettingsAPI->GetCreditWarningsSettings();

			// Does not hany any warnings setup? Well... we can't continue then.
			if (empty($warnings)) {
				return true;
			}

			$credit_left = self::creditAvailableTotal($userobject);
			
			//unlimited credit
			if($credit_left === true){
			    return true;
			}
			
			$whichlevel = self::creditWhichWarning($userobject, $credit_left, $warnings);

			// If $whichlevel contains FALSE, that means there was something wrong
			// when trying to figure out which warning level it should send out.
			if ($whichlevel === false) {
				return true;
			}
			
			$userGroup = API_USERGROUPS::getRecordById($userobject->groupid);
            if(!isset($userGroup['limit_emailspermonth'])){ return false; }
            $userobject_permonth = (int)$userGroup['limit_emailspermonth'];

			$fixed   = self::creditAvailableFixed($userobject);
			$monthly = self::creditAvailableThisMonth($userobject);
			
		    if($fixed === true) {
		        $userobject_permonth = $monthly;
		    } elseif ($monthly === true) {
				$userobject_permonth = $fixed;
			}	


			if (!empty($whichlevel)) {
				$tempNames = explode(' ', $userobject->fullname);
				$tempLastName = array_pop($tempNames);
				$tempFirstName = implode(' ', $tempNames);

				$available_custom_fields_key = array(
					'%%user_fullname%%',
					'%%user_firstname%%',
					'%%user_lastname%%',
					'%%credit_total%%',
					'%%credit_remains%%',
					'%%credit_remains_precentage%%',
					'%%credit_used%%',
					'%%credit_used_percentage%%'
				);

				$available_custom_fields_value = array(
					$userobject->fullname,
					$tempFirstName,
					$tempLastName,
					$userobject_permonth,
					intval($userobject_permonth * ($credit_left / 100)),
					intval($credit_left),
					intval($userobject_permonth * ((100 - $credit_left) / 100)),
					intval(100 - $credit_left)
				);

				$email_contents = str_replace($available_custom_fields_key, $available_custom_fields_value, $whichlevel['emailcontents']);
				$email_subject = str_replace($available_custom_fields_key, $available_custom_fields_value, $whichlevel['emailsubject']);

				// ----- We found which warnings it is that we want to send out
					require_once(IEM_PATH . '/ext/interspire_email/email.php');
					$emailapi = new Email_API();
					$emailapi->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);
					if ($userobject->smtpserver) {
						$emailapi->SetSmtp($userobject->smtpserver, $userobject->smtpusername, $userobject->smtppassword, $userobject->smtpport);
					}
					$emailapi->ClearRecipients();
					$emailapi->ForgetEmail();
					$emailapi->Set('forcechecks', false);
					$emailapi->AddRecipient($userobject->emailaddress, $userobject->fullname, 't');
					$emailapi->Set('FromName', false);
					$emailapi->Set('FromAddress', (defined('SENDSTUDIO_EMAIL_ADDRESS') ? SENDSTUDIO_EMAIL_ADDRESS : $userobject->emailaddress));
					$emailapi->Set('BounceAddress', SENDSTUDIO_EMAIL_ADDRESS);
					$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);
					$emailapi->Set('Subject', $email_subject);
					$emailapi->AddBody('text', $email_contents);
					$status = $emailapi->Send();
					if ($status['success'] != 1) {
						trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Was not able to send email: ' . serialize($status['failed']), E_USER_NOTICE);
						return false;
					}
				// -----

				// ----- Update user record
					$db = IEM::getDatabase();
					$status = $db->Query("UPDATE [|PREFIX|]users SET credit_warning_time = {$this_month}, credit_warning_percentage = {$whichlevel['creditlevel']} WHERE userid = {$userobject->userid}");

					// Update user object in session
					// FIXME, we really need to make a special getter/setter for this
					$current_user = IEM::getCurrentUser();
					if ($current_user && $current_user->userid == $userobject->userid) {
						$current_user->credit_warning_time = $this_month;
						$current_user->credit_warning_percentage = $whichlevel['creditlevel'];
					}
				// -----
			}

			return true;
		}

		/**
		 * Work out which warning message need to be sent out
		 *
		 * This function will return appropriate warning records according to user's information.
		 * By itself it will not do anything (ie. will not be affecting anything in the system).
		 *
		 * NOTE:
		 * - The available warnings record can be fetch from Settings_API::GetCreditWarningsSettings() method for now
		 *
		 * @param record_Users $userobject User object
		 * @param integer $user_monthly_credit_available Currently available credit
		 * @param array $available_warnings Available warnings record
		 *
		 * @return array|FALSE Return credit warnings record (empty array if no warnings are necessary), FALSE otherwise
		 */
		static public function creditWhichWarning($userobject, $user_monthly_credit_available, $available_warnings)
		{
			if (empty($available_warnings)) {
				return false;
			}

			if (!($userobject instanceof record_Users)) {
				return false;
			}

			$this_month = mktime(0, 0, 0, date('n'), 1, date('Y'));
			$whichlevel = array(); // The default warning level is empty array

			// If warning has been sent out (this month), do not continue:
			// - credit_warning_percentage is smaller than $credit_left
			// - credit_warning_percentage is NOT null
			if ($userobject->credit_warning_time >= $this_month
					&& ($userobject->credit_warning_percentage <= $user_monthly_credit_available
						&& !is_null($userobject->credit_warning_percentage))) {

				return $whichlevel;
			}

			foreach ($available_warnings as $warning) {
				// If credit level is smaller than credit_left, continue
				if ($warning['creditlevel'] < $user_monthly_credit_available) {
					continue;
				}

				// Only take the smallest value
				if (!empty($whichlevel) && $whichlevel < $warning['creditlevel']) {
					continue;
				}

				// If the warning is not enabled, continue
				if (!$warning['enabled']) {
					continue;
				}

				// Because we only evaluate "monthly warnings", we skip any fix credit warnings
				if (!$warning['aspercentage']) {
					continue;
				}

				// Skip any warnings that have been sent out this month
				if ($userobject->credit_warning_time >= $this_month
						&& ($warning['creditlevel'] >= $userobject->credit_warning_percentage)
							&& !is_null($userobject->credit_warning_percentage)) {

					continue;
				}

				$whichlevel = $warning;
			}

			return $whichlevel;
		}
	// --------------------------------------------------------------------------------
}