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

abstract class SimpleModel extends Model {
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	public final function __construct() {
		$this->isLoaded = true;
		$this->_init();
	}
	
	public function isComplex() {
		return false;
	}
	
	public function getObjectClass() {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	public function getObjectInstance($isloaded = true) {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param string $nodeName
	 * @param Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @throws \Exception
	 * @return mixed|null
	 */
	final protected function _export($value, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		return $this->exportSimple($value, $interfacer);
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	public function exportSimple($value, Interfacer $interfacer) {
		return $value;
	}
	
	/**
	 *
	 * @param ComhonDateTime $value
	 * @param Interfacer $interfacer
	 * @param ObjectCollection $localObjectCollection
	 * @param MainModel $parentMainModel
	 * @param boolean $isFirstLevel
	 * @return NULL|unknown
	 */
	final protected function _import($value, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel, $isFirstLevel = false) {
		return $this->importSimple($value, $interfacer);
	}
	
	/**
	 *
	 * @param mixed $value
	 * @param Interfacer $interfacer
	 * @return string|null
	 */
	public function importSimple($value, Interfacer $interfacer) {
		if (is_null($value)) {
			return $value;
		}
		if ($interfacer instanceof NoScalarTypedInterfacer) {
			$value = $interfacer->castValueToString($value);
		}
		return $value;
	}
	
	public function verifValue($value) {
		throw new \Exception('should be overrided');
	}
	

	public abstract function castValue($value);
	
}