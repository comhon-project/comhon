<?php

use PHPUnit\Framework\TestCase;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Test\Comhon\Mock\RequestHandlerMock;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Database\DatabaseHandler;

class RequestHandlerDeleteTest extends TestCase
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
	 * @dataProvider requestDeleteData
	 */
	public function testRequestDeleteValid($server, $responseCode, $responseHeaders, $responseContent)
	{
		$server['REQUEST_URI'] .= self::$id;
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $response->getSend();
		
		if (!is_null($responseContent)) {
			$responseContent = str_replace('[id]', self::$id, $responseContent);
		}
		
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestDeleteData()
	{
		return [
			[ // valid delete
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				204,
				[],
				null,
			],
			[ // resource already deleted
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/'
				],
				404,
				['Content-Type' => 'text/plain'],
				'resource \'Test\Person\Man\' with id \'[id]\' not found',
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestDeleteFailureData
	 */
	public function testRequestDeleteFailure($server, $responseCode, $responseHeaders, $responseContent)
	{
		$server['REQUEST_URI'] .= self::$id;
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestDeleteFailureData()
	{
		return [
			[ // invalid route
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cPerson%5cMan/1/'
				],
				404,
				['Content-Type' => 'text/plain'],
				'invalid route',
			],
			[ // no property id
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cTestNoId/'
				],
				404,
				['Content-Type' => 'application/json'],
				'{"code":106,"message":"invalid route, model \'Test\\\\TestNoId\' doesn\'t have id property"}',
			],
			[ // private property id
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cTestPrivateId/'
				],
				404,
				['Content-Type' => 'application/json'],
				'{"code":105,"message":"invalid route, cannot use private id property \'id\' on model \'Test\\\\TestPrivateId\' in public context"}'
			],
		];
	}
	
	/**
	 *
	 * @dataProvider requestDeleteFailureInvalidIdData
	 */
	public function testRequestDeleteFailureInvalidId($server, $responseCode, $responseHeaders, $responseContent)
	{
		$response = RequestHandlerMock::handle('index.php/api', $server);
		$send = $response->getSend();
		
		$this->assertEquals($responseContent, $send[2]);
		$this->assertEquals($responseCode, $send[0]);
		$this->assertEquals($responseHeaders, $send[1]);
	}
	
	public function requestDeleteFailureInvalidIdData()
	{
		return [
			[ // invalid id
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/1'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'1\'"}',
			],
			[ // missing value
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1]\'"}',
			],
			[ // empty string value
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[1,""]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[1,\"\"]\'"}',
			],
			[ // null value
				[
					'REQUEST_METHOD' => 'DELETE',
					'REQUEST_URI' => '/index.php/api/Test%5cTestDb/[null,"46"]'
				],
				400,
				['Content-Type' => 'application/json'],
				'{"code":206,"message":"invalid composite id \'[null,\"46\"]\'"}',
			],
		];
	}
	
}
