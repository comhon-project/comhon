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

use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\Interfacer;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Exception\ComhonException;

abstract class SimpleModel extends Model {
	
	/**
	 * initialize model name
	 */
	abstract protected function _initializeModelName();
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	final public function __construct() {
		$this->isLoaded = true;
		$this->_initializeModelName();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::isComplex()
	 */
	public function isComplex() {
		return false;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::isNextLevelFirstLevel()
	 */
	protected function isNextLevelFirstLevel($isCurrentLevelFirstLevel) {
		return $isCurrentLevelFirstLevel;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getObjectClass()
	 */
	public function getObjectClass() {
		throw new ComhonException('simple models doesn\'t have associated class');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::getObjectInstance()
	 */
	public function getObjectInstance($isloaded = true) {
		throw new ComhonException('simple models doesn\'t have associated class');
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_export()
	 */
	final protected function _export($value, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		return $this->exportSimple($value, $interfacer);
	}
	
	/**
	 * export value in specified format
	 * 
	 * @param mixed $value
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return mixed
	 */
	public function exportSimple($value, Interfacer $interfacer) {
		return $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::_import()
	 * 
	 * @return mixed
	 */
	final protected function _import($value, Interfacer $interfacer, ObjectCollection $localObjectCollection, $isFirstLevel) {
		return $this->importSimple($value, $interfacer, $isFirstLevel);
	}
	
	/**
	 * import interfaced value
	 *
	 * @param mixed $value
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param boolean $applyCast if true and if interfacer setting Interfacer::STRINGIFIED_VALUES is set to true, value will be casted during import
	 * @return string|null
	 */
	public function importSimple($value, Interfacer $interfacer, $applyCast = true) {
		if ($interfacer->isNullValue($value)) {
			return null;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToString($value);
		}
		return $value;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\Model::verifValue()
	 */
	public function verifValue($value) {
		throw new ComhonException('must be overrided');
	}
	
}