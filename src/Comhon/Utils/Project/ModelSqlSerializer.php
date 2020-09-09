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
use Comhon\Model\Model;
use Comhon\Utils\Model as ModelUtils;
use Comhon\Object\ComhonObject;
use Comhon\Utils\Utils;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Interfacer\NoScalarTypedInterfacer;
use Comhon\Model\Property\AggregationProperty;
use Comhon\Manifest\Parser\V_2_0\SerializationManifestParser as SerializationManifestParserV2;
use Comhon\Manifest\Parser\V_3_0\SerializationManifestParser as SerializationManifestParserV3;

class ModelSqlSerializer extends InteractiveProjectScript {
	
	/**
	 * permit to know if we want to create manifest serialization file.
	 * may be true and manifest serialization file will not be created because there is no informations to add
	 * 
	 * @var string
	 */
	const CREATE_SERIALIZATION_FILE = 'create_serialization_file';
	
	/**
	 * permit to know if a model has children models with an attached sql table before script execution.
	 * permit to ask to user if current model should share a child model sql serialization.
	 * 
	 * @var string
	 */
	const EXISTING_SQL_MODEL_CHILDREN = 'existing_sql_model_children';
	
	/**
	 * all differents sql tables that children model may have
	 * 
	 * @var string
	 */
	const SQL_TABLE_CHILDREN = 'sql_table_children';
	
	/**
	 * permit to no if a model had a serialization file before script execution
	 * 
	 * @var string
	 */
	const HAS_SERIALIZATION_FILE = 'has_serialization_file';
	
	/**
	 * permit to no if a model with an existing serialization file have a serialization node in it.
	 * the serialization node contain the serialization kind and settings (sql table, xml file, etc...)
	 * 
	 * @var string
	 */
	const HAS_SERIALIZATION_NODE = 'has_serialization_node';
	
	/**
	 * permit to no if at least one child model has an existing serialization file with a serialization node.
	 * if current model has a child model with serialization node, its properties serialization names cannot be modified.
	 * 
	 * 
	 * @var string
	 */
	const HAS_CHILD_WITH_SERILZATION_NODE = 'has_child_with_serialization_node';
	
	/**
	 * permit to know if a model get his serialization from it parent model.
	 * if false, model doesn't have serialization
	 * 
	 * @var string
	 */
	const SHARE_PARENT_SERIALIZATION = 'share_parent_serialization';
	
	/**
	 * permit to know if we have to create serialization node when we create manifest serialization file.
	 * 
	 * @var string
	 */
	const REGISTER_SERIALIZATION_NODE = 'register_serialization_node';
	
	/**
	 * the attached sql table of a model
	 * 
	 * @var string
	 */
	const SQL_TABLE = 'sql_table';
	
	/**
	 * all model names attached to a sql table
	 * 
	 * @var string
	 */
	const MODEL_NAMES = 'model_names';
	
	/**
	 * sql table columns
	 * 
	 * @var string
	 */
	const COLUMNS = 'columns';
	
	/**
	 * used when a table is attached to several models.
	 * permit to determine a column name that will differenciate models of serialized object 
	 * 
	 * @var string
	 */
	const INHERITANCE_KEY = 'inheritance_key';
	
	/**
	 * the serialization manifest object that will be saved into file
	 * 
	 * @var string
	 */
	const SERIALIZATION_MANIFEST = 'serialization_manifest';
	
	/**
	 * 
	 * @var string
	 */
	private $case = 'snake';
	
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
	private $rootModel;
	
	/**
	 * 
	 * @param boolean $isInteractive if true, when self::registerSerializations() is called all process will be done automatically
	 *                               otherwise, some questions may be ask to user so it must be called from CLI script.
	 */
	public function __construct($isInteractive) {
		parent::__construct($isInteractive);
		$this->rootModel = ModelManager::getInstance()->getInstanceModel('Comhon\Root');
	}
	
