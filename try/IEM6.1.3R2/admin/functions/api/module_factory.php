<?php
/**
 * Module Factory API
 *
 * This file contains a base "Module Factory" class
 *
 * @version $Id: module_factory.php,v 1.3 2008/01/21 04:57:35 hendri Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package API
 * @subpackage Module
 */

/**
 * Require API base class
 */
require_once(dirname(__FILE__) . '/api.php');

/**
 * Module Factory API abstract class
 *
 * This abstract class provide a base for all of other module factory classes.
 * This class should not be instantiated as it is only an ABSTRACT class.
 *
 * Each of the implementation of this class needs to implement the folllowing functions:
 * - getInstance
 * - CONSTRUCTOR
 *
 * You can override any other functions where applicable. But every "Module Factory"
 * should behave in accordance to their written "contracts" (ie. Receive the same input and
 * give the same output -- also perform similar task in nature).
 *
 * Each of the implementation must use the following convention:
 * - Class should be named as: module_[Modulename]Factory_API
 * - Where [Modulename] is the module name (Having uppercase in ther first character)
 * - File containing the class should be named as module_[modulename]factory.php
 * - Where [modulename] is the module name (All lower case)
 *
 * An example of an implementation can be found under module_trackerfactory.php
 * Which is an implementation to manufacture "Tracker"
 *
 * Implementation of a module must be placed in it's separate folders inside the module's
 * folder (ie. /module/MODULE_NAME/MODULE_IMPLEMENTATION). Implementation of each module's name
 * cannot start with under score characters (_).
 *
 *
 * @package API
 * @subpackage Module
 *
 * @abstract
 */
class module_Factory_API extends API
{
	/**
	 * Version numer for the module
	 * @var int
	 */
	var $VERSION = 0;




	/**
	 * Cached module name handled by this class
	 * @var string Module name
	 * @access private
	 */
	var $_cachedModuleName = null;

	/**
	 * Cached implemented modules handled by this class
	 * @var object[] Implemented module
	 * @access private
	 */
	var $_cachedImplementation = null;




	/**
	 * Get an instance of this class
	 * @return module_Factory_API Returns a reference of an instance of this class
	 * @throws E_USER_ERROR This class cannot be instantiated
	 * @abstract
	 * @static
	 */
	function &getInstance()
	{
		trigger_error('This class cannot be instantiated', E_USER_ERROR);
	}




	/**
	 * CONSTRUCTOR
	 *
	 * Each constructor must:
	 * - Emulate this constructor signature
	 * - Set the $VERSION
	 * - call $this->_init();
	 *
	 *
	 * @throws E_USER_ERROR This class cannot be instantiated
	 * @abstract
	 */
	function module_Factory_API()
	{
		trigger_error('This class cannot be instantiated', E_USER_ERROR);
	}




	/**
	 * List module implementation
	 * @return Mixed Returns a list of implementation if successful, FALSE otherwise
	 */
	function implementationList()
	{
		$fullPath = SENDSTUDIO_MODULE_BASEDIRECTORY . '/' . strtolower($this->_cachedModuleName) . '/';

		if (!is_dir($fullPath)) {
			trigger_error("module_Factory::implementationList -- Module directory '{$fullPath}', does not exist", E_USER_NOTICE);
			return false;
		}

		$dh = @opendir($fullPath);
		if ($dh === false) {
			trigger_error("module_Factory::implementationList -- Cannot open 'module' directory: '{$fullPath}'", E_USER_NOTICE);
			return false;
		}

		$ignore_entries = array('.', '..', 'cvs', '.svn');

		$entries = array();
		while (($entry = readdir($dh)) !== false) {
			if (in_array(strtolower($entry), $ignore_entries) || (substr($entry, 0, 1) == '_')) {
				continue;
			}

			if (is_dir($fullPath.'/'.$entry)) {
				array_push($entries, ucwords($entry));
			}
		}

		@closedir($dh);
		return $entries;
	}

	/**
	 * Check whether an implementation for handled module exists
	 * @param String $implementationName Implementation name for handled module
	 * @return Boolean Returns TRUE if exists, FALSE otherwise
	 */
	function implementationExists($implementationName)
	{
		if (substr($implementationName, 0, 1) == '_') {
			return false;
		}

		$mImplementationName = strtolower($implementationName);
		$mModuleName = strtolower($this->_cachedModuleName);
		return is_file(SENDSTUDIO_MODULE_BASEDIRECTORY . "/{$mModuleName}/{$mImplementationName}/module_{$mImplementationName}_{$mModuleName}.php");
	}

