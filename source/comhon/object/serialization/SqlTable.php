<?php
namespace comhon\object\serialization;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\SelectQuery;
use comhon\database\Literal;
use comhon\model\singleton\ModelManager;
use comhon\model\ModelForeign;
use comhon\model\ModelArray;
use comhon\object\ObjectArray;
use comhon\model\Model;
use comhon\object\collection\MainObjectCollection;
use comhon\model\MainModel;
use comhon\utils\SqlUtils;
use comhon\model\ModelInteger;
use comhon\object\Object;
use comhon\object\config\Config;

class SqlTable extends SerializationUnit {
	
	const HAS_INCR_ID_INDEX          = 0;
	const AUTO_INCR_PROPERTIES_INDEX = 1;
	const COLUMS_TO_CAST_INDEX       = 2;
	
	const INTEGER_INDEX = 0;
	const FLOAT_INDEX   = 1;
	
	private $mDbController;
	private $mTableId;

	private static $sAutoIncrementColumns = [];
	private static $sColumnsToEscape = [];
	private static $sModelInfos = [];
	
	private function _initDatabaseInterfacing(Model $pModel) {
		if (is_null($this->mDbController)) {
			$this->mSettings->loadValue('database');
			$this->mTableId = $this->mSettings->getValue('name').'_'.$this->mSettings->getValue('database')->getValue('id');
			$this->mDbController = DatabaseController::getInstanceWithDataBaseObject($this->mSettings->getValue('database'));
			$this->_initColumnsInfos();
		}
		$this->_initColumnsProperties($pModel);
	}
	
	private function _initColumnsInfos() {
		if (!array_key_exists($this->mTableId, self::$sAutoIncrementColumns)) {
			list(self::$sAutoIncrementColumns[$this->mTableId], self::$sColumnsToEscape[$this->mTableId]) = $this->_getSpecifiqueColumns();
		}
	}
	
