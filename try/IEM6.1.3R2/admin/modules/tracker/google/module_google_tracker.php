<?php
/**
 * Module Google Tracker class definition
 *
 * This file contains Google Tracker module class definition.
 *
 * @version $Id: module_google_tracker.php,v 1.3 2008/01/23 07:13:47 chris Exp $
 * @author Hendri <hendri@interspire.com>
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_Google_Tracker_DataObject
 * @uses module_Tracker
 */

/**
 * Include data object definition
 */
require_once(dirname(__FILE__) . '/module_google_tracker_dataobject.php');

/**
 * Google Tracker Module
 *
 * @package Module
 * @subpackage Tracker
 *
 * @uses module_Google_Tracker_DataObject
 * @uses module_Tracker
 */
class module_Google_Tracker extends module_Tracker
{

	/**
	 * CONSTRUCTOR
	 * Calls the parent init function.
	 *
	 * @see module_Tracker::_init
	 *
	 * @return Void Doesn't return anything.
	 */
	function module_Google_Tracker()
	{
		$this->_init();
	}


	/**
	 * Get HTML options to be displayed
	 *
	 * @return String Returns the parsed template with language variables replaced etc.
	 *
	 * @todo Allow to save user's preference (ie. when user has clicked enable "google", it should be pre-clicked the next time user see it
	 */
	function GetDisplayOption()
	{
		$HTML = array();

		$tpl = GetTemplateSystem();
		$HTML['Required'] = $tpl->ParseTemplate('Required', true);
		unset($tpl);

		$tpl = GetTemplateSystem(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'templates');
		$tpl->Assign('HTML', $HTML);
		return $tpl->ParseTemplate('Options', true);
	}

	/**
	 * Process options from request
	 *
	 * @param Array $request Request variables (ie. $_POST, $_GET, $_REQUEST)
	 *
	 * @return Boolean Returns TRUE if successful and data is processed and saved to database, FALSE otherwise
	 */
	function ProcessOptions($request)
	{
		if (!array_key_exists('module_tracker_google_use', $request)) {
			return false;
		}

		$data = array(
			'CampaignName'	=> 'Email Campaign',
			'SourceName'	=> 'MailingList'
		);

		if (isset($request['module_tracker_google_options_name'])) {
			$data['CampaignName'] = $request['module_tracker_google_options_name'];
		}

		if (isset($request['module_tracker_google_options_source'])) {
			$data['SourceName'] = $request['module_tracker_google_options_source'];
		}

		$bean = $this->GetDataObject();

		if (!$bean->setID($request['statid'], $request['stattype'], false)) {
			return false;
		}

		if (!$bean->setNewsletterID($request['newsletterid'], false)) {
			return false;
		}

		if (!$bean->setData($data)) {
			return false;
		}

		return $bean->save();
	}

	/**
	 * Get request variable names used by tracker
	 *
	 * @return Array Returns variable names in request used by tracker
	 */
	function GetRequestOptionNames()
	{
		return array('module_tracker_google_use', 'module_tracker_google_options_name', 'module_tracker_google_options_source');
	}

	/**
	 * Get record by ID
	 * If an invalid id or stats type is passed in, it will trigger an error and return false.
	 * If a statistic can't be loaded from the database, then it will also trigger an error and return false.
	 * Otherwise it returns a data object.
	 *
	 * @param Integer $statisticID The statistic to load up from the database.
	 * @param String $statisticType Statistic type to load up from the database.
	 *
	 * @throws E_USER_NOTICE Invalid ID
	 *
	 * @uses Db::Query()
	 * @uses Db::Quote()
	 * @uses Db::FreeResult()
	 *
	 * @return module_Tracker_DataObject|Null|False Returns data object if successful, NULL if no record matched, FALSE otherwise
	 */
	function GetRecordByID($statisticID, $statisticType)
	{
		$mStatID = intval($statisticID);
		$mStatType = in_array($statisticType, $this->_getValidStatisticTypes())? $statisticType : '';

		if ($mStatID == 0 || $mStatType == '') {
			trigger_error('module_Google_Tracker::GetRecordByID -- Invalid ID', E_USER_NOTICE);
			return false;
		}

		$mStatType = $this->_db->Quote($mStatType);

		$rs = $this->_db->Query('SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'module_tracker'.
								" WHERE statid = {$mStatID} AND stattype = '{$mStatType}'");

		if ($rs == false) {
			trigger_error('module_Google_Tracker::GetRecordByID -- Cannot execute query', E_USER_NOTICE);
			return false;
		}

		$record = $this->_db->Fetch($rs);
		$this->_db->FreeResult($rs);

		if ($record === false) {
			return null;
		}
		return new module_Google_Tracker_DataObject($record);
	}



	/**
	 * _ProcessURL
	 * This puts the url together for link tracking to use.
	 * It adds the utm_medium, utm_content and utm_campaign parameters to the url passed in and returns it
	 * This is then used by link tracking to redirect the subscriber to the right location.
	 *
	 * This method should only be called by the data object
	 *
	 * @param Array $trackerRecord Record fetched from database
	 * @param Array $linkRecord The link to append the campaign information to.
	 * @param Array $subscriberRecord The email address that we are adding to the end of the campaign url.
	 *
	 * @return String|False If a url is not passed in, this will return false. Otherwise the campaign link is put together and returned.
	 */
	function _ProcessURL($trackerRecord=array(), $linkRecord=array(), $subscriberRecord=array())
	{
		if (!array_key_exists('url', $linkRecord)) {
			return false;
		}

		$addedURLstring = '';
		if (strpos($linkRecord['url'], '?') === false) {
			$addedURLstring = '?';
		} else {
			$addedURLstring = '&';
		}

		if (!array_key_exists('SourceName', $trackerRecord['data'])) {
			$trackerRecord['data']['SourceName'] = $trackerRecord['data']['CampaignName'];
		}

		$addedURLstring .= 'utm_source='.urlencode($trackerRecord['data']['SourceName']);
		$addedURLstring .= '&utm_medium=email';
		$addedURLstring .= '&utm_campaign='.urlencode($trackerRecord['data']['CampaignName']);

		return ($linkRecord['url'] . $addedURLstring);
	}
}
