<?php
namespace comhon\request;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\LogicalJunctionOptimizer;
use comhon\database\Literal;
use comhon\database\ComplexLiteral;
use comhon\database\HavingLiteral;
use comhon\database\SelectQuery;
use comhon\model\singleton\ModelManager;
use comhon\object\Object;
use comhon\object\ObjectArray;
use comhon\model\Model;
use comhon\model\ModelArray;
use comhon\model\SimpleModel;
use comhon\model\ModelContainer;
use comhon\model\property\ForeignProperty;
use comhon\controller\ForeignObjectLoader;
use comhon\controller\AggregationLoader;
use comhon\exception\PropertyException;
use \Exception;
use comhon\object\serialization\SqlTable;
use comhon\database\TableNode;
use comhon\database\OnLiteral;
use comhon\database\OnLogicalJunction;
use comhon\database\Disjunction;

class ComplexLoadRequest extends ObjectLoadRequest {
	
	private $mModelByNodeId;
	private $mSelectQuery;
	private $mLiteralCollection = [];
	private $mLogicalJunction;
	private $mLoadLength;
	private $mOrder = [];
	private $mOffset;
	private $mSelectedColumns;
	private $mOptimizeLiterals = false;
	
	public function __construct($pModelName) {
		parent::__construct($pModelName);
		if (!$this->mModel->hasSqlTableUnit()) {
			throw new Exception('error : resquested model '.$this->mModel->getModelName().' must have a database serialization');
		}
		$this->mLogicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
	}
	
	public function setMaxLength($pInteger) {
		$this->mLoadLength = $pInteger;
		return $this;
	}
	
	public function addOrder($pPropertyName, $pType = SelectQuery::ASC) {
		$this->mOrder[] = [$pPropertyName, $pType];
		return $this;
	}
	
	public function setOffset($pInteger) {
		$this->mOffset = $pInteger;
		return $this;
	}
	
	public function opitimizeLiterals($pBoolean) {
		$this->mOptimizeLiterals = $pBoolean;
		return $this;
	}
	
	public function setLogicalJunction($pLogicalJunction) {
		$this->mLogicalJunction = $pLogicalJunction;
		return $this;
	}
	
	public function setLiteral($pLiteral) {
		$this->mLogicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
		$this->mLogicalJunction->addLiteral($pLiteral);
		return $this;
	}
	
	public function setPropertiesFilter($pPropertiesFilter) {
		$this->mSelectedColumns = [];
		foreach ($pPropertiesFilter as $pPropertyName) {
			$lProperty = $this->mModel->getProperty($pPropertyName, true);
			if (!$lProperty->isAggregation()) {
				$this->mSelectedColumns[] = $lProperty->getSerializationName();
			}
			else {
				throw new \Exception("property $pPropertyName can't be a filter property");
			}
		}
		return $this;
	}
	
	/**
	 * 
	 * @param stdClass $pStdObject
	 * @return ComplexLoadRequest
	 */
	public static function buildObjectLoadRequest($pStdObject) {
		if (isset($pStdObject->model)) {
			if (isset($pStdObject->tree)) {
				throw new Exception('request cannot have model property and tree property in same time');
			}
			$lObjectLoadRequest = new ComplexLoadRequest($pStdObject->model);
		} else if (isset($pStdObject->tree) && isset($pStdObject->tree->model)) {
			$lObjectLoadRequest = new ComplexLoadRequest($pStdObject->tree->model);
			$lObjectLoadRequest->importModelTree($pStdObject->tree);
		} else {
			throw new Exception('request doesn\'t have model');
		}
		if (isset($pStdObject->logicalJunction) && isset($pStdObject->literal)) {
			throw new Exception('can\'t have logicalJunction and literal properties in same time');
		}
		if (isset($pStdObject->literalCollection)) {
			$lObjectLoadRequest->importLiteralCollection($pStdObject->literalCollection);
		}
		if (isset($pStdObject->logicalJunction)) {
			$lObjectLoadRequest->importLogicalJunction($pStdObject->logicalJunction);
		}
		else if (isset($pStdObject->literal)) {
			$lObjectLoadRequest->importLiteral($pStdObject->literal);
		}
		if (isset($pStdObject->maxLength)) {
			$lObjectLoadRequest->setMaxLength($pStdObject->maxLength);
		}
		if (isset($pStdObject->offset)) {
			$lObjectLoadRequest->setOffset($pStdObject->offset);
		}
		if (isset($pStdObject->order)) {
			if (!is_array($pStdObject->order)) {
				throw new Exception('order parameter must be an array');
			}
			foreach ($pStdObject->order as $lOrder) {
				if (!isset($lOrder->property)) {
					throw new Exception('an order element doesn\'t have property');
				}
				$lObjectLoadRequest->addOrder($lOrder->property, isset($lOrder->type) ? $lOrder->type : SelectQuery::ASC);
			}
		}
		if (isset($pStdObject->requestChildren)) {
			$lObjectLoadRequest->requestChildren($pStdObject->requestChildren);
		}
		if (isset($pStdObject->loadForeignProperties)) {
			$lObjectLoadRequest->loadForeignProperties($pStdObject->loadForeignProperties);
		}
		return $lObjectLoadRequest;
	}
	
