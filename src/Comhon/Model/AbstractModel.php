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

use Comhon\Interfacer\Interfacer;
use Comhon\Object\Collection\ObjectCollectionInterfacer;

abstract class AbstractModel {
	
	/** @var string */
	protected $modelName;
	
	/** @var boolean */
	protected $isLoaded = false;
	
	/**
	 * verify if model is loaded or not
	 *
	 * @return boolean
	 */
	public function isLoaded() {
		return $this->isLoaded;
	}
	
	/**
	 * load model
	 */
	public function load() {
		// do nothing
	}
	
	/**
	 * verify if model is complex or not
	 * 
	 * model is complex if model is not instance of SimpleModel
	 * 
	 * @return boolean
	 */
	public function isComplex() {
		return $this instanceof ModelComplex;
	}
	
	/**
	 * verify if during import we stay in first level object or not
	 * 
	 * @param boolean $isCurrentLevelFirstLevel
	 * @return boolean
	 */
	abstract protected function _isNextLevelFirstLevel($isCurrentLevelFirstLevel);
	
	/**
	 * export value in specified format according interfacer
	 * 
	 * @param mixed $value
	 * @param string $nodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @param boolean $isolate
	 * @param \DOMElement[] $nullNodes nodes that need to be processed at the end of export (only used for xml export).
	 * @throws \Exception
	 * @return mixed|null
	 */
	abstract protected function _export($value, $nodeName, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer, &$nullNodes, $isolate = false);
	
	/**
	 * import interfaced object
	 * 
	 * @param mixed $interfacedValue
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @param \Comhon\Object\Collection\ObjectCollectionInterfacer $objectCollectionInterfacer
	 * @param boolean $isolate
	 * @return mixed|null
	 */
	abstract protected function _import($interfacedValue, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer, $isolate = false);
	
	/**
	 * verify if value is correct according current model
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	abstract public function verifValue($value);
	
	/**
	 * verify if specified model is equal (same instance) to this model
	 *
	 * @param AbstractModel $model
	 * @return boolean
	 */
	public function isEqual(AbstractModel $model) {
		return $this === $model;
	}
	
}