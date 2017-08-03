<?php


use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Interfacer\Interfacer;
use Object\Person;
use Comhon\Interfacer\AssocArrayInterfacer;

$time_start = microtime(true);

/** ************************* std **************************** **/

$interfacer= new StdObjectInterfacer();
$node = $interfacer->createNode('root');
$createdNode = $interfacer->createNode('object');
$interfacer->setValue($node, $createdNode, 'object');
$interfacer->setValue($createdNode, 'value', 'prop');
$interfacer->setValue($createdNode, 'value_node', 'prop_node', true);
$interfacer->setValue($node, 'root_value', 'root_prop');

$nodeArray = $interfacer->createArrayNode('array');
$interfacer->addValue($nodeArray, 'value1', 'element');
$interfacer->addValue($nodeArray, 'value2', 'element');

$createdNode = $interfacer->createNode('element');
$interfacer->setValue($createdNode, 123, 'object_element_prop');
$interfacer->setValue($createdNode, 123, 'object_element_node', true);
$interfacer->addValue($nodeArray, $createdNode);

$interfacer->setValue($node, $nodeArray, 'array');
$interfacer->flattenNode($node, 'array');

if ($interfacer->toString($node) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	throw new \Exception('bad value');
}

$interfacer->unflattenNode($node, 'array');
if ($interfacer->toString($node) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":["value1","value2",{"object_element_prop":123,"object_element_node":123}]}') {
	throw new \Exception('bad value');
}

$interfacer->flattenNode($node, 'array');
if ($interfacer->toString($node) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	throw new \Exception('bad value');
}

/** ************************* XML **************************** **/

$interfacer = new XMLInterfacer();
$node = $interfacer->createNode('root');
$createdNode = $interfacer->createNode('object');
$interfacer->setValue($node, $createdNode, 'object');
$interfacer->setValue($createdNode, 'value', 'prop');
$interfacer->setValue($createdNode, 'value_node', 'prop_node', true);
$interfacer->setValue($node, 'root_value', 'root_prop');

$nodeArray = $interfacer->createArrayNode('array');
$interfacer->addValue($nodeArray, 'value1', 'element');
$interfacer->addValue($nodeArray, 'value2', 'element');

$createdNode = $interfacer->createNode('element');
$interfacer->setValue($createdNode, 123, 'object_element_prop');
$interfacer->setValue($createdNode, 123, 'object_element_node', true);
$interfacer->addValue($nodeArray, $createdNode);

$interfacer->setValue($node, $nodeArray, 'array');
$interfacer->flattenNode($node, 'array');

if (!compareXML($interfacer->toString($node), '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array>&lt;element&gt;value1&lt;/element&gt;&lt;element&gt;value2&lt;/element&gt;&lt;element object_element_prop="123"&gt;&lt;object_element_node&gt;123&lt;/object_element_node&gt;&lt;/element&gt;</array></root>')) {
	throw new \Exception('bad value');
}

$interfacer->unflattenNode($node, 'array');
if (!compareXML($interfacer->toString($node), '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array><element>value1</element><element>value2</element><element object_element_prop="123"><object_element_node>123</object_element_node></element></array></root>')) {
	throw new \Exception('bad value');
}

$interfacer->flattenNode($node, 'array');
if (!compareXML($interfacer->toString($node), '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array>&lt;element&gt;value1&lt;/element&gt;&lt;element&gt;value2&lt;/element&gt;&lt;element object_element_prop="123"&gt;&lt;object_element_node&gt;123&lt;/object_element_node&gt;&lt;/element&gt;</array></root>')) {
	throw new \Exception('bad value');
}

/** ************************* array **************************** **/

$interfacer= new AssocArrayInterfacer();
$node = $interfacer->createNode('root');
$createdNode = $interfacer->createNode('object');
$interfacer->setValue($createdNode, 'value', 'prop');
$interfacer->setValue($createdNode, 'value_node', 'prop_node', true);
$interfacer->setValue($node, $createdNode, 'object');
$interfacer->setValue($node, 'root_value', 'root_prop');

$nodeArray = $interfacer->createArrayNode('array');
$interfacer->addValue($nodeArray, 'value1', 'element');
$interfacer->addValue($nodeArray, 'value2', 'element');

$createdNode = $interfacer->createNode('element');
$interfacer->setValue($createdNode, 123, 'object_element_prop');
$interfacer->setValue($createdNode, 123, 'object_element_node', true);
$interfacer->addValue($nodeArray, $createdNode);

$interfacer->setValue($node, $nodeArray, 'array');
$interfacer->flattenNode($node, 'array');

if ($interfacer->toString($node) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	throw new \Exception('bad value');
}

$interfacer->unflattenNode($node, 'array');
if ($interfacer->toString($node) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":["value1","value2",{"object_element_prop":123,"object_element_node":123}]}') {
	throw new \Exception('bad value');
}

$interfacer->flattenNode($node, 'array');
if ($interfacer->toString($node) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	throw new \Exception('bad value');
}

/** ************************* preferences **************************** **/

$preferences = [
	Interfacer::PRIVATE_CONTEXT        => true,
	Interfacer::SERIAL_CONTEXT         => true,
	Interfacer::DATE_TIME_ZONE         => 'Pacific/Tahiti',
	Interfacer::DATE_TIME_FORMAT       => 'Y-m-d H:i:s',
	Interfacer::ONLY_UPDATED_VALUES    => true,
	Interfacer::PROPERTIES_FILTERS     => ['person' => ['haha', 'hoho'], 'place' => ['plop1', 'plop2']],
	Interfacer::FLATTEN_VALUES         => true,
	Interfacer::EXPORT_MAIN_FOREIGN_OBJECTS   => true,
	Interfacer::FLAG_VALUES_AS_UPDATED => false,
	Interfacer::FLAG_OBJECT_AS_LOADED  => false,
	Interfacer::MERGE_TYPE             => Interfacer::NO_MERGE
];

$interfacer->setPreferences($preferences);

if ($interfacer->isPrivateContext() !== true) {
	throw new \Exception('bad value');
}
if ($interfacer->isSerialContext() !== true) {
	throw new \Exception('bad value');
}
if ($interfacer->getDateTimeZone()->getName() !== 'Pacific/Tahiti') {
	throw new \Exception('bad value');
}
if ($interfacer->getDateTimeFormat() !== 'Y-m-d H:i:s') {
	throw new \Exception('bad value');
}
if ($interfacer->hasToExportOnlyUpdatedValues() !== true) {
	throw new \Exception('bad value');
}
if ($interfacer->getPropertiesFilter('haha') !== null) {
	throw new \Exception('bad value');
}
if (json_encode($interfacer->getPropertiesFilter('person')) !== '{"haha":0,"hoho":1,"id":null}') {
	throw new \Exception('bad value');
}
if ($interfacer->hasToFlattenValues() !== true) {
	throw new \Exception('bad value');
}
if ($interfacer->hasToExportMainForeignObjects() !== true) {
	throw new \Exception('bad value');
}
if ($interfacer->hasToFlagValuesAsUpdated() !== false) {
	throw new \Exception('bad value');
}
if ($interfacer->hasToFlagObjectAsLoaded() !== false) {
	throw new \Exception('bad value');
}
if ($interfacer->getMergeType() !== Interfacer::NO_MERGE) {
	throw new \Exception('bad value');
}

$time_end = microtime(true);
var_dump('interfacer test exec time '.($time_end - $time_start));
