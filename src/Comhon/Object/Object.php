<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Object;

use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Model;
use Comhon\Model\ModelContainer;
use Comhon\Model\SimpleModel;
use Comhon\Object\ComhonObject;

final class Object extends ComhonObject {

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
		$this->setIsLoaded($pIsLoaded);
	}
	
}
