<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;
use Comhon\Object\ComhonObject;
use Comhon\Interfacer\StdObjectInterfacer;

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
		$this->assertSame($modelParentTwo->getSharedIdModel(), $modelGrandParent);
		$this->assertSame($modelChildThree->getSharedIdModel(), $modelGrandParent);
		$this->assertNull($modelParentThree->getSharedIdModel());
		$this->assertNull($modelGrandParent->getSharedIdModel());
	}

	/**
	 * 
	 * @dataProvider shareIdObjectCollectionData
	 */
	public function testShareIdObjectCollection($json)
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
	
	public function shareIdObjectCollectionData()
	{
		return [
			[
				'{
				    "foreignCollectionOne": [
				        "ChildThree",
				        {
				            "id": "ParentOne",
				            "__inheritance__": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentThree"
				        }
				    ],
				    "collection": [
				        {
				            "id": "ChildOne",
				            "__inheritance__": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentOne\\\\Child"
				        },
				        {
				            "id": "ParentOne",
				            "__inheritance__": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentOne"
				        },
				        {
				            "id": "ChildTwo",
				            "__inheritance__": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentTwo\\\\Child"
				        },
				        {
				            "id": "ParentTwo",
				            "__inheritance__": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentTwo"
				        },
				        {
				            "id": "ChildThree",
				            "__inheritance__": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentThree\\\\Child"
				        },
				        {
				            "id": "ParentOne",
				            "__inheritance__": "Test\\\\Extends\\\\ShareId\\\\GrandParent\\\\ParentThree"
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
	
	public function testShareIdObjectCollectionMain()
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
			'{"id":1,"children":[{"id":2,"__inheritance__":"Test\\\\Person\\\\Woman"}]}', 
			$interfacer->toString($interfacer->export($man))
		);
	}
	
}
