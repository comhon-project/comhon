<?php
namespace comhon\model\property;

use comhon\object\ObjectArray;
use comhon\object\Object;
use comhon\model\Model;

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
	 * @param Object $pObject
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadValue(Object $pObject, $pPropertiesFilter = null, $pForceLoad = false) {
		throw new \Exception('use loadAggregationValue function');
	}
	
	/**
	 *
	 * @param ObjectArray $pObjectArray
	 * @param Object $pParentObject
	 * @param string[] $pPropertiesFilter
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadAggregationValue(Object $pObjectArray, Object $pParentObject, $pPropertiesFilter = null, $pForceLoad = false) {
		if (!($pObjectArray instanceof ObjectArray)) {
			throw new \Exception('first parameter should be ObjectArray');
		}
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
	 * @param Object $pParentObject
	 * @param boolean $pForceLoad if object is already loaded, force to reload object
	 * @return boolean true if success
	 */
	public function loadValueIds(ObjectArray $pObjectArray, Object $pParentObject, $pForceLoad = false) {
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
		return parent::isExportable($pPrivate, $pSerialization, $pValue) && $pValue->isLoaded();
	}
}