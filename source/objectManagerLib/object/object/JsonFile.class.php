<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\model\ModelForeign;

class JsonFile extends SerializationUnit {
	
	public function saveObject($pValue, $pModel) {
		$lPath = $this->getValue("saticPath")."/".$pValue->getId()."/".$this->getValue("staticName");
		if (!file_exists(dirname($lPath))) {
			if (!mkdir(dirname($lPath), 0777, true)) {
				throw new \Exception("cannot save json file (id = $pId)");
			}
		}
		return file_put_contents($pPath, json_encode($pModel->toObject($pValue)));
	}
	
	public function loadObject($pObject) {
		$lId = $pObject->getId();
		$lPath = $this->getValue("saticPath")."/$lId/".$this->getValue("staticName");
		if (!file_exists($lPath)) {
			throw new \Exception("cannot load json file, file doesn't exists (id = $lId)");
		}
		$lStdClassObject = json_decode(file_get_contents($lPath));
		if ($lStdClassObject !== false) {
			$pObject->fromObject($lStdClassObject);
			return true;
		}else {
			return false;
		}
	}
	
	public function hasReturnValue() {
		return false;
	}
}