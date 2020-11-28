<?php

use Test\Comhon\Service\ObjectService;

$time_start = microtime(true);

/** ********** intermediate ********** **/
$data_ad = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Intermediate' . DIRECTORY_SEPARATOR . 'request.json';

$time_start_intermediaire = microtime(true);
$result = ObjectService::getObjects(json_decode(file_get_contents($data_ad)));
$time_intermediaire = microtime(true) - $time_start_intermediaire;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 1');
}
/** ********** complex ********** **/
$data_ad = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Advanced' . DIRECTORY_SEPARATOR . 'request.json';

$time_start_advanced = microtime(true);
$result = ObjectService::getObjects(json_decode(file_get_contents($data_ad)));
$time_advanced = microtime(true) - $time_start_advanced;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 2');
}

if ($time_advanced > $time_intermediaire) {
	var_dump('Warning!!! intermediate request is faster than advanced request');
}

/** *************************************************************************************************** **/

$Json = '{
	"root" : 1,
	"models" : [
		{
			"model"   : "Test\\\\Person",
			"id"      : 1
		},
		{
			"model" : "Test\\\\Home",
			"id"       : 2
		}
	],
	"simple_collection" : [
		{
			"id"       : 1,
			"node"     : 2,
			"property" : "id",
			"operator" : "=",
			"value"    : 1,
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Numeric\\\\Integer"
		}
	],
	"filter" : 1,
    "inheritance-": "Comhon\\\\Request\\\\Intermediate"
}';

$time_start_intermediaire = microtime(true);
$result = ObjectService::getObjects(json_decode($Json));
$time_intermediaire = microtime(true) - $time_start_intermediaire;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 3');
}

$Json = '{
	"tree" : {
		"model"   : "Test\\\\Person",
		"id"      : 1,
		"nodes" : [
			{
				"property" : "homes",
				"id"       : 2
			}
		]
	},
	"simple_collection" : [
		{
			"id"       : 1,
			"node"     : 2,
			"property" : "id",
			"operator" : "=",
			"value"    : 1,
        	"inheritance-": "Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Numeric\\\\Integer"
		}
	],
	"filter" : 1,
    "inheritance-": "Comhon\\\\Request\\\\Advanced"
}';

$time_start_advanced = microtime(true);
$result = ObjectService::getObjects(json_decode($Json));
$time_advanced = microtime(true) - $time_start_advanced;
if (!compareJson(json_encode($result), '{"success":true,"result":[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T20:04:05+01:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"}]}')) {
	throw new \Exception('bad result 4');
}

if ($time_advanced > $time_intermediaire) {
	var_dump('Warning!!! intermediate request is faster than advanced request');
}

$time_end = microtime(true);
var_dump('intermediate vs advanced request test exec time '.($time_end - $time_start));