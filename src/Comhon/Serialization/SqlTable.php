<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Serialization;

use Comhon\Database\DatabaseController;
use Comhon\Database\LogicalJunction;
use Comhon\Database\SelectQuery;
use Comhon\Database\Literal;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\ModelArray;
use Comhon\Object\ObjectArray;
use Comhon\Model\Model;
use Comhon\Utils\SqlUtils;
use Comhon\Object\ComhonObject;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Model\ModelBoolean;
use Comhon\Interfacer\Interfacer;
use Comhon\Model\ModelFloat;
use Comhon\Model\ModelInteger;

class SqlTable extends SerializationUnit {
	
	const HAS_INCR_ID_INDEX          = 0;
	const AUTO_INCR_PROPERTIES_INDEX = 1;
	const COLUMS_TO_CAST_INDEX       = 2;
	
	const INTEGER_INDEX = 0;
	const FLOAT_INDEX   = 1;
	const BOOLEAN_INDEX = 2;
	
	private $dbController;
	private $tableId;

	private static $autoIncrementColumns = [];
	private static $columnsToEscape = [];
	private static $modelInfos = [];
	
	private static $interfacer;
	
	private function _initDatabaseInterfacing(Model $model) {
		if (is_null($this->dbController)) {
			$this->settings->loadValue('database');
			$this->tableId = $this->settings->getValue('name').'_'.$this->settings->getValue('database')->getValue('id');
			$this->dbController = DatabaseController::getInstanceWithDataBaseObject($this->settings->getValue('database'));
			$this->_initColumnsInfos();
		}
		$this->_initColumnsProperties($model);
	}
	
	private function _initColumnsInfos() {
		if (!array_key_exists($this->tableId, self::$autoIncrementColumns)) {
			list(self::$autoIncrementColumns[$this->tableId], self::$columnsToEscape[$this->tableId]) = $this->_getSpecifiqueColumns();
		}
	}
	