	/**
	 * @param stdClass $pModelTree
	 */
	public function importModelTree($pModelTree) {
		if (!isset($pModelTree->model)) {
			throw new Exception('model tree doesn\'t have model');
		}
		if ($pModelTree->model != $this->mModel->getModelName()) {
			throw new Exception('root model in model tree is not the same as model specified in constructor');
		}
		
		$lTableNode = new TableNode($this->mModel->getSqlTableUnit()->getSettings()->getValue('name'), isset($pModelTree->id) ? $pModelTree->id : null);
		$this->mSelectQuery = new SelectQuery($lTableNode);
		
		$this->mModelByNodeId = [$lTableNode->getExportName() => $this->mModel];
		
		$lStack = [[$this->mModel, $lTableNode, $pModelTree]];
		while (!empty($lStack)) {
			$lLastElement    = array_pop($lStack);
			$lLeftModel      = $lLastElement[0];
			$lLeftTable      = $lLastElement[1];
			
			if (isset($lLastElement[2]->children) && is_array($lLastElement[2]->children)) {
				foreach ($lLastElement[2]->children as $lChildNode) {
					$lRightTableAlias = isset($lChildNode->id) ? $lChildNode->id : null;
					$lProperty        = $lLeftModel->getProperty($lChildNode->property, true);
					$lJoinedTable     = self::prepareJoinedTable($lLeftTable, $lProperty, $lRightTableAlias);
					
					$this->mSelectQuery->join(SelectQuery::LEFT_JOIN, $lJoinedTable['table'], $lJoinedTable['join_on']);
					$this->mModelByNodeId[$lJoinedTable['table']->getExportName()] = $lJoinedTable['model'];
					$lStack[] = [$lProperty->getUniqueModel(), $lJoinedTable['table'], $lChildNode];
				}
			}
		}
		return $this;
	}
	
	public function importLiteralCollection($pStdObjectLiteralCollection) {
		if (is_null($this->mModelByNodeId)) {
			throw new \Exception('model tree must be set');
		}
		if (is_array($pStdObjectLiteralCollection)) {
			foreach ($pStdObjectLiteralCollection as $pStdObjectLiteral) {
				if (!isset($pStdObjectLiteral->node) || !array_key_exists($pStdObjectLiteral->node, $this->mModelByNodeId)) {
					throw new \Exception('node doesn\' exists or not recognized');
				}
				$this->addliteralToCollection(Literal::stdObjectToLiteral($pStdObjectLiteral, $this->mModelByNodeId[$pStdObjectLiteral->node], null, $this->mSelectQuery));
			}
		}
	}
	
	public function addliteralToCollection($pLiteral) {
		if (!$pLiteral->hasId()) {
			throw new \Exception('literal must have id');
		}
		if (array_key_exists($pLiteral->getId(), $this->mLiteralCollection)) {
			throw new \Exception("literal with id '{$pLiteral->getId()}' already added in collection");
		}
		$this->mLiteralCollection[$pLiteral->getId()] = $pLiteral;
	}
	
	public function importLogicalJunction($pStdObjectLogicalJunction) {
		if (is_null($this->mModelByNodeId)) {
			$lMainTableName = $this->mModel->getSqlTableUnit()->getSettings()->getValue('name');
			$lMainTableNode = new TableNode($lMainTableName);
			$this->mSelectQuery = new SelectQuery($lMainTableNode);
			$this->mModelByNodeId = [$lMainTableName => $this->mModel];

			$lLitralsByModelName = [];
			$this->_getLiteralsByModelName($pStdObjectLogicalJunction, $lMainTableName, $lLitralsByModelName);
			$this->_buildAndAddJoins($lLitralsByModelName);
		}
		$this->setLogicalJunction(LogicalJunction::stdObjectToLogicalJunction($pStdObjectLogicalJunction, $this->mModelByNodeId, $this->mLiteralCollection, $this->mSelectQuery));
	}
	
