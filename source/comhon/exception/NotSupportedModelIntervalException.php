<?php
namespace comhon\exception;

use comhon\model\Model;

class NotSupportedModelIntervalException extends \Exception {
	
	public function __construct(Model $pModel) {
		parent::__construct(
			"interval cannot be defined on model '{$pModel->getName()}'", 
			ConstantException::NOT_SUPPORTED_MODEL_INTERVAL_EXCEPTION
		);
	}
	
}