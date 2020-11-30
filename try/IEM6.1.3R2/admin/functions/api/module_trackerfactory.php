<?php
/**
 * Module Tracker Factory API
 *
 * This file contains a factory class to manufacture Module Tracker
 *
 * @version $Id: module_trackerfactory.php,v 1.2 2008/01/21 04:57:35 hendri Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package API
 * @subpackage Module
 */

/**
 * Include common base
 */
require_once(dirname(__FILE__) . '/module_factory.php');

/**
 * Include "Tracker" module base
 */
require_once(SENDSTUDIO_MODULE_BASEDIRECTORY . '/tracker/module_tracker.php');

/**
 * Module Tracker Factory API
 *
 * This API will handle all related functions
 *
 * @package API
 * @subpackage Module
 */
class module_TrackerFactory_API extends module_Factory_API
{
	/**
	 * Get an instance of this class
	 * @return module_TrackerFactory_API Returns an instance of this class
	 * @static
	 */
	function &getInstance()
	{
		static $instance = null;
		if(is_null($instance)) {
			$instance = new module_TrackerFactory_API();
		}
		return $instance;
	}

	/**
	 * CONSTRUCTOR
	 */
	function module_TrackerFactory_API()
	{
		$this->VERSION = 1;
		$this->_init();
	}
}
?>