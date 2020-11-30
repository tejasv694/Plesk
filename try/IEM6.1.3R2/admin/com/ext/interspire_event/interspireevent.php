<?php
/**
 * This file contains classes that will provide functionalities for "Events"
 *
 * Contains the following class definition:
 * - InterspireEvent
 * - InterspireEventData
 * - InterspireEventException
 *
 * The main class that will be used on the framework is "InterspireEvent" static class
 *
 * @author Hendri <hendri@interspire.com>
 *
 * @package Library
 * @subpackage InterspireEvent
 */

/**
 * InterspireEvent class
 *
 * InterspireEvent is a static object of which function can be called straight away.
 * It provides "Event" framework for Interspire application.
 *
 * Event will have a priority between 1 and 100.
 * Where 1 will be executed first, and 100 is executed last.
 *
 * As a default, InterspireEvent class will be defaulted to STRICT MODE.
 * This means any event that is going to be triggered or listened to must first be created,
 * otherwise it will throw an exception.
 *
 * On a RELAXED MODE, when an event does not exists when you trigger/listen to it,
 * it will automatically create those events.
 *
 * To specify a STRICT MODE, simply call it with the default constructor:
 * <code>
 * ...
 * InterspireEvent::init($dataStorage);
 * ...
 * </code>
 *
 * To specify a RELAXED MODE, you need to add a second parameted:
 * <code>
 * ...
 * InterspireEvent::init($datStorage, false);
 * ...
 * </code>
 *
 * @package Library
 * @subpackage InterspireEvent
 */
class InterspireEvent
{
	/**
	 * Default storage key
	 */
	const DEFAULT_STORAGE_KEY = 'InterspireEvent';



	/**
	 * Listener list
	 * @var Mixed listener list
	 */
	static private $_listeners = null;

	/**
	 * Storage object to store listener to
	 * @var InterspireStash Storage engine
	 */
	static private $_storage = null;

	/**
	 * "Storage key" associated with this object to retrieve data from
	 * @var String Storage key
	 */
	static private $_storageKey = self::DEFAULT_STORAGE_KEY;

	/**
	 * Specify whether or not "Event" should be run in strict mode
	 * @var Boolean Strict mode
	 */
	static private $_strictMode = true;

	/**
	 * Holds a list of events that has already been loaded to improve loading performance
	 * @var Array A collection of event names
	 */
	static private $_eventsLoaded = array();

	/**
	 * Holds currently triggered events to make sure events that have been triggered is NOT triggered again
	 * @var Array A collection of currently triggered events
	 */
	static private $_currentEvents = array();



	/**
	 * init
	 * This function should be called to initialize this class before it is being used.
	 * When disabling STRICT MODE, triggering/listening to a non-existant event, the Event object will not
	 * throw an exception, rather it will create the event automatically.
	 *
	 * NOTE: This will read event listener from "InterspireStash", and populate the listener automatically
	 *
	 * @param InterspireStash $storage Storage object
	 * @param Boolean $strictMode Specify whether or not to run "Event" in strict mode (OPTIONAL, default = TRUE)
	 * @param String $storageKey Key name it should use to query the storage object (OPTIONAL)
	 *
	 * @throws InterspireEventException
	 *
	 * @return Void Returns nothing
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::CANNOT_READ_STORAGE
	 * @uses InterspireEventException::CANNOT_WRITE_STORAGE
	 */
	static public function init(InterspireStash $storage, $strictMode = true, $storageKey = self::DEFAULT_STORAGE_KEY)
	{
		self::$_storage = $storage;
		self::$_storageKey = $storageKey;
		self::$_strictMode = $strictMode;

		// Re-initialize loaded events
		self::$_eventsLoaded = array();

		if (!$storage->exists($storageKey)) {
			self::$_listeners = array();

			try {
				$storage->write($storageKey, self::$_listeners, true);
			} catch (InterspireStashException $e) {
				throw new InterspireEventException('Cannot write to storage:' . $e->getMessage(), InterspireEventException::CANNOT_WRITE_STORAGE);
			}
		} else {
			try {
				self::$_listeners = $storage->read($storageKey);
			} catch (InterspireStashException $e) {
				throw new InterspireEventException('Cannot read storage key: ' . $e->getMessage(), InterspireEventException::CANNOT_READ_STORAGE);
			}
		}
	}

