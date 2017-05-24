<?php

/*
 * This file is part of the Comhon package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Comhon\Request;

use Comhon\Database\DatabaseController;
use Comhon\Database\LogicalJunction;
use Comhon\Database\LogicalJunctionOptimizer;
use Comhon\Database\Literal;
use Comhon\Database\SelectQuery;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Model\Model;
use Comhon\Model\ModelArray;
use Comhon\Model\Property\ForeignProperty;
use Comhon\Serialization\SqlTable;
use Comhon\Database\TableNode;
use Comhon\Database\OnLiteral;
use Comhon\Database\OnLogicalJunction;
use Comhon\Database\Disjunction;
use Comhon\Object\ObjectArray;
use Comhon\Interfacer\Interfacer;

class ComplexLoadRequest extends ObjectLoadRequest {
	
	private $modelByNodeId;
	private $selectQuery;
	private $literalCollection = [];
	private $logicalJunction;
	private $loadLength;
	private $order = [];
	private $offset;
	private $optimizeLiterals = false;
	
	public function __construct($modelName, $private = false) {
		parent::__construct($modelName, $private);
		if (!$this->model->hasSqlTableUnit()) {
			throw new \Exception('error : resquested model '.$this->model->getName().' must have a database serialization');
		}
		$this->logicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
	}
	
	public function setMaxLength($integer) {
		$this->loadLength = $integer;
		return $this;
	}
	
	public function addOrder($propertyName, $type = SelectQuery::ASC) {
		$this->order[] = [$propertyName, $type];
		return $this;
	}
	
	public function setOffset($integer) {
		$this->offset = $integer;
		return $this;
	}
	
	public function opitimizeLiterals($boolean) {
		$this->optimizeLiterals = $boolean;
		return $this;
	}
	
	public function setLogicalJunction($logicalJunction) {
		$this->logicalJunction = $logicalJunction;
		return $this;
	}
	
	public function setLiteral($literal) {
		$this->logicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
		$this->logicalJunction->addLiteral($literal);
		return $this;
	}
	
	/**
	 * 
	 * @param stdClass $stdObject
	 * @return ComplexLoadRequest
	 */
	public static function buildObjectLoadRequest($stdObject, $private = false) {
		if (isset($stdObject->model)) {
			if (isset($stdObject->tree)) {
				throw new \Exception('request cannot have model property and tree property in same time');
			}
			$objectLoadRequest = new ComplexLoadRequest($stdObject->model, $private);
		} else if (isset($stdObject->tree) && isset($stdObject->tree->model)) {
			$objectLoadRequest = new ComplexLoadRequest($stdObject->tree->model, $private);
			$objectLoadRequest->importModelTree($stdObject->tree);
		} else {
			throw new \Exception('request doesn\'t have model');
		}
		if (isset($stdObject->logicalJunction) && isset($stdObject->literal)) {
			throw new \Exception('can\'t have logicalJunction and literal properties in same time');
		}
		if (isset($stdObject->literalCollection)) {
			$objectLoadRequest->importLiteralCollection($stdObject->literalCollection);
		}
		if (isset($stdObject->logicalJunction)) {
			$objectLoadRequest->importLogicalJunction($stdObject->logicalJunction);
		}
		else if (isset($stdObject->literal)) {
			$objectLoadRequest->importLiteral($stdObject->literal);
		}
		if (isset($stdObject->maxLength)) {
			$objectLoadRequest->setMaxLength($stdObject->maxLength);
		}
		if (isset($stdObject->properties) && is_array($stdObject->properties)) {
			$objectLoadRequest->setPropertiesFilter($stdObject->properties);
		}
		if (isset($stdObject->offset)) {
			$objectLoadRequest->setOffset($stdObject->offset);
		}
		if (isset($stdObject->order)) {
			if (!is_array($stdObject->order)) {
				throw new \Exception('order parameter must be an array');
			}
			foreach ($stdObject->order as $order) {
				if (!isset($order->property)) {
					throw new \Exception('an order element doesn\'t have property');
				}
				$objectLoadRequest->addOrder($order->property, isset($order->type) ? $order->type : SelectQuery::ASC);
			}
		}
		if (isset($stdObject->requestChildren)) {
			$objectLoadRequest->requestChildren($stdObject->requestChildren);
		}
		if (isset($stdObject->loadForeignProperties)) {
			$objectLoadRequest->loadForeignProperties($stdObject->loadForeignProperties);
		}
		return $objectLoadRequest;
	}
	
	/**
	 * @param stdClass $modelTree
	 */
	public function importModelTree($modelTree) {
		if (!isset($modelTree->model)) {
			throw new \Exception('model tree doesn\'t have model');
		}
		if ($modelTree->model != $this->model->getName()) {
			throw new \Exception('root model in model tree is not the same as model specified in constructor');
		}
		
		$tableNode = new TableNode($this->model->getSqlTableUnit()->getSettings()->getValue('name'), isset($modelTree->id) ? $modelTree->id : null);
		$this->selectQuery = new SelectQuery($tableNode);
		
		$this->modelByNodeId = [$tableNode->getExportName() => $this->model];
		
		$stack = [[$this->model, $tableNode, $modelTree]];
		while (!empty($stack)) {
			$lastElement    = array_pop($stack);
			$leftModel      = $lastElement[0];
			$leftTable      = $lastElement[1];
			
			if (isset($lastElement[2]->children) && is_array($lastElement[2]->children)) {
				foreach ($lastElement[2]->children as $childNode) {
					$rightTableAlias = isset($childNode->id) ? $childNode->id : null;
					$property        = $leftModel->getProperty($childNode->property, true);
					$joinedTable     = self::prepareJoinedTable($leftTable, $property, $rightTableAlias);
					
					$this->selectQuery->join(SelectQuery::LEFT_JOIN, $joinedTable['table'], $joinedTable['join_on']);
					$this->modelByNodeId[$joinedTable['table']->getExportName()] = $joinedTable['model'];
					$stack[] = [$property->getUniqueModel(), $joinedTable['table'], $childNode];
				}
			}
		}
		return $this;
	}
	
	public function importLiteralCollection($stdObjectLiteralCollection) {
		if (is_null($this->modelByNodeId)) {
			throw new \Exception('model tree must be set');
		}
		if (is_array($stdObjectLiteralCollection)) {
			foreach ($stdObjectLiteralCollection as $stdObjectLiteral) {
				if (!isset($stdObjectLiteral->node) || !array_key_exists($stdObjectLiteral->node, $this->modelByNodeId)) {
					throw new \Exception('node doesn\' exists or not recognized');
				}
				$this->addliteralToCollection(Literal::stdObjectToLiteral($stdObjectLiteral, $this->modelByNodeId[$stdObjectLiteral->node], null, $this->selectQuery, $this->private));
			}
		}
	}
	
	public function addliteralToCollection($literal) {
		if (!$literal->hasId()) {
			throw new \Exception('literal must have id');
		}
		if (array_key_exists($literal->getId(), $this->literalCollection)) {
			throw new \Exception("literal with id '{$literal->getId()}' already added in collection");
		}
		$this->literalCollection[$literal->getId()] = $literal;
	}
	
	public function importLogicalJunction($stdObjectLogicalJunction) {
		if (is_null($this->modelByNodeId)) {
			$mainTableName = $this->model->getSqlTableUnit()->getSettings()->getValue('name');
			$mainTableNode = new TableNode($mainTableName);
			$this->selectQuery = new SelectQuery($mainTableNode);
			$this->modelByNodeId = [$mainTableName => $this->model];

			$litralsByModelName = [];
			$this->_getLiteralsByModelName($stdObjectLogicalJunction, $mainTableName, $litralsByModelName);
			$this->_buildAndAddJoins($litralsByModelName);
		}
		$this->setLogicalJunction(LogicalJunction::stdObjectToLogicalJunction($stdObjectLogicalJunction, $this->modelByNodeId, $this->literalCollection, $this->selectQuery, $this->private));
	}
	
	public function importLiteral($stdObjectLiteral) {
		if (is_null($this->modelByNodeId)) {
			$mainTableName = $this->model->getSqlTableUnit()->getSettings()->getValue('name');
			$mainTableNode = new TableNode($mainTableName);
			$this->selectQuery = new SelectQuery($mainTableNode);
			$this->modelByNodeId = [$mainTableName => $this->model];
			
			$litralsByModelName = [];
			$this->_getLiteralByModelName($stdObjectLiteral, $mainTableName, $litralsByModelName);
			$this->_buildAndAddJoins($litralsByModelName);
		}
		if (!isset($stdObjectLiteral->node) || !array_key_exists($stdObjectLiteral->node, $this->modelByNodeId)) {
			throw new \Exception('node doesn\' exists or not recognized');
		}
		$this->setLiteral(Literal::stdObjectToLiteral($stdObjectLiteral, $this->modelByNodeId[$stdObjectLiteral->node], $this->literalCollection, $this->selectQuery, $this->private));
	}
	
	private function finalize() {
		if (is_null($this->selectQuery)) {
			throw new \Exception('query not initialized');
		}
		if ($this->optimizeLiterals) {
			$this->logicalJunction = LogicalJunctionOptimizer::optimizeLiterals($this->logicalJunction);
		}
		$this->selectQuery->where($this->logicalJunction);
		$this->selectQuery->limit($this->loadLength)->offset($this->offset);
		$this->selectQuery->setMainTableAsCurrentTable();
		$this->_addColumns();
		$this->_addGroupedColumns();
		$this->_addOrderColumns();
	}
	
	/**
	 * execute resquest and return resulting object
	 * @return ObjectArray
	 */
	public function execute() {
		$this->finalize();
		$sqlTable = $this->model->getSqlTableUnit()->getSettings();
		$sqlTable->loadValue('database');
		$dbInstance = DatabaseController::getInstanceWithDataBaseObject($sqlTable->getValue('database'));
		$rows = $dbInstance->executeSelectQuery($this->selectQuery);
		SqlTable::castStringifiedColumns($rows, $this->model);
		
		return $this->_buildObjectsWithRows($rows);
	}
	
	public function exportQuery() {
		$this->finalize();
		return $this->selectQuery;
	}
	
	private function _getLiteralsByModelName($stdObjectLogicalJunction, $mainTableName, &$litralsByModelName) {
		if (isset($stdObjectLogicalJunction->literals)) {
			foreach ($stdObjectLogicalJunction->literals as $literal) {
				$this->_getLiteralByModelName($literal, $mainTableName, $litralsByModelName);
			}
		}
		if (isset($stdObjectLogicalJunction->logicalJunctions)) {
			foreach ($stdObjectLogicalJunction->logicalJunctions as $logicalJunction) {
				$this->_getLiteralsByModelName($logicalJunction, $mainTableName, $litralsByModelName);
			}
		}
	}
	
	private function _getLiteralByModelName($literal, $mainTableName, &$litralsByModelName) {
		if (!isset($literal->model)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($literal));
		}
		ModelManager::getInstance()->getInstanceModel($literal->model); // verify if model exists
		if (!array_key_exists($literal->model, $litralsByModelName)) {
			$litralsByModelName[$literal->model] = [];
		}
		if ($literal->model == $this->model->getName()) {
			$literal->node = $mainTableName;
		}
		else {
			$litralsByModelName[$literal->model][] = $literal;
		}
	}
	
	/**
	 * add table to query $selectQuery
	 * @param array $litralsByModelName
	 * @throws \Exception
	 */
	private function _buildAndAddJoins($litralsByModelName) {
		if ((empty($litralsByModelName)) || ((count($litralsByModelName) == 1) && array_key_exists($this->model->getName(), $litralsByModelName))) {
			return;
		}
		$temporaryLeftJoins = [];
		$stackVisitedModels = [];
		$arrayVisitedModels = [];
		$stack              = [];
		
		$this->_extendsStacks($this->model, $litralsByModelName, $stack, $stackVisitedModels, $arrayVisitedModels);
	
		// Depth-first search to build all left joins
		while (!empty($stack)) {
			if ($stack[count($stack) - 1]['current'] != -1) {
				array_pop($temporaryLeftJoins);
				$modelName = array_pop($stackVisitedModels);
				$arrayVisitedModels[$modelName] -= 1;
			}
			$stack[count($stack) - 1]['current']++;
			if ($stack[count($stack) - 1]['current'] < count($stack[count($stack) - 1]['properties'])) {
				$stackIndex     = count($stack) - 1;
				$rightProperty  = $stack[$stackIndex]['properties'][$stack[$stackIndex]['current']];
				$rightModel     = $rightProperty->getUniqueModel();
				$rightModelName = $rightModel->getName();
				
				$higherRightModelName = $rightModelName;
				$model = $rightModel->getExtendsModel();
				while (!is_null($model) && $model->getSerializationSettings() === $rightModel->getSerializationSettings()) {
					$higherRightModelName = $model->getName();
					$model = $model->getExtendsModel();
				}
				
				if (array_key_exists($higherRightModelName, $arrayVisitedModels) && ($arrayVisitedModels[$higherRightModelName] > 0)) {
					$stackVisitedModels[] = $higherRightModelName;
					$arrayVisitedModels[$higherRightModelName] += 1;
					$temporaryLeftJoins[] = null;
					continue;
				}
				// add temporary leftJoin
				// add leftjoin if model $rightModel is in literals ($litralsByModelName)
				$leftModel = $stack[$stackIndex]['left_model'];
				$temporaryLeftJoins[] = self::prepareJoinedTable($leftModel->getSqlTableUnit()->getSettings()->getValue('name'), $rightProperty);
				if (array_key_exists($rightModelName, $litralsByModelName)) {
					$this->_joinTables($temporaryLeftJoins, $litralsByModelName[$rightModelName]);
					$temporaryLeftJoins = [];
				}
				// add serializable properties to stack
				$this->_extendsStacks($rightModel, $litralsByModelName, $stack, $stackVisitedModels, $arrayVisitedModels);
	
				// if no added model we can delete last stack element
				if (empty($stack[count($stack) - 1]['properties'])) {
					array_pop($stack);
				}
			}
			else {
				array_pop($stack);
				array_pop($temporaryLeftJoins);
			}
		}
	}
	
	private function _joinTables($joinedTables, $literals) {
		if (!empty($literals)) {
			foreach ($joinedTables as $joinedTable) {
				$this->selectQuery->join(SelectQuery::LEFT_JOIN, $joinedTable['table'], $joinedTable['join_on']);
				$this->modelByNodeId[$joinedTable['table']->getExportName()] = $joinedTable['model'];
			}
			$nodeId = $joinedTables[count($joinedTables) - 1]['table']->getExportName();
			foreach ($literals as $literal) {
				$literal->node = $nodeId;
			}
		}
	}
	
	private function _extendsStacks($model, $literalsByModelName, &$stack, &$stackVisitedModels, &$arrayVisitedModels) {
		if (array_key_exists($model->getName(), $arrayVisitedModels) && array_key_exists($model->getName(), $literalsByModelName)) {
			throw new \Exception('Cannot resolve literal. Literal with model \''.$model->getName().'\' can be applied on several properties');
		}
		$stack[] = [
				'left_model' => $model,
				'properties' => $model->getForeignSerializableProperties('sqlTable'),
				'current'    => -1
		];
		
		$higherRightModelName = $model->getName();
		$extendsModel = $model->getExtendsModel();
		while (!is_null($extendsModel) && $extendsModel->getSerializationSettings() === $model->getSerializationSettings()) {
			$higherRightModelName = $extendsModel->getName();
			$extendsModel = $extendsModel->getExtendsModel();
		}
		
		$stackVisitedModels[] = $higherRightModelName;
		$arrayVisitedModels[$higherRightModelName] = array_key_exists($higherRightModelName, $arrayVisitedModels) ? $arrayVisitedModels[$higherRightModelName] + 1 : 1;
	}
	
	public static function prepareJoinedTable($leftTable, $rightProperty, $rightAliasTable = null, $selectAllColumns = false) {
		if (!($rightProperty instanceof ForeignProperty) || !$rightProperty->hasSqlTableUnit()) {
			throw new \Exception("property '{$rightProperty->getName()}' hasn't sql serialization");
		}
		$rightModel = $rightProperty->getUniqueModel();
		$rightTable = new TableNode($rightModel->getSqlTableUnit()->getSettings()->getValue('name'), $rightAliasTable, $selectAllColumns);
		
		if ($rightProperty->isAggregation()) {
			$disJunction = [];
			foreach ($rightProperty->getAggregationProperties() as $aggregationProperty) {
				$rightForeignProperty = $rightModel->getProperty($aggregationProperty, true);
				
				if ($rightForeignProperty->hasMultipleSerializationNames()) {
					$on = new OnLogicalJunction(LogicalJunction::CONJUNCTION);
					foreach ($rightForeignProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
						$on->addLiteral(new OnLiteral(
							$leftTable,
							$idProperty->getSerializationName(),
							Literal::EQUAL,
							$rightTable,
							$serializationName
						));
					}
					$disJunction[] = $on;
				} else {
					$disJunction[] = new OnLiteral(
						$leftTable,
						$rightForeignProperty->getUniqueModel()->getFirstIdProperty()->getSerializationName(),
						Literal::EQUAL,
						$rightTable,
						$rightForeignProperty->getSerializationName()
					);
				}
			}
			if (count($disJunction) == 1) {
				$on = $disJunction[0];
			} else {
				$on = new OnLogicalJunction(LogicalJunction::DISJUNCTION);
				foreach ($disJunction as $onElement) {
					if ($onElement instanceof Literal) {
						$on->addLiteral($onElement);
					} else {
						$on->addLogicalJunction($onElement);
					}
				}
			}
		}else {
			if ($rightProperty->hasMultipleSerializationNames()) {
				$on = new OnLogicalJunction(LogicalJunction::CONJUNCTION);
				foreach ($rightProperty->getMultipleIdProperties() as $serializationName => $idProperty) {
					$on->addLiteral(new OnLiteral(
						$leftTable,
						$serializationName,
						Literal::EQUAL, 
						$rightTable,
						$idProperty->getSerializationName()
					));
				}
			} else {
				$on = new OnLiteral(
					$leftTable, 
					$rightProperty->getSerializationName(), 
					Literal::EQUAL, 
					$rightTable, 
					$rightModel->getFirstIdProperty()->getSerializationName()
				);
			}
		}
		return [
			'model'   => $rightModel,
			'table'   => $rightTable,
			'join_on' => $on
		];
	}
	
	/**
	 * add select columns to $selectQuery
	 */
	private function _addColumns() {
		
		if (empty($this->propertiesFilter)) {
			$this->selectQuery->getMainTable()->selectAllColumns(true);
		}
		else {
			$selectedColumns = [];
			foreach ($this->propertiesFilter as $propertyName) {
				$selectedColumns[] = $this->model->getProperty($propertyName, true)->getSerializationName();
			}
			$mainTable = $this->selectQuery->getMainTable();
			foreach ($selectedColumns as $column) {
				$mainTable->addSelectedColumn($column);
			}
		}
	}
	
	private function _addGroupedColumns() {
		$this->selectQuery->resetGroupColumns();
		foreach ($this->model->getIdProperties() as $property) {
			$this->selectQuery->addGroupColumn($property->getSerializationName());
		}
	}
	
	private function _addOrderColumns() {
		$this->selectQuery->resetOrderColumns();
		foreach ($this->order as $order) {
			$this->selectQuery->addOrderColumn($this->model->getProperty($order[0], true)->getSerializationName(), $order[1]);
		}
	}
	
	private function _buildObjectsWithRows($rows) {
		$sqlTableUnit = $this->model->getSqlTableUnit();
		
		if (!is_null($sqlTableUnit->getInheritanceKey())) {
			foreach ($rows as &$row) {
				$model = $sqlTableUnit->getInheritedModel($row, $this->model);
				if ($model!== $this->model) {
					$row[Interfacer::INHERITANCE_KEY] = $model->getName();
				}
			}
		}
		$modelArray = new ModelArray($this->model, $this->model->getName());
		$objectArray = $modelArray->import($rows, SqlTable::getInterfacer());
		
		return $this->_updateObjects($objectArray);
	}
	
}