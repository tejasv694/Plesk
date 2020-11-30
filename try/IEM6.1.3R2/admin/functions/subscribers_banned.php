<?php
/**
* This file has the banned subscriber functions in it. It handles adding banned subscribers, managing banned subscribers, removing banned subscribers.
*
* @version     $Id: subscribers_banned.php,v 1.22 2007/09/25 05:37:21 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
if (!defined('SENDSTUDIO_BASE_DIRECTORY')) {
	require_once(dirname(__FILE__) . '/sendstudio_functions.php');
}

/**
* Class for the banned subscriber page. Overrides the Subscribers Process function to handle all subfunctions for handling banned subscribers. The API does all of the work.
*
* @see Subscribers_API
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Subscribers_Banned extends Subscribers
{

	/**
	* Process
	* Works out what you're trying to do and takes the appropriate action.
	*
	* @param String $action The subaction or area you're working in.
	*
	* @see Print_Add_Form
	* @see BanSubscribers
	* @see ChooseList
	*
	* @return Void Doesn't return anything. Hands processing off to the other functions to handle.
	*/
	function Process($action=null)
	{

		if (!is_null($action)) {
			$action = strtolower($action);
		}

		$user = GetUser();

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = 'step2';
		}

		switch ($action) {
			case 'ban':
				// This can either a numeric: indicating the listid OR 'global' denoting "Global"
				$listid = $this->_getPOSTRequest('list', 0);

				if (!$this->_haveAccess($listid)) {
					$this->DenyAccess();
					break;
				}

				if (!empty($_POST['BannedEmailList'])) {
					$bannedlist = trim(str_replace(",", "\r\n", $_POST['BannedEmailList']));
					$bannedlist = explode("\r\n", $bannedlist);
				}

				if (isset($_FILES['BannedFile']) && $_FILES['BannedFile']['tmp_name'] != 'none' && $_FILES['BannedFile']['name'] != '') {
					$filename = TEMP_DIRECTORY . '/bannedlist.' . $user->userid . '.txt';
					if (is_uploaded_file($_FILES['BannedFile']['tmp_name'])) {
						move_uploaded_file($_FILES['BannedFile']['tmp_name'], $filename);
					} else {
						$GLOBALS['Error'] = sprintf(GetLang('UnableToOpenFile'), $_FILES['BannedFile']['name']);
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->Print_Add_Form();
						break;
					}
					if (!$fp = fopen($filename, 'r')) {
						$GLOBALS['Error'] = sprintf(GetLang('UnableToOpenFile'), $_FILES['BannedFile']['name']);
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->Print_Add_Form();
						break;
					}

					$data = fread($fp, filesize($filename));
					fclose($fp);
					unlink($filename);

					// convert newlines all to one format.
					// \r\n = windows
					// \r = mac
					// \n = unix
					$data = str_replace("\r\n", "\n", $data);
					$data = str_replace("\r", "\n", $data);
					$emailaddresses = explode("\n", $data);

					if (empty($emailaddresses)) {
						$GLOBALS['Error'] = sprintf(GetLang('EmptyFile'), $_FILES['BannedFile']['name']);
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->Print_Add_Form();
						break;
					}
					$bannedlist = $emailaddresses;
				}

				if (empty($bannedlist)) {
					$GLOBALS['Error'] = GetLang('EmptyBannedList');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->Print_Add_Form();
					break;
				}

				$this->BanSubscribers($listid, $bannedlist);
			break;

			case 'add':
				$this->Print_Add_Form();
			break;

			case 'step2':
				$list = null;
				if (isset($_POST['list'])) {
					$list = $_POST['list'];
				}

				if (isset($_GET['list'])) {
					$list = $_GET['list'];
				}

				if (!$this->_haveAccess($list)) {
					$this->ChooseList('banned', 'step2');
				} else {
					IEM::sessionRemove('Banned_Search_Subscribers');
					$this->ShowBannedList($list);
				}
			break;

			case 'delete':
				$list = $_GET['list'];
				$banlist = (isset($_POST['subscribers'])) ? $_POST['subscribers'] : array();
				if (isset($_GET['id'])) {
					$banlist = array((int)$_GET['id']);
				}

				if (!$this->_haveAccess($list)) {
					$this->DenyAccess();
					break;
				}

				$this->RemoveBans($banlist, $list);
			break;

			case 'edit':
				if (!isset($_GET['id']) || !isset($_GET['list'])) {
					$this->ChooseList('Banned', 'Step2');
					break;
				}
				$banid = (int)$_GET['id'];
				$list = $_GET['list'];

				if (!$this->_haveAccess($list)) {
					$this->DenyAccess();
					break;
				}

				$this->EditBan($banid, $list);
			break;

			case 'update':
				$banid = (int)$_GET['id'];
				$list = $_POST['list'];

				if (!$this->_haveAccess($list)) {
					$this->DenyAccess();
					break;
				}

				$subscriber_api = $this->GetApi('Subscribers');

				$info = array('emailaddress' => $_POST['BannedEmail'], 'list' => $list);
				list($updateresult, $msg) = $subscriber_api->UpdateBan($banid, $info);

				if (!$updateresult) {
					$GLOBALS['Error'] = sprintf(GetLang('SubscriberBan_UnableToUpdate'), $msg);
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
				} else {
					$GLOBALS['Message'] = $this->PrintSuccess('SubscriberBan_Updated', $msg);
				}
				$this->ShowBannedList($list);
			break;

			default:
				$this->ChooseList('banned', 'step2');
			break;
		}
	}

	/**
	* BanSubscribers
	* This talks to the API and bans subscribers as it needs to. It takes a listid (which list you're banning them from), and an array of email addresses to ban. It uses the API to ban the subscribers and then prints out a report.
	*
	* @param Int $listid List to ban the subscribers from.
	* @param Array $bannedlist An array of email addresses to ban from the list.
	*
	* @see Print_Add_Form
	* @see GetApi
	* @see Subscribers_API::AddBannedSubscriber
	*
	* @return Void Prints out the report, doesn't return anything.
	*/
	function BanSubscribers($listid=0, $bannedlist=array())
	{
		if (empty($bannedlist)) {
			$GLOBALS['Error'] = GetLang('EmptyBannedList');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->Print_Add_Form();
			return;
		}

		$success = array();
		$fail = array('addresses' => array(), 'messages' => array());
		$subscriber_api = $this->GetApi('Subscribers');

		$subscriber_api->Db->StartTransaction();

		foreach ($bannedlist as $pos => $emailaddress) {
			// skip empty ones.
			if (!$emailaddress) {
				continue;
			}
			if (!stristr($emailaddress, "@")){
			    $fail['addresses'][] = $emailaddress;
			    $fail['messages'][] = "Suppressions must contain the '@' symbol";
			    continue;
			}
			list($status, $msg) = $subscriber_api->AddBannedSubscriber($emailaddress, $listid);
			if (!$status) {
				$fail['addresses'][] = $emailaddress;
				$fail['messages'][] = $msg;
				continue;
			}
			$success[] = $emailaddress;
		}

		$subscriber_api->Db->CommitTransaction();

		$report = '';

		if (!empty($success)) {
			if (sizeof($success) > 1) {
				$report .= $this->PrintSuccess('MassBanSuccessful', $this->FormatNumber(sizeof($success)));
			} else {
				$report .= $this->PrintSuccess('MassBanSuccessful_Single');
			}
		}

		if (!empty($fail['addresses'])) {
			$errormsg = GetLang('MassBanFailed') . '<ul>';
			foreach ($fail['addresses'] as $pos => $email) {
				$errormsg .= '<li>' . $email . ' ( ' . $fail['messages'][$pos] . ')';
			}
			$errormsg .= '</ul>';
			$GLOBALS['Error'] = $errormsg;
			$report .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		$GLOBALS['Message'] = $report;
		$this->Print_Add_Form();
	}

	/**
	* Print_Add_Form
	* This lets you choose which list to add banned subscribers to. We don't use the 'ChooseList' function because we may want to add bans globally. If you are a list administrator (or higher), you can ban subscribers or domains globally and this affects all lists. If you are not a list administrator, you don't get this choice.
	*
	* @see User_API::GetLists
	* @see User::ListAdmin
	*
	* @return Void Prints out step 1 and nothing else.
	*/
	function Print_Add_Form()
	{
		$user = IEM::getCurrentUser();
		$lists = $user->GetLists();

		$listids = array_keys($lists);

		if (sizeof($listids) < 1) {
			if (!$user->HasAccess('Lists', 'Global')) {
				$this->ChooseList('Banned');
				return;
			}
		}

		$selectlist = '';

		if ($user->HasAccess('Lists', 'Global')) {
			$selectlist .= '<option value="global">' . GetLang('Subscribers_GlobalBan') . '</option>';
		}

		foreach ($lists as $listid => $listdetails) {
			$subscriber_count = '';
			if (isset($listdetails['subscribercount'])) {
				if ($listdetails['subscribercount'] == 1) {
					$subscriber_count = GetLang('Subscriber_Count_One');
				} else {
					$subscriber_count = sprintf(GetLang('Subscriber_Count_Many'), $this->FormatNumber($listdetails['subscribercount']));
				}
			}
			$selectlist .= '<option value="' . $listid . '">' . htmlspecialchars($listdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . $subscriber_count . '</option>';
		}

		$GLOBALS['SelectList'] = $selectlist;

		$this->ParseTemplate('Subscribers_Banned_Add');
	}

	/**
	* ShowBannedList
	* Shows a list of banned addresses for a particular list. It handles paging, sorting and so on.
	*
	* @param Mixed $listid The listid can either be an integer (if it's for a particular list), or if it's not a number it's for the "global" banned list.
	*
	* @see GetApi
	* @see User_API::ListAdmin
	* @see GetPerPage
	* @see GetCurrentPage
	* @see GetSortDetails
	* @see Subscriber_API::FetchBannedSubscribers
	*
	* @return Void Prints out the manage area, doesn't return anything.
	*/
	function ShowBannedList($listid=null)
	{
		$subscriber_api = $this->GetApi('Subscribers');

		$user = IEM::getCurrentUser();

		IEM::sessionRemove('ListBansCount');

		$search_details = array();
		$search_details['List'] = $listid;

		IEM::sessionSet('Banned_Search_Subscribers', $search_details);

		$banned_search_info = IEM::sessionGet('Banned_Search_Subscribers');

		if (!is_numeric($banned_search_info['List']) && strtolower($banned_search_info['List']) == 'global') {
			if (!$user->HasAccess('Lists', 'Global')) {
				$this->DenyAccess();
			}
		}

		$listname = '';
		if (is_numeric($banned_search_info['List'])) {
			$ListApi = $this->GetApi('Lists');
			$ListApi->Load($banned_search_info['List']);
			$listname = $ListApi->name;
		} else {
			$listname = GetLang('Subscribers_GlobalBan');
		}

		$GLOBALS['SubscribersBannedManage'] = sprintf(GetLang('SubscribersManageBanned'), htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$perpage = $this->GetPerPage();
		$pageid = $this->GetCurrentPage();

		$sortinfo = $this->GetSortDetails();

		$subscriber_list = $subscriber_api->FetchBannedSubscribers($pageid, $perpage, $banned_search_info, $sortinfo);

		$totalbans = $subscriber_list['count'];

		IEM::sessionSet('ListBansCount', $totalbans);

		if ($totalbans == 0) {
			IEM::sessionSet('EmptyBannedSubscriberMessage', $listname);
			$this->ChooseList('banned', 'step2');
			return;
		}

		unset($subscriber_list['count']);

		$GLOBALS['TotalSubscriberCount'] = $this->FormatNumber($totalbans);
		if ($totalbans == 1) {
			$GLOBALS['SubscribersReport'] = GetLang('Banned_Subscribers_FoundOne');
		} else {
			$GLOBALS['SubscribersReport'] = sprintf(GetLang('Banned_Subscribers_FoundMany'), $GLOBALS['TotalSubscriberCount']);
		}

		$DisplayPage = $pageid;
		$start = 0;
		if ($perpage != 'all') {
			$start = ($DisplayPage - 1) * $perpage;
		}

		$GLOBALS['PAGE'] = 'Subscribers&Action=Banned&SubAction=Step2&list=' . $banned_search_info['List'];
		$this->SetupPaging($totalbans, $DisplayPage, $perpage);
		$GLOBALS['FormAction'] = 'Action=Banned&SubAction=ProcessPaging&list=' . $banned_search_info['List'];
		$paging = $this->ParseTemplate('Paging', true, false);

		$GLOBALS['SubscribersManageBanned'] = sprintf(GetLang('SubscribersManageBanned'), htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));

		$GLOBALS['List'] = $banned_search_info['List'];

		$template = $this->ParseTemplate('Subscribers_Banned_Manage', true, false);

		$subscriberdetails = '';

		foreach ($subscriber_list['subscriberlist'] as $pos => $subscriberinfo) {
			$GLOBALS['Email'] = $subscriberinfo['emailaddress'];
			$GLOBALS['BanDate'] = $this->PrintDate($subscriberinfo['bandate']);

			$GLOBALS['BanID'] = $subscriberinfo['banid'];

			$GLOBALS['SubscriberAction'] = $this->ParseTemplate('Subscribers_Banned_Manage_EditLink', true, false);

			$GLOBALS['SubscriberAction'] .= $this->ParseTemplate('Subscribers_Banned_Manage_DeleteLink', true, false);

			$subscriberdetails .= $this->ParseTemplate('Subscribers_Banned_Manage_Row', true, false);
		}

		$template = str_replace('%%TPL_Subscribers_Banned_Manage_Row%%', $subscriberdetails, $template);
		$template = str_replace('%%TPL_Paging%%', $paging, $template);
		$template = str_replace('%%TPL_Paging_Bottom%%', $GLOBALS['PagingBottom'], $template);
		echo $template;
	}

	/**
	* RemoveBans
	* Removes an array of bans from the list passed in. It calls the API to do the actual removal, then prints out a report of actions taken.
	*
	* @param Array $banlist An array of bans to remove (their id's anyway). If it's not an array (it's a single ban to remove), it gets converted to an array for easy use.
	* @param Mixed $list List to remove the bans from. This can either be a numeric value (listid), or if it's 'global' it will cover the 'global' bans.
	*
	* @see GetApi
	* @see Subscriber_API::RemoveBannedSubscriber
	*
	* @return Void Prints out the report, doesn't return anything.
	*/
	function RemoveBans($banlist=array(), $list=null)
	{
		if (!is_array($banlist)) {
			$banlist = array($banlist);
		}

		$subscriber_api = $this->GetApi('Subscribers');

		$banned_search_info = IEM::sessionGet('Banned_Search_Subscribers');

		if (is_numeric($list)) {
			$ListApi = $this->GetApi('Lists');
			$ListApi->Load($banned_search_info['List']);
			$listname = $ListApi->name;
		} else {
			$listname = GetLang('Subscribers_GlobalBan');
		}

		$subscriber_api->Db->StartTransaction();

		$removed = 0; $notremoved = 0;
		foreach ($banlist as $pos => $banid) {
			list($status, $statusmsg) = $subscriber_api->RemoveBannedSubscriber($banid, $list);
			if ($status) {
				$removed++;
			} else {
				$notremoved++;
			}
		}

		$subscriber_api->Db->CommitTransaction();

		$msg = '';

		if ($notremoved > 0) {
			if ($notremoved == 1) {
				$GLOBALS['Error'] = sprintf(GetLang('Subscriber_Ban_NotDeleted_One'), htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));
			} else {
				$GLOBALS['Error'] = sprintf(GetLang('Subscriber_Ban_NotDeleted_Many'), $this->FormatNumber($notremoved), htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));
			}
			$msg .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		if ($removed > 0) {
			if ($removed == 1) {
				$msg .= $this->PrintSuccess('Subscriber_Ban_Deleted_One', htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));
			} else {
				$msg .= $this->PrintSuccess('Subscriber_Ban_Deleted_Many', $this->FormatNumber($removed), htmlspecialchars($listname, ENT_QUOTES, SENDSTUDIO_CHARSET));
			}
		}
		$GLOBALS['Message'] = $msg;
		$GLOBALS['List'] = $list;

		IEM::sessionSet('DeleteBannedSubscriberMessage', $msg);

		$banscount = IEM::sessionGet('ListBansCount');
		$newcount = $banscount - $removed;

		if ($newcount < 1) {
			IEM::redirectTo('Subscribers', array('Action' => 'Banned'));
			exit();
		}
		$this->ShowBannedList($list);
	}

	/**
	* EditBan
	* Edit a particular ban - either update it (change the banned email/domain) or change the list it applies to.
	*
	* @param Int $banid The banid to edit.
	* @param Mixed $list List to edit the ban for. This can either be a numeric value (listid), or if it's 'global' it will cover the 'global' bans.
	*
	* @see GetApi
	* @see Subscriber_API::LoadBan
	* @see User_API::GetLists
	* @see User_API::ListAdmin
	*
	* @return Void Prints out the report, doesn't return anything.
	*/
	function EditBan($banid=0, $list=null)
	{
		$subscriber_api = $this->GetApi('Subscribers');
		$ban = $subscriber_api->LoadBan($banid, $list);

		if (!$ban) {
			$this->DenyAccess(GetLang('BannedSubscriberDoesntExist'));
		}

		$user = IEM::getCurrentUser();
		$lists = $user->GetLists();

		$selectlist = '';

		if ($user->HasAccess('Lists', 'Global')) {
			$selected = '';
			if ($ban['list'] == 'g') {
				$selected = ' SELECTED';
			}

			$selectlist .= '<option value="global"' . $selected . '>' . GetLang('Subscribers_GlobalBan') . '</option>';
		}

		foreach ($lists as $listid => $listdetails) {
			$selected = '';
			if ($listid == $ban['list']) {
				$selected = ' SELECTED';
			}

			$selectlist .= '<option value="' . $listid . '"' . $selected . '>' . htmlspecialchars($listdetails['name'], ENT_QUOTES, SENDSTUDIO_CHARSET) . '</option>';
		}

		$GLOBALS['SelectList'] = $selectlist;

		$GLOBALS['BanID'] = $ban['banid'];
		$GLOBALS['BannedAddress'] = $ban['emailaddress'];

		$this->ParseTemplate('Subscribers_Banned_Edit');
	}

	/**
	 * _haveAccess
	 *
	 * Check whether or not current user have access to the banned list
	 *
	 * @param Mixed $listid ID of the list to be checked (This can either be integer for the list ID, or the word global to denote global suppression)
	 * @return Boolean Returns TRUE if user have access, FALSE otherwise
	 */
	function _haveAccess($listid)
	{
		$user = GetUser();

		/**
		 * Admin user can always access the suppression list.
		 * But regular user is NOT able to access "global" suppression list no matter what
		 */
		if ($user->Admin()) {
			return true;
		} elseif (!is_numeric($listid)) {
			return false;
		}

		$accessibleLists = $user->GetLists();
		if (!array($accessibleLists) || empty($accessibleLists)) {
			return false;
		}

		$accessibleLists = array_keys($accessibleLists);
		if (!in_array($listid, $accessibleLists)) {
			return false;
		}

		return true;
	}
}
