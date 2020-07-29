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
	
	public function testUnsetValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$object = $model->getObjectInstance(false);
		$object->setValue('valueRequired', 'aaaa');
		$object->validate();
		$this->assertTrue($object->isValid());
		
		$object->unsetValue('valueRequired');
		$this->assertFalse($object->isValid());
		
		$this->expectException(MissingRequiredValueException::class);
		$this->expectExceptionMessage("missing required value 'valueRequired' on comhon object with model 'Test\Validate'");
		$object->validate();
	}
	
	public function testSetNullValue()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$object = $model->getObjectInstance();
		$object->setValue('valueRequired', null); // a required property may be null
		$this->assertNull($object->getValue('valueRequired'));
		$object->validate();
	}
	
	public function testImportSuccessful()
	{
		$interfacer = new AssocArrayInterfacer();
		
		// object is not validated so required values of object are NOT required
		$interfacer->setValidate(false);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['value' => 'aaaa', 'valueComplex' => ['valueRequired' => 'aaaa']], $interfacer);
		$this->assertEquals('aaaa', $obj->getValue('value'));
		$this->assertTrue($obj->hasValue('valueComplex'));
		$this->assertEquals('aaaa', $obj->getValue('valueComplex')->getValue('valueRequired'));
		
		// object is validated so required values of object are required
		$interfacer->setValidate(true);
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa'], $interfacer);
		$this->assertEquals('aaaa', $obj->getValue('valueRequired'));
	}
	
	public function testImportFailureRoot()
	{
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		
		$thrown = false;
		try {
			$model->import(['value' => 'aaaa', 'valueComplex' => ['valueRequired' => 'aaaa']], $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.');
			$this->assertEquals(get_class($e->getOriginalException()), MissingRequiredValueException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::MISSING_REQUIRED_VALUE_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "missing required value 'valueRequired' on comhon object with model 'Test\Validate'");
		}
		$this->assertTrue($thrown);
	}
	
	public function testImportFailureDeep()
	{
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		
		$thrown = false;
		try {
			$model->import(['value' => 'aaaa', 'valueRequired' => 'aaaa', 'valueComplex' => ['value' => 'aaaa']], $interfacer);
		} catch (ImportException $e) {
			$thrown = true;
			$this->assertEquals($e->getStringifiedProperties(), '.valueComplex');
			$this->assertEquals(get_class($e->getOriginalException()), MissingRequiredValueException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), ConstantException::MISSING_REQUIRED_VALUE_EXCEPTION);
			$this->assertEquals($e->getOriginalException()->getMessage(), "missing required value 'valueRequired' on comhon object with model 'Test\Validate\localRestricted'");
		}
		$this->assertTrue($thrown);
	}
	
	public function testExportSuccessful() {
		
		$interfacer = new AssocArrayInterfacer();
		$interfacer->setValidate(true);
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->import(['valueRequired' => 'aaaa', 'valueComplex' => ['valueRequired' => 'aaaa']], $interfacer);
		
		$this->assertEquals(
			'{"valueRequired":"aaaa","valueComplex":{"valueRequired":"aaaa"}}',
			$interfacer->toString($obj->export($interfacer))
		);
		
		$obj->getValue('valueComplex')->unsetValue('valueRequired');
		$this->assertTrue($obj->isValid());
		$this->assertFalse($obj->getValue('valueComplex')->isValid());
		
		$interfacer->setValidate(false);
		$this->assertEquals(
			'{"valueRequired":"aaaa","valueComplex":[]}',
			$interfacer->toString($obj->export($interfacer))
		);
	}
	
	
	/**
	 * @dataProvider data
	 */
	public function testExportFailure($rootProperty, $leafProperty, $propertyString, $modelName) {
		
		$interfacer = new AssocArrayInterfacer();
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
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
			$this->assertEquals($e->getOriginalException()->getMessage(), "missing required value 'valueRequired' on comhon object with model '$modelName'");
		}
		$this->assertTrue($thrown);
	}
	
	public function data() {
		return [
			[
				'value',
				'valueRequired',
				'.',
				'Test\Validate'
			],
			[
				'valueRequired',
				'value',
				'.valueComplex',
				'Test\Validate\localRestricted'
			]
		];
	}
	
	public function testCast() {
		$model = ModelManager::getInstance()->getInstanceModel('Test\Validate');
		$obj = $model->getObjectInstance(false);
		
		$obj2 = $obj->initValue('valueComplex', false);
		$obj2->setValue('valueRequired', 'aaaa');
		$obj2->cast(ModelManager::getInstance()->getInstanceModel('Test\Validate\localRestrictedExtended'));
		
		$obj2 = $obj->initValue('valueComplex', false);
		$obj2->setValue('valueRequired', 'aaaa');
		$obj2->validate();
		$obj2->cast(ModelManager::getInstance()->getInstanceModel('Test\Validate\localRestrictedExtended'));
		
		$this->expectException(MissingRequiredValueException::class);
		$this->expectExceptionMessage("missing required value 'valueRequiredExtended' on comhon object with model 'Test\Validate\localRestrictedExtended'");
		$obj2->validate();
	}

}
