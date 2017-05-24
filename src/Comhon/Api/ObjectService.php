<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Api;

use Comhon\Request\ComplexLoadRequest;
use Comhon\Request\SimpleLoadRequest;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\Interfacer;
use Comhon\Model\Singleton\ModelManager;

class ObjectService {
	
	public static function getObject($params, $private = false) {
		try {
			if (!isset($params->id)) {
				throw new \Exception('request doesn\'t have id');
			}
			$object = SimpleLoadRequest::buildObjectLoadRequest($params, $private)->execute();
			$model  = ModelManager::getInstance()->getInstanceModel($params->model);
			$interfacer = new StdObjectInterfacer();
			$interfacer->setPropertiesFilter(self::_getFilterProperties($params, $object), $object->getModel()->getName());
			return self::_setSuccessReturn($model->export($object, $interfacer));
		} catch (\Exception $e) {
			return self::_setErrorReturn($e);
		}
	}
	
	public static function getObjects($params, $private = false) {
		try {
			$objectArray = ComplexLoadRequest::buildObjectLoadRequest($params, $private)->execute();
			$interfacer = new StdObjectInterfacer();
			$modelFilter = [$objectArray->getModel()->getName() => self::_getFilterProperties($params, $objectArray)];
			return self::_setSuccessReturn($interfacer->export($objectArray, [Interfacer::PROPERTIES_FILTERS => $modelFilter]));
		} catch (\Exception $e) {
			return self::_setErrorReturn($e);
		}
	}
	
	private static function _getFilterProperties($params, $oject) {
		if (!isset($params->properties) || empty($params->properties)) {
			return null;
		}
		$filterProperties = $params->properties;
		if ($oject->getModel()->hasIdProperties()) {
			foreach ($oject->getModel()->getIdProperties() as $property) {
				$filterProperties[] = $property->getName();
			}
		}
		return array_unique($filterProperties);
	}
	
	private static function _setSuccessReturn($returnValue) {
		$return = new \stdClass();
		$return->success = true;
		$return->result  = $returnValue;
		return $return;
	}
	
	private static function _setErrorReturn(\Exception $exception) {
		$return = new \stdClass();
		$return->success        = false;
		$return->error          = new \stdClass();
		$return->error->message = $exception->getMessage();
		return $return;
	}
	
}