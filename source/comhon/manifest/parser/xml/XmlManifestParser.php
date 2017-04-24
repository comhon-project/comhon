<?php

namespace comhon\manifest\parser\xml;

use \Exception;
use comhon\model\Model;
use comhon\manifest\parser\ManifestParser;

abstract class XmlManifestParser extends ManifestParser {
	
	/**
	 * verifiy if manifest has good type
	 * @param \SimpleXMLElement $pManifest
	 */
	protected function _verifManifest($pManifest) {
		if (!($pManifest instanceof \SimpleXMLElement)) {
			throw new \Exception('loaded manifest should be instance of SimpleXMLElement');
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
		
		$lManifestList = simplexml_load_file($pManifestListPath_afe);
		if ($lManifestList === false || is_null($lManifestList)) {
			throw new \Exception("manifestList file not found or malformed '$pManifestListPath_afe'");
		}
		if (!isset($lManifestList['version'])) {
			throw new \Exception("manifest list '$pManifestListPath_afe' doesn't have version");
		}
		$lVersion = (string) $lManifestList['version'];
		switch ($lVersion) {
			case '2.0': return self::_registerComplexModels_2_0($lManifestList, $lManifestListFolder_ad, $pSerializationMap, $pModelMap);
			default:    throw new \Exception("version $lVersion not recognized for manifest list $pManifestListPath_afe");
		}
	}
	
	/**
	 *
	 * @param \SimpleXMLElement $pManifestList
	 * @param string $pManifestListFolder_ad
	 * @param string[] $pSerializationMap
	 * @param array $pModelMap
	 */
	protected static function _registerComplexModels_2_0(\SimpleXMLElement $pManifestList, $pManifestListFolder_ad, $pSerializationMap, &$pModelMap) {
		foreach ($pManifestList->children() as $lManifest) {
			$lModelName = $lManifest->getName();
			if (array_key_exists($lModelName, $pModelMap)) {
				throw new Exception("several model with same type : '$lModelName'");
			}
			$lManifestPath_rfe = (string) $lManifest;
			$lSerializationPath_afe = array_key_exists($lModelName, $pSerializationMap) ? $pSerializationMap[$lModelName] : null;
			$pModelMap[$lModelName] = [$pManifestListFolder_ad.'/'.$lManifestPath_rfe, $lSerializationPath_afe];
		}
	}
	
	/**
	 * 
	 * @param string $pSerializationListPath_afe
	 * @throws \Exception
	 * @return string[]
	 */
	protected static function _getSerializationMap($pSerializationListPath_afe) {
		$pSerializationListFolrder_ad = dirname($pSerializationListPath_afe);
	
		$lSerializationList = simplexml_load_file($pSerializationListPath_afe);
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
	 * @param \SimpleXMLElement $pSerializationListXml
	 * @param string $pSerializationListFolrder_ad
	 * @return string[]
	 */
	protected static function _getSerializationMap_2_0(\SimpleXMLElement $pSerializationListXml, $pSerializationListFolrder_ad) {
		$lSerializationMap = [];
		foreach ($pSerializationListXml->children() as $lSerialization) {
			$lSerializationPath_rfe = (string) $lSerialization;
			$lSerializationMap[$lSerialization->getName()] = $pSerializationListFolrder_ad.'/'.$lSerializationPath_rfe;
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
		$lManifest = simplexml_load_file($pManifestPath_afe);
		
		if ($lManifest === false || is_null($lManifest)) {
			throw new \Exception("manifest file not found or malformed '$pManifestPath_afe'");
		}
		
		if (!isset($lManifest['version'])) {
			throw new \Exception("manifest '$pManifestPath_afe' doesn't have version");
		}
		$lVersion = (string) $lManifest['version'];
		switch ($lVersion) {
			case '2.0': return new v_2_0\XmlManifestParser($pModel, $lManifest, $pSerializationManifestPath_afe);
			default:    throw new \Exception("version $lVersion not recognized for manifest $pManifestPath_afe");
		}
	}
	
}