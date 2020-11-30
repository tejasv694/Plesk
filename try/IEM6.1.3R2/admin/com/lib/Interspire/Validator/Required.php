<?php

class Interspire_Validator_Required extends Interspire_Validator_Abstract
{
	public function isValid()
	{
		return (bool) $this->value;
	}
}