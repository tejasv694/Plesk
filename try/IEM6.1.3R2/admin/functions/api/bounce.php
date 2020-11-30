<?php
/**
 * Bounce Processing API.
 *
 * @version     $Id: bounce.php,v 1.42 2008/03/05 04:25:14 chris Exp $
 * @author Chris <chris@interspire.com>
 *
 * @package API
 * @subpackage Bounce_API
 */

/**
 * Require the base jobs class.
 */
require_once(dirname(__FILE__) . '/jobs.php');

/**
 * Require bounce rule list
 */
require_once dirname(dirname(dirname(__FILE__))) . '/resources/bounce_rules.php';

/**
 * This is the bounce system api. This connects to the email account, gets the number of messages, logs out and so on.
 * It also handles parsing the bounce message according to the bounce rules.
 *
 * The rules for bounce processing are in the language/jobs_bounce.php file.
 *
 * @package API
 * @subpackage Bounce_API
 */
class Bounce_API extends Jobs_API
{

	/**
	 * Whether debug mode for bounce processing is on or off.
	 * Switching this on will use 'LogFile' to save log messages as it goes through the processing routine.
	 *
	 * @see LogFile
	 */
	var $Debug = false;

	/**
	 * Where to save debug messages.
	 *
	 * @see Debug
	 * @see Bounce_API
	 */
	var $LogFile = null;

	/**
	 * ErrorMessage contains the last imap_error message or possibly if imap support is enabled on the server.
	 *
	 * @see Bounce
	 * @see Login
	 *
	 * @var String $ErrorMessage
	 */
	var $ErrorMessage = '';

	/**
	 * Email_API holds the email api class temporarily so we can notify the list admin etc if we need to.
	 *
	 * @see Bounce
	 *
	 * @var Object $Email_API
	 */
	var $Email_API = null;

	/**
	 * Subscriber_API holds the subscriber api class temporarily so we can record bounce info etc.
	 *
	 * @see Bounce
	 *
	 * @var Object $Subscriber_API
	 */
	var $Subscriber_API = null;

	/**
	 * Lists_API holds the list api class temporarily so we can record bounce info etc.
	 *
	 * @see Bounce
	 *
	 * @var Object $Lists_API
	 */
	var $Lists_API = null;

	/**
	 * Stats_API holds the stats api class temporarily so we can record bounce info etc.
	 *
	 * @see Bounce
	 *
	 * @var Object $Stats_API
	 */
	var $Stats_API = null;

	/**
	 * bounceuser The bounce username to log in with.
	 *
	 * @see Login
	 *
	 * @var String
	 */
	var $bounceuser = null;

	/**
	 * bouncepassword The bounce password to log in with.
	 *
	 * @see Login
	 *
	 * @var String
	 */
	var $bouncepassword = null;

	/**
	 * bounceserver The server name to log in to.
	 *
	 * @see Login
	 *
	 * @var String
	 */
	var $bounceserver = null;

	/**
	 * imapaccount Whether we are trying to log in to an imap account or a regular pop3 account.
	 *
	 * @see Login
	 *
	 * @var Boolean
	 */
	var $imapaccount = false;

	/**
	 * extramailsettings Any extra email account settings we may need to use to log in.
	 * For example '/notls' or '/nossl'
	 *
	 * @see Login
	 *
	 * @var String
	 */
	var $extramailsettings = null;

	/**
	 * connection Temporarily store the connection to the email account here for easy use.
	 *
	 * @see Login
	 * @see Logout
	 * @see Delete
	 * @see GetEmailCount
	 * @see GetHeader
	 * @see GetMessage
	 *
	 * @var Resource
	 */
	var $connection = null;

	/**
	 * list_objects
	 * Temporary storage for list settings in case we are processing via cron.
	 * Saves us loading up the same data over and over again if we don't need to.
	 *
	 * @see ProcessEmail
	 */
	var $list_objects = array();

	/**
	 * Regex cache for matching subject rules
	 *
	 * This variable is used by self::ProcessEmail when matching subject to the subject rule.
	 * Processed regex string will be stored here for further use by the function.
	 *
	 * Initial value for this variable should be set to NULL.
	 *
	 * @var string|null
	 * @see self::ProcessEmail()
	 */
	var $_subjectRegex = null;

	/**
	 * The number of bytes to scan in an email to locate the delivery message.
	 * Increasing this too much will considerably slow bounce processing.
	 *
	 * @see ParseBody
	 *
	 * @var Int
	 */
	var $_peek = 10240;

	/**
	 * Will mark a subscriber as bounced for a hard bounce.
	 *
	 * @see HandleBounce
	 *
	 * @var Boolean
	 */
	var $flagHardBounce = true;

	/**
	 * Will mark a subscriber as bounced for a soft bounce.
	 *
	 * @see HandleBounce
	 *
	 * @var Boolean
	 */
	var $flagSoftBounce = false;

