<?php

class Interspire_Validator_ImageSize extends Interspire_Validator_Abstract
{

	public $filesize;
	public $filelimit = 2000000;

	public function __construct($filesize) {
			$this->filesize = $filesize;

	}

	public function isValid()
	{

		if ($this->filesize <= $this->filelimit) {

			return true;
		}

		return false;
	}
}