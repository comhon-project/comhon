<?php
namespace objectManagerLib\object\model;

use objectManagerLib\object\object\ObjectArray;

class ModelForeign extends ModelContainer {

	public function toObject($pValue, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return null;
		}
		if ($this->getUniqueModel()->hasIdProperty()) {
			return $this->getModel()->toObjectId($pValue, $pUseSerializationName, $pMainForeignObjects);
		} else {
			if (!($this->getUniqueModel() instanceof MainModel)) {
				throw new \Exception('foreign property with local model must have id');
			}
			return $this->getModel()->toObject($pValue, $pUseSerializationName, $pMainForeignObjects);
		}
	}
	
	protected function _fromObject($pValue, $pLocalObjectCollection) {
		if (is_null($pValue)) {
			return null;
		}
		if ($this->getUniqueModel()->hasIdProperty()) {
			return $this->getModel()->_fromObjectId($pValue, $pLocalObjectCollection);
		} else {
			if (!($this->getUniqueModel() instanceof MainModel)) {
				throw new \Exception('foreign property with local model must have id');
			}
			return $this->getModel()->_fromObject($pValue, $pLocalObjectCollection);
		}
	}
	
	public function toXml($pValue, $pXmlNode, $pUseSerializationName = false, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return;
		}
		if ($this->getUniqueModel()->hasIdProperty()) {
			$this->getModel()->toXmlId($pValue, $pXmlNode, $pUseSerializationName, $pMainForeignObjects);
		} else {
			if (!($this->getUniqueModel() instanceof MainModel)) {
				throw new \Exception('foreign property with local model must have id');
			}
			$this->getModel()->toXml($pValue, $pXmlNode, $pUseSerializationName, $pMainForeignObjects);
		}
	}
	
	protected function _fromXml($pValue, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperty()) {
			return $this->getModel()->_fromXmlId($pValue, $pLocalObjectCollection);
		} else {
			if (!($this->getUniqueModel() instanceof MainModel)) {
				throw new \Exception('foreign property with local model must have id');
			}
			return $this->getModel()->_fromXml($pValue, $pLocalObjectCollection);
		}
	}
	
	protected function _fromSqlColumn($pValue, $pLocalObjectCollection) {
		return $this->getModel()->_fromSqlColumnId($pValue, $pLocalObjectCollection);
	}
	
}