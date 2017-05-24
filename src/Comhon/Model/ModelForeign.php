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

class ModelForeign extends ModelContainer {

	public function __construct($model) {
		parent::__construct($model);
		if ($this->model instanceof SimpleModel) {
			throw new Exception('model of foreign model can\'t be a simple model');
		}
	}
	
	public function getObjectClass() {
		return $this->getModel()->getObjectClass();
	}
	
	public function getObjectInstance($isloaded = true) {
		return $this->getModel()->getObjectInstance($isloaded);
	}
	
	/**
	 *
	 * @param ComhonObject $object
	 * @param string $nodeName
	 * @param Interfacer $interfacer
	 * @param boolean $isFirstLevel
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _export($object, $nodeName, Interfacer $interfacer, $isFirstLevel) {
		if (is_null($object)) {
			return null;
		}
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new \Exception('foreign property with local model must have id');
		}
		return $this->getModel()->_exportId($object, $nodeName, $interfacer);
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
	protected function _import($value, Interfacer $interfacer, ObjectCollection $localObjectCollection, MainModel $parentMainModel, $isFirstLevel = false) {
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new \Exception("foreign property must have model with id ({$this->getName()})");
		}
		return $this->getModel()->_importId($value, $interfacer, $localObjectCollection, $parentMainModel, $isFirstLevel);
	}
	
	public function verifValue($value) {
		$this->model->verifValue($value);
		return true;
	}
}