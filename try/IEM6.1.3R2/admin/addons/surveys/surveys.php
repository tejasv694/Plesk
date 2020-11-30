<?php
/**
 * This file contains the 'surveys' addon wich logs pages recently viewed by each user and displays them on every page.
 *
 * @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
 *
 * @package Interspire_Addons
 * @subpackage Addons_systemlog
 */

/**
 * Make sure the base Interspire_Addons class is defined.
 */
if (!class_exists('Interspire_Addons', false)) {
	require_once(dirname(dirname(__FILE__)) . '/interspire_addons.php');
}

require_once (dirname(__FILE__) . '/language/language.php');

/**
 * This class handles recording and displaying the 'Last Viewed' pages
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 */
class Addons_surveys extends Interspire_Addons
{
	/**
	 * api
	 * Holds reference to surveys api
	 */
	static public $api;

	static public $model;

	/**
	 * getApi
	 * Loads the surveys API
	 *
	 * @see Addons_survey_api
	 *
	 * @return Object Returns the Addons_survey_api
	 */
	public function getApi()
	{
		if (!self::$api instanceof Addons_survey_api) {
			if (!class_exists('Addons_survey_api', false)) {
				require(dirname(__FILE__) . '/api/surveys.php');
			}
			self::$api = new Addons_survey_api();
		}

		return self::$api;
	}

	/**
	 * getSpecificApi
	 *
	 * @param $api string apiname
	 * Will call the specific api specified in the parametersthe files
	 * need to exist under the addon directory
	 * Will return false if no file is found.
	 *
	 * @return the API object
	 */

	public function getSpecificApi($api)
	{
		$className = 'Addons_survey_' . $api . '_api';
		if (!class_exists($className, false)) {
				if (file_exists(dirname(__FILE__) . '/api/' . $api . '.php')) {
					require_once(dirname(__FILE__) . '/api/' . $api . '.php');
				} else {
					return false;
				}
		}
		$instance = new $className;
		return $instance;
	}

	/**
     * RequiresLib
     *
     * required all the library files form the interspire..
     * @return boolean true if all the library loaded succesfully.
     *
	 */
	public function RequiresLib($library) {

		$libfiles = array();
		$library = ucwords($library);

		if (file_exists(IEM_PATH . "/lib/Interspire/$library.php")) {
			require_once(IEM_PATH . "/lib/Interspire/$library.php");
		}

		if (is_dir(IEM_PATH . "/lib/Interspire/$library")) {
				if ($handle = opendir(IEM_PATH . "/lib/Interspire/$library")) {
				    while (false !== ($file = readdir($handle))) {
				        if ($file != "." && $file != ".." && (strstr($file, '.php') !== false )) {
							$libfiles[] = $file;
				        }
				    }
				    closedir($handle);
				}
				// Sort the array in alphabetical order
				asort($libfiles);

				foreach ($libfiles as $key=>$file) {
					if (file_exists(IEM_PATH . "/lib/Interspire/$library/$file")) {
						require_once(IEM_PATH . "/lib/Interspire/$library/$file");
					}
				}
		}
		return true;
	}


	/**
	 * loadModel
	 * Loads the current model
	 *
	 * @return the Object of the model
	 */
	public function loadModel($model_type)
	{
		require_once(dirname(__FILE__) . '/model/base.php');
		$model_target = 'Addons_surveys_model_' . $model_type;
		if (!self::$model instanceof $model_target) {
			if (!class_exists($model_target, false)) {
				require_once(dirname(__FILE__) . '/model/' . $model_type . '.php');
			}
			self::$model = new $model_target;
		}
		return self::$model;
	}



	/**
	 * Default settings
	 *
	 * @var Int
	 */
	protected $default_settings = array();

	/**
	 * Install
	 * This is called when the addon is installed in the main application.
	 * In this case, it simply sets the default settings and then calls the parent install method to add itself to the database.
	 *
	 * @uses default_settings
	 * @uses Interspire_Addons::Install
	 * @uses Interspire_Addons_Exception
	 *
	 * @throws Throws an Interspire_Addons_Exception if something goes wrong with the install process.
	 * @return True Returns true if all goes ok with the install.
	 */
	public function Install()
	{
		$this->settings = $this->default_settings;
		$this->db->StartTransaction();

		$prefix = $this->db->TablePrefix;
		$query = "SHOW TABLES LIKE '{$prefix}surveys'";
		$result = $this->db->Query($query);
		$row = $this->db->Fetch($result);
		if (empty($row)) {
			require_once dirname(__FILE__) . '/schema.' . SENDSTUDIO_DATABASE_TYPE . '.php';
			foreach ($queries as $query) {
				$qry = str_replace('%%TABLEPREFIX%%', $this->db->TablePrefix, $query);
				$result = $this->db->Query($qry);
				if (!$result) {
					throw new Interspire_Addons_Exception("Unable to install addon, query failed: " . $qry);
				}
			}
		}

		$this->enabled = true;
		$this->configured = true;
		try {
			$status = parent::Install();
		} catch (Interspire_Addons_Exception $e) {
			throw new Exception("Unable to install addon $this->GetId();" . $e->getMessage());
		}

		$this->db->CommitTransaction();
		return true;
	}


	/**
	 * Uninstall
	 * This is called when the addon is uninstalled in the main application.
	 *
	 * @uses default_settings
	 * @uses Interspire_Addons::Install
	 * @uses Interspire_Addons_Exception
	 *
	 * @throws Throws an Interspire_Addons_Exception if something goes wrong with the install process.
	 * @return True Returns true if all goes ok with the install.
	 */
	public function Uninstall()
	{
		// $prefix = $this->db->TablePrefix;
		// $query[] = "DROP TABLE {$prefix}surveys";
		// $query[] = "DROP TABLE {$prefix}survey_questions";
		// $query[] = "DROP TABLE {$prefix}survey_templates";
		$this->db->StartTransaction();

		require_once dirname(__FILE__) . '/schema.' . SENDSTUDIO_DATABASE_TYPE . '.php';
		foreach ($tables as $tablename) {
			$query = 'DROP TABLE [|PREFIX|]' . $tablename . ' CASCADE';
			$result = $this->db->Query($query);
			if (!$result) {
				$this->db->RollbackTransaction();
				throw new Interspire_Addons_Exception("Unable to install addon, query failed: " . $query);
			}
		}

		try {
			$status = parent::Uninstall();
		} catch (Interspire_Addons_Exception $e) {
			$this->db->RollbackTransaction();
			throw new Exception("Unable to uninstall addon $this->GetId();" . $e->getMessage());
		}

		$this->db->CommitTransaction();
		return true;
	}

	/**
	 * LoadSelf
	 * Creates an instance of Addons_surveys
	 *
	 * @return Mixed Returns an object on success, false on failure
	 */
	public static function LoadSelf()
	{
		try {
			$me = new self;
			$me->Load();
		} catch (Exception $e) {
			return false;
		}

		if (!$me->enabled) {
			return false;
		}
		$me->template_system->Assign('AdminUrl',$me->admin_url);
		return $me;
	}

	/**
	 * GetEventListeners
	 * This returns an array of events that the addon listens to.
	 *
	 * This addon uses
	 * - 'IEM_SENDSTUDIOFUNCTIONS_GENERATETEXTMENULINKS' event to put itself into the 'tools' menu at the top
	 *
	 * @see Interspire_Addons::GetEventListeners
	 *
	 * @return Array Returns an array of events, what methods to call and which file to call the method from.
	 */
	public function GetEventListeners()
	{

		$my_file = '{%IEM_ADDONS_PATH%}/surveys/surveys.php';
		$listeners = array();

		$listeners[] =
			array (
				'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS',
				'trigger_details' =>  array (
					'Addons_surveys',
					'SetMenuItems'
				),
				'trigger_file' => $my_file
			);


		$listeners[] =
			array (
				'eventname' => 'IEM_USERAPI_GETPERMISSIONTYPES',
				'trigger_details' => array (
					'Interspire_Addons',
					'GetAddonPermissions',
				),
				'trigger_file' => $my_file
			);


		$listeners[] =
		array (
			'eventname' => 'IEM_HTMLEDITOR_TINYMCEPLUGIN',
			'trigger_details' =>  array (
				'Addons_surveys',
				'TinyMCEPluginHook'
			),
			'trigger_file' => $my_file
		);

		$listeners [] =
		array (
				'eventname' => 'IEM_EDITOR_SURVEY_BUTTON',
				'trigger_details' => array (
					'Addons_surveys',
					'CreateInsertSurveyButton'
		), 'trigger_file' => $my_file
		);

		$listeners[] =
		array (
			'eventname' => 'IEM_SURVEYS_VIEWCONTENT',
			'trigger_details' =>  array (
				'Addons_surveys',
				'ViewContentHook'
			),
			'trigger_file' => $my_file
		);

		$listeners[] =
		array (
			'eventname' => 'IEM_SURVEYS_REPLACETAG',
			'trigger_details' =>  array (
				'Addons_surveys',
				'ReplaceSurveyTag'
			),
			'trigger_file' => $my_file
		);

		return $listeners;
	}


