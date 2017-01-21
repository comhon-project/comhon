<?php
namespace comhon\object\object;

use comhon\object\model\Model;
use comhon\object\MainObjectCollection;
use comhon\object\singleton\InstanceModel;

class JsonFile extends SerializationUnit {
	
	protected function _saveObject(Object $pObject) {
		if (!$pObject->getModel()->hasIdProperties()) {
			throw new \Exception("Cannot save model without id in json file");
		}
		if (!$pObject->hasCompleteId()) {
			throw new \Exception("Cannot save object, object id is not complete");
		}
		$lPath = $this->getValue("saticPath") . DIRECTORY_SEPARATOR . $pObject->getId() . DIRECTORY_SEPARATOR . $this->getValue("staticName");
		if (!file_exists(dirname($lPath))) {
			if (!mkdir(dirname($lPath), 0777, true)) {
				throw new \Exception("Cannot save object with id '{$pObject->getId()}'. Impossible to create directory '".dirname($lPath)."'");
			}
		}
		$lStdObject = $pObject->toSerialStdObject();
		if (!is_null($this->getInheritanceKey())) {
			$lStdObject->{$this->getInheritanceKey()} = $pObject->getModel()->getModelName();
		}
		if (file_put_contents($lPath, json_encode($lStdObject)) === false) {
			throw new \Exception("Cannot save object with id '{$pObject->getId()}'. Creation or filling file failed");
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \comhon\object\object\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(Object $pObject) {
		$lId = $pObject->getId();
		$lPath = $this->getValue("saticPath") . DIRECTORY_SEPARATOR . $lId . DIRECTORY_SEPARATOR . $this->getValue("staticName");
		if (!file_exists($lPath)) {
			return false;
		}
		$lStdClassObject = json_decode(file_get_contents($lPath));
		if (is_null($lStdClassObject) || $lStdClassObject === false) {
			throw new \Exception("cannot load json file, json decode file content failed");
		}
		if (!is_null($this->getInheritanceKey())) {
			$lExtendsModel = $pObject->getModel();
			$lModel = $this->getInheritedModel($lStdClassObject, $lExtendsModel);
			if ($lModel !== $lExtendsModel) {
				$pObject->cast($lModel);
			}
		}
		$pObject->fromSerializedStdObject($lStdClassObject);
		return true;
	}
	
	/**
	 * 
	 * @param \stdClass $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	protected function getInheritedModel($pValue, Model $pExtendsModel) {
		return isset($pValue->{$this->mInheritanceKey}) 
				? InstanceModel::getInstance()->getInstanceModel($pValue->{$this->mInheritanceKey}) : $pExtendsModel;
	}
	
}