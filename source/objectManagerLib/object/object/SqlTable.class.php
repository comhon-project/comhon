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
	
	protected function _saveObject($pObject) {
		if (!array_key_exists($this->getValue("database")->getValue("id"), self::$sDbObjectById)) {
			$this->loadValue("database");
			self::$sDbObjectById[$this->getValue("database")->getValue("id")] = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		$lMapOfString = $pObject->toSqlDataBase();
		$lQuery = "INSERT INTO ".$this->getValue("name")." (".implode(", ", array_keys($lMapOfString)).") VALUES (".implode(", ", array_fill(0, count($lMapOfString), '?')).");";
		trigger_error(var_export($lQuery, true));
		
		self::$sDbObjectById[$this->getValue("database")->getValue("id")]->prepareQuery($lQuery, array_values($lMapOfString));
		return self::$sDbObjectById[$this->getValue("database")->getValue("id")]->doQuery($lQuery);
	}
	
	protected function _loadObject($pObject) {
		$lWhereColumns = [];
		$lModel = $pObject->getModel();
		foreach ($lModel->getIdProperties() as $lPropertyName) {
			$lWhereColumns[$lModel->getProperty($lPropertyName)->getSerializationName()] = $pObject->getValue($lPropertyName);
		}
		$lReturn = $this->_loadObjectFromDatabase($pObject, array(), $lWhereColumns, LogicalJunction::CONJUNCTION);
		return $lReturn;
	}
	
	public function loadComposition(ObjectArray $pObject, $pParentId, $pCompositionProperties, $pOnlyIds) {
		$lReturn        = false;
		$lModel         = $pObject->getModel()->getUniqueModel();
		$lWhereColumns  = $this->getCompositionColumns($lModel, $pCompositionProperties);
		$lSelectColumns = array();
		$lWhereValues   = array();
		$lIdProperties  = $lModel->getIdProperties();
		
		if (empty($lWhereColumns)) {
			throw new \Exception('error : property is not serialized in database composition');
		}
		foreach ($lWhereColumns as $lColumn) {
			$lWhereValues[$lColumn] = $pParentId;
		}
		if ($pOnlyIds) {
			if (empty($lIdProperties)) {
				trigger_error("Warning! model '{$lModel->getModelName()}' doesn't have a unique property id. All model is loaded");
			}
			foreach ($lIdProperties as $lIdProperty) {
				$lSelectColumns[] = $lModel->getProperty($lIdProperty)->getSerializationName();
			}
		}
		$lReturn = $this->_loadObjectFromDatabase($pObject, $lSelectColumns, $lWhereValues, LogicalJunction::DISJUNCTION);
		return $lReturn;
	}
	
	private function _loadObjectFromDatabase($pObject, $pSelectColumns, $pWhereColumns, $lLogicalJunctionType) {
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
			if (empty($pSelectColumns)) {
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
	
}