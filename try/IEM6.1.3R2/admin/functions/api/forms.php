<?php
/**
* The Forms API.
*
* @version     $Id: forms.php,v 1.44 2008/02/25 06:59:27 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package API
* @subpackage Forms_API
*/

/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This will load a form, save a form, set details and get details.
*
* @package API
* @subpackage Forms_API
*/
class Forms_API extends API
{

	/**
	* The form we've loaded or created.
	*
	* @see Load
	*
	* @var Int
	*/
	var $formid = 0;

	/**
	* Name of the form that we've loaded.
	*
	* @var String
	*/
	var $name = '';

	/**
	* Design of the form we've loaded.
	*
	* @var String
	*/
	var $design = '';

	/**
	* The type of form. It's either a 's'ubscription form, 'u'nsubscription form or 'm'odify details form.
	*
	* @var Char
	*/
	var $formtype = 's';

	/**
	* Whether to let the subscriber choose the format they wish to receive. If it is 'c', they choose. If it is 'fh', they are forced to subscribe as html, if it is 'ft', they are forced to subscribe as text.
	*
	* @var Char chooseformat
	*/
	var $chooseformat = 'c';

	/**
	* Whether to let the subscriber change the format of the newsletter/autoresponders they receive. This is a boolean variable and only used by modify details forms.
	*
	* @var Boolean changeformat
	*/
	var $changeformat = false;

	/**
	* An array of mailing list id's this form is associated with.
	*
	* @var Array
	*/
	var $lists = array();

	/**
	* An array of mailing customfield id's this form is associated with. This is a multidimensional array, the first dimension is the listid, the second is the customfield id's.
	*
	* @var Array
	*/
	var $customfields = array();

	/**
	* Whether the form requires a confirmation email be sent or not.
	*
	* @var Boolean
	*/
	var $requireconfirm = true;

	/**
	* Whether the form sends a thanks to the subscriber for joining.
	*
	* @var Boolean
	*/
	var $sendthanks = true;

	/**
	* Whether the form is a contact form or not.
	*
	* @var Boolean
	*/
	var $contactform = false;

	/**
	* Whether the form should include captcha or not.
	*
	* @var Boolean
	*/
	var $usecaptcha = false;

	/**
	* The userid of the owner of this newsletter.
	*
	* @var Int
	*/
	var $ownerid = 0;

	/**
	* The timestamp of when the newsletter was created (integer)
	*
	* @var Int
	*/
	var $createdate = 0;

	/**
	* Default Order to show forms in.
	*
	* @see GetForms
	*
	* @var Array
	*/
	var $DefaultOrder = 'createdate';

	/**
	* Default direction to show forms in.
	*
	* @see GetForms
	*
	* @var String
	*/
	var $DefaultDirection = 'down';

	/**
	* An array of valid sorts that we can use here. This makes sure someone doesn't change the query to try and create an sql error.
	*
	* @see GetForms
	*
	* @var Array
	*/
	var $ValidSorts = array('name' => 'Name', 'date' => 'CreateDate');

	/**
	* FormHTML
	* Modify Details and Send-To-Friend forms cannot be displayed on external sites.
	* Instead we allow users to customise the forms and they are stored in the database.
	*
	* @var String
	*/
	var $formhtml = '';

	/**
	* List of all form types.
	* The option is the language pack variable to display.
	*
	* @see GetFormTypes
	*
	* @var Array
	*/
	var $FormTypes = array('s' => 'Subscribe', 'u' => 'Unsubscribe', 'm' => 'ModifyDetails', 'f' => 'SendToFriend');

	/**
	* The pages the form can contain.
	* They contain a lot of data such as the html version, text version, url, send details and so on.
	*
	* @see Save
	* @see Create
	* @see Load
	*
	* @var Array
	*/
	var $pages = array(
		'ErrorPage' => array(
			'html' => '', 'url' => '', 'sendfromname' => null, 'sendfromemail' => null, 'replytoemail' => null, 'bounceemail' => null, 'emailsubject' => null, 'emailhtml' => null, 'emailtext' => null
		),
		'ThanksPage' => array(
			'html' => '', 'url' => '', 'sendfromname' => '', 'sendfromemail' => '', 'replytoemail' => '', 'bounceemail' => '', 'emailsubject' => '', 'emailhtml' => '', 'emailtext' => ''
		),
		'ConfirmPage' => array(
			'html' => '', 'url' => '', 'sendfromname' => '', 'sendfromemail' => '', 'replytoemail' => '', 'bounceemail' => '', 'emailsubject' => '', 'emailhtml' => '', 'emailtext' => ''
		),
		'SendFriendPage' => array(
			'emailhtml' => '', 'emailtext' => ''
		)
	);

	/**
	* The order of the fields for the form. This includes email, the list options, custom fields.
	*
	* @see LoadCustomFields
	* @see SaveCustomFields
	* @see GetHTML
	*
	* @var Array
	*/
	var $fieldorder = array();


