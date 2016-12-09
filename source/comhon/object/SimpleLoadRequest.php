<?php
namespace comhon\object;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\LogicalJunctionOptimizer;
use comhon\database\ComplexLiteral;
use comhon\database\HavingLiteral;
use comhon\database\SelectQuery;
use comhon\object\singleton\InstanceModel;
use comhon\object\object\Object;
use comhon\object\model\Model;
use comhon\object\model\SimpleModel;
use comhon\object\model\ModelContainer;
use comhon\object\model\ForeignProperty;
use comhon\controller\ForeignObjectLoader;
use comhon\controller\CompositionLoader;

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
	 * @param stdClass $pStdObject
	 * @return SimpleLoadRequest
	 */
	public static function buildObjectLoadRequest($pStdObject) {
		if (!isset($pStdObject->model)) {
			throw new \Exception('request doesn\'t have model');
		}
		return new SimpleLoadRequest($pStdObject->model);
	}
}