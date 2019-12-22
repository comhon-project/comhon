<?php

$folders = [
	__DIR__ . '/manifests/',
	dirname(__DIR__) . '/src/Comhon/Manifest/Collection/'
];

foreach ($folders as $folder) {
	$files = [];
	$files = getDirContents($folder, $files);
	
	foreach ($files as $file) {
		$dir = dirname($file);
		if (basename($file) == 'manifest.xml' && !file_exists($dir.'/manifest.json')) {
			$xml = simplexml_load_file($file);
			transformManifest($xml, $dir);
		}
		elseif (basename($file) == 'serialization.xml' && !file_exists($dir.'/serialization.json')) {
			$xml = simplexml_load_file($file);
			transformSerialization($xml, $dir);
		}
	}
}

function transformSerialization($xml, $dir) {
	$json = new stdClass();

	$json->version = (string) $xml['version'];
	if (isset($xml->serialization)) {
		$json->serialization = new stdClass();
		$type = (string) $xml->serialization['type'];
		$json->serialization->type = $type;
		if (isset($xml->serialization['inheritanceKey'])) {
			$json->serialization->inheritanceKey = (string) $xml->serialization['inheritanceKey'];
		}
		if (isset($xml->serialization->value)) {
			$json->serialization->value = [];
			foreach ($xml->serialization->value->attributes() as $name => $value) {
				$json->serialization->value[$name] = (string) $value;
			}
			if (isset($xml->serialization->value->database)) {
				$json->serialization->value['database'] = (string) $xml->serialization->value->database;
			}
		} else {
			$json->serialization->id = (string) $xml->serialization['id'];
		}
	}
	
	if (isset($xml->properties)) {
		$keep = false;
		$json->properties = new stdClass();
		foreach ($xml->properties->children() as $child) {
			$keep = true;
			$property = new stdClass();
			if (isset($child['serializationName'])) {
				$property->serializationName = (string) $child['serializationName'];
			}
			if (isset($child->serializationNames)) {
				$serializationNames = [];
				foreach ($child->serializationNames->children() as $serializationName) {
					$serializationNames[$serializationName->getName()] = (string) $serializationName;
				}
				$property->serializationNames = $serializationNames;
			}
			if (isset($child->aggregations->aggregation)) {
				$aggregations = [];
				foreach ($child->aggregations->aggregation as $compo) {
					$aggregations[] = (string) $compo;
				}
				$property->aggregations = $aggregations;
			}
			if (isset($child['is_serializable'])) {
				$property->is_serializable = (string) $child['is_serializable'] !== '0';
			}	
			$json->properties->{$child->getName()} = $property;
		}
		if (!$keep) {
			unset($json->properties);
		}
	}
	
	file_put_contents($dir.'/serialization.json', json_encode($json, JSON_PRETTY_PRINT));
}

function transformManifest($xml, $dir) {
	$json = new stdClass();
	$json->version = (string) $xml['version'];
	
	if (isset($xml['is_main'])) {
		$json->is_main = (boolean) ((string) $xml['is_main']);
	}
	if (isset($xml['is_serializable'])) {
		$json->is_serializable = (boolean) ((string) $xml['is_serializable']);
	}
	if (isset($xml['is_abstract'])) {
		$json->is_abstract = (boolean) ((string) $xml['is_abstract']);
	}
	if (isset($xml['object'])) {
		$json->object = (string) $xml['object'];
	}
	if (isset($xml['share_parent_id'])) {
		$json->share_parent_id = (boolean) ((string) $xml['share_parent_id']);
	}
	if (isset($xml['shared_id'])) {
		$json->shared_id = (string) $xml['shared_id'];
	}
	
	if (isset($xml->extends)) {
		$json->extends = [];
		foreach ($xml->extends->children() as $extends) {
			$json->extends[] = (string) $extends;
		}
	}
	
	if (isset($xml->manifests)) {
		$json->manifests = new stdClass();
		foreach ($xml->manifests->children() as $manifest) {
			$path = (string) $manifest;
			$type = (string) $manifest->getName();
			$json->manifests->$type = $path;
		}
	}
	if (isset($xml->types)) {
		$json->types = [];
		foreach ($xml->types->children() as $type) {
			$typeObj = new stdClass();
			$typeObj->name = (string) $type['name'];
			if (isset($type['is_main'])) {
				$typeObj->is_main = (boolean) ((string) $type['is_main']);
			}
			if (isset($type['is_serializable'])) {
				$typeObj->is_serializable = (boolean) ((string) $type['is_serializable']);
			}
			if (isset($type['is_abstract'])) {
				$typeObj->is_abstract = (boolean) ((string) $type['is_abstract']);
			}
			if (isset($type['object'])) {
				$typeObj->object = (string) $type['object'];
			}
			if (isset($type['share_parent_id'])) {
				$typeObj->share_parent_id = (boolean) ((string) $type['share_parent_id']);
			}
			if (isset($type['shared_id'])) {
				$typeObj->shared_id = (string) $type['shared_id'];
			}
			if (isset($type->extends)) {
				$typeObj->extends = [];
				foreach ($type->extends->children() as $extends) {
					$typeObj->extends[] = (string) $extends;
				}
			}
			
			$typeObj->properties = getProperties($type->properties);
			$json->types[] = $typeObj;
		}
	}
	if (isset($xml->properties)) {
		$json->properties = new stdClass();
		$json->properties = getProperties($xml->properties);
	}
	
	file_put_contents($dir.'/manifest.json', json_encode($json, JSON_PRETTY_PRINT));
}

