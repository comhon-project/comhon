<?php
namespace objectManagerLib\database;

/**
 * logical junction is actually a disjunction or a conjunction
 * - a disjunction is true if at least one of elements of this disjonction is true
 * - a conjunction is true if all elements of this conjunction are true
 */
class LogicalJunction {

	const DISJUNCTION = 'disjunction';
	const CONJUNCTION = 'conjunction';
	
	protected $mType;
	protected $mLiterals = array();
	protected $mLogicalJunction = array();
	
	private static $sAcceptedTypes = array(
		self::DISJUNCTION => 'or',
		self::CONJUNCTION => 'and'
	);
	
	/**
	 * 
	 * @param string $pType can be self::CONJUNCTION or self::DISJUNCTION
	 */
	public function __construct($pType) {
		if (!array_key_exists($pType, self::$sAcceptedTypes)) {
			throw new \Exception("type '$pType' doesn't exists");
		}
		$this->mType = $pType;
	}
	
	public function getType() {
		return $this->mType;
	}
	
	public function getOperator() {
		return self::$sAcceptedTypes[$this->mType];
	}
	
	/**
	 * @param Literal $pLiteral
	 */
	public function addLiteral(Literal $pLiteral) {
		$this->mLiterals[] = $pLiteral;
	}
	
	/**
	 * @param LogicalJunction $pLogicalJunction
	 */
	public function addLogicalJunction(LogicalJunction $pLogicalJunction) {
		$this->mLogicalJunction[] = $pLogicalJunction;
	}
	
	/**
	 * @param array $pLiterals
	 */
	public function setLiterals($pLiterals) {
		$this->mLiterals = $pLiterals;
	}
	
	/**
	 * @param array $pLogicalJunction
	 */
	public function setLogicalJunction($pLogicalJunction) {
		$this->mLogicalJunction = $pLogicalJunction;
	}
	
	/**
	 * @param string $pKeyType can be "index" or "md5"
	 * @return array:
	 */
	public function getLiterals($pKeyType = "index") {
		$lReturn = $this->mLiterals;
		if ($pKeyType == "md5") {
			$lReturn = array();
			foreach ($this->mLiterals as $lLiteral) {
				$lReturn[md5($lLiteral->exportWithValue())] = $lLiteral;
			}
		}
		return $lReturn;
	}
	
	public function getLogicalJunction() {
		return $this->mLogicalJunction;
	}
	
	/**
	 * 
	 * @param string $pKeyType can be "index" or "md5"
	 * @return array
	 */
	public function getFlattenedLiterals($pKeyType = "index") {
		$lLiterals = array();
		$this->getFlattenedLiteralsWithRefParam($lLiterals, $pKeyType);
		return $lLiterals;
	}
	
	/**
	 * don't call this function, call getFlattenedLiterals
	 * @param array $pLiterals
	 * @param array $pKeyType
	 */
	public function getFlattenedLiteralsWithRefParam(&$pLiterals, $pKeyType) {
		foreach ($this->mLiterals as $lLiteral) {
			switch ($pKeyType) {
				case "md5":
					$pLiterals[md5($lLiteral->exportWithValue())] = $lLiteral;
					break;
				default:
					$pLiterals[] = $lLiteral;
					break;
			}
		}
		foreach ($this->mLogicalJunction as $lLogicalJunction) {
			$lLogicalJunction->getFlattenedLiteralsWithRefParam($pLiterals, $pKeyType);
		}
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		$lArray = array();
		foreach ($this->mLiterals as $lLiteral) {
			$lArray[] = $lLiteral->export($pValues);
		}
		foreach ($this->mLogicalJunction as $lLogicalJunction) {
			$lResult = $lLogicalJunction->export($pValues);
			if ($lResult != "") {
				$lArray[] = $lResult;
			}
		}
		return (!empty($lArray)) ? "(".implode(" ".$this->getOperator()." ", $lArray).")" : "";
	}
	
