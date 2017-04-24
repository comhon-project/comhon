<?php
namespace comhon\serialization\file;

use comhon\model\Model;
use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\serialization\SerializationFile;

class JsonFile extends SerializationFile {
	
	/**
	 *
	 * @param Object $pObject
	 * @return string
	 */
	protected function _getPath(Object $pObject) {
		return $this->mSettings->getValue('saticPath') . DIRECTORY_SEPARATOR . $pObject->getId() . DIRECTORY_SEPARATOR . $this->mSettings->getValue('staticName');
	}
	
	/**
	 *
	 * @param \stdClass $pStdClass
	 * @param string $pPath
	 */
	protected function _write($pStdClass, $pPath) {
		return file_put_contents($pPath, json_encode($pStdClass));
	}
	
	/**
	 *
	 * @param string $pPath
	 * @return \stdClass
	 */
	protected function _read($pPath) {
		return json_decode(file_get_contents($pPath));
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @return \stdClass
	 */
	protected function _transfromObject(Object $pObject) {
		return $pObject->toSerialStdObject();
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param \stdClass $pStdClass
	 */
	protected function _fillObject(Object $pObject, $pStdClass) {
		$pObject->fromSerializedStdObject($pStdClass);
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param \stdClass $pFormatedContent
	 */
	protected function _addInheritanceKey(Object $pObject, $pStdClass) {
		if (!is_null($this->getInheritanceKey())) {
			$pStdClass->{$this->getInheritanceKey()} = $pObject->getModel()->getName();
		}
	}
	
	/**
	 * 
	 * @param \stdClass $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	public function getInheritedModel($pValue, Model $pExtendsModel) {
		return isset($pValue->{$this->mInheritanceKey}) 
				? ModelManager::getInstance()->getInstanceModel($pValue->{$this->mInheritanceKey}) : $pExtendsModel;
	}
	
}