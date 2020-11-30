<?php

class Interspire_Validator_Uri extends Interspire_Validator_Abstract
{
	public function isValid()
	{
		return preg_match('/http:\/\/(www\.)(.+)\.(.+)/', $this->value);
	}
}