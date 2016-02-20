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
	
	public function toObject($pValue, $pUseSerializationName = false, $pExportForeignObject = false) {
		return $pValue;
	}
	
	public function fromObject($pValue) {
		return $pValue;
	}
	
	public function fromXml($pValue) {
		return (string) $pValue;
	}
	
	public function toXml($pValue, $pXmlNode = null, $pUseSerializationName = false, $pExportForeignObject = false) {
		return $pValue;
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1 == $pValue2;
	}
}