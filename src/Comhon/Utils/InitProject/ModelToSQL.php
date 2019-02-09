<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils\InitProject;

use Comhon\Object\Object;
use Comhon\Object\Config\Config;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\SerializationManifestIdException;
use Comhon\Serialization\SqlTable;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Model\Property\Property;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Model\SimpleModel;
use Comhon\Model\ModelForeign;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\ArgumentException;
use Comhon\Utils\OptionManager;
use Comhon\Serialization\SerializationUnit;
use Comhon\Model\Model;
use Comhon\Model\ModelContainer;
use Comhon\Model\ModelArray;
use Comhon\Model\ModelComplex;

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
	 * @var \Comhon\Object\ObjectUnique
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
	 * @param \Comhon\Object\ObjectUnique $sqlDatabase
	 * @return string
	 */
	private function initialize($output, ObjectUnique $sqlDatabase) {
		if (file_exists($output)) {
			exec("rm -r $output");
		}
		mkdir($output);
		
		$this->defaultSqlDatabase = $sqlDatabase;
		$this->table_ad = $output. '/table';
		mkdir($this->table_ad);
		$databasePath = $output . '/database/';
		mkdir($databasePath);
		
		$settings = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase')->getSerializationSettings();
		$origin_table_ad = $settings->getValue('saticPath');
		$settings->setValue('saticPath', $databasePath);
		
		$sqlDatabase->save(SerializationUnit::CREATE);
		
		$settings->setValue('saticPath', $origin_table_ad);
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
	 * @param \Comhon\Object\Object $table
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
			&& array_key_exists($property->getUniqueModel()->getName(), $this->sqlModels)
			&& !($property->getModel()->getModel() instanceof ModelContainer);
		
		return $hasMultipleColumns
			? $this->getMultipleForeignColumnDescription($property, $foreignConstraint)
			: $this->getUniqueForeignColumnDescription($property, $foreignConstraint);
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
			: $property->getModel()->getIdProperties();
		
		foreach ($properties as $name => $foreignIdProperty) {
			
			$column = $isInstanceMultiple 
				? ($this->case === 'iso' ? $this->escape($name) : $this->escape($this->transformString($name)))
				: $this->getColumnName($property->getModel(), $property->getModel()->getProperty($name));
			
			$columns[] = [
				'name' => $column,
				'type' => $this->getColumnType($foreignIdProperty),
			];
			
			if (!is_null($foreignConstraint)) {
				$foreignConstraint['local_columns'][] = $column;
				$foreignConstraint['foreign_columns'][] = $this->getColumnName($property->getModel(), $foreignIdProperty);
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
		$foreignIdProperty = current($idProperties);
		
		if (!is_null($foreignConstraint)) {
			$foreignConstraint['local_columns'][] = $this->getColumnName($this->currentModel, $property);
			$foreignConstraint['foreign_columns'][] = $this->getColumnName($property->getModel(), $foreignIdProperty);
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
				$type = 'INT';
				break;
			case 'float':
				$type = 'FLOAT';
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
	private function getColumnName(ModelComplex $model, Property $property) {
		$column = $property->getSerializationName();
		
		if ($model instanceof ModelForeign) {
			$model = $model->getUniqueModel();
		}
		if (!$model->hasSerialization() && !$property->hasDefinedSerializationName() && $this->case !== 'iso') {
			$column = $this->transformString($property->getSerializationName());
		}
		return $this->escape($column);
	}
	
	/**
	 * transform string to defined case
	 * 
	 * @param unknown $string
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
	
	/**
	 * get model only if model has sql serialization or doesn't have serialisation
	 *
	 * @param string $modelName
	 * @return \Comhon\Model\Model|null
	 *     return null if model has serialization different than sql serialisation
	 */
	private function getModel($modelName) {
		$model = null;
		try {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
		} catch(SerializationManifestIdException $e) {
			if ($e->getType() == 'Comhon\SqlTable') {
				$settings = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getSerializationSettings();
				$origin_table_ad = $settings->getValue('saticPath');
				$settings->setValue('saticPath', $this->table_ad);
				
				$sqlTable = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getObjectInstance();
				$sqlTable->setId($e->getId());
				$sqlTable->setValue('database', $this->defaultSqlDatabase);
				$sqlTable->save(SerializationUnit::CREATE);
				
				$model = ModelManager::getInstance()->getInstanceModel($modelName);
				$settings->setValue('saticPath', $origin_table_ad);
			}
		} catch(\Exception $e) {
			$this->displayContinue($e, $modelName);
		}
		if (!is_null($model) && $model->hasSerialization() && !($model->getSerialization() instanceof SqlTable)) {
			$model = null;
		}
		return $model;
	}
	
	private function displayContinue(\Exception $e, $modelName, $propertyName = null) {
		$msgProperty = is_null($propertyName) ? ' ' : " property '$propertyName' on ";
		$instruction = "Something goes wrong with{$msgProperty}model '$modelName' ({$e->getMessage()})." . PHP_EOL
		."Would you like to continue ? [y/n]" . PHP_EOL;
		do {
			echo $instruction;
			$response = trim(fgets(STDIN));
			$instruction = "Invalid response. Again, would you like to continue ? [y/n]" . PHP_EOL;
		} while ($response !== 'y' && $response !== 'n');
		
		if ($response === 'n') {
			echo "script exited" . PHP_EOL;
			exit(1);
		}
	}
	
	/**
	 * permit to define a table to serialize model
	 *
	 * @param string $modelName
	 * @return string|null
	 */
	private function defineTable($modelName) {
		$defaultTableName = $this->toSnakeCase(str_replace('\\', '', $modelName));
		$instruction = "Model $modelName doesn't have serialization." . PHP_EOL
		."Would you like to save it in sql database ? [y/n]" . PHP_EOL;
		do {
			echo $instruction;
			$response = trim(fgets(STDIN));
			$instruction = "Invalid response. Again, would you like to save it in sql database ? [y/n]" . PHP_EOL;
		} while ($response !== 'y' && $response !== 'n');
		
		if ($response == 'y') {
			echo "Enter a table name (default name : $defaultTableName) : " . PHP_EOL;
			$response = trim(fgets(STDIN));
			$table = empty($response) ? $defaultTableName: $response;
			
			$settings = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getSerializationSettings();
			$origin_table_ad = $settings->getValue('saticPath');
			$settings->setValue('saticPath', $this->table_ad);
			
			$sqlTable = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getObjectInstance();
			$sqlTable->setId($table);
			$sqlTable->setValue('database', $this->defaultSqlDatabase);
			$sqlTable->save(SerializationUnit::CREATE);
			
			$settings->setValue('saticPath', $origin_table_ad);
		}
		else {
			$table = null;
		}
		return $table;
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
	 * @return string
	 */
	private function getForeignConstraint($foreignConstraint) {
		return sprintf(
			PHP_EOL . "ALTER TABLE %s" . PHP_EOL
			."    ADD CONSTRAINT fk_%s_%s" . PHP_EOL
			."    FOREIGN KEY (%s) REFERENCES %s(%s);" . PHP_EOL,
			$foreignConstraint['local_table'],
			str_replace('.', '_', $foreignConstraint['local_table']),
			str_replace('.', '_', $foreignConstraint['foreign_table']),
			implode(', ', $foreignConstraint['local_columns']),
			$foreignConstraint['foreign_table'],
			implode(', ', $foreignConstraint['foreign_columns'])
		);
	}
	
	
	/**
	 * execute database file and comhon serialization files generation
	 * 
	 * @param string $outputPath folder path where database and serialization files will be exported
	 * @param unknown $configPath comhon config file path
	 * @param \Comhon\Object\ObjectUnique $sqlDatabase
	 * @throws ArgumentException
	 */
	private function transform($outputPath, $configPath, ObjectUnique $sqlDatabase = null, $case = 'iso') {
		Config::setLoadPath($configPath);
		
		$this->case = $case;
		if (is_null($sqlDatabase)) {
			$sqlDatabase = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase')->getObjectInstance();
			$sqlDatabase->setId('generated');
		}
		if ($sqlDatabase->getUniqueModel()->getName() !== 'Comhon\SqlDatabase') {
			$databaseModel = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
			$expected = $databaseModel->getObjectInstance()->getComhonClass();
			throw new ArgumentException($database, $expected, 3);
		}
		$this->initialize($outputPath, $sqlDatabase);
		
		$databaseQueries = [];
		$sqlTables = [];
		$foreignConstraints = [];
		
		$tableNames = [];
		foreach (Config::getInstance()->getManifestAutoloadList() as $namespace => $path) {
			$manifest_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $path;
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($manifest_ad), \RecursiveIteratorIterator::SELF_FIRST);
			
			/**
			 * @var SplFileInfo $object
			 */
			foreach($objects as $name => $object) {
				if (!is_dir($name) || $object->getBasename() === '.' || $object->getBasename() === '..') {
					continue;
				}
				$modelName = $namespace . '\\' . substr(str_replace(DIRECTORY_SEPARATOR, '\\', str_replace($manifest_ad, '', $name)), 1);
				$model = $this->getModel($modelName);
				
				if (!is_null($model)) {
					if ($model->hasSerialization()) {
						$model->getSerializationSettings()->loadValue('database');
						$this->sqlModels[$model->getName()] = [
							'table' => $model->getSerializationSettings(),
							'model' => $model
						];
					} elseif (!is_null($table = $this->defineTable($modelName))) {
						$serializationSettings = new Object('Comhon\SqlTable');
						$serializationSettings->setId($table);
						$serializationSettings->setValue('database', $this->defaultSqlDatabase);
						
						$this->sqlModels[$model->getName()] = [
							'table' => $serializationSettings,
							'model' => $model
						];
					}
					if (array_key_exists($model->getName(), $this->sqlModels)) {
						$tableName = $this->sqlModels[$model->getName()]['table']->getId();
						if (array_key_exists($tableName, $tableNames)) {
							$instruction = "Duplicated table '{$tableName}'."
								. ' Would you like to create table for :' . PHP_EOL
								. "(1) model '{$tableNames[$tableName]}', " . PHP_EOL
								. "(2) model '{$model->getName()}', " . PHP_EOL
								. '(0) or both ?' . PHP_EOL
								. 'Please type one of following responses [1/2/0]'. PHP_EOL;
							do {
								echo $instruction;
								$response = trim(fgets(STDIN));
								$instruction = 'Invalid response. Please type one of following responses [1/2/0]' . PHP_EOL;
							} while ($response !== '0' && $response !== '1' && $response !== '2');
							
							if ($response === '1') {
								unset($this->sqlModels[$model->getName()]);
							} elseif ($response === '2') {
								unset($this->sqlModels[$tableNames[$tableName]]);
								$tableNames[$tableName] = $model->getName();
							}
						} else {
							$tableNames[$tableName] = $model->getName();
						}
					}
				}
			}
		}
		
		// now models are fully loaded so we can process them
		foreach($this->sqlModels as $sqlModel) {
			$table = $sqlModel['table'];
			$this->currentModel = $sqlModel['model'];
			$primaryKey = [];
			$tableColumns = [];
			if (!array_key_exists($table->getValue('database')->getId(), $databaseQueries)) {
				$databaseQueries[$table->getValue('database')->getId()] = '';
			}
			
			foreach ($this->currentModel->getProperties() as $property) {
				if (!$property->isSerializable() || $property->isAggregation()) {
					continue;
				}
				try {
					if (
						array_key_exists($property->getUniqueModel()->getName(), $this->sqlModels)
						&& ($property->getModel() instanceof ModelContainer)
						&& ($property->getModel()->getModel() instanceof ModelArray)
					) {
						$instruction = "Is '{$this->getColumnName($this->currentModel, $property)}' an aggregation of '{$table->getId()}'"
						. ' (in other words not serialized as a column)? [y/n]' . PHP_EOL;
						do {
							echo $instruction;
							$response = trim(fgets(STDIN));
							$instruction = "Invalid response. Is it an aggregation ? [y/n]" . PHP_EOL;
						} while ($response !== 'y' && $response !== 'n');
						
						if ($response === 'y') {
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
				} catch (\Exception $e) {
					$this->displayContinue($e, $this->currentModel->getName(), $property->getName());
				}
			}
			$databaseQueries[$table->getValue('database')->getId()] .= $this->getTableDefinition($table->getId(), $tableColumns, $primaryKey);
		}
		
		
		foreach ($foreignConstraints as $databaseId => $databaseForeignConstraints) {
			foreach ($databaseForeignConstraints as $foreignConstraint) {
				$databaseQueries[$databaseId].= $this->getForeignConstraint($foreignConstraint);
			}
		}
		if (count($databaseQueries) > 1) {
			foreach ($databaseQueries as $databaseId => $databaseQuery) {
				file_put_contents($outputPath . "/database-$databaseId.sql", $databaseQuery);
			}
		} else {
			file_put_contents($outputPath . "/database.sql", current($databaseQueries));
		}
	}
	
	/**
	 * execute database file and comhon serialization files generation
	 * options are taken from script arguments
	 * 
	 * @param string $outputPath folder path where database and serialization files will be exported
	 * @param unknown $configPath comhon config file path
	 */
	public static function exec($outputPath, $configPath) {
		$optionManager = new OptionManager();
		$optionManager->register_option_desciption(self::getOptionsDescriptions());
		if ($optionManager->has_help_argument_option()) {
			echo $optionManager->get_help();
			exit(0);
		}
		
		$options = $optionManager->get_options();
		$case = isset($options['case']) ? $options['case'] : 'iso';
		Config::setLoadPath($configPath);
		
		if (isset($options['database'])) {
			$infos = explode(':', $options['database']);
			
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
		
		// TODO testPrivateId -> foreignObjectValues INT -> text
		$modelToSQL = new self();
		$modelToSQL->transform($outputPath, $configPath, $sqlDatabase, $case);
	}
	
	/**
	 * 
	 * @return array
	 */
	private static function getOptionsDescriptions() {
		return [
			'database' => [
				'short' => 'd',
				'long' => 'database',
				'has_value' => true,
				'description' => 'Database informations',
				'pattern' => '^([^:]+:){5,6}[^:]+$',
				'long_description' =>
					'Database informations that will be used for models without serialization.' . PHP_EOL .
					'Value must match with following patterns : ' . PHP_EOL .
					'id:DBMS:host:name:user:password or id:DBMS:host:name:user:password:port' . PHP_EOL .
					' - id is your database identifier that will be used in Comhon framework' . PHP_EOL .
					' - DBMS is your database management system' . PHP_EOL .
					' - host is your database host' . PHP_EOL .
					' - name is your database name' . PHP_EOL .
					' - user is your database user name' . PHP_EOL .
					' - password is your database password' . PHP_EOL .
					' - port is your database port (optional)'
			],
			'case' => [
				'short' => 'c',
				'long' => 'case',
				'has_value' => true,
				'enum' => ['camel', 'pascal', 'kebab', 'snake'],
				'description' => 'column name\'s case',
			]
		];
	}
    
}
