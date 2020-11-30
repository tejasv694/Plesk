<?php
/**
 * This is the api file for split tests to use.
 *
 * @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
 *
 * @package SendStudio
 * @subpackage SplitTests
 */

/**
 * Include the init file if we need to.
 * This allows us to use the base functions and settings.
 *
 * @uses init.php
 */
if (!class_exists('Send_API', false)) {
	require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/functions/api/send.php');
}

/**
 * This handles sending a split test campaign.
 * It is mostly used as a cache for multiple email objects, multiple newsletters etc to be saved in.
 * It handles common functionality for popup window sending and also cron split test sending.
 *
 * It also handles pausing, deleting, resuming and starting a split test send.
 *
 * @package SendStudio
 * @subpackage SplitTests
 */
class SplitTest_Send_API extends Send_API
{
	/**
	 * splitcampaign_details
	 * This is a 'cache' of the split campaign details we are currently sending.
	 * This is going to be used mainly for notifying the list owner's of a job's start/finish
	 *
	 * @usedby _ProcessJob
	 * @usedby _ActionJob
	 * @usedby ForgetSend
	 */
	protected $splitcampaign_details = array();

	/**
	 * queuetype
	 * This is used by the parent Send_API to work out which queue to process/clean up.
	 *
	 * @var String $queuetype Always set to 'splittest'
	 *
	 * @see Send_API
	 * @see Send_API::SendToRecipient
	 * @see Jobs_API::MarkAsProcessed
	 */
	protected $queuetype = 'splittest';

	/**
	 * newsletters
	 * This is a 'cache' of the newsletters that have been loaded.
	 * This is used so each newsletter is only loaded once
	 * and the details are temporarily stored here.
	 *
	 * @var Array $newsletters An array of newsletter details.
	 *
	 * @see SetupNewsletter
	 * @see SetupEmail
	 */
	private $newsletters = array();

	/**
	 * email_objects
	 * This is a 'cache' of the email objects that have been created.
	 * This is used so an email is only constructed once,
	 * then we can just switch between the objects for each email that needs to be sent.
	 *
	 * @var Array $email_objects An array of email objects.
	 *
	 * @see SetupEmail
	 */
	private $email_objects = array();

	/**
	 * _sending_newsletter
	 * Which newsletter id we are sending.
	 * This is used to work out which custom fields to give to the email api for replacement.
	 *
	 * @var Int $_sending_newsletter Which newsletter id we are currently processing.
	 *
	 * @see SetupNewsletter
	 * @see SetupCustomFields
	 * @see SendToRecipient
	 * @see SetupEmail
	 */
	private $_sending_newsletter = -1;

	/**
	 * statids is an array of:
	 * - newsletterid => statid
	 * so each newsletter being sent has it's own stat id.
	 *
	 * @Var Array $statids An array of newsletter => statid relationships.
	 *
	 * @see SetupNewsletter
	 * @see SetupEmail
	 */
	protected $statids = array();

	/**
	 * _jobid
	 * The current job id being processed.
	 */
	protected $_jobid = 0;

	/**
	 * _queueid
	 * The current queueid being processed.
	 */
	protected $_queueid = 0;

