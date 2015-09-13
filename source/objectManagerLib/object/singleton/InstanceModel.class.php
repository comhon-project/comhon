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
use objectManagerLib\object\model\Property;
use objectManagerLib\object\model\ModelForeign;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\SerializationUnit;
use objectManagerLib\object\model\SerializableProperty;

class InstanceModel {
	
	/** simple models **/
	const INTEGER = "integer";
	const BOOLEAN = "boolean";
	const STRING  = "string";
	
	/** complex models **/
	const TEST             = "Test";
	const TEST_CHILD       = "TestChild";
	const TEST_CHILD_2     = "TestChild2";
	const TEST_CHILD_CHILD = "TestChildChild";
	const TEST_FOREIGN     = "TestForeign";

	private $mInstanceModels;
	private $mInstanceSerializations = array();
	private $mDataBaseProperties;
	private $mManifestFolder = "§TOKEN:manifestFolder§";
	private $mManifestListFolder;
	private $mSerializationListFolder;
	
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
		$lManifestListPath = sprintf("%s/%s", $this->mManifestFolder, "§TOKEN:manifestList§");
		$this->mManifestListFolder = dirname($lManifestListPath);
		$lSerializationListPath = sprintf("%s/%s", $this->mManifestFolder, "§TOKEN:serializationList§");
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
			$lExtends = isset($lManifest["extends"]) ? (string) $lManifest["extends"] : null;
			$lObjectClass = isset($lManifest["object"]) ? (string) $lManifest["object"] : null;
			$lSerializationPath = array_key_exists($lType, $lSerializationMap) ? $lSerializationMap[$lType] : null;
			$this->mInstanceModels[$lType] = array((string) $lManifest, $lExtends, $lSerializationPath, $lObjectClass);
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
		// if model exists
		if (array_key_exists($pModelName, $this->mInstanceModels)) {
			// if model already initialized
			if (is_object($this->mInstanceModels[$pModelName])) {
				$lReturn = $this->mInstanceModels[$pModelName];
			}else {
				if (count($this->mInstanceModels[$pModelName]) == 5) {
					$lReturn = $this->mInstanceModels[$pModelName][4];
				} else {
					$lReturn = new Model($pModelName, $pLoadModel);
					// if model has been initialized during itself initialization
					if (is_object($this->mInstanceModels[$pModelName])) {
						$lReturn = $this->mInstanceModels[$pModelName];
					}
					// else add model
					else {
						if ($pLoadModel) {
							$this->mInstanceModels[$pModelName] = $lReturn;
						} else {
							$this->mInstanceModels[$pModelName][4] = $lReturn;
						}
					}
				}
			}
		}
		// else model doesn't exists
		else {
			throw new Exception("'$pModelName' doesn't exists, you must define it");
		}
		return $lReturn;
	}
	
	public function addInstanceModel($pModel) {
		if (($pModel instanceof Model) && !is_object($this->mInstanceModels[$pModel->getModelName()])) {
			$this->mInstanceModels[$pModel->getModelName()] = $pModel;
		}
	}
	
	public function getProperties($pModel) {
		if ($pModel instanceof Model) {
			if (is_object($this->mInstanceModels[$pModel->getModelName()]) && $this->mInstanceModels[$pModel->getModelName()]->isLoaded()) {
				$lReturn = array(
					"properties"    => $pModel->getProperties(), 
					"ids"           => $pModel->getIds(), 
					"objectClass"    => $pModel->getObjectCass(), 
					"serialization" => $pModel->getSerialization()
				);
			}else {
				$lObjectClass = $this->mInstanceModels[$pModel->getModelName()][3];
				$lReturn = array("properties" => array(), "ids" => array(), "serialization" => null, "objectClass" => $lObjectClass);
				$lManifestPath = sprintf("%s/%s", $this->mManifestListFolder, $this->mInstanceModels[$pModel->getModelName()][0]);
				$lManifest = simplexml_load_file($lManifestPath);
				
				$lSerializationXml = null;
				if (!is_null($this->mInstanceModels[$pModel->getModelName()][2])) {
					$lSerializationPath = sprintf("%s/%s", $this->mSerializationListFolder, $this->mInstanceModels[$pModel->getModelName()][2]);
					$lSerializationXml = simplexml_load_file($lSerializationPath);
				}
				InstanceModel::getInstance()->addInstanceModel($pModel);
				
				// build serialization
				if (!is_null($lSerializationXml) && isset($lSerializationXml->serialization)) {
					list($lSerializationName, $lSerialization, $lForeignIds) = $this->_buildSerialization($lSerializationXml->serialization);
					$lReturn["serialization"] = $lSerialization;
				}
				
				// build properties to model
				foreach ($lManifest->properties->property as $lProperty) {
					$lName = isset($lProperty->name) ? (string) $lProperty->name : (string) $lProperty;
					$lSerializationNode = isset($lSerializationXml->properties->$lName) ? $lSerializationXml->properties->$lName : null;
					$lReturn["properties"][$lName] = $this->_buildProperty($lProperty, $lSerializationNode, $lName);
					if ($lReturn["properties"][$lName]->isId()) {
						$lReturn["ids"][] = $lName;
					}
				}
			}
		}
		return $lReturn;
	}
	
	private function _buildProperty($pProperty, $pSerializationNode, $pName) {
		$lModel = $this->_buildModel($pProperty);
		$lIsId = (isset($pProperty["id"]) && ((string) $pProperty["id"] == "1")) ? true : false;
		if ($lIsId && !($lModel instanceof SimpleModel)) {
			throw new Exception("id property with name '$pName' must be a simple model");
		}
		if (!is_null($pSerializationNode)) {
			list($lSerializationName, $lSerialization, $lForeignIds) = $this->_buildSerialization($pSerializationNode);
			if (count($lSerialization) > 0) {
				if ($lModel instanceof SimpleModel) {
					throw new Exception("property with name '$pName' can't be a simple model");
				}
				$lProperty = new SerializableProperty($lModel, $pName, $lSerializationName, $lSerialization, $lIsId, $lForeignIds);
			}else {
				$lProperty = new Property($lModel, $pName, $lSerializationName, $lIsId);
			}
		}else {
			$lProperty = new Property($lModel, $pName, null, $lIsId);
		}
		return $lProperty;
	}
	
	private function _buildSerialization($pSerializationNode) {
		$lSerialization = array();
		$lForeignIds = isset($pSerializationNode["foreignIds"]) ? explode(",", (string) $pSerializationNode["foreignIds"]) : null;
		$lSerializationName = isset($pSerializationNode["serializationName"]) ? (string) $pSerializationNode["serializationName"] : null;
		foreach ($pSerializationNode->children() as $lChild) {
			$lType = $lChild->getName();
			$lStringProperties = "";
			$lArrayProperties = array();
			foreach ($lChild->attributes() as $lName => $lValue) {
				$lArrayProperties[$lName] = (string) $lValue;
			}
			ksort($lArrayProperties);
			foreach ($lArrayProperties as $lName => $lValue) {
				$lStringProperties .= $lName.$lValue;
			}
			if (array_key_exists($lKey = md5($lStringProperties), $this->mInstanceSerializations)) {
				$lObject = $this->mInstanceSerializations[$lKey];
			}else {
				$lObject = $this->getInstanceModel($lType)->getObjectInstance();
				$lObject->fromXml($lChild);
				$this->mInstanceSerializations[$lKey] = $lObject;
			}
			$lSerialization[] = $lObject;
		}
		return array($lSerializationName, $lSerialization, $lForeignIds);
	}
	
	private function _buildModel($pProperty) {
		if (((string) $pProperty["type"]) == "array") {
			$lReturn = new ModelArray($this->_buildModel($pProperty->values));
		}
		else if (((string) $pProperty["type"]) == "foreignProperty") {
			$lForeignTypeId = (string) $pProperty->foreignType;
			$lReturn = new ModelForeign($this->_getInstanceModel($lForeignTypeId, false));
		}
		else {
			$lTypeId = (string) $pProperty["type"];
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
	
	private function getDataBaseInformations($pModelName) {
		return array_key_exists($pModelName, $this->mDataBaseProperties) ? $this->mDataBaseProperties[$pModelName] : null;
	}
}