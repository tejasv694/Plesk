<?php
// Make sure that the IEM controller does NOT redirect request.
define('IEM_NO_CONTROLLER', true);

// Include the index file
require_once dirname(__FILE__) . '/../../../index.php';

require_once SENDSTUDIO_API_DIRECTORY . '/captcha.php';
$captcha = new Captcha_API();
$captcha->OutputImage();