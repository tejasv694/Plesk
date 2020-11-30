<?php
/**
* This is the send system api.
*
* @version     $Id: send.php,v 1.26 2008/01/22 08:01:30 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Send_API
*/

/**
* Require the base API class.
*/
require_once(dirname(__FILE__) . '/jobs.php');

/**
 * The send class handles basic processing of sending jobs.
 * It is used both by the popup sending method and also by scheduled sending.
 */
class Send_API extends Jobs_API
{

	/**
	 * queuetype
	 * The type of queue we are sending.
	 * This can be overridden by addons (eg trigger emails or splittests).
	 *
	 * @usedby MarkAsProcessed
	 *
	 * @var String The type of queue we are sending.
	 */
	protected $queuetype = 'send';

	/**
	 * queuesize
	 *
	 * The size of the queue we are cleaning up.
	 *
	 * @usedby Cleanup
	 *
	 * @var Int The queuesize to clean up.
	 */
	var $queuesize = 0;

	/**
	 * to_customfields
	 *
	 * The "To" custom fields (first & last name field ids)
	 * This allows the email class to further personalize the email by adding the first & last name to the "To" field.
	 *
	 * @usedby SetupJob
	 * @usedby SetupCustomFields
	 *
	 * @var Array
	 */
	var $to_customfields = array();

	/**
	 * custom_fields_to_replace
	 *
	 * The custom fields that will need replacing
	 * This is a temporary placeholder to be passed to the subscribers api so it can fetch the fields for a bunch of subscribers all at once.
	 *
	 * @usedby SetupJob
	 * @usedby SendToRecipient
	 * @usedby SetupCustomFields
	 *
	 * @var Array
	 */
	var $custom_fields_to_replace = array();

	/**
	 * $dynamic_content_replacement
	 *
	 * An array to store all the details of dynamic content to be replaced
	 *
	 * @usedby SetupDynamicContentFields
	 *
	 * @var Array
	 */
	var $dynamic_content_replacement = array();

	/**
	 * sendstudio_functions
	 * A temporary placeholder variable to get the sendstudio_functions class.
	 * This is used to get the attachments for a newsletter (which are then passed to the email api).
	 *
	 * @usedby Send_API
	 * @usedby SetupJob
	 *
	 * @var SendStudio_Functions
	 */
	var $sendstudio_functions = null;

	/**
	 * jobowner
	 * The job's owner (userid).
	 * Used to track their actions through the app.
	 *
	 * @usedby SetupJob
	 *
	 * @var Int $jobowner
	 */
	var $jobowner = 0;

	/**
	* The job status for the job we're running.
	*
	* @see FetchJob
	* @see ProcessJob
	* @see ActionJob
	*
	* @var Array
	*/
	var $jobstatus = null;


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
	* A reference to the newsletter api. Saves us having to re-create it all the time.
	*
	* @see Jobs_Send_API
	* @see ProcessJob
	* @see ActionJob
	*
	* @var Object
	*/
	var $Newsletters_API = null;

	/**
	* Used to remember the newsletter we're sending. Saves constantly loading it from the database.
	*
	* @see ActionJob
	*
	* @var Array
	*/
	var $newsletter = array();

	/**
	* A count of the emails we have sent (used for reporting).
	*
	* @var Int
	*/
	var $emailssent = 0;

	/**
	* The pause between sending each newsletter if applicable.
	*
	* @var Int
	*/
	var $userpause = null;

	/**
	* Temporarily holds the user object so it can be easily referenced.
	*
	* @var Object
	*/
	var $user = null;

	/**
	* The statistics id so we can record what's going on.
	*
	* @var Int
	*/
	var $statid = 0;

	/**
	* The name(s) of the list(s) we're sending to.
	*
	* @var Array
	*/
	var $listnames = Array();

	/**
	* The job details for the job we're running. This is used to save database queries.
	*
	* @see FetchJob
	* @see ProcessJob
	* @see ActionJob
	*
	* @var Array
	*/
	var $jobdetails = array();

	/**
	 * notify_email
	 * An array containing the notification email subject & message.
	 * If these aren't set, then the NotifyOwner method will work it out itself.
	 *
	 * If they are set, then they will be used instead of the default ones.
	 *
	 * @usedby NotifyOwner
	 */
	var $notify_email = array(
			'subject' => '',
			'message' => ''
		);