	/**
	 *
	 * @param \Comhon\Object\UniqueObject $sqlDatabase
	 */
	private function serializeSqlDatabase(UniqueObject $sqlDatabase) {
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
	 * @param \Comhon\Object\UniqueObject $sqlTable
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
			case 'iso':
				return $string.($suffix == '' ? '' : '_'.$suffix);
				break;
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
	 * create and update serialization manifest files for objects that have to be serizalized in sql database
	 * 
	 * @param \Comhon\Object\UniqueObject $sqlDatabase
	 * @param string $case case of tables and columns to use
	 * @param string $filterModelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 * @throws ArgumentException
	 */
	public function registerSerializations(UniqueObject $sqlDatabase, $case = 'snake', $filterModelName = null, $recursive = false) {
		$this->case = is_null($case) ? 'snake' : $case;
		if (!is_null($filterModelName) && !$recursive) {
			$this->defaultMustSave = 'yes';
		}
		if (!$sqlDatabase->isA('Comhon\SqlDatabase')) {
			$databaseModel = ModelManager::getInstance()->getInstanceModel('Comhon\SqlDatabase');
			$expected = $databaseModel->getObjectInstance()->getComhonClass();
			throw new ArgumentException($sqlDatabase, $expected, 1);
		}
		if (!is_null($filterModelName) && !$recursive) {
			// verify if model exists
			ModelManager::getInstance()->getInstanceModel($filterModelName);
		}
		$this->serializeSqlDatabase($sqlDatabase);
		
		$projectModelNames = $this->getValidatedProjectModelNames($filterModelName, $recursive);
		$filterModelNames = is_null($filterModelName)
			? null
			: $this->getFilterModelNames($projectModelNames, $filterModelName, $recursive);
		
		// important! we must have parents models before children models
		// to be sure to store highest parent model first
		// to know if a table is already defined and if we use it for children models
		ModelUtils::sortModelNamesByInheritance($projectModelNames);
		
		$interfacer = $this->getInterfacer();
		$modelsInfos = $this->initModelsInfos($projectModelNames, $interfacer);
		$tablesInfos = $this->initTablesInfos($modelsInfos);
		$this->addModelsToSerialize($modelsInfos, $tablesInfos, $filterModelNames);
		
		if (!is_null($filterModelNames)) {
			$removedModelNames = $this->filterModelsInfos($modelsInfos, $tablesInfos, $filterModelNames);
		}
		
		$this->attachLastSqlTables($modelsInfos, $tablesInfos);
		$this->addSerialiationFilesForProperties($modelsInfos);
		$this->setSqlTableChildren($modelsInfos, $tablesInfos);
		$this->setAutomaticSqlTableColums($modelsInfos, $tablesInfos);
		
		$this->initSerializationManifests($modelsInfos, $tablesInfos);
		$this->addCustomizableSerializationNames($modelsInfos, $tablesInfos);
		$this->updateInheritanceKey($modelsInfos, $tablesInfos, $interfacer, $removedModelNames);
		$this->addSerializationSettings($modelsInfos, $tablesInfos);
		
		$updated = false;
		foreach($modelsInfos as $modelInfos) {
			if (isset($modelInfos[SELF::SERIALIZATION_MANIFEST]) && $modelInfos[SELF::SERIALIZATION_MANIFEST]->isUpdated()) {
				$modelInfos[SELF::SERIALIZATION_MANIFEST]->save(SerializationUnit::CREATE);
				$updated = true;
			}
		}
		if (!$updated) {
			$this->displayMessage('already up to date');
		}
	}
	
	/**
	 * initialize informations map for all models
	 * 
	 * @param string[] $projectModelNames
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return array
	 */
	private function initModelsInfos($projectModelNames, $interfacer) {
		$modelsInfos = [];
		
		foreach ($projectModelNames as $modelName) {
			$modelsInfos[$modelName] = [
				self::CREATE_SERIALIZATION_FILE => false,
				self::EXISTING_SQL_MODEL_CHILDREN => [],
				self::SQL_TABLE_CHILDREN => [],
				self::HAS_SERIALIZATION_FILE => false,
				self::HAS_SERIALIZATION_NODE => false,
				self::HAS_CHILD_WITH_SERILZATION_NODE => false,
				self::SHARE_PARENT_SERIALIZATION => true
			];
			list($prefix, $suffix) = ModelManager::getInstance()->splitModelName($modelName);
			$manifestPath =  ModelManager::getInstance()->getManifestPath($prefix, $suffix);
			$path = ModelManager::getInstance()->getSerializationManifestPath($manifestPath, $prefix, $suffix);
			
			if (file_exists($path)) {
				$modelsInfos[$modelName][self::HAS_SERIALIZATION_FILE] = true;
				$model = ModelManager::getInstance()->getInstanceModel($modelName);
				
				$serializationManifest = $interfacer->read($path);
				if (is_null($serializationManifest)) {
					throw new \Exception("failure when reading $modelName serialization file");
				}
				
				if ($interfacer->hasValue($serializationManifest, 'serialization', true)) {
					$modelsInfos[$modelName][self::HAS_SERIALIZATION_NODE] = true;
					if ($model->hasSqlTableSerialization()) {
						$model->getSerializationSettings()->loadValue('database');
						$modelsInfos[$model->getName()][self::SQL_TABLE] = $model->getSerializationSettings();
						
						$parentModel = $model->getParent();
						while ($parentModel !== $this->rootModel) {
							$modelsInfos[$parentModel->getName()][self::EXISTING_SQL_MODEL_CHILDREN][] = $modelName;
							$parentModel = $parentModel->getParent();
						}
					}
					$parentModel = $model->getParent();
					while ($parentModel !== $this->rootModel) {
						$modelsInfos[$parentModel->getName()][self::HAS_CHILD_WITH_SERILZATION_NODE] = true;
						$parentModel = $parentModel->getParent();
					}
				} elseif ($interfacer->hasValue($serializationManifest, self::SHARE_PARENT_SERIALIZATION)) {
					$share = $interfacer->getValue($serializationManifest, self::SHARE_PARENT_SERIALIZATION);
					if ($interfacer instanceof NoScalarTypedInterfacer) {
						$interfacer->castValueToBoolean($share);
					}
					if ($share === false) {
						$modelsInfos[$modelName][self::SHARE_PARENT_SERIALIZATION] = false;
					}
				}
			}
		}
		return $modelsInfos;
	}
	
	/**
	 * initialize informations map for all sql table serializations
	 *
	 * @param array $modelsInfos
	 * @return array
	 */
	private function initTablesInfos($modelsInfos) {
		$tablesInfos = [];
		foreach ($modelsInfos as $modelName => $modelInfos) {
			if (!array_key_exists(self::SQL_TABLE, $modelInfos)) {
				continue;
			}
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$tableKey = $this->getTableUniqueKeyFromObject($model->getSerializationSettings());
			if (!array_key_exists($tableKey, $tablesInfos)) {
				$tablesInfos[$tableKey] = [
					self::MODEL_NAMES => [],
					SELF::INHERITANCE_KEY => null,
					self::COLUMNS => [],
				];
			}
			$tablesInfos[$tableKey][self::MODEL_NAMES][] = $modelName;
			$tablesInfos[$tableKey][SELF::INHERITANCE_KEY] = $model->getSerialization()->getInheritanceKey();
		}
		return $tablesInfos;
	}
	
	/**
	 * ask user if models must be serialized in sql database.
	 * update $modelsInfos and $tablesInfos for each model to serialize.
	 * 
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 * @param array $filterModelNames
	 */
	private function addModelsToSerialize(&$modelsInfos, &$tablesInfos, $filterModelNames = null) {
		foreach ($modelsInfos as $modelName => &$modelInfos) {
			if (
				$modelInfos[self::HAS_SERIALIZATION_FILE]
				|| (!is_null($filterModelNames) && !array_key_exists($modelName, $filterModelNames))
			) {
				continue;
			}
			$this->displayProcessingModel($modelName);
			
			$sharedTableModel = $this->getModelWithSharedTable($modelName, $modelsInfos);
			if (is_null($sharedTableModel) && !$this->mustHaveSqlSerialization($modelName)) {
				if (!is_null($this->getParentModelWithSqlTable($modelName, $modelsInfos))) {
					$modelInfos[self::CREATE_SERIALIZATION_FILE] = true;
					$modelInfos[self::SHARE_PARENT_SERIALIZATION] = false;
				}
				continue;
			}
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$sqlTable = is_null($sharedTableModel)
				? $this->getSqlTable($modelName, $tablesInfos)
				: $this->getDefinedSqlTable($sharedTableModel, $modelsInfos);
				
			$modelInfos[self::SQL_TABLE] = $sqlTable;
			$modelInfos[self::CREATE_SERIALIZATION_FILE] = true;
			$modelInfos[SELF::REGISTER_SERIALIZATION_NODE] = $sharedTableModel !== $model->getParent();
			
			$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
			if (!array_key_exists($tableKey, $tablesInfos)) {
				$tablesInfos[$tableKey] = [
						self::MODEL_NAMES => [],
						SELF::INHERITANCE_KEY => null,
						self::COLUMNS => [],
				];
			}
			$tablesInfos[$tableKey][self::MODEL_NAMES][] = $modelName;
		}
	}
	
	/**
	 * ask to user if model must have an attached serialization
	 *
	 * @param string $modelName
	 * @return boolean
	 */
	private function mustHaveSqlSerialization($modelName) {
		$question = "Model $modelName doesn't have serialization file." . PHP_EOL
		."Would you like to defined sql serialization on this model ?";
		return $this->ask($question, $this->defaultMustSave, ['yes', 'no']) === 'yes';
	}
	
	/**
	 * ask to user if model must be saved in same table than its parents or children models,
	 * if it's the case, the model is returned
	 *
	 * @param string $modelName
	 * @param array $modelsInfos
	 * @return \Comhon\Model\Model|null null if no parent model or if doesn't share table with parent model
	 */
	private function getModelWithSharedTable($modelName, $modelsInfos) {
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$parentModel = $model->getParent();
		$sharedTableModel = null;
		$visitedTables = [];
		
		while ($parentModel !== $this->rootModel) {
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
			foreach ($modelsInfos[$modelName][self::EXISTING_SQL_MODEL_CHILDREN] as $modelNameChild) {
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
	 * get the first parent model with sql table serialization.
	 * if a serialization different than sql table is encountred,
	 * it return null even if a higher parent has an attached sql table.
	 *
	 * @param string $modelName
	 * @param array $modelsInfos
	 * @return \Comhon\Model\Model|null
	 */
	private function getParentModelWithSqlTable($modelName, $modelsInfos) {
		$model = ModelManager::getInstance()->getInstanceModel($modelName);
		$parentModel = $model->getParent();
		
		while ($parentModel !== $this->rootModel) {
			$sqlTable = $this->getDefinedSqlTable($parentModel, $modelsInfos);
			if (!is_null($sqlTable)) {
				return $parentModel;
			}
			if ($modelsInfos[$modelName][self::HAS_SERIALIZATION_NODE]) {
				// model inherit serialization from a parent with a serialization different than sql table
				return null;
			}
			$parentModel = $parentModel->getParent();
		}
		return null;
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
		$default = $this->transformString(str_replace('\\', '_', $modelName));
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
		return isset($modelsInfos[$model->getName()][self::SQL_TABLE]) ? $modelsInfos[$model->getName()][self::SQL_TABLE]: null;
	}
	
	/**
	 * keep only models that have kinship with filter models or that share same sql table.
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 * @param array $filterModelNames
	 * @return array all deleted model names
	 */
	private function filterModelsInfos(&$modelsInfos, $tablesInfos, $filterModelNames) {
		$filteredModelNames = [];
		$tableKeys = [];
		foreach ($modelsInfos as $modelName => $modelInfos) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$modelNames = [$modelName];
			$add = false;
			if (array_key_exists($modelName, $filterModelNames)) {
				$add = true;
			}
			$parentModel = $model->getParent();
			while ($parentModel !== $this->rootModel) {
				$modelNames[] = $parentModel->getName();
				if (array_key_exists($parentModel->getName(), $filterModelNames)) {
					$add = true;
				}
				$parentModel = array_key_exists($parentModel->getName(), $filteredModelNames)
					? $this->rootModel
					: $parentModel->getParent();
			}
			if ($add) {
				if (array_key_exists(self::SQL_TABLE, $modelInfos)) {
					$tableKeys[$this->getTableUniqueKeyFromObject($modelInfos[self::SQL_TABLE])] = null;
				}
				foreach ($modelNames as $modelNameToAdd) {
					if (array_key_exists(self::SQL_TABLE, $modelsInfos[$modelNameToAdd])) {
						$tableKeys[$this->getTableUniqueKeyFromObject($modelsInfos[$modelNameToAdd][self::SQL_TABLE])] = null;
					}
					$filteredModelNames[$modelNameToAdd] = null;
				}
			}
		}
		// some models without kinship with filter models may use same table
		// so we have to keep them too
		foreach ($tableKeys as $tableKey => $value) {
			foreach ($tablesInfos[$tableKey][self::MODEL_NAMES] as $modelName) {
				$filteredModelNames[$modelName] = null;
			}
		}
		$allModelNames = array_flip(array_keys($modelsInfos));
		$toRemove = array_diff_key($allModelNames, $filteredModelNames);
		
		foreach ($toRemove as $modelName => $value) {
			unset($modelsInfos[$modelName]);
		}
		
		return $toRemove;
	}
	
	/**
	 * attach sql table on models that have serialization file but no serialization node.
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function attachLastSqlTables(&$modelsInfos, &$tablesInfos) {
		foreach ($modelsInfos as $modelName => &$modelInfos) {
			if ($modelInfos[self::SHARE_PARENT_SERIALIZATION] 
				&& $modelInfos[self::HAS_SERIALIZATION_FILE] 
				&& !$modelInfos[self::HAS_SERIALIZATION_NODE]
			) {
				$parentModel = $this->getParentModelWithSqlTable($modelName, $modelsInfos);
				if (!is_null($parentModel)) {
					$modelInfos[self::SQL_TABLE] = $modelsInfos[$parentModel->getName()][self::SQL_TABLE];
					$modelInfos[SELF::REGISTER_SERIALIZATION_NODE] = false;
					
					$tableKey = $this->getTableUniqueKeyFromObject($modelInfos[self::SQL_TABLE]);
					$tablesInfos[$tableKey][self::MODEL_NAMES][] = $modelName;
				}
			}
		}
	}
	
	/**
	 * set self::CREATE_SERIALIZATION_FILE to true for models that have no serialization but have children model with sql tables,
	 * that will permit to create serialization file only for properties serialization names.
	 *
	 * @param array $modelsInfos
	 */
	private function addSerialiationFilesForProperties(&$modelsInfos) {
		foreach ($modelsInfos as $modelName => &$modelInfos) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if (array_key_exists(self::SQL_TABLE, $modelInfos) && !$modelInfos[self::HAS_CHILD_WITH_SERILZATION_NODE]) {
				$parentModel = $model->getParent();
				while (
					$parentModel !== $this->rootModel
					&& (
						$modelsInfos[$parentModel->getName()][self::CREATE_SERIALIZATION_FILE] === false
						|| $modelsInfos[$parentModel->getName()][self::SHARE_PARENT_SERIALIZATION] === false
					)
					&& !array_key_exists(self::SQL_TABLE, $modelsInfos[$parentModel->getName()])
					&& !$modelsInfos[$parentModel->getName()][self::HAS_SERIALIZATION_NODE]
				) {
					if (!$modelsInfos[$parentModel->getName()][self::HAS_SERIALIZATION_FILE]) {
						$modelsInfos[$parentModel->getName()][self::CREATE_SERIALIZATION_FILE] = true;
						$modelsInfos[$parentModel->getName()][SELF::REGISTER_SERIALIZATION_NODE] = false;
					}
					$parentModel = $parentModel->getParent();
				}
			}
		}
	}
	
	/**
	 * list all children model sql tables for each models
	 * 
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function setSqlTableChildren(&$modelsInfos, &$tablesInfos) {
		foreach ($modelsInfos as $modelName => &$modelInfos) {
			if (array_key_exists(self::SQL_TABLE, $modelInfos)) {
				$tableKey = $this->getTableUniqueKeyFromObject($modelInfos[self::SQL_TABLE]);
				$parentModel = ModelManager::getInstance()->getInstanceModel($modelName)->getParent();
				while ($parentModel !== $this->rootModel) {
					$parentModelInfos = &$modelsInfos[$parentModel->getName()];
					if (!in_array($tableKey, $parentModelInfos[self::SQL_TABLE_CHILDREN])) {
						$parentModelInfos[self::SQL_TABLE_CHILDREN][] = $tableKey;
					}
					$parentModel = $parentModel->getParent();
				}
			}
		}
	}
	
	/**
	 * initialize serialization manifest
	 * 
	 * @param array $modelsInfos
	 */
	private function initSerializationManifests(&$modelsInfos) {
		foreach($modelsInfos as $modelName => &$modelInfos) {
			if ($modelInfos[self::CREATE_SERIALIZATION_FILE]) {
				$serializationManifest = new ComhonObject('Comhon\Serialization', false);
				$serializationManifest->setId($modelName, false);
				$serializationManifest->setValue('version', '3.0', false);
				$modelInfos[SELF::SERIALIZATION_MANIFEST] = $serializationManifest;
			}
		}
	}
	
	/**
	 * set all columns in $tablesInfos that can't be customized by user
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function setAutomaticSqlTableColums(&$modelsInfos, &$tablesInfos) {
		foreach($modelsInfos as $modelName => $modelInfos) {
			if (
				(!array_key_exists(self::SQL_TABLE, $modelInfos) && empty($modelInfos[self::SQL_TABLE_CHILDREN]))
				|| ($modelInfos[self::CREATE_SERIALIZATION_FILE] && !$modelInfos[self::HAS_CHILD_WITH_SERILZATION_NODE])
			) {
				continue;
			}
			$tableKeys = $modelInfos[self::SQL_TABLE_CHILDREN];
			if (array_key_exists(self::SQL_TABLE, $modelInfos)) {
				$tableKey = $this->getTableUniqueKeyFromObject($modelInfos[self::SQL_TABLE]);
				if (!in_array($tableKey, $tableKeys)) {
					$tableKeys[] = $tableKey;
				}
			}
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			/** @var \Comhon\Model\Property\Property[] $properties */
			$properties = array_diff_key($model->getProperties(), $model->getParent()->getProperties());
			
			$propertiesBySerializationNames = [];
			foreach ($properties as $property) {
				if ($property instanceof AggregationProperty) {
					continue;
				}
				if ($property instanceof MultipleForeignProperty) {
					foreach ($property->getMultipleIdProperties() as $serializationName => $idProperty) {
						if (array_key_exists($serializationName, $propertiesBySerializationNames)) {
							$this->displaySerializationNameConflict(
								$serializationName,
								$propertiesBySerializationNames[$serializationName]->getName(),
								$modelName,
								$property->getName(),
								$modelName
							);
						}
						$propertiesBySerializationNames[$serializationName] = $property;
					}
				} elseif ($property->isSerializable()) {
					$serializationName = $property->getSerializationName();
					if (array_key_exists($serializationName, $propertiesBySerializationNames)) {
						$this->displaySerializationNameConflict(
							$serializationName,
							$propertiesBySerializationNames[$serializationName]->getName(),
							$modelName,
							$property->getName(),
							$modelName
						);
					}
					$propertiesBySerializationNames[$serializationName] = $property;
				}
			}
			foreach ($tableKeys as $tableKey) {
				foreach ($propertiesBySerializationNames as $serializationName => $property) {
					if (
						array_key_exists($serializationName, $tablesInfos[$tableKey][self::COLUMNS])
						&& $property !== current($tablesInfos[$tableKey][self::COLUMNS][$serializationName])
					) {
						$this->displaySerializationNameConflict(
							$serializationName,
							current($tablesInfos[$tableKey][self::COLUMNS][$serializationName])->getName(),
							key($tablesInfos[$tableKey][self::COLUMNS][$serializationName]),
							$property->getName(),
							$modelName
						);
					} else {
						$tablesInfos[$tableKey][self::COLUMNS][$serializationName] = [$model->getName() => $property];
					}
				}
			}
		}
	}
	
