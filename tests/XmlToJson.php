<?php

$manifestString = '<?xml version="1.0" encoding="UTF-8"?>
<manifest>

	<manifests>
		<manifest type="personLocal">../person/manifest.xml</manifest>
	</manifests>

	<types>
		<type id="objectContainer">
			<properties>
				<foreignProperty type="object">foreignObjectValue</foreignProperty>
				<property type="objectTwo">objectValueTwo</property>
				<property type="personLocal">person</property>
			</properties>
		</type>
		<type id="object">
			<properties>
				<property type="string" id="1">id</property>
				<property type="string">propertyOne</property>
				<property type="string">propertyTwo</property>
			</properties>
		</type>
		<type id="objectTwo">
			<properties>
				<property type="string" id="1">id</property>
				<property type="string">propertyTwoOne</property>
				<property type="string">propertyTwoTwo</property>
			</properties>
		</type>
	</types>
	
	<properties>
		<property type="string" id="1">name</property>
		<property type="object">objectValue</property>
		<property type="array">
			<name>objectValues</name>
			<values type="object" name="objectValue"/>
		</property>
		<property type="objectContainer">objectContainer</property>
		<foreignProperty type="array">
			<name>foreignObjectValues</name>
			<values type="object" name="foreignObjectValue"/>
		</foreignProperty>
	</properties>
</manifest>';

$xml = simplexml_load_string($manifestString);
$lJson = new stdClass();

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

var_dump(json_encode($lJson));

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

