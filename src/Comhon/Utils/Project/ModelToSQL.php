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
use Comhon\Exception\Manifest\SerializationManifestIdException;
use Comhon\Serialization\SqlTable;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelForeign;
use Comhon\Object\UniqueObject;
use Comhon\Exception\ArgumentException;
use Comhon\Serialization\SerializationUnit;
use Comhon\Model\ModelContainer;
use Comhon\Model\ModelArray;
use Comhon\Model\Model;
use Comhon\Utils\Cli;
use Comhon\Utils\Model as ModelUtils;

class ModelToSQL {
	
	/**
	 * 
	 * @var \Comhon\Model\Model
	 */
	private $currentModel;
	
	/**
	 * 
	 * @var string
	 */
	private $case = 'iso';
	
	/**
	 * 
	 * @var \Comhon\Object\UniqueObject
	 */
	private $defaultSqlDatabase;
	
	/**
	 * 
	 * @var string
	 */
	private $table_ad;
	
	/**
	 * 
	 * @var array
	 */
	private $sqlModels = [];
	
	/**
	 *
	 * @param string $output
	 * @param \Comhon\Object\UniqueObject $sqlDatabase
	 * @return string
	 */
	private function initialize($output, UniqueObject $sqlDatabase) {
		if (file_exists($output)) {
			exec("rm -r $output");
		}
		mkdir($output, 0777, true);
		
		$this->defaultSqlDatabase = $sqlDatabase;
		$this->table_ad = $output. DIRECTORY_SEPARATOR . 'table';
		mkdir($this->table_ad, 0777, true);
		$databasePath = $output . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR;
		mkdir($databasePath, 0777, true);
		
		$settings = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase')->getSerializationSettings();
		$origin_table_ad = $settings->getValue('dir_path');
		$settings->setValue('dir_path', $databasePath);
		
		$sqlDatabase->save(SerializationUnit::CREATE);
		
		$settings->setValue('dir_path', $origin_table_ad);
	}
	
	/**
	 *
	 * @param Property $property
	 * @return string[]
	 */
	private function getColumnDescription(Property $property) {
		return [
			'name' => $this->getColumnName($this->currentModel, $property),
			'type' => $this->getColumnType($property)
		];
	}
	
	/**
	 * 
	 * @param \Comhon\Object\ComhonObject $table
	 * @param \Comhon\Model\Property\ForeignProperty $property
	 * @param array $foreignConstraints
	 * @return array
	 */
	private function getForeignColumnsDescriptions($table, ForeignProperty $property, array &$foreignConstraints) {
		$databaseId = $table->getValue('database')->getId();
		$foreignModelName = $property->getUniqueModel()->getName();
		
		if (
			array_key_exists($property->getUniqueModel()->getName(), $this->sqlModels)
			&& !($property->getModel()->getModel() instanceof ModelContainer)
			&& $databaseId === $this->sqlModels[$foreignModelName]['table']->getValue('database')->getId()
		) {
			$foreignTable = $this->sqlModels[$foreignModelName]['table'];
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
		
		$hasMultipleColumns = count($property->getUniqueModel()->getIdProperties()) > 1
			&& !($property->getModel()->getModel() instanceof ModelContainer);
		
		try {
			$columns = $hasMultipleColumns
				? $this->getMultipleForeignColumnDescription($property, $foreignConstraint)
				: $this->getUniqueForeignColumnDescription($property, $foreignConstraint);
		} finally {
			if (!is_null($foreignConstraint) && empty($foreignConstraint['local_columns'])) {
				array_pop($foreignConstraints[$databaseId]);
			}
		}
		
		return $columns;
	}
	
	/**
	 *
	 * @param ForeignProperty $property
	 * @param array $foreignConstraint
	 * @return string[][]
	 */
	private function getMultipleForeignColumnDescription(ForeignProperty $property, array &$foreignConstraint = null) {
		$columns = [];
		$isInstanceMultiple = ($property instanceof MultipleForeignProperty);
		
		$properties = $isInstanceMultiple 
			? $property->getMultipleIdProperties() 
			: $property->getUniqueModel()->getIdProperties();
		
		foreach ($properties as $name => $foreignIdProperty) {
			
			$column = $isInstanceMultiple 
				? ($this->case === 'iso' ? $this->escape($name) : $this->escape($this->transformString($name)))
				: $this->getColumnName($property->getUniqueModel(), $property->getUniqueModel()->getProperty($name));
			
			$columns[] = [
				'name' => $column,
				'type' => $this->getColumnType($foreignIdProperty),
			];
			
			if (!is_null($foreignConstraint)) {
				$foreignConstraint['local_columns'][] = $column;
				$foreignConstraint['foreign_columns'][] = $this->getColumnName($property->getUniqueModel(), $foreignIdProperty);
			}
		}
		return $columns;
	}
	
	/**
	 *
	 * @param ForeignProperty $property
	 * @param array $foreignConstraint
	 * @return string[][]
	 */
	private function getUniqueForeignColumnDescription(ForeignProperty $property, array &$foreignConstraint = null) {
		$idProperties = $property->getUniqueModel()->getIdProperties();
		if (empty($idProperties)) {
			throw new \Exception('foreign property not checked');
		}
		$foreignIdProperty = current($idProperties);
		
		if (!is_null($foreignConstraint)) {
			$foreignConstraint['local_columns'][] = $this->getColumnName($this->currentModel, $property);
			$foreignConstraint['foreign_columns'][] = $this->getColumnName($property->getUniqueModel(), $foreignIdProperty);
		}
		
		return [
			[
				'name' => $this->getColumnName($this->currentModel, $property),
				'type' => $this->getColumnType($foreignIdProperty),
			]
		];
	}
	
	/**
	 *
	 * @param Property $property
	 * @throws \Exception
	 * @return string
	 */
	private function getColumnType(Property $property) {
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
				switch ($this->sqlModels[$this->currentModel->getName()]['table']->getValue('database')->getValue('DBMS')) {
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
					|| (
						($property->getModel() instanceof ModelForeign) 
						&& !($property->getModel()->getModel() instanceof ModelArray)
					)
				){
					throw new \Exception('type not handled : ' . $property->getUniqueModel()->getName() . ' -> ' . get_class($property->getModel()));
				}
				// model that correspond to an object or an object array 
				// that will be JSON encoded during serialization
				// so type is text, may be JSON probably
				$type = 'TEXT';
				break;
		}
		return $type;
	}
	
