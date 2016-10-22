<?php
namespace comhon\object\object;

class JsonFile extends SerializationUnit {
	
	protected function _saveObject($pObject) {
		$lPath = $this->getValue("saticPath") . DIRECTORY_SEPARATOR . $pObject->getId() . DIRECTORY_SEPARATOR . $this->getValue("staticName");
		if (!file_exists(dirname($lPath))) {
			if (!mkdir(dirname($lPath), 0777, true)) {
				throw new \Exception("cannot save json file (id : {$pObject->getId()})");
			}
		}
		return file_put_contents($lPath, json_encode($pObject->toObject()));
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \comhon\object\object\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(Object $pObject) {
		$lId = $pObject->getId();
		$lPath = $this->getValue("saticPath") . DIRECTORY_SEPARATOR . $lId . DIRECTORY_SEPARATOR . $this->getValue("staticName");
		if (!file_exists($lPath)) {
			throw new \Exception("cannot load json file, file doesn't exists (id : $lId)");
		}
		$lStdClassObject = json_decode(file_get_contents($lPath));
		if ($lStdClassObject !== false) {
			$pObject->fromObject($lStdClassObject);
			return true;
		}else {
			return false;
		}
	}
	
}