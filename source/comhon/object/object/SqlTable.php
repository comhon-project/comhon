<?php
namespace comhon\object\object;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\SelectQuery;
use comhon\database\Literal;
use comhon\object\singleton\InstanceModel;
use comhon\object\model\ModelForeign;
use comhon\object\model\ModelArray;
use comhon\object\object\ObjectArray;
use comhon\object\model\Model;
use comhon\object\MainObjectCollection;

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
	
	protected function _saveObject(Object $pObject, $pOperation = null) {
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
			$pObject->setValue($pObject->getModel()->getFirstIdPropertyName(), $lId);
			return $lResult;
		}
	}
	
	private function _insertObject(Object $pObject) {
		$lMapOfString = $pObject->toSqlDatabase(self::getDatabaseConnectionTimeZone());
		
		foreach ($pObject->getModel()->getEscapedDbColumns() as $lColumn => $lEscapedColumn) {
			if (array_key_exists($lColumn, $lMapOfString)) {
				$lMapOfString[$lEscapedColumn] = $lMapOfString[$lColumn];
				unset($lMapOfString[$lColumn]);
			}
		}
		$lQuery = "INSERT INTO ".$this->getValue("name")." (".implode(", ", array_keys($lMapOfString)).") VALUES (".implode(", ", array_fill(0, count($lMapOfString), '?')).");";
		return self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeSimpleQuery($lQuery, array_values($lMapOfString));
	}
	
	/**
	 * @param Object $pObject
	 * @throws \Exception
	 */
	private function _updateObject(Object $pObject) {
		if (!$pObject->getModel()->hasIdProperty() || !$pObject->hasCompleteId()) {
			throw new \Exception('update operation require complete id');
		}
		$lModel            = $pObject->getModel();
		$lConditions       = array();
		$lUpdates          = array();
		$lUpdateValues     = array();
		$lConditionsValues = array();

		$lMapOfString      = $pObject->toSqlDatabase(self::getDatabaseConnectionTimeZone());
		$lEscapedDbColumns = $pObject->getModel()->getEscapedDbColumns();
		
		foreach ($pObject->getModel()->getIdProperties() as $lIdProperty) {
			$lColumn = $pObject->getModel()->getProperty($lIdProperty)->getSerializationName();
			$lValue  = $pObject->getValue($lIdProperty);
			if (is_null($lValue)) {
				throw new \Exception('update failed, id is not set');
			}
			unset($lMapOfString[$lColumn]);
			$lConditions[]       = "$lColumn = ?";
			$lConditionsValues[] = $lValue;
		}
		foreach ($lMapOfString as $lColumn => $lValue) {
			if (array_key_exists($lColumn, $lEscapedDbColumns)) {
				$lColumn   = $lEscapedDbColumns[$lColumn];
			}
			$lUpdates[]      = "$lColumn = ?";
			$lUpdateValues[] = $lValue;
		}
		$lQuery = "UPDATE ".$this->getValue("name")." SET ".implode(", ", $lUpdates)." WHERE ".implode(" and ", $lConditions).";";
		return self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeSimpleQuery($lQuery, array_merge($lUpdateValues, $lConditionsValues));
	}

	/**
	 * (non-PHPdoc)
	 * @see \comhon\object\object\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(Object $pObject) {
		$lWhereColumns = [];
		$lModel = $pObject->getModel();
		foreach ($lModel->getIdProperties() as $lPropertyName) {
			$lWhereColumns[$lModel->getProperty($lPropertyName)->getSerializationName()] = $pObject->getValue($lPropertyName);
		}
		$lReturn = $this->_loadObjectFromDatabase($pObject, [], $lWhereColumns, LogicalJunction::CONJUNCTION);
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
	
	/**
	 * 
	 * @param Object $pObject
	 * @param string[] $pSelectColumns
	 * @param string[] $pWhereColumns
	 * @param string $lLogicalJunctionType
	 * @return boolean
	 */
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
		$lRows         = self::$sDbObjectById[$this->getValue("database")->getValue("id")]->executeQuery($lSelectQuery);
		$lIsModelArray = $pObject->getModel() instanceof ModelArray;
		
		if (is_array($lRows) && ($lIsModelArray || (count($lRows) == 1))) {
			if (!is_null($this->getInheritanceKey())) {
				if ($lIsModelArray) {
					$lExtendsModel = $pObject->getModel()->getUniqueModel();
					foreach ($lRows as &$lRow) {
						$lModel = $this->getInheritedModel($lRow, $lExtendsModel);
						$lRow[Model::INHERITANCE_KEY] = $lModel->getModelName();
					}
				} else {
					$lModel = $this->getInheritedModel($lRows[0], $pObject->getModel());
					if ($lModel !== $pObject->getModel()) {
						$pObject->cast($lModel);
					}
				}
			}
			if (empty($pSelectColumns)) {
				$pObject->fromSqlDatabase($lIsModelArray ? $lRows : $lRows[0], self::getDatabaseConnectionTimeZone());
			} else {
				$pObject->fromSqlDatabaseId($lIsModelArray ? $lRows : $lRows[0], self::getDatabaseConnectionTimeZone());
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
	
	/**
	 * @param array $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	protected function getInheritedModel($pValue, Model $pExtendsModel) {
		return array_key_exists($this->mInheritanceKey, $pValue) && !is_null($pValue[$this->mInheritanceKey]) 
				? InstanceModel::getInstance()->getInstanceModel($pValue[$this->mInheritanceKey]) : $pExtendsModel;
	}
	
	public static function getDatabaseConnectionTimeZone() {
		return is_object(Config::getInstance()->getValue('database'))
				? (Config::getInstance()->getValue('database')->hasValue('timezone')
					? Config::getInstance()->getValue('database')->getValue('timezone')
					: 'UTC')
				: 'UTC';
	}
}