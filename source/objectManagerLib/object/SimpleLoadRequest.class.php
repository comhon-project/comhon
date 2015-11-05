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
use objectManagerLib\controller\ForeignObjectReplacer;
use objectManagerLib\controller\ForeignObjectLoader;
use objectManagerLib\controller\CompositionLoader;

class SimpleLoadRequest extends LoadObjectRequest {

	public function execute($pId) {
		if (is_null($lSerializationUnit = $this->mModel->getSerialization(0))) {
			throw new \Exception("model doesn't have serialization");
		}
		if (count($lPropertiesIds = $this->mModel->getIds()) != 1) {
			throw new \Exception("model must have one and only one id property");
		}
		$lObject = $this->mModel->getObjectInstance();
		$lObject->setValue($lPropertiesIds[0], $pId);
		$lResult = $lSerializationUnit->loadObject($lObject, $pId);
		if ($lResult) {
			$this->_updateObjects(array($lObject));
			$lResult = $lObject;
		}
		return $lResult;
	}
	
}