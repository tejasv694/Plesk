<?php
/**
* Language file variables for the character-sets.
*
* @see GetLang
*
* @version     $Id: charactersets.php,v 1.3 2007/11/01 02:48:20 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage Language
*/

/**
* Here are all of the variables for the character sets.. Please backup before you start!
*
* If you do add to or remove from this list, you will need to edit the list below as well.
*/

define('LNG_UTF-8',         'Universal Alphabet (UTF-8)');

define('LNG_ISO-8859-1',   'Western Alphabet (ISO-8859-1)');

/**
* These variables below are used to display a list of character sets.
* They correspond to the language variables above.
* If you do add to the list above, you will need to add it to the list below as well.
*
* The order they appear below is the order they will appear inside for when you select the character set.
*/
$GLOBALS['SendStudioCharacterSets'] = array(
	'ISO-8859-1',
	'UTF-8',
);
