<?php
namespace objectManagerLib\object\model;

class EnumProperty extends Property {
	
	protected $mEnum;
	
	public function __construct($pModel, $pName, $pEnum, $pSerializationName = null) {
		$this->mIsId  = false;
		$this->mModel = $pModel;
		$this->mName  = $pName;
		$this->mEnum  = $pEnum;
		$this->mSerializationName = $pSerializationName;
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
	
	public function toXml($pValue, $pXmlNode, $pUseSerializationName = false, $pExportForeignObject = false) {
		$lReturn = $this->mModel->toXml($pValue, $pUseSerializationName, $pExportForeignObject);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
	public function fromXml($pValue) {
		$lReturn = $this->mModel->fromXml($pValue);
		if (!in_array($lReturn, $this->mEnum)) {
			$lReturn = null;
		}
		return $lReturn;
	}
	
}