<?php

class Interspire_Validator_ImageFile extends Interspire_Validator_Abstract
{
	public
		$filepath,
		$filesize,
		$fileext,
		$filename;

	public $filetype = array();

	private $_validfiles = array ('gif', 'jpeg', 'jpg', 'png', 'swf', 'psd', 'bmp',
            'tiff', 'tiff', 'jpc', 'jp2', 'jpf', 'jb2', 'swc',
            'aiff', 'wbmp', 'xbm');



   public function image_type_to_extension($imagetype)
   {
       if(empty($imagetype)) return false;
       switch($imagetype)
       {
           case image_type_to_mime_type(IMAGETYPE_GIF)   : return 'gif';
           case image_type_to_mime_type(IMAGETYPE_JPEG)    : return 'jpg';
           case image_type_to_mime_type(IMAGETYPE_PNG)    : return 'png';
           case image_type_to_mime_type(IMAGETYPE_SWF)    : return 'swf';
           case image_type_to_mime_type(IMAGETYPE_PSD)    : return 'psd';
           case image_type_to_mime_type(IMAGETYPE_BMP)    : return 'bmp';
           case image_type_to_mime_type(IMAGETYPE_TIFF_II) : return 'tiff';
           case image_type_to_mime_type(IMAGETYPE_TIFF_MM) : return 'tiff';
           case 'image/pjpeg' : return 'jpg';
           default                : return false;
       }
   }


	public function __construct($filepath, $filesize, $filetype, $filename)
	{

		$this->filepath = $filepath;
		$this->fileext = $this->image_type_to_extension(strtolower($filetype));
		$this->filename = $filename;
		$this->filesize   = $filesize;
	}



	public function isValid()
	{
		if ($this->filename == "" || empty($this->filename))
		{
			return false;
		}

		if ($this->filesize == "" || $this->filesize == 0 )
		{
			return false;
		}

		// if the extension is not valid..
		if (!in_array($this->fileext, $this->_validfiles)) {
			return false;
		}

		return true;
	}
}