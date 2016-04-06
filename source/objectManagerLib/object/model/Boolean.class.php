<?php
namespace objectManagerLib\object\model;

class Boolean extends SimpleModel {
	
	const ID = "boolean";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	public function toXml($pValue, $pXmlNode = null, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		return $pValue ? 1 : 0;
	}
	
	protected function _fromXml($pValue, $pMainObjectId = null) {
		return ((integer) $pValue === 1) ? true : false;
	}
	
}