function getDirContents($dir, &$results = array()) {
	$files = scandir($dir);

	foreach($files as $value) {
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
	$propertiesJson = [];
	
	foreach ($xml->children() as $child) {
		$json = new stdClass();
		$typeId = (string) $child['type'];
		$json->name = (string) $child['name'];
		$json->type = $typeId;
		if (isset($child['is_associative'])) {
			$json->is_associative = (boolean) ((string) $child['is_associative']);
		}
		
		if ($typeId == 'array') {
			$json->values = new stdClass();
			$json->values->type = (string) $child->values['type'];
			$json->values->name = (string) $child->values['name'];
			
			if (isset($child->values->enum)) {
				$json->values->enum = [];
				foreach ($child->values->enum->value as $value) {
					if ($json->values->type == 'integer' || $json->values->type == 'index') {
						$json->values->enum[] = (integer) $value;
					} else if ($json->values->type == 'float' || $json->values->type == 'percentage') {
						$json->values->enum[] = (float) $value;
					} else {
						$json->values->enum[] = (string) $value;
					}
				}
			}
			if (isset($child->values['interval'])) {
				$json->values->interval = (string) $child->values['interval'];
			}
			if (isset($child->values['pattern'])) {
				$json->values->pattern = (string) $child->values['pattern'];
			}
			if (isset($child->values['not_null']) && ((string) $child->values['not_null'] == '1')) {
				$json->values->not_null = true;
			}
			if (isset($child->values['is_model_name']) && ((string) $child->values['is_model_name'] == '1')) {
				$json->values->is_model_name = true;
			}
			if (isset($child->values['not_empty']) && ((string) $child->values['not_empty'] == '1')) {
				$json->values->not_empty = true;
			}
			if (isset($child->values['length'])) {
				$json->values->length = (string) $child->values['length'];
			}
		}
		else {
			if (isset($child->enum)) {
				$json->enum = [];
				foreach ($child->enum->value as $value) {
					if ($typeId == 'integer' || $typeId == 'index') {
						$json->enum[] = (integer) $value;
					}else if ($typeId == 'float' || $typeId == 'percentage') {
						$json->enum[] = (float) $value;
					} else {
						$json->enum[] = (string) $value;
					}
				}
			}
			if (isset($child['interval'])) {
				$json->interval = (string) $child['interval'];
			}
			if (isset($child['pattern'])) {
				$json->pattern = (string) $child['pattern'];
			}
		}
		if (isset($child['length'])) {
			$json->length = (string) $child['length'];
		}
		if (isset($child['size'])) {
			$json->size = (string) $child['size'];
		}
		if (isset($child['is_required']) && ((string) $child['is_required'] == '1')) {
			$json->is_required = true;
		}
		if (isset($child['not_null']) && ((string) $child['not_null'] == '1')) {
			$json->not_null = true;
		}
		if (isset($child['is_model_name']) && ((string) $child['is_model_name'] == '1')) {
			$json->is_model_name = true;
		}
		if (isset($child['not_empty']) && ((string) $child['not_empty'] == '1')) {
			$json->not_empty = true;
		}
		if (isset($child['is_foreign']) && ((string) $child['is_foreign'] == '1')) {
			$json->is_foreign = true;
		}
		if (isset($child['is_id']) && ((string) $child['is_id'] == '1')) {
			$json->is_id = true;
		}
		if (isset($child['is_private']) && ((string) $child['is_private'] == '1')) {
			$json->is_private = true;
		}
		if (isset($child['xml'])) {
			$json->xml = (string) $child['xml'];
		}
		if (isset($child['default'])) {
			if ($typeId == 'boolean') {
				$json->default = (string) $child['default'] === "1";
			} else if ($typeId == 'integer' || $typeId == 'index') {
				$json->default = (integer) $child['default'];
			} else if ($typeId == 'float' || $typeId == 'percentage') {
				$json->default = (float) $child['default'];
			} else {
				$json->default = (string) $child['default'];
			}
		}
		$propertiesJson[] = $json;
	}
	return $propertiesJson;
}

