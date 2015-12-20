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

abstract class ObjectLoadRequest {

	protected $mModel;
	protected $mRequestChildren          = false;
	protected $mLoadForeignProperties    = false;
	protected $mReplaceForeignProperties = true;
	
	public function __construct($pModelName) {
		$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
	}
	
	public abstract function execute($pValue);
		
	public function requestChildren($pBoolean) {
		$this->mRequestChildren = $pBoolean;
		return $this;
	}
	
	public function loadForeignProperties($pBoolean) {
		$this->mLoadForeignProperties = $pBoolean;
		return $this;
	}
	
	public function ReplaceForeignProperties($pBoolean) {
		$this->mReplaceForeignProperties = $pBoolean;
		return $this;
	}
	
	public function getModel() {
		return $this->mModel;
	}
	
	protected function _updateObjects($pObjects) {
		$lReturn = array();
		$lForeignObjectReplacer = new ForeignObjectReplacer();
		$lForeignObjectLoader   = new ForeignObjectLoader();
		$lCompositionLoader     = new CompositionLoader();
		
		foreach ($pObjects as $lObject) {
			if ($this->mRequestChildren && !$this->mLoadForeignProperties) {
				$lCompositionLoader->execute($lObject, array($this->mLoadForeignProperties));
			}
			else if ($this->mLoadForeignProperties) {
				$lForeignObjectLoader->execute($lObject, array($this->mRequestChildren));
			}
			if ($this->mReplaceForeignProperties) {
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