	private function _getTableId() {
		return $this->settings->getValue('name').'_'.$this->settings->getValue('database')->getValue('id');
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * @return [string[],string[]]
	 */
	private function _getSpecifiqueColumns() {
	switch ($this->settings->getValue('database')->getValue('DBMS')) {
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
			default: throw new \Exception("DBMS '{$this->settings->getValue('database')->getValue('DBMS')}' not managed");
		}
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * @return [string[],string[]]
	 */
	private function _getSpecifiqueColumnsMySql() {
		$DBMS = $this->settings->getValue('database')->getValue('DBMS');
		$autoIncrementColumns = [];
		$columnsToEscape = [];
		
		$query = 'SHOW COLUMNS FROM '.$this->settings->getValue('name');
		$result = $this->dbController->executeSimpleQuery($query)->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($result as $row) {
			if ($row['Extra'] === 'auto_increment') {
				$autoIncrementColumns[] = $row['Field'];
				break;
			}
			if (SqlUtils::isReservedWorld($DBMS, $row['Field'])) {
				$columnsToEscape[$row['Field']] = '`'.$row['Field'].'`';
			}
		}
		return [$autoIncrementColumns, $columnsToEscape];
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * @return [string[],string[]]
	 */
	private function _getSpecifiqueColumnsPgSql() {
		$DBMS = $this->settings->getValue('database')->getValue('DBMS');
		$autoIncrementColumns = [];
		$columnsToEscape = [];
	
		// TODO manage sequence
		// else if ($has_sequence) {
		//   SELECT table_name, column_name, column_default from information_schema.columns where table_name='testing';
		//   or
		//   SELECT * from information_schema.columns where table_name = '<table_name>'
		//   SELECT pg_get_serial_sequence('<table_name>', '<column_name>')
		// }
		
		return [$autoIncrementColumns, $columnsToEscape];
	}
	
	private function _initColumnsProperties(Model $model) {
		if (array_key_exists($model->getName(), self::$modelInfos)) {
			return;
		}
		$autoIncrementProperties = [];
		$hasIncrementalId = false;
		
		$autoIncrementColumns = self::$autoIncrementColumns[$this->tableId];
		if (!empty($autoIncrementColumns)) {
			foreach ($model->getSerializableProperties() as $property) {
				if (in_array($property->getSerializationName(), $autoIncrementColumns)) {
					$autoIncrementProperties[] = $property->getName();
					if ($property->isId()) {
						$hasIncrementalId = true;
					}
				}
			}
		}
		
		self::$modelInfos[$model->getName()] = [
			self::HAS_INCR_ID_INDEX          => $hasIncrementalId,
			self::AUTO_INCR_PROPERTIES_INDEX => $autoIncrementProperties,
			self::COLUMS_TO_CAST_INDEX       => self::_initColumnsToCast($model),
		];
	}
	
	private static function _initColumnsToCast(Model $model) {
		if (array_key_exists($model->getName(), self::$modelInfos)) {
			return self::$modelInfos[$model->getName()][self::COLUMS_TO_CAST_INDEX];
		}
		$castIntegerColumns = [];
		$castFloatColumns   = [];
		$castBooleanColumns = [];
		foreach ($model->getSerializableProperties() as $property) {
			if ($property->isSerializable()) {
				if (!$property->isForeign()) {
					if ($property->getModel() instanceof ModelInteger) {
						$castIntegerColumns[] = $property->getSerializationName();
					} else if ($property->getModel() instanceof ModelFloat) {
						$castFloatColumns[] = $property->getSerializationName();
					} else if (($property->getModel() instanceof ModelBoolean)) {
						$castBooleanColumns[] = $property->getSerializationName();
					}
				}
				else if (!$property->isAggregation()) {
					if ($property->hasMultipleSerializationNames()) {
						foreach ($property->getMultipleIdProperties() as $serializationName => $property) {
							if ($property->getModel() instanceof ModelInteger) {
								$castIntegerColumns[] = $serializationName;
							} else if ($property->getModel() instanceof ModelFloat) {
								$castFloatColumns[] = $serializationName;
							} else if (($property->getModel() instanceof ModelBoolean)) {
								$castBooleanColumns[] = $serializationName;
							}
						}
					}
					else if ($property->getModel()->hasUniqueIdProperty()) {
						if ($property->getModel()->getFirstIdProperty()->getModel() instanceof ModelInteger) {
							$castIntegerColumns[] = $property->getSerializationName();
						} else if ($property->getModel()->getFirstIdProperty()->getModel() instanceof ModelFloat) {
							$castFloatColumns[] = $property->getSerializationName();
						} else if (($property->getModel() instanceof ModelBoolean)) {
							$castBooleanColumns[] = $property->getSerializationName();
						}
					}
				}
			}
		}
		return [$castIntegerColumns, $castFloatColumns, $castBooleanColumns];
	}
	
	public static function getInterfacer($flagObjectAsLoaded = true) {
		if (is_null(self::$interfacer)) {
			self::$interfacer = new AssocArrayInterfacer();
			self::$interfacer->setPrivateContext(true);
			self::$interfacer->setSerialContext(true);
			self::$interfacer->setFlagValuesAsUpdated(false);
			self::$interfacer->setDateTimeFormat('Y-m-d H:i:s');
			self::$interfacer->setDateTimeZone(Config::getInstance()->getDataBaseTimezone());
			self::$interfacer->setFlattenValues(true);
		}
		self::$interfacer->setFlagObjectAsLoaded($flagObjectAsLoaded);
		return self::$interfacer;
	}
	
	/**
	 * @param ComhonObject $object
	 * @param string $operation
	 * @return integer
	 */
	protected function _saveObject(ComhonObject $object, $operation = null) {
		$this->_initDatabaseInterfacing($object->getModel());
		
		if (self::$modelInfos[$object->getModel()->getName()][self::HAS_INCR_ID_INDEX]) {
			return $this->_saveObjectWithIncrementalId($object);
		} else if ($operation == self::CREATE) {
			return $this->_insertObject($object);
		} else if ($operation == self::UPDATE) {
			return $this->_updateObject($object);
		} else {
			throw new \Exception('unknown operation '.$operation);
		}
	}
	
	/**
	 *
	 * @param ComhonObject $object
	 * @throws \Exception
	 * @return integer
	 */
	private function _saveObjectWithIncrementalId(ComhonObject $object) {
		if (!self::$modelInfos[$object->getModel()->getName()][self::HAS_INCR_ID_INDEX]) {
			throw new \Exception('operation not specified');
		}
		if ($object->hasCompleteId()) {
			return $this->_updateObject($object);
		} else {
			return $this->_insertObject($object);
		}
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @throws \Exception
	 * @return integer
	 */
	private function _insertObject(ComhonObject $object) {
		$interfacer = self::getInterfacer();
		$interfacer->setExportOnlyUpdatedValues(false);
		$mapOfString = $object->export($interfacer);
		if (!is_null($this->getInheritanceKey())) {
			$mapOfString[$this->getInheritanceKey()] = $object->getModel()->getName();
		}
		
		if (is_null($this->dbController->getInsertReturn())) {
			$query = 'INSERT INTO '.$this->settings->getValue('name').' ('.$this->_getSelectColumnString($mapOfString)
					.') VALUES ('.implode(', ', array_fill(0, count($mapOfString), '?')).');';
		}else if ($this->dbController->getInsertReturn() == 'RETURNING') {
			// TODO
			throw new \Exception('not supported yet');
		}else if ($this->dbController->getInsertReturn() == 'OUTPUT') {
			// TODO
			throw new \Exception('not supported yet');
		}
		$statement = $this->dbController->executeSimpleQuery($query, array_values($mapOfString));
		$affectedRows = $statement->rowCount();
		
		$autoIncrementProperties = self::$modelInfos[$object->getModel()->getName()][self::AUTO_INCR_PROPERTIES_INDEX];
		if (($affectedRows > 0) && !empty($autoIncrementProperties)) {
			if ($this->dbController->isSupportedLastInsertId()) {
				$incrementalValue = $object->getProperty($autoIncrementProperties[0])->getModel()->castValue($this->dbController->lastInsertId());
				$object->setValue($autoIncrementProperties[0], $incrementalValue, false);
			} else {
				// TODO manage sequence with return value
			}
		}
		return $affectedRows;
	}
	
	/**
	 * 
	 * @param [] $mapOfString
	 * @return string
	 */
	private function _getSelectColumnString($mapOfString) {
		$columnsToEscape = self::$columnsToEscape[$this->tableId];
		
		if (empty($columnsToEscape)) {
			return implode(', ', array_keys($mapOfString));
		} else {
			$columns = [];
			foreach ($mapOfString as $column => $string) {
				if (array_key_exists($column, $columnsToEscape)) {
					$columns[] = $columnsToEscape[$column];
				} else {
					$columns[] = $column;
				}
			}
			return implode(', ', $columns);
		}
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @throws \Exception
	 * @return integer
	 */
	private function _updateObject(ComhonObject $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new \Exception('update operation require complete id');
		}
		$model            = $object->getModel();
		$conditions       = [];
		$updates          = [];
		$updateValues     = [];
		$conditionsValues = [];

		$interfacer = self::getInterfacer();
		$interfacer->setExportOnlyUpdatedValues(true);
		$mapOfString = $object->export($interfacer);
		foreach ($object->getDeletedValues() as $propertyName) {
			$property = $model->getProperty($propertyName);
			if (!$property->isId() && !$property->isAggregation()) {
				$mapOfString[$property->getSerializationName()] = null;
			}
		}
		
		foreach ($object->getModel()->getIdProperties() as $idPropertyName => $idProperty) {
			$column = $idProperty->getSerializationName();
			$value  = $object->getValue($idPropertyName);
			if (is_null($value)) {
				throw new \Exception('update failed, id is not set');
			}
			unset($mapOfString[$column]);
			$conditions[]       = "$column = ?";
			$conditionsValues[] = $value;
		}
		if (empty($mapOfString) && !$object->isCasted()) {
			return 0;
		}
		if (!is_null($this->getInheritanceKey())) {
			$mapOfString[$this->getInheritanceKey()] = $object->getModel()->getName();
		}
		$columnsToEscape = self::$columnsToEscape[$this->tableId];
		foreach ($mapOfString as $column => $value) {
			if (array_key_exists($column, $columnsToEscape)) {
				$column   = $columnsToEscape[$column];
			}
			$updates[]      = "$column = ?";
			$updateValues[] = $value;
		}
		$query = 'UPDATE '.$this->settings->getValue('name').' SET '.implode(', ', $updates).' WHERE '.implode(' and ', $conditions).';';
		$statement = $this->dbController->executeSimpleQuery($query, array_merge($updateValues, $conditionsValues));
		
		return $statement->rowCount();
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @throws \Exception
	 * @return integer
	 */
	protected function _deleteObject(ComhonObject $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new \Exception('delete operation require complete id');
		}
		$this->_initDatabaseInterfacing($object->getModel());
		
		$model            = $object->getModel();
		$conditions       = [];
		$conditionsValues = [];
	
		foreach ($object->getModel()->getIdProperties() as $idPropertyName => $idProperty) {
			$column = $idProperty->getSerializationName();
			$value  = $object->getValue($idPropertyName);
			if (is_null($value)) {
				throw new \Exception('delete failed, id is not set');
			}
			$conditions[]       = "$column = ?";
			$conditionsValues[] = $value;
		}
		$query = 'DELETE FROM '.$this->settings->getValue('name').' WHERE '.implode(' and ', $conditions).';';
		$statement = $this->dbController->executeSimpleQuery($query, $conditionsValues);
		
		return $statement->rowCount();
	}
	
	/**
	 * @param ComhonObject $object
	 * @param string[] $propertiesFilter
	 * @return boolean
	 */
	protected function _loadObject(ComhonObject $object, $propertiesFilter = null) {
		$model         = $object->getModel();
		$conjunction   = new LogicalJunction(LogicalJunction::CONJUNCTION);
		$selectColumns = [];
		
		foreach ($model->getIdProperties() as $propertyName => $property) {
			$conjunction->addLiteral(new Literal($this->settings->getValue('name'), $property->getSerializationName(), '=', $object->getValue($propertyName)));
		}
		if (is_array($propertiesFilter)) {
			foreach ($propertiesFilter as $propertyName) {
				$selectColumns[] = $model->getProperty($propertyName, true)->getSerializationName();
			}
		}
		$return = $this->_loadObjectFromDatabase($object, $selectColumns, $conjunction, false);
		return $return;
	}
	
	public function loadAggregation(ObjectArray $object, $parentId, $aggregationProperties, $propertiesFilter = null) {
		$model         = $object->getModel()->getUniqueModel();
		$disjunction   = $this->getAggregationConditions($model, $parentId, $aggregationProperties);
		$selectColumns = [];
		
		if (count($disjunction->getLiterals()) == 0 && count($disjunction->getLogicalJunction()) == 0) {
			throw new \Exception('error : property is not serialized in database aggregation');
		}
		if (is_array($propertiesFilter)) {
			foreach ($propertiesFilter as $propertyName) {
				$selectColumns[] = $model->getProperty($propertyName, true)->getSerializationName();
			}
			if (!empty($selectColumns)) {
				foreach ($aggregationProperties as $aggregationProperty) {
					$property = $model->getProperty($aggregationProperty, true);
					if ($property->hasMultipleSerializationNames()) {
						foreach ($property->getMultipleIdProperties() as $serializationName => $multipleForeignProperty) {
							$selectColumns[] = $serializationName;
						}
					} else {
						$selectColumns[] = $property->getSerializationName();
					}
				}
				array_unique($selectColumns);
			}
		}
		return $this->_loadObjectFromDatabase($object, $selectColumns, $disjunction, false);
	}
	
	public function loadAggregationIds(ObjectArray $object, $parentId, $aggregationProperties) {
		$model         = $object->getModel()->getUniqueModel();
		$disjunction   = $this->getAggregationConditions($model, $parentId, $aggregationProperties);
		$selectColumns = [];
		$idProperties  = $model->getIdProperties();
		
		if (count($disjunction->getLiterals()) == 0 && count($disjunction->getLogicalJunction()) == 0) {
			throw new \Exception('error : property is not serialized in database aggregation');
		}
		if (empty($idProperties)) {
			throw new \Exception("cannot load aggregation ids, model '{$model->getName()}' doesn't have property id");
		}
		foreach ($idProperties as $property) {
			$selectColumns[] = $property->getSerializationName();
		}
		foreach ($aggregationProperties as $aggregationProperty) {
			$property = $model->getProperty($aggregationProperty, true);
			if ($property->hasMultipleSerializationNames()) {
				foreach ($property->getMultipleIdProperties() as $serializationName => $multipleForeignProperty) {
					$selectColumns[] = $serializationName;
				}
			} else {
				$selectColumns[] = $property->getSerializationName();
			}
		}
		return $this->_loadObjectFromDatabase($object, $selectColumns, $disjunction, true);
	}
	
	/**
	 * 
	 * @param ComhonObject $object
	 * @param string[] $selectColumns
	 * @param LogicalJunction $logicalJunction
	 * @param boolean $onlyIds used only for aggregation loading
	 * @return boolean
	 */
	private function _loadObjectFromDatabase(ComhonObject $object, $selectColumns, LogicalJunction $logicalJunction, $onlyIds) {
		$success = false;
		$this->_initDatabaseInterfacing($object->getModel());
		
		$selectQuery = new SelectQuery($this->settings->getValue('name'));
		$selectQuery->where($logicalJunction);
		
		if (!empty($selectColumns) && $object->getModel()->hasIdProperties()) {
			foreach ($object->getModel()->getIdProperties() as $property) {
				if (!in_array($property->getSerializationName(), $selectColumns)) {
					$selectQuery->getMainTable()->addSelectedColumn($property->getSerializationName());
				}
			}
		}
		foreach ($selectColumns as $column) {
			$selectQuery->getMainTable()->addSelectedColumn($column);
		}
		$rows = $this->dbController->executeSelectQuery($selectQuery);
		
		if ($object->getModel() instanceof ModelArray) {
			$isModelArray = true;
			self::castStringifiedColumns($rows, $object->getModel()->getUniqueModel());
		} else {
			$isModelArray = false;
			self::castStringifiedColumns($rows, $object->getModel());
		}
		
		if (is_array($rows) && ($isModelArray || (count($rows) == 1))) {
			if (!is_null($this->getInheritanceKey())) {
				if ($isModelArray) {
					$extendsModel = $object->getModel()->getUniqueModel();
					foreach ($rows as &$row) {
						$model = $this->getInheritedModel($row, $extendsModel);
						$row[Interfacer::INHERITANCE_KEY] = $model->getName();
					}
				} else {
					$model = $this->getInheritedModel($rows[0], $object->getModel());
					if ($model !== $object->getModel()) {
						$object->cast($model);
					}
				}
			}
			$interfacer = self::getInterfacer(!$onlyIds);
			$object->fill($isModelArray ? $rows : $rows[0], $interfacer);
			$success = true;
		}
		return $success;
	}
	
	public function getAggregationConditions($model, $parentId, $aggregationProperties) {
		
		$disjunction = new LogicalJunction(LogicalJunction::DISJUNCTION);
		foreach ($aggregationProperties as $aggregationProperty) {
			$property = $model->getProperty($aggregationProperty, true);
			if ($property->hasMultipleSerializationNames()) {
				$decodedId = json_decode($parentId);
				$conjunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
				foreach ($property->getMultipleIdProperties() as $serializationName => $multipleForeignProperty) {
					$conjunction->addLiteral(new Literal($this->settings->getValue('name'), $serializationName, '=', current($decodedId)));
					next($decodedId);
				}
				$disjunction->addLogicalJunction($conjunction);
			} else {
				$disjunction->addLiteral(new Literal($this->settings->getValue('name'), $property->getSerializationName(), '=', $parentId));
			}
		}
		return $disjunction;
	}
	
	/**
	 * @param array $value
	 * @param Model $extendsModel
	 * @return Model
	 */
	public function getInheritedModel($value, Model $extendsModel) {
		return array_key_exists($this->inheritanceKey, $value) && !is_null($value[$this->inheritanceKey]) 
				? ModelManager::getInstance()->getInstanceModel($value[$this->inheritanceKey]) : $extendsModel;
	}
	
	public static function castStringifiedColumns(&$rows, Model $model) {
		if (empty($rows)) {
			return;
		}
		$columnsToCast = self::_initColumnsToCast($model);
		$castIntegerColumns = [];
		$castFloatColumns   = [];
		$castBooleanColumns = [];
		foreach ($columnsToCast[self::INTEGER_INDEX] as $column) {
			if (isset($rows[0][$column])) {
				foreach ($rows as $row) {
					if (is_null($row[$column])) {
						continue;
					}
					if (is_string($row[$column])) {
						$castIntegerColumns[] = $column;
					}
					break;
				}
			}
		}
		foreach ($columnsToCast[self::FLOAT_INDEX] as $column) {
			if (isset($rows[0][$column])) {
				foreach ($rows as $row) {
					if (is_null($row[$column])) {
						continue;
					}
					if (is_string($row[$column])) {
						$castFloatColumns[] = $column;
					}
					break;
				}
			}
		}
		foreach ($columnsToCast[self::BOOLEAN_INDEX] as $column) {
			if (isset($rows[0][$column])) {
				foreach ($rows as $row) {
					if (is_null($row[$column])) {
						continue;
					}
					if (is_string($row[$column])) {
						$castBooleanColumns[] = $column;
					}
					break;
				}
			}
		}
		if (!empty($castIntegerColumns) || !empty($castFloatColumns) || !empty($castBooleanColumns)) {
			for ($i = 0; $i < count($rows); $i++) {
				foreach ($castIntegerColumns as $column) {
					if (is_numeric($rows[$i][$column])) {
						$rows[$i][$column] = (integer) $rows[$i][$column];
					}
				}
				foreach ($castFloatColumns as $column) {
					if (is_numeric($rows[$i][$column])) {
						$rows[$i][$column] = (float) $rows[$i][$column];
					}
				}
				foreach ($castBooleanColumns as $column) {
					$value = $rows[$i][$column];
					if ($value === '1' || $value === 't') {
						$rows[$i][$column]= true;
					} else if ($value === '0' || $value === 'f') {
						$rows[$i][$column]= false;
					}
				}
			}
		}
	}
	
}