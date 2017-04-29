<?php
namespace comhon\model;

use comhon\object\Object;
use comhon\interfacer\Interfacer;
use comhon\object\collection\ObjectCollection;

class ModelForeign extends ModelContainer {

	public function __construct($pModel) {
		parent::__construct($pModel);
		if ($this->mModel instanceof SimpleModel) {
			throw new Exception('model of foreign model can\'t be a simple model');
		}
	}
	
	public function getObjectClass() {
		return $this->getModel()->getObjectClass();
	}
	
	public function getObjectInstance($pIsloaded = true) {
		return $this->getModel()->getObjectInstance($pIsloaded);
	}
	
	/**
	 *
	 * @param Object $pObject
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @param boolean $pIsFirstLevel
	 * @throws \Exception
	 * @return mixed|null
	 */
	protected function _export($pObject, $pNodeName, Interfacer $pInterfacer, $pIsFirstLevel) {
		if (is_null($pObject)) {
			return null;
		}
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new \Exception('foreign property with local model must have id');
		}
		return $this->getModel()->_exportId($pObject, $pNodeName, $pInterfacer);
	}
	
	/**
	 *
	 * @param ComhonDateTime $pValue
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param boolean $pIsFirstLevel
	 * @return NULL|unknown
	 */
	protected function _import($pValue, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection = null, $pIsFirstLevel = false) {
		if (!$this->getUniqueModel()->hasIdProperties()) {
			throw new \Exception("foreign property must have model with id ({$this->getName()})");
		}
		return $this->getModel()->_importId($pValue, $pInterfacer, $pLocalObjectCollection, $pIsFirstLevel);
	}
	
	protected function _toStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return null;
		}
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_toStdObjectId($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
		} else {
			throw new \Exception('foreign property with local model must have id');
		}
	}
	
	protected function _fromStdObject($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_fromStdObjectId($pValue, $pFlagAsUpdated, $pLocalObjectCollection);
		} else {
			throw new \Exception("foreign property must have model with id ({$this->getName()})");
		}
	}
	
	protected function _toXml($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return;
		}
		if ($this->getUniqueModel()->hasIdProperties()) {
			$this->getModel()->_toXmlId($pValue, $pXmlNode, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
		} else {
			throw new \Exception('foreign property with local model must have id');
		}
	}
	
	protected function _fromXml($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_fromXmlId($pValue, $pFlagAsUpdated, $pLocalObjectCollection);
		} else {
			throw new \Exception('foreign property must have id');
		}
	}
	
	protected function _toFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, &$pMainForeignObjects = null) {
		if (is_null($pValue)) {
			return null;
		}
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_toFlattenedValueId($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pUpdatedValueOnly, $pOriginalUpdatedValueOnly, $pMainForeignObjects);
		} else {
			throw new \Exception('foreign property with local model must have id');
		}
	}
	
	protected function _fromFlattenedValue($pValue, $pPrivate, $pUseSerializationName, $pDateTimeZone, $pFlagAsUpdated, $pLocalObjectCollection) {
		if ($this->getUniqueModel()->hasIdProperties()) {
			return $this->getModel()->_fromFlattenedValueId($pValue, $pFlagAsUpdated, $pLocalObjectCollection);
		} else {
			throw new \Exception('foreign property must have id');
		}
	}
	
	public function verifValue($pValue) {
		$this->mModel->verifValue($pValue);
		return true;
	}
}