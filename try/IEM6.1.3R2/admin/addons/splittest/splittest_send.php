<?php
/**
 * This file handles the processing of sending a split test.
 * It is a separate file so that the size and complexity of the main split test addon file
 * is kept to a minimum.
 * That is required so when an addon is included,
 * memory usage, code complexity (ie php parsing time) is kept as low as possible.
 *
 * @package Interspire_Addons
 * @subpackage Addons_splittest
 */

/**
 * If this class has been called from outside the Addons_splittest class, then die.
 * It's going to be an invalid request.
 */
if (!class_exists('Addons_splittest', false)) {
	die;
}

/**
 * Addons_splittest_Send
 * This class handles all aspects of sending a split test, from
 * - displaying the forms
 * - checking form values (ie you're posting right data)
 * - printing success/error messages at each step
 * etc
 *
 * It can only be included by the Addons_splittest class.
 * Trying to include it by itself will cause errors and the script will just die.
 *
 * @usedby Addons_splittest
 */
class Addons_splittest_Send extends Addons_splittest
{

	/**
	 * addon_id
	 * We need to masquerade as the 'splittest' addon
	 * So we get the right template path, template url,
	 * admin url and so on from the parent Interspire_Addons system.
	 *
	 * @var String
	 *
	 * @usedby Interspire_Addons::__construct
	 */
	protected $addon_id = 'splittest';

