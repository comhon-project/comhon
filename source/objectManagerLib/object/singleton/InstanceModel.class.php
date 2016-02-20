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
	
	
	public function hasInstanceModel($pModelName) {
		return array_key_exists($pModelName, $this->mInstanceModels);
	}
	
	public function getInstanceModel($pModelName) {
		$lReturn = $this->_getInstanceModel($pModelName, true);
		$lReturn->load();
		return $lReturn;
	}
	
	private function _getInstanceModel($pModelName, $pLoadModel) {
		$lReturn = null;
		
		if (!array_key_exists($pModelName, $this->mInstanceModels)) { // model doesn't exists
			throw new Exception("'$pModelName' doesn't exists, you must define it");
		}
		if (is_object($this->mInstanceModels[$pModelName])) { // model already initialized
			$lReturn = $this->mInstanceModels[$pModelName];
		}else {
			if (count($this->mInstanceModels[$pModelName]) == 3) {
				$lReturn = $this->mInstanceModels[$pModelName][2];
			} else {
				$lReturn = new MainModel($pModelName, $pLoadModel);
				// if model has been initialized during itself initialization
				if (is_object($this->mInstanceModels[$pModelName])) {
					$lReturn = $this->mInstanceModels[$pModelName];
				}
				// else add model
				else {
					if ($pLoadModel) {trigger_error("-+-".$lReturn->getModelName());
						$this->mInstanceModels[$pModelName] = $lReturn;
					} else {
						$this->mInstanceModels[$pModelName][] = $lReturn;
					}
				}
			}
		}
		return $lReturn;
	}
	
	public function addInstanceModel($pModel) {
		if (is_null($this->mLocalTypeXml) && ($pModel instanceof Model) && !is_object($this->mInstanceModels[$pModel->getModelName()])) {
			$this->mInstanceModels[$pModel->getModelName()] = $pModel;
		}
	}
	
	public function getProperties($pModel) {
		$lReturn = null;
		if ($pModel instanceof Model) {
			if (is_null($this->mLocalTypeXml) && is_object($this->mInstanceModels[$pModel->getModelName()]) && $this->mInstanceModels[$pModel->getModelName()]->isLoaded()) {
				$lReturn = array(
					self::PROPERTIES     => $pModel->getProperties(), 
					self::OBJECT_CLASS   => $pModel->getObjectClass()
				);
				if ($pModel instanceof MainModel) {
					$lReturn[self::SERIALIZATION] = $pModel->getSerialization();
				}
			}else {
				if (is_null($this->mLocalTypeXml)) {
					$lManifestPath = sprintf("%s/%s", $this->mManifestListFolder, $this->mInstanceModels[$pModel->getModelName()][0]);
					$lManifest = simplexml_load_file($lManifestPath);
				}else {
					$lManifest = $this->mLocalTypeXml;
				}
				$lExtends     = isset($lManifest["extends"]) ? (string) $lManifest["extends"] : null; // TODO manage extends
				$lObjectClass = isset($lManifest["object"]) ? (string) $lManifest["object"] : null;
				$lReturn      = array(self::PROPERTIES => array(), self::OBJECT_CLASS => $lObjectClass);
				
				if (is_null($this->mLocalTypeXml) && !is_null($this->mInstanceModels[$pModel->getModelName()][1])) {
					$lSerializationPath = sprintf("%s/%s", $this->mSerializationListFolder, $this->mInstanceModels[$pModel->getModelName()][1]);
					$this->mCurrentXmlSerialization  = simplexml_load_file($lSerializationPath);
				}
				$this->addInstanceModel($pModel);
				$this->_buildLocalTypes($lManifest);
				$lReturn[self::PROPERTIES] = $this->_buildProperties($lManifest->properties);
			}
		}
		return $lReturn;
	}
	
	private function _buildProperties($pPropertiesXml) {
		$lProperties = array();
		foreach ($pPropertiesXml->property as $lPropertyXml) {
			list($lName, $lModel, $lSerializationName, $lIsId) = $this->_getBaseInfosForProperty($lPropertyXml);
			$lProperty = new Property($lModel, $lName, $lSerializationName, $lIsId);
			$lProperties[$lName] = $lProperty;
		}
		foreach ($pPropertiesXml->foreignProperty as $lPropertyXml) {
			list($lName, $lModel, $lSerializationName, $lIsId) = $this->_getBaseInfosForProperty($lPropertyXml);
			if ($lModel instanceof SimpleModel) {
				throw new Exception("foreign property with name '$lName' can't be a simple model");
			}
			$lModelForeign = new ModelForeign($lModel);
			$lProperty = new ForeignProperty($lModelForeign, $lName, $lSerializationName);
			$lProperties[$lName] = $lProperty;
		}
		return $lProperties;
	}
	
	private function _getBaseInfosForProperty($pPropertyXml) {
		$lName  = isset($pPropertyXml->name) ? (string) $pPropertyXml->name : (string) $pPropertyXml;
		$lIsId  = (isset($pPropertyXml["id"]) && ((string) $pPropertyXml["id"] == "1")) ? true : false;
	
		if (array_key_exists((string) $pPropertyXml["type"], $this->mLocalTypes)) {
			$lModel = $this->mLocalTypes[(string) $pPropertyXml["type"]];
			$lSerializationName = null;
		}else {
			$lModel = $this->_buildModel($pPropertyXml);
			$lSerializationsNode = isset($this->mCurrentXmlSerialization->properties->$lName) ? $this->mCurrentXmlSerialization->properties->$lName : null;
			$lSerializationName  = (!is_null($lSerializationsNode) && isset($lSerializationsNode["serializationName"])) ? (string) $lSerializationsNode["serializationName"] : null;
		}
			
		if ($lIsId && !($lModel instanceof SimpleModel)) {
			throw new Exception("id property with name '$lName' must be a simple model");
		}
		return array($lName, $lModel, $lSerializationName, $lIsId);
	}
	
	private function _buildLocalTypes($pManifestXML) {
		// if $mLocalTypeXml is not null we are already building a local type (we can't define local type in another local type)
		if (is_null($this->mLocalTypeXml)) {
			$lXmlLocalTypes    = array();
			$this->mLocalTypes = array();
			if (isset($pManifestXML->types) && is_null($this->mLocalTypeXml)) {
				foreach ($pManifestXML->types->type as $lLocalType) {
					$lTypeId                     = (string) $lLocalType["id"];
					$lXmlLocalTypes[$lTypeId]    = $lLocalType;
					$this->mLocalTypes[$lTypeId] = new Model($lTypeId, false);
				}
				foreach ($this->mLocalTypes as $lTypeId => $lLocalType) {
					$this->mLocalTypeXml = $lXmlLocalTypes[$lTypeId];
					$this->mLocalTypes[$lTypeId]->load();
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
	
	private function _buildModel($pProperty) {
		$lTypeId = (string) $pProperty["type"];
		if ($lTypeId == "array") {
			if (!isset($pProperty->values['name'])) {
				throw new \Exception('type array must have a values name. property : '.(string) $pProperty->name);
			}
			$lReturn = new ModelArray($this->_buildModel($pProperty->values), (string) $pProperty->values['name']);
		}
		else if (array_key_exists($lTypeId, $this->mLocalTypes)) {
			$lReturn = $this->mLocalTypes[$lTypeId];
		}
		else {
			$lModel = $this->_getInstanceModel($lTypeId, false);
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