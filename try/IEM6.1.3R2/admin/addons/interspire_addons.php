<?php
/**
 * This file contains the base class for all addons to do their work.
 * Addons must extend this class as it contains basic functionality they will require.
 *
 * @package Interspire_Addons
 */

/**
 * Interspire_Addons
 * This is the base class for all addons to work off.
 * It handles:
 * - installing & uninstalling addons
 * - upgrading addons
 * - enabling & disabling addons
 * - running addons
 * - finding/loading addons including their language files and description.xml files
 *
 * The description.xml file is used by the calling code to work out
 * - the name of the addon
 * - a short description
 * - the current version number
 * - the author
 * - license details
 *
 * @uses Interspire_Addons_Exception
 */
class Interspire_Addons
{

	/**
	 * The applications url is stored here.
	 * This is then passed to child addons which can use other variables based around this one
	 *
	 * @see template_url
	 * @see admin_url
	 * @see settings_url
	 * @see application_url
	 *
	 * @usedby SetUrl
	 * @usedby Process
	 *
	 * @static $url stores the url which is then passed to child addons when the Process method is invoked
	 */
	static protected $url = '';

	/**
	 * The template_url is the url to get to the addon/{addon_name}/template/ folder
	 * This can be used for absolute links to images, stylesheets etc that a particular addon will need
	 * It always ends with a / so all you need to add is the folder and filename.
	 *
	 * <code>
	 * echo $this->template_url . 'images/logo.png';
	 * </code>
	 *
	 * @uses url
	 * @uses _set_url
	 *
	 * @var String
	 */
	protected $template_url = '';

	/**
	 * This provides an easy way to get to the template folder for an addon.
	 * It is set to
	 *
	 * <code>
	 * $this->addon_base_directory . $this->addon_id . '/templates/';
	 * </code>
	 *
	 * @uses addon_base_directory
	 * @uses addon_id
	 * @see __construct
	 */
	protected $addon_template_directory = '';

	/**
	 * The admin_url is the url to run the addon through the admin control panel.
	 * It will automatically add the page name and addon name to the url.
	 * All you have to do is give it an action
	 *
	 * <code>
	 * echo $this->admin_url . '&Action=Default';
	 * </code>
	 *
	 * @uses url
	 * @uses _set_url
	 *
	 * @var String
	 */
	protected $admin_url = '';

	/**
	 * The settings_url is the url to use when doing from the settings area, eg enabling/disabling, or for the settings form.
	 *
	 * <code>
	 * echo $this->settings_url . '&Action=SaveSettings';
	 * </code>
	 *
	 * @uses url
	 * @uses _set_url
	 *
	 * @var String
	 */
	protected $settings_url = '';

	/**
	 * The application_url is used to access the application (eg you want to include the app's stylesheet or reference it's logo image).
	 * This is the same as the url passed in to SetUrl except it always has a trailing slash on the end.
	 *
	 * <code>
	 * echo $this->application_url . 'images/logo.png';
	 * </code>
	 *
	 * @uses url
	 * @uses _set_url
	 *
	 * @var String
	 */
	protected $application_url = '';

	/**
	 * addon_base_directory is the base full path to the addons folder.
	 * This is just a helper variable to make it easier to use full paths instead of using dirname(__FILE__) all the time.
	 * It has the DIRECTORY_SEPARATOR on the end.
	 *
	 * @see __construct
	 *
	 * @var String
	 */
	protected $addon_base_directory = null;

	/**
	 * addon_id is the addon's id.
	 * This is based on the folder name we found the addon in.
	 * An addon can specify it's own id if it needs to.
	 * This is most useful when an addon is split into multiple files
	 * where each file handles specific areas for the addon.
	 *
	 * @see __construct
	 * @see GetId
	 *
	 * @var String
	 */
	protected $addon_id = null;

	/**
	 * addon_loaded is a flag which determines whether the addon has been 'loaded' and the language files etc have been loaded.
	 * This is used by Load() to see whether to do work or not.
	 * This means you can call Load() safely and it won't cause errors if it has already been loaded.
	 *
	 * @see Load
	 *
	 * @var Boolean
	 */
	protected $addon_loaded = false;

	/**
	 * description holds an array of data including
	 * - addon_id
	 * - addon description (eg 'this addon allows you to do x,y,z')
	 * - version number
	 * - license
	 * - license url
	 * - author
	 *
	 * @var Array
	 */
	protected $description = array();

	/**
	 * This is used to store menu locations for addons.
	 * When RegisterMenuItem is called, it stores the details in this array for GetMenuItems to return.
	 *
	 * @see RegisterMenuItem
	 * @see GetMenuItems
	 *
	 * @var Array
	 */
	static private $menuItems = array();

	/**
	 * This is used to store permissions for addons.
	 * When RegisterAddonPermission is called, the details are stored in this array for GetAddonPermissions to return.
	 *
	 * @see RegisterAddonPermission
	 * @see GetAddonPermissions
	 *
	 * @var Array
	 */
	static private $userPermissions = array();

	/**
	 * db Holds a local database object for easy use
	 *
	 * @see GetDb
	 *
	 * @var Object
	 */
	protected $db = null;

	/**
	 * template_system Holds a local template system object for easy use
	 *
	 * @see GetTemplateSystem
	 *
	 * @var Object
	 */
	protected $template_system = null;

	/**
	 * Whether the addon is installed or not.
	 *
	 * It is false by default (meaning the addon needs to be installed).
	 *
	 * @see Install
	 * @see UnInstall
	 * @see Load
	 *
	 * @var Boolean
	 */
	protected $installed = false;

	/**
	 * Whether the addon is configured or not.
	 * If an addon has no settings, then this can be set to true in the child addon class to bypass the need to configure the addon.
	 *
	 * It is false by default (meaning the addon needs to be configured before it can be enabled & used).
	 *
	 * @see Install
	 * @see UnInstall
	 * @see Load
	 *
	 * @var Boolean
	 */
	protected $configured = false;

	/**
	 * Whether the addon is enabled or not.
	 * If an addon has no configuration settings, then it can be enabled automatically when it's installed.
	 *
	 * It is false by default (meaning the addon needs to be enabled before it can used).
	 *
	 * @see Install
	 * @see UnInstall
	 * @see Load
	 *
	 * @var Boolean
	 */
	protected $enabled = false;

	/**
	 * The addon version number. This is used to work out if an addon needs upgrading or not.
	 *
	 * An addon can have minor versions (eg 1.5).
	 *
	 * @var Float
	 */
	protected $addon_version = 0;

	/**
	 * The addon's default settings.
	 * This can be used to set default options when the addon is installed the first time.
	 *
	 * <code>
	 * $this->default_settings = array ('option_1' => 'value 1', 'option_2' => 'value 2');
	 * </code>
	 *
	 * @var Array
	 */
	protected $default_settings = array();

	/**
	 * The addon's settings.
	 * This is serialized when the addon is configured & saved
	 * and then unserialized when it's loaded up.
	 *
	 * <code>
	 * $this->settings = array ('option_1' => 'value 1', 'option_2' => 'value 2');
	 * </code>
	 *
	 * @see Load
	 * @see Save
	 *
	 * @var Array
	 */
	protected $settings = array();

	/**
	 * Constructor
	 * This sets the addon_base_directory class variable and also the addon_id class variable for easy use.
	 *
	 * It grabs the database object for easy access as well as the template system.
	 *
	 * @param Object $db An optional database resource to pass in.
	 *
	 * @see addon_base_directory
	 * @see addon_id
	 * @see addon_template_directory
	 * @uses Interspire_Addons::GetDb
	 * @uses Interspire_Addons::GetTemplateSystem
	 *
	 * @return Void Doesn't returning anything.
	 */
	public function __construct($db=null)
	{
		/**
		 * If the addon hasn't specified it's own id, work out what it should be.
		 * An addon can specify it's own id if an addon is broken into multiple files.
		 */
		if ($this->addon_id === null) {
			$my_id = str_replace(array('Interspire_Addons_', 'Addons_'), '', get_class($this));
			$this->addon_id = $my_id;
		}

		$this->addon_base_directory = dirname(__FILE__) . '/';

		if (empty($this->addon_template_directory)) {
			$temp = $this->addon_base_directory . $this->addon_id . '/templates';
			if (is_dir($temp)) {
				$this->addon_template_directory = $temp;
			}
		}

		$this->GetDb($db);

		if (empty($this->addon_template_directory)) {
			$temp = $this->addon_base_directory . $this->addon_id . '/templates';
			if (is_dir($temp)) {
				$this->addon_template_directory = $temp;
			}
		}
		$this->GetTemplateSystem();

		/**
		 * _set_url can throw an exception if it's being called from the base class
		 * ie the 'addon_id' is 'Interspire_Addons'
		 * So just catch it and throw it away.
		 */
		try {
			$this->_set_url(self::$url);
		} catch (Interspire_Addons_Exception $e) {
		}

		$this->Load();
	}

