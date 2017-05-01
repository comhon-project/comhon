<?php
namespace comhon\serialization;

use comhon\model\Model;
use comhon\utils\Utils;
use comhon\object\Object;
use comhon\interfacer\Interfacer;
use comhon\model\singleton\ModelManager;

abstract class SerializationFile extends SerializationUnit {

	private $mInterfacer;
	
	/**
	 *
	 * @return Interfacer
	 */
	abstract protected function _getInterfacer();
	
	/**
	 *
	 * @param Object $pSettings
	 * @param string $pInheritanceKey
	 */
	protected function __construct(Object $pSettings, $pInheritanceKey = null) {
		parent::__construct($pSettings, $pInheritanceKey);
		$this->mInterfacer = $this->_getInterfacer();
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @return string
	 */
	protected function _getPath(Object $pObject) {
		return $this->mSettings->getValue('saticPath') . DIRECTORY_SEPARATOR . $pObject->getId() . DIRECTORY_SEPARATOR . $this->mSettings->getValue('staticName');
	}

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
		$lContent = $pObject->export($this->mInterfacer);
		$this->_addInheritanceKey($pObject, $lContent);
		if ($this->mInterfacer->write($lContent, $lPath) === false) {
			throw new \Exception("Cannot save object with id '{$pObject->getId()}'. Creation or filling file failed");
		}
		return 1;
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param mixed $InterfacedObject
	 */
	protected function _addInheritanceKey(Object $pObject, $InterfacedObject) {
		if (!is_null($this->getInheritanceKey())) {
			$this->mInterfacer->setValue($InterfacedObject, $pObject->getModel()->getName(), $this->getInheritanceKey());
		}
	}
	
	/**
	 * @param Object $pObject
	 * @param string[] $pPropertiesFilter
	 * @return boolean
	 */
	protected function _loadObject(Object $pObject, $pPropertiesFilter = null) {
		$lPath = $this->_getPath($pObject);
		if (!file_exists($lPath)) {
			return false;
		}
		$lFormatedContent = $this->mInterfacer->read($lPath);
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
		$pObject->fillObject($lFormatedContent, $this->mInterfacer);
		return true;
	}
	
	/**
	 * @param mixed $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	public function getInheritedModel($pValue, Model $pExtendsModel) {
		return $this->mInterfacer->hasValue($pValue, $this->mInheritanceKey)
			? ModelManager::getInstance()->getInstanceModel($this->mInterfacer->getValue($pValue, $this->mInheritanceKey))
			: $pExtendsModel;
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