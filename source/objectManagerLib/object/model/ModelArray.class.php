<?php
namespace objectManagerLib\object\model;

class ModelArray extends ModelContainer {
	
	
	public function getChildrenModel() {
		return $this->mModel;
	}
	
	public function toObject($pArray, $pUseSerializationName = false, $pExportForeignObject = false) {
		$lReturn = array();
		if (!is_null($pArray)) {
			foreach ($pArray as $lKey => $lValue) {
				$lReturn[$lKey] = $this->mModel->toObject($lValue, $pUseSerializationName, $pExportForeignObject);
			}
		}
		return $lReturn;
	}
	
	public function fromObject($pArray) {
		$lReturnArray = array();
		foreach ($pArray as $lKey => $lPhpValue) {
			$lReturnArray[$lKey] = $this->mModel->fromObject($lPhpValue);
		}
		return $lReturnArray;
	}
	
	public function fromSqlDataBase($pRows, $pLoadDepth) {
		$lReturnArray = array();
		foreach ($pRows as $lKey => $lRow) {
			$lReturnArray[$lKey] = $this->mModel->fromSqlDataBase($lRow, $pLoadDepth);
		}
		return $lReturnArray;
	}
	
	
	/*
	 * return true if $pArray1 and $pArray2 are equals
	 */
	public function isEqual($pArray1, $pArray2) {
		if (count($pArray1) != count($pArray2)) {
			return false;
		}
		foreach ($pArray1 as $lkey => $lValue1) {
			if (array_key_exists($lkey, $pArray2)) {
				$lValue2 = $pArray2[$lkey];
				if (!$lValue1->getModel()->isEqual($lValue1, $lValue2)) {
					return false;
				}
			}else {
				return false;
			}
		}
		return true;
	}
}