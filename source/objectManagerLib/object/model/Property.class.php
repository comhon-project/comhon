<?php
namespace objectManagerLib\object\model;

class Property {
	
	protected $mModel;
	protected $mName;
	protected $mSerializationName;
	protected $mIsId;
	
	public function __construct($pModel, $pName, $pSerializationName = null, $pIsId = false) {
		$this->mModel = $pModel;
		$this->mName = $pName;
		$this->mSerializationName = $pSerializationName;
		$this->mIsId = $pIsId;
	}
	
	public function getModel() {
		$this->mModel->load();
		return $this->mModel;
	}
	
	public function getUniqueModel() {
		$lUniqueModel = $this->mModel;
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
		return is_null($this->mSerializationName) ? $this->mName : $this->mSerializationName;
	}
	
	public function isId() {
		return $this->mIsId;
	}
	
	public function hasSerializationReturn() {
		return true;
	}
	
	public function save($pObject) {
		return $this->mModel->toObject($pObject);
	}
	
	public function getSerializations() {
		return null;
	}
}