	/**
	 * Get manufactured implementation
	 * @param String $implementationName Implementation name
	 * @return Object Returns a refrence to implemented module object
	 */
	function &manufacture($implementationName)
	{
		$temp = false;

		if (substr($implementationName, 0, 1) == '_') {
			return $temp;
		}

		$mImplementationName = strtolower($implementationName);
		$mModuleName = strtolower($this->_cachedModuleName);
		$mClassName = 'module_' . ucwords($implementationName) . '_' . $this->_cachedModuleName;

		if (is_null($this->_cachedImplementation)) {
			$this->_cachedImplementation = array();
		}

		if (!array_key_exists($implementationName, $this->_cachedImplementation)) {
			if (!$this->implementationExists($implementationName)) {
				trigger_error('module_Factory::manufacture -- Implementation does not exists', E_USER_NOTICE);
				return $temp;
			}

			$mPathToModule = SENDSTUDIO_MODULE_BASEDIRECTORY . "/{$mModuleName}/{$mImplementationName}";

			require_once("{$mPathToModule}/module_{$mImplementationName}_{$mModuleName}.php");

			// Get base class for each module too (if exists)
			if (is_file("{$mPathToModule}/module_{$mImplementationName}.php")) {
				require_once("{$mPathToModule}/module_{$mImplementationName}.php");
			}

			$temp = new $mClassName();
		} elseif(!$this->_cachedImplementation[$implementationName]) {
			$temp = new $mClassName();
		} else {
			$temp =& $this->_cachedImplementation[$implementationName];
		}

		return $temp;
	}




	/**
	 * A protected method to initialize this class
	 * @param mixed[] $record Record to be loaded to the class (Optional, Default = array())
	 * @throws E_USER_ERROR Invalid module factory class -- Unknown module name
	 * @access protected
	 *
	 * @uses Db
	 * @uses Db::Query()
	 * @uses Db::Quote()
	 * @uses Db::Fetch()
	 */
	function _init()
	{
		// Get implementation class name (hence the module name)
		preg_match('/module_(.+)Factory_API/i', get_class($this), $temp);
		if (count($temp) == 2) {
			$this->_cachedModuleName = ucwords($temp[1]);
		} else {
			$this->_cachedModuleName = '';
		}

		if (empty($this->_cachedModuleName)) {
			trigger_error('module_Factory::_checkValid -- Invalid module factory class -- Unknown module name', E_USER_NOTICE);
		}

		/**
		 * Check whether or not module has been initialized (ie. installed)
		 * @todo check for error when updating to the database
		 */
			$operation = '';
			$oldVersion = 0;

			$db = IEM::getDatabase();
			$rs = $db->Query('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . "modules WHERE modulename='" . $db->Quote($this->_cachedModuleName) . "'");

			if ($rs === false || $db->CountResult($rs) == 0) {
				$operation = 'install';
			} else {
				$record = $db->Fetch($rs);
				$oldVersion = intval($record['moduleversion']);
				if ($oldVersion < intval($this->VERSION)) {
					$operation = 'upgrade';
				}
			}

			if ($operation != '') {
				$className = "module_{$this->_cachedModuleName}_Admin";
				$fileName = SENDSTUDIO_MODULE_BASEDIRECTORY . '/' . strtolower($this->_cachedModuleName) . '/' . strtolower($className) . '.php';

				if (!is_file($fileName)) {
					trigger_error('module_Factory::_init -- Cannot find administrative object for the module:' . $this->_cachedModuleName, E_USER_ERROR);
				}

				require_once($fileName);

				$adminOperation = new $className();

				if ($operation == 'install') {
					$status = $adminOperation->install();

					if ($status) {
						// Insert module record to the database
						$db->Query(	'INSERT INTO ' . SENDSTUDIO_TABLEPREFIX . 'modules'.
									' (modulename, moduleversion)'.
									" VALUES('".$db->Quote($this->_cachedModuleName)."'," . intval($this->VERSION) . ')');
					}
				} else {
					$status = $adminOperation->upgrade($oldVersion);

					if ($status) {
						// Update module record in the database
						$db->Query(	'UPDATE ' . SENDSTUDIO_TABLEPREFIX . 'modules'.
									' SET moduleversion = ' . intval($this->VERSION).
									" WHERE modulename = '" . $db->Quote($this->_cachedModuleName) . "'");
					}
				}
			}
		/**
		 * -----
		 */
	}
}