	/**
	 * SetUrl
	 * This is used to set up the other urls
	 * It is called statically so the Process method can use the url and have a way of setting the child object variables it needs to.
	 *
	 * @param String $url The url to use as the base for the others. It is trimmed of any trailing '/' characters so it'll be in a consistent format.
	 *
	 * @see _set_url
	 * @see template_url
	 * @see admin_url
	 * @see settings_url
	 * @see application_url
	 * @uses url
	 * @see Process
	 *
	 * @return Void Doesn't return anything. Sets a static class variable for easy access.
	 *
	 * @static
	 */
	static public function SetUrl($url=null)
	{
		self::$url = rtrim($url, '/');
	}

	/**
	 * _set_url
	 * This is called by Process to set child object variables for ease of use
	 * It adds it's necessary bits and pieces (eg the addon_id) to each of the variables it creates.
	 * It's a private method as only Process() should call this. It should not need overriding by child objects.
	 *
	 * If the object has not been loaded (based on addon_id), then this will throw an exception.
	 * It will also throw an exception if the self::$url hasn't been set by calling SetUrl.
	 *
	 * @param String $url The url to use for the base.
	 *
	 * @uses template_url
	 * @uses admin_url
	 * @uses settings_url
	 * @uses application_url
	 * @see Process
	 *
	 * @return Mixed Throws an exception if no url has been supplied, or if the addon has not been loaded properly. Otherwise returns true.
	 */
	private function _set_url($url=null)
	{
		if ($url === null) {
			throw new Interspire_Addons_Exception("No url supplied, unable to set the url", Interspire_Addons_Exception::InvalidUrl);
		}

		if ($this->addon_id === null || $this->addon_id === 'Interspire_Addons') {
			throw new Interspire_Addons_Exception("Addon not loaded correctly", Interspire_Addons_Exception::AddonNotLoaded);
		}

		$this->template_url = $url . '/addons/' . $this->GetId() . '/templates/';
		$this->admin_url = $url . '/index.php?Page=Addons&Addon=' . $this->GetId();
		$this->settings_url = $url . '/index.php?Page=Settings&Action=Addons&Addon=' . $this->GetId();
		$this->application_url = $url . '/';

		return true;
	}

	/**
	 * GetDb
	 * This sets the local db variable for easy use
	 *
	 * @uses db
	 *
	 * @return Void Doesn't return anything.
	 */
	private function GetDb($db=null)
	{
		if (is_null($db)) {
			$this->db = IEM::getDatabase();
		} else {
			$this->db =& $db;
		}
	}

	/**
	 * LoadDescription
	 * Loads the description file from the addon subfolder passed in.
	 * It then converts the xml into an array
	 * Currently it's hardcoded to load particular variables but this could be done on the fly based on what's in the xml file
	 * If the addon doesn't exist or there is no description.xml file, it will throw an exception.
	 *
	 * It is a static method so we don't have to load all of the other class variables (which may or may not involve hitting the database for each addon) when we get a list of available addons.
	 *
	 * @todo Instead of hardcoding the variables to load and set, have it work this out automatically to make it more flexible.
	 *
	 * @param String $addon_id The addon to load the description file for.
	 *
	 * @see GetAllAddons
	 * @uses Interspire_Addons_Exception::AddonDoesntExist
	 * @uses Interspire_Addons_Exception::AddonDescriptionDoesntExist
	 *
	 * @return Mixed If the addon doesn't exist or there is no description.xml file, it will throw an exception. Otherwise, particular variables are loaded from the xml and returned.
	 *
	 * @static
	 */
	static function LoadDescription($addon_id=null)
	{
		if ($addon_id === null) {
			throw new Interspire_Addons_Exception("Invalid addon passed to load description", Interspire_Addons_Exception::AddonDoesntExist);
		}

		$description_file = dirname(__FILE__) . '/' . strtolower($addon_id) . '/description.xml';
		if (!is_file($description_file) || !is_readable($description_file)) {
			throw new Interspire_Addons_Exception("Unable to find or read the description.xml file for the addon " . $addon_id, Interspire_Addons_Exception::AddonDescriptionDoesntExist);
		}

		$desc = simplexml_load_file($description_file);
		$return = array();
		$return['name'] = (string)$desc->name;
		$return['description'] = (string)$desc->description;
		$return['addon_version'] = (string)$desc->version;
		$return['hasconfiguration'] = ($desc->hasconfiguration == 1 || strtolower($desc->hasconfiguration) == 'true');
		return $return;
	}

	/**
	 * LoadLanguageFile
	 * Loads the language file for the addon based on the addon_id class variable.
	 *
	 * If that hasn't been set properly, this will throw an exception.
	 *
	 * @uses GetId
	 * @uses Interspire_Addons_Exception::AddonNotLoaded
	 * @uses Interspire_Addons_Exception::AddonLanguageFileDoesntExist
	 *
	 * @return Mixed This will throw an exception if the addon hasn't been loaded (based on the id) or if the language file doesn't exist. If both of those conditions are met, this returns true.
	 */
	public function LoadLanguageFile()
	{
		$addon_id = $this->GetId();
		if ($addon_id === null) {
			throw new Interspire_Addons_Exception("Addon has not been loaded properly", Interspire_Addons_Exception::AddonNotLoaded);
		}

		/**
		 * Since the language files use defined variables,
		 * check if it's already been loaded.
		 * After loading a language file, this class will define a language variable:
		 * define('LNG_Addon_{$addon_id}_IsLoaded', true);
		 *
		 * so we have an easy way to detect if it's already done.
		 */
		if (defined('LNG_Addon_' . $addon_id . '_IsLoaded')) {
			return true;
		}

		$lang_file = $this->addon_base_directory . $addon_id . '/language/language.php';

		if (!is_file($lang_file) || !is_readable($lang_file)) {
			throw new Interspire_Addons_Exception("Addon has not been loaded properly", Interspire_Addons_Exception::AddonLanguageFileDoesntExist);
		}
		require_once($lang_file);
		define('LNG_Addon_' . $addon_id . '_IsLoaded', true);
		return true;
	}

	/**
	 * GetId
	 * Returns the addon_id class variable.
	 * This is null by default and is only set properly when an addon is loaded.
	 *
	 * @uses addon_id
	 * @uses Interspire_Addons_Exception::AddonNotLoaded
	 *
	 * @return Mixed Throws an exception if the addon id isn't set properly, otherwise it gets returned.
	 */
	public function GetId()
	{
		if ($this->addon_id === null) {
			throw new Interspire_Addons_Exception("Addon has not been loaded properly", Interspire_Addons_Exception::AddonNotLoaded);
		}
		return $this->addon_id;
	}

	/**
	 * GetAllAddons
	 * This looks through the current directory for subfolders
	 * Inside those subfolders, it calls LoadDescription to grab the details from the xml file in that folder
	 * That description is added to the addons array with the addon_id (the folder name) is the key
	 *
	 * You end up with a multidimensial array
	 *
	 * <code>
	 * $addons = array (
	 *	'addon_id_1' => array (
	 *			'name' => 'My Addon Name',
	 *			'description' => 'This is a detailed explanation of what the addon does',
	 *			'addon_version' => 1.0
	 *		),
	 *	'addon_id_2' => array (
	 *			'name' => 'Another Addon Name',
	 *			'description' => 'This is a detailed explanation of what the addon does',
	 *			'addon_version' => 1.5
	 *		),
	 * );
	 * </code>
	 *
	 * @return Array Returns a multidimensional array containing the details of the addons with the addon_id as the 'key' for the array.
	 *
	 * @uses LoadDescription
	 *
	 * @static
	 */
	static function GetAllAddons()
	{
		$basedir = dirname(__FILE__);
		$addons = array();
		$handle = opendir($basedir);
		while (false !== ($filename = readdir($handle))) {
			if ($filename === '.' || $filename === '..') {
				continue;
			}
			if (is_dir($basedir . '/' . $filename)) {
				try {
					$addon_desc = self::LoadDescription($filename);
					$addons[$filename] = $addon_desc;
				} catch (Exception $e) {
				}
			}
		}
		asort($addons);
		return $addons;
	}

