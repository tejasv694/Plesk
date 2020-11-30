<?php
/**
 * Module Google Tracker Data Object class definition
 *
 * This file contains Google Tracker Data Object class definition
 *
 * @version $Id: module_google_tracker_dataobject.php,v 1.2 2008/01/23 07:13:47 chris Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package Module
 * @subpackage Tracker
 */

/**
 * Google Tracker Data Object
 *
 * @package Module
 * @subpackage Tracker
 */
class module_Google_Tracker_DataObject extends module_Tracker_DataObject
{
	/**
	 * CONSTRUCTOR
	 * Passes the record off to the parent _init function to process.
	 *
	 * @param Array $record Record to be loaded to the class (Optional, Default = array())
	 *
	 * @see module_Tracker_DataObject::_init
	 *
	 * @return Void Doesn't return anything.
	 */
	function module_Google_Tracker_DataObject($record = array())
	{
		parent::_init($record);
	}
}
?>
