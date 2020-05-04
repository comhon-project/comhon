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
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Manifest\Parser\ManifestParser;
use Comhon\Manifest\Parser\SerializationManifestParser;
use Comhon\Serialization\Serialization;
use Comhon\Interfacer\Interfacer;

class ModelBinder extends InteractiveProjectScript {
	
	const MANIFEST_VERSIONS = ['2.0', '3.0'];
	const SERIALIZATION_MANIFEST_VERSIONS = ['2.0', '3.0'];
	
	/**
	 * bind models by adding inheritance_requestables and inheritance_values.
	 * inheritance_requestables permit to know which children models with same serialization are requestable.
	 * inheritance_values permit to know which filter models we have to add during complex request.
	 * 
	 * @param string $configPath comhon config file path
	 */
	public function bindModels($filterModelName = null, $recursive = false) {
		$interfacer = $this->getInterfacer();
		
		if (!is_null($filterModelName) && !$recursive) {
			// verify if model exists
			ModelManager::getInstance()->getInstanceModel($filterModelName);
		}
		$projectModelNames = $this->getValidatedProjectModelNames($filterModelName, $recursive);
		
		$manifestModelByLocalTypeModels = [];
		foreach ($projectModelNames as $modelName) {
			foreach (ModelManager::getInstance()->getLocalTypes($modelName) as $localType) {
				$manifestModelByLocalTypeModels[$localType] = $modelName;
			}
		}
		
		$kinshipFilterModelNames = is_null($filterModelName)
			? null
			: $this->getKinshipFilterModelNames($projectModelNames, $filterModelName, $recursive, $manifestModelByLocalTypeModels);
		
		$modelsInfos = $this->initModelsInfos($projectModelNames, $kinshipFilterModelNames, $manifestModelByLocalTypeModels, $interfacer);
		$modelsBySerializations = $this->setInheritanceModelNames($modelsInfos);
		$oneManifestUpdate = $this->updateInheritanceRequestableNodes($modelsInfos, $interfacer);
		$oneSerializationUpdate = $this->updateInheritanceValuesNodes($modelsInfos, $interfacer, $modelsBySerializations);
		
		if ($oneManifestUpdate) {
			$this->saveManifestUpdates($modelsInfos, $interfacer);
		}
		if ($oneSerializationUpdate) {
			$this->saveSerializationManifestUpdates($modelsInfos, $interfacer);
		}
		
		if (!$oneManifestUpdate && !$oneSerializationUpdate) {
			$this->displayMessage('Already up to date');
		}
	}
	