	private function _getTableId() {
		return $this->mSettings->getValue('name').'_'.$this->mSettings->getValue('database')->getValue('id');
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * @return [string[],string[]]
	 */
	private function _getSpecifiqueColumns() {
	switch ($this->mSettings->getValue('database')->getValue('DBMS')) {
			case 'mysql': return $this->_getSpecifiqueColumnsMySql();
			//case 'pgsql':
			//case 'cubrid':
			//case 'dblib':
			//case 'firebird':
			//case 'ibm':
			//case 'informix':
			//case 'sqlsrv':
			//case 'oci':
			//case 'odbc':
			//case 'sqlite':
			//case '4D':
			default: throw new \Exception("DBMS '{$this->mSettings->getValue('database')->getValue('DBMS')}' not managed");
		}
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * @return [string[],string[]]
	 */
	private function _getSpecifiqueColumnsMySql() {
		$lDBMS = $this->mSettings->getValue('database')->getValue('DBMS');
		$lAutoIncrementColumns = [];
		$lColumnsToEscape = [];
		
		$lQuery = 'SHOW COLUMNS FROM '.$this->mSettings->getValue('name');
		$lResult = $this->mDbController->executeSimpleQuery($lQuery)->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($lResult as $lRow) {
			if ($lRow['Extra'] === 'auto_increment') {
				$lAutoIncrementColumns[] = $lRow['Field'];
				break;
			}
			if (SqlUtils::isReservedWorld($lDBMS, $lRow['Field'])) {
				$lColumnsToEscape[$lRow['Field']] = '`'.$lRow['Field'].'`';
			}
		}
		return [$lAutoIncrementColumns, $lColumnsToEscape];
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * @return [string[],string[]]
	 */
	private function _getSpecifiqueColumnsPgSql() {
		$lDBMS = $this->mSettings->getValue('database')->getValue('DBMS');
		$lAutoIncrementColumns = [];
		$lColumnsToEscape = [];
	
		// TODO manage sequence
		// else if ($has_sequence) {
		//   SELECT table_name, column_name, column_default from information_schema.columns where table_name='testing';
		//   or
		//   SELECT * from information_schema.columns where table_name = '<table_name>'
		//   SELECT pg_get_serial_sequence('<table_name>', '<column_name>')
		// }
		
		return [$lAutoIncrementColumns, $lColumnsToEscape];
	}
	
	private function _initColumnsProperties(Model $pModel) {
		if (array_key_exists($pModel->getModelName(), self::$sModelInfos)) {
			return;
		}
		$lAutoIncrementProperties = [];
		$lHasIncrementalId = false;
		
		$lAutoIncrementColumns = self::$sAutoIncrementColumns[$this->mTableId];
		if (!empty($lAutoIncrementColumns)) {
			foreach ($pModel->getSerializableProperties() as $lProperty) {
				if (in_array($lProperty->getSerializationName(), $lAutoIncrementColumns)) {
					$lAutoIncrementProperties[] = $lProperty->getName();
					if ($lProperty->isId()) {
						$lHasIncrementalId = true;
					}
				}
			}
		}
		
		self::$sModelInfos[$pModel->getModelName()] = [
			self::HAS_INCR_ID_INDEX          => $lHasIncrementalId,
			self::AUTO_INCR_PROPERTIES_INDEX => $lAutoIncrementProperties,
			self::COLUMS_TO_CAST_INDEX       => self::_initColumnsToCast($pModel),
		];
	}
	
	private static function _initColumnsToCast(Model $pModel) {
		if (array_key_exists($pModel->getModelName(), self::$sModelInfos)) {
			return self::$sModelInfos[$pModel->getModelName()][self::COLUMS_TO_CAST_INDEX];
		}
		$lCastIntegerColumns = [];
		$lCastFloatColumns = [];
		foreach ($pModel->getSerializableProperties() as $lProperty) {
			if ($lProperty->isSerializable()) {
				if (!$lProperty->isForeign()) {
					if ($lProperty->isInteger()) {
						$lCastIntegerColumns[] = $lProperty->getSerializationName();
					} else if ($lProperty->isFloat()) {
						$lCastFloatColumns[] = $lProperty->getSerializationName();
					}
				}
				else if (!$lProperty->isAggregation()) {
					if ($lProperty->hasMultipleSerializationNames()) {
						foreach ($lProperty->getMultipleIdProperties() as $lSerializationName => $lProperty) {
							if ($lProperty->isInteger()) {
								$lCastIntegerColumns[] = $lSerializationName;
							} else if ($lProperty->isFloat()) {
								$lCastFloatColumns[] = $lSerializationName;
							}
						}
					}
					else if ($lProperty->getModel()->hasUniqueIdProperty()) {
						if ($lProperty->getModel()->getFirstIdProperty()->isInteger()) {
							$lCastIntegerColumns[] = $lProperty->getSerializationName();
						} else if ($lProperty->getModel()->getFirstIdProperty()->isFloat()) {
							$lCastFloatColumns[] = $lProperty->getSerializationName();
						}
					}
				}
			}
		}
		return [$lCastIntegerColumns, $lCastFloatColumns];
	}
	
	/**
	 * @param Object $pObject
	 * @param string $pOperation
	 * @return integer
	 */
	protected function _saveObject(Object $pObject, $pOperation = null) {
		$this->_initDatabaseInterfacing($pObject->getModel());
		
		if (self::$sModelInfos[$pObject->getModel()->getModelName()][self::HAS_INCR_ID_INDEX]) {
			return $this->_saveObjectWithIncrementalId($pObject);
		} else if ($pOperation == self::CREATE) {
			return $this->_insertObject($pObject);
		} else if ($pOperation == self::UPDATE) {
			return $this->_updateObject($pObject);
		} else {
			throw new \Exception('unknown operation '.$pOperation);
		}
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @throws \Exception
	 * @return integer
	 */
	private function _saveObjectWithIncrementalId(Object $pObject) {
		if (!self::$sModelInfos[$pObject->getModel()->getModelName()][self::HAS_INCR_ID_INDEX]) {
			throw new \Exception('operation not specified');
		}
		if ($pObject->hasCompleteId()) {
			return $this->_updateObject($pObject);
		} else {
			return $this->_insertObject($pObject);
		}
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @throws \Exception
	 * @return integer
	 */
	private function _insertObject(Object $pObject) {
		$lMapOfString = $pObject->toSqlDatabase(self::getDatabaseConnectionTimeZone());
		if (!is_null($this->getInheritanceKey())) {
			$lMapOfString[$this->getInheritanceKey()] = $pObject->getModel()->getModelName();
		}
		
		if (is_null($this->mDbController->getInsertReturn())) {
			$lQuery = 'INSERT INTO '.$this->mSettings->getValue('name').' ('.$this->_getSelectColumnString($lMapOfString)
					.') VALUES ('.implode(', ', array_fill(0, count($lMapOfString), '?')).');';
		}else if ($this->mDbController->getInsertReturn() == 'RETURNING') {
			// TODO
			throw new \Exception('not supported yet');
		}else if ($this->mDbController->getInsertReturn() == 'OUTPUT') {
			// TODO
			throw new \Exception('not supported yet');
		}
		$lStatement = $this->mDbController->executeSimpleQuery($lQuery, array_values($lMapOfString));
		$lAffectedRows = $lStatement->rowCount();
		
		$lAutoIncrementProperties = self::$sModelInfos[$pObject->getModel()->getModelName()][self::AUTO_INCR_PROPERTIES_INDEX];
		if (($lAffectedRows > 0) && !empty($lAutoIncrementProperties)) {
			if ($this->mDbController->isSupportedLastInsertId()) {
				$lIncrementalValue = $pObject->getProperty($lAutoIncrementProperties[0])->getModel()->castValue($this->mDbController->lastInsertId());
				$pObject->setValue($lAutoIncrementProperties[0], $lIncrementalValue);
			} else {
				// TODO manage sequence with return value
			}
		}
		return $lAffectedRows;
	}
	
	/**
	 * 
	 * @param [] $pMapOfString
	 * @return string
	 */
	private function _getSelectColumnString($pMapOfString) {
		$lColumnsToEscape = self::$sColumnsToEscape[$this->mTableId];
		
		if (empty($lColumnsToEscape)) {
			return implode(', ', array_keys($pMapOfString));
		} else {
			$lColumns = [];
			foreach ($pMapOfString as $lColumn => $lString) {
				if (array_key_exists($lColumn, $lColumnsToEscape)) {
					$lColumns[] = $lColumnsToEscape[$lColumn];
				} else {
					$lColumns[] = $lColumn;
				}
			}
			return implode(', ', $lColumns);
		}
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @throws \Exception
	 * @return integer
	 */
	private function _updateObject(Object $pObject) {
		if (!$pObject->getModel()->hasIdProperties() || !$pObject->hasCompleteId()) {
			throw new \Exception('update operation require complete id');
		}
		$lModel            = $pObject->getModel();
		$lConditions       = [];
		$lUpdates          = [];
		$lUpdateValues     = [];
		$lConditionsValues = [];

		$lMapOfString = $pObject->toSqlDatabase(self::getDatabaseConnectionTimeZone());
		if (!is_null($this->getInheritanceKey())) {
			$lMapOfString[$this->getInheritanceKey()] = $pObject->getModel()->getModelName();
		}
		
		foreach ($pObject->getModel()->getIdProperties() as $lIdPropertyName => $lIdProperty) {
			$lColumn = $lIdProperty->getSerializationName();
			$lValue  = $pObject->getValue($lIdPropertyName);
			if (is_null($lValue)) {
				throw new \Exception('update failed, id is not set');
			}
			unset($lMapOfString[$lColumn]);
			$lConditions[]       = "$lColumn = ?";
			$lConditionsValues[] = $lValue;
		}
		$lColumnsToEscape = self::$sColumnsToEscape[$this->mTableId];
		foreach ($lMapOfString as $lColumn => $lValue) {
			if (array_key_exists($lColumn, $lColumnsToEscape)) {
				$lColumn   = $lColumnsToEscape[$lColumn];
			}
			$lUpdates[]      = "$lColumn = ?";
			$lUpdateValues[] = $lValue;
		}
		$lQuery = 'UPDATE '.$this->mSettings->getValue('name').' SET '.implode(', ', $lUpdates).' WHERE '.implode(' and ', $lConditions).';';
		$lStatement = $this->mDbController->executeSimpleQuery($lQuery, array_merge($lUpdateValues, $lConditionsValues));
		
		return $lStatement->rowCount();
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @throws \Exception
	 * @return integer
	 */
	protected function _deleteObject(Object $pObject) {
		if (!$pObject->getModel()->hasIdProperties() || !$pObject->hasCompleteId()) {
			throw new \Exception('delete operation require complete id');
		}
		$this->_initDatabaseInterfacing($pObject->getModel());
		
		$lModel            = $pObject->getModel();
		$lConditions       = [];
		$lConditionsValues = [];
	
		foreach ($pObject->getModel()->getIdProperties() as $lIdPropertyName => $lIdProperty) {
			$lColumn = $lIdProperty->getSerializationName();
			$lValue  = $pObject->getValue($lIdPropertyName);
			if (is_null($lValue)) {
				throw new \Exception('delete failed, id is not set');
			}
			$lConditions[]       = "$lColumn = ?";
			$lConditionsValues[] = $lValue;
		}
		$lQuery = 'DELETE FROM '.$this->mSettings->getValue('name').' WHERE '.implode(' and ', $lConditions).';';
		$lStatement = $this->mDbController->executeSimpleQuery($lQuery, $lConditionsValues);
		
		return $lStatement->rowCount();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \comhon\object\serialization\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(Object $pObject) {
		$lWhereColumns = [];
		$lModel = $pObject->getModel();
		$lConjunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
		foreach ($lModel->getIdProperties() as $lPropertyName => $lProperty) {
			$lConjunction->addLiteral(new Literal($this->mSettings->getValue('name'), $lProperty->getSerializationName(), '=', $pObject->getValue($lPropertyName)));
		}
		$lReturn = $this->_loadObjectFromDatabase($pObject, [], $lConjunction);
		return $lReturn;
	}
	
	public function loadAggregation(ObjectArray $pObject, $pParentId, $pAggregationProperties, $pOnlyIds) {
		$lReturn        = false;
		$lModel         = $pObject->getModel()->getUniqueModel();
		$lDisjunction   = $this->getAggregationConditions($lModel, $pParentId, $pAggregationProperties);
		$lSelectColumns = [];
		$lIdProperties  = $lModel->getIdProperties();
		
		if (count($lDisjunction->getLiterals()) == 0 && count($lDisjunction->getLogicalJunction()) == 0) {
			throw new \Exception('error : property is not serialized in database aggregation');
		}
		if ($pOnlyIds) {
			if (empty($lIdProperties)) {
				trigger_error("Warning! model '{$lModel->getModelName()}' doesn't have a unique property id. All model is loaded");
			}
			foreach ($lIdProperties as $lIdProperty) {
				$lSelectColumns[] = $lIdProperty->getSerializationName();
			}
		}
		$lReturn = $this->_loadObjectFromDatabase($pObject, $lSelectColumns, $lDisjunction);
		return $lReturn;
	}
	
	/**
	 * 
	 * @param Object $pObject
	 * @param string[] $pSelectColumns
	 * @param logic$lLogicalJunctionType
	 * @return boolean
	 */
	private function _loadObjectFromDatabase($pObject, $pSelectColumns, LogicalJunction $pLogicalJunction) {
		$lSuccess = false;
		$this->_initDatabaseInterfacing($pObject->getModel());
		
		$lSelectQuery = new SelectQuery($this->mSettings->getValue('name'));
		$lSelectQuery->where($pLogicalJunction);
		foreach ($pSelectColumns as $lColumn) {
			$lSelectQuery->getMainTable()->addSelectedColumn($lColumn);
		}
		$lRows = $this->mDbController->executeSelectQuery($lSelectQuery);
		
		if ($pObject->getModel() instanceof ModelArray) {
			$lIsModelArray = true;
			self::castStringifiedColumns($lRows, $pObject->getModel()->getUniqueModel());
		} else {
			$lIsModelArray = false;
			self::castStringifiedColumns($lRows, $pObject->getModel());
		}
		
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
	
	public function getAggregationConditions($pModel, $pParentId, $pAggregationProperties) {
		
		$lDisjunction = new LogicalJunction(LogicalJunction::DISJUNCTION);
		foreach ($pAggregationProperties as $lAggregationProperty) {
			$lProperty = $pModel->getProperty($lAggregationProperty, true);
			if ($lProperty->hasMultipleSerializationNames()) {
				$lDecodedId = json_decode($pParentId);
				$lConjunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
				foreach ($lProperty->getMultipleIdProperties() as $lSerializationName => $lMultipleForeignProperty) {
					$lConjunction->addLiteral(new Literal($this->mSettings->getValue('name'), $lSerializationName, '=', current($lDecodedId)));
					next($lDecodedId);
				}
				$lDisjunction->addLogicalJunction($lConjunction);
			} else {
				$lDisjunction->addLiteral(new Literal($this->mSettings->getValue('name'), $pModel->getProperty($lAggregationProperty, true)->getSerializationName(), '=', $pParentId));
			}
		}
		return $lDisjunction;
	}
	
	/**
	 * @param array $pValue
	 * @param Model $pExtendsModel
	 * @return Model
	 */
	public function getInheritedModel($pValue, Model $pExtendsModel) {
		return array_key_exists($this->mInheritanceKey, $pValue) && !is_null($pValue[$this->mInheritanceKey]) 
				? ModelManager::getInstance()->getInstanceModel($pValue[$this->mInheritanceKey]) : $pExtendsModel;
	}
	
	public static function getDatabaseConnectionTimeZone() {
		return is_object(Config::getInstance()->getValue('database'))
				? (Config::getInstance()->getValue('database')->hasValue('timezone')
					? Config::getInstance()->getValue('database')->getValue('timezone')
					: 'UTC')
				: 'UTC';
	}
	
	public static function castStringifiedColumns(&$pRows, Model $pModel) {
		if (empty($pRows)) {
			return;
		}
		$lColumnsToCast = self::_initColumnsToCast($pModel);
		$lCastIntegerColumns = [];
		$lCastFloatColumns = [];
		foreach ($lColumnsToCast[self::INTEGER_INDEX] as $lColumn) {
			if (isset($pRows[0][$lColumn]) && is_string($pRows[0][$lColumn])) {
				$lCastIntegerColumns[] = $lColumn;
			}
		}
		foreach ($lColumnsToCast[self::FLOAT_INDEX] as $lColumn) {
			if (isset($pRows[0][$lColumn]) && is_string($pRows[0][$lColumn])) {
				$lCastFloatColumns[] = $lColumn;
			}
		}
		if (!empty($lCastIntegerColumns) || !empty($lCastFloatColumns)) {
			for ($i = 0; $i < count($pRows); $i++) {
				foreach ($lCastIntegerColumns as $lColumn) {
					if (is_numeric($pRows[$i][$lColumn])) {
						$pRows[$i][$lColumn] = (integer) $pRows[$i][$lColumn];
					}
				}
				foreach ($lCastFloatColumns as $lColumn) {
					if (is_numeric($pRows[$i][$lColumn])) {
						$pRows[$i][$lColumn] = (float) $pRows[$i][$lColumn];
					}
				}
			}
		}
	}
	
}