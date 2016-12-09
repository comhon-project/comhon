<?php
namespace comhon\object\object;

use comhon\object\model\Model;

abstract class SerializationUnit extends Object {
	
	/** @var string */
	protected $mInheritanceKey;
	
	/**
	 * 
	 * @return string
	 */
	public function getInheritanceKey() {
		return $this->mInheritanceKey;
	}
	
	/**
	 * 
	 * @param string $pValue
	 */
	public function setInheritanceKey($pValue) {
		$this->mInheritanceKey = $pValue;
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @throws \Exception
	 */
	public function saveObject(Object $pObject) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_saveObject($pObject);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @return boolean true if loading is successfull
	 * @throws \Exception
	 */
	public function loadObject(Object $pObject) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_loadObject($pObject);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 */
	protected abstract function _saveObject(Object $pObject);
	
	/**
	 * 
	 * @param Object $pObject
	 */
	protected abstract function _loadObject(Object $pObject);
	
	/**
	 *
	 * @param unknow $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	protected abstract function getInheritedModel($pValue, Model $pExtendsModel);
	
	/**
	 * 
	 * @param ObjectArray $pObject
	 * @param string|integer $pParentId
	 * @param string[] $pCompositionProperties
	 * @param boolean $pOnlyIds
	 * @throws \Exception
	 */
	public function loadComposition(ObjectArray $pObject, $pParentId, $pCompositionProperties, $pOnlyIds) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}