<?php
namespace comhon\exception;

class MalformedIntervalException extends \Exception {
	
	public function __construct($pInterval) {
		parent::__construct("interval '$pInterval' not valid", ConstantException::MALFORMED_INTERVAL_EXCEPTION);
	}
	
}