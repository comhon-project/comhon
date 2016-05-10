<?php
namespace objectManagerLib\httpapi;

use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\Literal;
use objectManagerLib\object\ComplexLoadRequest;
use objectManagerLib\object\singleton\InstanceModel;

class ObjectService {
	
	public static function getObjects($pParams) {
		try {
			$lPhpObjects    = array();
			$lObjects = ComplexLoadRequest::buildObjectLoadRequest($pParams)->execute();
			return self::_setSuccessReturn($lObjects->toObject(false));
		} catch (\Exception $e) {
			return self::_setErrorReturn($e);
		}
	}
	
	private static function _setSuccessReturn($pReturnValue) {
		$lReturn = new \stdClass();
		$lReturn->success = true;
		$lReturn->result  = $pReturnValue;
		return $lReturn;
	}
	
	private static function _setErrorReturn($pException) {
		$lReturn = new \stdClass();
		$lReturn->success = false;
		$lReturn->error   = $pException->getMessage();
		return $lReturn;
	}
	
}