	/**
	 * __construct
	 *
	 * Calls the parent constructor to set up various options including:
	 * - template path
	 * - template url
	 * - admin url
	 * - include the language file(s)
	 * etc
	 *
	 * @uses Interspire_Addons::__construct
	 *
	 * @return Void Doesn't return anything.
	 */
	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Show_Send_Step_1
	 * This shows the first step of the send process where the user chooses a list or segment to send to.
	 *
	 * You cannot send to part of a list (eg by searching),
	 * you can only send a split test to whole list(s) or whole segment(s).
	 *
	 * If the user has no lists or segments, they are taken back to the default page which shows a list of split test campaigns
	 * They are shown a message saying they need to create/be given access to a list or segment before trying to do a send.
	 *
	 * If there are lists or segments, then the user is shown a list of them to choose from.
	 * They must select at least one list or segment before going to the next step.
	 *
	 * It also clears out the session variable which holds the split test send details.
	 *
	 * Step 1 also checks what type of split campaign you are trying to send.
 	 * You can't send 'percentage' split campaigns if cron is not enabled.
	 * The 'percentage' split campaigns send a percentage of emails to a random selection of subscribers
	 * then pauses, then sends the rest of the emails to the 'winner' (which has the most opens/clicked links).
	 * This could probably be worked out if you don't have cron support but for now,
	 * you can't send 'percentage' split campaigns if there is no cron.
	 *
	 * @uses GetUser
	 * @uses User_API::GetLists
	 * @uses User_API::GetSegmentList
	 * @uses Session::Remove
	 * @uses FlashMessage
	 */
	public function Show_Send_Step_1()
	{
		$user = GetUser();
		$user_lists = $user->GetLists();

		$user_segments = $user->GetSegmentList();
		if (empty($user_lists) && empty($user_segments)) {
			FlashMessage(GetLang('Addon_splittest_Send_NoListsOrSegments'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$splitid = 0;
		if (isset($_GET['id'])) {
			$splitid = (int)$_GET['id'];
		}

		if ($splitid <= 0) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$api = $this->GetApi();
		$split_campaign_details = $api->Load($splitid);
		if (empty($split_campaign_details)) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		if (!empty($split_campaign_details['jobstatus']) && $split_campaign_details['jobstatus'] != 'c') {
			FlashMessage(GetLang('Addon_splittest_Send_CannotSend_StillSending'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		/**
		 * If it's a percentage send, then check if cron is enabled.
		 * If it's not, then don't allow the user to go any further.
		 */
		if ($split_campaign_details['splittype'] == 'percentage') {
			if (!self::CheckCronEnabled()) {
				$message = sprintf(GetLang('Addon_splittest_CannotSendPercentage_NoCron'), IEM::urlFor('Settings', array('Tab' => 4)));
				FlashMessage($message, SS_FLASH_MSG_ERROR, $this->admin_url);
				return;
			}
		}

		/**
		 * Only show segment options if the user has access to send to segments.
		 */
		$this->template_system->Assign('DisplaySegments', $user->HasAccess('Segments', 'Send'));

		IEM::sessionRemove('SplitTestSend');
		$send_details = array();
		$send_details['splitid'] = $splitid;
		IEM::sessionSet('SplitTestSend', $send_details);

		$this->template_system->Assign('FlashMessages', GetFlashMessages(), false);

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);

		$this->template_system->Assign('user_lists', $user_lists);
		$this->template_system->Assign('user_segments', $user_segments);

		$this->template_system->ParseTemplate('send_step1');
	}

	/**
	 * Show_Send_Step_2
	 * This handles the second step of the send process where
	 *
	 * 1) the first step is verified in php
	 * - checks you are choosing a list or segment
	 * - checks the list/segment id's are valid (ie they are proper id's and not non-numeric values)
	 * - checks the list or segment has contacts in it
	 *
	 * If that checks out ok, the second step shows the form where you set:
	 * - from details
	 * - bounce details
	 * - whether to send the campaigns as multipart (where possible)
	 * - whether to embed images or not (where possible)
	 * - choose the first/last name custom fields
	 * - when to send the split test (if cron is enabled)
	 *
	 * @uses FlashMessage
	 * @uses GetFlashMessages
	 * @uses List_API::Load
	 * @uses List_API::GetCustomFields
	 * @uses Segment_API::Load
	 * @uses GetUser
	 * @uses User_API::HasAccess
	 * @uses CheckCronEnabled
	 */
	public function Show_Send_Step_2()
	{
		$send_details = IEM::sessionGet('SplitTestSend');

		/**
		 * Check the user has been through step 1 successfully.
		 */
		if (!$send_details || !isset($send_details['splitid']) || (int)$send_details['splitid'] <= 0) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$step1_url = $this->admin_url . '&Action=Send&id=' . $send_details['splitid'];

		$flash_messages = GetFlashMessages();

		/**
		 * If we are not posting a form, maybe we're being redirected back to this step from step 3.
		 * If we are, we'll have flash messages.
		 *
		 * If there are no post variables and we are not showing any messages, then go back to step 1.
		 */
		if (empty($_POST) && empty($flash_messages)) {
			FlashMessage(GetLang('Addon_splittest_Send_ChooseListOrSegment'), SS_FLASH_MSG_ERROR, $step1_url);
			return;
		}

		/**
		 * If we are posting a form, check we are posting a proper form (we should have lists or segments chosen)
		 */
		if (!empty($_POST)) {
			/**
			 * Work out which option the user chose
			 */
			$sending_to = array();
			$send_type = '';
			switch ((int)$_POST['ShowFilteringOptions']) {
				case 1:
					$send_type = 'list';
					if (isset($_POST['lists'])) {
						$sending_to = $_POST['lists'];
					}
				break;

				case 2:
					$send_type = 'segment';
					if (isset($_POST['segments'])) {
						$sending_to = $_POST['segments'];
					}
				break;

				default:
					FlashMessage(GetLang('Addon_splittest_Send_ChooseListOrSegment'), SS_FLASH_MSG_ERROR, $step1_url);
					return;
				break;
			}

			/**
			 * Make sure the user actually chose some options.
			 */
			if (empty($sending_to)) {
				FlashMessage(GetLang('Addon_splittest_Send_ChooseListOrSegment'), SS_FLASH_MSG_ERROR, $step1_url);
				return;
			}

			/**
			 * Make sure the id's the user chose are valid
			 * If any area invalid (not int's), the user is thrown back to the first step.
			 */
			foreach ($sending_to as $id) {
				$id = (int)$id;
				if ($id <= 0) {
					FlashMessage(GetLang('Addon_splittest_Send_ChooseListOrSegment'), SS_FLASH_MSG_ERROR, $step1_url);
					return;
				}
			}

			/**
			 * After everything has been validated, store the settings and display the next form.
			 */
			$send_details['sendingto'] = array (
				'sendtype' => $send_type,
				'sendids' => $sending_to
			);

			IEM::sessionSet('SplitTestSend', $send_details);
		}

		$user = GetUser();

		/**
		 * Re-set these variables.
		 * They may be coming from the session if we are being redirected back to step 2 from another step.
		 */
		$send_type = $send_details['sendingto']['sendtype'];
		$sending_to = $send_details['sendingto']['sendids'];

		/**
		 * Get the first list or segment we're sending to.
		 * We need this to set the defaults for the from name, email, reply-to email and bounce details.
		 * It doesn't really matter what the id is, the user can override the options anyway through the form.
		 */
		$id = $sending_to[0];

		$send_size = 0;

		/**
		 * We always need the list api
		 * so we can load the right details regardless of whether the user is sending to a segment or list.
		 */
		require_once SENDSTUDIO_API_DIRECTORY . '/lists.php';
		$list_api = new Lists_API;

		switch ($send_type) {
			case 'list':
				$list_id = $id;
				$listids = $sending_to;

				$user_lists = $user->GetLists();

				foreach ($user_lists as $user_list_id => $user_list_details) {
					if (!in_array($user_list_id, $sending_to)) {
						continue;
					}
					$send_size += $user_list_details['subscribecount'];
				}

				// this is used at a later step to check duplicates etc.
				$send_details['sendingto']['Lists'] = $sending_to;
			break;

			case 'segment':
				require_once SENDSTUDIO_API_DIRECTORY . '/subscribers.php';
				$api = new Subscribers_API;
				$segment_info = $api->GetSubscribersFromSegment($sending_to, true);

				$send_size = $segment_info['count'];

				/**
				 * Since a segment can go across lists,
				 * we only need one list - so just get the first one we come across.
				 * The user can change the details on the fly anyway.
				 */
				$listids = $segment_info['lists'];
				$list_id = $listids[0];

				// this is used at a later step to check duplicates etc.
				$send_details['sendingto']['Lists'] = $listids;
			break;
		}

		if ($send_size <= 0) {
			$var = 'Addon_splittest_Send_NoContacts_';
			if (sizeof($sending_to) == 1) {
				$var .= 'One_';
			} else {
				$var .= 'Many_';
			}
			$var .= $send_type;
			FlashMessage(GetLang($var), SS_FLASH_MSG_ERROR, $step1_url);
			return;
		}

		$send_details['sendsize'] = $send_size;
		IEM::sessionSet('SplitTestSend', $send_details);

		/**
		 * Get the flashmessage to draw the box.
		 * then add to the existing messages (eg we're back here from step 3).
		 */
		if ($send_size == 1) {
			FlashMessage(GetLang('Addon_splittest_Send_Step2_Size_One'), SS_FLASH_MSG_SUCCESS);
		} else {
			FlashMessage(sprintf(GetLang('Addon_splittest_Send_Step2_Size_Many'), $this->PrintNumber($send_size)), SS_FLASH_MSG_SUCCESS);
		}

		$flash_messages .= GetFlashMessages();

		$this->template_system->Assign('FlashMessages', $flash_messages, false);

		if (self::CheckCronEnabled()) {
			$this->template_system->Assign('CronEnabled', true);

			/**
			 * Get the sendstudio functions file to create the date/time box.
			 */
			require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';

			/**
			 * also need to load the 'send' language file so it can put in the names/descriptions.
			 */
			$ssf = new SendStudio_Functions;
			$ssf->LoadLanguageFile('send');
			$timebox = $ssf->CreateDateTimeBox(0, false, 'datetime', true);

			$this->template_system->Assign('ScheduleTimeBox', $timebox, false);
		}

		$this->template_system->Assign('ShowBounceInfo', $user->HasAccess('Lists', 'BounceSettings'));

		$this->template_system->Assign('DisplayEmbedImages', SENDSTUDIO_ALLOW_EMBEDIMAGES);
		$this->template_system->Assign('EmbedImagesByDefault', SENDSTUDIO_DEFAULT_EMBEDIMAGES);

		$list_api->Load($list_id);
		$details = array (
			'fromname' => $list_api->Get('ownername'),
			'fromemail' => $list_api->Get('owneremail'),
			'replytoemail' => $list_api->Get('replytoemail'),
			'bounceemail' => $list_api->Get('bounceemail'),
		);

		$customfield_settings = array();
		$list_customfields = $list_api->GetCustomFields($listids);
		foreach ($list_customfields as $fieldid => $fielddetails) {
			if (strtolower($fielddetails['fieldtype']) != 'text') {
				continue;
			}
			$customfield_settings[$fieldid] = htmlspecialchars($fielddetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		}

		$show_customfields = false;
		if (!empty($customfield_settings)) {
			$show_customfields = true;
		}

		$this->template_system->Assign('CustomFields', $customfield_settings);
		$this->template_system->Assign('ShowCustomFields', $show_customfields);

		foreach ($details as $name => $value) {
			$this->template_system->Assign($name, $value);
		}

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);
		$this->template_system->ParseTemplate('send_step2');
	}

	/**
	 * Show_Send_Step_3
	 * Step 3 shows the user a report including:
	 * - which split test they are sending
	 * - how many subscribers it will be sent to
	 * - which lists/segments they are sending to
	 *
	 * The user has to confirm they want to either schedule the campaign
	 * or they can start sending the split test campaign
	 * or (in either case), they can "cancel" the send
	 * eg they chose the wrong split test to send or something
	 *
	 * @uses Jobs_API
	 * @uses Stats_API
	 * @uses Splittest_Send_API
	 * @uses CheckCronEnabled
	 * @uses Splittest_API::Load
	 * @uses Stats_API::CheckUserStats
	 * @uses Lists_API
	 * @uses Segment_API
	 * @uses GetApi
	 */
	public function Show_Send_Step_3()
	{
		$send_details = IEM::sessionGet('SplitTestSend');

		/**
		 * Check the user has been through step 1 successfully.
		 */
		if (!$send_details || !isset($send_details['splitid']) || (int)$send_details['splitid'] <= 0) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		/**
		 * Make sure we're posting a proper form.
		 */
		if (empty($_POST)) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$required_fields = array (
			'sendfromname' => 'EnterSendFromName',
			'sendfromemail' => 'EnterSendFromEmail',
			'replytoemail' => 'EnterReplyToEmail',
		);

		$erors = array();
		foreach ($required_fields as $fieldname => $lang_description) {
			if (!isset($_POST[$fieldname])) {
				$errors[] = GetLang('Addon_splittest_Send_Step3_' . $lang_description);
				continue;
			}
			$posted_value = trim($_POST[$fieldname]);
			if ($posted_value == '') {
				$errors[] = GetLang('Addon_splittest_Send_Step3_' . $lang_description);
				continue;
			}
		}

		if (!empty($errors)) {
			$errormsg = implode('<br/>', $errors);
			FlashMessage(sprintf(GetLang('Addon_splittest_Send_Step3_FieldsMissing'), $errormsg), SS_FLASH_MSG_ERROR, $this->admin_url . '&Action=Send&Step=2');
			return;
		}

		require_once SENDSTUDIO_API_DIRECTORY . '/jobs.php';
		$jobapi = new Jobs_API;

		require_once SENDSTUDIO_API_DIRECTORY . '/stats.php';
		$statsapi = new Stats_API;

		$send_api = $this->GetApi('Splittest_Send');

		$send_details['SendFromName'] = $_POST['sendfromname'];
		$send_details['SendFromEmail'] = $_POST['sendfromemail'];
		$send_details['ReplyToEmail'] = $_POST['replytoemail'];

		/**
		 * If the user has access to set bounce details, this will be available.
		 * If they don't, we'll use the email from the settings page.
		 */
		if (isset($_POST['bounceemail'])) {
			$send_details['BounceEmail'] = $_POST['bounceemail'];
		} else {
			$send_details['BounceEmail'] = SENDSTUDIO_BOUNCE_ADDRESS;
		}

		/**
		 * Set the charset.
		 */
		$send_details['Charset'] = SENDSTUDIO_CHARSET;

		$to_firstname = false;
		if (isset($_POST['to_firstname']) && (int)$_POST['to_firstname'] > 0) {
			$to_firstname = (int)$_POST['to_firstname'];
		}

		$send_details['To_FirstName'] = $to_firstname;

		$to_lastname = false;
		if (isset($_POST['to_lastname']) && (int)$_POST['to_lastname'] > 0) {
			$to_lastname = (int)$_POST['to_lastname'];
		}

		$send_details['To_LastName'] = $to_lastname;

		$send_details['SendStartTime'] = $send_api->GetServerTime();

		foreach (array('success', 'total', 'failure') as $area) {
			$send_details['EmailResults'][$area] = 0;
		}

		$send_details['NotifyOwner'] = 1;

		/**
		 * Split campaigns have to track opens & link clicks
		 * There's no other way they will work.
		 */
		$send_details['TrackOpens'] = $send_details['TrackLinks'] = 1;

		$send_details['Multipart'] = 0;
		if (isset($_POST['multipart'])) {
			$send_details['Multipart'] = 1;
		}

		$send_details['EmbedImages'] = 0;
		if (isset($_POST['embedimages'])) {
			$send_details['EmbedImages'] = 1;
		}

		/**
		 * If cron is enabled, we'll get new info we need to take into account.
		 */
		if (self::CheckCronEnabled()) {
			/**
			 * The default (from above) is to "notify", so if it's not set, don't notify.
			 */
			if (!isset($_POST['notifyowner'])) {
				$send_details['NotifyOwner'] = 0;
			}

			/**
			 * If we're not sending immediately, then check the date/time.
			 * We don't allow sending in past dates.
			 */
			if (!isset($_POST['sendimmediately'])) {

				$hrs = $_POST['sendtime_hours'];

				$am_pm = null;
				if (isset($_POST['sendtime_ampm'])) {
					$am_pm = strtolower($_POST['sendtime_ampm']);
				}

				if ($am_pm == 'pm' && $hrs < 12) {
					$hrs += 12;
				}
				if ($am_pm == 'am' && $hrs == 12) {
					$hrs = 0;
				}

				$gmt_check_time = AdjustTime(array($hrs, $_POST['sendtime_minutes'], 0, $_POST['datetime']['month'], $_POST['datetime']['day'], $_POST['datetime']['year']), true);
				$now = $send_api->GetServerTime();

				/**
				 * There's a leeway of 5 minutes just in case there are any server/time issues.
				 */
				$leeway = 5 * 60;
				if ($gmt_check_time < ($now - $leeway)) {
					FlashMessage(GetLang('Addon_splittest_Send_Step2_SendingInPast'), SS_FLASH_MSG_ERROR, $this->admin_url . '&Action=Send&Step=2');
					return;
				}
				$send_details['SendStartTime'] = $gmt_check_time;
			}
		}

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);

		$api = $this->GetApi();
		$split_campaign_details = $api->Load($send_details['splitid']);

		$sendingCampaigns = array();
		$send_details['newsletters'] = array();
		foreach ($split_campaign_details['splittest_campaigns'] as $campaignid => $campaignname) {
			$sendingCampaigns[$campaignid] = htmlspecialchars($campaignname, ENT_QUOTES, SENDSTUDIO_CHARSET);
			$send_details['newsletters'][$campaignid] = $campaignid;
		}

		/**
		 * Before saving the job details, randomize the newsletter order.
		 * This is so we don't send the newsletters in the same order every time.
		 */
		shuffle($send_details['newsletters']);

		$send_list = array();

		switch ($send_details['sendingto']['sendtype']) {
			case 'list':
				require_once SENDSTUDIO_API_DIRECTORY . '/lists.php';
				$list_api = new Lists_API;
				foreach ($send_details['sendingto']['sendids'] as $listid) {
					$list_api->Load($listid);
					$send_list[] = htmlspecialchars($list_api->Get('name'), ENT_QUOTES, SENDSTUDIO_CHARSET);
				}

				$this->template_system->Assign('SendingToLists', true);
			break;

			case 'segment':
				require_once SENDSTUDIO_API_DIRECTORY . '/segment.php';
				$segment_api = new Segment_API;
				foreach ($send_details['sendingto']['sendids'] as $segmentid) {
					$segment_api->Load($segmentid);
					$send_list[] = htmlspecialchars($segment_api->Get('segmentname'), ENT_QUOTES, SENDSTUDIO_CHARSET);
				}
				// The job expects a 'Segments' element, otherwise it will send to entire lists.
				$send_details['Segments'] = $send_details['sendingto']['sendids'];

				$this->template_system->Assign('SendingToSegments', true);
			break;
		}

		$subscriber_count = $send_details['sendsize'];

		$user = IEM::userGetCurrent();

		if ($user->HasAccess('Newsletters')) {
			$this->template_system->Assign('ApplicationUrl', $this->application_url, false);
			$this->template_system->Assign('NewsletterView', true);
		}

		$send_criteria = array();

		// can only send to active subscribers.
		$send_criteria['Status'] = 'a';

		$send_criteria['List'] = $send_details['sendingto']['Lists'];

		$send_details['SendCriteria'] = $send_criteria;

		$check_stats = $statsapi->CheckUserStats($user, $subscriber_count);

		list($ok_to_send, $not_ok_to_send_reason) = $check_stats;

		if (!$ok_to_send) {
			require_once SENDSTUDIO_LANGUAGE_DIRECTORY . '/default/send.php';
			FlashMessage(GetLang($not_ok_to_send_reason), SS_FLASH_MSG_ERROR, $this->admin_url . '&Action=Send&Step=2');
			return;
		}

		/**
		 * The 'job' expects a 'Lists' element, so just point it to the sendingto lists.
		 */
		$send_details['Lists'] = $send_details['sendingto']['Lists'];

		$send_details['SendSize'] = $subscriber_count;

		$jobcreated = $jobapi->Create('splittest', $send_details['SendStartTime'], $user->userid, $send_details, 'splittest', $send_details['splitid'], $send_details['sendingto']['Lists']);

		$send_details['Job'] = $jobcreated;

		IEM::sessionSet('JobSendSize', $subscriber_count);

		// if we're not using scheduled sending, create the queue and start 'er up!
		if (!self::CheckCronEnabled()) {
			require_once SENDSTUDIO_API_DIRECTORY . '/subscribers.php';
			$subscriberApi = new Subscribers_API;

			$sendqueue = $subscriberApi->CreateQueue('splittest');

			$jobapi->StartJob($jobcreated);

			$queuedok = $jobapi->JobQueue($jobcreated, $sendqueue);

			$queueinfo = array('queueid' => $sendqueue, 'queuetype' => 'splittest', 'ownerid' => $user->userid);

			if ($send_details['sendingto']['sendtype'] == 'segment') {
				$subscriberApi->GetSubscribersFromSegment($send_details['sendingto']['sendids'], false, $queueinfo, 'nosort');
			} else {
				$subscriberApi->GetSubscribers($send_criteria, array(), false, $queueinfo, $user->userid);
			}

			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$subscriberApi->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
			}

			$subscriberApi->RemoveDuplicatesInQueue($sendqueue, 'splittest', $send_details['sendingto']['Lists']);

			$subscriberApi->RemoveBannedEmails($send_details['sendingto']['Lists'], $sendqueue, 'splittest');

			$subscriberApi->RemoveUnsubscribedEmails($send_details['sendingto']['Lists'], $sendqueue, 'splittest');

			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$subscriberApi->Db->OptimizeTable(SENDSTUDIO_TABLEPREFIX . "queues");
			}

			$send_details['SendSize'] = $subscriberApi->QueueSize($sendqueue, 'splittest');

			$send_details['Stats'] = array();

			$statids = array();

			foreach ($send_details['newsletters'] as $newsletterid) {
				$newsletterstats = $send_details;
				$newsletterstats['Job'] = $jobcreated;
				$newsletterstats['Queue'] = $sendqueue;
				$newsletterstats['SentBy'] = $queueinfo['ownerid'];
				$newsletterstats['SendType'] = 'splittest';
				$newsletterstats['Newsletter'] = $newsletterid;
				$newsletterstats['Lists'] = $send_details['sendingto']['Lists'];
				$newsletterstats['SendCriteria'] = $send_criteria;

				$statid = $statsapi->SaveNewsletterStats($newsletterstats);
				$statids[] = $statid;

				$send_details['Stats'][$newsletterid] = $statid;

				$statsapi->RecordUserStats($user->userid, $jobcreated, $subscriber_count, $send_details['SendStartTime'], $statid);
			}

			$send_api->SaveSplitStats($send_details['splitid'], $jobcreated, $statids);

			$jobapi->PauseJob($jobcreated);
		}

		$this->template_system->Assign('sendingCampaigns', $sendingCampaigns);
		$this->template_system->Assign('sendLists', $send_list);

		$send_size = $send_details['SendSize'];

		IEM::sessionSet('SplitTestSendDetails', $send_details);

		/**
		 * This is used to work out if we should automatically clean up a half-finished send process.
		 * We need to do this because a half-finished send may have taken email-credits from a user
		 * so we need to give them back.
		 */
		IEM::sessionSet('SplitTestSend_Cleanup', $send_details);

		if ($send_size == 1) {
			$send_size_msg = GetLang('Addon_splittest_Send_Step3_Size_One');
		} else {
			$send_size_msg = sprintf(GetLang('Addon_splittest_Send_Step3_Size_Many'), $this->PrintNumber($send_size));
		}
		$this->template_system->Assign('SendingToNumberOfContacts', $send_size_msg);

		$this->template_system->Assign('CronEnabled', false);

		if (self::CheckCronEnabled()) {
			/**
			 * If cron is enabled, then record the stats allocation now.
			 * This will get fixed up when the actual send starts as it needs to create a stat item for each newsletter.
			 * However, at this stage we just need to record that the user is doing a send so it's removed from their send-allocation.
			 */
			$statsapi->RecordUserStats($user->userid, $jobcreated, $subscriber_count, $send_details['SendStartTime']);

			$user_adjusted_time = AdjustTime($send_details['SendStartTime'], false, GetLang('TimeFormat'), true);

			$this->template_system->Assign('CronEnabled', true);
			$this->template_system->Assign('JobScheduleTime', sprintf(GetLang('Addon_splittest_Send_Step3_JobScheduleTime'), $user_adjusted_time));

			/**
			 * Mark the job as "waiting".
			 * This will be used on the "manage" page to show it's waiting to send out.
			 */
			$send_api->StartJob($jobcreated, $send_details['splitid'], 'w');
		}
		$this->template_system->ParseTemplate('send_step3');
	}

	/**
	 * Show_Send_Step_4
	 * Step 4 handles two pieces of functionality:
	 * - if cron support is enabled, it "approves" the job for sending and then redirects the user to the main splittest page
	 *
	 * If cron is not enabled, it processes and sends the emails out in popup mode.
	 * It looks at the queues table for people to send to, and sends one email per window refresh.
	 * It prints out a report of what's going on:
	 * - how many have been sent
	 * - how many left
	 * - approx how long it has taken so far
	 * - approx how long to go
	 * - optional extra - pause after displaying that info and sending the email (based on user restrictions)
	 *
	 * @uses Jobs_API
	 * @uses Jobs_API::ApproveJob
	 * @uses Jobs_API::QueueSize
	 * @uses CheckCronEnabled
	 * @uses Splittest_Send_API::StartJob
	 */
	public function Show_Send_Step_4()
	{
		$send_details = IEM::sessionGet('SplitTestSendDetails');

		if (!$send_details || !isset($send_details['splitid']) || (int)$send_details['splitid'] <= 0) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$jobid = $send_details['Job'];

		require_once SENDSTUDIO_API_DIRECTORY . '/jobs.php';
		$jobApi = new Jobs_API;

		if (isset($_GET['Start']) || self::CheckCronEnabled()) {
			/**
			 * Remove the "cleanup" variables so we don't kill the send off when we either
			 * - successfully schedule a send
			 * - or start a send going.
			 */
			IEM::sessionRemove('SplitTestSend_Cleanup');

			$user = GetUser();
			$jobApi->ApproveJob($jobid, $user->Get('userid'), $user->Get('userid'));
		}

		/**
		 * If we get here and cron is enabled, we're finishing off a scheduled send setup.
		 * Show a message and return the user to the manage screen.
		 */
		if (self::CheckCronEnabled()) {
			FlashMessage(GetLang('Addon_splittest_Send_JobScheduled'), SS_FLASH_MSG_SUCCESS, $this->admin_url);
			return;
		}

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);

		$send_api = $this->GetApi('Splittest_Send');

		if (isset($_GET['Start'])) {
			$send_api->StartJob($jobid, $send_details['splitid']);
		}

		$sendqueue = $jobApi->GetJobQueue($jobid);

		$job = $jobApi->LoadJob($jobid);

		$send_api->Set('statids', $send_details['Stats']);

		$send_api->Set('jobdetails', $job['jobdetails']);
		$send_api->Set('jobowner', $job['ownerid']);

		$queuesize = $jobApi->QueueSize($sendqueue, 'splittest');

		$send_details['SendQueue'] = $sendqueue;

		$timenow = $send_api->GetServerTime();

		$timediff = ($timenow - $send_details['SendStartTime']);

		$time_so_far = $this->TimeDifference($timediff);

		$num_left_to_send = $send_details['SendSize'] - $queuesize;

		if ($num_left_to_send > 0) {
			$timeunits = $timediff / ($num_left_to_send);
			$timediff = ($timeunits * $queuesize);
		} else {
			$timediff = 0;
		}
		$timewaiting = $this->TimeDifference($timediff);

		$this->template_system->Assign('SendTimeSoFar', sprintf(GetLang('Addon_splittest_Send_Step4_TimeSoFar'), $time_so_far));
		$this->template_system->Assign('SendTimeLeft', sprintf(GetLang('Addon_splittest_Send_Step4_TimeLeft'), $timewaiting));

		if ($num_left_to_send == 1) {
			$this->template_system->Assign('Send_NumberAlreadySent', GetLang('Addon_splittest_Send_Step4_NumberSent_One'));
		} else {
			$this->template_system->Assign('Send_NumberAlreadySent', sprintf(GetLang('Addon_splittest_Send_Step4_NumberSent_Many'), $this->PrintNumber($num_left_to_send)));
		}

		if ($queuesize <= 0) {
			require_once SENDSTUDIO_API_DIRECTORY . '/ss_email.php';
			$email = new SS_Email_API;

			if (SENDSTUDIO_SAFE_MODE) {
				$email->Set('imagedir', TEMP_DIRECTORY . '/send');
			} else {
				$email->Set('imagedir', TEMP_DIRECTORY . '/send.' . $jobid . '.' . $sendqueue);
			}
			$email->CleanupImages();

			$send_details['SendEndTime'] = $send_api->GetServerTime();
			IEM::sessionSet('SplitTestSendDetails', $send_details);

			$this->template_system->Assign('Send_NumberLeft', GetLang('Addon_splittest_Send_Step4_SendFinished'));
			$this->template_system->ParseTemplate('send_step4');
			?>
				<script>
					window.opener.focus();
					window.opener.document.location = '<?php echo $this->admin_url . '&Action=Send&Step=5'; ?>';
					window.close();
				</script>
			<?php
			return;
		}

		if ($queuesize == 1) {
			$this->template_system->Assign('Send_NumberLeft', GetLang('Addon_splittest_Send_Step4_NumberLeft_One'));
		} else {
			$this->template_system->Assign('Send_NumberLeft', sprintf(GetLang('Addon_splittest_Send_Step4_NumberLeft_Many'), $this->PrintNumber($queuesize)));
		}

		$send_api->SetupJob($jobid, $sendqueue);
		$send_api->SetupNewsletter();

		$recipients = $send_api->FetchFromQueue($sendqueue, 'splittest', 1, 1);

		$send_api->SetupDynamicContentFields($recipients);
		$send_api->SetupCustomFields($recipients);

		$sent_ok = false;

		foreach ($recipients as $p => $recipientid) {
			$send_results = $send_api->SendToRecipient($recipientid, $sendqueue);

			// save the info in the session, then see if we need to pause between each email.
			if ($send_results['success'] > 0) {
				$sent_ok = true;
				$send_details['EmailResults']['success']++;
			} else {
				$send_details['EmailResults']['failure']++;
			}
			$send_details['EmailResults']['total']++;
			IEM::sessionSet('SplitTestSendDetails', $send_details);
		}
		session_write_close();

		$this->template_system->ParseTemplate('send_step4');

		// we should only need to pause if we successfully sent.
		if ($sent_ok) {
			$send_api->Pause();
		}
	}

	/**
	 * Show_Send_Step_5
	 * Step 5 handles the final step of doing a send.
	 * It marks the stats as finished,
	 * it cleans up anything left over in the send queue
	 * Then it calls PrintSendFailureReport to show a report of how many emails were sent and how long it took.
	 *
	 * @uses Stats_API::MarkNewsletterFinished
	 * @uses PrintSendFailureReport
	 * @uses GetApi
	 * @uses Splittest_Send_API::FinishJob
	 * @uses Jobs_API::ClearQueue
	 */
	public function Show_Send_Step_5()
	{
		$send_details = IEM::sessionGet('SplitTestSendDetails');

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);

		$jobid = $send_details['Job'];

		require_once SENDSTUDIO_API_DIRECTORY . '/stats.php';
		$statsapi = new Stats_API;

		/**
		 * Pass all of the stats through to the stats api.
		 *
		 * Since the stats contains an array of:
		 * newsletterid => statid
		 *
		 * we just need to pass through the statid's.
		 */
		$statsapi->MarkNewsletterFinished(array_values($send_details['Stats']), $send_details['SendSize']);

		$timetaken = $send_details['SendEndTime'] - $send_details['SendStartTime'];
		$timedifference = $this->TimeDifference($timetaken);

		$this->template_system->Assign('SendReport_Intro', sprintf(GetLang('Addon_splittest_Send_Step5_Intro'), $timedifference));

		$sendreport = '';
		if ($send_details['EmailResults']['success'] > 0) {
			if ($send_details['EmailResults']['success'] == 1) {
				FlashMessage(GetLang('Addon_splittest_Send_Step5_SendReport_Success_One'), SS_FLASH_MSG_SUCCESS);
			} else {
				FlashMessage(sprintf(GetLang('Addon_splittest_Send_Step5_SendReport_Success_Many'), $this->PrintNumber($send_details['EmailResults']['success'])), SS_FLASH_MSG_SUCCESS);
			}
			$sendreport = GetFlashMessages();
		}

		$this->PrintSendFailureReport($jobid, $sendreport);

		require_once SENDSTUDIO_API_DIRECTORY . '/jobs.php';
		$jobs_api = new Jobs_API;

		$send_api = $this->GetApi('Splittest_Send');
		$send_api->FinishJob($jobid, $send_details['splitid']);

		$jobs_api->ClearQueue($send_details['SendQueue'], 'splittest');

	}

