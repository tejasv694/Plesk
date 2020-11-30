<?php
/**
* This file has the base functions in it. For example, headers, footers.
*
* @version     $Id: sendstudio_functions.php,v 1.211 2008/02/28 06:54:42 chris Exp $
* @author Chris <chris@interspire.com>
* @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Make sure nobody is doing a sneaky and trying to go to the page directly.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
if (!defined('SENDSTUDIO_BASE_DIRECTORY')) {
	header('Location: ../index.php');
	exit();
}

/**
* Base class for SendStudio Functions.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class SendStudio_Functions
{

	/**
	* GlobalAreas
	* You can set global areas by putting them in this array. If they are in here, they will be used by ParseTemplate
	*
	* @see ParseTemplate
	*
	* @var Array
	*/
	var $GlobalAreas = array();

	/**
	* _RandomStrings
	* An array of helptip id's that have been generated. By remembering them here, we can ensure that they are unique.
	*
	* @see _GenerateHelpTip
	*
	* @var Array
	*/
	var $_RandomStrings = array();


	/**
	* _PagesToShow
	* This controls how many pages we show when we are creating the paging. This includes the current page. For example, if we are on page 7 of 20, we will see pages 5,6,7,8,9.
	* It should be an odd number so we get an even amount either side of the current page.
	*
	* @see SetupPaging
	*
	* @var Int
	*/
	var $_PagesToShow = 5;


	/**
	* _PagingMinimum Number of records to show before we start showing the paging at the bottom of the screen.
	*
	* @var Int
	*/
	var $_PagingMinimum = 5;


	/**
	* _PerPageDefault Default number to show per page. This is used if the user hasn't set anything before (in session).
	*
	* @see GetPerPage
	*
	* @var Int
	*/
	var $_PerPageDefault = 10;


	/**
	* _DefaultSort
	* Default sort order.
	*
	* @see _DefaultDirection
	* @see GetSortDetails
	*
	* @var String
	*/
	var $_DefaultSort = 'emailaddress';


	/**
	* _DefaultDirection
	* Default sort direction.
	*
	* @see _DefaultSort
	* @see GetSortDetails
	*
	* @var String
	*/
	var $_DefaultDirection = 'desc';

	/**
	* _SecondarySorts
	* Secondary sort for fields. This is used if the primary sort is not being done.
	*
	* @see GetSortDetails
	*
	* @var Array
	*/
	var $_SecondarySorts = array();


	/**
	* PopupWindows
	* A list of actions that are popup windows.
	*
	* @see Process
	*
	* @var Array
	*/
	var $PopupWindows = array('view');


	/**
	* ValidFileExtensions
	* An array of valid file extensions that you can attach to a newsletter.
	*
	* @see SaveAttachments
	*
	* @var Array
	*/
	var $ValidFileExtensions = array('pdf', 'doc', 'xls', 'zip', 'jpg', 'png', 'gif', 'jpeg', 'txt', 'htm', 'html', 'csv', 'rtf', 'rar', 'ppt', 'pps', 'avi', 'mp3', 'mpg', 'mpeg');

	/**
	* Months
	* An array of months. This lets us quickly grab the right language pack variable.
	*
	* @see CreateDateTimeBox
	* @see GetLang
	*
	* @var Array
	*/
	var $Months = array(
		'1' => 'Jan',
		'2' => 'Feb',
		'3' => 'Mar',
		'4' => 'Apr',
		'5' => 'May',
		'6' => 'Jun',
		'7' => 'Jul',
		'8' => 'Aug',
		'9' => 'Sep',
		'10' => 'Oct',
		'11' => 'Nov',
		'12' => 'Dec'
	);

	/**
	* days_of_week
	* An array of days of the week. This is mainly used by stats so "last 7 days" graphs get created properly.
	*
	* @see Stats_Chart::SetupChart_SubscriberSummary
	* @see Stats_Chart::SetupChart
	*
	* @var Array
	*/
	var $days_of_week = array(
		'0' => 'Sun',
		'1' => 'Mon',
		'2' => 'Tue',
		'3' => 'Wed',
		'4' => 'Thu',
		'5' => 'Fri',
		'6' => 'Sat',
		'7' => 'Sun'
	);

	/**
	* These are fields that can be selected in the visible fields section
	* of mailing lists that aren't custom fields
	*
	* @var Array
	*/
	var $BuiltinFields = array(
			'emailaddress' => 'EmailAddress','subscribedate' => 'DateSubscribed', 'format' => 'Format','status' => 'Status','confirmed' => 'Confirmed'
		);

	/**
	* @var _MaxNameLength The maximum length of a name (eg keywords, url, etc). If a name is longer than this length, it is chopped off (4 chars early) and has ' ...' appended to it.
	*
	* @see TruncateName
	*/
	var $_MaxNameLength = 45;

	/**
	* Constructor
	* Does nothing. Shouldn't ever be called itself anyway.
	*
	* @return Void Base class does nothing.
	*/
	function SendStudio_Functions()
	{
	}


	/**
	* Process
	* Base process function prints the header, prints the page and the footer.
	* If there is any functionality to provide, it must be overridden by the children objects.
	*
	* @see PrintHeader
	* @see PrintFooter
	*
	* @return Void Doesn't return anything. The base class prints out the header menu, prints out 'this' template and the footer. This should be overridden by the children objects.
	*/
	function Process()
	{
		$this->PrintHeader();
		$template = strtolower(get_class($this));
		$this->ParseTemplate($template);
		$this->PrintFooter();
	}


	/**
	* GetApi
	*
	* Gets the API we pass in. If we don't pass in an API to fetch, it will fetch the API based on the class.
	*
	* The second parameter is used for loading API, but not to instantiate them. This is useful if you want to load a singleton API.
	* An example of the usage of the second parameter can be found in [SS]/admin/functions/send.php under the method process
	* (Getting googleanalytics API)
	*
	* @param String $api The name of the API to fetch. If there is nothing passed in, it will fetch the API based on this class.
	* @param Boolean $instantiate Specify whether or not to instantiate the API
	*
	* @return True|False|Object Returns an object if it can find the API, TRUE if instanciate is specified as false, otherwise returns false.
	*/
	function GetApi($api=false, $instantiate = true)
	{
		if (!$api) {
			$api = get_class($this);
		}

		$api = strtolower($api);
		if ($api == 'email') {
			$api = 'ss_email';
		}

		$api_file = SENDSTUDIO_API_DIRECTORY.'/' . $api . '.php';
		if (!is_file($api_file)) {
			return false;
		}

		$api .= '_API';

		if (!class_exists($api, false)) {
			require_once($api_file);
		}

		if ($instantiate) {
			$myapi = New $api();
			return $myapi;
		} else {
			return true;
		}
	}

	/**
	* ReplaceLanguageVariables
	* Replaces language variables that are found in the content passed in, then returns the content.
	*
	* @param String $template The template content to replace language variables in.
	*
	* @see ParseTemplate
	*
	* @return String Returns the content with the language variables replaced.
	*/
	function ReplaceLanguageVariables($template='')
	{
		// Parse out the language pack variables in the template file
		preg_match_all("/(?siU)(%%LNG_[a-zA-Z0-9_]{1,}%%)/", $template, $matches);

		foreach ($matches[0] as $p => $match) {
			$langvar = str_replace(array('%', 'LNG_'), '', $match);
			$template = str_replace($match, GetLang($langvar), $template);
		}
		unset($matches);
		return $template;
	}

	/**
	* ParseTemplate
	* Loads the template that you pass in. Replaces any placeholders that you set in GlobalAreas and then goes through, looks for language placeholders, request vars, global vars and replaces them all.
	*
	* @param String $templatename The name of the template to load and then display.
	* @param Boolean $return Whether to return the template or just display it. Default is to display it.
	* @param Boolean $recurse Whether to recurse into other templates that are included or not.
	* @param String $fullpath The full path to the template. This is used by the forms to pass in the path to the design.
	*
	* @see GetLang
	* @see GlobalAreas
	* @see _GenerateHelpTip
	* @see User_API::Admin
	* @see ReplaceLanguageVariables
	*
	* @return Mixed Returns the template if specified otherwise it returns nothing.
	*/
	function ParseTemplate($templatename=false, $return=false, $recurse=true, $fullpath=null)
	{
		if (!$templatename) {
			return false;
		}

		if (defined('SENDSTUDIO_DEBUG_MODE') && SENDSTUDIO_DEBUG_MODE) {
			echo '<!-- Template Start: "' . $templatename . "\" -->\n\n";
		}

		$GLOBALS['APPLICATION_URL'] = SENDSTUDIO_APPLICATION_URL;
		$GLOBALS['CHARSET'] = SENDSTUDIO_CHARSET;

		if (!isset($GLOBALS['PAGE'])) {
			$GLOBALS['PAGE'] = get_class($this);
		}

		$temporaryGlobal = array();
		foreach ($this->GlobalAreas as $key => $value) {
			if (isset($GLOBALS[$key])) {
				$temporaryGlobal[$key] = $GLOBALS[$key];
			}
			$GLOBALS[$key] = $value;
		}

		$tpl = GetTemplateSystem();
		if ($templatename === true && !is_null($fullpath)) {
			$tempPath = dirname($fullpath);
			$tempFile = basename($fullpath);
			if (preg_match('/(.*)\..*$/', $tempFile, $matches)) {
				$tempFile = $matches[1];
			}

			$tpl->SetTemplatePath($tempPath);
			$output = $tpl->ParseTemplate($tempFile, true);
		} else {
			$output = $tpl->ParseTemplate($templatename, true);
		}

		foreach ($this->GlobalAreas as $key => $value) {
			if (isset($temporaryGlobal[$key])) {
				$GLOBALS[$key] = $temporaryGlobal[$key];
			} else {
				unset($GLOBALS[$key]);
			}
		}

		if (!$return) {
			print $output;
			return;
		}
		return $output;
	}

	/**
	* PrintHeader
	* Prints out the header info. You can also set menuareas up with the MenuAreas array.
	*
	* @param Boolean $PopupWindow Pass in whether this is a popup window or not. This can be used to work out whether to display the menu at the top of the page.
	* @param Boolean $LoadLanguageFile Whether to automatically load the language file or not.
	* @param Boolean $PrintHeader Should we print the header file? This is separate to the popup window option because subclasses (eg Subscribers_Remove) have their parent print work this out.
	*
	* @see Generate
	* @see MenuAreas
	* @see Subscribers_Remove::Process
	*
	* @uses IEM::getCurrentUser()
	* @uses UserActivityLog_API
	*
	* @return Void Doesn't return anything. Loads up the appropriate header based on the details passed in and that's it.
	*/
	function PrintHeader($PopupWindow=false, $LoadLanguageFile=true, $PrintHeader=true)
	{
		if (IEM::getCurrentUser()) {
			if (isset($_GET['Action']) && isset($_GET['Page']) && $_GET['Page'] != 'Subscribers' && strtolower($_GET['Action']) == 'view') {
				return;
			}
		}

		if ($PrintHeader) {
			header('Content-type: text/html; charset="' . SENDSTUDIO_CHARSET . '"');
		}

		if ($LoadLanguageFile) {
			$this->LoadLanguageFile();
		}

		if ($PopupWindow && $PrintHeader) {
			$this->ParseTemplate('header_popup');
			return;
		}


		if ($PrintHeader) {
			$user = IEM::getCurrentUser();

			IEM::sessionSet('RandomToken', md5(rand()));

			if ($user && $user->enableactivitylog) {
				$activity_api = $this->GetApi('UserActivityLog');
				$user_activity_list = $activity_api->GetActivity();

				if (is_array($user_activity_list) && count($user_activity_list) > 0) {
					$tpl = GetTemplateSystem();
					$tpl->Assign('records', $user_activity_list);
					$GLOBALS['BodyAddons'] = $tpl->ParseTemplate('user_activity_log', true);
				}
			}

			$trial_notification = false;

			if ($user && $user->trialuser) {
				$trial_information = $user->GetTrialInformation();

				if ($trial_information['days_left'] <= 0) {
					$logout_url = SENDSTUDIO_APPLICATION_URL ."/admin/index.php?Page=Logout";
					if (!headers_sent()) header("Location: {$logout_url}");
					echo "<script>window.location='{$logout_url}';</script>";
					exit();
				}

				$customfield_replace_key = array(
					'%%trial_days_current%%',
					'%%trial_days_left%%',
					'%%trial_days_allowed%%'
				);

				$customfield_replace_value = array(
					$trial_information['days_used'] + 1,
					$trial_information['days_left'] - 1,
					$trial_information['days_total']
				);

				$trial_notification = GetLang('AccountUpgradeMessage');
				$trial_notification = str_replace($customfield_replace_key, $customfield_replace_value, $trial_notification);
			}


			$tpl = GetTemplateSystem();
			$tpl->Assign('TrialNotification', $trial_notification);
			$tpl->ParseTemplate('header');

			$this->ShowInfoTip();
		}
	}

	/**
	* PrintFooter
	* Prints out the footer info.
	*
	* There are three slightly different footers.
	*
	* The Footer_Popup file doesn't have the copyright line at the bottom of the page.
	* The main footer (Footer) has the help column on the right & copyright line at the bottom.
	*
	* @param Boolean $PopupWindow Pass in whether this is a popup window or not. This can be used to work out whether to display the copyright info at the bottom of the page.
	* @param Boolean $return Whether to return the template or just print it. By default it is just printed.
	*
	* @return Mixed If return is set to true, this will return the footer. If it's not, this will print out the appropriate footer and that's it.
	*/
	function PrintFooter($PopupWindow=false, $return=false)
	{
		$tpl = GetTemplateSystem();
		if ($PopupWindow) {
			if ($return) {
				return $tpl->ParseTemplate('Footer_Popup', true);
			}
			$tpl->ParseTemplate('Footer_Popup');
		} else {
			$tpl->Assign('random', IEM::sessionGet('RandomToken'));
			if ($return) {
				return $tpl->ParseTemplate('Footer', true);
			}
			$tpl->ParseTemplate('Footer');
		}
	}

	/**
	* GenerateTextMenuLinks
	* Generates the text links at the top of the page - which include the home, "users" or "my account", logout links.
	* This is used by the template system when it prints out the header.
	*
	* If a license key error occurs, then this only shows the settings, logout and help links at the top.
	* It will not trigger an event to occur either (which addons will use to add themselves to the text links).
	*
	* Link areas are arrays which can be manipulated by addons if they are installed/enabled.
	*
	* A menu item can either be singular:
	* <code>
	* $links['area_name'] = array (
	* 	array (
	* 		'text' => 'This is the link to click',
	* 		'link' => 'index.php?Page=MyPage',
	* 		'show' => true,
	* 		'description' => 'This is the long description. It is used for the title tag and only shows when you hover over the link'
	* 	)
	* );
	* </code>
	*
	* or contain a dropdown menu:
	* <code>
	* $links['area_name'] = array (
	* 	'menudetails' => array (
	* 		'width' => 100, // this is the width in pixels for the dropdown menu
	*		'title' => 'What the dropdown menu is called',
	*		'description' => 'Long description which shows up when you hover over the dropdown link',
	* 	),
	* 	array (
	* 		'text' => 'This is the first item in the menu',
	* 		'link' => 'index.php?Page=MyPage',
	* 		'show' => true,
	* 		'description' => 'This is the long description. It is used for the title tag and only shows when you hover over the link'
	* 	),
	* 	array (
	* 		'text' => 'This is the second item in the menu',
	* 		'link' => 'index.php?Page=MyPage',
	* 		'show' => true,
	* 		'description' => 'This is the long description. It is used for the title tag and only shows when you hover over the link'
	* 	),
	* );
	* </code>
	*
	* The 'menudetails' are used to create the dropdown menu base.
	*
	* @see InterspireTemplate::IEM_Menu
	*
	* @return String Returns the string to display up the top.
	*
	* @uses EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS
	*/
	static function GenerateTextMenuLinks()
	{
		$user = IEM::getCurrentUser();

		$lke = IEM::sessionGet('LicenseError', false);

		// if there's an error with the lk, then just show the links to the settings page, logout & help.
		if ($lke && (!isset($_GET['Page']) || strtolower($_GET['Page']) == 'settings')) {
			$textlinks = '';
			if ($user->HasAccess('Settings')) {
				$textlinks .= '<a href="index.php?Page=Settings" class="MenuText" title="' . GetLang('Menu_Settings_Description') . '">' . GetLang('Settings') . '</a>|';
			}
			$textlinks .= '<a href="index.php?Page=Logout" class="MenuText" title="' . GetLang('Menu_Logout_Description') . '">' . GetLang('Logout') . '</a>|';
			$textlinks .= '<a href="JavaScript:LaunchHelp(\''.IEM::enableInfoTipsGet().'\');" class="MenuText" title="' . GetLang('Menu_Help_Description') . '">' . GetLang('ShowHelp') . '</a>';
			return $textlinks;
		}

		$links = array();

		$links['home'] = array (
			array (
				'text' => GetLang('Home'),
				'link' => 'index.php',
				'show' => true,
				'description' => GetLang('Menu_Home_Description'),
			)
		);

		$links['templates'] = array (
			'menudetails' => array (
				'width' => '185',
				'title' => GetLang('Menu_Templates'),
				'description' => GetLang('Menu_Templates_Description'),
			),
			array (
				'text' => GetLang('Menu_Templates_Manage'),
				'link' => 'index.php?Page=Templates&amp;Action=Manage',
				'show' => $user->HasAccess('Templates', 'Manage'),
				'description' => GetLang('Menu_Templates_Description'),
				'image' => 'templates_view.gif'
			),
			array (
				'text' => GetLang('Menu_Templates_Create'),
				'link' => 'index.php?Page=Templates&amp;Action=Create',
				'show' => $user->HasAccess('Templates', 'Create'),
				'description' => GetLang('Menu_Templates_Create_Description'),
				'image' => 'templates_add.gif'
			),
			array (
				'text' => GetLang('Menu_Templates_Manage_BuiltIn'),
				'link' => 'index.php?Page=Templates&amp;Action=BuiltIn',
				'show' => $user->HasAccess('Templates', 'BuiltIn'),
				'description' => GetLang('Menu_Templates_Manage_Description'),
				'image' => 'templates_builtin.gif'
			),

		);

		$links['forms'] = array (
			'menudetails' => array(
				'width' => '145',
				'title' => GetLang('Menu_Forms'),
				'show' => $user->HasAccess('Forms'),
				'description' => GetLang('Menu_Website_Forms_Description'),
			),
			array (
				'text' => GetLang('Menu_Website_Forms'),
				'link' => 'index.php?Page=Forms',
				'show' => $user->HasAccess('Forms'),
				'description' => GetLang('Menu_Website_Forms_Description'),
				'image' => 'forms_view.gif'
			),
			array (
				'text' => GetLang('Menu_Create_Form'),
				'link' => 'index.php?Page=Forms&Action=create',
				'show' => $user->HasAccess('Forms','Create'),
				'description' => GetLang('Menu_Create_Form_Description'),
				'image' => 'forms_add.gif'
			),
		);


		if (!$user->isAdmin() && $user->EditOwnSettings()) {
			$links['manageaccount'] = array (
				array (
					'text' => GetLang('MyAccount'),
					'link' => 'index.php?Page=ManageAccount',
					'show' => true,
					'description' => GetLang('Menu_Users_Own_Description'),
				)
			);
		}

		if ($user->isUserAdmin()) {
			$add_user_disabled = false;
			$temp              = get_available_user_count();
			
			if ($temp['normal'] == 0 && $temp['trial'] == 0) {
				$add_user_disabled = true;
			}

			$links['users'] = array (
				'menudetails' => array(
					'width' => '140',
					'title' => GetLang('Menu_UsersGroups'),
					'show' => true,
					'description' => GetLang('MenuDescription_UsersGroups'),
				),
				array (
					'text' => GetLang('Menu_UsersGroups_ManageUsers'),
					'link' => 'index.php?Page=Users',
					'show' => true,
				),
				array (
					'text' => GetLang('Menu_UsersGroups_CreateUser'),
					'link' => 'index.php?Page=Users&Action=Add',
					'show' => !$add_user_disabled,
				),
				null,
				array (
					'text' => GetLang('Menu_UsersGroups_ManageGroups'),
					'link' => 'index.php?Page=UsersGroups',
					'show' => true,
				),
				array (
					'text' => GetLang('Menu_UsersGroups_CreateGroup'),
					'link' => 'index.php?Page=UsersGroups&Action=createGroup',
					'show' => true,
				),
			);
		}

		if ($user->Admin()) {
			$links['settings'] = array (
				'menudetails' => array(
					'width' => '145',
					'title' => GetLang('Settings'),
					'show' => true,
					'description' => GetLang('Menu_Settings_Description'),
				),
				array (
					'text' => GetLang('ApplicationSettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=1',
					'show' => true,
				),
				array (
					'text' => GetLang('EmailSettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=2',
					'show' => true,
				),
				array (
					'text' => GetLang('BounceSettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=7',
					'show' => true,
				),
				array (
					'text' => GetLang('CreditSettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=3',
					'show' => true,
				),
				array (
					'text' => GetLang('CronSettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=4',
					'show' => true,
				),
				array (
					'text' => GetLang('PrivateLabelSettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=8',
					'show' => (defined('APPLICATION_SHOW_WHITELABEL_MENU')? constant('APPLICATION_SHOW_WHITELABEL_MENU') : true),
				),
				array (
					'text' => GetLang('SecuritySettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=5',
					'show' => true,
				),
				array (
					'text' => GetLang('AddonsSettings_Heading'),
					'link' => 'index.php?Page=Settings&Tab=6',
					'show' => true,
				),
			);

			$links['tools'] = array (
				'menudetails' => array (
					'width' => '140',
					'title' => GetLang('Menu_Tools'),
					'description' => GetLang('Menu_Tools_Description')
				),
				array (
					'text' => GetLang('Menu_Tools_SystemInformation'),
					'link' => 'index.php?Page=Settings&Action=SystemInfo',
					'show' => true,
					'description' => GetLang('Menu_Tools_SystemInformation_Description'),
				)
			);
		}

		$links['logout'] = array (
			array (
				'text' => GetLang('Logout'),
				'link' => 'index.php?Page=Logout',
				'description' => GetLang('Menu_Logout_Description'),
			)
		);

		$links['help'] = array (
			array (
				'text' => GetLang('ShowHelp'),
				'link' => 'JavaScript:LaunchHelp(\''.IEM::enableInfoTipsGet().'\');',
				'description' => GetLang('Menu_Help_Description')
			)
		);

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS();
			$tempEventData->data = &$links;
			$tempEventData->trigger();

			unset($tempEventData);
		/**
		 * -----
		 */

		$textlinks = '';

		/**
		 * Go through the link areas and work out if it's a normal link or whether it should be a menu.
		 * If it has a 'menudetails' section set, then it's going to be a dropdown menu.
		 * The menudetails array must contain:
		 * - title (used as the link name)
		 * - description (used as the link title)
		 * - width of the menu (in px)
		 *
		 * If those details are found, then it's turned into a dropdown menu rather than just a link.
		 */
		foreach ($links as $link_area => $sublinks) {
		    $has_submenu = false;
			
			if (isset($sublinks['menudetails'])) {
				$has_submenu = true;
			}

			if (isset($sublinks['show']) && $sublinks['show'] == false) {
				continue;
			}

			if (!$has_submenu) {
				$link_details  = array_pop($sublinks);
				$textlinks    .= '<a href="' . $link_details['link'] . '" class="MenuText" title="' . htmlspecialchars($link_details['description'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '">' . htmlspecialchars($link_details['text'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</a>|';
				
				continue;
			}

			$id = ucfirst($link_area);

			$width = '100';
			$width = (int) $sublinks['menudetails']['width'];

			$sublink = '<a href="#" title="' . htmlspecialchars($links[$link_area]['menudetails']['description'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '" id="' . $id . 'MenuButton" class="PopDownMenu MenuText">' . htmlspecialchars($links[$link_area]['menudetails']['title'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '<img src="images/arrow_down_white.gif" border="0" style="position: relative;" /></a><div id="' . $id . 'Menu" style="display: none; width: ' . $width . 'px;" class="DropDownMenu DropShadow">' .  '<ul>';

			unset($sublinks['menudetails']);

			foreach ($sublinks as $link_details) {
    			if (is_null($link_details)) {
    			    $sublink .= '<li><hr /></li>';
    			    
    			    continue;
    			}
			    
				if (isset($link_details['show']) && $link_details['show'] == false) {
					continue;
				}

				$sublink .= '<li><a href="' . $link_details['link'] . '">' . $link_details['text'] . '</a></li>';
			}
			
			$sublink   .= '</ul></div>|';
			$textlinks .= $sublink;
		}
		
		$textlinks = rtrim($textlinks, '|');
		
		return $textlinks;
	}

	/**
	* GenerateMenuLinks
	* Prints out the menu based on which options a user has access to.
	*
	* @see User::HasAccess
	*
	* @return String Returns the generated menu with the necessary options.
	*
	* @uses EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS
	*/
	static function GenerateMenuLinks()
	{
		$user = IEM::getCurrentUser();

		$menuItems = array (
			'contactlist_button' => array (
				array (
					'text' => GetLang('Menu_MailingLists_Manage'),
					'link' => 'index.php?Page=Lists',
					'show' => $user->HasAccess('Lists'),
					'description' => GetLang('Menu_MailingLists_Description'),
					'image' => 'lists_view.gif'
				),
				array (
					'text' => GetLang('Menu_MailingLists_Create'),
					'link' => 'index.php?Page=Lists&amp;Action=create',
					'show' => $user->HasAccess('Lists', 'Create'),
					'description' => GetLang('Menu_MailingLists_Create_Description'),
					'image' => 'lists_add.gif'
				),
				array (
					'text' => GetLang('Menu_MailingLists_CustomFields'),
					'link' => 'index.php?Page=CustomFields',
					'show' => $user->HasAccess('CustomFields'),
					'description' => GetLang('Menu_MailingLists_CustomFields_Description'),
					'image' => 'customfields.gif'
				),
				array (
					'text' => GetLang('Menu_MailingLists_Bounce'),
					'link' => 'index.php?Page=Bounce',
					'show' => $user->HasAccess('Lists', 'Bounce'),
					'description' => GetLang('Menu_MailingLists_Bounce_Description'),
					'image' => 'lists_process_bounces.gif'
				),
				array (
					'text' => GetLang('Menu_Segments_Manage'),
					'link' => 'index.php?Page=Segment',
					'show' => $user->HasAccess('Segments'),
					'description' => GetLang('Menu_Segment_Description'),
					'image' => 'contacts_segment_manage.gif'
				),
			),
			'contact_button' => array (
				array (
					'text' => GetLang('Menu_Members_Manage'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Manage&amp;Lists=any',
					'show' => $user->HasAccess('Subscribers', 'Manage'),
					'description' => GetLang('Menu_Members_Description'),
					'image' => 'contacts_view.gif'
				),
				array (
					'text' => GetLang('Menu_Search_Contacts'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Manage&amp;SubAction=AdvancedSearch',
					'show' => $user->HasAccess('Subscribers', 'Manage'),
					'description' => GetLang('Menu_Contacts_Search_Description'),
					'image' => 'contacts_search.gif'
				),
				array (
					'text' => GetLang('Menu_Members_Add'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Add',
					'show' => $user->HasAccess('Subscribers', 'Add'),
					'description' => GetLang('Menu_Members_Add_Description'),
					'image' => 'contacts_add.gif'
				),
				array (
					'text' => GetLang('Menu_Members_Import'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Import',
					'show' => $user->HasAccess('Subscribers', 'Import'),
					'description' => GetLang('Menu_Members_Import_Description'),
					'image' => 'contacts_import.gif'
				),
				array (
					'text' => GetLang('Menu_Members_Export'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Export',
					'show' => $user->HasAccess('Subscribers', 'Export'),
					'description' => GetLang('Menu_Members_Export_Description'),
					'image' => 'contacts_export.gif'
				),
				array (
					'text' => GetLang('Menu_Members_Remove'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Remove',
					'show' => $user->HasAccess('Subscribers', 'Delete'),
					'description' => GetLang('Menu_Members_Remove_Description'),
					'image' => 'contacts_remove.gif'
				),
				array (
					'text' => GetLang('Menu_Members_Banned_Manage'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Banned',
					'show' => $user->HasAccess('Subscribers', 'Banned'),
					'description' => GetLang('Menu_Members_Banned_Manage_Description'),
					'image' => 'contacts_suppress_view.gif'
				),
				array (
					'text' => GetLang('Menu_Members_Banned_Add'),
					'link' => 'index.php?Page=Subscribers&amp;Action=Banned&amp;SubAction=Add',
					'show' => $user->HasAccess('Subscribers', 'Banned'),
					'description' => GetLang('Menu_Members_Banned_Add_Description'),
					'image' => 'contacts_suppress_add.gif'
				),
			),
			'newsletter_button' => array (
				array (
					'text' => GetLang('Menu_Newsletters_Manage'),
					'link' => 'index.php?Page=Newsletters&amp;Action=Manage',
					'show' => $user->HasAccess('Newsletters', 'Manage'),
					'description' => GetLang('Menu_Newsletters_Description'),
					'image' => 'newsletters_view.gif'
				),
				array (
					'text' => GetLang('Menu_Newsletters_Create'),
					'link' => 'index.php?Page=Newsletters&amp;Action=Create',
					'show' => $user->HasAccess('Newsletters', 'Create'),
					'description' => GetLang('Menu_Newsletters_Create_Description'),
					'image' => 'newsletters_add.gif'
				),
				array (
					'text' => GetLang('Menu_Newsletters_Send'),
					'link' => 'index.php?Page=Send',
					'show' => $user->HasAccess('Newsletters', 'Send'),
					'description' => GetLang('Menu_Newsletters_Send_Description'),
					'image' => 'newsletters_send.gif'
				),
				array (
					'text' => GetLang('Menu_Images_Manage'),
					'link' => 'index.php?Page=ImageManager',
					'show' => true,
					'description' => GetLang('Menu_Images_Manage_Description'),
					'image' => 'pictures.png'
				),
				array (
					'text' => GetLang('Menu_Newsletters_ManageSchedule'),
					'link' => 'index.php?Page=Schedule',
					'show' => $user->HasAccess('Newsletters', 'Send'),
					'description' => GetLang('Menu_Newsletters_ManageSchedule_Description'),
					'image' => 'newsletters_queue.gif'
				),
			),
			'autoresponder_button' => array (
				array (
					'text' => GetLang('Menu_Autoresponders_Manage'),
					'link' => 'index.php?Page=Autoresponders',
					'show' => $user->HasAccess('Autoresponders', 'Manage'),
					'description' => GetLang('Menu_Autoresponders_Description'),
					'image' => 'autoresponders_view.gif'
				),
				array (
					'text' => GetLang('Menu_Autoresponders_Create'),
					'link' => 'index.php?Page=Autoresponders&amp;Action=Create',
					'show' => $user->HasAccess('Autoresponders', 'Create'),
					'description' => GetLang('Menu_Autoresponders_Create_Description'),
					'image' => 'newsletters_add.gif'
				),
				array (
					'text' => GetLang('Menu_TriggerEmails'),
					'link' => 'index.php?Page=TriggerEmails',
					'show' => ($user->HasAccess('TriggerEmails') && check('Triggermails')),
					'description' => GetLang('Menu_TriggerEmails_Description'),
					'image' => 'triggeremails.gif'
				),
			),

			/*
			 'customfields_button' => array(
				array (
					'text' => GetLang('Menu_MailingLists_CustomFields'),
					'link' => 'index.php?Page=CustomFields',
					'show' => $user->HasAccess('CustomFields'),
					'description' => GetLang('Menu_MailingLists_CustomFields_Description'),
					'image' => 'customfields.gif'
				),
				array(
					'text' => GetLang('Menu_CustomFields_Create'),
					'link' => 'index.php?Page=CustomFields&Action=Create',
					'show' => $user->HasAccess('CustomFields', 'Create'),
					'description' => GetLang('Menu_CustomFields_Create_Description'),
					'image' => 'customfields_add.gif'
				)
			), */

			'statistics_button' => array (
				array (
					'text' => GetLang('Menu_Statistics_Newsletters'),
					'link' => 'index.php?Page=Stats',
					'show' => $user->HasAccess('Statistics', 'Newsletter'),
					'description' => GetLang('Menu_Statistics_Description'),
					'image' => 'newsletters_view.gif'
				),
				array (
					'text' => GetLang('Menu_Statistics_Autoresponders'),
					'link' => 'index.php?Page=Stats&amp;Action=Autoresponders',
					'show' => $user->HasAccess('Statistics', 'Autoresponder'),
					'description' => GetLang('Menu_Autoresponders_Statistics_Description'),
					'image' => 'autoresponders_view.gif'
				),
				array (
					'text' => GetLang('Menu_Statistics_TriggerEmails'),
					'link' => 'index.php?Page=Stats&amp;Action=TriggerEmails',
					'show' => ($user->HasAccess('Statistics', 'TriggerEmails') && check('Triggermails')),
					'description' => GetLang('Menu_Statistics_TriggerEmails_Description'),
					'image' => 'triggeremails_view.gif'
				),
				array (
					'text' => GetLang('Menu_Statistics_Lists'),
					'link' => 'index.php?Page=Stats&amp;Action=List',
					'show' => $user->HasAccess('Statistics', 'List'),
					'description' => GetLang('Menu_Statistics_Lists_Description'),
					'image' => 'lists_view.gif'
				),
				array (
					'text' => GetLang('Menu_Statistics_Users'),
					'link' => 'index.php?Page=Stats&amp;Action=User',
					'show' => $user->HasAccess('Statistics', 'User'),
					'description' => GetLang('Menu_Statistics_Users_Description'),
					'image' => 'user.gif'
				),
			),
		);

		/**
		 * Trigger events
		 */
			$tempEventData = new EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS();

			$tempEventData->data = &$menuItems;


			$tempEventData->trigger();

			unset($tempEventData);
		/**
		 * -----
		 */

		// Generate the tabs
		$menu = '';

		// {{{ New menu
		$menu .= "\n".'<div id="headerMenu">'."\n".'<ul>'."\n";

		$selected_class = ' dropselected';

		/**
		 * Work out the current page.
		 * If it has a query string, tack it on.
		 */
		$current_page = 'index.php';
		if (isset($_SERVER['QUERY_STRING'])) {
			$current_page .= '?' . strtolower($_SERVER['QUERY_STRING']);
		}

		/**
		 * Work out the highlighted menu item.
		 * It goes through the menu one by one to see if the current page is a link there somewhere.
		 */
		$highlighted_menu_button = '';
		foreach ($menuItems as $image => $link) {
			if (is_array($link)) {
				foreach ($link as $id => $sub) {
					$sub['link'] = html_entity_decode(strtolower($sub['link']));

					/**
					 * If the link is a built in one, it won't have http at the start
					 * and the same url won't appear under two tabs (they will have different urls).
					 *
					 * So if we find a built in link in the current menu, then we can break out of everything.
					 */
					if (strpos($current_page, $sub['link']) !== false) {
						$highlighted_menu_button = $image;
						break 2;
					}

					/**
					 * Addons have full urls to their links, so strip the application url off to see if that now matches.
					 * Addons can also put multiple items into different menus (eg under email campaigns & under stats)
					 * so if we find a match, there could be a better one later on under another menu.
					 *
					 * So don't break out of both loops, just out of this menu loop.
					 */
					$cutdown_link = str_replace(SENDSTUDIO_APPLICATION_URL . '/admin/', '', $sub['link']);
					if (strpos($current_page, $cutdown_link) !== false) {
						$highlighted_menu_button = $image;
						break;
					}
				}
			}
		}

		$menubar_text = array (
			'contactlist_button' => GetLang('Menu_ContactLists'),
			'contact_button' => GetLang('Menu_Contacts'),
			'newsletter_button'	=> GetLang('Menu_EmailCampaigns'),
			'surveys_button' => GetLang('Menu_Surveys'),
			'autoresponder_button' => GetLang('Menu_Autoresponders'),
			'statistics_button' => GetLang('Menu_Statistics'),
		);

		$imagesDir = dirname(__FILE__).'/../images';

		foreach ($menuItems as $image => $link) {
			// If the menu has sub menus, display them
			if (is_array($link)) {
				$first = true;
				$shown = false;
				foreach ($link as $id => $sub) {
					$show = false;

					/**
					* is 'show' is an array, then an addon is trying to get us to check permissions.
					* The array will look like:
					*
					* $sub['show'] = array (
					* 	'CheckAccess' => 'FunctionToCall',
					*	'Permissions' => array('Addon_Id', 'Create')
					* );
					*
					* Eg, 'HasAccess' or 'Admin' (if only admin users should see this).
					* The second parameter (if present) is the specific permission to check.
					*
					* That way the option only shows up if the $user->HasAccess('Addon_Id', 'Create') permission returns true.
					*
					* If we try to run an invalid method, then the option doesn't show up (and won't trigger any errors).
					*/
					if (is_array($sub['show'])) {
						$method_to_call = $sub['show']['CheckAccess'];
						$perms_to_check = array();
						if (isset($sub['show']['Permissions'])) {
							$perms_to_check = $sub['show']['Permissions'];
						}
						if (method_exists($user, $method_to_call)) {
							$show = call_user_func_array(array($user, $method_to_call), $perms_to_check);
						}
					} else {
						$show = $sub['show'];
					}

					// If the child is forbidden by law, hide it
					if (!$show) {
						continue;
					} else {
						$shown = true;
					}
					// If its the first born, give it an image
					if ($first) {
						$target = '';
						if (isset($sub['target'])) {
							$target = ' target="' . $sub['target'] . '"';
						}

						$menu_selected = false;

						if ($image === $highlighted_menu_button) {
							$menu_selected = true;
						}

						$image_url = 'images/' . $image;
						$image_ext = '';


						if ($menu_selected) {
							$menu .= '<li class="dropdown ' . $selected_class . '">';
							$menu_select = '_on';
							$menu_select_bg = 'style="background-image: url(images/mnu_on_middle.gif); color: #fff;"';
						} else {
							$menu .= '<li class="dropdown">';
							$menu_select = '';
							$menu_select_bg = 'style="background-image: url(images/mnu_middle.gif);"';
						}

						$image_url .= $image_ext;
						$menu .= '<a  href="'.$sub['link'].'"' . $target . '>';

						// TO DO CHANGE FROM IMAGES TO TEXT HERE..
						$title = '';
						if (isset($sub['description'])) {
							$title = 'title="'.$sub['description'].'"';
						}

						$menu .= '<table cellspacing="0"><tbody><tr>';
						$menu .= '<td class="dropdown-tab-left"><img height="28" width="12" src="images/mnu' . $menu_select . '_left.gif"/></td>';
						$menu .= '<td class="dropdown-tab-icon" ' . $menu_select_bg . '><img height="16" width="16" src="images/mnu_' . $image . '.gif"/></td>';
						$menu .= '<td class="dropdown-tab-label" ' . $menu_select_bg . '><span>' . $menubar_text[$image] . '</span></td>';
						$menu .= '<td class="dropdown-tab-arrow" ' . $menu_select_bg . '><img height="4" width="8" src="images/mnu' . $menu_select . '_arrow.gif"/></td>';
						$menu .= '<td class="dropdown-tab-right"><img height="28" width="12" src="images/mnu' . $menu_select . '_right.gif"/></td>';
						$menu .= '</tr></tbody></table>';
						//$menu .= '<img '.$attr.' src="'.$image_url.'" border="0" hspace="2" alt="" ' . $title . ' height="' . $height . '" width="' . $width . '">+++';

						$menu .= '</a>'."\n";
						if (count($link) > 1) {
							$menu .= '<ul>'."\n";
						}
						$first = false;
					}
					// If it's not an only child, don't show the first item as a child
					if (count($link) > 1) {
						$target = '';
						if (isset($sub['target'])) {
							$target = ' target="' . $sub['target'] . '"';
						}

						$icon = 'images/blank.gif';
						if (isset($sub['image'])) {
							$icon = 'images/' . $sub['image'];
						}

						if (isset($sub['image_url'])) {
							$icon = $sub['image_url'];
						}

						$menu .= '<li><a style="background-image: url('.$icon.');" class="menu_mnuStats" href="'.$sub['link'].'" '.$target.'><strong>'.$sub['text'].'</strong><span>'.$sub['description'].'</span></a></li>'."\n";
					}
				}
				if ($shown) {
					if (count($link) > 1) {
						$menu .= '</ul>'."\n";
					}
					$menu .= '</li>'."\n";
				}
			}
		}
		$menu .= '</ul></div>'."\n";

		return $menu;
	}

	/**
	* ShowInfoTip
	* Shows info tips based on whether the user wants to see them or not (and if they are logged in).
	* If we are on the send page and cron jobs are not enabled, we always show the cron tip.
	* If we are not on the send page, or cron jobs are enabled, we show a random tip.
	*
	* This function is called on every page, so we can use it to reference CleanupOldQueues
	* This is done before we check whether to show info tips or not.
	*
	* @see _CleanupOldQueues
	* @see GetUser
	* @see User_API::InfoTips
	*
	* @return Void Prints out the tip, doesn't return anything.
	*/
	function ShowInfoTip()
	{
		$user = GetUser();
		if (!$user) {
			return; // if we're not logged in we can't show anything.
		}

		if (IEM::sessionGet('LicenseError', false)) {
			return;
		}

		$page = 'index';
		if (isset($_GET['Page'])) {
			$page = strtolower($_GET['Page']);
		}

		if ($page == 'index') {
			return;
		}

		$action = (isset($_GET['Action'])) ? (strtolower($_GET['Action'])) : 'manage';

		$this->_CleanupOldQueues($page, $action);

		if (!$user->InfoTips()) {
			return;
		}

		$this->LoadLanguageFile('InfoTips');

		$tipnumber = $tip = false;

		if ($page == 'send' && (!SENDSTUDIO_CRON_ENABLED || SENDSTUDIO_CRON_SEND <= 0)) {
			$GLOBALS['TipIntro'] = GetLang('Infotip_Cron_Intro');
			$GLOBALS['Tip'] = GetLang('Infotip_Cron_Details');
			$GLOBALS['ReadMore'] = '';
			$tipnumber = true;
		}

		$context_helptips = array_keys($GLOBALS['ContextSensitiveTips']);

		if (in_array($page, $context_helptips)) {
			$page_keys = array_keys($GLOBALS['ContextSensitiveTips'][$page]);
			if (in_array($action, $page_keys)) {
				if (isset($GLOBALS['ContextSensitiveTips'][$page][$action])) {
					$tipsize = sizeof($GLOBALS['ContextSensitiveTips'][$page][$action]);
					if ($tipsize > 1) {
						$tipnumber = mt_rand(1, $tipsize);
					} else {
						$tipnumber = 1;
					}

					$tip = $GLOBALS['ContextSensitiveTips'][$page][$action][$tipnumber-1];
				} else {
					if (sizeof($page_keys) == 1) {
						$tipsize = sizeof($GLOBALS['ContextSensitiveTips'][$page]);
						if ($tipsize > 1) {
							$tipnumber = mt_rand(1, $tipsize);
						} else {
							$tipnumber = 1;
						}

						$tip = $GLOBALS['ContextSensitiveTips'][$page][$tipnumber-1];
					}
				}
			}

			if ($tip) {
				$GLOBALS['TipIntro'] = GetLang('Infotip_' . $tip . '_Intro');
				$GLOBALS['Tip'] = GetLang('Infotip_' . $tip . '_Details');
				if (defined('LNG_Infotip_' . $tip . '_ReadMoreLink')) {
					$GLOBALS['ReadMoreLink'] = GetLang('Infotip_' . $tip . '_ReadMoreLink');
					$GLOBALS['ReadMoreInfo'] = GetLang('Infotip_' . $tip . '_ReadMore');

					$GLOBALS['InfoTip_ReadMore'] = $this->ParseTemplate('InfoTips_ReadMore', true, false);
				}
			}
		}

		$GLOBALS['ReadMoreLink'] = '';

		if (!$tipnumber) {
			$GLOBALS['TipIntro'] = GetLang('Infotip_Intro');
			$tipnumber = mt_rand(1, Infotip_Size);

			$GLOBALS['ReadMoreInfo'] = GetLang('Infotip_ReadMore');

			$GLOBALS['Extra'] = ':';
			$GLOBALS['Tip'] = GetLang('Infotip_' . $tipnumber . '_Intro');
			$GLOBALS['ReadMoreLink'] .= GetLang('Infotip_' . $tipnumber . '_ReadMoreLink');
			$GLOBALS['TipNumber'] = $tipnumber;
			$GLOBALS['InfoTip_ReadMore'] = $this->ParseTemplate('InfoTips_ReadMore', true, false);
		}

		$this->ParseTemplate('InfoTips');
	}

	/**
	* _GenerateHelpTip
	* Generates a help tip dynamically.
	* <b>Example</b>
	* If you pass in 'LNG_HLP_Status' - the tiptitle is 'LNG_Status', the description is 'LNG_HLP_Status'.
	*
	* @param String $tipname The name of the tip to create. This will get the variable from the language file and replace it and the title as necessary. The helptip title is the tipname.
	*
	* @see GetRandomId
	* @see ParseTemplate
	*
	* @return String The help tip that is generated.
	*/
	function _GenerateHelpTip($tipname=null, $tiptitle=null, $tipdescription=null)
	{
		if ($tipname === null && $tiptitle === null && $tipdescription === null) {
			return false;
		}

		if ($tipname !== null) {
			$tipname = str_replace(array('%%', 'LNG_'), '', $tipname);
			$tiptitle = str_replace('HLP_', '', $tipname);
			$tiptitle = GetLang($tiptitle);

			$tipdescription = GetLang($tipname);
		}

		$rand = $this->GetRandomId();

		$helptip = '<img onMouseOut="HideHelp(\'' . $rand . '\');" onMouseOver="ShowHelp(\'' . $rand . '\', \'' . $tiptitle . '\', \'' . $tipdescription . '\');" src="images/help.gif" width="24" height="16" border="0"><div style="display:none" id="' . $rand . '"></div>';
		return $helptip;
	}

	/**
	* GetRandomId
	* Generates a random id for tooltips to use.
	* Stores any random helptip id's in this->_RandomStrings so we can make sure there are no duplicates.
	*
	* @see _GenerateHelpTip
	* @see _RandomStrings
	*
	* @return String Returns a string to use as the random tooltip name.
	*/
	function GetRandomId()
	{
		$chars = array();
		foreach (range('a', 'z') as $p => $char) {
			$chars[] = $char;
		}
		foreach (range('A', 'Z') as $p => $char) {
			$chars[] = $char;
		}
		foreach (range('0', '9') as $p => $char) {
			$chars[] = $char;
		}

		while (true) {
			$rand = 'ss';
			$max = sizeof($chars) - 1;
			while (strlen($rand) < 10) {
				$randchar = rand(0, $max);
				$rand .= $chars[$randchar];
			}

			if (!in_array($rand, $this->_RandomStrings)) {
				$this->_RandomStrings[] = $rand;
				break;
			}
		}
		return $rand;
	}

	/**
	* SetupPaging
	* Sets up the paging header with page numbers (using $this->_PagesToShow), sets up the 'Next/Back' links, 'First Page/Last Page' links and so on - based on how many records there are, which page you are on currently and the number of records to display per page.
	* Gets settings from the session if it can (based on what you've done previously).
	* Sets the $GLOBALS['DisplayPage'] and $GLOBALS['PerPageDisplayOptions'] so the template can be parsed properly.
	*
	* @param Int $numrecords The number of records to calculate pages for
	* @param Int $currentpage The current page that we're on (so we can highlight the right one)
	* @param Mixed $perpage Number of records per page we're going to show so we can calculate the right page.
	*
	* @see _PagesToShow
	* @see GetCurrentPage
	* @see GetPerPage
	* @see ParseTemplate
	* @see SetCurrentPage
	*
	* @return Void Doesn't return anything. Places the paging in global variables GLOBALS['Paging'] and GLOBALS['PagingBottom']
	*/
	function SetupPaging($numrecords=0, $currentpage=1, $perpage=20)
	{
		/**
		 * Work out which page we are now on
		 */
			$page_type = '';
			if (isset($GLOBALS['PAGE'])) {
				$page_type = $GLOBALS['PAGE'];
			} elseif (isset($_GET['Page'])) {
				$page_type = strtolower($_GET['Page']);
			} elseif (isset($_GET['page'])) {
				$page_type = $_GET['page'];
			}
		/**
		 * -----
		 */

		$display_settings['NumberToShow'] = $this->GetPerPage();

		$PerPageDisplayOptions = '';
		$all_tok = '(' . GetLang('Paging_All') . ')';
		foreach (array('5', '10', '20', '30', '50', '100') as $p => $numtoshow) {
			$PerPageDisplayOptions .= '<option value="' . $numtoshow . '"';
			if ($numtoshow == $display_settings['NumberToShow']) {
				$PerPageDisplayOptions .= ' SELECTED';
			}
			$fmt_numtoshow = $this->FormatNumber($numtoshow);

			$PerPageDisplayOptions .= '>' . $fmt_numtoshow . '</option>';
		}
		$GLOBALS['PerPageDisplayOptions'] = $PerPageDisplayOptions;

		if (!$numrecords || $numrecords < 0) {
			$GLOBALS['PagingBottom'] = '<br />';
			$GLOBALS['Paging'] = '<br />';
			return false;
		}

		if ($currentpage < 1) {
			$currentpage = 1;
		}

		if ($perpage < 1 && $perpage != 'all') {
			$perpage = 10;
		}

		$num_pages = 1;
		if ($perpage != 'all') {
			$num_pages = ceil($numrecords / $perpage);
		}

		if ($this->GetCurrentPage() > $num_pages) {
			$this->SetCurrentPage($num_pages);
		}

		if ($currentpage > $num_pages) {
			// this case should only trigger if the number records in the result set have reduced (e.g. been deleted)
			// so we need to take them to the highest page number that still has results.
			$location = SENDSTUDIO_APPLICATION_URL . '/admin/index.php?' . $_SERVER['QUERY_STRING'];
			$location = preg_replace('/DisplayPage=\d+/i', '', $location);
			$location .= '&DisplayPage=' . $num_pages;
			echo "<script> window.location.href = '" . $location . "'; </script>\n";
			exit();
		}

		$prevpage = ($currentpage > 1) ? ($currentpage - 1) : 1;
		$nextpage = (($currentpage+1) > $num_pages) ? $num_pages : ($currentpage+1);

		$sortinfo = $this->GetSortDetails();

		$direction = $sortinfo['Direction'];
		$sort = $sortinfo['SortBy'];
		$sortdetails = '&SortBy=' . $sort . '&Direction=' . $direction;

		$string = '(' . GetLang('Page') . ' ' . $this->FormatNumber($currentpage) . ' ' . GetLang('Of') . ' ' . $this->FormatNumber($num_pages) . ')&nbsp;&nbsp;&nbsp;&nbsp;';

		$display_page_name = 'DisplayPage';
		if (isset($GLOBALS['PPDisplayName'])) {
			$display_page_name .= $GLOBALS['PPDisplayName'];
		}

		if ($currentpage > 1) {
			$string .= '<a href="index.php?Page=' . $page_type . $sortdetails . '&' . $display_page_name . '=1" title="' . GetLang('GoToFirst') . '">&laquo;</a>&nbsp;|&nbsp;';
			$string .= '<a href="index.php?Page=' . $page_type . $sortdetails . '&' . $display_page_name . '=' . $prevpage . '">' . GetLang('PagingBack') . '</a>&nbsp;|';
		} else {
			$string .= '&laquo;&nbsp;|&nbsp;';
			$string .= GetLang('PagingBack') . '&nbsp;|';
		}

		if ($num_pages > $this->_PagesToShow) {
			$start_page = $currentpage - (floor($this->_PagesToShow/2));
			if ($start_page < 1) {
				$start_page = 1;
			}

			$end_page = $currentpage + (floor($this->_PagesToShow/2));
			if ($end_page > $num_pages) {
				$end_page = $num_pages;
			}

			if ($end_page < $this->_PagesToShow) {
				$end_page = $this->_PagesToShow;
			}

			$pagestoshow = ($end_page - $start_page);
			if (($pagestoshow < $this->_PagesToShow) && ($num_pages > $this->_PagesToShow)) {
				$start_page = ($end_page - $this->_PagesToShow+1);
			}

		} else {
			$start_page = 1;
			$end_page = $num_pages;
		}

		for ($pageid = $start_page; $pageid <= $end_page; $pageid++) {
			if ($pageid > $num_pages) {
				break;
			}

			$string .= '&nbsp;';
			if ($pageid == $currentpage) {
				$string .= '<b>' . $pageid . '</b>';
			} else {
				$string .= '<a href="index.php?Page=' . $page_type . $sortdetails . '&' . $display_page_name . '=' . $pageid . '">' . $pageid . '</a>';
			}
			$string .= '&nbsp;|';
		}

		if ($currentpage == $num_pages) {
			$string .= '&nbsp;' . GetLang('PagingNext') . '&nbsp;|';
			$string .= '&nbsp;&raquo;';
		} else {
			$string .= '&nbsp;<a href="index.php?Page=' . $page_type . $sortdetails . '&' . $display_page_name . '=' . $nextpage . '">' . GetLang('PagingNext') . '</a>&nbsp;|';
			$string .= '&nbsp;<a href="index.php?Page=' . $page_type . $sortdetails . '&' . $display_page_name . '=' . $num_pages . '" title="' . GetLang('GoToLast') . '">&raquo;</a>';
		}

		$GLOBALS['DisplayPage'] = $string;

		if ($perpage != 'all' && ($perpage > $this->_PagingMinimum && $numrecords > $perpage)) {
			$paging_bottom = $this->ParseTemplate('Paging_Bottom', true, false);
		} else {
			$paging_bottom = '<br />';
		}

		$GLOBALS['PagingBottom'] = $paging_bottom;
	}

	/**
	* FormatNumber
	* Formats the number passed in according to language variables and returns the value.
	*
	* @param Int $number Number to format
	* @param Int $decimalplaces Number of decimal places to format to
	*
	* @see GetLang
	*
	* @return String The number formatted
	*/
	function FormatNumber($number=0, $decimalplaces=0)
	{
		return number_format((float)$number, $decimalplaces, GetLang('NumberFormat_Dec'), GetLang('NumberFormat_Thousands'));
	}

	/**
	* PrintDate
	* Prints the date according to the language variables and returns the string value.
	* Uses AdjustTime to convert from server time to local user time before displaying.
	*
	* @param Int $timestamp Timestamp to print.
	* @param String $dateformat The date format you want to print rather than the language variable DateFormat
	*
	* @see LNG_DateFormat
	* @see GetLang
	* @see AdjustTime
	*
	* @return String This will return the date formatted, adjusted for the users timezone.
	*/
	function PrintDate($timestamp=0, $dateformat=false)
	{
		if ($dateformat) {
			return AdjustTime($timestamp, false, $dateformat, true);
		}

		/*
		$now = AdjustTime();
		$seconds = $now % 86400; // find number of seconds that today has had so far, so we can remove it.
		$today = $now - $seconds;

		$yesterday = $today - 86400;

		$tomorrow = $today + 86400;

		$two_days = $tomorrow + 86400;

		if ($timestamp < $today && $timestamp >= $yesterday) {
			return GetLang('Yesterday_Date');
		}

		if ($timestamp >= $today && $timestamp < $tomorrow) {
			return GetLang('Today_Date');
		}

		if ($timestamp >= $tomorrow && $timestamp < $two_days) {
			return GetLang('Tomorrow_Date');
		}
		*/

		return AdjustTime($timestamp, false, GetLang('DateFormat'), true);
	}

	/**
	* PrintTime
	* Prints the time according to the language variables and returns the string value.
	* Uses AdjustTime to convert from server time to local user time before displaying.
	*
	* @param Int $timestamp Timestamp to print.
	* @param Boolean $stats_format If this is a stats time, we use a different format (without the day, month or year). By default we use the TimeFormat language variable. If this is set to true, we use the Stats_TimeFormat variable.
	*
	* @see GetLang
	* @see AdjustTime
	*
	* @return String This will return the time formatted, adjusted for the users timezone.
	*/
	function PrintTime($timestamp=0, $stats_format=false)
	{
		if ($timestamp == 0) {
			$timestamp = AdjustTime(0, true, null, true);
		}

		/*
		$now = AdjustTime();
		$seconds = $now % 86400; // find number of seconds that today has had so far, so we can remove it.
		$today = $now - $seconds;

		$yesterday = $today - 86400;

		$tomorrow = $today + 86400;

		$two_days = $tomorrow + 86400;

		if ($timestamp >= $today && $timestamp < $tomorrow) {
			if ($stats_format) {
				return sprintf(GetLang('Today_Time'), AdjustTime($timestamp, false, GetLang('Stats_TimeFormat'), true));
			}
			return sprintf(GetLang('Today_Time'), AdjustTime($timestamp, false, GetLang('TimeFormat'), true));
		}

		if ($timestamp < $today && $timestamp >= $yesterday) {
			if ($stats_format) {
				return sprintf(GetLang('Yesterday_Time'), AdjustTime($timestamp, false, GetLang('Stats_TimeFormat'), true));
			}
			return sprintf(GetLang('Yesterday_Time'), AdjustTime($timestamp, false, GetLang('TimeFormat'), true));
		}

		if ($timestamp >= $tomorrow && $timestamp < $two_days) {
			if ($stats_format) {
				return sprintf(GetLang('Tomorrow_Time'), AdjustTime($timestamp, false, GetLang('Stats_TimeFormat'), true));
			}
			return sprintf(GetLang('Tomorrow_Time'), AdjustTime($timestamp, false, GetLang('TimeFormat'), true));
		}
		*/

		return AdjustTime($timestamp, false, GetLang('TimeFormat'), true);
	}

	/**
	* LoadLanguageFile
	* Loads a language file for this class unless you pass in a language file to load.
	*
	* @param String $languagefile Languagefile to load. This is useful when you are loading a different language file other than this class. Eg. The logout languagefile on the login page.
	*
	* @see GetLang
	*
	* @return Boolean Whether loading the language file worked or not.
	*/
	function LoadLanguageFile($languagefile=null)
	{
		if (is_null($languagefile)) {
			$languagefile = get_class($this);
		}

		return IEM::langLoad($languagefile);
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
			'SortBy' => $this->_DefaultSort,
			'Direction' => $this->_DefaultDirection,
			'Secondary' => false,
			'SecondaryDirection' => false,
			);
		if (!is_null($update['Direction'])) {
			if (strtolower($update['Direction']) == 'up' || strtolower($update['Direction']) == 'asc') {
				$update['Direction'] = 'asc';
			} else {
				$update['Direction'] = 'desc';
			}
		}
		if (in_array($update['SortBy'], array_keys($this->_SecondarySorts))) {
			$update['Secondary'] = $this->_SecondarySorts[$update['SortBy']]['field'];
			$update['SecondaryDirection'] = $this->_SecondarySorts[$update['SortBy']]['order'];
		}
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
	* GetCurrentPage
	* Returns the current page number. This is used for paging.
	*
	* @uses SetCurrentPage
	*
	* @return Int Current page number.
	*/
	function GetCurrentPage()
	{
		if (isset($_GET['DisplayPage'])) {
			// if it's explicitly set, obey and remember it
			$page_num = $this->SetCurrentPage($_GET['DisplayPage']);
		} else {
			// if not, recall it from the session
			$user = GetUser();
			$display_settings = $user->GetSettings('DisplaySettings');

			$page_name = $this->GetPageName();
			if (isset($display_settings['DisplayPage'][$page_name])) {
				$page_num = $display_settings['DisplayPage'][$page_name];
			} else {
				$page_num = 1;
			}
		}
		if ($page_num <= 0) {
			$page_num = 1;
		}
		return (int)$page_num;
	}

	/**
	* SetCurrentPage
	* Remembers the current page number ($num) on the page ($page_num) the user was last at.
	*
	* @see GetCurrentPage
	*
	* @return Int Remembered page number.
	*/
	function SetCurrentPage($num, $page_name = null)
	{
		if (is_null($page_name)) {
			$page_name = $this->GetPageName();
		}
		$user = GetUser();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (!isset($display_settings['DisplayPage']) || !is_array($display_settings['DisplayPage'])) {
			$display_settings['DisplayPage'] = array();
		}
		$display_settings['DisplayPage'][$page_name] = (int)$num;
		$user->SetSettings('DisplaySettings', $display_settings);
		return (int)$num;
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
		$page = IEM::requestGetGET('Page', '');
		if (empty($page)) {
			$page = IEM::requestGetGET('page', 'unknown');
		} else {
			$page = strtolower($page);
		}

		$action = IEM::requestGetGET('Action', IEM::requestGetGET('action', false));

		if ($action == 'ProcessPaging' && isset($_GET['SubAction'])) {
			$action = strtolower($_GET['SubAction']);
		}

		if ($page == 'stats' && !$action) {
			$action = 'newsletters';
		}

		// see bugid:2195 for why we handle this special case with subscribers
		if ($page == 'stats' || ($page == 'subscribers' && $action == 'banned')) {
			$page .= '_'.$action;
		}

		return $page;
	}

	/**
	* GetPerPage
	* Gets the number to show based on your session. If you don't have a session, it sets a default of this->_PerPageDefault
	*
	* @see _PerPageDefault
	* @see User_API::GetSettings
	*
	* @return Mixed Number to show per page.
	*/
	function GetPerPage($page = null)
	{
    		$user = IEM::getCurrentUser();
		$display_settings = $user->GetSettings('DisplaySettings');
		if ($page == null) {
			$page = $this->GetPageName();
                        $page = strtolower($page);
		}
		if (!isset($display_settings['NumberToShow'][$page])) {
			$perpage = $this->_PerPageDefault;
		} else {
			$perpage = $display_settings['NumberToShow'][$page];
		}
		if ($perpage == 'all') {
			return $perpage;
		}
		return (int)$perpage;
	}

	/**
	* SetPerPage
	* Sets the number of records to view on a certain page using session data.
	*
	* @see GetPageName
	* @see User_API::GetSettings
	* @see User_API::SetSettings
	*
	* @param Mixed $perpage Can be an integer or 'all'.
	* @param String $page A container label to remember the number in, defaulting to $_GET['Page'].
	* @return Mixed Number to show per page.
	*/
	function SetPerPage($perpage, $page = null)
	{
		if ($perpage != 'all') {
			$perpage = (int)$perpage;
		}
		if (is_null($page)) {
			$page = $this->GetPageName();
		}
		$user = IEM::getCurrentUser();
		$display_settings = $user->GetSettings('DisplaySettings');
		if (!isset($display_settings['NumberToShow']) || !is_array($display_settings['NumberToShow'])) {
			$display_settings['NumberToShow'] = array();
		}
		$display_settings['NumberToShow'][$page] = $perpage;
		$user->SetSettings('DisplaySettings', $display_settings);
		$user->SaveSettings();

		return $perpage;
	}

	/**
	* FetchEditor
	* Fetches the editor for you to display. It loads the data from the session and gets the html or text editor as it needs to.
	*
	* @see GetHTMLEditor
	*
	* @return Mixed Returns false if the data isn't in the session. Otherwise returns the editor and it's contents.
	*/
	function FetchEditor()
	{
		$type = ucwords(get_class($this));
		$details = IEM::sessionGet($type);
		if (empty($details)) {
			return false;
		}
		$format = $details['Format'];
		$id = (isset($details['id'])) ? $details['id'] : 0;

		$dctEditorEvent = new EventData_IEM_EDITOR_TAG_BUTTON();
                $dctEditorEvent->trigger();
                if (isset($dctEditorEvent->tagButtonHtml) && isset($dctEditorEvent->tagButtonText)) {
	                $GLOBALS['tagButtonHtml'] = $dctEditorEvent->tagButtonHtml;
	                $GLOBALS['tagButtonText'] = $dctEditorEvent->tagButtonText;
                }

		        $surveyEditorEvent = new EventData_IEM_EDITOR_SURVEY_BUTTON();
		        $surveyEditorEvent->trigger();

		        if (isset($surveyEditorEvent->surveyButtonHtml) && isset($surveyEditorEvent->surveyButtonText)) {
	        		$GLOBALS['surveyButtonHtml'] = $surveyEditorEvent->surveyButtonHtml;
	        		$GLOBALS['surveyButtonText'] = $surveyEditorEvent->surveyButtonText;
		        }

		switch ($format) {
			case 'b':
				if (!$id && empty($details['contents']['text'])) {
					$details['contents']['text'] = GetLang('NewsletterDefaultTextContents');
				}

				$GLOBALS['TextContent'] = $details['contents']['text'];
				$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($details['contents']['html'], $id);
				$editor = $this->ParseTemplate('Editor_Multipart', true, true);
			break;

			case 'h':
				$GLOBALS['HTMLContent'] = $this->GetHTMLEditor($details['contents']['html'], $id);
				$editor = $this->ParseTemplate('Editor_HTML', true, true);
			break;

			default:
			case 't':
				$GLOBALS['TextContent'] = $details['contents']['text'];
				$editor = $this->ParseTemplate('Editor_Text', true, true);
			break;
		}
		return $editor;
	}

	/**
	* GetHTMLEditor
	* Gets the editor - whether it's devedit or the regular editor, inserts the content and returns it.
	*
	* @param String $value The content to put in the editor.
	* @param Int $id The ID of what you're loading. This allows us to use the right location for storing images etc.
	* @param String $elementId The name/id of the editor text area
	* @param String $mode The mode of the editor
	* @param Int $height The height of the editor.
	* @param Int $width The width of the editor.
	*
	* @see FetchEditor
	*
	* @return String Returns the editor and it's content.
	*/
	function GetHTMLEditor($htmlContent = '', $id = 0, $elementId = 'myDevEditControl', $mode = 'exact', $height = '400', $width = '95%')
	{
		$user        = IEM::getCurrentUser();
		$elementId   = trim($elementId) . '_html';
		$htmlContent = htmlentities($htmlContent, ENT_QUOTES, SENDSTUDIO_CHARSET);

		if (!$user->Get('usewysiwyg')) {
			$GLOBALS['HTMLContent'] = $htmlContent;
			$GLOBALS['Name']        = $elementId;

			return $this->ParseTemplate('editor_html_no_wysiwyg', true, false);
		}



		$dctEditorEvent = new EventData_IEM_DCT_HTMLEDITOR_TINYMCEPLUGIN();
                $dctEditorEvent->trigger();

		// Add the javascript embed to the survey editor code..

		$plugins            = 'safari,pagebreak,style,layer,table,save,advimage,advlink,media,searchreplace,print,contextmenu,paste,fullscreen,visualchars,nonbreaking,xhtmlxtras,template,inlinepopups,preview';
		$surveysEditorEvent = new EventData_IEM_HTMLEDITOR_TINYMCEPLUGIN();
		$surveysEditorEvent->trigger();

		if (isset($surveysEditorEvent->enable) && $surveysEditorEvent->enable == '1') {
                        $plugins = 'interspiresurvey,' . $plugins;
		}

		$pattern = '/id="body_holder"/';

		preg_match($pattern, $htmlContent, $matches);

		if (!sizeof($matches)) {
			$regex       = array("/<body([^>]*?)\>/i", "/<\/body>/i");
			$replacement = array('<body><table id="body_holder" border="0" cellspacing="0" cellpadding="0" width="100%"${1}><tr><td>', '</td></tr></table></body>');
			$htmlContent = preg_replace($regex, $replacement, $htmlContent);
		}

		$type = ucwords(get_class($this));

		if (!$id) {
			CreateDirectory(TEMP_DIRECTORY . '/user/' . $user->userid, TEMP_DIRECTORY, 0777);

			$type = 'user';
			$id   = $user->userid;
		} else {
			CreateDirectory(TEMP_DIRECTORY . '/' . strtolower($type) . '/' . $id, TEMP_DIRECTORY, 0777);

			$type = strtolower($type);
		}

		/**
		 * Return HTML
		 */
		$editor['Mode']           = $mode;
		$editor['Width']          = $width;
		$editor['ImageDirType']   = $type;
		$editor['ImageDirTypeId'] = $id;
		$editor['Height']         = $height;
		$editor['FullUrl']        = SENDSTUDIO_APPLICATION_URL . '/admin/includes/js/tiny_mce/';
		$editor['ElementId']      = $elementId;
		$editor['AppUrl']         = SENDSTUDIO_APPLICATION_URL;
		$editor['HtmlContent']    = $htmlContent;

		$tpl = GetTemplateSystem();

		if (!empty($dctEditorEvent->dynamicContentButton)) {
			$tpl->Assign('dynamicContentButton', $dctEditorEvent->dynamicContentButton);
			$tpl->Assign('dynamicContentPopup', $dctEditorEvent->dynamicContentPopup);
		}

//		if (!empty($surveysEditorEvent->dynamicContentButton)) {
//			$tpl->Assign('surveyButton', $surveysEditorEvent->dynamicContentButton);
//		}

		$tpl->Assign('plugins', $plugins);
		$tpl->Assign('editor', $editor);

		return $tpl->ParseTemplate('editor', true);
	}

	/**
	* ConvertContent
	* Changes content references from temporary storage under a user's id - to it's final location - under the type and it's id. Eg newsletter/1.
	*
	* @param String $content Content to change paths for.
	* @param String $dest The destination (eg newsletter or template).
	* @param Int $id The destinations id.
	*
	* @return String Returns the converted content.
	*/
	function ConvertContent($content='', $dest=false, $id=0)
	{
		if (!$dest || !$id) {
			return $content;
		}

		$user = IEM::getCurrentUser();
		$sourceurl = SENDSTUDIO_APPLICATION_URL . '/admin/temp/user/' . $user->userid;
		$destinationurl = SENDSTUDIO_APPLICATION_URL . '/admin/temp/' . $dest . '/' . $id;
		$content = str_replace($sourceurl, $destinationurl, $content);

		/**
		* if there is an unsubscribe link in the content with a full url (including sendstudio),
		* take it out and put just the unsubscribe placeholder back in.
		* devedit + ie6 seems to insist on having a full url and we have been unable to track it down
		* so here's an easier fix.
		* Then do the same for sendfriend_ links and also modifydetails_ links.
		*/
		$content = preg_replace('/' . preg_quote(SENDSTUDIO_APPLICATION_URL . '/admin/de/', '/') . '%%(.*)%%/', '%%$1%%', $content);
		return $content;
	}

	/****
	 * ConvertSurveyContainer
	 *  Convert the survey Container to the actual link
	 */
	function ConvertSurveyContainer($text)
	{
		$results = array();
		$surveys_tags =  preg_match_all("/(%%SURVEY.*?%%)/isx", $text, $results);
		if (!empty($surveys_tags) && $surveys_tags !== false) {

				$survey_links = $results[0];

				foreach ($survey_links as $survey_link) {
						$survey_pieces = explode('_', $survey_link);
						$id = str_replace('%%', '',  $survey_pieces[1]);
						$survey_url =  SENDSTUDIO_APPLICATION_URL . '/surveys.php?id=' . $id; // ID
						$text = str_replace($survey_link, $survey_url, $text);
				}

		}

		return $text;
	}



	/**
	* GetPredefinedLinkList
	* A function to generate a list of the available predefined links
	*
	* @return Void Doesn't return anything. Display the javascript content.
	*/
	function GetPredefinedLinkList()
	{

		$user = IEM::getCurrentUser();
		$form_userid = $user->userid;
		if ($user->Admin()) {
			$form_userid = 0;
		}
		$formapi = $this->GetApi('Forms');
		$output = '';

		$output .= 'var tinyMCELinkList = new Array(';
		$outputArray = array();

		$allLinks[] = array('name' => GetLang('Link_MailingListArchives'), 'url' => '%%mailinglistarchive%%');
		$allLinks[] = array('name' => GetLang('Link_WebVersion'), 'url' => '%%webversion%%');
		$allLinks[] = array('name' => GetLang('Link_Unsubscribe'), 'url' => '%%unsubscribelink%%');

		$modify_forms = $formapi->GetUserForms($form_userid, 'modify');
		if (!empty($modify_forms)) {
			foreach ($modify_forms as $p => $formdetails) {
				$allLinks[] = array('name' => htmlspecialchars($formdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET), 'url' => "%%modifydetails_" . $formdetails['formid'] . "%%");
			}
		}

		$sendfriend_forms = $formapi->GetUserForms($form_userid, 'friend');
		if (!empty($sendfriend_forms)) {
			foreach ($sendfriend_forms as $p => $formdetails) {
				$allLinks[] = array('name' => htmlspecialchars($formdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET), 'url' => "%%sendfriend_" . $formdetails['formid'] . "%%");
			}
		}

		foreach ($allLinks as $k=>$link){
			$outputArray[] = '["' . $link['name'] . '", "' . $link['url'] . '"]';
		}

		$output .= implode(",\n", $outputArray) . ');';

		header('Content-type: text/javascript');

		echo $output;
		die();
	}

	/**
	* MoveFiles
	* Moves uploaded images from temporary storage under a user's id - to it's final location - under the type and it's id. Eg newsletter/1.
	*
	* @param String $destination The destination (eg newsletter or template).
	* @param Int $id The destinations id.
	*
	* @see CreateDirectory
	* @see list_files
	*
	* @return Boolean Returns false if it can't create the paths or it can't copy the necessary files. Returns true if everything worked ok.
	*/
	function MoveFiles($destination=false, $id=0)
	{
		if (!$destination || !$id) {
			return false;
		}

		$destinationdir = TEMP_DIRECTORY . '/' . $destination . '/' . $id;
		$createdir = CreateDirectory($destinationdir);
		if (!$createdir) {
			return false;
		}

		$user = IEM::getCurrentUser();
		$sourcedir = TEMP_DIRECTORY . '/user/' . $user->userid;
		$file_list = list_files($sourcedir);

		$dir_list = list_directories($sourcedir);

		if (empty($file_list) && empty($dir_list)) {
			return true;
		}

		$result = true;

		foreach ($file_list as $p => $filename) {
			if (!copy($sourcedir . '/' . $filename, $destinationdir . '/' . $filename)) {
				$result = false;
			}
		}

		if ($result) {
			foreach ($dir_list as $dir) {
				$dirname = str_replace($sourcedir, '', $dir);
				if ($dirname == 'attachments') {
					continue;
				}
				$copy_dir_result = CopyDirectory($dir, $destinationdir . $dirname);
				if (!$copy_dir_result) {
					$resut = false;
				}
			}
		}
		return $result;
	}

	/**
	* SaveAttachments
	* Saves uploaded attachments in the appropriate place. Returns a report on what happened and why some attachments might not have uploaded. Checks whether the file extension is valid, permissions and so on.
	*
	* @param String $destination Where to save the files. Eg templates, newsletters, autoresponders.
	* @param Int $id The id of the destination.
	*
	* @see CreateDirectory
	* @see ValidFileExtensions
	*
	* @return Array Returns a status and a report. If all uploaded ok, it returns true and how many uploaded. If any can't be uploaded it returns false and a list of reasons why a file couldn't be uploaded.
	*/
	function SaveAttachments($destination=false, $id=0)
	{

		if (empty($_FILES)) {
			return array(false, sprintf(GetLang('MaximumFileSizeReached'), ini_get('upload_max_filesize')));
		}

		if (!$destination || !$id) {
			return array(false, 'Invalid Data');
		}

		$id = (int)$id;
		$destinationdir = TEMP_DIRECTORY . '/' . strtolower($destination) . '/' . $id . '/attachments';
		$createdir = CreateDirectory($destinationdir);
		if (!$createdir) {
			return array(false, GetLang('UnableToCreateDirectory'));
		}

		$result = true;
		$success = 0;
		$errors = array();

		if (!is_writable($destinationdir)) {
			$errors[] = sprintf(GetLang('DirectoryNotWritable'), $destinationdir);
			$result = false;
		}

		if ($result) {
			foreach ($_FILES['attachments']['name'] as $pos => $name) {
				if ($name == '') {
					continue;
				}

				if ($_FILES['attachments']['tmp_name'][$pos] == '' || $_FILES['attachments']['tmp_name'][$pos] == 'none') {
					if (isset($_FILES['attachments']['error'][$pos])) {
						$error = $_FILES['attachments']['error'][$pos];

						/**
						* see http://www.php.net/manual/en/features.file-upload.errors.php
						* for what these errors mean.
						*/

						// this was added in php 4.3.10 & 5.0.3.
						if (!defined('UPLOAD_ERR_NO_TMP_DIR')) {
							define('UPLOAD_ERR_NO_TMP_DIR', 6);
						}

						// this was added in php 5.1.0.
						if (!defined('UPLOAD_ERR_CANT_WRITE')) {
							define('UPLOAD_ERR_CANT_WRITE', 7);
						}

						// this was added in php 5.2.0.
						if (!defined('UPLOAD_ERR_EXTENSION')) {
							define('UPLOAD_ERR_EXTENSION', 8);
						}

						switch ($error) {
							case UPLOAD_ERR_INI_SIZE:
							case UPLOAD_ERR_FORM_SIZE:
								$errors[] = $name . ' (' . sprintf(GetLang('FileTooBig_Server'), ini_get('upload_max_filesize')) . ')';
								$result = false;
								continue;
							break;

							case UPLOAD_ERR_PARTIAL:
								$errors[] = $name . ' (' . GetLang('FilePartiallyUploaded') . ')';
								$result = false;
								continue;
							break;

							case UPLOAD_ERR_CANT_WRITE:
								$errors[] = $name . ' (' . sprintf(GetLang('FileTooBig_NoSpace'), $this->EasySize($size)) . ')';
								$result = false;
								continue;
							break;

							case UPLOAD_ERR_NO_TMP_DIR:
								$errors[] = $name . ' (' . GetLang('FileUploadProblem_NoTmpDir') . ')';
								$result = false;
								continue;
							break;

							case UPLOAD_ERR_EXTENSION:
								$fileparts = pathinfo($name);
								$extension = false;
								if (isset($fileparts['extension'])) {
									$extension = strtolower($fileparts['extension']);
								}

								$errors[] = $name . ' (' . sprintf(GetLang('FileExtensionNotValid'), $extension) . ')';
								$result = false;
								continue;
							break;
						}
					}
					continue;
				}

				$fileparts = pathinfo($name);
				$extension = false;
				if (isset($fileparts['extension'])) {
					$extension = strtolower($fileparts['extension']);
				}

				if (!in_array($extension, $this->ValidFileExtensions)) {
					$errors[] = $name . ' (' . sprintf(GetLang('FileExtensionNotValid'), $extension) . ')';
					$result = false;
					continue;
				}

				$size = $_FILES['attachments']['size'][$pos];
				$max_attachment_size = SENDSTUDIO_ATTACHMENT_SIZE*1024;
				if ($size > ($max_attachment_size)) {
					$errors[] = $name . ' (' . sprintf(GetLang('FileTooBig'), $this->EasySize($size), $this->EasySize($max_attachment_size)) . ')';
					$result = false;
					continue;
				}

				$destination = $destinationdir . '/' . $name;

				if (!move_uploaded_file($_FILES['attachments']['tmp_name'][$pos], $destination)) {
					if (!is_uploaded_file($_FILES['attachments']['tmp_name'][$pos])) {
						$errors[] = $name . ' (' . GetLang('NotUploadedFile') . ')';
					} else {
						$errors[] = $name . ' (' . GetLang('UnableToUploadFile') . ')';
					}
					$result = false;
					continue;
				}
				chmod($destination, 0644);
				$success++;
			}
		}

		$report = '';
		if ($success > 0) {
			if ($success == 1) {
				$report .= GetLang('FileUploadSuccessful_One') . '<br/>';
			} else {
				$report .= sprintf(GetLang('FileUploadSuccessful_Many'), $this->FormatNumber($success)) . '<br/>';
			}
		}

		if (!empty($errors)) {
			$report .= GetLang('FileUploadFailure') . '<br/>- ';
			$report .= implode('<br/>- ', $errors);
		}

		return array($result, $report);
	}

	/**
	* CleanupAttachments
	* Removes attachments from a particular area (template, newsletter etc) based on the DeleteAttachments post variable. If the list is empty, it returns true for the status and false for the report so you can quickly see whether to display a message or not.
	*
	* @param String $area Where to remove the files from. Eg templates, newsletters, autoresponders.
	* @param Int $id The id of the destination.
	*
	* @see list_files
	*
	* @return Array Returns a status and a report. If all were deleted ok, it returns true and how many deleted. If any can't be deleted it returns false and a list of reasons why a file couldn't be deleted.
	*/
	function CleanupAttachments($area=false, $id=0)
	{
		$deleteattachments_list = (isset($_POST['DeleteAttachments'])) ? $_POST['DeleteAttachments'] : array();

		if (empty($deleteattachments_list)) {
			return array(true, false);
		}

		if (!$area || !$id) {
			return array(false, 'Invalid Data');
		}

		$id = (int)$id;

		$realdir = TEMP_DIRECTORY . '/' . strtolower($area) . '/' . $id . '/attachments';
		if (!is_dir($realdir)) {
			return array(false, 'Directory Not Found');
		}

		$result = true;
		$success = 0;
		$errors = array();

		$filelist = list_files($realdir);

		foreach ($deleteattachments_list as $pos => $filetodelete) {
			$filetodelete = urldecode($filetodelete);
			if (!in_array($filetodelete, $filelist)) {
				$result = false;
				$errors[] = $filetodelete . ' (' . GetLang('FileNotFound') . ')';
				continue;
			}
			if (!unlink($realdir . '/' . $filetodelete)) {
				$result = false;
				$errors[] = $filetodelete . ' (' . GetLang('UnableToDelete') . ')';
				continue;
			}
			$success++;
		}

		$report = '';
		if ($success > 0) {
			if ($success == 1) {
				$report .= GetLang('FileDeleteSuccessful_One') . '<br/>';
			} else {
				$report .= sprintf(GetLang('FileDeleteSuccessful_Many'), $this->FormatNumber($success)) . '<br/>';
			}
		}

		if (!empty($errors)) {
			$report .= GetLang('FileDeleteFailure') . '<br/>- ';
			$report .= implode('<br/>- ', $errors);
		}

		return array($result, $report);
	}

	/**
	* GetAttachments
	* GetAttachments prints a small table with file attachments based on the area and id passed in. It also turns the filename into a link (opens in a new window by default) so you can see what you've uploaded and preview the attachment.
	*
	* @param String $area Where to get the files from. Eg templates, newsletters, autoresponders.
	* @param Int $id The id of the destination.
	* @param Boolean $listonly Whether to just retrieve a list of files or not. If this is false (default), it will print a report (table) - with options to delete files etc.
	*
	* @see list_files
	*
	* @return Mixed Returns false if there are no files in the directory or the directory doesn't exist. If you set listonly to true it will only return an array with the real path and the list of files. Otherwise returns a string ready for printing with the filename and a checkbox next to it for easy deletion.
	*/
	function GetAttachments($area=false, $id=0, $listonly=false)
	{
		if (!$area || !$id) {
			return false;
		}

		$id = (int)$id;
		$area = strtolower($area);
		$realdir = TEMP_DIRECTORY . '/' . $area . '/' . $id . '/attachments';

		if (!is_dir($realdir)) {
			return false;
		}

		$filelist = list_files($realdir);
		if (empty($filelist)) {
			return false;
		}

		if ($listonly) {
			return array('path' => $realdir, 'filelist' => $filelist);
		}

		$report = '<table border="0" cellspacing="0" cellpadding="0">';

		if (!empty($filelist)) {
			$report .= '<tr><td class="FieldLabel">' . $this->ParseTemplate('Not_Required', true, false) . GetLang('ExistingAttachments') . '</td>';
		}

		$fpos = 0;
		foreach ($filelist as $pos => $filename) {
			if ($fpos > 0) {
				$report .= '<tr><td>&nbsp;</td>';
			}

			$report .= '<td>';

			$attach_name = 'DeleteAttachments_' . urlencode($filename);

			$report .= '<input type="checkbox" name="DeleteAttachments[]" id="' . $attach_name . '" value="' . urlencode($filename) . '">&nbsp;';

			$report .= '<label for="' . $attach_name . '">' . GetLang('DeleteAttachment') . '</label>&nbsp;';

			$report .= '<a href="' . SENDSTUDIO_TEMP_URL . '/' . $area . '/' . $id . '/attachments/' . htmlspecialchars($filename, ENT_QUOTES, SENDSTUDIO_CHARSET) . '" target="_blank">' . htmlspecialchars($filename, ENT_QUOTES, SENDSTUDIO_CHARSET) . '</a>&nbsp;&nbsp;';

			$report .= $this->_GenerateHelpTip('LNG_HLP_DeleteAttachment');

			$report .= '</td>';
			$report .= '</tr>';
			$fpos++;
		}
		$report .= '</table>';
		return $report;
	}

	/**
	* PreviewWindow
	* Creates a preview window based on the details passed in.
	*
	* @param Array $details The details to print out. This is an array containing format and also the content.
	* @param Boolean $showBroken Specify whether or not to show broken rules for each email clients (Optional, Default = false)
	* @param String $sync If opening multiple previews at once, pass unique keys to each PreviewWindow call to stop the session data from being clobbered.
	*
	* @see Preview::Process
	*
	* @return Void Prints out the main preview frame only. The actual content is displayed by the "Preview" file.
	*/
	function PreviewWindow($details=array(), $showBroken = false, $sync = false)
	{
		if (empty($details)) {
			return false;
		}

		$tempBrokenRuleWidth = '18%';

		$details['showBroken'] = $showBroken;
		if ($details['htmlcontent'] == '') {
			unset($details['htmlcontent']);
			$details['format'] = 't';
		}

		if ($details['textcontent'] == '') {
			unset($details['textcontent']);
			$details['format'] = 'h';
		}

		if ($details['format'] == 't') {
			$tempBrokenRuleWidth = '0';
			if (isset($details['htmlcontent'])) {
				unset($details['htmlcontent']);
			}
		}

		$set_sync = '';
		if ($sync) {
			$windows = IEM::sessionGet('PreviewWindowHash');
			if (!is_array($windows)) {
				$windows = array();
			}
			$details['modified'] = time();
			$windows[$sync] = $details;
			IEM::sessionSet('PreviewWindowHash', $windows);
			$set_sync = '&sync=' . urlencode($sync);
		} else {
			// Maintain this store for compatibility.
			IEM::sessionSet('PreviewWindow', $details);
		}


		// ----- Output
			header('Content-type: text/html; charset="' . SENDSTUDIO_CHARSET . '"');
			?>
				<html>
					<head>
						<meta http-equiv="Content-Type" content="text/html; charset=<?php print SENDSTUDIO_CHARSET; ?>">
					</head>
					<frameset rows="40,*" framespacing="0" border="1">
						<frame src="<?php print SENDSTUDIO_APPLICATION_URL; ?>/admin/index.php?Page=Preview&Action=Top" noresize="noresize">
						<?php if ($showBroken) { ?>
							<frameset COLS="<?php print $tempBrokenRuleWidth; ?>,*" id="mainframe">
								<frame id="frame_broken" name="frame_broken" src="<?php print SENDSTUDIO_APPLICATION_URL; ?>/admin/index.php?Page=Preview&Action=BrokenRules<?php echo $set_sync; ?>">
								<frame id="frame_display" name="frame_display" src="<?php print SENDSTUDIO_APPLICATION_URL; ?>/admin/index.php?Page=Preview&Action=Display<?php echo $set_sync; ?>">
							</frameset>
						<?php } else { ?>
							<frame id="frame_display" name="frame_display" src="<?php print SENDSTUDIO_APPLICATION_URL; ?>/admin/index.php?Page=Preview&Action=Display<?php echo $set_sync; ?>">
						<?php } ?>
					</frameset>
				</html>
			<?php
		// -----
		// Clean up any stale preview window data.
		self::PreviewHashCleanup();
	}

	/**
	 * PreviewHashCleanup
	 * Cleans up the Preview Window hash to avoid session files becoming very large.
	 *
	 * @return Void Does not return anything. Modifies the session data directly.
	 */
	private static function PreviewHashCleanup()
	{
		$windows = IEM::sessionGet('PreviewWindowHash');

		if (!$windows || !is_array($windows)) {
			return;
		}

		foreach ($windows as $key => $window) {
			$last_modify_time = isset($window['modified']) ? intval($window['modified']) : 0;
			$cutoff_time = time() - 300; // 5 minutes ago

			// Expire the preview window data after 5 minutes.
			if ($last_modify_time < $cutoff_time) {
				unset($windows[$key]);
			}
		}

		IEM::sessionSet('PreviewWindowHash', $windows);
	}

	/**
	* Display_CustomField
	* Displays a date custom field box.
	*
	* @param Array $customfield_info The custom field information to use. This includes the order of the fields (dd/mm/yy).
	* @param Array $defaults The default settings to use for the custom field search. This will preselect the specified dates, if not passed in, it will default to 'today'.
	*
	* @see Subscribers_Manage::Process
	*
	* @return Void Doesn't return anything. Puts information in the GLOBALS['Display_date_Field_X'] placeholders.
	*/
	function Display_CustomField($customfield_info=array(), $defaults=array())
	{
		switch ($customfield_info['fieldtype']) {
			case 'date':
				$fieldsettings = (is_array($customfield_info['fieldsettings'])) ? $customfield_info['fieldsettings'] : unserialize($customfield_info['fieldsettings']);

                                $field_order = array_slice($fieldsettings['Key'], 0, 3);
                                if (!is_array($defaults)) {
                                        $defaultString = explode('/', $defaults);
                                        unset($defaults);

                                        foreach ($field_order as $p => $order) {
                                                switch ($order) {
                                                        case 'day':
                                                                $defaults['dd'] =  $defaultString[$p];
                                                        break;
                                                        case 'month':
                                                                $defaults['mm'] =  $defaultString[$p];
                                                        break;
                                                        case 'year':
                                                                $defaults['yy'] =  $defaultString[$p];
                                                        break;
                                                }
                                        }
                                }

                                if (empty($defaults) && $customfield_info['required']) {
					$dd = date('d');
					$mm = date('m');
					$yy = date('Y');
				} else {
					$dd = isset($defaults['dd']) ? $defaults['dd'] : null;
					$mm = isset($defaults['mm']) ? $defaults['mm'] : null;
					$yy = isset($defaults['yy']) ? $defaults['yy'] : null;
				}


				$year_start = $fieldsettings['Key'][3];
				$year_end = $fieldsettings['Key'][4];
				if ($year_end == 0) {
					$year_end = date('Y');
				}
				$select_option = '<option value="">- '.GetLang('Select').' -</option>';

				$daylist = '<select name="CustomFields['.$GLOBALS['FieldID'].'][dd]" id="CustomFields['.$GLOBALS['FieldID'].'][dd]" class="datefield CustomField_Date_Day">';
				$daylist .= $select_option;
				for ($i=1; $i<=31; $i++) {
					$dom = $i;
					$i = sprintf("%02d", $i);
					$sel = '';
					if ($i==$dd) {
						$sel='SELECTED';
					}

					$daylist.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'. $dom . '</option>';
				}
				$daylist.='</select>';

				$monthlist = '<select name="CustomFields['.$GLOBALS['FieldID'].'][mm]" id="CustomFields['.$GLOBALS['FieldID'].'][mm]" class="datefield CustomField_Date_Month">';
				$monthlist .= $select_option;
				for ($i=1; $i<=12; $i++) {
					$mth = $i;
					$sel = '';
					$i = sprintf("%02d",$i);

					if ($i==$mm) {
						$sel='SELECTED';
					}

					$monthlist.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.GetLang($this->Months[$mth]) . '</option>';
				}
				$monthlist.='</select>';

				$yearlist = '<select name="CustomFields['.$GLOBALS['FieldID'].'][yy]" id="CustomFields['.$GLOBALS['FieldID'].'][yy]" class="datefield CustomField_Date_Year">';
				$yearlist .= $select_option;
				for ($i=$year_start; $i <= $year_end; $i++) {
					$sel = '';
					$i = sprintf("%04d",$i);
					if ($i==$yy) {
						$sel='SELECTED';
					}

					$yearlist.='<option '.$sel.' value="'.sprintf("%02d",$i).'">' . $i . '</option>';
				}
				$yearlist.='</select>';

				foreach ($field_order as $p => $order) {
					switch ($order) {
						case 'day':
							$GLOBALS['Display_date_Field'.($p+1)] = $daylist;
						break;
						case 'month':
							$GLOBALS['Display_date_Field'.($p+1)] = $monthlist;
						break;
						case 'year':
							$GLOBALS['Display_date_Field'.($p+1)] = $yearlist;
						break;
					}
				}
			break;
		}
	}

	/**
	* Search_Display_CustomField
	* Prints out the 'search' version of a custom field. eg, it will show an empty text box for a textbox custom field, show a bunch of tickboxes and so on.
	*
	* @param Array $customfield_info Custom field data to create a search box for. This contains options, settings (eg tick box "X"), name and so on.
	*
	* @return String Returns ths generated search box option.
	*/
	function Search_Display_CustomField($customfield_info=array())
	{
		if (!is_array($customfield_info) || empty($customfield_info)) {
			$GLOBALS['OptionList'] = '';
			$GLOBALS['DefaultValue'] = '';
			$GLOBALS['FieldName'] = '';
			$GLOBALS['FieldID'] = 0;
			$GLOBALS['FieldValue'] = '';
			$customfield_info['FieldValue'] = '';
		}

		if (!isset($customfield_info['FieldValue'])) {
			$customfield_info['FieldValue'] = '';
		}

		$GLOBALS['FieldID'] = $customfield_info['fieldid'];

		switch (strtolower($customfield_info['fieldtype'])) {
			case 'date':
				$GLOBALS['Style_FieldDisplayOne'] = $GLOBALS['Style_FieldDisplayTwo'] = 'none';

				$GLOBALS['FilterDescription'] = sprintf(GetLang('YesFilterByCustomDate'), $customfield_info['name']);

				$field_value = $customfield_info['FieldValue'];

				$options = array('after', 'before', 'exactly', 'between');
				$filterdateoptions = '';
				foreach ($options as $optionp => $option) {
					$selected = '';

					if (is_array($field_value) && isset($field_value['type'])) {
						if ($option == $field_value['type']) {
							$selected = ' SELECTED';
						}
					}
					$filterdateoptions .= '<option value="' . $option . '" ' . $selected . '>' . GetLang(ucwords($option)) . '</option>';
				}
				$GLOBALS['FilterDateOptions'] = $filterdateoptions;

				if (is_array($field_value) && isset($field_value['filter'])) {
					if ($field_value['filter'] == 1) {
						$GLOBALS['FilterChecked'] = ' CHECKED';
						$GLOBALS['Style_FieldDisplayOne'] = '';
					}
					if ($field_value['type'] == 'between') {
						$GLOBALS['Style_FieldDisplayTwo'] = '';
					}
				}

				$optionlist = '';
				$fieldsettings = (is_array($customfield_info['fieldsettings'])) ? $customfield_info['fieldsettings'] : unserialize($customfield_info['fieldsettings']);

				$dd_start = $dd_end = date('d');
				$mm_start = $mm_end = date('m');
				$yy_start = $yy_end = date('Y');

				if (is_array($field_value) && isset($field_value['dd_start'])) {
					$dd_start = $field_value['dd_start'];
					$mm_start = $field_value['mm_start'];
					$yy_start = $field_value['yy_start'];

					$dd_end = $field_value['dd_end'];
					$mm_end = $field_value['mm_end'];
					$yy_end = $field_value['yy_end'];
				}

				$field_order = array_slice($fieldsettings['Key'], 0, 3);

				$daylist_start = $daylist_end = '<select name="CustomFields['.$GLOBALS['FieldID'].'][dd_whichone]" class="datefield">';
				for ($i=1; $i<=31; $i++) {
					$dom = $i;
					$i = sprintf("%02d", $i);
					$sel = '';
					if ($i==$dd_start) {
						$sel='SELECTED';
					}

					$daylist_start.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.$dom . '</option>';

					$sel = '';
					if ($i==$dd_end) {
						$sel='SELECTED';
					}

					$daylist_end.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.$dom . '</option>';

				}
				$daylist_start.='</select>';
				$daylist_end.='</select>';

				$monthlist_start = $monthlist_end ='<select name="CustomFields['.$GLOBALS['FieldID'].'][mm_whichone]" class="datefield">';
				for ($i=1; $i<=12; $i++) {
					$mth = $i;
					$sel = '';
					$i = sprintf("%02d",$i);

					if ($i==$mm_start) {
						$sel='SELECTED';
					}

					$monthlist_start.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.GetLang($this->Months[$mth]) . '</option>';

					if ($i==$mm_end) {
						$sel='SELECTED';
					}

					$monthlist_end .='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.GetLang($this->Months[$mth]) . '</option>';

				}
				$monthlist_start.='</select>';
				$monthlist_end.='</select>';

				$yearlist_start ='<input type="text" maxlength="4" size="4" value="'.$yy_start.'" name="CustomFields['.$GLOBALS['FieldID'].'][yy_whichone]" class="datefield">';
				$yearlist_end ='<input type="text" maxlength="4" size="4" value="'.$yy_end.'" name="CustomFields['.$GLOBALS['FieldID'].'][yy_whichone]" class="datefield">';

				foreach ($field_order as $p => $order) {
					switch ($order) {
						case 'day':
							$GLOBALS['Display_date1_Field'.($p+1)] = str_replace('_whichone', '_start', $daylist_start);
						break;
						case 'month':
							$GLOBALS['Display_date1_Field'.($p+1)] = str_replace('_whichone', '_start', $monthlist_start);
						break;
						case 'year':
							$GLOBALS['Display_date1_Field'.($p+1)] = str_replace('_whichone', '_start', $yearlist_start);
						break;
					}
				}

				foreach ($field_order as $p => $order) {
					switch ($order) {
						case 'day':
							$GLOBALS['Display_date2_Field'.($p+1)] = str_replace('_whichone', '_end', $daylist_end);
						break;
						case 'month':
							$GLOBALS['Display_date2_Field'.($p+1)] = str_replace('_whichone', '_end', $monthlist_end);
						break;
						case 'year':
							$GLOBALS['Display_date2_Field'.($p+1)] = str_replace('_whichone', '_end', $yearlist_end);
						break;
					}
				}
			break;

			case 'dropdown':
			case 'radiobutton':
				if (!is_array($customfield_info['FieldValue'])) {
					$customfield_info['FieldValue'] = array($customfield_info['FieldValue']);
				}

				$fieldsettings = (is_array($customfield_info['fieldsettings'])) ? $customfield_info['fieldsettings'] : unserialize($customfield_info['fieldsettings']);

				$optionlist = '';

				$nothing_selected = ' selected';
				foreach ($fieldsettings['Key'] as $pos => $key) {
					$selected = '';
					if (in_array($key, $customfield_info['FieldValue'])) {
						$selected = ' selected="selected"';
						$nothing_selected = '';
					}

					$optionlist .= '<option value="' . htmlspecialchars($key, ENT_QUOTES, SENDSTUDIO_CHARSET) . '"' . $selected . '>' . htmlspecialchars($fieldsettings['Value'][$pos], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
				}
				$optionlist = '<option value="" ' . $nothing_selected . '>' . GetLang('None') . '</option>' . $optionlist;
			break;

			case 'checkbox':
				$fieldsettings = (is_array($customfield_info['fieldsettings'])) ? $customfield_info['fieldsettings'] : unserialize($customfield_info['fieldsettings']);

				if (!is_array($customfield_info['FieldValue'])) {
					$customfield_info['FieldValue'] = array($customfield_info['FieldValue']);
				}

				/**
				* We have to run through this because if you have an empty array from array_keys:
				* array(0 => "")
				* an in_array check matches (not really sure why!).
				* So remove any empty options as applicable.
				*/
				$checked_options = array_keys($customfield_info['FieldValue']);
				foreach ($checked_options as $co => $option) {
					if ($option == '') {
						unset($checked_options[$co]);
					}
				}

				$GLOBALS['SpanID'] = 'CustomFields_' . $customfield_info['fieldid'];

				if (!isset($GLOBALS['CheckboxFilterType_AND']) || !isset($GLOBALS['CheckboxFilterType_OR']) || !isset($GLOBALS['CheckboxFilterType_NONE'])) {
					$GLOBALS['CheckboxFilterType_AND'] = ' SELECTED';
				}

				$GLOBALS['Search_OptionList'] = $this->ParseTemplate('Customfield_Search_Checkbox_Filtertype', true, false);

				$optionlist = '';

				$c = 1;
				$rowspan = 1;
				foreach ($fieldsettings['Key'] as $pos => $key) {
					$last = false;

					$checked = '';
					if (in_array($key, $checked_options)) {
						$checked = ' checked="checked"';
					}

					$label_id = 'CustomFields[' . $customfield_info['fieldid'] . '][' . $key . ']';

					$optionlist .= '<label for="'.$label_id.'"><input type="checkbox" name="'.$label_id.'" id="'.$label_id.'" value="1"' . $checked . '>' . htmlspecialchars($fieldsettings['Value'][$pos], ENT_QUOTES,
					SENDSTUDIO_CHARSET) . '</label>';

					$and_display = 'inline';
					$or_display = 'none';
					if (isset($GLOBALS['CheckboxFilterType_OR']) && $GLOBALS['CheckboxFilterType_OR'] == ' SELECTED') {
						$and_display = 'none';
						$or_display = 'inline';
					}

					$span = '&nbsp;<span class="' . $GLOBALS['SpanID'] . '_and" style="display:' . $and_display . ';">' . GetLang('AND') . '</span>&nbsp;<span class="' . $GLOBALS['SpanID'] . '_or" style="display:' .
					$or_display . ';">' . GetLang('OR') . '</span>';
					$optionlist .= $span;

					if ($c % 4 == 0) {
						$rowspan++;
						$optionlist .= '<br/>';
						$last = true;
					}
					$c++;
				}
				$remove_len = strlen($span);
				if ($last) {
					$remove_len += strlen('<br/>');
				}
				$optionlist = substr($optionlist, 0, -$remove_len);

				$GLOBALS['RowSpan'] = $rowspan;
			break;

			default:
				$optionlist = '';
				$GLOBALS['FieldValue'] = htmlspecialchars($customfield_info['FieldValue'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		}
		$GLOBALS['OptionList'] = $optionlist;
		$GLOBALS['FieldName'] = htmlspecialchars($customfield_info['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
		$display = $this->ParseTemplate('CustomField_Search_' . $customfield_info['fieldtype'], true, false);
		return $display;
	}

	/**
	* GetTemplateList
	* Returns a select box list of templates. This is used for email campaigns and autoresponders to get a list of 'live' templates the user can use.
	*
	* @param Boolean $built_in_only Whether to only show a list of built in templates or not. By default this will be false which means it will include both built in and user templates.
	* @param Int $select_size Select row size (Optional, default = 10)
	*
	* @see GetUser
	* @see GetApi
	* @see User_API::Admin
	* @see Templates::GetLiveTemplates
	*
	* @return String The select box options for templates.
	*/
	function GetTemplateList($built_in_only=false, $select_size = 10)
	{
		$user = GetUser();

		$templatelist = array();

		if (!$built_in_only) {
			$TemplatesApi = $this->GetApi('Templates');

			if ($user->Admin()) {
				$templatelist = $TemplatesApi->GetLiveTemplates();
			} else {
				$templatelist = $TemplatesApi->GetLiveTemplates($user->userid);
			}
		}

		$template_names = array();

		$template_packs = array();

		if ($user->HasAccess('Templates', 'BuiltIn')) {
			$server_template_list = list_files(SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY . '/', null, true, true);

			// we only support two folders depth currently so we'll hardcode the look.
			if ($server_template_list) {
				foreach ($server_template_list as $template_name => $sub_templates) {
					if ($template_name == 'CVS' || $template_name == '.svn') {
						continue;
					}

					if (empty($sub_templates)) {
						unset($server_template_list[$template_name]);
						continue;
					}

					$sub_folders = array_keys($sub_templates);

					if (empty($sub_folders)) {
						unset($server_template_list[$template_name]);
						continue;
					}

					if (in_array('CVS', $sub_folders)) {
						$pos = array_search('CVS', $sub_folders);
						if ($pos !== false) {
							unset($sub_folders[$pos]);
						}
					}
					if (in_array('.svn', $sub_folders)) {
						$pos = array_search('.svn', $sub_folders);
						if ($pos !== false) {
							unset($sub_folders[$pos]);
						}
					}

					foreach ($sub_folders as $sub_p => $sub_name) {
						if (!is_readable(SENDSTUDIO_NEWSLETTER_TEMPLATES_DIRECTORY . '/' . $template_name . '/' . $sub_name)) {
							unset($sub_folders[$sub_p]);
						}
					}

					if (!empty($sub_folders)) {
						$template_packs[] = array('Name' => $template_name, 'Designs' => $sub_folders);
						continue;
					}

					$template_names[] = $template_name;
				}
			}

			sort($template_names);
			sort($template_packs);
		}

		$GLOBALS['SelectRowSize'] = intval($select_size);
		$templateselect = $this->ParseTemplate('Template_Select_Start', true, false);

		$GLOBALS['TemplateID'] = 0;
		$GLOBALS['TemplateName'] = GetLang('NoTemplate');
		$templateselect .= $this->ParseTemplate('Template_Select_Option', true, false);

		if (!empty($templatelist)) {
			$templateselect .= '<optgroup class="templategroup" label="' . GetLang('Templates_User') . '">';

			foreach ($templatelist as $pos => $templateinfo) {
				$GLOBALS['TemplateID'] = $templateinfo['templateid'];
				$GLOBALS['TemplateName'] = htmlspecialchars($templateinfo['name'], ENT_QUOTES, SENDSTUDIO_CHARSET);
				$templateselect .= $this->ParseTemplate('Template_Select_Option', true, false);
			}

			$templateselect .= '</optgroup>';
		}

		if (!empty($template_names) || !empty($template_packs)) {
			$templateselect .= '<optgroup class="templategroup" label="' . GetLang('Templates_BuiltIn') . '">';

			foreach ($template_names as $p => $templatename) {
				$GLOBALS['TemplateID'] = $templatename;
				$GLOBALS['TemplateName'] = htmlspecialchars($templatename, ENT_QUOTES, SENDSTUDIO_CHARSET);
				$templateselect .= $this->ParseTemplate('Template_Select_Option', true, false);
			}
			$templateselect .= '</optgroup>';
		}

		if (!empty($template_packs)) {
			foreach ($template_packs as $p => $details) {
				sort($details['Designs']);
				$templateselect .= '<optgroup class="templategroup" label="&nbsp;&nbsp;' . $details['Name'] . '">';
				foreach ($details['Designs'] as $d => $name) {
					$GLOBALS['TemplateID'] = $details['Name'].'/'.$name;
					$GLOBALS['TemplateName'] = $name;
					$templateselect .= $this->ParseTemplate('Template_Select_Option', true, false);
				}
				$templateselect .= '</optgroup>';
			}
		}

		$templateselect .= $this->ParseTemplate('Template_Select_End', true, false);
		return $templateselect;
	}

	/**
	* TimeZoneList
	* Creates a dropdown list of timezones.
	* These are loaded from the language file (TimeZones) and it creates the list from the options provided.
	* If we are viewing the settings page, you cannot change the timezone - you will only get one item in the dropdown list.
	* This should hopefully stop confusion about the server timezone compared to the user timezone.
	*
	* @param String $selected_timezone The currently selected timezone (so it can be pre-selected in the list). This corresponds to the GMT offset (eg +10:00).
	*
	* @see LoadLanguageFile
	* @see GetLang
	*
	* @return String Returns an option list of timezones with the timezone pre-selected if possible.
	*/
	function TimeZoneList($selected_timezone='')
	{
		$settings_page = false;
		if (isset($_GET['Page']) && strtolower($_GET['Page']) == 'settings') {
			$settings_page = true;
		}

		$selected_timezone = trim($selected_timezone);
		$this->LoadLanguageFile('TimeZones');
		$list = '';
		foreach ($GLOBALS['SendStudioTimeZones'] as $pos => $offset) {
			$selected = '';
			if ($offset == $selected_timezone) {
				$selected = ' SELECTED';
			}
			$entry = '<option value="' . $offset . '"' . $selected . '>' . GetLang($offset) . '</option>';

			if (!$settings_page) {
				$list .= $entry;
			}

			if ($settings_page && $selected) {
				$list .= $entry;
			}
		}
		return $list;
	}

	/**
	* ChooseList
	* This prints out the select box which makes you choose a list (to start most processes).
	* If there is only one list, it will automatically redirect you to that particular list (depending on which area you're looking for).
	* Otherwise, it prints out the appropriate template for the area you're working with.
	*
	* @param String $page The page you're working with. This can be send, schedule, autoresponders etc.
	* @param String $action Which step you're up to in the process.
	* @param Boolean $autoredirect This is used to stop autoredirection if you only have access to one list. This may be used for example, if you try to send to a mailing list that has no subscribers. If you do try that, without setting that flag you would get an endless loop.
	*
	* @see User_API::GetLists
	* @see User_API::CanCreateList
	*
	* @return Void Prints out the appropriate template, doesn't return anything.
	*/
	function ChooseList($page='Send', $action='step2', $autoredirect=true)
	{
		$page = strtolower($page);
		$action = strtolower($action);
		$user = GetUser();
		$lists = array();

		if ($page == 'send') {
			$lists = $user->GetLists(false, true);
		} else {
			$lists = $user->GetLists();
		}

		$listids = array_keys($lists);

		if (sizeof($listids) < 1 || $page == '' || $action == '') {
			$GLOBALS['Intro'] = GetLang(ucwords($page) . '_' . ucwords($action));
			$GLOBALS['Lists_AddButton'] = '';

			switch ($page) {
				case 'autoresponders':
					if ($user->CanCreateList() === true) {
						$GLOBALS['Message'] = $this->PrintSuccess('Autoresponder_NoLists', GetLang('ListCreate'));
						$GLOBALS['Lists_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
					} else {
						$GLOBALS['Message'] = $this->PrintSuccess('Autoresponder_NoLists', GetLang('ListAssign'));
					}
					$this->ParseTemplate('Autoresponders_No_Lists');
				break;

				default:
					if ($user->CanCreateList() === true) {
						$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListCreate'));
						$GLOBALS['Lists_AddButton'] = $this->ParseTemplate('List_Create_Button', true, false);
					} else {
						$GLOBALS['Message'] = $this->PrintSuccess('NoLists', GetLang('ListAssign'));
					}
					$this->ParseTemplate('Lists_Manage_Empty');
				break;
			}
			return;
		}

		if (sizeof($listids) == 1) {
			if ($autoredirect) {
				$location = 'index.php?Page=' . $page . '&Action=' . $action . '&list=' . current($listids);
				?>
				<script>
					window.location = '<?php echo $location; ?>';
				</script>
				<?php
				exit();
			}
		}

		if ($page == 'autoresponders') {
			$this->DisplayCronWarning();
		}

		$selectlist = '';
		foreach ($lists as $listid => $listdetails) {
			$tempSubscriberCount = $listdetails['subscribecount'];

			if (array_key_exists('unconfirmedsubscribercount', $listdetails)) {
				$tempSubscriberCount = $tempSubscriberCount - intval($listdetails['unconfirmedsubscribercount']);
				if ($tempSubscriberCount < 0) {
					$tempSubscriberCount = 0;
				}
			}

			if ($tempSubscriberCount == 1) {
				$subscriber_count = GetLang('Subscriber_Count_Active_Confirmed_One');
			} else {
				$subscriber_count = sprintf(GetLang('Subscriber_Count_Active_Confirmed_Many'), $this->FormatNumber($tempSubscriberCount));
			}

			$autoresponder_count = '';

			if (strtolower($page) == 'autoresponders') {
				switch ($listdetails['autorespondercount']) {
					case 0:
						$autoresponder_count = GetLang('Autoresponder_Count_None');
					break;
					case 1:
						$autoresponder_count = GetLang('Autoresponder_Count_One');
					break;
					default:
						$autoresponder_count = sprintf(GetLang('Autoresponder_Count_Many'), $this->FormatNumber($listdetails['autorespondercount']));
					break;
				}
			}
			$selectlist .= '<option value="' . $listid . '">' . htmlspecialchars($listdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . $subscriber_count . $autoresponder_count . '</option>';
		}
		$GLOBALS['SelectList'] = $selectlist;

		$GLOBALS['DisplaySegmentOption'] = 'none';

		if ($page == 'send' && $user->HasAccess('Segments', 'Send')) {
			$selectSegment = '';
			$segments = $user->GetSegmentList();
			$segmentAPI = $this->GetApi('Segment');
			foreach ($segments as $segmentid => $segmentdetails) {
				$selectSegment .= 	'<option value="' . $segmentid . '">'
									. htmlspecialchars($segmentdetails['segmentname'], ENT_QUOTES, SENDSTUDIO_CHARSET)
									. '</option>';
			}
			$GLOBALS['SelectSegment'] = $selectSegment;

			$GLOBALS['DisplaySegmentOption'] = '';
		}

		$this->ParseTemplate($page . '_Step1');
	}

	/**
	 * Send Preview Display
	 *
	 * This will display the frame of the preview email.
	 * The actual preview email will be sent by self::SendPreview.
	 * The call will be made using Ajax function
	 *
	 * @return Void Doesn't return anything. Processes the form and displays a success/error message.
	 * @see self::SendPreview
	 */
	function SendPreviewDisplay()
	{
		$this->ParseTemplate('Preview_EmailWindow');
	}

	/**
	* SendPreview
	* Sends a preview email from the posted form to the email address supplied.
	* Uses the Email_API to put everything together and possibly add attachments.
	* Displays whether the email was sent ok or not.
	*
	* @see GetUser
	* @see User_API::Get
	* @see GetAttachments
	* @see GetApi
	* @see Email_API::Set
	* @see Email_API::AddBody
	* @see Email_API::AppendBody
	* @see Email_API::AddAttachment
	* @see Email_API::AddRecipient
	* @see Email_API::Send
	*
	* @return Void Doesn't return anything. Processes the form and displays a success/error message.
	*/
	function SendPreview()
	{

		$user = GetUser();
		$preview_email = (isset($_POST['PreviewEmail'])) ? $_POST['PreviewEmail'] : false;
		$subject = (isset($_POST['subject'])) ? $_POST['subject'] : '';
		$html = (isset($_POST['myDevEditControl_html'])) ? $_POST['myDevEditControl_html'] : false;
		$text = (isset($_POST['TextContent'])) ? $_POST['TextContent'] : false;
		$id = (isset($_POST['id'])) ? (int)$_POST['id'] : 0;

		$from = (isset($_POST['FromPreviewEmail'])) ? $_POST['FromPreviewEmail'] : $preview_email;

		/**
		* Preview emails are sent to Email Marketer using AJAX which will
		* always encode POST parameters in UTF8. If ISO-8859-1 is being
		* used as the charset and iconv() is available, re-encode the subject
		* and body to ISO-8859-1.
		*/
		if (SENDSTUDIO_DEFAULTCHARSET == 'ISO-8859-1' && function_exists('iconv')) {
			$subject = iconv("UTF-8", SENDSTUDIO_DEFAULTCHARSET, $subject);
			$html = iconv("UTF-8", SENDSTUDIO_DEFAULTCHARSET, $html);
			$text = iconv("UTF-8", SENDSTUDIO_DEFAULTCHARSET, $text);
		}

		if (!$preview_email) {
			$GLOBALS['Error'] = GetLang('NoEmailAddressSupplied');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			print $GLOBALS['Message'];
			return;
		}

		if (!$text && !$html) {
			$GLOBALS['Error'] = GetLang('NoContentToEmail');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			print $GLOBALS['Message'];
			return;
		}

		if ($id > 0) {
			$attachmentsarea = strtolower(get_class($this));
			$attachments_list = $this->GetAttachments($attachmentsarea, $id, true);
		} else {
			$attachments_list = false;
		}

		$email_api = $this->GetApi('Email');
		$email_api->Set('CharSet', SENDSTUDIO_CHARSET);
		$email_api->Set('Subject', $subject);
		$email_api->Set('FromAddress', $from);
		$email_api->Set('ReplyTo', $from);
		$email_api->Set('BounceAddress', $from);


		if ($text) {
			$text = $this->ConvertSurveyContainer($text);
			$email_api->AddBody('text', $text);
			$email_api->AppendBody('text', $user->Get('textfooter'));
			$email_api->AppendBody('text', stripslashes(SENDSTUDIO_TEXTFOOTER));
		}
		if ($html) {
			$html = $this->ConvertSurveyContainer($html);
			$email_api->AddBody('html', $html);
			$email_api->AppendBody('html', $user->Get('htmlfooter'));
			$email_api->AppendBody('html', stripslashes(SENDSTUDIO_HTMLFOOTER));
		}

		if ($attachments_list) {
			$path = $attachments_list['path'];
			$files = $attachments_list['filelist'];
			foreach ($files as $p => $file) {
				$email_api->AddAttachment($path . '/' . $file);
			}
		}

		$email_api->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

		$user_smtpserver = $user->Get('smtpserver');
		if ($user_smtpserver) {
			$email_api->SetSmtp($user_smtpserver, $user->Get('smtpusername'), $user->Get('smtppassword'), $user->Get('smtpport'));
		}

		$format = 'h';

		if ($text && $html) {
			$email_api->Set('Multipart', true);
		} else {
			if ($text) {
				$format = 't';
			}
			if ($html) {
				$format = 'h';
			}
		}

		$preview_email_separate = explode(",", $preview_email);
		$email_sent = array();

		// Limit the number of preview emails that can be sent at one time to 3 emails.
		$email_limit = 3;
		for ($i = 1, $j = count($preview_email_separate); ($i <= $j && $i <= $email_limit); ++$i) {
			$recipient = $preview_email_separate[$i - 1];
			$recipient = trim($recipient);
			if (!$recipient) {
				continue;
			}

			$email_api->AddRecipient($recipient, '', $format);
			$email_sent[] = $recipient;
		}

		$preview_email = implode(', ', $email_sent);

		$send_result = $email_api->Send();

		if (isset($send_result['success']) && $send_result['success'] > 0) {
			$GLOBALS['Message'] = $this->PrintSuccess('PreviewEmailSent', $preview_email);
		} else {
			$failure = array_shift($send_result['fail']);
			$GLOBALS['Error'] = sprintf(GetLang('PreviewEmailNotSent'), $preview_email, htmlspecialchars($failure[1], ENT_QUOTES, SENDSTUDIO_CHARSET));
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
		}

		print $GLOBALS['Message'];
	}

	/**
	 * SendTestPreviewDisplay
	 * This display a popup window that will call perform a call to the "SendStestPreview"
	 * @param String $url URL of the AJAX request
	 * @param String $javascriptParameters Parameters that should be passed to the AJAX request
	 * @return Void Prints out whether the email was sent successfully or not. Doesn't return anything.
	 */
	function SendTestPreviewDisplay($url, $javascriptParameters)
	{
		$GLOBALS['URLAction'] = $url;
		$GLOBALS['JavaScriptParameters'] = $javascriptParameters;
		$this->ParseTemplate('Preview_EmailWindow_Settings');
	}

	/**
	* SendTestPreview
	* This sends a 'preview email' (test email in this case) to the email address supplied.
	*
	* To get an appropriate display of the popup, please use self::SendTestPreviewDisplay()
	* This is to get around the fact that iFrame cannot put a "POST" request.
	*
	* @see GetUser
	* @see GetApi
	* @see User_API::Get
	* @see Email_API::Set
	* @see Email_API::AddBody
	* @see Email_API::AddRecipient
	* @see Email_API::Send
	*
	* @return Void Prints out whether the email was sent successfully or not. Doesn't return anything.
	*/
	function SendTestPreview()
	{
		$user = GetUser();
		$preview_email = (isset($_POST['PreviewEmail'])) ? urldecode($_POST['PreviewEmail']) : false;
		if (isset($_POST['smtp_test']) && $_POST['smtp_test'] != '') {
			$preview_email = urldecode($_POST['smtp_test']);
		}

		$subject = GetLang('TestSendingSubject');
		$text = GetLang('TestSendingEmail');

		if (!$preview_email) {
			$GLOBALS['Error'] = GetLang('NoEmailAddressSupplied');
			print $this->ParseTemplate('ErrorMsg', true, false);
			return;
		}

		$email_api = $this->GetApi('Email');

		$smtp = false;
		if (!empty($_POST['smtp_server'])) {
			$email_api->Set('SMTPServer', urldecode($_POST['smtp_server']));
			$smtp = true;
		}

		if (!empty($_POST['smtp_u'])) {
			$email_api->Set('SMTPUsername', urldecode($_POST['smtp_u']));
		}
		if (!empty($_POST['smtp_p'])) {
			$email_api->Set('SMTPPassword', urldecode($_POST['smtp_p']));
		}

		if (!empty($_POST['smtp_port'])) {
			$email_api->Set('SMTPPort', urldecode($_POST['smtp_port']));
		}

		$email_api->Set('Subject', $subject);
		$email_api->Set('CharSet', SENDSTUDIO_CHARSET);
		$email_api->Set('FromAddress', $preview_email);
		$email_api->Set('ReplyTo', $preview_email);
		$email_api->Set('BounceAddress', $preview_email);

		$user_email = $user->Get('emailaddress');
		if ($user_email) {
			$email_api->Set('FromAddress', $user_email);
			$email_api->Set('BounceAddress', $user_email);
			$email_api->Set('ReplyTo', $user_email);
			$user_name = $user->Get('fullname');
			if ($user_name) {
				$email_api->Set('FromName', $user_name);
			}
		}

		$email_api->AddBody('text', $text);

		$email_api->AddRecipient($preview_email, '', 't');
		$send_result = $email_api->Send();

		if (isset($send_result['success']) && $send_result['success'] > 0) {
			print $this->PrintSuccess('TestEmailSent', $preview_email);
		} else {
			if ($smtp) {
				$failure = array_shift($send_result['fail']);
				$GLOBALS['Error'] = sprintf(GetLang('TestEmailNotSent'), $preview_email, htmlspecialchars($failure[1], ENT_QUOTES, SENDSTUDIO_CHARSET));
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('TestEmailNotSent'), $preview_email, GetLang('ProblemWithLocalMailServer'));
			}
			print $this->ParseTemplate('ErrorMsg', true, false);
		}
	}

	/**
	* CreateDateTimeBox
	* Used to print out a date and time box based on the time passed in. This will pre-select options, check which order to display the date in and create a string of select boxes you can use to print out the date/time.
	*
	* @param Int $time Time to create the box for. If not specified it will create it for "now".
	* @param Boolean $mode24h Whether to create the box in 24h time (true) or 12-hour time (false).
	* @param String $name Name of the datetime box. Defaults to 'datetime'.
	* @param Boolean $hide_datebox Whether to show or hide the datebox option. This defaults to false (that is - show the datebox).
	*
	* @see AdjustTime
	*
	* @return String A string of select boxes ready for printing.
	*/
	function CreateDateTimeBox($time=0, $mode24h=false, $name='datetime', $hide_datebox=false)
	{
		if ($time == 0) {
			$time = AdjustTime(0, true, null, true);
		}

		if ($mode24h) {
			$time = AdjustTime($time, false, 'j:n:Y:G:i');
			list($day_chosen, $mth_chosen, $yr_chosen, $hr_chosen, $min_chosen) = explode(':', $time);
			$currentTime = $hr_chosen . ":" . $min_chosen;
		} else {
			$time = AdjustTime($time, false, 'j:n:Y:A:h:i');
			list($day_chosen, $mth_chosen, $yr_chosen, $meridiem_chosen, $hr_chosen, $min_chosen) = explode(':', $time);
			$currentTime = $hr_chosen . ":" . $min_chosen . $meridiem_chosen;
		}

		$required = $this->ParseTemplate('Required', true, false);

		$style = '';
		if ($hide_datebox) {
			$style = 'display:none';
		}

		$output = '';

		$day_list = '<select style="margin: 0px" name="' . $name . '[day]" class="DateTimeBox">';

		for ($i = 1; $i <= 31; $i++) {
			$day_list .= '<option value="' . $i . '"';
			if ($i == $day_chosen) {
				$day_list .= ' SELECTED';
			}
			$day_list .= '>' . sprintf('%02d', $i) . '</option>';
		}
		$day_list .= '</select>';

		$mth_list = '/ <select style="margin: 0px; width:60px" name="' . $name . '[month]" class="DateTimeBox">';
		for ($i = 1; $i <= 12; $i++) {
			$mth_list .= '<option value="' . $i . '"';
			if ($i == $mth_chosen) {
				$mth_list .= ' SELECTED';
			}
			$mth_list .= '>' . GetLang($this->Months[$i]) . '</option>';
		}
		$mth_list .= '</select>';

		$yr_list = '/ <select style="margin: 0px; width:60px" name="' . $name . '[year]" class="DateTimeBox">';
		for ($i = ($yr_chosen - 2); $i <= ($yr_chosen + 5); $i++) {
			$yr_list .= '<option value="' . $i . '"';
			if ($i == $yr_chosen) {
				$yr_list .= ' SELECTED';
			}
			$yr_list .= '>' . sprintf('%02d', $i) . '</option>';
		}
		$yr_list .= '</select>';

		$date_order = explode(' ', GetLang('DateTimeBoxFormat'));
		foreach ($date_order as $p => $order) {
			switch (strtolower($order)) {
				case 'd':
					$output .= '&nbsp;' . $day_list . '&nbsp;';
				break;
				case 'm':
					$output .= '&nbsp;' . $mth_list . '&nbsp;';
				break;
				case 'y':
					$output .= '&nbsp;' . $yr_list . '&nbsp;';
				break;
			}
		}
		$output = substr($output, 0, -6);

		// Output "at"
		$output .= '&nbsp;&nbsp;' . GetLang('Schedule_At') . '&nbsp;&nbsp;';

		// Output the select box for hours
		$output .= '<select onchange="SetSendTime()" style="width:50px" name="sendtime_hours" id="sendtime_hours">';

		$max_hour = ($mode24h ? 23 : 12);
		for ($i = 1; $i <= $max_hour; $i++) {
			$hour = (string)$i;

			if ($hour == $hr_chosen) {
				$sel = 'SELECTED="SELECTED"';
			}
			else {
				$sel = '';
			}

			$output .= sprintf('<option %s value="%s">%s</option>', $sel, sprintf('%02d', $hour), $hour);
		}
		$output .= '</select> : ';

		// Output the select box for minutes
		$output .= '<select onchange="SetSendTime()" style="width:50px" name="sendtime_minutes" id="sendtime_minutes">';
		for ($i = 0; $i <= 59; $i++) {
			$min = (string)$i;

			if ($min == $min_chosen) {
				$sel = 'SELECTED="SELECTED"';
			}
			else {
				$sel = '';
			}

			if (strlen($min) == 1) {
				$min = "0" . $min;
			}

			$output .= sprintf('<option %s value="%s">%s</option>', $sel, sprintf('%02d', $min), $min);
		}
		$output .= '</select> ';

		// Output the select box for meridian
		if (!$mode24h) {
			$output .= '&nbsp;<select onchange="SetSendTime()" style="width:50px" name="sendtime_ampm" id="sendtime_ampm">';

			$sel = ($meridiem_chosen == 'AM' ? 'SELECTED="SELECTED' : '');
			$output .= '	<option ' . $sel . ' value="AM">AM</option>';

			$sel = ($meridiem_chosen == 'PM' ? 'SELECTED="SELECTED' : '');
			$output .= '	<option ' . $sel . ' value="PM">PM</option>';

			$output .= '</select> ';
		}

		return $output;
	}

	/**
	* TimeDifference
	* Returns the time difference in an easy format / unit system (eg how many seconds, minutes, hours etc).
	*
	* @param Int $timedifference Time difference as an integer to transform.
	*
	* @return String Time difference plus units.
	*/
	function TimeDifference($timedifference)
	{
		if ($timedifference < 60) {
			if ($timedifference == 1) {
				$timechange = GetLang('TimeTaken_Seconds_One');
			} else {
				$timechange = sprintf(GetLang('TimeTaken_Seconds_Many'), $this->FormatNumber($timedifference, 0));
			}
		}

		if ($timedifference >= 60 && $timedifference < 3600) {
			$num_mins = floor($timedifference / 60);

			$secs = floor($timedifference % 60);

			if ($num_mins == 1) {
				$timechange = GetLang('TimeTaken_Minutes_One');
			} else {
				$timechange = sprintf(GetLang('TimeTaken_Minutes_Many'), $this->FormatNumber($num_mins, 0));
			}

			if ($secs > 0) {
				$timechange .= ', ' . sprintf(GetLang('TimeTaken_Seconds_Many'), $this->FormatNumber($secs, 0));
			}
		}

		if ($timedifference >= 3600) {
			$hours = floor($timedifference/3600);
			$mins = floor($timedifference % 3600) / 60;

			if ($hours == 1) {
				if ($mins == 0) {
					$timechange = GetLang('TimeTaken_Hours_One');
				} else {
					$timechange = sprintf(GetLang('TimeTaken_Hours_One_Minutes'), $this->FormatNumber($mins, 0));
				}
			}

			if ($hours > 1) {
				if ($mins == 0) {
					$timechange = sprintf(GetLang('TimeTaken_Hours_Many'), $this->FormatNumber($hours, 0));
				} else {
					$timechange = sprintf(GetLang('TimeTaken_Hours_Many_Minutes'), $this->FormatNumber($hours, 0), $this->FormatNumber($mins, 0));
				}
			}
		}

		// can expand this futher to years/months etc - the schedule_manage file has it all done in javascript.

		return $timechange;
	}

	/**
	* GetPageContents
	* Returns the url's contents.
	*
	* @param String $url The url to import from.
	*
	* @return False|String Returns false if there is no url or it can't be opened (invalid url). Otherwise returns the content from the url. Tries to use curl functions if they are available, otherwise it uses 'fopen' if it's available (ie safe-mode is off and it's not disabled).
	*/
	function GetPageContents($url='')
	{
		if (!$url) {
			return array(false, 'No URL');
		}

		// in case the url has spaces in it, convert them to %20's.
		// we can't just use rawurlencode because that will stuff up subfolders, eg:
		// http://www.domain.com/folder/subfolder/my news.html
		$url = str_replace(' ', '%20', $url);

		if (SENDSTUDIO_CURL) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			if (!SENDSTUDIO_SAFE_MODE && ini_get('open_basedir') == '') {
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			}
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);

			// Set up headers to "masquarade" as Firefox
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.12) Gecko/20080201 Firefox/2.0.0.12",
				"Accept: text/xml,application/xml,application/xhtml+xml, text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
				"Cache-Control: max-age=0",
				"Connection: keep-alive",
				"Keep-Alive: 300",
				"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
				"Accept-Language: en-us,en;q=0.5"
			));

			$pageData = curl_exec($ch);

			if (!$pageData) {
				$error = curl_error($ch);
			}
			curl_close($ch);

			if (!$pageData) {
				return array(false, $error);
			}
			return array($pageData, true);
		}

		if (!SENDSTUDIO_FOPEN) {
			return array(false, GetLang('NoCurlOrFopen'));
		}

		if (!@$fp = fopen($url, "rb")) {
			return array(false, GetLang('URLCantBeRead'));
		}

		// Grab the files content
		$pageData = "";

		while (!feof($fp)) {
			$pageData .= fgets($fp, 4096);
		}

		fclose($fp);

		return array($pageData, true);
	}

	/**
	* DenyAccess
	* Prints out an access denied message, the footer, and quits.
	*
	* @param string $message Message to display (OPTIONAL)
	* @return Void Doesn't return anything.
	*/
	function DenyAccess($message = false)
	{
		if (empty($message)) {
			$message = GetLang('NoAccess', 'You are not authorized to access this resource.');
		}

		$tpl = GetTemplateSystem();
		$tpl->Assign('ErrorMessage', $message);
		$tpl->ParseTemplate('Common_AccessDenied');

		$this->PrintFooter();
		exit();
	}

	/**
	* PrintSubscribeDate
	* Prints out subscribe date searching so you can easily display the day/month/year dropdown boxes used in searching.
	*
	* @see Subscribers_Manage::Process
	*
	* @return Void Doesn't return anything, puts information into the GLOBALS['Display_subdate_date1_Field_X'] placeholders.
	*/
	function PrintSubscribeDate()
	{

		$time = AdjustTime(0, true, null, true);

		$dd_start = $dd_end = AdjustTime($time, false, 'd');
		$mm_start = $mm_end = AdjustTime($time, false, 'm');
		$yy_start = $yy_end = AdjustTime($time, false, 'Y');

		$field_order = array('day', 'month', 'year');

		$daylist_start = $daylist_end = '<select name="datesearch[dd_whichone]" class="datefield">';
		for ($i=1; $i<=31; $i++) {
			$dom = $i;
			$i = sprintf("%02d", $i);
			$sel = '';

			if ($i==$dd_start) {
				$sel='SELECTED';
			}

			$daylist_start.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.$dom . '</option>';

			$sel = '';
			if ($i==$dd_end) {
				$sel='SELECTED';
			}

			$daylist_end.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.$dom . '</option>';

		}
		$daylist_start.='</select>';
		$daylist_end.='</select>';

		$monthlist_start = $monthlist_end ='<select name="datesearch[mm_whichone]" class="datefield">';
		for ($i=1;$i<=12;$i++) {
			$mth = $i;
			$sel = '';
			$i = sprintf("%02d",$i);

			if ($i==$mm_start) {
				$sel='SELECTED';
			}

			$monthlist_start.='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.GetLang($this->Months[$mth]) . '</option>';

			if ($i==$mm_end) {
				$sel='SELECTED';
			}

			$monthlist_end .='<option '.$sel.' value="'.sprintf("%02d",$i).'">'.GetLang($this->Months[$mth]) . '</option>';

		}
		$monthlist_start.='</select>';
		$monthlist_end.='</select>';

		$yearlist_start ='<input type="text" maxlength="4" size="4" value="'.$yy_start.'" name="datesearch[yy_whichone]" class="datefield">';
		$yearlist_end ='<input type="text" maxlength="4" size="4" value="'.$yy_end.'" name="datesearch[yy_whichone]" class="datefield">';

		foreach ($field_order as $p => $order) {
			switch ($order) {
				case 'day':
					$GLOBALS['Display_subdate_date1_Field'.($p+1)] = str_replace('_whichone', '_start', $daylist_start);
				break;
				case 'month':
					$GLOBALS['Display_subdate_date1_Field'.($p+1)] = str_replace('_whichone', '_start', $monthlist_start);
				break;
				case 'year':
					$GLOBALS['Display_subdate_date1_Field'.($p+1)] = str_replace('_whichone', '_start', $yearlist_start);
				break;
			}
		}

		foreach ($field_order as $p => $order) {
			switch ($order) {
				case 'day':
					$GLOBALS['Display_subdate_date2_Field'.($p+1)] = str_replace('_whichone', '_end', $daylist_end);
				break;
				case 'month':
					$GLOBALS['Display_subdate_date2_Field'.($p+1)] = str_replace('_whichone', '_end', $monthlist_end);
				break;
				case 'year':
					$GLOBALS['Display_subdate_date2_Field'.($p+1)] = str_replace('_whichone', '_end', $yearlist_end);
				break;
			}
		}
	}

	/**
	* PrintWarning
	* Returns the parsed template 'WarningMsg' with the appropriate variables placed in the message.
	* You can pass in any number of arguments. The first one is always the language variable to use.
	* The others are placeholders for within that language variable.
	* <b>Example</b>
	* <code>
	* PrintWarning('MyMessage', $number);
	* </code>
	* will return as if you put
	* <code>
	* sprintf(GetLang('MyMessage'), $number);
	* </code>
	*
	* If you need to pass in another language variable, it must be changed before passing into the function.
	* <b>Example</b>
	* <code>
	* PrintWarning('MyMessage', GetLang('SecondMessage'));
	* </code>
	*
	* @see ParseTemplate
	* @see DisplayCronWarning
	*
	* @return String Returns the parsed warningmsg template.
	*/
	function PrintWarning()
	{
		$tpl = GetTemplateSystem();
		$arg_list = func_get_args();
		$langvar = array_shift($arg_list);
		$GLOBALS['Warning'] = vsprintf(GetLang($langvar), $arg_list);
		return $tpl->ParseTemplate('WarningMsg', true, false);
	}

	/**
	* PrintSuccess
	* Returns the parsed template 'SuccessMsg' with the appropriate variables placed in the message.
	* You can pass in any number of arguments. The first one is always the language variable to use.
	* The others are placeholders for within that language variable.
	* <b>Example</b>
	* <code>
	* PrintSuccess('MyMessage', $number);
	* </code>
	* will return as if you put
	* <code>
	* sprintf(GetLang('MyMessage'), $number);
	* </code>
	*
	* If you need to pass in another language variable, it must be changed before passing into the function.
	* <b>Example</b>
	* <code>
	* PrintSuccess('MyMessage', GetLang('SecondMessage'));
	* </code>
	*
	* @see ParseTemplate
	*
	* @return String Returns the parsed successmsg template.
	*/
	function PrintSuccess()
	{
		$tpl = GetTemplateSystem();
		$arg_list = func_get_args();
		$langvar = array_shift($arg_list);
		$GLOBALS['Success'] = vsprintf(GetLang($langvar), $arg_list);
		return $tpl->ParseTemplate('SuccessMsg', true, false);
	}

	/**
	 * PrintError
	 *
	 * @see PrintSuccess
	 *
	 * @return String Returns the parsed errormsg template.
	 */
	function PrintError()
	{
		$tpl = GetTemplateSystem();
		$arg_list = func_get_args();
		$langvar = array_shift($arg_list);
		$GLOBALS['Error'] = vsprintf(GetLang($langvar), $arg_list);
		return $tpl->ParseTemplate('ErrorMsg', true, false);
	}

	/**
	* DisplayCronWarning
	* This may show a warning message if cron is not enabled
	* It may also show a warning message if cron is enabled but has not run successfully yet.
	* This is shown on the autoresponder pages to let people know what's going on
	*
	* @param Boolean $autoresponderpage Whether you are on the autoresponder page or not. If you are on the autoresponder page, the warning message is a little different to non-autoresponder pages.
	* @see GetApi
	* @see Settings_API::CronEnabled
	* @see Settings_API::CheckCron
	* @uses Settings_API::CheckCronStillRunning()
	*
	* @return Void Doesn't return anything. If there is a message to show, it calls PrintWarning. Otherwise nothing happens.
	*/
	function DisplayCronWarning($autoresponderpage=true)
	{
		$settings_api = $this->GetApi('Settings');
		$cron_enabled = $settings_api->CronEnabled($autoresponderpage);
		if (!$cron_enabled && $autoresponderpage) {
			$GLOBALS['CronWarning'] = $this->PrintWarning('AutoresponderCronNotEnabled');
		} else {
			$cron_check = $settings_api->CheckCron();
			if (!$cron_check) {
				$GLOBALS['CronWarning'] = $this->PrintWarning('CronNotSetup');
			} else {
				$cron_check = $settings_api->CheckCronStillRunning(3);
				if (!$cron_check) {
					$GLOBALS['CronWarning'] = $this->PrintWarning('CronSkippedProblem', 3);
				}
			}
		}
	}

	/**
	* TruncateName
	* Truncates a name to the _MaxNameLength name (minus 4) - so we can append '...' to the end.
	*
	* @param String $string String to truncate to the max length.
	* @param Int $length Max length to show. Defaults to _MaxNameLength.
	*
	* @see _MaxNameLength
	*
	* @return String The truncated string or the original string if it's less than MaxNameLength chars long
	*/
	function TruncateName($string='', $length=0)
	{
		if ($length == 0) {
			$length = $this->_MaxNameLength;
		}
		if (SENDSTUDIO_CHARSET == 'UTF-8') {
			if (utf8_strlen($string) > $length) {
				return utf8_substr($string, 0, ($length - 4)) . ' ...';
			}
		} else {
			if (strlen($string) > $length) {
				return substr($string, 0, ($length - 4)) . ' ...';
			}
		}
		return $string;
	}

	/**
	 * Truncates a string in the middle if it exceeds a certain length. The truncated section will be replaced with '...'.
	 *
	 * @param String $string The string that might get truncated in the middle.
	 * @param Int $max_length The maximum length the string can be without being truncated.
	 *
	 * @return String The string with the middle chopped out
	 */
	function TruncateInMiddle($string, $max_length=80)
	{
		$s_len = strlen($string);
		if ($s_len > $max_length) {
			$cut_to = $max_length/2 - 2; // subtract 2 and 1 (below) to make up for the '...'
			$cut_from = $s_len - ($max_length/2 - 1);
			$string = substr($string, 0, $cut_to) . '...' . substr($string, $cut_from);
		}
		return $string;
	}

	/**
	* CheckForUnsubscribeLink
	* Checks the content passed in to see if there is an unsubscribe link or not. This allows us to display a warning message about the problem if it's not present.
	* If FORCE_UNSUBLINK is switched on, this isn't needed.
	*
	* @param String $content Content to search for the unsubscribe link in.
	* @param String $contenttype Whether the content is html or text. This is used so we know which type of footer to check as well as the content passed in.
	*
	* @return Boolean Returns true if FORCE_UNSUBLINK is on, or if there is an unsubscribe link present. Otherwise returns false.
	*/
	function CheckForUnsubscribeLink($content='', $contenttype='html')
	{
		if (!$content) {
			return true;
		}

		if (SENDSTUDIO_FORCE_UNSUBLINK) {
			return true;
		}

		if (preg_match('/%basic:unsublink%/i', $content)) {
			return true;
		}

		if (preg_match('/%%unsubscribelink%%/i', $content)) {
			return true;
		}

		$user = GetUser();

		$global_footer_content = '';
		$user_footer_content = '';

		switch ($contenttype) {
			case 'text':
				$user_footer_content = $user->Get('textfooter');
				$global_footer_content = SENDSTUDIO_TEXTFOOTER;
			break;

			case 'html':
				$user_footer_content = $user->Get('htmlfooter');
				$global_footer_content = SENDSTUDIO_HTMLFOOTER;
			break;
		}

		if (preg_match('/%basic:unsublink%/i', $user_footer_content)) {
			return true;
		}

		if (preg_match('/%%unsubscribelink%%/i', $user_footer_content)) {
			return true;
		}

		if (preg_match('/%basic:unsublink%/i', $global_footer_content)) {
			return true;
		}

		if (preg_match('/%%unsubscribelink%%/i', $global_footer_content)) {
			return true;
		}

		return false;
	}

	/**
	* DisabledItem
	* This returns the "disableditem" template with the placeholders replaced. Use a simple function so it's easier to use everywhere.
	*
	* @param String $itemname The name of the item that is being disabled. Eg, edit, copy, delete.
	* @param String $itemtitle The title to put on the item when you hover over it. By default it's "NoAccess" but if you pass in a language variable name it will use that instead.
	*
	* @return String Returns the template parsed (language variables replaced).
	*/
	function DisabledItem($itemname='', $itemtitle='NoAccess')
	{
		$GLOBALS['ItemTitle'] = GetLang($itemtitle);
		$GLOBALS['ItemName'] = GetLang($itemname);
		return '&nbsp;&nbsp;' . $this->ParseTemplate('DisabledItem', true, false);
	}

	/**
	 * CheckForAttachments
	 * Checks if any users have $content_types with attachments in them.
	 *
	 * @param Array $ids An array of User IDs
	 * @param String $content_type Can be 'newsletters' or 'autoresponders'
	 *
	 * @return Boolean Returns false if attachments aren't allowed and attachments are detected, otherwise true.
	 */
	function CheckForAttachments($ids=array(), $content_type='newsletters')
	{
		if (SENDSTUDIO_ALLOW_ATTACHMENTS) {
			return true;
		}

		if (!is_array($ids)) {
			$ids = array($ids);
		}

		$ids_to_disable = array();

		$files = list_files(TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $content_type, null, true);
		if (!empty($files)) {
			foreach ($ids as $p => $id) {
				if (isset($files[$id]['attachments'])) {
					if (!empty($files[$id]['attachments'])) {
						$ids_to_disable[] = $id;
					}
				}
			}
		}

		if (empty($ids_to_disable)) {
			return true;
		}
		return false;
	}

	/**
	* EasySize
	* Turns a size into an appropriate unit. Eg bytes, Kb, Mb, Gb etc.
	*
	* @param Int $size Size to convert
	* @param Int $decimals Number of decimal places to round to. Defaults to 2.
	*
	* @see FormatNumber
	*
	* @return String The size in the appropriate unit (with unit attached).
	*/
	function EasySize($size=0, $decimals=2)
	{
		if ($size < 1024) {
			return $this->FormatNumber($size) . GetLang('Bytes');
		}

		if ($size >= 1024 && $size < (1024*1024)) {
			return $this->FormatNumber(($size/1024), $decimals) . GetLang('KiloBytes');
		}

		if ($size >= (1024*1024) && $size < (1024*1024*1024)) {
			return $this->FormatNumber(($size/1024/1024), $decimals) . GetLang('MegaBytes');
		}

		if ($size >= (1024*1024*1024)) {
			return $this->FormatNumber(($size/1024/1024/1024), $decimals) . GetLang('GigaBytes');
		}
	}

	/**
	* GetSize
	* Get the size of the content based on the array of info passed in.
	*
	* @param Array $session_info The array of info passed in includes multipart, embedimages, the content and the item's id. This is all used to work out how big the item is going to be approximately. This can either be from an autoresponder or a newsletter.
	*
	* @see Autoresponders
	* @see Newsletters
	*
	* @return Array Returns an array containing the approximate size (bytes) of the item and a message containing images that couldn't be loaded properly.
	*/
	function GetSize($session_info=array())
	{
		if (empty($session_info)) {
			return array(0, '');
		}

		$multipart = (isset($session_info['multipart']) && $session_info['multipart']) ? true : false;
		$embed_images = (isset($session_info['embedimages']) && $session_info['embedimages']) ? true : false;

		if (!SENDSTUDIO_ALLOW_EMBEDIMAGES) {
			$embed_images = false;
		}

		$total_size = 0;

		$html_body = $session_info['contents']['html'];
		$html_size = utf8_strlen($html_body);
		$text_size = utf8_strlen($session_info['contents']['text']);

		// if you are sending multipart, then put both parts together to work out an approximate size.
		if ($multipart) {
			$total_size += $html_size + $text_size;
		} else {
			// if you are not sending multipart, then try to work out the html part (as a guide for maximum size).
			if ($html_size > 0) {
				$total_size += $html_size;
			} else {
				$total_size += $text_size;
			}
		}

		if (isset($session_info['autoresponderid'])) {
			$attachments = $this->GetAttachments('autoresponders', $session_info['autoresponderid'], true);
		} else {
			$attachments = $this->GetAttachments('newsletters', $session_info['id'], true);
		}

		if (isset($attachments['filelist'])) {
			foreach ($attachments['filelist'] as $p => $attachment) {
				$file = $attachments['path'] . '/' . $attachment;
				// base64 encoding adds about 30% overhead so we need to add it here.
				$total_size += 1.3 * filesize($file);
			}
		}

		$email_api = $this->GetApi('Email');

		$problem_images = array();

		// we'll do a quick check for the images in the html content to make sure they all work.
		if (SENDSTUDIO_ALLOW_EMBEDIMAGES) {
			$email_api->Set('EmbedImages', true);
			$email_api->AddBody('html', $html_body);
			$images = $email_api->GetImages();
			if (is_array($images)) {
				foreach ($images as $md5 => $image_url) {
					list($img, $error) = $email_api->GetImage($image_url);
					if ($img) {
						if ($embed_images) {
							// base64 encoding adds about 30% overhead so we need to add it here.
							$total_size += 1.3 * strlen($img);
						}
					} else {
						$problem_images[] = array('img' => $image_url, 'error' => $error);
					}
				}
			}
		}

		$img_warning = '';

		if (!empty($problem_images)) {
			foreach ($problem_images as $p => $problem_details) {
				$img_warning .= sprintf(GetLang('UnableToLoadImage'), $problem_details['img'], $this->TruncateName($problem_details['img'], 100), $problem_details['error']);
			}
		}

		return array($total_size, $img_warning);
	}

	/**
	 * Function to display HTML frame for spam check
	 *
	 * This method will display the HTML that will frame spam content checking.
	 * The actual spam checking will be processed in self::CheckContentForSpam
	 * which will be called by an Ajax call
	 *
	 * @see self::CheckContentForSpam
	 *
	 * @param bool $force If true, will only allow form submission to continue if the spam check passes.
	 *
	 * @return Void Returns nothing, it just display spam check frame to the browser
	 */
	function CheckContentForSpamDisplay($force=false)
	{
		$tpl = GetTemplateSystem();
		$tpl->Assign('force', $force);
		$tpl->ParseTemplate('Spam_Check_Display');
	}

	/**
	 * ChekContentForSpam
	 * Function to check for spam keywords within an email.
	 * This function is called by an Ajax request.
	 *
	 * @param string $text The text content of the email.
	 * @param string $html The HTML content of the email.
	 *
	 * @see self::CheckContentForSpamDisplay
	 * @see Application.Modules.SpamCheck
	 *
	 * @return Void Returns nothing, it displays the contents of the spam check frame.
	 */
	function CheckContentForSpam($text, $html)
	{
		$tpl = GetTemplateSystem();
		$spam_api = $this->GetApi('Spam_Check');
		$result = $spam_api->Process($text, $html);

		$types = array();
                if ($html) {
                    array_push($types, 'html');
                }
                if ($text) {
                   array_push($types, 'text');
                }

		foreach ($types as $type) {

                    $rating = $result[$type]['rating'];
                    $score = $result[$type]['score'];
                    $broken_rules = $result[$type]['broken_rules'];

                    if (empty($broken_rules)) {
                            if (${$type} !== false) {
                                    $tpl->Assign('spam_heading', GetLang('Spam_Heading_intro_notspam_' . $type));
                                    $tpl->ParseTemplate('Spam_Check_Row_Not_Broken');
                            }
                            continue;
                    }
                    $tpl->Assign('spam_heading', GetLang('Spam_Heading_intro_' . $type));
                    $tpl->ParseTemplate('Spam_Check_SubHeader');

                    foreach ($broken_rules as $details) {
                            $tpl->Assign('rule_broken', $details[0]);
                            $tpl->Assign('rule_score', $this->FormatNumber($details[1], 1));
                            $tpl->ParseTemplate('Spam_Check_Row');
                    }

                    $is_spam = 0;
                    switch ($rating) {
                            case Spam_Check_API::RATING_SPAM:
                                    $display_style = 'spam';
                                    $is_spam = 1;
                                    break;
                            case Spam_Check_API::RATING_ALERT:
                                    $display_style = 'alert';
                                    break;
                            case Spam_Check_API::RATING_NOT_SPAM:
                                    $display_style = 'notspam';
                                    break;
                            default:
                                    $display_style = '';
                    }

                    $score_percent = number_format((($score / Spam_Check_API::RATING_SPAM) * 100), 0);
                    if ($score_percent > 100) {
                            $score_percent = 100;
                    }

                    $tpl->Assign('spam_percentage', $score_percent);
                    $tpl->Assign('spam_display_style', $display_style);
                    $tpl->Assign('spam_rating_message', sprintf(GetLang('Spam_Rating_Message'), $score, Spam_Check_API::RATING_SPAM, GetLang('Spam_Rating_' . $display_style)));
                    $tpl->Assign('is_spam', $is_spam);
                    $tpl->Assign('type', $type);

                    $tpl->ParseTemplate('Spam_Check_SubFooter');
                    if ($type == 'html') {
                            $tpl->ParseTemplate('Spam_Check_Row_Empty');
                    }

		}
	}

	/**
	 * ShowLinksClickedOptions
	 * Determines whether to show the normal or advanced Clicked Link template.
	 *
	 * @return String The appropriate parsed template.
	 */
	function ShowLinksClickedOptions()
	{
		if (
			SENDSTUDIO_DATABASE_TYPE == 'pgsql' ||
			(version_compare(SENDSTUDIO_SYSTEM_DATABASE_VERSION, '4.1', '>='))
		) {
			return $this->ParseTemplate('Clicked_Link_Advanced', true, false);
		} else {
			return $this->ParseTemplate('Clicked_Link', true, false);
		}
	}

	/**
	 * ShowOpenedNewsletterOptions
	 * Determines whether to show the normal or advanced Opened Newsletter template.
	 *
	 * @return String The appropriate parsed template.
	 */
	function ShowOpenedNewsletterOptions()
	{
		if (
			SENDSTUDIO_DATABASE_TYPE == 'pgsql' ||
			(version_compare(SENDSTUDIO_SYSTEM_DATABASE_VERSION, '4.1', '>='))
		) {
			return $this->ParseTemplate('Opened_Newsletter_Advanced', true, false);
		} else {
			return $this->ParseTemplate('Opened_Newsletter', true, false);
		}
	}

	/**
	* _CleanupOldQueues
	* This function cleans up old 'queues' or pending 'imports' if we navigate away from the page we are supposed to be on.
	* For example, we start an export but then go to the homepage.
	* This function will detect we are not on the 'export' page and will clean up the 'export' queue
	* It will do the same for cleaning up old import files.
	* It is called from ShowInfoTip which is called on every page that prints out the header (ie not in a popup window)
	* It also cleans up the TEMP_DIRECTORY folder so any old import or export files will be deleted after 30 days.
	*
	*
	* @param String $page This is the current page we are viewing. This tells us whether we need to do any cleanups or not.
	* @param String $action The current page action. This also tells us whether we need to do any cleanups or not.
	*
	* @see ShowInfoTip
	*
	* @return Void Doesn't return anything.
	*
	* @uses EventData_IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES
	*/
	function _CleanupOldQueues($page='', $action='')
	{
		$page = strtolower($page);
		$action = strtolower($action);

		$user = GetUser();

		$api = $this->GetApi('Subscribers');

		if ($page != 'send' && $page != 'schedule') {

			/**
			* If a send has not been approved by going to the "schedule" page or clicking "start sending",
			* we need to silently clean it up here.
			* This means they have either cancelled the send or haven't finished the process (ie browsed to somewhere else). Either way, since they haven't gone to the last page we need to clean up the job.
			*/

			$approve_job = IEM::sessionGet('ApproveJob');

			if ($approve_job) {

				$send_size = IEM::sessionGet('JobSendSize');

				$statsapi = $this->GetApi('Stats');
				$jobapi = $this->GetApi('Jobs');
				// we need to start the job
				// then get the queue
				// then we can get the stats
				// so a user can get their credits back
				// if they cancel a send before doing anything.
				$jobapi->StartJob($approve_job);
				$queueid = $jobapi->GetJobQueue($approve_job);

				$statid = $statsapi->GetStatsByQueue($queueid);

				$statsapi->Delete($statid, 'n');
				$statsapi->RefundCredit($user->userid, $approve_job);
				$jobapi->PauseJob($approve_job);
				$jobapi->Delete($approve_job);

				IEM::sessionRemove('JobSendSize');
				IEM::sessionRemove('ApproveJob');
			}
		}

		// clean up the old queue and export file if it didn't complete properly before.
		if ($action != 'export') {
			$exportinfo = IEM::sessionGet('ExportInfo');
			if ($exportinfo && is_array($exportinfo)) {
				if (isset($exportinfo['ExportQueue'])) {
					$queueid = $exportinfo['ExportQueue'];
					if ($queueid) {
						if (is_array($queueid)) {
							foreach ($queueid as $each) {
								$api->ClearQueue($each['queueid'], 'export');
							}
						} else {
							$api->ClearQueue($queueid, 'export');
						}
					}
				}
				if (isset($exportinfo['ExportFile'])) {
					$exportfile = $exportinfo['ExportFile'];
					if (is_file($exportfile)) {
						unlink(TEMP_DIRECTORY . '/' . $exportfile);
					}
				}
			}
			IEM::sessionRemove('ExportInfo');
		}

		// make sure there are no other import files lying around from a bad attempt.
		if ($action != 'import') {
			$importinfo = IEM::sessionGet('ImportInfo');
			if (isset($importinfo['Filename'])) {
				if (is_file(TEMP_DIRECTORY . '/' . $importinfo['Filename'])) {
					unlink(TEMP_DIRECTORY . '/' . $importinfo['Filename']);
				}
			}
			if (isset($importinfo['FileList'])) {
				foreach ($importinfo['FileList'] as $p => $filename) {
					if (is_file(TEMP_DIRECTORY . '/' . $filename)) {
						unlink(TEMP_DIRECTORY . '/' . $filename);
					}
				}
			}
			IEM::sessionRemove('ImportInfo');
		}

		$files = list_files(TEMP_DIRECTORY);

		foreach ($files as $file) {
			$fullpath = TEMP_DIRECTORY . '/' . $file;
			if (!is_file($fullpath)) {
				continue;
			}

			$filetype = substr($file, 0, 6);
			if ($filetype != 'import' && $filetype != 'export' && $filetype != 'stats_') {
				continue;
			}

			$last_mod_time = filemtime($fullpath);
			if ($last_mod_time < strtotime('-1 days')) {
				@unlink($fullpath);
			}
		}

		/**
		 * Trigger event
		 */
			$tempEventData = new EventData_IEM_SENDSTUDIOFUNCTIONS_CLEANUPOLDQUEUES();
			$tempEventData->page = $page; // Not passing by reference, as we don't want the listeners to change them
			$tempEventData->action = $action; // Not passing by reference, as we don't want the listeners to change them
			$tempEventData->trigger();

			unset($tempEventData);
		/**
		 * -----
		 */
	}

	/**
	 * Determines the root-relative path of the application.
	 *
	 * @see SetDevEditPath
	 *
	 * @return String The root relative path.
	 */
	function GetAppPath()
	{
	    $path_parts = parse_url(SENDSTUDIO_APPLICATION_URL);
		if (!isset($path_parts['path'])) {
			$path_parts['path'] = '';
		}
		return rtrim($path_parts['path'], '/');
	}

	/**
	* GoogleCalendarAdd
	* Uses the Google Calendar API to add an event to Google Calendar
	*
	* @param Array $data An array containing username, password, what, where, description, datefrom, timefrom, dateto, timeto, and allday
	* @param Boolean $test_auth Set to true to test authentication details only. This will throw an exception if authentication fails or will return true
	*
	* @throws Exception If an error occurs this function will throw an exception.
	*
	* @return Void Returns nothing
	*/
	function GoogleCalendarAdd($data,$test_auth = false)
	{
		if (!class_exists('GoogleCalendar', false)) {
			require_once(SENDSTUDIO_LIB_DIRECTORY . "/googlecalendar/googlecalendar.php");
		}

		$googlecalendar = new GoogleCalendar($data['username'],$data['password']);

		if ($test_auth) {
			return;
		}

		// Populate the event with the desired information
		$event['title'] = $data['what'];
		if (isset($data['where'])) {
			$event['where'] = $data['where'];
		}
		if (isset($data['description'])) {
			$event['text'] = $data['description'];
		}

		$user = GetUser();

		$allday = false;
		if (isset($data['allday'])) {
			$allday = true;
		}

		$event['when'] = array();
		$event['when']['from'] = $this->GoogleCalendarDate($data['datefrom'],$data['timefrom'],$allday,false);
		$event['when']['to'] = $this->GoogleCalendarDate($data['dateto'],$data['timeto'],$allday,true);

		if ($event['when']['from'] == $event['when']['to'] && $allday) {
			unset($event['when']['to']);
		}

		$newEvent = $googlecalendar->addSimpleEvent($event);
	}

	/**
	* GoogleCalendarDate
	* Parses date and time for use in Google Calendar API
	*
	* @param String $datetext The date in dd/mm/yyyy format
	* @param String $timetext The time in hh:nn am/pm format
	* @param String $tzOffset The timezone in +0:00 format
	* @param Boolean $allday True if the time should be omitted (date is for the entire day) or false to include the time
	*
	* @return String Returns the date formatted for use in Google Calendar
	*/
	function GoogleCalendarDate($datetext,$timetext,$allday,$endtime)
	{
		if (!preg_match('~(\d{1,2})/(\d{1,2})/(\d{4})~',$datetext,$matches)) {
			return false;
		}
		if (!$allday && !preg_match('~(\d{1,2}):(\d{1,2})\s+(am|pm)~i',$timetext,$matchest)) {
			return false;
		}

		$hour = $matchest[1];
		if (strtolower($matchest[3]) == 'pm') {
			$hour += 12;
		}

		if ($allday) {
			$date = (int)$matches[1];
			if ($endtime) {
				$date++;
			}
			return sprintf('%04d-%02d-%02d',$matches[3],$matches[2],$date);
		} else {
			return  sprintf('%04d-%02d-%02dT%02d:%02d:00.000',$matches[3],$matches[2],$matches[1],$hour,$matchest[2]);
		}
	}

	/**
	 * _getPOSTRequest
	 * Get request variable from $_POST
	 *
	 * @param String $variableName Variable name
	 * @param Mixed $defaultValue Default value if variable not found
	 * @return Mixed Return variable value from $_POST if it exists, otherwise it will return defaultValue
	 *
	 * @access private
	 */
	function _getPOSTRequest($variableName, $defaultValue = '')
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
	 * @access private
	 */
	function _getGETRequest($variableName, $defaultValue = '')
	{
		if (isset($_GET) && array_key_exists($variableName, $_GET)) {
			return $_GET[$variableName];
		} else {
			return $defaultValue;
		}
	}


	/**
	* NiceSize
	* Returns a datasize formatted into the most relevant units
	*
	* @param int Size In Bytes
	*
	* @return string The formatted filesize
	*/
	function NiceSize($SizeInBytes=0)
	{
		if ($SizeInBytes > 1024 * 1024 * 1024) {
			$suffix = 'GB';
			return sprintf("%01.2f %s", $SizeInBytes / (1024 * 1024 * 1024), $suffix);
		} elseif ($SizeInBytes > 1024 * 1024 ) {
			$suffix = 'MB';
			return sprintf("%01.2f %s", $SizeInBytes / (1024 * 1024), $suffix);
		} elseif ($SizeInBytes > 1024) {
			$suffix = 'KB';
			return sprintf("%01.2f %s", $SizeInBytes / 1024, $suffix);
		} elseif ($SizeInBytes < 1024) {
			$suffix = 'B';
			return sprintf("%d %s", $SizeInBytes, $suffix);
		}
	}

	/**
	* IsImageFile
	* To check if the file is a valid file name
	*
	* @param String $fileName The image filename
	*
	* @return Boolean Return true if the filename is the correct image file name. Otherwise, return false
	*/
	function IsImageFile($fileName)
	{
		$validImages = array('png', 'jpg', 'gif', 'jpeg', 'tiff', 'bmp', 'jpe');
		foreach($validImages as $image) {
			if(substr($fileName, (int)-(strlen($image)+1)) === '.' . $image){
				return true;
			}
		}
		return false;
	}

	/**
	 * IsIconFile
	 * To check if the icon file have a valid file name
	 *
	 * @param string $fileName The image filename
	 * @return boolean Return TRUE if the filename is valid, FALSE otherwise.
	 */
	function IsIconFile($fileName)
	{
		$validImages = array('ico');

		foreach($validImages as $image) {
			if(substr($fileName, (int)-(strlen($image)+1)) === '.' . $image){
				return true;
			}
		}

		return false;
	}

	/**
	* IsValidImageFile
	* To check if the file is a valid image file
	*
	* @param String $filePath The image path
	* @param String $fileType The image type
	*
	* @return Boolean Return true if the file is the correct image. Otherwise, return false
	*/
	function IsValidImageFile($filePath, $fileType)
	{
		// Check a list of known MIME types to establish the type of image we're uploading
		$imageTypes = array();
		$imageTypes[] = IMAGETYPE_GIF;
		$imageTypes[] = IMAGETYPE_JPEG;
		$imageTypes[] = IMAGETYPE_PNG;
		$imageTypes[] = IMAGETYPE_BMP;
		$imageTypes[] = IMAGETYPE_TIFF_II;

		$imageDimensions = getimagesize($filePath);
		if(!is_array($imageDimensions) || !in_array($imageDimensions[2], $imageTypes, true)) {
			@unlink($filePath);
			return false;
		}
		return true;
	}

	/**
	* IsValidIconFile
	* To check if the file is a valid icon file
	* @param string $filePath The icon path
	* @return boolean Return TRIE if valid icon file, FALSE otherwise
	*/
	function IsValidIconFile($filePath)
	{
		$valid = false;

		do {
			// ----- PHP >= 5.3 have icon support for getimagesize
				if (defined('IMAGETYPE_ICO')) {
					$imageDimensions = getimagesize($filePath);

					if (is_array($imageDimensions) && $imageDimensions[2] == IMAGETYPE_ICO) {
						$valid = true;
					}

					break;
				}
			// -----

			// ----- If icon is not yet supported, then we need to manually detect it
				$fh = @fopen($filePath, 'rb');
				if (!$fh) {
					break;
				}

				do {
					$temp = fread($fh, 2);
					if ($temp != "\x0\x0") {
						break;
					}

					$temp = fread($fh, 2);
					if ($temp != "\x1\x0") {
						break;
					}

					fseek($fh, 9);

					$temp = fread($fh, 1);
					if ($temp != "\x0") {
						break;
					}

					$valid = true;
				} while(false);

				@fclose($fh);
			// -----
		} while(false);


		if (!$valid) {
			@unlink($filePath);
			return false;
		}

		return true;
	}

	/**
	* ArrayToList
	* Convert the parsed in array to html list
	*
	* @param Array $arrList The target array to be converted
	* @param String $type The type of conversion.
	* @param Boolean $noHtmlChars Convert special characters to HTML entities
	*
	* @return Boolean Return true if the file is the correct image. Otherwise, return false
	*/
	function ArrayToList($arrList, $type='ul', $noHtmlChars=false){
		if($type !== 'ul' && $type !== 'ol' && $type!==false){
			$type = 'ul';
		}

		$output = '';

		if(!is_array($arrList)){
			if(strlen($arrList) < 1){
				return '';
			}else{
				$arrList = (array)$arrList;
			}
		}

		if($type !== false){
			$output = '<'.$type.'>';
		}

		foreach($arrList as $_key=>$value) {
			if($noHtmlChars){
				$output .= "<li>".$value."</li>";
			}else{
				$output .= "<li>".htmlspecialchars($value, ENT_QUOTES, SENDSTUDIO_CHARSET)."</li>";
			}
		}

		if($type !== false){
			$output .= '</'.$type.'>';
		}

		return $output;
	}

}