	/**
	* Send_API
	* Sets up the required classes for the sending process to use.
	* If the app is in "Test Mode", it also sets that for the email api.
	* Lastly, it sets up the database class for easy use.
	*
	* @uses Email_API
	* @uses Subscriber_API
	* @uses Lists_API
	* @uses Stats_API
	* @uses Newsletters_API
	* @uses sendstudio_functions
	* @uses GetDb
	*
	* @return Void Just sets up the required classes, doesn't return anything.
	*/
	function Send_API()
	{
		if (is_null($this->Email_API)) {
			if (!class_exists('ss_email_api', false)) {
				require_once(dirname(__FILE__) . '/ss_email.php');
			}
			$email = new SS_Email_API();
			$this->Email_API = &$email;
		}

		if (SENDSTUDIO_SEND_TEST_MODE) {
			$this->Email_API->TestMode = true;
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

		if (is_null($this->Newsletters_API)) {
			if (!class_exists('newsletters_api', false)) {
				require_once(dirname(__FILE__) . '/newsletters.php');
			}
			$news = new Newsletters_API();
			$this->Newsletters_API = &$news;
		}

		if (is_null($this->sendstudio_functions)) {
			if (!class_exists('sendstudio_functions', false)) {
				require_once(dirname(__FILE__) . '/../sendstudio_functions.php');
			}
			$ss_functions = new Sendstudio_Functions();
			$this->sendstudio_functions = &$ss_functions;
		}

		$this->GetDb();

		return true;
	}

	/**
	 * SetupJob
	 * This sets up the 'send job' ready to go.
	 * - It checks the "queue" is set up. If it's not, this will return false.
	 * - It loads the newsletter based on the "Newsletter" in the jobdetails array. If it's not a valid newsletter, then this will return false.
	 * - The newsletter gets stored in the newsletter array for easy access.
	 * - Loads the user object (so we can easily look at the per-hour & other sending limits).
	 * - It gets a list of attachments for the newsletter and gives them to the email class
	 * - Sets smtp details for the email class
	 * - Sets stat/listid's for the email class headers to use
	 * - Sets open/link tracking for the email class
	 * - Sets the charset for the email class
	 * - Sets up the rest of the details the email class needs (from details, bounce address, reply-to etc)
	 * - Works out the "userpause" time which is used later on to sleep for a period of time between each email sent
	 * - Gets the custom fields from the content so later on we know which fields to load for each subscriber
	 * - Works out the "to" custom fields so later on we know which fields to load for each subscriber
	 *
	 * @uses IsQueue
	 * @uses Newsletters_API::Load
	 * @uses GetUser
	 * @uses newsletter
	 * @uses SendStudio_Functions::GetAttachments
	 * @uses Email_API
	 * @uses Email_API::AddAttachment
	 * @uses Email_API::AddBody
	 * @uses Email_API::AppendBody
	 * @uses Email_API::ClearAttachments
	 * @uses Email_API::ForceLinkChecks
	 * @uses Email_API::ForgetEmail
	 * @uses Email_API::GetCustomFields
	 * @uses Email_API::Set
	 * @uses Email_API::SetSmtp
	 * @uses Email_API::TrackLinks
	 * @uses Email_API::TrackOpens
	 * @uses userpause
	 * @uses User_API::perhour
	 * @uses custom_fields_to_replace
	 * @uses to_customfields
	 *
	 * @return Boolean Returns false if the 'send queue' isn't set up already, or if the newsletter can't be loaded or doesn't exist.
	 *   Otherwise sets up the email class and other details ready for use, and returns true.
	 */
	function SetupJob($jobid=0, $queueid=0)
	{
		$is_queue = $this->IsQueue($queueid, 'send');
		
		if (!$is_queue) {
			return false;
		}

		// if we can't load the newsletter, pause it and immediately stop.
		$news_loaded = $this->Newsletters_API->Load($this->jobdetails['Newsletter']);
		
		if (!$news_loaded) {
			return false;
		}

		$this->user = GetUser($this->jobowner);
		
		$this->newsletter                = array();
		$this->newsletter['Format']      = $this->Newsletters_API->Get('format');
		$this->newsletter['Subject']     = $this->Newsletters_API->Get('subject');
		$this->newsletter['TextBody']    = $this->Newsletters_API->Get('textbody');
		$this->newsletter['HTMLBody']    = $this->Newsletters_API->Get('htmlbody');
		$this->newsletter['Attachments'] = $this->sendstudio_functions->GetAttachments('newsletters', $this->jobdetails['Newsletter'], true);

		$this->Email_API->ForgetEmail();
		$this->Email_API->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);
		$this->Email_API->Set('statid', $this->statid);
		$this->Email_API->Set('listids', $this->jobdetails['Lists']);

		if (SENDSTUDIO_FORCE_UNSUBLINK) {
			$this->Email_API->ForceLinkChecks(true);
		}

		if ($this->jobdetails['TrackLinks']) {
			$this->Email_API->TrackLinks(true);
		}

		if ($this->jobdetails['TrackOpens']) {
			$this->Email_API->TrackOpens(true);
		}

		$this->Email_API->Set('CharSet', $this->jobdetails['Charset']);

		if (!SENDSTUDIO_SAFE_MODE) {
			$this->Email_API->Set('imagedir', TEMP_DIRECTORY . '/send.' . $jobid . '.' . $queueid);
		} else {
			$this->Email_API->Set('imagedir', TEMP_DIRECTORY . '/send');
		}

		// clear out the attachments just to be safe.
		$this->Email_API->ClearAttachments();

		if ($this->newsletter['Attachments']) {
			$path  = $this->newsletter['Attachments']['path'];
			$files = $this->newsletter['Attachments']['filelist'];
			
			foreach ($files as $p => $file) {
				$this->Email_API->AddAttachment($path . '/' . $file);
			}
		}

		$this->Email_API->Set('Subject', $this->newsletter['Subject']);
		$this->Email_API->Set('FromName', $this->jobdetails['SendFromName']);
		$this->Email_API->Set('FromAddress', $this->jobdetails['SendFromEmail']);
		$this->Email_API->Set('ReplyTo', $this->jobdetails['ReplyToEmail']);
		$this->Email_API->Set('BounceAddress', $this->jobdetails['BounceEmail']);
		$this->Email_API->Set('Multipart', $this->jobdetails['Multipart']);
		$this->Email_API->Set('EmbedImages', $this->jobdetails['EmbedImages']);
		$this->Email_API->Set('SentBy', $this->user->Get('userid'));

		if ($this->jobdetails['Multipart']) {
			if ($this->newsletter['TextBody'] && $this->newsletter['HTMLBody'] && $this->newsletter['Format'] == 'b') {
				$sent_format = 'm';
			} else {
				$this->Email_API->Set('Multipart', false);
			}
		}

		if ($this->newsletter['TextBody'] && in_array($this->newsletter['Format'], array('t', 'b'))) {
			$this->Email_API->AddBody('text', $this->newsletter['TextBody']);
			$this->Email_API->AppendBody('text', $this->user->Get('textfooter'));
			$this->Email_API->AppendBody('text', stripslashes(SENDSTUDIO_TEXTFOOTER));
		}

		if ($this->newsletter['HTMLBody'] && in_array($this->newsletter['Format'], array('h', 'b'))) {
			$this->Email_API->AddBody('html', $this->newsletter['HTMLBody']);
			$this->Email_API->AppendBody('html', $this->user->Get('htmlfooter'));
			$this->Email_API->AppendBody('html', stripslashes(SENDSTUDIO_HTMLFOOTER));
		}

		if ($this->user->smtpserver) {
			$this->Email_API->SetSmtp($this->user->smtpserver, $this->user->smtpusername, $this->user->smtppassword, $this->user->smtpport);
		}

		/*
		 * The following code pauses the sending depending on if userpause
		 * (undescriptive variable name) is set or if the user has gone over
		 * their hourly limit.
		 */
		if (is_null($this->userpause)) {
			$pause      = $pausetime = 0;
			$hourlyRate = $this->user->group->limit_hourlyemailsrate;
			
			/*
			 * If the hourly rate is greater than 0, set the pause time to it.
			 * 
			 * Why originally pause was set to this is logically bad and creates
			 * unclear code. This should be written differently or at least use
			 * better variable names and have been originally commented.
			 * 
			 * @todo refactor
			 */
			if ($hourlyRate > 0) {
				$pause = $hourlyRate;
			}

			// in case the system rate is less than the user rate, lower it.
			if (SENDSTUDIO_MAXHOURLYRATE > 0) {
				if ($pause == 0) {
					$pause = SENDSTUDIO_MAXHOURLYRATE;
				} else {
					$pause = min($pause, SENDSTUDIO_MAXHOURLYRATE);
				}
			}

			if ($pause > 0) {
				$pausetime = 3600 / $pause;
			}
			
			$this->userpause = $pausetime;
		}

		$this->custom_fields_to_replace = $this->Email_API->GetCustomFields();

		$to_customfields = array();

		if ($this->jobdetails['To_FirstName']) {
			$to_customfields[] = $this->jobdetails['To_FirstName'];
		}

		if ($this->jobdetails['To_LastName']) {
			$to_customfields[] = $this->jobdetails['To_LastName'];
		}

		$this->to_customfields = array_unique($to_customfields);
		
		return true;
	}

