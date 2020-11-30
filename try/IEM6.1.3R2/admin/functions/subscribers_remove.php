<?php
/**
* This file handles removing of subscribers in bulk.
*
* @version     $Id: subscribers_remove.php,v 1.20 2007/12/27 22:58:22 hendri Exp $
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
* Class for handling of subscriber removal. This class only handles removing subscribers.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Subscribers_Remove extends Subscribers
{

	/**
	* Process
	* Works out what you're trying to do and takes appropriate action.
	* Checks to make sure you have access to remove subscribers before anything else.
	*
	* @param String $action Action to perform. This is usually 'step1', 'step2', 'step3' etc. This gets passed in by the Subscribers::Process function.
	*
	* @see Subscribers::Process
	* @see GetUser
	* @see User_API::HasAccess
	* @see ChooseList
	* @see RemoveSubscriber_Step2
	* @see RemoveSubscribers
	*
	* @return Void Prints out the step, doesn't return anything.
	*/
	function Process($action=null)
	{
		$user = GetUser();
		$access = $user->HasAccess('Subscribers', 'Delete');

		$this->PrintHeader(false, false, false);

		if (!is_null($action)) {
			$action = strtolower($action);
		}

		if (!$access) {
			$this->DenyAccess();
		}

		switch ($action) {
			case 'step3':
				$listid = (isset($_POST['list'])) ? (int)$_POST['list'] : $_GET['list'];

				/**
				 * Check if user have access to the list
				 */
					$temp = $user->GetLists();
					if (!array($temp) || empty($temp)) {
						$this->DenyAccess();
						return;
					}

					$temp = array_keys($temp);
					if (!in_array($listid, $temp)) {
						$this->DenyAccess();
						return;
					}
				/**
				 * -----
				 */

				$removelist = array();
				$removetype = strtolower($_POST['RemoveOption']);
				if (!empty($_POST['RemoveEmailList'])) {
					$removelist = explode("\r\n", trim($_POST['RemoveEmailList']));
				}

				if (isset($_FILES['RemoveFile']) && $_FILES['RemoveFile']['tmp_name'] != 'none' && $_FILES['RemoveFile']['name'] != '') {
					$filename = TEMP_DIRECTORY . '/removelist.' . $user->userid . '.txt';
					if (is_uploaded_file($_FILES['RemoveFile']['tmp_name'])) {
						move_uploaded_file($_FILES['RemoveFile']['tmp_name'], $filename);
					} else {
						$GLOBALS['Error'] = sprintf(GetLang('UnableToOpenFile'), $_FILES['RemoveFile']['name']);
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->RemoveSubscriber_Step2($listid);
						break;
					}

					if (!$fp = fopen($filename, 'r')) {
						$GLOBALS['Error'] = sprintf(GetLang('UnableToOpenFile'), $_FILES['RemoveFile']['name']);
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->RemoveSubscriber_Step2($listid);
						break;
					}

					$data = fread($fp, filesize($filename));
					fclose($fp);
					unlink($filename);
					$data = str_replace("\r\n", "\n", $data);
					$data = str_replace("\r", "\n", $data);
					$emailaddresses = explode("\n", $data);

					if (empty($emailaddresses)) {
						$GLOBALS['Error'] = sprintf(GetLang('EmptyFile'), $_FILES['RemoveFile']['name']);
						$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
						$this->RemoveSubscriber_Step2($listid);
						break;
					}
					$removelist = $emailaddresses;
				}

				if (is_array($removelist)) {
					$removelist = array_unique($removelist);
				}

				if (empty($removelist)) {
					$GLOBALS['Error'] = GetLang('EmptyRemoveList');
					$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
					$this->RemoveSubscriber_Step2($listid);
					break;
				}

				// reset the session so it can be set up again next time GetLists is called.
				IEM::sessionRemove('UserLists');

				$this->RemoveSubscribers($listid, $removetype, $removelist);

			break;
			case 'step2':
				$listid = (isset($_POST['list'])) ? (int)$_POST['list'] : $_GET['list'];
				// ----- get jobs running for this user
				$db = IEM::getDatabase();
				$jobs_to_check = array();
				$query = "SELECT jobid FROM [|PREFIX|]jobs_lists WHERE listid = {$listid}";
				$result = $db->Query($query);
				if(!$result){
					trigger_error(mysql_error());
					FlashMessage(mysql_error(). "<br />Line: ".__LINE__, SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
					exit();
				}
				while($row = $db->Fetch($result)){
					$jobs_to_check[] = $row['jobid'];
				}
				$db->FreeResult($result);
				if(!empty($jobs_to_check)){
					$query = "SELECT jobstatus FROM [|PREFIX|]jobs WHERE jobid IN (" . implode(',', $jobs_to_check) . ")";	
					$result = $db->Query($query);
					if(!$result){
						trigger_error(mysql_error());
						FlashMessage(mysql_error(). "<br />Line: ".__LINE__, SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
						exit();
					}
					while($row = $db->Fetch($result)){
						if($row['jobstatus'] != 'c'){
							FlashMessage('Unable to delete contacts from list(s). Please cancel any campaigns sending to the list(s) in order to delete them.', SS_FLASH_MSG_ERROR, IEM::urlFor('Lists'));
							exit();
						}
					}
					$db->FreeResult($result);
				}
				// -----
				$this->RemoveSubscriber_Step2($listid);
			break;
			default:
				$this->ChooseList('Remove', 'Step2');
		}
	}

	/**
	* RemoveSubscribers
	* Removes subscribers from the list passed in. Depending on the type of 'removal' you want to perform, this calls the appropriate API method to do the work.
	*
	* @param Int $listid Listid to remove the subscribers from.
	* @param String $removetype What type of removal to perform. This can be 'inactive', 'unsubscribe' or 'delete'.
	* @param Array $removelist List of subscriber email addresses to remove.
	*
	* @see RemoveSubscriber_Step2
	* @see GetApi
	* @see Subscribers_API::Inactive
	* @see Subscribers_API::Unsubscribe
	* @see Subscribers_API::Delete
	*
	* @return Void Creates a report about what actions were performed and what couldn't be actioned (eg subscriber doesn't exist on a particular list) and prints out the report. Doesn't return anything.
	*/
	function RemoveSubscribers($listid=0, $removetype='unsubscribe', $removelist = array())
	{
		$user = GetUser();
		$access = $user->HasAccess('Subscribers', 'Manage');
		if (!$access) {
			$this->DenyAccess();
		}

		if (empty($removelist)) {
			$GLOBALS['Error'] = GetLang('EmptyRemoveList');
			$GLOBALS['Message'] = $this->ParseTemplate('ErrorMsg', true, false);
			$this->RemoveSubscriber_Step2($listid);
			return;
		}

		$valid_remove_types = array('unsubscribe', 'delete');
		if (!in_array($removetype, $valid_remove_types)) {
			$removetype = 'unsubscribe';
		}

		$removetype = ucwords($removetype) . 'Subscriber';

		$success = array();
		$fail = array('addresses' => array(), 'messages' => array());
		$subscriber_api = $this->GetApi('Subscribers');

		foreach ($removelist as $pos => $emailaddress) {
			if (!$emailaddress) {
				continue;
			}

			if ($removetype == 'UnsubscribeSubscriber' && $subscriber_api->IsUnSubscriber($emailaddress, $listid)) {
				$fail['addresses'][] = $emailaddress;
				$fail['messages'][] = sprintf(GetLang('SubscriberIsAlreadyUnsubscribed'), $emailaddress);
				continue;
			}

			list($status, $msg) = $subscriber_api->$removetype($emailaddress, $listid);
			if ($status) {
				$success[] = $emailaddress;
			} else {
				$fail['addresses'][] = $emailaddress;
				$fail['messages'][] = $msg;
			}
		}

		$report = '';

		if (!empty($success)) {
			if (sizeof($success) > 1) {
				$report .= $this->PrintSuccess('MassUnsubscribeSuccessful', $this->FormatNumber(sizeof($success)));
			} else {
				$report .= $this->PrintSuccess('MassUnsubscribeSuccessful_Single');
			}
		}

		if (!empty($fail['addresses'])) {
			$errormsg = GetLang('MassUnsubscribeFailed') . '<ul>';
			foreach ($fail['addresses'] as $pos => $email) {
				$errormsg .= '<li>' . $email . ' ( ' . $fail['messages'][$pos] . ')';
			}
			$errormsg .= '</ul>';
			$GLOBALS['Error'] = $errormsg;
			$report .= $this->ParseTemplate('ErrorMsg', true, false);
		}

		$GLOBALS['Message'] = $report;
		$this->RemoveSubscriber_Step2($listid);
		return;
	}

	/**
	* RemoveSubscriber_Step2
	* Checks you have access to a particular list and if you do, prints out the form for you to put in email addresses or upload a file.
	*
	* @param Int $listid Listid to check and remove subscribers from.
	*
	* @return Void Doesn't return anything. Simply prints out the form for you to upload or input subscribers to remove from the list.
	*/
	function RemoveSubscriber_Step2($listid=0)
	{
		$user = GetUser();
		$access = $user->HasAccess('Subscribers', 'Manage');
		if (!$access) {
			$this->DenyAccess();
		}

		$GLOBALS['list'] = $listid;
		$this->ParseTemplate('Subscribers_Remove_Step2');
	}
}
