<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\singleton\InstanceModel;

abstract class SimpleModel extends Model {
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
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
	
	protected function _toObject($pValue, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _fromObject($pValue, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return $pValue;
	}
	
	protected function _fromXml($pValue, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return (string) $pValue;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _fromSqlColumn($pValue, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return $pValue;
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1 == $pValue2;
	}
	
	public function verifValue($pValue) {}
}