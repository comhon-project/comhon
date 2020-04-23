<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Utils;

use Comhon\Object\Config\Config;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\SimpleModel;
use Comhon\Model\Property\ForeignProperty;

class Model {
	
    /**
     * get all manifest model names found in all defined autoloads that you have defined in the config file of your project.
     * doesn't return model defined in local types.
     * doesn't validate models of returned model names.
     * 
     * @param string $directory a directory to filter search
     * @throws \Exception
     * @return string[]
     */
    public static function getManifestModelNames($directory = null) {
    	$modelNames = [];
    	$autoloads = [];
    	
    	if ($directory) {
    		if (!file_exists($directory) || !is_dir($directory)) {
    			throw new \Exception("directory '$directory' doesn't exist");
    		}
    		$directory_ad = realpath($directory);
    		foreach (Config::getInstance()->getManifestAutoloadList() as $namespace => $path) {
    			$manifest_ad = substr($path, 0, 1) == '.'
    					? realpath(Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $path)
    					: realpath($path);
    					if (strpos($manifest_ad, $directory_ad) === 0) {
    						$autoloads[$namespace] = $path;
    					} elseif (strpos($directory_ad, $manifest_ad) === 0) {
    						$namespace = $namespace . str_replace('/', '\\', substr($directory_ad, strlen($manifest_ad)));
    						$autoloads = [$namespace => $directory_ad];
    						
    						list($prefix, $suffix) = ModelManager::getInstance()->splitModelName($namespace);
    						$manifest_af = ModelManager::getInstance()->getManifestPath($prefix, $suffix);
    						
    						if (file_exists($manifest_af)) {
    							$modelNames[] = $namespace;
    						}
    						break;
    					}
    		}
    	} else {
    		$autoloads = Config::getInstance()->getManifestAutoloadList();
    	}
    	foreach ($autoloads as $namespace => $path) {
    		$manifest_ad = substr($path, 0, 1) == '.' ? Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $path : $path;
    		$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($manifest_ad), \RecursiveIteratorIterator::SELF_FIRST);
    		
    		/**
    		 * @var \SplFileInfo $object
    		 */
    		foreach($objects as $name => $object) {
    			if (!is_dir($name) || $object->getBasename() === '.' || $object->getBasename() === '..') {
    				continue;
    			}
    			$modelName = $namespace . '\\' . substr(str_replace(DIRECTORY_SEPARATOR, '\\', str_replace($manifest_ad, '', $name)), 1);
    			list($prefix, $suffix) = ModelManager::getInstance()->splitModelName($modelName);
    			$manifest_af = ModelManager::getInstance()->getManifestPath($prefix, $suffix);
    			
    			if (file_exists($manifest_af)) {
    				$modelNames[] = $modelName;
    			}
    		}
    	}
    	return $modelNames;
    }
    
    /**
     * get all model names found in all defined autoloads that you have defined in the config file of your project.
     *
     * @param string $directory a directory to filter search
     * @param boolean $includeLocalTypes if true, names of models defined in local types will be returned too
     * @param string[] $notValid if $notValid is provided, then it is filled with not valid models.
     *                           the key is the model name and the value is the reason why model is not valid.
     * @throws \Exception
     * @return string[] model names of validated models
     */
    public static function getValidatedProjectModelNames($directory = null, $includeLocalTypes = true, &$notValid = []) {
    	if (!is_array($notValid)) {
    		$notValid = [];
    	}
    	$manifestModelNames = self::getManifestModelNames($directory);
    	$validManifestModelNames = self::getValidatedModelNames($manifestModelNames, $notValid);
    	
    	if ($includeLocalTypes) {
    		$modelNames = [];
    		foreach ($validManifestModelNames as $modelName) {
    			$modelNames[] = $modelName;
    			foreach (ModelManager::getInstance()->getLocalTypes($modelName) as $localType) {
    				$modelNames[] = $localType;
    			}
    		}
    	} else {
    		$modelNames = $validManifestModelNames;
    	}
    	
    	$validModelNames = self::getValidatedModelNames($modelNames, $notValid);
    	
    	$duplicated = [];
    	$modelNames = self::getUniqueModelNames($validModelNames, $duplicated);
    	
    	foreach ($duplicated as $modelName) {
    		$notValid[$modelName] = "model '$modelName' appear several times in manifest and local types";
    	}
    	
    	return $modelNames;
    }
    
    /**
     * validate models according given model names and return only model names with validated model.
     *
     * @param string[] $modelNames
     * @param string[] $notValid if $notValid is provided, then it is filled with not valid models.
     *                           the key is the model name and the value is the reason why model is not valid.
     * @return string[] model names with validated model
     */
    public static function getValidatedModelNames(array $modelNames, &$notValid = []) {
    	if (!is_array($notValid)) {
    		$notValid = [];
    	}
    	$validModels = [];
    	$valid = [];
    	
    	// ensure that models are valid
    	foreach ($modelNames as $modelName) {
    		try {
    			ModelManager::getInstance()->getInstanceModel($modelName);
    			foreach (ModelManager::getInstance()->getLocalTypes($modelName) as $localType) {
    				ModelManager::getInstance()->getInstanceModel($localType);
    			}
    			$validModels[] = $modelName;
    		} catch(\Exception $e) {
    			$notValid[$modelName] = $e->getMessage();
    		}
    	}
    	
    	// ensure that properties models are valid
    	foreach ($validModels as $modelName) {
    		$model = ModelManager::getInstance()->getInstanceModel($modelName);
    		try {
    			foreach ($model->getProperties() as $property) {
    				$propertyModel = $property->getUniqueModel();
    				if (($property instanceof ForeignProperty) && !$propertyModel->hasIdProperties()) {
    					throw new \Exception("foreign property '{$property->getName()}' has a model without id ('{$propertyModel->getName()}')");
    				}
    			}
    			$valid[] = $modelName;
    		} catch (\Exception $e) {
    			$notValid[$modelName] = $e->getMessage();
    		}
    	}
    	
    	return $valid;
    }
    
    
    
    /**
     * removes duplicate model names from given array 
     *
     * @param string[] $modelNames
     * @param string[] $duplicated if $duplicated is provided, then it is filled with duplicated model names
     * @return string[] filtered and indexed model names
     */
    public static function getUniqueModelNames($modelNames, &$duplicated = []) {
    	if (!is_array($duplicated)) {
    		$duplicated = [];
    	}
    	$occurenceModelNames = array_count_values($modelNames);
    	$modelNames = [];
    	foreach ($occurenceModelNames as $modelName => $occurence) {
    		if ($occurence == 1) {
    			$modelNames[] = $modelName;
    		} else {
    			$duplicated[] = $modelName;
    		}
    	}
    	return $modelNames;
    }
    
    
    
    /**
     * sort model names by inheritance, parents models are placed before children models
     *
     * @param string $modelNames
     */
    public static function sortModelNamesByInheritance(&$modelNames)
    {
    	$sortedModelNames = [];
    	$flipedModelNames = array_count_values($modelNames);
    	$count = count($modelNames);
    	$modelNames = [];
    	
    	foreach ($flipedModelNames as $modelName => $value) {
    		$model = ModelManager::getInstance()->getInstanceModel($modelName);
    		if (array_key_exists($model->getName(), $sortedModelNames)) {
    			continue;
    		}
    		if ($model instanceof SimpleModel) {
    			$sortedModelNames[$model->getName()] = null;
    			continue;
    		}
    		$nodes = [['model' => $model, 'indexParent' => 0]];
    		
    		while (!empty($nodes)) {
    			$node = end($nodes);
    			$model = $node['model'];
    			$parent = $model->getParent($node['indexParent']);
    			
    			if (is_null($parent)) {
    				if (array_key_exists($model->getName(), $flipedModelNames)) {
    					$sortedModelNames[$model->getName()] = null;
    				}
    				array_pop($nodes);
    			} else {
    				$nodes[key($nodes)]['indexParent']++;
    				if (!array_key_exists($parent->getName(), $sortedModelNames)) {
    					$nodes[] = ['model' => $parent, 'indexParent' => 0];
    				}
    			}
    		}
    	}
    	foreach ($sortedModelNames as $modelName => $value) {
    		while ($flipedModelNames[$modelName] > 0) {
    			$modelNames[] = $modelName;
    			$flipedModelNames[$modelName]--;
    		}
    	}
    	if ($count !== count($modelNames)) {
    		throw new \Exception('something goes wrong during sort');
    	}
    }
    
}
