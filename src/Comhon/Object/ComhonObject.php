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
use Comhon\Model\ModelContainer;
use Comhon\Model\SimpleModel;
use Comhon\Exception\ComhonException;
use Comhon\Model\ModelComhonObject;

final class ComhonObject extends UniqueObject {

	/**
	 * 
	 * @param string|\Comhon\Model\ModelComhonObject $model can be a model name or an instance of model
	 * @param boolean $isLoaded
	 */
	final public function __construct($model, $isLoaded = true) {
		$objectModel = ($model instanceof ModelComhonObject) ? $model : ModelManager::getInstance()->getInstanceModel($model);
		
		if (($objectModel instanceof ModelContainer) || ($objectModel instanceof SimpleModel)) {
			throw new ComhonException('ComhonObject cannot have ModelContainer or SimpleModel');
		}
		$this->_affectModel($objectModel);
		
		foreach ($objectModel->getPropertiesWithDefaultValues() as $property) {
			$this->setValue($property->getName(), $property->getDefaultValue(), false);
		}
		$this->setIsLoaded($isLoaded);
	}
	
}
