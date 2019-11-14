<?php

use PHPUnit\Framework\TestCase;
use Comhon\Model\Singleton\ModelManager;
use Comhon\Object\ComhonObject;
use Test\Comhon\Data;
use Comhon\Object\Config\Config;

class InheritedSerializationTest extends TestCase
{
	public static function setUpBeforeClass()
	{
		Config::setLoadPath(Data::$config);
	}
	
	public function testParentSerializationInstance()
	{
		// start from Test\GreatGrandParent\GrandParent\ParentOne\Child
		$currentModel = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertSame($currentModel->getSerializationSettings(), $currentModel->getParent()->getSerializationSettings());
		$this->assertEquals(
			'{"saticPath":".\/data\/inherited_serialization","staticName":"file.json"}',
			json_encode($currentModel->getSerializationSettings()->getValues())
		);
		
		$currentModel = $currentModel->getParent();
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertSame($currentModel->getSerializationSettings(), $currentModel->getParent()->getSerializationSettings());
		
		$currentModel = $currentModel->getParent();
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNull($currentModel->getParent()->getSerializationSettings());
		
		// start from Test\GreatGrandParent\GrandParent\ParentTwo\Child
		$currentModel = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$childSerialization = $currentModel->getSerializationSettings();
		
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNotSame($childSerialization, $currentModel->getParent()->getSerializationSettings());
		
		$currentModel = $currentModel->getParent();
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertEquals(
			'{"saticPath":".\/data\/inherited_serialization_two","staticName":"file.json"}',
			json_encode($currentModel->getSerializationSettings()->getValues())
		);
		
		$currentModel = $currentModel->getParent();
		$this->assertSame($childSerialization, $currentModel->getSerializationSettings());
		$this->assertInstanceOf(ComhonObject::class, $currentModel->getSerializationSettings());
		$this->assertNull($currentModel->getParent()->getSerializationSettings());
	}
	
	public function testInheritanceKey()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent');
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne');
		$this->assertEquals('grand_parent', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('child_one', $model->getSerialization()->getInheritanceKey());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals(null, $model->getSerialization()->getInheritanceKey());
	}
	
	public function testSerializationAllowed()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne');
		$this->assertFalse($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertTrue($model->getSerialization()->isSerializationAllowed());
	}
	
	public function testFirstParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstMainParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstMainParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstMainParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstMainParentMatch(true, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getFirstMainParentMatch(true, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstMainParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstMainParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentTwo', $model->getFirstMainParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstMainParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentTwo', $model->getFirstMainParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getFirstMainParentMatch(true, true)->getName());
		$this->assertNull($model->getFirstMainParentMatch(true, false));
		$this->assertEquals('Test\GreatGrandParent', $model->getFirstMainParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentTwo', $model->getFirstMainParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParentTwo\Parent\Child');
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstMainParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstMainParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstMainParentMatch(null, false)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParentTwo\Parent', $model->getFirstMainParentMatch(true, false)->getName());
		$this->assertNull($model->getFirstMainParentMatch(false));
		$this->assertNull($model->getFirstMainParentMatch(false, true));
		$this->assertNull($model->getFirstMainParentMatch(false, false));
		$this->assertNull($model->getFirstMainParentMatch(true, true));
		$this->assertNull($model->getFirstMainParentMatch(null, true));
	}
	
	public function testLastParentMatch()
	{
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentOne\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastMainParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastMainParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastMainParentMatch(true, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent\ParentOne', $model->getLastMainParentMatch(true, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParent\ParentTwo\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastMainParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastMainParentMatch(null, true)->getName());
		$this->assertEquals('Test\GreatGrandParent\GrandParent', $model->getLastMainParentMatch(true, true)->getName());
		$this->assertNull($model->getLastMainParentMatch(true, false));
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch(false, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch(false)->getName());
		
		$model = ModelManager::getInstance()->getInstanceModel('Test\GreatGrandParent\GrandParentTwo\Parent\Child');
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch()->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch(true)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch(null, false)->getName());
		$this->assertEquals('Test\GreatGrandParent', $model->getLastMainParentMatch(true, false)->getName());
		$this->assertNull($model->getLastMainParentMatch(false));
		$this->assertNull($model->getLastMainParentMatch(false, true));
		$this->assertNull($model->getLastMainParentMatch(false, false));
		$this->assertNull($model->getLastMainParentMatch(true, true));
		$this->assertNull($model->getLastMainParentMatch(null, true));
	}
	
}
