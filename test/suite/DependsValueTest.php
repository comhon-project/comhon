<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Interfacer\ExportException;
use Comhon\Exception\Object\DependsValuesException;

class DependsValueTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testDependsProperty()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$this->assertEquals(
			['baseValue', 'value'],
			$model->getProperty('depends')->getDependencies()
		);
		$this->assertEquals(
			['baseValue'],
			$model->getProperty('dependsConflict')->getDependencies()
		);
		$this->assertEquals(
			[],
			$model->getProperty('depends')->getConflicts()
		);
	}
	
	
	public function testDependsSet()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->getObjectInstance(false);
		$obj->setValue('valueRequired', 'my_value');
		$obj->setIsLoaded(true);
		$obj->setValue('value', 'my_value');
		$obj->setValue('baseValue', 'my_value');
		$obj->setValue('depends', 'my_value');
		
		$obj->setIsLoaded(false);
		$obj->unsetValue('baseValue');
		$obj->unsetValue('depends');
		$obj->setIsLoaded(true);
		
		$this->expectException(DependsValuesException::class);
		$this->expectExceptionMessage('property value \'depends\' can\'t be set without property value \'baseValue\'');
		$obj->setValue('depends', 'my_value');
	}
	
	public function testValidateDepends()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->getObjectInstance(false);
		$obj->setValue('valueRequired', 'my_value');
		$obj->setValue('value', 'my_value');
		$obj->setValue('baseValue', 'my_value');
		$obj->setValue('depends', 'my_value');
		$obj->validate();
		
		$obj->unsetValue('value');
		$obj->unsetValue('depends');
		$obj->setValue('depends', 'my_value');
		
		$this->expectException(DependsValuesException::class);
		$this->expectExceptionMessage('property value \'depends\' can\'t be set without property value \'value\'');
		$obj->validate();
	}
	
	public function testDependsUnset()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->getObjectInstance(false);
		$obj->setValue('valueRequired', 'my_value');
		$obj->setIsLoaded(true);
		$obj->setValue('value', 'my_value');
		$obj->setValue('baseValue', 'my_value');
		$obj->setValue('depends', 'my_value');
		
		$this->expectException(DependsValuesException::class);
		$this->expectExceptionMessage('property value \'baseValue\' can\'t be unset when property value \'depends\' is set');
		$obj->unsetValue('baseValue');
	}
	
	public function testImportSuccessful()
	{
		$interfacer = new AssocArrayInterfacer();
		
		// root object NOT flaged as loaded so root object is not validated
		$interfacer->setFlagObjectAsLoaded(false);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa', 'value' => 'my_value', 'depends' => 'my_value'], $interfacer);
		$this->assertFalse($obj->isLoaded());
		$this->assertEquals('my_value', $obj->getValue('value'));
		$this->assertEquals('my_value', $obj->getValue('depends'));
		
		// root object flaged as loaded so root object is validated
		$interfacer->setFlagObjectAsLoaded(true);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa', 'value' => 'my_value', 'baseValue' => 'my_value', 'depends' => 'my_value'], $interfacer);
		$this->assertTrue($obj->isLoaded());
		$this->assertEquals('my_value', $obj->getValue('depends'));
	}
	
	public function testImportFailureRoot()
	{
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(true);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		
		$thrown = false;
		try {
			$obj = $model->import(['valueRequired' => 'aaaa', 'value' => 'my_value', 'depends' => 'my_value'], $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), DependsValuesException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::DEPENDS_VALUES_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "property value 'depends' can't be set without property value 'baseValue'");
		}
		$this->assertTrue($thrown);
	}
	
	public function testExportSuccessful() {
		
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa', 'value' => 'my_value', 'baseValue' => 'my_value', 'depends' => 'my_value'], $interfacer);
		
		$this->assertEquals(
			'{"value":"my_value","valueRequired":"aaaa","baseValue":"my_value","depends":"my_value"}',
			$interfacer->toString($obj->export($interfacer))
		);
	}
	
	public function testExportFailure() {
		
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->getObjectInstance(false);
		$obj->setValue('valueRequired', 'aaaa');
		$obj->setValue('depends', 'aaaa');
		
		$thrown = false;
		try {
			$obj->export($interfacer);
		} catch (ExportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), DependsValuesException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::DEPENDS_VALUES_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "property value 'depends' can't be set without property value 'baseValue'");
		}
		$this->assertTrue($thrown);
	}
	
}
