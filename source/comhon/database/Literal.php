<?php
namespace comhon\database;

use comhon\object\singleton\InstanceModel;
use comhon\object\model\ForeignProperty;
use comhon\object\model\ModelContainer;
use comhon\object\ComplexLoadRequest;

class Literal {
	
	const EQUAL      = '=';
	const SUPP       = '>';
	const INF        = '<';
	const SUPP_EQUAL = '>=';
	const INF_EQUAL  = '<=';
	const DIFF       = '<>';
	
	private static $sIndex = 0;

	protected $mId;
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

	public function getId() {
		return $this->mId;
	}
	
	public function setId($pId) {
		$this->mId = $pId;
	}
	
	public function hasId() {
		return !is_null($this->mId);
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
	 * @param stdClass $pStdObject
	 * @param array $pLeftJoins
	 * @throws \Exception
	 * @return Literal
	 */
	public static function stdObjectToLiteral($pStdObject, &$pLeftJoins, $pLiteralCollection = null) {
		if (isset($pStdObject->id) && !is_null($pLiteralCollection)) {
			if (!array_key_exists($pStdObject->id, $pLiteralCollection)) {
				throw new \Exception("literal id '{$pStdObject->id}' is not defined in literal collection");
			}
			return $pLiteralCollection[$pStdObject->id];
		}
		self::_verifStdObject($pStdObject);
		if (!array_key_exists($pStdObject->node, $pLeftJoins)) {
			throw new \Exception("node doesn't exist in join tree : ".json_encode($pStdObject));
		}
		
		$lLeftJoin      = $pLeftJoins[$pStdObject->node];
		$lLeftModel     = $lLeftJoin['left_model'];
		$lRightodel     = $lLeftJoin['right_model'];
		$lTable         = $pStdObject->node;
		
		if (isset($pStdObject->queue)) {
			$lSubQueryTables   = self::_queuetoLeftJoins($lLeftModel, $lTable, $pStdObject->queue);
			$lLeftJoin         = $pLeftJoins[$pStdObject->node];
			$lLeftColumn       = $lLeftJoin['left_model']->getFirstIdProperty()->getSerializationName();
			$lLeftTable        = array_key_exists('right_table_alias', $lLeftJoin) && !is_null($lLeftJoin['right_table_alias']) ? $lLeftJoin['right_table_alias'] : $lLeftJoin['right_table'];
			$lColumnIdSubQuery = $lSubQueryTables[0]['right_column'][0];
			$lSelectQuery      = self::_setSubSelectQuery($lSubQueryTables, $pStdObject);
			$lRigthTableAlias  = 't_'.self::$sIndex++;
			
			while (array_key_exists($lRigthTableAlias, $pLeftJoins)) {
				$lRigthTableAlias  = 't_'.self::$sIndex++;
			}
			$lJoinTable = array(
				'left_table'        => $lLeftTable,
				'left_column'       => $lLeftColumn,
				'right_table'       => $lSelectQuery,
				'right_table_alias' => $lRigthTableAlias,
				'right_column'      => $lColumnIdSubQuery
			);
			$pLeftJoins[$lJoinTable['right_table_alias']] = $lJoinTable;
			$lLiteral = new Literal($lJoinTable['right_table_alias'], $lColumnIdSubQuery, Literal::DIFF, null);
		}
		else {
			$lProperty =  $lRightodel->getProperty($pStdObject->property, true);
			if ($lProperty->isComposition()) {
				throw new \Exception("literal cannot contain foreign porperty '{$pStdObject->property}'");
			}
			$lLiteral  = new Literal($lTable, $lProperty->getSerializationName(), $pStdObject->operator, $pStdObject->value);
		}
		if (isset($pStdObject->id)) {
			$lLiteral->setId($pStdObject->id);
		}
		return $lLiteral;
	}
	
	private static function _verifStdObject($pStdObject) {
		if (!isset($pStdObject->node)) {
			throw new \Exception("malformed stdObject literal : ".json_encode($pStdObject));
		}
		if (isset($pStdObject->queue)) {
			if (!(isset($pStdObject->havingLiteral) xor isset($pStdObject->havingLogicalJunction)) || !is_object($pStdObject->queue)) {
				throw new \Exception("malformed stdObject literal : ".json_encode($pStdObject));
			}
		} else if (!isset($pStdObject->property) || !isset($pStdObject->operator) || !isset($pStdObject->value) || !isset($pStdObject->node)) {
			throw new \Exception("malformed stdObject literal : ".json_encode($pStdObject));
		}
	}
	
	private static function _queuetoLeftJoins($pModel, $pAlias, $pQueue, $pTableNameUsed = array()) {
		$lLeftModel      = $pModel;
		$lLeftTable      = $pModel->getSqlTableUnit();
		$lLeftAliasTable = self::_getAlias($lLeftTable->getValue('name'), $pTableNameUsed);
		$lLeftJoins      = array(
			array(
				'left_model'        => $pModel,
				'right_model'       => $pModel,
				'right_table'       => $lLeftTable->getValue('name'),
				'right_table_alias' => $lLeftAliasTable,
				'right_column'      => $pModel->getSerializationIds()
			)
		);
	
		$lCurrentNode = $pQueue;
		while (!is_null($lCurrentNode)) {
			if (!is_object($lCurrentNode) || !isset($lCurrentNode->property)) {
				throw new \Exception("malformed stdObject literal : ".json_encode($pStdObject));
			}
			$lLeftTableName = is_null($lLeftAliasTable) ? $lLeftTable->getValue('name') : $lLeftAliasTable;
			$lProperty      = $lLeftModel->getProperty($lCurrentNode->property, true);
			$lLeftJoin      = ComplexLoadRequest::prepareLeftJoin($lLeftTable, $lLeftModel, $lProperty);
				
			$lLeftJoin["left_table"]        = $lLeftTableName;
			$lLeftJoin["right_table_alias"] = self::_getAlias($lLeftJoin["right_table"], $pTableNameUsed);
	
			$lLeftJoins[]    = $lLeftJoin;
			$lLeftModel      = $lProperty->getUniqueModel();
			$lLeftTable      = $lProperty->getSqlTableUnit();
			$lLeftAliasTable = $lLeftJoin["right_table_alias"];
			$lCurrentNode    = isset($lCurrentNode->child) ? $lCurrentNode->child : null;
		}
		// if first left join has only one join column, first table is redundant so we can remove it.
		if (count($lLeftJoins[1]['right_column']) == 1) {
			array_shift($lLeftJoins);
		}
		return $lLeftJoins;
	}
	
	private static function _getAlias($pTableName, &$pTableNameUsed) {
		$lReturn = null;
		if (array_key_exists($pTableName, $pTableNameUsed)) {
			$pTableName = $pTableName.'_'.self::$sIndex++;
			$pTableNameUsed[$pTableName] = null;
			$lReturn = $pTableName;
		} else {
			$pTableNameUsed[$pTableName] = null;
		}
		return $lReturn;
	}
	
	private static function _setSubSelectQuery($pSubQueryTables, $pStdObject) {
		if (isset($pStdObject->subQuery)) {
			// TODO
		} else {
			$lSelectQuery = new SelectQuery($pSubQueryTables[0]['right_table'], $pSubQueryTables[0]['right_table_alias']);
			for ($i = 1; $i < count($pSubQueryTables); $i++) {
				$lJoinTable = $pSubQueryTables[$i];
				$lSelectQuery->addTable($lJoinTable["right_table"], $lJoinTable["right_table_alias"], SelectQuery::LEFT_JOIN, $lJoinTable["right_column"], $lJoinTable["left_column"], $lJoinTable["left_table"]);
			}
		}
		$lFirstQueryTable = $pSubQueryTables[0];
		$lFirstTableName  = array_key_exists('right_table_alias', $lFirstQueryTable) && !is_null($lFirstQueryTable['right_table_alias']) 
							? $lFirstQueryTable['right_table_alias'] : $lFirstQueryTable['right_table'];
		$lLastQueryTable  = $pSubQueryTables[count($pSubQueryTables) - 1];
		$lLastTableName   = array_key_exists('right_table_alias', $lLastQueryTable) && !is_null($lLastQueryTable['right_table_alias'])
							? $lLastQueryTable['right_table_alias'] : $lLastQueryTable['right_table'];

		$lFirstColumnId = $lFirstQueryTable['right_column'][0];
		$lSelectQuery->setFirstTableCurrentTable()->addSelectColumn($lFirstColumnId)->addGroupColumn($lFirstColumnId);
		
		if (isset($pStdObject->havingLogicalJunction)) {
			$lSubLogicalJunction = self::stdObjectToHavingLogicalJunction($pStdObject->havingLogicalJunction, $lFirstTableName, $lFirstColumnId, $lLastTableName, $lLastQueryTable["right_model"]);
		} else {
			self::_completeHavingLiteral($pStdObject->havingLiteral, $lFirstTableName, $lFirstColumnId, $lLastTableName, $lLastQueryTable["right_model"]);
			$lSubLogicalJunction = new HavingLogicalJunction(LogicalJunction::CONJUNCTION);
			$lSubLogicalJunction->addLiteral(HavingLiteral::stdObjectToHavingLiteral($pStdObject->havingLiteral));
		}
		$lSelectQuery->setHavingLogicalJunction($lSubLogicalJunction);
		return $lSelectQuery;
	}
	
	public static function stdObjectToHavingLogicalJunction($pStdObject, $pFirstTableName, $pFirstColumnId, $pLastTableName, $pLastModel) {
		if (!is_object($pStdObject) || !isset($pStdObject->type) || (isset($pStdObject->logicalJunctions) && !is_array($pStdObject->logicalJunctions)) || (isset($pStdObject->literals) && !is_array($pStdObject->literals))) {
			throw new \Exception("malformed stdObject LogicalJunction : ".json_encode($pStdObject));
		}
		$lLogicalJunction = new HavingLogicalJunction($pStdObject->type);
		if (isset($pStdObject->logicalJunctions)) {
			foreach ($pStdObject->logicalJunctions as $lStdObjectLogicalJunction) {
				$lLogicalJunction->addLogicalJunction(self::stdObjectToHavingLogicalJunction($lStdObjectLogicalJunction, $pFirstTableName, $pFirstColumnId, $pLastTableName, $pLastModel));
			}
		}
		if (isset($pStdObject->literals)) {
			foreach ($pStdObject->literals as $lStdObjectLiteral) {
				self::_completeHavingLiteral($lStdObjectLiteral, $pFirstTableName, $pFirstColumnId, $pLastTableName, $pLastModel);
				$lLogicalJunction->addLiteral(HavingLiteral::stdObjectToHavingLiteral($lStdObjectLiteral));
			}
		}
		return $lLogicalJunction;
	}
	
	private static function _completeHavingLiteral($pStdObjectLiteral, $pFirstTableName, $pFirstColumnId, $pLastTableName, $pLastModel) {
		if ($pStdObjectLiteral->function == HavingLiteral::COUNT) {
			$pStdObjectLiteral->node   = $pFirstTableName;
			$pStdObjectLiteral->column = $pFirstColumnId;
		}
		else {
			if (!isset($pStdObjectLiteral->property)) {
				throw new \Exception("malformed stdObject literal : ".json_encode($pStdObjectLiteral));
			}
			$pStdObjectLiteral->node   = $pLastTableName;
			$pStdObjectLiteral->column = $pLastModel->getProperty($pStdObjectLiteral->property, true)->getSerializationName();
		
		}
	}
}