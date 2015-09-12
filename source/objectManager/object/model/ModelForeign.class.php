<?php
namespace GenLib\objectManager\Model;

use GenLib\objectManager\object\object\UnloadObject;

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
	
	public function getObjectInstance() {
		return new UnloadObject($this->getModelName());
	}
	
	public function toObject($pValue, $pUseSerializationName = false, $pExportForeignObject = false) {
		$this->getForeignPropertyId();
		if (is_null($pValue)) {
			return null;
		} else if ($pExportForeignObject) {
			return $this->mModel->toObject($pValue, $pUseSerializationName, $pExportForeignObject);
		} else {
			$lModel = $this->mModel->getPropertyModel($this->mForeignModelPropertyId);
			return $lModel->toObject($pValue->getValue($this->mForeignModelPropertyId), $pUseSerializationName, $pExportForeignObject);
		}
	}
	
	public function fromObject($pValue) {
		$lObject = null;
		if (!is_null($pValue)) {
			$this->getForeignPropertyId();
			$lObject = $this->getObjectInstance();
			$lObject->setValue($this->mForeignModelPropertyId, $this->mModel->getPropertyModel($this->mForeignModelPropertyId)->fromObject($pValue));
		}
		return $lObject;
	}
	
	public function toXml($pValue) {
		$this->getForeignPropertyId();
		return $this->mModel->getPropertyModel($this->mForeignModelPropertyId)->toXml($pValue->getValue($this->mForeignModelPropertyId));
	}
	
	public function fromXml($pValue) {
		$this->getForeignPropertyId();
		$lObject = $this->getObjectInstance();
		$lObject->setValue($this->mForeignModelPropertyId, $this->mModel->getPropertyModel($this->mForeignModelPropertyId)->fromXml($pValue));
		return $lObject;
	}
	
}