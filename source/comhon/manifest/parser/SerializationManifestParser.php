<?php

namespace comhon\manifest\parser;

use comhon\model\Model;
use comhon\model\MainModel;
use comhon\interfacer\XMLInterfacer;
use comhon\interfacer\Interfacer;
use comhon\interfacer\AssocArrayInterfacer;
use comhon\interfacer\StdObjectInterfacer;

abstract class SerializationManifestParser {
	
	protected $mManifest;
	protected $mModel;
	protected $mInterfacer;

	public abstract function getPropertySerializationInfos($pPropertyName);
	
	protected abstract function _getSerializationSettings();
	public abstract function getInheritanceKey();
	
	/**
	 * @param Model $pModel
	 * @param string $pManifest
	 */
	public final function __construct(MainModel $pModel, $pManifest) {
		$this->mInterfacer = $this->_getInterfacer($pManifest);
		$this->mModel      = $pModel;
		$this->mManifest   = $pManifest;
		
		$this->mInterfacer->setSerialContext(true);
		$this->mInterfacer->setPrivateContext(true);
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
				$lInterfacer = new XMLInterfacer();
				break;
			case 'json':
				$lInterfacer = new AssocArrayInterfacer();
				break;
			default:
				throw new \Exception('extension not recognized for manifest file : '.$pSerializationManifestPath_afe);
		}
		return self::getVersionnedInstance($pModel, $pSerializationManifestPath_afe, $lInterfacer);
		
	}
	
	public final function getSerializationSettings(MainModel $pModel) {
		if ($this->mModel !== $pModel) {
			throw new \Exception('not same models');
		}
		return $this->mModel->hasLoadedSerialization()
			? $this->mModel->getSerialization()->getSettings() : $this->_getSerializationSettings();
	}
	
	/**
	 * get interfacer able to interpret manifest
	 * @param [] $pManifest
	 */
	public function _getInterfacer($pManifest) {
		if (is_array($pManifest)) {
			return new AssocArrayInterfacer();
		}
		if ($pManifest instanceof \stdClass) {
			return new StdObjectInterfacer();
		}
		if ($pManifest instanceof \DOMElement) {
			return new XMLInterfacer();
		}
		throw new \Exception('not recognized manifest format');
	}
	
	/**
	 *
	 * @param Model $pModel
	 * @param string $pSerializationManifestPath_afe
	 * @param Interfacer $pInterfacer
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getVersionnedInstance($pModel, $pSerializationManifestPath_afe, Interfacer $pInterfacer) {
		$lManifest = $pInterfacer->read($pSerializationManifestPath_afe);
		
		if ($lManifest === false || is_null($lManifest)) {
			throw new \Exception("serialization manifest file not found or malformed '$pSerializationManifestPath_afe'");
		}
		
		if (!$pInterfacer->hasValue($lManifest, 'version')) {
			throw new \Exception("serialization manifest '$pSerializationManifestPath_afe' doesn't have version");
		}
		$lVersion = (string) $pInterfacer->getValue($lManifest, 'version');
		switch ($lVersion) {
			case '2.0': return new v_2_0\SerializationManifestParser($pModel, $lManifest);
			default:    throw new \Exception("version $lVersion not recognized for manifest $pSerializationManifestPath_afe");
		}
	}
}