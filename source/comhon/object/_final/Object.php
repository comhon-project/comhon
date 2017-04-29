<?php
namespace comhon\object\_final;

use comhon\model\singleton\ModelManager;
use comhon\model\Model;
use comhon\model\ModelContainer;
use comhon\model\SimpleModel;
use comhon\object\Object as AbstractObject;

final class Object extends AbstractObject {

	/**
	 * 
	 * @param string|Model $pModel can be a model name or an instance of model
	 * @param boolean $lIsLoaded
	 */
	final public function __construct($pModel, $pIsLoaded = true) {
		$lModel = ($pModel instanceof Model) ? $pModel : ModelManager::getInstance()->getInstanceModel($pModel);
		
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
