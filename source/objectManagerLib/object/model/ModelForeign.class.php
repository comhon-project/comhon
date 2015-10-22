<?php
namespace objectManagerLib\object\model;

class ModelForeign extends ModelContainer {

	public function toObject($pValue, $pUseSerializationName = false, $pExportForeignObject = false) {
		if (is_null($pValue)) {
			return null;
		} else if ($pExportForeignObject) {
			return $this->mModel->toObject($pValue, $pUseSerializationName, $pExportForeignObject);
		} else {
			return $this->mModel->toObjectId($pValue, $pUseSerializationName);
		}
	}
	
	public function fromObject($pValue) {
		if (is_null($pValue)) {
			return null;
		} else {
			$lValue = $this->mModel->fromObject($pValue);
			if (is_array($lValue)) {
				foreach ($lValue as $lObject) {
					$lObject->setLoadStatus(false);
				}
			} else {
				$lValue->setLoadStatus(false);
			}
			return $lValue;
		}
	}
	
	public function toXml($pValue, $pXmlNode, $pUseSerializationName = false, $pExportForeignObject = false) {
		if (is_null($pValue)) {
			return null;
		} else if ($pExportForeignObject) {
			return $this->mModel->toXml($pValue, $pXmlNode, $pUseSerializationName, $pExportForeignObject);
		} else {
			return $this->mModel->toXmlId($pValue, $pXmlNode, $pUseSerializationName);
		}
	}
	
	public function fromXml($pValue) {
		if (is_null($pValue)) {
			return null;
		} else {
			$lValue = $this->mModel->fromXml($pValue);
			if (is_array($lValue)) {
				foreach ($lValue as $lObject) {
					$lObject->setLoadStatus(false);
				}
			} else {
				$lValue->setLoadStatus(false);
			}
			return $lValue;
		}
	}
	
}