	/**
	* SendToRecipient
	* This loads a recipient (subscriber) from the database, sets the right custom field values and passes the details off to the email api to actually send.
	*
	* The process is reasonably simple:
	* If a subscriber can't be loaded or is invalid (they are unsubscribed), it saves the details for later checking and returns a failure message.
	* If that passes, it works out which list the subscriber is on so it can set the right listname, companyname etc details for custom field processing.
	* It then checks to see if the subscriber has all of the custom fields for the email class.
	* If any custom fields are missing, they are replaced with blank data.
	* We need to do this so the end subscriber doesn't get placeholders (eg "%%fieldname%%") in the end email.
	* It also works out the "To" name for the subscriber if the "to" custom fields have been filled in during the send setup process.
	*
	* All of that data is passed to the email class to actually build & send the email.
	*
	* If the email class returns an error when trying to do a send,
	* this is then recorded with a different error code so later on we can see why it couldn't be sent.
	*
	* If the email class returns success when trying to do a send,
	* this records the send in statistics (based on which format they were emailed).
	*
	* In either case, this will mark the record as 'processed' so we don't try to email them again
	* and then the results from the email class are passed back to the calling code.
	*
	* @param Int $recipient The recipient we're trying to send to (really the "subscriber id" from the list_subscribers table).
	* @param Int $queueid The queue we're trying to process. This is used to mark them as processed or remove them from the right queue. Passed to Save_Unsent_Recipient, RemoveFromQueue and MarkAsProcessed.
	* @param String $queuetype The type of queue we're processing. Defaults to 'send' but addons might have their own queue type.
	*
	* @uses Email_API::ClearRecipients
	* @uses Email_API::AddRecipient
	* @uses Email_API::AddCustomFieldInfo
	* @uses Email_API::Send
	* @uses Stats_API::UpdateRecipient
	* @uses Subscriber_API::LoadSubscriberBasicInformation
	* @uses all_customfields
	* @uses MarkAsProcessed
	* @uses RemoveFromQueue
	* @uses Save_Unsent_Recipient
	* @uses to_customfields
	* @uses queuetype
	* @see language/language.php for descriptions and error codes you can use here.
	*
	* @return Array Returns an array of data which includes the following entries:
	* - 'success' => $counter,
	* - 'fail' => array ($recipient, $message);
	*
	* Where recipient is the recipient/subscriber id, and the message is why they couldn't be emailed.
	*/
	function SendToRecipient($recipient = 0, $queueid = 0, $queuetype = 'send')
	{
		$this->Email_API->ClearRecipients();
		
		$this->jobdetails['EmailResults']['total']++;
				
		$subscriberinfo = array();
		$subscriberinfo = $this->Subscriber_API->LoadSubscriberBasicInformation($recipient, $this->jobdetails['Lists']);
		
		// Removes subsriber from queue if we can't find it's record from list_subscribers table
		// Do not add this to queues_unsent table.
		if (empty($subscriberinfo) || !isset($subscriberinfo['subscribedate'])) {
			$this->RemoveFromQueue($queueid, $queuetype, $recipient);
			$mail_result['success'] = 0;
			$mail_result['fail'] = array();
			$mail_result['fail'][] = array($recipient, 'Unable to load subscriber info or they do not have a subscribe date set.');
			$this->jobdetails['EmailResults']['failure']++;
			$query = "UPDATE [|PREFIX|]jobs SET jobdetails='" . $this->Db->Quote(serialize($this->jobdetails)) . "' WHERE queueid={$queueid}";
			$result = $this->Db->Query($query);
			if (!$result) {trigger_error(mysql_error());}
			return $mail_result;
		}

		if (sizeof($this->jobdetails['Lists']) == 1) {
			if (empty($this->listnames)) {
				$listid = current($this->jobdetails['Lists']);
				$this->Lists_API->Load($listid);
				$this->listnames = array (
					$listid => array (
						'listname' => $this->Lists_API->Get('name'),
						'companyname' => $this->Lists_API->Get('companyname'),
						'companyaddress' => $this->Lists_API->Get('companyaddress'),
						'companyphone' => $this->Lists_API->Get('companyphone'),
					)
				);
			}
		} else {
			// a subscriberid is unique per list and per system, so a subscriberid can only have one listid associated with it.
			// so we'll just get the listname from that.
			$listid = $subscriberinfo['listid'];
			if (!in_array($listid, array_keys($this->listnames))) {
				$this->Lists_API->Load($listid);
				$this->listnames[$listid] = array (
					'listname' => $this->Lists_API->Get('name'),
					'companyname' => $this->Lists_API->Get('companyname'),
					'companyaddress' => $this->Lists_API->Get('companyaddress'),
					'companyphone' => $this->Lists_API->Get('companyphone'),
				);
			}
		}

		$subscriberinfo['ipaddress'] = $subscriberinfo['confirmip'];
		if (!$subscriberinfo['ipaddress']) {
			$subscriberinfo['ipaddress'] = $subscriberinfo['requestip'];
		}
		if (!$subscriberinfo['ipaddress']) {
			$subscriberinfo['ipaddress'] = 'n/a';
		}

		$subscriberinfo['CustomFields'] = array();
		if (isset($this->all_customfields[$recipient])) {
			$subscriberinfo['CustomFields'] = $this->all_customfields[$recipient];
		} else {
			/**
			* If the subscriber has no custom fields coming from the database, then set up blank placeholders.
			* If they have no custom fields in the database, they have no records in the 'all_customfields' array - so we need to fill it up with blank entries.
			*/
			foreach ($this->custom_fields_to_replace as $fieldid => $fieldname) {
				$subscriberinfo['CustomFields'][] = array(
					'fieldid' => $fieldid,
					'fieldname' => $fieldname,
					'fieldtype' => 'text',
					'defaultvalue' => '',
					'fieldsettings' => '',
					'subscriberid' => $recipient,
					'data' => ''
				);
			}
		}

		$name = false;

		if ($this->jobdetails['To_FirstName']) {
			foreach ($subscriberinfo['CustomFields'] as $p => $details) {
				if ($details['fieldid'] == $this->jobdetails['To_FirstName'] && $details['data'] != '') {
					$name = $details['data'];
					break;
				}
			}
		}

		if ($this->jobdetails['To_LastName']) {
			foreach ($subscriberinfo['CustomFields'] as $p => $details) {
				if ($details['fieldid'] == $this->jobdetails['To_LastName'] && $details['data'] != '') {
					$name .= ' ' . $details['data'];
					break;
				}
			}
		}

		$send_format = $subscriberinfo['format'];
		if ($this->newsletter['Format'] == 't') {
			$send_format = 't';
		}

		$this->Email_API->AddRecipient($subscriberinfo['emailaddress'], $name, $send_format, $subscriberinfo['subscriberid']);

		$lid = $subscriberinfo['listid'];
		$subscriberinfo['listname'] = $this->listnames[$lid]['listname'];
		$subscriberinfo['companyname'] = $this->listnames[$lid]['companyname'];
		$subscriberinfo['companyaddress'] = $this->listnames[$lid]['companyaddress'];
		$subscriberinfo['companyphone'] = $this->listnames[$lid]['companyphone'];

		$subscriberinfo['newsletter'] = $this->jobdetails['Newsletter'];
		$subscriberinfo['statid'] = $this->statid;

		$this->Email_API->AddCustomFieldInfo($subscriberinfo['emailaddress'], $subscriberinfo);

		$this->Email_API->AddDynamicContentInfo($this->dynamic_content_replacement);

		$disconnect = true;
		if (defined('IEM_CRON_JOB')) {
			$disconnect = false;
		}
		$mail_result = $this->Email_API->Send(true, $disconnect);

		if (!$this->Email_API->Get('Multipart')) {
			$sent_format = $subscriberinfo['format'];
		} else {
			$sent_format = 'multipart';
		}

		if ($mail_result['success'] > 0) {
			$this->Stats_API->UpdateRecipient($this->statid, $sent_format, 'n');
			$this->jobdetails['EmailResults']['success']++;
		} else {
			reset($mail_result['fail']);
			$failure_message = current($mail_result['fail']);
			switch ($failure_message[1]) {
				case 'BlankEmail':
					$errorcode = 10;
					$errormessage = $failure_message[1];
				break;

				default:
					$errorcode = 20;
					$errormessage = 'ServerRejected';

					if ($this->Email_API->SMTPServer !== false) {
						$errorcode = 30;
						$errormessage = $failure_message[1];
					}
			}

			$this->Save_Unsent_Recipient($recipient, $queueid, $errorcode, $errormessage);
			
			$this->jobdetails['EmailResults']['failure']++;
		}
		$query = "UPDATE [|PREFIX|]jobs SET jobdetails='" . $this->Db->Quote(serialize($this->jobdetails)) . "' WHERE queueid={$queueid}";
		$result = $this->Db->Query($query);
		if (!$result) {trigger_error(mysql_error());}
		$this->emailssent++;

		/*
		 * Trigger the sending event so hooks get fired.
		 */
		$tempEventData                 = new EventData_IEM_SENDAPI_SENDTORECIPIENT();
		$tempEventData->emailsent      = ($mail_result['success'] > 0);
		$tempEventData->jobdetails     = &$this->jobdetails;
		$tempEventData->subscriberinfo = &$subscriberinfo;
		$tempEventData->newsletter     = &$this->newsletter;
		$tempEventData->trigger();

		unset($tempEventData);

		if (!$this->MarkAsProcessed($queueid, $this->queuetype, $recipient)) {
			// This will make sure that we do not continue sending when the database server is DOWN
			die ('Cannot mark queue as procesed (Cannot write to the database)... Send Job stopped!! (QueueID: ' . $queueid . ', RecipientID: ' . $recipient . ')');
		}

		return $mail_result;
	}

