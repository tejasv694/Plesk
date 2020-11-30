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
class stats_autoresponders_create extends Upgrade_API
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
		$query = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "stats_autoresponders (
		  statid int(11) NOT NULL default '0',
		  htmlrecipients int(11) default '0',
		  textrecipients int(11) default '0',
		  multipartrecipients int(11) default '0',
		  bouncecount_soft int(11) default '0',
		  bouncecount_hard int(11) default '0',
		  bouncecount_unknown int(11) default '0',
		  unsubscribecount int(11) default '0',
		  autoresponderid int(11) default '0',
		  linkclicks int(11) default '0',
		  emailopens int(11) default '0',
		  emailforwards int(11) default '0',
		  emailopens_unique int(11) default '0',
		  htmlopens int default 0,
		  htmlopens_unique int default 0,
		  textopens int default 0,
		  textopens_unique int default 0,
		  hiddenby int default 0,
		  PRIMARY KEY(statid)
		)";
		$result = $this->Db->Query($query);

		return $result;
	}
}
