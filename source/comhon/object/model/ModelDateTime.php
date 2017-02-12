<?php
namespace comhon\object\model;

use comhon\object\object\config\Config;

class ModelDateTime extends SimpleModel {
	
	const ID = 'dateTime';
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $this->toString($pValue, $pDateTimeZone);
	}
	
	protected function _fromStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection = null) {
		return $this->fromString($pValue, $pDateTimeZone);
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $this->toString($pValue, $pDateTimeZone);
	}
	
	protected function _fromXml($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection = null) {
		return $this->fromString((string) $pValue,$pDateTimeZone);
	}
	
	protected function _toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $this->toString($pValue, $pDateTimeZone);
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection = null) {
		return $this->fromString($pValue, $pDateTimeZone);
	}
	
	/**
	 * 
	 * @param string $pValue
	 * @param \DateTimeZone $pDateTimeZone
	 * @return \DateTime
	 */
	public function fromString($pValue, \DateTimeZone $pDateTimeZone) {
		$lDateTime = new \DateTime($pValue, $pDateTimeZone);
		if ($lDateTime->getTimezone()->getName() !== $pDateTimeZone->getName()) {
			$lDateTime->setTimezone($pDateTimeZone);
		}
		return $lDateTime;
	}
	
	public function toString(\DateTime $pDateTime, $pDateTimeZone) {
		if ($pDateTimeZone->getName() == $pDateTime->getTimezone()->getName()) {
			return $pDateTime->format('c');
		}
		else {
			$lDateTimeZone = $pDateTime->getTimezone();
			$pDateTime->setTimezone($pDateTimeZone);
			$lDateTimeString =  $pDateTime->format('c');
			$pDateTime->setTimezone($lDateTimeZone);
			return $lDateTimeString;
		}
		
		return $pDateTime->format('c');
	}
	
	public function  isCheckedValueType($pValue) {
		return $pValue instanceof \DateTime;
	}
	
	public function castValue($pValue) {
		throw new \Exception('cannot cast datetime object');
	}
	
	public function verifValue($pValue) {
		if (!($pValue instanceof \DateTime)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be an instance of dateTime, instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
}