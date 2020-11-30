<?php
/**
 * Base Module Admin class
 *
 * This file contains a class that define Admin module base class definition
 *
 * @version $Id: module_admin.php,v 1.1 2008/01/21 04:57:37 hendri Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package Module
 */

/**
 * Base Module Admin class
 *
 * @package Module
 *
 * @abstract
 */
class module_Admin
{
	/**
	 * Install module
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_ERROR Cannot instantiate this class
	 * @throws E_USER_ERROR Your database type is not in our implementation list
	 * @throws E_USER_NOTICE Cannot execute query
	 * @abstract
	 */
	function install()
	{
		trigger_error('Cannot instantiate this class', E_USER_ERROR);
	}

	/**
	 * Upgrade module
	 * @param mixed $version Old version to be upgraded from
	 * @return boolean Returns TRUE if successful, FALSE otherwise
	 * @throws E_USER_ERROR Cannot instantiate this class
	 * @throws E_USER_NOTICE Cannot execute query
	 * @abstract
	 */
	function upgrade($version)
	{
		trigger_error('Cannot instantiate this class', E_USER_ERROR);
	}
}
?>