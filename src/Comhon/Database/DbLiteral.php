<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Database;

use Comhon\Request\ComplexRequester;
use Comhon\Model\Model;
use Comhon\Logic\Clause;
use Comhon\Logic\Literal;
use Comhon\Object\UniqueObject;
use Comhon\Exception\Serialization\SerializationException;
use Comhon\Model\Property\MultipleForeignProperty;
use Comhon\Object\ComhonArray;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\Literal\NotAllowedLiteralException;
use Comhon\Request\LiteralBinder;

abstract class DbLiteral extends Literal {
	
	private static $index = 0;

	/** @var TableNode|string */
	protected $table;
	
	/** @var string */
	protected $column;
	
	/**
	 * @param TableNode|string $table
	 * @param string $column
	 * @param string $operator
	 */
	public function __construct($table, $column, $operator) {
		parent::__construct($operator);
		$this->table     = $table;
		$this->column    = $column;
	}
	
	/**
	 * 
	 * @return TableNode|string
	 */
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * @return string
	 */
	public function getPropertyName() {
		return $this->column;
	}
	
	/**
	 * build instance of Literal 
	 * 
	 * @param \Comhon\Object\UniqueObject $literal
	 * @param \Comhon\Model\Model $model
	 * @param \Comhon\Database\SelectQuery $selectQuery
	 * @param \Comhon\Database\DbLiteral[] $dbLiteralsById do not specify this parameter, it is used implicitly
	 * @throws \Exception
	 * @return DbLiteral
	 */
	public static function build(UniqueObject $literal, Model $model, $selectQuery = null, &$dbLiteralsById = []) {
		if ($literal->getModel()->getName() != 'Comhon\Logic\Simple\Having') {
			$literalModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Literal');
			if (!$literal->isA($literalModel)) {
				$expected = [
					ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Having')->getObjectInstance(false)->getComhonClass(),
					$literalModel->getObjectInstance(false)->getComhonClass()
				];
				throw new ArgumentException($literal, $expected, 1);
			}
		}
		$literal->validate();
		if (array_key_exists($literal->getId(), $dbLiteralsById)) {
			return $dbLiteralsById[$literal->getId()];
		}
		
		$table = ComplexRequester::getTableAliasWithModelNode($literal->getValue('node'));
		
		if ($literal->getModel()->getName() == 'Comhon\Logic\Simple\Having') {
			list($joinedTables, $on) = self::_getJoinedTablesFromQueue($model, $literal->getValue('queue'));
			$subSelectQuery = self::_setSubSelectQuery($joinedTables, $on, $literal);
			$rigthTable  = new TableNode($subSelectQuery, self::_getAlias(), false);
			
			if (!is_null($selectQuery)) {
				$selectQuery->join(SelectQuery::LEFT_JOIN, $rigthTable, self::_getJoinColumns($table, $rigthTable->getExportName(), $on));
			}
			if (count($on) == 1) {
				$databaseLiteral = new SimpleDbLiteral($rigthTable, $on[0][1], Literal::DIFF, null);
			} else {
				$databaseLiteral = new NotNullJoinLiteral();
				foreach ($on as $literalArray) {
					$databaseLiteral->addLiteral($rigthTable->getExportName(), $literalArray[1]);
				}
			}
		}
		else {
			$property = $model->getProperty($literal->getValue('property'), true);
			if (!LiteralBinder::isAllowedLiteral($property, $literal)) {
				throw new NotAllowedLiteralException($model, $property, $literal);
			}
			
			$setModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Literal\Set');
			$value = $literal->isA($setModel) ? $literal->getValue('values')->getValues() : $literal->getValue('value');
			
			$databaseLiteral = ($property instanceof MultipleForeignProperty) 
				? new MultiplePropertyDbLiteral($table, $property, $literal->getValue('operator'), $value)
				: new SimpleDbLiteral($table, $property->getSerializationName(), $literal->getValue('operator'), $value);
		}
		$databaseLiteral->setId($literal->getId());
		$dbLiteralsById[$literal->getId()] = $databaseLiteral;
		
		return $databaseLiteral;
	}
	