	/**
	 * @return string
	 */
	public function exportDebug() {
		$lArray = array();
		foreach ($this->mLiterals as $lLiteral) {
			$lArray[] = $lLiteral->exportWithValue();
		}
		foreach ($this->mLogicalJunction as $lLogicalJunction) {
			$lResult = $lLogicalJunction->exportDebug();
			if ($lResult != "") {
				$lArray[] = $lResult;
			}
		}
		return (!empty($lArray)) ? "(".implode(" ".$this->getOperator()." ", $lArray).")" : "";
	}
	
	public function hasOnlyOneLiteral() {
		$lhasOnlyOneLiteral = false;
		if (count($this->mLiterals) > 1) {
			return false;
		}elseif (count($this->mLiterals) == 1) {
			$lhasOnlyOneLiteral = true;
		}
		foreach ($this->mLogicalJunction as $lLogicalJunction) {
			if ($lLogicalJunction->hasLiterals()) {
				if ($lhasOnlyOneLiteral) {
					return false;
				}elseif ($lLogicalJunction->hasOnlyOneLiteral()) {
					$lhasOnlyOneLiteral = true;
				}else {
					return false;
				}
			}
		}
		return $lhasOnlyOneLiteral;
	}
	
	public function hasLiterals() {
		if (!empty($this->mLiterals)) {
			return true;
		}foreach ($this->mLogicalJunction as $lLogicalJunction) {
			if ($lLogicalJunction->hasLiterals()) {
				return true;
			}
		}
		return false;
	}
	
	public function isSatisfied($pPredicates) {
		$lReturn = false;
		if ($this->mType == self::CONJUNCTION) {
			$lReturn = $this->_isSatisfiedConjunction($pPredicates);
		}elseif ($this->mType == self::DISJUNCTION) {
			$lReturn = $this->_isSatisfiedDisjunction($pPredicates);
		}
		return $lReturn;
	}
	
	private function _isSatisfiedConjunction($pPredicates) {
		foreach ($this->getLiterals("md5") as $lKey => $lLiteral) {
			if (!$pPredicates[$lKey]) {
				return false;
			}
		}
		foreach ($this->mLogicalJunction as $lLogicalJunction) {
			if (!$lLogicalJunction->isSatisfied($pPredicates)) {
				return false;
			}
		}
		return true;
	}
	
	private function _isSatisfiedDisjunction($pPredicates) {
		$lSatisfied = false;
		foreach ($this->getLiterals("md5") as $lKey => $lLiteral) {
			$lSatisfied = $lSatisfied || $pPredicates[$lKey];
		}
		foreach ($this->mLogicalJunction as $lLogicalJunction) {
			$lSatisfied = $lSatisfied || $lLogicalJunction->isSatisfied($pPredicates);
		}
		return $lSatisfied;
	}
	
	public static function phpObjectToLogicalJunction($pPhpObject, &$pLeftJoins, $pLiteralCollection = null) {
		if (!isset($pPhpObject->type) || (isset($pPhpObject->logicalJunctions) && !is_array($pPhpObject->logicalJunctions)) || (isset($pPhpObject->literals) && !is_array($pPhpObject->literals))) {
			throw new \Exception("malformed phpObject LogicalJunction : ".json_encode($pPhpObject));
		}
		$lLogicalJunction = new LogicalJunction($pPhpObject->type);
		if (isset($pPhpObject->logicalJunctions)) {
			foreach ($pPhpObject->logicalJunctions as $lPhpObjectLogicalJunction) {
				$lLogicalJunction->addLogicalJunction(LogicalJunction::phpObjectToLogicalJunction($lPhpObjectLogicalJunction, $pLeftJoins, $pLiteralCollection));
			}
		}
		if (isset($pPhpObject->literals)) {
			foreach ($pPhpObject->literals as $lPhpObjectLiteral) {
				$lLogicalJunction->addLiteral(Literal::phpObjectToLiteral($lPhpObjectLiteral, $pLeftJoins, $pLiteralCollection));
			}
		}
		return $lLogicalJunction;
	}
}