	/**
	 * GetAvailableAddons
	 * Gets status information from the database for installed addons.
	 * This allows us to see which ones are enabled, configured and also get their version number to see if they need upgrading.
	 *
	 * <code>
	 * $addons = array (
	 * 	'addon_id_1' => array (
	 * 		'addon_id' => 'addon_id_1',
	 * 		'installed' => 1,
	 * 		'enabled' => 0,
	 * 		'configured' => 1,
	 * 		'addon_version' => 1.5
	 * 	),
	 * 	'addon_id_2' => array (
	 * 		'addon_id' => 'addon_id_2',
	 * 		'installed' => 0,
	 * 		'enabled' => 0,
	 * 		'configured' => 0,
	 * 		'addon_version' => 1.7
	 * 	)
	 * );
	 * </code>
	 *
	 * @uses db
	 *
	 * @return Array Returns a multidimensional array of details for the addon.
	 */
	public function GetAvailableAddons()
	{
		$return = array();
		$result = $this->db->Query("SELECT addon_id, installed, enabled, configured, addon_version FROM " . $this->db->TablePrefix . "addons ORDER BY addon_id");
		while ($row = $this->db->Fetch($result)) {
			$return[$row['addon_id']] = $row;
		}
		return $return;
	}

	/**
	 * FixEnabledEventListeners
	 *
	 * This will re-register all event listeners for all enabled addons.
	 *
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function FixEnabledEventListeners()
	{
		$availableAddons = $this->GetAvailableAddons();

		foreach ($availableAddons as $addon_name => $addon_record) {
			if (!$addon_record['enabled']) {
				continue;
			}

			$addon_class = "Addons_{$addon_name}";
			$file = dirname(__FILE__) . "/{$addon_name}/{$addon_name}.php";

			if (!is_readable($file)) {
				continue;
			}

			require_once($file);
			if (!class_exists($addon_class, false)) {
				continue;
			}

			$addon = new $addon_class();
			$listeners = $addon->GetEventListeners();
			foreach ($listeners as $listener) {
				try {
					InterspireEvent::listenerRegister($listener['eventname'], $listener['trigger_details'], $listener['trigger_file']);
				} catch (Exception $e) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * GetEventListeners
	 * Gets an array of events a particular addon is going to be triggered for.
	 * By default, there are no events to listen for - so the base method must be overridden in child addons.
	 *
	 * Three common events are:
	 * - putting the addon in the main navigation menu
	 * - putting the addon in the 'tools' menu at the top
	 * - providing extra user permissions
	 *
	 * To put an addon in the main navigation menu, the event is called 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS'.
	 * To make an addon listen for that event, include the following code in the array that gets returned from GetEventListeners:
	 * <code>
	 * $listeners = array();
	 * $listeners[] = array (
	 * 					'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS',
	 * 					'trigger_details' => array (
	 * 						'Interspire_Addons',
	 * 						'GetMenuItems'
	 * 					),
	 * 					'trigger_file' => __FILE__
	 * 			);
	 * return $listeners;
	 * </code>
	 *
	 * If the trigger_details information is an array such as above, the first element is the name of the class to call and the second is the method to call.
	 * This must be declared as a static method otherwise it will not work.
	 * The trigger_file is the file to include to get the data from (use __FILE__ as it will be the full path to the file and works across all platforms).
	 *
	 * If you prefer to call a function rather than a static method, it will simply point to the name of the function to call:
	 *
	 * <code>
	 * 'trigger_details' => 'Function_To_Call'
	 * </code>
	 *
	 * To put an addon into the tools menu at the top, it's slightly different.
	 * The eventname is called 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS' and the trigger details are different as well.
	 * <code>
	 * $listeners = array();
	 * $listeners[] = array (
	 *					'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS',
	 *					'trigger_details' => array (
	 *						'Addons_addonid',
	 *						'GetTextMenuItems',
	 *				 	),
	 *					'trigger_file' => __FILE__
	 *			);
	 * return $listeners;
	 * </code>
	 * In this example, the addon itself is called ('Addons_addonid') rather than this base class.
	 *
	 *
	 * The next case allows you to create your own permissions in an addon.
	 * Since most addons will need to set their own permissions, this is done through this base class rather than hitting the addon's directly.
	 * <code>
	 * $listeners = array();
	 * $listeners[] = array (
	 * 					'eventname' => 'IEM_USERAPI_GETPERMISSIONTYPES',
	 * 					'trigger_details' => array (
	 * 						'Interspire_Addons',
	 * 						'GetAddonPermissions'
	 * 					),
	 * 					'trigger_file' => __FILE__
	 * 			);
	 * return $listeners;
	 * </code>
	 *
	 * An addon can do multiple things (such as create it's own menu items and have it's own permissions).
	 * Put all of the events you want to listen to in the array returned by GetEventListeners.
	 * <code>
	 * $listeners = array();
	 * $listeners[] = array (
	 * 					'eventname' => 'IEM_USERAPI_GETPERMISSIONTYPES',
	 * 					'trigger_details' => array (
	 * 						'Interspire_Addons',
	 * 						'GetAddonPermissions'
	 * 					),
	 * 					'trigger_file' => __FILE__
	 * 			);
	 * $listeners[] = array (
	 * 					'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS',
	 * 					'trigger_details' => array (
	 * 						'Interspire_Addons',
	 * 						'GetMenuItems'
	 * 					),
	 * 					'trigger_file' => __FILE__
	 * 			);
	 *
	 * return $listeners;
	 * </code>
	 * This will put the addon into the menu and also allow it's permissions to be set & used when the addon is installed, configured and enabled.
	 *
	 * @see GetAddonPermissions
	 * @see GetMenuItems
	 *
	 * @return Array Returns an empty array in the base class. Must be overridden to do anything.
	 */
	public function GetEventListeners()
	{
		return array();
	}

	/**
	 * Admin_Action_Default
	 * This is the default action when you view the addon through the admin control panel.
	 * Each subclass must override this method (otherwise it won't do anything).
	 * If it was possible this should be an abstract method
	 * however we need to call the class statically (having an abstract method would trigger an error)
	 * also when we get the addon status we have to create an object based on the class
	 * where it would also trigger an error.
	 *
	 * Instead, the default action just returns nothing.
	 *
	 * @return Void The base class returns nothing when this method is called. Each child class must override this method.
	 */
	public function Admin_Action_Default()
	{
		return;
	}

