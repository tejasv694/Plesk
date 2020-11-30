<?php
/**
 * This file contains the 'dynamiccontenttags' addon which allows the user to filter contents according to specific blocks rules.
 *
 * @package Interspire_Addons
 * @subpackage Addons_dynamiccontenttags
 */

/**
 * Make sure the base Interspire_Addons class is defined.
 */
if (! class_exists ( 'Interspire_Addons', false )) {
    require_once (dirname ( dirname ( __FILE__ ) ) . '/interspire_addons.php');
}

/**
 * Make sure the APIs are included.
 */
require_once (dirname ( __FILE__ ) . '/api/dynamiccontentblock.php');
require_once (dirname ( __FILE__ ) . '/api/dynamiccontenttag.php');
require_once (dirname(__FILE__) . '/language/language.php');

/**
 * This class handles the Dynamic Content Tags operations.
 *
 * @uses Interspire_Addons
 * @uses Interspire_Addons_Exception
 */
class Addons_dynamiccontenttags extends Interspire_Addons {
    var $_cacheCustomFieldsUsedByLists = array ();
    private $tags = array ();
    private $sortDetails = array();
    private $perPage = 0;
    private $start = 0;

    /**
     * Install
     * This addon has to create some database tables to work.
     * It includes the schema files (based on the database type) and creates the bits it needs.
     * Once that's done, it calls the parent Install method to do its work.
     *
     * @uses enabled
     * @uses configured
     * @uses Interspire_Addons::Install
     * @uses Interspire_Addons_Exception
     *
     * @throws Throws an Interspire_Addons_Exception if something in the install process fails.
     * @return True Returns true if everything works ok.
     */
    public function Install() {
        $tables = $sequences = array ();

        $this->db->StartTransaction ();

        require dirname ( __FILE__ ) . '/schema.' . SENDSTUDIO_DATABASE_TYPE . '.php';
        foreach ( $queries as $query ) {
            $qry = str_replace ( '%%TABLEPREFIX%%', $this->db->TablePrefix, $query );
            $result = $this->db->Query ( $qry );
            if (! $result) {
                $this->db->RollbackTransaction ();
                throw new Interspire_Addons_Exception ( "There was a problem running query " . $qry . ": " . $this->db->GetErrorMsg (), Interspire_Addons_Exception::DatabaseError );
            }
        }

        $this->enabled = true;
        $this->configured = true;
        try {
            $status = parent::Install ();
        } catch ( Interspire_Addons_Exception $e ) {
            $this->db->RollbackTransaction ();
            throw new Exception ( "Unable to install addon {$this->GetId()} " . $e->getMessage () );
        }

        $this->db->CommitTransaction ();

        return true;
    }

    /**
     * UnInstall
     * Drop tables the addon created.
     * It includes the schema files (based on the database type) and drops the bits it created.
     * Once that's done, it calls the parent UnInstall method to do its work.
     *
     * @uses Interspire_Addons::UnInstall
     * @uses Interspire_Addons_Exception
     *
     * @return Returns true if the addon was uninstalled successfully.
     * @throws Throws an Interspire_Addons_Exception::DatabaseError if one of the tables it created couldn't be removed. If the parent::UnInstall method throws an exception, this will
     * just re-throw that error.
     */
    public function UnInstall() {
        $tables = $sequences = array ();

        $this->db->StartTransaction ();

        try {
            $this->Disable ();
        } catch ( Interspire_Addons_Exception $e ) {
            $this->db->RollbackTransaction ();
            throw new Interspire_Addons_Exception ( $e->getMessage (), $e->getCode () );
        }

        require dirname ( __FILE__ ) . '/schema.' . SENDSTUDIO_DATABASE_TYPE . '.php';
        foreach ( $tables as $tablename ) {
            $query = 'DROP TABLE [|PREFIX|]' . $tablename . ' CASCADE';
            $result = $this->db->Query ( $query );
            if (! $result) {
                $this->db->RollbackTransaction ();
                throw new Interspire_Addons_Exception ( "There was a problem running query " . $query . ": " . $this->db->GetErrorMsg (), Interspire_Addons_Exception::DatabaseError );
            }
        }

        foreach ( $sequences as $sequencename ) {
            $query = 'DROP SEQUENCE [|PREFIX|]' . $sequencename;
            $result = $this->db->Query ( $query );
            if (! $result) {
                $this->db->RollbackTransaction ();
                throw new Interspire_Addons_Exception ( "There was a problem running query " . $query . ": " . $this->db->GetErrorMsg (), Interspire_Addons_Exception::DatabaseError );
            }
        }

        try {
            $status = parent::UnInstall ();
        } catch ( Interspire_Addons_Exception $e ) {
            $this->db->RollbackTransaction ();
            throw new Interspire_Addons_Exception ( $e->getMessage (), $e->getCode () );
        }

        $this->db->CommitTransaction ();

        return true;
    }

    /**
     * SetMenuItems
     * Adds itself to the navigation menu(s).
     *
     * @param EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS $data The current menu.
     *
     * @return Void The current menu is passed in by reference, no need to return anything.
     *
     * @uses EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS
     */
    static function SetMenuItems(EventData_IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS $data) {
        $self = new self ( );
        $news_dynamic_content_tag_menu = array ('text' => GetLang ( 'Addon_dynamiccontenttags_Menu_DynamicContentTags' ), 'link' => $self->admin_url, 'image' => '../addons/dynamiccontenttags/images/dct_add.gif', 'show' => array ('CheckAccess' => 'HasAccess', 'Permissions' => array ('dynamiccontenttags' ) ), 'description' => GetLang ( 'Addon_dynamiccontenttags_Menu_DynamicContentTags_Description' ) );

        $menuItems = $data->data;

        $slice_pos = false;

        foreach ( $menuItems ['newsletter_button'] as $pos => $newsletter_menu_item ) {
            if ($newsletter_menu_item ['link'] == 'index.php?Page=ImageManager') {
                $slice_pos = $pos;
                break;
            }
        }

        if ($slice_pos !== false) {
            $newsmenu_slice = array_slice ( $menuItems ['newsletter_button'], $slice_pos, 1 );
            $newsmenu_slice [] = $news_dynamic_content_tag_menu;
            array_splice ( $menuItems ['newsletter_button'], $slice_pos, 1, $newsmenu_slice );
        } else {
        /**
         * They don't have access to send campaigns? Just put it at the end of the campaign menu.
         */
            $menuItems ['newsletter_button'] [] = $news_dynamic_content_tag_menu;
        }
        $data->data = $menuItems;
    }

