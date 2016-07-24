<?php
namespace objectManagerLib\object\model;

class Boolean extends SimpleModel {
	
	const ID = "boolean";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _toXml($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		return $pValue ? 1 : 0;
	}
	
	protected function _fromXml($pValue, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return ((integer) $pValue === 1) ? true : false;
	}
	
}