	/**
	 * keep only models that have kinship with filter models.
	 *
	 * @param string[] $projectModelNames
	 * @param string $filterModelName
	 * @param boolean $recursive
	 * @param string[] $manifestModelByLocalTypeModels
	 * @return boolean[] return list of models. each key is a model name, each value determine if model may be updated
	 */
	private function getKinshipFilterModelNames($projectModelNames, $filterModelName, $recursive, $manifestModelByLocalTypeModels) {
		if (is_null($filterModelName)) {
			return $projectModelNames;
		}
		$filterModelNames = $this->getFilterModelNames($projectModelNames, $filterModelName, $recursive);
		
		$kinshipFilterModelNames = [];
		$this->populateKinshipFilterModelNames($kinshipFilterModelNames, $projectModelNames, $filterModelNames);
		
		// second call is necessary to get all kinship models
		// for example if my filter model contain only model 'Woman'
		// the first call will add parent model 'Person' but will miss model 'Man'
		// the second call will add model 'Man'
		$this->populateKinshipFilterModelNames($kinshipFilterModelNames, $projectModelNames, $kinshipFilterModelNames);
		
		// add all model that doesn't share skinship but have same serialization
		// we tag them as not updatable
		$serializationKeys = [];
		foreach($kinshipFilterModelNames as $modelName => $isModelUpdatable) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if ($model->hasSerialization()) {
				$uniqueKey = $this->getSerializationUniqueKey($model->getSerialization());
				$serializationKeys[$uniqueKey] = null;
			}
		}
		foreach($projectModelNames as $modelName) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if ($model->hasSerialization()) {
				$uniqueKey = $this->getSerializationUniqueKey($model->getSerialization());
				if (
					array_key_exists($uniqueKey, $serializationKeys)
					&& !array_key_exists($modelName, $kinshipFilterModelNames)
				) {
					$kinshipFilterModelNames[$modelName] = false;
				}
			}
		}
		
		// if local types are in filter without its manifest models
		// we have to add manifest models and tag them as not updatable
		$notUpdatable = [];
		foreach ($kinshipFilterModelNames as $kinshipFilterModelName => $isModelUpdatable) {
			if (
				array_key_exists($kinshipFilterModelName, $manifestModelByLocalTypeModels)
				&& !array_key_exists($manifestModelByLocalTypeModels[$kinshipFilterModelName], $kinshipFilterModelNames)
			) {
				$notUpdatable[$manifestModelByLocalTypeModels[$kinshipFilterModelName]] = false;
			}
		}
		if (!empty($notUpdatable)) {
			// we have to put them at the beginning to be sure they are befor local types
			$kinshipFilterModelNames = array_merge($notUpdatable, $kinshipFilterModelNames);
		}
		
		return $kinshipFilterModelNames;
	}
	
	private function populateKinshipFilterModelNames(&$kinshipFilterModelNames, $projectModelNames, $filterModelNames) {
		foreach ($projectModelNames as $modelName) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			$modelNames = [$modelName];
			$add = false;
			if (array_key_exists($modelName, $filterModelNames)) {
				$add = true;
			}
			$parentModel = $model->getFirstSharedIdParentMatch(true);
			while (!is_null($parentModel)) {
				$modelNames[] = $parentModel->getName();
				if (array_key_exists($parentModel->getName(), $filterModelNames)) {
					$add = true;
				}
				$parentModel = array_key_exists($parentModel->getName(), $kinshipFilterModelNames)
					? null
					: $parentModel->getFirstSharedIdParentMatch(true);
			}
			if ($add) {
				foreach ($modelNames as $modelNameToAdd) {
					$kinshipFilterModelNames[$modelNameToAdd] = true;
				}
			}
		}
	}
	
	/**
	 * initialize models informations
	 * 
	 * @param string[] $projectModelNames
	 * @param string[] $kinshipFilterModelNames
	 * @param string[] $manifestModelByLocalTypeModels
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return array
	 */
	private function initModelsInfos($projectModelNames, $kinshipFilterModelNames, $manifestModelByLocalTypeModels, $interfacer) {
		$modelsInfos = [];
		foreach ($projectModelNames as $modelName) {
			if (!is_null($kinshipFilterModelNames) && !array_key_exists($modelName, $kinshipFilterModelNames)) {
				continue;
			}
			try {
				$localModelName = null;
				
				list($prefix, $suffix) = ModelManager::getInstance()->splitModelName($modelName);
				$manifestPath =  ModelManager::getInstance()->getManifestPath($prefix, $suffix);
				$serializationManifestPath = ModelManager::getInstance()->getSerializationManifestPath($manifestPath, $prefix, $suffix);
				
				$manifestNode = $this->getManifestNode(
					$modelName,
					$manifestPath,
					$manifestModelByLocalTypeModels,
					$modelsInfos,
					$interfacer
				);
				$serializationManifest = $this->getSerializationManifestNode($serializationManifestPath, $interfacer);
				$namespace = array_key_exists($modelName, $manifestModelByLocalTypeModels)
					? $manifestModelByLocalTypeModels[$modelName]
					: $modelName;
				$isModelUpdatable = is_null($kinshipFilterModelNames) || $kinshipFilterModelNames[$modelName];
				
				$modelsInfos[$modelName] = [
						'manifestNode' => $manifestNode,
						'updateManifest' => false,
						'serializationManifestNode' => $serializationManifest,
						'serializationManifestPath' => $serializationManifestPath,
						'updateSerializationManifest' => false,
						'namespace' => $namespace,
						'isModelUpdatable' => $isModelUpdatable,
						ManifestParser::INHERITANCE_REQUESTABLES => [],
						SerializationManifestParser::INHERITANCE_VALUES => []
				];
				if (file_exists($manifestPath)) {
					$modelsInfos[$modelName]['manifestPath'] = $manifestPath;
				}
				
			} catch(\Exception $e) {
				$modelNameError = is_null($localModelName) ? $modelName : $localModelName;
				$this->displayContinueInvalidModel($e->getMessage(), $modelNameError);
			}
		}
		return $modelsInfos;
	}
	
	/**
	 * get manifest node
	 *
	 * @param string $manifestPath
	 * @param string[] $manifestModelByLocalTypeModels
	 * @param array $modelsInfos
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return mixed|null return root node or local type node
	 */
	private function getManifestNode($modelName, $manifestPath, $manifestModelByLocalTypeModels, $modelsInfos, $interfacer) {
		$manifestNode = null;
		if (array_key_exists($modelName, $manifestModelByLocalTypeModels)) { // local type
			$localTypeShortName = str_replace($manifestModelByLocalTypeModels[$modelName].'\\', '', $modelName);
			if (!array_key_exists($manifestModelByLocalTypeModels[$modelName], $modelsInfos)) {
				throw new \Exception("model name '{$manifestModelByLocalTypeModels[$modelName]}' not found");
			}
			$manifestRoot = $modelsInfos[$manifestModelByLocalTypeModels[$modelName]]['manifestNode'];
			foreach ($interfacer->getTraversableNode($interfacer->getValue($manifestRoot, 'types', true)) as $type) {
				if ($localTypeShortName == $interfacer->getValue($type, 'name')) {
					$manifestNode = $type;
					break;
				}
			}
			if (is_null($manifestNode)) {
				throw new \Exception("local type '$modelName' not found");
			}
		} else { // manifest model
			$manifestNode = $interfacer->read($manifestPath);
			if (!in_array($interfacer->getValue($manifestNode, 'version'), self::MANIFEST_VERSIONS)) {
				throw new \Exception(
					"manifest version '{$interfacer->getValue($manifestNode, 'version')}' not supported "
					. " on file '$manifestPath'"
				);
			}
		}
		
		return $manifestNode;
	}
	
	/**
	 * get serialization manifest node
	 * 
	 * @param string $serializationManifestPath
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	private function getSerializationManifestNode($serializationManifestPath, $interfacer) {
		
		if (file_exists($serializationManifestPath)) {
			$serializationManifest = $interfacer->read($serializationManifestPath);
			$version = $interfacer->getValue($serializationManifest, 'version');
			if (!in_array($version, self::SERIALIZATION_MANIFEST_VERSIONS)) {
				throw new \Exception(
					"serialization manifest version '$version' not supported "
					. "on file '$serializationManifestPath'"
				);
			}
		} else {
			$serializationManifest = null;
		}
		return $serializationManifest;
	}
	
	/**
	 * set 'inheritance_values' and 'inheritance_requestables' on models informations.
	 * 
	 * @param array $modelsInfos
	 * @return string[][] the list of all models stored by serialization
	 */
	private function setInheritanceModelNames(&$modelsInfos) {
		$modelsBySerializations = [];
		
		foreach($modelsInfos as $modelName => &$modelInfos) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if (!$model->hasSerialization()) {
				continue;
			}
			if (!$model->isAbstract()) {
				$modelInfos[SerializationManifestParser::INHERITANCE_VALUES][] = $modelName;
				$uniqueKey = $this->getSerializationUniqueKey($model->getSerialization());
				if (!array_key_exists($uniqueKey, $modelsBySerializations)) {
					$modelsBySerializations[$uniqueKey] = [];
				}
				$modelsBySerializations[$uniqueKey][] = $modelName;
			}
			$parentModel = $model->getFirstSharedIdParentMatch(true);
			while (!is_null($parentModel)) {
				$modelsInfos[$parentModel->getName()][ManifestParser::INHERITANCE_REQUESTABLES][] = $modelName;
				if (!$model->isAbstract()) {
					$modelsInfos[$parentModel->getName()][SerializationManifestParser::INHERITANCE_VALUES][] = $modelName;
				}
				$parentModel = $parentModel->getFirstSharedIdParentMatch(true);
			}
		}
		
		return $modelsBySerializations;
	}
	
	/**
	 * get serialization unique key
	 * 
	 * @param Serialization $serialization
	 */
	private function getSerializationUniqueKey(Serialization $serialization) {
		if (!is_null($serialization->getSerializationUnitClass())) {
			return $serialization->getSerializationUnitClass();
		} else {
			if ($serialization->getSettings()->isA('Comhon\SqlTable')) {
				return $serialization->getSettings()->getModel()->getName().'_'.$serialization->getSettings()->getId();
			} elseif ($serialization->getSettings()->isA('Comhon\File')) {
				return $serialization->getSettings()->getModel()->getName()
				.'_'.$serialization->getSettings()->getValue('dir_path')
				.'_'.$serialization->getSettings()->getValue('file_name');
			} else {
				throw new \Exception('serialization not managed : '.$serialization->getSettings()->getModel()->getName());
			}
		}
	}
	
	/**
	 * 
	 * @param array $modelsInfos
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return boolean
	 */
	private function updateInheritanceRequestableNodes(&$modelsInfos, $interfacer) {
		$oneManifestUpdate = false;
		// now we can save inheritance requestables in manifest
		foreach($modelsInfos as $modelName => $modelInfos) {
			if (!$modelInfos['isModelUpdatable']) {
				continue;
			}
			$updated = $this->updateInheritanceNode(
				$modelInfos['manifestNode'],
				ManifestParser::INHERITANCE_REQUESTABLES,
				'model',
				$interfacer,
				$modelInfos[ManifestParser::INHERITANCE_REQUESTABLES],
				$modelInfos['namespace']
			);
			if ($updated) {
				$modelNameToUpdate = array_key_exists('manifestPath', $modelInfos)
					? $modelName
					: $modelInfos['namespace'];
				$modelsInfos[$modelNameToUpdate]['updateManifest'] = $updated;
				$oneManifestUpdate = true;
			}
		}
		return $oneManifestUpdate;
	}
	
	/**
	 * 
	 * @param array $modelsInfos
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param string[][] $modelsBySerializations
	 */
	private function updateInheritanceValuesNodes(&$modelsInfos, Interfacer $interfacer, $modelsBySerializations) {
		$oneSerializationUpdate = false;
		foreach($modelsInfos as $modelName => &$modelInfos) {
			$model = ModelManager::getInstance()->getInstanceModel($modelName);
			if (
				!$modelInfos['isModelUpdatable']
				|| !$model->hasSerialization()
				|| (
					empty($modelInfos[SerializationManifestParser::INHERITANCE_VALUES])
					&& is_null($modelInfos['serializationManifestNode'])
				)
			) {
				continue;
			}
			$uniqueKey = $this->getSerializationUniqueKey($model->getSerialization());
			$sameSerializationCount = array_key_exists($uniqueKey, $modelsBySerializations)
			? count($modelsBySerializations[$uniqueKey]) : 0;
			$inheritanceValuesCount = count($modelInfos[SerializationManifestParser::INHERITANCE_VALUES]);
			
			// if same count, that means all model with same serialization are children of current model
			// so in this case we don't have to add inheritance values to filter complex requests
			if ($sameSerializationCount == $inheritanceValuesCount) {
				$modelInfos[SerializationManifestParser::INHERITANCE_VALUES] = [];
			}
			if (is_null($modelInfos['serializationManifestNode'])) {
				$root = $interfacer->createNode('root');
				$interfacer->setValue($root, $modelName, 'name');
				$interfacer->setValue($root, '3.0', 'version');
				$modelInfos['serializationManifestNode'] = $root;
			}
			$updated = $this->updateInheritanceNode(
				$modelInfos['serializationManifestNode'],
				SerializationManifestParser::INHERITANCE_VALUES,
				'model',
				$interfacer,
				$modelInfos[SerializationManifestParser::INHERITANCE_VALUES],
				null
			);
			$modelsInfos[$modelName]['updateSerializationManifest'] = $updated;
			
			if ($updated) {
				$oneSerializationUpdate = true;
			}
		}
		
		return $oneSerializationUpdate;
	}
	
	/**
	 * update node inheritances if needed
	 * 
	 * @param mixed $containerNode
	 * @param string $childNodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @param string[] $modelNames
	 * @param string $namespace
	 * @return boolean true if $containerNode has been updated, false otherwise
	 */
	private function updateInheritanceNode($containerNode, $childNodeName, $elementNodeName, $interfacer, $modelNames, $namespace = null) {
		$existingModelNames = $this->getChildNodeArrayStringValues($containerNode, $childNodeName, $interfacer);
		if (count($modelNames) === count($existingModelNames)) {
			if (!is_null($namespace)) {
				foreach ($existingModelNames as &$modelName) {
					$modelName = $modelName[0] == '\\'
						? substr($modelName, 1)
						: $namespace . '\\' . $modelName;
				}
			}
			if (empty(array_diff($modelNames, $existingModelNames))) {
				return false;
			}
		}
		
		$interfacer->unsetValue($containerNode, $childNodeName, true);
		$node = $interfacer->createArrayNode($childNodeName);
		foreach ($modelNames as $modelName) {
			if (!is_null($namespace)) {
				$modelName = '\\' . $modelName;
			}
			$interfacer->addValue($node, $modelName, $elementNodeName);
		}
		$interfacer->setValue($containerNode, $node, $childNodeName);
		
		// unset and reset values to keep properties order (only for manifest, no need for serizaltion manifest)
		if (!is_null($namespace)) {
			$version = $interfacer->getValue($containerNode, 'version');
			if (!is_null($version)) {
				$interfacer->unsetValue($containerNode, 'version');
				$interfacer->setValue($containerNode, $version, 'version');
			}
			$types = $interfacer->getValue($containerNode, 'types', true);
			if (!is_null($types)) {
				$interfacer->unsetValue($containerNode, 'types', true);
				$interfacer->setValue($containerNode, $types, 'types', true);
			}
		}
		return true;
	}
	
	/**
	 * get child node array string values if exists.
	 *
	 * @param mixed $containerNode
	 * @param string $childNodeName
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @return string[] return empty array if $childNodeName doesn't exists
	 */
	private function getChildNodeArrayStringValues($containerNode, $childNodeName, $interfacer) {
		if ($interfacer->hasValue($containerNode, $childNodeName, true)) {
			$existingNode = $interfacer->getValue($containerNode, $childNodeName, true);
			
			$values = $interfacer->getTraversableNode($existingNode);
			if ($interfacer instanceof XMLInterfacer) {
				foreach ($values as $key => $domNode) {
					$values[$key] = $interfacer->extractNodeText($domNode);
				}
			}
		} else {
			$values = [];
		}
		return $values;
	}
	
	/**
	 * update manifests with inheritance requestables
	 * 
	 * @param array $modelsInfos
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 */
	private function saveManifestUpdates($modelsInfos, Interfacer $interfacer) {
		$this->displayMessage('Manifests : ');
		try {
			foreach($modelsInfos as $modelName => $modelInfos) {
				if ($modelInfos['updateManifest'] && array_key_exists('manifestPath', $modelInfos)) {
					if (!$interfacer->write($modelInfos['manifestNode'], $modelInfos['manifestPath'], true)) {
						throw new \Exception('failure when trying to save file : '.$modelInfos['manifestPath']);
					}
					$this->displayMessage(' - ' . $modelName . ' updated');
				}
			}
		} catch(\Exception $e) {
			$this->displayContinue($e->getMessage());
		}
	}
	
	
	/**
	 * create or update serialization manifests with inheritance values
	 *
	 * @param array $modelsInfos
	 * @param \Comhon\Interfacer\Interfacer $interfacer
	 * @throws \Exception
	 */
	private function saveSerializationManifestUpdates($modelsInfos, Interfacer $interfacer) {
		$this->displayMessage('Serialization manifests : ');
		try {
			foreach($modelsInfos as $modelName => $modelInfos) {
				if ($modelInfos['updateSerializationManifest']) {
					$isCreate = !file_exists($modelInfos['serializationManifestPath']);
					if (!file_exists(dirname($modelInfos['serializationManifestPath']))) {
						mkdir(dirname($modelInfos['serializationManifestPath']), 0777);
					}
					if (!$interfacer->write($modelInfos['serializationManifestNode'], $modelInfos['serializationManifestPath'], true)) {
						throw new \Exception('failure when trying to save file : '.$modelInfos['serializationManifestPath']);
					}
					$this->displayMessage(' - ' . $modelName . ($isCreate ? ' created' : ' updated'));
				}
			}
		} catch(\Exception $e) {
			$this->displayContinue($e->getMessage());
		}
	}
	
	/**
	 * bind models by adding inheritance_requestables and inheritance_values.
	 * inheritance_requestables permit to know which children models with same serialization are requestable.
	 * inheritance_values permit to know which filter models we have to add during complex request.
	 * should be called from CLI script.
	 *
	 * @param string $configPath comhon config file path
	 * @param string $modelName filter to process only given model
	 * @param string $recursive if model is provided, process recursively models with same name space
	 */
	public static function exec($configPath, $filterModelName = null, $recursive = false) {
		Config::setLoadPath($configPath);
		
		$self = new self(true);
		$self->bindModels($filterModelName, $recursive);
	}
	
}

