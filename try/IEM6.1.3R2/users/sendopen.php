<?php
/**
* This file is here for b/c reasons. It will only display the blank image, it will not record statistics.
*
* @version     $Id: sendopen.php,v 1.2 2007/03/26 08:08:29 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
*/

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

/**
* Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
*/
require_once dirname(__FILE__) . '/../admin/index.php';

DisplayImage();

/**
* DisplayImage
* Loads up the 'openimage' and displays it. It will exit after displaying the image.
*
* @return Void Doesn't return anything.
*/
function DisplayImage()
{
	// open the file in a binary mode
	$name = SENDSTUDIO_IMAGE_DIRECTORY . '/open.gif';
	$fp = fopen($name, 'rb');

	// send the right headers
	header("Content-Type: image/gif");
	header("Content-Length: " . filesize($name));

	// dump the picture and stop the script
	fpassthru($fp);
	exit(0);
}
