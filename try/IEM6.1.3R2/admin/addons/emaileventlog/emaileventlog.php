<?php
/**
 * This file contains the 'emaileventlog' addon which logs and event for a contact when a campaign or autoresponder is sent to them.
 *
 * @package Interspire_Addons
 * @subpackage Addons_systemlog
 */

/**
 * Make sure the base Interspire_Addons class is defined.
 */
if (!class_exists('Interspire_Addons', false)) {
	require_once(dirname(dirname(__FILE__)) . '/interspire_addons.php');
}

/**
 * This class handles listing, deleting and processing system log entries.
 * It also puts itself in the tools menu for system-admins to use.
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 */
class Addons_emaileventlog extends Interspire_Addons
{
	/**
	 * Caches an instance of this object
	 * @var Addons_emaileventlog Instace of this object
	 */
	static protected $me = null;

	/**
	 * Set a default for the maximum number of log entries to keep.
	 * This is used when the addon is first installed so we have a default setting.
	 * Of course it can be changed through the admin control panel to be whatever you like.
	 *
	 * @var Int
	 */
	protected $default_settings = array();

	/**
	 * Install
	 * This is called when the addon is installed in the main application.
	 * In this case, it simply sets the default settings and then calls the parent install method to add itself to the database.
	 *
	 * @uses default_settings
	 * @uses Interspire_Addons::Install
	 * @uses Interspire_Addons_Exception
	 *
	 * @throws Throws an Interspire_Addons_Exception if something goes wrong with the install process.
	 * @return True Returns true if all goes ok with the install.
	 */
	public function Install()
	{
		$this->settings = $this->default_settings;
		$this->enabled = true;
		$this->configured = true;

		try {
			$status = parent::Install();
		} catch (Interspire_Addons_Exception $e) {
			throw new Exception("Unable to install addon $this->GetId();" . $e->getMessage());
		}
		return true;
	}

	/**
	 * GetEventListeners
	 * This returns an array of events that the addon listens to.
	 *
	 * This addon uses
	 * - 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS' event to put itself into the 'tools' menu at the top
	 *
	 * @see Interspire_Addons::GetEventListeners
	 *
	 * @return Array Returns an array of events, what methods to call and which file to call the method from.
	 */
	public function GetEventListeners()
	{
		return array (
			array (
				'eventname' => 'IEM_SENDAPI_SENDTORECIPIENT',
				'trigger_details' => array (
					'Addons_emaileventlog',
					'CampaignSent',
				),
				'trigger_file' => '{%IEM_ADDONS_PATH%}/emaileventlog/emaileventlog.php'
			),
			array (
				'eventname' => 'IEM_JOBSTRIGGEREMAILSAPI_PROCESSJOBSEND',
				'trigger_details' => array (
					'Addons_emaileventlog',
					'TriggerSent',
				),
				'trigger_file' => '{%IEM_ADDONS_PATH%}/emaileventlog/emaileventlog.php'
			),
			array (
				'eventname' => 'IEM_JOBSAUTORESPONDERAPI_ACTIONJOB',
				'trigger_details' => array (
					'Addons_emaileventlog',
					'AutoresponderSent',
				),
				'trigger_file' => '{%IEM_ADDONS_PATH%}/emaileventlog/emaileventlog.php'
			),
			array (
				'eventname' => 'IEM_STATSAPI_RECORDLINKCLICK',
				'trigger_details' => array (
					'Addons_emaileventlog',
					'LinkClicked',
				),
				'trigger_file' => '{%IEM_ADDONS_PATH%}/emaileventlog/emaileventlog.php'
			),
			array (
				'eventname' => 'IEM_STATSAPI_RECORDOPEN',
				'trigger_details' => array (
					'Addons_emaileventlog',
					'OpenTracked',
				),
				'trigger_file' => '{%IEM_ADDONS_PATH%}/emaileventlog/emaileventlog.php'
			)
		);
	}