	/**
	 * transform column name if needed
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param Property $property
	 * @throws \Exception
	 * @return string
	 */
	private function getColumnName(Model $model, Property $property) {
		$column = $property->getSerializationName();
		
		if (!$model->hasSerialization() && !$property->hasDefinedSerializationName() && $this->case !== 'iso') {
			$column = $this->transformString($property->getSerializationName());
		}
		return $this->escape($column);
	}
	
	/**
	 * transform string to defined case
	 * 
	 * @param string $string
	 * @throws \Exception
	 * @return string
	 */
	private function transformString($string) {
		switch ($this->case) {
			case 'camel':
				return $this->toCamelCase($string);
				break;
			case 'pascal':
				return $this->toPascalCase($string);
				break;
			case 'kebab':
				return $this->toKebabCase($string);
				break;
			case 'snake':
				return $this->toSnakeCase($string);
				break;
			default:
				throw new \Exception("invalid case $this->case");
				break;
		}
	}
	
	private function escape($column) {
		switch ($this->sqlModels[$this->currentModel->getName()]['table']->getValue('database')->getValue('DBMS')) {
			case 'mysql':
				$column = "`$column`";
				break;
			case 'pgsql':
				$column = "\"$column\"";
				break;
		}
		return $column;
	}
	
	/**
	 *
	 * @param string $string
	 * @return string
	 */
	private function toCamelCase($string) {
		$string = preg_replace_callback(
				"|([_-][a-z])|",
				function ($matches) {return strtoupper(substr($matches[1], 1));},
				$string
				);
		return lcfirst($string);
	}
	
	/**
	 * 
	 * @param string $string
	 * @return string
	 */
	private function toPascalCase($string) {
		return ucfirst($this->toCamelCase($string));
	}
	
	/**
	 *
	 * @param string $string
	 * @return string
	 */
	private function toSnakeCase($string) {
		$string = lcfirst($string);
		$string = preg_replace_callback(
			"|(?:[^A-Z]([A-Z]))|",
			function ($matches) {return strtolower(substr($matches[0], 0, 1) . '_' . $matches[1]);},
			$string
		);
		$string = preg_replace_callback(
			"|(?:[A-Z]([^A-Z]))|",
			function ($matches) {return strtolower(substr($matches[0], 0, 1) . '_' . $matches[1]);},
			$string
		);
		
		return strtolower(str_replace('-', '_', $string));
	}
	
	/**
	 *
	 * @param string $string
	 * @return string
	 */
	private function toKebabCase($string) {
		return str_replace('_', '-', $this->toSnakeCase($string));
	}
	
