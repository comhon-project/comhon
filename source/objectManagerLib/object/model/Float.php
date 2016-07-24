<?php
namespace objectManagerLib\object\model;

class Float extends SimpleModel {
	
	const ID = "float";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	protected function _fromXml($pValue, $pDateTimeZone = null, $pLocalObjectCollection = null) {
		return (float) $pValue;
	}
}