	public static function CreateInsertSurveyButton(EventData_IEM_EDITOR_SURVEY_BUTTON $data) {

		$userAPI = GetUser();

        // Permission checking
        $access = true;
        // $access = $access || $userAPI->Admin();

        if (!$access) {
            $data->tagButtonHtml = $data->tagButtonText = '';
            return;
        }

        $data->surveyButtonText = '
            <li>
                    <a href="#" title="'.GetLang('SurveysInsert_Editor').'" onclick="javascript: InsertSurveyLink(\'TextContent\'); return false;">
                            <img src="images/mnu_surveys_button.gif" alt="icon" />'.GetLang('SurveysInsert_Editor').'</a>
            </li>
        ';

        $data->surveyButtonHtml = '
            <li>
                    <a href="#" title="'.GetLang('SurveysInsert_Editor').'" onclick="javascript: InsertSurveyLink(\'HtmlContent\'); return false;">
                            <img src="images/mnu_surveys_button.gif" alt="icon" />'.GetLang('SurveysInsert_Editor').'</a>
            </li>
        ';


	}

	/**
	 * _checkSurveyAccess
	 * @param $formId
	 * Check if the right survey bellongs to the right user group
	 */

	private function _checkSurveyAccess($surveyId)
	{
		$surveysApi = $this->getApi();
		$surveysApi->Load($surveyId);
		$user = GetUser();

		// if the user is system admin free pass for everything
		if (!empty($user->group->systemadmin)) {
			return;
		}

		if (!$surveysApi->checkValidSurveyAccess($user)) {
			$redirect = 'index.php?Page=Addons&Addon=surveys';
			FlashMessage(sprintf(GetLang('Addon_surveys_AccessError')), SS_FLASH_MSG_ERROR, $redirect);
		}
	}


	/**
	 * ViewContentHook,
	 * Actually replace the string / content
	 *  When the actual content get loaded..
	 *
	 */
	public static function ViewContentHook(IEM_SURVEYS_VIEWCONTENT $data)
	{


	}

	/**
	 * ReplaceSurveyTag
	 * Replacing the survey placeholder with the right link
	 *
	 */
	public static function ReplaceSurveyTag(EventData_IEM_SURVEYS_REPLACETAG $data)
	{
		$results = array();
		$surveys_tags =  preg_match_all("/(%%SURVEY.*?%%)/isx", $data->description, $results);

		if (!empty($surveys_tags) && $surveys_tags !== false) {
				$survey_links = $results[0];
				foreach ($survey_links as $survey_link) {
						$survey_pieces = explode('_', $survey_link);
						$id = str_replace('%%', '',  $survey_pieces[1]);
						$survey_url =  SENDSTUDIO_APPLICATION_URL . '/surveys.php?id=' . $id; // ID
						$data->description = str_replace($survey_link, $survey_url, $data->description);
				}
		}

	}

	/**
	 * Listens for the tinymce plugin event.
	 * dispatches the call to the tinymce plugin.
	 *
	 * @return Void
	 * @param  iwp_event_admin_content_tinymceplugin $data
	 */
	public static function TinyMCEPluginHook(EventData_IEM_HTMLEDITOR_TINYMCEPLUGIN $data)
	{
		// enabling the plugin hook call
		$data->enable='1';
	}

	/**
	 * TinyMCE Survey List is for TinyMCE addons.
	 * The list will render out the all the availble survey for that specific users.
	 * This is used in the survey button from the TinyMCE editor
	 *
	 * @return unknown_type
	 */

	public function Admin_Action_tinymceSurveylist()
	{
		$user = GetUser();
		$ownerid = 	$user->userid;

		$survey_api = $this->getApi();
		$surveys = $survey_api->GetSurveys($ownerid,'','all' );

		$this->GetTemplateSystem();
		$tpl = $this->template_system;

		$tpl->Assign('surveys', $surveys);
		$tpl->ParseTemplate('tinymce_surveylist');
	}

	/**
	 * SetMenuItems
	 * Adds itself to the navigation menu(s).
	 *
	 * If the user has access to "send email campaigns" in the email campaigns menu,
	 * it tries to put "View Split Tests" under that.
	 * If they don't have access to that, then "View Split Tests" goes at the bottom of the email campaigns menu.
	 *
	 * If the user has access to "email campaign stats" in the stats menu,
	 * it tries to put "Split Test Stats" under that.
	 * If they don't, then it goes at the bottom of the stats menu.
	 *
	 * @param EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS $data The current menu.
	 *
	 * @return Void The current menu is passed in by reference, no need to return anything.
	 *
	 * @uses EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS
	 */

