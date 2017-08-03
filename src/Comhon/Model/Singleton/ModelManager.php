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
use Comhon\Model\ModelPercentage;
use Comhon\Model\ModelIndex;
use Comhon\Model\ModelDateTime;
use Comhon\Model\Model;
use Comhon\Model\MainModel;
use Comhon\Model\LocalModel;
use Comhon\Model\Property\Property;
use Comhon\Serialization\SerializationUnit;
use Comhon\Object\Config\Config;
use Comhon\Manifest\Parser\ManifestParser;
use Comhon\Object\ObjectUnique;
use Comhon\Exception\NotDefinedModelException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\AlreadyUsedModelNameException;

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
	 */
	private $instanceModels;
	
	/**
	 * @var \Comhon\Model\SimpleModel[] map that contain all simple model instances
	 */
	private $instanceSimpleModels;
	
	/**
	 * @var string namespace to apply on local models during manifest parsing
	 */
	private $currentNamespace;
	
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
		$this->instanceSimpleModels = [
			ModelInteger::ID    => new ModelInteger(),
			ModelFloat::ID      => new ModelFloat(),
			ModelBoolean::ID    => new ModelBoolean(),
			ModelString::ID     => new ModelString(),
			ModelIndex::ID      => new ModelIndex(),
			ModelPercentage::ID => new ModelPercentage(),
			ModelDateTime::ID   => new ModelDateTime()
		];
		$this->instanceModels = $this->instanceSimpleModels;
	}
	
	/**
	 * verify if model has been registered
	 * 
	 * @param string $modelName fully qualified name of wanted model
	 * @return boolean
	 */
	public function hasModel($modelName) {
		return array_key_exists($modelName, $this->instanceModels);
	}
	
	/**
	 * verify if has model instance (not necessary loaded)
	 *
	 * @param string $modelName fully qualified name of wanted model
	 * @throws \Exception if model has not been registered
	 * @return boolean
	 */
	public function hasInstanceModel($modelName) {
		if (!$this->hasModel($modelName)) {
			throw new NotDefinedModelException($modelName);
		}
		return is_object($this->instanceModels[$modelName]) || array_key_exists(2, $this->instanceModels[$modelName]);
	}
	
	/**
	 * verify if model is loaded
	 *
	 * @param string $modelName fully qualified name of wanted model
	 * @throws \Exception if model has not been registered
	 * @return boolean
	 */
	public function isModelLoaded($modelName) {
		if (!$this->hasModel($modelName)) {
			throw new NotDefinedModelException($modelName);
		}
		if (is_object($this->instanceModels[$modelName])) {
			if (!$this->instanceModels[$modelName]->isLoaded()) {
				throw new ComhonException("$modelName must be loaded");
			}
			return true;
		}
		if (array_key_exists(2, $this->instanceModels[$modelName])) {
			if ($this->instanceModels[$modelName][2]->isLoaded()) {
				throw new ComhonException("$modelName must be not loaded");
			}
			return false;
		}
		return false;
	}
	
	/**
	 * get model instance
	 * 
	 * @param string $modelName fully qualified name of wanted model
	 * @return \Comhon\Model\LocalModel|\Comhon\Model\MainModel
	 */
	public function getInstanceModel($modelName) {
		$return = $this->_getInstanceModel($modelName, true);
		return $return;
	}
	
	/**
	 * get model instance
	 * 
	 * unlike public method, retrieved model is not necessarily loaded
	 * 
	 * @param string $modelName fully qualified name of wanted model
	 * @param boolean $loadModel true to load model not already instanciated
	 * @throws \Exception
	 * @return \Comhon\Model\Model
	 */
	private function _getInstanceModel($modelName, $loadModel) {
		if (!array_key_exists($modelName, $this->instanceModels)) { // model doesn't exists in map
			$this->loadIntermediateManifest($modelName);
			if (!array_key_exists($modelName, $this->instanceModels)) {
				throw new NotDefinedModelException($modelName);
			}
		}
		if (is_object($this->instanceModels[$modelName])) { // model already initialized
			$return = $this->instanceModels[$modelName];
		}else {
			if (count($this->instanceModels[$modelName]) == 3) {
				$return = $this->instanceModels[$modelName][2];
				if ($loadModel) {
					$return->load();
				}
			} else {
				if ($this->_isMainModel($modelName)) {
					$return = new MainModel($modelName, $loadModel);
				} else {
					$return = new LocalModel($modelName, $loadModel);
				}
				
				if (is_object($this->instanceModels[$modelName])) {
					if ($this->instanceModels[$modelName] !== $return) {
						throw new ComhonException('already exists '.$modelName);
					}
					if (!$loadModel) {
						throw new ComhonException('model has been loaded');
					}
				}
				else { // else add model
					if ($loadModel) {
						$this->instanceModels[$modelName] = $return;
					} else {
						$this->instanceModels[$modelName][] = $return;
					}
				}
			}
		}
		return $return;
	}
	
	/**
	 * try to load intermediate manifests
	 * 
	 * @param string $modelName
	 * @throws \Exception
	 */
	private function loadIntermediateManifest($modelName) {
		$explodedModelName = explode('\\', $modelName);
		
		if (count($explodedModelName) > 1) {
			array_pop($explodedModelName);
			$intermediateModelName = $explodedModelName[0];
			if (!array_key_exists($intermediateModelName, $this->instanceModels)) {
				throw new NotDefinedModelException($intermediateModelName);
			}
			$this->_getInstanceModel($intermediateModelName, true);
			
			for ($i = 1; $i < count($explodedModelName); $i++) {
				$intermediateModelName .= '\\' . $explodedModelName[$i];
				if (!array_key_exists($intermediateModelName, $this->instanceModels)) {
					throw new NotDefinedModelException($intermediateModelName);
				}
				$model = $this->_getInstanceModel($intermediateModelName, true);
			}
		}
	}
	
	private function _isMainModel($modelName) {
		return strpos($modelName, '\\') === false;
	}
	
	/**
	 * add loaded instance model
	 * 
	 * @param \Comhon\Model\Model $model
	 */
	private function _addInstanceModel(Model $model) {
		if (is_object($this->instanceModels[$model->getName()])) {
			throw new ComhonException('model already added');
		}
		$this->instanceModels[$model->getName()] = $model;
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
		
		if (is_null($this->manifestParser) && is_object($this->instanceModels[$model->getName()]) && $this->instanceModels[$model->getName()]->isLoaded()) {
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
				$unsetManifestParser    = true;
				$manifestPath_afe       = $this->instanceModels[$model->getName()][0];
				$manifestPath_ad        = dirname($manifestPath_afe);
				$serializationPath_afe  = !is_null($this->instanceModels[$model->getName()][1]) ? $this->instanceModels[$model->getName()][1] : null;
				$this->manifestParser   = ManifestParser::getInstance($model, $manifestPath_afe, $serializationPath_afe);
				$this->currentNamespace = $model->getName();
				
				$this->_addInstanceModel($model);
				$this->_buildLocalModels($model, $manifestPath_ad);
			}
			$parentModel = $this->_getParentModel($model);
			
			$return = [
				self::PARENT_MODEL  => $parentModel,
				self::OBJECT_CLASS  => $this->manifestParser->getObjectClass(),
				self::PROPERTIES    => $this->_buildProperties($parentModel)
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
			throw new ComhonException('cannot define local model inside local model');
		}
		if ($this->manifestParser->getLocalModelCount() > 0) {
			$this->manifestParser->registerComplexLocalModels($this->instanceModels, $manifestPath_ad, $this->currentNamespace);
			$this->manifestParser->activateFocusOnLocalModels();
			
			do {
				$localModelName = $this->currentNamespace. '\\' . $this->manifestParser->getCurrentLocalModelName();
				if (array_key_exists($localModelName, $this->instanceModels)) {
					throw new AlreadyUsedModelNameException($localModelName);
				}
				$this->instanceModels[$localModelName] = new LocalModel($localModelName, false);
			} while ($this->manifestParser->nextLocalModel());
			
			$this->manifestParser->activateFocusOnLocalModels();
			do {
				$localModelName = $this->currentNamespace. '\\' . $this->manifestParser->getCurrentLocalModelName();
				$this->instanceModels[$localModelName]->load();
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
		
		if (array_key_exists($modelName, $this->instanceSimpleModels)) {
			throw new ComhonException("{$model->getName()} cannot extends from $modelName");
		}
		
		if (!is_null($modelName)) {
			$modelName = $modelName[0] == '\\' ? substr($modelName, 1) : $this->currentNamespace. '\\' . $modelName;
			
			$manifestParser = $this->manifestParser;
			$this->manifestParser = null;
			$parentModel = $this->getInstanceModel($modelName);
			$this->manifestParser = $manifestParser;
		}
		return $parentModel;
	}
	
	/**
	 * build model properties
	 * 
	 * @param \Comhon\Model\Model|null $parentModel
	 * @throws \Exception
	 * @return \Comhon\Model\Property\Property[]
	 */
	private function _buildProperties(Model $parentModel = null) {
		$properties = is_null($parentModel) ? [] : $parentModel->getProperties();
		do {
			$modelName = $this->manifestParser->getCurrentPropertyModelName();
			if (!array_key_exists($modelName, $this->instanceSimpleModels)) {
				$modelName = ($modelName[0] != '\\') 
					? $this->currentNamespace. '\\' . $modelName 
					: substr($modelName, 1) ;
			}
			
			$propertyModel = $this->_getInstanceModel($modelName, false);
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