<?php
/**
 * This file contains record_Users class definition
 * @author Chris <chris@interspire.com>
 *
 * @package interspire.iem.lib.record
 */

/**
 * User Record class definition
 *
 * This class will provide encapsulation to access record level information from a database.
 * It mainly provide an interface for developers to have their code cleaner.
 *
 * NOTE:
 * - There are two valid types of user status currently available 0 and 1 to indicate "Active" and "Inactive" status.
 * - Two types of "admintype" is currently recognized a and c to indicate "System Admin" and "Regular User".
 * - Two types of "listadmintype": a and c to indicate "List Admin" and "Non list admin" -- This is hardly used, its use might need to be reconsidered.
 * - Two types of "segmentadmintype": a and c to indicate "Segment Admin" and "Non segment admin" -- This is hardly used, its use might need to be reconsidered.
 *
 * @property integer $userid User ID
 * @property integer $groupid User group ID
 * @property string $username Username
 * @property string $password User password
 * @property string $status User status. See note above about user status
 * @property string $admintype Admin type. See note above about admin type
 * @property string $listadmintype List Admin type. See not above about list admin type
 *
 * @property integer $permonth Email limit per month
 *
 * @property integer $credit_warning_time Time of which last warning was sent
 * @property integer $credit_warning_percentage The percentage level of credit when user received the last warning (used by monthly credit)
 * @property integer $credit_warning_fixed The credit level of credit when user received the last warning (used by fixed credit)
 *
 * @package interspire.iem.lib.record
 *
 * @todo all
 */
class record_Users extends IEM_baseRecord
{
	public function __construct($data = array())
	{
		$this->properties = array(
			'userid'			=> null,
			'groupid'			=> null,
			'trialuser'			=> '0',
			'username'			=> null,
			'password'			=> null,
			'status'			=> '0',
			'admintype'			=> 'c',
			'listadmintype'			=> 'c',
			'templateadmintype'		=> 'c',
			'segmentadmintype'		=> 'c',
			'fullname'			=> null,
			'emailaddress'			=> null,
			'settings'			=> null,
			'editownsettings'		=> '0',
			'usertimezone'			=> null,
			'textfooter'			=> null,
			'htmlfooter'			=> null,
			'infotips'			=> '0',
			'smtpserver'			=> null,
			'smtpusername'			=> null,
			'smtppassword'			=> null,
			'smtpport'			=> 0,
			'createdate'			=> 0,
			'lastloggedin'			=> 0,
			'forgotpasscode'		=> null,
			'usewysiwyg'			=> '1',
			'xmlapi'			=> '0',
			'xmltoken'			=> null,
			'gettingstarted'		=> 0,
			'googlecalendarusername'        => null,
			'googlecalendarpassword'	=> null,
			'user_language'			=> 'default',
			'unique_token'			=> null,
			'enableactivitylog'		=> '1',
			'eventactivitytype'		=> null,
			'forcedoubleoptin'		=> '0',
			'credit_warning_time'		=> null,
			'credit_warning_percentage'	=> null,
			'credit_warning_fixed'		=> null,
			'adminnotify_email'		=> null,
			'adminnotify_send_flag'		=> '0',
			'adminnotify_send_threshold'	=> null,
			'adminnotify_send_emailtext'	=> null,
			'adminnotify_import_flag'	=> '0',
			'adminnotify_import_threshold'	=> null,
			'adminnotify_import_emailtext'	=> null,
		);

		parent::__construct($data);
	}
	
	/**
	 * Returns the group associated to the current user.
	 * 
	 * @return record_UserGroups
	 */
	public function getGroup()
	{
		return API_USERGROUPS::getRecordById($this->groupid);
	}

	/**
	 * Returns whether or not the current user is an administrator..
	 * 
	 * @return bool
	 */
	public function isAdmin()
	{
		return $this->getGroup()->isAdmin();
	}
	
