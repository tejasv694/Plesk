<?php
/**
* This file has some functions relating to utf8 handling
* including checking whether a string contains any non-utf8 valid characters,
* and getting the length of a utf-8 string.
*
* @package Library
* @subpackage General
*/

/**
* Check if a string is a valid utf-8 string or not
*
* @param String $str The string to check
*
* @return Boolean If it's a valid utf-8 string
*/
function utf8_isvalid($str)
{
	/*
	From rfc3629
	UTF8-octets = *( UTF8-char )
	UTF8-char   = UTF8-1 / UTF8-2 / UTF8-3 / UTF8-4
	UTF8-1      = %x00-7F
	UTF8-2      = %xC2-DF UTF8-tail

	UTF8-3      = %xE0 %xA0-BF UTF8-tail / %xE1-EC 2( UTF8-tail ) /
				 %xED %x80-9F UTF8-tail / %xEE-EF 2( UTF8-tail )
	UTF8-4      = %xF0 %x90-BF 2( UTF8-tail ) / %xF1-F3 3( UTF8-tail ) /
				 %xF4 %x80-8F 2( UTF8-tail )
	UTF8-tail   = %x80-BF
	*/
	$is_utf8 = preg_match(
	'/^([^'.
	'[\x00-\x7F]'.
	'|[\xC2-\xDF][\x80-\xBF]'.
	'|\xE0[\xA0-\xBF][\x80-\xBF]'.
	'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'.
	'|\xED[\x80-\x9F][\x80-\xBF]'.
	'|\xF0[\x90-\xBF][\x80-\xBF]{2}'.
	'|[\xF1-\xF3][\x80-\xBF]{3}'.
	'|\xF4[\x80-\x8F][\x80-\xBF]{2}'.
	'])*$/', $str);
	return !(bool) $is_utf8;
}

/**
* Returns the number of characters in the utf8 encoded string
* or null if the string contains any invalid characters
*
* @param String $str The string to check
* @param String $offset The offset to check the length of the next character from (defaults to 0)
*
* @return Mixed The length of the valid utf-8 char from offset or null if the next character is invalid
*/
function utf8_charlength($str, $offset=0)
{
	if (strlen($str{$offset}) == 0) {
		return 0;
	}

	$ord = ord($str{$offset});

	if ($ord <= 0x7F) {
		return 1;
	} elseif ($ord >= 0xC2 && $ord <= 0xDF) {
		return 2;
	} elseif ($ord >= 0xE0 && $ord <= 0xEF) {
		return 3;
	} elseif ($ord >= 0xF0 && $ord <= 0xF4) {
		return 4;
	} else {
		return null;
	}
}

/**
* Returns the number of characters in a utf-8 encoded string or null if the
* string has any invalid characters in it
*
* @param String $str String to get the length of.
*
* @uses utf8_isvalid
* @uses utf8_charlength
*
* @return Mixed The length of the valid utf-8 string or null if the string contains any invalid characters
*/
function utf8_strlen($str)
{
	if (!utf8_isvalid($str)) {
		return null;
	}

	$charnum = 0;
	$offset = 0;
	$len = strlen($str);

	while ($offset < $len) {
		$charlen = utf8_charlength($str, $offset);

		if (is_null($charlen)) {
			$offset++;
			continue;
		}

		$charnum++;
		$offset += $charlen;

		// Just to make sure we arn't reading past the end of the string
		if ($offset >= $len) {
			break;
		}
	}

	return $charnum;
}

/**
* Returns the subset of the string from $start for $len characters or null if
* the string contains any invalid characters
*
* @param String $string The string to take the substring of
* @param Integer $start The character offset to start from
* @param Integer $length The number of characters from offset to return
*
* @uses utf8_isvalid
* @uses utf8_charlength
*
* @return Mixed The substring or null if there are any invalid characters
*/
function utf8_substr($string, $start, $length=null)
{
	if ($length===0 || strlen($string) == 0) {
		return '';
	}

	if (!utf8_isvalid($string)) {
		return null;
	}

	$buffer='';
	$charnum = 0;
	$offset = 0;
	$len = strlen($string);

	// If they want the whole string from a certain point, just set the length
	// to the length of the string (since we break out once we are done anyway
	if ($length === null) {
		$length = $len;
	}

	while ($offset < $len) {
		$charlen = utf8_charlength($string, $offset);

		if (is_null($charlen)) {
			$offset++;
			continue;
		}

		// If the character is one we want add it to the buffer
		if ($charnum >= $start && $charnum < $start + $length) {
			$buffer .= substr($string, $offset, $charlen);
		}

		$charnum++;
		$offset += $charlen;

		// Just to make sure we arn't reading past the end of the string
		if ($offset >= $len) {
			break;
		}
	}
	return $buffer;
}

?>
