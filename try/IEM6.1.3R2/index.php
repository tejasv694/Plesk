<?php
/**
* Simply redirect to the admin/ area.
*
* @version     $Id: index.php,v 1.3 2007/06/26 06:24:01 chris Exp $
* @author Chris <chris@interspire.com>
*
* @package SendStudio
*/

$location = 'admin/index.php';

/**
* If sendstudio is set up and working,
* redirect to the full application url
* rather than just admin/index.php
*/
$config_file = dirname(__FILE__) . '/admin/includes/config.php';
if (is_file($config_file)) {
	require_once($config_file);
	if (defined('SENDSTUDIO_IS_SETUP') && SENDSTUDIO_IS_SETUP == 1) {
		$location = SENDSTUDIO_APPLICATION_URL . '/admin/index.php';
	}
}

header('Location: ' . $location);
exit();
