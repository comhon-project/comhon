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
use Comhon\Model\Property\Property;
use Comhon\Model\ModelForeign;
use Comhon\Object\UniqueObject;
use Comhon\Exception\ArgumentException;
use Comhon\Serialization\SerializationUnit;
use Comhon\Model\ModelArray;
use Comhon\Model\Model;
use Comhon\Utils\Cli;
use Comhon\Utils\Model as ModelUtils;
use Comhon\Object\ComhonObject;
use Comhon\Utils\Utils;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;

class ModelSqlSerializer {
	
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
	private $defaultMustSave = 'no';
	
	/**
	 *
	 * @var string
	 */
	private $isInteractive = true;
	
	public function __construct($isInteractive = true) {
		$this->isInteractive = $isInteractive;
	}
	
	/**
	 * 
	 * @see \Comhon\Utils\Cli::ask()
	 */
	public function ask($question, $default = null, $filter = null, $filterType = Cli::FILTER_VALUE) {
		if ($this->isInteractive) {
			return Cli::ask($question, $default, $filter, $filterType);
		} elseif (!is_null($default)) {
			return $default;
		}
		throw new \Exception('interactive mode is desactivated and question doesn\'t have default response');
	}
	
	/**
	 *
	 * @param string $output
	 * @param \Comhon\Object\UniqueObject $sqlDatabase
	 * @return string
	 */
	private function initialize(UniqueObject $sqlDatabase) {
		$this->defaultSqlDatabase = $sqlDatabase;
		
		$sqlDatabase = new ComhonObject($this->defaultSqlDatabase->getModel(), false);
		$sqlDatabase->setId($this->defaultSqlDatabase->getId());
		if ($sqlDatabase->load()) {
			if ($this->defaultSqlDatabase->getValues() != $sqlDatabase->getValues()) {
				throw new \Exception(
					"confict between given sql database setting and existing one with id '{$this->defaultSqlDatabase->getId()}'"
				);
			}
		} else {
			$this->defaultSqlDatabase->save(SerializationUnit::CREATE);
		}
		
	}
	
	/**
	 *
	 * @param string $output
	 * @param \Comhon\Object\UniqueObject $sqlDatabase
	 * @return string
	 */
	private function serializeSqlTable(UniqueObject $sqlTable) {
		
		$LoadedSqlTable = new ComhonObject($sqlTable->getModel(), false);
		$LoadedSqlTable->setId($sqlTable->getId());
		if ($LoadedSqlTable->load()) {
			if (
				$LoadedSqlTable->getValue('name') !== $sqlTable->getValue('name')
				|| $LoadedSqlTable->getValue('database')->getId() !== $sqlTable->getValue('database')->getId()
			) {
				throw new \Exception(
						"table cannot be serialized, there is a confict with existing table with id '{$sqlTable->getId()}'"
				);
			}
		} else {
			$sqlTable->save(SerializationUnit::CREATE);
		}
		
	}
	
	/**
	 * transform string to defined case. 
	 * 
	 * @param string $string
	 * @param string $suffix if $suffix is provided, $suffix is concatenate to $string
	 * @throws \Exception
	 * @return string
	 */
	private function transformString($string, $suffix = '') {
		switch ($this->case) {
			case 'camel':
				return Utils::toCamelCase($string.ucfirst($suffix));
				break;
			case 'pascal':
				return Utils::toPascalCase($string.ucfirst($suffix));
				break;
			case 'snake':
				return Utils::toSnakeCase($string.($suffix == '' ? '' : '_'.$suffix));
				break;
			case 'kebab':
				return Utils::toKebabCase($string.($suffix == '' ? '' : '-'.$suffix));
				break;
			default:
				throw new \Exception("invalid case $this->case");
				break;
		}
	}
	
	/**
	 *
	 * @param string $message
	 * @param string $modelName
	 * @param string $propertyName
	 */
	private function displayMessage($message) {
		if ($this->isInteractive) {
			fwrite(Cli::$STDOUT, $message);
		}
	}
	
