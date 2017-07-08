<?php

use Comhon\Api\ObjectService;

$time_start = microtime(true);


/** ****************************** test private request ****************************** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"properties" : ["date","timestamp","integer","string"],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "testDb",
				"property" : "boolean2",
				"operator" : "=",
				"value"    : true
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success 
		|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "private property 'string' can't be a filter property for public request") {
	throw new Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ****************************** test literal private request ****************************** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "testDb",
				"property" : "string",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal contain private property 'string'") {
	throw new Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ****************************** test literal private request ****************************** **/

$Json = '{
	"model" : "mainTestDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"     : "mainTestDb",
				"queue"     : {"property" : "childrenTestDb"},
				"havingClause" : {
					"type" : "conjunction",
					"elements" : [
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

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "having literal contain private property 'string'") {
	throw new Exception('bad ObjectService::getObjects return '.json_encode($result));
}

$time_end = microtime(true);
var_dump('request failure test exec time '.($time_end - $time_start));