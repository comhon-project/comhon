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
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Serialization\Serialization;
use Comhon\Manifest\Parser\SerializationManifestParser;
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Model\ModelRoot;
use Comhon\Exception\Config\ConfigMalformedException;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Cache\CacheHandler;

class ModelManager {

	/** @var string */
	const PROPERTIES      = 'properties';
	
	/** @var string */
	const OBJECT_CLASS    = 'objectClass';
	
	/** @var string */
	const SERIALIZATION   = 'serialization';
	
	/** @var string */
	const PARENT_MODELS   = 'parentModels';
	
	/** @var string */
	const IS_MAIN_MODEL   = 'isMainModel';
	
	/** @var string */
	const IS_ABSTRACT = 'is_abstract';
	
	/** @var string */
	const SHARED_ID_MODEL = 'shared_id_model';
	
	/** @var string */
	const CONFLICTS = 'conflicts';
	
	/**
	 * @var string
	 */
	private $config_ad;
	
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
	 * @var string
	 */
	private $originalModelName;
	
	/**
	 * @var string
	 */
	private $manifestExtension = 'json';
	
	/**
	 * @var \Comhon\Model\ModelRoot
	 */
	private $modelRoot;
	
	/**
	 * @var \Comhon\Cache\CacheHandler
	 */
	private $cacheHandler;
	