	/**
	 * PrintSendFailureReport
	 * Prints out a report of
	 * - how many emails were sent,
	 * - how many emails were not sent (and why)
	 *
	 * Once that is worked out, it sets a template variable based on the report it creates.
	 *
	 * @param Int $job The job to load from the database with all of the job details in it
	 * @param String $sendreport The current "report" from the send (which would include how long the email took)
	 *
	 * @uses Jobs_API::LoadJob
	 * @uses Jobs_API::UnsentQueueSize
	 * @uses Jobs_API::Get_Unsent_Reasons
	 * @uses language/send.php
	 */
	private function PrintSendFailureReport($job=0, $sendreport='')
	{
		$send_details = IEM::sessionGet('SplitTestSendDetails');

		/**
		 * Include the 'send' language file
		 * as it contains all of the "failure report" error codes & messages.
		 */
		require_once SENDSTUDIO_LANGUAGE_DIRECTORY . '/default/send.php';

		require_once SENDSTUDIO_API_DIRECTORY . '/jobs.php';
		$jobApi = new Jobs_API;

		$jobinfo = $jobApi->LoadJob($job);

		$send_details = $jobinfo['jobdetails'];

		IEM::sessionSet('ReportQueue', $jobinfo['queueid']);

		$sendqueue = $jobinfo['queueid'];
		$failure_count = $jobApi->UnsentQueueSize($sendqueue);

		if ($failure_count > 0) {
			$error_report = '';
			$reason_codes = $jobApi->Get_Unsent_Reasons($jobinfo['queueid']);
			foreach ($reason_codes as $error_reason) {
				$this->template_system->Assign('ReportLink', sprintf(GetLang('SendReport_Failure_Link'), $error_reason['reasoncode']));
				$reason_message = GetLang('SendReport_Failure_Reason_' . $error_reason['reasoncode']);
				if ($error_reason['count'] == 1) {
					$this->template_system->Assign('ReasonMessage', sprintf(GetLang('SendReport_Failure_Reason_One'), $reason_message));
				} else {
					$this->template_system->Assign('ReasonMessage', sprintf(GetLang('SendReport_Failure_Reason_Many'), $this->PrintNumber($error_reason['count']), $reason_message));
				}
				$error_report .= $this->template_system->ParseTemplate('sendreport_failure_reason', true);
			}

			$error = '';

			if ($failure_count == 1) {
				$error = GetLang('SendReport_Failure_One');
			} else {
				$error = sprintf(GetLang('SendReport_Failure_Many'), $this->PrintNumber($failure_count));
			}
			$error .= '<br/><ul>' . $error_report . '</ul>';
			FlashMessage($error, SS_FLASH_MSG_ERROR);

			$sendreport .= GetFlashMessages();
		}

		$this->template_system->Assign('SendReport_Details', $sendreport, false);
		$this->template_system->ParseTemplate('sendreport');
	}


