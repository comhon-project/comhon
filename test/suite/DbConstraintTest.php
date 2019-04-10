<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Exception\Serialization\UniqueException;
use Comhon\Database\DatabaseHandler;
use Comhon\Exception\Serialization\ForeignValueException;
use Comhon\Exception\Serialization\NotNullException;

class DbConstraintTest extends TestCase
{
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
		$obj2->save();
	}
	
	public function testForeign()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		
		$objForeign = $model->getObjectInstance();
		$objForeign->setId(2236);
		
		$obj = $model->getObjectInstance();
		$obj->setValue('unique_name', 'haha');
		$obj->setValue('foreign_constraint', $objForeign);
		
		$this->expectException(ForeignValueException::class);
		$obj->save();
	}
	
	public function testForeignComposite()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		$modelTest = ModelManager::getInstance()->getInstanceModel('Test\TestDb');
		
		$objForeign = $modelTest->getObjectInstance();
		$objForeign->setId('[123, "does not exist"]');
		
		$obj = $model->getObjectInstance();
		$obj->setValue('unique_name', 'foo');
		$obj->setValue('foreign_constraint_composite', $objForeign);
		
		$this->expectException(ForeignValueException::class);
		$obj->save();
	}
	
	public function testNotNullSetToNull()
	{
		$hasThrownEx = false;
		 $model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		 
		 $obj = $model->getObjectInstance();
		 $obj->setValue('unique_name', null);
		 
		 $this->expectException(NotNullException::class);
		 $obj->save();
	}
	
	public function testNotNullNotSet()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\DbConstraint');
		
		$objForeign = $model->getObjectInstance();
		$objForeign->setId(1);
		
		$obj = $model->getObjectInstance();
		$obj->setValue('foreign_constraint', $objForeign);
		
		$this->expectException(NotNullException::class);
		$obj->save();
	}
}
