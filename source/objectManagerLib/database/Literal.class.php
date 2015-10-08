<?php
namespace objectManagerLib\database;

use objectManagerLib\object\singleton\InstanceModel;

class Literal {

	const EQUAL      = '=';
	const SUPP       = '>';
	const INF        = '<';
	const SUPP_EQUAL = '>=';
	const INF_EQUAL  = '<=';
	const DIFF       = '<>';
	
	protected $mTable;
	protected $mPropertyName; // name of table concatanate with propertyName
	protected $mOperator;     // operator
	protected $mValue;        // value(s) to filter
	protected $mModelName;
	
	protected static $sAcceptedOperators = array(
		self::EQUAL      => null,
		self::SUPP       => null,
		self::INF        => null,
		self::SUPP_EQUAL => null,
		self::INF_EQUAL  => null,
		self::DIFF       => null
	);
	
	protected static $sOppositeOperator = array(
		self::EQUAL      => self::DIFF,
		self::INF        => self::SUPP_EQUAL,
		self::INF_EQUAL  => self::SUPP,
		self::SUPP       => self::INF_EQUAL,
		self::SUPP_EQUAL => self::INF,
		self::DIFF       => self::EQUAL
	);
	
	/**
	 * construtor
	 * @param unknown $pTable
	 * @param unknown $pPropertyName
	 * @param unknown $pOperator
	 * @param unknown $pValue could be null, a string, a number or an array with null or string or number values
	 * @param string $pModelName
	 * @throws \Exception
	 */
	public function __construct($pTable, $pPropertyName, $pOperator, $pValue, $pModelName = null) {
		$this->mTable = $pTable;
		$this->mOperator = $pOperator;
		$this->mValue = $pValue;
		if (is_null($pModelName)) {
			$this->mPropertyName = $pPropertyName;
		}else {
			$this->mModelName = $pModelName;
			$lModel = InstanceModel::getInstance()->getInstanceModel($this->mModelName);
			if (is_null($lProperty = $lModel->getProperty($pPropertyName))) {
				throw new \Exception("'$pModelName' doesn't have property '$pPropertyName'");
			}
			$this->mPropertyName = $lProperty->getSerializationName();
		}
		$this->_verifLiteral();
	}
	
	protected function _verifLiteral() {
		if (!array_key_exists($this->mOperator, self::$sAcceptedOperators)) {
			throw new \Exception("operator '".$this->mOperator."' doesn't exists");
		}
		if (is_null($this->mValue) && ($this->mOperator != "=") && ($this->mOperator != "<>")) {
			throw new \Exception("literal with operator '".$this->mOperator."' can't have null value");
		}
		if (is_array($this->mValue) && ($this->mOperator != "=") && ($this->mOperator != "<>")) {
			throw new \Exception("literal with operator '".$this->mOperator."' can't have array value");
		}
	}

	public function getTable() {
		return $this->mTable;
	}
	
	public function setTable($pTableName) {
		$this->mTable = $pTableName;
	}
	
	public function getPropertyName() {
		return $this->mPropertyName;
	}
	
	public function getOperator() {
		return $this->mOperator;
	}
	
	public function reverseOperator() {
		$this->mOperator = self::$sOppositeOperator[$this->mOperator];
	}
	
	public function getValue() {
		return $this->mValue;
	}
	
	public function getModelName() {
		return $this->mModelName;
	}
	
