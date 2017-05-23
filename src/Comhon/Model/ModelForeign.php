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

	public function __construct($pModel) {
		parent::__construct($pModel);
		if ($this->mModel instanceof SimpleModel) {
			throw new Exception('model of foreign model can\'t be a simple model');
		}
	}
	
	public function getObjectClass() {
		return $this->getModel()->getObjectClass();
	}
	
	public function getObjectInstance($pIsloaded = true) {
		return $this->getModel()->getObjectInstance($pIsloaded);
	}
	
	/**
	 *
	 * @param ComhonObject $pObject
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @param boolean $pIsFirstLevel
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _export($pObject, $pNodeName, Interfacer $pInterfacer, $pIsFirstLevel) {
		if (is_null($pObject)) {
			return null;
		}
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new \Exception('foreign property with local model must have id');
		}
		return $this->getModel()->_exportId($pObject, $pNodeName, $pInterfacer);
	}
	
	/**
	 *
	 * @param ComhonDateTime $pValue
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param MainModel $pParentMainModel
	 * @param boolean $pIsFirstLevel
	 * @return NULL|unknown
	 */
	protected function _import($pValue, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection, MainModel $pParentMainModel, $pIsFirstLevel = false) {
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new \Exception("foreign property must have model with id ({$this->getName()})");
		}
		return $this->getModel()->_importId($pValue, $pInterfacer, $pLocalObjectCollection, $pParentMainModel, $pIsFirstLevel);
	}
	
	public function verifValue($pValue) {
		$this->mModel->verifValue($pValue);
		return true;
	}
}