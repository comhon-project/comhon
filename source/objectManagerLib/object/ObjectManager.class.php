<?php
namespace objectManagerLib\object;

use objectManagerLib\database\DatabaseController;
use objectManagerLib\database\LinkedConditions;
use objectManagerLib\database\ConditionOptimizer;
use objectManagerLib\database\ConditionExtended;
use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\database\JoinedTables;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\ModelForeign;
use objectManagerLib\object\model\SerializableProperty;
use objectManagerLib\controller\ForeignObjectReplacer;
use objectManagerLib\controller\ForeignObjectLoader;
use objectManagerLib\object\model\ModelContainer;

class ObjectManager {
	
	const CREATE = "create";
	const UPDATE = "update";
	const DELETE = "delete";
	const DELETE_CASCADE = "deleteCascade";
	const CREATE_OR_UPDATE = "createOrUpdate";

	/**
	 * 
	 * @param string $pModelName model name of objects that you want to retrieve
	 * @param LinkedConditions $pCondition condition(s) for query (can be an object Condition)
	 * @param integer $pLoadDepth
	 * @param integer $pLoadLength
	 * @param boolean $pLoadForeignObject
	 * @param boolean $pOptimizeConditions
	 * @param string $pKey
	 * @return array
	 */
	public function getObjects($pModelName, $pCondition = null, $pLoadDepth = 0, $pLoadLength = null, $pLoadForeignObject = false, $pOptimizeConditions = false, $pKey = null) {
		$lReturn = array();
		$lModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
		if (!is_null($pKey) && (!$lModel->hasProperty($pKey) || ! ($lModel->getProperty($pKey)->getModel() instanceof SimpleModel))) {
			trigger_error("key '".$pKey."' unauthorized");
			throw new \Exception("key '".$pKey."' unauthorized");
		}
		if (is_null($lSqlTable = $lModel->getSqlTableUnit())) {
			trigger_error("error : resquested model must have a database serialization");
			throw new \Exception("error : resquested model must have a database serialization");
		}
		if (is_null($pCondition)) {
			$lLinkedConditions = new LinkedConditions("and");
		}else if ($pCondition instanceof LinkedConditions) {
			$lLinkedConditions = $pCondition;
		}else {
			$lLinkedConditions = new LinkedConditions("and");
			$lLinkedConditions->addCondition($pCondition);
		}
		if ($pOptimizeConditions) {
			$lLinkedConditions = ConditionOptimizer::optimizeConditions($lLinkedConditions);
		}
		$lSqlTable->loadValue("database");
		$lJoinedTables = new JoinedTables($lSqlTable->getValue("name"));
		$this->_addTablesForQuery($lJoinedTables, $lModel, $lLinkedConditions);
		
		$lDbInstance = DatabaseController::getInstanceWithDataBaseObject($lSqlTable->getValue("database"));
		$lRows = $lDbInstance->select($lJoinedTables, $this->_getColumnsByTable($lModel), $lLinkedConditions, $this->_getGroupedColumns($lModel));
		
		return $this->_buildObjectsWithRows($lModel, $lRows, $pLoadDepth, $pLoadForeignObject, $pKey);
	}
	
