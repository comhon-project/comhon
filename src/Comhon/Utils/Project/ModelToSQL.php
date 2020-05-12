<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils\Project;

use Comhon\Object\Config\Config;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelForeign;
use Comhon\Object\UniqueObject;
use Comhon\Model\ModelContainer;
use Comhon\Utils\Model as ModelUtils;
use Comhon\Model\Model;
use Comhon\Database\DatabaseHandler;
use Comhon\Utils\Utils;
use Comhon\Model\Property\AutoProperty;

class ModelToSQL extends InteractiveProjectScript {
	
	/**
	 * list of all accepted database types for each type generated from simple model
	 * 
	 * @var array
	 */
	const COMPATIBLE_TYPES = [
		'INT' => ['BIGINT', 'INTEGER'],
		'FLOAT' => ['DOUBLE PRECISION'],
		'BOOLEAN' => ['TINYINT'],
		'TEXT' => ['VARCHAR', 'CHARACTER VARYING'],
		'VARCHAR' => ['TEXT', 'CHARACTER VARYING'],
		'TIMESTAMP' => ['TIMESTAMP WITH TIME ZONE', 'TIMESTAMP WITHOUT TIME ZONE', 'DATETIME']
	];
	
	
	
	/**
	 * generate SQL instructions to create/update SQL tables according models that have SQL serialization.
	 *
	 * @param string $outputPath directory where SQL files will be stored
	 * @param string $update if true, connect to database and build table update query if table already exist
	 * @param string $filterModelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 * @return string[] queries to execute. each key is a database id, each value is query to execute.
	 */
	public function generateQueries($update = false, $filterModelName = null, $recursive = false) {
		if (!is_null($filterModelName) && !$recursive) {
			// verify if model exists
			ModelManager::getInstance()->getInstanceModel($filterModelName);
		}
		$projectModelNames = $this->getValidatedProjectModelNames($filterModelName, $recursive);
		ModelUtils::sortModelNamesByInheritance($projectModelNames);
		
		$filterSqlTables = null;
		$foreignKeys = [];
		$errorSqlTables = [];
		
		if (!is_null($filterModelName)) {
			$filterSqlTables = $this->getFilterSqlTables($projectModelNames, $filterModelName, $recursive);
		}
		$tablesInfos = $this->getTablesInformations(
			$projectModelNames,
			$filterSqlTables,
			$update,
			$foreignKeys,
			$errorSqlTables
		);
		return $this->buildDatabaseQueries($tablesInfos, $foreignKeys, $errorSqlTables);
	}
	
	/**
	 * create file(s) with SQL instructions to create/update SQL tables according models that have SQL serialization.
	 *
	 * @param string $outputPath directory where SQL files will be stored
	 * @param string $update if true, connect to database and build table update query if table already exist
	 * @param string $filterModelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 */
	public function generateFiles($outputPath, $update = false, $filterModelName = null, $recursive = false) {
		$databaseQueries = $this->generateQueries($update, $filterModelName, $recursive);
		
		if (empty($databaseQueries)) {
			$this->displayMessage('already up to date');
		} else {
			if (file_exists($outputPath)) {
				if (!is_dir($outputPath)) {
					throw new \Exception("output path given '$outputPath' is not a directory");
				}
				Utils::deleteDirectory($outputPath);
			}
			mkdir($outputPath, 0777, true);
			foreach ($databaseQueries as $databaseId => $databaseQuery) {
				file_put_contents($outputPath . DIRECTORY_SEPARATOR . "database-$databaseId.sql", $databaseQuery);
			}
		}
	}
	
	
	
	/**
	 *
	 * @param string[] $projectModelNames
	 * @param string $filterModelName
	 * @param boolean $recursive
	 * @throws \Exception
	 * @return NULL[]
	 */
	private function getFilterSqlTables($projectModelNames, $filterModelName, $recursive) {
		$filterSqlTables = [];
		if (!is_null($filterModelName)) {
			$filterModelNames = $this->getFilterModelNames($projectModelNames, $filterModelName, $recursive);
			foreach ($filterModelNames as $modelName => $value) {
				$model = ModelManager::getInstance()->getInstanceModel($modelName);
				if ($model->hasSqlTableSerialization()) {
					$tableKey = $this->getTableUniqueKey($model->getSerializationSettings());
					if (!array_key_exists($tableKey, $filterSqlTables)) {
						$filterSqlTables[$tableKey] = null;
					}
				}
			}
		}
		
		return $filterSqlTables;
	}
	
