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

use Comhon\Exception\ComhonException;
use Comhon\Model\ModelForeign;
use Comhon\Model\Singleton\ModelManager;

class MultipleForeignProperty extends ForeignProperty {
	
	/** @var Property[] */
	private $multipleIdProperties = [];
	
	/** @var Property[] */
	private $multipleIdPropertiesNames = [];
	
	/** @var boolean */
	private $propertiesInitialized = false;
	
	/**
	 * 
	 * @param \Comhon\Model\ModelForeign $model
	 * @param string $name
	 * @param string[] $serializationNames
	 * @param boolean $isPrivate
	 * @param boolean $isRequired
	 * @param boolean $isSerializable
	 * @param boolean $isNotNull
	 * @param boolean $dependencies
	 */
	public function __construct(ModelForeign $model, $name, $serializationNames, $isPrivate = false, $isRequired = false, $isSerializable = true, $isNotNull = false, $dependencies = []) {
		parent::__construct($model, $name, null, $isPrivate, $isRequired, $isSerializable, $isNotNull, $dependencies);
		$this->multipleIdPropertiesNames = $serializationNames;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::hasMultipleSerializationNames()
	 */
	public function hasMultipleSerializationNames() {
		return true;
	}
	
	/**
	 * get multiple id properties
	 * 
	 * @throws \Exception
	 * @return \Comhon\Model\Property\Property[]
	 */
	public function getMultipleIdProperties() {
		if (!$this->propertiesInitialized) {
			$model = $this->getUniqueModel();
			$idProperties = $model->getIdProperties();
			if (count($idProperties) != count($this->multipleIdPropertiesNames)) {
				throw new ComhonException(
					'ids properties and serialization names doesn\t match : '
					.json_encode(array_keys($idProperties)).' != '. json_encode(array_values($this->multipleIdPropertiesNames))
				);
			}
			foreach ($idProperties as $idProperty) {
				if (!array_key_exists($idProperty->getName(), $this->multipleIdPropertiesNames)) {
					throw new ComhonException(
						'ids properties and serialization names doesn\t match : '
						.json_encode(array_keys($idProperties)).' != '. json_encode($this->multipleIdPropertiesNames)
					);
				}
				$this->multipleIdProperties[$this->multipleIdPropertiesNames[$idProperty->getName()]] = $idProperty;
			}
			$this->propertiesInitialized = true;
		}
		return $this->multipleIdProperties;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\Property::isEqual()
	 */
	public function isEqual(Property $property) {
		if (
			!($property instanceof MultipleForeignProperty) 
			|| count($this->getMultipleIdProperties()) != count($property->getMultipleIdProperties())
		) {
			return false;
		}
		foreach ($property->getMultipleIdProperties() as $serializationName => $idProperty) {
			if (!array_key_exists($serializationName, $this->getMultipleIdProperties())) {
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
	 * @see \Comhon\Model\Property\Property::getLiteralModel()
	 */
	public function getLiteralModel() {
		return ModelManager::getInstance()->getInstanceModel('string');
	}
	
	/**
	 * serialize model.
	 * this function must be called only in caching context.
	 */
	public function serialize() {
		parent::serialize();
		$this->multipleIdProperties = [];
		$this->propertiesInitialized = false;
	}
	
}