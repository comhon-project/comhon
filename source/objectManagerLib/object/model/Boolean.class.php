<?php
namespace objectManagerLib\object\model;

class Boolean extends SimpleModel {
	
	const ID = "boolean";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	public function toXml($pValue, $pXmlNode = null, $pUseSerializationName = false, $pExportForeignObject = false) {
		return $pValue ? 1 : 0;
	}
	
	public function fromXml($pValue) {
		return ((integer) $pValue === 1) ? true : false;
	}
	
}