	/**
	 * Sets up the database object, loads the form if the ID passed in is not 0.
	 *
	 * @param int  $formid The formid of the form to load. If it is 0 then you get a base class only. Passing in a formid > 0 will load that form.
	 * @param bool $connect_to_db Whether to connect to the database or not. If this is set to false, you need to set the database up yourself.
	 *
	 * @return bool Returns true if there is no formid passed in. If there is a formid passed in, it will return the value from Load.
	 */
	function Forms_API($formid=0, $connect_to_db=true)
	{
		if ($connect_to_db) {
			$this->GetDb();
		}

		if ($formid > 0) {
			return $this->Load($formid);
		}
		
		return true;
	}

	/**
	 * Loads up the form and sets the appropriate class variables.
	 *
	 * @param int $formid The formid to load up. If the formid is not present or it is not a number, then it will not load up.
	 *
	 * @return bool Will return false if the formid is not present, or the form can't be found, otherwise it set the class vars and return true.
	 */
	function Load($formid = 0)
	{
		$formid = (int)$formid;
		
		if ($formid <= 0) {
			return false;
		}
        
		$query = "
			SELECT 
				f.*, 
				ug.forcedoubleoptin
			FROM 
				[|PREFIX|]forms      AS f, 
				[|PREFIX|]users      AS u,
				[|PREFIX|]usergroups AS ug
			WHERE 
				f.formid   = " . $formid . " AND 
				f.ownerid  = u.userid        AND
				ug.groupid = u.groupid
		";
        
		$result = $this->Db->Query($query);
		
		if (!$result) {
			return false;
		}
        
		$form = $this->Db->Fetch($result);
		
		if (empty($form)) {
			return false;
		}

		$this->formid         = $form['formid'];
		$this->name           = $form['name'];
		$this->design         = $form['design'];
		$this->chooseformat   = trim($form['chooseformat']);
		$this->sendthanks     = trim($form['sendthanks']);
		$this->requireconfirm = ($form['forcedoubleoptin'] || trim($form['requireconfirm']));
		$this->changeformat   = trim($form['changeformat']);
		$this->contactform    = trim($form['contactform']);
		$this->usecaptcha     = trim($form['usecaptcha']);
		$this->ownerid        = $form['ownerid'];
		$this->createdate     = $form['createdate'];
		$this->formtype       = $form['formtype'];
		$this->formhtml       = $form['formhtml'];

		$this->LoadPages($formid);
		$this->LoadLists($formid);
		$this->LoadCustomFields($formid);
		
		return true;
	}

	/**
	 * Loads up the pages associated with this form. This will fill the pages class variable for easy use.
	 *
	 * @param int $formid The form to load pages for.
	 *
	 * @return bool Returns false if the pages can't be loaded, otherwise the variables are set and this returns true.
	 */
	function LoadPages($formid=0)
	{
		$formid = (int)$formid;

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "form_pages WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$pages = array();
		while ($row = $this->Db->Fetch($result)) {
			$pagetype = $row['pagetype'];
			$pages[$pagetype]['html'] = $row['html'];
			$pages[$pagetype]['url'] = $row['url'];
			$pages[$pagetype]['sendfromname'] = $row['sendfromname'];
			$pages[$pagetype]['sendfromemail'] = $row['sendfromemail'];
			$pages[$pagetype]['replytoemail'] = $row['replytoemail'];
			$pages[$pagetype]['bounceemail'] = $row['bounceemail'];
			$pages[$pagetype]['emailsubject'] = $row['emailsubject'];
			$pages[$pagetype]['emailhtml'] = $row['emailhtml'];
			$pages[$pagetype]['emailtext'] = $row['emailtext'];
		}

		foreach (array_keys($this->pages) as $p => $pagename) {
			if (isset($pages[$pagename]) && is_array($pages[$pagename])) {
				continue;
			}

			foreach ($this->pages[$pagename] as $k => $v) {
				$pages[$pagename][$k] = '';
			}
		}
		$this->pages = $pages;
	}

	/**
	* LoadLists
	* Loads up the lists associated with this form and sets them in the lists class variable.
	*
	* @param Int $formid The form to load lists for.
	*
	* @see lists
	*
	* @return Boolean Returns false if the lists can't be loaded, otherwise the variables are set and this returns true.
	*/
	function LoadLists($formid=0)
	{
		$formid = (int)$formid;
		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "form_lists WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$lists = array();
		while ($row = $this->Db->Fetch($result)) {
			$lists[] = $row['listid'];
		}
		$this->lists = $lists;
		return true;
	}

	/**
	* LoadCustomFields
	* Loads up the custom fields for the form id passed in, once that's done it puts them (per list) into the customfields class variable.
	*
	* @param Int $formid The formid to load custom fields for.
	*
	* @see customfields
	*
	* @return Boolean Returns false if the fields can't be loaded, otherwise sets the variables and returns true.
	*/
	function LoadCustomFields($formid=0)
	{
		$formid = (int)$formid;

		$query = "SELECT * FROM " . SENDSTUDIO_TABLEPREFIX . "form_customfields WHERE formid='" . $formid . "' ORDER BY fieldorder ASC";

		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}

		$customfields = array();

		$fieldorder = array();

		while ($row = $this->Db->Fetch($result)) {
			$customfields[] = $row['fieldid'];
			$fieldorder[] = $row['fieldid'];
		}
		$this->customfields = $customfields;
		$this->fieldorder = $fieldorder;

