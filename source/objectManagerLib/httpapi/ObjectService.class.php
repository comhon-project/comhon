<?php
namespace objectManagerLib\httpapi;

use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\Literal;
use objectManagerLib\object\ComplexLoadRequest;
use objectManagerLib\object\singleton\InstanceModel;

class ObjectService {
	
	public static function getObjects($pParams) {
		if (isset($pParams->model)) {
			return self::_execSimpleRequestObjects($pParams);
		}
		if (isset($pParams->tree) && (isset($pParams->logicalJunction) || isset($pParams->literal))) {
			return self::_execIntermediateRequestObjects($pParams);
		}
		if (isset($pParams->tree)) {
			return self::_execAdvancedRequestObjects($pParams);
		}
	}
	
	private static function _execSimpleRequestObjects($pParams) {
		if (isset($pParams->logicalJunction) && isset($pParams->literal)) {
			throw new \Exception('can\'t have logicalJunction and literal properties in same time');
		}
		$lPhpObjects    = array();
		$lLogicalObject = null;
		$lObjectRequest = new ComplexLoadRequest($pParams->model);
		$lModel         = InstanceModel::getInstance()->getInstanceModel($pParams->model);
		
		if (isset($pParams->logicalJunction)) {
			$lLogicalObject = LogicalJunction::phpObjectToLogicalJunction($pParams->logicalJunction, $lModel);
		}
		else if (isset($pParams->literal)) {
			$lLogicalObject = Literal::phpObjectToLiteral($pParams->literal, $lModel);
		}
		$lObjects = $lObjectRequest->getChildren(true)->loadForeignProperty(true)->execute($lLogicalObject);
		foreach ($lObjects as $lObject) {
			$lPhpObjects[] = $lObject->toObject(false, true);
		}
		return $lPhpObjects;
	}
	
	private static function _execIntermediateRequestObjects($pParams) {
		
	}
	
	private static function _execAdvancedRequestObjects($pParams) {
		if (isset($pParams->logicalJunction) && isset($pParams->literal)) {
			throw new \Exception('can\'t have logicalJunction and literal properties in same time');
		}
	}
}