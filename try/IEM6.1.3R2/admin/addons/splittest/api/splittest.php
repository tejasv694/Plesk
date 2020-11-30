<?php
/**
 * This is the api file for split tests to use.
 * It allows you to create, update, delete, load a split test.
 *
 * @package SendStudio
 * @subpackage SplitTests
 */

/**
 * This is the split tests api class.
 * It handles all of the database stuff (creating/loading/updating/searching).
 *
 * @uses IEM::getDatabase()
 *
 * @package SendStudio
 * @subpackage SplitTests
 */
class SplitTest_API extends API
{
	/**
	 * $db
	 * A local database connection
	 *
	 * @see __construct
	 */
	private $db;

	/**
	 * $valid_split_types
	 * An array of valid split types.
	 * The api uses this to make sure you're not trying to create another split test type.
	 *
	 * If the splittype is 'distributed',
	 * when the split test is sent to a contact list, the list is broken into portions
	 * Each portion is sent a different email campaign
	 *
	 * So if you have 100 contacts and you choose 4 different email campaigns,
	 * 25 contacts will receive each campaign.
	 *
	 * If the splittype is 'percentage', then the splitdetails are:
	 * - what percentage of your list to initially email (eg 10%)
	 * - how long to wait to work out results of the initial test (eg 6 hours)
	 *
	 * @see Create
	 * @see Save
	 */
	private $valid_split_types = array ('distributed', 'percentage');

	/**
	 * $weight_measures
	 * An array of valid weight measures.
	 * The api uses this to make sure you're not trying to "weight" a winner by another means.
	 * It can also be used by the calling code to provide some checking on that end.
	 *
	 * @usedby GetWeightMeasures
	 * @usedby Create
	 * @usedby Save
	 */
	private $weight_measures = array ('openrate', 'linkclick');

	/**
	 * __construct
	 * Sets up the database connection for easy use.
	 *
	 * @uses IEM::getDatabase()
	 * @see db
	 */
	public function __construct()
	{
		$this->db = IEM::getDatabase();
	}

