<?php
namespace comhon\object\model;

use comhon\object\object\SqlTable;
use comhon\object\object\Object;

class ForeignProperty extends Property {
	
	private $mCompositionProperties = null;
	
	public function __construct($pModel, $pName, $pSerializationName = null, $pIsPrivate = false) {
		parent::__construct($pModel, $pName, $pSerializationName, false, $pIsPrivate);
	}
	
	public function loadValue(Object $pObject) {
		if ($pObject->isLoaded()) {
			return false;
		}
		if ($pObject->getModel() !== $this->getUniqueModel() && !$pObject->getModel()->isInheritedFrom($this->getUniqueModel())) {
			$lReflexion1 = new \ReflectionClass(get_class($pObject->getModel()));
			$lReflexion2 = new \ReflectionClass(get_class($this->getUniqueModel()));
			throw new \Exception("object not compatible with property : {$pObject->getModel()->getModelName()} ({$lReflexion1->getShortName()}) | {$this->getUniqueModel()->getModelName()} ({$lReflexion2->getShortName()})");
		}
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($lSerializationUnit)) {
			return false;
		}
		return $lSerializationUnit->loadObject($pObject);
	}
	
	public function getSerialization() {
		return $this->getUniqueModel()->getSerialization();
	}
	
	public function hasSerializationUnit($pSerializationType) {
		return $this->getUniqueModel()->hasSerializationUnit();
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
}