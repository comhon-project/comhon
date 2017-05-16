<?php

$lFolders = [
	'/home/jean-philippe/ReposGit/comhon/test/manifests/',
	'/home/jean-philippe/ReposGit/comhon/source/comhon/manifest/collection/'
];

foreach ($lFolders as $lFolder) {
	$lFiles = [];
	$lFiles = getDirContents($lFolder, $lFiles);
	
	foreach ($lFiles as $lFile) {
		$dir = dirname($lFile);
		if (basename($lFile) == 'manifest.xml') {
			$xml = simplexml_load_file($lFile);
			if (!isset($xml->serialization)) {
				transformProperties($xml, '/manifest/properties/property');
				transformProperties($xml, '/manifest/types/*/properties/property');
				transformProperties($xml, '/manifest/properties/foreignProperty');
				transformProperties($xml, '/manifest/types/*/properties/foreignProperty');
				$xml->asXML($lFile);
				
				$content = file_get_contents($lFile);
				$content = str_replace('</foreignProperty>', '</property>', $content);
				$content = str_replace('<foreignProperty', '<property is_foreign="1"', $content);
				$content = str_replace(' private=', ' is_private=', $content);
				$content = str_replace(' id=', ' is_id=', $content);
				file_put_contents($lFile, $content);
			}
		}
	}
}

function transformProperties($xml, $xpathPattern) {
	$results = $xml->xpath($xpathPattern);
	foreach ($results as $node) {
		if (!isset($node['name'])) {
			$node['name'] = (string) $node;
		}
		$dom = dom_import_simplexml($node);
		
		if ($dom->childNodes->length === 1) {
			if ($dom->childNodes->item(0)->nodeType === XML_TEXT_NODE) {
				$dom->removeChild($dom->childNodes->item(0));
			}
		}
	}
}

function getDirContents($dir, &$results = array()){
	$files = scandir($dir);

	foreach($files as $key => $value){
		$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
		if(!is_dir($path)) {
			$results[] = $path;
		} else if($value != "." && $value != "..") {
			getDirContents($path, $results);
			$results[] = $path;
		}
	}

	return $results;
}

