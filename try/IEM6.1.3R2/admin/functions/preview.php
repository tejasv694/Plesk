<?php
/**
* This file handles previewing of templates, newsletters, autoresponders and so on.
*
* @version     $Id: preview.php,v 1.18 2008/01/22 03:14:20 hendri Exp $
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
* Class for handling previewing of items like templates, newsletters, autoresponders and forms.
*
* @package SendStudio
* @subpackage SendStudio_Functions
*/
class Preview extends SendStudio_Functions
{

	/**
	* Process
	* Prints out the preview frames.
	*
	* @return Void Prints out the frame previews, doesn't return anything.
	*/
	function Process()
	{
		$design_rule_directory = IEM_PATH . '/resources/design_rules/';
		$design_rule_files = list_files($design_rule_directory);

		foreach ($design_rule_files as $design_rule) {
			$filename_parts = pathinfo($design_rule);
			if (isset($filename_parts['extension']) && $filename_parts['extension'] == 'php') {
				require_once $design_rule_directory . $design_rule;
			}
		}

		$valid_design_rules = array_keys($GLOBALS['Design_Rules']);

		$action = '';
		if (isset($_GET['Action'])) {
			$action = strtolower($_GET['Action']);
		}

		$sync_key = IEM::requestGetGET('sync', false);

		if ($sync_key) {
			$details = IEM::sessionGet('PreviewWindowHash');
			$details = $details[$sync_key];
		} else {
			$details = IEM::sessionGet('PreviewWindow');
		}

		if (empty($details)) {
			return;
		}

		switch ($action) {
			case 'top':
				$GLOBALS['SwitchOptions'] = '';

				if ($details['format'] == 't' || $details['format'] == 'b') {
					$GLOBALS['SwitchOptions'] .= '<option value="text">' . GetLang('TextPreview') . '</option>';
					$GLOBALS['HideDescription'] = "'';";
					$GLOBALS['ShowDescription'] = "none;";
				}

				if ($details['format'] == 'h' || $details['format'] == 'b') {
					$GLOBALS['SwitchOptions'] .= '<option value="html" SELECTED>' . GetLang('HTMLPreview') . '</option>';
					$GLOBALS['ShowDescription'] = "'';";
					$GLOBALS['HideDescription'] = "none;";
				}

				if ($details['showBroken']) {
					$designrules_description = '<a href="#" onClick="javascript: changeDisplay(\'html\');">'.GetLang('OriginalHTMLVersion').'</a>';
					foreach ($valid_design_rules as $design_rule) {
						$designrules_description .= '&nbsp;|&nbsp;<a href="#" onClick="javascript: changeDisplay(\'' . $design_rule . '\');">'.
													'<img border="0" src="images/email_clients/'.str_replace(' ', '_', $design_rule).'.gif" />&nbsp;'.$design_rule.
													'</a>';
					}
					$GLOBALS['DesignRules_Description'] = $designrules_description;
				} else $GLOBALS['DesignRules_Description'] = '';

				$this->ParseTemplate('Preview_Window_TopFrame', false, false);
			break;

			case 'brokenrules':
				$content = '';
				if (isset($details['htmlcontent'])) {
					$content = $details['htmlcontent'];
				}

				$GLOBALS['BrokenRuleList'] = '';
				foreach ($valid_design_rules as $design_rule) {
					$GLOBALS['BrokenRuleList'] .= 	'<a name="broken_rule_'.$design_rule.'"></a>'.
													'<div class="designRule" rulename="'.$design_rule.'">'.
													'<div class="designRuleLoading"> '. sprintf(GetLang('DesignRules_LoadingRules'), $design_rule).
													'... &nbsp;<img src="images/loading.gif" alt="loading" />'.
													'</div>'.
													'</div>';
				}
				header('Content-type: text/html; charset="' . SENDSTUDIO_CHARSET . '"');
				$this->ParseTemplate('Design_Rules_Broken', false, false);
			break;

			case 'processeachrule':
				header('Content-type: text/html; charset="' . SENDSTUDIO_CHARSET . '"');
				$reqRuleName = isset($_POST['rulename'])? $_POST['rulename'] : null;
				if (in_array($reqRuleName, $valid_design_rules)) {
					$details = IEM::sessionGet('PreviewWindow');
					$contents = isset($details['htmlcontent'])? $details['htmlcontent'] : '';

					require_once(SENDSTUDIO_API_DIRECTORY . '/design_rules_check.php');
					$api = new Design_Rules_Check_API();
					$api->Load($reqRuleName);
					$api->Process($contents);

					$broken_rules = $api->GetBrokenRules();

					print	'<div class="designRuleName">'.
							'<img src="images/'.(empty($broken_rules)? 'success.gif' : 'error.gif').'" alt="'.(empty($broken_rules)? 'success' : 'failed').'" />&nbsp;'.$reqRuleName.
							'</div>';

					$response = '';
					if (!empty($broken_rules)) {
						$response .= '<ul>';
						foreach ($broken_rules as $i=>$broken_rule) {
							$className = (($i + 1) % 2 == 0)? 'even' : 'odd';
							$response .= '<li class="'.$className.'">'.$broken_rule.'</li>';
						}
						$response .= '</ul>';
					} else {
						$response .= '<div class="designRuleOK">'.GetLang('DesignRules_NoRulesBroken').'</div>';
					}

					print $response;
				}
			break;

			case 'display':
				$displaytype = 'html';

				if (isset($_GET['Type'])) {
					$displaytype = $_GET['Type'];
				}

				if ($displaytype != 'html' && $displaytype != 'text') {
					if (!in_array($displaytype, $valid_design_rules)) {
						$displaytype = 'html';
					}
				}

				if ($details['format'] == 't') {
					$displaytype = 'text';
				}

				if ($displaytype == 'html') {
					header('Content-type: text/html; charset=' . SENDSTUDIO_CHARSET);
					echo $details['htmlcontent'];
					exit;
				}

				if ($displaytype == 'text') {
					header('Content-type: text/html; charset=' . SENDSTUDIO_CHARSET);
					echo nl2br($details['textcontent']);
					exit;
				}

				require_once(SENDSTUDIO_API_DIRECTORY . '/design_rules_check.php');
				$api = new Design_Rules_Check_API($displaytype);
				$new_content = $api->Process($details['htmlcontent'], true);

				header('Content-type: text/html; charset=' . SENDSTUDIO_CHARSET);
				echo $new_content;
			break;
		}
	}
}
