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
	
	public function castValue($pValue) {
		return (boolean) $pValue;
	}
	
	public function verifValue($pValue) {
		if (!is_bool($pValue)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument passed to {$lNodes[0]['class']}::{$lNodes[0]['function']}() must be a boolean, instance of $lClass given, called in {$lNodes[0]['file']} on line {$lNodes[0]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
	
}