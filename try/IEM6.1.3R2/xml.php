<?php

/**
 * The XML API for sendstudio is handled here.
 * Most of the function calls relate directly to the PHP-API so 90% of the work is done there already.
 * There are some special cases handled here.
 *
 * The 'AddSubscriberToList' request method is a special case handled just in the xml-api, though it relies on functionality straight from the php-api.
 *
 * @version     $Id: xml.php,v 1.7 2008/03/05 08:50:26 chris Exp $
 * @author Chris <chris@interspire.com>
 *
 * @package SendStudio
 */
// Make sure that the IEM controller does NOT redirect request.
if (!defined('IEM_NO_CONTROLLER')) {
    define('IEM_NO_CONTROLLER', true);
}

// Require base sendstudio functionality. This connects to the database, sets up our base paths and so on.
require_once dirname(__FILE__) . '/admin/index.php';

// Set content type before anything else
header("Content-Type: text/xml");

// ----- Defines common functions used
/**
 * Send response back
 * It will print out a response and stop the script execution
 *
 * @param boolean $status Indicates whether or not to respond with a "SUCCESS" or "FAILED" response
 * @param string $data Data to be sent back
 */
function SendResponse($status, $data) {
    echo '<?xml version="1.0" encoding="' . SENDSTUDIO_CHARSET . '" ?>';
    echo "\n<response>\n";

    if ($status) {
        echo "<status>SUCCESS</status>\n";
        echo "<data>" . trim(CreateOutput($data)) . "</data>";
    } else {
        echo "<status>FAILED</status>\n";
        echo "<errormessage>";
        if(is_array($data)){
            foreach($data as $str){
                echo htmlspecialchars($str, ENT_QUOTES, SENDSTUDIO_CHARSET);
            }    
        } else {
            echo htmlspecialchars($data, ENT_QUOTES, SENDSTUDIO_CHARSET);
        }
        echo "</errormessage>\n";
         
    }

    echo "</response>\n";
    exit;
}

/**
 * CreateFunctionParams
 * Creates an array of function parameters to pass directly to the php-api
 * This is a recursive function.
 * If multiple elements are found for a particular area, it's turned into an array.
 *
 * @param Array $params Takes an array from the simple-xml object based on the 'details' item.
 *
 * @return Array Returns an array of function parameters to pass to the php-api. The array key is the 'field name' and the value is the data from the xml document.
 */
function CreateFunctionParams($params) {
    if (empty($params)) {
        return array();
    }

    $return = array();

    foreach ($params as $sub_key => $sub_params) {
        $sub_key = trim($sub_key);

        $child_params = $sub_params->children();

        $has_children = true;
        if (empty($child_params)) {
            $has_children = false;
        }

        if (!$has_children) {
            if (array_key_exists($sub_key, $return)) {
                if (!is_array($return[$sub_key])) {
                    $return[$sub_key] = array($return[$sub_key]);
                }

                array_push($return[$sub_key], trim((string) $sub_params));
            } else {
                $return[$sub_key] = trim((string) $sub_params);
            }
            continue;
        }

        if (!isset($return[$sub_key])) {
            $return[$sub_key] = array();
        } else {
            if (!is_array($return[$sub_key])) {
                $return[$sub_key] = array($return[$sub_key]);
            }
        }
        $sub_params = CreateFunctionParams($child_params);

        /**
         * If it's an 'addsubscribertolist' request type, then we need to
         * make sure the custom fields sub-items are a multi-dimensional array
         * so we end up with:
         *
         * [0] => array
         * (
         * 	[fieldid]	=> 'A',
         * 	[value]		=> 'B',
         * ),
         * [1] => array
         * (
         * 	[fieldid]	=> 'C',
         * 	[value]		=> 'D',
         * );
         *
         * If it's not that particular request type, then
         * we should end up with a normal array, eg:
         *
         * 	[searchinfo] => Array
         * 	(
         * 		[List] => 1
         * 		[Email] => @domain.com
         * 	)
         *
         */
        if (SENDSTUDIO_XML_REQUESTTYPE == 'subscribers' && SENDSTUDIO_XML_REQUESTMETHOD == 'addsubscribertolist') {
            if (sizeof(array_keys($sub_params)) > 1) {
                if(@isset($sub_params['value'][0]['dd'])){
                    $sub_params['value'] = $sub_params['value'][0];
                }
                $return[$sub_key][] = $sub_params;
            } else {
                $return[$sub_key] = $sub_params;
            }
        } else {
            $return[$sub_key] = $sub_params;
        }
    }
    return $return;
}

