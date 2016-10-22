<?php
namespace comhon\exception;
use \Exception;

class ControllerParameterException extends Exception {
	
	public function __construct($pParameterName = null) {
		$lMessage = is_null($pParameterName) ? 'Bad parameters definition : must be an array or null'
											 : "Missing parameter : '$pParameterName' must be specified";
		
		parent::__construct($lMessage, ConstantException::CONTROLLER_PARAMETER_EXCEPTION);
	}
	
}