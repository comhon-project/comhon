<?php
namespace objectManagerLib\database;

class HavingLiteral extends Literal {

	private $mFunction;
	private $mModelNameForJoin;

	const COUNT = "COUNT";
	const SUM   = "SUM";
	const AVG   = "AVG";
	const MIN   = "MIN";
	const MAX   = "MAX";
	
	protected static $sAcceptedFunctions = array(
			self::COUNT => null,
			self::SUM   => null,
			self::AVG   => null,
			self::MIN   => null,
			self::MAX   => null
	);
	
	public function __construct($pFunction, $pTable, $pPropertyName, $pOperator, $pValue, $pModelName = null, $pModelNameForJoin = null) {
		$this->mFunction = $pFunction;
		$this->mModelNameForJoin = $pModelNameForJoin;
		parent::__construct($pTable, $pPropertyName, $pOperator, $pValue, $pModelName);
	}
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->mOperator, self::$sAcceptedOperators)) {
			throw new \Exception("operator '".$this->mOperator."' doesn't exists");
		}
		if (!array_key_exists($this->mFunction, self::$sAcceptedFunctions)) {
			throw new \Exception("function '".$this->mFunction."' doesn't exists");
		}
		if (!is_int($this->mValue)) {
			throw new \Exception("having literal must have an integer value");
		}
	}
	
	public function hasModelNameForJoin() {
		return !is_null($this->mModelNameForJoin);
	}
	
	public function getModelNameForJoin() {
		return $this->mModelNameForJoin;
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		return sprintf("%s(%s.%s) %s %s", $this->mFunction, $this->mTable, $this->mPropertyName, $this->mOperator, $this->mValue);
	}
}