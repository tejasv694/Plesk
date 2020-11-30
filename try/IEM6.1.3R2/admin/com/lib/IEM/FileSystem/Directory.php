<?php

/**
 * @package    IEM_FileSystem
 * @subpackage IEM_FileSystem_Directory
 *
 * @author Trey Shugart
 */

/**
 * Class designed specifically for directory manipulation.
 */
class IEM_FileSystem_Directory
{
	private
		/**
		 * @var string The name of the directory currently being manipulated.
		 */
		$_dir = '';



	/**
	 * Accepts a directory as the first parameter. If the directory doesn't exist and $mkdirIfNotExists
	 * is set to true, the directory is created with $mkdirPermissions.
	 *
	 * @return object
	 *
	 * @param string $dir              The directory you want to manipulate or create.
	 * @param bool   $mkdirIfNotExists Whether to create the directory if it doesn't exist, or not.
	 * @param int    $mkdirPermissions The octal permissions to set the file permissions to.
	 */
	public function __construct($dir, $mkdirIfNotExists = false, $mkdirPermissions = 0755)
	{


		$dir = realpath($dir);

		if ($mkdirIfNotExists && !is_dir($dir)) {
			$dirParts = explode(DIRECTORY_SEPARATOR, $dir);
			$dirStr   = '';

			foreach ($dirParts as $dirPart) {
				$dirStr .= DIRECTORY_SEPARATOR . $dirPart;

				if (!is_dir($dirStr)) {
					mkdir($dirStr, $mkdirPermissions);
				}
			}
		}

		$this->_dir = $dir;
	}

	/**
	 * Returns the pathname of the current directory.
	 *
	 * @return string The pathname of the current directory.
	 */
	public function __toString()
	{
		return $this->_dir;
	}



	/**
	 * Returns all of the directories under the current directory as an array. If no
	 * directories exist, then an empty array is returned.
	 *
	 * @return array
	 */
	public function getDirectories()
	{
		$dirArr = array();

		foreach (new DirectoryIterator($this->_dir) as $file) {
			if ($file->isDot()) {
				continue;
			}

			if ($file->isDir()) {
				$dirArr[] = new IEM_FileSystem_Directory($file->getPathname());
			}
		}

		return $dirArr;
	}

	/**
	 * Returns the all of the files under the current directory as an array. If no files
	 * exist, then an empty array is returned.
	 *
	 * @return array
	 */
	public function getFiles()
	{
		$fileArr = array();

		foreach (new DirectoryIterator($this->_dir) as $file) {
			if ($file->isDot()) {
				continue;
			}

			if ($file->isFile()) {
				$fileArr[] = new IEM_FileSystem_File($file->getPathname());
			}
		}

		return $fileArr;
	}

	/**
	 * Returns all of the files and the directories under the current directory as an array.
	 * If no files or directories exist, an empty array is returned.
	 *
	 * @return array
	 */
	public function getDirectoriesAndFiles()
	{
		return array_merge($this->getDirectories(), $this->getFiles());
	}

	/**
	 * Returns whether the current directory is empty or not. Takes into account both directories
	 * and files.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return count($this->getFilesAndDirectories()) === 0;
	}

	/**
	 * Deletes the current directory. If $removeAll is set to true, then the directory is emptied
	 * before being removed. After the directory is deleted, the current directory object is unset.
	 *
	 * @return void
	 */
	public function delete($removeAll = true)
	{
		if ($removeAll) {
			foreach ($this->getDirectoriesAndFiles() as $file) {
				$file->delete();
			}
		}

		rmdir($this->_dir);

		unset($this);
	}
}