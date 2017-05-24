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
use Comhon\Model\Model;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelCustom;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Exception\ControllerParameterException;

abstract class Visitor {
	
	protected $mainObject;
	protected $params;
	private   $instanceObjectHash = [];
	private   $propertyNameStack;
	
	/**
	 * execute controller
	 * @param ComhonObject $object
	 * @param array $params
	 * @param array $visitRootObject
	 * @return unknown|boolean
	 */
	public final function execute(ComhonObject $object, $params = []) {
		$this->_verifParameters($params);
		if ($object->getModel() instanceof Model) {
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
		}
		return false;
	}
	
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
	

	protected function _isVisitRootObject() {
		return true;
	}
	
	private function _verifParameters($params) {
		$parameters = $this->_getMandatoryParameters();
		if (is_array($parameters)) {
			if (!empty($parameters)) {
				if (!is_array($params)) {
					throw new ControllerParameterException(implode(', ', $parameters));
				}
				foreach ($parameters as $parameterName) {
					if (!array_key_exists($parameterName, $params)) {
						throw new ControllerParameterException($parameterName);
					}
				}
			}
		} else if (!is_null($parameters)) {
			throw new ControllerParameterException(null);
		}
	}

	protected abstract function _getMandatoryParameters();

	protected abstract function _init($object);
	
	protected abstract function _visit($parentObject, $key, $propertyNameStack);
	
	protected abstract function _postVisit($parentObject, $key, $propertyNameStack);
	
	protected abstract function _finalize($object);
}