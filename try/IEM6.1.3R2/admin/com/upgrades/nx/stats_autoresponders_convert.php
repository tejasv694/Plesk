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
class stats_autoresponders_convert extends Upgrade_API
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

		// ----- Splitting process into chunks
			$dbUpgradeStatus = IEM::sessionGet('DatabaseUpgradeStatusList');
			$thisQuery = null;
			if (isset($dbUpgradeStatus[get_class($this)])) {
				$thisQuery = $dbUpgradeStatus[get_class($this)];
			}

			if (is_null($thisQuery)) {
				$result = $this->Db->Query("	SELECT	COUNT(autoresponderid) AS listcount
												FROM 	{$tablePrefix}autoresponders");
				$row = $this->Db->Fetch($result);
				$this->Db->FreeResult($result);

				$thisQuery = array(
					'Total' 	=> $row['listcount'],
					'Processed' => 0,
					'Offset' 	=> 0,
					'Limit'		=> 10
				);
			}
		// -----

		$query = "	SELECT	autoresponderid, listid
					FROM 	{$tablePrefix}autoresponders
					LIMIT	{$thisQuery['Limit']}
					OFFSET	{$thisQuery['Offset']}";

		$result = $this->Db->Query($query);
		while ($row = $this->Db->Fetch($result)) {
			$statid = $this->Db->NextId(SENDSTUDIO_TABLEPREFIX . 'stats_sequence');

			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks(clicktime, clickip, subscriberid, statid, linkid) SELECT lc.timestamp, lc.ipaddress, lc.memberid, " . $statid . ", ln.linkid FROM " . SENDSTUDIO_TABLEPREFIX . "link_clicks lc, " . SENDSTUDIO_TABLEPREFIX . "links l, " . SENDSTUDIO_TABLEPREFIX . "links_new ln WHERE lc.linkid=l.linkid AND l.url=ln.url AND lc.ComposedID=l.ComposedID AND UPPER(lc.LinkType)='AUTO' AND lc.ListID='" . $row['listid'] . "'";

			$this->Db->Query($query);

			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens(subscriberid, statid, opentime, openip) SELECT MemberID, " . $statid . ", TimeStamp, NULL FROM " . SENDSTUDIO_TABLEPREFIX . "email_opens WHERE SendID='" . $row['autoresponderid'] . "' AND UPPER(EmailType)='AUTO'";

			$this->Db->Query($query);


			$link_clicks_query = "SELECT COUNT(linkid) AS linkcount FROM " . SENDSTUDIO_TABLEPREFIX . "stats_links WHERE statid='" . $statid . "'";
			$clicks_result = $this->Db->Query($link_clicks_query);
			$link_clicks = $this->Db->FetchOne($clicks_result, 'linkcount');

			$link_clicks_query = "SELECT COUNT(openid) AS opencount FROM " . SENDSTUDIO_TABLEPREFIX . "stats_emailopens WHERE statid='" . $statid . "'";
			$opens_result = $this->Db->Query($link_clicks_query);
			$email_opens = $this->Db->FetchOne($opens_result, 'opencount');


			$insert_query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders(statid, htmlrecipients, textrecipients, multipartrecipients, bouncecount_soft, bouncecount_hard, bouncecount_unknown, unsubscribecount, autoresponderid, linkclicks, emailopens, emailforwards, emailopens_unique, hiddenby) VALUES ('" . $statid . "', '0', '0', '0', 0, 0, 0, 0, '" . $row['autoresponderid'] . "', '" . $link_clicks . "', '" . $email_opens . "', 0, '" . $email_opens . "', 0)";

			$insert_result = $this->Db->Query($insert_query);
		}

		// ----- Make sure the process run for the next chunk
			$thisQuery['Processed'] += $thisQuery['Limit'];
			if ($thisQuery['Processed'] > $thisQuery['Total']) {
				$thisQuery['Processed'] = $thisQuery['Total'];
			}
			$thisQuery['Offset'] = $thisQuery['Processed'] - 1;

			$dbUpgradeStatus[get_class($this)] = $thisQuery;
			IEM::sessionSet('DatabaseUpgradeStatusList', $dbUpgradeStatus);
		// -----

		// -----
		// Will return 1 if need to process the same table, TRUE if processing complete, FALSE if process failed
		// Will also process subsequent commands after finishing the main process
		// -----
			if ($thisQuery['Processed'] >= $thisQuery['Total']) {
				// save all of the stat -> link associations here.
				$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "stats_links SELECT statid, linkid FROM " . SENDSTUDIO_TABLEPREFIX . "stats_linkclicks GROUP BY statid, linkid";
				$this->Db->Query($query);
				return true;
			} else {
				return 1;
			}
		// -----
	}
}
