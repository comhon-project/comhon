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

class MultipleForeignProperty extends ForeignProperty {

	private $multipleIdProperties = [];
	private $propertiesInitialized = false;
	
	public function __construct(Model $model, $name, $serializationNames, $isPrivate = false, $isSerializable = true) {
		parent::__construct($model, $name, null, $isPrivate, $isSerializable);
		$this->multipleIdProperties = $serializationNames;
	}
	
	/**
	 * verifiy if property has several serialization names
	 * @return boolean
	 */
	public function hasMultipleSerializationNames() {
		return true;
	}
	
	public function getMultipleIdProperties() {
		if (!$this->propertiesInitialized) {
			$model = $this->getUniqueModel();
			$idProperties = $model->getIdProperties();
			if (count($idProperties) != count($this->multipleIdProperties)) {
				throw new \Exception('ids properties and serialization names doesn\t match : '
					.json_encode(array_keys($idProperties)).' != '. json_encode(array_values($this->multipleIdProperties)));
			}
			$multipleIdProperties = [];
			foreach ($idProperties as $idPropertyName => $idProperty) {
				if (!array_key_exists($idProperty->getName(), $this->multipleIdProperties)) {
					throw new \Exception('ids properties and serialization names doesn\t match : '
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
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * @param boolean $private if true private mode, otherwise public mode
	 * @param boolean $serialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($private, $serialization) {
		return !$serialization && parent::isInterfaceable($private, $serialization);
	}
	
}