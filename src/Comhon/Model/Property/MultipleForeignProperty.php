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

use Comhon\Model\Model;
use Comhon\Exception\ComhonException;

class MultipleForeignProperty extends ForeignProperty {

	/** @var Property[] */
	private $multipleIdProperties = [];
	
	/** @var boolean */
	private $propertiesInitialized = false;
	
	/**
	 * 
	 * @param \Comhon\Object\Model $model
	 * @param string $name
	 * @param string[] $serializationNames
	 * @param boolean $isPrivate
	 * @param boolean $isSerializable
	 */
	public function __construct(Model $model, $name, $serializationNames, $isPrivate = false, $isSerializable = true) {
		parent::__construct($model, $name, null, $isPrivate, $isSerializable);
		$this->multipleIdProperties = $serializationNames;
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
			if (count($idProperties) != count($this->multipleIdProperties)) {
				throw new ComhonException('ids properties and serialization names doesn\t match : '
					.json_encode(array_keys($idProperties)).' != '. json_encode(array_values($this->multipleIdProperties)));
			}
			$multipleIdProperties = [];
			foreach ($idProperties as $idPropertyName => $idProperty) {
				if (!array_key_exists($idProperty->getName(), $this->multipleIdProperties)) {
					throw new ComhonException('ids properties and serialization names doesn\t match : '
						.json_encode(array_keys($idProperties)).' != '. json_encode($this->multipleIdProperties));
				}
				$multipleIdProperties[$this->multipleIdProperties[$idProperty->getName()]] = $idProperty;
			}
			$this->multipleIdProperties = $multipleIdProperties;
			$this->propertiesInitialized = true;
		}
		return $this->multipleIdProperties;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Property\ForeignProperty::isInterfaceable()
	 */
	public function isInterfaceable($private, $serialization) {
		return !$serialization && parent::isInterfaceable($private, $serialization);
	}
	
}