<?php
/**
 * Folders API.
 *
 * @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
 *
 * @package API
 * @subpackage Folders_API
 */

/**
 * Include the base API class if we haven't already.
 */
require_once(dirname(__FILE__) . '/api.php');

/**
 * Thge Folders API provides a way to manipulate the persistent
 * storage of item->folder mappings.
 *
 * @package API
 * @subpackage Folders_API
 */
class Folders_API extends API
{
	/**
	 * A mapping of readable folder types to the single character versions
	 * needed for database queries.
	 *
	 * @see GetValidType
	 */
	private $_TYPES = array(
		'list' => 'l',
		'campaign' => 'c'
		);

	/**
	 * __construct
	 * Folders_Api constructor
	 *
	 * @return Void Does not return anything.
	 */
	public function __construct()
	{
		$this->GetDb();
	}

	/**
	 * CreateFolder
	 * Creates a new folder.
	 *
	 * @param Int $owner_id The User ID of the owner.
	 * @param String $folder_type The type of folder.
	 * @param String $folder_name The name of the folder.
	 *
	 * @see $_TYPES
	 *
	 * @return Int|Boolean The ID of the new folder or false if the name clashes.
	 */
	public function CreateFolder($owner_id, $folder_type, $folder_name)
	{
		$folder_type = $this->GetValidType($folder_type);
		if (!$folder_type) {
			return false;
		}
		if ($this->_FolderExists($owner_id, $folder_type, $folder_name)) {
			return false;
		}
		$createdate = AdjustTime();
		$query = "INSERT INTO [|PREFIX|]folders (name, type, createdate, ownerid) VALUES
		('" . $this->Db->Quote($folder_name) . "', '" . $this->Db->Quote($folder_type) . "', " . intval($createdate) . ", " . intval($owner_id) . ")";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		return $this->Db->LastId('[|PREFIX|]folders_sequence');
	}

	/**
	 * RemoveFolders
	 * Deletes folders and all other information connected to them, orphaning
	 * any items within them.
	 *
	 * @param Int|Array $folder_ids The IDs of the folder.
	 *
	 * @return Boolean False on error, otherwise true.
	 */
	public function RemoveFolders($folder_ids)
	{
		if (!is_array($folder_ids)) {
			$folder_ids = array($folder_ids);
		}
		$folder_ids = array_map('intval', $folder_ids);
		$prefix = "DELETE FROM [|PREFIX|]";
		$delete_queries = array();
		$delete_queries[] = "folder_item WHERE folderid IN (" . implode(',', $folder_ids) . ")";
		$delete_queries[] = "folder_user WHERE folderid IN (" . implode(',', $folder_ids) . ")";
		$delete_queries[] = "folders WHERE folderid IN (" . implode(',', $folder_ids) . ")";
		$this->Db->StartTransaction();
		foreach ($delete_queries as $query) {
			$query = $prefix . $query;
			$result = $this->Db->Query($query);
			if (!$result) {
				$this->Db->RollbackTransaction();
				return false;
			}
		}
		$this->Db->CommitTransaction();
		return true;
	}

