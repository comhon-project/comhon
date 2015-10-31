<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\ObjectArray;

class ModelForeign extends ModelContainer {

	public function toObject($pValue, $pUseSerializationName = false, $pExportForeignObject = false) {
		if (is_null($pValue)) {
			$lReturn = null;
		} else if (($pValue instanceof ObjectArray) && !$pValue->isLoaded()) {
			$lReturn = ObjectArray::__UNLOAD__;
		} else if ($pExportForeignObject) {
			$lReturn = $this->mModel->toObject($pValue, $pUseSerializationName, $pExportForeignObject);
		} else {
			$lReturn = $this->mModel->toObjectId($pValue, $pUseSerializationName);
		}
		return $lReturn;
	}
	
	public function fromObject($pValue) {
		if (is_null($pValue)) {
			return null;
		}
		if (($this->mModel instanceof ModelArray) && is_string($pValue) && $pValue == ObjectArray::__UNLOAD__) {
			return $this->mModel->getObjectInstance(false);
		}
		$lValue = $this->mModel->fromObject($pValue);
		if ($lValue instanceof ObjectArray) {
			foreach ($lValue->getValues() as $lObject) {
				$lObject->setLoadStatus(false);
			}
		} else {
			$lValue->setLoadStatus(false);
		}
		return $lValue;
	}
	
	public function toXml($pValue, $pXmlNode, $pUseSerializationName = false, $pExportForeignObject = false) {
		if (is_null($pValue)) {
			// do nothing
		} else if (($pValue instanceof ObjectArray) && !$pValue->isLoaded()) {
			$pXmlNode[ObjectArray::__UNLOAD__] = "1";
		} else if ($pExportForeignObject) {
			$this->mModel->toXml($pValue, $pXmlNode, $pUseSerializationName, $pExportForeignObject);
		} else {
			$this->mModel->toXmlId($pValue, $pXmlNode, $pUseSerializationName);
		}
	}
	
	public function fromXml($pValue) {
		if (isset($pValue[ObjectArray::__UNLOAD__]) && ((string) $pValue[ObjectArray::__UNLOAD__] == "1")) {
			$lValue = $this->mModel->getObjectInstance(false);
		} else {
			$lValue = $this->mModel->fromXml($pValue);
			if (!is_null($lValue)) {
				if ($lValue instanceof ObjectArray) {
					foreach ($lValue->getValues() as $lObject) {
						$lObject->setLoadStatus(false);
					}
				} else {
					$lValue->setLoadStatus(false);
				}
			}
		}
		return $lValue;
	}
	
}