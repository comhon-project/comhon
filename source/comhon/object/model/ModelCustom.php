<?php
namespace comhon\object\model;
use \Exception;

class ModelCustom extends Model {

	public function __construct($pModelName, $pProperties) {
		$this->mModelName = $pModelName;
		$this->mIsLoaded = true;
		$this->_setProperties($pProperties);
	}
	
}