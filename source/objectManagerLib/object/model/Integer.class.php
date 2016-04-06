<?php
namespace objectManagerLib\object\model;

class Integer extends SimpleModel {
	
	const ID = "integer";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _fromXml($pValue, $pMainObjectId = null) {
		return (integer) $pValue;
	}
}