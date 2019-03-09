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

use Comhon\Object\AbstractComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Exception\ComhonException;

class ModelForeign extends ModelContainer {

	/**
	 * 
	 * @param AbstractModel $model
	 * @throws Exception
	 */
	public function __construct(AbstractModel $model) {
		parent::__construct($model);
		if ($this->model instanceof SimpleModel) {
			throw new ComhonException('ModelForeign can\'t contain SimpleModel');
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_isNextLevelFirstLevel()
	 */
	protected function _isNextLevelFirstLevel($isCurrentLevelFirstLevel) {
		return $isCurrentLevelFirstLevel;
	}
	
	/**
	 * get full qualified class name of object associated to contained model
	 * 
	 * @return string
	 */
	public function getObjectClass() {
		return $this->getModel()->getObjectClass();
	}
	
	/**
	 * get instance of object associated to contained model
	 * 
	 * @param boolean $isloaded define if instanciated object will be flaged as loaded or not
	 * @return \Comhon\Object\AbstractComhonObject
	 */
	public function getObjectInstance($isloaded = true) {
		return $this->getModel()->getObjectInstance($isloaded);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_addMainCurrentObject()
	 */
	protected function _addMainCurrentObject(AbstractComhonObject $objectArray, Interfacer $interfacer) {
		throw new ComhonException('cannot call _addMainCurrentObject via ModelForeign');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_removeMainCurrentObject()
	 */
	protected function _removeMainCurrentObject(AbstractComhonObject $objectArray, Interfacer $interfacer) {
		throw new ComhonException('cannot call _removeMainCurrentObject via ModelForeign');
	}
	
	/**
	 * export comhon object id in specified format
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return mixed
	 */
	public function export(AbstractComhonObject $object, Interfacer $interfacer) {
		try {
			$this->verifValue($object);
			$interfacer->initializeExport();
			$node = $this->_export($object, 'root', $interfacer, true);
			if (is_object($node)) {
				$interfacer->finalizeExport($node);
			}
			return $node;
		} catch (ComhonException $e) {
			throw new ExportException($e);
		}
	}
	
	/**
	 * export comhon object to interfaced id in specified format
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::_export()
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		if (is_null($object)) {
			return null;
		}
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new ComhonException("foreign property must have model with id, actual model '{$this->getUniqueModel()->getName()}' doesn't");
		}
		return $this->getModel()->_exportId($object, $nodeName, $interfacer);
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_exportId()
	 */
	protected function _exportId(AbstractComhonObject $objectArray, $nodeName, Interfacer $interfacer) {
		throw new ComhonException('should not call _exportId via ModelForeign');
	}
	
	/**
	 * import interfaced array
	 *
	 * build comhon object array with values from interfaced object
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject|\Comhon\Object\ComhonArray
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		return $this->_import($interfacedObject, $interfacer, new ObjectCollection(), true);
	}
	
	/**
	 * import interfaced id
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::_import()
	 */
	protected function _import($value, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new ComhonException("foreign property must have model with id, actual model '{$this->getUniqueModel()->getName()}' doesn't");
		}
		return $this->getModel()->_importId($value, $interfacer, $localObjectCollection, $isFirstLevel);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_importId()
	 */
	protected function _importId($interfacedId, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		throw new ComhonException('cannot call _importId via ModelForeign');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::fillObject()
	 */
	public function fillObject(AbstractComhonObject $object, $interfacedObject, Interfacer $interfacer) {
		throw new ComhonException('cannot fill object via ModelForeign');
	}
	
	/**
	 * verify if value is correct according contained model
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		$this->model->verifValue($value);
		return true;
	}
}