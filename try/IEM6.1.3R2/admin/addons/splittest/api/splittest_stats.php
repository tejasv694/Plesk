<?php

require_once(dirname(__FILE__) . '/../../../functions/api/api.php');

/**
 * This is used to retrieve, process and otherwise manipulate split test statistics.
 *
 * @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
 *
 * @uses API
 *
 * @package SendStudio
 * @subpackage SplitTests
 */
class Splittest_Stats_API extends API
{

	/**
	 * statsColumnNameLabels
	 * A useful lookup table between DB column names and UI Labels.
	 *
	 * @var Array
	 */
	private $statsColumnNameLabels = array();

	/**
	 * campaignIdNameCache
	 *
	 * A lookup table of campaign names nad campaing Ids
	 * made up of campaings used in a Splittest send.
	 *
	 * @var Array
	 */
	private $campaignIdNameCache = array();

	/**
	 * listCount
	 * Number of lists sent to in this Split Test.
	 *
	 * @var Int
	 */
	private $listCount = 0;


	/**
	 * newsletterCount
	 * A count of the number of newsletters sent.
	 *
	 * @var Interger
	 */
	private $newsletterCount = 0;

	/**
	 * sendSize
	 * Total number of recipients for a Split Test Campaign Send.
	 *
	 * @var Int
	 */
	private $sendSize = 0;


	/**
	 * newsletterStats
	 * Data derived from the stats_newsletter table.
	 *
	 * @var Array
	 */
	private $newsletterStats = array();


	/**
	 * campaignRecipientCountCache
	 * For each campaign keep a count of what the recipient count was at the time of campaign winner testing.
	 *
	 * @var Array
	 */
	private $campaignRecipientCountCache = array();


	/**
	 * splitType
	 * Type of Split Test Campaign ('percentage' | 'distributed').
	 *
	 * @var String
	 */
	private $splitType;


	/**
	 * splitDetails
	 * Configuration parameters for a Split Test.
	 *
	 * @var Array
	 */
	private $splitDetails = array();

	/**
	 * campaignStats
	 * Records from the stats_newsletters table for each given job id.
	 *
	 * @var Array
	 */
	private $campaignStats = array();


	/**
	 * finishTime
	 * The time the Split Test finished sending if this is 0 the campaign is in progress or timed out
	 * in which case the stats are not final.
	 *
	 * @var Integer
	 */
	private $finishTime = 0;


	/**
	 * recpientsAtCalculation
	 * Determine the number of recipients the winning campaign was sent to at the time we decided who the winner was
	 * this is because we want to keep track of what winners scores were at that time.
	 *
	 * @var Integer
	 */
	private $recpientsAtCalculation;


	/**
	 * winnerRecipientCount
	 * The approx number of recipients the winning campaign was sent to
	 * at the time of calculating who the winner was.
	 *
	 * @var Integer
	 */
	private $winnerRecipientCount = 0;

	/**
	 * campaignWinner
	 * Details about the campaign winner.
	 *
	 * @var Array
	 */
	private $campaignWinner = array();

	/**
	 * splittestPercentage
	 * The percentage value for the splittest.
	 *
	 * @var Integer
	 */
	private $splittestPercentage = 50;


	/**
	 * statsNewsletterFields
	 * A list of relevant column names from the _stats_newsletters table.
	 *
	 * @var Array
	 */
	private $statsNewsletterFields = array('statid', 'finishtime', 'linkclicks', 'emailopens', 'emailopens_unique', 'htmlopens', 'htmlopens_unique', 'textopens', 'textopens_unique', 'unsubscribecount', 'bouncecount_soft', 'bouncecount_hard', 'bouncecount_unknown');

