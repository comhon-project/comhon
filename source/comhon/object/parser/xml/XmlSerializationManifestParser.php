<?php

namespace comhon\object\parser\xml;

use comhon\object\model\Model;
use comhon\object\model\MainModel;
use comhon\object\parser\SerializationManifestParser;
use comhon\object\singleton\ModelManager;

abstract class XmlSerializationManifestParser extends SerializationManifestParser {

	/**
	 * @param SimpleXMLElement $pManifest
	 */
	protected function _verifManifest($pManifest) {
		if (!($pManifest instanceof \SimpleXMLElement)) {
			throw new \Exception('loaded manifest should be instance of SimpleXMLElement');
		}
	}

	/**
	 *
	 * @param Model $pModel
	 * @param string $pManifestPath_afe
	 * @param string $pSerializationManifestPath_afe
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getVersionnedInstance($pModel, $pSerializationManifestPath_afe) {
		$lManifest = simplexml_load_file($pSerializationManifestPath_afe);
	
		if ($lManifest === false || is_null($lManifest)) {
			throw new \Exception("serialization manifest file not found or malformed '$pSerializationManifestPath_afe'");
		}
	
		if (!isset($lManifest['version'])) {
			throw new \Exception("serialization manifest '$pSerializationManifestPath_afe' doesn't have version");
		}
		$lVersion = (string) $lManifest['version'];
		switch ($lVersion) {
			case '2.0': return new v_2_0\XmlSerializationManifestParser($pModel, $lManifest);
			default:    throw new \Exception("version $lVersion not recognized for manifest $pSerializationManifestPath_afe");
		}
	}
	
}