	/**
	 * Register a menu item for a particular addon.
	 *
	 *
	 * A menu item needs to contain a bit of information including:
	 * - the "menu image" it should appear under
	 * - the "text" to show for the item (short description)
	 * - the "link" to the addon. The link will take the user to the default action (eg "manage" page)
	 * - whether to show the item or not
	 * - a description (long description)
	 * - an image url for the menu item. This is a thumbnail image that shows in the dropdown navigation menu.
	 *
	 * <code>
	 * $menuitem = array (
	 * 	'contactlist_button' => array (
	 * 		'text' => 'My Menu Item',
	 *		'link' => 'index.php?Page=Addons&Addon=addon_id',
	 *		'show' => true,
	 *		'description' => 'This is a long description. It tells you more about what the addon does',
	 *		'image_url' => 'http://domain.com/admin/addons/templates/images/image.gif'
	 *	)
	 * );
	 * </code>
	 * This will appear under the 'contactlist_button' menu (first on the left).
	 *
	 * An addon can also create a new button in the navigation menu by using a full path to it's own image.
	 * If there is only one item in the menu, then the image becomes clickable and takes you to the link provided.
	 * If more than one item is in the menu, it automatically becomes a dropdown menu.
	 *
	 * The image must be a GIF and there should also be a "_selected" image in case you are in that area.
	 * The menu will automatically highlight to show which area a user is in.
	 *
	 * <code>
	 * $menuitem = array (
	 * 	'/path/to/app/admin/addons/addon_id/templates/images/my_button' => array (
	 *		'text' => 'This will create a new menu item',
	 *		'link' => 'index.php?Page=Addons&Addon=addon_id',
	 *		'show' => true,
	 *		'description' => 'This addon has its own menu',
	 *		'image_url' => 'http://domain.com/admin/addons/templates/images/image.gif'
	 *	)
	 * );
	 * </code>
	 *
	 * A menu item can also check permissions using the 'show' entry.
	 *
	 * Menu items always show for super-admin users, you cannot block them.
	 *
	 * To check if a user has access to a particular area, the 'show' entry will be an array like:
	 *
	 * <code>
	 * 'show' => array (
	 *	'CheckAccess' => 'Admin'
	 * )
	 * </code>
	 *
	 * This checks the user is a super-administrator before displaying.
	 * You can also use 'ListAdmin', 'SegmentAdmin' or 'TemplateAdmin' to check lower level administrators.
	 *
	 * You can also check specific permissions for your addon using a slightly different syntax:
	 *
	 * <code>
	 * 'show' => array (
	 * 	'CheckAccess' => 'HasAccess',
	 * 	'Permissions' => array ('addon_id', 'permission_1')
	 * )
	 * </code>
	 *
	 * This will check the user has access to 'permission_1' before it is displayed.
	 * You can check whether a user has access to any part of the addon by just specifying the addon name:
	 *
	 * <code>
	 * 'show' => array (
	 * 	'CheckAccess' => 'HasAccess',
	 * 	'Permissions' => array ('addon_id')
	 * )
	 * </code>
	 *
	 * That is, the item will show in the menu if the user has 'permission_1', 'permission_2' or 'permission_3'.
	 *
	 * @param Array $item An array containing the details of the menu item. It is temporarily stores in the $menuItems array for GetMenuItems to call.
	 *
	 * @see GetMenuItems
	 *
	 * @static
	 */
	static function RegisterMenuItem($item)
	{
		self::$menuItems[] = $item;
	}

	/**
	 * Get a list of menu items for a particular addon.
	 *
	 * It uses GetAvailableAddons to work out which ones are available/enabled
	 * If an addon is enabled then it is loaded, and this will call the Addon::RegisterMenuItems static method.
	 * Calling that puts the addon menu items into the self::$menuItems array for us to use.
	 * This method adds itself to the $data->data array and then returns.
	 * The data is passed in as an object therefore by reference, so we don't need to return anything.
	 * Permissions can be checked by the menu in the main application to see if an item should be shown or not.
	 *
	 * @param InterspireEventData $data The existing menu data is passed in as an object (therefore by reference).
	 *
	 * @uses GetAvailableAddons
	 * @uses RegisterMenuItems
	 * @uses menuItems
	 * @see RegisterMenuItem
	 *
	 *
	 * @return Void Since the data is passed in by reference, this doesn't return anything, it just adds itself to the menu directly.
	 */
	static function GetMenuItems(InterspireEventData $data)
	{
		$self = new self;
		$addons = $self->GetAvailableAddons();
		foreach ($addons as $addon_id => $details) {
			if (!$details['enabled']) {
				continue;
			}
			require_once($self->addon_base_directory . $addon_id . '/' . $addon_id . '.php');

			/**
			 * call the "RegisterMenuItems" static method in the subclasses
			 * That adds an item to self::$menuItems for us to then use.
			 */
			$class_name = 'Addons_' . $addon_id;
			if (method_exists($class_name, 'RegisterMenuItems')) {
				call_user_func(array($class_name, 'RegisterMenuItems'));
			}
		}

		foreach (self::$menuItems as $details) {
			foreach ($details as $button => $menu_details) {
				if (isset($data->data[$button])) {
					$data->data[$button][] = $menu_details;
					continue;
				}
				$data->data[$button] = array ($menu_details);
			}
		}

		unset($self);
	}

	/**
	 * RegisterAddonPermission
	 * This adds the permission details passed in to the static userPermissions array.
	 * This array is then used by GetAddonPermissions to return the permissions it needs based on whether an addon is available/enabled.
	 * Regardless of the addon or permissions you try to set, a super-administrator always has access to it - they cannot be blocked or locked out.
	 * If an addon does not set any permissions, then only a super-administrator will be able to access it through the menu item it creates.
	 * The permissions can be used in conjunction with the menu items to only appear for certain users who have a permission set.
	 *
	 * <code>
	 * $permissions = array (
	 * 	'addon_id' => array (
	 *		'addon_description' => 'This is the short description for the addon. It comes from the description.xml file.',
	 * 		'permission_1' => array (
	 * 			'name' => 'This is the name of the permission. The name is a short description (eg "Create Options")',
	 * 			'help' => 'This is the help tip description if it needs one. If one is not supplied, a help tip icon is not shown next to the permission'
	 * 		),
	 * 		'permission_2' => array (
	 * 			'name' => 'This is another permission (eg "Edit Options"). It does not have a helptip'
	 * 		),
	 * 		'permission_3' => array (
	 * 			'name' => 'This is a 3rd permission (eg "Delete Options"). It does have a helptip',
	 * 			'help' => 'This is the helptip for the 3rd permission'
	 * 		),
	 * 	)
	 * );
	 * </code>
	 *
	 * The permissions are checked like:
	 *
	 * <code>
	 * $access_ok = $user->HasAccess('addon_id', 'permission_1');
	 * if (!$access_ok) {
	 * 	echo "Permission denied";
	 * } else {
	 * 	echo "Permission granted";
	 * }
	 * </code>
	 *
	 * @param Array $permissions The new permissions to include for the addon.
	 *
	 * @uses userPermissions
	 * @see GetEventListeners
	 * @see User_API::GrantAccess
	 * @see User_API::RevokeAccess
	 * @see User_API::HasAccess
	 * @see GetMenuItems
	 * @see RegisterMenuItems
	 */
	static function RegisterAddonPermission($permissions=array())
	{
		self::$userPermissions = array_merge(self::$userPermissions, $permissions);
	}

	/**
	 * GetAddonPermissions
	 * Gets permissions from enabled and active addons.
	 * This is used by the main application to work out which permissions to show and which permissions are allowed to be used.
	 * Only enabled addons are included in the new permissions.
	 *
	 * @param EventData_IEM_USERAPI_GETPERMISSIONTYPES $data The original addon permissions. This is passed in by reference as an object so just add the new permissions to the original data.
	 *
	 * @uses GetAvailableAddons
	 * @uses RegisterAddonPermissions
	 *
	 * @return Void Returns nothing, the data is passed in by reference so new permissions are just added to it straight away.
	 *
	 * @uses EventData_IEM_USERAPI_GETPERMISSIONTYPES
	 */
	static function GetAddonPermissions(EventData_IEM_USERAPI_GETPERMISSIONTYPES $data)
	{
		$self = new self;
		$addons = $self->GetAvailableAddons();
		foreach ($addons as $addon_id => $details) {
			if (!$details['enabled']) {
				continue;
			}
			require_once($self->addon_base_directory . $addon_id . '/' . $addon_id . '.php');

			/**
			 * call the "RegisterMenuItems" static method in the subclasses
			 * That adds an item to self::$menuItems for us to then use.
			 */
			$class_name = 'Addons_' . $addon_id;
			if (method_exists($class_name, 'RegisterAddonPermissions')) {
				call_user_func(array($class_name, 'RegisterAddonPermissions'));
			}
		}
		unset($self);

		$data->extra_permissions = array_merge($data->extra_permissions, self::$userPermissions);
	}

