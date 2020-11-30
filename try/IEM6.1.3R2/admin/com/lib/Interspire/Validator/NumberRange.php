<?php

class Interspire_Validator_NumberRange extends Interspire_Validator_Abstract
{
	public
		$startRange,
		$endRange;
	
	
	
	public function __construct($startRange = 0, $endRange = 0)
	{
		$this->startRange = (int) $startRange;
		$this->endRange   = (int) $endRange;
	}
	
	
	
	public function isValid()
	{
		$this->value = (int) $this->value;
		
		if ($this->value >= $this->startRange) {
			if ($this->endRange && $this->value > $this->endRange) {
				return false;
			}
			
			return true;
		}
		
		return false;
	}
}