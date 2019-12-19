<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\XMLInterfacer;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Exception\Object\MissingRequiredValueException;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\ConstantException;
use Comhon\Exception\Interfacer\ExportException;

class RequiredValueTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testInstanciateObject()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$model->getObjectInstance(false);
		
		$this->expectException(MissingRequiredValueException::class);
		$this->expectExceptionMessage("missing required value 'valueRequired' on loaded comhon object with model 'Test\Required'");
		$model->getObjectInstance();
	}
	
	public function testSetIsLoadedValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$object = $model->getObjectInstance(false);
		$object->setValue('valueRequired', 'aaaa');
		$object->setIsLoaded(true);
		
		$object = $model->getObjectInstance(false);
		$this->expectException(MissingRequiredValueException::class);
		$this->expectExceptionMessage("missing required value 'valueRequired' on loaded comhon object with model 'Test\Required'");
		$object->setIsLoaded(true);
	}
	
	public function testUnsetValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$object = $model->getObjectInstance(false);
		$object->setValue('valueRequired', 'aaaa');
		$object->unsetValue('valueRequired');
		
		$object->setValue('valueRequired', 'aaaa');
		$object->setIsLoaded(true);
		$this->expectException(MissingRequiredValueException::class);
		$this->expectExceptionMessage("impossible to unset required value 'valueRequired' on loaded comhon object with model 'Test\Required'");
		$object->unsetValue('valueRequired');
	}
	
	public function testSetNullValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$object = $model->getObjectInstance(false);
		$object->setValue('valueRequired', null); // a required property may be null
		$this->assertNull($object->getValue('valueRequired'));
	}
	
	public function testImportSuccessful()
	{
		$interfacer = new AssocArrayInterfacer();
		
		// root object NOT flaged as loaded so required values of root object are NOT required
		$interfacer->setFlagObjectAsLoaded(false);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$obj = $model->import(['value' => 'aaaa', 'valueComplex' => ['valueRequired' => 'aaaa']], $interfacer);
		$this->assertFalse($obj->isLoaded());
		$this->assertEquals('aaaa', $obj->getValue('value'));
		$this->assertTrue($obj->hasValue('valueComplex'));
		$this->assertEquals('aaaa', $obj->getValue('valueComplex')->getValue('valueRequired'));
		
		// root object flaged as loaded so required values of root object are required
		$interfacer->setFlagObjectAsLoaded(true);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$obj = $model->import(['valueRequired' => 'aaaa'], $interfacer);
		$this->assertTrue($obj->isLoaded());
		$this->assertEquals('aaaa', $obj->getValue('valueRequired'));
	}
	
	public function testImportFailureRoot()
	{
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(true);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		
		$thrown = false;
		try {
			$model->import(['value' => 'aaaa', 'valueComplex' => ['valueRequired' => 'aaaa']], $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), MissingRequiredValueException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::MISSING_REQUIRED_VALUE_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "missing required value 'valueRequired' on loaded comhon object with model 'Test\Required'");
		}
		$this->assertTrue($thrown);
	}
	
	public function testImportFailureDeep()
	{
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		
		$thrown = false;
		try {
			$model->import(['value' => 'aaaa', 'valueComplex' => ['value' => 'aaaa']], $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.valueComplex');
			$this->assertEquals(get_class($e->getOriginalException()), MissingRequiredValueException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::MISSING_REQUIRED_VALUE_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "missing required value 'valueRequired' on loaded comhon object with model 'Test\Required\localRestricted'");
		}
		$this->assertTrue($thrown);
	}
	
	public function testExportSuccessful() {
		
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$obj = $model->import(['valueRequired' => 'aaaa', 'valueComplex' => ['valueRequired' => 'aaaa']], $interfacer);
		
		$this->assertEquals(
			'{"valueRequired":"aaaa","valueComplex":{"valueRequired":"aaaa"}}',
			$interfacer->toString($obj->export($interfacer))
		);
	}
	
	
	/**
	 * @dataProvider data
	 */
	public function testExportFailure($rootProperty, $leafProperty, $propertyString, $modelName) {
		
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setFlagObjectAsLoaded(false);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$obj = $model->getObjectInstance(false);
		$obj->setValue($rootProperty, 'aaaa');
		$obj2 = $obj->initValue('valueComplex', false);
		$obj2->setValue($leafProperty, 'aaaa');
		
		$thrown = false;
		try {
			$obj->export($interfacer);
		} catch (ExportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), $propertyString);
			$this->assertEquals(get_class($e->getOriginalException()), MissingRequiredValueException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::MISSING_REQUIRED_VALUE_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "missing required value 'valueRequired' on loaded comhon object with model '$modelName'");
		}
		$this->assertTrue($thrown);
	}
	
	public function data() {
		return [
			[
				'value',
				'valueRequired',
				'.',
				'Test\Required'
			],
			[
				'valueRequired',
				'value',
				'.valueComplex',
				'Test\Required\localRestricted'
			]
		];
	}
	
	public function testCast() {
		$model = ModelManager::getInstance()->getInstanceModel('Test\Required');
		$obj = $model->getObjectInstance(false);
		
		$obj2 = $obj->initValue('valueComplex', false);
		$obj2->setValue('valueRequired', 'aaaa');
		$obj2->cast(ModelManager::getInstance()->getInstanceModel('Test\Required\localRestrictedExtended'));
		
		$obj2 = $obj->initValue('valueComplex', false);
		$obj2->setValue('valueRequired', 'aaaa');
		$obj2->setIsLoaded(true);
		
		$this->expectException(MissingRequiredValueException::class);
		$this->expectExceptionMessage("missing required value 'valueRequiredExtended' on loaded comhon object with model 'Test\Required\localRestrictedExtended'");
		$obj2->cast(ModelManager::getInstance()->getInstanceModel('Test\Required\localRestrictedExtended'));
	}

}
