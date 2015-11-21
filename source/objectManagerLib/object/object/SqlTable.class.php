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
	
	public function loadObject($pObject, $pId, $pColumn = null, $pParentModel = null) {
		$lWhereColumns = $this->getJoinColumns($pObject->getModel(), $pColumn, $pParentModel);
		$lReturn = $this->_loadObject($pObject, $pId, $pColumn, $pParentModel, array(), $lWhereColumns);
		return $lReturn;
	}
	
	public function loadCompositionIds($pObject, $pId, $pColumn, $pParentModel) {
		$lReturn = false;
		$lWhereColumns = $this->getCompositionColumns($pParentModel, $pColumn);
		if (count($lWhereColumns) > 0) {
			$lPropertiesIds = $pObject->getModel()->getIds();
			if (count($lPropertiesIds) !== 1) {
				trigger_error("Warning! model '{$pObject->getModel()->getModelName()}' doesn't have a unique property id. All model is loaded");
				$lSelectColumns = array();
				//throw new \Exception("model '{$pObject->getModel()->getModelName()}' must have one and only one id property");
			} else {
				$lSelectColumns = array($pObject->getProperty($lPropertiesIds[0])->getSerializationName());
			}
			$lReturn = $this->_loadObject($pObject, $pId, $pColumn, $pParentModel, $lSelectColumns, $lWhereColumns);
			if ((count($lSelectColumns) > 0) && ($pObject->getModel() instanceof ModelArray)) {
				foreach ($pObject->getValues() as $lValue) {
					$lValue->setLoadStatus(false);
				}
			}
		}
		else {
			throw new \Exception('error : property is not serialized in database composition');
		}
		return $lReturn;
	}
	
	public function _loadObject($pObject, $pId, $pColumn, $pParentModel, $pSelectColumns, $pWhereColumns) {
		$lReturn = false;
		if (!array_key_exists($this->getValue("database")->getValue("id"), self::$sDbObjectById)) {
			$this->loadValue("database");
			self::$sDbObjectById[$this->getValue("database")->getValue("id")] = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
		}
		$lLinkedLiteral = new LogicalJunction(LogicalJunction::DISJUNCTION);
		foreach ($pWhereColumns as $lColumn) {
			$lLinkedLiteral->addLiteral(new Literal($this->getValue("name"), $lColumn, "=", $pId));
		}
		$lSelectQuery = new SelectQuery($this->getValue("name"));
		$lSelectQuery->setWhereLogicalJunction($lLinkedLiteral);
		foreach ($pSelectColumns as $lColumn) {
			$lSelectQuery->addSelectColumn($lColumn);
		}
		$lResult = self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeQuery($lSelectQuery);
	
		if (is_array($lResult)) {
			$lAddUnloadValues = count($pSelectColumns) == 0;
			if ($pObject->getModel() instanceof ModelArray) {
				$pObject->fromSqlDataBase($lResult, $lAddUnloadValues);
				$lReturn = true;
			}
			else if (count($lResult) > 0) {
				$pObject->fromSqlDataBase($lResult[0], $lAddUnloadValues);
				$lReturn = true;
			}
		}
		return $lReturn;
	}
	
	private function getJoinColumns($pModel, $pColumn, $pParentModel) {
		$lColumns = $this->getCompositionColumns($pParentModel, $pColumn);
		if (count($lColumns) == 0) {
			foreach ($pModel->getIds() as $pPropertyName) {
				$lColumns[] = $pModel->getProperty($pPropertyName)->getSerializationName();
			}
		}
		return $lColumns;
	}
	
	public function getCompositionColumns($pParentModel, $pColumn) {
		$lColumns = array();
		if (!is_null($pParentModel) && $this->isComposition($pParentModel, $pColumn)) {
			foreach ($this->getValue("compositions")->getValues() as $lComposition) {
				if ($lComposition->getValue("parent") == $pParentModel->getModelName()) {
					$lColumns[] = $lComposition->getValue("column");
				}
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