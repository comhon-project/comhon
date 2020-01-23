<?php

use PHPUnit\Framework\TestCase;
use Comhon\Database\SelectQuery;
use Comhon\Database\DatabaseHandler;
use Comhon\Request\ComplexRequester;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;

class CountRequestTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testCountSelectQueryMySql()
	{
		// no group
		$select = new SelectQuery('person');
		$select->addOrder('id')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('1')->count($select);
		$this->assertSame(9, $count);
		
		// group by id
		$select = new SelectQuery('person');
		$select->addGroup('id')->addOrder('id')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('1')->count($select);
		$this->assertSame(9, $count);
		
		// group by last_name (some same last_name)
		$select = new SelectQuery('person');
		$select->addGroup('last_name')->addOrder('last_name')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('1')->count($select);
		$this->assertSame(7, $count);
	}
	
	public function testCountSelectQueryPgSql()
	{
		// no group
		$select = new SelectQuery('person');
		$select->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('2')->count($select);
		$this->assertSame(9, $count);
		
		// group by id -> each group have count one
		$select = new SelectQuery('person');
		$select->addGroup('id')->addOrder('id')->limit(2);
		$select->getMainTable()->addSelectedColumn('id');
		$count = DatabaseHandler::getInstanceWithDataBaseId('2')->count($select);
		$this->assertSame(9, $count);
		
		// group by last_name (some same last_name)
		$select = new SelectQuery('person');
		$select->addGroup('"last_name"')->addOrder('"last_name"')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('2')->count($select);
		$this->assertSame(7, $count);
	}
	
	public function testCountServiceRequest()
	{
		$request = ModelManager::getInstance()->getInstanceModel('Comhon\Request\Complex')->getObjectInstance(false);
		$tree = $request->initValue('tree', false);
		$tree->setId(1);
		$tree->setValue('model', 'Test\Person');
		$request->setIsLoaded(true);
		$tree->setIsLoaded(true);
		$count = ComplexRequester::build($request)->count();
		
		$this->assertSame(9, $count);
	}
	
	public function testCountWithLimitAndFilterServiceRequest()
	{
		$request = [
			"tree" => [
				"id" => 1,
				"model" => "Test\Person"
			],
			"filter" => 1,
			"simpleCollection" => [
				[
					"id" => 1,
					"node" => 1,
					"property" => "lastName",
					"operator" => "=",
					"value" => "Dupond",
					"__inheritance__" => "Comhon\Logic\Simple\Literal\String"
				]
			],
			"limit" => 1,
			"order" => [["property" => "firstName"]],
			"__inheritance__" => 'Comhon\Request\Complex'
		];
		
		$objects = ComplexRequester::build($request)->execute();
		$this->assertCount(1, $objects->getValues());
		
		// no limit for count so we may have count greater than retrieved objects
		$count = ComplexRequester::build($request)->count();
		$this->assertSame(2, $count);
	}
}
