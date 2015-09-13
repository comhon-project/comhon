<?php
namespace objectManagerLib\object\object;

use objectManagerLib\object\model\ModelForeign;

class JsonFile extends SerializationUnit {
	
	public function saveObject($pValue, $pModel) {
		$lPath = $this->getValue("saticPath")."/".$pValue->getId()."/".$this->getValue("staticName");
		return file_put_contents($pPath, json_encode($pModel->toObject($pValue)));
	}
	
	public function loadObject($pId, $pModel, $pLoadDepth) {
		$lPath = $this->getValue("saticPath")."/$pId/".$this->getValue("staticName");
		if ($pModel instanceof ModelForeign) {
			return $pModel->getModel()->fromObject(json_decode(file_get_contents($lPath)), $pLoadDepth);
		}else {
			return $pModel->fromObject(json_decode(file_get_contents($lPath)), $pLoadDepth);
		}
	}
	
	public function hasReturnValue() {
		return false;
	}
}