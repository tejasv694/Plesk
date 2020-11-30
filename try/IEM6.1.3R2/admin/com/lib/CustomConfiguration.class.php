<?php
/**
 * This file contains a "Custom Configuration" class
 *
 * @package interspire.iem.lib
 */

/**
 * Custom Configuration class
 * A class that will help reading "custom" configuration.
 *
 * Custom configurations are custom codes that will influence the behaviour of the main application.
 * It is similar to a application settings, but will not be configurable from the front end.
 *
 * Customization can range from changing the behaviour of a procedure, or specifying certain variables
 * to the application.
 *
 * Each customization can have a different method of infuencing the application.
 * This class only support the customization that made with the following format:
 * <code>
 * $custom = array(
 *   'some_key' => 'some_value',
 *   'some_key_2' => array(),
 *   'some_key_3' => false
 * );
 * </code>
 *
 * @package interspire.iem.lib
 */
class CustomConfiguration
{
	/**
	 * Customization cache
	 * @var Array Customization array cache
	 */
	static protected $cached = array();

	/**
	 * Current customization name
	 * @var String Current customization name
	 */
	protected $customizationName = false;




	/**
	 * CONSTRUCTOR
	 * @param String $customfile Customization file
	 */
	public function __construct($customfile)
	{
		if (!array_key_exists($customfile, self::$cached)) {
			$custom = array();

			$file = IEM_PATH . "/custom/{$customfile}.php";
			if (is_readable($file)) {
				@include $file;
			}

			self::$cached[$customfile] = $custom;
		}

		$this->customizationName = $customfile;
	}




	/**
	 * get
	 * Get the specified customization
	 *
	 * @param String $key Customization key
	 * @param Mixed $default Default value
	 * @param String $callback Callback function to be applied to the value
	 *
	 * @return Mixed Returns customization settings
	 */
	public function get($key, $default = null, $callback = null)
	{
		$value = $default;

		if (array_key_exists($key, self::$cached[$this->customizationName])) {
			$value = self::$cached[$this->customizationName][$key];
		}

		return $this->processValue($value, $callback);
	}




	/**
	 * processValue
	 * Process value before it got returned
	 *
	 * @param Mixed $value Value to be processed
	 * @param String $callback Callback function the value is to be processed against
	 *
	 * @return Mixed Processed value
	 */
	protected function processValue($value, $callback)
	{
		if (empty($callback)) {
			return $value;
		}

		if (is_array($value)) {
			foreach ($value as &$each) {
				$each = $this->processValue($each, $callback);
			}

			return $value;
		} else {
			return $callback($value);
		}
	}
}
