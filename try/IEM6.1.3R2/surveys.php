<?php
/**
 * Survey page
 *
 * To render out a survey for the link provided for the user..
 */

//if (empty($_POST)) {
//	echo "Can't post an empty form<br/>";
//	exit();
//}

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

if (empty($_GET['id'])) {
	echo "Invalid Id";
	exit();
}

$surveyId = $_GET['id'];

/**
 * Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
 *
 */
require_once dirname(__FILE__) . '/admin/index.php';
require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';
require_once SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/api/surveys.php';


// now use the API...

$tpl = GetTemplateSystem('admin/addons/surveys/templates');
$survey_api = new Addons_survey_api();
$survey_api->getSurveyContent($surveyId, $tpl)


?>