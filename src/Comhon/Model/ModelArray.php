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
use Comhon\Exception\ComhonException;
use Comhon\Exception\Value\UnexpectedValueTypeException;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Object\Collection\ObjectCollectionInterfacer;
use Comhon\Exception\Value\UnexpectedArrayException;
use Comhon\Model\Restriction\Restriction;
use Comhon\Model\Restriction\NotNull;
use Comhon\Exception\Value\NotSatisfiedRestrictionException;
use Comhon\Exception\Interfacer\IncompatibleValueException;
use Comhon\Exception\ArgumentException;

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
	 * @var boolean
	 */
	private $isNotNullElement;
	
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
	 * @param \Comhon\Model\ModelUnique|\Comhon\Model\ModelArray $model
	 * @param boolean $isAssociative
	 * @param string $elementName
	 * @param \Comhon\Model\Restriction\Restriction[] $arrayRestrictions
	 * @param \Comhon\Model\Restriction\Restriction[] $elementRestrictions
	 * @param boolean $isNotNullElement
	 */
	public function __construct(AbstractModel $model, $isAssociative, $elementName, array $arrayRestrictions = [], array $elementRestrictions = [], $isNotNullElement = false) {
		if (!($model instanceof ModelUnique) && !($model instanceof ModelArray)) {
			throw new ArgumentException(get_class($model), [ModelUnique::class, ModelArray::class], 1);
		}
		parent::__construct($model);
		$this->isAssociative = $isAssociative;
		$this->elementName = $elementName;
		$this->isNotNullElement = $isNotNullElement;
		
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
	 * verify if elements of comhon array must be not null
	 *
	 * @return boolean
	 */
	public function isNotNullElement() {
		return $this->isNotNullElement;
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::getObjectInstance()
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
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
			throw new IncompatibleValueException($interfacedObject, $interfacer);
		}
		$objectArray = $this->getObjectInstance(false);
		$isSimple = $this->getModel() instanceof SimpleModel;
		try {
			foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
				try {
					if ($isSimple) {
						$value = $interfacer->isNullValue($element) ? null : $this->getModel()->importSimple($element, $interfacer, true);
					} else {
						try {
							$value = $interfacer->isNullValue($element) ? null : $this->getModel()->import($element, $interfacer);
						} catch (IncompatibleValueException $e) {
							$Obj = $this->getModel()->getObjectInstance(false);
							throw new UnexpectedValueTypeException($element, $Obj->getComhonClass());
						}
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
			throw new IncompatibleValueException($interfacedObject, $interfacer);
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
		if (!($value instanceof ComhonArray)) {
			$Obj = $this->getObjectInstance(false);
			throw new UnexpectedValueTypeException($value, $Obj->getComhonClass());
		}
		return $this->_verifModel($value->getModel(), $value, 0);
	}
	
	/**
	 * 
	 * @param Model $modelArray
	 * @throws UnexpectedValueTypeException
	 * @throws UnexpectedArrayException
	 * @return boolean
	 */
	public function _verifModel($model, $value, $depth) {
		if ($model === $this) {
			return true;
		}
		if (!($model instanceof ModelArray)) {
			throw new UnexpectedArrayException($value, $this, $depth);
		}
		if ($this->isAssociative !== $model->isAssociative) {
			throw new UnexpectedArrayException($value, $this, $depth);
		}
		if ($this->elementName !== $model->elementName) {
			throw new UnexpectedArrayException($value, $this, $depth);
		}
		if ($this->isNotNullElement !== $model->isNotNullElement) {
			throw new UnexpectedArrayException($value, $this, $depth);
		}
		if ($model->model !== $this->model) {
			if ($this->model instanceof ModelArray) {
				$this->model->_verifModel($model->model, $value, $depth + 1);
			} elseif (!($model->getModel() instanceof Model) || !$model->getModel()->isInheritedFrom($this->getModel())) {
				throw new UnexpectedArrayException($value, $this, $depth);
			}
		}
		if (!Restriction::compare($this->arrayRestrictions, $model->getArrayRestrictions())) {
			throw new UnexpectedArrayException($value, $this, $depth);
		}
		if (!Restriction::compare($this->elementRestrictions, $model->getElementRestrictions())) {
			throw new UnexpectedArrayException($value, $this, $depth);
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
			if ($this->isNotNullElement) {
				throw new NotSatisfiedRestrictionException($value, new NotNull());
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