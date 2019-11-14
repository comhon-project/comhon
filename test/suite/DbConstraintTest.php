<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Serialization\UniqueException;
use Comhon\Database\DatabaseHandler;
use Comhon\Exception\Serialization\ForeignValueException;
use Comhon\Exception\Serialization\NotNullException;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;

class DbConstraintTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	/**
	 * @beforeClass
	 * @afterClass
	 */
	public static function resetDbConstraintTable() {
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		$table = $model->getSerialization()->getSettings();
		$dbHandler = DatabaseHandler::getInstanceWithDataBaseId(
			$table->getValue('database')->getId()
		);
		$dbHandler->execute("DELETE FROM {$table->getValue('name')};");
	}
	
	public function testUniqueness()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		
		$obj = $model->getObjectInstance();
		$obj->setValue('unique_name', 'hehe');
		$obj->save();
		
		$obj2 = $model->getObjectInstance();
		$obj2->setValue('unique_name', 'hehe');
		
		$this->expectException(UniqueException::class);
		$this->expectExceptionMessage("value hehe of property unique_name for model 'Test\DbConstraint' already exists and must be unique");
		$obj2->save();
	}
	
	public function testUniquenessComposite()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		
		$obj = $model->getObjectInstance();
		$obj->setValue('unique_name', 'lalala');
		$obj->setValue('unique_one', 1);
		$obj->setValue('unique_two', '50');
		$obj->save();
		
		$obj2 = $model->getObjectInstance();
		$obj2->setValue('unique_name', 'hehehe');
		$obj2->setValue('unique_one', 1);
		$obj2->setValue('unique_two', '50');
		
		$this->expectException(UniqueException::class);
		
		$dbHandler = DatabaseHandler::getInstanceWithDataBaseId(
			$obj2->getModel()->getSerialization()->getSettings()->getValue('database')->getId()
		);
		if ($dbHandler->getDBMS() === DatabaseHandler::MYSQL) {
			$this->expectExceptionMessage("value 1 of property unique_one for model 'Test\DbConstraint' already exists and must be unique");
		} else {
			$this->expectExceptionMessage("values 1, 50 of properties unique_one, unique_two for model 'Test\DbConstraint' already exists and must be unique");
		}
		$obj2->save();
	}
	
	public function testUniquenessForeignComposite()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		$modelTest = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
		
		$objForeign = $modelTest->getObjectInstance();
		$objForeign->setId('[1, "50"]');
		
		$obj = $model->getObjectInstance();
		$obj->setValue('unique_name', 'lalalala');
		$obj->setValue('foreign_constraint_composite', $objForeign);
		$obj->save();
		
		$obj2 = $model->getObjectInstance();
		$obj2->setValue('unique_name', 'hehehe');
		$obj2->setValue('foreign_constraint_composite', $objForeign);
		
		$this->expectException(UniqueException::class);
		$this->expectExceptionMessage("value [1,\"50\"] of property foreign_constraint_composite for model 'Test\DbConstraint' already exists and must be unique");
		$obj2->save();
	}
	
	public function testForeign()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		
		$objForeign = $model->getObjectInstance();
		$objForeign->setId(2236);
		
		$obj = $model->getObjectInstance();
		$obj->setValue('unique_name', 'haha');
		$obj->setValue('foreign_constraint', $objForeign);
		
		$this->expectException(ForeignValueException::class);
		$this->expectExceptionMessage("reference 2236 of foreign property 'foreign_constraint' for model 'Test\DbConstraint' doesn't exists");
		$obj->save();
	}
	
	public function testForeignComposite()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		$modelTest = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
		
		$objForeign = $modelTest->getObjectInstance();
		$objForeign->setId('[123, "does not exist"]');
		
		$obj = $model->getObjectInstance();
		$obj->setValue('unique_name', 'foo');
		$obj->setValue('foreign_constraint_composite', $objForeign);
		
		$this->expectException(ForeignValueException::class);
		$this->expectExceptionMessage("reference [123,\"does not exist\"] of foreign property 'foreign_constraint_composite' for model 'Test\DbConstraint' doesn't exists");
		$obj->save();
	}
	
	public function testNotNullSetToNull()
	{
		 $model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		 
		 $obj = $model->getObjectInstance();
		 $obj->setValue('unique_name', null);
		 
		 $this->expectException(NotNullException::class);
		 $this->expectExceptionMessage("property 'unique_name' of model 'Test\DbConstraint' cannot be serialized with null value");
		 $obj->save();
	}
	
	public function testNotNullNotSet()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		
		$objForeign = $model->getObjectInstance();
		$objForeign->setId(1);
		
		$obj = $model->getObjectInstance();
		$obj->setValue('foreign_constraint', $objForeign);
		
		$this->expectException(NotNullException::class);
		$this->expectExceptionMessage("property 'unique_name' of model 'Test\DbConstraint' cannot be serialized with null value");
		$obj->save();
	}
}
