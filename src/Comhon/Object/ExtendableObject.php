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

use Comhon\Object\ComhonObject;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\SimpleModel;

abstract class ExtendableObject extends ComhonObject {

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
			throw new \Exception('Extendable object cannot have SimpleModel');
		}
		$this->_affectModel($lModel);
		
		foreach ($lModel->getPropertiesWithDefaultValues() as $lProperty) {
			$this->setValue($lProperty->getName(), $lProperty->getDefaultValue(), false);
		}
		$this->setIsLoaded($pIsLoaded);
	}
	
}
