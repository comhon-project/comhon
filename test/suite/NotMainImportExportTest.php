<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Object\ComhonArray;
use Comhon\Model\ModelArray;
use Comhon\Model\ModelForeign;
use Comhon\Interfacer\XMLInterfacer;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;

/**
 * 
 * test models that are not tagged as main.
 * object linked to this kind of model are not stored in MainObjectCollection
 *
 */
class NotMainImportExportTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
		ModelManager::resetSingleton();
	}
	
	public function testExport()
	{
		$personLocalModel = ModelManager::getInstance()->getInstanceModel('Test\Test\PersonLocal');
		$this->assertFalse($personLocalModel->isMain());
		
		// set object
		$personLocal = $personLocalModel->getObjectInstance();
		$personLocal->setValue('id', 'an_id');
		$personLocal->setValue('firstName', 'john');
		$obj = $personLocal->initValue('anObjectWithIdAndMore');
		$obj->setValue('plop', 'plop');
		$obj->setValue('plop3', 'plop3');
		$personLocal->setValue('aForeignObjectWithIdAndMore', $obj);
		$mother = $personLocal->initValue('mother');
		$mother->setValue('id', 789);
		$mother->setValue('firstName', 'jane');
		
		// export object
		$interfacer = new StdObjectInterfacer();
		$interfacer->setPrivateContext(true);
		$interfacedPerson = $personLocal->export($interfacer);
		$this->assertEquals('{"id":"an_id","firstName":"john","anObjectWithIdAndMore":{"plop":"plop","plop3":"plop3"},"aForeignObjectWithIdAndMore":"plop","mother":789}', $interfacer->toString($interfacedPerson));
		
		// export object array
		$personLocals = new ComhonArray($personLocalModel);
		$personLocals->pushValue($personLocal);
		$interfacedPerson = $personLocals->export($interfacer);
		$this->assertEquals('[{"id":"an_id","firstName":"john","anObjectWithIdAndMore":{"plop":"plop","plop3":"plop3"},"aForeignObjectWithIdAndMore":"plop","mother":789}]', $interfacer->toString($interfacedPerson));
		
		
		// export object foreign
		$personLocalForeignModel = new ModelForeign($personLocalModel);
		$this->assertEquals('an_id', $personLocalForeignModel->export($personLocal, $interfacer));
		
		// export object foreign array
		$personLocalForeignModel = new ModelForeign(new ModelArray($personLocalModel, false, 'elem'));
		$this->assertEquals(['an_id'], $personLocalForeignModel->export($personLocals, $interfacer));
		
		// XML export
		$interfacer = new XMLInterfacer();
		$interfacer->setPrivateContext(true);
		
		// XML export object foreign
		$personLocalForeignModel = new ModelForeign($personLocalModel);
		$this->assertEquals('an_id', $personLocalForeignModel->export($personLocal, $interfacer));
		
		// XML export object foreign array
		$personLocalForeignModel = new ModelForeign(new ModelArray($personLocalModel, false, 'elem'));
		$this->assertEquals('<root><elem>an_id</elem></root>', $interfacer->toString($personLocalForeignModel->export($personLocals, $interfacer)));
	}
	
	
	public function testImport()
	{
		$personLocalModel = ModelManager::getInstance()->getInstanceModel('Test\Test\PersonLocal');
		$interfacer = new StdObjectInterfacer();
		$interfacer->setPrivateContext(true);
		
		// import object
		$personLocal = $interfacer->import(json_decode('{"id":"an_id","firstName":"john","anObjectWithIdAndMore":{"plop":"plopplop","plop3":"plop3"},"aForeignObjectWithIdAndMore":"plopplop","mother":789}'), $personLocalModel);
		
		$this->assertSame($personLocal->getValue('anObjectWithIdAndMore'), $personLocal->getValue('aForeignObjectWithIdAndMore'));
		
		// exporte imported object to verify if they are same
		$interfacedPerson = $personLocal->export($interfacer);
		$interfacedMother = $personLocal->getValue('mother')->export($interfacer);
		$this->assertEquals('{"id":"an_id","firstName":"john","mother":789,"anObjectWithIdAndMore":{"plop":"plopplop","plop3":"plop3"},"aForeignObjectWithIdAndMore":"plopplop"}', $interfacer->toString($interfacedPerson));
		
		// mother should contain 'firstName' value (take mother instanced in testExport() )
		$this->assertEquals('{"id":789,"firstName":"jane"}', $interfacer->toString($interfacedMother));
		
		// import object array
		$modelArray = new ModelArray($personLocalModel, false, 'elem');
		$personLocals = $modelArray->import(
			json_decode('
				[
					{"id":"an_id","firstName":"john","anObjectWithIdAndMore":{"plop":"plopplop2","plop3":"plop3"},"aForeignObjectWithIdAndMore":"plopplop2","mother":789},
					{"id":"an_id2","firstName":"john","anObjectWithIdAndMore":{"plop":"plopplop2","plop3":"plop3"},"aForeignObjectWithIdAndMore":"plopplop2","mother":789}
				]'
			), $interfacer
		);
		
		$this->assertSame($personLocals->getValue(0)->getValue('anObjectWithIdAndMore'), $personLocals->getValue(0)->getValue('aForeignObjectWithIdAndMore'));
		$this->assertSame($personLocals->getValue(1)->getValue('anObjectWithIdAndMore'), $personLocals->getValue(1)->getValue('aForeignObjectWithIdAndMore'));
		
		$this->assertNotSame($personLocals->getValue(0)->getValue('anObjectWithIdAndMore'), $personLocals->getValue(1)->getValue('anObjectWithIdAndMore'));
		$this->assertEquals($personLocals->getValue(0)->getValue('anObjectWithIdAndMore'), $personLocals->getValue(1)->getValue('anObjectWithIdAndMore'));
		
		
		// import object foreign
		$personLocalForeignModel = new ModelForeign($personLocalModel);
		$personLocal = $personLocalForeignModel->import('a_new_id', $interfacer);
		$this->assertEquals('a_new_id', $personLocal->getId());
		
		// import object foreign array
		$personLocalForeignModel = new ModelForeign(new ModelArray($personLocalModel, false, 'elem'));
		$personLocals = $personLocalForeignModel->import(['a_new_id', 'a_new_id2'], $interfacer);
		$this->assertEquals(['a_new_id', 'a_new_id2'], $personLocalForeignModel->export($personLocals, $interfacer));
		
		// XML import
		$interfacer = new XMLInterfacer();
		$interfacer->setPrivateContext(true);
		
		// XML import object foreign
		$personLocalForeignModel = new ModelForeign($personLocalModel);
		$personLocal = $personLocalForeignModel->import('a_new_id', $interfacer);
		$this->assertEquals('a_new_id', $personLocal->getId());
		
		// XML import object foreign array
		$personLocalForeignModel = new ModelForeign(new ModelArray($personLocalModel, false, 'elem'));
		$xml = simplexml_load_string('<root><elem>a_new_id</elem><elem>a_new_id2</elem></root>');
		$personLocals = $personLocalForeignModel->import($xml, $interfacer);
		$this->assertEquals('<root><elem>a_new_id</elem><elem>a_new_id2</elem></root>', $interfacer->toString($personLocalForeignModel->export($personLocals, $interfacer)));
	}
	
	
}
