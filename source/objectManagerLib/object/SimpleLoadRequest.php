<?php
namespace objectManagerLib\object;

use objectManagerLib\database\DatabaseController;
use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\LogicalJunctionOptimizer;
use objectManagerLib\database\ComplexLiteral;
use objectManagerLib\database\HavingLiteral;
use objectManagerLib\database\SelectQuery;
use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\ModelContainer;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\controller\ForeignObjectLoader;
use objectManagerLib\controller\CompositionLoader;

class SimpleLoadRequest extends ObjectLoadRequest {

	public function execute($pId) {
		$lObject = $this->mModel->loadObject($pId);
		if (!is_null($lObject)) {
			$this->_updateObjects($lObject);
		}
		return $lObject;
	}
	
	/**
	 *
	 * @param stdClass $pPhpObject
	 * @return SimpleLoadRequest
	 */
	public static function buildObjectLoadRequest($pPhpObject) {
		if (!isset($pPhpObject->model)) {
			throw new \Exception('request doesn\'t have model');
		}
		return new SimpleLoadRequest($pPhpObject->model);
	}
}