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
	
	private $mAggregationProperties = null;
	
	public function __construct(Model $pModel, $pName, $pAggregationProperties, $pSerializationName = null, $pIsPrivate = false) {
		parent::__construct($pModel, $pName, $pSerializationName, $pIsPrivate, false);
		if (empty($pAggregationProperties)) {
			throw new \Exception('aggregation must have at least one aggregation property');
		}
		$this->mAggregationProperties = $pAggregationProperties;
	}
	
	public function isAggregation() {
		return true;
	}
	
	public function getAggregationProperties() {
		return $this->mAggregationProperties;
	}
	
	/**
	 *
	 * @param ComhonObject $pObject
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadValue(ComhonObject $pObject, $pPropertiesFilter = null, $pForceLoad = false) {
		throw new \Exception('use loadAggregationValue function');
	}
	
	/**
	 *
	 * @param ObjectArray $pObjectArray
	 * @param ComhonObject $pParentObject
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadAggregationValue(ObjectArray $pObjectArray, ComhonObject $pParentObject, $pPropertiesFilter = null, $pForceLoad = false) {
		$this->getModel()->verifValue($pObjectArray);
		if ($pObjectArray->isLoaded() && !$pForceLoad) {
			return false;
		}
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($lSerializationUnit)) {
			throw new \Exception('aggregation has not model with sql serialization');
		}
		return $lSerializationUnit->loadAggregation($pObjectArray, $pParentObject->getId(), $this->mAggregationProperties, $pPropertiesFilter);
	}
	
	/**
	 * 
	 * @param ObjectArray $pObjectArray
	 * @param ComhonObject $pParentObject
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadValueIds(ObjectArray $pObjectArray, ComhonObject $pParentObject, $pForceLoad = false) {
		$this->getModel()->verifValue($pObjectArray);
		if (is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			throw new \Exception('aggregation has not model with sql serialization');
		}
		if ($pObjectArray->isLoaded() && !$pForceLoad) {
			return false;
		}
		return $lSqlTableUnit->loadAggregationIds($pObjectArray, $pParentObject->getId(), $this->mAggregationProperties);
	}
	
	/**
	 *
	 * @param Property $pProperty
	 * @return boolean
	 */
	public function isEqual(Property $pProperty) {
		if (count($this->mAggregationProperties) != count($pProperty->getAggregationProperties())) {
			return false;
		}
		foreach ($pProperty->getAggregationProperties() as $lPropertyName) {
			if (!in_array($lPropertyName, $this->mAggregationProperties)) {
				return false;
			}
		}
		return parent::isEqual($pProperty);
	}
	
	/**
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * @param boolean $pPrivate if true private mode, otherwise public mode
	 * @param boolean $pSerialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($pPrivate, $pSerialization) {
		return !$pSerialization && parent::isInterfaceable($pPrivate, $pSerialization);
	}
	
	/**
	 * verify if property is exportable in public/private/serialization mode
	 *
	 * @param boolean $pPrivate if true private mode, otherwise public mode
	 * @param boolean $pSerialization if true serialization mode, otherwise model mode
	 * @param mixed $pValue value that we want to export
	 * @return boolean true if property is interfaceable
	 */
	public function isExportable($pPrivate, $pSerialization, $pValue) {
		return parent::isExportable($pPrivate, $pSerialization, $pValue) && (is_null($pValue) || $pValue->isLoaded());
	}
}