	/**
	 * @var boolean
	 */
	private $isCachingContext;
	
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
	 * @var string[] map namespace prefix to directory to allow options manifest autoloading
	 */
	private $autoloadOptionsManifest = [
		'Comhon' => __DIR__ . DIRECTORY_SEPARATOR
			. '..' . DIRECTORY_SEPARATOR
			. '..' . DIRECTORY_SEPARATOR
			. 'Manifest' . DIRECTORY_SEPARATOR
			. 'Collection' . DIRECTORY_SEPARATOR
			. 'Options'
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
			$config_af = realpath(Config::getLoadPath());
			$configArray = $this->_getConfigArray($config_af);
			$this->config_ad = dirname($config_af);
			if (isset($configArray['cache_settings'])) {
				$this->cacheHandler = CacheHandler::getInstance($configArray['cache_settings'], $this->config_ad);
			}
			
			// must be done before SqlTable and SqlDatabase model instanciation
			if (Config::hasInstance()) {
				Config::getInstance()->getModel()->register();
				$this->modelRoot = $this->getInstanceModel('Comhon\Root');
				$this->_setBaseConfigFromObject(Config::getInstance());
			} elseif ($this->cacheHandler) {
				$config = $this->cacheHandler->loadConfig();
				if (!is_null($config)) {
					$this->modelRoot = $this->getInstanceModel('Comhon\Root');
					$this->_setBaseConfigFromObject($config);
				} else {
					$this->modelRoot = new ModelRoot();
					$this->_setBaseConfigFromArray($configArray);
					$config = Config::initInstance($configArray, $this->config_ad);
					$this->cacheHandler->registerConfig($config);
				}
			} else {
				$this->modelRoot = new ModelRoot();
				$this->_setBaseConfigFromArray($configArray);
				Config::initInstance($configArray, $this->config_ad);
			}
			
			// load sqlTable and sqlDatabase and update serialization if needed
			if (isset($configArray['sql_table'])) {
				$this->_modifySqlFileSerialization('Comhon\SqlTable', $configArray['sql_table'], 'sql_table');
			}
			if (isset($configArray['sql_database'])) {
				$this->_modifySqlFileSerialization('Comhon\SqlDatabase', $configArray['sql_database'], 'sql_database');
			}
		} catch (\Exception $e) {
			self::$_instance = null;
			throw $e;
		}
	}
	
	/**
	 * 
	 * @param string $modelName
	 * @param string $newSerializationPath
	 * @param string $type
	 * @throws ConfigFileNotFoundException
	 */
	private function _modifySqlFileSerialization($modelName, $newSerializationPath, $type) {
		$useCache = !is_null($this->cacheHandler) && !$this->hasInstanceModel($modelName);
		
		if (!$useCache || is_null($this->loadModelFromCache($modelName))) {
			$path = $this->_toAbsolutePath($newSerializationPath, $this->config_ad);
			if (!is_dir($path)) {
				throw new ConfigFileNotFoundException($type, 'directory', $newSerializationPath);
			}
			$this->getInstanceModel($modelName)->getSerializationSettings()->setValue('dir_path', $path);
			if ($useCache) {
				// need to register manually with new path values
				$this->registerModelIntoCache($this->getInstanceModel($modelName));
			}
		}
	}
	
	/**
	 * get cache handler according "cache_setting" in config file
	 * 
	 * @return \Comhon\Cache\CacheHandler|null return null if there is no "cache_setting" in config file
	 */
	public function getCacheHandler() {
		return $this->cacheHandler;
	}
	
	/**
	 * verify if a model is currently serializing or unserializing
	 *
	 * @return boolean
	 */
	public function isCachingContext() {
		return $this->isCachingContext;
	}
	
	/**
	 * load config into associative array and return it.
	 * 
	 * @param string $config_af
	 * @throws ConfigFileNotFoundException
	 * @throws ConfigMalformedException
	 * @return array
	 */
	private function _getConfigArray($config_af) {
		if ($config_af === false) {
			throw new ConfigFileNotFoundException('configuration', 'file', $config_af);
		}
		$interfacer = new AssocArrayInterfacer();
		$arrayConfig = $interfacer->read($config_af);
		if (is_null($arrayConfig)) {
			throw new ConfigMalformedException($config_af);
		}
		return $arrayConfig;
	}
	
	/**
	 * transform given path to absolute path if given path begin with a dot (examples: ./my_path or ../my_path).
	 *
	 * @param string $path
	 * @param string $config_ad
	 * @return string
	 */
	private function _toAbsolutePath($path, $config_ad) {
		return substr($path, 0, 1) == '.' ? $config_ad . DIRECTORY_SEPARATOR . $path : $path;
	}
	
	/**
	 * 
	 * @param array $configArray
	 */
	private function _setBaseConfigFromArray(array $configArray) {
		if (isset($configArray['manifest_format'])) {
			$this->manifestExtension = $configArray['manifest_format'];
		}
		if (isset($configArray['autoload']['manifest'])) {
			$comhonPath_ad = $this->autoloadManifest['Comhon'];
			$this->autoloadManifest = $configArray['autoload']['manifest'];
			$this->autoloadManifest['Comhon'] = $comhonPath_ad;
		}
		if (isset($configArray['autoload']['serialization'])) {
			$comhonPath_ad = $this->autoloadSerializationManifest['Comhon'];
			$this->autoloadSerializationManifest = $configArray['autoload']['serialization'];
			$this->autoloadSerializationManifest['Comhon'] = $comhonPath_ad;
		}
		if (isset($configArray['autoload']['options'])) {
			$comhonPath_ad = $this->autoloadOptionsManifest['Comhon'];
			$this->autoloadOptionsManifest = $configArray['autoload']['options'];
			if (!isset($this->autoloadOptionsManifest['Comhon'])) {
				$this->autoloadOptionsManifest['Comhon'] = $comhonPath_ad;
			}
		}
	}
	
	/**
	 * 
	 * @param \Comhon\Object\Config\Config $config
	 */
	private function _setBaseConfigFromObject(Config $config) {
		if (!is_null(Config::getInstance()->getValue('manifest_format'))) {
			$this->manifestExtension = Config::getInstance()->getValue('manifest_format');
		}
		$lManifestAutoloadList = $config->getManifestAutoloadList();
		if (!is_null($lManifestAutoloadList)) {
			$comhonPath_ad = $this->autoloadManifest['Comhon'];
			$this->autoloadManifest = $lManifestAutoloadList->getValues();
			$this->autoloadManifest['Comhon'] = $comhonPath_ad;
		}
		$lSerializationManifestAutoloadList = $config->getSerializationAutoloadList();
		if (!is_null($lSerializationManifestAutoloadList)) {
			$comhonPath_ad = $this->autoloadSerializationManifest['Comhon'];
			$this->autoloadSerializationManifest = $lSerializationManifestAutoloadList->getValues();
			$this->autoloadSerializationManifest['Comhon'] = $comhonPath_ad;
		}
		$lOptionsManifestAutoloadList = $config->getOptionsAutoloadList();
		if (!is_null($lOptionsManifestAutoloadList)) {
			$comhonPath_ad = $this->autoloadOptionsManifest['Comhon'];
			$this->autoloadOptionsManifest = $lOptionsManifestAutoloadList->getValues();
			if (!isset($this->autoloadOptionsManifest['Comhon'])) {
				$this->autoloadOptionsManifest['Comhon'] = $comhonPath_ad;
			}
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
	 * get local types defined in manifest of given model name
	 *
	 * @return string[]
	 */
	public function getLocalTypes($modelName) {
		return $this->getInstanceModel($modelName)->getLocalTypes();
	}
	
	/**
	 * get model instance
	 *
	 * @param string $modelName fully qualified name of wanted model
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	public function getInstanceModel($modelName) {
		return $this->_getInstanceModel($modelName, true);
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
		if (!is_string($modelName)) {
			throw new \InvalidArgumentException('first argument must be a string');
		}
		if (!array_key_exists($modelName, $this->instanceModels)) {
			if (
				$loadModel 
				&& !is_null($this->cacheHandler) 
				&& $this->cacheHandler->hasValue($this->cacheHandler->getModelKey($modelName))
			) {
				$this->loadModelFromCache($modelName);
			} else {
				new Model($modelName);
			}
			// instance model must be added during model instanciation (in constructor)
			if (!array_key_exists($modelName, $this->instanceModels)) {
				throw new ComhonException('model not added during model instanciation');
			}
		}
		if ($loadModel) {
			$this->instanceModels[$modelName]->load();
		}
		return $this->instanceModels[$modelName];
	}
	
	/**
	 * get manifest path
	 * 
	 * @param string $fullyQualifiedNamePrefix
	 * @param string $fullyQualifiedNameSuffix
	 * @return string
	 */
	public function getManifestPath($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix) {
		if (!array_key_exists($fullyQualifiedNamePrefix, $this->autoloadManifest)) {
			throw new ComhonException("prefix namespace '$fullyQualifiedNamePrefix' do not belong to autoload manifest list");
		}
		$prefix_ad = substr($this->autoloadManifest[$fullyQualifiedNamePrefix], 0, 1) == '.'
			? $this->config_ad . DIRECTORY_SEPARATOR . $this->autoloadManifest[$fullyQualifiedNamePrefix]
			: $this->autoloadManifest[$fullyQualifiedNamePrefix];
		return $prefix_ad . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $fullyQualifiedNameSuffix) . DIRECTORY_SEPARATOR .'manifest.' . $this->manifestExtension;
	}
	
	/**
	 * get serialization manifest path
	 *
	 * @param string $manifest_af
	 * @param string $fullyQualifiedNamePrefix
	 * @param string $fullyQualifiedNameSuffix
	 * @return string|null
	 */
	public function getSerializationManifestPath($manifest_af, $fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix) {
		if (array_key_exists($fullyQualifiedNamePrefix, $this->autoloadSerializationManifest)) {
			$prefix_ad = substr($this->autoloadSerializationManifest[$fullyQualifiedNamePrefix], 0, 1) == '.'
				? $this->config_ad . DIRECTORY_SEPARATOR . $this->autoloadSerializationManifest[$fullyQualifiedNamePrefix]
				: $this->autoloadSerializationManifest[$fullyQualifiedNamePrefix];
			$manifest_af = $prefix_ad . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $fullyQualifiedNameSuffix) . DIRECTORY_SEPARATOR .'serialization.' . $this->manifestExtension;
		} else {
			$manifest_af = dirname($manifest_af) . DIRECTORY_SEPARATOR .'serialization.' . $this->manifestExtension;
		}
		return $manifest_af;
	}
	
	/**
	 * get options manifest path
	 *
	 * @param string $manifest_af
	 * @param string $fullyQualifiedNamePrefix
	 * @param string $fullyQualifiedNameSuffix
	 * @return string|null
	 */
	public function getOptionsManifestPath($manifest_af, $fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix) {
		if (array_key_exists($fullyQualifiedNamePrefix, $this->autoloadOptionsManifest)) {
			$prefix_ad = substr($this->autoloadOptionsManifest[$fullyQualifiedNamePrefix], 0, 1) == '.'
				? $this->config_ad . DIRECTORY_SEPARATOR . $this->autoloadOptionsManifest[$fullyQualifiedNamePrefix]
				: $this->autoloadOptionsManifest[$fullyQualifiedNamePrefix];
			$manifest_af = $prefix_ad . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $fullyQualifiedNameSuffix) . DIRECTORY_SEPARATOR .'options.' . $this->manifestExtension;
		} else {
			$manifest_af = dirname($manifest_af) . DIRECTORY_SEPARATOR .'options.' . $this->manifestExtension;
		}
		return $manifest_af;
	}
	
	/**
	 * add manifest parser to specified model
	 * 
	 * @param \Comhon\Model\Model $model
	 * @return \Comhon\Model\Model[] models from local types
	 */
	public function addManifestParser(Model $model) {
		$fullyQualifiedName = $model->getName();
		if ($fullyQualifiedName[0] == '\\') {
			throw new ComhonException('invalid model name, it cannot begin by baskslash (\)');
		}
		if (strpos($fullyQualifiedName, '\\\\') !== false) {
			throw new ComhonException('invalid model name, it cannot contain several followed baskslash (\\\\)');
		}
		list (
			$manifestPath_afe,
			$fullyQualifiedNamePrefix,
			$fullyQualifiedNameSuffix,
			$fullyQualifiedName
		) = $this->searchManifestPath($fullyQualifiedName);
		
		if (is_null($manifestPath_afe)) {
			throw new NotDefinedModelException($model->getName());
		}
		$serializationPath_afe  = $this->getSerializationManifestPath(
			$manifestPath_afe, 
			$fullyQualifiedNamePrefix, 
			$fullyQualifiedNameSuffix
		);
		
		/**
		 * the model that come from manifest (not necessarily same than $model because $model might be a model from local type)
		 * @var Model $mainModel
		 */
		$mainModel = $this->_getInstanceModel($fullyQualifiedName, false);
		$manifestParser = ManifestParser::getInstance($manifestPath_afe, $serializationPath_afe, $mainModel->getName());
		$localTypeManifestParsers = $manifestParser->getLocalModelManifestParsers();
		
		if ($mainModel !== $model && !array_key_exists($model->getName(), $localTypeManifestParsers)) {
			throw new NotDefinedModelException($model->getName());
		}
		$mainModel->setManifestParser($manifestParser);
		$mainModel->setLocalTypes(array_keys($localTypeManifestParsers));
		return $this->_instanciateLocalModels($localTypeManifestParsers);
	}
	
	/**
	 * 
	 * @param string $modelName
	 * @throws ComhonException
	 * @return string[]
	 */
	public function splitModelName($modelName) {
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
	 * get the manifest path according given model name.
	 * 
	 * if manifest file corresponding to given model name is not found,
	 * a "parent" manifest path may be returned because this file may contain 
	 * a local type that correspond to wanted model.
	 * example :
	 * - the searched model is Comhon\Manifest\Local
	 * - the returned path point to manifest that describe model Comhon\Manifest
	 * - actually Comhon\Manifest\Local is a local type defined in this manifest
	 * 
	 * Warning! a "parent" manifest path may be returned even if 
	 * there is no local type that correspond to given model name.
	 * Actually there is no verification, the file is not opened.
	 * 
	 * @param string $modelName
	 * @return string[] an array that contain manifest informations
	 *                  - at index 0 : the manifest path (null if nothing found)
	 *                  - at index 1 : the fully Qualified Name Prefix of model corresponding to manifest
	 *                  - at index 2 : the fully Qualified Name Suffix of model corresponding to manifest
	 *                  - at index 3 : the fully Qualified Name of model corresponding to manifest
	 */
	public function searchManifestPath($modelName) {
		try {
			list($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix) = $this->splitModelName($modelName);
		} catch (NotDefinedModelException $e) {
			return [null, null, null, null];
		}
		$separatorOffset = PHP_INT_MAX;
		$manifestPath_afe = null;
		
		while (is_null($manifestPath_afe) && $separatorOffset !== false) {
			$tempManifestPath_afe = $this->getManifestPath($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix);
			if (file_exists($tempManifestPath_afe)) {
				$manifestPath_afe = $tempManifestPath_afe;
			}
			if (is_null($manifestPath_afe) && ($separatorOffset = strrpos($fullyQualifiedNameSuffix, '\\')) !== false) {
				$fullyQualifiedNameSuffix = substr($fullyQualifiedNameSuffix, 0, $separatorOffset);
				$modelName = $fullyQualifiedNamePrefix . '\\' . $fullyQualifiedNameSuffix;
			}
		}
		return [$manifestPath_afe, $fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix, $modelName];
	}
	
	/**
	 * add instance model.
	 * automatically called during \Comhon\Model\Model instanciation
	 * 
	 * @param \Comhon\Model\Model $model
	 * @return array
	 */
	public function addInstanceModel(Model $model) {
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
	 *     self::IS_MAIN_MODEL => bool
	 *     self::PROPERTIES    => \Comhon\Model\Property\Property[]
	 *     self::PARENT_MODELS => \Comhon\Model\Model[]
	 *     self::OBJECT_CLASS  => string|null
	 *     self::SERIALIZATION => \Comhon\Serialization\SerializationUnit|null
	 * ]
	 */
	public function getProperties(Model $model, ManifestParser $manifestParser) {
		$properties = null;
		$isOriginalModel = false;
		
		try {
			if (isset($this->instanceModels[$model->getName()]) && $this->instanceModels[$model->getName()]->isLoaded()) {
				throw new ComhonException("function should not be called, model {$model->getName()} already loaded");
			}
			if (is_null($this->originalModelName)) {
				$this->originalModelName = $model->getName();
				$isOriginalModel = true;
			}
			$parentModels = $this->_getParentModels($model, $manifestParser);
			
			$properties = [
				self::OBJECT_CLASS => $manifestParser->getObjectClass(),
				self::IS_ABSTRACT => $manifestParser->isAbstract(),
				self::PROPERTIES => $this->_buildProperties($parentModels, $model, $manifestParser),
				self::CONFLICTS => $manifestParser->getConflicts(),
			];
			$properties[self::SERIALIZATION] = $this->_getSerializationInstance(
				$manifestParser, 
				$manifestParser->getSerializationManifestParser(), 
				$parentModels
			);
			$properties[self::SHARED_ID_MODEL] = $this->_getSharedIdModel($model, $manifestParser, $properties[self::SERIALIZATION], $parentModels); 
			$properties[self::IS_MAIN_MODEL] = $properties[self::SERIALIZATION] ? true : $manifestParser->isMain();
			if (empty($parentModels)) {
				$parentModels[] = $this->modelRoot;
			}
			$properties[self::PARENT_MODELS] = $parentModels;
			
			if ($isOriginalModel) {
				$this->originalModelName = null;
			}
		} catch (\Exception $e) {
			$this->originalModelName = null;
			throw $e;
		}
		return $properties;
	}
	
	/**
	 * instanciate models according given local manifest parsers
	 * 
	 * @param array $localTypeManifestParsers
	 * @return \Comhon\Model\Model[] models from local types
	 */
	private function _instanciateLocalModels($localTypeManifestParsers) {
		$models = [];
		foreach ($localTypeManifestParsers as $modelName => $localManifestParser) {
			$model = $this->_getInstanceModel($modelName, false);
			if (!$model->isLoaded()) {
				if ($model->hasManifestParser()) {
					throw new AlreadyUsedModelNameException($model->getName());
				}
				$model->setManifestParser($localManifestParser);
			}
			$models[] = $model;
		}
		return $models;
	}
	
	/**
	 * get parent models if exist
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param \Comhon\Manifest\Parser\ManifestParser $manifestParser
	 * @throws \Exception
	 * @return \Comhon\Model\Model[]
	 */
	private function _getParentModels(Model $model, ManifestParser $manifestParser) {
		$parentModels = [];
		$modelNames = $manifestParser->getExtends();
		
		if (!is_null($modelNames)) {
			foreach ($modelNames as $modelName) {
				if (array_key_exists($modelName, $this->instanceSimpleModels)) {
					throw new ComhonException("{$model->getName()} cannot extends from {$modelName}");
				}
				$modelName = $modelName[0] == '\\' ? substr($modelName, 1) : $manifestParser->getNamespace(). '\\' . $modelName;
				
				if ($this->hasInstanceModel($modelName) && $this->_getInstanceModel($modelName, false)->isLoading()) {
					throw new ComhonException("loop detected in model inheritance : {$model->getName()} and {$this->originalModelName}");
				}
				$parentModels[] = $this->getInstanceModel($modelName);
			}
		}
		
		return $parentModels;
	}
	
	/**
	 * build model properties
	 * 
	 * @param \Comhon\Model\Model[] $parentModels
	 * @param \Comhon\Model\Model $currentModel
	 * @param \Comhon\Manifest\Parser\ManifestParser $manifestParser
	 * @throws \Exception
	 * @return \Comhon\Model\Property\Property[]
	 */
	private function _buildProperties(array $parentModels, Model $currentModel, ManifestParser $manifestParser) {
		/** @var \Comhon\Model\Property\Property[] $properties */
		$properties = [];
		foreach ($parentModels as $parentModel) {
			foreach ($parentModel->getProperties() as $propertyName => $property) {
				if (array_key_exists($propertyName, $properties) && !$properties[$propertyName]->isEqual($property)) {
					throw new ComhonException(
						"Multiple inheritance conflict on property \"$propertyName\" ".
						"on model \"{$currentModel->getName()}\""
					);
				}
				$properties[$propertyName] = $property;
			}
		}
		if ($manifestParser->getCurrentPropertiesCount() > 0) {
			do {
				$modelName = $manifestParser->getCurrentPropertyModelUniqueName();
				if (!array_key_exists($modelName, $this->instanceSimpleModels)) {
					$modelName = ($modelName[0] != '\\') 
						? $manifestParser->getNamespace(). '\\' . $modelName 
						: substr($modelName, 1) ;
				}
				
				$propertyModelUnique = $this->_getInstanceModel($modelName, false);
				$property = $manifestParser->getCurrentProperty($propertyModelUnique);
				
				if (array_key_exists($property->getName(), $properties) && !$properties[$property->getName()]->isEqual($property)) {
					throw new ComhonException(
							"Inheritance conflict on property \"$propertyName\" ".
							"on model \"{$currentModel->getName()}\""
					);
				}
				
				$properties[$property->getName()] = $property;
			} while ($manifestParser->nextProperty());
		}
	
		return $properties;
	}
	
	/**
	 * get serialization if exists
	 * 
	 * @param \Comhon\Manifest\Parser\ManifestParser $manifestParser
	 * @param \Comhon\Manifest\Parser\SerializationManifestParser $serializationManifestParser
	 * @param \Comhon\Model\Model[] $parentModels
	 * @return \Comhon\Serialization\Serialization|null null if no serialization
	 */
	private function _getSerializationInstance(ManifestParser $manifestParser, SerializationManifestParser $serializationManifestParser = null, array $parentModels = []) {
		$serializationSettings = null;
		$serializationUnitClass = null;
		$inheritanceKey = null;
		$inheritanceValues = null;
		$serialization = null;
		$shareParentSerialization = true;
		$parentModel = isset($parentModels[0]) ? $parentModels[0] : null;
		
		if (!is_null($serializationManifestParser)) {
			$inheritanceKey = $serializationManifestParser->getInheritanceKey();
			$serializationSettings = $serializationManifestParser->getSerializationSettings();
			$serializationUnitClass = $serializationManifestParser->getSerializationUnitClass();
			$inheritanceValues = $serializationManifestParser->getInheritanceValues();
			$shareParentSerialization = $serializationManifestParser->shareParentSerialization();
		}
		if (!is_null($serializationSettings)) {
			$serialization = Serialization::getInstanceWithSettings(
				$this->_getUniqueSerializationSettings($serializationSettings, $parentModel), 
				$inheritanceKey, 
				$inheritanceValues
			);
		} elseif (!is_null($serializationUnitClass)) {
			$serialization = Serialization::getInstanceWithUnitClass(
				$serializationUnitClass,
				$inheritanceKey,
				$inheritanceValues
			);
		} elseif ($shareParentSerialization) {
			while (!is_null($parentModel) && !$parentModel->hasSerialization()) {
				$parentModel = $parentModel->getParent();
			}
			if (!is_null($parentModel)) {
				if (!is_null($parentModel->getSerializationSettings())) {
					$serialization = Serialization::getInstanceWithSettings(
						$parentModel->getSerialization()->getSettings(),
						$parentModel->getSerialization()->getInheritanceKey(), 
						$inheritanceValues
					);
				} elseif (!is_null($serializationUnitClass)) {
					$serialization = Serialization::getInstanceWithUnitClass(
						$parentModel->getSerialization()->getSerializationUnitClass(),
						$parentModel->getSerialization()->getInheritanceKey(), 
						$inheritanceValues
					);
				}
			}
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
	private function _getUniqueSerializationSettings(UniqueObject $serializationSettings, Model $parentModel = null) {
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
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param \Comhon\Manifest\Parser\ManifestParser $manifestParser
	 * @param \Comhon\Serialization\Serialization $serialization
	 * @param \Comhon\Model\Model[] $parentModels
	 * @throws ComhonException
	 */
	private function _getSharedIdModel(Model $model, ManifestParser $manifestParser, Serialization $serialization = null, $parentModels = null) {
		$parentModel = isset($parentModels[0]) ? $parentModels[0] : null;
		$sharedIdType = $manifestParser->sharedId();
		$shareParentId = $manifestParser->isSharedParentId();
		$sharedIdModel = null;
		$sharedIdModelTemp = null;
		
		if (is_null($parentModel)) {
			if ($shareParentId) {
				throw new ComhonException("Invalid manifest that define model '{$model->getName()}' : '"
					.ManifestParser::SHARE_PARENT_ID."' is set to true but there is no defined extends."
				);
			}
			if (!is_null($sharedIdType)) {
				throw new ComhonException("Invalid manifest that define model '{$model->getName()}' : '"
					.ManifestParser::SHARED_ID."' is set but there is no defined extends."
				);
			}
		}
		
		if (!is_null($serialization)) {
			$tempParent = $parentModel;
			while (!is_null($tempParent) && (!$tempParent->hasSerialization() || $tempParent->getSerialization()->getSettings() !== $serialization->getSettings())) {
				$tempParent = $tempParent->getParent();
			}
			if (!is_null($tempParent)) {
				if ($serialization->getInheritanceKey() !== $tempParent->getSerialization()->getInheritanceKey()) {
					throw new ComhonException(
						"conflict on inheritance keys '{$serialization->getInheritanceKey()}' and '{$tempParent->getSerialization()->getInheritanceKey()}' "
						."on models {$model->getName()} and {$tempParent->getName()}. "
						.'inherited model with same serialization than parent model must have same inheritance key than parent model'
					);
				}
				$sharedIdModel = ObjectCollection::getModelKey($tempParent);
			}
		}
		
		if ($shareParentId && !is_null($sharedIdType)) {
			throw new ComhonException("Conflict in manifest that define model '{$model->getName()}' : '"
				.ManifestParser::SHARED_ID."' and ".ManifestParser::SHARE_PARENT_ID." cannot be defined together."
			);
		}
		if (!is_null($parentModel) && $shareParentId) {
			$sharedIdModelTemp = ObjectCollection::getModelKey($parentModel);
		}
		if (!is_null($sharedIdType)) {
			$modelName = $sharedIdType[0] == '\\' ? substr($sharedIdType, 1) : $manifestParser->getNamespace(). '\\' . $sharedIdType;
			
			$sharedIdModelTemp = $this->_getInstanceModel($modelName, true);
			// cannot call isInheritedFrom() on $model because parent model is not currently set
			if (is_null($parentModel) || ($parentModel !== $sharedIdModelTemp && !$parentModel->isInheritedFrom($sharedIdModelTemp))) {
				throw new ComhonException("Invalid shared id type in manifest that define '{$model->getName()}'. shared id type must be a parent model.");
			}
			$sharedIdModelTemp = ObjectCollection::getModelKey($sharedIdModelTemp);
		}
		if (!is_null($sharedIdModelTemp)) {
			if (!is_null($sharedIdModel) && $sharedIdModelTemp !== $sharedIdModel) {
				throw new ComhonException("Conflict on model '{$model->getName()}' between shared id and serialization");
			}
			$sharedIdModel = $sharedIdModelTemp;
		}
		return $sharedIdModel;
	}
	
	/**
	 * get model instance (model may be loaded or not).
	 * this function is only callable in caching context
	 *
	 * @return \Comhon\Model\Model|\Comhon\Model\SimpleModel
	 */
	public function getNotLoadedInstanceModel($modelName) {
		if (!$this->isCachingContext) {
			throw new ComhonException('wrong context, caching model is not launched, can\'t call getNotLoadedInstanceModel');
		}
		return $this->_getInstanceModel($modelName, false);
	}
	
	/**
	 * load model from cache if exists
	 *
	 * @return \Comhon\Model\Model|null return model or null if model is not cached
	 */
	public function loadModelFromCache($modelName) {
		if (is_null($this->cacheHandler)) {
			throw new ComhonException("can't call loadModelFromCache, there is no cache handler set");
		}
		if ($this->hasInstanceModelLoaded($modelName)) {
			throw new ComhonException("can't call loadModelFromCache, model '$modelName' is already loaded");
		}
		if (!$this->cacheHandler->hasValue($this->cacheHandler->getModelKey($modelName))) {
			return null;
		}
		$isAlreadyCachingContext = $this->isCachingContext;
		$this->isCachingContext = true;
		try {
			/** @var \Comhon\Model\Model $model */
			if ($this->hasInstanceModel($modelName)) {
				$modelTemp = unserialize($this->cacheHandler->getValue($this->cacheHandler->getModelKey($modelName)));
				$model = $this->_getInstanceModel($modelName, false);
				$model->overwrite($modelTemp);
			} else {
				$model = unserialize($this->cacheHandler->getValue($this->cacheHandler->getModelKey($modelName)));
				$this->addInstanceModel($model);
			}
			$model->restore();
		} finally {
			if (!$isAlreadyCachingContext) {
				$this->isCachingContext = false;
			}
		}
		
		return $model;
	}
	
	/**
	 * register model into cache
	 *
	 * @param \Comhon\Model\Model $model
	 */
	public function registerModelIntoCache(Model $model) {
		if (is_null($this->cacheHandler)) {
			throw new ComhonException("can't call registerModelIntoCache, there is no cache handler set");
		}
		if (!$model->isLoaded()) {
			throw new ComhonException("can't call registerModelIntoCache, model '{$model->getName()}' is not loaded");
		}
		$isAlreadyCachingContext = $this->isCachingContext;
		$this->isCachingContext = true;
		try {
			if (!$model->isLoaded()) {
				throw new ComhonException("cannot cache unloaded model, '{$model->getName()}' is not loaded");
			}
			
			$this->cacheHandler->registerValue(
				$this->cacheHandler->getModelKey($model->getName()),
				$model->serialize()
			);
		} finally {
			if (!$isAlreadyCachingContext) {
				$this->isCachingContext = false;
			}
		}
	}
	
}
