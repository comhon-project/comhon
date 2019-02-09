<?php
use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Interfacer\AssocArrayInterfacer;
use Comhon\Interfacer\XMLInterfacer;
use Comhon\Exception\Interfacer\ImportException;
use Comhon\Exception\UnexpectedValueTypeException;

class ImportExportTest extends TestCase
{
	
	public function testThrowExceptionImport()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\Test');
		$test = $model->getObjectInstance();
		$testJson = '{"objectContainer":{"person":{"recursiveLocal":{"firstName":true}}}}';
		
		$stdInterfacer = new StdObjectInterfacer();
		
		$this->expectException(ImportException::class);
		$test->fill(json_decode($testJson), $stdInterfacer);
	}
	
	/**
	 * @depends testThrowExceptionImport
	 */
	public function testThrownExceptionImport()
	{
		$hasThrownEx = false;
		$model = ModelManager::getInstance()->getInstanceModel('Test\Test');
		$test = $model->getObjectInstance();
		$testJson = '{"objectContainer":{"person":{"recursiveLocal":{"firstName":true}}}}';
		
		$stdInterfacer = new StdObjectInterfacer();
		
		try {
			$test->fill(json_decode($testJson), $stdInterfacer);
		} catch (ImportException $e) {
			$this->assertEquals($e->getStringifiedProperties(), '.objectContainer.person.recursiveLocal.firstName');
			$this->assertEquals(get_class($e->getOriginalException()), UnexpectedValueTypeException::class);
			$this->assertEquals($e->getOriginalException()->getCode(), 203);
			$this->assertEquals($e->getOriginalException()->getMessage(), 'value must be a string, boolean \'true\' given');
		}
		
	}
	

}