	/**
	 * display a message to user to warn him about duplicated properties serialization names
	 * 
	 * @param string $serializationName
	 * @param string $prop1
	 * @param string $modelName1
	 * @param string $prop2
	 * @param string $modelName2
	 */
	private function displaySerializationNameConflict($serializationName, $prop1, $modelName1, $prop2, $modelName2) {
		$this->displayMessage(
			"\033[1;30mWarning! serialization name '$serializationName' is used several times : ".PHP_EOL
			."- for property '$prop1' in model '$modelName1'".PHP_EOL
			."- for property '$prop2' in model '$modelName2'".PHP_EOL
			."\033[0m"
		);
	}
	
	/**
	 * add properties serialization names on manifest serializations.
	 * updated columns in $tablesInfos if new serialization names are set.
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 */
	private function addCustomizableSerializationNames(&$modelsInfos, &$tablesInfos) {
		foreach($modelsInfos as $modelName => $modelInfos) {
			if (
				(!array_key_exists(self::SQL_TABLE, $modelInfos) && empty($modelInfos[self::SQL_TABLE_CHILDREN]))
				|| !$modelInfos[self::CREATE_SERIALIZATION_FILE] 
				|| $modelInfos[self::HAS_CHILD_WITH_SERILZATION_NODE]
			) {
				continue;
			}
			$tableKeys = $modelInfos[self::SQL_TABLE_CHILDREN];
			if (array_key_exists(self::SQL_TABLE, $modelInfos)) {
				$tableKey = $this->getTableUniqueKeyFromObject($modelInfos[self::SQL_TABLE]);
				if (!in_array($tableKey, $tableKeys)) {
					$tableKeys[] = $tableKey;
				}
			}
			$this->displayProcessingModel($modelName, 'properties');
			
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			/** @var \Comhon\Object\ComhonObject $serializationManifest */
			$serializationManifest = $modelInfos[SELF::SERIALIZATION_MANIFEST];
			
			/** @var \Comhon\Model\Property\Property[] $properties */
			$properties = array_diff_key($model->getProperties(), $model->getParent()->getProperties());
			
			foreach ($properties as $property) {
				if ($property instanceof AggregationProperty) {
					continue;
				}
				$serializationProperty = null;
				
				if ($this->ask("is property '{$property->getName()}' serializable", 'yes', ['yes', 'no']) === 'yes') {
					if ($this->isMultipleIdForeignProperty($property)) {
						$names = $this->getSerializationNames($property, $tableKeys, $tablesInfos);
						$serializationProperty = new ComhonObject('Comhon\Serialization\Property');
						$serializationNames = $serializationProperty->initValue('serialization_names', false);
						foreach ($names as $propertyName => $serializationName) {
							$serializationNames->setValue($propertyName, $serializationName);
							$this->addColumn($serializationName, $tableKeys, $tablesInfos);
						}
					} else {
						$serializationName = $this->getSerializationName($property, $tableKeys, $tablesInfos);
						if (!is_null($serializationName)) {
							$serializationProperty = new ComhonObject('Comhon\Serialization\Property');
							$serializationProperty->setValue('serialization_name', $serializationName);
							$this->addColumn($serializationName, $tableKeys, $tablesInfos);
						}
					}
				} else {
					$serializationProperty = new ComhonObject('Comhon\Serialization\Property');
					$serializationProperty->setValue('is_serializable', false);
				}
				if (!is_null($serializationProperty)) {
					if (!$serializationManifest->issetValue('properties')) {
						$serializationManifest->initValue('properties');
					}
					$serializationProperty->setValue('property_name', $property->getName());
					$serializationManifest->getValue('properties')->pushValue($serializationProperty);
				}
			}
		}
	}
	
