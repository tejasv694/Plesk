<?php
/**
* Captcha Class
*
* This class generates, manages and outputs the Captcha images in order to prevent
* automated form submittion which causes SPAM.
*
* It detects if the server has GD running, if so it uses that, otherwise it
* uses static images to generate the Captcha Code.
* Based on the captcha class from ArticleLive, (c) Interspire
*
* @version 	$Id: captcha.php,v 1.9 2007/11/29 08:57:20 chris Exp $
* @author  	Jordie Bodlay <jordie@interspire.com>
* @package 	SendStudio
*/

/**
* Load up the base API class if we need to.
*/
require_once dirname(__FILE__) . '/api.php';

class Captcha_API extends API
{

	/**
	* Holds the secret captcha code.
	*
	* @var string
	*/
	var $__secret;

	/**
	* Determines the length of the code. Default is 6.
	*
	* @var integer
	*/
	var $length;

	/**
	* Contains the path to the TTF font file to be used for GD.
	*
	* @var string
	*/
	var $fontPath;

	/**
	* Contains the path to the list of letter images to be used
	* when GD isn't installed on the server.
	*
	* @var string
	*/
	var $imgDir;

	/**
	* Determines the size of the font when using GD
	*
	* @var integer
	*/
	var $fontSize;

	/**
	* Determines the color of the font when using GD
	*
	* @var hexidecimal
	*/
	var $textColor;

	/**
	* Determines one of the gradient values for the image background when
	* using GD
	*
	* @var html color value
	*/
	var $bgCol1;

	/**
	* Determines one of the gradient values for the image background when
	* using GD
	*
	* @var html color value
	*/
	var $bgCol2;

	/**
	* Determines the shape of the gradient fill in the image background when
	* using GD
	*
	* @var string
	*/
	var $bgFillStyle;

	/**
	* Whether we are generating this inside sendstudio or not.
	* This changes the image urls slightly to include or exclude the session id setting.
	* This stops us from getting logged out when we 'view' a form from inside the admin control panel.
	*/
	var $inside_sendstudio = false;

	/**
	* Whether we are generating this captcha-code for a modify details form or not.
	* This changes the image urls slightly to include or exclude the session id setting.
	*/
	var $modify_details = false;

	/**
	* Constructor
	*
	* Sets variables needed by the class
	*/
	function Captcha_API($inside_sendstudio=false)
	{
		// Detect if the server has GD installed or not
		// Set variables for later use
		if (function_exists('imageCreateFromPNG')) {
			$this->type = 'dynamic';
		}else{
			$this->type = 'static';
		}

		// all variables
		$this->length		= 6;

		// img captcha variables
		$this->imgDir = SENDSTUDIO_FORM_DESIGNS_DIRECTORY . '/captcha';

		// gd type captcha variables
		$this->fontPath		= SENDSTUDIO_FORM_DESIGNS_DIRECTORY . '/captcha/captcha.ttf';

		$this->fontSize		= '12';
		$this->textColor	= '000000';

		$this->inside_sendstudio = (bool)$inside_sendstudio;

		// $this->bgCol1		= '#ffffff';
		// $this->bgCol2		= '#ffffff';
		// $this->bgFillStyle	= '';
	}

	/**
	* CreateSecret
	*
	* Generates a new random secret captcha code
	*
	* @return true
	*/
	function CreateSecret()
	{
		if ($this->type == 'static') {

			$captcha_counter = (int)IEM::sessionGet('CaptchaCounter');

			if (!$captcha_counter || $captcha_counter < 1 || $captcha_counter >= $this->length) {
				$captcha_counter = 1;
			} else {
				$captcha_counter++;
			}

			IEM::sessionSet('CaptchaCounter', $captcha_counter);

			if ($captcha_counter == 1) {
				// get random characters, set the secret variable to it
				$this->__secret = $this->GetRandom($this->length);

				//set the session variable
				$this->SetSecret();
			}
			return true;
		}

		// get random characters, set the secret variable to it
		$this->__secret = $this->GetRandom($this->length);

		//set the session variable
		$this->SetSecret();
		return true;
	}

	/**
	* GetSecret
	*
	* Detects if there is already a secret saved, if so the function returns the secret
	* otherwise it generates a new one.
	*
	* @return string
	*/
	function GetSecret()
	{
		if (!isset($this->__secret) OR $this->__secret == '') {
		#	// if the secret is not already set, create it
			return $this->LoadSecret();
		}else{
			// otherwise return it
			return $this->__secret;
		}
	}

	/**
	* LoadSecret
	*
	* If the secret is stored in the Session, retrieve and decode it
	* Otherwise create a new secret.
	*
	* @return secret
	*/
	function LoadSecret()
	{
		$captchaCode = IEM::sessionGet('CaptchaCode');

		// if the secret stored in the session, retreive it
		// otherwise create a new secret
		if ($captchaCode) {
			$this->__secret = $captchaCode;
		}else{
			$this->CreateSecret();
		}
		return $this->__secret;
	}

