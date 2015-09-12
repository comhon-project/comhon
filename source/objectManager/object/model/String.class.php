<?php
namespace ObjectManagerLib\objectManager\Model;

class String extends SimpleModel {
	
	const ID = "string";
	
	protected function _init() {
		$this->mModelName = self::ID;
	}
}