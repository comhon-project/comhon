<?php

namespace objectManagerLib\object\singleton;

use \Exception;
use objectManagerLib\object\model\ModelArray;
use objectManagerLib\object\model\ModelEnum;
use objectManagerLib\object\model\Integer;
use objectManagerLib\object\model\Float;
use objectManagerLib\object\model\Boolean;
use objectManagerLib\object\model\String;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\model\LocalModel;
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ModelForeign;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\SerializationUnit;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\SimpleLoadRequest;

class InstanceModel {
	
	/** simple models **/
	const INTEGER = 'integer';
	const BOOLEAN = 'boolean';
	const STRING  = 'string';

	const PROPERTIES     = 'properties';
	const OBJECT_CLASS   = 'objectClass';
	const SERIALIZATION  = 'serialization';
	
	private $mInstanceModels;
	private $mInstanceSerializations = array();
	private $mCurrentXmlSerialization;
	private $mManifestListFolder;
	private $mSerializationListFolder;
	private $mLocalTypeXml;
	private $mLocalTypes = array();
	
	private  static $_instance;
	
	public static function getInstance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
	
		return self::$_instance;
	}
	
	protected function __construct() {
		$this->_registerSimpleModelClasses();
		$this->_registerComplexModel();
	}	
	
	private function _registerSimpleModelClasses() {
		$this->mInstanceModels = array(
			Integer::ID => new Integer(),
			Float::ID   => new Float(),
			Boolean::ID => new Boolean(),
			String::ID  => new String()
		);
	}
	
	/**
	 * register path of each manifest
	 */
	private function _registerComplexModel() {
		$lManifestListPath = sprintf("%s/%s", "§TOKEN:manifestFolder§", "§TOKEN:manifestList§");
		$this->mManifestListFolder = dirname($lManifestListPath);
		$lSerializationListPath = sprintf("%s/%s", "§TOKEN:manifestFolder§", "§TOKEN:serializationList§");
		$this->mSerializationListFolder = dirname($lSerializationListPath);
		
		$lManifestList = simplexml_load_file($lManifestListPath);
		$lSerializationList = simplexml_load_file($lSerializationListPath);
		$lSerializationMap = array();
		
		foreach ($lSerializationList->serialization as $lSerialization) {
			$lSerializationMap[(string) $lSerialization["type"]] = (string) $lSerialization;
		}
		
		foreach ($lManifestList->manifest as $lManifest) {
			$lType = (string) $lManifest["type"];
			if (array_key_exists($lType, $this->mInstanceModels)) { 
				throw new Exception("several model with same type : '$lType'");
			}
			$lSerializationPath = array_key_exists($lType, $lSerializationMap) ? $lSerializationMap[$lType] : null;
			$this->mInstanceModels[$lType] = array((string) $lManifest, $lSerializationPath);
		}
	}
	
	
	public function hasInstanceModel($pModelName, $pMainModelName = null) {
		if (is_null($pMainModelName)) {
			return array_key_exists($pModelName, $this->mInstanceModels);
		} else {
			return array_key_exists($pMainModelName, $this->mLocalTypes) && array_key_exists($pModelName, $this->mLocalTypes[$pMainModelName]);
		}
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
			}else {
				$lManifest = $this->mLocalTypeXml;
			}
			$lExtends     = isset($lManifest["extends"]) ? (string) $lManifest["extends"] : null; // TODO manage extends
			$lObjectClass = isset($lManifest["object"]) ? (string) $lManifest["object"] : null;
			$lReturn      = array(self::PROPERTIES => array(), self::OBJECT_CLASS => $lObjectClass);
			
			if (is_null($this->mLocalTypeXml) && !is_null($lInstanceModels[$pModel->getModelName()][1])) {
				$lSerializationPath = sprintf("%s/%s", $this->mSerializationListFolder, $lInstanceModels[$pModel->getModelName()][1]);
				$this->mCurrentXmlSerialization  = simplexml_load_file($lSerializationPath);
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
			list($lName, $lModel, $lSerializationName, $lIsId, $lEnum) = $this->_getBaseInfosForProperty($lPropertyXml, $pMainModelName);
			if (is_null($lEnum)) {
				$lProperty = new Property($lModel, $lName, $lSerializationName, $lIsId);
			} else if ($lModel instanceof SimpleModel) {
				if ($lIsId) {
					throw new Exception("enum property with name '$lName' can't be an id");
				}
				$lProperty = new EnumProperty($lModel, $lName, $lEnum, $lSerializationName);
			} else {
				throw new Exception("enum property with name '$lName' must be a simple model");
			}
			$lProperties[$lName] = $lProperty;
		}
		foreach ($pPropertiesXml->foreignProperty as $lPropertyXml) {
			list($lName, $lModel, $lSerializationName, $lIsId, $lEnum) = $this->_getBaseInfosForProperty($lPropertyXml, $pMainModelName);
			if (!is_null($lEnum)) {
				throw new Exception("foreign property with name '$lName' can't be an enumeration");
			}
			if ($lModel instanceof SimpleModel) {
				throw new Exception("foreign property with name '$lName' can't be a simple model");
			}
			$lProperty = new ForeignProperty($lModel, $lName, $lSerializationName);
			$lProperties[$lName] = $lProperty;
		}
		return $lProperties;
	}
	
	private function _getBaseInfosForProperty($pPropertyXml, $pMainModelName) {
		$lName  = isset($pPropertyXml->name) ? (string) $pPropertyXml->name : (string) $pPropertyXml;
		$lIsId  = (isset($pPropertyXml["id"]) && ((string) $pPropertyXml["id"] == "1")) ? true : false;
		$lEnum  = null;
	
		if (array_key_exists((string) $pPropertyXml["type"], $this->mLocalTypes[$pMainModelName])) {
			$lModel = $this->mLocalTypes[$pMainModelName][(string) $pPropertyXml["type"]];
			$lSerializationName = null;
		}else {
			$lModel = $this->_buildModel($pPropertyXml, $pMainModelName);
			$lSerializationsNode = isset($this->mCurrentXmlSerialization->properties->$lName) ? $this->mCurrentXmlSerialization->properties->$lName : null;
			$lSerializationName  = (!is_null($lSerializationsNode) && isset($lSerializationsNode["serializationName"])) ? (string) $lSerializationsNode["serializationName"] : null;
		}
			
		if ($lIsId && !($lModel instanceof SimpleModel)) {
			throw new Exception("id property with name '$lName' must be a simple model");
		}
		if (isset($pProperty->enum)) {
			$lEnum = array();
			foreach ($pProperty->enum->value as $lValue) {
				$lEnum[] = (string) $lValue;
			}
		}
		return array($lName, $lModel, $lSerializationName, $lIsId, $lEnum);
	}
	
	private function _buildLocalTypes($pManifestXML, $pModel) {
		// if $mLocalTypeXml is not null we are already building a local type (we can't define local type in another local type)
		if (is_null($this->mLocalTypeXml) && ($pModel instanceof MainModel)) {
			$lXmlLocalTypes = array();
			$lMainModelName = $pModel->getModelName();
			$this->mLocalTypes[$lMainModelName] = array();
			if (isset($pManifestXML->types) && is_null($this->mLocalTypeXml)) {
				foreach ($pManifestXML->types->type as $lLocalType) {
					$lTypeId                     = (string) $lLocalType["id"];
					$lXmlLocalTypes[$lTypeId]    = $lLocalType;
					$this->mLocalTypes[$lMainModelName][$lTypeId] = new LocalModel($lTypeId, $lMainModelName, false);
				}
				foreach ($this->mLocalTypes[$lMainModelName] as $lTypeId => $lLocalType) {
					$this->mLocalTypeXml = $lXmlLocalTypes[$lTypeId];
					$lLocalType->load();
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
			if (!array_key_exists($lType, $this->mInstanceSerializations)) {
				$this->mInstanceSerializations[$lType] = array();
			}
			if (array_key_exists($lId, $this->mInstanceSerializations[$lType])) {
				$lSerialization = $this->mInstanceSerializations[$lType][$lId];
			} else {
				$lSerialization = $this->_getSerializationObject($lType, $lId);
			}
		}
		return $lSerialization;
	}
	
	private function _getSerializationObject($pType, $pId) {
		if (array_key_exists($pId, $this->mInstanceSerializations[$pType])) {
			$lObject = $this->mInstanceSerializations[$pType][$pId];
		} else {
			$lRequest = new SimpleLoadRequest($pType);
			$lObject = $lRequest->execute($pId);
			$this->mInstanceSerializations[$pType][$pId] = $lObject;
		}
		return $lObject;
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
			$lReturn = $this->_getInstanceModel($lTypeId, $pMainModelName, false);
		}
		return $lReturn;
	}
	
}