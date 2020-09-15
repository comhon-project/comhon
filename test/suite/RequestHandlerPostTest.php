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
use Test\Comhon\Utils\RequestTestTrait;

class RequestHandlerPostTest extends TestCase
{
	use RequestTestTrait;
	
	private static $id;
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->getObjectInstance(false);
		$obj->setValue('firstName', 'john');
		$obj->save();
		self::$id = $obj->getId();
	}
	
	public static function tearDownAfterClass()
	{
		$settings = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->getSerializationSettings();
		$dbHandlerPgSql = DatabaseHandler::getInstanceWithDataBaseId($settings->getValue('database')->getId());
		$dbHandlerPgSql->getPDO()->exec("DELETE FROM {$settings->getValue('name')} WHERE id >= 12");
		
		$settings = ModelManager::getInstance()->getInstanceModel('Test\TestDb')->getSerializationSettings();
		$dbHandlerPgSql = DatabaseHandler::getInstanceWithDataBaseId($settings->getValue('database')->getId());
		$dbHandlerPgSql->getPDO()->exec("DELETE FROM {$settings->getValue('name')} WHERE id_1 = 111");
		
		$settings = ModelManager::getInstance()->getInstanceModel('Test\TestNoId')->getSerializationSettings();
		$dbHandlerPgSql = DatabaseHandler::getInstanceWithDataBaseId($settings->getValue('database')->getId());
		$dbHandlerPgSql->getPDO()->exec("DELETE FROM {$settings->getValue('name')}");
	}
	
	public function setUp()
	{
		MainObjectCollection::getInstance()->reset();
	}
	
	/**
	 *
	 * @dataProvider requestPostIncrementalData
	 */
	public function testRequestPostIncremental($server, $requestHeaders, $RequestBody, $responseCode, $responseHeaders, $responseContent, $modelName, $interfacer)
	{
		self::$id++;
		$obj = ModelManager::getInstance()->getInstanceModel($modelName)->loadObject(self::$id, [], true);
		$this->assertNull($obj);
		
		$response = RequestHandlerMock::handle('index.php/api', $server, [], $requestHeaders, $RequestBody);
		$send = $this->responseToArray($response);
		$sendContentObj = $interfacer->fromString($send[2]);
		$id = $interfacer->getValue($sendContentObj, 'id');
		$this->assertEquals(self::$id, (integer) $id);
		
		$interfacer->unsetValue($sendContentObj, 'id');
		$send[2] = $interfacer->toString($sendContentObj);
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
		
		$obj = ModelManager::getInstance()->getInstanceModel($modelName)->loadObject(self::$id, [], true);
		$this->assertNotNull($obj);
	}
	
	public function requestPostIncrementalData()
	{
		return [
			[ // default content type json
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				201,
				['Content-Type' => 'application/json'],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				'Test\Person\Man',
				new AssocArrayInterfacer()
			],
			[ // content type json
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'Content-Type' => 'application/json'
				],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				201,
				['Content-Type' => 'application/json'],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				'Test\Person\Man',
				new AssocArrayInterfacer()
			],
			[ // content type xml
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'Content-Type' => 'application/xml'
				],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" firstName="Bernard" lastName="Dupond" birthDate="2016-11-13T19:04:05+00:00"><birthPlace>2</birthPlace><bestFriend xsi:nil="true"/><father xsi:nil="true"/><mother xsi:nil="true"/></root>',
				201,
				['Content-Type' => 'application/xml'],
				'<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" firstName="Bernard" lastName="Dupond" birthDate="2016-11-13T19:04:05+00:00"><birthPlace>2</birthPlace><bestFriend xsi:nil="true"/><father xsi:nil="true"/><mother xsi:nil="true"/></root>',
				'Test\Person\Man',
				new XMLInterfacer()
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestPostNotIncrementalData
	 */
	public function testRequestPostNotIncremental($server, $requestHeaders, $RequestBody, $responseCode, $responseHeaders, $responseContent, $modelName, $id)
	{
		$obj = ModelManager::getInstance()->getInstanceModel($modelName)->loadObject($id, [], true);
		$this->assertNull($obj);
		
		$response = RequestHandlerMock::handle('index.php/api', $server, [], $requestHeaders, $RequestBody);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
		
		$obj = ModelManager::getInstance()->getInstanceModel($modelName)->loadObject($id, [], true);
		$this->assertNotNull($obj);
	}
	
	public function requestPostNotIncrementalData()
	{
		return [
			[ // id needed
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				[],
				'{"id1":111,"id2":"aaa","integer":12,"mainParentTestDb":1,"boolean":true,"timestamp":"2020-01-02T19:20:34+00:00"}',
				201,
				['Content-Type' => 'application/json'],
				'{"defaultValue":"default","id1":111,"id2":"aaa","date":null,"timestamp":"2020-01-02T19:20:34+00:00","object":null,"objectWithId":null,"integer":12,"mainParentTestDb":1,"objectsWithId":[],"foreignObjects":[],"lonelyForeignObject":null,"lonelyForeignObjectTwo":null,"manBodyJson":null,"womanXml":null,"boolean":true,"boolean2":true}',
				'Test\TestDb',
				'[111,"aaa"]'
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestPostNoIdData
	 */
	public function testRequestPostNoId($server, $requestHeaders, $RequestBody, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server, [], $requestHeaders, $RequestBody);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestPostNoIdData()
	{
		return [
			[ // model without id
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId'
				],
				[],
				'{"name":"my-name"}',
				201,
				['Content-Type' => 'application/json'],
				'{"name":"my-name"}',
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestPostFailureData
	 */
	public function testRequestPostFailure($server, $requestHeaders, $RequestBody, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server, [], $requestHeaders, $RequestBody);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestPostFailureData()
	{
		return [
			[ // invalid route
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1'
				],
				[],
				'',
				405,
				['Content-Type' => 'text/plain', 'Allow' => 'GET, HEAD, PUT, DELETE, OPTIONS'],
				'method POST not allowed',
			],
			[ // invalid body
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[
					'Content-Type' => 'application/xml'
				],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				400,
				['Content-Type' => 'text/plain'],
				'invalid body',
			],
			[ // interfaced object invalid
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[],
				'{"firstName":"Bernard","lastName":1}',
				400,
				['Content-Type' => 'application/json'],
				'{"code":202,"message":"Something goes wrong on \'.lastName\' value : \nvalue must be a string, integer \'1\' given"}',
			],
			[ // specified id but auto incremental id
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				[],
				'{"id":1000,"firstName":"Bernard"}',
				400,
				['Content-Type' => 'text/plain'],
				'id must not be set to create resource \'Test\Person\Man\'',
			],
			[ // not specified id but needed
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				[],
				'{"id1":1,"integer":12}',
				400,
				['Content-Type' => 'text/plain'],
				'id must be set to create resource \'Test\TestDb\'',
			],
			[ // already existing id
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb'
				],
				[],
				'{"id1":111,"id2":"aaa","integer":12,"mainParentTestDb":1,"boolean":true,"timestamp":"2020-01-02T19:20:34+00:00"}',
				409,
				['Content-Type' => 'text/plain'],
				'resource with id \'[111,"aaa"]\' already exists',
			],
			[ // abstract model with defined serialization
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson'
				],
				['Content-Type' => 'application/json'],
				'{"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				405,
				['Content-Type' => 'text/plain', 'Allow' => 'GET, HEAD, OPTIONS'],
				'method POST not allowed',
			],
			[ // database constraint not satisfied
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cDbConstraint'
				],
				['Content-Type' => 'application/json'],
				'{"unique_name":"Bernard","foreign_constraint":7566}',
				400,
				['Content-Type' => 'application/json'],
				'{"code":802,"message":"reference 7566 of foreign property \'foreign_constraint\' for model \'Test\\\\DbConstraint\' doesn\'t exists"}',
			],
			[ // not specified not null and not required value 
			  // (must be specified and not null due to SQL serialization that set automatically a null value)
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan'
				],
				['Content-Type' => 'application/json'],
				'{"lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null}',
				400,
				['Content-Type' => 'application/json'],
				'{"code":805,"message":"property \'firstName\' of model \'Test\\\\Person\\\\Man\' is not set and cannot be serialized with null value. property should probably be set as required"}',
			],
		];
	}
	
}
