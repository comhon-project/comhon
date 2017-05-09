<?php
namespace comhon\model;
use \Exception;
use comhon\serialization\SerializationUnit;
use comhon\interfacer\Interfacer;
use comhon\object\collection\ObjectCollection;

abstract class ModelContainer extends Model {

	protected $mModel;
	protected $mIsLoaded = true;
	
	public function __construct($pModel) {
		if (!($pModel instanceof Model)) {
			throw new Exception('model parameter must be an instanceof Model');
		}
		$this->mModel = $pModel;
	}
	
	public function getObjectClass() {
		throw new \Exception('containers models don\'t have associated class (except array and foreign model)');
	}
	
	public function getObjectInstance($pIsloaded = true) {
		throw new \Exception('containers models don\'t have associated class (except array and foreign model)');
	}
	
	public function getName() {
		return $this->getModel()->getName();
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
	
	public function getSerializableProperties() {
		return $this->getModel()->getSerializableProperties();
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
	
	public function hasIdProperties() {
		return $this->getModel()->hasIdProperties();
	}
	
	public function hasUniqueIdProperty() {
		return $this->getModel()->hasUniqueIdProperty();
	}
	
	/**
	 * get id property if there is one and only one id property
	 * @return Property|null
	 */
	public function getUniqueIdProperty() {
		return $this->getModel()->getUniqueIdProperty();
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
	
	/**
	 * @return SerializationUnit|null
	 */
	public function getSerializationSettings() {
		return $this->getModel()->getSerializationSettings();
	}
	
	/**
	 *
	 * @param Object $pObjectArray
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @param MainModel $pParentMainModel
	 * @param boolean $pIsFirstLevel
	 * @throws \Exception
	 */
	protected function _export($pValue, $pNodeName, Interfacer $pInterfacer, $pIsFirstLevel) {
		throw new \Exception('must be overrided');
	}
	
	/**
	 *
	 * @param mixed $pInterfacedObject
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsFirstLevel
	 * @return NULL|unknown
	 */
	protected function _import($pInterfacedObject, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection, MainModel $pParentMainModel, $pIsFirstLevel = false) {
		throw new \Exception('must be overrided');
	}
	
}