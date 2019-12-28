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

use Comhon\Exception\Request\MalformedRequestException;
use Comhon\Exception\Request\NotAllowedRequestException;

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
		if (!$this->model->hasIdProperties()) {
			throw new NotAllowedRequestException($this->model, [NotAllowedRequestException::SIMPLE_REQUEST]);
		}
		if (!$this->private) {
			foreach ($this->model->getIdProperties() as $property) {
				if ($property->isPrivate()) {
					throw new MalformedRequestException("id of model '$modelName' is private, cannot retrieve object for public request");
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
		return $this->model->loadObject($this->id, $this->propertiesFilter, true);
	}
	
	/**
	 * build load request
	 * 
	 * @param string $modelName
	 * @param mixed $id
	 * @param string[] $propertiesFilter
	 * @param boolean $private
	 * @return \Comhon\Request\SimpleLoadRequest
	 */
	public static function build($modelName, $id, $propertiesFilter = [], $private = false) {
		$request = new SimpleLoadRequest($modelName, $private);
		$request->setRequestedId($id);
		if (is_array($propertiesFilter)) {
			$request->setPropertiesFilter($propertiesFilter);
		}
		return $request;
	}
}