	/**
	 * Pause
	 * This is called after each email is sent and pauses so the send process doesn't send more than the maximum number of emails per hour.
	 *
	 * @uses userpause
	 * @see SetupJob
	 *
	 * @return Void Doesn't return anything, just sleeps for the required time.
	 */
	function Pause()
	{
		if ($this->userpause > 0) {
			if ($this->userpause > 0 && $this->userpause < 1) {
				$p = ceil($this->userpause*1000000);
				usleep($p);
			} else {
				$p = ceil($this->userpause);
				sleep($p);
			}
		}
	}

	/**
	 * CleanUp
	 * This marks a newsletter as having finished sending, then cleans up the left over entries in the queues table.
	 * It passes the current statid and queuesize to the stats api to deal with.
	 * It also calls the email api cleanupimages method which clears out cached embedded images etc it has stored temporarily.
	 *
	 * @param Int $queueid The queue to clean up. This is passed to the ClearQueue method.
	 *
	 * @uses Stats_API::MarkNewsletterFinished
	 * @uses statid
	 * @uses queuesize
	 * @uses ClearQueue
	 * @uses Email_API::CleanupImages
	 *
	 * @return Void Doesn't return anything, just does it's work.
	 */
	function CleanUp($queueid=0)
	{
		$this->Stats_API->MarkNewsletterFinished($this->statid, $this->queuesize);
		$this->ClearQueue($queueid, 'send');

		$this->Email_API->CleanupImages();
	}

