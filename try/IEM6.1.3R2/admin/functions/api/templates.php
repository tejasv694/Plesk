<?php
/**
* The templates API. It handles loading, fetching of 'live' templates, deleting and saving.
*
* @version     $Id: templates.php,v 1.33 2007/05/28 07:04:50 scott Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Templates_API
*/

/**
* Load up the base API class if we need to.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This will load a template, save a template, set details and get details.
* It will also check access areas.
*
* @package API
* @subpackage Templates_API
*/
class Templates_API extends API
{

	/**
	* The template that is loaded. By default is 0 (no template).
	*
	* @var Int
	*/
	var $templateid = 0;

	/**
	* Name of the template that we've loaded.
	*
	* @var String
	*/
	var $name = '';

	/**
	* The templates' format
	*
	* @var String
	*/
	var $format = 'h';

	/**
	* Whether the template is active or not.
	*
	* @see Active
	*
	* @var Boolean
	*/
	var $active = 0;

	/**
	* Whether the template is a global template or not.
	*
	* @see IsGlobal
	*
	* @var Boolean
	*/
	var $isglobal = 0;

	/**
	* The userid of the owner of this template.
	*
	* @var Int
	*/
	var $ownerid = 0;

	/**
	* The timestamp of when the template was created (integer)
	*
	* @var Int
	*/
	var $createdate = 0;

	/**
	* Default Order to show templates in.
	*
	* @see GetTemplates
	*
	* @var String
	*/
	var $DefaultOrder = 'createdate';

	/**
	* Default direction to show templates in.
	*
	* @see GetTemplates
	*
	* @var String
	*/
	var $DefaultDirection = 'down';

	/**
	* An array of valid sorts that we can use here. This makes sure someone doesn't change the query to try and create an sql error.
	*
	* @see GetTemplates
	*
	* @var Array
	*/
	var $ValidSorts = array('name' => 'Name', 'date' => 'CreateDate');

	/**
	* Constructor
	* Sets up the database object, loads the template if the ID passed in is not 0.
	*
	* @param Int $templateid The templateid of the template to load. If it is 0 then you get a base class only. Passing in a templateid > 0 will load that template.
	*
	* @see GetDb
	* @see Load
	*
	* @return True|Load If no templateid is passed in, this will always return true. If a templateid is passed in, this will return the status from Load.
	*/
	function Templates_API($templateid=0)
	{
		$this->GetDb();
		if ($templateid > 0) {
			return $this->Load($templateid);
		}
		return true;
	}

	/**
	* Load
	* Loads up the template and sets the appropriate class variables.
	*
	* @param Int $templateid The templateid to load up. If the templateid is not present then it will not load up.
	*
	* @return Boolean Will return false if the templateid is not present, or the template can't be found, otherwise it set the class vars and return true.
	*/
	function Load($templateid=0)
	{
		$templateid = (int)$templateid;
		if ($templateid <= 0) {
			return false;
		}

		$query = 'SELECT * FROM ' . SENDSTUDIO_TABLEPREFIX . 'templates WHERE templateid=\'' . $templateid . '\'';
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$template = $this->Db->Fetch($result);
		if (empty($template)) {
			return false;
		}

		$this->templateid = $template['templateid'];
		$this->name = $template['name'];
		$this->createdate = $template['createdate'];
		$this->format = $template['format'];
		$this->textbody = $template['textbody'];
		$this->htmlbody = $template['htmlbody'];
		$this->active = $template['active'];
		$this->isglobal = $template['isglobal'];
		$this->ownerid = $template['ownerid'];

		return true;
	}

