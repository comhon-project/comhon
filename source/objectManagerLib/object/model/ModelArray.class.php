<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\ObjectArray;

class ModelArray extends ModelContainer {
	
	
	public function toObject($pObjectArray, $pUseSerializationName = false, $pExportForeignObject = false) {
		$lReturn = array();
		if (!$pObjectArray->isLoaded()) {
			$lReturn = null;
		}
		if (!is_null($pObjectArray)) {
			foreach ($pObjectArray->getValues() as $lKey => $lValue) {
				$lReturn[$lKey] = $this->mModel->toObject($lValue, $pUseSerializationName, $pExportForeignObject);
			}
		}
		return $lReturn;
	}
	
	public function toObjectId($pObjectArray, $pUseSerializationName = false) {
		$lReturn = array();
		if (!is_null($pObjectArray)) {
			foreach ($pObjectArray->getValues() as $lKey => $lValue) {
				$lReturn[$lKey] = $this->mModel->toObjectId($lValue, $pUseSerializationName);
			}
		}
		return $lReturn;
	}
	
	public function fromObject($pArray) {
		$lObjectArray = new ObjectArray($this);
		foreach ($pArray as $lKey => $lPhpValue) {
			$lObjectArray->setValue($lKey, $this->mModel->fromObject($lPhpValue));
		}
		return $lObjectArray;
	}
	
	public function fromSqlDataBase($pRows, $pLoadDepth = 0) {
		$lObjectArray = new ObjectArray($this);
		foreach ($pRows as $lKey => $lRow) {
			$lObjectArray->setValue($lKey, $this->mModel->fromSqlDataBase($lRow, $pLoadDepth));
		}
		return $lObjectArray;
	}
	
	public function toXml($pObjectArray, $pXmlNode, $pUseSerializationName = false, $pExportForeignObject = false) {
		if (!is_null($pObjectArray)) {
			foreach ($pObjectArray->getValues() as $lKey => $lValue) {
				$lXmlChildNode = $pXmlNode->addChild($this->mModel->getModelName());
				$this->mModel->toXml($lValue, $lXmlChildNode, $pUseSerializationName, $pExportForeignObject);
			}
		}
	}
	
	public function toXmlId($pObjectArray, $pXmlNode, $pUseSerializationName = false) {
		if (!is_null($pObjectArray)) {
			foreach ($pObjectArray->getValues() as $lKey => $lValue) {
				$lXmlChildNode = $pXmlNode->addChild($this->mModel->getModelName());
				$this->mModel->toXmlId($lValue, $lXmlChildNode, $pUseSerializationName);
			}
		}
	}
	
	public function fromXml($pXml) {
		$lObjectArray = new ObjectArray($this);
		$lChildrenModelName = $this->mModel->getModelName();
		foreach ($pXml->$lChildrenModelName as $lChild) {
			$lObjectArray->pushValue($this->mModel->fromXml($lChild));
		}
		return $lObjectArray;
	}
	
	public function fromIdValue($pArray) {
		$lObjectArray = new ObjectArray($this);
		foreach ($pArray as $lKey => $lPhpValue) {
			$lObjectArray->setValue($lKey, $this->mModel->fromIdValue($lPhpValue));
		}
		return $lObjectArray;
	}
	
	
	/*
	 * return true if $pArray1 and $pArray2 are equals
	 */
	public function isEqual($pArray1, $pArray2) {
		if (count($pArray1) != count($pArray2)) {
			return false;
		}
		foreach ($pArray1 as $lkey => $lValue1) {
			if (array_key_exists($lkey, $pArray2)) {
				$lValue2 = $pArray2[$lkey];
				if (!$lValue1->getModel()->isEqual($lValue1, $lValue2)) {
					return false;
				}
			}else {
				return false;
			}
		}
		return true;
	}
}