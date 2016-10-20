<?php

namespace objectManagerLib\object\parser;

use objectManagerLib\object\model\Model;
use objectManagerLib\object\parser\xml\XmlSerializationManifestParser;
use objectManagerLib\object\model\MainModel;
use objectManagerLib\object\parser\json\JsonSerializationManifestParser;

abstract class SerializationManifestParser {
	
	protected $mManifest;
	protected $mModel;

	public abstract function getPropertySerializationInfos($pPropertyName);
	
	protected abstract function _loadManifest($pManifestPath_afe);
	protected abstract function _getSerialization();
	
	/**
	 * @param Model $pModel
	 * @param string $pManifestPath_afe
	 */
	public final function __construct(MainModel $pModel, $pManifestPath_afe) {
		$this->mModel    = $pModel;
		$this->_loadManifest($pManifestPath_afe);
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
				return new XmlSerializationManifestParser($pModel, $pSerializationManifestPath_afe);
				break;
			case 'json':
				return new JsonSerializationManifestParser($pModel, $pSerializationManifestPath_afe);
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