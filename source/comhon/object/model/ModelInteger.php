<?php
namespace comhon\object\model;

use comhon\utils\Utils;
class ModelInteger extends SimpleModel {
	
	const ID = "integer";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _fromXml($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return (integer) $pValue;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return (integer) $pValue;
	}
	
	public function  isCheckedValueType($pValue) {
		return is_int($pValue);
	}
	
	public function castValue($pValue) {
		return (integer) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_integer($pValue)) {
			trigger_error($pValue);
			Utils::printStack();
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be an integer, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
}