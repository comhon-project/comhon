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

class ModelForeign extends ModelContainer {

	/**
	 * 
	 * @param Model $model
	 * @throws Exception
	 */
	public function __construct(Model $model) {
		parent::__construct($model);
		if ($this->model instanceof SimpleModel) {
			throw new ComhonException('ModelForeign can\'t contain SimpleModel');
		}
	}
	
	/**
	 * get full qualified class name of object associated to contained model
	 * 
	 * @return string
	 */
	public function getObjectClass() {
		return $this->getModel()->getObjectClass();
	}
	
	/**
	 * get instance of object associated to contained model
	 * 
	 * @param boolean $isloaded define if instanciated object will be flaged as loaded or not
	 * @return \Comhon\Object\ComhonObject
	 */
	public function getObjectInstance($isloaded = true) {
		return $this->getModel()->getObjectInstance($isloaded);
	}
	
	/**
	 * export comhon object to interfaced id in specified format
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::_export()
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		if (is_null($object)) {
			return null;
		}
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new ComhonException("foreign property must have model with id, actual model '{$this->getUniqueModel()->getName()}' doesn't");
		}
		return $this->getModel()->_exportId($object, $nodeName, $interfacer);
	}
	
	/**
	 * import interfaced id
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Model\ModelContainer::_import()
	 */
	protected function _import($value, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $mainModelContainer, $isFirstLevel = false) {
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new ComhonException("foreign property must have model with id, actual model '{$this->getUniqueModel()->getName()}' doesn't");
		}
		return $this->getModel()->_importId($value, $interfacer, $localObjectCollection, $mainModelContainer, $isFirstLevel);
	}
	
	/**
	 * verify if value is correct according contained model
	 * 
	 * @param mixed $value
	 * @return boolean
	 */
	public function verifValue($value) {
		$this->model->verifValue($value);
		return true;
	}
}