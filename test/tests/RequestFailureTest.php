<?php

use Comhon\Api\ObjectService;

$time_start = microtime(true);


/** ****************************** test private property in selected properties request ****************************** **/

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
		|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "cannot use private property 'string' in public context"
		|| !isset($result->error->code) || $result->error->code !== 108
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
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
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "cannot use private property 'string' in public context"
	|| !isset($result->error->code) || $result->error->code !== 108
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ****************************** test having literal private request ****************************** **/

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
				"having" : {
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
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "cannot use private property 'string' in public context"
	|| !isset($result->error->code) || $result->error->code !== 108
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ****************************** test literal aggregation request ****************************** **/

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
				"property" : "childrenTestDb",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal cannot contain aggregation property 'childrenTestDb' except in queue node"
	|| !isset($result->error->code) || $result->error->code !== 703
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ****************************** test literal with undefined id request ****************************** **/

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"tree" : {
		"model"   : "testDb",
		"id"      : "p1"
	},
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{"id" : "l1"}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal with id 'l1' not found in literal collection"
	|| !isset($result->error->code) || $result->error->code !== 701
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ****************** test literal with unresolvable model request **************** **/

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"model" : "locatedHouse",
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "town",
				"property" : "name",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "Cannot resolve literal with model 'town', it might be applied on several properties"
	|| !isset($result->error->code) || $result->error->code !== 705
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** **************** test literal with not linked model inter request **************** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "person",
				"property" : "name",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "model 'person' from literal {\"model\":\"person\",\"property\":\"name\",\"operator\":\"=\",\"value\":\"plop\"} is not linked to requested model 'testDb' or doesn't have compatible serialization"
	|| !isset($result->error->code) || $result->error->code !== 706
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ****** test literal with not linked model (diff db connection) inter request ****** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "notLinkableTestDb",
				"property" : "name",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "model 'notLinkableTestDb' from literal {\"model\":\"notLinkableTestDb\",\"property\":\"name\",\"operator\":\"=\",\"value\":\"plop\"} is not linked to requested model 'testDb' or doesn't have compatible serialization"
	|| !isset($result->error->code) || $result->error->code !== 706
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** ******* test literal with not linked model (db/file system) inter request ****** **/

$Json = '{
	"model" : "testDb",
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"model"    : "test",
				"property" : "name",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
		|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "model 'test' from literal {\"model\":\"test\",\"property\":\"name\",\"operator\":\"=\",\"value\":\"plop\"} is not linked to requested model 'testDb' or doesn't have compatible serialization"
		|| !isset($result->error->code) || $result->error->code !== 706
		) {
			throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
		}

/** ****** test literal with different serialisation (db/file system) complex request ****** **/

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"tree" : {
		"model"   : "testDb",
		"id"      : "p1",
		"children" : [
			{
				"property" : "notLinkableTestObjValue",
				"id"       : "p2"
			}
		]
	},
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"node"    : "p2",
				"property" : "stringValue",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal (with property 'notLinkableTestObjValue') serialization incompatible with requested model serialization"
	|| !isset($result->error->code) || $result->error->code !== 702
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}
		
/** ************ test literal with different db connections complex request ************ **/

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"tree" : {
		"model"   : "testDb",
		"id"      : "p1",
		"children" : [
			{
				"property" : "notLinkableTestDb",
				"id"       : "p2"
			}
		]
	},
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"node"    : "p2",
				"property" : "name",
				"operator" : "=",
				"value"    : "plop"
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal (with property 'notLinkableTestDb') serialization incompatible with requested model serialization"
	|| !isset($result->error->code) || $result->error->code !== 702
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}
		
/** ***** test HAVING literal (aggregation) with different db connections complex request ***** **/

$Json = '{
	"requestChildren" : false,
	"loadForeignProperties" : false,
	"order" : [{"property":"id1", "type":"DESC"}],
	"tree" : {
		"model"   : "testDb",
		"id"      : "p1"
	},
	"filter" : {
		"type" : "conjunction",
		"elements" : [
			{
				"node"      : "p1",
				"queue"     : {
					"property" : "notLinkableArrayTestDb"
				},
				"having" : {
					"type" : "conjunction",
					"elements" : [
						{
							"function" : "COUNT",
							"operator" : "=",
							"value"    : 170
						}
					]
				}
			}
		]
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal (with property 'notLinkableArrayTestDb') serialization incompatible with requested model serialization"
	|| !isset($result->error->code) || $result->error->code !== 702
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** *************** test complex request model without sql serialization *************** **/

$Json = '{
	"tree" : {
		"model"   : "test",
		"id"      : "p1"
	}
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "intermediate or complex request not allowed for model 'test'"
	|| !isset($result->error->code) || $result->error->code !== 707
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** *************** test simple request model without id request *************** **/

$Json = '{
	"model"   : "test"
}';

$result = ObjectService::getObject(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "request doesn't have id"
	|| !isset($result->error->code) || $result->error->code !== 700
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** *************** test simple request model without id property *************** **/

$Json = '{
	"model"   : "testNoId",
	"id"      : "an_id"
}';

$result = ObjectService::getObject(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "simple request not allowed for model 'testNoId'"
	|| !isset($result->error->code) || $result->error->code !== 707
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}

/** *************** test simple request model without serialization *************** **/

$Json = '{
	"model"   : "testNoSerialization",
	"id"      : 12
}';

$result = ObjectService::getObject(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "request not allowed for model 'testNoSerialization'"
	|| !isset($result->error->code) || $result->error->code !== 707
) {
	throw new \Exception('bad ObjectService::getObjects return '.json_encode($result));
}


$time_end = microtime(true);
var_dump('request failure test exec time '.($time_end - $time_start));