	/**
	 * Load
	 * Loads addon settings from the database then sets class variables based on what the database returns.
	 * If there is a database error (or the database is down), this will throw an exception
	 *
	 * If the addon is found in the database, then the following details are loaded into class variables:
	 * - installed
	 * - configured
	 * - enabled
	 * - addon_version
	 * - settings (unserialized)
	 *
	 * If the addon is not found in the database, then no details are set.
	 *
	 * @uses addon_loaded
	 * @uses GetId
	 * @see installed
	 * @see configured
	 * @see enabled
	 * @see addon_version
	 * @see settings
	 * @uses LoadLanguageFile
	 * @uses LoadDescription
	 * @uses Interspire_Addons_Exception::DatabaseError
	 *
	 * @return Mixed Throws an exception if the database returns an error or if there are no details present in the database for the addon we're trying to load. If there are details, object variables are set, language files are loaded and the method returns true.
	 */
	public function Load()
	{
		if ($this->addon_loaded || $this->GetId() === 'Interspire_Addons') {
			return;
		}

		$query = "SELECT addon_id, installed, configured, enabled, addon_version, settings FROM " . $this->db->TablePrefix . "addons WHERE addon_id='" . $this->db->Quote($this->GetId()) . "'";
		$result = $this->db->Query($query);
		if (!$result) {
			throw new Interspire_Addons_Exception($this->db->Error(), Interspire_Addons_Exception::DatabaseError);
		}

		$addon_details = $this->db->Fetch($result);

		if (!empty($addon_details)) {
			$this->installed = (bool)$addon_details['installed'];
			$this->configured = (bool)$addon_details['configured'];
			$this->enabled = (bool)$addon_details['enabled'];
			$this->addon_version = $addon_details['addon_version'];
			$this->settings = unserialize($addon_details['settings']);
		}

		$this->LoadLanguageFile();

		$this->description = self::LoadDescription($this->GetId());

		$this->addon_loaded = true;

		return true;

	}

	/**
	 * UnInstall
	 * The parent method for 'UnInstall' simply deletes the addon to the database.
	 * If the child class needs to do anything else, then it can do that in itself then call the parent.
	 *
	 * @uses GetId
	 * @uses Interspire_Addons_Exception::DatabaseError
	 * @uses _unregisterListeners
	 * @uses InterspireEventException
	 *
	 * @throws Throws an InterspireEventException if the addons events can't be unregistered. Throws Interspire_Addons_Exception if there is a database error.
	 * @return True Returns true if the addon unregisters itself and also if the database change is successful.
	 */
	public function UnInstall()
	{
		try {
			$this->_unregisterListeners();
		} catch (InterspireEventException $e) {
			throw new InterspireEventException($e->getMessage(), $e->getCode());
		}

		$result = $this->db->Query("DELETE FROM " . $this->db->TablePrefix . "addons WHERE addon_id='" . $this->db->Quote($this->GetId()) . "'");
		if ($result) {
			return true;
		}

		throw new Interspire_Addons_Exception($this->db->Error(), Interspire_Addons_Exception::DatabaseError);
	}

	/**
	 * Disable
	 * Disabling the addon does not remove it's configuration options, it simply stops users from being able to access it.
	 * If the child class needs to do anything else, then it can do that in itself then call the parent.
	 *
	 * @uses GetId
	 * @uses Interspire_Addons_Exception::DatabaseError
	 * @uses _unregisterListeners
	 * @uses InterspireEventException
	 *
	 * @return True Returns true if the events can all be unregistered and if the database update to disable the addon is successful.
	 * @throws Throws an InterspireEventException if the addons events can't be unregistered. Throws Interspire_Addons_Exception if there is a database error.
	 */
	public function Disable()
	{
		try {
			$this->_unregisterListeners();
		} catch (InterspireEventException $e) {
			throw new InterspireEventException($e->getMessage(), $e->getCode());
		}

		$result = $this->db->Query("UPDATE " . $this->db->TablePrefix . "addons SET enabled=0 WHERE addon_id='" . $this->db->Quote($this->GetId()) . "'");
		if ($result) {
			return true;
		}
		throw new Interspire_Addons_Exception($this->db->Error(), Interspire_Addons_Exception::DatabaseError);
	}

	/**
	 * Configure
	 * The default option for configure is to return a message saying the addon doesn't need to be configured.
	 * If an addon has settings it needs to use, this method must be overridden in the child addon.
	 *
	 * @return String Returns a message saying the addon doesn't need to be configured.
	 */
	public function Configure()
	{
		return "This addon does not have any configuration options.";
	}

	/***
	 * isEnabled
	 * Check whether a specific addon is enabled or disabled from the database,
	 *
	 *  @return Boolean active is true
	 *
	 */
	public function isEnabled($addon)
	{
		$result = $this->db->Query("SELECT enabled FROM " . $this->db->TablePrefix . "addons WHERE addon_id='" . $addon . "'");
		if (!$result) {
			throw new Interspire_Addons_Exception($this->db->Error(), Interspire_Addons_Exception::DatabaseError);
			return false;
		}

		$res = $this->db->Fetch($result);
		$enable = $res['enabled'];
		if ($enable && $enable === '1') {
			return true;
		}
		return false;
	}

	/**
	 * _unregisterListeners
	 * This is called when you uninstall or disable an addon.
	 * It goes through all of the events an addon listens to and removes them from the triggers.
	 *
	 * @uses GetEventListeners
	 * @uses InterspireEvent::listenerUnregister
	 * @uses InterspireEventException
	 *
	 * @return Void Doesn't return anything.
	 * @throws Throws an InterspireEventException if an event can't be unregistered. The error and code come from InterspireEventException.
	 */
	private function _unregisterListeners()
	{
		$listeners = $this->GetEventListeners();
		foreach ($listeners as $listener) {
			try {
				InterspireEvent::listenerUnregister($listener['eventname'], $listener['trigger_details'], $listener['trigger_file']);
			} catch (InterspireEventException $e) {
				throw new InterspireEventException($e->getMessage(), $e->getCode());
			}
		}
	}

	/**
	 * Install
	 * The parent method for 'Install' simply adds the addon to the database so it can track whether it's enabled, what configuration options it has set (if applicable) and so on.
	 * A child class must call the parent for it to be registered in the database.
	 * If the child class needs to do anything else (eg check permissions on files/folders), then it can do that in itself then call the parent.
	 * If the child class doesn't have any settings or if the addon should be enabled/configured straight away (eg using default settings),
	 * then it can set the appropriate class variables and then call the parent method to handle that work.
	 * If the addon is automatically enabled (eg it doesn't need to be configured), events are automatically registered
	 *
	 * @uses GetId
	 * @uses configured
	 * @uses enabled
	 * @uses description
	 * @uses settings
	 * @uses _registerListeners
	 * @uses Interspire_Addons_Exception::DatabaseError
	 * @uses InterspireEventException
	 *
	 * @return True Returns true if the addon is successfully installed and if the addon is automatically enabled, if the addon events are registered successfully.
	 * @throws Throws an InterspireEventException if the addons events can't be registered. Throws Interspire_Addons_Exception if there is a database error.
	 */
	public function Install()
	{
		$this->description = self::LoadDescription($this->GetId());

		if ($this->enabled) {
			try {
				$this->_registerListeners();
			} catch (InterspireEventException $e) {
				throw new InterspireEventException($e->getMessage(), $e->getCode());
			}
		}

		$query = "SELECT COUNT(*) FROM [|PREFIX|]addons WHERE addon_id='" . $this->db->Quote($this->GetId()) . "'";
		$already_installed = $this->db->FetchOne($query);
		// I'm not sure if it's possible for an addon to have `installed` = 0, since uninstalling an addon removes it from the table.
		if ($already_installed) {
			return true;
		}

		$query = "INSERT INTO [|PREFIX|]addons (addon_id, installed, configured, enabled, addon_version, settings) VALUES ('" . $this->db->Quote($this->GetId()) . "', 1, " . (int)$this->configured . ", " . (int)$this->enabled . ", '" . $this->db->Quote($this->description['addon_version']) . "', '" . $this->db->Quote(serialize($this->settings)) . "')";
		$result = $this->db->Query($query);
		if ($result) {
			return true;
		}

		throw new Interspire_Addons_Exception($this->db->Error(), Interspire_Addons_Exception::DatabaseError);
	}