	/**
	 * eventCreate
	 * Create an event
	 *
	 * NOTE: each time eventPublish is called, the class will also store
	 * the listener list array to a file.
	 *
	 * @param String $eventName Event name to be published
	 * @throws InterspireEventException
	 * @return Void Returns nothing
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 * @uses InterspireEventException::EVENT_EXISTS
	 * @uses InterspireEventException::CANNOT_WRITE_STORAGE
	 */
	static public function eventCreate($eventName) {
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		// Check if event is already registered, if it has, depending on the mode, it should either throw an exception or return
		if (self::eventExists($eventName)) {
			if (self::$_strictMode) {
				throw new InterspireEventException(sprintf("Event with the same identifier '%s' has already been published", $eventName), InterspireEventException::EVENT_EXISTS);
			}

			return;
		}

		self::$_listeners[$eventName] = array();

		// Write to data storage
		try {
			self::$_storage->write(self::$_storageKey, self::$_listeners, true);
		} catch(Exception $e) {
			// Revert back change if writing to data storage failed, and throw exception
			unset(self::$_listeners[$eventName]);
			throw new InterspireEventException('Cannot write to data storage: ' . $e->getMessage(), InterspireEventException::CANNOT_WRITE_STORAGE);
		}
	}

	/**
	 * eventExists
	 * Check whether or not an event has already been published
	 *
	 * @param String $eventName Event name to be checked
	 * @throws InterspireEventException
	 * @return Boolean Returns TRUE if event has been published, FALSE otherwise
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 */
	static public function eventExists($eventName) {
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		return array_key_exists($eventName, self::$_listeners);
	}

	/**
	 * eventRemove
	 * Remove an event
	 *
	 * NOTE: each time eventRemove is called, the class will also store
	 * the listener list array to a file.
	 *
	 * @param String $eventName Event name to be removed
	 * @param Boolean $forceRemove Remove events without checking if there are any listeners listening (OPTIONAL, default FALSE)
	 *
	 * @throws InterspireEventException
	 *
	 * @return Void Returns nothing
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 * @uses InterspireEventException::EVENT_NOT_EXISTS
	 * @uses InterspireEventException::EVENT_NOT_EMPTY
	 * @uses InterspireEventException::CANNOT_WRITE_STORAGE
	 */
	static public function eventRemove($eventName, $forceRemove = false) {
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		// Check if event exists, if it does not exists, depending on the mode, it should either throw an exception or return
		if (!self::eventExists($eventName)) {
			if (self::$_strictMode) {
				throw new InterspireEventException(sprintf("Event '%s' does not exist.", $eventName), InterspireEventException::EVENT_NOT_EXISTS);
			}

			return;
		}

		if ((!$forceRemove) && (count(self::$_listeners[$eventName]) != 0)) {
			throw new InterspireEventException(sprintf("Cannot remove event '%s' when it still has listeners.", $eventName), InterspireEventException::EVENT_NOT_EMPTY);
		}

		$tempHolder = self::$_listeners[$eventName];
		unset(self::$_listeners[$eventName]);

		if (array_key_exists($eventName, self::$_eventsLoaded)) {
			unset(self::$_eventsLoaded[$eventName]);
		}

		// Write to data storage
		try {
			self::$_storage->write(self::$_storageKey, self::$_listeners, true);
		} catch(Exception $e) {
			// Revert back change if writing to data storage failed, and throw exception
			self::$_listeners[$eventName] = $tempHolder;
			throw new InterspireEventException('Cannot write to data storage: ' . $e->getMessage(), InterspireEventException::CANNOT_WRITE_STORAGE);
		}
	}

