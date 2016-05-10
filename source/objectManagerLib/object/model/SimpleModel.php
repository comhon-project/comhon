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
	
	public function toObject($pValue, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	/**
	 * 
	 * @param unknown $pValue
	 * @param string $pLocalObjectCollection not used but is mandatory to stay compatible with parent function
	 * @return unknown
	 */
	protected function _fromObject($pValue, $pLocalObjectCollection = null) {
		return $pValue;
	}
	
	protected function _fromXml($pValue, $pLocalObjectCollection = null) {
		return (string) $pValue;
	}
	
	public function toXml($pValue, $pXmlNode = null, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $pValue;
	}
	
	protected function _fromSqlColumn($pValue, $pLocalObjectCollection = null) {
		return $pValue;
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1 == $pValue2;
	}
}