	/**
	 * 
	 * @param \Comhon\Model\Model $model
	 * @param \Comhon\Object\ComhonArray $queue
	 * @throws \Exception
	 * @return [[], []]
	 * - first element is array of joined tables
	 * - second element is array of columns that will be use for group, select and joins with principale query
	 */
	private static function _getJoinedTablesFromQueue(Model $model, ComhonArray $queue) {
		$firstTable    = new TableNode($model->getSqlTableSettings()->getValue('name'), null, false);
		$leftTable     = $firstTable;
		$firstModel    = $model;
		$leftModel     = $firstModel;
		$joinedTables  = [];
		$on            = null;
		
		if (!$firstModel->hasSqlTableSerialization()) {
			throw new SerializationException('resquested model '.$firstModel->getName().' must have a database serialization');
		}
		$database = $firstModel->getSqlTableSettings()->getValue('database');
		if (!($database instanceof UniqueObject)) {
			throw new SerializationException('not valid serialization settings, database information is missing');
		}
		$databaseId = $database->getId();
		
		foreach ($queue as $propertyName) {
			$property = $leftModel->getProperty($propertyName, true);
			$leftJoin = ComplexRequester::prepareJoinedTable($leftTable, $property, $databaseId, self::_getAlias());
			$joinedTables[] = $leftJoin;
			
			$leftModel   = $leftJoin['model'];
			$leftTable   = $leftJoin['table'];
		}
		$on = [];
		if (!($joinedTables[0]['join_on'] instanceof Clause) || $joinedTables[0]['join_on']->getType() !== Clause::DISJUNCTION) {
			$firstJoinedTable = $joinedTables[0];
			if ($firstJoinedTable['join_on'] instanceof Clause) {
				foreach ($firstJoinedTable['join_on']->getElements() as $literal) {
					$on[] = [$literal->getPropertyName(), $literal->getColumnRight()];
				}
			} else {
				$on[] = [$firstJoinedTable['join_on']->getPropertyName(), $firstJoinedTable['join_on']->getColumnRight()];
			}
		} else {
			array_unshift($joinedTables, ['table' => $firstTable, 'model' => $firstModel]);
			foreach ($model->getIdProperties() as $idProperty) {
				$column = $idProperty->getSerializationName();
				$on[] = [$column, $column];
			}
		}
		return [$joinedTables, $on];
	}
	
	/**
	 * 
	 * @param array $joinedTables 
	 * @param array $groupColumns
	 * @param \Comhon\Object\UniqueObject $literal
	 * @return SelectQuery
	 */
	private static function _setSubSelectQuery(array $joinedTables, array $groupColumns, UniqueObject $literal) {
		$mainTable   = $joinedTables[0]['table'];
		$selectQuery = new SelectQuery($mainTable);
		
		for ($i = 1; $i < count($joinedTables); $i++) {
			$joinTable = $joinedTables[$i];
			$selectQuery->join(SelectQuery::INNER_JOIN, $joinTable['table'], $joinTable['join_on']);
		}
			
		$lastTable = $joinedTables[count($joinedTables) - 1]['table'];
		$lastModel = $joinedTables[count($joinedTables) - 1]['model'];

		$selectQuery->setFocusOnMainTable();
		foreach ($groupColumns as $columns) {
			$mainTable->addSelectedColumn($columns[1]);
			$selectQuery->addGroup($columns[1]);
		}
		$havingFormula = $literal->getValue('having');
		
		if ($havingFormula->getModel()->getName() == 'Comhon\Logic\Having\Clause') {
			$having = HavingClause::buildHaving($havingFormula, $mainTable, $lastTable, $lastModel);
		} else {
			$table = $havingFormula->getModel()->getName() == 'Comhon\Logic\Having\Literal\Count' ? $mainTable : $lastTable;
			$having = HavingLiteral::buildHaving($havingFormula, $table, $lastModel);
		}
		$selectQuery->having($having);
		return $selectQuery;
	}
	
	/**
	 * 
	 * @param TableNode|string $leftTable
	 * @param TableNode|string $rightTable
	 * @param array $on
	 * @return \Comhon\Logic\Clause|OnLiteral
	 */
	private function _getJoinColumns($leftTable, $rightTable, $on) {
		if (count($on) == 1) {
			$onLiteral = new OnLiteral($leftTable, $on[0][0], Literal::EQUAL, $rightTable, $on[0][1]);
		} else {
			$onLiteral = new Clause(Clause::CONJUNCTION);
			foreach ($on as $onLiteralArray) {
				$onSubLiteral = new OnLiteral($leftTable, $onLiteralArray[0], Literal::EQUAL, $rightTable, $onLiteralArray[1]);
				$onLiteral->addLiteral($onSubLiteral);
			}
		}
		return $onLiteral;
	}
	
	/**
	 * get unique alias
	 *
	 * @return string
	 */
	private static function _getAlias() {
		return 'tq_'.self::$index++;
	}
	
}