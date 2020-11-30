<?php
/**
* This file has the info tips page in it.
*
* @version     $Id: infotips.php,v 1.4 2006/08/18 04:07:44 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/

/**
* Include the base sendstudio functions.
*/
require_once(dirname(__FILE__) . '/sendstudio_functions.php');

/**
* Class for handling the info tips descriptions.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Infotips extends SendStudio_Functions
{

	/**
	* Constructor
	* Loads the language file.
	*
	* @see LoadLanguageFile
	*
	* @return Void Doesn't return anything.
	*/
	function Infotips()
	{
		$this->LoadLanguageFile();
	}

	/**
	* Process
	* Prints out the info tips list.
	*
	* @return Void Doesn't return anything.
	*/
	function Process()
	{
		$this->PrintHeader(true);

		$template = $this->ParseTemplate('InfoTips_List_Start', true, false);

		$tiplist = '';
		for ($tipid = 1; $tipid <= Infotip_Size; $tipid++) {
			$GLOBALS['Tip'] = $tipid;
			$GLOBALS['TipNumber'] = $tipid . '.';
			$GLOBALS['TipTitle'] = GetLang('Infotip_' . $tipid . '_Title');
			$GLOBALS['TipDetails'] = GetLang('Infotip_' . $tipid . '_Details');
			$tiplist .= $this->ParseTemplate('InfoTips_List_Row', true, false);
		}
		$template = str_replace('%%TPL_InfoTips_List_Row%%', $tiplist, $template);
		echo $template;
		$this->PrintFooter(true);
	}
}
