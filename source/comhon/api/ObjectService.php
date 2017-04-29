<?php
namespace comhon\api;

use comhon\request\ComplexLoadRequest;
use comhon\request\SimpleLoadRequest;
use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\Interfacer;

class ObjectService {
	
	public static function getObject($pParams, $pPrivate = false) {
		try {
			if (!isset($pParams->id)) {
				throw new \Exception('request doesn\'t have id');
			}
			$lObject = SimpleLoadRequest::buildObjectLoadRequest($pParams, $pPrivate)->execute();
			$lInterfacer = new StdObjectInterfacer();
			$lModelFilter = [$lObject->getModel()->getName() => self::_getFilterProperties($pParams, $lObject)];
			return self::_setSuccessReturn($lInterfacer->export($lObject, [Interfacer::PROPERTIES_FILTERS => $lModelFilter]));
		} catch (\Exception $e) {
			return self::_setErrorReturn($e);
		}
	}
	
	public static function getObjects($pParams, $pPrivate = false) {
		try {
			$lObjectArray = ComplexLoadRequest::buildObjectLoadRequest($pParams, $pPrivate)->execute();
			$lInterfacer = new StdObjectInterfacer();
			$lModelFilter = [$lObjectArray->getModel()->getName() => self::_getFilterProperties($pParams, $lObjectArray)];
			return self::_setSuccessReturn($lInterfacer->export($lObjectArray, [Interfacer::PROPERTIES_FILTERS => $lModelFilter]));
		} catch (\Exception $e) {
			return self::_setErrorReturn($e);
		}
	}
	
	private static function _getFilterProperties($pParams, $pOject) {
		if (!isset($pParams->properties) || empty($pParams->properties)) {
			return null;
		}
		$lFilterProperties = $pParams->properties;
		if ($pOject->getModel()->hasIdProperties()) {
			foreach ($pOject->getModel()->getIdProperties() as $lProperty) {
				$lFilterProperties[] = $lProperty->getName();
			}
		}
		return array_unique($lFilterProperties);
	}
	
	private static function _setSuccessReturn($pReturnValue) {
		$lReturn = new \stdClass();
		$lReturn->success = true;
		$lReturn->result  = $pReturnValue;
		return $lReturn;
	}
	
	private static function _setErrorReturn(\Exception $pException) {
		$lReturn = new \stdClass();
		$lReturn->success        = false;
		$lReturn->error          = new \stdClass();
		$lReturn->error->message = $pException->getMessage();
		return $lReturn;
	}
	
}