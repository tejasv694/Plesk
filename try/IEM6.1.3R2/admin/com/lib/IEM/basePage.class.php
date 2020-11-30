<?php
/**
 * This file contains a "page" base class.
 * This class needs to be extended by every "page" type classes.
 *
 * @package interspire.iem.lib.iem
 */

/**
 *
 * @todo all
 *
 */
abstract class IEM_basePage
{
	/**
	 * CONSTRUCTOR
	 * @todo better PHPDOC
	 */
	public function __construct()
	{

	}

	/**
	 * page_index
	 * The "controller" assume that this function always exists.
	 * If the method is not overwritten, user will be re-directed to index page.
	 *
	 * TODO: becareful with index page... This can create infinite loop when the page_Index class
	 * does not overwrite this method.
	 */
	public function page_index()
	{
		IEM::redirectTo('index');
		return false;
	}
}