	/**
	 * Create
	 * Create a new split test in the database.
	 *
	 * The details are all passed in as an array:
	 * <code>
	 * $split_campaign_details = array (
	 * 	'splitname' => 'My split test campaign name',
	 * 	'splittest_campaigns' => array (
	 * 			1,
	 * 			2,
	 * 			3
	 * 		),
	 * 	'userid' => 0,
	 * 	'splittype' => 'distributed',
	 * 	'splitdetails' => array (
	 * 		'percentage' => 0,
	 * 		'hoursafter' => 0,
	 * 		'weights' => array (
	 * 			'openrate' => 0,
	 * 			'linkclick' => 0
	 * 		),
	 * 	),
	 * );
	 * </code>
	 *
	 * splittest_campaigns is an array of campaign id's to include in the split test.
	 * userid is the id of the user creating the campaign.
	 *
	 * The splittype can be 'distributed' which means send the email campaigns evenly to a list (or as close as possible)
	 * It can also be 'percentage' which means you send to the first X% of a list,
	 * then pause, work out the "best performing" campaign and send that to the rest of the list.
	 *
	 * If it's a percentage split test campaign, the split details array must contain:
	 * - percentage (starting percentage)
	 * - hoursafter (how long to delay sending the rest of the emails for)
	 * - an array of 'weights' including the openrate/linkclick rates.
	 * Weights are used in calculating which is the better performing newsletter.
	 *
	 * @param Array $split_campaign_details The details for the split campaign as an array.
	 *
	 * @uses valid_split_types
	 * @uses weight_measures
	 *
	 * @return False if any of the required details are missing.
	 */
	public function Create($split_campaign_details = array())
	{
		$required_fields = array ('splitname', 'splittest_campaigns', 'userid', 'splittype', 'splitdetails');
		foreach ($required_fields as $field) {
			if (!isset($split_campaign_details[$field])) {
				return false;
			}
		}

		$split_type = $split_campaign_details['splittype'];
		if (!in_array($split_type, $this->valid_split_types)) {
			return false;
		}

		if ($split_campaign_details['splittype'] == 'percentage') {
			$required_details = array ('percentage', 'hoursafter');
			foreach ($required_details as $field) {
				if (!isset($split_campaign_details['splitdetails'][$field])) {
					return false;
				}
				$val = (int)$split_campaign_details['splitdetails'][$field];
				if ($val <= 0) {
					return false;
				}
			}
		}

		/**
		 * Make sure "weights" have been supplied.
		 */
		if (!isset($split_campaign_details['splitdetails']['weights'])) {
			return false;
		}

		$weights = $split_campaign_details['splitdetails']['weights'];
		if (empty($weights)) {
			return false;
		}

		$weight_types = array_keys($weights);

		/**
		 * Check that the weight_types are all valid.
		 */
		$diff = array_diff($weight_types, $this->weight_measures);
		if (!empty($diff)) {
			return false;
		}

		/**
		 * Check that all $this->weight_measures have been passed through.
		 */
		$diff = array_diff($this->weight_measures, $weight_types);
		if (!empty($diff)) {
			return false;
		}

		/**
		 * Weights *can* have 0 values, eg:
		 * openrate = 100%
		 * clickthrough rate = 0%
		 * so everything is based on openrate when working out the "best" campaign to send.
		 *
		 * So the check for valid data is slightly different.
		 */
		$total_weight = 0;
		foreach ($weights as $weight_value) {
			$weight_value = (int)$weight_value;
			if ($weight_value < 0 || $weight_value > 100) {
				return false;
			}
			$total_weight += $weight_value;
		}

		/**
		 * Make sure the total weights for everything is 100%.
		 * Could do an array_sum here but since we're looping over the weights to check the values,
		 * we'll just add it up as we go.
		 */
		if ($total_weight != 100) {
			return false;
		}

		/**
		 * Check the userid is a valid id.
		 */
		$userid = (int)$split_campaign_details['userid'];
		if ($userid <= 0) {
			return false;
		}

		$timenow = $this->GetServerTime();

		$this->db->StartTransaction();

		$query = "INSERT INTO [|PREFIX|]splittests";
		$query .= " (splitname, splittype, splitdetails, createdate, userid)";
		$query .= " VALUES";
		$query .= " ('" . $this->db->Quote($split_campaign_details['splitname']) . "', '" . $this->db->Quote($split_campaign_details['splittype']) . "', '" . $this->db->Quote(serialize($split_campaign_details['splitdetails'])) . "', " . $timenow . ", " . $userid . ")";

		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		$split_id = $this->db->LastId('[|PREFIX|]splittests_sequence');

		foreach ($split_campaign_details['splittest_campaigns'] as $campaignid) {
			$id = (int)$campaignid;
			if ($id <= 0) {
				$this->db->RollBackTransaction();
				return false;
			}
			$query = "INSERT INTO [|PREFIX|]splittest_campaigns (splitid, campaignid) VALUES (" . $split_id . ", " . $id . ")";
			$result = $this->db->Query($query);
			if (!$result) {
				$this->db->RollBackTransaction();
				return false;
			}
		}

		$this->db->CommitTransaction();
		return $split_id;
	}

	/**
	 * Copy
	 * This copies a split test campaign almost exactly
	 * The only things that aren't copied are:
	 * - the "name" - which gets a prefix ("CopyPrefix" language variable).
	 * - the create time (gets set to "now")
	 * - who created the split test campaign (passed in)
	 *
	 * @param Int $old_id The old split test id to copy
	 * @param Int $copied_by Who is copying the split test so it can be correctly assigned.
	 *
	 * @return Boolean Returns false if invalid id's are passed through, or if anything goes wrong in the copy database queries.
	 * Otherwise if everything is OK, returns the new split test name.
	 */
	public function Copy($old_id=0, $copied_by=0)
	{
		$old_id = (int)$old_id;
		if ($old_id <= 0) {
			return false;
		}

		$copied_by = (int)$copied_by;
		if ($copied_by <= 0) {
			return false;
		}

		$timenow = $this->GetServerTime();

		$this->db->StartTransaction();

		$query = "INSERT INTO [|PREFIX|]splittests";
		$query .= " (splitname, splittype, splitdetails, createdate, userid)";
		$query .= " SELECT " . $this->db->Concat("'". GetLang('CopyPrefix'). "'", 'splitname') . " AS splitname, splittype, splitdetails, " . $timenow . ", " . $copied_by;
		$query .= " FROM [|PREFIX|]splittests WHERE splitid=" . $old_id;

		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		$split_id = $this->db->LastId('[|PREFIX|]splittests_sequence');
		if ($split_id <= 0) {
			$this->db->RollBackTransaction();
			return false;
		}

		$query = "INSERT INTO [|PREFIX|]splittest_campaigns (splitid, campaignid) ";
		$query .= " SELECT " . $split_id . ", campaignid";
		$query .= " FROM [|PREFIX|]splittest_campaigns WHERE splitid=" . $old_id;

		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		$this->db->CommitTransaction();

		$query = "SELECT splitname FROM [|PREFIX|]splittests WHERE splitid=" . intval($split_id);
		return $this->db->FetchOne($query);
	}

