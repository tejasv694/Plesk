<?php

/**
 * @package    Interspire
 * @subpackage String
 */

/**
 * Interspire string methods for string manipulation.
 */
class Interspire_String
{
	/**
	 * Camelcases the input string removing any non-alphanumeric characters. Can
	 * also make the first letter uppercase as well as convert directory separators
	 * to underscores:
	 * 
	 * IWP/String                      : IWP_String
	 * camel_--case/this\string&for*me : camelCaseThisStringForMe
	 * Path/To/Class                   : Path_To_Class
	 * 
	 * etc.
	 * 
	 * @return string
	 * @param  string $str
	 * @param  bool   $ucFirst Whether or not to uppercase the first character. The first character is 
	 *                         is automatically converted to uppercase if a forward slash or back slash
	 *                         is present in the string.
	 */
	static public function camelCase($str, $ucFirst = false)
	{
		// in case the string was passed in from the uri
		$str = urldecode($str);
		
		// normalize the string
		$str = str_replace(array('\\', DIRECTORY_SEPARATOR), '/', $str);
		$str = trim($str, '/');
		
		// if a forward slash is in the string, auto-ucfirst
		$autoUcFirst = strpos($str, '/') !== false;
		
		// split into parts for parsing
		$parts = explode('/', $str);
		
		foreach ($parts as $k => $v) {
			$subParts = preg_split('/[^a-zA-Z0-9]/', $v);
			
			foreach ($subParts as $kk => $vv) {
				$subParts[$kk] = ucfirst($vv);
			}
			
			$parts[$k] = implode('', $subParts);
		}
		
		$str = implode('_', $parts);
		
		if ($autoUcFirst || $ucFirst) {
			$str = ucfirst($str);
		} else {
			$str{0} = strtolower($str{0});
		}
		
		return $str;
	}
	
	/**
	 * Makes the first letter of the string lowercase.
	 * 
	 * @return String
	 * @param  $str
	 */
	static public function lcFirst($str)
	{
		$str{0} = strtolower($str{0});
		
		return $str;
	}
	
	/**
	 * ucfirst is a function in PHP, but this was created to follow a good convention and 
	 * give IWP_String::lcFirst a counterpart.
	 * 
	 * @return String
	 * @param  $str
	 */
	static public function ucFirst($str)
	{
		return ucfirst($str);
	}
}