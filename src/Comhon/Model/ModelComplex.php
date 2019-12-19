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
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Object\Collection\ObjectCollectionInterfacer;

abstract class ModelComplex extends AbstractModel {
	
	/**
	 * @var integer[] array used to avoid infinite loop when objects are visited
	 */
	protected static $instanceObjectHash = [];
	
	/**
	 * export comhon object in specified format
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return mixed
	 */
	public function export(AbstractComhonObject $object, Interfacer $interfacer) {
		$this->verifValue($object);
		$interfacer->initializeExport();
		self::$instanceObjectHash = [];
		
		$node = $this->_exportRoot($object, 'root', $interfacer);
		
		self::$instanceObjectHash = [];
		$interfacer->finalizeExport($node);
		return $node;
	}
	
	/**
	 * export comhon object in specified format
	 *
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param string $nodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return mixed
	 */
	protected function _exportRoot(AbstractComhonObject $object, $nodeName, Interfacer $interfacer) {
		throw new ComhonException('cannot call _importRoot on '.get_class($this));
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
}