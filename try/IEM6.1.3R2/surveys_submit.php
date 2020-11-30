<?php
/**
 * Survey page
 *
 * @author Fredrick Gabelmann <fredrick.gabelmann@interspire.com>
 *
 * To render out a survey for the link provided for the user..
 */

// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
	define('IEM_NO_CONTROLLER', true);
}

/**
* Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
*/
require_once dirname(__FILE__) . '/admin/index.php';
require_once SENDSTUDIO_FUNCTION_DIRECTORY . '/sendstudio_functions.php';
require_once SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/api/surveys.php';
require_once SENDSTUDIO_BASE_DIRECTORY . '/addons/interspire_addons.php';
require_once SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/language/language.php';

class surveySubmit extends Interspire_Addons {

	/**
	 * api
	 * Holds reference to surveys api
	 */
	static public $api;

	public function __construct() {
		$this->_handleSubmitAction();
	}

	public function getApi()
	{
		if (!self::$api instanceof Addons_survey_api) {
			if (!class_exists('Addons_survey_api', false)) {
				require(SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/api/surveys.php');
			}
			self::$api = new Addons_survey_api();
		}

		return self::$api;
	}

	/**
	 * Redirects back to the http referer.s
	 *
	 * @return Void
	 */
	private function redirectToReferer()
	{
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		exit;
	}

	/**
	 * getSpecificApi
	 *
	 * @param $api string apiname
	 * Will call the specific api specified in the parametersthe files
	 * need to exist under the addon directory
	 * Will return false if no file is found.
	 *
	 * @return the API object
	 */

	public function getSpecificApi($api)
	{
		$className = 'Addons_survey_' . $api . '_api';
		if (!class_exists($className, false)) {
				if (file_exists(SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/api/' . $api . '.php')) {
					require_once(SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/api/' . $api . '.php');
				} else {
					return false;
				}
		}
		$instance = new $className;
		return $instance;
	}

	/**
	 * _validateIsBlank
	 * @param $str the string
	 * @param $trim whether or not to trim the str
	 *
	 * Handles the form submission from all feedback forms on the front end.
	 *
	 * @return boolean true when its blank, false otherwise
	 **/
	private function _validateIsBlank($str,$trim=true)
	{
		if ($trim) { $str = trim($str); }
		if(strlen($str) === 0 || $str === null || strtolower($str) == "null" || $str === "0" || $str === 0){
			return true;
		}else{
			return false;
		}
	}

	private function _handleSubmitAction()
	{
		// don't escape
		$template_dir = SENDSTUDIO_BASE_DIRECTORY . '/addons/surveys/templates';
		$this->_template = 	 GetTemplateSystem($template_dir);

		$this->_template->DefaultHtmlEscape = false;

		$formId      = (int) IEM::requestGetGET('formId');
		$postWidgets = IEM::requestGetPOST('widget');

		// If there are files, take the values and place them in the $postWidgets array so they can
		// get validated and entered into the response values in the same manner. Uploads will be
		// handled separately.

		if (isset($_FILES['widget'])) {
			foreach ($_FILES['widget']['name'] as $widgetId => $widget) {
				foreach ($widget as $fields) {
					foreach ($fields as $fieldId => $field) {
						$postWidgets[$widgetId]['field'][$fieldId]['value'] = 'file_' . $field['value'];
					}
				}
			}
		}

		// If the form and widgets weren't posted in the format we require then redirect back
		if (!$formId) {
			$this->redirectToReferer();
		}

		$surveyApi = $this->getApi();
		$surveyApi->Load($formId);
		$surveyData = $surveyApi->GetData();

		$errors       = 0;
		$widgets      = $surveyApi->getWidgets($formId);
		$widgetErrors = array();


		/****  START OF ERROR VALIDATION ****/

		// compile a list of widget ids so we can check the posted widgets against a list of
		// valid widget ids


		foreach ($widgets as $widgetKey => $widget) {

			if (!isset($widgetErrors[$widget['id']])) {
				$widgetErrors[$widget['id']] = array();
			}

			// validate required fields
			if ($widget['is_required']) {
				// the widget is assumed blank until one of it's fields is found not blank
				$isBlank = true;
				$isOther = false;


				// make sure the required widget was even posted

				if (isset($postWidgets[$widget['id']])) {
					foreach ($postWidgets[$widget['id']]['field'] as $field) {
						if (isset($field['value'])) {
							$values = (array) $field['value'];

							foreach ($values as $value) {

								// get the value of an "other" field if it is one, otherwise just grab
								// the normal value
								if ($value == '__other__') {
									$isOther = true;
									$value   = $field['other'];
								}

								// make sure the value isn't blank
								if ($this->_validateIsBlank($value) !== true) {
									$isBlank = false;
								}
							}
						}
					}
				}

				// if the widget is blank, flag an error
				if ($isBlank) {
					if ($isOther) {
						$error = GetLang('Addon_Surveys_ErrorRequiredOther');
					} else {
						$error = GetLang('Addon_Surveys_ErrorRequired');
					}
					$widgetErrors[$widget['id']][] = $error;
					$errors++;
				}
				
				if ($widget['type'] == 'file') {
					foreach ($postWidgets[$widget['id']]['field'] as $fieldid) {
						if (isset($fieldid['value'])) {$uploaded_file = $fieldid['value'];break;}
					}
					if (empty($uploaded_file) || $uploaded_file == "file_") {
						$error = GetLang('Addon_Surveys_ErrorRequired');
						$widgetErrors[$widget['id']][] = $error;
						$errors++;
					}					
				}
			}




			// validate file types
			if ($widget['type'] == 'file') {
				
				if (!empty($widget['allowed_file_types'])) {
					$typeArr     = preg_split('/\s*,\s*/', strtolower($widget['allowed_file_types']));
					$invalidType = false;


					// foreach of the passed fields (most likely 1) check and see if they are valid file types
					foreach ($postWidgets[$widget['id']]['field'] as $field) {
						$parts = explode('.', $field['value']);
						$ext   = strtolower(end($parts));



						// only if the field has a value we will test its file type
						if (trim($field['value']) != '' && !in_array($ext, $typeArr)) {
							$invalidType = true;
						}
					}

					// if the a file is not a valid file type, then the whole widget fails validation
					if ($invalidType) {
						$lastFileType   = '<em>.' . array_pop($typeArr) . '</em>';
						$firstFileTypes = '<em>.' . implode('</em>, <em>.', $typeArr) . '</em>';
						$widgetErrors[$widget['id']][] = sprintf(GetLang('Addon_Surveys_ErrorInvalidFileType'), $lastFileType, $firstFileTypes);
						$errors++;
					}
				}
			}

			if (isset($postWidgets[$widget['id']])) {
				// add a value to the values array so it can be passed to the email feedback template
				@$widgets[$widgetKey]['values'] = $postWidgets[$widget['id']]['field'];
			}
		}

		// if there were errors, redirect back and display the errors
		if ($errors) {
			// set a global error message to alert the user to the specific errors
			IEM::sessionSet('survey.addon.' . $formId . '.errorMessage', $surveyData['error_message']);
			// set the widget errors so we can retrieve them for the user
			IEM::sessionSet('survey.addon.' . $formId . '.widgetErrors', $widgetErrors);
			$this->redirectToReferer();
		}

		/****  END OF ERROR VALIDATION ****/

		// isntantiate a new response object
		$response = $this->getSpecificApi('responses');

		// associate the response to a particular form
		$response->surveys_id = $formId;

		// if the response was saved, then associate values to the response
		if ($response->Save()) {
			// foreach of the posted widgets, check to see if it belongs in this form and save it if it does

			foreach ($postWidgets as $postWidgetId => $postWidget) {
				// iterate through each field and enter it in the feedback

				foreach ($postWidget['field'] as $field) {
					// make sure it has a value first

					if (isset($field['value'])) {
						// since multiple values can be given, we treat them as an array
						$values = (array) $field['value'];

						foreach ($values as $value) {

							$responseValue = $this->getSpecificApi('responsesvalue');
							// foreign key for the response id
							$responseValue->surveys_response_id = $response->GetId();

							// set the widget id foreign key; widgets can have multiple field values and
							// should be treated as such
							$responseValue->surveys_widgets_id =  $postWidgetId;

							// set the value of the feedback; this should be a single value since widgets
							// can have multiple feed back values
							if ($value == '__other__') {
								$responseValue->value =  $field['other'];
								$responseValue->is_othervalue = 1;
							} else {
								// if file value exist we need to save the md5 name of the file in the database
								$responseValue->file_value = "";
								if (substr($value, 0, 5) == "file_") {
									$value = str_replace("file_", "", $value);
									$responseValue->file_value = md5($value);
								}

								$responseValue->value = $value;
								$responseValue->is_othervalue = 0;
							}

							// save it
							$responseValue->Save();
						}

					}
				}
			}

			// send an email if desired
			/**
			 *  Prepare for sending the email..
			 */

			$widget_api = $this->getSpecificApi('widgets');

			if ($surveyData['email_feedback']) {
				foreach ($widgets as &$widget) {
					$widget_api->populateFormData($widget);

					// set the values (normally 1, unless it's a list of checkboxes)
					$widget['values'] = $widget_api->getResponseValues($response->id);

					// get the other value
					$other = $widget_api->getOtherField();

					// add the full url to the file
					if ($widget['type'] == 'file') {
						$attachment_url = "admin/index.php?Page=Addons&Addon=surveys&Action=DownloadAttach&ajax=1&formId=" . $formId . "&responseId=" . $response->id . "&value=" . base64_encode($widget['values'][0]['value']);
						$attachment_tag =  SENDSTUDIO_APPLICATION_URL . "/" .  $attachment_url;
						// . "'>" . $widget['values'][0]['value'];
						$widget['values'][0]['value'] = $attachment_tag;
					}

					if ($other) {
						// the other value will be the last one
						$otherValueIndex = count($widget['values']) - 1;
						$widget['values'][$otherValueIndex]['value'] = $other['other_label_text'] . ' ' . $widget['values'][$otherValueIndex]['value'];
					}
				}


				$viewUri = SENDSTUDIO_APPLICATION_URL
					 . '/admin/index.php?Page=Addons&Addon=surveys&Action=viewresponses&surveyId='
					 . $surveyApi->id
					 . '&responseId='
					 . $response->id;
				$editUri = SENDSTUDIO_APPLICATION_URL
					 . '/admin/index.php?Page=Addons&Addon=surveys&Action=editresponse&surveyId='
					 . $surveyApi->id
					 . '&responseId='
					 . $response->id;

				$this->_template->Assign('form', $surveyApi->GetData());
				$this->_template->Assign('widgets', $widgets);
				$this->_template->Assign('emailBodyStart', sprintf(GetLang('Addon_Surveys_emailBodyStart'), $surveyApi->Get('name')));
				$this->_template->Assign('emailViewLink', sprintf(GetLang('Addon_Surveys_emailViewLink'), $viewUri));
				$this->_template->Assign('emailEditLink', sprintf(GetLang('Addon_Surveys_emailEditLink'), $editUri));

				// parse the email template for its content
				$emailTemplate = $this->_template->ParseTemplate('email', true);

				require_once(IEM_PATH . '/ext/interspire_email/email.php');
				$emailapi = new Email_API();

				$emailapi->SetSmtp(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);
				//if ($this->smtpserver) {
				//	$emailapi->SetSmtp($this->smtpserver, $this->smtpusername, $this->smtppassword, $this->smtpport);
				//}

				$emailapi->ClearRecipients();
				$emailapi->ForgetEmail();
				$emailapi->Set('forcechecks', false);

				$to = ($surveyApi->Get('email'));
				$emailapi->AddRecipient($to);

				$emailapi->Set('FromAddress', (defined('SENDSTUDIO_EMAIL_ADDRESS') ? SENDSTUDIO_EMAIL_ADDRESS : $userobject->emailaddress));
				$emailapi->Set('BounceAddress', SENDSTUDIO_EMAIL_ADDRESS);
				$emailapi->Set('CharSet', SENDSTUDIO_CHARSET);

				$subject = sprintf(GetLang('Addon_Surveys_emailSubject'), $surveyApi->Get('name'));
				$emailapi->Set('Subject', $subject);


				//email body
				$emailapi->AddBody('text', $emailTemplate);
				$status = $emailapi->Send();
				if ($status['success'] != 1) {
					trigger_error(__CLASS__ . '::' . __METHOD__ . ' -- Was not able to send email: ' . serialize($status['failed']), E_USER_NOTICE);
					return false;
				}
			}

			// perform file uploading

			if (isset($_FILES['widget']['name'])) {
				$files = $_FILES['widget']['name'];

				foreach ($files as $widgetId => $widget) {
					foreach ($widget as $widgetKey => $fields) {
						foreach ($fields as $fieldId => $field) {
							// gather file information
							$name    = $_FILES['widget']['name'][$widgetId]['field'][$fieldId]['value'];
							$type    = $_FILES['widget']['type'][$widgetId]['field'][$fieldId]['value'];
							$tmpName = $_FILES['widget']['tmp_name'][$widgetId]['field'][$fieldId]['value'];
							$error   = $_FILES['widget']['error'][$widgetId]['field'][$fieldId]['value'];
							$size    = $_FILES['widget']['size'][$widgetId]['field'][$fieldId]['value'];

							// if the upload was successful to the temporary folder, move it
							if ($error == UPLOAD_ERR_OK) {
								$tempdir   = TEMP_DIRECTORY;
								$upBaseDir = $tempdir . DIRECTORY_SEPARATOR . 'surveys';
								$upSurveyDir = $upBaseDir . DIRECTORY_SEPARATOR . $formId;
								$upDir     = $upSurveyDir . DIRECTORY_SEPARATOR . $response->GetId();

								// if the base upload directory doesn't exist create it
								if (!is_dir($upBaseDir)) {
									mkdir($upBaseDir, 0755);
								}

								if (!is_dir($upSurveyDir)) {
									mkdir($upSurveyDir, 0755);
								}

								// if the upload directory doesn't exist create it
								if (!is_dir($upDir)) {
									mkdir($upDir, 0755);
								}

								// upload the file
								move_uploaded_file($tmpName, $upDir . DIRECTORY_SEPARATOR . $name);
							}
						}
					}
				}
			}
		}

		// if we are redirecting to a url, redirect them
		switch ($surveyData['after_submit']) {
			case 'show_uri':
				header('Location: ' . $surveyApi->show_uri);
				exit;
			break;

			case 'show_message':
				IEM::sessionSet('survey.addon.' . $formId . '.successMessage', $surveyApi->show_message);

			default:
				// redirect back
				$this->redirectToReferer();
		}
	}
}


$submit = new surveySubmit();

?>