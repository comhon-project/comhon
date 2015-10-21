<?php
namespace objectManagerLib\object\model;

class ModelForeign extends ModelContainer {

	private $mForeignModelPropertyId;
	
	public function getForeignPropertyId() {
		if (is_null($this->mForeignModelPropertyId)) {
			foreach ($this->mModel->getProperties() as $lPropertyName => $lProperty) {
				if ($lProperty->isId()) {
					if (!is_null($this->mForeignModelPropertyId)) {
						throw new \Exception("foreign model must have only one property id");
					}
					$this->mForeignModelPropertyId = $lPropertyName;
				}
			}
			if (is_null($this->mForeignModelPropertyId)) {
				throw new \Exception("foreign model must have one property id");
			}
		}
		return $this->mForeignModelPropertyId;
	}
	
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
	
	public function toXml($pValue) {
		$this->getForeignPropertyId();
		return $this->mModel->getPropertyModel($this->mForeignModelPropertyId)->toXml($pValue->getValue($this->mForeignModelPropertyId));
	}
	
	public function fromXml($pValue) {
		$this->getForeignPropertyId();
		$lObject->setValue($this->mForeignModelPropertyId, $this->mModel->getPropertyModel($this->mForeignModelPropertyId)->fromXml($pValue));
		return $lObject;
	}
	
}