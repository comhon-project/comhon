<?php
namespace comhon\object\model;

class ModelString extends SimpleModel {
	
	const ID = "string";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	public function  isCheckedValueType($pValue) {
		return is_string($pValue);
	}
	
	public function castValue($pValue) {
		return (string) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_string($pValue)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be a string, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
	
}