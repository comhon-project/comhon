<?php

use comhon\object\singleton\InstanceModel;
use comhon\object\object\Object;
use comhon\api\ObjectService;
use comhon\object\object\SqlTable;
use comhon\object\SimpleLoadRequest;
use comhon\object\MainObjectCollection;

$time_start = microtime(true);

$Json = '{
	"model" : "person",
	"requestChildren" : true,
	"loadForeignProperties" : true,
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
			{
				"model"    : "person",
				"property" : "firstName",
				"operator" : "=",
				"value"    : ["Paul", "Bernard", null]
			},
			{
				"model"    : "house",
				"property" : "surface",
				"operator" : ">",
				"value"    : 200
			},
			{
				"model"     : "person",
				"queue"     : {"property" : "children"},
				"havingLogicalJunction" : {
					"type" : "conjunction",
					"literals" : [
						{
							"function" : "COUNT",
							"operator" : ">",
							"value"    : 1
						},
						{
							"function" : "COUNT",
							"operator" : "<",
							"value"    : 3
						},
						{
							"function" : "AVG",
							"property" : "age",
							"operator" : ">",
							"value"    : 3
						}
					]
				}
			},
			{
				"model"     : "person",
				"queue"     : {
					"property" : "homes", 
					"child" : {
						"property" : "house"
					}
				},
				"havingLiteral" : {
					"function" : "AVG",
					"property" : "surface",
					"operator" : "=",
					"value"    : 170
				}
			}
		],
		"logicalJunctions" : [
			{
				"type" : "conjunction",
				"literals" : [
					{
						"model"    : "house",
						"property" : "surface",
						"operator" : ">",
						"value"    : 250
					},
					{
						"model"     : "person",
						"queue"     : {"property" : "homes"},
						"havingLogicalJunction" : {
							"type" : "disjunction",
							"literals" : [
								{
									"function" : "COUNT",
									"operator" : ">=",
									"value"    : 3
								},
								{
									"function" : "COUNT",
									"operator" : ">",
									"value"    : 2
								}
							],
							"logicalJunctions" : [
								{
									"type" : "conjunction",
									"literals" : [
										{
											"function" : "COUNT",
											"operator" : ">=",
											"value"    : 3
										},
										{
											"function" : "COUNT",
											"operator" : ">",
											"value"    : 2
										}
									]
								}
							]
						}
					},
					{
						"model"    : "town",
						"property" : "name",
						"operator" : "=",
						"value"    : "Montpellier"
					}
				],
				"logicalJunctions" : []
			}
		]
	}
}';

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"logicalJunction" : {
		"type" : "disjunction",
		"literals" : [
			{
				"model"    : "testDb",
				"property" : "string",
				"operator" : "=",
				"value"    : ["aaaa","cccc","bbbbsdfsdfsdf"]
			}
		]
	}
}';

/*
$Json = '{
	"model" : "person",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id", "type":"DESC"}],
	"logicalJunction" : {
		"type" : "disjunction",
		"literals" : []
	}
}';*/


/** ****************************** test request objects ****************************** **/
$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
			{
				"model"    : "testDb",
				"property" : "string",
				"operator" : "=",
				"value"    : ["aaaa","cccc","bbbbsdfsdfsdf"]
			},
			{
				"model"    : "testDb",
				"property" : "boolean2",
				"operator" : "=",
				"value"    : true
			}
		]
	}
}';

$lResult = ObjectService::getObjects(json_decode($Json));

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success || !isset($lResult->result) || !is_array($lResult->result)) {
	var_dump(json_encode($lResult));
	throw new Exception('bad ObjectService::getObjects return '.json_encode($lResult));
}

if (json_encode($lResult->result) != '[{"id1":1,"id2":"23","defaultValue":"default","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true},{"id1":1,"id2":"101","defaultValue":"default","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}]') {
	var_dump(json_encode($lResult->result));
	throw new Exception('bad objects : '.json_encode($lResult->result));
}

/** ****************************** test following export import objects ****************************** **/

$lBasedObjects  = [
	json_decode('{"id1":1,"id2":"23","defaultValue":"default","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}'),
	json_decode('{"id1":1,"id2":"101","defaultValue":"default","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"integer":2,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"boolean":false,"boolean2":true}')
];

$lObject = null;
foreach ($lResult->result as $lIndex => $lStdObject) {
	$lObject = new Object('testDb');
	try {
		$lObject->fromPrivateStdObject($lStdObject);
		$lThrow = true;
	} catch (Exception $e) {
		$lThrow = false;
	}
	if ($lThrow) {
		throw new Exception('import should works other instance already exists');
	}
	$lId1 = $lStdObject->id1;
	$lId2 = $lStdObject->id2;
	unset($lStdObject->id1);
	unset($lStdObject->id2);
	
	$lObject = new Object('testDb');
	$lObject->fromPrivateStdObject($lStdObject);

	$lObject2 = new Object('testDb');
	$lObject2->fromSerializedXml($lObject->toSerialXml());
	$lObject2->setIdValue('id1', $lId1);
	$lObject2->setIdValue('id2', $lId2);
	
	if (json_encode($lObject2->toPrivateStdObject('Europe/Berlin')) !== json_encode($lBasedObjects[$lIndex])) {
		throw new Exception('bad object : '.json_encode($lObject2->toPrivateStdObject('Europe/Berlin')));
	}
}

/** ****************************** test DateTime/DateTimeZone with database serialization ****************************** **/

$lDbTestModel = InstanceModel::getInstance()->getInstanceModel('testDb');

$lObject = $lDbTestModel->loadObject('[1,1501774389]');
$lObjectJson = $lObject->toPrivateStdObject();
$lPublicObjectJson = $lObject->toPublicStdObject();
$lObject->getValue('timestamp')->sub(new DateInterval('P0Y0M0DT5H0M0S'));
$lObject->save(SqlTable::UPDATE);

$lObject = $lDbTestModel->loadObject('[1,1501774389]', true);
$lObject->getValue('timestamp')->add(new DateInterval('P0Y0M0DT5H0M0S'));
$lObject->setValue('notSerializedValue', 'azezaeaze');
$lObject->setValue('notSerializedForeignObject', $lObject->getValue('lonelyForeignObject'));
$lObject->save(SqlTable::UPDATE);

$lObject->deleteValue('notSerializedValue');
$lObject->deleteValue('notSerializedForeignObject');

$lObject = $lDbTestModel->loadObject('[1,1501774389]', true);

if (json_encode($lObject->toPrivateStdObject()) !== json_encode($lObjectJson)) {
	throw new Exception('bad object');
}

/** ****************************** test simple load request api ****************************** **/

$lParams = new stdClass();
$lParams->model = 'testDb';
$lParams->id = '[1,1501774389]';
$lResult = ObjectService::getObject($lParams);

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success) {
	throw new Exception('simple load request failed');
}
if (json_encode($lResult->result) !== json_encode($lPublicObjectJson)) {
	throw new Exception('bad object');
}

$time_end = microtime(true);
var_dump('request test exec time '.($time_end - $time_start));