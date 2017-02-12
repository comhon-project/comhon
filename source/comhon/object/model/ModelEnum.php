<?php
namespace comhon\object\model;
use \Exception;

class ModelEnum extends ModelContainer {

	private $mEnum;
	
	public final function __construct($pModel, $pEnum) {
		if (!($pModel instanceof SimpleModel)) {
			throw new Exception('model parameter must be an instanceof SimpleModel');
		}
		if (!is_array($pEnum)) {
			throw new Exception('enum parameter must be an array');
		}
		$this->mModel    = $pModel;
		$this->mEnum     = $pEnum;
	}
	
	public function getEnum() {
		return $this->mEnum;
	}
	
	protected function _toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = $this->mModel->_toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = $this->mModel->_toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = $this->mModel->_toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromXml($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromXml($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	public function verifValue($pValue) {
		$this->mModel->verifValue($pValue);
		if (!in_array($pValue, $this->mEnum)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument 2 passed to {$lNodes[1]['class']}::{$lNodes[1]['function']}() must be in enumeration ".json_encode($this->mEnum).", instance of $lClass given, called in {$lNodes[1]['file']} on line {$lNodes[1]['line']} and defined in {$lNodes[0]['file']}");
		}
	}
	
}