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
		if (basename($lFile) == 'manifest.xml' && !file_exists($dir.'/manifest.json')) {
			$xml = simplexml_load_file($lFile);
			if (isset($xml->serialization) || !isset($xml->properties->property)) {
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
	if (isset($xml->serialization)) {
		$lJson->serialization = new stdClass();
		$lType = (string) $xml->serialization['type'];
		$lJson->serialization->type = $lType;
		if (isset($xml->serialization['inheritanceKey'])) {
			$lJson->serialization->inheritanceKey = (string) $xml->serialization['inheritanceKey'];
		}
		if (isset($xml->serialization->value)) {
			$lJson->serialization->value = [];
			foreach ($xml->serialization->value->attributes() as $lName => $lValue) {
				$lJson->serialization->value[$lName] = (string) $lValue;
			}
		} else {
			$lJson->serialization->id = (string) $xml->serialization['id'];
		}
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
			if (isset($lChild->serializationNames)) {
				$serializationNames = [];
				foreach ($lChild->serializationNames->children() as $serializationName) {
					$serializationNames[$serializationName->getName()] = (string) $serializationName;
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
			if (isset($lChild['is_serializable'])) {
				$lproperty->is_serializable = (string) $lChild['is_serializable'] !== '0';
			}	
			$lJson->properties->{$lChild->getName()} = $lproperty;
		}
		if (!$keep) {
			unset($lJson->properties);
		}
	}
	
	file_put_contents($dir.'/manifest.json', json_encode($lJson, JSON_PRETTY_PRINT));
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
		$lJson->types = [];
		foreach ($xml->types->children() as $type) {
			$lType = new stdClass();
			$lType->name = (string) $type['name'];
			if (isset($type['object'])) {
				$lType->object = (string) $type['object'];
			}
			if (isset($type['extends'])) {
				$lType->extends = (string) $type['extends'];
			}
			$lType->properties = getProperties($type->properties);
			$lJson->types[] = $lType;
		}
	}
	$lJson->properties = new stdClass();
	$lJson->properties = getProperties($xml->properties);
	
	file_put_contents($dir.'/manifest.json', json_encode($lJson, JSON_PRETTY_PRINT));
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
	$lPropertiesJson = [];
	
	foreach ($xml->children() as $lChild) {
		$lJson = new stdClass();
		$lTypeId = (string) $lChild['type'];
		$lJson->name = (string) $lChild['name'];
		$lJson->type = $lTypeId;
		
		if ($lTypeId == 'array') {
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
			if (isset($lChild->values['interval'])) {
				$lJson->values->interval = (string) $lChild->values['interval'];
			}
			if (isset($lChild->values['pattern'])) {
				$lJson->values->pattern = (string) $lChild->values['pattern'];
			}
		}
		else {
			if (isset($lChild->enum)) {
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
			if (isset($lChild['interval'])) {
				$lJson->interval = (string) $lChild['interval'];
			}
			if (isset($lChild['pattern'])) {
				$lJson->pattern = (string) $lChild['pattern'];
			}
		}
		if (isset($lChild['is_foreign']) && ((string) $lChild['is_foreign'] == '1')) {
			$lJson->is_foreign = true;
		}
		if (isset($lChild['is_id']) && ((string) $lChild['is_id'] == '1')) {
			$lJson->is_id = true;
		}
		if (isset($lChild['is_private']) && ((string) $lChild['is_private'] == '1')) {
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
		$lPropertiesJson[] = $lJson;
	}
	return $lPropertiesJson;
}

