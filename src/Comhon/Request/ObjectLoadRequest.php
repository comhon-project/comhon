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
use Comhon\Exception\MalformedRequestException;
use Comhon\Exception\PropertyVisibilityException;
use Comhon\Exception\NotAllowedRequestException;

abstract class ObjectLoadRequest {

	/** @var \Comhon\Model\Model requested model */
	protected $model;
	
	/**
	 * @var boolean determine if request has to load children (aggregation)
	 *     if self::$loadForeignProperties is false load only children ids
	 */
	protected $requestChildren          = false;
	
	/** 
	 * @var boolean determine if request has to load foreign properties
	 *     if self::$requestChildren is true load aggregations too
	 */
	protected $loadForeignProperties    = false;
	
	/** @var string[] filter that define properties that have to be exported */
	protected $propertiesFilter;
	
	/** @var boolean define export context */
	protected $private;
	
	/**
	 * 
	 * @param string $modelName
	 * @param boolean $private
	 */
	public function __construct($modelName, $private = false) {
		$this->model = ModelManager::getInstance()->getInstanceModel($modelName);
		if (!$this->model->hasSerialization()) {
			throw new NotAllowedRequestException($this->model);
		}
		$this->private = $private;
	}
	
	/**
	 * execute resquest and return resulting object
	 * 
	 * @return \Comhon\Object\ComhonObject
	 */
	abstract public function execute();
	
	/**
	 * build load request 
	 *
	 * @param \stdClass $settings
	 * @param boolean $private
	 * @return \Comhon\Request\ObjectLoadRequest
	 */
	abstract public static function buildObjectLoadRequest(\stdClass $settings, $private = false);
	
	/**
	 * set properties that have to be exported
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
				throw new MalformedRequestException("aggregation property '$propertyName' can't be a filter property");
			} else if (!$this->private && $property->isPrivate()) {
				throw new PropertyVisibilityException($propertyName);
			}
			else {
				$this->propertiesFilter[] = $propertyName;
			}
		}
		// remove possible duplicated columns
		$this->propertiesFilter = array_unique($this->propertiesFilter);
	}
	
	/**
	 * define if children will be requested
	 * 
	 * @param boolean $boolean
	 * @return ObjectLoadRequest
	 */
	public function requestChildren($boolean) {
		$this->requestChildren = $boolean;
		return $this;
	}
	
	/**
	 * define if foreign properties will be requested
	 *
	 * @param boolean $boolean
	 * @return ObjectLoadRequest
	 */
	public function loadForeignProperties($boolean) {
		$this->loadForeignProperties = $boolean;
		return $this;
	}
	
	/**
	 * get model
	 * 
	 * @return \Comhon\Model\Model
	 */
	public function getModel() {
		return $this->model;
	}
	
	/**
	 * complete retrieved comhon object 
	 * 
	 * load foreign properties and aggregations according request settings
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @return \Comhon\Object\ObjectArray
	 */
	protected function _completeObject(ComhonObject $object) {
		$objects = ($object instanceof ObjectArray) ? $object->getValues() : [$object];

		if ($this->requestChildren && !$this->loadForeignProperties) {
			foreach ($objects as $obj) {
				foreach ($obj->getModel()->getAggregationProperties() as $propertyName => $aggregation) {
					$obj->loadAggregationIds($propertyName);
				}
			}
		}
		else if ($this->loadForeignProperties) {
			foreach ($objects as $obj) {
				foreach ($obj->getModel()->getComplexProperties() as $propertyName => $property) {
					if (($property->isAggregation() && $this->requestChildren) || ($property->isForeign() && !is_null($obj->getValue($propertyName)))) {
						$obj->loadValue($propertyName);
					}
				}
			}
		}
		
		return $object;
	}
	
}