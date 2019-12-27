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

use Comhon\Database\DatabaseHandler;
use Comhon\Logic\Clause;
use Comhon\Database\SelectQuery;
use Comhon\Logic\Literal;
use Comhon\Model\ModelArray;
use Comhon\Object\ComhonArray;
use Comhon\Model\Model;
use Comhon\Object\AbstractComhonObject;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Object\UniqueObject;
use Comhon\Database\SimpleDbLiteral;
use Comhon\Exception\Database\NotSupportedDBMSException;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\Database\QueryExecutionFailureException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Serialization\UniqueException;
use Comhon\Exception\Serialization\ForeignValueException;
use Comhon\Exception\Serialization\NotNullException;
use Comhon\Interfacer\Interfacer;

class SqlTable extends ValidatedSerializationUnit {
	
	/** 
	 * @var string sql serialization type
	 */
	const SETTINGS_TYPE = 'Comhon\SqlTable';
	
	/**
	 * @var integer index that store information that permit to know if model has incremantal id
	 */
	const HAS_INCR_ID_INDEX = 0;
	
	/**
	 * @var integer index that store information that permit to know if model has incremantal properties
	 */
	const AUTO_INCR_PROPERTIES_INDEX = 1;
	
	/**
	 * @var array store all incremental columns names grouped by table
	 */
	private $autoIncrementColumns = [];
	
	/**
	 * @var array store table informations group by model
	 */
	private $modelInfos = [];
	
	/**
	 * @var \Comhon\Interfacer\AssocArrayInterfacer interfacer able to read retrieved rows from database
	 */
	private $interfacer;
	
	/**
	 * @var \Comhon\Serialization\File\XmlFile
	 */
	private static $instance;
	
	/**
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::getInstance()
	 *
	 * @return \Comhon\Serialization\SqlTable
	 */
	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * get serialization unit type
	 * 
	 * @return string
	 */
	public static function getType() {
		return self::SETTINGS_TYPE;
	}
	
	/**
	 * initialize interfacing between specified model and sql table
	 * 
	 * @param \Comhon\Model\Model $model
	 */
	private function _initDatabaseInterfacing(Model $model) {
		$settings = $model->getSerializationSettings();
		if (is_null($settings)) {
			throw new SerializationException("model '{$model->getName()}' doesn't have serialization");
		}
		
		$databaseId = $settings->getValue('database')->getId();
		$tableName = $settings->getValue('name');
		$tableId = $tableName. '_' . $databaseId;
		$this->_initColumnsInfos($databaseId, $tableId, $tableName);
		$this->_initColumnsProperties($model, $tableId);
	}
	
	/**
	 * retrieve and store columns informations of table (auto incremental columns)
	 * 
	 * @param string $databaseId
	 * @param string $tableId
	 * @param string $tableName
	 */
	private function _initColumnsInfos($databaseId, $tableId, $tableName) {
		if (!array_key_exists($tableId, $this->autoIncrementColumns)) {
			$dbHandler = DatabaseHandler::getInstanceWithDataBaseId($databaseId);
			$this->autoIncrementColumns[$tableId] = $this->_getIncrementalColumns($dbHandler, $tableName);
		}
	}
	