	/**
	 * __construct
	 * Does nothing apart from call the parent constructor.
	 *
	 * @uses Send_API::__construct
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * SetupNewsletter
	 * This is called before each recipient is emailed.
	 * It switches between the newsletters being sent
	 * and updates the jobdetails array to keep track of which one was last sent
	 * so if a job is paused etc, it's able to pick up from where it left off
	 *
	 * If a newsletter is already loaded into memory, it just sets the appropriate variables
	 * and returns.
	 *
	 * If a newsletter needs to be loaded, it will load it up,
	 * set the appropriate variables, set up the local cache
	 * and then return.
	 *
	 * @uses jobdetails
	 * @uses _sending_newsletter
	 * @uses Newsletters_API::Load
	 * @uses newsletters
	 */
	public function SetupNewsletter()
	{
		$all_newsletters = $this->jobdetails['newsletters'];

		if (!isset($this->jobdetails['sendinfo'])) {
			$this->jobdetails['sendinfo'] = array (
				'sendsize_left' => $this->jobdetails['sendsize'],
				'newsletter' => -1,
			);
		}

		/**
		 * We put the stat id's in the job details so if we pause a send then resume it later,
		 * we can keep the same id's.
		 */
		if (!isset($this->jobdetails['Stats'])) {
			$this->jobdetails['Stats'] = $this->statids;
		}

		/**
		 * We need to cast the existing 'newsletter' id to an int before adding 1 to it
		 * otherwise it doesn't add properly.
		 */
		$next_newsletter = (int)$this->jobdetails['sendinfo']['newsletter'] + 1;

		/**
		 * If there is no "next" newsletter,
		 * go back to the first one.
		 */
		if (!isset($all_newsletters[$next_newsletter])) {
			$next_newsletter = 0;
		}

		$this->jobdetails['sendinfo']['newsletter'] = $next_newsletter;

		$news_id = $this->jobdetails['newsletters'][$next_newsletter];

		/**
		 * This is used by SetupCustomFields to load things from the 'cache' if it can.
		 */
		$this->_sending_newsletter = $news_id;

		if (isset($this->newsletters[$news_id])) {
			return true;
		}

		// if we can't load the newsletter, pause it and immediately stop.
		$news_loaded = $this->Newsletters_API->Load($news_id);
		if (!$news_loaded) {
			return false;
		}

		$this->newsletters[$news_id] = array();
		$this->newsletters[$news_id]['Format'] = $this->Newsletters_API->Get('format');
		$this->newsletters[$news_id]['Subject'] = $this->Newsletters_API->Get('subject');
		$this->newsletters[$news_id]['TextBody'] = $this->Newsletters_API->Get('textbody');
		$this->newsletters[$news_id]['HTMLBody'] = $this->Newsletters_API->Get('htmlbody');
		$this->newsletters[$news_id]['Attachments'] = $this->sendstudio_functions->GetAttachments('newsletters', $news_id, true);

		/**
		 * This is used after sending an email to save stats for each newsletter that has been sent.
		 * So we'll be able to see how many each particular newsletter sent out successfully.
		 */
		if (!isset($this->jobdetails['sendinfo']['email_results'])) {
			$this->jobdetails['sendinfo']['email_results'] = array();
		}

		$this->jobdetails['sendinfo']['email_results'][$news_id] = array (
			'success' => 0,
			'fail' => 0
		);
		return true;
	}

