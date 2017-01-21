<?php
namespace comhon\object\model;
use \Exception;

abstract class ModelContainer extends Model {

	protected $mModel;
	protected $mIsLoaded = true;
	
	public function __construct($pModel) {
		if (!($pModel instanceof Model)) {
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
	
	public function getAllProperties() {
		return array_merge($this->getModel()->getIdProperties(), $this->getModel()->getProperties());
	}
	
	public function getAllPropertiesNames() {
		return $this->getModel()->getAllPropertiesNames();
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
	
	public function getFirstIdProperty() {
		return $this->getModel()->getFirstIdProperty();
	}
	
	public function isLoaded() {
		return $this->mModel->isLoaded();
	}
	
	public function getSerialization() {
		return $this->getModel()->getSerialization();
	}
	
	protected function _toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		throw new \Exception('must be overrided');
	}
	
	protected function _toXml($pObject, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		throw new \Exception('must be overrided');
	}
	
	protected function _fromStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection)  {
		throw new \Exception('must be overrided');
	}
	
	protected function _toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		throw new \Exception('must be overrided');
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		throw new \Exception('must be overrided');
	}
	
}