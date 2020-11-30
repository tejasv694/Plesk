<?php
/**
 * This file handles processing / uploading / importing of the newsletter urls and files. It does it 'ajax' style so the whole page doesn't need to reload.
 *
 * TODO: This file SHOULD NOT BE CALLED DIRECTLY from the request.
 * All request should be directed to index.php
 *
 * @version     $Id: remote.php,v 1.24 2008/02/20 20:20:17 tye Exp $
 * @author Chris <chris@interspire.com>
 *
 * @package SendStudio
 * @subpackage SendStudio_Functions
 */

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

/**
* Since we are calling this file differently, we need to include init ourselves and then include the base sendstudio functions.
*/
require_once (dirname(__FILE__) . '/../index.php');
require_once (dirname(__FILE__) . '/sendstudio_functions.php');

/**
* The class is called in this file (processing wouldn't work by passing it like other sendstudio pages).
* Doing it this way means easy access to all regular sendstudio functions and restrictions (eg userid's etc).
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class RemoteUpload extends SendStudio_Functions
{
	/**
	* RemoteUpload
	* Loads up the 'newsletters' language language file.
	*
	* @return Void Doesn't return anything.
	*/
	function RemoteUpload()
	{
		$this->LoadLanguageFile('Newsletters');
		$this->LoadLanguageFile('ImageManager');
	}

	/**
	* Process
	* This processes the ajax requests.
	* There are only two types of request - importfile and importurl.
	*
	* If it's importfile, it will display the 'fileupload' iframe again, and also process the file if there was one uploaded. It base 64 encodes the data to pass to javascript, this saves having to worry about newlines, quotes and so on. The javascript decodes it itself, then calls the DoImport function in the includes/js/javascript.js file.
	*
	* If it's importurl, it simply calls GetPageContents and returns that.
	*
	* @see GetPageContents
	*
	* @return Void Doesn't return anything, simply prints out the results.
	*/
	function Process()
	{
		// make sure they are logged in appropriately.
		if (!IEM::getCurrentUser()) {
			return;
		}

		$GLOBALS['ImportType'] = 'HTML';
		if (isset($_GET['ImportType']) && strtolower($_GET['ImportType']) == 'text') {
			$GLOBALS['ImportType'] = 'Text';
		}

		if (isset($_GET['DisplayFileUpload'])) {
			$this->ParseTemplate('Editor_FileUpload');
			return;
		}

		$user = GetUser();

		if (isset($_POST['what'])) {
			$what = $_POST['what'];

			switch (strtolower($what)) {
				case 'check_existing':
					// this is used when editing an autoresponder and you check the "send to existing" box.
					// it will alert you if you have sent this autoresponder to any recipients before
					// so you're aware that they will receive it again.

					$autoresponder_id = (isset($_POST['auto'])) ? (int)$_POST['auto'] : 0;
					if ($autoresponder_id <= 0) {
						exit;
					}

					$this->LoadLanguageFile('autoresponders');

					require_once(SENDSTUDIO_API_DIRECTORY . '/autoresponders.php');
					$auto_api = new Autoresponders_API();

					$userid = $user->userid;

					// If user is an admin, omit the userid so that it won't get checked
					if ($user->Admin()) {
						$userid = 0;
					}

					$already_sent_to = $auto_api->GetRecipientCount($autoresponder_id, $userid);
					if (!$already_sent_to) {
						exit;
					}

					if ($already_sent_to > 0) {
						$msg = sprintf(GetLang('AutoresponderAlreadySentTo'), $this->FormatNumber($already_sent_to));
						echo $msg;
					}
					exit;
				break;

				case 'importlinks':
					$listid = false;
					$processing_list = IEM::sessionGet('LinksForList');
					if ($processing_list) {
						$listid = (int)$processing_list;
					}

					$links = $user->GetAvailableLinks($listid);

					$link_list = 'mylinks[-1]=\'' . GetLang('FilterAnyLink') . '\';' . "\n";
					foreach ($links as $linkid => $url) {
						$link_list .= 'mylinks[' . $linkid . ']=\'' . addslashes($url) . '\';' . "\n";
					}
					echo $link_list;
				break;

				case 'importnewsletters':
					$listid = false;

					$processing_list = IEM::sessionGet('NewsForList');
					if ($processing_list) {
						$listid = (int)$processing_list;
					}

					$news = $user->GetAvailableNewsletters($listid);

					$news_list = 'mynews[-1]=\'' . GetLang('FilterAnyNewsletter') . '\';' . "\n";
					foreach ($news as $newsid => $name) {
						$news_list .= 'mynews[' . $newsid . ']=\'' . addslashes($name) . '\';' . "\n";
					}
					echo $news_list;
				break;

				case 'importfile':
					if (!empty($_FILES['newsletterfile'])) {
						if (is_uploaded_file($_FILES['newsletterfile']['tmp_name'])) {
							$page = file_get_contents($_FILES['newsletterfile']['tmp_name']);

							$page = self::ScrubPage($page);

							header('Content-type: text/html;');

							?>
							<script>
								parent.ajaxData = '<?php echo addcslashes($page,"'\\\n\r"); ?>';
								parent.DoImport('file', '<?php echo $GLOBALS['ImportType']; ?>');
							</script>
							<?php
						}
					}
					$this->ParseTemplate('Editor_FileUpload');
				break;

				case 'importurl':
					$url = false;
					if (isset($_POST['url'])) {
						$url = $_POST['url'];
					}
					list($page, $statusmsg) = $this->GetPageContents($url);
					if ($page) {
						// if there is a base href, don't worry about converting the links and images.
						// the email class does this when it sends the content.
						if (preg_match('%base href=%is', $page)) {
							echo $page;
							break;
						}

						$page = self::ScrubPage($page);

						/**
						* $url starts off as something like http://www.domain.com/path/index.html
						*
						* Grab the scheme & hostname from the url.
						*/
						$urlparts = parse_url($url);
						$baseurl = $urlparts['scheme'] . '://';
						$baseurl .= $urlparts['host'];

						/**
						* If there is a path (eg '/path/index.html'), break it up into sections.
						*
						* Then if there is an extension to the path, we assume it's a file (the extension in this case is 'html').
						*
						* So we need to take the basename of the file (/path) and add that to our url.
						*
						* If the url starts off as http://www.domain.com/path/
						* there will be no extension for '/path' so we assume it's a directory
						* So that means we have to add the 'basename' of the url ('/path') to the base url.
						*
						* If the url starts off as http://www.domain.com then there will be no path to worry about at all,
						* so we can skip that whole section
						*
						*/
						if (isset($urlparts['path'])) {
							$path_parts = pathinfo($urlparts['path']);
							$baseurl .= $path_parts['dirname'];

							if (!isset($path_parts['extension'])) {
								$baseurl .= $path_parts['basename'];
							}
						}

						// Remove trailing '\' from base URL
						$baseurl = preg_replace('/(%5c|\\\\)$/i', '', $baseurl);

						// make sure the baseurl always has a '/' on the end - ie we need to point to a directory not a file.
						if (substr($baseurl, -1) != '/') {
							$baseurl .= '/';
						}

						// Modified to parse HTML and find src and href, and convert it into an absolute resource link
						$pattern = '~(?<=src=["\']|href=["\']|link=["\']|background=["\']|url\()(?![a-z0-9]*?\://|\%\%|mailto\:|#|javascript\:|news\:)(.*?)(?=["\'])~i';
						$page = preg_replace($pattern, "{$baseurl}\$1", $page);

						/**
						* Clean up links that started out looking like
						* http://host/path/to/file.ext
						* and ended up looking like
						* http://host/path/to//path/to/file.ext
						*/
						if (isset($path_parts['dirname'])) {
							$path = $path_parts['dirname'];
							if (strlen($path) > 1) {
								$page = str_replace($path . '/' . $path, $path, $page);
							}
						}

						/**
						* Clean up the urls so they don't have double slashes or '/./' in them.
						*/
						$page = str_replace(array('/./', '//'), '/', $page);

						/**
						* However that breaks our scheme (http or https) so we need to re-fix those again.
						*/
						$page = str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $page);

						echo $page;
					}
				break;

				case 'save_version':
					// Only admin user can save "version"
					if (!$user->Admin()) {
						exit();
					}

					$lines = array();

					if (isset($_POST['latest'])) {
						$lines[] = 'latest=' . $_POST['latest'];
					}

					if (isset($_POST['feature'])) {
						$lines[] = 'feature=' . $_POST['feature'];
					}

					if (isset($_POST['latest_critical'])) {
						$lines[] = 'latest_critical=' . (int)$_POST['latest_critical'];
					}

					if (isset($_POST['feature_critical'])) {
						$lines[] = 'feature_critical=' . (int)$_POST['feature_critical'];
					}

					$fp = fopen(IEM_STORAGE_PATH . '/.version', 'w');
					if ($fp) {
						foreach ($lines as $line) {
							$line .= "\r\n";
							fputs($fp, $line);
						}
						fclose($fp);
					}
				break;

				case 'googlecalendar':
					$this->LoadLanguageFile('Subscribers');
					if (strlen($user->googlecalendarusername) && strlen($user->googlecalendarpassword)) {
						if (isset($_POST['google']) && is_array($_POST['google'])) {
							$google = $_POST['google'];
							$google['username'] = $user->googlecalendarusername;
							$google['password'] = $user->googlecalendarpassword;
							if (isset($google['allday']) && $google['allday']) {
								IEM::sessionSet('gcal_allday',true);
							} else {
								IEM::sessionSet('gcal_allday',false);
							}

							try {
								$this->GoogleCalendarAdd($google);
								echo 'top.tb_remove();';
							} catch (GoogleCalendarException $e) {
								switch ($e->getCode()) {
									case GoogleCalendarException::BADAUTH;
										echo 'alert("' . GetLang('GoogleCalendarAuth') . '");';
									break;
									default:
										echo 'alert("' . GetLang('GoogleCalendarException') . '");';
										echo "//" . $e->getMessage();
								}

							}
						}
					}
				break;
				case 'imagemanagerrename':
					$api = $this->GetApi('ImageManager');

					// lets get the extension from the old filename
					$ext = substr(strrchr($_POST['fromName'], "."), 0);
					$_POST['toName'] = $_POST['toName'] . $ext;

					$return = array();
					if(strpos($_POST['toName'], '/') !== false || strpos($_POST['toName'], '\\') !== false ){
						$return['success'] = false;
						$return['message'] = GetLang('ImageManagerRenameInvalidFileName');
						die(json_encode($return));
					}

					if(!$this->IsImageFile($_POST['toName'])){
						$return['success'] = false;
						$return['message'] = GetLang('ImageManagerRenameInvalidFileName');
						die(json_encode($return));
					}
					if(!file_exists($api->GetImagePath() . '/' . $_POST['fromName'])){
						$return['success'] = false;
						$return['message'] = GetLang('ImageManagerFileDoesntExistRename');
						die(json_encode($return));
					}

					if(file_exists($api->GetImagePath() . '/' . $_POST['toName'])){
						$return['success'] = false;
						$return['message'] = GetLang('ImageManagerRenameFileAlreadyExists');
						die(json_encode($return));
					}

					if(!@rename($api->GetImagePath() . '/' . $_POST['fromName'], $api->GetImagePath() . '/' . $_POST['toName'])){
						if(isset($php_errormsg)){
							$msgBits = explode(':', $php_errormsg);
							if(isset($msgBits[1])){
								$message =  $msgBits[1] . '.';
							}else{
								$message =  $php_errormsg  . '.';
							}
						}else{
							$message = 'Unknown error.';
						}
						$return['success'] = false;
						$return['message'] = $message;
						die(json_encode($return));
					}

					$return['success'] = true;
					$newName = $_POST['toName'];
					$newName = substr($newName, 0, strrpos($newName, "."));
					$return['newname'] = strtolower(htmlspecialchars($newName));
					$return['newrealname'] = strtolower(htmlspecialchars($_POST['toName']));
					$return['newurl'] = $api->GetImageDir() . urlencode(strtolower($_POST['toName']));
					echo json_encode($return);
					die();
				break;
				case 'imagemanagerdelete':

					$api = $this->GetApi('ImageManager');
					$successImages = $errorFiles = $return = array();

					if(!is_array($_POST['deleteimages']) || empty($_POST['deleteimages'])) {
						$return['success'] = false;
						$return['message'] = GetLang('ImageManagerNoImagesSelectedDelete');
						die(json_encode($return));
					}

					foreach($_POST['deleteimages'] as $k=>$image) {
						if(file_exists($api->GetImagePath() . '/' . $image)){
							if(!@unlink($api->GetImagePath() . '/' . $image)) {
								if(isset($php_errormsg)){
									$msgBits = explode(':', $php_errormsg);
									if(isset($msgBits[1])){
										$errorFiles =  $msgBits[1] .'.';
									}else{
										$errorFiles =  $php_errormsg  .'.';
									}
								}else{
									$errorFiles[] = GetLang('ImageManagerUnableDeleteError') . ' ' . $image;
								}
								unset($php_errormsg);
							}else{
								$ext = strrchr($image, '.');
								if($ext !== false) {
									$image = substr($image, 0, -strlen($ext));
								}
								$successImages[] = $image;
							}
						}
					}
					if(!empty($errorFiles)){
						$return['success'] = false;
						$return['message'] = GetLang('ImageManagerDeleteErrors') . $this->ArrayToList($errorFiles);
						die(json_encode($return));
					}

					$return['success'] = true;
					$return['successimages'] = $successImages;
					if(count($successImages) == 1){
						$return['message'] = GetLang('ImageManagerDeleteSuccessSingle');
					}elseif(count($successImages) > 1){
						$return['message'] = sprintf(GetLang('ImageManagerDeleteSuccessMulti'), count($successImages));
					}
					echo json_encode($return);
					die();
				break;
				case 'imagemanagerimagenumshown':
					$api = $this->GetApi('ImageManager');
					$api->Init();
					$return['text'] = $api->GetImageNumberShownText();
					echo json_encode($return);
				break;
				case 'imagemanagermanage':
					$api = $this->GetApi('ImageManager');
					$settingApi = $this->GetApi('settings');
					$GLOBALS['imgLocation'] = $api->GetImageDir();

					// Sorting of the images
					$validSort = array("name.asc", "name.desc", "modified.desc", "modified.asc", "size.asc", "size.desc");
					$sortby = '';
					if(isset($_GET['SortBy'])){
						$sortby = $_GET['SortBy'];
						$sortBits = explode('.', $sortby);
						$_GET['SortBy'] = $sortBits[0];
						$_GET['Direction'] = $sortBits[1];
					}

					$perpage = $this->GetPerPage();
					$DisplayPage = $this->GetCurrentPage();
					$start = 0;
					$sortinfo = $this->GetSortDetails();


					// if sorting field and direction is defined
					if (isset($sortinfo['Direction']) && isset($sortinfo['SortBy'])) {
						$sortby = $sortinfo['SortBy'].'.'.$sortinfo['Direction'];
					}

					// Default sorting field and direction
					if(empty($sortby) || !in_array($sortby, $validSort, true)){
						$sortby = 'name.asc';
						list($sortinfo['SortBy'], $sortinfo['Direction']) = explode('.', $sortby);
					}

					// Init the images sorting field and direction
					$api->Init($sortinfo['Direction'], $sortinfo['SortBy']);

					// Pagination setup
					$GLOBALS['SortList'] = '';
					foreach ($validSort as $eachSort) {
						$eachSortBits = explode('.', $eachSort);
						$displayText = GetLang('Sort'.ucwords($eachSortBits[0]).ucwords($eachSortBits[1]));
						$sel = '';
						if ($eachSort == $sortby) {
							$sel = ' SELECTED ';
						}
						$GLOBALS['SortList'] .= '<option value="'.$eachSort.'" '.$sel.'>' . $displayText . '</option> ';
					}
					if (strtolower($perpage) != 'all') {
						$api->start = ($perpage * $DisplayPage) - $perpage;
						$api->finish = ($perpage * $DisplayPage);
					}

					$NumberOfImages = ($api->CountDirItems())?$api->CountDirItems():1;

					$this->SetupPaging($NumberOfImages, $DisplayPage, $perpage);
					$GLOBALS['FormAction'] = 'Action=ProcessPaging';
					$paging = $this->ParseTemplate('Paging', true);
					$GLOBALS['dirImages'] = '';
					$dirImages = $api->GetImageDirFiles();

					$GLOBALS['Intro_Help'] = GetLang('Help_ImageManagerManage');
					$GLOBALS['Intro'] = GetLang('ImageManagerManage');
					$GLOBALS['NumImageShown'] = $api->GetImageNumberShownText();

					$GLOBALS['ImageManager_AddButton'] = '<input id="btnUpload" type="button" value="'.GetLang('ImageManagerUploadImages').'" class="SmallButton" />';
					$showDeleteBtn = "display:none";
					if ($api->CountDirItems()) {
						$showDeleteBtn = "";
						foreach ($dirImages as $dirImage) {
							$GLOBALS['dirImages'] .= "AdminImageManager.AddImage( '".$dirImage['name']."', '".$dirImage['url']."', '".$dirImage['size']." Bytes', '".$dirImage['width']."', '".$dirImage['height']."', '".$dirImage['origwidth']." X ".$dirImage['origheight']."', '".$dirImage['id']."'); ";
							$GLOBALS['DisplayImagePanel'] = 'block';
						}
					} else {
						$GLOBALS['DisplayImagePanel'] = 'none';
						$GLOBALS['Message'] = $GLOBALS['Message'] = $this->PrintSuccess('NoImage');
					}
					$GLOBALS['ImageManager_DeleteButton'] = '<input id="deleteButton" type="button" value="'.GetLang('DeleteSelected').'"  class="SmallButton" style="'.$showDeleteBtn.';" />';

					$tpl = GetTemplateSystem();
					$tpl->Assign('SessionName', IEM::SESSION_NAME);
					$tpl->Assign('Pagination', $paging);
					$tpl->Assign('SessionID', IEM::sessionID());
					echo $tpl->ParseTemplate('Image_Manager_Sub');
				break;
			}
		}
	}

	/**
	 * ScrubPage
	 * Removes certain content from an HTML document ready to be inserted into the WYSIWYG editor.
	 *
	 * @param String $page The page content.
	 *
	 * @return String The scrubbed page content.
	 */
	private static function ScrubPage($page)
	{
		// Scrub JavaScript as this can mess with the upload/import process and can cause problems with DE.
		return preg_replace('%<script[^>]*>.*?</script>%is', '', $page);
	}
}

/**
* We need to manually set up the object and process the request, because the ../remote.php file has to include this file. This saves worrying about paths/urls to this file.
*/
$RemoteUploader = new RemoteUpload();
$RemoteUploader->Process();
