<?php


use comhon\interfacer\JSONInterfacer;
use comhon\interfacer\XMLInterfacer;

$time_start = microtime(true);

$lInterfacer= new JSONInterfacer();
$lNode = $lInterfacer->initialize('root');
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

if ($lInterfacer->serialize() !== '{"object":{"prop":"value","prop_node":"value_node"},"root_prop":"root_value","array":["value1","value2",{"object_element_prop":123,"object_element_node":123}]}') {
	throw new Exception('bad value');
}

$time_start = microtime(true);

$lInterfacer = new XMLInterfacer();
$lNode = $lInterfacer->initialize('root');
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

if (trim(str_replace('<?xml version="1.0"?>', '', $lInterfacer->serialize()))!== '<root root_prop="root_value"><object prop="value"><prop_node>value_node</prop_node></object><array><element>value1</element><element>value2</element><element object_element_prop="123"><object_element_node>123</object_element_node></element></array></root>') {
	throw new Exception('bad value');
}

$time_end = microtime(true);
var_dump('interfacer test exec time '.($time_end - $time_start));
