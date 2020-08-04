<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Database\DatabaseHandler;
use Comhon\Serialization\SqlTable;
use Comhon\Object\UniqueObject;
use Comhon\Object\ComhonDateTime;
use Comhon\Object\ComhonObject;
use Comhon\Object\Collection\MainObjectCollection;
use Comhon\Serialization\File\XmlFile;
use Comhon\Exception\Serialization\SerializationException;

class UpdateTest extends TestCase
{
	private static $id;
	
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Person\Man');
		$obj = $model->getObjectInstance(false);
		$obj->setValue('firstName', 'john');
		$obj->setValue('lastName', 'doe');
		$obj->setValue('birthDate', new ComhonDateTime('2010-12-12'));
		$father = new ComhonObject('Test\Person\Man');
		$father->setId(1234, false);
		$obj->setValue('father', $father);
		$mother = new ComhonObject('Test\Person\Woman');
		$mother->setId(2345, false);
		$obj->setValue('mother', $mother);
		$obj->save();
		
		self::$id = $obj->getId();
	}
	
	public static function  tearDownAfterClass() {
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person')->getObjectInstance(false);
		$obj->setId(self::$id);
		$obj->delete();
	}
	
	/**
	 * 
	 * @param UniqueObject $object
	 * @return string
	 */
	private function getDBMS(UniqueObject $object)
	{
		$table = $object->getModel()->getSerialization()->getSettings();
		return $table->getValue('database')->getValue('DBMS');
	}
	
	/**
	 * 
	 * @param UniqueObject $object
	 * @return \Comhon\Database\DatabaseHandler|NULL
	 */
	private function getDbHandler(UniqueObject $object)
	{
		$table = $object->getModel()->getSerialization()->getSettings();
		return DatabaseHandler::getInstanceWithDataBaseId(
			$table->getValue('database')->getId()
		);
	}
	
	/**
	 * get query according DBMS
	 * 
	 * @param string $query must be a query with mysql escape character (`). 
	 * @param string $DBMS
	 */
	private function getQuery($query, $DBMS) {
		if ($DBMS == 'pgsql') {
			$query = str_replace('`person`', '"public"."person"', $query);
			$query = str_replace('`', '"', $query);
		}
		
		return $query;
	}
	
	/**
	 * execute update when operation is not specified (object already exists)
	 * all values must be in query even if they are not updated and if they are not set (null value is added when not set)
	 */
	public function testImplicitUpdate()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->loadObject(self::$id, null, true);
		$this->assertEquals(
			'{"id":'.self::$id.',"firstName":"john","lastName":"doe","birthDate":"2010-12-12T00:00:00+00:00","birthPlace":null,"bestFriend":null,"father":1234,"mother":2345}', 
			str_replace(["\n", ' '], ['', ''], $obj->__toString())
		);
		$this->getDbHandler($obj)->clearPreparedQueries();
		
		$obj->unsetValue('birthDate', false);
		$obj->save();
		$this->assertNotEmpty($this->getDbHandler($obj)->getPreparedQueries());
		$expected = $this->getQuery(
			"UPDATE `person` SET `first_name`= ?, `last_name`= ?, `birth_place`= ?, `best_friend`= ?, `father_id`= ?, `mother_id`= ?, `sex`= ?, `birth_date`= ? WHERE `id`= ?;",
			$this->getDBMS($obj)
		);
		$this->assertEquals($expected, current($this->getDbHandler($obj)->getPreparedQueries()));
		
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->loadObject(self::$id, null, true);
		$this->assertEquals(
			'{"id":'.self::$id.',"firstName":"john","lastName":"doe","birthDate":null,"birthPlace":null,"bestFriend":null,"father":1234,"mother":2345}',
			str_replace(["\n", ' '], ['', ''], $obj->__toString())
		);
	}
	
	
	/**
	 * all values must be in query even if they are not updated and if they are not set (null value is added when not set)
	 */
	public function testUpdate()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->loadObject(self::$id, null, true);
		$this->getDbHandler($obj)->clearPreparedQueries();
		
		$obj->unsetValue('birthDate', false);
		$obj->save(SqlTable::UPDATE);
		$this->assertNotEmpty($this->getDbHandler($obj)->getPreparedQueries());
		$expected = $this->getQuery(
			"UPDATE `person` SET `first_name`= ?, `last_name`= ?, `birth_place`= ?, `best_friend`= ?, `father_id`= ?, `mother_id`= ?, `sex`= ?, `birth_date`= ? WHERE `id`= ?;",
			$this->getDBMS($obj)
		);
		$this->assertEquals($expected, current($this->getDbHandler($obj)->getPreparedQueries()));
	}
	
	/**
	 * only updated values are exported in query
	 */
	public function testPatchWithUpdatedValues()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->loadObject(self::$id, null, true);
		
		$this->getDbHandler($obj)->clearPreparedQueries();
		$obj->flagValueAsUpdated('firstName');
		$obj->unsetValue('mother');
		$obj->unsetValue('father', false);
		
		$obj->save(SqlTable::PATCH);
		$this->assertNotEmpty($this->getDbHandler($obj)->getPreparedQueries());
		
		$expected = $this->getQuery(
			"UPDATE `person` SET `first_name`= ?, `mother_id`= ? WHERE `id`= ?;",
			$this->getDBMS($obj)
		);
		$this->assertEquals($expected, current($this->getDbHandler($obj)->getPreparedQueries()));
		
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->loadObject(self::$id, null, true);
		$obj->unsetValue('birthDate'); // value may be different if testImplicitUpdate() is called or not, and no need to compare this value
		$this->assertEquals(
			'{"id":'.self::$id.',"firstName":"john","lastName":"doe","birthPlace":null,"bestFriend":null,"father":1234,"mother":null}',
			str_replace(["\n", ' '], ['', ''], $obj->__toString())
		);
	}
	
	/**
	 * if object is casted, new object model name must appear in query
	 */
	public function testPatchWithCast()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person')->getObjectInstance(false);
		$obj->setId(self::$id);
		$obj->cast(ModelManager::getInstance()->getInstanceModel('Test\Person\Man'));
		$this->getDbHandler($obj)->clearPreparedQueries();
		
		$obj->save(SqlTable::PATCH);
		$this->assertNotEmpty($this->getDbHandler($obj)->getPreparedQueries());
		
		$expected = $this->getQuery(
			"UPDATE `person` SET `sex`= ? WHERE `id`= ?;",
			$this->getDBMS($obj)
		);
		$this->assertEquals($expected, current($this->getDbHandler($obj)->getPreparedQueries()));
	}
	
	/**
	 * no query executed without updated values and without cast
	 */
	public function testPatchWithoutUpdatedValues()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\Man')->loadObject(self::$id, null, true);
		$this->getDbHandler($obj)->clearPreparedQueries();
		
		$obj->save(SqlTable::PATCH);
		$this->assertEmpty($this->getDbHandler($obj)->getPreparedQueries());
	}
	
	
	
	/**
	 * patch not available for file serialization
	 */
	public function testPatchFile()
	{
		$obj = ModelManager::getInstance()->getInstanceModel('Test\Person\WomanXml')->getObjectInstance();
		
		$this->expectException(SerializationException::class);
		$this->expectExceptionMessage('patch not allowed for file serialization');
		$obj->save(XmlFile::PATCH);
	}
	
}
