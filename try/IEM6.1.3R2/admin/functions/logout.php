<?php
/**
* This file has the logout functions in it. After logging you out it will redirect you back to the login form.
*
* @version     $Id: logout.php,v 1.7 2006/08/18 04:07:44 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for the logout page. After logging you out it will redirect you back to the login form.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Logout extends SendStudio_Functions
{
	/**
	* Process
	* Logs you out and redirects you back to the login page.
	*
	* @see Login::Process
	*
	* @return Void Doesn't return anything. Unsets session variables, removes the "remember me" cookie if it's set and redirects you back to the login page.
	*/
	function Process()
	{
		$sessionuser = IEM::getCurrentUser();
		$sessionuser->SaveSettings();
		unset($sessionuser);
		
		IEM::userLogout();
		
		IEM::requestRemoveCookie('IEM_CookieLogin');
		IEM::requestRemoveCookie('IEM_LoginPreference');

		$url = SENDSTUDIO_APPLICATION_URL;
		if (substr($url, -1, 1) != '/') {
			$url .= '/';
		}
		$url .= 'admin/index.php';

		header("Location: {$url}");
		exit();
	}
}
