<?php
namespace objectManagerLib\object;

use objectManagerLib\database\DatabaseController;
use objectManagerLib\database\LogicalJunction;
use objectManagerLib\database\LogicalJunctionOptimizer;
use objectManagerLib\database\ComplexLiteral;
use objectManagerLib\database\HavingLiteral;
use objectManagerLib\database\SelectQuery;
use objectManagerLib\object\singleton\InstanceModel;
use objectManagerLib\object\object\Object;
use objectManagerLib\object\model\Model;
use objectManagerLib\object\model\SimpleModel;
use objectManagerLib\object\model\ModelContainer;
use objectManagerLib\object\model\ForeignProperty;
use objectManagerLib\controller\ForeignObjectReplacer;
use objectManagerLib\controller\ForeignObjectLoader;
use objectManagerLib\controller\CompositionLoader;

abstract class LoadObjectRequest {

	protected $mModel;
	protected $mGetChildren            = false;
	protected $mLoadForeignProperty    = false;
	protected $mReplaceForeignProperty = true;
	
	public function __construct($pModelName) {
		$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
	}
	
	public abstract function execute($pValue);
		
	public function getChildren($pBoolean) {
		$this->mGetChildren = $pBoolean;
		return $this;
	}
	
	public function loadForeignProperty($pBoolean) {
		$this->mLoadForeignProperty = $pBoolean;
		return $this;
	}
	
	public function replaceForeignProperty($pBoolean) {
		$this->mReplaceForeignProperty = $pBoolean;
		return $this;
	}
	
	
	
	protected function _updateObjects($pObjects) {
		$lReturn = array();
		$lForeignObjectReplacer = new ForeignObjectReplacer();
		$lForeignObjectLoader   = new ForeignObjectLoader();
		$lCompositionLoader     = new CompositionLoader();
		
		foreach ($pObjects as $lObject) {
			if ($this->mGetChildren && !$this->mLoadForeignProperty) {
				$lCompositionLoader->execute($lObject, array($this->mLoadForeignProperty));
			}
			else if ($this->mLoadForeignProperty) {
				$lForeignObjectLoader->execute($lObject, array($this->mGetChildren));
			}
			if ($this->mReplaceForeignProperty) {
				$lForeignObjectReplacer->execute($lObject);
			}
			$lReturn[] = $lObject;
		}
		return $lReturn;
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