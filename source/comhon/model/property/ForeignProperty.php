<?php
namespace comhon\model\property;

use comhon\object\serialization\SqlTable;
use comhon\object\Object;

class ForeignProperty extends Property {
	
	public function __construct($pModel, $pName, $pSerializationName = null, $pIsPrivate = false, $pIsSerializable = true) {
		parent::__construct($pModel, $pName, $pSerializationName, false, $pIsPrivate, $pIsSerializable);
	}
	
	public function loadValue(Object $pObject, $pPropertiesFilter = null) {
		if ($pObject->isLoaded()) {
			return false;
		}
		if ($pObject->getModel() !== $this->getUniqueModel() && !$pObject->getModel()->isInheritedFrom($this->getUniqueModel())) {
			$lReflexion1 = new \ReflectionClass(get_class($pObject->getModel()));
			$lReflexion2 = new \ReflectionClass(get_class($this->getUniqueModel()));
			throw new \Exception("object not compatible with property : {$pObject->getModel()->getName()} ({$lReflexion1->getShortName()}) | {$this->getUniqueModel()->getName()} ({$lReflexion2->getShortName()})");
		}
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($lSerializationUnit)) {
			return false;
		}
		return $lSerializationUnit->loadObject($pObject, $pPropertiesFilter);
	}
	
	public function getSerialization() {
		return $this->getUniqueModel()->getSerialization();
	}
	
	public function hasSerializationUnit($pSerializationType) {
		return $this->getUniqueModel()->hasSerializationUnit($pSerializationType);
	}
	
	public function hasSqlTableUnit() {
		return $this->getUniqueModel()->hasSqlTableUnit();
	}
	
	public function getSqlTableUnit() {
		return $this->getUniqueModel()->getSqlTableUnit();
	}
	
	public function isForeign() {
		return true;
	}
	
	public function isComplex() {
		return true;
	}
	
	/**
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * @param boolean $pPrivate if true private mode, otherwise public mode
	 * @param boolean $pSerialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($pPrivate, $pSerialization) {
		return parent::isInterfaceable($pPrivate, $pSerialization) && ($pPrivate || !$this->getUniqueModel()->hasPrivateIdProperty());
	}
	
}