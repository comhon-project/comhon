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
	
	public function getFirstIdPropertyName() {
		return $this->getModel()->getFirstIdPropertyName();
	}
	
	public function isLoaded() {
		return $this->mModel->isLoaded();
	}
	
	public function getSerialization() {
		return $this->getModel()->getSerialization();
	}
	
	protected function _toObject($pValue, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		throw new \Exception('must be overrided');
	}
	
	protected function _toXml($pObject, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		throw new \Exception('must be overrided');
	}
	
	protected function _fromObject($pValue, $pDateTimeZone, $pLocalObjectCollection)  {
		throw new \Exception('must be overrided');
	}
	
	protected function _fromSqlColumn($pValue, $pDateTimeZone, $pLocalObjectCollection) {
		throw new \Exception('must be overrided');
	}
	
	/*
	 * return true if $pValue1 and $pValue2 are equals
	 */
	public function isEqual($pValue1, $pValue2) {
		return $pValue1 == $pValue2;
	}
}