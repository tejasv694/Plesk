<?php
/**
* This file has the user editing forms in it.
*
* @version     $Id: users.php,v 1.55 2008/03/05 04:00:38 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for the users management page.
* This checks whether you are allowed to manage users or whether you are only allowed to manage your own account. This also handles editing, deleting, checks to make sure you're not removing the 'last' user and so on.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Users extends SendStudio_Functions
{
	/**
	* PopupWindows
	* An array of popup windows used in this class. Used to work out what sort of header and footer to print.
	*
	* @see Process
	*
	* @var Array
	*/
	var $PopupWindows = array('sendpreview', 'sendpreviewdisplay', 'generatetoken','testgooglecalendar');

	/**
	* _DefaultDirection
	* Override the default sort direction for users.
	*
	* @see _DefaultSort
	* @see GetSortDetails
	*
	* @var String
	*/
	var $_DefaultDirection = 'asc';

	/**
	* Constructor
	* Loads the 'users' and 'timezones' language files
	*
	* @return Void Doesn't return anything.
	*/
	function Users()
	{
		$this->LoadLanguageFile('Users');
		$this->LoadLanguageFile('Timezones');
	}

	/**
	* Process
	* Works out what's going on.
	* The API does the loading, saving, updating - this page just displays the right form(s), checks password validation and so on.
	* After that, it'll print a success/failure message depending on what happened.
	* It also checks to make sure that you're an admin before letting you add or delete.
	* It also checks you're not going to delete your own account.
	* If you're not an admin user, it won't let you edit anyone elses account and it won't let you delete your own account either.
	*
	* @see PrintHeader
	* @see ParseTemplate
	* @see IEM::getDatabase()
	* @see GetUser
	* @see GetLang
	* @see User_API::Set
	* @see PrintEditForm
	* @see CheckUserSystem
	* @see PrintManageUsers
	* @see User_API::Find
	* @see User_API::Admin
	* @see PrintFooter
	*
	* @return Void Doesn't return anything, passes control over to the relevant function and prints that functions return message.
	*/
	function Process()
	{
		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : '';

		if (!in_array($action, $this->PopupWindows)) {
			$this->PrintHeader();
		}

		$thisuser    = IEM::getCurrentUser();
		$checkaction = $action;
		
		if ($action == 'generatetoken') {
			$checkaction = 'manage';
		}
		
		if (!$thisuser->HasAccess('users', $checkaction)) {
			$this->DenyAccess();
		}

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			
			$action = '';
		}

		switch ($action) {
			case 'generatetoken':
				$check_fields = array('username', 'fullname', 'emailaddress');
				foreach ($check_fields as $field) {
					if (!isset($_POST[$field])) {
						exit;
					}
					$$field = $_POST[$field];
				}
				$user = GetUser();
				echo htmlspecialchars(sha1($username . $fullname . $emailaddress . GetRealIp(true) . time() . microtime()), ENT_QUOTES, SENDSTUDIO_CHARSET);
				exit;
			break;

			case 'save':
				$userid = (isset($_GET['UserID']))
					? $_GET['UserID']
					: 0;
				
				if (empty($_POST)) {
					$GLOBALS['Error']   = GetLang('UserNotUpdated');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					
					$this->PrintEditForm($userid);
					
					break;
				}

				$user     = GetUser($userid);
				$username = false;
				
				if (isset($_POST['username'])) {
					$username = $_POST['username'];
				}
				
				$userfound = $user->Find($username);
				$error     = false;
				$template  = false;

				$duplicate_username = false;
				
				if ($userfound && $userfound != $userid) {
					$duplicate_username = true;
					$error = GetLang('UserAlreadyExists');
				}

				$warnings           = array();
				$GLOBALS['Message'] = '';

				if (!$duplicate_username) {
					$to_check = array();
					
					foreach (array('status' => 'isLastActiveUser', 'admintype' => 'isLastSystemAdmin') as $area => $desc) {
						if (!isset($_POST[$area])) {
							$to_check[] = $desc;
						}
						
						if (isset($_POST[$area]) && $_POST[$area] == '0') {
							$to_check[] = $desc;
						}
					}

					if ($user->isAdmin()) {
						$to_check[] = 'isLastSystemAdmin';
					}

					$error = $this->CheckUserSystem($userid, $to_check);
                    
					if (!$error) {
						$smtptype = (isset($_POST['smtptype']))
							? $_POST['smtptype'] 
							: 0;

						// Make sure smtptype is eiter 0 or 1
						if ($smtptype != 1) {
							$smtptype = 0;
						}

						/**
						 * This was added, because User's API uses different names than of the HTML form names.
						 * HTML form names should stay the same to keep it consistant throught the application
						 *
						 * This will actually map HTML forms => User's API fields
						 */
						$areaMapping = array(
							'trialuser'                    => 'trialuser',
							'groupid'                      => 'groupid',
							'username'                     => 'username',
							'fullname'                     => 'fullname',
							'emailaddress'                 => 'emailaddress',
							'status'                       => 'status',
							'admintype'                    => 'admintype',
							'listadmintype'                => 'listadmintype',
							'segmentadmintype'             => 'segmentadmintype',
							'templateadmintype'            => 'templateadmintype',
							'editownsettings'              => 'editownsettings',
							'usertimezone'                 => 'usertimezone',
							'textfooter'                   => 'textfooter',
							'htmlfooter'                   => 'htmlfooter',
							'infotips'                     => 'infotips',
							'smtp_server'                  => 'smtpserver',
							'smtp_u'                       => 'smtpusername',
							'smtp_p'                       => 'smtppassword',
							'smtp_port'                    => 'smtpport',
							'usewysiwyg'                   => 'usewysiwyg',
							'usexhtml'                     => 'usexhtml',
							'enableactivitylog'            => 'enableactivitylog',
							'xmlapi'                       => 'xmlapi',
							'xmltoken'                     => 'xmltoken',
							'googlecalendarusername'       => 'googlecalendarusername',
							'googlecalendarpassword'       => 'googlecalendarpassword',
							'user_language'                => 'user_language',
							'adminnotify_email'            => 'adminnotify_email',
							'adminnotify_send_flag'        => 'adminnotify_send_flag',
							'adminnotify_send_threshold'   => 'adminnotify_send_threshold',
							'adminnotify_send_emailtext'   => 'adminnotify_send_emailtext',
							'adminnotify_import_flag'      => 'adminnotify_import_flag',
							'adminnotify_import_threshold' => 'adminnotify_import_threshold',
							'adminnotify_import_emailtext' => 'adminnotify_import_emailtext'
						);
						
						$group           = API_USERGROUPS::getRecordById($_POST['groupid']);
						$totalEmails     = (int) $group['limit_totalemailslimit'];
						$unlimitedEmails = $totalEmails == 0;
						
						// set fields
						foreach ($areaMapping as $p => $area) {
							$val = (isset($_POST[$p])) ? $_POST[$p] : '';
							
							if (in_array($area, array('status', 'editownsettings'))) {
								if ($userid == $thisuser->userid) {
									$val = $thisuser->$area;
								}
							}
							
							$user->Set($area, $val);
						}

						// activity type
						$activity = IEM::requestGetPOST('eventactivitytype', '', 'trim');
						
						if (!empty($activity)) {
							$activity_array = explode("\n", $activity);
							
							for ($i = 0, $j = count($activity_array); $i < $j; ++$i) {
								$activity_array[$i] = trim($activity_array[$i]);
							}
						} else {
							$activity_array = array();
						}
						
						$user->Set('eventactivitytype', $activity_array);

						// the 'limit' things being on actually means unlimited. so check if the value is NOT set.
						foreach (array('permonth', 'perhour', 'maxlists') as $p => $area) {
							$limit_check = 'limit' . $area;
							$val         = 0;
							
							if (!isset($_POST[$limit_check])) {
								$val = (isset($_POST[$area])) 
									? $_POST[$area]
									: 0;
							}
							
							$user->Set($area, $val);
						}

						if (SENDSTUDIO_MAXHOURLYRATE > 0) {
							if ($user->Get('perhour') == 0 || ($user->Get('perhour') > SENDSTUDIO_MAXHOURLYRATE)) {
								$user_hourly = $this->FormatNumber($user->Get('perhour'));
								
								if ($user->Get('perhour') == 0) {
									$user_hourly = GetLang('UserPerHour_Unlimited');
								}
								
								$warnings[] = sprintf(GetLang('UserPerHourOverMaxHourlyRate'), $this->FormatNumber(SENDSTUDIO_MAXHOURLYRATE), $user_hourly);
							}
						}

						if ($smtptype == 0) {
							$user->Set('smtpserver', '');
							$user->Set('smtpusername', '');
							$user->Set('smtppassword', '');
							$user->Set('smtpport', 25);
						}

						if ($_POST['ss_p'] != '') {
							if ($_POST['ss_p_confirm'] != '' && $_POST['ss_p_confirm'] == $_POST['ss_p']) {
								$user->Set('password', $_POST['ss_p']);
							} else {
								$error = GetLang('PasswordsDontMatch');
							}
						}
					}

					if (!$error) {
						$user->RevokeAccess();

						$temp = array();
						
						if (!empty($_POST['permissions'])) {
							foreach ($_POST['permissions'] as $area => $p) {
								foreach ($p as $subarea => $k) {
									$temp[$subarea] = $user->GrantAccess($area, $subarea);
								}
							}
						}
					}
				}

				if (!$error) {
					$result = $user->Save();

					if ($result) {
						FlashMessage(GetLang('UserUpdated'), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Users'));
					} else {
						$GLOBALS['Message'] = GetFlashMessages();
						$GLOBALS['Error'] = GetLang('UserNotUpdated');
						$GLOBALS['Message'] .= $this->ParseTemplate('ErrorMsg', true, false);
					}
				} else {
					$GLOBALS['Error'] = $error;
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				}

				if (!empty($warnings)) {
					$GLOBALS['Warning'] = implode('<br/>', $warnings);
					$GLOBALS['Message'] .= $this->ParseTemplate('WarningMsg', true, false);
				}

				$this->PrintEditForm($userid);
			break;

			case 'add':
				$temp = get_available_user_count();
				if ($temp['normal'] == 0 && $temp['trial'] == 0) {
					$this->PrintManageUsers();
					break;
				}

				$this->PrintEditForm(0);
			break;

			case 'delete':
				$users = IEM::requestGetPOST('users', array(), 'intval');
				$deleteData = (IEM::requestGetPOST('deleteData', 0, 'intval') == 1);

				$this->DeleteUsers($users, $deleteData);
			break;

			case 'create':
				$user     = New User_API();
				$warnings = array();
				$fields   = array(
					'trialuser', 'username', 'fullname', 'emailaddress',
					'status', 'admintype', 'editownsettings',
					'listadmintype', 'segmentadmintype', 'usertimezone',
					'textfooter', 'htmlfooter', 'templateadmintype',
					'infotips', 'smtpserver',
					'smtpusername', 'smtpport', 'usewysiwyg',
					'enableactivitylog', 'xmlapi', 'xmltoken',
					'googlecalendarusername','googlecalendarpassword',
					'adminnotify_email','adminnotify_send_flag','adminnotify_send_threshold',
					'adminnotify_send_emailtext','adminnotify_import_flag','adminnotify_import_threshold',
					'adminnotify_import_emailtext'
				);

				if (!$user->Find($_POST['username'])) {
					foreach ($fields as $p => $area) {
						$val = (isset($_POST[$area]))
							? $_POST[$area]
							: '';

						$user->Set($area, $val);
					}

					// activity type
					$activity = IEM::requestGetPOST('eventactivitytype', '', 'trim');
					
					if (!empty($activity)) {
						$activity_array = explode("\n", $activity);
						
						for ($i = 0, $j = count($activity_array); $i < $j; ++$i) {
							$activity_array[$i] = trim($activity_array[$i]);
						}
					} else {
						$activity_array = array();
					}
					
					$user->Set('eventactivitytype', $activity_array);

					// the 'limit' things being on actually means unlimited. so check if the value is NOT set.
					foreach (array('permonth', 'perhour', 'maxlists') as $p => $area) {
						$limit_check = 'limit' . $area;
						$val         = 0;
						
						if (!isset($_POST[$limit_check])) {
							$val = (isset($_POST[$area])) 
								? $_POST[$area]
								: 0;
						}
						
						$user->Set($area, $val);
					}

					if (SENDSTUDIO_MAXHOURLYRATE > 0) {
						if ($user->Get('perhour') == 0 || ($user->Get('perhour') > SENDSTUDIO_MAXHOURLYRATE)) {
							$user_hourly = $this->FormatNumber($user->Get('perhour'));
							
							if ($user->Get('perhour') == 0) {
								$user_hourly = GetLang('UserPerHour_Unlimited');
							}
							
							$warnings[] = sprintf(GetLang('UserPerHourOverMaxHourlyRate'), $this->FormatNumber(SENDSTUDIO_MAXHOURLYRATE), $user_hourly);
						}
					}

					// this has a different post value otherwise firefox tries to pre-fill it.
					$smtp_password = '';
					
					if (isset($_POST['smtp_p'])) {
						$smtp_password = $_POST['smtp_p'];
					}
					
					$user->Set('smtppassword', $smtp_password);

					$error = false;

					if ($_POST['ss_p'] != '') {
						if ($_POST['ss_p_confirm'] != '' && $_POST['ss_p_confirm'] == $_POST['ss_p']) {
							$user->Set('password', $_POST['ss_p']);
						} else {
							$error = GetLang('PasswordsDontMatch');
						}
					}

					if (!$error) {
						if (!empty($_POST['permissions'])) {
							foreach ($_POST['permissions'] as $area => $p) {
								foreach ($p as $subarea => $k) {
									$user->GrantAccess($area, $subarea);
								}
							}
						}

						if (!empty($_POST['lists'])) {
							$user->GrantListAccess($_POST['lists']);
						}

						if (!empty($_POST['templates'])) {
							$user->GrantTemplateAccess($_POST['templates']);
						}

						if (!empty($_POST['segments'])) {
							$user->GrantSegmentAccess($_POST['segments']);
						}

						$GLOBALS['Message'] = '';

						if (!empty($warnings)) {
							$GLOBALS['Warning']  = implode('<br/>', $warnings);
							$GLOBALS['Message'] .= $this->ParseTemplate('WarningMsg', true, false);
						}

						$user->Set('gettingstarted', 0);
						$user->Set('groupid', (int) IEM_Request::getParam('groupid'));
						
						$result = $user->Create();
						
						if ($result == '-1') {
							FlashMessage(GetLang('UserNotCreated_License'), SS_FLASH_MSG_ERROR, IEM::urlFor('Users'));
							
							break;
						} else {
							if ($result) {
								FlashMessage(GetLang('UserCreated'), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Users'));
								
								break;
							} else {
								FlashMessage(GetLang('UserNotCreated'), SS_FLASH_MSG_ERROR, IEM::urlFor('Users'));
							}
						}
					} else {
						$GLOBALS['Error'] = $error;
					}
				} else {
					$GLOBALS['Error'] = GetLang('UserAlreadyExists');
				}
				
				$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);

				$details = array();
				
				foreach (array('FullName', 'EmailAddress', 'Status', 'AdminType', 'ListAdminType', 'SegmentAdminType', 'TemplateAdminType', 'InfoTips', 'forcedoubleoptin', 'forcespamcheck', 'smtpserver', 'smtpusername', 'smtpport') as $p => $area) {
					$lower          = strtolower($area);
					$val            = (isset($_POST[$lower])) ? $_POST[$lower] : '';
					$details[$area] = $val;
				}
				
				$this->PrintEditForm(0, $details);
			break;

			case 'edit':
				$userid = IEM::requestGetGET('UserID', 0, 'intval');
				
				if ($userid == 0) {
					$this->DenyAccess();
				}

				$this->PrintEditForm($userid);
			break;

			case 'sendpreviewdisplay':
				$this->PrintHeader(true);
				$this->SendTestPreviewDisplay('index.php?Page=Users&Action=SendPreview', 'self.parent.getSMTPPreviewParameters()');
				$this->PrintFooter(true);
			break;

			case 'testgooglecalendar':
				$status = array(
					'status' => false,
					'message' => ''
				);
				try {
					$details = array(
						'username' => $_REQUEST['gcusername'],
						'password' => $_REQUEST['gcpassword']
					);

					$this->GoogleCalendarAdd($details, true);

					$status['status'] = true;
					$status['message'] = GetLang('GooglecalendarTestSuccess');
				} catch (Exception $e) {
					$status['message'] = GetLang('GooglecalendarTestFailure');
				}

				print GetJSON($status);
			break;

			case 'sendpreview':
				$this->SendTestPreview();
			break;

			default:
				$this->PrintManageUsers();
			break;
		}

		if (!in_array($action, $this->PopupWindows)) {
			$this->PrintFooter();
		}
	}

	/**
	* PrintManageUsers
	* Prints a list of users to manage. If you are only allowed to manage your own account, only shows your account in the list. This allows you to edit, delete and so on.
	*
	* @see GetApi
	* @see GetPerPage
	* @see GetSortDetails
	* @see User_API::Admin
	* @see GetUsers
	* @see SetupPaging
	*
	* @return Void Prints out the list, doesn't return anything.
	*/
	function PrintManageUsers()
	{
		// ----- Sanitize and declare variables that is going to be used in this function
			$pageRecordPP		= 0;
			$pageCurrentIndex	= $this->GetCurrentPage();
			$pageSortInfo		= $this->GetSortDetails();

			$requestPreserveQuickSearch	= IEM::requestGetGET('PreserveQuickSearch', 0, 'intval');
			$requestSearch				= IEM::requestGetPOST('QuickSearchString', false);
			$requestGroupID				= IEM::requestGetGET('GroupID', 0, 'intval');

			$records			= array();
			$recordTotal		= 0;

			$api				= $this->GetApi('User');

			$currentUser		= IEM::getCurrentUser();

			$page = array(
				'messages'		=> GetFlashMessages(),
				'userreport'	=> '',
				'currentuserid'	=> $currentUser->userid
			);

			$permissions = array(
				'admin'				=> $currentUser->UserAdmin()
			);

			$groupInformation = array();
		// -----

		// Only admin/user admin able to view these pages
		if (!$currentUser->isAdmin()) {
			$this->DenyAccess();
		}

		$temp = ssk23twgezm2();
		if (is_array($temp) && isset($temp['message'])) {
			$page['userreport'] = $temp['message'];
		}

		if ($requestSearch === false && $requestPreserveQuickSearch) {
			$requestSearch = IEM::sessionGet('Users_Manage_QuickSearchString', '');
		} else {
			$requestSearch = trim($requestSearch);
			IEM::sessionSet('Users_Manage_QuickSearchString', $requestSearch);
		}

		// ----- Get "Record Per Page"
			if ($pageRecordPP == 0) {
				$pageRecordPP = $this->GetPerPage();
			}
		// -----

		$start = 0;
		if ($pageRecordPP != 'all') {
			$start = ($pageCurrentIndex - 1) * $pageRecordPP;
		}

		$recordTotal = $api->GetUsers(0, $pageSortInfo, true, $start, $pageRecordPP, $requestSearch, $requestGroupID);
		$records = $api->GetUsers(0, $pageSortInfo, false, $start, $pageRecordPP, $requestSearch, $requestGroupID);

		if (!empty($requestGroupID)) {
			$groupInformation = API_USERGROUPS::getRecordByID($requestGroupID);
		}

		for ($i = 0, $j = count($records); $i < $j; ++$i) {
			$records[$i]['processed_CreateDate'] = $this->PrintDate($records[$i]['createdate']);
			$records[$i]['processed_LastLoggedIn'] = ($records[$i]['lastloggedin'] ? $this->PrintDate($records[$i]['lastloggedin']) : '-');
		}

		// ----- Calculate pagination, this is using the older method of pagination
			$GLOBALS['PAGE'] = 'Users&PreserveQuickSearch=1' . (!empty($requestGroupID) ? "&GroupID={$requestGroupID}" : '');
			$GLOBALS['FormAction'] = 'Action=ProcessPaging&PreserveQuickSearch=1' . (!empty($requestGroupID) ? "&GroupID={$requestGroupID}" : '');

			$this->SetupPaging($recordTotal, $pageCurrentIndex, $pageRecordPP);
		// -----

		// ----- Print out HTML
			$tpl = GetTemplateSystem();
			$tpl->Assign('PAGE', $page);
			$tpl->Assign('records', $records);
			$tpl->Assign('permissions', $permissions);
			$tpl->Assign('quicksearchstring', $requestSearch);
			$tpl->Assign('groupInformation', $groupInformation);

			echo $tpl->ParseTemplate('Users', true);
		// -----

		return;
	}

	/**
	* PrintEditForm
	* Prints a form to edit a user. If you pass in a userid, it will load up that user and print their information. If you pass in the details array, it will prefill the form with that information (eg if you tried to create a user with a duplicate username). Also checks whether you are allowed to edit this user. If you are not an admin, you are only allowed to edit your own account.
	*
	* @param Int $userid Userid to load up.
	* @param Array $details Details to prefill the form with (in case there was a problem creating the user).
	*
	* @see User_API::Admin
	* @see User_API::Status
	* @see User_API::ListAdmin
	* @see User_API::EditOwnSettings
	* @see GetUser
	*
	* @return Void Returns nothing. If you don't have access to edit a particular user, it prints an error message and exits. Otherwise it prints the correct form (either edit-own or edit) and then exits.
	*/
	function PrintEditForm($userid = 0, $details = array())
	{
		$thisuser = IEM::getCurrentUser();
		if (!$thisuser->UserAdmin()) {
			if ($userid != $thisuser->userid) {
				$this->DenyAccess();
			}

			if (!$thisuser->EditOwnSettings()) {
				$this->DenyAccess();
			}
		}

		$user = $this->GetApi('User');

		$listapi = $this->GetApi('Lists');
		$all_lists = $listapi->GetLists(0, array('SortBy' => 'name', 'Direction' => 'asc'), false, 0, 0);

		$segmentapi = $this->GetApi('Segment');
		$all_segments = $segmentapi->GetSegments(array('SortBy' => 'segmentname', 'Direction' => 'asc'), false, 0, 'all');

		$templateapi = $this->GetApi('Templates');
		$all_templates = $templateapi->GetTemplates(0, array('SortBy' => 'name', 'Direction' => 'asc'), false, 0, 0);

		$all_groups = API_USERGROUPS::getRecords(false, false, 0, 0, 'groupname');

		$GLOBALS['CustomSmtpServer_Display'] = '0';

		$GLOBALS['XmlPath'] = SENDSTUDIO_APPLICATION_URL . '/xml.php';

		if ($userid > 0) {
			$user = GetUser($userid);
			if ($user->Get('userid') <= 0) {
				$GLOBALS['ErrorMessage'] = GetLang('UserDoesntExist');
				$this->DenyAccess();
				return;
			}
			$GLOBALS['UserID'] = $user->Get('userid');
			$GLOBALS['UserName'] = htmlspecialchars($user->Get('username'), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['FullName'] = htmlspecialchars($user->Get('fullname'), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['EmailAddress'] = htmlspecialchars($user->Get('emailaddress'), ENT_QUOTES, SENDSTUDIO_CHARSET);

			$activity = $user->GetEventActivityType();
			if (!is_array($activity)) {
				$activity = array();
			}
			$GLOBALS['EventActivityType'] = implode("\n", $activity);

			$GLOBALS['MaxLists'] = $user->group->limit_list;
			$GLOBALS['MaxEmails'] = $user->group->limit_totalemailslimit;
			$GLOBALS['PerMonth'] = $user->group->limit_emailspermonth;
			$GLOBALS['PerHour'] = $user->group->limit_hourlyemailsrate;


			$GLOBALS['DisplayMaxLists'] = '';
			if ($user->Get('maxlists') == 0) {
				$GLOBALS['LimitListsChecked'] = ' CHECKED';
				$GLOBALS['DisplayMaxLists'] = 'none';
			}

			$GLOBALS['DisplayEmailsPerHour'] = '';
			if ($user->Get('perhour') == 0) {
				$GLOBALS['LimitPerHourChecked'] = ' CHECKED';
				$GLOBALS['DisplayEmailsPerHour'] = 'none';
			}

			$GLOBALS['DisplayEmailsPerMonth'] = '';
			if ($user->Get('permonth') == 0) {
				$GLOBALS['LimitPerMonthChecked'] = ' CHECKED';
				$GLOBALS['DisplayEmailsPerMonth'] = 'none';
			}

			$GLOBALS['LimitMaximumEmailsChecked'] = ' CHECKED';
			$GLOBALS['DisplayEmailsMaxEmails'] = 'none';

			if (!$user->hasUnlimitedCredit()) {
				$GLOBALS['LimitMaximumEmailsChecked'] = '';
				$GLOBALS['DisplayEmailsMaxEmails'] = '';
			}

			if ($user->Get('usewysiwyg')) {
				$GLOBALS['UseWysiwyg'] = ' CHECKED';
				$GLOBALS['UseXHTMLDisplay'] = ' style="display:block;"';
			} else {
				$GLOBALS['UseXHTMLDisplay'] = ' style="display:none;"';
			}

			if ($user->Get('enableactivitylog')) {
				$GLOBALS['EnableActivityLog'] = ' CHECKED';
			} else {
				$GLOBALS['EnableActivityLog'] = '';
			}

			$GLOBALS['UseXHTMLCheckbox'] = $user->Get('usexhtml')? ' CHECKED' : '';

			$GLOBALS['Xmlapi'] = $user->Get('xmlapi')? ' CHECKED' : '';
			$GLOBALS['XMLTokenDisplay'] = ' style="display:none;"';

			if ($user->Get('xmlapi')) {
				$GLOBALS['XMLTokenDisplay'] = ' style="display:block;"';
			}
			$GLOBALS['XmlToken'] = htmlspecialchars($user->Get('xmltoken'), ENT_QUOTES, SENDSTUDIO_CHARSET);

			$GLOBALS['TextFooter'] = $user->Get('textfooter');
			$GLOBALS['HTMLFooter'] = $user->Get('htmlfooter');

			$GLOBALS['SmtpServer'] = $user->Get('smtpserver');
			$GLOBALS['SmtpUsername'] = $user->Get('smtpusername');
			$GLOBALS['SmtpPassword'] = $user->Get('smtppassword');
			$GLOBALS['SmtpPort'] = $user->Get('smtpport');

			if ($GLOBALS['SmtpServer']) {
				$GLOBALS['CustomSmtpServer_Display'] = '1';
			}

			$GLOBALS['googlecalendarusername'] = htmlspecialchars($user->Get('googlecalendarusername'), ENT_QUOTES, SENDSTUDIO_CHARSET);
			$GLOBALS['googlecalendarpassword'] = htmlspecialchars($user->Get('googlecalendarpassword'), ENT_QUOTES, SENDSTUDIO_CHARSET);

			$GLOBALS['FormAction'] = 'Action=Save&UserID=' . $user->userid;

			if (!$thisuser->UserAdmin()) {

				$smtp_access = $thisuser->HasAccess('User', 'SMTP');

				$GLOBALS['ShowSMTPInfo'] = 'none';
				$GLOBALS['DisplaySMTP'] = '0';

				if ($smtp_access) {
					$GLOBALS['ShowSMTPInfo'] = '';
				}

				if ($GLOBALS['SmtpServer']) {
					$GLOBALS['CustomSmtpServer_Display'] = '1';
					if ($smtp_access) {
						$GLOBALS['DisplaySMTP'] = '1';
					}
				}

				$this->ParseTemplate('User_Edit_Own');
				return;
			}

			$GLOBALS['StatusChecked'] = ($user->Status()) ? ' CHECKED' : '';

			$GLOBALS['ForceDoubleOptInChecked'] = ($user->Get('forcedoubleoptin')) ? ' CHECKED' : '';
			$GLOBALS['ForceSpamCheckChecked'] = ($user->Get('forcespamcheck')) ? ' CHECKED' : '';
			$GLOBALS['InfoTipsChecked'] = ($user->InfoTips()) ? ' CHECKED' : '';

			$editown = '';
			if ($user->UserAdmin()) {
				$editown = ' CHECKED';
			} else {
				if ($user->EditOwnSettings()) {
					$editown = ' CHECKED';
				}
			}
			$GLOBALS['EditOwnSettingsChecked'] = $editown;

			$timezone = $user->usertimezone;

			$GLOBALS['TimeZoneList'] = $this->TimeZoneList($timezone);

			$admintype = $user->AdminType();
			$listadmintype = $user->ListAdminType();
			$segmentadmintype = $user->SegmentAdminType();
			$templateadmintype = $user->TemplateAdminType();

			$admin = $user->Admin();
			$listadmin = $user->ListAdmin();
			$segmentadmin = $user->SegmentAdmin();
			$templateadmin = $user->TemplateAdmin();

			$permissions = $user->Get('permissions');
			$area_access = $user->Get('access');

			$GLOBALS['Heading'] = GetLang('EditUser');
			$GLOBALS['Help_Heading'] = GetLang('Help_EditUser');

			$GLOBALS['AdminNotifyEmailAddress'] = $user->Get('adminnotify_email');
			if (empty($GLOBALS['AdminNotifyEmailAddress'])) {
				$GLOBALS['AdminNotifyEmailAddress'] = constant('SENDSTUDIO_EMAIL_ADDRESS');
			}

			$GLOBALS['AdminNotifications_Send_Email'] = $user->Get('adminnotify_send_emailtext');
			if (empty($GLOBALS['AdminNotifications_Send_Email'])) {
				$GLOBALS['AdminNotifications_Send_Email'] = GetLang('AdminNotifications_Send_Email');
			}

			$GLOBALS['AdminNotifications_Import_Email'] = $user->Get('adminnotify_import_emailtext');
			if (empty($GLOBALS['AdminNotifications_Import_Email'])) {
				$GLOBALS['AdminNotifications_Import_Email'] = GetLang('AdminNotifications_Import_Email');
			}

			$GLOBALS['SendLimit'] = $user->Get('adminnotify_send_threshold');
			$GLOBALS['ImportLimit'] = $user->Get('adminnotify_import_threshold');

			if (empty($GLOBALS['SendLimit'])) {
				$GLOBALS['SendLimit'] = 1000;
			}
			if (empty($GLOBALS['ImportLimit'])) {
				$GLOBALS['ImportLimit'] = 1000;
			}

			$admin_flag = $user->Get('adminnotify_send_flag');
			if ($user->Get('adminnotify_send_flag') == 1) {
				$GLOBALS['AdminNotificationsSend'] = 'CHECKED';
				$GLOBALS['UseNotifySend'] = '';
			} else {
				$GLOBALS['UseNotifySend'] = "style=display:none;";
			}
			if ($user->Get('adminnotify_import_flag') == 1) {
				$GLOBALS['AdminNotificationsImport'] = 'CHECKED';
				$GLOBALS['UseNotifyImport'] = '';
			} else {
				$GLOBALS['UseNotifyImport'] = "style=display:none;";
			}

			$GLOBALS['SmtpPort'] = $user->Get('smtpport');


			// Log this to "User Activity Log"
			IEM::logUserActivity(IEM::urlFor('users', array('Action' => 'Edit', 'UserID' => $userid)), 'images/user.gif', $user->username);

		} else {
			$timezone = (isset($details['timezone'])) ? $details['timezone'] : SENDSTUDIO_SERVERTIMEZONE;
			$GLOBALS['TimeZoneList'] = $this->TimeZoneList($timezone);

			$activity = $thisuser->defaultEventActivityType;
			if (!is_array($activity)) {
				$activity = array();
			}
			$GLOBALS['EventActivityType'] = implode("\n", $activity);

			$GLOBALS['FormAction'] = 'Action=Create';

			if (!empty($details)) {
				foreach ($details as $area => $val) {
					$GLOBALS[$area] = $val;
				}
			}
			$GLOBALS['Heading'] = GetLang('CreateUser');
			$GLOBALS['Help_Heading'] = GetLang('Help_CreateUser');

			$listadmintype = 'c';
			$segmentadmintype = 'c';
			$admintype = 'c';
			$templateadmintype = 'c';

			$GLOBALS['DisplayMaxLists'] = 'none';
			$GLOBALS['DisplayEmailsPerHour'] = 'none';
			$GLOBALS['DisplayEmailsPerMonth'] = 'none';
			$GLOBALS['DisplayEmailsMaxEmails'] = 'none';

			$GLOBALS['MaxLists'] = '0';
			$GLOBALS['PerHour'] = '0';
			$GLOBALS['PerMonth'] = '0';
			$GLOBALS['MaxEmails'] = '0';

			$GLOBALS['StatusChecked'] = ' CHECKED';
			$GLOBALS['ForceDoubleOptInChecked'] = '';
			$GLOBALS['ForceSpamCheckChecked'] = '';
			$GLOBALS['InfoTipsChecked'] = ' CHECKED';
			$GLOBALS['EditOwnSettingsChecked'] = ' CHECKED';

			$GLOBALS['LimitListsChecked'] = ' CHECKED';
			$GLOBALS['LimitPerHourChecked'] = ' CHECKED';
			$GLOBALS['LimitPerMonthChecked'] = ' CHECKED';
			$GLOBALS['LimitMaximumEmailsChecked'] = ' CHECKED';

			$GLOBALS['UseWysiwyg'] = ' CHECKED';
			$GLOBALS['EnableLastViewed'] = '';
			$GLOBALS['UseXHTMLCheckbox'] = ' CHECKED';

			$GLOBALS['HTMLFooter'] = GetLang('Default_Global_HTML_Footer');
			$GLOBALS['TextFooter'] = GetLang('Default_Global_Text_Footer');

			$GLOBALS['EnableActivityLog'] = ' CHECKED';

			$GLOBALS['Xmlapi'] = '';
			$GLOBALS['XMLTokenDisplay'] = ' style="display:none;"';

			$admin = $listadmin = $segmentadmin = $templateadmin = false;
			$permissions = array();
			$area_access = array('lists' => array(), 'templates' => array(), 'segments' => array());

			$GLOBALS['AdminNotifyEmailAddress'] = constant('SENDSTUDIO_EMAIL_ADDRESS');
			$GLOBALS['UseNotifySend'] = "style=display:none;";
			$GLOBALS['UseNotifyImport'] = "style=display:none;";

			$GLOBALS['SendLimit'] = 1000;
			$GLOBALS['ImportLimit'] = 1000;
			$GLOBALS['AdminNotifications_Send_Email'] = GetLang('AdminNotifications_Send_Email');
			$GLOBALS['AdminNotifications_Import_Email'] = GetLang('AdminNotifications_Import_Email');

		}

		$agencyid = defined('IEM_SYSTEM_LICENSE_AGENCY') ? IEM_SYSTEM_LICENSE_AGENCY : '';
		$available_users = $user->AvailableUsers();

		$template = GetTemplateSystem();
        
		$template->Assign('UserID', $user->userid);
		$template->Assign('groupid', $user->groupid);
		$template->Assign('canChangeUserGroup', !$user->isLastAdmin());
		$template->Assign('AgencyEdition', get_agency_license_variables());
		$template->Assign('EditOwn', ($user->userid != 0 && $user->userid == $thisuser->userid));
		$template->Assign('TrialUser', $user->trialuser);
		$template->Assign('EditMode', !empty($user->userid));
		$template->Assign('AvailableNormalUsers', isset($available_users['normal']) ? $available_users['normal'] : 0);
		$template->Assign('AvailableTrialUsers', isset($available_users['trial']) ? $available_users['trial'] : 0);
		$template->Assign('AvailableGroups', $all_groups);
		$template->Assign('record_groupid', $user->groupid);
		$template->Assign('DefaultIdTab', IEM::requestGetPOST('id_tab_num', 1, 'intval'));
		$template->Assign('showSmtpInfo', (bool) $user->smtpserver);

		$template->ParseTemplate('User_Form');
	}

	/**
	* CheckUserSystem
	* Checks the user system to make sure that this particular user isn't the last user, last active user or last admin user. This just ensures a bit of system integrity.
	*
	* @param Int $userid Userid to check.
	* @param Array $to_check Which areas you want to check. This can be LastActiveUser, LastUser and/or LastAdminUser.
	*
	* @see GetUser
	* @see User_API::LastActiveUser
	* @see User_API::LastUser
	* @see User_API::LastAdminUser
	*
	* @return False|String Returns false if you are not the last 'X', else it returns an error message about why the user can't be edited/deleted.
	*/
	function CheckUserSystem($userid = 0, $to_check = array('LastActiveUser', 'LastUser', 'LastAdminUser'))
	{
		$return_error = false;

		$user_system = GetUser($userid);

		if (in_array('LastActiveUser', $to_check)) {
			if ($user_system->LastActiveUser()) {
				$return_error = GetLang('LastActiveUser');
			}
		}

		if (in_array('LastUser', $to_check)) {
			if (!$return_error && $user_system->LastUser()) {
				$return_error = GetLang('LastUser');
			}
		}

		if (in_array('LastAdminUser', $to_check)) {
			if (!$return_error && $user_system->LastAdminUser()) {
				$return_error = GetLang('LastAdminUser');
			}
		}

		return $return_error;
	}

	/**
	* DeleteUsers
	* Deletes a list of users from the database via the api. Each user is checked to make sure you're not going to accidentally delete your own account and that you're not going to delete the 'last' something (whether it's the last active user, admin user or other).
	* If you aren't an admin user, you can't do anything at all.
	*
	* @param integer[] $users An array of userid's to delete
	* @param boolean $deleteData Whether or not to delete data owned by user along
	*
	* @see GetUser
	* @see User_API::UserAdmin
	* @see DenyAccess
	* @see CheckUserSystem
	* @see PrintManageUsers
	*
	* @return Void Doesn't return anything. Works out the relevant message about who was/wasn't deleted and prints that out. Returns control to PrintManageUsers.
	*/
	function DeleteUsers($users = array(), $deleteData = false)
	{
		$thisuser = GetUser();
		if (!$thisuser->UserAdmin()) {
			$this->DenyAccess();
			return;
		}

		if (!is_array($users)) {
			$users = array($users);
		}

		$not_deleted_list = array();
		$not_deleted = $deleted = 0;
		foreach ($users as $p => $userid) {
			if ($userid == $thisuser->Get('userid')) {
				$not_deleted++;
				$not_deleted_list[$userid] = array('username' => $thisuser->Get('username'), 'reason' => GetLang('User_CantDeleteOwn'));
				continue;
			}

			$error = $this->CheckUserSystem($userid);
			if (!$error) {
				$result = API_USERS::deleteRecordByID($userid, $deleteData);

				if ($result) {
					$deleted++;
				} else {
					$not_deleted++;
					$user = GetUser($userid);
					if ($user instanceof User_API) {
						$not_deleted_list[$userid] = array('username' => $user->Get('username'), 'reason' => '');
					} else {
						$not_deleted_list[$userid] = array('username' => $userid, 'reason' => '');
					}
				}
			} else {
				$not_deleted++;
				$user = GetUser($userid);
				if ($user instanceof User_API) {
					$not_deleted_list[$userid] = array('username' => $user->Get('username'), 'reason' => $error);
				} else {
					$not_deleted_list[$userid] = array('username' => $userid, 'reason' => $error);
				}
			}
		}


		if ($not_deleted > 0) {
			foreach ($not_deleted_list as $uid => $details) {
				FlashMessage(sprintf(GetLang('UserDeleteFail'), htmlspecialchars($details['username'], ENT_QUOTES, SENDSTUDIO_CHARSET), htmlspecialchars($details['reason'], ENT_QUOTES, SENDSTUDIO_CHARSET)), SS_FLASH_MSG_ERROR);
			}
		}

		if ($deleted > 0) {
			if ($deleted == 1) {
				FlashMessage(GetLang('UserDeleteSuccess_One'), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Users'));
			} else {
				FlashMessage(sprintf(GetLang('UserDeleteSuccess_Many'), $this->FormatNumber($deleted)), SS_FLASH_MSG_SUCCESS, IEM::urlFor('Users'));
			}
		}

		IEM::redirectTo('Users');
	}
}
