<?php
namespace objectManagerLib\object\model;
use \Exception;

abstract class ModelContainer extends Model {

	protected $mModel;
	protected $mIsLoaded = true;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
	 */
	public function __construct($pModel) {
		if (!($pModel instanceof Model) && !($pModel instanceof ModelContainer)) {
			throw new Exception("model parameter must be an instanceof Model");
		}
		$this->mModel = $pModel;
	}
	
	public function getObjectInstance($pIsloaded = true) {
		return $this->getModel()->getObjectInstance($pIsloaded);
	}
	
	public function getModelName() {
		return $this->getModel()->getModelName();
	}
	
	public function getProperty($pPropertyName, $pThrowException = false) {
		return $this->getModel()->getProperty($pPropertyName);
	}
	
	public function getProperties() {
		return $this->getModel()->getProperties();
	}
	
	public function getPropertiesNames() {
		return $this->getModel()->getPropertiesNames();
	}
	
	public function getPropertyModel($pPropertyName) {
		return $this->getModel()->getPropertyModel($pPropertyName);
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
	
	public function hasProperty($pPropertyName) {
		return $this->getModel()->hasProperty($pPropertyName);
	}
	
	public final function getExportKeys() {
		return $this->getModel()->getExportKeys();
	}
	
	
	public function getExportKey($pKey) {
		return $this->getModel()->getExportKey($pKey);
	}
	
	public function getIdProperties() {
		return $this->getModel()->getIdProperties();
	}
	
	public function hasUniqueIdProperty() {
		return $this->getModel()->hasUniqueIdProperty();
	}
	
	public function getFirstId() {
		return $this->getModel()->getFirstId();
	}
	
	public function isLoaded() {
		return $this->mModel->isLoaded();
	}
	
	public function getSerialization() {
		return $this->getModel()->getSerialization();
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
			throw new Exception("table must be specified");
		}
		$lQuery = ($this->isNew()) ? $this->_setInsertQuery($pPDO, $pId) : $this->_setUpdateQuery($pPDO);
		$lResult = $pPDO->doQuery($lQuery);
		/*if ($lResult && is_null($this->getId())) {
			$this->_setId($pPDO->lastInsertId());
		}*/
		return $lResult;
	}
	
	public function toObject($pValue, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		throw new \Exception('must be overrided');
	}
	
	public function toXml($pObjectArray, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		throw new \Exception('must be overrided');
	}
	
	protected function _fromObject($pValue, $pLocalObjectCollection)  {
		throw new \Exception('must be overrided');
	}
	
	protected function _fromSqlColumn($pValue, $pLocalObjectCollection) {
		throw new \Exception('must be overrided');
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1 == $pValue2;
	}
}