	/**
	 * Delete
	 * Deletes a split test from the database.
	 * It can only do one at a time as it checks each one to make sure there are no "jobs" left over that are:
	 * - in progress
	 * - waiting to be sent
	 * - paused
	 *
	 * If they are any of those statuses, the job needs to be cleaned up first.
	 *
	 * This is done as a separate action as user credits need to be re-allocated depending on the job's status and where it's up to.
	 * For example, a job that has sent 100 out of 1,000 emails can re-credit the user with 900 emails.
	 *
	 * @param Int $splitid The split test campaign to delete.
	 *
	 * @return Boolean Returns true if the id was deleted from the database, otherwise false.
	 */
	public function Delete($splitid=0)
	{
		$splitid = (int)$splitid;
		if ($splitid <= 0) {
			return false;
		}

		$this->db->StartTransaction();

		$query = "SELECT jobstatus FROM [|PREFIX|]splittests WHERE splitid=" . $splitid;
		$jobstatus = $this->db->FetchOne($query);
		if (!in_array($jobstatus, array ('c', 'w', null, '', false))) {
			return false;
		}

		/**
		 * Clean up any completed jobs for these split tests.
		 */
		$query = "DELETE FROM [|PREFIX|]jobs WHERE jobtype='splittest' AND jobid IN (SELECT jobid FROM [|PREFIX|]splittests WHERE splitid=" . $splitid . ")";
		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		$query = "DELETE FROM [|PREFIX|]splittest_campaigns WHERE splitid=" . $splitid;
		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		$query = "DELETE FROM [|PREFIX|]splittests WHERE splitid=" . $splitid;
		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		$this->db->CommitTransaction();
		return true;
	}

	/**
	 * Load a split test based on the id
	 * and return the details back to the calling object
	 *
	 * If the split test can be loaded the array looks like this:
	 * <code>
	 * $split_details = array (
	 * 	'splitid' => $splitid,
	 * 	'splitname' => 'Split test name',
	 * 	'splittest_campaigns' => array (
	 * 			'campaignid' => 'Campaign Name',
	 * 			'campaignid_2' => 'Campaign Name #2',
	 * 		),
	 * 	'splittype' => 'percentage/distributed',
	 * 	'splitdetails' => array (
	 * 		'percentage' => 0,
	 * 		'hoursafter' => 0,
	 * 		'weights' => array (
	 * 			openrate' => 0,
	 * 			'linkclick' => 0
	 * 		),
	 * 	),
	 * );
	 * </code>
	 *
	 * @param Int $splitid The split test id to load.
	 *
	 * @return Array Returns an array containing the split test details. If the id is invalid (it can't be loaded), then an empty array is returned.
	 */
	public function Load($splitid=0)
	{
		$splitid = (int)$splitid;
		if ($splitid <= 0) {
			return array();
		}

		$return = array();

		$query = "SELECT * FROM [|PREFIX|]splittests WHERE splitid=" . $splitid;
		$result = $this->db->Query($query);
		$return = $this->db->Fetch($result);

		$return['splitdetails'] = unserialize($return['splitdetails']);

		$return['splittest_campaigns'] = array();

		$query = "SELECT newsletterid, name FROM [|PREFIX|]newsletters n INNER JOIN [|PREFIX|]splittest_campaigns spt ON (n.newsletterid=spt.campaignid) WHERE spt.splitid=" . $splitid . " ORDER BY n.name ASC";
		$result = $this->db->Query($query);
		while ($row = $this->db->Fetch($result)) {
			$return['splittest_campaigns'][$row['newsletterid']] = $row['name'];
		}
		return $return;
	}

