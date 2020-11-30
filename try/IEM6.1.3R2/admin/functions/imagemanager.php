<?php
/**
* This file handles adding, editing, deleting of images.
*
* @version     $Id: imagemanager.php,v 1.0 2008/12/11 16:33:37 David Exp $
* @author 		David <david.chandra@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for the processing images. Uses the API's to handle functionality, this simply handles processing and calls the API's to do the work.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class ImageManager extends SendStudio_Functions
{
	public $user = null;
	/**
	* Constructor
	* Load the language file of ImageManager
	*
	* @return Void
	*/
	function ImageManager() {
		$this->LoadLanguageFile('ImageManager');
	}

	/**
	* Process
	* Takes the appropriate action based on the action
	*
	* @return Void Doesn't return anything. Takes the appropriate action.
	*/
	function Process()
	{
		$GLOBALS['Message'] = '';

		if(is_null($this->user)){$this->user = IEM::userGetCurrent();}

		$action = (isset($_GET['Action'])) ? strtolower($_GET['Action']) : null;

		$type = (isset($_GET['Type'])) ? strtolower($_GET['Type']) : '';
		$typeId = (isset($_GET['TypeId'])) ? strtolower($_GET['TypeId']) : 0;
		
		//trying to get another users images? that shouldn't happen
		if($type == 'user' && $typeId != $this->user->userid){
			$GLOBALS['Message'] = 'Unable to load images for another user ID';
			exit();
		}

		if ($action == 'processpaging') {
			$this->SetPerPage($_GET['PerPageDisplay']);
			$action = 'manage';
		}

		switch ($action) {
			case 'remoteupload':
			$this->RemoteUpload($type, $typeId);
			break;
			case 'getimageslist':
			$this->GetImagesList($type, $typeId);
			break;
			case 'noflashupload':
			$this->NoFlashUpload();
			break;
			default:
			$this->PrintHeader();
			$this->ManageImages($this->user->userid);
		}

		$this->PrintFooter();
	}

	/**
	* ManageImages
	* Perform the action to display the UI for managing Images.
	*
	* @param Interger $userid The user id of current login user.
	*
	* @return Void Doesn't return anything. Display the template to the browser.
	*/
	function ManageImages () {
		$api = $this->GetApi();
		$settingApi = $this->GetApi('settings');
		$GLOBALS['adminUrl'] = SENDSTUDIO_APPLICATION_URL.'/admin';
		$GLOBALS['imgLocation'] = $api->GetImageDir();
		$params = '';
		foreach ($_GET as $k=>$v) {
			$params[] = $k.'='.$v;
		}
		if ($params) {
			$params = '?'.implode('&', $params);
		}

		$tpl = GetTemplateSystem();
		$tpl->Assign('SessionName', IEM::SESSION_NAME);
		$tpl->Assign('SessionID', IEM::sessionID());
		$tpl->Assign('Params', $params);
		echo $tpl->ParseTemplate('Image_Manage');
		die();
	}

	/**
	* GetImagesList
	* A function to generate a list of the available images for the editor displays
	*
	* @param String $type The type of the folder.
	* @param Interger $typeId The Id of the folder type.
	*
	* @return Void Doesn't return anything. Display the javascript content.
	*/
	function GetImagesList($type, $typeId)
	{
        $d_path = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $type. DIRECTORY_SEPARATOR . $typeId;
        if(!is_dir($d_path)){CreateDirectory($d_path,TEMP_DIRECTORY, 0777);}
        $remove_temp_dir = IEM::sessionGet($type.'_creation['.$this->user->Get('userid').']');
        if(empty($remove_temp_dir)){IEM::sessionSet($type.'_creation['.$this->user->Get('userid').']',true);}
    	$api = $this->GetApi();
		$api->Init('asc', 'name', $type, $typeId);
		$output = '';

		$output .= 'var tinyMCEImageList = new Array(';
		$outputArray = array();

		$imgDir = $api->GetImageDirFiles();
		foreach ($imgDir as $k=>$image){
			$outputArray[] = '["' . $image['name'] . '", "' . $image['url'] . '"]';
		}

		$output .= implode(",\n", $outputArray) . ');';

		header('Content-type: text/javascript');
		
		die($output);
	}

	/**
	* RemoteUpload
	* A function to upload the Image remotely
	*
	* @param String $type The type of the folder.
	* @param Interger $typeId The Id of the folder type.
	*
	* @return Void Doesn't return anything. Display the result content.
	*/
	function RemoteUpload($type, $typeId) {

		$api = $this->GetApi();
		$api->Init('asc', 'name', $type, $typeId);
		$_FILES['Filedata']['filesize'] = $this->NiceSize($_FILES['Filedata']['size']);
		$_FILES['Filedata']['id'] = substr(md5($_FILES['Filedata']['name']),0, 10);
		$_FILES['Filedata']['errorfile'] = false;

		if ($_FILES['Filedata']['error'] == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES["Filedata"]["tmp_name"];
			$name = $_FILES["Filedata"]["name"] = strtolower($_FILES["Filedata"]["name"]);

			if (file_exists($api->GetImagePath() . '/' . $name)) {
				$_FILES['Filedata']['duplicate'] = true;

                                //unset($_FILES['Filedata']['tmp_name']);

                                //echo json_encode($_FILES);
                                //return;

			} elseif (!$this->IsImageFile(strtolower($name))) {
				$_FILES['Filedata']['errorfile'] = 'badname';

			} else {
				$new_file_name = $api->GetImagePath() . '/' . $name;

				$_FILES['Filedata']['duplicate'] = false;
				if (@move_uploaded_file($tmp_name, $new_file_name)) {
					@chmod($new_file_name, 0666);

					if (!$this->IsValidImageFile($new_file_name, $_FILES['Filedata']['type'])) {
						$_FILES['Filedata']['errorfile'] = 'badtype';
						@unlink($new_file_name);
					}
				} else {
					$_FILES['Filedata']['errorfile'] = 'noupload';
				}
			}
		}

		list($width, $height) = @getimagesize($api->GetImagePath() . '/' . $name);
		$width = max((int)$width, 10);
		$height = max((int)$height, 10);
		$origWidth = $width;
		$origHeight = $height;

		$_FILES['Filedata']['origheight'] = $origHeight;
		$_FILES['Filedata']['origwidth'] = $origWidth;

		if($width > 200){
			$height = (200/$width) * $height;
			$width = 200;
		}

		if($height > 150){
			$width = (150/$height) * $width;
			$height = 150;
		}

		$_FILES['Filedata']['width'] = $width;
		$_FILES['Filedata']['height'] = $height;
		$_FILES['Filedata']['imagepath'] = $api->GetImageDir();

		unset($_FILES['Filedata']['tmp_name']);

		echo json_encode($_FILES);
		die();
	}

	/**
	* NoFlashUpload
	* A function to display the upload form for non flash upload
	*
	* @return Void Doesn't return anything. Display the template.
	*/
	function NoFlashUpload()
	{
		$tpl = GetTemplateSystem();
		echo $tpl->ParseTemplate('image_manager_multiupload', true);
		die();
	}
}