	/**
	 * eventList
	 * List all of the available events
	 *
	 * @throws InterspireEventException
	 * @return Array Returns an array of string (Event names)
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 */
	static public function eventList() {
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		return array_keys(self::$_listeners);
	}

	/**
	 * trigger
	 * Trigger an event
	 *
	 * NOTE:
	 * - If the "listeners" file does not exists, it will not execute that particular listner... It will continue to the next listener, but it wil trigger a E_USER_NOTICE error
	 * - If the "listeners" function does not exists, it will not execute that particular listner... It will continue to the next listener, but it wil trigger a E_USER_NOTICE error
	 *
	 * @param String $eventName Event name
	 * @param InterspireEventData $data Instance of object that is, or extends, InterspireEventData to be passed to listener (OPTIONAL, default = NULL)
	 * @param Boolean $removeListenerWhenLoadFailed Remove event listener when files cannot be included (OPTIONAL, default = FALSE)
	 *
	 * @throw InterspireEventException
	 *
	 * @return Boolean Returns TRUE or FALSE depending on whether or not eventData preventDefault property
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 * @uses InterspireEventException::EVENT_NOT_EXISTS
	 * @uses InterspireEventException::EVENT_MULTIPLE_TRIGGER
	 */
	static public function trigger($eventName, InterspireEventData $data = null, $removeListenerWhenLoadFailed = false)
	{
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		// If no data was provided, create an instance of InterspireEventData so that propogation and cancelation can still work
		if (is_null($data)) {
			$data = new InterspireEventData();
		}

		// If events has been loaded before (ie. has been checked), do not load again
		if (!array_key_exists($eventName, self::$_eventsLoaded)) {
			self::$_eventsLoaded[$eventName] = true;

			// Check if event has been registered, if not depending on the mode, it will either throw a fit, or create the event
			if (!array_key_exists($eventName, self::$_listeners)) {
				if (self::$_strictMode) {
					throw new InterspireEventException(sprintf("Event '%s' has not been published.", $eventName), InterspireEventException::EVENT_NOT_EXISTS);
				}

				self::eventCreate($eventName);
			}

			// Check if the event has been triggered
			// This will prevent listeners throwing the same event more than once in the same event tree.
			// Otherwise the listener can create an infinite loop of trigger.
			if (array_key_exists($eventName, self::$_currentEvents)) {
				throw new InterspireEventException(sprintf("Event '%s' has already been triggered in current event tree.", $eventName), InterspireEventException::EVENT_MULTIPLE_TRIGGER);
			}

			// ----- Execute listeners
				self::$_currentEvents[$eventName] = true;

				foreach (self::$_listeners[$eventName] as $priority=>$listeners) {
					foreach ($listeners as $each) {
						// Include file if specified.
						// If file cannot be read, trigger E_USER_NOTICE error so that user get notified
						// (or there is an entry in the error log about it), and skip the listener
						if (!is_null($each['file'])) {
							$actual_file = $each['file'];

							// Replace any "placeholders"
							if (preg_match_all('/\{%(.*?)%\}/', $actual_file, $matches)) {
								foreach ($matches[0] as $index => $search_string) {
									$const_name = $matches[1][$index];
									$const_value = defined($const_name) ? constant($const_name) : false;

									if ($const_value) {
										$actual_file = str_replace($search_string, $const_value, $actual_file);
									}
								}
							}

							if (is_readable($actual_file)) {
								require_once($actual_file);
							} else {
								if ($removeListenerWhenLoadFailed) {
									self::listenerUnregister($eventName, $each['function'], $each['file'], $priority);
								} else {
									trigger_error('Cannot include file: ' . $each['file'] . ', that is set to be included when ' . $eventName . ' is being triggered', E_USER_NOTICE);
								}

								continue;
							}
						}

						// Check if function can be called.
						// If function cannot be called, trigger E_USER_NOTICE error so that user get notified
						// (or there is an entry in the error log about it), and skip the the listener
						if (!is_callable($each['function'])) {
							if ($removeListenerWhenLoadFailed) {
								self::listenerUnregister($eventName, $each['function'], $each['file'], $priority);
							} else {
								trigger_error('Cannot call function: ' . print_r($each['function'], true) . ', that needed to be called when ' . $eventName . ' is being triggered', E_USER_NOTICE);
							}
							continue;
						}

						call_user_func($each['function'], $data);

						// Check whether or not to continue to propagate the event
						if ($data->getStopPropagation()) {
							break;
						}
					}
				}

				unset(self::$_currentEvents[$eventName]);
			// -----


		// Since event has been loaded before, no need to check anything (or load any files)
		} else {
			self::$_currentEvents[$eventName] = true;

			foreach (self::$_listeners[$eventName] as $priority=>$listeners) {
				foreach ($listeners as $each) {
					call_user_func($each['function'], $data);

					// Check whether or not to continue to propagate the event
					if ($data->getStopPropagation()) {
						break;
					}
				}
			}

			unset(self::$_currentEvents[$eventName]);
		}

		return !$data->getPreventDefault();
	}

