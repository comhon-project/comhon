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
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\ObjectManager;

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
	private $mManifestListFolder;
	private $mSerializationListFolder;
	private $mLocalTypeXml;
	
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
					"properties"     => $pModel->getProperties(), 
					"objectClass"    => $pModel->getObjectCass(), 
					"serializations" => $pModel->getSerializations()
				);
			}else {
				if (is_null($this->mLocalTypeXml)) {
					$lManifestPath = sprintf("%s/%s", $this->mManifestListFolder, $this->mInstanceModels[$pModel->getModelName()][0]);
					$lManifest = simplexml_load_file($lManifestPath);
				}else {
					$lManifest = $this->mLocalTypeXml;
				}
				$lExtends     = isset($lManifest["extends"]) ? (string) $lManifest["extends"] : null; // TODO manage extends
				$lObjectClass = isset($lManifest["object"]) ? (string) $lManifest["object"] : null;
				$lReturn      = array("properties" => array(), "serializations" => null, "objectClass" => $lObjectClass);
				
				$lSerializationXml = null;
				if (is_null($this->mLocalTypeXml) && !is_null($this->mInstanceModels[$pModel->getModelName()][1])) {
					$lSerializationPath = sprintf("%s/%s", $this->mSerializationListFolder, $this->mInstanceModels[$pModel->getModelName()][1]);
					$lSerializationXml = simplexml_load_file($lSerializationPath);
				}
				$this->addInstanceModel($pModel);
				
				if (!is_null($lSerializationXml) && isset($lSerializationXml->serializations)) {
					list($lSerializationName, $lSerializations) = $this->_buildSerializations($lSerializationXml->serializations);
					$lReturn["serializations"] = $lSerializations;
				}
				
				$lReturn["properties"] = array();
				$lLocalTypes = $this->_buildLocalTypes($lManifest);
				$this->_buildProperties($lManifest->properties, $lSerializationXml, $lLocalTypes, $lReturn["properties"]);
				$this->_buildForeignProperties($lManifest->properties, $lSerializationXml, $lLocalTypes, $pModel, $lReturn["properties"]);
			}
		}
		return $lReturn;
	}
	
	private function _getBaseInfosForProperty($pPropertyXml, $pSerializationXml, $pLocalTypes) {
		$lName  = isset($pPropertyXml->name) ? (string) $pPropertyXml->name : (string) $pPropertyXml;
		$lIsId  = (isset($pPropertyXml["id"]) && ((string) $pPropertyXml["id"] == "1")) ? true : false;
		
		if (array_key_exists((string) $pPropertyXml["type"], $pLocalTypes)) {
			$lModel = $pLocalTypes[(string) $pPropertyXml["type"]];
			$lSerializationsNode = null;
		}else {
			$lModel = $this->_buildModel($pPropertyXml, $pLocalTypes);
			$lSerializationsNode = isset($pSerializationXml->properties->$lName) ? $pSerializationXml->properties->$lName : null;
		}
			
		if ($lIsId && !($lModel instanceof SimpleModel)) {
			throw new Exception("id property with name '$lName' must be a simple model");
		}
		return array($lName, $lModel, $lSerializationsNode, $lIsId);
	}
	
	private function _buildProperties($pPropertiesXml, $pSerializationXml, $pLocalTypes, &$pProperties) {
		foreach ($pPropertiesXml->property as $lPropertyXml) {
			list($lName, $lModel, $lSerializationsNode, $lIsId) = $this->_getBaseInfosForProperty($lPropertyXml, $pSerializationXml, $pLocalTypes);
			$lSerializationName = (!is_null($lSerializationsNode) && isset($lSerializationsNode["serializationName"])) ? (string) $lSerializationsNode["serializationName"] : null;
			$lProperty = new Property($lModel, $lName, $lSerializationName, $lIsId);
			$pProperties[$lName] = $lProperty;
		}
	}
	
	private function _buildForeignProperties($pPropertiesXml, $pSerializationXml, $pLocalTypes, $pParentModel, &$pProperties) {
		foreach ($pPropertiesXml->foreignProperty as $lPropertyXml) {
			list($lName, $lModel, $lSerializationsNode, $lIsId) = $this->_getBaseInfosForProperty($lPropertyXml, $pSerializationXml, $pLocalTypes);
			if ($lModel instanceof SimpleModel) {
				throw new Exception("foreign property with name '$lName' can't be a simple model");
			}
			$lModelForeign = new ModelForeign($lModel);
			if (!is_null($lSerializationsNode)) {
				list($lSerializationName, $lSerializations) = $this->_buildSerializations($lSerializationsNode);
				$lProperty = new ForeignProperty($lModelForeign, $lName, $lSerializationName, $lSerializations);
			}else {
				$lProperty = new ForeignProperty($lModelForeign, $lName);
			}
			$pProperties[$lName] = $lProperty;
		}
	}
	
	private function _buildLocalTypes($pManifestXML) {
		$lLocalTypes = array();
		if (isset($pManifestXML->types) && is_null($this->mLocalTypeXml)) {
			foreach ($pManifestXML->types->type as $lLocalType) {
				$this->mLocalTypeXml = $lLocalType;
				$lTypeId = (string) $lLocalType["id"];
				$lLocalTypes[$lTypeId] = new Model($lTypeId, true);
			}
			$this->mLocalTypeXml = null;
		}
		return $lLocalTypes;
	}
	
	private function _buildSerializations($pSerializationsNode) {
		$lSerializations = array();
		$lSerializationName = isset($pSerializationsNode["serializationName"]) ? (string) $pSerializationsNode["serializationName"] : null;
		foreach ($pSerializationsNode->serialization as $lSerializationNode) {
			$lType = (string) $lSerializationNode["type"];
			if (isset($lSerializationNode->$lType)) {
				$lObjectXml = $lSerializationNode->$lType;
				$lObject = $this->getInstanceModel($lType)->getObjectInstance();
				$lObject->fromXml($lObjectXml);
			} else {
				$lId = (string) $lSerializationNode;
				if (!array_key_exists($lType, $this->mInstanceSerializations)) {
					$this->mInstanceSerializations[$lType] = array();
				}
				if (array_key_exists($lId, $this->mInstanceSerializations[$lType])) {
					$lObject = $this->mInstanceSerializations[$lType][$lId];
				} else {
					$lObject = $this->_getSerializationObject($lType, $lId);
				}
			}
			$lSerializations[] = $lObject;
		}
		return array($lSerializationName, $lSerializations);
	}
	
	private function _getSerializationObject($pType, $pId) {
		if (array_key_exists($pId, $this->mInstanceSerializations[$pType])) {
			$lObject = $this->mInstanceSerializations[$pType][$pId];
		} else {
			$lObject = ObjectManager::getObject($pType, $pId);
			$this->mInstanceSerializations[$pType][$pId] = $lObject;
		}
		return $lObject;
	}
	
	private function _buildModel($pProperty, $pLocalTypes) {
		$lTypeId = (string) $pProperty["type"];
		if ($lTypeId == "array") {
			$lReturn = new ModelArray($this->_buildModel($pProperty->values, $pLocalTypes));
		}
		else if (array_key_exists($lTypeId, $pLocalTypes)) {
			$lReturn = $pLocalTypes[$lTypeId];
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