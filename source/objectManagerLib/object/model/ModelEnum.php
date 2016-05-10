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
	
	public function toObject($pValue, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lReturn = $this->mModel->toObject($pValue, $pUseSerializationName, $pMainForeignObjects);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromObject($pValue, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromObject($pValue, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromSqlColumn($pValue, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromSqlColumn($pValue, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	public function toXml($pValue, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		$lReturn = $this->mModel->toXml($pValue, $pXmlNode, $pUseSerializationName, $pMainForeignObjects);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	protected function _fromXml($pValue, $pLocalObjectCollection) {
		$lReturn = $this->mModel->_fromXml($pValue, $pLocalObjectCollection);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
}