	/**
	* Create
	* This function creates a template based on the current class vars.
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Create()
	{
		$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "templates(name, format, active, isglobal, textbody, htmlbody, createdate, ownerid) VALUES('" . $this->Db->Quote($this->name) . "', '" . $this->Db->Quote($this->format) . "', '" . (int)$this->active . "', '" . (int)$this->isglobal . "', '" . $this->Db->Quote($this->textbody) . "', '" . $this->Db->Quote($this->htmlbody) . "', '" . $this->GetServerTime() . "', '" . $this->Db->Quote($this->ownerid) . "')";

		$result = $this->Db->Query($query);
		if ($result) {
			$templateid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'templates_sequence');
			$this->templateid = $templateid;
			return $templateid;
		}
		return false;
	}

	/**
	* Delete
	* Delete a template from the database
	* This will also clean up after itself in case there are any attachments or images associated with the template.
	*
	* @param Int $templateid Templateid of the template to delete. If not passed in, it will delete 'this' template. We delete the template, then reset all class vars.
	*
	* @see remove_directory
	*
	* @return Boolean True if it deleted the template, false otherwise.
	*/
	function Delete($templateid=0)
	{
		if ($templateid == 0) {
			$templateid = $this->templateid;
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "templates WHERE templateid='" . $templateid. "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$template_dir = TEMP_DIRECTORY . '/templates/' . $templateid;
		remove_directory($template_dir);

		$this->templateid = 0;
		$this->name = '';
		$this->format = 'h';
		$this->active = 0;
		$this->SetBody('text', '');
		$this->SetBody('html', '');
		$this->ownerid = 0;
		return true;
	}

	/**
	* Copy
	* Copy a template along with attachments, images etc.
	*
	* @param Int $oldid Templateid of the template to copy.
	*
	* @see Load
	* @see Create
	* @see CopyDirectory
	* @see Save
	*
	* @return Boolean True if it copied the template, false otherwise.
	*/
	function Copy($oldid=0)
	{
		$oldid = (int)$oldid;
		if ($oldid <= 0) {
			return array(false, 'No ID');
		}

		if (!$this->Load($oldid)) {
			return array(false, 'Unable to load old template.');
		}

		$this->name = GetLang('CopyPrefix') . $this->name;
		$newid = $this->Create();
		if (!$newid) {
			return array(false, 'Unable to create new template');
		}

		$this->Load($newid);

		$olddir = TEMP_DIRECTORY . '/templates/' . $oldid;
		$newdir = TEMP_DIRECTORY . '/templates/' . $newid;

		$status = CopyDirectory($olddir, $newdir);

		$this->textbody = str_replace('templates/' . $oldid, 'templates/' . $newid, $this->textbody);
		$this->htmlbody = str_replace('templates/' . $oldid, 'templates/' . $newid, $this->htmlbody);

		$this->Save();

		return array(true, $newid, $status);
	}

	/**
	* Save
	* This function saves the current class vars to the template.
	*
	* @return Boolean Returns true if it worked, false if it fails.
	*/
	function Save()
	{
		if ($this->templateid <= 0) {
			return false;
		}

		$query = "UPDATE " . SENDSTUDIO_TABLEPREFIX . "templates SET name='" . $this->Db->Quote($this->name) . "', textbody='" . $this->Db->Quote($this->textbody) . "', htmlbody='" . $this->Db->Quote($this->htmlbody) . "', format='" . $this->Db->Quote($this->format) . "', active='" . (int)$this->active . "', isglobal='" . (int)$this->isglobal . "' WHERE templateid='" . $this->Db->Quote($this->templateid) . "'";

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		return true;
	}

	/**
	* Active
	* Returns whether the template is active or not.
	*
	* @return Boolean Returns true if the template is active, otherwise returns false.
	*/
	function Active()
	{
		return $this->active;
	}

	/**
	* IsGlobal
	* Returns whether the template is a global template or not. This is a template that anyone can use.
	*
	* @return Boolean Returns true if the template is global, otherwise returns false.
	*/
	function IsGlobal()
	{
		return $this->isglobal;
	}

	/**
	* GetLiveTemplates
	* This function only retrieves live templates from the database. It will find active and global templates. If you pass in an owner, it will also find their templates (on top of the active & global templates).
	*
	* @param Int $ownerid The ownerid to fetch templates for.
	*
	* @see active
	* @see isglobal
	*
	* @return Array Returns an array of templates that are live.
	*/
	function GetLiveTemplates($ownerid=0)
	{
		$user = GetUser($ownerid);

		$qry = "SELECT templateid, name, ownerid FROM " . SENDSTUDIO_TABLEPREFIX . "templates";

		if (!$user->TemplateAdmin()) {
			$access = $user->Get('access');

			$qry .= " WHERE ownerid='" . $this->Db->Quote($user->Get('userid')) . "'";
			if (isset($access['templates']) && is_array($access['templates']) && !empty($access['templates'])) {
				$qry .= " OR templateid IN (" . implode(',', $access['templates']) . ")";
			}
			$qry .= " OR isglobal='1'";
		} else {
			$qry .= " WHERE 1=1";
		}
		$qry .= " AND active > 0 ORDER BY LOWER(name) ASC";

		$result = $this->Db->Query($qry);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$templates = array();
		while ($row = $this->Db->Fetch($result)) {
			$templates[] = $row;
		}
		return $templates;
	}

	/**
	* GetTemplates
	* Get a list of templates based on the criteria passed in.
	*
	* @param Mixed $templates If this parameter is passed in, it should be an array. This will be a list of templateid's the user has access to. If this is not passed in, they are assumed to be an administrator so have access to everything.
	* @param Array $sortinfo An array of sorting information - what to sort by and what direction.
	* @param Boolean $countonly Whether to only get a count of templates, rather than the information.
	* @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	* @param Int|String $perpage How many results to return (max).
	*
	* @see ValidSorts
	* @see DefaultOrder
	* @see DefaultDirection
	*
	* @return Mixed Returns false if it couldn't retrieve template information. Otherwise returns the count (if specified), or a list of templateid's.
	*/
	function GetTemplates($templates=null, $sortinfo=array(), $countonly=false, $start=0, $perpage=0)
	{
		$start = (int)$start;

		if (is_array($templates)) {
			$templates = $this->CheckIntVars($templates);
			$templates[] = '0';
		}

		if ($countonly) {
			$query = "SELECT COUNT(templateid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "templates";
			if (is_array($templates)) {
				$query .= " WHERE templateid IN (" . implode(',', $templates) . ")";
			}
			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "templates";
		if (is_array($templates)) {
			$query .= " WHERE templateid IN (" . implode(',', $templates) . ")";
		}

		$order = (isset($sortinfo['SortBy']) && !is_null($sortinfo['SortBy'])) ? strtolower($sortinfo['SortBy']) : $this->DefaultOrder;

		$order = (in_array($order, array_keys($this->ValidSorts))) ? $this->ValidSorts[$order] : $this->DefaultOrder;

		$direction = (isset($sortinfo['Direction']) && !is_null($sortinfo['Direction'])) ? $sortinfo['Direction'] : $this->DefaultDirection;

		$direction = (strtolower($direction) == 'up' || strtolower($direction) == 'asc') ? 'ASC' : 'DESC';
		$query .= " ORDER BY " . $order . " " . $direction;

		if ($perpage != 'all' && ($start || $perpage)) {
			$query .= $this->Db->AddLimit($start, $perpage);
		}

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}
		$return_templates = array();
		while ($row = $this->Db->Fetch($result)) {
			$return_templates[] = $row;
		}
		return $return_templates;
	}

	/**
	* ReadServerTemplate
	* Reads a template from the SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY based on the name passed in. This is used when creating/editing a newsletter so you can see a list of templates on the server rather than having to load them all in the database. The template will be called templatename/index.html for consistency reasons.
	* Img Src urls are rewritten to point to the sendstudio application url.
	*
	* @param String $templatename Name of the template to load up and return
	*
	* @see SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY
	* @see SENDSTUDIO_APPLICATION_URL
	* @see _GetNewImagePath
	*
	* @return False|String If the directory doesn't exist or if the index.html file doesn't exist, this will return false. Otherwise the template is loaded and the html content is returned.
	*/
	function ReadServerTemplate($templatename='')
	{
		if (!$templatename) {
			return false;
		}
		$templatename = str_replace('servertemplate_', '', $templatename);

		$templatedir = SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY . '/' . $templatename;
		if (!is_dir($templatedir)) {
			return false;
		}

		$templatefile = $templatedir . '/index.html';

		if (!is_file($templatefile)) {
			return false;
		}

		$contents = file_get_contents($templatefile);
		preg_match_all('%img(.*?)src=(["\']*[^"\' >]+["\'> ])%i', $contents, $imagematches);

		foreach ($imagematches[2] as $match) {
			if (substr($match, 0, 4) == 'http') {
				continue;
			}

			$newurl = $this->_GetNewImagePath($match, $templatename);

			$contents = str_replace('src=' . $match, 'src=' . $newurl, $contents);
		}
		unset($imagematches);

		preg_match_all('%background=(["\']*[^"\' >]+["\'> ])%i', $contents, $imagematches);

		foreach ($imagematches[1] as $match) {
			if (substr($match, 0, 4) == 'http') {
				continue;
			}

			$newurl = $this->_GetNewImagePath($match, $templatename);

			$contents = str_replace('background=' . $match, 'background=' . $newurl, $contents);
		}
		unset($imagematches);

		$stylematches = array();
		preg_match_all('%style=(["\']*[^"\'>]+["\'> ])%i', $contents, $stylematches);
		foreach ($stylematches[1] as $m => $match) {
			$imagematches = array();
			preg_match_all('%url\((.*?)\)%', $match, $imagematches);
			foreach ($imagematches[1] as $imagematch) {
				if (substr($imagematch, 0, 4) == 'http') {
					continue;
				}

				$newurl = $this->_GetNewImagePath($imagematch, $templatename);

				$newmatch = str_replace('url(' . $imagematch . ')', 'url(' . $newurl . ')', $match);

				$contents = str_replace($match, $newmatch, $contents);
			}
		}

		preg_match_all('%:background\((.*?)\)%i', $contents, $imagematches);

		foreach ($imagematches[1] as $match) {
			if (substr($match, 0, 4) == 'http') {
				continue;
			}

			$newurl = $this->_GetNewImagePath($match, $templatename);

			$contents = str_replace(':background(' . $match . ')', ':background(' . $newurl . ')', $contents);
		}
		unset($imagematches);

		return $contents;
	}

	/**
	* _GetNewImagePath
	* Converts an image path passed in to a resources/email_templates/$img path.
	* This is needed for background images on tables (for example), standard images, css images.
	* It puts quotes around the url if needed
	*
	* @param String $img The image to convert.
	* @param String $templatename The name of the template we are converting images for.
	*
	* @see ReadServerTemplate
	*
	* @return String Returns the new image url.
	*/
	function _GetNewImagePath($img=false, $templatename)
	{
		if (!$img) {
			return '';
		}

		$addquotes = '';
		if (substr($img, 0, 1) == '"') {
			$img = str_replace('"', '', $img);
			$addquotes = '"';
		}
		if (substr($img, 0, 1) == "'") {
			$img = str_replace("'", '', $img);
			$addquotes = "'";
		}

		// We need to url encode it so that the template loads in ie on step 2 of creating an email campaign or autoresponder
		// We need to replace %2F with / so that images for templates that are 2 levels deep have the correct path and display
		$newurl = SENDSTUDIO_APPLICATION_URL . '/admin/resources/email_templates/' . str_replace('%2F', '/', rawurlencode($templatename)) . '/' . $img;

		if ($addquotes) {
			$newurl = $addquotes . $newurl . $addquotes;
		}
		return $newurl;
	}
}