	/**
	 * eventListenerList
	 * Returns a list of listeners for a given event
	 *
	 * @param String $eventName Event to get a list of listeners for
	 *
	 * @throw InterspireEventException
	 *
	 * @return Array Returns an associative array of listener information with the keys 'priority', 'file' and 'function'
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 * @uses InterspireEventException::EVENT_NOT_EXISTS
	 */
	static public function eventListenerList($eventName)
	{
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		// Check if event has been registered
		if (!self::eventExists($eventName)) {
			if (self::$_strictMode) {
				throw new InterspireEventException(sprintf("Event '%s' has not been published.", $eventName), InterspireEventException::EVENT_NOT_EXISTS);
			}

			self::eventCreate($eventName);
		}

		$list = array();

		foreach (self::$_listeners[$eventName] as $priority => $listeners) {
			foreach ($listeners as $listener) {
				$list[] = array(
					'priority' => intval($priority),
					'file' => $listener['file'],
					'function' => $listener['function']
				);
			}
		}

		return $list;
	}

	/**
	 * listenerRegister
	 * Register a listener against an event
	 *
	 * NOTE:
	 * - Special placeholder has been added to the $file parameter which will allow you to add relative path to the file.
	 * - To use this, simply use the following notation: {%CONSTANT_NAME%}
	 * - This will replace anything in the middle of the notation with a defined constant
	 * - For example:
	 * -- define('IEM_PUBLIC_PATH', '/home/beta/hendri/iem/admin');
	 * -- this will evaluate: {%IEM_PUBLIC_PATH%}/addons/splittest/common.php
	 * -- to: /home/beta/hendri/iem/admin/addons/splittest/common.php
	 *
	 * NOTE: each time registerListener is called, the class will also store the listener list array to a file.
	 *
	 * @param String $eventName Event to be observed
	 * @param String|Array $function Function to be executed
	 * @param String|NULL $file File to be included before executing the function (OPTIONAL, dafault = NULL)
	 * @param Integer $priority Priority of the listener (1 to 100) (OPTIONAL, default = 50)
	 * @param Boolean $temporary Whether or not the listener should be saved to the file  -- useful for temporary listeners
	 *
	 * @throws InterspireEventException
	 *
	 * @return Void Returns nothing
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 * @uses InterspireEventException::EVENT_NOT_EXISTS
	 * @uses InterspireEventException::INVALID_PRIORITY
	 * @uses InterspireEventException::LISTENER_EXISTS
	 */
	static public function listenerRegister($eventName, $function, $file = null, $priority = 50, $temporary = false)
	{
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		// Check if event has been registered
		if (!self::eventExists($eventName)) {
			if (self::$_strictMode) {
				throw new InterspireEventException(sprintf("Event '%s' has not been published.", $eventName), InterspireEventException::EVENT_NOT_EXISTS);
			}

			self::eventCreate($eventName);
		}

		// Check if listener exists
		if (self::listenerExists($eventName, $function, $file, $priority)) {
			if (self::$_strictMode) {
				throw new InterspireEventException(sprintf("The specified listner has already been registered for event '%s'.", $eventName), InterspireEventException::LISTENER_EXISTS);
			}

			return;
		}

		$priority = intval($priority);

		if ($priority < 1 || $priority > 100) {
			throw new InterspireEventException('Priority must be greater than or equal to 1 and less than or equal to 100 (1 to 100).', InterspireEventException::INVALID_PRIORITY);
		}

		// Format priority so it will be sorted correctly
		$priority = sprintf('%03d', $priority) . ' ';

		// Copy existing event to local variable so that it can be reverted in case of a failure
		$eventListener = self::$_listeners;

		if (!array_key_exists($priority, $eventListener[$eventName])) {
			$eventListener[$eventName][$priority] = array();
		}

		array_push($eventListener[$eventName][$priority], array(
			'file' => $file,
			'function' => $function
		));

		$eventListener[$eventName] = self::_sortByPriority($eventListener[$eventName]);

		if(!$temporary){
			// Write to data storage
			try {
				self::$_storage->write(self::$_storageKey, $eventListener, true);
			} catch(Exception $e) {
				throw new InterspireEventException('Cannot write to data storage: ' . $e->getMessage(), InterspireEventException::CANNOT_WRITE_STORAGE);
			}
		}

		if (array_key_exists($eventName, self::$_eventsLoaded)) {
			unset(self::$_eventsLoaded[$eventName]);
		}

		// Copy back the event listener array with the newly added listener
		self::$_listeners = $eventListener;
	}