    /**
     * GetEventListeners
     * The addon uses quite a few events to place itself in the app and allow it to work.
     *
     * @return Array Returns an array containing the listeners, the files to include, the function/methods to run etc.
     */
    function GetEventListeners() {
        $my_file = '{%IEM_ADDONS_PATH%}/dynamiccontenttags/dynamiccontenttags.php';
        $listeners = array ();

        $listeners [] =
            array (
            'eventname' => 'IEM_SENDSTUDIOFUNCTIONS_GENERATEMENULINKS',
            'trigger_details' => array (
            'Addons_dynamiccontenttags',
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

        $listeners [] =
            array (
            'eventname' => 'IEM_DCT_HTMLEDITOR_TINYMCEPLUGIN',
            'trigger_details' => array (
            'Addons_dynamiccontenttags',
            'DctTinyMCEPluginHook'
            ),
            'trigger_file' => $my_file
        );

        $listeners [] =
            array (
            'eventname' => 'IEM_EDITOR_TAG_BUTTON',
            'trigger_details' => array (
            'Addons_dynamiccontenttags',
            'CreateInsertTagButton'
            ),
            'trigger_file' => $my_file
        );

        $listeners[] =
            array (
            'eventname' => 'IEM_ADDON_DYNAMICCONTENTTAGS_GETALLTAGS',
            'trigger_details' =>  array (
            'Addons_dynamiccontenttags',
            'getAllTags'
            ),
            'trigger_file' => $my_file
        );

        $listeners[] =
            array (
            'eventname' => 'IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT',
            'trigger_details' =>  array (
            'Addons_dynamiccontenttags',
            'replaceTagContent'
            ),
            'trigger_file' => $my_file
        );

        return $listeners;
    }

    /**
     * RegisterAddonPermissions
     * Registers permissions for this addon to create.
     * This allows an admin user to finely control which parts of split tests a user can access.
     *
     * Creates the following permissions:
     * - create
     * - edit
     * - delete
     * - send
     * - stats
     *
     * @uses RegisterAddonPermission
     */
    static function RegisterAddonPermissions() {
        $description = self::LoadDescription('dynamiccontenttags');
        $perms = array (
            'dynamiccontenttags' => array (
            'addon_description' => GetLang('Addon_dynamiccontenttags_Settings_Header'),
            'general' => array('name' => GetLang('Addon_dynamiccontenttags_Permission_General')),
            ),
        );
        self::RegisterAddonPermission($perms);
    }

    /**
     * getAllTags
     * Get all available tags
     *
     * @param EventData_IEM_ADDON_DYNAMICCONTENTTAGS_GETALLTAGS $data This will be set and used by the caller function
     * @return void This will return the variable via $data instead of return via function
     */
    public static function getAllTags(EventData_IEM_ADDON_DYNAMICCONTENTTAGS_GETALLTAGS $data) {
        $num = rand();
        $tagObject = new Addons_dynamiccontenttags();
        $tagObject->loadTags();
        foreach($tagObject->tags as $tagEntry) {
            $tagEntry->loadBlocks();
            $tagEntry->loadLists();
        }
        if ($tagObject->getTagsSize()) {
            $data->allTags = $tagObject->tags;
        }
    }

    /**
     * replaceTagContent
     * This is used to replace the place holder for the email to the correct content
     *
     * @param EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT $data This will be set and used by the caller function
     * @return void This will return the variable via $data instead of return via function
     */
    public static function replaceTagContent(EventData_IEM_ADDON_DYNAMICCONTENTTAGS_REPLACETAGCONTENT $data) {
        require_once (SENDSTUDIO_API_DIRECTORY . '/subscribers.php');
        $subsrciberApi = new Subscribers_API();
        $subscriberInfo = $data->info;
		//if there's no data then there's no point in finishing
		if(empty($subscriberInfo) || empty($subscriberInfo[0])){
			return;
		}
        foreach ($subscriberInfo as $subscriberInfoEntry) {
    		if(empty($subscriberInfoEntry) || empty($subscriberInfoEntry['emailaddress']) || empty($subscriberInfoEntry['subscriberid'])){
    			continue;
    		}
            $tagObject = new Addons_dynamiccontenttags();
            $subscriberList = $subsrciberApi->GetAllListsForEmailAddress($subscriberInfoEntry['emailaddress'], $data->lists);
            if (is_array($subscriberList)) {
                foreach($subscriberList as $listKey => $listVal) {
                    $subscriberList[$listKey] = $listVal['listid'];
                }
            } else {
                $subscriberList = array($subscriberList);
            }

            // preload the array key value and customfield id
            $preloadCustomFieldLoc = array();
            if (is_array($subscriberInfoEntry['CustomFields'])) {
                foreach($subscriberInfoEntry['CustomFields'] as $customFieldKey => $customFieldVal) {
                    $preloadCustomFieldLoc[$customFieldVal['fieldid']] = $customFieldKey;
                }
            }

            $tagObject->loadTagsByList($subscriberList);
            if ($tagObject->getTagObjectsSize()) {
                $tagsTobeReplaced = array();
                $tagsContentTobeReplaced = array();
                $permanentRulesMatches = array(
                    'email'=>'emailaddress',
                    'format'=>'format',
                    'confirmation'=>'confirmed',
                    'subscribe'=>'subscribedate',
                );
				$generatedContent = '';  //stores the content blocks that will be used in this tag, initializing for scope
                foreach($tagObject->tags as $tagEntry) {
                    $tagEntry->loadBlocks();
                    $blocks = $tagEntry->getBlocks();
                    $defaultBlock = null;
                    $anyRulesPassed = false;
                    foreach($blocks as $blockEntry) {
                        $rulesPassed = true;
                        $decodedRules = $blockEntry->getDecodedRules();
                        foreach ($decodedRules->Rules[0]->rules as $ruleEntry) {
                            $continue = false;
                            $tempRuleValues = trim(strtolower($ruleEntry->rules->ruleValues));
                            $tempActualValues = (isset ($permanentRulesMatches[$ruleEntry->rules->ruleName]) && isset ($subscriberInfoEntry[$permanentRulesMatches[$ruleEntry->rules->ruleName]]))?trim(strtolower($subscriberInfoEntry[$permanentRulesMatches[$ruleEntry->rules->ruleName]])):'';
                            switch ($ruleEntry->rules->ruleName) {
                                case 'email':
                                case 'format':
                                case 'confirmation':
                                    $continue = true;
                                    break;
                                case 'status':
                                    $tempActualValues = array();
                                    $tempIndex = '';
                                    switch ($tempRuleValues) {
                                        case 'a':
                                            $tempIndex = 'notboth';
                                            break;
                                        case 'b':
                                            $tempIndex = 'bounced';
                                            break;
                                        case 'u':
                                            $tempIndex = 'unsubscribed';
                                            break;
                                    }

                                    switch ($ruleEntry->rules->ruleOperator) {
                                        case 'equalto':
                                            if (isset($subscriberInfoEntry[$tempIndex]) && $subscriberInfoEntry[$tempIndex] == 0) {
                                                $rulesPassed = false;
                                            } elseif (!(isset($subscriberInfoEntry[$tempIndex])) && !($subscriberInfoEntry['bounced'] == 0 && $subscriberInfoEntry['unsubscribed'] == 0) ) {
                                                $rulesPassed = false;
                                            }
                                            break;
                                        case 'notequalto':
                                            if (isset($subscriberInfoEntry[$tempIndex]) && !($subscriberInfoEntry[$tempIndex] == 0)) {
                                                $rulesPassed = false;
                                            } elseif (!(isset($subscriberInfoEntry[$tempIndex])) && ($subscriberInfoEntry['bounced'] == 0 && $subscriberInfoEntry['unsubscribed'] == 0) ) {
                                                $rulesPassed = false;
                                            }
                                            break;

                                    }
                                    break;
                                case 'subscribe':
                                // date conversion
                                    $tempActualValues = strtotime(date('Y-m-d', $tempActualValues));
                                    $tempRuleValues = explode('/', $tempRuleValues);
                                    $tempRuleValues = strtotime(implode('-', array_reverse($tempRuleValues)));
                                    $continue = true;
                                    break;
                                case 'campaign':
                                    switch ($ruleEntry->rules->ruleOperator) {
                                        case 'equalto':
                                            if (!$subsrciberApi->IsSubscriberHasOpenedNewsletters($subscriberInfoEntry['emailaddress'], $tempRuleValues)) {
                                                $rulesPassed = false;
                                            }
                                            break;
                                        case 'notequalto':
                                            if ($subsrciberApi->IsSubscriberHasOpenedNewsletters($subscriberInfoEntry['emailaddress'], $tempRuleValues)) {
                                                $rulesPassed = false;
                                            }
                                            break;

                                    }
                                    break;
                                default:
                                    $continue = true;
                            }
                            if ($continue) {
                                if ((int)$ruleEntry->rules->ruleName) {
                                    $tempActualValues = (isset ($preloadCustomFieldLoc[$ruleEntry->rules->ruleName]) && isset ($subscriberInfoEntry['CustomFields'][$preloadCustomFieldLoc[$ruleEntry->rules->ruleName]]['data']))?trim(strtolower($subscriberInfoEntry['CustomFields'][$preloadCustomFieldLoc[$ruleEntry->rules->ruleName]]['data'])):'';
                                    if ($ruleEntry->rules->ruleType == 'date') {
                                        $tempActualValues = split('/', $tempActualValues);
                                        $tempActualValues = strtotime(implode('-', array_reverse($tempActualValues)));
                                        $tempRuleValues = split('/', $tempRuleValues);
                                        $tempRuleValues = strtotime(implode('-', array_reverse($tempRuleValues)));
                                    }


                                }
                                switch ($ruleEntry->rules->ruleType) {
                                    case 'text':
                                    case 'textarea':
                                    case 'dropdown':
                                    case 'number':
                                    case 'radiobutton':
                                    case 'date':
                                        switch ($ruleEntry->rules->ruleOperator) {
                                            case 'equalto':
                                                if (!($tempActualValues == $tempRuleValues)) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            case 'notequalto':
                                                if ($tempActualValues == $tempRuleValues) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            case 'like':
                                                if (!(strstr($tempActualValues, $tempRuleValues))) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            case 'notlike':
                                                if (strstr($tempActualValues, $tempRuleValues)) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            case 'greaterthan':
                                                if ($tempActualValues <= $tempRuleValues) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            case 'lessthan':
                                                if ($tempActualValues >= $tempRuleValues) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            default:
                                                $rulesPassed = false;
                                        }
                                        break;
                                    case 'checkbox':
                                        $tempActualValues = unserialize($tempActualValues);
                                        $tempRuleValues = explode(', ', $tempRuleValues);
                                        $tempRuleValues = (is_array($tempRuleValues)) ? $tempRuleValues : array() ;
                                        $tempActualValues = (is_array($tempActualValues)) ? $tempActualValues : array() ;
                                        switch ($ruleEntry->rules->ruleOperator) {
                                            case 'equalto':
                                                if (sizeof(array_intersect($tempActualValues, $tempRuleValues)) != sizeof($tempRuleValues)) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            case 'notequalto':
                                                if (sizeof(array_intersect($tempActualValues, $tempRuleValues)) == sizeof($tempRuleValues)) {
                                                    $rulesPassed = false;
                                                }
                                                break;
                                            default:
                                                $rulesPassed = false;
                                        }
                                        break;
                                    default:
                                        $rulesPassed = false;
                                }
                            }
                        }
                        if ($blockEntry->isActivated()) {
                            $defaultBlock = $decodedRules;
                        }
                        if ($rulesPassed) {
                            $generatedContent = empty($generatedContent) ? '' : "$generatedContent<br />\r\n" ;
                            $generatedContent .= $decodedRules->Content;
                            $anyRulesPassed = true;
                        }
                    }
                    $data->contentTobeReplaced[$subscriberInfoEntry['subscriberid']]['tagsTobeReplaced'][] = '%%[' . trim($tagEntry->getName()) . ']%%';
                    if ($anyRulesPassed) {
						$data->contentTobeReplaced[$subscriberInfoEntry['subscriberid']]['tagsContentTobeReplaced'][] = $generatedContent;
					} else {
						$data->contentTobeReplaced[$subscriberInfoEntry['subscriberid']]['tagsContentTobeReplaced'][] = $defaultBlock->Content;
                    }
                }
            }
        }
    }

    /**
     * DctTinyMCEPluginHook
     * This will display the TinyMCE button for Dynamic Content Tag
     *
     * @param EventData_IEM_DCT_HTMLEDITOR_TINYMCEPLUGIN $data This will be set and used by the caller function
     * @return void This will return the variable via $data instead of return via function
     */
    public static function DctTinyMCEPluginHook(EventData_IEM_DCT_HTMLEDITOR_TINYMCEPLUGIN $data) {

        $userAPI = GetUser();

        // Permission checking
        $access = $userAPI->HasAccess('dynamiccontenttags', 'general');
        $access = $access || $userAPI->Admin();
        if (!$access) {
            $data->dynamicContentButton = "";
            $data->dynamicContentPopup = "";
            return;
        }

        $title = GetLang('Addon_dynamiccontenttags_DynamicContentTagsTitle');
        $page = (isset ($GLOBALS['PAGE']))?$GLOBALS['PAGE']:'';
        $data->dynamicContentButton = ",|,dctbutton";
        $data->dynamicContentPopup = "
		ed.addButton('dctbutton', {
			title : '$title',
			image : '" . SENDSTUDIO_APPLICATION_URL . "/admin/images/mce_dct_add.gif',
			onclick : function() {
				javascript: ShowDynamicContentTag('html', 'myDevEditControl', '" . $page . "'); return false;
			}
		});
		";

    }

    /**
     * CreateInsertTagButton
     * This will insert the dynamic content tag button below the editor
     *
     * @param EventData_IEM_EDITOR_TAG_BUTTON $data This will be set and used by the caller function
     * @return void This will return the variable via $data instead of return via function
     */
    public static function CreateInsertTagButton(EventData_IEM_EDITOR_TAG_BUTTON $data) {

        $userAPI = GetUser ();

        // Permission checking
        $access = $userAPI->HasAccess('dynamiccontenttags', 'general');
        $access = $access || $userAPI->Admin();
        if (!$access) {
            $data->tagButtonHtml = $data->tagButtonText = '';
            return;
        }
        $data->tagButtonText = '
            <li>
                    <a href="#" title="'.GetLang('DynContentTagsInsert_Editor').'" onclick="javascript: ShowDynamicContentTag(\'TextContent\', \'myDevEditControl\', \'%%PAGE%%\'); return false;">
                            <img src="images/mce_dct_add.gif" alt="icon" />'.GetLang('DynContentTagsInsert_Editor').'</a>
            </li>
        ';
        $data->tagButtonHtml = '
            <li>
                    <a href="#" title="'.GetLang('DynContentTagsInsert_Editor').'" onclick="javascript: ShowDynamicContentTag(\'html\', \'myDevEditControl\', \'%%PAGE%%\'); return false;">
                            <img src="images/mce_dct_add.gif" alt="icon" />'.GetLang('DynContentTagsInsert_Editor').'</a>
            </li>
        ';
    }

    /**
     * Admin_Action_ShowDynamicContentTag
     * This will show a list of available dynamic content tags for selection into the editor as place holder
     *
     * @return void This function will only show the available dynamic content tags
     */
    public function Admin_Action_ShowDynamicContentTag() {
    	$user = GetUser();
        $GLOBALS ['ContentArea'] = $_GET ['ContentArea'];
        $GLOBALS ['EditorName'] = 'myDeveditControl';
        if (isset ( $_GET ['EditorName'] )) {
            $GLOBALS ['EditorName'] = $_GET ['EditorName'];
        }

        // Query the tags
        $tmpTags = array ();
        $query = "SELECT * FROM [|PREFIX|]dynamic_content_tags dct ";
        if (!$user->isAdmin()) {
        	$query .= " WHERE dct.ownerid = '{$user->Get('userid')}' ";
        }


        $result = $this->db->Query ( $query );
        while ( $row = $this->db->Fetch ( $result ) ) {
            $tmpTags [] = array ($row ['tagid'], $row ['name'], $row ['createdate'] );
        }

        if (sizeof ( $tmpTags )) {
            $this->template_system->ParseTemplate ( 'DynamicContentTags_List_Start' );
            $this->template_system->ParseTemplate ( 'DynamicContentTags_List_List_Start' );
            foreach ( $tmpTags as $tagEntry ) {
                $this->template_system->assign ( 'tagId', $tagEntry [0] );
                $this->template_system->assign ( 'tagName', (strlen($tagEntry [1]) >= 120)?substr($tagEntry [1], 0, 120).'...':$tagEntry [1] );
                $this->template_system->ParseTemplate ( 'DynamicContentTags_List_Tag' );
            }
            $this->template_system->ParseTemplate ( 'DynamicContentTags_List_List_End' );
        } else {

            FlashMessage(GetLang('Addon_dynamiccontenttags_NoTags'), SS_FLASH_MSG_SUCCESS);
            $flash_messages = GetFlashMessages ();
            $this->template_system->Assign ( 'FlashMessages', $flash_messages, false );
            $this->template_system->Assign ( 'ShowInfo', 'display:none;' );
            $this->template_system->Assign ( 'CloseButton', '<input type="button" style="width: 50px;float:right;margin:0 10px; 0 0;" class="FormButton" value="'.GetLang('Close').'" onclick="linkWin.close(); return false;"/>' );
            $this->template_system->ParseTemplate ( 'DynamicContentTags_List_Start' );
        }
        $this->template_system->ParseTemplate ( 'DynamicContentTags_List_End' );
    }

    /**
     * Admin_Action_Delete
     * This will delete the selected dynamic content tag and related data
     *
     * @return boolean Return true if everything are deleted with no problem. Otherwise, return false;
     */
    public function Admin_Action_Delete() {
        $set = false;
        if (isset ( $_POST['tagid'] )) {
            $set = $_POST['tagid'];
        } else if (isset ( $_POST['tagids'] )) {
            $set = $_POST['tagids'];
        }
        
        if ($set !== false) {
            if (! is_array ( $set )) {
                $set = array ($set);
            }
            $ids = implode ( "','", $set );

            $this->db->StartTransaction ();
            $query = "DELETE FROM [|PREFIX|]dynamic_content_tags WHERE tagid in ('" . $ids . "')";

            if (! $result = $this->db->Query ( $query )) {
            // Error message
                $this->db->RollbackTransaction ();
                FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_Delete_Failure' ), SS_FLASH_MSG_ERROR, $this->admin_url );
                return false;
            }
            $query = "DELETE FROM [|PREFIX|]dynamic_content_block WHERE tagid in ('" . $ids . "')";

            if (! $result = $this->db->Query ( $query )) {
            // Error message
                $this->db->RollbackTransaction ();
                FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_Delete_Failure' ), SS_FLASH_MSG_ERROR, $this->admin_url );
                return false;
            }
            $this->db->CommitTransaction ();
            FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_Delete_Success' ), SS_FLASH_MSG_SUCCESS, $this->admin_url );
            return true;
        } else {
            FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_Delete_Failure' ) . "<br />POST data not found.", SS_FLASH_MSG_ERROR, $this->admin_url );
            return false;
        }
    }

    /**
     * Admin_Action_CustomFieldUsedByList
     * This will get all the custom fields related to the list
     *
     */
    public function Admin_Action_CustomFieldUsedByList() {
        $listIDs = $this->_getPOSTRequest ( 'listid', null );
        if (is_array ( $listIDs )) {
            $output = GetJSON ( $this->_getCustomFieldUsedByList ( $listIDs ) );
            echo $output;
        }
    }

    /**
     * Admin_Action_UpdateBlocksOrder
     * This only update the sort order of the content blocks     *
     */
    public function Admin_Action_UpdateBlocksOrder() {
        $blockIds = $this->_getPOSTRequest ( 'blockid', array() );
        foreach ($blockIds as $order => $blockid) {
            $query = "UPDATE [|PREFIX|]dynamic_content_block SET sortorder = '{$order}' WHERE blockid = '" . intval($blockid) . "'";
            $this->db->Query ( $query );
        }
        return;
    }

    /**
     * Admin_Action_SetDefaultBlock
     * This will set the default set for specific block
     *
     */
    public function Admin_Action_SetDefaultBlock() {
        $blockId = $this->_getPOSTRequest ( 'blockId', 0 );
        $query = "UPDATE [|PREFIX|]dynamic_content_block SET activated = '1' WHERE blockid = '" . intval($blockId) . "'";
        $this->db->Query ( $query );
        return;
    }

    /**
     * Admin_Action_CheckDuplicateTag
     * This will check if there is any duplication of the dynamic content tag name
     *
     */
    public function Admin_Action_CheckDuplicateTag() {
        $tagName = $this->_getPOSTRequest ( 'tagname', '' );
        $tagId = $this->_getPOSTRequest ( 'tagid', 0 );
        $query = "SELECT dct.name FROM [|PREFIX|]dynamic_content_tags dct WHERE dct.tagid != " . intval($tagId) . " AND dct.name = '{$tagName}'";
        $result = $this->db->Query ( $query );
        $row = $this->db->Fetch($result);
        if (!preg_match('/^[A-Za-z0-9_\- ]+$/', $tagName)) {
            echo GetLang ( 'Addon_dynamiccontenttags_TagNameInvalidChars' );
        } elseif (!empty($row)) {
            echo GetLang ( 'Addon_dynamiccontenttags_DuplicateTagName' );
        }
        return;
    }

    /**
     * Admin_Action_CustomFieldCompareByList
     * This compares the custom fields between 2 lists.
     *
     */
    public function Admin_Action_CustomFieldCompareByList() {
        $listIDs = $this->_getPOSTRequest ( 'listid', null );
        $otherListIDs = $this->_getPOSTRequest ( 'otherlistid', 0 );
        $listCf = array ();
        $resultListIdOutput = '';
        if (is_array ( $listIDs )) {
            $resultListId = $this->_getCustomFieldUsedByList ( $listIDs );
            $listCf = array_keys ( $resultListId ['customfields'] );
            $resultListIdOutput = GetJSON ( $listCf );
        }

        if (is_array ( $otherListIDs )) {
            $resultOtherListId = $this->_getCustomFieldUsedByList ( $otherListIDs );
            $otherListCf = array_keys ( $resultOtherListId ['customfields'] );
            $otherCfList = array ();
            foreach ( $listCf as $listCfEntry ) {
                if (! in_array ( $listCfEntry, $otherListCf )) {
                    $otherCfList [] = $listCfEntry;
                }
            }
            $resultListIdOutput = GetJSON ( $otherCfList );
        }
        echo $resultListIdOutput;
    }

    /**
     * Admin_Action_DeleteBlock
     * This will delete content block
     *
     */
    public function Admin_Action_DeleteBlock() {
        $blockId = $this->_getPOSTRequest ( 'blockid', 0 );
        if ($blockId) {
            $blockIds = implode ( "','", $blockId );
            $query = "DELETE FROM [|PREFIX|]dynamic_content_block WHERE blockid in ('{$blockIds}')";

            if ($result = $this->db->Query ( $query )) {
            // Error message
                FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_DeleteBlock_Success' ), SS_FLASH_MSG_SUCCESS );
                echo GetJSON ( array ('message' => GetFlashMessages (), 'result' => '1' ) );
                return;
            }
        }
        FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_DeleteBlock_Failure' ), SS_FLASH_MSG_ERROR );
        echo GetJSON ( array ('message' => GetFlashMessages (), 'result' => '0' ) );
        return;
    }

    /**
     * Admin_Action_UpdateBlock
     * This will update block
     *
     */
    public function Admin_Action_UpdateBlock() {
        $blockId = $this->_getPOSTRequest ( 'blockid', 0 );
        $tagId = $this->_getPOSTRequest ( 'tagid', 0 );
        $name = $this->_getPOSTRequest ( 'name', 0 );
        $rules = $this->_getPOSTRequest ( 'rules', 0 );
        $activated = $this->_getPOSTRequest ( 'activated', 0 );
        $sortorder = $this->_getPOSTRequest ( 'sortorder', -1 );

        if (intval($activated) == 1) {
        	$query = "UPDATE [|PREFIX|]dynamic_content_block "
            . " SET activated = '0' "
            . " WHERE tagid = '".intval($tagId)."'";
            $result = $this->db->Query ( $query );
        }


        $query = "UPDATE [|PREFIX|]dynamic_content_block "
            . " SET name = '".$this->db->Quote($name)."'"
            . ", rules = '".$this->db->Quote($rules)."'"
            . ", activated = '".intval($activated)."'"
            . ", sortorder = '".$sortorder."'"
            . " WHERE blockid = '".intval($blockId)."'";
        if (strlen($blockId) == 32) {
            $query = "INSERT INTO [|PREFIX|]dynamic_content_block (tagid, name, rules, activated, sortorder) VALUES ('{$tagId}', '{$this->db->Quote($name)}', '{$this->db->Quote($rules)}', '{$activated}', '{$sortorder}')";
        }
        $result = $this->db->Query ( $query );
        if ($result && strlen($blockId) == 32) {
            $blockId = $this->db->LastId('[|PREFIX|]dynamic_content_block');
        }

        echo $blockId;
        return;
    }

    /**
     * Admin_Action_Default
     * This will list all the available dynamic tags for users to manage
     *
     */
    public function Admin_Action_Default() {
        $user = GetUser ();

		$userLists = $user->GetLists();
		$userListsId = array_keys($userLists);
		if (sizeof($userListsId) < 1) {
			$GLOBALS['Intro_Help'] = GetLang('Addon_dynamiccontenttags_Form_Intro');
			$GLOBALS['Intro'] = GetLang('Addon_dynamiccontenttags_ViewHeading');
			$GLOBALS['Lists_AddButton'] = '';

			if ($user->CanCreateList() === true) {
	            FlashMessage(sprintf(GetLang('Addon_dynamiccontenttags_Tags_NoLists'), GetLang('Addon_dynamiccontenttags_ListCreate')), SS_FLASH_MSG_SUCCESS);
	            $GLOBALS['Message'] = GetFlashMessages ();
				$GLOBALS['Lists_AddButton'] = $this->template_system->ParseTemplate('Dynamiccontenttags_List_Create_Button', true);
			} else {
	            FlashMessage(sprintf(GetLang('Addon_dynamiccontenttags_Tags_NoLists'), GetLang('Addon_dynamiccontenttags_ListAssign')), SS_FLASH_MSG_SUCCESS);
	            $GLOBALS['Message'] = GetFlashMessages ();
			}
			$this->template_system->ParseTemplate('Dynamiccontenttags_Subscribers_No_Lists');
			return;
		}

        $this->template_system->Assign ( 'AdminUrl', $this->admin_url, false );
        $this->sortDetails = $this->GetSortDetails ();

        $this->perPage = intval($this->_getGETRequest('PerPageDisplay', 0));
        if (!$this->perPage) {
            $this->perPage = $this->GetPerPage();
        }
        $displayPage = $this->GetCurrentPage();
        if ($this->perPage != 'all') {
            $this->start = ($displayPage - 1) * $this->perPage;
        }
        $this->loadTags ();

        $numberOfTags = $this->getTagsSize ();

        $create_button = $this->template_system->ParseTemplate ( 'Create_Button', true, false );
        $this->template_system->Assign ( 'Tags_Create_Button', $create_button, false );

        $this->template_system->Assign ( 'ShowDeleteButton', true );

        $flash_messages = GetFlashMessages ();

        $this->template_system->Assign ( 'FlashMessages', $flash_messages, false );

        if (! isset ( $GLOBALS ['Message'] )) {
            $GLOBALS ['Message'] = '';
        }

        $userid = $user->Get ( 'userid' );
        if ($user->Admin ()) {
            $userid = 0;
        }

        if ($numberOfTags == 0) {
            $curr_template_dir = $this->template_system->GetTemplatePath ();

            $this->template_system->SetTemplatePath ( SENDSTUDIO_TEMPLATE_DIRECTORY );
            $GLOBALS ['Success'] = GetLang ( 'Addon_dynamiccontenttags_NoTagsListPage' );

            $msg = $this->template_system->ParseTemplate ( 'successmsg', true );
            $this->template_system->SetTemplatePath ( $curr_template_dir );

            $this->template_system->Assign ( 'Addon_Tags_Empty', $msg, false );

            $this->template_system->ParseTemplate ( 'manage_empty' );
            return;
        }

        $this->template_system->Assign ( 'ApplicationUrl', $this->application_url, false );

        $this->template_system->Assign ( 'EditPermission', true );

        $this->template_system->Assign ( 'DeletePermission', true );

        $paging = $this->SetupPaging ( $this->admin_url, $numberOfTags);
        $this->template_system->Assign ( 'Paging', $paging, false );
        $this->template_system->Assign ( 'DateFormat', GetLang ( 'DateFormat' ) );

        $tmpTags = array ();
        foreach ( $this->tags as $k => $v ) {
        	$tmpUser = GetUser($v->getOwnerId());
            $tmpTags [$k] ['tagid'] = $v->getTagId ();
            $tmpTags [$k] ['name'] = $v->getName ();
            $tmpTags [$k] ['createdate'] = $v->getCreatedDate ();
            $tmpTags [$k] ['ownerid'] = $v->getOwnerId();
            $tmpTags [$k] ['ownerusername'] = $tmpUser->username;
        }

        $this->template_system->Assign ( 'tags', $tmpTags );
        $this->template_system->ParseTemplate ( 'manage_display' );

    }

    /**
     * _getCustomFieldUsedByList
     * This function get a list of custom fields by list
     *
     * @param array $listIDs An array of list where its custom fields to be retrieved
     *
     * @return array A list of custom fields used by the lists.
     */
    function _getCustomFieldUsedByList($listIDs) {
        require_once (SENDSTUDIO_API_DIRECTORY . '/lists.php');

        $cacheid = implode ( ':', $listIDs );

        if (! array_key_exists ( $cacheid, $this->_cacheCustomFieldsUsedByLists )) {
            $listapi = new Lists_API ( );

            $tempOutput = array ('list' => array (), 'customfields' => array (), 'values' => array () );

            foreach ( $listIDs as $tempID ) {
                $tempStatus = $listapi->GetCustomFields ( $tempID );

                if (! array_key_exists ( $tempID, $tempOutput ['list'] )) {
                    $tempOutput ['list'] [$tempID] = array ();
                }

                foreach ( $tempStatus as $tempEach ) {
                    array_push ( $tempOutput ['list'] [$tempID], $tempEach ['fieldid'] );

                    /**
                     * Get list of custom fields
                     */
                    if (! array_key_exists ( $tempEach ['fieldid'], $tempOutput ['customfields'] )) {
                        $tempFieldType = 'text';

                        switch ($tempEach ['fieldtype']) {
                            case 'date' :
                                $tempFieldType = 'date';
                                break;

                            case 'number' :
                                $tempFieldType = 'number';
                                break;

                            case 'checkbox' :
                                $tempFieldType = 'multiple';
                                break;

                            case 'radiobutton' :
                            case 'dropdown' :
                                $tempFieldType = 'dropdown';
                                break;
                        }

                        $tempOutput ['customfields'] [$tempEach ['fieldid']] = array ('name' => htmlspecialchars ( $tempEach ['name'], ENT_QUOTES, SENDSTUDIO_CHARSET ), 'fieldtype' => $tempEach ['fieldtype'], 'defaultvalue' => $tempEach ['defaultvalue'], 'operatortype' => $tempFieldType );
                    }
                    /**
                     * -----
                     */

                    /**
                     * Get list of values the custom field uses
                     */
                    if (! array_key_exists ( $tempEach ['fieldid'], $tempOutput ['values'] )) {
                        $tempFieldValues = array ();
                        $temp = unserialize ( $tempEach ['fieldsettings'] );
                        if (is_array ( $temp ) && array_key_exists ( 'Key', $temp ) && array_key_exists ( 'Value', $temp )) {
                            foreach ( $temp ['Key'] as $index => $value ) {
                                array_push ( $tempFieldValues, array ('value' => $value, 'text' => htmlspecialchars ( $temp ['Value'] [$index], ENT_QUOTES, SENDSTUDIO_CHARSET ) ) );
                            }
                        }

                        if (count ( $tempFieldValues ) != 0) {
                            $tempOutput ['values'] [$tempEach ['fieldid']] = $tempFieldValues;
                        }
                    }
                }

            }

            if (count ( $tempOutput ['list'] ) == 0) {
                $tempOutput ['list'] = null;
            }

            if (count ( $tempOutput ['customfields'] ) == 0) {
                $tempOutput ['customfields'] = null;
            }

            if (count ( $tempOutput ['values'] ) == 0) {
                $tempOutput ['values'] = null;
            }

            $this->_cacheCustomFieldsUsedByLists [$cacheid] = $tempOutput;
        }

        return $this->_cacheCustomFieldsUsedByLists [$cacheid];
    }

    /**
     * Admin_Action_Save
     * This will save the dynamic content tag and related blocks and lists.
     *
     */
    public function Admin_Action_Save() {
        // Dynamic Content Tags Properties
        $userAPI = GetUser ();
        $tagId = $this->_getPOSTRequest ( 'dynamiccontenttags_id', 0 );
        $tagName = $this->_getPOSTRequest ( 'dynamiccontenttags_name', '' );
        $tagDate = time ();
        $mesgPrefix = 'Update';
        if ($tagId == 0) {
            $mesgPrefix = 'Create';
        }
        $redirectUrl = $this->admin_url;

        // Tag lists
        $lists = $this->_getPOSTRequest ( 'SelectList', array () );

        // Content Blocks Properties
        $tmpBlocks = $this->_getPOSTRequest ( 'blocks', array () );

        $blocks = array ();
        if (sizeof ( $tmpBlocks )) {
            $sortOrderCounter = 0;
            foreach ( $tmpBlocks as $k => $v ) {
                $blockId = (strlen($k) == 32) ? 0 : $k;
                $blockActivated = (isset ( $v ['activated'] ) && $v ['activated'] == 1) ? 1 : 0;
                $blockName = $v ['name'];
                $blockRules = $v ['data'];
                $blockSortOrder = $sortOrderCounter++;
                $blocks [] = new DynamicContentTag_Api_Block ( $blockId, $blockName, $blockRules, $blockActivated, $blockSortOrder, $tagId );
            }
        }

        $tag = new DynamicContentTag_Api_Tag ( $tagId, $tagName, $tagDate, $userAPI->Get('userid'), $blocks, $lists );

        $savedTagId = $tag->save ();

        if (isset ( $_POST ['subact'] ) && $_POST ['subact'] == 'saveedit') {
            $redirectUrl = $this->admin_url . "&Action=Edit&id={$savedTagId}";
        }

        if ($savedTagId) {
            FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_' . $mesgPrefix . 'Tag_Success' ), SS_FLASH_MSG_SUCCESS, $redirectUrl );
        } else {
            FlashMessage ( GetLang ( 'Addon_dynamiccontenttags_' . $mesgPrefix . 'Tag_Failure' ), SS_FLASH_MSG_ERROR, $redirectUrl );
        }
    }

    /**
     * Admin_Action_Edit
     * This will display the edition/creation page for dynamic content tag
     *
     */
    public function Admin_Action_Edit() {
        $ssf = new SendStudio_Functions ( );
        $id = $this->_getGETRequest ( 'id', 0 );
        $userAPI = GetUser ();

		$userLists = $userAPI->GetLists();
		$userListsId = array_keys($userLists);
		if (sizeof($userListsId) < 1) {
			$GLOBALS['Intro_Help'] = GetLang('Addon_dynamiccontenttags_Form_Intro');
			$GLOBALS['Intro'] = GetLang('Addon_dynamiccontenttags_Form_CreateHeading');
			$GLOBALS['Lists_AddButton'] = '';

			if ($userAPI->CanCreateList() === true) {
	            FlashMessage(sprintf(GetLang('Addon_dynamiccontenttags_Tags_NoLists'), GetLang('Addon_dynamiccontenttags_ListCreate')), SS_FLASH_MSG_SUCCESS);
	            $GLOBALS['Message'] = GetFlashMessages ();
				$GLOBALS['Lists_AddButton'] = $this->template_system->ParseTemplate('Dynamiccontenttags_List_Create_Button', true);
			} else {
	            FlashMessage(sprintf(GetLang('Addon_dynamiccontenttags_Tags_NoLists'), GetLang('Addon_dynamiccontenttags_ListAssign')), SS_FLASH_MSG_SUCCESS);
	            $GLOBALS['Message'] = GetFlashMessages ();
			}
			$this->template_system->ParseTemplate('Dynamiccontenttags_Subscribers_No_Lists');
			return;
		}

        $listIDs = array ();
        $this->template_system->Assign ( 'DynamicContentTagId', intval ( $id ) );
        if ($id === 0) {
            $this->template_system->Assign ( 'FormType', 'create' );
        } else {
            $this->template_system->Assign ( 'FormType', 'edit' );
            // Load the existing Tags.
            $tag = new DynamicContentTag_Api_Tag ( $id );
            if (!$tag->getTagId()) {
                FlashMessage ( GetLang ( 'NoAccess' ), SS_FLASH_MSG_ERROR, $this->admin_url );
                return false;
            }

            $tag->loadLists ();
            $tag->loadBlocks ();
            $listIDs = $tag->getLists ();
            $blocks = $tag->getBlocks ();
            $blocksString = '';

            foreach ( $blocks as $blockEntry ) {
                $rule = $blockEntry->getRules ();
                $rule = str_replace(array('\"', "'"), array('\\\\"', '&#39;'), $rule);
                $blocksString .= " BlockInterface.Add(" . intval ( $blockEntry->getBlockId () ) . ", '" . $blockEntry->getName () . "', " . intval ( $blockEntry->isActivated () ) . ", " . intval ( $blockEntry->getSortOrder() ) . ", '" . $rule . "'); ";
            }

            $this->template_system->Assign ( 'dynamiccontenttags_name', $tag->getName () );
            $this->template_system->Assign ( 'dynamiccontenttags_blocks', $blocksString );
        }

        $tempList = $userAPI->GetLists ();
        $tempSelectList = '';

        foreach ( $tempList as $tempEach ) {
            $tempSubscriberCount = intval ( $tempEach ['subscribecount'] );

            $GLOBALS ['ListID'] = intval ( $tempEach ['listid'] );
            $GLOBALS ['ListName'] = htmlspecialchars ( $tempEach ['name'], ENT_QUOTES, SENDSTUDIO_CHARSET );
            $GLOBALS ['OtherProperties'] = in_array ( $GLOBALS ['ListID'], $listIDs ) ? ' selected="selected"' : '';

            if ($tempSubscriberCount == 1) {
                $GLOBALS ['ListSubscriberCount'] = GetLang ( 'Addon_dynamiccontenttags_Subscriber_Count_One' );
            } else {
                $GLOBALS ['ListSubscriberCount'] = sprintf ( GetLang ( 'Addon_dynamiccontenttags_Subscriber_Count_Many' ), $ssf->FormatNumber ( $tempSubscriberCount ) );
            }

            $tempSelectList .= $this->template_system->ParseTemplate ( 'DynamicContentTags_Form_ListRow', true );

            unset ( $GLOBALS ['OtherProperties'] );
            unset ( $GLOBALS ['ListSubscriberCount'] );
            unset ( $GLOBALS ['ListName'] );
            unset ( $GLOBALS ['ListID'] );
        }

        // If list is less than 10, use the following formula: list size * 25px for the height
        $tempCount = count ( $tempList );
        if ($tempCount <= 10) {
            if ($tempCount < 3) {
                $tempCount = 3;
            }
            $selectListStyle = 'height: ' . ($tempCount * 25) . 'px;';
            $this->template_system->Assign ( 'SelectListStyle', $selectListStyle );
        }
        $flash_messages = GetFlashMessages ();

        $this->template_system->Assign ( 'FlashMessages', $flash_messages, false );
        $this->template_system->Assign ( 'AdminUrl', $this->admin_url, false );
        $this->template_system->Assign ( 'SelectListHTML', $tempSelectList );
        $this->template_system->ParseTemplate ( 'dynamiccontenttags_form' );
    }

    /**
     * Admin_Action_ShowBlockForm
     * This will show the edition/creation page for content blocks
     *
     */
    public function Admin_Action_ShowBlockForm() {
        $ssf = new SendStudio_Functions ( );
        $action = 'new';
        $GLOBALS ['blockid'] = (isset ( $_GET ['id'] ) && $_GET ['id'] > 0) ? $_GET ['id'] : md5(rand ( 1, 100000000 ));
        $GLOBALS ['tagid'] = (isset ( $_GET ['tagId'] ) && $_GET ['tagId'] > 0) ? $_GET ['tagId'] : 0;
        $GLOBALS ['blockaction'] = (isset ( $_GET ['id'] ) && $_GET ['id'] > 0) ? 'edit' : 'new';
        $GLOBALS ['BlockEditor'] = $ssf->GetHTMLEditor ( '', false, 'blockcontent', 'exact', 260, 630 );
        $GLOBALS ['CustomDatepickerUI'] = $this->template_system->ParseTemplate('UI.DatePicker.Custom_IEM', true);
        $this->template_system->ParseTemplate ( 'dynamiccontentblocks_form' );
    }

    /**
     * loadTags
     * This will load the dynamic content tags from database to memory
     *
     */
    public function loadTags($id = 0) {
    	$user = GetUser();

        $tmpTags = array ();
        $query = "SELECT * FROM [|PREFIX|]dynamic_content_tags dct";
        if ($id != 0) {
            $query .= " WHERE dct.tagid = {$id} ";
            if (!$user->isAdmin()) {
            	$query .= " AND dct.ownerid = ".$user->Get('userid');
            }
        }else{
            if (!$user->isAdmin()) {
            	$query .= " WHERE dct.ownerid = ".$user->Get('userid');
            }
        }

        if (sizeof($this->sortDetails)) {
            $query .= " ORDER BY {$this->sortDetails['SortBy']} {$this->sortDetails['Direction']}";
        }
        if (isset ($this->perPage) && $this->perPage > 0) {
            $query .= $this->db->AddLimit($this->start, $this->perPage);
        }

        $result = $this->db->Query ( $query );
        while ( $row = $this->db->Fetch ( $result ) ) {
            $tmpTags [] = new DynamicContentTag_Api_Tag ( $row ['tagid'], $row ['name'], $row ['createdate'], $row ['ownerid'], array (), array () );
        }
        $this->setTags ( $tmpTags );
    }

    /**
     * loadTagsByList
     * This will load the dynamic content tags according to its lists
     *
     */
    public function loadTagsByList($listId = array()) {
        $tmpTags = array ();
        $query = "SELECT dct.* FROM [|PREFIX|]dynamic_content_tags dct, [|PREFIX|]list_tags dcl ";
        if (is_array($listId) && sizeof($listId)) {
            $query .= " WHERE dct.tagid = dcl.tagid AND dcl.listid in ('".implode($listId, "','")."')";
        }

        $result = $this->db->Query ( $query );
        while ( $row = $this->db->Fetch ( $result ) ) {
            $tmpTags [] = new DynamicContentTag_Api_Tag ( $row ['tagid'], $row ['name'], $row ['createdate'], $row ['ownerid'], array (), array () );
        }
        $this->setTags ( $tmpTags );
    }

    /**
     * setTags
     * This will replace the dynamic content tag by a new one.
     *
     * @return void This function replace the dynamic content tag without returning any value.
     *
     */
    public function setTags($newVal) {
        $this->tags = $newVal;
    }

    /**
     * getTags
     * This will return available dynamic content tags
     *
     * @return array Return all the loaded dynamic content tags
     *
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * getTagsSize
     * This will return number of dynamic content tags
     *
     * @return int Return size of loaded dynamic content tags
     */
    public function getTagsSize() {
    	$user = GetUser();
        $query = "SELECT COUNT(dct.tagid) AS tagsize FROM [|PREFIX|]dynamic_content_tags dct ";
        if (!$user->isAdmin()) {
        	$query .= " WHERE dct.ownerid = '{$user->Get('userid')}' ";
        }
        $result = $this->db->Query ( $query );
        if($row = $this->db->Fetch ( $result )) {
            return $row ['tagsize'];
        }
        return 0;
    }

    /**
     * getTagObjectsSize
     * This will return number of dynamic content tags objects
     *
     * @return int Return size of loaded dynamic content tags objects
     */
    public function getTagObjectsSize() {
        if(isset($this->tags) && is_array($this->tags)) {
            return sizeof($this->tags);
        }
        return 0;
    }

}