	/**
	 * __construct
	 * Calls the API::__construct method only
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		/**
		 * campaignIdNameCache
		 *
		 * A lookup table of campaign names nad campaing Ids
		 * made up of campaings used in a Splittest send
		 *
		 * @var Array
		 */
		$this->statsColumnNameLabels = array(
			'emailopens_unique' => GetLang('Addon_splittest_emailopens_unique'),
			'emailopens_unique_percent' => GetLang('Addon_splittest_emailopens_unique_precent'),
			'linkclicks' => GetLang('Addon_splittest_linkclicks'),
			'linkclicks_percent' => GetLang('Addon_splittest_linkclicks_percent'),
			'linkclicks_unique' => GetLang('Addon_splittest_linkclicks_unique'),
			'linkclicks_unique_percent' => GetLang('Addon_splittest_linkclicks_unique_percent'),
			'bouncecount_total' => GetLang('Addon_splittest_bouncecount_total'),
			'bouncecount_total_percent' => GetLang('Addon_splittest_bouncecount_total_percent'),
			'unsubscribecount' => GetLang('Addon_splittest_unsubscribecount'),
			'unsubscribecount_percent' => GetLang('Addon_splittest_unsubscribecount_percent'),
		);
	}


	/**
	 * DeleteSplittestStats
	 * Delete split test statistics by job Id
	 *  - a list of job Ids can be provided (by creating the appropriate join)
	 *  tables from which records are removed :
	 *		- email_stats_newsletters
	 *		- email_splittest_statistics
	 *		- splittest_statistics_newsletters
	 * 		- email_splittest_statistics_newsletters
	 *
	 * @param Array $jobids	a list of job Ids (correspnding to records in the stats_newsletters table
	 *
	 * @return Boolean True if the stats were deleted, otherwise false.
	 *
	 */
	public function DeleteSplittestStats($jobids)
	{
		if (empty($jobids)) {
			return false;
		}

		for ($i = 0; $i < count($jobids); $i++ ) {
			$this->Db->StartTransaction();
			$jobid = $jobids[$i];
			$stats = $this->Db->Query('SELECT statid FROM email_stats_newsletters WHERE jobid = ' . intval($jobid));
			if (!$stats) {
				$this->Db->RollBackTransaction();
				return false;
			}

			while ($row = $this->Db->Fetch($stats)) {
				$statid = $row['statid'];
				$query = 'SELECT split_statid FROM email_splittest_statistics_newsletters WHERE newsletter_statid = ' . intval($statid);
				$res = $this->Db->Query($query);

				while ($r = $this->Db->Fetch($res)) {
					$split_statid = $r['split_statid'];
					$deleteSplittestStatsQuery = 'DELETE FROM email_splittest_statistics
													WHERE split_statid = '. intval($split_statid) . '
													AND jobid = ' . intval($jobid);

					$result = $this->Db->Query($deleteSplittestStatsQuery);

					if (!$result) {
						$this->db->RollBackTransaction();
						return false;
					}

					$deleteNewsletterStatsQuery = 'DELETE FROM email_splittest_statistics
													WHERE jobid = ' . intval($jobid);

					$result = $this->Db->Query($deleteNewsletterStatsQuery);

					if (!$result) {
						$this->Db->RollBackTransaction();
						return false;
					}
				}
				$deleteSplittestStatsNewsletterQuery = 'DELETE FROM email_splittest_statistics_newsletters WHERE newsletter_statid = '
				. intval($statid);
				$result = $this->Db->Query($deleteSplittestStatsNewsletterQuery);
				if (!$result) {
					$this->Db->RollBackTransaction();
					return false;
				}
			}

			// Delete records from the stats_newsletters table for the given Splittest job
			$deleteNewsletterStatsQuery = 'DELETE FROM email_stats_newsletters WHERE jobid = ' . intval($jobid);
			$result = $this->Db->Query($deleteNewsletterStatsQuery);
			if (!$result) {
				$this->Db->RollBackTransaction();
				return false;
			}
			$this->Db->CommitTransaction();
		}
		return true;
	}

	/**
	 * GetStats
	 * Gets an array of split test statistics based on the listids etc passed in.
	 * It returns an array including:
	 * - split test name
	 * - start/finish times for the stat
	 * - the split id
	 * - the newsletter names used in the split test
	 * - which list(s)/segment(s) were used in the send
	 *
	 * Then returns the details.
	 *
	 * The sort details include a field name and an order.
	 * The field name defaults to the splitname, but can be one of the extra fields:
	 * - splitname
	 * - starttime
	 * - finishtime
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
	 * @param Array $listids The lists we are showing statistics for.
	 * @param Array $sortinfo How to sort the resulting data.
	 * @param Boolean $countonly Whether to return a count of the number of stats only, or whether to actually return the stats themselves.
	 * @param Int $start The start position (passed to sql as the offset)
	 * @param Int $result_limit The number of results to return (passed to sql as the limit).
	 * @param Boolean $displayAll Set to true if you want to query for all Split Tests otherwise query based on listids
	 * @param Integer $jobid The jobid for a particular Split Test send job. If null the query is based on listids or all all Split Tests
	 *
	 * @return Mixed Returns the number of split tests if you are only doing a count. Otherwise, returns an array of split test stat details. Returns false on error.
	 */
	public function GetStats($listids=array(), $sortinfo=array(), $countonly=false, $start=0, $perpage=10, $displayAll=true, $jobid=null)
	{
		// FIXME: This function call is a big code smell with the presence of the $jobid param. It
		// - overloads the first parameter's meaning (either split_id or list_ids), and
		// - invalidates all the other parameters in between.
		// It should really be refactored.

		$listids = $this->CheckIntVars($listids);
		if (empty($listids)) {
			return false;
		}
		$this->listCount = count($listids);

		// return array
		$results = array();
		$results_jobs = array();

		$query = 'SELECT s.splitid, s.splitname, s.splittype, s.splitdetails, ss.split_statid, ss.starttime, ss.finishtime, ss.jobid, ';
		$query .= 'CASE WHEN ss.finishtime > 0 THEN 0 ELSE 1 END AS finishtime_check';
		$from_clause = '
				FROM
					[|PREFIX|]splittests s INNER JOIN [|PREFIX|]splittest_statistics ss ON (s.splitid=ss.splitid)
				WHERE
					ss.split_statid IN
					(
						SELECT ssn.split_statid
						  FROM [|PREFIX|]stats_newsletter_lists snl
							INNER JOIN
							[|PREFIX|]splittest_statistics_newsletters ssn
							  ON (snl.statid = ssn.newsletter_statid)';

		if ($jobid != null) {
			$from_clause .= '
							INNER JOIN [|PREFIX|]stats_newsletters news
								ON (news.statid = ssn.newsletter_statid)';
		}

		if ($displayAll == true) {
			$from_clause .= ' WHERE snl.listid IN (' . implode(',', $listids) . ')
						GROUP BY ssn.split_statid';
		} else {
			$from_clause .= ' WHERE s.splitid = ' . intval($listids[0]);
			if ($jobid != null) {
				$from_clause .= ' AND news.jobid = ' . intval($jobid);
			}
		}

		$from_clause .= ' )	AND	ss.hiddenby = 0';

		if ($countonly && $displayAll == true) {
			$query = "SELECT COUNT(1) AS count";
			$query .= $from_clause;
			$count = $this->Db->FetchOne($query);
			return $count;
		}

		$query .= $from_clause;

		$valid_fields = array (
			'splitname',
			'splittype',
			'starttime',
			'splitdetails',
			'finishtime',
			'jobid',
		);

		$valid_directions = array (
			'asc',
			'desc',
		);

		$order_field = 'finishtime';
		$order_direction = 'DESC';

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

		if ($displayAll == true) {
			$query .= ' ORDER BY ';
			if ($order_field == 'finishtime') {
				$query .= 'finishtime_check ' . $order_direction . ', ';
			}
			$query .= $order_field . ' ' . $order_direction;
			// If we're not ordering by splitname, use that as a secondary sort.
			if ($order_field != 'splitname') {
				$query .= ', splitname ASC';
			}
		}
		$query .= ' ' . $this->Db->AddLimit(($start - 1) * $perpage, $perpage);
		$result = $this->Db->Query($query);
		if (!$result) {
			trigger_error(__FILE__ . '::' . __METHOD__ . ' -- Unable to query statistics', E_USER_WARNING);
			return $results;
		}

		// Get some statistics about emails campaigns sent
		while ($row = $this->Db->Fetch($result)) {
			// We need the jobid to get the campaign stats for this splittest send
			$jobid = $row['jobid'];

			$row['campaign_names'] = array();

			// Split Test Configuration data
			$this->splitType = $row['splittype'];
			$this->splitDetails = $this->getSplitDetails($row['splitdetails']);
			$row['splitdetails'] = $this->splitDetails;

			// stats_newsletters records for each newsletter sent
			$campaignStats = array();

			// Get some overall Campaign send statistics for the Splittest
			$sendStats = $this->splittestSendStats($row['split_statid'], $jobid);
			$row['bouncecount'] = $sendStats['bouncecount'];
			$row['lists'] = $sendStats['lists'];

			$campaignIds = $this->getCampaignIDs($jobid);

			// keep a count of all the subscribers campaigns for a splittest are sent to
			$totalRecipientCount = 0;

			// if we don't have any campaign IDs in the Split Test configuration then we will never find any stats records
			if (empty($campaignIds)) {
				continue;
			}
			$this->campaignStats = array();
			for ($i=0; $i<count($campaignIds); $i++) {
				$campaignSendData = array();

				// Get the stats for this particular campaign send
				$newsletterStatsData['stats_newsletters'] = $this->getCampaignStats($campaignIds[$i], $jobid);
				$campaignName = $newsletterStatsData['stats_newsletters']['campaign_name'];

				// Add some meta data for the campaign
				$newsletterStatsData['campaign_name'] = $campaignName;
				$row['campaign_names'][] = $campaignName;
				$newsletterStatsData['campaign_id'] = $campaignIds[$i];
				$newsletterStatsData['statistics_id'] = $row['split_statid'];

				$this->campaignStats[$campaignIds[$i]] = $newsletterStatsData;

				if (!array_key_exists($campaignIds[$i], $this->campaignIdNameCache)) {
					$this->campaignIdNameCache[$campaignIds[$i]] = $campaignName;
				}

				$this->campaignRecipientCountCache[$campaignIds[$i]] = $newsletterStatsData['stats_newsletters']['recipients'];
				$totalRecipientCount += $newsletterStatsData['stats_newsletters']['recipients'];
				$this->newsletterCount++;
			}

			$this->sendSize = $totalRecipientCount;

			// report array on campaign statistics and order by best performing
			$compareCampaignStats = $this->compareCampaignStats($this->campaignStats);

			// list campaign details and score for best performing campaign
			$this->campaignWinner = $this->getCampaignWinners($row['splitdetails'], $compareCampaignStats);

			// once we have dertermined the winner set the recipient count at time of calculation
			$this->getRecpientsAtCalculation();

			// stats_newsletters result with final percentage calculations added in
			$this->newsletterStats = $this->finalPercentageStats();

			if ($this->splitType != 'percentage') {
				$row['winner_type_message'] = sprintf(GetLang('Addon_splittest_WonMessage'), $this->campaignWinner['winnerName']);
			}

			$winnerType = $this->campaignWinner['winnerType'];
			$winner = $compareCampaignStats['campaignStatsWinners'];
			if ($winner['emailopens']['value'] == 0 && $winner['linkclicks']['value'] == 0) {
				$winnerType = 'None';
			}

			// some values useful displaying from the template
			$row['campaigns'] = $this->newsletterStats;
			$row['campaign_names'] = implode($row['campaign_names'], ', ');
			$row['campaign_statistics'] = $compareCampaignStats;
			$row['campaign_winner_id'] = $this->campaignWinner['winnerId'];
			$row['campaign_winner_name'] = $this->campaignWinner['winnerName'];
			$row['campaign_winner_type'] = $winnerType;
			$row['winner_message'] = $this->getWinnerMessage();
			$row['total_recipient_count'] = $totalRecipientCount;
			$row['total_recipients_at_calculation'] = $this->recpientsAtCalculation;
			$row['winner_recipient_count'] = $this->campaignWinner['winnerRecipientCount'];
			$row['finishtime'] = self::userTimestamp(self::fixTimestamp($row['finishtime']));
			$row['starttime'] = self::userTimestamp(self::fixTimestamp($row['starttime']));

			$results[$jobid] = $row;
			$results_jobs[] = $jobid;
		}
		$this->Db->FreeResult($result);

		if (!empty($results_jobs)) {
			switch (SENDSTUDIO_DATABASE_TYPE) {
				case 'mysql':
					$query = "
						SELECT	GROUP_CONCAT(l.name SEPARATOR ', ') AS list_names,
								ss.jobid

						FROM	[|PREFIX|]lists AS l
								INNER JOIN [|PREFIX|]stats_newsletter_lists AS snl
									ON (l.listid = snl.listid)
								INNER JOIN [|PREFIX|]splittest_statistics_newsletters AS ssn
									ON (snl.statid = ssn.newsletter_statid)
								INNER JOIN [|PREFIX|]splittest_statistics AS ss
									ON (ssn.split_statid = ss.split_statid)

			            WHERE	ss.jobid IN (" . implode(',', $results_jobs) . ")

			            GROUP BY l.name

			            ORDER BY l.name ASC LIMIT 1
					";

					$result = $this->Db->Query($query);
					if (!$result) {
						trigger_error(__FILE__ . '::' . __METHOD__ . ' -- Unable to query statistics', E_USER_WARNING);
						return $results;
					}

					while ($row = $this->Db->Fetch($result)) {
						$results[$row['jobid']]['list_names'] = $row['list_names'];
					}

					$this->Db->FreeResult($result);
				break;

				case 'pgsql':
					$query = "
						SELECT DISTINCT l.name AS list_names, ss.jobid

						FROM	[|PREFIX|]lists AS l
								INNER JOIN [|PREFIX|]stats_newsletter_lists AS snl
									ON (l.listid = snl.listid)
								INNER JOIN [|PREFIX|]splittest_statistics_newsletters AS ssn
									ON (snl.statid = ssn.newsletter_statid)
								INNER JOIN [|PREFIX|]splittest_statistics AS ss
									ON (ssn.split_statid = ss.split_statid)

			            WHERE	ss.jobid IN (" . implode(',', $results_jobs) . ")

						GROUP BY l.name, ss.jobid

						ORDER BY l.name ASC
					";

					$result = $this->Db->Query($query);
					if (!$result) {
						trigger_error(__FILE__ . '::' . __METHOD__ . ' -- Unable to query statistics', E_USER_WARNING);
						return $results;
					}

					$names = array();
					while ($row = $this->Db->Fetch($result)) {
						if (!array_key_exists($row['jobid'], $names)) {
							$names[$row['jobid']] = array();
						}

						$names[$row['jobid']][] = $row['list_names'];
					}

					$keys = array_keys($names);
					foreach ($keys as $jobid) {
						$results[$jobid]['list_names'] = implode(' ,', $names[$jobid]);
					}

					$this->Db->FreeResult($result);
				break;

				default:
					trigger_error(__FILE__ . '::' . __METHOD__ . ' - Invalid database type', E_USER_WARNING);
					return $results;
				break;
			}
		}

		return $results;
	}

	/**
	 * fixTimestamp
	 * IEM currently stores all timestamps incorrectly. By definition ALL
	 * timestamps are to be interpreted as being GMT (see http://php.net/time).
	 * However, IEM timestamps have had the server timezone offset subtracted
	 * from it, because of a mistaken assumption that the GMT timestamps had
	 * the server timezone offset added to it already.
	 *
	 * Because we now dynamically determine the server timezone offset, this
	 * function will return inaccurate results (off by one hour) when the
	 * server goes into DST for broken timestamps saved outside of DST (and
	 * vice-versa). Hopefully we can change stored timestamps to be in GMT one
	 * day.
	 *
	 * @param Int|String $broken_ts A broken timestamp (one that has had the server timezone offset subtracted from it).
	 *
	 * @return Int A timestamp in GMT.
	 */
	private static function fixTimestamp($broken_ts)
	{
		if (!$broken_ts) {
			// If the timestamp is 0 then maintain this, because its value
			// probably has special meaning.
			return 0;
		}

		// Server timezone offset (seconds).
		$server_offset = date('Z');

		return intval($broken_ts) + $server_offset;
	}

	/**
	 * userTimestamp
	 * When provided with a GMT Unix timestamp, it will return a timestamp
	 * adjusted for the user's timezone, taking into account the server's
	 * timezone offset.
	 *
	 * @see fixTimestamp
	 *
	 * @param Int|String $gmt_ts A valid GMT timestamp.
	 *
	 * @return Int A timestamp that has been adjusted with the current user's timezone offset.
	 */
	private static function userTimestamp($gmt_ts)
	{
		if (!$gmt_ts) {
			return 0;
		}

		$user = GetUser();

		// User timezone offset (seconds).
		$user_offset = $user->Get('usertimezone'); // "GMT-11:30"
		$user_offset = substr($user_offset, 3); // "-11:30"
		$user_offset = str_replace(':3', '.5', $user_offset); // "-11.50"
		$user_offset = str_replace(':', '.', $user_offset);
		$user_offset = floatval($user_offset); // -11.5
		$user_offset = $user_offset * 60 * 60; // to seconds

		$server_offset = date('Z');

		return intval($gmt_ts) + ($user_offset - $server_offset);
	}


	/**
	 * getCampaignStats
	 * For a given Campaign ID get the send statistics for a particular job
	 * this jobid is associated with a particular Splittest send.
	 *
	 * @param Int $campaignId the Newsletter ID.
	 * @param Int $jobid the jobid for the Splittest statstics.
	 *
	 * @return Mixed Array of values from the appropriate record in the stats_newsletters table.
	 */
	private function getCampaignStats($campaignId, $jobid)
	{
		$query = 'SELECT ' . implode(',', $this->statsNewsletterFields) .
						', SUM(bouncecount_soft + bouncecount_hard + bouncecount_unknown) AS bouncecount_total,
						SUM(htmlrecipients + textrecipients + multipartrecipients) AS recipients
				FROM [|PREFIX|]stats_newsletters
				WHERE newsletterid = ' . intval($campaignId) . '
					AND jobid = ' . intval($jobid) . '
				GROUP BY ' . implode(',', $this->statsNewsletterFields);

		$rs = $this->Db->Query($query);

		require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/functions/api/stats.php');
		$stats_api = new Stats_API();

		$row = array();
		while ($row = $this->Db->Fetch($rs)) {
			$row['linkclicks_unique'] = $stats_api->GetUniqueClickers($row['statid']);
			$row['recipients'] > 0 ? $recipients = $row['recipients'] : $recipients = 0;
			$row['percent_emailopens_unique'] = $this->_doPercentage($row['emailopens_unique'], $recipients);
			$row['percent_linkclicks_unique'] = $this->_doPercentage($row['linkclicks_unique'], $recipients);
			$row['percent_bouncecount_total'] = $this->_doPercentage($row['bouncecount_total'], $recipients);
			$row['percent_unsubscribecount'] = $this->_doPercentage($row['unsubscribecount'], $recipients);
			$row['campaign_name'] = $this->getNewsletterNameFromStatid($row['statid']);
			$this->finishTime = $row['finishtime'];
			break;
		}
		$this->Db->FreeResult($rs);
		return $row;
	}

	/**
	 * getWinnerMessage
	 * Return a string describing the win state, i.e. the winner campaign name, type and score.
	 *
	 * @return String Message.
	 */
	private function getWinnerMessage()
	{
		$winnerType = $this->campaignWinner['winnerType'];
		$winnerName = $this->campaignWinner['winnerName'];
		$winnerId = $this->campaignWinner['winnerId'];
		$winnerOpenPercent = $this->newsletterStats[$winnerId]['stats_newsletters']['percent_emailopens_unique'];
		$winnerLinkPercent = $this->newsletterStats[$winnerId]['stats_newsletters']['percent_linkclicks_unique'];

		$message = sprintf(GetLang('Addon_splittest_WonMessage'), $winnerName) . ', ';
		if ($winnerType == 'Open') {
			$message .= sprintf(GetLang('Addon_splittest_OpenRate'), $winnerOpenPercent);
		} else {
			$message .= sprintf(GetLang('Addon_splittest_ClickRate'), $winnerLinkPercent);
		}
		return $message;
	}


	/**
	 * getRecpientsAtCalculation
	 * try and work how many recipients were sent to when the Campaign paused to work out who the winner is
	 *
	 */
	private function getRecpientsAtCalculation()
	{
		// If the campaign is Evenly Distributed then recipientCount = totalRecipients / $this->listCount
		// If the campaign is Percentage Based then the recipientCount = ((totalRecipient / $this->splittestPercentage) * 100) / $this->listCount
		if ($this->splitType == 'percentage') {
			$recipientCount = ($this->splittestPercentage / $this->sendSize ) * 100;
			$recipientCount = floor($recipientCount / $this->newsletterCount);
		} else {
			$recipientCount = $this->sendSize / $this->newsletterCount;
		}
		$this->recpientsAtCalculation = $recipientCount;
	}


	/**
	 * finalPercentageStats
	 * Work out the statistics given the ratio of the final recipient count.
	 *
	 * @param Array $campaignStats statistics derived from the _stats_newsletters table
	 * @return Mixed appended final percentage values to the statistics array
	 */
	private function finalPercentageStats()
	{
		while (list($id, $statistics) = each($this->campaignStats)) {
			for ($i=0; $i<count($this->statsNewsletterFields); $i++) {
				$statistics['stats_newsletters']['final_percent_' . $this->statsNewsletterFields[$i]] = $this->_doPercentage($statistics['stats_newsletters'][$this->statsNewsletterFields[$i]], $this->sendSize);
			}

			$statistics['stats_newsletters']['final_percent_bouncecount_total'] = $this->_doPercentage($statistics['stats_newsletters']['bouncecount_total'], $this->sendSize);
			$statistics['stats_newsletters']['final_percent_linkclicks_unique'] = $this->_doPercentage($statistics['stats_newsletters']['linkclicks_unique'], $this->sendSize);
			$statistics['stats_newsletters']['final_total_recipient_count'] = $this->sendSize;
			$this->campaignStats[$id] = $statistics;
		}
		return $this->campaignStats;
	}


	/**
	 * getCampaignWinners
	 * Get Statistics data about the best performing campaign.
	 *
	 * @param Array $weighting The weighting that opens and clicks have, to help decide the winner type.
	 * @param Array $compareCampaignStats stats_newsletter data about each campaing used in the send.
	 *
	 * @return Mixed statistical information about the best performing campaign.
	 *
	 */
	private function getCampaignWinners($weighting, $compareCampaignStats)
	{
		$stats = $compareCampaignStats['campaignStatsWinners'];
		// FIXME: should these be based off unique opens/clicks?
		// Get the names ofthe best performing campaigns
		$openrateName = $stats['emailopens']['name'];
		$clickrateName = $stats['linkclicks']['name'];

		// Get the raw and weighted values of the best performing campaigns
		$openrateValue = $stats['emailopens']['value'];
		$weightedOpenRate = $openrateValue * $weighting['openrate'] / 100;
		$clickrateValue = $stats['linkclicks']['value'];
		$weightedclickRate = $clickrateValue * $weighting['clickrate'] / 100;

		// this is the best performing open rate campaign
		$openrateCampaignId = $stats['emailopens']['id'];

		// this is the best performing click rate campaign
		$clickrateCampaignId = $stats['linkclicks']['id'];

		// the winner score was derived by adding the highest open rate + click rates scores together
		$winnerId = $stats['weighted']['id'];
		$winnerName = $stats['weighted']['name'];
		$winnerScore = $stats['weighted']['value'];
		$winnerPercentScore = $winnerScore;

		if ($weightedOpenRate > $weightedclickRate) {
			$winnerType = 'Open';
			$winnerRawValue = $openrateValue;
		} else {
			$winnerType = 'Click';
			$winnerRawValue = $clickrateValue;
		}

		if ($openrateValue == 0 && $clickrateValue == 0) {
			$winnerType = 'None';
		}

		$results = array(
			'winnerId' => $winnerId,
			'winnerType' => $winnerType,
			'winnerName' => $winnerName,
			'winnerCount' => $winnerRawValue,
			'winnerPercentScore' => $winnerPercentScore,
			'winnerRecipientCount' => $this->campaignRecipientCountCache[$winnerId],
			'winnerOpenName' => $openrateName,
			'winnerClickName' => $clickrateName,
			'winnerOpenAbsoluteValue' => $openrateValue,
			'winnerClickAbsoluteValue' => $clickrateValue
			);
		return $results;
	}


	/**
	 * barChartDataURL
	 * Produces a string suitable for passing to the InsertChart function for a custom_bar Chart type.
	 *   Eg ; xLabels=Opens,Clicks,Bounces,Unsubscribes&data=John:8.9,11,10.1Paul:33.3,29.2,21,34,5George:58.3,64.3.64,0,Ringo:41.7,40.1,0,2
	 *
	 * @param Array $stats Statistics fo a campaign send derived from newsletter_stats table.
	 * @param String $statType The type of statistics to report ie emailopens, bouncecount.
	 * @param Boolean $rawValue Report raw values.
	 * @param Booleam $pcValue Report percentage of recipients value.
	 * @param Boolean $final If true then we want to get the final percentage value which is the percentage value of the total split test send size rather than the individual campaign size.
	 *
	 * @return String $urlStr A string suitable for sending to the am charts class.
	 */
	public function barChartDataURL($stats, $statType, $rawValue, $pcValue, $final=false)
	{
		// if rawValue and pcValue are false nothing is looked up, therefore let's not continue
		if ($rawValue == false && $pcValue == false ) {
			return false;
		}

		// a list of labels for the chart X Axis
		$xLabels = array();

		// a list of traces and their respective data values
		$members = array();

		// loop through an array of campaigns and their associated 'stat_newsletters' data
		while (list($id, $details) = each($stats)) {
			$valueStr = urlencode($details['campaign_name']) . ':';
			$values = array();

			// Use our manually-determined unique link clicks instead of the raw value.
			if ($statType == 'linkclicks') {
				$statType = 'linkclicks_unique';
			}

			if ($rawValue === true) {
				if (!in_array($this->statsColumnNameLabels[$statType], $xLabels)) {
					$xLabels[] = $this->statsColumnNameLabels[$statType];
				}
				$values[] = $details['stats_newsletters'][$statType];
			}
			if ($pcValue === true) {
				$pcLabel = $statType . '_percent';
				if (!in_array($this->statsColumnNameLabels[$pcLabel], $xLabels)) {
					$xLabels[] = $this->statsColumnNameLabels[$pcLabel];
				}
				$values[] = $details['stats_newsletters']['percent_' . $statType];
			}
			$valueStr .= implode(':', $values);
			$members[] = $valueStr;
		}
		foreach($xLabels as &$label){
			$label=urlencode($label);
		}
		$urlStr = 'xLabels=' . implode(',', $xLabels) . '&data=';
		$urlStr .= implode(',', $members);
		return $urlStr;
	}


	/**
	 * barChartDataURL
	 * Produces a string suitable for a custom bar chart.
	 * xLabels=Opens,Clicks,Bounces,Unsubscribes&data=John:8.9,11,10.1Paul:33.3,29.2,21,34,5George:58.3,64.3.64,0,Ringo:41.7,40.1,0,2
	 *
	 * @param Array $stats The stats_newsletter data for the campaigns used in a Split Test send.
	 * @param String $xLabels Labels to be used on the X Axis of the chart.
	 *
	 * @return String $urlStr A string of campaign name and statistics values for each campaign.
	 */
	public function barChartSummaryDataURL($stats, $xLabels)
	{
		$urlStr = 'xLabels=' . $xLabels . '&data=';
		while (list($id, $details) = each($stats) ) {
			$urlStr .= urlencode($details['campaign_name']) . ':';
			$urlStr .= $details['stats_newsletters']['emailopens_unique'] . ':';
			$urlStr .= $details['stats_newsletters']['linkclicks_unique'] . ':';
			$urlStr .= $details['stats_newsletters']['bouncecount_total'] . ':';
			$urlStr .= $details['stats_newsletters']['unsubscribecount'] . ',' ;
		}
		return substr($urlStr, 0, -1);
	}


	/**
	 * getSplitDetails
	 * Process the splitdetails column into some sensible values (openrate, clickrate, percentage, etc).
	 *
	 * @param String $splitdetails A serialized string of options, e.g. 'a:3:{s:10:"percentage";i:80;s:10:"hoursafter";d:2...'
	 *
	 * @return Array $details an array of configurations for the given Split Test.
	 */
	private function getSplitDetails($splitdetails)
	{
		$data = unserialize($splitdetails);
		if (!count($data)) {
			return array();
		}
		$details = array(
			'openrate' => $data['weights']['openrate'],
			'clickrate' => $data['weights']['linkclick'],
			);
		if (isset($data['percentage'])) {
			$details['percentage'] = $data['percentage'];
		}
		return $details;
	}


	/**
	 * compareCampaignStats
	 * Compare an array of statistics for a series of Campaign sends
	 * to determine the best performing 'winner' for a Splittest send
	 * and rank the individual components of a send (Opens, Clicks, Bounce etc) in order of values.
	 *
	 * @param Array $stats an array of data for a given newsletter send (i.e. from the stats_newsletters table)
	 *
	 * @return Mixed array of campaign stats results,
	 *					ranked by highest values
	 *					and ranked by weighted performance as determined by the Split Test configuration
	 *
	 */
	private function compareCampaignStats($stats)
	{
		$linkclicksPercentHash = array();
		$emailopensPercentHash = array();
		$bouncecountPercentHash = array();
		$unsubscribesPercentHash = array();

		$campaignStatsWinners = array();
		$weightedHash = array();
		$weightedAbsolutePercentageHash = array();

		while (list($id, $data) = each($stats)) {
			$stats_newsletters = $data['stats_newsletters'];

			// these will be the sort fields used when listing the newsletters on each stats pane
			$hash_linkclicks[$id] = $stats_newsletters['linkclicks_unique'];
			$hash_emailopens[$id] = $stats_newsletters['emailopens'];
			$hash_bouncecount[$id] = $stats_newsletters['bouncecount_total'];
			$hash_unsubscribes[$id] = $stats_newsletters['unsubscribecount'];

			$recipientCount = $stats_newsletters['recipients'];
			$openrateWeight = $this->splitDetails['openrate'] / 100;
			$clickrateWeight =  $this->splitDetails['clickrate'] / 100;

			// Use a 'weighted' multiplier to determine a 'handicap' based result
			$linkWeighted = $openWeighted = 0;
			if ($recipientCount > 0) {
				$linkWeighted = (($stats_newsletters['linkclicks_unique'] / $recipientCount) * 100) * $clickrateWeight;
				$openWeighted = (($stats_newsletters['emailopens_unique'] / $recipientCount) * 100) * $openrateWeight;
			}

			$weightedResult = $linkWeighted + $openWeighted;

			$hash_weighted[$id] = round($weightedResult, 1);
			$weightedAbsolutePercentageHash[$id] = array(
				'linkclicks_unique' => $stats_newsletters['linkclicks_unique'],
				'emailopens_unique' => $stats_newsletters['emailopens_unique']
			);

		}
		// The following adds the best performing for the various stat types
		list($id, $value) = $this->processWinnerRank($this->rankHash($hash_weighted));
		$campaignStatsWinners['weighted'] = array(
								'name' => $this->campaignIdNameCache[$id],
								'value' => $value,
								'id' => $id
		);

		list($id, $value) = $this->processWinnerRank($this->rankHash($hash_linkclicks));
		$campaignStatsWinners['linkclicks'] = array(
								'name' => $this->campaignIdNameCache[$id],
								'value' => $value,
								'id' => $id
		);

		list($id, $value) = $this->processWinnerRank($this->rankHash($hash_emailopens));
		$campaignStatsWinners['emailopens'] = array(
								'name' => $this->campaignIdNameCache[$id],
								'value' => $value,
								'id' => $id
		);

		list($id, $value) = $this->processWinnerRank($this->rankHash($hash_bouncecount));
		$campaignStatsWinners['bouncecount_total'] = array(
								'name' => $this->campaignIdNameCache[$id],
								'value' => $value,
								'id' => $id
		);

		list($id, $value) = $this->processWinnerRank($this->rankHash($hash_unsubscribes));
		$campaignStatsWinners['unsubscribes'] = array(
								'name' => $this->campaignIdNameCache[$id],
								'value' => $value,
								'id' => $id
		);

		$results['campaignStatsWinners'] = $campaignStatsWinners;
		$results['rankings'] = array(
			'linkclicks_description' => 'percentage of newsletter recipients who click',
			'linkclicks' => $this->rankHash($hash_linkclicks),
			'emailopens_description' => 'percentage of newsetter recipients who unique opens',
			'emailopens' => $this->rankHash($hash_emailopens),
			'bouncecount_total_description' => 'percentage of newletters recipients who bounce',
			'bouncecount_total' => $this->rankHash($hash_bouncecount),
			'unsubscribes_description' => 'percentage of newsletter recipients who unsubscribed',
			'unsubscribes' => $this->rankHash($hash_unsubscribes),
			'weighted_description' => 'weighted score based on split test percentages',
			'weighted' => $this->rankHash($hash_weighted),
			'weighted_absolute' => $weightedAbsolutePercentageHash
		);
		return $results;
	}


	/**
	 * processWinnerRank
	 * Returns the winning ID and total of a newsletter from a group of newsletter results.
	 *
	 * @param Array $candidates A list of newsletters in the form array(array(id => total)).
	 *
	 * @return Mixed A key=>value pair containing the newsletter ID with the highest total in the form array(id => total).
	 */
	private function processWinnerRank($candidates)
	{
		$max_id = 0;
		$max_total = -1;
		foreach ($candidates as $candidate) {
			$id = key($candidate);
			if ($candidate[$id] > $max_total) {
				$max_id = $id;
				$max_total = $candidate[$id];
			}
		}
		return array($max_id, $max_total);
	}


	/**
	 * _doPercentage
	 * Returns the $value as a percentage of $divisor.
	 * For example, _doPercentage(5, 10) = 50.0.
	 *
	 * @param Integer $value The number of items to get a percentage for (a subset of the total).
	 * @param Integer $divisor The total number of items in the set.
	 * @param Integer $round Number of decimal places to permit for rounding purposes.
	 *
	 * @return Float A percentage value.
	 */
	private function _doPercentage($value, $divisor, $round=1)
	{
		$percentage = 0;
		if ($value > 0 && $divisor != 0) {
			$percentage = ($value / $divisor) * 100;
			$percentage = round($percentage, $round);
		}
		return $percentage;
	}


	/**
	 * getCampaignIDs
	 * For a given Splittest job return a list ot its Campaigns IDs.
	 *
	 * @param Int $job_id The ID of the job.
	 *
	 * @return Array returns An array of Campaign Ids set for the given job.
	 *
	 */
	private function getCampaignIDs($job_id)
	{
            $query = "SELECT sc.campaignid AS id FROM [|PREFIX|]splittest_statistics s, [|PREFIX|]splittest_campaigns sc WHERE s.jobid = " . intval($job_id) . " AND sc.splitid = s.splitid";
            $result = $this->Db->Query($query);
            $details = array();
            while ($row = $this->Db->Fetch($result)) {
			$details[] = $row['id'];
            }
            $campaign_ids = $details;
            return $campaign_ids;
	}


	/**
	 * splittestSendStats
	 * Get some sending Statistics for a particular Splittest.
	 * For each splittest send there will multiple matching jobids (for each campaign sent)
	 * which we will match with a record in the _splittest_statistics table.
	 *
	 * @param Int $split_statid the statistics ID for a given Splittest send.
	 * @param Int $jobid the jobid associated with a particular Splittest send.
	 * @param Array $stats_newsletters_fields Array of fields to query for in the stats_newsletters table.
	 *
	 * @return Mixed array of overall statistcs for a given Splittest send
	 */
	private function splittestSendStats($split_statid, $jobid, $stats_newsletters_fields=array())
	{
		$query = 'SELECT SUM(news.bouncecount_soft + news.bouncecount_hard + news.bouncecount_unknown) AS bouncecount';
		for ($i = 0; $i < count($stats_newsletters_fields); $i++) {
			$query .= ', SUM(news.' . $stats_newsletters_fields[$i] . ') AS ' . $stats_newsletters_fields[$i];
			if ($i < count($stats_newsletters_fields)-1) {
				$query .= ', ';
			}
		}
		$query .= ' FROM [|PREFIX|]splittests s, [|PREFIX|]splittest_statistics ss, [|PREFIX|]splittest_statistics_newsletters ssn, [|PREFIX|]stats_newsletters news
						WHERE ss.split_statid = ' . intval($split_statid) . '
						AND ss.jobid = ' . intval($jobid) . '
						AND ss.splitid = s.splitid
						AND ssn.split_statid = ss.split_statid
						AND ssn.newsletter_statid =  news.statid
						AND news.sendtype = \'splittest\'';
		$result = $this->Db->Query($query);
		$row = $this->Db->Fetch($result);
		$row['lists'] = $this->getCampaignLists($split_statid, $jobid);
		$this->Db->FreeResult($result);
		return $row;
	}


	/**
	 * getCampaignLists
	 * For a given campaign stat fetch the list id and names associated with that send.
	 *
	 * @param Int $statid The campaign stat id.
	 * @param Int $jobid The campaign job id.
	 *
	 * @return Array A list of List Ids and names belonging to that campaign send.
	 */
	private function getCampaignLists($statid, $jobid)
	{
		$lists = array();
		$query = 'SELECT DISTINCT l.listid, l.name
					FROM [|PREFIX|]lists l, [|PREFIX|]stats_newsletters sn, [|PREFIX|]stats_newsletter_lists snl
					WHERE jobid = ' . intval($jobid) . '
					AND sn.statid = snl.statid
					AND snl.listid = l.listid';
		$rs = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($rs)) {
			$lists[] = $row;
		}
		$this->Db->FreeResult($rs);
		return $lists;
	}


	/**
	 * getNewsletterNameFromStatid
	 * For a given jobid return the Newsletter name for this job.
	 * This is a convenience function so we don't have to load the Newsletter API.
	 *
	 * @param Integer $statid A newsletter send statid.
	 *
	 * @return String $newsletter Name.
	 */
	private function getNewsletterNameFromStatid($statid)
	{
		$query = 'SELECT n.name
					FROM [|PREFIX|]newsletters n, [|PREFIX|]stats_newsletters sn
				   WHERE sn.statid = ' . intval($statid) . ' AND sn.newsletterid = n.newsletterid';
		return $this->Db->FetchOne($query);
	}


	/**
	 * rankHash
	 * Sort a hash into an order array based on hash values from highest to lowest.
	 *
	 * @param Array $hash An array of keys and corresponding values.
	 *
	 * @return Array $ranked An array of keys and corresponding values sorted by values highest to lowest.
	 */
	private function rankHash($hash)
	{
		$ranked = array();
		arsort($hash);
		while (list($key, $val) = each($hash)) {
			$ranked[] = array($key => $val);
		}
		return $ranked;
	}
}