	/**
	 * Save
	 * Updates a split test campaign in the database to have new information.
	 *
	 * @param Int $splitid The split test id to update
	 * @param Array $splitdetails The split test details to use, which includes the name, split test type, campaigns to include etc.
	 *
	 * @see Create
	 *
	 * @return Boolean Returns false if the splitid is invalid or if anything goes wrong in the update process(es).
	 * Returns true if everything works.
	 */
	public function Save($splitid=0, $splitdetails=array())
	{
		$splitid = (int)$splitid;
		if ($splitid <= 0) {
			return false;
		}

		$this->db->StartTransaction();

		$query = "UPDATE [|PREFIX|]splittests SET ";
		$query .= " splitname='" . $this->db->Quote($splitdetails['splitname']) . "', ";
		$query .= " splittype='" . $this->db->Quote($splitdetails['splittype']) . "', ";
		$query .= " splitdetails='" . $this->db->Quote(serialize($splitdetails['splitdetails'])) . "' ";
		$query .= " WHERE splitid=" . $splitid;
		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		$query = "DELETE FROM [|PREFIX|]splittest_campaigns WHERE splitid=" . $splitid;
		$result = $this->db->Query($query);
		if (!$result) {
			$this->db->RollBackTransaction();
			return false;
		}

		foreach ($splitdetails['splittest_campaigns'] as $campaignid) {
			$campaignid = (int)$campaignid;
			if ($campaignid <= 0) {
				continue;
			}
			$query = "INSERT INTO [|PREFIX|]splittest_campaigns(splitid, campaignid) VALUES (" . $splitid . ", " . $campaignid . ")";
			$result = $this->db->Query($query);
			if (!$result) {
				$this->db->RollBackTransaction();
				return false;
			}
		}
		$this->db->CommitTransaction();
		return true;
	}

	/**
	 * GetSplitTests
	 * Returns an array of split test details.
	 * It also sorts the results in a particular order.
	 * The sort details include a field name and an order.
	 * The field name defaults to the splitname, but can be one of the extra fields:
	 * - createdate
	 * - splittype
	 * - lastsent
	 *
	 * The sort order has to either be 'asc' or 'desc' and defaults to 'asc'.
	 *
	 * The sort details are passed through as an array:
	 * <code>
	 * $sortinfo = array (
	 * 	'sort' => $fieldname,
	 * 	'direction' => $direction,
	 * );
	 * </code>
	 *
	 * @param Int $userid The userid who created the split tests. If set to 0, it includes all users.
	 * @param Array $sortinfo How to sort the results.
	 * @param Boolean $countonly Whether to only return a count of how many tests there are, or whether to return the array of split test details.
	 * @param Int $start The start position (passed to sql as the offset)
	 * @param Int $result_limit The number of results to return (passed to sql as the limit).
	 *
	 * @return Mixed Returns the number of split tests if you are only doing a count.
	 * Otherwise, returns an array of split test details including the name, create date, split type, last sent date, email campaign names used by the split test etc.
	 */
	public function GetSplitTests($userid=0, $sortinfo=array(), $countonly=false, $start=0, $result_limit=10)
	{
		$userid = (int)$userid;

		if ($countonly) {
			$query = "SELECT COUNT(splitid) AS count FROM [|PREFIX|]splittests";
			if ($userid > 0) {
				$query .= " WHERE userid=" . $userid;
			}
			return $this->db->FetchOne($query);
		}

		$tests = array();

		$pg_campaign_list = "array_to_string(array(SELECT name FROM [|PREFIX|]newsletters n INNER JOIN [|PREFIX|]splittest_campaigns stc ON (n.newsletterid=stc.campaignid) WHERE stc.splitid=st.splitid ORDER BY n.name ASC), ', ') AS campaign_names";

		$mysql_campaign_list = "(SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM [|PREFIX|]newsletters n INNER JOIN [|PREFIX|]splittest_campaigns stc ON (n.newsletterid=stc.campaignid) WHERE stc.splitid=st.splitid ORDER BY n.name ASC) AS campaign_names";

		$campaign_list_query = $mysql_campaign_list;
		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$campaign_list_query = $pg_campaign_list;
		}

		$count_campaign_query = "(SELECT COUNT(campaignid) FROM [|PREFIX|]splittest_campaigns c WHERE c.splitid=st.splitid) AS campaigncount";

		$query = "SELECT splitid, splitname, createdate, splittype, jobid, jobstatus, splitdetails, lastsent, " . $campaign_list_query . ", " . $count_campaign_query;
		$query .= ", CASE WHEN lastsent > 0 THEN 0 ELSE 1 END AS lastsent_check";
		$query .= " FROM [|PREFIX|]splittests st";
		if ($userid > 0) {
			$query .= " WHERE userid=" . $userid;
		}

		$valid_fields = array (
				'splitname',
				'createdate',
				'splittype',
				'lastsent'
			);

		$valid_directions = array (
				'asc',
				'desc',
			);

		$order_field = 'splitname';
		$order_direction = 'ASC';

		if (isset($sortinfo['SortBy'])) {
			$field = strtolower($sortinfo['SortBy']);
			if (in_array($field, $valid_fields)) {
				$order_field = $field;
			}
		}

		if (isset($sortinfo['Direction'])) {
			$dir = strtolower($sortinfo['Direction']);
			if (in_array($dir, $valid_directions)) {
				$order_direction = $dir;
			}
		}