	private function displayContinue($message, $modelName, $propertyName = null) {
		$msgModel = "model '$modelName'";
		$msgPropertyOrModel = (is_null($propertyName) ? '' : "property '$propertyName' on ").$msgModel;
		$question = "Something goes wrong with {$msgPropertyOrModel} :".PHP_EOL
			."\033[0;31m{$message}\033[0m".PHP_EOL
			."You can stop or continue without $msgModel".PHP_EOL
			."Would you like to continue ?";
		$response = Cli::ask($question, 'yes', ['yes', 'no']);
		
		if ($response === 'no') {
			self::exit();
		} else {
			echo "\033[1;30m".$msgModel." is ignored\033[0m".PHP_EOL.PHP_EOL;
		}
	}
	
	private static function exit($message = null) {
		if (!is_null($message)) {
			echo "\033[0;31m$message\033[0m".PHP_EOL;
		}
		echo "script exited".PHP_EOL;
		exit(1);
	}
	
	/**
	 * ask to user if model must be saved in sql database
	 *
	 * @param string $modelName
	 * @return boolean
	 */
	private function mustSaveModel($modelName) {
		$question = "Model $modelName doesn't have serialization." . PHP_EOL
		."Would you like to save it in sql database ?";
		return Cli::ask($question, 'yes', ['yes', 'no']) === 'yes';
	}
	
	/**
	 * ask to user to define a table to serialize model.
	 * the sql table object is serialized in output directory.
	 * 
	 *
	 * @param string $modelName
	 * @param string[][] $modelsByTable
	 * @return \Comhon\Object\ComhonObject the sql table object
	 */
	private function getSqlTable($modelName, $modelsByTable) {
		$default = $this->toSnakeCase(str_replace('\\', '', $modelName));
		$dbId = $this->defaultSqlDatabase->getId();
		if (array_key_exists($this->getTableUniqueKeyFromName($default, $dbId), $modelsByTable)) {
			$i = 2;
			while (array_key_exists($this->getTableUniqueKeyFromName($default.'_'.$i, $dbId), $modelsByTable)) {
				$i++;
			}
			$default .= '_'.$i;
		}
		$table = Cli::ask('Enter a table name', $default);
		while (array_key_exists($this->getTableUniqueKeyFromName($table, $dbId), $modelsByTable)) {
			$table = Cli::ask('Talbe name already use, please enter a new table name', $default);
		}
		
		$settings = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getSerializationSettings();
		$origin_table_ad = $settings->getValue('dir_path');
		$settings->setValue('dir_path', $this->table_ad);
		
		$sqlTable = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getObjectInstance();
		$sqlTable->setId($table);
		$sqlTable->setValue('database', $this->defaultSqlDatabase);
		$sqlTable->save(SerializationUnit::CREATE);
		
		$settings->setValue('dir_path', $origin_table_ad);
		
		return $sqlTable;
	}
	
