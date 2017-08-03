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

use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Exception\ComhonException;

abstract class ModelContainer extends Model {

	/**
	 * @var Model model of object array elements
	 */
	protected $model;
	
	/** @var boolean */
	protected $isLoaded = true;
	
	/**
	 * @param Model $model contained model
	 */
	public function __construct(Model $model) {
		$this->model = $model;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getObjectClass()
	 */
	public function getObjectClass() {
		throw new ComhonException('containers models don\'t have associated class (except array and foreign model)');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getObjectInstance()
	 */
	public function getObjectInstance($isloaded = true) {
		throw new ComhonException('containers models don\'t have associated class (except array and foreign model)');
	}
	
	/**
	 * get model name of contained model
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->getModel()->getName();
	}
	
	/**
	 * get property in contained model according specified name
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getProperty()
	 */
	public function getProperty($propertyName, $throwException = false) {
		return $this->getModel()->getProperty($propertyName);
	}
	
	/**
	 * get properties of contained model
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getProperties()
	 */
	public function getProperties() {
		return $this->getModel()->getProperties();
	}
	
	/**
	 * get properties names of contained model
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getPropertiesNames()
	 */
	public function getPropertiesNames() {
		return $this->getModel()->getPropertiesNames();
	}
	
	/**
	 * get serializable properties of contained model
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getSerializableProperties()
	 */
	public function getSerializableProperties() {
		return $this->getModel()->getSerializableProperties();
	}
	
	/**
	 * get contained model
	 * 
	 * @return \Comhon\Model\Model
	 */
	public function getModel() {
		$this->model->load();
		return $this->model;
	}
	
	/**
	 * get unique contained model
	 * 
	 * a model container may contain another container so this function permit to 
	 * get the final unique model that is not a container
	 * 
	 * @return \Comhon\Model\Model
	 */
	public function getUniqueModel() {
		$uniqueModel = $this->model;
		while ($uniqueModel instanceof ModelContainer) {
			$uniqueModel = $uniqueModel->getModel();
		}
		$uniqueModel->load();
		return $uniqueModel;
	}
	
	/**
	 * verify if contained model has property with specified name
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::hasProperty()
	 */
	public function hasProperty($propertyName) {
		return $this->getModel()->hasProperty($propertyName);
	}
	
	/**
	 * get id properties of contained model
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getIdProperties()
	 */
	public function getIdProperties() {
		return $this->getModel()->getIdProperties();
	}
	
	/**
	 * verify if contained model has at least one id property
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::hasIdProperties()
	 */
	public function hasIdProperties() {
		return $this->getModel()->hasIdProperties();
	}
	
	/**
	 * verify if contained model has one and only one id property
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::hasUniqueIdProperty()
	 */
	public function hasUniqueIdProperty() {
		return $this->getModel()->hasUniqueIdProperty();
	}
	
	/**
	 * get id property of contained model if there is one and only one id property
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getUniqueIdProperty()
	 */
	public function getUniqueIdProperty() {
		return $this->getModel()->getUniqueIdProperty();
	}
	
	/**
	 * get first id property of contained model if model has at least one id property
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getFirstIdProperty()
	 */
	public function getFirstIdProperty() {
		return $this->getModel()->getFirstIdProperty();
	}
	
	/**
	 * verify if contained model is loaded or not
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::isLoaded()
	 */
	public function isLoaded() {
		return $this->model->isLoaded();
	}
	
	/**
	 * get serialization linked to contained model
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getSerialization()
	 */
	public function getSerialization() {
		return $this->getModel()->getSerialization();
	}
	
	/**
	 * get serialization settings of contained model
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getSerializationSettings()
	 */
	public function getSerializationSettings() {
		return $this->getModel()->getSerializationSettings();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_export()
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		throw new ComhonException('must be overrided');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_import()
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer, $isFirstLevel = false) {
		throw new ComhonException('must be overrided');
	}
	
}