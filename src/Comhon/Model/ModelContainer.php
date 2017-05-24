<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Model;

use Comhon\Serialization\SerializationUnit;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;

abstract class ModelContainer extends Model {

	protected $model;
	protected $isLoaded = true;
	
	public function __construct(Model $model) {
		$this->model = $model;
	}
	
	public function getObjectClass() {
		throw new \Exception('containers models don\'t have associated class (except array and foreign model)');
	}
	
	public function getObjectInstance($isloaded = true) {
		throw new \Exception('containers models don\'t have associated class (except array and foreign model)');
	}
	
	public function getName() {
		return $this->getModel()->getName();
	}
	
	public function getProperty($propertyName, $throwException = false) {
		return $this->getModel()->getProperty($propertyName);
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
		$this->model->load();
		return $this->model;
	}
	
	public function getUniqueModel() {
		$uniqueModel = $this->model;
		while ($uniqueModel instanceof ModelContainer) {
			$uniqueModel = $uniqueModel->getModel();
		}
		$uniqueModel->load();
		return $uniqueModel;
	}
	
	public function hasProperty($propertyName) {
		return $this->getModel()->hasProperty($propertyName);
	}
	
	public final function getExportKeys() {
		return $this->getModel()->getExportKeys();
	}
	
	
	public function getExportKey($key) {
		return $this->getModel()->getExportKey($key);
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
		return $this->model->isLoaded();
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
	 * @param ComhonObject $object
	 * @param string $nodeName
	 * @param Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @throws \Exception
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		throw new \Exception('must be overrided');
	}
	
	/**
	 *
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param MainModel $parentMainModel
	 * @param boolean $isFirstLevel
	 * @return NULL|unknown
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel, $isFirstLevel = false) {
		throw new \Exception('must be overrided');
	}
	
}