	/**
	 * Show_Send_Step_10
	 * This is the page that gets shown when a user clicks "pause" in the split test send popup window.
	 *
	 * It marks the job as "paused" in the database
	 * then shows an appropriate message.
	 *
	 * @uses GetApi
	 * @uses Splittest_Send_API::PauseJob
	 */
	public function Show_Send_Step_10()
	{
		$send_details = IEM::sessionGet('SplitTestSendDetails');
		if (!$send_details || !isset($send_details['splitid']) || (int)$send_details['splitid'] <= 0) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$job = (int)$send_details['Job'];

		/**
		 * Pause it in the split test.
		 * This makes it easier to work out a send's "state" (paused, in progress etc).
		 */
		$send_api = $this->GetApi('Splittest_Send');
		$paused = $send_api->PauseJob($job, $send_details['splitid']);

		if ($paused) {
			FlashMessage(GetLang('Addon_splittest_Send_Paused_Success'), SS_FLASH_MSG_SUCCESS);
		} else {
			FlashMessage(GetLang('Addon_splittest_Send_Paused_Failure'), SS_FLASH_MSG_ERROR);
		}

		$flash_messages = GetFlashMessages();

		$this->template_system->Assign('FlashMessages', $flash_messages, false);
		$this->template_system->Assign('AdminUrl', $this->admin_url);
		$this->template_system->ParseTemplate('send_paused');
	}