	/**
	 * listenerUnregister
	 * Unregister a listener from an event.
	 * The parameters must be the same as when listener was registered.
	 *
	 * NOTE: each time unregisterListener is called, the class will also store
	 * the listener list array to a file.
	 *
	 * @param String $eventName Event to be observed
	 * @param String|Array $function Function to be executed
	 * @param String $file File to be included before executing the function (OPTIONAL, dafault = NULL)
	 * @param Integer $priority Priority of the listener (1 to 100) (OPTIONAL, default = 50)
	 * @param Boolean $temporary Whether or not it should be removed from the file -- useful for temporary listeners
	 *
	 * @throws InterspireEventException
	 *
	 * @return Void Returns nothing
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 * @uses InterspireEventException::INVALID_PRIORITY
	 * @uses InterspireEventException::LISTENER_NOT_FOUND
	 */
	static public function listenerUnregister($eventName, $function, $file = null, $priority = 50, $temporary = false) {
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		$priority = intval($priority);

		if ($priority < 1 || $priority > 100) {
			throw new InterspireEventException('Priority must be greater than or equal to 1 and less than or equal to 100 (1 to 100).', InterspireEventException::INVALID_PRIORITY);
		}

		// Format priority so it will be sorted correctly
		$priority = sprintf('%03d', $priority) . ' ';

		if (!isset(self::$_listeners[$eventName][$priority])) {
			if (self::$_strictMode) {
				throw new InterspireEventException(sprintf("Listener not found for event '%s'.", $eventName), InterspireEventException::LISTENER_NOT_FOUND);
			}

			return;
		}

		// Copy existing event to local variable so that it can be reverted in case of a failure
		$eventListener = self::$_listeners;

		/**
		 * Find and remove the listner from listener array
		 */
		$foundFlag = false;

		$comparable = array('function' => $function, 'file' => $file);
		reset($eventListener[$eventName][$priority]);
		while (list($index, $each) = each($eventListener[$eventName][$priority])) {
			if ($each == $comparable) {
				unset($eventListener[$eventName][$priority][$index]);

				if (count($eventListener[$eventName][$priority]) == 0) {
					unset($eventListener[$eventName][$priority]);
				}

				$foundFlag = true;
				break;
			}
		}

		if (!$foundFlag) {
			if (self::$_strictMode) {
				throw new InterspireEventException(sprintf("Listener not found for event '%s'.", $eventName), InterspireEventException::LISTENER_NOT_FOUND);
			}

			return;
		}

		/**
		 * -----
		 */

		if(!$temporary){
			// Write to data storage
			try {
				self::$_storage->write(self::$_storageKey, $eventListener, true);
			} catch(Exception $e) {
				throw new InterspireEventException('Cannot write to data storage: ' . $e->getMessage(), InterspireEventException::CANNOT_WRITE_STORAGE);
			}
		}

		if (array_key_exists($eventName, self::$_eventsLoaded)) {
			unset(self::$_eventsLoaded[$eventName]);
		}

		self::$_listeners = $eventListener;
	}