	/**
	 *
	 * @param string[] $projectModelNames
	 * @param array $filterSqlTables
	 * @param boolean $update
	 * @param array $foreignKeys
	 * @param array $errorSqlTables
	 * @return array
	 * @throws \Exception
	 */
	private function getTablesInformations($projectModelNames, $filterSqlTables, $update, &$foreignKeys, &$errorSqlTables) {
		$tablesInfos = [];
		$visitedDatabases = [];
		foreach($projectModelNames as $modelName) {
			try {
				$model = ModelManager::getInstance()->getInstanceModel($modelName);
				if (!$model->hasSqlTableSerialization()) {
					continue;
				}
				$tableKey = $this->getTableUniqueKey($model->getSerializationSettings());
				if (
					(!is_null($filterSqlTables) && !array_key_exists($tableKey, $filterSqlTables))
					|| array_key_exists($tableKey, $errorSqlTables)
				) {
					continue;
				}
				$database = $model->getSerializationSettings()->getValue('database');
				$this->loadDatabase($database);
				if ($update && !array_key_exists($database->getId(), $visitedDatabases)) {
					$visitedDatabases[$database->getId()] = null;
					$foreignKeys = array_merge($foreignKeys, $this->getForeignKeysInformations($database));
				}
				if (!array_key_exists($tableKey, $tablesInfos)) {
					$existingColumns = $update 
						? $this->getExistingColumnsInformations($model->getSerializationSettings(), $foreignKeys)
						: [];
					if (!empty($existingColumns)) {
						$this->verifyExistingConflictId($model, $existingColumns);
					}
					$tablesInfos[$tableKey] = [
						'table' => $model->getSerializationSettings(),
						'properties' => [],
						'serialization_names' => [],
						'inheritance_keys' => [],
						'existing_columns' => $existingColumns,
						'first_model' => $model
					];
				} elseif (!$model->isInheritedFrom($tablesInfos[$tableKey]['first_model'])) {
					$this->verifyConflictId($model, $tablesInfos[$tableKey]);
				}
				$this->verifyConflictModelSerializationNames($model);
				$this->addTableInformations($model, $tablesInfos[$tableKey]);
						
			} catch (\Exception $e) {
				unset($tablesInfos[$tableKey]);
				$errorSqlTables[$tableKey] = null;
				$sqlTableName = $model->getSerializationSettings()->getValue('name');
				$this->displayContinue(
					$e->getMessage(),
					"you can stop or continue without SQL Table '$sqlTableName'",
					"SQL Table '$sqlTableName' is skipped"
				);
			}
		}
		
		return $tablesInfos;
	}
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $database
	 * @throws \Exception
	 */
	private function loadDatabase(UniqueObject $database) {
		if (!$database->isLoaded()) {
			$success = $database->load();
			if (!$success) {
				$dbId = $database->getId();
				throw new \Exception("impossible to load database with id '$dbId'");
			}
		}
		if (!$database->issetValue('DBMS')) {
			throw new \Exception("database with id '$dbId' doesn't have DBMS value");
		}
	}
	
	/**
	 * get SQL table columns names, types, primary keys and foreign keys if table already exists in database
	 * 
	 * @param \Comhon\Object\UniqueObject $sqlTable
	 * @param string[] $foreignKeys all foreign keys
	 * @return array each key is a column name, each value is array of others informations
	 */
	private function getExistingColumnsInformations(UniqueObject $sqlTable, $foreignKeys) {
		$tableName = $this->getTableName($sqlTable); // table name without possible schema
		$schemaName = $this->getTableSchema($sqlTable); // for mysql, schema is the database name
		$dbHandler = DatabaseHandler::getInstanceWithDataBaseObject($sqlTable->getValue('database'));
		$query = "SELECT column_name, data_type FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = ? AND table_schema = ?";
		$statement = $dbHandler->execute($query, [$tableName, $schemaName]);
		
		$columnsInfos = [];
		foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$columnUniqueName = $this->getColumnUniqueName(
				$row['column_name'], 
				$tableName, 
				$schemaName, 
				$sqlTable->getValue('database')->getId()
			);
			$foreignColumn = array_key_exists($columnUniqueName, $foreignKeys) 
				? $foreignKeys[$columnUniqueName]['foreign_column'] : null;
			$columnsInfos[$row['column_name']] = [
				'type' => $row['data_type'],
				'is_primary' => false,
				'foreign_column' => $foreignColumn
			];
		}
		
		$query = "SELECT
    kcu.column_name, 
	tc.constraint_type
FROM 
    information_schema.table_constraints AS tc 
    JOIN information_schema.key_column_usage AS kcu
      ON kcu.constraint_name = tc.constraint_name
      AND kcu.table_schema = tc.table_schema
      AND kcu.table_name = tc.table_name
WHERE tc.constraint_type = 'PRIMARY KEY'
    AND tc.table_name = ?
	AND tc.table_schema = ?";
		$statement = $dbHandler->execute($query, [$this->getTableName($sqlTable), $this->getTableSchema($sqlTable)]);
		
		foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			if ($row['constraint_type'] == 'PRIMARY KEY') {
				$columnsInfos[$row['column_name']]['is_primary'] = true;
			}
		}
		
		return $columnsInfos;
	}
	
	/**
	 * get database foreign keys informations
	 * 
	 * @param \Comhon\Object\UniqueObject $database
	 * @return string[] each key is the local column unique name, each value is the referenced column unique name
	 */
	private function getForeignKeysInformations(UniqueObject $database) {
		switch ($database->getValue('DBMS')) {
			case 'mysql': 
				return $this->getMySqlForeignKeys($database);
			case 'pgsql':
				return $this->getPostgresForeignKeys($database);
			default:
				throw new \Exception("DBMS '{$database->getValue('DBMS')}' not managed");
		}
	}
	
	/**
	 *
	 * @param \Comhon\Object\UniqueObject $database
	 * @return string[]
	 */
	private function getMySqlForeignKeys(UniqueObject $database) {
		$query = "SELECT
    TABLE_SCHEMA as schema_name,
    TABLE_NAME as table_name,
    COLUMN_NAME as column_name,
    CONSTRAINT_NAME as constraint_name, 
    REFERENCED_TABLE_SCHEMA as foreign_schema,
    REFERENCED_TABLE_NAME as foreign_table,
    REFERENCED_COLUMN_NAME as foreign_column
    FROM
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_SCHEMA = '{$database->getValue('name')}'";
		
		$dbHandler = DatabaseHandler::getInstanceWithDataBaseObject($database);
		$statement = $dbHandler->execute($query);
		
		$foreignKeys = [];
		foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$column = $this->getColumnUniqueName(
				$row['column_name'],
				$row['table_name'],
				$row['schema_name'],
				$database->getId()
			);
			$foreignColumn = $this->getColumnUniqueName(
				$row['foreign_column'],
				$row['foreign_table'],
				$row['foreign_schema'],
				$database->getId()
			);
			$foreignKeys[$column] = [
				'constraint_name' => $row['constraint_name'],
				'foreign_column_unique' => $foreignColumn,
				'foreign_column' => $row['foreign_column'],
				'foreign_table' => $row['foreign_table'],
				'foreign_schema' => $row['foreign_schema']
			];
		}
		
		return $foreignKeys;
	}
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $database
	 * @return string[]
	 */
	private function getPostgresForeignKeys(UniqueObject $database) {
		$query = "SELECT connamespace::regnamespace AS schema_name,
    conrelid::regclass AS table_name,
    conname as constraint_name,
    pg_get_constraintdef(oid) as foreign_constraint
FROM   pg_constraint
WHERE  contype = 'f'
ORDER  BY connamespace::regnamespace, conrelid::regclass ASC;";
		
		$dbHandler = DatabaseHandler::getInstanceWithDataBaseObject($database);
		$statement = $dbHandler->execute($query);
		
		$foreignKeys = [];
		foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
			$matches = [];
			if (preg_match('/FOREIGN KEY \\((.+)\\) REFERENCES (.+)\\((.+)\\)/', $row['foreign_constraint'], $matches) !== 1) {
				throw new \Exception('no recognized foreign constraint : '.$row['foreign_constraint']);
			}
			$columns = explode(',', str_replace(' ', '', $matches[1]));
			$foreignColumns = explode(',', str_replace(' ', '', $matches[3]));
			$foreignTableName = $this->getTableNameFromString($matches[2], $database->getValue('DBMS'));
			$foreignSchemaName = $this->getTableSchemaFromString(
				$matches[2], 
				$database->getValue('DBMS'), 
				$database->getValue('name')
			);
			foreach ($columns as $i => $column) {
				$column = $this->getColumnUniqueName(
					$column,
					$row['table_name'],
					$row['schema_name'],
					$database->getId()
				);
				$foreignColumn = $this->getColumnUniqueName(
					$foreignColumns[$i],
					$foreignTableName,
					$foreignSchemaName,
					$database->getId()
				);
				$foreignKeys[$column] = [
					'constraint_name' => $row['constraint_name'],
					'foreign_column_unique' => $foreignColumn,
					'foreign_column' => $foreignColumns[$i],
					'foreign_table' => $foreignTableName,
					'foreign_schema' => $foreignSchemaName
				];
			}
		}
		
		return $foreignKeys;
	}
	
	/**
	 * concatenate table name, column name and schema name if provided
	 *
	 * @param string $columnName
	 * @param string $tableName
	 * @param string $schemaName for mysql schema is the database name
	 * @param string $databaseId
	 */
	private function getColumnUniqueName($columnName, $tableName, $schemaName, $databaseId) {
		return $databaseId.'_'.$schemaName.'_'.$tableName.'_'.$columnName;
	}
	
	/**
	 * 
	 * @param \Comhon\Object\UniqueObject $sqlTable
	 * @throws \Exception
	 * @return string
	 */
	private function getTableName(UniqueObject $sqlTable) {
		return $this->getTableNameFromString(
			$sqlTable->getValue('name'),
			$sqlTable->getValue('database')->getValue('DBMS')
		);
	}
	
	/**
	 *
	 * @param string $tableName
	 * @param string $DBMS
	 * @throws \Exception
	 * @return string
	 */
	private function getTableNameFromString($tableName, $DBMS) {
		switch ($DBMS) {
			case 'mysql':
				break;
			case 'pgsql':
				$exploded = explode('.', $tableName);
				if (count($exploded) > 1) {
					array_shift($exploded);
					$tableName = implode('.', $exploded);
				}
				break;
			default:
				throw new \Exception("DBMS '{$DBMS}' not managed");
		}
		if ($tableName[0] == '"' || $tableName[0] == '`') {
			$tableName = substr($tableName, 1, -1);
		}
		
		return $tableName;
	}
	
	/**
	 *
	 * @param \Comhon\Object\UniqueObject $sqlTable
	 * @throws \Exception
	 * @return string
	 */
	private function getTableSchema(UniqueObject $sqlTable) {
		return $this->getTableSchemaFromString(
			$sqlTable->getValue('name'),
			$sqlTable->getValue('database')->getValue('DBMS'),
			$sqlTable->getValue('database')->getValue('name')
		);
	}
	
	/**
	 *
	 * @param string $tableName
	 * @param string $DBMS
	 * @param string $databaseName
	 * @throws \Exception
	 * @return string
	 */
	private function getTableSchemaFromString($tableName, $DBMS, $databaseName) {
		switch ($DBMS) {
			case 'mysql':
				$schemaName =  $databaseName;
				break;
			case 'pgsql':
				$explode = explode('.', $tableName);
				$schemaName = count($explode) > 1 ? $explode[0] : 'public';
				break;
			default:
				throw new \Exception("DBMS '{$DBMS}' not managed");
		}
		if ($schemaName[0] == '"' || $schemaName[0] == '`') {
			$schemaName = substr($schemaName, 1, -1);
		}
		
		return $schemaName;
	}
	
	/**
	 * 
	 * @param Model $model
	 * @param array $existingColumns
	 */
	private function verifyExistingConflictId(Model $model, $existingColumns) {
		$ColumnsIds = [];
		$serializationNameIds = [];
		foreach ($existingColumns as $name => $existingColumn) {
			if ($existingColumn['is_primary']) {
				$ColumnsIds[] = $name;
			}
		}
		foreach ($model->getIdProperties() as $property) {
			$serializationNameIds[] = $property->getSerializationName();
		}
		if (count($serializationNameIds) !== count($ColumnsIds) || !empty(array_diff($serializationNameIds, $ColumnsIds))) {
			throw new \Exception(
				"Conflict with existing SQL table '{$model->getSerializationSettings()->getValue('name')}' : ".PHP_EOL
				." - existing primary key : ".json_encode($ColumnsIds).PHP_EOL
				." - serialization names of id properties of model '{$model->getName()}' : ".json_encode($serializationNameIds)
			);
		}
	}
	
	/**
	 * verify if there is conflict between two models that use same SQL table
	 *
	 * @param \Comhon\Model\Model $model
	 * @param array $tableInfos
	 * @throws \Exception
	 */
	private function verifyConflictId(Model $model, $tableInfos) {
		$DBMS = $model->getSerializationSettings()->getValue('database')->getValue('DBMS');
		$sameId = true;
		if (count($tableInfos['first_model']->getIdProperties()) != count($model->getIdProperties())) {
			$sameId = false;
		} else {
			$existing = [];
			foreach ($tableInfos['first_model']->getIdProperties() as $idProperty) {
				$existing[$idProperty->getSerializationName()] = $this->getColumnType($idProperty, $DBMS);
			}
			foreach ($model->getIdProperties() as $idProperty) {
				if (
					!array_key_exists($idProperty->getSerializationName(), $existing)
					|| $existing[$idProperty->getSerializationName()] !== $this->getColumnType($idProperty, $DBMS)
				) {
					$sameId = false;
					break;
				}
			}
		}
		if (!$sameId) {
			throw new \Exception(
				"Conflict between id properties from different models "
				."using same SQL table '{$model->getSerializationSettings()->getValue('name')}' : ".PHP_EOL
				." - model '{$tableInfos['first_model']->getName()}'".PHP_EOL
				." - model '{$model->getName()}'"
			);
		}
	}
	
	/**
	 * verify if there is conflict between several properties serialization names on provided model
	 *
	 * @param \Comhon\Model\Model $model
	 * @throws \Exception
	 */
	private function verifyConflictModelSerializationNames(Model $model) {
		$modelSerializationNames = [];
		foreach ($model->getProperties() as $property) {
			if (!$property->isSerializable() || $property->isAggregation()) {
				continue;
			}
			if ($property instanceof MultipleForeignProperty) {
				foreach ($property->getMultipleIdProperties() as $serializationName => $idProperty) {
					if (array_key_exists($serializationName, $modelSerializationNames)) {
						throw new \Exception(
							"Conflict on several properties with same serialtion name '$serializationName' on model '{$model->getName()}'"
						);
					}
					$modelSerializationNames[$serializationName] = null;
				}
			} else {
				$serializationName = $property->getSerializationName();
				if (array_key_exists($serializationName, $modelSerializationNames)) {
					throw new \Exception(
						"Conflict on several properties with same serialtion name '$serializationName' on model '{$model->getName()}'"
					);
				}
				$modelSerializationNames[$serializationName] = null;
			}
		}
	}
	
	/**
	 * add models informations to associated SQL table
	 *
	 * @param \Comhon\Model\Model $model
	 * @param array $tablesInfos
	 * @throws \Exception
	 */
	private function addTableInformations(Model $model, &$tableInfos) {
		$DBMS = $model->getSerializationSettings()->getValue('database')->getValue('DBMS');
		$existingColumns = $tableInfos['existing_columns'];
		
		foreach ($model->getProperties() as $propertyName => $property) {
			if (!$property->isSerializable() || $property->isAggregation()) {
				continue;
			}
			if (array_key_exists($propertyName, $tableInfos['properties'])) {
				$existingProperty = $tableInfos['properties'][$propertyName];
				if ($property->isEqual($existingProperty)) {
					// either it's a parent model property already processed,
					// or it's a property of another model with same table and there is no difference
					continue;
				}
			}
			
			// compare equality only if serializations names are the same
			// if differents there is no coflict
			if ($property instanceof MultipleForeignProperty) {
				foreach ($property->getMultipleIdProperties() as $serializationName => $idProperty) {
					if (array_key_exists($serializationName, $tableInfos['serialization_names'])) {
						$serializationNameNode = $tableInfos['serialization_names'][$serializationName];
						if ($this->getColumnType($idProperty, $DBMS) !== $serializationNameNode['column_type']) {
							throw new \Exception(
								"Conflict for properties with serialtion name '$serializationName' "
								."on SQL table '{$model->getSerializationSettings()->getValue('name')}' : ".PHP_EOL
								." - property of model '{$serializationNameNode['model']->getName()}'".PHP_EOL
								." - property of model '{$model->getName()}'"
							);
						}
					}
					$columnType = $this->getColumnType($idProperty, $DBMS);
					$this->verifyColumnType($serializationName, $columnType, $existingColumns, $model);
					$tableInfos['serialization_names'][$serializationName] = [
						'model' => $model,
						'column_type' => $columnType
					];
				}
			} else {
				$serializationName = $property->getSerializationName();
				if ($this->isForeignPropertySimpleIdColumn($property)) {
					$idProperties = $property->getUniqueModel()->getIdProperties();
					$columnProperty = current($idProperties);
				} else {
					$columnProperty = $property;
				}
				if (array_key_exists($serializationName, $tableInfos['serialization_names'])) {
					$serializationNameNode = $tableInfos['serialization_names'][$serializationName];
					if ($this->getColumnType($columnProperty, $DBMS) !== $serializationNameNode['column_type']) {
						throw new \Exception(
							"Conflict for properties with serialtion name '$serializationName' "
							."on SQL table '{$model->getSerializationSettings()->getValue('name')}' : ".PHP_EOL
							." - property of model '{$serializationNameNode['model']->getName()}'".PHP_EOL
							." - property of model '{$model->getName()}'"
						);
					}
				}
				$columnType = $this->getColumnType($columnProperty, $DBMS);
				$this->verifyColumnType($serializationName, $columnType, $existingColumns, $model);
				$tableInfos['serialization_names'][$serializationName] = [
					'model' => $model,
					'column_type' => $columnType
				];
			}
			if (array_key_exists($propertyName, $tableInfos['properties'])) {
				// two models witout kinship using same sql table and having same property name
				// but with differents serialization names
				$tableInfos['properties'][] = $property;
			} else {
				$tableInfos['properties'][$propertyName] = $property;
			}
		}
		$inheritanceKey = $model->getSerialization()->getInheritanceKey();
		if (!is_null($model->getSerialization()->getInheritanceKey())) {
			if (array_key_exists($inheritanceKey, $tableInfos['serialization_names'])) {
				$serializationNameNode = $tableInfos['serialization_names'][$inheritanceKey];
				throw new \Exception(
					"Conflict between property and inhertance_key "
					."on SQL table '{$model->getSerializationSettings()->getValue('name')}' : ".PHP_EOL
					." - property with serialtion name '$inheritanceKey' "
					."of model '{$serializationNameNode['model']->getName()}'".PHP_EOL
					." - inhertance_key '$inheritanceKey' of model '{$model->getName()}'"
				);
			}
			$this->verifyColumnType($inheritanceKey, 'TEXT', $existingColumns, $model);
			$tableInfos['inheritance_keys'][$model->getSerialization()->getInheritanceKey()] = null;
		}
	}
	
	/**
	 * if column already exists, verify if there is no conflict on column type
	 * 
	 * @param string $serializationName
	 * @param string $columnType
	 * @param array $existingColumns
	 * @param \Comhon\Model\Model $model
	 * @throws \Exception
	 */
	private function verifyColumnType($serializationName, $columnType, $existingColumns, Model $model) {
		if (
			array_key_exists($serializationName, $existingColumns)
			&& !$this->isCompatibleTypes($existingColumns[$serializationName]['type'], $columnType)
		) {
			throw new \Exception(
				"Type conflict on column with name '$serializationName' from model {$model->getName()} "
				."on SQL table '{$model->getSerializationSettings()->getValue('name')}' : ".PHP_EOL
				." - existing column type : '{$existingColumns[$serializationName]['type']}'".PHP_EOL
				." - new column type : '$columnType'"
			);
		}
	}
	
	/**
	 * verify if provided types are compatible
	 * 
	 * @param string $dbType
	 * @param string $modelType
	 * @return boolean
	 */
	private function isCompatibleTypes($dbType, $modelType) {
		// remove precision
		if (strpos($dbType, '(') !== false) {
			$dbType = strstr($dbType, '(', true);
		}
		if (strpos($modelType, '(') !== false) {
			$modelType = strstr($modelType, '(', true);
		}
		return strtoupper($dbType) == strtoupper($modelType) 
			|| (
				array_key_exists(strtoupper($modelType), self::COMPATIBLE_TYPES)
				&& in_array(strtoupper($dbType), self::COMPATIBLE_TYPES[strtoupper($modelType)])
			);
	}
	
	/**
	 * build SQL queries according provided tables informations
	 *
	 * @param array $tablesInfos
	 * @param array $foreignKeys
	 * @param array $errorSqlTables
	 * @return string[]
	 */
	private function buildDatabaseQueries($tablesInfos, $foreignKeys, $errorSqlTables) {
		$references = [];
		$DBMSByDbId = [];
		foreach ($foreignKeys as $foreignKey) {
			$references[$foreignKey['foreign_column_unique']] = $foreignKey;
		}
		$databaseQueries = [];
		$foreignConstraints = [];
		$dropforeignConstraints = [];
		foreach($tablesInfos as $tableInfos) {
			if (empty($tableInfos['properties'])) {
				continue;
			}
			/** @var \Comhon\Object\UniqueObject $table */
			$table = $tableInfos['table'];
			$DBMS = $table->getValue('database')->getValue('DBMS');
			$DBMSByDbId[$table->getValue('database')->getId()] = $DBMS;
			$existingColumns = $tableInfos['existing_columns'];
			$primaryKey = [];
			$tableColumns = [];
			if (!array_key_exists($table->getValue('database')->getId(), $databaseQueries)) {
				$databaseQueries[$table->getValue('database')->getId()] = '';
			}
			
			foreach ($tableInfos['properties'] as $property) {
				$propertyColumns = $this->isForeignPropertySimpleIdColumn($property) || ($property instanceof MultipleForeignProperty)
					? $this->getForeignColumnsDescriptions($property, $tableInfos, $foreignConstraints)
					: [$this->getColumnDescription($property, $DBMS)];
				
				foreach ($propertyColumns as $column) {
					$tableColumns[] = $column;
				}
				if ($property->isId()) {
					$primaryKey[] = $property->getSerializationName();
				}
			}
			foreach ($tableInfos['inheritance_keys'] as $columName => $null) {
				$tableColumns[] = [
					'name' => $columName,
					'type' => 'TEXT'
				];
			}
			if (empty($existingColumns)) {
				// CREATE
				$databaseQueries[$table->getValue('database')->getId()] .= $this->getTableDefinition(
					$table,
					$tableColumns,
					$primaryKey
				);
			} else {
				// UDPATE
				$databaseQueries[$table->getValue('database')->getId()] .= $this->getTableAlterations(
					$table,
					$tableColumns,
					$foreignKeys,
					$references,
					$existingColumns,
					$dropforeignConstraints
				);
			}
		}
		
		// constraints to drop are placed at the begining
		foreach ($dropforeignConstraints as $databaseId => $databaseDropforeignConstraints) {
			$query = '';
			foreach ($databaseDropforeignConstraints as $table => $dropforeignConstraints) {
				$tableAlterations = [];
				foreach ($dropforeignConstraints as $constraintName) {
					$tableAlterations[] = $this->getDropForeignKeyInstruction($constraintName, $DBMSByDbId[$databaseId]);
				}
				if (count($tableAlterations) > 0) {
					$query .=  "\nALTER TABLE $table" . PHP_EOL
					. implode("," . PHP_EOL, $tableAlterations) . PHP_EOL
					.';' . PHP_EOL;
				}
			}
			$databaseQueries[$databaseId] = $query.$databaseQueries[$databaseId];
		}
		
		// constraints to create are placed at the end
		foreach ($foreignConstraints as $databaseId => $databaseForeignConstraints) {
			foreach ($databaseForeignConstraints as $foreignConstraint) {
				$localKey = $this->getTableUniqueKey($foreignConstraint['local_table']);
				$foreignKey = $this->getTableUniqueKey($foreignConstraint['foreign_table']);
				if (!array_key_exists($localKey, $errorSqlTables) && !array_key_exists($foreignKey, $errorSqlTables)) {
					$databaseQueries[$databaseId].= $this->getForeignConstraint($foreignConstraint);
				}
			}
		}
		foreach ($databaseQueries as $databaseId => $databaseQuery) {
			if (empty($databaseQuery)) {
				unset($databaseQueries[$databaseId]);
			}
		}
		
		return $databaseQueries;
	}
	
	/**
	 *
	 * @param Property $property
	 * @param string $DBMS database management system
	 * @return string[]
	 */
	private function getColumnDescription(Property $property, $DBMS) {
		$column = [
			'name' => $property->getSerializationName(),
			'type' => $this->getColumnType($property, $DBMS)
		];
		if ($property instanceof AutoProperty) {
			$column['auto'] = $property->getAutoFunction();
		}
		return $column;
	}
	
	/**
	 * 
	 * @param \Comhon\Model\Property\ForeignProperty $property
	 * @param array $tableInfos
	 * @param array $foreignConstraints
	 * @return array
	 */
	private function getForeignColumnsDescriptions(ForeignProperty $property, $tableInfos, array &$foreignConstraints) {
		
		/** @var \Comhon\Object\UniqueObject $table */
		$table = $tableInfos['table'];
		$existingColumns = $tableInfos['existing_columns'];
		
		$databaseId = $table->getValue('database')->getId();
		$DBMS = $table->getValue('database')->getValue('DBMS');
		$hasExistingForeignKey = false;
		$existingPropertyColumnsTypes = [];
		
		$serializationNames = ($property instanceof MultipleForeignProperty) 
			? array_keys($property->getMultipleIdProperties()) : [$property->getSerializationName()];
		foreach ($serializationNames as $name) {
			if (array_key_exists($name, $existingColumns)) {
				$existingPropertyColumnsTypes[] = strtoupper($existingColumns[$name]['type']);
				if (!is_null($existingColumns[$name]['foreign_column'])) {
					$hasExistingForeignKey = true;
					break;
				}
			}
		}
		
		if (
			!$hasExistingForeignKey
			&& $property->getUniqueModel()->hasSqlTableSerialization()
			&& !($property->getModel()->getModel() instanceof ModelContainer)
			&& $databaseId === $property->getUniqueModel()->getSerializationSettings()->getValue('database')->getId()
			&& $this->isIdModelManagedForForeignKeys($property->getUniqueModel(), $existingPropertyColumnsTypes, $DBMS)
		) {
			$foreignTable = $property->getUniqueModel()->getSerializationSettings();
			$foreignConstraint = [
				'local_table' => $table,
				'foreign_table' => $foreignTable,
				'local_columns' => [],
				'foreign_columns' => []
			];
			if (!array_key_exists($databaseId, $foreignConstraints)) {
				$foreignConstraints[$databaseId] = [];
			}
			$foreignConstraints[$databaseId][] =& $foreignConstraint;
		} else {
			$foreignConstraint = null;
		}
		
		$columns = ($property instanceof MultipleForeignProperty)
			? $this->getMultipleForeignColumnDescription($property, $DBMS, $foreignConstraint)
			: $this->getUniqueForeignColumnDescription($property, $DBMS, $foreignConstraint);
		
		return $columns;
	}
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $DBMS
	 * @param string[] $existingPropertyColumnsTypes
	 * @throws \Exception
	 * @return boolean
	 */
	private function isIdModelManagedForForeignKeys(Model $model, $existingPropertyColumnsTypes, $DBMS) {
		switch ($DBMS) {
			case 'mysql':
				if (in_array('TEXT', $existingPropertyColumnsTypes)) {
					return false;
				}
				foreach ($model->getIdProperties() as $property) {
					if ($this->getColumnType($property, $DBMS) == 'TEXT') {
						return false;
					}
				}
				return true;
				break;
			case 'pgsql':
				return true;
				break;
			default:
				throw new \Exception("DBMS '$DBMS' not managed");
		}
	}
	
	/**
	 *
	 * @param MultipleForeignProperty $property
	 * @param string $DBMS database management system
	 * @param array $foreignConstraint
	 * @return string[][]
	 */
	private function getMultipleForeignColumnDescription(MultipleForeignProperty $property, $DBMS, array &$foreignConstraint = null) {
		$columns = [];
		
		foreach ($property->getMultipleIdProperties()  as $serializationName => $foreignIdProperty) {
			$columns[] = [
				'name' => $serializationName,
				'type' => $this->getColumnType($foreignIdProperty, $DBMS),
			];
			
			if (!is_null($foreignConstraint)) {
				$foreignConstraint['local_columns'][] = $serializationName;
				$foreignConstraint['foreign_columns'][] = $foreignIdProperty->getSerializationName();
			}
		}
		return $columns;
	}
	
	/**
	 *
	 * @param ForeignProperty $property
	 * @param string $DBMS database management system
	 * @param array $foreignConstraint
	 * @return string[][]
	 */
	private function getUniqueForeignColumnDescription(ForeignProperty $property, $DBMS, array &$foreignConstraint = null) {
		$idProperties = $property->getUniqueModel()->getIdProperties();
		if (empty($idProperties)) {
			throw new \Exception('foreign property not checked');
		}
		$foreignIdProperty = current($idProperties);
		
		if (!is_null($foreignConstraint)) {
			$foreignConstraint['local_columns'][] = $property->getSerializationName();
			$foreignConstraint['foreign_columns'][] = $foreignIdProperty->getSerializationName();
		}
		
		return [
			[
				'name' => $property->getSerializationName(),
				'type' => $this->getColumnType($foreignIdProperty, $DBMS),
			]
		];
	}
	
	/**
	 *
	 * @param Property $property
	 * @param string $DBMS database management system
	 * @throws \Exception
	 * @return string
	 */
	private function getColumnType(Property $property, $DBMS) {
		switch ($property->getUniqueModel()->getName()) {
			case 'string':
				// mysql doesn't manage key with text type
				$type = $property->isId() ? 'VARCHAR(255)' : 'TEXT' /*CHARACTER SET utf8'*/;
				break;
			case 'integer':
			case 'index':
				$type = 'INT';
				break;
			case 'float':
			case 'percentage':
				switch ($DBMS) {
					case 'mysql':
						$type = 'DECIMAL(20,10)';
						break;
					case 'pgsql':
						$type = 'FLOAT';
						break;
					default:
						throw new \Exception('database doesn\'t have DBMS');
				}
				break;
			case 'boolean':
				$type = 'BOOLEAN';
				break;
			case 'dateTime':
				$type = 'TIMESTAMP'; // 'TIMESTAMP NULL'
				break;
			default:
				if (
					($property->getModel() instanceof SimpleModel) 
					|| $this->isForeignPropertySimpleIdColumn($property)
				){
					throw new \Exception(
						'type not handled : '
						. $property->getUniqueModel()->getName().' -> '
						.get_class($property->getModel()));
				}
				// model that correspond to an object or an object array 
				// or foreign model with several ids but not defined as MultipleForeignProperty
				// that will be JSON encoded during serialization
				// so type is text, may be JSON probably
				$type = 'TEXT';
				break;
		}
		return $type;
	}
	
	/**
	 * verify if property is foreign and must be stored as a simple id column.
	 * 
	 * @param \Comhon\Model\Property\Property $property
	 */
	private function isForeignPropertySimpleIdColumn(Property $property) {
		return ($property->getModel() instanceof ModelForeign)
			&& ($property->getModel()->getModel() instanceof Model) // avoid arrays  
			&& count($property->getModel()->getModel()->getIdProperties()) == 1; // avoid model with several ids
	}
	
	/**
	 * 
	 * @param string $column
	 * @param string $DBMS database management system
	 * @return string
	 */
	private function escape($column, $DBMS) {
		return $this->getEscapeChar($DBMS).$column.$this->getEscapeChar($DBMS);
	}
	
	/**
	 * espace provided values and concat them with coma character
	 *
	 * @param string[] $columns
	 * @param string $DBMS database management system
	 * @return string
	 */
	private function escapeList($columns, $DBMS) {
		$es = $this->getEscapeChar($DBMS);
		return $es.implode($es.', '.$es, $columns).$es;
	}
	
	/**
	 *
	 * @param string $column
	 * @param string $DBMS database management system
	 * @return string
	 */
	private function getEscapeChar($DBMS) {
		switch ($DBMS) {
			case 'mysql':
				return '`';
				break;
			case 'pgsql':
				return '"';
				break;
			default:
				throw new \Exception("DBMS '$DBMS' not managed");
		}
	}
	
	/**
	 *
	 * @param \Comhon\Object\UniqueObject $sqlTable
	 * @param string[] $tableColumns
	 * @param string[] $primaryKey
	 * @return string
	 */
	private function getTableDefinition(UniqueObject $sqlTable, $tableColumns, $primaryKey = null) {
		$tableDescription = [];
		$DBMS = $sqlTable->getValue('database')->getValue('DBMS');
		
		foreach ($tableColumns as $column) {
			$tableDescription[] = '    '.$this->getColumnDefinition($column, $DBMS);
		}
		if (!empty($primaryKey)) {
			$tableDescription[] = '    PRIMARY KEY (' . $this->escapeList($primaryKey, $DBMS) . ')';
		}
		
		return "\nCREATE TABLE {$sqlTable->getValue('name')} (" . PHP_EOL
			. implode("," . PHP_EOL, $tableDescription) . PHP_EOL
			.');' . PHP_EOL;
	}
	
	/**
	 *
	 * @param string[] $column
	 * @param string $DBMS
	 * @throws \Exception
	 * @return string
	 */
	private function getColumnDefinition($column, $DBMS) {
		$columnName = $this->escape($column['name'], $DBMS);
		if (isset($column['auto']) && $column['auto'] == 'incremental') {
			switch ($DBMS) {
				case 'mysql':
					$columnString = "$columnName {$column['type']} AUTO_INCREMENT";
					break;
				case 'pgsql':
					$columnString = "$columnName SERIAL";
					break;
				default:
					throw new \Exception("DBMS '$DBMS' not managed");
			}
		} else {
			$columnString = "$columnName {$column['type']}";
		}
		
		return $columnString;
	}
	
	/**
	 *
	 * @param \Comhon\Object\UniqueObject $sqlTable
	 * @param string[] $tableColumns
	 * @param string[] $foreignKeys
	 * @param array $references
	 * @param array $existingColumns
	 * @param array $dropforeignConstraints
	 * @return string
	 */
	private function getTableAlterations(UniqueObject $sqlTable, $tableColumns, $foreignKeys, $references, $existingColumns, &$dropforeignConstraints) {
		$tableAlterations = [];
		$sameColumns = [];
		$query = '';
		$DBMS = $sqlTable->getValue('database')->getValue('DBMS');
		
		foreach ($tableColumns as $column) {
			if (!array_key_exists($column['name'], $existingColumns)) {
				$tableAlterations[] = '    ADD '.$this->getColumnDefinition($column, $DBMS);
			} else {
				$sameColumns[$column['name']] = null;
			}
		}
		foreach (array_diff_key($existingColumns, $sameColumns) as $toDeleteColumnName => $value) {
			$columnUniqueName = $this->getColumnUniqueName(
				$toDeleteColumnName,
				$this->getTableName($sqlTable),
				$this->getTableSchema($sqlTable), 
				$sqlTable->getValue('database')->getId()
			);
			if (array_key_exists($columnUniqueName, $references)) {
				$this->addConstraintToDrop(
					$references[$columnUniqueName]['constraint_name'],
					$references[$columnUniqueName]['table_name'],
					$references[$columnUniqueName]['schema_name'],
					$sqlTable->getValue('database')->getId(),
					$sqlTable->getValue('database')->getValue('DBMS'),
					$dropforeignConstraints
				);
			}
			if (array_key_exists($columnUniqueName, $foreignKeys)) {
				$this->addConstraintToDropWithSqlTableObject(
					$foreignKeys[$columnUniqueName]['constraint_name'], 
					$sqlTable,
					$dropforeignConstraints
				);
			}
			$tableAlterations[] = "    DROP $toDeleteColumnName";
		}
		if (count($tableAlterations) > 0) {
			$query =  "\nALTER TABLE {$sqlTable->getValue('name')}" . PHP_EOL
				. implode("," . PHP_EOL, $tableAlterations) . PHP_EOL
				.';' . PHP_EOL;
		}
		return $query;
	}
	
	private function addConstraintToDropWithSqlTableObject($constraintName, UniqueObject $sqlTable, &$dropforeignConstraints) {
		$this->addConstraintToDrop(
			$constraintName, 
			$this->getTableName($sqlTable),
			$this->getTableSchema($sqlTable),
			$sqlTable->getValue('database')->getId(),
			$sqlTable->getValue('database')->getValue('DBMS'),
			$dropforeignConstraints
		);
	}
	
	/**
	 * 
	 * @param string $constraintName
	 * @param string $tableName table name without schema
	 * @param string $schemaName
	 * @param string $databaseId
	 * @param string $DBMS
	 * @param array $dropforeignConstraints
	 */
	private function addConstraintToDrop($constraintName, $tableName, $schemaName, $databaseId, $DBMS, &$dropforeignConstraints) {
		$tableKey = empty($schemaName)
			? $this->escape($tableName, $DBMS)
			: $this->escape($schemaName, $DBMS).'.'.$this->escape($tableName, $DBMS);
		if (!array_key_exists($databaseId, $dropforeignConstraints)) {
			$dropforeignConstraints[$databaseId] = [];
		}
		if (!array_key_exists($tableKey, $dropforeignConstraints[$databaseId])) {
			$dropforeignConstraints[$databaseId][$tableKey] = [];
		}
		if (!in_array($constraintName, $dropforeignConstraints[$databaseId][$tableKey])) {
			$dropforeignConstraints[$databaseId][$tableKey][] = $constraintName;
		}
	}
	
	/**
	 * 
	 * @param string $constraintName
	 * @param string $DBMS
	 * @throws \Exception
	 * @return string
	 */
	private function getDropForeignKeyInstruction($constraintName, $DBMS) {
		switch ($DBMS) {
			case 'mysql':
				return "    DROP FOREIGN KEY $constraintName";
				break;
			case 'pgsql':
				return "    DROP CONSTRAINT $constraintName";
				break;
			default:
				throw new \Exception("DBMS '$DBMS' not managed");
		}
	}
	
	/**
	 *
	 * @param array $foreignConstraint
	 * @return string
	 */
	private function getForeignConstraint($foreignConstraint) {
		$DBMS = $foreignConstraint['local_table']->getValue('database')->getValue('DBMS');
		return sprintf(
			PHP_EOL . "ALTER TABLE %s" . PHP_EOL
			."    ADD FOREIGN KEY (%s) REFERENCES %s(%s);" . PHP_EOL,
			$foreignConstraint['local_table']->getValue('name'),
			$this->escapeList($foreignConstraint['local_columns'], $DBMS),
			$foreignConstraint['foreign_table']->getValue('name'),
			$this->escapeList($foreignConstraint['foreign_columns'], $DBMS)
		);
	}
	
	/**
	 * 
	 * @param UniqueObject $sqlTable
	 */
	private function getTableUniqueKey(UniqueObject $sqlTable) {
		return $sqlTable->getValue('database')->getId().'_'.$sqlTable->getId();
	}
	
	/**
	 * create file(s) with SQL instructions to create/update SQL tables according models that have SQL serialization.
	 * should be called from CLI script.
	 *
	 * @param string $configPath comhon config file path
	 * @param string $outputPath directory where SQL files will be stored
	 * @param string $update if true, connect to database and build table update query if table already exist
	 * @param string $filterModelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 */
	public static function exec($configPath, $outputPath, $update = false, $filterModelName = null, $recursive = false) {
		Config::setLoadPath($configPath);
		
		$self = new self(true);
		$self->generateFiles($outputPath, $update, $filterModelName, $recursive);
	}
    
}