	/**
	 * LoadSelf
	 * Creates an instance of Addons_emaileventlog
	 *
	 * @return Mixed Returns an object on success, false on failure
	 */
	public static function LoadSelf()
	{
		if (is_null(self::$me)) {
			try {
				self::$me = new self;
				self::$me->Load();
			} catch (Exception $e) {
				return false;
			}
		}

		if (!self::$me->enabled) {
			return false;
		}

		return self::$me;
	}

	/**
	 * CampaignSent
	 * Logs an event when a user is sent a campaign
	 *
	 * @uses Subscriber_API::AddEvent
	 *
	 * @return Void Returns nothing
	 */
	static public function CampaignSent($eventdata)
	{
		if (!$me = self::LoadSelf()) {
			return;
		}

		if ($eventdata->emailsent) {
			$newsletterapi = new Newsletters_API();
			$newsletter = $newsletterapi->Load($eventdata->jobdetails['Newsletter']);
			$subscribersapi = new Subscribers_API();
			$event = array(
				'type' => GetLang('Addon_emaileventlog_email'),
				'eventdate' => $subscribersapi->GetServerTime(),
				'subject' => sprintf(GetLang('Addon_emaileventlog_sent_campaign_subject'), htmlspecialchars($newsletterapi->Get('name'), ENT_QUOTES, SENDSTUDIO_CHARSET)),
				'notes' => GetLang('Addon_emaileventlog_sent_campaign')
			);
			$subscribersapi->AddEvent($eventdata->subscriberinfo['subscriberid'], $eventdata->subscriberinfo['listid'], $event);
		}
	}

	/**
	 * TriggerSent
	 * Logs an event when a user is sent a campaign VIA triggeremails
	 *
	 * @param EventData_IEM_JOBSTRIGGEREMAILSAPI_PROCESSJOBSEND $eventdata Event data
	 *
	 * @return Void Returns nothing
	 *
	 * @uses Subscribers_API::GetServerTime()
	 * @uses Subscribers_API::AddEvent()
	 */
	static public function TriggerSent(EventData_IEM_JOBSTRIGGEREMAILSAPI_PROCESSJOBSEND $eventdata)
	{
		if (!$me = self::LoadSelf()) {
			return;
		}

		if ($eventdata->emailsent) {
			$subscribersapi = new Subscribers_API();
			$event = array(
				'type' => GetLang('Addon_emaileventlog_email'),
				'eventdate' => $subscribersapi->GetServerTime(),
				'subject' => sprintf(GetLang('Addon_emaileventlog_sent_campaign_subject'), htmlspecialchars($eventdata->newsletter['name'], ENT_QUOTES, SENDSTUDIO_CHARSET)),
				'notes' => sprintf(GetLang('Addon_emaileventlog_sent_trigger'),htmlspecialchars($eventdata->triggerrecord['name'], ENT_QUOTES, SENDSTUDIO_CHARSET))
			);

			$subscribersapi->AddEvent($eventdata->subscriberid, $eventdata->listid, $event, $eventdata->triggerrecord['ownerid']);
		}
	}

	/**
	 * AutoresponderSent
	 * Logs an event when a user is sent an autoresponder
	 *
	 * @uses Subscriber_API::AddEvent
	 *
	 * @return Void Returns nothing
	 */
	static public function AutoresponderSent($eventdata)
	{
		if (!$me = self::LoadSelf()) {
			return;
		}

		if ($eventdata->emailsent) {
			$subscribersapi = new Subscribers_API();
			$event = array(
				'type' => GetLang('Addon_emaileventlog_autoresponder'),
				'eventdate' => $subscribersapi->GetServerTime(),
				'subject' => sprintf(GetLang('Addon_emaileventlog_sent_autoresponder_subject'), htmlspecialchars($eventdata->autoresponder->name, ENT_QUOTES, SENDSTUDIO_CHARSET)),
				'notes' => GetLang('Addon_emaileventlog_sent_autoresponder')
			);
			$subscribersapi->AddEvent($eventdata->subscriberinfo['subscriberid'], $eventdata->subscriberinfo['listid'], $event);
		}
	}

