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

	const UPDATE = 'update';
	const INSERT = 'insert';
	
	private static $sDbObjectById = array();

	private $mInitialized = false;
	private $mHasIncrementalId = false;
	
	private function _initDbObject() {
		$this->loadValue("database");
		self::$sDbObjectById[$this->getValue("database")->getValue("id")] = DatabaseController::getInstanceWithDataBaseObject($this->getValue("database"));
	}
	
	private function _initColumnsProperties($pModel) {
		$lQuery = 'SHOW COLUMNS FROM '.$this->getValue("name");
		$lResult = self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeSimpleQuery($lQuery);
	
		if ($pModel->hasUniqueIdProperty()) {
			$lColumnId = $pModel->getProperty($pModel->getFirstIdPropertyName())->getSerializationName();
			foreach ($lResult as $lRow) {
				if ($lRow['Field'] == $lColumnId) {
					if ($lRow['Extra'] == 'auto_increment') {
						$this->mHasIncrementalId = true;
					}
					break;
				}
			}
		}
		$this->mInitialized = true;
	}
	
	public function saveObject($pObject, $pOperation = null) {
		if ($this !== $pObject->getModel()->getSerialization()) {
			throw new \Exception('this serialization mismatch with parameter object serialization');
		}
		return $this->_saveObject($pObject, $pOperation);
	}
	
	protected function _saveObject($pObject, $pOperation = null) {
		if (!array_key_exists($this->getValue("database")->getValue("id"), self::$sDbObjectById)) {
			$this->_initDbObject();
		}
		if (!$this->mInitialized) {
			$this->_initColumnsProperties($pObject->getModel());
		}
		if (is_null($pOperation)) {
			return $this->_saveObjectWithIncrementalId($pObject);
		} else if ($pOperation == self::INSERT) {
			return $this->_insertObject($pObject);
		} else if ($pOperation == self::UPDATE) {
			return $this->_updateObject($pObject);
		} else {
			throw new \Exception('unknown operation '.$pOperation);
		}
	}
	
	private function _saveObjectWithIncrementalId($pObject) {
		if (!$this->mHasIncrementalId) {
			throw new \Exception('operation not specified');
		}
		if ($pObject->hasCompleteId()) {
			return $this->_updateObject($pObject);
		} else {
			$lResult = $this->_insertObject($pObject);
			$lId = self::$sDbObjectById[$this->getValue("database")->getValue("id")]->lastInsertId();
			$lPropertyName = $pObject->getModel()->getFirstIdPropertyName();
			$pObject->setValue($lPropertyName, $lId);
			return $lResult;
		}
	}
	
	private function _insertObject($pObject) {
		$lMapOfString = $pObject->toSqlDataBase();
		$lQuery = "INSERT INTO ".$this->getValue("name")." (".implode(", ", array_keys($lMapOfString)).") VALUES (".implode(", ", array_fill(0, count($lMapOfString), '?')).");";
		return self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeSimpleQuery($lQuery, array_values($lMapOfString));
	}
	
	private function _updateObject($pObject) {
		if (!$pObject->hasCompleteId()) {
			throw new \Exception('update operation require complete id');
		}
		$lModel            = $pObject->getModel();
		$lConditions       = array();
		$lUpdates          = array();
		$lUpdateValues     = array();
		$lConditionsValues = array();
		$lMapOfString      = $pObject->toSqlDataBase(false);
		
		foreach ($lMapOfString as $lPropertyName => $lValue) {
			$lProperty = $lModel->getProperty($lPropertyName);
			if ($lProperty->isId()) {
				$lConditions[]       = "{$lProperty->getSerializationName()} = ?";
				$lConditionsValues[] = $lValue;
			} else {
				$lUpdates[]      = "{$lProperty->getSerializationName()} = ?";
				$lUpdateValues[] = $lValue;
			}
		}
		$lQuery = "UPDATE ".$this->getValue("name")." SET ".implode(", ", $lUpdates)." WHERE ".implode(" and ", $lConditions).";";
		return self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeSimpleQuery($lQuery, array_merge($lUpdateValues, $lConditionsValues));
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
			$this->_initDbObject();
		}
		if (!$this->mInitialized) {
			$this->_initColumnsProperties($pObject->getModel());
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