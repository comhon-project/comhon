<?php
namespace comhon\object\model;

class Float extends SimpleModel {
	
	const ID = "float";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _fromXml($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return (float) $pValue;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return (float) $pValue;
	}
	
	public function  isCheckedValueType($pValue) {
		return is_float($pValue);
	}
	
	public function castValue($pValue) {
		return (float) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_numeric($pValue)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be a float or numeric, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
	
}