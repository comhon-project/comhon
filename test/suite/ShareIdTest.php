<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Object\ComhonObject;
use Comhon\Interfacer\StdObjectInterfacer;
use Comhon\Object\Collection\MainObjectCollection;

class ShareIdTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testShareIdWithoutSerialization()
	{
		$modelParentOne = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParent\ParentOne');
		$modelChildOne = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParent\ParentOne\Child');
		$modelChildTwo = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParent\ParentTwo\Child');
		$modelParentTwo = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParent\ParentTwo');
		$modelParentThree = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParent\ParentThree');
		$modelChildThree = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParent\ParentThree\Child');
		$modelGrandParent = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParent');
		
		$this->assertSame($modelChildOne->getSharedIdModel(), $modelGrandParent);
		$this->assertSame($modelParentOne->getSharedIdModel(), $modelGrandParent);
		$this->assertSame($modelChildTwo->getSharedIdModel(), $modelGrandParent);
		$this->assertSame($modelParentTwo->getSharedIdModel(), $modelGrandParent);
		$this->assertSame($modelChildThree->getSharedIdModel(), $modelGrandParent);
		$this->assertNull($modelParentThree->getSharedIdModel());
		$this->assertNull($modelGrandParent->getSharedIdModel());
	}
	
	public function testShareIdWithSerialization()
	{
		$modelParentOne = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParentSerialized\ParentOne');
		$modelChildOne = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParentSerialized\ParentOne\Child');
		$modelChildTwo = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParentSerialized\ParentTwo\Child');
		$modelParentTwo = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParentSerialized\ParentTwo');
		$modelParentThree = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParentSerialized\ParentThree');
		$modelChildThree = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParentSerialized\ParentThree\Child');
		$modelGrandParent = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\GrandParentSerialized');
		
		$this->assertSame($modelChildOne->getSharedIdModel(), $modelGrandParent);
		$this->assertSame($modelParentOne->getSharedIdModel(), $modelGrandParent);
		$this->assertSame($modelChildTwo->getSharedIdModel(), $modelGrandParent);
		$this->assertNull($modelParentTwo->getSharedIdModel());
		$this->assertSame($modelChildThree->getSharedIdModel(), $modelGrandParent);
		$this->assertNull($modelParentThree->getSharedIdModel());
		$this->assertNull($modelGrandParent->getSharedIdModel());
	}

	/**
	 * 
	 * @dataProvider shareIdImportExportData
	 */
	public function testShareIdImportExport($json)
	{
		$shardId = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId');
		$interfacer = new StdObjectInterfacer();
		$stdObj = json_decode($json);
		$obj = $shardId->import($stdObj, $interfacer);
		
		$this->assertSame($obj->getValue('collection')->getValue(0), $obj->getValue('foreignCollectionTwo')->getValue(0));
		$this->assertSame($obj->getValue('collection')->getValue(1), $obj->getValue('foreignCollectionTwo')->getValue(1));
		$this->assertSame($obj->getValue('collection')->getValue(2), $obj->getValue('foreignCollectionTwo')->getValue(2));
		$this->assertSame($obj->getValue('collection')->getValue(3), $obj->getValue('foreignCollectionTwo')->getValue(3));
		$this->assertSame($obj->getValue('collection')->getValue(4), $obj->getValue('foreignCollectionOne')->getValue(0));
		$this->assertSame($obj->getValue('collection')->getValue(5), $obj->getValue('foreignCollectionOne')->getValue(1));
		
		$this->assertEquals(
			$obj->getValue('collection')->getValue(1)->getId(),
			$obj->getValue('collection')->getValue(5)->getId()
		);
		$this->assertNotSame(
			$obj->getValue('collection')->getValue(1),
			$obj->getValue('collection')->getValue(5)
		);

		$this->assertEquals(json_encode($stdObj), $interfacer->toString($obj->export($interfacer)));
	}
	
	public function shareIdImportExportData()
	{
		return [
			[
				'{
				    "foreignCollectionOne": [
				        "ChildThree",
				        {
				            "id": "ParentOne",
				            "inheritance-": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentThree"
				        }
				    ],
				    "collection": [
				        {
				            "id": "ChildOne",
				            "inheritance-": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentOne\\\\Child"
				        },
				        {
				            "id": "ParentOne",
				            "inheritance-": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentOne"
				        },
				        {
				            "id": "ChildTwo",
				            "inheritance-": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentTwo\\\\Child"
				        },
				        {
				            "id": "ParentTwo",
				            "inheritance-": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentTwo"
				        },
				        {
				            "id": "ChildThree",
				            "inheritance-": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentThree\\\\Child"
				        },
				        {
				            "id": "ParentOne",
				            "inheritance-": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentThree"
				        }
				    ],
				    "foreignCollectionTwo": [
				        "ChildOne",
				        "ParentOne",
				        "ChildTwo",
				        "ParentTwo"
				    ]
				}'
			]
		];
	}
	
	public function testShareIdExportMain()
	{
		$man = new ComhonObject('Test\Person\Man');
		$man->setId(1);
		$woman = new ComhonObject('Test\Person\Woman');
		$woman->setId(2);
		$man->initValue('children');
		$man->getValue('children')->pushValue($woman);
		$interfacer = new StdObjectInterfacer();
		
		// even if woman model share id with person, 
		// the exported children woman has its inheritance specified
		// because for main model it may be usefull to keep the inheritance information
		$this->assertEquals(
			'{"id":1,"children":[{"id":2,"inheritance-":"Test\\\\Person\\\\Woman"}]}', 
			$interfacer->toString($interfacer->export($man))
		);
	}
	
	public function testMainObjectCollection()
	{
		$modelMain = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\MainNotMain\Main');
		$modelNotMain = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\MainNotMain\NotMain');
		$modelNotMainMain = ModelManager::getInstance()->getInstanceModel('Test\Extends\ShareId\MainNotMain\NotMain\Main');
		
		$obj = new ComhonObject('Test\Extends\ShareId\MainNotMain');
		$obj->setId(1);
		$this->assertTrue($obj->getModel()->isMain());
		$this->assertSame($obj, MainObjectCollection::getInstance()->getObject(1, 'Test\Extends\ShareId\MainNotMain', false));
		
		$obj->cast($modelMain);
		$this->assertTrue($obj->getModel()->isMain());
		$this->assertSame($obj, MainObjectCollection::getInstance()->getObject(1, 'Test\Extends\ShareId\MainNotMain\Main', false));
		
		$id = 2;
		$obj2 = new ComhonObject('Test\Extends\ShareId\MainNotMain');
		$obj2->setId($id);
		$this->assertSame($obj2, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain', false));
		
		$obj2->cast($modelNotMain);
		$this->assertFalse($obj2->getModel()->isMain());
		$this->assertFalse(MainObjectCollection::getInstance()->hasObject($id, 'Test\Extends\ShareId\MainNotMain', false));
		$this->assertFalse(MainObjectCollection::getInstance()->hasObject($id, 'Test\Extends\ShareId\MainNotMain\NotMain', false));
		
		$obj2->cast($modelNotMainMain);
		$this->assertTrue($obj2->getModel()->isMain());
		$this->assertSame($obj2, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain\NotMain\Main', false));
		$this->assertSame($obj2, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain', false));
		
		$obj3 = new ComhonObject('Test\Extends\ShareId\MainNotMain');
		$obj3->setId($id);
		$this->assertNotSame($obj3, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain', false));
		$this->assertNotSame($obj3, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain\NotMain\Main', false));
		
		$obj3->cast($modelNotMain);
		$this->assertNotSame($obj3, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain', false));
		$this->assertNotSame($obj3, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain\NotMain\Main', false));
		
		$obj3->cast($modelNotMainMain);
		$this->assertNotSame($obj3, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain', false));
		$this->assertNotSame($obj3, MainObjectCollection::getInstance()->getObject($id, 'Test\Extends\ShareId\MainNotMain\NotMain\Main', false));
	}
	
}
