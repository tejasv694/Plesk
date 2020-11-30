<?php
/**
 * This file contains "AdminTools" page class
 *
 * @package interspire.iem.pages
 */

/**
 * AdminTools page class
 *
 * This page contains a list of "admin tools" that can be used
 * to manage this installation.
 *
 * Any administrative functionalities that does not fit anywhere else
 * can be appended to this class.
 *
 * @package interspire.iem.pages
 *
 * TODO better PHPDOC
 */
class page_AdminTools extends IEM_basePage
{
	/**
	 * CONSTRUCTOR
	 * Override parant's constructor.
	 *
	 * We will need to check whether or not current user have the correct
	 * privilage to use "AdminTools"
	 *
	 * TODO better PHPDOC
	 */
	public function __construct()
	{
		// ----- Make sure that current user is an admin
			$currentUser = IEM::getCurrentUser();
			if (!$currentUser || !$currentUser->isAdmin()) {
				IEM::redirectTo('index');
				return false;
			}
		// -----

		return parent::__construct();
	}

	/**
	 * Disguise action
	 *
	 * Administrator is able to disguise (and login) as other users.
	 * This method will facilitate this functionalities.
	 *
	 * TODO better PHPDOC
	 */
	public function page_disguise()
	{
		// newUserID variable need to be passed in as a POST variable
		$reqUserID = IEM::requestGetPOST('newUserID', 0, 'intval');
		if (empty($reqUserID)) {
			IEM::redirectTo('index');
			return false;
		}

		// Attempt to login user with different ID
		if (!IEM::userLogin($reqUserID, false)) {
			IEM::redirectTo('index');
			return false;
		}

		IEM::redirectTo('index');
		return true;
	}
}
