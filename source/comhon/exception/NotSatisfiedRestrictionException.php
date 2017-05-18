<?php
namespace comhon\exception;

use comhon\model\restriction\Restriction;

class NotSatisfiedRestrictionException extends \Exception {
	
	public function __construct($pValue, Restriction $pRestriction) {
		$lMessage = $pRestriction->toString($pValue);
		parent::__construct($lMessage, ConstantException::NOT_SATISFIED_RESTRICTION_EXCEPTION);
	}
	
}