<?php
namespace comhon\exception;
use \Exception;

class ReservedWordException extends Exception {
	
	public function __construct($pWord) {
		parent::__construct("reserved word '$pWord' cannot be used in manifest", ConstantException::RESERVED_WORD_EXCEPTION);
	}
	
}