	/**
	 * Show_Send_Step_20
	 * Step 20 is what is shown if a user closes the popup sending window and
	 * has to 'Pause' the send before they can resume it.
	 *
	 * Basically it:
	 * - calls "PauseJob" in the splittest send api,
	 * - sets a flash message,
	 * - redirects the user back to the "Manage" page.
	 *
	 * If no id is supplied (or it's invalid), then an error is saved
	 * and the user is taken back to the manage page.
	 *
	 * @uses GetApi
	 * @uses SplitTest_API::Load
	 * @uses SplitTest_Send_API::PauseJob
	 *
	 * @return Void Returns nothing. Pauses the job, saves an appropriate flash message and takes the user back to the 'Manage' page.
	 */
	public function Show_Send_Step_20()
	{
		$splitid = 0;
		if (isset($_GET['id'])) {
			$splitid = (int)$_GET['id'];
		}
		if ($splitid <= 0) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$split_api = $this->GetApi();
		$split_details = $split_api->Load($splitid);
		if (empty($split_details)) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$send_api = $this->GetApi('SplitTest_Send');

		$paused = $send_api->PauseJob($split_details['jobid'], $split_details['splitid']);

		if ($paused) {
			FlashMessage(GetLang('Addon_splittest_Send_Paused_Success'), SS_FLASH_MSG_SUCCESS, $this->admin_url);
		} else {
			FlashMessage(GetLang('Addon_splittest_Send_Paused_Failure'), SS_FLASH_MSG_ERROR, $this->admin_url);
		}
	}

