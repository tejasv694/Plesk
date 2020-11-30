<?php

/**
 * @author     Trey Shugart
 * @package    IEM
 * @subpackage Support
 */

/**
 * Contains methods for feature detection so we can determine if a particular
 * feature, or even IEM as a whole will work on any given server configuration.
 */
class IEM_Support
{
	/**
	 * Returns whether or not the server has the GD Library installed.
	 * 
	 * @return bool
	 */
	public static function hasGdLibrary()
	{
		return function_exists('gd_info');
	}
	
	/**
	 * Returns whether or not the server has support for PDO.
	 * 
	 * @return bool
	 */
	public static function hasPdo()
	{
		return class_exists('PDO', false);
	}

	/**
	 * Returns whether the Zend Optimizer is installed on the server. The Zend
	 * Optimizer is known to conflict with the IonCube loader sometimes causing
	 * the application to fail.
	 *
	 * @return bool
	 */
	public static function hasZendOptimizer()
	{
		return function_exists('zend_optimizer_version');
	}
}