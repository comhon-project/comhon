<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Request\ComplexLoadRequest;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Exception\Literal\NotLinkableLiteralException;
use Comhon\Exception\Literal\UnresolvableLiteralException;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Exception\Request\NotAllowedLiteralException;

class ObjectLoadRequestTest extends TestCase
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
	
	public function testBadFirstArgumentModel()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\SqlTable')->getObjectInstance();
		
		$this->expectException(ArgumentException::class);
		ComplexLoadRequest::build($obj);
	}
	
	public function testUnloadedRequestRoot()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate')->getObjectInstance(false);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage('all objects must be loaded. object not loaded found : .');
		ComplexLoadRequest::build($obj);
	}
	
	public function testUnloadedRequestLeaf()
	{
		$NodeModel = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Node');
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex')->getObjectInstance(false);
		$tree = $obj->initValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', 'Comhon\SqlTable');
		$nodes = $tree->initValue('nodes', false);
		$nodes->pushValue($NodeModel->getObjectInstance(false));
		$obj->setIsLoaded(true);
		$tree->setIsLoaded(true);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage('all objects must be loaded. object not loaded found : .tree.nodes.0');
		ComplexLoadRequest::build($obj);
	}
	
	/**
	 * @dataProvider validIntermediateRequest
	 */
	public function testIntermediateToComplexValid($file_rf)
	{
		$dataIntermediate_ad = self::$data_ad . 'IntermediateToComplex' . DIRECTORY_SEPARATOR . 'Intermediate' . DIRECTORY_SEPARATOR;
		$dataComplex_ad = self::$data_ad . 'IntermediateToComplex' . DIRECTORY_SEPARATOR . 'Complex' . DIRECTORY_SEPARATOR;
		
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate');
		
		$interfacedObject = $interfacer->read($dataIntermediate_ad . $file_rf);
		$request = ComplexLoadRequest::intermediateToComplexRequest($model->import($interfacedObject, $interfacer));
		$this->assertEquals(json_encode($interfacer->read($dataComplex_ad . $file_rf)), $interfacer->toString($request->export($interfacer)));
	}
	
	public function validIntermediateRequest()
	{
		return [
			[
				'valid_no_filter.json',
			],
			[
				'valid_filter_only_root.json',
			],
			[
				'valid_filter_1.json',
			],
			[
				'valid_filter_2.json',
			]
		];
	}
	
	/**
	 * @dataProvider notPersistantIntermediateRequestData
	 */
	public function testIntermediateToComplexNotValid($file_rf, $exception, $message)
	{
		$data_ad = self::$data_ad . 'IntermediateToComplex' . DIRECTORY_SEPARATOR . 'Intermediate' . DIRECTORY_SEPARATOR;
		
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate');
		
		$interfacedObject = $interfacer->read($data_ad . $file_rf);
		
		$this->expectException($exception);
		$this->expectExceptionMessage($message);
		ComplexLoadRequest::intermediateToComplexRequest($model->import($interfacedObject, $interfacer));
		
	}
	
	public function notPersistantIntermediateRequestData()
	{
		return [
			[
				'invalid_not_linkable_1.json',
				NotLinkableLiteralException::class,
				'model \'Test\Person\' from literal {"id":1,"node":2,"property":"integer","operator":">","value":200} is not linked to requested model \'Test\Request\Root\' or doesn\'t have compatible serialization'
			],
			[
				'invalid_not_linkable_2.json',
				NotLinkableLiteralException::class,
				'model \'Test\Request\Five\' from literal {"id":1,"node":2,"property":"integer","operator":">","value":200} is not linked to requested model \'Test\Request\Root\' or doesn\'t have compatible serialization'
			],
			[
				'invalid_not_resolvable.json',
				UnresolvableLiteralException::class,
				'Cannot resolve literal with model \'Test\Request\Three\', it might be applied on several properties'
			]
		];
	}
	
	
	
	/**
	 * @dataProvider notAllowedLiteralData
	 */
	public function testNotAllowedLiteralRequest($literal, $exception, $message)
	{
		$literal["id"] = 1;
		$literal["node"] = 1;
		$request = [
			"tree" => [
				"model"   => 'Test\TestDb',
				"id"      => 1
			],
			"simpleCollection" => [
				$literal
			],
			"filter" => 1,
		    "__inheritance__"=> 'Comhon\Request\Complex'
		];
		
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex');
		
		$this->expectException($exception);
		$this->expectExceptionMessage($message);
		ComplexLoadRequest::build($model->import($request, $interfacer), true);
		
	}
	
	public function notAllowedLiteralData()
	{
		return [
			[
				[
					"property" => "string",
					"operator" => "=",
					"value"    => true,
					"__inheritance__"=> 'Comhon\Logic\Simple\Literal\Boolean'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\Boolean' not allowed on property 'string' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\String, Comhon\Logic\Simple\Literal\Set\String, Comhon\Logic\Simple\Literal\Null]"
			],
			[
				[
					"property" => "boolean",
					"operator" => "=",
					"value"    => 'aaa',
					"__inheritance__"=> 'Comhon\Logic\Simple\Literal\String'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\String' not allowed on property 'boolean' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\Boolean, Comhon\Logic\Simple\Literal\Null]"
			],
			[
				[
					"property" => "integer",
					"operator" => "=",
					"value"    => true,
					"__inheritance__"=> 'Comhon\Logic\Simple\Literal\Boolean'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\Boolean' not allowed on property 'integer' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\Numeric\Integer, Comhon\Logic\Simple\Literal\Set\Numeric\Integer, Comhon\Logic\Simple\Literal\Null]"
			],
				
			[
				[
					"property" => "date",
					"operator" => "=",
					"value"    => true,
					"__inheritance__"=> 'Comhon\Logic\Simple\Literal\Boolean'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\Boolean' not allowed on property 'date' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\String, Comhon\Logic\Simple\Literal\Set\String, Comhon\Logic\Simple\Literal\Null]"
			],
		];
	}
	
}
