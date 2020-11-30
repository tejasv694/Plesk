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
	exit;
}

/**
* create_list_subscriber_events
*
* Creates list_subscriber_events table
*
* @see Upgrade_API
*
* @package SendStudio
*/
class create_list_subscriber_events extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the list_subscriber_event upgrade
	*
	* @return Resource Returns query resource
	*/
	function RunUpgrade()
	{
		if ($this->TableExists('list_subscriber_events')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = 	'CREATE TABLE ' . SENDSTUDIO_TABLEPREFIX . 'list_subscriber_events (
									eventid int(11) NOT NULL auto_increment,
									subscriberid int(11) NOT NULL,
									listid int(11) NOT NULL,
									eventtype text NOT NULL,
									eventsubject text NOT NULL,
									eventdate int(11) NOT NULL,
									lastupdate int(11) NOT NULL,
									eventownerid int(11) NOT NULL,
									eventnotes text NOT NULL,
									PRIMARY KEY  (eventid),
									KEY subscriberid (subscriberid)
								) character set utf8 engine=innodb';

			$result = $this->Db->Query($query);
		} else {
			$query = 'CREATE SEQUENCE ' . SENDSTUDIO_TABLEPREFIX . 'list_subscriber_events_sequence';

			$result = $this->Db->Query($query);

			if ($result) {
				$query = 	'CREATE TABLE ' . SENDSTUDIO_TABLEPREFIX . 'list_subscriber_events ('.
							" eventid INT DEFAULT nextval('" . SENDSTUDIO_TABLEPREFIX . "list_subscriber_events_sequence') NOT NULL PRIMARY KEY,
									subscriberid INT NOT NULL,
									listid INT NOT NULL,
									eventtype TEXT NOT NULL,
									eventsubject TEXT NOT NULL,
									eventdate INT NOT NULL,
									lastupdate INT NOT NULL,
									eventownerid INT NOT NULL,
									eventnotes text NOT NULL
								)";

				$result = $this->Db->Query($query);
			}
		}

		return $result;
	}
}
?>
