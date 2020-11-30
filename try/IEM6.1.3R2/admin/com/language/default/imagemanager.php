<?php
/**
* Language file variables for Image Manager. This includes creating, editing, deleting, updating, managing etc.
*
* @see GetLang
*
* @version     $Id: imagemanager.php,v 1.25 2008/02/18 06:33:38 David Exp $
* @author David <david.chandra@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the customfields area... Please backup before you start!
*/
define('LNG_ImageManagerManage', 'Image Manager');
define('LNG_Help_ImageManagerManage', 'Click the button below to upload one or more images from your computer. You can use the image by clicking the \'Insert Image\' button on the editor when creating content.');
define('LNG_DeleteSelected', 'Delete Selected');
define('LNG_ImageManagerUploadImages', 'Upload Images...');
define('LNG_ShowImageNumber', 'Show %s Images Per Page');
define('LNG_NumImageShown', '%s of %s Images Shown Below');
define('LNG_ShowAllImage', 'Show All Images');
define('LNG_NoImage', 'You haven\'t uploaded any images yet. Click the \'Upload Images...\' button below to upload some now.');

define('LNG_SortNameAsc', 'Name (A - Z)');
define('LNG_SortNameDesc', 'Name (Z - A)');
define('LNG_SortSizeAsc', 'File Size (Smallest First)');
define('LNG_SortSizeDesc', 'File Size (Largest First)');
define('LNG_SortBy', 'Sort by');

define('LNG_ImageManagerRenameInvalidFileName', 'The filename that was entered is not a valid filename.');
define('LNG_ImageManagerFileDoesntExistRename', 'The requested file does not exist to rename.');
define('LNG_ImageManagerRenameFileAlreadyExists', 'The destination file already exists.');

define('LNG_ImageManagerNoImagesSelectedDelete', 'No images were selected to be deleted.');
define('LNG_ImageManagerDeleteSuccessMulti', 'The %d selected images have been deleted successfully.');
define('LNG_ImageManagerDeleteSuccessSingle', 'The selected image has been deleted successfully.');
define('LNG_ImageManagerDeleteErrors', 'An error occured while trying to delete your selected images: ');

define('LNG_ImageManagerNotValidImage', 'The following images were not uploaded because they are not valid image files');
define('LNG_ImageManagerNotValidImageException', 'The following images were not uploaded because they are not valid image files (Any image not listed here was uploaded successfully)');
define('LNG_ImageManagerUploadDuplicate', 'All images were uploaded sucessfully with the exception of the following which were found to be duplicates. Please rename these files and try again.');
define('LNG_ImageManagerUploadSuccessSingle', 'The selected image has been uploaded and is shown below.');
define('LNG_ImageManagerUploadSuccessMultiple', 'The \' + FileCount + \' selected images have been uploaded and are shown below.');
define('LNG_ImageManagerRenameSuccess', 'Your file has been successfully renamed from &quot;%from%&quot; to &quot;%to%&quot;.');
define('LNG_ImageManagerRenameError', 'There was an error while trying to rename your file. ');
define('LNG_ImageManagerUnableDeleteError', 'Unable to delete');
define('LNG_DelThisImage', 'Delete this Image');
define('LNG_ImageManagerNoImageSelected', 'Please choose at least one image to delete first.');
define('LNG_ImageManagerDeleteConfirmation', 'Are you sure you want to delete the selected images? Click OK to confirm.');
define('LNG_ImageManagerViewFullSize', 'View Full Size');
define('LNG_ImageManagerImageUpload', 'Image Upload');
define('LNG_NoFlashImageUploadIntro', 'Click the browse button below to select an image to upload. Once you select an image more fields will become available to select additional images.');

/**
 * Added/Modified for IEM 5.7.3
 */
define('LNG_SortModifiedAsc', 'Date Modified (Oldest First)');
define('LNG_SortModifiedDesc', 'Date Modified (Newest First)');

/**
 * Added/Modified for IEM 5.7.4
 */
define('LNG_ClickForFullSize', 'Click here to view the full size image');
define('LNG_ImageSize', 'Size');
define('LNG_ImageDimension', 'Dimension');
