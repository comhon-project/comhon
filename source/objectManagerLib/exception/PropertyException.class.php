<?php
namespace objectManagerLib\exception;
use \Exception;

class PropertyException extends Exception {
	
	public function __construct($pModel, $pPropertyName) {
		$lMessage = "Unknown property '$lPropertyName' for model '{$pModel->getModelName()}'";
		parent::__construct($lMessage, ConstantException::PROPERTY_EXCEPTION);
	}
	
}