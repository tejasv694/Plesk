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
* add_user_credit_summary_table
*
* Adds user_credit_summary table
*
* @see Upgrade_API
*
* @package SendStudio
*/
class add_user_credit_summary_table extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the add_user_credit_summary_table upgrade
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		if ($this->TableExists('user_credit_summary')) {
			return true;
		}

		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$query = "
				CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "user_credit_summary (
				    usagesummaryid          BIGINT          UNSIGNED NOT NULL AUTO_INCREMENT,
				    userid                  INT             NOT NULL,
				    startperiod             INT             NOT NULL,
				    credit_used             INT             NOT NULL DEFAULT 0,
				    PRIMARY KEY (usagesummaryid),
				    FOREIGN KEY (userid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users (userid) ON DELETE CASCADE,
				    UNIQUE KEY (userid, startperiod)
				) CHARACTER SET UTF8 ENGINE=INNODB
			";
		} else {
			$query = "
				CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "user_credit_summary (
				    usagesummaryid          BIGSERIAL       NOT NULL,
				    userid                  INT             NOT NULL,
				    startperiod             INT             NOT NULL,
				    credit_used             INT             NOT NULL DEFAULT 0,
				    PRIMARY KEY (usagesummaryid),
				    FOREIGN KEY (userid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users (userid) ON DELETE CASCADE,
				    UNIQUE (userid, startperiod)
				)
			";
		}

		$result = $this->Db->Query($query);
		return $result;
	}
}
