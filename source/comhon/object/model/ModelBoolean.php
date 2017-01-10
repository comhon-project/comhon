<?php
namespace comhon\object\model;

class ModelBoolean extends SimpleModel {
	
	const ID = "boolean";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $pValue ? 1 : 0;
	}
	
	protected function _fromXml($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return ((integer) $pValue === 1) ? true : false;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		if (is_bool($pValue)) {
			return $pValue;
		}
		$lBoolean = filter_var($pValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
		if (is_null($lBoolean)) {
			if ($pValue === 't') {
				$lBoolean = true;
			} else if ($pValue === 'f') {
				$lBoolean = false;
			} else {
				$lBoolean = $pValue;
			}
		}
		return $lBoolean;
	}
	
	public function  isCheckedValueType($pValue) {
		return is_bool($pValue);
	}
	
	public function castValue($pValue) {
		return (boolean) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_bool($pValue)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be a boolean, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
	
}