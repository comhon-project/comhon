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

use Comhon\Object\AbstractComhonObject;
use Comhon\Object\ComhonArray;
use Comhon\Exception\Visitor\VisitorParameterException;

abstract class Visitor {
	
	/** @var \Comhon\Object\ComhonObject main object to visit */
	protected $mainObject;
	
	/** @var array parameters to apply on visitor */
	protected $params;
	
	/** @var array instances of all visisted objects to avoid infinite loop */
	private $instanceObjectHash = [];
	
	/** @var string[] stack of all properies names  already visited */
	private $propertyNameStack;
	
	/**
	 * execute visitor
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param array $params
	 * @return mixed|boolean
	 */
	final public function execute(AbstractComhonObject $object, $params = []) {
		$this->_verifParameters($params);
		$this->propertyNameStack = [];
		$this->mainObject = $object;
		$this->params = $params;

		$this->_init($object);
		$this->_acceptChildren($object, false);
		
		return $this->_finalize($object);
	}
	
	/**
	 * accept to visit object of specified parent
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $parentObject
	 * @param string $key
	 * @param boolean $isForeign
	 */
	private function _accept($parentObject, $key, $isForeign) {
		$object = $parentObject->getValue($key);
		if (!is_null($object)) {
			$this->propertyNameStack[] = $key;
			$visitChild = $this->_visit($parentObject, $key, $this->propertyNameStack, $isForeign);
			if ($visitChild && (!$isForeign || ($object instanceof ComhonArray))) {
				$this->_acceptChildren($object, $isForeign);
			}
			$this->_postVisit($parentObject, $key, $this->propertyNameStack, $isForeign);
			array_pop($this->propertyNameStack);
		}
	}
	
	/**
	 * accept to visit children of specified object
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param boolean $isForeign
	 */
	private function _acceptChildren($object, $isForeign) {
		if (is_null($object)) {
			return;
		}
		if ($object instanceof ComhonArray) {
			$propertyName = $object->getModel()->getElementName();
			foreach ($object->getValues() as $key => $value) {
				$this->_accept($object, $key, $isForeign);
			}
		}
		elseif (!array_key_exists(spl_object_hash($object), $this->instanceObjectHash)) {
			$this->instanceObjectHash[spl_object_hash($object)] = $object;
			foreach ($object->getModel()->getProperties() as $propertyName => $property) {
				if (!$property->isUniqueModelSimple()) {
					$this->_accept($object, $propertyName, $property->isForeign());
				}
			}
			unset($this->instanceObjectHash[spl_object_hash($object)]);
		}
	}
	

	/**
	 * verify parameters
	 * 
	 * @param array $params
	 * @throws VisitorParameterException
	 */
	private function _verifParameters($params) {
		$parameters = $this->_getMandatoryParameters();
		if (is_array($parameters)) {
			if (!empty($parameters)) {
				if (!is_array($params)) {
					throw new VisitorParameterException(implode(', ', $parameters));
				}
				foreach ($parameters as $parameterName) {
					if (!array_key_exists($parameterName, $params)) {
						throw new VisitorParameterException($parameterName);
					}
				}
			}
		} else if (!is_null($parameters)) {
			throw new VisitorParameterException(null);
		}
	}

	/**
	 * get mandatory parameters
	 * 
	 * permit to define mandatory parameters.
	 * an exception is thrown if there are missing parameters
	 */
	abstract protected function _getMandatoryParameters();

	/**
	 * initialize visit
	 * 
	 * permit to initialize some informations before visit
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $object
	 */
	abstract protected function _init($object);
	
	/**
	 * visit object in $parentObject at $key
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $parentObject
	 * @param string $key
	 * @param string $propertyNameStack
	 * @param boolean $isForeign
	 */
	abstract protected function _visit($parentObject, $key, $propertyNameStack, $isForeign);
	
	/**
	 * called after visting all children of current object
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $parentObject
	 * @param string $key
	 * @param string $propertyNameStack
	 * @param boolean $isForeign
	 */
	abstract protected function _postVisit($parentObject, $key, $propertyNameStack, $isForeign);
	
	/**
	 * finalize visit
	 * 
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @return mixed permit to return all needed information at the end of visit
	 */
	abstract protected function _finalize($object);
}