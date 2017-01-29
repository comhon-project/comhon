<?php
namespace comhon\object;

use comhon\database\DatabaseController;
use comhon\database\LogicalJunction;
use comhon\database\LogicalJunctionOptimizer;
use comhon\database\Literal;
use comhon\database\ComplexLiteral;
use comhon\database\HavingLiteral;
use comhon\database\SelectQuery;
use comhon\object\singleton\ModelManager;
use comhon\object\object\Object;
use comhon\object\object\ObjectArray;
use comhon\object\model\Model;
use comhon\object\model\ModelArray;
use comhon\object\model\SimpleModel;
use comhon\object\model\ModelContainer;
use comhon\object\model\ForeignProperty;
use comhon\controller\ForeignObjectLoader;
use comhon\controller\AggregationLoader;
use comhon\dataStructure\Tree;
use comhon\exception\PropertyException;
use \Exception;
use comhon\object\object\serialization\SqlTable;

class ComplexLoadRequest extends ObjectLoadRequest {
	
	private $mLeftJoins;
	private $mSelectQuery;
	private $mLiteralCollection = array();
	private $mLogicalJunction;
	private $mLoadLength;
	private $mOrder = array();
	private $mOffset;
	private $mSelectedColumns;
	private $mOptimizeLiterals = false;
	
	public function __construct($pModelName) {
		parent::__construct($pModelName);
		if (!$this->mModel->hasSqlTableUnit()) {
			trigger_error("error : resquested model must have a database serialization");
			throw new Exception("error : resquested model must have a database serialization");
		}
		$this->mLogicalJunction = new LogicalJunction(LogicalJunction::CONJUNCTION);
	}
	
	public function setMaxLength($pInteger) {
		$this->mLoadLength = $pInteger;
		return $this;
	}
	
	public function addOrder($pPropertyName, $pType = SelectQuery::ASC) {
		$this->mOrder[] = array($pPropertyName, $pType);
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
		$this->mSelectedColumns = array();
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
			$lObjectLoadRequest = new ComplexLoadRequest($pStdObject->model);
		} else if (isset($pStdObject->tree) && isset($pStdObject->tree->model)) {
			$lObjectLoadRequest = new ComplexLoadRequest($pStdObject->tree->model);
			$lObjectLoadRequest->importModelTree($pStdObject->tree);
		} else {
			throw new Exception("request doesn't have model");
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
				throw new Exception("order parameter must be an array");
			}
			foreach ($pStdObject->order as $lOrder) {
				if (!isset($lOrder->property)) {
					throw new Exception("an order element doesn't have property");
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
			throw new Exception("model tree doesn't have model");
		}
		if ($pModelTree->model != $this->mModel->getModelName()) {
			throw new Exception("root model in model tree is not the same as model specified in constructor");
		}
		$lSqlTable = $this->mModel->getSqlTableUnit();
		$lAlias = isset($pModelTree->id) ? $pModelTree->id : null;
		$this->mSelectQuery = new SelectQuery($lSqlTable->getValue('name'), $lAlias);
		
		$this->mLeftJoins = array();
		$lTableName = is_null($lAlias) ? $lSqlTable->getValue('name') : $lAlias;
		$this->mLeftJoins[$lTableName] = array('left_model' => $this->mModel, 'right_model' => $this->mModel, "right_table" => $lSqlTable->getValue('name'), "right_table_alias" => $lAlias);
		
		$lStack = array(array($this->mModel, $lSqlTable, $lAlias, $pModelTree));
		while (!empty($lStack)) {
			$lLastElement    = array_pop($lStack);
			$lLeftModel      = $lLastElement[0];
			$lLeftTable      = $lLastElement[1];
			$lLeftAliasTable = $lLastElement[2];
			$lLeftTableName  = is_null($lLeftAliasTable) ? $lLeftTable->getValue('name') : $lLeftAliasTable;
			$lChildrenNodes  = isset($lLastElement[3]->children) ? $lLastElement[3]->children : array();
			
			foreach ($lChildrenNodes as $lChildNode) {
				$lProperty                      = $lLeftModel->getProperty($lChildNode->property, true);
				$lLeftJoin                      = self::prepareLeftJoin($lLeftTable, $lLeftModel, $lProperty);
				$lLeftJoin["left_table"]        = $lLeftTableName;
				$lLeftJoin["right_table_alias"] = isset($lChildNode->id) ? $lChildNode->id : null;
				
				$this->mLeftJoins[$lLeftJoin["right_table_alias"]] = $lLeftJoin;
				$lStack[] = array($lProperty->getUniqueModel(), $lProperty->getSqlTableUnit(), $lLeftJoin["right_table_alias"], $lChildNode);
			}
		}
		return $this;
	}
	
