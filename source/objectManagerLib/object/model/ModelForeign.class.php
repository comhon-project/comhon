<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\ObjectArray;

class ModelForeign extends ModelContainer {

	public function toObject($pValue, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return null;
		}
		return $this->getModel()->toObjectId($pValue, $pUseSerializationName, $pMainForeignObjects);
	}
	
	protected function _fromObject($pValue, $pMainObjectId) {
		if (is_null($pValue)) {
			return null;
		}
		return $this->getModel()->_fromObjectId($pValue, $pMainObjectId);
	}
	
	public function toXml($pValue, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return;
		}
		$this->getModel()->toXmlId($pValue, $pXmlNode, $pUseSerializationName, $pMainForeignObjects);
	}
	
	protected function _fromXml($pValue, $pMainObjectId) {
		return $this->getModel()->_fromXmlId($pValue, $pMainObjectId);
	}
	
	protected function _fromSqlColumn($pValue) {
		return $this->getModel()->_fromSqlId($pValue);
	}
	
}