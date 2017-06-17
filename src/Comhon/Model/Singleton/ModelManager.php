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
use Comhon\Object\ObjectUnique;

class ModelManager {

	/** @var string */
	const PROPERTIES     = 'properties';
	
	/** @var string */
	const OBJECT_CLASS   = 'objectClass';
	
	/** @var string */
	const SERIALIZATION  = 'serialization';
	
	/** @var string */
	const PARENT_MODEL  = 'parentModel';
	
	/**
	 * @var \Comhon\Model\Model[]
	 *     map that contain all main model and simple model instances
	 *     an element may be a model if model is loaded
	 *     an element may be an array that contain a non loaded model (with needed informations to load it)
	 * 
	 */
	private $instanceModels;
	
	/**
	 * @var [string => Comhon\Model\Model[]] 
	 *     map that contain all local model instances grouped by main model name
	 *     for each group : 
	 *     an element may be a model if model is loaded
	 *     an element may be an array that contain a non loaded model (with needed informations to load it)
	 *
	 */
	private $instanceLocalModels= [];
	
	/**
	 * @var \Comhon\Manifest\Parser\ManifestParser
	 */
	private $manifestParser;
	
	/**
	 * @var \Comhon\Manifest\Parser\SerializationManifestParser
	 */
	private $serializationManifestParser;
	
	/**
	 * @var ModelManager
	 */
	private  static $_instance;
	
	/**
	 * get instance of model manager
	 * 
	 * @return ModelManager
	 */
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
	
	/**
	 * register al simple model
	 */
	private function _registerSimpleModelClasses() {
		$this->instanceModels = [
			ModelInteger::ID  => new ModelInteger(),
			ModelFloat::ID    => new ModelFloat(),
			ModelBoolean::ID  => new ModelBoolean(),
			ModelString::ID   => new ModelString(),
			ModelDateTime::ID => new ModelDateTime()
		];
	}
	
	/**
	 * verify if model has been registered
	 * 
	 * @param string $modelName name of wanted model
	 * @param string $mainModelName if not null $modelName is considered as local model name
	 * @return boolean
	 */
	public function hasModel($modelName, $mainModelName = null) {
		if (is_null($mainModelName)) {
			return array_key_exists($modelName, $this->instanceModels);
		} else {
			return array_key_exists($mainModelName, $this->instanceLocalModels) && array_key_exists($modelName, $this->instanceLocalModels[$mainModelName]);
		}
	}
	
	/**
	 * verify if has model instance (not necessary loaded)
	 *
	 * @param string $modelName name of wanted model
	 * @param string $mainModelName if not null $modelName is considered as local model name
	 * @throws \Exception if model has not been registered
	 * @return boolean
	 */
	public function hasInstanceModel($modelName, $mainModelName = null) {
		if (!$this->hasModel($modelName, $mainModelName)) {
			throw new \Exception("model $modelName doesn't exists");
		}
		if (is_null($mainModelName)) {
			$instanceModels =& $this->instanceModels;
		} else {
			$instanceModels =& $this->instanceLocalModels[$mainModelName];
		}
		return is_object($instanceModels[$modelName]) || array_key_exists(2, $instanceModels[$modelName]);
	}
	