	/**
	 * ResetSend
	 * This clears out class variables in case we are sending via scheduled sending
	 * and there is another newsletter to be sent out.
	 *
	 * @uses listnames
	 * @uses userpause
	 * @uses emailssent
	 *
	 * @return Void Clears out the variables, doesn't reset anything.
	 */
	function ResetSend()
	{
		// unset the listname.
		$this->listnames = Array();

		// reset the 'pause' counter.
		$this->userpause = null;

		$this->emailssent = 0;
	}

	/**
	 * SetupCustomFields
	 * Loads custom fields for all recipients passed in (so we're only doing one database hit for a bunch of recipients)
	 * Then goes through each custom field found and tries to fix up non text custom fields.
	 * It will look at a date custom field, work out the right order to display and convert the custom field data (from the subscriber) to be in that format.
	 * It will look at dropdown, radio button and checkbox custom fields and convert them from being "keys" to "values".
	 *
	 * It is done here and not through the custom field api to save time
	 * so we don't have to re-load each custom field being used over and over again
	 * which is what using the custom field api directly would do.
	 *
	 * Once the values have all been checked, then for all of the custom fields being used in an email,
	 * it checks that the all_customfields array for the subscriber id has an entry.
	 * Otherwise, some custom field values would be empty (eg a new field was added but that particular subscriber wasn't updated).
	 *
	 * @param Array $recipients The recipient data to load custom field data for. It is a large-ish (500-1000) array so we only have to hit the database once to get all values.
	 *
	 * @uses Subscriber_API::GetAllSubscriberCustomFields
	 * @uses jobdetails['Lists']
	 * @uses custom_fields_to_replace
	 * @uses to_customfields
	 * @uses all_customfields
	 *
	 * @return Void Returns nothing. It does all of it's work against the all_customfields class variable directly.
	 */
	function SetupCustomFields($recipients=array())
	{
		$this->all_customfields = $this->Subscriber_API->GetAllSubscriberCustomFields($this->jobdetails['Lists'], $this->custom_fields_to_replace, $recipients, $this->to_customfields);

		// rather than using the customfield api to do all of this (which would require loading each item separately, then running GetRealValue) we'll do the "dodgy" and do it all here instead.
		foreach ($this->all_customfields as $subscriberid => $customfield_list) {
			$fields_found = array();
			foreach ($customfield_list as $p => $details) {
				$fields_found[] = $details['fieldname'];
				if ($details['data'] === null || $details['data'] == '') {
					continue;
				}

				$fieldid = $details['fieldid'];

				switch ($details['fieldtype']) {
					case 'date':
						list($dd, $mm, $yy) = explode('/', $details['data']);
						$real_order = array();
						$settings = unserialize($details['fieldsettings']);
						$field_order = array_slice($settings['Key'], 0, 3);
						foreach ($field_order as $posp => $order) {
							switch ($order) {
								case 'day':
									$real_order[] = $dd;
								break;
								case 'month':
									$real_order[] = $mm;
								break;
								case 'year':
									$real_order[] = $yy;
								break;
							}
						}
						$this->all_customfields[$subscriberid][$p]['data'] = implode('/', $real_order);
					break;

					case 'dropdown':
						$settings = unserialize($details['fieldsettings']);
						$data = $details['data'];
						$pos = array_search($data, $settings['Key']);
						if (is_numeric($pos)) {
							$this->all_customfields[$subscriberid][$p]['data'] = $settings['Value'][$pos];
						}
					break;

					case 'radiobutton':
						$settings = unserialize($details['fieldsettings']);
						$data = $details['data'];
						$pos = array_search($data, $settings['Key']);

						if (is_numeric($pos)) {
							$this->all_customfields[$subscriberid][$p]['data'] = $settings['Value'][$pos];
						}
					break;

					case 'checkbox':
						$settings = unserialize($details['fieldsettings']);
						$data = $details['data'];

						$value = unserialize($data);

						$return_value = array();

						foreach ($settings['Key'] as $pos => $key) {
							if (in_array($key, $value)) {
								$return_value[] = $settings['Value'][$pos];
							}
						}

						$data = implode(',', $return_value);

						$this->all_customfields[$subscriberid][$p]['data'] = $data;
					break;
				}
			}

			if (!isset($this->all_customfields[$subscriberid])) {
				$this->all_customfields[$subscriberid] = array();
			}

			/**
			* If the subscriber only has some custom fields from the database, make sure we fill in the rest with blank entries.
			* Otherwise in some cases they could end up with custom fields not being replaced.
			* Eg if they have a custom field value for "Name" but a new custom field is added for "Company",
			* if they have not re-entered their data, they will not have a "Company" custom field entry.
			*
			* This makes for double handling of the custom fields but this should ensure you don't get half-managed custom fields.
			*/
			foreach ($this->custom_fields_to_replace as $fieldid => $fieldname) {
				if (!in_array($fieldname, $fields_found)) {
					$this->all_customfields[$subscriberid][] = array (
						'fieldid' => $fieldid,
						'fieldname' => $fieldname,
						'fieldtype' => 'text',
						'defaultvalue' => '',
						'fieldsettings' => '',
						'subscriberid' => $subscriberid,
						'data' => ''
					);
				}
			}
		}
	}

