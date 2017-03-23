<?php
namespace comhon\database;

use comhon\model\singleton\ModelManager;
use comhon\model\property\ForeignProperty;
use comhon\model\ModelContainer;
use comhon\request\ComplexLoadRequest;
use comhon\model\Model;
use comhon\model\MainModel;

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
	
	protected static $sAcceptedOperators = [
		self::EQUAL      => null,
		self::SUPP       => null,
		self::INF        => null,
		self::SUPP_EQUAL => null,
		self::INF_EQUAL  => null,
		self::DIFF       => null
	];
	
	protected static $sOppositeOperator = [
		self::EQUAL      => self::DIFF,
		self::INF        => self::SUPP_EQUAL,
		self::INF_EQUAL  => self::SUPP,
		self::SUPP       => self::INF_EQUAL,
		self::SUPP_EQUAL => self::INF,
		self::DIFF       => self::EQUAL
	];
	
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
			throw new \Exception('operator \''.$this->mOperator.'\' doesn\'t exists');
		}
		if (is_null($this->mValue) && ($this->mOperator != '=') && ($this->mOperator != '<>')) {
			throw new \Exception('literal with operator \''.$this->mOperator.'\' can\'t have null value');
		}
		if (is_array($this->mValue) && ($this->mOperator != '=') && ($this->mOperator != '<>')) {
			throw new \Exception('literal with operator \''.$this->mOperator.'\' can\'t have array value');
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
		$lColumnTable = (($this->mTable instanceof TableNode) ? $this->mTable->getExportName() : $this->mTable) . '.' . $this->mColumn;
		if ((($this->mOperator == '=') || ($this->mOperator == '<>')) && is_array($this->mValue)) {
			$i = 0;
			$lToReplaceValues = [];
			$lHasNullValue = false;
			while ($i < count($this->mValue)) {
				if (is_null($this->mValue[$i])) {
					$lHasNullValue = true;
				}else {
					$pValues[] = $this->mValue[$i];
					$lToReplaceValues[] = '?';
				}
				$i++;
			}
			$lOperator = ($this->mOperator == '=') ? ' IN ' : ' NOT IN ';
			$lToReplaceValues = '('.implode(',', $lToReplaceValues).')';
			$lStringValue = sprintf('%s %s %s', $lColumnTable, $lOperator, $lToReplaceValues);
			if ($lHasNullValue) {
				$lOperator = ($this->mOperator == '=') ? 'is null' : 'is not null';
				$lConnector = ($this->mOperator == '=') ? 'or' : 'and';
				$lStringValue = sprintf('(%s %s %s %s)', $lStringValue, $lConnector, $lColumnTable, $lOperator);
			}
		}else {
			if (is_null($this->mValue)) {
				$lOperator = ($this->mOperator == '=') ? 'is null' : 'is not null';
				$lStringValue = sprintf('%s %s', $lColumnTable, $lOperator);
			}else {
				$pValues[] = $this->mValue;
				$lStringValue = sprintf('%s %s ?', $lColumnTable, $this->mOperator);
			}
		}
		return $lStringValue;
	}
	
	/**
	 * can't be used to populate a database query
	 * @return string
	 */
	public function exportWithValue() {
		if ((($this->mOperator == '=') || ($this->mOperator == '<>')) && is_array($this->mValue)) {
			$i = 0;
			$lToReplaceValues = [];
			$lHasNullValue = false;
			while ($i < count($this->mValue)) {
				if (is_null($this->mValue[$i])) {
					$lHasNullValue = true;
				}else {
					$lToReplaceValues[] = $this->mValue[$i];
				}
				$i++;
			}
			$lOperator = ($this->mOperator == '=') ? ' IN ' : ' NOT IN ';
			$lToReplaceValues = '('.implode(',', $lToReplaceValues).')';
			$lStringValue = sprintf('%s.%s %s %s', $this->mTable, $this->mColumn, $lOperator, $lToReplaceValues);
			if ($lHasNullValue) {
				$lOperator = ($this->mOperator == '=') ? 'is null' : 'is not null';
				$lConnector = ($this->mOperator == '=') ? 'or' : 'and';
				$lStringValue = sprintf('(%s %s %s.%s %s)', $lStringValue, $lConnector, $this->mTable, $this->mColumn, $lOperator);
			}
		}else {
			if (is_null($this->mValue)) {
				$lOperator = ($this->mOperator == '=') ? 'is null' : 'is not null';
				$lStringValue = sprintf('%s.%s %s', $this->mTable, $this->mColumn, $lOperator);
			}else {
				$lStringValue = sprintf('%s.%s %s %s', $this->mTable, $this->mColumn, $this->mOperator, $this->mValue);
			}
		}
		return $lStringValue;
	}
	
	/**
	 * @param stdClass $pStdObject
	 * @param Model $pMainModel
	 * @param Literal[] $pLiteralCollection
	 * @param SelectQuery $pSelectQuery
	 * @param boolean $pAllowPrivateProperties
	 * @throws \Exception
	 * @return Literal
	 */
	public static function stdObjectToLiteral($pStdObject, $pMainModel, $pLiteralCollection = null, $pSelectQuery = null, $pAllowPrivateProperties = true) {
		if (isset($pStdObject->id) && !is_null($pLiteralCollection)) {
			if (!array_key_exists($pStdObject->id, $pLiteralCollection)) {
				throw new \Exception("literal id '{$pStdObject->id}' is not defined in literal collection");
			}
			return $pLiteralCollection[$pStdObject->id];
		}
		self::_verifStdObject($pStdObject);
		$lTable = $pStdObject->node;
		
		if (isset($pStdObject->queue)) {
			list($lJoinedTables, $lOn) = self::_getJoinedTablesFromQueue($pMainModel, $pStdObject->queue, $pAllowPrivateProperties);
			$lSelectQuery = self::_setSubSelectQuery($lJoinedTables, $lOn, $pStdObject, $pAllowPrivateProperties);
			$lRigthTable  = new TableNode($lSelectQuery, 't_'.self::$sIndex++, false);
			
			if (!is_null($pSelectQuery)) {
				$pSelectQuery->join(SelectQuery::LEFT_JOIN, $lRigthTable, self::_getJoinColumns($lTable, $lRigthTable->getExportName(), $lOn));
			}
			if (count($lOn) == 1) {
				$lLiteral = new Literal($lRigthTable->getExportName(), $lOn[0][1], Literal::DIFF, null);
			} else {
				$lLiteral = new NotNullJoinLiteral();
				foreach ($lOn as $lLiteralArray) {
					$lLiteral->addLiteral($lRigthTable->getExportName(), $lLiteralArray[1]);
				}
			}
		}
		else {
			$lProperty =  $pMainModel->getProperty($pStdObject->property, true);
			if ($lProperty->isAggregation()) {
				throw new \Exception("literal cannot contain aggregation porperty '{$pStdObject->property}'");
			}
			if (!$pAllowPrivateProperties && $lProperty->isPrivate()) {
				throw new \Exception("literal contain private property '{$lProperty->getName()}'");
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
			throw new \Exception('malformed stdObject literal : '.json_encode($pStdObject));
		}
		if (isset($pStdObject->queue)) {
			if (!(isset($pStdObject->havingLiteral) xor isset($pStdObject->havingLogicalJunction)) || !is_object($pStdObject->queue)) {
				throw new \Exception('malformed stdObject literal : '.json_encode($pStdObject));
			}
		} else if (!isset($pStdObject->property) || !isset($pStdObject->operator) || !isset($pStdObject->value) || !isset($pStdObject->node)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($pStdObject));
		}
	}
	
	/**
	 * 
	 * @param MainModel $pModel
	 * @param \stdClass $pQueue
	 * @param boolean $pAllowPrivateProperties
	 * @throws \Exception
	 * @return [[], []]
	 * - first element is array of joined tables
	 * - second element is array of columns that will be use for group, select and joins with principale query
	 */
	private static function _getJoinedTablesFromQueue(MainModel $pModel, $pQueue, $pAllowPrivateProperties) {
		$lFirstTable    = new TableNode($pModel->getSqlTableUnit()->getSettings()->getValue('name'), null, false);
		$lLeftTable     = $lFirstTable;
		$lFirstModel    = $pModel;
		$lLeftModel     = $lFirstModel;
		$lFirstNode     = $pQueue;
		$lCurrentNode   = $lFirstNode;
		$lJoinedTables  = [];
		$lOn            = null;
		
		while (!is_null($lCurrentNode)) {
			if (!is_object($lCurrentNode) || !isset($lCurrentNode->property)) {
				throw new \Exception('malformed stdObject literal : '.json_encode($pStdObject));
			}
			$lProperty = $lLeftModel->getProperty($lCurrentNode->property, true);
			if (!$pAllowPrivateProperties && $lProperty->isPrivate()) {
				throw new \Exception("literal contain private property '{$lProperty->getName()}'");
			}
			$lLeftJoin       = ComplexLoadRequest::prepareJoinedTable($lLeftTable, $lProperty, self::_getAlias());
			$lJoinedTables[] = $lLeftJoin;
			
			
			$lLeftModel   = $lLeftJoin['model'];
			$lLeftTable   = $lLeftJoin['table'];
			$lCurrentNode = isset($lCurrentNode->child) ? $lCurrentNode->child : null;
		}
		if (!is_null($lFirstNode)) {
			$lOn = [];
			if (!($lJoinedTables[0]['join_on'] instanceof LogicalJunction) || $lJoinedTables[0]['join_on']->getType() !== LogicalJunction::DISJUNCTION) {
				$lFirstJoinedTable = $lJoinedTables[0];
				if ($lFirstJoinedTable['join_on'] instanceof LogicalJunction) {
					foreach ($lFirstJoinedTable['join_on']->getLiterals() as $lLiteral) {
						$lOn[] = [$lLiteral->getPropertyName(), $lLiteral->getColumnRight()];
					}
				} else {
					$lOn[] = [$lFirstJoinedTable['join_on']->getPropertyName(), $lFirstJoinedTable['join_on']->getColumnRight()];
				}
			} else {
				array_unshift($lJoinedTables, ['table' => $lFirstTable, 'model' => $lFirstModel]);
				foreach ($pModel->getSerializationIds() as $lColumn) {
					$lOn[] = [$lColumn, $lColumn];
				}
			}
		}
		return [$lJoinedTables, $lOn];
	}
	
	private static function _getAlias() {
		return 't_'.self::$sIndex++;
	}
	
	/**
	 * 
	 * @param TableNode $pMainTable
	 * @param Model $pMainModel
	 * @param [] $pJoinedTables
	 * @param \stdClass $pStdObject
	 * @param boolean $pAllowPrivateProperties
	 * @return \comhon\database\SelectQuery
	 */
	private static function _setSubSelectQuery($pJoinedTables, $pGroupColumns, $pStdObject, $pAllowPrivateProperties) {
		$lMainTable   = $pJoinedTables[0]['table'];
		$lSelectQuery = new SelectQuery($lMainTable);
		
		for ($i = 1; $i < count($pJoinedTables); $i++) {
			$lJoinTable = $pJoinedTables[$i];
			$lSelectQuery->join(SelectQuery::INNER_JOIN, $lJoinTable['table'], $lJoinTable['join_on']);
		}
			
		$lLastTable = $pJoinedTables[count($pJoinedTables) - 1]['table'];
		$lLastModel = $pJoinedTables[count($pJoinedTables) - 1]['model'];

		$lSelectQuery->setMainTableAsCurrentTable();
		foreach ($pGroupColumns as $lColumns) {
			$lMainTable->addSelectedColumn($lColumns[1]);
			$lSelectQuery->addGroupColumn($lColumns[1]);
		}
		
		if (isset($pStdObject->havingLogicalJunction)) {
			$lHaving = HavingLogicalJunction::stdObjectToHavingLogicalJunction($pStdObject->havingLogicalJunction, $lMainTable, $lLastTable, $lLastModel, $pAllowPrivateProperties);
		} else {
			$lTable  = isset($pStdObject->havingLiteral->function) && ($pStdObject->havingLiteral->function == HavingLiteral::COUNT) ? $lMainTable : $lLastTable;
			$lHaving = HavingLiteral::stdObjectToHavingLiteral($pStdObject->havingLiteral, $lTable, $lLastModel, $pAllowPrivateProperties);
		}
		$lSelectQuery->having($lHaving);
		return $lSelectQuery;
	}
	
	/**
	 * 
	 * @param string $lLeftTable
	 * @param string $pRightTable
	 * @param [] $pOn
	 * @return OnLogicalJunction|OnLiteral
	 */
	private function _getJoinColumns($lLeftTable, $pRightTable, $pOn) {
		if (count($pOn) == 1) {
			$lOn = new OnLiteral($lLeftTable, $pOn[0][0], Literal::EQUAL, $pRightTable, $pOn[0][1]);
		} else {
			$lOn = new OnLogicalJunction(LogicalJunction::CONJUNCTION);
			foreach ($pOn as $lOnLiteralArray) {
				$lOnLiteral = new OnLiteral($lLeftTable, $lOnLiteralArray[0], Literal::EQUAL, $pRightTable, $lOnLiteralArray[1]);
				$lOn->addLiteral($lOnLiteral);
			}
		}
		return $lOn;
	}
	
}