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
use Comhon\Exception\ComhonException;
use Comhon\Object\Collection\ObjectCollectionInterfacer;
use Comhon\Visitor\ObjectFinder;
use Comhon\Exception\Interfacer\InterfaceException;
use Comhon\Exception\Interfacer\NotReferencedValueException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Object\ComhonArray;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Object\UniqueObject;
use Comhon\Object\Collection\ObjectCollection;

abstract class ModelComplex extends AbstractModel {
	
	/**
	 * @var integer[] array used to avoid infinite loop when objects are visited
	 */
	protected static $instanceObjectHash = [];
	
	/**
	 *
	 * @param \Comhon\Object\UniqueObject $object
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @throws \Comhon\Exception\ComhonException
	 */
	protected function _verifyReferences(AbstractComhonObject $object, ObjectCollectionInterfacer $objectCollectionInterfacer) {
		$objects = $objectCollectionInterfacer->getNotReferencedObjects();
		if (!empty($objects)) {
			$objectFinder = new ObjectFinder();
			foreach ($objects as $obj) {
				$statck = $objectFinder->execute(
					$object,
					[
						ObjectFinder::ID => $obj->getId(),
						ObjectFinder::MODEL => $obj->getModel(),
						ObjectFinder::SEARCH_FOREIGN => true
					]
				);
				if (is_null($statck)) {
					throw new ComhonException('value should not be null');
				}
				// for the moment InterfaceException manage only one error
				// so we throw exception at the first loop
				throw InterfaceException::getInstanceWithProperties(
					new NotReferencedValueException($obj),
					array_reverse($statck)
				);
			}
		}
	}
	
	/**
	 * get inherited model name from interfaced object
	 *
	 * @param mixed $interfacedObject
	 * @param Interfacer $interfacer
	 * @param bool $isFirstLevel
	 * @return string|null
	 */
	protected function _getInheritedModelName($interfacedObject, Interfacer $interfacer, $isFirstLevel) {
		throw new ComhonException('cannot call _getInheritedModelName on '.get_class($this));
	}
	
	/**
	 * get inherited model
	 *
	 * @param string $inheritanceModelName
	 * @return Model;
	 */
	protected function _getInheritedModel($inheritanceModelName) {
		throw new ComhonException('cannot call _getInheritedModel on '.get_class($this));
	}
	
	/**
	 * export comhon object in specified format
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $forceIsolateElements this parameter is only use if exported object is a comhon array.
	 *                force isolate each elements of comhon array 
	 *                (isolated element doesn't share objects instances with others elements).
	 * @return mixed
	 */
	public function export(AbstractComhonObject $object, Interfacer $interfacer, $forceIsolateElements = true) {
		try {
			$this->verifValue($object);
			self::$instanceObjectHash = [];
			$objectCollectionInterfacer = new ObjectCollectionInterfacer();
			$nullNodes = $interfacer instanceof XMLInterfacer ? [] : null;
			$isolate = $forceIsolateElements && $object instanceof ComhonArray;
			$node = $this->_export($object, 'root', $interfacer, true, $objectCollectionInterfacer, $nullNodes, $isolate);
			if ($interfacer->hasToVerifyReferences() && !($this instanceof ModelForeign)) {
				$this->_verifyReferences($object, $objectCollectionInterfacer);
			}
			if (!empty($nullNodes)) { // if not empty, interfacer must be xml interfacer
				$this->_processNullNodes($interfacer, $node, $nullNodes);
			}
			self::$instanceObjectHash = [];
		} catch (ComhonException $e) {
			throw new ExportException($e);
		}
		return $node;
	}
	
	/**
	 * add null namespace on given root element and flag given nodes as null
	 * 
	 * @param XMLInterfacer $interfacer
	 * @param \DOMElement $rootNode
	 * @param \DOMElement[] $nullNodes
	 */
	private function _processNullNodes(XMLInterfacer $interfacer, \DOMElement $rootNode, array $nullNodes) {
		$interfacer->addNullNamespaceURI($rootNode);
		foreach ($nullNodes as $nullNode) {
			$interfacer->setNodeAsNull($nullNode);
		}
	}
	
