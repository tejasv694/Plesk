<?php
/**
 * IEM_MinimumVersion
 *
 * Provides utility functions for checking minimum requirements for IEM.
 *
 * @package interspire.iem.lib.iem
 */
class IEM_MinimumVersion
{

	/**
	 * The minimum required versions of each application.
 	 * @var Array
	 */
	static private $_applications = array(
		'php'	=> '5.1.0',
		'mysql'	=> '4.1.1',
		'pgsql' => '8.1.0',
		);

	/**
	 * ForApp
	 * Retrieves the minimum version string required for a given application.
	 *
	 * @see _applications
	 *
	 * @param String $app The application to check the minimum version for.
	 *
	 * @return Mixed The minimum version string for the supplication application, or false is no such application exists.
	 */
	static public function ForApp($app)
	{
		if (isset(self::$_applications[$app])) {
			return self::$_applications[$app];
		}
		return false;
	}

	/**
	 * Sufficient
	 * Checks whether the version of a particular application meets the minimum requirements.
	 *
	 * @param String $app The application name, e.g. 'php'.
	 * @param String $version The current application version, e.g. '5.2.6'.
	 *
	 * @return Boolean True if the $version is >= the minimum required for $app.
	 */
	static public function Sufficient($app, $version)
	{
		if (isset(self::$_applications[$app])) {
			$cmp = version_compare(self::$_applications[$app], $version);
			return ($cmp <= 0);
		}
		return false;
	}

}