/**
 * CreateOutput
 * This is a recursive function that creates a valid xml response and handles whether the output passed in is
 * - just a string (return an escaped version of the output)
 * - an array (return an xml based tree)
 * - an array with numeric id's (eg '<0>xyz</0>')
 * - a multidimensional array (eg '<0><listname>xyz</listname></0>')
 *
 * @param Mixed $output The output to display can be a string, a single-element array or multi-dimensional array.
 *
 * @see FormatXML
 *
 * @return Void Returns a formatted xml document.
 */
function CreateOutput($output='') {
    if (!is_array($output)) {
        return sprintf('%s', htmlspecialchars($output, ENT_QUOTES, SENDSTUDIO_CHARSET)) . "\n";
    }

    $xml_output = '';
    foreach ($output as $name => $data) {
        if (is_numeric($name)) {
            $name = 'item';
        }
        $quoted_name = htmlspecialchars($name, ENT_QUOTES, SENDSTUDIO_CHARSET);

        if (!is_array($data)) {
            $xml_output .= sprintf('<%s>%s</%s>', $quoted_name, htmlspecialchars($data, ENT_QUOTES, SENDSTUDIO_CHARSET), $quoted_name);
            continue;
        }

        $xml_output .= sprintf('<%s>', $quoted_name);

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $xml_output .= '<item>' . CreateOutput($v) . '</item>';
                    continue;
                }
                if (is_numeric($k)) {
                    $k = 'item';
                }
                $k_quoted = htmlspecialchars($k, ENT_QUOTES, SENDSTUDIO_CHARSET);
                $xml_output .= sprintf('<%s>%s</%s>', $k_quoted, htmlspecialchars($v, ENT_QUOTES, SENDSTUDIO_CHARSET), $k_quoted);
            }
        }
        $xml_output .= sprintf('</%s>', $quoted_name);
    }
    return FormatXML($xml_output);
}

/**
 * FormatXML
 * Formats xml passed in and indents tags etc to make it visually appealing.
 *
 * @param String $xml The xml to format.
 *
 * @return String Returns a well-formed, indented xml document.
 */
