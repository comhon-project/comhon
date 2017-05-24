<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Request;

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject;
use Comhon\Object\ObjectArray;

abstract class ObjectLoadRequest {

	protected $model;
	protected $requestChildren          = false;
	protected $loadForeignProperties    = false;
	protected $propertiesFilter;
	protected $private;
	
	public function __construct($modelName, $private = false) {
		$this->model = ModelManager::getInstance()->getInstanceModel($modelName);
		$this->private = $private;
	}
	
	/**
	 * execute resquest and return resulting object
	 * @return ComhonObject
	 */
	abstract public function execute();
	
	/**
	 *
	 * @param string[] $propertiesFilter
	 */
	public function setPropertiesFilter($propertiesFilter) {
		if (empty($propertiesFilter)) {
			return;	
		}
		$this->propertiesFilter = [];
		// ids have to be in selected columns so if they are not defined in filter, we add them
		foreach ($this->model->getIdProperties() as $property) {
			$this->propertiesFilter[] = $property->getName();
		}
		// add defined columns
		foreach ($propertiesFilter as $propertyName) {
			$property = $this->model->getProperty($propertyName, true);
			if ($property->isAggregation()) {
				throw new \Exception("aggregation property '$propertyName' can't be a filter property");
			} else if (!$this->private && $property->isPrivate()) {
				throw new \Exception("private property '$propertyName' can't be a filter property for public request");
			}
			else {
				$this->propertiesFilter[] = $propertyName;
			}
		}
		// remove possible duplicated columns
		$this->propertiesFilter = array_unique($this->propertiesFilter);
	}
		
	public function requestChildren($boolean) {
		$this->requestChildren = $boolean;
		return $this;
	}
	
	public function loadForeignProperties($boolean) {
		$this->loadForeignProperties = $boolean;
		return $this;
	}
	
	public function getModel() {
		return $this->model;
	}
	
	protected function _updateObjects(ComhonObject $object) {
		$objects = ($object instanceof ObjectArray) ? $object->getValues() : [$object];

		if ($this->requestChildren && !$this->loadForeignProperties) {
			foreach ($objects as $obj) {
				foreach ($obj->getModel()->getAggregations() as $propertyName => $aggregation) {
					$obj->loadValueIds($propertyName);
				}
			}
		}
		else if ($this->loadForeignProperties) {
			foreach ($objects as $obj) {
				foreach ($obj->getModel()->getComplexProperties() as $propertyName => $property) {
					if ($property->isAggregation() || ($property->isForeign() && !is_null($obj->getValue($propertyName)))) {
						$obj->loadValue($propertyName);
					}
				}
			}
		}
		
		return $object;
	}
	
}