	/**
	 * ask to user if model must be saved in same table than its parent model,
	 * if it's the case, parent model is returned
	 *
	 * @param string $modelName
	 * @return \Comhon\Model\Model|null null if no parent model or if doesn't share table with parent model
	 */
	private function getParentModelWithSharedTable($modelName) {
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$root = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$parentModel = $model->getParent();
		$sharedTableModel = null;
		
		while ($parentModel !== $root) {
			if (array_key_exists($parentModel->getName(), $this->sqlModels)) {
				$table = $this->sqlModels[$parentModel->getName()]['table']->getId();
				$response = Cli::ask(
					"$modelName inherit from {$parentModel->getName()},".PHP_EOL."would you like to use same table ($table) ?",
					'yes',
					['yes', 'no']
				);
				if ($response == 'yes') {
					$sharedTableModel = $parentModel;
					break;
				}
			}
			$parentModel = $parentModel->getParent();
		}
		return $sharedTableModel;
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
	 * execute database file and comhon serialization files generation
	 * 
	 * @param string $outputPath folder path where database and serialization files will be exported
	 * @param \Comhon\Object\UniqueObject $sqlDatabase
	 * @param string $case case of tables and columns to use
	 * @param string $inputPath path to a folder to filter manifest to process
	 * @throws ArgumentException
	 */
	private function transform($outputPath, UniqueObject $sqlDatabase = null, $case = 'iso', $inputPath = null) {
		$this->case = is_null($case) ? 'iso' : $case;
		if (is_null($sqlDatabase)) {
			$sqlDatabase = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase')->getObjectInstance();
			$sqlDatabase->setId('generated');
			$sqlDatabase->setValue('DBMS', 'mysql');
		}
		if ($sqlDatabase->getUniqueModel()->getName() !== 'Comhon\SqlDatabase') {
			$databaseModel = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
			$expected = $databaseModel->getObjectInstance()->getComhonClass();
			throw new ArgumentException($sqlDatabase, $expected, 3);
		}
		$this->initialize($outputPath, $sqlDatabase);
		
		$databaseQueries = [];
		$foreignConstraints = [];
		$modelsByTable = [];
		$SqlableModelNames = [];
		$notValid = [];
		
		$modelNames = ModelUtils::getValidatedProjectModelNames($inputPath, true, $notValid);
		foreach ($notValid as $modelName => $message) {
			$this->displayContinue($message, $modelName);
		}
		
		// keep only "sqlable" models that have sql serialization or that doesn't have defined serialization
		foreach ($modelNames as $modelName) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if (!$model->hasSerialization() || ($model->getSerialization()->getSerializationUnit() instanceof SqlTable)) {
				$SqlableModelNames[] = $modelName;
			}
		}
		
		// important! we must have parents models before children models
		// to be sure to store highest parent model first 
		// to know if a table is already defined and if we use it for children models
		ModelUtils::sortModelNamesByInheritance($SqlableModelNames);
		
		$toDeleteModels = [];
		foreach ($SqlableModelNames as $modelName) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if (!$model->hasSerialization()) {
				continue;
			}
			$tableKey = $this->getTableUniqueKeyFromObject($model->getSerializationSettings());
			if (array_key_exists($tableKey, $modelsByTable)) {
				$existingModel = ModelManager::getInstance()->getInstanceModel($modelsByTable[$tableKey][0]);
				if (!$model->isInheritedFrom($existingModel)) {
					$several = count($modelsByTable[$tableKey]) > 1;
					$response = Cli::ask(
						"models '{$model->getname()}' and '{$existingModel->getname()}'"
						."share same serialization and no one is inherited from other,".PHP_EOL
						."but the serialization may be shared only if one model inherit from other.".PHP_EOL
						."Would you like to ?",
						null,
						[
							"continue with model {$model->getname()}",
							"continue with model".($several ? 's ' : ' ').implode(', ', $modelsByTable[$tableKey]),
							"exit"
						],
						Cli::FILTER_KEY
					);
					if ($response === '0') {
						foreach ($modelsByTable[$tableKey] as $toDeleteModelName) {
							$toDeleteModels[] = $toDeleteModelName;
						}
					} elseif ($response === '1') {
						$toDeleteModels[] = $modelName;
					} else {
						self::exit();
					}
				} else {
					$modelsByTable[$tableKey][] = $model->getName();
				}
			} else {
				$modelsByTable[$tableKey] = [$model->getName()];
			}
		}
		if (!empty($toDeleteModels)) {
			$SqlableModelNames = array_values(array_diff($SqlableModelNames, $toDeleteModels));
		}
		
		foreach ($SqlableModelNames as $modelName) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$sharedTableParentModel = null;
			
