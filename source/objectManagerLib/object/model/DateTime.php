<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\Config;

class DateTime extends SimpleModel {
	
	const ID = "dateTime";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _toObject($pValue, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $this->toString($pValue, $pDateTimeZone);
	}
	
	protected function _fromObject($pValue, $pDateTimeZone, $pLocalObjectCollection = null) {
		return $this->fromString($pValue, $pDateTimeZone);
	}
	
	protected function _toXml($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $this->toString($pValue, $pDateTimeZone);
	}
	
	protected function _fromXml($pValue, $pDateTimeZone, $pLocalObjectCollection = null) {
		return $this->fromString((string) $pValue,$pDateTimeZone);
	}
	
	protected function _fromSqlColumn($pValue, $pDateTimeZone, $pLocalObjectCollection = null) {
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
}