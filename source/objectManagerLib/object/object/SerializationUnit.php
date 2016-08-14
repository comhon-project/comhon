<?php
namespace objectManagerLib\object\object;

abstract class SerializationUnit extends Object {
	
	public function saveObject($pObject) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_saveObject($pObject);
	}
	
	/**
	 * @param Object $pObject
	 * @throws \Exception
	 */
	public function loadObject(Object $pObject) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_loadObject($pObject);
	}
	
	protected abstract function _saveObject($pObject);
	
	/**
	 * @param Object $pObject
	 */
	protected abstract function _loadObject(Object $pObject);
	
	public function loadComposition(ObjectArray $pObject, $pParentId, $pCompositionProperties, $pOnlyIds) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}