<?php
/**
 * Module Tracker Admin
 *
 * This class contains an admin class that will install/upgrade "Tracker" module
 *
 * @version $Id: module_tracker_admin.php,v 1.1 2008/01/21 04:57:36 hendri Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_Admin
 */


/**
 * Require base class
 */
require_once(SENDSTUDIO_MODULE_BASEDIRECTORY . '/module_admin.php');

/**
 * Tracker Module Admin
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_Admin
 */
class module_Tracker_Admin extends module_Admin
{
	/**
	 * Install module
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_ERROR Your database type is not in our implementation list
	 * @throws E_USER_NOTICE Cannot execute query
	 */
	function install()
	{
		switch (SENDSTUDIO_DATABASE_TYPE) {
			case 'mysql':
				require(dirname(__FILE__) . '/_install/mysql.php');
			break;

			case 'pgsql':
				require(dirname(__FILE__) . '/_install/pgsql.php');
			break;

			default:
				trigger_error('Your database type is not in our implementation list', E_USER_ERROR);
				return false;
			break;
		}

		$db = IEM::getDatabase();

		foreach ($queries as $name => $query) {
			$status = $db->Query($query);
			if ($status == false) {
				trigger_error('module_Tracker_Admin::install -- Cannot execute query "' . $name . '" to install. Error returned: ' . $db->Error(), E_USER_NOTICE);
				return false;
			}
		}

		return true;
	}

	/**
	 * Upgrade module
	 * @param Mixed $version Old version to be upgraded from
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_NOTICE Cannot execute query
	 */
	function upgrade($version)
	{
		switch ($version) {
			default:
				// Nothing to be upgraded yet
			break;
		}

		return true;
	}
}
?>