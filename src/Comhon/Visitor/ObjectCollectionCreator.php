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

use Comhon\Model\ModelArray;
use Comhon\Object\Collection\ObjectCollection;

/**
 * instanciate and populate ObjectCollection
 */
class ObjectCollectionCreator extends Visitor {

	/** @var ObjectCollection */
	private $localObjectCollection;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_getMandatoryParameters()
	 */
	protected function _getMandatoryParameters() {
		return null;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_isVisitRootObject()
	 */
	protected function _isVisitRootObject() {
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_init()
	 */
	protected function _init($object) {
		$this->localObjectCollection = new ObjectCollection();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_visit()
	 */
	protected function _visit($parentObject, $key, $propertyNameStack) {
		$value = $parentObject->getValue($key);
		
		if (!($value->getModel() instanceof ModelArray)) {
			$this->localObjectCollection->addObject($value, false);
		}
		
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_postVisit()
	 */
	protected function _postVisit($parentObject, $key, $propertyNameStack) {}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_finalize()
	 */
	protected function _finalize($object) {
		return $this->localObjectCollection;
	}
}