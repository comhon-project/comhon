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
		$this->assertEquals($responseCode, $response->getCode());
		$this->assertEquals($responseHeaders,$response->getHeaders());
		$this->assertEquals($responseContent,$response->getContent());
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
	
		$this->assertEquals($responseCode, $response->getCode());
		$this->assertEquals($responseHeaders, $response->getHeaders());
		$this->assertEquals($responseContent, $response->getContent());
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
			[ // filter properties with inheritance
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
		
		$this->assertEquals($responseCode, $response->getCode());
		$this->assertEquals($responseHeaders, $response->getHeaders());
		$this->assertEquals($responseContent, $response->getContent());
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
			[ // bad properties filter
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				[
					'__properties__' => ['does_not_exist']
				],
				400,
				['Content-Type' => 'text/plain'],
				'Undefined property \'does_not_exist\' for model \'Test\Person\''
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
		
		$this->assertEquals($responseCode, $response->getCode());
		$this->assertEquals($responseHeaders, $response->getHeaders());
		$this->assertEquals($responseContent, $response->getContent());
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
	
}
