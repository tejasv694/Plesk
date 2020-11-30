<?php
/**
 * The Subscribers API. It handles loading, whether a subscriber exists, is on a particular mailing list and so on.
 *
 * @version     $Id: subscribers.php,v 1.133 2008-02-25 06:24:52 chris Exp $
 * @author Chris <chris@interspire.com>
 * @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
 *
 * @package API
 * @subpackage Subscribers_API
 */

/**
 * Load up the base API class if we need to.
 */
require_once(dirname(__FILE__) . '/api.php');

/**
 * This will load a subscriber, save a subscriber, set details and get details.
 *
 * @package API
 * @subpackage Subscribers_API
 */
class Subscribers_API extends API
{

	/**
	 * The current subscriber id. By default it's '0'. Is set by Load
	 *
	 * @see Load
	 *
	 * @var Int
	 */
	var $subscriberid = 0;

	/**
	 * Current subscriber list id
	 * @var Int
	 */
	var $listid = 0;

	/**
	 * The subscribers email address.
	 *
	 * @var String
	 */
	var $emailaddress = '';

	/**
	 * The subscribers chosen format. This is either 't' (text) or 'h' (html). This depends on which list they are subscribed (or trying to subscribe) to.
	 *
	 * @var String
	 */
	var $format = 't';

	/**
	 * Whether the subscriber has confirmed their email address or not. This depends on which list they are subscribed (or trying to subscribe) to.
	 *
	 * @var Int
	 */
	var $confirmed = 0;

	/**
	 * The form the subscriber first joined from.
	 *
	 * @var Int
	 */
	var $formid = 0;

	/**
	 * A random string used to authenticate a subscriber. It is randomly generated.
	 *
	 * @see GenerateConfirmCode
	 *
	 * @var String
	 */
	var $confirmcode = false;

	/**
	 * The date the subscriber tried to join a list.
	 *
	 * @var Int
	 */
	var $requestdate = 0;

	/**
	 * The ip from which the subscriber tried to join a list.
	 *
	 * @var String
	 */
	var $requestip = '';

	/**
	 * Whether the subscriber has confirmed their unsubscribe request or not.
	 *
	 * @var Int
	 */
	var $unsubscribeconfirmed = 0;

	/**
	 * The ip from which the subscriber tried to unsubscribe from a list.
	 *
	 * @var String
	 */
	var $unsubscriberequestip = '';

	/**
	 * The date the subscriber tried to unsubscribe from a list.
	 *
	 * @var Int
	 */
	var $unsubscriberequesttime = 0;

	/**
	 * The date the subscriber confirmed they wanted to unsubscribe.
	 *
	 * @var Int
	 */
	var $unsubscribetime = 0;

	/**
	 * The ip from which the subscriber confirmed unsubscribing.
	 *
	 * @var String
	 */
	var $unsubscribeip = '';

	/**
	 * The date the subscriber confirmed their subscription.
	 *
	 * @var Int
	 */
	var $confirmdate = 0;

	/**
	 * The date the subscriber became active on the list.
	 * This is kept separately and can be imported through 'Import Subscribers' so we can see the 3 stages:
	 * - request
	 * - confirm
	 * - subscribe
	 *
	 * @var Int
	 */
	var $subscribedate = 0;

	/**
	 * The ip from which the subscriber confirmed joining the list.
	 *
	 * @var String
	 */
	var $confirmip = '';

	/**
	 * This temporarily stores the subscribers custom field data. This depends on which list they are subscribed (or trying to subscribe) to.
	 *
	 * @see GetCustomFieldSettings
	 * @see LoadSubscriberList
	 * @see LoadSubscriberListCustomFields
	 * @see GetCustomFieldSettings
	 *
	 * @var Array
	 */
	var $customfields = array();

	/**
	 * Required number of messages before we 'bounce' a subscriber from the list and make them inactive. This is the upper limit, so if they reach this amount they are bounced. That is, it's inclusive of the bounce that is being recorded.
	 *
	 * @var Int
	 */
	var $softbounce_count = 5;

	/**
	 * Stores cache of customfield lists
	 * @var Array Custom fields record list
	 */
	var $_cacheCustomfields = null;

	/**
	 * Stores a list of columns contact events can be sorted by
	 * @var Array An array of column names
	 */
	var $ValidEventSorts = array('eventsubject','eventtype','lastupdate','username','eventdate');

	/**
	 * Constructor
	 * Sets up the database object only. You cannot pass in a subscriber id to load. Loading a subscriber's settings depends on what list they are subscribed to.
	 *
	 * @see LoadSubscriberList
	 *
	 * @return True Always returns true.
	 */
	function Subscribers_API()
	{
		$this->GetDb();
		return true;
	}

	/**
	 * LoadSubscriberBasicInformation
	 * Loads up basic subscriber information for a particular list, which includes the format they are subscribed as, the confirm code and so on. This is used by scheduled sending to check which list(s) a particular email address is on.
	 * If they are not on a list or they are bounced / unsubscribed from a list, this returns an empty array.
	 * If they are active on a list it returns an array of their information.
	 *
	 * @param Int $subscriberid The subscriber / recipient to check.
	 * @param Array $listids The listids to check. This can be a single number (eg '1') or an array. If it's not an array, it will be converted into one.
	 *
	 * @return Array Returns either an empty array or full array.
	 */
	function LoadSubscriberBasicInformation($subscriberid=0, $listids=Array())
	{
		if (!is_array($listids)) {
			$listids = array((int)$listids);
		} else {
			$listids = $this->CheckIntVars($listids);
		}

		if (empty($listids)) {
			return array();
		}

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid IN (" . implode(',', $listids) . ") AND subscriberid=" . intval($subscriberid);

		$result = $this->Db->Query($query);
		$subscriberinfo = $this->Db->Fetch($result);

		if (empty($subscriberinfo)) {
			return array();
		}

		if ($subscriberinfo['bounced'] > 0 || $subscriberinfo['unsubscribed'] > 0) {
			return array();
		}

		$subscriberinfo['subscriberid'] = $subscriberid;
		return $subscriberinfo;
	}

	/**
	 * GetSubscriberIdsToConfirm
	 * Gets a list of subscriberid's that need confirming based on the email address and confirm code passed in.
	 * We need to do this because subscriberid's are unique per system, so if you sign up for multiple lists we need to get all subscriberid's to confirm.
	 * The confirm code will be the same and of course so will the email address.
	 * Returns an array of subscriberid's that need confirming.
	 *
	 * @param String $email Email address to check for
	 * @param String $confirmcode The confirmation code to check for
	 *
	 * @see confirm.php
	 *
	 * @return Array Returns an array of subscriberid's that need confirming.
	 */
	function GetSubscriberIdsToConfirm($email='', $confirmcode='')
	{
		$email = trim($email);

		$query = "SELECT subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE emailaddress='" . $this->Db->Quote($email) . "' AND confirmcode='" . $this->Db->Quote($confirmcode) . "'";

		$result = $this->Db->Query($query);

		$ids = array();
		while ($row = $this->Db->Fetch($result)) {
			$ids[] = $row['subscriberid'];
		}
		return $ids;
	}

