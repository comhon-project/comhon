<?php
namespace objectManagerLib\database;

class HavingLiteral extends Literal {

	private $mFunction;

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
	
	public function __construct($pFunction, $pTable, $pColumn, $pOperator, $pValue) {
		$this->mFunction = $pFunction;
		parent::__construct($pTable, $pColumn, $pOperator, $pValue);
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
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		return sprintf("%s(%s.%s) %s %s", $this->mFunction, $this->mTable, $this->mColumn, $this->mOperator, $this->mValue);
	}
	
	private static function _verifPhpObject($pPhpObject) {
		if (!is_object($pPhpObject) || !isset($pPhpObject->function) || !isset($pPhpObject->node) || !isset($pPhpObject->column) || !isset($pPhpObject->operator) ||!isset($pPhpObject->value)) {
			throw new \Exception("malformed phpObject literal : ".json_encode($pPhpObject));
		}
	}
	
	/**
	 * @param stdClass $pPhpObject
	 * @param Tree $pJoinTree
	 * @throws \Exception
	 * @return Literal
	 */
	public static function phpObjectToLiteral($pPhpObject, $pJoinTree = null) {
		self::_verifPhpObject($pPhpObject);
		$lLiteral  = new HavingLiteral($pPhpObject->function, $pPhpObject->node, $pPhpObject->column, $pPhpObject->operator, $pPhpObject->value);
		return $lLiteral;
	}
	
}