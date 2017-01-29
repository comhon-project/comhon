<?php

namespace comhon\object\parser\json;

use \Exception;
use comhon\object\model\ModelArray;
use comhon\object\model\ModelEnum;
use comhon\object\model\ModelInteger;
use comhon\object\model\ModelFloat;
use comhon\object\model\ModelBoolean;
use comhon\object\model\ModelString;
use comhon\object\model\ModelDateTime;
use comhon\object\model\Model;
use comhon\object\model\MainModel;
use comhon\object\model\LocalModel;
use comhon\object\model\Property;
use comhon\object\model\ModelForeign;
use comhon\object\model\SimpleModel;
use comhon\object\model\ForeignProperty;
use comhon\object\model\AggregationProperty;
use comhon\object\object\config\Config;
use comhon\object\parser\ManifestParser;
use comhon\object\parser\SerializationManifestParser;

abstract class JsonManifestParser extends ManifestParser {

	/**
	 * verifiy if manifest has good type
	 * @param [] $pManifest
	 */
	public function _verifManifest($pManifest) {
		if (!is_array($pManifest)) {
			throw new \Exception('loaded manifest should be an associative array');
		}
	}
	
	/**
	 * register path of each manifest
	 * @param string $pManifestListPath_afe
	 * @param string[] $pSerializationMap
	 * @param array $pModelMap
	 * @throws \Exception
	 */
	protected static function _registerComplexModels($pManifestListPath_afe, $pSerializationMap, &$pModelMap) {
		$lManifestListFolder_ad = dirname($pManifestListPath_afe);
		
		$lManifestList = json_decode(file_get_contents($pManifestListPath_afe), true);
		if ($lManifestList === false || is_null($lManifestList)) {
			throw new \Exception("manifestList file not found or malformed '$pManifestListPath_afe'");
		}
		if (!isset($lManifestList['version'])) {
			throw new \Exception("manifest list '$pManifestListPath_afe' doesn't have version");
		}
		$lVersion = $lManifestList['version'];
		switch ($lVersion) {
			case '2.0': return self::_registerComplexModels_2_0($lManifestList, $lManifestListFolder_ad, $pSerializationMap, $pModelMap);
			default:    throw new \Exception("version $lVersion not recognized for manifest list $pManifestListPath_afe");
		}
	}
	
	/**
	 *
	 * @param [] $pManifestList
	 * @param string $pManifestListFolder_ad
	 * @param string[] $pSerializationMap
	 * @param array $pModelMap
	 */
	protected static function _registerComplexModels_2_0($pManifestList, $pManifestListFolder_ad, $pSerializationMap, &$pModelMap) {
		foreach ($pManifestList['list'] as $lModelName => $lManifestPath_rfe) {
			if (array_key_exists($lModelName, $pModelMap)) {
				throw new Exception("several model with same type : '$lModelName'");
			}
			$lSerializationPath_afe = array_key_exists($lModelName, $pSerializationMap) ? $pSerializationMap[$lModelName] : null;
			$pModelMap[$lModelName] = array($pManifestListFolder_ad.'/'.$lManifestPath_rfe, $lSerializationPath_afe);
		}
	}
	
	/**
	 * 
	 * @param string $pSerializationListPath_afe
	 * @throws \Exception
	 * @return string[]
	 */
	protected static function _getSerializationMap($pSerializationListPath_afe) {
		$lSerializationMap = [];
		$pSerializationListFolrder_ad = dirname($pSerializationListPath_afe);
	
		$lSerializationList = json_decode(file_get_contents($pSerializationListPath_afe), true);
		if ($lSerializationList === false || is_null($lSerializationList)) {
			throw new \Exception("serializationList file not found or malformed '$pSerializationListPath_afe'");
		}
		if (!isset($lSerializationList['version'])) {
			throw new \Exception("serialization list '$pSerializationListPath_afe' doesn't have version");
		}
		$lVersion = (string) $lSerializationList['version'];
		switch ($lVersion) {
			case '2.0': return self::_getSerializationMap_2_0($lSerializationList, $pSerializationListFolrder_ad);
			default:    throw new \Exception("version $lVersion not recognized for serialization list $pSerializationListPath_afe");
		}
	}
	
	/**
	 *
	 * @param [] $pSerializationList
	 * @param string $pSerializationListFolrder_ad
	 * @return string[]
	 */
	protected static function _getSerializationMap_2_0($pSerializationList, $pSerializationListFolrder_ad) {
		$lSerializationMap = [];
		foreach ($pSerializationList['list'] as $lModelName => $lSerializationPath_rfe) {
			$lSerializationMap[$lModelName] = $pSerializationListFolrder_ad.'/'.$lSerializationPath_rfe;
		}
		return $lSerializationMap;
	}
	
	/**
	 *
	 * @param Model $pModel
	 * @param string $pManifestPath_afe
	 * @param string $pSerializationManifestPath_afe
	 * @throws \Exception
	 * @return ManifestParser
	 */
	public static function getVersionnedInstance($pModel, $pManifestPath_afe, $pSerializationManifestPath_afe) {
		$lManifest = json_decode(file_get_contents($pManifestPath_afe), true);
	
		if ($lManifest === false || is_null($lManifest)) {
			throw new \Exception("manifest file not found or malformed '$pManifestPath_afe'");
		}
	
		if (!isset($lManifest['version'])) {
			throw new \Exception("manifest '$pManifestPath_afe' doesn't have version");
		}
		$lVersion = (string) $lManifest['version'];
		switch ($lVersion) {
			case '2.0': return new v_2_0\JsonManifestParser($pModel, $lManifest, $pSerializationManifestPath_afe);
			default:    throw new \Exception("version $lVersion not recognized for manifest $pManifestPath_afe");
		}
	}
	
}