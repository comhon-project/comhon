<?php
namespace comhon\object\object\serialization;

use comhon\object\model\Model;
use comhon\object\object\ObjectArray;
use comhon\object\object\Object;

abstract class SerializationUnit extends Object {

	const UPDATE = 'update';
	const CREATE = 'create';
	
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
	public function saveObject(Object $pObject, $pOperation = null) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		if (!is_null($pOperation) && ($pOperation !== self::CREATE) && ($pOperation !== self::UPDATE)) {
			throw new \Exception("operation '$pOperation' not recognized");
		}
		return $this->_saveObject($pObject, $pOperation);
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
	 * @throws \Exception
	 */
	public function deleteObject(Object $pObject) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_deleteObject($pObject);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 */
	protected abstract function _saveObject(Object $pObject, $pOperation = null);
	
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
	public abstract function getInheritedModel($pValue, Model $pExtendsModel);
	
	/**
	 * @param Object $pObject
	 * @throws \Exception
	 */
	protected abstract function _deleteObject(Object $pObject);
	
	/**
	 * 
	 * @param ObjectArray $pObject
	 * @param string|integer $pParentId
	 * @param string[] $pAggregationProperties
	 * @param boolean $pOnlyIds
	 * @throws \Exception
	 */
	public function loadAggregation(ObjectArray $pObject, $pParentId, $pAggregationProperties, $pOnlyIds) {
		throw new \Exception('error : property is not serialized in a sql table');
	}
}