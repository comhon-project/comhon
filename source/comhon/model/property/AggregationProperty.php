<?php
namespace comhon\model\property;

use comhon\object\serialization\SqlTable;
use comhon\object\ObjectArray;
use comhon\object\Object;

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
	 * @param ObjectArray $pObjectArray
	 * @param Object $pParentObject
	 * @param string[] $pPropertiesFilter
	 * @return boolean true if success
	 */
	public function loadValue(ObjectArray $pObjectArray, Object $pParentObject, $pPropertiesFilter = null) {
		if ($pObjectArray->isLoaded()) {
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
	 * @param Object $pParentObject
	 * @return boolean true if success
	 */
	public function loadValueIds(ObjectArray $pObjectArray, Object $pParentObject) {
		if (is_null($lSqlTableUnit = $this->getSqlTableUnit())) {
			throw new \Exception('aggregation has not model with sql serialization');
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
}