	/**
	* SetSecret
	*
	* Sets the session variable to the current secret code
	*
	* @return unknown
	*/
	function SetSecret()
	{
		IEM::sessionRemove('CaptchaCode');

		$new_code = $this->GetSecret();

		// set new secret to the session
		IEM::sessionSet('CaptchaCode', $new_code);
	}

	/**
	* GetRandom
	*
	* Generates a string of random alphanumeric characters of a length
	* determined by $length
	*
	* @param integer $length
	* @return string
	*/
	function GetRandom($length=5)
	{
		// init
		$returnRandom = '';

		// make sure its an integer
		$length = (int)$length;

		$this->SeedRandom();
		$chars = array('a','b','c','d','e','f','g','h','j','k','m','n','p','q','r','s','t','u','v','w','x','y','z','2','3','4','5','6','7','8','9');
		for ($i=0; $i<$length; $i++) {
			$key = array_rand($chars);
			$returnRandom .= $chars[$key];
		}

		return $returnRandom;
	}

	function SeedRandom()
	{
		// If we are running php less than 4.2.0 we have to manually seed
		// mt_rand otherwise php does it for us
		if (version_compare(phpversion(), '4.2.0') < 0) {
			mt_srand($this->make_seed());
		}
	}

	function make_seed()
	{
		list($usec, $sec) = explode(' ', microtime());
		return (float) $sec + ((float) $usec * 100000);
	}


	/**
	* EncodeLetter
	*
	* Takes in 1 letter and outputs it in a jumbled string
	*
	* @param char $letter
	* @return string
	*/
	function EncodeLetter($letter)
	{
		// make sure we have 1 letter
		$letter = substr($letter,0,1);

		// we are going to hide the single letter in
		// a string of 15 characters, 10 before, 4 after.

		// get the first random 10
		$random = $this->GetRandom(10);

		// get the last random 4
		$random2 = $this->GetRandom(4);

		// put it all together
		$together = $random.$letter.$random2;

		// encode it for the session storage
		$together = base64_encode($together);

		return $together;
	}

	/**
	* LoadImage
	*
	* Outputs an image determined by $this->type
	*
	* @param char $letter
	* @return binary
	*/
	function LoadImage($letter='')
	{

		// if dynamic, use GD, otherwise open imagefile
		if ($this->type == 'dynamic') {
			// buffer everything so its all returned together
			ob_start();

			$img_handle = imageCreate(100,25);

			// sets background
			$bg = imagecolorallocate($img_handle, 255, 255, 255);
			imagefill($img_handle, 0, 0, $bg);

			// Make background transparent
			imagecolortransparent($img_handle, $bg);

			// grab text color
			$col = $this->hex2rgb($this->textColor);

			// set background
			// $this->gd_gradient_fill($img_handle, $this->bgFillStyle, $this->bgCol1, $this->bgCol2);

			// use the image value we set, if its not valid, default to black
			if ($col) {
				$text_color = ImageColorAllocate ($img_handle, $col['r'], $col['g'], $col['b']);
			} else {
				$text_color = ImageColorAllocate ($img_handle, 0, 0, 0);
			}

			// if the font-file exists then use it, otherwise, use the GD default text
			if (file_exists($this->fontPath) && function_exists("imagettftext")) {

				$length = strlen($this->__secret);
				for($i=0;$i<$length;$i++){
					$x = 10+rand(-2, 2);
					$x = $x + (12*($i));
					imagettftext($img_handle, $this->fontSize, rand(-4, 4), $x, 15+rand(-1, 1), $text_color, $this->fontPath, $this->__secret{$i});
				}
				#imagettftext($img_handle, $this->fontSize, rand(-4, 4), 10+rand(-3, 3), 15+rand(-3, 3), $text_color, $this->fontPath, $this->__secret);
			} else {
				ImageString ($img_handle, 5, 20, 5, $this->__secret, $text_color);
			}
			// create the image
			ImagePng ($img_handle);
			ImageDestroy ($img_handle);
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		}else{

			$filename = $this->imgDir.'/'.strtolower($letter).'.png';

			// check to see if the settings are correct
			if (!is_dir($this->imgDir)) {
				return false;
			}

			if (!is_file($filename)) {
				return false;
			}

			ob_start();
			// output image file
			readfile($filename);
			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		}
	}

	/**
	* OutputImage
	*
	* Outputs the image header, then the content of the image
	*
	*/
	function OutputImage()
	{
		$this->CreateSecret();

		// send several headers to make sure the image is not cached

		header('P3P: CP="NON NID CURa ADMa DEVa PSAa PSDa OUR IND UNI COM NAV"');

		// a date in the past
		header("Expires: Mon, 23 Jul 1993 05:00:00 GMT");

		// always modified
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

		// HTTP/1.1
		header("Cache-Control: no-store, no-cache, must-revalidate");

		header("Cache-Control: post-check=0, pre-check=0, max-age=0", false);

		header('Content-type: image/png');

		$this->LoadSecret();

		// check to see what action we need to take
		if ($this->type == 'dynamic') {
			echo $this->LoadImage();
		}else{
			$pos = 0;
			if (isset($_GET['c'])) {
				$pos = intval($_GET['c']);
			}
			$letter = substr($this->__secret, $pos, 1);
			echo $this->LoadImage($letter);
		}
		die();
	}

