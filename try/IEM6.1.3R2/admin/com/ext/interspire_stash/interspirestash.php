<?php
/**
 * This file contains code that will provide functionalities for storing common data
 *
 * Contains the following:
 * - InterspireStash interface
 * - InterspireStashException class
 *
 * @author Hendri <hendri@interspire.com>
 *
 * @package Library
 * @subpackage InterspireStash
 */

/**
 * InterspireStash interface
 *
 * This interface defines a way to shared common data between different requests.
 *
 * @package Library
 * @subpackage InterspireStash
 */
interface InterspireStash
{
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
	public function read($key);

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
	public function write($key, $data, $overwrite = false);

	/**
	 * exists
	 * Check whether or not data with specified key exists
	 *
	 * @param String $key Key associated with the data
	 *
	 * @throws InterspireStashException
	 *
	 * @return Boolean Returns TRUE if data exists, FALSE otherwise
	 *
	 * @uses InterspireStashException
	 * @uses InterspireStashException::CANNOT_READ_DATA
	 */
	public function exists($key);

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
	public function remove($key);
}



/**
 * InterspireStashException
 *
 * @package Library
 * @subpackage InterspireStash
 */
class InterspireStashException extends Exception
{
	const CANNOT_READ_DATA		= 1;
	const CANNOT_WRITE_DATA		= 2;
	const KEY_NOT_EXISTS		= 3;
	const KEY_EXISTS			= 4;
}
