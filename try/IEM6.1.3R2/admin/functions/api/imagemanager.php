<?php

/**
 * Image Manager API
 *
 * @version $Id$
 * @author Jordie <jordie@interspire.com>
 *
 * @package API
 * @subpackage Imagemanager_API
 */

/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* This class does image managing.
*
* @package API
* @subpackage Imagemanager_API
*/
class Imagemanager_API {

	/**
	 * imageDirectory
	 * The image directory
	 *
	 * @var String imageDirectory
	 */
	protected $imageDirectory = '';
	
	protected $userimageDirectory = '';

	/**
	 * dirObject
	 * The object of the image directory, to store all the information related to the image directory
	 *
	 * @var object dirObject
	 */
	protected $dirObject = null;

	/**
	 * start
	 * This used for pagination, the start of the images viewed
	 *
	 * @var Integer start
	 */
	public $start = null;
	
	
	public $user = null;

	/**
	 * finish
	 * This used for pagination, the start of the images viewed
	 *
	 * @var Integer finish
	 */
	public $finish = null;

	/**
	 * sortField
	 * The sort field of the current image sorting, default is sorted by name
	 *
	 * @var String sortField
	 */
	public $sortField = 'name';

	/**
	 * sortDirection
	 * The sort direction of the current image sorting
	 *
	 * @var String sortDirection
	 */
	public $sortDirection = 'asc';

	/**
	 * dirItems
	 * The object of the images, to store all the information related to the images
	 *
	 * @var object dirItems
	 */
	protected $dirItems = array();

	/**
	 * Instance
	 * This static variable holds the current instance of this object being loaded.
	 * So using the getInstance function anywhere will return the very same instance.
	 *
	 * @var object Instance
	 */
	public static $Instance;

	/**
	 * getInstance
	 * This is a static function that sets up the class instance and stores it to the static variable. It will then return that instantiation in the future.
	 *
	 * @return object Returns the instantiated object
	 **/
	public static function getInstance(){
		if(!isset(self::$Instance)){
			self::$Instance = new self();
		}
		return self::$Instance;
	}

	/**
	* Constructor
	* Initialize the image directory
	*
	* @return Void
	*/
	function __construct() {
		if(is_null($this->user)){$this->user = IEM::userGetCurrent();}
		$userid = $this->user->userid;
		$this->imageDirectory = '/user/'   . $userid;
		//added userimageDirectory as a permanent place holder for the user's folder -  imageDirectory will change inside Init
		$this->userimageDirectory = '/user/'   . $userid;
		//---
		if(!file_exists(TEMP_DIRECTORY  . $this->imageDirectory)){
			CreateDirectory(TEMP_DIRECTORY  . $this->imageDirectory, TEMP_DIRECTORY, 0777);
		}
	}

	/**
	* Init
	* Initialize Images on the directory and arrange the sorting fields.
	*
	* @return Void
	*/
	function Init($sortDirection='asc', $sortField='name', $type = '', $typeId = 0){

		if ($type != '') {
			$this->imageDirectory = '/'.$type.'/'.$typeId;
		}

		if($sortDirection == 'desc'){
			$this->sortDirection = 'desc';
		}

		if($sortField == 'size' || $sortField == 'modified'){
			$this->sortField = $sortField;
		}

		if(!empty($type) && strtolower($type) != 'user'){
			$this->dirObject = new DirectoryIterator(TEMP_DIRECTORY  . $this->userimageDirectory);
			foreach($this->dirObject as $dirItem){
				if($this->IsImageFile($dirItem)){
					list($width, $height) = @getimagesize($dirItem->getPathname());
					$width = max((int)$width, 10);
					$height = max((int)$height, 10);
					$origWidth = $width;
					$origHeight = $height;

					if($width > 200){
						$height = (200/$width) * $height;
						$width = 200;
					}

					if($height > 150){
						$width = (150/$height) * $width;
						$height = 150;
					}

					$this->dirItems[] = array(
					'id'=>substr(md5((string)$dirItem->getFilename()), 0 , 10),
					'url'=>SENDSTUDIO_APPLICATION_URL . '/admin/temp'   . $this->userimageDirectory . '/' . (string)$dirItem->getFilename(),
					'name'=>(string)$dirItem->getFilename(),
					/*	'displayname'=>$this->GetDisplayName((string)$dirItem->getFilename()),*/
					'size'=>(string)filesize($dirItem->getPathname()),
					'modified'=>(string)filemtime($dirItem->getPathname()),
					'height'=>(string)$height,
					'origheight'=>(string)$origHeight,
					'width'=>(string)$width,
					'origwidth'=>(string)$origWidth,
					);
				}
			}			
		}
                    $this->dirObject = new DirectoryIterator($this->GetImagePath());
                    foreach($this->dirObject as $dirItem){
                            if($this->IsImageFile($dirItem)){
                                    list($width, $height) = @getimagesize($dirItem->getPathname());
                                    $width = max((int)$width, 10);
                                    $height = max((int)$height, 10);
                                    $origWidth = $width;
                                    $origHeight = $height;

                                    if($width > 200){
                                            $height = (200/$width) * $height;
                                            $width = 200;
                                    }

                                    if($height > 150){
                                            $width = (150/$height) * $width;
                                            $height = 150;
                                    }

                                    $this->dirItems[] = array(
                                    'id'=>substr(md5((string)$dirItem->getFilename()), 0 , 10),
                                    'url'=>$this->GetImageDir() . (string)$dirItem->getFilename(),
                                    'name'=>(string)$dirItem->getFilename(),
                                    /*	'displayname'=>$this->GetDisplayName((string)$dirItem->getFilename()),*/
                                    'size'=>(string)filesize($dirItem->getPathname()),
                                    'modified'=>(string)filemtime($dirItem->getPathname()),
                                    'height'=>(string)$height,
                                    'origheight'=>(string)$origHeight,
                                    'width'=>(string)$width,
                                    'origwidth'=>(string)$origWidth,
                                    );
                            }
                    }                    
                	
                
		if($sortField == 'size' || $sortField == 'modified'){
			usort($this->dirItems, array($this, 'iem_imgcmpint'));
		}else{
			usort($this->dirItems, array($this, 'iem_imgcmpstr'));
		}
	}

