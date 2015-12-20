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
			foreach ($lObjects as $lObject) {
				$lPhpObjects[] = $lObject->toObject(false, true);
			}
			return self::_setSuccessReturn($lPhpObjects);
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