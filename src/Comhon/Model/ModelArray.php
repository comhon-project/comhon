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

use Comhon\Object\ComhonArray;
use Comhon\Model\Model;
use Comhon\Object\AbstractComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\ExportException;

class ModelArray extends ModelContainer implements ModelComhonObject {
	
	/**
	 * @var string name of each element
	 *     for exemple if we have a ModelArray 'children', each element name would be 'child'
	 */
	private $elementName;
	
	/**
	 * @var boolean
	 */
	private $isAssociative;
	
	/**
	 * 
	 * @var boolean
	 */
	private $hasComplexValues;
	
	/**
	 * 
	 * @param ModelUnique $model
	 * @param boolean $isAssociative
	 * @param string $elementName
	 */
	public function __construct(ModelUnique $model, $isAssociative, $elementName) {
		parent::__construct($model);
		$this->isAssociative = $isAssociative;
		$this->elementName = $elementName;
		
		$this->hasComplexValues = !($this->_getUniqueModel() instanceof SimpleModel);
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
	 * verify if array is associative
	 *
	 * @return boolean
	 */
	public function isAssociative() {
		return $this->isAssociative;
	}
	
	/**
	 * verify if array has complex values (comhon objects)
	 *
	 * @return boolean
	 */
	public function hasComplexValues() {
		return $this->hasComplexValues;
	}
	
	/**
	 * get full qualified class name of object array
	 * 
	 * @return string
	 */
	public function getObjectClass() {
		return 'Comhon\Object\ComhonArray';
	}
	
	/**
	 * get instance of object array
	 * 
	 * @param boolean $isloaded define if instanciated object will be flaged as loaded or not
	 * @return \Comhon\Object\ComhonArray
	 */
	public function getObjectInstance($isloaded = true) {
		return new ComhonArray($this, $isloaded);
	}
	
	/**
	 * verify if during import we stay in first level object or not
	 *
	 * @param boolean $isCurrentLevelFirstLevel
	 * @return boolean
	 */
	protected function _isNextLevelFirstLevel($isCurrentLevelFirstLevel) {
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_addMainCurrentObject()
	 */
	protected function _addMainCurrentObject(AbstractComhonObject $objectArray, Interfacer $interfacer) {
		if (!($objectArray instanceof ComhonArray)) {
			throw new ArgumentException($objectArray, ComhonArray::class, 1);
		}
		if ($interfacer->hasToExportMainForeignObjects()) {
			foreach ($objectArray->getValues() as $object) {
				if (!is_null($object) && $object->getModel()->isMain() && !is_null($object->getId()) && $object->hasCompleteId()) {
					$interfacer->addMainForeignObject($interfacer->createNode('empty'), $object->getId(), $object->getModel());
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_removeMainCurrentObject()
	 */
	protected function _removeMainCurrentObject(AbstractComhonObject $objectArray, Interfacer $interfacer) {
		if (!($objectArray instanceof ComhonArray)) {
			throw new ArgumentException($objectArray, ComhonArray::class, 1);
		}
		if ($interfacer->hasToExportMainForeignObjects()) {
			foreach ($objectArray->getValues() as $object) {
				if (!is_null($object) && $object->getModel()->isMain() && !is_null($object->getId()) && $object->hasCompleteId()) {
					$interfacer->removeMainForeignObject($object->getId(), $object->getModel());
				}
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_export()
	 */
	protected function _export($objectArray, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		if (is_null($objectArray)) {
			return null;
		}
		$nodeArray = $interfacer->createArrayNode($nodeName);
		
		foreach ($objectArray->getValues() as $key => $value) {
			try {
				if ($this->isAssociative) {
					$interfacer->addAssociativeValue($nodeArray, $this->getModel()->_export($value, $key, $interfacer, $isFirstLevel), $key);
				} else {
					$interfacer->addValue($nodeArray, $this->getModel()->_export($value, $this->elementName, $interfacer, $isFirstLevel), $this->elementName);
				}
			} catch (ComhonException $e) {
				throw new ExportException($e, $key);
			}
		}
		return $nodeArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::_exportId()
	 */
	protected function _exportId(AbstractComhonObject $objectArray, $nodeName, Interfacer $interfacer) {
		$nodeArray = $interfacer->createArrayNode($nodeName);
		foreach ($objectArray->getValues() as $key => $value) {
			if (is_null($value)) {
				$interfacer->addValue($nodeArray, null, $this->elementName);
			} else {
				if ($this->isAssociative) {
					$interfacer->addAssociativeValue($nodeArray, $this->getModel()->_exportId($value, $key, $interfacer), $key);
				} else {
					$interfacer->addValue($nodeArray, $this->getModel()->_exportId($value, $this->elementName, $interfacer), $this->elementName);
				}
			}
		}
		return $nodeArray;
	}
	
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_import()
	 * 
	 * @return \Comhon\Object\ComhonArray|null
	 */
	protected function _import($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		if ($interfacer->isNullValue($interfacedObject)) {
			return null;
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
			throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getArrayNodeClasses()));
		}
		$objectArray = $this->getObjectInstance();
		foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
			try {
				if ($this->isAssociative) {
					$objectArray->setValue($key, $this->getModel()->_import($element, $interfacer, $localObjectCollection, false), $interfacer->hasToFlagValuesAsUpdated());
				} else {
					$objectArray->pushValue($this->getModel()->_import($element, $interfacer, $localObjectCollection, false), $interfacer->hasToFlagValuesAsUpdated());
				}
			} catch (ComhonException $e) {
				throw new ImportException($e, $key);
			}
		}
		return $objectArray;
	}
	
	/**
	 * create object array and for each array element, create or get comhon object according interfaced id
	 *
	 * @param mixed $interfacedId
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param boolean $isFirstLevel
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _importId($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		if (is_null($interfacedObject)) {
			return null;
		}
		$objectArray = $this->getObjectInstance();
		foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
			if ($this->isAssociative) {
				$objectArray->setValue($key, $this->getModel()->_importId($element, $interfacer, $localObjectCollection, false), $interfacer->hasToFlagValuesAsUpdated());
			} else {
				$objectArray->pushValue($this->getModel()->_importId($element, $interfacer, $localObjectCollection, false), $interfacer->hasToFlagValuesAsUpdated());
			}
		}
		return $objectArray;
	}
	
	/**
	 * import interfaced array 
	 * 
	 * build comhon object array with values from interfaced object
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return \Comhon\Object\ComhonArray
	 */
	public function import($interfacedObject, Interfacer $interfacer) {
		$this->load();
		if (is_null($interfacedObject)) {
			return null;
		}
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
			$type = is_object($interfacedObject) ? get_class($interfacedObject) : gettype($interfacedObject);
			throw new ComhonException('Argument 1 ('.$type.') imcompatible with argument 2 ('.get_class($interfacer).')');
		}
		$objectArray = $this->getObjectInstance();
		foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
			try {
				$value = $interfacer->isNullValue($element) ? null : $this->getModel()->import($element, $interfacer);
				
				if ($this->isAssociative) {
					$objectArray->setValue($key, $value, $interfacer->hasToFlagValuesAsUpdated());
				} else {
					$objectArray->pushValue($value, $interfacer->hasToFlagValuesAsUpdated());
				}
			} catch (ComhonException $e) {
				throw new ImportException($e, $key);
			}
		}
		return $objectArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::fillObject()
	 */
	public function fillObject(AbstractComhonObject $objectArray, $interfacedObject, Interfacer $interfacer) {
		$this->load();
		$this->verifValue($objectArray);
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
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
		foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
			try {
				$value = $interfacer->isNullValue($element) ? null : $this->getModel()->_importRoot($element, $interfacer, $localObjectCollection);
				
				if ($this->isAssociative) {
					$objectArray->setValue($key, $value, $interfacer->hasToFlagValuesAsUpdated());
				} else {
					$objectArray->pushValue($value, $interfacer->hasToFlagValuesAsUpdated());
				}
			} catch (ComhonException $e) {
				throw new ImportException($e, $key);
			}
		}
		$objectArray->setIsLoaded(true);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::verifValue()
	 */
	public function verifValue($value) {
		if (!($value instanceof ComhonArray) || ($value->getModel()->getModel() !== $this->getModel() && !$value->getModel()->getModel()->isInheritedFrom($this->getModel()))) {
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