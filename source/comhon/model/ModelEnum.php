<?php
namespace comhon\model;
use \Exception;
use comhon\interfacer\Interfacer;
use comhon\object\collection\ObjectCollection;

class ModelEnum extends ModelContainer {

	private $mEnum;
	
	public final function __construct($pModel, $pEnum) {
		if (!($pModel instanceof SimpleModel)) {
			throw new Exception('model parameter must be an instanceof SimpleModel');
		}
		if (!is_array($pEnum)) {
			throw new Exception('enum parameter must be an array');
		}
		$this->mModel    = $pModel;
		$this->mEnum     = $pEnum;
	}
	
	public function isComplex() {
		return false;
	}
	
	public function getEnum() {
		return $this->mEnum;
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
	protected function _export($pValue, $pNodeName, Interfacer $pInterfacer, $pIsFirstLevel) {
		$lValue = $this->mModel->_export($pValue, $pNodeName, $pInterfacer, $pIsFirstLevel);
		if (!in_array($lValue, $this->mEnum)) {
			throw new \Exception($lValue. 'is not allowed for enum ' . json_encode($this->mEnum));
		}
		return $lValue;
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
	protected function _import($pValue, Interfacer $pInterfacer, ObjectCollection $pLocalObjectCollection, MainModel $pParentMainModel, $pIsFirstLevel = false) {
		$lValue = $this->mModel->_import($pValue, $pInterfacer, $pLocalObjectCollection, $pParentMainModel, $pIsFirstLevel);
		if (!in_array($lValue, $this->mEnum)) {
			throw new \Exception($lValue. 'is not allowed for enum ' . json_encode($this->mEnum));
		}
		return $lValue;
	}
	
	public function verifValue($pValue) {
		$this->mModel->verifValue($pValue);
		if (!in_array($pValue, $this->mEnum)) {
			$lNodes = debug_backtrace();
			$lClass = gettype($pValue) == 'object' ? get_class($pValue): gettype($pValue);
			throw new \Exception("Argument passed to {$lNodes[0]['class']}::{$lNodes[0]['function']}() must be in enumeration ".json_encode($this->mEnum).", instance of $lClass given, called in {$lNodes[0]['file']} on line {$lNodes[0]['line']} and defined in {$lNodes[0]['file']}");
		}
		return true;
	}
	
}