<?php
/**
 * TriggerEmails API.
 *
 * @author Hendri <hendri@interspire.com>
 *
 * @package API
 * @subpackage TriggerEmails_API
 */

/**
* Load up the base API class if we need to.
*/
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'api.php');

/**
 * This class provide an abstraction layer for interacting to the database.
 *
 * To create a trigger email record, you need to populate API's non-read-only properties.
 * The read-only properties cannot be changed from outside of this API.
 *
 * @package API
 * @subpackage TriggerEmails_API
 *
 * @property-read Integer $triggeremailsid Trigger emails ID
 * @property String $active Flag whether or not this trigger email is active or not (0 = inactive, 1 = active)
 * @property-read $createdate Timestamp of when the trigger was created
 * @property-read $ownerid Owner ID of the trigger
 * @property String $name Trigger email name
 *
 * @property String $triggertype Trigger type ('f' => Based on date custom field, 'l' => Based on link being clicked, 'n' => Based on newsletter get opened)
 *
 * @property Integer $triggerhours Number of hours after/before the event -- before is denoted with negative numbers, and only available on trigger type f
 * @property Integer $triggerinterval Number of times the trigger can run
 *
 * @property-read Integer $queueid Queue ID that is going to be used by this trigger
 * @property-read Integer $statid Statistic ID of the trigger
 *
 * @property-write Array data Trigger email data
 * @property-write Array triggeractions Trigger email actions
 */
class TriggerEmails_API extends API
{
	/**
	 * Class properties (record that will be saved to database)
	 * @var Array Hold trigger emails record, contains default value as a default
	 */
	private $_properties = array(
		'triggeremailsid'			=> 0,
		'active'					=> '0',
		'createdate'				=> 0,
		'ownerid'					=> 0,
		'name'						=> 0,

		// f = from custom field, n = newsletter open, l = links clicked
		// This option is mutually exclusive
		'triggertype'				=> '',

		'triggerhours'				=> 0,
		'triggerinterval'			=> 0,

		'queueid'					=> 0,
		'statid'					=> 0
	);

	/**
	 * Hold "action" data for saving into the database
	 * @var Array Holds trigger actions
	 */
	private $_actions = null;

	/**
	 * Holds "data" array to be saved to the database
	 * @var Array Holds trigger data
	 */
	private $_data = null;

	/**
	 * Read only class properties
	 * @var Array Hold read-only property name
	 */
	private $_propertiesReadOnly = array('triggeremailsid', 'queueid', 'statid', 'createdate');

	/**
	 * Write only class properties
	 * @var Array Hold write-only property name
	 */
	private $_propertiesWriteOnly = array();

	/**
	 * Specify the default sort column
	 * @var String Default sort column
	 */
	private $_fieldDefaultSort = 'name';

	/**
	 * Specify which columns can be sorted
	 * @var Array An array of column names that can be sorted
	 */
	private $_fieldSortable = array('name', 'createdate', 'active', 'triggertype', 'triggerhours');

	/**
	 * Specify valid trigger types
	 * @var Array An array of available trigger types
	 */
	private $_validTriggerTypes = array('f', 'l', 'n');




	// --------------------------------------------------------------------------------
	// Event handler
	// --------------------------------------------------------------------------------
		/**
		 * eventEmailOpen
		 * This is a listener for "IEM_STATSAPI_RECORDOPEN" event.
		 * It allows trigger email to capture "Newsletter Opened" and "Link Clicked"
		 *
		 * @param EventData_IEM_STATSAPI_RECORDOPEN $eventData Event data object
		 * @return Void Returns nothing
		 *
		 * @uses EventData_IEM_STATSAPI_RECORDOPEN
		 */
		static public function eventEmailOpen(EventData_IEM_STATSAPI_RECORDOPEN $eventData)
		{
			// Only intrested if it is a newsletter
			if ($eventData->statstype != 'newsletter') {
				return;
			}

			// If this open has been recorded previously, ignore it
			if ($eventData->have_been_recorded) {
				return;
			}

			$api = new TriggerEmails_API();
			$statid = intval($eventData->open_details['statid']);
			$triggerrecords = $api->GetRecordByAssociatedNewsletterStatisticID($statid, 'n');

			// If no trigger records are found, return from function
			if (!$triggerrecords || !is_array($triggerrecords) || !isset($triggerrecords['triggeremails']) || empty($triggerrecords['triggeremails'])) {
				return;
			}

			$recipients = $api->_getSubscriberIDSFromList($eventData->open_details['subscriberid'], array_keys($triggerrecords['lists']));

			foreach ($triggerrecords['triggeremails'] as $trigger) {
				// If recipients have been added to the send queue or have been sent an email for this particular trigger, do not re-add the subscriber again
				if (!$api->CanAddToQueue($trigger['triggeremailsid'], $eventData->open_details['subscriberid'], $trigger['queueid'])) {
					continue;
				}

				$schedule = time() + ($trigger['triggerhours'] * 3600);

				IEM::userLogin($trigger['ownerid'], false);
				$api->AddToQueue($trigger['queueid'], 'triggeremail', $recipients, $schedule);
				IEM::userLogout();
			}

			return;
		}

		/**
		 * eventLinkClicked
		 * This is a listener for "IEM_STATSAPI_RECORDLINKCLICK" event.
		 * It allows trigger email to capture "Newsletter Opened" and "Link Clicked"
		 *
		 * @param EventData_IEM_STATSAPI_RECORDLINKCLICK $eventData Event data object
		 * @return Void Returns nothing
		 *
		 * @uses EventData_IEM_STATSAPI_RECORDLINKCLICK
		 */
		static public function eventLinkClicked(EventData_IEM_STATSAPI_RECORDLINKCLICK $eventData)
		{
			// Only intrested if it is a newsletter
			if ($eventData->statstype != 'newsletter') {
				return;
			}

			// If this click has been recorded previously, ignore it
			if ($eventData->have_been_recorded) {
				return;
			}

			$api = new TriggerEmails_API();
			$linkid = intval($eventData->click_details['linkid']);
			$statid = intval($eventData->click_details['statid']);
			$triggerrecords = $api->GetRecordByAssociatedLinkIDStatID($linkid, $statid);

			// If no trigger records are found, return from function
			if (!$triggerrecords || !is_array($triggerrecords) || !isset($triggerrecords['triggeremails']) || empty($triggerrecords['triggeremails'])) {
				return;
			}

			$recipients = $api->_getSubscriberIDSFromList($eventData->click_details['subscriberid'], array_keys($triggerrecords['lists']));

			foreach ($triggerrecords['triggeremails'] as $trigger) {
				// If receipients has been added to the send queue or have been sent an email for this particular trigger, do not re-add the subscriber again
				if (!$api->CanAddToQueue($trigger['triggeremailsid'], $eventData->click_details['subscriberid'], $trigger['queueid'])) {
					continue;
				}

				$schedule = time() + ($trigger['triggerhours'] * 3600);

				IEM::userLogin($trigger['ownerid'], false);
				$api->AddToQueue($trigger['queueid'], 'triggeremail', $recipients, $schedule);
				IEM::userLogout();
			}

			return;
		}
	// --------------------------------------------------------------------------------




	// --------------------------------------------------------------------------------
	// Properties related
	// --------------------------------------------------------------------------------
		/**
		 * __set
		 * Magic function for property setter
		 * @param String $propertyName Property name
		 * @param Mixed $propertyValue Property value
		 * @return Void Returns nothing
		 */
		public function __set($propertyName, $propertyValue)
		{
			switch ($propertyName) {
				case 'triggeractions':
					if (!is_array($propertyValue)) {
						throw new Exception('You cannot set this value as triggeractions');
					}

					$this->_actions = $propertyValue;
				break;

				case 'data':
					if (!is_array($propertyValue)) {
						throw new Exception('You cannot set this value as trigger data');
					}

					$this->_data = $propertyValue;
				break;

				default:
					// See if property is available
					if (!in_array($propertyName, array_keys($this->_properties))) {
						trigger_error("The property: {$propertyName} does not exists", E_USER_ERROR);
					}

					// See if this is a read only property
					if (in_array($propertyName, $this->_propertiesReadOnly)) {
						trigger_error("The property: {$propertyName} is set as read-only", E_USER_ERROR);
					}

					// See if setter is available
					if (is_callable(array($this, "_property_set_{$propertyName}"))) {
						$propertyValue = $this->{"_property_set_{$propertyName}"}($propertyValue);
					}

					$this->_properties[$propertyName] = $propertyValue;
				break;
			}
		}

