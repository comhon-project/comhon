<?php
namespace comhon\object\serialization;

use comhon\object\collection\MainObjectCollection;
use comhon\model\Model;
use comhon\model\singleton\ModelManager;
use comhon\utils\Utils;
use comhon\object\Object;

abstract class SerializationFile extends SerializationUnit {

	/**
	 * 
	 * @param string $pPath
	 */
	abstract protected function _read($pPath);
	
	/**
	 * 
	 * @param unknown $pContent
	 * @param string $pPath
	 */
	abstract protected function _write($pContent, $pPath);
	
	/**
	 * 
	 * @param Object $pObject
	 */
	abstract protected function _getPath(Object $pObject);

	/**
	 * 
	 * @param Object $pObject
	 */
	abstract protected function _transfromObject(Object $pObject);
	
	/**
	 * 
	 * @param Object $pObject
	 * @param unknown $pFormatedContent
	 */
	abstract protected function _addInheritanceKey(Object $pObject, $pFormatedContent);
	
	/**
	 * 
	 * @param Object $pObject
	 * @param unknown $pFormatedContent
	 */
	abstract protected function _fillObject(Object $pObject, $pFormatedContent);
	
	/**
	 * @param Object $pObject
	 * @param string $pOperation
	 * @return integer
	 */
	protected function _saveObject(Object $pObject, $pOperation = null) {
		if (!$pObject->getModel()->hasIdProperties()) {
			throw new \Exception('Cannot save model without id in xml file');
		}
		if (!$pObject->hasCompleteId()) {
			throw new \Exception('Cannot save object, object id is not complete');
		}
		$lPath = $this->_getPath($pObject);
		if (!is_null($pOperation)) {
			if ($pOperation == self::CREATE) {
				if (file_exists($lPath)) {
					throw new \Exception("Cannot save object with id '{$pObject->getId()}'. try to create file but file already exists");
				}
			} else if ($pOperation == self::UPDATE) {
				if (!file_exists($lPath)) {
					return 0;
				}
			}
		}
		if (!file_exists(dirname($lPath))) {
			if (!mkdir(dirname($lPath), 0777, true) && !file_exists(dirname($lPath))) {
				throw new \Exception("Cannot save object with id '{$pObject->getId()}'. Impossible to create directory '".dirname($lPath).'\'');
			}
		}
		$lContent = $this->_transfromObject($pObject);
		$this->_addInheritanceKey($pObject, $lContent);
		if ($this->_write($lContent, $lPath) === false) {
			throw new \Exception("Cannot save object with id '{$pObject->getId()}'. Creation or filling file failed");
		}
		return 1;
	}
	
	/**
	 * @param Object $pObject
	 * @return boolean
	 */
	protected function _loadObject(Object $pObject) {
		$lPath = $this->_getPath($pObject);
		if (!file_exists($lPath)) {
			return false;
		}
		$lFormatedContent = $this->_read($lPath);
		if ($lFormatedContent === false || is_null($lFormatedContent)) {
			throw new \Exception("cannot load file '$lPath'");
		}
		if (!is_null($this->getInheritanceKey())) {
			$lExtendsModel = $pObject->getModel();
			$lModel = $this->getInheritedModel($lFormatedContent, $lExtendsModel);
			if ($lModel !== $lExtendsModel) {
				$pObject->cast($lModel);
			}
		}
		$this->_fillObject($pObject, $lFormatedContent);
		return true;
	}
	
	/**
	 * @param Object $pObject
	 * @throws \Exception
	 * @return integer
	 */
	protected function _deleteObject(Object $pObject) {
		if (!$pObject->getModel()->hasIdProperties() || !$pObject->hasCompleteId()) {
			throw new \Exception('delete operation require complete id');
		}
		$lId = $pObject->getId();
		if ($lId == null || $lId == '') {
			throw new \Exception("Cannot delete object '{$pObject->getModel()->getName()}' with id '$lId', object id is empty");
		}
		$lPath = $this->_getPath($pObject);
		if (!file_exists($lPath)) {
			return 0;
		}
		if (!Utils::delTree(dirname($lPath))) {
			throw new \Exception("Cannot delete object '{$pObject->getModel()->getName()}' with id '$lId'");
		}
		return 1;
	}
	
}