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
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Object\Collection\ObjectCollectionInterfacer;

abstract class SimpleModel extends AbstractModel implements ModelUnique {
	
	/**
	 * initialize model name
	 */
	abstract protected function _initializeModelName();
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	final public function __construct() {
		$this->_initializeModelName();
	}
	
	/**
	 * get model name
	 *
	 * @return string
	 */
	public function getName() {
		return $this->modelName;
	}
	
	/**
	 * get short name of model (name without namespace)
	 *
	 * @return string
	 */
	public function getShortName() {
		return $this->modelName;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::isLoaded()
	 */
	public function isLoaded() {
		return true;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_isNextLevelFirstLevel()
	 */
	protected function _isNextLevelFirstLevel($isCurrentLevelFirstLevel) {
		return $isCurrentLevelFirstLevel;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\AbstractModel::_export()
	 */
	final protected function _export($value, $nodeName, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer, &$nullNodes, $isolate = false) {
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
	 * @see \Comhon\Model\AbstractModel::_import()
	 */
	final protected function _import($value, Interfacer $interfacer, $isFirstLevel, ObjectCollectionInterfacer $objectCollectionInterfacer, $isolate = false) {
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
	
}