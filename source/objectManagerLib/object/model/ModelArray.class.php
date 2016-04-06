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
	
	public function toObject($pObjectArray, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (is_null($pObjectArray)) {
			return null;
		}
		if (!$pObjectArray->isLoaded()) {
			return  ObjectArray::__UNLOAD__;
		}
		$lReturn = array();
		foreach ($pObjectArray->getValues() as $lKey => $lValue) {
			$lReturn[$lKey] = $this->getModel()->toObject($lValue, $pUseSerializationName, $pMainForeignObjects);
		}
		return $lReturn;
	}
	
	public function toObjectId($pObjectArray, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (is_null($pObjectArray)) {
			return null;
		}
		if (!$pObjectArray->isLoaded()) {
			return  ObjectArray::__UNLOAD__;
		}
		$lReturn = array();
		if (!is_null($pObjectArray)) {
			foreach ($pObjectArray->getValues() as $lKey => $lValue) {
				$lReturn[$lKey] = $this->getModel()->toObjectId($lValue, $pUseSerializationName, $pMainForeignObjects);
			}
		}
		return $lReturn;
	}
	
	public function fromObject($pArray) {
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		return $this->_fromObject($pArray, null);
	}
	
	protected function _fromObject($pArray, $pMainObjectId) {
		$lObjectArray = $this->getObjectInstance();
		foreach ($pArray as $lKey => $lPhpValue) {
			$lObjectArray->setValue($lKey, $this->getModel()->_fromObject($lPhpValue, $pMainObjectId));
		}
		return $lObjectArray;
	}
	
	protected function _fromObjectId($pArray, $pMainObjectId) {
		if (is_null($pArray)) {
			return null;
		}
		if (is_string($pArray) && $pArray == ObjectArray::__UNLOAD__) {
			return $this->getObjectInstance(false);
		}
		$lReturn = $this->getObjectInstance();
		foreach ($pArray as $lKey => $lValue) {
			$lReturn->setValue($lKey, $this->getModel()->_fromObjectId($lValue, $pMainObjectId));
		}
		return $lReturn;
	}
	
	protected function _fromSqlColumn($pJsonEncodedObject) {
		if (is_null($pJsonEncodedObject)) {
			return null;
		}
		$lPhpObject = json_decode($pJsonEncodedObject);
		$lObject    = $this->getObjectInstance();
		foreach ($lPhpObject as $lKey => $lPhpValue) {
			$lObject->setValue($lKey, $this->getModel()->_fromObject($lPhpValue));
		}
		return $lObject;
	}
	
	public function fromSqlDataBase($pRows, $pAddUnloadValues = true) {
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		return $this->_fromSqlDataBase($pRows, $pAddUnloadValues);
	}
	
	protected function _fromSqlDataBase($pRows, $pAddUnloadValues = true) {
		$lObjectArray = $this->getObjectInstance();
		foreach ($pRows as $lKey => $lRow) {
			$lObjectArray->setValue($lKey, $this->getModel()->_fromSqlDataBase($lRow, $pAddUnloadValues));
		}
		return $lObjectArray;
	}
	
	public function toXml($pObjectArray, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (!is_null($pObjectArray)) {
			if (!$pObjectArray->isLoaded()) {
				$pXmlNode[ObjectArray::__UNLOAD__] = "1";
			}
			else {
				foreach ($pObjectArray->getValues() as $lKey => $lValue) {
					$lXmlChildNode = $pXmlNode->addChild($this->mElementName);
					$this->getModel()->toXml($lValue, $lXmlChildNode, $pUseSerializationName, $pMainForeignObjects);
				}
			}
		}
	}
	
	public function toXmlId($pObjectArray, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (!is_null($pObjectArray)) {
			if (!$pObjectArray->isLoaded()) {
				$pXmlNode[ObjectArray::__UNLOAD__] = "1";
			}
			else {
				foreach ($pObjectArray->getValues() as $lKey => $lValue) {
					$lXmlChildNode = $pXmlNode->addChild($this->mElementName);
					$this->getModel()->toXmlId($lValue, $lXmlChildNode, $pUseSerializationName, $pMainForeignObjects);
				}
			}
		}
	}
	
	public function fromXml($pArray) {
		if (!($this->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		return $this->_fromXml($pArray, null);
	}
	
	protected function _fromXml($pXml, $pMainObjectId) {
		$lObjectArray = $this->getObjectInstance();
		foreach ($pXml->{$this->mElementName} as $lChild) {
			$lObjectArray->pushValue($this->getModel()->_fromXml($lChild, $pMainObjectId));
		}
		return $lObjectArray;
	}
	
	protected function _fromXmlId($pXml, $pMainObjectId) {
		if (isset($pXml[ObjectArray::__UNLOAD__]) && ((string) $pXml[ObjectArray::__UNLOAD__] == "1")) {
			$lValue = $this->getModel()->getObjectInstance(false);
		} else {
			$lValue = $this->getObjectInstance();
			foreach ($pXml->{$this->mElementName} as $lChild) {
				$lValue->pushValue($this->getModel()->_fromXmlId($lChild, $pMainObjectId));
			}
		}
		return $lValue;
	}

	protected function _fromSqlId($pValue) {
		return $this->_fromObjectId(json_decode($pValue));
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
}