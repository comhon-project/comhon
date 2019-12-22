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

use Comhon\Object\UniqueObject;

/**
 * verify if all objects are loaded (check recursively all contained objects)
 */
class LoadStatuschecker extends Visitor {
	
	/**
	 *
	 * @var string[]
	 */
	private $stack = null;
	
	/**
	 *
	 * @var boolean
	 */
	private $found = false;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_getMandatoryParameters()
	 */
	protected function _getMandatoryParameters() {
		return [];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_init()
	 */
	protected function _init($object) {
		if (($object instanceof UniqueObject) && !$object->isLoaded()) {
			$this->stack = [];
			$this->found = true;
		}
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_visit()
	 */
	protected function _visit($parentObject, $key, $propertyNameStack, $isForeign) {
		$value = $parentObject->getValue($key);
		
		if (($value instanceof UniqueObject) && !$value->isLoaded()) {
			$this->stack = $propertyNameStack;
			$this->found = true;
		}
		
		return !$this->found;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_postVisit()
	 */
	protected function _postVisit($parentObject, $key, $propertyNameStack, $isForeign) {}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_finalize()
	 */
	protected function _finalize($object) {
		return $this->stack;
	}
}