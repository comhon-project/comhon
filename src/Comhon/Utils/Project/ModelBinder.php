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
use Comhon\Exception\ComhonException;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Manifest\Parser\ManifestParser;
use Comhon\Manifest\Parser\SerializationManifestParser;

class ModelBinder {
	
	private static function displayContinue(\Exception $e, $modelName) {
		$instruction = "Something goes wrong with model '$modelName' ({$e->getMessage()})." . PHP_EOL
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
	 * execute link binding between models
	 * options are taken from script arguments
	 * 
	 * @param string $configPath comhon config file path
	 */
	public static function exec($configPath, $interactive = true) {
		Config::setLoadPath($configPath);
		
		$format = Config::getInstance()->getManifestFormat();
		$oneManifestUpdate = false;
		$oneSerializationUpdate = false;
		$modelsInfos = [];
		
		switch ($format) {
			case 'json':
				$interfacer = new StdObjectInterfacer();
				break;
			case 'xml':
				$interfacer = new XMLInterfacer();
				break;
			default:
				throw new ComhonException('not managed extension : '.$format);
		}
		$serializations = Config::getInstance()->getSerializationAutoloadList()->getValues();
		
		foreach (Config::getInstance()->getManifestAutoloadList() as $namespace => $path) {
			$manifest_ad = Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $path;
			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($manifest_ad), \RecursiveIteratorIterator::SELF_FIRST);
			
			$serialization_ad = array_key_exists($namespace, $serializations)
				? Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $serializations[$namespace]
				: $manifest_ad;
			
			/**@var \SplFileInfo $object */
			foreach($objects as $name => $object) {
				$path_af = $object->getRealPath();
				if (!is_file($path_af)) {
					continue;
				}
				$ext = $object->getExtension();
				if ($ext !== $format) {
					continue;
				}
				if ($object->getBasename('.' . $ext) !== 'manifest') {
					continue;
				}
				$modelName = $namespace . '\\' . substr(str_replace(DIRECTORY_SEPARATOR, '\\', str_replace($manifest_ad, '', dirname($name))), 1);
				try {
					$model = ModelManager::getInstance()->getInstanceModel($modelName);
					$serializationPath_af = str_replace($manifest_ad, $serialization_ad, dirname($name)) 
						. DIRECTORY_SEPARATOR . 'serialization.' . $object->getExtension();
					
					$modelsInfos[$model->getName()] = [
						'manifestPath' => $path_af,
						'serializationPath' => $serializationPath_af,
						'model' => $model,
						'localType' => null,
						'inheritanceRequestables' => [],
						'inheritanceSerializables' => []
					];
					$dirname = dirname($serializationPath_af);
					$basename = basename($serializationPath_af);
					$manifestParser = ManifestParser::getInstance($path_af, null, $model->getName());
					
					foreach ($manifestParser->getLocalModelManifestParsers(false) as $modelName => $parser) {
						$localModel = ModelManager::getInstance()->getInstanceModel($modelName);
						$localModelNameWithoutNamespace = str_replace($model->getName().'\\', '', $modelName);
						
						$serializationPath_af = $dirname
							. DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $localModelNameWithoutNamespace)
							. DIRECTORY_SEPARATOR . $basename;
						
						$modelsInfos[$localModel->getName()] = [
							'manifestPath' => $path_af,
							'serializationPath' => $serializationPath_af,
							'model' => $localModel,
							'localType' => $localModelNameWithoutNamespace,
							'inheritanceRequestables' => [],
							'inheritanceSerializables' => []
						];
					}
				} catch(\Exception $e) {
					if ($interactive) {
						self::displayContinue($e, $modelName);
					} else {
						echo 'Warning !!! ' . $e->getMessage() . PHP_EOL;
					}
					continue;
				}
			}
		}
		
		// now  we can set models with inheritance requestable
		foreach($modelsInfos as $modelInfos) {
			$model = $modelInfos['model'];
			$modelName = $model->getName();
			
			if (is_null($model->getSerialization())) {
				continue;
			}
			$serializationAllowed = $model->getSerialization()->isSerializationAllowed();
			
			while (!is_null($model = $model->getFirstSharedIdParentMatch(true))) {
				if (!array_key_exists($model->getName(), $modelsInfos)) {
					throw new ComhonException('model not found : ' . $model->getName());
				}
				$modelsInfos[$model->getName()]['inheritanceRequestables'][$modelName] = $serializationAllowed;
			}
		}
		
