<?php

/**
 * This file provides a class for connecting to the Google Calendar API. Connections are done through fsockopen() and require SSL support.
 *
 * @author Tye <tye@interspire.com>
 *
 * @package Library
 */


/**
* GoogleCalendar class
* This class will interface with Google Calendar using the Gdata protocol. Only logging in and adding a non-recurring event are supported. This is done using the two functions: ClientLogin and addSimpleEvent. If the username & password are given when the class is constructed the ClientLogin function will be automatically called.
* Both functions will throw a GoogleCalendarException on failure
*
* Example:
*
*try {
*	$gcal = new GoogleCalendar('myaddress@gmail.com','mypassword');
*	$event = array(
*		'title' => 'My Title',
*		'text' => 'My Text',
*		'where' => 'My Where',
*		'when' => array(
*			'from' => '2008-09-03T12:00:00.000',
*			'to' => '2008-09-03T14:00:00.000'
*		)
*	);
*	$gcal->addSimpleEvent($event);
*} catch (GoogleCalendarException $e) {
*	echo "Exception: " . $e->getMessage();
*}
*/

class GoogleCalendar
{
	/**
	* connection
	* Holds the file handle for the connect to google.com
	* @var Resource
	*/
	private $connection;
	
	/**
	* _host
	* The host to connect to
	* @var String
	*/
	private $_host = 'ssl://www.google.com';
	
	/**
	* _post
	* The port to connect on
	* @var Integer
	*/
	private $_port = 443;
	
	/**
	* _auth
	* Holds the authentication data
	* @var Array
	*/
	private $_auth = array();

	/**
	* _debug
	* Enable debugging messages
	*/
	private $_debug = false;

	/**
	* __construct
	* Performs login if the username and password were given
	*
	* @param String $username The username to login as
	* @param String $password The password to use
	*
	* @see ClientLogin
	*
	* @return Void Returns nothing
	*/
	public function __construct($username = false,$password = false)
	{
		if ($username !== false && $password !== false) {
			$this->ClientLogin($username,$password);
		}
	}
	
	/**
	* _connection
	* Returns a reference to the connection to google.com and creates a new connection if not already connected.
	*
	* @throws GoogleCalendarException Throws an exception on an error.
	*
	* @return Resource Returns the file handle to the connection.
	*/
	private function &_connection()
	{
		if (is_resource($this->connection)) { return $this->connection; }
		$connection = fsockopen($this->_host,$this->_port,$errno,$errstr,30);
		if (!$connection) {
			throw new GoogleCalendarException("Could not connect to {$this->_host}: ($errno) $errstr",GoogleCalendarException::CONNECTION);
		}
		return $connection;
	}
	
	/**
	* ClientLogin
	* Sends the ClientLogin request to the Google API. Stores the Auth token in $this->_auth
	*
	* @param String $username The username to login as
	* @param String $password The password to use
	*
	* @see _connection
	* @see _postParams
	* @see _sendRequest
	* @see _parseAuthResponse
	*
	* @throws GoogleCalendarException On failure throws an exception
	*
	* @return Boolean Returns true on success
	*/
	public function ClientLogin($username,$password)
	{
		$connection =& $this->_connection();
		
		if (!strstr($username,'@')) {
			$username = $username . "@gmail.com";
		}
		
		$headers = 
			"POST /accounts/ClientLogin HTTP/1.1\n" .
			"Content-Type: application/x-www-form-urlencoded\n" .
			"Host: www.google.com\n" .
			"Accept: text/html, *\n" .
			"Connection: keep-alive";
		
		$params = array(
			'accountType' => 'HOSTED_OR_GOOGLE',
			'Email' => $username,
			'Passwd' => $password,
			'source' => 'none-none-1',
			'service' => 'cl'
		);
		$body = $this->_postParams($params);
		
		$response = $this->_sendRequest($connection,$headers,$body);
		$rheaders = $this->_parseHeaders($response['headers']);
		if ($rheaders['HTTP'] == '200') {
			$this->_parseAuthResponse($response['body']);
			return true;
		} else {
			if (strstr($response['body'],'Error=BadAuthentication')) {
				throw new GoogleCalendarException("Authentication details were not accepted",GoogleCalendarException::BADAUTH);
			}
			throw new GoogleCalendarException("Did not receive a 200 OK response",GoogleCalendarException::UNEXCEPTEDRESPONSE);
		}
	}
	