	/**
	 * RenameFolder
	 * Changes the name of a folder.
	 *
	 * @param Int $folder_id The ID of the folder.
	 * @param String $new_name What the folder should be renamed to.
	 *
	 * @return Boolean False on error, otherwise true.
	 */
	public function RenameFolder($folder_id, $new_name)
	{
		$query = "UPDATE [|PREFIX|]folders SET name = '" . $this->Db->Quote($new_name) . "' WHERE folderid = " . intval($folder_id);
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * GetFolderList
	 * Returns an array of folders along with a list of item IDs that belong
	 * to them. Items that don't belong in any folders will be put under
	 * folder ID 0.
	 *
	 * @param String $folder_type The type of folder.
	 * @param Int $user_id The ID of the user who owns the folder.
	 * @param Array $sort_info What field to sort on and which direction.
	 *
	 * @return Mixed False on error, otherwise an associative array with the folder ID as the key.
	 */
	public function GetFolderList($folder_type, $user_id, $sort_info)
	{
		$folder_type = $this->GetValidType($folder_type);
		if (!$folder_type) {
			return false;
		}
		// Find the items that aren't in any folder.
		$rows[0] = array(
			'name' => 'Uncategorised', // this can be overwritten with a language token elsewhere
			'expanded' => 1,
			'ordering' => 0,
			'items' => $this->_GetOrphanItems($folder_type, $user_id),
			);
		// Query for the items that are in folders.
		// Only sort folders by name, but the direction is choosable.
		$folder_sort_direction = 'ASC';
		if ($sort_info['SortBy'] == 'name') {
			$folder_sort_direction = $sort_info['Direction'];
		}
		$query = "SELECT f.*, i.itemid, u.userid, u.expanded, u.ordering FROM ([|PREFIX|]folders f LEFT JOIN [|PREFIX|]folder_item i ON f.folderid = i.folderid) LEFT JOIN [|PREFIX|]folder_user u ON f.folderid = u.folderid WHERE f.ownerid = " . intval($user_id) . " AND f.type='" . $folder_type . "' AND f.ownerid = " . intval($user_id) . " ORDER BY f.name " . $folder_sort_direction;
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		// Load these items into an array.
		while (($row = $this->Db->Fetch($result))) {
			$fid = $row['folderid'];
			if (!isset($rows[$fid])) {
				$rows[$fid] = array(
					'name' => $row['name'],
					'ordering' => $row['ordering'],
					'expanded' => $row['expanded'],
					'items' => array(),
				);
			}
			if (!empty($row['itemid'])) {
				$rows[$fid]['items'][$row['itemid']] = $row['itemid'];
			}
		}
		return $rows;
	}

	/**
	 * OwnsFolders
	 * Checks if a given user owns a set of folders.
	 *
	 * @param Int $folder_id The ID of the folder.
	 * @param Int $user_id The ID of the user.
	 *
	 * @return Boolean True if the user is the owner, otherwise false.
	 */
	public function OwnsFolders($folder_ids, $user_id)
	{
		if (!is_array($folder_ids)) {
			$folder_ids = array($folder_ids);
		}
		$folder_ids = array_map('intval', $folder_ids);
		$query = "SELECT ownerid FROM [|PREFIX|]folders WHERE folderid IN (" . implode(',', $folder_ids) . ") AND ownerid <> " . intval($user_id);
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		return !($this->Db->CountResult($result));
	}

	/**
	 * ExpandFolder
	 * Expands a folder for a given user (shows its contents).
	 *
	 * @param Int $folder_id The ID of the folder.
	 * @param Int $user_id The ID of the user.
	 *
	 * @uses _CreateFolderView
	 * @uses _SetFolderView
	 * @see CollapseFolder
	 *
	 * @return Boolean True if the operation was successful, otherwise false.
	 */
	public function ExpandFolder($folder_id, $user_id)
	{
		if (!$this->_CreateFolderView($folder_id, $user_id, 1)) {
			return $this->_SetFolderView($folder_id, $user_id, 1);
		}
		return true;
	}

	/**
	 * CollapseFolder
	 * Collapses a folder for a given user (hides its contents).
	 *
	 * @param Int $folder_id The ID of the folder.
	 * @param Int $user_id The ID of the user.
	 *
	 * @uses _CreateFolderView
	 * @uses _SetFolderView
	 * @see ExpandFolder
	 *
	 * @return Boolean True if the operation was successful, otherwise false.
	 */
	public function CollapseFolder($folder_id, $user_id)
	{
		if (!$this->_CreateFolderView($folder_id, $user_id, 0)) {
			return $this->_SetFolderView($folder_id, $user_id, 0);
		}
		return true;
	}


	/**
	 * SaveItemsToFolders
	 * Removes all items from the given folders and adds in all the items
	 * passed in to their respective folder.
	 *
	 * @param Array $folder_map An array mapping the folder IDs to the item IDs.
	 *
	 * @return Boolean True if the items were added to the folders, otherwise false.
	 */
	public function SaveItemsToFolders($folder_map)
	{
		$pairs = array();
		$fids = array();
		foreach ($folder_map as $folder) {
			$fid = intval($folder['id']);
			if ($fid == 0) {
				continue;
			}
			$fids[] = $fid;
			if (isset($folder['children'])) {
				foreach ($folder['children'] as $item) {
					$pairs[] = "(" . $fid . ", " . intval($item['id']) . ")";
				}
			}
		}
		$this->Db->StartTransaction();
		// remove all items from folders in question
		if (count($fids)) {
			$query = "DELETE FROM [|PREFIX|]folder_item WHERE folderid IN (" . implode(',', $fids) . ")";
			$result = $this->Db->Query($query);
			if (!$result) {
				$this->Db->RollbackTransaction();
				return false;
			}
		}
		// insert the items in again
		if (count($pairs)) {
			$query = "INSERT INTO [|PREFIX|]folder_item (folderid, itemid) VALUES " . implode(',', $pairs);
			$result = $this->Db->Query($query);
			if (!$result) {
				$this->Db->RollbackTransaction();
				return false;
			}
		}
		$this->Db->CommitTransaction();
		return true;
	}

	/**
	 * GetValidType
	 * Returns the folder type name suitable for the database query.
	 *
	 * @param String $type The type of folder
	 *
	 * @see $_TYPES
	 *
	 * @return String|Boolean The single-character version of the folder type, or false if invalid.
	 */
	public function GetValidType($type)
	{
		$type = strtolower($type);
		if (in_array($type, $this->_TYPES)) {
			return $type;
		}
		if (in_array($type, array_keys($this->_TYPES))) {
			return $this->_TYPES[$type];
		}
		return false;
	}

	// Private methods

	/**
	 * _FolderExists
	 * Checks if a user already has a folder of a given name and type.
	 *
	 * @param Int $owner_id The User ID of the owner.
	 * @param String $folder_type The type of folder (see $_TYPES).
	 * @param String $folder_name The name of the folder.
	 *
	 * @return Boolean True if the folder already exists, otherwise false.
	 */
	private function _FolderExists($owner_id, $folder_type, $folder_name)
	{
		$query = "SELECT * FROM [|PREFIX|]folders WHERE ownerid = " . intval($owner_id) . " AND type = '" . $this->Db->Quote($folder_type) . "' AND name = '" . $this->Db->Quote($folder_name) . "'";
		$result = $this->Db->Query($query);
		if (!$result) {
			list($error, $level) = $this->Db->GetError();
			//trigger_error($error, $level);
		}
		
	}

	/**
	 * _CreateFolderView
	 * Changes the expanded state of a folder for a user. This is used when
	 * the folder has not had its view settings set for the given user before.
	 *
	 * @param Int $folder_id The ID of the folder.
	 * @param Int $user_id The ID of the user.
	 *
	 * @uses _SetFolderView
	 * @see ExpandFolder
	 * @see CollapseFolder
	 *
	 * @return Boolean True if the operation was successful, otherwise false.
	 */
	private function _CreateFolderView($folder_id, $user_id, $expanded)
	{
		$query = "INSERT INTO [|PREFIX|]folder_user (folderid, userid, expanded) VALUES (" . intval($folder_id) . ", " . intval($user_id) . ", " . intval($expanded) . ")";
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * _SetFolderView
	 * Changes the expanded state of a folder for a user. This is used when a
	 * folder's view settings for a user already exist.
	 *
	 * @param Int $folder_id The ID of the folder.
	 * @param Int $user_id The ID of the user.
	 *
	 * @uses _CreateFolderView
	 * @see ExpandFolder
	 * @see CollapseFolder
	 *
	 * @return Boolean True if the operation was successful, otherwise false.
	 */
	private function _SetFolderView($folder_id, $user_id, $expanded)
	{
		$query = "UPDATE [|PREFIX|]folder_user SET expanded = " . intval($expanded) . " WHERE folderid = " . intval($folder_id) . " AND userid = " . intval($user_id);
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		return true;
	}

	/**
	 * _GetOrphanItems
	 * Fetches the items of a certain type that aren't in a folder.
	 *
	 * @param String $folder_type The type of folder.
	 *
	 * @return Array A list of item IDs.
	 */
	private function _GetOrphanItems($folder_type, $owner_id)
	{
		$folder_type = $this->GetValidType($folder_type);
		if (!$folder_type) {
			return false;
		}
		switch ($folder_type) {
			case 'l':
				list($table, $field, $order) = array('lists', 'listid', 'name');
				break;
			case 'c':
				list($table, $field, $order) = array('newsletters', 'newsletterid', 'name');
				break;
			default:
				return false;
		}
		// query for the items that aren't in folders
		$query = "SELECT " . $field . " FROM [|PREFIX|]" . $table . " t WHERE t.listid NOT IN (SELECT i.itemid FROM [|PREFIX|]folders f, [|PREFIX|]folder_item i WHERE f.folderid = i.folderid AND f.type = '" . $folder_type . "' AND f.ownerid = " . intval($owner_id) . ") ORDER BY t." . $order;
		$result = $this->Db->Query($query);
		if (!$result) {
			return false;
		}
		// load these items into an array
		$rows = array();
		while (($row = $this->Db->Fetch($result))) {
			$rows[] = $row[$field];
		}
		return $rows;
	}

}