	/**
	 * SetupJob
	 * This sets up some local class variables after checking a proper job/queue has been loaded.
	 * It mainly works out what 'pausetime' to use between each email being sent.
	 *
	 * @param Int $jobid The job we are processing
	 * @param Int $queueid The job queue we are processing
	 *
	 * @uses IsQueue
	 * @uses _jobid
	 * @uses _queueid
	 * @uses GetUser
	 * @uses jobowner
	 * @uses User_API::perhour
	 * @uses userpause
	 *
	 * @return Boolean Returns false if the queue is invalid. Otherwise sets the appropriate class variables and then returns true.
	 */
	function SetupJob($jobid=0, $queueid=0)
	{
		$is_queue = $this->IsQueue($queueid, $this->queuetype);
		if (!$is_queue) {
			return false;
		}

		$this->_jobid = $jobid;
		$this->_queueid = $queueid;

		$this->user = GetUser($this->jobowner);
                $group = $this->user->group;

                if (is_null($this->userpause)) {
			$pause = $pausetime = 0;
                   
			if ($group->limit_hourlyemailsrate > 0) {
                            $pause = $group->limit_hourlyemailsrate;
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
		return true;
	}

	/**
	 * SetupEmail
	 * Sets up the email object for use.
	 * If the email has been set up before, it sets the Email_API object to the cache version and returns.
	 *
	 * If it has not been set up before,
	 * the newsletter is loaded into the email class (subject, htmlbody, textbody, attachments)
	 *
	 * If it's a partial setup (the flag passed in), once the content has been loaded into the Email_API object
	 * the method returns.
	 *
	 * This is used to get custom field id's being used in the email campaigns
	 * so they can be loaded in bulk.
	 *
	 * If it's not a partial setup, then it
	 * - sets the appropriate smtp details
	 * - loads attachments
	 * - sets extra header information
	 * - sets the email header details (bounce, reply-to, from details etc)
	 * - after all of that, set the object into the email_objects cache for later use.
	 *
	 * @param Boolean $partial_setup Whether this is a partial setup or not. A partial set up is used to work out which custom fields are being used so they can be loaded in bulk.
	 *
	 * @uses Email_API
	 * @uses email_objects
	 * @uses newsletters
	 * @uses _sending_newsletter
	 * @uses SS_Email_API
	 */
	private function SetupEmail($partial_setup=false)
	{
		if (isset($this->email_objects[$this->_sending_newsletter])) {
			$this->Email_API = $this->email_objects[$this->_sending_newsletter];
			return;
		}

		/**
		 * Set up the email class
		 * so we can get the custom fields we need to.
		 */
		if (!isset($this->newsletters[$this->_sending_newsletter])) {
			return false;
		}
		$newsletter = $this->newsletters[$this->_sending_newsletter];

		$email_api = new SS_Email_API;

		if (SENDSTUDIO_SEND_TEST_MODE) {
			$email_api->TestMode = true;
		}

		$email_api->Set('Multipart', $this->jobdetails['Multipart']);
		$email_api->Set('Subject', $newsletter['Subject']);

		if ($this->jobdetails['Multipart']) {
			if ($newsletter['TextBody'] && $newsletter['HTMLBody'] && $newsletter['Format'] == 'b') {
				$sent_format = 'm';
			} else {
				$email_api->Set('Multipart', false);
			}
		}

		if ($newsletter['TextBody'] && in_array($newsletter['Format'], array('t', 'b'))) {
			$email_api->AddBody('text', $newsletter['TextBody']);
			$email_api->AppendBody('text', $this->user->Get('textfooter'));
			$email_api->AppendBody('text', stripslashes(SENDSTUDIO_TEXTFOOTER));
		}

		if ($newsletter['HTMLBody'] && in_array($newsletter['Format'], array('h', 'b'))) {
			$email_api->AddBody('html', $newsletter['HTMLBody']);
			$email_api->AppendBody('html', $this->user->Get('htmlfooter'));
			$email_api->AppendBody('html', stripslashes(SENDSTUDIO_HTMLFOOTER));
		}

		if ($partial_setup) {
			$this->Email_API = $email_api;
			return;
		}

		$email_api->ClearRecipients();

		$email_api->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

		$email_api->Set('statid', $this->statids[$this->_sending_newsletter]);
		$email_api->Set('listids', $this->jobdetails['Lists']);

		if (SENDSTUDIO_FORCE_UNSUBLINK) {
			$email_api->ForceLinkChecks(true);
		}

		$email_api->TrackOpens(true);
		$email_api->TrackLinks(true);

		$email_api->Set('CharSet', $this->jobdetails['Charset']);

		if (!SENDSTUDIO_SAFE_MODE) {
			$email_api->Set('imagedir', TEMP_DIRECTORY . '/send.' . $this->_jobid . '.' . $this->_queueid);
		} else {
			$email_api->Set('imagedir', TEMP_DIRECTORY . '/send');
		}

		// clear out the attachments just to be safe.
		$email_api->ClearAttachments();

		if ($newsletter['Attachments']) {
			$path = $newsletter['Attachments']['path'];
			$files = $newsletter['Attachments']['filelist'];
			foreach ($files as $p => $file) {
				$email_api->AddAttachment($path . '/' . $file);
			}
		}

		$email_api->Set('FromName', $this->jobdetails['SendFromName']);
		$email_api->Set('FromAddress', $this->jobdetails['SendFromEmail']);
		$email_api->Set('ReplyTo', $this->jobdetails['ReplyToEmail']);
		$email_api->Set('BounceAddress', $this->jobdetails['BounceEmail']);

		$email_api->Set('EmbedImages', $this->jobdetails['EmbedImages']);

		$email_api->Set('SentBy', $this->user->Get('userid'));

		if ($this->user->smtpserver) {
			$email_api->SetSmtp($this->user->smtpserver, $this->user->smtpusername, $this->user->smtppassword, $this->user->smtpport);
		}

		$this->email_objects[$this->_sending_newsletter] = $email_api;
		$this->Email_API = $email_api;
	}

	/**
	 * SetupAllNewsletters
	 * This basically loads all of the email campaigns/newsletters into memory
	 * so custom fields can be worked out in bulk when split test sending is run via cron.
	 *
	 * It loops over the jobdetails['newsletters'] array and calls SetupNewsletter and SetupCustomFields
	 * to load the custom fields to replace.
	 *
	 * @uses jobdetails
	 * @uses SetupNewsletter
	 * @uses SetupCustomFields
	 * @uses custom_fields_to_replace
	 * @uses to_customfields
	 * @uses _sending_newsletter
	 */
	function SetupAllNewsletters()
	{
		$all_fields = array();
		$to_fields = array();
		foreach ($this->jobdetails['newsletters'] as $newsletterid) {
			$this->SetupNewsletter();
			$this->SetupCustomFields();
			$all_fields = array_merge($all_fields, $this->custom_fields_to_replace);
			$to_fields = array_merge($to_fields, $this->to_customfields);
			$this->custom_fields_to_replace = $this->to_customfields = array();
		}

		$this->to_customfields = $to_fields;
		$this->custom_fields_to_replace = $all_fields;

		/**
		 * reset the 'newsletter sequence' back to -1
		 */
		$this->_sending_newsletter = -1;
	}

	/**
	 * SetupCustomFields
	 * Using the email api, works out which custom fields are included in the email content
	 * and also works out the "to" custom fields (eg "first name = fieldid 4, last name = fieldid 10").
	 *
	 * If the custom fields to replace array is already set up and an array of recipient id's are passed to this method,
	 * then it calls the Send_API::SetupCustomFields method which re-works some of the custom field field types.
	 *
	 * For example, date fields are re-formatted to the right order,
	 * checkbox/radio/select box fields are changed from key values to proper values
	 *
	 * If the custom_fields_to_replace array is empty, then it calls SetupEmail($partial_setup=true) which loads the newsletter into memory.
	 * Then it gets custom fields from that newsletter and works out the "to" custom fields.
	 *
	 * @param Array $recipients The recipients to set up custom fields for. This calls the Send_API::SetupCustomFields method to re-work some fields (date fields, checkbox/radio button
	 * fields)
	 *
	 * @uses all_customfields
	 * @uses custom_fields_to_replace
	 * @uses SetupEmail
	 * @uses to_customfields
	 * @uses Send_API::SetupCustomFields
	 * @uses SS_Email_API::GetCustomFields
	 */
	function SetupCustomFields($recipients=array())
	{
		$this->all_customfields = array();

		/**
		 * If the placeholders are already there, use them.
		 * Otherwise we'll have to recreate the details.
		 */

		if (!empty($this->custom_fields_to_replace)) {
			if (!empty($recipients)) {
				return parent::SetupCustomFields($recipients);
			}
			return;
		}

		$this->SetupEmail(true);

		$this->custom_fields_to_replace = $this->Email_API->GetCustomFields();

		$to_customfields = array();

		if ($this->jobdetails['To_FirstName']) {
			$to_customfields[] = $this->jobdetails['To_FirstName'];
		}

		if ($this->jobdetails['To_LastName']) {
			$to_customfields[] = $this->jobdetails['To_LastName'];
		}

		$this->to_customfields = array_unique($to_customfields);

		if (!empty($recipients)) {
			return parent::SetupCustomFields($recipients);
		}
		return;
	}

	/**
	 * SendToRecipient
	 * Sends an email to a particular recipient.
	 *
	 * It calls SetupEmail to load the right email into the Email_API class variable
	 * then calls the Send_API::SendToRecipient method.
	 *
	 * After that has been called, it updates job details which includes how many emails were sent per newsletter
	 * and how many emails are left to send overall.
	 *
	 * Then it calls UpdateJobDetails which actually saves the new details into the database.
	 *
	 * @param Int $recipient The recipient we are sending to. This is passed to the Send_API::SendToRecipient method to do it's work.
	 * @param Int $queueid The queue we are processing. This is passed to the Send_API::SendToRecipient method to do it's work.
	 *
	 * @uses SetupEmail
	 * @uses newsletter
	 * @uses newsletters
	 * @uses _sending_newsletter
	 * @uses jobdetails
	 * @uses UpdateJobDetails
	 * @see language/language.php for descriptions and error codes you can use here.
	 * @see API::Save_Unsent_Recipient as well.
	 *
	 * @return Array Returns an array of the mailing results, same as Send_API::SendToRecipient
	 */
	function SendToRecipient($recipient=0, $queueid=0)
	{
		$this->SetupEmail();

		/**
		 * These are for the send_api sendtorecipient method to use.
		 */
		$this->newsletter = $this->newsletters[$this->_sending_newsletter];
		$this->statid = $this->statids[$this->_sending_newsletter];

		$this->jobdetails['Newsletter'] = $this->_sending_newsletter;

		$mail_result = parent::SendToRecipient($recipient, $queueid, $this->queuetype);

		/**
		 * Once we've worked out whether the email was sent or not,
		 * save the details so we can update stats later on with the right results.
		 *
		 * Right now, we only care about success/failure.
		 */
		if ($mail_result['success'] > 0) {
			$this->jobdetails['sendinfo']['email_results'][$this->_sending_newsletter]['success']++;
		} else {
			$this->jobdetails['sendinfo']['email_results'][$this->_sending_newsletter]['fail']++;
		}
		$this->jobdetails['sendinfo']['sendsize_left']--;

		$this->UpdateJobDetails();

		return $mail_result;
	}

	/**
	 * UpdateJobDetails
	 * This updates the job details information in the database.
	 * This allows us to keep up to date with the last newsletter that was sent in the list to send.
	 * That in turn means if we pause a split test send (or it dies if it's running via scheduled sending)
	 * we'll know where to start back up again and we should get a pretty even spread of campaigns.
	 *
	 * @uses jobdetails
	 * @uses _jobid
	 *
	 * @return Boolean Returns whether the update query worked or not.
	 */
	function UpdateJobDetails()
	{
		$query = "UPDATE [|PREFIX|]jobs SET jobdetails='" . $this->Db->Quote(serialize($this->jobdetails)) . "' WHERE jobid=" . (int)$this->_jobid;
		$result = $this->Db->Query($query);
		if ($result) {
			return true;
		}
		return false;
	}

	/**
	 * StartJob
	 * Marks a job as 'started' in the database
	 * both in the 'jobs' table and in the 'splittests' table for ease of access.
	 * If the jobstatus is 'i' it also updates the 'lastsent' field for the split test table.
	 *
	 * @param Int $jobid The job we are starting
	 * @param Int $splitid The split test we are starting
	 * @param String $jobstatus The new jobstatus. It should be 'i' but can also be 'w' for 'w'aiting to be sent.
	 *
	 * @uses Jobs_API::StartJob
	 *
	 * @return Boolean Returns true if the parent startjob method returns true, and the splittests table was successfully updated. Returns false if any of those conditions fail.
	 */
	function StartJob($jobid=0, $splitid=0, $jobstatus='i')
	{
		$this->Db->StartTransaction();
		if ($jobstatus == 'i') {
			$status = parent::StartJob($jobid);
			if (!$status) {
				$this->Db->RollbackTransaction();
				return false;
			}
		}
		$query = "UPDATE [|PREFIX|]splittests SET jobstatus='" . $this->Db->Quote($jobstatus) . "', jobid=" . (int)$jobid;
		if ($jobstatus == 'i') {
			$query .= ", lastsent=" . $this->GetServerTime();
		}
		$query .= " WHERE splitid=" . (int)$splitid;
		$result = $this->Db->Query($query);
		if ($result) {
			$this->Db->CommitTransaction();
			return true;
		}
		$this->Db->RollbackTransaction();
		return false;
	}

	/**
	 * FinishJob
	 * Marks a job as 'finished' in the database.
	 * Updates both the 'jobs' table and the 'splittests' table for ease of access.
	 * It also updates the statistics table with the current time for the "finishtime".
	 *
	 * @param Int $jobid The job we are finishing
	 * @param Int $splitid The split test we are finishing
	 *
	 * @uses Jobs_API::FinishJob
	 *
	 * @return Boolean Returns true if the parent finishjob method returns true, and the splittests table was successfully updated. Returns false if any of those conditions fail.
	 */
	function FinishJob($jobid=0, $splitid=0)
	{
		$this->Db->StartTransaction();
		$status = parent::FinishJob($jobid);
		if (!$status) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$splitid = (int)$splitid;

		$queries = array();
		$queries[] = "UPDATE [|PREFIX|]splittests SET jobstatus='c', jobid=" . (int)$jobid . " WHERE splitid=" . $splitid;
		$queries[] = "UPDATE [|PREFIX|]splittest_statistics SET finishtime=" . $this->GetServerTime() . " WHERE splitid=" . $splitid . " AND jobid=" . $jobid;

		foreach ($queries as $query) {
			$result = $this->Db->Query($query);
			if (!$result) {
				$this->Db->RollbackTransaction();
				return false;
			}
		}
		$this->Db->CommitTransaction();
		return true;
	}

	/**
	 * _GetSplitByJobid
	 * Gets a split test id based on the jobid passed in.
	 * This is used if we only have a jobid to go by and no splitid (eg we're editing/deleting a 'scheduled job')
	 *
	 * @param Int $jobid The job we're actioning.
	 *
	 * @return Int Returns the splitid the job is for - comes from the fkid field from the tables table.
	 */
	private function _GetSplitByJobid($jobid=0)
	{
		$splitid = $this->Db->FetchOne("SELECT fkid FROM [|PREFIX|]jobs WHERE jobid=" . (int)$jobid . " AND fktype='splittest'");
		return $splitid;
	}

	/**
	 * PauseJob
	 * Pauses a job in the database.
	 * Updates both the 'jobs' table and the 'splittests' table.
	 *
	 * If no splitid is supplied, it uses the _GetSplitByJobid method to work out which split we are processing.
	 *
	 * @param Int $jobid The job we are pausing
	 * @param Int $splitid The splitid we are pausing. If not supplied, it is worked out.
	 *
	 * @uses Jobs_API::PauseJob
	 * @uses _GetSplitByJobid
	 *
	 * @return Boolean Returns true if the parent 'pausejob' method returns true and if we're able to find an appropriate split test (and we update that split test with the right info).
	 * Returns false if any of those conditions fail.
	 */
	function PauseJob($jobid=0, $splitid=0)
	{
		$this->Db->StartTransaction();
		$status = parent::PauseJob($jobid);
		if (!$status) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$splitid = (int)$splitid;
		$jobid = (int)$jobid;

		if ($splitid <= 0) {
			$splitid = $this->_GetSplitByJobid($jobid);
		}

		if ($splitid <= 0) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$query = "UPDATE [|PREFIX|]splittests SET jobstatus='p', jobid=" . $jobid . " WHERE splitid=" . $splitid;
		$result = $this->Db->Query($query);
		if ($result) {
			$this->Db->CommitTransaction();
			return true;
		}
		$this->Db->RollbackTransaction();
		return false;
	}

	/**
	 * ResumeJob
	 * Marks a job as 'resumed' in the database.
	 * Updates both the 'jobs' table and the 'splittests' table.
	 *
	 * If no splitid is supplied, it uses the _GetSplitByJobid method to work out which split we are processing.
	 *
	 * @param Int $jobid The job we are resuming.
	 * @param Int $splitid The splitid we are resuming. If not supplied, it is worked out.
	 *
	 * @uses Jobs_API::ResumeJob
	 * @uses _GetSplitByJobid
	 *
	 * @return Boolean Returns true if the parent 'resumejob' method returns true and if we're able to find an appropriate split test (and we update that split test with the right info).
	 * Returns false if any of those conditions fail.
	 */
	function ResumeJob($jobid=0, $splitid=0)
	{
		$this->Db->StartTransaction();
		$status = parent::ResumeJob($jobid);
		if (!$status) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$splitid = (int)$splitid;

		if ($splitid <= 0) {
			$splitid = $this->_GetSplitByJobid($jobid);
		}

		if ($splitid <= 0) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$query = "UPDATE [|PREFIX|]splittests SET jobstatus='w' WHERE splitid=" . $splitid;
		$result = $this->Db->Query($query);
		if ($result) {
			$this->Db->CommitTransaction();
			return true;
		}
		$this->Db->RollbackTransaction();
		return false;
	}

	/**
	 * DeleteJob
	 * Deletes a job from the 'jobs' table and also updates the 'splittests' table with the new jobstatus/jobid.
	 *
	 * If no splitid is supplied, it uses the _GetSplitByJobid method to work out which split we are processing.
	 *
	 * @param Int $jobid The job we are deleting.
	 * @param Int $splitid The splitid we are deleting. If not supplied, it is worked out.
	 *
	 * @uses Jobs_API::Delete
	 * @uses _GetSplitByJobid
	 *
	 * @return Boolean Returns true if the parent 'delete' method returns true and if we're able to find an appropriate split test (and we update that split test with the right info).
	 * Returns false if any of those conditions fail.
	 */
	function DeleteJob($jobid=0, $splitid=0)
	{
		$splitid = (int)$splitid;

		if ($splitid <= 0) {
			$splitid = $this->_GetSplitByJobid($jobid);
		}

		if ($splitid <= 0) {
			return false;
		}

		$this->Db->StartTransaction();

		$status = parent::Delete($jobid);
		if (!$status) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$query = "UPDATE [|PREFIX|]splittests SET jobstatus=null, jobid=0 WHERE splitid=" . $splitid;
		$result = $this->Db->Query($query);
		if ($result) {
			$this->Db->CommitTransaction();
			return true;
		}
		$this->Db->RollbackTransaction();
		return false;
	}

	/**
	 * TimeoutJob
	 * Marks a job for "timeout".
	 * A "timeout" job status is the state between the first X % has been sent to a list
	 * and when the rest of the newsletter(s) should be sent to the other (100-X)% of the list.
	 *
	 * A job is "timed out" from the end of the first X%, this method works out the delay before sending the rest.
	 *
	 * @param Int $jobid The job we are timing out.
	 * @param Int $splitid The split campaign we're timing out. If not supplied, it is worked out.
	 * @param Int $hoursafter The number of hours to time out the campaign for. If not supplied, it is worked out.
	 *
	 * @uses _GetSplitByJobid
	 * @uses Splittest_API::Load
	 *
	 * @return Boolean Returns true if the job can be timed out and an appropriate hoursafter delay is set (or calculated). If the split test can't be found, or an invalid hoursafter
	 * delay is passed in/calculated, it will return false.
	 */
	protected function TimeoutJob($jobid=0, $splitid=null, $hoursafter=null)
	{
		$jobid = (int)$jobid;
		if ($jobid <= 0) {
			return false;
		}

		if ($splitid === null) {
			$splitid = $this->_GetSplitByJobid($jobid);
		}

		$splitid = (int)$splitid;
		if ($splitid <= 0) {
			return false;
		}

		if ($hoursafter === null) {
			$split_api = new Splittest_API;
			$split_details = $split_api->Load($splitid);
			$hoursafter = $split_details['splitdetails']['hoursafter'];
		}

		$hoursafter = (int)$hoursafter;

		if ($hoursafter <= 0) {
		 	return false;
		}

		$this->Db->StartTransaction();

		$new_jobtime = $this->GetServerTime() + ($hoursafter * 3600);

		$query = "UPDATE [|PREFIX|]jobs SET jobstatus='t', jobtime=" . $new_jobtime . " WHERE jobid=" . $jobid;
		$result = $this->Db->Query($query);
		if (!$result) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$query = "UPDATE [|PREFIX|]splittests SET jobstatus='t' WHERE splitid=" . $splitid;
		$result = $this->Db->Query($query);
		if (!$result) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$this->Db->CommitTransaction();
		return true;
	}

	/**
	 * ForgetSend
	 * Forgets the current split test campaign being sent.
	 * This is used between split test campaigns being sent in one cron run.
	 *
	 * @uses ResetSend
	 * @uses newsletters
	 * @uses statids
	 * @uses custom_fields_to_replace
	 * @uses to_customfields
	 * @uses _sending_newsletter
	 * @uses _jobid
	 * @uses _queueid
	 * @uses jobdetails
	 * @uses splitcampaign_details
	 */
	protected function ForgetSend()
	{
		$this->ResetSend();

		$this->newsletters = array();
		$this->statids = array();
		$this->custom_fields_to_replace = array();
		$this->to_customfields = array();
		$this->_sending_newsletter = -1;
		$this->_jobid = 0;
		$this->_queueid = 0;
		$this->jobdetails = array();
		$this->splitcampaign_details = array();
	}

	/**
	 * SaveSplitStats
	 * This saves split test statistics into the database
	 * It also saves the split send -> stats_newsletter statid relationship
	 *
	 * This is mainly because you can delete 'jobs' (which normally contains this data)
	 * but we need this to be permanent for displaying later on.
	 *
	 * @param Int $splitid The split we are saving stats for.
	 * @param Int $jobid The job we are sending
	 * @param Array $statids The newsletter_stats statid's that were created in the send setup.
	 *
	 * @return Boolean Returns true if the stats can be saved, otherwise false (or if any data is invalid it returns false).
	 */
	public function SaveSplitStats($splitid=0, $jobid=0, $statids=array())
	{
		$splitid = (int)$splitid;
		$jobid = (int)$jobid;
		$statids = $this->CheckIntVars($statids);

		if ($splitid <= 0 || $jobid <= 0 || empty($statids)) {
			return false;
		}

		$this->Db->StartTransaction();

		$query = "INSERT INTO [|PREFIX|]splittest_statistics (splitid, jobid, starttime, finishtime, hiddenby)
			VALUES
			(" . $splitid . ", " . $jobid . ", " . $this->GetServerTime() . ", 0, 0)";

		$result = $this->Db->Query($query);
		if (!$result) {
			$this->Db->RollbackTransaction();
			return false;
		}

		$split_statid = $this->Db->LastId('[|PREFIX|]splittest_statistics_sequence');
		if ($split_statid <= 0) {
			$this->Db->RollbackTransaction();
			return false;
		}

		foreach ($statids as $statid) {
			$query = "INSERT INTO [|PREFIX|]splittest_statistics_newsletters (split_statid, newsletter_statid) VALUES (" . $split_statid . ", " . $statid . ")";
			$result = $this->Db->Query($query);
			if (!$result) {
				$this->Db->RollbackTransaction();
				return false;
			}
		}

		$this->Db->CommitTransaction();
		return true;
	}
}
