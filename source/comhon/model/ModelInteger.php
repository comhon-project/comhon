<?php
namespace comhon\model;

use comhon\interfacer\Interfacer;
use comhon\interfacer\NoScalarTypedInterfacer;

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
	
	public function castValue($pValue) {
		return (integer) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_integer($pValue)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument passed to {$lNodes[0]['class']}::{$lNodes[0]['function']}() must be an integer, instance of $lClass given, called in {$lNodes[0]['file']} on line {$lNodes[0]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
}