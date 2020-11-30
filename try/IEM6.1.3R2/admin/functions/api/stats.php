<?php
/**
* The Stats API.
*
* @version     $Id: stats.php,v 1.96 2008/03/03 03:20:22 chris Exp $
* @author Chris <chris@interspire.com>
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
*
* @package API
* @subpackage User_API
*/

/**
* Include the base api class if we need to.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* The stats may be a little off on a week where daylight savings start.
* The calculation to get the start of week isn't quite right.
* So what will happen is:
* Now = Tuesday, 8pm.
* "Last Sunday" is getting calculated as "Saturday 11pm"
* This will only happen when daylight savings starts
*
* When daylight savings ends, it will go the other way and adjust it to "Sunday 1am".
* Not a huge deal but worth noting anyway.
*
* @package API
* @subpackage User_API
*/
class Stats_API extends API
{

	/**
	* Types of charts that are 'daily' views.
	*
	* @see Process
	* @see SetupChartDates
	* @see SetupChart_Subscribers
	* @see SetupChart
	*
	* @var Array
	*/
	var $daily_stats_types = array('today', 'yesterday', 'last24hours');

	/**
	* Types of charts that are 'monthly' views.
	*
	* @see Process
	* @see SetupChartDates
	* @see SetupChart_Subscribers
	* @see SetupChart
	*
	* @var Array
	*/
	var $monthly_stats_types = array('thismonth', 'lastmonth', 'last30days');

	/**
	* The type of stats we're looking at. This is passed to the stats api to work out queries.
	*
	* @var String
	*/
	var $stats_type = false;

	/**
	* The calendar type of stats we're looking at. This is used to work out dates and views for the stats we're displaying.
	*
	* @see SetupChartDates
	* @see SetupChart_Subscribers
	* @see SetupChart
	*
	* @var String
	*/
	var $calendar_type = false;

	/**
	* Holds reference to the subscribersapi
	*
	* @see Subscribers_API
	*
	* @var Object
	*/
	var $Subscriber_API = null;

	/**
	* Constructor
	* Sets up the database object.
	*
	* @return True Always returns true.
	*/
	function Stats_API()
	{
		$this->GetDb();
		return true;
	}

	/**
	* Delete
	* Deletes statistics from the database. This is different from HideStats in that HideStats does not remove any records from the database, it only prevents them from being displays in the control panel.
	*
	* @param Array $statids The statids you want to delete
	* @param String $statstype The type of statistics you want to delete (newsletter / autoresponder)
	*
	* @see HideStats
	*
	* @return Boolean Returns false if statistics couldn't be deleted (invalid arguments passed in for example), otherwise returns true.
	*/
	function Delete($statids=array(), $statstype='n')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			return false;
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		/**
		 * Cleanup trackers
		 *
		 * When statistics are deleted, delete associated tracker record too
		 * @todo ask Chris what happened when record delete failed
		 */
			$validStatTypes = array(	'n'	=> 'newsletter',
										'a' => 'autresponder');
			$convertedStatType = isset($validStatTypes[$statstype])? $validStatTypes[$statstype] : '';

			if ($convertedStatType != '') {
				$tempContinue = false;
				if (!class_exists('module_TrackerFactory', false)) {
					$tempFile = dirname(__FILE__) . '/module_trackerfactory.php';
					if (is_file($tempFile)) {
						require_once($tempFile);
						$tempContinue = true;
					}
				} else {
					$tempContinue = true;
				}

				if ($tempContinue) {
					foreach ($statids as $statid) {
						module_Tracker::DeleteRecordsForAllTrackerByID($statid, $convertedStatType);
					}
				}
			}
		/**
		 * -----
		 */

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . $table . " WHERE statid IN (" . implode(',', $statids). ")";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		if ($statstype == 'n') {
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists WHERE statid IN (" . implode(',', $statids). ")";
			$result = $this->Db->Query($query);
		}

		/**
		* Clean up old links.
		*/
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_links WHERE statid IN (" . implode(',', $statids). ")";
		$result = $this->Db->Query($query);

		/**
		* Clean up old link clicks.
		*/
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks WHERE statid IN (" . implode(',', $statids). ")";
		$result = $this->Db->Query($query);

		/**
		* Clean up opening records.
		*/
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens WHERE statid IN (" . implode(',', $statids). ")";
		$result = $this->Db->Query($query);

		/**
		* Clean up forwarding records.
		*/
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards WHERE statid IN (" . implode(',', $statids). ")";
		$result = $this->Db->Query($query);

