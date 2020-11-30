<?php

/**
 *
 */

/**
 *
 */
class Interspire_Validator
{
	private
		/**
		 *
		 */
		$_values = array(),

		/**
		 *
		 */
		$_validators = array(),

		/**
		 *
		 */
		$_errors = array();



	/**
	 *
	 */
	public function __construct($post = null, $validators = null)
	{
		$this->addValues($post);
		$this->addValidators($validators);
	}

	/**
	 *
	 */
	public function addValues($values, $value = null)
	{
		// if there are fields
		if ($values) {
			// allow an array as the first argument
			if (is_string($values)) {
				$values = array($values => $value);
			}

			// foreach of the fields, set its name as the key
			// and its value as the value
			foreach ($values as $valueId => $value) {
				$this->_values[$valueId] = $value;
			}
		}

		return $this;
	}

	/**
	 *
	 */
	public function removeValues($valueNames)
	{
		// remove each field passed
		foreach ((array) $valueNames as $valueName) {
			unset($this->_values[$valueName]);
		}

		return $this;
	}

	/**
	 *
	 */
	public function addValidators($values, $validators = null)
	{
		// if there are fields
		if ($values) {
			// allow an array as the first argument
			if (is_string($values)) {
				$values = array($values => $validators);
			}

			// foreach of the fields, add their corresponding rule
			foreach ($values as $valueId => $validators) {
				// strict coding
				if (!isset($this->_validators[$valueId])) {
					$this->_validators[$valueId] = array();
				}

				// accept a single instance or an array of instances
				if (!is_array($validators)) {
					$validators = array($validators);
				}

				// for each rule, add it to the field
				foreach ($validators as $validator) {
					$this->_validators[$valueId][] = $validator;
				}
			}
		}

		return $this;
	}

	/**
	 *
	 */
	public function removeValidators($values, $validator = null)
	{
		// accept both 2 arguments or an array as the first
		if (is_string($values)) {
			$values = array($values => $validator);
		}

		// go through each field passed, and remove the validation rules specified
		foreach ($values as $valueId => $validator) {
			// if the rule is null, then we will remove all validation rules for the current field
			if (is_null($validator)) {
				unset($this->_values[$valueId]);

				continue;
			}

			// otherwise loop through each validation rule set and remove it if it is
			// an isntance of the classname passed
			foreach ($this->_validators[$valueId] as $valueRuleKey => $valueRuleInstanceName) {
				if ($valueRuleInstanceName instanceof $validator) {
					unset($this->_validators[$valueId][$k]);
				}
			}
		}
	}

	/**
	 * Retrieves the errors. The array key contains the valueId/fieldName in which
	 * the error was thrown on and the value is an array of all the errors on that
	 * field. If a valueId is supplied, then only the error messages for that valueId
	 * are returned.
	 *
	 * @return Array
	 *
	 * @param $valueId[optional] The id of the field/value of the error messages to retrieve.
	 */
	public function getErrors($valueId = null)
	{
		// if set, return a single field's errors
		if ($valueId) {
			return $this->_errors[$valueId];
		}

		// otherwise, return all errors
		return $this->_errors;
	}

	/**
	 * Returns a flat array of all of the error messages
	 */
	public function getErrorMessages()
	{
		$errorMessages = array();

		foreach ($this->_errors as $msgs) {
			foreach ($msgs as $msg) {
				$errorMessages[] = $msg;
			}
		}

		return $errorMessages;
	}

	/**
	 * Validates the passed in fields. If validated, it can be called again to revalidate the
	 * fields if they were since modified.
	 */
	public function validate()
	{
		// reset the errors
		$this->_errors = array();

		// validate each rule against each field's value

		foreach ($this->_validators as $valueId => $validators) {
			foreach ($validators as $validator) {
				$validator->value = $this->_values[$valueId];

				if (!$validator->isValid()) {
					// if not already an array, set the fields errors to an array
					if (!isset($this->_errors[$valueId])) {
						$this->_errors[$valueId] = array();
					}

					// set the error message
					$this->_errors[$valueId][] = $validator->errorMessage;
				}
			}
		}

//		echo '<pre style="border: 1px solid red";><b style="color:RED;">YUDI_DEBUG:'. __FILE__ .' ON LINE: ' . __LINE__ . '</b><br />';
//		print_r($this->_validators);
//		echo '</pre>';
//		die;

		return $this;
	}

	/**
	 * Returns whether a specific field is valid or not based on the most recent call to Interspire_Validation::validate().
	 */
	public function isValid($valueId)
	{
		return !isset($this->_errors[$valueId]);
	}
}