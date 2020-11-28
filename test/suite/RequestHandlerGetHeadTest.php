<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Test\Comhon\Mock\RequestHandlerMock;
use Comhon\Object\Collection\MainObjectCollection;
use Test\Comhon\Utils\RequestTestTrait;
use Test\Comhon\Utils\ApiModelNameHandler;

class RequestHandlerGetHeadTest extends TestCase
{
	use RequestTestTrait;
	
	private static $apiModelNames = [
		['api_model_name' => 'man', 'comhon_model_name' => 'Test\Person\Man', 'extends' => ['Test\Person']],
		['api_model_name' => 'woman', 'comhon_model_name' => 'Test\Person\Woman'],
		['api_model_name' => 'house', 'comhon_model_name' => 'Test\Dont\Exist'],
	];
	
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
	 * @dataProvider apiModelNamesData
	 */
	public function testApiModelNames($server, $apiModelNameHandler, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], [], '', $apiModelNameHandler);
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], [], '', $apiModelNameHandler);
		$sendHead = $this->responseToArray($response);
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals($responseHeaders, $sendHead[1]);
		}
	}
	
	public function apiModelNamesData()
	{
		return [
			[ // without api model name handler
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				null,
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
			],
			[ // with api model name handler but doesn't use api model name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				new ApiModelNameHandler(false, self::$apiModelNames),
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
			],
			[ // existing api model name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/man/1'
				],
				new ApiModelNameHandler(true, self::$apiModelNames),
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
					
			],
			[ // existing api model name, not lower case
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/MaN/1'
				],
				new ApiModelNameHandler(true, self::$apiModelNames),
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
					
			],
			[ // NOT existing api model name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/person'
				],
				new ApiModelNameHandler(true, self::$apiModelNames),
				404,
				['Content-Type' => 'text/plain'],
				"resource api model name 'person' doesn't exist"
			],
			[ // NOT existing comhon model name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/house'
				],
				new ApiModelNameHandler(true, self::$apiModelNames),
				404,
				['Content-Type' => 'text/plain'],
				"resource model 'Test\Dont\Exist' doesn't exist"
			]
		];
	}
	
	/**
	 *
	 * @dataProvider getModelNamesData
	 */
	public function testGetModelNames($server, $requestHeaders, $apiModelNameHandler, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders, '', $apiModelNameHandler);
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders, '', $apiModelNameHandler);
		$sendHead = $this->responseToArray($response);
		
		$this->assertEquals($responseCode, $sendHead[0]);
		if ($responseCode == 200) {
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
		}
	}
	
	public function getModelNamesData()
	{
		return [
			[ // json
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/models'
				],
				['Accept' => 'application/json'],
				new ApiModelNameHandler(true, self::$apiModelNames),
				200,
				['Content-Type' => 'application/json'],
				'[{"api_model_name":"man","comhon_model_name":"Test\\\\Person\\\\Man","extends":["Test\\\\Person"]},{"api_model_name":"woman","comhon_model_name":"Test\\\\Person\\\\Woman"},{"api_model_name":"house","comhon_model_name":"Test\\\\Dont\\\\Exist"}]',
			],
			[ // xml
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/models'
				],
				['Accept' => 'application/xml'],
				new ApiModelNameHandler(true, self::$apiModelNames),
				200,
				['Content-Type' => 'application/xml'],
				'<root><model comhon_model_name="Test\Person\Man" api_model_name="man"><extends><model>Test\Person</model></extends></model><model comhon_model_name="Test\Person\Woman" api_model_name="woman"/><model comhon_model_name="Test\Dont\Exist" api_model_name="house"/></root>',
			],
			[ // route not handled
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/models'
				],
				['Accept' => 'application/xml'],
				null,
				404,
				[],
				""
			],
			[ // route not handled
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/models'
				],
				['Accept' => 'application/xml'],
				new ApiModelNameHandler(false, null),
				404,
				[],
				""
			]
		];
	}
	
	/**
	 *
	 * @dataProvider patternsData
	 */
	public function testPatterns($pattern, $regex)
	{
		$requestHeaders = ['Content-Type' => 'application/json'];
		$server = [
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => "/index.php/api/pattern/$pattern"
		];
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders);
		$sendGet = $this->responseToArray($response);
		
		if ($regex) {
			$this->assertEquals(200, $sendGet[0]);
			$this->assertEquals(['Content-Type' => 'text/plain'], $sendGet[1]);
		} else {
			$this->assertEquals(404, $sendGet[0]);
		}
		$this->assertEquals($regex, $sendGet[2]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders);
		$sendHead = $this->responseToArray($response);
		
		if ($regex) {
			$this->assertEquals(200, $sendHead[0]);
			$this->assertEmpty($sendHead[2]);
			$this->assertArrayHasKey('Content-Length', $sendHead[1]);
			$this->assertEquals(strlen($sendGet[2]), $sendHead[1]['Content-Length']);
			unset($sendHead[1]['Content-Length']);
			$this->assertEquals([], $sendHead[1]);
		} else {
			$this->assertEquals(404, $sendGet[0]);
		}
	}
	
	public function patternsData()
	{
		return [
			[
			'email', 
			'/^\S+@\S+\.[a-z]{2,6}$/'
			],
			[
			'foo',
			null
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], $requestHeaders);
		$sendHead = $this->responseToArray($response);
		
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api', $server, $get, []);
		$sendHead = $this->responseToArray($response);
		
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
					'-order' => '[{"property":"id","type":"DESC"}]',
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}'
			],
			[ // inheritance success
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson/1'
				],
				[],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"}'
			],
			[ // inheritance not found (sqlTable), id exists but a woman is requested and given id corespond to man object
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cWoman/1'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				"resource 'Test\Person\Woman' with id '1' not found"
			],
			[ // inheritance not found (file), id exists but a WomanXmlExtended is requested and given id corespond to WomanXml object
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cWomanXmlExtended/2'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				"resource 'Test\Person\WomanXmlExtended' with id '2' not found"
			],
			[ // filter properties
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson/1'
				],
				[
					'-properties' => ['birthPlace', 'firstName']
				],
				200,
				['Content-Type' => 'application/json'],
				'{"id":1,"firstName":"Bernard","birthPlace":2,"inheritance-":"Test\\\\Person\\\\Man"}'
			],
			[ // multiple id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,"23"]'
				],
				[],
				200,
				['Content-Type' => 'application/json'],
				'{"id1":1,"id2":"23","defaultValue":"default","date":"2016-05-01T12:53:54+00:00","timestamp":"2016-10-16T19:50:19+00:00","object":null,"objectWithId":null,"integer":0,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":false,"boolean2":true}'
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, $get, []);
		$sendHead = $this->responseToArray($response);
		
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
					'-order' => '[{"property":"id","type":"DESC"}]',
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
					'-order' => '[{"property":"firstName","type":"ASC"}]',
					'-range' => '3-5'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":2,"firstName":"Marie","lastName":"Smith","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":null,"bestFriend":{"id":5,"inheritance-":"Test\\\\Person\\\\Man"},"father":null,"mother":null},{"id":11,"firstName":"Naelya","lastName":"Dupond","birthDate":null,"birthPlace":2,"bestFriend":null,"father":1,"mother":null},{"id":10,"firstName":"plop","lastName":"plop","birthDate":null,"birthPlace":null,"bestFriend":null,"father":5,"mother":7}]'
			],
			[ // inheritance, order firstName ASC, filter properties with inheritance
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'-order' => '[{"property":"firstName","type":"ASC"}]',
					'-properties' => ['birthPlace', 'firstName']
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","birthPlace":2,"inheritance-":"Test\\\\Person\\\\Man"},{"id":5,"firstName":"Jean","birthPlace":null,"inheritance-":"Test\\\\Person\\\\Man"},{"id":6,"firstName":"john","birthPlace":null,"inheritance-":"Test\\\\Person\\\\Man"},{"id":9,"firstName":"lala","birthPlace":null,"inheritance-":"Test\\\\Person\\\\Woman"},{"id":7,"firstName":"lois","birthPlace":null,"inheritance-":"Test\\\\Person\\\\Woman"},{"id":8,"firstName":"louise","birthPlace":null,"inheritance-":"Test\\\\Person\\\\Woman"},{"id":2,"firstName":"Marie","birthPlace":null,"inheritance-":"Test\\\\Person\\\\Woman"},{"id":11,"firstName":"Naelya","birthPlace":2,"inheritance-":"Test\\\\Person\\\\Woman"},{"id":10,"firstName":"plop","birthPlace":null,"inheritance-":"Test\\\\Person\\\\Woman"}]'
			],
			[ // unique filter string
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'-order' => '[{"property":"firstName","type":"ASC"}]',
					'-properties' => ['birthPlace', 'firstName'],
					'firstName' => 'Bernard'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","birthPlace":2,"inheritance-":"Test\\\\Person\\\\Man"}]'
			],
			[ // empty result
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'-order' => '[{"property":"firstName","type":"ASC"}]',
					'-properties' => ['birthPlace', 'firstName'],
					'firstName' => 'no name'
				],
				200,
				['Content-Type' => 'application/json'],
				'[]'
			],
			[ // unique filter array string
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'-order' => '[{"property":"firstName","type":"ASC"}]',
					'-properties' => ['firstName'],
					'firstName' => ['Bernard', 'john']
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","inheritance-":"Test\\\\Person\\\\Man"},{"id":6,"firstName":"john","inheritance-":"Test\\\\Person\\\\Man"}]'
			],
			[ // several filter conjunction
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'-order' => '[{"property":"firstName","type":"ASC"}]',
					'-properties' => ['firstName'],
					'firstName' => ['Bernard', 'john'],
					'lastName' => 'lennon'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":6,"firstName":"john","inheritance-":"Test\\\\Person\\\\Man"}]'
			],
			[ // several filter disjunction
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'-order' => '[{"property":"firstName","type":"ASC"}]',
					'-properties' => ['firstName'],
					'-clause' => 'disjunction',
					'firstName' => ['Bernard', 'john'],
					'lastName' => 'Henri'
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","inheritance-":"Test\\\\Person\\\\Man"},{"id":5,"firstName":"Jean","inheritance-":"Test\\\\Person\\\\Man"},{"id":6,"firstName":"john","inheritance-":"Test\\\\Person\\\\Man"}]'
			],
			[ // private id property must not appear in result (with filter properties)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId'
				],
				[
					'-order' => '[{"property":"id","type":"ASC"}]',
					'-range' => '0-0',
					'-properties' => ['name']
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"name":"a"}]'
			],
			[ // private id property must not appear in result (without filter properties)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId'
				],
				[
					'-order' => '[{"property":"id","type":"ASC"}]',
					'-range' => '0-0',
				],
				200,
				['Content-Type' => 'application/json'],
				'[{"name":"a","objectValues":null,"foreignObjectValue":null,"foreignObjectValues":null,"foreignTestPrivateId":null,"foreignTestPrivateIds":null}]'
			],
			[ // test requestable properties (defined in option files)
					[
							'REQUEST_METHOD' => 'GET',
							'REQUEST_URI' => '/index.php/api/Test%5cBody%5cMan'
					],
					[
							'hairColor' => 'black',
							'height' => 1.8,
							'-properties' => ['id', 'height', 'physicalAppearance']
					],
					200,
					['Content-Type' => 'application/json'],
					'[{"id":1,"height":1.8,"physicalAppearance":"muscular"},{"id":2,"height":1.8,"physicalAppearance":"slim"}]'
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('/index.php/api', $server, $get, []);
		$sendHead = $this->responseToArray($response);
		
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
			[ // count 0
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/count/Test%5cPerson'
				],
				['firstName' => 'no name'],
				200,
				['Content-Type' => 'text/plain'],
				'0'
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('/index.php/api/', $server, $get, []);
		$sendHead = $this->responseToArray($response);
		
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
					'-properties' => ['does_not_exist']
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
					'-properties' => 'not_array'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"value of property \'-properties\' must be a array, string \'not_array\' given"}'
			],
			[ // malformed properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[
					'-properties' => [1]
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":103,"message":"Undefined property \'1\' for model \'Test\\\\Person\\\\Man\'"}'
			],
			[ // private properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,"23"]'
				],
				[
					'-properties' => ['string']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"cannot use private property \'string\' on model \'Test\\\\TestDb\' in public context"}'
			],
			[ // malformed clause
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-clause' => ['hehe']
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"Something goes wrong on \'.-clause\' value : \nvalue must be a string, array given"}'
			],
			[ // malformed clause
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-clause' => 'hehe'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":200,"message":"Something goes wrong on \'.-clause\' value : \nhehe is not in enumeration [\"disjunction\",\"conjunction\"]"}'
			],
			[ // ##### 10 ##### malformed order 
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-order' => 'aaaa'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"value of property \'-order\' must be a json, string \'aaaa\' given"}'
			],
			[ // malformed order
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-order' => '["aaa"]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"Something goes wrong on \'.-order.0\' value : \nvalue must be a array, string \'aaa\' given"}'
			],
			[ // malformed order
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-order' => '[{"hehe":"hehe"}]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":204,"message":"Something goes wrong on \'.-order.0\' value : \nmissing required value \'property\' on comhon object with model \'Comhon\\\\Request\\\\Order\'"}'
			],
				[ // malformed order undefined property name
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-order' => '[{"property":"undefined_property"}]'
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
					'-range' => '1-2'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":208,"message":"property value \'-range\' can\'t be set without property value \'-order\'"}'
			],
			[ // malformed range 1
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-range' => 'my_range',
					'-order' => '[{"property":"id"}]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":200,"message":"Something goes wrong on \'.-range\' value : \nmy_range doesn\'t satisfy range format \'x-y\' where x and y are integer and x<=y"}'
			],
			[ // malformed range 2
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'-range' => '9-2',
					'-order' => '[{"property":"id"}]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":200,"message":"Something goes wrong on \'.-range\' value : \n9-2 doesn\'t satisfy range format \'x-y\' where x and y are integer and x<=y"}'
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
			[ // ##### 20 ##### request filter with malformed property 3 
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
				[ // request filter with not requestable property (defined in option files)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				[
					'string' => 'aaaa'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"cannot use private property \'string\' on model \'Test\\\\TestDb\' in public context"}'
			],
			[ // request filter with private property
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cBody%5cMan'
				],
				[
					'eyesColor' => 'blue'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":107,"message":"cannot request  property \'eyesColor\' on model \'Test\\\\Body\\\\Man\' in public context"}'
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
				404,
				['Content-Type' => 'text/plain'],
				'resource model \'Test\Basic\NoId\' is not requestable'
			],
			[ // ##### 25 ##### request unique id resource without id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId/bbb'
				],
				[],
				404,
				['Content-Type' => 'application/json'],
				'{"code":106,"message":"invalid route, model \'Test\\\\TestNoId\' doesn\'t have id property"}'
			],
			[ // request unique id resource with private id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId/1'
				],
				[],
				404,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"invalid route, cannot use private id property \'id\' on model \'Test\\\\TestPrivateId\' in public context"}'
			],
			[ // TestNoId has 'value' property but sql table doesn't have column 'value', so it fail
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId'
				],
				[
					'-properties' => ['value']
				],
				500,
				[],
				null
			],
			[ // invalid composite id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,"23]'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1,\\"23]\'"}'
			],
			[ // invalid composite id
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1]'
				],
				[],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1]\'"}'
			],
			[ // invalid composite id
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendHead = $this->responseToArray($response);
		
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
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/request'
				],
				'intermediate.json',
				['Content-Type' => 'application/json'],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"},{"id":11,"firstName":"Naelya","lastName":"Dupond","birthDate":null,"birthPlace":2,"bestFriend":null,"father":1,"mother":null,"inheritance-":"Test\\\\Person\\\\Woman"}]'
			],
			[ // complex request
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/request'
				],
				'complex.json',
				['Content-Type' => 'application/json'],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"}]'
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('/index.php/api', $server, [], $requestHeaders, $body);
		$sendHead = $this->responseToArray($response);
		
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
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/count'
				],
				'intermediate.json',
				['Content-Type' => 'application/json'],
				200,
					['Content-Type' => 'text/plain'],
				2
			],
			[ // complex request
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/count'
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendHead = $this->responseToArray($response);
		
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
			[ // invalid body
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/request'
				],
				'not-json',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'invalid body'
			],
			[ // error in request model
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/request'
				],
				'{"tree": {"id": 1,"model": 1},"inheritance-": "Comhon\\\\Request\\\\Complex"}',
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
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
		$server['REQUEST_METHOD'] = 'HEAD';
		$response = RequestHandlerMock::handle('index.php/api/', $server, [], $requestHeaders, $body);
		$sendHead = $this->responseToArray($response);
		
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
			[ // invalid body
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/count'
				],
				'not-json',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'invalid body'
			],
			[ // error in request model
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/count'
				],
				'{"tree": {"id": 1,"model": 1},"inheritance-": "Comhon\\\\Request\\\\Complex"}',
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"Something goes wrong on \'.tree.model\' value : \nvalue must be a string, integer \'1\' given"}'
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestManifestData
	 */
	public function testRequestManifest($server, $get, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server, $get, []);
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $sendGet[2]);
		$this->assertEquals($responseCode, $sendGet[0]);
		$this->assertEquals($responseHeaders, $sendGet[1]);
		
	}
	
	public function requestManifestData()
	{
		return [
			[ // test existing manifest
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Comhon%5cManifest/Comhon%5cSqlTable'
				],
				[],
				200,
				['Content-Type' => 'application/json'],
				'{"name":"Comhon\\\\SqlTable","properties":[{"name":"name","is_id":true,"inheritance-":"Comhon\\\\Manifest\\\\Property\\\\String"},{"name":"database","model":"\\\\Comhon\\\\SqlDatabase","is_foreign":true,"inheritance-":"Comhon\\\\Manifest\\\\Property\\\\Object"}],"version":"3.0"}'
			],
			[ // test model redirect to parent manifest
			[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Comhon%5cManifest/Comhon%5cManifest%5cLocal'
				],
				[],
				301,
				['Content-Type' => 'text/plain', 'Location' => 'http://localhost//index.php/api/Comhon%5cManifest/Comhon%5CManifest'],
				'Comhon\Manifest'
			],
			[ // test existing manifest (invalid namespace prefix)
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Comhon%5cManifest/CComhon%5cManifest%5cLocal'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				"resource 'Comhon\Manifest' with id 'CComhon\Manifest\Local' not found"
			],
			[ // test existing manifest
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Comhon%5cManifest/Comhon%5cMManifest%5cLocal'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				"resource 'Comhon\Manifest' with id 'Comhon\MManifest\Local' not found"
			]
		];
	}
	
}
