<?php
namespace objectManagerLib\httpapi;

use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\Literal;
use objectManagerLib\object\ObjectManager;
use objectManagerLib\object\singleton\InstanceModel;

class ObjectService {
	
	public static function getObjects($pParams) {
		if (isset($pParams->logicalJunction) && isset($pParams->literal)) {
			throw new \Exception('can\'t have logicalJunction and literal properties in same time');
		}
		$lPhpObjects    = array();
		$lLogicalObject = null;
		$lObjectManager = new ObjectManager();
		$lModel         = InstanceModel::getInstance()->getInstanceModel($pParams->model);
		
		if (isset($pParams->logicalJunction)) {
			$lLogicalObject = LogicalJunction::phpObjectToLogicalJunction($pParams->logicalJunction, $lModel);
		}
		else if (isset($pParams->literal)) {
			$lLogicalObject = Literal::phpObjectToLiteral($pParams->literal, $lModel);
		}
		
		$lObjects = $lObjectManager->getObjects($pParams->model, $lLogicalObject, 4, null, true, true);
		foreach ($lObjects as $lObject) {
			$lPhpObjects[] = $lObject->toObject(false, true);
		}
		return $lPhpObjects;
	}
	
}