		/**
		 * __get
		 * Magic function for property getter
		 * @param String $propertyName Property name
		 * @return Mixed Returns property value
		 */
		public function __get($propertyName)
		{
			// See if property is available
			if (!in_array($propertyName, array_keys($this->_properties))) {
				trigger_error("The property: {$propertyName} does not exists", E_USER_ERROR);
			}

			// See if this is a write only property
			if (in_array($propertyName, $this->_propertiesWriteOnly)) {
				trigger_error("The property: {$propertyName} is set as write-only", E_USER_ERROR);
			}

			$propertyValue = $this->_properties[$propertyName];

			// See if getter is available
			if (is_callable(array($this, "_property_get_{$propertyName}"))) {
				$propertyValue = $this->{"_property_get_{$propertyName}"}($propertyValue);
			}

			return $propertyValue;
		}
	// --------------------------------------------------------------------------------




	/**
	 * CONSTURCTOR
	 * @return TriggerEmails_API Returns this object
	 */
	public function __construct()
	{
		$this->GetDb();
	}





	/**
	 * Load
	 * Load Trigger Emails record from the database
	 * @param Integer $id Trigger Emails ID
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function Load($id)
	{
		$record = $this->GetRecordByID($id);

		if (!$record || empty($record)) {
			return false;
		}

		foreach ($record as $key => $value) {
			if (array_key_exists($key, $this->_properties)) {
				$this->_properties[$key] = $value;
			}
		}

		$this->_actions = null;

		return true;
	}

	/**
	 * Save
	 *
	 * Save loaded/added trigger email record to the database.
	 * If triggeremailsid is specified, it will update the existing record with the newer data,
	 * otherwise, it will insert the record into the database.
	 *
	 * If "record" parameter is specified, it will use the record instead of
	 * the record that has been loaded to the API
	 *
	 * @param Array $record Record to be saved (Associative array) (Optional)
	 * @return Mixed Returns triggeremailsid if successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::$_properties
	 * @uese Db::Quote()
	 * @uses Db::StartTransaction()
	 * @uses API::CreateQueue()
	 * @uses Stats_API
	 * @uses Stats_API::SaveNewsletterStats()
	 * @uses Db::RollbackTransaction()
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::GetError()
	 * @uses Db::LastId()
	 * @uses Db::CommitTransaction()
	 *
	 * @todo Test save using parameter
	 */
	public function Save($record = array())
	{
		// --------------------------------------------------------------------------------------------
		// If record is specified from the parameter, that means the function need to save
		// the record that got passed in instead of the record that may/may not be "loaded" in the class
		// properties.
		//
		// This is predominantly will be used by the XML API
		// --------------------------------------------------------------------------------------------
			if (!empty($record)) {
				$api = new TriggerEmails_API();

				if (array_key_exists('triggeremailsid', $record) && !empty($record['triggeremailsid'])) {
					if (!$api->Load($record['triggeremailsid'])) {
						return false;
					}
				}

				foreach ($record as $key => $value) {
					if (in_array($key, $this->_propertiesReadOnly)) {
						continue;
					}

					$api->{$key} = $value;
				}

				return $api->Save();
			}
		// --------------------------------------------------------------------------------------------

		// Make necessary modifications of the record
		switch ($this->triggertype) {
			case 'f': break;
			case 's': break;

			case 'l':
				// Only send once for "Link Click"
				$this->triggerinterval = 0;
			break;

			case 'n':
				// Only send once for "Email open"
				$this->triggerinterval = 0;
			break;
		}

		$record = $this->_properties;
		foreach ($record as $key => $value) {
			if (is_null($value)) {
				$record[$key] = 'NULL';
			} elseif (!is_numeric($value)) {
				$record[$key] = "'" . $this->Db->Quote(strval($value)) . "'";
			}
		}

		$status = $this->_validateRecord($this->_properties, $this->_data, $this->_actions);
		if (!$status) {
			trigger_error('Invalid record', E_USER_NOTICE);
			return false;
		}

		// ----- INSERT
			if ($this->triggeremailsid == 0) {
				$this->Db->StartTransaction();

				// Unset this, as we don't want the ID to be inserted too
				unset($record['triggeremailsid']);

				// Populate read-only field
				$record['createdate'] = time();
				$record['queueid'] = $this->CreateQueue('triggeremail');

				/**
				 * Create statistic for trigger, and populate statid
				 */
					require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'stats.php');
					$statsapi = new Stats_API();

					$tempDetails = array(
						'Job' => 0,
						'Queue' => $record['queueid'],
						'TrackOpens' => 0,
						'TrackLinks' => 0,
						'Newsletter' => 0,
						'SendFromName' => '',
						'SendFromEmail' => '',
						'BounceEmail' => '',
						'ReplyToEmail' => '',
						'Charset' => '',
						'SendCriteria' => '',
						'SendSize' => 0,
						'SentBy' => 0,
						'NotifyOwner' => 0,
						'SendType' => 'triggeremail',
						'Lists' => array(0),
					);

					$statid = $statsapi->SaveNewsletterStats($tempDetails);
					if (!$statid) {
						$this->Db->RollbackTransaction();
						trigger_error('Cannot get statistic ID', E_USER_NOTICE);
						return false;
					}

					$record['statid'] = $statid;
				/**
				 * -----
				 */

				// Add to triggeremails table
				$query = 'INSERT INTO [|PREFIX|]triggeremails(' . implode(',', array_keys($record)) . ') VALUES (' . implode(',', $record) . ')';
				if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
					$query .= ' RETURNING triggeremailsid';
				}

				$status = $this->Db->Query($query);
				if ($status === false) {
					list($msg, $errno) = $this->Db->GetError();
					$this->Db->RollbackTransaction();
					trigger_error($msg, $errno);
					return false;
				}

				// Get new ID
				if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
					$temp = $this->Db->Fetch($status);
					$this->Db->FreeResult($status);

