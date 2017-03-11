<?php
namespace comhon\request;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\LogicalJunctionOptimizer;
use comhon\database\ComplexLiteral;
use comhon\database\HavingLiteral;
use comhon\database\SelectQuery;
use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\model\Model;
use comhon\model\SimpleModel;
use comhon\model\ModelContainer;
use comhon\model\property\ForeignProperty;
use comhon\controller\ForeignObjectLoader;
use comhon\controller\AggregationLoader;

class SimpleLoadRequest extends ObjectLoadRequest {

	public function execute($pId, $pPropertiesFilter = []) {
		$lObject = $this->mModel->loadObject($pId, $pPropertiesFilter);
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