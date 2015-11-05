<?php
namespace objectManagerLib\database;

use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\object\model\ModelContainer;

class Literal {

	const EQUAL      = '=';
	const SUPP       = '>';
	const INF        = '<';
	const SUPP_EQUAL = '>=';
	const INF_EQUAL  = '<=';
	const DIFF       = '<>';
	
	protected $mTable;
	protected $mColumn;
	protected $mOperator;
	protected $mValue;
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
	 * @param unknown $pColumn 
	 * @param unknown $pOperator
	 * @param unknown $pValue could be :
	 * - null
	 * - a string
	 * - a number
	 * - an array with null or string or number values
	 * @param string $pModelName
	 * @throws \Exception
	 */
	public function __construct($pTable, $pColumn, $pOperator, $pValue, $pModelName = null) {
		$this->mTable     = $pTable;
		$this->mOperator  = $pOperator;
		$this->mValue     = $pValue;
		$this->mColumn    = $pColumn;
		$this->mModelName = $pModelName;
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
		return $this->mColumn;
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
			$lStringValue = sprintf("%s.%s %s %s", $this->mTable, $this->mColumn, $lOperator, $lToReplaceValues);
			if ($lHasNullValue) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lConnector = ($this->mOperator == "=") ? 'or' : 'and';
				$lStringValue = sprintf("(%s %s %s.%s %s)", $lStringValue, $lConnector, $this->mTable, $this->mColumn, $lOperator);
			}
		}else {
			if (is_null($this->mValue)) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lStringValue = sprintf("%s.%s %s", $this->mTable, $this->mColumn, $lOperator);
			}else {
				$pValues[] = $this->mValue;
				$lStringValue = sprintf("%s.%s %s ?", $this->mTable, $this->mColumn, $this->mOperator);
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
			$lStringValue = sprintf("%s.%s %s %s", $this->mTable, $this->mColumn, $lOperator, $lToReplaceValues);
			if ($lHasNullValue) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lConnector = ($this->mOperator == "=") ? 'or' : 'and';
				$lStringValue = sprintf("(%s %s %s.%s %s)", $lStringValue, $lConnector, $this->mTable, $this->mColumn, $lOperator);
			}
		}else {
			if (is_null($this->mValue)) {
				$lOperator = ($this->mOperator == "=") ? "is null" : "is not null";
				$lStringValue = sprintf("%s.%s %s", $this->mTable, $this->mColumn, $lOperator);
			}else {
				$lStringValue = sprintf("%s.%s %s %s", $this->mTable, $this->mColumn, $this->mOperator, $this->mValue);
			}
		}
		return $lStringValue;
	}
	
	public static function phpObjectToLiteral($pPhpObject, $pMainModel, $pModelByTable = null) {
		if ((!isset($pPhpObject->property) && (!isset($pPhpObject->function) || ($pPhpObject->function != HavingLiteral::COUNT))) || !isset($pPhpObject->operator) || !isset($pPhpObject->value) || (!isset($pPhpObject->model) && !isset($pPhpObject->table))) {
			throw new \Exception("malformed phpObject literal : ".json_encode($pPhpObject));
		}
		$lModelName    = isset($pPhpObject->model) ? $pPhpObject->model : null;
		$lTable        = isset($pPhpObject->node)  ? $pPhpObject->node  : null;
		$lPropertyName = $pPhpObject->property;
		
		if (!is_null($lModelName)) {
			$lModel = InstanceModel::getInstance()->getInstanceModel($lModelName);
		} else if (!is_null($lTable) && !is_null($pModelByTable)) {
			if (!array_key_exists($lTable, $pModelByTable)) {
				throw new \Exception("error : unknown property '$lPropertyName' for model '{$lModel->getModelName()}'");
			}
			$lModel = $pModelByTable[$lTable];
		} else {
			throw new \Exception("error : phpObject Literal must have 'node' or 'model' property");
		}
		if (!$lModel->hasProperty($lPropertyName)) {
			throw new \Exception("error : unknown property '$lPropertyName' for model '{$lModel->getModelName()}'");
		}
		
		if (isset($pPhpObject->function)) {
			$lSelectQuery = new SelectQuery(null);
			$lSubLogicalJunction = new HavingLogicalJunction(LogicalJunction::CONJUNCTION);
			
			if ($pPhpObject->function == HavingLiteral::COUNT) {
				if (!($lModel->getProperty($lPropertyName) instanceof ForeignProperty) || !$lModel->getProperty($lPropertyName)->hasSqlTableUnitComposition($lModel)) {
					throw new \Exception("error : function 'COUNT' must be applied on a composition property. '$lPropertyName' for model '{$lModel->getModelName()}' is not a composition");
				}
				$lPropertyModel = $lModel->getProperty($lPropertyName)->getModel();
				while ($lPropertyModel instanceof ModelContainer) {
					$lPropertyModel = $lPropertyModel->getModel();
				}
				$lColumn = $pMainModel->getProperty($pMainModel->getFirstId())->getSerializationName();
				$lHavingLiteral = new HavingLiteral($pPhpObject->function, null, $lColumn, $pPhpObject->operator, $pPhpObject->value, $pMainModel->getModelName(), $lPropertyModel->getModelName());
			}
			else {
				$lColumn = $lModel->getProperty($lPropertyName)->getSerializationName();
				$lHavingLiteral = new HavingLiteral($pPhpObject->function, null, $lColumn, $pPhpObject->operator, $pPhpObject->value, $lModelName);
			}
			$lSubLogicalJunction->addLiteral($lHavingLiteral);
			
			$lSelectQuery->setHavingLogicalJunction($lSubLogicalJunction);
			$lLiteral = new ComplexLiteral(null, null, ComplexLiteral::IN, $lSelectQuery, $pMainModel->getModelName());
		}
		else {
			$lColumn = $lModel->getProperty($lPropertyName)->getSerializationName();
			$lLiteral = new Literal($lTable, $lColumn, $pPhpObject->operator, $pPhpObject->value, $lModelName);
		}
		return $lLiteral;
	}
}