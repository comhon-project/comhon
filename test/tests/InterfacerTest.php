<?php


use comhon\interfacer\StdObjectInterfacer;
use comhon\interfacer\XMLInterfacer;
use comhon\model\singleton\ModelManager;
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

if ($lInterfacer->serialize($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	var_dump($lInterfacer->serialize($lNode));
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

if (trim(str_replace('<?xml version="1.0"?>', '', $lInterfacer->serialize($lNode)))!== '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array>&lt;element&gt;value1&lt;/element&gt;&lt;element&gt;value2&lt;/element&gt;&lt;element object_element_prop="123"&gt;&lt;object_element_node&gt;123&lt;/object_element_node&gt;&lt;/element&gt;</array></root>') {
	var_dump(trim(str_replace('<?xml version="1.0"?>', '', $lInterfacer->serialize($lNode))));
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

if ($lInterfacer->serialize($lNode) !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":"[\"value1\",\"value2\",{\"object_element_prop\":123,\"object_element_node\":123}]"}') {
	var_dump($lInterfacer->serialize($lNode));
	throw new Exception('bad value');
}

/** ************************* preferences **************************** **/

$lPreferences = [
	Interfacer::PRIVATE                => true,
	Interfacer::SERIAL_CONTEXT         => true,
	Interfacer::DATE_TIME_ZONE         => 'Pacific/Tahiti',
	Interfacer::DATE_TIME_FORMAT       => 'Y-m-d H:i:s',
	Interfacer::ONLY_UPDATED_VALUES    => true,
	Interfacer::PROPERTIES_FILTERS     => ['person' => ['haha', 'hoho'], 'place' => ['plop1', 'plop2']],
	Interfacer::FLATTEN_VALUES         => true,
	Interfacer::MAIN_FOREIGN_OBJECTS   => true,
	Interfacer::FLAG_VALUES_AS_UPDATED => true,
	Interfacer::MERGE_TYPE             => Interfacer::NO_MERGE
];

$lInterfacer->setPreferences($lPreferences);

if ($lInterfacer->interfacePrivateProperties() !== true) {
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
$lInterfacer->addMainForeignObject(['plop' => 'plop'], 12, ModelManager::getInstance()->getInstanceModel('person'));
if (json_encode($lInterfacer->getMainForeignObjects()) !== '{"person":{"12":{"plop":"plop"}}}') {
	throw new Exception('bad value');
}
if ($lInterfacer->hasToFlagValuesAsUpdated() !== true) {
	throw new Exception('bad value');
}
if ($lInterfacer->getMergeType() !== Interfacer::NO_MERGE) {
	throw new Exception('bad value');
}

/** ************************* export stdClass **************************** **/
$lPreferences[Interfacer::FLATTEN_VALUES] = true;
$lPreferences[Interfacer::ONLY_UPDATED_VALUES] = false;
$lPreferences[Interfacer::SERIAL_CONTEXT] = false;
$lPreferences[Interfacer::MAIN_FOREIGN_OBJECTS] = true;
$lPreferences[Interfacer::MERGE_TYPE] = Interfacer::NO_MERGE;
$lPreferences[Interfacer::PROPERTIES_FILTERS] = ['place' => ['firstName', 'birthPlace'], 'womanBody' => ['date', 'tatoos']];

$lDbTestModel = ModelManager::getInstance()->getInstanceModel('testDb');
$lObject = $lDbTestModel->loadObject('[1,1501774389]');
$lInterfacer = new XMLInterfacer();
$lNode = $lInterfacer->export($lObject, $lPreferences);
$lObject2 = $lInterfacer->import($lNode, $lDbTestModel, $lPreferences);
$lNode2 = $lInterfacer->export($lObject2, $lPreferences);

$lDbTestModel->fillObject($lObject, $lNode2, $lInterfacer);


$time_end = microtime(true);
var_dump('interfacer test exec time '.($time_end - $time_start));
