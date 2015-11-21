<?php
namespace objectManagerLib\httpapi;

use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\Literal;
use objectManagerLib\object\ComplexLoadRequest;
use objectManagerLib\object\singleton\InstanceModel;

class ObjectService {
	
	public static function getObjects($pParams) {
		$lPhpObjects    = array();
		$lObjects = ComplexLoadRequest::buildObjectLoadRequest($pParams)->execute();
		foreach ($lObjects as $lObject) {
			$lPhpObjects[] = $lObject->toObject(false, true);
		}
		return $lPhpObjects;
	}
}