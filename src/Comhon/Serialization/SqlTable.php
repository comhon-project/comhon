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
use Comhon\Logic\Clause;
use Comhon\Database\SelectQuery;
use Comhon\Logic\Literal;
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
use Comhon\Object\ObjectUnique;
use Comhon\Database\SimpleDbLiteral;
use Comhon\Exception\Database\NotSupportedDBMSException;
use Comhon\Exception\SerializationException;
use Comhon\Exception\ArgumentException;

class SqlTable extends SerializationUnit {
	
	/**
	 * @var integer index that store information that permit to know if model has incremantal id
	 */
	const HAS_INCR_ID_INDEX          = 0;
	
	/**
	 * @var integer index that store information that permit to know if model has incremantal properties
	 */
	const AUTO_INCR_PROPERTIES_INDEX = 1;
	
	/**
	 * @var integer index that store information that permit to know wich columns values need to be casted
	 */
	const COLUMS_TO_CAST_INDEX       = 2;
	
	/**
	 * @var integer index that store columns that have to be casted in integer
	 */
	const INTEGER_INDEX = 0;
	
	/**
	 * @var integer index that store columns that have to be casted in float
	 */
	const FLOAT_INDEX   = 1;
	
	/**
	 * @var integer index that store columns that have to be casted in boolean
	 */
	const BOOLEAN_INDEX = 2;
	
	/**
	 * @var DatabaseController
	 */
	private $dbController;
	
	/**
	 * @var string
	 */
	private $tableId;

	/**
	 * @var array store all incremental columns names grouped by table
	 */
	private static $autoIncrementColumns = [];
	
	/**
	 * @var array store all columns names grouped by table that have to be escaped
	 */
	private static $columnsToEscape = [];
	
	/**
	 * @var array store table informations group by model
	 */
	private static $modelInfos = [];
	
	/**
	 * @var \Comhon\Interfacer\AssocArrayInterfacer interfacer able to read retrieved rows from database
	 */
	private static $interfacer;
	
	
	/**
	 * initialize interfacing between specified model and sql table
	 * 
	 * @param \Comhon\Model\Model $model
	 */
	private function _initDatabaseInterfacing(Model $model) {
		if (is_null($this->dbController)) {
			$this->settings->loadValue('database');
			$this->tableId = $this->settings->getValue('name').'_'.$this->settings->getValue('database')->getValue('id');
			$this->dbController = DatabaseController::getInstanceWithDataBaseObject($this->settings->getValue('database'));
			$this->_initColumnsInfos();
		}
		$this->_initColumnsProperties($model);
	}
	
	/**
	 * retrieve and store columns informations of table (auto incremental columns and columns to escape)
	 */
	private function _initColumnsInfos() {
		if (!array_key_exists($this->tableId, self::$autoIncrementColumns)) {
			list(self::$autoIncrementColumns[$this->tableId], self::$columnsToEscape[$this->tableId]) = $this->_getSpecificColumns();
		}
	}
	
