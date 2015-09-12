<?php

use GenLib\objectManager\singleton\InstanceModel;

class QueryModel {

	private $mModel;
	private $mId;
	
	
	/**
	 * 
	 * @param unknown $pModelName
	 * @param string $pId unique id to identify queryModel
	 * @param string $pLinkId reference to an queryModel id
	 */
	public function __construct($pModelName, $pId = null, $pLinkId = null) {
		$this->mModel = InstanceModel::getInstance()->getInstanceModel($pModelName);
		$this->mId = is_null($pId)? mt_rand() : $pId;
	}
	
}