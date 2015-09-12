<?php
namespace GenLib\objectManager\Model;
use \Exception;

abstract class ModelContainer {

	protected $mModel;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
	 */
	public function __construct($pModel) {
		if ($pModel instanceof GenLib\objectManager\Model\Model) {
			throw new Exception("model parameter must be an instanceof Model");
		}
		$this->mModel = $pModel;
	}
	
	public function getModelName() {
		return $this->mModel->getModelName();
	}
	
	public function getProperty($pPropertyName) {
		return $this->mModel->getProperty($pPropertyName);
	}
	
	public function getProperties() {
		return $this->mModel->getProperties();
	}
	
	public function getPropertiesNames() {
		return $this->mModel->getPropertiesNames();
	}
	
	public function getPropertyModel($pPropertyName) {
		return $this->mModel->getPropertyModel($pPropertyName);
	}
	
	public function getModel() {
		return $this->mModel;
	}
	
	public function hasProperty($pPropertyName) {
		return $this->mModel->hasProperty($pPropertyName);
	}
	
	public final function getExportKeys() {
		return $this->mModel->getExportKeys();
	}
	
	
	public function getExportKey($pKey) {
		return $this->mModel->getExportKey($pKey);
	}
	
	public function getIds() {
		return $this->mModel->getIds();
	}
	
	public function load() {
		$this->mModel->load();
	}
	
	public function isLoaded() {
		return $this->mModel->isLoaded();
	}
	
	/*
	 * this function can insert or update a row in dataBase
	 * if the attribut "id" is set in this instance, that will be an update, otherwise that will be an insert
	 * if $pId is specified that will force to set the id to $pId (only if it's an insert)
	 */
	public function save($pPDO, $pId = null) {
		global $gPostgres;
		$lResult = null;
		if (is_null($this->_initTable())) {
			trigger_error("table must be specified");
			throw new Exception("table must be specified");
		}
		$lQuery = ($this->isNew()) ? $this->_setInsertQuery($pPDO, $pId) : $this->_setUpdateQuery($pPDO);
		$lResult = $pPDO->doQuery($lQuery);
		/*if ($lResult && is_null($this->getId())) {
			$this->_setId($pPDO->lastInsertId());
		}*/
		return $lResult;
	}
	
	public function toObject($pValue, $pUseSerializationName = false, $pExportForeignObject = false) {
		return $this->mModel->toObject($pValue, $pUseSerializationName, $pExportForeignObject);
	}
	
	public function fromObject($pValue) {
		return $this->mModel->fromObject($pValue);
	}
	
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1 == $pValue2;
	}
}