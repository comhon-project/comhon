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
 * instanciate and populate ObjectCollection
 */
class ObjectFinder extends Visitor {

	const ID = 'id';
	const MODEL = 'model';
	const SEARCH_FOREIGN = 'searchForeign';
	
	/**
	 *
	 * @var string|number
	 */
	private $id;
	
	/**
	 *
	 * @var \Comhon\Model\Model
	 */
	private $model;
	
	/**
	 *
	 * @var boolean
	 */
	private $searchForeign = false;
	
	/**
	 *
	 * @var boolean
	 */
	private $found = false;
	
	/**
	 *
	 * @var string[]
	 */
	private $stack = null;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_getMandatoryParameters()
	 */
	protected function _getMandatoryParameters() {
		return [self::ID, self::MODEL, self::SEARCH_FOREIGN];
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_init()
	 */
	protected function _init($object) {
		$this->id = $this->params[self::ID];
		$this->model = $this->params[self::MODEL];
		$this->searchForeign = isset($this->params[self::SEARCH_FOREIGN]) ? $this->params[self::SEARCH_FOREIGN] : false;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Visitor\Visitor::_visit()
	 */
	protected function _visit($parentObject, $key, $propertyNameStack, $isForeign) {
		$value = $parentObject->getValue($key);
		
		if (($this->searchForeign === $isForeign) && ($value instanceof UniqueObject) && $value->getId() === $this->id && $value->getModel() === $this->model) {
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