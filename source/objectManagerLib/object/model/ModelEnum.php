<?php
namespace objectManagerLib\object\model;
use \Exception;

class ModelEnum extends ModelContainer {

	private $mEnum;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
	 */
	public final function __construct($pModel, $pEnum) {
		if (!($pModel instanceof SimpleModel)) {
			throw new Exception("model parameter must be an instanceof SimpleModel");
		}
		if (!is_array($pEnum)) {
			throw new Exception("enum parameter must be an array");
		}
		$this->mModel    = $pModel;
		$this->mEnum     = $pEnum;
	}
	
	protected function _toObject($pValue, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = $this->mModel->_toObject($pValue, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromObject($pValue, $pDateTimeZone, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromObject($pValue, $pDateTimeZone, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromSqlColumn($pValue, $pDateTimeZone, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromSqlColumn($pValue, $pDateTimeZone, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		$lReturn = $this->mModel->_toXml($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromXml($pValue, $pDateTimeZone, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromXml($pValue, $pDateTimeZone, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
}