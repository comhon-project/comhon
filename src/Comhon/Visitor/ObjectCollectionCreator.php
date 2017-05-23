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

	private $mLocalObjectCollection;
	
	protected function _getMandatoryParameters() {
		return null;
	}
	
	protected function _isVisitRootObject() {
		return false;
	}
	
	protected function _init($pObject) {
		$this->mLocalObjectCollection = new ObjectCollection();
	}
	
	protected function _visit($pParentObject, $pKey, $pPropertyNameStack) {
		$lValue = $pParentObject->getValue($pKey);
		
		// each element will be visited if return true
		if ($lValue->getModel() instanceof ModelArray) {
			return true;
		}
		$lSuccess = $this->mLocalObjectCollection->addObject($lValue, false);
		
		// we don't want to visit child object with main model because they can't share LocalObjectCollection
		return !($lValue->getModel() instanceof MainModel);
	}
	
	protected function _postVisit($pParentObject, $pKey, $pPropertyNameStack) {}
	
	protected function _finalize($pObject) {
		return $this->mLocalObjectCollection;
	}
}