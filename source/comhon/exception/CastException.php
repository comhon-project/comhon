<?php
namespace comhon\exception;
use \Exception;
use comhon\model\Model;

class CastException extends Exception {
	
	public function __construct(Model $pSourceModel, Model $pDestModel) {
		$lMessage = "Cannot cast object, '{$pSourceModel->getName()}' is not inherited from '{$pDestModel->getName()}'";
		parent::__construct($lMessage, ConstantException::CAST_EXCEPTION);
	}
	
}