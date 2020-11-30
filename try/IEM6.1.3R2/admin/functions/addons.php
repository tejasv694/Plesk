<?php
/**
* This file has the addon handling functionality in it.
* All this file does is check the user has permissions to work with a particular addon,
* then calls the addon to do the rest of the work.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sendstudio_functions.php');

/**
* Class for handling, processing and calling addons.
* It also checks a user has permissions before doing anything with the addon.
* Once that has been done, control of what happens (actions, what gets displayed etc)
* is given to the addon itself. This class does nothing else.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Addons extends SendStudio_Functions
{

	/**
	 * __construct
	 * There is nothing for the constructor to do.
	 *
	 * @return Void Does nothing.
	 */
	function __construct()
	{
	}

	/**
	 * Process
	 * Process does the basic work to figure out:
	 * - which addon you're trying to access (and whether it exists & is active/enabled)
	 * - whether you have access to perform that action
	 * - whether to print a header/footer or not
	 *
	 * Then hands control over to the addon itself to do the rest.
	 *
	 * @uses HasAccess
	 * @uses Interspire_Addons
	 * @uses Interspire_Addons::GetAvailableAddons
	 * @uses Interspire_Addons::Process
	 *
	 * @return Void Doesn't return anything.
	 */
	function Process()
	{
		// if we're not viewing an addon, then show an "access denied" message
		// there's no way to view a list of addons to run
		// they all have to appear in a menu somewhere.
		if (!isset($_GET['Addon'])) {
			$this->DenyAccess();
			return;
		}

		/**
		 * print_header tells us whether to print the header/footer at all.
		 */
		$print_header = true;

		/**
		 * popup_header tells us whether it's a popup window or not.
		 * If it's a full window, that includes the nav menus etc.
		 * If it's a popup window, then none of that is shown.
		 */
		$popup_header = false;

		$get_keywords = array_map('strtolower', array_keys($_GET));

		/**
		 * See if the 'ajax' keyword is used in the get string anywhere as a key.
		 * If it is, don't show the header/footer.
		 */
		if (in_array('ajax', $get_keywords)) {
			$print_header = false;
		}

		/**
		 * See if the 'popup' keyword is used in the get string anywhere as a key.
		 * If it is, show the popup header/footer.
		 */
		if (in_array('popup', $get_keywords)) {
			$popup_header = true;
		}

		if ($print_header) {
			$this->PrintHeader($popup_header);
		}

		$addon = strtolower($_GET['Addon']);

		require_once(SENDSTUDIO_BASE_DIRECTORY . DIRECTORY_SEPARATOR . 'addons' . DIRECTORY_SEPARATOR . 'interspire_addons.php');
		$addon_system = new Interspire_Addons();
		$addons = $addon_system->GetAvailableAddons();

		if (!isset($addons[$addon])) {  
			$this->DenyAccess();
			if ($print_header) {
				$this->PrintFooter($popup_header);
			}
			return;
		}

		$action = 'Default';
		if (isset($_GET['Action']) && $_GET['Action'] !== '') {
			$action = $_GET['Action'];
		}

		/**
		* Check the user has access to this addon and what you are trying to do.
		* The first argument is always the addon id (eg 'surveys').
		*
		* The second argument is the action you are trying to perform.
		* eg 'create', 'edit', 'delete', 'stats'
		* This must match up to the methods in the addon itself
		* eg if you're trying to get the addon to create something, the permission is 'create' and the addon method must be 'Admin_Create_Action'
		*
		*/
		$user = IEM::GetCurrentUser();
		$admin_action = $action;
		if ($admin_action == 'Default') {
			$admin_action = null;
		}
		
		if(!is_null($admin_action)){$admin_action = strtolower($admin_action);}
		// Survey permission addon... added in the group now
		foreach ($user->group->permissions as $perm_key => &$perm ) {
			if ($perm_key == 'surveys') {
				$perm[] = "submit";
				$perm[] = "tinymcesurveylist";

				foreach ($perm as $perm_action) {
					switch ($perm_action):
						case 'create':
							$perm[] = "build";
							$perm[] = "save";
							break;
						case 'viewresponsesdefault':
							$perm[] = "viewresponses";
							$perm[] = "downloadattach";
							break;
						case 'editresponse':
							$perm[] = "saveresponse";
							$perm[] = "downloadattach";
						case 'exportdefault':
							$perm[] = "export";
						case 'resultdefault':
							$perm[] = "result";
							$perm[] = "result_responseslist";
							break;
					endswitch;
				}
			}
		}
		
		// Check if addon is enabled or disabled
		if (!$addon_system->isEnabled($addon)) {
				$this->DenyAccess();
		}
               
		if (!is_null($admin_action)) {  
            $access = $user->HasAccess($addon, $admin_action);
			if(!$access){
		        $this->DenyAccess();
                if ($print_header) {$this->PrintFooter($popup_header);}
                return;
            }
			
		} else {  
            $access = $user->HasAccess($addon); 
			if(!$access){
		        $this->DenyAccess();
                if ($print_header) {$this->PrintFooter($popup_header);}
                return;	
            }
				  
		}
              
		try {
            if(is_null($action)){$action = "Default";}
			$result = Interspire_Addons::Process($addon, 'Admin_Action_'.$action);
			echo $result;
		} catch (Exception $e) {
			echo "Error!: " . $e->getMessage();
		}

		if ($print_header) {
			$this->PrintFooter($popup_header);
		}
	}
}
