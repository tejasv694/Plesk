<?php
/**
 * @package Csv
 */

/** 
 * Class contains abstract methods to outline a design patter for all
 * classes within the Csv package.
 */
abstract class Interspire_Csv_Abstract
{
	private
		/**
		 * @var array $_config
		 */
		$_config = array();
	
	
	
	/**
	 * Sets a single or multiple configuration variables.
	 * 
	 * @return object
	 * 
	 * @param mixed $name  Either a string name of the configuration variable to set
	 *                     with $value, or an array of key value pairs.
	 * @param mixed $value The value of the variable to be set. Not used if $name is
	 *                     an array.
	 */
	public function setConfig($name, $value = null)
	{
		if (!is_array($name)) {
			$name = array($name => $value);
		}
	
		foreach ($name as $configName => $configValue) {
			if (isset($this->_config[$configName])) {
				$this->_config[$configName] = $configValue;
			}
		}
		
		return $this;
	}
	
	/**
	 * Retrieves a configuration variable.
	 * 
	 * @return mixed
	 * 
	 * @param string $name The name of the configuration variable you want to retrieve.
	 */
	public function getConfig($name)
	{
		return $this->_config[$name];
	}
}