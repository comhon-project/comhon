<?php
namespace comhon\model;

class ModelString extends SimpleModel {
	
	const ID = 'string';
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	public function castValue($pValue) {
		return (string) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_string($pValue)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument passed to {$lNodes[0]['class']}::{$lNodes[0]['function']}() must be a string, instance of $lClass given, called in {$lNodes[0]['file']} on line {$lNodes[0]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
	
}