	/**
	 * 
	 * @param string $message
	 * @param string $modelName
	 * @param string $propertyName
	 */
	private function displayContinue($message, $modelName, $propertyName = null) {
		$msgModel = "model '$modelName'";
		$msgPropertyOrModel = (is_null($propertyName) ? '' : "property '$propertyName' on ").$msgModel;
		$question = "Something goes wrong with {$msgPropertyOrModel} :".PHP_EOL
			."\033[0;31m{$message}\033[0m".PHP_EOL
			."You can stop or continue without $msgModel".PHP_EOL
			."Would you like to continue ?";
		$response = $this->ask($question, 'no', ['yes', 'no']);
		
		if ($response === 'no') {
			throw new \Exception($message);
		} else {
			$this->displayMessage("\033[1;30m".$msgModel." is ignored\033[0m".PHP_EOL.PHP_EOL);
		}
	}
	
	/**
	 * 
	 * @param string $modelName
	 * @param string $part
	 */
	private function displayProcessingModel($modelName, $part = null) {
		if (empty($part)) {
			$this->displayMessage("\033[0;93mProcessing model \033[1;33m'{$modelName}'\033[0m".PHP_EOL);
		} else {
			$this->displayMessage("\033[0;93mProcessing \033[1;33m{$part}\033[0;93m of model \033[1;33m'{$modelName}'\033[0m".PHP_EOL);
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
	 * create and update serialization manifest files for objects that have to be serizalized in sql database
	 * 
	 * @param \Comhon\Object\UniqueObject $sqlDatabase
	 * @param string $case case of tables and columns to use
	 * @param string $modelNameFilter filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 * @throws ArgumentException
	 */
	public function registerSerializations(UniqueObject $sqlDatabase, $case = 'iso', $modelNameFilter = null, $recursive = false) {
		$this->case = is_null($case) ? 'iso' : $case;
		
		if (!$sqlDatabase->isA('Comhon\SqlDatabase')) {
			$databaseModel = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
			$expected = $databaseModel->getObjectInstance()->getComhonClass();
			throw new ArgumentException($sqlDatabase, $expected, 3);
		}
		$this->initialize($sqlDatabase);
		
		$notValid = [];
		$projectModelNames = ModelUtils::getValidatedProjectModelNames(null, true, $notValid);
		if (!is_null($modelNameFilter)) {
			if (in_array($modelNameFilter, $projectModelNames)) {
				$WantedModelNames = [$modelNameFilter => null];
			} else {
				$WantedModelNames = [];
			}
			if ($recursive) {
				foreach ($projectModelNames as $projectModelName) {
					if (strpos($projectModelName, $modelNameFilter) === 0) {
						$WantedModelNames[$projectModelName] = null;
					}
				}
			} else {
				$this->defaultMustSave = 'yes';
				// verify if model exists
				ModelManager::getInstance()->getInstanceModel($modelNameFilter);
			}
		} else {
			$WantedModelNames = array_flip($projectModelNames);
		}
		foreach ($notValid as $modelName => $message) {
			if (is_null($modelNameFilter)
				|| ($recursive && strpos($modelName, $modelNameFilter) === 0)
				|| (!$recursive && $modelName === $modelNameFilter)
			) {
				$this->displayContinue($message, $modelName);
			}
		}
		
		// important! we must have parents models before children models
		// to be sure to store highest parent model first
		// to know if a table is already defined and if we use it for children models
		ModelUtils::sortModelNamesByInheritance($projectModelNames);
		
		$modelsInfos = $this->initModelsInfos($projectModelNames, $WantedModelNames);
		$tablesInfos = $this->initTablesInfos($modelsInfos);
		$this->addModelsToSerialize($modelsInfos, $tablesInfos);
		$this->initTablesColumns($tablesInfos);
		
		$this->initSerializationManifests($modelsInfos, $tablesInfos);
		$this->addSerializationNames($modelsInfos, $tablesInfos);
		$this->updateInheritanceKey($modelsInfos, $tablesInfos);
		$this->addSerializationSettings($modelsInfos, $tablesInfos);
		
		foreach($modelsInfos as $modelName => $modelInfos) {
			if (isset($modelInfos['serialization_manifest']) && $modelInfos['serialization_manifest']->isUpdated()) {
				$modelInfos['serialization_manifest']->save(SerializationUnit::CREATE);
			}
		}
	}
	
	/**
	 * initialize informations map for all models with defined sql serialization or without serizaltion file
	 * 
	 * @param string[] $projectModelNames
	 * @param array $WantedModelNames
	 * @return array
	 */
	private function initModelsInfos($projectModelNames, $WantedModelNames) {
		$modelsInfos = [];
		$root = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		
		foreach ($projectModelNames as $modelName) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			list($prefix, $suffix) = ModelManager::getInstance()->splitModelName($modelName);
			$manifestPath =  ModelManager::getInstance()->getManifestPath($prefix, $suffix);
			$path = ModelManager::getInstance()->getSerializationManifestPath($manifestPath, $prefix, $suffix);
			
			if (file_exists($path)) {
				if ($model->hasSqlTableSerialization()) {
					$model->getSerializationSettings()->loadValue('database');
					$modelsInfos[$model->getName()] = [
						'table' => $model->getSerializationSettings(),
						'create' => false,
						'has_serialization_file' => true,
						'sqlModelChildren' => []
					];
					
					$parentModel = $model->getParent();
					while ($parentModel !== $root) {
						if (array_key_exists($parentModel->getName(), $modelsInfos)) {
							$modelsInfos[$parentModel->getName()]['sqlModelChildren'][] = $modelName;
						}
						$parentModel = $parentModel->getParent();
					}
				}
			} elseif (array_key_exists($modelName, $WantedModelNames)) {
				$modelsInfos[$model->getName()] = [
					'create' => false,
					'has_serialization_file' => false,
					'sqlModelChildren' => []
				];
			}
		}
		return $modelsInfos;
	}
	
	
	
	/**
	 * initialize informations map for all models with defined sql serialization or without serizaltion file
	 *
	 * @param array $modelsInfos
	 * @param array $WantedModelNames
	 * @return array
	 */
	private function initTablesInfos($modelsInfos) {
		$tablesInfos = [];
		foreach ($modelsInfos as $modelName => $modelInfos) {
			if (!array_key_exists('table', $modelInfos)) {
				continue;
			}
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$tableKey = $this->getTableUniqueKeyFromObject($model->getSerializationSettings());
			if (!array_key_exists($tableKey, $tablesInfos)) {
				$tablesInfos[$tableKey] = [
					'modelNames' => [],
					'inheritance_key' => null,
					'columns' => [],
				];
			}
			$tablesInfos[$tableKey]['modelNames'][] = $modelName;
			$tablesInfos[$tableKey]['inheritance_key'] = $model->getSerialization()->getInheritanceKey();
		}
		return $tablesInfos;
	}
	
	/**
	 * ask user if models must be serialized in sql database.
	 * update $modelsInfos and $tablesInfos for each model to serialize.
	 * 
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function addModelsToSerialize(&$modelsInfos, &$tablesInfos) {
		$root = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		foreach ($modelsInfos as $modelName => &$modelInfos) {
			if (isset($modelInfos['table'])) {
				continue;
			}
			$this->displayProcessingModel($modelName);
			
			$sharedTableModel = $this->getModelWithSharedTable($modelName, $modelsInfos);
			if (is_null($sharedTableModel) && !$this->mustDefineSqlSerialization($modelName)) {
				continue;
			}
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$sqlTable = is_null($sharedTableModel)
				? $this->getSqlTable($modelName, $tablesInfos)
				: $this->getDefinedSqlTable($sharedTableModel, $modelsInfos);
			
			$modelInfos['table'] = $sqlTable;
			$modelInfos['create'] = true;
			$modelInfos['register_serialization'] = $sharedTableModel !== $model->getParent();
			
			$parentModel = $model->getParent();
			while ($parentModel !== $root) {
				if (array_key_exists($parentModel->getName(), $modelsInfos)) {
					$modelsInfos[$parentModel->getName()]['sqlModelChildren'][] = $modelName;
				}
				$parentModel = $parentModel->getParent();
			}
			$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
			if (!array_key_exists($tableKey, $tablesInfos)) {
				$tablesInfos[$tableKey] = [
					'modelNames' => [],
					'inheritance_key' => null,
					'columns' => [],
				];
			}
			$tablesInfos[$tableKey]['modelNames'][] = $modelName;
		}
	}
	
	/**
	 * ask to user if model must have an attached serialization
	 *
	 * @param string $modelName
	 * @return boolean
	 */
	private function mustDefineSqlSerialization($modelName) {
		$question = "Model $modelName doesn't have serialization." . PHP_EOL
		."Would you like to save it in sql database ?";
		return $this->ask($question, $this->defaultMustSave, ['yes', 'no']) === 'yes';
	}
	
	/**
	 * ask to user if model must be saved in same table than its parent or inherited model,
	 * if it's the case, the model is returned
	 *
	 * @param string $modelName
	 * @param array $modelsInfos
	 * @param string[] $modelsChildren
	 * @return \Comhon\Model\Model|null null if no parent model or if doesn't share table with parent model
	 */
	private function getModelWithSharedTable($modelName, $modelsInfos) {
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$root = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
		$parentModel = $model->getParent();
		$sharedTableModel = null;
		$visitedTables = [];
		
		while ($parentModel !== $root) {
			$sqlTable = $this->getDefinedSqlTable($parentModel, $modelsInfos);
			if (!is_null($sqlTable)) {
				$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
				if (!array_key_exists($tableKey, $visitedTables)) {
					$response = $this->ask(
							"$modelName inherit from {$parentModel->getName()},".PHP_EOL.
							"would you like to use same table ({$sqlTable->getValue('name')}) ?",
							'yes',
							['yes', 'no']
					);
					if ($response == 'yes') {
						$sharedTableModel = $parentModel;
						break;
					}
					$visitedTables[$tableKey] = null;
				}
			}
			$parentModel = $parentModel->getParent();
		}
		if (is_null($sharedTableModel)) {
			foreach ($modelsInfos[$modelName]['sqlModelChildren'] as $modelNameChild) {
				$modelChild = ModelManager::getInstance()->getInstanceModel($modelNameChild);
				$sqlTable = $modelChild->getSerializationSettings();
				$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
				if (!array_key_exists($tableKey, $visitedTables)) {
					$response = $this->ask(
							"{$modelChild->getName()} inherit from $modelName,".PHP_EOL.
							"would you like to use same table ({$sqlTable->getValue('name')}) ?",
							'yes',
							['yes', 'no']
					);
					if ($response == 'yes') {
						$sharedTableModel = $modelChild;
						break;
					}
					$visitedTables[$tableKey] = null;
				}
			}
		}
		return $sharedTableModel;
	}
	
	/**
	 * ask to user to define a table to serialize model.
	 * the sql table object is serialized in output directory.
	 *
	 *
	 * @param string $modelName
	 * @param string[][] $tablesInfos
	 * @return \Comhon\Object\ComhonObject the sql table object
	 */
	private function getSqlTable($modelName, $tablesInfos) {
		$default = Utils::toSnakeCase(str_replace('\\', '', $modelName));
		$dbId = $this->defaultSqlDatabase->getId();
		if (array_key_exists($this->getTableUniqueKeyFromName($default, $dbId), $tablesInfos)) {
			$i = 2;
			while (array_key_exists($this->getTableUniqueKeyFromName($default.'_'.$i, $dbId), $tablesInfos)) {
				$i++;
			}
			$default .= '_'.$i;
		}
		$message = 'Enter a table name';
		do {
			$table = $this->ask($message, $default);
			$message = 'Table name already used, please enter a new table name';
		} while (array_key_exists($this->getTableUniqueKeyFromName($table, $dbId), $tablesInfos));
		
		$sqlTable = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getObjectInstance();
		$sqlTable->setValue('name', $table);
		$sqlTable->setValue('database', $this->defaultSqlDatabase);
		$this->serializeSqlTable($sqlTable);
		
		return $sqlTable;
	}
	
	/**
	 * get sql table from either model serialization or $modelsInfos
	 *
	 * @param \Comhon\Model\Model $model
	 * @param array $modelsInfos
	 * @return \Comhon\Object\ComhonObject|null null if model doesn't have defined sql table serialization
	 */
	private function getDefinedSqlTable(Model $model, $modelsInfos) {
		return isset($modelsInfos[$model->getName()]['table'])
		? $modelsInfos[$model->getName()]['table']
		: ($model->hasSqlTableSerialization() ? $model->getSerializationSettings() : null);
	}
	
	/**
	 * initialize columns of each table with existing informations
	 * 
	 * @param array $tablesInfos
	 */
	private function initTablesColumns(&$tablesInfos) {
		foreach ($tablesInfos as &$tableInfos) {
			foreach ($tableInfos['modelNames'] as $modelName) {
				$model = ModelManager::getInstance()->getInstanceModel($modelName);
				foreach ($model->getSerializableProperties() as $property) {
					if ($property->isAggregation()) {
						continue;
					}
					if ($property instanceof MultipleForeignProperty) {
						foreach ($property->getMultipleIdProperties() as $serializationName => $property) {
							$tableInfos['columns'][$serializationName] = null;
						}
					} else {
						$tableInfos['columns'][$property->getSerializationName()] = null;
					}
				}
			}
		}
	}
	
	/**
	 * initialize serialization manifest for all models that must be serialized in database
	 * 
	 * @param array $modelsInfos
	 */
	private function initSerializationManifests(&$modelsInfos) {
		foreach($modelsInfos as $modelName => &$modelInfos) {
			if (!$modelInfos['create']) {
				continue;
			}
			$serializationManifest = new ComhonObject('Comhon\Serialization', false);
			$serializationManifest->setId($modelName, false);
			$serializationManifest->setValue('version', '3.0', false);
			$modelInfos['serialization_manifest'] = $serializationManifest;
		}
	}
	
	/**
	 * add properties serialization names on manifest serializations.
	 * tables columns in $tablesInfos may be updated if new serialization names are set.
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function addSerializationNames(&$modelsInfos, &$tablesInfos) {
		foreach($modelsInfos as $modelName => $modelInfos) {
			if (!$modelInfos['create']) {
				continue;
			}
			$this->displayProcessingModel($modelName, 'properties');
			
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$serializationManifest = $modelInfos['serialization_manifest'];
			$sqlTable = $modelInfos['table'];
			$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
			$columns = &$tablesInfos[$tableKey]['columns'];
			
			/** @var \Comhon\Model\Property\Property[] $properties */
			$properties = array_diff_key($model->getProperties(), $model->getParent()->getProperties());
			
			foreach ($properties as $property) {
				$serializationProperty = null;
				
				$aggregation = $this->getAggregation($model, $property, $modelsInfos);
				if (!empty($aggregation)) {
					$serializationProperty = new ComhonObject('Comhon\Serialization\Property');
					$serializationAggregation = $serializationProperty->initValue('aggregations', false);
					foreach ($aggregation as $propertyName) {
						$serializationAggregation->pushValue($propertyName);
					}
					unset($columns[$property->getName()]);
				} elseif ($this->ask("is property '{$property->getName()}' serializable", 'yes', ['yes', 'no']) === 'yes') {
					if ($this->isMultipleIdForeignProperty($property)) {
						$names = $this->getSerializationNames($property);
						$serializationProperty = new ComhonObject('Comhon\Serialization\Property');
						$serializationNames = $serializationProperty->initValue('serialization_names', false);
						foreach ($names as $propertyName => $serializationName) {
							$serializationNames->setValue($propertyName, $serializationName);
							$columns[$serializationName] = null;
						}
						unset($columns[$property->getName()]);
					} else {
						$serializationName = $this->getSerializationName($property);
						if (!is_null($serializationName)) {
							$serializationProperty = new ComhonObject('Comhon\Serialization\Property');
							$serializationProperty->setValue('serialization_name', $serializationName);
							unset($columns[$property->getName()]);
							$columns[$serializationName] = null;
						}
					}
				} else {
					$serializationProperty = new ComhonObject('Comhon\Serialization\Property');
					$serializationProperty->setValue('is_serializable', false);
					unset($columns[$property->getName()]);
				}
				if (!is_null($serializationProperty)) {
					if (!$serializationManifest->issetValue('properties')) {
						$serializationManifest->initValue('properties');
					}
					$serializationManifest->getValue('properties')->setValue($property->getName(), $serializationProperty);
				}
			}
		}
	}
	
	/**
	 * update exsting serialization manifests by adding inhertiance key if needed
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function updateInheritanceKey(&$modelsInfos, &$tablesInfos) {
		switch (Config::getInstance()->getManifestFormat()) {
			case 'xml':
				$interfacer = new XMLInterfacer();
				break;
			case 'json':
				$interfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('manifest format not managed : '.Config::getInstance()->getManifestFormat());
		}
		foreach($tablesInfos as $tableKey => $tableInfos) {
			if (!$this->isInheritanceKeyNeeded($tableInfos, $modelsInfos) || !is_null($tablesInfos[$tableKey]['inheritance_key'])) {
				continue;
			}
			$inheritanceKeyValue = null;
			foreach($tableInfos['modelNames'] as $modelName) {
				$modelInfos = $modelsInfos[$modelName];
				if ($modelInfos['create'] || !isset($modelInfos['table'])) {
					continue;
				}
				$this->displayProcessingModel($modelName, 'inheritance key');
				
				$sqlTable = $modelInfos['table'];
				$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
				
				list($prefix, $suffix) = ModelManager::getInstance()->splitModelName($modelName);
				$manifestPath =  ModelManager::getInstance()->getManifestPath($prefix, $suffix);
				$path = ModelManager::getInstance()->getSerializationManifestPath($manifestPath, $prefix, $suffix);
				
				if (!file_exists($path)) {
					throw new \Exception("model $modelName should have serialization file");
				}
				$serializationManifest = $interfacer->read($path);
				
				if (is_null($serializationManifest)) {
					throw new \Exception("failure when reading $modelName serialization file");
				}
				
				$version = $interfacer->getValue($serializationManifest, 'version');
				switch ($version) {
					case '2.0':
						$inheritanceKey = 'inheritanceKey';
						break;
					case '3.0':
						$inheritanceKey = 'inheritance_key';
						break;
					default:
						throw new \Exception("serialization manifest version of model '$modelName' not managed : $version");
				}
				
				if (is_null($inheritanceKeyValue)) {
					$inheritanceKeyValue = $this->getInheritanceKey($tablesInfos[$tableKey]);
					$tablesInfos[$tableKey]['inheritance_key'] = $inheritanceKeyValue;
				}
				$serialization = &$interfacer->getValue($serializationManifest, 'serialization', true);
				$interfacer->setValue($serialization, $inheritanceKeyValue, $inheritanceKey);
				$interfacer->write($serializationManifest, $path, true);
			}
		}
	}
	
	/**
	 * verify if an inheritance key must be created
	 * 
	 * @param array $tableInfos
	 * @return boolean
	 */
	private function isInheritanceKeyNeeded($tableInfos, $modelsInfos) {
		return count($tableInfos['modelNames']) > 1;
	}
	
	/**
	 * get inheritance key from user
	 * 
	 * @param array $tableInfos
	 * @return string
	 */
	private function getInheritanceKey($tableInfos) {
		$message = 'Enter an inheritance key '
			.'(for example if your sql table store persons (male and female), inheritance key would be "gender")';
		do {
			$inheritanceKey = $this->ask($message, '__inheritance__');
			$message = 'Name already used on property, please enter a new inheritance key '
				.'(for example if your sql table store persons (male and female), inheritance key would be "gender")';
		} while (array_key_exists($inheritanceKey, $tableInfos['columns']));
		
		return $inheritanceKey;
	}
	
	/**
	 * add serialization settings and inheritance key on manifest serializations
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function addSerializationSettings(&$modelsInfos, &$tablesInfos) {
		foreach($modelsInfos as $modelName => $modelInfos) {
			if (!$modelInfos['create']) {
				continue;
			}
			
			$sqlTable = $modelInfos['table'];
			$serializationManifest = $modelInfos['serialization_manifest'];
			$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
			
			if ($modelInfos['register_serialization']) {
				$serialization = $serializationManifest->initValue('serialization');
				$serialization->setValue('foreign_settings', $sqlTable);
				
				if ($this->isInheritanceKeyNeeded($tablesInfos[$tableKey], $modelsInfos)) {
					if (isset($tablesInfos[$tableKey]['inheritance_key'])) {
						$serialization->setValue('inheritance_key', $tablesInfos[$tableKey]['inheritance_key']);
					} else {
						$this->displayProcessingModel($modelName, 'inheritance key');
						
						$inheritanceKey = $this->getInheritanceKey($tablesInfos[$tableKey]);
						$serialization->setValue('inheritance_key', $inheritanceKey);
						$tablesInfos[$tableKey]['inheritance_key'] = $inheritanceKey;
					}
				}
			}
		}
	}
	
	/**
	 * get aggregation properties.
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param \Comhon\Model\Property\Property $property
	 * @param array $modelsInfos
	 * @return null|string[] if property is defined as aggregation return array of property names, null otherwise.
	 */
	private function getAggregation(Model $model, Property $property, $modelsInfos) {
		$aggregation = null;
		if (
			array_key_exists($property->getUniqueModel()->getName(), $modelsInfos)
			&& ($property->getModel() instanceof ModelForeign)
			&& ($property->getModel()->getModel() instanceof ModelArray)
			&& ($property->getModel()->getModel()->getModel() instanceof Model)
		) {
			$aggregationable = [];
			foreach ($property->getModel()->getModel()->getModel()->getProperties() as $aggregationableProperty) {
				if (
					($aggregationableProperty->getModel() instanceof ModelForeign)
					&& ($aggregationableProperty->getModel()->getModel() instanceof Model)
					&& (
						$aggregationableProperty->getModel()->getModel() === $model 
						|| $aggregationableProperty->getModel()->getModel()->isInheritedFrom($model)
					)
				) {
					$aggregationable[] = $aggregationableProperty->getName();
				}
			}
			if (!empty($aggregationable)) {
				$question = "Is '{$property->getName()}' an aggregation of '{$model->getName()}'"
				. ' (in other words not serialized as a column but reference rows in a table)?';
				
				if ($this->ask($question, 'no', ['yes', 'no']) === 'yes') {
					$aggregation = [];
					$aggregationable[] = 'finally it\'s not an aggregation';
					$not = count($aggregationable) - 1;
					
					$question = 'Specify which property(ies) is used for aggregation.';
					
					$responses = $this->ask($question, null, $aggregationable, Cli::FILTER_MULTI);
					if (!in_array($not, $responses)) {
						foreach ($responses as $response) {
							$aggregation[] = $aggregationable[$response];
						}
					}
				}
			}
		}
		return $aggregation;
	}
	
	/**
	 * verify if property is a foreign porperty with several ids
	 *
	 * @param \Comhon\Model\Property\Property $property
	 * @return boolean
	 */
	private function isMultipleIdForeignProperty(Property $property) {
		return ($property->getModel() instanceof ModelForeign)
			&& ($property->getModel()->getModel() instanceof Model)
			&& (count($property->getModel()->getModel()->getIdProperties()) > 1);
	}
	
	/**
	 * get serialization name
	 *
	 * @param \Comhon\Model\Model $model
	 * @param Property $property
	 * @throws \Exception
	 * @return string|null null if no serialization name
	 */
	private function getSerializationName(Property $property) {
		if ($this->case === 'iso') {
			return null;
		}
		$serializationName = $this->transformString($property->getName());
		return $serializationName !== $property->getName() ? $serializationName : null;
	}
	
	/**
	 * get serialization names
	 *
	 * @param \Comhon\Model\Model $model
	 * @param Property $property
	 * @throws \Exception
	 * @return string|null null if no serialization name
	 */
	private function getSerializationNames(Property $property) {
		$serializationNames = [];
		foreach ($property->getModel()->getModel()->getIdProperties() as $idProperty) {
			$serializationName = $this->case === 'iso'
				? $idProperty->getName()
				: $this->transformString($property->getName(), $idProperty->getName());
			$serializationNames[$idProperty->getName()] = $serializationName;
		}
		return $serializationNames;
	}
	
	/**
	 *
	 * @param string $tabeName
	 * @param string $dbId
	 * @return string
	 */
	private function getTableUniqueKeyFromName($tabeName, $dbId) {
		return $tabeName.'_'.$dbId;
	}
	
	/**
	 *
	 * @param string $tabeName
	 * @param string $dbId
	 * @return string
	 */
	private function getTableUniqueKeyFromObject(UniqueObject $sqlTable) {
		return $this->getTableUniqueKeyFromName($sqlTable->getValue('name'), $sqlTable->getValue('database')->getId());
	}
	
	/**
	 * execute database file and comhon serialization files generation
	 * options are taken from script arguments
	 * 
	 * @param string $configPath comhon config file path
	 * @param string $case case of tables and columns to use
	 * @param string $database database id or database connection informations
	 * @param string $modelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 */
	public static function exec($configPath, $database, $case = 'iso', $modelName = null, $recursive = false) {
		Config::setLoadPath($configPath);
		
		$sqlTableModel = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
		$sqlDatabase = $sqlTableModel->loadObject($database);
		
		if (is_null($sqlDatabase)) {
			$infos = explode(':', $database);
			if (count($infos) > 7 || count($infos) < 6) {
				throw new \Exception("not existing database id or malformed database connection informations : '$database'");
			}
			
			$sqlDatabase = $sqlTableModel->getObjectInstance();
			$sqlDatabase->setId($infos[0]);
			$sqlDatabase->setValue('DBMS', $infos[1]);
			$sqlDatabase->setValue('host', $infos[2]);
			$sqlDatabase->setValue('name', $infos[3]);
			$sqlDatabase->setValue('user', $infos[4]);
			$sqlDatabase->setValue('password', $infos[5]);
			if (isset($infos[6])) {
				$sqlDatabase->setValue('port', (integer) $infos[6]);
			}
		}
		
		$modelToSQL = new self();
		try {
			$modelToSQL->registerSerializations($sqlDatabase, $case, $modelName, $recursive);
		} catch (\Exception $e) {
			self::exit($e->getMessage());
		}
	}
    
}
