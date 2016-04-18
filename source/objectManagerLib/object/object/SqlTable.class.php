<?php
namespace objectManagerLib\object\object;

use objectManagerLib\database\DatabaseController;
use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\SelectQuery;
use objectManagerLib\database\Literal;
use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\model\ModelForeign;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\object\ObjectArray;

class SqlTable extends SerializationUnit {
	
	private static $sDbObjectById = array();
	
	public function saveObject($pValue, $pModel) {
		if (!array_key_exists($this->getValue("database")->getValue("id"), self::$sDbObjectById)) {
			$this->loadValue("database");
			self::$sDbObjectById[$this->getValue("database")->getValue("id")] = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		return $pModel->toSqlDataBase($pValue, $this->getValue("name"), self::$sDbObjectById[$this->getValue("database")->getValue("id")]);
	}
	
	public function loadObject($pObject) {
		$lWhereColumns = [];
		$lModel = $pObject->getModel();
		foreach ($lModel->getIdProperties() as $lPropertyName) {
			$lWhereColumns[$lModel->getProperty($lPropertyName)->getSerializationName()] = $pObject->getValue($lPropertyName);
		}
		$lReturn = $this->_loadObject($pObject, array(), $lWhereColumns, LogicalJunction::CONJUNCTION);
		return $lReturn;
	}
	
	public function loadComposition(ObjectArray $pObject, $pParentId, $pCompositionProperties, $pOnlyIds) {
		$lReturn        = false;
		$lModel         = $pObject->getModel()->getUniqueModel();
		$lWhereColumns  = $this->getCompositionColumns($lModel, $pCompositionProperties);
		$lSelectColumns = array();
		$lWhereValues   = array();
		$lIdProperties  = $lModel->getIdProperties();
		
		if (count($lWhereColumns) == 0) {
			throw new \Exception('error : property is not serialized in database composition');
		}
		foreach ($lWhereColumns as $lColumn) {
			$lWhereValues[$lColumn] = $pParentId;
		}
		if ($pOnlyIds) {
			if (count($lIdProperties) == 0) {
				trigger_error("Warning! model '{$lModel->getModelName()}' doesn't have a unique property id. All model is loaded");
			}
			foreach ($lIdProperties as $lIdProperty) {
				$lSelectColumns[] = $lModel->getProperty($lIdProperty)->getSerializationName();
			}
		}
		$lReturn = $this->_loadObject($pObject, $lSelectColumns, $lWhereValues, LogicalJunction::DISJUNCTION);
		return $lReturn;
	}
	
	private function _loadObject($pObject, $pSelectColumns, $pWhereColumns, $lLogicalJunctionType) {
		$lSuccess = false;
		if (!array_key_exists($this->getValue("database")->getValue("id"), self::$sDbObjectById)) {
			$this->loadValue("database");
			self::$sDbObjectById[$this->getValue("database")->getValue("id")] = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		$lLinkedLiteral = new LogicalJunction($lLogicalJunctionType);
		foreach ($pWhereColumns as $lColumn => $lValue) {
			$lLinkedLiteral->addLiteral(new Literal($this->getValue("name"), $lColumn, "=", $lValue));
		}
		$lSelectQuery = new SelectQuery($this->getValue("name"));
		$lSelectQuery->setWhereLogicalJunction($lLinkedLiteral);
		foreach ($pSelectColumns as $lColumn) {
			$lSelectQuery->addSelectColumn($lColumn);
		}
		$lResult = self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeQuery($lSelectQuery);
	
		$lIsModelArray = $pObject->getModel() instanceof ModelArray;
		if (is_array($lResult) && ($lIsModelArray || (count($lResult) == 1))) {
			if (!$lIsModelArray) {
				$lResult = $lResult[0];
			}
			if (count($pSelectColumns) == 0) {
				$pObject->fromSqlDataBase($lResult);
			} else {
				$pObject->fromSqlDataBaseId($lResult);
			}
			$lSuccess = true;
		}
		return $lSuccess;
	}
	
	public function getCompositionColumns($pModel, $pCompositionProperties) {
		$lColumns = array();
		foreach ($pCompositionProperties as $lCompositionProperty) {
			$lColumns[] = $pModel->getProperty($lCompositionProperty, true)->getSerializationName();
		}
		return $lColumns;
	}
	
	public function hasReturnValue() {
		return false;
	}
}