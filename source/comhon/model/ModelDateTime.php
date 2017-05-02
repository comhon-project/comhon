<?php
namespace comhon\model;

use comhon\object\ComhonDateTime;
use comhon\interfacer\Interfacer;
use comhon\interfacer\NoScalarTypedInterfacer;

class ModelDateTime extends SimpleModel {
	
	const ID = 'dateTime';
	
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
		return $this->toString($pValue, $pInterfacer->getDateTimeZone(), $pInterfacer->getDateTimeFormat());
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @return ComhonDateTime|null
	 */
	public function importSimple($pValue, Interfacer $pInterfacer) {
		if (is_null($pValue)) {
			return $pValue;
		}
		if ($pInterfacer instanceof NoScalarTypedInterfacer) {
			$pValue = $pInterfacer->castValueToString($pValue);
		}
		return $this->fromString($pValue, $pInterfacer->getDateTimeZone());
	}
	
	/**
	 * 
	 * @param string $pValue
	 * @param \DateTimeZone $pDateTimeZone
	 * @return ComhonDateTime
	 */
	public function fromString($pValue, \DateTimeZone $pDateTimeZone) {
		$lDateTime = new ComhonDateTime($pValue, $pDateTimeZone);
		if ($lDateTime->getTimezone()->getName() !== $pDateTimeZone->getName()) {
			$lDateTime->setTimezone($pDateTimeZone);
		}
		return $lDateTime;
	}
	
	public function toString(ComhonDateTime $pDateTime, $pDateTimeZone, $pDateFormat = 'c') {
		if ($pDateTimeZone->getName() == $pDateTime->getTimezone()->getName()) {
			return $pDateTime->format($pDateFormat);
		}
		else {
			$lDateTimeZone = $pDateTime->getTimezone();
			$pDateTime->setTimezone($pDateTimeZone);
			$lDateTimeString =  $pDateTime->format($pDateFormat);
			$pDateTime->setTimezone($lDateTimeZone);
			return $lDateTimeString;
		}
	}
	
	public function  isCheckedValueType($pValue) {
		return $pValue instanceof ComhonDateTime;
	}
	
	public function castValue($pValue) {
		throw new \Exception('cannot cast datetime object');
	}
	
	public function verifValue($pValue) {
		if (!($pValue instanceof ComhonDateTime)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be an instance of dateTime, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
}