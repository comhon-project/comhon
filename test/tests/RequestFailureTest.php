<?php

use Test\Comhon\Service\ObjectService;

$time_start = microtime(true);


/** ****************************** test private property in selected properties request ****************************** **/

$Json = '{
	"tree" : {
		"model"   : "Test\\\\TestDb",
		"id"      : 1
	},
	"order" : [{"property":"id1", "type":"DESC"}],
	"properties" : ["date","timestamp","integer","string"],
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "boolean2",
			"operator" : "=",
			"value"    : true,
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Boolean"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success 
		|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "cannot use private property 'string' in public context"
		|| !isset($result->error->code) || $result->error->code !== 108
) {
	throw new \Exception('bad ObjectService::getObjects return 1 '.json_encode($result));
}

/** ****************************** test literal private request ****************************** **/

$Json = '{
	"tree" : {
		"model"   : "Test\\\\TestDb",
		"id"      : 1
	},
	"order" : [{"property":"id1", "type":"DESC"}],
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "string",
			"operator" : "=",
			"value"    : "plop",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "cannot use private property 'string' in public context"
	|| !isset($result->error->code) || $result->error->code !== 108
) {
	throw new \Exception('bad ObjectService::getObjects return 2 '.json_encode($result));
}

/** ****************************** test having literal private request ****************************** **/

$Json = '{
	"tree" : {
		"model"   : "Test\\\\MainTestDb",
		"id"      : 1
	},
	"order" : [{"property":"id1", "type":"DESC"}],
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"queue"     : ["childrenTestDb"],
			"having" : 1,
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Having"
		}
	],
	"havingCollection" : [
		{
			"id"       : 1,
			"function" : "MAX",
			"property" : "string",
			"operator" : "=",
			"value"    : 3,
        	"__inheritance__": "Comhon\\\\Logic\\\\Having\\\\Literal\\\\Function"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "cannot use private property 'string' in public context"
	|| !isset($result->error->code) || $result->error->code !== 108
) {
	throw new \Exception('bad ObjectService::getObjects return 3 '.json_encode($result));
}

/** ****************************** test literal aggregation request ****************************** **/

$Json = '{
	"tree" : {
		"model"   : "Test\\\\TestDb",
		"id"      : 1
	},
	"order" : [{"property":"id1", "type":"DESC"}],
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "childrenTestDb",
			"operator" : "=",
			"value"    : "plop",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== 'there is no literal allowed on property \'childrenTestDb\' of model \'Test\TestDb\'.'
	|| !isset($result->error->code) || $result->error->code !== 709
		) {
	throw new \Exception('bad ObjectService::getObjects return 4 '.json_encode($result));
}


/** ****************** test literal with unresolvable model request **************** **/

$Json = '{
	"root": 1,
	"models" : [
		{
			"model"   : "Test\\\\LocatedHouse",
			"id"      : 1
		},
		{
			"model"   : "Test\\\\Town",
			"id"      : 2
		}
	],
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 2,
			"property" : "name",
			"operator" : "=",
			"value"    : "plop",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Intermediate"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "Cannot resolve literal with model 'Test\Town', it might be applied on several properties"
	|| !isset($result->error->code) || $result->error->code !== 705
) {
	throw new \Exception('bad ObjectService::getObjects return 6 '.json_encode($result));
}

/** ******* test literal with not linked model (db/file system) inter request ****** **/

$Json = '{
	"root": 1,
	"models" : [
		{
			"model"   : "Test\\\\TestDb",
			"id"      : 1
		},
		{
			"model"   : "Test\\\\Test",
			"id"      : 2
		}
	],
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 2,
			"property" : "name",
			"operator" : "=",
			"value"    : "plop",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Intermediate"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
		|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "model 'Test\Test' from literal {\"id\":1,\"node\":2,\"property\":\"name\",\"operator\":\"=\",\"value\":\"plop\"} is not linked to requested model 'Test\TestDb' or doesn't have compatible serialization"
		|| !isset($result->error->code) || $result->error->code !== 706
) {
	throw new \Exception('bad ObjectService::getObjects return 9 '.json_encode($result));
}

