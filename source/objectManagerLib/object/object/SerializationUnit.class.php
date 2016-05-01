<?php
namespace objectManagerLib\object\object;

abstract class SerializationUnit extends Object {
	
	public function saveObject($pObject) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_saveObject($pObject);
	}
	public function loadObject($pObject) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_loadObject($pObject);
	}
	
	protected abstract function _saveObject($pObject);
	protected abstract function _loadObject($pObject);
	
	public function loadComposition(ObjectArray $pObject, $pParentId, $pCompositionProperties, $pOnlyIds) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}