		return true;
	}

	/**
	* Create
	* Saves the current class variables as a new form in the database. It will get the next id from the forms_sequence and use that for everything.
	*
	* @see SavePages
	* @see SaveLists
	* @see SaveCustomFields
	*
	* @return Boolean Returns false if the initial creation failed, otherwise returns false.
	*/
	function Create()
	{

		$contactform = 0;
		if ($this->contactform) {
			$contactform = 1;
		}

		$quotedName = $this->Db->Quote($this->name);
		$quotedDesign = $this->Db->Quote($this->design);
		$quotedFormat = $this->Db->Quote($this->chooseformat);
		$quotedChangeFormat = ($this->changeformat ? 1 : 0);
		$quotedUseCAPTCHA = ($this->usecaptcha ? 1 : 0);
		$quotedSendThanks = ($this->sendthanks ? 1 : 0);
		$quotedRequireConfirm = ($this->requireconfirm ? 1 : 0);
		$quotedFormType = $this->Db->Quote($this->formtype);
		$quotedFormHTML = $this->Db->Quote($this->formhtml);
		$quotedOwnerID = intval($this->ownerid);
		$quotedServerTime = $this->GetServerTime();

		$query = "
			INSERT INTO [|PREFIX|]forms(
				name, design, chooseformat, changeformat,
				contactform, usecaptcha, sendthanks, requireconfirm,
				formtype, formhtml, ownerid, createdate
			) VALUES (
				'{$quotedName}', '{$quotedDesign}', '{$quotedFormat}', '{$quotedChangeFormat}',
				'{$contactform}', '{$quotedUseCAPTCHA}', '{$quotedSendThanks}', '{$quotedRequireConfirm}',
				'{$quotedFormType}', '{$quotedFormHTML}', {$quotedOwnerID}, {$quotedServerTime}
			)
		";

		$result = $this->Db->Query($query);

		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$formid = $this->Db->LastId(SENDSTUDIO_TABLEPREFIX . 'forms_sequence');

		$this->SavePages($formid);

		$this->SaveLists($formid);

		$this->SaveCustomFields($formid);

		$this->formid = $formid;

		return $formid;
	}

	/**
	* Save
	* Saves the current class vars to the database. If the form isn't loaded it will not do anything, so the form has to be loaded before it can be saved.
	*
	* @see formid
	* @see SavePages
	* @see SaveLists
	* @see SaveCustomFields
	*
	* @return Boolean Returns false if the form isn't loaded or there is a problem with the database query, otherwise returns true.
	*/
	function Save()
	{
		if ($this->formid <= 0) {
			return false;
		}

		$contactform = 0;
		if ($this->contactform) {
			$contactform = 1;
		}

		$quotedName = $this->Db->Quote($this->name);
		$quotedDesign = $this->Db->Quote($this->design);
		$quotedFormat = $this->Db->Quote($this->chooseformat);
		$quotedChangeFormat = ($this->changeformat ? 1 : 0);
		$quotedUseCAPTCHA = ($this->usecaptcha ? 1 : 0);
		$quotedSendThanks = ($this->sendthanks ? 1 : 0);
		$quotedRequireConfirm = ($this->requireconfirm ? 1 : 0);
		$quotedFormType = $this->Db->Quote($this->formtype);
		$quotedFormHTML = $this->Db->Quote($this->formhtml);
		$quotedFormID = intval($this->formid);

		$query = "
			UPDATE	[|PREFIX|]forms

			SET		name = '{$quotedName}',
					design = '{$quotedDesign}',
					chooseformat = '{$quotedFormat}',
					changeformat = '{$quotedChangeFormat}',
					usecaptcha = '{$quotedUseCAPTCHA}',
					sendthanks = '{$quotedSendThanks}',
					requireconfirm = '{$quotedRequireConfirm}',
					contactform = '{$contactform}',
					formtype = '{$quotedFormType}',
					formhtml = '{$quotedFormHTML}'

			WHERE	formid = {$quotedFormID}
		";

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$this->SavePages($this->formid);

		$this->SaveLists($this->formid);

		$this->SaveCustomFields($this->formid);

		return true;
	}

	/**
	* SaveCustomFields
	* Saves the custom assocations for the form after deleting the old ones.
	*
	* @param Int $formid The formid to save the assocations to.
	*
	* @see customfields
	* @see Create
	* @see Save
	*
	* @return boolean Returns TRUE if successful, FALSE otherwise
	*/
	function SaveCustomFields($formid=0)
	{
		$formid = intval($formid);

		$this->Db->StartTransaction();

		$query = "DELETE FROM [|PREFIX|]form_customfields WHERE formid={$formid}";
		$result = $this->Db->Query($query);

		if (!empty($this->customfields)) {
			foreach ($this->customfields as $t => $fieldid) {
				if (in_array($fieldid, array('e', 'cl', 'cf'))) {
					continue;
				}

				$fieldorder = array_search($fieldid, $this->fieldorder) + 1;
				$quotedFieldid = intval($fieldid);

				$query = "
					INSERT INTO [|PREFIX|]form_customfields (formid, fieldid, fieldorder)
					VALUES ({$formid}, '{$quotedFieldid}', {$fieldorder})
				";

				$status = $this->Db->Query($query);
				if (!$status) {
					$this->Db->RollbackTransaction();
					list($error, $level) = $this->Db->GetError();
					trigger_error($error, $level);
					return false;
				}
			}
		}

		$fields_required = array('e');
		$fields_optional = array('cl', 'cf');
		$fields = array_merge($fields_required, $fields_optional);

		foreach ($fields as $field) {
			$fieldorder = array_search($field, $this->fieldorder);
			if ($fieldorder === false || is_null($fieldorder)) {
				if (in_array($field, $fields_required)) {
					$fieldorder = 0;
				} else {
					$fieldorder = false;
				}
			}

			if ($fieldorder !== false) {
				++$fieldorder;
				$query = "INSERT INTO [|PREFIX|]form_customfields(formid, fieldid, fieldorder) VALUES ({$formid}, '{$field}', {$fieldorder})";
				$status = $this->Db->Query($query);
				if (!$status) {
					$this->Db->RollbackTransaction();
					list($error, $level) = $this->Db->GetError();
					trigger_error($error, $level);
					return false;
				}
			}
		}

		$this->Db->CommitTransaction();
		return true;
	}

	/**
	* SaveLists
	* Saves the list assocations for the form after deleting the old ones.
	*
	* @param Int $formid The formid to save the assocations to.
	*
	* @see lists
	* @see Create
	* @see Save
	*
	* @return True Always returns true.
	*/
	function SaveLists($formid=0)
	{
		$formid = (int)$formid;
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "form_lists WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);

		foreach ($this->lists as $p => $listid) {
			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "form_lists(formid, listid) VALUES ('" . $formid . "', '" . $this->Db->Quote($listid) . "')";
			$this->Db->Query($query);
		}
		return true;
	}

	/**
	* SavePages
	* Deletes old pages associated with this form, then goes through the pages listed in the class and saves the content to the database. It will go through each area and set a default of '' if it's not specified.
	*
	* @param Int $formid The id of the form to save the information to.
	*
	* @see pages
	* @see Create
	* @see Save
	*
	* @return True Always returns true.
	*/
	function SavePages($formid=0)
	{
		$formid = (int)$formid;

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "form_pages WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);

		$pagetypes = array_keys($this->pages);

		foreach ($pagetypes as $p => $pagetype) {
			if (isset($this->pages[$pagetype]['html'])) {
				$pagehtml = $this->pages[$pagetype]['html'];
			} else {
				$pagehtml = '';
			}

			if (isset($this->pages[$pagetype]['url'])) {
				$pageurl = $this->pages[$pagetype]['url'];
			} else {
				$pageurl = '';
			}

			if (isset($this->pages[$pagetype]['sendfromname'])) {
				$sendfromname = $this->pages[$pagetype]['sendfromname'];
			} else {
				$sendfromname = '';
			}

			if (isset($this->pages[$pagetype]['sendfromemail'])) {
				$sendfromemail = $this->pages[$pagetype]['sendfromemail'];
			} else {
				$sendfromemail = '';
			}

			if (isset($this->pages[$pagetype]['replytoemail'])) {
				$replyto = $this->pages[$pagetype]['replytoemail'];
			} else {
				$replyto = '';
			}

			if (isset($this->pages[$pagetype]['bounceemail'])) {
				$bounceemail = $this->pages[$pagetype]['bounceemail'];
			} else {
				$bounceemail = '';
			}

			if (isset($this->pages[$pagetype]['emailsubject'])) {
				$subject = $this->pages[$pagetype]['emailsubject'];
			} else {
				$subject = '';
			}

			if (isset($this->pages[$pagetype]['emailhtml'])) {
				$emailhtml = $this->pages[$pagetype]['emailhtml'];
			} else {
				$emailhtml = '';
			}

			if (isset($this->pages[$pagetype]['emailtext'])) {
				$emailtext = $this->pages[$pagetype]['emailtext'];
			} else {
				$emailtext = '';
			}

			$query = "INSERT INTO " . SENDSTUDIO_TABLEPREFIX . "form_pages(formid, pagetype, html, url, sendfromname, sendfromemail, replytoemail, bounceemail, emailsubject, emailhtml, emailtext) VALUES ('" . $formid . "', '" . $this->Db->Quote($pagetype) . "', '" . $this->Db->Quote($pagehtml) . "', '" . $this->Db->Quote($pageurl) . "', '" . $this->Db->Quote($sendfromname) . "', '" . $this->Db->Quote($sendfromemail) . "', '" . $this->Db->Quote($replyto) . "', '" . $this->Db->Quote($bounceemail) . "', '" . $this->Db->Quote($subject) . "', '" . $this->Db->Quote($emailhtml) . "', '" . $this->Db->Quote($emailtext) . "')";

			$this->Db->Query($query);
		}
		return true;
	}

	/**
	* Delete
	* Delete a form from the database. It will delete all pages, list assocations and custom field assocations as well.
	*
	* @param Int $formid FormID of the form to delete. If not passed in, it will delete 'this' form. We delete the form, then reset all class vars.
	*
	* @return Boolean True if it deleted the form, false otherwise.
	*/
	function Delete($formid=0)
	{
		if ($formid == 0) {
			$formid = $this->formid;
		}

		$formid = (int)$formid;
		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "forms WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			$this->SetError($this->Db->GetError());
			return false;
		}

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "form_pages WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "form_lists WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);

		$query = "DELETE FROM " . SENDSTUDIO_TABLEPREFIX . "form_customfields WHERE formid='" . $formid . "'";
		$result = $this->Db->Query($query);

		return true;
	}

	/**
	* Copy
	* Copy a form. This simply loads the old form, changes the name and then saves the new details under a new id. If any step fails, it will return false. If all succeed, then the new form id is returned.
	*
	* @param Int $oldid FormID of the form to copy.
	*
	* @see Load
	* @see Create
	*
	* @return False|Int This returns false if the old form can't be loaded or the create call doesn't work, otherwise it returns the new formid.
	*/
	function Copy($oldid=0)
	{
		$oldid = (int)$oldid;
		if ($oldid <= 0) {
			return false;
		}

		if (!$this->Load($oldid)) {
			return false;
		}

		$this->name = GetLang('CopyPrefix') . $this->name;

		$newid = $this->Create();
		if (!$newid) {
			return false;
		}

		return true;
	}

	/**
	* GetForms
	* Get a list of forms based on the criteria passed in. This is used by the admin area to display all forms the user has access to.
	*
	* @param Int $ownerid Ownerid of the forms to check for.
	* @param Array $sortinfo An array of sorting information - what to sort by and what direction.
	* @param Boolean $countonly Whether to only get a count of forms, rather than the information.
	* @param Int $start Where to start in the list. This is used in conjunction with perpage for paging.
	* @param Int|String $perpage How many results to return (max).
	*
	* @see ValidSorts
	* @see DefaultOrder
	* @see DefaultDirection
	* @see Forms::Process
	*
	* @return Mixed Returns false if it couldn't retrieve form information. Otherwise returns the count (if specified), or an array of forms.
	*/
	function GetForms($ownerid=0, $sortinfo=array(), $countonly=false, $start=0, $perpage=0)
	{
		if ($countonly) {
			$query = "SELECT COUNT(formid) AS count FROM " . SENDSTUDIO_TABLEPREFIX . "forms";
			if ($ownerid) {
				$query .= " WHERE ownerid='" . $this->Db->Quote($ownerid) . "'";
			}

			$result = $this->Db->Query($query);
			return $this->Db->FetchOne($result, 'count');
		}

		$query = "SELECT f.*, u.username AS username, u.fullname AS fullname, ";
		$query .= "CASE WHEN u.fullname = '' THEN u.username ELSE u.fullname END AS owner ";
		$query .= "FROM [|PREFIX|]forms f LEFT JOIN [|PREFIX|]users u ON f.ownerid = u.userid ";
		if ($ownerid) {
			$query .= "WHERE f.ownerid=" . intval($ownerid);
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

		$forms = array();
		while ($row = $this->Db->Fetch($result)) {
			$forms[] = $row;
		}
		return $forms;
	}

	/**
	* GetUserForms
	* Gets a list of forms that the owner passed in has access to.
	* This is used by the custom fields display window to show modify details forms and send-to-friend form placeholders.
	*
	* @param Int $ownerid The owner if the form. If this is not present, then all owners are returned.
	* @param Char $formtype The type of form to return. This can be 'm'odify details, or send to 'f'riend.
	*
	* @see ShowCustomFields::Process
	*/
	function GetUserForms($ownerid=0, $formtype='m')
	{
		$formtype = strtolower(substr($formtype, 0, 1));
		$query = "SELECT formid, name FROM " . SENDSTUDIO_TABLEPREFIX . "forms WHERE formtype='" . $this->Db->Quote($formtype) . "'";
		if ($ownerid) {
			$query .= " AND ownerid='" . $this->Db->Quote($ownerid) . "'";
		}

		$query .= " ORDER BY name DESC";

		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			trigger_error($error, $level);
			return false;
		}

		$forms = array();
		while ($row = $this->Db->Fetch($result)) {
			$forms[] = $row;
		}
		return $forms;
	}

	/**
	* GetFormTypes
	* Returns a list of all form types.
	*
	* @see FormTypes
	*
	* @return Array List of all form types that we can use.
	*/
	function GetFormTypes()
	{
		return $this->FormTypes;
	}

	/**
	* GetFormType
	* Returns a specific form type.
	*
	* @see FormTypes
	*
	* @return String the language var for the form type.
	*/
	function GetFormType($formtype=false)
	{
		if (!isset($this->FormTypes[$formtype])) {
			return false;
		}

		return $this->FormTypes[$formtype];
	}

	/**
	* GetFormDesigns
	* Returns a list of all form designs.
	*
	* @return Array List of all form designs that we can use.
	*/
	function GetFormDesigns()
	{
		$formdesigns = array();
		$formdir = SENDSTUDIO_FORM_DESIGNS_DIRECTORY;
		if (!is_dir($formdir)) {
			return $formdesigns;
		}

		$designs = list_files($formdir, null, true);
		foreach ($designs as $name => $type) {
			if (strtolower($name) == 'cvs' || strtolower($name) == 'captcha' || strtolower($name) == '.svn') {
				continue;
			}
			$formdesigns[htmlspecialchars($name, ENT_QUOTES, SENDSTUDIO_CHARSET)] = ucwords(str_replace('_', ' ', $name));
		}
		ksort($formdesigns);
		return $formdesigns;
	}

	/**
	* GetFormDesign
	* Gets a specific form design based on the criteria passed in. This will load up the base design for a form.
	*
	* @param String $designname The name of the design we're trying to load
	* @param String $formtype The type of form we're trying to load for the design.
	* @param Boolean $content Whether to return the content or just the name of the file. This is used by the admin area to check to make sure the design is valid, then the full path is passed to Sendstudio_Functions::ParseTemplate to prefill any placeholders present.
	*
	* @see GetHTML
	* @see GetFormDesigns
	* @see FormTypes
	* @see FetchFile
	*
	* @return Mixed Returns false if the design doesn't exist or it's an invalid form type. If content is true, then the file is loaded up and returned as a string. If it's not true, this returns the full path to the file on the server.
	*/
	function GetFormDesign($designname=false, $formtype=false, $content=false)
	{
		if (!$designname || !$formtype) {
			return false;
		}

		$designs = $this->GetFormDesigns();
		if (!$designs) {
			return false;
		}

		$types = array_keys($this->FormTypes);
		if (!in_array($formtype, $types)) {
			return false;
		}

		$designname = htmlspecialchars($designname, ENT_QUOTES, SENDSTUDIO_CHARSET);
		$designnames = array_keys($designs);
		if (!in_array($designname, $designnames)) {
			return false;
		}

		$filename = strtolower($this->FormTypes[$formtype]);

		if ($content) {
			return $this->FetchFile($designname, $formtype);
		}

		$file = SENDSTUDIO_FORM_DESIGNS_DIRECTORY . '/' . $designname . '/' . $filename . '.html';

		if (!is_file($file)) {
			return false;
		}

		return $file;
	}

	/**
	* FetchFile
	* Fetchs a file from the server and returns it's contents. This is used for loading up a form design or the options file it uses to display information.
	*
	* @param String $design The name of the design we're trying to load up.
	* @param String $formtype The type of form we're trying to load up. The different form types might have different displays (for example, the unsubscribe form won't include custom field information it's very basic). This can be left out in case a $file is specified (eg required/notrequired).
	* @param String $file The filename to load up. This can be used with or without the formtype. For example, the required/notrequired file doesn't change depending on the formtype.
	*
	* @return False|String Returns false if the file doesn't exist, otherwise it is loaded and returned as a string.
	*/
	function FetchFile($design=false, $formtype=false, $file=false)
	{
		$fullpath = SENDSTUDIO_FORM_DESIGNS_DIRECTORY . '/' . $design . '/';

		if ($formtype) {
			$formtype = strtolower($this->FormTypes[$formtype]);
			$fullpath .=  $formtype;
		}

		if ($file) {
			$fullpath .= $file;
		}

		$fullpath .= '.html';
		if (!is_file($fullpath)) {
			return false;
		}

		return file_get_contents($fullpath);
	}

	/**
	* GetHTML
	* Gets the html for the particular form type and form design that is loaded.
	* This will also load up custom fields, put them into the form, add format choice dropdown (if applicable) and finally return the form html for displaying or putting on a website.
	* If it's a modify details form, then there are placeholders put into the form so the calling object/method can pre-fill the form as necessary.
	*
	* @param Boolean $inside_sendstudio Pass in whether we are viewing the form from inside the application or not. This allows us to include/exclude information accordingly. This stops a problem where viewing a form will log you out of the admin control panel.
	*
	* @see GetFormDesign
	* @see FetchFile
	* @see formtype
	* @see chooseformat
	* @see changeformat
	* @see lists
	* @see customfields
	*
	* @return String Returns the form's html content.
	*/
	function GetHTML($inside_sendstudio=false)
	{
		/**
		* This file lets us get api's, load language files and parse templates.
		*/
		if (!class_exists('sendstudio_functions', false)) {
			require_once(SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php');
		}

		$sendstudio_functions = new Sendstudio_Functions();
		$sendstudio_functions->LoadLanguageFile('frontend');
		$sendstudio_functions->LoadLanguageFile('forms');

		$content = $this->GetFormDesign($this->design, $this->formtype, true);

		$displayoption = $this->FetchFile($this->design, $this->formtype, '_options');

		$requiredoption = $this->FetchFile($this->design, false, 'required');
		$notrequiredoption = $this->FetchFile($this->design, false, 'notrequired');

		$javascript = '
			function CheckMultiple' . $this->formid . '(frm, name) {
				for (var i=0; i < frm.length; i++)
				{
					fldObj = frm.elements[i];
					fldId = fldObj.id;
					if (fldId) {
						var fieldnamecheck=fldObj.id.indexOf(name);
						if (fieldnamecheck != -1) {
							if (fldObj.checked) {
								return true;
							}
						}
					}
				}
				return false;
			}
		';

		$javascript .= 'function CheckForm' . $this->formid . '(f) {';

		$email_placeholder = '';
		if ($this->formtype == 'm' || $this->formtype == 'f') {
			$email_placeholder = '%%Email%%';
		}

		$alert = GetLang('Form_Javascript_EnterEmailAddress');

		$javascript .= '
			var email_re = /[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/i;
			if (!email_re.test(f.email.value)) {
				alert("' . $alert . '");
				f.email.focus();
				return false;
			}
		';

		$formatlist = '';
		if ($this->formtype != 'u') {
			if ($this->formtype == 'm' && $this->changeformat) {
				$optionname = $requiredoption . GetLang('Form_ChooseFormat') . ':';

				$option = '<select name="format">';
				$option .= '<option value="h"%%Format_html%%>' . GetLang('Format_HTML') . '</option>';
				$option .= '<option value="t"%%Format_text%%>' . GetLang('Format_Text') . '</option>';
				$option .= '</select>';

				$formatlist = str_replace(array('%%GLOBAL_OptionName%%', '%%GLOBAL_Option%%'), array($optionname, $option), $displayoption);
			} elseif ($this->formtype != 'm') {
				if ($this->chooseformat == 'c') {
					$optionname = $requiredoption . GetLang('Form_ChooseFormat') . ':';

					$option = '<select name="format">';
					$option .= '<option value="h">' . GetLang('Format_HTML') . '</option>';
					$option .= '<option value="t">' . GetLang('Format_Text') . '</option>';
					$option .= '</select>';

					$alert = GetLang('Form_Javascript_ChooseFormat');
					$javascript .= '
						if (f.format.selectedIndex == -1) {
							alert("' . $alert . '");
							f.format.focus();
							return false;
						}
					';

					$formatlist = str_replace(array('%%GLOBAL_OptionName%%', '%%GLOBAL_Option%%'), array($optionname, $option), $displayoption);
				} else {
					$formatlist = '<input type="hidden" name="format" value="' . str_replace('f', '', $this->chooseformat) . '" />';
				}
			}
		}

		if ($this->usecaptcha) {
			$alert = GetLang('Form_Javascript_EnterCaptchaAnswer');

			$javascript .= '
				if (f.captcha.value == "") {
					alert("' . $alert . '");
					f.captcha.focus();
					return false;
				}
			';
		}

		$placeholder_lists = '';

		$list_intro_shown = false;

		if (sizeof($this->lists) > 1) {
			if (!class_exists('Lists_API', false)) {
				require_once(dirname(__FILE__) . '/lists.php');
			}
			$lists_api = new Lists_API(0, false);
			$lists_api->Set('Db', $this->Db);
			$listlist = '';

			foreach ($this->lists as $p => $listid) {
				$lists_api->Load($listid);
				$optionname = '';

				if (!$list_intro_shown) {
					$optionname = $notrequiredoption . GetLang('MailingLists') . ':';
					$list_intro_shown = true;
				} else {
					$optionname = '&nbsp;';
				}

				if ($this->formtype == 'm') {
					$placeholder_lists = '%%Lists_' . $listid . '%%';
				}

				$option = '<label for="lists_' . $listid . '"><input type="checkbox" id="lists_' . $listid . '" name="lists[]" value="' . $listid . '"' . $placeholder_lists . ' />&nbsp;' . $lists_api->Get('name') . '</label>';

				$listlist .= str_replace(array('%%GLOBAL_OptionName%%', '%%GLOBAL_Option%%'), array($optionname, $option), $displayoption);

				$alert = GetLang('Form_Javascript_ChooseLists');
				$javascript .= '
					lists_chosen = CheckMultiple' . $this->formid . '(f, "lists");
					if (!lists_chosen) {
						alert("' . $alert . '");
						return false;
					}
				';

			}
		} else {
			$listid = current($this->lists);

			if ($this->formtype == 'm') {
				$placeholder_lists = '%%Lists_' . $listid . '%%';
			}
			$listlist = '<input type="hidden" name="lists" value="' . $listid . '" />';
		}

		$formcontents = '';

		// custom fields is a multidimensional array with list as the key.
		// The subarray contains fields to show for that list.
		// $displayfields = array();
		$displayfields = $this->fieldorder;

		foreach ($this->customfields as $p => $field) {
			if (!in_array($field, $displayfields)) {
				$displayfields[] = $field;
			}
		}

		if (!class_exists('CustomFields_API', false)) {
			require_once(dirname(__FILE__) . '/customfields.php');
		}

		$customfields_api = new CustomFields_API(0, false);
		$customfields_api->Db = $this->Db;

		$shown_list_options = false;

		foreach ($displayfields as $p => $field) {
			if ($field == 'e') {
				$optionname = $requiredoption . GetLang('Form_EmailAddress') . ':';

				$option = '<input type="text" name="email" value="' . $email_placeholder . '" />';
				$formcontents .= str_replace(array('%%GLOBAL_OptionName%%', '%%GLOBAL_Option%%'), array($optionname, $option), $displayoption);
				continue;
			}

			if ($field == 'cl') {
				$shown_list_options = true;
				$formcontents .= $listlist;
				continue;
			}

			if ($this->formtype == 'u') {
				continue;
			}

			if ($field == 'cf') {
				$formcontents .= $formatlist;
				continue;
			}

			$option = '';
			$optionvalue = '';

			$loaded = $customfields_api->Load($field);

			if (!$loaded) {
				continue;
			}

			$subfield = $customfields_api->LoadSubField();

			$javascript .= $subfield->CreateJavascript($this->formid);

			if ($subfield->IsRequired()) {
				$optionname = $requiredoption;
			} else {
				$optionname = $notrequiredoption;
			}
			$optionname .= $subfield->GetFieldName() . ':';
			$option = $subfield->DisplayFieldOptions($customfields_api->Settings['DefaultValue'], true, $this->formid);

			if ($this->formtype == 'm') {
				switch ($subfield->fieldtype) {
					case 'dropdown':
						$option = preg_replace('/<option value="(.*?)">/', "<option value=\"\${1}\"%%CustomField_".$field."_\${1}%%>", $option);
					break;

					case 'checkbox':
						$option = preg_replace('/name="(.*?)" value="(.*?)">/', "name=\"\${1}\" value=\"\${2}\"%%CustomField_".$field."_\${2}%%>", $option);
					break;

					case 'radiobutton':
						$option = preg_replace('/value="(.*?)">/', "value=\"\${1}\"%%CustomField_".$field."_\${1}%%>", $option);
					break;

					case 'date':
						foreach (array('dd', 'mm', 'yy') as $p => $datepart) {
							$match_string = preg_quote('<select name="CustomFields[' . $field . '][' . $datepart . ']"', '%') . '.*?\>(.*?)' . preg_quote('</select>', '%');
							if (preg_match('%'.$match_string.'%i', $option, $matches)) {
								$orig_text = $full_text = $matches[0];

								$full_text = preg_replace('/value="(.*?)">/', "value=\"\${1}\"%%CustomField_" . $field . "_\${1}_" . $datepart . "%%>", $full_text);

								$option = str_replace($orig_text, $full_text, $option);
							}
						}
					break;

					case 'textarea':
						$option = str_replace('</textarea>', '%%CustomField_' . $field . '%%</textarea>', $option);
					break;
										
					case 'number':
						$option = str_replace('value="0"', 'value="%%CustomField_' . $field . '%%"', $option);
					break;

					default:
						$option = str_replace('value=""', 'value="%%CustomField_' . $field . '%%"', $option);
					break;
				}
			}

			$formcontents .= str_replace(array('%%GLOBAL_OptionName%%', '%%GLOBAL_Option%%'), array($optionname, $option), $displayoption);
		}

		if ($this->formtype == 'm' && !$shown_list_options) {
			$formcontents .= $listlist;
		}

		switch ($this->formtype) {
			case 's':
				$formaction = SENDSTUDIO_APPLICATION_URL . '/form.php?form=' . $this->formid;
			break;
			case 'u':
				$formaction = SENDSTUDIO_APPLICATION_URL . '/unsubform.php?form=' . $this->formid;
			break;

			case 'f':
			case 'm':
				/**
				* We don't hardcode the form action in case we are generating the form. Since a modify details form stays inside sendstudio, we don't want to hardcode the url (instead it's generated by the modifydetails.php file).
				* Why? In case we change the url - we don't want to have to change database values at the same time.
				*/
				$formaction = '%%FORMACTION%%';
			break;
			default:
				$formaction = false;
		}

		if (!in_array('cf', $displayfields)) {
			$formcontents .= $formatlist;
		}

		if (!class_exists('captcha_api', false)) {
			require_once(dirname(__FILE__) . '/captcha.php');
		}

		$captcha_api = new Captcha_API($inside_sendstudio);

		if ($this->usecaptcha) {
			$optionname = $requiredoption . GetLang('Form_EnterCaptcha') . ':';

			if ($this->formtype == 'm') {
				$option = '%%captchaimage%%';
			} else {
				$option = $captcha_api->ShowCaptcha();
			}

			$option .= '<br/><input type="text" name="captcha" value="" />';

			$formcontents .= str_replace(array('%%GLOBAL_OptionName%%', '%%GLOBAL_Option%%'), array($optionname, $option), $displayoption);
		}

		$javascript .= '
				return true;
			}
		';

		$content = str_replace(array('%%FormContents%%', '%%FormAction%%', '%%FormID%%', '%%Javascript%%'), array($formcontents, $formaction, $this->formid, $javascript), $content);

		$content = $sendstudio_functions->ReplaceLanguageVariables($content);
		return $content;
	}


	/**
	* GetPage
	* Gets a page from the current loaded form.
	* This can be used to fetch (for example) the confirmation page, or error page.
	* This is used both in the admin area and by the forms on the frontend.
	*
	* @param String $pagename The page you want to fetch. This can be confirmpage, errorpage, thankspage
	* @param String $area The area you want to fetch. For example the confirmpage URL. If this is not passed in, the whole page is returned. This includes the sendfrom details, the html & text content for the page and so on.
	*
	* @see form.php
	* @see confirm.php
	* @see unsubform.php
	*
	* @return False|String|Array Returns false if the pagename doesn't exist. If you are trying to fetch the url and it's only the http:// prefix (ie not a valid url) then this also returns false. Finally, if the area is specified, this is checked and if not present will return false. If the area is specified, only that particular item is returned. If the area is not specified, then this will return the whole page as an array.
	*/
	function GetPage($pagename=false, $area=false)
	{
		if (!$pagename) {
			return false;
		}

		if (!isset($this->pages[$pagename])) {
			return false;
		}

		if ($area) {
			if (!isset($this->pages[$pagename][$area])) {
				return false;
			}

			if (strtolower($area) == 'url') {
				if ($this->pages[$pagename]['url'] == 'http://') {
					return false;
				}
			}
			return $this->pages[$pagename][$area];
		}
		return $this->pages[$pagename];
	}

}