	/**
	 * get auto incremental columns
	 * 
	 * @param \Comhon\Database\DatabaseHandler $dbHandler
	 * @param string $tableName
	 * @return string[]
	 */
	private function _getIncrementalColumns($dbHandler, $tableName) {
		switch ($dbHandler->getDBMS()) {
			case 'mysql': return $this->_getIncrementalColumnsMySql($dbHandler, $tableName);
			case 'pgsql': return $this->_getIncrementalColumnsPgSql($dbHandler, $tableName);
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
			default: throw new NotSupportedDBMSException($dbHandler->getDBMS());
		}
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * 
	 * @param \Comhon\Database\DatabaseHandler $dbHandler
	 * @param string $tableName
	 * @return string[]
	 */
	private function _getIncrementalColumnsMySql($dbHandler, $tableName) {
		$autoIncrementColumns = [];
		
		$query = 'SHOW COLUMNS FROM ' . $tableName;
		$result = $dbHandler->execute($query)->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($result as $row) {
			if ($row['Extra'] === 'auto_increment') {
				$autoIncrementColumns[] = $row['Field'];
			}
		}
		return $autoIncrementColumns;
	}
	
	/**
	 * get auto incremental columns and columns to escape
	 * 
	 * @param \Comhon\Database\DatabaseHandler $dbHandler
	 * @param string $tableName
	 * @return string[]
	 */
	private function _getIncrementalColumnsPgSql($dbHandler, $tableName) {
		$autoIncrementColumns = [];
		
		$explodedTable = explode('.', $tableName);
		if (count($explodedTable) == 1) {
			$schema = 'public';
			$table  = $explodedTable[0];
		} else if (count($explodedTable) == 2) {
			$schema = $explodedTable[0];
			$table  = $explodedTable[1];
		} else {
			throw new SerializationException('doesn\'t manage table names that contain \'.\' character');
		}
		if (strpos($schema, '"') === 0) {
			$schema = substr($schema, 1, -1);
		}
		if (strpos($table, '"') === 0) {
			$table= substr($table, 1, -1);
		}
	
		$query = "SELECT column_name, column_default FROM information_schema.columns 
		WHERE table_schema='$schema' AND table_name='$table';";
		$rows = $dbHandler->execute($query)->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($rows as $row) {
			if ($row['column_default'] !== null && strpos($row['column_default'], 'nextval(') === 0) {
				$autoIncrementColumns[] = $row['column_name'];
				break;
			}
		}
		return $autoIncrementColumns;
	}
	
	/**
	 * store properties that are binded to specific columns (incremental columns and columns that have to be casted)
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $tableId
	 */
	private function _initColumnsProperties(Model $model, $tableId) {
		if (array_key_exists($model->getName(), $this->modelInfos)) {
			return;
		}
		$autoIncrementProperties = [];
		$hasIncrementalId = false;
		
		$autoIncrementColumns = $this->autoIncrementColumns[$tableId];
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
		
		$this->modelInfos[$model->getName()] = [
			self::HAS_INCR_ID_INDEX          => $hasIncrementalId,
			self::AUTO_INCR_PROPERTIES_INDEX => $autoIncrementProperties
		];
	}
	
	public function hasIncrementalId(Model $model) {
		$this->_initDatabaseInterfacing($model);
		return $this->modelInfos[$model->getName()][self::HAS_INCR_ID_INDEX];
	}
	
	/**
	 * get interfacer able to read retrieved rows from database
	 * 
	 * @param string $flagObjectAsLoaded if true flag imported comhon object as loaded
	 * @return \Comhon\Interfacer\AssocArrayInterfacer
	 */
	public function getInterfacer($flagObjectAsLoaded = true) {
		if (is_null($this->interfacer)) {
			$this->interfacer = new AssocArrayInterfacer();
			$this->interfacer->setPrivateContext(true);
			$this->interfacer->setSerialContext(true);
			$this->interfacer->setFlagValuesAsUpdated(false);
			$this->interfacer->setDateTimeFormat('Y-m-d H:i:s');
			$this->interfacer->setDateTimeZone(Config::getInstance()->getDataBaseTimezone());
			$this->interfacer->setFlattenValues(true);
			$this->interfacer->setStringifiedValues(true);
			$this->interfacer->setMergeType(Interfacer::OVERWRITE);
		}
		$this->interfacer->setFlagObjectAsLoaded($flagObjectAsLoaded);
		return $this->interfacer;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_saveObject()
	 */
	protected function _saveObject(UniqueObject $object, $operation = null) {
		$this->_initDatabaseInterfacing($object->getModel());
		
		if ($this->modelInfos[$object->getModel()->getName()][self::HAS_INCR_ID_INDEX]) {
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
	 * @param \Comhon\Object\UniqueObject $object
	 * @throws \Exception
	 * @return integer
	 */
	private function _saveObjectWithIncrementalId(UniqueObject $object) {
		if (!$this->modelInfos[$object->getModel()->getName()][self::HAS_INCR_ID_INDEX]) {
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
	 * @param \Comhon\Object\UniqueObject $object
	 * @throws \Exception
	 * @return integer number of affected rows
	 */
	private function _insertObject(UniqueObject $object) {
		$interfacer = $this->getInterfacer();
		$interfacer->setExportOnlyUpdatedValues(false);
		$mapOfString = $object->export($interfacer);
		$databaseId = $object->getModel()->getSerializationSettings()->getValue('database')->getId();
		$databaseHandler = DatabaseHandler::getInstanceWithDataBaseId($databaseId);
		
		$query = 'INSERT INTO '.$object->getModel()->getSerializationSettings()->getValue('name').' ('.$this->_getSelectColumnString($mapOfString, $databaseHandler)
				.') VALUES ('.implode(', ', array_fill(0, count($mapOfString), '?')).')';
		if (is_null($databaseHandler->getInsertReturn())) {
			$queryEnding = ';';
		}else if ($databaseHandler->getInsertReturn() == 'RETURNING') {
			$autoIncrementProperties = $this->modelInfos[$object->getModel()->getName()][self::AUTO_INCR_PROPERTIES_INDEX];
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
		}else if ($databaseHandler->getInsertReturn() == 'OUTPUT') {
			// TODO
			throw new SerializationException('not supported yet');
		}
		$query .= $queryEnding;
		
		$statement = $this->execute($databaseHandler, $query, array_values($mapOfString), $object);
		$affectedRows = $statement->rowCount();
		
		$autoIncrementProperties = $this->modelInfos[$object->getModel()->getName()][self::AUTO_INCR_PROPERTIES_INDEX];
		if (($affectedRows > 0) && !empty($autoIncrementProperties)) {
			if ($databaseHandler->isSupportedLastInsertId()) {
				$incrementalValue = current($autoIncrementProperties)->getModel()->castValue($databaseHandler->lastInsertId());
				$object->setValue(current($autoIncrementProperties)->getName(), $incrementalValue, false);
			} elseif (!is_null($databaseHandler->getInsertReturn()) && (count($autoIncrementProperties) > 0)) {
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
	 * @param \Comhon\Database\DatabaseHandler $dbHandler
	 * @return string
	 */
	private function _getSelectColumnString($mapOfString, $dbHandler) {
		if (empty($mapOfString)) {
			return '';
		} else {
			$esc = $dbHandler->getEscapeChar();
			return $esc . implode($esc . ', ' . $esc, array_keys($mapOfString)) . $esc;
		}
	}
	
	/**
	 * execute update query to save comhon object
	 * 
	 * @param \Comhon\Object\UniqueObject $object
	 * @throws \Exception
	 * @return integer number of affected rows
	 */
	private function _updateObject(UniqueObject $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new SerializationException('update operation require complete id');
		}
		$model            = $object->getModel();
		$databaseId       = $model->getSerializationSettings()->getValue('database')->getId();
		$databaseHandler  = DatabaseHandler::getInstanceWithDataBaseId($databaseId);
		$esc              = $databaseHandler->getEscapeChar();
		$conditions       = [];
		$updates          = [];
		$updateValues     = [];
		$conditionsValues = [];

		$interfacer = $this->getInterfacer();
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
			$conditions[]       = "{$esc}$column{$esc}= ?";
			$conditionsValues[] = $value;
		}
		if (empty($mapOfString) && !$object->isCasted()) {
			return 0;
		}
		foreach ($mapOfString as $column => $value) {
			$updates[]      = "{$esc}$column{$esc}= ?";
			$updateValues[] = $value;
		}
		$query = 'UPDATE '.$object->getModel()->getSerializationSettings()->getValue('name').' SET '.implode(', ', $updates).' WHERE '.implode(' and ', $conditions).';';
		$statement = $this->execute($databaseHandler, $query, array_merge($updateValues, $conditionsValues), $object);
		
		return $statement->rowCount();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_deleteObject()
	 */
	protected function _deleteObject(UniqueObject $object) {
		if (!$object->getModel()->hasIdProperties() || !$object->hasCompleteId()) {
			throw new SerializationException('delete operation require complete id');
		}
		$this->_initDatabaseInterfacing($object->getModel());
		
		$model            = $object->getModel();
		$databaseId       = $model->getSerializationSettings()->getValue('database')->getId();
		$databaseHandler  = DatabaseHandler::getInstanceWithDataBaseId($databaseId);
		$esc              = $databaseHandler->getEscapeChar();
		$conditions       = [];
		$conditionsValues = [];
	
		foreach ($object->getModel()->getIdProperties() as $idPropertyName => $idProperty) {
			$column = $idProperty->getSerializationName();
			$value  = $object->getValue($idPropertyName);
			if (is_null($value)) {
				throw new SerializationException('delete failed, id is not set');
			}
			$conditions[]       = "{$esc}$column{$esc}= ?";
			$conditionsValues[] = $value;
		}
		$query = 'DELETE FROM '.$object->getModel()->getSerializationSettings()->getValue('name').' WHERE '.implode(' and ', $conditions).';';
		$statement = $databaseHandler->execute($query, $conditionsValues);
		
		return $statement->rowCount();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::_loadObject()
	 */
	protected function _loadObject(UniqueObject $object, $propertiesFilter = null) {
		$model         = $object->getModel();
		$conjunction   = new Clause(Clause::CONJUNCTION);
		$selectColumns = [];
		
		foreach ($model->getIdProperties() as $propertyName => $property) {
			$conjunction->addLiteral(new SimpleDbLiteral($object->getModel()->getSerializationSettings()->getValue('name'), $property->getSerializationName(), Literal::EQUAL, $object->getValue($propertyName)));
		}
		if (is_array($propertiesFilter)) {
			foreach ($propertiesFilter as $propertyName) {
				$selectColumns[] = $model->getProperty($propertyName, true)->getSerializationName();
			}
		}
		if (!empty($selectColumns) && $model->getSerialization()->getInheritanceKey()) {
			$selectColumns[] = $model->getSerialization()->getInheritanceKey();
		}
		return $this->_loadObjectFromDatabase($object, $selectColumns, $conjunction, false);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \Comhon\Serialization\SerializationUnit::loadAggregation()
	 */
	public function loadAggregation(ComhonArray $object, $parentId, $aggregationProperties, $propertiesFilter = null) {
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
	 * @param \Comhon\Object\ComhonArray $object
	 * @param integer|string $parentId
	 * @param string[] $aggregationProperties
	 * @throws \Exception
	 * @return boolean
	 */
	public function loadAggregationIds(ComhonArray $object, $parentId, $aggregationProperties) {
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
	 * @param \Comhon\Object\AbstractComhonObject $object
	 * @param string[] $selectColumns
	 * @param \Comhon\Logic\Clause $clause
	 * @param boolean $onlyIds used only for aggregation loading
	 * @return boolean
	 */
	private function _loadObjectFromDatabase(AbstractComhonObject $object, $selectColumns, Clause $clause, $onlyIds) {
		$success = false;
		$uniqueModel = ($object->getModel() instanceof ModelArray) ? $object->getUniqueModel() : $object->getModel();
		$this->_initDatabaseInterfacing($uniqueModel);
		
		$selectQuery = new SelectQuery($uniqueModel->getSerializationSettings()->getValue('name'));
		$selectQuery->where($clause);
		
		if (!empty($selectColumns) && $uniqueModel->hasIdProperties()) {
			foreach ($uniqueModel->getIdProperties() as $property) {
				if (!in_array($property->getSerializationName(), $selectColumns)) {
					$selectQuery->getMainTable()->addSelectedColumn($property->getSerializationName());
				}
			}
		}
		foreach ($selectColumns as $column) {
			$selectQuery->getMainTable()->addSelectedColumn($column);
		}
		
		$databaseId = $uniqueModel->getSerializationSettings()->getValue('database')->getId();
		$databaseHandler = DatabaseHandler::getInstanceWithDataBaseId($databaseId);
		$rows = $databaseHandler->select($selectQuery);
		
		$isModelArray = $object->getModel() instanceof ModelArray;
		if (is_array($rows) && ($isModelArray || (count($rows) == 1))) {
			$interfacer = $this->getInterfacer(!$onlyIds);
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
	private function getAggregationConditions(Model $model, $parentId, $aggregationProperties) {
		$settings = $model->getSerializationSettings();
		if (is_null($settings)) {
			throw new SerializationException("model '{$model->getName()}' doesn't have serialization");
		}
		$disjunction = new Clause(Clause::DISJUNCTION);
		foreach ($aggregationProperties as $aggregationProperty) {
			$property = $model->getProperty($aggregationProperty, true);
			if ($property->hasMultipleSerializationNames()) {
				$decodedId = json_decode($parentId);
				$conjunction = new Clause(Clause::CONJUNCTION);
				foreach ($property->getMultipleIdProperties() as $serializationName => $multipleForeignProperty) {
					$conjunction->addLiteral(new SimpleDbLiteral($settings->getValue('name'), $serializationName, Literal::EQUAL, current($decodedId)));
					next($decodedId);
				}
				$disjunction->addClause($conjunction);
			} else {
				$disjunction->addLiteral(new SimpleDbLiteral($settings->getValue('name'), $property->getSerializationName(), Literal::EQUAL, $parentId));
			}
		}
		return $disjunction;
	}
	
	/**
	 * 
	 * @param DatabaseHandler $databaseHandler
	 * @param string $query
	 * @param array $values
	 * @param UniqueObject $object
	 * @return \PDOStatement
	 */
	private function execute(DatabaseHandler $databaseHandler, $query, $values, UniqueObject $object) {
		try {
			return $databaseHandler->execute($query, $values);
		} catch (QueryExecutionFailureException $e) {
			$PDOStatement = $e->getPDOStatement();
			if (!is_null($PDOStatement)) {
				switch ($databaseHandler->getDBMS()) {
					case DatabaseHandler::MYSQL:
						$this->interfaceMySqlError($PDOStatement, $object);
						break;
					case DatabaseHandler::PGSQL:
						$this->interfacePgSqlError($PDOStatement, $object);
						break;
					default:
						throw new ComhonException('dbsm not managed : ' . $databaseHandler->getDBMS());
				}
			}
			throw $e;
		}
	}
	
	/**
	 * 
	 * @param \PDOStatement $PDOStatement
	 * @param UniqueObject $object
	 * @throws UniqueException
	 * @throws NotNullException
	 * @throws ForeignValueException
	 */
	private function interfaceMySqlError(\PDOStatement $PDOStatement, UniqueObject $object) {
		$infos = $PDOStatement->errorInfo();
		switch ($infos[1]) {
			case 1048:
				$res = preg_match('/Column \'([^\']+)\' cannot be null/', $infos[2], $matches);
				if ($res === 1) {
					$propertyNames = $this->getPropertiesNamesFromSqlMessage($object->getModel(), $matches[1]);
					throw new NotNullException($object->getModel(), $propertyNames[0]);
				}
				break;
			case 1062:
				$res = preg_match('/\'(.+)\' for key \'(.+)\'/', $infos[2], $matches);
				if ($res === 1) {
					$propertyNames = $this->getPropertiesNamesFromSqlMessage($object->getModel(), $matches[2]);
					throw new UniqueException($object, $propertyNames);
				}
				break;
			case 1364:
				$res = preg_match('/Field \'([^\']+)\' doesn\'t have a default value/', $infos[2], $matches);
				if ($res === 1) {
					$propertyNames = $this->getPropertiesNamesFromSqlMessage($object->getModel(), $matches[1]);
					throw new NotNullException($object->getModel(), $propertyNames[0]);
				}
				break;
			case 1452:
				$res = preg_match('/FOREIGN KEY \\(((?:`?[^`\\(\\)]+`?, )*`?[^`\\(\\)]+`?)\\) REFERENCES/', $infos[2], $matches);
				if ($res === 1) {
					$propertyNames = $this->getPropertiesNamesFromSqlMessage($object->getModel(), $matches[1]);
					throw new ForeignValueException($object, $propertyNames[0]);
				}
				break;
		}
	}
	
	/**
	 * 
	 * @param \PDOStatement $PDOStatement
	 * @param UniqueObject $object
	 * @throws NotNullException
	 * @throws ForeignValueException
	 * @throws UniqueException
	 */
	private function interfacePgSqlError(\PDOStatement $PDOStatement, UniqueObject $object) {
		$infos = $PDOStatement->errorInfo();
		switch ($infos[0]) {
			case '23502':
				$res = preg_match('/null value in column "([^"]+)" violates not-null constraint/', $infos[2], $matches);
				if ($res === 1) {
					$propertyNames = $this->getPropertiesNamesFromSqlMessage($object->getModel(), $matches[1]);
					throw new NotNullException($object->getModel(), $propertyNames[0]);
				}
				break;
			case '23503':
				$res = preg_match('/\\((.+)\\)=\\((.+)\\)/', $infos[2], $matches);
				if ($res === 1) {
					$propertyNames = $this->getPropertiesNamesFromSqlMessage($object->getModel(), $matches[1]);
					throw new ForeignValueException($object, $propertyNames[0]);
				}
				break;
			case '23505':
				$res = preg_match('/\\((.+)\\)=\\(.+\\)/', $infos[2], $matches);
				if ($res === 1) {
					$propertyNames = $this->getPropertiesNamesFromSqlMessage($object->getModel(), $matches[1]);
					throw new UniqueException($object, $propertyNames);
				}
				break;
		}
	}
	
	/**
	 *
	 * @param \Comhon\Model\Model $model
	 * @param string $messageColumns
	 * @return string[]
	 */
	private function getPropertiesNamesFromSqlMessage(Model $model, $messageColumns) {
		$columns = explode(', ', $messageColumns);
		$propertiesNames = [];
		foreach ($columns as $column) {
			if ($column[0] === '`') {
				$column = substr($column, 1, -1);
			}
			foreach ($model->getProperties() as $property) {
				if ($property->hasMultipleSerializationNames()) {
					foreach ($property->getMultipleIdProperties() as $seriakizationName => $idProperty) {
						if ($column === $seriakizationName) {
							$propertiesNames[] = $property->getName();
							break;
						}
					}
				} elseif ($column === $property->getSerializationName()) {
					$propertiesNames[] = $property->getName();
					break;
				}
			}
		}
		
		return empty($propertiesNames) ? [$messageColumns] : array_unique($propertiesNames);
	}
	
}