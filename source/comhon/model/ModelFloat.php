<?php
namespace comhon\model;

use comhon\interfacer\Interfacer;
use comhon\interfacer\NoScalarTypedInterfacer;

class ModelFloat extends SimpleModel {
	
	const ID = 'float';
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @return float|null
	 */
	public function importSimple($pValue, Interfacer $pInterfacer) {
		if (is_null($pValue)) {
			return $pValue;
		}
		if ($pInterfacer instanceof NoScalarTypedInterfacer) {
			$pValue = $pInterfacer->castValueToFloat($pValue);
		}
		return $pValue;
	}
	
	protected function _fromXml($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pFlagAsUpdated = true, $pLocalObjectCollection = null) {
		return (float) $pValue;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pFlagAsUpdated = true, $pLocalObjectCollection = null) {
		return (float) $pValue;
	}
	
	public function  isCheckedValueType($pValue) {
		return is_float($pValue) || is_integer($pValue);
	}
	
	public function castValue($pValue) {
		return (float) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!(is_float($pValue) || is_integer($pValue))) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be a float or integer, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
	
}