		// now  we can clean models with inheritance requestable
		// but not serializable and without serialisable children
		foreach($modelsInfos as &$modelInfos) {
			$hasInheritanceSerializable = false;
			foreach ($modelInfos['inheritanceRequestables'] as $modelName => $isSerializable) {
				if ($isSerializable) {
					$hasInheritanceSerializable = true;
					break;
				}
			}
			if (!$hasInheritanceSerializable) {
				$modelInfos['inheritanceRequestables'] = [];
			}
		}
		
		// now  we can clean models with inheritance requestable
		// that have empty inheritance requestable
		foreach($modelsInfos as &$modelInfos) {
			foreach ($modelInfos['inheritanceRequestables'] as $inheritanceModelName => $isSerializable) {
				$inheritanceModel = $modelsInfos[$inheritanceModelName]['model'];
				$inheritanceRequestables = $modelsInfos[$inheritanceModelName]['inheritanceRequestables'];
				if (!($inheritanceModel->getSerialization() && $inheritanceModel->getSerialization()->isSerializationAllowed()) && empty($inheritanceRequestables)) {
					unset($modelInfos['inheritanceRequestables'][$inheritanceModelName]);
				}
			}
		}
		
		// now we can set models with inheritance serializable
		foreach($modelsInfos as $modelInfos) {
			$addFilter = false;
			/** @var \Comhon\Model\Model $currentModel */
			$currentModel = $modelInfos['model'];
			$model = $currentModel;
			$parentModel = $model->getFirstSharedIdParentMatch(true);
			
			while (!is_null($parentModel) && !$addFilter) {
				if (is_null($parentModel->getSerialization())) {
					$parentModel = $parentModel->getParent();
					continue;
				}
				if ($parentModel->getSerialization()->isSerializationAllowed()) {
					$addFilter = true;
				}
				foreach ($modelsInfos[$parentModel->getName()]['inheritanceRequestables'] as $modelName => $isSerializable) {
					if ($modelName !== $model->getName() && $isSerializable) {
						$addFilter = true;
						break;
					}
				}
				$model = $parentModel;
				$parentModel = $model->getFirstSharedIdParentMatch(true);
			}
			
			if ($addFilter) {
				foreach ($modelsInfos[$currentModel->getName()]['inheritanceRequestables'] as $modelName => $isSerializable) {
					if ($isSerializable) {
						$modelsInfos[$currentModel->getName()]['inheritanceSerializables'][] = $modelName;
					}
				}
				if ($currentModel->getSerialization() && $currentModel->getSerialization()->isSerializationAllowed()) {
					$modelsInfos[$currentModel->getName()]['inheritanceSerializables'][] = $currentModel->getName();
				}
			}
		}
		
		// now we can save inheritance requestables in manifest
		foreach($modelsInfos as $modelInfos) {
			try {
				if (!file_exists($modelInfos['manifestPath'])) {
					throw new ComhonException('file doesn\'t exist : '.$modelInfos['manifestPath']);
				}
				$root = $interfacer->read($modelInfos['manifestPath']);
				if (!in_array($interfacer->getValue($root, 'version'), ['3.0', '2.0'])) {
					throw new ComhonException(
						'manifest version \'' . $interfacer->getValue($root, 'version'). '\' not supported '
							. ' on file \'' . $modelInfos['manifestPath'] . '\''
					);
				}
				$containerNode = null;
				if (is_null($modelInfos['localType'])) {
					$containerNode = $root;
				} else {
					$types = $interfacer->getValue($root, 'types', true);
					
					if (is_null($types)) {
						var_dump($interfacer->toString($types));
						throw new ComhonException('types node doesn\' t exist : '.$modelInfos['manifestPath']);
					}
					foreach ($interfacer->getTraversableNode($types) as $type) {
						if ($interfacer->getValue($type, 'name') == $modelInfos['localType']) {
							$containerNode = $type;
							break;
						}
					}
				}
				if (is_null($containerNode)) {
					throw new ComhonException("type node '{$modelInfos['localType']}' doesn' t exist : {$modelInfos['manifestPath']}");
				}
				if ($interfacer->hasValue($containerNode, ManifestParser::INHERITANCE_REQUESTABLES, true)) {
					$existingNode = $interfacer->getValue($containerNode, ManifestParser::INHERITANCE_REQUESTABLES, true);
					
					$types = $interfacer->getTraversableNode($existingNode);
					if ($interfacer instanceof XMLInterfacer) {
						foreach ($types as $key => $domNode) {
							$types[$key] = $interfacer->extractNodeText($domNode);
						}
					}
					$types = array_flip($types);
					$interfacer->unsetValue($containerNode, ManifestParser::INHERITANCE_REQUESTABLES, true);
				} else {
					$types = [];
				}
				if (count($types) === count($modelInfos['inheritanceRequestables'])) {
					$same = true;
					foreach ($modelInfos['inheritanceRequestables'] as $modelName => $isSerializable) {
						if (!array_key_exists('\\' . $modelName, $types)) {
							$same = false;
							break;
						}
					}
					if ($same) {
						continue;
					}
				}
				
				$node = $interfacer->createArrayNode(ManifestParser::INHERITANCE_REQUESTABLES);
				foreach ($modelInfos['inheritanceRequestables'] as $modelName => $isSerializable) {
					$interfacer->addValue($node, '\\' . $modelName, 'inheritance_requestable');
				}
				$interfacer->setValue($containerNode, $node, ManifestParser::INHERITANCE_REQUESTABLES);
				if (!$interfacer->write($root, $modelInfos['manifestPath'], true)) {
					throw new ComhonException('failure when trying to save file : '.$modelInfos['manifestPath']);
				}
				if (!$oneSerializationUpdate) {
					echo 'Manifest : ' . PHP_EOL;
				}
				$oneManifestUpdate = true;
				echo $modelInfos['model']->getName() . ' updated' . PHP_EOL;
			
			} catch(\Exception $e) {
				if ($interactive) {
					self::displayContinue($e, $modelName);
				} else {
					echo 'Warning !!! ' . $e->getMessage() . PHP_EOL;
				}
				continue;
			}
		}
		