	/**
	 * Retrieves the amount of credit the user has used this hour.
	 * 
	 * @return int
	 */
	public function getUsedHourlyCredit()
	{
        $db    = IEM::getDatabase();
	    $query = "
			SELECT
				SUM(emailssent) as sendsize
			FROM
				[|PREFIX|]user_stats_emailsperhour
			WHERE
				userid = {$this->userid}
		";

            $thisHour = AdjustTime(array (date('H')    , 0, 0, date('n'), date('j'), date('Y')), true, null, true);
            $nextHour = AdjustTime(array (date('H') + 1, 0, 0, date('n'), date('j'), date('Y')), true, null, true);

	    if ($thisHour) {
	    	if (is_numeric($thisHour)) {
	        	strtotime($thisHour);
	    	}

	        $query .= ' AND sendtime >= ' . $thisHour;
	    }

	    if ($nextHour) {
	    	if (is_numeric($nextHour)) {
	        	strtotime($nextHour);
	    	}

	        $query .= ' AND sendtime < ' . $nextHour;
	    }

	    $query   .= ' AND statid != 0';
	    $result   = $db->FetchOne($query);
        $credits  = (int) $result;
        
        $items = 0;
        $result = $db->Query("
                SELECT
                        jobdetails
                FROM
                        [|PREFIX|]jobs
                WHERE
                        ownerid    = {$this->userid} AND
                        approved  != 0               AND
                        jobtype    = 'send'          AND
                        jobstatus != 'c'
        ");

        // add on the current queue
        while ($row = $db->Fetch($result)) {
                $jobdetails = unserialize($row['jobdetails']);
                $sendsize = (int)$jobdetails['SendSize'];
                $items = $items + $sendsize;
        }
        
        $credits = $credits + $items;
        $db->FreeResult($result);
        
        return $credits;
	}
	
	/**
	 * Retrieves the amount of credit the user has used this month.
	 * 
	 * @return int
	 */
	public function getUsedMonthlyCredit($queuetime = 0)
	{
	    $db = IEM::getDatabase();
        $months = 0;
        if ($queuetime == 0) {
			$queuetime = time();
			$months = 0;
		} else {
			if(date("n",$queuetime) != date("n")){
				$months = date("n",$queuetime) - date("n");
			}
		}
		$thisMonth = mktime(0,0,0,date('n',$queuetime),1,date("Y",$queuetime));
		
		$nextMonth = mktime(0,0,0,(date('n',$queuetime)+1),1,date("Y",$queuetime));
		
		$query = "
				SELECT
						SUM(htmlrecipients+textrecipients+multipartrecipients)
				FROM
						[|PREFIX|]stats_newsletters
				WHERE
						sentby = {$this->userid} AND
						starttime >= {$thisMonth} AND
						starttime < {$nextMonth}
		";
		
		$result = $db->Query($query);
		
		if(!$result){
			trigger_error(mysql_error(),E_USER_WARNING);
		}

       	$credits = (int) $db->FetchOne($result);
	   
       	$db->FreeResult($result);
//Add on for future sends only if they are at least 1 month away and have not started sending - otherwise it's added from stats_newsletters above
		if($months >= 1){
			$query = "
					SELECT
							jobdetails
					FROM
							[|PREFIX|]jobs
					WHERE
							ownerid = {$this->userid} AND
							jobtime >= {$thisMonth} AND
							jobtime < {$nextMonth} AND
							jobstatus = 'w' AND
							queueid = 0
			";
			
			$result = $db->Query($query);
			
			if(!$result){
				trigger_error(mysql_error(),E_USER_WARNING);
			}
						
			while ($row = $db->Fetch($result)) {
					$jobdetails = unserialize($row['jobdetails']);
					$sendsize = (int)$jobdetails['SendSize'];
					$items = $items + $sendsize;
			}
			
			$credits = $credits + $items;
			
			$db->FreeResult($result);	
		}
//Add on credits for triggers, autoresponders, and manual adjustments (@TODO manual adjustments)
		$query = "
				SELECT
						SUM(credit)
				FROM
						[|PREFIX|]user_credit
				WHERE
						userid = {$this->userid} AND
						transactiontime >= {$thisMonth} AND
						transactiontime < {$nextMonth} AND
						transactiontype != 'send_campaign'
		";
		
		$result = $db->Query($query);
		
		if(!$result){
			trigger_error(mysql_error(),E_USER_WARNING);
		}
		
		$add_credits = (int)$db->FetchOne($result);
		
		if($add_credits > 0){
			$credits += $add_credits;
		}else if($add_credits < 0){
			$credits -= $add_credits;
		}
	   
       	$db->FreeResult($result);
			   
       	return $credits;
	}
	
	/**
	 * Retrieves the amount of credit used in total by the current user.
	 * 
	 * @return int
	 */
	public function getUsedCredit()
	{
	    $db    = IEM::getDatabase();
				
		$query = "
				SELECT
						SUM(htmlrecipients+textrecipients+multipartrecipients)
				FROM
						[|PREFIX|]stats_newsletters
				WHERE
						sentby  = {$this->userid} AND
						starttime != 0
		";
		
		$result = $db->Query($query);
		
		if(!$result){
			trigger_error(mysql_error(),E_USER_WARNING);
		}

       	$credits = (int) $db->FetchOne($result);
	   
       	$db->FreeResult($result);
//Add on credits for triggers, autoresponders, and manual adjustments (@TODO manual adjustments)
		$query = "
				SELECT
						SUM(credit)
				FROM
						[|PREFIX|]user_credit
				WHERE
						userid = {$this->userid} AND
						transactiontype != 'send_campaign'
		";
		
		$result = $db->Query($query);
		
		if(!$result){
			trigger_error(mysql_error(),E_USER_WARNING);
		}
		
		$add_credits = (int)$db->FetchOne($result);
		
		if($add_credits > 0){
			$credits += $add_credits;
		}else if($add_credits < 0){
			$credits -= $add_credits;
		}
	   
       	$db->FreeResult($result);
					   
       	return $credits;
	}
}
