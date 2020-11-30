<?php

if (!function_exists('stripslashes_deep')) {
	/**
	* Recursively use stripslashes on an array or a value
	* If magic_quotes_gpc is on, strip all the slashes it added.
	* By doing this we can be sure that all the gpc vars never have slashes and so
	* we will always need to treat them the same way
	*
	* @param Mixed $value The array or value to perform stripslashes on
	*
	* @return Mixed The array or value which was stripslashed
	*/
	function stripslashes_deep($value='')
	{
		if (!get_magic_quotes_gpc()) {
			return $value;
		}
		if (is_array($value)) {
			foreach ($value as $k=>$v) {
				$sk = stripslashes($k); // we may need to strip the key as well
				$sv = stripslashes_deep($v);
				if ($sk != $k) {
					unset($value[$k]);
				}
				$value[$sk] = $sv;
			}
		} else {
			$value = stripslashes($value);
		}
		return $value;
	}
	
	$_POST		= stripslashes_deep($_POST);
	$_GET		= stripslashes_deep($_GET);
	$_COOKIE	= stripslashes_deep($_COOKIE);
	$_REQUEST	= stripslashes_deep($_REQUEST);
}