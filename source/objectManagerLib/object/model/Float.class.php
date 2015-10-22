<?php
namespace objectManagerLib\object\model;

class Float extends SimpleModel {
	
	const ID = "float";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	public function fromXml($pValue) {
		return (float) $pValue;
	}
}