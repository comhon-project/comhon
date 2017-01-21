<?php
namespace comhon\object\model;

use comhon\object\object\ObjectArray;
use comhon\object\object\Object;

class ModelForeign extends ModelContainer {

	public function __construct($pModel) {
		parent::__construct($pModel);
		if ($this->mModel instanceof SimpleModel) {
			throw new Exception("model of foreign model can't be a simple model");
		}
	}
	
	protected function _toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return null;
		}
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_toStdObjectId($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		} else {
			throw new \Exception('foreign property with local model must have id');
		}
	}
	
	protected function _fromStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_fromStdObjectId($pValue, $pLocalObjectCollection);
		} else {
			throw new \Exception("foreign property must have model with id ({$this->getModelName()})");
		}
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return;
		}
		if ($this->getUniqueModel()->hasIdProperties()) {
			$this->getModel()->_toXmlId($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		} else {
			throw new \Exception('foreign property with local model must have id');
		}
	}
	
	protected function _fromXml($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_fromXmlId($pValue, $pLocalObjectCollection);
		} else {
			throw new \Exception('foreign property must have id');
		}
	}
	
	protected function _toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return null;
		}
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_toFlattenedValueId($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pMainForeignObjects);
		} else {
			throw new \Exception('foreign property with local model must have id');
		}
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_fromFlattenedValueId($pValue, $pLocalObjectCollection);
		} else {
			throw new \Exception('foreign property must have id');
		}
	}
	
	public function verifValue(Object $pValue) {
		$this->mModel->verifValue($pValue);
	}
}