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
use Comhon\Object\Collection\MainObjectCollection;

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
	 * @var boolean
	 */
	private $isIsolatedElement;
	
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
	 * @param boolean $isIsolatedElement
	 */
	public function __construct(AbstractModel $model, $isAssociative, $elementName, array $arrayRestrictions = [], array $elementRestrictions = [], $isNotNullElement = false, $isIsolatedElement = false) {
		if (!($model instanceof ModelUnique) && !($model instanceof ModelArray)) {
			throw new ArgumentException(get_class($model), [ModelUnique::class, ModelArray::class], 1);
		}
		parent::__construct($model);
		$this->isAssociative = $isAssociative;
		$this->elementName = $elementName;
		$this->isNotNullElement = $isNotNullElement;
		$this->isIsolatedElement = $isIsolatedElement;
		
		if ($this->isIsolatedElement && !($this->model instanceof Model)) {
			throw new ComhonException('only ModelArray with contained model instance of '.Model::class.' may be isolated');
		}
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
	 * verify if elements of comhon array are isolated
	 *
	 * @return boolean
	 */
	public function isIsolatedElement() {
		return $this->isIsolatedElement;
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
	 * get model array dimensions count
	 *
	 * @return integer
	 */
	public function getDimensionsCount() {
		return $this->model instanceof ModelArray
		? ($this->model->getDimensionsCount() + 1)
		: 1;
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
		if ($interfacer->mustValidate()) {
			$objectArray->validate();
		}
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
	protected function _export($objectArray, $nodeName, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer, $isolate = false) {
		/** @var \Comhon\Object\ComhonArray $objectArray */
		if (is_null($objectArray)) {
			return null;
		}
		if ($interfacer->mustValidate()) {
			$objectArray->validate();
		}
		$nodeArray = $interfacer->createArrayNode($nodeName);
		
		foreach ($objectArray->getValues() as $key => $value) {
			try {
				if ($this->isAssociative) {
					$interfacer->addAssociativeValue(
						$nodeArray, 
						$this->getModel()->_export($value, $key, $interfacer, $isFirstLevel, $objectCollectionInterfacer, $this->isIsolatedElement),
						$key
					);
				} else {
					$interfacer->addValue(
						$nodeArray,
							$this->getModel()->_export($value, $this->elementName, $interfacer, $isFirstLevel, $objectCollectionInterfacer, $this->isIsolatedElement),
						$this->elementName
					);
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
	protected function _import($interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer, $isolate = false) {
		if ($interfacer->isNullValue($interfacedObject)) {
			return null;
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
			throw new UnexpectedValueTypeException($interfacedObject, implode(' or ', $interfacer->getArrayNodeClasses()));
		}
		$objectArray = $this->getObjectInstance(false);
		return $this->_fillObjectArray($objectArray, $interfacedObject, $interfacer, $isFirstLevel, $objectCollectionInterfacer);
	}
	
	/**
	 * 
	 * @param ComhonArray $objectArray
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @throws ImportException
	 * @return \Comhon\Object\ComhonArray
	 */
	protected function _fillObjectArray(ComhonArray $objectArray, $interfacedObject, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		$setIsLoaded = $isFirstLevel && $interfacer->hasToFlagObjectAsLoaded() && $this->model instanceof ModelComhonObject;
		foreach ($interfacer->getTraversableNode($interfacedObject, $this->isAssociative) as $key => $element) {
			try {
				$value = $this->getModel()->_import($element, $interfacer, $isFirstLevel, $objectCollectionInterfacer, $this->isIsolatedElement);
				if ($setIsLoaded && !is_null($value)) {
					$value->setIsLoaded(true);
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
		if ($isFirstLevel && $interfacer->hasToVerifyReferences()) {
			$this->_verifyReferences($objectArray, $objectCollectionInterfacer);
		}
		if ($interfacer->mustValidate()) {
			$objectArray->validate();
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
		if ($interfacer->mustValidate()) {
			$objectArray->validate();
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
	 * @param boolean $forceIsolateElements force isolate each elements of imported array 
	 * (isolated element doesn't share objects instances with others elements)
	 * @throws \Exception
	 * @return \Comhon\Object\ComhonArray
	 */
	public function import($interfacedObject, Interfacer $interfacer, $forceIsolateElements = true) {
		$this->load();
		if ($interfacedObject instanceof \SimpleXMLElement) {
			$interfacedObject = dom_import_simplexml($interfacedObject);
		}
		if (!$interfacer->isArrayNodeValue($interfacedObject, $this->isAssociative)) {
			throw new IncompatibleValueException($interfacedObject, $interfacer);
		}
		$objectCollectionInterfacer = new ObjectCollectionInterfacer();
		if ($interfacer->getMergeType() == interfacer::OVERWRITE) {
			$uniqueModel = $this->getUniqueModel();
			if ($uniqueModel instanceof Model && $uniqueModel->isMain()) {
				$this->_resetMainObjets($interfacedObject, $interfacer);
			}
		}
		try {
			if ($forceIsolateElements && !$this->isIsolatedElement && $this->model instanceof Model) {
				$this->isIsolatedElement = true;
				$objectArray = $this->_import($interfacedObject, $interfacer, true, $objectCollectionInterfacer);
				$this->isIsolatedElement = false;
			} else {
				$objectArray = $this->_import($interfacedObject, $interfacer, true, $objectCollectionInterfacer);
			}
		} catch (ComhonException $e) {
			throw new ImportException($e);
		}
		return $objectArray;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelComplex::fillObject()
	 * @param boolean $forceIsolateElements force isolate each elements of imported array 
	 * (isolated element doesn't share objects instances with others elements)
	 */
	public function fillObject(AbstractComhonObject $objectArray, $interfacedObject, Interfacer $interfacer, $forceIsolateElements = true) {
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
		if ($interfacer->getMergeType() == interfacer::OVERWRITE) {
			$uniqueModel = $this->getUniqueModel();
			if ($uniqueModel instanceof Model && $uniqueModel->isMain()) {
				$this->_resetMainObjets($interfacedObject, $interfacer);
			}
		}
		try {
			$objectArray->reset();
			if ($forceIsolateElements && !$this->isIsolatedElement && $this->model instanceof Model) {
				$this->isIsolatedElement = true;
				$this->_fillObjectArray($objectArray, $interfacedObject, $interfacer, true, $objectCollectionInterfacer);
				$this->isIsolatedElement = false;
			} else {
				$this->_fillObjectArray($objectArray, $interfacedObject, $interfacer, true, $objectCollectionInterfacer);
			}
		} catch (ComhonException $e) {
			throw new ImportException($e);
		}
	}
	
	/**
	 * 
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 */
	private function _resetMainObjets($interfacedObject, Interfacer $interfacer) {
		$model = $this->model;
		$isSubModelArray = $model instanceof ModelArray;
		foreach ($interfacer->getTraversableNode($interfacedObject) as $value) {
			if ($isSubModelArray) {
				if ($interfacer->isArrayNodeValue($value, $model->isAssociative)) {
					$model->_resetMainObjets($value, $interfacer);
				}
			} else {
				if ($interfacer->isNodeValue($value)) {
					$inheritance = $model->_getInheritedModelName($value, $interfacer, true);
					/** @var \Comhon\Model\Model $inheritanceModel */
					$inheritanceModel = is_null($inheritance) ? $model : $model->_getInheritedModel($inheritance);
					$id = $inheritanceModel->getIdFromInterfacedObject($value, $interfacer, true);
					
					if (!is_null($object = MainObjectCollection::getInstance()->getObject($id, $inheritanceModel->getName()))) {
						$object->reset(false);
					}
				}
			}
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
		if ($this->isIsolatedElement !== $model->isIsolatedElement) {
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