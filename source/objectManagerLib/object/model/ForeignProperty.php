<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\SqlTable;
use objectManagerLib\object\object\Object;

class ForeignProperty extends Property {
	
	private $mCompositionProperties = null;
	
	public function __construct($pModel, $pName, $pSerializationName = null) {
		parent::__construct($pModel, $pName, $pSerializationName);
	}
	
	public function loadValue(Object $pObject) {
		if ($pObject->getModel() !== $this->getUniqueModel()) {
			throw new \Exception('object not compatible with property ');
		}
		$lSerializationUnit = $this->getUniqueModel()->getSerialization();
		if (is_null($lSerializationUnit)) {
			trigger_error("+++++++++ no serial +++++++++");
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
	
}