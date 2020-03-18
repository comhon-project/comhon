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
use Comhon\Object\Collection\ObjectCollection;
use Comhon\Model\ModelRoot;

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
			$this->modelRoot = new ModelRoot();
			$this->_addInstanceModel($this->modelRoot);
			
			if (Config::getInstance()->hasValue('sql_table')) {
				$path = Config::getInstance()->getSerializationSqlTablePath();
				if (!is_dir($path)) {
					throw new ConfigFileNotFoundException('sql_table', 'directory', Config::getInstance()->getSerializationSqlTablePath(false));
				}
				$this->getInstanceModel('Comhon\SqlTable')->getSerializationSettings()->setValue('dir_path', $path);
			}
			if (Config::getInstance()->hasValue('sql_database')) {
				$path = Config::getInstance()->getSerializationSqlDatabasePath();
				if (!is_dir($path)) {
					throw new ConfigFileNotFoundException('sql_database', 'directory', Config::getInstance()->getSerializationSqlDatabasePath(false));
				}
				$this->getInstanceModel('Comhon\SqlDatabase')->getSerializationSettings()->setValue('dir_path', $path);
			}
			if (!is_null(Config::getInstance()->getValue('manifest_format'))) {
				$this->manifestExtension = Config::getInstance()->getValue('manifest_format');
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
			$lOptionsManifestAutoloadList = Config::getInstance()->getOptionsAutoloadList();
			if (!is_null($lOptionsManifestAutoloadList)) {
				$comhonPath_ad = $this->autoloadOptionsManifest['Comhon'];
				$this->autoloadOptionsManifest = $lOptionsManifestAutoloadList->getValues();
				if (!isset($this->autoloadOptionsManifest['Comhon'])) {
					$this->autoloadOptionsManifest['Comhon'] = $comhonPath_ad;
				}
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
			$this->_addInstanceModel(new Model($modelName));
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
			? Config::getInstance()->getDirectory() . DIRECTORY_SEPARATOR . $this->autoloadManifest[$fullyQualifiedNamePrefix]
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
				? Config::getInstance()->getDirectory(). DIRECTORY_SEPARATOR . $this->autoloadSerializationManifest[$fullyQualifiedNamePrefix]
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
				? Config::getInstance()->getDirectory(). DIRECTORY_SEPARATOR . $this->autoloadOptionsManifest[$fullyQualifiedNamePrefix]
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
	 */
	public function addManifestParser(Model $model) {
		$fullyQualifiedName = $model->getName();
		if (strpos($fullyQualifiedName, '\\\\') !== false) {
			throw new ComhonException('invalid model name, it cannot contain several followed baskslash (\\\\)');
		}
		list($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix) = $this->splitModelName($fullyQualifiedName);
		$separatorOffset = PHP_INT_MAX;
		$manifestPath_afe = null;
		
		while (is_null($manifestPath_afe) && $separatorOffset !== false) {
			$tempManifestPath_afe = $this->getManifestPath($fullyQualifiedNamePrefix, $fullyQualifiedNameSuffix);
			if (file_exists($tempManifestPath_afe)) {
				$manifestPath_afe = $tempManifestPath_afe;
			}
			if (is_null($manifestPath_afe) && ($separatorOffset = strrpos($fullyQualifiedNameSuffix, '\\')) !== false) {
				$fullyQualifiedNameSuffix = substr($fullyQualifiedNameSuffix, 0, $separatorOffset);
				$fullyQualifiedName = $fullyQualifiedNamePrefix . '\\' . $fullyQualifiedNameSuffix;
			}
		}
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
		$this->_instanciateLocalModels($localTypeManifestParsers);
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
				self::CONFLICTS => $manifestParser->getconflicts(),
			];
			$properties[self::SERIALIZATION] = $this->_getSerializationInstance(
				$manifestParser, 
				$manifestParser->getSerializationManifestParser(), 
				$manifestParser->isSerializable(), 
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
	 */
	private function _instanciateLocalModels($localTypeManifestParsers) {
		foreach ($localTypeManifestParsers as $modelName => $localManifestParser) {
			$model = $this->_getInstanceModel($modelName, false);
			if (!$model->isLoaded()) {
				if ($model->hasManifestParser()) {
					throw new AlreadyUsedModelNameException($model->getName());
				}
				$model->setManifestParser($localManifestParser);
			}
		}
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
				$modelName = $manifestParser->getCurrentPropertyModelName();
				if (!array_key_exists($modelName, $this->instanceSimpleModels)) {
					$modelName = ($modelName[0] != '\\') 
						? $manifestParser->getNamespace(). '\\' . $modelName 
						: substr($modelName, 1) ;
				}
				
				$propertyModel = $this->_getInstanceModel($modelName, false);
				$property      = $manifestParser->getCurrentProperty($propertyModel);
				
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
	 * @param bool $isSerializable
	 * @param \Comhon\Model\Model[] $parentModels
	 * @return \Comhon\Serialization\Serialization|null null if no serialization
	 */
	private function _getSerializationInstance(ManifestParser $manifestParser, SerializationManifestParser $serializationManifestParser = null, $isSerializable = false, array $parentModels = []) {
		$serializationSettings = null;
		$serializationUnitClass = null;
		$inheritanceKey = null;
		$inheritanceValues = null;
		$serialization = null;
		$parentModel = isset($parentModels[0]) ? $parentModels[0] : null;
		
		if (!is_null($serializationManifestParser)) {
			$inheritanceKey         = $serializationManifestParser->getInheritanceKey();
			$serializationSettings  = $serializationManifestParser->getSerializationSettings();
			$serializationUnitClass = $serializationManifestParser->getSerializationUnitClass();
			$inheritanceValues      = $serializationManifestParser->getInheritanceValues();
		}
		if (!is_null($serializationSettings)) {
			$serialization = Serialization::getInstanceWithSettings(
				$this->getUniqueSerializationSettings($serializationSettings, $parentModel), 
				$inheritanceKey, 
				$isSerializable, 
				$inheritanceValues
			);
		} elseif (!is_null($serializationUnitClass)) {
			$serialization = Serialization::getInstanceWithUnitClass(
				$serializationUnitClass,
				$inheritanceKey,
				$isSerializable,
				$inheritanceValues
			);
		} elseif (!is_null($parentModel) && !is_null($parentModel->getSerialization())) {
			if (!is_null($parentModel->getSerializationSettings())) {
				$serialization = Serialization::getInstanceWithSettings(
					$parentModel->getSerialization()->getSettings(),
					$parentModel->getSerialization()->getInheritanceKey(), 
					$isSerializable,
					$inheritanceValues
				);
			} elseif (!is_null($serializationUnitClass)) {
				$serialization = Serialization::getInstanceWithUnitClass(
					$parentModel->getSerialization()->getSerializationUnitClass(),
					$parentModel->getSerialization()->getInheritanceKey(), 
					$isSerializable,
					$inheritanceValues
				);
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
			while (!is_null($tempParent) && (is_null($tempParent->getSerialization()) || $tempParent->getSerialization()->getSettings() !== $serialization->getSettings())) {
				$tempParent = $tempParent->getParent();
			}
			if (!is_null($tempParent)) {
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
	
}
