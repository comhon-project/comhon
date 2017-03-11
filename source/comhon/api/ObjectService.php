<?php
namespace comhon\api;

use comhon\database\LogicalJunction;
use comhon\database\Literal;
use comhon\request\ComplexLoadRequest;
use comhon\model\singleton\ModelManager;
use comhon\request\SimpleLoadRequest;

class ObjectService {
	
	public static function getObject($pParams) {
		try {
			if (!isset($pParams->id)) {
				throw new \Exception('request doesn\'t have id');
			}
			$lFilterProperties = isset($pParams->properties) ? $pParams->properties : [];
			$lObjectArray = SimpleLoadRequest::buildObjectLoadRequest($pParams)->execute($pParams->id, $lFilterProperties);
			return self::_setSuccessReturn($lObjectArray->toPublicStdObject(null, false, self::_getFilterProperties($pParams, $lObjectArray)));
		} catch (\Exception $e) {
			return self::_setErrorReturn($e);
		}
	}
	
	public static function getObjects($pParams) {
		try {
			$lObjectArray = ComplexLoadRequest::buildObjectLoadRequest($pParams)->execute();
			$lFilterProperties = isset($pParams->properties) ? $pParams->properties : null;
			return self::_setSuccessReturn($lObjectArray->toPublicStdObject(null, false, self::_getFilterProperties($pParams, $lObjectArray)));
		} catch (\Exception $e) {
			return self::_setErrorReturn($e);
		}
	}
	
	private static function _getFilterProperties($pParams, $pOjectArray) {
		if (!isset($pParams->properties) || empty($pParams->properties)) {
			return null;
		}
		$lFilterProperties = $pParams->properties;
		if ($pOjectArray->getModel()->hasIdProperties()) {
			foreach ($pOjectArray->getModel()->getIdProperties() as $lProperty) {
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
		$lReturn->error->trace   = $pException->getTrace();
		return $lReturn;
	}
	
}