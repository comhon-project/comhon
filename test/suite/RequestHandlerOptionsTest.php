<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Test\Comhon\Mock\RequestHandlerMock;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;

class RequestHandlerOptionsTest extends TestCase
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
	 * @dataProvider requestOptionsData
	 */
	public function testRequestOptions($server, $responseCode, $responseHeaders)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $response->getSend();
		
		$this->assertNull($send[2]);
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
					['Allow' => 'GET, HEAD, OPTIONS'],
			],
			[ // unique, sql, id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				200,
				['Allow' => 'GET, HEAD, POST, PUT, DELETE, OPTIONS'],
			],
			[ // collection, sql, no id
				[
						'REQUEST_METHOD' => 'OPTIONS',
						'REQUEST_URI' => '/index.php/api/Test%5cTestNoId'
				],
				200,
				['Allow' => 'GET, HEAD, OPTIONS'],
			],
			[ // unique, sql, no id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId/1'
				],
				200,
				['Allow' => 'OPTIONS'],
			],
			[ // collection, sql, private id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId'
				],
				200,
				['Allow' => 'GET, HEAD, OPTIONS'],
			],
			[ // unique, sql, private id
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId/1'
				],
				200,
				['Allow' => 'OPTIONS'],
			],
			[ // collection, file
			[
				'REQUEST_METHOD' => 'OPTIONS',
				'REQUEST_URI' => '/index.php/api/Test%5cTest'
			],
			200,
			['Allow' => 'OPTIONS'],
		],
			[ // unique, file
				[
					'REQUEST_METHOD' => 'OPTIONS',
					'REQUEST_URI' => '/index.php/api/Test%5cTest/1'
				],
				200,
				['Allow' => 'GET, HEAD, POST, PUT, DELETE, OPTIONS'],
			],
		];
	}
	
}
