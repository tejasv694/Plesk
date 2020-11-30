<?php
/**
* This file contains the functionality for FlashMessages to work.
* A flash message is something that is temporarily stored in the session for displaying later.
*
* A common use is after creating (or updating) an item,
* set a message and then redirect the user back to the 'Manage Items' page.
*
* This stops the user from being able to hit 'Refresh' in their browser and cause problems.
* It also stops the user from using the 'Back' button in their browser
* to go back in and edit the item they were just working on
* which will most likely also cause issues.
*
* FlashMessages can be done in bulk (multiple messages) or it can be just one message
* which will be stored in the session and then redirect the user.
*
* @package Library
* @subpackage FlashMessage
*/

/**
* These are used as constants for working out what sort of flash message to display.
* The message type controls which template will be parsed (ie what sort of 'box' the error will be in).
*
* @see GetFlashMessages
* @see FlashMessage
*/
define('SS_FLASH_MSG_SUCCESS', 1);
define('SS_FLASH_MSG_ERROR', 2);
define('SS_FLASH_MSG_WARNING', 3);
define('SS_FLASH_MSG_INFO', 4);

/**
* FlashMessage
* Save a message in the session for the next time we display the page.
* This allows us to do a redirect back to the same page we were on so we don't have issues with hitting f5 and re-saving data.
* If a url is passed in, then it will try to do a redirect to that url after saving the message in the session.
* If headers have already been sent, then a javascript redirection is done instead of a header('Location: $url') type redirect.
*
* @param String $msg The message we will store. This needs to be the whole string/description, not the language variable.
* @param Int $msg_type The type of message we're going to show. This will be one of 4 states. This is used to work out which template to show the msg in.
* @param String $url If a url is passed to FlashMessage, then immediately after storing the message in the session, we'll redirect to that location. The url you pass in will be relative to the admin/ folder.
*
* @uses SS_FLASH_MSG_SUCCESS
* @uses SS_FLASH_MSG_ERROR
* @uses SS_FLASH_MSG_WARNING
* @uses SS_FLASH_MSG_INFO
* @see GetFlashMessages
*
* @return Void Doesn't return anything, the message (and it's details) are just stored in the session.
*/
function FlashMessage($msg='', $msg_type=SS_FLASH_MSG_SUCCESS, $url=null)
{
	$flash_messages = IEM::sessionGet('FlashMessages', false);
	if (!$flash_messages) {
		$flash_messages = array();
	}
	$flash_messages[] = array('message' => $msg, 'type' => $msg_type);
	IEM::sessionSet('FlashMessages', $flash_messages);

	if ($url !== null) {

		/**
		* If the url doesn't start with http (or https), put the full url at the start of it.
		* If it does start with http (or https), don't touch it.
		*/
		if (substr($url, 0, 4) !== 'http') {
			$url = SENDSTUDIO_APPLICATION_URL . '/admin/' . $url;
		}

		if (!headers_sent()) {
			header('Location: ' . $url);
			exit;
		}
		?>
		<script>
			window.location.href = '<?php echo $url; ?>';
		</script>
		<?php
		exit;
	}
}

/**
* GetFlashMessages
* Gets the messages from the session and works out which template etc to display them in based on the message type.
* If there are multiple messages, they are all returned (based on which type/template etc) in one long string.
*
* It will not combine all 'success' messages into one box and all 'error' messages into another box.
* Each message is displayed in it's own box and they are returned in the order they were created.
*
* If you create a 'success' message then an 'info' message then an 'error' message, that is the order they are returned in.
*
* @see FlashMessage
* @uses SS_FLASH_MSG_SUCCESS
* @uses SS_FLASH_MSG_ERROR
* @uses SS_FLASH_MSG_WARNING
* @uses SS_FLASH_MSG_INFO
*
* @return String Returns the message ready for displaying.
*/
function GetFlashMessages()
{
	$flash_messages = IEM::sessionGet('FlashMessages', false);

	if (!$flash_messages) {
		return '';
	}

	$template_system = GetTemplateSystem();

	$print_msg = '';
	foreach ($flash_messages as $msg) {
		switch ($msg['type']) {
			case SS_FLASH_MSG_SUCCESS:
				$GLOBALS['Success'] = $msg['message'];
				$print_msg .= $template_system->ParseTemplate('successmsg', true);
			break;

			case SS_FLASH_MSG_ERROR:
				$GLOBALS['Error'] = $msg['message'];
				$print_msg .= $template_system->ParseTemplate('errormsg', true);
			break;

			case SS_FLASH_MSG_INFO:
				$GLOBALS['Message'] = $msg['message'];
				$print_msg .= $template_system->ParseTemplate('infomsg', true);
			break;

			case SS_FLASH_MSG_WARNING:
				$GLOBALS['Warning'] = $msg['message'];
				$print_msg .= $template_system->ParseTemplate('warningmsg', true);
			break;
		}
	}
	IEM::sessionRemove('FlashMessages');

	return $print_msg;
}
