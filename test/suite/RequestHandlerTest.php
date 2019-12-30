<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Test\Comhon\Mock\RequestHandlerMock;
use Comhon\Object\Collection\MainObjectCollection;

class RequestHandlerTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function setUp()
	{
		MainObjectCollection::getInstance()->reset();
	}
	
	/**
	 *
	 * @dataProvider requestNamespaceData
	 */
	public function testRequestNamespace($server, $requestHeaders, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestNamespaceData()
	{
		return [
			[ // no namespace
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
			],
			[ // empty namespace
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'namespace' => '',
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
					
			],
			[ // namespace
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Man/1'
				],
				[
					'namespace' => 'Test\Person',
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
					
			],
			[ // non-existent model name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Man/1'
				],
				[
					'namespace' => 'Test\Aaaa',
				],
				404,
				['Content-Type' => 'text/plain'],
				'resource model \'Test\Aaaa\Man\' doesn\'t exist'
			]
		];
	}
	
	/**
	 *
	 * @dataProvider requestUniqueResponseData
	 */
	public function testRequestUniqueResponse($server, $get, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, $get, []);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}

	public function requestUniqueResponseData()
	{
		return [
			[ // basic
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'__order__' => '[{"property":"id","type":"DESC"}]',
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}'
			],
			[ // inheritance
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson/1'
				],
				[
					'__order__' => '[{"property":"id","type":"DESC"}]',
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}'
			],
			[ // filter properties
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson/1'
				],
				[
					'__order__' => '[{"property":"id","type":"DESC"}]',
					'__properties__' => ['birthPlace', 'firstName']
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","birthPlace":2,"__inheritance__":"Test\\\\Person\\\\Man"}'
			]
		];
	}
	
	/**
	 *
	 * @dataProvider requestArrayResponseData
	 */
	public function testRequestArrayResponse($server, $get, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, $get, []);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestArrayResponseData()
	{
		return [
			[ // order id DESC
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__order__' => '[{"property":"id","type":"DESC"}]',
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":6,"firstName":"john","lastName":"lennon","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":null,"bestFriend":null,"father":1,"mother":2},{"id":5,"firstName":"Jean","lastName":"Henri","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":null,"bestFriend":7,"father":1,"mother":2},{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}]'
			],
			[ // order firstName ASC, range
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cWoman'
				],
				[
					'__order__' => '[{"property":"firstName","type":"ASC"}]',
					'__range__' => '3-5'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":2,"firstName":"Marie","lastName":"Smith","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":null,"bestFriend":{"id":5,"__inheritance__":"Test\\\\Person\\\\Man"},"father":null,"mother":null},{"id":11,"firstName":"Naelya","lastName":"Dupond","birthDate":null,"birthPlace":2,"bestFriend":null,"father":1,"mother":null},{"id":10,"firstName":"plop","lastName":"plop","birthDate":null,"birthPlace":null,"bestFriend":null,"father":5,"mother":7}]'
			],
			[ // inheritance, order firstName ASC, filter properties with inheritance
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'__order__' => '[{"property":"firstName","type":"ASC"}]',
					'__properties__' => ['birthPlace', 'firstName']
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","birthPlace":2,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":5,"firstName":"Jean","birthPlace":null,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":6,"firstName":"john","birthPlace":null,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":9,"firstName":"lala","birthPlace":null,"__inheritance__":"Test\\\\Person\\\\Woman"},{"id":7,"firstName":"lois","birthPlace":null,"__inheritance__":"Test\\\\Person\\\\Woman"},{"id":8,"firstName":"louise","birthPlace":null,"__inheritance__":"Test\\\\Person\\\\Woman"},{"id":2,"firstName":"Marie","birthPlace":null,"__inheritance__":"Test\\\\Person\\\\Woman"},{"id":11,"firstName":"Naelya","birthPlace":2,"__inheritance__":"Test\\\\Person\\\\Woman"},{"id":10,"firstName":"plop","birthPlace":null,"__inheritance__":"Test\\\\Person\\\\Woman"}]'
			],
			[ // unique filter string
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'__order__' => '[{"property":"firstName","type":"ASC"}]',
					'__properties__' => ['birthPlace', 'firstName'],
					'firstName' => 'Bernard'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","birthPlace":2,"__inheritance__":"Test\\\\Person\\\\Man"}]'
			],
			[ // unique filter array string
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'__order__' => '[{"property":"firstName","type":"ASC"}]',
					'__properties__' => ['firstName'],
					'firstName' => ['Bernard', 'john']
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","__inheritance__":"Test\\\\Person\\\\Man"},{"id":6,"firstName":"john","__inheritance__":"Test\\\\Person\\\\Man"}]'
			],
			[ // several filter conjunction
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'__order__' => '[{"property":"firstName","type":"ASC"}]',
					'__properties__' => ['firstName'],
					'firstName' => ['Bernard', 'john'],
					'lastName' => 'lennon'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":6,"firstName":"john","__inheritance__":"Test\\\\Person\\\\Man"}]'
			],
			[ // several filter disjunction
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'__order__' => '[{"property":"firstName","type":"ASC"}]',
					'__properties__' => ['firstName'],
					'__clause__' => 'disjunction',
					'firstName' => ['Bernard', 'john'],
					'lastName' => 'Henri'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","__inheritance__":"Test\\\\Person\\\\Man"},{"id":5,"firstName":"Jean","__inheritance__":"Test\\\\Person\\\\Man"},{"id":6,"firstName":"john","__inheritance__":"Test\\\\Person\\\\Man"}]'
			],
			[ // private id property must not appear in result (with filter properties)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId'
				],
				[
					'__order__' => '[{"property":"id","type":"ASC"}]',
					'__properties__' => ['name']
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"name":null}]'
			],
			[ // private id property must not appear in result (without filter properties)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId'
				],
				[
					'__order__' => '[{"property":"id","type":"ASC"}]',
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"name":null,"objectValues":null,"foreignObjectValue":null,"foreignObjectValues":null,"foreignTestPrivateId":null,"foreignTestPrivateIds":null}]'
			]
		];
	}
	
	/**
	 *
	 * @dataProvider requestCountData
	 */
	public function testRequestCount($server, $get, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, $get, []);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestCountData()
	{
		return [
			[ // order id DESC
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson%5cMan'
				],
				[],
				200,
				['Content-Type' => 'text/plain'],
				'3'
			],
			[ // inheritance
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson'
				],
				[],
				200,
				['Content-Type' => 'text/plain'],
				'9'
			],
			[ // invalid route
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				'invalid route'
			],
			[ // non-existent model name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Aaaaa'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				"resource model 'Aaaaa' doesn't exist"
			]
		];
	}
	
	/**
	 *
	 * @dataProvider malformedGetRequestData
	 */
	public function testMalformedGetRequest($server, $get, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, $get, []);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function malformedGetRequestData()
	{
		return [
			[ // non-existent model name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cAaaa%5cMan/1'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				'resource model \'Test\Aaaa\Man\' doesn\'t exist'
			],
			[ // not handled route
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/apii/Test%5cPerson%5cMan/1'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				'not handled route'
			],
			[ // invalid route
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1/aaa'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				'invalid route'
			],
			[ // invalid route
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson%5cMan/1'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				'invalid route'
			],
			[ // not defined property in properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'__properties__' => ['does_not_exist']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"Undefined property \'does_not_exist\' for model \'Test\\\\Person\\\\Man\'"}'
			],
			[ // malformed properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'__properties__' => 'not_array'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":203,"message":"value of property \'__properties__\' must be a array, string \'not_array\' given"}'
			],
			[ // malformed properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'__properties__' => [1]
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":203,"message":"Something goes wrong on \'.0\' value : \nvalue must be a string, integer \'1\' given"}'
			],
			[ // private properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/1'
				],
				[
					'__properties__' => ['string']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":108,"message":"cannot use private property \'string\' in public context"}'
			],
			[ // malformed clause
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__clause__' => ['hehe']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":203,"message":"Something goes wrong on \'.__clause__\' value : \nvalue must be a string, array given"}'
			],
			[ // malformed clause
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__clause__' => 'hehe'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":201,"message":"Something goes wrong on \'.__clause__\' value : \nhehe is not in enumeration [\"disjunction\",\"conjunction\"]"}'
			],
			[ // malformed order
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__order__' => 'aaaa'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":203,"message":"value of property \'__order__\' must be a json, string \'aaaa\' given"}'
			],
			[ // malformed order
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__order__' => '["aaa"]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":203,"message":"Something goes wrong on \'.__order__.0\' value : \nvalue must be a Comhon\\\\Object\\\\UniqueObject(Comhon\\\\Request\\\\Order), string \'aaa\' given"}'
			],
			[ // malformed order
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__order__' => '[{"hehe":"hehe"}]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":205,"message":"Something goes wrong on \'.__order__.0\' value : \nmissing required value \'property\' on comhon object with model \'Comhon\\\\Request\\\\Order\'"}'
			],
				[ // malformed order undefined property name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__order__' => '[{"property":"undefined_property"}]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"Undefined property \'undefined_property\' for model \'Test\\\\Person\\\\Man\'"}'
			],
			[ // range without order
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__range__' => '1-2'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":109,"message":"property \'__range__\' can\'t be set without property \'__order__\'"}'
			],
			[ // malformed range 1
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__range__' => 'my_range',
					'__order__' => '[{"property":"id"}]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":201,"message":"Something goes wrong on \'.__range__\' value : \nmy_range doesn\'t satisfy range format \'x-y\' where x and y are integer and x<=y"}'
			],
			[ // malformed range 2
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__range__' => '9-2',
					'__order__' => '[{"property":"id"}]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":201,"message":"Something goes wrong on \'.__range__\' value : \n9-2 doesn\'t satisfy range format \'x-y\' where x and y are integer and x<=y"}'
			],
			[ // request filter with undefined property
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'undefined_property' => 'value'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"Undefined property \'undefined_property\' for model \'Test\\\\Person\\\\Man\'"}'
			],
			[ // request filter with malformed property 1
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				[
					'boolean' => 'value'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":107,"message":"Cannot cast value \'value\' for property \'boolean\', value should belong to enumeration [\"0\",\"1\"]"}'
			],
			[ // request filter with malformed property 2
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				[
					'boolean' => ['0']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":709,"message":"literal not allowed on property \'boolean\' of model \'Test\\\\TestDb\'. must be one of [Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Boolean, Comhon\\\\Logic\\\\Simple\\\\Literal\\\\Null]"}'
			],
			[ // request filter with malformed property 3
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'bestFriend' => 'aaa'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":107,"message":"Cannot cast value \'aaa\' for property \'bestFriend\', value should be integer"}'
			],
			[ // request filter with malformed property 4
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'bestFriend' => ['0', 'aaaa']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":107,"message":"Cannot cast value \'aaaa\' for property \'bestFriend\', value should be integer"}'
			],
			[ // request filter with private property
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				[
					'string' => 'aaaa'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":108,"message":"cannot use private property \'string\' in public context"}'
			],
			[ // request unique id malformed
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/bbb'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":107,"message":"Cannot cast value \'bbb\' for property \'id\', value should be integer"}'
			],
			[ // request unique id resource without id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cBasic%5cNoId/bbb'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":110,"message":"model \'Test\\\\Basic\\\\NoId\' doesn\'t have id property"}'
			],
			[ // request unique id resource with private id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId/1'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":108,"message":"cannot use private id property \'id\' in public context"}'
			],
			[ // TestNoId is linked to person serialization but doesn't have same properties, so select name column fail
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId'
				],
				[
					'__properties__' => ['name']
				],
				500,
				[],
				null
			]
		];
	}
	
}
