<?php

abstract class Interspire_Validator_Abstract
{
	public 
		$value        = null,
		$errorMessage = null;
	
	abstract public function isValid();
}