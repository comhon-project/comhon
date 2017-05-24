<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Request;

use Comhon\Object\ComhonObject;
use Comhon\Model\Model;

class SimpleLoadRequest extends ObjectLoadRequest {

	private $id;
	
	public function __construct($modelName, $private = false) {
		parent::__construct($modelName, $private);
		if (!$this->private) {
			foreach ($this->model->getIdProperties() as $property) {
				if ($property->isPrivate()) {
					throw new \Exception('id is private, cannot retrieve object for public request');
				}
			}
		}
	}
	
	/**
	 * 
	 * @param string|integer $id
	 */
	public function setRequestedId($id) {
		$this->id = $id;
	}
	
	/**
	 * execute resquest and return resulting object
	 * @return ComhonObject
	 */
	public function execute() {
		$object = $this->model->loadObject($this->id, $this->propertiesFilter);
		if (!is_null($object)) {
			$this->_updateObjects($object);
		}
		return $object;
	}
	
	/**
	 *
	 * @param stdClass $stdObject
	 * @return SimpleLoadRequest
	 */
	public static function buildObjectLoadRequest($stdObject, $private = false) {
		if (!isset($stdObject->model)) {
			throw new \Exception('request doesn\'t have model');
		}
		if (!isset($stdObject->id)) {
			throw new \Exception('request doesn\'t have id');
		}
		$request = new SimpleLoadRequest($stdObject->model, $private);
		$request->setRequestedId($stdObject->id);
		if (isset($stdObject->properties) && is_array($stdObject->properties)) {
			$request->setPropertiesFilter($stdObject->properties);
		}
		return $request;
	}
}