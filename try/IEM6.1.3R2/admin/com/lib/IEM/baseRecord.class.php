<?php
/**
 *
 * @todo all
 *
 */
class IEM_baseRecord
{
	protected $properties = array();
	protected $data = array();




	public function __set($property, $value)
	{
		if (!array_key_exists($property, $this->properties)) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " -- Specified property '{$property}' does not exists", E_USER_ERROR);
		}

		$this->data[$property] = $value;
	}

	public function __get($property)
	{
		if (!array_key_exists($property, $this->properties)) {
			trigger_error(__CLASS__ . '::' . __METHOD__ . " -- Specified property '{$property}' does not exists", E_USER_ERROR);
		}

		return $this->data[$property];
	}

	public function __construct($values = array())
	{
		$this->data = array();

		foreach ($this->properties as $property => $data) {
			$this->data[$property] = (array_key_exists($property, $values) ? $values[$property] : $data);
		}
	}

	public function getPropertyList()
	{
		static $cache = null;

		if (is_null($cache)) {
			$cache = array_keys($this->properties);
		}

		return $cache;
	}

	public function getAssociativeArray()
	{
		return $this->data;
	}
}