	/**
	 * Enable
	 * Enabling the addon allows users to access it based on it's configuration options (eg permissions).
	 * If the child class needs to do anything else, then it can do that in itself then call the parent.
	 * When an addon is enabled, it automatically registers events which need to be triggered.
	 *
	 * @uses GetId
	 * @uses _registerListeners
	 * @uses Interspire_Addons_Exception::DatabaseError
	 * @uses InterspireEventException
	 *
	 * @return True Returns true if the addon is successfully installed and if the addon events are registered successfully.
	 * @throws Throws an InterspireEventException if the addons events can't be registered. Throws Interspire_Addons_Exception if there is a database error.
	 */
	function Enable()
	{
		try {
			$this->_registerListeners();
		} catch (InterspireEventException $e) {
			throw new InterspireEventException($e->getMessage(), $e->getCode());
		}

		$result = $this->db->Query("UPDATE " . $this->db->TablePrefix . "addons SET enabled=1 WHERE addon_id='" . $this->db->Quote($this->GetId()) . "'");
		if ($result) {
			return true;
		}
		throw new Interspire_Addons_Exception($this->db->Error(), Interspire_Addons_Exception::DatabaseError);
	}

	/**
	 * _registerListeners
	 * This is called when you install or enable an addon.
	 * It goes through all of the events an addon listens to and addons them to the triggers.
	 *
	 * @uses GetEventListeners
	 * @uses InterspireEvent::listenerRegister
	 * @uses InterspireEventException
	 *
	 * @return Void Doesn't return anything.
	 * @throws Throws an InterspireEventException if an event can't be registered. The error and code come from InterspireEventException.
	 */
	private function _registerListeners()
	{
		$listeners = $this->GetEventListeners();
		foreach ($listeners as $listener) {
			try {
				InterspireEvent::listenerRegister($listener['eventname'], $listener['trigger_details'],$listener['trigger_file']);
			} catch (Exception $e) {
				throw new Interspire_Addons_Exception($e->getMessage(), Interspire_Addons_Exception::DatabaseError);
			}
		}
	}

	/**
	 * Upgrade
	 * The parent upgrade method only updates the version number in the database for the currently loaded addon.
	 * If the child class needs to do anything else, then it can do that in itself then call the parent.
	 *
	 * @uses GetId
	 * @uses Interspire_Addons_Exception::DatabaseError
	 *
	 * @return Mixed Throws an exception if the database record can't be updated and the exception message is the database error that occurred, otherwise the details are updated and the method returns true.
	 */
	protected function _Upgrade($new_version=0)
	{
		$query = "UPDATE " . $this->db->TablePrefix . "addons SET addon_version='" . $this->db->Quote($new_version) . "' WHERE addon_id='" . $this->db->Quote($this->GetId()) . "'";
		$result = $this->db->Query($query);
		if ($result) {
			return true;
		}
		throw new Interspire_Addons_Exception("query: " . $query . "; " . $this->db->Error(), Interspire_Addons_Exception::DatabaseError);
	}

