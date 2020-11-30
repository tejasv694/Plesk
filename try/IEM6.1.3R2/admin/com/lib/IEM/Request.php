<?php

/**
 * @author Trey Shugart
 */

/**
 * @package    IEM
 * @subpackage Request
 */
class IEM_Request
{
	/**
	 * Retrieves a request parameter from either GET or POST. If the parameter doesn't exist, then
	 * $defaultValue is returned.
	 * 
	 * @param $name         The name of the request parameter to retrieve.
	 * @param $defaultValue The value to be returned if the parameter cannot be found.
	 */
	static public function getParam($name, $defaultValue = null)
	{
		if (isset($_GET[$name])) {
			return $_GET[$name];
		} elseif (isset($_POST[$name])) {
			return $_POST[$name];
		}
		
		return $defaultValue;
	}
}