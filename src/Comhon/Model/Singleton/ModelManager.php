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
use Comhon\Exception\ConfigFileNotFoundException;

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
	 * @var string[] map namespace prefix to directory to allow manifest autoloading
	 */
	private $autoloadManifest = [
		'Comhon' => __DIR__ . '/../../Manifest/Collection/Manifest'
	];
	
	/**
	 * @var string[] map namespace prefix to directory to allow serialization manifest autoloading
	 */
	private $autoloadSerializationManifest = [
		'Comhon' => __DIR__ . '/../../Manifest/Collection/Serialization'
	];
	
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
		
		if (Config::getInstance()->hasValue('sqlTable')) {
			$path = Config::getInstance()->getSerializationSqlTablePath();
			if (!is_dir($path)) {
				throw new ConfigFileNotFoundException('sqlTable', 'directory', Config::getInstance()->getSerializationSqlTablePath(false));
			}
			$this->getInstanceModel('sqlTable')->getSerializationSettings()->setValue('saticPath', $path);
		}
		if (Config::getInstance()->hasValue('sqlDatabase')) {
			$path = Config::getInstance()->getSerializationSqlDatabasePath();
			if (!is_dir($path)) {
				throw new ConfigFileNotFoundException('sqlDatabase', 'directory', Config::getInstance()->getSerializationSqlDatabasePath(false));
			}
			$this->getInstanceModel('sqlDatabase')->getSerializationSettings()->setValue('saticPath', $path);
		}
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
	 * verify if model exists (i.e. is defined by an manifest or is a simple model)
	 * 
	 * @param string $modelName fully qualified name of wanted model
	 * @return boolean
	 */
	public function modelExists($modelName) {
		return array_key_exists($modelName, $this->instanceModels) || $this->manifestExists($modelName);
	}
	
	/**
	 * verify if manifest exists
	 *
	 * @param string $modelName model fully qualified name
	 * @return boolean
	 */
	public function manifestExists($modelName) {
		return array_key_exists($modelName, $this->instanceModels);
	}
	
	/**
	 * verify if specified model is instanciated (not necessary loaded)
	 *
	 * @param string $modelName fully qualified name of wanted model
	 * @return boolean
	 */
	public function hasInstanceModel($modelName) {
		return array_key_exists($modelName, $this->instanceModels);
	}
	
	/**
	 * verify if specified model is instanciated and loaded
	 *
	 * @param string $modelName fully qualified name of wanted model
	 * @throws \Exception if model has not been registered
	 * @return boolean
	 */
	public function hasInstanceModelLoaded($modelName) {
		return array_key_exists($modelName, $this->instanceModels) && $this->instanceModels[$modelName]->isLoaded();
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
		if (!array_key_exists($modelName, $this->instanceModels)) {
			list($prefix, $suffix) = $this->_splitModelName($modelName);
			var_dump('***********');
			var_dump($prefix);
			var_dump($suffix);
			var_dump($this->_getManifestPath($prefix, $suffix));
			if (file_exists($this->_getManifestPath($prefix, $suffix))) {
				$model = $this->_isMainModel($suffix) ? new MainModel($modelName) : new LocalModel($modelName);
				$this->_addInstanceModel($model);
			} else {
				$this->loadIntermediateManifest($prefix, $suffix);
				if (!array_key_exists($modelName, $this->instanceModels)) {
					throw new NotDefinedModelException($modelName);
				}
			}
		}
		if ($loadModel) {
			$this->instanceModels[$modelName]->load();
		}
		return $this->instanceModels[$modelName];
	}
	
	private function _getManifestPath($nameSpacePrefix, $nameSpaceSuffix) {
		$prefix_ad = substr($this->autoloadManifest[$nameSpacePrefix], 0, 1) == '.'
			? Config::getLoadPath() . DIRECTORY_SEPARATOR . $this->autoloadManifest[$nameSpacePrefix]
			: $this->autoloadManifest[$nameSpacePrefix];
		return $prefix_ad . '/' . str_replace('\\', '/', $nameSpaceSuffix) . '/manifest';
	}
		
	private function _getSerializationManifestPath($manifest_af, $nameSpacePrefix, $nameSpaceSuffix) {
		if (array_key_exists($nameSpacePrefix, $this->autoloadSerializationManifest)) {
			$prefix_ad = substr($this->autoloadSerializationManifest[$nameSpacePrefix], 0, 1) == '.'
				? Config::getLoadPath() . DIRECTORY_SEPARATOR . $this->autoloadSerializationManifest[$nameSpacePrefix]
				: $this->autoloadSerializationManifest[$nameSpacePrefix];
			$manifest_af = $prefix_ad . '/' . str_replace('\\', '/', $nameSpaceSuffix) . '/serialization';
		} else {
			$manifest_af = dirname($manifest_af) . '/serialization';
		}
		return file_exists($manifest_af) ? $manifest_af : null;
	}
	
	/**
	 * try to load intermediate manifests
	 * 
	 * @param string $modelName
	 * @throws \Exception
	 */
	private function loadIntermediateManifest($nameSpacePrefix, $nameSpaceSuffix) {
		$parentNameSpaceSuffix = $nameSpaceSuffix;
		
		while ($separatorOffset = strrpos($parentNameSpaceSuffix, '\\') !== false) {
			$parentNameSpaceSuffix.= substr($parentNameSpaceSuffix, 0, $separatorOffset);
			$parentNameSpace = $nameSpacePrefix . '\\' . $parentNameSpaceSuffix;
			
			if (array_key_exists($parentNameSpace, $this->instanceModels)) {
				$this->instanceModels[$parentNameSpace]->load();
				$parentNameSpaceSuffix = '';
			} elseif (file_exists($this->_getManifestPath($nameSpacePrefix, $parentNameSpaceSuffix))) {
				$this->getInstanceModel($parentNameSpace);
				$parentNameSpaceSuffix = '';
			}
		}
	}
	
	private function _isMainModel($suffixModelName) {
		return strpos($suffixModelName, '\\') === false;
	}
	
	/**
	 * 
	 * @param string $modelName
	 * @throws ComhonException
	 * @return string[]
	 */
	private function _splitModelName($modelName) {
		$prefix = '';
		$suffix = $modelName;
		do {
			$separatorOffset = strpos($suffix, '\\');
			if ($separatorOffset === false) {
				throw new NotDefinedModelException($modelName);
			}
			$prefix .= substr($suffix, 0, $separatorOffset);
			$suffix = substr($suffix, $separatorOffset + 1);
		} while (!array_key_exists($prefix, $this->autoloadManifest));
		
		return [$prefix, $suffix];
	}
	
	/**
	 * add loaded instance model
	 * 
	 * @param \Comhon\Model\Model $model
	 * @return array
	 */
	private function _addInstanceModel(Model $model) {
		if (array_key_exists($model->getName(), $this->instanceModels)) {
			throw new AlreadyUsedModelNameException($model->getName());
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
		
		try {
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
					list($prefix, $suffix)  = $this->_splitModelName($model->getName());
					$manifestPath_afe       = $this->_getManifestPath($nameSpacePrefix, $nameSpaceSuffix);
					$serializationPath_afe  = $this->_getSerializationManifestPath($manifestPath_afe, $nameSpacePrefix, $nameSpaceSuffix);
					$this->manifestParser   = ManifestParser::getInstance($model, $manifestPath_afe, $serializationPath_afe);
					$this->currentNamespace = $model->getName();
					
					$this->_buildLocalModels($model);
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
		} catch (\Exception $e) {
			$this->manifestParser = null;
			throw $e;
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
	private function _buildLocalModels(Model $model) {
		if ($this->manifestParser->isFocusOnLocalModel()) {
			throw new ComhonException('cannot define local model inside local model');
		}
		if ($this->manifestParser->getLocalModelCount() > 0) {
			$this->manifestParser->activateFocusOnLocalModels();
			
			do {
				$localModelName = $this->currentNamespace. '\\' . $this->manifestParser->getCurrentLocalModelName();
				$this->_addInstanceModel(new LocalModel($localModelName));
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
		if ($this->manifestParser->getCurrentPropertiesCount() > 0) {
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
		}
	
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