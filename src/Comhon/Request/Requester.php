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
use Comhon\Exception\Request\MalformedRequestException;
use Comhon\Exception\Model\PropertyVisibilityException;
use Comhon\Exception\Request\NotAllowedRequestException;

abstract class Requester {

	/** @var \Comhon\Model\Model requested model */
	protected $model;
	
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
	 * @return \Comhon\Object\AbstractComhonObject
	 */
	abstract public function execute();
	
	/**
	 * set properties that have to be exported
	 *
	 * @param string[] $propertiesFilter
	 */
	public function setPropertiesFilter($propertiesFilter) {
		if (is_null($propertiesFilter)) {
			$this->propertiesFilter = null;
			return;
		}
		$this->propertiesFilter = [];
		// ids have to be in selected columns so if they are not defined in filter, we add them
		foreach ($this->model->getIdProperties() as $property) {
			if ($this->private || !$property->isPrivate()) {
				$this->propertiesFilter[] = $property->getName();
			}
		}
		// add defined columns
		foreach ($propertiesFilter as $propertyName) {
			$property = $this->model->getProperty($propertyName, true);
			if ($property->isAggregation()) {
				throw new MalformedRequestException("aggregation property '$propertyName' can't be a filter property");
			} else if (!$this->private && $property->isPrivate()) {
				throw new PropertyVisibilityException($property, $this->model);
			}
			else {
				$this->propertiesFilter[] = $propertyName;
			}
		}
		// remove possible duplicated columns
		$this->propertiesFilter = array_unique($this->propertiesFilter);
	}
	
	/**
	 * get model
	 * 
	 * @return \Comhon\Model\Model
	 */
	public function getModel() {
		return $this->model;
	}
	
}