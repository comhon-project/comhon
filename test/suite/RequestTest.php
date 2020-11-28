<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Request\ComplexRequester;
use Comhon\Exception\ArgumentException;
use Comhon\Exception\ComhonException;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Exception\Literal\NotLinkableLiteralException;
use Comhon\Exception\Literal\UnresolvableLiteralException;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Exception\Literal\NotAllowedLiteralException;
use Comhon\Exception\Interfacer\ImportException;

class RequestTest extends TestCase
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
		ComplexRequester::build($obj);
	}
	
	public function testBadModel()
	{
		$request = [
			"inheritance-"=> 'Comhon\SqlTable'
		];
		
		$this->expectException(ImportException::class);
		$this->expectExceptionMessage("model must be a 'Comhon\Request', model 'Comhon\SqlTable' given");
		ComplexRequester::build($request);
	}
	
	public function testUnloadedNotValidRequestRoot()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate')->getObjectInstance(false);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage("Something goes wrong on '.' object : 
missing required value 'root' on comhon object with model 'Comhon\Request\Intermediate'");
		ComplexRequester::build($obj);
	}
	
	public function testUnloadedNotValidRequestLeaf()
	{
		$NodeModel = ModelManager::getInstance()->getInstanceModel('Comhon\Model\Node');
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Advanced')->getObjectInstance(false);
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
		ComplexRequester::build($obj);
	}
	
	public function testNotRefValueRequest()
	{
		$literalModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Literal\String');
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Advanced')->getObjectInstance(false);
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
		$simpleCollection = $obj->initValue('simple_collection', false);
		$simpleCollection->pushValue($filter);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage("Something goes wrong on '.simple_collection.0.node' object : 
foreign value with model 'Comhon\Model\Root' and id '2' not referenced in interfaced object");
		ComplexRequester::build($obj);
	}
	
	public function testForeignWithoutIdRequest()
	{
		$literalModel = ModelManager::getInstance()->getInstanceModel('Comhon\Logic\Simple\Literal\String');
		$obj = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Advanced')->getObjectInstance(false);
		$tree = $obj->getInstanceValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', 'Comhon\SqlTable');
		$obj->setValue('tree', $tree);
		$filter = $literalModel->getObjectInstance(false);
		$obj->setValue('filter', $filter);
		
		$this->expectException(ComhonException::class);
		$this->expectExceptionMessage("Something goes wrong on '.filter' object : 
missing or not complete id on foreign value");
		ComplexRequester::build($obj);
	}
	
	/**
	 * @dataProvider validIntermediateRequest
	 */
	public function testIntermediateToAdvancedValid($file_rf)
	{
		$dataIntermediate_ad = self::$data_ad . 'IntermediateToAdvanced' . DIRECTORY_SEPARATOR . 'Intermediate' . DIRECTORY_SEPARATOR;
		$dataAdvanced_ad = self::$data_ad . 'IntermediateToAdvanced' . DIRECTORY_SEPARATOR . 'Advanced' . DIRECTORY_SEPARATOR;
		
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate');
		
		$interfacedObject = $interfacer->read($dataIntermediate_ad . $file_rf);
		$request = ComplexRequester::intermediateToAdvancedRequest($model->import($interfacedObject, $interfacer));
		$this->assertEquals(json_encode($interfacer->read($dataAdvanced_ad . $file_rf)), $interfacer->toString($request->export($interfacer)));
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
	public function testIntermediateToAdvancedNotValid($file_rf, $exception, $message)
	{
		$data_ad = self::$data_ad . 'IntermediateToAdvanced' . DIRECTORY_SEPARATOR . 'Intermediate' . DIRECTORY_SEPARATOR;
		
		$interfacer = new StdObjectInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Intermediate');
		
		$interfacedObject = $interfacer->read($data_ad . $file_rf);
		
		$this->expectException($exception);
		$this->expectExceptionMessage($message);
		ComplexRequester::intermediateToAdvancedRequest($model->import($interfacedObject, $interfacer));
		
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
	 * @dataProvider notAllowedLiteralRequestData
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
			"simple_collection" => [
				$literal
			],
			"filter" => 1,
		    "inheritance-"=> 'Comhon\Request\Advanced'
		];
		
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Advanced');
		
		$this->expectException($exception);
		$this->expectExceptionMessage($message);
		ComplexRequester::build($model->import($request, $interfacer), true);
	}
	
	public function notAllowedLiteralRequestData()
	{
		return [
			[
				[
					"property" => "string",
					"operator" => "=",
					"value"    => true,
					"inheritance-"=> 'Comhon\Logic\Simple\Literal\Boolean'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\Boolean' not allowed on property 'string' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\String, Comhon\Logic\Simple\Literal\Set\String, Comhon\Logic\Simple\Literal\Null]"
			],
			[
				[
					"property" => "boolean",
					"operator" => "=",
					"value"    => 'aaa',
					"inheritance-"=> 'Comhon\Logic\Simple\Literal\String'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\String' not allowed on property 'boolean' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\Boolean, Comhon\Logic\Simple\Literal\Null]"
			],
			[
				[
					"property" => "integer",
					"operator" => "=",
					"value"    => true,
					"inheritance-"=> 'Comhon\Logic\Simple\Literal\Boolean'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\Boolean' not allowed on property 'integer' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\Numeric\Integer, Comhon\Logic\Simple\Literal\Set\Numeric\Integer, Comhon\Logic\Simple\Literal\Null]"
			],
			[
				[
					"property" => "date",
					"operator" => "=",
					"value"    => true,
					"inheritance-"=> 'Comhon\Logic\Simple\Literal\Boolean'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\Boolean' not allowed on property 'date' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\String, Comhon\Logic\Simple\Literal\Set\String, Comhon\Logic\Simple\Literal\Null]"
			],
			[
				[
					"property" => "mainParentTestDb",
					"operator" => "=",
					"value"    => true,
					"inheritance-"=> 'Comhon\Logic\Simple\Literal\Boolean'
				],
				NotAllowedLiteralException::class,
				"literal 'Comhon\Logic\Simple\Literal\Boolean' not allowed on property 'mainParentTestDb' of model 'Test\TestDb'. must be one of [Comhon\Logic\Simple\Literal\Numeric\Integer, Comhon\Logic\Simple\Literal\Set\Numeric\Integer, Comhon\Logic\Simple\Literal\Null]"
			],
		];
	}
	
	/**
	 * 
	 * @dataProvider foreignValueFilterRequestData
	 */
	public function testForeignValueFilterRequest($modelName, $propertyName, $value, $literalModelName, $result)
	{
		$request = [
			"tree" => [
				"model"   => $modelName,
				"id"      => 1
			],
			"simple_collection" => [
				[
					"id"       => 1,
					"node"     => 1,
					"property" => $propertyName,
					"operator" => "=",
					"value"    => $value,
					"inheritance-"=> $literalModelName
				]
			],
			"filter" => 1,
		    "inheritance-"=> 'Comhon\Request\Advanced'
		];
		$request = ComplexRequester::build($request);
		$array = $request->execute();
		$interfacer = new AssocArrayInterfacer();
		
		$this->assertEquals($result, $interfacer->toString($array->export($interfacer)));
	}
	
	public function foreignValueFilterRequestData()
	{
		return [
			[
				'Test\ChildTestDb',
				'parentTestDb',
				'[1,"1501774389"]',
				'Comhon\Logic\Simple\Literal\String',
				'[{"id":1,"name":"plop","parentTestDb":"[1,\"1501774389\"]"},{"id":2,"name":"plop2","parentTestDb":"[1,\"1501774389\"]"}]'
			],
			[
				'Test\Person',
				'birthPlace',
				2,
				'Comhon\Logic\Simple\Literal\Numeric\Integer',
				'[{"id":1,"firstName":"Bernard","lastName":"Dupond","birthDate":"2016-11-13T19:04:05+00:00","birthPlace":2,"bestFriend":null,"father":null,"mother":null,"inheritance-":"Test\\\\Person\\\\Man"},{"id":11,"firstName":"Naelya","lastName":"Dupond","birthDate":null,"birthPlace":2,"bestFriend":null,"father":1,"mother":null,"inheritance-":"Test\\\\Person\\\\Woman"}]'
			]
		];
	}
	
	/**
	 *
	 * @dataProvider notFilterableValueRequestData
	 */
	public function testNotFilterableValueRequest($modelName, $propertyName, $value, $literalModelName, $exception, $message)
	{
		$request = [
			"tree" => [
				"model"   => $modelName,
				"id"      => 1
			],
			"simple_collection" => [
				[
					"id"       => 1,
					"node"     => 1,
					"property" => $propertyName,
					"operator" => "=",
					"value"    => $value,
					"inheritance-"=> $literalModelName
				]
			],
			"filter" => 1,
			"inheritance-"=> 'Comhon\Request\Advanced'
		];
		
		$this->expectException($exception);
		$this->expectExceptionMessage($message);
		ComplexRequester::build($request, true);
	}
	
	public function notFilterableValueRequestData()
	{
		return [
			[
				'Test\TestDb',
				'objectWithId',
				'my_id',
				'Comhon\Logic\Simple\Literal\String',
				NotAllowedLiteralException::class,
				'there is no literal allowed on property \'objectWithId\' of model \'Test\TestDb\'.'
			],
			[
				'Test\Person',
				'children',
				2,
				'Comhon\Logic\Simple\Literal\Numeric\Integer',
				NotAllowedLiteralException::class,
				'there is no literal allowed on property \'children\' of model \'Test\Person\'.'
			]
		];
	}
}
