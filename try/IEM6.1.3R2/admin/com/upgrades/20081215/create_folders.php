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
* lists_add_agreedeleteall
*
* Creates the agreedeleteall column on the email_lists table.
*
* @see Upgrade_API
*
* @package SendStudio
*/
class create_folders extends Upgrade_API
{
	/**
	 * RunUpgrde
	 * Run current upgrade
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function RunUpgrade()
	{
		if ($this->TableExists('folders')) {
			return true;
		}
		$queries = array();
		if (SENDSTUDIO_DATABASE_TYPE == 'mysql') {
			$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "folders (
				folderid INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				name VARCHAR(255),
				type CHAR(1),
				createdate INT(11) DEFAULT 0,
				ownerid INT(11)
			) CHARACTER SET UTF8 ENGINE=INNODB";
			$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "folder_item (
				folderid INT(11) NOT NULL REFERENCES " . SENDSTUDIO_TABLEPREFIX . "folders(folderid) ON DELETE CASCADE,
				itemid INT(11) NOT NULL,
				PRIMARY KEY (folderid, itemid)
			) CHARACTER SET UTF8 ENGINE=INNODB";
			$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "folder_user (
				folderid INT(11) NOT NULL REFERENCES " . SENDSTUDIO_TABLEPREFIX . "folders(folderid) ON DELETE CASCADE,
				userid INT(11) NOT NULL REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users(userid) ON DELETE CASCADE,
				expanded CHAR(1) NOT NULL DEFAULT '1',
				ordering INT(11),
				PRIMARY KEY  (folderid, userid)
			) CHARACTER SET UTF8 ENGINE=INNODB";
		} else {
			$queries[] = "CREATE SEQUENCE " . SENDSTUDIO_TABLEPREFIX . "folders_sequence";
			$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "folders (
				folderid INT DEFAULT nextval('" . SENDSTUDIO_TABLEPREFIX . "folders_sequence') NOT NULL PRIMARY KEY,
				name VARCHAR(255),
				type CHAR(1),
				createdate INT DEFAULT 0,
				ownerid INT
			)";
			$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "folder_item (
				folderid INT NOT NULL REFERENCES " . SENDSTUDIO_TABLEPREFIX . "folders(folderid) ON DELETE CASCADE,
				itemid INT NOT NULL,
				PRIMARY KEY (folderid, itemid)
			)";
			$queries[] = "CREATE TABLE " . SENDSTUDIO_TABLEPREFIX . "folder_user (
				folderid INT NOT NULL REFERENCES " . SENDSTUDIO_TABLEPREFIX . "folders(folderid) ON DELETE CASCADE,
				userid INT NOT NULL REFERENCES " . SENDSTUDIO_TABLEPREFIX . "users(userid) ON DELETE CASCADE,
				expanded CHAR(1) NOT NULL DEFAULT '1',
				ordering INT,
				PRIMARY KEY  (folderid, userid)
			)";
		}

		$queries[] = "CREATE UNIQUE INDEX " . SENDSTUDIO_TABLEPREFIX . "folders_name_type_ownerid_idx ON " . SENDSTUDIO_TABLEPREFIX . "folders (name, type, ownerid);";
		$queries[] = "CREATE UNIQUE INDEX " . SENDSTUDIO_TABLEPREFIX . "folder_item_folderid_itemid_idx ON " . SENDSTUDIO_TABLEPREFIX . "folder_item (folderid, itemid)";
		$queries[] = "CREATE INDEX " . SENDSTUDIO_TABLEPREFIX . "folder_user_userid_folderid_idx ON " . SENDSTUDIO_TABLEPREFIX . "folder_user (userid, folderid)";

		foreach ($queries as $query) {
			$result = $this->Db->Query($query);
			if (!$result) {
				return false;
			}
		}
		return true;
	}
}
