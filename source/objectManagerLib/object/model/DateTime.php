<?php
namespace objectManagerLib\object\model;

class DateTime extends SimpleModel {
	
	const ID = "dateTime";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	public function toObject($pValue, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $this->toString($pValue);
	}
	
	protected function _fromObject($pValue, $pLocalObjectCollection = null) {
		return $this->fromString($pValue);
	}
	
	public function toXml($pValue, $pXmlNode = null, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $this->toString($pValue);
	}
	
	protected function _fromXml($pValue, $pLocalObjectCollection = null) {
		return $this->fromString((string) $pValue);
	}
	
	public function toSqlColumn($pValue, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $this->toString($pValue);
	}
	
	protected function _fromSqlColumn($pValue, $pLocalObjectCollection = null) {
		return $this->fromString($pValue);
	}
	
	public function fromString($pValue) {
		return new \DateTime($pValue);
	}
	
	public function toString(\DateTime $pDateTime) {
		if (date_default_timezone_get() == $pDateTime->getTimezone()->getName()) {
			return $pDateTime->format('c');
		}
		else {
			$lDateTimeZone = $pDateTime->getTimezone();
			$pDateTime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
			$lDateTimeString =  $pDateTime->format('c');
			$pDateTime->setTimezone($lDateTimeZone);
			return $lDateTimeString;
		}
		//$pDateTime->setTimezone(new \DateTimeZone('Europe/Paris'));
		return $pDateTime->format('c');
	}
}