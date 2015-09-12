<?php
namespace GenLib\objectManager\Model;

class Boolean extends SimpleModel {
	
	const ID = "boolean";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
	
}