<?php
namespace comhon\object\model;

use comhon\object\object\ObjectArray;
use comhon\object\object\Object;

class Property {
	
	protected $mModel;
	protected $mName;
	protected $mSerializationName;
	protected $mIsId;
	
	public function __construct($pModel, $pName, $pSerializationName = null, $pIsId = false) {
		$this->mModel = $pModel;
		$this->mName = $pName;
		$this->mSerializationName = is_null($pSerializationName) ? $this->mName : $pSerializationName;
		$this->mIsId = $pIsId;
		
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
	
	public function save($pObject) {
		return $this->getModel()->toObject($pObject);
	}
	
	public function getSerialization() {
		return null;
	}
	
	public function isComposition() {
		return false;
	}
	
	public function getCompositionProperties() {
		return null;
	}
	
	public function loadValue(Object $pObject) {
		throw new \Exception('cannot load object, property is not foreign property');
	}
	
	public function loadValueIds(ObjectArray $pObject, $pParentId) {
		throw new \Exception('cannot load composition ids, property is not composition property');
	}
}