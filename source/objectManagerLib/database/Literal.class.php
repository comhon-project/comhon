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
	public function __construct($pTable, $pColumn, $pOperator, $pValue) {
		$this->mTable     = $pTable;
		$this->mOperator  = $pOperator;
		$this->mValue     = $pValue;
		$this->mColumn    = $pColumn;
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
	
	/**
	 * @param stdClass $pPhpObject
	 * @param Model $pMainModel model of your object request
	 * @throws \Exception
	 * @return Literal
	 */
	public static function phpObjectToLiteral($pPhpObject, $pJoinTree, $pTablesWithConditions) {
		if ((!isset($pPhpObject->property) && (!isset($pPhpObject->function) || ($pPhpObject->function != HavingLiteral::COUNT))) || !isset($pPhpObject->operator) || !isset($pPhpObject->value) || !isset($pPhpObject->node)) {
			throw new \Exception("malformed phpObject literal : ".json_encode($pPhpObject));
		}
		if (!$pJoinTree->goToSavedNodeAt($pPhpObject->node)) {
			throw new \Exception("node doesn't exist in join tree : ".json_encode($pPhpObject));
		}
		$lJoinValue     = $pJoinTree->current();
		$lLeftModel     = $lJoinValue['left_model'];
		$lRightodel     = $lJoinValue['right_model'];
		$lProperty      = $lRightodel->getProperty($pPhpObject->property);
		$lTable         = $pPhpObject->node;
		
		if (is_null($lProperty)) {
			throw new \Exception("error : unknown property '{$pPhpObject->property}' for model '{$lRightodel->getModelName()}'");
		}
		$lColumn        = $lProperty->getSerializationName();
		
		if (isset($pPhpObject->function)) {
			$lSubQueryTables = self::_getSubQueryTables($pJoinTree, $lTable, $pTablesWithConditions);
			$lNodeValue      = $pJoinTree->current();
			$lComplexColumn  = $lNodeValue['left_model']->getProperty($lNodeValue['left_model']->getFirstId())->getSerializationName();
			$lComplexTable   = array_key_exists('right_table_alias', $lNodeValue) && !is_null($lNodeValue['right_table_alias']) ? $lNodeValue['right_table_alias'] : $lNodeValue['right_table'];
			
			if ($pPhpObject->function == HavingLiteral::COUNT) {
				$lSubQueryTables[] = self::_getCountJoinTable($lJoinValue, $lLeftModel, $lProperty);
				$lColumn = $lComplexColumn;
				$lTable  = $lComplexTable;
			}
			$lSelectQuery = self::_setSubSelectQuery($lSubQueryTables, $pPhpObject, $lTable, $lColumn, $lComplexColumn);
			$lLiteral     = new ComplexLiteral($lComplexTable, $lComplexColumn, ComplexLiteral::IN, $lSelectQuery);
		}
		else {
			$lLiteral = new Literal($lTable, $lColumn, $pPhpObject->operator, $pPhpObject->value);
		}
		return $lLiteral;
	}
	
	private static function _getSubQueryTables($pJoinTree, $pTable, $pTablesWithConditions) {
		$pJoinTree->goToSavedNodeAt($pTable);
		$lSubQueryTables = array();
		$lChildTable     = $pTable;
		$lIsDeletable    = true;
		$lRewind         = true;
		while ($lRewind && $pJoinTree->goToParent()) {
			$lIndex = $pJoinTree->searchChild(function($pJoinTable, $pTableName) {
				if (array_key_exists('right_table_alias', $pJoinTable) && !is_null($pJoinTable['right_table_alias'])) {
					return $pJoinTable['right_table_alias'] == $pTableName;
				}
				return $pJoinTable['right_table'] == $pTableName;
			}, $lChildTable);
			$lSubQueryTable    = $lIsDeletable ? $pJoinTree->deleteChildAt($lIndex) : $pJoinTree->getChildAt($lIndex);
			$lSubQueryTables[] = $lSubQueryTable;
			$lRewind      = !array_key_exists($lSubQueryTable['left_table'], $pTablesWithConditions);
			$lIsDeletable = !$pJoinTree->hasChildren();
			$lChildTable  = $lSubQueryTable['left_table'];
		}
		$lSubQueryTables[] = $pJoinTree->current();
		return array_reverse($lSubQueryTables);
	}
	
	private static function _getCountJoinTable($pJoinValue, $pModel, $pProperty) {
		if (!($pProperty instanceof ForeignProperty) || !$pProperty->hasSqlTableUnitComposition($pModel)) {
			throw new \Exception("error : function 'COUNT' must be applied on a composition property. '{$pProperty->getName()}' for model '{$pModel->getModelName()}' is not a composition");
		}
		$lRightTable = $pProperty->getSqlTableUnit();
		return  array(
				"left_table"        => array_key_exists('right_table_alias', $pJoinValue) && !is_null($pJoinValue['right_table_alias']) ? $pJoinValue['right_table_alias'] : $pJoinValue['right_table'],
				"right_table"       => $lRightTable->getValue("name"),
				"right_table_alias" => $lRightTable->getValue("name")."_".mt_rand(),
				"left_column"       => $pModel->getProperty($pModel->getFirstId())->getSerializationName(),
				"right_column"      => $lRightTable->getCompositionColumns($pModel, $pProperty->getSerializationName())
		);
	}
	
	private static function _setSubSelectQuery($pSubQueryTables, $pPhpObject, $pHavingTable, $pHavingColumn, $pSelectColumn) {
		$lSelectQuery = new SelectQuery($pSubQueryTables[0]['right_table'], $pSubQueryTables[0]['right_table_alias']);
		$lSelectQuery->addSelectColumn($pSelectColumn)->addGroupColumn($pSelectColumn);
		
		for ($i = 1; $i < count($pSubQueryTables); $i++) {
			$lJoinTable = $pSubQueryTables[$i];
			$lSelectQuery->addTable($lJoinTable["right_table"], $lJoinTable["right_table_alias"], SelectQuery::LEFT_JOIN, $lJoinTable["right_column"], $lJoinTable["left_column"], $lJoinTable["left_table"]);
		}
		$lSubLogicalJunction = new HavingLogicalJunction(LogicalJunction::CONJUNCTION);
		$lSubLogicalJunction->addLiteral(new HavingLiteral($pPhpObject->function, $pHavingTable, $pHavingColumn, $pPhpObject->operator, $pPhpObject->value));
			
		$lSelectQuery->setHavingLogicalJunction($lSubLogicalJunction);
		return $lSelectQuery;
	}
	
}