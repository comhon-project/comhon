<?php
namespace comhon\request;

use comhon\object\Object;
use comhon\model\Model;

class SimpleLoadRequest extends ObjectLoadRequest {

	public function __construct($pModelName, $pPrivate = false) {
		parent::__construct($pModelName, $pPrivate);
		if (!$this->mPrivate) {
			foreach ($this->mModel->getIdProperties() as $lProperty) {
				if ($lProperty->isPrivate()) {
					throw new \Exception('id is private, cannot retrieve object for public request');
				}
			}
		}
	}
	
	/**
	 * 
	 * @param string|integer $pId
	 */
	public function setRequestedId($pId) {
		$this->mId = $pId;
	}
	
	public function execute() {
		$lObject = $this->mModel->loadObject($this->mId, $this->mPropertiesFilter);
		if (!is_null($lObject)) {
			$this->_updateObjects($lObject);
		}
		return $lObject;
	}
	
	/**
	 *
	 * @param stdClass $pStdObject
	 * @return SimpleLoadRequest
	 */
	public static function buildObjectLoadRequest($pStdObject, $pPrivate = false) {
		if (!isset($pStdObject->model)) {
			throw new \Exception('request doesn\'t have model');
		}
		if (!isset($pStdObject->id)) {
			throw new \Exception('request doesn\'t have id');
		}
		$lRequest = new SimpleLoadRequest($pStdObject->model, $pPrivate);
		$lRequest->setRequestedId($pStdObject->id);
		if (isset($pStdObject->properties) && is_array($pStdObject->properties)) {
			$lRequest->setPropertiesFilter($pStdObject->properties);
		}
		return $lRequest;
	}
}