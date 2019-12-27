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
use Comhon\Exception\Interfacer\ImportException;

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
	
	public function testBadModel()
	{
		$request = [
			"__inheritance__"=> 'Comhon\SqlTable'
		];
		
		$this->expectException(ImportException::class);
		$this->expectExceptionMessage("model must be a 'Comhon\Request', model 'Comhon\SqlTable' given");
		ComplexLoadRequest::build($request);
	}
	
	public function testUnloadedNotValidRequestRoot()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate')->getObjectInstance(false);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage("Something goes wrong on '.' object : 
missing required value 'root' on comhon object with model 'Comhon\Request\Intermediate'");
		ComplexLoadRequest::build($obj);
	}
	
	public function testUnloadedNotValidRequestLeaf()
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
		$this->expectExceptionMessage("Something goes wrong on '.tree.nodes.0' object : 
missing required value 'id' on comhon object with model 'Comhon\Model\Node'");
		ComplexLoadRequest::build($obj);
	}
	
	public function testNotRefValueRequest()
	{
		$literalModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Literal\String');
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex')->getObjectInstance(false);
		$tree = $obj->getInstanceValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', 'Comhon\SqlTable');
		$obj->setValue('tree', $tree);
		$notRefTree = $obj->getInstanceValue('tree', false);
		$notRefTree->setId(2);
		$notRefTree->setValue('model', 'Comhon\SqlTable');
		$filter = $literalModel->getObjectInstance(false);
		$filter->setId(1);
		$filter->setValue('node', $notRefTree);
		$filter->setValue('property', 'my_property');
		$filter->setValue('operator', '=');
		$filter->setValue('value', 'hehe');
		$simpleCollection = $obj->initValue('simpleCollection', false);
		$simpleCollection->pushValue($filter);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage("Something goes wrong on '.simpleCollection.0.node' object : 
foreign value with model 'Comhon\Model\Root' and id '2' not referenced in interfaced object");
		ComplexLoadRequest::build($obj);
	}
	
	public function testForeignWithoutIdRequest()
	{
		$literalModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Literal\String');
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex')->getObjectInstance(false);
		$tree = $obj->getInstanceValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', 'Comhon\SqlTable');
		$obj->setValue('tree', $tree);
		$filter = $literalModel->getObjectInstance(false);
		$obj->setValue('filter', $filter);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage("Something goes wrong on '.filter' object : 
missing or not complete id on foreign value");
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
