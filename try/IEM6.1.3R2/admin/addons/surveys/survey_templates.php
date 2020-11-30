<?php

/**
* This file handles processing of survey templates
*
 * @package Interspire_Addons
 * @subpackage Addons_surveys
*/
class Addons_survey_templates extends Addons_surveys
{
	/**
	 * addon_id
	 * We need to masquerade as the 'splittest' addon
	 * So we get the right template path, template url,
	 * admin url and so on from the parent Interspire_Addons system.
	 *
	 * @var String
	 *
	 * @usedby Interspire_Addons::__construct
	 */
	protected $addon_id = 'surveys';
	
	/**
	* __construct
	* Only calls parent constructor
	*/
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	* Admin_Action_Templates_Default
	* Prints the list of templates
	*
	* @return Void Returns nothing
	*/
	public function Admin_Action_Templates_Default()
	{
		$api = self::getApi();
		$user = GetUser();
		
		/**
		* Add the add button and print flash messages
		*/
		$this->template_system->Assign('Add_Button',$this->template_system->ParseTemplate('add_survey_template_button',true),false);
		$this->template_system->Assign('Message',GetFlashMessages(),false);
		
		/**
		* If there are no templates print an empty message
		*/
		$numsurveys = $api->GetTemplates($user->userid,0,0,array(),array(),true);
		if ($numsurveys == 0) {
			$this->template_system->ParseTemplate('manage_templates_empty');
			return;
		}
		
		/**
		* The default sort details
		*/
		$sort_details = array(
			'SortBy' => 'name',
			'Direction' => 'asc'
		);
		
		/**
		* If valid sorting details are given overwrite the defaults
		*/
		if (isset($_GET['SortBy']) && in_array(strtolower($_GET['SortBy']),$api->validSorts)) {
			$sort_details['SortBy'] = strtolower($_GET['SortBy']);
		}
		if (in_array(strtolower($_GET['Direction']),array('up','down'))) {
			$direction = strtolower($_GET['Direction']);
			if ($direction == 'up') {
				$sort_details['Direction'] = 'asc';
			} else {
				$sort_details['Direction'] = 'desc';
			}
		}

		$perpage = $this->GetPerPage();
		if (isset($_GET['PerPageDisplay'])) {
			$perpage = (int)$_GET['PerPageDisplay'];
			$this->SetPerPage($perpage);
		}
		
		$page = (int)$_GET['DisplayPage'];
		if ($page < 1) { $page = 1; }
		
		$paging = $this->SetupPaging($this->admin_url,$numsurveys);
		$this->template_system->Assign('Paging',$paging,false);
		
		$search_info = array();
		
		$surveys = $api->GetTemplates($user->userid,$page,$perpage,$search_info,$sort_details,false);
		
		$survey_rows = '';
		foreach ($surveys as $survey) {
			$this->template_system->Assign('name',$survey['name']);
			$this->template_system->Assign('surveyid',$survey['surveyid']);
			$this->template_system->Assign('created',AdjustTime($survey['created'],false,GetLang('DateFormat'),true));
			
			/**
			* add the edit link
			*/
			$editlink = '<a href="' . $this->admin_url . '&Action=Edit&id=' . $survey['surveyid'] . '">' . GetLang('Edit') . '</a>';
			$this->template_system->Assign('edit_link',$editlink,false);
			
			/**
			* add the delete link
			*/
			$deletelink = '<a href="' . $this->admin_url . '&Action=Delete&id=' . $survey['surveyid'] . '">' . GetLang('Delete') . '</a>';
			$this->template_system->Assign('delete_link',$deletelink,false);
			
			$survey_rows .= $this->template_system->ParseTemplate('manage_surveys_row',true);
		}
		$this->template_system->Assign('Items',$survey_rows,false);

		$this->template_system->ParseTemplate('templates_manage');
	}
	
	/**
	* Admin_Action_Templates_Create
	* Prints the create template form
	* 
	* @return Void Returns nothing
	*/
	public function Admin_Action_Templates_Create()
	{
		$surveyid = 0;
		if (isset($_REQUEST['id'])) {
			$surveyid = (int)$_REQUEST['id'];
		}
		$api = self::getApi();
		$user = GetUser();
		
		$this->template_system->Assign('Heading',GetLang('Survey_Template_Heading_Create'));
		$this->template_system->Assign('Intro',GetLang('Survey_Template_Intro_Create'));
		
		$this->template_system->ParseTemplate('template_form');
	}
}
?>