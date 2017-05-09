<?php
namespace comhon\model;

use comhon\model\singleton\ModelManager;
use comhon\interfacer\Interfacer;
use comhon\interfacer\NoScalarTypedInterfacer;
use comhon\object\collection\ObjectCollection;

abstract class SimpleModel extends Model {
	
	/**
	 * don't instanciate a model by yourself because it take time
	 * to get a model instance use singleton ModelManager
	 */
	public final function __construct() {
		$this->mIsLoaded = true;
		$this->_init();
	}
	
	public function isComplex() {
		return false;
	}
	
	public function getObjectClass() {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	public function getObjectInstance($pIsloaded = true) {
		throw new \Exception('simple models don\'t have associated class');
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param string $pNodeName
	 * @param Interfacer $pInterfacer
	 * @param boolean $pIsFirstLevel
	 * @throws \Exception
	 * @return mixed|null
	 */
	final protected function _export($pValue, $pNodeName, Interfacer $pInterfacer, $pIsFirstLevel) {
		return $this->exportSimple($pValue, $pInterfacer);
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return mixed|null
	 */
	public function exportSimple($pValue, Interfacer $pInterfacer) {
		return $pValue;
	}
	
	/**
	 *
	 * @param ComhonDateTime $pValue
	 * @param Interfacer $pInterfacer
	 * @param ObjectCollection $pLocalObjectCollection
	 * @param MainModel $pParentMainModel
	 * @param boolean $pIsFirstLevel
	 * @return NULL|unknown
	 */
	final protected function _import($pValue, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection, MainModel $pParentMainModel, $pIsFirstLevel = false) {
		return $this->importSimple($pValue, $pInterfacer);
	}
	
	/**
	 *
	 * @param mixed $pValue
	 * @param Interfacer $pInterfacer
	 * @return string|null
	 */
	public function importSimple($pValue, Interfacer $pInterfacer) {
		if (is_null($pValue)) {
			return $pValue;
		}
		if ($pInterfacer instanceof NoScalarTypedInterfacer) {
			$pValue = $pInterfacer->castValueToString($pValue);
		}
		return $pValue;
	}
	
	public function verifValue($pValue) {
		throw new \Exception('should be overrided');
	}
	

	public abstract function  isCheckedValueType($pValue);
	public abstract function castValue($pValue);
	
}