	public function importLiteral($pStdObjectLiteral) {
		if (is_null($this->mModelByNodeId)) {
			$lMainTableName = $this->mModel->getSqlTableUnit()->getSettings()->getValue('name');
			$lMainTableNode = new TableNode($lMainTableName);
			$this->mSelectQuery = new SelectQuery($lMainTableNode);
			$this->mModelByNodeId = [$lMainTableName => $this->mModel];
			
			$lLitralsByModelName = [];
			$this->_getLiteralByModelName($pStdObjectLiteral, $lMainTableName, $lLitralsByModelName);
			$this->_buildAndAddJoins($lLitralsByModelName);
		}
		if (!isset($pStdObjectLiteral->node) || !array_key_exists($pStdObjectLiteral->node, $this->mModelByNodeId)) {
			throw new \Exception('node doesn\' exists or not recognized');
		}
		$this->setLiteral(Literal::stdObjectToLiteral($pStdObjectLiteral, $this->mModelByNodeId[$pStdObjectLiteral->node], $this->mLiteralCollection, $this->mSelectQuery));
	}
	
	private function finalize() {
		if (is_null($this->mSelectQuery)) {
			throw new \Exception('query not initialized');
		}
		if ($this->mOptimizeLiterals) {
			$this->mLogicalJunction = LogicalJunctionOptimizer::optimizeLiterals($this->mLogicalJunction);
		}
		$this->mSelectQuery->where($this->mLogicalJunction);
		$this->mSelectQuery->limit($this->mLoadLength)->offset($this->mOffset);
		$this->mSelectQuery->setMainTableAsCurrentTable();
		$this->_addColumns();
		$this->_addGroupedColumns();
		$this->_addOrderColumns();
	}
	
	/**
	 * execute request
	 * @param unknown $pValue not used : parent function has parameter so we add one to have same number of parameter
	 * @return array
	 */
	public function execute($pValue = null) {
		$this->finalize();
		$lSqlTable = $this->mModel->getSqlTableUnit()->getSettings();
		$lSqlTable->loadValue('database');
		$lDbInstance = DatabaseController::getInstanceWithDataBaseObject($lSqlTable->getValue('database'));
		$lRows = $lDbInstance->executeSelectQuery($this->mSelectQuery);
		SqlTable::castStringifiedColumns($lRows, $this->mModel);
		
		return $this->_buildObjectsWithRows($lRows);
	}
	
	public function exportQuery() {
		$this->finalize();
		return $this->mSelectQuery;
	}
	
	private function _getLiteralsByModelName($pStdObjectLogicalJunction, $pMainTableName, &$pLitralsByModelName) {
		if (isset($pStdObjectLogicalJunction->literals)) {
			foreach ($pStdObjectLogicalJunction->literals as $lLiteral) {
				$this->_getLiteralByModelName($lLiteral, $pMainTableName, $pLitralsByModelName);
			}
		}
		if (isset($pStdObjectLogicalJunction->logicalJunctions)) {
			foreach ($pStdObjectLogicalJunction->logicalJunctions as $lLogicalJunction) {
				$this->_getLiteralsByModelName($lLogicalJunction, $pMainTableName, $pLitralsByModelName);
			}
		}
	}
	
	private function _getLiteralByModelName($pLiteral, $pMainTableName, &$pLitralsByModelName) {
		if (!isset($pLiteral->model)) {
			throw new \Exception('malformed stdObject literal : '.json_encode($pLiteral));
		}
		ModelManager::getInstance()->getInstanceModel($pLiteral->model); // verify if model exists
		if (!array_key_exists($pLiteral->model, $pLitralsByModelName)) {
			$pLitralsByModelName[$pLiteral->model] = [];
		}
		if ($pLiteral->model == $this->mModel->getModelName()) {
			$pLiteral->node = $pMainTableName;
		}
		else {
			$pLitralsByModelName[$pLiteral->model][] = $pLiteral;
		}
	}
	