	/**
	 * listenerExists
	 * Check if listener exists
	 *
	 * @param String $eventName Listener's event to be checked
	 * @param String|Array $function Listener's function to be checked
	 * @param String $file Listener's file (OPTIONAL, dafault = NULL)
	 * @param Integer $priority Listener's priority (1 to 100) (OPTIONAL, default = 50)
	 *
	 * @throws InterspireEventException
	 *
	 * @return Boolean Returns TRUE if exists, FALSE otherwise
	 *
	 * @uses InterspireEventException
	 * @uses InterspireEventException::NOT_INITIALIZED
	 * @uses InterspireEventException::INVALID_PRIORITY
	 */
	static public function listenerExists($eventName, $function, $file = null, $priority = 50) {
		// Check if class has been initialized
		if (is_null(self::$_listeners)) {
			throw new InterspireEventException('InterspireEvent class has not yet been initialized.', InterspireEventException::NOT_INITIALIZED);
		}

		$priority = intval($priority);

		if ($priority < 1 || $priority > 100) {
			throw new InterspireEventException('Priority must be greater than or equal to 1 and less than or equal to 100 (1 to 100).', InterspireEventException::INVALID_PRIORITY);
		}

		// Format priority so it will be sorted correctly
		$priority = sprintf('%03d', $priority) . ' ';

		if (!isset(self::$_listeners[$eventName][$priority])) {
			return false;
		}

		$comparable = array('function' => $function, 'file' => $file);
		reset(self::$_listeners[$eventName][$priority]);
		while (list($index, $each) = each(self::$_listeners[$eventName][$priority])) {
			if ($each == $comparable) {
				return true;
			}
		}

		return false;
	}



	/**
	 * _sortByPriority
	 * Sort listener array by priority
	 *
	 * @param Array $arrayToBeSorted Array to be sorted
	 * @return Array Returns sorted array
	 */
	static private function _sortByPriority($arrayToBeSorted)
	{
		$keys = array_keys($arrayToBeSorted);
		array_multisort($keys, $arrayToBeSorted);
		return $arrayToBeSorted;
	}
}

/**
 * InterspireEventException class
 * This is an exception class that will be triggered by the Event and Event related functions.
 *
 * @package Library
 * @subpackage InterspireEvent
 */
class InterspireEventException extends Exception
{
	const INTERNAL_ERROR			= 1;
	const CANNOT_READ_STORAGE		= 3;
	const CANNOT_WRITE_STORAGE		= 4;
	const INVALID_PRIORITY			= 5;
	const NOT_INITIALIZED			= 6;
	const LISTENER_NOT_FOUND		= 7;
	const LISTENER_EXISTS			= 8;
	const EVENT_NOT_EXISTS			= 9;
	const EVENT_EXISTS				= 10;
	const EVENT_NOT_EMPTY			= 11;
	const EVENT_MULTIPLE_TRIGGER	= 12;
}