	/**
	 * verify if model is loaded
	 *
	 * @param string $modelName name of wanted model
	 * @param string $mainModelName if not null $modelName is considered as local model name
	 * @throws \Exception if model has not been registered
	 * @return boolean
	 */
	public function isModelLoaded($modelName, $mainModelName = null) {
		if (!$this->hasModel($modelName, $mainModelName)) {
			throw new \Exception("model $modelName doesn't exists");
		}
		if (is_null($mainModelName)) {
			$instanceModels =& $this->instanceModels;
		} else {
			$instanceModels =& $this->instanceLocalModels[$mainModelName];
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
	 * get model instance
	 * 
	 * @param string $modelName name of wanted model
	 * @param string $mainModelName if not null $modelName is considered as local model name
	 * @return \Comhon\Model\LocalModel|\Comhon\Model\MainModel
	 */
	public function getInstanceModel($modelName, $mainModelName = null) {
		$return = $this->_getInstanceModel($modelName, $mainModelName, true);
		$return->load();
		return $return;
	}
	
	/**
	 * get model instance
	 * 
	 * unlike public method, retrieved model is not necessarily loaded
	 * 
	 * @param string $modelName name of wanted model
	 * @param string $mainModelName if not null $modelName is considered as local model name
	 * @param boolean $loadModel true to load model not already instanciated
	 * @throws \Exception
	 * @return \Comhon\Model\Model
	 */
	private function _getInstanceModel($modelName, $mainModelName, $loadModel) {
		if (is_null($mainModelName)) {
			$instanceModels =& $this->instanceModels;
		} else {
			// call getInstanceModel() to be sure to have a loaded main model
			$mainModel = $this->getInstanceModel($mainModelName);
			if (!array_key_exists($modelName, $this->instanceLocalModels[$mainModelName])) {
				$exists = false;
				while (!is_null($mainModel->getParent()) && !$exists) {
					$exists = array_key_exists($modelName, $this->instanceLocalModels[$mainModel->getParent()->getName()]);
					$mainModel = $mainModel->getParent();
				}
				if ($exists) {
					$mainModelName = $mainModel->getName();
				}
			}
			$instanceModels =& $this->instanceLocalModels[$mainModelName];
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
	 * add loaded instance model
	 * 
	 * @param \Comhon\Model\Model $model
	 */
	private function _addInstanceModel(Model $model) {
		if ($model instanceof LocalModel) {
			$mainModel = $this->getInstanceModel($model->getMainModelName());
			$instanceModels =& $this->instanceLocalModels[$model->getMainModelName()];
		} else {
			$instanceModels =& $this->instanceModels;
		}
		
		if (is_object($instanceModels[$model->getName()])) {
			throw new \Exception('model already added');
		}
		$instanceModels[$model->getName()] = $model;
	}
	
	/**
	 * get properties (and optional parent model, object class and serialization) of specified model
	 * 
	 * @param \Comhon\Model\Model $model
	 * @return [
	 *     self::PROPERTIES    => \Comhon\Model\Property\Property[]
	 *     self::PARENT_MODEL  => \Comhon\Model\Model|null
	 *     self::OBJECT_CLASS  => string|null
	 *     self::SERIALIZATION => \Comhon\Serialization\SerializationUnit|null
	 * ]
	 */
	public function getProperties(Model $model) {
		$return = null;
		
		if ($model instanceof LocalModel) {
			$instanceModels =& $this->instanceLocalModels[$model->getMainModel()->getName()];
		} else {
			$instanceModels =& $this->instanceModels;
		}
		
		if (is_null($this->manifestParser) && is_object($instanceModels[$model->getName()]) && $instanceModels[$model->getName()]->isLoaded()) {
			$return = [
				self::PROPERTIES     => $model->getProperties(), 
				self::PARENT_MODEL   => $model->getParent(),
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
				$this->_buildLocalModels($model, $manifestPath_ad);
			}
			$parentModel = $this->_getParentModel($model);
			
			$return = [
				self::PARENT_MODEL  => $parentModel,
				self::OBJECT_CLASS  => $this->manifestParser->getObjectClass(),
				self::PROPERTIES    => $this->_buildProperties($model, $parentModel)
			];
			
			if ($unsetManifestParser) {
				$this->serializationManifestParser = $this->manifestParser->getSerializationManifestParser();
				unset($this->manifestParser);
				$this->manifestParser = null;
			}
		}
		return $return;
	}
	
	/**
	 * build local models
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $manifestPath_ad
	 * @throws \Exception
	 */
	private function _buildLocalModels(Model $model, $manifestPath_ad) {
		if ($this->manifestParser->isFocusOnLocalModel()) {
			throw new \Exception('cannot define local model in local models');
		}
		if (!($model instanceof MainModel)) {
			// perhaps allow local models defined in there own manifest to have local models
			return;
		}
		$this->instanceLocalModels[$model->getName()] = [];
		if ($this->manifestParser->getLocalModelCount() > 0) {
			$mainModelName = $model->getName();
			
			$this->manifestParser->registerComplexLocalModels($this->instanceLocalModels[$mainModelName], $manifestPath_ad);
			$this->manifestParser->activateFocusOnLocalModels();
			
			do {
				$localModelName = $this->manifestParser->getCurrentLocalModelName();
				
				if (array_key_exists($localModelName, $this->instanceModels)) {
					throw new \Exception("local model in main model '$mainModelName' has same name than another main model '$localModelName' ");
				}
				if (array_key_exists($localModelName, $this->instanceLocalModels[$mainModelName])) {
					throw new \Exception("several local model with same name '$localModelName' in main model '$mainModelName'");
				}
				$this->instanceLocalModels[$mainModelName][$localModelName] = new LocalModel($localModelName, $mainModelName, false);
			} while ($this->manifestParser->nextLocalModel());
			
			$this->manifestParser->activateFocusOnLocalModels();
			do {
				$localModelName = $this->manifestParser->getCurrentLocalModelName();
				$this->instanceLocalModels[$mainModelName][$localModelName]->load();
			} while ($this->manifestParser->nextLocalModel());
			
			$this->manifestParser->desactivateFocusOnLocalModels();
		}
	}
	
	/**
	 * get parent model if exists
	 * 
	 * @param \Comhon\Model\Model $model
	 * @throws \Exception
	 * @return \Comhon\Model\Model|null null if no parent model
	 */
	private function _getParentModel(Model $model) {
		$parentModel = null;
		$modelName = $this->manifestParser->getExtends();
		if (!is_null($modelName)) {
			$mainModelName = $model->getMainModelName();
			if ($model instanceof MainModel) {
				$mainModelName = null;
			}
			else if (array_key_exists($modelName, $this->instanceModels)) {
				if (!is_null($mainModelName) && array_key_exists($modelName, $this->instanceLocalModels[$mainModelName])) {
					throw new \Exception("cannot determine if property '$modelName' is local or main model");
				}
				$mainModelName = null;
			}
			$manifestParser = $this->manifestParser;
			$this->manifestParser = null;
			$parentModel = $this->getInstanceModel($modelName, $mainModelName);
			$this->manifestParser = $manifestParser;
		}
		return $parentModel;
	}
	
	/**
	 * build model properties
	 * 
	 * @param \Comhon\Model\Model $currentModel
	 * @param \Comhon\Model\Model|null $parentModel
	 * @throws \Exception
	 * @return \Comhon\Model\Property\Property[]
	 */
	private function _buildProperties(Model $currentModel, Model $parentModel = null) {
		$properties = is_null($parentModel) ? [] : $parentModel->getProperties();
	
		do {
			$modelName     = $this->manifestParser->getCurrentPropertyModelName();
			$mainModelName = $currentModel->getMainModelName();
			
			if (array_key_exists($modelName, $this->instanceModels)) {
				if (!is_null($mainModelName) && array_key_exists($modelName, $this->instanceLocalModels[$mainModelName])) {
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
	
	/**
	 * get serialization if exists
	 * 
	 * @param \Comhon\Model\MainModel $model
	 * @return \Comhon\Serialization\SerializationUnit|null null if no serialization
	 */
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
	
	/**
	 * get serialization from parent model if exists and if needed
	 * 
	 * if current model has same serialization settings than it parent model, 
	 * we take parent model serialization to avoid to duplicated serializations
	 * 
	 * @param \Comhon\Model\MainModel $model
	 * @param \Comhon\Object\ObjectUnique $serializationSettings
	 * @param string $inheritanceKey
	 * @return \Comhon\Serialization\SerializationUnit|null null if no serialization
	 */
	private function _getUniqueSerialization(MainModel $model, ObjectUnique $serializationSettings = null, $inheritanceKey = null) {
		$serialization = null;
		if (!is_null($model->getParent()) && !is_null($model->getParent()->getSerialization())) {
			$extendedSerializationSettings = $model->getParent()->getSerialization()->getSettings();
			$extendedInheritanceKey = $model->getParent()->getSerialization()->getInheritanceKey();
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