	/**
	 * SetupDynamicContentFields
     * Loads the Dynamic Content to be replaced and its content
	 *
	 * @param Array $recipients The recipient data to load Dynamic content field data for.
	 *
	 * @return Void Returns nothing. It does all of it's work against the dynamic_content_replacement class variable directly.
	 */
        function SetupDynamicContentFields($recipients=array()) {
            $customFields = $this->Lists_API->GetCustomFields($this->jobdetails['Lists']);
            $allCustomFields = array();
            $allSubscribersInfo = array();
            foreach ($customFields as $customFieldEntry) {
                $allCustomFields[] = $customFieldEntry['name'];
            }
            $this->custom_fields_to_replace = array_unique(array_merge($allCustomFields, $this->custom_fields_to_replace));

            $allCustomFields = $this->Subscriber_API->GetAllSubscriberCustomFields($this->jobdetails['Lists'],$allCustomFields, $recipients);
            $counter = 0;
            foreach ($allCustomFields as $subscriberId => $customFieldEntry) {
                $allSubscribersInfo[$counter] = $this->Subscriber_API->LoadSubscriberBasicInformation($subscriberId, $this->jobdetails['Lists']);
                $allSubscribersInfo[$counter]['CustomFields'] = $customFieldEntry;
                $counter++;
            }
            $dctEvent =new EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT();
            $dctEvent->lists = $this->jobdetails['Lists'];
            $dctEvent->info = $allSubscribersInfo;
            $dctEvent->trigger();
            $this->dynamic_content_replacement = $dctEvent->contentTobeReplaced;
        }

