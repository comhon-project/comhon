<?php
namespace ObjectManagerLib\objectManager\Model;
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
		$this->mModel = $pModel;
		$this->mEnum = $pEnum;
	}
	
	public function toObject($pValue, $pUseSerializationName = false, $pExportForeignObject = false) {
		$lReturn = $this->mModel->toObject($pValue, $pUseSerializationName, $pExportForeignObject);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	public function fromObject($pValue) {
		$lReturn = $this->mModel->fromObject($pValue);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
}