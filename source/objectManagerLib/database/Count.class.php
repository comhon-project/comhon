<?php
namespace objectManagerLib\database;

use objectManagerLib\object\singleton\InstanceModel;

class Count extends Literal {

	/**
	 * constructor
	 * @param string $pModelName model linked to your literal. MUST have a database serialization
	 * @param string $pPropertyName
	 * @param string $pOperator
	 * @param integer $pValue
	 */
	public function __construct($pTable, $pOperator, $pValue) {
		parent::__construct($pTable, null, $pOperator, $pValue);
	}

	private function _verifLiteral() {
		if (!array_key_exists($this->mOperator, self::$sAcceptedLiterals)) {
			throw new \Exception("operator '".$this->mOperator."' doesn't exists");
		}
		if (!is_int($this->mValue)) {
			throw new \Exception("count literal must have an integer value");
		}
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
				$lConnector = ($this->mOperator == "=") ? LogicalJunction::_OR : LogicalJunction::_AND;
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