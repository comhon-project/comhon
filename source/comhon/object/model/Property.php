<?php
namespace comhon\object\model;

use comhon\object\object\ObjectArray;
use comhon\object\object\Object;

class Property {
	
	protected $mModel;
	protected $mName;
	protected $mSerializationName;
	protected $mIsId;
	protected $mIsPrivate;
	protected $mDefault;
	protected $mIsSerializable;
	
	public function __construct($pModel, $pName, $pSerializationName = null, $pIsId = false, $pIsPrivate = false, $pIsSerializable = true, $pDefault = null) {
		$this->mModel = $pModel;
		$this->mName = $pName;
		$this->mSerializationName = is_null($pSerializationName) ? $this->mName : $pSerializationName;
		$this->mIsId = $pIsId;
		$this->mIsPrivate = $pIsPrivate;
		$this->mDefault = $pDefault;
		$this->mIsSerializable = $pIsSerializable;
		
		if ($this->mIsId && !($this->mModel instanceof SimpleModel)) {
			throw new \Exception("id property with name '$pName' must be a simple model");
		}
	}
	
	/**
	 * @return Model
	 */
	public function getModel() {
		$this->mModel->load();
		return $this->mModel;
	}
	
	public function getUniqueModel() {
		$lUniqueModel = $this->getModel();
		while ($lUniqueModel instanceof ModelContainer) {
			$lUniqueModel = $lUniqueModel->getModel();
		}
		$lUniqueModel->load();
		return $lUniqueModel;
	}
	
	public function getName() {
		return $this->mName;
	}
	
	public function getSerializationName() {
		return $this->mSerializationName;
	}
	
	public function isId() {
		return $this->mIsId;
	}
	
	public function isPrivate() {
		return $this->mIsPrivate;
	}
	
	public function isSerializable() {
		return $this->mIsSerializable;
	}
	
	public function isInteger() {
		return ($this->mModel instanceof ModelInteger) 
		|| (($this->mModel instanceof ModelEnum) && ($this->mModel->getModel() instanceof ModelInteger));
	}
	
	public function isFloat() {
		return ($this->mModel instanceof ModelFloat)
		|| (($this->mModel instanceof ModelEnum) && ($this->mModel->getModel() instanceof ModelFloat));
	}
	
	public function hasDefaultValue() {
		return !is_null($this->mDefault);
	}
	
	public function getDefaultValue() {
		if ($this->mModel instanceof ModelDateTime) {
			return new \DateTime($this->mDefault);
		}
		return $this->mDefault;
	}
	
	public function getSerialization() {
		return null;
	}
	
	public function isAggregation() {
		return false;
	}
	
	public function isForeign() {
		return false;
	}
	
	/**
	 * check if property is interfaceable for export/import in public/private/serialization mode
	 * @param boolean $pPrivate if true private mode, otherwise public mode
	 * @param boolean $pSerialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($pPrivate, $pSerialization) {
		return ($pPrivate || !$this->mIsPrivate) && (!$pSerialization || $this->mIsSerializable);
	}
	
	public function getAggregationProperties() {
		return null;
	}
	
	public function loadValue(Object $pObject) {
		throw new \Exception('cannot load object, property is not foreign property');
	}
	
	public function loadValueIds(ObjectArray $pObject, Object $pParentObject) {
		throw new \Exception('cannot load aggregation ids, property is not aggregation property');
	}
}