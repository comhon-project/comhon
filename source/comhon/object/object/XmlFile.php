<?php
namespace comhon\object\object;

use comhon\object\MainObjectCollection;
use comhon\object\model\Model;
use comhon\object\singleton\InstanceModel;

class XmlFile extends SerializationUnit {
	
	protected function _saveObject(Object $pObject) {
		if (!$pObject->getModel()->hasIdProperties()) {
			throw new \Exception("Cannot save model without id in xml file");
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
		$lXml = $pObject->toSerialXml();
		if (!is_null($this->getInheritanceKey())) {
			$lXml[$this->getInheritanceKey()] = $pObject->getModel()->getModelName();
		}
		if ($lXml->asXML($lPath) === false) {
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
		$lSimpleXmlElement = simplexml_load_file($lPath);
		if ($lSimpleXmlElement === false || is_null($lSimpleXmlElement)) {
			throw new \Exception("cannot load json file, xml parsing file content failed");
		}
		if (!is_null($this->getInheritanceKey())) {
			$lExtendsModel = $pObject->getModel();
			$lModel = $this->getInheritedModel($lSimpleXmlElement, $lExtendsModel);
			if ($lModel !== $lExtendsModel) {
				$pObject->cast($lModel);
			}
		}
		$pObject->fromSerializedXml($lSimpleXmlElement);
		return true;
	}
	
	/**
	 * @param \SimpleXMLElement $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	protected function getInheritedModel($pValue, Model $pExtendsModel) {
		return isset($pValue[$this->mInheritanceKey]) 
				? InstanceModel::getInstance()->getInstanceModel((string) $pValue[$this->mInheritanceKey]) : $pExtendsModel;
	}
	
}