<?php
/**
* This file is part of the upgrade process.
*
* @package SendStudio
*/

/**
* Do a sanity check to make sure the upgrade api has been included.
*/
if (!class_exists('Upgrade_API', false)) {
	exit();
}

/**
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class stats_newsletters_convert extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the query for the upgrade process
	* and returns the result from the query.
	* The calling function looks for a true or false result
	*
	* @return Mixed Returns true if the condition is already met (eg the column already exists).
	*  Returns false if the database query can't be run.
	*  Returns the resource from the query (which is then checked to be true).
	*/
	function RunUpgrade()
	{
		$tablePrefix = SENDSTUDIO_TABLEPREFIX;

		/**
		 * Splitting process into chunks and startup process
		 */
			$dbUpgradeStatus = IEM::sessionGet('DatabaseUpgradeStatusList');

			$thisQuery = null;
			if (isset($dbUpgradeStatus[get_class($this)])) {
				$thisQuery = $dbUpgradeStatus[get_class($this)];
			}

			if (is_null($thisQuery)) {
				$query = "DELETE FROM {$tablePrefix}sends WHERE DateEnded=0 OR DateStarted=0";
				$this->Db->Query($query);

				$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "sends WHERE DateStarted=DateEnded";
				$delete_result = $this->Db->Query($query);

				$result = $this->Db->Query("	SELECT	COUNT(s.SendID) AS listcount
												FROM 	{$tablePrefix}sends s,
														{$tablePrefix}lists l
												WHERE 	l.listid=s.listid");
				$row = $this->Db->Fetch($result);
				$this->Db->FreeResult($result);

				$thisQuery = array(
					'Total' 	=> $row['listcount'],
					'Processed' => 0,
					'Offset' 	=> 0,
					'Limit'		=> 5
				);
			}
		/* ----- */

		$query = "	SELECT	s.*,
							l.ownerid AS ownerid
					FROM 	{$tablePrefix}sends s,
							{$tablePrefix}lists l
					WHERE 	l.listid=s.listid
					LIMIT 	{$thisQuery['Limit']}
					OFFSET	{$thisQuery['Offset']}";

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$ownerid = $row['ownerid'];

			$statid = $this->Db->NextId(SENDSTUDIO_TABLEPREFIX . 'stats_sequence');

			$insert_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_newsletter_lists(statid, listid) VALUES ('" . $statid . "', '" . $row['ListID'] . "')";
			$insert_result = $this->Db->Query($insert_query);

			$sent_by = $row['SendFrom'];

			$send_from_name = $send_from_email = '';

			preg_match('%["](.*)["]\s+<(.*)>%', $sent_by, $matches);

			if (isset($matches[1]) && isset($matches[2])) {
				$send_from_name = $matches[1];
				$send_from_email = $matches[2];
			}

			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks(clicktime, clickip, subscriberid, statid, linkid) SELECT lc.timestamp, lc.ipaddress, lc.memberid, " . $statid . ", ln.linkid FROM " . SENDSTUDIO_TABLEPREFIX . "link_clicks lc, " . SENDSTUDIO_TABLEPREFIX . "links l, " . SENDSTUDIO_TABLEPREFIX . "links_new ln WHERE lc.linkid=l.linkid AND l.url=ln.url AND lc.ComposedID=l.ComposedID AND UPPER(lc.LinkType)='SEND' AND lc.ListID='" . $row['ListID'] . "'";

			$this->Db->Query($query);

			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens(subscriberid, statid, opentime, openip) SELECT MemberID, " . $statid . ", TimeStamp, NULL FROM " . SENDSTUDIO_TABLEPREFIX . "email_opens WHERE SendID='" . $row['SendID'] . "' AND UPPER(EmailType)='SEND'";

			$this->Db->Query($query);


			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_users(userid, statid, jobid, queuesize,queuetime) SELECT " . $ownerid . ", " . $statid . ", 0, EmailsSent, TimeStamp FROM sends_permonth WHERE SendID='" . $row['SendID'] . "'";

			$this->Db->Query($query);


			$send_query = "SELECT DateStarted, DateEnded, TotalRecipients FROM " . SENDSTUDIO_TABLEPREFIX . "sends WHERE SendID='" . $row['SendID'] . "'";

			$send_result = $this->Db->Query($send_query);

			while ($send_row = $this->Db->Fetch($send_result)) {

				// took X number of hours.
				$send_time = ceil(($send_row['DateEnded'] - $send_row['DateStarted']) / 3600);

				// if somehow a send to 0 hours, then skip it.
				if ($send_time == 0) {
					continue;
				}

				// average number of emails per hour.
				$avg_rate = ceil($send_row['TotalRecipients'] / $send_time);

				$start_date = getdate($send_row['DateStarted']);

				$start_hour = mktime($start_date['hours'], 0, 1, $start_date['mon'], $start_date['mday'], $start_date['year']);

				$sendtime = $start_hour;

				for ($h = 0; $h < $send_time; $h++) {
					$insert_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "user_stats_emailsperhour(statid, sendtime, emailssent, userid) VALUES ('" . $statid . "', '" . $start_hour . "', '" . $avg_rate . "', '" . $ownerid . "')";

					$insert_result = $this->Db->Query($insert_query);

					$start_hour += 3600;
				}
			}

			$link_clicks_query = "SELECT COUNT(linkid) AS linkcount FROM " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks WHERE statid='" . $statid . "'";
			$links_result = $this->Db->Query($link_clicks_query);
			$link_clicks = $this->Db->FetchOne($links_result, 'linkcount');

			$link_clicks_query = "SELECT COUNT(openid) AS opencount FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens WHERE statid='" . $statid . "'";
			$clicks_result = $this->Db->Query($link_clicks_query);
			$email_opens = $this->Db->FetchOne($clicks_result, 'opencount');

			$insert_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_newsletters(statid, queueid, starttime, finishtime, htmlrecipients, textrecipients, multipartrecipients, trackopens, tracklinks, bouncecount_soft, bouncecount_hard, bouncecount_unknown, unsubscribecount, newsletterid, sendfromname, sendfromemail, bounceemail, replytoemail, charset, sendinformation, sendsize, sentby, notifyowner, linkclicks, emailopens, emailforwards, emailopens_unique, hiddenby) VALUES ('" . $statid . "', '0', '" . $row['DateStarted'] . "', '" . $row['DateEnded'] . "', '" . $row['HTMLRecipients'] . "', '" . $row['TextRecipients'] . "', '0', '" . $row['TrackOpens'] . "', '" . $row['TrackLinks'] . "', 0, 0, 0, 0, '" . $row['ComposedID'] . "', '" . $send_from_name . "', '" . $send_from_email . "', '" . $row['ReturnPath'] . "', '" . $row['ReplyTo'] . "', '" . SENDSTUDIO_DEFAULTCHARSET . "', '" . $row['SearchCriteria'] . "', '" . $row['TotalRecipients'] . "', '" . $ownerid . "', '" . $row['NotifyOwner'] . "', '" . $link_clicks . "', '" . $email_opens . "', '0', '" . $email_opens . "', 0)";

			$insert_result = $this->Db->Query($insert_query);
		}

		/**
		 * Make sure the process run for the next chunk
		 */
			$thisQuery['Processed'] += $thisQuery['Limit'];
			if ($thisQuery['Processed'] > $thisQuery['Total']) {
				$thisQuery['Processed'] = $thisQuery['Total'];
			}
			$thisQuery['Offset'] = $thisQuery['Processed'] - 1;

			$dbUpgradeStatus[get_class($this)] = $thisQuery;
			IEM::sessionSet('DatabaseUpgradeStatusList', $dbUpgradeStatus);
		/* ----- */

		/**
		 * Will return 1 if need to process the same table, TRUE if processing complete, FALSE if process failed
		 * Will also process subsequent commands after finishing the main process
		 */
			if ($thisQuery['Processed'] >= $thisQuery['Total']) {
				return true;
			} else {
				return 1;
			}
		/* ----- */
	}
}
