<?php
namespace comhon\model;

use comhon\model\singleton\ModelManager;
use comhon\interfacer\Interfacer;

abstract class SimpleModel extends Model {
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	public final function __construct() {
		$this->mIsLoaded = true;
		$this->_init();
	}
	
	public function isComplex() {
		return false;
	}
	
	public function getObjectClass() {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	public function getObjectInstance($pIsloaded = true) {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _export($pValue, $pNodeName, Interfacer $pInterfacer, $pIsFirstLevel) {
		return $pValue;
	}
	
	protected function _toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _fromStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection = null) {
		return $pValue;
	}
	
	public function fromXmlAttribute($pValue) {
		return $this->_fromXml($pValue);
	}
	
	protected function _fromXml($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection = null) {
		return (string) $pValue;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection = null) {
		return $pValue;
	}

	public function verifValue($pValue) {}
	

	public abstract function  isCheckedValueType($pValue);
	public abstract function castValue($pValue);
	
}