			if ($model->hasSerialization()) {
				$model->getSerializationSettings()->loadValue('database');
				$this->sqlModels[$model->getName()] = [
					'table' => $model->getSerializationSettings(),
					'model' => $model,
					'shareTableModels' => []
				];
				$sharedTableParentModel = $model->getLastSharedIdParentMatch(true);
			} elseif ($this->mustSaveModel($modelName)) {
				$sharedTableParentModel = $this->getParentModelWithSharedTable($modelName);
				$sqlTable = is_null($sharedTableParentModel)
					? $this->getSqlTable($modelName, $modelsByTable)
					: $this->sqlModels[$sharedTableParentModel->getName()]['table'];
				
				$this->sqlModels[$model->getName()] = [
					'table' => $sqlTable,
					'model' => $model,
					'shareTableModels' => []
				];
			}
			if (!is_null($sharedTableParentModel)) {
				$this->sqlModels[$sharedTableParentModel->getName()]['shareTableModels'][] = $model;
			}
			if (array_key_exists($model->getName(), $this->sqlModels)) {
				$this->sqlModels[$model->getName()]['createTable'] = is_null($sharedTableParentModel);
				$tableKey = $this->getTableUniqueKeyFromObject($this->sqlModels[$model->getName()]['table']);
				if (!array_key_exists($tableKey, $modelsByTable)) {
					$modelsByTable[$tableKey] = [];
				}
				$modelsByTable[$tableKey][] = $model->getName();
			}
		}
		
		// now models are fully loaded so we can process them
		foreach($this->sqlModels as $sqlModel) {
			if (!$sqlModel['createTable'] || count($sqlModel['model']->getProperties()) == 0) {
				continue;
			}
			$table = $sqlModel['table'];
			$this->currentModel = $sqlModel['model'];
			$primaryKey = [];
			$tableColumns = [];
			if (!array_key_exists($table->getValue('database')->getId(), $databaseQueries)) {
				$databaseQueries[$table->getValue('database')->getId()] = '';
			}
			$properties = $this->currentModel->getProperties();
			foreach ($sqlModel['shareTableModels'] as $model) {
				$properties = array_merge($properties, $model->getProperties());
			}
			
			foreach ($properties as $property) {
				if (!$property->isSerializable() || $property->isAggregation()) {
					continue;
				}
				if (
					!$this->currentModel->hasSerialization()
					&& array_key_exists($property->getUniqueModel()->getName(), $this->sqlModels)
					&& ($property->getModel() instanceof ModelContainer)
					&& ($property->getModel()->getModel() instanceof ModelArray)
				) {
					$question = "Is '{$this->getColumnName($this->currentModel, $property)}' an aggregation of '{$table->getId()}'"
						. ' (in other words not serialized as a column)?';
					
					$response = Cli::ask($question, 'no', ['yes', 'no']);
					
					if ($response === 'yes') {
						continue;
					}
				}
				
				$propertyColumns = $property->isForeign() && !($property->getModel()->getModel() instanceof ModelArray)
				? $this->getForeignColumnsDescriptions($table, $property, $foreignConstraints)
				: [$this->getColumnDescription($property)];
				
				foreach ($propertyColumns as $column) {
					$tableColumns[] = $column;
				}
				if ($property->isId()) {
					$primaryKey[] = $this->getColumnName($this->currentModel, $property);
				}
			}
			$databaseQueries[$table->getValue('database')->getId()] .= $this->getTableDefinition($table->getId(), $tableColumns, $primaryKey);
		}
		
		$foreignKeySuffix = 0;
		foreach ($foreignConstraints as $databaseId => $databaseForeignConstraints) {
			foreach ($databaseForeignConstraints as $foreignConstraint) {
				$databaseQueries[$databaseId].= $this->getForeignConstraint($foreignConstraint, $foreignKeySuffix);
			}
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
	 * @param UniqueObject $sqlTable
	 */
	private function getTableUniqueKeyFromObject(UniqueObject $sqlTable) {
		return $this->getTableUniqueKeyFromName($sqlTable->getId(), $sqlTable->getValue('database')->getId());
	}
	
	/**
	 *
	 * @param UniqueObject $sqlTable
	 */
	private function getTableUniqueKeyFromName($tableName, $databaseId) {
		return $tableName.'_'.$databaseId;
	}
	
	/**
	 * execute database file and comhon serialization files generation
	 * options are taken from script arguments
	 * 
	 * @param string $outputPath folder path where database and serialization files will be exported
	 * @param string $configPath comhon config file path
	 * @param string $case case of tables and columns to use
	 * @param string $database database connection informations
	 * @param string $inputPath path to a folder to filter manifest to process
	 */
	public static function exec($outputPath, $configPath, $case = 'iso', $database = null, $inputPath = null) {
		Config::setLoadPath($configPath);
		
		if (!is_null($database)) {
			$infos = explode(':', $database);
			
			if (count($infos) > 7 || $infos < 6) {
				throw new \Exception('malformed database description : '.$database);
			}
			
			$sqlDatabase = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase')->getObjectInstance();
			$sqlDatabase->setId($infos[0]);
			$sqlDatabase->setValue('DBMS', $infos[1]);
			$sqlDatabase->setValue('host', $infos[2]);
			$sqlDatabase->setValue('name', $infos[3]);
			$sqlDatabase->setValue('user', $infos[4]);
			$sqlDatabase->setValue('password', $infos[5]);
			if (isset($infos[6])) {
				$sqlDatabase->setValue('port', (integer) $infos[6]);
			}
		} else {
			$sqlDatabase = null;
		}
		
		$modelToSQL = new self();
		try {
			$modelToSQL->transform($outputPath, $sqlDatabase, $case, $inputPath);
		} catch (\Exception $e) {
			self::exit($e->getMessage());
		}
	}
    
}
