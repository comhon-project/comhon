<?php

namespace comhon\manifest\parser;

use comhon\model\Model;
use comhon\manifest\parser\xml\XmlSerializationManifestParser;
use comhon\model\MainModel;
use comhon\manifest\parser\json\JsonSerializationManifestParser;

abstract class SerializationManifestParser {
	
	protected $mManifest;
	protected $mModel;

	public abstract function getPropertySerializationInfos($pPropertyName);
	
	protected abstract function _verifManifest($pManifestPath);
	protected abstract function _getSerialization();
	
	/**
	 * @param Model $pModel
	 * @param string $pManifest
	 */
	public final function __construct(MainModel $pModel, $pManifest) {
		$this->_verifManifest($pManifest);
		$this->mModel    = $pModel;
		$this->mManifest = $pManifest;
	}
	
	/**
	 * 
	 * @param Model $pModel
	 * @param string $pSerializationManifestPath_afe
	 * @throws \Exception
	 * @return SerializationManifestParser
	 */
	public static function getInstance(Model $pModel, $pSerializationManifestPath_afe) {
		switch (mb_strtolower(pathinfo($pSerializationManifestPath_afe, PATHINFO_EXTENSION))) {
			case 'xml':
				return XmlSerializationManifestParser::getVersionnedInstance($pModel, $pSerializationManifestPath_afe);
				break;
			case 'json':
				return JsonSerializationManifestParser::getVersionnedInstance($pModel, $pSerializationManifestPath_afe);
				break;
			default:
				throw new \Exception('extension not recognized for manifest file : '.$pSerializationManifestPath_afe);
		}
	}
	
	public final function getSerialization(MainModel $pModel) {
		if ($this->mModel !== $pModel) {
			throw new \Exception('not same models');
		}
		if ($this->mModel->hasLoadedSerialization()) {
			$lSerialization = $this->mModel->getSerialization();
		}
		else {
			$lSerialization = $this->_getSerialization();
		}
		return $lSerialization;
	}
}