		// now we can save inheritance values in serialization manifest
		foreach($modelsInfos as $modelInfos) {
			try {
				if (empty($modelInfos['inheritanceSerializables']) && !file_exists($modelInfos['serializationPath'])) {
					continue;
				}
				if (file_exists($modelInfos['serializationPath'])) {
					$root = $interfacer->read($modelInfos['serializationPath']);
					if (!in_array($interfacer->getValue($root, 'version'), ['3.0', '2.0'])) {
						throw new ComhonException(
							'serialization manifest version \'' . $interfacer->getValue($root, 'version'). '\' not supported '
							. ' on file \'' . $modelInfos['serializationPath'] . '\''
						);
					}
				} else {
					$root = $interfacer->createNode('manifest');
					$interfacer->setValue($root, '2.0', 'version');
				}
				if ($interfacer->hasValue($root, SerializationManifestParser::INHERITANCE_VALUES, true)) {
					$existingNode = $interfacer->getValue($root, SerializationManifestParser::INHERITANCE_VALUES, true);
					
					$types = $interfacer->getTraversableNode($existingNode);
					if ($interfacer instanceof XMLInterfacer) {
						foreach ($types as $key => $domNode) {
							$types[$key] = $interfacer->extractNodeText($domNode);
						}
					}
					$types = array_flip($types);
					$interfacer->unsetValue($root, SerializationManifestParser::INHERITANCE_VALUES, true);
				} else {
					$types = [];
				}
				if (count($types) === count($modelInfos['inheritanceSerializables'])) {
					$same = true;
					foreach ($modelInfos['inheritanceSerializables'] as $modelName) {
						if (!array_key_exists($modelName, $types)) {
							$same = false;
							break;
						}
					}
					if ($same) {
						continue;
					}
				}
				
				$node = $interfacer->createArrayNode(SerializationManifestParser::INHERITANCE_VALUES);
				foreach ($modelInfos['inheritanceSerializables'] as $modelName) {
					$interfacer->addValue($node, $modelName, 'model');
				}
				$interfacer->setValue($root, $node, SerializationManifestParser::INHERITANCE_VALUES);
				
				if (!file_exists(dirname($modelInfos['serializationPath']))) {
					if (!mkdir(dirname($modelInfos['serializationPath']), 0755, true)) {
						throw new ComhonException('failure when trying to create directory : '.dirname($modelInfos['serializationPath']));
					}
				}
				if (!$interfacer->write($root, $modelInfos['serializationPath'], true)) {
					throw new ComhonException('failure when trying to save file : '.$modelInfos['serializationPath']);
				}
				if (!$oneSerializationUpdate) {
					echo 'Manifest Serialization : ' . PHP_EOL;
				}
				$oneSerializationUpdate = true;
				echo 'Manifest Serialization ' . $modelInfos['model']->getName() . ' updated' . PHP_EOL;
			
			} catch(\Exception $e) {
				if ($interactive) {
					self::displayContinue($e, $modelName);
				} else {
					echo 'Warning !!! ' . $e->getMessage() . PHP_EOL;
				}
				continue;
			}
		}
		
		if (!$oneManifestUpdate && !$oneSerializationUpdate) {
			echo 'Already up to date' . PHP_EOL;
		}
	}
	
}