	/**
	 * Will delete subscribers who have hard-bounced.
	 *
	 * @see HandleBounce
	 *
	 * @var Boolean
	 */
	var $deleteHardBounce = false;
	

	/**
	 * Bounce
	 * The constructor sets up the required objects, checks for imap support and loads the language files which also load up the bounce rules.
	 *
	 * @return Mixed Returns false if there is no imap support. Otherwise returns true once all the sub-objects are set up for easy access.
	 */
	function Bounce_API()
	{
		if (is_null($this->LogFile)) {
			if (defined('TEMP_DIRECTORY')) {
				$this->LogFile = TEMP_DIRECTORY . '/bounce.debug.log';
			}
		}

		require_once dirname(dirname(__FILE__)) . '/sendstudio_functions.php';
		$temp = new SendStudio_Functions();
		$temp->LoadLanguageFile('jobs_bounce');
		$temp->LoadLanguageFile('jobs_send');
		unset($temp);

		if (is_null($this->Email_API)) {
			if (!class_exists('email_api', false)) {
				require_once(IEM_PATH . '/ext/interspire_email/email.php');
			}
			$email = new Email_API();
			$this->Email_API = &$email;
		}

		if (is_null($this->Subscriber_API)) {
			if (!class_exists('subscribers_api', false)) {
				require_once(dirname(__FILE__) . '/subscribers.php');
			}
			$subscribers = new Subscribers_API();
			$this->Subscriber_API = &$subscribers;
		}

		if (is_null($this->Lists_API)) {
			if (!class_exists('lists_api', false)) {
				require_once(dirname(__FILE__) . '/lists.php');
			}
			$list = new Lists_API();
			$this->Lists_API = &$list;
		}

		if (is_null($this->Stats_API)) {
			if (!class_exists('stats_api', false)) {
				require_once(dirname(__FILE__) . '/stats.php');
			}
			$stats = new Stats_API();
			$this->Stats_API = &$stats;
		}

		$this->GetDb();
		return true;
	}

	/**
	 * Login
	 * Logs in to the email account using the settings provided.
	 *
	 * @see bounceuser
	 * @see bouncepassword
	 * @see bounceserver
	 * @see imapaccount
	 * @see extramailsettings
	 * @see ErrorMessage
	 * @see connection
	 *
	 * @return Boolean Returns true if any of the required parameters are missing (bounceuser, bouncepassword, bounceserver) or if a connection cannot be made. If the details are all present but we can't connect, sets the last error message in ErrorMessage for checking.
	 */
	function Login()
	{
		if (is_null($this->bounceuser) || is_null($this->bouncepassword) || is_null($this->bounceserver)) {
			return false;
		}

		if ($this->imapaccount) {
			if (strpos($this->bounceserver, ':') === false) {
				$connection = '{' . $this->bounceserver . ':143';
			} else {
				$connection = '{' . $this->bounceserver;
			}
		} else {
			if (strpos($this->bounceserver, ':') === false) {
				$connection = '{' . $this->bounceserver . ':110/pop3';
			} else {
				$connection = '{' . $this->bounceserver . '/pop3';
			}
		}

		if ($this->extramailsettings) {
			$connection .= $this->extramailsettings;
		}
		$connection .= '}INBOX';

		$password = @base64_decode($this->bouncepassword);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; connection string: ' . $connection ."\n", 3, $this->LogFile);
			error_log('Line ' . __LINE__ . '; bounceuser: ' . $this->bounceuser ."\n", 3, $this->LogFile);
			error_log('Line ' . __LINE__ . '; password: ' . $password ."\n", 3, $this->LogFile);
		}