	/**
	* ResendJob_Setup
	* This sets up the scheduled send 'job' ready for a resend.
	* This includes fixing up user statistics, moving the unsent recipients from the unsent queue to the live queue and making the job ready to go.
	*
	* If an invalid jobid is passed in (not an in or it's less than 0) this will return false.
	* There needs to be a user object set up so statistics can be re-allocated properly. If this is not set up, the function returns false.
	*
	* @param Int $jobid The job we are re-setting up.
	*
	* @uses Jobs_API::GetJobQueue
	* @uses Jobs_API::GetJobQueueSize
	* @uses Jobs_API::RestoreUnsentQueue
	* @uses Stats_API::ChangeUserStats
	*
	* @return Boolean Returns false if there is an invalid jobid provided or if the user object is not set up properly. Otherwise, it does all of it's work and returns true.
	*/
	function ResendJob_Setup($jobid=0)
	{
		$jobid = (int)$jobid;
		if ($jobid <= 0) {
			return false;
		}

		if ($this->jobowner <= 0) {
			return false;
		}

		require_once(dirname(__FILE__) . '/jobs.php');
		require_once(dirname(__FILE__) . '/stats.php');

		$jobapi = new Jobs_API();
		$stats_api = new Stats_API();

		$sendqueue = $jobapi->GetJobQueue($jobid);

		// if we're resending a newsletter, restore the 'unsent' queue back to the original.
		$jobsize_info_before = $jobapi->GetJobQueueSize($jobid);

		$jobapi->RestoreUnsentQueue($jobid, $sendqueue, $this->jobowner, 'send');

		// then make sure we mark the emails as allocated in statistics.
		$restore_size = $jobapi->QueueSize($sendqueue, 'send');
		$new_size = $jobsize_info_before['totalsent'] + $restore_size;

		$stats_api->ChangeUserStats($this->jobowner, $jobid, $new_size);

		unset($jobapi);
		unset($stats_api);

		return true;
	}

