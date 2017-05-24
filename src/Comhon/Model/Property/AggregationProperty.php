<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model\Property;

use Comhon\Object\ObjectArray;
use Comhon\Object\ComhonObject;
use Comhon\Model\Model;

class AggregationProperty extends ForeignProperty {
	
	private $aggregationProperties = null;
	
	public function __construct(Model $model, $name, $aggregationProperties, $serializationName = null, $isPrivate = false) {
		parent::__construct($model, $name, $serializationName, $isPrivate, false);
		if (empty($aggregationProperties)) {
			throw new \Exception('aggregation must have at least one aggregation property');
		}
		$this->aggregationProperties = $aggregationProperties;
	}
	
	public function isAggregation() {
		return true;
	}
	
	public function getAggregationProperties() {
		return $this->aggregationProperties;
	}
	
	/**
	 *
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadValue(ComhonObject $object, $propertiesFilter = null, $forceLoad = false) {
		throw new \Exception('use loadAggregationValue function');
	}
	
	/**
	 *
	 * @param ObjectArray $objectArray
	 * @param ComhonObject $parentObject
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadAggregationValue(ObjectArray $objectArray, ComhonObject $parentObject, $propertiesFilter = null, $forceLoad = false) {
		$this->getModel()->verifValue($objectArray);
		if ($objectArray->isLoaded() && !$forceLoad) {
			return false;
		}
		$serializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($serializationUnit)) {
			throw new \Exception('aggregation has not model with sql serialization');
		}
		return $serializationUnit->loadAggregation($objectArray, $parentObject->getId(), $this->aggregationProperties, $propertiesFilter);
	}
	
	/**
	 * 
	 * @param ObjectArray $objectArray
	 * @param ComhonObject $parentObject
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadValueIds(ObjectArray $objectArray, ComhonObject $parentObject, $forceLoad = false) {
		$this->getModel()->verifValue($objectArray);
		if (is_null($sqlTableUnit = $this->getSqlTableUnit())) {
			throw new \Exception('aggregation has not model with sql serialization');
		}
		if ($objectArray->isLoaded() && !$forceLoad) {
			return false;
		}
		return $sqlTableUnit->loadAggregationIds($objectArray, $parentObject->getId(), $this->aggregationProperties);
	}
	
	/**
	 *
	 * @param Property $property
	 * @return boolean
	 */
	public function isEqual(Property $property) {
		if (count($this->aggregationProperties) != count($property->getAggregationProperties())) {
			return false;
		}
		foreach ($property->getAggregationProperties() as $propertyName) {
			if (!in_array($propertyName, $this->aggregationProperties)) {
				return false;
			}
		}
		return parent::isEqual($property);
	}
	
	/**
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($private, $serialization) {
		return !$serialization && parent::isInterfaceable($private, $serialization);
	}
	
	/**
	 * verify if property is exportable in public/private/serialization mode
	 *
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @param mixed $value value that we want to export
	 * @return boolean true if property is interfaceable
	 */
	public function isExportable($private, $serialization, $value) {
		return parent::isExportable($private, $serialization, $value) && (is_null($value) || $value->isLoaded());
	}
}