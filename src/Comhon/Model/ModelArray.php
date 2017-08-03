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

use Comhon\Object\ObjectArray;
use Comhon\Model\MainModel;
use Comhon\Object\ComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\UnexpectedValueTypeException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\ExportException;

class ModelArray extends ModelContainer {
	
	/**
	 * @var string name of each element
	 *     for exemple if we have a ModelArray 'children', each element name would be 'child'
	 */
	private $elementName;
	
	/**
	 * 
	 * @param Model $model
	 * @param string $elementName
	 */
	public function __construct($model, $elementName) {
		parent::__construct($model);
		$this->elementName = $elementName;
	}
	
	/**
	 * get element name
	 * 
	 * element name is used for xml interface
	 * 
	 * @return string
	 */
	public function getElementName() {
		return $this->elementName;
	}
	
	/**
	 * get full qualified class name of object array
	 * 
	 * @return string
	 */
	public function getObjectClass() {
		return 'Comhon\Object\ObjectArray';
	}
	
	/**
	 * get instance of object array
	 * 
	 * @param boolean $isloaded define if instanciated object will be flaged as loaded or not
	 * @return \Comhon\Object\ObjectArray
	 */
	public function getObjectInstance($isloaded = true) {
		return new ObjectArray($this, $isloaded);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_addMainCurrentObject()
	 */
	protected function _addMainCurrentObject(ComhonObject $objectArray, Interfacer $interfacer) {
		if (!($objectArray instanceof ObjectArray)) {
			throw new ArgumentException($objectArray, ObjectArray::class, 1);
		}
		if ($interfacer->hasToExportMainForeignObjects()) {
			foreach ($objectArray->getValues() as $object) {
				if (!is_null($object) && ($object->getModel() instanceof MainModel) && !is_null($object->getId()) && $object->hasCompleteId()) {
					$interfacer->addMainForeignObject($interfacer->createNode('empty'), $object->getId(), $object->getModel());
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_removeMainCurrentObject()
	 */
	protected function _removeMainCurrentObject(ComhonObject $objectArray, Interfacer $interfacer) {
		if (!($objectArray instanceof ObjectArray)) {
			throw new ArgumentException($objectArray, ObjectArray::class, 1);
		}
		if ($interfacer->hasToExportMainForeignObjects()) {
			foreach ($objectArray->getValues() as $object) {
				if (!is_null($object) && ($object->getModel() instanceof MainModel) && !is_null($object->getId()) && $object->hasCompleteId()) {
					$interfacer->removeMainForeignObject($object->getId(), $object->getModel());
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::_export()
	 */
	protected function _export($objectArray, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		if (is_null($objectArray)) {
			return null;
		}
		$this->verifValue($objectArray);
		if (!$objectArray->isLoaded()) {
			return  Interfacer::__UNLOAD__;
		}
		$nodeArray = $interfacer->createArrayNode($nodeName);
		
		foreach ($objectArray->getValues() as $key => $value) {
			try {
				$this->verifElementValue($value);
				$interfacer->addValue($nodeArray, $this->getModel()->_export($value, $this->elementName, $interfacer, $isFirstLevel), $this->elementName);
			} catch (ComhonException $e) {
				throw new ExportException($e, $key);
			}
		}
		return $nodeArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_exportId()
	 */
	protected function _exportId(ComhonObject $objectArray, $nodeName, Interfacer $interfacer) {
		$this->verifValue($objectArray);
		if (!$objectArray->isLoaded()) {
			return  Interfacer::__UNLOAD__;
		}
		$nodeArray = $interfacer->createArrayNode($nodeName);
		foreach ($objectArray->getValues() as $value) {
			if (is_null($value)) {
				$interfacer->addValue($nodeArray, null, $this->elementName);
			} else {
				$this->verifElementValue($value);
				$interfacer->addValue($nodeArray, $this->getModel()->_exportId($value, $this->elementName, $interfacer), $this->elementName);
			}
		}
		return $nodeArray;
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::_import()
	 * 
	 * @return \Comhon\Object\ObjectArray|null
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer, $isFirstLevel = false) {
		if ($interfacer->isNullValue($interfacedObject)) {
			return null;
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject)) {
			throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getArrayNodeClasses()));
		}
		$objectArray = $this->getObjectInstance();
		foreach ($interfacer->getTraversableNode($interfacedObject) as $key => $element) {
			try {
				$objectArray->pushValue($this->getModel()->_import($element, $interfacer, $localObjectCollection, $mainModelContainer, $isFirstLevel), $interfacer->hasToFlagValuesAsUpdated());
			} catch (ComhonException $e) {
				throw new ImportException($e, $key);
			}
		}
		return $objectArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_importId()
	 */
	protected function _importId($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer) {
		if (is_null($interfacedObject)) {
			return null;
		}
		$objectArray = $this->getObjectInstance();
		foreach ($interfacer->getTraversableNode($interfacedObject) as $element) {
			$objectArray->pushValue($this->getModel()->_importId($element, $interfacer, $localObjectCollection, $mainModelContainer), $interfacer->hasToFlagValuesAsUpdated());
		}
		return $objectArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::import()
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		$this->load();
		if (is_null($interfacedObject)) {
			return null;
		}
		if (!($this->getModel() instanceof MainModel)) {
			throw new ComhonException('can\'t apply function. Only callable from ModelArray that contain MainModel');
		}
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject)) {
			$type = is_object($interfacedObject) ? get_class($interfacedObject) : gettype($interfacedObject);
			throw new ComhonException('Argument 1 ('.$type.') imcompatible with argument 2 ('.get_class($interfacer).')');
		}
		$objectArray = $this->getObjectInstance();
		foreach ($interfacer->getTraversableNode($interfacedObject) as $key => $element) {
			try {
				$value = $interfacer->isNullValue($element) ? null : $this->getModel()->import($element, $interfacer);
				$objectArray->pushValue($value, $interfacer->hasToFlagValuesAsUpdated());
			} catch (ComhonException $e) {
				throw new ImportException($e, $key);
			}
		}
		return $objectArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::fillObject()
	 */
	public function fillObject(ComhonObject $objectArray, $interfacedObject, Interfacer $interfacer) {
		$this->load();
		$this->verifValue($objectArray);
		if (!($this->getModel() instanceof MainModel)) {
			throw new ComhonException('can\'t apply function. Only callable from ModelArray that contain MainModel');
		}
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject)) {
			$type = is_object($interfacedObject) ? get_class($interfacedObject) : gettype($interfacedObject);
			throw new ComhonException('Argument 1 ('.$type.') imcompatible with argument 2 ('.get_class($interfacer).')');
		}
		$localObjectCollection = new ObjectCollection();
		if ($interfacer->getMergeType() !== Interfacer::NO_MERGE) {
			foreach ($objectArray->getValues() as $value) {
				$localObjectCollection->addObject($value);
			}
		}
		$objectArray->reset();
		foreach ($interfacer->getTraversableNode($interfacedObject) as $key => $element) {
			try {
				$value = $interfacer->isNullValue($element) ? null : $this->getModel()->_importMain($element, $interfacer, $localObjectCollection);
				$objectArray->pushValue($value, $interfacer->hasToFlagValuesAsUpdated());
			} catch (ComhonException $e) {
				throw new ImportException($e, $key);
			}
		}
		$objectArray->setIsLoaded(true);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::verifValue()
	 */
	public function verifValue($value) {
		if (!($value instanceof ObjectArray) || ($value->getModel()->getModel() !== $this->getModel() && !$value->getModel()->getModel()->isInheritedFrom($this->getModel()))) {
			$Obj = $this->getObjectInstance();
			throw new UnexpectedValueTypeException($value, $Obj->getComhonClass());
		}
		return true;
	}
	
	/**
	 * verify if value is correct according element model in object array 
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifElementValue($value) {
		return is_null($value) ? true : $this->getModel()->verifValue($value);
	}
	
}