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
use Comhon\Object\Config\Config;
use Comhon\Manifest\Parser\ManifestParser;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Model\NotDefinedModelException;
use Comhon\Exception\ComhonException;
use Comhon\Exception\Model\AlreadyUsedModelNameException;
use Comhon\Exception\Config\ConfigFileNotFoundException;
use Comhon\Model\ModelUnique;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Serialization\Serialization;
use Comhon\Manifest\Parser\SerializationManifestParser;

class ModelManager {

	/** @var string */
	const PROPERTIES      = 'properties';
	
	/** @var string */
	const OBJECT_CLASS    = 'objectClass';
	
	/** @var string */
	const SERIALIZATION   = 'serialization';
	
	/** @var string */
	const PARENT_MODEL    = 'parentModel';
	
	/** @var string */
	const IS_MAIN_MODEL   = 'isMainModel';
	
	/** @var string */
	const IS_SERIALIZABLE = 'isSerializable';
	
	/**
	 * @var \Comhon\Model\AbstractModel[]
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
	 * @var string
	 */
	private $manifestExtension = 'json';
	
	/**
	 * @var string[] map namespace prefix to directory to allow manifest autoloading
	 */
	private $autoloadManifest = [
		'Comhon' => __DIR__ . DIRECTORY_SEPARATOR 
			. '..' . DIRECTORY_SEPARATOR 
			. '..' . DIRECTORY_SEPARATOR 
			. 'Manifest' . DIRECTORY_SEPARATOR 
			. 'Collection' . DIRECTORY_SEPARATOR 
			. 'Manifest'
	];
	
	/**
	 * @var string[] map namespace prefix to directory to allow serialization manifest autoloading
	 */
	private $autoloadSerializationManifest = [
		'Comhon' => __DIR__ . DIRECTORY_SEPARATOR 
			. '..' . DIRECTORY_SEPARATOR 
			. '..' . DIRECTORY_SEPARATOR 
			. 'Manifest' . DIRECTORY_SEPARATOR 
			. 'Collection' . DIRECTORY_SEPARATOR 
			. 'Serialization'
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
		
		try {
			$this->_registerSimpleModelClasses();
			
			if (Config::getInstance()->hasValue('sqlTable')) {
				$path = Config::getInstance()->getSerializationSqlTablePath();
				if (!is_dir($path)) {
					throw new ConfigFileNotFoundException('sqlTable', 'directory', Config::getInstance()->getSerializationSqlTablePath(false));
				}
				$this->getInstanceModel('Comhon\SqlTable')->getSerializationSettings()->setValue('saticPath', $path);
			}
			if (Config::getInstance()->hasValue('sqlDatabase')) {
				$path = Config::getInstance()->getSerializationSqlDatabasePath();
				if (!is_dir($path)) {
					throw new ConfigFileNotFoundException('sqlDatabase', 'directory', Config::getInstance()->getSerializationSqlDatabasePath(false));
				}
				$this->getInstanceModel('Comhon\SqlDatabase')->getSerializationSettings()->setValue('saticPath', $path);
			}
			if (!is_null(Config::getInstance()->getValue('manifestFormat'))) {
				$this->manifestExtension = Config::getInstance()->getValue('manifestFormat');
			}
			$lManifestAutoloadList = Config::getInstance()->getManifestAutoloadList();
			if (!is_null($lManifestAutoloadList)) {
				$comhonPath_ad = $this->autoloadManifest['Comhon'];
				$this->autoloadManifest = $lManifestAutoloadList->getValues();
				$this->autoloadManifest['Comhon'] = $comhonPath_ad;
			}
			$lSerializationManifestAutoloadList = Config::getInstance()->getSerializationAutoloadList();
			if (!is_null($lSerializationManifestAutoloadList)) {
				$comhonPath_ad = $this->autoloadSerializationManifest['Comhon'];
				$this->autoloadSerializationManifest = $lSerializationManifestAutoloadList->getValues();
				$this->autoloadSerializationManifest['Comhon'] = $comhonPath_ad;
			}
		} catch (\Exception $e) {
			self::$_instance = null;
			throw $e;
		}
	}
	
	/**
	 * reset singleton - should be called only for testing (reset main object collection too)
	 */
	public static function resetSingleton() {
		MainObjectCollection::getInstance()->reset();
		self::$_instance = null;
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
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
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
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	private function _getInstanceModel($modelName, $loadModel) {
		if (!array_key_exists($modelName, $this->instanceModels)) {
			$model = new Model($modelName);
			$this->_addInstanceModel($model);
		}
		if ($loadModel) {
			$this->instanceModels[$modelName]->load();
		}
		return $this->instanceModels[$modelName];
	}
	
	/**
	 * 
	 * @param string $nameSpacePrefix
	 * @param string $nameSpaceSuffix
	 * @return string
	 */
	private function _getManifestPath($nameSpacePrefix, $nameSpaceSuffix) {
		if (!array_key_exists($nameSpacePrefix, $this->autoloadManifest)) {
			throw new ComhonException("prefix namespace '$nameSpacePrefix' do not belong to autoload manifest list");
		}
		$prefix_ad = substr($this->autoloadManifest[$nameSpacePrefix], 0, 1) == '.'
			? Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $this->autoloadManifest[$nameSpacePrefix]
			: $this->autoloadManifest[$nameSpacePrefix];
		return $prefix_ad . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $nameSpaceSuffix) . DIRECTORY_SEPARATOR .'manifest.' . $this->manifestExtension;
	}
		