		if ($statstype == 'a') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "autoresponders SET statid=0 WHERE statid IN (" . implode(',', $statids). ")";
			$result = $this->Db->Query($query);
		}

		return true;
	}

	/**
	* HideStats
	* Hiding statistics does not actually delete them.
	* Instead it records who called "Hide" (ie "delete" in the admin area) so we know who wanted to delete the stuff.
	* We do this instead of actually deleting them because deleting records here will affect user statistics
	* While the number of emails sent is recorded in a summary table, we use the stats_newsletters & stats_autoresponders tables to get the number of emails sent, number of emails opened and so on (both for user stats & mailing list stats).
	* So instead of trying to duplicate their work into summary tables, we "Hide" stats and they don't show up when you go to a specific area in the statistics section.
	*
	* @param Array $statids The statids you want to hide.
	* @param String $statstype The type of statistics you want to hide (newsletter / autoresponder)
	* @param Int $userid The userid of the person who requested the "hiding" of statistics.
	*
	* @return Boolean Returns false if statistics couldn't be hidden (invalid arguments passed in for example), otherwise returns true.
	*/
	function HideStats($statids, $statstype='n', $userid=0)
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			return false;
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		$userid = (int)$userid;
		if ($userid <= 0) {
			return false;
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . $table . " SET hiddenby='" . $userid . "' WHERE statid IN (" . implode(',', $statids). ")";
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		if ($statstype == 'n') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists SET hiddenby='" . $userid . "' WHERE statid IN (" . implode(',', $statids). ")";
			$result = $this->Db->Query($query);
		}

		if ($statstype == 'a') {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "autoresponders SET statid=0 WHERE statid IN (" . implode(',', $statids). ")";
			$result = $this->Db->Query($query);
			$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders_recipients WHERE statid IN (" . implode(',', $statids) . ")";
			$result = $this->Db->Query($query);
		}

		return true;
	}

	/**
	* GetStatsTable
	* Returns the table name for a specific stats type. Specifying n for $statstype returns stats_newsletters, specifying a returns stats_autoresponders
	*
	* @param String $statstype The type of statistics you want to get the table for (newsletter / autoresponder)
	*
	* @return Variable Returns false if an invalid type was passed otherwise returns a String with the table name
	*/
	function GetStatsTable($statstype=false)
	{
		$statstype = strtolower(substr($statstype, 0, 1));
		switch ($statstype) {
			case 'n':
				$table = 'stats_newsletters';
			break;
			case 'a':
				$table = 'stats_autoresponders';
			break;
			default:
				$table = false;
		}
		return $table;
	}

	/**
	* FetchStats
	* Fetches the details of a newsletter or autoresponder statistics entry
	*
	* @param Array $statid The statid of the entry you want to retrieve from the database.
	* @param String $statstype The type of statistics the entry you are retrieving is (newsletter / autoresponder)
	*
	* @see GetStatsTable
	*
	* @return Array Returns an array of details about the statistics entry
	*/
	function FetchStats($statid=0, $statstype=false)
	{
		$statid = (int)$statid;
		if ($statid <= 0) {
			return false;
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . $table . " WHERE statid='" . $statid . "'";

		$result = $this->Db->Query($query);
		$statsdetails = $this->Db->Fetch($result);

		if ($statstype{0} == 'a') {
			$query = "SELECT listid FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders WHERE autoresponderid='" . (int)$statsdetails['autoresponderid'] . "'";
			$result = $this->Db->Query($query);
			$lists[] = $this->Db->FetchOne($result, 'listid');
		} else {
			$lists = array();
			$listtable = substr($table, 0, -1) . "_lists"; // take the "S" off the end of the table name.
			$query = "SELECT listid FROM " . SENDSTUDIO_TABLEPREFIX . $listtable . " WHERE statid='" . $statid . "'";
			$result = $this->Db->Query($query);
			while ($row = $this->Db->Fetch($result)) {
				$lists[] = $row['listid'];
			}
		}

		$statsdetails['Lists'] = $lists;
		return $statsdetails;
	}

	/**
	* GetBounceGraphData
	* Retrieves bounce data used to generate the bounce graphs
	*
	* @param String $statstype The type of statistics you want to hide (newsletter / autoresponder)
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Array $statids The statids you want to get bounce data for
	*
	* @return Array Returns an array of data for the graph
	*/
	function GetBounceGraphData($stats_type=false, $calendar_restrictions='', $statids=array(),$listid = 0)
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			return array();
		}

		// then bounces
		$bounces_query = "SELECT count(bounceid) AS count, bouncetype, ";
		$bounces_query .= $this->CalculateGroupBy($stats_type, 'bouncetime');
		$bounces_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces WHERE ";
		if ($listid) {
			$bounces_query .= "listid = $listid AND ";
		}
		$bounces_query .= "statid IN (" . implode(',', $statids) . ") ";

		if ($calendar_restrictions) {
			$bounces_query .= $calendar_restrictions;
		}

		switch ($stats_type) {
			case 'daily':
				$general_query = ' GROUP BY hr';
			break;

			case 'last7days':
				$general_query = ' GROUP BY dow';
			break;

			case 'last30days':
			case 'monthly':
				$general_query = ' GROUP BY dom';
			break;

			default:
				$general_query = ' GROUP BY mth, yr';
		}

		$general_query .= ', bouncetype';

		$result = $this->Db->Query($bounces_query . $general_query);

		while ($row = $this->Db->Fetch($result)) {
			$row['bouncetype'] = $row['bouncetype'];
			$return[] = $row;
		}
		return $return;
	}

	/**
	* GetSubscriberGraphData
	* Retrieves subscriber data used to generate the summary graphs
	*
	* @param String $statstype The type of statistics you want to hide (newsletter / autoresponder)
	* @param Array $restrictions An array of SQL fragments giving date restrictions for different subscriber types. Valid keys are 'subscribes', 'unsubscribes', 'bounces' and 'forwards'
	* @param Array $listids The listids you want to get subscriber data for
	*
	* @return Array Returns an array of data for the graph
	*/
	function GetSubscriberGraphData($stats_type=false, $restrictions=array(), $listids=array())
	{
		$return = array(
			'unconfirms' => array(),
			'confirms' => array(),
			'unsubscribes' => array(),
			'bounces' => array(),
			'forwards' => array()
		);

		if (!is_array($listids)) {
			$listids = array($listids);
		}

		$listids = $this->CheckIntVars($listids);
		if (empty($listids)) {
			$listids = array('0');
		}

		// first we'll do requests.
		$unconfirms_query = "SELECT COUNT(subscriberid) AS count, ";
		$unconfirms_query .= $this->CalculateGroupBy($stats_type, 'subscribedate');
		$unconfirms_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers";
		$unconfirms_query .= " WHERE listid IN (" . implode(',', $listids) . ") AND (bounced = 0 AND unsubscribed = 0 AND confirmed='0')";
		if (isset($restrictions['subscribes']) && !empty($restrictions['subscribes'])) {
			$unconfirms_query .= $restrictions['subscribes'];
		}


		// first we'll do requests.
		$confirms_query = "SELECT COUNT(subscriberid) AS count, ";
		$confirms_query .= $this->CalculateGroupBy($stats_type, 'subscribedate');
		$confirms_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers";
		$confirms_query .= " WHERE listid IN (" . implode(',', $listids) . ") AND (bounced = 0 AND unsubscribed = 0 AND confirmed='1')";
		if (isset($restrictions['subscribes']) && !empty($restrictions['subscribes'])) {
			$confirms_query .= $restrictions['subscribes'];
		}

		// then unsubscribes
		$unsubscribes_query = "SELECT COUNT(subscriberid) AS count, ";
		$unsubscribes_query .= $this->CalculateGroupBy($stats_type, 'unsubscribetime');
		$unsubscribes_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe";
		$unsubscribes_query .= " WHERE listid IN (" . implode(',', $listids) . ") AND unsubscribetime > 0";
		if (isset($restrictions['unsubscribes']) && !empty($restrictions['unsubscribes'])) {
			$unsubscribes_query .= $restrictions['unsubscribes'];
		}

		// then bounces
		$bounces_query = "SELECT COUNT(subscriberid) AS count, ";
		$bounces_query .= $this->CalculateGroupBy($stats_type, 'bouncetime');
		$bounces_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces";
		$bounces_query .= " WHERE listid IN (" . implode(',', $listids) . ")";
		if (isset($restrictions['bounces']) && !empty($restrictions['bounces'])) {
			$bounces_query .= $restrictions['bounces'];
		}

		// then forwards
		$forwards_query = "SELECT COUNT(subscriberid) AS count, ";
		$forwards_query .= $this->CalculateGroupBy($stats_type, 'forwardtime');
		$forwards_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards";
		$forwards_query .= " WHERE listid IN (" . implode(',', $listids) . ")";
		if (isset($restrictions['forwards']) && !empty($restrictions['forwards'])) {
			$forwards_query .= $restrictions['forwards'];
		}

		switch ($stats_type) {
			case 'daily':
				$general_query = ' GROUP BY hr';
			break;

			case 'last7days':
				$general_query = ' GROUP BY dow';
			break;

			case 'last30days':
			case 'monthly':
				$general_query = ' GROUP BY dom';
			break;

			default:
				$general_query = ' GROUP BY mth, yr';
		}

		$result = $this->Db->Query($unconfirms_query . $general_query);

		while ($row = $this->Db->Fetch($result)) {
			$return['unconfirms'][] = $row;
		}

		$result = $this->Db->Query($confirms_query . $general_query);

		while ($row = $this->Db->Fetch($result)) {
			$return['confirms'][] = $row;
		}

		$result = $this->Db->Query($unsubscribes_query . $general_query);

		while ($row = $this->Db->Fetch($result)) {
			$return['unsubscribes'][] = $row;
		}

		$result = $this->Db->Query($bounces_query . $general_query);

		while ($row = $this->Db->Fetch($result)) {
			$return['bounces'][] = $row;
		}

		$result = $this->Db->Query($forwards_query . $general_query);

		while ($row = $this->Db->Fetch($result)) {
			$return['forwards'][] = $row;
		}

		return $return;
	}

	/**
	* GetSubscriberDomainCount
	* Returns number of distinct domains used in a list
	*
	* @param Int $listid The listid you want to get the count for
	*
	* @return Int Returns the number of distinct domains
	*/
	function GetSubscriberDomainCount($listid=0)
	{
          $listid = (int)$listid;
		  if ($listid <= 0) {
			  return false;
		  }

          $query = "SELECT COUNT(DISTINCT domainname) FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers WHERE listid = $listid";
          $result = $this->Db->Query($query);
          return $this->Db->FetchOne($result);
	}

	/**
	* GetSubscriberDomainGraphData
	* Retrieves subscriber domain data used to generate the domain summary graphs
	*
	* @param Array $restrictions An array of SQL fragments giving date restrictions for different subscriber types. Valid keys are 'subscribes', 'unsubscribes', 'bounces' and 'forwards'
	* @param Int $listid The listid you want to get subscriber data for
	* @param Int $limit The number of total domains to return data for. These will be sorted by the number of subscribers.
	*
	* @return Array Returns an array of data for the graph
	*/
	function GetSubscriberDomainGraphData($restrictions=array(), $listid=0, $limit=10)
	{
		$return = array(
			'confirms' => array(),
			'unconfirms' => array(),
			'unsubscribes' => array(),
			'bounces' => array(),
			'forwards' => array()
		);

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$domain_name_query = "SUBSTRING(l.domainname, LOCATE('@', l.domainname) + 1) AS domainname";
			$subscribes_query = "SELECT COUNT(l.subscriberid) AS count, " . $domain_name_query;
			$subscribes_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l";
			$subscribes_query .= " WHERE l.listid='" . $listid . "'";
			if (isset($restrictions['subscribes']) && !empty($restrictions['subscribes'])) {
				$subscribes_query .= $restrictions['subscribes'];
			}
			$general_query = " GROUP BY SUBSTRING(l.domainname, LOCATE('@', l.domainname) + 1)";
			$forwards_group_query = " GROUP BY SUBSTRING(l.emailaddress, LOCATE('@', l.emailaddress) + 1)";
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$domain_name_query = "SUBSTRING(l.domainname, POSITION('@' IN l.domainname) + 1) AS domainname";
			$subscribes_query = "SELECT COUNT(l.subscriberid) AS count, " . $domain_name_query;
			$subscribes_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l";
			$subscribes_query .= " WHERE l.listid='" . $listid . "'";
			if (isset($restrictions['subscribes']) && !empty($restrictions['subscribes'])) {
				$subscribes_query .= $restrictions['subscribes'];
			}
			$general_query = " GROUP BY domainname";

			$forwards_group_query = " GROUP BY SUBSTRING(l.emailaddress, POSITION('@' IN l.emailaddress) + 1)";
		}

		$order_query = " ORDER BY count DESC LIMIT " . $limit;

		$domain_general_query = $forwards_domain_general_query = '';

		$general_start_query = "SELECT COUNT(l.subscriberid) AS count, " . $domain_name_query . " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l";
		$general_start_query .= " WHERE l.listid='" . $listid . "'";

		$result = $this->Db->Query($subscribes_query . " AND (bounced=0 AND unsubscribed=0 AND confirmed='0') " . $general_query . $order_query);
		while ($row = $this->Db->Fetch($result)) {
			$domain_general_query .= "l.domainname='@" . $this->Db->Quote($row['domainname']) . "' OR ";
			$forwards_domain_general_query .= "l.emailaddress LIKE '%" . $this->Db->Quote($row['domainname']) . "' OR ";
			$return['unconfirms'][] = $row;
		}

		$result = $this->Db->Query($subscribes_query . " AND (bounced=0 AND unsubscribed=0 AND confirmed='1') " . $general_query . $order_query);
		while ($row = $this->Db->Fetch($result)) {
			$domain_general_query .= "l.domainname='@" . $this->Db->Quote($row['domainname']) . "' OR ";
			$return['confirms'][] = $row;
		}

		if (strlen($domain_general_query) > 1) {
			$domain_general_query = '(' . substr($domain_general_query, 0, -4) . ') AND ';
		}
		$domain_general_query .= '1=1';

		if (strlen($forwards_domain_general_query) > 1) {
			$forwards_domain_general_query = '(' . substr($forwards_domain_general_query, 0, -4) . ') AND ';
		}
		$forwards_domain_general_query .= '1=1';

		$unsubscribes_query = $general_start_query . " AND unsubscribed > 0 AND " . $domain_general_query;
		if (isset($restrictions['unsubscribes']) && !empty($restrictions['unsubscribes'])) {
			$unsubscribes_query .= str_replace('unsubscribetime', 'unsubscribed', $restrictions['unsubscribes']);
		}

		$result = $this->Db->Query($unsubscribes_query . $general_query . $order_query);
		while ($row = $this->Db->Fetch($result)) {
			$return['unsubscribes'][] = $row;
		}

		$bounces_query = $general_start_query . " AND bounced > 0 AND " . $domain_general_query;
		if (isset($restrictions['bounces']) && !empty($restrictions['bounces'])) {
			$bounces_query .= str_replace('bouncetime', 'bounced', $restrictions['bounces']);
		}

		$result = $this->Db->Query($bounces_query . $general_query . $order_query);
		while ($row = $this->Db->Fetch($result)) {
			$return['bounces'][] = $row;
		}

		$forwards_query = "SELECT COUNT(l.subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards l";
		$forwards_query .= " WHERE l.listid='" . $listid . "'";
		$forwards_query .= " AND " . $forwards_domain_general_query;
		if (isset($restrictions['forwards']) && !empty($restrictions['forwards'])) {
			$forwards_query .= $restrictions['forwards'];
		}

		$result = $this->Db->Query($forwards_query . $forwards_group_query . $order_query);
		while ($row = $this->Db->Fetch($result)) {
			$return['forwards'][] = $row;
		}
		return $return;
	}

	/**
	 * GetGraphData
	 *
	 * Gets data for the graphs to use when viewing stats
	 *
	 * @param Array $statids The stats to get the data for
	 * @param String $stats_type The type of stats we're viewing. This can be 'daily' or 'monthly' or 'weekly' etc.
	 * @param String $restrictions The calendar restrictions (this has been worked out in a previous step)
	 * @param String $chart_type The type of chart we're viewing
	 * @param Int $listid This is used for unsubscribechart - as you can unsubscribe from a list but not through an email or autoresponder
	 *
	 * @return Array Returns a multidimensional array of data for the given info.
	 */
	function GetGraphData($statids=array(), $stats_type=false, $restrictions='', $chart_type='', $listid=0)
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		// make sure it's a valid id.
		$listid = (int)$listid;

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			return array();
		}

		switch ($chart_type) {
			case 'openchart':
				$countid = 'openid';
				$table = 'stats_emailopens';
				$field_restrictor = 'opentime';
			break;

			case 'unsubscribechart':
				$countid = 'subscriberid';
				$table = 'list_subscribers_unsubscribe';
				$field_restrictor = 'unsubscribetime';
				$restrictions .= " AND unsubscribetime > 0";
			break;

			case 'forwardschart':
				$countid = 'forwardid';
				$table = 'stats_emailforwards';
				$field_restrictor = 'forwardtime';
			break;

			case 'linkschart':
				$countid = 'clickid';
				$table = 'stats_linkclicks';
				$field_restrictor = 'clicktime';
			break;
		}

		$query = "SELECT COUNT(" . $countid . ") AS count,";
		$query .= $this->CalculateGroupBy($stats_type, $field_restrictor);
		$query .= " FROM " . SENDSTUDIO_TABLEPREFIX . $table . ' AS stats';

		if ($chart_type == 'openchart' || $chart_type == 'linkschart') {
			$query .= " INNER JOIN " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls ON (stats.subscriberid = ls.subscriberid)";
		} else if ($listid > 0 && $chart_type == 'unsubscribechart') {
			$query .= ", " . SENDSTUDIO_TABLEPREFIX . "lists AS ls";
		}

		$query .= " WHERE";

		if ($listid > 0 && $chart_type == 'unsubscribechart') {
			$query .= " ls.listid=stats.listid AND ls.listid=" . $listid . "";
		} elseif ($listid > 0 && ($chart_type == 'openchart' || $chart_type == 'linkschart')) {
			$query .= " ls.listid='" . $listid . "' AND";
			$query .= " statid IN(" . implode(',', $statids) . ")";
		} else {
			$query .= " statid IN(" . implode(',', $statids) . ")";
		}

		if ($restrictions) {
			$query .= $restrictions;
		}

		switch ($stats_type) {
			case 'daily':
				$query .= ' GROUP BY hr';
			break;

			case 'last7days':
				$query .= ' GROUP BY dow';
			break;

			case 'monthly':
				$query .= ' GROUP BY dom';
			break;

			default:
				$query .= ' GROUP BY mth, yr';
		}

		$return = array();

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* CalculateGroupBy
	* Generates a date column that results should be grouped by for charts.
	*
	* @param String $stats_type The date view being used. This can be 'today', 'yesterday', 'daily', 'last24hours', 'last7days', 'thismonth', 'lastmonth', 'monthly' or anything else for All Time.
	* @param Int $fieldname The fieldname that holds the date for the statistics being fetched
	*
	* @return String Returns a string that can be appended to the list of fields being fetched from a stats table.
	*/
	function CalculateGroupBy($stats_type=false, $fieldname='')
	{
		$user_tz = '+0:00';
		$user = GetUser();
		if (strlen($user->usertimezone)) {
			if ($user->usertimezone == 'GMT') {
				$user_tz = '+0:00';
			} else {
				$user_tz = substr($user->usertimezone,3);
			}
		}

		$server_tz = date('Z');

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$difference = substr($user_tz, 1);
			$operator = "ADDTIME";

			if ($user_tz{0} == '-') {
				$operator = "SUBTIME";
			}

			$timestamp = "{$operator}(FROM_UNIXTIME($fieldname), '{$difference}')";

			switch ($stats_type) {
				case 'today':
				case 'yesterday':
				case 'daily':
				case 'last24hours':
					$query = " EXTRACT(hour FROM $timestamp) AS hr";
				break;

				case 'last7days':
					$query = " DATE_FORMAT($timestamp, '%w') AS dow";
				break;

				case 'last30days':
				case 'thismonth':
				case 'lastmonth':
				case 'monthly':
					$query = " EXTRACT(day FROM $timestamp) AS dom";
				break;
				default:
					$query = " EXTRACT(month FROM $timestamp) AS mth, EXTRACT(year FROM $timestamp) AS yr";
			}
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'pgsql') {
			$user_tz = explode(':', $user_tz);
			$user_tz_seconds = (int)$user_tz[0] * 3600 + (int)$user_tz[1] * 60;
			$timestamp = "TIMESTAMP WITH TIME ZONE 'epoch' + ($fieldname + $user_tz_seconds) * INTERVAL '1 second'";

			switch ($stats_type) {
				case 'today':
				case 'yesterday':
				case 'daily':
				case 'last24hours':
					$query = "EXTRACT(hour FROM $timestamp) AS hr";
				break;

				case 'last7days':
					$query = "EXTRACT(dow FROM $timestamp) AS dow";
				break;

				case 'last30days':
				case 'thismonth':
				case 'lastmonth':
				case 'monthly':
					$query = "EXTRACT(day FROM $timestamp) AS dom";
				break;

				default:
					$query = "EXTRACT(month FROM $timestamp) AS mth, EXTRACT(year FROM $timestamp) AS yr";
			}
		}
		return $query;
	}

	/**
	* This is used by index.php to show the number of subscribers over the last 30 days.
	*
	* @param Array $listids The listid's to include in the query. This is the lists a particular user has access to. This can either be an array or a singular id (which is then turned into an array).
	*
	* @see CheckIntVars
	* @see CalculateGroupBy
	* @see functions/index.php
	*
	* @return If there are no valid list id's, this returns an empty array. Otherwise the query is run and the results are returned as a multi-dimensional array.
	*/
	function GetSubscribersByDayGraphData($listids=array())
	{
		if (!is_array($listids)) {
			$listids = array($listids);
		}
		$listids = $this->CheckIntVars($listids);

		if (empty($listids)) {
			return array();
		}

		$seven_days_ago = $this->GetServerTime() - (7 * 86400);

		$group_by = $this->CalculateGroupBy('last7days', 'subscribedate');
		$query = "SELECT COUNT(subscriberid) AS count, confirmed, ";
		$query .= $group_by;
		$query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ";
		$query .= " WHERE listid IN (" . implode(',', $listids) . ")";
		$query .= " AND subscribedate > " . $seven_days_ago;
		$query .= " GROUP BY dow, confirmed";

		$return = array();
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* GetMostPopularLink
	* Returns the link with the most clicks
	*
	* @param Array $statids The statids you want to fetch the most popular link for..
	* @param Int $linkid The linkid to restrict results to, specify 'a' for all links
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	*
	* @return String Returns the URL of the most popular link
	*/
	function GetMostPopularLink($statids=array(), $linkid='a', $calendar_restrictions='')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$query = "SELECT l.url AS url, COUNT(clickid) AS linkcount FROM " . SENDSTUDIO_TABLEPREFIX . "links l, " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks lc WHERE l.linkid=lc.linkid AND lc.statid IN (" . implode(',', $statids) . ") " . $calendar_restrictions;

		if (is_numeric($linkid)) {
			$query .= " AND l.linkid='" . $linkid . "'";
		}

		$query .= " GROUP BY l.url ORDER BY linkcount DESC LIMIT 1";

		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		$url = str_replace(array('"', "'"), '', $row['url']);
		return $url;
	}

	/**
	* GetUniqueClickRecipients
	* Returns the number of unique recipients who clicked on any links. This is used when exporting stats.
	*
	* @param Array $statids The statids you want to fetch data for
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Int $linkid The linkid to restrict results to, specify 'a' for all links
	*
	* @return String Returns the number of unique recipients who clicked on the specified links.
	*/
	function GetUniqueClickRecipients($statids=array(), $calendar_restrictions='',$linkid = 'a')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$query = "
			SELECT COUNT(DISTINCT lc.subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks lc
			WHERE lc.statid IN(" . implode(',', $statids) . ") ";

		if (is_numeric($linkid)) {
			$query .= " AND lc.linkid='" . $linkid . "'";
		}

		$query .= $calendar_restrictions;

		$result = $this->Db->Query($query);
		return $this->Db->FetchOne($result, 'count');
	}

	/**
	* GetUniqueClickers
	* Returns the number of subscribers who clicked any link in a given newsletter/autoresponder/etc.
	*
	* @param Array $statids The statids you want to fetch data for.
	*
	* @return String Returns the number of subscribers who clicked a link in the given statid item.
	*/
	function GetUniqueClickers($statids=array())
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}
		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}
		$query = "SELECT COUNT(DISTINCT subscriberid) AS count FROM [|PREFIX|]stats_linkclicks WHERE statid IN (" . implode(',', $statids) . ")";
		$result = $this->Db->Query($query);
		return $this->Db->FetchOne($result, 'count');
	}

	/**
	* GetUniqueClicks
	* Returns the number of unique clicks for a link.
	*
	* @param Array $statids The statids you want to fetch data for
	* @param Int $linkid The linkid to restrict results to, specify 'a' for all links
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	*
	* @return String Returns the number of unique clicks for a link
	*/
	function GetUniqueClicks($statids=array(), $linkid='a', $calendar_restrictions='')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$query = "SELECT COUNT(DISTINCT lc.linkid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks lc, " . SENDSTUDIO_TABLEPREFIX . "links ml WHERE ml.linkid=lc.linkid AND lc.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;

		if (is_numeric($linkid)) {
			$query .= " AND ml.linkid='" . $linkid . "'";
		}

		$result = $this->Db->Query($query);
		return $this->Db->FetchOne($result, 'count');
	}

	/**
	* GetClicks
	* Fetches a list of clicks for a statid and linkid. This is used to display the table of links on the Link Stats page
	*
	* @param Array $statids The statids you want to fetch data for
	* @param Int $start The offset to return results from
	* @param Int $perpage The number of results per page
	* @param Int $linkid The linkid to restrict results to, specify 'a' for all links
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Boolean $count_only Specify True to return the number of clicks instead of a list of clicks
	* @param String $order_by The column to order the results by. Defaults to 'clicktime'
	* @param String $order_dir The direction to order results by. Defaults to descending order. Specify ASC for ascending and DESC for descending.
	*
	* @return Array Returns an array of link clicks or if $count_only was set to true returns the number of link clicks in total
	*/
	function GetClicks($statids=array(), $start=0, $perpage=10, $linkid='a', $calendar_restrictions='', $count_only=false, $order_by = 'clicktime', $order_dir = 'DESC')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		if ($count_only) {
			$query = "SELECT COUNT(l.subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks lc, " . SENDSTUDIO_TABLEPREFIX . "links ml WHERE ml.linkid=lc.linkid AND l.subscriberid=lc.subscriberid AND lc.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;

			if (is_numeric($linkid)) {
				$query .= " AND ml.linkid='" . $linkid . "'";
			}
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT l.emailaddress, clicktime, clickip, url FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks lc, " . SENDSTUDIO_TABLEPREFIX . "links ml WHERE ml.linkid=lc.linkid AND l.subscriberid=lc.subscriberid AND lc.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;

		if (is_numeric($linkid)) {
			$query .= " AND ml.linkid='" . $linkid . "'";
		}

		$query .= " ORDER BY $order_by $order_dir ";

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}

		return $return;
	}

	/**
	* GetBounceCounts
	* Fetchs the total number of bounces grouped by type (soft, hard)
	*
	* @param Array $statids The statids you want to fetch data for
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	*
	* @return Array Returns an array of bounce totals with three indexes: hard, soft and total
	*/
	function GetBounceCounts($statids=array(), $calendar_restrictions='')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$query = "SELECT count(bounceid) AS count, bouncetype FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces WHERE statid IN (" . implode(',', $statids) . ") " . $calendar_restrictions . " GROUP BY bouncetype";

		$bouncecounts = array('soft' => 0, 'hard' => 0, 'total' => 0);

		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$bouncecounts[$row['bouncetype']] += $row['count'];
		}
		$bouncecounts['total'] = $bouncecounts['soft'] + $bouncecounts['hard'];
		return $bouncecounts;
	}

	/**
	* GetBounces
	* Fetches a list of bounces for a statid. This is used to display the table of bounces on the Bounce Stats page
	*
	* @param Array $statids The statids you want to fetch data for
	* @param Int $start The offset to return results from
	* @param Int $perpage The number of results per page
	* @param String $bounce_type The type of bounce to get results for, specify 'soft', 'hard' or 'any'
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Boolean $count_only Specify True to return the number of bounces instead of a list of bounces
	* @param String $order_by The column to order the results by. Defaults to 'bouncetime'
	* @param String $order_dir The direction to order results by. Defaults to descending order. Specify ASC for ascending and DESC for descending.
	*
	* @return Array Returns an array of bounces or if $count_only was set to true returns the number of bounces in total
	*/
	function GetBounces($statids=array(), $start=0, $perpage=10, $bounce_type=false, $calendar_restrictions='', $count_only=false,$order_by = 'bouncetime', $order_dir = 'DESC')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		if ($bounce_type == 'any') {
			$bounce_type = false;
		}

		if ($count_only) {
			$query = "SELECT COUNT(l.subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces b WHERE l.subscriberid=b.subscriberid AND b.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;
			if ($bounce_type) {
				$query .= " AND bouncetype='" . $this->Db->Quote($bounce_type) . "'";
			}
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT l.emailaddress, bouncetime, bouncetype, bouncerule FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "list_subscriber_bounces b WHERE l.subscriberid=b.subscriberid AND b.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;

		if ($bounce_type) {
			$query .= " AND bouncetype='" . $this->Db->Quote($bounce_type) . "'";
		}

		$query .= "ORDER BY $order_by $order_dir ";

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* GetRecipients
	* Fetches a list of recipients for an autoresponder.
	*
	* @param Array $statids The statids you want to fetch data for
	* @param String $statstype The type of statistics you want to get recipients for. Currently only supports autoresponder.
	* @param Int $start The offset to return results from
	* @param Int $perpage The number of results per page
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Boolean $count_only Specify True to return the number of recipients instead of a list of recipients
	* @param String $order_by The column to order the results by. Defaults to 'sendtime'
	* @param String $order_dir The direction to order results by. Defaults to descending order. Specify ASC for ascending and DESC for descending.
	*
	* @return Array Returns an array of recipients or if $count_only was set to true returns the number of recipients in total
	*/
	function GetRecipients($statids=array(), $stats_type='autoresponder', $start=0, $perpage=10, $calendar_restrictions='', $count_only=false, $order_by = 'sendtime', $order_dir = 'DESC')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		switch ($stats_type) {
			case 'autoresponder':
				$tablename = 'stats_autoresponders_recipients';
			break;
		}

		if ($count_only) {
			$query = "SELECT COUNT(recipient) as count FROM " . SENDSTUDIO_TABLEPREFIX . $tablename . " s, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE l.subscriberid=s.recipient AND s.statid IN (" . implode(',',
			$statids) . ") " . $calendar_restrictions;
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT l.emailaddress, s.sendtime, send_status, reason FROM " . SENDSTUDIO_TABLEPREFIX . $tablename . " s, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE l.subscriberid=s.recipient AND s.statid IN (" . implode(',', $statids) . ") " . $calendar_restrictions;
		$query .= " ORDER BY " . $order_by . " " . $order_dir;

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* GetForwardsRecipients
	* Returns the number of recipients who forwarded a newsletter at least once. This is used by the Export Stats function.
	*
	* @param Array $statids The statids you want to fetch data for
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	*
	* @return String Returns the number of recipients who forwarded a campaign at least once
	*/
	function GetForwardsRecipients($statids=array(), $calendar_restrictions='')
	{

		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$query = "SELECT COUNT(*) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards f WHERE f.statid IN(" . implode(',', $statids) . ") $calendar_restrictions GROUP BY f.subscriberid";

		$result = $this->Db->Query($query);
		return $this->Db->FetchOne($result, 'count');
	}

	/**
	* GetForwards
	* Fetches a list of subscribers who forwarded a campaign or autoresponder
	*
	* @param Array $statids The statids you want to fetch data for
	* @param Int $start The offset to return results from
	* @param Int $perpage The number of results per page
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Boolean $count_only Specify True to return the number of forwards instead of a list of forwards
	* @param Boolean $new_signups Specify true to restrict results to those that resulted in a new signup
	* @param String $order_by The column to order the results by. Defaults to 'forwardtime'
	* @param String $order_dir The direction to order results by. Defaults to descending order. Specify ASC for ascending and DESC for descending.
	*
	* @return Array Returns an array of forwards or if $count_only was set to true returns the number of forwards in total
	*/
	function GetForwards($statids=array(), $start=0, $perpage=10, $calendar_restrictions='', $count_only=false, $new_signups=false,$order_by = 'forwardtime', $order_dir = 'DESC')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		if ($count_only) {
			$query = "SELECT COUNT(l.subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards f WHERE l.subscriberid=f.subscriberid AND f.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;

			if ($new_signups) {
				$query .= " AND subscribed > 0";
			}
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT l.emailaddress AS forwardedby, forwardtime, forwardip, f.emailaddress AS forwardedto, subscribed FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards f WHERE l.subscriberid=f.subscriberid AND f.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;

		if ($new_signups) {
			$query .= " AND subscribed > 0";
		}

		$query .= " ORDER BY $order_by $order_dir ";

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}
		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* GetUnsubscribes
	* Fetches a list of subscribers who unsubscribed from a campaign or autoresponder
	*
	* @param Array $statids The statids you want to fetch data for. This value is ignored if $listid is specified
	* @param Int $start The offset to return results from
	* @param Int $perpage The number of results per page
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Boolean $count_only Specify True to return the number of unsubscribes instead of a list of unsubscribes
	* @param String $order_by The column to order the results by. Defaults to 'unsubscribetime'
	* @param String $order_dir The direction to order results by. Defaults to descending order. Specify ASC for ascending and DESC for descending.
	* @param Int $listid If specified this will restrict results to the listid and will ignore the $statids parameter
	*
	* @return Array Returns an array of unsubscribes or if $count_only was set to true returns the number of unsubscribes in total
	*/
	function GetUnsubscribes($statids=array(), $start=0, $perpage=10, $calendar_restrictions='', $count_only=false,$order_by = 'unsubscribetime', $order_dir = 'DESC', $listid=0)
	{

		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$listid = (int)$listid;

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		if ($listid) {
			$statid_restriction = " l.listid = $listid AND lsu.statid IN(" . implode(',', $statids) . ") ";
		} else {
			$statid_restriction = " lsu.statid IN(" . implode(',', $statids) . ") ";
		}

		if ($count_only) {
			$query = "SELECT COUNT(l.subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe lsu WHERE l.subscriberid=lsu.subscriberid AND ";
			if ($listid > 0) {
				$query .= " lsu.listid='" . $listid . "'";
			} else {
				$query .= " lsu.statid IN (" . implode(',', $statids) . ") ";
			}
			$query .= $calendar_restrictions;
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT l.emailaddress, unsubscribetime, unsubscribeip FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe lsu WHERE l.subscriberid=lsu.subscriberid AND ";
		if ($listid > 0) {
			$query .= " lsu.listid='" . $listid . "'";
		} else {
			$query .= " lsu.statid IN (" . implode(',', $statids) . ") ";
		}
        if(!empty($calendar_restrictions)){$query .= $calendar_restrictions;}
		$query .= " ORDER BY {$order_by} {$order_dir} ";

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* GetOpens
	* Fetches a list of subscribers who opened a campaign or autoresponder
	*
	* @param Array $statids The statids you want to fetch data for. This value is ignored if $listid is specified
	* @param Int $start The offset to return results from
	* @param Int $perpage The number of results per page
	* @param Int $only_unique Specify true to count/retrieve unique opens only, specify false for all opens
	* @param String $calendar_restrictions The range of dates to get data for. This should be an SQL fragment.
	* @param Boolean $count_only Specify True to return the number of opens instead of a list of opens
	* @param String $order_by The column to order the results by. Defaults to 'opentime'
	* @param String $order_dir The direction to order results by. Defaults to descending order. Specify ASC for ascending and DESC for descending.
	* @param Int $listid If specified this will restrict results to the listid and will ignore the $statids parameter
	*
	* @return Array Returns an array of opens or if $count_only was set to true returns the number of opens in total
	*/
	function GetOpens($statids=array(), $start=0, $perpage=10, $only_unique=false, $calendar_restrictions='', $count_only=false, $order_by = 'opentime', $order_dir = 'DESC')
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		if (!$only_unique) {
			if ($count_only) {
				$query = "SELECT COUNT(l.subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o WHERE l.subscriberid=o.subscriberid AND o.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions;
				$result = $this->Db->Query($query);
				return $this->Db->FetchOne($result, 'count');
			}

			$query = "SELECT l.emailaddress, opentime, openip, opentype FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o WHERE l.subscriberid=o.subscriberid AND o.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions . " ORDER BY $order_by $order_dir ";
		} else {
			if ($count_only) {
				/**
				 * When called from 'link stats', there is a 'clicktime' in the calendar info
				 * We need the 'opentime' field instead.
				 */
				$query = "SELECT COUNT(DISTINCT l.emailaddress) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o WHERE l.subscriberid=o.subscriberid AND o.statid IN(" . implode(',', $statids) . ") " . str_replace('clicktime', 'opentime', $calendar_restrictions);
				$result = $this->Db->Query($query);
				return $this->Db->FetchOne($result, 'count');
			}

			// mysql lets you only group by one field in the select list, so we'll take the easy way out.
			// also only v4.1+ supports subselects so we're out of luck doing it that way anyway.
			if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
				$query = "SELECT l.emailaddress, opentime, openip, opentype FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o WHERE l.subscriberid=o.subscriberid AND o.statid IN(" . implode(',', $statids) . ") " . $calendar_restrictions . " GROUP BY l.emailaddress ORDER BY $order_by $order_dir ";
			} else {
				// postgres supports subselects and won't let you group by only one field in the select list, so we have to do it this way.
				// this will get the latest opentime for an email open (in the subselect) and use that as the joining criteria.
				$query = "SELECT l.emailaddress, oo.opentime, oo.openip, oo.opentype FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l, " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens oo WHERE (SELECT opentime FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o WHERE o.subscriberid=l.subscriberid AND o.statid IN(" . implode(',', $statids) . ") ORDER BY opentime DESC LIMIT 1)=oo.opentime AND l.subscriberid=oo.subscriberid " . $calendar_restrictions . " ORDER BY $order_by $order_dir ";
			}
		}
		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$return[] = $row;
		}
		return $return;
	}

	/**
	* GetMostOpens
	* Calculates which time period saw the most opens for a campaign or autoresponder. The period used is determined by the value of $calendar_type
	*
	* @param Array $statids The statids you want to fetch data for.
	* @param String $restrictions An SQL fragment giving any additional restrictions for the query
	* @param Int $listid Restrict results to subscribers from a certain list
	*
	* @see CalculateStatsType
	* @see $calendar_type
	* @see CalculateGroupBy
	*
	* @return Array Returns an array giving the number of opens and the start of the time period for the most opens
	*/
	function GetMostOpens($statids=array(), $restrictions='',$listid = 0)
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$listid = (int)$listid;

		$this->CalculateStatsType();

		if ($listid > 0) {
			$qry = "SELECT COUNT(openid) AS count, ls.listid as listid, ";
		} else {
			$qry = "SELECT COUNT(openid) AS count, ";
		}

		$qry .= $this->CalculateGroupBy($this->calendar_type, 'opentime');
		$qry .= " FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o ";

		if ($listid > 0) {
			$qry .= "INNER JOIN " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls ON (o.subscriberid = ls.subscriberid) ";
		}

		$qry .= "WHERE ";
		if ($listid > 0) {
			$qry .= "listid = $listid AND o.statid IN(" . implode(',', $statids) . ") ";
		} else {
			$qry .= "o.statid IN(" . implode(',', $statids) . ") ";
		}

		if ($restrictions) {
			$qry .= $restrictions;
		}

		switch ($this->calendar_type) {
			case 'today':
			case 'yesterday':
			case 'daily':
			case 'last24hours':
				$general_query = ' GROUP BY hr';
			break;

			case 'last7days':
				$general_query = ' GROUP BY dow';
			break;

			case 'last30days':
			case 'thismonth':
			case 'lastmonth':
			case 'monthly':
				$general_query = ' GROUP BY dom';
			break;

			default:
				$general_query = ' GROUP BY mth, yr';
		}

		$qry .= $general_query;

		$qry .= " ORDER BY count DESC LIMIT 1";
		$result = $this->Db->Query($qry);

		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		return $row;
	}

	/**
	* GetMostUnsubscribes
	* Calculates which time period saw the most unsubscribes for a campaign or autoresponder. The period used is determined by the value of $calendar_type
	*
	* @param Array $statids The statids you want to fetch data for. This value is ignored if $listid is specified
	* @param String $restrictions An SQL fragment giving any additional restrictions for the query
	* @param Int $listid If specified this will restrict results to the listid and will ignore the $statids parameter
	*
	* @see CalculateStatsType
	* @see $calendar_type
	* @see CalculateGroupBy
	*
	* @return Array Returns an array giving the number of unsubscribers and the start of the time period for the most unsubscribes
	*/
	function GetMostUnsubscribes($statids=array(), $restrictions='', $listid=0)
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$listid = (int)$listid;

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$this->CalculateStatsType();

		$qry = "SELECT COUNT(unsubscribetime) AS count, ";
		$qry .= $this->CalculateGroupBy($this->calendar_type, 'unsubscribetime');
		$qry .= " FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe";
		if ($listid > 0) {
			$qry .= " WHERE listid ='" . $listid . "'";
		} else {
			$qry .= " WHERE statid IN(" . implode(',', $statids) . ")";
		}
		if ($restrictions) {
			$qry .= $restrictions;
		}

		switch ($this->calendar_type) {
			case 'today':
			case 'yesterday':
			case 'daily':
			case 'last24hours':
				$general_query = ' GROUP BY hr';
			break;

			case 'last7days':
				$general_query = ' GROUP BY dow';
			break;

			case 'last30days':
			case 'thismonth':
			case 'lastmonth':
			case 'monthly':
				$general_query = ' GROUP BY dom';
			break;

			default:
				$general_query = ' GROUP BY mth, yr';
		}

		$qry .= $general_query;

		$qry .= " ORDER BY count DESC LIMIT 1";

		$result = $this->Db->Query($qry);
		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		return $row;
	}

	/**
	* CheckStatsSequence
	* Checks that the stats_sequence table holds a valid value for the next statid. If it doesn't it will attemp to reset the sequence number.
	*
	* @return Boolean Returns true if the next sequence value is valid, returns false if it's not.
	*/
	function CheckStatsSequence()
	{
		$queue_sequence_ok = $this->Db->CheckSequence(SENDSTUDIO_TABLEPREFIX . 'stats_sequence');
		if (!$queue_sequence_ok) {
			$qry = "(SELECT statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters ORDER BY statid DESC LIMIT 1)
			UNION
			(SELECT statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders ORDER BY statid DESC LIMIT 1)
			UNION
			(SELECT id AS statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_sequence ORDER BY id DESC LIMIT 1)
			ORDER BY statid DESC LIMIT 1";
			$id = $this->Db->FetchOne($qry, 'statid');
			$new_id = $id + 1;
			$reset_ok = $this->Db->ResetSequence(SENDSTUDIO_TABLEPREFIX . 'stats_sequence', $new_id);
			if (!$reset_ok) {
				return false;
			}
		}
		return true;
	}

	/**
	* SaveNewsletterStats
	* Creates a statistics entry for a newsletter when it starts to send.
	*
	* @param Array $newsletterdetails The details of the newsletter the stats are for
	*
	* @see CheckStatsSequence
	* @see GetServerTime
	* @see Jobs_Send_API::ProcessJob
	*
	* @return Integer Returns the statid value used for the statistics entry
	*/
	function SaveNewsletterStats($newsletterdetails)
	{
		if (!$this->CheckStatsSequence()) {
			return false;
		}
		$statid = $this->Db->NextId(SENDSTUDIO_TABLEPREFIX . 'stats_sequence');

		$start_time = $this->GetServerTime();

		$test_mode = 0;
		if (SENDSTUDIO_SEND_TEST_MODE) {
			$test_mode = 1;
		}

		/**
		 * If the send type isn't set, then it's a newsletter send.
		 * Split testing and trigger emails will set this to another type.
		 */
		if (!isset($newsletterdetails['SendType'])) {
			$newsletterdetails['SendType'] = 'newsletter';
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters(statid, jobid, queueid, starttime, finishtime, htmlrecipients, textrecipients, multipartrecipients, trackopens, tracklinks, newsletterid,
		sendfromname, sendfromemail, bounceemail, replytoemail, charset, sendinformation, sendsize, sentby, notifyowner, bouncecount_soft, bouncecount_hard, bouncecount_unknown, unsubscribecount, emailopens,
		emailforwards, sendtestmode, hiddenby, sendtype) VALUES ('" . $statid . "', '" . (int)$newsletterdetails['Job'] . "', '" . (int)$newsletterdetails['Queue'] . "', '" . $start_time . "', 0, 0, 0, 0, '" .  $this->Db->Quote($newsletterdetails['TrackOpens']) . "', '" . $this->Db->Quote($newsletterdetails['TrackLinks']) . "', '" . (int)$newsletterdetails['Newsletter'] . "', '" .  $this->Db->Quote($newsletterdetails['SendFromName']) . "', '" . $this->Db->Quote($newsletterdetails['SendFromEmail']) . "', '" . $this->Db->Quote($newsletterdetails['BounceEmail']) . "', '" .  $this->Db->Quote($newsletterdetails['ReplyToEmail']) . "', '" . $this->Db->Quote($newsletterdetails['Charset']) . "', '" . $this->Db->Quote(serialize($newsletterdetails['SendCriteria'])) . "', '" .  (int)$newsletterdetails['SendSize'] . "', '" . (int)$newsletterdetails['SentBy'] . "', '" . $this->Db->Quote($newsletterdetails['NotifyOwner']) . "', 0, 0, 0, 0, 0, 0, ".$test_mode.", 0, '" . $this->Db->Quote($newsletterdetails['SendType']) . "')";

		$this->Db->Query($query);

		/**
		 * Different segments can send to the same lists, so we need to find unique id's here.
		 * Otherwise when we try to add the stat -> list connection(s), we get a duplicate key conflict.
		 *
		 * Segment 1 sends to lists 1,2
		 * Segment 2 sends to lists 2,3
		 *
		 * If we send to both at the same time, it's going to try to add:
		 * 1,2,2,3
		 *
		 * which causes the problem.
		*/
		$newsletterdetails['Lists'] = array_unique($newsletterdetails['Lists']);

		foreach ($newsletterdetails['Lists'] as $p => $listid) {
			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists(statid, listid) VALUES ('" . $statid . "', '" . (int)$listid . "')";
			$this->Db->Query($query);
		}
		return $statid;
	}

	/**
	* SaveAutoresponderStats
	* Creates a statistics entry for an autoresponder when it starts sending.
	*
	* @param Array $autoresponderdetails The details of the autoresponder the stats are for
	*
	* @see CheckStatsSequence
	* @see Jobs_Autoresponders_API::ProcessJob
	*
	* @return Integer Returns the statid value used for the statistics entry
	*/
	function SaveAutoresponderStats($autoresponderdetails)
	{
		if (!$this->CheckStatsSequence()) {
			return false;
		}

		$statid = $this->Db->NextId(SENDSTUDIO_TABLEPREFIX . 'stats_sequence');

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders(statid, autoresponderid, hiddenby) VALUES ('" . $statid . "', '" . (int)$autoresponderdetails['autoresponderid'] . "', 0)";
		$this->Db->Query($query);
		return $statid;
	}

	/**
	* UpdateRecipient
	* Increments the number of recipients for an autoresponder or campaign by one
	*
	* @param Int $statid The statid for the campaign or autoresponder the recipient count should be incremented for
	* @param Boolean $format The format the email was in, multipart, html or text
	* @param String $statstype The type of statistics the statid is (newsletter / autoresponder)
	*
	* @return Mixed Returns false on failure, nothing on success
	*/
	function UpdateRecipient($statid=0, $format=false, $statstype='n')
	{
		$statid = (int)$statid;
		if (!$format || $statid <= 0) {
			return false;
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		switch (strtolower(substr($format, 0, 1))) {
			case 'm':
				$subquery = 'multipartrecipients=multipartrecipients + 1';
			break;
			case 'h':
				$subquery = 'htmlrecipients=htmlrecipients + 1';
			break;

			case 't':
				$subquery = 'textrecipients=textrecipients + 1';
			break;

			default:
				$subquery = false;
		}

		if ($subquery) {
			$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . $table . " SET " . $subquery . " WHERE statid='" . $statid . "'";
			$this->Db->Query($query);
		}
	}

	/**
	* MarkNewsletterFinished
	* Updates the statistics entry for a campaign to indicate the campaign has finished sending
	* It also re-allocates any credits a user may have if it needs to.
	*
	* For example:
	* - Newsletter starts sending
	* - Paused
	* - Delete the scheduled event before it finishes
	*
	* It will re-allocate the credits for the unsent emails.
	*
	* You can pass in an array of statid's to mark as finished and it will look at all of them to work out how many emails to re-allocate.
	* This is used by split test campaigns sending as each newsletter being sent as part of the campaign has it's own statid.
	* If we didn't look at all of them at once, it would give back all credits as each 'newsletter' would give back it's unsent emails.
	*
	* @param Array $statids The statids to mark as finished and summarize. If it's not passed in as an array, it's turned into one for backwards compatibility.
	* @param Array $original_queuesizes The original number of recipients for the campaign(s). If it's not passed in as an array, it's turned into one for backwards compatibility.
	*
	* @return Void Returns nothing
	*
	* @uses Stats_API::SummarizeEmailSend()
	*/
	function MarkNewsletterFinished($statids=array(), $original_queuesizes=array())
	{
		$endtime = intval($this->GetServerTime());

		if (!is_array($statids)) {
			$statids = array($statids);
		}

		if (!is_array($original_queuesizes)) {
			$original_queuesizes = array($original_queuesizes);
		}

		$statids = $this->CheckIntVars($statids);
		$original_queuesizes = $this->CheckIntVars($original_queuesizes);

		/**
		 * Mark all of the stats as "finished" at the same time.
		 */
		$query = "UPDATE [|PREFIX|]stats_newsletters SET finishtime={$endtime} WHERE statid IN (" . implode(',', $statids) . ")";
		$this->Db->Query($query);

		/**
		 * Work out the total number of emails sent for all stats so we can update the user stats to show the exact number of emails sent.
		 */
		$query = "SELECT (multipartrecipients + htmlrecipients + textrecipients) AS totalrecipients, queueid, sentby, statid FROM [|PREFIX|]stats_newsletters WHERE statid IN (" . implode(',', $statids) . ")";
		$result = $this->Db->Query($query);

		/**
		 * total_recipients will be the number of emails sent by all stats.
		 */
		$total_recipients = 0;

		/**
		 * These are needed so they can be passed through to the summarizeemailsend method.
		 */
		$userids = $queueids = array();

		while ($row = $this->Db->Fetch($result)) {
			$queueids[] = $row['queueid'];
			$userids[] = $row['sentby'];

			$total_sent = intval($row['totalrecipients']);
			$statid = intval($row['statid']);

			//$query = "UPDATE [|PREFIX|]stats_newsletters SET sendsize={$total_sent} WHERE statid={$statid}";
			//$update_result = $this->Db->Query($query);

			$total_recipients += $total_sent;
		}

		$this->Db->FreeResult($result);

		/**
		 * The users should all be the same.
		 */
		$userid = intval($userids[0]);

		/**
		 * Now work out the total for all of the queue sizes passed in.
		 */
		$original_queuesize = array_sum($original_queuesizes);

		/**
		 * Once we've fixed up the user credits etc, summarize each send individually.
		 */

		$user = GetUser($userid);
		foreach ($statids as $k => $statid) {
			$this->SummarizeEmailSend($queueids[$k], $statid, $userids[$k]);

			// Check for sending notification
			$result = $this->Db->Query("
			SELECT sendsize, en.subject AS campaign_subject, en.name AS campaign_name
			FROM
				[|PREFIX|]stats_newsletters AS es, [|PREFIX|]newsletters AS en
			WHERE
					es.newsletterid = en.newsletterid AND
					sendtype = 'newsletter' AND
					statid = {$statid}
			");

			if (!$result) {
				trigger_error(__CLASS__ . '::' . __METHOD__ . ' - Cannot get record from database', E_USER_NOTICE);
				return false;
			}
			$campaign_reports = $this->Db->Fetch($result);

			$this->Db->FreeResult($result);
			$user->CheckAdminSendNotification($campaign_reports);
		}
	}

	/**
	 * Refund user credit
	 * Please see MarkNewsletterFinished() method above.
	 * @todo everything
	 * @param integer $jobid ID of the job so that user credit can be refunded
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RefundFixedCredit($jobid)
	{
		return true;
	}

	/**
	* GetNewsletterStats
	* Fetches a list of newsletters statistics. This is used to list the newsletters on the Email Campaign Stats page.
	*
	* It looks for records in the stats_newsletters table with the sendtype of 'newsletter' - which is what email campaigns set.
	* Other types of sends such as split test or trigger emails change the 'sendtype' and we don't want them shown here.
	*
	* The method will return either of the following:
	* - (Array) An array of statistic record
	* - (Integer) Newsletter statistic record count
	* - (Boolean: FALSE) When an error occured
	*
	* @param Array $listids The listids to get newsletters for
	* @param Array $sortinfo Sort informatio for the results. Valid keys are SortBy to specify the column to sort by and Direction (asc or desc) for the direction of the sort
	* @param Boolean $countonly Specify true to only return the number of campaigns, false to return a list of campaigns
	* @param Int $start The offset to start returning results from
	* @param Int $perpage The number of results to return
	* @param Int $newsletterid Restrict results to a specific newsletter
	*
	* @see Stats::PrintNewsletterStats_Step1
	*
	* @return Mixed Return an array or statistic record or an integer (depending on the parameter $countonly) if successful, FALSE otherwise
	*/
	function GetNewsletterStats($listids=array(), $sortinfo=array(), $countonly=false, $start=0, $perpage=10, $newsletterid=0)
	{
		/**
		 * Static Local variables
		 */
			static $valid_sorts = array (
				'newsletter'	=> 'LOWER(n.name)',
				'list'			=> 'LOWER(l.name)',
				'startdate'		=> 'starttime',
				'finishdate'	=> 'finishtime',
				'recipients'	=> '(sn.htmlrecipients + sn.textrecipients + sn.multipartrecipients)',
				'unsubscribes'	=> 'sn.unsubscribecount',
				'bounces'		=> '(bouncecount_soft + bouncecount_hard + bouncecount_unknown)'
			);

			static $default_sortinfo = array (
				'SortBy'	=> 'finishdate',
				'Direction'	=> 'desc'
			);

			static $tableprefix = SENDSTUDIO_TABLEPREFIX;
		/**
		 * -----
		 */


		/**
		 * Sanitize parameters
		 */
			if (!is_array($listids)) {
				$listids = array($listids);
			}

			$listids = $this->CheckIntVars($listids);

			if (!is_array($sortinfo)) {
				/**
				 * sortinfo must be an array...
				 * If non-array is passed in, replace it with the default sortinfo
				 */
				$sortinfo = $default_sortinfo;
			} else {
				/**
				 * Check whether or not the required sort information is availabe.
				 * If not, use the default value for each data
				 */
				foreach ($default_sortinfo as $key => $value) {
					if (!array_key_exists($key, $sortinfo)) {
						$sortinfo[$key] = $value;
					}
				}
			}

			/**
			 * Check for valid sort by column
			 */
			if (!array_key_exists($sortinfo['SortBy'], $valid_sorts)) {
				$sortinfo['SortBy'] = $default_sortinfo['SortBy'];
			}

			$sortinfo['SortBy'] = $valid_sorts[$sortinfo['SortBy']];

			/**
			 * Validate the sort direction
			 */
			if (in_array(strtolower($sortinfo['Direction']), array('up', 'asc'))) {
				$sortinfo['Direction'] = 'asc';
			} else {
				$sortinfo['Direction'] = 'desc';
			}

			$start = intval($start);
			$perpage = intval($perpage);
			$newsletterid = intval($newsletterid);
		/**
		 * -----
		 */



		/**
		 * Count Only operation
		 */
			if ($countonly) {
				// If list IDs is empty, do not even bother querying the database
				if (empty($listids)) {
					return 0;
				}

				$queryConditions = array();

				// Add default condition
				array_push($queryConditions, 'sn.hiddenby = 0');
				array_push($queryConditions, "sn.sendtype = 'newsletter'");

				/**
				 * Add conditions for filtering record by listid
				 */
					$tempImplodedListIDs = implode(',', $listids);

					array_push($queryConditions, trim("
						sn.statid IN (
							SELECT statid
							FROM {$tableprefix}stats_newsletter_lists
							WHERE listid IN ({$tempImplodedListIDs})
						)
					"));

					unset($tempImplodedListIDs);
				/**
				 * -----
				 */

				// Add condition for filtering record by newsletterid
				if ($newsletterid != 0) {
					array_push($queryConditions, "sn.newsletterid = {$newsletterid}");
				}

				// Construct query string
				$tempImplodedConditions = implode(' AND ', $queryConditions);
				$query = trim("
					SELECT count(1) AS count
					FROM {$tableprefix}stats_newsletters AS sn
					WHERE {$tempImplodedConditions}
				");

				// Query the database
				$result = $this->Db->Query($query);
				if ($result === false) {
					list($msg, $errno) = $this->Db->GetError();
					trigger_error($msg, $errno);
					return false;
				}

				$return = $this->Db->FetchOne($result, 'count');
				$this->Db->FreeResult($result);

				return $return;
			}
		/**
		 * -----
		 */

		// Return an empty string if list IDs are not specified
		if (empty($listids)) {
			return array();
		}

		$select_fields = "snl.statid, starttime, finishtime, htmlrecipients, textrecipients, multipartrecipients, sendsize, bouncecount_soft, bouncecount_hard, bouncecount_unknown, sn.unsubscribecount, n.name";
		$select = "SELECT " . $select_fields . " AS newslettername";

		$where_clause = " FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists snl, " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters sn, " . SENDSTUDIO_TABLEPREFIX . "lists l, " . SENDSTUDIO_TABLEPREFIX . "newsletters n WHERE sn.newsletterid=n.newsletterid AND l.listid=snl.listid AND snl.statid=sn.statid AND snl.listid IN (" . implode(',', $listids) . ") AND hiddenby=0 AND sn.sendtype='newsletter'";
		if ($newsletterid) {
			$where_clause .= " AND n.newsletterid='" . (int)$newsletterid . "'";
		}

		$group_by = "";
		$order_by = " ORDER BY " . $sortinfo['SortBy'] . " " . $sortinfo['Direction'];

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			if (version_compare(SENDSTUDIO_SYSTEM_DATABASE_VERSION, '4.1', '>=')) {
				$select .= ", CONCAT('\\'', GROUP_CONCAT(l.name SEPARATOR '\\',\\''), '\\'') AS listname";
				$group_by = " GROUP BY " . $select_fields;
			} else {
				$select .= ", l.name AS listname";
			}

			$query = $select . $where_clause . $group_by . $order_by;
		} else {
			$query = "SELECT " . $select_fields . " AS newslettername, array_to_string(array(SELECT l.name FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists snl INNER JOIN " . SENDSTUDIO_TABLEPREFIX . "lists l ON snl.listid=l.listid WHERE snl.statid=sn.statid), ',') AS listname";
			$query .= $where_clause;
			$query = stripslashes($query);

			$group_by = " GROUP BY " . $select_fields . ", sn.statid ";

			$query .= $group_by . $order_by;
		}

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);

		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$stats = array();
		while ($row = $this->Db->Fetch($result)) {
			$stats[] = $row;
		}
		return $stats;
	}

	/**
	* GetNewsletterSummary
	* Calculates the summary statistics for a newsletter. The includes the newsletter subject, user who sent the campaign and the lists the campaign was sent to. If $skip_subareas is false it also returns a list of opens, clicks and unsubscribes
	*
	* @param Int $statid The statid to get statistics for
	* @param Boolean $skip_subareas Specify true to not return a list of opens, clicks or unsubscribes. This is used by Stats::Printreport so it only gets the summary, not the last 5 clicks etc.
	* @param Int $subarea_limit The limit of opens, clicks, etc to return. Default is 10.
	*
	* @return Array Returns an array of statsitics
	*/
	function GetNewsletterSummary($statid=0, $skip_subareas=false, $subarea_limit=10)
	{
		$statid = (int)$statid;

		$query = "
			(	SELECT	sn.*,
						n.name AS newslettername,
						n.subject AS newslettersubject,
						n.newsletterid AS newsletterid,
						u.username,
						u.fullname,
						u.emailaddress

				FROM	[|PREFIX|]newsletters n,
						[|PREFIX|]stats_newsletters sn
							LEFT OUTER JOIN [|PREFIX|]users u
								ON (u.userid=sn.sentby)

				WHERE	sn.statid = {$statid}
						AND sn.newsletterid=n.newsletterid
						AND sn.sendtype <> 'triggeremail')

			UNION

			(	SELECT	sn.*,
						n.name AS newslettername,
						n.subject AS newslettersubject,
						n.newsletterid AS newsletterid,
						u.username,
						u.fullname,
						u.emailaddress

				FROM	[|PREFIX|]stats_newsletters AS sn
							JOIN [|PREFIX|]triggeremails AS t
								ON t.statid = sn.statid
							JOIN [|PREFIX|]triggeremails_actions AS ta
								ON (	ta.triggeremailsid = t.triggeremailsid
										AND ta.action = 'send')
							JOIN [|PREFIX|]triggeremails_actions_data AS tad
								ON (	tad.triggeremailsactionid = ta.triggeremailsactionid
										AND tad.datakey = 'newsletterid')
							JOIN [|PREFIX|]newsletters AS n
								ON n.newsletterid = tad.datavalueinteger
							LEFT OUTER JOIN [|PREFIX|]users u
								ON u.userid = t.ownerid

				WHERE	sn.statid = {$statid}
						AND sn.sendtype = 'triggeremail')
		";

		$result = $this->Db->Query($query);
		$stats = $this->Db->Fetch($result);

		$lists = array();
		$query = "
			SELECT	l.listid,
					l.name AS listname
			FROM 	[|PREFIX|]lists l,
					[|PREFIX|]stats_newsletter_lists snl
			WHERE	snl.listid=l.listid
					AND snl.statid= {$statid}
		";
		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$lists[$row['listid']] = $row['listname'];
		}
		$stats['lists'] = $lists;

		if ($skip_subareas) {
			return $stats;
		}

		$clicks = array();

		$query = "SELECT lc.clickid, ls.emailaddress, lc.clicktime, l.url FROM " . SENDSTUDIO_TABLEPREFIX . "links l, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls, " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks lc WHERE ls.subscriberid=lc.subscriberid AND l.linkid=lc.linkid AND lc.statid='" . $statid . "' ORDER BY clicktime DESC LIMIT " . $subarea_limit;
		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$clicks[$row['clickid']] = array('emailaddress' => $row['emailaddress'], 'clicktime' => $row['clicktime'], 'url' => $row['url']);
		}
		$stats['clicks'] = $clicks;

		$opens = array();

		$query = "SELECT o.openid, l.emailaddress, o.opentime FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE ls.subscriberid=o.subscriberid AND o.statid='" . $statid . "' ORDER BY opentime DESC LIMIT " . $subarea_limit;

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$opens[$row['openid']] = array('emailaddress' => $row['emailaddress'], 'opentime' => $row['opentime']);
		}
		$stats['opens'] = $opens;


		$unsubscribes = array();

		$query = "SELECT u.subscriberid, l.emailaddress, unsubscribetime, unsubscribeip FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe u, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE l.subscriberid=u.subscriberid AND statid='" . $statid . "' ORDER BY unsubscribetime DESC LIMIT " . $subarea_limit;
		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$unsubscribes[$row['subscriberid']] = array('emailaddress' => $row['emailaddress'], 'unsubscribetime' => $row['unsubscribetime'], 'unsubscribeip' => $row['unsubscribeip']);
		}
		$stats['unsubscribes'] = $unsubscribes;

		return $stats;
	}


	/**
	 * GetTriggerEmailsStats
	 * Fetches a list of statistic records that will be used to list available trigger email statistics
	 *
	 * The statistics itself is stored in stats_newsletters table with "sendtype" set as "triggeremail"
	 *
	 * The method will return either of the following:
	 * - (Array) An array of statistic record
	 * - (Integer) Newsletter statistic record count
	 * - (Boolean: FALSE) When an error occured
	 *
	 * @param Int $userid The owner of the trigger emails
	 * @param Array $sortinfo Sort informatio for the results. Valid keys are SortBy to specify the column to sort by and Direction (asc or desc) for the direction of the sort
	 * @param Boolean $countonly Specify true to only return the number of campaigns, false to return a list of campaigns
	 * @param Int $start The offset to start returning results from
	 * @param Int $perpage The number of results to return
	 *
	 * @see Stats::TriggerEmailsStats_List()
	 *
	 * @return Mixed Return an array or statistic record or an integer (depending on the parameter $countonly) if successful, FALSE otherwise
	 */
	function GetTriggerEmailsStats($userid, $sortinfo = array(), $countonly = false, $start = 0, $perpage = 10)
	{
		/**
		 * Static Local variables
		 */
			static $valid_sorts = array (
				'triggeremail'	=> 'LOWER(t.name)',
				'newsletter'	=> 'LOWER(nw.name)',
				'triggertype'	=> 't.triggertype',
				'triggerhours'	=> 't.triggerhours',
				'recipients'	=> '(sn.htmlrecipients + sn.textrecipients + sn.multipartrecipients)',
				'unsubscribes'	=> 'sn.unsubscribecount',
				'bounces'		=> '(sn.bouncecount_soft + sn.bouncecount_hard + sn.bouncecount_unknown)'
			);

			static $default_sortinfo = array (
				'SortBy'	=> 'triggeremail',
				'Direction'	=> 'desc'
			);

			static $tableprefix = SENDSTUDIO_TABLEPREFIX;
		/**
		 * -----
		 */


		/**
		 * Sanitize parameters
		 */
			$userid = intval($userid);

			if (!is_array($sortinfo)) {
				/**
				 * sortinfo must be an array...
				 * If non-array is passed in, replace it with the default sortinfo
				 */
				$sortinfo = $default_sortinfo;
			} else {
				/**
				 * Check whether or not the required sort information is availabe.
				 * If not, use the default value for each data
				 */
				foreach ($default_sortinfo as $key => $value) {
					if (!array_key_exists($key, $sortinfo)) {
						$sortinfo[$key] = $value;
					}
				}
			}

			/**
			 * Check for valid sort by column
			 */
			if (!array_key_exists($sortinfo['SortBy'], $valid_sorts)) {
				$sortinfo['SortBy'] = $default_sortinfo['SortBy'];
			}

			$sortinfo['SortBy'] = $valid_sorts[$sortinfo['SortBy']];

			/**
			 * Validate the sort direction
			 */
			if (in_array(strtolower($sortinfo['Direction']), array('up', 'asc'))) {
				$sortinfo['Direction'] = 'asc';
			} else {
				$sortinfo['Direction'] = 'desc';
			}

			$start = intval($start);
			$perpage = intval($perpage);
		/**
		 * -----
		 */


		$queryString = '';
		$querySelect = array();
		$queryTables = array();
		$queryConditions = array();
		$queryOrder = array();


		// Add default table
		array_push($queryTables, trim("
			{$tableprefix}triggeremails AS t
		"));


		if ($countonly) {
			array_push($querySelect, 'count(1) AS count');
		} else {
			array_push($querySelect, 'sn.*');
			array_push($querySelect, 't.name AS triggeremailsname');
			array_push($querySelect, 't.triggeremailsid AS triggeremailsid');
			array_push($querySelect, 't.triggertype AS triggeremailstype');
			array_push($querySelect, 't.triggerhours AS triggeremailshours');

			array_push($queryTables, "{$tableprefix}stats_newsletters AS sn");

			array_push($queryConditions, 't.statid = sn.statid');
			array_push($queryConditions, 'sn.hiddenby = 0');

			array_push($queryOrder, "{$sortinfo['SortBy']} {$sortinfo['Direction']}");
		}

		// Add filtering condition for limiting the trigger email by specifying its ID
		if (!empty($userid)) {
			array_push($queryConditions, 't.ownerid =' . $userid);
		}


		/**
		 * Construct query
		 */
			$tempImplodeSelect = implode(',', $querySelect);
			$tempImplodeTables = implode(',', $queryTables);
			$tempImplodeConditions = '';
			$tempImplodeOrder = '';

			if (count($queryConditions) != 0) {
				$tempImplodeConditions = ' WHERE ' . implode(' AND ', $queryConditions);
			}

			if (count($queryOrder) != 0) {
				$tempImplodeOrder = ' ORDER BY ' . implode(',', $queryOrder);
			}

			$queryString = "
				SELECT {$tempImplodeSelect}
				FROM {$tempImplodeTables}
				{$tempImplodeConditions}
				{$tempImplodeOrder}
			";
		/**
		 * -----
		 */



		/**
		 * Count Only operation... Will query the database and return the number of available records.
		 */
			if ($countonly) {
				// Query the database
				$result = $this->Db->Query($queryString);
				if ($result === false) {
					list($msg, $errno) = $this->Db->GetError();
					trigger_error($msg, $errno);
					return false;
				}

				$return = $this->Db->FetchOne($result, 'count');
				$this->Db->FreeResult($result);

				return $return;
			}
		/**
		 * -----
		 */


		if ($perpage != 'all' && ($start || $perpage)) {
			$queryString .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($queryString);

		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$records = array();
		while ($row = $this->Db->Fetch($result)) {
			array_push($records, $row);
		}
		$this->Db->FreeResult($result);

		return $records;
	}

	/**
	 * GetTriggerEmailsStatsRecord
	 * Get trigger emails statstic record. The record will also include any variables that are necessary from the trigger email table.
	 * @param Integer $triggeremailid Trigger emails ID
	 * @return Mixed Returns an array representing trigger emails statistics record if found, NULL otherwise
	 */
	function GetTriggerEmailsStatsRecord($triggeremailid)
	{
		$triggeremailid = intval($triggeremailid);
		if ($triggeremailid == 0) {
			return null;
		}

		$query = "
			SELECT	sn.*,
					t.name AS triggeremailsname,
					t.triggeremailsid AS triggeremailsid,
					t.triggertype AS triggeremailstype,
					t.triggerhours AS triggeremailshours,
					u.userid AS owneruserid,
					u.username AS ownerusername,
					u.emailaddress AS owneremail,
					u.fullname AS ownername
			FROM	[|PREFIX|]triggeremails AS t
						JOIN [|PREFIX|]stats_newsletters AS sn
							ON t.statid = sn.statid
						JOIN [|PREFIX|]users AS u
							ON t.ownerid = u.userid

			WHERE
				t.triggeremailsid = {$triggeremailid}
		";

		$result = $this->Db->Query($query);

		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$record = $this->Db->Fetch($result);
		$this->Db->FreeResult($result);

		return $record;
	}

	/**
	* IsFinished
	* Determines whether or not a statid is for a completed send.
	*
	* @param Int $statid The statid to check
	* @param String $statstype The type of statistics the statid is for, specify n for newsletter
	*
	* @see GetStatsTable
	*
	* @return Boolean Returns true is the statid is completed, false if it is not
	*/
	function IsFinished($statid=0, $statstype='n')
	{
		$statid = (int)$statid;
		if ($statid <= 0) {
			return false;
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		$query = "SELECT finishtime FROM " . SENDSTUDIO_TABLEPREFIX . $table . " WHERE statid='" . $statid. "'";
		$result = $this->Db->Query($query);
		$finish_time = $this->Db->FetchOne($result, 'finishtime');

		if ($finish_time > 0) {
			return true;
		}
		return false;
	}

	/**
	* IsHidden
	* Determines whether or not a statid is hidden.
	*
	* @param Int $statid The statid to check
	* @param String $statstype The type of statistics the statid is for, specify n for newsletter
	*
	* @see GetStatsTable
	*
	* @return Boolean Returns true is the statid is hidden, false if it is not
	*/
	function IsHidden($statid=0, $statstype='n')
	{
		$statid = (int)$statid;
		if ($statid <= 0) {
			return false;
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		$query = "SELECT hiddenby FROM " . SENDSTUDIO_TABLEPREFIX . $table . " WHERE statid='" . $statid. "'";
		$result = $this->Db->Query($query);
		$hiddenby = $this->Db->FetchOne($result, 'hiddenby');

		if ($hiddenby != 0) {
			return true;
		}
		return false;
	}

	/**
	* GetAutoresponderStats
	* Fetches a list of autoresponders there are statistics for. This is used to list the autoresponders on the Autoresponders Stats page.
	*
	* @param Array $listids The listids to get autoresponders for
	* @param Array $sortinfo Sort information for the results. Valid keys are SortBy to specify the column to sort by and Direction (asc or desc) for the direction of the sort
	* @param Boolean $countonly Specify true to only return the number of autoresponders, false to return a list of autoresponders
	* @param Int $start The offset to start returning results from
	* @param Int $perpage The number of results to return
	* @param Int $autoresponderid Restrict results to a specific autoresponder
	*
	* @see Stats::PrintAutoresponderStats_Step1
	*
	* @return Array Returns an array of autoresponders
	*/
	function GetAutoresponderStats($listids=array(), $sortinfo=array(), $countonly=false, $start=0, $perpage=10, $autoresponderid=0)
	{
		$start = (int)$start;

		if (!is_array($listids)) {
			$listids = array($listids);
		}

		$listids = $this->CheckIntVars($listids);

		if (empty($listids)) {
			$listids = array('0');
		}

		if ($countonly) {
			$query = "SELECT COUNT(a.autoresponderid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "lists l, " . SENDSTUDIO_TABLEPREFIX . "autoresponders a LEFT OUTER JOIN " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders sa ON (sa.autoresponderid=a.autoresponderid) WHERE l.listid=a.listid AND l.listid IN (" . implode(',', $listids) . ") AND hiddenby=0";
			if ($autoresponderid) {
				$query .= " AND a.autoresponderid='" . (int)$autoresponderid . "'";
			}

			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$orderby = 'LOWER(a.name)';
		$orderdirection = 'desc';

		if (strtolower($sortinfo['Direction']) == 'up' || strtolower($sortinfo['Direction']) == 'asc') {
			$orderdirection = 'asc';
		}

		$valid_sorts = array(
			'autoresponder' => 'LOWER(a.name)',
			'list' => 'LOWER(l.name)',
			'recipients' => '(htmlrecipients + textrecipients + multipartrecipients)',
			'unsubscribes' => 'sa.unsubscribecount',
			'bounces' => '(bouncecount_soft + bouncecount_hard + bouncecount_unknown)',
			'delay' => 'hoursaftersubscription'
		);

		if (in_array(strtolower($sortinfo['SortBy']), array_keys($valid_sorts))) {
			$orderby = $valid_sorts[$sortinfo['SortBy']];
		}

		$query = "SELECT sa.statid AS statid, a.autoresponderid AS autoresponderid, a.name as autorespondername, l.name as listname, (htmlrecipients + textrecipients + multipartrecipients) as sendsize, sa.unsubscribecount, bouncecount_soft, bouncecount_hard, bouncecount_unknown, hoursaftersubscription FROM " . SENDSTUDIO_TABLEPREFIX . "lists l, " . SENDSTUDIO_TABLEPREFIX . "autoresponders a LEFT OUTER JOIN " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders sa ON (sa.autoresponderid=a.autoresponderid) WHERE l.listid=a.listid AND l.listid IN (" . implode(',', $listids) . ") AND hiddenby=0";
		if ($autoresponderid) {
			$query .= " AND a.autoresponderid='" . (int)$autoresponderid . "'";
		}
		$query .= " ORDER BY " . $orderby . " " . $orderdirection;

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$stats = array();
		while ($row = $this->Db->Fetch($result)) {
			$stats[] = $row;
		}
		return $stats;
	}

	/**
	* GetAutoresponderSummary
	* Calculates the summary statistics for an autoresponder. The includes the autoresponder name, subject, user who created the autoresponder and the lists the autoresponder is for. If $skip_subareas is false it also returns a list of opens, clicks and unsubscribes
	*
	* @param Int $autoresponderid The autoresponderid to get stats for
	* @param Boolean $skip_subareas Specify true to not return a list of opens, clicks or unsubscribes. This is used by Stats::Printreport so it only gets the summary, not the last 5 clicks etc.
	* @param Int $subarea_limit The limit of opens, clicks, etc to return. Default is 10.
	*
	* @return Array Returns an array of statsitics
	*/
	function GetAutoresponderSummary($autoresponderid=0, $skip_subareas=false, $subarea_limit=10)
	{
		$autoresponderid = (int)$autoresponderid;

		$query = "SELECT sa.*, a.*, a.subject AS autorespondersubject, a.name AS autorespondername, u.username, u.fullname, u.emailaddress, l.name AS listname FROM " . SENDSTUDIO_TABLEPREFIX . "lists l, " . SENDSTUDIO_TABLEPREFIX . "autoresponders a LEFT OUTER JOIN " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders sa ON (sa.autoresponderid=a.autoresponderid) LEFT OUTER JOIN " . SENDSTUDIO_TABLEPREFIX . "users u ON (a.ownerid = u.userid) WHERE a.autoresponderid='" . $autoresponderid . "' AND a.listid=l.listid AND sa.hiddenby=0";

		$result = $this->Db->Query($query);
		$stats = $this->Db->Fetch($result);
		if ($skip_subareas) {
			return $stats;
		}

		$clicks = array();

		$query = "SELECT lc.clickid, s.emailaddress, lc.clicktime, l.url FROM " . SENDSTUDIO_TABLEPREFIX . "links l, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers ls, " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks lc WHERE ls.subscriberid=lc.subscriberid AND l.linkid=lc.linkid AND lc.statid='" . $stats['statid'] . "' ORDER BY clicktime DESC LIMIT " . $subarea_limit;

		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$clicks[$row['clickid']] = array('emailaddress' => $row['emailaddress'], 'clicktime' => $row['clicktime'], 'url' => $row['url']);
		}
		$stats['clicks'] = $clicks;

		$opens = array();

		$query = "SELECT o.openid, l.emailaddress, o.opentime FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens o, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE l.subscriberid=o.subscriberid AND o.statid='" . $stats['statid'] . "' ORDER BY opentime DESC LIMIT " . $subarea_limit;

		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$opens[$row['openid']] = array('emailaddress' => $row['emailaddress'], 'opentime' => $row['opentime']);
		}
		$stats['opens'] = $opens;


		$unsubscribes = array();

		$query = "SELECT u.subscriberid, l.emailaddress, unsubscribetime, unsubscribeip FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe u, " . SENDSTUDIO_TABLEPREFIX . "list_subscribers l WHERE l.subscriberid=u.subscriberid AND statid='" . $stats['statid'] . "' ORDER BY unsubscribetime DESC LIMIT " . $subarea_limit;

		$result = $this->Db->Query($query);

		while ($row = $this->Db->Fetch($result)) {
			$unsubscribes[$row['subscriberid']] = array('emailaddress' => $row['emailaddress'], 'unsubscribetime' => $row['unsubscribetime'], 'unsubscribeip' => $row['unsubscribeip']);
		}
		$stats['unsubscribes'] = $unsubscribes;

		return $stats;
	}

	/**
	* GetUniqueLinks
	* Fetches a list of unique links for a campaign or autoresponder.
	*
	* @param Array $statids A list of statids to get the unique links for
	* @param Int $listid Restrict results to subscribers from a specific list
	*
	* @see Remote_Stats::Process
	* @see Stats::DisplayAutoresponderLinks
	* @see Stats::DisplayListLinks
	*
	* @return Array Returns a list of unique links
	*/
	function GetUniqueLinks($statids=array(),$listid = 0)
	{
		if (!is_array($statids)) {
			$statids = array($statids);
		}

		$statids = $this->CheckIntVars($statids);
		if (empty($statids)) {
			$statids = array('0');
		}

		$listid = (int)$listid;

		$listSQLSelect = '';
		$listSQLJoin = '';
		$listSQLGroup = '';
		if ($listid > 0) {
			$listSQLSelect = ', ls.listid AS listid';
			$listSQLJoin = "
				INNER JOIN [|PREFIX|]list_subscribers ls
					ON (
						ls.listid = {$listid}
						AND sl.subscriberid = ls.subscriberid
					)
			";
			$listSQLGroup = ', ls.listid';
		}

		$implodedStats = implode(',', $statids);
		$query = "
			SELECT		l.linkid AS linkid,
						l.url AS url
						{$listSQLSelect}

			FROM		[|PREFIX|]stats_linkclicks AS sl
							JOIN [|PREFIX|]links AS l
								ON sl.linkid = l.linkid
							{$listSQLJoin}

			WHERE		sl.statid IN({$implodedStats})

			GROUP BY	l.url, l.linkid {$listSQLGroup}
		";

		$result = $this->Db->Query($query);

		$return = array();
		while ($row = $this->Db->Fetch($result)) {
			$row['url'] = $row['url'];
			$return[] = $row;
		}

		return $return;
	}

	/**
	* Unsubscribe
	* Increments the unsubscribe count for a statid
	*
	* @param Int $statid The statid to update the count for
	* @param String $statstype The type of statistics this is for (n for newsletter, a for autoresponder)
	*
	* @see Subscribers_API::UnsubscribeSubscriber
	*
	* @return Mixed Returns false on failure otherwise returns a resource for the query
	*/
	function Unsubscribe($statid, $statstype)
	{
		$statid = (int)$statid;

		if ($statid <= 0) {
			return false;
		}

		$table = $this->GetStatsTable($statstype);

		if (!$table) {
			return false;
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . $table . " SET unsubscribecount=unsubscribecount + 1 WHERE statid='" . $statid . "'";
		$result = $this->Db->Query($query);

		/**
		* We don't keep a separate table for unsubscribes, this is done in Subscribers_API::UnsubscribeSubscriber
		*/
		return $result;
	}

	/**
	* RecordOpen
	* Increments the open count for a statistics entry
	*
	* @param Array $open_details An array containing the keys 'subscriberid', 'statid', and 'opentype' where opentype is h for an HTML email or t for a text email.
	* @param String $statstype The type of statistics entry this is for, 'n' for newsletter or 'a' for autoresponder
	* @param Boolean $from_link_click Specify true if this open is being recorded because a link was clicked on or false if it is being recorded because the open image was loaded
	*
	* @see link.php
	* @see open.php
	*
	* @return Boolean Returns true on success, false on failure
	*
	* @uses EventData_IEM_STATSAPI_RECORDOPEN
	*/
	function RecordOpen($open_details=array(), $statstype, $from_link_click=false)
	{
		if (!isset($open_details['subscriberid'])) {
			return false; // if there's no subscriber id, it's probably an invalid array passed in.
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		$record_open = true;

		/**
		* this is the number of minutes to record link clicks as 'opens'.
		* that is - if you click a link and tries to record an open,
		* if it's in the last $number_of_mins of the last open recorded for that subscriber
		* it is not logged as a separate open.
		*/
		$number_of_mins = 2;

		/**
		* check how many times this subscriber has 'opened' the newsletter.
		* if this returns anything, they have either displayed the open image
		* or clicked a link in the past.
		*/
		$query = "SELECT opentime, fromlink FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens WHERE subscriberid='" . (int)$open_details['subscriberid'] . "' AND statid='" . (int)$open_details['statid'] . "' ORDER BY opentime DESC LIMIT 1";
		$result = $this->Db->Query($query);
		$last_open_row = $this->Db->Fetch($result);
		$row_count = $this->Db->CountResult($result);
		$this->Db->FreeResult($result);

		if (!empty($last_open_row)) {
			$cutoff = $this->GetServerTime() - ($number_of_mins * 60);
			$last_opened = $last_open_row['opentime'];

			// Anything recorded AFTER cutoff timestamp will not be recorded
			if ($last_opened > $cutoff) {
				$record_open = false;
			}
		}

		if (!$record_open) {
			return true;
		}

		/**
		 * Trigger Event
		 */
			$tempEventData = new EventData_IEM_STATSAPI_RECORDOPEN();
			$tempEventData->open_details = &$open_details;
			$tempEventData->statstype = &$statstype;
			$tempEventData->from_link_click = &$from_link_click;
			$tempEventData->have_been_recorded = ($row_count != 0);

			if (!$tempEventData->trigger()) {
				// Trigger cancel the record open event
				return true;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$opentype = 'u';
		if (isset($open_details['opentype'])) {
			$opentype = strtolower($open_details['opentype']);
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . $table . " SET emailopens=emailopens + 1";

		// if there is only one open for this subscriber after just adding that record, update the unique counter as well.
		if ($row_count == 0) {
			$query .= ", emailopens_unique=emailopens_unique+1";
		}

		if ($opentype == 'h') {
			$query .= ", htmlopens=htmlopens + 1";
			if ($row_count == 0) {
				$query .= ", htmlopens_unique=htmlopens_unique + 1";
			}
		} elseif ($opentype == 't') {
			$query .= ", textopens=textopens + 1";
			if ($row_count == 0) {
				$query .= ", textopens_unique=textopens_unique + 1";
			}
		} else {
			// This ensures a junk opentype will be set to something valid.
			$opentype = 'u';
		}

		$query .= " WHERE statid='" . (int)$open_details['statid'] . "'";
		$result = $this->Db->Query($query);

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens(opentime, openip, subscriberid, statid, fromlink, opentype) VALUES('" . (int)$open_details['opentime'] . "', '" . $this->Db->Quote($open_details['openip']) . "', '" . (int)$open_details['subscriberid'] . "', '" . (int)$open_details['statid'] . "', '" . (int)$from_link_click . "', '" . $this->Db->Quote($opentype) . "')";
		$result = $this->Db->Query($query);

	}

	/**
	* RecordLinkClick
	* Increments the link click count for a statistics entry
	*
	* @param Array $clickdetails An array containing the keys 'subscriberid', 'statid', and 'clicktime'
	* @param String $statstype The type of statistics entry this is for, 'n' for newsletter or 'a' for autoresponder
	*
	* @see link.php
	*
	* @return Boolean Returns true on success, false on failure
	*
	* @uses EventData_IEM_STATSAPI_RECORDLINKCLICK
	*/
	function RecordLinkClick($clickdetails=array(), $statstype)
	{
		if (!isset($clickdetails['subscriberid'])) {
			return false; // if there's no subscriber id, it's probably an invalid array passed in.
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		$record_click = true;

		/**
		* this is the number of seconds to record link clicks as 'opens'.
		* that is - if you click a link and tries to record a click,
		* if it's in the last $number_of_seconds of the last clicked recorded for that subscriber
		* it is not logged as a separate open.
		*/
		$number_of_seconds = 5;

		/**
		* check how many times this subscriber has 'opened' the newsletter.
		* if this returns anything, they have either displayed the open image
		* or clicked a link in the past.
		*/
		$query = "SELECT clicktime FROM " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks WHERE subscriberid='" . (int)$clickdetails['subscriberid'] . "' AND statid='" . (int)$clickdetails['statid'] . "' AND linkid='" . (int)$clickdetails['linkid'] . "' ORDER BY clicktime DESC LIMIT 1";
		$result = $this->Db->Query($query);
		$row_count = $this->Db->CountResult($result);

		if ($row_count) {
			$now = $this->GetServerTime();
			$last_click_row = $this->Db->Fetch($result);
			$last_click = $last_click_row['clicktime'];
			if ($last_click && (($now - $last_click) < $number_of_seconds)) {
				$record_click = false;
			}
		}

		if (!$record_click) {
			return;
		}

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_STATSAPI_RECORDLINKCLICK();
			$tempEventData->click_details = &$clickdetails;
			$tempEventData->statstype = &$statstype;
			$tempEventData->have_been_recorded = ($row_count != 0);

			if (!$tempEventData->trigger()) {
				// Trigger cancel the record link click event
				return true;
			}

			unset($tempEventData);
		/**
		 * -----
		 */

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks(clicktime, clickip, subscriberid, statid, linkid) VALUES('" . (int)$clickdetails['clicktime'] . "', '" . $this->Db->Quote($clickdetails['clickip']) . "', '" . (int)$clickdetails['subscriberid'] . "', '" . (int)$clickdetails['statid'] . "', '" . (int)$clickdetails['linkid'] . "')";
		$this->Db->Query($query);

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . $table . " SET linkclicks=linkclicks + 1 WHERE statid='" . (int)$clickdetails['statid'] . "'";
		$result = $this->Db->Query($query);
	}

	/**
	* RecordForward
	* Increments the forward count for a statistics entry
	*
	* @param Array $forward_details An array containing the keys 'subscriberid', 'statid', 'forwardtime', and 'forwardip'
	* @param String $statstype The type of statistics entry this is for, 'n' for newsletter or 'a' for autoresponder
	*
	* @see send_friend.php
	*
	* @return Boolean Returns true on success, false on failure
	*/
	function RecordForward($forward_details=array(), $statstype)
	{
		if (!isset($forward_details['subscriberid'])) {
			return false; // if there's no subscriber id, it's probably an invalid array passed in.
		}

		$table = $this->GetStatsTable($statstype);
		if (!$table) {
			return false;
		}

		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards(forwardtime, forwardip, subscriberid, statid, listid, subscribed, emailaddress) VALUES('" . (int)$forward_details['forwardtime'] . "', '" . $this->Db->Quote($forward_details['forwardip']) . "', '" . (int)$forward_details['subscriberid'] . "', '" . (int)$forward_details['statid'] . "', '" . (int)$forward_details['listid'] . "', 0, '" . $this->Db->Quote($forward_details['emailaddress']) . "')";
		$result = $this->Db->Query($query);

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . $table . " SET emailforwards=emailforwards + 1";
		$query .= " WHERE statid='" . (int)$forward_details['statid'] . "'";
		$result = $this->Db->Query($query);
	}

	/**
	* RecordForwardSubscribe
	* Records when a user subscribed to a list from a forwarded email
	*
	* @param String $emailaddress The emailaddress of the user who subscribed
	* @param Int $subscriber_id The subscriberid of the new subscriber
	* @param Array $lists An array of listids the subscriber subscribed to
	*
	* @see confirm.php
	* @see form.php
	*
	* @return Mixed Returns false on failure or a query resource on success
	*/
	function RecordForwardSubscribe($emailaddress='', $subscriber_id=0, $lists=array())
	{
		$lists = $this->CheckIntVars($lists);

		if (!$emailaddress || !is_numeric($subscriber_id) || empty($lists)) {
			return false;
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "stats_emailforwards SET subscribed='" . (int)$subscriber_id . "' WHERE emailaddress='" . $this->Db->Quote($emailaddress) . "' AND listid IN (" . implode(',', $lists) . ")";
		$result = $this->Db->Query($query);
		return $result;
	}

	/**
	 * Checks whether this user is allowed to send these emails or not. This is 
	 * used when the size of the send queue has changed.
	 *
	 * @param object $user_object        User Object to check.
	 * @param int    $original_queuesize The size of the queue when the send was 
	 *                                   originall scheduled
	 * @param int    $new_queuesize      The size of the queue to check.
	 * @param int    $queuetime          The time when you are trying to send / schedule the queue.
	 * @param int    $statid             The statid to recheck the send queue for
	 *
	 * @see Jobs_Send_API::ProcessJob
	 * @see NotifyAdmin
	 *
	 * @return array Returns an array of status and a language variable describing why it can't be sent. This allows us to differentiate between whether it's a "maxemails" issue or a "per month" issue.
	 */
	function ReCheckUserStats(&$user, $original_queuesize=0, $new_queuesize=0, $queuetime=0)
	{
                // if they have no limits, then no need to do any other checks
		if ($user->hasUnlimitedCredit()) {return array(true, false);}
		
                // if the queuesize did not change or is less than the original just return; no extra credit to check
		if($new_queuesize === $original_queuesize || $new_queuesize < $original_queuesize) {return array(true, false);}
                
                $queueSize = (int) $new_queuesize;
                
		if (!$user->hasUnlimitedMonthlyCredit()){
                    $monthly = (int) API_USERS::creditAvailableThisMonth($user->userid, false, $queuetime);
                }
                
                if (!$user->hasUnlimitedTotalCredit()){
                    $total = (int) API_USERS::creditAvailableFixed($user->userid);
                }
		
		// do monthly credit check
		if (isset($monthly) && $queueSize > $monthly){return array(false, 'OverLimit_PerMonth');}

		// do total credit check
		if (isset($total) && $queueSize > $total) {return array(false, 'OverLimit_MaxEmails');}

		return array(true, false);
	}

	/**
	 * Checks whether this user is allowed to send these emails or not.
	 *
	 * @param object $user_object User Object to check.
	 * @param int    $queuesize   The size of the queue to check.
	 * @param int    $queuetime   The time when you are trying to send / schedule the queue.
	 *
	 * @return array Returns an array of status and a language variable describing why it can't be sent. This allows us to differentiate between whether it's a "maxemails" issue or a "per month" issue.
	 */
	public function CheckUserStats(User_API $user, $queueSize = 0, $queuetime=0)
	{
                // if they have no limits, then no need to do any other checks
		if ($user->hasUnlimitedCredit()) {return array(true, false);}

		$queueSize = (int) $queueSize;
                
		if (!$user->hasUnlimitedMonthlyCredit()){
                    $monthly = (int) API_USERS::creditAvailableThisMonth($user->userid, false, $queuetime);
                }
                
                if (!$user->hasUnlimitedTotalCredit()){
                    $total = (int) API_USERS::creditAvailableFixed($user->userid);
                }
                
		// do monthly credit check
		if (isset($monthly) && $queueSize > $monthly){return array(false, 'OverLimit_PerMonth');}

		// do total credit check
		if (isset($total) && $queueSize > $total) {return array(false, 'OverLimit_MaxEmails');}

		return array(true, false);
	}

	/**
	* RecordBounceInfo
	* Records a bounce in autoresponder/newsletters statistics
	*
	* @param Int $subscriberid The subscriberid that bounced
	* @param Int $statid The statistics entry to record the bounce for
	* @param String $bouncetype The type of bounce, 'soft' or 'hard'
	*
	* @see Bounce_API::ProcessBody
	*
	* @return Boolean Returns true on success, false if the bounce was not recorded
	*/
	function RecordBounceInfo($subscriberid=0, $statid=0, $bouncetype='soft')
	{
		$statid = (int)$statid;

		$query = "SELECT statid, 'stats_autoresponders' AS tabletype FROM " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders WHERE statid='" . $statid . "' UNION SELECT statid, 'stats_newsletters' AS tabletype FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters WHERE statid='" . $statid . "'";

		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$row = $this->Db->Fetch($result);
		if (empty($row)) {
			return false;
		}

		$table = $row['tabletype'];

		$bouncetype = strtolower(substr($bouncetype, 0, 4));

		$bounce_table = "bouncecount_" . $bouncetype;
		if (!in_array($bouncetype, array('soft', 'hard'))) {
			$bounce_table = 'bouncecount_unknown';
		}

		$stats_update_query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . $table . " SET " . $bounce_table . "=" . $bounce_table . "+1 WHERE statid='" . (int)$statid . "'";
		$result = $this->Db->Query($stats_update_query);
		return true;
	}

	/**
	* UpdateUserStats
	* Associates a user statistics entry for a job with a statid
	*
	* @param Int $userid The userid that owns the job
	* @param Int $jobid The jobid of the campaign or autoresponder
	* @param Int $statid The statid for the campaign or autoresponder
	*
	* @see Send::Process
	* @see Jobs_Send_API::ProcessJob
	*
	* @return Boolean Returns true
	*/
	function UpdateUserStats($userid, $jobid, $statid)
	{
		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "stats_users SET statid='" . (int)$statid . "' WHERE userid='" . (int)$userid . "' AND jobid='" . (int)$jobid . "'";
		$result = $this->Db->Query($query);
		return true;
	}

	/**
	* DeleteUserStats
	* Deletes a statistics entry for a user
	*
	* @param Int $userid The user id the job belongs to
	* @param Int $jobid The jobid the statistics entry is for
	*
	* @return Boolean Returns false on
	*/
	function DeleteUserStats($userid=0, $jobid=0)
	{
		$userid = (int)$userid;
		$jobid = (int)$jobid;
		if ($userid <= 0 || $jobid <= 0) {
			return false;
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "stats_users WHERE userid='" . $userid . "' AND jobid='" . $jobid . "'";
		$result = $this->Db->Query($query);

		return true;
	}

	/**
	* GetSentNewsletterStats
	* Calculates the total number of emails that have been sent for a specific queueid
	*
	* @param Int $queueid The queueid to lookup the stats for
	*
	* @return Int Returns the number of total emails sent
	*/
	function GetSentNewsletterStats($queueid=0)
	{
		$query = "SELECT SUM(htmlrecipients + textrecipients + multipartrecipients) AS total_sent FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters WHERE queueid='" . (int)$queueid . "'";
		return $this->Db->FetchOne($query, 'total_sent');
	}

	/**
	* ChangeUserStats
	* Changes the queue size of a user statistics entry
	*
	* @param Int $userid Userid to change statistics for
	* @param Int $jobid The jobid to update
	* @param Int $new_size The new queue size
	*
	* @see UpdateUserStats
	*
	* @return Void Returns nothing
	*/
	function ChangeUserStats($userid=0, $jobid=0, $new_size=0)
	{
		$userid = intval($userid);
		$jobid = intval($jobid);
		$new_size = intval($new_size);

		// If user id is not specified, don't bother updating the database.
		if ($userid == 0) {
			return;
		}

		$query = "
			UPDATE	[|PREFIX|]stats_users
			SET		queuesize={$new_size}
			WHERE	userid={$userid}
					AND jobid={$jobid}
		";

		$rs = $this->Db->Query($query);
		if (!$rs) {
			error_log('Stats_API::ChangeUserStats -- Cannot update user stats -- ' . $this->Db->Error());
		}
	}

	/**
	* RecordUserStats
	* Create a new user statistics entry
	*
	* @param Int $userid The userid to create the entry for
	* @param Int $jobid The jobid the statistics entry is for
	* @param Int $queuesize The size of the send queue for the job
	* @param Int $queuetime The time the queue was created
	* @param Int $statid The statid for the newsletter/autoresponder the job is for
	*
	* @see CheckUserStats
	* @see DeleteUserStats
	* @see ChangeUserStats
	* @see UpdateUserStats
	*
	* @return Boolean Returns false if an error occurred, true if the entry was created successfully
	*/
	function RecordUserStats($userid=0, $jobid=0, $queuesize=0, $queuetime=0, $statid=0)
	{
		$userid = intval($userid);
		$jobid = intval($jobid);
		$queuesize = intval($queuesize);
		$queuetime = intval($queuetime);
		$statid = intval($statid);

		if ($queuetime == 0) {
			$queuetime = $this->GetServerTime();
		}

		if ($userid <= 0 || $jobid <= 0 || $queuesize <= 0) {
			return false;
		}

		$query = "
			INSERT INTO [|PREFIX|]stats_users (
				userid, statid, jobid,
				queuesize, queuetime
			)

			VALUES (
				{$userid}, {$statid}, {$jobid},
				{$queuesize}, {$queuetime}
			)
		";
		$result = $this->Db->Query($query);

		return true;
	}

	/**
	 * Refund credit
	 * @param integer $userid User ID
	 * @param integer $jobid Job ID
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 *
	 * @todo error checking
	 */
	function RefundCredit($userid, $jobid)
	{
		$userid = intval($userid);
		$jobid = intval($jobid);

		$this->Db->Query("DELETE FROM [|PREFIX|]stats_users WHERE userid = {$userid} AND jobid = {$jobid}");
		return true;
	}

	/**
	* GetStatsId
	* Fetches a list of statid for a set of lists. This includes all the campaigns for the lists.
	* If no list ids are supplied or they are all invalid (non-int), then you will get an empty array in return.
	*
	* @param String $type The type of statids to return. Currently only supports 'n' for newsletters
	* @param Array $listids An array of listids to get results for. If no valid id's are supplied, you will get an empty array back.
	*
	* @return Array Returns a list of statids
	*/
	function GetStatsIds($type=null, $listids=array())
	{
		$query = '';
		$stats_type = strtolower(substr($type, 0, 1));
		$listids = $this->CheckIntVars($listids);
		$return_list = array();

		if (empty($listids)) {
			return $return_list;
		}

		switch ($stats_type) {
			case 'n':
				$tempListIDs = implode(',', array_keys($listids));

				$query = "
					SELECT		snl.statid AS statid
					FROM		[|PREFIX|]stats_newsletter_lists snl,
								[|PREFIX|]stats_newsletters sn,
								[|PREFIX|]lists l,
								[|PREFIX|]newsletters n
					WHERE		sn.newsletterid = n.newsletterid
								AND l.listid = snl.listid
								AND snl.statid = sn.statid
								AND snl.listid IN ({$$tempListIDs})
					ORDER BY	sn.statid DESC
				";

				unset($tempListIDs);
			break;
		}

		if (empty($query)) {
			return $return_list;
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			error_log('stats_API::GetStatsIds - Cannot query the database -- ' . $this->Db->Error());
		}

		while ($row = $this->Db->Fetch($result)) {
			$return_list[] = $row['statid'];
		}

		$this->Db->FreeResult($result);

		return $return_list;
	}

	/**
	* CalculateStatsType
	* Calculates the calendar type for a statistics entry. This uses settings in the user's session if $idx is true, otherwise uses the settings in the current user's account. The results are set to $this->stats_type
	*
	* @param Boolean $idx Specify try to use settings in session or false to use settings in user account. Defaults to false
	*
	* @return Void Returns nothing
	*/
	function CalculateStatsType($idx=false)
	{
		$thisuser = GetUser();
		$calendar_settings = $thisuser->GetSettings('Calendar');

		if ($idx == true) {
			$calendar_settings = IEM::sessionGet('IndexCalendar');
		}

		if (empty($calendar_settings)) {
			$calendar_settings['DateType'] = 'alltime';
		}

		$calendar_type = strtolower($calendar_settings['DateType']);
		$this->calendar_type = $calendar_type;

		if (in_array($calendar_type, $this->daily_stats_types)) {
			$this->stats_type = 'daily';
		}

		if ($calendar_type == 'custom') {
			// if they are exactly the same day, show the daily graph.
			if ($calendar_settings['From']['Day'] == $calendar_settings['To']['Day'] &&
				$calendar_settings['From']['Mth'] == $calendar_settings['To']['Mth'] &&
				$calendar_settings['From']['Yr'] == $calendar_settings['To']['Yr']) {
					$this->stats_type = 'daily';
				}

			// if they are the same mth & year, then check whether the it's more than 7 days.
			// if it's more than 7 days, it's a monthly graph.
			// if it's less than 7 days, it's a last7days graph.
			if ($calendar_settings['From']['Mth'] == $calendar_settings['To']['Mth'] && $calendar_settings['From']['Yr'] == $calendar_settings['To']['Yr']) {
				if (($calendar_settings['To']['Day'] - $calendar_settings['From']['Day']) > 7) {
					$this->stats_type = 'monthly';
				} else {
					$this->stats_type = 'last7days';
				}
			}
		}

		if ($calendar_type == 'last7days') {
			$this->stats_type = $calendar_type;
		}

		if (in_array($calendar_type, $this->monthly_stats_types)) {
			$this->stats_type = 'monthly';
		}
	}

	/**
	* GetLastNewsletterSent
	* Finds the most recent newsletter send from a user account. This uses the time the campaigns started sending.
	*
	* @param Int $userid The userid to get the lastest campaign for
	*
	* @return Int Returns the timestamp for the latest campaign or false if there was an error
	*/
	function GetLastNewsletterSent($userid=0)
	{
		$userid = (int)$userid;
		if ($userid <= 0) {
			return false;
		}
		$query = "SELECT starttime FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters WHERE sentby='" . $this->Db->Quote($userid) . "' ORDER BY starttime DESC LIMIT 1";
		$result = $this->Db->Query($query);
		return $this->Db->FetchOne($result, 'starttime');
	}

	/**
	* GetUserMailingLists
	* Calculates the number of mailing lists a user has
	*
	* @param Int $userid The userid to get the number of mailing lists for
	*
	* @see Stats::PrintUserStats_Step2
	*
	* @return Int Returns the number of mailing lists for the user
	*/
	function GetUserMailingLists($userid=0)
	{
		$userid = (int)$userid;
		if ($userid <= 0) {
			return false;
		}
		$query = "SELECT COUNT(listid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "lists WHERE ownerid='" . $this->Db->Quote($userid) . "'";
		$result = $this->Db->Query($query);
		return $this->Db->FetchOne($result, 'count');
	}

	/**
	* GetUserAutoresponders
	* Calculates the number of autoresponders a user has
	*
	* @param Int $userid The userid to get the number of autoresponders for
	*
	* @see Stats::PrintUserStats_Step2
	*
	* @return Int Returns the number of autoresponders for the user
	*/
	function GetUserAutoresponders($userid=0)
	{
		$userid = (int)$userid;
		if ($userid <= 0) {
			return false;
		}
		$query = "SELECT COUNT(autoresponderid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "autoresponders WHERE ownerid='" . $this->Db->Quote($userid) . "'";
		$result = $this->Db->Query($query);
		return $this->Db->FetchOne($result, 'count');
	}

	/**
	* GetUserNewsletterStats
	* Calculates the total number of newsletters sent, total emails sent, total unique opens, total opens and total bounces
	*
	* @param Int $userid The userid to get the statistics for
	*
	* @see Stats::PrintUserStats_Step2
	*
	* @return Array Returns an array with the statistics in it
	*/
	function GetUserNewsletterStats($userid=0)
	{
		$userid = (int)$userid;
		if ($userid <= 0) {
			return false;
		}

		$return = array(
			'newsletters_sent' => 0,
			'total_emails_sent' => 0,
			'unique_opens' => 0,
			'total_opens' => 0,
			'total_bounces' => 0
		);

		$query = "SELECT COUNT(statid) AS newsletters_sent, SUM(htmlrecipients + textrecipients + multipartrecipients) AS total_emails_sent, SUM(emailopens_unique) AS unique_opens, SUM(emailopens) AS total_opens, SUM(bouncecount_soft + bouncecount_hard + bouncecount_unknown) AS total_bounces FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters WHERE sentby='" . $this->Db->Quote($userid) . "'";

		$result = $this->Db->Query($query);

		$return = $this->Db->Fetch($result);

		return $return;
	}

	/**
	* SummarizeEmailSend
	* Calculates the number of emails sent for a campaign and adds this to the user_stats_emailsperhour table. This is called when a campaign has finished sending.
	*
	* @param Int $queueid The queueid the send is for
	* @param Int $statid The statid for the send
	* @param Int $userid The userid the send is for
	*
	* @see Stats_API::MarkNewsletterFinished
	*
	* @return Void Returns nothing
	*/
	function SummarizeEmailSend($queueid=0, $statid=0, $userid=0)
	{
		// ----- Initialize common variables for this function
			$totalRecipient = 0;
			$timeStart = 0;
			$timeFinished = 0;

			$mStatID = intval($statid);
			$mUserID = intval($userid);
		// -----


		// ----- Get statistic information from stats_newsletter table
			$rs = $this->Db->Query("
				SELECT	htmlrecipients, textrecipients, multipartrecipients, starttime, finishtime
				FROM	[|PREFIX|]stats_newsletters
				WHERE	statid={$mStatID}
			");

			if ($rs !== false) {
				$row = $this->Db->Fetch($rs);
				$this->Db->FreeResult($rs);

				if ($row !== false) {
					$totalRecipient = $row['htmlrecipients'] + $row['textrecipients'] + $row['multipartrecipients'];
					$timeStart = intval($row['starttime']);
					$timeFinished = intval($row['finishtime']);
				} else {
					error_log('stats_API::SummarizeEmailSend - Newsletter statistic does not exist');
				}
			} else {
				error_log('stats_API::SummarizeEmailSend - Cannot get newsletter statistics');
			}
		// -----


		// ----- Calculate how many emails have already been recorded and subtract that from the total
			$query = "
				SELECT	SUM(emailssent) as count
				FROM	[|PREFIX|]user_stats_emailsperhour
				WHERE	userid = {$mUserID} AND statid = {$mStatID}
			";

			$result = $this->Db->Query($query);
			$totalRecorded = $this->Db->FetchOne($result, 'count');
			if ($totalRecorded > 0) {
				$totalRecipient -= $totalRecorded;
			}
		// -----


		// ----- Calculate and insert statistic to user_stats_emailsperhour table
			if ($totalRecipient > 0) {
				$tempSpan = $timeFinished - $timeStart;
				$tempHours = floor($tempSpan / 3600);
				$tempAverage = ($tempHours == 0 ? 0 : floor($totalRecipient / $tempHours));
				$tempRemainder = ($tempHours == 0 ? $totalRecipient : ($totalRecipient % $tempHours));

				// Get the starting time in increment of hours
				// ie. If the campaign is started on 26th of Feb 2009 14:16,
				// it will contains 26th of Feb 2009 14:00
				// The time is noted as unix timestamp
				$tempCurrentTime = $timeStart - ($timeStart % 3600);

				for ($i = 0; $i <= $tempHours; ++$i) {
					$tempCurrentRecipient = ($i == $tempHours) ? $tempRemainder : $tempAverage;

					$status = $this->Db->Query("
						INSERT INTO [|PREFIX|]user_stats_emailsperhour(statid, sendtime, emailssent, userid)
						VALUES ({$mStatID}, {$tempCurrentTime}, {$tempCurrentRecipient}, {$mUserID})
					");

					if ($status === false) {
						error_log('stats_API::SummarizeEmailSend - Cannot insert into user_stats_emailsperhour table - Error message: ' . $this->Db->Error());
					}

					$tempCurrentTime += 3600;
				}
			}
		// -----
	}

	/**
	* GetUserSendSummary
	* Calculates the total number of emails sent for a user
	*
	* @param Int $userid The userid to get stats for
	* @param String $stats_type The calendary type to group stats by
	* @param String $restrictions An SQL fragment of restrictions for the query
	*
	* @see Stats::PrintUserStats_Step2
	*
	* @return Array Returns an array of emails sent grouped by the calendar type
	*/
	function GetUserSendSummary($userid=0, $stats_type, $restrictions=false)
	{
		$qry = "SELECT SUM(emailssent) AS count, ";
		$qry .= $this->CalculateGroupBy($stats_type, 'sendtime');
		$qry .= " FROM " . SENDSTUDIO_TABLEPREFIX . "user_stats_emailsperhour";
		$qry .= " WHERE userid='" . (int)$userid . "'";
		if ($restrictions) {
			$qry .= $restrictions;
		}

		switch ($stats_type) {
			case 'daily':
				$general_query = ' GROUP BY hr';
			break;

			case 'last7days':
				$general_query = ' GROUP BY dow';
			break;

			case 'last30days':
			case 'monthly':
				$general_query = ' GROUP BY dom';
			break;

			default:
				$general_query = ' GROUP BY mth, yr';
		}

		$qry .= $general_query;

		$return_results = array();

		$result = $this->Db->Query($qry);
		if (!$result) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Unable to execute query: ' . $this->Db->Error(), E_USER_NOTICE);
			return $return_results;
		}

		while ($row = $this->Db->Fetch($result)) {
			$return_results[] = $row;
		}

		$this->Db->FreeResult($result);

		return $return_results;
	}

	/**
	* GetListSummary
	* Calculates the total number of emails sent, bounces, unsubscribes, opens, forwards and link clicks for a list
	*
	* @param Int $listid The listid to get stats for
	*
	* @see Stats::PrintListStats_Step2
	*
	* @return Array Returns an array of the statistics
	*/
	function GetListSummary($listid=0)
	{
		$summary = array(
			'emails_sent' => 0,
			'bouncecount_soft' => 0,
			'bouncecount_hard' => 0,
			'bouncecount_unknown' => 0,
			'unsubscribecount' => 0,
			'emailopens' => 0,
			'emailforwards' => 0,
			'linkclicks' => 0
		);

		$listid = (int)$listid;

		if ($listid <= 0) {
			$summary['emailopens_unique'] = 0;
			$summary['statids'] = array();
			return $summary;
		}

		// this is used by both autoresponders & newsletters.
		$select_query = "SELECT SUM(htmlrecipients + textrecipients + multipartrecipients) AS emails_sent,";
		$select_query .= "SUM(bouncecount_soft) AS bouncecount_soft, SUM(bouncecount_hard) AS bouncecount_hard, SUM(bouncecount_unknown) AS bouncecount_unknown,";
		$select_query .= "SUM(unsubscribecount) AS unsubscribecount,";
		$select_query .= "SUM(emailopens) AS emailopens,";
		$select_query .= "SUM(emailforwards) AS emailforwards,";
		$select_query .= "SUM(linkclicks) AS linkclicks";

		$newsletter_query = $select_query;
		$newsletter_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters sn, ";
		$newsletter_query .= SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists snl";
		$newsletter_query .= " WHERE snl.statid=sn.statid";
		$newsletter_query .= " AND snl.listid='" . $listid . "'";

		/*
		$newsletter_result = $this->Db->Query($newsletter_query);
		// there will only ever be one row, no need to do a loop here.
		$newsletter_row = $this->Db->Fetch($newsletter_result);

		// add the info to the summary.
		foreach ($summary as $p => $item) {
			$summary[$p] += $newsletter_row[$p];
		}
		 */

		$autoresponder_query = $select_query;
		$autoresponder_query .= " FROM " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders sa, ";
		$autoresponder_query .= SENDSTUDIO_TABLEPREFIX . "autoresponders a";
		$autoresponder_query .= " WHERE sa.autoresponderid=a.autoresponderid";
		$autoresponder_query .= " AND a.listid='" . $listid . "'";

		/*
		$autoresponder_result = $this->Db->Query($autoresponder_query);
		// there will only ever be one row, no need to do a loop here.
		$autoresponder_row = $this->Db->Fetch($autoresponder_result);

		// add the info to the summary.
		foreach ($summary as $p => $item) {
			$summary[$p] += $autoresponder_row[$p];
		}
		 */

		$query = $newsletter_query . " UNION " . $autoresponder_query;
		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			foreach ($row as $key => $amount) {
				$summary[$key] += $amount;
			}
		}

		/**
		 * Unsubscribes can also happen through the control panel, so work out that number separately.
		 * Those subscribers won't have a 'statid' in the list_subscribers_unsubscribe table as they weren't removed by a 'stat' (autoresponder or newsletter).
		*/
		$query = "SELECT COUNT(subscriberid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "list_subscribers_unsubscribe WHERE listid='" . $listid . "' AND statid=0";
		$result = $this->Db->Query($query);
		$count = $this->Db->FetchOne($result, 'count');

		$summary['unsubscribecount'] += $count;

		$summary['statids'] = array();

		$statids_query = "SELECT statid FROM ";
		$statids_query .= SENDSTUDIO_TABLEPREFIX . "stats_autoresponders sa, " . SENDSTUDIO_TABLEPREFIX . "autoresponders a";
		$statids_query .= " WHERE sa.autoresponderid=a.autoresponderid AND a.listid='" . $listid . "'";
		$statids_query .= " UNION ALL ";
		$statids_query .= "SELECT statid FROM ";
		$statids_query .= SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists snl";
		$statids_query .= " WHERE snl.listid='" . $listid . "'";

		$result = $this->Db->Query($statids_query);
		while ($row = $this->Db->Fetch($result)) {
			$summary['statids'][] = $row['statid'];
		}

		$summary['emailopens_unique'] = 0;

		/**
		*
		we need to do this in case a subscriber has opened multiple newsletters or autoresponders on the same list. we can't just use the emailopens_unique number from the stats table because that doesn't take into account other newsletters/autoresponders sent to that list.
		so subscriber '1' could open newsletter '1' and autoresponder '1' - which would both be unique for their respective stats but would make an incorrect summary as it would be included twice.
		*/
		if (!empty($summary['statids'])) {
			$unique_opens_query = "SELECT COUNT(DISTINCT subscriberid) AS unique_count FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens WHERE statid IN (" . implode(',', $summary['statids']) . ")";
			$result = $this->Db->Query($unique_opens_query);
			$summary['emailopens_unique'] = $this->Db->FetchOne($result, 'unique_count');
		}

		return $summary;
	}

	/**
	* GetStatsByQueue
	* Fetches the statid associated with a specific queueid
	*
	* @param Int $queueid The queueid to get the statid for
	*
	* @see Sendstudio_Functions::_CleanupOldQueues
	* @see Send::Process
	*
	* @return Int Returns a statid value
	*/
	function GetStatsByQueue($queueid=0)
	{
		$query = "SELECT statid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters WHERE queueid='" . $this->Db->Quote($queueid) . "'";
		return $this->Db->FetchOne($query);
	}

	/**
	* NotifyAdmin
	* Notifies the administrator when a user has exceeded their quota
	*
	* @param Int $userid The userid the notification is for
	* @param Int $size_difference The number of emails the user has exceeded their limit by
	* @param Int $queuetime The time the queue was created at
	* @param String $langvar The langvar to use to describe which limit has been exceeded. This langvar is returned by the CheckUserStats/ReCheckuserStats functions.
	* @param Boolean $stopped_send Specify true if the send has been halted, false if the send is continuing
	*
	* @see Stats_API::ReCheckUserStats
	* @see Stats_API::CheckUserStats
	*
	* @return Void Returns nothing
	*/
	function NotifyAdmin($userid, $size_difference, $queuetime, $langvar, $stopped_send=false)
	{
		$user = GetUser($userid);
		$user_queuetime = AdjustTime($queuetime, false, GetLang('UserDateFormat'));

		require_once(IEM_PATH . '/ext/interspire_email/email.php');

		$email_api = new Email_API();

		$email_api->Set('Subject', GetLang('User_OverQuota_Subject'));

		$username = $user->Get('username');
		if ($user->fullname) {
			$username = $user->fullname;
			$email_api->Set('FromName', $user->fullname);
		} else {
			$email_api->Set('FromName', GetLang('SendingSystem'));
		}

		if ($user->emailaddress) {
			$email_api->Set('FromAddress', $user->emailaddress);
		} else {
			$email_api->Set('FromAddress', GetLang('SendingSystem_From'));
		}

		$over_size = number_format($size_difference, 0, GetLang('NumberFormat_Dec'), GetLang('NumberFormat_Thousands'));

		$extra_mail = '';
		if ($stopped_send) {
			$extra_mail = GetLang('User_OverQuota_StoppedSend');
		}

		$message = sprintf(GetLang('User_OverQuota_Email'), $username, $user->Get('emailaddress'), $user_queuetime, GetLang('User_'.$langvar), $over_size, $extra_mail);

		$email_api->Set('Multipart', false);

		$email_api->AddBody('text', $message);

		$email_api->ClearAttachments();
		$email_api->ClearRecipients();

		$email_api->AddRecipient(SENDSTUDIO_EMAIL_ADDRESS, '', 't');

		$email_api->Send();

		$email_api->ForgetEmail();

		// now send the user notification.

		$email_api->Set('Subject', GetLang('User_OverQuota_Subject'));

		$email_api->Set('FromName', '');

		$email_api->Set('FromAddress', SENDSTUDIO_EMAIL_ADDRESS);

		$message = sprintf(GetLang('User_OverQuota_ToUser_Email'), $user_queuetime, GetLang('User_'.$langvar), $over_size, $extra_mail);

		$email_api->Set('Multipart', false);

		$email_api->AddBody('text', $message);

		$email_api->ClearAttachments();
		$email_api->ClearRecipients();

		$email_api->AddRecipient($user->emailaddress, '', 't');

		$email_api->Send();

		$email_api->ForgetEmail();
	}
}
