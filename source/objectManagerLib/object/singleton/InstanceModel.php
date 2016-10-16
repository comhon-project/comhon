<?php

namespace objectManagerLib\object\singleton;

use \Exception;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\ModelEnum;
use objectManagerLib\object\model\Integer;
use objectManagerLib\object\model\Float;
use objectManagerLib\object\model\Boolean;
use objectManagerLib\object\model\String;
use objectManagerLib\object\model\DateTime;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\LocalModel;
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ModelForeign;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\SerializationUnit;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\CompositionProperty;
use objectManagerLib\object\object\Config;
use objectManagerLib\object\parser\ManifestParser;
use objectManagerLib\utils\Utils;

class InstanceModel {

	const PROPERTIES     = 'properties';
	const OBJECT_CLASS   = 'objectClass';
	const SERIALIZATION  = 'serialization';
	
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
			__DIR__ . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'manifestCollection' . DIRECTORY_SEPARATOR . 'manifest'. DIRECTORY_SEPARATOR .'manifestList.json', 
			__DIR__ . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'manifestCollection' . DIRECTORY_SEPARATOR . 'serialization' . DIRECTORY_SEPARATOR . 'serializationList.json',
			$this->mInstanceModels
		);
		
		ManifestParser::registerComplexModels(
			Config::getInstance()->getValue('manifestList'),
			Config::getInstance()->getValue('serializationList'),
			$this->mInstanceModels
		);
	}	
	
	private function _registerSimpleModelClasses() {
		$this->mInstanceModels = array(
			Integer::ID  => new Integer(),
			Float::ID    => new Float(),
			Boolean::ID  => new Boolean(),
			String::ID   => new String(),
			DateTime::ID => new DateTime()
		);
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
	 * @throws Exception
	 * @return NULL|Model
	 */
	private function _getInstanceModel($pModelName, $pMainModelName, $pLoadModel) {
		$lReturn = null;
		if (is_null($pMainModelName)) {
			$lInstanceModels =& $this->mInstanceModels;
		} else {
			$lMainModel = $this->getInstanceModel($pMainModelName);
			$lInstanceModels =& $this->mLocalTypes[$pMainModelName];
		}
		
		if (!array_key_exists($pModelName, $lInstanceModels)) { // model doesn't exists
			throw new Exception("'$pModelName' doesn't exists, you must define it");
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
						throw new \Exception("already exists ".$pModelName.' '.var_export($pMainModelName, true));
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
		
		if (is_object($lInstanceModels[$pModel->getModelName()])) {
			throw new \Exception('model already added');
		}
		$lInstanceModels[$pModel->getModelName()] = $pModel;
	}
	
	public function getProperties(Model $pModel) {
		$lReturn = null;
		
		if ($pModel instanceof LocalModel) {
			$lInstanceModels =& $this->mLocalTypes[$pModel->getMainModel()->getModelName()];
		} else {
			$lInstanceModels =& $this->mInstanceModels;
		}
		
		if (is_null($this->mManifestParser) && is_object($lInstanceModels[$pModel->getModelName()]) && $lInstanceModels[$pModel->getModelName()]->isLoaded()) {
			$lReturn = array(
				self::PROPERTIES     => $pModel->getProperties(), 
				self::OBJECT_CLASS   => $pModel->getObjectClass()
			);
			if ($pModel instanceof MainModel) {
				$lReturn[self::SERIALIZATION] = $pModel->getSerialization();
			}
		}else {
			$lUnsetManifestParser = false;
			if (is_null($this->mManifestParser)) {
				$lUnsetManifestParser   = true;
				$lManifestPath_afe      = $lInstanceModels[$pModel->getModelName()][0];
				$lManifestPath_ad       = dirname($lManifestPath_afe);
				$lSerializationPath_afe = !is_null($lInstanceModels[$pModel->getModelName()][1]) ? $lInstanceModels[$pModel->getModelName()][1] : null;
				$this->mManifestParser  = ManifestParser::getInstance($pModel, $lManifestPath_afe, $lSerializationPath_afe);
				
				$this->_addInstanceModel($pModel);
				$this->_buildLocalTypes($pModel, $lManifestPath_ad);
			}
			
			$lExtends     = $this->mManifestParser->getExtends(); // TODO manage extends
			$lObjectClass = $this->mManifestParser->getObjectClass();
			$lReturn      = [
				self::PROPERTIES   => array(),
				self::OBJECT_CLASS => $lObjectClass,
				self::PROPERTIES   => $this->_buildProperties($pModel)
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
		// if localTypeIndex is different than -1 we are already building a local type (we can't define local type in another local type)
		if (($this->mManifestParser->getLocalTypeIndex() == -1) && ($pModel instanceof MainModel)) {
			$lXmlLocalTypes = [];
			$lMainModelName = $pModel->getModelName();
			$this->mLocalTypes[$lMainModelName] = array();
			
			$this->mManifestParser->registerComplexLocalModels($this->mLocalTypes[$lMainModelName], $pManifestPath_ad);
			
			while ($this->mManifestParser->nextLocalType()) {
				$lTypeId = $this->mManifestParser->getCurrentLocalTypeId();
				
				if (array_key_exists($lTypeId, $this->mLocalTypes[$lMainModelName])) {
					throw new Exception("several local model with same type '$lTypeId' in main model '$lMainModelName'");
				}
				$this->mLocalTypes[$lMainModelName][$lTypeId] = new LocalModel($lTypeId, $lMainModelName, false);
			}
			$this->mManifestParser->resetLocalTypeIndex();
			
			while ($this->mManifestParser->nextLocalType()) {
				$lTypeId = $this->mManifestParser->getCurrentLocalTypeId();
				$this->mLocalTypes[$lMainModelName][$lTypeId]->load();
			}
			$this->mManifestParser->resetLocalTypeIndex();
		}
	}
	
	private function _buildProperties($pMainModel) {
		$lProperties = [];
	
		while ($this->mManifestParser->nextProperty()) {
			$lModelName     = $this->mManifestParser->getCurrentPropertyModelName();
			$lMainModelName = $pMainModel->getMainModelName();
			
			if (array_key_exists($lModelName, $this->mInstanceModels)) {
				if (!is_null($lMainModelName) && array_key_exists($lModelName, $this->mLocalTypes[$lMainModelName])) {
					throw new \Exception("cannot determine if property '$lModelName' is local or main model");
				}
				$lMainModelName = null;
			}
			
			$lPropertyModel = $this->_getInstanceModel($lModelName, $lMainModelName, false);
			$lProperty      = $this->mManifestParser->getCurrentProperty($lPropertyModel);
			
			$lProperties[$lProperty->getName()] = $lProperty;
		}
	
		return $lProperties;
	}
	
	public function getSerialization(MainModel $pModel) {
		if (!is_null($this->mSerializationManifestParser)) {
			$lSerialization = $this->mSerializationManifestParser->getSerialization($pModel);
			unset($this->mSerializationManifestParser);
			$this->mSerializationManifestParser = null;
			return $lSerialization;
		}
		return null;
	}
}