function FormatXML($xml) {
    $xml = (string) $xml;
    $newxml = '';

    $len = strlen($xml);
    $tags = array();
    $InCData = false;
    $alphabet = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'), range(0, 9));
    $numbers = range('0', '9');

    for ($char = 0; $char < $len; ++$char) {

        if ($xml[$char] == "<" && $InCData !== true && $xml[$char + 1] != "?") {
            // starting some sort of tag!
            // is it a closing tag?!
            if ($xml[$char + 1] == "/") {
                // its a closing tag! for what tho?
                $num = 2;
                $tagName = '';
                while (in_array($xml[$char + $num], $alphabet, true) || (in_array((int) $xml[$char + $num], $numbers, true) && is_numeric($xml[$char + $num]))) {
                    $tagName .= $xml[$char + $num];
                    ++$num;
                }

                // continue until the end of the tag
                while ($xml[$char + $num] != '>') {
                    ++$num;
                }

                if ($lastaction == "closed") {
                    $newxml = $newxml . "\n" . str_repeat("\t", max(sizeof($tags) - 1, 0)) . substr($xml, $char, $num + 1);
                } else {
                    $newxml = $newxml . substr($xml, $char, $num + 1);
                }

                $char = $char + $num;

                if (in_array($tagName, $tags)) {
                    // we need to kill the tag, but only the most recent one
                    $size = sizeof($tags);
                    if ($size > 0) {
                        foreach ($tags as $key => $tmpTag) {
                            if ($tmpTag == $tagName) {
                                $lastKey = $key;
                            }
                        }
                        // $lastKey holds the tag we want to kill
                        $tmpArray = array();
                        foreach ($tags as $key => $tmpTag) {
                            if ($key != $lastKey) {
                                $tmpArray[] = $tmpTag;
                            }
                        }
                        $tags = $tmpArray;
                    }
                }

                $lastaction = "closed";
            } elseif ($xml[$char] . $xml[$char + 1] . $xml[$char + 2] . $xml[$char + 3] . $xml[$char + 4] . $xml[$char + 5] . $xml[$char + 6] . $xml[$char + 7] . $xml[$char + 8] == "<![CDATA[") {
                // its a cdata!
                $InCData = true; // don't need to do anything else
                $newxml = $newxml . $xml[$char];
                $lastaction = '';
            } else {
                // must be an opening tag...
                $num = 1;
                $tagName = '';
                while (in_array($xml[$char + $num], $alphabet, true) || (in_array((int) $xml[$char + $num], $numbers, true) && is_numeric($xml[$char + $num]))) {
                    $tagName .= $xml[$char + $num];
                    ++$num;
                }
                $owntag = false;

                // continue until the end of the tag, make sure its not a single one
                while ($xml[$char + $num] != '>') {
                    if ($xml[$char + $num] . $xml[$char + $num + 1] == "/>") {
                        // self contained tag! don't add it
                        $owntag = true;
                        break;
                    }
                    ++$num;
                }

                if (!$owntag) {
                    $newxml = $newxml . "\n" . str_repeat("\t", sizeof($tags)) . substr($xml, $char, $num + 1);
                    $tags[] = $tagName;
                    $char = $char + $num;
                } else {
                    $newxml = $newxml . "\n" . str_repeat("\t", sizeof($tags)) . substr($xml, $char, $num + 2);
                    $char = $char + $num + 1;
                }
                $lastaction = 'opened';
            }
        } elseif ($InCData === true && $xml[$char - 2] . $xml[$char - 1] . $xml[$char] == "]]>") {
            $newxml = $newxml . '>';
            $InCData = false;
        } else {
            $newxml = $newxml . $xml[$char];
        }
    }
    return $newxml;
}

// -----
// ----- VARIABLES
$xml = false;
$userRecord = false;

$handlerObject = false;
$handlerMethod = false;

$function_params = false;
// -----
// SimpleXML extensions needs to be loaded
if (!extension_loaded('SimpleXML')) {
    SendResponse(false, 'The XML-API requires the SimpleXML extension to be loaded.');
}

if (defined('IEM_SYSTEM_ACTIVE') && !IEM_SYSTEM_ACTIVE) {
    SendResponse(false, 'Error: Please contact your system admin!');
}

// ----- Get XML object
$tempXMLString = IEM::requestGetPOST('xml', '', 'trim');

// They do not parse the XML string into the POST parameter, so getting it from php://input stream
if (empty($tempXMLString)) {
    $tempXMLString = file_get_contents('php://input');
}

$tempXMLString = trim($tempXMLString);

// Make sure XML request is NOT empty
if (empty($tempXMLString)) {
    SendResponse(false, 'No data has been given to the XML-API.');
}

/**
 * we can't use a try/catch and a 'new SimpleXMLObject' here because php4 throws a parse error when it hits the 'try' line.
 * We need a try/catch to check to make sure the xml is valid,
 * so instead of doing it that way, use another function to do it.
 */
$xml = @simplexml_load_string($tempXMLString);
if (!is_object($xml)) {
    SendResponse(false, 'The XML you provided is not valid. Please check your XML document and try again.');
}

unset($tempXMLString);
// -----
// ----- Verify that required field exists, and set up environment
$tempRequired = array('username', 'usertoken', 'requesttype', 'requestmethod', 'details');