/**
 * InterspireEventData class
 * This class is used by Event class to pass around the event data.
 * It includes any data passed by the triggering function, and a way for the event listener to cancel or prevent default action.
 *
 * The "InterspireEventData" object can also be used to:
 * - Trigger it's event
 * - Add listener to it's event
 *
 * Example to trigger event:
 * <code>
 * ...
 * $event = new InterspireEventData_IEM_ADD_SUBSCRIBER();
 * $event->property1 = &$subscriberRecord;
 * if ($event->trigger()) {
 *     print "Event have been canceled";
 * }
 * ...
 * </code>
 *
 * Example to add listener to event:
 * <code>
 * $listeningFunction = 'somefunction';
 * $fileToInclude = __FILE__;
 * $priority = 50;
 *
 * $event = new InterspireEventData_IEM_ADD_SUBSCRIBER();
 * $event->listen($listeningFunction, $fileToInclude, $priority);
 * </code>
 *
 * The "triggering" and "registering listener" procedure itself will be delegated to the
 * appropriate functions in the InterspireEvent class.
 *
 * @package Library
 * @subpackage InterspireEvent
 */
class InterspireEventData
{
	/**
	 * Holds the event name
	 * @var String event name
	 */
	protected $_eventName = '';




	/**
	 * A flag to store wheter or not the event is cancellable by the listener
	 * @var Boolean
	 */
	private $_cancelable = true;

	/**
	 * A flag to store whether or not the event should be stopped
	 * @var Boolean
	 */
	private $_stopPropagation = false;

	/**
	 * A flag to store whether or not the triggering function should prevent default action
	 * @var Boolean
	 */
	private $_preventDefault = false;



	/**
	 * __construct
	 * @param Boolean $cancelable Whether or not this event can be canceled (OPTIONAL, default = FALSE)
	 * @return EventData Return this object
	 */
	public function __construct($cancelable = true)
	{
		if ($cancelable === false) {
			$this->_cancelable = false;
		} else {
			$this->_cancelable = true;
		}
	}

	/**
	 * getCancelable
	 * "cancelable" property accessor
	 * @return Boolean Returns cancelable state
	 */
	public function getCancelable ()
	{
		return $this->_cancelable;
	}

	/**
	 * preventDefault
	 * Set "preventDefault" flag to TRUE
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function preventDefault()
	{
		if ($this->_cancelable) {
			$this->_preventDefault = true;
			return true;
		}

		return false;
	}

	/**
	 * stopPropagation
	 * Set "stopPropagation" flag to TRUE
	 * @return Boolean Returns TRUE if successful, FALSE otherwise
	 */
	public function stopPropagation()
	{
		$this->_stopPropagation = true;
		return true;
	}

	/**
	 * getPreventDefault
	 * "preventDefault" property accessor
	 * @return Boolean Returns preventDefault state
	 */
	public function getPreventDefault()
	{
		return $this->_preventDefault;
	}

	/**
	 * getPreventDefault
	 * "stopPropagation" property accessor
	 * @return Boolean Returns stopPropagation state
	 */
	public function getStopPropagation()
	{
		return $this->_stopPropagation;
	}

	/**
	 * trigger
	 * Trigger the event that is associated with the event
	 * @param Boolean $removeListenerWhenLoadFailed Remove the listener when file loading failed (OPTIONAL, default = FALSE)
	 * @return Boolean Returns TRUE or FALSE depending on whether or not eventData preventDefault property
	 */
	public function trigger($removeListenerWhenLoadFailed = false)
	{
		if (empty($this->_eventName)) {
			// This peice of code will never be reached if the class has been constructed properly
			die('Please specify the "_eventName" property first in order to use this function');
		}

		return InterspireEvent::trigger($this->_eventName, $this, $removeListenerWhenLoadFailed);
	}

	/**
	 * listen
	 * Register a listener to "listen" to this event
	 * @param String|Array $listener Listener function
	 * @param String|NULL $file File to be included before executing the function (OPTIONAL, dafault = NULL)
	 * @param Integer $priority Priority of the listener (1 to 100) (OPTIONAL, default = 50)
	 * @return Void Returns nothing
	 */
	public function listen($listener, $file = null, $priority = 50)
	{
		if (empty($this->_eventName)) {
			// This peice of code will never be reached if the class has been constructed properly
			die('Please specify the "_eventName" property first in order to use this function');
		}

		return InterspireEvent::listenerRegister($this->_eventName, $listener, $file, $priority);
	}
}