	/**
	* _parseAuthResponse
	* Parses a response from the ClientLogin function and stores the Auth token.
	*
	* @param String $body The body from the ClientLogin response
	*
	* @return Void Returns nothing. The auth token is stored in $this->_auth['Auth']
	*/
	private function _parseAuthResponse($body)
	{
		$body = explode("\n",$body);
		foreach ($body as $line) {
			if (preg_match('/([^=]+)=(.*)/',$line,$matches)) {
				$this->_auth[urldecode($matches[1])] = urldecode($matches[2]);
			}
		}
	}
	
	/**
	* _postParams
	* Formats an associative array into HTTP POST parameters
	*
	* @param Array $params An associative array of values
	*
	* @return String Returns a string that can be used in an HTTP POST request
	*/
	private function _postParams($params)
	{
		$return = array();
		foreach ($params as $key => $val) {
			$return[] = urlencode($key) . "=" . urlencode($val);
		}
		return implode('&',$return);
	}
	
	/**
	* _sendRequest
	* Sends an HTTP request to the server and retrieves the response
	*
	* @param Resource $connection The file handle for the connection to google.com
	* @param String $headers The headers for the request. The content-length header will be appended automatically. The headers must not end in a new line.
	* @param String $body The body for the request
	*
	* @see _getResponse
	*
	* @throws GoogleCalendarException Throws an exception on an error
	*
	* @return Array Returns the response for the request
	*/
	private function _sendRequest(&$connection,$headers,$body)
	{
		$headers .= "\nContent-length: " . strlen($body);
		
		$data = $headers . "\r\n\r\n" . $body;

		if ($this->_debug) {
			echo "\n>>>\n";
			echo $data;
			echo "\n---\n";
		}
		
		if (!fwrite($connection,$data)) {
			throw new GoogleCalendarException("Unable to send data",GoogleCalendarException::WRITEERROR);
		}
		
		return $this->_getResponse($connection);
	}
	
	/**
	* _getResponse
	* Read a response from a server. This will read until the first blank line (specifying the end of the headers) then will read N bytes where N is the length given by the Content-Length header.
	*
	* @return Array Returns an array with two keys: 'headers' which contains the headers for the response and 'body' containing the body
	*/
	private function _getResponse(&$connection)
	{
		$headers = $body = '';
		$length = 0;
		// Read the headers
		while (!feof($connection) && $line = fgets($connection)) {
			if ($line == "\r\n") {
				// End of headers
				break;
			}
			// The Google API will always send a Content-length header
			if (preg_match('/Content-length:\s*(\d+)/i',$line,$matches)) {
				$length = $matches[1];
			}
			$headers .= $line;
		}
		
		if ($this->_debug) {
			echo $headers;
			echo "\n";
		}
		
		// Read the body
		if ($length) {
			$body = fread($connection,$length);
		}
		
		if ($this->_debug) {
			echo $body;
			echo "\n<<<\n";
		}
		
		return array('headers' => $headers,'body' => $body);
	}
	
	/**
	* _parseHeaders
	* Parses HTTP headers and turns them into an associative array. The header names are changed to lowercase.
	*
	* @param String $headers The headers from an HTTP request.
	*
	* @return Array Returns an associative array with the headers in it. 
	*/
	private function _parseHeaders($headers)
	{
		$headers = preg_split('/\r?\n/',$headers);
		$return = array();
		foreach ($headers as $line) {
			if (preg_match('~^HTTP/1.1~',$line)) {
				$line = explode(" ",$line);
				$return['HTTP'] = $line[1];
			} elseif (preg_match('/(.*?):\s+(.*)/',$line,$matches)) {
				$return[strtolower($matches[1])] = $matches[2];
			}
		}
		
		return $return;
	}

