<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Test\Comhon\Service;

use Comhon\Request\ComplexLoadRequest;
use Comhon\Request\SimpleLoadRequest;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelComhonObject;

class ObjectService {
	
	/**
	 * retrieve object (if exists) according specified model name and id
	 * 
	 * @param \stdClass $params
	 * @param boolean $private
	 * @return \stdClass
	 */
	public static function getObject(\stdClass $params, $private = false) {
		try {
			$object = SimpleLoadRequest::buildObjectLoadRequest($params, $private)->execute();
			if (is_null($object)) {
				$result = null;
			} else {
				$model  = ModelManager::getInstance()->getInstanceModel($params->model);
				$interfacer = new StdObjectInterfacer();
				$interfacer->setPropertiesFilter(self::_getFilterProperties($params, $object->getModel()), $object->getModel()->getName());
				$result = $model->export($object, $interfacer);
			}
			return self::_setSuccessResponse($result);
		} catch (\Exception $e) {
			return self::_setErrorResponse($e);
		}
	}
	
	/**
	 * retrieve requested objects
	 * 
	 * @param \stdClass $params
	 * @param boolean $private
	 * @return \stdClass
	 */
	public static function getObjects(\stdClass $params, $private = false) {
		try {
			$objectArray = ComplexLoadRequest::buildObjectLoadRequest($params, $private)->execute();
			$interfacer = new StdObjectInterfacer();
			$modelFilter = [$objectArray->getUniqueModel()->getName() => self::_getFilterProperties($params, $objectArray->getUniqueModel())];
			return self::_setSuccessResponse($interfacer->export($objectArray, [Interfacer::PROPERTIES_FILTERS => $modelFilter]));
		} catch (\Exception $e) {
			return self::_setErrorResponse($e);
		}
	}
	
	/**
	 * retrieve requested objects count
	 *
	 * @param \stdClass $params
	 * @param boolean $private
	 * @return \stdClass
	 */
	public static function getObjectsCount(\stdClass $params, $private = false) {
		return ComplexLoadRequest::buildObjectLoadRequest($params, $private)->count();
	}
	
	/**
	 * get filter to apply on exported properties
	 * 
	 * @param \stdClass $params
	 * @param \Comhon\Model\ModelComhonObject $model
	 * @return array|null
	 */
	private static function _getFilterProperties(\stdClass $params, ModelComhonObject $model) {
		if (!isset($params->properties) || empty($params->properties)) {
			return null;
		}
		$filterProperties = $params->properties;
		if ($model->hasIdProperties()) {
			foreach ($model->getIdProperties() as $property) {
				$filterProperties[] = $property->getName();
			}
		}
		return array_unique($filterProperties);
	}
	
	/**
	 * build successfull response
	 * 
	 * @param mixed $returnValue
	 * @return \stdClass
	 */
	private static function _setSuccessResponse($returnValue) {
		$return = new \stdClass();
		$return->success = true;
		$return->result  = $returnValue;
		return $return;
	}
	
	/**
	 * build error response
	 * 
	 * @param \Exception $exception
	 * @return \stdClass
	 */
	private static function _setErrorResponse(\Exception $exception) {
		$return = new \stdClass();
		$return->success        = false;
		$return->error          = new \stdClass();
		$return->error->message = $exception->getMessage();
		$return->error->code    = $exception->getCode();
		return $return;
	}
	
}