	/**
	 * get table id
	 * 
	 * @return string
	 */
	private function _getTableId() {
		return $this->settings->getValue('name').'_'.$this->settings->getValue('database')->getValue('id');
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * 
	 * @return [string[],string[]]
	 */
	private function _getSpecificColumns() {
	switch ($this->settings->getValue('database')->getValue('DBMS')) {
			case 'mysql': return $this->_getSpecificColumnsMySql();
			case 'pgsql': return $this->_getSpecificColumnsPgSql();
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
			default: throw new NotSupportedDBMSException($this->settings->getValue('database')->getValue('DBMS'));
		}
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * 
	 * @return [string[],string[]]
	 */
	private function _getSpecificColumnsMySql() {
		$DBMS = $this->settings->getValue('database')->getValue('DBMS');
		$autoIncrementColumns = [];
		$columnsToEscape = [];
		
		$query = 'SHOW COLUMNS FROM '.$this->settings->getValue('name');
		$result = $this->dbController->executeSimpleQuery($query)->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($result as $row) {
			if ($row['Extra'] === 'auto_increment') {
				$autoIncrementColumns[] = $row['Field'];
			}
			if (SqlUtils::isReservedWorld($DBMS, $row['Field'])) {
				$columnsToEscape[$row['Field']] = "`{$row['Field']}`";
			}
		}
		return [$autoIncrementColumns, $columnsToEscape];
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * 
	 * @return [string[],string[]]
	 */
	private function _getSpecificColumnsPgSql() {
		$DBMS = $this->settings->getValue('database')->getValue('DBMS');
		$autoIncrementColumns = [];
		$columnsToEscape = [];
		
		$explodedTable = explode('.', $this->settings->getValue('name'));
		if (count($explodedTable) == 1) {
			$schema = 'public';
			$table  = $explodedTable[0];
		} else if (count($explodedTable) == 2) {
			$schema = $explodedTable[0];
			$table  = $explodedTable[1];
		} else {
			throw new SerializationException('doesn\'t manage table names that contain \'.\' character');
		}
	
		$query = "SELECT column_name, column_default FROM information_schema.columns 
		WHERE table_schema='$schema' AND table_name='$table';";
		$rows = $this->dbController->executeSimpleQuery($query)->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($rows as $row) {
			if ($row['column_default'] !== null && strpos($row['column_default'], 'nextval(') === 0) {
				$autoIncrementColumns[] = $row['column_name'];
				break;
			}
			if (SqlUtils::isReservedWorld($DBMS, $row['column_name'])) {
				$columnsToEscape[$row['column_name']] = "\"{$row['column_name']}\"";
			}
		}
		return [$autoIncrementColumns, $columnsToEscape];
	}
	
	/**
	 * store properties that are binded to specific columns (incremental columns and columns that have to be casted)
	 * 
	 * @param \Comhon\Model\Model $model
	 */
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
					$autoIncrementProperties[$property->getSerializationName()] = $property;
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
	
	/**
	 * store columns that have to be casted according specified model
	 * 
	 * @param \Comhon\Model\Model $model
	 * @return [string[], string[], string[]]
	 */
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
	
	/**
	 * get interfacer able to read retrieved rows from database
	 * 
	 * @param string $flagObjectAsLoaded if true flag imported comhon object as loaded
	 * @return \Comhon\Interfacer\AssocArrayInterfacer
	 */
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
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_saveObject()
	 */
	protected function _saveObject(ObjectUnique $object, $operation = null) {
		$this->_initDatabaseInterfacing($object->getModel());
		
		if (self::$modelInfos[$object->getModel()->getName()][self::HAS_INCR_ID_INDEX]) {
			return $this->_saveObjectWithIncrementalId($object);
		} else if ($operation == self::CREATE) {
			return $this->_insertObject($object);
		} else if ($operation == self::UPDATE) {
			return $this->_updateObject($object);
		} else {
			throw new ArgumentException($operation, [self::CREATE, self::UPDATE], 2);
		}
	}
	
	/**
	 * save object that have model with unique id property binded to incremental column
	 *
	 * @param \Comhon\Object\ObjectUnique $object
	 * @throws \Exception
	 * @return integer
	 */
	private function _saveObjectWithIncrementalId(ObjectUnique $object) {
		if (!self::$modelInfos[$object->getModel()->getName()][self::HAS_INCR_ID_INDEX]) {
			throw new SerializationException('operation not specified');
		}
		if ($object->hasCompleteId()) {
			return $this->_updateObject($object);
		} else {
			return $this->_insertObject($object);
		}
	}
	
	/**
	 * execute insert query to save comhon object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @throws \Exception
	 * @return integer number of affected rows
	 */
	private function _insertObject(ObjectUnique $object) {
		$interfacer = self::getInterfacer();
		$interfacer->setExportOnlyUpdatedValues(false);
		$mapOfString = $object->export($interfacer);
		if (!is_null($this->getInheritanceKey())) {
			$mapOfString[$this->getInheritanceKey()] = $object->getModel()->getName();
		}
		
		$query = 'INSERT INTO '.$this->settings->getValue('name').' ('.$this->_getSelectColumnString($mapOfString)
				.') VALUES ('.implode(', ', array_fill(0, count($mapOfString), '?')).')';
		if (is_null($this->dbController->getInsertReturn())) {
			$queryEnding = ';';
		}else if ($this->dbController->getInsertReturn() == 'RETURNING') {
			$autoIncrementProperties = self::$modelInfos[$object->getModel()->getName()][self::AUTO_INCR_PROPERTIES_INDEX];
			if (count($autoIncrementProperties) == 0) {
				$queryEnding = ';';
			} elseif (count($autoIncrementProperties) == 1) {
				$queryEnding = ' RETURNING ' . current($autoIncrementProperties)->getSerializationName() . ';';
			} else {
				$returns = [];
				foreach ($autoIncrementProperties as $autoIncrementProperty) {
					$returns[] = $autoIncrementProperty->getSerializationName();
				}
				$queryEnding = ' RETURNING ' . implode(',', $returns) . ';';
			}
		}else if ($this->dbController->getInsertReturn() == 'OUTPUT') {
			// TODO
			throw new SerializationException('not supported yet');
		}
		$query .= $queryEnding;
		$statement = $this->dbController->executeSimpleQuery($query, array_values($mapOfString));
		$affectedRows = $statement->rowCount();
		
		$autoIncrementProperties = self::$modelInfos[$object->getModel()->getName()][self::AUTO_INCR_PROPERTIES_INDEX];
		if (($affectedRows > 0) && !empty($autoIncrementProperties)) {
			if ($this->dbController->isSupportedLastInsertId()) {
				$incrementalValue = current($autoIncrementProperties)->getModel()->castValue($this->dbController->lastInsertId());
				$object->setValue(current($autoIncrementProperties)->getName(), $incrementalValue, false);
			} elseif (!is_null($this->dbController->getInsertReturn()) && (count($autoIncrementProperties) > 0)) {
				$rows = $statement->fetchAll();
				foreach ($autoIncrementProperties as $column => $autoIncrementProperty) {
					if (!array_key_exists($column, $rows[0])) {
						throw new SerializationException("error insert return, should contain column '$column'");
					}
					$object->setValue($autoIncrementProperty->getName(), $rows[0][$column], false);
				}
			}
		}
		return $affectedRows;
	}
	
	/**
	 * get stringified columns
	 * 
	 * escape columns if needed
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
	 * execute update query to save comhon object
	 * 
	 * @param \Comhon\Object\ObjectUnique $object
	 * @throws \Exception
	 * @return integer number of affected rows
	 */
	private function _updateObject(ObjectUnique $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new SerializationException('update operation require complete id');
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
				throw new SerializationException('update failed, id is not set');
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
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_deleteObject()
	 */
	protected function _deleteObject(ObjectUnique $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new SerializationException('delete operation require complete id');
		}
		$this->_initDatabaseInterfacing($object->getModel());
		
		$model            = $object->getModel();
		$conditions       = [];
		$conditionsValues = [];
	
		foreach ($object->getModel()->getIdProperties() as $idPropertyName => $idProperty) {
			$column = $idProperty->getSerializationName();
			$value  = $object->getValue($idPropertyName);
			if (is_null($value)) {
				throw new SerializationException('delete failed, id is not set');
			}
			$conditions[]       = "$column = ?";
			$conditionsValues[] = $value;
		}
		$query = 'DELETE FROM '.$this->settings->getValue('name').' WHERE '.implode(' and ', $conditions).';';
		$statement = $this->dbController->executeSimpleQuery($query, $conditionsValues);
		
		return $statement->rowCount();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(ObjectUnique $object, $propertiesFilter = null) {
		$model         = $object->getModel();
		$conjunction   = new Clause(Clause::CONJUNCTION);
		$selectColumns = [];
		
		foreach ($model->getIdProperties() as $propertyName => $property) {
			$conjunction->addLiteral(new SimpleDbLiteral($this->settings->getValue('name'), $property->getSerializationName(), Literal::EQUAL, $object->getValue($propertyName)));
		}
		if (is_array($propertiesFilter)) {
			foreach ($propertiesFilter as $propertyName) {
				$selectColumns[] = $model->getProperty($propertyName, true)->getSerializationName();
			}
		}
		$return = $this->_loadObjectFromDatabase($object, $selectColumns, $conjunction, false);
		return $return;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::loadAggregation()
	 */
	public function loadAggregation(ObjectArray $object, $parentId, $aggregationProperties, $propertiesFilter = null) {
		$model         = $object->getModel()->getUniqueModel();
		$disjunction   = $this->getAggregationConditions($model, $parentId, $aggregationProperties);
		$selectColumns = [];
		
		if (count($disjunction->getElements()) == 0) {
			throw new SerializationException('property is not serialized as database aggregation');
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
	
	/**
	 * load aggregation ids from database according parent id
	 * 
	 * @param \Comhon\Object\ObjectArray $object
	 * @param integer|string $parentId
	 * @param string[] $aggregationProperties
	 * @throws \Exception
	 * @return boolean
	 */
	public function loadAggregationIds(ObjectArray $object, $parentId, $aggregationProperties) {
		$model         = $object->getModel()->getUniqueModel();
		$disjunction   = $this->getAggregationConditions($model, $parentId, $aggregationProperties);
		$selectColumns = [];
		$idProperties  = $model->getIdProperties();
		
		if (count($disjunction->getElements()) == 0) {
			throw new SerializationException('property is not serialized as database aggregation');
		}
		if (empty($idProperties)) {
			throw new SerializationException("cannot load aggregation ids, model '{$model->getName()}' doesn't have property id");
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
	 * load specified comhon object according logical junction
	 * 
	 * @param \Comhon\Object\ComhonObject $object
	 * @param string[] $selectColumns
	 * @param \Comhon\Logic\Clause $clause
	 * @param boolean $onlyIds used only for aggregation loading
	 * @return boolean
	 */
	private function _loadObjectFromDatabase(ComhonObject $object, $selectColumns, Clause $clause, $onlyIds) {
		$success = false;
		$this->_initDatabaseInterfacing($object->getModel());
		
		$selectQuery = new SelectQuery($this->settings->getValue('name'));
		$selectQuery->where($clause);
		
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
					$baseModel = $object->getModel()->getUniqueModel();
					foreach ($rows as &$row) {
						$model = $this->getInheritedModel($row, $baseModel);
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
	
	/**
	 * get conditions that have to be added to query to retrieve aggregation
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param integer|string $parentId
	 * @param string[] $aggregationProperties
	 * @return \Comhon\Logic\Clause
	 */
	public function getAggregationConditions(Model $model, $parentId, $aggregationProperties) {
		
		$disjunction = new Clause(Clause::DISJUNCTION);
		foreach ($aggregationProperties as $aggregationProperty) {
			$property = $model->getProperty($aggregationProperty, true);
			if ($property->hasMultipleSerializationNames()) {
				$decodedId = json_decode($parentId);
				$conjunction = new Clause(Clause::CONJUNCTION);
				foreach ($property->getMultipleIdProperties() as $serializationName => $multipleForeignProperty) {
					$conjunction->addLiteral(new SimpleDbLiteral($this->settings->getValue('name'), $serializationName, Literal::EQUAL, current($decodedId)));
					next($decodedId);
				}
				$disjunction->addClause($conjunction);
			} else {
				$disjunction->addLiteral(new SimpleDbLiteral($this->settings->getValue('name'), $property->getSerializationName(), Literal::EQUAL, $parentId));
			}
		}
		return $disjunction;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::getInheritedModel()
	 */
	public function getInheritedModel($value, Model $baseModel) {
		return array_key_exists($this->inheritanceKey, $value) && !is_null($value[$this->inheritanceKey]) 
				? ModelManager::getInstance()->getInstanceModel($value[$this->inheritanceKey]) : $baseModel;
	}
	
	/**
	 * cast values retrieved from table in right type
	 * 
	 * @param mixed[] $rows
	 * @param \Comhon\Model\Model $model
	 */
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