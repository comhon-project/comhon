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

use Comhon\Request\ComplexRequester;
use Comhon\Request\SimpleRequester;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelComhonObject;
use Comhon\Exception\Interfacer\ImportException;

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
			if (!isset($params->properties)) {
				$params->properties = [];
			}
			$object = SimpleRequester::build($params->model, $params->id, $params->properties, $private)->execute();
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
			$objectArray = ComplexRequester::build($params, $private)->execute();
			$interfacer = new StdObjectInterfacer();
			$modelFilter = [$objectArray->getUniqueModel()->getName() => self::_getFilterProperties($params, $objectArray->getUniqueModel())];
			return self::_setSuccessResponse($interfacer->export($objectArray, [Interfacer::PROPERTIES_FILTERS => $modelFilter]));
		} catch (ImportException $e) {
			var_dump($e->getStringifiedProperties());
			return self::_setErrorResponse($e);
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
		return ComplexRequester::build($params, $private)->count();
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
			return [];
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
	 * @param mixed $returnedValue
	 * @return \stdClass
	 */
	private static function _setSuccessResponse($returnedValue) {
		$res = new \stdClass();
		$res->success = true;
		$res->result  = $returnedValue;
		return $res;
	}
	
	/**
	 * build error response
	 * 
	 * @param \Exception $exception
	 * @return \stdClass
	 */
	private static function _setErrorResponse(\Exception $exception) {
		$res = new \stdClass();
		$res->success        = false;
		$res->error          = new \stdClass();
		$res->error->message = $exception->getMessage();
		$res->error->code    = $exception->getCode();
		return $res;
	}
	
}