<?php

$listeners = array();

$listeners[] = array('IEM_STATSAPI_RECORDOPEN', 'TriggerEmails_API::eventEmailOpen', '{%IEM_PUBLIC_PATH%}/functions/api/triggeremails.php');
$listeners[] = array('IEM_STATSAPI_RECORDLINKCLICK', 'TriggerEmails_API::eventLinkClicked', '{%IEM_PUBLIC_PATH%}/functions/api/triggeremails.php');
