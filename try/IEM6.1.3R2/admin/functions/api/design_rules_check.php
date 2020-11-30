<?php

/**
* Include the base API class if we haven't already.
*/
require_once(dirname(__FILE__) . '/api.php');

/**
* Class for checking the spam rating of an email campaign.
*/
class Design_Rules_Check_API extends API
{
	/**
	* BrokenRules
	* This is a multi dimensional array that stores the rules that you have broken so that it can be displayed.
	*
	* @var Array
	*/
	var $BrokenRules = array();

	/**
	 * Design type
	 * @var Mixed
	 */
	var $design_type = null;

	/**
	 * Constructor
	 * @param Mixed $designtype Design type
	 * @return Void Does not return anything
	 */
	function Design_Rules_Check_API($designtype=false)
	{
		if ($designtype) {
			$this->Load($designtype);
		}
	}

	/**
	 * Load design rule files
	 *
	 * @param Mixed $designtype Design type
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	function Load($designtype=false)
	{
		if (!$designtype) {
			return false;
		}
		$filename = IEM_PATH . '/resources/design_rules/' . str_replace(' ', '', strtolower($designtype)) . '.php';

		if (!is_file($filename)) {
			return false;
		}

		require $filename;
		$this->design_type = $designtype;

		$this->BrokenRules = array();
		return true;
	}

	/**
	* Process
	* This will scan your emails content and use the reg expressions loaded to find and determine
	*
	* @return Void Doesn't return anything.
	*/
	function Process(&$content, $replace_content=false)
	{
		if (!$this->design_type) {
			return false;
		}

		if (!isset($GLOBALS['Design_Rules'][$this->design_type])) {
			return false;
		}

		foreach ($GLOBALS['Design_Rules'][$this->design_type] as $rule) {
			$matches_found = preg_match_all($rule['regular_expression'].'is', $content, $matches);

			if ($matches_found == 0) {
				continue;
			}

			$this->BrokenRules[] = $rule['description'];

			if (!$replace_content) {
				continue;
			}

			if (isset($rule['use_preg_replace']) && $rule['use_preg_replace'] == 1) {
				$content = preg_replace($rule['regular_expression'].'is', $rule['replacement'], $content);
			} else {
				// str_replace handles arrays as arguments so we dont need a loop here.
				$content = str_replace($matches[$rule['match_offset']], $rule['replacement'], $content);
				/*
				foreach ($matches[$rule['match_offset']] as $p => $match) {
					$content = str_replace($match, $rule['replacement'], $content);
				}
				*/
			}
		}

		return $content;
	}


	/**
	* GetBrokenRules
	* This will return an array of the broken rules
	*
	* @return returns Array BrokenRules.
	*/
	function GetBrokenRules()
	{
		return $this->BrokenRules;
	}

}