	/**
	 * export comhon object id(s)
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param string $nodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @param \DOMElement[] $nullNodes nodes that need to be processed at the end of export (only used for xml export).
	 * @throws \Exception
	 * @return mixed|null
	 */
	abstract protected function _exportId(AbstractComhonObject $object, $nodeName, Interfacer $interfacer, ObjectCollectionInterfacer $objectCollectionInterfacer, &$nullNodes);
	
	/**
	 * import interfaced object
	 *
	 * @param mixed $interfacedValue
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject|\Comhon\Object\ComhonArray
	 */
	abstract public function import($interfacedValue, Interfacer $interfacer);
	
	/**
	 * create or get comhon object according interfaced id
	 *
	 * @param mixed $interfacedId
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @return \Comhon\Object\UniqueObject
	 */
	abstract protected function _importId($interfacedId, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer);
	
	/**
	 * import interfaced object
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\AbstractComhonObject $rootObject
	 * @param boolean $isolate determine if root comhon array elements must be isolated.
	 *                         this parameter may by true only if the imported root object is a comhon array
	 *                         and if the parameter $forceIsolateElements is set to true.
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _importRoot($interfacedObject, Interfacer $interfacer, AbstractComhonObject $rootObject = null, $isolate = false) {
		$this->load();
		$mergeType = $interfacer->getMergeType();
		
		if (is_null($rootObject)) {
			$rootObject = $this->_getRootObject($interfacedObject, $interfacer);
		}
		
		$objectCollectionInterfacer = $this->_initObjectCollectionInterfacer($rootObject, $mergeType);
		
		if ($interfacer->getMergeType() == Interfacer::OVERWRITE || $this instanceof ModelArray) {
			$isLoaded = $rootObject->isLoaded();
			$rootObject->reset(false);
			$rootObject->setIsLoaded($isLoaded);
		}
		$this->_fillObject(
			$rootObject,
			$interfacedObject,
			$interfacer,
			true,
			$objectCollectionInterfacer,
			$isolate
		);
		
		if ($interfacer->hasToVerifyReferences()) {
			$this->_verifyReferences($rootObject, $objectCollectionInterfacer);
		}
		
		return $rootObject;
	}
	
	/**
	 * get root object (instanciate or get existing instance)
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return \Comhon\Object\ComhonObject|\Comhon\Object\ComhonArray
	 */
	abstract protected function _getRootObject($interfacedObject, Interfacer $interfacer);
	
	/**
	 *
	 * @param \Comhon\Object\ComhonObject|\Comhon\Object\ComhonArray $objectArray
	 * @param string $mergeType
	 * @return \Comhon\Object\Collection\ObjectCollectionInterfacer
	 */
	abstract protected function _initObjectCollectionInterfacer(AbstractComhonObject $object, $mergeType);
	
	/**
	 * fill comhon object (or comhon aray) with values from interfaced object
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @param boolean $isolate determine if root comhon array elements must be isolated.
	 *                         this parameter may by true only if the imported root object is a comhon array
	 *                         and if the parameter $forceIsolateElements is set to true.
	 * @throws \Comhon\Exception\Interfacer\ImportException
	 * @return \Comhon\Object\ComhonArray
	 */
	abstract protected function _fillObject(
		AbstractComhonObject $object,
		$interfacedObject,
		Interfacer $interfacer,
		$isFirstLevel,
		ObjectCollectionInterfacer $objectCollectionInterfacer,
		$isolate = false
	);
	
	/**
	 * fill comhon object with values from interfaced object
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 */
	abstract public function fillObject(AbstractComhonObject $object, $interfacedObject, Interfacer $interfacer);
	
	/**
	 * get instance of object associated to model
	 *
	 * @param boolean $isloaded define if instanciated object will be flagged as loaded or not
	 * @return \Comhon\Object\UniqueObject|\Comhon\Object\ComhonArray
	 */
	abstract public function getObjectInstance($isloaded = true);
	
}