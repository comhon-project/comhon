<?php


use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\XMLInterfacer;
use comhon\interfacer\Interfacer;
use object\Person;
use comhon\interfacer\AssocArrayInterfacer;

$time_start = microtime(true);

/** ************************* std **************************** **/

$lInterfacer= new StdObjectInterfacer();
$lNode = $lInterfacer->createNode('root');
$lCreatedNode = $lInterfacer->createNode('object');
$lInterfacer->setValue($lNode, $lCreatedNode, 'object');
$lInterfacer->setValue($lCreatedNode, 'value', 'prop');
$lInterfacer->setValue($lCreatedNode, 'value_node', 'prop_node', true);
$lInterfacer->setValue($lNode, 'root_value', 'root_prop');

$lNodeArray = $lInterfacer->createNodeArray('array');
$lInterfacer->addValue($lNodeArray, 'value1', 'element');
$lInterfacer->addValue($lNodeArray, 'value2', 'element');

$lCreatedNode = $lInterfacer->createNode('element');
$lInterfacer->setValue($lCreatedNode, 123, 'object_element_prop');
$lInterfacer->setValue($lCreatedNode, 123, 'object_element_node', true);
$lInterfacer->addValue($lNodeArray, $lCreatedNode);

$lInterfacer->setValue($lNode, $lNodeArray, 'array');
$lInterfacer->flattenNode($lNode, 'array');

