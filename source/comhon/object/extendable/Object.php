<?php
namespace comhon\object\extendable;

use comhon\object\Object as AbstractObject;
use comhon\model\singleton\ModelManager;
use comhon\model\SimpleModel;
use comhon\model\ModelContainer;

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
		
		if (($lModel instanceof ModelContainer) || ($lModel instanceof SimpleModel)) {
			throw new \Exception('Object cannot have ModelContainer or SimpleModel');
		}
		$this->_affectModel($lModel);
		
		foreach ($lModel->getPropertiesWithDefaultValues() as $lProperty) {
			$this->setValue($lProperty->getName(), $lProperty->getDefaultValue(), false);
		}
		foreach ($lModel->getAggregations() as $lProperty) {
			$this->initValue($lProperty->getName(), false, false);
		}
		$this->setIsLoaded($pIsLoaded);
	}
	
}
