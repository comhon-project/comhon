<?php
namespace comhon\object\object\serialization\file;

use comhon\object\MainObjectCollection;
use comhon\object\model\Model;
use comhon\object\singleton\ModelManager;
use comhon\utils\Utils;
use comhon\object\object\Object;
use comhon\object\object\serialization\SerializationFile;

class XmlFile extends SerializationFile {
	
	/**
	 * 
	 * @param Object $pObject
	 * @return string
	 */
	protected function _getPath(Object $pObject) {
		return $this->getValue('saticPath') . DIRECTORY_SEPARATOR . $pObject->getId() . DIRECTORY_SEPARATOR . $this->getValue('staticName');
	}
	
	/**
	 * 
	 * @param SimpleXMLElement $pXml
	 * @param string $pPath
	 */
	protected function _write($pXml, $pPath) {
		return $pXml->asXML($pPath);
	}

	/**
	 * 
	 * @param string $pPath
	 * @return SimpleXMLElement
	 */
	protected function _read($pPath) {
		return simplexml_load_file($pPath);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @return SimpleXMLElement
	 */
	protected function _transfromObject(Object $pObject) {
		return $pObject->toSerialXml();
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param SimpleXMLElement $pXml
	 */
	protected function _fillObject(Object $pObject, $pXml) {
		$pObject->fromSerializedXml($pXml);
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @param SimpleXMLElement $pXml
	 */
	protected function _addInheritanceKey(Object $pObject, $pXml) {
		if (!is_null($this->getInheritanceKey())) {
			$pXml[$this->getInheritanceKey()] = $pObject->getModel()->getModelName();
		}
	}
	
	/**
	 * @param \SimpleXMLElement $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	public function getInheritedModel($pValue, Model $pExtendsModel) {
		return isset($pValue[$this->mInheritanceKey]) 
				? ModelManager::getInstance()->getInstanceModel((string) $pValue[$this->mInheritanceKey]) : $pExtendsModel;
	}
	
}