	/**
	 * @param array $pValues
	 * @return string
	 */
	public function export(&$pValues) {
		if ((($this->mOperator == "=") || ($this->mOperator == "<>")) && is_array($this->mValue)) {
			$i = 0;
			$lToReplaceValues = array();
			$lHasNullValue = false;
			while ($i < count($this->mValue)) {
				if (is_null($this->mValue[$i])) {
					$lHasNullValue = true;
				}else {
					$pValues[] = $this->mValue[$i];
					$lToReplaceValues[] = "?";
				}
				$i++;
			}
			$lOperator = ($this->mOperator == "=") ? " IN " : " NOT IN ";
			$lToReplaceValues = "(".implode(",", $lToReplaceValues).")";
			$lStringValue = sprintf("%s.%s %s %s", $this->mTable, $this->mPropertyName, $lOperator, $lToReplaceValues);
			if ($lHasNullValue) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lConnector = ($this->mOperator == "=") ? 'or' : 'and';
				$lStringValue = sprintf("(%s %s %s.%s %s)", $lStringValue, $lConnector, $this->mTable, $this->mPropertyName, $lOperator);
			}
		}else {
			if (is_null($this->mValue)) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lStringValue = sprintf("%s.%s %s", $this->mTable, $this->mPropertyName, $lOperator);
			}else {
				$pValues[] = $this->mValue;
				$lStringValue = sprintf("%s.%s %s ?", $this->mTable, $this->mPropertyName, $this->mOperator);
			}
		}
		return $lStringValue;
	}
	
	/**
	 * can't be used to populate a database query
	 * @return string
	 */
	public function exportWithValue() {
		if ((($this->mOperator == "=") || ($this->mOperator == "<>")) && is_array($this->mValue)) {
			$i = 0;
			$lToReplaceValues = array();
			$lHasNullValue = false;
			while ($i < count($this->mValue)) {
				if (is_null($this->mValue[$i])) {
					$lHasNullValue = true;
				}else {
					$lToReplaceValues[] = $this->mValue[$i];
				}
				$i++;
			}
			$lOperator = ($this->mOperator == "=") ? " IN " : " NOT IN ";
			$lToReplaceValues = "(".implode(",", $lToReplaceValues).")";
			$lStringValue = sprintf("%s.%s %s %s", $this->mTable, $this->mPropertyName, $lOperator, $lToReplaceValues);
			if ($lHasNullValue) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lConnector = ($this->mOperator == "=") ? 'or' : 'and';
				$lStringValue = sprintf("(%s %s %s.%s %s)", $lStringValue, $lConnector, $this->mTable, $this->mPropertyName, $lOperator);
			}
		}else {
			if (is_null($this->mValue)) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lStringValue = sprintf("%s.%s %s", $this->mTable, $this->mPropertyName, $lOperator);
			}else {
				$lStringValue = sprintf("%s.%s %s %s", $this->mTable, $this->mPropertyName, $this->mOperator, $this->mValue);
			}
		}
		return $lStringValue;
	}
	
	public static function phpObjectToLiteral($pPhpObject, $pMainModel) {
		if ((!isset($pPhpObject->property) && (!isset($pPhpObject->function) || ($pPhpObject->function != HavingLiteral::COUNT))) || !isset($pPhpObject->operator) || !isset($pPhpObject->value) || (!isset($pPhpObject->model) && !isset($pPhpObject->table))) {
			throw new \Exception("malformed phpObject literal : ".json_encode($pPhpObject));
		}
		$lModelName = isset($pPhpObject->model)    ? $pPhpObject->model : null;
		$lTable     = isset($pPhpObject->table)    ? $pPhpObject->table : null;
		$lProperty  = isset($pPhpObject->property) ? $pPhpObject->property : null;
		
		if (isset($pPhpObject->function)) {
			if (is_null($lProperty) && !is_null($lModelName) && ($pPhpObject->function == HavingLiteral::COUNT)) {
				$lModel = InstanceModel::getInstance()->getInstanceModel($lModelName);
				if (count($lModel->getIds()) != 1) {
					throw new \Exception("error : count literal must have one and only one property id");
				}
				$lProperty = $lModel->getFirstId();
			}
			$lSubLogicalJunction = new HavingLogicalJunction(LogicalJunction::CONJUNCTION);
			$lSubLogicalJunction->addLiteral(new HavingLiteral($pPhpObject->function, null, $lProperty, $pPhpObject->operator, $pPhpObject->value, $lModelName));
			$lSelectQuery = new SelectQuery(null);
			$lSelectQuery->setHavingLogicalJunction($lSubLogicalJunction);
			$lLiteral = new ComplexLiteral(null, null, ComplexLiteral::IN, $lSelectQuery, $pMainModel->getModelName());
		}
		else {
			$lLiteral = new Literal($lTable, $lProperty, $pPhpObject->operator, $pPhpObject->value, $lModelName);
		}
		return $lLiteral;
	}
}