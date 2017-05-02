<?php
namespace comhon\model;

use comhon\interfacer\Interfacer;
use comhon\interfacer\NoScalarTypedInterfacer;
use comhon\utils\Utils;

class ModelInteger extends SimpleModel {
	
	const ID = 'integer';
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @return integer|null
	 */
	public function importSimple($pValue, Interfacer $pInterfacer) {
		if (is_null($pValue)) {
			return $pValue;
		}
		if ($pInterfacer instanceof NoScalarTypedInterfacer) {
			$pValue = $pInterfacer->castValueToInteger($pValue);
		}
		return $pValue;
	}
	
	public function  isCheckedValueType($pValue) {
		return is_int($pValue);
	}
	
	public function castValue($pValue) {
		return (integer) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_integer($pValue)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be an integer, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
}