	/**
	 * GetAllListsForEmailAddress
	 * Gets all subscriberid's, listid's for a particular email address and returns an array of them.
	 * This is used by the unsubscribe function to remove a subscriber from multiple lists at once.
	 * This only gets called if you send to multiple lists.
	 * This also only finds active subscribers. If you have unsubscribed from a list already it will not return your entry.
	 *
	 * @param String $email The email address to find on all of the lists.
	 * @param Array $listids The lists to check for the address on. This will be the lists that the newsletter was sent to. By limiting the query here, saves some processing on the unsubscribe side of things.
	 * @param Int $main_listid This is used for ordering the results of the query. When this is passed in, the main list should appear at the top. This makes it first in line for checking whether the subscriber is valid or not. We need to do this if you are subscribed to multiple lists, because confirmcodes will be different per list.
	 *
	 * @return Array Returns either an empty array (if no email address is passed in) or a multidimensional array containing both subscriberid and listid.
	 */
	function GetAllListsForEmailAddress($email='', $listids=array(), $main_listid=0)
	{
		$return = array();
		if (!$email) {
			return $return;
		}

		$email = trim($email);

        if(!is_array($listids)){$listids = array($listids);}
                
		$listids = $this->CheckIntVars($listids);

		$query = "SELECT subscriberid, listid";

		if ($main_listid) {
			$query .= ", CASE WHEN listid='" . (int)$main_listid . "' THEN 1 ELSE 0 END AS order_list";
		}

		$query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE emailaddress='" . $this->Db->Quote($email) . "' AND (unsubscribed = 0 AND bounced = 0)";
		if (!empty($listids) && $listids[0] != '0') {
			$query .= " AND listid IN (" . implode(',', $listids) . ")";
		}

		if ($main_listid) {
			$query .= " ORDER BY order_list DESC";
		}

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$return[] = array('subscriberid' => $row['subscriberid'], 'listid' => $row['listid']);
		}
		return $return;
	}

	/**
	 * GetEmailForSubscriber
	 * Gets the email address for the subscriberid passed in. This is used by unsubscribe forms so we can then use GetAllListsForEmailAddress to get all lists the subscriber is on. We need to do this in case you send to multiple lists and someone clicks an unsubscribe link.
	 *
	 * @param Int $subscriberid The subscriberid to look up and fetch the email address for.
	 *
	 * @return Boolean|String Returns false if the email address can't be found. Otherwise returns the email address.
	 */
	function GetEmailForSubscriber($subscriberid=0)
	{
		$query = "SELECT emailaddress FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE subscriberid=" . intval($subscriberid);
		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if (empty($row) || !isset($row['emailaddress'])) {
			return false;
		}
		return $row['emailaddress'];
	}


	/**
	 * LoadSubscriberForm
	 * This is used by confirmation emails to get the order of the custom fields according to the order in the website form.
	 *
	 * It is much the same as LoadSubscriberList & LoadSubscriberCustomFields put together, but it joins the form custom fields table as well to get the order and give info in the specified order.
	 *
	 * @see LoadSubscriberList
	 * @see LoadSubscriberCustomFields
	 *
	 * @return Array Returns an array containing the basic subscriber information and also the custom field information in the order according to the form.
	 */
	function LoadSubscriberForm($subscriberid=0, $listid=0)
	{
		$subscriberinfo = $this->LoadSubscriberList($subscriberid, $listid, true, false, false);

		if (empty($subscriberinfo)) {
			return array();
		}

		$allcustomfields = array();

		$query = "select distinct sd.fieldid AS fieldid, sd.data AS data, c.fieldtype AS fieldtype, c.name AS fieldname FROM ";
		$query .= SENDSTUDIO_TABLEPREFIX . "subscribers_data sd,";
		$query .= SENDSTUDIO_TABLEPREFIX . "customfield_lists cl,";
		$query .= SENDSTUDIO_TABLEPREFIX . "form_customfields fc,";
		$query .= SENDSTUDIO_TABLEPREFIX . "customfields c";
		$query .= " WHERE ";
		$query .= "sd.fieldid=cl.fieldid AND ";
		$query .= "fc.fieldid=cl.fieldid AND ";
		$query .= "c.fieldid=sd.fieldid AND ";
		$query .= "sd.subscriberid=" . intval($subscriberid) . " AND ";
		$query .= "cl.listid=" . intval($listid);
		$query .= " ORDER BY fc.fieldorder ASC";

		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$allcustomfields[] = $row;
		}

		$subscriberinfo['CustomFields'] = $allcustomfields;

		return $subscriberinfo;
	}

	/**
	 * LoadSubscriberList
	 * Loads subscriber data based on the list specified. Also loads custom fields (if there are any).
	 *
	 * @param Int $subscriberid Subscriber to load up.
	 * @param Int $listid The list the subscriber is on.
	 * @param Boolean $returnonly Whether to only return the results or whether to set them in the class variables as well. If it's false (default), it sets the class variables. If it's true, then it only returns the values.
	 * @param Boolean $activeonly Whether to search for active only subscribers.
	 * @param Boolean $include_customfields Whether to load up custom fields at the same time as loading the subscriber or not. This is used by sending and autoresponders to possibly limit the number of queries that are run.
	 *
	 * @see LoadSubscriberListCustomFields
	 *
	 * @return Array Returns the subscribers information with custom fields etc.
	 */
	function LoadSubscriberList($subscriberid=0, $listid=0, $returnonly=false, $activeonly=false, $include_customfields=true)
	{
        if($listid === 0){
            $query = "SELECT lu.*,l.* FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l LEFT OUTER JOIN " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe lu ON (l.subscriberid=lu.subscriberid AND l.listid=lu.listid) WHERE l.subscriberid=" . intval($subscriberid);
        } else {
            $query = "SELECT lu.*,l.* FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l LEFT OUTER JOIN " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe lu ON (l.subscriberid=lu.subscriberid AND l.listid=lu.listid) WHERE l.listid=" . intval($listid) . " AND l.subscriberid=" . intval($subscriberid);
        }

		if ($activeonly) {
			$query .= " AND (unsubscribed = 0 AND bounced = 0)";
		}

		$result = $this->Db->Query($query);
		if ($result === false) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return array();
		}
		$subscriberinfo = $this->Db->Fetch($result);
        
        $this->Db->FreeResult($result);

		$customfields = array();

		if ($returnonly && empty($subscriberinfo)) {
			return array();
		}

		if ($include_customfields) {
			if (!empty($subscriberinfo)) {
				$customfields = $this->LoadSubscriberCustomFields($subscriberid, $subscriberinfo['listid']);
			}
			$subscriberinfo['CustomFields'] = $customfields;
		} else {
			$subscriberinfo['CustomFields'] = array();
		}

		$subscriberinfo['subscriberid'] = $subscriberid;

		if (!isset($subscriberinfo['confirmcode'])) {
			return array();
		}

		if (!$returnonly) {
			$this->confirmcode = $subscriberinfo['confirmcode'];
			$this->confirmip = $subscriberinfo['confirmip'];
			$this->confirmdate = $subscriberinfo['confirmdate'];
			$this->requestip = $subscriberinfo['requestip'];
			$this->requestdate = $subscriberinfo['requestdate'];
			$this->customfields = $customfields;
			$this->subscriberid = $subscriberid;
			$this->format = $subscriberinfo['format'];
			$this->formid = $subscriberinfo['formid'];
		}
		return $subscriberinfo;
	}

	/**
	 * LoadSubscriberSegment
	 * Loads subscriber data based on the segment specified. Also loads custom fields (if there are any).
	 *
	 * @param Int $subscriberid Subscriber to load up.
	 * @param Int $segmentid The segment the subscriber is on.
	 * @param Boolean $returnonly Whether to only return the results or whether to set them in the class variables as well. If it's false (default), it sets the class variables. If it's true, then it only returns the values.
	 * @param Boolean $activeonly Whether to search for active only subscribers.
	 * @param Boolean $include_customfields Whether to load up custom fields at the same time as loading the subscriber or not. This is used by sending and autoresponders to possibly limit the number of queries that are run.
	 *
	 * @return Array Returns the subscribers information with custom fields etc.
	 *
	 * @uses Subscribers_API::LoadSubscriberListCustomFields()
	 * @uses Segment_API::Load()
	 * @uses Segment_API::AppendRule()
	 * @uses Segment_API::GetSubscriberQueryString()
	 */
	function LoadSubscriberSegment($subscriberid = 0, $segmentid = 0, $returnonly = false, $activeonly = false, $include_customfields = true)
	{
		require_once dirname(__FILE__) . '/segment.php';

		$segmentAPI = new Segment_API();
		$segmentAPI->Load($segmentid);
		$segmentAPI->AppendRule(
			'AND',
		array(
				'ruleName'		=> 'subscriberid',
				'ruleOperator'	=> 'equalto',
				'ruleValues'	=> array($subscriberid)
		)
		);

		$query = $segmentAPI->GetSubscribersQueryString($activeonly);
		$query = preg_replace('/^SELECT .*? FROM/i', 'SELECT subscribers.* FROM', $query);

		$result = $this->Db->Query($query);
		if ($result === false) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return array();
		}

		$subscriberinfo = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		$customfields = array();

		if ($returnonly && empty($subscriberinfo)) {
			return array();
		}

		if ($include_customfields) {
			if (!empty($subscriberinfo)) {
				$customfields = $this->LoadSubscriberCustomFields($subscriberid, $subscriberinfo['listid']);
			}
			$subscriberinfo['CustomFields'] = $customfields;
		} else {
			$subscriberinfo['CustomFields'] = array();
		}

		$subscriberinfo['subscriberid'] = $subscriberid;

		if (!isset($subscriberinfo['confirmcode'])) {
			return array();
		}

		if (!$returnonly) {
			$this->confirmcode = $subscriberinfo['confirmcode'];
			$this->confirmip = $subscriberinfo['confirmip'];
			$this->confirmdate = $subscriberinfo['confirmdate'];
			$this->requestip = $subscriberinfo['requestip'];
			$this->requestdate = $subscriberinfo['requestdate'];
			$this->customfields = $customfields;
			$this->subscriberid = $subscriberid;
			$this->format = $subscriberinfo['format'];
			$this->formid = $subscriberinfo['formid'];
		}

		return $subscriberinfo;
	}

	/**
	 * AddToListAutoresponders
	 * This will add a subscriber to all autoresponders for a particular list.
	 * The autoresponder file handles removing duplicate subscribers so we don't have to worry about it here.
	 *
	 * @param integer $subscriberid Subscriber id to add to the autoresponder.
	 * @param integer $listid List to add them to for autoresponders.
	 *
	 * @see Jobs_Autoresponders::ActionJob
	 * @see RemoveDuplicatesInQueue
	 *
	 * @return Boolean Returns false if there is no subscriber or listid, otherwise adds them and returns true.
	 */
	function AddToListAutoresponders($subscriberid = 0, $listid = 0)
	{
		$subscriberid = intval($subscriberid);
		$listid = intval($listid);
		if ($subscriberid <= 0 || $listid <= 0) {
			return false;
		}

		// in case they have been added to the autoresponders already, we should remove them
		$query = "
			DELETE FROM [|PREFIX|]queues
			WHERE	queuetype='autoresponder'
					AND recipient={$subscriberid}
		";
		$this->Db->Query($query);

		$query = "
			INSERT INTO [|PREFIX|]queues (queueid, queuetype, ownerid, recipient, processed)

			SELECT	queueid, 'autoresponder', ownerid, {$subscriberid}, 0
			FROM	[|PREFIX|]autoresponders
			WHERE	listid={$listid}
					AND active<>0
		";
		$status = $this->Db->Query($query);
		if (!$status) {
			return false;
		}

		return true;
	}

	/**
	 * LoadSubscriberListCustomFields
	 * Loads customfield data based on the list specified.
	 * Checks whether the list actually exists and fetches custom fields from that list.
	 *
	 * @param Int $subscriberid Subscriber to load up.
	 * @param Int $listid The list the subscriber is on.
	 * @param Array $customfields An array of custom fields to load and return. This is passed in by the 'Manage Subscribers' area so we don't have to load the custom fields each time we
	 * show a subscribers custom field data. It is a multidimensional array which includes the fieldid, type, name and default value.
	 *
	 * <b>Example</b>
	 * $customfield_info = array (
	 * 	1 => array ('fieldid' => 1, 'fieldtype' => 'text', 'fieldname' => 'First Name', 'data' => 'Friend'),
	 * 	5 => array ('fieldid' => 5, 'fieldtype' => 'radio', 'fieldname' => 'Music', 'data' => ''),
	 * );
	 * The keys (1 & 5) are the fieldid's duplicated.
	 * LoadSubscriberCustomFields($subscriberid, $listid, $customfield_info);
	 *
	 * @see LoadSubscriberList
	 * @see Lists_API
	 * @see Lists_API::GetCustomFields
	 *
	 * @return Array Returns the subscribers custom field data for that particular list.
	 */
	function LoadSubscriberCustomFields($subscriberid=0, $listid=0, $customfields=array())
	{
		$subscriberid = (int)$subscriberid;
		$listid = (int)$listid;

		$fieldids = array();
		if (!empty($customfields)) {
			$fieldids = array_keys($customfields);
			// make sure they are integer fields.
			$fieldids = $this->CheckIntVars($fieldids);
		}

		require_once(dirname(__FILE__) . '/lists.php');
		$list = new Lists_API();

		// only load custom fields if there are no fieldids passed in.
		if (empty($fieldids)) {
			$customfields = $list->GetCustomFields($listid);
		}

		$fields = array();
		$fieldtypes = array();
		foreach ($customfields as $pos => $details) {
			$fields[] = $details['fieldid'];
			$fieldtypes[$details['fieldid']] = array('fieldid' => $details['fieldid'], 'fieldtype' => $details['fieldtype'], 'fieldname' => $details['name'], 'data' => '');
		}

		$allcustomfields = array();
		if (empty($fields)) {
			return $allcustomfields;
		}

		$query = "SELECT fieldid, data FROM " . SENDSTUDIO_TABLEPREFIX . "subscribers_data WHERE subscriberid=" . intval($subscriberid) . " AND fieldid IN (" . implode(',', $fields) . ")";
		$result = $this->Db->Query($query);
		$foundcustomfields = array();
		while ($row = $this->Db->Fetch($result)) {
			$row['fieldtype'] = $fieldtypes[$row['fieldid']]['fieldtype'];
			$cur = sizeof($allcustomfields);
			$allcustomfields[$cur] = $row;
			$allcustomfields[$cur]['fieldname'] = $fieldtypes[$row['fieldid']]['fieldname'];
			$foundcustomfields[] = $row['fieldid'];
		}

		$notfoundcustomfields = array_diff($fields, $foundcustomfields);
		foreach ($notfoundcustomfields as $fieldid) {
			$allcustomfields[] = $fieldtypes[$fieldid];
		}

		unset($fields);
		unset($fieldtypes);
		return $allcustomfields;
	}

	/**
	 * GetAllSubscriberCustomFields
	 * Gets all subscriber custom fields for a particular list. This is used by autoresponders and sending so it only has to load all custom fields once per run.
	 *
	 * @param Array $listids An array of listid's that the custom fields are attached to. If this is not an array, it is turned into one for easy processing.
	 * @param Array $limit_fields An array of field names to fetch custom field information for. These are strings (eg 'Name') and are the placeholders in the newsletter/autoresponder that are going to be replaced.
	 * @param Array $subscriberids An array of subscriberids to fetch custom field information for.
	 * @param Array $custom_fieldids An array of custom field IDs that should additionally be added into the result
	 *
	 * @return Array Returns the subscribers custom field data for the lists & fields passed in. It will also contain an 'unclaimed' entry which will have default values in it if applicable. This is so custom field replacement can use the default if the specific subscriber doesn't have data.
	 */
	function GetAllSubscriberCustomFields($listids=array(), $limit_fields=array(), $subscriberids=array(), $custom_fieldids=array())
	{
		/**
		 * Used variables
		 */
		$query;
		$return_fields;
		/**
		 * -----
		 */



		/**
		 * Sanitize input
		 */
		if (!is_array($listids)) {
			$listids = array($listids);
		}

		$listids = $this->CheckIntVars($listids);

		if (!is_array($limit_fields)) {
			$limit_fields = array($limit_fields);
		}

		if (!is_array($subscriberids)) {
			$subscriberids = array($subscriberids);
		}

		$subscriberids = $this->CheckIntVars($subscriberids);

		if (!is_array($custom_fieldids)) {
			$custom_fieldids = array($custom_fieldids);
		}

		$custom_fieldids = $this->CheckIntVars($custom_fieldids);
		/**
		 * -----
		 */


		/**
		 * Check if the parameters are correct:
		 * - $limit_fields and $custom_fields cannot both be empty. Either or both have to have some values
		 * - $listids must not be empty
		 */
		if ((empty($limit_fields) && empty($custom_fieldids)) || empty($listids)) {
			return array();
		}





		/**
		 * Process the "limit_fields" parameter...
		 * If the array item contains a string, it should be "qoted", if it contains numeric, move it to $custom_fields variable
		 */
		$tempNewLimit = array();
		foreach ($limit_fields as $fieldName) {
			if (is_numeric($fieldName)) {
				array_push($custom_fieldids, intval($fieldName));
				continue;
			}

			array_push($tempNewLimit, "'" . $this->Db->Quote($fieldName) . "'");
		}

		$limit_fields = $tempNewLimit;
		unset($tempNewLimit);
		/**
		 * -----
		 */



		/**
		 * Construct query
		 */
		$tempTablePrefix = SENDSTUDIO_TABLEPREFIX;
		$tempImplodedListID = implode(',', $listids);
		$tempImplodedSubscriberID = implode(',', $subscriberids);
		$tempSQLCondition = '1';

		// Make sure that at least  1 list ID is specified
		if (empty($tempImplodedListID)) {
			$tempImplodedListID = 0;
		}

		// Make sure that at least 1 subscriber ID is specified
		if (empty($tempImplodedSubscriberID)) {
			$tempImplodedSubscriberID = 0;
		}

		/**
		 * Process the SQL condition
		 */
		$tempConditionArray = array();

		if (count($limit_fields) != 0) {
			array_push($tempConditionArray, ('c.name IN (' . implode(',', $limit_fields) . ')'));
		}

		if (count($custom_fieldids) != 0) {
			array_push($tempConditionArray, ('c.fieldid IN (' . implode(',', $custom_fieldids)) . ')');
		}

		if (count($tempConditionArray) != 0) {
			$tempSQLCondition = '(' . implode(' OR ', $tempConditionArray) . ')';
		}

		unset($tempConditionArray);
		/**
		 * -----
		 */


		$query = trim("
				(
					SELECT
						c.fieldid AS fieldid,
						c.name AS fieldname,
						c.fieldtype AS fieldtype,
						c.fieldsettings AS fieldsettings,
						d.subscriberid AS subscriberid,
						d.data AS data
					FROM
					{$tempTablePrefix}customfields AS c
							JOIN {$tempTablePrefix}customfield_lists AS cl
								ON (
									c.fieldid = cl.fieldid
									AND cl.listid IN ($tempImplodedListID)
								)
							JOIN {$tempTablePrefix}list_subscribers AS ls
								ON (
									cl.listid = ls.listid
									AND ls.listid IN ($tempImplodedListID)
									AND ls.subscriberid IN ({$tempImplodedSubscriberID})
								)
							JOIN {$tempTablePrefix}subscribers_data AS d
								ON (
									ls.subscriberid = d.subscriberid
									AND cl.fieldid = d.fieldid
									AND c.fieldid = d.fieldid
								)
					WHERE
					{$tempSQLCondition}
						AND d.subscriberid IN ({$tempImplodedSubscriberID})
				) UNION (
					SELECT
						c.fieldid AS fieldid,
						c.name AS fieldname,
						c.fieldtype AS fieldtype,
						c.fieldsettings AS fieldsettings,
						ls.subscriberid AS subscriberid,
						'' AS data
					FROM
					{$tempTablePrefix}customfields AS c
							JOIN {$tempTablePrefix}customfield_lists AS cl
								ON (
									c.fieldid = cl.fieldid
									AND cl.listid IN ($tempImplodedListID)
								)
							JOIN {$tempTablePrefix}list_subscribers ls
								ON (
									cl.listid = ls.listid
									AND ls.listid IN ($tempImplodedListID)
									AND ls.subscriberid IN ({$tempImplodedSubscriberID})
								)
							LEFT JOIN {$tempTablePrefix}subscribers_data d
								ON (
									ls.subscriberid = d.subscriberid
									AND c.fieldid = d.fieldid
								)
					WHERE
					{$tempSQLCondition}
						AND d.subscriberid IS null
				)
			");

					unset($tempTablePrefix);
					unset($tempSQLCondition);
					unset($tempImplodedListID);
					unset($tempImplodedSubscriberID);
					/**
					 * -----
					 */


					/**
					 * Query the database
					 */
					$tempResult = $this->Db->Query($query);
					if ($tempResult == false) {
						list($msg, $errno) = $this->Db->GetError();
						trigger_error($msg, $errno);
						return false;
					}

					$return_fields = array();
					while ($tempRow = $this->Db->Fetch($tempResult)) {
						if ($tempRow['fieldtype'] != 'text') {
							$tempRow['defaultvalue'] = null;
						}

						if (!in_array($tempRow['subscriberid'], array_keys($return_fields))) {
							$return_fields[$tempRow['subscriberid']] = array();
						}

						$return_fields[$tempRow['subscriberid']][] = $tempRow;
					}

					$this->Db->FreeResult($tempResult);
					/**
					 * -----
					 */

					return $return_fields;
	}

	/**
	 * GetCustomFieldSettings
	 * Goes through this subscribers custom fields and looks for specific field (based on the id).
	 *
	 * @param Int $fieldid Field to check for.
	 * @param Boolean $allinfo Whether to return just the data or both the data, fieldtype and id. Being off (default) only returns the data.
	 *
	 * @see customfields
	 *
	 * @return Mixed Returns false if it can't find the field. Returns the data if that's all you want. Returns an array of the data, fieldtype and id if you specify allinfo.
	 */
	function GetCustomFieldSettings($fieldid=0, $allinfo=false)
	{
		if (!$fieldid) {
			return false;
		}

		foreach ($this->customfields as $pos => $details) {
			if ($fieldid != $details['fieldid']) {
				continue;
			}
			if (!$allinfo) {
				return $details['data'];
			}

			return $details;
		}
		return false;
	}

	/**
	 * Save Subscriber CustomFields
	 * Saves custom field information for a particular subscriber, particular list and particular field.
	 *
	 * NOTE:
	 * - Any old custom field data will be deleted.
	 * - NULL data values will not be saved to the database.
	 *
	 * @param array|integer $subscriberids ID of the subscribers whose data need to be updated.
	 * @param integer $fieldid The ID of Custom field you are saving for.
	 * @param mixed $data The actual custom field data. If this is an array, it will be serialized up before saving.
	 *
	 * @return boolean Returns TRUE if successful, FALSE otherwise.
	 */
	function SaveSubscriberCustomField($subscriberids, $fieldid, $data = '')
	{
		if (!is_array($subscriberids)) {
			$subscriberids = array($subscriberids);
		}

		$subscriberids = $this->CheckIntVars($subscriberids);
		$fieldid = intval($fieldid);

		if (empty($subscriberids) || $fieldid <= 0) {
			return false;
		}
		if (is_array($data)) {
			// if it's a date field, store it a little differently.
			// This makes searching a lot easier.
			if (isset($data['dd']) && isset($data['mm']) && isset($data['yy'])) {
				$data = str_pad($data['dd'], 2, '0', STR_PAD_LEFT) . '/' . str_pad($data['mm'], 2, '0', STR_PAD_LEFT) . '/' . $data['yy'];
				if ($data == '//' || $data == '00/00/' || empty($data)) {
					// Don't save empty dates.
					$data = null;
				}
			} else {
				$data = serialize($data);
			}
		}

		$this->Db->StartTransaction();

		$query = "DELETE FROM [|PREFIX|]subscribers_data WHERE subscriberid IN (" . implode(',', $subscriberids) . ") AND fieldid={$fieldid}";
		$result = $this->Db->Query($query);

		if (empty($data)) {
			$this->Db->CommitTransaction();
			return true; // Skip insert if data is empty.
		}

		$data = $this->Db->Quote($data);

		foreach ($subscriberids as $p => $subscriberid) {
			$query = "INSERT INTO [|PREFIX|]subscribers_data (subscriberid, fieldid, data) VALUES ({$subscriberid}, {$fieldid}, '{$data}')";
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
	 * AddToList
	 * Adds a subscriber to a list. Checks whether the list actually exists. If it doesn't, returns an error.
	 *
	 * @param String $emailaddress Subscriber address to add to the list.
	 * @param Mixed $listid The list to add the subcriber to. This can be a list name or a list id.
	 * @param Boolean $add_to_autoresponders Whether to add the subscriber to the lists' autoresponders or not.
	 * @param Boolean $skip_listcheck Whether to skip checking the list or not. This is useful if you've already processed the lists to make sure they are ok.
	 *
	 * @see GenerateConfirmCode
	 * @see Lists_API
	 * @see Lists_API::Find
	 *
	 * @return Boolean Returns false if there is an invalid subscriber or list id, or if the list doesn't really exist. If it works, then it returns the new subscriber id from the database.
	 */
	function AddToList($emailaddress='', $listid=null, $add_to_autoresponders=true, $skip_listcheck=false)
	{

		$emailaddress = trim($emailaddress);

		if (!$emailaddress || is_null($listid)) {
			return false;
		}

		if ($skip_listcheck) {
			$real_listid= (int)$listid;
			if ($real_listid <= 0) {
				return false;
			}
		} else {
			require_once(dirname(__FILE__) . '/lists.php');
			$list = new Lists_API();
			$real_listid = $list->Find($listid);
			if ($real_listid <= 0) {
				return false;
			}
		}

		if ($this->confirmed) {
			$confirmdate = $this->GetServerTime();
			if ($this->confirmdate > 0) {
				$confirmdate = $this->confirmdate;
			}
		} else {
			$confirmdate = 0;
		}

		$requestdate = $this->GetServerTime();
		if ($this->requestdate > 0) {
			$requestdate = $this->requestdate;
		}

		$this->requestdate = $requestdate;
		$this->confirmdate = $confirmdate;

		if ($confirmdate) {
			$this->subscribedate = $confirmdate;
		} else {
			$this->subscribedate = $requestdate;
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscribers(listid, emailaddress, format, confirmed, confirmcode, subscribedate, bounced, unsubscribed, requestdate, requestip, confirmdate, confirmip, formid, domainname) VALUES ('" . $real_listid . "', '" . $this->Db->Quote($emailaddress) . "', '" . $this->Db->Quote($this->format) . "', '" . $this->Db->Quote((int)$this->confirmed) . "', '" . $this->Db->Quote($this->GenerateConfirmCode()) . "', " . $this->subscribedate . ", '0', '0', " . $requestdate . ", '" . $this->Db->Quote($this->requestip) . "', " . $confirmdate . ", '" . $this->Db->Quote($this->confirmip) . "', '" . (int)$this->formid . "', ";

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query .= "SUBSTRING('" . $this->Db->Quote($emailaddress) . "' FROM LOCATE('@', '" . $this->Db->Quote($emailaddress) . "'))";
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$query .= "SUBSTRING('" . $this->Db->Quote($emailaddress) . "' FROM POSITION('@' IN '" . $this->Db->Quote($emailaddress) . "'))";
		}
		$query .= ")";

		$result = $this->Db->Query($query);

		if (!$result) {
			return false;
		}

		$subscriberid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'list_subscribers_sequence');

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET subscribecount=subscribecount + 1 WHERE listid=" . intval($real_listid);
		$result = $this->Db->Query($query);

		if ($add_to_autoresponders) {
			$this->AddToListAutoresponders($subscriberid, intval($real_listid));
		}

		return $subscriberid;
	}

	/**
	 * IsSubscriberHasOpenedNewsletters
	 * A function to check if particular subscriber has opened particular newsletters
	 *
	 * @param Int $emailAddress Email address to check for
	 * @param Array $newsletterIds Newsletter id to check if the subscriber has opened it
	 * @param Boolean $opened The checking command if the check newsletters has or hasn't been opened
	 * @param Int $subscriberId Subscriber Id to check for, if this is passed to the function
	 *
	 * @return Boolean Return false if the condition is false. Otherwise return true.
	 *
	 */
	function IsSubscriberHasOpenedNewsletters($emailAddress='', $newsletterIds = array(), $subscriberId=0) {
		$newsletterIds = (is_array($newsletterIds)) ? $newsletterIds : array($newsletterIds);
		$newsletterIds = $this->CheckIntVars($newsletterIds);

		$additionalQuery = array();
		if ($subscriberId) {
			$additionalQuery[] = " ls.subscriberid = '$subscriberId' ";
		}

		if ($emailAddress) {
			$additionalQuery[] = " ls.emailaddress = '$emailAddress' ";
		}

		$additionalQuery = (sizeof($additionalQuery)) ? ' AND ' . implode(' AND ', $additionalQuery) : '';

		$query = " SELECT DISTINCT ls.emailaddress";
		$query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens eo";
		$query .= " JOIN " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters sn ON (eo.statid = sn.statid AND sn.newsletterid IN ('" . implode("'", $newsletterIds) . "'))";
		$query .= " JOIN " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls ON (ls.subscriberid = eo.subscriberid) $additionalQuery";

		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			return false;
		}
		return true;
	}

	/**
	 * IsSubscriberOnList
	 * Checks whether a subscriber is on a particular list based on their email address or subscriberid and whether you are checking only for active subscribers.
	 *
	 * @param String $emailaddress Email address to check for.
	 * @param Array $listids Lists to check on. If this is not an array, it's turned in to one for easy checking.
	 * @param Int $subscriberid Subscriber id. This can be used instead of the email address.
	 * @param Boolean $activeonly Whether to only check for active subscribers or not.  By default this is false - so it will not restrict searching.
	 * @param Boolean $not_bounced Whether to only check for non-bounced subscribers or not. By default this is false - so it will not restrict searching.
	 * @param Boolean $return_listid Whether to return the listid as well as the subscriber id. By default this is false, so it will only return the subscriberid. The bounce processing functions changes this to true, so it returns the list and the subscriber id's.
	 *
	 * @return Int|False Returns false if there is no such subscriber. Otherwise returns the subscriber id.
	 */
	function IsSubscriberOnList($emailaddress='', $listids=array(), $subscriberid=0, $activeonly=false, $not_bounced=false, $return_listid=false)
	{
		if (!is_array($listids)) {
			$listids = array($listids);
		}

		$listids = $this->CheckIntVars($listids);
		if (sizeof($listids) == 0) {
			return false;
		}

		$query = "SELECT subscriberid";
		if ($return_listid) {
			$query .= ", listid";
		}

		$query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid IN (" . implode(',', $listids) . ")";

		if ($emailaddress) {
			$emailaddress = trim($emailaddress);
			$op = $this->GetCIOp();
			$query .= " AND emailaddress {$op} '" . $this->Db->Quote($emailaddress) . "'";
		}

		if ($subscriberid) {
			$query .= " AND subscriberid=" . intval($subscriberid);
		}

		if ($activeonly) {
			$query .= " AND confirmed='1' AND unsubscribed=0";
		}

		if ($not_bounced) {
			$query .= " AND unsubscribed=0 AND bounced=0";
		}

		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			return false;
		}

		if ($return_listid) {
			return $row;
		} else {
			return (int)$row['subscriberid'];
		}
	}

	/**
	 * UpdateEmailAddress
	 * Updates an email address for a particular subscriber id. If the email address is the same as it was previously, this will return true straight away. Otherwise it will update the database and change the emailaddress class variable.
	 *
	 * @param Array $subscriberids An array of subscriberid's to update. We need to pass in an array for modify details forms - that is, so we can change it across multiple lists at once. If this is not already an array, it will be turned into one.
	 * @param String $emailaddress Email address to update to. If this isn't specified, uses 'this' email address.
	 *
	 * @see emailaddress
	 *
	 * @return Boolean Returns true if it worked, false otherwise.
	 */
	function UpdateEmailAddress($subscriberids=array(), $emailaddress='')
	{
		if (!is_array($subscriberids)) {
			$subscriberids = array($subscriberids);
		}

		$subscriberids = $this->CheckIntVars($subscriberids);

		if (empty($subscriberids)) {
			return false;
		}

		$emailaddress = trim($emailaddress);
		$this->emailaddress = trim($this->emailaddress);

		if (($emailaddress == $this->emailaddress) && $emailaddress != '') {
			return true;
		}

		/**
		 * Only set this after the above check otherwise
		 * emailaddress always === this->emailaddress
		 * and thus it always returns true
		 * which means you can never change your email address
		 */
		if (!$emailaddress) {
			$emailaddress = $this->emailaddress;
		}

		foreach ($subscriberids as $p => $subscriberid) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET emailaddress='" . $this->Db->Quote($emailaddress) . "', domainname=";

			if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
				$query .= "SUBSTRING('" . $this->Db->Quote($emailaddress) . "' FROM LOCATE('@', '" . $this->Db->Quote($emailaddress) . "'))";
			}

			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$query .= "SUBSTRING('" . $this->Db->Quote($emailaddress) . "' FROM POSITION('@' IN '" . $this->Db->Quote($emailaddress) . "'))";
			}

			$query .= " WHERE subscriberid=" . intval($subscriberid);
			$result = $this->Db->Query($query);
		}
		return true;
	}

	/**
	 * UpdateList
	 * Updates list information for a particular subscriber. Checks whether a list exists or not first before updating information. It updates the format, confirm status, confirm code, confirm date.
	 *
	 * @param Int $subscriberid Subscriber to update.
	 * @param Int $listid List to update their information on.
	 *
	 * @see confirmed
	 * @see format
	 * @see GenerateConfirmCode
	 * @see Lists_API
	 * @see Lists_API::Find
	 *
	 * @return Boolean Returns true if it worked, false if the list doesn't exist or if it didn't work.
	 */
	function UpdateList($subscriberid=0, $listid=0)
	{
		$subscriberid = (int)$subscriberid;
		$listid = (int)$listid;

		if ($subscriberid <= 0 || !$listid) {
			return false;
		}

		require_once(dirname(__FILE__) . '/lists.php');
		$list = new Lists_API();
		$real_listid = $list->Find($listid);
		if ($real_listid['listid'] <= 0) {
			return false;
		}

		if ($this->confirmed == '0') {
			$this->confirmdate = 0;
		} elseif ($this->confirmdate == 0) {
			$this->confirmdate = $this->GetServerTime();
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET format='" . $this->Db->Quote($this->format) . "', confirmed='" . $this->Db->Quote((int)$this->confirmed) . "', confirmdate='" . $this->Db->Quote($this->confirmdate) . "', confirmip = '" . $this->Db->Quote($this->confirmip) . "', requestip = '" . $this->Db->Quote($this->requestip) . "'";

		if ((int)$this->subscribedate > 0) {
			$query .= ", subscribedate='" . $this->Db->Quote($this->subscribedate) . "'";
		}

		$query .= " WHERE listid=" . intval($listid) . " AND subscriberid=" . intval($subscriberid);

		$result = $this->Db->Query($query);
		return $result;
	}

	/**
	 * UpdateSubscriberIP
	 * Updates a subscriber's ip address based on the email address and listid passed in.
	 *
	 * The function checks if more than one subscriber will be affected. If there are none affected or more than one affected (not sure how but just in case), then the query undoes the changes (rolls back the transaction) and returns false.
	 * Only if the query works and changes one record will it return true.
	 *
	 * If ip tracking is disabled in sendstudio, this will also return false.
	 *
	 * If the subscriber is confirmed, then that ip will be updated. If the subscriber is not confirmed, it will update that ip address instead.
	 *
	 * @param String $emailaddress The email address of the subscriber you want to update
	 * @param Int $listid The list the subscriber is on
	 * @param String $ipaddress The ip address the person is using to update their details
	 *
	 * @return Boolean Returns false if there is no email, list or no ip address is supplied. Also returns false if the query affects more than one subscriber. If the query only affects one subscriber, then this will return true.
	 */
	function UpdateSubscriberIP($emailaddress='', $listid=0, $ipaddress='')
	{
		if (!$emailaddress || !$ipaddress) {
			return false;
		}

		$listid = (int)$listid;
		if ($listid <= 0) {
			return false;
		}

		// if ip tracking is disabled, return false.
		if (!SENDSTUDIO_IPTRACKING) {
			return false;
		}

		$this->Db->StartTransaction();

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET ";
		$query .= " requestip = (CASE confirmed WHEN '0' THEN '" . $this->Db->Quote($ipaddress) . "' ELSE requestip END), ";
		$query .= " confirmip = (CASE confirmed WHEN '1' THEN '" . $this->Db->Quote($ipaddress) . "' ELSE confirmip END)";
		$query .= " WHERE listid = " . $listid . " AND emailaddress='" . $this->Db->Quote($emailaddress) . "'";

		$result = $this->Db->Query($query);
		if (!$result) {
			$this->Db->RollbackTransaction();
			return false;
		}

		if ($this->Db->NumAffected($result) == 1) {
			$this->Db->CommitTransaction();
			return true;
		}

		$this->Db->RollbackTransaction();
		return false;
	}

	/**
	 * IsDuplicate
	 * Checks whether an email address is already on a particular list. It can ignore a particular subscriber based on their id. This is handy if you want to change other details but not your email address, otherwise this would return true even though you're not changing the email. This also helps check if you are already subscribed using a different email address apart from the one being checked (eg family members signing up for the same newsletter).
	 *
	 * @param String $emailaddress Email Address to check.
	 * @param Int $listid List to check for duplicates on.
	 * @param Int $ignore_subscriberid This excludes the 'subscriberid' mentioned. This allows you to update an email address for a subscriber on a list and make sure it doesn't return the existing (current) subscriber.
	 *
	 * @return Mixed Returns the duplicate subscriberid if there is a duplicate. Returns false if there isn't one.
	 */
	function IsDuplicate($emailaddress='', $listid=0, $ignore_subscriberid=0)
	{
		$emailaddress = trim($emailaddress);

		if ($emailaddress == '' || $listid <= 0) {
			return true;
		}

		$op = $this->GetCIOp();
		$query = "SELECT subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE emailaddress {$op} '" . $this->Db->Quote($emailaddress) . "' AND listid=" . intval($listid);

		if ($ignore_subscriberid) {
			$query .= " AND subscriberid != '" . $this->Db->Quote($ignore_subscriberid) . "'";
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			return true;
		}

		$subscriber = $this->Db->FetchOne($result, 'subscriberid');
		if ($subscriber > 0) {
			return $subscriber;
		}

		return false;
	}

	/**
	 * IsUnSubscriber
	 * Checks whether an email address is an 'unsubscriber' - they have unsubscribed from a list.
	 *
	 * @param String $emailaddress Email Address to check.
	 * @param Int $listid List to check for.
	 * @param Int $subscriberid Subscriber id to check.
	 *
	 * @return Int|False Returns the unsubscribed id if there is one. Returns false if there isn't one.
	 */
	function IsUnSubscriber($emailaddress='', $listid=0, $subscriberid=0)
	{
		$emailaddress = trim($emailaddress);

		if ((!$emailaddress && $subscriberid <= 0) || $listid <= 0) {
			return false;
		}

        

		$query = "SELECT subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid=" . intval($listid) . " AND unsubscribed > 0";

		if ($emailaddress) {
			$op = $this->GetCIOp();
			$query .= " AND emailaddress {$op} '" . $this->Db->Quote($emailaddress) . "'";
		}

		if ($subscriberid) {
			$query .= " AND subscriberid=" . intval($subscriberid);
		}

		$result = $this->Db->Query($query);
		if (!$result) {
            trigger_error(mysql_error());
			return false;
		}

		$subscriber = $this->Db->FetchOne($result, 'subscriberid');
		if ($subscriber > 0) {
			return $subscriber;
		}

		return false;
	}

	/**
	 * IsBounceSubscriber
	 * Checks whether an email address has 'bounced' on a list.
	 *
	 * @param String $emailaddress Email Address to check.
	 * @param Int $listid List to check for.
	 *
	 * @return Int|False Returns the bounced id if there is one. Returns false if there isn't one.
	 */
	function IsBounceSubscriber($emailaddress='', $listid=0)
	{
		$emailaddress = trim($emailaddress);

		if ($emailaddress == '' || $listid <= 0) {
			return false;
		}

		$op = $this->GetCIOp();
		$query = "SELECT subscriberid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid=" . intval($listid) . " AND emailaddress {$op} '" . $this->Db->Quote($emailaddress) . "' AND bounced > 0";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$subscriber = $this->Db->FetchOne($result, 'subscriberid');
		if ($subscriber > 0) {
			return $subscriber;
		}

		return false;
	}

	/**
	 * IsBannedSubscriber
	 * Checks whether an email address is banned or not. Checks for a specific list or 'g'lobally.
	 *
	 * @param String $emailaddress Email Address to check.
	 * @param Array $listids An array of listids to check and make sure they are not banned from. This will be run through CheckIntVars to make sure they are all integers, then it will add the global ban list to the array of id's to check.
	 * @param Boolean $return_ids Whether to return the listid's the person is banned from subscribing or not. If this is false, an array is returned with a status (true/false). If this is true, an array will be returned with the listid's the person is banned from.
	 *
	 * @return Mixed If return_ids is false, this will return an array with a status (false if they are not banned). If return_ids is true, this will return a multidimensional array with the 'list' (listid) and the listname they are banned from joining.
	 */
	function IsBannedSubscriber($emailaddress='', $listids=array(), $return_ids=false)
	{
		$emailaddress = trim($emailaddress);

		if ($emailaddress == '') {
			return array(true, 'No email address supplied');
		}

		if (!is_array($listids)) {
			$listids = array($listids);
		}

		$listids = $this->CheckIntVars($listids);

		$listids[] = 'g';

		$domain_parts = explode('@', $emailaddress);
		if (isset($domain_parts[1])) {
			$domain = $domain_parts[1];
		} else {
			$domain = $emailaddress;
		}

		/**
		 * Instead of joining the lists & banned_emails tables together, we deal with them separately.
		 * Postgresql 8.3 doesn't allow this join because the 'banned_emails' table has a list with a column type of 'varchar',
		 * where-as the lists table is an int.
		 * Previously we were able to take advantage of that mismatch but we can't any longer.
		 */
		$query = "SELECT banid, list FROM " . SENDSTUDIO_TABLEPREFIX . "banned_emails be WHERE emailaddress IN ('" . $this->Db->Quote($emailaddress) . "', '" . $this->Db->Quote($domain) . "', '@" . $this->Db->Quote($domain) . "') AND list IN ('" . implode('\',\'', $listids) . "')";

		if (!$return_ids) {
			$query .= " LIMIT 1";
			$result = $this->Db->Query($query);
			if (!$result) {
				trigger_error(mysql_error());
				return array(true, null);
			}
			$row = $this->Db->FetchOne($result);
			if((int)$row['banid'] > 0){
				return array(true, null);
			} else {
				return array(false, null);
			}
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			trigger_error(mysql_error());
			return array(true, null);
		}

		$banid = 0;

		$banned_lists = array();
		while ($row = $this->Db->Fetch($result)) {
			$banid = $row['banid'];
			$banned_lists[] = (int)$row['list'];
		}

		/**
		 * If we're not returning ids, but we haven't found any bans
		 * return an empty array.
		 */
		if (empty($banned_lists) || $banid === 0) {
			return array();
		}
		$all_banned_lists = array();
		
		if(count($banned_lists) == 1 && $banned_lists[0] == 0){
			$all_banned_lists[] = array('list' => 'g', 'listname' => 'global');
			return $all_banned_lists;
		}

		
		$query = "SELECT listid, name FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE listid IN (" . implode(',', $banned_lists) . ")";
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$all_banned_lists[] = array('list' => $row['listid'], 'listname' => $row['name']);
		}
		return $all_banned_lists;
	}

	/**
	 * ValidEmail
	 * This checks whether an email address is valid or not using a series of checks and general regular expressions.
	 *
	 * @param String $email Email address to check.
	 *
	 * @return Boolean Returns true if the email address has correct syntax, otherwise false.
	 */
	function ValidEmail($email=false)
	{
		$email = trim($email);

		// If the email is empty it can't be valid
		if (empty($email)) {
			return false;
		}

		// Email address is too long
		if (strlen($email) > 256) {
			return false;
		}

		// If the email doesnt have exactle 1 @ it isnt valid
		if (substr_count($email, '@') != 1) {
			return false;
		}

		// double check there are no double dots in the address anywhere.
		if (substr_count($email, '..') > 0) {
			return false;
		}

		$matches = array();
		$local_matches = array();
		preg_match(':^([^@]+)@([a-zA-Z0-9\-\[][a-zA-Z0-9\-\.\]]{0,254}[^\.])$:', $email, $matches);

		if (count($matches) != 3) {
			return false;
		}

		$local = $matches[1];
		$domain = $matches[2];

		// If the local part has a space but isnt inside quotes its invalid
		if (strpos($local, ' ') && (substr($local, 0, 1) != '"' || substr($local, -1, 1) != '"')) {
			return false;
		}

		// If there are not exactly 0 and 2 quotes
		if (substr_count($local, '"') != 0 && substr_count($local, '"') != 2) {
			return false;
		}

		// if the local part starts with a dot (.)
		if (substr($local, 0, 1) == '.' || substr($local, -1, 1) == '.') {
			return false;
		}

		// If the local string doesnt start and end with quotes
		if ((strpos($local, '"') || strpos($local, ' ')) && (substr($local, 0, 1) != '"' || substr($local, -1, 1) != '"')) {
			return false;
		}

		preg_match(':^([\ \"\w\!\#\$\%\&\'\*\+\-\/\=\?\^\_\`\{\|\}\~\.]{1,64}$):', $local, $local_matches);

		if (empty($local_matches)) {
			return false;
		}

		// if the domain has a [ at the start or ] at the end, it'll be an ip address.
		// which means we do extra checks.
		if (substr($domain, 0, 1) == '[' || substr($domain, -1, 1) == ']') {
			preg_match(':^(\[(\d{2,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\])$:', $domain, $ip_matches);

			// there should be 6 matches if it's a valid ip address email.
			// the first two are the domain name (one for the original text, the second for the matched text)
			// the next 4 are the numbers (a, b, c, d).
			if (count($ip_matches) != 6) {
				return false;
			}

			// make sure each part of the ip address is between 0 and 255.
			foreach (array(2,3,4,5) as $match_id) {
				if ($ip_matches[$match_id] < 0 || $ip_matches[$match_id] > 255) {
					return false;
				}
			}
		}

		// Check the domain has at least 1 dot in it
		if (strpos($domain, '.') !== false) {
			return true;
		}

		return false;

	}

	/**
	 * AddBannedSubscriber
	 * Adds a subscriber to the 'banned' list.
	 *
	 * @param String $emailaddress Email Address to add to the banned list. This can either be a specific email address or a domain name.
	 * @param Mixed $listid List to ban them from. This can either be an integer for a specific list or 'g' for the global list.
	 *
	 * @see IsBannedSubscriber
	 *
	 * @return Array Returns a status(true/false) whether they were added to the banned list and why.
	 */
	function AddBannedSubscriber($emailaddress='', $listid=0)
	{
		$emailaddress = trim($emailaddress);

		if ($emailaddress == '') {
			return array(false, 'No email address supplied');
		}

		if (!is_numeric($listid)) {
			$listid = 'g';
		}

		list($isbanned, $msg) = $this->IsBannedSubscriber($emailaddress, $listid, false);
		if ($isbanned) {
			return array(false, 'Already in the suppression list');
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "banned_emails (emailaddress, list, bandate) VALUES ('" . $this->Db->Quote($emailaddress) . "', '" . $this->Db->Quote($listid) . "',  " . $this->GetServerTime() . ")";
		$result = $this->Db->Query($query);
		if ($result) {
			return array(true, false);
		}

		return array(false, 'Bad Query');
	}

	/**
	 * RemoveBannedSubscriber
	 * Remove a subscriber from the 'banned' list.
	 *
	 * @param Int $banid Ban to remove from the list.
	 * @param Mixed $listid List to ban them from. This can either be an integer (listid) or 'g' for the global list.
	 *
	 * @return Array Returns a status(true/false) whether they were removed from the banned list and why.
	 */
	function RemoveBannedSubscriber($banid=0, $listid=0)
	{
		$banid = (int)$banid;
		if ($banid <= 0) {
			return array(false, 'No ban id supplied');
		}

		if (!is_numeric($listid)) {
			$listid = 'g';
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "banned_emails WHERE banid='" . $this->Db->Quote($banid) . "' AND list='" . $this->Db->Quote($listid) . "'";
		$result = $this->Db->Query($query);
		if ($result) {
			return array(true, false);
		}

		return array(false, 'Bad Query');
	}

	/**
	 * DeleteSubscriber
	 * Deletes a subscriber and their information from a particular list. If you specify an email address, it will use that. If there is a subscriber id, it will use that instead. If you specify an email address, it will verify they are a subscriber before going any further. It will also fix up subscribe/unsubscribe counts if applicable.
	 *
	 * @param String $emailaddress Email Address to delete.
	 * @param Int $listid List to delete them off.
	 * @param Int $subscriberid Subscriberid to delete. This is used if the email address is empty.
	 *
	 * @see IsSubscriberOnList
	 * @see Subscribers_Manage::DeleteSubscribers
	 *
	 * @return Array Returns a status (success,failure) and a reason why.
	 */
	function DeleteSubscriber($emailaddress='', $listid=0, $subscriberid=0)
	{
		$emailaddress = trim($emailaddress);
		$subscriberid = intval($subscriberid);

		if ($emailaddress == '' && $subscriberid <= 0) {
			return array(false, 'No email address or subscriber id');
		}

		if ($subscriberid == 0 && $emailaddress != '') {
			$subscriberid = $this->IsSubscriberOnList($emailaddress, $listid);
			if (!$subscriberid) {
				return array(false, sprintf(GetLang('Subscriber_NotSubscribed'), $emailaddress));
			}
		}

		$query = "SELECT unsubscribed, bounced, listid FROM [|PREFIX|]list_subscribers WHERE subscriberid={$subscriberid}";

		if ($listid) {
			$query .= " AND listid=" . intval($listid);
		}

		$unsubscribe_result = $this->Db->Query($query);
		if (!$unsubscribe_result) {
			trigger_error('Subscribers_API::DeleteSubscribers -- Unable to query database -- ' . $this->Db->Error(), E_USER_NOTICE);
			return array(false, 'Unable to query the database');
		}

		$unsubscribe_info = $this->Db->Fetch($unsubscribe_result);

		$this->Db->FreeResult($unsubscribe_result);

		$unsub_date = $unsubscribe_info['unsubscribed'];
		$bounce_date = $unsubscribe_info['bounced'];
		$listid = intval($unsubscribe_info['listid']);

		if (!$listid) {
			return array(false, sprintf(GetLang('Subscriber_NotSubscribed'), $emailaddress));
		}

		// ----- Delete data from database
		// Queries to be executed (the query will be executed in order they are defined)
		$queries = array();

		// if they were previously unsubscribed, we need to fix up the list counts.
		if ($unsub_date > 0) {
			$queries[] = "UPDATE [|PREFIX|]lists SET unsubscribecount = unsubscribecount - 1 WHERE listid={$listid}";
			$queries[] = "DELETE FROM [|PREFIX|]list_subscribers_unsubscribe WHERE subscriberid={$subscriberid}";
		} elseif ($bounce_date > 0) {
			$queries[] = "UPDATE [|PREFIX|]lists SET bouncecount = bouncecount - 1 WHERE listid={$listid}";
			$queries[] = "DELETE FROM [|PREFIX|]list_subscriber_bounces WHERE subscriberid={$subscriberid}";
		} else {
			$queries[] = "UPDATE [|PREFIX|]lists SET subscribecount = subscribecount - 1 WHERE listid={$listid}";
		}

		$queries[] = "DELETE FROM [|PREFIX|]subscribers_data WHERE subscriberid={$subscriberid}";
		$queries[] = "DELETE FROM [|PREFIX|]queues WHERE recipient={$subscriberid}";
		$queries[] = "DELETE FROM [|PREFIX|]queues_unsent WHERE recipient={$subscriberid}";
		$queries[] = "DELETE FROM [|PREFIX|]list_subscriber_events WHERE subscriberid={$subscriberid}";
		$queries[] = "DELETE FROM [|PREFIX|]list_subscribers WHERE subscriberid={$subscriberid} AND listid={$listid}";

		$this->Db->StartTransaction();
		foreach ($queries as $query) {
			$status = $this->Db->Query($query);
			if (!$status) {
				$this->Db->RollbackTransaction();
				trigger_error('Subscribers_API::DeleteSubscribers -- Unable to query database -- ' . $this->Db->Error(), E_USER_NOTICE);
				return array(false, 'Unable to query the database');
			}
		}
		$this->Db->CommitTransaction();
		// -----

		return array(true, false);
	}

	/**
	 * ChangeSubscriberFormat
	 * Change a particular subscribers format for a list.
	 *
	 * @param String $format Format to change them to.
	 * @param Int $listid List to change them for.
	 * @param Int $subscriberid Subscriberid to change.
	 *
	 * @see Subscribers_Manage::ChangeFormat
	 *
	 * @return Array Returns a status (success,failure) and a reason why.
	 */
	function ChangeSubscriberFormat($format='html', $subscriberid=0)
	{
		$format = strtolower($format);
		if ($format == 'html') {
			$format = 'h';
		}

		if ($format == 'text') {
			$format = 't';
		}

		if ($format != 'h' && $format != 't') {
			return array(false, 'Invalid Format supplied');
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET format='" . $format . "' WHERE subscriberid=" . intval($subscriberid);

		$this->Db->Query($query);
		return array(true, false);
	}

	/**
	 * ChangeSubscriberConfirm
	 * Change a particular subscribers confirmation status. You can do it for a subscriber on a list, you can do it for a subscriber in general (all lists - in case they signed up to multiple lists on a form).
	 *
	 * @param String $status Status to change them to.
	 * @param Int $listid List to change them for.
	 * @param Int $subscriberid Subscriberid to change.
	 *
	 * @see Subscribers_Manage::ChangeStatus
	 *
	 * @return Array Returns a status (success,failure) and a reason why.
	 */
	function ChangeSubscriberConfirm($status='confirm', $listid=0, $subscriberid=0)
	{
		$status = strtolower($status);
		if ($status == 'confirm') {
			$status = 'c';
		}

		if ($status == 'unconfirm') {
			$status = 'u';
		}

		if ($status != 'c' && $status != 'u') {
			return array(false, 'Invalid Status supplied');
		}

		if ($status == 'c') {
			$status = '1';

			$confirmdate = $this->GetServerTime();
			if ((int)$this->confirmdate > 0) {
				$confirmdate = (int)$this->confirmdate;
			}
		}

		if ($status == 'u') {
			$status = '0';
			$confirmdate = 0;
		}

		if ($listid) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET confirmed='" . $this->Db->Quote($status) . "', confirmdate='" . $this->Db->Quote($confirmdate) . "' WHERE subscriberid=" . intval($subscriberid) . " AND listid=" . intval($listid);
		} else {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET confirmed='" . $this->Db->Quote($status) . "', confirmdate='" . $this->Db->Quote($confirmdate) . "' WHERE subscriberid=" . intval($subscriberid);
		}

		$this->Db->Query($query);
		return array(true, false);
	}

	/**
	 * ListConfirm
	 * Updates a subscribers' status on a particular list to mark them as confirmed.
	 * This is used as part of the form confirm process.
	 * Checks whether the subscriber is on the list at all in the first place.
	 * When you confirm your subscription it re-adds you to any autoresponders the list has set up.
	 * This is done so if you are unconfirmed when the autoresponder cron job runs, you won't get the autoresponder because you (most likely) won't meet the autoresponder criteria.
	 *
	 * @param Int $listid List to confirm the subscriber on.
	 * @param Int $subscriberid Subscriber id to confirm
	 *
	 * @see IsSubscriberOnList
	 * @see AddToListAutoresponders
	 *
	 * @return Array Returns a status (success, failure) and a reason why.
	 */
	function ListConfirm($listid=0, $subscriberid=0)
	{
		if (!$this->IsSubscriberOnList(false, $listid, $subscriberid)) {
			return array(false, sprintf(GetLang('Subscriber_NotSubscribed'), $subscriberid));
		}

		$confirmdate = $this->GetServerTime();
		if ((int)$this->confirmdate > 0) {
			$confirmdate = (int)$this->confirmdate;
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX. "list_subscribers SET confirmed='1', confirmip='" . $this->Db->Quote($this->confirmip) . "', confirmdate='" . $confirmdate . "', subscribedate='" . $confirmdate . "' WHERE listid=" . intval($listid) . " AND subscriberid=" . intval($subscriberid);
		$result = $this->Db->Query($query);

		if (!$result) {
			return array(false, true);
		}

		$this->AddToListAutoresponders($subscriberid, $listid);

		return array(true, false);
	}

	/**
	 * UnsubscribeSubscriber
	 * Unsubscribes an email address from a particular list. Makes sure the email address is subscribed to a list before going any further. It updates the list statistics if the person has made a request previously and this is confirming that unsubscribe request.
	 *
	 * @param String $emailaddress Subscriber's email address to unsubscribe.
	 * @param Int $listid List to remove them from.
	 * @param Int $subscriberid Subscriberid to remove.
	 * @param Boolean $skipcheck Whether to skip the check to make sure they are on the list.
	 * @param String $statstype The type of statistic we're updating (send/autoresponder)
	 * @param Int $statid The statistics id we're updating so we can see (through stats) the number of people who have unsubscribed directly from a send/autoresponder
	 *
	 * @see UnsubscribeRequest
	 * @see IsSubscriberOnList
	 *
	 * @return Array Returns a status (success,failure) and a reason why.
	 */
	function UnsubscribeSubscriber($emailaddress='', $listid=0, $subscriberid=0, $skipcheck=false, $statstype=false, $statid=0)
	{
		$emailaddress = trim($emailaddress);

		if (($emailaddress == '' && $subscriberid <= 0) || $listid <= 0) {
			return array(false, 'No List or email address');
		}

		if (!$skipcheck) {
			$subscriberid = $this->IsSubscriberOnList($emailaddress, $listid);
			if (!$subscriberid) {
				return array(false, sprintf(GetLang('Subscriber_NotSubscribed'), $emailaddress));
			}
		}

		$unsubscribetime = $this->GetServerTime();
		if ($this->unsubscribetime > 0) {
			$unsubscribetime = $this->unsubscribetime;
		}

		$subscriberid = (int)$subscriberid;
		$listid = (int)$listid;

		// fix up the list totals.
		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET unsubscribecount = unsubscribecount + 1, subscribecount = subscribecount - 1 WHERE listid=" . $listid;
		$this->Db->Query($query);

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET unsubscribed=" . intval($unsubscribetime) . ", unsubscribeconfirmed='1' WHERE listid=" . $listid . " AND subscriberid=" . $subscriberid;
		$this->Db->Query($query);

		$unsub_requestdate = 0;
		$unsub_requestip = '';
		// load up the request date/ip for the unsubscribe.
		$query = "SELECT unsubscriberequesttime, unsubscriberequestip FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe WHERE subscriberid=" . $subscriberid . " AND listid=" . $listid;
		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if (!empty($row)) {
			$unsub_requestdate = (int)$row['unsubscriberequesttime'];
			$unsub_requestip = $row['unsubscriberequestip'];
		}

		// delete the old request (if applicable).
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe WHERE subscriberid=" . $subscriberid . " AND listid=" . $listid;
		$this->Db->Query($query);

		if (!$this->unsubscribeip) {
			$this->unsubscribeip = GetRealIp();
		}

		if (!$statstype) {
			$statstype = 'f'; // f = form.
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe (subscriberid, unsubscribetime, listid, unsubscribeip, unsubscriberequesttime, unsubscriberequestip, statid, unsubscribearea) VALUES ('" . $subscriberid . "', " . $this->Db->Quote($unsubscribetime) . ", '" . $listid . "', '" . $this->Db->Quote($this->unsubscribeip) . "', '" . $unsub_requestdate . "', '" . $this->Db->Quote($unsub_requestip) . "', '" . (int)$statid . "', '" . $this->Db->Quote(strtolower(substr($statstype, 0, 1))) . "')";
		$this->Db->Query($query);

		return array(true, false);
	}

	/**
	 * BounceSubscriber
	 * Bounces an email address from a particular list. Makes sure the email address is subscribed to a list before going any further.
	 *
	 * @param String $emailaddress Subscriber's email address to bounce.
	 * @param Int $listid List to remove them from.
	 * @param Int $subscriberid The subscriber's id from the database. If this is supplied, then it is used and the email address is not checked (ie it assumes you have already checked it's valid).
	 * @param Boolean $already_bounced Whether the email address has already been marked as a bounce message. This is true if bounced by the 'RecordBounceInfo' function, but not if manually bounced from a mailing list.
	 *
	 * @see IsSubscriberOnList
	 * @see RecordBounceInfo
	 *
	 * @return Array Returns a status (success,failure) and a reason why.
	 */
	function BounceSubscriber($emailaddress=false, $listid=0, $subscriberid=0, $bouncetime=0, $already_bounced=false)
	{
		if ($emailaddress) {
			$emailaddress = trim($emailaddress);
		}

		$subscriberid = (int)$subscriberid;
		$bouncetime = (int)$bouncetime;
		if ($bouncetime <= 0) {
			$bouncetime = $this->GetServerTime();
		}

		if (!$emailaddress && $subscriberid <= 0) {
			return array(false, 'No email address supplied');
		}

		if ($listid <= 0) {
			return array(false, 'No List supplied');
		}

		// if we're passing in a subscriberid, don't do this check.
		if ($subscriberid <= 0) {
			$subscriberid = $this->IsSubscriberOnList($emailaddress, $listid);
			if (!$subscriberid) {
				return array(false, sprintf(GetLang('Subscriber_NotSubscribed'), $emailaddress));
			}
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET bounced=" . intval($bouncetime) . " WHERE listid=" . intval($listid) . " AND subscriberid=" . intval($subscriberid);
		$this->Db->Query($query);

		if (!$already_bounced) {
			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces (subscriberid, bouncetime, listid, statid, bouncetype, bouncerule, bouncemessage) VALUES ('" . $this->Db->Quote($subscriberid) . "', " . $this->Db->Quote($bouncetime) . ", '" . $this->Db->Quote($listid) . "', 0, 'unknown', 'unknown', 'Manually Bounced')";
			$this->Db->Query($query);
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET bouncecount=bouncecount + 1, subscribecount=subscribecount - 1 WHERE listid=" . intval($listid);
		$this->Db->Query($query);

		return array(true, false);
	}

	/**
	 * AlreadyBounced
	 * Check whether a bounce has already been recorded for the statid, listid and subscriberid passed in.
	 * This is only used by bounce processing and should stop the same bounce information from being recorded if a bounce gets interrupted (otherwise it would be recorded again and again).
	 * A bounce should only be recorded ONCE per statid & listid & subscriberid.
	 * Autoresponders & newsletters share the same sequence so we don't need to check the type of bounce it was coming from.
	 *
	 * @param Int $subscriberid Subscriberid to check.
	 * @param Int $statid Statid to check.
	 * @param Int $listid Listid to check.
	 *
	 * @return Mixed Returns the bounceid if a bounce has already been recorded. Returns false if one has not been recorded.
	 */
	function AlreadyBounced($subscriberid=0, $statid=0, $listid=0)
	{
		$query = "SELECT bounceid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces WHERE subscriberid=" . intval($subscriberid) . " AND statid=" . intval($statid) . " AND listid=" . intval($listid) . " LIMIT 1";
		$result = $this->Db->Query($query);
		$bid = $this->Db->FetchOne($result, 'bounceid');
		if ($bid > 0) {
			return $bid;
		}
		return false;
	}

	/**
	 * RecordBounceInfo
	 * This will save bounce information against the subscriber and also work out whether they have bounced too many times in the last period of time.
	 * If they have bounced less than $this->softbounce_count, the bounce is only recorded.
	 * If they have bounced more than or equal to $this->softbounce_count, then they are marked as bounced on the list and are made inactive.
	 *
	 * @param Int $subscriberid Subscriber id to bounce / check. This is not checked to make sure it is valid, it has been done already by the calling function.
	 * @param Int $bounce_statid The bounce statistics id to record this particular bounce against.
	 * @param Int $bounce_listid The list id the subscriber is bouncing from.
	 * @param String $bounce_type The type of bounce this matched. This is stored in the database for 'historical' reasons.
	 * @param String $bounce_rule The bounce rule this matched. This is stored in the database for 'historical' reasons.
	 * @param String $bounce_message The entire bounce message. This is stored in the database for 'historical' reasons.
	 * @param Int $bounce_time The time of the bounce. If this is not passed in, it is assumed to be 'now'.
	 *
	 * @see softbounce_count
	 *
	 * @return Boolean Returns true if the subscriber has been completely bounced from the mailing list and made inactive. Returns false if they have not been made inactive.
	 */
	function RecordBounceInfo($subscriberid=0, $bounce_statid=0, $bounce_listid=0, $bounce_type='', $bounce_rule='', $bounce_message='', $bounce_time=0)
	{
		$subscriberid = (int)$subscriberid;
		$bounce_statid = (int)$bounce_statid;
		$bounce_listid = (int)$bounce_listid;
		$bounce_time = (int)$bounce_time;

		if ($bounce_time <= 0) {
			$bounce_time = $this->GetServerTime();
		}

		/**
		 * We don't really need to save the bouncemessage
		 * instead of removing it from the function call, just don't save it.
		 * this saves changing all calling functions and possibly causing other issues.
		 *
		 * Only save the message if it's for "blockedcontent" so we can see what message was returned by the other server.
		 */
		if ($bounce_rule != 'blockedcontent') {
			$bounce_message = '';
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces(subscriberid, statid, listid, bouncetime, bouncetype, bouncerule, bouncemessage) VALUES (" . $subscriberid . ", " . $bounce_statid . ", " . $bounce_listid . ", " . $bounce_time . ", '" . $this->Db->Quote($bounce_type) . "', '" . $this->Db->Quote($bounce_rule) . "', '" . $this->Db->Quote($bounce_message) . "')";
		$result = $this->Db->Query($query);

		$query = "SELECT COUNT(*) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces WHERE subscriberid='" . $subscriberid . "'";
		$result = $this->Db->Query($query);
		$bounce_count = $this->Db->FetchOne($result, 'count');

		if ($bounce_count >= $this->softbounce_count || $bounce_type == 'hard') {
			$this->BounceSubscriber(false, $bounce_listid, $subscriberid, $bounce_time, true);
			return true;
		}

		return false;
	}

	/**
	 * ActivateSubscriber
	 * Re-activates a subscriber and removes them from the 'bounce' and 'unsubscribe' lists. It will also update list subscribe/unsubscribe counts appropriately.
	 *
	 * @param String $emailaddress Subscriber's email address to re-activate.
	 * @param Int $listid List to activate them on.
	 *
	 * @see IsSubscriberOnList
	 *
	 * @return Array Returns a status (success,failure) and a reason why.
	 */
	function ActivateSubscriber($emailaddress='', $listid=0)
	{
		$emailaddress = trim($emailaddress);

		if ($emailaddress == '' || $listid <= 0) {
			return array(false, 'No List or email address');
		}

		$subscriberid = $this->IsSubscriberOnList($emailaddress, $listid);
		if (!$subscriberid) {
			return array(false, sprintf(GetLang('Subscriber_NotSubscribed'), $emailaddress));
		}

		$query = "SELECT unsubscribed, bounced FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE subscriberid=" . intval($subscriberid) . " AND listid=" . intval($listid);
		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);

		// if they were previously unsubscribed, we need to fix up the list counts.
		if ($row['unsubscribed'] > 0) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET unsubscribecount = unsubscribecount - 1, subscribecount = subscribecount + 1 WHERE listid=" . intval($listid);
			$this->Db->Query($query);
		}

		// if they were previously bounced, we need to fix up the list counts.
		if ($row['bounced'] > 0) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "lists SET bouncecount = bouncecount - 1, subscribecount = subscribecount + 1 WHERE listid=" . intval($listid);
			$this->Db->Query($query);
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe WHERE subscriberid=" . intval($subscriberid) . " AND listid=" . intval($listid);
		$this->Db->Query($query);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces WHERE subscriberid=" . intval($subscriberid) . " AND listid=" . intval($listid);
		$this->Db->Query($query);

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET bounced=0, unsubscribed=0 WHERE listid=" . intval($listid) . " AND subscriberid=" . intval($subscriberid);
		$this->Db->Query($query);

		return array(true, false);

	}


	/**
	 * GenerateConfirmCode
	 * Generates a random string to use as a confirmation code.
	 *
	 * @see confirmcode
	 *
	 * @return String Returns an md5 sum confirmation code.
	 */
	function GenerateConfirmCode()
	{
		if ($this->confirmcode) {
			return $this->confirmcode;
		}

		$code = md5(uniqid(rand(), true));
		$this->confirmcode = $code;
		return $code;
	}

	/**
	 * GenerateSubscriberSubQuery
	 * Generates the queries involved both for fetching & getting subscribers
	 * It uses information in the $searchinfo array passed in to generate the query.
	 * This info should include the list, link, newsletter, status etc you are searching for.
	 *
	 * @param Array $searchinfo The information to create the search query.
	 * @param Array $sortdetails The sort information to add to the end of the query.
	 * @param Boolean $queueonly Whether the calling function is queuing the results from this query or not. This will stop the first part of the query ("SELECT") from being returned.
	 *
	 * @see FetchSubscribers
	 * @see GetSubscribers
	 * @see _GenerateDateSubQuery
	 * @see _GenerateSubscribeDateSubQuery
	 * @see _CreateCustomFieldSubquery
	 * @see _CreateNumberQuery
	 * @see _subqueryCapable
	 *
	 * @return Array Returns an array of 'search_query' and 'count_query'.
	 */
	function GenerateSubscriberSubQuery($searchinfo, $sortdetails, $queueonly=false)
	{
		$tablePrefix = SENDSTUDIO_TABLEPREFIX;

		$tables = array('l' => "{$tablePrefix}list_subscribers AS l");
		$tableConditions = array();
		$selectColumns = array('l.subscriberid, l.emailaddress, l.format, l.subscribedate, l.confirmed, l.unsubscribed, l.bounced, l.listid');
		$selectDistinct = false;

		$leftJoinTables = array();
		$leftJoinConditions = array();

		if (is_array($searchinfo['List']) || $searchinfo['List'] == 'any') {
			$tables['ml'] = "{$tablePrefix}lists";
			$tableConditions[] = 'l.listid=ml.listid';
			$selectColumns[] = 'ml.name AS listname';
		}

		$list = $searchinfo['List'];

		if (isset($searchinfo['AvailableLists'])) {
			$searchinfo['AvailableLists'] = $this->CheckIntVars($searchinfo['AvailableLists']);
			if (empty($searchinfo['AvailableLists'])) {
				$searchinfo['AvailableLists'] = array('0');
			}
			$list = $searchinfo['AvailableLists'];
		}

		if (is_array($list)) {
			$list = $this->CheckIntVars($list);
			if (count($list) > 0) {
				$tableConditions[] = "l.listid IN (" . implode(',', $list) . ")";
			}
		} else {
			if ($list != 'any') {
				$tableConditions[] = "l.listid='" . $this->Db->Quote($list) . "'";
			}
		}

		/**
		 * Filter by link/link clicks
		 */
		if (isset($searchinfo['Link'])) {
			if (!isset($searchinfo['LinkType']) || $searchinfo['LinkType'] == 'clicked') {
				$tables['lc'] = "{$tablePrefix}stats_linkclicks";
				$tableConditions[] = 'l.subscriberid=lc.subscriberid';
				if ($searchinfo['Link'] > -1) {
					$tableConditions[] = 'lc.linkid=' . (int)$searchinfo['Link'];
				}
				$selectDistinct = true;
			} else {
				$query = 	"SELECT lc.subscriberid subscriberid FROM {$tablePrefix}stats_linkclicks lc".
				(($searchinfo['Link'] > -1)? ' WHERE lc.linkid=' . (int)$searchinfo['Link'] : '');

				if ($this->_subqueryCapable()) {
					$tableConditions[] = 'l.subscriberid NOT IN (' . $query . ')';
				} else {
					$records = array('0');
					$rs = $this->Db->Query($query);
					while ($row = $this->Db->Fetch($rs)) {
						$records[] = (int)$row['subscriberid'];
					}

					$this->Db->FreeResult($rs);
					$tableConditions[] = 'l.subscriberid NOT IN ('. implode(',', $records) .')';
				}
			}
		}

		/**
		 * Filter by email opened
		 */
		if (isset($searchinfo['Newsletter'])) {
			if (!isset($searchinfo['OpenType']) || $searchinfo['OpenType'] == 'opened') {
				$tables['seo'] = "{$tablePrefix}stats_emailopens";
				$tables['sn'] = "{$tablePrefix}stats_newsletters";
				$tableConditions[] = 'l.subscriberid=seo.subscriberid';
				$tableConditions[] = 'sn.statid=seo.statid';
				if ($searchinfo['Newsletter'] > -1) {
					$tableConditions[] = 'sn.newsletterid=' . (int)$searchinfo['Newsletter'];
				}
				$selectDistinct = true;
			} else {
				$query = "SELECT subscriberid FROM {$tablePrefix}stats_emailopens seo INNER JOIN {$tablePrefix}stats_newsletters sn WHERE sn.statid=seo.statid".
				(($searchinfo['Newsletter'] > -1)? ' AND sn.newsletterid=' . (int)$searchinfo['Newsletter'] : '');

				if ($this->_subqueryCapable()) {
					$tableConditions[] = 'l.subscriberid NOT IN (' . $query . ')';
				} else {
					$records = array('0');
					$rs = $this->Db->Query($query);
					while ($row = $this->Db->Fetch($rs)) {
						$records[] = (int)$row['subscriberid'];
					}

					$this->Db->FreeResult($rs);
					$tableConditions[] = 'l.subscriberid NOT IN ('. implode(',', $records) .')';
				}
			}
		}

		if (isset($searchinfo['Format'])) {
			$tableConditions[] = "l.format='" . $this->Db->Quote($searchinfo['Format']) . "'";
		}

		if (isset($searchinfo['Email']) && $searchinfo['Email'] != '') {
			$like = 'LIKE';
			if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
				$like = 'ILIKE';
			}
			$tableConditions[] = "l.emailaddress {$like} '%" . $this->Db->Quote($searchinfo['Email']) . "%'";
		}

		if (isset($searchinfo['Status'])) {
			switch (strtolower($searchinfo['Status'])) {
				case 'b':
					$tableConditions[] = '(l.bounced > 0)';
					break;

				case 'u':
					$tableConditions[] = '(l.unsubscribed > 0)';
					break;

				case 'a':
				default:
					$tableConditions[] = '(l.unsubscribed=0 AND l.bounced=0)';
					break;
			}
		}

		if (isset($searchinfo['Confirmed'])) {
			switch (strtolower($searchinfo['Confirmed'])) {
				case '1':
					$tableConditions[] = 'confirmed=\'1\'';
					break;

				case '0':
					$tableConditions[] = 'confirmed=\'0\'';
					break;
			}
		}

		if (isset($searchinfo['Subscriber'])) {
			$tableConditions[] = "l.subscriberid='" . (int)$searchinfo['Subscriber'] . "'";
		}

		if (isset($searchinfo['DateSearch'])) {
			$temp = trim($this->_GenerateSubscribeDateSubQuery($searchinfo['DateSearch']));
			if (!empty($temp)) {
				$tableConditions[] = $temp;
			}
		}

		/**
		 * Filter by custom field if we need to.
		 */
		$customfield_subqueries = $this->_CreateCustomFieldSubquery($searchinfo);

		if (!empty($customfield_subqueries)) {
			foreach ($customfield_subqueries as $index => $subquery) {
				$query = "	SELECT 	d.subscriberid AS subscriberid
							FROM 	{$tablePrefix}subscribers_data d
							WHERE 	{$subquery['query']}";

				if ($this->_subqueryCapable()) {
					$selectDistinct = true;

					if ($subquery['condition'] == 'NONE') {
						$tableConditions[] = "l.subscriberid NOT IN ({$query})";
					} else {
						$tableConditions[] = "l.subscriberid IN ({$query})";
					}
				} else {
					$records = array('0');
					$rs = $this->Db->Query($query);
					while ($row = $this->Db->Fetch($rs)) {
						$records[] = (int)$row['subscriberid'];
					}

					$this->Db->FreeResult($rs);
					if ($subquery['condition'] == 'NONE') {
						$tableConditions[] = 'l.subscriberid NOT IN ('. implode(',', $records) .')';
					} else {
						$tableConditions[] = 'l.subscriberid IN ('. implode(',', $records) .')';
					}
				}
			}
		}

		// When sorting by a custom field load the custom field values using a left join
		if (isset($sortdetails['CustomFields'])) {
			$leftJoinTables['sd'] = "{$tablePrefix}subscribers_data";
			$leftJoinConditions['sd'] = array();
			$fields = array();
			if (is_array($sortdetails['CustomFields'])) {
				$selectColumns[0] .= ",sd.data";
				foreach ($sortdetails['CustomFields'] as $fieldid) {
					if (is_numeric($fieldid)) {
						$fields[] = (int)$fieldid;
					}
				}
				$leftJoinConditions['sd'][] = "sd.fieldid IN (" . implode(',',$fields) . ") AND sd.subscriberid = l.subscriberid";

				// since the l. table is used in the left join conditions it needs to be listed as the last table in the FROM part
				if (isset($tables['l'])) {
					$value = $tables['l'];
					unset($tables['l']);
					$tables['l'] = $value;
				}
			}
		}

		$query = '';

		$temp = array();
		foreach ($tables as $alias=>$name) {
			if ($alias == 'l') {
				$temp[] = $name;
			} else {
				$temp[] = "{$name} AS {$alias}";
			}
		}
		$query .= ' FROM ' . implode(',', $temp);

		foreach ($leftJoinTables as $shortname => $tablename) {
			$query .= " LEFT JOIN $tablename AS $shortname ON (";
			if (is_array($leftJoinConditions[$shortname])) {
				foreach ($leftJoinConditions[$shortname] as $condition) {
					$query .= " $condition";
				}
			} else {
				$query .= ' 1';
			}
			$query .= ')';
		}

		$query .= (count($tableConditions) > 0? (' WHERE ' . implode(' AND ', $tableConditions)) : '');

		$count_query = 'SELECT COUNT(' . ($selectDistinct? 'DISTINCT l.subscriberid' : '1') . ') AS count' . $query;

		$search_query = '';
		if (!$queueonly) {
			$search_query = 'SELECT ';
			if ($selectDistinct) {
				$search_query .= ' DISTINCT ';
			}
			$search_query .= implode(',',$selectColumns);
		}
		$search_query .= $query;

		if (!$queueonly) {
			$search_query .= ' ORDER BY ';

			if (strtolower($sortdetails['SortBy']) == 'status') {
				$search_query .= 'CASE WHEN (bounced=0 AND unsubscribed=0) THEN 1 WHEN (unsubscribed > 0) THEN 2 WHEN (bounced > 0) THEN 3 END';
			} else {
				$search_query .= $sortdetails['SortBy'];
			}
			$search_query .= (strtolower($sortdetails['Direction']) == 'asc') ? ' asc ' : ' desc ';
			$search_query .= (strtolower($sortdetails['SortBy']) != 'emailaddress') ? ', emailaddress' : '';
		}

		return array('count_query' => $count_query, 'search_query' => $search_query);
	}

	/**
	 * _CreateCustomFieldSubquery
	 * Creates custom field queries based on the field types etc that are present in the information passed in.
	 *
	 * @param Array $searchinfo The information to generate a custom field query from.
	 *
	 * @see _CreateNumberQuery
	 *
	 * @return Array Returns an array of subqueries to put together.
	 */
	function _CreateCustomFieldSubquery($searchinfo=array())
	{
		$customfield_subqueries = array();
		if (isset($searchinfo['CustomFields']) && !empty($searchinfo['CustomFields'])) {
			foreach ($searchinfo['CustomFields'] as $fieldid => $fielddata) {
				if ($fielddata != "") {

					$fieldtype = $this->GetCustomFieldType($fieldid);

					switch ($fieldtype) {
						case 'date':
							$subquery = $this->_GenerateDateSubQuery($fielddata, $fieldid);

							// if we don't have "filter" set, then we're not filtering by this field.
							if (!$subquery) {
								break;
							}

							array_push($customfield_subqueries, array('condition' => '', 'query' => $subquery));
							break;

						case 'number':
							array_push($customfield_subqueries, array('condition' => '', 'query' => $this->_CreateNumberQuery($fieldid, $fielddata)));
							break;

						case 'checkbox':
							$fielddata_queries = array();
							if (is_array($fielddata)) {
								foreach ($fielddata as $k => $p) {
									// hand "serialize" the string so we can find it reasonably easily.
									$newfielddata = 's:' . strlen($k) . ':"' . $this->Db->Quote($k) . '";';
									$fielddata_queries[] = "(d.fieldid='" . $this->Db->Quote($fieldid) . "' AND d.data LIKE '%" . $newfielddata . "%')";
								}
							}

							if (!empty($fielddata_queries)) {
								$join_type = 'AND';
								$condition = 'AND';
								if (isset($searchinfo['Search_Options']['CustomFields'][$fieldid])) {
									$condition = $searchinfo['Search_Options']['CustomFields'][$fieldid];
									if ($condition == 'NONE') {
										$join_type = 'OR';
									} else {
										$join_type = $condition;
									}
								}

								if (!in_array($join_type, array('AND','OR'))) {
									$join_type = 'AND';
								}

								array_push($customfield_subqueries, array('condition' => $condition, 'query' => ('(' . implode(' '.$join_type.' ', $fielddata_queries) . ')')));
							}
							break;

						case 'dropdown':
						case 'radiobutton':
							if (is_array($fielddata)) {
								$fielddata_queries = array();
								foreach ($fielddata as $fieldoption) {
									if ($fieldoption == '') {
										continue;
									}
									$fielddata_queries[] = "(d.fieldid='" . $this->Db->Quote($fieldid) . "' AND d.data='" . $this->Db->Quote($fieldoption) . "')";
								}
								if (!empty($fielddata_queries)) {
									$customfield_subqueries[] = array('condition' => '', 'query' => '(' . implode(' OR ', $fielddata_queries) . ')');
								}
								break;
							}
							array_push($customfield_subqueries, array('condition' => '', 'query' => "(d.fieldid='" . $this->Db->Quote($fieldid) . "' AND d.data='" . $this->Db->Quote($fielddata) . "')"));
							break;

						default:
							array_push($customfield_subqueries, array('condition' => '', 'query' => "(d.fieldid='" . $this->Db->Quote($fieldid) . "' AND d.data LIKE '%" . $this->Db->Quote($fielddata) . "%')"));
					}
				}
			}
		}
		return $customfield_subqueries;
	}

	/**
	 * Creates a subquery for a number custom field based on the information passed in. This allows number fields to use '>', '<' and '=' in their search criteria.
	 *
	 * @param Int $fieldid The field we are creating the search subquery for.
	 * @param String $fielddata The data we want to search for.
	 *
	 * @see _CreateCustomFieldSubquery
	 *
	 * @return String Returns the subquery for number searching after it has been cleaned up. Anything not one of these characters is stripped out.
	 */
	function _CreateNumberQuery($fieldid=0, $fielddata='')
	{
		$fieldid = (int)$fieldid;
		if (!$fielddata || $fieldid <= 0) {
			return '';
		}

		// get rid of anything that isn't a number, isn't a space, isn't a
		// <, >, =, |, &, and, or.
		$fielddata = preg_replace('%[^\d\s|&><\-=(and|or)]+%', '', $fielddata);

		// get rid of multiple spaces between numbers.
		$fielddata = preg_replace('%([\d])\s+([\d])%', '\\1\\2', $fielddata);

		if (preg_match('%[><=]+%', $fielddata)) {
			$subq = "(d.fieldid=" . $fieldid . " AND d.data";

			if (preg_match('%[^0-9]+%', $fielddata)) {
				$fielddata = strtolower($fielddata);
				$fielddata = str_replace(' && ', ' && d.data ', $fielddata);
				$fielddata = str_replace(' || ', ' || d.data ', $fielddata);
				$fielddata = str_replace(' and ', ' and d.data ', $fielddata);
				$fielddata = str_replace(' or ', ' or d.data ', $fielddata);

				// get rid of anything not a number on the end of the query.
				$fielddata = preg_replace('/[^\d]+$/', '', trim($fielddata));

				$subq .= $fielddata;
			} else {
				$subq .= "='" . (int)$fielddata."'";
			}
			return $subq . ")";
		}
		return "(d.fieldid=" . $fieldid . " AND d.data=" . (int)$fielddata . ")";
	}

	/**
	 * GetSubscribers
	 * Returns a list of subscriber id's based on the information passed in.
	 * This is used to create a queue for exporting or sending to particular subscribers.
	 * If the queue details are passed in, then this will put the subscribers who match the criteria straight into the queue instead of returning them.
	 *
	 * @param Array $searchinfo An array of search information to restrict searching to. This is used to construct queries to cut down the subscribers found.
	 * @param Array $sortdetails How to sort the resulting subscriber information.
	 * @param Boolean $countonly Whether to only do a count or get the list of subscribers as well.
	 * @param Array $queuedetails If this is not an empty array, the subscribers returned from the query are put directly into this queue (based on the array fields).
	 *
	 * @see CreateQueue
	 * @see FetchSubscribers
	 * @see _GenerateDateSubQuery
	 * @see _GenerateSubscribeDateSubQuery
	 * @see GenerateSubscriberSubQuery
	 *
	 * @return Mixed This will return the count only if that is set to true. Otherwise this will return an array of data including the count and the subscriber list.
	 */
	function GetSubscribers($searchinfo=array(), $sortdetails=array(), $countonly=false, $queuedetails=array(), $setmax='-1')
	{
		if (empty($searchinfo)) {
			return array('count' => 0, 'subscribers' => array());
		}

		if (empty($sortdetails)) {
			$sortdetails = array('SortBy' => 'emailaddress', 'Direction' => 'asc');
		}

		$search_query = "";
		$queueonly = false;

		if (!empty($queuedetails)) {
			$distinct_query = "";

			if (isset($searchinfo['Link'])) {
				$distinct_query = " DISTINCT";
			}

			if (isset($searchinfo['Newsletter'])) {
				$distinct_query = " DISTINCT";
			}

			$queuedetails['ownerid'] = (int)$queuedetails['ownerid'];

			$search_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "queues (queueid, queuetype, ownerid, recipient, processed) SELECT " . $distinct_query . " " . $queuedetails['queueid'] . ", '" . $queuedetails['queuetype'] . "', " . $queuedetails['ownerid'] . ", l.subscriberid, 0";
			$queueonly = true;
		}

		$queries = $this->GenerateSubscriberSubQuery($searchinfo, $sortdetails, $queueonly);

		$count_query = $queries['count_query'];
		$search_query .= $queries['search_query'];

		setmax($setmax, $search_query);

		$count_result = $this->Db->Query($count_query);
		$count = $this->Db->FetchOne($count_result, 'count');

		checksize($count, $setmax, $countonly);

		if ($countonly) {
			return $count;
		}

		$search_result = $this->Db->Query($search_query);

		$subscriber_results = array();

		if (!$queueonly) {
			while ($row = $this->Db->Fetch($search_result)) {
				$subscriber_results[] = $row;
			}

			$this->Db->FreeResult($search_result);
		}

		$return = array();
		$return['count'] = $count;
		$return['subscriberlist'] = $subscriber_results;
		return $return;
	}

	/**
	 * GetSubscribersFromSegment
	 * Get subscribers from segment. This is esentially the same functions as Subscribers::GetSubscribers(),
	 * but it uses segment's information to fetch subscribers instead of search filter.
	 *
	 * @param Array $segmentIDs An array of segments that we want to fetch subscribers ID from
	 * @param Boolean $countonly Whether to only do a count or get the list of subscribers as well.
	 * @param Array $queuedetails If this is not an empty array, the subscribers returned from the query are put directly into this queue (based on the array fields).
	 *
	 * @return Mixed This will return the count only if that is set to true. Otherwise this will return an array of data including the count and the subscriber list.
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Segment_API
	 * @uses Segment_API::Load()
	 * @uses Segment_API::GetSearchInfo()
	 * @uses Segment_API::ReplaceLists()
	 * @uses Segment_API::ReplaceRules()
	 * @uses Segment_API::GetSubscribersCount()
	 * @uses Segment_API::GetSubscribersQueryString()
	 * @uses Db::Query()
	 * @uses Db::FetchOne()
	 * @uses Db::Fetch()
	 */
	function GetSubscribersFromSegment($segmentIDs, $countonly = false, $queuedetails = null, $sortdetails = array())
	{
		$return = array('count' => 0, 'subscriberlist' => 0, 'lists' => array());

		if (empty($sortdetails)) {
			$sortdetails = array('SortBy' => 'emailaddress', 'Direction' => 'asc', 'Max' => 100);
		}

		require_once(dirname(__FILE__) . '/segment.php');

		$count = 0;
		$lists = array();
		$selectQueries = array();
		foreach ($segmentIDs as $id) {
			$segmentAPI = new Segment_API();

			// Cannot load segment
			$status = $segmentAPI->Load($id);
			if (!$status) {
				return array();
			}

			// Get lists that are used in this segment
			$tempLists = $segmentAPI->GetMailingListUsed();
			$lists = array_merge($lists, $tempLists);

			// Get count
			$count += $segmentAPI->GetSubscribersCount(0, true);

			// Get query
			$tempQuery = $segmentAPI->GetSubscribersQueryString(true);
			$selectQueries[] = preg_replace('/^SELECT .*? FROM?/i', '', $tempQuery);
		}
		unset($segmentAPI);

		checksize($count, (isset($sortdetails['Max'])? $sortdetails['Max'] : 100), $countonly);
		$return['count'] = $count;
		$return['lists'] = $lists;

		if (empty($selectQueries)) {
			return array();
		}

		if ($countonly) {
			return $return;
		}

		if (empty($queuedetails)) {
			$temp = 'SELECT DISTINCT subscribers.subscriberid AS subscriberid FROM ';
			$selectQuery = $temp . implode(" UNION {$temp}", $selectQueries);
		} else {
			$queueID = intval($queuedetails['queueid']);
			$queueType = $this->Db->Quote($queuedetails['queuetype']);
			$queueOwnerID = intval($queuedetails['ownerid']);

			$temp = "SELECT DISTINCT {$queueID}, '{$queueType}', {$queueOwnerID}, subscribers.subscriberid, 0 FROM ";

			$selectQuery = 'INSERT INTO [|PREFIX|]queues (queueid, queuetype, ownerid, recipient, processed)';
			$selectQuery .= $temp . implode(" UNION {$temp}", $selectQueries);
		}

		setmax($sortdetails, $selectQuery);
		$selectQuery = preg_replace('/l.subscribedate/', 'subscribers.subscribedate', $selectQuery);

		$search_result = $this->Db->Query($selectQuery);
		if (!$search_result) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " -- Unable to query database with the following query string: {$selectQuery}", E_USER_NOTICE);
			return array();
		}

		if (!empty($queuedetails)) {
			return array();
		}

		$subscriber_results = array();
		while ($row = $this->Db->Fetch($search_result)) {
			$subscriber_results[] = $row;
		}
		$return['subscriberlist'] = $subscriber_results;

		return $return;
	}

	/**
	 * FetchSubscribers
	 * Returns all subscriber information based on the criteria passed in.
	 * Customfield restrictions are also checked if they are present in the $searchnfo array.
	 * The difference between this function and GetSubscribers is the return type, and GetSubscribers can also insert directly into a queue (for exporting or sending). This will only return an array of 'count' and the subscriber list (even if it's empty).
	 *
	 * <b>Example</b>
	 * <code>
	 * $return_array = array('count' => $count, 'subscribers' => $subscriber_list_array);
	 * </code>
	 *
	 * @param Int $pageid Which 'page' of results to return. Used with perpage it handles paging of results.
	 * @param Mixed $perpage How many results to return (Int|String)
	 * @param Array $searchinfo An array of search information to restrict searching to. This is used to construct queries to cut down the subscribers found.
	 * @param Array $sortdetails How to sort the resulting subscriber information.
	 *
	 * @see GetSubscribers
	 * @see GenerateSubscriberSubQuery
	 *
	 * @return Array Returns an empty array if there is no search info or no subscribers found. Otherwise returns subscriber info based on the criteria.
	 */
	function FetchSubscribers(&$pageid=1, $perpage=20, $searchinfo=array(), $sortdetails=array())
	{
		if ($pageid < 1) {
			$pageid = 1;
		}

		if ($perpage <= 0 && $perpage != 'all') {
			$perpage = 20;
		}

		if (empty($searchinfo)) {
			return array('count' => 0, 'subscribers' => array());
		}

		if (empty($sortdetails)) {
			$sortdetails = array('SortBy' => 'emailaddress', 'Direction' => 'asc');
		}

		$queries = $this->GenerateSubscriberSubQuery($searchinfo, $sortdetails);

		$count_query = $queries['count_query'];

		$count_result = $this->Db->Query($count_query);
		$count = $this->Db->FetchOne($count_result, 'count');

		$search_query = $queries['search_query'];
		if ($perpage != 'all') {
			$limit_start = ($pageid - 1) * $perpage;

			// make sure our page id is within the count
			if ($limit_start > $count) {
				$pageid = 1;
			}

			$search_query .= $this->Db->AddLimit((($pageid - 1) * $perpage), $perpage);
		}

                if ($perpage > 0 || $perpage == 'all') {
			$search_result = $this->Db->Query($search_query);
			$subscriber_results = array();
			while ($row = $this->Db->Fetch($search_result)) {
				$subscriber_results[] = $row;
			}
		} else {
			$subscriber_results = array();
		}

		$return = array();
		$return['count'] = $count;
		$return['subscriberlist'] = $subscriber_results;
		return $return;
	}

	/**
	 * FetchBannedSubscribers
	 * Returns all subscriber information based on the criteria passed in.
	 *
	 * @param Int $pageid Which 'page' of results to return. Used with perpage it handles paging of results.
	 * @param Mixed $perpage How many results to return (Int|String)
	 * @param Array $searchinfo An array of search information to restrict searching to. This is used to construct queries to cut down the banned subscribers found.
	 * @param Array $sortdetails How to sort the resulting banned subscriber information.
	 *
	 * @return Array Returns an empty array if there is no search info or no subscribers found. Otherwise returns banned subscriber info based on the criteria. Always contains both a count and a list.
	 * <b>Example</b>
	 * <code>
	 * $return_array = array('count' => $count, 'subscribers' => $subscriber_list_array());
	 * </code>
	 */
	function FetchBannedSubscribers($pageid=1, $perpage=10, $searchinfo=array(), $sortdetails=array())
	{
		if ((int)$pageid < 1) {
			$pageid = 1;
		}

		if ($perpage <= 0 && $perpage != 'all') {
			$perpage = 10;
		}

		if (empty($searchinfo)) {
			return array('count' => 0, 'subscribers' => array());
		}

		if (empty($sortdetails)) {
			$sortdetails = array('SortBy' => 'emailaddress', 'Direction' => 'asc');
		}

		$count_query = "SELECT COUNT(*) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "banned_emails WHERE ";

		$search_query = "SELECT banid, emailaddress, list, bandate FROM " . SENDSTUDIO_TABLEPREFIX . "banned_emails WHERE ";

		$total_subquery = "";

		if (!is_numeric($searchinfo['List'])) {
			$total_subquery .= "list='g'";
		} else {
			$total_subquery .= "list='" . (int)$searchinfo['List'] . "'";
		}

		$count_query .= $total_subquery;

		$search_query .= $total_subquery;

		$search_query .= " ORDER BY " . $sortdetails['SortBy'] . " " . $sortdetails['Direction'];

		if ($perpage != 'all') {
			$search_query .= $this->Db->AddLimit((($pageid - 1) * $perpage), $perpage);
		}

		$count_result = $this->Db->Query($count_query);
		$count = $this->Db->FetchOne($count_result, 'count');

		if ($perpage > 0 || $perpage == 'all') {
			$search_result = $this->Db->Query($search_query);
			$subscriber_results = array();
			while ($row = $this->Db->Fetch($search_result)) {
				$subscriber_results[] = $row;
			}
		} else {
			$subscriber_results = array();
		}

		$return = array();
		$return['count'] = $count;
		$return['subscriberlist'] = $subscriber_results;
		return $return;
	}

	/**
	 * LoadBan
	 * Loads a ban from the database based on the 'banid' and 'list' passed in. If the list passed in is numeric, that's ok - if it's not, then we assume you're checking the global banned list.
	 *
	 * @param Int $banid The ban id to load up (used for editing).
	 * @param Mixed $list The list the ban is on. If this is not a number it will look at the 'global' ban list.
	 *
	 * @return False|Array Returns false if the ban doesn't exist or if the query can't be run. Otherwise returns the result (which is an array).
	 */
	function LoadBan($banid=0, $list=null)
	{
		$banid = (int)$banid;
		if ($banid <= 0) {
			return false;
		}

		if (is_numeric($list)) {
			$list = (int)$list;
		} elseif (!is_null($list)) {
			$list = 'g';
		} else {
			$list = false;
		}

		$query = "SELECT banid, emailaddress, list, bandate FROM " . SENDSTUDIO_TABLEPREFIX . "banned_emails WHERE banid=" . intval($banid);
		if ($list) {
			$query .= " AND list='" . $this->Db->Quote($list) . "'";
		}
		$query .= " LIMIT 1";

		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		return $row;
	}

	/**
	 * UpdateBan
	 * Updates a banned email address and list based on the information passed in. If it's the same as the current information, it just returns and nothing else happens. If it's different, it gets updated.
	 *
	 * @param Int $banid The ban id from the database to update. This is loaded to make sure it's valid and then gets updated.
	 * @param Array $info The new ban information to update. This includes the email address and the list to update.
	 *
	 * @see LoadBan
	 *
	 * @return Array Returns a status (true/false) and a message about what happened.
	 */
	function UpdateBan($banid=0, $info=array())
	{
		$banid = (int)$banid;
		$current_ban = $this->LoadBan($banid);
		if (!$current_ban) {
			return array(false, 'Bad ban id');
		}

		if ($current_ban['emailaddress'] == $info['emailaddress'] && $current_ban['list'] ==  $info['list']) {
			return array(true, false);
		}

		$newlist = $info['list'];
		if (!is_numeric($newlist)) {
			$newlist = 'g';
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "banned_emails SET emailaddress='" . $this->Db->Quote($info['emailaddress']) . "', list='" . $this->Db->Quote($newlist) . "' WHERE banid=" . intval($banid);

		$result = $this->Db->Query($query);
		if (!$result) {
			return array(false, 'Bad Query');
		}
		return array(true, false);
	}

	/**
	 * GetForm
	 * Gets the last form the subscriber used for an action. This is used for unsubscribing and confirming of the unsubscribe request. If the formid is present, then this means the request came from a form and not from an unsubscribe link in an email.
	 *
	 * @param Int $subscriberid The subscriberid to fetch the formid for.
	 *
	 * @see SetForm
	 * @see confirm.php
	 * @see formid
	 *
	 * @return False|Int Returns false if there is no formid present in the requests. If there is one, it returns the formid last used.
	 */
	function GetForm($subscriberid=0)
	{
		$query = "SELECT formid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE subscriberid=" . intval($subscriberid) . " ORDER BY requestdate DESC LIMIT 1";
		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			return false;
		}

		return (int)$row['formid'];
	}

	/**
	 * SetForm
	 * Sets the last form the subscriber used for an action. This is used for unsubscribing and confirming of the unsubscribe request.
	 *
	 * @param Int $subscriberid The subscriberid to update to the new formid.
	 *
	 * @see GetForm
	 * @see unsubform.php
	 * @see formid
	 *
	 * @return Boolean Returns whether the update worked or not.
	 */
	function SetForm($subscriberid=0)
	{
		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET formid='" . (int)$this->formid . "' WHERE subscriberid=" . intval($subscriberid);
		$result = $this->Db->Query($query);
		return $result;
	}

	/**
	 * GetListsByForm
	 * Gets an array of lists the email address is on according to the formid.
	 * This is used when confirming (especially unsubscribe confirmations) so you are only removed from the appropriate lists you want.
	 * For example, if you are on multiple lists, and an unsubscribe form has multiple lists, we don't want to remove you from all mailing lists - only the ones you have chosen.
	 *
	 * @param String $emailaddress The emailaddress to fetch the info for.
	 * @param Int $formid The form to check against.
	 *
	 * @see SetForm
	 * @see confirm.php
	 * @see formid
	 *
	 * @return Array Returns an array of list id's. If none match, an empty array is returned.
	 */
	function GetListsByForm($emailaddress='', $formid=0)
	{
		$emailaddress = trim($emailaddress);

		$lists = array();

		$formid = (int)$formid;
		if (!$emailaddress || $formid <= 0) {
			return $lists;
		}

		$query = "SELECT listid FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE emailaddress='" . $this->Db->Quote($emailaddress) . "' AND formid=" . $formid;
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$lists[] = (int)$row['listid'];
		}
		return $lists;
	}

	/**
	 * Enter description here...
	 * @param Integer $subscriberid Subscriber ID to fetch
	 * @return Array|FALSE Returns an associative array of the record if successful, FALSE othwerwise
	 */
	function GetRecordByID($subscriberid)
	{
		$subscriberid = intval($subscriberid);
		$status = $this->Db->Query("SELECT * FROM [|PREFIX|]list_subscribers WHERE subscriberid = {$subscriberid}");
		if (!$status) {
			return false;
		}

		$record = $this->Db->Fetch($status);
		$this->Db->FreeResult($status);

		return $record;
	}

	/**
	 * UnsubscribeRequest
	 * This handles unsubscribe requests. If this is a first-time request, it logs it appropriately. If the first request wasn't acknowledged or process (ie you submit to an unsubscribe form again before clicking the 'unsubscribe' link), this will delete the old request and re-add it.
	 * If the request is acknowledged, the subscriber will be unsubscribed from the list accordingly.
	 *
	 * @param Int $subscriberid The subscriber's id from the database
	 * @param Int $listid The listid to unsubscribe them from
	 *
	 * @see IsSubscriberOnList
	 * @see UnsubscribeSubscriber
	 * @see unsubform.php
	 *
	 * @return Boolean Returns true if the unsubscribe worked, or if the request is acknowledged. Returns false if the subscriber is not on the mailing list in the first place or if the unsubscribe confirmation failed.
	 */
	function UnsubscribeRequest($subscriberid=0, $listid=0)
	{
		if (!$this->IsSubscriberOnList(false, $listid, $subscriberid, true)) {
			return false;
		}

		$subscriberid = (int)$subscriberid;
		$listid = (int)$listid;

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscribers SET unsubscribeconfirmed='" . $this->unsubscribeconfirmed . "' WHERE subscriberid='" . $subscriberid . "' AND listid='" . $listid . "'";
		$this->Db->Query($query);

		if ($this->unsubscribeconfirmed) {
			$result = $this->UnsubscribeSubscriber(false, $listid, $subscriberid, true);
			if ($result[0] == true) {
				return true;
			}
			return false;
		} else {
			// delete the old request (if applicable).
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe WHERE subscriberid='" . $subscriberid . "' AND listid='" . $listid . "'";
			$this->Db->Query($query);

			if (!$this->unsubscriberequestip) {
				$this->unsubscriberequestip = GetRealIp();
			}

			// re-add it.
			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe (subscriberid, unsubscribetime, listid, unsubscribeip, unsubscriberequesttime, unsubscriberequestip) VALUES ('" . $subscriberid . "', 0, '" . $listid . "', '', '" . $this->GetServerTime() . "', '" . $this->unsubscriberequestip . "')";
			$this->Db->Query($query);
		}
		return true;
	}

	/**
	 * _GenerateDateSubQuery
	 * Generates the date query that gets appended for queries that include any sort of date custom field. It has to break the date stored in the database up into chunks and check each part separately to make sure they match. This is done so the date is stored in a consistent manner and searching will work in any scenario.
	 *
	 * It takes in an array of data:
	 * <b>Example</b>
	 * <code>
	 * $fielddata = array('filter' => true, 'type' => 'after', 'mm_start' => 01, 'dd_start' => 01, 'yy_start' => 2001);
	 * </code>
	 * will return the sql query to search for the 'date' after 01/01/2001
	 * If filter is not in the array, this will return false (ie we are not using this as a filter).
	 *
	 * @param Array $fielddata The array of data to use for constructing the sql query for proper searching
	 * @param Int $fieldid The field we are searching for.
	 *
	 * @see GetSubscribers
	 * @see FetchSubscribers
	 * @see _GenerateDateSubQuery_PG
	 *
	 * @return False|String This returns false if the 'filter' option isn't set in the array. Otherwise it will construct the date 'subquery' and return it.
	 */
	function _GenerateDateSubQuery($fielddata=array(), $fieldid=0)
	{
		if (!isset($fielddata['filter'])) {
			return false;
		}

		// The only difference between the MySQL and PgSQL way of doing this is
		// the 'to date' function and the 'get day of year' function.
		$field_date = "STR_TO_DATE(d.data, '%d/%m/%Y')";
		$day_of_year = "DAYOFYEAR(%s)";
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$field_date = "TO_DATE(d.data, 'DD/MM/YYYY')";
			$day_of_year = "EXTRACT(DOY FROM DATE (%s))";
		}

		// This condition is just for the 'between' case when searching for a date where we don't care about the year (e.g. birthdays).
		if (!is_numeric($fielddata['yy_start']) || !is_numeric($fielddata['yy_end'])) {
			$fielddata['yy_start'] = 0;
			$fielddata['yy_end'] = 0;
		}
		$start_date = "'" . sprintf("%04s", intval($fielddata['yy_start'])) . '-' . sprintf("%02s", intval($fielddata['mm_start'])) . '-' . sprintf("%02s", intval($fielddata['dd_start'])) . "'";

		$subquery = "d.fieldid=" . intval($fieldid) . " AND (";
		switch ($fielddata['type']) {
			case 'after':
				$subquery .= "{$field_date} > {$start_date}";
				break;

			case 'between':
				// The end date is only present when we're doing a 'between'.
				$end_date = "'" . sprintf("%04s", intval($fielddata['yy_end'])) . '-' . sprintf("%02s", intval($fielddata['mm_end'])) . '-' . sprintf("%02s", intval($fielddata['dd_end'])) . "'";
				if ($fielddata['yy_start'] == 0) {
					// We don't care about the year.
					$start_date = sprintf($day_of_year, $start_date);
					$end_date = sprintf($day_of_year, $end_date);
					$field_date = sprintf($day_of_year, $field_date);
				}
				$subquery .= "{$field_date} BETWEEN {$start_date} AND {$end_date}";
				break;

			case 'before':
				$subquery .= "{$field_date} < {$start_date}";
				break;

			case 'exactly':
				$subquery .= "{$field_date} = {$start_date}";
				break;
		}
		$subquery .= ")";

		return $subquery;
	}

	/**
	 * _GenerateSubscribeDateSubQuery
	 * Generates the subscribe date query that gets appended for queries that include the subscribe date.
	 * It takes in an array of data:
	 * <b>Example</b>
	 * <code>
	 * $fielddata = array('filter' => true, 'type' => 'after', 'mm_start' => 01, 'dd_start' => 01, 'yy_start' => 01);
	 * </code>
	 * will return the sql query to search for subscribers after 01/01/(20)01
	 * If filter is not in the array, this will return false (ie we are not using this as a filter).
	 *
	 * @param Array $fielddata The array of data to use for constructing the sql query for proper searching
	 *
	 * @see GetSubscribers
	 * @see FetchSubscribers
	 *
	 * @return False|String This returns false if the 'filter' option isn't set in the array. Otherwise it will construct the subscribe date 'subquery' and return it.
	 */
	function _GenerateSubscribeDateSubQuery($fielddata=array())
	{
		if (!isset($fielddata['filter'])) {
			return false;
		}

		$type = strtolower($fielddata['type']);

		switch ($type) {
			case 'after':
				$query_clause = " subscribedate >= " . $fielddata['StartDate'];
				break;

			case 'before':
				$query_clause = " subscribedate <= " . $fielddata['StartDate'];
				break;

			case 'exact':
			case 'exactly':
				$query_clause = " (subscribedate >= " . $fielddata['StartDate'] . " AND subscribedate < " . ($fielddata['StartDate'] + 86400) . ")";
				break;

			case 'between':
				$query_clause = " (subscribedate >= " . $fielddata['StartDate'] . " AND subscribedate <= " . $fielddata['EndDate'] . ")";
				break;

			case 'not':
				$query_clause = " subscribedate <> " . $fielddata['StartDate'];
				break;
		}
		return $query_clause;
	}

	/**
	 * LoadSubscriberBounceInfo
	 * Load subscriber bounce information
	 * This is used by exporting to load the bounce information so it can be included in the file.
	 *
	 * @param Int $subscriberid The subscriberid to load bounce information for.
	 * @param Int $listid The listid to load the bounce information for.
	 *
	 * @return Array Returns an array of information including the bounce time, the bounce type (hard/soft) and the bounce rule (eg 'user does not exist').
	 */
	function LoadSubscriberBounceInfo($subscriberid=0, $listid=0)
	{
		$query = "SELECT bouncetime, bouncetype, bouncerule FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces WHERE subscriberid=" . intval($subscriberid) . " AND listid=" . intval($listid);

		$result = $this->Db->Query($query);
		return $this->Db->Fetch($result);
	}

	/**
	 * GetUnsentSubscribers
	 * Gets a list of email addresses from the list_subscribers table based on the queue id passed in and which reason we are looking at.
	 * This is so we can show a list of emailaddresses that couldn't be sent to for a particular reason (eg the mail server was down or smtp details were wrong or the subscriber has issues - eg unsubscribed).
	 *
	 * @param Int $queueid The queue we are looking at. This looks at the queues_unsent table.
	 * @param Int $reasoncode The reasoncode of what we're looking at.
	 *
	 * @see API::Save_Unsent_Recipient
	 *
	 * @return Array Returns an array of email addresses for that particular queue & reason.
	 */
	function GetUnsentSubscribers($queueid=0, $reasoncode=0)
	{
		$subscribers = array();
		$query = "SELECT emailaddress FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls, " . SENDSTUDIO_TABLEPREFIX . "queues_unsent qu WHERE ls.subscriberid=qu.recipient AND qu.queueid=" . intval($queueid) . " AND qu.reasoncode=" . intval($reasoncode);
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$subscribers[] = $row['emailaddress'];
		}
		return $subscribers;
	}

	/**
	 * GenerateQueryFromSegmentRules
	 * Generate query string from segemnt record
	 *
	 * Successful return array wil have the following structure:
	 * - selectQuery => Query to select the subscribers described by the rules
	 * - countQuery => Query to count the subscribers described by the rules
	 *
	 * @param Array $listIDs An array of list IDs to be queried for
	 * @param Array $rules An array of segment rules
	 *
	 * @return Mixed Returns an array of SQL statement (Please see description above) (Array|FALSE)
	 *
	 * @uses Subscribers_API::_GenerateSegmentRuleQuery()
	 * @uses Subscribers_API::$_cacheCustomfields
	 */
	function GenerateQueryFromSegmentRules($listIDs, $rules)
	{
		$listIDs = $this->CheckIntVars($listIDs);

		$tables = array(
			'subscribers' => SENDSTUDIO_TABLEPREFIX . 'list_subscribers AS subscribers',
		);

		$joins = array();

		$conditions = array(
			'subscribers.listid IN (' . implode(', ', $listIDs) . ')',
		);

		$columns = array(
			'subscribers.subscriberid AS subscriberid',
			', subscribers.emailaddress AS emailaddress',
			', subscribers.subscribedate AS subscribedate',
			', subscribers.format AS format',
			', subscribers.unsubscribed AS unsubscribed',
			', subscribers.bounced AS bounced',
			', subscribers.confirmed AS confirmed',
			', subscribers.listid AS listid'
			);


			// Adding list names, as it is always going to be required
			$tables['lists'] = '';
			$tables['subscribers'] .=	' JOIN ' . SENDSTUDIO_TABLEPREFIX . 'lists AS lists'
			. ' ON lists.listid = subscribers.listid'
			. ' AND lists.listid IN (' . implode(', ', $listIDs) . ')';
			array_push($columns, ', lists.name AS listname');

			// Get custom fields cache used by this segment
			if (is_null($this->_cacheCustomfields)) {
				require_once(dirname(__FILE__) . '/lists.php');

				$listAPI = new Lists_API();
				$temp = $listAPI->GetCustomFields($listIDs);
				$this->_cacheCustomfields = array();
				if (is_array($temp)) {
					foreach ($temp as $each) {
						$this->_cacheCustomfields[$each['fieldid']] = $each['fieldtype'];
					}
				}
			}

			$status = $this->_GenerateSegmentRuleQuery($rules, 'AND', $tables, $joins, $conditions, $listIDs);
			if ($status === false) {
				return false;
			}

			$tempQuery = 	' FROM ' . implode(' ', $tables)
			. ' WHERE ' . (count($joins) > 0? implode(' AND ', $joins) . ' AND ' : '') . implode(' ', $conditions);

			return array(	'selectQuery' => 'SELECT DISTINCT ' . implode(' ', $columns) . $tempQuery,
						'countQuery' => 'SELECT COUNT(DISTINCT subscribers.subscriberid) AS count' . $tempQuery);
	}

	/**
	 * FetchSubscribersFromSegment
	 * Returns all subscriber information based on "Segment" descriptor
	 *
	 * @param Int $pageid Which 'page' of results to return. Used with perpage it handles paging of results.
	 * @param Mixed $perpage How many results to return (int or 'all').
	 * @param Mixed $segments Segment ID(s) of which subscribers are going to be fetched (Integer|Array)
	 * @param Array $sortdetails How to sort the resulting subscriber information (OPTIONAL)
	 * @param String $searchemail Search for this particular email within the segment
	 *
	 * @return Array Returns an empty array if there is no search info or no subscribers found. Otherwise returns subscriber info based on the criteria.
	 *
	 * @uses Segment_API::GetSearchInfo()
	 * @uses Segment_API::ReplaceLists()
	 * @uses Segment_API::ReplaceRules()
	 * @uses Segment_API::AppendRule()
	 * @uses Segment_API::GetSubscribersQueryString()
	 * @uses Segment_API::GetSubscribersCount()
	 * @uses Db::AddLimit()
	 * @uses Db::Query()
	 * @uses Db::Fetch()
	 */
	function FetchSubscribersFromSegment($pageid=1, $perpage=20, $segments, $sortdetails=array(), $searchemail = null)
	{
		if ($pageid < 1) {
			$pageid = 1;
		}

		if ($perpage <= 0 && $perpage != 'all') {
			$perpage = 20;
		}

		if (empty($sortdetails)) {
			$sortdetails = array('SortBy' => 'emailaddress', 'Direction' => 'asc');
		}

		require_once(dirname(__FILE__) . '/segment.php');

		$return = array('count' => 0, 'subscriberlist' => array());
		$segmentAPI = new Segment_API();

		/**
		 * Construct new segment if more than one segment ID is parsed in,
		 * otherwise, load segment ID into the API
		 */
		if (is_array($segments)) {
			$segmentInfo = $segmentAPI->GetSearchInfo($segments);
			if ($segmentInfo === false) {
				trigger_error('Cannot get segment search info', E_USER_WARNING);
				return array();
			}

			$status = $segmentAPI->ReplaceLists($segmentInfo['Lists']);
			if ($status === false) {
				trigger_error('Cannot replace segment lists', E_USER_WARNING);
				return array();
			}

			$status = $segmentAPI->ReplaceRules($segmentInfo['Rules']);
			if ($status === false) {
				trigger_error('Cannot replace segment rules', E_USER_WARNING);
				return array();
			}
		} else {
			$segments = intval($segments);
			if ($segments == 0) {
				trigger_error('Invalid Segment ID was passed in', E_USER_WARNING);
				return array();
			}

			$status = $segmentAPI->Load($segments);
			if (!$status) {
				trigger_error('Cannot load Segment', E_USER_WARNING);
				return array();
			}
		}

		if (!is_null($searchemail)) {
			$status = $segmentAPI->AppendRule('AND', array('ruleName' => 'email', 'ruleOperator' => 'like', 'ruleValues' => array($searchemail)));
			if ($status === false) {
				return array();
			}
		}

		$return['count'] = $segmentAPI->GetSubscribersCount();

		$selectQuery = $segmentAPI->GetSubscribersQueryString();

		/**
		 * Add in sort details
		 */
		$selectQuery .= ' ORDER BY ';

		if (strtolower($sortdetails['SortBy']) == 'sd.data') {
			$selectQuery = preg_replace(	'/^(SELECT .*? FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers AS subscribers)(.*)/i',
			('$1 LEFT JOIN ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data AS subscriberdata'
			. ' ON subscribers.subscriberid = subscriberdata.subscriberid AND subscriberdata.fieldid=' . intval($sortdetails['CustomFields'][0]) . ' $2'),
			$selectQuery);

			$selectQuery .= 'subscriberdata.data';

		} elseif (strtolower($sortdetails['SortBy']) == 'status') {
			$selectQuery .= 'CASE WHEN (bounced=0 AND unsubscribed=0) THEN 1 WHEN (unsubscribed > 0) THEN 2 WHEN (bounced > 0) THEN 3 END';

		} else {
			$selectQuery .= $sortdetails['SortBy'];
		}
		$selectQuery .= (strtolower($sortdetails['Direction']) == 'asc') ? ' asc ' : ' desc ';
		$selectQuery .= (strtolower($sortdetails['SortBy']) != 'emailaddress') ? ', emailaddress' : '';
		/**
		 * -----
		 */

		// Add pagination
		if ($perpage != 'all') {
			$selectQuery .= $this->Db->AddLimit((($pageid - 1) * $perpage), $perpage);
		}

		/**
		 * Get subscriber records
		 */
		$tempRecords = array();
		$result = $this->Db->Query($selectQuery);
		while ($row = $this->Db->Fetch($result)) {
			array_push($tempRecords, $row);
		}

		$return['subscriberlist'] = $tempRecords;
		/**
		 * -----
		 */

		return $return;
	}

	/**
	 * _GenerateSegmentRuleQuery
	 * Generate segment rule query by populating "tables", "joins", and "conditions" that needed to be added to the query
	 *
	 * @param Array $rules Segment rule
	 * @param String $connector Connector for the query (this can be 'AND' or 'OR')
	 * @param Array &$tables A reference to an array that will contains "tables" that needed to be added to the query string
	 * @param Array &$joins A reference to an array that will contains "join" condition that needed to be added to the query string
	 * @param Array &$conditions A reference to an array that will contains "where" conditions that needed to be added to the query string
	 * @param Array $listids An array of list ID
	 *
	 * @return Boolean Returns TRUE if process was successful, FALSE otherwise
	 *
	 * @uses SENDSTUDIO_TABLEPREFIX
	 * @uses Subscribers_API::_GenerateSegmentRuleQuery()
	 * @uses Subscribers_API::_TranslateSegmentOperator()
	 * @uses Subscribers_API::_GenerateSegmentRuleQuery_Customfield()
	 * @uses Db::Quote()
	 * @uses Db::Query()
	 * @uses Db::FreeResult()
	 * @uses API::_subqueryCapable()
	 *
	 * @access private
	 */
	function _GenerateSegmentRuleQuery($rules, $connector, &$tables, &$joins, &$conditions, $listids)
	{
		$firstCondition = true;
		$previousConnector = $connector;

		array_push($conditions, $connector . ' (');

		foreach ($rules as $eachRule) {
			if (!$firstCondition) {
				array_push($conditions, $previousConnector);
			}

			switch ($eachRule['type']) {
				case 'group':
					$status = $this->_GenerateSegmentRuleQuery($eachRule['rules'], '', $tables, $joins, $conditions, $listids);
					if ($status === false) {
						trigger_error('Failed to process rule grouping', E_USER_WARNING);
						return false;
					}
					break;

				case 'rule':
					// Numeric "ruleName" means the rule is using a custom field.
					if (is_numeric($eachRule['rules']['ruleName'])) {
						$operator = $eachRule['rules']['ruleOperator'];

						/**
						 * The operator: isempty and isnotempty; will check whether or not
						 * a contact has filled in a praticualar custom field.
						 */
						switch ($operator) {
							case 'isempty':
								$tempQuery = 	'SELECT s.subscriberid AS subscriberid'
								. ' FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers s'
								. ' LEFT JOIN ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data d'
								. ' ON s.subscriberid = d.subscriberid'
								. ' AND d.fieldid = ' . intval($eachRule['rules']['ruleName'])
								. ' WHERE d.fieldid IS NULL OR d.data = ""';
								break;

							case 'isnotempty':
								$tempQuery = 	'SELECT s.subscriberid AS subscriberid'
								. ' FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscribers s'
								. ' JOIN ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data d'
								. ' ON s.subscriberid = d.subscriberid'
								. ' AND d.fieldid = ' . intval($eachRule['rules']['ruleName'])
								. ' AND d.data <> ""';
								break;

							default:
								$condition = $this->_GenerateSegmentRuleQuery_Customfield($eachRule);

								if ($condition === false) {
									trigger_error('Failed to process custom field condition', E_USER_WARNING);
									return false;
								}

								$tempQuery = 	'SELECT d.subscriberid AS subscriberid'
								. ' FROM ' . SENDSTUDIO_TABLEPREFIX . 'subscribers_data d'
								. ' WHERE ' . $condition;
								break;
						}

						$tempInclusion = 'IN';
						if (in_array($operator, array('notequalto', 'notlike'))) {
							$tempInclusion = 'NOT IN';
						}

						if ($this->_subqueryCapable()) {
							array_push($conditions, 'subscribers.subscriberid ' . $tempInclusion . ' (' . $tempQuery . ')');
						} else {
							$records = array('0');
							$rs = $this->Db->Query($tempQuery);
							while ($row = $this->Db->Fetch($rs)) {
								$records[] = (int)$row['subscriberid'];
							}

							$this->Db->FreeResult($rs);
							array_push($conditions, 'subscribers.subscriberid ' . $tempInclusion . ' ('. implode(',', $records) .')');
						}
					} else {
						switch ($eachRule['rules']['ruleName']) {
							// Filter by email
							case 'email':
								$tempValue =  $eachRule['rules']['ruleValues'][0];

								if (in_array($eachRule['rules']['ruleOperator'], array('like', 'notlike'))) {
									// Check whether or not use has already included their %, if not add % automatically
									$tempPosition = strpos($tempValue, '%');
									if (($tempPosition !== 0) && ($tempPosition !== (strlen($tempValue) - 1))) {
										$tempValue = '%' . $tempValue . '%';
									}
								}

								array_push($conditions,	'subscribers.emailaddress '
								. $this->_TranslateSegmentOperator($eachRule['rules']['ruleOperator'])
								. " '" . $this->Db->Quote($tempValue) . "'");
								break;

								// Filter by format preference
							case 'format':
								array_push($conditions, 'subscribers.format'
								. $this->_TranslateSegmentOperator($eachRule['rules']['ruleOperator'])
								. " '" . ($eachRule['rules']['ruleValues'][0] == 'h'? 'h' : 't') . "'");
								break;

								// Filter by subscriber status (active/bounced/unsubscribed)
							case 'status':
								$tempValue = false;
								$tempOperator = $eachRule['rules']['ruleOperator'];

								switch ($eachRule['rules']['ruleValues'][0]) {
									case 'b':
										if ($tempOperator == 'equalto') {
											$tempValue = 'subscribers.bounced > 0';
										} else {
											$tempValue = 'subscribers.bounced = 0';
										}
										break;

									case 'u':
										if ($tempOperator == 'equalto') {
											$tempValue = 'subscribers.unsubscribed > 0';
										} else {
											$tempValue = 'subscribers.unsubscribed = 0';
										}
										break;

									case 'a':
										if ($tempOperator == 'equalto') {
											$tempValue = '(subscribers.unsubscribed=0 AND subscribers.bounced=0)';
										} else {
											$tempValue = '(subscribers.unsubscribed<>0 OR subscribers.bounced<>0)';
										}
										break;
								}

								if ($tempValue == false) {
									trigger_error('Unknown "Status" was specified in rule', E_USER_WARNING);
									return false;
								}

								array_push($conditions, $tempValue);
								break;

								// Filter by confirmation status (confirmed or unconfirmed)
							case 'confirmation':
								array_push($conditions, 'subscribers.confirmed '
								. $this->_TranslateSegmentOperator($eachRule['rules']['ruleOperator'])
								. " '" . intval($eachRule['rules']['ruleValues'][0]) . "'");
								break;

								// Filter by subscribed date
							case 'subscribe':
								$tempOperator = $this->_TranslateSegmentOperator($eachRule['rules']['ruleOperator'], true);
								$tempValues = $eachRule['rules']['ruleValues'];

								/**
								 * Make sure that the time is always in chronological order
								 * (as there is only 2 times, we don't need to deply any sorting method --- This will do nicely)
								 */
								if (count($tempValues) == 2) {
									list($tempDay, $tempMonth, $tempYear) = explode('/', $tempValues[0]);
									$tempValue1 = mktime(null, null, null, $tempMonth, $tempDay, $tempYear);

									list($tempDay, $tempMonth, $tempYear) = explode('/', $tempValues[1]);
									$tempValue2 = mktime(null, null, null, $tempMonth, $tempDay, $tempYear);

									if ($tempValue1 < $tempValue2) {
										$tempValues = array($tempValues[0], $tempValues[1]);
									} else {
										$tempValues = array($tempValues[1], $tempValues[0]);
									}
								}
								/**
								 * -----
								 */
                                $user = IEM::userGetCurrent();
                                $DateConverter = new ConvertDate(SENDSTUDIO_SERVERTIMEZONE, $user->Get('usertimezone'));
								// Convert date into unix timestamp
								$tempTime = array();
								if (count($tempValues) == 2) {
									list($tempDay, $tempMonth, $tempYear) = explode('/', $tempValues[0]);
									array_push($tempTime, $DateConverter->ConvertToGMTFromServer(0, 0, 0, $tempMonth, $tempDay, $tempYear));

									list($tempDay, $tempMonth, $tempYear) = explode('/', $tempValues[1]);
									array_push($tempTime, $DateConverter->ConvertToGMTFromServer(23, 59, 59, $tempMonth, $tempDay, $tempYear));
								} else {
									list($tempDay, $tempMonth, $tempYear) = explode('/', $tempValues[0]);
									//array_push($tempTime, $DateConverter->ConvertToGMTFromServer(0, 0, 0, $tempMonth, $tempDay, $tempYear));     ????????????????
									array_push($tempTime, $DateConverter->ConvertToGMTFromServer(23, 59, 59, $tempMonth, $tempDay, $tempYear));
								}

								$tempCondition = array();
								foreach ($tempTime as $index => $each) {
									array_push($tempCondition, ('subscribers.subscribedate '
									. (is_array($tempOperator)? $tempOperator[$index] : $tempOperator)
									. ' ' . $each));
								}

								$tempOperator = ' AND ';
								if ($eachRule['rules']['ruleOperator'] == 'notequalto') {
									$tempOperator = ' OR ';
								}

								array_push($conditions, '(' . implode($tempOperator, $tempCondition) . ')');
								break;

								// Filter by "have clicked" or "have not clicked" link
							case 'link':
								$tempValue = intval($eachRule['rules']['ruleValues'][0]);

								if ($eachRule['rules']['ruleOperator'] == 'equalto') {
									if (!array_key_exists('lc', $tables)) {
										$tables['lc'] = '';
										$tables['subscribers'] .= ' LEFT JOIN ' . SENDSTUDIO_TABLEPREFIX . 'stats_linkclicks AS lc ON subscribers.subscriberid=lc.subscriberid';
									}

									if ($tempValue > -1) {
										array_push($conditions, 'lc.linkid=' . $tempValue);
									} else {
										array_push($conditions, 'lc.linkid IS NOT NULL');
									}
								} else {
									$query = 	'SELECT lc.subscriberid AS subscriberid FROM ' . SENDSTUDIO_TABLEPREFIX . 'stats_linkclicks AS lc'
									. (($tempValue > -1)? ' WHERE lc.linkid=' . $tempValue : '');

									if ($this->_subqueryCapable()) {
										array_push($conditions, 'subscribers.subscriberid NOT IN (' . $query . ')');
									} else {
										$records = array('0');
										$rs = $this->Db->Query($query);
										while ($row = $this->Db->Fetch($rs)) {
											$records[] = (int)$row['subscriberid'];
										}

										$this->Db->FreeResult($rs);
										array_push($conditions, 'subscribers.subscriberid NOT IN ('. implode(',', $records) .')');
									}
								}
								break;

								// Filter subsribers by "opened" and "have not opened" a campaign
							case 'campaign':
								if ($eachRule['rules']['ruleOperator'] == 'equalto') {
									if (!array_key_exists('seo', $tables)) {
										$tables['seo'] = ', [|PREFIX|]stats_emailopens AS seo';
										$tables['sn'] = 'JOIN [|PREFIX|]stats_newsletters AS sn'
										. ' ON seo.statid = sn.statid';
										array_push($joins, 'subscribers.subscriberid = seo.subscriberid');
									}

									$tempNewsletterID = intval($eachRule['rules']['ruleValues'][0]);
									if ($tempNewsletterID == -1) {
										array_push($conditions, 'sn.newsletterid <> 0');
									} else {
										array_push($conditions, 'sn.newsletterid = ' . $tempNewsletterID);
									}
								} else {
									$query =	'SELECT subscriberid FROM [|PREFIX|]stats_emailopens AS seo'
									. ', [|PREFIX|]stats_newsletters AS sn'
									. ' WHERE sn.statid=seo.statid'
									. (($eachRule['rules']['ruleValues'][0] != -1)? ' AND sn.newsletterid=' . intval($eachRule['rules']['ruleValues'][0]) : '');

									if ($this->_subqueryCapable()) {
										array_push($conditions, 'subscribers.subscriberid NOT IN (' . $query . ')');
									} else {
										$records = array('0');
										$rs = $this->Db->Query($query);
										while ($row = $this->Db->Fetch($rs)) {
											$records[] = (int)$row['subscriberid'];
										}

										$this->Db->FreeResult($rs);
										array_push($conditions, 'subscribers.subscriberid NOT IN ('. implode(',', $records) .')');
									}
								}
								break;

								// Filter subscriber by "subscriber ID"
							case 'subscriberid':
								$tempValue =  $eachRule['rules']['ruleValues'][0];

								array_push($conditions,	'subscribers.subscriberid '
								. $this->_TranslateSegmentOperator($eachRule['rules']['ruleOperator'])
								. intval($tempValue));
								break;

							default:
								trigger_error('Unknown rule name', E_USER_WARNING);
								return false;
								break;
						}
					}
					break;

				default:
					trigger_error('Unknown rule type -- Must either be group or rule', E_USER_WARNING);
					return false;
					break;
			}

			$previousConnector = $eachRule['connector'];
			$firstCondition = false;
		}

		array_push($conditions, ')');

		return true;
	}

	/**
	 * _GenerateSegmentRuleQuery_Customfield
	 * Generate "Custom Field" condition query
	 *
	 * @param Array $eachRule Segment rule
	 * @return Mixed Returns condition string if successful, FALSE otherwise
	 *
	 * @uses Subscribers_API::$_cacheCustomfields
	 * @uses Subscribers_API::_TranslateSegmentOperator()
	 * @uses Subscribers_API::_GenerateDateSubQuery()
	 * @uses Db::Quote()
	 *
	 * @access private
	 */
	function _GenerateSegmentRuleQuery_Customfield($eachRule)
	{
		$condition = false;

		// Make sure negation operator (notequalto or notlike) also includes BLANK records
		switch ($eachRule['rules']['ruleOperator']) {
			case 'notequalto':
				$eachRule['rules']['ruleOperator'] = 'equalto';
				break;

			case 'notlike':
				$eachRule['rules']['ruleOperator'] = 'like';
				break;
		}

		if (!isset($this->_cacheCustomfields[$eachRule['rules']['ruleName']])) {
			return $condition;
		}

		switch ($this->_cacheCustomfields[$eachRule['rules']['ruleName']]) {
			case 'text':
			case 'textarea':
			case 'dropdown':
			case 'radiobutton':
				// For expandibility reason, we'll keep this as an array
				$tempValue =  $eachRule['rules']['ruleValues'][0];

				if ($eachRule['rules']['ruleOperator'] == 'like') {
					// Check whether or not use has already included their %, if not add % automatically
					$tempPosition = strpos($tempValue, '%');
					if (($tempPosition !== 0) || ($tempPosition !== (strlen($tempValue) - 1))) {
						$tempValue = '%' . $tempValue . '%';
					}
				}

				$condition = 	'd.data '
				. $this->_TranslateSegmentOperator($eachRule['rules']['ruleOperator'])
				. " '" . $this->Db->Quote($tempValue) . "'"
				. ' AND d.fieldid = ' . intval($eachRule['rules']['ruleName']);
				break;

				// Number can have more than 1 values entered in the "rule values"
				// This is because number have the operator "between" that will
				// requrire 2 inputs.
			case 'number':
				if (!is_array($eachRule['rules']['ruleValues'])) {
					return false;
				}

				if ($eachRule['rules']['ruleOperator'] == 'between' && count($eachRule['rules']['ruleValues']) != 2) {
					return false;
				}

				$tempOperator = $this->_TranslateSegmentOperator($eachRule['rules']['ruleOperator']);

				// Make sure if the operator is an array, it will have the same count number as rule values
				if (is_array($tempOperator) && (count($tempOperator) != count($eachRule['rules']['ruleValues']))) {
					return false;
				}

				$tempRules = array('d.fieldid = ' . intval($eachRule['rules']['ruleName']));
				foreach ($eachRule['rules']['ruleValues'] as $tempIndex => $tempEach) {
					array_push($tempRules, ('d.data '
					. $tempOperator[$tempIndex]
					. ' ' . intval($tempEach)));
				}

				$condition = implode(' AND ', $tempRules);
				break;

				// Checkboxes can have multiple values entered in the "rule values"
				// Storage for "checkbox" will also different from the other values,
				// Whereby all of the array values are stored as a serialized array string.
			case 'checkbox':
				if (!is_array($eachRule['rules']['ruleValues'])) {
					return false;
				}

				// Because the checkbox data is stored as a serialize array,
				// the data field will not only contains an exact string of the value,
				// but it also contains other string values, so it must always use "LIKE" -- Negation is handled differently

				$tempRules = array('d.fieldid = ' . intval($eachRule['rules']['ruleName']));
				foreach ($eachRule['rules']['ruleValues'] as $tempEach) {
					array_push($tempRules, ("d.data LIKE '%" . $this->Db->Quote('s:' . strlen($tempEach) . ':"' . $tempEach . '";') . "%'"));
				}

				$condition = implode(' AND ', $tempRules);
				break;

				// Date is stored as dd/mm/yyyy in the data field.
				// Interpretation of the "date" field will be handled by self::_GenerateDateSubQuery()
				// But because it does not understand "segment" structure, we need to pass
				// compatible data structure to the method.
			case 'date':
				if (!is_array($eachRule['rules']['ruleValues'])) {
					return false;
				}

				if ($eachRule['rules']['ruleOperator'] == 'between' && count($eachRule['rules']['ruleValues']) != 2) {
					return false;
				}

				/**
				 * Make sure that the time is always in chronological order
				 * (as there is only 2 times, we don't need to deply any sorting method --- This will do nicely)
				 */
				$dates = array();
				$prevTime = 0;
				foreach ($eachRule['rules']['ruleValues'] as $each) {
					list($tempDay, $tempMonth, $tempYear) = explode('/', $each);

					$tempDay = intval($tempDay);
					$tempMonth = intval($tempMonth);
					$tempYear = intval($tempYear);

					$tempTime = mktime(null, null, null, $tempMonth, $tempDay, $tempYear);

					if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
						$tempSQL = "TO_DATE('{$tempDay}/{$tempMonth}/{$tempYear}', 'DD/MM/YYYY')";
					} else {
						$tempSQL = "STR_TO_DATE('{$tempDay}/{$tempMonth}/{$tempYear}', '%d/%m/%Y')";
					}

					if ($prevTime < $tempTime) {
						array_push($dates, $tempSQL);
					} else {
						array_unshift($dates, $tempSQL);
					}

					$prevTime = $tempTime;
				}

				// ----- Create query
				if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
					$tempSQL = "TO_DATE(d.data, 'DD/MM/YYYY')";
				} else {
					$tempSQL = "STR_TO_DATE(d.data, '%d/%m/%Y')";
				}

				switch ($eachRule['rules']['ruleOperator']) {
					case 'equalto':
					case 'notequalto':
						$condition = "{$tempSQL} = {$dates[0]}";
						break;

					case 'greaterthan':
						$condition = "{$tempSQL} > {$dates[0]}";
						break;

					case 'lessthan':
						$condition = "{$tempSQL} < {$dates[0]}";
						break;

					case 'between':
						$condition = "{$tempSQL} >= {$dates[0]} AND {$tempSQL} <= {$dates[1]}";
						break;

						// Unknown operator
					default:
						return false;
						break;
				}
				// -----

				$fieldid = intval($eachRule['rules']['ruleName']);
				$condition = "(d.fieldid = {$fieldid} AND {$condition})";
				break;
		}

		return $condition;
	}

	/**
	 * _TranslateSegmentOperator
	 * Translate segment operator into SQL operator
	 *
	 * The $date parameter will make sure that the operator is returning the correct operator
	 * for date comparison... This is because SendStudio store dates as timestamp, so an equal operator
	 * need to return an array('>', '<') -- Must be grater than start of day and less than end of day
	 *
	 * @param String $operator Segment operator
	 * @param Boolean $date Specify whether or not this operatior is for date comparison (OPTIONAL, default = FALSE)
	 * @return Mixed Returns string or array of SQL operator if successful, FALSE otherwise
	 *
	 * @access private
	 */
	function _TranslateSegmentOperator($operator, $date = false)
	{
		switch ($operator) {
			case 'equalto':
				if ($date) {
					return array('>', '<');
				} else {
					return '=';
				}
				break;

			case 'notequalto':
				if ($date) {
					return array('<', '>');
				} else {
					return '<>';
				}
				break;

			case 'like':
				return (SENDSTUDIO_DATABASE_TYPE == 'pgsql'? 'ILIKE' : 'LIKE');
				break;

			case 'notlike':
				return (SENDSTUDIO_DATABASE_TYPE == 'pgsql'? 'NOT ILIKE' : 'NOT LIKE');
				break;

			case 'between':
				return array('>', '<');
				break;

			case 'greaterthan':
				return '>';
				break;

			case 'lessthan':
				return '<';
				break;

			default:
				return false;
				break;
		}
	}

	/**
	 * CountEvents
	 * Counts the number of events for a subscriber
	 *
	 * @param Int $subscriberid The subscriberid to check events for. If not subsciberid is given it will check $this->subsciberid
	 *
	 * @return Int Returns the number of events or 0 if there are none
	 */
	function CountEvents($subscriberid = 0)
	{
		if ($subscriberid == 0) {
			$subscriberid = $this->subscriberid;
		}

		$subscriberid = (int)$subscriberid;

		if ($subscriberid == 0) {
			return array(false,'No subscriberid specified');
		}

		$query = 'SELECT COUNT(eventid) as count FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscriber_events WHERE subscriberid = ' . $subscriberid;

		$result = $this->Db->Query($query);

		$count = $this->Db->FetchOne($result,'count');

		return $count;
	}

	/**
	 * GetEvents
	 * Fetches the list of events for a subscriber
	 *
	 * @param Int $subscriberid The subscriberid to check events for. If not subsciberid is given it will check $this->subsciberid
	 * @param Int $pageid The page to start retrieving results from. This is ignored if perpage is set to 'all'
	 * @param Int $perpage The number of entries per page. Specify 'all' to retrieve all results.
	 * @param Array $search_info Restrict results to certain values. Key names should be the column names. Example:
	 * array(
	 * 	'eventtype' => 'Email', 'listid' => 4
	 * )
	 * Specify an array to select multiple values:
	 * array(
	 * 	'eventtype' => array('Email','Phone Call')
	 * )
	 * Date restrictions are specified using the 'restrictions' key:
	 * array(
	 * 	'restrictions' => 'eventdate >= 1216250000 AND eventdate < 1216252570'
	 * )
	 * @param Array $sort_details Column and direction to sort the results by
	 * @param Boolean $count_only Specify true to return the number of events. Specify false (default) to return a list of events
	 *
	 * @return Array Returns an array of events and their parameters
	 */
	function GetEvents($subscriberid = 0,$pageid=1,$perpage=20,$search_info = false,$sort_details = array(),$count_only = false)
	{
		if ($subscriberid == 0) {
			$subscriberid = $this->subscriberid;
		}

		$subscriberid = (int)$subscriberid;

		if ($subscriberid == 0) {
			return array(false,'No subscriberid specified');
		}

		$search_query = array();
		if (is_array($search_info)) {
			foreach ($search_info as $key => $val) {
				if ($key == 'restrictions') {
					$search_query[] = $val;
				} else {
					$part = "$key ";
					if (is_array($val)) {
						$part .= " IN (";
						$parts = array();
						foreach ($val as $subval) {
							$parts[] = "'" . $this->Db->Quote($subval) . "'";
						}
						$part .= implode(',',$parts);
						$part .= ")";
					} else {
						$part .= " = '" . $this->Db->Quote($val) . "'";
					}
					$search_query[] = $part;
				}
			}
		}
		if (!count($search_query)) {
			$search_query = "";
		} else {
			$search_query = " AND " . implode(" AND ",$search_query);
		}

		if ($count_only) {
			$count_query = 'SELECT count(se.eventid) as count FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscriber_events se WHERE se.subscriberid = ' . $subscriberid . $search_query;
			$result = $this->Db->Query($count_query);
			return $this->Db->FetchOne($result,'count');
		}

		if ($perpage == 'all') {
			$perpage = 0;
			$offset = 0;
		} else {
			$perpage = (int)$perpage;
			$offset = ($pageid - 1) * $perpage;
		}

		if (!in_array($sort_details['SortBy'],$this->ValidEventSorts)) {
			$sortby = 'lastupdate';
		} else {
			$sortby = $sort_details['SortBy'];
		}
		if (!in_array(strtolower($sort_details['Direction']),array('asc','desc'))) {
			$direction = 'desc';
		} else {
			$direction = $sort_details['Direction'];
		}

		$query = 'SELECT se.*,u.username as username FROM ' . SENDSTUDIO_TABLEPREFIX . 'list_subscriber_events se LEFT JOIN ' . SENDSTUDIO_TABLEPREFIX . 'users u ON se.eventownerid = u.userid WHERE se.subscriberid = ' . $subscriberid . $search_query . " ORDER BY " . $sortby . " " . $direction;
		if ($perpage) {
			$query .= " LIMIT $perpage";
		}
		if ($offset) {
			$query .= " OFFSET $offset";
		}

		$result = $this->Db->Query($query);

		$events = array();
		while ($row = $this->Db->Fetch($result)) {
			$events[] = $row;
		}

		return $events;
	}

	/**
	 * AddEvent
	 * Adds an event for a subscriber
	 *
	 * @param Int $subscriberid The subscriberid to check events for. If not subsciberid is given it will check $this->subsciberid
	 * @param Int $listid The subscriberid to check events for. If not subsciberid is given it will check $this->subsciberid
	 * @param Int $event The event to add, must have elements: type, eventdate, notes
	 * @param Int $specificuser Specific user thats going to be added as the owner of the event
	 *
	 * @return Mixed Returns nothing on success, returns an array on failure
	 */
	function AddEvent($subscriberid, $listid, $event, $specificuser = 0)
	{
		if ($subscriberid == 0) {
			$subscriberid = $this->subscriberid;
		}
		if ($listid == 0) {
			$listid = $this->listid;
		}

		$subscriberid = (int)$subscriberid;
		$listid = (int)$listid;
		$specificuser = intval($specificuser);

		if ($subscriberid == 0) {
			return array(false,'No subscriberid specified');
		} elseif ($listid == 0) {
			return array(false,'No listid specified');
		} elseif (!$this->IsSubscriberOnList('',$listid,$subscriberid)) {
			return array(false,'Subscriber ' . $subscriberid . 'is not on list ' . $listid);
		}

		$userid = $specificuser;
		if (empty($userid)) {
			$user = &GetUser();
			if ($user) {
				$userid = $user->userid;
			}
		}

		$lastupdate = $this->GetServerTime();

		$eventType = $this->Db->Quote($event['type']);
		$eventDate = intval($event['eventdate']);
		$eventNote = $this->Db->Quote($event['notes']);
		$eventSubject = $this->Db->Quote($event['subject']);

		$query = "
			INSERT INTO [|PREFIX|]list_subscriber_events (subscriberid, listid, eventtype, eventdate, eventownerid, eventnotes, eventsubject, lastupdate)
			VALUES ({$subscriberid}, {$listid}, '{$eventType}', {$eventDate}, {$userid}, '{$eventNote}', '{$eventSubject}', {$lastupdate})
		";

		$this->Db->Query($query);
	}

	/**
	 * UpdateEvent
	 * Updates an event with new values
	 *
	 * @param Int $subscriberid The subscriberid to check events for. If not subsciberid is given it will check $this->subsciberid
	 * @param Int $eventid The eventid to update
	 * @param Int $event The event values to update the event with, must have elements: type, eventdate, notes
	 *
	 * @return Mixed Returns nothing on success, returns an array on failure
	 */
	function UpdateEvent($subscriberid,$eventid,$event)
	{
		if ($subscriberid == 0) {
			$subscriberid = $this->subscriberid;
		}
		$eventid = (int)$eventid;

		if ($subscriberid == 0) {
			return array(false,'No subscriberid specified');
		} elseif ($eventid == 0) {
			return array(false,'No eventid specified');
		}

		$query = "SELECT COUNT(*) as count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_events WHERE subscriberid = " . $subscriberid . " AND eventid = " . $eventid . " LIMIT 1";
		$result = $this->Db->Query($query);
		$count = $this->Db->FetchOne($result,'count');
		if (!$count) {
			return array(false,'Event ' . $eventid . ' does not exist for subscriber ' . $subscriberid);
		}

		$lastupdate = $this->GetServerTime();

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_events SET eventtype = '" . $this->Db->Quote($event['type']) . "',eventdate = " . (int)$event['eventdate'] . ", eventnotes = '" . $this->Db->Quote($event['notes']) . "', eventsubject = '" . $this->Db->Quote($event['subject']) . "', lastupdate = " . $lastupdate . " WHERE  subscriberid = " . $subscriberid . " AND eventid = " . $eventid;
		$this->Db->Query($query);
	}

	/**
	 * DeleteEvent
	 * Deletes an event
	 *
	 * @param Int $subscriberid The subscriberid to check events for. If not subsciberid is given it will check $this->subsciberid
	 * @param Int $eventid The eventid to update. This can be an array of eventids.
	 *
	 * @return Mixed Returns nothing on success, returns an array on failure
	 */
	function DeleteEvent($subscriberid,$eventid)
	{
		if ($subscriberid == 0) {
			$subscriberid = $this->subscriberid;
		}

		if (is_array($eventid)) {
			foreach ($eventid as $key => $val) {
				$eventid[$key] = (int)$val;
			}
			$event_sql = "eventid IN (" . implode(',',$eventid) . ")";
		} else {
			$eventid = (int)$eventid;
			$event_sql = "eventid = $eventid";
		}

		if ($subscriberid == 0) {
			return array(false,'No subscriberid specified');
		} elseif (!is_array($eventid) && $eventid == 0) {
			return array(false,'No eventid specified');
		}

		$query = "SELECT COUNT(*) as count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_events WHERE subscriberid = " . $subscriberid . " AND " . $event_sql . " LIMIT 1";
		$result = $this->Db->Query($query);
		$count = $this->Db->FetchOne($result,'count');
		if (!$count) {
			return array(false,'Event ' . $eventid . ' does not exist for subscriber ' . $subscriberid);
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_events  WHERE  subscriberid = " . $subscriberid . " AND " . $event_sql;

		$this->Db->Query($query);
	}

	/**
	 * CheckPermission
	 * Check if user have access to subscribers
	 *
	 * @param Integer $userid User ID that we want to check the permission against
	 * @param Mixed $subscribers Subscribers ID that needed to be checked against subscriber's permission
	 * @return Boolean Returns TRUE if user have access, FALSE otherwise
	 */
	function CheckPermission($userid, $subscribers)
	{
		$userid      = intval($userid);
		$user        = API_USERS::getRecordById($userid);
		$checkedUser = &GetUser($userid);

		if ($checkedUser->Admin() || $checkedUser->ListAdmin() || $checkedUser->ListAdminType() == 'a') {
			return true;
		}

		$tablePrefix = SENDSTUDIO_TABLEPREFIX;

		if (!is_array($subscribers)) {
			$subscribers = array($subscribers);
		}

		$subscribers = $this->CheckIntVars($subscribers);
		$subscribers = array_unique($subscribers);

		if (empty($subscribers)) {
			return false;
		}

		$implodedSubscribers = implode(',', $subscribers);

		$query = trim("
			SELECT
				l.ownerid AS ownerid, ac.groupid AS groupid

			FROM
			{$tablePrefix}list_subscribers AS ls

			JOIN {$tablePrefix}lists AS l
			ON (
				ls.listid           =  l.listid
				AND ls.subscriberid IN ({$implodedSubscribers})
			)

			LEFT JOIN {$tablePrefix}usergroups_access AS ac
			ON (
				l.listid        = ac.resourceid            AND
				ac.groupid      = {$user->groupid}
			)
		");
			$result = $this->Db->Query($query);

			if (!$result) {
				list($msg, $errno) = $this->Db->GetError();

				trigger_error($msg, $errno);

				return false;
			}

			$row_count = 0;

			while ($row = $this->Db->Fetch($result)) {
				if ($row['ownerid'] != $userid && $row['groupid'] != $user->groupid) {
					$row_count = 0;

					break;
				}

				++$row_count;
			}

			$this->Db->FreeResult($result);

			return ($row_count >= count($subscribers));
	}

	/**
	 * GetCIOp
	 * Returns the operator used in SQL for case-insensitive comparisons, depending on which database is being used.
	 * This is particularly useful for comparing email addresses.
	 *
	 * @see IsDuplicate
	 * @see IsSubscriberOnList
	 *
	 * @return String The operator used for case-insensitive comparison in S
	 */
	function GetCIOp()
	{
		// We can't just use something like LOWER(emailaddress) = strtolower($emailaddress) because it can be quite slow.
		// In MySQL, just using '=' will work on a case-insensitive collation.
		$op = '=';
		// For PostgreSQL we can use ILIKE.
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$op = 'ILIKE';
		}
		return $op;
	}

	/**
	 * GetSubscribersListByStatOwner
	 * Get the subscriber and lists based on the statistic id and the list owner
	 *
	 *
	 * @see Integer $statId Statistic Id
	 * @see Integer $subscriberId Subcriber Id
	 * @see Integer $statstype Statistic Type
	 *
	 * @return Array The list of the contact lists and subscribers.
	 */
	function GetSubscribersListByStatOwner($statId = 0, $subscriberId = 0, $statstype = 'newsletter')
	{
		if (!$statId || !$subscriberId) {
			return false;
		}

		$email = $this->GetEmailForSubscriber($subscriberId);

		// get the owner id of the list
		if ($statstype == 'newsletter') {
			$query = " select DISTINCT l.ownerid"
			. " from " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters as sn "
			. " join " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists as snl on (sn.statid = snl.statid and sn.statid = '".$this->Db->Quote($statId)."') "
			. " join " . SENDSTUDIO_TABLEPREFIX . "lists as l on snl.listid = l.listid "
			;
		} else if ($statstype == 'auto') {
			$query = " select distinct t.ownerid"
			. " from " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders as a"
			. " join " . SENDSTUDIO_TABLEPREFIX . "autoresponders as t on (a.autoresponderid = t.autoresponderid and a.statid = '".$this->Db->Quote($statId)."')"
			;
		} else {
			return false;
		}

		$result = $this->Db->Query($query);

		$ownerIds = array();
		$ownerQryStr = '';
		while ($row = $this->Db->Fetch($result)) {
			$ownerIds[] = $row['ownerid'];
		}
		$ownerQryStr = implode("','", $ownerIds);

		$query = "SELECT DISTINCT l.listid, ls.subscriberid"
		. " FROM " . SENDSTUDIO_TABLEPREFIX . "lists l"
		. " JOIN " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls ON (l.listid = ls.listid AND l.ownerid IN ('".$ownerQryStr."') and ls.unsubscribed = '0' AND ls.emailaddress = '" . $this->Db->Quote($email) . "')"
		;

		$result = $this->Db->Query($query);

		$ids = array();
		while ($row = $this->Db->Fetch($result)) {
			$ids[] = array('listid' => $row['listid'], 'subscriberid' => $row['subscriberid']);
		}
		return $ids;
	}
}