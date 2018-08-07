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

use Comhon\Object\ComhonObject;
use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Interfacer\ExportException;

abstract class ModelComplex extends AbstractModel {
	
	/**
	 * @var integer[] array used to avoid infinite loop when objects are visited
	 */
	protected static $instanceObjectHash = [];
	
	/**
	 * add main current object(s) to main foreign objects list in interfacer
	 *
	 * object is added only if it has a main model associated
	 * avoid to re-export current object via export of main foreign object
	 *
	 * @param \Comhon\Object\ComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	abstract protected function _addMainCurrentObject(ComhonObject $objectArray, Interfacer $interfacer);
	
	/**
	 * remove main current object(s) from main foreign objects list in interfacer previously added
	 *
	 * @param \Comhon\Object\ComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 */
	abstract protected function _removeMainCurrentObject(ComhonObject $objectArray, Interfacer $interfacer);
	
	/**
	 * export comhon object in specified format
	 *
	 * @param \Comhon\Object\ComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return mixed
	 */
	public function export(ComhonObject $object, Interfacer $interfacer) {
		$interfacer->initializeExport();
		self::$instanceObjectHash = [];
		$this->_addMainCurrentObject($object, $interfacer);
		try {
			$node = $this->_export($object, 'root', $interfacer, true);
		} catch (ComhonException $e) {
			throw new ExportException($e);
		}
		$this->_removeMainCurrentObject($object, $interfacer);
		self::$instanceObjectHash = [];
		$interfacer->finalizeExport($node);
		return $node;
	}
	
	/**
	 * export comhon object id(s)
	 *
	 * @param \Comhon\Object\ComhonObject $object
	 * @param string $nodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	abstract protected function _exportId(ComhonObject $object, $nodeName, Interfacer $interfacer);
	
	/**
	 * import interfaced comhon object
	 *
	 * @param mixed $interfacedValue
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique|\Comhon\Object\ObjectArray
	 */
	abstract public function import($interfacedValue, Interfacer $interfacer);
	
	/**
	 * create or get comhon object according interfaced id
	 *
	 * @param mixed $interfacedId
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @param boolean $isFirstLevel
	 * @return \Comhon\Object\ObjectUnique
	 */
	abstract protected function _importId($interfacedId, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel);
	
	/**
	 * import interfaced object related to a main model
	 *
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param \Comhon\Object\Collection\ObjectCollection $localObjectCollection
	 * @throws \Exception
	 * @return \Comhon\Object\ObjectUnique
	 */
	protected function _importRoot($interfacedObject, Interfacer $interfacer, ObjectCollection $localObjectCollection) {
		throw new ComhonException('can call _importRoot only via Model');
	}
	
	/**
	 * fill comhon object with values from interfaced object
	 *
	 * @param \Comhon\Object\ComhonObject $object
	 * @param mixed $interfacedObject
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 */
	abstract public function fillObject(ComhonObject $object, $interfacedObject, Interfacer $interfacer);
}