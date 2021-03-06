<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Test\Comhon\Mock\RequestHandlerMock;
use Comhon\Object\Collection\MainObjectCollection;
use Test\Comhon\Utils\RequestTestTrait;

class RequestHandlerOptionsTest extends TestCase
{
	use RequestTestTrait;
	
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
	 * @dataProvider requestOptionsData
	 */
	public function testRequestOptions($server, $responseCode, $responseHeaders)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestOptionsData()
	{
		return [
			[ // collection, sql, id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				200,
				['Allow' => 'GET, HEAD, POST, OPTIONS'],
			],
			[ // unique, sql, id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				200,
				['Allow' => 'GET, HEAD, PUT, DELETE, OPTIONS'],
			],
			[ // collection, sql, no id (options file defined but without allowed properties nodes)
				[
						'REQUEST_METHOD' => 'OPTIONS',
						'REQUEST_URI' => '/index.php/api/Test%5cTestNoId'
				],
				200,
				['Allow' => 'GET, HEAD, POST, OPTIONS', 'Content-Type' => 'application/json'],
			],
			[ // collection, sql, private id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId'
				],
				200,
				['Allow' => 'GET, HEAD, POST, OPTIONS'],
			],
			[ // collection, file
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTest'
				],
				200,
				['Allow' => 'POST, OPTIONS'],
			],
			[ // unique, file
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTest/1'
				],
				200,
				['Allow' => 'GET, HEAD, PUT, DELETE, OPTIONS'],
			],
			[ // collection, abstract, sql
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				200,
				['Allow' => 'GET, HEAD, OPTIONS'],
			],
			[ // unique, abstract, sql
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson/1'
				],
				200,
				['Allow' => 'GET, HEAD, DELETE, OPTIONS'],
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestOptionsWithBodyData
	 */
	public function testRequestOptionsWithBody($server, $responseCode, $responseHeaders, $responseBody)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseBody, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestOptionsWithBodyData()
	{
		return [
			[
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Comhon%5cManifest/1'
				],
				200,
				['Allow' => 'GET, HEAD, OPTIONS', 'Content-Type' => 'application/json'],
				'{"name":"Comhon\\\\Manifest","version":"3.0","unique":{"allowed_methods":["GET","HEAD","OPTIONS"]},"collection":{"allowed_methods":[]}}',
			],
			[
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Comhon%5cManifest'
				],
				404,
				['Content-Type' => 'text/plain'],
				'invalid route',
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestOptionsFailureData
	 */
	public function testRequestOptionsFailure($server, $responseCode, $responseHeaders, $responseBody)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseBody, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestOptionsFailureData()
	{
		return [
			[ // unique, sql, no id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId/1'
				],
				404,
				['Content-Type' => 'application/json'],
				'{"code":106,"message":"invalid route, model \'Test\\\\TestNoId\' doesn\'t have id property"}',
			],
			[ // unique, sql, private id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId/1'
				],
				404,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"invalid route, cannot use private id property \'id\' on model \'Test\\\\TestPrivateId\' in public context"}',
			],
		];
	}
	
	/**
	 *
	 * @dataProvider patternsData
	 */
	public function testPatterns($pattern)
	{
		$server = [
			'REQUEST_METHOD' => 'OPTIONS',
			'REQUEST_URI' => "/index.php/api/pattern/$pattern"
		];
		$response = RequestHandlerMock::handle('////index.php////api///', $server, [], []);
		$sendGet = $this->responseToArray($response);
		
		$this->assertEquals('', $sendGet[2]);
		$this->assertEquals(200, $sendGet[0]);
		$this->assertEquals(['Allow' => 'GET, HEAD, OPTIONS'], $sendGet[1]);
	}
	
	public function patternsData()
	{
	return [
		[
			'email'
		],
		[
			'foo'
		]
	];
	}
	
}
