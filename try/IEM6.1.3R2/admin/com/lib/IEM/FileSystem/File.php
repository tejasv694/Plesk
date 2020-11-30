<?php

/**
 * @package    IEM_FileSystem
 * @subpackage IEM_FileSystem_File
 *
 * @author Trey Shugart
 */

/**
 * Class designed specifically for file manipulation.
 */
class IEM_FileSystem_File
{
	private
		/**
		 * @var string The name of the file currently being manipulated.
		 */
		$_file = '';

	/**
	 * Takes a file path as the parameter and constructs a new file object to manipulate that file.
	 * If the file doesn't exist an error is triggered.
	 *
	 * @return object
	 *
	 * @param string $file The file you want to manipulate.
	 */
	public function __construct($file)
	{
		if (!is_file($file)) {
			trigger_error('<strong' . $file . '</strong> is not a file', E_USER_ERROR);
		}

		$this->_file = $file;
	}

	/**
	 * Returns the content of the current file. Employs self::getContents().
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->contents();
	}



	/**
	 * Gets the content of the current file.
	 *
	 * @return string
	 */
	public function getContents()
	{
		return file_get_contents($this->_file);
	}

	/**
	 * Sets the content of the current file.
	 *
	 * @return object The current file object.
	 */
	public function setContents($content)
	{
		file_put_contents($this->_file, $content);

		return $this;
	}

	/**
	 * Deletes the current file and destroys the object.
	 *
	 * @return void
	 */
	public function delete()
	{
		unlink($this->_file);

		unset($this);
	}
}