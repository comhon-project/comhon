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
	 *                force isolate each elements of imported array 
	 *                (isolated element doesn't share objects instances with others elements).
	 * @return mixed
	 */
	public function export(AbstractComhonObject $object, Interfacer $interfacer, $forceIsolateElements = true) {
		try {
			$this->verifValue($object);
			self::$instanceObjectHash = [];
			$objectCollectionInterfacer = new ObjectCollectionInterfacer();
			$isolate = $forceIsolateElements && $object instanceof ComhonArray;
			$node = $this->_export($object, 'root', $interfacer, true, $objectCollectionInterfacer, $isolate);
			if ($interfacer->hasToVerifyReferences()) {
				$this->_verifyReferences($object, $objectCollectionInterfacer);
			}
			self::$instanceObjectHash = [];
		} catch (ComhonException $e) {
			throw new ExportException($e);
		}
		return $node;
	}
	
	/**
	 * export comhon object id(s)
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param string $nodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	abstract protected function _exportId(AbstractComhonObject $object, $nodeName, Interfacer $interfacer, ObjectCollectionInterfacer $objectCollectionInterfacer);
	
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
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @throws \Exception
	 * @return \Comhon\Object\UniqueObject
	 */
	protected function _importRoot($interfacedObject, Interfacer $interfacer, ObjectCollectionInterfacer $objectCollectionInterfacer = null) {
		throw new ComhonException('can call _importRoot only via Model');
	}
	
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