	/**
	 * Process
	 * Process taked the name of the addon and the method name to call.
	 * This is a wrapper method which can't be overridden and provides a simple gateway to allow errors to be trapped.
	 * For example, calling an invalid addon will throw an exception.
	 * Or if the addon exists, calling an invalid method name will throw an exception.
	 * If an addon method throws an exception itself, then it's simply re-thrown for the calling code to catch and handle appropriately.
	 *
	 * The Process method takes an unspecified number of arguments however the first argument has to be the addon_id and the second is the method to call in that addon.
	 * It can be called statically and it will automatically check & load the child addon appropriately.
	 *
	 * <code>
	 * try {
	 * 	Interspire_Addons::Process('Addon_Id', 'Install');
	 * } catch (Interspire_Addons_Exception $e) {
	 * 	echo "Problem trying to install Addon_Id: " . $e->getMessage();
	 * }
	 * </code>
	 *
	 * This allows the calling code to be very simple and also makes the addon system very flexible as no calling code needs to change based on which methods etc exist in the addon.
	 * If extra parameters need to be passed to the addon, this method takes an unspecified number of arguments, so variables can be passed through appropriately.
	 * However doing it that way makes the calling code hard to maintain as it will need changing all the time based on arguments/get variables etc.
	 * The extra arguments are passed straight to the child addon which then needs to handle whether those details are available or not.
	 *
	 * <code>
	 * try {
	 * 	Interspire_Addons::Process('Addon_Id', 'Install', $_POST['variable_1'], $_POST['variable_2']);
	 * } catch (Interspire_Addons_Exception $e) {
	 * 	echo "Problem trying to install Addon_Id: " . $e->getMessage();
	 * }
	 * </code>
	 *
	 * If it's a method or addon available to non-admin users, the Action has to match the name of the permission.
	 * That is, if an addon has 'create', 'edit', 'delete' and 'stats' permissions, then the Action must be one of those.
	 * If it is not, then the user will see an 'Access Denied' message.
	 *
	 * For multi-step forms (or even creating a new item or updating an existing item), the action will stay the same but you can then handle the steps involved inside the method.
	 * <code>
	 * public function Admin_Action_Create()
	 * {
	 * 	if (empty($_POST)) {
	 * 		return $this->template_system->ParseTemplate('create_form');
	 * 	}
	 *	$this->db->Query("insert into " . $this->db->TablePrefix . "my_table (name, type) values ('" . $this->db->Quote($_POST['name']) . "', '" . $this->db->Quote($_POST['type']) . "')";
	 *	return $this->template_system->ParseTemplate('item_created');
	 * }
	 * </code>
	 *
	 * If 'AJAX' or 'ajax' is in the query string as a key such as:
	 * <code>
	 * <a href="{$AdminUrl}&ajax=1">Link</a>
	 * </code>
	 * Then the header and footer are not displayed by the application.
	 * This is useful when using a thickbox or popup window that shouldn't display anything except your content.
	 * If this is not present as a get keyword, the application will display it's own header/footer around your content - which happens before the Interspire_Addons::Process method is called.
	 *
	 * If 'POPUP' or 'popup' is in the query string as a key such as:
	 * <code>
	 * <a href="${AdminUrl}&popup=1">Link</a>
	 * </code>
	 * Then the popup header/footer will be displayed.
	 * In IEM, these template files don't include nav menus and are only used by sending via the popup window and the templates are cleaner than the main application.
	 *
	 * @uses Interspire_Addons_Exception::MethodDoesntExist
	 * @uses Interspire_Addons_Exception::AddonDoesntExist
	 *
	 * @return Mixed Throws an exception if invalid parameters are passed in, or if an invalid addon_id is passed in, or if an invalid method is passed in. Otherwise it returns the status (which may or may not be an exception itself) from the method back to the calling code.
	 *
	 * @final
	 */
	final function Process()
	{
		$args = func_get_args();
		if (sizeof($args) < 2) {
			throw new Interspire_Addons_Exception("Need to specify addon_id and method name to call", Interspire_Addons_Exception::MethodDoesntExist);
		}

		$addon_id = preg_replace('/[^\w]/', '_', strtolower($args[0]));
		$func = $args[1];

		// get rid of the addon_id.
		array_shift($args);
		// then get rid of the function name.
		array_shift($args);

		$filename = dirname(__FILE__) . '/' . $addon_id . '/' . $addon_id . '.php';
		if (!is_file($filename)) {
			throw new Interspire_Addons_Exception("Addon " . $addon_id . " doesn't exist", Interspire_Addons_Exception::AddonDoesntExist);
		}

		require_once($filename);
		$class_name = 'Addons_'.$addon_id;
		$child = new $class_name;

		if (!method_exists($child, $func)) {
			throw new Interspire_Addons_Exception("Function " . $func . " doesn't exist for addon " . $addon_id, Interspire_Addons_Exception::MethodDoesntExist);
		}

		try {
			return $child->$func($args);
		} catch (Interspire_Addons_Exception $e) {
			throw new Interspire_Addons_Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * GetTemplateSystem
	 *
	 * Sets the local template_system variable for child addons to use and process templates.
	 *
	 * @uses template_system
	 * @uses GetTemplateSystem
	 *
	 * @return Void Doesn't return anything, just sets the local template_system variable.
	 */
	protected function GetTemplateSystem()
	{
		$template_dir = null;
		if ($this->addon_id !== null && $this->addon_id !== 'Interspire_Addons') {
			$template_dir = $this->addon_template_directory;
		}

		if (!empty($template_dir)) {
			$this->template_system = GetTemplateSystem($template_dir);
		}
	}

	/**
	 * GetCurrentPage
	 * Gets the page number the user is currently viewing.
	 * This is used by paging to work out where the user is up to in the list of things to show.
	 * It looks for the DisplayPage get variable.
	 *
	 * @return Int Returns the page id the user is viewing. If the DisplayPage get variable isn't set, '1' is returned (the first page).
	 */
	protected function GetCurrentPage()
	{
		$current_page = 0;
		if (isset($_GET['DisplayPage'])) {
			$current_page = (int)$_GET['DisplayPage'];
		}

		if ($current_page < 1) {
			$current_page = 1;
		}
		return $current_page;
	}

	/**
	 * GetPerPage
	 * This is an internal only method which gets the current number of results to show per page for the current addon.
	 * If an invalid number is set (or if no number is set in the first place),
	 * then the default to show is 10 per page.
	 *
	 * @uses addon_id
	 * @uses GetUser
	 * @uses User_API::GetSettings
	 *
	 * @return Int Returns the current per page settings, or 10 by default if the addon hasn't been displayed before.
	 */
	protected function GetPerPage()
	{
		$perpage = 10;

		$user = IEM::userGetCurrent();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (isset($display_settings['NumberToShow'][$this->addon_id])) {
			$perpage = (int)$display_settings['NumberToShow'][$this->addon_id];
		}

		if ($perpage < 1) {
			$perpage = 10;
		}
		return $perpage;
	}

	/**
	 * SetPerPage
	 * This is an internal only method which sets the number of results to show per page for a particular addon per user.
	 *
	 * Each addon (and each user) can have different per-page settings.
	 *
	 * @param Int $perpage The new perpage limit for the current addon. The default is 10 and if an invalid number is passed through, 10 is also the new number set.
	 *
	 * @uses addon_id
	 * @uses GetUser
	 * @uses User_API::GetSettings
	 * @uses User_API::SetSettings
	 *
	 * @return Int Returns the new perpage limit set.
	 */
	protected function SetPerPage($perpage=10)
	{
		$perpage = (int)$perpage;
		if ($perpage < 1) {
			$perpage = 10;
		}

		$user = IEM::userGetCurrent();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (!isset($display_settings['NumberToShow']) || !is_array($display_settings['NumberToShow'])) {
			$display_settings['NumberToShow'] = array();
		}
		$display_settings['NumberToShow'][$this->addon_id] = $perpage;
		$user->SetSettings('DisplaySettings', $display_settings);
		$user->SaveSettings();
		return $perpage;
	}

	/**
	 * SetupPaging
	 * This sets up the paging options for addons to use.
	 * It shows the dropdown list of results per page with the current setting highlighted.
	 * It also works out how many pages to show left & right based on which page is currently being displayed.
	 * If you try to view page 50 and there are only 40 pages worth of results, it automatically takes you to the last page.
	 * It is pretty much a clone of the SetupPaging method in the SendStudio_Functions class.
	 *
	 * This *must* be called before GetPerPage(), otherwise it will use stale data.
	 *
	 * <b>Example</b>
	 * <code>
	 * 	$total_records = $this->db->FetchOne('select count(id) as count from ' . $this->db->TablePrefix . 'mytable');
	 * 	$paging = $this->SetupPaging($this->admin_url, $total_records);
	 * </code>
	 *
	 * <b>Another example</b>
	 * <code>
	 * 	$total_records = $this->db->FetchOne('select count(id) as count from ' . $this->db->TablePrefix . 'mytable');
	 * 	$paging = $this->SetupPaging($this->admin_url . '&Action=Stats', $total_records);
	 * </code>
	 *
	 * @param String $url The url for paging to use. Most likely based on the admin_url for the addon, but it can be changed as well (eg viewing stats or another area).
	 * @param Int $number_of_records The total number of records there are to page through. This is used to work out how many pages of results there are.
	 * @param Int $number_of_links_to_show The number of links to show for paging in total. The default is 5, so if you are on page 4, you will see 2 links either side of the current page
	 * (pages 2,3,4,5,6) will be linked).
	 *
	 * @see Sendstudio_Functions::SetupPaging
	 * @uses SetPerPage
	 * @uses GetCurrentPage
	 * @uses GetPerPage
	 * @uses addon_id
	 *
	 * @return String Returns the paging options templatized, language packed and pre-filled. This allows an addon to just set the template variable and automatically include paging.
	 */
	protected function SetupPaging($url=null, $number_of_records=0, $number_of_links_to_show=5)
	{

		if ($url === null) {
			return '';
		}

		$number_of_records = (int)$number_of_records;
		if ($number_of_records <= 0) {
			return '';
		}

		if (isset($_GET['PerPageDisplay'])) {
			$this->SetPerPage($_GET['PerPageDisplay']);
		}

		$current_page = $this->GetCurrentPage();

		$per_page = $this->GetPerPage();

		$number_of_links_to_show = (int)$number_of_links_to_show;
		if ($number_of_links_to_show < 1) {
			$number_of_links_to_show = 5;
		}

		$display_settings['NumberToShow'][$this->addon_id] = $this->GetPerPage();

		$GLOBALS['FormAction'] = 'Addon=' . $this->addon_id . '&Action=' . $this->_getGETRequest('Action', null);

		$GLOBALS['PerPageDisplayOptions'] = '';
		foreach (array('5', '10', '20', '30', '50', '100', '200', '500', '1000') as $p => $numtoshow) {
			$option = '<option value="' . $numtoshow . '"';
			if ($numtoshow == $display_settings['NumberToShow'][$this->addon_id]) {
				$option .= ' SELECTED';
			}
			$option .= '>' . number_format($numtoshow, 0, GetLang('NumberFormat_Dec'), GetLang('NumberFormat_Thousands'))  . '</option>';
			$GLOBALS['PerPageDisplayOptions'] .= $option;
		}

		$num_pages = 1;
		$num_pages = ceil($number_of_records / $per_page);

		if ($current_page > $num_pages) {
			// this case should only trigger if the number records in the result set have reduced (e.g. been deleted)
			// so we need to take them to the highest page number that still has results.
			$location = SENDSTUDIO_APPLICATION_URL . '/admin/index.php?' . $_SERVER['QUERY_STRING'];
			$location = preg_replace('/DisplayPage=\d+/i', '', $location);
			$location .= '&DisplayPage=' . $num_pages;
			echo "<script> window.location.href = '" . $location . "'; </script>\n";
			exit();
		}

		$prevpage = 1;
		if ($current_page > 1) {
			$prevpage = $current_page - 1;
		}

		$nextpage = $current_page + 1;
		if ($nextpage > $num_pages) {
			$nextpage = $num_pages;
		}

		$string = '(' . GetLang('Page') . ' ' . $current_page . ' ' . GetLang('Of') . ' ' . $num_pages . ')&nbsp;&nbsp;&nbsp;&nbsp;';

		$display_page_name = 'DisplayPage';

		if ($current_page > 1) {
			$string .= '<a href="' . $url . '&' . $display_page_name . '=1" title="' . GetLang('GoToFirst') . '">&laquo;</a>&nbsp;|&nbsp;';
			$string .= '<a href="' . $url . '&' . $display_page_name . '=' . $prevpage . '">' . GetLang('PagingBack') . '</a>&nbsp;|';
		} else {
			$string .= '&laquo;&nbsp;|&nbsp;';
			$string .= GetLang('PagingBack') . '&nbsp;|';
		}

		$start_page = 1;
		$end_page = $num_pages;

		if ($num_pages > $number_of_links_to_show) {
			$start_page = $current_page - (floor($number_of_links_to_show/2));
			if ($start_page < 1) {
				$start_page = 1;
			}

			$end_page = $current_page + (floor($number_of_links_to_show/2));
			if ($end_page > $num_pages) {
				$end_page = $num_pages;
			}

			if ($end_page < $number_of_links_to_show) {
				$end_page = $number_of_links_to_show;
			}

			$pagestoshow = ($end_page - $start_page);
			if (($pagestoshow < $number_of_links_to_show) && ($num_pages > $number_of_links_to_show)) {
				$start_page = ($end_page - $number_of_links_to_show+1);
			}
		}

		for ($pageid = $start_page; $pageid <= $end_page; $pageid++) {
			if ($pageid > $num_pages) {
				break;
			}

			$string .= '&nbsp;';
			if ($pageid == $current_page) {
				$string .= '<b>' . $pageid . '</b>';
			} else {
				$string .= '<a href="' . $url . '&' . $display_page_name . '=' . $pageid . '">' . $pageid . '</a>';
			}
			$string .= '&nbsp;|';
		}

		if ($current_page == $num_pages) {
			$string .= '&nbsp;' . GetLang('PagingNext') . '&nbsp;|';
			$string .= '&nbsp;&raquo;';
		} else {
			$string .= '&nbsp;<a href="' . $url . '&' . $display_page_name . '=' . $nextpage . '">' . GetLang('PagingNext') . '</a>&nbsp;|';
			$string .= '&nbsp;<a href="' . $url . '&' . $display_page_name . '=' . $num_pages . '" title="' . GetLang('GoToLast') . '">&raquo;</a>';
		}
		$GLOBALS['DisplayPage'] = $string;

		$curr_template_dir = $this->template_system->GetTemplatePath();
		$this->template_system->SetTemplatePath(SENDSTUDIO_TEMPLATE_DIRECTORY);
		$string = $this->template_system->ParseTemplate('paging', true);
		$this->template_system->SetTemplatePath($curr_template_dir);
		return $string;
	}

	/**
	 * LoadSortDetails
	 * Retrieves the saved sort details from the session for the current page.
	 *
	 * @see GetPageName
	 * @see GetSettings
	 * @see GetUser
	 *
	 * @return Array Sort details from the session.
	 */
	function LoadSortDetails($page_name = null)
	{
		$user = GetUser();
		$display_settings = $user->GetSettings('DisplaySettings');
		if ($page_name == null) {
			$page_name = $this->GetPageName();
		}

		if (isset($display_settings['Sort'][$page_name])) {
			$sort = $display_settings['Sort'][$page_name];
		} else {
			$sort = array();
		}
		return $sort;
	}

	/**
	 * SaveSortDetails
	 * Saves sort details for $page_name in the session (defaults to the current page).
	 *
	 * @see GetPageName
	 * @see GetSettings
	 * @see GetUser
	 * @see SetSettings
	 *
	 * @return Array The sort details that were just saved.
	 */
	function SaveSortDetails($sort, $page_name = null)
	{
		if (is_null($page_name)) {
			$page_name = $this->GetPageName();
		}
		$user = GetUser();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (!isset($display_settings['DisplayPage'])) {
			$display_settings['DisplayPage'] = array();
		}
		if (!isset($display_settings['Sort']) || !is_array($display_settings['DisplayPage'])) {
			$display_settings['Sort'] = array();
		}
		$display_settings['Sort'][$page_name] = $sort;
		$user->SetSettings('DisplaySettings', $display_settings);
		return $sort;
	}

	/**
	 * GetSortDetails
	 * Returns an array of sort information, remembering it for that page.
	 *
	 * @see _DefaultSort
	 * @see _DefaultDirection
	 * @see _SecondarySorts
	 * @see LoadSortDetails
	 * @see SaveSortDetails
	 *
	 * @return Array Array of sort information including sort direction and what field sort by.
	 */
	function GetSortDetails($page_name = null)
	{
		$sort = $this->LoadSortDetails($page_name);
		$update = array(
			'SortBy' => (isset($_GET['SortBy'])) ? strtolower($_GET['SortBy']) : null,
			'Direction' => (isset($_GET['Direction'])) ? strtolower($_GET['Direction']) : null,
			'Secondary' => null,
			'SecondaryDirection' => null,
			);
		$default = array(
			'SortBy' => 'name',
			'Direction' => 'desc',
			'Secondary' => false,
			'SecondaryDirection' => false,
			);
		// Filter directions.
		if (!is_null($update['Direction'])) {
			if (strtolower($update['Direction']) == 'up' || strtolower($update['Direction']) == 'asc') {
				$update['Direction'] = 'asc';
			} else {
				$update['Direction'] = 'desc';
			}
		}
		// Add in default values if necessary.
		foreach ($update as $k=>$v) {
			if (!is_null($update[$k])) {
				$sort[$k] = $update[$k];
			}
			if (!isset($sort[$k]) || is_null($sort[$k])) {
				$sort[$k] = $default[$k];
			}
		}
		return $this->SaveSortDetails($sort, $page_name);
	}

	/**
	 * GetPageName
	 * Used in remembering each section's paging settings.
	 *
	 * @see GetPerPage
	 * @see SetPerPage
	 *
	 * @return String The name of the page/section in lower case, e.g. 'subscribers' or 'unknown' if not found.
	 */
	function GetPageName()
	{
		$page = $this->addon_id;
		$action = isset($_GET['Action']) ? strtolower($_GET['Action']) : '';
		if ($action == 'processpaging' && isset($_GET['SubAction'])) {
			$action = strtolower($_GET['SubAction']);
		}
		if ($page == 'stats' && !$action) {
			$action = 'newsletters';
		}
		// see bugid:2195 for why we handle this special case with subscribers
		if ($page == 'stats' || ($page == 'subscribers' && $action == 'banned')) {
			$page .= '_'.$action;
		}
		return $page . $action;
	}


	/**
	 * _getPOSTRequest
	 * Get request variable from $_POST
	 *
	 * @param String $variableName Variable name
	 * @param Mixed $defaultValue Default value if variable not found
	 * @return Mixed Return variable value from $_POST if it exists, otherwise it will return defaultValue
	 *
	 * @access protected
	 */
	protected function _getPOSTRequest($variableName, $defaultValue = '')
	{
		if (isset($_POST) && array_key_exists($variableName, $_POST)) {
			return $_POST[$variableName];
		} else {
			return $defaultValue;
		}
	}


	/**
	 * _getGETRequest
	 * Get request variable from $_GET
	 *
	 * @param String $variableName Variable name
	 * @param Mixed $defaultValue Default value if variable not found
	 * @return Mixed Return variable value from $_POST if it exists, otherwise it will return defaultValue
	 *
	 * @access protected
	 */
	protected function _getGETRequest($variableName, $defaultValue = '')
	{
		if (isset($_GET) && array_key_exists($variableName, $_GET)) {
			return $_GET[$variableName];
		} else {
			return $defaultValue;
		}
	}
}


/**
* Interspire_Addons_Exception
* This provides constant variables for different types of exceptions for the addon system to throw.
*
* @uses Interspire_Addons
*/
class Interspire_Addons_Exception extends Exception
{
	/**
	 * AddonDoesntExist
	 * This is used if an addon (based on the id supplied to Interspire_Addons) doesn't exist or can't be loaded.
	 */
	const AddonDoesntExist = 1;

	/**
	 * DatabaseError
	 * This is used if a database error occurs when an addon does anything (eg enable, disable, install, uninstall)
	 */
	const DatabaseError = 2;

	/**
	 * MethodDoesntExist
	 * This is used if the Interspire_Addons::Process method can't find the method name in the child addon.
	 */
	const MethodDoesntExist = 3;

	/**
	 * AddonNotLoaded
	 * This is used if the Interspire_Addons methods have somehow been called when the addon has not been Loaded or through the default constructor.
	 */
	const AddonNotLoaded = 4;

	/**
	 * InvalidUrl
	 * This is used if the Interspire_Addons methods have somehow been called when the addon has not been Loaded or through the default constructor.
	 */
	const InvalidUrl = 5;

	/**
	 * AddonDescriptionDoesntExist
	 * This is used if the Interspire_Addons LoadDescription method can't find (or read) the addon's description.xml file.
	 */
	const AddonDescriptionDoesntExist = 6;

	/**
	 * AddonLanguageFileDoesntExist
	 * This is used if the Interspire_Addons LoadLanguageFile method can't find (or read) the addon's language file.
	 */
	const AddonLanguageFileDoesntExist = 7;

	/**
	 * AddonTemplateDoesntExist
	 * This os used if the Interspire_Addons GetTemplateSystem method can't find (or read) template directory.
	 */
	const AddonTemplateDoesntExist = 8;
}

