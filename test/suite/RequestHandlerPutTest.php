<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Test\Comhon\Mock\RequestHandlerMock;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Database\DatabaseHandler;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;

class RequestHandlerPutTest extends TestCase
{
	private static $id;
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->getObjectInstance(false);
		$obj->save();
		self::$id = $obj->getId();
	}
	
	public static function tearDownAfterClass()
	{
		$settings = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->getSerializationSettings();
		$dbHandlerPgSql = DatabaseHandler::getInstanceWithDataBaseId($settings->getValue('database')->getId());
		$dbHandlerPgSql->getPDO()->exec("DELETE FROM {$settings->getValue('name')} WHERE id >= 12");
	}
	
	public function setUp()
	{
		MainObjectCollection::getInstance()->reset();
	}
	
	/**
	 *
	 * @dataProvider requestPutData
	 */
	public function testRequestPutValid($server, $requestHeaders, $RequestBody, $responseCode, $responseHeaders, $responseContent, $interfacer)
	{
		$server['REQUEST_URI'] .= self::$id;
		$response = RequestHandlerMock::handle('index.php/api', $server, [], $requestHeaders, $RequestBody);
		$send = $response->getSend();
		$sendContentObj = $interfacer->fromString($send[2]);
		$id = $interfacer->getValue($sendContentObj, 'id');
		$this->assertEquals(self::$id, $id);
		
		$interfacer->unsetValue($sendContentObj, 'id');
		$send[2] = $interfacer->toString($sendContentObj);
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestPutData()
	{
		return [
			[ // default content type json
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				200,
				['Content-Type' => 'application/json'],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				new AssocArrayInterfacer()
			],
			[ // content type xml, partial content so not specified values are updated to null
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				[
					'Content-Type' => 'application/xml'
				],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" firstName="Bernardo"></root>',
					200,
				['Content-Type' => 'application/xml'],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" firstName="Bernardo" lastName="xsi:nil" birthDate="xsi:nil"><birthPlace xsi:nil="true"/><bestFriend xsi:nil="true"/><father xsi:nil="true"/><mother xsi:nil="true"/></root>',
				new XMLInterfacer()
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestPutFailureData
	 */
	public function testRequestPutFailure($server, $requestHeaders, $RequestBody, $responseCode, $responseHeaders, $responseContent)
	{
		$server['REQUEST_URI'] .= self::$id;
		$response = RequestHandlerMock::handle('index.php/api', $server, [], $requestHeaders, $RequestBody);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestPutFailureData()
	{
		return [
			[ // invalid route
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1/'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				404,
				['Content-Type' => 'text/plain'],
				'invalid route',
			],
			[ // invalid body
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				[
					'Content-Type' => 'application/json'
				],
				'malformed',
				400,
				['Content-Type' => 'text/plain'],
				'invalid body',
			],
			[ // interfaced object invalid
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				[
					'Content-Type' => 'application/xml'
				],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" firstName="Bernardo" lastName="Dupond" birthDate="2016-11-13T19:04:05+00:00"><birthPlace>aaa</birthPlace><bestFriend xsi:nil="true"/><father xsi:nil="true"/><mother xsi:nil="true"/></root>',
				400,
				['Content-Type' => 'application/json'],
				'{"code":104,"message":"Something goes wrong on \'.birthPlace\' value : \nCannot cast value \'aaa\', value should be integer"}',
			],
			[ // conflict route and body id 
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				[
					'Content-Type' => 'application/xml'
				],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="4545" firstName="Bernardo" lastName="Dupond" birthDate="2016-11-13T19:04:05+00:00"><birthPlace>2</birthPlace><bestFriend xsi:nil="true"/><father xsi:nil="true"/><mother xsi:nil="true"/></root>',
				400,
				['Content-Type' => 'text/plain'],
				'conflict on route id and body id',
			],
			[ // no property id
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId/'
				],
				[
					'Content-Type' => 'application/xml'
				],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" firstName="Bernardo" lastName="Dupond"></root>',
				404,
				['Content-Type' => 'application/json'],
				'{"code":106,"message":"invalid route, model \'Test\\\\TestNoId\' doesn\'t have id property"}',
			],
			[ // private property id
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId/'
				],
				[
					'Content-Type' => 'application/xml'
				],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" firstName="Bernardo" lastName="Dupond"></root>',
				404,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"invalid route, cannot use private id property \'id\' on model \'Test\\\\TestPrivateId\' in public context"}'
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestPutOtherFailureData
	 */
	public function testRequestPutOtherFailure($server, $requestHeaders, $RequestBody, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server, [], $requestHeaders, $RequestBody);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestPutOtherFailureData()
	{
		return [
			[ // invalid id
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/1'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'1\'"}',
			],
			[ // missing value
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1]'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1]\'"}',
			],
			[ // empty string value
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,""]'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1,\"\"]\'"}',
			],
			[ // null value
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,null]'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1,null]\'"}',
			],
			[ // null value
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[123123,"azezae"]'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				404,
				['Content-Type' => 'text/plain'],
				'resource \'Test\TestDb\' with id \'[123123,"azezae"]\' not found',
			],
			[ // abstract model with defined serialization
				[
					'REQUEST_METHOD' => 'PUT',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson/213'
				],
				['Content-Type' => 'application/json'],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				405,
				['Content-Type' => 'text/plain', 'Allow' => 'GET, HEAD, DELETE, OPTIONS'],
				'method PUT not allowed',
			],
		];
	}
	
}
