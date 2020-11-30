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
* This class runs one change for the upgrade process.
* The Upgrade_API looks for a RunUpgrade method to call.
* That should return false for failure
* It should return true for success or if the change has already been made.
*
* @package SendStudio
*/
class create_segments extends Upgrade_API
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
		if ($this->TableExists('segments')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = 	'CREATE TABLE ' . SENDSTUDIO_TABLEPREFIX . 'segments ('.
						' segmentid INT AUTO_INCREMENT NOT NULL,'.
						' segmentname VARCHAR(255) NOT NULL,'.
						' createdate INT(11) DEFAULT 0,'.
						' ownerid INT(11) NOT NULL,'.
						' searchinfo TEXT NOT NULL,'.
						' PRIMARY KEY (segmentid)'.
						') character set utf8 engine=innodb';

			$result = $this->Db->Query($query);
		} else {
			$query = 'CREATE SEQUENCE ' . SENDSTUDIO_TABLEPREFIX . 'segments_sequence';

			$result = $this->Db->Query($query);

			if ($result) {
				$query = 	'CREATE TABLE ' . SENDSTUDIO_TABLEPREFIX . 'segments ('.
							" segmentid INT DEFAULT nextval('" . SENDSTUDIO_TABLEPREFIX . "segments_sequence') NOT NULL,".
							' segmentname VARCHAR(255) NOT NULL,'.
							' createdate INT DEFAULT 0,'.
							' ownerid INT NOT NULL,'.
							' searchinfo TEXT NOT NULL,'.
							' PRIMARY KEY (segmentid)'.
							')';

				$result = $this->Db->Query($query);
			}
		}

		return $result;
	}
}
?>
