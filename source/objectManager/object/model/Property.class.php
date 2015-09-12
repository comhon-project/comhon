<?php
namespace ObjectManagerLib\objectManager\Model;

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
	
	public function save($pObject, $pStringify = false) {
		return $this->mModel->toObject($pObject, $pStringify);
	}
	
	public function getSerialization() {
		return null;
	}
}