	private function _addTablesForQuery($pJoinedTables, $pModel, $pLinkedConditions) {
		$lTemporaryLeftJoins = array();
		$lStackVisitedModels = array();
		$lArrayVisitedModels = array();
		$lStack = array();
	
		$lModelsByName = array();
		$lConditions = $pLinkedConditions->getFlattenedConditions();
		foreach ($lConditions as $lCondition) {
			if (! ($lCondition instanceof ConditionExtended)) {
				throw new \Exception("all conditions must be instance of ConditionExtended to know related model");
			}
			$lModelsByName[$lCondition->getModel()->getModelName()] = $lCondition->getModel();
		}
	
		// stack initialisation with $pModel
		$this->_extendsStacks($pModel, $pModel->getSqlTableUnit(), $lModelsByName, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
		// Depth-first search to build all left joins
		while ((count($lStack) > 0)) {
			if ($lStack[count($lStack) - 1]["current"] != -1) {
				array_pop($lTemporaryLeftJoins);
				$lModelName = array_pop($lStackVisitedModels);
				$lArrayVisitedModels[$lModelName] -= 1;
			}
			$lStack[count($lStack) - 1]["current"]++;
			if ($lStack[count($lStack) - 1]["current"] < count($lStack[count($lStack) - 1]["properties"])) {
				$lStackIndex    = count($lStack) - 1;
				$lRightProperty = $lStack[$lStackIndex]["properties"][$lStack[$lStackIndex]["current"]];
				$lRightModel    = ($lRightProperty->getModel() instanceof ModelContainer) ? $lRightProperty->getModel()->getModel() : $lRightProperty->getModel();
	
				if (array_key_exists($lRightModel->getModelName(), $lArrayVisitedModels) && ($lArrayVisitedModels[$lRightModel->getModelName()] > 0)) {
					$lStackVisitedModels[] = $lRightModel->getModelName();
					$lArrayVisitedModels[$lRightModel->getModelName()] += 1;
					$lTemporaryLeftJoins[] = null;
					continue;
				}
				// add temporary leftJoin
				// add leftjoin if model $lRightModel is in conditions ($lModelsByName)
				$lTemporaryLeftJoins[] = $this->_prepareLeftJoin($lStack[$lStackIndex]["leftTable"], $lStack[$lStackIndex]["leftId"], $lRightModel, $lRightProperty);
				if (array_key_exists($lRightModel->getModelName(), $lModelsByName)) {
					foreach ($lTemporaryLeftJoins as $lLeftJoin) {
						$pJoinedTables->addTable($lLeftJoin["right_table"], null, JoinedTables::LEFT_JOIN, $lLeftJoin["right_column"], $lLeftJoin["left_column"], $lLeftJoin["left_table"]);
					}
					$lTemporaryLeftJoins = array();
				}
				// add serializable properties to stack
				$this->_extendsStacks($lRightModel, $lRightProperty->getSqlTableUnit(), $lModelsByName, $lStack, $lStackVisitedModels, $lArrayVisitedModels);
	
				// if no added model we can delete last stack element
				if (count($lStack[count($lStack) - 1]["properties"]) == 0) {
					array_pop($lStack);
				}
			}
			else {
				array_pop($lStack);
				array_pop($lTemporaryLeftJoins);
			}
		}
	}
	
	private function _extendsStacks($pModel, $pSqlTable, $pModelsByName, &$pStack, &$pStackVisitedModels, &$pArrayVisitedModels) {
		if (array_key_exists($pModel->getModelName(), $pArrayVisitedModels) && array_key_exists($pModel->getModelName(), $pModelsByName)) {
			throw new Exception("Cannot resolve condition. Condition with model '".$pModel->getModelName()."' can be applied on several properties");
		}
		$lIds = $pModel->getIds();
		$pStack[] = array(
				"leftId"     => (is_array($lIds) && (count($lIds) > 0)) ? $pModel->getProperty($lIds[0])->getSerializationName() : null,
				"leftTable"  => $pSqlTable,
				"properties" => $pModel->getSerializableProperties("sqlTable"),
				"current"    => -1
		);
		$lModelName = $pModel->getModelName();
		$pStackVisitedModels[] = $lModelName;
		$pArrayVisitedModels[$lModelName] = array_key_exists($lModelName, $pArrayVisitedModels) ? $pArrayVisitedModels[$lModelName] + 1 : 1;
	}
	
	private function _prepareLeftJoin($pLeftTable, $pLeftId, $pRightModel, $pRightProperty) {
		$lRightTable = $pRightProperty->getSqlTableUnit();
		$lReturn = array(
				"left_table"   => $pLeftTable->getValue("name"),
				"right_table"  => $lRightTable->getValue("name")
		);
		if ($pRightProperty->getModel() instanceof ModelForeign) {
			$lIds = $pRightModel->getIds();
			$lReturn["left_column"] = $pRightProperty->getSerializationName();
			$lReturn["right_column"] = $pRightModel->getProperty($lIds[0])->getSerializationName();
		}else {
			$lRightColumns = array();
			foreach ($pRightProperty->getForeignIds() as $lPropertyId) {
				$lRightColumns[] = $pRightModel->getProperty($lPropertyId)->getSerializationName();
			}
			$lReturn["left_column"] = $pLeftId;
			$lReturn["right_column"] = $lRightColumns;
		}
		$lReturn["right_model"] = $pRightModel;
		return $lReturn;
	}
	
	/**
	 * @param array $pObjects each object is a serializable property or a model
	 * @return string
	 */
	private function _getColumnsByTable($pModel) {
		$lColumnsByTable = array();
		if (is_null($lSqlTable = $pModel->getSqlTableUnit())) {
			trigger_error("must have a database serialization");
			throw new \Exception("must have a database serialization");
		}
		$lTableName = $lSqlTable->getValue("name");
		$lColumnsByTable[$lTableName] = array();
		foreach ($pModel->getProperties() as $lProperty) {
			if (!($lProperty instanceof SerializableProperty) || is_null($lProperty->getForeignIds())) {
				$lColumnsByTable[$lTableName][] = array($lProperty->getSerializationName());
			}
		}
		return $lColumnsByTable;
	}
	
	private function _getGroupedColumns($pModel) {
		$lArray = array();
		$lTableName = $pModel->getSqlTableUnit()->getValue("name");
		foreach ($pModel->getIds() as $lPropertyName) {
			$lArray[] = $lTableName.".".$pModel->getProperty($lPropertyName)->getSerializationName();
		}
		return $lArray;
	}
	
	private function _buildObjectsWithRows($pModel, $pRows, $pLoadDepth, $pLoadForeignObject, $pKey) {
		$lReturn = array();
		if (is_array($pRows)) {
			$lForeignObjectReplacer = new ForeignObjectReplacer();
			$lForeignObjectLoader = new ForeignObjectLoader();
			foreach ($pRows as $lRow) {
				$lObject = $pModel->fromSqlDataBase($lRow, $pLoadDepth);
				$lForeignObjectReplacer->execute($lObject);
				if ($pLoadForeignObject) {
					$lForeignObjectLoader->execute($lObject);
				}
				if (is_null($pKey)) {
					$lReturn[] = $lObject;
				}else {
					$lReturn[$lObject->getValue($pKey)] = $lObject;
				}
			}
		}
		return $lReturn;
	}
	
	/*
	 * $pOperationList is an array of sdtClass objects.
	 * exemple :
	 *    [
	 *    	0 : {operation : "update", id : "12", metada : {...}},
	 *    	1 : {operation : "create", metada : {...}},
	 *    	2 : {operation : "delete", id : "12"}
	 *    ]
	 */
	public function manageObjectList($pOperationList, $pSaveData = true) {
		$lReturn = array();
		
		foreach ($pOperationList as $lStdClassObject) {
			try {
				if (!isset($lStdClassObject->operation)) {
					throw new Exception("operation missing");
				}
				switch ($lStdClassObject->operation) {
					case self::CREATE:
						$lResult = $this->_createOrUpdateObject($lStdClassObject, true);
					break;
					case self::UPDATE:
						$lResult = $this->_createOrUpdateObject($lStdClassObject, false);
					break;
					case self::CREATE_OR_UPDATE:
						if (isset($lStdClassObject->id)) {// if there is an id that means the object has already been created so we do an update
							$lResult = $this->_createOrUpdateObject($lStdClassObject, false);
						}else {// else we do an insert
							$lResult = $this->_createOrUpdateObject($lStdClassObject, true);
						}
					break;
					case self::DELETE_CASCADE:
						 //TODO if it's possible remove children and then go in DELETE
					case self::DELETE:
						$lResult = $this->_deleteObject($lStdClassObject->id);
					break;
					default:
						throw new Exception("operation not recognize : ".$lStdClassObject->operation);
					break;
				}
			}catch (Exception $e) {
				trigger_error(get_class($this));
				$lResult = $this->_setErrorObject($e, $lStdClassObject->id);
			}
			$lReturn[] = $lResult;
		}
		/*if ($pSaveData) {
			$lExchangePointController = ExchangePointController::getInstance();
			$lExchangePointController->sendAllData();
		}*/
		return $lReturn;
	}
	
	/*
	 * update the dataBase
	 * just put files in $pBasePath will not save them. They must be sent to ADFData so $pBasePath should be a path to an exchange point
	 * 
	 * $pCreate indicate if object must be created or updated
	 * 
	 */
	protected function _createOrUpdateObject($pStdClassObject, $pCreate) {
		try {
			$this->_checkSentModel($pStdClassObject, $pCreate);
			$lObject = $this->_getOrCreateObject($pStdClassObject, $pCreate);
			$lResult = $lObject->toObjectPrimayKey();
			$lResult->success = true;
		}catch (Exception $e) {
			$lResult = $this->_setErrorObject($e, $pStdClassObject->id);
		}
		return $lResult;
	}
	
	protected function _checkSentModel($pStdClassObject, $pCreate) {
		if ((!$pCreate && !isset($pStdClassObject->id)) || ($pCreate && (!isset($pStdClassObject->metadata)))) {
			throw new Exception("Bad object");
		}
	}
	
	protected function _getOrCreateObject($pStdClassObject, $pCreate) {
		if ($pCreate) {
			$lObject = clone $this->mObjectReference;
			$lId = (isset($pStdClassObject->id)) ? $pStdClassObject->id : null;
			$this->_createObject($lObject, $pStdClassObject->metadata, $lId);
		}else {
			$lObject = $this->_getObjectfromSqlDataBaseWithId($pStdClassObject->id);
			if (is_null($lObject)) {
				throw new Exception("object does not exist");
			}
			if (isset($pStdClassObject->metadata)) {
				$this->_updateObject($lObject, $pStdClassObject->metadata);
			}
		}
		return $lObject;
	}
	
	protected function _setErrorObject($pException, $pId = null) {
		$lResult = new stdClass();
		$lResult->success = false;
		$lResult->error = new stdClass();
		$lResult->error->code = $pException->getCode();
		$lResult->error->message = $pException->getMessage();
		if (!is_null($pId)) {
			$lResult->id = $pId;
		}
		
		return $lResult;
	}
	
}