<?php
namespace comhon\model;

use comhon\interfacer\Interfacer;
use comhon\interfacer\XMLInterfacer;
use comhon\interfacer\NoScalarTypedInterfacer;

class ModelBoolean extends SimpleModel {
	
	const ID = 'boolean';
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	public function exportSimple($pValue, Interfacer $pInterfacer) {
		if (is_null($pValue)) {
			return $pValue;
		}
		if ($pInterfacer instanceof XMLInterfacer) {
			return $pValue ? 1 : 0;
		}
		return $pValue;
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @return boolean|null
	 */
	public function importSimple($pValue, Interfacer $pInterfacer) {
		if (is_null($pValue)) {
			return $pValue;
		}
		if ($pInterfacer instanceof NoScalarTypedInterfacer) {
			$pValue = $pInterfacer->castValueToBoolean($pValue);
		}
		return $pValue;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		return $pValue ? 1 : 0;
	}
	
	protected function _fromXml($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pFlagAsUpdated = true, $pLocalObjectCollection = null) {
		return ((integer) $pValue === 1) ? true : false;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pFlagAsUpdated = true, $pLocalObjectCollection = null) {
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
		return true;
	}
	
}