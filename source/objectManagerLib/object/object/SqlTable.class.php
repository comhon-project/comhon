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
	
	private static $sDbObjectById = array();
	
	public function saveObject($pValue, $pModel) {
		if (!array_key_exists($this->getValue("database")->getValue("id"), self::$sDbObjectById)) {
			$this->loadValue("database");
			self::$sDbObjectById[$this->getValue("database")->getValue("id")] = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		return $pModel->toSqlDataBase($pValue, $this->getValue("name"), self::$sDbObjectById[$this->getValue("database")->getValue("id")]);
	}
	
	public function loadObject($pObject, $pId, $pModel, $pColumn = null, $pParentModel = null) {
		$lReturn = false;
		if (!array_key_exists($this->getValue("database")->getValue("id"), self::$sDbObjectById)) {
			$this->loadValue("database");
			self::$sDbObjectById[$this->getValue("database")->getValue("id")] = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		$lLinkedLiteral = new LogicalJunction(LogicalJunction::DISJUNCTION);
		foreach ($this->_getColumns($pObject->getModel(), $pColumn, $pParentModel) as $lColumn) {
			$lLinkedLiteral->addLiteral(new Literal($this->getValue("name"), $lColumn, "=", $pId));
		}
		$lSelectQuery = new SelectQuery($this->getValue("name"));
		$lSelectQuery->setWhereLogicalJunction($lLinkedLiteral);
		$lResult = self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeQuery($lSelectQuery);
		
		if (is_array($lResult)) {
			if ($pObject->getModel() instanceof ModelArray) {
				$pObject->fromSqlDataBase($lResult);
				$lReturn = true;
			}
			else if (count($lResult) > 0) {
				$pObject->fromSqlDataBase($lResult[0]);
				$lReturn = true;
			}
		}
		return $lReturn;
	}
	
	private function _getColumns($pModel, $pColumn, $pParentModel) {
		$lColumns = array();
		if (!is_null($pParentModel) && $this->isComposition($pParentModel, $pColumn)) {
			foreach ($this->getValue("compositions")->getValues() as $lComposition) {
				if ($lComposition->getValue("parent") == $pParentModel->getModelName()) {
					$lColumns[] = $lComposition->getValue("column");
				}
			}
		} 
		if (count($lColumns) == 0) {
			foreach ($pModel->getIds() as $pPropertyName) {
				$lColumns[] = $pModel->getProperty($pPropertyName)->getSerializationName();
			}
		}
		return $lColumns;
	}
	
	public function isComposition($pParentModel, $pColumn) {
		$lIsComposition = false;
		if ($this->hasValue("compositions")) {
			foreach ($this->getValue("compositions")->getValues() as $lComposition) {
				if ($lComposition->getValue("parent") == $pParentModel->getModelName()) {
					if ($lComposition->getValue("column") == $pColumn) {
						return false;
					}
					$lIsComposition = true;
				}
			}
		}
		return $lIsComposition;
	}
	
	public function hasReturnValue() {
		return false;
	}
}