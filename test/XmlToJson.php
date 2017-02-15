<?php

$lFolders = [
	'/home/jean-philippe/ReposGit/ObjectManagerLib/test/manifests/',
	'/home/jean-philippe/ReposGit/ObjectManagerLib/source/comhon/manifest/collection/'
];

foreach ($lFolders as $lFolder) {
	$lFiles = [];
	$lFiles = getDirContents($lFolder, $lFiles);
	
	foreach ($lFiles as $lFile) {
		$dir = dirname($lFile);
		if (basename($lFile) == 'manifest.xml' && !file_exists($dir.'/manifest.json')) {
			$xml = simplexml_load_file($lFile);
			if (isset($xml->serialization)) {
				transformSerialization($xml, $dir);
			} else {
				transformManifest($xml, $dir);
			}
		}
	}
}

function transformSerialization($xml, $dir) {
	$lJson = new stdClass();

	$lJson->version = (string) $xml['version'];
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
			if (isset($lChild->serializationNames->serializationName)) {
				$serializationNames = [];
				foreach ($lChild->serializationNames->serializationName as $serializationName) {
					$serializationNames[] = (string) $serializationName;
				}
				$lproperty->serializationNames = $serializationNames;
			}
			if (isset($lChild->aggregations->aggregation)) {
				$aggregations = [];
				foreach ($lChild->aggregations->aggregation as $lCompo) {
					$aggregations[] = (string) $lCompo;
				}
				$lproperty->aggregations = $aggregations;
			}
			if (isset($lChild['serializable'])) {
				$lproperty->is_serializable = (string) $lChild['serializable'] !== '0';
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
	$lJson->version = (string) $xml['version'];
	
	if (isset($xml['object'])) {
		$lJson->object = (string) $xml['object'];
	}
	
	if (isset($xml['extends'])) {
		$lJson->extends = (string) $xml['extends'];
	}
	
	if (isset($xml->manifests)) {
		$lJson->manifests = new stdClass();
		foreach ($xml->manifests->children() as $manifest) {
			$lPath = (string) $manifest;
			$lType = (string) $manifest->getName();
			$lJson->manifests->$lType = $lPath;
		}
	}
	if (isset($xml->types)) {
		$lJson->types = new stdClass();
		foreach ($xml->types->children() as $type) {
			$lType = $type->getName();
			$lJson->types->$lType = new stdClass();
			if (isset($type['object'])) {
				$lJson->types->$lType->object = (string) $type['object'];
			}
			if (isset($type['extends'])) {
				$lJson->types->$lType->extends = (string) $type['extends'];
			}
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
			
			if (isset($lChild->values->enum)) {
				$lJson->values->enum = [];
				foreach ($lChild->values->enum->value as $value) {
					if ($lJson->values->type == 'integer') {
						$lJson->values->enum[] = (integer) $value;
					} else if ($lJson->values->type == 'float') {
						$lJson->values->enum[] = (float) $value;
					} else {
						$lJson->values->enum[] = (string) $value;
					}
				}
			}
		}
		else if (isset($lChild->enum)) {
			$lPropertyName = (string) $lChild->name;
			$lJson->enum = [];
			foreach ($lChild->enum->value as $value) {
			if ($lTypeId == 'integer') {
					$lJson->enum[] = (integer) $value;
				}else if ($lTypeId == 'float') {
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
		if (isset($lChild['private']) && ((string) $lChild['private'] == '1')) {
			$lJson->is_private = true;
		}
		if (isset($lChild['xml'])) {
			$lJson->xml = (string) $lChild['xml'];
		}
		if (isset($lChild['default'])) {
			if ($lTypeId == 'boolean') {
				$lJson->default = (string) $lChild['default'] === "1";
			} else if ($lTypeId == 'integer') {
				$lJson->default = (integer) $lChild['default'];
			} else if ($lTypeId == 'float') {
				$lJson->default = (float) $lChild['default'];
			} else {
				$lJson->default = (string) $lChild['default'];
			}
		}
		$lPropertiesJson->$lPropertyName = $lJson;
	}
	return $lPropertiesJson;
}

