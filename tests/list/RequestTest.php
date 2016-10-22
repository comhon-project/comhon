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

$lResult = ObjectService::getObjects(json_decode($Json));

if (!is_object($lResult) || !isset($lResult->success) || !$lResult->success || !isset($lResult->result) || !is_array($lResult->result)) {
	throw new Exception('bad ObjectService::getObjects return '.json_encode($lResult));
}

if (json_encode($lResult->result) != '[{"id1":"1","id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":"0","mainParentTestDb":"1"},{"id1":"1","id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"string":"cccc","integer":"2","mainParentTestDb":"1"}]') {
	throw new Exception('bad objects : '.json_encode($lResult->result));
}

/** ****************************** test following export import objects ****************************** **/

$lBasedObjects  = [
	json_decode('{"id1":"1","id2":"23","date":"2016-05-01T14:53:54+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"aaaa","integer":0,"mainParentTestDb":"1"}'),
	json_decode('{"id1":"1","id2":"101","date":"2016-04-13T09:14:33+02:00","timestamp":"2016-10-16T21:50:19+02:00","string":"cccc","integer":2,"object":{"plop":"plop","plop2":"plop2"},"objectWithId":{"plop":"plop","plop2":"plop2"},"mainParentTestDb":"1"}')
];

$lObject = null;
foreach ($lResult->result as $lIndex => $lPhpObject) {
	$lObject = new Object('testDb');
	$lObject->fromObject($lPhpObject);

	$lObject2 = new Object('testDb');
	$lObject2->fromXml($lObject->toXml(false));
	
	if (json_encode($lObject2->toObject(false, 'Europe/Berlin')) !== json_encode($lBasedObjects[$lIndex])) {
		throw new Exception('bad object : '.json_encode($lObject2->toObject(false, 'Europe/Berlin')));
	}
}

/** ****************************** test DateTime/DateTimeZone with database serialization ****************************** **/

$lDbTestModel = InstanceModel::getInstance()->getInstanceModel('testDb');

$lObject = $lDbTestModel->loadObject('[1,1501774389]');
$lObjectJson = $lObject->toObject();
$lObject->getValue('timestamp')->sub(new DateInterval('P0Y0M0DT5H0M0S'));
$lObject->save(SqlTable::UPDATE);

$lObject = $lDbTestModel->loadObject('[1,1501774389]', true);
$lObject->getValue('timestamp')->add(new DateInterval('P0Y0M0DT5H0M0S'));
$lObject->save(SqlTable::UPDATE);

$lObject = $lDbTestModel->loadObject('[1,1501774389]', true);

if (json_encode($lObject->toObject()) !== json_encode($lObjectJson)) {
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
if (json_encode($lResult->result) !== json_encode($lObjectJson)) {
	throw new Exception('bad object');
}

$time_end = microtime(true);
var_dump('model test exec time '.($time_end - $time_start));