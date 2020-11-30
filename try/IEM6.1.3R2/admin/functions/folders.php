<?php
/**
* This file contains common functions for managing Folders.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for handling Folders.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Folders extends SendStudio_Functions
{

	/**
	* Constructor
	* Loads the language file.
	*
	* @see LoadLanguageFile
	*
	* @return Void Doesn't return anything.
	*/
	public function __construct()
	{
		$this->LoadLanguageFile();
	}

	/**
	 * PrintHeader
	 * Prints HTTP headers for this page.
	 *
	 * @return Void Doesn't return anything.
	 */
	public function PrintHeader()
	{
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

	/**
	* Process
	* Handles AJAX requests and Thickbox generation.
	*
	* @uses GetUser
	* @uses _DoAjaxRequest
	* @uses _getGETRequest
	* @uses _getPOSTRequest
	*
	* @return Void Doesn't return anything.
	*/
	public function Process()
	{
		$this->PrintHeader();

		$user = GetUser();

		if (!isset($_GET['Action'])) {
			return;
		}
		$req_action = strtolower($_GET['Action']);
		$response = '';

		switch ($req_action) {
			case 'ajax':
				header("Content-type: application/json");
				$response = $this->_DoAjaxRequest($user->Get('userid'));
				break;
			case 'add':
			case 'remove':
			case 'rename':
				$GLOBALS['CHARSET'] = SENDSTUDIO_DEFAULTCHARSET;
				$GLOBALS['FolderOperation'] = $req_action;
				$GLOBALS['FolderType'] = $this->_getGETRequest('FolderType', null);
				$GLOBALS['FolderID'] = $this->_getGETRequest('FolderID', null);
				$GLOBALS['FolderName'] = $this->_getGETRequest('FolderName', null);
				$this->ParseTemplate('folder_operation');
				return true;
				break;
		}
	}

	/**
	* SetFolderMode
	* Sets the viewing mode to either the normal non-folder list mode or
	* folder mode.
	*
	* @param String $mode Either 'list' (normal) or 'folder'.
	*
	* @uses GetPageName
	* @uses GetUser
	* @uses User_API::GetSettings
	* @uses User_API::SetSettings
	*
	* @return Boolean True if the setting was saved, otherwise false.
	*/
	public function SetFolderMode($mode)
	{
		$mode = strtolower($mode);

		if (!in_array($mode, array('list', 'folder'))) {
			return false;
		}

		$user = IEM::userGetCurrent();
		$page = $this->GetPageName();

		$display_settings = $user->GetSettings('DisplaySettings');
		if (!isset($display_settings['FolderMode']) || !is_array($display_settings['FolderMode'])) {
			$display_settings['FolderMode'] = array();
		}
		$display_settings['FolderMode'][$page] = $mode;

		$user->SetSettings('DisplaySettings', $display_settings);
		$user->SaveSettings();

		return true;
	}

	/**
	* IsFolderMode
	* Informs whethe the current user is viewing records in folder mode for
	* this page or not.
	*
	* @uses GetPageName
	* @uses GetUser
	* @uses User_API::GetSettings
	*
	* @return Boolean Returns true if the user's display settings are set to
	* show the current page in Folder Mode, otherwise false.
	*/
	public function InFolderMode()
	{
		$user = GetUser();
		$page = $this->GetPageName();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (isset($display_settings['FolderMode'][$page])) {
			$mode = $display_settings['FolderMode'][$page];
			return ($mode == 'folder');
		}
		return false;
	}

	/**
	 * OrphanExpanded
	 * Gets the expanded/collapsed state of the 'orphan' or 'uncategorised'
	 * folder.
	 *
	 * @param String $folder_type The type of folder.
	 *
	 * @uses GetUser
	 * @uses User_API::GetSettings
	 *
	 * @return Boolean True if the folder is expanded, otherwise false.
	 */
	public function IsOrphanExpanded($folder_type)
	{
		$Folders_Api = $this->GetApi('Folders');
		$folder_type = $Folders_Api->GetValidType($folder_type);
		$user = GetUser();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (isset($display_settings['FolderOrphan'][$folder_type]['expanded'])) {
			return ($display_settings['FolderOrphan'][$folder_type]['expanded'] == 1);
		}
		return true;
	}

	/**
	 * _SetOrphanFolder
	 * Sets the expanded/collapsed state of the 'orphan' or 'uncategorised'
	 * folder.
	 *
	 * @param String $folder_type The type of folder.
	 * @param Int $expanded 1 to expand, 0 to collapse.
	 *
	 * @uses GetUser
	 * @uses User_API::GetSettings
	 * @uses User_API::SetSettings
	 *
	 * @return Boolean True if the operation succeeded, otherwise false.
	 */
	private function _SetOrphanFolder($folder_type, $expanded)
	{
		$Folders_Api = $this->GetApi('Folders');
		$folder_type = $Folders_Api->GetValidType($folder_type);
		if (!$folder_type) {
			return false;
		}
		$user = GetUser();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (!isset($display_settings['FolderOrphan']) || !is_array($display_settings['FolderOrphan'])) {
			$display_settings['FolderOrphan'] = array();
		}
		if (!isset($display_settings['FolderOrphan'][$folder_type]) || !is_array($display_settings['FolderOrphan'][$folder_type])) {
			$display_settings['FolderOrphan'][$folder_type] = array();
		}
		$display_settings['FolderOrphan'][$folder_type]['expanded'] = $expanded;
		$user->SetSettings('DisplaySettings', $display_settings);
		return true;
	}

	/**
	 * _DoAjaxRequest
	 * Return an AJAX request based on the request type
	 *
	 * @param Array $parameter Any parameters that need to be parsed to this function
	 *
	 * @uses GetJSON()
	 *
	 * @return String Returns response string that can be outputted to the browser
	 */
	private function _DoAjaxRequest($user_id)
	{
		$Folders_Api = $this->GetApi('Folders');

		// if we're re-ordering then PlaceholderSortable will be set
		$order = $this->_getPOSTRequest('PlaceholderSortable', null);
		if (is_array($order)) {
			// grab all the folder IDs involved
			$folder_ids = array();
			foreach ($order as $folder) {
				$fid = intval($folder['id']);
				// The folder with ID 0 is the 'Uncategorised' special folder
				// and does not need ordering information saved.
				if ($fid == 0) {
					continue;
				}
				$folder_ids[] = $fid;
			}
			// permission check
			if (!$Folders_Api->OwnsFolders($folder_ids, $user_id)) {
				return $this->_fail();
			}
			// save which items are in which folders
			if (!$Folders_Api->SaveItemsToFolders($order)) {
				return $this->_fail();
			}
			return $this->_succeed();
		}

		// If we're not re-ordering, we need to look at $request_type to see what to do
		$request_type = $this->_getPOSTRequest('AjaxType', null);
		if (is_null($request_type)) {
			return $this->_fail();
		}

		$folder_id = $this->_getPOSTRequest('folder_id', null);
		$folder_name = $this->_getPOSTRequest('folder_name', null);
		$folder_type = $this->_getPOSTRequest('folder_type', null);

		if (!$Folders_Api->OwnsFolders($folder_id, $user_id)) {
			return $this->_fail();
		}

		// jQuery will only submit UTF-8 data via ajax, so if we're using some kind of single-byte encoding we need to decode it.
		// Note that if we're using a different multi-byte encoding besides UTF-8, this will be broken.
		if (SENDSTUDIO_CHARSET != 'UTF-8') {
			$folder_name = utf8_decode($folder_name);
		}

		switch ($request_type) {
			case 'Expand':
				if (is_null($folder_id)) {
					return $this->_fail();
				} else if ($folder_id == 0) {
					if (!$this->_SetOrphanFolder($folder_type, 1)) {
						return $this->_fail();
					}
				} else if (!$Folders_Api->ExpandFolder($folder_id, $user_id)) {
					return $this->_fail();
				}
				return $this->_succeed();
			case 'Collapse':
				if (is_null($folder_id)) {
					return $this->_fail();
				} else if ($folder_id == 0) {
					if (!$this->_SetOrphanFolder($folder_type, 0)) {
						return $this->_fail();
					}
				} else if (!$Folders_Api->CollapseFolder($folder_id, $user_id)) {
					return $this->_fail();
				}
				return $this->_succeed();
			case 'Add':
				if (empty($folder_name) || is_null($folder_type)) {
					return $this->_fail(GetLang('Folders_FolderNameNotEmpty'));
				}
				if (!$Folders_Api->CreateFolder($user_id, $folder_type, $folder_name)) {
					return $this->_fail(GetLang('Folders_NameConflict'));
				}
				return $this->_succeed();
			case 'Remove':
				if (is_null($folder_id)) {
					return $this->_fail();
				}
				if (!$Folders_Api->RemoveFolders($folder_id)) {
					return $this->_fail();
				}
				return $this->_succeed();
			case 'Rename':
				if (is_null($folder_id) || empty($folder_name)) {
					return $this->_fail(GetLang('Folders_FolderNameNotEmpty'));
				}
				if (!$Folders_Api->RenameFolder($folder_id, $folder_name)) {
					return $this->_fail(GetLang('Folders_NameConflict'));
				}
				return $this->_succeed();
			default:
				return $this->_fail();
		}
	}

	/**
	 * _fail
	 * Return a JSON-formatted failure status message.
	 *
	 * @return Void Doesn't return anything.
	 */
	private function _fail($msg = null)
	{
		$response = array('status'=>'Failed');
		if (!is_null($msg)) {
			$response['message'] = $msg;
		}
		echo GetJSON($response) . "\n";
	}

	/**
	 * _succeed
	 * Return a JSON-formatted success status message.
	 *
	 * @return Void Doesn't return anything.
	 */
	private function _succeed()
	{
		echo GetJSON(array('status'=>'OK')) . "\n";

	}
}
