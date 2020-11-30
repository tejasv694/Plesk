<?php

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

require_once dirname(__FILE__) . '/admin/index.php';
require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';
require_once SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/api/surveys.php';
require_once SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/api/responses.php';


if (empty($_GET['f'])) {
	exit("Invalid file");
} else {
		// Now processing request here..
		$files = $_GET['f'];
		$filespart = explode("_", $files, 3);

		if (count($filespart) < 3) {
			exit ("Invalid file");
		}

		$surveyId = $filespart[0];
		$responseId = $filespart[1];
		$file_name = base64_decode($filespart[2]);

		if (!is_numeric($responseId) || !is_numeric($surveyId)) {
			exit("Invalid file request");
		}

		$response_api = new Addons_survey_responses_api();
		$response_api->Load($responseId);


		$filename = $response_api->getRealFileValue($file_name);
		$upBaseDir = TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'surveys';
		$upSurveyDir = $upBaseDir . DIRECTORY_SEPARATOR . $surveyId;
		$upDir     = $upSurveyDir . DIRECTORY_SEPARATOR . $responseId;
		$filepath = $upDir . DIRECTORY_SEPARATOR . $filename;

		if (!file_exists($filepath)) {
			exit("file not exist");
		}

		// getting actual file
		header("Content-Disposition: attachment; filename=" . $file_name);
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Description: File Transfer");
		header("Content-Length: " . filesize($filepath));
		// flush();  this doesn't really matter.

		$fp = fopen($filepath, "r");
		while (!feof($fp))
		{
		    echo fread($fp, 3840);
		    flush(); // this is essential for large downloads
		}
		fclose($fp);
}
?>