	private function _getSerializationManifestPath($manifest_af, $nameSpacePrefix, $nameSpaceSuffix) {
		if (array_key_exists($nameSpacePrefix, $this->autoloadSerializationManifest)) {
			$prefix_ad = substr($this->autoloadSerializationManifest[$nameSpacePrefix], 0, 1) == '.'
				? Config::getInstance()->getDirectory(). DIRECTORY_SEPARATOR . $this->autoloadSerializationManifest[$nameSpacePrefix]
				: $this->autoloadSerializationManifest[$nameSpacePrefix];
			$manifest_af = $prefix_ad . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $nameSpaceSuffix) . DIRECTORY_SEPARATOR .'serialization.' . $this->manifestExtension;
		} else {
			$manifest_af = dirname($manifest_af) . DIRECTORY_SEPARATOR .'serialization.' . $this->manifestExtension;
		}
		return file_exists($manifest_af) ? $manifest_af : null;
	}
	
	/**
	 * load model from "intermediate" manifests. 
	 * actually specified model is not defined in manifest but it may be defined in another manifest as local type.
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param string $nameSpacePrefix
	 * @param string $nameSpaceSuffix
	 */
	private function _loadModelFromIntermediateManifest(Model $model, $nameSpacePrefix, $nameSpaceSuffix) {
		$parentNameSpaceSuffix = $nameSpaceSuffix;
		
		// at this point (before following line) the current local model is tagged as loading
		// so if we try to call load function on it, it will do nothing more
		// but we will "re"load it through to intermediate manifest 
		// so we have to abort current loading to be able to truly load it
		$model->abortLoading();
		
		while (($separatorOffset = strrpos($parentNameSpaceSuffix, '\\')) !== false) {
			$parentNameSpaceSuffix = substr($parentNameSpaceSuffix, 0, $separatorOffset);
			$parentNameSpace = $nameSpacePrefix . '\\' . $parentNameSpaceSuffix;
			
			if (array_key_exists($parentNameSpace, $this->instanceModels)) {
				$this->instanceModels[$parentNameSpace]->load();
				$parentNameSpaceSuffix = '';
			} elseif (file_exists($this->_getManifestPath($nameSpacePrefix, $parentNameSpaceSuffix))) {
				$this->getInstanceModel($parentNameSpace);
				$parentNameSpaceSuffix = '';
			}
		}
		
		// current handled model should be loaded during intermediate model loading
		// if not, that means model doesn't exist.
		if (!$model->isLoaded()) {
			throw new NotDefinedModelException($model->getName());
		}
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
	 * @param \Comhon\Model\ModelUnique $model
	 * @return array
	 */
	private function _addInstanceModel(ModelUnique $model) {
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
					self::IS_MAIN_MODEL  => $model->isMain(),
					self::PROPERTIES     => $model->getProperties(), 
					self::PARENT_MODEL   => $model->getParent(),
					self::OBJECT_CLASS   => $model->getObjectClass(),
					self::SERIALIZATION  => $model->getSerialization()
				];
			}else {
				if (is_null($this->manifestParser)) {
					$isLocalType = false;
					list($prefix, $suffix) = $this->_splitModelName($model->getName());
					
					// if manifest file doesn't exists that probably means that we are handling a local type defined in a manifest
					// so we have to load the "intermediate" model from manifest and so the local model
					if (!file_exists($this->_getManifestPath($prefix, $suffix))) {
						$this->_loadModelFromIntermediateManifest($model, $prefix, $suffix);
						
						// current handled model loading is stopped because already loaded from intermediate model loading
						return null;
					}
					
					$manifestPath_afe       = $this->_getManifestPath($prefix, $suffix);
					$serializationPath_afe  = $this->_getSerializationManifestPath($manifestPath_afe, $prefix, $suffix);
					$this->manifestParser   = ManifestParser::getInstance($manifestPath_afe, $serializationPath_afe);
					$this->currentNamespace = $model->getName();
					$isMain                 = $this->manifestParser->isMain();
					$isSerializable         = $this->manifestParser->isSerializable();
					$serialManifestParser   = $this->manifestParser->getSerializationManifestParser();
					
					$this->_buildLocalModels();
				} else {
					$isLocalType = true;
					$isMain = false;
					$isSerializable = false;
					$serialManifestParser = null;
				}
				$parentModel = $this->_getParentModel($model);
				
				$return = [
					self::PARENT_MODEL    => $parentModel,
					self::OBJECT_CLASS    => $this->manifestParser->getObjectClass(),
					self::PROPERTIES      => $this->_buildProperties($parentModel, $model->getName()),
				];
				
				if (!$isLocalType) {
					$this->manifestParser = null;
				}
				
				// must be done after set $this->manifestParser to null because new model may be instanciated
				$return[self::SERIALIZATION] = $isLocalType ? null
					: $this->_getSerializationInstance($serialManifestParser, $isSerializable, $parentModel);
				$return[self::IS_MAIN_MODEL] = $return[self::SERIALIZATION] ? true : $isMain;
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
	 * @param string $manifestPath_ad
	 * @throws \Exception
	 */
	private function _buildLocalModels() {
		if ($this->manifestParser->isFocusOnLocalModel()) {
			throw new ComhonException('cannot define local model inside local model');
		}
		if ($this->manifestParser->getLocalModelCount() > 0) {
			$this->manifestParser->activateFocusOnLocalModels();
			
			do {
				$localModelName = $this->currentNamespace. '\\' . $this->manifestParser->getCurrentLocalModelName();
				if (!array_key_exists($localModelName, $this->instanceModels)) {
					$this->_addInstanceModel(new Model($localModelName));
				}
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
	private function _buildProperties(Model $parentModel = null, $modelNameux) {
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
	 * @param \Comhon\Manifest\Parser\SerializationManifestParser $serializationManifestParser
	 * @param bool $isSerializable
	 * @param \Comhon\Model\Model $parentModel
	 * @return \Comhon\Serialization\Serialization|null null if no serialization
	 */
	private function _getSerializationInstance(SerializationManifestParser $serializationManifestParser = null, $isSerializable = false, Model $parentModel = null) {
		$serializationSettings = null;
		$inheritanceKey = null;
		$inheritanceValues = null;
		$serialization = null;
		
		if (!is_null($serializationManifestParser)) {
			$inheritanceKey        = $serializationManifestParser->getInheritanceKey();
			$serializationSettings = $serializationManifestParser->getSerializationSettings();
			$inheritanceValues     = $serializationManifestParser->getInheritanceValues();
			
			if (is_array($inheritanceValues)) {
				for ($i = 0; $i < count($inheritanceValues); $i++) {
					$modelName = $inheritanceValues[$i];
					$modelName = ($modelName[0] != '\\') ? $this->currentNamespace. '\\' . $modelName : substr($modelName, 1) ;
					$inheritanceValues[$i] = $modelName;
				}
			}
		}
		if (!is_null($serializationSettings)) {
			$serialization = new Serialization(
				$this->getUniqueSerializationSettings($serializationSettings, $parentModel), 
				$inheritanceKey, 
				$isSerializable, 
				$inheritanceValues
			);
		} elseif (!is_null($parentModel) && !is_null($parentModel->getSerialization())) {
			$serialization = new Serialization(
				$parentModel->getSerializationSettings(), 
				$parentModel->getSerialization()->getInheritanceKey(), 
				$isSerializable, 
				$inheritanceValues
			);
		}
		
		return $serialization;
	}
	
	/**
	 * get serialization settings from parent model if exists and if needed
	 *
	 * if current model has same serialization settings than it parent model,
	 * we take parent model serialization settings.
	 * 
	 * by having unique instance we can compare quickly if parent and children have same serialization settings.
	 * it is basicaly used in ObjectCollection.
	 *
	 * @param \Comhon\Object\UniqueObject $serializationSettings
	 * @param \Comhon\Model\Model $parentModel
	 * @return \Comhon\Object\UniqueObject|null null if no serialization
	 */
	private function getUniqueSerializationSettings(UniqueObject $serializationSettings, Model $parentModel = null) {
		$same = false;
		while (!is_null($parentModel) && !$same) {
			if (is_null($parentModel->getSerialization())) {
				$parentModel = $parentModel->getParent();
				continue;
			}
			$parentSerializationSettings = $parentModel->getSerializationSettings();
			
			if ($serializationSettings === $parentSerializationSettings) {
				$same = true;
			}
			elseif ($serializationSettings->getModel()->getName() == $parentSerializationSettings->getModel()->getName()) {
				$same = true;
				foreach ($serializationSettings->getModel()->getProperties() as $property) {
					if ($serializationSettings->getValue($property->getName()) !== $parentSerializationSettings->getValue($property->getName())) {
						$same = false;
						break;
					}
				}
			}
			$parentModel = $parentModel->getParent();
		}
		
		return $same ? $parentSerializationSettings : $serializationSettings;
	}
	
}
