<?php
namespace comhon\model\property;

use comhon\object\ObjectArray;
use comhon\object\Object;
use comhon\model\SimpleModel;
use comhon\model\ModelEnum;
use comhon\model\ModelContainer;
use comhon\model\ModelInteger;
use comhon\model\ModelFloat;
use comhon\model\ModelDateTime;

class Property {

	protected $mModel;
	protected $mName;
	protected $mSerializationName;
	protected $mIsId;
	protected $mIsPrivate;
	protected $mIsSerializable;
	protected $mDefault;
	protected $mInterfaceAsNodeXml;
	
	public function __construct($pModel, $pName, $pSerializationName = null, $pIsId = false, $pIsPrivate = false, $pIsSerializable = true, $pDefault = null, $pIsInterfacedAsNodeXml = null) {
		$this->mModel = $pModel;
		$this->mName = $pName;
		$this->mSerializationName = is_null($pSerializationName) ? $this->mName : $pSerializationName;
		$this->mIsId = $pIsId;
		$this->mIsPrivate = $pIsPrivate;
		$this->mIsSerializable = $pIsSerializable;
		$this->mDefault = $pDefault;
		
		if (($this->mModel instanceof SimpleModel) || ($this->mModel instanceof ModelEnum)) {
			$this->mInterfaceAsNodeXml = is_null($pIsInterfacedAsNodeXml) ? false : $pIsInterfacedAsNodeXml;
		} else {
			if (!is_null($pIsInterfacedAsNodeXml) && !$pIsInterfacedAsNodeXml) {
				trigger_error('warning! 8th parameter is ignored, property with complex model is inevitably interfaced as node xml');
			}
			// without inheritance foreign property may be exported as attribute because only id is exported
			// but due to inheritance, model name can be exported with id so we need to export as node
			$this->mInterfaceAsNodeXml = true;
		}
		
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
	 * verifiy if property has several serialization names
	 * @return boolean
	 */
	public function hasMultipleSerializationNames() {
		return false;
	}
	
	/**
	 * verify if property is interfaceable for export/import in public/private/serialization mode
	 * @param boolean $pPrivate if true private mode, otherwise public mode
	 * @param boolean $pSerialization if true serialization mode, otherwise model mode
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfaceable($pPrivate, $pSerialization) {
		return ($pPrivate || !$this->mIsPrivate) && (!$pSerialization || $this->mIsSerializable);
	}
	
	/**
	 * verify if property is exported/imported as node for xml export/import
	 * @return boolean true if property is interfaceable
	 */
	public function isInterfacedAsNodeXml() {
		return $this->mInterfaceAsNodeXml;
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
	
	/**
	 * 
	 * @param Property $pProperty
	 * @return boolean
	 */
	public function isEqual(Property $pProperty) {
		return $this === $pProperty || (
			get_class($this)          === get_class($pProperty) &&
			$this->mModel             === $pProperty->getModel() &&
			$this->mName              === $pProperty->getName() &&
			$this->mIsId              === $pProperty->isId() &&
			$this->mIsPrivate         === $pProperty->isPrivate() &&
			$this->mDefault           === $pProperty->getDefaultValue() &&
			$this->mIsSerializable    === $pProperty->isSerializable() &&
			$this->mSerializationName === $pProperty->getSerializationName()
		);
	}
}