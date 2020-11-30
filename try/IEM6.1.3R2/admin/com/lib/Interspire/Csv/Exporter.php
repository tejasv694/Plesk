<?php

/**
 * @package Csv
 */

/** 
 * Exports a valid array to a CSV string.
 */
class Interspire_Csv_Exporter extends Interspire_Csv_Abstract
{
	private
		/**
		 * @var array $_beforeParse
		 */
		$_beforeParse = array(),
		
		/**
		 * @var string $_afterParse
		 */
		$_afterParse  = '',
		
		/**
		 * @var array $_config
		 */
		$_config      = array(
				'delimiter' => ',',
				'enclosure' => '"',
				'newLine'   => "\n"
			);
	
	
	
	/**
	 * Constructs the exporter and takes an array as its only argument.
	 * This is the array that will be parsed into a CSV file.
	 * 
	 * @return object
	 * 
	 * @param string $arrToExport The array to convert to a CSV file.
	 */
	public function __construct($arrToExport, $config = null)
	{
		$this->setConfig($config);
		
		if ($arrToExport instanceof Interspire_Csv_Importer) {
			$this->_beforeParse = $arrToExport->parse();
		} else {
			$this->_beforeParse = $arrToExport;
		}
	}
	
	/**
	 * Parses the array passed to __construct as a valid CSV file, given that the
	 * array supplied was in the correct format.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		$lines = array();
		
		foreach ($this->_beforeParse as &$lineFields) {
			foreach ($lineFields as &$field) {
				// if a delimiter is found within the field, enclose it with the enclosure
				if (false !== strpos($field, $this->_config['delimiter']) || false !== strpos($field, $this->_config['newLine'])) {
					$field = $this->_config['enclosure'] . $field . $this->_config['enclosure'];
				}
			}
			
			$lines[] = implode($this->_config['delimiter'], $lineFields);
		}
		
		return implode($this->_config['newLine'], $lines);
	}
}