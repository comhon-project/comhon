<?php
namespace objectManagerLib\object\model;

class Integer extends SimpleModel {
	
	const ID = "integer";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
	public function fromXml($pValue) {
		return (integer) $pValue;
	}
}