	/**
	* NotifyOwner
	* This will notify the list owner(s) of job runs.
	* This will send the appropriate message depending on the state of the job.
	* If the job is not set to notify the owner, this does nothing.
	*
	* @see emailssent
	* @see jobdetails
	* @see jobstatus
	* @see Email_API::ForgetEmail
	* @see GetUser
	* @see Email_API::Set
	* @see Email_API::Subject
	* @see Email_API::FromName
	* @see Email_API::FromAddress
	* @see Email_API::Multipart
	* @see Email_API::AddBody
	* @see Email_API::ClearAttachments
	* @see Email_API::ClearRecipients
	* @see Email_API::AddRecipient
	* @see Email_API::Send
	* @see Sendstudio_Functions::PrintTime
	* @see Sendstudio_Functions::FormatNumber
	*
	* @return Void Doesn't return anything.
	*/
	function NotifyOwner()
	{
		if (empty($this->jobdetails)) {
			return;
		}

		if (!$this->jobdetails['NotifyOwner']) {
			return;
		}

		if (is_null($this->jobstatus)) {
			return;
		}

		/**
		 * If test mode is enabled, no point doing anything else.
		 */
		if (SENDSTUDIO_SEND_TEST_MODE) {
			return;
		}

		if (!class_exists('SS_Email_API', false)) {
			require_once SENDSTUDIO_API_DIRECTORY . DIRECTORY_SEPARATOR . 'ss_email.php';
		}

		$notify_email = new SS_Email_API;
		$owner = GetUser($this->jobowner);

		// Check if each user have SMTP settings specified.
		// Otherwise use the global SMTP settings.
		if ($owner->smtpserver) {
			$notify_email->SetSmtp($owner->smtpserver, $owner->smtpusername, $owner->smtppassword, $owner->smtpport);
		} else {
			$notify_email->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);
		}

		$time = $this->sendstudio_functions->PrintTime();

		/**
		 * If the notify email subject or message are empty, create them.
		 * This is mainly for backwards compatibility so the jobs_send.php file didn't need to be changed too much.
		 */
		if ($this->notify_email['subject'] == '' || $this->notify_email['message'] == '') {
			switch ($this->jobstatus) {
				case 'c':
					$subject = sprintf(GetLang('Job_Subject_Complete'), $this->newsletter['Subject']);
					if ($this->emailssent == 1) {
						$message = sprintf(GetLang('Job_Message_Complete_One'), $this->newsletter['Subject'], $time);
					} else {
						$message = sprintf(GetLang('Job_Message_Complete_Many'), $this->newsletter['Subject'], $time, $this->sendstudio_functions->FormatNumber($this->emailssent));
					}
				break;
				case 'p':
					$subject = sprintf(GetLang('Job_Subject_Paused'), $this->newsletter['Subject']);
					if ($this->emailssent == 1) {
						$message = sprintf(GetLang('Job_Message_Paused_One'), $this->newsletter['Subject'], $time);
					} else {
						$message = sprintf(GetLang('Job_Message_Paused_Many'), $this->newsletter['Subject'], $time, $this->sendstudio_functions->FormatNumber($this->emailssent));
					}
				break;
				default:
					$subject = sprintf(GetLang('Job_Subject_Started'), $this->newsletter['Subject']);
					$message = sprintf(GetLang('Job_Message_Started'), $this->newsletter['Subject'], $time);
			}

			$this->notify_email = array (
				'subject' => $subject,
				'message' => $message
			);
		}

		$subject = $this->notify_email['subject'];
		$message = $this->notify_email['message'];

		$notify_email->Set('Subject', $subject);
		$notify_email->Set('CharSet', SENDSTUDIO_CHARSET);
		if ($owner->fullname) {
			$notify_email->Set('FromName', $owner->fullname);
		} else {
			$notify_email->Set('FromName', GetLang('SendingSystem'));
		}

		if ($owner->emailaddress) {
			$notify_email->Set('FromAddress', $owner->emailaddress);
		} else {
			$notify_email->Set('FromAddress', GetLang('SendingSystem_From'));
		}

		$notify_email->Set('Multipart', false);

		$notify_email->AddBody('text', $message);

		$query = "SELECT listid, ownername, owneremail FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE listid IN(" . implode(',', $this->jobdetails['Lists']) . ")";
		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$notify_email->AddRecipient($row['owneremail'], $row['ownername'], 't');
		}

		$notify_email->Send();

		$notify_email->ForgetEmail();

		unset($notify_email);

		$this->notify_email['subject'] = '';
		$this->notify_email['message'] = '';
	}

}
