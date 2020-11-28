<?php

use Test\Comhon\Service\ObjectService;

$time_start = microtime(true);

$Json = '{
	"tree" : {
		"model"   : "Test\\\\MainTestDb",
		"id"      : 1
	},
	"simple_collection" : [
		{
			"id"       : 0,
			"elements" : [1,2],
            "type": "conjunction",
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Clause"
		},
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "name",
			"operator" : "IN",
			"values"    : ["azeaze", "Bernard", null],
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\String"
		},
		{
			"id"       : 2,
			"node"     : 1,
			"queue"     : ["childrenTestDb", "childrenTestDb"],
			"having" : 1,
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Having"
		}
	],
	"having_collection" : [
		{
			"id"       : 1,
			"operator" : "=",
			"value"    : 2,
            "inheritance-": "Comhon\\\\Logic\\\\Having\\\\Literal\\\\Count"
		}
	],
	"filter" : 0,
    "inheritance-": "Comhon\\\\Request\\\\Advanced"
}';

// SELECT main_test.* 
// FROM   main_test 
//        LEFT JOIN (SELECT t_16.main_test_id 
//                   FROM   test AS t_16 
//                          INNER JOIN child_test AS t_17 
//                                  ON ( t_16.id_1 = t_17.parent_id_1 
//                                       AND t_16.id_2 = t_17.parent_id_2 ) 
//                   GROUP  BY t_16.main_test_id 
//                   HAVING ( Count(*) = 2 )) AS t_18 
//               ON main_test.id = t_18.main_test_id 
// WHERE  ( ( main_test.NAME IN ( "azeaze", "bernard" ) 
//             OR main_test.NAME IS NULL ) 
//          AND t_18.main_test_id IS NOT NULL ) 
// GROUP  BY main_test.id 

$result = ObjectService::getObjects(json_decode($Json));

$expected = '{"success":true,"result":[{"name":"azeaze","obj":null,"id":1}]}';

if (!compareJson(json_encode($result), $expected)) {
	throw new \Exception('bad result 1');
}

$Json = '{
	"root": 1,
	"models" : [
		{
			"model"   : "Test\\\\MainTestDb",
			"id"      : 1
		},
		{
			"model"   : "Test\\\\TestDb",
			"id"      : 2
		}
	],
	"simple_collection" : [
		{
			"id"       : 0,
			"elements" : [1,2],
            "type": "conjunction",
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Clause"
		},
		{
			"id"       : 1,
			"node"     : 1,
			"property" : "name",
			"operator" : "IN",
			"values"    : ["azeaze", "Bernard", null],
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\String"
		},
		{
			"id"       : 2,
			"node"     : 2,
			"queue"     : ["childrenTestDb"],
			"having" : 1,
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Having"
		}
	],
	"having_collection" : [
		{
			"id"       : 1,
			"operator" : "=",
			"value"    : 2,
            "inheritance-": "Comhon\\\\Logic\\\\Having\\\\Literal\\\\Count"
		}
	],
	"filter" : 0,
    "inheritance-": "Comhon\\\\Request\\\\Intermediate"
}';

// SELECT main_test.* 
// FROM   main_test 
//        LEFT JOIN test 
//               ON main_test.id = test.main_test_id 
//        LEFT JOIN (SELECT t_19.parent_id_1, 
//                          t_19.parent_id_2 
//                   FROM   child_test AS t_19 
//                   GROUP  BY t_19.parent_id_1, 
//                             t_19.parent_id_2 
//                   HAVING ( Count(*) = 2 )) AS t_20 
//               ON ( test.id_1 = t_20.parent_id_1 
//                    AND test.id_2 = t_20.parent_id_2 ) 
// WHERE  ( ( main_test.NAME IN ( "azeaze", "bernard" ) 
//             OR main_test.NAME IS NULL ) 
//          AND ( t_20.parent_id_1 IS NOT NULL 
//                AND t_20.parent_id_2 IS NOT NULL ) ) 
// GROUP  BY main_test.id 


$result = ObjectService::getObjects(json_decode($Json));
if (!compareJson(json_encode($result), $expected)) {
	throw new \Exception('bad result 2');
}

$Json = '{
	"root": 1,
	"models" : [
		{
			"model"   : "Test\\\\ChildTestDb",
			"id"      : 1
		},
		{
			"model"   : "Test\\\\TestDb",
			"id"      : 2
		}
	],
	"order" : [{"property":"id", "type":"ASC"}],
	"simple_collection" : [
		{
			"id"       : 1,
			"node"     : 2,
			"property" : "string",
			"operator" : "IN",
			"values"    : ["nnnn", "bbbb", null],
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Set\\\\String"
		}
	],
	"filter" : 1,
    "inheritance-": "Comhon\\\\Request\\\\Intermediate"
}';

// SELECT child_test.* 
// FROM   child_test 
//        LEFT JOIN test 
//               ON ( child_test.parent_id_1 = test.id_1 
//                    AND child_test.parent_id_2 = test.id_2 ) 
// WHERE  (( test.string IN ( "nnnn", "bbbb" ) 
//            OR test.string IS NULL )) 
// GROUP  BY child_test.id 

$result = ObjectService::getObjects(json_decode($Json), true);
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]}')) {
	throw new \Exception('bad result 3');
}

$time_end = microtime(true);
var_dump('multiple id request test exec time '.($time_end - $time_start));