	public function importLiteralCollection($pStdObjectLiteralCollection) {
		if (is_null($this->mLeftJoins)) {
			throw new \Exception('model tree must be set');
		}
		if (is_array($pStdObjectLiteralCollection)) {
			foreach ($pStdObjectLiteralCollection as $pStdObjectLiteral) {
				$this->addliteralToCollection(Literal::stdObjectToLiteral($pStdObjectLiteral, $this->mLeftJoins));
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
		if (is_null($this->mLeftJoins)) {
			$lMainTableName = $this->mModel->getSqlTableUnit()->getValue('name');
			$this->mSelectQuery = new SelectQuery($lMainTableName);
			$this->mLeftJoins   = array();
			$this->mLeftJoins[$lMainTableName] = array('left_model' => $this->mModel, 'right_model' => $this->mModel, "right_table" => $lMainTableName, "right_table_alias" => null);

			$lModels = array();
			$this->_getModelLiterals($pStdObjectLogicalJunction, $lMainTableName, $lModels);
			$this->_buildModelTree($lModels);
		}
		$this->setLogicalJunction(LogicalJunction::stdObjectToLogicalJunction($pStdObjectLogicalJunction, $this->mLeftJoins, $this->mLiteralCollection));
		
		array_shift($this->mLeftJoins);
		foreach ($this->mLeftJoins as $lLeftJoin) {
			$lAlias = array_key_exists('right_table_alias', $lLeftJoin) ? $lLeftJoin['right_table_alias'] : null;
			$this->mSelectQuery->addTable($lLeftJoin["right_table"], $lAlias, SelectQuery::LEFT_JOIN, $lLeftJoin["right_column"], $lLeftJoin["left_column"], $lLeftJoin["left_table"]);
		}
	}
	
	public function importLiteral($pStdObjectLiteral) {
		if (is_null($this->mLeftJoins)) {
			$this->_buildModelTree(array($pStdObjectLiteral->model => null));
		}
		$this->setLiteral(Literal::stdObjectToLiteral($pStdObjectLiteral, $this->mLeftJoins, $this->mLiteralCollection));
	}
	
	private function finalize() {
		if (is_null($this->mSelectQuery)) {
			throw new \Exception("query not initialized");
		}
		if ($this->mOptimizeLiterals) {
			$this->mLogicalJunction = LogicalJunctionOptimizer::optimizeLiterals($this->mLogicalJunction);
		}
		$this->mSelectQuery->setWhereLogicalJunction($this->mLogicalJunction);
		$this->mSelectQuery->setLimit($this->mLoadLength)->setOffset($this->mOffset);
		$this->mSelectQuery->setFirstTableCurrentTable();
		$this->_addColumns();
		$this->_addGroupedColumns();
		$this->_addOrderColumns();
	}
	
	/**
	 * execute request
	 * @param unknown $pFakeValue parent function has parameter so we add on to have same number of parameter
	 * @return array
	 */
	public function execute($pFakeValue = null) {
		$this->finalize();
		$lSqlTable = $this->mModel->getSqlTableUnit();
		$lSqlTable->loadValue("database");
		$lDbInstance = DatabaseController::getInstanceWithDataBaseObject($lSqlTable->getValue("database"));
		$lRows = $lDbInstance->executeSelectQuery($this->mSelectQuery);
		SqlTable::castStringifiedColumns($lRows, $this->mModel);
		
		return $this->_buildObjectsWithRows($lRows);
	}
	
	public function exportQuery() {
		$this->finalize();
		return $this->mSelectQuery;
	}
	
	private function _getModelLiterals($pStdObjectLogicalJunction, $pMainTableName, &$pModels) {
		if (isset($pStdObjectLogicalJunction->literals)) {
			foreach ($pStdObjectLogicalJunction->literals as $lLiteral) {
				if (!isset($lLiteral->model)) {
					throw new \Exception("malformed stdObject literal : ".json_encode($lLiteral));
				}
				ModelManager::getInstance()->getInstanceModel($lLiteral->model); // verify if model exists
				if (!array_key_exists($lLiteral->model, $pModels)) {
					$pModels[$lLiteral->model] = array();
				}
				if ($lLiteral->model == $this->mModel->getModelName()) {
					$lLiteral->node = $pMainTableName;
				}
				else {
					$pModels[$lLiteral->model][] = $lLiteral;
				}
				unset($lLiteral->model);
			}
		}
		if (isset($pStdObjectLogicalJunction->logicalJunctions)) {
			foreach ($pStdObjectLogicalJunction->logicalJunctions as $lLogicalJunction) {
				$this->_getModelLiterals($lLogicalJunction, $pMainTableName, $pModels);
			}
		}
	}
	
	/**
	 * add table to query $mSelectQuery
	 * @param array $pModels [modelName => alias]
	 * @throws Exception
	 */
	private function _buildModelTree($pModels) {
		if ((empty($pModels)) || ((count($pModels) == 1) && array_key_exists($this->mModel->getModelName(), $pModels))) {
			return;
		}
		$lTemporaryLeftJoins = array();
		$lStackVisitedModels = array();
		$lArrayVisitedModels = array();
		$lStack              = array();
		$lMainModelTableName = $this->mModel->getSqlTableUnit()->getValue('name');
		
		// stack initialisation with $pModel
		$this->_extendsStacks($this->mModel, $this->mModel->getSqlTableUnit(), $pModels, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
		// Depth-first search to build all left joins
		while (!empty($lStack)) {
			if ($lStack[count($lStack) - 1]["current"] != -1) {
				array_pop($lTemporaryLeftJoins);
				$lModelName = array_pop($lStackVisitedModels);
				$lArrayVisitedModels[$lModelName] -= 1;
			}
			$lStack[count($lStack) - 1]["current"]++;
			if ($lStack[count($lStack) - 1]["current"] < count($lStack[count($lStack) - 1]["properties"])) {
				$lStackIndex     = count($lStack) - 1;
				$lRightProperty  = $lStack[$lStackIndex]["properties"][$lStack[$lStackIndex]["current"]];
				$lRightModel     = $lRightProperty->getUniqueModel();
				$lRightModelName = $lRightModel->getModelName();
	
				if (array_key_exists($lRightModelName, $lArrayVisitedModels) && ($lArrayVisitedModels[$lRightModelName] > 0)) {
					$lStackVisitedModels[] = $lRightModelName;
					$lArrayVisitedModels[$lRightModelName] += 1;
					$lTemporaryLeftJoins[] = null;
					continue;
				}
				// add temporary leftJoin
				// add leftjoin if model $lRightModel is in literals ($pModels)
				$lTemporaryLeftJoins[] = self::prepareLeftJoin($lStack[$lStackIndex]["leftTable"], $lStack[$lStackIndex]["leftModel"], $lRightProperty);
				if (array_key_exists($lRightModelName, $pModels)) {
					$this->_addJoins($lTemporaryLeftJoins, $pModels[$lRightModelName]);
					$lTemporaryLeftJoins = array();
				}
				// add serializable properties to stack
				$this->_extendsStacks($lRightModel, $lRightProperty->getSqlTableUnit(), $pModels, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
				// if no added model we can delete last stack element
				if (empty($lStack[count($lStack) - 1]["properties"])) {
					array_pop($lStack);
				}
			}
			else {
				array_pop($lStack);
				array_pop($lTemporaryLeftJoins);
			}
		}
	}
	
	private function _addJoins($pLeftJoins, $pLiterals) {
		if (!empty($pLiterals)) {
			foreach ($pLeftJoins as $lLeftJoin) {
				$this->mLeftJoins[$lLeftJoin["right_table"]] = $lLeftJoin;
			}
			$lLiteralNode     = $pLeftJoins[count($pLeftJoins) - 1]["right_table"];
			foreach ($pLiterals as $pLiteral) {
				$pLiteral->node = $lLiteralNode;
			}
		}
	}
	
	private function _getAlias($pModelName, $pSuffix) {
		return is_null($pSuffix) ? $pModelName : $pModelName."_".$pSuffix;
	}
	
	private function _extendsStacks($pModel, $pSqlTable, $pLiteralsByModelName, &$pStack, &$pStackVisitedModels, &$pArrayVisitedModels) {
		if (array_key_exists($pModel->getModelName(), $pArrayVisitedModels) && array_key_exists($pModel->getModelName(), $pLiteralsByModelName)) {
			throw new Exception("Cannot resolve literal. Literal with model '".$pModel->getModelName()."' can be applied on several properties");
		}
		$pStack[] = array(
				"leftTable"  => $pSqlTable,
				"leftModel"  => $pModel,
				"properties" => $pModel->getForeignSerializableProperties("sqlTable"),
				"current"    => -1
		);
		$lModelName = $pModel->getModelName();
		$pStackVisitedModels[] = $lModelName;
		$pArrayVisitedModels[$lModelName] = array_key_exists($lModelName, $pArrayVisitedModels) ? $pArrayVisitedModels[$lModelName] + 1 : 1;
	}
	
	public static function prepareLeftJoin($pLeftTable, $pLeftModel, $pRightProperty) {
		if (!($pRightProperty instanceof ForeignProperty) || !$pRightProperty->hasSqlTableUnit()) {
			throw new Exception("property '{$lCurrentNode->property}' in model '{$lLeftModel->getModelName()}' hasn't sql serialization");
		}
		$lRightTable = $pRightProperty->getSqlTableUnit();
		$lRightModel = $pRightProperty->getUniqueModel();
		$lReturn = array(
			"left_model"   => $pLeftModel,
			"right_model"  => $lRightModel,
			"left_table"   => $pLeftTable->getValue('name'),
			"right_table"  => $lRightTable->getValue('name')
		);
		$lColumn = $pRightProperty->getSerializationName();
		if ($pRightProperty->isAggregation()) {
			$lColumns = [];
			foreach ($pRightProperty->getAggregationProperties() as $lAggregationProperty) {
				$lColumns[] = $lRightModel->getProperty($lAggregationProperty, true)->getSerializationName();
			}
			$lReturn["left_column"] = $pLeftModel->getFirstIdProperty()->getSerializationName();
			$lReturn["right_column"] = $lColumns;
		}else {
			$lReturn["left_column"] = $lColumn;
			$lReturn["right_column"] = $lRightModel->getFirstIdProperty()->getSerializationName();
		}
		return $lReturn;
	}
	
	/**
	 * add select columns to $mSelectQuery
	 */
	private function _addColumns() {
		$this->mSelectQuery->resetSelectColumns();
		if (is_null($this->mSelectedColumns)) {
			$this->mSelectQuery->addSelectTableColumns();
		} else {
			foreach ($this->mSelectedColumns as $lColumn) {
				$this->mSelectQuery->addSelectColumn($lColumn);
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
		$lSqlTable = $this->mModel->getSqlTableUnit();
		
		if (!is_null($lSqlTable->getInheritanceKey())) {
			foreach ($pRows as &$lRow) {
				$lModel = $lSqlTable->getInheritedModel($lRow, $this->mModel);
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