		$inbox = @imap_open($connection, $this->bounceuser, $password);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; inbox: ' . $inbox ."\n", 3, $this->LogFile);
		}

		if (!$inbox) {
			$errormsg = imap_last_error();

			$errors = imap_errors();

			if (is_array($errors) && !empty($errors)) {
				$errormsg = array_shift($errors);
			} else {
				$alerts = imap_alerts();
				if (is_array($alerts) && !empty($alerts)) {
					$errormsg = array_shift($alerts);
				}
			}

			$this->ErrorMessage = $errormsg;

			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; imap_errors: ' . print_r(imap_errors(), true) ."\n", 3, $this->LogFile);
				error_log('Line ' . __LINE__ . '; imap_alerts: ' . print_r(imap_alerts(), true) ."\n", 3, $this->LogFile);
			}
			imap_alerts();
			return false;
		}
		imap_errors();
		imap_alerts();

		$this->connection = $inbox;
		return true;
	}

	/**
	 * GetEmailCount
	 * Gets the number of emails in the account based on the current connection.
	 *
	 * @see connection
	 *
	 * @return Mixed Returns false if the connection has not been established, otherwise gets the number of emails and returns that.
	 */
	function GetEmailCount()
	{
		if (is_null($this->connection)) {
			return false;
		}

		$display_errors = @ini_get('display_errors');
		@ini_set('display_errors', false);

		$count = imap_num_msg($this->connection);
		@ini_set('display_errors', $display_errors);
		return $count;
	}

	/**
	 * Logout
	 * Logs out of the established connection and optionally deletes messages that have been marked for deletion. Also resets the class connection variable.
	 *
	 * @param Boolean $delete_messages Whether to delete messages that have been marked for deletion or not.
	 *
	 * @see connection
	 * @see Delete
	 *
	 * @return Boolean Returns false if the connection has not been established previously, otherwise returns true.
	 */
	function Logout($delete_messages=false)
	{
		if (is_null($this->connection)) {
			return false;
		}

		if ($delete_messages) {
			// delete any emails marked for deletion.
			$this->ExpungeEmail();
		}

		imap_close($this->connection);
		$this->connection = null;

		imap_errors();
		imap_alerts();

		return true;
	}

	/**
	 * DeleteEmail
	 * Marks a message for deletion when logging out of the account.
	 *
	 * @param Int $messageid The message to delete when logging out.
	 *
	 * @see connection
	 * @see Logout
	 *
	 * @return Boolean Returns false if there is an invalid message number passed in or if there is no previous connection. Otherwise marks the message for deletion and returns true.
	 */
	function DeleteEmail($messageid=0)
	{
		$messageid = (int)$messageid;
		if ($messageid <= 0) {
			return false;
		}

		if (is_null($this->connection)) {
			return false;
		}

		imap_delete($this->connection, $messageid);
		return true;
	}

	/**
	 * ExpungeEmail
	 * Delete all emails marked for deletion.
	 *
	 * @return Void Does not return anything.
	 */
	function ExpungeEmail()
	{
		imap_expunge($this->connection);
	}

	/**
	 * GetHeader
	 * Gets the email header(s) for a particular message.
	 *
	 * @param Int $messageid The message to get the header(s) for.
	 *
	 * @return Mixed Returns false if the messageid is invalid or if there is no established connection, otherwise returns an object of the headers (per imap_header)
	 */
	function GetHeader($messageid=0)
	{
		$messageid = (int)$messageid;
		if ($messageid <= 0) {
			return false;
		}

		if (is_null($this->connection)) {
			return false;
		}

		$header = @imap_header($this->connection, $messageid);

		if (isset($header->to)) {
			return $header;
		}

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; the email headers werent returned properly. See if they have a utf-8 byte-order-mark in them.' . "\n", 3, $this->LogFile);
		}

		$body = @imap_body($this->connection, $messageid);

		// in some bizarre cases, hotmail returns an email with utf-8 BOM (byte-order-mark) at the start of their bounces.
		// so, in that case we have to do something a little different.
		$headers = preg_match("%^(.*?)\r\n\r\n%s", $body, $matches);

		unset($body);

		if (empty($matches) || !isset($matches[1])) {
			return false;
		}

		$header = $matches[1];
		$imap_headers = imap_rfc822_parse_headers(str_replace('﻿', '', $header));

		if (empty($imap_headers)) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; the email headers are completely invalid - imap cant parse them. Returning false.' . "\n", 3, $this->LogFile);
			}
			return false;
		}

		return $imap_headers;
	}

	/**
	 * GetBounceTime
	 * Gets the bounce time using the udate header from the header object passed in.
	 *
	 * @param Object $header The header object to look for the udate property in.
	 *
	 * @return Mixed Returns false if the header is invalid, otherwise returns the udate property which is in unix-timestamp format.
	 */
	function GetBounceTime($header=false)
	{
		if (!is_object($header)) {
			return false;
		}

		$bounce_time = 0;
		if (isset($header->udate)) {
			$bounce_time = $header->udate;
		}
		return $bounce_time;
	}

	/**
	 * GetBounceFrom
	 * Constructs and returns the from header based on the object passed in.
	 *
	 * @param Object $header The object to find the from details in.
	 *
	 * @return Mixed Returns false if an invalid header object is passed in, otherwise constructs the from address and returns it as a string.
	 */
	function GetBounceFrom($header=false)
	{
		if (!is_object($header)) {
			return false;
		}

		if (!isset($header->from) || empty($header->from)) {
			return false;
		}

		reset($header->from);
		// we can't juse use header->fromaddress because this might contain a name or something.
		// so we reconstruct it ourselves.
		$from_header = current($header->from);

		// If the from header doesn't have a required property return false also
		if (!isset($from_header->mailbox) || !isset($from_header->host)) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; the email from_header object didnt have a required property '.print_r($from_header, true)."\n", 3, $this->LogFile);
			}
			return false;
		}

		$fromaddress = strtolower($from_header->mailbox . '@' . $from_header->host);

		return $fromaddress;
	}

	/**
	 * Get bounce subject
	 *
	 * @param Object $header Header object
	 *
	 * @return String|False Returns email subject if successful, FALSE otherwise
	 */
	function GetBounceSubject($header=false)
	{
		if (!is_object($header)) {
			return false;
		}

		if (!isset($header->subject)) {
			return false;
		}

		return strtolower($header->subject);
	}

	/**
	 * GetBounceMailbox
	 * Returns the 'mailbox' or the first part of an email address (eg 'mailer-daemon') from the header object passed in.
	 *
	 * @param Object $header The header object to get the mailbox details from.
	 *
	 * @return Mixed Returns false if an invalid header object is passed in, otherwise gets the mailbox part of the email address and returns it as a string.
	 */
	function GetBounceMailbox($header=false)
	{
		if (!is_object($header)) {
			return false;
		}

		if (!isset($header->from) || empty($header->from)) {
			return false;
		}

		reset($header->from);
		$from_header = current($header->from);
		$mailbox = strtolower($from_header->mailbox);

		return $mailbox;
	}

	/**
	 * GetMessage
	 * Returns the message body based on the messageid passed in.
	 *
	 * @param Int $messageid The message number to get the email body for.
	 *
	 * @return Mixed Returns false if an invalid message number is passed in or there is an invalid connection. Otherwise returns the whole message body.
	 */
	function GetMessage($messageid=0)
	{
		$messageid = (int)$messageid;
		if ($messageid <= 0) {
			return false;
		}

		if (is_null($this->connection)) {
			return false;
		}

		return imap_body($this->connection, $messageid);
	}

	/**
	 * GetBounceList
	 * Gets the listid from the bounced email's body (passed in).
	 *
	 * @param String $body The message body to look for the listid in.
	 *
	 * @return Mixed Returns false if there is no message body passed in, otherwise returns the x-mailer-listid it found in the body.
	 */
	function GetBounceList($body=false)
	{
		if (!$body) {
			return false;
		}

		$bounce_listids = Array();

		$body = preg_replace('%\s+%', ' ', $body);

		if (preg_match('%x-mailer-lid: ([\d,]+)%i', $body, $match)) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; Found a lid match '.print_r($match, true)."\n", 3, $this->LogFile);
			}
			$bounce_listids = trim($match[1]);
			if (strpos($bounce_listids, ',') !== false) {
				$bounce_listids = explode(',', str_replace(' ', '', $bounce_listids));
			} else {
				$bounce_listids = Array($bounce_listids);
			}
		}

		if (empty($bounce_listids)) {
			if (preg_match('%x-mailer-listid: ([\d+,]*)%i', $body, $match)) {
				if ($this->Debug) {
					error_log('Line ' . __LINE__ . '; Found a lid match '.print_r($match, true)."\n", 3, $this->LogFile);
				}
				$bounce_listid = trim($match[1]);
				$bounce_listids = array($bounce_listid);
			}
		}

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; before calling checkint vars the listids array is '.print_r($bounce_listids, true)."\n", 3, $this->LogFile);
		}

		$bounce_listids = $this->CheckIntVars($bounce_listids);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; after calling checkint vars the listids array is '.print_r($bounce_listids, true)."\n", 3, $this->LogFile);
		}

		unset($body);

		return $bounce_listids;
	}

	/**
	 * GetBounceStat
	 * Gets the statid from the bounced email's body (passed in).
	 *
	 * @param String $body The message body to look for the statid in.
	 *
	 * @return Mixed Returns false if there is no message body passed in, otherwise returns the x-mailer-sid it found in the body.
	 */
	function GetBounceStat($body=false)
	{
		if (!$body) {
			return false;
		}

		$body = preg_replace('%\s+%', ' ', $body);

		$bounce_statid = 0;
		if (preg_match('%x-mailer-sid: ([\d+]*)%i', $body, $match)) {
			$bounce_statid = trim($match[1]);
		}

		unset($body);

		return $bounce_statid;
	}

	/**
	 * ParseBody
	 * This trims the original message from the body passed in, then goes through the body of the email and works out what type of bounce it is.
	 * The only way to know is to use a series of regular expressions (in the GLOBALS['BOUNCE_RULES'] array) and try to match one against the body passed in.
	 * This returns a triple entry array.
	 * The first entry is the bounce type (hard/soft bounce).
	 * The second entry is the bounce group (hard bounce - doesntexist, relayerror, inactive; soft bounce - overquota).
	 * The third entry is the email address that it found to bounce.
	 * If no regular expressions match, then each part is returned as 'false'.
	 *
	 * @param String $body The body to parse and try to match the bounce rules against.
	 *
	 * @return Array Returns a triple element array with the bounce type, bounce group and email address.
	 *
	 * @uses Bounce_API::_ProcessRFC3463Transient()
	 * @uses Bounce_API::_ProcessRFC3463Permanent()
	 */
	function ParseBody($body)
	{
		/**
		 * Don't care what the original message is, get rid of it.
		 */
		$body = preg_replace('%--- Below this line is a copy of the message.(.*)%is', '', $body);
		$body = preg_replace('%------ This is a copy (.*)%is', '', $body);
		$body = preg_replace('%Content-Type: message/rfc822.*%is', '', $body);

		/**
		 * postfix is different (of course).
		 */
		$body = preg_replace('%Content-Description: Delivery report.*\s*?%i', '', $body);

		$body = str_replace("\r", "", $body);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; processing body: ' . $body . "\n", 3, $this->LogFile);
		}

		$body = str_replace(array("\n", "\r"), " ", $body);

		/**
		 * in case the body put extra spacing after newlines, get rid of them.
		 */
		$body = preg_replace('%\s+%', ' ', $body);

		/**
		 * Get email addresses
		 */
		preg_match_all("%\b([^@\s]+@[a-zA-Z0-9\-][a-zA-Z0-9\-\.]{0,254})\b%is", $body, $email_matches);
		$emails_to_return = Array();
		foreach ($email_matches[1] as $p => $emailaddress) {
			if (strpos($emailaddress, 'postmaster') !== false) {
				continue;
			}
			if (!in_array($emailaddress, $emails_to_return)) {
				$emails_to_return[] = $emailaddress;
			}
		}

		/**
		 * Return a false when we can't find any emails in the bounce message,
		 * as subsequent process to the email will be useless anyway
		 */
		if (count($emails_to_return) == 0) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . "; Email addresses not found, NOT continuing to process this email\n", 3, $this->LogFile);
			}
			return array(false, false, false);
		}

		// ------------------------------------------------------------------------------------
		// Check bounce type against RFC 3464 & RFC 3463
		// The use of algo for the next section will reduce the load of regexes used to find reason for the bounce.
		// If pattern not found, it will ignore the RFC, and continue with matching the bounce rules
		// ------------------------------------------------------------------------------------
		$status = array();

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . "; Looking for RFC 3464 & RFC 3463 pattern\n", 3, $this->LogFile);
		}

		// Get delivery status notification
		if (preg_match('/(--[^\s]*?)\sContent-Type\s*?\:\s*?message\/delivery-status\s(.*?)\1/is', substr($body, 0, $this->_peek), $matches)) {
			if (count($matches) == 3) {
				preg_match('/Status\s*?\:\s*?([2|4|5]+)\.(\d{1,3}).(\d{1,3})/is', $matches[2], $status);
			}

			unset($matches);
		}

		// Process delivery status notification
		if (count($status) == 4) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . "; Delivery status follows RFC standard, processing delivery status with code: {$status[1]}.{$status[2]}.{$status[3]};\n", 3, $this->LogFile);
			}

			$bounce_type = false;
			$bounce_group = false;

			switch ($status[1]) {
				// Delete successful delivery status
				case 2:
					$bounce_type = 'delete';
					$bounce_group = 'delete';
					return array($bounce_type, $bounce_group, $emails_to_return);
					break;

					// Trasient delivery failure
				case 4:
					list ($matched, $bounce_type, $bounce_group) = $this->_ProcessRFC3463Transient($status[2], $status[3]);
					if ($matched) {
						return array($bounce_type, $bounce_group, $emails_to_return);
					}
					break;

					// Permanent delivery failure
				case 5:
					if ($status[2] == 5 && $status[3] == 0) {
						// X.5.0 Other or undefined protocol status
						// We should let this fall through to the regexes since it could be a soft bounce for blocked content.
						break;
					}
					list ($matched, $bounce_type, $bounce_group) = $this->_ProcessRFC3463Permanent($status[2], $status[3]);
					if ($matched) {
						return array($bounce_type, $bounce_group, $emails_to_return);
					}
					break;
			}
		} else {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . "; RFC pattern not found, continuing with rules\n", 3, $this->LogFile);
			}
		}
		// -----

		foreach ($GLOBALS['BOUNCE_RULES'] as $bounce_type => $bounce_rule) {
			foreach ($bounce_rule as $bounce_group => $rules) {
				foreach ($rules as $p => $target_string) {
					if ($this->Debug) {
						error_log('Line ' . __LINE__ . '; Processing bounce type ' . $bounce_type . '; rule: ' . $target_string . "\n", 3, $this->LogFile);
					}

					if (preg_match('%' . preg_quote($target_string) . '%is', $body)) {
						if (!empty($email_matches)) {

							if ($this->Debug) {
								error_log('Line ' . __LINE__ . '; email_matches: ' . print_r($email_matches, true) . "\n", 3, $this->LogFile);
							}

							return array($bounce_type, $bounce_group, $emails_to_return);
						}
						if ($this->Debug) {
							error_log('Line ' . __LINE__ . '; no email_matches: ' . print_r($email_matches, true) . "\n", 3, $this->LogFile);
						}
					} else {
						if ($this->Debug) {
							error_log('Line ' . __LINE__ . '; no matches found for rule ' . $target_string . "\n", 3, $this->LogFile);
						}
					}
				}
			}
		}
		return array(false, false, false);
	}

	/**
	 * ProcessEmail
	 * Analyses an email and determines whether it's a hard or soft bounce or something else to be deleted or ignored.
	 *
	 * @param Integer $emailid The ID of the email.
	 * @param Integer $listid The ID of the list the email belongs to.
	 * @param Boolean $dry_run If set to true, will not actually record bounce information.
	 *
	 * @return String Either 'delete', 'ignore', 'hard' or 'soft'.
	 */
	function ProcessEmail($emailid=0, $listid=0, $dry_run=false)
	{

		if ($this->Debug) {
			error_log("\n---------\n", 3, $this->LogFile);
			error_log('Line ' . __LINE__ . '; Date: ' . date('r') . "\n", 3, $this->LogFile);
			error_log('Line ' . __LINE__ . '; Processing emailid: ' . $emailid . '; listid: ' . $listid . "\n", 3, $this->LogFile);
		}

		$header = $this->GetHeader($emailid);

		if (!$header || empty($header)) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; no proper email headers found. Returning ignore' . "\n", 3, $this->LogFile);
			}
			return 'ignore';
		}

		$bounce_time = $this->GetBounceTime($header);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; bounce_time: ' . $bounce_time . "\n", 3, $this->LogFile);
		}

		if ($bounce_time == 0) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; ignoring emails with bounce time of 0' . "\n", 3, $this->LogFile);
			}
			return 'ignore';
		}

		$fromaddress = $this->GetBounceFrom($header);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; From Address: ' . $fromaddress . "\n", 3, $this->LogFile);
		}

		$mailbox = $this->GetBounceMailbox($header);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; Mailbox: ' . $mailbox . "\n", 3, $this->LogFile);
		}

		// ------------------------------------------------------------------------------------
		// Check subject for bounce pattern
		//
		// If either subject or bounce pattern was empty, it will proceed to evaluate the body
		// If subject is evaluated, and pattern does not match, ignore
		//
		// This aims to reduce bandwith consumption by downloading all the un-necessary emails
		// by screening them first VIA subject
		// ------------------------------------------------------------------------------------
			// ----- Getting subject from header
			$subject = $this->GetBounceSubject($header);
			$this->msgInfo['subject'] = $subject;

			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; Subject: ' . $subject . "\n", 3, $this->LogFile);
			}

			// if it's a sendstudio generated subject, just delete the email straight away.
			if (preg_match('%^email campaign.*?has (started|finished) sending$%i', $subject)) {
				return 'delete';
			}

			if (preg_match('%^a subscriber has (joined|unsubscribed)%i', $subject)) {
				return 'delete';
			}

			if (preg_match('%^invalid login details$%i', $subject)) {
				return 'delete';
			}
			// -----

			// ----- Initialize subject regex cache (so it won't need to be constructed over and over again)
			if (is_null($this->_subjectRegex)) {
				if (isset($GLOBALS['BOUNCE_RULES_SUBJECT']) && is_array($GLOBALS['BOUNCE_RULES_SUBJECT']) && count($GLOBALS['BOUNCE_RULES_SUBJECT']) > 0) {
					$temp = array();
					foreach ($GLOBALS['BOUNCE_RULES_SUBJECT'] as $each) {
						$temp[] = preg_quote($each, '~');
					}
					$this->_subjectRegex = '~('.implode('|', $temp).')~i';
				} else $this->_subjectRegex = '';
			}
			// -----

			// ----- Evaluate subject (if regex or subject is empty continue without evaluating)
			if ($this->_subjectRegex != '' || trim($subject) != '') {
				if (!preg_match($this->_subjectRegex, $subject)) {
					if ($this->Debug) {
						error_log('Line ' . __LINE__ . "; Subject '{$subject}' does not match pattern", 3, $this->LogFile);
					}

					return 'ignore';
				}
			} else {
				if ($this->Debug) {
					error_log('Line ' . __LINE__ . '; Ignoring subject filter (' . ($this->_subjectRegex == ''? 'Subject filter empty' : 'Subject empty') . ')', 3, $this->LogFile);
				}
			}
			// -----
		// -----

		$body = $this->GetMessage($emailid);

		/**
		 * Base 64 encoding is used by Microsoft Exchange, so decode them first
		 */
		if (preg_match('/\r?\n(.*?)\r?\nContent-Type\:\s*text\/plain.*?Content-Transfer-Encoding\:\sbase64\r?\n\r?\n(.*?)\1/is', substr($body, 0, $this->_peek), $matches)) {
			$body = str_replace($matches[2], base64_decode(str_replace(array("\n", "\r"), '', $matches[2])), $body);
		}
		/**
		 * -----
		 */

		// now go through the bounce types and work out what it is.
		list($bounce_type, $bounce_rule, $bounce_emails) = $this->ParseBody($body);

		// if we can't find a bounce type, just ignore the email.
		if (!$bounce_type) {
			return 'ignore';
		}

		if ($bounce_type == 'delete') {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; bounce type is delete - just return' . "\n", 3, $this->LogFile);
			}
			return $bounce_type;
		}

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; bounce_type: ' . $bounce_type . '; bounce_rule: '. $bounce_rule . '; bounce_email: ' . print_r($bounce_emails, true) . "\n", 3, $this->LogFile);
		}
		
		$bounce_listids = $this->GetBounceList($body);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; bounce_listids: ' . print_r($bounce_listids, true) . "\n", 3, $this->LogFile);
		}

		// if we got this far, we found a matching rule for the bounced email.
		// however if there are no listids, we can't match it to a statistic.
		// so just delete the email and go to the next one.
		if (empty($bounce_listids)) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; No bounce listid was found.' . "\n", 3, $this->LogFile);
			}
			return 'delete';
		}

		$bounce_statid = $this->GetBounceStat($body);

		if ($this->Debug) {
			error_log('Line ' . __LINE__ . '; bounce_statid: ' . $bounce_statid . "\n", 3, $this->LogFile);
		}

		if (empty($bounce_emails) || (!$bounce_type && !$bounce_rule)) {
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; The bounce_email or bounce_type or bounce_rule dont exist. Returning "ignore"' . "\n", 3, $this->LogFile);
			}
			return 'ignore';
		}

		// remember whether we need to delete the email from the inbox or not.
		$delete_email = false;

		foreach ($bounce_emails as $bep => $bounce_email) {
			$subscriber_id = false;
			$bounce_listid = 0;

			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; checking email ' . $bounce_email . ' against listids ' . print_r($bounce_listids, true) . "\n", 3, $this->LogFile);
			}

			$subscriber_info = $this->Subscriber_API->IsSubscriberOnList($bounce_email, $bounce_listids, 0, false, true, true);

			if (is_array($subscriber_info)) {
				$subscriber_id = $subscriber_info['subscriberid'];
				$bounce_listid = $subscriber_info['listid'];
			}

			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; subscriber_id: ' . $subscriber_id . "\n", 3, $this->LogFile);
				error_log('Line ' . __LINE__ . '; bounce_listid: ' . $bounce_listid . "\n", 3, $this->LogFile);
			}

			if (!in_array($bounce_listid, array_keys($this->list_objects))) {
				$this->Lists_API->Load($bounce_listid);
				$this->list_objects[$bounce_listid] = $this->Lists_API;
			}

			// Never unsubscribe the list owner. Sometimes a bounce might include
			// the from address which would be detected as an address to bounce
			if ($bounce_email == $this->list_objects[$bounce_listid]->owneremail) {
				if ($this->Debug) {
					error_log('The bounce email ' . $bounce_email . ' matches the list owners email ' . $this->list_objects[$bounce_listid]->owneremail . '; ignoring this email address' . "\n", 3, $this->LogFile);
				}
				continue;
			}

			if (!$subscriber_id || !$bounce_listid) {
				if ($this->Debug) {
					error_log('Line ' . __LINE__ . '; subscriber_id is not on list.' . "\n", 3, $this->LogFile);
				}

				if (in_array($listid, $bounce_listids)) {
					if ($this->Debug) {
						error_log('Line ' . __LINE__ . '; listid: ' . $listid . ' is in the bounce listids: ' . print_r($bounce_listids, true) . ' but we are unable to find the subscriber on the list - so remember we have to delete the email and go to the next email address' . "\n", 3, $this->LogFile);
					}

					/**
					 * dont return a bounce type - just remember we have to delete this email from the inbox.
					 * it could be that we're checking the wrong email address in the bounced email and
					 * we need to go to the next one to find the subscribers email address.
					 */
					$delete_email = true;
					continue;
				}

				if ($this->Debug) {
					error_log('Line ' . __LINE__ . '; skipping email.' . "\n", 3, $this->LogFile);
				}
				continue;
			}

			$already_bounced = $this->Subscriber_API->AlreadyBounced($subscriber_id, $bounce_statid, $bounce_listid);
			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; already_bounced returned ' . $already_bounced . "\n", 3, $this->LogFile);
			}

			if ($already_bounced) {
				if ($this->Debug) {
					error_log('Line ' . __LINE__ . '; a bounce has already been recorded so returning bounce type ' . $bounce_type . "\n", 3, $this->LogFile);
				}
				return $bounce_type;
			}

			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; a bounce has not yet been recorded. Recording info. bounce_type: ' . $bounce_type . '; bounce_rule: '. $bounce_rule . "\n", 3, $this->LogFile);
			}

			// Actually record the bounce.
			if ($dry_run) {
				error_log('Line ' . __LINE__ . "; not recording bounce as this is a dry run.\n", 3, $this->LogFile);
			} else {
				$params = array(
					'subscriber_id' => $subscriber_id,
					'bounce_statid' => $bounce_statid,
					'bounce_listid' => $bounce_listid,
					'bounce_type' => $bounce_type,
					'bounce_rule' => $bounce_rule,
					'bounce_time' => $bounce_time,
				);
				$this->HandleBounce($params);
			}

			if ($this->Debug) {
				error_log('Line ' . __LINE__ . '; returning bounce type ' . $bounce_type . "\n", 3, $this->LogFile);
			}

			return $bounce_type;
		}

		if ($delete_email) {
			return 'delete';
		}

		return 'ignore';
	}

	/**
	 * HandleBounce
	 * Performs the appropriate action for processing a bounce as per the following member options:
	 *
	 * @see flagHardBounce
	 * @see flagSoftBounce
	 * @see deleteHardBounce
	 *
	 * @param array $params Bounce parameters, including subscriber_id, $bounce_statid, $bounce_listid, $bounce_type, $bounce_rule and $bounce_time.
	 *
	 * @return void Does not return anything.
	 */
	private function HandleBounce($params)
	{
		$bt = $params['bounce_type'];
		// Record the true stats regardless.
		$this->Stats_API->RecordBounceInfo($params['subscriber_id'], $params['bounce_statid'], $bt);
		// Mark the subscribers only if instructed.
		if ($this->flagHardBounce || $this->flagSoftBounce) {
			$subscriber_bt = $bt;
			if ($this->flagSoftBounce) {
				// If we're flagging subscribers who soft bounced as inactive, they need to be hard bounced.
				$subscriber_bt = 'hard';
			}
			$this->Subscriber_API->RecordBounceInfo($params['subscriber_id'], $params['bounce_statid'], $params['bounce_listid'], $subscriber_bt, $params['bounce_rule'], '', $params['bounce_time']);
		}
		// Delete the subscribers who hard bounced if instructed.
		if ($this->deleteHardBounce && $bt == 'hard') {
			$this->Subscriber_API->DeleteSubscriber('', $params['bounce_listid'], $params['subscriber_id']);
		}
	}

	/**
	 * _ProcessRFC3463Transient
	 * Process bounces for transient failure codes
	 *
	 * @param Integer $statusCode Status code
	 * @param Integer $statusSubCode Status sub code
	 *
	 * @return Array Returns an array of bounce status
	 */
	function _ProcessRFC3463Transient($statusCode, $statusSubCode)
	{
		switch ($statusCode) {
			case 5:
				// 4.5.3 Too many recipients
				if ($statusSubCode == 3) {
					return array(true, 'soft', 'localconfigerror');
				}
				break;
		}

		// As a default, Transient failure is considered as a warning, so delete the message
		return array(true, 'delete', 'delete');
	}

	/**
	 * _ProcessRFC3463Permanent
	 * Process bounces for permanent failure codes
	 *
	 * @param Integer $statusCode Status code
	 * @param Integer $statusSubCode Status sub code
	 *
	 * @return Array Returns an array of bounce status
	 */
	function _ProcessRFC3463Permanent($statusCode, $statusSubCode)
	{
		$bounce_type = false;
		$bounce_group = false;
		switch ($statusCode) {
			case '1': // Addressing status
				if (in_array($statusSubCode, array('0','1','2','3','4','5','6'))) {
					$bounce_type = 'hard';
					$bounce_group = 'emaildoesntexist';
				}
				return array(true, $bounce_type, $bounce_group);
				break;

			case '2': // Mailbox status
				$bounce_type = 'soft';

				// '1' is also inactive, so it can go through to the default case.
				switch ($statusSubCode) {
					case '2':
						$bounce_group = 'overquota';
						break;

					case '3':
						$bounce_group = 'blockedcontent';
						break;

					case '4':
						$bounce_group = 'remoteconfigerror';
						break;

					default:
						$bounce_group = 'inactive';
						break;
				}
				return array(true, $bounce_type, $bounce_group);
				break;

			case '3': // Mail system status
				$bounce_type = 'soft';

				// 2, 3 and 5 are all 'remoteconfigerror' so just send 'em to the default case.
				switch ($statusSubCode) {
					case '1':
						$bounce_group = 'overquota';
						break;

					case '4':
						$bounce_group = 'overquota';
						break;

					default:
						$bounce_group = 'remoteconfigerror';
				}
				return array(true, $bounce_type, $bounce_group);
				break;

			case '4': // Network and routing status
				$bounce_type = 'soft';
				$bounce_group = 'remoteconfigerror';
				return array(true, $bounce_type, $bounce_group);
				break;

			case '5': // Mail delivery protocol status
				if (in_array($statusSubCode, array('0'))) {
					$bounce_type = 'hard';
					$bounce_group = 'relayerror';
				} else {
					$bounce_type = 'soft';
					$bounce_group = 'localconfigerror';
				}
				return array(true, $bounce_type, $bounce_group);
				break;

			case '6': // Message contents status
				$bounce_type = 'soft';
				$bounce_group = 'localconfigerror';
				return array(true, $bounce_type, $bounce_group);
				break;

			case '7': // Security status
				switch ($statusSubCode) {
					case '1':
						$bounce_group = 'blockedcontent';
						break;

					default:
						$bounce_group = 'localconfigerror';
						break;
				}

				$bounce_type = 'soft';
				return array(true, $bounce_type, $bounce_group);
				break;
		}

		// Do not consisder this status, as it doesn't match any of the available cases
		return array(false, false, false);
	}
}
