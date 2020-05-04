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
use Comhon\Exception\ArgumentException;
use Comhon\Model\ModelContainer;
use Comhon\Utils\Model as ModelUtils;
use Comhon\Model\Model;

class ModelToSQL extends InteractiveProjectScript {
	
	/**
	 * create file(s) with SQL instructions to create SQL tables according models that have SQL serialization.
	 *
	 * @param string $outputPath directory where SQL files will be stored
	 * @param string $filterModelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 * @throws ArgumentException
	 */
	public function transform($outputPath, $filterModelName = null, $recursive = false) {
		if (!is_null($filterModelName) && !$recursive) {
			// verify if model exists
			ModelManager::getInstance()->getInstanceModel($filterModelName);
		}
		$projectModelNames = $this->getValidatedProjectModelNames($filterModelName, $recursive);
		ModelUtils::sortModelNamesByInheritance($projectModelNames);
		
		$filterSqlTables = null;
		$errorSqlTables = [];
		
		if (!is_null($filterModelName)) {
			$filterSqlTables = $this->getFilterSqlTables($projectModelNames, $filterModelName, $recursive);
		}
		$tablesInfos = $this->getTablesInformations($projectModelNames, $filterSqlTables, $errorSqlTables);
		$databaseQueries = $this->buildDatabaseQueries($tablesInfos, $errorSqlTables);
		
		if (!file_exists($outputPath)) {
			mkdir($outputPath, 0777, true);
		}
		if (count($databaseQueries) > 1) {
			foreach ($databaseQueries as $databaseId => $databaseQuery) {
				file_put_contents($outputPath . DIRECTORY_SEPARATOR . "database-$databaseId.sql", $databaseQuery);
			}
		} else {
			file_put_contents($outputPath . DIRECTORY_SEPARATOR . "database.sql", current($databaseQueries));
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
					if (!$model->getSerializationSettings()->getValue('database')->isLoaded()) {
						$success = $model->getSerializationSettings()->getValue('database')->load();
						if (!$success) {
							$dbId = $model->getSerializationSettings()->getValue('database')->getId();
							throw new \Exception("impossible to load database with id '$dbId'");
						}
					}
					if (!$model->getSerializationSettings()->getValue('database')->issetValue('DBMS')) {
						throw new \Exception("database with id '$dbId' doesn't have DBMS value");
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
	 * @param array $errorSqlTables
	 * @return array
	 * @throws \Exception
	 */
	private function getTablesInformations($projectModelNames, $filterSqlTables, &$errorSqlTables) {
		$tablesInfos = [];
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
				if (!array_key_exists($tableKey, $tablesInfos)) {
					$tablesInfos[$tableKey] = [
						'table' => $model->getSerializationSettings(),
						'properties' => [],
						'serialization_names' => [],
						'inheritance_keys' => [],
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
					$tableInfos['serialization_names'][$serializationName] = [
						'model' => $model,
						'column_type' => $this->getColumnType($idProperty, $DBMS)
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
				$tableInfos['serialization_names'][$serializationName] = [
					'model' => $model,
					'column_type' => $this->getColumnType($columnProperty, $DBMS)
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
			$tableInfos['inheritance_keys'][$model->getSerialization()->getInheritanceKey()] = null;
		}
	}
	
	/**
	 * build SQL queries according provided tables informations
	 *
	 * @param array $tablesInfos
	 * @param array $errorSqlTables
	 * @return string[]
	 */
	private function buildDatabaseQueries($tablesInfos, $errorSqlTables) {
		$databaseQueries = [];
		$foreignConstraints = [];
		foreach($tablesInfos as $tableInfos) {
			if (empty($tableInfos['properties'])) {
				continue;
			}
			$table = $tableInfos['table'];
			$DBMS = $table->getValue('database')->getValue('DBMS');
			$primaryKey = [];
			$tableColumns = [];
			if (!array_key_exists($table->getValue('database')->getId(), $databaseQueries)) {
				$databaseQueries[$table->getValue('database')->getId()] = '';
			}
			
			foreach ($tableInfos['properties'] as $property) {
				$propertyColumns = $this->isForeignPropertySimpleIdColumn($property) || ($property instanceof MultipleForeignProperty)
				? $this->getForeignColumnsDescriptions($table, $DBMS, $property, $errorSqlTables, $foreignConstraints)
				: [$this->getColumnDescription($property, $DBMS)];
				
				foreach ($propertyColumns as $column) {
					$tableColumns[] = $column;
				}
				if ($property->isId()) {
					$primaryKey[] = $this->getColumnName($property, $DBMS);
				}
			}
			foreach ($tableInfos['inheritance_keys'] as $columName => $null) {
				$tableColumns[] = [
						'name' => $this->escape($columName, $DBMS),
						'type' => 'VARCHAR(255)'
				];
			}
			$databaseQueries[$table->getValue('database')->getId()] .= $this->getTableDefinition(
					$table->getId(),
					$tableColumns,
					$primaryKey
					);
		}
		
		$foreignKeySuffix = 0;
		foreach ($foreignConstraints as $databaseId => $databaseForeignConstraints) {
			foreach ($databaseForeignConstraints as $foreignConstraint) {
				$databaseQueries[$databaseId].= $this->getForeignConstraint($foreignConstraint, $foreignKeySuffix);
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
		return [
			'name' => $this->getColumnName($property, $DBMS),
			'type' => $this->getColumnType($property, $DBMS)
		];
	}
	
	/**
	 * 
	 * @param \Comhon\Object\ComhonObject $table
	 * @param string $DBMS database management system
	 * @param \Comhon\Model\Property\ForeignProperty $property
	 * @param array $errorSqlTables
	 * @param array $foreignConstraints
	 * @return array
	 */
	private function getForeignColumnsDescriptions($table, $DBMS, ForeignProperty $property, $errorSqlTables, array &$foreignConstraints) {
		$databaseId = $table->getValue('database')->getId();
		
		if (
			$property->getUniqueModel()->hasSqlTableSerialization()
			&& !($property->getModel()->getModel() instanceof ModelContainer)
			&& $databaseId === $property->getUniqueModel()->getSerializationSettings()->getValue('database')->getId()
			&& !array_key_exists($this->getTableUniqueKey($property->getUniqueModel()->getSerializationSettings()), $errorSqlTables)
		) {
			$foreignTable = $property->getUniqueModel()->getSerializationSettings();
			$foreignConstraint = [
				'local_table' => $table->getId(),
				'foreign_table' => $foreignTable->getId(),
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
		
		try {
			$columns = ($property instanceof MultipleForeignProperty)
				? $this->getMultipleForeignColumnDescription($property, $DBMS, $foreignConstraint)
				: $this->getUniqueForeignColumnDescription($property, $DBMS, $foreignConstraint);
		} finally {
			if (!is_null($foreignConstraint) && empty($foreignConstraint['local_columns'])) {
				array_pop($foreignConstraints[$databaseId]);
			}
		}
		
		return $columns;
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
			$column = $this->escape($serializationName, $DBMS);
			
			$columns[] = [
				'name' => $column,
				'type' => $this->getColumnType($foreignIdProperty, $DBMS),
			];
			
			if (!is_null($foreignConstraint)) {
				$foreignConstraint['local_columns'][] = $column;
				$foreignConstraint['foreign_columns'][] = $this->getColumnName($foreignIdProperty, $DBMS);
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
			$foreignConstraint['local_columns'][] = $this->getColumnName($property, $DBMS);
			$foreignConstraint['foreign_columns'][] = $this->getColumnName($foreignIdProperty, $DBMS);
		}
		
		return [
			[
				'name' => $this->getColumnName($property, $DBMS),
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
				$type = 'TIMESTAMP NULL';
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
	 * transform column name if needed
	 * 
	 * @param Property $property
	 * @param string $DBMS database management system
	 * @return string
	 */
	private function getColumnName(Property $property, $DBMS) {
		return $this->escape($property->getSerializationName(), $DBMS);
	}
	
	/**
	 * 
	 * @param string $column
	 * @param string $DBMS database management system
	 * @return string
	 */
	private function escape($column, $DBMS) {
		switch ($DBMS) {
			case 'mysql':
				$column = "`$column`";
				break;
			case 'pgsql':
				$column = "\"$column\"";
				break;
			default:
				throw new \Exception("DBMS '$DBMS' not managed");
		}
		return $column;
	}
	
	/**
	 *
	 * @param string $table
	 * @param string[] $tableColumns
	 * @param string[] $primaryKey
	 * @param bool $transform
	 * @return string
	 */
	private function getTableDefinition($table, $tableColumns, $primaryKey = null) {
		$tableDescription = [];
		
		foreach ($tableColumns as $column) {
			$tableDescription[] = "    {$column['name']} {$column['type']}";
		}
		
		if (!empty($primaryKey)) {
			$tableDescription[] = '    PRIMARY KEY (' . implode(', ', $primaryKey) . ')';
		}
		
		return "\nCREATE TABLE $table (" . PHP_EOL
				. implode("," . PHP_EOL, $tableDescription) . PHP_EOL
				.');' . PHP_EOL;
	}
	
	/**
	 *
	 * @param array $foreignConstraint
	 * @param integer $foreignKeySuffix
	 * @return string
	 */
	private function getForeignConstraint($foreignConstraint, &$foreignKeySuffix) {
		$foreignKeySuffix++;
		return sprintf(
			PHP_EOL . "ALTER TABLE %s" . PHP_EOL
			."    ADD CONSTRAINT fk_%s_$foreignKeySuffix" . PHP_EOL
			."    FOREIGN KEY (%s) REFERENCES %s(%s);" . PHP_EOL,
			$foreignConstraint['local_table'],
			str_replace(['.', '`', '"'], ['_', '', ''], $foreignConstraint['local_table']),
			implode(', ', $foreignConstraint['local_columns']),
			$foreignConstraint['foreign_table'],
			implode(', ', $foreignConstraint['foreign_columns'])
		);
	}
	
	/**
	 * 
	 * @param UniqueObject $sqlTable
	 */
	private function getTableUniqueKey(UniqueObject $sqlTable) {
		return $sqlTable->getId().'_'.$sqlTable->getValue('database')->getId();
	}
	
	/**
	 * create file(s) with SQL instructions to create SQL tables according models that have SQL serialization.
	 * should be called from CLI script.
	 *
	 * @param string $configPath comhon config file path
	 * @param string $outputPath directory where SQL files will be stored
	 * @param string $filterModelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 */
	public static function exec($configPath, $outputPath, $filterModelName = null, $recursive = false) {
		Config::setLoadPath($configPath);
		
		$self = new self(true);
		$self->transform($outputPath, $filterModelName, $recursive);
	}
    
}
