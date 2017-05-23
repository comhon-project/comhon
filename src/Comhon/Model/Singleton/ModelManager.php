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
	
	private $mInstanceModels;
	private $mCurrentXmlSerialization;
	private $mLocalTypes = [];
	private $mManifestParser;
	private $mSerializationManifestParser;
	
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
			$this->mInstanceModels
		);
		
		ManifestParser::registerComplexModels(
			Config::getInstance()->getManifestListPath(),
			Config::getInstance()->getSerializationListPath(),
			$this->mInstanceModels
		);
	}	
	
	private function _registerSimpleModelClasses() {
		$this->mInstanceModels = [
			ModelInteger::ID  => new ModelInteger(),
			ModelFloat::ID    => new ModelFloat(),
			ModelBoolean::ID  => new ModelBoolean(),
			ModelString::ID   => new ModelString(),
			ModelDateTime::ID => new ModelDateTime()
		];
	}
	
	
	public function hasModel($pModelName, $pMainModelName = null) {
		if (is_null($pMainModelName)) {
			return array_key_exists($pModelName, $this->mInstanceModels);
		} else {
			return array_key_exists($pMainModelName, $this->mLocalTypes) && array_key_exists($pModelName, $this->mLocalTypes[$pMainModelName]);
		}
	}
	
	public function hasInstanceModel($pModelName, $pMainModelName = null) {
		if (!$this->hasModel($pModelName, $pMainModelName)) {
			throw new \Exception("model $pModelName doesn't exists");
		}
		if (is_null($pMainModelName)) {
			$lInstanceModels =& $this->mInstanceModels;
		} else {
			$lInstanceModels =& $this->mLocalTypes[$pMainModelName];
		}
		return is_object($lInstanceModels[$pModelName]) || array_key_exists(2, $lInstanceModels[$pModelName]);
	}
	
	public function isModelLoaded($pModelName, $pMainModelName = null) {
		if (!$this->hasModel($pModelName, $pMainModelName)) {
			throw new \Exception("model $pModelName doesn't exists");
		}
		if (is_null($pMainModelName)) {
			$lInstanceModels =& $this->mInstanceModels;
		} else {
			$lInstanceModels =& $this->mLocalTypes[$pMainModelName];
		}
		if (is_object($lInstanceModels[$pModelName])) {
			if (!$lInstanceModels[$pModelName]->isLoaded()) {
				throw new \Exception("$pModelName must be loaded");
			}
			return true;
		}
		if (array_key_exists(2, $lInstanceModels[$pModelName])) {
			if ($lInstanceModels[$pModelName][2]->isLoaded()) {
				throw new \Exception("$pModelName must be not loaded");
			}
			return false;
		}
		return false;
	}
	
	/**
	 * get model instance (specify main model name if you request a local model)
	 * @param string $pModelName
	 * @param string $pMainModelName
	 * @return Model
	 */
	public function getInstanceModel($pModelName, $pMainModelName = null) {
		$lReturn = $this->_getInstanceModel($pModelName, $pMainModelName, true);
		$lReturn->load();
		return $lReturn;
	}
	
	/**
	 * 
	 * @param string $pModelName
	 * @param string $pMainModelName null if $pModelName is a main model name
	 * @param boolean $pLoadModel
	 * @throws \Exception
	 * @return NULL|Model
	 */
	private function _getInstanceModel($pModelName, $pMainModelName, $pLoadModel) {
		$lReturn = null;
		if (is_null($pMainModelName)) {
			$lInstanceModels =& $this->mInstanceModels;
		} else {
			// call getInstanceModel() to be sure to have a loaded main model
			$lMainModel = $this->getInstanceModel($pMainModelName);
			if (!array_key_exists($pModelName, $this->mLocalTypes[$pMainModelName])) {
				$lExists = false;
				while (!is_null($lMainModel->getExtendsModel()) && !$lExists) {
					$lExists = array_key_exists($pModelName, $this->mLocalTypes[$lMainModel->getExtendsModel()->getName()]);
					$lMainModel = $lMainModel->getExtendsModel();
				}
				if ($lExists) {
					$pMainModelName = $lMainModel->getName();
				}
			}
			$lInstanceModels =& $this->mLocalTypes[$pMainModelName];
		}
		if (!array_key_exists($pModelName, $lInstanceModels)) { // model doesn't exists
			$lMessageModel = is_null($pMainModelName) ? "main model '$pModelName'" : "local model '$pModelName' in main model '$pMainModelName'";
			throw new \Exception("$lMessageModel doesn't exists, you must define it");
		}
		if (is_object($lInstanceModels[$pModelName])) { // model already initialized
			$lReturn = $lInstanceModels[$pModelName];
		}else {
			if (count($lInstanceModels[$pModelName]) == 3) {
				$lReturn = $lInstanceModels[$pModelName][2];
			} else {
				if (is_null($pMainModelName)) {
					$lReturn = new MainModel($pModelName, $pLoadModel);
				} else {
					$lReturn = new LocalModel($pModelName, $pMainModelName, $pLoadModel);
				}
				
				if (is_object($lInstanceModels[$pModelName])) {
					if ($lInstanceModels[$pModelName] !== $lReturn) {
						throw new \Exception('already exists '.$pModelName.' '.var_export($pMainModelName, true));
					}
					if (!$pLoadModel) {
						throw new \Exception('model has been loaded');
					}
				}
				else { // else add model
					if ($pLoadModel) {
						$lInstanceModels[$pModelName] = $lReturn;
					} else {
						$lInstanceModels[$pModelName][] = $lReturn;
					}
				}
			}
		}
		return $lReturn;
	}
	
	/**
	 * 
	 * @param Model $pModel
	 */
	private function _addInstanceModel(Model $pModel) {
		if ($pModel instanceof LocalModel) {
			$lMainModel = $this->getInstanceModel($pModel->getMainModelName());
			$lInstanceModels =& $this->mLocalTypes[$pModel->getMainModelName()];
		} else {
			$lInstanceModels =& $this->mInstanceModels;
		}
		
		if (is_object($lInstanceModels[$pModel->getName()])) {
			throw new \Exception('model already added');
		}
		$lInstanceModels[$pModel->getName()] = $pModel;
	}
	
	public function getProperties(Model $pModel) {
		$lReturn = null;
		
		if ($pModel instanceof LocalModel) {
			$lInstanceModels =& $this->mLocalTypes[$pModel->getMainModel()->getName()];
		} else {
			$lInstanceModels =& $this->mInstanceModels;
		}
		
		if (is_null($this->mManifestParser) && is_object($lInstanceModels[$pModel->getName()]) && $lInstanceModels[$pModel->getName()]->isLoaded()) {
			$lReturn = [
				self::PROPERTIES     => $pModel->getProperties(), 
				self::EXTENDS_MODEL  => $pModel->getExtendsModel(),
				self::OBJECT_CLASS   => $pModel->getObjectClass()
			];
			if ($pModel instanceof MainModel) {
				$lReturn[self::SERIALIZATION] = $pModel->getSerialization();
			}
		}else {
			$lUnsetManifestParser = false;
			if (is_null($this->mManifestParser)) {
				$lUnsetManifestParser   = true;
				$lManifestPath_afe      = $lInstanceModels[$pModel->getName()][0];
				$lManifestPath_ad       = dirname($lManifestPath_afe);
				$lSerializationPath_afe = !is_null($lInstanceModels[$pModel->getName()][1]) ? $lInstanceModels[$pModel->getName()][1] : null;
				$this->mManifestParser  = ManifestParser::getInstance($pModel, $lManifestPath_afe, $lSerializationPath_afe);
				
				$this->_addInstanceModel($pModel);
				$this->_buildLocalTypes($pModel, $lManifestPath_ad);
			}
			$lExtendsModel = $this->_getExtendsModel($pModel);
			
			$lReturn = [
				self::EXTENDS_MODEL => $lExtendsModel,
				self::OBJECT_CLASS  => $this->mManifestParser->getObjectClass(),
				self::PROPERTIES    => $this->_buildProperties($pModel, $lExtendsModel)
			];
			
			if ($lUnsetManifestParser) {
				$this->mSerializationManifestParser = $this->mManifestParser->getSerializationManifestParser();
				unset($this->mManifestParser);
				$this->mManifestParser = null;
			}
		}
		return $lReturn;
	}
	
	private function _buildLocalTypes($pModel, $pManifestPath_ad) {
		if ($this->mManifestParser->isFocusOnLocalTypes()) {
			throw new \Exception('cannot define local types in local types');
		}
		if (!($pModel instanceof MainModel)) {
			// perhaps allow local models defined in there own manifest to have local types
			return;
		}
		$this->mLocalTypes[$pModel->getName()] = [];
		if ($this->mManifestParser->getLocalTypesCount() > 0) {
			$lXmlLocalTypes = [];
			$lMainModelName = $pModel->getName();
			
			$this->mManifestParser->registerComplexLocalModels($this->mLocalTypes[$lMainModelName], $pManifestPath_ad);
			$this->mManifestParser->activateFocusOnLocalTypes();
			
			do {
				$lTypeId = $this->mManifestParser->getCurrentLocalTypeId();
				
				if (array_key_exists($lTypeId, $this->mInstanceModels)) {
					throw new \Exception("local model in main model '$lMainModelName' has same name than another main model '$lTypeId' ");
				}
				if (array_key_exists($lTypeId, $this->mLocalTypes[$lMainModelName])) {
					throw new \Exception("several local model with same type '$lTypeId' in main model '$lMainModelName'");
				}
				$this->mLocalTypes[$lMainModelName][$lTypeId] = new LocalModel($lTypeId, $lMainModelName, false);
			} while ($this->mManifestParser->nextLocalType());
			
			$this->mManifestParser->activateFocusOnLocalTypes();
			do {
				$lTypeId = $this->mManifestParser->getCurrentLocalTypeId();
				$this->mLocalTypes[$lMainModelName][$lTypeId]->load();
			} while ($this->mManifestParser->nextLocalType());
			
			$this->mManifestParser->desactivateFocusOnLocalTypes();
		}
	}
	
	private function _getExtendsModel(Model $pModel) {
		$lModel = null;
		$lModelName = $this->mManifestParser->getExtends();
		if (!is_null($lModelName)) {
			$lMainModelName = $pModel->getMainModelName();
			if ($pModel instanceof MainModel) {
				$lMainModelName = null;
			}
			else if (array_key_exists($lModelName, $this->mInstanceModels)) {
				if (!is_null($lMainModelName) && array_key_exists($lModelName, $this->mLocalTypes[$lMainModelName])) {
					throw new \Exception("cannot determine if property '$lModelName' is local or main model");
				}
				$lMainModelName = null;
			}
			$lManifestParser = $this->mManifestParser;
			$this->mManifestParser = null;
			$lModel = $this->getInstanceModel($lModelName, $lMainModelName);
			$this->mManifestParser = $lManifestParser;
		}
		return $lModel;
	}
	
	/**
	 * @param Model $pCurrentModel
	 * @param Model $lExtendsModel
	 * @throws \Exception
	 * @return Property[]
	 */
	private function _buildProperties(Model $pCurrentModel, Model $lExtendsModel = null) {
		$lProperties = is_null($lExtendsModel) ? [] : $lExtendsModel->getProperties();
	
		do {
			$lModelName     = $this->mManifestParser->getCurrentPropertyModelName();
			$lMainModelName = $pCurrentModel->getMainModelName();
			
			if (array_key_exists($lModelName, $this->mInstanceModels)) {
				if (!is_null($lMainModelName) && array_key_exists($lModelName, $this->mLocalTypes[$lMainModelName])) {
					throw new \Exception("cannot determine if property '$lModelName' is local or main model");
				}
				$lMainModelName = null;
			}
			
			$lPropertyModel = $this->_getInstanceModel($lModelName, $lMainModelName, false);
			$lProperty      = $this->mManifestParser->getCurrentProperty($lPropertyModel);
			
			$lProperties[$lProperty->getName()] = $lProperty;
		} while ($this->mManifestParser->nextProperty());
	
		return $lProperties;
	}
	
	public function getSerializationInstance(MainModel $pModel) {
		if (!is_null($this->mSerializationManifestParser)) {
			$lInheritanceKey        =  $this->mSerializationManifestParser->getInheritanceKey();
			$lSerializationSettings = $this->mSerializationManifestParser->getSerializationSettings($pModel);
			$lSerialization         = $this->_getUniqueSerialization($pModel, $lSerializationSettings, $lInheritanceKey);
			unset($this->mSerializationManifestParser);
			$this->mSerializationManifestParser = null;
			return $lSerialization;
		}
		return $this->_getUniqueSerialization($pModel);
	}
	
	private function _getUniqueSerialization(MainModel $pModel, ComhonObject $pSerializationSettings = null, $pInheritanceKey = null) {
		$lSerialization = null;
		if (!is_null($pModel->getExtendsModel()) && !is_null($pModel->getExtendsModel()->getSerialization())) {
			$lExtendedSerializationSettings = $pModel->getExtendsModel()->getSerialization()->getSettings();
			$lExtendedInheritanceKey = $pModel->getExtendsModel()->getSerialization()->getInheritanceKey();
			$lSame = false;
			
			if (is_null($pSerializationSettings) || $pSerializationSettings === $lExtendedSerializationSettings) {
				$lSame = true;
			}
			else if ($pSerializationSettings->getModel()->getName() == $lExtendedSerializationSettings->getModel()->getName()) {
				$lSame = true;
				foreach ($pSerializationSettings->getModel()->getProperties() as $lProperty) {
					if ($pSerializationSettings->getValue($lProperty->getName()) !== $lExtendedSerializationSettings->getValue($lProperty->getName())) {
						$lSame = false;
						break;
					}
				}
			}
			if ($lSame) {
				$lInheritanceKey = is_null($pInheritanceKey) ? $lExtendedInheritanceKey : $pInheritanceKey;
				$lSerialization = SerializationUnit::getInstance($lExtendedSerializationSettings, $lInheritanceKey);
			} else {
				$lSerialization = SerializationUnit::getInstance($pSerializationSettings, $pInheritanceKey);
			}
		} else if (!is_null($pSerializationSettings)) {
			$lSerialization = SerializationUnit::getInstance($pSerializationSettings, $pInheritanceKey);
		}
		return $lSerialization;
	}
}