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

use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject;
use Comhon\Object\ObjectArray;

abstract class ObjectLoadRequest {

	protected $mModel;
	protected $mRequestChildren          = false;
	protected $mLoadForeignProperties    = false;
	protected $mPropertiesFilter;
	protected $mPrivate;
	
	public function __construct($pModelName, $pPrivate = false) {
		$this->mModel = ModelManager::getInstance()->getInstanceModel($pModelName);
		$this->mPrivate = $pPrivate;
	}
	
	/**
	 * execute resquest and return resulting object
	 * @return ComhonObject
	 */
	abstract public function execute();
	
	private $mId;
	
	/**
	 *
	 * @param string[] $pPropertiesFilter
	 */
	public function setPropertiesFilter($pPropertiesFilter) {
		if (empty($pPropertiesFilter)) {
			return;	
		}
		$this->mPropertiesFilter = [];
		// ids have to be in selected columns so if they are not defined in filter, we add them
		foreach ($this->mModel->getIdProperties() as $lProperty) {
			$this->mPropertiesFilter[] = $lProperty->getName();
		}
		// add defined columns
		foreach ($pPropertiesFilter as $pPropertyName) {
			$lProperty = $this->mModel->getProperty($pPropertyName, true);
			if ($lProperty->isAggregation()) {
				throw new \Exception("aggregation property '$pPropertyName' can't be a filter property");
			} else if (!$this->mPrivate && $lProperty->isPrivate()) {
				throw new \Exception("private property '$pPropertyName' can't be a filter property for public request");
			}
			else {
				$this->mPropertiesFilter[] = $pPropertyName;
			}
		}
		// remove possible duplicated columns
		$this->mPropertiesFilter = array_unique($this->mPropertiesFilter);
	}
		
	public function requestChildren($pBoolean) {
		$this->mRequestChildren = $pBoolean;
		return $this;
	}
	
	public function loadForeignProperties($pBoolean) {
		$this->mLoadForeignProperties = $pBoolean;
		return $this;
	}
	
	public function getModel() {
		return $this->mModel;
	}
	
	protected function _updateObjects(ComhonObject $pObject) {
		$lObjects = ($pObject instanceof ObjectArray) ? $pObject->getValues() : [$pObject];

		if ($this->mRequestChildren && !$this->mLoadForeignProperties) {
			foreach ($lObjects as $lObject) {
				foreach ($lObject->getModel()->getAggregations() as $lPropertyName => $lAggregation) {
					$lObject->loadValueIds($lPropertyName);
				}
			}
		}
		else if ($this->mLoadForeignProperties) {
			foreach ($lObjects as $lObject) {
				foreach ($lObject->getModel()->getComplexProperties() as $lPropertyName => $lProperty) {
					if ($lProperty->isAggregation() || ($lProperty->isForeign() && !is_null($lObject->getValue($lPropertyName)))) {
						$lObject->loadValue($lPropertyName);
					}
				}
			}
		}
		
		return $pObject;
	}
	
}