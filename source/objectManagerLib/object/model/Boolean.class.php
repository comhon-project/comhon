<?php
namespace objectManagerLib\object\model;

class Boolean extends SimpleModel {
	
	const ID = "boolean";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
}