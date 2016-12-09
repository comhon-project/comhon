<?php
namespace comhon\object\object;

use comhon\object\model\Model;
use comhon\object\MainObjectCollection;

class JsonFile extends SerializationUnit {
	
	protected function _saveObject(Object $pObject) {
		$lPath = $this->getValue("saticPath") . DIRECTORY_SEPARATOR . $pObject->getId() . DIRECTORY_SEPARATOR . $this->getValue("staticName");
		if (!file_exists(dirname($lPath))) {
			if (!mkdir(dirname($lPath), 0777, true)) {
				throw new \Exception("cannot save json file (id : {$pObject->getId()})");
			}
		}
		return file_put_contents($lPath, json_encode($pObject->toSerialStdObject()));
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
				trigger_error('inherited and DIFFERENT model -> '.json_encode($lStdClassObject));
			} else {
				trigger_error('inherited and SAME model -> '.json_encode($lStdClassObject));
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