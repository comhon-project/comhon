<?php
namespace objectManagerLib\object\object;

use objectManagerLib\database\DatabaseController;
use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\SelectQuery;
use objectManagerLib\database\Literal;
use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\model\ModelForeign;
use objectManagerLib\object\model\ModelArray;

class SqlTable extends SerializationUnit {
	
	private $mDbController;
	
	public function saveObject($pValue, $pModel) {
		if (is_null($this->mDbController)) {
			$this->loadValue("database");
			$this->mDbController = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		return $pModel->toSqlDataBase($pValue, $this->getValue("name"), $lDbController);
	}
	
	public function loadObject($pId, $pModel, $pLoadDepth, $pPropertiesNames = null) {
		if (is_null($this->mDbController)) {
			$this->loadValue("database");
			$this->mDbController = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		if (is_null($pPropertiesNames)) {
			$pPropertiesNames = $pModel->getIds();
		}
		$lLinkedLiteral = new LogicalJunction(LogicalJunction::DISJUNCTION);
		foreach ($pPropertiesNames as $pPropertyName) {
			$lColumn = $pModel->getProperty($pPropertyName)->getSerializationName();
			$lLinkedLiteral->addLiteral(new Literal($this->getValue("name"), $lColumn, "=", $pId));
		}
		$lSelectQuery = new SelectQuery($this->getValue("name"));
		$lSelectQuery->setWhereLogicalJunction($lLinkedLiteral);
		$lResult = $this->mDbController->executeQuery($lSelectQuery);
		
		if (count($lResult) > 0) {
			if (! ($pModel instanceof ModelArray)) {
				$lResult = $lResult[0];
			}
			if ($pModel instanceof ModelForeign) {
				return $pModel->getModel()->fromSqlDataBase($lResult);
			}else {
				return $pModel->fromSqlDataBase($lResult, $pLoadDepth - 1);
			}
		}
	}
	
	public function hasReturnValue() {
		return false;
	}
}