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

use Comhon\Model\Model;

class SimpleLoadRequest extends ObjectLoadRequest {

	/** @var string|integer */
	private $id;
	
	/**
	 * 
	 * @param string $modelName
	 * @param boolean $private
	 * @throws \Exception
	 */
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Request\ObjectLoadRequest::execute()
	 */
	public function execute() {
		$object = $this->model->loadObject($this->id, $this->propertiesFilter);
		if (!is_null($object)) {
			$this->_completeObject($object);
		}
		return $object;
	}
	
	/**
	 * build load request
	 *
	 * @param \stdClass $settings
	 * @param boolean $private
	 * @throws \Exception
	 * @return \Comhon\Request\SimpleLoadRequest
	 */
	public static function buildObjectLoadRequest(\stdClass $settings, $private = false) {
		if (!isset($settings->model)) {
			throw new \Exception('request doesn\'t have model');
		}
		if (!isset($settings->id)) {
			throw new \Exception('request doesn\'t have id');
		}
		$request = new SimpleLoadRequest($settings->model, $private);
		$request->setRequestedId($settings->id);
		if (isset($settings->properties) && is_array($settings->properties)) {
			$request->setPropertiesFilter($settings->properties);
		}
		return $request;
	}
}