if ($lInterfacer->toString($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	var_dump($lInterfacer->toString($lNode));
	throw new Exception('bad value');
}

$lInterfacer->unflattenNode($lNode, 'array');
if ($lInterfacer->toString($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":["value1","value2",{"object_element_prop":123,"object_element_node":123}]}') {
	var_dump($lInterfacer->toString($lNode));
	throw new Exception('bad value');
}

$lInterfacer->flattenNode($lNode, 'array');
if ($lInterfacer->toString($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	var_dump($lInterfacer->toString($lNode));
	throw new Exception('bad value');
}

/** ************************* XML **************************** **/

$lInterfacer = new XMLInterfacer();
$lNode = $lInterfacer->createNode('root');
$lCreatedNode = $lInterfacer->createNode('object');
$lInterfacer->setValue($lNode, $lCreatedNode, 'object');
$lInterfacer->setValue($lCreatedNode, 'value', 'prop');
$lInterfacer->setValue($lCreatedNode, 'value_node', 'prop_node', true);
$lInterfacer->setValue($lNode, 'root_value', 'root_prop');

$lNodeArray = $lInterfacer->createNodeArray('array');
$lInterfacer->addValue($lNodeArray, 'value1', 'element');
$lInterfacer->addValue($lNodeArray, 'value2', 'element');

$lCreatedNode = $lInterfacer->createNode('element');
$lInterfacer->setValue($lCreatedNode, 123, 'object_element_prop');
$lInterfacer->setValue($lCreatedNode, 123, 'object_element_node', true);
$lInterfacer->addValue($lNodeArray, $lCreatedNode);

$lInterfacer->setValue($lNode, $lNodeArray, 'array');
$lInterfacer->flattenNode($lNode, 'array');

if (!compareXML($lInterfacer->toString($lNode), '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array>&lt;element&gt;value1&lt;/element&gt;&lt;element&gt;value2&lt;/element&gt;&lt;element object_element_prop="123"&gt;&lt;object_element_node&gt;123&lt;/object_element_node&gt;&lt;/element&gt;</array></root>')) {
	throw new Exception('bad value');
}

$lInterfacer->unflattenNode($lNode, 'array');
if (!compareXML($lInterfacer->toString($lNode), '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array><element>value1</element><element>value2</element><element object_element_prop="123"><object_element_node>123</object_element_node></element></array></root>')) {
	throw new Exception('bad value');
}

$lInterfacer->flattenNode($lNode, 'array');
if (!compareXML($lInterfacer->toString($lNode), '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array>&lt;element&gt;value1&lt;/element&gt;&lt;element&gt;value2&lt;/element&gt;&lt;element object_element_prop="123"&gt;&lt;object_element_node&gt;123&lt;/object_element_node&gt;&lt;/element&gt;</array></root>')) {
	throw new Exception('bad value');
}

/** ************************* array **************************** **/

$lInterfacer= new AssocArrayInterfacer();
$lNode = $lInterfacer->createNode('root');
$lCreatedNode = $lInterfacer->createNode('object');
$lInterfacer->setValue($lCreatedNode, 'value', 'prop');
$lInterfacer->setValue($lCreatedNode, 'value_node', 'prop_node', true);
$lInterfacer->setValue($lNode, $lCreatedNode, 'object');
$lInterfacer->setValue($lNode, 'root_value', 'root_prop');

$lNodeArray = $lInterfacer->createNodeArray('array');
$lInterfacer->addValue($lNodeArray, 'value1', 'element');
$lInterfacer->addValue($lNodeArray, 'value2', 'element');

$lCreatedNode = $lInterfacer->createNode('element');
$lInterfacer->setValue($lCreatedNode, 123, 'object_element_prop');
$lInterfacer->setValue($lCreatedNode, 123, 'object_element_node', true);
$lInterfacer->addValue($lNodeArray, $lCreatedNode);

$lInterfacer->setValue($lNode, $lNodeArray, 'array');
$lInterfacer->flattenNode($lNode, 'array');

if ($lInterfacer->toString($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	var_dump($lInterfacer->toString($lNode));
	throw new Exception('bad value');
}

$lInterfacer->unflattenNode($lNode, 'array');
if ($lInterfacer->toString($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":["value1","value2",{"object_element_prop":123,"object_element_node":123}]}') {
	var_dump($lInterfacer->toString($lNode));
	throw new Exception('bad value');
}

$lInterfacer->flattenNode($lNode, 'array');
if ($lInterfacer->toString($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	var_dump($lInterfacer->toString($lNode));
	throw new Exception('bad value');
}

/** ************************* preferences **************************** **/

$lPreferences = [
	Interfacer::PRIVATE_CONTEXT        => true,
	Interfacer::SERIAL_CONTEXT         => true,
	Interfacer::DATE_TIME_ZONE         => 'Pacific/Tahiti',
	Interfacer::DATE_TIME_FORMAT       => 'Y-m-d H:i:s',
	Interfacer::ONLY_UPDATED_VALUES    => true,
	Interfacer::PROPERTIES_FILTERS     => ['person' => ['haha', 'hoho'], 'place' => ['plop1', 'plop2']],
	Interfacer::FLATTEN_VALUES         => true,
	Interfacer::MAIN_FOREIGN_OBJECTS   => true,
	Interfacer::FLAG_VALUES_AS_UPDATED => false,
	Interfacer::FLAG_OBJECT_AS_LOADED  => false,
	Interfacer::MERGE_TYPE             => Interfacer::NO_MERGE
];

$lInterfacer->setPreferences($lPreferences);

if ($lInterfacer->isPrivateContext() !== true) {
	throw new Exception('bad value');
}
if ($lInterfacer->isSerialContext() !== true) {
	throw new Exception('bad value');
}
if ($lInterfacer->getDateTimeZone()->getName() !== 'Pacific/Tahiti') {
	throw new Exception('bad value');
}
if ($lInterfacer->getDateTimeFormat() !== 'Y-m-d H:i:s') {
	throw new Exception('bad value');
}
if ($lInterfacer->hasToExportOnlyUpdatedValues() !== true) {
	throw new Exception('bad value');
}
if ($lInterfacer->getPropertiesFilter('haha') !== null) {
	throw new Exception('bad value');
}
if (json_encode($lInterfacer->getPropertiesFilter('person')) !== '{"haha":0,"hoho":1,"id":null}') {
	throw new Exception('bad value');
}
if ($lInterfacer->hasToFlattenValues() !== true) {
	throw new Exception('bad value');
}
if ($lInterfacer->hasToExportMainForeignObjects() !== true) {
	throw new Exception('bad value');
}
if ($lInterfacer->hasToFlagValuesAsUpdated() !== false) {
	throw new Exception('bad value');
}
if ($lInterfacer->hasToFlagObjectAsLoaded() !== false) {
	throw new Exception('bad value');
}
if ($lInterfacer->getMergeType() !== Interfacer::NO_MERGE) {
	throw new Exception('bad value');
}

$time_end = microtime(true);
var_dump('interfacer test exec time '.($time_end - $time_start));
