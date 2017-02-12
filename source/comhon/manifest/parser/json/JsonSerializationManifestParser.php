<?php

namespace comhon\manifest\parser\json;

use comhon\model\Model;
use comhon\model\MainModel;
use comhon\manifest\parser\SerializationManifestParser;
use comhon\model\singleton\ModelManager;

abstract class JsonSerializationManifestParser extends SerializationManifestParser {

	/**
	 * verifiy if manifest has good type
	 * @param [] $pManifest
	 */
	public function _verifManifest($pManifest) {
		if (!($pManifest instanceof \stdClass)) {
			throw new \Exception('loaded manifest should be an instance of stdClass');
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
		$lManifest = json_decode(file_get_contents($pSerializationManifestPath_afe));
	
		if ($lManifest === false || is_null($lManifest)) {
			throw new \Exception("serialization manifest file not found or malformed '$pSerializationManifestPath_afe'");
		}
	
		if (!isset($lManifest->version)) {
			throw new \Exception("serialization manifest '$pSerializationManifestPath_afe' doesn't have version");
		}
		$lVersion = (string) $lManifest->version;
		switch ($lVersion) {
			case '2.0': return new v_2_0\JsonSerializationManifestParser($pModel, $lManifest);
			default:    throw new \Exception("version $lVersion not recognized for manifest $pSerializationManifestPath_afe");
		}
	}
	
}