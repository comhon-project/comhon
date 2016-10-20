<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\ObjectArray;
use objectManagerLib\object\object\Object;

class ModelForeign extends ModelContainer {

	public function __construct($pModel) {
		parent::__construct($pModel);
		if ($this->mModel instanceof SimpleModel) {
			throw new Exception("model of foreign model can't be a simple model");
		}
	}
	
	protected function _toObject($pValue, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return null;
		}
		if ($this->getUniqueModel()->hasIdProperty()) {
			return $this->getModel()->_toObjectId($pValue, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		} else {
			if (!($this->getUniqueModel() instanceof MainModel)) {
				throw new \Exception('foreign property with local model must have id');
			}
			return $this->getModel()->_toObject($pValue, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		}
	}
	
	protected function _fromObject($pValue, $pDateTimeZone, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperty()) {
			return $this->getModel()->_fromObjectId($pValue, $pLocalObjectCollection);
		} else {
			throw new \Exception('foreign property must have id');
		}
	}
	
	protected function _toXml($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return;
		}
		if ($this->getUniqueModel()->hasIdProperty()) {
			$this->getModel()->_toXmlId($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		} else {
			if (!($this->getUniqueModel() instanceof MainModel)) {
				throw new \Exception('foreign property with local model must have id');
			}
			$this->getModel()->_toXml($pValue, $pXmlNode, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		}
	}
	
	protected function _fromXml($pValue, $pDateTimeZone, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperty()) {
			return $this->getModel()->_fromXmlId($pValue, $pLocalObjectCollection);
		} else {
			throw new \Exception('foreign property must have id');
		}
	}
	
	protected function _fromSqlColumn($pValue, $pDateTimeZone, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperty()) {
			return $this->getModel()->_fromSqlColumnId($pValue, $pLocalObjectCollection);
		} else {
			throw new \Exception('foreign property must have id');
		}
	}
	
	public function verifValue(Object $pValue) {
		$this->mModel->verifValue($pValue);
	}
}