	/**
	* ShowCaptcha
	*
	* Returns the html img tags for the captcha image(s)
	*
	* @return string
	*/
	function ShowCaptcha()
	{
		$tpl = GetTemplateSystem();
		$tpl->Assign('captcha_baseurl', SENDSTUDIO_RESOURCES_URL . '/form_designs/captcha/index.php');
		$tpl->Assign('captcha_length', $this->length);
		$tpl->Assign('captcha_dynamic', ($this->type == 'dynamic'));
		$tpl->Assign('captcha_session', (!$this->inside_sendstudio && !$this->modify_details));

		return $tpl->ParseTemplate('captcha_code', true);
	}

	function hex2rgb($hex)
	{
		// If the first char is a # strip it off
		if (substr($hex, 0, 1) == '#') {
			$hex = substr($hex, 1);
		}

		// If the string isnt the right length return false
		if (strlen($hex) != 6) {
			return false;
		}

		$vals = array();
		$vals[]  = hexdec(substr($hex, 0, 2));
		$vals[]  = hexdec(substr($hex, 2, 2));
		$vals[]  = hexdec(substr($hex, 4, 2));
		$vals['r'] = $vals[0];
		$vals['g'] = $vals[1];
		$vals['b'] = $vals[2];
		return $vals;
	}

	function gd_gradient_fill($im,$direction,$start,$end)
	{
		switch ($direction) {
			case 'horizontal':
				$line_numbers = imagesx($im);
				$line_width = imagesy($im);
				list($r1,$g1,$b1) = $this->hex2rgb($start);
				list($r2,$g2,$b2) = $this->hex2rgb($end);
			break;

			case 'vertical':
				$line_numbers = imagesy($im);
				$line_width = imagesx($im);
				list($r1,$g1,$b1) = $this->hex2rgb($start);
				list($r2,$g2,$b2) = $this->hex2rgb($end);
			break;

			case 'ellipse':
			case 'circle':
				$line_numbers = sqrt(pow(imagesx($im),2)+pow(imagesy($im),2));
				$center_x = imagesx($im)/2;
				$center_y = imagesy($im)/2;
				list($r1,$g1,$b1) = $this->hex2rgb($end);
				list($r2,$g2,$b2) = $this->hex2rgb($start);
			break;

			case 'square':
			case 'rectangle':
				$width = imagesx($im);
				$height = imagesy($im);
				$line_numbers = max($width,$height)/2;
				list($r1,$g1,$b1) = $this->hex2rgb($end);
				list($r2,$g2,$b2) = $this->hex2rgb($start);
			break;

			case 'diamond':
				list($r1,$g1,$b1) = $this->hex2rgb($end);
				list($r2,$g2,$b2) = $this->hex2rgb($start);
				$width = imagesx($im);
				$height = imagesy($im);
				$rh=$height>$width?1:$width/$height;
				$rw=$width>$height?1:$height/$width;
				$line_numbers = min($width,$height);
				break;
			default:
				list($r,$g,$b) = $this->hex2rgb($start);
				$col = imagecolorallocate($im,$r,$g,$b);
				imagefill($im, 0, 0, $col);
				return true;
		}

		for ( $i = 0; $i < $line_numbers; $i=$i+1 ) {
			$r = ( $r2 - $r1 != 0 ) ? $r1 + ( $r2 - $r1 ) * ( $i / $line_numbers ) : $r1;
			$g = ( $g2 - $g1 != 0 ) ? $g1 + ( $g2 - $g1 ) * ( $i / $line_numbers ) : $g1;
			$b = ( $b2 - $b1 != 0 ) ? $b1 + ( $b2 - $b1 ) * ( $i / $line_numbers ) : $b1;
			$fill = imagecolorallocate( $im, $r, $g, $b );
			switch ($direction) {
				case 'vertical':
						imageline( $im, 0, $i, $line_width, $i, $fill );
					break;

				case 'horizontal':
						imageline( $im, $i, 0, $i, $line_width, $fill );
					break;

				case 'ellipse':
				case 'circle':
						imagefilledellipse ($im,$center_x, $center_y, $line_numbers-$i, $line_numbers-$i,$fill);
					break;

				case 'square':
				case 'rectangle':
					imagefilledrectangle ($im,$i*$width/$height,$i*$height/$width,$width-($i*$width/$height), $height-($i*$height/$width),$fill);
				break;

				case 'diamond':
					imagefilledpolygon($im, array (
					$width/2, $i*$rw-0.5*$height,
					$i*$rh-0.5*$width, $height/2,
					$width/2,1.5*$height-$i*$rw,
					1.5*$width-$i*$rh, $height/2 ), 4, $fill);
				break;

				default:
			}
		}
	}

}
