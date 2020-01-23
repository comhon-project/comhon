<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Test\Comhon\Mock\RequestHandlerMock;
use Comhon\Object\Collection\MainObjectCollection;

class RequestHandlerGetHeadTest extends TestCase
{
	private static $data_ad = __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'Request' . DIRECTORY_SEPARATOR;
	
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
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
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
	 * @dataProvider requestAcceptHeaderData
	 */
	public function testRequestAcceptHeader($server, $requestHeaders, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
	}
	
	public function requestAcceptHeaderData()
	{
		return [
			[ // empty Accept
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				['Accept' => ''],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
			],
			[ // Accept xml
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'Accept' => 'application/xml',
				],
				200,
				['Content-Type' => 'application/xml'],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="1" firstName="Bernard" lastName="Dupond" birthDate="2016-11-13T19:04:05+00:00"><birthPlace>2</birthPlace><bestFriend xsi:nil="true"/><father xsi:nil="true"/><mother xsi:nil="true"/></root>',
			],
			[ // Accept several content type + quality value 1
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
						'Accept' => 'application/json;q=0.55, application/xml;q=0.6'
				],
				200,
				['Content-Type' => 'application/xml'],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Man id="1" firstName="Bernard" lastName="Dupond" birthDate="2016-11-13T19:04:05+00:00"><birthPlace>2</birthPlace><bestFriend xsi:nil="true"/><father xsi:nil="true"/><mother xsi:nil="true"/></Man><Man id="5" firstName="Jean" lastName="Henri" birthDate="2016-11-13T19:04:05+00:00"><birthPlace xsi:nil="true"/><bestFriend>7</bestFriend><father>1</father><mother>2</mother></Man><Man id="6" firstName="john" lastName="lennon" birthDate="2016-11-13T19:04:05+00:00"><birthPlace xsi:nil="true"/><bestFriend xsi:nil="true"/><father>1</father><mother>2</mother></Man></root>',
			],
			[ // Accept several content type + quality value 2
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'Accept' => 'application/json, application/xml;q=0.6',
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}'
			],
			[ // non-existent content type
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'Accept' => 'aaaa',
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}'
			]
		];
	}
	
	/**
	 *
	 * @dataProvider requestUniqueResponseData
	 */
	public function testRequestUniqueResponse($server, $get, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server, $get, []);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api', $server, $get, []);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
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
				[],
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
					'__properties__' => ['birthPlace', 'firstName']
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","birthPlace":2,"__inheritance__":"Test\\\\Person\\\\Man"}'
			],
			[ // multiple id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,"23"]'
				],
				[],
				200,
				['Content-Type' => 'application/json'],
				'{"defaultValue":"default","id1":1,"id2":"23","date":"2016-05-01T12:53:54+00:00","timestamp":"2016-10-16T19:50:19+00:00","object":null,"objectWithId":null,"integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}'
			]
		];
	}
	
	/**
	 *
	 * @dataProvider requestArrayResponseData
	 */
	public function testRequestArrayResponse($server, $get, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api/', $server, $get, []);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, $get, []);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
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
		$response = RequestHandlerMock::handle('/index.php/api', $server, $get, []);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('/index.php/api', $server, $get, []);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
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
		$response = RequestHandlerMock::handle('/index.php/api/', $server, $get, []);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('/index.php/api/', $server, $get, []);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
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
				'{"code":103,"message":"Undefined property \'does_not_exist\' for model \'Test\\\\Person\\\\Man\'"}'
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
				'{"code":202,"message":"value of property \'__properties__\' must be a array, string \'not_array\' given"}'
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
				'{"code":202,"message":"Something goes wrong on \'.0\' value : \nvalue must be a string, integer \'1\' given"}'
			],
			[ // private properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,"23"]'
				],
				[
					'__properties__' => ['string']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"cannot use private property \'string\' in public context"}'
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
				'{"code":202,"message":"Something goes wrong on \'.__clause__\' value : \nvalue must be a string, array given"}'
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
				'{"code":200,"message":"Something goes wrong on \'.__clause__\' value : \nhehe is not in enumeration [\"disjunction\",\"conjunction\"]"}'
			],
			[ // malformed order #10
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'__order__' => 'aaaa'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"value of property \'__order__\' must be a json, string \'aaaa\' given"}'
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
				'{"code":202,"message":"Something goes wrong on \'.__order__.0\' value : \nvalue must be a array, string \'aaa\' given"}'
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
				'{"code":204,"message":"Something goes wrong on \'.__order__.0\' value : \nmissing required value \'property\' on comhon object with model \'Comhon\\\\Request\\\\Order\'"}'
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
				'{"code":103,"message":"Undefined property \'undefined_property\' for model \'Test\\\\Person\\\\Man\'"}'
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
				'{"code":208,"message":"property value \'__range__\' can\'t be set without property value \'__order__\'"}'
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
				'{"code":200,"message":"Something goes wrong on \'.__range__\' value : \nmy_range doesn\'t satisfy range format \'x-y\' where x and y are integer and x<=y"}'
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
				'{"code":200,"message":"Something goes wrong on \'.__range__\' value : \n9-2 doesn\'t satisfy range format \'x-y\' where x and y are integer and x<=y"}'
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
				'{"code":103,"message":"Undefined property \'undefined_property\' for model \'Test\\\\Person\\\\Man\'"}'
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
				'{"code":104,"message":"Cannot cast value \'value\' for property \'boolean\', value should belong to enumeration [\"0\",\"1\"]"}'
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
				'{"code":104,"message":"Cannot cast value \'aaa\' for property \'bestFriend\', value should be integer"}'
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
				'{"code":104,"message":"Cannot cast value \'aaaa\' for property \'bestFriend\', value should be integer"}'
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
				'{"code":105,"message":"cannot use private property \'string\' in public context"}'
			],
			[ // request unique id malformed
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/bbb'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":104,"message":"Cannot cast value \'bbb\' for property \'id\', value should be integer"}'
			],
			[ // request unique id resource without serialization
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cBasic%5cNoId/bbb'
				],
				[],
				405,
				['Content-Type' => 'text/plain'],
				'resource model \'Test\Basic\NoId\' is not requestable'
			],
			[ // request unique id resource without id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId/bbb'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":106,"message":"model \'Test\\\\TestNoId\' doesn\'t have id property"}'
			],
			[ // request unique id resource with private id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId/1'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"cannot use private id property \'id\' in public context"}'
			],
			[ // TestNoId has 'value' property but sql table doesn't have column 'value', so it fail
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId'
				],
				[
					'__properties__' => ['value']
				],
				500,
				[],
				null
			],
			[ // filter properties
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,"23]'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1,\\"23]\'"}'
			],
			[ // filter properties
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1]'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1]\'"}'
			],
			[ // filter properties
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,null]'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1,null]\'"}'
			]
		];
	}
	
	/**
	 *
	 * @dataProvider requestWithBodyData
	 */
	public function testRequestWithBody($server, $bodyFile_rf, $requestHeaders, $responseCode, $responseHeaders, $responseContent)
	{
		$body = file_get_contents(self::$data_ad . $bodyFile_rf);
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
	}
	
	public function requestWithBodyData()
	{
		return [
			[ // intermediate request
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				'intermediate.json',
				['Content-Type' => 'application/json'],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"},{"id":11,"firstName":"Naelya","lastName":"Dupond","birthDate":null,"birthPlace":2,"bestFriend":null,"father":1,"mother":null,"__inheritance__":"Test\\\\Person\\\\Woman"}]'
			],
			[ // complex request
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				'complex.json',
				['Content-Type' => 'application/json'],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"__inheritance__":"Test\\\\Person\\\\Man"}]'
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestCountWithBodyData
	 */
	public function testRequestCountWithBody($server, $bodyFile_rf, $requestHeaders, $responseCode, $responseHeaders, $responseContent)
	{
		$body = file_get_contents(self::$data_ad . $bodyFile_rf);
		$response = RequestHandlerMock::handle('/index.php/api', $server, [], $requestHeaders, $body);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('/index.php/api', $server, [], $requestHeaders, $body);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
	}
	
	public function requestCountWithBodyData()
	{
		return [
			[ // intermediate request
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson'
				],
				'intermediate.json',
				['Content-Type' => 'application/json'],
				200,
					['Content-Type' => 'text/plain'],
				2
			],
			[ // complex request
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson'
				],
				'complex.json',
				['Content-Type' => 'application/json'],
				200,
				['Content-Type' => 'text/plain'],
				1
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestWithBodyFailureData
	 */
	public function testRequestWithBodyFailure($server, $body, $requestHeaders, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
	}
	
	public function requestWithBodyFailureData()
	{
		return [
			[ // route model name different than request model name (complex request)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				'{"tree": {"id": 1,"model": "Test\\\\Person"},"__inheritance__": "Comhon\\\\Request\\\\Complex"}',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'request model name is different than route model : Test\Person != Test\Person\Man'
			],
			[ // route model name different than request model name (intermediate request)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				'{"models": [{"id": 1,"model": "Test\\\\Person"}],"root":1,"__inheritance__": "Comhon\\\\Request\\\\Intermediate"}',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'request model name is different than route model : Test\Person != Test\TestDb'
			],
			[ // incalid body
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				'not-json',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'invalid body'
			],
				[ // error in request model
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				'{"tree": {"id": 1,"model": 1},"__inheritance__": "Comhon\\\\Request\\\\Complex"}',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"Something goes wrong on \'.tree.model\' value : \nvalue must be a string, integer \'1\' given"}'
			],
		];
	}
	
	
	/**
	 *
	 * @dataProvider requestCountWithBodyFailureData
	 */
	public function testRequestCountWithBodyFailure($server, $body, $requestHeaders, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendGet = $response->getSend();
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendHead = $response->getSend();
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
	}
	
	public function requestCountWithBodyFailureData()
	{
		return [
			[ // route model name different than request model name (complex request)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson%5cMan'
				],
				'{"tree": {"id": 1,"model": "Test\\\\Person"},"__inheritance__": "Comhon\\\\Request\\\\Complex"}',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'request model name is different than route model : Test\Person != Test\Person\Man'
			],
			[ // route model name different than request model name (intermediate request)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				'{"models": [{"id": 1,"model": "Test\\\\Person"}],"root":1,"__inheritance__": "Comhon\\\\Request\\\\Intermediate"}',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'request model name is different than route model : Test\Person != Test\TestDb'
			],
			[ // invalid body
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson'
				],
				'not-json',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'invalid body'
			],
			[ // error in request model
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson'
				],
				'{"tree": {"id": 1,"model": 1},"__inheritance__": "Comhon\\\\Request\\\\Complex"}',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"Something goes wrong on \'.tree.model\' value : \nvalue must be a string, integer \'1\' given"}'
			],
	];
	}
	
}