foreach ($tempRequired as $tempEach) {
    if (!isset($xml->$tempEach) || empty($xml->$tempEach)) {
        SendResponse(false, "The XML format you have sent is invalid. The following field is required: {$tempEach}");
    }
}

unset($tempEach);
unset($tempRequired);

// Make sure that the requesttype and requestmethod is alphanumeric
$xml->requesttype = preg_replace('/[^\w]/', '_', $xml->requesttype);
$xml->requestmethod = preg_replace('/[^\w]/', '_', $xml->requestmethod);

define('SENDSTUDIO_XML_REQUESTTYPE', strtolower($xml->requesttype));
define('SENDSTUDIO_XML_REQUESTMETHOD', strtolower($xml->requestmethod));
// -----
// ----- Get and verify user credentials
$tempAuth = new AuthenticationSystem();

$userRecord = $tempAuth->Authenticate((string) $xml->username, null, (string) $xml->usertoken);

unset($tempAuth);

// User not found
if (empty($userRecord) || !isset($userRecord['userid'])) {
    SendResponse(false, 'Unable to check user details.');
}

// authentication::xmlapitest receive special treatment
if ($xml->requesttype == 'authentication' && $xml->requestmethod == 'xmlapitest') {
    SendResponse(true, array(
        'userid' => $userRecord['userid'],
        'username' => $userRecord['username']
    ));
}
// -----
// -----
// Get request handlerObject (ie. Get the class or class name of the API that will execute the request),
// handlerMethod, and it's parameter
// -----
$tempFile = SENDSTUDIO_API_DIRECTORY . '/' . (string) $xml->requesttype . '.php';
$tempClass = (string) $xml->requesttype;

// This is using the older API in admin/functions/api directory
if (is_readable($tempFile)) {
    require_once $tempFile;
    $tempClass = ucwords(strtolower($tempClass)) . '_API';

    if (!class_exists($tempClass, false)) {
        SendResponse(false, 'Invalid request type');
    }

    if ($tempClass == 'User_API') {
        $handlerObject = new User_API($userRecord['userid']);
    } else {
        $handlerObject = new $tempClass;
    }

    $handlerMethod = strtolower((string) $xml->requestmethod);


    // This request is using the new API located in com/lib/API directory
    // Make sure that it is only restricted to use API library and not everything else
} elseif (substr($tempClass, 0, 4) == 'API_' && class_exists($tempClass, true)) {
    $handlerObject = $tempClass;
    $handlerMethod = (string) $xml->requestmethod;

    // No handler (or API found), so print out an invalid request error
} else {
    SendResponse(false, 'Invalid request type');
}

// Get parameter
$function_params = CreateFunctionParams($xml->details[0]);

unset($tempClass);
unset($tempFile);
// -----