	/**
	 * LinkClicked
	 * Logs an event when a user clicks a link in a campaign or autoresponder
	 *
	 * @uses Subscriber_API::AddEvent
	 *
	 * @param EventData_IEM_STATSAPI_RECORDLINKCLICK $eventData Event data
	 * @return Void Returns nothing
	 */
	static public function LinkClicked(EventData_IEM_STATSAPI_RECORDLINKCLICK $eventdata)
	{
		if (!self::LoadSelf()) {
			return;
		}

		$ss = new SendStudio_Functions();
		$resourceName = '';

		if ($eventdata->statstype == 'a') {
			$api = $ss->GetApi('Autoresponders');
			$record = $api->GetRecordByStatID($eventdata->click_details['statid']);
			if ($record && isset($record['name'])) {
				$resourceName = $record['name'];
			}
		} else {
			$api = $ss->GetApi('Stats');
			$record = $api->GetNewsletterSummary($eventdata->click_details['statid'], true);
			if ($record && isset($record['newslettername'])) {
				$resourceName = $record['newslettername'];
			}
		}

		$subscribersapi = new Subscribers_API();
		$event = array(
			'type' => GetLang('Addon_emaileventlog_link'),
			'eventdate' => $subscribersapi->GetServerTime(),
			'subject' => sprintf(GetLang('Addon_emaileventlog_link_clicked_subject'), htmlspecialchars($resourceName, ENT_QUOTES, SENDSTUDIO_CHARSET)),
			'notes' => sprintf(GetLang('Addon_emaileventlog_link_clicked'), $eventdata->click_details['url'], $eventdata->click_details['url'])
		);
		$subscribersapi->AddEvent($eventdata->click_details['subscriberid'], $eventdata->click_details['listid'], $event);
	}

	/**
	 * OpenTracked
	 * Logs an event when a user opens a campaign or autoresponder
	 *
	 * @uses Subscriber_API::AddEvent
	 * @uses Stats_API::GetNewsletterSummary
	 * @uses Stats_API::GetAutoresponderSummary
	 *
	 * @return Void Returns nothing
	 */
	static public function OpenTracked($eventdata)
	{
		if (!self::LoadSelf()) {
			return;
		}

		$subscribersapi = new Subscribers_API();
		$statsapi = new Stats_API();

		if (!isset($eventdata->open_details['subscriberid']) || !isset($eventdata->open_details['listid'])) {
			return;
		}

		switch ($eventdata->statstype[0]) {
			case 'n':
				$newsletter = $statsapi->GetNewsletterSummary($eventdata->open_details['statid'], true);
				if (empty($newsletter) || !isset($newsletter['newsletterid'])) {
					return false;
				}
				$event = array(
					'type' => GetLang('Addon_emaileventlog_open'),
					'eventdate' => $subscribersapi->GetServerTime(),
					'subject' => sprintf(GetLang('Addon_emaileventlog_open_subject'), htmlspecialchars($newsletter['newslettername'], ENT_QUOTES, SENDSTUDIO_CHARSET)),
					'notes' => GetLang('Addon_emaileventlog_opened_campaign')
				);
			break;

			case 'a':
				$stats = $statsapi->FetchStats($eventdata->open_details['statid'], 'a');
				$autoresponder = $statsapi->GetAutoresponderSummary($stats['autoresponderid'], true);
				if (empty($autoresponder) || !isset($autoresponder['autoresponderid'])) {
					return false;
				}
				$event = array(
					'type' => GetLang('Addon_emaileventlog_open_autoresponder'),
					'eventdate' => $subscribersapi->GetServerTime(),
					'subject' => sprintf(GetLang('Addon_emaileventlog_open_autoresponder_subject'), htmlspecialchars($autoresponder['autorespondername'], ENT_QUOTES, SENDSTUDIO_CHARSET)),
					'notes' => GetLang('Addon_emaileventlog_opened_autoresponder')
				);
			break;
			default:
		}

		$subscribersapi->AddEvent($eventdata->open_details['subscriberid'], $eventdata->open_details['listid'], $event);
	}
}
