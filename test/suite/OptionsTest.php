<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\StdObjectInterfacer;
use Test\Comhon\Mock\RequestHandlerMock;
use Test\Comhon\Utils\RequestTestTrait;

class OptionsTest extends TestCase
{
	use RequestTestTrait;
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testNotDefinedOptions()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Person');
		$this->assertNull($model->getOptions());
	}
	
	public function testDefinedOptions()
	{
		$modelBody = ModelManager::getInstance()->getInstanceModel('Test\Body');
		$options = $modelBody->getOptions();
		$this->assertNotNull($options);
		$this->assertSame($options, $modelBody->getOptions());
		
		$interfacer = new StdObjectInterfacer();
		$obj = $interfacer->export($modelBody->getOptions());
		$this->assertEquals(
			'{"name":"Test\\\\Body","version":"3.0","unique":{"allowed_methods":[]},"collection":{"allowed_methods":["GET","HEAD","OPTIONS"],"allow_complex_request":false,"requestable_properties":["id","height","hairColor"]}}', 
			json_encode($obj)
		);
		
		$modelBodyMan = ModelManager::getInstance()->getInstanceModel('Test\Body\Man');
		$this->assertSame($options, $modelBodyMan->getOptions());
	}
	
	public function testDefinedOptionsOnParent()
	{
		$modelBodyMan = ModelManager::getInstance()->getInstanceModel('Test\Body\Man');
		$modelBody = ModelManager::getInstance()->getInstanceModel('Test\Body');
		$this->assertSame($modelBody->getOptions(), $modelBodyMan->getOptions());
	}
	
	public function testDefinedOptionsOnParentAndCurrent()
	{
		$modelBodyWoman = ModelManager::getInstance()->getInstanceModel('Test\Body\Woman');
		$modelBody = ModelManager::getInstance()->getInstanceModel('Test\Body');
		$this->assertNotSame($modelBody->getOptions(), $modelBodyWoman->getOptions());
		
		$interfacer = new StdObjectInterfacer();
		$obj = $interfacer->export($modelBodyWoman->getOptions());
		$this->assertEquals('{"name":"Test\\\\Body\\\\Woman","version":"3.0","unique":{"allowed_methods":[]},"collection":{"allowed_methods":["GET"]}}', json_encode($obj));
	}
	
	/**
	 *
	 * @dataProvider requestAllowedData
	 */
	public function testRequestAllowed($server, $requestGet, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server, $requestGet);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestAllowedData()
	{
		return [
			[ // Options manifest allow GET on collection route
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cBody%5cMan'
				],
				['id' => 1, '__properties__' => ['height']],
				200,
				['Content-Type' => 'application/json'],
				'[{"id":1,"height":1.8}]',
			],
			[ // Options manifest doesn't allow POST on collection route
				[
					'REQUEST_METHOD' => 'POST',
					'REQUEST_URI' => '/index.php/api/Test%5cBody%5cMan'
				],
				[],
				405,
				['Content-Type' => 'text/plain', 'Allow' => 'GET, HEAD, OPTIONS'],
				'method POST not allowed',
			],
			[ // Options manifest doesn't allow any method on unique route
				[
					'REQUEST_METHOD' => 'GET',
					'REQUEST_URI' => '/index.php/api/Test%5cBody%5cMan/1'
				],
				[],
				404,
				['Content-Type' => 'text/plain'],
				'invalid route',
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestDefaultLimitData
	 */
	public function testRequestDefaultLimit($modelName, $requestGet, $responseCode, $responseHeaders, $count, $limitedCount)
	{
		$server = [
				'REQUEST_METHOD' => 'GET',
				'REQUEST_URI' => "/index.php/api/count/".urlencode($modelName)
		];
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $this->responseToArray($response);
		$this->assertEquals($count, $send[2]);
		
		
		$server['REQUEST_URI'] = "/index.php/api/".urlencode($modelName);
		$response = RequestHandlerMock::handle('index.php/api', $server, $requestGet);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($limitedCount, count(json_decode($send[2])));
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestDefaultLimitData()
	{
		return [
			[ // Config default limit, request without limit
				'Test\TestPrivateId',
				['__properties__' => ['name']],
				200,
				['Content-Type' => 'application/json'],
				26,
				20,
			],
			[ // Config default limit, request with limit inferior than default
				'Test\TestPrivateId',
				['__properties__' => ['name'], "__range__" => "0-9", "__order__" => '[{"property":"id"}]'],
				200,
				['Content-Type' => 'application/json'],
				26,
				10,
			],
			[ // Config default limit, request with limit supperior than default
				'Test\TestPrivateId',
				['__properties__' => ['name'], "__range__" => "0-99", "__order__" => '[{"property":"id"}]'],
				200,
				['Content-Type' => 'application/json'],
				26,
				20,
			],
			[ // Model default limit, request without limit
				'Test\TestNoId',
				['__properties__' => ['name']],
				200,
				['Content-Type' => 'application/json'],
				5,
				3,
			],
			[ // Model default limit, request with limit inferior than default
				'Test\TestNoId',
				['__properties__' => ['name'], "__range__" => "0-1", "__order__" => '[{"property":"name"}]'],
				200,
				['Content-Type' => 'application/json'],
				5,
				2,
			],
			[ // Model default limit, request with limit supperior than default
				'Test\TestNoId',
				['__properties__' => ['name'], "__range__" => "0-99", "__order__" => '[{"property":"name"}]'],
				200,
				['Content-Type' => 'application/json'],
				5,
				3,
			],
		];
	}
	
	/**
	 *
	 * @dataProvider AllowedRequestData
	 */
	public function testAllowedRequest($modelName, $requestGet, $requestHeaders, $responseCode, $responseHeaders, $responseContent)
	{
		$server = [
				'REQUEST_METHOD' => 'GET',
				'REQUEST_URI' => "/index.php/api/".urlencode($modelName)
		];
		$response = RequestHandlerMock::handle('index.php/api', $server, $requestGet, $requestHeaders);
		$send = $this->responseToArray($response);
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function AllowedRequestData()
	{
		return [
			[ // Test\Body doesn't allow complex request on options file
				'Test\Body\Man',
				['id' => '1'],
				['Content-Type' => 'application/json'],
				400,
				['Content-Type' => 'text/plain'],
				'complex or intermediate request not allowed for model Test\Body\Man',
			],
		];
	}

}
