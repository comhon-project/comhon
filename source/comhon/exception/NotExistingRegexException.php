<?php
namespace comhon\exception;

class NotExistingRegexException extends \Exception {
	
	public function __construct($pRegexName) {
		parent::__construct("regex with name '$pRegexName' doesn't exist", ConstantException::NOT_EXISTING_REGEX_EXCEPTION);
	}
	
}