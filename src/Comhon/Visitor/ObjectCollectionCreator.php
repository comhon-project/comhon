<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Visitor;

use Comhon\Model\MainModel;
use Comhon\Model\ModelArray;
use Comhon\Object\Collection\ObjectCollection;

/**
 * instanciate and populate ObjectCollection
 */
class ObjectCollectionCreator extends Visitor {

	private $localObjectCollection;
	
	protected function _getMandatoryParameters() {
		return null;
	}
	
	protected function _isVisitRootObject() {
		return false;
	}
	
	protected function _init($object) {
		$this->localObjectCollection = new ObjectCollection();
	}
	
	protected function _visit($parentObject, $key, $propertyNameStack) {
		$value = $parentObject->getValue($key);
		
		// each element will be visited if return true
		if ($value->getModel() instanceof ModelArray) {
			return true;
		}
		$success = $this->localObjectCollection->addObject($value, false);
		
		// we don't want to visit child object with main model because they can't share LocalObjectCollection
		return !($value->getModel() instanceof MainModel);
	}
	
	protected function _postVisit($parentObject, $key, $propertyNameStack) {}
	
	protected function _finalize($object) {
		return $this->localObjectCollection;
	}
}