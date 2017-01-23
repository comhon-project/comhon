<?php
namespace comhon\object\model;

use comhon\object\singleton\ModelManager;

abstract class SimpleModel extends Model {
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	public final function __construct() {
		$this->mIsLoaded = true;
		$this->_init();
	}
	
	public function getObjectClass() {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	public function getObjectInstance($pIsloaded = true) {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	protected function _toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _fromStdObject($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return $pValue;
	}
	
	public function fromXmlAttribute($pValue) {
		return $this->_fromXml($pValue);
	}
	
	protected function _fromXml($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return (string) $pValue;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate = false, $pUseSerializationName = false, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return $pValue;
	}

	public function verifValue($pValue) {}
	

	public abstract function  isCheckedValueType($pValue);
	public abstract function castValue($pValue);
	
}