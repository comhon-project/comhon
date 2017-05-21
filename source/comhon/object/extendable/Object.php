<?php
namespace comhon\object\extendable;

use comhon\object\Object as AbstractObject;
use comhon\model\singleton\ModelManager;
use comhon\model\SimpleModel;

abstract class Object extends AbstractObject {

	/**
	 * return string
	 */
	abstract protected function _getModelName();
	
	/**
	 * 
	 * @param boolean $lIsLoaded
	 */
	final public function __construct($pIsLoaded = true) {
		$lModel = ModelManager::getInstance()->getInstanceModel($this->_getModelName());
		
		if ($lModel instanceof SimpleModel) {
			throw new \Exception('Object cannot have SimpleModel');
		}
		$this->_affectModel($lModel);
		
		foreach ($lModel->getPropertiesWithDefaultValues() as $lProperty) {
			$this->setValue($lProperty->getName(), $lProperty->getDefaultValue(), false);
		}
		$this->setIsLoaded($pIsLoaded);
	}
	
}