/** ****** test literal with different serialisation (db/file system) complex request ****** **/

$Json = '{
	"tree" : {
		"id"      : 1,
		"model"   : "Test\\\\TestDb",
		"nodes" : [
			{
				"id"      : 2,
				"property" : "notLinkableTestObjValue"
			}
		]
	},
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 2,
			"property" : "stringValue",
			"operator" : "=",
			"value"    : "plop",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal (with property 'notLinkableTestObjValue') serialization incompatible with requested model serialization"
	|| !isset($result->error->code) || $result->error->code !== 702
) {
	throw new \Exception('bad ObjectService::getObjects return 10 '.json_encode($result));
}
		
/** ************ test literal with different db connections complex request ************ **/

$Json = '{
	"tree" : {
		"id"      : 1,
		"model"   : "Test\\\\TestDb",
		"nodes" : [
			{
				"id"      : 2,
				"property" : "notLinkableTestDb"
			}
		]
	},
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 2,
			"property" : "name",
			"operator" : "=",
			"value"    : "plop",
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\String"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal (with property 'notLinkableTestDb') serialization incompatible with requested model serialization"
	|| !isset($result->error->code) || $result->error->code !== 702
) {
	throw new \Exception('bad ObjectService::getObjects return 11 '.json_encode($result));
}
		
/** ***** test HAVING literal (aggregation) with different db connections complex request ***** **/

$Json = '{
	"tree" : {
		"model"   : "Test\\\\TestDb",
		"id"      : 1
	},
	"order" : [{"property":"id1", "type":"DESC"}],
	"simpleCollection" : [
		{
			"id"       : 1,
			"node"     : 1,
			"queue"     : ["notLinkableArrayTestDb"],
			"having" : 1,
        	"__inheritance__": "Comhon\\\\Logic\\\\Simple\\\\Having"
		}
	],
	"havingCollection" : [
		{
			"id"       : 1,
			"property" : "string",
			"operator" : "=",
			"value"    : 3,
        	"__inheritance__": "Comhon\\\\Logic\\\\Having\\\\Literal\\\\Count"
		}
	],
	"filter" : 1,
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "literal (with property 'notLinkableArrayTestDb') serialization incompatible with requested model serialization"
	|| !isset($result->error->code) || $result->error->code !== 702
) {
	throw new \Exception('bad ObjectService::getObjects return 12 '.json_encode($result));
}

/** *************** test complex request model without sql serialization *************** **/

$Json = '{
	"tree" : {
		"model"   : "Test\\\\Test",
		"id"      : 1
	},
    "__inheritance__": "Comhon\\\\Request\\\\Complex"
}';

$result = ObjectService::getObjects(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "intermediate or complex request not allowed for model 'Test\Test'"
	|| !isset($result->error->code) || $result->error->code !== 707
) {
	throw new \Exception('bad ObjectService::getObjects return 13 '.json_encode($result));
}

/** *************** test simple request model without id property *************** **/

$Json = '{
	"model"   : "Test\\\\TestNoId",
	"id"      : "an_id"
}';

$result = ObjectService::getObject(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "simple request not allowed for model 'Test\TestNoId'"
	|| !isset($result->error->code) || $result->error->code !== 707
) {
	throw new \Exception('bad ObjectService::getObjects return 15 '.json_encode($result));
}

/** *************** test simple request model without serialization *************** **/

$Json = '{
	"model"   : "Test\\\\TestNoSerialization",
	"id"      : 12
}';

$result = ObjectService::getObject(json_decode($Json));

if (!is_object($result) || !isset($result->success) || $result->success
	|| !isset($result->error) || !isset($result->error->message) || $result->error->message !== "request not allowed for model 'Test\TestNoSerialization'"
	|| !isset($result->error->code) || $result->error->code !== 707
) {
	throw new \Exception('bad ObjectService::getObjects return 16 '.json_encode($result));
}


$time_end = microtime(true);
var_dump('request failure test exec time '.($time_end - $time_start));