<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Comhon\Model\Singleton;

use Comhon\Model\ModelInteger;
use Comhon\Model\ModelFloat;
use Comhon\Model\ModelBoolean;
use Comhon\Model\ModelString;
use Comhon\Model\ModelDateTime;
use Comhon\Model\Model;
use Comhon\Model\MainModel;
use Comhon\Model\LocalModel;
use Comhon\Model\Property\Property;
use Comhon\Serialization\SerializationUnit;
use Comhon\Object\Config\Config;
use Comhon\Manifest\Parser\ManifestParser;
use Comhon\Object\ComhonObject;

class ModelManager {

	const PROPERTIES     = 'properties';
	const OBJECT_CLASS   = 'objectClass';
	const SERIALIZATION  = 'serialization';
	const EXTENDS_MODEL  = 'extendsModel';
	
	private $instanceModels;
	private $localTypes = [];
	private $manifestParser;
	private $serializationManifestParser;
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			new self();
		}
	
		return self::$_instance;
	}
	
	private function __construct() {
		self::$_instance = $this;
		$this->_registerSimpleModelClasses();
		ManifestParser::registerComplexModels(
			__DIR__ . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Manifest' . DIRECTORY_SEPARATOR . 'Collection' . DIRECTORY_SEPARATOR . 'Manifest'. DIRECTORY_SEPARATOR .'manifestList.json', 
			__DIR__ . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Manifest' . DIRECTORY_SEPARATOR . 'Collection' . DIRECTORY_SEPARATOR . 'Serialization' . DIRECTORY_SEPARATOR . 'serializationList.json',
			$this->instanceModels
		);
		
		ManifestParser::registerComplexModels(
			Config::getInstance()->getManifestListPath(),
			Config::getInstance()->getSerializationListPath(),
			$this->instanceModels
		);
	}	
	
	private function _registerSimpleModelClasses() {
		$this->instanceModels = [
			ModelInteger::ID  => new ModelInteger(),
			ModelFloat::ID    => new ModelFloat(),
			ModelBoolean::ID  => new ModelBoolean(),
			ModelString::ID   => new ModelString(),
			ModelDateTime::ID => new ModelDateTime()
		];
	}
	
	
	public function hasModel($modelName, $mainModelName = null) {
		if (is_null($mainModelName)) {
			return array_key_exists($modelName, $this->instanceModels);
		} else {
			return array_key_exists($mainModelName, $this->localTypes) && array_key_exists($modelName, $this->localTypes[$mainModelName]);
		}
	}
	
	public function hasInstanceModel($modelName, $mainModelName = null) {
		if (!$this->hasModel($modelName, $mainModelName)) {
			throw new \Exception("model $modelName doesn't exists");
		}
		if (is_null($mainModelName)) {
			$instanceModels =& $this->instanceModels;
		} else {
			$instanceModels =& $this->localTypes[$mainModelName];
		}
		return is_object($instanceModels[$modelName]) || array_key_exists(2, $instanceModels[$modelName]);
	}
	
	public function isModelLoaded($modelName, $mainModelName = null) {
		if (!$this->hasModel($modelName, $mainModelName)) {
			throw new \Exception("model $modelName doesn't exists");
		}
		if (is_null($mainModelName)) {
			$instanceModels =& $this->instanceModels;
		} else {
			$instanceModels =& $this->localTypes[$mainModelName];
		}
		if (is_object($instanceModels[$modelName])) {
			if (!$instanceModels[$modelName]->isLoaded()) {
				throw new \Exception("$modelName must be loaded");
			}
			return true;
		}
		if (array_key_exists(2, $instanceModels[$modelName])) {
			if ($instanceModels[$modelName][2]->isLoaded()) {
				throw new \Exception("$modelName must be not loaded");
			}
			return false;
		}
		return false;
	}
	
	/**
	 * get model instance (specify main model name if you request a local model)
	 * @param string $modelName
	 * @param string $mainModelName
	 * @return Model
	 */
	public function getInstanceModel($modelName, $mainModelName = null) {
		$return = $this->_getInstanceModel($modelName, $mainModelName, true);
		$return->load();
		return $return;
	}
	
	/**
	 * 
	 * @param string $modelName
	 * @param string $mainModelName null if $modelName is a main model name
	 * @param boolean $loadModel
	 * @throws \Exception
	 * @return NULL|Model
	 */
	private function _getInstanceModel($modelName, $mainModelName, $loadModel) {
		$return = null;
		if (is_null($mainModelName)) {
			$instanceModels =& $this->instanceModels;
		} else {
			// call getInstanceModel() to be sure to have a loaded main model
			$mainModel = $this->getInstanceModel($mainModelName);
			if (!array_key_exists($modelName, $this->localTypes[$mainModelName])) {
				$exists = false;
				while (!is_null($mainModel->getExtendsModel()) && !$exists) {
					$exists = array_key_exists($modelName, $this->localTypes[$mainModel->getExtendsModel()->getName()]);
					$mainModel = $mainModel->getExtendsModel();
				}
				if ($exists) {
					$mainModelName = $mainModel->getName();
				}
			}
			$instanceModels =& $this->localTypes[$mainModelName];
		}
		if (!array_key_exists($modelName, $instanceModels)) { // model doesn't exists
			$messageModel = is_null($mainModelName) ? "main model '$modelName'" : "local model '$modelName' in main model '$mainModelName'";
			throw new \Exception("$messageModel doesn't exists, you must define it");
		}
		if (is_object($instanceModels[$modelName])) { // model already initialized
			$return = $instanceModels[$modelName];
		}else {
			if (count($instanceModels[$modelName]) == 3) {
				$return = $instanceModels[$modelName][2];
			} else {
				if (is_null($mainModelName)) {
					$return = new MainModel($modelName, $loadModel);
				} else {
					$return = new LocalModel($modelName, $mainModelName, $loadModel);
				}
				
				if (is_object($instanceModels[$modelName])) {
					if ($instanceModels[$modelName] !== $return) {
						throw new \Exception('already exists '.$modelName.' '.var_export($mainModelName, true));
					}
					if (!$loadModel) {
						throw new \Exception('model has been loaded');
					}
				}
				else { // else add model
					if ($loadModel) {
						$instanceModels[$modelName] = $return;
					} else {
						$instanceModels[$modelName][] = $return;
					}
				}
			}
		}
		return $return;
	}
	
	/**
	 * 
	 * @param Model $model
	 */
	private function _addInstanceModel(Model $model) {
		if ($model instanceof LocalModel) {
			$mainModel = $this->getInstanceModel($model->getMainModelName());
			$instanceModels =& $this->localTypes[$model->getMainModelName()];
		} else {
			$instanceModels =& $this->instanceModels;
		}
		
		if (is_object($instanceModels[$model->getName()])) {
			throw new \Exception('model already added');
		}
		$instanceModels[$model->getName()] = $model;
	}
	
	public function getProperties(Model $model) {
		$return = null;
		
		if ($model instanceof LocalModel) {
			$instanceModels =& $this->localTypes[$model->getMainModel()->getName()];
		} else {
			$instanceModels =& $this->instanceModels;
		}
		
		if (is_null($this->manifestParser) && is_object($instanceModels[$model->getName()]) && $instanceModels[$model->getName()]->isLoaded()) {
			$return = [
				self::PROPERTIES     => $model->getProperties(), 
				self::EXTENDS_MODEL  => $model->getExtendsModel(),
				self::OBJECT_CLASS   => $model->getObjectClass()
			];
			if ($model instanceof MainModel) {
				$return[self::SERIALIZATION] = $model->getSerialization();
			}
		}else {
			$unsetManifestParser = false;
			if (is_null($this->manifestParser)) {
				$unsetManifestParser   = true;
				$manifestPath_afe      = $instanceModels[$model->getName()][0];
				$manifestPath_ad       = dirname($manifestPath_afe);
				$serializationPath_afe = !is_null($instanceModels[$model->getName()][1]) ? $instanceModels[$model->getName()][1] : null;
				$this->manifestParser  = ManifestParser::getInstance($model, $manifestPath_afe, $serializationPath_afe);
				
				$this->_addInstanceModel($model);
				$this->_buildLocalTypes($model, $manifestPath_ad);
			}
			$extendsModel = $this->_getExtendsModel($model);
			
			$return = [
				self::EXTENDS_MODEL => $extendsModel,
				self::OBJECT_CLASS  => $this->manifestParser->getObjectClass(),
				self::PROPERTIES    => $this->_buildProperties($model, $extendsModel)
			];
			
			if ($unsetManifestParser) {
				$this->serializationManifestParser = $this->manifestParser->getSerializationManifestParser();
				unset($this->manifestParser);
				$this->manifestParser = null;
			}
		}
		return $return;
	}
	
	private function _buildLocalTypes($model, $manifestPath_ad) {
		if ($this->manifestParser->isFocusOnLocalTypes()) {
			throw new \Exception('cannot define local types in local types');
		}
		if (!($model instanceof MainModel)) {
			// perhaps allow local models defined in there own manifest to have local types
			return;
		}
		$this->localTypes[$model->getName()] = [];
		if ($this->manifestParser->getLocalTypesCount() > 0) {
			$xmlLocalTypes = [];
			$mainModelName = $model->getName();
			
			$this->manifestParser->registerComplexLocalModels($this->localTypes[$mainModelName], $manifestPath_ad);
			$this->manifestParser->activateFocusOnLocalTypes();
			
			do {
				$typeId = $this->manifestParser->getCurrentLocalTypeId();
				
				if (array_key_exists($typeId, $this->instanceModels)) {
					throw new \Exception("local model in main model '$mainModelName' has same name than another main model '$typeId' ");
				}
				if (array_key_exists($typeId, $this->localTypes[$mainModelName])) {
					throw new \Exception("several local model with same type '$typeId' in main model '$mainModelName'");
				}
				$this->localTypes[$mainModelName][$typeId] = new LocalModel($typeId, $mainModelName, false);
			} while ($this->manifestParser->nextLocalType());
			
			$this->manifestParser->activateFocusOnLocalTypes();
			do {
				$typeId = $this->manifestParser->getCurrentLocalTypeId();
				$this->localTypes[$mainModelName][$typeId]->load();
			} while ($this->manifestParser->nextLocalType());
			
			$this->manifestParser->desactivateFocusOnLocalTypes();
		}
	}
	
	private function _getExtendsModel(Model $model) {
		$extendsModel = null;
		$modelName = $this->manifestParser->getExtends();
		if (!is_null($modelName)) {
			$mainModelName = $model->getMainModelName();
			if ($model instanceof MainModel) {
				$mainModelName = null;
			}
			else if (array_key_exists($modelName, $this->instanceModels)) {
				if (!is_null($mainModelName) && array_key_exists($modelName, $this->localTypes[$mainModelName])) {
					throw new \Exception("cannot determine if property '$modelName' is local or main model");
				}
				$mainModelName = null;
			}
			$manifestParser = $this->manifestParser;
			$this->manifestParser = null;
			$extendsModel = $this->getInstanceModel($modelName, $mainModelName);
			$this->manifestParser = $manifestParser;
		}
		return $extendsModel;
	}
	
	/**
	 * @param Model $currentModel
	 * @param Model $extendsModel
	 * @throws \Exception
	 * @return Property[]
	 */
	private function _buildProperties(Model $currentModel, Model $extendsModel = null) {
		$properties = is_null($extendsModel) ? [] : $extendsModel->getProperties();
	
		do {
			$modelName     = $this->manifestParser->getCurrentPropertyModelName();
			$mainModelName = $currentModel->getMainModelName();
			
			if (array_key_exists($modelName, $this->instanceModels)) {
				if (!is_null($mainModelName) && array_key_exists($modelName, $this->localTypes[$mainModelName])) {
					throw new \Exception("cannot determine if property '$modelName' is local or main model");
				}
				$mainModelName = null;
			}
			
			$propertyModel = $this->_getInstanceModel($modelName, $mainModelName, false);
			$property      = $this->manifestParser->getCurrentProperty($propertyModel);
			
			$properties[$property->getName()] = $property;
		} while ($this->manifestParser->nextProperty());
	
		return $properties;
	}
	
	public function getSerializationInstance(MainModel $model) {
		if (!is_null($this->serializationManifestParser)) {
			$inheritanceKey        =  $this->serializationManifestParser->getInheritanceKey();
			$serializationSettings = $this->serializationManifestParser->getSerializationSettings($model);
			$serialization         = $this->_getUniqueSerialization($model, $serializationSettings, $inheritanceKey);
			unset($this->serializationManifestParser);
			$this->serializationManifestParser = null;
			return $serialization;
		}
		return $this->_getUniqueSerialization($model);
	}
	
	private function _getUniqueSerialization(MainModel $model, ComhonObject $serializationSettings = null, $inheritanceKey = null) {
		$serialization = null;
		if (!is_null($model->getExtendsModel()) && !is_null($model->getExtendsModel()->getSerialization())) {
			$extendedSerializationSettings = $model->getExtendsModel()->getSerialization()->getSettings();
			$extendedInheritanceKey = $model->getExtendsModel()->getSerialization()->getInheritanceKey();
			$same = false;
			
			if (is_null($serializationSettings) || $serializationSettings === $extendedSerializationSettings) {
				$same = true;
			}
			else if ($serializationSettings->getModel()->getName() == $extendedSerializationSettings->getModel()->getName()) {
				$same = true;
				foreach ($serializationSettings->getModel()->getProperties() as $property) {
					if ($serializationSettings->getValue($property->getName()) !== $extendedSerializationSettings->getValue($property->getName())) {
						$same = false;
						break;
					}
				}
			}
			if ($same) {
				$inheritanceKey = is_null($inheritanceKey) ? $extendedInheritanceKey : $inheritanceKey;
				$serialization = SerializationUnit::getInstance($extendedSerializationSettings, $inheritanceKey);
			} else {
				$serialization = SerializationUnit::getInstance($serializationSettings, $inheritanceKey);
			}
		} else if (!is_null($serializationSettings)) {
			$serialization = SerializationUnit::getInstance($serializationSettings, $inheritanceKey);
		}
		return $serialization;
	}
}