switch ($handlerMethod) {
    /**
    * Fix for outdated XML API documentation
    * Changes the 'list' parameter to 'listid' and removes the 'list' parameter
    */
    case 'deletesubscriber':
        if(isset($function_params['list'])){$function_params['listid'] = $function_params['list']; unset($function_params['list']);}
        list($status,$msg) = call_user_func_array(array($handlerObject, 'DeleteSubscriber'), $function_params);
        if (!$status) {
            SendResponse(false, $msg);
        } else {
            SendResponse(true,"");
        }
    break;
    /**
     * addsubscribertolist is an xml-api specific function.
     * It checks if the subscriber is already on the list (if it is, it returns)
     * If they are not on the list, they are added.
     * Then each of the custom fields are associated with the subscriber if necessary.
     * The php-api has this as a multiple step process, but the xml-api does it all in one go.
     *
     * @see Subscriber_API::IsSubscriberOnList
     * @see Subscriber_API::AddToList
     * @see Subscriber_API::SaveSubscriberCustomField
     */
    case 'addsubscribertolist':
        $params = array();
        $params['emailaddress'] = (isset($function_params['emailaddress'])) ? $function_params['emailaddress'] : false;
        $params['mailinglist'] = (isset($function_params['mailinglist'])) ? $function_params['mailinglist'] : false;

        // check if they are on the list already.
        $subid = call_user_func_array(array($handlerObject, 'IsSubscriberOnList'), $params);
        if ($subid) {
		/* #*#*# DISABLED! FLIPMODE! #*#*#
			SendResponse(false, 'Subscriber already exists on list');
	    #*#*# / / / / #*#*# */
		
		/* #*#*# ADDED! FLIPMODE! #*#*# * ????????
            SendResponse(true, '');
		???????? */
            SendResponse(false, $subid);
            exit;
        }

        $params['add_to_autoresponders'] = (isset($function_params['add_to_autoresponders'])) ? $function_params['add_to_autoresponders'] : true;

        $db = IEM::getDatabase();
        $db->StartTransaction();

        if (isset($function_params['format'])) {
            $format = strtolower($function_params['format']);
            $formats = array('t', 'text', 'h', 'html');
            if (in_array($format, $formats)) {
                $handlerObject->format = substr($format, 0, 1);
            }
        }

        if (isset($function_params['ipaddress'])) {
            if (SENDSTUDIO_IPTRACKING) {
                $handlerObject->requestip = $function_params['ipaddress'];
            }
        }

        if (isset($function_params['confirmed'])) {
            $sub_confirmed = 0;
            $confirmed = strtolower($function_params['confirmed']);
            if ($confirmed == 'yes' || $confirmed == 'y' || $confirmed == 'true' || $confirmed == '1') {
                $sub_confirmed = 1;
            }
            if (isset($function_params['ipaddress'])) {
                if (SENDSTUDIO_IPTRACKING) {
                    $handlerObject->confirmip = $function_params['ipaddress'];
                }
            }
            $handlerObject->confirmed = $sub_confirmed;
        }

        // now try to add them to the list.
        $subscriber_id = call_user_func_array(array($handlerObject, 'AddToList'), $params);
        if (!$subscriber_id) {
            $db->RollbackTransaction();
	    /* #*#*# DISABLED! FLIPMODE! #*#*#
            SendResponse(false, 'Failed adding subscriber to the list');
		#*#*# / / / / #*#*# */
	    
		/* #*#*# ADDED! FLIPMODE! #*#*# */
            SendResponse(true, '');
        }

        $send_notification = false;

        require_once(SENDSTUDIO_LANGUAGE_DIRECTORY . '/default/frontend.php');

        /**
         * we don't need to include the api/lists.php file
         * because the AddToList function in the subscribers api does it already
         * because it checks the list exists before anything else.
         */
        $lists_api = new Lists_API();
        $lists_api->Load($function_params['mailinglist']);

        $listowneremail = $lists_api->Get('owneremail');
        $listownername = $lists_api->Get('ownername');

        require_once(IEM_PATH . '/ext/interspire_email/email.php');
        $emailapi = new Email_API();

        $emailapi->SetSMTP(SENDSTUDIO_SMTP_SERVER, SENDSTUDIO_SMTP_USERNAME, @base64_decode(SENDSTUDIO_SMTP_PASSWORD), SENDSTUDIO_SMTP_PORT);

        $emailapi->Set('CharSet', SENDSTUDIO_CHARSET);

        $emailapi->Set('Subject', GetLang('SubscriberNotification_Subject'));
        $emailapi->Set('FromName', false);
        $emailapi->Set('FromAddress', $listowneremail);
        $emailapi->Set('ReplyTo', $function_params['emailaddress']);
        $emailapi->Set('BounceAddress', SENDSTUDIO_EMAIL_ADDRESS);

        $emailapi->Set('Subject', sprintf(GetLang('SubscriberNotification_Subject_Lists'), $lists_api->name));

        $body = '';
        $body .= sprintf(GetLang('SubscriberNotification_Field'), GetLang('EmailAddress'), $function_params['emailaddress']);

        // no custom fields to process? just return the subscriber id.
        if (!isset($function_params['customfields'])) {

            $body .= sprintf(GetLang('SubscriberNotification_Lists'), $lists_api->name);

            $emailbody = sprintf(GetLang('SubscriberNotification_Body'), $body);

            $emailapi->AddBody('text', $emailbody);

            if ($lists_api->notifyowner) {
                $emailapi->AddRecipient($lists_api->owneremail, $lists_api->ownername, 't');
                $emailapi->Send();
            }

            $db->CommitTransaction();
            SendResponse(true, $subscriber_id);
            exit;
        }

        require_once(SENDSTUDIO_API_DIRECTORY . '/customfields.php');
        $customfields_api = new CustomFields_API();

        // if there is only one custom field, then it's not converted into a multi-dimensional array with each custom field being an 'item'.
        // instead we just get a single element.
        $subscriber_customfields = (isset($function_params['customfields']['item'])) ? $function_params['customfields']['item'] : $function_params['customfields'];

        foreach ($subscriber_customfields as $k => $details) {
            $loaded = $customfields_api->Load($details['fieldid']);
            if (!$loaded) {
                $db->RollbackTransaction();
                SendResponse(false, "Unable to load field id '" . $details['fieldid'] . "'");
                exit;
            }

            // See if specific custom fields need data transformation
            switch ($customfields_api->fieldtype) {
                // Custom fields that require multiple values need to be converted to array
                case 'checkbox':
                    if (!is_array($details['value'])) {
                        $details['value'] = array($details['value']);
                    }
                break;
                
                 case 'date':
                    require_once(SENDSTUDIO_API_DIRECTORY.'/customfields_date.php');
                    $cfdateapi = new CustomFields_Date_API($details['fieldid']);
                    $details['value'] = $cfdateapi->CheckData($details['value'],true);
                    if($details['value'] !== false){$details['value'] = $details['value'][0]."/".$details['value'][1]."/".$details['value'][2];}
                 break;

                // All other custom fields
                default:
                break;
            }

            if($details['value'] !== false){
                $valid_value = $customfields_api->ValidData($details['value']);
            }else{
                $valid_value = false;
            }
            if (!$valid_value) {
                $db->RollbackTransaction();
                SendResponse(false, "The data provided for field '" . $customfields_api->GetFieldName() . "' is invalid (you provided '" . $details['value'] . "')");
                exit;
            }
            $handlerObject->SaveSubscriberCustomField($subscriber_id, $details['fieldid'], $details['value']);

            $fieldvalue = $customfields_api->GetRealValue($details['value']);
            if ($fieldvalue == '') {
                $fieldvalue = GetLang('SubscriberNotification_EmptyField');
            }
            $fieldname = $customfields_api->GetFieldName();
            $body .= sprintf(GetLang('SubscriberNotification_Field'), $fieldname, $fieldvalue);
        }

        $body .= sprintf(GetLang('SubscriberNotification_Lists'), $lists_api->name);

        $emailbody = sprintf(GetLang('SubscriberNotification_Body'), $body);

        $emailapi->AddBody('text', $emailbody);

        if ($lists_api->notifyowner) {
            $emailapi->AddRecipient($lists_api->owneremail, $lists_api->ownername, 't');
            $emailapi->Send();
        }

        $db->CommitTransaction();
        SendResponse(true, $subscriber_id);
        exit;
        break;

    case 'createnewuser':
    case 'editexistinguser':
        $editMode = ($handlerMethod == 'editexistinguser');

        // Set properties and their default value
        // null means there isn't any default value, and caller must supply this as a parameter
        $properties = array(
            'groupid' => null,
            'trialuser' => '0',
            'username' => null,
            'password' => null,
            'fullname' => null,
            'emailaddress' => null,
            'usertimezone' => null,
            'gettingstarted' => 0,
            'status' => 1,
            'textfooter' => '',
            'htmlfooter' => '',
            'admintype' => 'c',
            'listadmintype' => 'c',
            'segmentadmintype' => 'c',
            'templateadmintype' => 'c',
            'smtpserver' => '',
            'smtpusername' => '',
            'smtppassword' => '',
            'smtpport' => '',
            'infotips' => 1,
            'usewysiwyg' => 1,
            'enableactivitylog' => 1,
            'editownsettings' => 0,
            'xmlapi' => 0,
            'xmltoken' => '',
            'googlecalendarusername' => '',
            'googlecalendarpassword' => '',
            'user_language' => '',
            'adminnotify_email' => '',
            'adminnotify_send_flag' => '0',
            'adminnotify_send_threshold' => 0,
            'adminnotify_send_emailtext' => '',
            'adminnotify_import_flag' => '0',
            'adminnotify_import_threshold' => 0,
            'adminnotify_import_emailtext' => '',
        );



        // ----- Check if the required parameter is present
        $tempRequired = array();

        // If "edit mode", the only parameter that's required is userid
        if ($editMode) {
            if ($editMode && !isset($function_params['userid'])) {
                array_push($tempRequired, 'userid');
            }
        } else {
            // Make sure that required properties is passed in
            // Get all keys that have NULL values, and put them in "Required" array
            foreach ($properties as $key => $value) {
                if (is_null($value) && !isset($function_params[$key])) {
                    array_push($tempRequired, $key);
                }
            }

            // Also need the "permissions" parameter
            if (!isset($function_params['permissions'])) {
                array_push($tempRequired, 'permissions');
            }
        }



        if (count($tempRequired) != 0) {
            SendResponse(false, 'Invalid parameters specified to use this function.');
            exit();
        }
        // -----


        $user = New User_API();
        $warnings = array();

        // Load existing data if editing
        if ($editMode) {
            $param_userid = IEM::ifsetor($function_params['userid'], false);
            if (!$param_userid) {
                SendResponse(false, 'userid cannot be empty.');
                exit();
            }

            $status = $user->Load($param_userid, true);
            if (!$status) {
                SendResponse(false, 'Cannot load user record.');
                exit();
            }
        }

        // ----- Check if username is available to be used
        $param_username = IEM::ifsetor($function_params['username'], false);
        if (!$param_username) {
            SendResponse(false, 'username cannot be empty.');
            exit();
        }

        $existingUser = $user->Find($param_username);

        if ($existingUser !== false) {
            $tempError = true;

            if ($editMode && $existingUser == $function_params['userid']) {
                $tempError = false;
            }

            if ($tempError) {
                SendResponse(false, 'Username is already taken.');
                exit();
            }
        }
        // -----
        // Set class properties
        foreach ($properties as $key => $value) {
            // If "edit mode", only set properties that were added in
            if ($editMode) {
                if (isset($function_params[$key])) {
                    $user->Set($key, $function_params[$key]);
                }

                continue;
            }

            $tempValue = $value;
            if (isset($function_params[$key])) {
                $tempValue = $function_params[$key];
            }
            $user->Set($key, $tempValue);
        }

        // Adjust max hourly rate so that it's not greater than the global max hourly rate
        if (SENDSTUDIO_MAXHOURLYRATE > 0) {
            if ($user->Get('perhour') == 0 || ($user->Get('perhour') > SENDSTUDIO_MAXHOURLYRATE)) {
                $user_hourly = $this->FormatNumber($user->Get('perhour'));
                if ($user->Get('perhour') == 0) {
                    $user_hourly = GetLang('UserPerHour_Unlimited');
                }
                $warnings[] = sprintf(GetLang('UserPerHourOverMaxHourlyRate'), $this->FormatNumber(SENDSTUDIO_MAXHOURLYRATE), $user_hourly);
            }
        }

        // Set permissions only if supplied
        if (!empty($function_params['permissions'])) {
            $user->RevokeAccess();
            foreach ($function_params['permissions'] as $area => $p) {
                foreach ($p as $subarea => $k) {
                    $user->GrantAccess($area, $subarea);
                }
            }
        }

        // Check if we need to grant extra list access to the user
        if (isset($function_params['lists'])) {
            $user->RevokeListAccess();

            $access = array();
            if (!is_array($function_params['lists'])) {
                $access[$function_params['lists']] = 1;
            } else {
                foreach ($function_params['lists'] as $listid) {
                    $access[$listid] = 1;
                }
            }

            $user->GrantListAccess($access);
        }

        // Check if we need to grant extra templates access to the user
        if (isset($function_params['templates'])) {
            $user->RevokeTemplateAccess();

            $access = array();
            if (!is_array($function_params['templates'])) {
                $access[$function_params['templates']] = 1;
            } else {
                foreach ($function_params['templates'] as $templateid) {
                    $access[$templateid] = 1;
                }
            }

            $user->GrantTemplateAccess($access);
        }

        // Check if we need to grant extra segments access to the user
        if (isset($function_params['segments'])) {
            $user->RevokeSegmentAccess();

            $access = array();
            if (!is_array($function_params['segments'])) {
                $access[$function_params['segments']] = 1;
            } else {
                foreach ($function_params['segments'] as $segmentid) {
                    $access[$segmentid] = 1;
                }
            }

            $user->GrantSegmentAccess($access);
        }

        if ($editMode) {
            $result = $user->Save(true);
            if (!$result) {
                SendResponse(false, 'Unable to update user');
                exit();
            }

            // Run any customized modifications we need to. Will load xml-api-editexistinguser.php if it exists
            $file = IEM_PATH . '/custom/xml-api-editexistinguser.php';
            if (is_readable($file)) {
                include $file;
            }

            SendResponse(true, $user->userid);
            exit();
        } else {
            // Create user
            $result = $user->Create();
            if ($result == '-1') {
                SendResponse(false, 'The license will not allow you to create more users.');
                exit();
            } elseif (!$result) {
                SendResponse(false, 'Unable to create user');
                exit();
            }

            // Run any customized modifications we need to. Will load xml-api-createnewuser.php if it exists
            $file = IEM_PATH . '/custom/xml-api-createnewuser.php';
            if (is_readable($file)) {
                include $file;
            }

            // TODO: it doesn't print any warnings yet
            SendResponse(true, intval($result));
            exit();
        }
        break;

    /**
     * If the request method is not an xml-api specific method, then try to call the php-api function directly and return what it needs to return.
     * Of course, we check to make sure it's a valid requestmethod before trying to call it.
     */
    default:
        if (!is_callable(array($handlerObject, $handlerMethod)) and !is_callable("{$handlerObject}::{$handlerMethod}")) {
            SendResponse(false, 'Invalid request type');
        }
        $response = false;
        $handlerMethodReflector = new ReflectionMethod($handlerObject, $handlerMethod);
        $handlerMethodParameterReflector = $handlerMethodReflector->getParameters();
        $newFunctionParams = false;
        foreach ($handlerMethodParameterReflector as &$handlerMethodParam) {
            $response[] = $handlerMethodParam->getName();
            if (array_key_exists($handlerMethodParam->getName(), $function_params)) {
                //leave it
                $newFunctionParams[] = $function_params[$handlerMethodParam->getName()];
            } else {
                //add a null
                $newFunctionParams[] = $handlerMethodParam->getDefaultValue();
            }
        }
        if (is_object($handlerObject)) {
            $response = $handlerMethodReflector->invokeArgs($handlerObject, $newFunctionParams);
        } else {
            $response = $handlerMethodReflector->invokeArgs(null, $newFunctionParams);
        }
        if(is_array($response) && isset($response[0]) && $response[0] === false){
            if(isset($response[1]) && !empty($response[1])){
                SendResponse(false, $response[1]);
            } else {
                SendResponse(false, $response);
            }
        }
    
        if(is_array($response) && isset($response[0]) && $response[0] === true){
            if(isset($response[1])){
                $response = (empty($response[1])) ? null : $response[1];
                SendResponse(true, $response);
            } else {
                SendResponse(true, null);
            }
        }
        
        if(empty($response)){SendResponse(false, $response);}else{SendResponse(true, $response);}
        break;
}
