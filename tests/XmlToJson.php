<?php

$lFolders = [
	'/home/jean-philippe/ReposGit/ObjectManagerLib/source/objectManagerLib/manifestLib/',
	'/home/jean-philippe/ReposGit/ObjectManagerLib/source/objectManagerLib/manifestCollection/'
];

foreach ($lFolders as $lFolder) {
	$lFiles = [];
	$lFiles = getDirContents($lFolder, $lFiles);
	
	foreach ($lFiles as $lFile) {
		$dir = dirname($lFile);
		if (basename($lFile) == 'manifest.xml' && !file_exists($dir.'/manifest.json')) {
			$xml = simplexml_load_file($lFile);
			if (isset($xml->serialization)) {
				transformSerializationt($xml, $dir);
			} else {
				transformManifest($xml, $dir);
			}
		}
	}
}

function transformSerializationt($xml, $dir) {
	$lJson = new stdClass();
	
	$lJson->serialization = new stdClass();
	$lType = (string) $xml->serialization['type'];
	$lJson->serialization->type = $lType;
	if (isset($xml->serialization->$lType)) {
		$lJson->serialization->value = [];
		foreach ($xml->serialization->$lType->attributes() as $lName => $lValue) {
			$lJson->serialization->value[$lName] = (string) $lValue;
		}
	} else {
		$lJson->serialization->id = (string) $xml->serialization;
	}
	
	if (isset($xml->properties)) {
		$keep = false;
		$lJson->properties = new stdClass();
		foreach ($xml->properties->children() as $lChild) {
			$keep = true;
			$lproperty = new stdClass();
			if (isset($lChild['serializationName'])) {
				$lproperty->serializationName = (string) $lChild['serializationName'];
			}
			if (isset($lChild->compositions->composition)) {
				$compositions = [];
				foreach ($lChild->compositions->composition as $lCompo) {
					$compositions[] = (string) $lCompo;
				}
				$lproperty->compositions = $compositions;
			}
				
			$lJson->properties->{$lChild->getName()} = $lproperty;
		}
		if (!$keep) {
			unset($lJson->properties);
		}
	}
	
	file_put_contents($dir.'/manifest.json', json_encode($lJson));
}

function transformManifest($xml, $dir) {
	$lJson = new stdClass();
	
	if (isset($xml['object'])) {
		$lJson->object = (string) $xml['object'];
	}
	
	if (isset($xml->manifests)) {
		$lJson->manifests = new stdClass();
		foreach ($xml->manifests->manifest as $manifest) {
			$lPath = (string) $manifest;
			$lType = (string) $manifest['type'];
			$lJson->manifests->$lType = $lPath;
		}
	}
	if (isset($xml->types)) {
		$lJson->types = new stdClass();
		foreach ($xml->types->type as $type) {
			$lType = (string) $type['id'];
			$lJson->types->$lType = new stdClass();
			$lJson->types->$lType->properties = getProperties($type->properties);
		}
	}
	$lJson->properties = new stdClass();
	$lJson->properties = getProperties($xml->properties);
	
	file_put_contents($dir.'/manifest.json', json_encode($lJson));
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

function getProperties($xml) {
	$lPropertiesJson = new stdClass();
	
	foreach ($xml->children() as $lChild) {
		$lJson = new stdClass();
		$lTypeId = (string) $lChild['type'];
		if ($lTypeId == 'array') {
			$lPropertyName = (string) $lChild->name;
			$lJson->values = new stdClass();
			$lJson->values->type = (string) $lChild->values['type'];
			$lJson->values->name = (string) $lChild->values['name'];
		}
		else if (isset($lChild->enum)) {
			$lPropertyName = (string) $lChild->name;
			$lJson->enum = [];
			foreach ($lChild->enum->value as $value) {
				if ($lTypeId == 'integer') {
					$lJson->enum[] = (integer) $value;
				} else {
					$lJson->enum[] = (string) $value;
				}
			}
		}
		else {
			$lPropertyName = (string) $lChild;
		}
		$lJson->type = $lTypeId;
		if ($lChild->getName() == 'foreignProperty') {
			$lJson->is_foreign = true;
		}
		if (isset($lChild['id']) && ((string) $lChild['id'] == '1')) {
			$lJson->is_id = true;
		}
		$lPropertiesJson->$lPropertyName = $lJson;
	}
	return $lPropertiesJson;
}

