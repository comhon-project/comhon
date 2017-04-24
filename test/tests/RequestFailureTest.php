<?php

use comhon\api\ObjectService;

$time_start = microtime(true);


/** ****************************** test private request ****************************** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"properties" : ["date","timestamp","integer","string"],
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
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

if (!is_object($lResult) || !isset($lResult->success) || $lResult->success 
		|| !isset($lResult->error) || !isset($lResult->error->message) || $lResult->error->message !== "private property 'string' can't be a filter property for public request") {
	var_dump(json_encode($lResult));
	throw new Exception('bad ObjectService::getObjects return '.json_encode($lResult));
}

/** ****************************** test literal private request ****************************** **/

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
				"value"    : "plop"
			}
		]
	}
}';

$lResult = ObjectService::getObjects(json_decode($Json));

if (!is_object($lResult) || !isset($lResult->success) || $lResult->success
|| !isset($lResult->error) || !isset($lResult->error->message) || $lResult->error->message !== "literal contain private property 'string'") {
	var_dump(json_encode($lResult));
	throw new Exception('bad ObjectService::getObjects return '.json_encode($lResult));
}

/** ****************************** test literal private request ****************************** **/

$Json = '{
	"model" : "mainTestDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"logicalJunction" : {
		"type" : "conjunction",
		"literals" : [
			{
				"model"     : "mainTestDb",
				"queue"     : {"property" : "childrenTestDb"},
				"havingLogicalJunction" : {
					"type" : "conjunction",
					"literals" : [
						{
							"function" : "MAX",
							"property" : "string",
							"operator" : "=",
							"value"    : 3
						}
					]
				}
			}
		]
	}
}';

$lResult = ObjectService::getObjects(json_decode($Json));

if (!is_object($lResult) || !isset($lResult->success) || $lResult->success
|| !isset($lResult->error) || !isset($lResult->error->message) || $lResult->error->message !== "having literal contain private property 'string'") {
	var_dump(json_encode($lResult));
	throw new Exception('bad ObjectService::getObjects return '.json_encode($lResult));
}

$time_end = microtime(true);
var_dump('request test exec time '.($time_end - $time_start));