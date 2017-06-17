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

use Comhon\Object\ComhonObject;
use Comhon\Object\ObjectArray;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelCustom;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Exception\VisitorParameterException;

abstract class Visitor {
	
	/** @var \Comhon\Object\Object main object to visit */
	protected $mainObject;
	
	/** @var array parameters to apply on visitor */
	protected $params;
	
	/** @var array instances of all visisted objects to avoid infinite loop */
	private   $instanceObjectHash = [];
	
	/** @var string[] stack of all properies names  already visited */
	private   $propertyNameStack;
	
	/**
	 * execute visitor
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @param array $params
	 * @return mixed|boolean
	 */
	final public function execute(ComhonObject $object, $params = []) {
		$this->_verifParameters($params);
		$this->propertyNameStack = [];
		$this->mainObject        = $object;
		$this->params            = $params;	

		$this->_init($object);
		
		if ($this->_isVisitRootObject()) {
			$modelName   = $object->getModel()->getName();
			$property    = new ForeignProperty($object->getModel(), $modelName);
			$customModel = new ModelCustom('modelCustom', [$property]);
			$rootObject  = $customModel->getObjectInstance();
			$rootObject->setValue($modelName, $object);
			$this->_accept($rootObject, $modelName, $modelName);
		} else {
			$this->_acceptChildren($object);
		}
		
		return $this->_finalize($object);
		return false;
	}
	
	/**
	 * accept to visit object of specified parent
	 * 
	 * @param \Comhon\Object\ComhonObject $parentObject
	 * @param string $key
	 * @param string $propertyName
	 */
	private function _accept($parentObject, $key, $propertyName) {
		if (!is_null($parentObject->getValue($key))) {
			$this->propertyNameStack[] = $propertyName;
			$visitChild = $this->_visit($parentObject, $key, $this->propertyNameStack);
			if ($visitChild) {
				$this->_acceptChildren($parentObject->getValue($key));
			}
			$this->_postVisit($parentObject, $key, $this->propertyNameStack);
			array_pop($this->propertyNameStack);
		}
	}
	
	/**
	 * accept to visit children of specified object
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 */
	private function _acceptChildren($object) {
		if (is_null($object)) {
			return;
		}
		if ($object instanceof ObjectArray) {
			$propertyName = $object->getModel()->getElementName();
			foreach ($object->getValues() as $key => $value) {
				$this->_accept($object, $key, $propertyName);
			}
		}
		else if (!array_key_exists(spl_object_hash($object), $this->instanceObjectHash)) {
			$this->instanceObjectHash[spl_object_hash($object)] = $object;
			foreach ($object->getModel()->getProperties() as $propertyName => $property) {
				if (! ($property->getUniqueModel() instanceof SimpleModel)) {
					$this->_accept($object, $propertyName, $propertyName);
				}
			}
			unset($this->instanceObjectHash[spl_object_hash($object)]);
		}
	}
	

	/**
	 * verify if visitor has to visit root object
	 * 
	 * @return boolean
	 */
	protected function _isVisitRootObject() {
		return true;
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
	 * @param \Comhon\Object\ComhonObject $object
	 */
	abstract protected function _init($object);
	
	/**
	 * visit object in $parentObject at $key
	 * 
	 * @param \Comhon\Object\ComhonObject $parentObject
	 * @param string $key
	 * @param string $propertyNameStack
	 */
	abstract protected function _visit($parentObject, $key, $propertyNameStack);
	
	/**
	 * called after visting all children of current object
	 * 
	 * @param \Comhon\Object\ComhonObject $parentObject
	 * @param string $key
	 * @param string $propertyNameStack
	 */
	abstract protected function _postVisit($parentObject, $key, $propertyNameStack);
	
	/**
	 * finalize visit
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @return mixed permit to return all needed information at the end of visit
	 */
	abstract protected function _finalize($object);
}