<?php

use PHPUnit\Framework\TestCase;
use Comhon\Database\SelectQuery;
use Comhon\Database\DatabaseHandler;
use Comhon\Request\ComplexLoadRequest;

class CountRequestTest extends TestCase
{
	
	public function testCountSelectQueryMySql()
	{
		// no group
		$select = new SelectQuery('person');
		$select->addOrder('id')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('1')->count($select);
		$this->assertSame(9, $count);
		
		// group by id -> each group have count one
		$select = new SelectQuery('person');
		$select->addGroup('id')->addOrder('id')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('1')->count($select);
		$this->assertSame(9, $count);
		
		// group by last_name -> groups have differents counts (same last_name)
		$select = new SelectQuery('person');
		$select->addGroup('last_name')->addOrder('last_name')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('1')->count($select);
		$this->assertSame(9, $count);
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
		
		// group by last_name -> groups have differents counts (same last_name)
		$select = new SelectQuery('person');
		$select->addGroup('"last_name"')->addOrder('"last_name"')->limit(2);
		$count = DatabaseHandler::getInstanceWithDataBaseId('2')->count($select);
		$this->assertSame(9, $count);
	}
	
	public function testCountServiceRequest()
	{
		$params = new \stdClass();
		$params->model = 'Test\Person';
		$count = ComplexLoadRequest::buildObjectLoadRequest($params)->count();
		
		$this->assertSame(9, $count);
	}
	
	public function testCountWithLimitAndFilterServiceRequest()
	{
		$params = new \stdClass();
		$params->model = 'Test\Person';
		$params->filter = new \stdClass();
		$params->filter->model = 'Test\Person';
		$params->filter->property = 'lastName';
		$params->filter->operator = '=';
		$params->filter->value = 'Dupond';
		$params->limit = 1;
		$order = new \stdClass();
		$order->property = 'firstName';
		$params->order = [$order];
		
		$objects = ComplexLoadRequest::buildObjectLoadRequest($params)->execute();
		$this->assertCount(1, $objects->getValues());
		
		$count = ComplexLoadRequest::buildObjectLoadRequest($params)->count();
		$this->assertSame(2, $count);
	}
}