	/**
	 * add table to query $mSelectQuery
	 * @param array $pLitralsByModelName
	 * @throws Exception
	 */
	private function _buildAndAddJoins($pLitralsByModelName) {
		if ((empty($pLitralsByModelName)) || ((count($pLitralsByModelName) == 1) && array_key_exists($this->mModel->getModelName(), $pLitralsByModelName))) {
			return;
		}
		$lTemporaryLeftJoins = [];
		$lStackVisitedModels = [];
		$lArrayVisitedModels = [];
		$lStack              = [];
		
		$this->_extendsStacks($this->mModel, $pLitralsByModelName, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
		// Depth-first search to build all left joins
		while (!empty($lStack)) {
			if ($lStack[count($lStack) - 1]['current'] != -1) {
				array_pop($lTemporaryLeftJoins);
				$lModelName = array_pop($lStackVisitedModels);
				$lArrayVisitedModels[$lModelName] -= 1;
			}
			$lStack[count($lStack) - 1]['current']++;
			if ($lStack[count($lStack) - 1]['current'] < count($lStack[count($lStack) - 1]['properties'])) {
				$lStackIndex     = count($lStack) - 1;
				$lRightProperty  = $lStack[$lStackIndex]['properties'][$lStack[$lStackIndex]['current']];
				$lRightModel     = $lRightProperty->getUniqueModel();
				$lRightModelName = $lRightModel->getModelName();
				
				$lHigherRightModelName = $lRightModelName;
				$lModel = $lRightModel->getExtendsModel();
				while (!is_null($lModel) && $lModel->getSerializationSettings() === $lRightModel->getSerializationSettings()) {
					$lHigherRightModelName = $lModel->getModelName();
					$lModel = $lModel->getExtendsModel();
				}
				
				if (array_key_exists($lHigherRightModelName, $lArrayVisitedModels) && ($lArrayVisitedModels[$lHigherRightModelName] > 0)) {
					$lStackVisitedModels[] = $lHigherRightModelName;
					$lArrayVisitedModels[$lHigherRightModelName] += 1;
					$lTemporaryLeftJoins[] = null;
					continue;
				}
				// add temporary leftJoin
				// add leftjoin if model $lRightModel is in literals ($pLitralsByModelName)
				$lLeftModel = $lStack[$lStackIndex]['left_model'];
				$lTemporaryLeftJoins[] = self::prepareJoinedTable($lLeftModel->getSqlTableUnit()->getSettings()->getValue('name'), $lRightProperty);
				if (array_key_exists($lRightModelName, $pLitralsByModelName)) {
					$this->_joinTables($lTemporaryLeftJoins, $pLitralsByModelName[$lRightModelName]);
					$lTemporaryLeftJoins = [];
				}
				// add serializable properties to stack
				$this->_extendsStacks($lRightModel, $pLitralsByModelName, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
				// if no added model we can delete last stack element
				if (empty($lStack[count($lStack) - 1]['properties'])) {
					array_pop($lStack);
				}
			}
			else {
				array_pop($lStack);
				array_pop($lTemporaryLeftJoins);
			}
		}
	}
	
	private function _joinTables($pJoinedTables, $pLiterals) {
		if (!empty($pLiterals)) {
			foreach ($pJoinedTables as $lJoinedTable) {
				$this->mSelectQuery->join(SelectQuery::LEFT_JOIN, $lJoinedTable['table'], $lJoinedTable['join_on']);
				$this->mModelByNodeId[$lJoinedTable['table']->getExportName()] = $lJoinedTable['model'];
			}
			$lNodeId = $pJoinedTables[count($pJoinedTables) - 1]['table']->getExportName();
			foreach ($pLiterals as $pLiteral) {
				$pLiteral->node = $lNodeId;
			}
		}
	}
	
	private function _extendsStacks($pModel, $pLiteralsByModelName, &$pStack, &$pStackVisitedModels, &$pArrayVisitedModels) {
		if (array_key_exists($pModel->getModelName(), $pArrayVisitedModels) && array_key_exists($pModel->getModelName(), $pLiteralsByModelName)) {
			throw new Exception('Cannot resolve literal. Literal with model \''.$pModel->getModelName().'\' can be applied on several properties');
		}
		$pStack[] = [
				'left_model' => $pModel,
				'properties' => $pModel->getForeignSerializableProperties('sqlTable'),
				'current'    => -1
		];
		
		$lHigherRightModelName = $pModel->getModelName();
		$lModel = $pModel->getExtendsModel();
		while (!is_null($lModel) && $lModel->getSerializationSettings() === $pModel->getSerializationSettings()) {
			$lHigherRightModelName = $lModel->getModelName();
			$lModel = $lModel->getExtendsModel();
		}
		
		$pStackVisitedModels[] = $lHigherRightModelName;
		$pArrayVisitedModels[$lHigherRightModelName] = array_key_exists($lHigherRightModelName, $pArrayVisitedModels) ? $pArrayVisitedModels[$lHigherRightModelName] + 1 : 1;
	}
	
	public static function prepareJoinedTable($pLeftTable, $pRightProperty, $pRightAliasTable = null, $pSelectAllColumns = false) {
		if (!($pRightProperty instanceof ForeignProperty) || !$pRightProperty->hasSqlTableUnit()) {
			throw new Exception("property '{$pRightProperty->getName()}' hasn't sql serialization");
		}
		$lRightModel = $pRightProperty->getUniqueModel();
		$lRightTable = new TableNode($lRightModel->getSqlTableUnit()->getSettings()->getValue('name'), $pRightAliasTable, $pSelectAllColumns);
		
		if ($pRightProperty->isAggregation()) {
			$lDisJunction = [];
			foreach ($pRightProperty->getAggregationProperties() as $lAggregationProperty) {
				$lRightForeignProperty = $lRightModel->getProperty($lAggregationProperty, true);
				
				if ($lRightForeignProperty->hasMultipleSerializationNames()) {
					$lOn = new OnLogicalJunction(LogicalJunction::CONJUNCTION);
					foreach ($lRightForeignProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
						$lOn->addLiteral(new OnLiteral(
							$pLeftTable,
							$lIdProperty->getSerializationName(),
							Literal::EQUAL,
							$lRightTable,
							$lSerializationName
						));
					}
					$lDisJunction[] = $lOn;
				} else {
					$lDisJunction[] = new OnLiteral(
						$pLeftTable,
						$lRightForeignProperty->getUniqueModel()->getFirstIdProperty()->getSerializationName(),
						Literal::EQUAL,
						$lRightTable,
						$lRightForeignProperty->getSerializationName()
					);
				}
			}
			if (count($lDisJunction) == 1) {
				$lOn = $lDisJunction[0];
			} else {
				$lOn = new OnLogicalJunction(LogicalJunction::DISJUNCTION);
				foreach ($lDisJunction as $lOnElement) {
					if ($lOnElement instanceof Literal) {
						$lOn->addLiteral($lOnElement);
					} else {
						$lOn->addLogicalJunction($lOnElement);
					}
				}
			}
		}else {
			if ($pRightProperty->hasMultipleSerializationNames()) {
				$lOn = new OnLogicalJunction(LogicalJunction::CONJUNCTION);
				foreach ($pRightProperty->getMultipleIdProperties() as $lSerializationName => $lIdProperty) {
					$lOn->addLiteral(new OnLiteral(
						$pLeftTable,
						$lSerializationName,
						Literal::EQUAL, 
						$lRightTable,
						$lIdProperty->getSerializationName()
					));
				}
			} else {
				$lOn = new OnLiteral(
					$pLeftTable, 
					$pRightProperty->getSerializationName(), 
					Literal::EQUAL, 
					$lRightTable, 
					$lRightModel->getFirstIdProperty()->getSerializationName()
				);
			}
		}
		return [
			'model'   => $lRightModel,
			'table'   => $lRightTable,
			'join_on' => $lOn
		];
	}
	
	/**
	 * add select columns to $mSelectQuery
	 */
	private function _addColumns() {
		if (is_null($this->mSelectedColumns)) {
			$this->mSelectQuery->getMainTable()->selectAllColumns(true);
		} else {
			$lMainTable = $this->mSelectQuery->getMainTable();
			foreach ($this->mSelectedColumns as $lColumn) {
				$lMainTable->addSelectedColumn($lColumn);
			}
		}
	}
	
	private function _addGroupedColumns() {
		$this->mSelectQuery->resetGroupColumns();
		foreach ($this->mModel->getIdProperties() as $lProperty) {
			$this->mSelectQuery->addGroupColumn($lProperty->getSerializationName());
		}
	}
	
	private function _addOrderColumns() {
		$this->mSelectQuery->resetOrderColumns();
		foreach ($this->mOrder as $lOrder) {
			$this->mSelectQuery->addOrderColumn($this->mModel->getProperty($lOrder[0], true)->getSerializationName(), $lOrder[1]);
		}
	}
	
	private function _buildObjectsWithRows($pRows) {
		$lSqlTableUnit = $this->mModel->getSqlTableUnit();
		
		if (!is_null($lSqlTableUnit->getInheritanceKey())) {
			foreach ($pRows as &$lRow) {
				$lModel = $lSqlTableUnit->getInheritedModel($lRow, $this->mModel);
				if ($lModel !== $this->mModel) {
					$lRow[Model::INHERITANCE_KEY] = $lModel->getModelName();
				}
			}
		}
		$lModelArray = new ModelArray($this->mModel, $this->mModel->getModelName());
		$lObjectArray = $lModelArray->fromSqlDatabase($pRows, Model::MERGE, SqlTable::getDatabaseConnectionTimeZone());
		
		return $this->_updateObjects($lObjectArray);
	}
	
}