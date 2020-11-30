<?php
/**
 * This file contains a database "factory" static object and it's supporting exception object
 *
 * @package interspire.iem.lib.iem
 */

/**
 * Require Db base object
 */
require_once IEM_PATH . '/ext/database/db.php';

/**
 * DataBase Factory static object
 * Factory object that will encapsulate database instantiation procedure.
 *
 * This is a static object that
 *
 * @package interspire.iem.lib.iem
 */
class IEM_DBFACTORY
{
	/**
	 * Cache of available implementation
	 * @var Array Cache of available implementation
	 */
	static private $_cacheLists = null;




	/**
	 * COSNTRUCTOR
	 */
	public function __construct()
	{
		die('This class cannot be instantiated.');
	}

	/**
	 * manufacture
	 * Manufacture a concrete implementation of the Db object
	 *
	 * @param String $type Database type
	 * @param String $host The host where the database server is located
	 * @param String $user Username to login to the database server
	 * @param String $password Password to authenticate the username
	 * @param String $name Database password
	 *
	 * @return Db Returns a concrete implementation of the Db object
	 *
	 * @throws exception_IEM_DBFACTORY
	 */
	static public function manufacture($type, $host = 'localhost', $user = null, $password = null, $name = null)
	{
		if (!self::exists($type)) {
			throw new exception_IEM_DBFACTORY('IEM_DBFACTORY::manufacture -- Implementation does not exists', exception_IEM_DBFACTORY::IMPLEMENTATION_DOES_NOT_EXISTS);
		}

		$class = "{$type}Db";
		require_once IEM_PATH . "/ext/database/{$type}.php";

		if (!class_exists($class, false)) {
			throw new exception_IEM_DBFACTORY('IEM_DBFACTORY::manufacture -- Invalid implementation', exception_IEM_DBFACTORY::INVALID_IMPLEMENTATION);
		}

		$db = new $class();
		
		//Should almost always be UTF-8
		// » List of character sets that MySQL supports
		//   http://dev.mysql.com/doc/refman/5.1/en/charset-charsets.html
 		$db->charset = 'utf8';
		
		$db->Connect($host, $user, $password, $name);

		return $db;
	}

	/**
	 * lists
	 * List all available Db implementations in the system
	 *
	 * @return Array Return an array of string of the available Db implementation
	 */
	static public function lists()
	{
		if (is_null(self::$_cacheLists)) {
			$dircontents = scandir(IEM_PATH . '/ext/database/');

			$lists = array();
			foreach ($dircontents as $each) {
				if (in_array($each, array('.', '..'. 'db.php'))) {
					continue;
				}

				list($filename, $ext) = explode('.', $each);
				array_push($lists, $filename);
			}

			self::$_cacheLists = $lists;
		}

		return self::$_cacheLists;
	}

	/**
	 * exists
	 * Check whether or not an implementation exists
	 *
	 * @param String $type Database type
	 * @return Boolean Returns TRUE if implementation exists, FALSE otherwise
	 */
	static public function exists($type)
	{
		$type = preg_replace('~[\W]~', '_', $type);
		return (is_readable(IEM_PATH . '/ext/database/' . $type . '.php'));
	}
}


/**
 * DBFactory exception class
 * @package interspire.iem.lib.iem
 */
class exception_IEM_DBFACTORY extends Exception
{
	const IMPLEMENTATION_DOES_NOT_EXISTS	= 1;
	const INVALID_IMPLEMENTATION			= 2;
}
