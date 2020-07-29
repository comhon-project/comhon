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
	}
	
	
	public function testValidateDepends()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->getObjectInstance();
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
		$obj->validate();
		
		$obj->unsetValue('baseValue');
		
		$this->expectException(DependsValuesException::class);
		$this->expectExceptionMessage("property value 'depends' can't be set without property value 'baseValue'");
		$obj->validate();
	}
	
	public function testImportSuccessful()
	{
		$interfacer = new AssocArrayInterfacer();
		
		// object is not validated
		$interfacer->setValidate(false);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa', 'value' => 'my_value', 'depends' => 'my_value'], $interfacer);
		$this->assertEquals('my_value', $obj->getValue('value'));
		$this->assertEquals('my_value', $obj->getValue('depends'));
		
		// object is validated
		$interfacer->setValidate(true);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa', 'value' => 'my_value', 'baseValue' => 'my_value', 'depends' => 'my_value'], $interfacer);
		$this->assertEquals('my_value', $obj->getValue('depends'));
	}
	
	public function testImportFailureRoot()
	{
		$interfacer = new AssocArrayInterfacer();
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
		$interfacer->setValidate(true);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa', 'value' => 'my_value', 'baseValue' => 'my_value', 'depends' => 'my_value'], $interfacer);
		$this->assertTrue($obj->isValid());
		
		$this->assertEquals(
			'{"value":"my_value","valueRequired":"aaaa","baseValue":"my_value","depends":"my_value"}',
			$interfacer->toString($obj->export($interfacer))
		);
		
		$obj->unsetValue('value');
		$this->assertFalse($obj->isValid());
		$interfacer->setValidate(false);
		$this->assertEquals(
			'{"valueRequired":"aaaa","baseValue":"my_value","depends":"my_value"}',
			$interfacer->toString($obj->export($interfacer))
		);
	}
	
	public function testExportFailure() {
		
		$interfacer = new AssocArrayInterfacer();
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
