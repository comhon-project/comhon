<?php
namespace comhon\object\object;

class XmlFile extends SerializationUnit {
	
	protected function _saveObject($pObject) {
		$lPath = $this->getValue("saticPath") . DIRECTORY_SEPARATOR . $pObject->getId() . DIRECTORY_SEPARATOR . $this->getValue("staticName");
		if (!file_exists(dirname($lPath))) {
			if (!mkdir(dirname($lPath), 0777, true)) {
				throw new \Exception("cannot save xml file (id : {$pObject->getId()})");
			}
		}
		return $pObject->toXml()->asXML($lPath);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \comhon\object\object\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(Object $pObject) {
		$lId = $pObject->getId();
		$lPath = $this->getValue("saticPath") . DIRECTORY_SEPARATOR . $lId . DIRECTORY_SEPARATOR . $this->getValue("staticName");
		if (!file_exists($lPath)) {
			throw new \Exception("cannot load xml file, file doesn't exists (id : $lId)");
		}
		$lSimpleXmlElement = simplexml_load_file($lPath);
		if ($lSimpleXmlElement !== false && !is_null($lSimpleXmlElement)) {
			$pObject->fromXml($lSimpleXmlElement);
			return true;
		}else {
			return false;
		}
	}
	
}