	/**
	* GetDisplayName
	* Get the correct display name for the image.
	*
	* @param String $name The original name of the image
	*
	* @return String Return the correct image name with the correct length.
	*/
	public function GetDisplayName($name){
		if(strlen($name) < 25){
			return $name;
		}

		$first = substr($name, 0, 12);
		$last = substr($name, -12);
		return $first. '...'.$last;
	}

	/**
	* CountDirItems
	* Count the total number of images in the directory
	*
	* @return Integer Return the total number of images in the directory.
	*/
	public function CountDirItems() {
		return count($this->dirItems);
	}

	/**
	* GetImageDirFiles
	* Get the images objects according to the number of viewed.
	*
	* @return Mixed The array of the images objects.
	*/
	public function GetImageDirFiles(){
		if(is_null($this->start) ||is_null($this->finish)){
			return $this->dirItems;
		}

		$returnItems = array();

		for($i=$this->start;$i<$this->finish;++$i){
			if(isset( $this->dirItems[$i])){
				$returnItems[] = $this->dirItems[$i];
			}
		}

		return $returnItems;
	}

	/**
	* GetImagePath
	* Get the image directory path.
	*
	* @return String Return the directory path of images.
	*/
	public function GetImagePath(){
		return TEMP_DIRECTORY  . $this->imageDirectory;
	}

	/**
	* GetImageDir
	* Get the image URL path.
	*
	* @return String Return the URL path of images.
	*/
	public function GetImageDir(){
		return SENDSTUDIO_APPLICATION_URL . '/admin/temp'   . $this->imageDirectory . '/';
	}

	/**
	* IsImageFile
	* Check if the file is a correct image file type.
	*
	* @param String $fileName The name of the image file
	*
	* @return Boolean Return true if this is a valid image file name. Otherwise, it will return false.
	*/
	public function IsImageFile($fileName){
		if($fileName->isDir() || $fileName->isDot()){
			return false;
		}

		$validImages = array('png' , 'jpg', 'gif', 'jpeg' ,'tiff', 'bmp');
		foreach($validImages as $image){
			if(substr(strtolower($fileName), $this->neg(strlen($image))-1) == '.' . $image){
				return true;
			}
		}
		return false;
	}

	/**
	* iem_imgcmpstr
	* Comparing function for the images String attributes.
	*
	* @param String $a The first object for comparing
	* @param String $b The second object for comparing
	*
	* @return Boolean Return the comparison result.
	*/
	function iem_imgcmpstr($a, $b)
	{
		$return = strnatcmp(strtolower($a[$this->sortField]), strtolower($b[$this->sortField]));
		if($return === -1){
			$return = false;
		}else{
			$return = true;
		}
		if($this->sortDirection == 'desc'){
			return !$return;
		}
		return $return;
	}

	/**
	* iem_imgcmpint
	* Comparing function for the images Integer attributes.
	*
	* @param Integer $a The first object for comparing
	* @param Integer $b The second object for comparing
	*
	* @return Boolean Return the comparison result.
	*/
	function iem_imgcmpint($a, $b)
	{
		$return = false;
		if($a[$this->sortField] >= $b[$this->sortField]){
			$return = true;
		}

		if($this->sortDirection == 'desc'){
			return !$return;
		}
		return $return;
	}

	/**
	* neg
	* Get the negative value of a number
	*
	* @param Integer $num The original value of the Integer
	*
	* @return Integer the negative value of the original number.
	*/
	function neg($num){
		$num = (int)$num;
		return ($num - ($num*2));
	}

	/**
	* GetImageNumberShownText
	* Get the display text for the number of current images shown out of the total number of the images
	*
	* @return String The formatted string of detailed information of current viewed images.
	*/
	function GetImageNumberShownText() {
		return sprintf(GetLang('NumImageShown'), sizeof($this->GetImageDirFiles()), $this -> CountDirItems());
	}

}


