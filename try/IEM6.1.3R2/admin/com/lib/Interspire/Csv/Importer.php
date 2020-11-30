<?php

/**
 * @package Csv
 */

/**
 * Takes a CSV file or CSV string and converts it to an array.
 */
class Interspire_Csv_Importer extends Interspire_Csv_Abstract
{
	private
		/**
		 * @var string $_beforeParse
		 */
		$_beforeParse = '',
		
		/**
		 * @var array $_afterParse
		 */
		$_afterParse  = array(),
		
		/**
		 * @var array $_config
		 */
		$_config      = array(
				'delimiter' => ',',
				'enclosure' => '"',
				'newLine'   => "\n"
			);
	
	
	
	/**
	 * 
	 */
	public function __construct($csvToParse, $config = null)
	{
		$this->setConfig($config);
		
		if (is_file($csvToParse)) {
			$this->_beforeParse = file_get_contents($csvToParse);
		} else {
			$this->_beforeParse = $csvToParse;
		}
	}
	
	/**
	 * 
	 */
	public function parse()
	{
		// split the file into lines
		$lines = explode($this->_config['newLine'], $this->_beforeParse);
		
		// foreach line, we will parse it into columns and set the line to a columns array
		foreach ($lines as $lineIndex => $line) {
			// we add extra padding to the line so that if the last character was a delimiter
			// then it will catch that and add an empty column at the end
			$line       = $line . ' ';
			$open       = false;
			$lineLength = strlen($line);
			$colIndex   = 0;
			$cols       = array();
			
			for ($charIndex = 0; $charIndex < $lineLength; $charIndex++) {
				$lastChar    = $charIndex ? $line{$charIndex - 1} : '';
				$char        = $line{$charIndex};
				$nextChar    = $charIndex + 1 != $lineLength ? $line{$charIndex + 1} : '';
				$isEnclosure = $char == $this->_config['enclosure'] && $lastChar != '\\';
				
				if (!isset($cols[$colIndex])) {
					$cols[$colIndex] = '';
				}
				
				// if this an opening enclosure, set open to true and continue the loop
				if (!$open && $isEnclosure) {
					$open = true;
					
					continue;
				}
				
				// if this is a closing enclosure
				if ($open && $isEnclosure) {
					$open = false;
					
					continue;
				}
				
				if (!$open && $char == $this->_config['delimiter']) {
					$colIndex++;
					
					continue;
				}
				
				$cols[$colIndex] .= $char;
			}
			
			// iterate through each column and trim clean it up
			foreach ($cols as &$col) {
				$col = trim($col);
			}
			
			// set the current line
			$lines[$lineIndex] = $cols;
		}
		
		$this->_afterParse = $lines;
		
		return $this->_afterParse;
	}
}