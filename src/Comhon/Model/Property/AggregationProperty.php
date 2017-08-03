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
use Comhon\Object\ObjectUnique;
use Comhon\Model\Model;
use Comhon\Exception\ComhonException;
use Comhon\Serialization\SqlTable;

class AggregationProperty extends ForeignProperty {
	
	/** @var Property[] */
	private $aggregationProperties = null;
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $name
	 * @param Property[] $aggregationProperties
	 * @param string $serializationName
	 * @param boolean $isPrivate
	 * @throws \Exception
	 */
	public function __construct(Model $model, $name, $aggregationProperties, $serializationName = null, $isPrivate = false) {
		parent::__construct($model, $name, $serializationName, $isPrivate, false);
		if (empty($aggregationProperties)) {
			throw new ComhonException('aggregation must have at least one aggregation property');
		}
		$this->aggregationProperties = $aggregationProperties;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isAggregation()
	 */
	public function isAggregation() {
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::getAggregationProperties()
	 */
	public function getAggregationProperties() {
		return $this->aggregationProperties;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\ForeignProperty::loadValue()
	 * @throws \Exception cannot call this function for aggregation
	 */
	public function loadValue(ObjectUnique $object, $propertiesFilter = null, $forceLoad = false) {
		throw new ComhonException('use self::loadAggregationValue() function');
	}
	
	/**
	 * load aggregation value
	 *
	 * @param \Comhon\Object\ObjectArray $objectArray
	 * @param \Comhon\Object\ObjectUnique $parentObject
	 * @param string[] $propertiesFilter
	 * @param boolean $forceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadAggregationValue(ObjectArray $objectArray, ObjectUnique $parentObject, $propertiesFilter = null, $forceLoad = false) {
		$this->getModel()->verifValue($objectArray);
		if ($objectArray->isLoaded() && !$forceLoad) {
			return false;
		}
		$serializationUnit = $this->getUniqueModel()->getSerialization();
		if (!($serializationUnit instanceof SqlTable)) {
			throw new ComhonException('aggregation doesn\'t have model with sql serialization');
		}
		return $serializationUnit->loadAggregation($objectArray, $parentObject->getId(), $this->aggregationProperties, $propertiesFilter);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::loadAggregationIds()
	 */
	public function loadAggregationIds(ObjectArray $objectArray, ObjectUnique $parentObject, $forceLoad = false) {
		$this->getModel()->verifValue($objectArray);
		if (is_null($sqlTableUnit = $this->getUniqueModel()->getSqlTableUnit())) {
			throw new ComhonException('aggregation doesn\'t have model with sql serialization');
		}
		if ($objectArray->isLoaded() && !$forceLoad) {
			return false;
		}
		return $sqlTableUnit->loadAggregationIds($objectArray, $parentObject->getId(), $this->aggregationProperties);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isEqual()
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\ForeignProperty::isInterfaceable()
	 */
	public function isInterfaceable($private, $serialization) {
		return !$serialization && parent::isInterfaceable($private, $serialization);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isExportable()
	 */
	public function isExportable($private, $serialization, $value) {
		return parent::isExportable($private, $serialization, $value) && (is_null($value) || $value->isLoaded());
	}
}