	/**
	 * add columns on all given sql tables
	 * 
	 * @param string $serializationName
	 * @param string[] $tableKeys
	 * @param array $tablesInfos
	 */
	private function addColumn($serializationName, $tableKeys, &$tablesInfos) {
		foreach ($tableKeys as $tableKey) {
			$tablesInfos[$tableKey][self::COLUMNS][$serializationName] = null;
		}
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
	 * @param \Comhon\Model\Property\Property $property
	 * @param string[] $tableKeys
	 * @param array $tablesInfos
	 * @return string|null null if no serialization name
	 */
	private function getSerializationName(Property $property, $tableKeys, $tablesInfos) {
		$serializationName = $this->transformString($property->getName());
		$serializationName = $this->getNotUsedSerializationName($serializationName, $tableKeys, $tablesInfos);
		return $serializationName !== $property->getName() ? $serializationName : null;
	}
	
	/**
	 * get serialization names
	 *
	 * @param \Comhon\Model\Model $model
	 * @param Property $property
	 * @throws \Exception
	 * @return string[]
	 */
	private function getSerializationNames(Property $property, $tableKeys, $tablesInfos) {
		$serializationNames = [];
		foreach ($property->getModel()->getModel()->getIdProperties() as $idProperty) {
			$serializationName = $this->transformString($property->getName(), $idProperty->getName());
			$serializationName = $this->getNotUsedSerializationName($serializationName, $tableKeys, $tablesInfos);
			$serializationNames[$idProperty->getName()] = $serializationName;
		}
		return $serializationNames;
	}
	
	/**
	 * get serialization name that is not used in provided tables and generate one if needed
	 *
	 * @param string $serializationName
	 * @param string[] $tableKeys table keys to check
	 * @param array $tablesInfos
	 * @return string
	 */
	private function getNotUsedSerializationName($serializationName, $tableKeys, $tablesInfos) {
		if ($this->isUsedSerializationName($serializationName, $tableKeys, $tablesInfos)) {
			$i = 2;
			while ($this->isUsedSerializationName($this->transformString($serializationName, $i), $tableKeys, $tablesInfos)) {
				$i++;
			}
			$serializationName = $this->transformString($serializationName, $i);
		}
		return $serializationName;
	}
	
	/**
	 * verify if serialization name is already used in provided tables
	 *
	 * @param string $serializationName
	 * @param string[] $tableKeys table keys to check
	 * @param array $tablesInfos
	 */
	private function isUsedSerializationName($serializationName, $tableKeys, $tablesInfos) {
		foreach ($tableKeys as $tableKey) {
			if (array_key_exists($serializationName, $tablesInfos[$tableKey][self::COLUMNS])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * update existing serialization manifests by adding inhertiance key if needed
	 *
	 * @param array $modelsInfos
	 * @param array $tablesInfos
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param array $removedModelNames
	 */
	private function updateInheritanceKey(&$modelsInfos, &$tablesInfos, $interfacer, $removedModelNames) {
		foreach($tablesInfos as $tableKey => $tableInfos) {
			if (!$this->isInheritanceKeyNeeded($tableInfos, $modelsInfos) || !is_null($tablesInfos[$tableKey][SELF::INHERITANCE_KEY])) {
				continue;
			}
			$inheritanceKeyValue = null;
			foreach($tableInfos[self::MODEL_NAMES] as $modelName) {
				if (array_key_exists($modelName, $removedModelNames)) {
					continue;
				}
				$modelInfos = $modelsInfos[$modelName];
				if (!$modelInfos[self::HAS_SERIALIZATION_NODE] || !isset($modelInfos[self::SQL_TABLE])) {
					continue;
				}
				$this->displayProcessingModel($modelName, 'inheritance key');
				
				$sqlTable = $modelInfos[self::SQL_TABLE];
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
						$inheritanceKey = SerializationManifestParserV2::INHERITANCE_KEY;
						break;
					case '3.0':
						$inheritanceKey = SerializationManifestParserV3::INHERITANCE_KEY;
						break;
					default:
						throw new \Exception("serialization manifest version of model '$modelName' not managed : $version");
				}
				
				if (is_null($inheritanceKeyValue)) {
					$inheritanceKeyValue = $this->getInheritanceKey($tablesInfos[$tableKey]);
					$tablesInfos[$tableKey][SELF::INHERITANCE_KEY] = $inheritanceKeyValue;
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
		if (count($tableInfos[self::MODEL_NAMES]) < 2) {
			return false;
		}
		$count = 0;
		foreach ($tableInfos[self::MODEL_NAMES] as $modelName) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if (!$model->isAbstract()) {
				$count++;
			}
		}
		return $count > 1;
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
		} while (array_key_exists($inheritanceKey, $tableInfos[self::COLUMNS]));
		
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
			if (!$modelInfos[self::CREATE_SERIALIZATION_FILE]) {
				continue;
			}
			$serializationManifest = $modelInfos[SELF::SERIALIZATION_MANIFEST];
			if (isset($modelInfos[self::SHARE_PARENT_SERIALIZATION]) && $modelInfos[self::SHARE_PARENT_SERIALIZATION] === false) {
				$serializationManifest->setValue(self::SHARE_PARENT_SERIALIZATION, false);
			}
			elseif ($modelInfos[SELF::REGISTER_SERIALIZATION_NODE]) {
				$sqlTable = $modelInfos[self::SQL_TABLE];
				$tableKey = $this->getTableUniqueKeyFromObject($sqlTable);
				$serialization = $serializationManifest->initValue('serialization');
				$serialization->setValue('foreign_settings', $sqlTable);
				
				if ($this->isInheritanceKeyNeeded($tablesInfos[$tableKey], $modelsInfos)) {
					if (isset($tablesInfos[$tableKey][SELF::INHERITANCE_KEY])) {
						$serialization->setValue(SELF::INHERITANCE_KEY, $tablesInfos[$tableKey][SELF::INHERITANCE_KEY]);
					} else {
						$this->displayProcessingModel($modelName, 'inheritance key');
						
						$inheritanceKey = $this->getInheritanceKey($tablesInfos[$tableKey]);
						$serialization->setValue(SELF::INHERITANCE_KEY, $inheritanceKey);
						$tablesInfos[$tableKey][SELF::INHERITANCE_KEY] = $inheritanceKey;
					}
				}
			}
		}
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
	 * create and update serialization manifest files for objects that have to be serizalized in sql database.
	 * should be called from CLI script.
	 * 
	 * @param string $configPath comhon config file path
	 * @param string $case case of tables and columns to use
	 * @param string $database database id or database connection informations
	 * @param string $modelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 */
	public static function exec($configPath, $database, $case = 'snake', $modelName = null, $recursive = false) {
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
		
		$modelToSQL = new self(true);
		$modelToSQL->registerSerializations($sqlDatabase, $case, $modelName, $recursive);
	}
    
}