		$query .= " ORDER BY ";
		if ($order_field == 'lastsent') {
			$query .= 'lastsent_check ' . $order_direction . ', ';
		}
		$query .= $order_field . " " . $order_direction;
		$query .= $this->db->AddLimit(($start * $result_limit), $result_limit);

		$result = $this->db->Query($query);
		while ($row = $this->db->Fetch($result)) {
			$row['splitdetails'] = unserialize($row['splitdetails']);
			$tests[] = $row;
		}
		return $tests;
	}

	/**
	 * GetSendingJobStatusCodes
	 * This returns an array of job status codes which is used to check if a job can be deleted or not.
	 * If a split test is actually sending, the split test can't be deleted.
	 *
	 * It is an array containing two status codes:
	 * - i (in progress)
	 * - r (re-sending the job if any emails fail)
	 *
	 * @return Array Returns an array of job status codes which indicate a 'sending' status.
	 */
	public function GetSendingJobStatusCodes()
	{
		return array ('i', 'r');
	}

	/**
	 * GetCampaignsUsed
	 * This returns an array of distinct campaign id's used by all split tests.
	 *
	 * If supplied, the id's passed in are used to restrict the search.
	 * This is used to check which campaigns are allowed to be deleted (ie unused by split test campaigns).
	 *
	 * If the id's to restrict to are not supplied (or not int id's),
	 * then all distinct campaign id's (newsletter id's) are returned.
	 *
	 * @param Array $campaign_ids The campaign id's to specifically search for. If none are supplied, then all campaign id's are returned.
	 *
	 * @return Array Returns an array of campaign id's currently used by split test campaigns.
	 */
	public function GetCampaignsUsed($campaign_ids=array())
	{
		if (!is_array($campaign_ids)) {
			$campaign_ids = array($campaign_ids);
		}

		if (!empty($campaign_ids)) {
			foreach ($campaign_ids as $p => $id) {
				if (!is_numeric($id)) {
					unset($campaign_ids[$p]);
					continue;
				}
			}
		}

		$query = "SELECT DISTINCT campaignid FROM [|PREFIX|]splittest_campaigns";
		if (!empty($campaign_ids)) {
			$query .= " WHERE campaignid IN (" . implode(',', $campaign_ids) . ")";
		}

		$ids_used = array();
		$result = $this->db->Query($query);
		while ($row = $this->db->Fetch($result)) {
			$ids_used[] = $row['campaignid'];
		}
		return $ids_used;
	}

	/**
	 * OwnsSplitTests
	 * Checks whether a user owns a set of split tests.
	 *
	 * @param Int $user_id The user ID whose permission to check.
	 * @param Array|Int $split_ids A split test ID or an array of split test IDs.
	 *
	 * @return Boolean True if the user owns all the split tests, otherwise false.
	 */
	public static function OwnsSplitTests($user_id, $split_ids)
	{
		$split_ids = self::FilterIntSet($split_ids);
		$db = IEM::getDatabase();
		$id_list = implode(', ', $split_ids);
		$query = "SELECT COUNT(*) FROM [|PREFIX|]splittests WHERE splitid IN ({$id_list}) AND userid = " . intval($user_id);
		if ($db->FetchOne($query) == count($split_ids)) {
			return true;
		}
		return false;
	}

	/**
	 * OwnsJobs
	 * Checks whether the given user owns all the jobs passed in.
	 *
	 * @param Int $user_id The user to test is the owner of the jobs.
	 * @param Array|Int $job_ids The list of job IDs to test.
	 *
	 * @return Boolean True if all the jobs are owned by the user, otherwise false.
	 */
	public static function OwnsJobs($user_id, $job_ids)
	{
		$job_ids = self::FilterIntSet($job_ids);
		$db = IEM::getDatabase();
		$id_list = implode(', ', $job_ids);
		$query = "SELECT COUNT(*) FROM [|PREFIX|]jobs WHERE jobid IN ({$id_list}) AND ownerid = " . intval($user_id);
		if ($db->FetchOne($query) == count($job_ids)) {
			return true;
		}
		return false;
	}

	/**
	 * FilterIntSet
	 * Sanitises a set of integers.
	 *
	 * @param Array|Int $items An integer or array of integers.
	 *
	 * @return An array of unique, sanitised integers.
	 */
	public static function FilterIntSet($items)
	{
		if (!is_array($items)) {
			$items = array($items);
		}
		return array_unique(array_map('intval', $items));
	}
}
