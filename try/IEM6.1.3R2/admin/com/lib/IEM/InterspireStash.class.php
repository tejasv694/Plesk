<?php
/**
 * IEM implementation of Interspire Stash
 *
 * The implementation will allow caching of commonly used variables to a file.
 *
 * @package @package interspire.iem.lib.iem
 */

/**
 * Include base directory
 */
require_once(IEM_PATH . '/ext/interspire_stash/interspirestash.php');


/**
 * IEM_InterspireStash class
 *
 * The IEM Interspire Stash class is designed to handle caching of commonly used variables
 * that require presistant storage.
 *
 * Limitation at this stage:
 * - It can only hold data of up to ~16.7 million bytes (ie. characters).
 * - Whenever the cache file (and it's backup) gets deleted, you loose all of your cache
 *
 * @package @package interspire.iem.lib.iem
 */
class IEM_InterspireStash implements InterspireStash
{
	/**
	 * The number of backup this class should make
	 */
	const BACKUP_COUNT = 2;




	/**
	 * Singleton instance of this class
	 * @var IEM_InterspireStash IEM_InterspireStash
	 */
	static private $_instance = null;




	/**
	 * Cached data
	 * @var Array Cached data
	 */
	private $_data = null;


	/**
	 * getInstance
	 * Get an instance of this class (Provide singleton access to this class)
	 * @return IEM_InterspireStash Returns current instance of this class
	 */
	static public function getInstance()
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new IEM_InterspireStash();
		}

		return self::$_instance;
	}

	/**
	 * CONSTRUCTOR
	 * Constructor for this class
	 *
	 * Attempt to load data from data file:
	 * - If data file does not exits, it will try to load it from backup data file
	 * - If data file exists, but cannot be read/write, it will throw an exception
	 *
	 * @throws InterspireStashException
	 *
	 * @return IEM_InterspireStash Returns an instance of this object
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::CANNOT_WRITE_DATA
	 * @uses InterspireStashException::CANNOT_READ_DATA
	 */
	private function __construct()
	{
		$fileName = $this->_getFileName();
		if (file_exists($fileName)) {
			if (!is_readable($fileName)) {
				throw new InterspireStashException('Data file cannot be read', InterspireStashException::CANNOT_READ_DATA);
			}

			if (!is_writable($fileName)) {
				throw new InterspireStashException('Data file cannot be written to', InterspireStashException::CANNOT_WRITE_DATA);
			}
		}

		if (!$this->_loadFromFile()) {
			$this->_data = array();
			$this->_writeToFile();
		}
	}

	/**
	 * read
	 * This method will get data for the given key value
	 *
	 * @param String $key Key associated with the data to be fetched
	 *
	 * @throws InterspireStashException
	 *
	 * @return Mixed Returns key value
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::CANNOT_READ_DATA
	 * @uses InterspireStashException::KEY_NOT_EXISTS
	 */
	public function read($key)
	{
		if (!$this->exists($key)) {
			throw new InterspireStashException('Key does not exists', InterspireStashException::KEY_NOT_EXISTS);
		}

		return $this->_data[$key];
	}

	/**
	 * write
	 * This method will store data for a given key value
	 *
	 * @param String $key Key to be associated with the data
	 * @param Mixed $value Data to be stored
	 * @param Boolean $overwrite Overwrite existing data (OPTIONAL, Default = FALSE)
	 *
	 * @throws InterspireStashException
	 *
	 * @return Void Returns nothing
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::CANNOT_WRITE_DATA
	 * @uses InterspireStashException::KEY_EXISTS
	 */
	public function write($key, $data, $overwrite = false)
	{
		if ((!$overwrite) && ($this->exists($key))) {
			throw new InterspireStashException('Key exists', InterspireStashException::KEY_EXISTS);
		}

		$this->_data[$key] = $data;
		$this->_writeToFile();
	}

	/**
	 * exists
	 * Check whether or not data with specified key exists
	 *
	 * @param String $key Key associated with the data
	 *
	 * @return Boolean Returns TRUE if data exists, FALSE otherwise
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::CANNOT_READ_DATA
	 */
	public function exists($key)
	{
		if (!is_array($this->_data)) {
			return false;
		}
		return array_key_exists($key, $this->_data);
	}


	/**
	 * remove
	 * Remove data associated with sepecified key
	 *
	 * @param String $key Key associated with the data to be removed
	 *
	 * @throws InterspireStashException
	 *
	 * @return Void Returns nothing
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::KEY_NOT_EXISTS
	 * @uses InterspireStashException::CANNOT_WRITE_DATA
	 */
	public function remove($key)
	{
		if (!$this->exists($key)) {
			throw new InterspireStashException('Key not found', InterspireStashException::KEY_NOT_EXISTS);
		}

		unset($this->_data[$key]);
		$this->_writeToFile();
	}

	/**
	 * _loadFromFile
	 * This function will load data from specified data file
	 * If file not found, attempt to load from backup file
	 *
	 * @throws InterspireStashException
	 * @return Boolean Returns TRUE if file is loaded, FALSE otherwise
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::CANNOT_WRITE_DATA
	 */
	private function _loadFromFile()
	{
		$loaded = false;

		for ($i = 0; $i < self::BACKUP_COUNT; ++$i) {
			$postfix = ($i != 0) ? '.backup_' . $i : '';
			$fileName = $this->_getFileName($postfix);
			if (!file_exists($fileName)) {
				continue;
			}

			$contents = file_get_contents($fileName);
			if ($contents === false) {
				continue;
			}

			$this->_data = unserialize(substr($contents, 8));
			$loaded = true;

			// ----- Copy back the backup data file to the default data file
				if ($i != 0) {
					$originalFile = $this->_getFileName();
					if (!copy($fileName, $originalFile)) {
						throw InterspireStashException('Cannot write to "Stash" data file', InterspireStashException::CANNOT_WRITE_DATA);
					}

					@chmod($originalFile, 0666);
				}
			// -----

			// Stash variable found and read to the memory
			break;
		}

		return $loaded;
	}

	/**
	 * _writeToFile
	 * This function will write data to specified file.
	 * It will also create backup of the existing data to backup files
	 *
	 * @throw InterspireStashException
	 *
	 * @return Void Does not return anything
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::CANNOT_WRITE_DATA
	 */
	private function _writeToFile()
	{
		$fileName = $this->_getFileName();
		if (!@file_put_contents($fileName, '<?php /*' . serialize($this->_data)) > 0) {
			throw new InterspireStashException('Cannot write to "Stash" data file', InterspireStashException::CANNOT_WRITE_DATA);
		}

		@chmod($fileName, 0666);

		/**
		 * Make a copy of data file
		 */
		for ($i = 1; $i <= self::BACKUP_COUNT; ++$i) {
			$backupFileName = $this->_getFileName('.backup_' . $i);
			copy($fileName, $backupFileName);
			@chmod($backupFileName, 0666);
		}
	}

	/**
	 * _getFileName
	 * This function will return stash file name
	 *
	 * @param String $postfix File postfix
	 * @return String Returns stash file name
	 */
	private function _getFileName($postfix = '')
	{
		return IEM_STORAGE_PATH . '/iem_stash_storage' . $postfix . '.php';
	}
}
