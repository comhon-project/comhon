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
use Comhon\Object\AbstractComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Object\Collection\ObjectCollectionInterfacer;
use Comhon\Exception\Value\UnexpectedRestrictedArrayException;
use Comhon\Model\Restriction\Restriction;
use Comhon\Model\Restriction\NotNull;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;

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
	 * @var \Comhon\Model\Restriction\Restriction[]
	 */
	private $arrayRestrictions = [];
	
	/**
	 * @var \Comhon\Model\Restriction\Restriction[]
	 */
	private $elementRestrictions = [];
	
	/**
	 * 
	 * @param \Comhon\Model\ModelUnique $model
	 * @param boolean $isAssociative
	 * @param string $elementName
	 * @param \Comhon\Model\Restriction\Restriction[] $arrayRestrictions
	 * @param \Comhon\Model\Restriction\Restriction[] $elementRestrictions
	 */
	public function __construct(ModelUnique $model, $isAssociative, $elementName, array $arrayRestrictions = [], array $elementRestrictions = []) {
		parent::__construct($model);
		$this->isAssociative = $isAssociative;
		$this->elementName = $elementName;
		
		foreach ($arrayRestrictions as $restriction) {
			if (!$restriction->isAllowedModel($this)) {
				throw new ComhonException('restriction doesn\'t allow specified model'.get_class($this));
			}
			$this->arrayRestrictions[get_class($restriction)] = $restriction;
		}
		
		foreach ($elementRestrictions as $restriction) {
			if (!$restriction->isAllowedModel($this->model)) {
				throw new ComhonException('restriction doesn\'t allow specified model'.get_class($this->model));
			}
			$this->elementRestrictions[get_class($restriction)] = $restriction;
		}
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
	 * get restrictions applied on comhon array itslef
	 *
	 * @return \Comhon\Model\Restriction\Restriction[]
	 */
	public function getArrayRestrictions() {
		return $this->arrayRestrictions;
	}
	
	/**
	 * get restrictions applied on each comhon array element
	 *
	 * @return \Comhon\Model\Restriction\Restriction[]
	 */
	public function getElementRestrictions() {
		return $this->elementRestrictions;
	}
	
	/**
	 * get full qualified class name of object array
	 * 
	 * @return string
	 */
	public function getObjectClass() {
		return ComhonArray::class;
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
	 * @see \Comhon\Model\ModelComplex::_exportRoot()
	 */
	protected function _exportRoot(AbstractComhonObject $objectArray, $nodeName, Interfacer $interfacer) {
		$objectArray->validate();
		if ($this->getModel() instanceof SimpleModel) {
			$nodeArray = $this->_export($objectArray, $nodeName, $interfacer, true, new ObjectCollectionInterfacer());
		} else {
			$nodeArray = $interfacer->createArrayNode($nodeName);
			
			foreach ($objectArray->getValues() as $key => $value) {
				try {
					if ($this->isAssociative) {
						$interfacer->addAssociativeValue($nodeArray, $this->getModel()->_exportRoot($value, $key, $interfacer), $key);
					} else {
						$interfacer->addValue($nodeArray, $this->getModel()->_exportRoot($value, $this->elementName, $interfacer), $this->elementName);
					}
				} catch (ComhonException $e) {
					throw new ExportException($e, $key);
				}
			}
		}
		
		return $nodeArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_export()
	 */
	protected function _export($objectArray, $nodeName, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		/** @var \Comhon\Object\ComhonArray $objectArray */
		if (is_null($objectArray)) {
			return null;
		}
		$objectArray->validate();
		$nodeArray = $interfacer->createArrayNode($nodeName);
		
		foreach ($objectArray->getValues() as $key => $value) {
			try {
				if ($this->isAssociative) {
					$interfacer->addAssociativeValue($nodeArray, $this->getModel()->_export($value, $key, $interfacer, $isFirstLevel, $objectCollectionInterfacer), $key);
				} else {
					$interfacer->addValue($nodeArray, $this->getModel()->_export($value, $this->elementName, $interfacer, $isFirstLevel, $objectCollectionInterfacer), $this->elementName);
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
	protected function _exportId(AbstractComhonObject $objectArray, $nodeName, Interfacer $interfacer, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		$nodeArray = $interfacer->createArrayNode($nodeName);
		foreach ($objectArray->getValues() as $key => $value) {
			if (is_null($value)) {
				$interfacer->addValue($nodeArray, null, $this->elementName);
			} else {
				if ($this->isAssociative) {
					$interfacer->addAssociativeValue($nodeArray, $this->getModel()->_exportId($value, $key, $interfacer, $objectCollectionInterfacer), $key);
				} else {
					$interfacer->addValue($nodeArray, $this->getModel()->_exportId($value, $this->elementName, $interfacer, $objectCollectionInterfacer), $this->elementName);
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
	protected function _import($interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		if ($interfacer->isNullValue($interfacedObject)) {
			return null;
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
			throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getArrayNodeClasses()));
		}
		$objectArray = $this->getObjectInstance(false);
		foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
			try {
				if ($this->isAssociative) {
					$objectArray->setValue($key, $this->getModel()->_import($element, $interfacer, false, $objectCollectionInterfacer), $interfacer->hasToFlagValuesAsUpdated());
				} else {
					$objectArray->pushValue($this->getModel()->_import($element, $interfacer, false, $objectCollectionInterfacer), $interfacer->hasToFlagValuesAsUpdated());
				}
			} catch (ComhonException $e) {
				throw new ImportException($e, $key);
			}
		}
		$objectArray->setIsLoaded(true);
		return $objectArray;
	}
	
	/**
	 * create object array and for each array element, create or get comhon object according interfaced id
	 *
	 * @param mixed $interfacedId
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _importId($interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		if ($interfacer->isNullValue($interfacedObject)) {
			return null;
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
			throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getArrayNodeClasses()));
		}
		$objectArray = $this->getObjectInstance(false);
		foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
			if ($this->isAssociative) {
				$objectArray->setValue($key, $this->getModel()->_importId($element, $interfacer, false, $objectCollectionInterfacer), $interfacer->hasToFlagValuesAsUpdated());
			} else {
				$objectArray->pushValue($this->getModel()->_importId($element, $interfacer, false, $objectCollectionInterfacer), $interfacer->hasToFlagValuesAsUpdated());
			}
		}
		$objectArray->setIsLoaded(true);
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
		$objectArray = $this->getObjectInstance(false);
		$isSimple = $this->getModel() instanceof SimpleModel;
		try {
			foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
				try {
					if ($isSimple) {
						$value = $interfacer->isNullValue($element) ? null : $this->getModel()->importSimple($element, $interfacer, true);
					} else {
						$value = $interfacer->isNullValue($element) ? null : $this->getModel()->import($element, $interfacer);
					}
					
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
		} catch (ComhonException $e) {
			throw new ImportException($e);
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
		$isSimple = $this->getModel() instanceof SimpleModel;
		if (!$isSimple && !$this->getUniqueModel()->hasIdProperties()) {
			$objectCollectionInterfacer = null;
		} else {
			$objectCollectionInterfacer = new ObjectCollectionInterfacer();
			foreach ($objectArray->getValues() as $value) {
				$objectCollectionInterfacer->addStartObject($value);
			}
		}
		try {
			$objectArray->reset();
			foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
				try {
					if ($isSimple) {
						$value = $interfacer->isNullValue($element) ? null : $this->getModel()->importSimple($element, $interfacer, true);
					} else {
						$value = $interfacer->isNullValue($element) ? null : $this->getModel()->_importRoot($element, $interfacer, $objectCollectionInterfacer);
					}
					
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
		} catch (ComhonException $e) {
			throw new ImportException($e);
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::verifValue()
	 */
	public function verifValue($value) {
		if (
			!($value instanceof ComhonArray) 
			|| (
				$value->getModel() !== $this 
				&& $value->getModel()->model !== $this->model 
				&& (
					$value->getModel()->model instanceof SimpleModel
					|| !$value->getModel()->getModel()->isInheritedFrom($this->getModel())
				)
			)
		) {
			$Obj = $this->getObjectInstance(false);
			throw new UnexpectedValueTypeException($value, $Obj->getComhonClass());
		}
		if ($value->getModel() !== $this) {
			if (!Restriction::compare($this->arrayRestrictions, $value->getModel()->getArrayRestrictions())) {
				throw new UnexpectedRestrictedArrayException($value, $this);
			}
			if (!Restriction::compare($this->elementRestrictions, $value->getModel()->getElementRestrictions())) {
				throw new UnexpectedRestrictedArrayException($value, $this);
			}
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
		if (is_null($value)) {
			if (isset($this->elementRestrictions[NotNull::class])) {
				throw new NotSatisfiedRestrictionException($value, $this->elementRestrictions[NotNull::class]);
			}
		} else {
			$this->getModel()->verifValue($value);
			if (!is_null($restriction = Restriction::getFirstNotSatisifed($this->elementRestrictions, $value))) {
				throw new NotSatisfiedRestrictionException($value, $restriction);
			}
		}
		return true;
	}
	
	/**
	 * verify if a value may be added to given comhon array
	 *
	 * @param \Comhon\Object\ComhonArray $array
	 * @return boolean
	 */
	public function verifAddValue(ComhonArray $array) {
		foreach ($this->arrayRestrictions as $restriction) {
			if (!$restriction->satisfy($array, 1)) {
				throw new NotSatisfiedRestrictionException($array, $restriction, 1);
			}
		}
		return true;
	}
	
	/**
	 * verify if a value may be removed from given comhon array
	 *
	 * @param \Comhon\Object\ComhonArray $array
	 * @return boolean
	 */
	public function verifRemoveValue(ComhonArray $array) {
		foreach ($this->arrayRestrictions as $restriction) {
			if (!$restriction->satisfy($array, $array->count() == 0 ? 0 : -1)) {
				throw new NotSatisfiedRestrictionException($array, $restriction, $array->count() == 0 ? 0 : -1);
			}
		}
		return true;
	}
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::isEqual()
	 */
	public function isEqual(AbstractModel $model) {
		return parent::isEqual($model) &&
		Restriction::compare($this->arrayRestrictions, $model->getArrayRestrictions()) &&
		Restriction::compare($this->elementRestrictions, $model->getElementRestrictions());
	}
	
}