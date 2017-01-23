<?php
namespace comhon\object\model;

use comhon\object\object\SqlTable;
use comhon\object\object\ObjectArray;
use comhon\object\object\Object;

class AggregationProperty extends ForeignProperty {
	
	private $mAggregationProperties = null;
	
	public function __construct($pModel, $pName, $pAggregationProperties, $pSerializationName = null, $pIsPrivate = false) {
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
	 * @param Object $pObjectArray
	 * @param strong|integer $pParentObject
	 * @return boolean true if success
	 */
	public function loadValue(ObjectArray $pObjectArray, Object $pParentObject) {
		if ($pObjectArray->isLoaded()) {
			return false;
		}
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($lSerializationUnit)) {
			throw new \Exception('aggregation has not model with sql serialization');
		}
		return $lSerializationUnit->loadAggregation($pObjectArray, $pParentObject->getId(), $this->mAggregationProperties, false);
	}
	
	/**
	 * 
	 * @param ObjectArray $pObjectArray
	 * @param strong|integer $pParentId
	 * @return boolean true if success
	 */
	public function loadValueIds(ObjectArray $pObjectArray, Object $pParentObject) {
		if (is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			throw new \Exception('aggregation has not model with sql serialization');
		}
		return $lSqlTableUnit->loadAggregation($pObjectArray, $pParentObject->getId(), $this->mAggregationProperties, true);
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
}