	static function SetMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS $data)
	{
		$self = new self;

		$surveys_menu = array (
			'surveys_button' => array (
					array (
						'text' => GetLang('Menu_Surveys_View'),
						'link' => 'index.php?Page=Addons&amp;Addon=surveys',
						'show' => array (
							'CheckAccess' => 'HasAccess',
							'Permissions' => array('surveys'),
						),
						'description' => GetLang('Menu_Surveys_View_Description'),
						'image' => 'surveys_views.gif',
						'perm'	=> array('create', 'delete', 'edit')
					),
					array (
						'text' => GetLang('Menu_Surveys_Create'),
						'link' => 'index.php?Page=Addons&amp;Addon=surveys&amp;Action=create',
						'show' => array (
							'CheckAccess' => 'HasAccess',
							'Permissions' => array('surveys'),
						),
						'description' => GetLang('Menu_Surveys_Create_Description'),
						'image' => 'surveys_add.gif',
						'perm'	=> array('create')
					),
					array(
						'text' => GetLang('Menu_Surveys_Results'),
						'link' => 'index.php?Page=Addons&amp;Addon=surveys&amp;Action=resultdefault',
						'show' => array (
							'CheckAccess' => 'HasAccess',
							'Permissions' => array('surveys'),
						),
						'description' => GetLang('Menu_Surveys_Results_Description'),
						'image' => 'surveys_results.png',
						'perm'	=> array('resultdefault')
					),
					array (
						'text' => GetLang('Menu_Surveys_Responses_Browse'),
						'link' => 'index.php?Page=Addons&amp;Addon=surveys&amp;Action=viewResponsesDefault',
						'show' => array (
							'CheckAccess' => 'HasAccess',
							'Permissions' => array('surveys'),
						),
						'description' => GetLang('Menu_Surveys_Responses_Browse_Description'),
						'image' => 'surveys_responses_view.gif',
						'perm'	=> array('viewresponsesdefault', 'editresponse', 'deleteresponse')
					),
					array (
						'text' => GetLang('Menu_Surveys_Responses_Export'),
						'link' => 'index.php?Page=Addons&amp;Addon=surveys&amp;Action=exportDefault',
						'show' => array (
							'CheckAccess' => 'HasAccess',
							'Permissions' => array('surveys'),
						),
						'description' => GetLang('Menu_Surveys_Responses_Export_Description'),
						'image' => 'surveys_responses_export.gif',
						'perm'	=> array('exportdefault')
					),
				)
		);


		$menuItems = $data->data;

		// Menubar filter for the right permission
		$user = GetUser();
		if (!$user->isAdmin())  {
			$perm = $user->group->permissions;

			if (isset($perm['surveys'])) {
			$survey_permission = $perm['surveys'];
				foreach ($surveys_menu['surveys_button']  as $key=>$value) {
					$check = 0;
					foreach ($value['perm'] as $permission) {
						if (in_array($permission, $survey_permission)) {
							$check = 1;
							break;
						}
					}
					
					if ($check == 0) {
						unset($surveys_menu['surveys_button'][$key]);
					}	
				}
			}
		}
		
		


		/**
		 * Putting the survey menu to where it belongs..
		 */

		$new_menuItems = array();
		foreach ($menuItems as $key => $eachmenu) {
			$new_menuItems[$key] = $eachmenu;
			
			// put it after newsletter
			if ($key == 'newsletter_button') {
				$new_menuItems['surveys_button'] = $surveys_menu['surveys_button'];
			}
		}
		
	
		$data->data = $new_menuItems;
	}




	/**
	 * GetMenuItems
	 * Adds the survey menu item after customfields_button
	 */
	static public function GetMenuItems(InterspireEventData $data)
	{
		if (!$me = self::LoadSelf()) {
			return;
		}

		$menu = &$data->data;

		if (isset($menu['newsletter_button'])) {
			$index = array_search('customfields_button',array_keys($menu)) + 1;
			$menu_part1 = $menu;
			$menu_part2 = array_splice($menu_part1,$index);
			$menu_survey =
			array('survey_button' =>
				array(
					array(
						'text' => GetLang('Addon_surveys_ViewSurveys'),
						'link' => $me->admin_url,
						'show' => 1,
						'description' => GetLang('Addon_surveys_ViewSurveysDescription'),
						'image' => 'forms_view.gif'
					),
					array(
						'text' => GetLang('Addon_surveys_CreateSurveys'),
						'link' => $me->admin_url,
						'show' => 1,
						'description' => GetLang('Addon_surveys_CreateSurveysDescription'),
						'image' => ''
					),
					array(
						'text' => GetLang('Addon_surveys_SurveyTemplates'),
						'link' => $me->admin_url . "&Action=Templates",
						'show' => 1,
						'description' => GetLang('Addon_surveys_SurveyTemplatesDescription'),
						'image' => ''
					)
				)
			);
			$menu = array_merge($menu_part1,$menu_survey,$menu_part2);
		}
	}

	/**
	 * Register Addon Permission
	 *
	 *
	 */
	static function RegisterAddonPermissions()
	{
		$description = self::LoadDescription('surveys');
		$perms = array (
			'surveys' => array (
				'addon_description' => GetLang('Addon_Settings_Survey_Header'),
				'create' => array('name' => GetLang('Addon_surveys_Permission_Create')),
				'edit' => array('name' => GetLang('Addon_surveys_Permission_Edit')),
				'delete' => array('name' => GetLang('Addon_surveys_Permission_Delete')),
				'resultdefault' => array('name' => GetLang('Addon_surveys_results_Permission_View')),
				'viewresponsesdefault' => array('name' => GetLang('Addon_surveys_responses_Permission_View')),
				'editresponse' => array('name' => GetLang('Addon_surveys_responses_Permission_Edit')),
				'deleteresponse' => array('name' => GetLang('Addon_surveys_responses_Permission_Delete')),
				'exportdefault' => array('name' => GetLang('Addon_surveys_export_Permission'))
			)
		);
		self::RegisterAddonPermission($perms);
	}


	/**
	 * Admin_Action_ViewResponsesDefault
	 *
	 * This will show the default page for editing responses,
	 *
	 * @return void
	 */

	public function Admin_Action_ViewResponsesdefault()
	{
		$user = GetUser();
		$ownerid = 	$user->userid;
		$this->GetTemplateSystem();
		$tpl = $this->template_system;

		$surveys_api = self::getApi();
		$surveys = $surveys_api->GetSurveys($ownerid,'','all' );

		$tpl->Assign('FlashMessages',GetFlashMessages(),false);
		$tpl->Assign('forms', $surveys);
		$tpl->parseTemplate('view_default');
	}


	/**
	 * Admin_Action_ViewResponses
	 * This will show a page for editing a single response page..
	 *
	 * @return void
	 */

	public function Admin_Action_ViewResponses()
	{

		// a form id is required
		$surveyId = IEM::requestGetGET('surveyId');
		if (!$surveyId) {
			$surveyId = IEM::requestGetPOST('surveyId');
		}

		// check valid survey permission
		$this->_checkSurveyAccess($surveyId);

		// initiating template system
		$this->GetTemplateSystem();
		$tpl = $this->template_system;
		$tpl->Assign('FlashMessages',GetFlashMessages(),false);

		// if a form id was passed
		if ($surveyId) {
			// retrieve the response number
			$responseId     = IEM::requestGetGET('responseId');
			if (!$responseId) {
			    $responseId = IEM::requestGetPOST('responseId');
			}	
			$responseNumber = IEM::requestGetGET('responseNumber');
			if (!$responseNumber) {
			    $responseNumber = IEM::requestGetPOST('responseNumber');
			}
			$tpl->Assign('surveyId',$surveyId,false);
			//$tpl->Assign('responseId',$responseId,false);

			$survey_api = $this->getApi();
			$survey_api->Load($surveyId);

			$response_api = $this->getSpecificApi('responses');
			$surveyData = $survey_api->GetData();
			$responseCount  = $survey_api->getResponseCount();

			// Getting all responses Id from specific survey, Responses Number map the actual ID with the number
			$responseNumbers  = array();
			$responseNumbers = $survey_api->getResponsesId();

			if (!$responseId) {
					$responseId = $responseNumbers[1];
			}

			// if there are no responses the number is 0
			if ($responseCount == 0) {
				$responseNumber = 0;
			}

			if ($responseId) {
				$response_api->Load($responseId);
//				if (!$this->GetId()) {
//					$this->setErrorMessage('errorMessage_responseDoesNotExist')->redirect('view.responses');
//				}
				// Getting which number is currently on by counting
				$responseNumber   = $response_api->getResponseNumber();
			}

			$response_data = $response_api->getData();

			if (empty($response_data)) {
				$responseDefault = 'index.php?Page=Addons&Addon=surveys&Action=viewResponsesDefault';
				FlashMessage(GetLang('Addon_Surveys_viewResponseInvalidResponseId'), SS_FLASH_MSG_ERROR, $responseDefault);
			} else {

				$widgets          = $survey_api->getWidgets();
				$widgetErrors     = IEM::sessionGet('survey.addon.widgetErrors');

				// if there are responses at all then we will set the values
				if ($responseCount) {
					// contains the number that will be assigned to the widget
					$widgetNumber = 1;
					$widget_api = $this->getSpecificApi("widgets");

					foreach ($widgets as $widgetKey => &$widget) {
						$widget_api->populateFormData($widget);
						$searchValues   = array();

						// set widget properties for use in template
						$widget['number'] = $widgetNumber;
						$widget['fields'] = $widget_api->getFields();
						$widget['values'] = $widget_api->getResponseValues($responseId);

						if (isset($widget['values'][0]['file_value'])) {
							$widget['values'][0]['file_encode'] = base64_encode($widget['values'][0]['value']);
						}

						// randomize the fields
						if ($widget['is_random'] == 1) {
							shuffle($widget['fields']);
						}

						// retrieve the other field if one exists
						if ($other = $widget_api->getOtherField()) {
							$widget['fields'][] = $other;
						}

						// if the widget has errors, set them so we can display them
						if ($widgetErrors && isset($widgetErrors[$widget['id']]) && count($widgetErrors[$widget['id']]) > 0) {
							$widget['errors'] = $widgetErrors[$widget['id']];
						}

						// if there are values set for this widget
						if ($widget['values']) {
							// the values we will search in are the widget responses
							foreach ($widget['values'] as $value) {
								$searchValues[] = $value['value'];
							}

							// search through each widget field and mark selected fields
							foreach ($widget['fields'] as &$field) {
								// search for the values
								$searchKey = array_search($field['value'], $searchValues);

								// if a value was found, then mark it as selected
								if ($searchKey !== false) {
									$field['is_selected'] = 1;

									// unset it in the search values so if there are any leftover
									// then the leftover value is the value of the "other" field
									unset($searchValues[$searchKey]);
								// otherwise it's not selected
								} else {
									$field['is_selected'] = 0;
								}
							}

							// if there is a search value left, it means that it is an "other" field
							if (count($searchValues) == 1) {
								// foreach of the fields
								foreach ($widget['fields'] as &$field) {
									// if it is an "other" field
									if ($field['is_other']) {
										// its value is equal to the value that wasn't found in the search values
										// and it is selected
										$field['value']       = reset($searchValues);
										$field['is_selected'] = 1;
									}
								}
							}

							// flag the last one so we can add a bottom margin
							 //$widget->values[count($widget['values']) - 1]->isLast = true;
							$widget['values'][count($widget['values']) - 1]['isLast'] = true;
						}

						// mark the last one so we can give a bottom margin to it in the template
						$widget['fields'][count($widget['fields']) - 1]['isLast'] = true;
						$tpl->Assign('widget', $widget);

						// parse the template with widget/field variables for output in the edit template
						// we don't parse templates for the "section.break" widget type
						if ($widget['type'] != 'section.break') {
							$widget['template'] = $tpl->ParseTemplate('edit.widget.' . $widget['type'], true);
						}

						// we don't assign numbers to the section breaks
						if ($widget['type'] != 'section.break') {
							$widgetNumber++;
						}
					}
					// unset the widget errors
					IEM::sessionRemove('survey.addon.widgetErrors');
				}

				$tpl->Assign('widgets', $widgets);
				$tpl->Assign('responseId', $responseId);
				$tpl->Assign('responseNumbers', $responseNumbers);
			}
			$tpl->Assign('form', $surveyData);
			$tpl->Assign('responseCount', $responseCount);
			$tpl->Assign('responseNumber', $responseNumber);

		} else {
			// if there wasn't a valid for id, redirect to the view responses page
			$responseDefault = 'index.php?Page=Addons&Addon=surveys&Action=viewResponsesDefault';
			FlashMessage(GetLang('Addon_Surveys_viewResponseInvalidSurveyId'), SS_FLASH_MSG_ERROR, $responseDefault);
		}

		$action = IEM::requestGetGET('Action');
		if ($action == 'editresponse') {
			$tpl->parseTemplate('edit.response');
		} else {
			$tpl->ParseTemplate('view.response');
		}
	}

	/**
	 * Admin_Action_ResultDefault
	 *
	 * Generate the Result Summary Projection here
	 * void
	 */

	public function Admin_Action_ResultDefault()
	{
//		if (!$this->userCan('viewResponses')) {
//			$this->setErrorMessage('permission_sitemodules_cannotViewResponses')->redirect();
//		}
		$user = GetUser();
		$ownerid = $user->userid;
		$this->GetTemplateSystem();
		$tpl = $this->template_system;

		$surveys_api = self::getApi();
		$surveys = $surveys_api->GetSurveys($ownerid,'','all' );

		$tpl->Assign('surveys', $surveys);
		$tpl->parseTemplate('results_survey_default');
	}

	/**
	 * Admin_Action_Result_Other_List
	 *
	 * This is an ajax call getting list 10 times at a time
	 * @return void
	 */

	public function Admin_Action_Result_ResponsesList()
	{
		 $limit = 10;
		 $offset  = IEM::requestGetGET('start');
		 $widgetId = IEM::requestGetGET('widgetId');
		 $surveyId = IEM::requestGetGET('surveyId');
		 $total_others = IEM::requestGetGET('total_others');

		 if (empty($startindex)) {
		 	$startIndex = 0;
		 }

		 // initializing API...
		$widget_api = $this->getSpecificApi('widgets');
		$widget_api->Load($widgetId);

		// Getting all 10 text, can be other options or actual survey option.
		if (in_array($widget_api->type, array('radio', 'checkbox', 'select'))) {
			$other_answers = $widget_api->getResponseValuesByType($offset,true);
		} else {
			$other_answers = $widget_api->getResponseValuesByType($offset);
			if ($widget_api->type == "file") {
					foreach ($other_answers as &$answer) {
						$file_path =  'temp/surveys/' . $surveyId . '/' . $answer['id'] . '/';
						$answer['value'] = '<a href="' . $file_path . $answer['value'] .'">' . $answer['value'] . '</a>';
					}
				}
		}

		$this->GetTemplateSystem();

	 	$keys = array_keys($other_answers);
	 	$nextpage = max($keys);
	 	$prevpage = min($keys) - $limit - 1;

	 	if ($prevpage < 0 ) {
	 		$prevpage = false;
	 	}

	 	if ( ($nextpage - $total_others ) >= 0) {
	 		$nextpage = false;
	 	}

	 	$tpl = $this->template_system;
		$tpl->assign('question_id', $widgetId);
		$tpl->assign('nextpage', $nextpage);
		$tpl->assign('prevpage', $prevpage);
		$tpl->assign('surveyId', $surveyId);
		$tpl->assign('total_others', $total_others);

		$tpl->assign('other_answers', $other_answers);
		$tpl->ParseTemplate('results.viewanswers');
	}

	/**
	 * Admin_Action_Result
	 *
	 * Provides an overview of overall survey result
	 *
	 * @return void
	 */
	public function Admin_Action_Result()
	{
		$this->GetTemplateSystem();
		$tpl = $this->template_system;

		$surveyId = IEM::requestGetGET('surveyId');
		if (!$surveyId) {
			$surveyId = IEM::requestGetPOST('surveyId');
		}

		$this->_checkSurveyAccess($surveyId);

		$tpl->assign('surveyId', $surveyId);
		$survey_api = $this->getApi();
		$survey_api->Load($surveyId);

		$widget_api = $this->getSpecificApi('widgets');
		$response_api = $this->getSpecificApi('responses');

		// widget == question in this case..
		$all_widgets = $survey_api->getWidgets($surveyId);
		$all_responses = $survey_api->getResponses();
		$responseCount  = $survey_api->getResponseCount();
		$survey_results = array();

		// foreach questions...
		$question_number = 1;
		foreach ($all_widgets as $widget) {
			$tpl->assign('question_id', $widget['id']);
			$tpl->assign('question_number', $question_number);

			$tpl->assign('question', $widget['name']);
			$tpl->assign('question_description', $widget['description']);

			$widget_api->populateFormData($widget);
			$fields = $widget_api->getFields(true);

			// Case for multi Answers...
			if (in_array($widget_api->type, array('radio', 'checkbox', 'select'))) {
					$stats = array();
					$percentage = array();

					foreach ($fields as $field) {
						$field_label = $field['value'];
						// the empty field label are for others, this will be calculated separtely
						if (!empty($field_label)) {
							$stats[$field_label] = 0;
							// Based on each response calculate the number of occurence a particular response comesout
							foreach ($all_responses as $responses) {
								$response_api->Load($responses['id']);
								$responses = $response_api->getValues($widget_api->id);

									foreach ($responses as $r_key => $r_value) {
										if ($field_label == $responses[$r_key]['value']) {
											$stats[$field_label]++;
										}
									}
							}
						}

						if ($field['is_other'] == 1) {
								$other_label = $field['other_label_text'];
						}
					}

					// getResponseValuesByType all is get all values if there are any..
					if (!isset($other_label)) {
						$other_label = GetLang('Addon_Surveys_Results_others');
					}

					$other_answers = $widget_api->getResponseValuesByType(0,10,true);
					$total_others = $widget_api->getResponsesCount(true);
					$tpl->assign('total_others', $total_others);
					$total_response = array_sum($stats) + $total_others;

					// calculating percentage for answers
					foreach ($stats as $stats_key=>$stats_num) {
							$percentage[$stats_key] = number_format(($stats_num * 100) / $total_response, 2);
					}

					// calculating percentage for other answers
					if ($total_response > 0) {
							$percentage["others"] = number_format(($total_others * 100) / $total_response, 2);
					}

					$tpl->assign('widget', $widget);
					$tpl->assign('percentage', $percentage);
					$tpl->assign('stats', $stats);

					// now determining the maximum stats
					$stats['others'] = $total_others;
					$maxstats =  max($stats);

					$tpl->assign('maxstats', $maxstats);
					$tpl->assign('other_label', $other_label);

					$tpl->assign('other_answers', $other_answers);
					$tpl->assign('totalresponse', $total_others);

					$survey_results[] = $tpl->ParseTemplate('results.multianswers', true);
			// case for single answer such as text, textarea or file
			} elseif (in_array($widget_api->type, array('text', 'textarea', 'file'))) {

				$answers = $widget_api->getResponseValuesByType(0);

				$totalresponse = $widget_api->getResponsesCount();

				if ($totalresponse > 0) {
					$percentage = 100;
				} else {
					$percentage = 0;
				}

				if ($widget_api->type == "file") {
					foreach ($answers as &$answer) {
						$file_path =  'temp/surveys/' . $surveyId . '/' . $answer['id'] . '/';
						$answer['value'] = '<a href="' . $file_path . $answer['value'] .'">' . $answer['value'] . '</a>';
					}
				}

				$tpl->assign('percentage', $percentage);
				$tpl->assign('totalresponse', $totalresponse);
				// Getting all responses value..
				$tpl->assign('other_answers', $answers );
				$survey_results[] = $tpl->ParseTemplate('results.singleanswer', true);
			}

			$question_number++;
		}

		//$title = sprintf(GetLang(''), $survey_name);
		$tpl->Assign('survey_name', $survey_api->name);
		$tpl->Assign('survey_id', $survey_api->id);
		$tpl->Assign('responseCount', $responseCount);
		$tpl->Assign('survey_results', $survey_results);
		$tpl->parseTemplate('results_survey');
	}


	public function Admin_Action_EditResponse()
	{
		$this->Admin_Action_ViewResponses();
	}


	/**
	 * Admin_Action_DeleteResponse
	 * Default page for deleting particular response
	 * @param void
	 * @return void
	 */
	public function Admin_Action_DeleteResponse()
	{
		// can they even view responses
//		if (!$this->userCan('viewResponses')) {
//			$this->setErrorMessage('permission_sitemodules_cannotDeleteResponses')->redirect();
//		}

		$surveyId         = IEM::requestGetGET('surveyId');
		$responseId       = IEM::requestGetGET('responseId');
		$responseNumber   = IEM::requestGetGET('responseNumber');

		$survey_api = $this->getApi();
		$survey_api->Load($surveyId);

		$response_api = $this->getSpecificApi('responses');
		$response_api->Load($responseId);

		// if they can't view this response, then they can't delete it
//		if (!$this->userCan('viewResponses', $formId)) {
//			$this->setErrorMessage('permission_sitemodules_cannotDeleteResponse')->redirect();
//		}

		$redirect = "";
		if ($response_api->Delete($surveyId)) {
			if ($survey_api->getResponseByNumber($responseNumber)) {
				// go to the next response
				$redirect = 'index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId=' . $surveyId . '&responseNumber=' . $responseNumber;
			} elseif ($survey_api->getResponseByNumber($responseNumber - 1)) {
				// go to the previous response
				$redirect = 'index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId=' . $surveyId . '&responseNumber=' . ($responseNumber - 1);
			} else {
				// go back to the view responses page
				$redirect = 'index.php?Page=Addons&Addon=surveys&Action=viewResponsesDefault';
			}

			FlashMessage(sprintf(GetLang('Addon_Surveys_deleteResponseMessageSuccess'), $responseId, $survey_api->name), SS_FLASH_MSG_SUCCESS, $redirect);
		} else {
			// go back to the current response
			$redirect = 'index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId=' . $surveyId . '&responseNumber=' . $responseNumber;
			FlashMessage(sprintf(GetLang('Addon_Surveys_deleteResponseMessageError'), $responseId, $survey_api->name), SS_FLASH_MSG_ERROR, $redirect);
		}
		exit;
	}

	/**
	 * Admin_Action_Delete
	 * Deletes a survey
	 *
	 * @param Int $surveyid The surveyid to delete
	 *
	 * @return Void Returns nothing
	 */
	public function Admin_Action_Delete()
	{
		$surveyid = array();
		if (is_array($_POST['survey_select'])) {
			$surveyid = $_POST['survey_select'];
		}

		$totaldelete = count($surveyid);

		$me = self::LoadSelf();
		if ($surveyid === array()) {
			$surveyid = 0;
			if (isset($_REQUEST['id'])) {
				$surveyid = $_REQUEST['id'];
			}
		}

		if (empty($surveyid)) {
			FlashMessage(GetLang('Addon_surveys_SurveyDeleted_Error'), SS_FLASH_MSG_ERROR, $this->admin_url);
		}

		$api = self::getApi();

		if (is_array($surveyid)) {
			foreach ($surveyid as $id) {
				$api->Delete((int)$id);
			}

			if ($totaldelete == 1) {
				$deletedmessage = GetLang('Addon_surveys_SurveyDeleted_Multi');
			} else {
				$deletedmessage = sprintf(GetLang('Addon_surveys_SurveyDeleted_Multi_Real'), $totaldelete);
			}

			FlashMessage($deletedmessage, SS_FLASH_MSG_SUCCESS, $this->admin_url);

		} else {
			$api->Delete((int)$surveyid);
			FlashMessage(GetLang('Addon_surveys_SurveyDeleted'), SS_FLASH_MSG_SUCCESS, $this->admin_url);
		}
		//$me->Admin_Action_Default();
	}

	/**
	 * Admin_Action_DownloadAttach
	 *
	 * Enter description here...
	 * @return unknown_type
	 */
	public function Admin_Action_DownloadAttach()
	{
		//to download a specific survey response..
		$surveyId = IEM::requestGetGET('formId');
		$responseId = IEM::requestGetGET('responseId');
		$file_name = base64_decode(IEM::requestGetGET('value'));

		$ajaxcall = IEM::requestGetGET('ajax');
		if (!$ajaxcall) {
			return;
		}
		// Checking authentication...

		// say if user is authenticated fine.. then let them download..
		$response_api = $this->getSpecificApi('responses');

		// check user permission
		$user =  GetUser();
		$response_api->Load($responseId);


		$upBaseDir = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'surveys';
		$upSurveyDir = $upBaseDir . DIRECTORY_SEPARATOR . $surveyId;
		$upDir     = $upSurveyDir . DIRECTORY_SEPARATOR . $responseId;
		$filepath = $upDir . DIRECTORY_SEPARATOR . $file_name;

		//Added this to grab any files that were stored with an encoded name
		if (!file_exists($filepath)) {
			$filename = $response_api->getRealFileValue($file_name);
			$filepath = $upDir . DIRECTORY_SEPARATOR . $filename;
		}


		if (!file_exists($filepath)) {
			die("file not exist");
		}
		
		$file_name = str_replace(" ", "_", $file_name);

		header("Content-Disposition: attachment; filename=" . $file_name);
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Description: File Transfer");
		header("Content-Length: " . filesize($filepath));
		// flush();  this doesn't really matter.


		$fp = fopen($filepath, "r");
		while (!feof($fp))
		{
		    echo fread($fp, 3840);
		    flush(); // this is essential for large downloads
		}
		fclose($fp);


	}

	/**
	 * Admin Action Export
	 *
	 * Enter description here...
	 * @return unknown_type
	 */
	public function Admin_Action_Export()
	{
		// can they view responses at all
//		if (!$this->userCan('viewResponses')) {
//			$this->setErrorMessage('permission_sitemodules_cannotExportResponses')->redirect();
//		}
		$surveyId =	IEM::requestGetPost('surveyId');
		$this->_checkSurveyAccess($surveyId);


		if (empty($surveyId)) {
			$surveyId = IEM::requestGetGET('surveyId');
		}

		// can they view this response, cause if they can't, then they can't export it cause that would be viewing it
//		if (!$this->userCan('viewResponses', $formId)) {
//			$this->setErrorMessage('permission_sitemodules_cannotExportResponse')->redirect('export.responses');
//		}

		if ($surveyId) {
			$data         = array();

			// get the survey API
			$survey_api = $this->getApi();
			$survey_api->Load($surveyId);
			$surveyData = $survey_api->GetData();
			$widgets      = $survey_api->getWidgets($surveyId);
			$survy_api->id = $surveyId;
			$responses    = $survey_api->getResponses();

			// create a header array
			$data[0] = array();

			// create the column names
			foreach ($widgets as $widget) {
				if ($widget['type'] !== 'section.break') {
					$data[0][] = $widget['name'];
				}
			}

			foreach ($responses as $responseKey => $response) {
				// since there already is a row for the headers
				$responseKey = $responseKey + 1;

				// create a response without loading the data
				$response_api = $this->getSpecificApi('responses');
				$response_api->LoadData($response);

				// $responseInstance = new iwp_module_form_model_response($response, false);
				foreach ($widgets as $widgetKey => $widget) {
					if ($widget['type'] !== 'section.break') {
						// create a row for this response
						$data[$responseKey][$widgetKey] = array();
						$values = $response_api->getValues($widget['id']);
						if ($values) {
							$valuesImplodeArray = array();
							foreach ($values as $value) {
								if ($widget['type'] == 'file') {
									//index.php?Page=Addons&Addon=surveys&Action=downloadAttach&formId=6&responseId=8&value=YW5uaXZlcnNheV9kYXRlLmpwZw==&ajax=1
									$valuesImplodeArray[] = SENDSTUDIO_APPLICATION_URL . '/survey_attachment.php?f=' . $surveyId . '_' . $response_api->GetId() .'_' . base64_encode($value['value']);
								} else {
									$valuesImplodeArray[] = $value['value'];
								}
							}
							$data[$responseKey][$widgetKey] = implode(', ', $valuesImplodeArray);
						} else {
							$data[$responseKey][$widgetKey] = '';
						}
					}
				}
			}

			// send the csv headers
			header('Content-Type: text/csv');
			header('Content-Disposition: attachment;filename=' . 'surveys_' . $surveyId . '_export_' . date('ymd') . '.csv');

			$this->RequiresLib('csv');
			echo new Interspire_Csv_Exporter($data);

			exit;
		}
	}

	/**
	 * Admin_Action_ExportDefault
	 * Showing all the right option for the export..
	 *
	 * @return unknown_type
	 */

	public function Admin_Action_ExportDefault()
	{
//		if (!$this->userCan('viewResponses')) {
//			$this->setErrorMessage('permission_sitemodules_cannotViewResponses')->redirect();
//		}
		$user = GetUser();
		$ownerid = 	$user->userid;
		$this->GetTemplateSystem();
		$tpl = $this->template_system;

		$surveys_api = self::getApi();
		$surveys = $surveys_api->GetSurveys($ownerid,'','all' );

		$tpl->Assign('forms', $surveys);
		$tpl->parseTemplate('export_default');
	}



	/**
	 * Admin_Action_PreConfig
	 *
	 * is use to preconfigured any request before hitting any of of the Action..
	 * Perhaps this can be used to setup any prerequeisite like seting error messages or warning
	 * and other related used that can be used accross action..
	 *
	 *
	 * @return void
	 */

	public function Admin_Action_PreConfig()
	{
		$messageText = IEM::sessionGet('MessageText');
		$messageType = IEM::sessionGet('MessageType');

		if ($messageText) {
			$message['type'] = $messageType;
			$message['message'] = $messageText;
			$messageArr[] = $message;

			IEM::sessionSet('FlashMessages', $messageArr);

			// removing the session for next usage
			IEM::sessionRemove('MessageText');
			IEM::sessionRemove('MessageType');
		}
	}

	/**
	 * Admin_Action_Default
	 * Displays the list of surveys, with pagination, and all the CRUD options.
	 *
	 * @return Void Returns nothing
	 */
	public function Admin_Action_Default()
	{
		$this->Admin_Action_PreConfig();

		$me = self::LoadSelf();
		$surveyid = 0;
		if (isset($_REQUEST['id'])) {
			$surveyid = (int)$_REQUEST['id'];
		}
		$api = self::getApi();
		$user = GetUser();

		$me->template_system->Assign('Add_Button',$me->template_system->ParseTemplate('add_survey_button',true),false);
		$me->template_system->Assign('Delete_Button', $me->template_system->ParseTemplate('delete_survey_button', true), false);

		$me->template_system->Assign('FlashMessages',GetFlashMessages(),false);

		$numsurveys = $api->GetSurveys($user->userid,0,0,array(),array(),true);
		if ($numsurveys == 0) {
			$me->template_system->ParseTemplate('manage_surveys_empty');
			return;
		}

		$sort_details = array(
			'SortBy' => 'name',
			'Direction' => 'asc'
		);

		if (isset($_GET['SortBy']) && in_array(strtolower(IEM::requestGetGET('SortBy')), Addons_survey_api::$validSorts)) {
			$sort_details['SortBy'] = strtolower(IEM::requestGetGET('SortBy'));
		}

		if (in_array(strtolower(IEM::requestGetGET('Direction')),array('up','down'))) {
			$direction = strtolower(IEM::requestGetGET('Direction'));
			if ($direction == 'up') {
				$sort_details['Direction'] = 'asc';
			} else {
				$sort_details['Direction'] = 'desc';
			}
		}

		$perpage = $me->GetPerPage();
		if (empty($perpage)) {
			$perpage = (int)IEM::requestGetGET('PerPageDisplay');
		}

		$me->SetPerPage($perpage);

		$page = (int)IEM::requestGetGET('DisplayPage');
		if ($page < 1) { $page = 1; }

		$paging = $me->SetupPaging($me->admin_url,$numsurveys);


		$me->template_system->Assign('Paging',$paging,false);
		$search_info = array();

		$surveys = $api->GetSurveys($user->userid,$page,$perpage,$search_info,$sort_details,false);

		$survey_rows = '';
		foreach ($surveys as $survey) {
			$me->template_system->Assign('name',$survey['name']);
			$me->template_system->Assign('surveyid',$survey['id']);
			$me->template_system->Assign('created',AdjustTime($survey['created'],false,GetLang('DateFormat'),true));

			if (isset($survey['updated'])) {
				$me->template_system->Assign('updated',AdjustTime($survey['updated'],false,GetLang('DateFormat'),true));
			} else {
				$me->template_system->Assign('updated',GetLang('Addon_Surveys_Default_NeverUpdated'),false);
			}

			// Number of response to be zero first..
			// now lets geat each number of response..

			$me->template_system->Assign('numresponses', $survey['responseCount']);

			if (empty($survey['responseCount'])) {
				$view_results = GetLang('Addon_Surveys_Default_Table_ViewResults');
				$export_responses = GetLang('Addon_Surveys_Default_Table_ExportResponses');

			} else {
				$view_results = '<a href="' . $me->admin_url . '&Action=result&surveyId=' . $survey['id'] . '">' . GetLang('Addon_Surveys_Default_Table_ViewResults') . '</a>';
				$export_responses = '<a href="' . $me->admin_url . '&Action=Export&ajax=1&surveyId=' . $survey['id'] . '">' . GetLang('Addon_Surveys_Default_Table_ExportResponses') . '</a>';
			}

			$me->template_system->Assign('view_results',$view_results,false);
			$me->template_system->Assign('export_responses',$export_responses ,false);

			$editlink = '<a href="' . $me->admin_url . '&Action=Edit&formId=' . $survey['id'] . '">' . GetLang('Edit') . '</a>';
			$me->template_system->Assign('edit_link',$editlink,false);

			$deletelink = '<a class=\'deleteButton\' href="' . $me->admin_url . '&Action=Delete&id=' . $survey['id'] . '">' . GetLang('Delete') . '</a>';
			$me->template_system->Assign('delete_link',$deletelink,false);

			$previewlink = '<a target="_blank" href="' . SENDSTUDIO_APPLICATION_URL . '/surveys.php?id=' . $survey['id'] . '">' . GetLang('Preview') . '</a>';
			$me->template_system->Assign('preview_link', $previewlink,false);

			$survey_rows .= $me->template_system->ParseTemplate('manage_surveys_row',true);
		}

		$me->template_system->Assign('Items',$survey_rows,false);
		$me->template_system->ParseTemplate('manage_surveys');
	}

	/**
	 * Admin_Action_Create
	 * Prints the create survey form
	 *
	 * @return Void Returns nothing
	 */


	public function Admin_Action_Create()
	{
		$this->GetTemplateSystem();
		$tpl = $this->template_system;

		$surveyid = 0;
		if (isset($_REQUEST['id'])) {
			$surveyid = (int)$_REQUEST['id'];
		}

		$api = self::getApi();
		$QuestionTypes = '';

		$tpl->Assign('TitleLabel',Getlang('Addon_surveys_Question_Title'));
		$tpl->Assign('Heading',GetLang('Addon_surveys_Survey_Heading_Create'));
		$tpl->Assign('Intro',GetLang('Addon_surveys_Survey_Intro_Create'));

		$form["surveys_header_text"] = GetLang('Addon_surveys_Settings_Header');
		$form["show_message"] = GetLang('Addon_surveys_Settings_ShowMessage');
		$form["show_uri"] = GetLang('Addon_surveys_Settings_ShowUri');
		$form["email"] = GetLang('Addon_surveys_Settings_Email');
		$form["error_message"] = GetLang('Addon_surveys_Settings_ErrorMessage');
		$form["submit_button_text"] = GetLang('Addon_surveys_Settings_Submit');

		$tpl->Assign('form', $form);
		$tpl->ParseTemplate('survey_form');
	}

	/**
	 * Admin_Action_Build
	 * This is for backend editor to call the template system and render the right widget for the editor.
	 *
	 * @return void
	 */
	public function Admin_Action_Build()
	{
		$widgetname = IEM::requestGetGET('widget');
		if (isset($widgetname)) {
			if (preg_match_all('/(.*)-(.*)/',$widgetname, $widgetparts)) {
				$widget_func = $widgetparts[1][0] . ucfirst($widgetparts[2][0]) . "Action";
			} else {
				$widget_func = $widgetname . "Action";
			}
		} else {
			return;
		}

		$filename = dirname(__FILE__) . '/surveys_build.php';
		require_once ($filename);

		$surveys_build = new Addons_surveys_build($this->template_system);
		// check if method exist..
		if (!method_exists($surveys_build, $widget_func)) {
			throw new Interspire_Addons_Exception("Function " . $widget_func . " doesnt exist in " . $survey_build, Interspire_Addons_Exception::MethodDoesntExist);
		}

		// Calling the survey build function depending on whats being called.
		try {
			return $surveys_build->$widget_func();
		} catch (Interspire_Addons_Exception $e) {
			throw new Interspire_Addons_Exception($e->getMessage());
		}
	}

	/**
	 * Admin_Action_Submit
	 *
	 */
	public function Admin_Action_Submit()
	{
		$this->_handleSubmitAction();
	}

	/**
	 * Redirects back to the http referer.s
	 *
	 * @return Void
	 */
	private function redirectToReferer()
	{
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		exit;
	}


	/**
	 * Admin_Action_Save
	 * Saves and exit or Save and continue..
	 *
	 * @return Void Returns nothing
	 */
	// TODO: BUILDING THE SAVE FUNCTION NOW!!
	public function Admin_Action_Save()
	{
		$form     = IEM::requestGetPost('form');
		$widgets  = IEM::requestGetPost('widget');


		if ($form) {
				// Check for permission here to prevent URL attack..
			if ($form['id']) {
				$formId = $form['id'];
				// check the right permission
				$this->_checkSurveyAccess($formId);
			} else {
				// raise invalid survey here..

			}
		}

		if (isset($form['surveys_header_logo_filename']))
		{
			$form['surveys_header_logo'] = $form['surveys_header_logo_filename'];
			unset($form['surveys_header_logo_filename']);
		}

		$this->RequiresLib('validator');

		// This is used to make sure count will return Zero if the array is empty.
		if (empty($widgets)) {
		//	unset($widgets);
			$widgets = array();
		}


			// validation rules; since they are arrays, their names/values can't be pulled from $_POST
			// otherwise we could go: new Interspire_Validator($_POST);
			$validator = new Interspire_Validator(array(
					'form[email]'              	=> $form['email'],
				//	'form[surveys_header_text]'	=> $form['surveys_header_text'],
					'form[surveys_header_logo]'	=> @$form['surveys_header_logo'],
					'form[show_message]'       	=> $form['show_message'],
					'form[show_uri]'           	=> $form['show_uri'],
					'form[error_message]'      	=> $form['error_message'],
					'form[submit_button_text]' 	=> $form['submit_button_text'],
					'formMustHaveWidgets'      	=> count($widgets)
				));

			/*
			if ($form['surveys_header'] == "headertext") {
					$formHeaderText = new Interspire_Validator_Required;
					$formHeaderText->errorMessage = GetLang('Addon_Surveys_ErrorMessage_formEmail_headertext');
					$validator->addValidators('form[surveys_header_text]', $formHeaderText);
			} else
			*/
			if ($form['surveys_header'] == "headerlogo") {
				// if its the headerlogo..
				if (isset($_FILES['form'])) {
					$file_data = $_FILES['form'];
					$filepath = $file_data['tmp_name']['surveys_header_logo'];
					$filesize = $file_data['size']['surveys_header_logo'];
					$filename = $file_data['name']['surveys_header_logo'];
					$filetype = $file_data['type']['surveys_header_logo'];

					// Validation for checking the images file
					$formHeaderLogoImageSize = new Interspire_Validator_ImageSize($filesize);
					$formHeaderLogoImageSize->errorMessage    = GetLang('Addon_Surveys_ErrorMessage_formEmail_headerlogosize');
					$validator->addValidators("form[surveys_header_logo]", $formHeaderLogoImageSize);

					// Validation for checking the images type
					$formHeaderLogoImageFile = new Interspire_Validator_ImageFile($filepath, $filesize, $filetype, $filename);
					$formHeaderLogoImageFile->errorMessage    = GetLang('Addon_Surveys_ErrorMessage_formEmail_headerlogotype');
				} else {
					$formHeaderLogoImageFile = new Interspire_Validator_ImageFile('','','','');
					$formHeaderLogoImageFile->errorMessage    = GetLang('Addon_Surveys_ErrorMessage_formEmail_headerlogotype');
				}

				// If the form already have a logo, and the File Post is empty ignore..
				if ($form['surveys_header_logo'] != "" && empty($file_data)) {

				} else {
					$validator->addValidators('form[surveys_header_logo]', $formHeaderLogoImageFile);
				}

				if ($filename != "") {
						$form['surveys_header_logo'] = $filename;
				}
			}

			// email feedback validation
			if (isset($form['email_feedback'])) {
				$formEmailEmail                  = new Interspire_Validator_Email;
				$formEmailEmail->errorMessage    = GetLang('Addon_Surveys_ErrorMessage_formEmail_email');

				$validator->addValidators('form[email]', $formEmailEmail);
			} else {
				$form['email_feedback'] = 0;
			}


			// after submit validation
			if (isset($form['after_submit'])) {
				if ($form['after_submit'] == 'show_message') {
					$formShowMessageRequired               = new Interspire_Validator_Required;
					$formShowMessageRequired->errorMessage = GetLang('Addon_Surveys_ErrorMessage_formShowMessage_required');
					$validator->addValidators('form[show_message]', $formShowMessageRequired);
				} else {
					// uri validation
					$formShowUriRequired               = new Interspire_Validator_Required;
					$formShowUriRequired->errorMessage = GetLang('Addon_Surveys_ErrorMessage_formShowUri_required');
					$validator->addValidators('form[show_uri]', $formShowUriRequired);

					if ($form['show_uri']) {
						$formUri = new Interspire_Validator_Uri;
						$formUri->errorMessage = GetLang('Addon_Surveys_ErrorMessage_formShowUri_uri');
						$validator->addValidators('form[show_uri]', $formUri);
					}
				}
			}

			// error message and submit button text validators
			$formErrorMessageRequired                   = new Interspire_Validator_Required;
			$formErrorMessageRequired->errorMessage     = GetLang('Addon_Surveys_ErrorMessage_formErrorMessage_required');

			$formSubmitButtonTextRequired               = new Interspire_Validator_Required;
			$formSubmitButtonTextRequired->errorMessage = GetLang('Addon_Surveys_ErrorMessage_formSubmitButtonText_required');

			// error message and submit button text validation
			$validator->addValidators(array(
					'form[error_message]'      => $formErrorMessageRequired,
					'form[submit_button_text]' => $formSubmitButtonTextRequired
				));

			// the form must have widgets
			$formMustHaveWidgets               = new Interspire_Validator_NumberRange(1);
			$formMustHaveWidgets->errorMessage = GetLang('Addon_Surveys_ErrorMessage_mustHaveWidgets_numberRange');

			// the widget number validator
			$validator->addValidators('formMustHaveWidgets', $formMustHaveWidgets);

			// validate the fields
			$validator->validate();

			// if any error messages are found then do this..
			if ($errors = $validator->getErrors()) {
				echo json_encode(array(
						'success'  => false,
						'message'  => GetLang('Addon_Surveys_saveFormMessageError'),
						'messages' => $validator->getErrorMessages(),
						'errors'   => $errors
					));
				exit;
			}

		$surveysApi = $this->getApi();

		// Loading the Form Data first using populateFormData then Create or Save it
		$_columns = array('name','userid','description','created','surveys_header','surveys_header_text','surveys_header_logo','email','email_feedback','after_submit','show_message','show_uri','error_message','submit_button_text');
		$surveysApi->populateFormData($_columns, $form);

		// If there is no ID Create if exist then update
		if (empty($formId)) {
			$create_new = true;
			$surveysApi->__set('created', date('Y-m-d h:i:s'));
			$formId = $surveysApi->Create();
		} else {
			$surveysApi->setId($formId);
			$surveysApi->Update();
		}

		//  if we are uploding a file need to take
		// care of and copy the actual file
		if ($form['surveys_header'] == "headerlogo") {
				if (!empty($file_data)) {
					$filepath = $file_data['tmp_name']['surveys_header_logo'];
					$filesize = $file_data['size']['surveys_header_logo'];
					$filename = $file_data['name']['surveys_header_logo'];
					$filetype = $file_data['type']['surveys_header_logo'];

					$surveys_dir = TEMP_DIRECTORY . '/surveys';
					if (!is_dir($surveys_dir)) {
						mkdir($surveys_dir);
						chmod($surveys_dir, 0777);
					}

					$surveys_dir .= '/' . $formId;
					if (!is_dir($surveys_dir)) {
						mkdir($surveys_dir);
						chmod($surveys_dir, 0777);
					}

					if (move_uploaded_file($filepath, "$surveys_dir/$filename") !== false) {

					}
				}
		}

		if ($formId) {
			// Now saving all the widgets here..
			$widgetapi = $this->getSpecificApi('widgets');
			if ($widgets) {
					// a list of widget/field ids, we will use this to delete widgets that
					// were removed from the front end after we have done the saving
					$widgetIds          = array();
					$fieldIds           = array();
					$widgetDisplayOrder = 0;
					// save the widgets
					foreach ($widgets as $widget) {
						// default required value if not provided
						if (!isset($widget['is_required'])) {
							$widget['is_required'] = 0;
						}
						if (!isset($widget['is_random'])) {
							$widget['is_random'] = 0;
						}

						// if all file types is selected, then clear the allowed file types
						if (isset($widget['all_file_types']) && $widget['all_file_types'] == 1) {
							$widget['allowed_file_types'] = '';
						}

						// set the display order for the widget
						$widget['display_order'] = $widgetDisplayOrder;

						// save the widget
						$widgetapi->populateFormData($widget);
						$widgetid = $widgetapi->saveWidget($formId);

						// if the widget was saved, look for fields to save
						if ($widgetid !== false) {
							// add the saved widget id to the list of widgets not to remove
							$widgetIds[] = $widgetid;

							// save the widget fields
							if (isset($widget['field']) && is_array($widget['field'])) {
								$fieldDisplayOrder = 0;

							// add / update fields
								foreach ($widget['field'] as $widget_key => $field) {
									// if the field isn't selected, we must mark it as not selected
									// since the value doesn't exist if not checked and won't override
									// a checked value
									if (!isset($field['is_selected'])) {
										$field['is_selected'] = 0;
									}

									// set the display order of the widget field
									$field['display_order'] = $fieldDisplayOrder;

									// save the field

									if (!is_int($widget_key)) {
										unset($field['id']);
									}

									$fieldId = $widgetapi->saveFields($widgetid, $field);
									// if saved, add the saved field id to the list of fields not to remove
									if ($fieldId) {
										$fieldIds[] = $fieldId;
									}
									// field display order, doesn't affect random ordering
									$fieldDisplayOrder++;
								}

								// Remove all fields that weren't included in the current post array that
								// are associated to the current widget.
								$widgetapi->deleteFieldsNotIn($fieldIds);
							}
						}
						// widget display order
						$widgetDisplayOrder++;
					}
					// remove any widgets that weren't saved that are associated to the current form
					$surveysApi->id = $formId;
					$surveysApi->deleteWidgetsNotIn($widgetIds);
			}
		} else {
			$surveysApi->deleteAllWidgets($formId);
		}

		// Redirect
		if (isset($_REQUEST['exit'])) {
			$redirect = 'index.php?Page=Addons&Addon=surveys';
			IEM::sessionSet('MessageText', GetLang('Addon_Surveys_saveSurveysMessageSuccess'));
			IEM::sessionSet('MessageType', SS_FLASH_MSG_SUCCESS);
		} else {
			$redirect = 'index.php?Page=Addons&Addon=surveys&Action=Edit&formId=' . $formId;
			IEM::sessionSet('MessageText', GetLang('Addon_Surveys_saveSurveysMessageSuccess'));
			IEM::sessionSet('MessageType', SS_FLASH_MSG_SUCCESS);
		}

		// success message
		echo json_encode(array(
				'success'  => true,
				'message'  => GetLang('Addon_Surveys_saveSurveysMessageSuccess'),
				'redirect' => $redirect
			));
		exit;
	}

	//TODO: TO finish editing the actual survey..

	/***
	 * Admin_Action_Edit
	 *
	 * Backend Edit page for the survey designer
	 *
	 */
	public function Admin_Action_Edit()
	{
		$this->Admin_Action_PreConfig();

		$me = self::LoadSelf();
		$formId = (int) IEM::requestGetGET('formId');

		// if a form id was given, load the corresponding form
		$surveysApi = $this->getApi();

		$this->_checkSurveyAccess($formId);
		$formId = $surveysApi->getId();

		if (!empty($formId)) {

			$widgetTemplates = array();

			$widgetapi = $this->getSpecificApi('widgets');

			$surveys_widgets = $surveysApi->getWidgets($formId);

			foreach ($surveys_widgets as $widget) {
				$widgetapi->SetId($widget['id']);

				$me->template_system->Assign('randomId', 'widget_' . md5(microtime()));
				$me->template_system->Assign('widget', $widget);
				$me->template_system->Assign('widgetFields', $widgetapi->getFields());

				$me->template_system->Assign('widgetFieldOther', $widgetapi->getOtherField());
				$widgetTemplates[] = $me->template_system->ParseTemplate('widget.' . $widget['type'], true);
			}

			$me->template_system->Assign('widgetTemplates', $widgetTemplates);

		} else {

			// now die here..
			FlashMessage(GetLang('Addon_Surveys_InvalidSurveyID'), SS_FLASH_MSG_ERROR);

			// default checkbox state
			$surveysApi->email_feedback = 1;

			// default action after submitting a form
			$surveysApi->after_submit = 'show_message';

			// the default message to be shown
			$surveysApi->show_message = GetLang('Addon_surveys_Settings_ShowMessage');

			// the default uri to be shown
			$surveysApi->show_uri = GetLang('Addon_surveys_Settings_ShowUri');

			// the default error message to be shown
			$surveysApi->error_message = GetLang('Addon_surveys_Settings_ErrorMessage');

			// the default error message to be shown
			$surveysApi->submit_button_text = GetLang('Addon_surveys_Settings_Submit');
		}

		// assign default form email
		if (!$surveysApi->Get('email')) {
			$surveysApi->email = $survey->emailaddress;
		}

		// assign survey and widget data
		$form_data = $surveysApi->GetData();

		foreach ($form_data as &$form_val) {
			$form_val = htmlspecialchars($form_val);
		}

		$me->template_system->Assign('Heading',GetLang('Addon_surveys_Heading_Edit'));
		$me->template_system->Assign('Intro',GetLang('Addon_surveys_Edit_Intro'));
		$me->template_system->Assign('FlashMessages',GetFlashMessages(),false);
		$me->template_system->Assign('form', $form_data);
		$me->template_system->ParseTemplate('survey_form');
	}

	/**
	 * saveResponseAction
	 * Save the actual save response action
	 *
	 * @return void
	 *
	 */

	public function Admin_Action_SaveResponse()
	{
		$surveyId       = (int) IEM::requestGetPOST('formId');
		// check permission here
		$this->_checkSurveyAccess($surveyId);

		$responseId     = IEM::requestGetPOST('responseId');
		$responseNumber = IEM::requestGetPOST('responseNumber');
		$postWidgets    = IEM::requestGetPOST('widget');
		$errors         = 0;

		if ($postWidgets || $_FILES) {
			// If there are files, take the values and place them in the $postWidgets array so they can
			// get validated and entered into the response values in the same manner. Uploads will be
			// handled separately.
			if (isset($_FILES['widget'])) {
				foreach ($_FILES['widget']['name'] as $widgetId => $widget) {
					foreach ($widget as $fields) {
						foreach ($fields as $fieldId => $field) {
							if ($field['value']) {
								$postWidgets[$widgetId]['field'][$fieldId]['value'] = 'file_' . $field['value'];
							}
						}
					}
				}
			}

			$survey_api = $this->getApi();
			$survey_api->Load($surveyId);
			$widgets      = $survey_api->getWidgets();
			$widgetErrors = array();

			foreach ($widgets as $widget) {
				if (!isset($widgetErrors[$widget['id']])) {
					$widgetErrors[$widget['id']] = array();
				}

				// validate required fields
				if ($widget['is_required']) {
					// the widget is assumed blank until one of it's fields is found not blank
					$isBlank = true;
					$isOther = false;

					// make sure the required widget was even posted
					if (isset($postWidgets[$widget['id']])) {
						foreach ($postWidgets[$widget['id']]['field'] as $field) {
							if (isset($field['value'])) {
								// get the value of an "other" field if it is one, otherwise just grab
								// the normal value
								if ($field['value'] == '__other__') {
									$isOther = true;
									$value   = $field['other'];
								} else {
									$value = $field['value'];
								}

								// make sure the value isn't blank
								if (!$this->_validateIsBlank($value)) {
									$isBlank = false;
								}
							}
						}
					}

					// if the widget is blank, flag an error
					if ($isBlank) {
						if ($isOther) {
							$error = GetLang('Addon_Surveys_ErrorRequiredOther');
						} else {
							$error = GetLang('Addon_Surveys_ErrorRequired');
						}

						$widgetErrors[$widget['id']][] = $error;

						$errors++;
					}
				}

				// validate file types
				if (isset($postWidgets[$widget['id']]) && $widget['allowed_file_types']) {
					$typeArr     = preg_split('/\s*,\s*/', strtolower($widget['allowed_file_types']));
					$invalidType = false;

					// foreach of the passed fields (most likely 1) check and see if they are valid file types
					foreach ($postWidgets[$widget->id]['field'] as $field) {
						$parts = explode('.', $field['value']);
						$ext   = strtolower(end($parts));

						// only if the field has a value we will test its file type
						if (trim($field['value']) != '' && !in_array($ext, $typeArr)) {
							$invalidType = true;
						}
					}

					// if the a file is not a valid file type, then the whole widget fails validation
					if ($invalidType) {
						$lastFileType   = '<em>.' . array_pop($typeArr) . '</em>';
						$firstFileTypes = '<em>.' . implode('</em>, <em>.', $typeArr) . '</em>';

						$widgetErrors[$widget->id][] = sprintf(GetLang('errorInvalidFileType'), $firstFileTypes, $lastFileType);

						$errors++;
					}
				}
			}

			// if there were errors, redirect back and display the errors
			if ($errors) {
				echo '<pre style="border: 1px solid red";><b style="color:RED;">YUDI_DEBUG:'. __FILE__ .' ON LINE: ' . __LINE__ . '</b><br />';
				print_r($widgetErrors);
				echo '</pre>';
				die;
				// set the widget errors so we can retrieve them for the user
				IEM::sessionSet('survey.addon.widgetErrors', $widgetErrors);
				IEM::sessionSet('MessageText', GetLang('Addon_Surveys_saveResponseMessageError'));
				IEM::sessionSet('MessageType', MSG_ERROR);
			} else {
				// isntantiate a new response object

				$response_api = $this->getSpecificApi('responses');
				$response_api->Load($responseId);

				// delete the values in this response, since they will be added back in
				$response_api->deleteValues();

				// if the response was saved, then associate values to the response
				if ($response_api->Save()) {
					$responseValue = $this->getSpecificApi('responsesvalue');
					// foreach of the posted widgets, check to see if it belongs in this form and save it if it does
					foreach ($postWidgets as $postWidgetId => $postWidget) {

						// iterate through each field and enter it in the feedback
						foreach ($postWidget['field'] as $field) {
							if (!isset($field['value'])) {
								continue;
							}
							// foreign key for the response id
							$responseValue->surveys_response_id =  $responseId;

							// set the widget id foreign key; widgets can have multiple field values and
							// should be treated as such
							$responseValue->surveys_widgets_id = $postWidgetId;

							// set the value of the feedback; this should be a single value since widgets
							// can have multiple feed back values
							if ($field['value'] == '__other__') {
								$responseValue->value = $field['other'];
								$responseValue->is_othervalue = 1;
							} else {
								$responseValue->file_value = "";
								if (substr($field['value'] , 0, 5) == "file_") {
									$value = str_replace("file_", "", $field['value']);
									$responseValue->file_value = md5($value);
								}
								$responseValue->value = $field['value'];
							}
							// save it
							$responseValue->Save();
						}

					}

					// perform file uploading
					if (isset($_FILES['widget']['name'])) {
						$files = $_FILES['widget']['name'];

						foreach ($files as $widgetId => $widget) {
							foreach ($widget as $widgetKey => $fields) {
								foreach ($fields as $fieldId => $field) {
									// gather file information
									$name    = $_FILES['widget']['name'][$widgetId]['field'][$fieldId]['value'];
									$type    = $_FILES['widget']['type'][$widgetId]['field'][$fieldId]['value'];
									$tmpName = $_FILES['widget']['tmp_name'][$widgetId]['field'][$fieldId]['value'];
									$error   = $_FILES['widget']['error'][$widgetId]['field'][$fieldId]['value'];
									$size    = $_FILES['widget']['size'][$widgetId]['field'][$fieldId]['value'];

									// if the upload was successful to the temporary folder, move it
									if ($error == UPLOAD_ERR_OK) {
										$curDir    = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'surveys';
										$upBaseDir = $curDir  . DIRECTORY_SEPARATOR . $surveyId;
										$upDir     = $upBaseDir . DIRECTORY_SEPARATOR . $response_api->GetId();


										// if the main survey folder is not yet created then create it
										if (!is_dir($curDir)) {
											mkdir($curDir, 0755);
										}

										// if the base upload directory doesn't exist create it
										if (!is_dir($upBaseDir)) {
											mkdir($upBaseDir, 0755);
										}

										// if the upload directory doesn't exist create it
										if (!is_dir($upDir)) {
											mkdir($upDir, 0755);
										}

										// upload the file
										move_uploaded_file($tmpName, $upDir . DIRECTORY_SEPARATOR . $name);
									}
								}
							}
						}
					}
					IEM::sessionSet('MessageText', GetLang('Addon_Surveys_saveResponseMessageSuccess'));
					IEM::sessionSet('MessageType', SS_FLASH_MSG_SUCCESS);
				}
			}
		}

		// if view is set, then go to the view page for this response
		if (!$errors && IEM::requestGetPOST('view')) {
			if (IEM::requestGetPost('viewNext')) {
				$responseId = IEM::requestGetPost('viewNext');
			}

			header('Location: index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId=' . $surveyId . '&responseId=' . $responseId);
			exit;
		}

		// redirect back to the edit page
		header('Location: index.php?Page=Addons&Addon=surveys&Action=editresponse&surveyId=' . $surveyId. '&responseId=' . $responseId);
		exit;
	}

	/**
	 * Admin_Action_Templates
	 * Prints the survey templates page
	 *
	 * @return Void Returns nothing
	 */
	public function Admin_Action_Templates()
	{
		$me = self::LoadSelf();
		$step = 1;
		if (isset($_GET['SubAction'])) {
			$method = $_GET['SubAction'];
		} else {
			$method = 'Default';
		}

		$method = "Admin_Action_Templates_{$method}";

		require dirname(__FILE__) . '/survey_templates.php';

		$templates = new Addons_survey_templates;
		$templates->template_system->Assign('AdminUrl',$me->admin_url);

		if (method_exists($templates, $method)) {
			return $templates->$method();
		}

		/**
		 * If the method doesn't exist, take the user back to the default action.
		 */
		FlashMessage(GetLang('Addon_surveys_Templates_InvalidSurveyTemplate'), SS_FLASH_MSG_ERROR, $this->admin_url);
	}

	/**
	 * _refreshSurvey
	 * Prints JavaScript needed to refresh a survey after content has been modified
	 *
	 * @param Int $surveyid The surveyid to refresh
	 *
	 * @return Void Returns nothing, prints the javascript functions
	 */
	public function _refreshSurvey($surveyid)
	{
		$questions_html = $this->Admin_Action_Edit($surveyid,true);
		$questions_html = str_replace(array('"',"\n"),array('\\"','\n'),$questions_html);

		echo "$('#questions_container').html(\"$questions_html\");";
		echo "surveyRefresh();surveyUpdateControls();";
	}

	/**
	 * _loadCountries
	 * Loads list of countries from the data file
	 *
	 * @return Array Returns the list of countries in an array
	 */
	public function _loadCountries()
	{
		$countries = array();
		$country_file = dirname(__FILE__) . "/data/countries.php";
		if (is_readable($country_file)) {
			require($country_file);
		}
		return $countries;
	}

	/**
	 * Configure
	 * This method is called when the addon needs to be configured.
	 * It uses the templates/settings.tpl file to show it's current settings and display the settings form.
	 *
	 * @uses settings
	 * @uses template_system
	 * @uses InterspireTemplate::Assign
	 * @uses InterspireTemplate::ParseTemplate
	 *
	 * @return String Returns the settings form with the current settings pre-filled.
	 */
	function Configure()
	{
		return $this->template_system->ParseTemplate('settings', true);
	}



	/**
	 * SaveSettings
	 * This is called when the settings form is submitted.
	 * It checks if any values were posted.
	 * It then checks against the settings it should find (from default_settings) to make sure you're not trying to sneak any extra settings in there
	 *
	 * If no form was posted or if you post invalid options, this will return false (which then displays an error message).
	 *
	 * @see Configure
	 * @uses default_settings
	 * @uses db
	 *
	 * @return Boolean Returns false if an invalid settings form is posted or if
	 */
	function SaveSettings()
	{
		return true;
	}

	/**
	 * _validateIsBlank
	 * @param $str the string
	 * @param $trim whether or not to trim the str
	 *
	 * Handles the form submission from all feedback forms on the front end.
	 *
	 * @return boolean true when its blank, false otherwise
	 **/
	private function _validateIsBlank($str,$trim=true)
	{
		if($trim) { $str = trim($str); }
		if(strlen($str) === 0 || $str === null || strtolower($str) == "null" || $str === "0" || $str === 0){
			return true;
		}else{
			return false;
		}
	}

}
