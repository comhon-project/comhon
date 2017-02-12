<?php
namespace comhon\object;

use comhon\model\property\ForeignProperty;
use comhon\model\MainModel;
use comhon\model\Model;

class ObjectArray extends Object {

	const __UNLOAD__ = '__UNLOAD__';
	
	/**
	 *
	 * @param string $pName
	 * @return boolean true if loading is successfull (loading can fail if object is not serialized)
	 */
	public function loadValue($pkey) {
		return $this->getModel()->getUniqueModel()->loadAndFillObject($this->getValue($pkey));
	}
	
	public function getId() {
		return null;
	}
	
	public final function setValues($pValues) {
		$this->_setValues($pValues);
	}
	
	public final function pushValue($pValue, $pStrict = true) {
		if ($pStrict && !is_null($pValue)) {
			$this->getModel()->getModel()->verifValue($pValue);
		}
		$this->_pushValue($pValue);
	}
	
	public function fromSqlDatabaseId($pRows, $pTimeZone = null, $pUpdateLoadStatus = true) {
		if (!($this->getModel()->getModel() instanceof MainModel)) {
			throw new \Exception('can\'t apply function. Only callable for array with MainModel');
		}
		$this->resetValues();
		foreach ($pRows as $lRow) {
			$this->pushValue($this->getModel()->getModel()->fromSqlDatabaseId($lRow));
		}
		if ($pUpdateLoadStatus) {
			$this->setLoadStatus();
		}
	}
	
}