	/**
	 * Show_Send_Step_30
	 * This shows a summary report of the split test campaign
	 * after a user has paused the campaign
	 * and they want to resume sending it
	 *
	 * It shows:
	 * - which lists/segments it will be sent to
	 * - the split test name
	 * - which campaigns it will send
	 *
	 * and a "resume" button.
	 *
	 * If cron is enabled, then it will mark the job as "waiting" to send again in the database,
	 * set a flash message and redirect the user back to the "manage split tests" page.
	 *
	 * @uses GetApi
	 * @uses Splittest_API::Load
	 * @uses Jobs_API::LoadJob
	 * @uses CheckCronEnabled
	 * @uses Splittest_Send_API::ResumeJob
	 */
	public function Show_Send_Step_30()
	{
		$splitid = 0;
		if (isset($_GET['id'])) {
			$splitid = (int)$_GET['id'];
		}

		$api = $this->GetApi();
		$split_campaign_details = $api->Load($splitid);
		if (empty($split_campaign_details)) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		$jobid = 0;
		if (isset($split_campaign_details['jobid'])) {
			$jobid = (int)$split_campaign_details['jobid'];
		}

		require_once SENDSTUDIO_API_DIRECTORY . '/jobs.php';
		$jobApi = new Jobs_API;
		$job = $jobApi->LoadJob($jobid);

		if (empty($job)) {
			FlashMessage(GetLang('Addon_splittest_Send_InvalidSplitTest'), SS_FLASH_MSG_ERROR, $this->admin_url);
			return;
		}

		/**
		 * If we're sending via cron,
		 * then mark the job as "waiting" to send again
		 * and then show an appropriate message.
		 */
		if (self::CheckCronEnabled()) {
			$send_api = $this->GetApi('SplitTest_Send');
			$resumed = $send_api->ResumeJob($jobid, $splitid);
			if ($resumed) {
				FlashMessage(GetLang('Addon_splittest_Send_Resumed_Success'), SS_FLASH_MSG_SUCCESS, $this->admin_url);
			} else {
				FlashMessage(GetLang('Addon_splittest_Send_Resumed_Failure'), SS_FLASH_MSG_ERROR, $this->admin_url);
			}
			return;
		}

		$sendingCampaigns = array();
		$send_details['newsletters'] = array();
		foreach ($split_campaign_details['splittest_campaigns'] as $campaignid => $campaignname) {
			$sendingCampaigns[$campaignid] = htmlspecialchars($campaignname, ENT_QUOTES, SENDSTUDIO_CHARSET);
			$send_details['newsletters'][] = $campaignid;
		}

		$send_list = array();

		switch ($job['jobdetails']['sendingto']['sendtype']) {
			case 'list':
				require_once SENDSTUDIO_API_DIRECTORY . '/lists.php';
				$list_api = new Lists_API;
				foreach ($job['jobdetails']['sendingto']['sendids'] as $listid) {
					$list_api->Load($listid);
					$send_list[] = htmlspecialchars($list_api->Get('name'), ENT_QUOTES, SENDSTUDIO_CHARSET);
				}

				$this->template_system->Assign('SendingToLists', true);
			break;

			case 'segment':
				require_once SENDSTUDIO_API_DIRECTORY . '/segment.php';
				$segment_api = new Segment_API;
				foreach ($job['jobdetails']['sendingto']['sendids'] as $segmentid) {
					$segment_api->Load($segmentid);
					$send_list[] = htmlspecialchars($segment_api->Get('segmentname'), ENT_QUOTES, SENDSTUDIO_CHARSET);
				}

				$this->template_system->Assign('SendingToSegments', true);
			break;
		}

		/**
		 * Set everything in the session ready to go.
		 */
		$job['jobdetails']['Job'] = $job['jobid'];
		IEM::sessionSet('SplitTestSendDetails', $job['jobdetails']);

		/**
		 * Work out how many more emails there are to send.
		 */
		$send_size = $job['jobdetails']['sendinfo']['sendsize_left'];

		if ($send_size == 1) {
			$send_size_msg = GetLang('Addon_splittest_Send_Step3_Size_One');
		} else {
			$send_size_msg = sprintf(GetLang('Addon_splittest_Send_Step3_Size_Many'), $this->PrintNumber($send_size));
		}
		$this->template_system->Assign('SendingToNumberOfContacts', $send_size_msg);

		$this->template_system->Assign('sendingCampaigns', $sendingCampaigns);
		$this->template_system->Assign('sendLists', $send_list);

		$this->template_system->Assign('AdminUrl', $this->admin_url, false);
		$this->template_system->ParseTemplate('send_step3');
	}

	/**
	 * CheckCronEnabled
	 * Checks whether cron support is enabled in the app
	 * and also if split test cron support is not disabled.
	 *
	 * If cron is not enabled (or split test cron sending is disabled),
	 * it will return false.
	 *
	 * If cron is enabled and split test cron sending is enabled (for any timeframe),
	 * it will return true.
	 *
	 * @uses SENDSTUDIO_CRON_ENABLED
	 * @uses SENDSTUDIO_CRON_SPLITTEST
	 *
	 * @return Boolean Returns false if cron is not enabled or if split test cron support is disabled. If both are enabled, returns true.
	 */
	static public function CheckCronEnabled()
	{
		if (!defined('SENDSTUDIO_CRON_SPLITTEST')) {
			return false;
		}

		if (SENDSTUDIO_CRON_ENABLED && SENDSTUDIO_CRON_SPLITTEST > 0) {
			return true;
		}
		return false;
	}
}
