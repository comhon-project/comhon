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
use Comhon\Exception\ComhonException;
use Comhon\Model\Model;

final class ComhonObject extends UniqueObject {

	/**
	 * 
	 * @param string|\Comhon\Model\Model $model can be a model name or an instance of model
	 * @param boolean $isLoaded
	 */
	final public function __construct($model, $isLoaded = true) {
		if (is_string($model)) {
			$model = ModelManager::getInstance()->getInstanceModel($model);
		}
		if (!($model instanceof Model)) {
			throw new ComhonException("invalid model '{$model->getName()}', ComhonObject must have instance of Model");
		}
		$this->_affectModel($model);
		
		foreach ($model->getPropertiesWithDefaultValues() as $property) {
			$this->setValue($property->getName(), $property->getDefaultValue(), false);
		}
		$this->setIsLoaded($isLoaded);
	}
	
}