	/**
	* addSimpleEvent
	* Adds a non-recurring event to the google calendar. You either must include the username/password when constructing the object or call ClientLogin before calling this function.
	*
	* @param Array $event The data for the event in the format:
	* array(
	*		'title' => 'Text for the title',
	*		'text' => 'The content of the event',
	*		'where' => 'The location for the event',
	*		'when' => array(
	*		'from' => '2008-09-03T12:00:00.000Z',
	*			'to' => '2008-09-03T13:00:00.000Z'
	*		)
	*	)
	*
	* @see ClientLogin
	* @see _connection
	* @see _sendRequest
	* @see _parseHeaders
	* @see 
	* @throws GoogleCalendarException Throws an exception on failure
	*
	* @return Boolean Returns true on success
	*/
	public function addSimpleEvent($event)
	{
		$request = "<entry xmlns='http://www.w3.org/2005/Atom' xmlns:gd='http://schemas.google.com/g/2005'>
			<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/g/2005#event'></category>";
		if (!strlen($event['title'])) {
			throw new GoogleCalendarException('No title specified',GoogleCalendarException::INVALIDEVENT);
		}
		$request .= "<title type='text'>" . htmlspecialchars($event['title'], ENT_QUOTES, SENDSTUDIO_CHARSET) . "</title>";
		if (strlen($event['text'])) {
			$request .= "<content type='text'>" . htmlspecialchars($event['text'], ENT_QUOTES, SENDSTUDIO_CHARSET) . "</content>";
		}
		$request .= "<gd:transparency value='http://schemas.google.com/g/2005#event.opaque'></gd:transparency> <gd:eventStatus value='http://schemas.google.com/g/2005#event.confirmed'></gd:eventStatus>";
		
		if (isset($event['where'])) {
			$request .= "<gd:where valueString='" . htmlspecialchars($event['where'], ENT_QUOTES, SENDSTUDIO_CHARSET) . "'></gd:where>";
		}
		
		if (!is_array($event['when'])) {
			throw new GoogleCalendarException('No when specified',GoogleCalendarException::INVALIDEVENT);
		}	elseif (!strlen($event['when']['from'])) {
			throw new GoogleCalendarException('No from when specified',GoogleCalendarException::INVALIDEVENT);
		}
		
		$request .= "<gd:when startTime='" . htmlspecialchars($event['when']['from'], ENT_QUOTES, SENDSTUDIO_CHARSET) . "'";
		if (strlen($event['when']['to'])) {
			$request .= " endTime='" . htmlspecialchars($event['when']['to'], ENT_QUOTES, SENDSTUDIO_CHARSET) ."'";
		}
		$request .= ">";
		$request .= "</gd:when></entry>";

		$connection =& $this->_connection();
		
		$headers =
			"POST /calendar/feeds/default/private/full HTTP/1.1\n" .
			"Content-Type: application/atom+xml\n" .
			"Host: www.google.com\n" .
			"Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2\n" .
			"Connection: keep-alive\n" .
			'Authorization: GoogleLogin auth="' . $this->_auth['Auth'] . '"';
		
		$response = $this->_sendRequest($connection,$headers,$request);
		$rheaders = $this->_parseHeaders($response['headers']);
		
		if ($rheaders['HTTP'] == '302') {
			$url = str_replace(array('https://www.google.com','http://www.google.com'),array('',''),$rheaders['location']);
			$headers =
				"POST $url HTTP/1.1\n" .
				"Content-Type: application/atom+xml\n" .
				"Host: www.google.com\n" .
				"Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2\n" .
				"Connection: keep-alive\n" .
				'Authorization: GoogleLogin auth="' . $this->_auth['Auth'] . '"';
				
			if (isset($rheaders['set-cookie'])) {
				$cookie = explode(';',$rheaders['set-cookie']);
				$headers .= "\n" . 'Cookie: ' . $cookie[0];
			}
			
			$response = $this->_sendRequest($connection,$headers,$request);
			$rheaders = $this->_parseHeaders($response['headers']);
		}
		
		if ($rheaders['HTTP'] == '201') {
			return true;
		} elseif ($rheaders['HTTP'] == '401') {
			throw new GoogleCalendarException("Authentication details were not accepted",GoogleCalendarException::BADAUTH);
		} else {
			throw new GoogleCalendarException("Received an HTTP {$rheaders['HTTP']} response, was expecting HTTP 200",GoogleCalendarException::UNEXCEPTEDRESPONSE);
		}
	}
}

/**
* GoogleCalendarException
* This is the type of exception that the GoogleCalendar class will throw. The error code for the exception will be one of the defined constants in the class.
*/
class GoogleCalendarException extends Exception
{
	/**
	* CONNECTION
	* There was a problem connecting to google.com
	*/
	const CONNECTION = 1;
	
	/**
	* BADAUTH
	* Authentication details were not accepted
	*/
	const BADAUTH = 2;
	
	/**
	* BADRESPONSE
	* A response couldn't be understood
	*/
	const BADRESPONSE = 3;
	
	/**
	* INVALIDEVENT
	* The calendar event specified was invalid
	*/
	const INVALIDEVENT = 4;
	
	/**
	* WRITERROR
	* There was an error sending data to google.com
	*/
	const WRITEERROR = 5;
	
	/**
	* READERROR
	* There was a problem reading from google.com
	*/
	const READERROR = 6;
	
	/**
	* UNEXPECTEDRESPONSE
	* An unexpected HTTP response was received
	*/
	const UNEXCEPTEDRESPONSE = 7;
}


?>