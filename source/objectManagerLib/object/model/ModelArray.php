<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\model\MainModel;

class ModelArray extends ModelContainer {
	
	/**
	 * name of each element
	 * for exemple if we have a ModelArray 'children', each element name would be 'child'
	 * @var string
	 */
	private $mElementName;
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton InstanceModel
	 */
	public function __construct($pModel, $pElementName) {
		parent::__construct($pModel);
		$this->mElementName = $pElementName;
	}
	
	public function getElementName() {
		return $this->mElementName;
	}
	
	public function getObjectInstance($pIsloaded = true) {
		return new ObjectArray($this, $pIsloaded);
	}
	
	protected function _toObject($pObjectArray, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pObjectArray)) {
			return null;
		}
		if (!$pObjectArray->isLoaded()) {
			return  ObjectArray::__UNLOAD__;
		}
		$lReturn = array();
		foreach ($pObjectArray->getValues() as $lKey => $lValue) {
			$lReturn[$lKey] = $this->getModel()->_toObject($lValue, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		}
		return $lReturn;
	}
	
	protected function _toObjectId($pObjectArray, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pObjectArray)) {
			return null;
		}
		if (!$pObjectArray->isLoaded()) {
			return  ObjectArray::__UNLOAD__;
		}
		$lReturn = array();
		if (!is_null($pObjectArray)) {
			foreach ($pObjectArray->getValues() as $lKey => $lValue) {
				$lReturn[$lKey] = $this->getModel()->_toObjectId($lValue, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
			}
		}
		return $lReturn;
	}
	
	public function fromObject($pArray, $pMergeType = self::MERGE, $pTimeZone = null) {
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pArray as $lKey => $lPhpValue) {
			$lObjectArray->setValue($lKey, $this->getModel()->fromObject($lPhpValue, $pMergeType, $pTimeZone));
		}
		return $lObjectArray;
	}
	
	protected function _fromObject($pArray, $pDateTimeZone, $pLocalObjectCollection) {
		if (is_string($pArray) && $pArray == ObjectArray::__UNLOAD__) {
			return $this->getObjectInstance(false);
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pArray as $lKey => $lPhpValue) {
			$lObjectArray->setValue($lKey, $this->getModel()->_fromObject($lPhpValue, $pDateTimeZone, $pLocalObjectCollection));
		}
		return $lObjectArray;
	}
	
	protected function _fromObjectId($pArray, $pLocalObjectCollection) {
		if (is_null($pArray)) {
			return null;
		}
		if (is_string($pArray) && $pArray == ObjectArray::__UNLOAD__) {
			return $this->getObjectInstance(false);
		}
		$lReturn = $this->getObjectInstance();
		foreach ($pArray as $lKey => $lValue) {
			$lReturn->setValue($lKey, $this->getModel()->_fromObjectId($lValue, $pLocalObjectCollection));
		}
		return $lReturn;
	}
	
	protected function _fromSqlColumn($pJsonEncodedObject, $pDateTimeZone, $pLocalObjectCollection) {
		if (is_null($pJsonEncodedObject)) {
			return null;
		}
		$lPhpObject = json_decode($pJsonEncodedObject);
		return $this->_fromObject($lPhpObject, $pDateTimeZone, $pLocalObjectCollection);
	}
	
	public function fromSqlDataBase($pRows, $pMergeType = self::MERGE, $pTimeZone = null, $pAddUnloadValues = true) {
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pRows as $lKey => $lRow) {
			$lObjectArray->setValue($lKey, $this->getModel()->fromSqlDataBase($lRow, $pMergeType, $pTimeZone, $pAddUnloadValues));
		}
		return $lObjectArray;
	}
	
	protected function _fromSqlDataBase($pRows, $pDateTimeZone, $pLocalObjectCollection, $pAddUnloadValues = true) {
		$lObjectArray = $this->getObjectInstance();
		foreach ($pRows as $lKey => $lRow) {
			$lObjectArray->setValue($lKey, $this->getModel()->_fromSqlDataBase($lRow, $pDateTimeZone, $pLocalObjectCollection, $pAddUnloadValues));
		}
		return $lObjectArray;
	}
	
	protected function _toXml($pObjectArray, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (!is_null($pObjectArray)) {
			if (!$pObjectArray->isLoaded()) {
				$pXmlNode[ObjectArray::__UNLOAD__] = "1";
			}
			else {
				foreach ($pObjectArray->getValues() as $lKey => $lValue) {
					$lXmlChildNode = $pXmlNode->addChild($this->mElementName);
					$this->getModel()->_toXml($lValue, $lXmlChildNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				}
			}
		}
	}
	
	protected function _toXmlId($pObjectArray, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (!is_null($pObjectArray)) {
			if (!$pObjectArray->isLoaded()) {
				$pXmlNode[ObjectArray::__UNLOAD__] = "1";
			}
			else {
				foreach ($pObjectArray->getValues() as $lKey => $lValue) {
					$lXmlChildNode = $pXmlNode->addChild($this->mElementName);
					$this->getModel()->_toXmlId($lValue, $lXmlChildNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
				}
			}
		}
	}
	
	public function fromXml($pXml, $pMergeType = self::MERGE, $pTimeZone = null) {
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$lObjectArray = $this->getObjectInstance();
		foreach ($pXml->{$this->mElementName} as $lChild) {
			$lObjectArray->pushValue($this->getModel()->fromXml($lChild, $pMergeType, $pTimeZone));
		}
		return $lObjectArray;
	}
	
	protected function _fromXml($pXml, $pDateTimeZone, $pLocalObjectCollection) {
		if (isset($pXml[ObjectArray::__UNLOAD__]) && ((string) $pXml[ObjectArray::__UNLOAD__] == "1")) {
			$lObjectArray = $this->getObjectInstance(false);
		} else {
			$lObjectArray = $this->getObjectInstance();
			foreach ($pXml->{$this->mElementName} as $lChild) {
				$lObjectArray->pushValue($this->getModel()->_fromXml($lChild, $pDateTimeZone, $pLocalObjectCollection));
			}
		}
		return $lObjectArray;
	}
	
	protected function _fromXmlId($pXml, $pLocalObjectCollection) {
		if (isset($pXml[ObjectArray::__UNLOAD__]) && ((string) $pXml[ObjectArray::__UNLOAD__] == "1")) {
			$lValue = $this->getObjectInstance(false);
		} else {
			$lValue = $this->getObjectInstance();
			foreach ($pXml->{$this->mElementName} as $lChild) {
				$lValue->pushValue($this->getModel()->_fromXmlId($lChild, $pLocalObjectCollection));
			}
		}
		return $lValue;
	}

	protected function _fromSqlColumnId($pValue, $pLocalObjectCollection) {
		return $this->_fromObjectId(json_decode($pValue), $pLocalObjectCollection);
	}
	
	/*
	 * return true if $pArray1 and $pArray2 are equals
	 */
	public function isEqual($pArray1, $pArray2) {
		if (count($pArray1) != count($pArray2)) {
			return false;
		}
		foreach ($pArray1 as $lkey => $lValue1) {
			if (array_key_exists($lkey, $pArray2)) {
				$lValue2 = $pArray2[$lkey];
				if (!$lValue1->getModel()->isEqual($lValue1, $lValue2)) {
					return false;
				}
			}else {
				return false;
			}
		}
		return true;
	}
	
	public function verifValue(ObjectArray $pValue) {}
	
}