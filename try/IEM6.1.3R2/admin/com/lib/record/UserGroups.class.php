<?php
/**
 * This file contains record_UserGroups class definition
 *
 * @package interspire.iem.lib.record
 */

/**
 * UserGroups Record class definition
 *
 * This class will provide encapsulation to access record level information from a database.
 * It mainly provide an interface for developers to have their code cleaner.
 *
 * @property integer $groupid Group ID
 * @property string $groupname Group name
 * @property integer $createdate Create date (this is unix timestamp)
 *
 * @property integer $limit_list The number of contact list that the group is limited to (0 = unlimited)
 * @property integer $limit_hourlyemailsrate Hourly sending rate limit (0 = unlimited)
 * @property integer $limit_emailspermonth Group is able to send
 * @property integer $limit_totalemailslimit The total number of emails the users
 *                                           associated with this group can send.
 *
 * @package interspire.iem.lib.record
 *
 * @todo all
 */
class record_UserGroups extends IEM_baseRecord
{
	public function __construct($data = array())
	{
		$this->properties = array(
			'groupid'						=> null,
			'groupname'						=> '',
			'createdate'					=> null,

			'limit_list'					=> 0,
			'limit_hourlyemailsrate'		=> 0,
			'limit_emailspermonth'			=> 0,
			'limit_totalemailslimit'		=> 0,

			'forcedoubleoptin'				=> '0',
			'forcespamcheck'				=> '0',

			'systemadmin'					=> '0',
			'listadmin'						=> '0',
			'segmentadmin'					=> '0',
			'templateadmin'					=> '0',
		);

		parent::__construct($data);
	}
	
	/**
	 * Returns whether the current group is the only admin group.
	 * 
	 * @return bool
	 */
	public function isAdmin()
	{
		return $this->systemadmin == 1;
	}
	
	/**
	 * Returns whether the current user is the only administrator
	 * group.
	 * 
	 * @return bool
	 */
	public function isLastAdmin()
	{
		return 
			$this->isAdmin() && 
			self::hasOneAdmin();
	}
	
	/**
	 * Returns whether this is the last administrator with users.
	 * 
	 * @return bool
	 */
	public function isLastAdminWithUsers()
	{
		if (!$this->groupid) {
			return false;
		}
		
		if ($this->isLastAdmin()) {
			return true;
		}
		
		$db  = IEM::getDatabase();
		$res = $db->Query("
			SELECT
				COUNT(u.userid) AS count
			FROM
				[|PREFIX|]users      AS u,
				[|PREFIX|]usergroups AS ug
			WHERE
				ug.systemadmin  = 1                AND
				ug.groupid     != {$this->groupid} AND
				u.groupid       = ug.groupid
		");
		
		$users = $db->Fetch($res);
		
		return (int) $users['count'] == 0;
	}
	
	/**
	 * Returns the number of users in the current group.
	 * 
	 * @return int
	 */
	public function getUserCount()
	{
		$db    = IEM::getDatabase();
		$users = $db->Fetch($db->Query("
			SELECT
				COUNT(userid) AS count
			FROM
				[|PREFIX|]users
			WHERE
				groupid = {$this->groupid}
		"));
		
		return (int) $users['count'];
	}
	
	/**
	 * Returns the users that are in the current group.
	 * 
	 * @return array
	 */
	public function getUsers()
	{
		$db    = IEM::getDatabase();
		$users = $db->Fetch($db->Query("
			SELECT
				*
			FROM
				[|PREFIX|]users
			WHERE
				groupid = {$this->groupid}
		"));
		
		// instantiate user objects
		foreach ($users as &$user) {
			$user = new record_Users($user);
		}
		
		return (array) $users;
	}
	
	/**
	 * Checks to see if there is only one admin group.
	 * 
	 * @return bool
	 */
	static public function hasOneAdmin()
	{
		return self::getAdminCount() == 1;
	}
	
	/**
	 * Returns all admin user group records.
	 * 
	 * @return array
	 */
	static public function getAdmins()
	{
		$db     = IEM::getDatabase();
		$groups = $db->Fetch($db->Query("
			SELECT
				*
			FROM
				[|PREFIX|]usergroups
			WHERE
				systemadmin = 1
		"));
		
		// instantiate user objects
		foreach ($groups as &$group) {
			$group = new record_UserGroups($group);
		}
		
		return (array) $groups;
	}
	
	/**
	 * Returns the total number of admin groups.
	 * 
	 * @return int
	 */
	static public function getAdminCount()
	{
		$db  = IEM::getDatabase();
		$res = $db->Fetch($db->Query("
			SELECT
				COUNT(groupid) as count
			FROM
				[|PREFIX|]usergroups
			WHERE
				systemadmin = 1
		"));
		
		return (int) $res['count'];
	}
}
