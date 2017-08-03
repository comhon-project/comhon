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
use Comhon\Model\SimpleModel;
use Comhon\Exception\ComhonException;

abstract class ExtendableObject extends ObjectUnique {

	/**
	 * get model name
	 * 
	 * return string
	 */
	abstract protected function _getModelName();
	
	/**
	 * 
	 * @param boolean $isLoaded
	 */
	final public function __construct($isLoaded = true) {
		$model = ModelManager::getInstance()->getInstanceModel($this->_getModelName());
		
		if ($model instanceof SimpleModel) {
			throw new ComhonException('Extendable object cannot have SimpleModel');
		}
		$this->_affectModel($model);
		
		foreach ($model->getPropertiesWithDefaultValues() as $property) {
			$this->setValue($property->getName(), $property->getDefaultValue(), false);
		}
		$this->setIsLoaded($isLoaded);
	}
	
}
