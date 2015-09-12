<?php

class Condition {

	protected $mTable;
	protected $mPropertyName;      // name of table concatanate with propertyName
	protected $mOperator; // operator
	protected $mValue;    // value(s) to filter
	
	private static $sAcceptedConditions = array(
		"=" => null,
		"<" => null,
		"<=" => null,
		">" => null,
		">=" => null,
		"<>" => null
	);
	
	public static $sOppositeConditions = array(
			"=" => "<>",
			"<" => ">=",
			"<=" => ">",
			">" => "<=",
			">=" => "<",
			"<>" => "="
	);
	
	/**
	 * 
	 * @param string $pConditionType
	 * @param unknown $pValue could be a string or an array if $pConditionType is set to "="
	 */
	public function __construct($pTable, $pPropertyName, $pOperator, $pValue) {
		$this->mTable = $pTable;
		$this->mPropertyName = $pPropertyName;
		$this->mOperator = $pOperator;
		$this->mValue = $pValue;
	}

	public function getTable() {
		return $this->mTable;
	}
	
	public function getPropertyName() {
		return $this->mPropertyName;
	}
	
	public function getOperator() {
		return $this->mOperator;
	}
	
	public function getValue() {
		return $this->mValue;
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
				$lConnector = ($this->mOperator == "=") ? "or" : "and";
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
}