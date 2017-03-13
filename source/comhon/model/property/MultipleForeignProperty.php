<?php
namespace comhon\model\property;

use comhon\serialization\SqlTable;
use comhon\object\Object;

class MultipleForeignProperty extends ForeignProperty {

	private $mMultipleIdProperties = [];
	private $mPropertiesInitialized = false;
	
	public function __construct($pModel, $pName, $pSerializationNames, $pIsPrivate = false, $pIsSerializable = true) {
		parent::__construct($pModel, $pName, null, $pIsPrivate, $pIsSerializable);
		$this->mMultipleIdProperties = $pSerializationNames;
	}
	
	/**
	 * verifiy if property has several serialization names
	 * @return boolean
	 */
	public function hasMultipleSerializationNames() {
		return true;
	}
	
	public function getMultipleIdProperties() {
		if (!$this->mPropertiesInitialized) {
			$lModel = $this->getUniqueModel();
			$lIdProperties = $lModel->getIdProperties();
			if (count($lIdProperties) != count($this->mMultipleIdProperties)) {
				throw new \Exception('ids properties and serialization names doesn\t match : '
					.json_encode(array_keys($lIdProperties)).' != '. json_encode(array_values($this->mMultipleIdProperties)));
			}
			$lMultipleIdProperties = [];
			foreach ($lIdProperties as $lIdPropertyName => $lIdProperty) {
				if (!array_key_exists($lIdProperty->getName(), $this->mMultipleIdProperties)) {
					throw new \Exception('ids properties and serialization names doesn\t match : '
						.json_encode(array_keys($lIdProperties)).' != '. json_encode($this->mMultipleIdProperties));
				}
				$lMultipleIdProperties[$this->mMultipleIdProperties[$lIdProperty->getName()]] = $lIdProperty;
			}
			$this->mMultipleIdProperties = $lMultipleIdProperties;
			$this->mPropertiesInitialized = true;
		}
		return $this->mMultipleIdProperties;
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