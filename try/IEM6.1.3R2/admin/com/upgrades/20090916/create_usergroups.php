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
 * create_usergroups table
 *
 * @see Upgrade_API
 *
 * @package SendStudio
 */
class create_usergroups extends Upgrade_API
{
	/**
	 * RunUpgrade
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('usergroups') && $this->TableExists('usergroups_permissions') && $this->TableExists('usergroups_access')) {
			return true;
		}

		$queries = array();

		if (!$this->TableExists('usergroups')) {
			if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
				$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "usergroups (
				    groupid                 INT             NOT NULL AUTO_INCREMENT,
				    groupname               VARCHAR(255)    NOT NULL,
				    createdate              INT             NOT NULL,

				    limit_list              INT             DEFAULT 0,
				    limit_hourlyemailsrate  INT             DEFAULT 0,
				    limit_emailspermonth    INT             DEFAULT 0,
				    limit_totalemailslimit  INT             DEFAULT NULL,

				    forcedoubleoptin        CHAR(1)         DEFAULT '0',
				    forcespamcheck          CHAR(1)         DEFAULT '0',

				    systemadmin             CHAR(1)         DEFAULT '0',
				    listadmin               CHAR(1)         DEFAULT '0',
				    segmentadmin            CHAR(1)         DEFAULT '0',
				    templateadmin           CHAR(1)         DEFAULT '0',

				    PRIMARY KEY (groupid)
				)";

			} else {
				$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "usergroups (
				    groupid                 SERIAL          NOT NULL,
				    groupname               VARCHAR(50)     NOT NULL,
				    createdate              INT             NOT NULL,

				    limit_list              INT             DEFAULT 0,
				    limit_hourlyemailsrate  INT             DEFAULT 0,
				    limit_emailspermonth    INT             DEFAULT 0,
				    limit_totalemailslimit  INT             DEFAULT NULL,

				    forcedoubleoptin        CHAR(1)         DEFAULT '0',
				    forcespamcheck          CHAR(1)         DEFAULT '0',

				    systemadmin             CHAR(1)         DEFAULT '0',
				    listadmin               CHAR(1)         DEFAULT '0',
				    segmentadmin            CHAR(1)         DEFAULT '0',
				    templateadmin           CHAR(1)         DEFAULT '0',

				    PRIMARY KEY (groupid)
				)";
			}
		}

		if (!$this->TableExists('usergroups_permissions')) {
			$temp = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "usergroups_permissions (
				    groupid         INT             NOT NULL,
				    area            VARCHAR(255)    NOT NULL,
				    subarea         VARCHAR(255)    DEFAULT NULL,
			";

			$temp .= " FOREIGN KEY (groupid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "usergroups(groupid)) ";

			$queries[] = $temp;
		}

		if (!$this->TableExists('usergroups_access')) {
			$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "usergroups_access (
			    groupid         INT             NOT NULL,
			    resourcetype    VARCHAR(100)    NOT NULL,
			    resourceid      INT             NOT NULL,
			    FOREIGN KEY (groupid) REFERENCES " . SENDSTUDIO_TABLEPREFIX . "usergroups(groupid)
			)";
		}


		foreach ($queries as $query) {
			if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
				$query .= " ENGINE=InnoDB DEFAULT CHARSET=utf8";
			}

			$result = $this->Db->Query($query);
			if ($result == false) {
				return false;
			}
		}

		return true;
	}
}
