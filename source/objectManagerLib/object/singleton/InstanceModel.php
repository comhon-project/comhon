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

class InstanceModel {

	const PROPERTIES     = 'properties';
	const OBJECT_CLASS   = 'objectClass';
	const SERIALIZATION  = 'serialization';
	
	private $mInstanceModels;
	private $mCurrentXmlSerialization;
	private $mManifestListFolder;
	private $mSerializationListFolder;
	private $mLocalTypeXml;
	private $mLocalTypes = array();
	
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
		$this->_registerComplexModel(
			__DIR__ . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'manifestCollection' . DIRECTORY_SEPARATOR . 'manifest'. DIRECTORY_SEPARATOR .'manifestList.xml', 
			__DIR__ . DIRECTORY_SEPARATOR .'..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'manifestCollection' . DIRECTORY_SEPARATOR . 'serialization' . DIRECTORY_SEPARATOR . 'serializationList.xml'
		);
		foreach ($this->mInstanceModels as $lModelName => $lValue) {
			$this->getInstanceModel($lModelName);
		}
		
		$this->_registerComplexModel(
			Config::getInstance()->getValue('manifestList'),
			Config::getInstance()->getValue('serializationList')
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
	
	/**
	 * register path of each manifest
	 */
	private function _registerComplexModel($pManifestListPath, $pSerializationListPath) {
		$this->mManifestListFolder = dirname($pManifestListPath);
		$this->mSerializationListFolder = dirname($pSerializationListPath);
		
		$lManifestList = simplexml_load_file($pManifestListPath);
		if ($lManifestList === false) {
			throw new \Exception("manifestList file not found '$pManifestListPath'");
		}
		$lSerializationList = simplexml_load_file($pSerializationListPath);
		if ($lSerializationList === false) {
			throw new \Exception("serializationList file not found '$pSerializationListPath'");
		}
		$lSerializationMap = array();
		
		foreach ($lSerializationList->serialization as $lSerialization) {
			$lSerializationMap[(string) $lSerialization["type"]] = (string) $lSerialization;
		}
		
		$this->_addComplexModelToMap($lManifestList, $lSerializationMap);
	}
	
	/**
	 * 
	 * @param SimpleXMLElement $pManifestList
	 * @param array $pSerializationMap
	 * @param MainModel $pMainModel
	 * @throws Exception
	 */
	private function _addComplexModelToMap($pManifestList, $pSerializationMap, $pMainModel = null) {
		if (is_null($pMainModel)) {
			$lInstanceModels =& $this->mInstanceModels;
		} else {
			$lInstanceModels =& $this->mLocalTypes[$pMainModel->getModelName()];
		}
		foreach ($pManifestList->manifest as $lManifest) {
			$lType = (string) $lManifest["type"];
			if (array_key_exists($lType, $lInstanceModels)) {
				throw new Exception("several model with same type : '$lType'");
			}
			$lSerializationPath = array_key_exists($lType, $pSerializationMap) ? $pSerializationMap[$lType] : null;
			$lInstanceModels[$lType] = array((string) $lManifest, $lSerializationPath);
		}
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
				
				if (is_object($lInstanceModels[$pModelName])) { // if model has been initialized during itself initialization
					$lReturn = $lInstanceModels[$pModelName];
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
		
		if (is_null($this->mLocalTypeXml) && !is_object($lInstanceModels[$pModel->getModelName()])) {
			$lInstanceModels[$pModel->getModelName()] = $pModel;
		}
	}
	
	public function getProperties(Model $pModel) {
		$lReturn = null;
		
		if ($pModel instanceof LocalModel) {
			$lInstanceModels =& $this->mLocalTypes[$pModel->getMainModel()->getModelName()];
		} else {
			$lInstanceModels =& $this->mInstanceModels;
		}
		
		if (is_null($this->mLocalTypeXml) && is_object($lInstanceModels[$pModel->getModelName()]) && $lInstanceModels[$pModel->getModelName()]->isLoaded()) {
			$lReturn = array(
				self::PROPERTIES     => $pModel->getProperties(), 
				self::OBJECT_CLASS   => $pModel->getObjectClass()
			);
			if ($pModel instanceof MainModel) {
				$lReturn[self::SERIALIZATION] = $pModel->getSerialization();
			}
		}else {
			if (is_null($this->mLocalTypeXml)) {
				$lManifestPath = sprintf("%s/%s", $this->mManifestListFolder, $lInstanceModels[$pModel->getModelName()][0]);
				$lManifest = simplexml_load_file($lManifestPath);
				if ($lManifest === false) {
					throw new \Exception("manifest file not found '$lManifestPath'");
				}
			}else {
				$lManifest = $this->mLocalTypeXml;
			}
			$lExtends     = isset($lManifest["extends"]) ? (string) $lManifest["extends"] : null; // TODO manage extends
			$lObjectClass = isset($lManifest["object"]) ? (string) $lManifest["object"] : null;
			$lReturn      = array(self::PROPERTIES => array(), self::OBJECT_CLASS => $lObjectClass);
			
			if (is_null($this->mLocalTypeXml) && !is_null($lInstanceModels[$pModel->getModelName()][1])) {
				$lSerializationPath = sprintf("%s/%s", $this->mSerializationListFolder, $lInstanceModels[$pModel->getModelName()][1]);
				$this->mCurrentXmlSerialization  = simplexml_load_file($lSerializationPath);
				if ($this->mCurrentXmlSerialization === false) {
					throw new \Exception("serialization file not found '$lSerializationPath'");
				}
			}
			$this->_addInstanceModel($pModel);
			$this->_buildLocalTypes($lManifest, $pModel);
			$lReturn[self::PROPERTIES] = $this->_buildProperties($lManifest->properties, $pModel->getMainModelName());
		}
		return $lReturn;
	}
	
	private function _buildProperties($pPropertiesXml, $pMainModelName) {
		$lProperties = array();
		foreach ($pPropertiesXml->property as $lPropertyXml) {
			list($lName, $lModel, $lSerializationName, $lIsId, $lCompositions) = $this->_getBaseInfosForProperty($lPropertyXml, $pMainModelName);
			$lProperty = new Property($lModel, $lName, $lSerializationName, $lIsId);
			$lProperties[$lName] = $lProperty;
		}
		foreach ($pPropertiesXml->foreignProperty as $lPropertyXml) {
			list($lName, $lModel, $lSerializationName, $lIsId, $lCompositions) = $this->_getBaseInfosForProperty($lPropertyXml, $pMainModelName);
			if ($lModel instanceof SimpleModel) {
				throw new Exception("foreign property with name '$lName' can't be a simple model");
			}
			$lModelForeign = new ModelForeign($lModel);
			if (is_null($lCompositions)) {
				$lProperty = new ForeignProperty($lModelForeign, $lName, $lSerializationName);
			} else {
				$lProperty = new CompositionProperty($lModelForeign, $lName, $lCompositions, $lSerializationName);
			}
			$lProperties[$lName] = $lProperty;
		}
		return $lProperties;
	}
	
	private function _getBaseInfosForProperty($pPropertyXml, $pMainModelName) {
		$lTypeId = (string) $pPropertyXml["type"];
		$lName   = isset($pPropertyXml->name) ? (string) $pPropertyXml->name : (string) $pPropertyXml;
		$lIsId   = (isset($pPropertyXml["id"]) && ((string) $pPropertyXml["id"] == "1")) ? true : false;
		$lModel  = $this->_buildModel($pPropertyXml, $pMainModelName);
		
		if (array_key_exists($lTypeId, $this->mLocalTypes[$pMainModelName])) {
			$lSerializationName = null;
			$lCompositions      = null;
		}
		else {
			list($lSerializationName, $lCompositions) = $this->_getSerializationBaseInfosForProperty($this->mCurrentXmlSerialization, $lName);
		}
			
		if ($lIsId && !($lModel instanceof SimpleModel)) {
			throw new Exception("id property with name '$lName' must be a simple model");
		}
		return array($lName, $lModel, $lSerializationName, $lIsId, $lCompositions);
	}
	
	private function _getSerializationBaseInfosForProperty($pXmlSerialization, $pPropertyName) {
		$lSerializationName = null;
		$lCompositions      = null;
		
		if (isset($pXmlSerialization->properties->$pPropertyName)) {
			$lSerializationNode = $pXmlSerialization->properties->$pPropertyName;
			if (isset($lSerializationNode['serializationName'])) {
				$lSerializationName = (string) $lSerializationNode['serializationName'];
			}
			if (isset($lSerializationNode->compositions->composition)) {
				$lCompositions = [];
				foreach ($lSerializationNode->compositions->composition as $lComposition) {
					$lCompositions[] = (string) $lComposition;
				}
			}
		}
		
		return array($lSerializationName, $lCompositions);
	}
	
	private function _buildLocalTypes($pManifestXML, $pModel) {
		// if $mLocalTypeXml is not null we are already building a local type (we can't define local type in another local type)
		if (is_null($this->mLocalTypeXml) && ($pModel instanceof MainModel)) {
			$lXmlLocalTypes = array();
			$lMainModelName = $pModel->getModelName();
			$this->mLocalTypes[$lMainModelName] = array();
			
			if (isset($pManifestXML->manifests)) {
				$this->_addComplexModelToMap($pManifestXML->manifests, [], $pModel);
			}
			
			if (isset($pManifestXML->types)) {
				foreach ($pManifestXML->types->type as $lLocalType) {
					$lTypeId                     = (string) $lLocalType["id"];
					$lXmlLocalTypes[$lTypeId]    = $lLocalType;
					
					if (array_key_exists($lTypeId, $this->mLocalTypes[$lMainModelName])) {
						throw new Exception("several local model with same type '$lTypeId' in main model '$lMainModelName'");
					}
					$this->mLocalTypes[$lMainModelName][$lTypeId] = new LocalModel($lTypeId, $lMainModelName, false);
				}
				foreach ($lXmlLocalTypes as $lTypeId => $lXmlLocalType) {
					$this->mLocalTypeXml = $lXmlLocalType;
					$this->mLocalTypes[$lMainModelName][$lTypeId]->load();
				}
			}
			$this->mLocalTypeXml = null;
		}
	}
	
	public function getSerialization($pModel) {
		$lReturn = null;
		if ($pModel instanceof MainModel) {
			$lCurrentXmlSerialization = $this->mCurrentXmlSerialization;
			$this->mCurrentXmlSerialization = null;
			if ($pModel->hasLoadedSerialization()) {
				$lReturn = $pModel->getSerialization();
			}
			else if (!is_null($lCurrentXmlSerialization) && isset($lCurrentXmlSerialization->serialization)) {
				$lReturn = $this->_buildSerialization($lCurrentXmlSerialization->serialization);
			}
		}
		return $lReturn;
	}
	
	private function _buildSerialization($pSerializationNode) {
		$lType = (string) $pSerializationNode["type"];
		if (isset($pSerializationNode->$lType)) {
			$lObjectXml = $pSerializationNode->$lType;
			$lSerialization = $this->getInstanceModel($lType)->getObjectInstance();
			$lSerialization->fromXml($lObjectXml);
		} else {
			$lId = (string) $pSerializationNode;
			if (empty($lId)) {
				throw new \Exception('malformed serialization, must have description or id');
			}
			$lSerialization = $this->getInstanceModel($lType)->loadObject($lId);
		}
		return $lSerialization;
	}
	
	private function _buildModel($pProperty, $pMainModelName) {
		$lTypeId = (string) $pProperty["type"];
		if ($lTypeId == "array") {
			if (!isset($pProperty->values['name'])) {
				throw new \Exception('type array must have a values name. property : '.(string) $pProperty->name);
			}
			$lReturn = new ModelArray($this->_buildModel($pProperty->values, $pMainModelName), (string) $pProperty->values['name']);
		}
		else {
			if (array_key_exists($lTypeId, $this->mInstanceModels)) {
				if (array_key_exists($lTypeId, $this->mLocalTypes[$pMainModelName])) {
					throw new \Exception("cannot determine if property '$lTypeId' is local or main model");
				}
				$pMainModelName = null;
			}
			$lModel = $this->_getInstanceModel($lTypeId, $pMainModelName, false);
			if (isset($pProperty->enum)) {
				$lEnum = array();
				foreach ($pProperty->enum->value as $lValue) {
					$lEnum[] = (string) $lValue;
				}
				$lReturn = new ModelEnum($lModel, $lEnum);
			}else {
				$lReturn = $lModel;
			}
		}
		return $lReturn;
	}
	
}