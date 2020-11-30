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
* add_user_credit_table
*
* Adds user_credit table
*
* @see Upgrade_API
*
* @package SendStudio
*/
class add_user_credit_table extends Upgrade_API
{
	/**
	* RunUpgrade
	* Runs the add_user_credit_table upgrade
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function RunUpgrade()
	{
		if ($this->TableExists('user_credit')) {
			return true;
		}

		$queries = array();
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$queries[] = "
				CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "user_credit (
				    usercreditid            BIGINT          UNSIGNED NOT NULL AUTO_INCREMENT,
				    userid                  INT             NOT NULL,
				    transactiontype         VARCHAR(25)     NOT NULL,
				    transactiontime         INT             UNSIGNED NOT NULL,
				    credit                  BIGINT          NOT NULL,
				    jobid                   INT             DEFAULT NULL,
				    statid                  INT             DEFAULT NULL,
				    expiry                  INT             DEFAULT NULL,
				    PRIMARY KEY (usercreditid),
				    FOREIGN KEY (userid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users (userid) ON DELETE CASCADE,
				    INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_transactiontype_idx (transactiontype),
				    INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_userid_transactiontype_idx (userid, transactiontype),
				    INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_transactiontime_idx (transactiontime),
				    INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_userid_transactiontime_idx (userid, transactiontime),
				    INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_transactiontype_transactiontime_idx (transactiontype, transactiontime),
				    INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_userid_transactiontype_transactiontime_idx (userid, transactiontype, transactiontime)
				) CHARACTER SET UTF8 ENGINE=INNODB
			";
		} else {
			$queries[] = "
				CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "user_credit (
				    usercreditid            BIGSERIAL       NOT NULL,
				    userid                  INT             NOT NULL,
				    transactiontype         VARCHAR(25)     NOT NULL,
				    transactiontime         INT             NOT NULL,
				    credit                  BIGINT          NOT NULL,
				    jobid                   INT             DEFAULT NULL,
				    statid                  INT             DEFAULT NULL,
				    expiry                  INT             DEFAULT NULL,
				    PRIMARY KEY (usercreditid),
				    FOREIGN KEY (userid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users (userid) ON DELETE CASCADE
				)
			";

			$queries[] = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_transactiontype_idx ON " . SENDSTUDIO_TABLEPREFIX . "user_credit (transactiontype)";
			$queries[] = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_userid_transactiontype_idx ON " . SENDSTUDIO_TABLEPREFIX . "user_credit (userid, transactiontype)";
			$queries[] = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_transactiontime_idx ON " . SENDSTUDIO_TABLEPREFIX . "user_credit (transactiontime)";
			$queries[] = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_userid_transactiontime_idx ON " . SENDSTUDIO_TABLEPREFIX . "user_credit (userid, transactiontime)";
			$queries[] = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_transactiontype_transactiontime_idx ON " . SENDSTUDIO_TABLEPREFIX . "user_credit (transactiontype, transactiontime)";
			$queries[] = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "user_credit_userid_transactiontype_transactiontime_idx ON " . SENDSTUDIO_TABLEPREFIX . "user_credit (userid, transactiontype, transactiontime)";
		}

		foreach ($queries as $query) {
			$result = $this->Db->Query($query);
			if (!$result) {
				return false;
			}
		}

		return true;
	}
}