					$record['triggeremailsid'] = $temp['triggeremailsid'];
				} else {
					$record['triggeremailsid'] = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'triggeremails_triggeremailsid_seq');
				}

				// Add data and it's data if available
				if (is_array($this->_actions)) {
					if (!$this->_updateData($record['triggeremailsid'], $this->_data)) {
						$this->Db->RollbackTransaction();
						trigger_error('Unable to save data', E_USER_NOTICE);
						return false;
					}
				}

				// Add actions and it's data if available
				if (is_array($this->_actions)) {
					if (!$this->_updateActions($record['triggeremailsid'], $this->_actions)) {
						$this->Db->RollbackTransaction();
						trigger_error('Unable to save action data', E_USER_NOTICE);
						return false;
					}
				}

				$this->Db->CommitTransaction();

				// Populate the properties with new data
				$this->_properties['statid'] = $record['statid'];
				$this->_properties['queueid'] = $record['queueid'];
				$this->_properties['triggeremailsid'] = $record['triggeremailsid'];
				$this->_properties['createdate'] = $record['createdate'];

				return $record['triggeremailsid'];
			}
		// -----


		// ----- UPDATE
			$this->Db->StartTransaction();

			$id = $record['triggeremailsid'];
			unset($record['triggeremailsid']);

			$temp = array();
			foreach ($record as $key => $value) {
				array_push($temp, "{$key} = {$value}");
			}

			$query = 'UPDATE [|PREFIX|]triggeremails SET ' . implode(',', $temp) . ' WHERE triggeremailsid = ' . $id;
			$status = $this->Db->Query($query);
			if ($status === false) {
				list($msg, $errno) = $this->Db->GetError();
				$this->Db->RollbackTransaction();
				trigger_error($msg, $errno);
				return false;
			}

			// Add data and it's ata if available
			if (is_array($this->_actions)) {
				if (!$this->_updateData($id, $this->_data)) {
					$this->Db->RollbackTransaction();
					trigger_error('Unable to save data', E_USER_NOTICE);
					return false;
				}
			}

			// Add actions and it's data if available
			if (is_array($this->_actions)) {
				if (!$this->_updateActions($id, $this->_actions)) {
					$this->Db->RollbackTransaction();
					trigger_error('Unable to save action data', E_USER_NOTICE);
					return false;
				}
			}

			$this->Db->CommitTransaction();
			return $id;
		// -----
	}

	/**
	 * Create
	 *
	 * Create a new record. This is an alias of Save().
	 * It also provides a way for XML API to create trigger record.
	 *
	 * @param Array $record Record to be created (Associated values)
	 * @return Mixed Returns newly created trigger email ID if successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::Save()
	 */
	public function Create($record = array())
	{
		return $this->Save($record);
	}

	/**
	 * Copy
	 *
	 * Create a copy of an existing trigger record record
	 *
	 * @param Integer $id Existing record to be copied over
	 * @return Mixed Returns the newly created trigger email ID if successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::Load()
	 * @uses TriggerEmails_API::Save()
	 * @uses GetLang()
	 */
	public function Copy($id = null)
	{
		if (!is_null($id)) {
			$api = new TriggerEmails_API();
			if (!$api->Load($id)) {
				return false;
			}

			return $api->Copy();
		} else {
			if ($this->triggeremailsid == 0) {
				return false;
			}

			$oldid = $this->triggeremailsid;

			$temp = $this->_properties;
			foreach ($this->_propertiesReadOnly as $properties) {
				$this->_properties[$properties] = 0;
			}

			$data = $this->GetData($oldid);
			if (array_key_exists($oldid, $data)) {
				$data = $data[$oldid];
			} else {
				$data = array();
			}

			$actions = $this->GetActions($oldid);
			if (array_key_exists($oldid, $actions)) {
				$actions = $actions[$oldid];
			} else {
				$actions = array();
			}

			$api = clone $this;
			$api->name = GetLang('CopyPrefix') . $api->name;
			$api->active = 0;
			$api->data = $data;
			$api->triggeractions = $actions;

			$this->_properties = $temp;

			return $api->Save();
		}
	}

	/**
	 * Delete
	 *
	 * Delete an existing trigger record from the database
	 *
	 * @param Integer $id Existing record to be deleted
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::DeleteMultiple()
	 */
	public function Delete($id = null)
	{
		if (is_null($id)) {
			$id = $this->triggeremailsid;
		}

		$id = intval($id);

		if ($id == 0) {
			return false;
		}

		return $this->DeleteMultiple(array($id));
	}

	/**
	 * DeleteMultiple
	 *
	 * Delete multiple records from the database
	 *
	 * @param Array $ids An array of trigger emails ID to be deleted
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses SendStudio_Functions::CheckIntVars()
	 * @uses GetUser()
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::StartTransaction()
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::RollbackTransaction()
	 * @uses Db::CommitTransaction()
	 */
	public function DeleteMultiple($ids)
	{
		if (!is_array($ids)) {
			trigger_error('IDs must be an array', E_USER_NOTICE);
			return false;
		}

		$ids = $this->CheckIntVars($ids);
		$user = GetUser();

		$implodedids = implode(',', $ids);
		$userid = intval($user->userid);

		if (empty($ids)) {
			return false;
		}

		if (!is_a($user, 'User_API')) {
			trigger_error('You need to login before you can delete any records.', E_USER_NOTICE);
			return false;
		}

		$this->Db->StartTransaction();

		// Hide information on stats table
		$status = $this->Db->Query("
			UPDATE	[|PREFIX|]stats_newsletters
			SET		hiddenby={$userid}
			WHERE	statid IN (SELECT statid FROM [|PREFIX|]triggeremails WHERE triggeremailsid IN ({$implodedids}))
		");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		// Delete any jobs in the queue table
		$status = $this->Db->Query("
			DELETE FROM [|PREFIX|]queues
			WHERE queueid IN (	SELECT queueid
								FROM [|PREFIX|]triggeremails
								WHERE triggeremailsid IN ({$implodedids}))
		");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		// Delete action data
		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_actions_data WHERE triggeremailsid IN ({$implodedids})");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		// Delete action
		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_actions WHERE triggeremailsid IN ({$implodedids})");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		// Delete log
		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_log WHERE triggeremailsid IN ({$implodedids})");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		// Delete log summary
		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_log_summary WHERE triggeremailsid IN ({$implodedids})");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		// Delete data
		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_data WHERE triggeremailsid IN ({$implodedids})");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		// Delete actual trigger records
		$status = $this->Db->Query('DELETE FROM ' . SENDSTUDIO_TABLEPREFIX . 'triggeremails WHERE triggeremailsid IN (' . implode(',', $ids) . ')');
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		$this->Db->CommitTransaction();
		return true;
	}

	/**
	 * GetActions
	 * Get actions for specified trigger emails
	 *
	 * Returned records are organized as follow:
	 * - An associative array having trigger ID as the key and contains an array of actions
	 * - The actions are an associative array with "action name" as the key. An action will contains an array of data
	 * - The data are an associative array with "data key" as the key, and data value as the value
	 *
	 * @param Integer|Array $triggerids Which trigger emails to query
	 *
	 * @return Array|FALSE Returns a list of trigger email action records if successful, FALSE otherwise
	 */
	public function GetActions($triggerids)
	{
		if (!is_array($triggerids)) {
			$triggerids = array($triggerids);
		}

		$triggerids = array_unique(array_map('intval', $triggerids));

		if (empty($triggerids)) {
			return array();
		}

		$query = "
			SELECT	a.triggeremailsid AS triggeremailsid,
					a.action AS action,
					d.datakey AS datakey,
					d.datavaluestring AS datavaluestring,
					d.datavalueinteger AS datavalueinteger
			FROM 	[|PREFIX|]triggeremails_actions AS a
						JOIN [|PREFIX|]triggeremails_actions_data AS d
							ON a.triggeremailsactionid = d.triggeremailsactionid
			WHERE	a.triggeremailsid IN (" . implode(',', $triggerids) . ")
		";
		$result = $this->Db->Query($query);
		if ($result == false) {
			list($msg, $errno) = $this->Db->GetError();
			trigger_error($msg, $errno);
			return false;
		}

		$actions = array();
		while ($row = $this->Db->Fetch($result)) {
			if (!array_key_exists($row['triggeremailsid'], $actions)) {
				$actions[$row['triggeremailsid']] = array();
			}

			if (!array_key_exists($row['action'], $actions[$row['triggeremailsid']])) {
				$actions[$row['triggeremailsid']][$row['action']] = array();
			}

			if (array_key_exists($row['datakey'], $actions[$row['triggeremailsid']][$row['action']])) {
				if (!is_array($actions[$row['triggeremailsid']][$row['action']][$row['datakey']])) {
					$actions[$row['triggeremailsid']][$row['action']][$row['datakey']] = array($actions[$row['triggeremailsid']][$row['action']][$row['datakey']]);
				}

				$actions[$row['triggeremailsid']][$row['action']][$row['datakey']][] = empty($row['datavalueinteger']) ? $row['datavaluestring'] : $row['datavalueinteger'];
			} else {
				$actions[$row['triggeremailsid']][$row['action']][$row['datakey']] = empty($row['datavalueinteger']) ? $row['datavaluestring'] : $row['datavalueinteger'];
			}
		}

		$this->Db->FreeResult($result);

		return $actions;
	}

	/**
	 * GetData
	 * Get data for specified trigger emails
	 *
	 * Returned records are organized as follow:
	 * - An associative array having trigger ID as the key and contains an array of data
	 * - The data are an associative array with "data key" as the key, and data value as the value
	 *
	 * @param Integer|Array $triggerids Which trigger emails to query
	 *
	 * @return Array|FALSE Returns a list of trigger email data records if successful, FALSE otherwise
	 */
	public function GetData($triggerids)
	{
		if (!is_array($triggerids)) {
			$triggerids = array($triggerids);
		}

		$triggerids = array_unique(array_map('intval', $triggerids));

		if (empty($triggerids)) {
			return array();
		}

		$query = "
			SELECT	*
			FROM 	[|PREFIX|]triggeremails_data
			WHERE	triggeremailsid IN (" . implode(',', $triggerids) . ")
		";
		$result = $this->Db->Query($query);
		if ($result == false) {
			list($msg, $errno) = $this->Db->GetError();
			trigger_error($msg, $errno);
			return false;
		}

		$data = array();
		while ($row = $this->Db->Fetch($result)) {
			if (!array_key_exists($row['triggeremailsid'], $data)) {
				$data[$row['triggeremailsid']] = array();
			}

			if (array_key_exists($row['datakey'], $data[$row['triggeremailsid']])) {
				if (!is_array($data[$row['triggeremailsid']][$row['datakey']])) {
					$data[$row['triggeremailsid']][$row['datakey']] = array($data[$row['triggeremailsid']][$row['datakey']]);
				}

				$data[$row['triggeremailsid']][$row['datakey']][] = empty($row['datavalueinteger']) ? $row['datavaluestring'] : $row['datavalueinteger'];
			} else {
				$data[$row['triggeremailsid']][$row['datakey']] = empty($row['datavalueinteger']) ? $row['datavaluestring'] : $row['datavalueinteger'];
			}
		}

		$this->Db->FreeResult($result);

		return $data;
	}

	/**
	 * GetRecords
	 * This method will return a list of triggeremails that are accessible by the specified user.
	 *
	 * @param Array $sortinfo An array of sorting information - what to sort by and what direction (OPTIONAL)
	 * @param Boolean $countonly Whether only to return the number of records available, rather than the whole records.
	 * @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	 * @param Mixed $perpage How many results to return (Int or String) (max).
	 *
	 * @return Mixed Returns false if it couldn't retrieve triggeremails information. Otherwise returns the count (if specified), or an array of trigger email records.
	 *
	 * @uses TriggerEmails_API::GetRecordsByUserID()
	 */
	public function GetRecords($sortinfo = array(), $countonly=false, $start=0, $perpage=10)
	{
		return $this->GetRecordsByUserID(null, $sortinfo, $countonly, $start, $perpage);
	}

	/**
	 * GetRecordByID
	 * Fetches record from the database, and return an associative array of the record
	 *
	 * @param Int $id ID of the triggeremails to be fetched
	 * @param Boolean $getdata Whether or not the function should also get triggeremails data
	 * @param Boolean $getactions Whether or not the function should also get triggeremails actions
	 *
	 * @return Mixed Returns an associative array of the record if exists, FALSE otherwise
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::GetError()
	 * @uses Db::Query()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	public function GetRecordByID($id, $getdata = false, $getactions = false)
	{
		$id = intval($id);

		$result = $this->Db->Query("
			SELECT	*
			FROM	[|PREFIX|]triggeremails
			WHERE	triggeremailsid = {$id}
		");
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$row = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		if (empty($row)) {
			return array();
		}

		$this->_processRecord($row, $getdata, $getactions);

		return $row;
	}

	/**
	 * GetRecordsByUserID
	 * This method will return a list of trigger emails that are accessible by the specified user.
	 * If the parameter $userID is omitted, all records will be returned
	 *
	 * The returned array will contains associated array,
	 * whereby the array index is the triggeremails id
	 *
	 * @param Int $userID User ID (OPTIONAL, default NULL)
	 * @param Array $sortinfo An array of sorting information - what to sort by and what direction (OPTIONAL)
	 * @param Boolean $countonly Whether only to return the number of records available, rather than the whole records.
	 * @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	 * @param Mixed $perpage How many results to return (Integer or String) (max).
	 *
	 * @return Mixed Returns false if it couldn't retrieve trigger emails information. Otherwise returns the count (if specified), or an array of trigger emails record.
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses TriggerEmails_API::_fieldDefaultSort
	 * @uses TriggerEmails_API::_fieldSortable
	 * @uses Db::AddLimit()
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	public function GetRecordsByUserID($userID = null, $sortinfo = array(), $countonly=false, $start=0, $perpage=10)
	{
		$query =	'SELECT ' . ($countonly? 'COUNT(1) AS count' : 't.*')
					. ' FROM [|PREFIX|]triggeremails AS t';

		// Constraint by user's permission if user ID is specified
		if (!empty($userID)) {
			$userID = intval($userID);
			$user   = API_USERS::getRecordById($userID);
			$query .=	"
				WHERE	t.ownerid = {$userID}
						OR t.triggeremailsid IN (
							SELECT resourceid
							FROM [|PREFIX|]usergroups_access
							WHERE 
								resourcetype = 'triggeremails'  AND 
								groupid      = {$user->groupid}
						)
			";
		}

		if (!$countonly) {
			// add sorting to the query
			$sortField     = $this->_fieldDefaultSort;
			$sortDirection = 'asc';

			if (isset($sortinfo['SortBy']) && in_array($sortinfo['SortBy'], $this->_fieldSortable)) {
				$sortField = strtolower($sortinfo['SortBy']);
			}

			switch ($sortField) {
				case 'name':
					$sortField = 'LOWER(t.name)';
				break;

				default:
					$sortField = 't.' . $sortField;
				break;
			}

			if (isset($sortinfo['Direction'])) {
				$sortDirection = strtolower(trim($sortinfo['Direction']));
			}

			$sortDirection = ($sortDirection == 'up' || $sortDirection == 'asc')? ' ASC' : ' DESC';

			$query .= ' ORDER BY ' . $sortField . $sortDirection;


			// Add limit to the query
			if ($perpage != 'all' && ($start || $perpage)) {
				$query .= $this->Db->AddLimit($start, $perpage);
			}
			
			// Query the database
			$lists  = array();
			$result = $this->Db->Query($query);
			
			if (!$result) {
				list($error, $level) = $this->Db->GetError();
				
				trigger_error($error, $level);
				
				return false;
			}
			
			while (($row = $this->Db->Fetch($result))) {
				$this->_processRecord($row);
				
				$lists[$row['triggeremailsid']] = $row;
			}
			
			$this->Db->FreeResult($result);

			return $lists;
		} else {
			$result = $this->Db->Query($query);
			
			if (!$result) {
				list($error, $level) = $this->Db->GetError();
				
				trigger_error($error, $level);
				
				return false;
			}

			$row   = $this->Db->Fetch($result);
			$count = $row['count'];

			$this->Db->FreeResult($result);

			return $count;
		}
	}

	/**
	 * Get trigger record by associated statistic ID
	 * @param $statisticID Statistic ID
	 * @return Mixed Returns an associative array of the record if exists, FALSE otherwise
	 */
	public function GetRecordByAssociatedStatisticID($statisticID)
	{
		$statisticID = intval($statisticID);

		$result = $this->Db->Query("
			SELECT	*
			FROM	[|PREFIX|]triggeremails
			WHERE	statid = {$statisticID}
		");
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$row = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		if (empty($row)) {
			return array();
		}

		$this->_processRecord($row);

		return $row;
	}

	/**
	 * RecordActivate
	 * @param Integer $recordID Record to be activated (If omitted, it will activate currently loaded record)
	 * @return Boolean Returns TRUE if operation is successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::_recordSetStatus()
	 */
	public function RecordActivate($recordID = null)
	{
		return $this->_recordSetStatus($recordID, 1);
	}

	/**
	 * RecordActivateMultiple
	 * @param Array $ids A list of record ID to be activated
	 * @return Boolean Returns TRUE if operation is successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::_recordSetMultipleStatus()
	 */
	public function RecordActivateMultiple($ids)
	{
		return $this->_recordSetMultipleStatus($ids, 1);
	}

	/**
	 * RecordDeactivate
	 * @param Integer $recordID Record to be deactivated (If omitted, it will deactivate currently loaded record)
	 * @return Boolean Returns TRUE if operation is successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::_recordSetStatus()
	 */
	public function RecordDeactivate($recordID = null)
	{
		return $this->_recordSetStatus($recordID, 0);
	}

	/**
	 * RecordDeactivateMultiple
	 * @param Array $ids A list of record ID to be deactivated
	 * @return Boolean Returns TRUE if operation is successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::_recordSetMultipleStatus()
	 */
	public function RecordDeactivateMultiple($ids)
	{
		return $this->_recordSetMultipleStatus($ids, 0);
	}

	/**
	 * GetRecipientList
	 *
	 * Get list of recipients (subscribers who have received the triggered email) for a trigger email.
	 * This follow the standard "pagination" scheme of the API.
	 *
	 * @param Integer $triggerid Trigger email ID
	 * @param Integer $start Starting offset
	 * @param Integer $perpage Record per page
	 * @param String $calendar_restrictions An SQL restriction based from the calendar duration choosen
	 * @param Boolean $count_only Whether or not to return total record count
	 * @param String $date_format Date format to format "senttime"
	 *
	 * @return Mixed Returns record count or an array of recipient records depending on the parameter
	 */
	public function GetRecipientList($triggerid, $start = 0, $perpage = 10, $calendar_restrictions = '', $count_only = false, $date_format = '')
	{
		// ----- Sanitize input
			$triggerid = intval($triggerid);
			$start = intval($start);
			$perpage = intval($perpage);
			if ($perpage == 0) {
				$perpage = 10;
			}
		// -----

		$query = 'SELECT ' . ($count_only ? 'COUNT(1)' : '*');
		$query .= ' FROM [|PREFIX|]triggeremails_log';
		$query .= " WHERE action='send' AND triggeremailsid = {$triggerid}";
		if (!empty($calendar_restrictions)) {
			$calendar_restrictions = preg_replace('/sendtime/', 'timestamp', $calendar_restrictions);
			$query .= $calendar_restrictions;
		}
		if (!$count_only) {
			$query .= ' LIMIT ' . $perpage . ' OFFSET ' . $start;
		}

		$return = false;
		$rs = $this->Db->Query($query);
		if (!$rs) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		if ($count_only) {
			$return = $this->Db->FetchOne($rs);
		} else {
			$format_checked = false;
			$return = array();
			while ($row = $this->Db->Fetch($rs)) {
				if (!empty($date_format)) {
					$row['processed_senttime'] = @date($date_format, $row['timestamp']);

					if (!$format_checked) {
						if ($row['processed_senttime'] == false) {
							$date_format = '';
						}

						$format_checked = true;
					}
				}

				array_push($return, $row);
			}
		}

		$this->Db->FreeResult($rs);
		return $return;
	}

	/**
	 * GetFailedList
	 *
	 * Get list of recipients that failed receiving the list
	 * (ie. When the application tried to send them, the MTA rejected the send)
	 *
	 * @param Integer $triggerid Trigger email ID
	 * @param Integer $start Starting offset
	 * @param Integer $perpage Record per page
	 * @param String $calendar_restrictions An SQL restriction based from the calendar duration choosen
	 * @param Boolean $count_only Whether or not to return total record count
	 * @param String $date_format Date format to format "senttime"
	 *
	 * @return Mixed Returns record count or an array of failed records depending on the parameter
	 */
	public function GetFailedList($triggerid, $start = 0, $perpage = 10, $calendar_restrictions = '', $count_only = false, $date_format = '')
	{
		// ----- Sanitize input
			$triggerid = intval($triggerid);
			$start = intval($start);
			$perpage = intval($perpage);
			if ($perpage == 0) {
				$perpage = 10;
			}
		// -----

		$query = 'SELECT ' . ($count_only ? 'COUNT(1)' : '*');
		$query .= ' FROM [|PREFIX|]triggeremails_log';
		$query .= " WHERE action='send_failed' AND triggeremailsid = {$triggerid}";
		if (!empty($calendar_restrictions)) {
			$calendar_restrictions = preg_replace('/sendtime/', 'timestamp', $calendar_restrictions);
			$query .= $calendar_restrictions;
		}
		if (!$count_only) {
			$query .= ' LIMIT ' . $perpage . ' OFFSET ' . $start;
		}

		$return = false;
		$rs = $this->Db->Query($query);
		if (!$rs) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		if ($count_only) {
			$return = $this->Db->FetchOne($rs);
		} else {
			$format_checked = false;
			$return = array();
			while ($row = $this->Db->Fetch($rs)) {
				if (!empty($date_format)) {
					$row['processed_senttime'] = @date($date_format, $row['timestamp']);

					if (!$format_checked) {
						if ($row['processed_senttime'] == false) {
							$date_format = '';
						}

						$format_checked = true;
					}
				}

				array_push($return, $row);
			}
		}

		$this->Db->FreeResult($rs);
		return $return;
	}

	/**
	 * IsOwner
	 * Check whether or not the supplied record id is owned by a particular user.
	 *
	 * @param Integer|Array $recordids Trigger email records to be checked against a user id
	 * @param Integer $ownerid Owner user ID
	 *
	 * @return Boolean Returns TRUE if all the suppied trigger email records are owned by user
	 */
	public function IsOwner($recordids, $ownerid)
	{
		if (!is_array($recordids)) {
			$recordids = intval($recordids);
			$recordids = array($recordids);
		} else {
			$recordids = array_map('intval', $recordids);
			$recordids = array_unique($recordids);
		}

		$ownerid = intval($ownerid);

		$query = 'SELECT count(1) AS counter FROM [|PREFIX|]triggeremails WHERE triggeremailsid IN (' . implode(',', $recordids) . ') AND ownerid = ' . $ownerid;
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$count = $this->Db->FetchOne($result, 'counter');
		$this->Db->FreeResult($result);

		return (count($recordids) == $count);
	}





	/**
	 * GetRecordByAssociatedNewsletterStatisticID
	 *
	 * Get trigger email records that are being triggered by newsletter open.
	 *
	 * The function will take in "StatisticID" of the newsletter,
	 * and work out the association with the newsletter and thus any triggeremails
	 * that is listening to it.
	 *
	 * An example is when IEM_STATSAPI_RECORDOPEN event is being triggered,
	 * it will only contains "statisticid" of the send and it does not contains any newsletterid.
	 * This is where this function come in handy to fetch any trigger records that are associated
	 * with the record without needing to do separate queries.
	 *
	 * This method will return an array of the following:
	 * - listid: All of the list id associated with the statistics
	 * - triggeremails: All of triggeremails that is listening to this particular newsletter open
	 *
	 * @param Integer $statid ID of the statistic ID to be searched for
	 * @return Mixed Returns FALSE if it couldn't retrieve trigger emails information. Otherwise returns an array response (see description).
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	protected function GetRecordByAssociatedNewsletterStatisticID($statid)
	{
		$return = array(
			'lists' => array(),
			'triggeremails' => array()
		);

		$statid = intval($statid);
		$newsletter = null;

		if ($statid == 0) {
			trigger_error('Invalid statistic ID specified', E_USER_NOTICE);
			return false;
		}

		// ----- Get Newsletter ID from statistic
			require_once(dirname(__FILE__) . '/stats.php');
			$statapi = new Stats_API();

			$newsletter = $statapi->GetNewsletterSummary($statid, true);
			if (empty($newsletter) || !isset($newsletter['newsletterid'])) {
				// Fail silently, because if the newsletter record has been removed we don't want to fill up the logs.
				return false;
			}

			unset($statapi);
		// -----

		if (isset($newsletter['lists']) && is_array($newsletter['lists'])) {
			$return['lists'] = $newsletter['lists'];
		}

		$query = "
			SELECT	t.*
			FROM 	[|PREFIX|]triggeremails AS t
						JOIN [|PREFIX|]triggeremails_data AS td
							ON (	td.triggeremailsid = t.triggeremailsid
									AND td.datakey = 'newsletterid'
									AND td.datavalueinteger = {$newsletter['newsletterid']})
			WHERE	t.active = '1'
					AND t.triggertype = 'n'
		";

		$result = $this->Db->Query($query);

		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		while (($row = $this->Db->Fetch($result))) {
			$return['triggeremails'][$row['triggeremailsid']] = $row;
		}
		$this->Db->FreeResult($result);

		// ----- Get data and actions associated with the triggers
			if (!empty($return['triggeremails'])) {
				$triggerids = array_keys($return['triggeremails']);

				$tempData = $this->GetData($triggerids);
				if (!$tempData) {
					trigger_error('Cannot fetch triggeremails data', E_USER_NOTICE);
					return false;
				}
				foreach ($tempData as $id => $each) {
					$return['triggeremails'][$id]['data'] = $each;
				}

				$tempActions = $this->GetActions($triggerids);
				if (!$tempActions) {
					trigger_error('Cannot fetch triggeremails actions', E_USER_NOTICE);
					return false;
				}
				foreach ($tempActions as $id => $each) {
					$return['triggeremails'][$id]['triggeractions'] = $each;
				}
			}
		// -----

		return $return;
	}

	/**
	 * GetRecordByAssociatedLinkIDStatID
	 * Get trigger email records that are being triggered by link clicked on a particular statistic.
	 *
	 * This method will return an array of the following:
	 * - listid: All of the list id associated with the statistics
	 * - triggeremails: All of triggeremails that is listening to this particular newsletter open
	 *
	 * @param Integer $linkid ID of the link to be searched for
	 * @param Integer $statid ID of the statistic of which the link was clicked from
	 * @return Mixed Returns FALSE if it couldn't retrieve trigger emails information. Otherwise returns an array response (see description).
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	protected function GetRecordByAssociatedLinkIDStatID($linkid, $statid)
	{
		$return = array(
			'lists' => array(),
			'triggeremails' => array()
		);

		$linkid = intval($linkid);
		$statid = intval($statid);
		$newsletter = null;

		if ($linkid == 0 || $statid == 0) {
			trigger_error('Invalid link ID/Stat ID specified', E_USER_NOTICE);
			return false;
		}

		// ----- Get Newsletter ID from statistic
			require_once(dirname(__FILE__) . '/stats.php');
			$statapi = new Stats_API();

			$newsletter = $statapi->GetNewsletterSummary($statid, true);
			if (empty($newsletter) || !isset($newsletter['newsletterid'])) {
				// Fail silently, because if the newsletter record has been removed we don't want to fill up the logs.
				return false;
			}

			unset($statapi);
		// -----

		if (isset($newsletter['lists']) && is_array($newsletter['lists'])) {
			$return['lists'] = $newsletter['lists'];
		}

		$query = "
			SELECT	t.*
			FROM 	[|PREFIX|]triggeremails AS t
						JOIN [|PREFIX|]triggeremails_data AS tdl
							ON (	tdl.triggeremailsid = t.triggeremailsid
									AND tdl.datakey = 'linkid'
									AND tdl.datavalueinteger = {$linkid})
						JOIN [|PREFIX|]triggeremails_data AS tdn
							ON (	tdn.triggeremailsid = t.triggeremailsid
									AND tdn.datakey = 'linkid_newsletterid'
									AND tdn.datavalueinteger = {$newsletter['newsletterid']})
			WHERE	t.active = '1'
					AND t.triggertype = 'l'
		";

		$result = $this->Db->Query($query);

		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		while (($row = $this->Db->Fetch($result))) {
			$return['triggeremails'][$row['triggeremailsid']] = $row;
		}

		$this->Db->FreeResult($result);

		// ----- Get data and actions associated with the triggers
			if (!empty($return['triggeremails'])) {
				$triggerids = array_keys($return['triggeremails']);

				$tempData = $this->GetData($triggerids);
				if (!$tempData) {
					trigger_error('Cannot fetch triggeremails data', E_USER_NOTICE);
					return false;
				}
				foreach ($tempData as $id => $each) {
					$return['triggeremails'][$id]['data'] = $each;
				}

				$tempActions = $this->GetActions($triggerids);
				if (!$tempActions) {
					trigger_error('Cannot fetch triggeremails actions', E_USER_NOTICE);
					return false;
				}
				foreach ($tempActions as $id => $each) {
					$return['triggeremails'][$id]['triggeractions'] = $each;
				}
			}
		// -----

		return $return;
	}

	/**
	 * CanAddToQueue
	 *
	 * Check whether or not the trigger can add the recipient into the queue table
	 *
	 * @param Integer $triggeremailsid Trigger emails ID
	 * @param Mixed $recipientid ID(s) of the recipients that needs to be checked (Integer[]|Integer)
	 * @param Integer $queueid Queue ID of the trigger to indicate whether or not to check the queue table for currently waiting send
	 *
	 * @return Mixed Returns TRUE if you can add the subscriberid to the queue, FALSE if you can't add them to the queue, NULL if it encounters any errors
	 *
	 * @uses Db::Query()
	 * @uses Db::GetError()
	 * @uses Db::Fetch()
	 * @uses Db::FreeResult()
	 */
	protected function CanAddToQueue($triggeremailsid, $recipientid, $queueid = 0)
	{
		if (!is_array($recipientid)) {
			$recipientid = array($recipientid);
		}

		$recipientid = array_map('intval', $recipientid);

		$implodedRecipient = implode(',', $recipientid);
		$triggeremailsid = intval($triggeremailsid);
		$queueid = intval($queueid);

		$cutoff = (intval(time() / 86400) - 1) * 86400;

		$query = "
			SELECT	COUNT(1) AS count
			FROM	[|PREFIX|]triggeremails_log_summary
			WHERE	triggeremailsid = {$triggeremailsid}
					AND subscriberid IN ({$implodedRecipient})
					AND lastactiontimestamp > {$cutoff}
		";

		if ($queueid != 0) {
			$query .= "
				UNION

				SELECT	COUNT(1) AS count
				FROM	[|PREFIX|]queues
				WHERE	queueid = {$queueid}
						AND recipient IN ({$implodedRecipient})
			";
		}

		$status = $this->Db->Query($query);
		if ($status == false) {
			list($msg, $errno) = $this->Db->GetError();
			trigger_error($msg, $errno);
			return null;
		}

		$sum = 0;
		while ($row = $this->Db->Fetch($status)) {
			$sum += intval($row['count']);
		}
		$this->Db->FreeResult($status);

		return ($sum == 0);
	}


	/**
	 * RecordLogActions
	 * Record actions that were taken by the triggers
	 *
	 * @param Integer $triggeremailsid Trigger Emails ID
	 * @param Integer $recipientid Recipient ID
	 * @param Array|String $actions Action
	 *
	 * @return Bolean Returns TRUE if successful, FALSE otherwise
	 */
	protected function RecordLogActions($triggeremailsid, $recipientid, $actions, $note = null)
	{
		/// ----- Untaint input
			$triggeremailsid = intval($triggeremailsid);
			$recipientid = intval($recipientid);
			$timestamp = time();

			if (!is_array($actions)) {
				$actions = array($actions);
			}
		// -----

		$note = is_null($note) ? 'NULL' : ("'" . $this->Db->Quote($note) . "'");

		$values = array();
		foreach ($actions as $action) {
			$action = $this->Db->Quote($action);
			$values[] = "({$triggeremailsid}, {$recipientid}, '{$action}', {$timestamp}, $note)";
		}

		if (count($values) == 0) {
			return true;
		}

		$query = "
			INSERT INTO [|PREFIX|]triggeremails_log(
				triggeremailsid,
				subscriberid,
				action,
				timestamp,
				note
			) VALUES
		";
		$query .= implode(',', $values);

		if (!$this->Db->Query($query)) {
			list($msg, $errno) = $this->Db->GetError();

			// Check if trigger has previously been sent to the same subscriber
			$tempResult = $this->Db->Query("SELECT * FROM [|PREFIX|]triggeremails_log WHERE triggeremailsid = {$triggeremailsid} AND subscriberid = {$recipientid} AND action = '{$action}' AND timestamp = {$senttime}");
			if (!$tempResult) {
				trigger_error($msg, $errno);
				return false;
			}

			$tempRow = $this->Db->Fetch($tempResult);
			$this->Db->FreeResult($tempResult);

			// No, the same set of data did not exists, so there might be something wrong with the database server?
			if (!empty($tempRow)) {
				trigger_error($msg, $errno);
				return false;
			}
		}

		return true;
	}

	/**
	 * RecordLogSummary
	 * Record/Update log summary
	 *
	 * @param Integer $triggeremilsid Trigger Emails ID
	 * @param Integer $recipientid Recipient ID
	 *
	 * @return Bolean Returns TRUE if successful, FALSE otherwise
	 */
	protected function RecordLogSummary($triggeremilsid, $recipientid)
	{
		/// ----- Untaint input
			$triggeremilsid = intval($triggeremilsid);
			$recipientid = intval($recipientid);
			$lastactiontimestamp = time();
		// -----

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$status = $this->Db->Query("
				INSERT INTO [|PREFIX|]triggeremails_log_summary(triggeremailsid, subscriberid, actionedoncount, lastactiontimestamp)
				VALUES ({$triggeremilsid}, {$recipientid}, 1, {$lastactiontimestamp})
				ON DUPLICATE KEY UPDATE actionedoncount = actionedoncount + 1, lastactiontimestamp = {$lastactiontimestamp}
			");
			if (!$status) {
				list($msg, $errno) = $this->Db->GetError();
				trigger_error($msg, $errno);
				return false;
			}
		} else {
			$status = $this->Db->Query("SELECT triggeremailsid FROM [|PREFIX|]triggeremails_log_summary WHERE triggeremailsid = {$triggeremilsid} AND subscriberid = {$recipientid}");
			if (!$status) {
				list($msg, $errno) = $this->Db->GetError();
				trigger_error($msg, $errno);
				return false;
			}

			$tempRow = $this->Db->Fetch($status);
			$this->Db->FreeResult($status);

			if ($tempRow) {
				$query = "
					UPDATE [|PREFIX|]triggeremails_log_summary SET actionedoncount = actionedoncount + 1, lastactiontimestamp = {$lastactiontimestamp}
					WHERE 	triggeremailsid = {$triggeremilsid}
							AND subscriberid = {$recipientid}
				";
			} else {
				$query = "
					INSERT INTO [|PREFIX|]triggeremails_log_summary(triggeremailsid, subscriberid, actionedoncount, lastactiontimestamp)
					VALUES ({$triggeremilsid}, {$recipientid}, 1, {$lastactiontimestamp})
				";
			}

			$status = $this->Db->Query($query);
			if (!$status) {
				list($msg, $errno) = $this->Db->GetError();
				trigger_error($msg, $errno);
				return false;
			}
		}

		return true;
	}




	/**
	 * _processRecord
	 * This function will process records that was fetched from the database
	 * to add any "processed" values that needed to be processed
	 *
	 * @param Array &$record The record to be processed
	 * @param Boolean $getdata Whether or not the function should also get triggeremails data
	 * @param Boolean $getactions Whether or not the function should also get triggeremails actions
	 *
	 * @return Void Does not return anything
	 *
	 * @uses AdjustTime()
	 * @uses GetLang()
	 */
	private function _processRecord(&$record, $getdata = false, $getactions = false)
	{
		$record['procstr_createdate'] = AdjustTime($record['createdate'], false, GetLang('DateFormat'), true);

		if ($getdata) {
			$temp = $this->GetData($record['triggeremailsid']);
			if (!$temp) {
				trigger_error('Cannot fetch trigger data', E_USER_NOTICE);
				return;
			}

			$record['data'] = $temp[$record['triggeremailsid']];
		}

		if ($getactions) {
			$temp = $this->GetActions($record['triggeremailsid']);
			if (!$temp) {
				trigger_error('Cannot fetch trigger data', E_USER_NOTICE);
				return;
			}

			$record['triggeractions'] = $temp[$record['triggeremailsid']];
		}
	}

	/**
	 * _recordSetStatus
	 * This function will process anything that needs to be done to activate/deactivate a record
	 *
	 * @param Integer $recordID ID of the record to be activated/deactivated
	 * @param Integer $status Status to change the record into (Currently available values are: 1 or 0)
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses TriggerEmails_API::_recordSetMultipleStatus()
	 * @uses TriggerEmails_API::Save()
	 */
	private function _recordSetStatus($recordID, $status)
	{
		if (is_null($recordID) && $this->triggeremailsid == 0) {
			trigger_error('ID must be specified, or load the API with a record', E_USER_NOTICE);
			return false;
		}

		$status = intval($status);
		if ($status != 0) {
			$status = 1;
		}

		// ----- If recordID is specified, use that instead
			if (!is_null($recordID)) {
				$recordID = intval($recordID);
				if ($recordID == 0) {
					trigger_error('Invalid ID passed through', E_USER_NOTICE);
					return false;
				}

				return $this->_recordSetMultipleStatus(array($recordID), $status);
			}
		// -----

		$this->active = $status;
		if ($this->Save() === false) {
			return false;
		}

		return true;
	}

	/**
	 * _recordSetMultipleStatus
	 * This function will process anything that needs to be done to activate/deactivate multiple records
	 *
	 * @param Array $recordID A list of record IDs to be activated/deactivated
	 * @param Integer $status Status to change the records into (Currently available values are: 1 or 0)
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @uses SendStudio_Functions::CheckIntVars()
	 * @uses Db::Query()
	 */
	private function _recordSetMultipleStatus($ids, $status)
	{
		if (!is_array($ids)) {
			trigger_error('IDs must be an array', E_USER_NOTICE);
			return false;
		}

		$ids = $this->CheckIntVars($ids);

		if (empty($ids)) {
			return false;
		}

		$status = intval($status);
		if ($status != 0) {
			$status = 1;
		}

		$status = $this->Db->Query("UPDATE [|PREFIX|]triggeremails SET active='{$status}' WHERE triggeremailsid IN (" . implode(',', $ids) . ')');
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			trigger_error($msg, $errno);
			return false;
		}

		return true;
	}

	/**
	 * _updateActions
	 * Update actions
	 *
	 * @param Integer $triggerid Trigger ID
	 * @param Array $actions Action records to be updated as the trigger action
	 * @return Boolean Returns TRUE if successful, FALSE otherwsie
	 */
	private function _updateActions($triggerid, $actions)
	{
		$triggerid = intval($triggerid);
		if ($triggerid == 0) {
			return false;
		}

		$this->Db->StartTransaction();

		// Remove related action and action data first
		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_actions_data WHERE triggeremailsid = {$triggerid}");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_actions WHERE triggeremailsid = {$triggerid}");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}


		foreach ($actions as $actionName => $actionData) {
			// Insert into action table
			$tempActionName = $this->Db->Quote($actionName);
			$query = "INSERT INTO [|PREFIX|]triggeremails_actions (triggeremailsid, action) VALUES ({$triggerid}, '{$tempActionName}')";
			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$query .= ' RETURNING triggeremailsactionid';
			}

			$status = $this->Db->Query($query);
			if ($status === false) {
				list($msg, $errno) = $this->Db->GetError();
				$this->Db->RollbackTransaction();
				trigger_error($msg, $errno);
				return false;
			}

			// Get new ID
			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$temp = $this->Db->Fetch($status);
				$this->Db->FreeResult($status);

				$tempActionID = $temp['triggeremailsactionid'];
			} else {
				$tempActionID = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'triggeremails_action_triggeremailsactionid_seq');
			}

			// Construct a series of string that can be used to insert action data in one query
			$tempActionDatas = array();
			foreach ($actionData as $dataKey => $dataValue) {
				$tempDataKey = $this->Db->Quote($dataKey);

				if (!is_array($dataValue)) {
					$dataValue = array($dataValue);
				}

				foreach ($dataValue as $each) {
					$tempDataValueString = '';
					$tempDataValueInteger = 'NULL';

					if (is_numeric($each)) {
						$tempDataValueInteger = intval($each);
					} else {
						$tempDataValueString = $this->Db->Quote($each);
					}

					$tempActionDatas[] = "({$tempActionID}, '{$tempDataKey}', '{$tempDataValueString}', {$tempDataValueInteger}, {$triggerid})";
				}
			}

			// Insert action data
			$status = $this->Db->Query("INSERT INTO [|PREFIX|]triggeremails_actions_data (triggeremailsactionid, datakey, datavaluestring, datavalueinteger, triggeremailsid) VALUES " . implode(',', $tempActionDatas));
			if ($status === false) {
				list($msg, $errno) = $this->Db->GetError();
				$this->Db->RollbackTransaction();
				trigger_error($msg, $errno);
				return false;
			}
		}

		$this->Db->CommitTransaction();
		return true;
	}

	/**
	 * _updateData
	 * Update data
	 *
	 * @param Integer $triggerid Trigger ID
	 * @param Array $data Data records to be updated in to the database
	 * @return Boolean Returns TRUE if successful, FALSE otherwsie
	 */
	private function _updateData($triggerid, $data)
	{
		$triggerid = intval($triggerid);
		if ($triggerid == 0) {
			return false;
		}

		$this->Db->StartTransaction();

		// Remove related data first
		$status = $this->Db->Query("DELETE FROM [|PREFIX|]triggeremails_data WHERE triggeremailsid = {$triggerid}");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		$tempDataInsertQueries = array();
		foreach ($data as $dataKey => $dataValue) {
			$dataKey = $this->Db->Quote($dataKey);

			if (!is_array($dataValue)) {
				$dataValue = array($dataValue);
			}

			foreach ($dataValue as $each) {
				$tempDataValueString = '';
				$tempDataValueInteger = 'NULL';

				if (is_numeric($each)) {
					$tempDataValueInteger = intval($each);
				} else {
					$tempDataValueString = $this->Db->Quote($each);
				}

				$tempDataInsertQueries[] = "{$triggerid}, '{$dataKey}', '{$tempDataValueString}', {$tempDataValueInteger}";
			}
		}

		$status = $this->Db->Query("INSERT INTO [|PREFIX|]triggeremails_data (triggeremailsid, datakey, datavaluestring, datavalueinteger) VALUES (" . implode('),(', $tempDataInsertQueries) . ")");
		if ($status === false) {
			list($msg, $errno) = $this->Db->GetError();
			$this->Db->RollbackTransaction();
			trigger_error($msg, $errno);
			return false;
		}

		$this->Db->CommitTransaction();
		return true;
	}

	/**
	 * _validateRecord
	 * Validate records
	 *
	 * Check whether or not specified resources exists
	 *
	 * @param Array $record Associated array of the trigger record
	 * @param Array $data Associated array of trigger record data
	 * @param Array $actions Associated array of trigger actions data
	 *
	 * @return Boolean Returns TRUE if everything is verified, FALSE otherwise
	 */
	private function _validateRecord($record, $data, $actions)
	{
		$actions_specified = array();

		// The follwing are needed for each trigger type
		switch ($record['triggertype']) {
			case 'f':
				// listid and customfieldid needs to be populated
				if (!isset($data['listid']) || empty($data['listid']) || !isset($data['customfieldid']) || empty($data['customfieldid'])) {
					trigger_error('listid and customfieldid data must be sepecified', E_USER_NOTICE);
					return false;
				}

				require_once(dirname(__FILE__) . '/lists.php');
				$listapi = new Lists_API();

				$customfields = $listapi->GetCustomFields($data['listid']);
				if (!array_key_exists($data['customfieldid'], $customfields)) {
					trigger_error('Custom field is not available', E_USER_NOTICE);
					return false;
				}
			break;

			case 'l':
				// linkid_newsletterid and linkid must be populated
				if (!isset($data['linkid_newsletterid']) || empty($data['linkid_newsletterid']) || !isset($data['linkid']) || empty($data['linkid'])) {
					trigger_error('linkid_newsletterid and linkid data must be specified', E_USER_NOTICE);
					return false;
				}

				require_once(dirname(__FILE__) . '/newsletters.php');
				$newsletterapi = new Newsletters_API();

				$links = $newsletterapi->GetLinks($data['linkid_newsletterid']);
				if (!array_key_exists($data['linkid'], $links)) {
					trigger_error('Links does not exists', E_USER_NOTICE);
					return false;
				}
			break;

			case 'n':
				// newsletterid must be populated
				if (!isset($data['newsletterid']) || empty($data['newsletterid'])) {
					trigger_error('newsletterid data must be sepecified', E_USER_NOTICE);
					return false;
				}

				require_once(dirname(__FILE__) . '/newsletters.php');
				$newsletterapi = new Newsletters_API();

				if (!is_array($newsletterapi->GetRecordByID($data['newsletterid']))) {
					trigger_error('Newsletter does not exits', E_USER_NOTICE);
					return false;
				}
			break;

			case 's':
				// staticdate must be populated
				if (!isset($data['staticdate']) || empty($data['staticdate'])) {
					trigger_error('staticdate data must be sepecified', E_USER_NOTICE);
					return false;
				}

				list($year, $month, $day) = explode('-', $data['staticdate']);
				$tempTime = mktime(0, 0, 0, $month, $day, $year);
				if (!$tempTime || $tempTime == -1) {
					trigger_error('Invalid date specified', E_USER_NOTICE);
					return false;
				}

				if (!isset($data['staticdate_listids']) || empty($data['staticdate_listids'])) {
					trigger_error('staticdate must be assigned to a specific list', E_USER_NOTICE);
					return false;
				}

				if (!is_array($data['staticdate_listids'])) {
					$data['staticdate_listids'] = array($data['staticdate_listids']);
				}

				require_once(dirname(__FILE__) . '/lists.php');
				$listapi = new Lists_API();
				$count = $listapi->GetLists($data['staticdate_listids'], array(), true);
				if (!$count || count($data['staticdate_listids']) != $count) {
					trigger_error('Some (or All) the contact list assigned to this record is not available', E_USER_NOTICE);
					return false;
				}
			break;

			default:
				trigger_error('Unknown trigger type', E_USER_NOTICE);
				return false;
			break;
		}

		// ----- The following are required for "send" action
			if (isset($actions['send']) && isset($actions['send']['enabled']) && $actions['send']['enabled']) {
				$temp = array('newsletterid', 'sendfromname', 'sendfromemail', 'replyemail', 'bounceemail');
				foreach ($temp as $each) {
					if (!isset($actions['send'][$each])) {
						trigger_error('Required parameter for send actions are not passed in', E_USER_NOTICE);
						return false;
					}
				}

				// Check if newsletterid is available
				require_once(dirname(__FILE__) . '/newsletters.php');
				$newsletterapi = new Newsletters_API();

				if (!is_array($newsletterapi->GetRecordByID($actions['send']['newsletterid']))) {
					trigger_error('Newsletter does not exits', E_USER_NOTICE);
					return false;
				}

				array_push($actions_specified, 'send');
			}
		// -----

		// ----- The following are required for "addlist" action
			if (isset($actions['addlist']) && isset($actions['addlist']['enabled']) && $actions['addlist']['enabled']) {
				if (!isset($actions['addlist']['listid']) || empty($actions['addlist']['listid'])) {
					trigger_error('Required parameter for "addlist" actions are not passed in', E_USER_NOTICE);
					return false;
				}

				if (!is_array($actions['addlist']['listid'])) {
					$actions['addlist']['listid'] = array($actions['addlist']['listid']);
				}

				// Check if selected lists are available
				require_once(dirname(__FILE__) . '/lists.php');
				$listapi = new Lists_API();
				$count = $listapi->GetLists($actions['addlist']['listid'], array(), true);
				if (!$count || count($actions['addlist']['listid']) != $count) {
					trigger_error('Some (or All) the contact list assigned to this record is not available', E_USER_NOTICE);
					return false;
				}

				array_push($actions_specified, 'addlist');
			}
		// -----

		// ----- The following are required for "removelist" action
			if (isset($actions['removelist']) && isset($actions['removelist']['enabled']) && $actions['removelist']['enabled']) {
				// removelist action does not need anything, but we will need to add it to the "actions_specified" array
				array_push($actions_specified, 'removelist');
			}
		// -----

		// At least one action needs to be specified:
		if (empty($actions_specified)) {
			trigger_error('At least one trigger actions need to be specified', E_USER_NOTICE);
			return false;
		}

		return true;
	}

	/**
	 * _getSubscriberIDSFromList
	 * Get all subsriber IDs with the same email address within a list
	 *
	 * @param Integer $subscriberid Subscriber ID
	 * @param Array $lists An array of list IDs
	 *
	 * @return Array|FALSE an array of return records that can be used to pass through Api::AddToQueue(), FALSE otherwise
	 */
	private function _getSubscriberIDSFromList($subscriberid, $lists)
	{
		if (!is_array($lists) || empty($lists)) {
			return array(array('subscriberid' => $subscriberid));
		}

		require_once(dirname(__FILE__) . '/subscribers.php');
		$subscribersapi = new Subscribers_API();

		$emailaddress = $subscribersapi->GetEmailForSubscriber($subscriberid);
		if (!$emailaddress) {
			return array(array('subscriberid' => $subscriberid));
		}

		$records = $subscribersapi->GetAllListsForEmailAddress($emailaddress, $lists);

		if (empty($records)) {
			return array(array('subscriberid' => $subscriberid));
		}

		return $records;
	}
}
